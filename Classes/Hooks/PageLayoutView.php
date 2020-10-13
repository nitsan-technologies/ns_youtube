<?php
namespace Nitsan\NsYoutube\Hooks;

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class PageLayoutView implements \TYPO3\CMS\Backend\View\PageLayoutViewDrawItemHookInterface
{
    public function preProcess(\TYPO3\CMS\Backend\View\PageLayoutView &$parentObject, &$drawItem, &$headerContent, &$itemContent, array &$row)
    {
        if ($row['CType'] == 'list' && $row['list_type'] == 'nsyoutube_youtube') {
            $drawItem = false;

            $headerContent =
                "<table class='table table-striped table-hover typo3-extension-list no-footer'><thead><tr><th colspan='2'>" . LocalizationUtility::translate('pi1_title', 'ns_youtube') . '</th></tr></thead>';

            $ffXml = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($row['pi_flexform']);
            $itemContent= '<tbody>';

            if ($ffXml['data']['sDEF']['lDEF']['settings.listType']['vDEF']=='single') {
                $itemContent .= "<tr>
                                    <th style='text-align:left'>" . LocalizationUtility::translate('backend.video_type', 'ns_youtube') . "</th>
                                    <td style='padding-left: 10px;'>" . LocalizationUtility::translate('backend.embed_single_video', 'ns_youtube') . '</td>
                                </tr>';

                $itemContent .= "<tr>
                                    <th style='text-align:left'>" . LocalizationUtility::translate('backend.URL', 'ns_youtube') . "</th>
                                    <td style='padding-left: 10px;'>" . $ffXml['data']['sDEF']['lDEF']['settings.videourl']['vDEF'] . '</td>
                                </tr>';
            } elseif ($ffXml['data']['sDEF']['lDEF']['settings.listType']['vDEF']=='playlist') {
                $itemContent .= "<tr>
                                    <th style='text-align:left'>" . LocalizationUtility::translate('backend.video_type', 'ns_youtube') . "</th>
                                    <td style='padding-left: 10px;'>" . LocalizationUtility::translate('backend.embed_playlist', 'ns_youtube') . '</td>
                                </tr>';

                $itemContent .= "<tr>
                                    <th style='text-align:left'>" . LocalizationUtility::translate('backend.URL', 'ns_youtube') . "</th>
                                    <td style='padding-left: 10px;'>" . $ffXml['data']['sDEF']['lDEF']['settings.playlisturl']['vDEF'] . '</td>
                                </tr>';

                $itemContent .= "<tr>
                                    <th style='text-align:left'>" . LocalizationUtility::translate('backend.layout', 'ns_youtube') . "</th>
                                    <td style='padding-left: 10px;'>" . $ffXml['data']['sDEF']['lDEF']['settings.layout']['vDEF'] . '</td>
                                </tr>';
                if ($ffXml['data']['sDEF']['lDEF']['settings.layout']['vDEF']=='gallery') {
                    $itemContent .= "<tr>
                                <th style='text-align:left'>" . LocalizationUtility::translate('backend.titlecrop', 'ns_youtube') . "</th>
                                <td style='padding-left: 10px;'>" . $ffXml['data']['sDEF']['lDEF']['settings.titlecrop']['vDEF'] . '</td>
                            </tr>';
                }
            } else {
                $itemContent .= "<tr>
                                    <th style='text-align:left'>" . LocalizationUtility::translate('backend.video_type', 'ns_youtube') . "</th>
                                    <td style='padding-left: 10px;'>" . LocalizationUtility::translate('backend.embed_channel', 'ns_youtube') . '</td>
                                </tr>';

                $itemContent .= "<tr>
                                    <th style='text-align:left'>" . LocalizationUtility::translate('backend.channel_type', 'ns_youtube') . "</th>
                                    <td style='padding-left: 10px;'>" . $ffXml['data']['sDEF']['lDEF']['settings.channeltype']['vDEF'] . '</td>
                                </tr>';
                if ($ffXml['data']['sDEF']['lDEF']['settings.channeltype']['vDEF']=='username') {
                    $itemContent .= "<tr>
                                        <th style='text-align:left'>" . LocalizationUtility::translate('backend.user_name', 'ns_youtube') . "</th>
                                        <td style='padding-left: 10px;'>" . $ffXml['data']['sDEF']['lDEF']['settings.channelname']['vDEF'] . '</td>
                                    </tr>';
                } else {
                    $itemContent .= "<tr>
                                        <th style='text-align:left'>" . LocalizationUtility::translate('backend.channel_id', 'ns_youtube') . "</th>
                                        <td style='padding-left: 10px;'>" . $ffXml['data']['sDEF']['lDEF']['settings.channelid']['vDEF'] . '</td>
                                    </tr>';
                }
                $itemContent .= "<tr>
                                    <th style='text-align:left'>" . LocalizationUtility::translate('backend.layout', 'ns_youtube') . "</th>
                                    <td style='padding-left: 10px;'>" . $ffXml['data']['sDEF']['lDEF']['settings.layout']['vDEF'] . '</td>
                                </tr>';
                if ($ffXml['data']['sDEF']['lDEF']['settings.layout']['vDEF']=='gallery') {
                    $itemContent .= "<tr>
                                <th style='text-align:left'>" . LocalizationUtility::translate('backend.titlecrop', 'ns_youtube') . "</th>
                                <td style='padding-left: 10px;'>" . $ffXml['data']['sDEF']['lDEF']['settings.titlecrop']['vDEF'] . '</td>
                            </tr>';
                }
            }
            if ($ffXml['data']['sDEF']['lDEF']['settings.listType']['vDEF']!='single') {
                if ($ffXml['data']['sDEF']['lDEF']['settings.layout']['vDEF']=='gallery') {
                    $yes = "<td style='padding-left: 10px;'>" . LocalizationUtility::translate('backend.yes', 'ns_youtube') . '</td>';
                    $no = "<td style='padding-left: 10px;'>" . LocalizationUtility::translate('backend.yes', 'ns_youtube') . '</td>';

                    // pagesize
                    $itemContent .= "<tr>
                                            <th style='text-align:left'>" . LocalizationUtility::translate('backend.pagesize', 'ns_youtube') . "</th>
                                            <td style='padding-left: 10px;'>" . $ffXml['data']['sDEF']['lDEF']['settings.pagesize']['vDEF'] . '</td>
                                        </tr>';

                    // columns
                    $itemContent .= "<tr>
                                            <th style='text-align:left'>" . LocalizationUtility::translate('backend.columns', 'ns_youtube') . "</th>
                                            <td style='padding-left: 10px;'>" . $ffXml['data']['sDEF']['lDEF']['settings.columns']['vDEF'] . '</td>
                                        </tr>';

                    // showtitle
                    if ($ffXml['data']['sDEF']['lDEF']['settings.showtitle']['vDEF']==1) {
                        $itemContent .= "<tr>
                                                <th style='text-align:left'>" . LocalizationUtility::translate('backend.showtitle', 'ns_youtube') . '</th>
                                                ' . $yes . '
                                            </tr>';
                    } else {
                        $itemContent .= "<tr>
                                                <th style='text-align:left'>" . LocalizationUtility::translate('backend.showtitle', 'ns_youtube') . '</th>
                                                ' . $no . '
                                            </tr>';
                    }

                    // thumbplay
                    if ($ffXml['data']['sDEF']['lDEF']['settings.thumbplay']['vDEF']==1) {
                        $itemContent .= "<tr>
                                                <th style='text-align:left'>" . LocalizationUtility::translate('backend.thumbplay', 'ns_youtube') . '</th>
                                                ' . $yes . '
                                            </tr>';
                    } else {
                        $itemContent .= "<tr>
                                                <th style='text-align:left'>" . LocalizationUtility::translate('backend.thumbplay', 'ns_youtube') . '</th>
                                                ' . $no . '
                                            </tr>';
                    }

                    // showpaging
                    if ($ffXml['data']['sDEF']['lDEF']['settings.showpaging']['vDEF']==1) {
                        $itemContent .= "<tr>
                                                <th style='text-align:left'>" . LocalizationUtility::translate('backend.showpaging', 'ns_youtube') . '</th>
                                                ' . $yes . '
                                            </tr>';

                        // insertAbove
                        if ($ffXml['data']['sDEF']['lDEF']['settings.insertAbove']['vDEF']==1) {
                            $itemContent .= "<tr>
                                                    <th style='text-align:left'>" . LocalizationUtility::translate('backend.insertAbove', 'ns_youtube') . '</th>
                                                    ' . $yes . '
                                                </tr>';
                        }

                        // insertBelow
                        if ($ffXml['data']['sDEF']['lDEF']['settings.insertBelow']['vDEF']==1) {
                            $itemContent .= "<tr>
                                                    <th style='text-align:left'>" . LocalizationUtility::translate('backend.insertBelow', 'ns_youtube') . '</th>
                                                    ' . $yes . '
                                                </tr>';
                        }
                    } else {
                        $itemContent .= "<tr>
                                                <th style='text-align:left'>" . LocalizationUtility::translate('backend.showpaging', 'ns_youtube') . '</th>
                                                ' . $no . '
                                            </tr>';
                    }

                    // Subscribe Button
                    if ($ffXml['data']['sDEF']['lDEF']['settings.channelsubtext']['vDEF']) {
                        $itemContent .= "<tr>
                                    <th style='text-align:left'>" . LocalizationUtility::translate('backend.subscribation_button_text', 'ns_youtube') . "</th>
                                    <td style='padding-left: 10px;'>" . $ffXml['data']['sDEF']['lDEF']['settings.channelsubtext']['vDEF'] . '</td>
                                </tr>';
                    }
                }
            }

            $itemContent .= '</tbody></table>';
        }
    }
}
