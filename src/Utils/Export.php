<?php

namespace Drupal\ckan_admin\Utils;
use Drupal\ckan_admin\Utils\Logger;


ini_set('memory_limit', '2048M'); // or you could use 1G
ini_set('max_execution_time', 2000);

class Export{

	static function getExport( $format, $fieldGeometries, $fieldCoordinates, $records, $query_params, $ids) {
		if ($format == 'json') {
			return Export::getJsonExport($fieldGeometries, $fieldCoordinates, $records, $query_params, $ids);
		} else if ($format == 'geojson' || $format == 'kml' || $format == 'shp') {
			return Export::getGeoJsONExport($fieldGeometries, $fieldCoordinates, $records);
		} else {
			return null;
		}
	}

	static function getJsonExport($fieldGeometries, $fieldCoordinates, $records, $query_params, $ids) {
		$data_array = array();
		//echo count($records);
		foreach ($records as $v) {
			$record = array();
			// $fields = explode(',', $query_params['fields']);
			//$fiedsArray = array();
			if($fieldCoordinates != ''){
			//foreach ($v as $f => $fk) {
			//	if($f == $fieldCoordinates) $fk = array_reverse (array_map('floatval', explode(',', $fk)));
			//	$v[$f] = $fk;
			//}
				$v[$fieldCoordinates] = array_reverse(array_map('floatval', explode(',', $v[$fieldCoordinates])));
				$v[$fieldGeometries] = json_decode($v[$fieldGeometries]);
			}

			$record['fields'] = $v;//$fiedsArray;
			if($fieldGeometries != ''){
				$record['geometry'] = $v[$fieldGeometries];
			}
			else if($fieldCoordinates != ''){
				$geom = array();
				$geom['type'] = "Point";
				$geom['coordinates'] = $v[$fieldCoordinates];//array_map('floatval', explode(',', $v[$fieldCoordinates]));
				$record['geometry'] = $geom;
			}

			$record['datasetid'] = $query_params['resource_id'];
			//The following mix the ID if the records are not get in order so we add it in the result previously
			$record['recordid'] = $v['_id'];//$ids[array_search($v,$records)];
			// $record['recordid'] = $ids[$cpt];//$ids[array_search($v,$records)];

			$data_array[] = $record;	
		}

		return $data_array;
	}
	
	static function getGeoJsonExport($fieldGeometries, $fieldCoordinates, $records) {
		$result = array();
		$result['type'] = "FeatureCollection";

		$data_array = array();
		foreach ($records as $v) {
			$record = array();
			$record['type'] = "Feature";

			if($fieldCoordinates != ''){
				$v[$fieldCoordinates] = array_reverse(array_map('floatval', explode(',', $v[$fieldCoordinates])));
			}

			$record['properties'] = $v;
			if($fieldGeometries != ''){
				$record['geometry'] = json_decode($v[$fieldGeometries]);
			}
			else if($fieldCoordinates != ''){
				$geom = array();
				$geom['type'] = "Point";
				$geom['coordinates'] = $v[$fieldCoordinates];//array_map('floatval', explode(',', $v[$fieldCoordinates]));
				$record['geometry'] = $geom;
			}
			$data_array[] = $record;
		}
		$result['features'] = $data_array;

		return $result;
    }

	static function getCSVfromJson($json) {
		if($json == null || count($json) == 0){
			return "";
		}
		
		// If passed a string, turn it into an array
		if (is_array($json) === false) {
			$json = json_decode($json, true);
		}
		
		
		$boolEchoCsv = true;
		$strTempFile = 'csvOutput' . date("U") . ".csv";
		$f = fopen($strTempFile,"w+");
		
		$firstLineKeys = false;
		foreach ($json as $line) {
			unset($line["extras"]);
			unset($line["resources"]);
			if (empty($firstLineKeys)) {
				$firstLineKeys = array_keys($line);
				fputcsv($f, $firstLineKeys);
				$firstLineKeys = array_flip($firstLineKeys);
			}
			foreach($line as $key => $val){
				if(is_array($val)){
					if(count($val) > 0){
						$line[$key] = json_encode($val);
					} else {
						$line[$key] = "";
					}
				} else if(is_string($val) && (strpos($val, "\n") !== false || strpos($val, "\r") !== false)){
					//error_log("cot : ". $val);
					//$line[$key] = 'BB' . $val . 'Bb';
					$line[$key] = json_encode($val);
				}
			}
			// Using array_merge is important to maintain the order of keys acording to the first element
			fputcsv($f, array_merge($firstLineKeys, $line));
		}
		fclose($f);
		
		$res = "";
		if (($handle = fopen($strTempFile, "r")) !== FALSE) {
			while (($data = fgetcsv($handle)) !== FALSE) {
				$res .= implode(";",$data);
				$res .= "\n";
			}
			fclose($handle);
		}
		
		// Delete the temp file
		unlink($strTempFile);
		
		return $res;
	}
	
	static function createCSVfromGeoJSON($json) {
		$start = microtime(true);
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

		// $sample = $json["features"][0];
		// $index = 0;
		// foreach($sample["properties"] as $key => $val){
		// 	$cols[] = $key;
		// 	$colNames[] = Export::clearGeoProperties($key, $index);
		// 	$index++;
		// }
		// if ($sample["geometry"]["type"] == "Point") {
		// 	$cols[] = "geo_point_2d";
		// 	$colNames[] = "geo_point_2d";
		// }
		// else {
		// 	$cols[] = "coordinates";
		// 	$cols[] = "geo_shape";
		// 	$colNames[] = "coordinates";
		// 	$colNames[] = "geo_shape";
		// }

		//Previously we were getting only the columns for the first feature but we could miss a lot of informations
		//We now go through all features but we have to check if it not too much time consuming
		$hasShapes = false;
		$index = 0;
		foreach($json["features"] as $feat) {
			foreach($feat["properties"] as $key => $val){

				//We check if the key already exist
				if (!in_array($key, $cols)) {

					// Logger::logMessage("Checking column " . json_encode($cols));
					Logger::logMessage("Found column " . $key);

					$cols[] = $key;
					$colNames[] = Export::clearGeoProperties($key, $index);
					$index++;
				}
			}
			// Logger::logMessage("Found geometry of type " . $feat["geometry"]["type"]);
			if ($feat["geometry"]["type"] != "Point") {
				// Logger::logMessage("Found geometry of type shape");
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
		
		// $crs = $json["crs"]["properties"]["name"];
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
					if((isset($colsTypes[$col]) && $colsTypes[$col] == "text") || !Export::isNumericColumn($json,$col)){
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
		//$data_csv = array_merge($data_csv, $rows);
		array_unshift($rows, $data_csv);
		error_log("count ". (count($rows)));
		//$res = utf8_encode(implode($data_csv, "\n"));
		$res = implode($rows, "\n");
		//error_log("eeee ".mb_detect_encoding($res, 'CP1257,ASCII,ISO-8859-15,UTF-8'));
		//$res = utf8_decode($res);
		//$res = Export::convert_bad_characters($res);
		//$res = iconv("UTF-8", "Windows-1252//TRANSLIT", $res);
		return $res;
	}

	static function clearGeoProperties($colName, $index) {
		if(preg_match("/geo_point|coordin|coordon|geopoint|geoPoint|pav_positiont2d|geoloc|wgs84|equgpsy_x|geoban|codegeo|geometry/i",$colName)){
			return "colonne_renomme_" . $index;
		}
		else {
			return $colName;
		}
	}
	
	static function isNumericColumn($json, $colName) {
		
		for($i=0; $i< 100; $i++){
			$val = $json["features"][$i]["properties"][$col];
			if( !is_numeric ($val)){
				return false;
			} 
		}
		return true;
	}
	
	static function convert_bad_characters($string){
		$new2old = array(
			 'á' => '/Ã¡/',
			 
			 'À' => '/Ã€/',
			 'ä' => '/Ã¤/',
			 'Ä' => '/Ã„/',
			 'ã' => '/Ã£/',
			 'å' => '/Ã¥/',
			 'Å' => '/Ã…/',
			 'æ' => '/Ã¦/',
			 'Æ' => '/Ã†/',
			 'ç' => '/Ã§/',
			 'Ç' => '/Ã‡/',
			 'é' => '/Ã©/',
			 'É' => '/Ã‰/',
			 'è' => '/Ã¨/',
			 'È' => '/Ãˆ/',
			 'ê' => '/Ãª/',
			 'Ê' => '/ÃŠ/',
			 'ë' => '/Ã«/',
			 'Ë' => '/Ã‹/',
			 'í' => '/Ã-­­/',
			 'Í' => '/Ã/',
			 'ì' => '/Ã¬/',
			 'Ì' => '/ÃŒ/',
			 'î' => '/Ã®/',
			 'Î' => '/ÃŽ/',
			 'ï' => '/Ã¯/',
			 'Ï' => '/Ã/',
			 'ñ' => '/Ã±/',
			 'Ñ' => '/Ã‘/',
			 'ó' => '/Ã³/',
			 'Ó' => '/Ã“/',
			 'ò' => '/Ã²/',
			 'Ò' => '/Ã’/',
			 'ô' => '/Ã´/',
			 'Ô' => '/Ã”/',
			 'ö' => '/Ã¶/',
			 'Ö' => '/Ã–/',
			 'õ' => '/Ãµ/',
			 'Õ' => '/Ã•/',
			 'ø' => '/Ã¸/',
			 'Ø' => '/Ã˜/',
			 'œ' => '/Å“/',
			 'Œ' => '/Å’/',
			 'ß' => '/ÃŸ/',
			 'ú' => '/Ãº/',
			 'Ú' => '/Ãš/',
			 'ù' => '/Ã¹/',
			 'Ù' => '/Ã™/',
			 'û' => '/Ã»/',
			 'Û' => '/Ã›/',
			 'ü' => '/Ã¼/',
			 'Ü' => '/Ãœ/',
			 '€' => '/â‚¬/',
			 '’' => '/â€™/',
			 '‚' => '/â€š/',
			 'ƒ' => '/Æ’/',
			 '„' => '/â€ž/',
			 '…' => '/â€¦/',
			 '‡' => '/â€¡/',
			 'ˆ' => '/Ë†/',
			 '‰' => '/â€°/',
			 'Š' => '/Å /',
			 '‹' => '/â€¹/',
			 'Ž' => '/Å½/',
			 '‘' => '/â€˜/',
			 '“' => '/â€œ/',
			 '•' => '/â€¢/',
			 '–' => '/â€“/',
			 '—' => '/â€”/',
			 '˜' => '/Ëœ/',
			 '™' => '/â„¢/',
			 'š' => '/Å¡/',
			 '›' => '/â€º/',
			 'ž' => '/Å¾/',
			 'Ÿ' => '/Å¸/',
			 '¡' => '/Â¡/',
			 '¢' => '/Â¢/',
			 '£' => '/Â£/',
			 '¤' => '/Â¤/',
			 '¥' => '/Â¥/',
			 '¦' => '/Â¦/',
			 '§' => '/Â§/',
			 '¨' => '/Â¨/',
			 '©' => '/Â©/',
			 'ª' => '/Âª/',
			 '«' => '/Â«/',
			 '¬' => '/Â¬/',
			 '®' => '/Â®/',
			 '¯' => '/Â¯/',
			 '°' => '/Â°/',
			 '±' => '/Â±/',
			 '²' => '/Â²/',
			 '³' => '/Â³/',
			 '´' => '/Â´/',
			 'µ' => '/Âµ/',
			 '¶' => '/Â¶/',
			 '·' => '/Â·/',
			 '¸' => '/Â¸/',
			 '¹' => '/Â¹/',
			 'º' => '/Âº/',
			 '»' => '/Â»/',
			 '¼' => '/Â¼/',
			 '½' => '/Â½/',
			 '¾' => '/Â¾/',
			 '¿' => '/Â¿/',
			 'à' => '/Ã /',
			 '†' => '/â€ /',
			 '”' => '/â€/',
			 'Á' => '/Ã/',
			 'â' => '/Ã¢/',
			 'Â' => '/Ã‚/',
			 'Ã' => '/Ãƒ/',
			);
			 
			foreach( $new2old as $key => $value ) {
			   $new[] = $key;
			   $old[] = $value;
			}
			error_log("convert");
			$string_new = str_replace( $old, $new, $string );
			return $string_new;
	}
}