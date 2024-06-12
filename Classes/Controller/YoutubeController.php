<?php
namespace Nitsan\NsYoutube\Controller;

use TYPO3\CMS\Core\Http\HttpRequest;

/***
 *
 * This file is part of the "Youtube" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2017
 *
 ***/
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Annotation\Inject as inject;

/**
 * YoutubeController
 */
class YoutubeController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * finalsrc
     * @var string
     */
    public string $finalsrc = '';

    public $badentities = ['&#215;', '×', '&#8211;', '–', '&amp;', '&#038;', '&#38;'];
    public $goodliterals = ['x', 'x', '--', '--', '&', '&', '&'];

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     * @extensionScannerIgnoreLine
     * @inject
     */
    protected $configurationManager;

    /**
     * action list
     *
     * @return void
     */
    public function listAction()
    {
        // Delcare variables
        $video = (object) [];
        $options = (object) [];
        $error = $videoid = $link = $centercode = '';
        $jsonResult = [];
        $constant = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_nsyoutube.']['settings.'];
        $usenocookie = $showcontrol = $showrelatedvideo = 0;
        // Get link
        if ($this->settings['listType'] == 'single') {
            $link = trim(
                str_replace($this->badentities, $this->goodliterals, $this->settings['videourl'])
            );
            $showcontrol = trim(
                str_replace($this->badentities, $this->goodliterals, $this->settings['showcontrol'])
            );
            $showrelatedvideo = trim(
                str_replace($this->badentities, $this->goodliterals, $this->settings['showrelatedvideo'])
            );
            $usenocookie = trim(
                str_replace($this->badentities, $this->goodliterals, $this->settings['nocookie'])
            );
        } else {
            if ($this->settings['listType'] == 'playlist') {
                $link = trim(
                    str_replace($this->badentities, $this->goodliterals, $this->settings['playlisturl'])
                );
            }
        }

        $link = preg_replace('/\s/', '', $link);
        $linkparamstemp = explode('?', $link);
        $linkparams = [];

        if (count($linkparamstemp) > 1) {
            $linkparams = $this->keyvalue($linkparamstemp[1], true);
        }

        if ($this->settings['listType'] == 'single') {
            if (isset($linkparams['v']) && $linkparams['v'] != '') {
                $videoid = $linkparams['v'];
            } elseif (isset($linkparams['list'])) {
                $finalparams = $linkparams + $this->settings;
                $options = $this->getOptions($finalparams);
                $jsonResult = $this->getVideoFromList($options);
                $videoid = $this->getVideo($jsonResult, $finalparams)->videoid;
            }
        } else {
            if (!empty($this->settings['apiKey'])) {
                if ($this->settings['listType'] == 'playlist') {
                    if ($this->settings['layout'] == 'gallery') {
                        $finalparams = $linkparams + $this->settings;
                        $options = $this->getOptions($finalparams);
                        $jsonResult = $this->getVideoFromList($options);
                        if (!empty($jsonResult)) {
                            $video = $this->getVideo($jsonResult, $finalparams);
                            $videoid = $video->init_id ?? '';
                        } elseif ($linkparams['v'] != '' && !empty($linkparams['v'])) {
                            $videoid = $linkparams['v'];
                        }

                    } else {
                        if ($this->settings['layout'] == 'playlist') {
                            $finalparams = $linkparams + $this->settings;
                            if (isset($finalparams['list'])) {
                                if ($finalparams['list'] != '' && isset($finalparams['list'])) {
                                    $this->finalsrc = $this->getPlaylistView($finalparams);
                                } elseif ($finalparams['v'] != '' && isset($finalparams['v'])) {
                                    $videoid = $finalparams['v'];
                                    $error = 1;
                                }
                            }
                        } else {
                            $error = 1;
                        }
                    }
                } else {
                    $finalparams = $linkparams + $this->settings;
                    $options = $this->getOptions($finalparams);
                    $jsonResult = $this->getChannelVideo($options);
                    if ($this->settings['layout'] == 'gallery' && !empty($jsonResult)) {
                        $video = $this->getVideo($jsonResult, '');
                        $videoid = $video->init_id ?? '';
                    } else {
                        $cnt = 0;
                        if (isset($jsonResult->items) && $jsonResult->items != null && is_array($jsonResult->items)) {
                            foreach ($jsonResult->items as $item) {
                                $param = new \stdClass();
                                //@extensionScannerIgnoreLine
                                $param->id = $item->id->videoId ?? null;
                                $param->list = $item->snippet->channelId ?? null;
                                if ($cnt == 0 && $options->pageToken == null) {
                                    $linkparams['v'] = $param->id;
                                    $linkparams['list'] = $param->list;
                                }
                                $cnt++;
                            }
                            $finalparams = $linkparams + $this->settings;
                            if ($finalparams['list'] != '' && isset($finalparams['list'])) {
                                $this->finalsrc = $this->getPlaylistView($finalparams);
                            } elseif ($finalparams['v'] != '' && isset($finalparams['v'])) {
                                $videoid = $finalparams['v'] ?? '';
                                $error = 1;
                            }
                        }
                    }
                }
            }
        }

        $thumbplay = $this->settings['thumbplay'] ?? '1';
        $youtubebaseurl = ($usenocookie == 1) ? 'youtube-nocookie' : 'youtube';

        if (!empty($videoid) || isset($this->finalsrc)) {
            $ifram_src = '';
            if ($this->settings['enableGdpr'] || $constant['enableGdpr']) {
                $ifram_src = 'data-';
            }
            $code = '<iframe id="_ytid_' . rand(10000, 99999) . '" ' . $ifram_src . 'src="https://www.'
                . $youtubebaseurl . '.com/embed/' . $videoid . '?autoplay=' . $thumbplay .
                '&controls=' . $showcontrol . '&rel=' . $showrelatedvideo;
            $code = $code . $this->finalsrc . '" class="__youtube_prefs__" allowfullscreen '
                . $centercode . '"></iframe>';
            $popupLink = 'https://www.' . $youtubebaseurl . '.com/embed/' . $videoid .
                '?autoplay=' . $thumbplay . '&controls=' . $showcontrol . '&rel=' . $showrelatedvideo;

        }

        if (
            empty($this->settings['apiKey']) &&
            ($this->settings['listType'] == 'playlist' ||
                $this->settings['listType'] == 'channel')
        ) {
            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'add_api_key',
                    'ns_youtube'
                ),
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );
        }

        $this->view->assignMultiple([
            'constant' => $constant ?? '',
            'totalPages' => $video->totalPages ?? '',
            'options' => $options ?? '',
            'nextPageToken' => $video->nextPageToken ?? '',
            'prevPageToken' => $video->prevPageToken ?? '',
            'subscibe' => $video->channelTitle ?? '',
            'iframe' => $code ?? '',
            'error' => $error,
            'jsonResult' => $jsonResult ?? '',
            'popupLink' => $popupLink ?? ''
        ]);
    }

    /**
     * action ajax
     *
     * @return void
     */
    public function ajaxAction()
    {
        if($this->request->hasArgument('settings')) {
            $this->settings = $this->request->getArgument('settings');
        }
        if (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        ) {
            $options = (object) $_POST;
            $options->apiKey = $this->settings['apiKey'];
            $options->pageSize = $this->settings['pagesize'] ?? 5;
            if ($this->settings['listType'] == 'channel') {
                $jsonResult = $this->getChannelVideo($options);
            } else {
                $jsonResult = $this->getVideoFromList($options);
            }
            $gallery = $this->getVideo($jsonResult, $options);
            $nextvideo = $gallery->html ?? '';
            echo $nextvideo;
            die();
        }
    }

    // Get Options
    public function getOptions($options)
    {
        $opt = new \stdClass();
        $opt->playlistId = (isset($options['list'])) ? $options['list'] : '';
        $opt->pageToken = null;
        $opt->pageSize = empty($options['pagesize']) ? 5 : $options['pagesize'];
        $opt->apiKey = $this->settings['apiKey'];
        return $opt;
    }

    // Get keyValue
    public function keyvalue($qry, $includev)
    {
        $ytvars = explode('&', $qry);
        $ytkvp = [];
        foreach ($ytvars as $v) {
            $kvp = explode('=', $v);
            if (count($kvp) == 2 && ($includev || strtolower($kvp[0]) != 'v')) {
                $ytkvp[$kvp[0]] = $kvp[1];
            }
        }

        return $ytkvp;
    }


    // Get Videos from list parameter
    public function getVideoFromList($options)
    {
        $apiEndpoint = 'https://www.googleapis.com/youtube/v3/playlistItems?part=snippet,status&playlistId='
            . $options->playlistId . '&maxResults=' . $options->pageSize . '&key=' . $options->apiKey;

        if ($options->pageToken != null) {
            $apiEndpoint .= '&pageToken=' . $options->pageToken;
        }
        try {
            $apiResult = $this->connectAPI($apiEndpoint);
            return json_decode($apiResult->getBody());
        } catch (\Exception $e) {
            $error = $this->catchException($e);
            $this->addFlashMessage(
                $error,
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );
            return [];
        }
    }

    // Get Channel Video
    public function getChannelVideo($options)
    {
        try {
            if (
                $this->settings['channeltype'] == 'username' &&
                isset($this->settings['channelname']) &&
                $this->settings['channelname'] != ''
            ) {
                $api = 'https://www.googleapis.com/youtube/v3/channels?part=snippet&forHandle=' .
                    str_replace(' ', '', $this->settings['channelname']) . '&key=' . $this->settings['apiKey'];

                $result = $this->connectAPI($api);
                $jsonresult = json_decode($result->getBody());
                if (isset($jsonresult->items)) {
                    foreach ($jsonresult->items as $item) {
                        $channelId = $item->id;
                    }
                }
            } elseif ($this->settings['channelid'] != '' && isset($this->settings['channelid'])) {
                $channelId = $this->settings['channelid'];
            }
            $channelId = (isset($channelId)) ? $channelId : '';

            if ($channelId != null) {
                $apiEndpoint = 'https://www.googleapis.com/youtube/v3/search?part=snippet,id&type=video&order=date&channelId='
                    . $channelId . '&maxResults=' . $options->pageSize . '&key=' . $this->settings['apiKey'];
            } else {
                $this->addFlashMessage(
                    \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                        'add_user_name',
                        'ns_youtube'
                    ),
                    '',
                    \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
                );
                return [];
               }

            $apiEndpoint = 'https://www.googleapis.com/youtube/v3/search?part=snippet,id&type=video&order=date&channelId='
                . $channelId . '&maxResults=' . $options->pageSize . '&key=' . $this->settings['apiKey'];
            if ($options->pageToken != null) {
                $apiEndpoint .= '&pageToken=' . $options->pageToken;
            }
            $apiResult = $this->connectAPI($apiEndpoint);
            $jsonResult = json_decode($apiResult->getBody());
            //@extensionScannerIgnoreLine
            $jsonResult->pageInfo->resultsPerPage = $options->pageSize;
            return $jsonResult;
        } catch (\Exception $e) {
            $error =  \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                'invalid_api_key',
                'ns_youtube'
            );
            $this->addFlashMessage(
                $error,
                '',
                \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
            );
            return [];
        }
    }

    // Get Video Id
    public function getVideo($jsonResult, $options)
    {
        $obj = new \stdClass();
        //@extensionScannerIgnoreLine
        $resultsPerPage = $jsonResult->pageInfo->resultsPerPage;
        //@extensionScannerIgnoreLine
        $totalResults = $jsonResult->pageInfo->totalResults;
        $totalPages = 0;
        //@extensionScannerIgnoreLine
        if (!empty($jsonResult->pageInfo->totalResults)) {
            $totalPages = ceil($totalResults / $resultsPerPage);
        }

        $nextPageToken = $prevPageToken = '';
        if (isset($jsonResult->nextPageToken)) {
            $nextPageToken = $jsonResult->nextPageToken;
        }
        if (isset($jsonResult->prevPageToken)) {
            $prevPageToken = $jsonResult->prevPageToken;
        }
        $cnt = 0;
        if (isset($jsonResult->items) && $jsonResult->items != null && is_array($jsonResult->items)) {
            $results = new \stdClass();
            $thumb = new \stdClass();
            foreach ($jsonResult->items as $item) {
                $results->key = $item;
                $thumb->id = $item->snippet->resourceId->videoId ?? $item->id->videoId;
                //@extensionScannerIgnoreLine
                $thumb->title = isset($options->showTitle) ? $item->snippet->title : '';
                $thumb->privacyStatus = $item->status->privacyStatus ?? null;
                $thumb->subscibe = $item->snippet->channelId;
                $optionspageToken = $options->pageToken ?? '';

                if ($cnt == 0 && $optionspageToken == null) {
                    $init_id = $thumb->id;
                }
                $cnt++;
                $results->key->snippet->datavideoid = $thumb->id;
            }
            $obj->prevPageToken = $prevPageToken;
            $obj->nextPageToken = $nextPageToken;
            $obj->channelTitle = $thumb->subscibe;
            $obj->init_id = $init_id ?? '';
            $obj->settings = $this->settings;
            $obj->options = $options;
            $obj->totalPages = $totalPages;
            $obj->jsonResult = $jsonResult;
            $gallery = json_decode(json_encode($obj), true);
            if ($gallery != null) {
                $html = $this->getTemplateHtml(
                    'Youtube',
                    'Gallery',
                    $gallery
                );
                $obj->html = $html;
            }
            return $obj;
        }
    }

    // Get Plylist View
    public function getPlaylistView($finalparams)
    {
        $autoplay = (isset($this->settings['autoplay'])) ? $this->settings['autoplay'] : '';
        $yt_options = [
            $autoplay,
            $this->settings['listType'],
            'index',
            'list',
            'start',
            'v',
            'end'
        ];

        if ($finalparams['listType'] == 'channel') {
            $playlistId = $this->getPlaylistId($finalparams['list']);
            $this->finalsrc .= 'listType=playlist&list=' . $playlistId;
        } else {
            foreach ($finalparams as $key => $value) {
                if (in_array($key, $yt_options)) {
                    $this->finalsrc .= htmlspecialchars($key) . '=' . htmlspecialchars($value) . '&';
                    if ($value == 1 && !isset($finalparams['list'])) {
                        $this->finalsrc .= 'playlist=' . $finalparams['list'] . '&';
                    }
                }
            }
        }
        return $this->finalsrc;
    }

    // Get Plyalist Id
    public function getPlaylistId($channelId)
    {
        $apiEndpoint = 'https://www.googleapis.com/youtube/v3/channels?part=contentDetails&key='
            . $this->settings['apiKey'] . '&id=' . $channelId;
        $apiResult = $this->connectAPI($apiEndpoint);
        $jsonResult = json_decode($apiResult->getBody());
        foreach ($jsonResult->items as $item) {
            $playlistId = $item->contentDetails->relatedPlaylists->uploads;
        }
        return $playlistId ?? null;
    }

    // Exception
    public function catchException($e)
    {
        if (property_exists($e, 'response')) {
            $response = $e->getResponse();
        }
        $response = $response ?? null;
        if (empty($response) && $response == null) {
            $error = $e->getMessage();
        } else {
            $jsonBody = json_decode($response->getBody()->getContents(), 1);
            $error = $jsonBody['error']['message'];
        }
        return $error;
    }

    // Connect API
    public function connectAPI($url)
    {
        $version = GeneralUtility::makeInstance(VersionNumberUtility::class);
        $versionNum = $version->getNumericTypo3Version();
        $explode = explode('.', $versionNum);

        if ($explode[0] == 7 || $explode[0] < 7) {
            // Http request for typo3 version 7 and lower than 7
            $request = GeneralUtility::makeInstance(HttpRequest::class, $url);
            $response = $request->send();
        } else {
            // Http request for typo3 version 8 and up
            $request = GeneralUtility::makeInstance(RequestFactory::class);
            $response = $request->request($url);
        }
        return $response;
    }

    // Get HTML
    public function getTemplateHtml($controllerName, $templateName, array $variables = [])
    {
        /** @var \TYPO3\CMS\Fluid\View\StandaloneView $tempView */
        $tempView = $this->objectManager->get('TYPO3\\CMS\\Fluid\\View\\StandaloneView');

        $extbaseFrameworkConfiguration = $this->configurationManager
            ->getConfiguration(
                \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
            );

        // From TYPO3 7 templateRootPath has changed to templateRootPaths (plural),
        // so get the last existing template file in array (fallback)
        $partialRootPaths = $extbaseFrameworkConfiguration['view']['partialRootPaths'];
        $templatePathAndFilename = '';
        foreach (array_reverse($partialRootPaths) as $partialRootPath) {
            $templatePathAndFilename = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(
                $partialRootPath . $controllerName . '/' . $templateName . '.html'
            );

            if (file_exists($templatePathAndFilename)) {
                break;
            }
        }

        $tempView->setTemplatePathAndFilename($templatePathAndFilename);
        // Set layout and partial root paths
        $tempView->assignMultiple($variables);
        return $tempView->render();
    }
}
