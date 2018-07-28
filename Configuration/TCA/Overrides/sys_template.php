<?php
defined('TYPO3_MODE') or die();

$extKey = 'ns_youtube';

 \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($extKey, 'Configuration/TypoScript', 'Nitsan Youtube');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_nsyoutube_domain_model_youtube', 'EXT:ns_youtube/Resources/Private/Language/locallang_csh_tx_youtube_domain_model_youtube.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_nsyoutube_domain_model_youtube');