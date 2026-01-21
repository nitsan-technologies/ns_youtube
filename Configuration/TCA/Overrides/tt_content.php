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
        'ext-ns-youtube-icon',
        'plugins'
    );

    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        '--div--;plugin,pi_flexform,',
        $pluginSignature,
        'after:subheader',
    );

    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:ns_youtube/Configuration/FlexForms/FlexForm.xml',
        'nsyoutube_youtube'
    );
 

