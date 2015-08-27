<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */


/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(array
(
	'HeimrichHannot',
));


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	// Classes
	'HeimrichHannot\AvisotaSubscriptionRecipientPlus\RecipientsRecipientSourceSalutationsFactory' => 'system/modules/avisota_subscription_recipient_plus/classes/RecipientsRecipientSourceSalutationsFactory.php',
	'HeimrichHannot\AvisotaSubscriptionRecipientPlus\RecipientsRecipientSourceSalutations'        => 'system/modules/avisota_subscription_recipient_plus/classes/RecipientsRecipientSourceSalutations.php',
	'HeimrichHannot\AvisotaSubscriptionRecipientPlus\MembersRecipientSourceNoEmail'               => 'system/modules/avisota_subscription_recipient_plus/classes/MembersRecipientSourceNoEmail.php',
	'HeimrichHannot\AvisotaSubscriptionRecipientPlus\CSVFileRecipientSourceSalutationsFactory'    => 'system/modules/avisota_subscription_recipient_plus/classes/CSVFileRecipientSourceSalutationsFactory.php',
	'HeimrichHannot\AvisotaSubscriptionRecipientPlus\CSVFileRecipientSourceSalutations'           => 'system/modules/avisota_subscription_recipient_plus/classes/CSVFileRecipientSourceSalutations.php',
	'HeimrichHannot\AvisotaSubscriptionRecipientPlus\MembersRecipientSourceNoEmailFactory'        => 'system/modules/avisota_subscription_recipient_plus/classes/MembersRecipientSourceNoEmailFactory.php',
));
