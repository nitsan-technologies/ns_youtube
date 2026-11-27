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

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * YoutubeController
 */
class YoutubeController extends ActionController
{
    protected const PLUGIN_CTYPE = 'nsyoutube_youtube';

    /**
     * @var array<int, string> $badentities
     */
    public array $badentities = ['&#215;', '×', '&#8211;', '–', '&amp;', '&#038;', '&#38;'];

    /**
     * @var array<int, string> $goodliterals
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
     * ViewFactory
     *
     * @var ViewFactoryInterface|null
     */
    protected ?ViewFactoryInterface $viewFactory = null;

    /**
     * @var array<mixed> $typoScriptConstants
     */
    protected array $typoScriptConstants = [];

    public function listAction(): ResponseInterface
    {
        $youtubebaseurl = 'youtube';
        $options = (object) [];
        $error = $videoid = $centercode = '';
        $jsonResult = $linkparamstemp = $linkparams = [];
        $fullTypoScript = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        $constant = $fullTypoScript['plugin.']['tx_nsyoutube.']['settings.'] ?? [];
        $usenocookie = $showcontrol = $showrelatedvideo = 0;
        $contentObj = $this->request->getAttribute('currentContentObject');
        $contentUid = (int)($contentObj->data['uid'] ?? 0);

        // Get link
        if ($this->settings['listType'] == 'single') {
            $this->link = trim(str_replace($this->badentities, $this->goodliterals, (string)($this->settings['videourl'] ?? '')));
            $showcontrol = trim(str_replace($this->badentities, $this->goodliterals, (string)($this->settings['showcontrol'] ?? '0')));
            $showrelatedvideo = trim(str_replace($this->badentities, $this->goodliterals, (string)($this->settings['showrelatedvideo'] ?? '0')));
            $usenocookie = trim(str_replace($this->badentities, $this->goodliterals, (string)($this->settings['nocookie'] ?? '0')));
        } elseif ($this->settings['listType'] == 'playlist') {
            $this->link = trim(str_replace($this->badentities, $this->goodliterals, (string)($this->settings['playlisturl'] ?? '')));
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
                    $contentUid
                );
                $fileObjects[0] = $fileObjects[0] ?? '';
                $this->view->assign('youtubeImage', $fileObjects[0]);
            }
        }

        if ($this->settings['listType'] == 'single') {
            if (isset($linkparams['v']) && $linkparams['v'] != '') {
                $videoid = $linkparams['v'];
            } elseif (isset($linkparams['list'])) {
                $finalparams = $linkparams + $this->settings;
                $options = $this->getOptions($finalparams);
                $jsonResult = $this->getVideoFromList($options);
                $videoid = $this->getVideo($jsonResult)->videoid ?? '';
            }
        } else {

            if (!empty($this->settings['apiKey'])) {
                if ($this->settings['listType'] == 'playlist') {
                    if (isset($this->settings['layout']) && $this->settings['layout'] == 'gallery') {

                        $finalparams = $linkparams + $this->settings;
                        $options = $this->getOptions($finalparams);
                        $jsonResult = $this->getVideoFromList($options);
                        if ($jsonResult) {
                            $video = $this->getVideo($jsonResult);
                            $videoid = $video->init_id ?? '';
                            $this->view->assignMultiple(
                                [
                                    'totalPages' => $video->totalPages ?? 0,
                                    'nextPageToken' => $video->nextPageToken ?? 0,
                                    'prevPageToken' => $video->prevPageToken ?? 0,
                                    'options' => $options,
                                    'jsonResult' => $jsonResult,
                                    'subscibe' => $video->channelTitle ?? ''
                                ]
                            );
                        } elseif ($linkparams['v'] != '' && !empty($linkparams['v'])) {
                            $videoid = $linkparams['v'];
                        }
                    } else {
                        if (isset($this->settings['layout']) && $this->settings['layout'] == 'playlist') {
                            $finalparams = $linkparams + $this->settings;
                            if (isset($finalparams['list']) && $finalparams['list'] != '') {
                                $this->finalsrc = $this->getPlaylistView($finalparams);
                            } elseif (isset($finalparams['v']) && $finalparams['v'] != '' && isset($finalparams['v'])) {
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
        $videoid = $this->sanitizeYoutubeVideoId((string)$videoid);
        if ($videoid !== '' || $this->finalsrc !== '') {
            $enableGdprFromSettings = $this->settings['enableGdpr'] ?? false;
            $enableGdprFromConstant = $constant['enableGdpr'] ?? false;
            $useDataSrc = (bool)$enableGdprFromSettings || (bool)$enableGdprFromConstant;
            $code = $this->buildEmbedIframe(
                $youtubebaseurl,
                $videoid,
                $this->normalizeBinaryFlag($thumbplay),
                $this->normalizeBinaryFlag($showcontrol),
                $this->normalizeBinaryFlag($showrelatedvideo),
                $this->finalsrc,
                $useDataSrc,
                $centercode
            );
            $popupLink = $this->buildEmbedUrl(
                $youtubebaseurl,
                $videoid,
                $this->normalizeBinaryFlag($thumbplay),
                $this->normalizeBinaryFlag($showcontrol),
                $this->normalizeBinaryFlag($showrelatedvideo)
            );
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
        if ($this->settings['enableGdpr'] && isset($this->settings['wantBgImage']) && $this->settings['wantBgImage']) {
            $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
            $fileObjects = $fileRepository
                ->findByRelation(
                    'tt_content',
                    'backImage',
                    $contentUid
                );
            $this->view->assign('backImage', $fileObjects[0] ?? '');
        }
        $this->view->assignMultiple(
            [
                'constant' => $constant,
                'options' => $options,
                'error' => $error,
                'jsonResult' => $jsonResult,
                'contentUid' => $contentUid,
            ]
        );
        return $this->htmlResponse();
    }

    /**
     * Gallery pagination endpoint. Settings (including API key) are loaded from the
     * content element / TypoScript only — never from request arguments.
     *
     * Returns a bare HTML fragment (same as before) via ImmediateResponseException so
     * the frontend page chrome is not wrapped around the gallery markup.
     */
    public function ajaxAction(): ResponseInterface
    {
        if (!$this->isXmlHttpRequest()) {
            $this->sendAjaxFragment('');
        }

        $contentUid = 0;
        if ($this->request->hasArgument('contentUid')) {
            $contentUid = (int)$this->request->getArgument('contentUid');
        }
        if (!$this->applySettingsFromContentElement($contentUid)) {
            $this->sendAjaxFragment('');
        }

        $parsedBody = $this->request->getParsedBody();
        if (!is_array($parsedBody)) {
            $parsedBody = [];
        }

        $options = new \stdClass();
        $options->apiKey = (string)($this->settings['apiKey'] ?? '');
        $options->pageToken = $this->sanitizeYoutubeApiToken((string)($parsedBody['pageToken'] ?? ''));
        if ($options->pageToken === '') {
            $options->pageToken = null;
        }
        $options->pageSize = max(1, min(50, (int)($this->settings['pagesize'] ?? 5)));
        $options->playlistId = '';

        if (($this->settings['listType'] ?? '') === 'playlist') {
            $playlistLink = trim(str_replace($this->badentities, $this->goodliterals, (string)($this->settings['playlisturl'] ?? '')));
            $playlistLink = preg_replace('/\s/', '', $playlistLink) ?? '';
            $playlistParamsTemp = [];
            if ($playlistLink !== '') {
                $playlistParamsTemp = GeneralUtility::trimExplode('?', $playlistLink);
            }
            if (count($playlistParamsTemp) > 1) {
                $playlistParams = $this->keyvalue($playlistParamsTemp[1], true);
                if (isset($playlistParams['list'])) {
                    $options->playlistId = $this->sanitizeYoutubeListId($playlistParams['list']);
                }
            }
        }

        if (($this->settings['listType'] ?? '') === 'channel') {
            $jsonResult = $this->getChannelVideo($options);
        } else {
            $jsonResult = $this->getVideoFromList($options);
        }
        $gallery = $this->getVideo($jsonResult);
        $this->sendAjaxFragment($gallery->html ?? '');
    }

    /**
     * @param array $options
     * @return Object
     */
    public function getOptions(array $options): object
    {
        $opt = new \stdClass();
        if (array_key_exists('list', $options)) {
            $opt->playlistId = $this->sanitizeYoutubeListId((string)($options['list'] ?: ''));
        }
        $opt->pageToken = null;
        $opt->pageSize = empty($options['pagesize']) ? 5 : max(1, min(50, (int)$options['pagesize']));
        $opt->apiKey = (string)($this->settings['apiKey'] ?? '');
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
        $playlistId = $this->sanitizeYoutubeListId((string)($options->playlistId ?? ''));
        $pageSize = max(1, min(50, (int)($options->pageSize ?? 0)));
        $params = [
            'part' => 'snippet,status',
            'playlistId' => $playlistId,
            'maxResults' => $pageSize,
            'key' => (string)($options->apiKey ?? ''),
        ];
        if (!empty($options->pageToken)) {
            $params['pageToken'] = $this->sanitizeYoutubeApiToken((string)$options->pageToken);
        }
        $apiEndpoint = 'https://www.googleapis.com/youtube/v3/playlistItems?' . http_build_query($params);
        try {
            $apiResult = $this->connectAPI($apiEndpoint);
            return json_decode($apiResult->getBody());
        } catch (\Exception $e) {
            $this->addFlashMessage(
                $this->getSafeApiErrorMessage($e),
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
            if (isset($this->settings['channeltype']) && $this->settings['channeltype'] == 'username' && isset($this->settings['channelname']) && $this->settings['channelname'] != '') {
                $handle = $this->sanitizeYoutubeHandle(str_replace(' ', '', (string)$this->settings['channelname']));
                $api = 'https://www.googleapis.com/youtube/v3/channels?' . http_build_query([
                    'part' => 'snippet',
                    'forHandle' => $handle,
                    'key' => (string)($this->settings['apiKey'] ?? ''),
                ]);
                $result = $this->connectAPI($api);
                $jsonresult = json_decode($result->getBody());
                if (isset($jsonresult->items)) {
                    foreach ($jsonresult->items as $item) {
                        $channelId = $this->sanitizeYoutubeChannelId((string)$item->id);
                    }
                }
            } elseif (isset($this->settings['channelid']) && $this->settings['channelid'] != '' && isset($this->settings['channelid'])) {
                $channelId = $this->sanitizeYoutubeChannelId((string)$this->settings['channelid']);
            }
            if ($channelId != null && $channelId !== '') {
                $params = [
                    'part' => 'snippet,id',
                    'type' => 'video',
                    'order' => 'date',
                    'channelId' => $channelId,
                    'maxResults' => max(1, min(50, (int)($options->pageSize ?? 5))),
                    'key' => (string)($this->settings['apiKey'] ?? ''),
                ];
                if (!empty($options->pageToken)) {
                    $params['pageToken'] = $this->sanitizeYoutubeApiToken((string)$options->pageToken);
                }
                $apiEndpoint = 'https://www.googleapis.com/youtube/v3/search?' . http_build_query($params);
            } else {
                $this->addFlashMessage(
                    LocalizationUtility::translate('add_user_name', 'ns_youtube'),
                    '',
                    ContextualFeedbackSeverity::ERROR
                );
                return (object) [];
            }
            $apiResult = $this->connectAPI($apiEndpoint);
            return json_decode($apiResult->getBody());
        } catch (\Exception $e) {
            $this->addFlashMessage(
                $this->getSafeApiErrorMessage($e),
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
                    $thumb->id = $this->sanitizeYoutubeVideoId(
                        (string)($item->snippet->resourceId->videoId ?? $item->id->videoId ?? '')
                    );
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
            $playlistId = $this->sanitizeYoutubeListId($this->getPlaylistId((string)$finalparams['list']));
            $this->finalsrc .= 'listType=playlist&list=' . rawurlencode($playlistId);
        } else {
            foreach ($finalparams as $key => $value) {
                if (in_array($key, $yt_options, true)) {
                    if ($key === 'v') {
                        $value = $this->sanitizeYoutubeVideoId((string)$value);
                    } elseif ($key === 'list') {
                        $value = $this->sanitizeYoutubeListId((string)$value);
                    }
                    $this->finalsrc .= htmlspecialchars((string)$key) . '=' . htmlspecialchars((string)$value) . '&';
                    if ($value == 1 && !isset($finalparams['list'])) {
                        $this->finalsrc .= 'playlist=' . htmlspecialchars($this->sanitizeYoutubeListId((string)($finalparams['list'] ?? ''))) . '&';
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
        $channelId = $this->sanitizeYoutubeChannelId($channelId);
        $apiEndpoint = 'https://www.googleapis.com/youtube/v3/channels?' . http_build_query([
            'part' => 'contentDetails',
            'key' => (string)($this->settings['apiKey'] ?? ''),
            'id' => $channelId,
        ]);

        $apiResult = $this->connectAPI($apiEndpoint);
        $jsonResult = json_decode($apiResult->getBody());
        foreach ($jsonResult->items as $item) {
            $playlistId = $item->contentDetails->relatedPlaylists->uploads;
        }
        return $this->sanitizeYoutubeListId((string)$playlistId);
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
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        if ($typo3Version->getMajorVersion() >= 14) {
            $viewFactory = GeneralUtility::makeInstance(ViewFactoryInterface::class);
            $viewFactoryData = new ViewFactoryData(
                templateRootPaths: $extbaseFrameworkConfiguration['view']['templateRootPaths'] ?? [],
                partialRootPaths: $extbaseFrameworkConfiguration['view']['partialRootPaths'] ?? [],
                layoutRootPaths: $extbaseFrameworkConfiguration['view']['layoutRootPaths'] ?? [],
                request: $this->request,
                templatePathAndFilename: $templatePathAndFilename
            );
            $tempView = $viewFactory->create($viewFactoryData);
            $tempView->assignMultiple((array) $variables);
        } else {
            // @extensionScannerIgnoreLine
            $tempView = GeneralUtility::makeInstance(StandaloneView::class);
            $tempView->setTemplatePathAndFilename($templatePathAndFilename);
            $tempView->assignMultiple((array) $variables);
        }
        return $tempView->render();
    }

    /**
     * @return string
     */
    public static function getApiUrl(): string
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('ns_youtube');

        $apiUrl = $extensionConfiguration['urlApi'] ?? '';

        return $apiUrl;
    }

    /**
     * Load FlexForm settings for the given content element and merge onto TypoScript
     * settings. The API key always stays the TypoScript value and is never taken from
     * the request or FlexForm.
     */
    protected function applySettingsFromContentElement(int $contentUid): bool
    {
        if ($contentUid <= 0) {
            return false;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $row = $queryBuilder
            ->select('uid', 'CType', 'pi_flexform')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($contentUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter(self::PLUGIN_CTYPE))
            )
            ->executeQuery()
            ->fetchAssociative();

        if ($row === false) {
            return false;
        }

        $apiKey = (string)($this->settings['apiKey'] ?? '');
        $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
        $flexform = $flexFormService->convertFlexFormContentToArray((string)($row['pi_flexform'] ?? ''));
        $flexSettings = $flexform['settings'] ?? [];
        if (!is_array($flexSettings)) {
            $flexSettings = [];
        }
        $this->settings = array_replace_recursive($this->settings, $flexSettings);
        $this->settings['apiKey'] = $apiKey;
        unset($this->settings['settings']);

        return $apiKey !== '' && in_array(($this->settings['listType'] ?? ''), ['playlist', 'channel'], true);
    }

    protected function isXmlHttpRequest(): bool
    {
        return strtolower($this->request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';
    }

    /**
     * @throws ImmediateResponseException
     */
    protected function sendAjaxFragment(string $html): never
    {
        throw new ImmediateResponseException(new HtmlResponse($html), 1763995200);
    }

    /**
     * Avoid leaking API keys / full request URIs from Guzzle messages to the frontend.
     */
    protected function getSafeApiErrorMessage(\Throwable $e): string
    {
        $jsonMessage = $this->catchException($e);
        if (is_string($jsonMessage) && $jsonMessage !== '' && !str_contains($jsonMessage, 'googleapis.com')) {
            return $jsonMessage;
        }
        return LocalizationUtility::translate('gallery_error', 'ns_youtube') ?? 'YouTube API request failed.';
    }

    protected function sanitizeYoutubeVideoId(string $videoId): string
    {
        return preg_match('/^[A-Za-z0-9_-]{1,32}$/', $videoId) === 1 ? $videoId : '';
    }

    protected function sanitizeYoutubeListId(string $listId): string
    {
        return preg_match('/^[A-Za-z0-9_-]{1,128}$/', $listId) === 1 ? $listId : '';
    }

    protected function sanitizeYoutubeChannelId(string $channelId): string
    {
        return preg_match('/^[A-Za-z0-9_-]{1,128}$/', $channelId) === 1 ? $channelId : '';
    }

    protected function sanitizeYoutubeHandle(string $handle): string
    {
        $handle = ltrim($handle, '@');
        return preg_match('/^[A-Za-z0-9._-]{1,100}$/', $handle) === 1 ? $handle : '';
    }

    protected function sanitizeYoutubeApiToken(string $token): string
    {
        // YouTube page tokens are opaque; allow common base64url characters including '='.
        return preg_match('/^[A-Za-z0-9_\-=.]{0,1024}$/', $token) === 1 ? $token : '';
    }

    protected function normalizeBinaryFlag(mixed $value): int
    {
        return (int)$value === 1 ? 1 : 0;
    }

    protected function buildEmbedUrl(
        string $baseHost,
        string $videoId,
        int $autoplay,
        int $controls,
        int $rel
    ): string {
        $baseHost = $baseHost === 'youtube-nocookie' ? 'youtube-nocookie' : 'youtube';
        $query = http_build_query([
            'autoplay' => $autoplay,
            'controls' => $controls,
            'rel' => $rel,
        ]);
        if ($videoId === '') {
            return 'https://www.' . $baseHost . '.com/embed/?' . $query;
        }
        return 'https://www.' . $baseHost . '.com/embed/' . rawurlencode($videoId) . '?' . $query;
    }

    protected function buildEmbedIframe(
        string $baseHost,
        string $videoId,
        int $autoplay,
        int $controls,
        int $rel,
        string $extraQuery,
        bool $useDataSrc,
        string $extraAttributes
    ): string {
        $src = $this->buildEmbedUrl($baseHost, $videoId, $autoplay, $controls, $rel);
        if ($extraQuery !== '') {
            $src .= (str_contains($src, '?') ? '&' : '?') . ltrim($extraQuery, '&');
        }
        $srcAttr = $useDataSrc ? 'data-src' : 'src';
        $iframeId = '_ytid_' . random_int(10000, 99999);
        return '<iframe id="' . htmlspecialchars($iframeId) . '" '
            . $srcAttr . '="' . htmlspecialchars($src) . '" '
            . 'class="__youtube_prefs__" allowfullscreen '
            . htmlspecialchars($extraAttributes) . '></iframe>';
    }
}
