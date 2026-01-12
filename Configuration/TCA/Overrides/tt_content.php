<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

use TYPO3\CMS\Core\Utility\VersionNumberUtility;

defined('TYPO3') or die();

/***************
 * Plugin
 */
$version = VersionNumberUtility::convertVersionStringToArray(
       VersionNumberUtility::getCurrentTypo3Version()
       );
 if ($version['version_main'] < 14) {
$pluginSignature = ExtensionUtility::registerPlugin(
    'NsYoutube',
    'Youtube',
    'Youtube',
    'ext-ns-youtube-icon',
    'plugins'
);

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['nsyoutube_youtube'] = 'recursive,select_key,pages';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
ExtensionManagementUtility::addPiFlexFormValue(
    $pluginSignature,
    'FILE:EXT:ns_youtube/Configuration/FlexForms/FlexForm.xml'
);
}
else{

$pluginSignature = ExtensionUtility::registerPlugin(
    'NsYoutube',
    'Youtube',
    'Youtube',
    'ext-ns-youtube-icon',
    'plugins'
);

  ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        'pi_flexform, pages',
        $pluginSignature,
        'after:pi_flexform',
    );
     $showitemWithPluginTab = '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                --palette--;;general,
                --palette--;;headers,
            --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.plugin,
                pi_flexform,
            --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                --palette--;;frames,
                --palette--;;appearanceLinks,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                --palette--;;language,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                --palette--;;hidden,
                --palette--;;access,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                categories,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                rowDescription,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,';
        $extKey='ns_youtube';
        // Configure Album type
        $GLOBALS['TCA']['tt_content']['types'][$pluginSignature] = [
            'showitem' => $showitemWithPluginTab,
            'columnsOverrides' => [
                'pi_flexform' => [
                    'config' => [
                        'ds' => 'FILE:EXT:' . $extKey . '/Configuration/FlexForms/FlexForm.xml',
                    ],
                ],
            ],
        ];
    
}
