
    <?php

defined('TYPO3') or die();

return [
    'ext-ns-youtube-icon' =>[
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
'source' => 'EXT:ns_youtube/Resources/Public/Icons/user_plugin_youtube.svg'],
    ];





// $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);

// $iconRegistry->registerIcon(
//     'ext-ns-youtube-icon',
//     SvgIconProvider::class,
//     ['source' => 'EXT:ns_youtube/Resources/Public/Icons/user_plugin_youtube.svg']
// );



// return [
//     // Register icons for the plugin
//     'ext-ns-youtube-icon' => [
//         'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
//         'source' => 'EXT:ns_helpdesk/Resources/Public/Icons/ns_helpdesk-plugin-helpdesk.svg',
//     ],
   
//     // You can add more icons here as needed
// ];