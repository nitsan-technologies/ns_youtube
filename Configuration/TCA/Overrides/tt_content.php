<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

/***************
 * Plugin
 */
$version = VersionNumberUtility::convertVersionStringToArray(
    VersionNumberUtility::getCurrentTypo3Version()
);
if ($version['version_main'] <= 13) {
    $pluginSignature = ExtensionUtility::registerPlugin(
        'NsYoutube',
        'Youtube',
        'Youtube',
        'ext-ns-youtube-icon',
        'plugins'
    );

    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['nsyoutube_youtube'] = 'recursive,select_key,pages';

    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['nsyoutube_youtube'] = 'pi_flexform';
    if ($version['version_main'] == 12) {
    ExtensionManagementUtility::addPiFlexFormValue(
    'nsyoutube_youtube',
    'FILE:EXT:ns_youtube/Configuration/FlexForms/FlexForm.xml'
    );
    } else {

    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:ns_youtube/Configuration/FlexForms/FlexForm.xml',
        'nsyoutube_youtube'
    );
    }
} else {
    $pluginSignature = ExtensionUtility::registerPlugin(
        'NsYoutube',
        'Youtube',
        'Youtube',
        'ext-ns-youtube-icon',
        'plugins',
        '',
        'FILE:EXT:ns_youtube/Configuration/FlexForms/FlexForm.xml'
    );
}
