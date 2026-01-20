<?php
# WFS 2.0 Fallback Handler
# Provides fallback mechanisms for broken WFS services (e.g., HALE with DescribeFeatureType bugs)
# Used by both WFS Factory (during registration) and Client (during filtering)

require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__)."/class_connector.php");

class Wfs_2_0_Fallback {
    
    /**
     * Get attributes from GetFeature response when DescribeFeatureType fails
     * Maps attribute names to types from DescribeFeatureType schema
     * 
     * @param string $getFeatureUrl WFS GetFeature endpoint
     * @param string $describeFeatureTypeUrl WFS DescribeFeatureType endpoint
     * @param string $featureTypeName Feature type name (e.g., "ft2:Schulbezirke")
     * @param mixed $auth Authentication info (optional)
     * @return array Array of objects with ->name and ->type properties
     */
    public static function getAttributesFromGetFeature($getFeatureUrl, $describeFeatureTypeUrl, $featureTypeName, $auth = false) {
        try {
            // Build GetFeature request
            $url = $getFeatureUrl . 
                self::getConjunctionCharacter($getFeatureUrl) .
                "SERVICE=WFS&VERSION=2.0.0&REQUEST=GetFeature&" .
                "TYPENAME=" . urlencode($featureTypeName) . 
                "&count=1&outputFormat=" . urlencode("application/geo+json");
            
            // Fetch GeoJSON
            $geoJsonResponse = self::httpGet($url, $auth);
            
            $geoJsonArray = json_decode($geoJsonResponse, true);
            
            if (!$geoJsonArray) {
                return array();
            }
            
            if (!isset($geoJsonArray['features']) || count($geoJsonArray['features']) === 0) {
                return array();
            }
            
            $firstFeature = $geoJsonArray['features'][0];
            if (!isset($firstFeature['properties'])) {
                return array();
            }
            
            $attributeNames = array_keys($firstFeature['properties']);
            
            // Get type mapping from DescribeFeatureType
            $typeMapping = self::extractTypeMapping($describeFeatureTypeUrl, $auth);
            
            // Build result with mapped types
            $result = array();
            foreach ($attributeNames as $attrName) {
                $obj = new stdClass();
                $obj->name = $attrName;
                $obj->type = $typeMapping[$attrName] ?? 'string';
                $result[] = $obj;
            }
            
            return $result;
            
        } catch (Exception $ex) {
            return array();
        }
    }
    
    /**
     * Extract type mapping from DescribeFeatureType response (all FeatureTypes)
     * Parses all complexTypes and builds attribute name -> type mapping
     * 
     * @param string $describeFeatureTypeUrl WFS DescribeFeatureType endpoint
     * @param mixed $auth Authentication info (optional)
     * @return array Mapping: attribute name => type (e.g., ["DSTNR" => "decimal", "NAME" => "string"])
     */
    private static function extractTypeMapping($describeFeatureTypeUrl, $auth = false) {
        try {
            // Fetch DescribeFeatureType without TYPENAME filter
            $url = $describeFeatureTypeUrl . 
                self::getConjunctionCharacter($describeFeatureTypeUrl) .
                "SERVICE=WFS&VERSION=2.0.0&REQUEST=DescribeFeatureType";
            
            $describeXml = self::httpGet($url, $auth);
            
            if (!$describeXml) {
                return array();
            }
            
            $doc = new DOMDocument();
            $doc->loadXML($describeXml);
            $xpath = new DOMXPath($doc);
            $xpath->registerNamespace("xs", "http://www.w3.org/2001/XMLSchema");
            
            $typeMapping = array();
            
            // Extract types from all complexTypes (including anonymous ones)
            $allComplexTypes = $xpath->query("//xs:complexType");
            
            foreach ($allComplexTypes as $complexType) {
                $elements = $xpath->query(".//xs:element", $complexType);
                foreach ($elements as $element) {
                    $attrName = $element->getAttribute("name");
                    $attrType = $element->getAttribute("type");
                    
                    if ($attrName && $attrType && !isset($typeMapping[$attrName])) {
                        // Remove namespace prefix from type
                        $typeParts = explode(":", $attrType);
                        $typeMapping[$attrName] = end($typeParts);
                    }
                }
            }
            
            return $typeMapping;
            
        } catch (Exception $ex) {
            return array();
        }
    }
    
    /**
     * Make HTTP GET request using connector class
     * 
     * @param string $url The URL to fetch
     * @param mixed $auth Authentication info (optional)
     * @return string|false Response content or false on error
     */
    private static function httpGet($url, $auth = false) {
        try {
            $conn = new connector();
            return $conn->load($url, $auth);
        } catch (Exception $ex) {
            return false;
        }
    }
    
    /**
     * Helper: Get conjunction character for URL (? or &)
     */
    private static function getConjunctionCharacter($url) {
        return (strpos($url, '?') === false) ? '?' : '&';
    }
}
?>
