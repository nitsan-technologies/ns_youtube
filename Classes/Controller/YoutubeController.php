<?php

namespace Nitsan\NsYoutube\Controller;

/***
 *
 * This file is part of the "Youtube" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2023
 *
 ***/
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * YoutubeController
 */
class YoutubeController extends ActionController
{
    /**
     * @var array<mixed> $badentities
     */
    public array $badentities = ['&#215;', '×', '&#8211;', '–', '&amp;', '&#038;', '&#38;'];

    /**
     * @var array<mixed> $goodliterals
     */
    public array $goodliterals = ['x', 'x', '--', '--', '&', '&', '&'];


    /**
     * @var string $finalsrc
     */
    public string $finalsrc = '';

    /**
     * @var string $link
     */
    public string $link = '';

    /**
     * @var ConfigurationManagerInterface
     *
     */
    protected $configurationManager;

    /**
     * action list
     *
     * @return ResponseInterface
     */
    public function listAction(): ResponseInterface
    {
        $youtubebaseurl = 'youtube';
        $options = (object) [];
        $error = $videoid = $centercode = '';
        $jsonResult = $linkparamstemp = $linkparams = [];
        $constant = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_nsyoutube.']['settings.'];
        $usenocookie = $showcontrol = $showrelatedvideo = 0;
        $contentObj = $this->request->getAttribute('currentContentObject');

        // Get link
        if ($this->settings['listType'] == 'single') {
            $this->link = trim(str_replace($this->badentities, $this->goodliterals, $this->settings['videourl']));
            $showcontrol = trim(str_replace($this->badentities, $this->goodliterals, $this->settings['showcontrol']));
            $showrelatedvideo = trim(str_replace($this->badentities, $this->goodliterals, $this->settings['showrelatedvideo']));
            $usenocookie = trim(str_replace($this->badentities, $this->goodliterals, $this->settings['nocookie']));
        } elseif ($this->settings['listType'] == 'playlist') {
            $this->link = trim(str_replace($this->badentities, $this->goodliterals, $this->settings['playlisturl']));
        }

        if ($this->settings['listType'] == 'single' || $this->settings['listType'] == 'playlist') {
            $this->link = preg_replace('/\s/', '', $this->link);
            if (!empty($this->link)) {
                $linkparamstemp = GeneralUtility::trimExplode('?', $this->link);
            }

            if (count($linkparamstemp) > 1) {
                $linkparams = $this->keyvalue($linkparamstemp[1], true);
            }

            $youtubebaseurl = ($usenocookie == 1) ? 'youtube-nocookie' : 'youtube';
            if ($this->settings['listType'] == 'single') {
                $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
                $fileObjects = $fileRepository->findByRelation(
                    'tt_content',
                    'youtubeImage',
                    $contentObj->data['uid']
                );
                $fileObjects[0] = $fileObjects[0] ?? '';
                $this->view->assign('youtubeImage', $fileObjects[0]);
            }
        }

        if ($this->settings['listType'] == 'single') {
            if ($linkparams['v'] != '' && isset($linkparams['v'])) {
                $videoid = $linkparams['v'];
            } elseif (isset($linkparams['list'])) {
                $finalparams = $linkparams + $this->settings;
                $options = $this->getOptions($finalparams);
                $jsonResult = $this->getVideoFromList($options);
                $videoid = $this->getVideo($jsonResult)->videoid;
            }
        } else {

            if (!empty($this->settings['apiKey'])) {
                if ($this->settings['listType'] == 'playlist') {
                    if ($this->settings['layout'] == 'gallery') {

                        $finalparams = $linkparams + $this->settings;
                        $options = $this->getOptions($finalparams);
                        $jsonResult = $this->getVideoFromList($options);
                        if ($jsonResult) {
                            $video = $this->getVideo($jsonResult);
                            $videoid = $video->init_id ?? '';
                            $this->view->assignMultiple(
                                [
                                    'totalPages' => $video->totalPages,
                                    'nextPageToken' => $video->nextPageToken,
                                    'prevPageToken' => $video->prevPageToken,
                                    'options' => $options,
                                    'jsonResult' => $jsonResult,
                                    'subscibe' => $video->channelTitle
                                ]
                            );
                        } elseif ($linkparams['v'] != '' && !empty($linkparams['v'])) {
                            $videoid = $linkparams['v'];
                        }

                    } else {
                        if ($this->settings['layout'] == 'playlist') {
                            $finalparams = $linkparams + $this->settings;
                            if ($finalparams['list'] != '' && isset($finalparams['list'])) {
                                $this->finalsrc = $this->getPlaylistView($finalparams);
                            } elseif ($finalparams['v'] != '' && isset($finalparams['v'])) {
                                $videoid = $finalparams['v'];
                                $error = 1;
                            }
                        } else {
                            $error = 1;
                        }
                    }
                } else {
                    $finalparams = $this->settings;
                    $options = $this->getOptions($finalparams);
                    $jsonResult = $this->getChannelVideo($options);
                    if ($this->settings['layout'] == 'gallery') {
                        $video = $this->getVideo($jsonResult);
                        if (isset($video->totalPages)) {
                            $this->view->assignMultiple(
                                [
                                    'jsonResult' => $jsonResult,
                                    'totalPages' => $video->totalPages,
                                    'nextPageToken' => $video->nextPageToken,
                                    'prevPageToken' => $video->prevPageToken,
                                    'options' => $options,
                                    'subscibe' => $video->channelTitle
                                ]
                            );
                        }

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
                                $videoid = isset($finalparams['v']) ? $finalparams['v'] : '';
                                $error = 1;
                            }
                        }
                    }
                }
            }
        }
        $thumbplay = $this->settings['thumbplay'] ?? '';
        if (!empty($videoid) || isset($this->finalsrc)) {
            $ifram_src = '';
            if ($this->settings['enableGdpr'] || $constant['enableGdpr']) {
                $ifram_src = 'data-';
            }
            $code1 = '<iframe id="_ytid_' . rand(10000, 99999) . '" ' . $ifram_src . 'src="https://www.'
                . $youtubebaseurl . '.com/embed/' . $videoid . '?autoplay=' . $thumbplay . '&controls='
                . $showcontrol . '&rel=' . $showrelatedvideo;
            $code = $code1 . $this->finalsrc . '" class="__youtube_prefs__" allowfullscreen ' . $centercode . '"></iframe>';
            $popupLink = 'https://www.' . $youtubebaseurl . '.com/embed/' . $videoid . '?autoplay=' . $thumbplay .
                '&controls=' . $showcontrol . '&rel=' . $showrelatedvideo;
            $this->view->assignMultiple([
                'iframe' => $code,
                'popupLink' => $popupLink
            ]);
        }
        if (
            empty($this->settings['apiKey']) &&
            ($this->settings['listType'] == 'playlist' || $this->settings['listType'] == 'channel')
        ) {
            $this->addFlashMessage(
                LocalizationUtility::translate('add_api_key', 'ns_youtube'),
                '',
                ContextualFeedbackSeverity::ERROR
            );
        }
        if ($this->settings['enableGdpr'] && $this->settings['wantBgImage'] && $this->settings['enableGdpr']) {
            $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
            $fileObjects = $fileRepository
                ->findByRelation(
                    'tt_content',
                    'backImage',
                    $contentObj->data['uid']
                );
            $this->view->assign('backImage', $fileObjects[0] ?? '');
        }
        $this->view->assignMultiple(
            [
                'constant' => $constant,
                'options' => $options,
                'error' => $error,
                'jsonResult' => $jsonResult
            ]
        );
        return $this->htmlResponse();
    }

    /**
     * action ajax
     *
     * @return void
     */
    public function ajaxAction(): void
    {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $playlistParamsTemp = '';
            $options = (object) $_POST;
            $options->apiKey = $this->settings['apiKey'];

            // Get Playlist Id
            if ($this->settings['listType'] == 'playlist') {
                $playlistLink = trim(str_replace($this->badentities, $this->goodliterals, $this->settings['playlisturl']));
                $playlistLink = preg_replace('/\s/', '', $playlistLink);
                if (!empty($playlistLink)) {
                    $playlistParamsTemp = GeneralUtility::trimExplode('?', $playlistLink);
                }

                if (count($playlistParamsTemp) > 1) {
                    $playlistParams = $this->keyvalue($playlistParamsTemp[1], true);
                }
                if (isset($playlistParams['list'])) {
                    $finalPlaylistParams = $playlistParams + $this->settings;
                    $options->playlistId = $finalPlaylistParams['list'];
                }
            }
            $options->pageSize = $this->settings['pagesize'] ?? 5;
            if ($this->settings['listType'] == 'channel') {
                $jsonResult = $this->getChannelVideo($options);
            } else {
                $jsonResult = $this->getVideoFromList($options);
            }
            $gallery = $this->getVideo($jsonResult);
            $nextvideo = $gallery->html;
            echo $nextvideo;
            die();
        }
    }

    /**
     * @param array $options
     * @return Object
     */
    public function getOptions(array $options): object
    {
        $opt = new \stdClass();
        if (array_key_exists("list", $options)) {
            $opt->playlistId = $options['list'] ?: '';
        }
        $opt->pageToken = null;
        $opt->pageSize = empty($options['pagesize']) ? 5 : $options['pagesize'];
        $opt->apiKey = $this->settings['apiKey'];
        return $opt;
    }

    /**
     * @param string $qry
     * @param bool $includev
     * @return array
     */
    public function keyvalue(string $qry, bool $includev): array
    {
        $ytvars = GeneralUtility::trimExplode('&', $qry);
        $ytkvp = [];
        foreach ($ytvars as $v) {
            $kvp = GeneralUtility::trimExplode('=', $v);
            if (count($kvp) == 2 && ($includev || strtolower($kvp[0]) != 'v')) {
                $ytkvp[$kvp[0]] = $kvp[1];
            }
        }
        return $ytkvp;
    }

    /**
     * @param Object $options
     * @return Object
     */
    public function getVideoFromList(object $options): object
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
                ContextualFeedbackSeverity::ERROR
            );
            return (object) [];
        }
    }

    /**
     * @param Object $options
     * @return Object
     */
    public function getChannelVideo(object $options): object
    {
        try {
            $channelId = '';
            if (
                $this->settings['channeltype'] == 'username' &&
                isset($this->settings['channelname']) &&
                $this->settings['channelname'] != ''
            ) {
                $api = 'https://www.googleapis.com/youtube/v3/channels?part=snippet&forHandle='
                    . str_replace(' ', '', $this->settings['channelname']) . '&key=' . $this->settings['apiKey'];
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
            if ($channelId != null) {
                $apiEndpoint = 'https://www.googleapis.com/youtube/v3/search?part=snippet,id&type=video&order=date&channelId='
                    . $channelId . '&maxResults=' . $options->pageSize . '&key=' . $this->settings['apiKey'];
            } else {
                $this->addFlashMessage(
                    LocalizationUtility::translate('add_user_name', 'ns_youtube'),
                    '',
                    ContextualFeedbackSeverity::ERROR
                );
                return (object) [];
            }
            if ($options->pageToken != null) {
                $apiEndpoint .= '&pageToken=' . $options->pageToken;
            }
            $apiResult = $this->connectAPI($apiEndpoint);
            return json_decode($apiResult->getBody());
        } catch (\Exception $e) {
            $error = $this->catchException($e);
            $this->addFlashMessage(
                $error,
                '',
                ContextualFeedbackSeverity::ERROR
            );
            return (object) [];
        }
    }

    /**
     * @param Object $jsonResult
     * @return Object
     */
    public function getVideo(object $jsonResult): object
    {
        $totalPages = 0;
        $obj = new \stdClass();
        //@extensionScannerIgnoreLine
        if (isset($jsonResult->pageInfo)) {
            # code...
            $resultsPerPage = $jsonResult->pageInfo->resultsPerPage;
            $totalResults = $jsonResult->pageInfo->totalResults;
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
                $init_id = 0;
                foreach ($jsonResult->items as $item) {
                    $results->key = $item;
                    $thumb->id = $item->snippet->resourceId->videoId ?? $item->id->videoId;
                    $thumb->title = $item->snippet->title ?: '';
                    $thumb->privacyStatus = $item->status->privacyStatus ?? null;
                    $thumb->subscibe = $item->snippet->channelId;
                    if ($cnt == 0) {
                        $init_id = $thumb->id;
                    }
                    $cnt++;
                    $results->key->snippet->datavideoid = $thumb->id;

                }
                $obj->prevPageToken = $prevPageToken;
                $obj->nextPageToken = $nextPageToken;
                $obj->channelTitle = $thumb->subscibe;
                $obj->init_id = $init_id;
                $obj->settings = $this->settings;
                $obj->totalPages = $totalPages;
                $obj->jsonResult = $jsonResult;
                $gallery = json_decode(json_encode($obj));
                if ($gallery != null) {
                    $html = $this->getTemplateHtml(
                        'Youtube',
                        'Gallery',
                        $gallery
                    );
                    $obj->html = $html;
                }
            }
        }
        return $obj;
    }

    /**
     * @param array $finalparams
     * @return string
     */
    public function getPlaylistView(array $finalparams): string
    {
        $yt_options = [
            $this->settings['listType'],
            'index',
            'list',
            'start',
            'v',
            'end'
        ];
        if ($this->settings['listType'] == 'channel') {
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

    /**
     * @param string $channelId
     * @return string
     */
    public function getPlaylistId(string $channelId): string
    {
        $playlistId = '';
        $apiEndpoint = 'https://www.googleapis.com/youtube/v3/channels?part=contentDetails&key=' .
            $this->settings['apiKey'] . '&id=' . $channelId;
        $apiResult = $this->connectAPI($apiEndpoint);
        $jsonResult = json_decode($apiResult->getBody());
        foreach ($jsonResult->items as $item) {
            $playlistId = $item->contentDetails->relatedPlaylists->uploads;
        }
        return $playlistId;
    }

    /**
     * @param mixed $e
     * @return string
     */
    public function catchException(mixed $e): string
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

    /**
     * @param string $url
     * @return ResponseInterface
     */
    public function connectAPI(string $url): ResponseInterface
    {
        $request = GeneralUtility::makeInstance(RequestFactory::class);
        return $request->request($url, 'GET');
    }

    /**
     * @param string $controllerName
     * @param string $templateName
     * @param Object $variables
     * @return string
     */
    public function getTemplateHtml(string $controllerName, string $templateName, object $variables): string
    {
        $tempView = GeneralUtility::makeInstance(StandaloneView::class);
        $extbaseFrameworkConfiguration = $this->configurationManager
            ->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

        $partialRootPaths = $extbaseFrameworkConfiguration['view']['partialRootPaths'];
        $templatePathAndFilename = '';
        foreach (array_reverse($partialRootPaths) as $partialRootPath) {
            $templatePathAndFilename = GeneralUtility::getFileAbsFileName(
                $partialRootPath . $controllerName . '/' . $templateName . '.html'
            );
            if (file_exists($templatePathAndFilename)) {
                break;
            }
        }

        $tempView->setTemplatePathAndFilename($templatePathAndFilename);
        // Set layout and partial root paths
        $tempView->assignMultiple((array) $variables);
        return $tempView->render();
    }
}
