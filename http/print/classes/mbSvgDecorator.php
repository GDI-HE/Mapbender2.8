<?php

class mbSvgDecorator extends mbTemplatePdfDecorator
{
//    protected $pageElementType = "svg";
//    protected $elementId;

    protected $pageElementType = "map";
    protected $elementId;
    protected $filename;
    /* a decorator should declare which parameters could be overwritten through the request object */
    protected $overrideMembersFromRequest = array("res_dpi", "angle");
    protected $res_dpi;
    protected $angle = 0;

    public function __construct($pdfObj, $elementId, $mapConf, $controls, $manualValues, $svgParam)
    {
        parent::__construct($pdfObj, $mapConf, $controls, $manualValues);
        $this->elementId = $elementId;
        $this->filename = TMPDIR . "/" . parent::generateOutputFileName($svgParam, "png");
        $this->svgParam = $svgParam;
        $this->override();
        $this->decorate();
    }

    public function override()
    {

    }

    public function decorate()
    {
        require_once(dirname(__FILE__) . "/../print_functions.php");

        global $mapOffset_left, $mapOffset_bottom, $map_height, $map_width, $coord;
        global $yAxisOrientation;
        $yAxisOrientation = 1;
        $doc = new \DOMDocument();
        if ($this->hasValue("svg_extent") && count(explode(',', $this->getValue("svg_extent"))) === 4 &&
            $this->hasValue($this->svgParam) && @$doc->loadXML($this->getValue($this->svgParam))) {
            $e = new mb_notice("mbSvgDecorator: svg: " . $this->getValue($this->svgParam));
        } else {
            return "No svg found.";
        }

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace("xlink", "http://www.w3.org/1999/xlink");
        $xpath->registerNamespace("svg", "http://www.w3.org/2000/svg");
        $coord = mb_split(",", $this->pdf->getMapExtent());
        $mapInfo = $this->pdf->getMapInfo();
        foreach ($mapInfo as $k => $v) {
            $e = new mb_notice("mbSvgDecorator: mapInfo: " . $k . "=" . $v);
        }
        $mapOffset_left = $mapInfo["x_ul"];
        $mapOffset_bottom = $mapInfo["y_ul"];
        $map_height = $mapInfo["height"];
        $map_width = $mapInfo["width"];
        $map_extent = explode(',', $mapInfo["extent"]);

        $oext = explode(',', $this->getValue("svg_extent"));
        $angle = $this->hasValue('angle') ? floatval($this->getValue('angle')) : 0;
        $svg_w = intval(preg_replace('[^0-9]', '', $doc->documentElement->getAttribute("width")));
        $svg_h = intval(preg_replace('[^0-9]', '', $doc->documentElement->getAttribute("height")));
        $res = $this->pdf->objPdf->k * ($this->conf->res_dpi / 72);
        $map_width_px = intval(round($map_width * $res));
        $map_height_px = intval(round($map_height * $res));

        // calculate factors for x and y
        $k_svg_x = ($oext[2] - $oext[0]) / $svg_w;
        $k_svg_y = ($oext[3] - $oext[1]) / $svg_h;
        // calculate offsets for x and y
        $offset_svg_x_px = ($oext[0] - $map_extent[0]) / $k_svg_x;
        $offset_svg_y_px = ($oext[3] - $map_extent[3]) / $k_svg_y;
        $svg_bbox_w = ($map_extent[2] - $map_extent[0]) / $k_svg_x;
        $svg_bbox_h = ($map_extent[3] - $map_extent[1]) / $k_svg_y;

        $scale = 1;
        $padding = 2;
        if ($svg_bbox_w > $svg_bbox_h) {
            $scale = ($map_width_px - $padding) / $svg_bbox_w;
        } else {
            $scale = ($map_height_px - $padding) / $svg_bbox_h;
        }
        if ($angle != 0) {
            $neededHeight = round(abs(sin(deg2rad($angle)) * $map_width_px) + abs(cos(deg2rad($angle)) * $map_width_px));
            $neededWidth = round(abs(sin(deg2rad($angle)) * $map_height_px) + abs(cos(deg2rad($angle)) * $map_height_px));
            $x = $offset_svg_x_px * $scale + ($neededWidth - $map_width_px) / 2;
            $y = (-$offset_svg_y_px * $scale) + ($neededHeight - $map_height_px) / 2;
            $doc->documentElement->setAttribute("height", $neededHeight);
            $doc->documentElement->setAttribute("width", $neededWidth);
        } else {
            $x = $offset_svg_x_px * $scale;
            $y = -$offset_svg_y_px * $scale;
            $doc->documentElement->setAttribute("height", $map_height_px);
            $doc->documentElement->setAttribute("width", $map_width_px);
        }
        foreach ($xpath->query("//*[@d]", $doc->documentElement) as $elm) {
            $elm->setAttribute('transform', "translate($x,$y) scale($scale,$scale)"); #rotate($angle, $rx0, $ry0)
        }
        foreach ($xpath->query("//svg:text", $doc->documentElement) as $elm) {
            $elm->setAttribute('transform', "translate($x,$y) scale($scale,$scale)"); #rotate($angle, $rx0, $ry0)
        }
//        foreach ($xpath->query("//*[@style]", $doc->documentElement) as $elm) {
//            $elm->removeAttribute('style');
//        }
        // Extract the minimum opacity from SVG elements so we can apply it
        // at PDF level (more reliable than relying on Imagick alpha channels)
        $svgOpacity = 1.0;

        // Check 'opacity' attribute
        foreach ($xpath->query("//*[@opacity]") as $elm) {
            $val = floatval($elm->getAttribute('opacity'));
            if ($val > 0 && $val < $svgOpacity) {
                $svgOpacity = $val;
            }
        }

        // Also check 'fill-opacity' attribute
        foreach ($xpath->query("//*[@fill-opacity]") as $elm) {
            $val = floatval($elm->getAttribute('fill-opacity'));
            if ($val > 0 && $val < $svgOpacity) {
                $svgOpacity = $val;
            }
        }

        // Also check inline style for opacity/fill-opacity
        foreach ($xpath->query("//*[@style]") as $elm) {
            $style = $elm->getAttribute('style');
            if (preg_match('/(?:^|;)\s*fill-opacity\s*:\s*([0-9.]+)/i', $style, $m)) {
                $val = floatval($m[1]);
                if ($val > 0 && $val < $svgOpacity) {
                    $svgOpacity = $val;
                }
            }
            if (preg_match('/(?:^|;)\s*opacity\s*:\s*([0-9.]+)/i', $style, $m)) {
                $val = floatval($m[1]);
                if ($val > 0 && $val < $svgOpacity) {
                    $svgOpacity = $val;
                }
            }
        }

        // Strip ALL opacity from SVG elements so Imagick renders solid pixels.
        // The transparency will be applied via PDF ExtGState instead.
        if ($svgOpacity < 1.0) {
            foreach ($xpath->query("//*[@opacity]") as $elm) {
                $elm->removeAttribute('opacity');
            }
            foreach ($xpath->query("//*[@fill-opacity]") as $elm) {
                $elm->removeAttribute('fill-opacity');
            }
            // Strip opacity and fill-opacity from inline styles too
            foreach ($xpath->query("//*[@style]") as $elm) {
                $style = $elm->getAttribute('style');
                $style = preg_replace('/(?:^|(?<=;))\s*fill-opacity\s*:\s*[0-9.]+\s*;?/i', '', $style);
                $style = preg_replace('/(?:^|(?<=;))\s*opacity\s*:\s*[0-9.]+\s*;?/i', '', $style);
                $elm->setAttribute('style', trim($style, '; '));
            }
        }

        $imagick = new \Imagick();
        $imagick->setBackgroundColor(new \ImagickPixel('none'));
        $imagick->readImageBlob($doc->saveXML());
        $quantum = $imagick->getQuantumRange()['quantumRangeLong'];
        $imagick->transparentPaintImage($imagick->getImagePixelColor(0, 0), 0.0, $quantum * 0.05, false);
        $imagick->setImageFormat("png32");
        if ($angle != 0) {
            $imagick->rotateImage(new \ImagickPixel('none'), $angle);
//            $imgWidth = $imagick->getImageWidth();
//            $imgHeight = $imagick->getImageHeight();
//            $imagick->cropImage($map_width_px, $map_height_px, ($imgWidth-$map_width_px)/2, ($imgHeight-$map_height_px)/2); orig, imagick bug?
            $imagick->cropImage($map_width_px, $map_height_px, ($neededWidth - $map_width_px) / 2,
                ($neededHeight - $map_height_px) / 2);
        }
        file_put_contents($this->filename, $imagick->getImageBlob());
        if ($svgOpacity < 1.0) {
            $this->pdf->objPdf->SetAlpha($svgOpacity);
        }
        $this->pdf->objPdf->Image($this->filename, $mapOffset_left, $mapOffset_bottom, $map_width, $map_height, 'png');
        if ($svgOpacity < 1.0) {
            $this->pdf->objPdf->SetAlpha(1.0);
        }
        $this->pdf->unlink($this->filename);
    }
}

