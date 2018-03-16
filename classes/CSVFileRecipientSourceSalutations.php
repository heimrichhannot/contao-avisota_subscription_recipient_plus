<?php

/**
 * Avisota newsletter and mailing system
 *
 * PHP Version 5.3
 *
 * @copyright  bit3 UG 2013
 * @author     Tristan Lins <tristan.lins@bit3.de>
 * @package    avisota-core
 * @license    LGPL-3.0+
 * @link       http://avisota.org
 */

namespace HeimrichHannot\AvisotaSubscriptionRecipientPlus;

use Avisota\Recipient\MutableRecipient;
use Avisota\RecipientSource\RecipientSourceInterface;
use Contao\Doctrine\ORM\EntityHelper;

/**
 * A recipient source that read the recipients from a csv file.
 *
 * @package avisota-core
 */
class CSVFileRecipientSourceSalutations implements RecipientSourceInterface
{

    private $file;

    private $columnAssignment;

    private $delimiter;

    private $enclosure;

    private $escape;

    /**
     * @var \Swift_Mime_Grammar
     */
    private $grammar;

    /**
     * @param string $fileData
     */
    public function __construct($file, array $columnAssignment, $delimiter = ',', $enclosure = '"', $escape = '\\')
    {
        $this->file             = (string) $file;
        $this->columnAssignment = $columnAssignment;
        $this->delimiter        = $delimiter;
        $this->enclosure        = $enclosure;
        $this->escape           = $escape;
    }

    /**
     * @return \Swift_Mime_Grammar
     */
    public function getGrammar()
    {
        if (!$this->grammar)
        {
            $this->grammar = new \Swift_Mime_Grammar();
        }

        return $this->grammar;
    }

    /**
     * @param \Swift_Mime_Grammar $grammar
     *
     * @return CSVFile
     */
    public function setGrammar(\Swift_Mime_Grammar $grammar)
    {
        $this->grammar = $grammar;

        return $this;
    }

    /**
     * Count the recipients.
     *
     * @return int
     */
    public function countRecipients()
    {
        $in = fopen($this->file, 'r');

        if (!$in)
        {
            return 0;
        }

        $recipients = 0;
        $regexp     = '/^' . $this->getGrammar()->getDefinition('addr-spec') . '$/D';
        $index      = array_search('email', $this->columnAssignment);
        $emails     = [];

        while ($row = fgetcsv($in, 0, $this->delimiter, $this->enclosure, $this->escape))
        {
            $email = trim($row[$index]);

            if (!empty($email) && preg_match($regexp, $email) && !in_array($email, $emails))
            {
                $recipients++;
                $emails[] = $email;
            }
        }

        fclose($in);

        return $recipients;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecipients($limit = null, $offset = null)
    {
        $in = fopen($this->file, 'r');

        if (!$in)
        {
            return null;
        }

        $recipients = [];
        $regexp     = '/^' . $this->getGrammar()->getDefinition('addr-spec') . '$/D';
        $index      = array_search('email', $this->columnAssignment);
        $emails     = [];

        // skip offset lines
        for (; $offset > 0 && !feof($in); $offset--)
        {
            $row   = fgetcsv($in, 0, $this->delimiter, $this->enclosure, $this->escape);
            $email = trim($row[$index]);

            // skip invalid lines without counting them
            if (empty($email) || !preg_match($regexp, $email) || in_array($email, $emails))
            {
                $offset++;
            }
            else
            {
                $emails[] = $email;
            }
        }

        // read lines
        while ((!$limit || count($recipients) < $limit)
               && $row = fgetcsv($in, 0, $this->delimiter, $this->enclosure, $this->escape))
        {
            $details = [];

            foreach ($this->columnAssignment as $index => $field)
            {
                if (isset($row[$index]))
                {
                    $details[$field] = trim($row[$index]);
                }
            }

            if (!empty($details['email'])
                && preg_match($regexp, $details['email'])
                && !in_array($details['email'], $emails)
            )
            {
                $details = static::addMemberProperties($details);

                $email = $details['email'];

                if (!static::checkIfUnsubscribed($email))
                {
                    $recipients[] = new MutableRecipient($email, $details);
                    $emails[]     = $email;
                }
            }
        }

        fclose($in);

        return $recipients;
    }

    public static function checkIfUnsubscribed($email)
    {
        if (null === ($member = \MemberModel::findByEmail($email)))
        {
            return true;
        }

        return $member->skipAvisotaEmails;
    }

    public static function addMemberProperties($arrDetails)
    {
        $arrRecipientFields = [];

        \Controller::loadDataContainer('orm_avisota_recipient');

        foreach ($GLOBALS['TL_DCA']['orm_avisota_recipient']['metapalettes']['default'] as $strPalette => $arrFields)
        {
            $arrRecipientFields = array_merge($arrRecipientFields, $arrFields);
        }

        $objMember = \MemberModel::findByEmail($arrDetails['email']);

        foreach ($arrRecipientFields as $strName)
        {
            // ignore member data if a csv column is already there
            if ($arrDetails[$strName])
            {
                continue;
            }

            // ignore salutations inserted in the backend
            if ($strName == 'salutation')
            {
                continue;
            }

            if ($strName != 'email')
            {
                $arrDetails[$strName] = '';
            }

            // enhance with member data if existing
            if ($objMember !== null)
            {
                if ($objMember->$strName)
                {
                    $arrDetails[$strName] = $objMember->$strName;
                }
                else
                {
                    // try synonyms
                    $synonymizer = $GLOBALS['container']['avisota.recipient.synonymizer'];
                    $arrSynonyms = $synonymizer->findSynonyms($strName);

                    if ($arrSynonyms)
                    {
                        foreach ($arrSynonyms as $strSynonym)
                        {
                            if ($objMember->$strSynonym)
                            {
                                $arrDetails[$strName] = $objMember->$strSynonym;
                            }
                        }
                    }
                }
            }
        }

        return $arrDetails;
    }
}
