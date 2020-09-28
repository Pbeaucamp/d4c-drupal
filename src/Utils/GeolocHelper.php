<?php

namespace Drupal\ckan_admin\Utils;

use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\Logger;

class GeolocHelper {
    
    public function __construct(){
        $this->config = json_decode(file_get_contents(__DIR__ ."/../../config.json"));
		$this->urlCkan = $this->config->ckan->url;
    }

    function buildGeoloc($selectedDataset, $selectedResource, $selectedSeparator, $selectedEncoding, $buildGeolocType, $colCoordinate, 
        $coordinateSeparator, $onlyOneAddress, $colNum, $colStreet, $colAdress, $colPostalCode, $colCity, $colLat, $colLon, $uploadGeojson){
        # $command = '/usr/bin/java -jar ' . $pathUserClientData . '/bpm.geoloc.creator_1.0.0.jar 
        # g = 0 if we don't need it, 1 if we need to get geolocalisation from the API BAN, 2 if we need to merge two coordinate column
        # n = Node URL
        # np = Node path
        # d4c = URL to D4C
        # d = URL to CKAN
        # k = D4C API KEY
        # pid = Package Name
        # rid = Resource ID
        # rs = Resource separator (Default is ',')
        # re = Resource encoding (Default is 'UTF-8')
        # oa = True if the address is only in one column
        # coor = Coordinate column name
        # cs = Coordinate column separator (Default is ',')
		# num = Address number column name
		# rue = Address street column name
        # a = Address column name
        # p = Postal code column name
        # v = City column name
        # lat = Latitude column name
        # lon = Longitude column name
        # s = Minimum score to accept geolocalisation (Between 0 and 100) (Default is '60')
        # f = Temp file path
        # ug = Upload geojson

        
        $pathUserClient = '/home/user-client';
        $pathUserClientData = $pathUserClient . '/data';
        $geolocJar = 'bpm.geoloc.creator_1.0.0.jar';
        $nodeUrl = 'https://localhost:1337/';
        $nodePath = '/home/user-client/data/clusters';
        $minimumScore = '10';
        $pathTempFile = '/home/user-client/data/temp';


        $g = $buildGeolocType;
        $n = $nodeUrl;
        $np = $nodePath;
        $d4c = (isset($_SERVER['HTTPS']) ? "https" : "https") . "://$_SERVER[HTTP_HOST]";
        $d = $this->urlCkan;
        $k = $this->config->ckan->api_key;
        $pid = $selectedDataset;
        $rid = $selectedResource;
        $rs = $selectedSeparator;
        $re = $selectedEncoding;
        $s = $minimumScore;
        $f = $pathTempFile;
        $ug = $uploadGeojson;

		if ($buildGeolocType == '0') {
            $geolocParams = ' -coor "' . $colCoordinate . '" -cs "' . $coordinateSeparator . '"';
        }
		else if ($buildGeolocType == '1') {
            Logger::logMessage("Ony one adress =  " . $onlyOneAddress ."\r\n");
            Logger::logMessage("Col adress =  " . $colAdress ."\r\n");
            Logger::logMessage("Col num =  " . $colNum ."\r\n");
            Logger::logMessage("Col street =  " . $colStreet ."\r\n");
            Logger::logMessage("Col city =  " . $colCity ."\r\n");
            Logger::logMessage("Col postal code =  " . $colPostalCode ."\r\n");

            $geolocParams = ' -oa "' . $onlyOneAddress . '" -a "' . $colAdress . '"';
            if ($colNum != '' && $colNum != '----') {
                $geolocParams .= ' -num "' . $colNum . '"';
            }
            if ($colStreet != '' && $colStreet != '----') {
                $geolocParams .= ' -rue "' . $colStreet . '"';
            }
            if ($colCity != '' && $colCity != '----') {
                $geolocParams .= ' -v "' . $colCity . '"';
            }
            if ($onlyOneAddress == 'false' && $colPostalCode != '' && $colPostalCode != '----') {
                $geolocParams .= ' -p "' . $colPostalCode . '"';
            }
		}
		else if($buildGeolocType == '2') {
			$geolocParams = ' -lat "' . $colLat . '" -lon "' . $colLon . '"';
        }

        Logger::logMessage("Geoloc params =  " . $geolocParams ."\r\n");
        Logger::logMessage("D4C URL " . $d4c ."\r\n");

		$command = '/usr/bin/java -jar ' . $pathUserClientData . '/' . $geolocJar . ' -g "' . $g . '" -n "' . $n . '" -np "' . $np . '" -d4c "' . $d4c . '" -d "' . $d . '" -k "' . $k . '" -pid "' . $pid . '" -rid "' . $rid . '" -rs "' . $rs . '" -re "' . $re . '" ' . $geolocParams . ' -s "' . $s . '" -f "' . $f . '" -ug "' . $ug . '"';
        Logger::logMessage($command);

        $output = shell_exec($command);
        
        Logger::logMessage("Geoloc finish with result " . $output);

        if (strpos($output, 'GEOLOC END WITH SUCCESS') !== false) {
            Logger::logMessage("Geoloc success \r\n");
			// sleep(20);
			// $api = new Api();
            // $api->calculateVisualisations($selectedDataset);
            return "SUCCESS";
        }
        else {
            Logger::logMessage($output);
            return $output;
        }
        
        // if ($validOutput[count($validOutput)-1]=='defined.' && $validOutput[count($validOutput)-2]=='correctly' && $validOutput[count($validOutput)-3]=='not'){
        //     drupal_set_message($output, 'error');
        // }
        // else{
        //     drupal_set_message($output, 'status', false);
        // }
        // Logger::logMessage($validOutput);
        
        // if ($validOutput[count($validOutput)-1]=='defined.' && $validOutput[count($validOutput)-2]=='correctly' && $validOutput[count($validOutput)-3]=='not'){
        //     drupal_set_message($output, 'error');
        // }
        // else{
        //     drupal_set_message($output, 'status', false);
		// 	sleep(20);
		// 	$api = new Api();
		// 	$api->calculateVisualisations($selectedDataset);
        // }


		// return $response;
    }
    
}