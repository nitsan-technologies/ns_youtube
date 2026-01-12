<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Nitsan\NsYoutube\Controller\YoutubeController;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;


$versionNumber =  VersionNumberUtility::convertVersionStringToArray(VersionNumberUtility::getCurrentTypo3Version());
if ($versionNumber['version_main'] == '12') {

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
}
else {
    ExtensionUtility::configurePlugin(
    'NsYoutube',
    'Youtube',
    [
        YoutubeController::class => 'list,ajax'
    ],
    // non-cacheable actions
    [
        YoutubeController::class => 'list,ajax'
    ],null
);
}