<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

/***************
 * Plugin
 */
$pluginSignature = ExtensionUtility::registerPlugin(
    'NsYoutube',
    'Youtube',
    'Youtube',
    '',
    'plugins'
);

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['nsyoutube_youtube'] = 'recursive,select_key,pages';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
ExtensionManagementUtility::addPiFlexFormValue(
    $pluginSignature,
    'FILE:EXT:ns_youtube/Configuration/FlexForms/FlexForm.xml'
);


