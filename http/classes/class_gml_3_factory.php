<?php
# $Id: class_wfs.php 3094 2008-10-01 13:52:35Z christoph $
# http://www.mapbender.org/index.php/class_wfs.php
# Copyright (C) 2002 CCGIS 
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2, or (at your option)
# any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.

require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__)."/../classes/class_gml_factory.php");
require_once(dirname(__FILE__)."/../classes/class_gml_3.php");
require_once(dirname(__FILE__)."/../classes/class_connector.php");
require_once(dirname(__FILE__)."/../classes/class_administration.php");

/**
 * Creates GML 3 objects from a GML documents.
 * 
 * @return Gml_3
 */
class Gml_3_Factory extends GmlFactory {

	/**
	 * Creates a GML object from a GeoJSON (http://www.geojson.org) String
	 * 
	 * @return Gml_3
	 * @param $geoJson String
	 */
	public function createFromGeoJson ($geoJson) {
		$gml3 = new Gml_3();
		
		return parent::createFromGeoJson($geoJson, $gml3);
	}

	/**
	 * Creates GML 3 objects from GML documents.
	 * 
	 * @return Gml_3
	 * @param $xml String
	 */
	public function createFromXml ($xml, $wfsConf, $myWfs=false, $myFeatureType=false, $geomColumnName=false) {
		$gml3 = new Gml_3();
		return parent::createFromXml($xml, $wfsConf, $gml3, $myWfs, $myFeatureType, $geomColumnName);
	}	

	public static function getDimensionFromNode ($domNode) {
		if (!$domNode->hasAttribute("srsDimension")) {
			return 2;
		}
		$dim = intval($domNode->getAttribute("srsDimension"), 10);
		if (2 == $dim || 3 == $dim) {
			return $dim;
		}
		return 2;
	}
	
	function findNameSpace($s){
		if (strpos($s, ":") === false) {
			$ns = "";
			$FeaturePropertyName = $s;
		}
		else {
			list($ns,$FeaturePropertyName) = explode(":",$s, 2);
		}
		$nodeName = array('ns' => $ns, 'value' => $FeaturePropertyName);
		return $nodeName;
	}

	private static function getNodeLocalName($domNode) {
		if (!($domNode instanceof DOMNode)) {
			return "";
		}
		if (!empty($domNode->localName)) {
			return $domNode->localName;
		}
		$parts = explode(":", $domNode->nodeName, 2);
		return (count($parts) === 2) ? $parts[1] : $parts[0];
	}

	private static function isKnownGeometryType($localName) {
		$knownTypes = array(
			"Point",
			"LineString",
			"Curve",
			"Polygon",
			"MultiPoint",
			"MultiLineString",
			"MultiCurve",
			"MultiSurface",
			"MultiPolygon"
		);
		return in_array($localName, $knownTypes, true);
	}

	private static function findFirstGeometryElement($domNode) {
		if (!($domNode instanceof DOMNode) || !$domNode->hasChildNodes()) {
			return null;
		}
		$currentChild = $domNode->firstChild;
		while ($currentChild) {
			if ($currentChild->nodeType === XML_ELEMENT_NODE) {
				$localName = self::getNodeLocalName($currentChild);
				if (self::isKnownGeometryType($localName)) {
					return $currentChild;
				}
				$nestedGeom = self::findFirstGeometryElement($currentChild);
				if ($nestedGeom instanceof DOMNode) {
					return $nestedGeom;
				}
			}
			$currentChild = $currentChild->nextSibling;
		}
		return null;
	}

	private static function getSrsFromGeometryContext($geomNode, $fallbackSrs) {
		$currentNode = $geomNode;
		while ($currentNode instanceof DOMNode) {
			if ($currentNode->nodeType === XML_ELEMENT_NODE && $currentNode->hasAttributes() && $currentNode->attributes->getNamedItem("srsName")) {
				$srs = $currentNode->getAttribute("srsName");
				if ($srs !== "") {
					return $srs;
				}
			}
			$currentNode = $currentNode->parentNode;
		}
		return $fallbackSrs;
	}

	private static function xpathGeometryNodes($simpleXMLNode, $prefixedPath, $localNamePath) {
		$nodes = $simpleXMLNode->xpath($prefixedPath);
		if (count($nodes) === 0) {
			$nodes = $simpleXMLNode->xpath($localNamePath);
		}
		return $nodes;
	}

	public static function parsePoint ($domNode) {
		$gmlPoint = new GmlPoint();
		$currentSibling = $domNode->firstChild;
		while ($currentSibling) {
			$coordArray = explode(" ", $currentSibling->nodeValue);
			$gmlPoint->setPoint($coordArray[0], $coordArray[1]);
			$currentSibling = $currentSibling->nextSibling;
		}
		return $gmlPoint;
	}

	public static function parseLine ($domNode) {
		$gmlLine = new GmlLine();
		
		$currentSibling = $domNode->firstChild;
		while ($currentSibling) {

			$dim = self::getDimensionFromNode($currentSibling);
			$coordArray = explode(' ', trim($currentSibling->nodeValue));
			for ($i = 0; $i < count($coordArray); $i += $dim) {
				$x = $coordArray[$i];
				$y = $coordArray[$i+1];
				$gmlLine->addPoint($x, $y);
			}
			$currentSibling = $currentSibling->nextSibling;
		}
		return $gmlLine;
	}

	public static function parseCurve ($domNode) {
		$gmlLine = new GmlLine();
		$simpleXMLNode = simplexml_import_dom($domNode);
		$simpleXMLNode->registerXPathNamespace('gml', 'http://www.opengis.net/gml');

		$allCoords = self::xpathGeometryNodes(
			$simpleXMLNode,
			"gml:segments/gml:LineStringSegment/gml:posList",
			"*[local-name()='segments']/*[local-name()='LineStringSegment']/*[local-name()='posList']"
		);
		if (count($allCoords) === 0) {
			$allCoords = self::xpathGeometryNodes(
				$simpleXMLNode,
				"gml:LineStringSegment/gml:posList",
				"*[local-name()='LineStringSegment']/*[local-name()='posList']"
			);
		}

		foreach ($allCoords as $coords) {
			$coordsDom = dom_import_simplexml($coords);
			$dim = self::getDimensionFromNode($coordsDom);
			$coordArray = preg_split('/\s+/', trim($coordsDom->nodeValue));
			$coordCount = count($coordArray);
			for ($i = 0; $i + 1 < $coordCount; $i += $dim) {
				if (!isset($coordArray[$i + 1])) {
					break;
				}
				$x = $coordArray[$i];
				$y = $coordArray[$i + 1];
				$gmlLine->addPoint($x, $y);
			}
		}

		if ($gmlLine->isEmpty()) {
			return self::parseLine($domNode);
		}

		return $gmlLine;
	}

	public static function parsePolygon ($domNode) {
		$gmlPolygon = new GmlPolygon();
		
		$simpleXMLNode = simplexml_import_dom($domNode);

		$simpleXMLNode->registerXPathNamespace('gml', 'http://www.opengis.net/gml');
		
		$allCoords = self::xpathGeometryNodes(
			$simpleXMLNode,
			"gml:exterior/gml:LinearRing/gml:posList",
			"*[local-name()='exterior']/*[local-name()='LinearRing']/*[local-name()='posList']"
		);
			
		$cnt=0;
		foreach ($allCoords as $Coords) {
			$coordsDom = dom_import_simplexml($Coords);
			
			$dim = self::getDimensionFromNode($coordsDom);
			$coordArray = explode(' ', trim($coordsDom->nodeValue));
			for ($i = 0; $i < count($coordArray); $i += $dim) {
				$x = $coordArray[$i];
				$y = $coordArray[$i+1];
				$gmlPolygon->addPoint($x, $y);
			}
			$cnt++;
		}
		
		$innerRingNodeArray = self::xpathGeometryNodes(
			$simpleXMLNode,
			"gml:innerBoundaryIs/gml:LinearRing",
			"*[local-name()='innerBoundaryIs']/*[local-name()='LinearRing']"
		);
		if ($innerRingNodeArray) {
			$ringCount = 0;
			foreach ($innerRingNodeArray as $ringNode) {
				$coordinates = self::xpathGeometryNodes(
					$ringNode,
					"gml:coordinates",
					"*[local-name()='coordinates']"
				);
				foreach ($coordinates as $coordinate) {
					$coordsDom = dom_import_simplexml($coordinate);
						
					$dim = self::getDimensionFromNode($coordsDom);
					$coordArray = explode(' ', trim($coordsDom->nodeValue));
					for ($i = 0; $i < count($coordArray); $i += $dim) {
						$x = $coordArray[$i];
						$y = $coordArray[$i+1];
						$gmlPolygon->addPointToRing($ringCount, $x, $y);
					}
				}
				$ringCount++;
			}
		}
		return $gmlPolygon;
	}

	public static function parseMultiLine ($domNode) {
		$gmlMultiLine = new GmlMultiLine();
		
		$simpleXMLNode = simplexml_import_dom($domNode);

		$simpleXMLNode->registerXPathNamespace('gml', 'http://www.opengis.net/gml');
		
		$allCoords = self::xpathGeometryNodes(
			$simpleXMLNode,
			"gml:lineStringMember/gml:LineString/gml:posList",
			"*[local-name()='lineStringMember']/*[local-name()='LineString']/*[local-name()='posList']"
		);
		if (count($allCoords) === 0) {
			$allCoords = self::xpathGeometryNodes(
				$simpleXMLNode,
				"gml:lineStringMembers/gml:LineString/gml:posList",
				"*[local-name()='lineStringMembers']/*[local-name()='LineString']/*[local-name()='posList']"
			);
		}
			
		$cnt=0;
		foreach ($allCoords as $Coords) {
			
			$gmlMultiLine->lineArray[$cnt] = array();
			
			$coordsDom = dom_import_simplexml($Coords);
				
			$dim = self::getDimensionFromNode($coordsDom);
			$coordArray = explode(' ', trim($coordsDom->nodeValue));
			for ($i = 0; $i < count($coordArray); $i += $dim) {
				$x = $coordArray[$i];
				$y = $coordArray[$i+1];
				$gmlMultiLine->addPoint($x, $y, $cnt);
			}			
			$cnt++;
		}
		return $gmlMultiLine;
	}	
	
	public static function parseMultiPoint ($domNode) {
		$gmlMultiPoint = new GmlMultiPoint();
		
		$simpleXMLNode = simplexml_import_dom($domNode);

		$simpleXMLNode->registerXPathNamespace('gml', 'http://www.opengis.net/gml');
		
		$allCoords = self::xpathGeometryNodes(
			$simpleXMLNode,
			"gml:pointMember/gml:Point/gml:pos",
			"*[local-name()='pointMember']/*[local-name()='Point']/*[local-name()='pos']"
		);
		if (count($allCoords) === 0) {
			$allCoords = self::xpathGeometryNodes(
				$simpleXMLNode,
				"gml:pointMembers/gml:Point/gml:pos",
				"*[local-name()='pointMembers']/*[local-name()='Point']/*[local-name()='pos']"
			);
		}
			
		foreach ($allCoords as $Coords) {
			
			$coordsDom = dom_import_simplexml($Coords);
				
			$dim = self::getDimensionFromNode($coordsDom);
			$coordArray = explode(' ', trim($coordsDom->nodeValue));
			for ($i = 0; $i < count($coordArray); $i += $dim) {
				$x = $coordArray[$i];
				$y = $coordArray[$i+1];
				$gmlMultiPoint->addPoint($x, $y);
				break;
			}			
		}
		return $gmlMultiPoint;
	}		

	public static function parseMultiCurve ($domNode) {
		$gmlMultiLine = new GmlMultiLine();
		
		$simpleXMLNode = simplexml_import_dom($domNode);

		$simpleXMLNode->registerXPathNamespace('gml', 'http://www.opengis.net/gml');
		
		$allCoords = self::xpathGeometryNodes(
			$simpleXMLNode,
			"gml:curveMembers/gml:LineString/gml:posList",
			"*[local-name()='curveMembers']/*[local-name()='LineString']/*[local-name()='posList']"
		);
		if (count($allCoords) === 0) {
			$allCoords = self::xpathGeometryNodes(
				$simpleXMLNode,
				"gml:curveMember/gml:LineString/gml:posList",
				"*[local-name()='curveMember']/*[local-name()='LineString']/*[local-name()='posList']"
			);
		}
		if (count($allCoords) === 0) {
			$allCoords = self::xpathGeometryNodes(
				$simpleXMLNode,
				"gml:curveMember/gml:Curve/gml:segments/gml:LineStringSegment/gml:posList",
				"*[local-name()='curveMember']/*[local-name()='Curve']/*[local-name()='segments']/*[local-name()='LineStringSegment']/*[local-name()='posList']"
			);
		}
		if (count($allCoords) === 0) {
			$allCoords = self::xpathGeometryNodes(
				$simpleXMLNode,
				"gml:curveMembers/gml:Curve/gml:segments/gml:LineStringSegment/gml:posList",
				"*[local-name()='curveMembers']/*[local-name()='Curve']/*[local-name()='segments']/*[local-name()='LineStringSegment']/*[local-name()='posList']"
			);
		}
			
		$cnt=0;
		foreach ($allCoords as $Coords) {
			
			$gmlMultiLine->lineArray[$cnt] = array();
			
			$coordsDom = dom_import_simplexml($Coords);

			$dim = self::getDimensionFromNode($coordsDom);
			$coordArray = explode(' ', trim($coordsDom->nodeValue));
			for ($i = 0; $i < count($coordArray); $i += $dim) {
				$x = $coordArray[$i];
				$y = $coordArray[$i+1];
				$gmlMultiLine->addPoint($x, $y, $cnt);
			}
			$cnt++;
		}
		return $gmlMultiLine;
	}		
	
	public static function parseMultiPolygon ($domNode) {
		$gmlMultiPolygon = new GmlMultiPolygon();
		
		$simpleXMLNode = simplexml_import_dom($domNode);

		$simpleXMLNode->registerXPathNamespace('gml', 'http://www.opengis.net/gml');

		$allPolygons = self::xpathGeometryNodes(
			$simpleXMLNode,
			"gml:surfaceMember/gml:Polygon",
			"*[local-name()='surfaceMember']/*[local-name()='Polygon']"
		);
		if (count($allPolygons) === 0) {
			$allPolygons = self::xpathGeometryNodes(
				$simpleXMLNode,
				"gml:surfaceMembers/gml:Polygon",
				"*[local-name()='surfaceMembers']/*[local-name()='Polygon']"
			);
		}
		
		$cnt=0;
		foreach ($allPolygons as $polygon) {
			$allCoords = self::xpathGeometryNodes(
				$polygon,
				"gml:exterior/gml:LinearRing/gml:posList",
				"*[local-name()='exterior']/*[local-name()='LinearRing']/*[local-name()='posList']"
			);
				
			$gmlMultiPolygon->polygonArray[$cnt] = array();
			foreach ($allCoords as $Coords) {
				
				$coordsDom = dom_import_simplexml($Coords);

				$dim = self::getDimensionFromNode($coordsDom);
				$coordArray = explode(' ', trim($coordsDom->nodeValue));
				for ($i = 0; $i < count($coordArray); $i += $dim) {
					$x = $coordArray[$i];
					$y = $coordArray[$i+1];
					$gmlMultiPolygon->addPoint($x, $y, $cnt);
				}
			}
			
			$gmlMultiPolygon->innerRingArray[$cnt] = array();
			$innerRingNodeArray = self::xpathGeometryNodes(
				$polygon,
				"gml:interior",
				"*[local-name()='interior']"
			);
			if ($innerRingNodeArray) {
				$ringCount = 0;
				foreach ($innerRingNodeArray as $ringNode) {
					$currentRingNode = self::xpathGeometryNodes(
						$ringNode,
						"gml:LinearRing",
						"*[local-name()='LinearRing']"
					);
					foreach ($currentRingNode as $node) {
						$coordinates = self::xpathGeometryNodes(
							$node,
							"gml:posList",
							"*[local-name()='posList']"
						);
						foreach ($coordinates as $coordinate) {
							$coordsDom = dom_import_simplexml($coordinate);
								
							$dim = self::getDimensionFromNode($coordsDom);
							$coordArray = explode(' ', trim($coordsDom->nodeValue));
							for ($i = 0; $i < count($coordArray); $i += $dim) {
								$x = $coordArray[$i];
								$y = $coordArray[$i+1];
								$gmlMultiPolygon->addPointToRing($cnt, $ringCount, $x, $y);
							}
						}
						$ringCount++;
						
					}
				}
			}
			$cnt++;
//			new mb_exception("create multipolygon " . serialize($gmlMultiPolygon->innerRingArray));
		}
		return $gmlMultiPolygon;		
	}
	
	/**
	 * Parses the feature segment of a GML and stores the geometry in the
	 * $geometry variable of the class.
	 * 	
	 * Example of a feature segment of a GML. 
	 * 
	 * <gml:featureMember>
	 * 	<ms:my_polygons gml:id="my_polygons.624">
	 * 		<gml:boundedBy>
	 * 			<gml:Envelope srsName="epsg:4326">
	 * 				<gml:lowerCorner>39.700000 29.400000</gml:lowerCorner>
	 * 				<gml:upperCorner>46.400000 35.400000</gml:upperCorner>
	 * 			</gml:Envelope>
	 * 		</gml:boundedBy>
	 * 		<ms:the_geom>
	 * 			<gml:MultiSurface srsName="epsg:4326">
	 * 				<gml:surfaceMembers>
	 * 					<gml:Polygon>
	 * 						<gml:exterior>
	 * 							<gml:LinearRing>
	 * 								<gml:posList srsDimension="2">
	 * 									43.200000 35.400000 
	 * 									46.400000 31.700000 
	 * 									44.100000 31.000000 
	 * 									41.700000 29.400000 
	 * 									39.700000 31.400000 
	 * 									43.300000 32.300000 
	 * 									43.200000 35.400000 
	 * 								</gml:posList>
	 * 							</gml:LinearRing>
	 * 						</gml:exterior>
	 * 					</gml:Polygon>
	 * 				</gml:surfaceMembers>
	 * 			</gml:MultiSurface>
	 * 		</ms:the_geom>
	 * 		<ms:oid>16752039</ms:oid>
	 * 		<ms:gid>624</ms:gid>
	 * 		<ms:name>inter_08</ms:name>
	 * 		<ms:angle/>
	 * 		<ms:annotation/>
	 * 		<ms:style>3</ms:style>
	 * 		</ms:my_polygons>
	 * 	</gml:featureMember>
	 * 
	 * @return void
	 * @param $domNode DOMNodeObject the feature tag of the GML 
	 * 								(<gml:featureMember> in the above example)
	 */
	protected function parseFeature($domNode, $feature, $wfsConf, $gmlBoundedBySrs, $geometryColumnName=false) {
		if ($geometryColumnName == false) {
			//get name by information from wfs_conf
			$geomFeaturetypeElement = $wfsConf->getGeometryColumnName();
		} else {
			$geomFeaturetypeElement = $geometryColumnName;
		}
		$feature->fid = $domNode->getAttribute("gml:id");
		
		$currentSibling = $domNode->firstChild;
		
		while ($currentSibling) {
		
			$name = $currentSibling->nodeName;
			$value = $currentSibling->nodeValue;

			$namespace = $this->findNameSpace($name);
			$ns = $namespace['ns'];
			$columnName = $namespace['value'];
			$isGeomColumn = ($geomFeaturetypeElement == null || $columnName == $geomFeaturetypeElement);
			//$e = new mb_exception("classes/class_gml3_factory.php: current column name: ".$columnName." - compare to geomname: ". $geomFeaturetypeElement);			
			// check if this node is a geometry node.
			// however, even if it is a property node, 
			// it has a child node, the text node!
			// So we might need to do something more 
			// sophisticated here...
			if ($currentSibling->hasChildNodes()){
				$geomNode = self::findFirstGeometryElement($currentSibling);
				$canParseAsGeometry = (
					$isGeomColumn ||
					($geomNode instanceof DOMNode && ($feature->geometry === false || $feature->geometry === null))
				);
				if (!$canParseAsGeometry) {
					$feature->properties[$columnName] = $value;
					$currentSibling = $currentSibling->nextSibling;
					continue;
				}
				if (!($geomNode instanceof DOMNode)) {
					$feature->properties[$columnName] = $value;
					$currentSibling = $currentSibling->nextSibling;
					continue;
				}
				$geomType = self::getNodeLocalName($geomNode);
				$srs = self::getSrsFromGeometryContext($geomNode, $gmlBoundedBySrs);
//$e = new mb_exception("classes/class_gml_3_factory: found srs: ".$srs);				
				//if srsName of featureMember is empty, use the srsName of node //gml:boundedBy/gml:Envelope
				if($srs == "") {
					$srs = $gmlBoundedBySrs;
				}
				switch ($geomType) {
					case "Polygon" :// untested!
						$feature->geometry = self::parsePolygon($geomNode);
						if ($feature->geometry->isEmpty()) {
							$feature->geometry = Gml_2_Factory::parsePolygon($geomNode);
						}
						$feature->geometry->srs = $srs;
						break;
					case "LineString" :// untested!
						$feature->geometry = self::parseLine($geomNode);
						if ($feature->geometry->isEmpty()) {
							$feature->geometry = Gml_2_Factory::parseLine($geomNode);
						}
						$feature->geometry->srs = $srs;
						break;
					case "Curve" :
						$feature->geometry = self::parseCurve($geomNode);
						if ($feature->geometry->isEmpty()) {
							$feature->geometry = Gml_2_Factory::parseLine($geomNode);
						}
						$feature->geometry->srs = $srs;
						break;
					case "Point" :
						$feature->geometry = self::parsePoint($geomNode);
						if ($feature->geometry->isEmpty()) {
							$feature->geometry = Gml_2_Factory::parsePoint($geomNode);
						}
						$feature->geometry->srs = $srs;
						break;
					case "MultiPoint" :
						$feature->geometry = self::parseMultiPoint($geomNode);
						if ($feature->geometry->isEmpty()) {
							$feature->geometry = Gml_2_Factory::parseMultiPoint($geomNode);
						}
						$feature->geometry->srs = $srs;
						break;
					case "MultiLineString" :
						new mb_exception("found multilinestring");
						$feature->geometry = self::parseMultiLine($geomNode);
						if ($feature->geometry->isEmpty()) {
							$feature->geometry = Gml_2_Factory::parseMultiLine($geomNode);
						}
						$feature->geometry->srs = $srs;
						break;
					case "MultiCurve" :
						$feature->geometry = self::parseMultiCurve($geomNode);
						if ($feature->geometry->isEmpty()) {
							$feature->geometry = Gml_2_Factory::parseMultiLine($geomNode);
						}
						$feature->geometry->srs = $srs;
						break;
					case "MultiSurface" :
					case "MultiPolygon" : 
						$feature->geometry = self::parseMultiPolygon($geomNode);
						if ($feature->geometry->isEmpty()) {
							$feature->geometry = Gml_2_Factory::parseMultiPolygon($geomNode);
						}
						$feature->geometry->srs = $srs;
						break;
					default:
						$feature->properties[$columnName] = $value;
						break;
				}
			} 
			else {
				if ($currentSibling->hasChildNodes() && $currentSibling->firstChild instanceof DOMText) {
					$feature->properties[$columnName] = $value;
				}
			}
			
			$currentSibling = $currentSibling->nextSibling;
		}
	}
	
	
}
?>
