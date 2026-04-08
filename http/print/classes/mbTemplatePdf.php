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
            if (!empty($this->insertPages)) {
                new mb_notice("inserting pages");

                $dir = TMPDIR;
                $base = $this->baseOutputFileName();
                log_error_exec("pdfseparate $dir/$this->outputFileName $dir/$base-%d.pdf");
                $origPages = $this->objPdf->PageNo();
                $mergePdfs = array();
                for ($i = 1; $i <= $origPages; $i++) {
                    $mergePdfs[] = "$dir/$base-$i.pdf";
                    if (array_key_exists($i, $this->insertPages)) {
                        $mergePdfs[] = $this->insertPages[$i];
                    }
                }
                $mergeNames = join(" ", $mergePdfs);
                log_error_exec("pdfunite $mergeNames $dir/$this->outputFileName");
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

        $mapUrls = explode("___", $_REQUEST["map_url"]);

        new mb_notice("print featureinfo: mapUrls: " . join(", ", $mapUrls));

        $backgroundUrls = array();

        foreach ($this->featureInfo->backgroundWMS as $index) {
            $backgroundUrls[] = $mapUrls[$index];
        }

        new mb_notice("print featureinfo: " . json_encode($backgroundUrls));

        foreach ($this->featureInfo->urls as $url) {
            if (!$url->inBbox) {
                continue;
            }

            $featureInfoConnector = new connector();
            $featureInfoConnector->set("timeOut", "10");
            $featureInfoConnector->load($url->request);
            $featureInfoResult = $featureInfoConnector->file;

            if (!trim($featureInfoResult) || preg_match("/<body>\s*<\/body>/i", $featureInfoResult)) {
                continue;
            }

            if (intval($featureInfoConnector->getHttpCode()) >= 400) {
                continue;
            }

            $this->objPdf->addPage();
            $this->objPdf->useTemplate($tplidx);

            // extract specific wms layer(s) from feature info request

            $matches = array();
            preg_match("/^[^?]*/", $url->request, $matches);
            $host = $matches[0];
            preg_match("/LAYERS=([^&]*)/", $url->request, $matches);
            $queryLayers = explode(",", $matches[1]);

            new mb_notice("print featureinfo: host: $host, layers: " . implode(",", $queryLayers));

            // find wms url in mapUrls that contains any of the queried layers

            $matchedMapUrl = null;
            $matchedLayers = array();
            $matchedStyles = array();

            foreach ($mapUrls as $candidateUrl) {
                // Must be from the same host
                if (strpos($candidateUrl, $host) !== 0) {
                    continue;
                }
                if (!preg_match("/LAYERS=([^&]*)/", $candidateUrl, $lm)) {
                    continue;
                }
                $candidateLayers = explode(",", $lm[1]);
                // Check if any of our query layers appear in this map URL
                $found = array_intersect($queryLayers, $candidateLayers);
                if (!empty($found)) {
                    $matchedMapUrl = $candidateUrl;
                    $matchedLayers = $found;
                    // Extract corresponding styles
                    if (preg_match("/STYLES=([^&]*)/", $candidateUrl, $sm)) {
                        $allStyles = explode(",", $sm[1]);
                        foreach ($found as $layer) {
                            $pos = array_search($layer, $candidateLayers);
                            $matchedStyles[] = isset($allStyles[$pos]) ? $allStyles[$pos] : "";
                        }
                    }
                    break;
                }
            }

            if (!$matchedMapUrl) {
                new mb_exception("print featureinfo: Found no fitting layer for feature info request.");
                continue;
            }

            new mb_notice("print featureinfo: found url: $matchedMapUrl");

            // construct new map url with only the matched layers
            $layersStr = implode(",", $matchedLayers);
            $mapUrl = preg_replace("/LAYERS=[^&]*/", "LAYERS=$layersStr", $matchedMapUrl);
            if (!empty($matchedStyles)) {
                $stylesStr = implode(",", $matchedStyles);
                $mapUrl = preg_replace("/STYLES=[^&]*/", "STYLES=$stylesStr", $mapUrl);
            }

            // construct new map url

            $mapUrl = join("___", array_merge($backgroundUrls, array($mapUrl)));

            new mb_notice("print featureinfo: new url: $mapUrl");

            // Reject legend URLs that contain more than one '?' — these are malformed parent-layer
            // URLs where multiple GetLegendGraphic requests are concatenated (e.g. STYLE=...,https://...?...).
            $rawLegendUrl = ($url->legendurl !== "empty" && !empty($url->legendurl)) ? $url->legendurl : "";
            $legendUrl = (substr_count($rawLegendUrl, '?') <= 1) ? $rawLegendUrl : "";

            $manualValues = array(
                "title" => $url->title,
                "map_url" => $mapUrl,
                "legend_url" => json_encode(array(
                    array(
                        "Legende" => array(
                            array(
                                "title" => $url->title,
                                "legendUrl" => $legendUrl
                            )
                        )
                    )
                ))
            );

            $this->renderElements($pageConf, $manualValues);

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

            // Hide the red stripe column (.tab1) as Dompdf 0.8.x cannot handle
            // float-based side-by-side layouts and causes timeouts/rendering issues.
            $domPdfLayoutFix = '<style type="text/css">'
                . '.tab1 { display: none !important; }'
                . '.tab2 { margin-left: 0 !important; }'
                . '</style>';
            if (stripos($featureInfoResult, '</head>') !== false) {
                $featureInfoResult = str_ireplace('</head>', $domPdfLayoutFix . '</head>', $featureInfoResult);
            } else {
                $featureInfoResult = $domPdfLayoutFix . $featureInfoResult;
            }

            // Remove all images - Dompdf 0.8.x cannot reliably load remote images.
            $featureInfoResult = preg_replace('/<img[^>]*>/i', '', $featureInfoResult);

            $dompdf->loadHtml("$featureInfoResult");
            $dompdf->render();

            $pageNo = $this->objPdf->PageNo();
            $fileName = TMPDIR . "/" . $this->baseOutputFileName() . "-$pageNo-fi.pdf";
            file_put_contents($fileName, $dompdf->output());
            $this->insertPages[$pageNo] = $fileName;
        }
    }
}

?>


