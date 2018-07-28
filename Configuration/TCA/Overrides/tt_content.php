<?php
defined('TYPO3_MODE') or die();

/***************
 * Plugin
 */
 \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
 	'NS.ns_youtube',
 	'Youtube',
 	'Youtube'
 );

 $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['nsyoutube_youtube'] = 'recursive,select_key,pages';

 $pluginSignature = str_replace('_', '', 'ns_youtube') . '_' . 'youtube';
 $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
 \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature,'FILE:EXT:ns_youtube/Configuration/FlexForms/FlexForm.xml');