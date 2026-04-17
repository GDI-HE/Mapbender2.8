<?php
require_once(dirname(__FILE__) . "/../../extensions/fpdf/mb_fpdi.php");
require_once(dirname(__FILE__) . "/../../php/log_error_exec.php");
require_once(dirname(__FILE__) . "/../../classes/class_connector.php");

class mbTemplatePdf extends mbPdf
{
    /* it seems several decorators are going to need this information */
    public $mapInfo = array();
    public $unlinkFiles = false;
    public $logRequests = false;
    public $logType = "file";
    public $featureInfo;
    private $insertPages = array();
    private $appendFiles  = array();
    public $renderingFeatureInfo = false;

    public function __construct($jsonConf)
    {
        $this->confPdf = $jsonConf;
        if (!$this->confPdf->orientation || !$this->confPdf->units || !$this->confPdf->format) {
            die("no valid config");
        }
        $this->objPdf = new mb_fpdi($this->confPdf->orientation, $this->confPdf->units, $this->confPdf->format);
        $this->outputFileName = $this->generateOutputFileName("map", "pdf");
    }

    public function setMapInfo($x_ul, $y_ul, $width, $height, $aBboxString)
    {
        $this->mapinfo["x_ul"] = $x_ul;
        $this->mapinfo["y_ul"] = $y_ul;
        $this->mapinfo["width"] = $width;
        $this->mapinfo["height"] = $height;
        $this->mapinfo["extent"] = $aBboxString;
        $e = new mb_notice("mbTemplatePdf: setting mapInfo ...");
    }

    public function getMapInfo()
    {
        $e = new mb_notice("mbTemplatePdf: getting mapInfo ..");
        return $this->mapinfo;
    }

    public function setMapExtent($aBboxString)
    {
        $this->mapinfo["extent"] = $aBboxString;
        $e = new mb_notice("mbTemplatePdf: setting mapExtent to " . $this->mapinfo["extent"]);
    }

    public function getMapExtent()
    {
        $e = new mb_notice("mbTemplatePdf: getting mapExtent as " . $this->mapinfo["extent"]);
        return $this->mapinfo["extent"];
    }

    public function adjustBbox($elementConf, $aBboxArray, $aSrsString)
    {
        $aMbBbox = new Mapbender_bbox($aBboxArray[0], $aBboxArray[1], $aBboxArray[2], $aBboxArray[3],
            $aSrsString);
        $aMap = new Map();
        $aMap->setWidth($elementConf->width);
        $aMap->setHeight($elementConf->height);
        $aMap->calculateExtent($aMbBbox);
        $this->mapinfo["scale"] = isset($_REQUEST["scale"]) ? $_REQUEST["scale"] : $aMap->getScale($elementConf->res_dpi);
        $adjustedMapExt = $aMap->getExtentInfo();
        return implode(",", $adjustedMapExt);
    }

    private function pageElementsContainsType($pageConf, $type)
    {
        foreach ($pageConf->elements as $_ => $pageElementConf) {
            if ($pageElementConf->type == $type) {
                return true;
            }
        }
        return false;
    }

    private function renderElements($pageConf, $manualValues = array())
    {
        $controls = $this->confPdf->controls;

        foreach ($pageConf->elements as $pageElementId => $pageElementConf) {
            switch ($pageElementConf->type) {
                case "map":
                    $err = new mbMapDecorator($this, $pageElementId, $pageElementConf, $controls, $manualValues);
                    $err = new mbSvgDecorator($this, $pageElementId, $pageElementConf, $controls, $manualValues, "map_svg_kml");
                    $err = new mbSvgDecorator($this, $pageElementId, $pageElementConf, $controls, $manualValues, "map_svg_measures");
                    break;
                case "overview":
                    $err = new mbOverviewDecorator($this, $pageElementId, $pageElementConf, $controls, $manualValues);
                    break;
                case "text":
                    $err = new mbTextDecorator($this, $pageElementId, $pageElementConf, $controls, $manualValues);
                    break;
                case "para":
                    $err = new mbParagraphDecorator($this, $pageElementId, $pageElementConf, $controls, $manualValues);
                    break;
//                    case "measure": ignored, s. case "map":
//                        $err = new mbSvgDecorator($this, $pageElementId, $pageElementConf, $controls, "map_svg_measures");
//                        $err = new mbMeasureDecorator($this, $pageElementId, $pageElementConf, $controls);
//                        break;
                case "image":
                    $err = new mbImageDecorator($this, $pageElementId, $pageElementConf, $controls, $manualValues);
                    break;
                case "legend":
                    if ($this->printLegend == 'true') {
                        $err = new mbLegendDecorator($this, $pageElementId, $pageElementConf, $controls, $manualValues);
                    }
                    break;
                case "permanentImage":
                    $err = new mbPermanentImgDecorator($this, $pageElementId, $pageElementConf, $controls, $manualValues);
                    break;
                case "marker":
                    break;
            }
        }
    }

    public function render()
    {
        foreach ($this->confPdf->pages as $pageConf) {
            $this->objPdf->setSourceFile(dirname(__FILE__) . "/../" . $pageConf->tpl);
            $tplidx = $this->objPdf->importPage($pageConf->useTplPage);

            if (count($pageConf->elements) == 1 && $this->printLegend == 'false' && $this->pageElementsContainsType($pageConf, "legend")) {
                break;
            } else if ($pageConf->featureInfo) {
                $this->renderFeatureInfos($tplidx, $pageConf);
                break;
            } else {
                $this->objPdf->addPage();
                $this->objPdf->useTemplate($tplidx);
                $this->renderElements($pageConf);
            }
        }

        $this->isRendered = true;
    }

    public function save()
    {
        if ($this->isRendered) {
            $this->objPdf->Output(TMPDIR . "/" . $this->outputFileName, "F");
            $this->isSaved = true;
            if (!empty($this->insertPages) || !empty($this->appendFiles)) {
                new mb_notice("inserting pages");

                $dir = TMPDIR;
                $base = $this->baseOutputFileName();


                $separateOk = log_error_exec("pdfseparate $dir/$this->outputFileName $dir/$base-%d.pdf");

                $origPages = $this->objPdf->PageNo();
                $mergePdfs = array();
                for ($i = 1; $i <= $origPages; $i++) {
                    $pageFile = "$dir/$base-$i.pdf";
                    $mergePdfs[] = $pageFile;
                    if (array_key_exists($i, $this->insertPages)) {
                        $fiFile = $this->insertPages[$i];
                        $mergePdfs[] = $fiFile;
                    }
                }
                // Append legend page(s) at the very end
                foreach ($this->appendFiles as $appendFile) {
                    $mergePdfs[] = $appendFile;
                }
                $mergeNames = join(" ", $mergePdfs);
                $uniteOk = log_error_exec("pdfunite $mergeNames $dir/$this->outputFileName");
                $finalSize = file_exists("$dir/$this->outputFileName") ? filesize("$dir/$this->outputFileName") : -1;
                log_error_exec("rm $mergeNames");
            }
        }
    }

    public function unlink($filename)
    {
        if ($this->unlinkFiles && $this->unlinkFiles == 'true') {
            unlink($filename);
        }
    }

    public function logWmsRequests($requestType, $wmsRequest)
    {
        if ($this->logRequests && $this->logRequests == 'true') {
            include_once(dirname(__FILE__) . "/../../classes/class_log.php");
            $logMessage = new log("printPDF_" . $requestType, $wmsRequest, "", $this->logType);
        }
    }

    public function baseOutputFileName()
    {
        return preg_replace("/\\.pdf$/", "", $this->outputFileName);
    }

    private function renderFeatureInfos($tplidx, $pageConf)
    {
        new mb_notice("print featureinfo");

        // Flag to prevent decorator progress reporting during featureInfo rendering
        $this->renderingFeatureInfo = true;
        if (function_exists('pfi_is_rendering_featureinfo')) {
            pfi_is_rendering_featureinfo(true);
        }

        $mapUrls = explode("___", $_REQUEST["map_url"]);


        $backgroundUrls = array();

        foreach ($this->featureInfo->backgroundWMS as $index) {
            $backgroundUrls[] = $mapUrls[$index];
        }


        // Progress tracking
        $pfi_token = (isset($_REQUEST['pfi_progress_token']) && preg_match('/^[a-zA-Z0-9_-]{8,64}$/', $_REQUEST['pfi_progress_token']))
            ? $_REQUEST['pfi_progress_token']
            : '';

        $urlsInBbox = array_values(array_filter((array)$this->featureInfo->urls, function($u) { return $u->inBbox; }));
        $totalUrls  = count($urlsInBbox);
        $urlIndex   = 0;

        foreach ($this->featureInfo->urls as $url) {
            if (!$url->inBbox) {
                continue;
            }

            $featureInfoConnector = new connector();
            $featureInfoConnector->set("timeOut", "10");

            // Update progress: fetching feature info for this layer
            $urlIndex++;
            if ($totalUrls > 0 && $pfi_token) {
                $fetchPercent = 46 + (int)(($urlIndex / ($totalUrls + 1)) * 14);
                pfi_write_progress($pfi_token, 2,
                    'Sachdaten werden abgerufen (' . $urlIndex . '/' . $totalUrls . '): ' . htmlspecialchars($url->title, ENT_QUOTES, 'UTF-8'),
                    $fetchPercent);
            }

            $featureInfoConnector->load($url->request);
            $featureInfoResult = $featureInfoConnector->file;

            $httpCode = intval($featureInfoConnector->getHttpCode());

            if (!trim($featureInfoResult) || preg_match("/<body>\s*<\/body>/i", $featureInfoResult)) {
                continue;
            }

            if ($httpCode >= 400) {
                continue;
            }

            // extract specific wms layer(s) from feature info request

            $matches = array();
            preg_match("/^[^?]*/", $url->request, $matches);
            $host = $matches[0];
            preg_match("/LAYERS=([^&]*)/", $url->request, $matches);
            $queryLayers = explode(",", urldecode($matches[1]));


            // find wms url in mapUrls that contains any of the queried layers
            // Pass 1: strict host match (direct WMS URL)
            // Pass 2: fallback layer-only match (handles owsproxy / URL rewriting)

            $matchedMapUrl = null;
            $matchedLayers = array();
            $matchedStyles = array();
            $candidateLayers = array();

            foreach (array(true, false) as $requireHostMatch) {
                foreach ($mapUrls as $candidateUrl) {
                    if ($requireHostMatch && strpos($candidateUrl, $host) !== 0) {
                        continue;
                    }
                    if (!preg_match("/LAYERS=([^&]*)/", $candidateUrl, $lm)) {
                        continue;
                    }
                    $cLayers = explode(",", urldecode($lm[1]));
                    $found = array_intersect($queryLayers, $cLayers);
                    if (!empty($found)) {
                        $matchedMapUrl = $candidateUrl;
                        $matchedLayers = $found;
                        $candidateLayers = $cLayers;
                        // Extract corresponding styles
                        if (preg_match("/STYLES=([^&]*)/", $candidateUrl, $sm)) {
                            $allStyles = explode(",", $sm[1]);
                            foreach ($found as $layer) {
                                $pos = array_search($layer, $cLayers);
                                $matchedStyles[] = isset($allStyles[$pos]) ? $allStyles[$pos] : "";
                            }
                        }
                        break 2;
                    }
                }
            }

            if (!$matchedMapUrl) {
                new mb_exception("print featureinfo: Found no fitting layer for feature info request.");
                continue;
            }


            // Add page only after we know we have a valid match — avoids blank pages on failure
            $this->objPdf->addPage();
            $this->objPdf->useTemplate($tplidx);


            // construct new map url with only the matched layers
            $layersStr = implode(",", $matchedLayers);
            $mapUrl = preg_replace("/LAYERS=[^&]*/", "LAYERS=$layersStr", $matchedMapUrl);
            if (!empty($matchedStyles)) {
                $stylesStr = implode(",", $matchedStyles);
                $mapUrl = preg_replace("/STYLES=[^&]*/", "STYLES=$stylesStr", $mapUrl);
            }

            // construct new map url

            $mapUrl = join("___", array_merge($backgroundUrls, array($mapUrl)));


            // $url->legendurl is a comma-separated string built in map_obj.js
            // (one entry per in-bbox layer, trailing comma, entries may be "empty").
            // Collect ALL valid HTTP URLs — a combined WMS query may cover several layers.
            $legendUrls = array();
            if (!empty($url->legendurl)) {
                $legendUrlParts = explode(',', $url->legendurl);
                foreach ($legendUrlParts as $legendUrlPart) {
                    $legendUrlPart = trim($legendUrlPart);
                    if ($legendUrlPart === '' || $legendUrlPart === 'empty') {
                        continue;
                    }
                    if (preg_match('/^https?:\/\//i', $legendUrlPart) && substr_count($legendUrlPart, '?') <= 1) {
                        $legendUrls[] = $legendUrlPart;
                    }
                }
            }

            $manualValues = array(
                "title" => $url->title,
                "map_url" => $mapUrl,
            );

            // Suppress the template's legend element while rendering the featureInfo
            // map page — legend will be drawn side-by-side with the map below.
            $savedPrintLegend = $this->printLegend;
            $this->printLegend = 'false';

            // If legend is to be shown, narrow the map element to 70% of its original
            // width so the legend panel can sit beside it (not on top of it).
            $includeLegend = !isset($_REQUEST['pfi_include_legend']) || $_REQUEST['pfi_include_legend'] !== '0';
            $mapElementConf  = null;
            $savedMapWidth   = null;
            if ($includeLegend && !empty($legendUrls)) {
                foreach ($pageConf->elements as $pageElementConf) {
                    if ($pageElementConf->type === 'map') {
                        $mapElementConf = $pageElementConf;
                        break;
                    }
                }
                if ($mapElementConf !== null) {
                    $savedMapWidth = $mapElementConf->width;
                    $mapElementConf->width = $mapElementConf->width * 0.70;
                }
            }

            $this->renderElements($pageConf, $manualValues);
            $this->printLegend = $savedPrintLegend;

            // Restore original map width (so subsequent pages are unaffected).
            if ($savedMapWidth !== null && $mapElementConf !== null) {
                $mapElementConf->width = $savedMapWidth;
            }

            // Draw legend image(s) in the freed 30% to the right of the map.
            if ($includeLegend && !empty($legendUrls) && $mapElementConf !== null) {
                $mapX = floatval($mapElementConf->x_ul);
                $mapY = floatval($mapElementConf->y_ul);
                $mapW = floatval($savedMapWidth);   // original full width
                $mapH = floatval($mapElementConf->height);

                // Legend panel occupies the right 30% of the original map area.
                $panelW  = $mapW * 0.30;
                $panelX  = $mapX + $mapW * 0.70;   // starts right after the narrowed map
                $padding = 1.5;

                // White background panel with a thin left border.
                $this->objPdf->SetFillColor(255, 255, 255);
                $this->objPdf->SetDrawColor(180, 180, 180);
                $this->objPdf->SetLineWidth(0.2);
                $this->objPdf->Rect($panelX, $mapY, $panelW, $mapH, 'FD');

                // "Legende" heading.
                $this->objPdf->SetFont('Arial', 'B', 6);
                $this->objPdf->SetTextColor(0, 0, 0);
                $this->objPdf->SetXY($panelX + $padding, $mapY + $padding);
                $this->objPdf->Cell($panelW - $padding * 2, 4, 'Legende', 0, 1, 'L');

                $curY   = $mapY + $padding + 4 + 1;
                $imgW   = $panelW - $padding * 2;
                // Distribute available height evenly across all legend items so no
                // single large image dominates the panel.
                $availH = $mapH - $padding * 2 - 5;   // subtract heading area
                $maxPerItem = $availH / max(1, count($legendUrls));

                foreach ($legendUrls as $legendUrlItem) {
                    if ($curY >= $mapY + $mapH - $padding) {
                        break;
                    }
                    $legendConnector = new connector();
                    $legendConnector->set('timeOut', '10');
                    $legendConnector->load($legendUrlItem);
                    $legendImgData = $legendConnector->file;
                    if (empty($legendImgData)) {
                        continue;
                    }
                    $tmpImgFile = TMPDIR . '/' . $this->baseOutputFileName()
                        . '-lgnd-' . substr(md5($legendUrlItem), 0, 8) . '.png';
                    file_put_contents($tmpImgFile, $legendImgData);
                    $imgInfo = @getimagesize($tmpImgFile);
                    if ($imgInfo !== false && $imgInfo[0] > 0 && $imgInfo[1] > 0) {
                        // Convert natural pixel size to mm at 96 dpi (screen resolution).
                        $naturalW = $imgInfo[0] * 25.4 / 96.0;
                        $naturalH = $imgInfo[1] * 25.4 / 96.0;
                        // Scale DOWN to panel width if needed, but never upscale.
                        if ($naturalW > $imgW) {
                            $drawW = $imgW;
                            $drawH = $imgW * ($naturalH / $naturalW);
                        } else {
                            $drawW = $naturalW;
                            $drawH = $naturalH;
                        }
                        // Cap height: never exceed per-item budget or remaining space.
                        $maxH = min($maxPerItem, $mapY + $mapH - $padding - $curY);
                        if ($drawH > $maxH && $maxH > 0) {
                            $drawH = $maxH;
                            $drawW = $drawH * ($naturalW / $naturalH);
                            if ($drawW > $imgW) $drawW = $imgW;
                        }
                        $this->objPdf->Image($tmpImgFile, $panelX + $padding, $curY, $drawW, $drawH);
                        $curY += $drawH + 1;
                    }
                    @unlink($tmpImgFile);
                }
            }

            require_once(dirname(__FILE__) . "/../../extensions/dompdf/autoload.inc.php");

            $dompdf = new Dompdf\Dompdf(array(
              "isRemoteEnabled" => true,
              "tempDir" => ABSOLUTE_TMPDIR
            ));

            $format = strtoupper($this->confPdf->format);
            $orientationMap = array(
                "P" => "portrait",
                "L" => "landscape"
            );
            $orientation = $orientationMap[$this->confPdf->orientation];

            $dompdf->setPaper($format, $orientation);

            if (preg_match("/[?&]INFO_FORMAT=text\/plain/i", $url->request)) {
                $featureInfoResult = nl2br(wordwrap($featureInfoResult, 75, "\n", true));
            }

            // The WMS may return multiple full HTML documents concatenated together
            // (one per feature). Dompdf only renders the first <html> block and
            // ignores the rest. Merge all body contents into one valid HTML document.
            $htmlBlocks = preg_split('/(?=<html[\s>])/i', $featureInfoResult);
            if (count($htmlBlocks) > 1) {
                // Extract <head> from first block for styles
                $headContent = '';
                if (preg_match('/<head>(.*?)<\/head>/is', $htmlBlocks[0], $headMatch)) {
                    $headContent = $headMatch[1];
                } elseif (preg_match('/<head>(.*?)<\/head>/is', $htmlBlocks[1], $headMatch)) {
                    $headContent = $headMatch[1];
                }
                // Extract body content from every block
                $allBodies = '';
                foreach ($htmlBlocks as $block) {
                    if (!trim($block)) continue;
                    if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $block, $bodyMatch)) {
                        $allBodies .= $bodyMatch[1];
                    } elseif (preg_match('/<body[^>]*>(.*)/is', $block, $bodyMatch)) {
                        $allBodies .= $bodyMatch[1];
                    }
                }
                $featureInfoResult = '<html><head>' . $headContent . '</head><body>' . $allBodies . '</body></html>';
            }

            if (!empty($pageConf->titleHTML)) {
                if (preg_match("/<body>/i", $featureInfoResult)) {
                    $featureInfoResult = preg_replace("/<body>/i", "$0".$pageConf->titleHTML, $featureInfoResult);
                } else {
                    $featureInfoResult = $pageConf->titleHTML . $featureInfoResult;
                }
            }

            // Hide the red stripe column (.tab1) for BRW-Ort content only.
            // Dompdf 0.8.x cannot handle float-based side-by-side layouts; for other
            // layers the column is wanted and can be left in place.
            // Check both the layer title from config and the <title> tag in the HTML response.
            $htmlTitleMatch = array();
            preg_match('/<title[^>]*>(.*?)<\/title>/is', $featureInfoResult, $htmlTitleMatch);
            $htmlTitle = isset($htmlTitleMatch[1]) ? $htmlTitleMatch[1] : '';
            $hasTab2 = (stripos($featureInfoResult, 'class="tab2"') !== false || stripos($featureInfoResult, "class='tab2'") !== false);
            if ($hasTab2 && (stripos($url->title, 'BRW') !== false || stripos($htmlTitle, 'BRW') !== false)) {
                $domPdfLayoutFix = '<style type="text/css">'
                    . '.tab1 { display: none !important; }'
                    . '.tab2 { margin-left: 0 !important; }'
                    . '</style>';
                if (stripos($featureInfoResult, '</head>') !== false) {
                    $featureInfoResult = str_ireplace('</head>', $domPdfLayoutFix . '</head>', $featureInfoResult);
                } else {
                    $featureInfoResult = $domPdfLayoutFix . $featureInfoResult;
                }
            }

            // Remove remote images (Dompdf 0.8.x cannot reliably load them),
            // but keep base64-encoded images which Dompdf can render inline.
            $featureInfoResult = preg_replace('/<img(?![^>]*src=["\']data:)[^>]*>/i', '', $featureInfoResult);

            // Inject per-layer legend as an inline right-side column (65 / 35 split).
            $includeLegend = !isset($_REQUEST['pfi_include_legend']) || $_REQUEST['pfi_include_legend'] !== '0';
            if ($includeLegend && !empty($legendUrls)) {
                // Legend is already drawn on the FPDF map page above; nothing to do here.
            }

            $dompdf->loadHtml("$featureInfoResult");
            $dompdf->render();
            $dompdfOutput = $dompdf->output();

            // Update progress: rendering page
            if ($totalUrls > 0 && $pfi_token) {
                $renderPercent = 60 + (int)(($urlIndex / $totalUrls) * 10);
                pfi_write_progress($pfi_token, 3,
                    'Seite wird erstellt ' . $urlIndex . '/' . $totalUrls . ': ' . htmlspecialchars($url->title, ENT_QUOTES, 'UTF-8'),
                    $renderPercent);
            }

            $pageNo = $this->objPdf->PageNo();
            $fileName = TMPDIR . "/" . $this->baseOutputFileName() . "-$pageNo-fi.pdf";
            file_put_contents($fileName, $dompdfOutput);
            $this->insertPages[$pageNo] = $fileName;
        }

        if ($pfi_token) {
            pfi_write_progress($pfi_token, 4, 'PDF-Dateien werden zusammengeführt...', 70);
        }
    }

    private function renderLegendPage($pfi_token = '')
    {
        // Respect the user's choice from the print dialog
        if (isset($_REQUEST['pfi_include_legend']) && $_REQUEST['pfi_include_legend'] === '0') {
            return;
        }

        $legendJson = isset($_REQUEST['legend_url']) ? $_REQUEST['legend_url'] : '';
        if (empty($legendJson)) {
            return;
        }

        $wmsLegendArray = json_decode($legendJson, true);
        if (!is_array($wmsLegendArray) || empty($wmsLegendArray)) {
            return;
        }

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">'
              . '<style>'
              . 'body{font-family:Arial,sans-serif;font-size:11pt;margin:10mm 15mm;}'
              . 'h1{font-size:14pt;border-bottom:1px solid #333;padding-bottom:3mm;margin-bottom:6mm;}'
              . 'h2{font-size:11pt;margin:6mm 0 2mm;color:#333;}'
              . '.layer{margin-bottom:4mm;}'
              . '.layer-title{font-size:9pt;color:#555;margin-bottom:1mm;}'
              . 'img{max-width:100%;height:auto;}'
              . '</style></head><body>'
              . '<h1>Legende</h1>';

        $hasContent = false;

        require_once(dirname(__FILE__) . "/../../extensions/dompdf/autoload.inc.php");

        foreach ($wmsLegendArray as $wmsObj) {
            if (!is_array($wmsObj)) {
                continue;
            }
            foreach ($wmsObj as $wmsTitle => $layers) {
                if (!is_array($layers)) {
                    continue;
                }
                $wmsHtml = '';
                foreach ($layers as $layer) {
                    if (!is_array($layer)) {
                        continue;
                    }
                    $legendUrl = isset($layer['legendUrl']) ? $layer['legendUrl'] : '';
                    if (empty($legendUrl) || substr_count($legendUrl, '?') > 1) {
                        continue;
                    }
                    // Reject non-HTTP schemes to prevent SSRF (file://, gopher://, etc.)
                    if (!preg_match('/^https?:\/\//i', $legendUrl)) {
                        continue;
                    }
                    $imgConnector = new connector();
                    $imgConnector->set('timeOut', '10');
                    $imgConnector->load($legendUrl);
                    $imgData = $imgConnector->file;
                    if (empty($imgData)) {
                        continue;
                    }
                    // Detect MIME type from magic bytes
                    if (substr($imgData, 0, 3) === "\xFF\xD8\xFF") {
                        $mime = 'image/jpeg';
                    } elseif (substr($imgData, 0, 4) === "GIF8") {
                        $mime = 'image/gif';
                    } else {
                        $mime = 'image/png';
                    }
                    $b64 = base64_encode($imgData);
                    $layerTitle = isset($layer['title']) ? $layer['title'] : '';
                    $wmsHtml .= '<div class="layer">';
                    if ($layerTitle !== '') {
                        $wmsHtml .= '<div class="layer-title">' . htmlspecialchars($layerTitle, ENT_QUOTES, 'UTF-8') . '</div>';
                    }
                    $wmsHtml .= '<img src="data:' . $mime . ';base64,' . $b64 . '" />';
                    $wmsHtml .= '</div>';
                    $hasContent = true;
                }
                if ($wmsHtml !== '') {
                    $html .= '<h2>' . htmlspecialchars($wmsTitle, ENT_QUOTES, 'UTF-8') . '</h2>' . $wmsHtml;
                }
            }
        }

        $html .= '</body></html>';

        if (!$hasContent) {
            return;
        }

        $dompdf = new Dompdf\Dompdf(array(
            'isRemoteEnabled' => false,
            'tempDir'         => ABSOLUTE_TMPDIR
        ));
        $format      = strtoupper($this->confPdf->format);
        $orientation = ($this->confPdf->orientation === 'L') ? 'landscape' : 'portrait';
        $dompdf->setPaper($format, $orientation);
        $dompdf->loadHtml($html);
        $dompdf->render();
        $output   = $dompdf->output();
        $fileName = TMPDIR . '/' . $this->baseOutputFileName() . '-legend-page.pdf';
        file_put_contents($fileName, $output);
        $this->appendFiles[] = $fileName;
    }
}

?>


