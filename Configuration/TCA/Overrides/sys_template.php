<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

$extKey = 'ns_youtube';

ExtensionManagementUtility::addStaticFile(
    $extKey,
    'Configuration/TypoScript',
    'Youtube'
);
