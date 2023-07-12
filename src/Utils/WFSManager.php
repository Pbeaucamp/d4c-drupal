<?php

namespace Drupal\ckan_admin\Utils;

use DOMDocument;
use DOMXPath;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;

class WFSManager {
  
    const KEY_TYPE_NAME = "{{name}}";
    const KEY_MAX_FEATURES = "{{maxFeatures}}";
    const KEY_PROJECTION = "{{projection}}";
    const KEY_FORMAT = "{{format}}";
    
    const WFS_PARAM_SERVICE = "service=WFS";
    const WFS_PARAM_VERSION_1_0_0 = "version=1.0.0";
    const WFS_PARAM_VERSION_1_1_0 = "version=1.1.0";
    const WFS_PARAM_REQUEST_GET_FEATURE = "request=GetFeature";
    const WFS_PARAM_REQUEST_GET_CAPABILITIES = "request=GetCapabilities";
    const WFS_PARAM_TYPE_NAME = "typeName=" . self::KEY_TYPE_NAME;
    const WFS_PARAM_MAX_FEATURES = "maxFeatures=" . self::KEY_MAX_FEATURES;
    const WFS_PARAM_OUTPUT_FORMAT = "outputFormat=" . self::KEY_FORMAT;
    const WFS_PARAM_PROJECTION = "srsName=urn:ogc:def:crs:EPSG::" . self::KEY_PROJECTION;
    
    const DEFAULT_PROJECTION = 4326;
  
    const FORMAT_JSON = ["application/json"];
    const FORMAT_GEO_JSON = ["application/json; subtype=geojson"];
    const FORMAT_GML = ["text/xml; subtype=gml/3.1.1", "application/gml+xml; version=3.2", "gml3"];
    
    public static function retrieveJSONFromWFS($fileInputPath, $urlWFS, $layerName, $format) {
        $wfsUrl = self::buildWFSUrl($urlWFS, $layerName, $format);
        if (empty($wfsUrl)) {
            throw new \Exception("Unable to build WFS URL from resource");
        }

        $file = fopen($fileInputPath, 'w');

        if ($file !== false) {
            $handle = fopen($wfsUrl, 'r');

            if ($handle !== false) {
                while (!feof($handle)) {
                    $chunk = fread($handle, 8192);
                    fwrite($file, $chunk);
                }

                fclose($handle);
                fclose($file);
                return $fileInputPath;
            }

            fclose($file);
        }

        return null;
    }
  
    public static function getSelectedFormat($urlWFS) {
        try {
            $supportGML = false;
            $availableFormats = self::getWFSAvailableFormats($urlWFS);
            if (!empty($availableFormats)) {
                foreach ($availableFormats as $format) {
                    if (self::isInArray(self::FORMAT_JSON, strtolower($format))) {
                        return "application%2Fjson";
                    }
                    elseif (self::isInArray(self::FORMAT_GEO_JSON, strtolower($format))) {
                        return "application%2Fjson;%20subtype=geojson";
                    }
                    elseif (self::isInArray(self::FORMAT_GML, strtolower($format))) {
                        $supportGML = true;
                    }
                }
            }
            
            return $supportGML ? "GML3" : "application%2Fjson";
        } catch (\Exception $e) {
            \Drupal::logger('my_module')->error($e->getMessage());
            return "application%2Fjson";
        }
    }
  
    private static function buildWFSUrl($urlWFS, $layerName, $format) {
        $urlWFS = self::extractWFSUrl($urlWFS);
        $typeName = $layerName;
        $projection = strval(self::DEFAULT_PROJECTION);

        // Logger::logMessage("TRM - WFS URL 2 : " . $urlWFS);
        // Logger::logMessage("TRM - Layer name: " . $layerName);
        // Logger::logMessage("TRM - Format: " . $format);
        // Logger::logMessage("TRM - Projection: " . $projection);

        $hasParameters = strpos($urlWFS, "?") !== false;
        $url = $urlWFS;
        $url .= ($hasParameters ? "&" : "?") . self::WFS_PARAM_SERVICE;
        $url .= "&" . self::WFS_PARAM_VERSION_1_1_0;
        $url .= "&" . self::WFS_PARAM_REQUEST_GET_FEATURE;
        $url .= "&" . self::WFS_PARAM_TYPE_NAME;
        $url .= "&" . self::WFS_PARAM_MAX_FEATURES;
        $url .= "&" . self::WFS_PARAM_OUTPUT_FORMAT;
        $url .= "&" . self::WFS_PARAM_PROJECTION;
        
        $url = str_replace(self::KEY_TYPE_NAME, $typeName, $url);
        $url = str_replace(self::KEY_FORMAT, $format, $url);
        $url = str_replace("&maxFeatures=" . self::KEY_MAX_FEATURES, "", $url);
        $url = str_replace(self::KEY_PROJECTION, $projection, $url);

        Logger::logMessage("TRM - URL built: " . $url);

        return $url;
    }
  
    private static function getWFSAvailableFormats($urlWFS) {
        $wfsUrl = self::buildGetFeatureWFS($urlWFS);
        if (empty($wfsUrl)) {
            throw new \Exception("Unable to build WFS URL from resource");
        }
    
        $formats = [];
        try {
            $xml = file_get_contents($wfsUrl);
            $dom = new DOMDocument();
            $dom->loadXML($xml);
            
            $xpath = new DOMXPath($dom);
            $query = "//*[local-name() = 'OperationsMetadata']/*[local-name() = 'Operation'][@name=\"GetFeature\"]/*[local-name() = 'Parameter'][@name=\"outputFormat\"]/*[local-name() = 'Value']";
            $nodes = $xpath->query($query);
            
            foreach ($nodes as $node) {
                $formats[] = $node->nodeValue;
            }
        } catch (\Exception $e) {
            \Drupal::logger('my_module')->error($e->getMessage());
        }
    
        return $formats;
    }

    private static function buildGetFeatureWFS($urlWFS) {
        $wfsUrl = self::extractWFSUrl($urlWFS);
        $hasParameters = strpos($wfsUrl, "?") !== false;
        
        $url = $wfsUrl;
        $url .= ($hasParameters ? "&" : "?") . self::WFS_PARAM_SERVICE;
        $url .= "&" . self::WFS_PARAM_VERSION_1_1_0;
        $url .= "&" . self::WFS_PARAM_REQUEST_GET_CAPABILITIES;
        
        return $url;
    }
  
    private static function extractWFSUrl($url) {
        if (!empty($url)) {
            return self::removeWFSUnwantedParameters($url);
        }
        
        return null;
    }
  
    private static function removeWFSUnwantedParameters($url) {
        if (empty($url)) {
            return $url;
        }
    
        try {
            $parsedUrl = UrlHelper::parse($url);
            unset($parsedUrl['query']['service']);
            unset($parsedUrl['query']['SERVICE']);
            unset($parsedUrl['query']['request']);
            unset($parsedUrl['query']['REQUEST']);

            $newUrl = Url::fromUri($parsedUrl['path'], $parsedUrl['options'])
                ->setOption('query', $parsedUrl['query'])
                ->toString();

            return $newUrl;
        } catch (\Exception $e) {
            \Drupal::logger('my_module')->error($e->getMessage());
            return $url;
        }
    }
  
    private static function isInArray($array, $value) {
        if (!empty($array) && !empty($value)) {
            return in_array($value, $array);
        }
        return false;
    }
  
}