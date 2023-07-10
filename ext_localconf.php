<?php
if (!defined('TYPO3')) {
    die('Access denied.');
}
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Nitsan\NsYoutube\Controller\YoutubeController;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

ExtensionUtility::configurePlugin(
    'NsYoutube',
    'Youtube',
    [
       YoutubeController::class => 'list,ajax'
    ],
    // non-cacheable actions
    [
       YoutubeController::class => 'list,ajax'
    ]
);
$iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);

$iconRegistry->registerIcon(
    'ext-ns-youtube-icon',
    SvgIconProvider::class,
    ['source' => 'EXT:ns_youtube/Resources/Public/Icons/user_plugin_youtube.svg']
);

$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.backend.enforceContentSecurityPolicy'] = false;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.frontend.enforceContentSecurityPolicy'] = false;