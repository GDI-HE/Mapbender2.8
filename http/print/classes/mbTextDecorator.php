<?php

class mbTextDecorator extends mbTemplatePdfDecorator
{

    protected $pageElementType = "text";
    protected $elementId;
    /* a decorator should declare which parameters could be overwritten through the request object */
    protected $overrideMembersFromRequest = array("value");
    /* the actual text that should be printed */
    protected $value = "Lorem ipsum";

    public function __construct($pdfObj, $elementId, $mapConf, $controls, $manualValues)
    {
        parent::__construct($pdfObj, $mapConf, $controls, $manualValues);
        $this->elementId = $elementId;
        $this->override();
        $this->decorate();
    }

    public function override()
    {
        foreach ($this->overrideMembersFromRequest as $overrideMemberFromRequest) {
            switch ($this->conf->{$overrideMemberFromRequest}) {
                case "date":
                    $this->{$overrideMemberFromRequest} = date("j.n.Y");
                    break;
                case "time":
                    $this->{$overrideMemberFromRequest} = date("G:i");
                    break;
                case "scale":
                    $mapInfoScale = $this->pdf->getMapInfo();
                    $rawScale = floatval($mapInfoScale["scale"]);
                    $widthFactor = isset($mapInfoScale["pfi_map_width_factor"]) && $mapInfoScale["pfi_map_width_factor"] > 0
                        ? floatval($mapInfoScale["pfi_map_width_factor"]) : 1.0;
                    $correctedScale = $widthFactor != 1.0 ? $rawScale / $widthFactor : $rawScale;
                    // Round to the same nearest power-of-10 magnitude as the JS printbox does
                    if ($correctedScale > 0) {
                        $magnitude = pow(10, floor(log10($correctedScale)));
                        $correctedScale = round($correctedScale / $magnitude) * $magnitude;
                    }
                    $this->{$overrideMemberFromRequest} = "1 : " . intval($correctedScale);
                    break;
                default:
                    $this->overrideMembers();
                    break;
            }
        }
    }

    public function decorate()
    {
        $rgb = array(0, 0, 0);
        $fontColor = $this->conf->font_color;
        if (isset($fontColor) && $fontColor !== "" && $fontColor !== null) {
            $rgb = explode(',', $fontColor);
        }
        $this->pdf->objPdf->setTextColor($rgb[0], $rgb[1], $rgb[2]);
        $fontStyle = isset($this->conf->font_style) ? $this->conf->font_style : "";
        $this->pdf->objPdf->setFont($this->conf->font_family, $fontStyle, $this->conf->font_size);
        new mb_notice("print: " . $this->conf->x_ul . " " . $this->conf->y_ul . " " . $this->value);
        $this->pdf->objPdf->Text($this->conf->x_ul, $this->conf->y_ul, utf8_decode($this->value));
    }


}

?>

