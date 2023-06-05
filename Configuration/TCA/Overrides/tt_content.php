<?php
defined('TYPO3') or die();

/***************
 * Plugin
 */
 $pluginSignature = \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
     'NsYoutube',
     'Youtube',
     'Youtube'
 );

 $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['nsyoutube_youtube'] = 'recursive,select_key,pages';

 $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
 \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:ns_youtube/Configuration/FlexForms/FlexForm.xml');
