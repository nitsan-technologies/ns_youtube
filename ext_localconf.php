<?php
if (!defined('TYPO3')) {
    die('Access denied.');
}
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'NsYoutube',
    'Youtube',
    [
       \Nitsan\NsYoutube\Controller\YoutubeController::class => 'list,ajax'
    ],
    // non-cacheable actions
    [
       \Nitsan\NsYoutube\Controller\YoutubeController::class => 'list,ajax'
    ]
);
$iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);

$iconRegistry->registerIcon(
    'ext-ns-youtube-icon',
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    ['source' => 'EXT:ns_youtube/Resources/Public/Icons/user_plugin_youtube.svg']
);

$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.backend.enforceContentSecurityPolicy'] = false;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.frontend.enforceContentSecurityPolicy'] = false;