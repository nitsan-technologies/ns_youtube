<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}
//@extensionScannerIgnoreLine
if (version_compare(TYPO3_branch, '11.0', '>=')) {
    $moduleClass = \Nitsan\NsYoutube\Controller\YoutubeController::class;
} else {
    $moduleClass = 'Youtube';
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Nitsan.NsYoutube',
    'Youtube',
    [
        $moduleClass => 'list,ajax'
    ],
    // non-cacheable actions
    [
        $moduleClass => 'list,ajax'
    ]
);

//@extensionScannerIgnoreLine
if (version_compare(TYPO3_branch, '7.0', '>')) {
    if (TYPO3_MODE === 'BE') {
        $icons = [
            'ext-ns-youtube-icon' => 'user_plugin_youtube.svg',
        ];
        $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Imaging\IconRegistry::class
        );
        foreach ($icons as $identifier => $path) {
            $iconRegistry->registerIcon(
                $identifier,
                \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
                ['source' => 'EXT:ns_youtube/Resources/Public/Icons/' . $path]
            );
        }
    }
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['ns_youtube']
        = \Nitsan\NsYoutube\Hooks\PageLayoutView::class;
