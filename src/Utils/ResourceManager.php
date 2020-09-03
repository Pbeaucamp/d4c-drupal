<?php

namespace Drupal\ckan_admin\Utils;
use Drupal\ckan_admin\Utils\Api;
use ZipArchive;
use Drupal\ckan_admin\Utils\Logger;


class ResourceManager {

	function manageFiles($id, $url, $filesDirectory) {
		Logger::logMessage("Managing files in '" . $filesDirectory . "'");

		// $files = scandir($filesDirectory);
		$files = $this->getDirContents($filesDirectory);
		foreach($files as $file) {
			$csv = $this->manageFile($id, $url, $file);
			if ($csv != null) {
				return $csv;
			}
		}
	}

	function getDirContents($dir, &$results = array()) {
		$files = scandir($dir);
	
		foreach ($files as $key => $value) {
			$path = realpath($dir . DIRECTORY_SEPARATOR . $value);
			if (!is_dir($path)) {
				$results[] = $path;
			}
			else if ($value != "." && $value != "..") {
				$this->getDirContents($path, $results);
				$results[] = $path;
			}
		}
	
		return $results;
	}

	function manageFile($id, $url, $filePath) {
		Logger::logMessage("Managing file '" . $filePath . "'");

		try {
			$type = $this->extractFormat($filePath);
		} catch (Exception $e) {
			Logger::logMessage("Impossible de récupérer le format du fichier (" . $e->getMessage() . ")");
		}
		Logger::logMessage("Found format " . $type);
		if ($type == 'csv') {

		}
		else if ($type == 'zip') {
			return $this->manageZip($id, $url, $filePath);
		}
		else if ($type == 'json' || $type == 'geojson' || $type == 'kml' || $type == 'shp') {
			return $this->manageGeoFiles($type, $id, $url, $filePath);
		}
		else {
			Logger::logMessage("We do not process the file '" . $filePath . "'");
			return null;
		}
	}

	/**
	 * Extract the format of a file based on the filename
	 * and assign a type to help managing the file afterward
	 * 
	 * For now files can be of type jpg, jpeg, gif, png, txt, doc, xls, pdf, ppt, pps, odt, ods, odp, csv, json, xls, xlsx, geojson, zip
	 * 
	 */
	function extractFormat($filePath) {
		$format = pathinfo($filePath, PATHINFO_EXTENSION);
		if (strcasecmp($format , 'jpg') == 0) {
			return 'jpg';
		}
		else if (strcasecmp($format , 'jpeg') == 0) {
			return 'jpeg';
		}
		else if (strcasecmp($format , 'gif') == 0) {
			return 'gif';
		}
		else if (strcasecmp($format , 'png') == 0) {
			return 'png';
		}
		else if (strcasecmp($format , 'txt') == 0) {
			return 'txt';
		}
		else if (strcasecmp($format , 'doc') == 0) {
			return 'doc';
		}
		else if (strcasecmp($format , 'xls') == 0) {
			return 'xls';
		}
		else if (strcasecmp($format , 'pdf') == 0) {
			return 'pdf';
		}
		else if (strcasecmp($format , 'ppt') == 0) {
			return 'ppt';
		}
		else if (strcasecmp($format , 'pps') == 0) {
			return 'pps';
		}
		else if (strcasecmp($format , 'odt') == 0) {
			return 'odt';
		}
		else if (strcasecmp($format , 'ods') == 0) {
			return 'ods';
		}
		else if (strcasecmp($format , 'odp') == 0) {
			return 'odp';
		}
		else if (strcasecmp($format , 'csv') == 0) {
			return 'csv';
		}
		else if (strcasecmp($format , 'json') == 0) {
			return 'json';
		}
		else if (strcasecmp($format , 'geojson') == 0) {
			return 'geojson';
		}
		else if (strcasecmp($format , 'xls') == 0) {
			return 'xls';
		}
		else if (strcasecmp($format , 'xlsx') == 0) {
			return 'xlsx';
		}
		else if (strcasecmp($format , 'zip') == 0) {
			return 'zip';
		}
		return $format;
	}
	
	function manageZip($id, $url, $filePath) {
		Logger::logMessage("Manage zip file");
		// $path = pathinfo(realpath($filePath), PATHINFO_DIRNAME);

		$outputDirectory = '/home/user-client/drupal-d4c/sites/default/files/dataset/zip_extraction_'.uniqid().'';

		$zip = new ZipArchive;
		$res = $zip->open($filePath);
		if ($res === TRUE) {
			// extract it to the path we determined above
			$zip->extractTo($outputDirectory);
			$zip->close();

			return $this->manageFiles($id, $url, $outputDirectory);
		}
		else {
			throw new Exception('Le fichier ne peut pas être extrait.');
		}
	}

	/**
	 * Generate a geojson file (if it does not exist) and a CSV file from various type of Geo format
	 * 
	 * $type can be geojson, json, kml and shp
	 * $id of the file
	 * $url of the file
	 * 
	 */
	function manageGeoFiles($type, $id, $url, $filePath) {
		Logger::logMessage("Manage " . $type . " file");

		Logger::logMessage("Retrieving file '" . $url + "'");
		$fileContent = Query::callSolrServer($url);

		if ($type == 'geojson' || $type == 'json'){
			$csv = $this->buildCSVFromGeojson($fileContent);
		}
		else if ($type == 'kml' || $type == 'shp') {
			//We create a tmp file in which we write the result and an output file to convert
			// $pathInput = tempnam(sys_get_temp_dir(), 'input_convert_geo_file_');
			// $fileInput = fopen($pathInput, 'w');
			// fwrite($fileInput, $fileContent);
			// fclose($fileInput);

			$scriptPath = '/home/user-client/drupal-d4c/modules/ckan_admin/src/Utils/convert_geo_files_ogr2ogr.sh';

			$typeConvert = 'GEOJSON';
			
			$rootJson='/home/user-client/drupal-d4c/sites/default/files/dataset/gen_'.uniqid().'.geojson';
			$command = $scriptPath." 2>&1 '" . $typeConvert . "' " . $rootJson . " " . $filePath . "";
			$message = shell_exec($command);
			$json = file_get_contents ($rootJson);

			$csv = $this->buildCSVFromGeojson($json);
			unlink ($rootJson);
		}
		else {
			throw new Exception('Le type de fichier ' . $type . ' is not supported.');
		}

		// $outputCsvPath = '/home/user-client/drupal-d4c/sites/default/files/dataset/gen_'.uniqid().'.csv';
		// file_put_contents($outputCsvPath, $csv);

		Logger::logMessage("Returning CSV");
		return $csv;
	}
	
	function buildCSVFromGeojson($json) {
		if($json == null || count($json) == 0){
			return "";
		}
		
		Logger::logMessage("Creating CSV from GeoJson");
		
		// If passed a string, turn it into an array
		if (is_array($json) === false) {
			//$json = utf8_encode($json);
			//$json = Export::convert_bad_characters($json);
			$json = json_decode($json, true, 512, JSON_UNESCAPED_UNICODE);
			//$json = json_decode($json, true);
		}
		
		if($json["type"] != "FeatureCollection"){
			return "";
		}
		//construction du csv
		$cols = array();
		$colNames = array();
		$data_csv = array();

		//Previously we were getting only the columns for the first feature but we could miss a lot of informations
		//We now go through all features but we have to check if it not too much time consuming
		$hasShapes = false;
		$index = 0;
		foreach($json["features"] as $feat) {
			foreach($feat["properties"] as $key => $val){

				//We check if the key already exist
				if (!in_array($key, $cols)) {
					Logger::logMessage("Found column " . $key);

					$cols[] = $key;
					$colNames[] = $this->clearGeoProperties($key, $index);
					$index++;
				}
			}
			if ($feat["geometry"]["type"] != "Point") {
				$hasShapes = true;
			}
		}
		if ($hasShapes) {
			$cols[] = "coordinates";
			$cols[] = "geo_shape";
			$colNames[] = "coordinates";
			$colNames[] = "geo_shape";
		}
		else {
			$cols[] = "geo_point_2d";
			$colNames[] = "geo_point_2d";
		}
		
		$rows = array();
		$colsTypes = array();
		foreach($json["features"] as $feat){
			$row = array();
			foreach($cols as $col){
				if($col == "geo_point_2d"){
					$str = json_encode($feat["geometry"]["coordinates"]);
					preg_match('/\[([-]?[\d|.]+),([-]?[\d|.]+)/i', $str, $match);
					$val = '"'.$match[2] .",". $match[1].'"';
					$row[] = $val;
				} else if($col == "geo_shape") {
					$str = json_encode($feat["geometry"]);
					preg_match('/\[([-]?[\d|.]+),([-]?[\d|.]+)/i', $str, $match);
					$coord = '"' . $match[2] . "," . $match[1] . '"';

					//We replace " by "" to escape them
					$str = str_replace('"', "\"\"", $str);

					$row[] = $coord;
					$row[] = '"' . $str . '"';
				} else if($col == "coordinates"){
					continue;
				}	
				else {
					$value = $feat["properties"][$col];
					if((isset($colsTypes[$col]) && $colsTypes[$col] == "text") || !$this->isNumericColumn($json,$col)){
						//We replace " by "" to escape them
						$value = str_replace('"', "\"\"", $value);

						$row[] = '"' . $value . '"';
						if(!isset($colsTypes[$col])){
							$colsTypes[$col] = "text";
						}
					} else {
						$row[] = $value;
						if(!isset($colsTypes[$col])){
							$colsTypes[$col] = "float";
						}
					}
				}
			}
			
			$rows[] = $row;
		}
		
		foreach($rows as &$row){
			if(count($row) < count($cols)){
				$row = array_pad($row, count($cols), "");
			}
			$row = implode($row, ",");
		}
		
		$data_csv = strtolower(implode($colNames, ","));
		array_unshift($rows, $data_csv);
		error_log("count ". (count($rows)));
		$res = implode($rows, "\n");
		return $res;
	}
	
	function isNumericColumn($json, $colName) {
		
		for($i=0; $i< 100; $i++){
			$val = $json["features"][$i]["properties"][$col];
			if( !is_numeric ($val)){
				return false;
			} 
		}
		return true;
	}

	function clearGeoProperties($colName, $index) {
		if(preg_match("/geo_point|coordin|coordon|geopoint|geoPoint|pav_positiont2d|geoloc|wgs84|equgpsy_x|geoban|codegeo|geometry/i",$colName)){
			return "colonne_renomme_" . $index;
		}
		else {
			return $colName;
		}
	}
}