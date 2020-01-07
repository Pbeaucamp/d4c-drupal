<?php

namespace Drupal\ckan_admin\Utils;


ini_set('memory_limit', '2048M'); // or you could use 1G

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
		$cpt=0;
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
			$record['recordid'] = $ids[$cpt];//$ids[array_search($v,$records)];

			$data_array[] = $record;
			$cpt++;		
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
}