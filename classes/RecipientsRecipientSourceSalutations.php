<?php

namespace HeimrichHannot\AvisotaSubscriptionRecipientPlus;

use Avisota\Contao\Entity\MailingList;
use Avisota\Contao\Entity\Recipient;
use Avisota\Contao\SubscriptionRecipient\RecipientSource\RecipientsRecipientSourceFactory;
use Avisota\Recipient\MutableRecipient;
use Avisota\RecipientSource\RecipientSourceInterface;
use Contao\Doctrine\ORM\EntityHelper;
use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\System\LoadLanguageFileEvent;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class AvisotaRecipientSourceIntegratedRecipients
 *
 *
 * @copyright  bit3 UG 2013
 * @author     Tristan Lins <tristan.lins@bit3.de>
 * @package    avisota/contao-subscription-recipient
 */
class RecipientsRecipientSourceSalutations extends \Avisota\Contao\SubscriptionRecipient\RecipientSource\RecipientsRecipientSource
{
	public function getRecipients($limit = null, $offset = null)
	{
		$queryBuilder = EntityHelper::getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select('r')
			->from('Avisota\Contao:Recipient', 'r');
		$this->prepareQuery($queryBuilder);
		if ($limit > 0) {
			$queryBuilder->setMaxResults($limit);
		}
		if ($offset > 0) {
			$queryBuilder->setFirstResult($offset);
		}
		$queryBuilder->orderBy('r.email');
		$query      = $queryBuilder->getQuery();
		$recipients = $query->getResult();

		$entityAccessor = EntityHelper::getEntityAccessor();

		$mutableRecipients = array();

		/** @var EventDispatcherInterface $eventDispatcher */
		$eventDispatcher = $GLOBALS['container']['event-dispatcher'];

		/** @var Recipient $recipient */
		foreach ($recipients as $recipient) {
			$properties = $entityAccessor->getPublicProperties($recipient, true);
			$properties = static::addMemberProperties($properties);

			if ($this->manageSubscriptionUrlPattern) {
				$loadLanguageEvent = new LoadLanguageFileEvent('fe_avisota_subscription');
				$eventDispatcher->dispatch(ContaoEvents::SYSTEM_LOAD_LANGUAGE_FILE, $loadLanguageEvent);

				$url = $this->manageSubscriptionUrlPattern;
				$url = preg_replace_callback(
					'~##([^#]+)##~',
					function ($matches) use ($properties) {
						if (isset($properties[$matches[1]])) {
							return $properties[$matches[1]];
						}
						return $matches[0];
					},
					$url
				);

				$properties['manage_subscription_link'] = array(
					'url'  => $url,
					'text' => &$GLOBALS['TL_LANG']['fe_avisota_subscription']['manage_subscription']
				);
			}

			if ($this->unsubscribeUrlPattern) {
				$loadLanguageEvent = new LoadLanguageFileEvent('fe_avisota_subscription');
				$eventDispatcher->dispatch(ContaoEvents::SYSTEM_LOAD_LANGUAGE_FILE, $loadLanguageEvent);

				$url = $this->unsubscribeUrlPattern;
				$url = preg_replace_callback(
					'~##([^#]+)##~',
					function ($matches) use ($properties) {
						if (isset($properties[$matches[1]])) {
							return $properties[$matches[1]];
						}
						return $matches[0];
					},
					$url
				);

				$properties['unsubscribe_link'] = array(
					'url'  => $url,
					'text' => &$GLOBALS['TL_LANG']['fe_avisota_subscription']['unsubscribe_direct']
				);
			}

			$mutableRecipients[] = new MutableRecipient(
				$recipient->getEmail(),
				$properties
			);
		}

		return $mutableRecipients;
	}

	protected function prepareQuery(QueryBuilder $queryBuilder)
	{
		$expr = $queryBuilder->expr();

		if (count($this->filteredMailingLists)) {
			$queryBuilder->innerJoin('r.subscriptions', 's');

			$or = $expr->orX();
			foreach ($this->filteredMailingLists as $index => $mailingList) {
				$or->add($expr->eq('s.mailingList', ':mailingList' . $index));
				$queryBuilder->setParameter('mailingList' . $index, $mailingList->getId());
			}

			$queryBuilder->andWhere($or);
		}

		if (count($this->filteredProperties)) {
			foreach ($this->filteredProperties as $index => $filteredProperty) {
				$property   = 'r.' . $filteredProperty['recipientsPropertyFilter_property'];
				$comparator = $filteredProperty['recipientsPropertyFilter_comparator'];
				$value      = $filteredProperty['recipientsPropertyFilter_value'];

				switch ($comparator) {
					case 'empty':
						$queryBuilder->andWhere(
							$expr->orX(
								$expr->eq($property, ':property' . $index),
								$expr->isNull($property)
							)
						);
						$value = '';
						break;

					case 'not empty':
						$queryBuilder->andWhere(
							$expr->gt($property, ':property' . $index)
						);
						$value = '';
						break;

					case 'eq':
						$queryBuilder->andWhere(
							$expr->eq($property, ':property' . $index)
						);
						break;

					case 'neq':
						$queryBuilder->andWhere(
							$expr->neq($property, ':property' . $index)
						);
						break;

					case 'gt':
						$queryBuilder->andWhere(
							$expr->gt($property, ':property' . $index)
						);
						break;

					case 'gte':
						$queryBuilder->andWhere(
							$expr->gte($property, ':property' . $index)
						);
						break;

					case 'lt':
						$queryBuilder->andWhere(
							$expr->lt($property, ':property' . $index)
						);
						break;

					case 'lte':
						$queryBuilder->andWhere(
							$expr->lte($property, ':property' . $index)
						);
						break;
				}

				$queryBuilder->setParameter(
					':property' . $index,
					$value
				);
			}
		}
	}

	public static function addMemberProperties($arrProperties)
	{
		$arrResult = array();

		foreach ($arrProperties as $strName => $strValue)
		{
			// ignore salutations inserted in the backend
			if ($strName == 'salutation')
				continue;

			$objMember = \MemberModel::findByEmail($arrProperties['email']);

			if (!$strValue)
			{
				// first store the existing name-value pair
				$arrResult[$strName] = $strValue;

				// enhance member data if existing
				if ($objMember !== null)
				{
					if ($objMember->$strName)
						$arrResult[$strName] = $objMember->$strName;
					else
					{
						// try synonyms
						$synonymizer = $GLOBALS['container']['avisota.recipient.synonymizer'];
						$arrSynonyms    = $synonymizer->findSynonyms($strName);

						if ($arrSynonyms) {
							foreach ($arrSynonyms as $strSynonym) {
								if ($objMember->$strSynonym)
								{
									$arrResult[$strName] = $objMember->$strSynonym;
								}
							}
						}
					}
				}
			}
			else
			{
				$arrResult[$strName] = $strValue;
			}
		}

		return $arrResult;
	}

}
