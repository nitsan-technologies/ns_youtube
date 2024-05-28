<?php
defined('TYPO3_MODE') or die();

$extKey = 'ns_youtube';

 \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
     $extKey,
     'Configuration/TypoScript',
     'Youtube'
 );

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages(
    'tx_nsyoutube_domain_model_youtube'
);
