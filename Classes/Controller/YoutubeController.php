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
        // Delcare variables
        $youtubebaseurl = 'youtube';
        $video   = (object)[];
        $options = (object) [];
        $error = '';
        $videoid = '';
        $jsonResult = [];
        $linkparamstemp = [];
        $linkparams     = [];
        $constant = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_nsyoutube.']['settings.'];
        $centercode = '';
        $usenocookie = 0;
        $hidelogo = 0;
        $showcontrol = 0;
        $showrelatedvideo = 0;
        $contentObj = $this->configurationManager->getContentObject();
        
        // Get link
        if ($this->settings['listType'] == 'single') {
            $this->link = trim(str_replace($this->badentities, $this->goodliterals, $this->settings['videourl']));
            $showcontrol = trim(str_replace($this->badentities, $this->goodliterals, $this->settings['showcontrol']));
            $hidelogo = trim(str_replace($this->badentities, $this->goodliterals, $this->settings['hidelogo']));
            $showrelatedvideo = trim(str_replace($this->badentities, $this->goodliterals, $this->settings['showrelatedvideo']));
            $usenocookie = trim(str_replace($this->badentities, $this->goodliterals, $this->settings['nocookie']));
        } else {
            if ($this->settings['listType'] == 'playlist') {
                $this->link = trim(str_replace($this->badentities, $this->goodliterals, $this->settings['playlisturl']));
                $usenocookie = 0;
                $showcontrol = 0;
                $hidelogo = 0;
                $showrelatedvideo = 0;
            }
            if ($this->settings['listType'] == 'channel') {
                $this->link = '';
                $usenocookie = 0;
                $showcontrol = 0;
                $hidelogo = 0;
                $showrelatedvideo = 0;
            }
        }  

        if ($this->settings['listType'] == 'single' || $this->settings['listType'] == 'playlist') {
            $this->link     = preg_replace('/\s/', '', $this->link);
            if(!empty($this->link)){
                $linkparamstemp = GeneralUtility::trimExplode('?', $this->link);
            }
            
            if (count($linkparamstemp) > 1) {
                $linkparams = $this->keyvalue($linkparamstemp[1], true);
            }
            $youtubebaseurl = ($usenocookie == 1) ? 'youtube-nocookie' : 'youtube';
            if($this->settings['listType'] == 'single'){
                $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
                $fileObjects = $fileRepository->findByRelation('tt_content', 'youtubeImage', $contentObj->data['uid']);
                $fileObjects[0] = $fileObjects[0] ?? '';
                $this->view->assign('youtubeImage', $fileObjects[0]);
            }
        }

        if ($this->settings['listType'] == 'single') {
            if ($linkparams['v'] != '' && isset($linkparams['v'])) {
                $videoid = $linkparams['v'];
            } else {
                if (isset($linkparams['list'])) {
                    $finalparams = $linkparams + $this->settings;
                    $options     = $this->getOptions($finalparams);
                    $jsonResult  = $this->getVideoFromList($options);
                    $videoid     = $this->getVideo($jsonResult, $finalparams)->videoid;
                }
            }
        } else {
            if (!empty($this->settings['apiKey'])) {
                if ($this->settings['listType'] == 'playlist') {
                    if ($this->settings['layout'] == 'gallery') {

                        $finalparams = $linkparams + $this->settings;
                        $options     = $this->getOptions($finalparams);
                        $jsonResult  = $this->getVideoFromList($options);
                        if ($jsonResult) {
                            $video   = $this->getVideo($jsonResult, $finalparams);
                            $videoid = isset($video->init_id) ? $video->init_id : '';
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
                        } else {
                            if ($linkparams['v'] != '' && !empty($linkparams['v'])) {
                                $videoid = $linkparams['v'];
                            }
                        }
                    } else {
                        if ($this->settings['layout'] == 'playlist') {
                            $finalparams = $linkparams + $this->settings;
                            if ($finalparams['list'] != '' && isset($finalparams['list'])) {
                                $this->finalsrc = $this->getPlaylistView($finalparams); //103
                            } else {
                                if ($finalparams['v'] != '' && isset($finalparams['v'])) {
                                    $videoid = $finalparams['v'];
                                    $error   = 1;
                                }
                            }
                        } else {
                            $error = 1;
                        }
                    }
                } else {
                    $finalparams = $this->settings;
                    $options     = $this->getOptions($finalparams);
                    $jsonResult  = $this->getChannelVideo($options);
                    if ($this->settings['layout'] == 'gallery') {
                        if ($jsonResult) {
                            $video   = $this->getVideo($jsonResult, []);
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
                            $videoid = isset($video->init_id) ? $video->init_id : '';
                        }
                    } else {
                        $cnt = 0;
                        if (isset($jsonResult->items) && $jsonResult->items != null && is_array($jsonResult->items)) {
                            foreach ($jsonResult->items as $item) {
                                $param       = new \stdClass();
                                $param->id   = isset($item->id->videoId) ? $item->id->videoId : null;
                                $param->list = isset($item->snippet->channelId) ? $item->snippet->channelId : null;
                                if ($cnt == 0 && $options->pageToken == null) {
                                    $linkparams['v']    = $param->id;
                                    $linkparams['list'] = $param->list;
                                }
                                $cnt++;
                            }
                            $finalparams = $linkparams + $this->settings;
                            if ($finalparams['list'] != '' && isset($finalparams['list'])) {
                                $this->finalsrc = $this->getPlaylistView($finalparams);
                            } else {
                                if ($finalparams['v'] != '' && isset($finalparams['v'])) {
                                    $videoid = isset($finalparams['v']) ? $finalparams['v'] : '';
                                    $error   = 1;
                                }
                            }
                        }
                    }
                }
            }
        }
        $thumbplay = $this->settings['thumbplay'] ?? '';
        if (!empty($videoid) || isset($this->finalsrc)) {
            $code1 = '<iframe id="_ytid_' . rand(10000, 99999) . '" src="https://www.' . $youtubebaseurl . '.com/embed/' . $videoid . '?autoplay=' . $thumbplay . '&controls=' . $showcontrol . '&modestbranding=' . $hidelogo . '&rel=' . $showrelatedvideo;
            if($this->settings['enableGdpr'] || $constant['enableGdpr']){
                $code1 = '<iframe id="_ytid_' . rand(10000, 99999) . '" data-src="https://www.' . $youtubebaseurl . '.com/embed/' . $videoid . '?autoplay=' . $thumbplay . '&controls=' . $showcontrol . '&modestbranding=' . $hidelogo . '&rel=' . $showrelatedvideo;
            }
            $code2 = '" class="__youtube_prefs__" allowfullscreen ' . $centercode . '"></iframe>';
            $code  = $code1 . $this->finalsrc . $code2;
            $popupLink = 'https://www.' . $youtubebaseurl . '.com/embed/' . $videoid . '?autoplay=' . $thumbplay . '&controls=' . $showcontrol . '&modestbranding=' . $hidelogo . '&rel=' . $showrelatedvideo;
            $this->view->assign('iframe', $code);
            $this->view->assign('popupLink', $popupLink);

        }
        if (empty($this->settings['apiKey'])) {
            if ($this->settings['listType'] == 'playlist' || $this->settings['listType'] == 'channel') {
                $this->addFlashMessage(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('add_api_key', 'ns_youtube'), '', ContextualFeedbackSeverity::ERROR);
            }
        }
        $contentObj = $this->configurationManager->getContentObject();
        if($this->settings['enableGdpr']){
            if($this->settings['wantBgImage'] && $this->settings['enableGdpr']){
                $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
                $fileObjects = $fileRepository->findByRelation('tt_content', 'backImage', $contentObj->data['uid']);
                $fileObjects[0] = $fileObjects[0] ?? '';
                $this->view->assign('backImage', $fileObjects[0]);
            }
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
            $options           = (object) $_POST;
            $options->apiKey   = $this->settings['apiKey'];

            // Get Playlist Id
            if ($this->settings['listType'] == 'playlist') {
                $playlistLink = trim(str_replace($this->badentities, $this->goodliterals, $this->settings['playlisturl']));
                $playlistLink     = preg_replace('/\s/', '', $playlistLink);
                if(!empty($playlistLink)){
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
            $options->pageSize = isset($this->settings['pagesize']) ? $this->settings['pagesize'] : 5;
            $gallery           = new \stdClass();
            if ($this->settings['listType'] == 'channel') {
                $jsonResult = $this->getChannelVideo($options);
            } else {
                $jsonResult = $this->getVideoFromList($options);
            }
            $gallery   = $this->getVideo($jsonResult, $options);
            $nextvideo = $gallery->html;
            echo $nextvideo;
            die();
        }
    }

    // Get Options
    public function getOptions(array $options): Object
    {
        $opt             = new \stdClass();
        if(array_key_exists("list",$options)){
            $opt->playlistId = $options['list'] ?: '';
        }
        $opt->pageToken  = null;
        $opt->pageSize   = empty($options['pagesize']) ? 5 : $options['pagesize'];
        $opt->apiKey     = $this->settings['apiKey'];
        return $opt;
    }

    // Get keyValue
    public function keyvalue(string $qry,bool $includev): array
    {
        $ytvars = GeneralUtility::trimExplode('&', $qry);
        $ytkvp  = [];
        foreach ($ytvars as $k => $v) {
            $kvp = GeneralUtility::trimExplode('=', $v);
            if (count($kvp) == 2 && ($includev || strtolower($kvp[0]) != 'v')) {
                $ytkvp[$kvp[0]] = $kvp[1];
            }
        }

        return $ytkvp;
    }

    // Get Videos from list parameter
    public function getVideoFromList(Object|array $options): Object|array
    {
        $apiEndpoint = 'https://www.googleapis.com/youtube/v3/playlistItems?part=snippet,status&playlistId=' . $options->playlistId . '&maxResults=' . $options->pageSize . '&key=' . $options->apiKey;
        if ($options->pageToken != null) {
            $apiEndpoint .= '&pageToken=' . $options->pageToken;
        }
        try {
            $apiResult  = $this->connectAPI($apiEndpoint, 'GET', null, true, null);
            $jsonResult = json_decode($apiResult->getBody());
            return $jsonResult;
        } catch (\Exception $e) {
            $error = $this->catchException($e);
            return $this->addFlashMessage($error, '', ContextualFeedbackSeverity::ERROR);
        }
    }

    // Get Channel Video
    public function getChannelVideo(Object $options): Object
    {
        try {
            $channelId = '';
            if ($this->settings['channeltype'] == 'username' && isset($this->settings['channelname']) && $this->settings['channelname'] != '') {
                $api = 'https://www.googleapis.com/youtube/v3/channels?part=contentDetails&forUsername=' . str_replace(' ', '', $this->settings['channelname']) . '&key=' . $this->settings['apiKey'];
                $result     = $this->connectAPI($api, 'GET', null, true, null);
                $jsonresult = json_decode($result->getBody());
                foreach ($jsonresult->items as $item) {
                    $channelId = $item->id;
                }
            } elseif ($this->settings['channelid'] != '' && isset($this->settings['channelid'])) {
                $channelId = $this->settings['channelid'];
            }
            $apiEndpoint = 'https://www.googleapis.com/youtube/v3/search?part=snippet,id&type=video&order=date&channelId=' . $channelId . '&maxResults=' . $options->pageSize . '&key=' . $this->settings['apiKey'];
            if ($options->pageToken != null) {
                $apiEndpoint .= '&pageToken=' . $options->pageToken;
            }
            $apiResult   = $this->connectAPI($apiEndpoint, 'GET', null, true, null);
            $jsonResult  = json_decode($apiResult->getBody());
            return $jsonResult;
        } catch (\Exception $e) {
            $error = $this->catchException($e);
            return $this->addFlashMessage($error, '', ContextualFeedbackSeverity::ERROR);
        }
    }

    // Get Video Id
    public function getVideo(Object $jsonResult,array|Object $options): Object
    {
        $totalPages = 0;
        $obj            = new \stdClass();
        $resultsPerPage = $jsonResult->pageInfo->resultsPerPage; // $jsonResult->pageInfo->resultsPerPage;
        $totalResults   = $jsonResult->pageInfo->totalResults;
        if (!empty($jsonResult->pageInfo->totalResults)) {
            $totalPages     = ceil($totalResults / $resultsPerPage);
        }
        $nextPageToken = '';
        $prevPageToken = '';
        if (isset($jsonResult->nextPageToken)) {
            $nextPageToken = $jsonResult->nextPageToken;
        }

        if (isset($jsonResult->prevPageToken)) {
            $prevPageToken = $jsonResult->prevPageToken;
        }
        $cnt = 0;
        if (isset($jsonResult->items) && $jsonResult->items != null && is_array($jsonResult->items)) {
            $results = new \stdClass();
            foreach ($jsonResult->items as $key => $item) {
                $results->key = $item;
                $thumb        = new \stdClass();

                $thumb->id            = isset($item->snippet->resourceId->videoId) ? $item->snippet->resourceId->videoId : null;
                $thumb->id            = $thumb->id ? $thumb->id : $item->id->videoId;
                $thumb->title         = $item->snippet->title ?: '';
                $thumb->privacyStatus = isset($item->status->privacyStatus) ? $item->status->privacyStatus : null;
                $thumb->subscibe      = $item->snippet->channelId;
                if ($cnt == 0) {
                    $init_id = $thumb->id;
                }
                $cnt++;
                if ($results) {
                    $results->key->snippet->datavideoid = $thumb->id;
                }

            }
            $obj->prevPageToken = $prevPageToken;
            $obj->nextPageToken = $nextPageToken;
            $obj->channelTitle  = $thumb->subscibe;
            $obj->init_id       = $init_id;
            $obj->settings      = $this->settings;
            $obj->totalPages    = $totalPages;
            $obj->jsonResult    = $jsonResult;
            $gallery            = json_decode(json_encode($obj));
            if ($gallery != null) {
                $html      = $this->getTemplateHtml('Youtube', 'Gallery', $gallery);
                $obj->html = $html;
            }

        }
        return $obj;
    }

    // Get Plylist View
    /**
     * @param array<mixed> $finalparams
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
            $this->finalsrc .= 'listType=playlist&';
            $this->finalsrc .= 'list=' . $playlistId;
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
    public function getPlaylistId(string $channelId): string
    {
        $playlistId = '';
        $apiEndpoint = 'https://www.googleapis.com/youtube/v3/channels?part=contentDetails&key=' . $this->settings['apiKey'] . '&id=' . $channelId;
        $apiResult   = $this->connectAPI($apiEndpoint, 'GET', null, true, null);
        $jsonResult  = json_decode($apiResult->getBody());
        $items = [];
        $items       = $jsonResult->items;
        foreach ($jsonResult->items as $item) {
            $playlistId = $item->contentDetails->relatedPlaylists->uploads;
        }
        return $playlistId;
    }

    // Exception
    public function catchException($e): string
    {
        if(property_exists($e,'response')){
            $response = $e->getResponse();
        }
        $response = isset($response) ? $response : null;
        if (empty($response) && $response == null) {
            $error = $e->getMessage();
        } else {
            $jsonBody = json_decode($response->getBody()->getContents(), 1);
            $error    = $jsonBody['error']['message'];
        }
        return $error;
    }

    // Connect API
    public function connectAPI(string $url,string $method = 'GET',string $params = null,bool $json = true,string $headers = null): ResponseInterface
    {
        $request = GeneralUtility::makeInstance(RequestFactory::class);
        $response = $request->request($url, 'GET');
        return $response;
    }

    // Get HTML
    public function getTemplateHtml(string $controllerName,string $templateName, Object $variables): string
    {
        $tempView = GeneralUtility::makeInstance(StandaloneView::class);
        $extbaseFrameworkConfiguration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

        $partialRootPaths = $extbaseFrameworkConfiguration['view']['partialRootPaths'];
        $templatePathAndFilename = '';
        foreach (array_reverse($partialRootPaths) as $partialRootPath) {
            $templatePathAndFilename = GeneralUtility::getFileAbsFileName($partialRootPath . $controllerName . '/' . $templateName . '.html');

            if (file_exists($templatePathAndFilename)) {
                break;
            }
        }

        $tempView->setTemplatePathAndFilename($templatePathAndFilename);

        // Set layout and partial root paths
        $tempView->assignMultiple((array)$variables);
        $tempHtml = $tempView->render();
        return $tempHtml;
    }
}
