<?php

require_once(dirname(__FILE__)."/../../../core/globalSettings.php");
require_once(dirname(__FILE__) . "/../../classes/class_json.php");
require_once(dirname(__FILE__) . "/../../classes/class_administration.php");


abstract class mbPrintFactory {

	protected function createOutput($jsonConf, $concreteFactory, $identifier = null) {
		return $concreteFactory->create($jsonConf, $identifier);
	}
	
	abstract public function create($jsonConf, $identifier = null);

}

class mbPdfFactory extends mbPrintFactory {

	private function readConfig($jsonConfFile) {
		$admin = new administration();
		$mbjson = new Mapbender_JSON(); 
		$jsonStr = file_get_contents(dirname(__FILE__) . "/../".$jsonConfFile);
		if ($jsonStr == false) {
			$e = new mb_exception("mbPdfFactory: config file could not be read.");
			die("config not found.");
		}
		$jsonConf = $mbjson->decode($admin->char_encode($jsonStr));
		return $jsonConf;
	}
	
	public function create($jsonConfFile, $identifier = null) {
 		$jsonConf = $this->readConfig($jsonConfFile);
 		
 		// For paper sizes other than the default FPDF sizes, give size in mm instead of name!
 		switch($jsonConf->format) {
 		    case 'a0':
 			$jsonConf->format = array(841,1189);
 			break;
 		    case 'a1':
 			$jsonConf->format = array(594,841);
 			break;
 		    case 'a2':
 			$jsonConf->format = array(420,594);
 			break;
 		}
 		
		try {
			switch ($jsonConf->type) {
				case "templatePDF":
					$factory = new mbTemplatePdfFactory();
            		break;
				case "dynamicPDF":
					$factory = new mbDynamicPdfFactory();
            		break;
				default:
					$e = new mb_exception("mbPdfFactory: output type not supported.");
			}
			return $this->createOutput($jsonConf, $factory, $identifier);
		} catch (Exception $e){
			$e = new mb_exception("mbPdfFactory: could not create PDF output.");
		} 
		
		return;
	}

}

class mbTemplatePdfFactory extends mbPrintFactory {

	public function create($jsonConf, $identifier = null) {
		return new mbTemplatePdf($jsonConf, $identifier);
	}

}

class mbDynamicPdfFactory extends mbPrintFactory {

	public function create($jsonConf, $identifier = null) {
		return new mbDynamicPdf($jsonConf, $identifier);
	}

}

function __autoload($class_name) {
	if (file_exists(dirname(__FILE__). "/{$class_name}.php"))
    	require_once $class_name . '.php';
}

?>
