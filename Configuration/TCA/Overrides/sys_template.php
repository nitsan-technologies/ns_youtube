<?php
defined('TYPO3') or die();

$extKey = 'ns_youtube';

 \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($extKey, 'Configuration/TypoScript', '[NITSAN] Youtube');