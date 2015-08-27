<?php

namespace HeimrichHannot\AvisotaSubscriptionRecipientPlus;

use Avisota\Contao\Core\CoreEvents;
use Avisota\Contao\Core\Event\CreateRecipientSourceEvent;
use Avisota\Contao\Core\RecipientSource\RecipientSourceFactoryInterface;
use Avisota\Contao\Entity\RecipientSource;
use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\Controller\GenerateFrontendUrlEvent;
use ContaoCommunityAlliance\Contao\Bindings\Events\Controller\GetPageDetailsEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MembersRecipientSourceNoEmailFactory implements RecipientSourceFactoryInterface
{
	public function createRecipientSource(RecipientSource $recipientSourceEntity)
	{
		$recipientSource = new MembersRecipientSourceNoEmail();

		if ($recipientSourceEntity->getFilter()) {
			if ($recipientSourceEntity->getMembersUseGroupFilter()) {
				$recipientSource->setFilteredGroups($recipientSourceEntity->getMembersGroupFilter());
			}
			/*
			if ($recipientSourceEntity->getFilterByMailingLists()) {
				$recipientSource->setFilteredMailingLists($recipientSourceEntity->getMailingLists()->toArray());
			}
			*/
			if ($recipientSourceEntity->getMembersUsePropertyFilter()) {
				$recipientSource->setFilteredProperties($recipientSourceEntity->getMembersPropertyFilter());
			}
		}

		/** @var EventDispatcherInterface $eventDispatcher */
		$eventDispatcher = $GLOBALS['container']['event-dispatcher'];

		/*
		if ($recipientSourceEntity->getMembersManageSubscriptionPage()) {
			$getPageDetailsEvent = new GetPageDetailsEvent($recipientSourceEntity->getMembersManageSubscriptionPage());
			$eventDispatcher->dispatch(ContaoEvents::CONTROLLER_GET_PAGE_DETAILS, $getPageDetailsEvent);

			$generateFrontendUrlEvent = new GenerateFrontendUrlEvent($getPageDetailsEvent->getPageDetails());
			$eventDispatcher->dispatch(ContaoEvents::CONTROLLER_GENERATE_FRONTEND_URL, $generateFrontendUrlEvent);

			$url = $generateFrontendUrlEvent->getUrl();
			$url .= (strpos($url, '?') !== false ? '&' : '?') . 'avisota_subscription_email=##email##';

			if (!preg_match('~^\w+:~', $url)) {
				$environment = \Environment::getInstance();
				$url         = rtrim($environment->base, '/') . '/' . ltrim($url, '/');
			}

			$recipientSource->setManageSubscriptionUrlPattern($url);
		}
		*/

		/*
		if ($recipientSourceEntity->getMembersUnsubscribePage()) {
			$getPageDetailsEvent = new GetPageDetailsEvent($recipientSourceEntity->getMembersUnsubscribePage());
			$eventDispatcher->dispatch(ContaoEvents::CONTROLLER_GET_PAGE_DETAILS, $getPageDetailsEvent);

			$generateFrontendUrlEvent = new GenerateFrontendUrlEvent($getPageDetailsEvent->getPageDetails());
			$eventDispatcher->dispatch(ContaoEvents::CONTROLLER_GENERATE_FRONTEND_URL, $generateFrontendUrlEvent);

			$url = $generateFrontendUrlEvent->getUrl();
			$url .= (strpos($url, '?') !== false ? '&' : '?') . 'avisota_subscription_email=##email##';

			if (!preg_match('~^\w+:~', $url)) {
				$environment = \Environment::getInstance();
				$url         = rtrim($environment->base, '/') . '/' . ltrim($url, '/');
			}

			$recipientSource->setUnsubscribeUrlPattern($url);
		}
		*/

		$event = new CreateRecipientSourceEvent($recipientSourceEntity, $recipientSource);
		$eventDispatcher->dispatch(CoreEvents::CREATE_RECIPIENT_SOURCE, $event);

		return $event->getRecipientSource();
	}
}
