<?php

namespace Drupal\ckan_admin\Utils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Drupal\ckan_admin\Utils\Export;
use ZipArchive;
use Drupal\file\Entity\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use \PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use \PhpOffice\PhpSpreadsheet\Writer\Csv;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\Style\CellAlignment;
use SplFileObject;
use finfo;
use Drupal\ckan_admin\Utils\Logger;



ini_set('memory_limit', '2048M'); // or you could use 1G
ini_set('max_execution_time', 200);

/*
 *
This file uses a library under MIT Licence :

ods-widgets -- https://github.com/opendatasoft/ods-widgets
Copyright (c) 2014 - Opendatasoft

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
 *
 */

class Api{
	
	//protected $config = \Drupal::config('ckan_admin.settings');
	protected $urlCkan;// = "http://192.168.2.223/";
	//protected $urlCkan = file_get_contents(__DIR__ ."/../../config.json");
	protected $config;
    //-------------- 
    
	public function __construct(){
        $this->config = json_decode(file_get_contents(__DIR__ ."/../../config.json"));
		$this->urlCkan = $this->config->ckan->url;
    }
    
	public function getStoreOptions(){
		$headr = array();
		$headr[] = 'Content-length: 0';
		$headr[] = 'Content-type: application/json';
		//$headr[] = 'Authorization: 995efb3c-9349-43d7-965c-d7ce567b323a';
		$headr[] = 'Authorization: '.$this->config->ckan->api_key;
		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER     => $headr,
			CURLOPT_POST=>true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST =>  0
		);
		return $options;
	}

	public function getSimpleOptions(){
		$options = array(
			CURLOPT_POST=>true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST =>  0,
			CURLOPT_POSTFIELDS => array()
		);
		return $options;
	}

	public function getSimpleGetOptions(){
		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST =>  0
		);
		return $options;
	}


	public function callDatastoreApi($params) {
		$result = $this->getDatastoreApi($params);

		echo json_encode($result);
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	public function getDatastoreApi($params) {

		$patternRefine = '/refine./i';
		$patternDisj = '/disjunctive./i';
		$filters_init = array();
		//echo $params . "\r\n";
		$query_params = $this->proper_parse_str($params);
		if(array_key_exists('rows', $query_params)){
			$query_params['limit'] = $query_params['rows'];
			unset($query_params['rows']);
		}
		if(array_key_exists('q', $query_params)){
			if (strpos($query_params['q'], '{') == false) {
				if (strpos($query_params['q'], ':') != false && substr($query_params['q'], 0, 1 ) != '"') {
					$ex = explode(':', $query_params['q']);
					$query_params['q'] = '"'. $ex[0] .'":' .  $ex[1];
				}
			    $query_params['q'] = '{'.$query_params['q'].'}';
			    //echo $query_params['q'];
			}
		}
		foreach($query_params as $key => $value) {
		    if (preg_match($patternRefine,$key)){
		    	$filters_init[preg_replace($patternRefine,"",$key)] =  $value;

		        unset($query_params[$key]);
		        //echo preg_replace($pattern,"",$key);
		    }
		    if (preg_match($patternDisj,$key)){
		    	unset($query_params[$key]);
		    	//$disj[] = preg_replace($patternDisj,"",$key);
		    }
			if($key == "id" || $key == "calendarview"){
		    	unset($query_params[$key]); 
		    }
		}
		if(!empty($filters_init)){
			$query_params['filters'] = json_encode($filters_init);
		}
		

		$url2 = http_build_query($query_params);
		$callUrl =  $this->urlCkan . "api/action/datastore_search?" . $url2;
				
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		//echo $result . "\r\n";
		curl_close($curl);

		$result = json_decode($result,true);
		unset($result["help"]);
		unset($result["result"]["_links"]);


		return $result;
	}

	/**
	 * 
	 * This method is specifically made for CR Reunion
	 * The server does not react the same as the proxmox instance
	 * 
	 * We need to call that for the majority of the methods
	 * 
	 */
	function retrieveParameters($params) {
		$isReunion = false;
		if ($isReunion) {
			if ($params == '') {
				$params = $_SERVER['QUERY_STRING'];

				//We decode parameters (replace %3D by = and + by a space)
				$params = str_replace('%3D', '=', $params);
				$params = str_replace('+', ' ', $params);
			}
			else {
				$params;
			}
		}

		return $params;
	}

	function proper_parse_str($str) {
		if($str == ''){
			$str = $_SERVER['QUERY_STRING'];
		} else {
			if(substr($str, 0, 1 ) == '?'){
				$str = substr($str, 1);
			}
		}	  
		$str = preg_replace('/_slash_/i',"/",$str);
		# result array
	  $arr = array();

	  # split on outer delimiter
	  $pairs = explode('&', $str);

	  # loop through each pair
	  foreach ($pairs as $i) {
	    # split into name and value
	    list($name,$value) = explode('=', $i, 2);
	    
	    # if name already exists
	    if( isset($arr[$name]) ) {
	      # stick multiple values into an array
	      if( is_array($arr[$name]) ) {
	        $arr[$name][] = $value;
	      }
	      else {
	        $arr[$name] = array($arr[$name], $value);
	      }
	    }
	    # otherwise, simply stick it in a scalar
	    else {
	      $arr[$name] = $value;
	    }
	  }

	  # return result array
	  return $arr;
	}

	private function constructReqQToSQL($value, $append=""){
		//"q=emr_dt_service:[2018-04-21T22:00:00Z TO 2018-07-20T22:00:00Z]"
		//"q=emr_dt_service>=\"2018-04-02T22:00:00Z\""
		//q=nom_com:"lyon"
		//q=lyon
		//TODO améliorer boucle avec parenthèses
		$res = "";
		if(count(explode(" AND ", $value)) > 1){
			//$res = " and (";
			foreach(explode(" AND ", $value) as $item){
				$res .= $this->constructReqQToSQL($item," and ");
			}
			$res = substr($res, 5);
		} else if(count(explode(" OR ", $value)) > 1){
			//$res = " and (";
			foreach(explode(" OR ", $value) as $item){
				$res .= $this->constructReqQToSQL($item," or ");
			}
			$res = substr($res, 4);
		} else {
			if(count(explode(" TO ", $value)) > 1){
				$field = explode(":", $value)[0];
				$datas = substr(explode(":", $value,2)[1], 1, -1);
				$d1 = explode(" TO ", $datas)[0];
				$d2 = explode(" TO ", $datas)[1];
				if(is_numeric($d1)){
					$res.=  $field. " >= " . $d1 . " and ". $field. " <= " . $d2; 
				} else {
					$res.=  $field. " >= '" . $d1 . "' and ". $field. " <= '" . $d2 . "'"; 
				}
			} else if(count(explode(":", $value)) == 2){
				$field = explode(":", $value)[0];
				$data = explode(":", $value)[1];
				if(is_numeric($data)){
					$res.=  $field. " = " . $data ; 
				} else {
					if(substr($data, 0, 1 ) == '"') $data = substr($data, 1, -1);
					$res.=  $field. " ilike '%" . $data . "%'"; 
				}
			} else if(count(explode(">=", $value)) > 1 || count(explode("<=", $value)) > 1 || count(explode("=", $value)) > 1 || count(explode(">", $value)) > 1 || count(explode("<", $value)) > 1){
				
				if(count(explode(">=", $value)) > 1){
					$field = explode(">=", $value)[0];
					$data = explode(">=", $value)[1];
					if(is_numeric($data)){
						$res.=  $field. " >= " . $data ; 
					} else {
						if(substr($data, 0, 1 ) == '"') $data = substr($data, 1, -1);
						$res.=  $field. " >= '" . $data . "'"; 
					}
				} else if(count(explode("<=", $value)) > 1){
					$field = explode("<=", $value)[0];
					$data = explode("<=", $value)[1];
					if(is_numeric($data)){
						$res.=  $field. " <= " . $data ; 
					} else {
						if(substr($data, 0, 1 ) == '"') $data = substr($data, 1, -1);
						$res.=  $field. " <= '" . $data . "'"; 
					}
				} else if(count(explode(">", $value)) > 1){
					$field = explode(">", $value)[0];
					$data = explode(">", $value)[1];
					if(is_numeric($data)){
						$res.=  $field. " > " . $data ; 
					} else {
						if(substr($data, 0, 1 ) == '"') $data = substr($data, 1, -1);
						$res.=  $field. " > '" . $data . "'"; 
					}
				} else if(count(explode("<", $value)) > 1){
					$field = explode("<", $value)[0];
					$data = explode("<", $value)[1];
					if(is_numeric($data)){
						$res.=  $field. " < " . $data ; 
					} else {
						if(substr($data, 0, 1 ) == '"') $data = substr($data, 1, -1);
						$res.=  $field. " < '" . $data . "'"; 
					}
				} else {
					$field = explode("=", $value)[0];
					$data = explode("=", $value)[1];
					if(is_numeric($data)){
						$res.=  $field. " = " . $data ; 
					} else {
						if(substr($data, 0, 1 ) == '"') $data = substr($data, 1, -1);
						$res.=  $field. " = '" . $data . "'"; 
					}
				}
			} else if(count(explode("NOT #null(", $value)) > 1){
				$field = substr(explode("NOT #null(", $value)[1], 0, -1);
				$res.=  $field. " not in ('', ',')"; 
			} else {
				$res.=  "_full_text @@ to_tsquery('" . $value . "')";
			}
		}
		
		if($append == ""){
			$res = " and (".$res.")";
		} else {
			$res = $append . $res;
		}
		return $res;
	}


	public function callDatastoreApiFacet($params) {
		$params = $this->retrieveParameters($params);
	
		//error_log('params = ' . $params);
		$query_params = $this->proper_parse_str($params);
		if(array_key_exists('fields', $query_params) || array_key_exists('facet', $query_params)){

			$nhits;$nhitsTotal=0;
			$facet_groups = array();

			$fields = $this->getAllFields($query_params['resource_id']);
			$fieldCoordinates="";
			$fieldGeometries="";
			$fieldId = "id";
			foreach ($fields as $value) {
				if(preg_match("/id|num|code|siren/i",$value['name'])){
					$fieldId = $value['name'];
					break;
				} 
			}
			foreach ($fields as $value) {
				if($value['type'] == "geo_point_2d") $fieldCoordinates = $value['name'];
				if($value['type'] == "geo_shape") $fieldGeometries = $value['name'];
			}

			$filters_init = array();
			$disj = array();
			$patternRefine = '/refine./i';
			$patternDisj = '/disjunctive./i';
			$patternSort = '/facetsort./i';
			$reqQfilter="";$qField="";
	//		echo json_encode($query_params);
			foreach($query_params as $key => $value) {
			    if (preg_match($patternRefine,$key)){
			    	$filters_init[preg_replace($patternRefine,"",$key)] =  $value;

			        unset($query_params[$key]);
			        //echo preg_replace($pattern,"",$key);
			    }
			    if (preg_match($patternDisj,$key)){
			    	unset($query_params[$key]);
			    	$disj[] = preg_replace($patternDisj,"",$key);
			    }
				if (preg_match($patternSort,$key)){
			    	unset($query_params[$key]);
			    }
			    if($key == "q"){
			    	$reqQfilter = $this->constructReqQToSQL($value);
			    	$pattern = '/and (\w+) /i';
			    	preg_match($pattern,$reqQfilter,$qField); 
			    }
			    if($key == "id" || $key == "basemap" || $key == "location" || $key == "datasetcard" || $key == "static" || $key == "scrollWheelZoom"){
			    	unset($query_params[$key]); 
			    }
				if ($key == "geofilter.distance"){
					unset($query_params[$key]);
					$filters_init[$key] =  $value;
				}
				if ($key == "geofilter.polygon"){
					unset($query_params[$key]);
					$filters_init[$key] =  $value;
				}
			}


			
			//echo json_encode($filters_init);
			//$filters_init = json_decode($query_params['filters']);
			if(array_key_exists('fields', $query_params)){
				$facets = preg_split('/,/', $query_params['fields']);
			} else if(array_key_exists('facet', $query_params)){
				if(is_array($query_params['facet'])){
					$facets = $query_params['facet'];
				} else {
					$facets = array();
					$facets[] = $query_params['facet'];
				}
				//unset($query_params['facet']);
			}
			
			$nhits = 0;
			if(!array_key_exists('rows', $query_params) || $query_params["rows"] == 0){
				for($i = 0; $i < count($facets); ++$i) {
					$group = array();
					$query_params['fields'] = $facets[$i];
					$query_params['distinct'] = "true";
					if(count($filters_init) > 0){
						$filters = array_merge(array(), $filters_init);
						if (in_array($facets[$i], $disj)) {
							unset($filters[$facets[$i]]);
						}
						$query_params['filters'] = json_encode($filters);
					}
					
					//echo $query_params['filters'];
					unset($query_params['limit']);
					
					
					$where = "";
					if(!empty($filters)){
						$where = " where ";
						foreach ($filters as $key => $value) {
							if($key == "geofilter.distance"){
								$coord = explode(',', $value);
								$lat = $coord[0];
								$long = $coord[1];
								if(count($coord)> 2){
									$dist = $coord[2];
									//$bbox = $this->getBbox($lat,$long,$dist);
									//$bbox = explode(',', $bbox);
									//$minlat = $bbox[0];
									//$minlong = $bbox[1];
									//$maxlat = $bbox[2];
									//$maxlong = $bbox[3];
									//$where .= "box(point(" . $minlat . "," . $minlong . "),point(" . $maxlat . "," . $maxlong . ")) @> point(".$fieldCoordinates.") and ";
									//$where .= "circle(point(" . $lat . "," . $long . "), " . $dist/100000 . ") @> point(".$fieldCoordinates.") and ";
									$where .= "circle(point(" . $lat . "," . $long . "), " . $this->getRadius($lat,$long,$dist) . ") @> point(".$fieldCoordinates.") and ";
									//$where .= "circle(polygon(path '(" . $this->getLosangePath($lat,$long,$dist) . ")')) @> point(".$fieldCoordinates.") and ";
								} else {
									//$where .= "CAST(split_part(".$fieldCoordinates.",',',1)AS FLOAT) = " . $lat . " and ";
									//$where .= "CAST(split_part(".$fieldCoordinates.",',',2)AS FLOAT) = " . $long . " and ";
									$where .= "point(" . $lat . "," . $long . ") ~= point(".$fieldCoordinates.") and ";
								}
								
								$where .= $fieldCoordinates." not in ('', ',') and ";
							} else if($key == "geofilter.polygon"){
								//polygon(path '((0,0),(1,1),(2,0))')
								$where .= "polygon(path '(" . $value . ")') @> point(".$fieldCoordinates.") and ";
								$where .= $fieldCoordinates." not in ('', ',') and ";
							} else {
								if(is_numeric($value) && $key != "insee_com" && $key != "code_insee"){
									$where .= $key . "=" . $value . " and ";
								} else if(is_array($value)){ 
									$where .= $key . " in (" . implode(',', array_map(array($this, 'quotesArrayValue'), str_replace("'", "''", $value))) . ") and ";
								} else {
									$where .= $key . "='" . str_replace("'", "''", $value) . "' and ";
								}
							}
						}
						$where = substr($where, 0, strlen($where)-4 );
						if($reqQfilter != NULL){
							$where .= $reqQfilter;
						}
					}
					else if($reqQfilter != NULL){
						$where = " where " . substr($reqQfilter, 5);
					}

					$req = array();
					$sql = "Select \"".$query_params['fields']."\", count(\"".$query_params['fields']."\") as total from \"" . $query_params['resource_id'] . "\"" . $where . "group by \"".$query_params['fields'] . "\"";
					
					error_log($sql);
					
					$req['sql'] = $sql;
					//echo $sql;
					$url2 = http_build_query($req);
					$callUrl =  $this->urlCkan . "api/action/datastore_search_sql?" . $url2;


					$curl = curl_init($callUrl);
					curl_setopt_array($curl, $this->getStoreOptions());
					$result = curl_exec($curl);
					//echo $callUrl;
					curl_close($curl);
					$result = json_decode($result,true);
					//echo count($result['result']['records']) . "\r\n";
					//$nhits = $result['result']['total'];
					//$nhits = count($result['result']['records']);
					//$nhitsTotal += $nhits;
					$nhitsTotal = 0;
					$nhitsRefined = 0;
					$values = array();

					for($j = 0; $j < count($result['result']['records']); ++$j) {

							$value = array();
							$value['name'] = $result['result']['records'][$j][$facets[$i]];
							$value['path'] = $value['name'];
							//$value['count'] = $result2['result']['total'];
							$value['count'] = $result['result']['records'][$j]['total'];
							$bool = false;
							foreach ($filters_init as $k => $v) {
								if(is_array($v)){
									if(in_array($value['name'], $v)){
										$bool = true;
										break;
									}
								} else {
									if($value['name'] == $v){
										$bool = true;
										break;
									}
								}
							}
							if($qField != "" && $value['name'] == $qField){
								$bool = true;
							}
							if($bool){
								$value['state'] = "refined";
								$nhitsRefined += $value['count'];
							} else {
								$value['state'] = "displayed";
							}
							if($value['count'] > 0){
								$values[] = $value;

								$nhitsTotal += $value['count'];
							}
										 
		 
					}
					
					array_multisort( array_column($values, "count"), SORT_DESC, $values );
					
					//echo count($values)." ". $nhitsTotal; 
					if(count($values) > ($nhitsTotal - 5*$nhitsTotal/100)){ //protection interface
						$values = array_slice($values, 0, 500); 
					}
					$group['name'] = $facets[$i];
					$group['facets'] = $values;
					
					$facet_groups[] = $group;
					if($nhitsRefined == 0){
						$nhitsRefined = $nhitsTotal;
					}
					if($nhits == 0){
						$nhits = $nhitsRefined;
					} else {
						$nhits = min($nhits,$nhitsRefined);
					}
				}
			}
			$data_array = array();
			$data_array['nhits'] = $nhits;
			$data_array['facet_groups'] = $facet_groups;
			foreach($query_params as $key => $value) {
				if(!empty($key)){
				  	$data_array["parameters"][$key] =  $value;
				}	
			}

			if(array_key_exists("rows", $query_params) && $query_params["rows"] > 0){
				$data = $this->getDatastoreRecord_v2($params);
//echo json_encode($data);
				$data_array['records'] = $data['records'];
				$data_array['nhits'] = $data['nhits'];
			}
			echo json_encode( $data_array );
			$response = new Response();
			$response->headers->set('Content-Type', 'application/json');
			//$response->body(json_encode( $data_array ));
			return $response;
		}
		else {
			return $this->callDatastoreApi($params);
		}
	}

	private function getFacetValuebyName($name, $array){
		foreach($array as $row) {
			if($row['name'] == $name){
				return $row;
			}
		}
		return NULL;
	}

	

	public function callPackageShow($params) {
		$result = $this->getPackageShow($params);
		unset($result["help"]);
		foreach($result["result"]["resources"] as $j => $value) {
			unset($result["result"]["resources"][$j]["url"]);	
		}

		
		$result["result"]["metadata_imported"] = $result["result"]["metadata_modified"];
		$result["result"]["metadata_modified"] = current(array_filter($result["result"]["extras"], function($f){ return $f["key"] == "date_moissonnage_last_modification";}))["value"] ?: $result["result"]["metadata_modified"];
		$result["result"]["metadata_created"] = current(array_filter($result["result"]["extras"], function($f){ return $f["key"] == "date_moissonnage_creation";}))["value"] ?: $result["result"]["metadata_created"];
		
		echo json_encode($result);
		$response = new Response();
		//$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	public function callPackageShowForSearch($params) {
		$result = $this->getPackageShow($params);
		unset($result["help"]);
		
		echo json_encode($result);
		$response = new Response();
		//$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	public function getPackageShow($params){
		$callUrl =  $this->urlCkan . "api/action/package_show?" . $params;		
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		//echo $result . "\r\n";
        //drupal_set_message('<pre>'. print_r($json, true) .'</pre>');
		$result = json_decode($result,true);
		return $result;
	}
	
	public function getPackageSearch($params, $additionnalParameters = null, $rows = null, $start = null){
		//$params = str_replace("qf=title^3.0 notes^1.0", "qf=title^3.0+notes^1.0", $params);	 
		$callUrl =  $this->urlCkan . "api/action/package_search";


		if(!is_null($params)){
			$params = str_replace('&defType=edismax', '', $params);
			$callUrl .= "?" . $params;
			$callUrl = str_replace('%3D', '=', $callUrl);
			$callUrl = str_replace('%26', '&', $callUrl);
		}

		if ($additionnalParameters) {
			$callUrl = str_replace('%3D', '=', $callUrl);
		}

		Logger::logMessage("Call search " . $callUrl);

		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getSimpleOptions());
		$result = curl_exec($curl);
		curl_close($curl);

		$result = json_decode($result,true);

		Logger::logMessage("Found " . count($result["result"]["results"]) . " datasets");

		//Here we have the result from CKAN
		//We need to filter those result according to the selected map area (if there is a selection)

		//First we get the coordinate from the map
		$coordmap ="";
		if($additionnalParameters) {

			$coordmap = $additionnalParameters;

			//We put the coordinates in an array. Very ugly way to do but no time. To remake
			$coordmap = str_replace('%28', '(', $coordmap);
			$coordmap = str_replace('%29', ')', $coordmap);
			$coordmap = str_replace('%2C', ',', $coordmap);
			$coordinates = explode("),", $coordmap);

			for($i = 0; $i < count($coordinates); ++$i) {
				$coordinates[$i] = str_replace('(', '', $coordinates[$i]);
				$coordinates[$i] = str_replace(')', '', $coordinates[$i]);
			}

			// Logger::logMessage("COORDINATES " . json_encode($coordinates));
			$dataSetscontent = [];

			

			// We browse the resources of all the dataset found to see if it contains a geoloc field
			foreach($result["result"]["results"] as $keydataset=>$dataset) {

				$resourceId = null;
				$fieldCoordinates = null;
				foreach ($dataset["resources"] as $value) {
					//We get the field for the dataset
					$fields = $this->getAllFields($value['id']);
					// Logger::logMessage("Dataset      " . $dataset['id'] . "    with resource     " . $value['id']);

					foreach ($fields as $field) {
						if($field['type'] == "geo_point_2d") {
							$fieldCoordinates = $field['name'];
							break;
						}
					}

					//If there is a coordinate field, we 
					if ($fieldCoordinates) {
						$resourceId = $value['id'];
						break;
					}
				}
			
				//If there is a coordinate field, we call the database to see if one of his point belong to the user selection
				if ($fieldCoordinates) {
					Logger::logMessage("Found field geo_point_2d '" . $fieldCoordinates . "' for resource id '" . $resourceId . "' and dataset id '" . $dataset['id'] . "'");

					$polygon = '';
					$first = true;
					
					$coord = explode(',', $coordinates);
					if(sizeof($coordinates) <= 3) {
						if($coord == null ) {
							$coord = explode(',', $coordinates[0]);
						}
						$lat = $coord[0];
						$long = $coord[1];
						
						
						if(count($coord)> 2){
									$dist = $coord[2];
									
							
									$sql = "Select count(*), min((point(" . $fieldCoordinates . "))[0]) as minLat, max((point(" . $fieldCoordinates . "))[0]) as maxLat, min((point(" . $fieldCoordinates . "))[1]) as minLong, max((point(" . $fieldCoordinates . "))[1]) as maxLong from \"" . $resourceId . "\"";
									$sql .= "where circle(point(" . $lat . "," . $long . "), " . $this->getRadius($lat,$long,$dist) . ") @> point(".$fieldCoordinates.")  ";
									
								}

					} else {
						foreach ($coordinates as $coordinate) {
						if (!$first) {
							$polygon .= ",";
						}
						$first = false;
						$polygon .= "(" . $coordinate . ")";
						}

						$sql = "Select count(*), min((point(" . $fieldCoordinates . "))[0]) as minLat, max((point(" . $fieldCoordinates . "))[0]) as maxLat, min((point(" . $fieldCoordinates . "))[1]) as minLong, max((point(" . $fieldCoordinates . "))[1]) as maxLong from \"" . $resourceId . "\"";
						$sql .= " where polygon(path '(" . $polygon . ")') @> point(" . $fieldCoordinates . ") ";
					}
					

					

					$req['sql'] = $sql;

					$sqlUrl = http_build_query($req);
					$callUrl =  $this->urlCkan . "api/action/datastore_search_sql?" . $sqlUrl;
					$curl = curl_init($callUrl);
					curl_setopt_array($curl, $this->getStoreOptions());
					$resultSql = curl_exec($curl);

					// Logger::logMessage("Result SQL " . $resultSql);

					curl_close($curl);
					$resultSql = json_decode($resultSql, true);

					if((int)$resultSql["result"]["records"][0]["count"] > 0 ) {
						if(!in_array($dataset, $dataSetscontent)) {
							array_push($dataSetscontent, $dataset);
						}
						
					}else {
						
						unset($result["result"]["results"][$keydataset]);
						$result["result"]["count"] -= 1;
					}
					//array_push($dataSetscontent, $dataset);
					//TODO : LEAVE THE DATASET OR REMOVE ACCORDING TO THE RESULT
				}
				//If not we remove the dataset from the result
				else {
					
					$result["result"]["count"] -= 1;
					
					unset($result["result"]["results"][$keydataset]);
					

					//TODO : REMOVE THE DATASET
				}
			
			}
			if ($fieldCoordinates) {
			$result["result"]["results"] = $dataSetscontent;
			$result["result"]["count"] = sizeof( $dataSetscontent);
			}
		
			
		}
			
		return $result;
	}
	
	public function getExtendedPackageSearch($params, $exclude_private_orgas = TRUE/*, $return_visualisations = TRUE*/){
		$query_params = $this->proper_parse_str($params);

		$orgs;
		//error_log($params);
		if($query_params["sort"] != null){
			$query_params["sort"] = str_replace("title", "title_string", $query_params["sort"]);
		}

		$coordinateParam = null;
		if(array_key_exists('coordReq', $query_params)){
			$coordinateParam = $query_params['coordReq'];
			unset($query_params['coordReq']);

			//We replace the rows and start to get all the dataset
			$rows = $query_params['rows'];
			unset($query_params['rows']);

			$start = $query_params['start'];
			unset($query_params['rows']);

			$query_params["rows"] = 1000;
			
			if ($query_params["fq"] == null) {
				$query_params["fq"] = "features:(*geo*)";
			}
			else {
				$query_params["fq"] .= " AND features:(*geo*)";
			}
		}
		
		if($exclude_private_orgas){
			$callUrlOrg =  $this->urlCkan . "api/action/organization_list?all_fields=true&include_extras=true";
			$curlOrg = curl_init($callUrlOrg);
			curl_setopt_array($curlOrg, $this->getSimpleOptions());
			$orgs = curl_exec($curlOrg);
			curl_close($curlOrg);
			$orgs = json_decode($orgs, true);

			$orgs_private=[];
			$orgsPrivateIndex = [];
			for ( $i= 0 ; $i <= count($orgs["result"]) ; $i++ ) {
				$org = $orgs["result"][$i];
				foreach($org["extras"] as $extra){
					if($extra["key"] == "private"){
						if($extra["value"] == "true"){
							$orgs_private[] = $org["name"];
							$orgsPrivateIndex[] = $i;
							// unset($orgs["result"][$key]);
						}
						break;
					}
				}
			}
			foreach($orgsPrivateIndex as $index){
				array_splice($orgs["result"], $index, 1);
			}

			if(count($orgs_private) > 0){
				$queryOrgs = implode($orgs_private, " OR ");
				$req = "-organization:(".$queryOrgs.")";
				
				if($query_params["fq"] == null){
					$query_params["fq"] = $req;
				} else {
					$query_params["fq"] .= " AND " . $req;
				}
			}
		}


		$url2 = http_build_query($query_params);

		//echo $url2;
		$result = $this->getPackageSearch($url2, $coordinateParam, $rows, $start);
		$result["all_organizations"] = $orgs["result"];
		error_log($result["result"]["count"]);
		return $result;
		
	}

	public function callPackageSearch($params) {
		$arrFac;
		$arrFacSearch;
		$arr = array();
        //$hasFacetFeature = false;
        $result = $this->getExtendedPackageSearch($params);
		
		$hasFacetFeature = array_key_exists("features",$result["result"]["facets"]);
		
		unset($result["help"]);//echo count($result["result"]["results"]);
		foreach($result["result"]["results"] as &$dataset) {
			$dataset["metas"] = array();
			$dataset["metas"]["records_count"] = current(array_filter($dataset["extras"], function($f){ return $f["key"] == "records_count";}))["value"] ?: 0;
			$dataset["metas"]["records_count"] = floatval($dataset["metas"]["records_count"]);
			
			$dataset["metas"]["features"] = current(array_filter($dataset["extras"], function($f){ return $f["key"] == "features";}))["value"] ?: null;
			$dataset["metas"]["features"] = explode(",", $dataset["metas"]["features"]);
			if($hasFacetFeature){
				$arr[] = $dataset["metas"]["features"];
			}
			
			$dataset["metas"]["custom_view"] = current(array_filter($dataset["extras"], function($f){ return $f["key"] == "custom_view";}))["value"] ?: null;
			$dataset["metas"]["custom_view"] = json_decode($dataset["metas"]["custom_view"], true);
			
			$dataset["metadata_imported"] = $dataset["metadata_modified"];
			$dataset["metadata_modified"] = current(array_filter($dataset["extras"], function($f){ return $f["key"] == "date_moissonnage_last_modification";}))["value"] ?: $dataset["metadata_modified"];
			$dataset["metadata_created"] = current(array_filter($dataset["extras"], function($f){ return $f["key"] == "date_moissonnage_creation";}))["value"] ?: $dataset["metadata_created"];
			
			foreach($dataset["resources"] as $j => $value) {
				unset($dataset["resources"][$j]["url"]);	//echo $value["url"];
			}
               
		}
		
		if($hasFacetFeature){
			
			$arr = array();
			foreach($result["result"]["facets"]["features"] as $key => $count){
				for($i=0; $i<$count; $i++){
					$arr = array_merge($arr, explode(",", $key));
				}
			}
			
			$result["result"]["facets"]["features"] = array_count_values($arr);
			unset($result["result"]["facets"]["features"]["api"]);
			unset($result["result"]["facets"]["features"]["table"]);
			unset($result["result"]["facets"]["features"]["timeserie"]);
			
			$result["result"]["search_facets"]["features"]["items"] = array();
			foreach($result["result"]["facets"]["features"] as $feat => $c){
				$result["result"]["search_facets"]["features"]["items"][] = array(
					"count" => $c,
					"display_name" => $feat,
					"name" => $feat
				);
			}
		}

		$response = new Response();
		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
    
    
 	public function callPackageSearch_public_private($params, $iduser=NULL) {
		$params = str_replace("qf=title^3.0 notes^1.0", "qf=title^3.0+notes^1.0", $params);	 
		//$params = str_replace(" ", "+", $params);	 
		$params = str_replace("+asc", " asc", str_replace("+desc", " desc", $params));	 
		$callUrl =  $this->urlCkan . "api/action/package_search";
		
        if($iduser != NULL){
			
			$query_params = $this->proper_parse_str($params);
			
			$orgs = implode($orgs_private, " OR ");
				$req = "-(-edition_security:**".$iduser."** OR edition_security:*)";
				
				if($query_params["fq"] == null){
					$query_params["fq"] = $req;
				} else {
					$query_params["fq"] .= " AND " . $req;
				}

			//We encode url again
			$params = http_build_query($query_params);
		}
		else {
			//We replace space here as we do not encode url again
			$params = str_replace(" ", "+", $params);	 
		}
		
        if(!is_null($params)){
			$callUrl .= "?" . $params;
		} 
		// drupal_set_message($callUrl);
        error_log('url check : ' . $callUrl);
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		//echo $callUrl;
		curl_close($curl);
		
		$result = json_decode($result,true);
        
        
        
		unset($result["help"]);//echo count($result["result"]["results"]);
		foreach($result["result"]["results"] as $i => $dataset) {
            
            //error_log('aaaaaa');
            //$dataset['result'] = $result["result"]["results"][$i];
            //$paramForRightBar = $this->getPackageShow2($dataset['result'], '', false);
            
            //error_log($paramForRightBar);
            //$result["result"]["results"]["features"]=array();
            
			$result["result"]["results"][$i]["metas"] = array();
			$result["result"]["results"][$i]["metas"]["records_count"] = 0;
			/*foreach($dataset["resources"] as $j => $value) {
				//unset($result["result"]["results"][$i]["resources"][$j]["url"]);	//echo $value["url"];
				
				$format = $result["result"]["results"][$i]["resources"][$j]["format"];
				if(($format == "CSV" || $format = "XLS" || $format == "XLSX") && $result["result"]["results"][$i]["resources"][$j]["datastore_active"] == true){
					//$records_result = $this->getDatastoreRecord_v2("dataset=".$result["result"]["results"][$i]["name"]."&rows=1");
					$records_result = $this->getDatastoreApi("resource_id=".$result["result"]["results"][$i]["resources"][$j]["id"]."&limit=0");
					$result["result"]["results"][$i]["metas"]["records_count"] = $records_result["result"]["total"];
                    //error_log(print_r($records_result,true));
					break;
				}	
			}*/
            
		}
				
		//echo json_encode($result);
		foreach( $arr_dell as &$value){
            
            unset($result["result"]["results"][$value]);
        }
		$result["result"]["results"]= array_merge($result["result"]["results"]);

		$response = new Response();
		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}   
    
    
    

	public function callDatastoreApiBoundingBox($params) {
		$params = $this->retrieveParameters($params);


		$patternRefine = '/refine./i';
		$patternDisj = '/disjunctive./i';
		$patternBbox = '/geofilter.bbox/i';
		$patternDistance = '/geofilter.distance/i';
		$patternPolygon = '/geofilter.polygon/i';
		$reqQfilter="";$qField="";
		$filters_init = array();
		//echo $params . "\r\n";
		$query_params = $this->proper_parse_str($params);

		$fields = $this->getAllFields($query_params['resource_id']);

		$fieldCoordinates="";
		$fieldGeometries="";
		$fieldId = "_id";
		/*foreach ($fields as $value) {
			if(preg_match("/id|num|code|siren/i",$value['name'])){
				$fieldId = $value['name'];
				break;
			} 
		}*/
		
		//This is not working we decided to move the geoloc column during csv creation
		//We check first if the fields contains a facet is_geoloc which means he is in charge for coordinate
		// $coordinatesAlreadyDefined = false;
		// foreach ($fields as $value) {
		// 	foreach($value["annotations"] as $annotation){
		// 		if($annotation["name"] == "is_geoloc"){
		// 			$fieldCoordinates = $value['name'];
		// 			$coordinatesAlreadyDefined = true;
		// 		}
		// 	}
		// }

		$coordinatesAlreadyDefined = false;
		$geometriesAlreadyDefined = false;
		foreach ($fields as $value) {
			//echo $value['id'];
			/*if($value['id'] == "geo_point_2d") $fieldCoordinates = $value['id'];
			if($value['id'] == "geo_shape") $fieldGeometries = "cast(geo_shape::json->'type' as text)";
			if(preg_match("/coordin/i",$value['id'])) $fieldCoordinates = $value['id'];
			if(preg_match("/coordon/i",$value['id'])) $fieldCoordinates = $value['id'];
			if(preg_match("/geometr/i",$value['id'])) $fieldGeometries = $value['id'];*/
			if(!$coordinatesAlreadyDefined && $value['type'] == "geo_point_2d") {
				$fieldCoordinates = $value['name'];
				$coordinatesAlreadyDefined = true;
			}
			//if($value['type'] == "geo_shape") $fieldGeometries = "cast(".$value['name']."::json->'type' as text)";
			if(!$geometriesAlreadyDefined && $value['type'] == "geo_shape") {
				$fieldGeometries = $value['name'];
				$geometriesAlreadyDefined = true;
			}
		}
        Logger::logMessage("Found coordinate " . $fieldCoordinates ."\r\n");
        Logger::logMessage("Found geometries " . $fieldGeometries ."\r\n");


		if(array_key_exists('rows', $query_params)){
			$query_params['limit'] = $query_params['rows'];
			unset($query_params['rows']);
		}
		if(array_key_exists('q', $query_params)){
			/*if (strpos($query_params['q'], '{') == false) {
				if (strpos($query_params['q'], ':') != false && substr($query_params['q'], 0, 1 ) != '"') {
					$ex = explode(':', $query_params['q']);
					$query_params['q'] = '"'. $ex[0] .'":' .  $ex[1];
				}
			    $query_params['q'] = '{'.$query_params['q'].'}';
			    //echo $query_params['q'];
			}*/
			$reqQfilter = $this->constructReqQToSQL($query_params['q']);
		}
		foreach($query_params as $key => $value) {
			Logger::logMessage("Found parameter " . $key ." with value " . $value . "\r\n");

		    if (preg_match($patternRefine,$key)){
		    	$filters_init[preg_replace($patternRefine,"",$key)] =  $value;

		        unset($query_params[$key]);
		        //echo preg_replace($pattern,"",$key);
		    }
		    if (preg_match($patternDisj,$key)){
		    	unset($query_params[$key]);
		    	//$disj[] = preg_replace($patternDisj,"",$key);
		    }
		    if (preg_match($patternBbox,$key)){
		    	unset($query_params[$key]);
		    	$filters_init[$key] =  $value;
		    }
			if (preg_match($patternDistance,$key)){
		    	unset($query_params[$key]);
		    	$filters_init[$key] =  $value;
		    }
			if (preg_match($patternPolygon,$key)){
		    	unset($query_params[$key]);
		    	$filters_init[$key] =  $value;
		    }
		}
		$where = "";
		if(!empty($filters_init)){
			Logger::logMessage("Filters exists");

			$where = " where ";
			foreach ($filters_init as $key => $value) {
				if($key == "geofilter.bbox"){
					Logger::logMessage("Build query for geofilter.bbox \r\n");

					$bbox = explode(',', $value);
					$minlat = $bbox[0];
					$minlong = $bbox[1];
					$maxlat = $bbox[2];
					$maxlong = $bbox[3];
					//$where .= "CAST(split_part(".$fieldCoordinates.",',',1)AS FLOAT) between " . $minlat . " and " . $maxlat . " and ";
					//$where .= "CAST(split_part(".$fieldCoordinates.",',',2)AS FLOAT) between " . $minlong . " and " . $maxlong . " and ";
					$where .= "box(point(" . $minlat . "," . $minlong . "),point(" . $maxlat . "," . $maxlong . ")) @> point(".$fieldCoordinates.") and ";
					$where .= $fieldCoordinates." not in ('', ',') and ";
				} else if($key == "geofilter.distance"){
					Logger::logMessage("Build query for geofilter.distance \r\n");

					$coord = explode(',', $value);
					$lat = $coord[0];
					$long = $coord[1];
					if(count($coord)> 2){
						$dist = $coord[2];
						//$bbox = $this->getBbox($lat,$long,$dist);
						//$bbox = explode(',', $bbox);
						//$minlat = $bbox[0];
						//$minlong = $bbox[1];
						//$maxlat = $bbox[2];
						//$maxlong = $bbox[3];
						//$where .= "box(point(" . $minlat . "," . $minlong . "),point(" . $maxlat . "," . $maxlong . ")) @> point(".$fieldCoordinates.") and ";
						//$where .= "circle(box(point(" . $minlat . "," . $minlong . "),point(" . $maxlat . "," . $maxlong . "))) @> point(".$fieldCoordinates.") and ";
						$where .= "circle(point(" . $lat . "," . $long . "), " . $this->getRadius($lat,$long,$dist) . ") @> point(".$fieldCoordinates.") and ";
						//$where .= "circle(polygon(path '(" . $this->getLosangePath($lat,$long,$dist) . ")')) @> point(".$fieldCoordinates.") and ";
						//echo $where;
					} else {
						//$where .= "CAST(split_part(".$fieldCoordinates.",',',1)AS FLOAT) = " . $lat . " and ";
						//$where .= "CAST(split_part(".$fieldCoordinates.",',',2)AS FLOAT) = " . $long . " and ";
						$where .= "point(" . $lat . "," . $long . ") ~= point(".$fieldCoordinates.") and ";
					}
					
					//$where .= $fieldCoordinates." not in ('', ',') and ";
				} else if($key == "geofilter.polygon"){
					Logger::logMessage("Build query for geofilter.polygon \r\n");

					//polygon(path '((0,0),(1,1),(2,0))')
					$where .= "polygon(path '(" . $value . ")') @> point(".$fieldCoordinates.") and ";
				} else {
					Logger::logMessage("Build query without parameter \r\n");

					if(is_numeric($value) && $key != "insee_com" && $key != "code_insee"){
						$where .= $key . "=" . $value . " and ";
					} else if(is_array($value)){ 
						$where .= $key . " in (" . implode(',', array_map(array($this, 'quotesArrayValue'), str_replace("'", "''", $value))) . ") and ";
					} else {
						$where .= $key . "='" . str_replace("'", "''", $value) . "' and ";
					}
				}
				
				
			}
			$where = substr($where, 0, strlen($where)-4 );
			if($reqQfilter != ""){
				$where .= $reqQfilter;
			}
		}
		else if($reqQfilter != ""){
			Logger::logMessage("Req filter is not empty '" . $reqQfilter . "' and we put '" . substr($reqQfilter, 5) . "'");

			$where = " where " . substr($reqQfilter, 5);
		}


		$req = array();
		if($fieldGeometries != ""/* && !empty($filters_init)*/){
			$sql = "with latlong as ( Select cast(unnest(regexp_matches(".$fieldGeometries.", '\[([-]?[\d|.]*),', 'g')) as float) as longs, cast(unnest(regexp_matches(".$fieldGeometries.", ',[ ]?([-]?[\d|.]*)(?:,[\d|\w]+,[\d|\w]+)*\]', 'g')) as float) as lats, ".$fieldId." as ids from \"" . $query_params['resource_id'] . "\"" . $where .	   
					" limit 1000) select count(distinct ids), min(lats) as minlat, max(lats) as maxlat, min(longs) as minlong, max(longs) as maxlong from latlong";
			
			$req['sql'] = $sql;
		}
		else {
			//$sql = "Select count(*) as count,min(CAST(split_part(".$fieldCoordinates.",',',1)AS FLOAT)) as minLat,max(CAST(split_part(".$fieldCoordinates.",',',1)AS FLOAT)) as maxLat,min(CAST(split_part(".$fieldCoordinates.",',',2)AS FLOAT)) as minLong,max(CAST(split_part(".$fieldCoordinates.",',',2)AS FLOAT)) as maxLong from \"" . $query_params['resource_id'] . "\"";
			$sql = "Select count(*), min((point(".$fieldCoordinates."))[0]) as minLat, max((point(".$fieldCoordinates."))[0]) as maxLat, min((point(".$fieldCoordinates."))[1]) as minLong, max((point(".$fieldCoordinates."))[1]) as maxLong from \"" . $query_params['resource_id'] . "\"";
			if(!empty($filters_init)){
				$sql = $sql . $where. " and ".$fieldCoordinates." not in ('', ',')";
			} 
			else if($reqQfilter != ""){
				$sql = $sql . $where. " and ".$fieldCoordinates." not in ('', ',')";
			}
			else {
				$sql = $sql . " where ".$fieldCoordinates." not in ('', ',')";
			}
			$req['sql'] = $sql;
		}
  
		Logger::logMessage("Query : " . $req['sql']);
		
		$url2 = http_build_query($req);
		$callUrl =  $this->urlCkan . "api/action/datastore_search_sql?" . $url2;
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);

		Logger::logMessage("Result query coordinate : " . $result);
		
		curl_close($curl);
		$result = json_decode($result,true);

		if($fieldGeometries != ""){
			if(!empty($filters_init)){
				$where = $where. " and ".$fieldGeometries." not in ('', ',')";
			} else {
				$where = " where ".$fieldGeometries." not in ('', ',')";
			}
			$sql = "Select cast(".$fieldGeometries."::json->'type' as text) as type_geom, count(*) from \"" . $query_params['resource_id'] . "\"" . $where ." group by type_geom";
			// $sql = "Select " . $fieldGeometries . " as type_geom, count(*) from \"" . $query_params['resource_id'] . "\"" . $where ." group by type_geom";
			$req['sql'] = $sql;

			Logger::logMessage("Geometry query : " . $req['sql']);
		
			$url2 = http_build_query($req);
			$callUrl =  $this->urlCkan . "api/action/datastore_search_sql?" . $url2;
			$curl = curl_init($callUrl);
			curl_setopt_array($curl, $this->getStoreOptions());
			$result2 = curl_exec($curl);

			// Logger::logMessage("Result query geometry : " . $result2);
			
			curl_close($curl);
			$result2 = json_decode($result2,true);
		} else {
			//echo "point";
			$result2 = array();
			$result2["result"] = array();
			$result2["result"]["records"] = array();
			$result2["result"]["records"][] = array("type_geom" => "\"Point\"", "count" => $result["result"]["records"][0]["count"]);
			//$result2 = json_decode('{"result": {"records" : [{"type": v, "count" :'.$result["result"]["records"][0]["count"].'}]}}');
			//echo json_decode($result2) . "\r\n";
		}
		

		$data_array = array();
		$data_array['geometries'] = array();
		$c = 0;
		foreach ($result2["result"]["records"] as $value) {
			$key = substr($value['type_geom'], 1, strlen($value['type_geom'])-2);
			 
			if(substr($key, 0, 5) == "Multi"){
				if(array_key_exists('GeometryCollection', $data_array['geometries'])){
					$data_array['geometries']['GeometryCollection'] .= intval ($value['count']);
				} else {
					$data_array['geometries']['GeometryCollection'] = intval ($value['count']);
				}
			} else {
				$data_array['geometries'][$key] = intval ($value['count']);
			}
			$c += intval ($value['count']);
		}
		//$data_array['count'] = intval ($result["result"]["records"][0]["count"]);
		$data_array['count'] = $c;
		if($data_array['count'] == 0){
			$data_array['bbox'] = array();
		} else {

			$data_array['bbox'] = array(
								$result["result"]["records"][0]["minlong"],
								$result["result"]["records"][0]["maxlat"],
								$result["result"]["records"][0]["maxlong"],
								$result["result"]["records"][0]["minlat"]
			);
		}
		

		echo json_encode( $data_array );
		$response = new Response();
//		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	private function quotesArrayValue($item){
		if(!is_numeric($item)) {
	        return "'" . $item . "'";
	    } else {
	        return $item;
	    }
	}

	public function getAllFields($id, $full = FALSE, $includeIdCkan = TRUE) {

		$callUrl ="";
		if($full){
			$callUrl =  $this->urlCkan . "api/action/datastore_search?resource_id=" . $id . "&limit=0";
		} else {
			$req = array();
			$sql = "Select * from \"" . $id . "\" limit 0";
			$req['sql'] = $sql;
			//echo $sql;
			$url2 = http_build_query($req);
			$callUrl =  $this->urlCkan . "api/action/datastore_search_sql?" . $url2;
		}
		
		//echo $callUrl;
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		$result = json_decode($result,true);

		$geoPointnb = 0;

		$data_array = array();
		$hasFacet = false;
		foreach ($result['result']['fields'] as $value) {
			if(!$includeIdCkan && $value['id'] == "_id"){
				continue;
			}
			$field = array();
			//if($value['id'] == "_id") continue;
			$field['name'] = $value['id'];

			$isFile = false;
			if($full){
				$annotations = array();
				$description = $value['info']['notes'];
				if(preg_match("/<!--.*facet.*-->/i",$description)) {
					$annotations[] = array("name" => "facet");
					$hasFacet = true; //echo "1";
				} 
				if(preg_match("/<!--.*exportApi.*-->/i",$description)) {
					$annotations[] = array("name" => "exportApi");
				} 
				if(preg_match("/<!--.*disjunctive.*-->/i",$description)) {
					$annotations[] = array("name" => "disjunctive");
				}
				if(preg_match("/<!--.*sortable.*-->/i",$description)){
					$annotations[] = array("name" => "sortable");
				}
				if(preg_match("/<!--.*startDate.*-->/i",$description)){
					$annotations[] = array("name" => "startDate");
				}
				if(preg_match("/<!--.*endDate.*-->/i",$description)){
					$annotations[] = array("name" => "endDate");
				}
				if(preg_match("/<!--.*date.*-->/i",$description)){
					$annotations[] = array("name" => "date");
				}
				if(preg_match("/<!--.*images.*-->/i",$description)){
					$annotations[] = array("name" => "has_thumbnails");
					$isFile = true;
				}
                if(preg_match("/<!--.*wordcount-->/i",$description)) {
					$annotations[] = array("name" => "wordcount");
					$hasFacet = true; //echo "1";
				}
                if(preg_match("/<!--.*wordcountNumber.*-->/i",$description)) {
					$annotations[] = array("name" => "wordcountNumber");
					$hasFacet = true; //echo "1";
				}
                if(preg_match("/<!--.*timeserie_precision.*-->/i",$description)) {
					$annotations[] = array("name" => "timeserie_precision");
					$hasFacet = true; //echo "1";
				} 
				if(preg_match("/<!--.*descr_for_timeLine.*-->/i",$description)) {
					$annotations[] = array("name" => "descr_for_timeLine");
					//$hasFacet = true; //echo "1";
				}
                if(preg_match("/<!--.*image_url.*-->/i",$description)) {
					$annotations[] = array("name" => "image_url");
					//$hasFacet = true; //echo "1";
				}if(preg_match("/<!--.*title_for_timeLine.*-->/i",$description)) {
					$annotations[] = array("name" => "title_for_timeLine");
					//$hasFacet = true; //echo "1";
				}
                if(preg_match("/<!--.*date_timeLine.*-->/i",$description)) {
					$annotations[] = array("name" => "date_timeLine");
					//$hasFacet = true; //echo "1";
				}
				
				$descriptionLabel = $description;
				preg_match_all('/(?<=<!--description\?)([^>]*)-->/', $descriptionLabel, $matches);
				if($matches) {
					$descriptionLabel = $matches[1][0];
				}
				else {
					$descriptionLabel = '';
				}
				$field['descriptionlabel'] = $descriptionLabel;
				
				$field['description'] = $description;
				
				if(count($annotations) > 0){
					$field['annotations'] = $annotations;
				}
				if($value['info']['label'] != ""){
					$field['label'] = $value['info']['label'];
				} else {
					$field['label'] = $field['name'];
				}
				
			}
			else {
				$field['label'] = $field['name'];
			}
			
										
			if(preg_match("/geoloc/i",$value['id']) || preg_match("/geo_point/i",$value['id']) || 
				preg_match("/coordin/i",$value['id']) || preg_match("/coordon/i",$value['id']) || 
				preg_match("/geopoint/i",$value['id']) || preg_match("/geoPoint/i",$value['id']) || 
				preg_match("/pav_positiont2d/i",$value['id']) || preg_match("/wgs84/i",$value['id']) || 
				preg_match("/equgpsy_x/i",$value['id']) || preg_match("/geoban/i",$value['id']) || 
				preg_match("/codegeo/i",$value['id']) || preg_match("/localisation/i",$value['id']) || 
				preg_match("/latlon/i",$value['id']) || preg_match("/lat_lon/i",$value['id'])) {
					// if($geoPointnb == 0) {
						$field['type'] = "geo_point_2d";
						$geoPointnb = 1;
					// }
				//$field['type'] = "geo_point_2d";
			} else if(preg_match("/geo_shape/i",$value['id']) || preg_match("/geome/i",$value['id']) || preg_match("/geojson/i",$value['id'])) {
				$field['type'] = "geo_shape";
			} else if($value['type'] == "timestamp"){
				$field['type'] = "datetime";
			} else if($value['type'] == "numeric"){
				$field['type'] = "double";
			} else if($isFile){
				$field['type'] = "file";
			} else {
				$field['type'] = $value['type'];
			}
			$data_array[] = $field;
		}		

		if(!$hasFacet){
			$hasTextCol = false;
			foreach ($data_array as $id => $field) {
				if($field['type'] == "text"){
					$data_array[$id]['annotations'][] = array("name" => "facet");
					$hasTextCol = true;
					break;
				}
			}
			if(!$hasTextCol){
				$data_array[0]['annotations'][] = array("name" => "facet");
			}
		}

		return $data_array;
	}
    
    public function getAllFieldsForTableParam($resourceId) {
        $callUrl =  $this->urlCkan . "api/action/datastore_search?resource_id=" . $resourceId . "&limit=0";
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		$result = json_decode($result,true);
		return $result;
	}
    
    public function callAllFieldsForTableParam($params) {

		$res = $this->getAllFieldsForTableParam($params);
        
        //$rendered_message = \Drupal\Core\Render\Markup::create('<pre>' . $res . '</pre>');
        //drupal_set_message($rendered_message);
        
		echo json_encode($res);
		$response = new Response();
		//$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;

		return $result;
	}
    



	public function getTableFields($id) {

		$callUrl =  $this->urlCkan . "api/action/datastore_search?resource_id=" . $id . "&limit=0";
		
		//echo $callUrl;
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		$result = json_decode($result,true);

		$res = array();$allFields = array();

		foreach ($result['result']['fields'] as $value) {
			$description = $value['info']['notes'];
			if(preg_match("/<!--\s*table\s*-->/i",$description)) {				
				$res[] =  $value['id'];
			}
			if($value['id'] == "_id") continue;
			$allFields[] =  $value['id'];
		}
		if(count($res) > 0){
			return $res;
		} else {
			return $allFields;
		}
		
	 }

	public function getMapTooltipFields($id) {

		$callUrl =  $this->urlCkan . "api/action/datastore_search?resource_id=" . $id . "&limit=0";
				//echo $callUrl;
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		$result = json_decode($result,true);

		$res = array();$allFields = array();

		foreach ($result['result']['fields'] as $value) {
			$description = $value['info']['notes'];
			if(preg_match("/<!--\s*tooltip\s*-->/i",$description)) {				
				$res[] =  $value['id'];
			}
			if($value['id'] == "_id") continue;
			$allFields[] =  $value['id'];
		}
		if(count($res) > 0){
			return $res;
		} else {
			return $allFields;
		}
		
	}

	public function getRecordsDownload($params) {
		$patternId = '/id|num|code|siren/i';
		$patternRefine = '/refine./i';
		$patternDisj = '/disjunctive./i';
		$patternBbox = '/geofilter.bbox/i';
		$patternDistance = '/geofilter.distance/i';
		$patternSerie = '/y.serie/i';
		$filters_init = array();
		$query_params = $this->proper_parse_str($params);
		$params = $this->retrieveParameters($params);
		$exportField = false;
       

        if($query_params['user_defined_fields']) {
        	$exportField = true;
			
		}
		
		$fields = $this->getAllFields($query_params['resource_id'], TRUE, FALSE);


		//echo json_encode($fields);
		$fieldId = "_id";
		$reqFields="";

		$fieldCoordinates='';
		$fieldGeometries='';

		$reqQfilter;
		/*foreach ($fields as $value) {
			if(preg_match("/id|num|code|siren/i",$value['name'])){
				$fieldId = $value['name'];
				break;
			} 
		}*/

		//This is not working we decided to move the geoloc column during csv creation
		//We check first if the fields contains a facet is_geoloc which means he is in charge for coordinate
		// $coordinatesAlreadyDefined = false;
		// foreach ($fields as $value) {
		// 	foreach($value["annotations"] as $annotation){
		// 		if($annotation["name"] == "is_geoloc"){
		// 			$fieldCoordinates = $value['name'];
		// 			$coordinatesAlreadyDefined = true;
		// 		}
		// 	}
		// }

		$coordinatesAlreadyDefined = false;
		$geometriesAlreadyDefined = false;
		foreach ($fields as $value) {
			/*if($value['id'] == "geo_point_2d") $fieldCoordinates = $value['id'];
			if(preg_match("/coordin/i",$value['id'])) $fieldCoordinates = $value['id'];
			if(preg_match("/coordon/i",$value['id'])) $fieldCoordinates = $value['id'];*/
			if(!$coordinatesAlreadyDefined && $value['type'] == "geo_point_2d") {
				$fieldCoordinates = $value['name'];
				$coordinatesAlreadyDefined = true;
			}
			if(!$geometriesAlreadyDefined && $value['type'] == "geo_shape") {
				$fieldGeometries = $value['name'];
				$geometriesAlreadyDefined = true;
			}					  
		}

		foreach($query_params as $key => $value) {
		    if (preg_match($patternRefine,$key)){
		    	$filters_init[preg_replace($patternRefine,"",$key)] =  $value;

		        unset($query_params[$key]);
		        //echo preg_replace($pattern,"",$key);
		    }
		    if (preg_match($patternDisj,$key)){
		    	unset($query_params[$key]);
		    	//$disj[] = preg_replace($patternDisj,"",$key);
		    }
		    if (preg_match($patternBbox,$key)){
		    	unset($query_params[$key]);
		    	$filters_init[$key] =  $value;
		    	//$disj[] = preg_replace($patternDisj,"",$key);
		    }
			if (preg_match($patternDistance,$key)){
		    	unset($query_params[$key]);
		    	$filters_init[$key] =  $value;
		    }
			if (preg_match($patternSerie,$key)){
		    	unset($query_params[$key]);
		    }
			if ($key == "geofilter.polygon"){
		    	unset($query_params[$key]);
		    	$filters_init[$key] =  $value;
		    }
			if ($key == "geo_digest"){
		    	unset($query_params[$key]);
		    	$filters_init[$key] =  $value;
		    }
		    if($key == "format"){
				$globalFormat = $value;

		    	if($value == "json"){
		    		$format = "objects";
		    	} else if($value == "csv" || $value == "xls"){
		    		$format = "csv";
		    	} else if($value == "tsv"){
		    		$format = "tsv";
		    	} else if($value == "geojson"){
		    		$format = "objects";
		    	} else {
		    		$format = "objects";
		    	}
		    	unset($query_params[$key]);
		    }
		    if($key == "geo_simplify"){
				unset($query_params[$key]);
		    }
		    if($key == "geo_simplify_zoom"){
				unset($query_params[$key]);
		    }
		    if($key == "rows"){
				$query_params['limit'] = $value;
				unset($query_params['rows']);
		    }
			if($key == "fields"){
				$reqFields = $value;
				unset($query_params['fields']);
		    }
			if($key == "start"){
				$query_params['offset'] = $value;
				unset($query_params['start']);
				$query_params['limit'] = $query_params['limit'] + $query_params['offset'];
		    }
		    if($key == "q"){
		    	$reqQfilter = $this->constructReqQToSQL($value);
		    	//$pattern = '/and (\w+) /i';
		    	//preg_match($pattern,$reqQfilter,$qField); 
		    }
		    
		    if($key == "id" || $key == "basemap" || $key == "location" || $key == "datasetcard" || $key == "static" || $key == "scrollWheelZoom" || $key == "clusterdistance" || $key == "dataset"){
				unset($query_params[$key]);
		    }
		    if($key == "use_labels_for_header" || $key == "return_polygons" || $key == "calendarview" || $key == "dataChart"){
				unset($query_params[$key]);
		    }
		}
		
		if($reqFields == "") {
			$i = 0;
			foreach ($fields as $value) {
				if($i > 0) {
					$reqFields .= ',';
					
				}
				if($value['name'] != '_id' && $value['name'] != '_full_text') {
					$reqFields .= $value['name'];
					$i++;
				}
				
			}
		}
		
		unset($query_params["clusterprecision"]);
		unset($query_params["q"]);
		$where = "";$limit  = "";
		if(!empty($filters_init)){
			$where = " where ";
			foreach ($filters_init as $key => $value) {
				if($key == "geofilter.bbox"){
					$bbox = explode(',', $value);
					$minlat = $bbox[0];
					$minlong = $bbox[1];
					$maxlat = $bbox[2];
					$maxlong = $bbox[3];
					//$where .= "CAST(split_part(".$fieldCoordinates.",',',1)AS FLOAT) between " . $minlat . " and " . $maxlat . " and ";
					//$where .= "CAST(split_part(".$fieldCoordinates.",',',2)AS FLOAT) between " . $minlong . " and " . $maxlong . " and ";
					$where .= "box(point(" . $minlat . "," . $minlong . "),point(" . $maxlat . "," . $maxlong . ")) @> point(".$fieldCoordinates.") and ";
					$where .= $fieldCoordinates." not in ('', ',') and ";
				} else if($key == "geofilter.distance"){
					$coord = explode(',', $value);
					$lat = $coord[0];
					$long = $coord[1];
					if(count($coord)> 2){
						$dist = $coord[2];
						//$bbox = $this->getBbox($lat,$long,$dist);
						//$bbox = explode(',', $bbox);
						//$minlat = $bbox[0];
						//$minlong = $bbox[1];
						//$maxlat = $bbox[2];
						//$maxlong = $bbox[3];
						//$where .= "box(point(" . $minlat . "," . $minlong . "),point(" . $maxlat . "," . $maxlong . ")) @> point(".$fieldCoordinates.") and ";
						//$where .= "circle(point(" . $lat . "," . $long . "), " . $dist/100000 . ") @> point(".$fieldCoordinates.") and ";
						$where .= "circle(point(" . $lat . "," . $long . "), " . $this->getRadius($lat,$long,$dist) . ") @> point(".$fieldCoordinates.") and ";
						//$where .= "circle(polygon(path '(" . $this->getLosangePath($lat,$long,$dist) . ")')) @> point(".$fieldCoordinates.") and ";
					} else {
						//$where .= "CAST(split_part(".$fieldCoordinates.",',',1)AS FLOAT) = " . $lat . " and ";
						//$where .= "CAST(split_part(".$fieldCoordinates.",',',2)AS FLOAT) = " . $long . " and ";
						$where .= "point(" . $lat . "," . $long . ") ~= point(".$fieldCoordinates.") and ";
					}
					
					$where .= $fieldCoordinates." not in ('', ',') and ";
				} else if($key == "geo_digest"){
					$where .= "md5(".$fieldGeometries.") = '". $value . "' and ";
				} else if($key == "geofilter.polygon"){
					//polygon(path '((0,0),(1,1),(2,0))')
					$where .= "polygon(path '(" . $value . ")') @> point(".$fieldCoordinates.") and ";
					$where .= $fieldCoordinates." not in ('', ',') and ";
				} else {
					if(is_numeric($value) && $key != "insee_com" && $key != "code_insee"){
						$where .= $key . "=" . $value . " and ";
					} else if(is_array($value)){ 
						$where .= $key . " in (" . implode(',', array_map(array($this, 'quotesArrayValue'), str_replace("'", "''", $value))) . ") and ";
					} else {
						$where .= $key . "='" . str_replace("'", "''", $value) . "' and ";
					}
				}
				
				
			}
			$where = substr($where, 0, strlen($where)-4 );

			if($reqQfilter != NULL){
				$where .= $reqQfilter;
			}
		}
		else if($reqQfilter != NULL){
			$where = " where " . substr($reqQfilter, 5);
		}
		
		if(array_key_exists("limit", $query_params)){
			$limit = " limit ".$query_params['limit'];
		}

		
		$req = array();
		$sql = "Select ".$fieldId." as id from \"" . $query_params['resource_id'] . "\"" . $where . $limit;
		$req['sql'] = $sql;
		//echo $sql;
		$url2 = http_build_query($req);
		$callUrl =  $this->urlCkan . "api/action/datastore_search_sql?" . $url2;
		
		//echo $callUrl;
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		//echo $result . "\r\n";
		curl_close($curl);
		$result = json_decode($result,true);

		//We build the first row with the header's name
		$fieldsHeader = "";
		//set fields header to filter with
		$fieldsToExport = "";
		if ($reqFields != "") {
			$reqFieldsArray = explode(",", $reqFields);
		}

		$first = true;

		
		/* if exportField is not exist or is null, means, that the attributes names of dataset doest not changed by user, so assign the default name to fieldHeader value */

		foreach ($fields as $value) {
			$exportval = false;
			//We skip the column _full_text because we don't get the data and it is created by postgres
			if ($value['name'] == "_full_text") {
				continue;
			}

			foreach ($value["annotations"] as $keyAnnota => $annotat) {
				if($annotat["name"] == "exportApi") {
					$exportval = true;
					break;
					
				}

			}
			if($exportval) {
				$fieldsToExport .= (!$first ? ";" : "" ) . $value['name'];
					if (isset($reqFieldsArray)) {
						if (in_array($value['name'], $reqFieldsArray)) {
							if($exportField) {
								$fieldsHeader .= (!$first ? ";" : "" ) . $value['label'];
							}
							else {
								$fieldsHeader .= (!$first ? ";" : "" ) . $value['name'];
							}
							
						}
					}
					else {
						if($exportField) {
								$fieldsHeader .= (!$first ? ";" : "" ) . $value['label'];
							}
							else {
								$fieldsHeader .= (!$first ? ";" : "" ) . $value['name'];
								
							}
					}
					$first = false;
				}
		}


		$fieldsHeader .= "\n";
		$fieldsToExport .= "\n";

		$ids = array();
		foreach ($result["result"]["records"] as $value) {
			$ids[] = $value["id"];
		}
		$chunk = array_chunk($ids, 850);
		if($format == "objects"){
			$records = array();
		}
		

		
		foreach ($chunk as $_ids){
			$query_params['filters'] = json_encode(array($fieldId => $_ids));
			if($reqFields != ""){$query_params['fields'] = $reqFields;}
			$query_params['records_format'] = $format;
			if(!array_key_exists('limit', $query_params)){
				$query_params['limit'] = 100000000;
			}
			if($query_params['user_defined_fields']) {
				//$query_params = array_pop($query_params);
				unset($query_params['user_defined_fields']);
			}

			//search value in database by fields
			$fieldsheaderparams =  str_replace(";", ',', trim($fieldsToExport)) ;
			$paramsUrl = $query_params;
			$paramsUrl["fields"] = $fieldsheaderparams;

			$url2 = http_build_query($paramsUrl);

			//echo $url2;
			$callUrl =  $this->urlCkan . "api/action/datastore_search?" . $url2;

			//echo mb_strlen($callUrl , '8bit');				  
			$curl = curl_init($callUrl);
			curl_setopt_array($curl, $this->getStoreOptions());
			$result2 = curl_exec($curl);
			//echo $result2 . "\r\n";
			curl_close($curl);

	//		header('Content-Type:text/csv');
	//		header('Content-Disposition:attachment; filename=file.csv');
	//		echo json_encode(json_decode($result2,true)["result"]["records"]);	
			if($format == "objects"){
				$records = array_merge($records, json_decode($result2,true)["result"]["records"]);
			} else {
				// $records = array_merge($records, json_decode($result2,true)["result"]["records"]);
				error_log('aaaaaaaaaaaaaaaaaaaaaaaaaaaaa'.$callUrl );
				$records .=json_decode($result2,true)["result"]["records"];
			}
		
		}

		if($format == "objects"){
			$data_array = Export::getExport($globalFormat, $fieldGeometries, $fieldCoordinates, $records, $query_params, $ids);
			return json_encode($data_array);
			// return json_encode($records);
		} else {
			$records = chr(239) . chr(187) . chr(191) . $fieldsHeader . preg_replace('/,(?![^"]*",)/i', ';',$records);
			return 	$records;
		}
		
		
//		$response = new Response();
//		$response->setContent(json_encode($result));
//		$response->headers->set('Content-Type', 'application/octet-stream');
//		$response->headers->set("Content-Transfer-Encoding","Binary");
//		$response->headers->set("Content-Disposition","attachment; filename=file.csv");
//		$response->send();
	}

	public function getIndexFieldHeaders($array, $element) {
		$index="";
		foreach ($array as $key => $value) {
			if($value["id"] == $element){
				$index = $key;
			}
			
		}
		return $index ;
	}
	public function callDatastoreApiDownload($params) {
		$result = $this->getRecordsDownload($params);

		echo $result;
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	public function callDatastoreApiDownloadFile($params) {
		
		$query_params = $this->proper_parse_str($params);
		$format = $query_params['format'];

		if ($format == "csv") {
			header('Content-Type:text/csv');
			header('Content-Disposition:attachment; filename='.$query_params['resource_id'].'.csv');
		} else if ($format == "xls") {
			header('Content-Type:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			//header('Content-Disposition:attachment; filename='.$query_params['resource_id'].'.xls');
			header('Content-Disposition:attachment; filename='.$query_params['resource_id'].'.xlsx');
		} else if ($format == "json") {
			header('Content-Type:application/json');
			header('Content-Disposition:attachment; filename='.$query_params['resource_id'].'.json');
		} else if ($format == "geojson") {
			header('Content-Type:application/vnd.geo+json');
			header('Content-Disposition:attachment; filename='.$query_params['resource_id'].'.geojson');
		} else if ($format == "shp") {
			header('Content-Type:application/zip');
			header('Content-Disposition:attachment; filename='.$query_params['resource_id'].'.zip');
		} else if ($format == "kml") {
			header('Content-Type:application/vnd.google-earth.kml+xml');
			header('Content-Disposition:attachment; filename='.$query_params['resource_id'].'.kml');
		} else {
			header('Content-Type:application/json');
			header('Content-Disposition:attachment; filename='.$query_params['resource_id'].'.json');
		}

		$fields = $this->getAllFieldsForTableParam($query_params['resource_id'], 'true');


		$result = $this->getRecordsDownload($params);
	
		if ($format == "csv" || $format == "json" || $format == "geojson") {
			echo $result;
		} else if ($format == "xls") {
			//We create a tmp file in which we write the result and an output file to convert
			$pathInput = tempnam(sys_get_temp_dir(), 'input_convert_geo_file_');
			$fileInput = fopen($pathInput, 'w');
			fwrite($fileInput, $result);
			fclose($fileInput);

			//We rename the file because PhpSpreadsheet does not support conversion without
			rename($pathInput, $pathInput .= '.csv');

			$pathOutput = tempnam(sys_get_temp_dir(), 'output_convert_geo_file_');

			// $spreadsheet = new Spreadsheet();
			// $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
			
			// /* Set CSV parsing options */
			// $reader->setDelimiter(';');
			// $reader->setEnclosure('"');
			// $reader->setSheetIndex(0);
			
			// /* Load a CSV file and save as a XLS */
			// $spreadsheet = $reader->load($pathInput);
			// $writer = new Xls($spreadsheet);
			// $writer->save($pathOutput);
			// $spreadsheet->disconnectWorksheets();
			
			// unset($spreadsheet);
			
			$reader = ReaderFactory::create(Type::CSV);
			$reader->setFieldDelimiter(';');
			$reader->setFieldEnclosure('"');
			$reader->setEndOfLineCharacter("\r");
			
			$writer = WriterFactory::create(Type::XLSX);
			
			$reader->open($pathInput);
			$writer->openToFile($pathOutput); // write data to a file or to a PHP stream

			foreach ($reader->getSheetIterator() as $sheet) {
				foreach ($sheet->getRowIterator() as $row) {
					$writer->addRow($row);
				}
			}//$writer->addRows($multipleRows); // add multiple rows at a time

			$reader->close();
			$writer->close();

			header('Content-Length: ' . filesize($pathOutput));
			readfile($pathOutput);
		} else if ($format == "shp" || $format == "kml") {
			//We create a tmp file in which we write the result and an output file to convert
			$pathInput = tempnam(sys_get_temp_dir(), 'input_convert_geo_file_');
			$fileInput = fopen($pathInput, 'w');
			fwrite($fileInput, $result);
			fclose($fileInput);

			//Get current Php directory to call the script
			$dir = dirname(__FILE__);
			$scriptPath = $dir.'/convert_geo_files_ogr2ogr.sh';

			if ($format == "shp") {
				$typeConvert = 'ESRI Shapefile';

				//We create a temp directory
				$pathOutput = $this->tempdir(null, 'output_convert_geo_file_');
			} else if ($format == "kml") {
				$typeConvert = 'KML';
			
				//We create a temp file
				$pathOutput = tempnam(sys_get_temp_dir(), 'output_convert_geo_file_');
			}

			$command = $scriptPath." 2>&1 '".$typeConvert."' ".$pathOutput." ".$pathInput."";
			$message = shell_exec($command);

			if ($format == "kml") {
				header('Content-Length: ' . filesize($pathOutput));
				readfile($pathOutput);
			}
			else if ($format == "shp") {
				$pathOutputZip = tempnam(sys_get_temp_dir(), 'output_zip_convert_geo_file_');

				$zip = new ZipArchive();
				if ($zip->open($pathOutputZip, ZipArchive::CREATE) !== TRUE) {
					echo "Problem creating the zip file";
				}
				if ($handle = opendir($pathOutput)) {
					while (false !== ($entry = readdir($handle))) {
						if ($entry != "." && $entry != ".." && !strstr($entry,'.php')) {
							$zip->addFile($pathOutput."/".$entry, $entry);
						}
					}
					closedir($handle);
				}

				$zip->close();

				header('Content-Length: ' . filesize($pathOutputZip));
				readfile($pathOutputZip);
			}
		} else {
			echo $result;
		}

		$response = new Response();
		return $response;
	}

	/**
	 * Creates a random unique temporary directory, with specified parameters,
	 * that does not already exist (like tempnam(), but for dirs).
	 *
	 * Created dir will begin with the specified prefix, followed by random
	 * numbers.
	 *
	 * @link https://php.net/manual/en/function.tempnam.php
	 *
	 * @param string|null $dir Base directory under which to create temp dir.
	 *     If null, the default system temp dir (sys_get_temp_dir()) will be
	 *     used.
	 * @param string $prefix String with which to prefix created dirs.
	 * @param int $mode Octal file permission mask for the newly-created dir.
	 *     Should begin with a 0.
	 * @param int $maxAttempts Maximum attempts before giving up (to prevent
	 *     endless loops).
	 * @return string|bool Full path to newly-created dir, or false on failure.
	 */
	public function tempdir($dir = null, $prefix = 'tmp_', $mode = 0700, $maxAttempts = 1000) {
		/* Use the system temp dir by default. */
		if (is_null($dir))
		{
			$dir = sys_get_temp_dir();
		}

		/* Trim trailing slashes from $dir. */
		$dir = rtrim($dir, DIRECTORY_SEPARATOR);

		/* If we don't have permission to create a directory, fail, otherwise we will
		* be stuck in an endless loop.
		*/
		if (!is_dir($dir) || !is_writable($dir))
		{
			return false;
		}

		/* Make sure characters in prefix are safe. */
		if (strpbrk($prefix, '\\/:*?"<>|') !== false)
		{
			return false;
		}

		/* Attempt to create a random directory until it works. Abort if we reach
		* $maxAttempts. Something screwy could be happening with the filesystem
		* and our loop could otherwise become endless.
		*/
		$attempts = 0;
		do
		{
			$path = sprintf('%s%s%s%s', $dir, DIRECTORY_SEPARATOR, $prefix, mt_rand(100000, mt_getrandmax()));
		} while (
			!mkdir($path, $mode) &&
			$attempts++ < $maxAttempts
		);

		return $path;
	}
  
	public function callPackageShow3($params) {
		$query_params = $this->proper_parse_str($params);
		$datasetid = $query_params['id'];
		return $this->callPackageShow2($datasetid, $params);
	}
    
    
   


	public function getPackageShow2($datasetid,$params, $callCkan = true) {


        
        $result = '';
        
		if($callCkan) {
        
			$query_params = $this->proper_parse_str($params);
			//$callUrl =  $this->urlCkan . "api/action/package_show?" . $params . "&id=" . $datasetid;
			$callUrl =  $this->urlCkan . "api/action/package_show?id=" . $datasetid; //temporaire
			

			$curl = curl_init($callUrl);
			curl_setopt_array($curl, $this->getStoreOptions());
			$result = curl_exec($curl);
			curl_close($curl);
			//echo $callUrl. "\r\n";
			$result = json_decode($result,true);
		} else {
			$result = $datasetid;
		}
 
		$resourcesid = "";$isGeo = false;$resourceCSV=NULL;
		$alternative_exports = array(); 
		foreach ($result['result']['resources'] as $value) {
		 	if(($value['format'] == 'CSV' || $value['format'] == 'XLS' || $value['format'] == 'XLSX') && $value["datastore_active"] == true){
		 		$resourcesid = $value['id'];
				$resourceCSV = $value;
		 	}
		 	if($value['format'] == 'GeoJSON'){
		 		$isGeo = true;
		 	}
		}
		foreach ($result['result']['resources'] as $value) {
		 	if($resourceCSV == null || (($value['format'] != 'CSV' && $value['format'] != 'XLS' && $value['format'] != 'XLSX' && $value['format'] != 'GeoJSON' && $value['format'] != 'JSON' && $value['format'] != 'KML' && $value['format'] != 'SHP'))
				|| (($value['format'] == 'CSV' || $value['format'] == 'XLS' || $value['format'] == 'XLSX') && $value["datastore_active"] == false)){
				$a = array();
				$a["title"] = $value['name'];
				$a["format"] = $value['format'];
				$a["id"] = $value['id'];
				$a["description"] = $value['description'];
				$alternative_exports[] = $a;
			}
		}

		$data_array = array();
		$data_array['alternative_exports'] = $alternative_exports; //[]

		$data_array['attachments'] = array(); //[]
		$data_array['data_visible'] = true;
		$data_array['datasetid'] = $result['result']['name'];
		$data_array['extra_metas'] = array();
		$data_array['extra_metas']['explore'] = ''; //{"feedback_enabled": true, "file_field_download_count": 0, "popularity_score": 143.7, "reuse_count": 0, "api_call_count": 18317845, "download_count": 12126, "attachment_download_count": 0}
		$data_array['extra_metas']['processing'] = ''; //{"processing_modified": "2018-06-06T09:19:06+02:00", "records_size": 137993224.0, "security_last_modified": "2018-06-06T11:45:40+02:00"}	
				
		$visu = array();
		
		$visu['map_tooltip_html'] = $this->getMapTooltip(false, null);
		$visu['image_tooltip_html_enabled'] = false;
		$visu['map_marker_color'] = "#0e7ce3";//"#df0ee3";

		/*if($result['result']['name'] == 'observatoire_2g_3g_4g' || $result['result']['name'] == 'observatoire-2g'){
			$visu['map_tooltip_html_enabled'] = true; //true si page d'accueil, false sinon
		} else {
			$visu['map_tooltip_html_enabled'] = false; //true si page d'accueil, false sinon et les champs tooltip sont pris en compte
		}*/
		$visu['map_tooltip_html_enabled'] = false;
		$visu['map_tooltip_disabled'] = false;
		$visu['map_tooltip_fields'] =  array();
		$visu['map_tooltip_title'] = '';
		
		$visu['map_marker_picto'] = "dot";
		$visu['map_marker_hidemarkershape'] = true;
		
		foreach($result['result']['extras'] as $value){
			if($value["key"] == "type_map"){
				$visu["default_map"] = $value["value"];
			}
			if($value["key"] == "overlays"){
				$visu["overlays"] = $value["value"];
			}
			if($value["key"] == "tooltip"){
				$val = json_decode($value["value"], true);
				if($val["type"] == "html"){
					$visu['map_tooltip_html_enabled'] = true;
					$visu['map_tooltip_html'] = $this->getMapTooltip(true, $val["value"]);
				} else {
					$visu['map_tooltip_html_enabled'] = false;
					$visu['map_tooltip_fields'] =  explode(",", $val["value"]["fields"]);
					$visu['map_tooltip_title'] = $val["value"]["title"];
				}
				
			}
			if($value["key"] == "reports"){
				$visu['map_tooltip_html_enabled'] = true;
				$visu["reports"] = json_decode($value["value"]);
			}
			if($value["key"] == "records_count"){
				$data_array["metas"]["data_visible"] = $data_array["data_visible"];
				$data_array["metas"]["records_count"] = floatval($value["value"]);
			}
			if($value["key"] == "features"){
				$data_array['features'] = explode(",", $value["value"]);
			}
			if($value["key"] == "Picto"){
				$visu['map_marker_picto'] = $value["value"];
			}
			if($value["key"] == "FieldColor" && $value["value"] != ''){

				$visu['map_marker_color'] = array();
				$visu['map_marker_color']['type'] = "field";
				$visu['map_marker_color']['field'] = $value["value"];

				// $ranges = array();
				// $ranges["10"] = "aqua";
				// $ranges["20"] = "coral";
				// $ranges["10000"] = "chartreuse";

				// $visu['map_marker_color'] = array();
				// $visu['map_marker_color']['type'] = "choropleth";
				// $visu['map_marker_color']['field'] = $value["value"];
				// $visu['map_marker_color']['ranges'] = $ranges;
			}
		}
		
		if($resourcesid == ""){
			$visu['table_fields'] =  array();
			//$visu['map_tooltip_fields'] =  array();
			//$visu['map_tooltip_title'] = '';
			
			$data_array['fields'] =array();
		} else {
			$visu['table_fields'] = $this->getTableFields($resourcesid); //["code_insee","en_service","mutualisation_public","sup_id","mutualisation","nom_reg","nom_com","nom_dept"]
			if(count($visu['map_tooltip_fields']) == 0){
				$visu['map_tooltip_fields'] = $this->getMapTooltipFields($resourcesid);// ["emr_lb_systeme","emr_dt_service","generation","coord","nom_com","nom_dept","nom_reg"]fields
				$visu['map_tooltip_title'] = $visu['map_tooltip_fields'][0];
			}
			
			
			$data_array['fields'] = $this->getAllFields($resourcesid, TRUE, FALSE);
		}
		
		
		$visu['calendar_tooltip_html_enabled'] = false;
		$visu['analyze_default'] = ''; //"{\"queries\":[{\"charts\":[{\"type\":\"line\",\"func\":\"COUNT\",\"color\":\"range-Accent\",\"scientificDisplay\":true}],\"xAxis\":\"nom_com\",\"maxpoints\":\"\",\"timescale\":null,\"sort\":\"\",\"seriesBreakdown\":\"emr_lb_systeme\"}],\"timescale\":\"\",\"displayLegend\":true,\"alignMonth\":true}"
				
		//$data_array['features'] = array(); //["timeserie", "analyze", "geo", "image", "calendar", "custom_view","wordcloud"]
		//$data_array['features'][] = "analyze"; //tab chart
		if($isGeo){
			$data_array['features'][] = "geo"; //tab map 
		}
		//$data_array['features'][] = "analyze"; //tab chart 
		//$data_array['features'][] = "timeline"; //tab timeline 
		//$data_array['features'][] = "timeserie"; //unknown tab
        
        if(count($data_array['fields'])>0){
			$colStart = null;$colEnd = null;$colWordCount = null;$colTimeline=null;
			foreach($data_array['fields'] as $f){
				foreach($f["annotations"] as $a){
					if($a["name"] == "startDate"){
						$colStart = $f["name"];
					} else if($a["name"] == "endDate"){
						$colEnd = $f["name"];
					} else if($a["name"] == "date"){
						$colEnd = $f["name"];$colStart = $f["name"];
					} else if($a["name"] == "wordcount" || $a["name"] == "wordcountNumber"){
						$colWordCount = $f["name"];
					}
//                    else if($a["name"] == "timeline"){
//						$colTimeline = $f["name"];
//					}
				}
				if($colEnd != null && $colStart != null){
					//break;
				}
				if($f["type"] == "file"){
					//$data_array['features'][] = "image";
					$visu['image_title'] = $visu['map_tooltip_title'];
					$visu['image_fields'] = $visu['map_tooltip_fields'];
				}
			}
			
			if($colStart != null && $colEnd != null){
				//$data_array['features'][] = "timeserie";
				//$data_array['features'][] = "calendar";
				
				$visu['calendar_enabled'] = true;
				$visu['calendar_available_views'] = "month,agendaWeek,agendaDay";
				$visu['calendar_event_color'] = "#C32D1C";
				$visu['calendar_event_end'] = $colEnd;
				$visu['calendar_event_title'] = $visu['map_tooltip_title'];
				$visu['calendar_default_view'] = "month";
				$visu['calendar_event_start'] = $colStart;
				$visu['calendar_tooltip_html_enabled'] = $visu['map_tooltip_html_enabled'];
				$visu['calendar_tooltip_fields'] = $visu['map_tooltip_fields'];
			} else {
				$visu['calendar_enabled'] = false;
			}
			
			if($colWordCount != null){
				//$data_array['features'][] = "wordcloud";
				$visu['wordcloud_field'] = $colWordCount;
			}
            
//            if($colTimeline != null){
//				$data_array['features'][] = "timeline";
//				$visu['timeline_field'] = $colTimeline;
//			}
		}
        
        // a commenté la ligne parce qu'il y a quelques problèmes avec  CustomView !!!!!!!!!!!!
        
        
		$customView = $this->getCustomView($result['result']['id']);
		if($customView){ // TODO custom_view search
		
			//$data_array['features'][] = "custom_view";
			$visu["custom_view_title"] = $customView->cv_title;
			$visu["custom_view_slug"] = $customView->cv_name;
			$visu["custom_view_icon"] = $customView->cv_icon;
			$i = $customView->cv_template;$html = "";
			foreach($customView->html as $key => $obj){
				$customView->html[$key]->cvh_html = str_replace("d4c-chart ", 'd4c-chart d4c-order="'.$obj->cvh_order.'" ', $obj->cvh_html);
			}
			if ($i == 1) {
				if(isset($customView->html[0])){
					$html = $customView->html[0]->cvh_html;
				}
			} elseif ($i == 2) {
				if(isset($customView->html[0])){
					$html .= '<div class="col-md-12 col-sm-12"><div class="d4c-box">'.$customView->html[0]->cvh_html.'</div></div>';
				}
				if(isset($customView->html[1])){
					$html .= '<div class="col-md-12 col-sm-12"><div class="d4c-box">'.$customView->html[1]->cvh_html.'</div></div>';
				}
			} elseif ($i == 3) {
				$html .= '<div class="row">';
				if(isset($customView->html[0])){
					$html .= '<div class="col-md-12 col-sm-12"><div class="d4c-box">'.$customView->html[0]->cvh_html.'</div></div>';
				}
				if(isset($customView->html[1])){
					$html .= '<div class="col-md-12 col-sm-12"><div class="d4c-box">'.$customView->html[1]->cvh_html.'</div></div>';
				}
				$html .= '</div>';
				$html .= '<div class="row"><div class="d4c-box">';
				if(isset($customView->html[2])){
					$html .= $customView->html[2]->cvh_html;
				}
				$html .= '</div></div>';
			} elseif ($i == 4) {
				$html .= '<div class="row">';
				if(isset($customView->html[0])){
					$html .= '<div class="col-md-12 col-sm-12"><div class="d4c-box">'.$customView->html[0]->cvh_html.'</div></div>';
				}
				if(isset($customView->html[1])){
					$html .= '<div class="col-md-12 col-sm-12"><div class="d4c-box">'.$customView->html[1]->cvh_html.'</div></div>';
				}
				$html .= '</div>';
				$html .= '<div class="row">';
				if(isset($customView->html[2])){
					$html .= '<div class="col-md-12 col-sm-12"><div class="d4c-box">'.$customView->html[2]->cvh_html.'</div></div>';
				}
				if(isset($customView->html[3])){
					$html .= '<div class="col-md-12 col-sm-12"><div class="d4c-box">'.$customView->html[3]->cvh_html.'</div></div>';
				}
				$html .= '</div>';
			}
			/*$visu["custom_view_html"] = '<d4c-dataset-context context="prixdescarburants" prixdescarburants-dataset="prix-des-carburants">
											<d4c-map no-refit="true" scroll-wheel-zoom="false" display-control="true" search-box="true" toolbar-fullscreen="true" toolbar-geolocation="true" basemap="undefined" location="8,45.34828,4.10065">
												<d4c-map-layer-group>
													<d4c-map-layer context="prixdescarburants" color-categories="{\'R\':\'#6D7A87\',\'A\':\'#619FC8\',\'N\':\'#F7C87E\'}" color-by-field="presence" color-categories-other="#C32D1C" picto="d4c-cow" show-marker="false" display="categories" shape-opacity="0.5" point-opacity="1" border-color="#FFFFFF" border-opacity="1" border-size="1" border-pattern="solid" caption="true" title="Prix des carburants" size="3"></d4c-map-layer>
												</d4c-map-layer-group>
											</d4c-map>

										</d4c-dataset-context>';
			*/
			$visu["custom_view_html"] = $html;
		}
		$visu['custom_view_enabled'] = $customView != null;

		$data_array['extra_metas']['visualization'] = $visu;
		
		$data_array['has_records'] = true;
		$data_array['metas'] = $result['result'];
		
		$data_array["metas"]["domain"]="";
		$data_array["metas"]["language"]="fr";
		//$data_array["metas"]["title"]=$result["result"]["name"];
		$desc = str_replace(PHP_EOL, '<br>', $result["result"]["notes"]);
		$data_array["metas"]["description"]= $desc;
		$data_array["metas"]["modified"] = $this->findMostRecentDate(current(array_filter($result["result"]["extras"], function($f){ return $f["key"] == "date_moissonnage_last_modification";}))["value"], $result["result"]["metadata_modified"]);
		$data_array["metas"]["visibility"]="domain";
		$data_array["metas"]["metadata_processed"]=$result["result"]["metadata_created"];
		$data_array["metas"]["license"]=$data_array["metas"]["license_title"];
		//$data_array["metas"]["data_processed"]="2018-07-05T12:07:03+00:00";
		$data_array["metas"]["publisher"]=$data_array["metas"]["organization"]["title"];
		foreach($data_array['metas']['extras'] as $value){
			if($value["key"] == "theme"){
				$data_array['metas']["theme"] = str_replace(",", ", ", $value["value"]);
			}
		}
        $result["result"]["results"][$i]["metadata_imported"] = $result["result"]["results"][$i]["metadata_modified"];
			$result["result"]["results"][$i]["metadata_modified"] = current(array_filter($result["result"]["results"][$i]["extras"], function($f){ return $f["key"] == "date_moissonnage_last_modification";}))["value"] ?: $result["result"]["results"][$i]["metadata_modified"];
			$result["result"]["results"][$i]["metadata_created"] = current(array_filter($result["result"]["results"][$i]["extras"], function($f){ return $f["key"] == "date_moissonnage_creation";}))["value"] ?: $result["result"]["results"][$i]["metadata_created"];
//        foreach($data_array['metas']['extras'] as $value){
//			if($value["key"] == "LinkedDataSet"){
//                
//                
//                
//				//$data_array['metas']["LinkedDataSet"] = str_replace(";", "; ", $value["value"]);
//                
//                $name_id =explode(";", $value["value"]);
//                     
//                for($t=0; $t < count($name_id); $t++){
//                    
//                    $a = (":", $name_id[$i]);
//                    
//                    $data_array['metas']["LinkedDataSet"][$a[0]]=$data_array['metas']["LinkedDataSet"][$a[1]];
//                    
//                 
//                }
//                 
//                
//			}
//		}
		if(count($data_array["metas"]["tags"]) > 0){
			$data_array["metas"]["keyword"]=array_column($data_array["metas"]["tags"],"display_name");
		} else {
			$data_array["metas"]["keyword"] = array();
		}
		

        /*if($resourceCSV != NULL){
			$records_result = $this->getDatastoreRecord_v2("dataset=".$data_array['datasetid']."&rows=1");
			
			$data_array["metas"]["data_visible"] = $data_array["data_visible"];
			$data_array["metas"]["records_count"] = $records_result["nhits"];
		}*/
        
		return $data_array;
	}

	public function findMostRecentDate($firstDateStr, $lastDateStr) {
		if ($firstDateStr) {
			$firstDate = strtotime($firstDateStr);
			$lastDate = strtotime($lastDateStr);
			if ($firstDate > $lastDate) {
				return $firstDateStr;
			}
		}

		return $lastDateStr;
	}

	public function callPackageShow2($datasetid,$params) {
        


		$res = $this->getPackageShow2($datasetid,$params);
        
        //$rendered_message = \Drupal\Core\Render\Markup::create('<pre>' . $res . '</pre>');
        //drupal_set_message($rendered_message);
        //error_log('dsfsdfsdsd');
        
		echo json_encode($res);
		$response = new Response();
		//$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}	   
  
	public function callDatastoreApiGeoClusterOld($params) {
		
		$params = $this->retrieveParameters($params);

		$patternRefine = '/refine./i';
		$patternDisj = '/disjunctive./i';
		/*$patternBbox = '/geofilter.bbox/i';*/
		$patternSerie = '/y.serie/i';
		$filters_init = array();
		$fieldId = "id";
		$fieldCoordinates="";
		$reqQfilter="";
		$ySeries = array();

		//echo $params . "\r\n";
		$query_params = $this->proper_parse_str($params);


		foreach($query_params as $key => $value) {
		    if (preg_match($patternRefine,$key)){
		    	$filters_init[preg_replace($patternRefine,"",$key)] =  $value;

		        unset($query_params[$key]);
		        //echo preg_replace($pattern,"",$key);
		    }
		    if (preg_match($patternDisj,$key)){
		    	unset($query_params[$key]);
		    	$disj[] = preg_replace($patternDisj,"",$key);
		    }

			if($key == "q"){
		    	$reqQfilter = $this->constructReqQToSQL($value);
		    	//$pattern = '/and (\w+) /i';
		    	//preg_match($pattern,$reqQfilter,$qField); 
		    }
			if (preg_match($patternSerie,$key)){
		    	$var = explode('.', $key);
				$nom = $var[1];
				$app = $var[2];
				
				if(array_key_exists($nom, $ySeries)){
					$ySeries[$nom][$app] = $value;
				} else {
					$ySeries[$nom]["name"] = $nom;
					$ySeries[$nom][$app] = $value;
				}
		    }
		}


		$clusterDistance = 50;
		if(array_key_exists("clusterdistance", $query_params)){
			$clusterDistance = $query_params["clusterdistance"];
		}
		unset($query_params["clusterdistance"]);

		$clusterPrec = 5;
		if(array_key_exists("clusterprecision", $query_params)){
			$clusterPrec = $query_params["clusterprecision"]-2;
		}
		unset($query_params["clusterprecision"]);

		$return_polygons = false;
		if(array_key_exists("return_polygons", $query_params)){
			$clusterDistance = $query_params["return_polygons"];
		}
		unset($query_params["return_polygons"]);
		

		$geofilter_bbox = explode(",", $query_params["geofilter.bbox"]);
		unset($query_params["geofilter.bbox"]);
		unset($query_params["q"]);
		$datasetId = $query_params["dataset"];
//		unset($query_params["dataset"]);

		if(array_key_exists("geofilter.distance", $query_params) || array_key_exists("geofilter.polygon", $query_params)){
			
			$callUrl =  $this->urlCkan . "api/action/package_show?id=" . $datasetId;
			$curl = curl_init($callUrl);
			curl_setopt_array($curl, $this->getStoreOptions());
			$package = curl_exec($curl);
			//echo $package . "\r\n";
			curl_close($curl);
			$package = json_decode($package, true);
			foreach ($package['result']['resources'] as $value) { 
			 	if(($value['format'] == 'CSV' || $value['format'] == 'XLS' || $value['format'] == 'XLSX') && $value["datastore_active"] == true){ 
			 		$resourceCSV = $value['id'];
			 		break;
			 	}
			}
			
			/*if(count($ySeries)>0){
				$fields = $this->getAllFields($resourceCSV);
				$fieldId = "_id";
				foreach ($fields as $value) {
					if(preg_match("/id|num|code|siren/i",$value['name'])){
						$fieldId = $value['name'];
						break;
					} 
				}
			}*/

			$geojson = $this->getRecordsDownload($params."&format=geojson&resource_id=".$resourceCSV);
			//echo $geojson . "\r\n";
			$arr = array();
			$arr["data"] = $geojson;
			$arr["zoom"] = $clusterPrec;
			/*$ckanurl = $this->urlCkan;
			if(substr($ckanurl, -1) == "/"){
				$ckanurl = substr($ckanurl, 0, -1);
			}
			$callUrl =  $ckanurl.":1337/clusterDirect";*/
			$callUrl = $this->config->cluster->url . "clusterDirect";
			//echo $callUrl;
			$curl = curl_init($callUrl);
			curl_setopt_array($curl, array(
				CURLOPT_POST => TRUE,
				CURLOPT_RETURNTRANSFER => TRUE,
				CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST =>  0,
				CURLOPT_POSTFIELDS =>  json_encode($arr)
			));
			//curl_setopt_array($curl, $this->getSimpleGetOptions());
			$dataset = curl_exec($curl);
			curl_close($curl);
			//echo $dataset . "\r\n";
			$dataset = json_decode($dataset, true);
			$data_array = array();
			$clusters = array();
			$allFeatures = json_decode($geojson, true)["features"];
			foreach ($dataset["features"] as $value) {
				$c = array();
				$c["clusters"] = array();
				$c["cluster_center"] = array_reverse($value["geometry"]["coordinates"]);
				$c["count"] = $value["properties"]["point_count"];//echo $c["count"]. "\r\n";
				if($c["count"] == NULL) $c["count"]=1;
				
				if(count($ySeries) > 0){
					$c["series"] = array();
					$serFilteredValues = array();
					if($c["count"] > 1){
						$ids = array_flip($value["properties"]["ids"]);
						foreach($allFeatures as $f){
							if(isset($ids[$f["properties"]["_id"]])){
								$serFilteredValues[] = $f["properties"];
							}
						}
					} else {
						$serFilteredValues[] = $value["properties"];
					}
					
					foreach($ySeries as $y){
						$colValues = array_column($serFilteredValues,$y["expr"]);
						$func = $y["func"];
						switch ($func) {
							case "AVG":
								$f = 0;
								if(count($colValues)){
									$f = array_sum($colValues) / count($colValues);
								} 
								break;
							case "MIN":
								$f = min($colValues);
								break;
							case "MAX":
								$f = max($colValues);
								break;
							case "STDDEV":
								$f = 0;
								if(count($colValues)>1){
									$rr = function($x, $mean) { return pow($x - $mean,2); };
									$f = sqrt(array_sum(array_map($rr, $colValues, array_fill(0,count($colValues), (array_sum($colValues) / count($colValues)) ) ) ) / (count($colValues)-1) );
								}
								break;
							case "SUM":
								$f = array_sum($colValues);
								break;
							default:
								$f = max($colValues);
								break;
						}
						$c["series"][$y["name"]] = $f; 
					}
				}
				
				$clusters[] = $c;
			}
			$data_array['clusters'] = $clusters;
		
			$data_array['clusterprecision'] = $clusterPrec;
			$data_array['series'] = array();
			foreach($ySeries as $y){
				$data_array["series"][$y["name"]] = array();
				$values = array_column(array_column($clusters, 'series'), $y["name"]);
				$data_array["series"][$y["name"]]["min"] = min($values);
				$data_array["series"][$y["name"]]["max"] = max($values);
			}
			$data_array['count'] = array();
			$counts = array_column($clusters, 'count');
			$data_array['count']['min'] = min($counts);
			$data_array['count']['max'] = max($counts);
			

			echo json_encode( $data_array );
			
			$response = new Response();
	//		$response->setContent(json_encode($result));
			$response->headers->set('Content-Type', 'application/json');
			return $response;
		}

		$query = "idRes=".$datasetId."&zoom=".$clusterPrec."&minLat=".$geofilter_bbox[0]."&minLong=".$geofilter_bbox[1]."&maxLat=".$geofilter_bbox[2]."&maxLong=".$geofilter_bbox[3];
		/*$ckanurl = $this->urlCkan;
		if(substr($ckanurl, -1) == "/"){
			$ckanurl = substr($ckanurl, 0, -1);
		}
		$callUrl =  "http://192.168.2.223:1337/cluster?".$query;
		//$callUrl =  "https://anfr2.data4citizen.com:1337/cluster?".$query;*/
		
		$callUrl = $this->config->cluster->url . "cluster?".$query;
		
		//echo $callUrl . "\r\n";
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getSimpleGetOptions());
		$dataset = curl_exec($curl);
		curl_close($curl);

		//echo $dataset . "\r\n";
		$dataset = json_decode($dataset, true);
		if(empty($dataset["features"])) {
			if($clusterPrec < 20) {
				$params = str_replace('clusterprecision=' . ($clusterPrec + 2), 'clusterprecision=' . ($clusterPrec + 4), $params);
				//error_log($params);
				return $this->callDatastoreApiGeoClusterOld($params);
			}
		}

		//recup resourceCSV
		$resourceCSV; 
		$maxCount;
		$array_filter_id;
		$result2;
				 
		if(count($filters_init) > 0 || $reqQfilter != "" || count($ySeries) > 0){
			$callUrl =  $this->urlCkan . "api/action/package_show?id=" . $datasetId;
			$curl = curl_init($callUrl);
			curl_setopt_array($curl, $this->getStoreOptions());
			$package = curl_exec($curl);
			//echo $package . "\r\n";
			curl_close($curl);
			$package = json_decode($package, true);
			foreach ($package['result']['resources'] as $value) { 
			 	if(($value['format'] == 'CSV' || $value['format'] == 'XLS' || $value['format'] == 'XLSX') && $value["datastore_active"] == true){ 
			 		$resourceCSV = $value['id'];
			 		break;
			 	}
			}
			unset($query_params['dataset']);
			$query_params['resource_id'] = $resourceCSV;

			$fields = $this->getAllFields($query_params['resource_id']);
			//echo json_encode($fields);
			$fieldId = "_id";
			/*foreach ($fields as $value) {
				if(preg_match("/id|num|code|siren/i",$value['name']) && $value['name'] != "_id"){
					$fieldId = $value['name'];
					break;
				} 
			}*/
			foreach ($fields as $value) {
				if($value['type'] == "geo_point_2d") $fieldCoordinates = $value['name'];
			}
			
			//series
			$series = "";
			if(count($ySeries) > 0){
				foreach($ySeries as $y){
					if($y["expr"] == NULL){
						$y["expr"] = "*";
					}
					/*
					 func
					 Somme : y.serie1.expr=prix_gazole&y.serie1.func=SUM
					 Ecart Type : y.serie1.expr=prix_gazole&y.serie1.func=STDDEV
					 Maximum : y.serie1.expr=prix_gazole&y.serie1.func=MAX
					 Minimum : y.serie1.expr=prix_gazole&y.serie1.func=MIN
					 Moyenne : y.serie1.expr=prix_gazole&y.serie1.func=AVG
					*/
					$f = "string_agg(".$y["expr"]."::text,',') as ". $y["name"].",";
					$series .= $f;
				}
				$series = ", ". substr($series, 0, -1);
			}
			

			//where
			$where = "";
			if(!empty($filters_init)){
				$where = " where ";
				foreach ($filters_init as $key => $value) {
					if(is_numeric($value) && $key != "insee_com" && $key != "code_insee"){
						$where .= $key . "=" . $value . " and ";
					} else if(is_array($value)){ 
						$where .= $key . " in (" . implode(',', array_map(array($this, 'quotesArrayValue'), str_replace("'", "''", $value))) . ") and ";
					} else {
						$where .= $key . "='" . str_replace("'", "''", $value) . "' and ";
					}
					
				}
				$where = substr($where, 0, strlen($where)-4 );
				if($reqQfilter != ""){
					$where .= $reqQfilter;
				}
			}
			else if($reqQfilter != ""){
				$where = " where " . substr($reqQfilter, 5);
			}
		 
			$req = array();
			$sql = "Select string_agg(".$fieldId."::text,',') as agg". $series ." from \"" . $query_params['resource_id'] . "\"" . $where ;
			$req['sql'] = $sql;
			//echo $sql;
			$url2 = http_build_query($req);
			$callUrl =  $this->urlCkan . "api/action/datastore_search_sql?" . $url2;
			//echo $callUrl. "\r\n";
			$curl = curl_init($callUrl);
			curl_setopt_array($curl, $this->getStoreOptions());
			$result2 = curl_exec($curl);
			//echo $result2 . "\r\n";
			curl_close($curl);
			$result2 = json_decode($result2, true);
			//$array_filter_id = array_column($result2["result"]["records"], $fieldId);//echo count($array_filter_id). "\r\n";
			$array_filter_id = explode(",",$result2["result"]["records"][0]["agg"]);//echo count($array_filter_id). "\r\n";

		}
		

		$data_array = array();
		
		$clusters = array();
		foreach ($dataset["features"] as $value) {
			$c = array();
			$c["clusters"] = array();
			$c["cluster_center"] = array_reverse($value["geometry"]["coordinates"]);
			//$c["cluster_center"] = $value["geometry"]["coordinates"];
			$c["count"] = $value["properties"]["point_count"];//echo $c["count"]. "\r\n";
			if($c["count"] == NULL) $c["count"]=1;
			
			$ids = array();
			foreach ($value["properties"]["ids"] as $v) {
				$ids[] = $v;
			}
			
			if(count($filters_init) > 0 || $reqQfilter != "" || count($ySeries) > 0){
			
				//$array = array_intersect($array_filter_id, $ids);
				$index = array_flip($array_filter_id);					   
				$second = array_flip($ids);

				$x = array_intersect_key($index, $second);
				$array = array_flip($x);
				
				//$array_filter_id = array_diff($array_filter_id, $ids);							  
				$c["count"] = count($array);//echo $c["count"]. "\r\n";
				if($c["count"] == 1){
					$id = array_values($array)[0]; 
					$req = array();
					$sql = "Select ".$fieldCoordinates." as coord from \"" . $resourceCSV . "\" where " . $fieldId . "=".$id;
					$req['sql'] = $sql;
					//echo $sql;
					$url2 = http_build_query($req);
					$callUrl =  $this->urlCkan . "api/action/datastore_search_sql?" . $url2;
					//echo $callUrl. "\r\n";
					$curl = curl_init($callUrl);
					curl_setopt_array($curl, $this->getStoreOptions());
					$record = curl_exec($curl);
					//echo $result . "\r\n";
					curl_close($curl);
					$record = json_decode($record,true);
					//echo $record["result"]["records"][0]["coord"];						
					$c["cluster_center"] = array_map('floatval', explode(',', $record["result"]["records"][0]["coord"]));
				}
				
				if(count($ySeries) > 0){
					$c["series"] = array();
					foreach($ySeries as $y){
						$serAllValues = explode(",",$result2["result"]["records"][0][$y["name"]]);
						$serFilteredValues = array_intersect_key($serAllValues, $array);
						$func = $y["func"];
						switch ($func) {
							case "AVG":
								$f = 0;
								if(count($serFilteredValues)){
									$f = array_sum($serFilteredValues) / count($serFilteredValues);
								} 
								break;
							case "MIN":
								$f = min($serFilteredValues);
								break;
							case "MAX":
								$f = max($serFilteredValues);
								break;
							case "STDDEV":
								$f = 0;
								/*$population = count($serFilteredValues);
								if ($population != 0) {
									$somme_tableau = array_sum($serFilteredValues);
									$moyenne = $somme_tableau / $population;
									$somme_ecart = 0.0;
									for ($i = 0; $i < $population; $i++){
										$ecart_donnee = $serFilteredValues[$i] - $moyenne;
										$somme_ecart += $ecart_donnee*$ecart_donnee;
									}//echo json_encode($ecart);
									//$somme_ecart = array_sum($ecart);
									$division = $somme_ecart / $population;
									$f = sqrt ($division);
								}*/
								//stats_standard_deviation($serFilteredValues);
								$rr = function($x, $mean) { return pow($x - $mean,2); };
								$f = sqrt(array_sum(array_map($rr, $serFilteredValues, array_fill(0,count($serFilteredValues), (array_sum($serFilteredValues) / count($serFilteredValues)) ) ) ) / (count($serFilteredValues)-1) );
								break;
							case "SUM":
								$f = array_sum($serFilteredValues);
								break;
							default:
								$f = max($serFilteredValues);
								break;
						}
						$c["series"][$y["name"]] = $f; 
					}
				}
			}
			if($c["count"] != 0){
				$clusters[] = $c;
			}
		}
		//echo count($array_filter_id);asort($array_filter_id);print_r($array_filter_id);
		$data_array['clusters'] = $clusters;
		
		$data_array['clusterprecision'] = $clusterPrec;
		$data_array['series'] = array();
		foreach($ySeries as $y){
			$data_array["series"][$y["name"]] = array();
			$values = array_column(array_column($clusters, 'series'), $y["name"]);
			$data_array["series"][$y["name"]]["min"] = min($values);
			$data_array["series"][$y["name"]]["max"] = max($values);
		}
		$data_array['count'] = array();
		$counts = array_column($clusters, 'count');
		$data_array['count']['min'] = min($counts);
		$data_array['count']['max'] = max($counts);
		

		echo json_encode( $data_array );
		$response = new Response();
//		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

													  
	public function callDatastoreApiGeoPreview($params) {

		$query_params = $this->proper_parse_str($params);
		
		$fields = $this->getAllFields($query_params['resource_id']);
		
		$fieldGeometries="";
		foreach ($fields as $value) {
			/*if($value['id'] == "geo_shape") $fieldGeometries = $value['id'];
			if(preg_match("/geometr/i",$value['id'])) $fieldGeometries = $value['id'];*/
			if($value['type'] == "geo_shape") $fieldGeometries = $value['name'];
		}

		//$result = $this->getRecordsDownload($params . "&format=json");
		//echo $result;
		//$result = json_decode($result, true);

		if(array_key_exists("rows", $query_params)){
			$limit = " limit ".$query_params['rows'];
		}

		$where = $this->getSQLWhereRecordsDownload($params);
		$req = array();
		// $sql = "Select cast(".$fieldGeometries."::json->'type' as text) as geo from \"" . $query_params['resource_id'] . "\"" . $where . $limit;
		$sql = "Select ".$fieldGeometries." as geo from \"" . $query_params['resource_id'] . "\"" . $where . $limit;
		$req['sql'] = $sql;

		Logger::logMessage("Geopreview query : " . $req['sql']);

		$url2 = http_build_query($req);
		$callUrl =  $this->urlCkan . "api/action/datastore_search_sql?" . $url2;
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		
		curl_close($curl);
		$result = json_decode($result,true);
		
		
		$data_array = array();
		foreach ($result["result"]["records"] as $value) {
		
			// $res = array();
			// foreach ($value as $key => $val) {
			// 	if ($key == 'geo') {
			// 		Logger::logMessage("Found value : " . $val);
			// 		Logger::logMessage("Encode value : " . json_decode($val) . "\r\n");

			// 		$error = '';
			// 		switch (json_last_error()) {
			// 			case JSON_ERROR_NONE:
			// 				$error = ' - No errors';
			// 			break;
			// 			case JSON_ERROR_DEPTH:
			// 				$error = ' - Maximum stack depth exceeded';
			// 			break;
			// 			case JSON_ERROR_STATE_MISMATCH:
			// 				$error = ' - Underflow or the modes mismatch';
			// 			break;
			// 			case JSON_ERROR_CTRL_CHAR:
			// 				$error = ' - Unexpected control character found';
			// 			break;
			// 			case JSON_ERROR_SYNTAX:
			// 				$error = ' - Syntax error, malformed JSON';
			// 			break;
			// 			case JSON_ERROR_UTF8:
			// 				$error = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
			// 			break;
			// 			default:
			// 				$error = ' - Unknown error';
			// 			break;
			// 		}

			// 		Logger::logMessage("Error : " . $error . "\r\n");
			// 		$res['geo_digest'] = md5($val); //3566411980376893035
			// 		// try{
			// 		// 	Logger::logMessage("Found geo : " . $val);
		
			// 		// 	$res['geometry'] = json_decode($val, true); 
			// 		// } catch(Exception $e){
			// 		// 	Logger::logMessage("Found geo with cast error : " . $val);
		
			// 			$res['geometry'] = $val; 
			// 		// }
			// 	}
			// }
			$res = array();
			//echo json_encode( $value );
			$res['geo_digest'] = md5($value["geo"]); //3566411980376893035
			try{
				// Logger::logMessage("Found geo  : " . $value["geo"]);
			    $res['geometry'] = json_decode($value["geo"], true); 
			} catch(Exception $e){
				// Logger::logMessage("Found geo with cast error : " . $value["geo"]);
			    $res['geometry'] = $value["geo"]; 
			}
			

			$data_array[] = $res;
		}
		
		echo json_encode( $data_array );
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}												  
				   
  

	public function getMapTooltip($hasCustomHtml, $html){
		$res = "<div class=\"tooltipcustom\">";
		if($hasCustomHtml){
			$res = $html;
		} else {
			$res = "<h2 class=\"d4cwidget-map-tooltip__header\" ng-show=\"!!getTitle(record)\"><span ng-bind=\"getTitle(record)\"></span></h2>".
			"<ul style=\"display: block; list-style-type: none; color: #2c3f56; padding:0; margin:0;\">".
			"<li  ng-repeat=\"field in context.dataset.extra_metas.visualization.map_tooltip_fields\"><strong>{{field}}</strong> : {{record.fields[field]}}</li>".
			"</ul>";
		}
		$res .= "<div  ng-repeat=\"report in context.dataset.extra_metas.visualization.reports\">".
			"<strong>Rapport de d\u00e9tail</strong> : <a ng-href=\"{{getReportUrl(report[0], record)}}\" target=\"_blank\">Voir</a>".
			"</div>";
		$res .= "</div>";
		//return utf8_encode ("<div class=\"tooltipcustom\"><h2 class=\"d4cwidget-map-tooltip__header\" ng-show=\"!!getTitle(record)\"><span ng-bind=\"getTitle(record)\"></span></h2><ul style=\"display: block; list-style-type: none; color: #2c3f56; padding:0; margin:0;\"><li  ng-repeat=\"field in context.dataset.extra_metas.visualization.map_tooltip_fields\">".			"<strong>{{field}}</strong> : {{record.fields[field]}}</li></ul><div  ng-repeat=\"report in context.dataset.extra_metas.visualization.reports\">".			"<strong>Rapport de d\u00e9tail</strong> : <a ng-href=\"{{getReportUrl(report[0], record)}}\" target=\"_blank\">Voir</a></div></div>");
		return utf8_encode ($res);
	}

	public function getPackageShow2_v2($params){
		$query_params = $this->proper_parse_str($params);

		unset($query_params["facet"]);
		$query_params["id"] = $query_params["DATASETID"];
		unset($query_params["DATASETID"]);

		$url2 = http_build_query($query_params);
	
		$callUrl =  $this->urlCkan . "api/action/package_show?". $url2; //temporaire
		
		
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		//echo $result . "\r\n";
		$result = json_decode($result,true);
		
		$data_array = array();
		$data_array["datasetid"]= $result["result"]["id"];
		$data_array["metas"]["domain"]="anfr";
		$data_array["metas"]["language"]="fr";
		$data_array["metas"]["title"]=$result["result"]["name"];
		
		$data_array["metas"]["modified"]= $result["result"]["metadata_modified"];
		$data_array["metas"]["visibility"]="domain";
		$data_array["metas"]["metadata_processed"]=$result["result"]["metadata_created"];
		//$data_array["metas"]["data_processed"]="2018-07-05T12:07:03+00:00";
		$data_array["attachments"]="";
	    $data_array["alternative_exports"]="";
		$data_array["features"]=array("timeserie","analyse","geo");
		$resourceCSV;
	
        foreach ($result['result']['resources'] as $value) {
         	if(($value['format'] == 'CSV' || $value['format'] == 'XLS' || $value['format'] == 'XLSX') && $value["datastore_active"] == true){ 
             	$resourceCSV = $value;
             	break;
         	}
        }
        if($resourceCSV != NULL){
			$data_array["has_records"]=true;
			$data_array["data_visible"]=$resourceCSV['datastore_active'];
			$data_array["metas"]["data_visible"] = $data_array["data_visible"];
			$data_array["metas"]["records_count"] = $resourceCSV["size"];
			$fields = $this->getAllFields($resourceCSV['id'], TRUE);
			$data_array["fields"]=$fields;
		} else {
			$data_array["has_records"]=false;
		}
		
	
		return $data_array ;
	
	}

	public function callPackageShow2_v2($params){
		$res = $this->getPackageShow2_v2($params);	
		echo json_encode($res);

		$response = new Response();
		//$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	
	}

	public function callPackageSearch_v2($params) {
		$query_params = $this->proper_parse_str($params);

		//  q lang rows start sort facet refine exclude refine.features source extrametas interopmetas fields
		
		$patternRefine = '/refine./i';
		$patternExclude = '/exclude./i';
		
		$data_array = array();
		  
		//unset($query_params["refine"]);//TODO
		//unset($query_params["exclude"]);//TODO
		unset($query_params["lang"]);
		unset($query_params["source"]);
		unset($query_params["extrametas"]);
		unset($query_params["interopmetas"]);
		
		unset($query_params["sort"]);
		
		
		foreach($query_params as $key => $value) {
			if(!empty($key)){
			  	$data_array["parameters"][$key] =  $value;
			}	
		}
		//rows
		$rows = $query_params["rows"];
		$query_params["rows"] = 1000;
		
		//filters
		$refineFeatures = null;
		$filters = array();
		$reqQ = null;
		foreach($query_params as $key => $value) {
			if($key == "refine.features"){	
				if(is_array($query_params["refine.features"])){
					$refineFeatures = $query_params["refine.features"];
				} else {
					$refineFeatures = array();
					$refineFeatures[] = $query_params["refine.features"];
				}
				$filters[preg_replace($patternRefine,"",$key)] =  "(*". implode("* OR *", $refineFeatures) ."*)";
				unset($query_params[$key]);
			} else
			if (preg_match($patternRefine,$key)){
		    	$filters[preg_replace($patternRefine,"",$key)] =  $value;
		        unset($query_params[$key]);
		    }else 
			if (preg_match($patternExclude,$key)){
		    	$filters["-".preg_replace($patternExclude,"",$key)] =  $value;
		        unset($query_params[$key]);
		    }
			
		}
		if(!empty($filters)){
			$reqQ = "";
			foreach($filters as $key => $value) {
				if($key != "features"){
					$reqQ .= $key . ':"' . $value . '" AND ';
				} else {
					$reqQ .= $key . ':' . $value . ' AND ';
				}
			}
			$reqQ = substr($reqQ, 0, -5);
			$query_params["fq"] = $reqQ;
		}
		
		
		//facets / fields
		$reqFacet = null;
		if(array_key_exists("facet", $query_params) || array_key_exists("fields", $query_params)){	
			$fac = array();
			if(array_key_exists("facet", $query_params)){
				if(is_array($query_params["facet"])){
					$fac = $query_params["facet"];
				} else {
					$fac = array();
					$fac[] = $query_params["facet"];
				}
			}
			if(array_key_exists("fields", $query_params)){
				$fields = explode(",", $query_params["fields"]);
				$fac = array_merge($fac, $fields);
			}
			$reqFacet = "[";
			foreach($fac as $f) {
				$reqFacet .= '"'.$f.'",';
			}
			$reqFacet = substr($reqFacet, 0, -1);
			$reqFacet .= "]";
			unset($query_params["facet"]);
			unset($query_params["fields"]);
		} else {
			
			$fac = array();
			foreach($data_array["parameters"] as $key => $value) {
				if (preg_match($patternRefine,$key) && $key != "refine.features"){
					$fac[] = preg_replace($patternRefine,"",$key);
				}	
			}
			if(count($fac) > 0){
				$reqFacet = "[";
				foreach($fac as $f) {
					$reqFacet .= '"'.$f.'",';
				}
				$reqFacet = substr($reqFacet, 0, -1);
				$reqFacet .= "]";
			}
			
		}
		if($reqFacet != null){
			$query_params["facet.field"] = $reqFacet;
		}
		
		//echo json_encode($query_params);
	  	$url2 = http_build_query($query_params);
		  

	      //$callUrl =  $this->urlCkan . "api/action/package_search";
	  	$callUrl =  $this->urlCkan . "api/action/package_search?". $url2;
		/*if(!is_null($params)){
			$callUrl .= "?" . $params;
		} */
				
		
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);

		//echo $result . "\r\n";

        $datasets = array();   
		$facet_groups = array();
		$result = json_decode($result,true);
		foreach($result["result"]["results"] as $value){
			if($rows != null && count($datasets) >= $rows){
				break;
			}
			
			$dataset = array();
			$isGeo = false;

		   	$dataset["datasetid"]= $value["id"];
			$dataset["metas"]["domain"]="anfr";
			$dataset["metas"]["language"]="fr";
			$dataset["metas"]["title"]=$value["name"];
			
			$dataset["metas"]["modified"]= $value["metadata_modified"];
			$dataset["metas"]["visibility"]="domain";
			$dataset["metas"]["metadata_processed"]=$value["metadata_created"];
			$dataset["attachments"]="";
		    $dataset["alternative_exports"]="";
			
			$resourceCSV;
		
	        foreach ($value['resources'] as $v) {
	         	if(($v['format'] == 'CSV' || $v['format'] == 'XLS' || $v['format'] == 'XLSX') && $v["datastore_active"] == true){ 
	             	$resourceCSV = $v;
	         	}
				if($v['format'] == 'GeoJSON'){
					$isGeo = true;
				}
	        }
	        if($resourceCSV != NULL){
				$dataset["has_records"]=true;
				$dataset["data_visible"]=$resourceCSV['datastore_active'];
				$dataset["metas"]["data_visible"] = $dataset["data_visible"];
				//$dataset["metas"]["records_count"] = $resourceCSV["size"];
				$fields = $this->getAllFields($resourceCSV['id']);
				$dataset["fields"]=$fields;
			} else {
				$dataset["has_records"]=false;
			}

			$dataset['features'] = array(); //["timeserie", "analyze", "geo", "image", "calendar", "timeserie", "custom_view"]
			/*if($isGeo){
				$dataset['features'][] = "geo"; //tab map 
			}
			$dataset['features'][] = "analyze"; //tab chart 
			//$dataset['features'][] = "timeserie"; //unknown tab
			//echo json_encode($dataset['features']);*/
			
			foreach($value['extras'] as $extra){
				if($extra["key"] == "features"){
					$dataset['features'] = explode(",", $extra["value"]);
				}
			}
			
			/*if($refineFeatures != null){
				$match = false;
				foreach($refineFeatures as $feat) {
					if (in_array($feat, $dataset['features'])) {
						$match = true;
						break;
					}
				}
				if(!$match){
					continue;
				}
			}*/
			

			$datasets[]=$dataset;
		   
	   		//echo $datasets;
		}	
		$data_array["datasets"] = $datasets;
		
		foreach($result["result"]["facets"] as $key => $value){
			if (!empty((array) $value)) {
				$facet = array();
				$facet["name"] = $key;
				$facet["facets"] = array();
				foreach($value as $val => $count){
					$item = array();
					$item["name"] = $val;
					$item["path"] = $val;
					$item["count"] = $count;
					if(array_key_exists("refine.".$key, $data_array["parameters"])){
						$filter = $data_array["parameters"]["refine.".$key];
						if((is_array($filter) && in_array($val, $filter)) || (!is_array($filter) && $val = $filter)){
							$item["state"] = "refined";
						} else {
							$item["state"] = "displayed";
						}
					} else {
						$item["state"] = "displayed";
					}
					
					$facet["facets"][] = $item;
				}
				$facet_groups[] = $facet;
			}
		}
		$data_array["facet_groups"] = $facet_groups;
		$data_array["nhits"] = $result["result"]["count"];//count($datasets);
		echo json_encode( $data_array );
		$response = new Response();
//		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		
		return $response;
	
	
	
	}

	public function callDatastoreApi_v2($params) {
		
		$res = $this->getDatastoreRecord_v2($params);	
		echo json_encode($res);
		$response = new Response();
//		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		
		return $response;
		
		
	}

	public function getDatastoreRecord_v2($params) {								 
		//dataset q lang rows start sort facet refine exclude geofilter.distance geofilter.polygon timezone
		$patternRefine = '/refine./i';
		$patternExclude = '/exclude./i';
		$patternDisj = '/disjunctive./i';
		$patternDistance = '/geofilter.distance/i';
		$filters_init = array();
		$fieldId = "_id";
		$fieldCoordinates='';$fieldGeometries='';
		$reqQfilter;
		$query_params = $this->proper_parse_str($params);
		$data_array = array();
		foreach($query_params as $key => $value) {
			if(!empty($key)){
			  	$data_array["parameters"][$key] =  $value;
			}	
		}
		
		
		unset($query_params["lang"]);
		//unset($query_params["geofilter.distance"]); //TODO
		unset($query_params["geofilter.polygon"]); //TODO
		unset($query_params["timezone"]);
		unset($query_params["geo_simplify"]);
		unset($query_params["geo_simplify_zoom"]);

		
		$datasetId ="";
		if(!array_key_exists("resource_id", $query_params) && array_key_exists("dataset", $query_params)){
			$resourceCSV;
			$datasetId = $query_params['dataset'];
			$callUrl =  $this->urlCkan . "api/action/package_show?id=" . $query_params['dataset'];
			$curl = curl_init($callUrl);
			curl_setopt_array($curl, $this->getStoreOptions());
			$package = curl_exec($curl);
			//echo $package . "\r\n";
			curl_close($curl);
			$package = json_decode($package, true);
			foreach ($package['result']['resources'] as $value) { 
			 	if(($value['format'] == 'CSV' || $value['format'] == 'XLS' || $value['format'] == 'XLSX') && $value["datastore_active"] == true){ 
			 		$resourceCSV = $value['id'];
			 		break;
			 	}
			}
			unset($query_params['dataset']);
			$query_params['resource_id'] = $resourceCSV;
		}
		
		$fields = $this->getAllFields($query_params['resource_id'], TRUE);	
		foreach ($fields as $value) {
			if($value['type'] == "geo_point_2d") $fieldCoordinates = $value['name'];
		}
		/*foreach ($fields as $value) {
			if(preg_match("/id|num|code|siren/i",$value['name'])){
				$fieldId = $value['name'];
				break;
			} 
		}*/

		foreach($query_params as $key => $value) {
		    if (preg_match($patternRefine,$key)){
				
				$value = str_replace('_plussign_', '+', $value);
				
		    	$filters_init[preg_replace($patternRefine,"",$key)] =  $value;
		        unset($query_params[$key]);
		    }
		    if (preg_match($patternDisj,$key)){
		    	unset($query_params[$key]);
		    }
		    /*if (preg_match($patternBbox,$key)){
		    	unset($query_params[$key]);
		    	$filters_init[$key] =  $value;
		    }*/
		    if (preg_match($patternExclude,$key)){
		    	unset($query_params[$key]);
		    }
		    if (preg_match($patternDistance,$key)){
		    	
				if(count(explode(',', $query_params[$key])) == 3){ //distance + meters
					$args = explode(',', $query_params[$key]);
					$filters_init["geofilter.bbox"] =  $this->getBbox($args[0],$args[1],$args[2]);
				} else { //distance precision
					$filters_init[$key] =  $value;
				}
				unset($query_params[$key]);
		    }
		    if($key == "rows"){
				$query_params['limit'] = $query_params['rows'];
				unset($query_params['rows']);
		    }
		    if($key == "start"){
				$query_params['offset'] = $value;
				unset($query_params['start']);
				$query_params['limit'] = $query_params['limit'] /*+ $query_params['offset']*/;
		    }
		    //$query_params['sort'] TODO 
		    if(array_key_exists('facet', $query_params)){
				$query_params['fields'] = implode(",", $query_params['facet']);
		    }
		    if($key == "q"){
		    	$reqQfilter = $this->constructReqQToSQL($value);
		    }
		    if($key == "id" || $key == "basemap" || $key == "location" || $key == "datasetcard" || $key == "static" || $key == "scrollWheelZoom"){
				unset($query_params[$key]);
		    }
		    /*if($key == "use_labels_for_header"){
			unset($query_params[$key]);
		    }*/
			if($key == "recordid"){
				$filters_init[$fieldId] = $value;
				unset($query_params["recordid"]);
		    }
		}
		
		$where = "";$limit  = "";$offset  = "";$orderby = "";
		
		if(!empty($filters_init)){
			$where = " where ";
			foreach ($filters_init as $key => $value) {
				if($key == "geofilter.bbox"){
					$bbox = explode(',', $value);
					$minlat = $bbox[0];
					$minlong = $bbox[1];
					$maxlat = $bbox[2];
					$maxlong = $bbox[3];
					//$where .= "CAST(split_part(".$fieldCoordinates.",',',1)AS FLOAT) between " . $minlat . " and " . $maxlat . " and ";
					//$where .= "CAST(split_part(".$fieldCoordinates.",',',2)AS FLOAT) between " . $minlong . " and " . $maxlong . " and ";
					$where .= "box(point(" . $minlat . "," . $minlong . "),point(" . $maxlat . "," . $maxlong . ")) @> point(".$fieldCoordinates.") and ";
					$where .= $fieldCoordinates." not in ('', ',') and ";
				} else if($key == "geofilter.distance"){
					$coord = explode(',', $value);
					$lat = $coord[0];
					$long = $coord[1];
					//$where .= "CAST(split_part(".$fieldCoordinates.",',',1)AS FLOAT) = " . $lat . " and ";
					//$where .= "CAST(split_part(".$fieldCoordinates.",',',2)AS FLOAT) = " . $long . " and ";
					$where .= "point(" . $lat . "," . $long . ") ~= point(".$fieldCoordinates.") and ";
					$where .= $fieldCoordinates." not in ('', ',') and ";
				}/* else if($key == "geo_digest"){
					$where .= "md5(".$fieldGeometries.") = '". $value . "' and ";
				}*/ else {
					$ftype == null;
					foreach($fields as $field){
						if($field["name"] == $key){
							$ftype = $field["type"];
							break;
						}
					}
					if(($ftype != null && $ftype == "double") || ($ftype == null && is_numeric($value) && $key != "insee_com" && $key != "code_insee")){
						$where .= $key . "=" . $value . " and ";
					} else if(is_array($value)){ 
						$where .= $key . " in (" . implode(',', array_map(array($this, 'quotesArrayValue'), str_replace("'", "''", $value))) . ") and ";
					} else {
						$where .= $key . "='" . str_replace("'", "''", $value) . "' and ";
					}
				}
				
				
			}
			$where = substr($where, 0, strlen($where)-4 );

			if($reqQfilter != NULL){
				$where .= $reqQfilter;
			}
		}
		else if($reqQfilter != NULL){
			$where = " where " . substr($reqQfilter, 5);
		}

		if(array_key_exists("limit", $query_params)){
			$limit = " limit ".$query_params['limit'];
		} else {
			$limit = " limit 100"; //par defaut
		}
		
		if(array_key_exists("offset", $query_params)){
			$offset = " offset ".$query_params['offset'];
		}
		
		if((is_array($query_params["sort"]) && count($query_params["sort"]) > 0) || $query_params["sort"] != ""){
			$orderby = " order by ";
			foreach(explode(',', $query_params["sort"]) as $sort){
				if(substr($sort, 0, 1) == "-"){
					$orderby .= substr($sort, 1) . " DESC,";
				} else {
					$orderby .= $sort . " ASC,";
				}
				
			}
			$orderby = substr($orderby, 0, -1);
		}

	  	$req = array();
		$sql = "Select *, count(*) OVER() AS total_count from \"" . $query_params['resource_id'] . "\"" . $where . $orderby . $limit . $offset;
		$req['sql'] = $sql;
		//echo $sql;
		$url2 = http_build_query($req);
		$callUrl =  $this->urlCkan . "api/action/datastore_search_sql?" . $url2;
		//echo $callUrl . "\r\n";
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		//echo $result . "\r\n";
		
		$result = json_decode($result,true);
		
 
		//$data_array["nhits"] = $result["result"]["total"];
		$colFile = null;
		foreach($fields as $f){
			if($f["type"] == "file"){
				$colFile = $f["name"];
			}
		}
		$records = array();	
		foreach($result["result"]["records"] as $value){
			
			$rec;
			$rec["datasetid"]=$datasetId;
     			//$rec["recordid"]=$value["_id"];
			$rec["recordid"]= $value[$fieldId];
			foreach($value as $k => $v) {
		  		$rec["fields"][$k] =  $v;
				/*if(preg_match("/id|num|code|siren/i",$k)){
					$rec["recordid"] = $value[$k];				
				}*/
				if($colFile != null && $colFile == $k){
					$rec["fields"][$k] = array();
					$rec["fields"][$k]["url"] = $v;
					if(strrpos($v, "/")){
						$rec["fields"][$k]["filename"] = substr($v, strrpos($v, "/")+1);
					}
					
					$size = getimagesize($v);
					$rec["fields"][$k]["width"] = $size[0];
					$rec["fields"][$k]["height"] = $size[1];
				}
		  	}
			
	   		$records[]=$rec;
					 
		}
		$data_array["records"] = $records;
		//$data_array["nhits"] = count($records);
		if(count($records) == 0){
			$data_array["nhits"] = 0;
		} else {
			$data_array["nhits"] = $result["result"]["records"][0]["total_count"];
		}
		$data_array["status"] = $result["success"] == true ? "success" : "error";
		return $data_array;
				
	}

	public function getDatastoreRecord_v2OLD($params) {								 
		//dataset q lang rows start sort facet refine exclude geofilter.distance geofilter.polygon timezone
		$patternRefine = '/refine./i';
		$patternExclude = '/exclude./i';
		$patternDisj = '/disjunctive./i';

		
		$query_params = $this->proper_parse_str($params);
		unset($query_params["lang"]);
		unset($query_params["geofilter.distance"]); //TODO
		unset($query_params["geofilter.polygon"]); //TODO
		unset($query_params["timezone"]);
		$init_params = array_merge(array(), $query_params);
		$datasetId ="";
		if(!array_key_exists("resource_id", $query_params) && array_key_exists("dataset", $query_params)){
			$resourceCSV;
			$datasetId = $query_params['dataset'];
			$callUrl =  $this->urlCkan . "api/action/package_show?id=" . $query_params['dataset'];
			$curl = curl_init($callUrl);
			curl_setopt_array($curl, $this->getStoreOptions());
			$package = curl_exec($curl);
			//echo $package . "\r\n";
			curl_close($curl);
			$package = json_decode($package, true);
			foreach ($package['result']['resources'] as $value) { 
			 	if(($value['format'] == 'CSV' || $value['format'] == 'XLS' || $value['format'] == 'XLSX') && $value["datastore_active"] == true){ 
			 		$resourceCSV = $value['id'];
			 		break;
			 	}
			}
			unset($query_params['dataset']);
			$query_params['resource_id'] = $resourceCSV;
		}
		
			

		/*if(array_key_exists('q', $query_params)){
			if (strpos($query_params['q'], '{') == false) {
				if (strpos($query_params['q'], ':') != false && substr($query_params['q'], 0, 1 ) != '"') {
					$ex = explode(':', $query_params['q']);
					$query_params['q'] = '"'. $ex[0] .'":' .  $ex[1];
				}
			    $query_params['q'] = '{'.$query_params['q'].'}';
			    //echo $query_params['q'];
			}
		}*/

		if(array_key_exists('rows', $query_params)){
			$query_params['limit'] = $query_params['rows'];
			unset($query_params['rows']);
		}
		if(array_key_exists('start', $query_params)){
			$query_params['offset'] = $query_params['start'];
			unset($query_params['start']);
			$query_params['limit'] = $query_params['limit'] + $query_params['offset'];
		}
		//$query_params['sort'] TODO 
		if(array_key_exists('facet', $query_params)){
			$query_params['fields'] = implode(",", $query_params['facet']);
		}
		

		$filters_init = array();
		
		foreach($query_params as $key => $value) {
		    if (preg_match($patternRefine,$key)){
		    	$filters_init[preg_replace($patternRefine,"",$key)] =  $value;
		        unset($query_params[$key]);
		    }
		    if (preg_match($patternExclude,$key)){
		    	unset($query_params[$key]);
		    }
			if (preg_match($patternDisj,$key)){
		    	unset($query_params[$key]);
		    }
		    if($key == "id" || $key == "basemap" || $key == "location" || $key == "datasetcard" || $key == "static" || $key == "scrollWheelZoom"){
		    	unset($query_params[$key]);
		    }
		}
		if(!empty($filters_init)){
			$query_params['filters'] = json_encode($filters_init);
		}

		
	  	$url2 = http_build_query($query_params);
		$callUrl =  $this->urlCkan . "api/action/datastore_search?" . $url2;
		
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		//echo $result . "\r\n";
		
		$result = json_decode($result,true);
		$data_array = array();
 
		$data_array["nhits"] = $result["result"]["total"];
		foreach($init_params as $key => $value) {
			if(!empty($key)){
			  	$data_array["parameters"][$key] =  $value;
			}	
		}
		$records = array();
		
		foreach($result["result"]["records"] as $value){
			$rec;
			$rec["datasetid"]=$datasetId;
     			//$rec["recordid"]=$value["_id"];
			$rec["recordid"]= "";
			foreach($value as $k => $v) {
		  		$rec["fields"][$k] =  $v;
				if(preg_match("/id|num|code|siren/i",$k)){
					$rec["recordid"] = $value[$k];				
				} 
		  	}
			
	   		$records[]=$rec;
	   		
					 
		}
		$data_array["records"] = $records;
			
		return $data_array;
		
		
	}

	public function metaBasic() {
		//dataset q lang rows start sort facet refine exclude geofilter.distance geofilter.polygon timezone
		$basic = '{"schema": [
		{"widget": "textinput", "name": "title", "uri": "http://purl.org/dc/terms/title", "search": true, "label": "Title", "allow_empty": false, "type": "text"}, 
		{"widget": "richtextinput", "name": "description", "uri": "http://purl.org/dc/terms/description", "search": true, "label": "Description", "allow_empty": true, "type": "longstring"}, 
		{"widget": "multidatalist", "name": "theme", "uri": "http://www.w3.org/ns/dcat#theme", "search": true, "label": "Themes", "allow_empty": true, "type": "list"}, 
		{"widget": "tags", "name": "keyword", "uri": "http://www.w3.org/ns/dcat#keyword", "search": true, "label": "Keywords", "allow_empty": true, "type": "list"}, 
		{"widget": "datalist", "name": "license", "uri": "http://purl.org/dc/terms/licence", "search": true, "label": "License", "allow_empty": true, "type": "text"}, 
		{"widget": "select", "name": "language", "uri": "http://purl.org/dc/terms/language", "search": true, "label": "Language", "allow_empty": true, "type": "text"}, 
		{"widget": "datetimeinput", "name": "modified", "uri": "http://purl.org/dc/terms/modified", "search": true, "label": "Modified", "allow_empty": true, "type": "datetime"}, 
		{"widget": "geoarea", "name": "geographic_area_mode", "uri": null, "search": true, "label": "Geographic area mode", "allow_empty": true, "type": "text"}, 
		{"widget": "geoarea", "name": "geographic_area", "uri": null, "search": true, "label": "Geographic area", "allow_empty": true, "type": "geo_shape"}, 
		{"widget": null, "name": "data_processed", "uri": null, "search": true, "label": "Data processed", "allow_empty": true, "type": "datetime"}, 
		{"widget": null, "name": "metadata_processed", "uri": null, "search": true, "label": "Metadata processed", "allow_empty": true, "type": "datetime"}, 
		{"widget": null, "name": "publisher", "uri": "http://purl.org/dc/terms/publisher", "search": true, "label": "Publisher", "allow_empty": true, "type": "text"}, 
		{"widget": null, "name": "references", "uri": "http://purl.org/dc/terms/references", "search": true, "label": "Reference", "allow_empty": true, "type": "text"}, 
		{"widget": null, "name": "records_count", "uri": null, "search": true, "label": "Records count", "allow_empty": true, "type": "int"}, 
		{"widget": "multitextinput", "name": "attributions", "uri": null, "search": true, "label": "Attributions", "allow_empty": true, "type": "list"}, 
		{"widget": null, "name": "source_domain", "uri": null, "search": true, "label": "Source domain", "allow_empty": true, "type": "text"}, 
		{"widget": null, "name": "source_domain_title", "uri": null, "search": true, "label": "Source domain title", "allow_empty": true, "type": "text"}, 
		{"widget": null, "name": "source_domain_address", "uri": null, "search": true, "label": "Source domain address", "allow_empty": true, "type": "text"}, 
		{"widget": null, "name": "source_dataset", "uri": null, "search": true, "label": "Source dataset", "allow_empty": true, "type": "text"}, 
		{"widget": null, "name": "oauth_scope", "uri": null, "search": true, "label": "OAuth2 Scope", "allow_empty": true, "type": "text"}, 
		{"widget": null, "name": "parent_domain", "uri": null, "search": true, "label": "Parent domain identifier", "allow_empty": true, "type": "text"}
		]}';
		
			
		echo $basic;
		$response = new Response();
//		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		
		return $response;
		
		
	}

	public function metaInterop() {
		//dataset q lang rows start sort facet refine exclude geofilter.distance geofilter.polygon timezone
		$interop = '[]';
			
		echo $interop;
		$response = new Response();
//		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		
		return $response;
		
		
	}

	private function getBbox($lat, $long, $meters){

 		//Earth's radius, sphere
 		$R=6378137;

 		//Coordinate offsets in radians
 		$dLat = $meters/$R;
 		$dLon = $meters/($R*cos(pi()*$lat/180));

 		//OffsetPosition, decimal degrees
 		$minLat = $lat - $dLat * 180/pi();
 		$minLong = $long - $dLon * 180/pi();
 		$maxLat = $lat + $dLat * 180/pi();
 		$maxLong = $long + $dLon * 180/pi(); 
		return $minLat . "," . $minLong . "," . $maxLat . "," . $maxLong;
	}
	
	private function getRadius($lat, $long, $meters){

 		//Earth's radius, sphere
 		$R=6378137;

 		//Coordinate offsets in radians
 		$dLat = $meters/$R;
		$dLon = $meters/($R*cos(pi()*$lat/180));

 		//radius
 		$rad = /*($dLon * 180/pi() + */$dLat * 180/pi()/*)/2*/;
		return $rad;
	}
	
	private function getLosangePath($lat, $long, $meters){

 		//Earth's radius, sphere
 		$R=6378137;

 		//Coordinate offsets in radians
 		$dLat = $meters/$R;
 		$dLon = $meters/($R*cos(pi()*$lat/180));

 		//OffsetPosition, decimal degrees
 		$minLat = $lat - $dLat * 180/pi();
 		$minLong = $long - $dLon * 180/pi();
 		$maxLat = $lat + $dLat * 180/pi();
 		$maxLong = $long + $dLon * 180/pi(); 
		return "(".$maxLat.",".$long."),(".$lat.",".$maxLong."),(".$minLat.",".$long."),(".$lat.",".$minLong.")";
	}
	
	public function orgaShow($params) {
		$callUrl =  $this->urlCkan . "api/action/organization_show?" . $params;
				
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		//echo $result . "\r\n";

		$result = json_decode($result,true);
		unset($result["help"]);
		
		echo json_encode($result);
		$response = new Response();
		//$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	public function licenseList() {
		$callUrl =  $this->urlCkan . "api/action/license_list" ;
				
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getSimpleOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		//echo $result . "\r\n";

		$result = json_decode($result,true);
		unset($result["help"]);
		
		echo json_encode($result);
		$response = new Response();
		//$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	public function getPackageList() {
		$callUrl =  $this->urlCkan . "api/action/package_list" ;
				
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getSimpleOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		//echo $result . "\r\n";

		$result = json_decode($result,true);
		unset($result["help"]);
		
		return $result;
	}
	
	public function packageList() {
		$result = $this->getPackageList();
		
		echo json_encode($result);
		$response = new Response();
		//$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	public function cluster($params) {
		
		$ckanurl = $this->urlCkan;
		if(substr($ckanurl, -1) == "/"){
			$ckanurl = substr($ckanurl, 0, -1);
		}
		$callUrl =  "192.168.2.184:1337/cluster?".$params;

		
//		echo $callUrl . "\r\n";
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getSimpleOptions());
		$dataset = curl_exec($curl);
		curl_close($curl);
		//echo $dataset . "\r\n";
		
		echo $dataset;
		$response = new Response();
//		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	public function getSQLWhereRecordsDownload($params) {

		$patternId = '/id|num|code|siren/i';
		$patternRefine = '/refine./i';
		$patternDisj = '/disjunctive./i';
		$patternBbox = '/geofilter.bbox/i';
		$patternDistance = '/geofilter.distance/i';
		$filters_init = array();
		$query_params = $this->proper_parse_str($params);

		$fields = $this->getAllFields($query_params['resource_id']);
		//echo json_encode($fields);
		$fieldId = "id";$reqFields="";
		$fieldCoordinates='';$fieldGeometries='';
		$reqQfilter;
		foreach ($fields as $value) {
			if(preg_match("/id|num|code|siren/i",$value['name'])){
				$fieldId = $value['name'];
				break;
			} 
		}
		foreach ($fields as $value) {
			/*if($value['id'] == "geo_point_2d") $fieldCoordinates = $value['id'];
			if(preg_match("/coordin/i",$value['id'])) $fieldCoordinates = $value['id'];
			if(preg_match("/coordon/i",$value['id'])) $fieldCoordinates = $value['id'];*/
			if($value['type'] == "geo_point_2d") $fieldCoordinates = $value['name'];
			if($value['type'] == "geo_shape") $fieldGeometries = $value['name'];					  
		}


		foreach($query_params as $key => $value) {
		    if (preg_match($patternRefine,$key)){
		    	$filters_init[preg_replace($patternRefine,"",$key)] =  $value;

		        unset($query_params[$key]);
		        //echo preg_replace($pattern,"",$key);
		    }
		    if (preg_match($patternDisj,$key)){
		    	unset($query_params[$key]);
		    	//$disj[] = preg_replace($patternDisj,"",$key);
		    }
		    if (preg_match($patternBbox,$key)){
		    	unset($query_params[$key]);
		    	$filters_init[$key] =  $value;
		    	//$disj[] = preg_replace($patternDisj,"",$key);
		    }
			if (preg_match($patternDistance,$key)){
		    	unset($query_params[$key]);
		    	$filters_init[$key] =  $value;
		    	//$disj[] = preg_replace($patternDisj,"",$key);
		    }
			if ($key == "geo_digest"){
		    	unset($query_params[$key]);
		    	$filters_init[$key] =  $value;
		    }
		    if($key == "format"){
				$globalFormat = $value;

		    	if($value == "json"){
		    		$format = "objects";
		    	} else if($value == "csv" || $value == "xls"){
		    		$format = "csv";
		    	} else if($value == "tsv"){
		    		$format = "tsv";
		    	} else if($value == "geojson"){
		    		$format = "objects";
		    	} else {
		    		$format = "objects";
		    	}
		    	unset($query_params[$key]);
		    }
		    if($key == "geo_simplify"){
				unset($query_params[$key]);
		    }
		    if($key == "geo_simplify_zoom"){
				unset($query_params[$key]);
		    }
		    if($key == "rows"){
				$query_params['limit'] = $value;
				unset($query_params['rows']);
		    }
			if($key == "fields"){
				$reqFields = $value;
				unset($query_params['fields']);
		    }
			if($key == "start"){
				$query_params['offset'] = $value;
				unset($query_params['start']);
				$query_params['limit'] = $query_params['limit'] + $query_params['offset'];
		    }
		    if($key == "q"){
		    	$reqQfilter = $this->constructReqQToSQL($value);
		    	//$pattern = '/and (\w+) /i';
		    	//preg_match($pattern,$reqQfilter,$qField); 
		    }
		    
		    if($key == "id" || $key == "basemap" || $key == "location" || $key == "datasetcard" || $key == "static" || $key == "scrollWheelZoom"){
				unset($query_params[$key]);
		    }
		    if($key == "use_labels_for_header"){
				unset($query_params[$key]);
		    }
		}
		unset($query_params["clusterprecision"]);
		unset($query_params["q"]);
		$where = "";$limit  = "";
		if(!empty($filters_init)){
			$where = " where ";
			foreach ($filters_init as $key => $value) {
				if($key == "geofilter.bbox"){
					$bbox = explode(',', $value);
					$minlat = $bbox[0];
					$minlong = $bbox[1];
					$maxlat = $bbox[2];
					$maxlong = $bbox[3];
					//$where .= "CAST(split_part(".$fieldCoordinates.",',',1)AS FLOAT) between " . $minlat . " and " . $maxlat . " and ";
					//$where .= "CAST(split_part(".$fieldCoordinates.",',',2)AS FLOAT) between " . $minlong . " and " . $maxlong . " and ";
					$where .= "box(point(" . $minlat . "," . $minlong . "),point(" . $maxlat . "," . $maxlong . ")) @> point(".$fieldCoordinates.") and ";
					$where .= $fieldCoordinates." not in ('', ',') and ";
				} else if($key == "geofilter.distance"){
					$coord = explode(',', $value);
					$lat = $coord[0];
					$long = $coord[1];
					//$where .= "CAST(split_part(".$fieldCoordinates.",',',1)AS FLOAT) = " . $lat . " and ";
					//$where .= "CAST(split_part(".$fieldCoordinates.",',',2)AS FLOAT) = " . $long . " and ";
					$where .= "point(" . $lat . "," . $long . ") ~= point(".$fieldCoordinates.") and ";
					$where .= $fieldCoordinates." not in ('', ',') and ";
				} else if($key == "geo_digest"){
					$where .= "md5(".$fieldGeometries.") = '". $value . "' and ";
				} else {
					if(is_numeric($value) && $key != "insee_com" && $key != "code_insee"){
						$where .= $key . "=" . $value . " and ";
					} else if(is_array($value)){ 
						$where .= $key . " in (" . implode(',', array_map(array($this, 'quotesArrayValue'), str_replace("'", "''", $value))) . ") and ";
					} else {
						$where .= $key . "='" . str_replace("'", "''", $value) . "' and ";
					}
				}
				
				
			}
			$where = substr($where, 0, strlen($where)-4 );

			if($reqQfilter != NULL){
				$where .= $reqQfilter;
			}
		}
		else if($reqQfilter != NULL){
			$where = " where " . substr($reqQfilter, 5);
		}
		
		return $where;
	}
	
	public function renderFrame(Request $request, $tab) {
		$id = $request->query->get('id');
		
		$api = new API();
		$dataset = $api->getPackageShow2($id,"");
		$ctx = str_replace(array("{", "}", '"'), array("\{", "\}", "&quot;"), json_encode($dataset));
		$element =  '<body>
        <div class="d4c-content">

            <div class="d4c-app-embed-dataset d4c-app-embed-dataset--analyze ng-cloak"
				ng-app="d4c.frontend"
				d4c-dataset-context
				context="ctx"
				ctx-urlsync="true"
				ctx-dataset-schema="'.$ctx.'">';
			
			if($tab == "information"){
				$element .= '';
			}
            if($tab == "table"){
				$element .= '<d4c-table context="ctx" class="d4c-app-embed-dataset__visualization d4c-app-embed-dataset__visualization--table d4cwidget-table--embedded" ></d4c-table>';
			}
            if($tab == "map"){
				$element .= '<d4c-map context="ctx" class="d4c-app-embed-dataset__visualization d4c-app-embed-dataset__visualization--map" sync-to-url="true" static-map=""></d4c-map>';
			}
            if($tab == "analyze"){
				$element .= '<d4c-analyze context="ctx" class="d4c-app-embed-dataset__visualization d4c-app-embed-dataset__visualization--analyze" sync-to-url="true" auto-resize="true" no-controls="true"></d4c-analyze>';
			}
            if($tab == "images"){
				$element .= '<d4c-media-gallery context="ctx" class="d4c-app-embed-dataset__visualization d4c-app-embed-dataset__visualization--images" d4c-auto-resize d4c-widget-tooltip display-mode="compact"></d4c-media-gallery>';
			}
            if($tab == "calendar"){
				$element .= '<d4c-calendar context="ctx" class="d4c-app-embed-dataset__visualization d4c-app-embed-dataset__visualization--calendar"></d4c-calendar>';
			}
            if($tab == "custom_view"){
				$element .= '<div d4c-bind-angular-content="ctx.dataset.extra_metas.visualization.custom_view_html" do-not-decode-content></div>
                        <style type="text/css" d4c-bind-angular-content="ctx.dataset.extra_metas.visualization.custom_view_css"></style>';
				$tab = "custom";
			}
			if($tab == "wordcloud"){
				$element .= '<d4c-wordcloud context="ctx" class="d4c-app-embed-dataset__visualization d4c-app-embed-dataset__visualization--wordcloud"  sync-to-url="true"></d4c-wordcloud>';
			}
			if($tab == "timeline"){
				$element .= '<d4c-timeline context="ctx" class="d4c-app-embed-dataset__visualization d4c-app-embed-dataset__visualization--timeline" sync-to-url="true"></d4c-timeline>';
			}
            if($tab == "export"){
				$element .= '';
			}
			if($tab == "api"){
				$element .= '';
			}
            
			
            $element .= '
			<a class="d4c-embed-watermark d4c-embed-watermark--'.$tab.'"
               target="_parent"
               href="'.str_replace("/frame/", "/", $request->getUri()).'">
                <img class="d4c-embed-watermark__image" ng-src="/sites/default/files/api/portail_d4c/img/theme-default.png" />
            </a>
        
    </div>
        <script src="/sites/default/files/api/portail_d4c/js/jquery-3.2.1.js"></script>
        <script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/bootstrap.min.js"></script>
        <script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/libraries.js"></script>
		<script type="text/javascript" src="/sites/default/files/api/portail_d4c/lib/qtip/jquery.qtip.min.js"></script>	
		<script type="text/javascript" src="/sites/default/files/api/portail_d4c/lib/fullcalendar/moment.min.js"></script>
		<script type="text/javascript" src="/sites/default/files/api/portail_d4c/lib/fullcalendar/fullcalendar.min.js"></script>
		<script type="text/javascript" src="/sites/default/files/api/portail_d4c/lib/fullcalendar/lang/fr.js"></script>
		<script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/underscore-min.js"></script>
        <script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/angular-core.js"></script>
        <script type="text/javascript">
        	
            var app = angular.module(\'d4c.core.config\', []);

            
            app.factory("domainConfig", [function() {
                return {"languages": ["fr"], "explore.reuse": null, "explore.dataset_catalog_separate_languages": null, "explore.disable_analyze": null, "explore.enable_api_tab": false};
            }]);
            

            app.factory("config", [function() {
                return {
                    DATASET_ID: \'\',
                    LANGUAGE: \'fr\',
                    AVAILABLE_LANGUAGES: ["fr"],
                    USER: null,
                    BRAND_HOSTNAME: "'.$this->config->client->domain.'",
                    DEFAULT_BASEMAP: {"provider": "osm","minZoom": 0,"maxZoom": 22,"label": "Plan"},
                
                    DOMAIN_ID: "",
                    FEEDBACK: true,
                    RECORDS_COUNTER_ENABLED: true,
                    DOWNLOAD_COUNTER_ENABLED: false,
                    RESOURCE_DOWNLOAD_CONDITIONS: false,
                    PARENT_DOMAIN: false,
                    PAGES_SECURITY_ENABLED: false,
                    
                    MINUTE_LEVEL_SCHEDULING: false,
                
                    CENTRALSTORE: true,
                    FORCE_DEBUG_LOGGER: false
                }
            }]);
        </script>
        <script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/i18n.js"></script>
        <script src="/sites/default/files/api/portail_d4c/js/supported-browsers-message.js" type="text/javascript"></script>
		<script type="text/javascript">
        angular.module(\'d4c.frontend\', [\'d4c\']);

        var app = angular.module(\'d4c-widgets\');

        app.config(function(D4CWidgetsConfigProvider) {
            D4CWidgetsConfigProvider.setConfig({
                customAPIHeaders: {
                    "D4C-API-Analytics-App": "explore",
                    "D4C-API-Analytics-Embed-Type": "explore-analyze",
                    "D4C-API-Analytics-Embed-Referrer": "None"
                }
            });
        });
    </script>
    <script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/embed-dataset.js"></script>

   <script>
   			$("head").append("<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"/> ");
			$("head").append("<link href=\"/sites/default/files/api/portail_d4c/css/normalize.css\" rel=\"stylesheet\">");
			$("head").append("<link href=\"/sites/default/files/api/portail_d4c/css/d4cui.css\" rel=\"stylesheet\">");
			//$("head").append("<link href=\"/sites/default/files/api/portail_d4c/css/bootstrap.min.css\" rel=\"stylesheet\">");
			$("head").append("<link href=\"/sites/default/files/api/portail_d4c/css/visualisation.css\" rel=\"stylesheet\">");
			$("head").append("<link href=\"/sites/default/files/api/portail_d4c/css/'.$this->config->client->css_file.'\" rel=\"stylesheet\">");
			$("head").append("<base href=\"/\">");	
			$("head").append("<link href=\"/sites/default/files/api/portail_d4c/css/font-awesome.min.css\" rel=\"stylesheet\">");
	</script>

</body>';
		echo $element;
		$response = new Response();

		$response->headers->set('Content-Type', 'text/html');
		
		return $response;

	}

	public function getAnalyze($params) {
		$params = preg_replace('/_slash_/i',"/",$params);
		//echo $params;
		//dataset x sort y.serie1-{j} maxpoints y.serie1-1.expr y.serie1-2.func y.serie1-1.cumulative y.serie1-1-range-0.expr timezone
		$patternSerie = '/y.serie[\d]-/i';
		
		$query_params = $this->proper_parse_str($params);
		unset($query_params["timezone"]);
		
		
		$datasetId ="";
		if(!array_key_exists("resource_id", $query_params) && array_key_exists("dataset", $query_params)){
			$resourceCSV;
			$datasetId = $query_params['dataset'];
			$callUrl =  $this->urlCkan . "api/action/package_show?id=" . $query_params['dataset'];
			$curl = curl_init($callUrl);
			curl_setopt_array($curl, $this->getStoreOptions());
			$package = curl_exec($curl);
			//echo $package . "\r\n";
			curl_close($curl);
			$package = json_decode($package, true);
			foreach ($package['result']['resources'] as $value) { 
			 	if(($value['format'] == 'CSV' || $value['format'] == 'XLS' || $value['format'] == 'XLSX') && $value["datastore_active"] == true){ 
			 		$resourceCSV = $value['id'];
			 		break;
			 	}
			}
			unset($query_params['dataset']);
			$query_params['resource_id'] = $resourceCSV;
		}
		
		$ySeries = array();
		foreach($query_params as $key => $value) {
		    if (preg_match($patternSerie,$key)){
		    	$var = explode('.', $key);
				$nom = $var[0].'.'.$var[1];
				$app = $var[2];
				
				if(array_key_exists($nom, $ySeries)){
					$ySeries[$nom][$app] = $value;
				} else {
					$ySeries[$nom]["name"] = $nom;
					$ySeries[$nom][$app] = $value;
				}
				
/*				$exists = false;
				$c= 0;
				foreach($ySeries as $y){
					if($y["name"] == $nom){
					$ySeries[$c][$app] = $value;
						$exists = true;
						break;
					}
					$c++;
				}
				if($exists == false){
					$ySeries[]["name"] = $nom;
					$ySeries[count($ySeries)-1][$app] = $value;
				}*/
		    }
		}
		$fields = "";
		foreach($ySeries as $y){
			if($y["expr"] == NULL){
				$y["expr"] = "*";
			}
			/*
			 func
			 Valeur Constante : y.serie1-1.expr=20&y.serie1-1.func=AVG&y.serie1-1.cumulative=false
			 Percentile : y.serie1-1.func=QUANTILES&y.serie1-1.cumulative=false&y.serie1-1.subsets=50
			 Somme : y.serie1-1.func=SUM&y.serie1-1.cumulative=false
			 Ecart Type : y.serie1-1.func=STDDEV&y.serie1-1.cumulative=false
			 Maximum : y.serie1-1.func=MAX&y.serie1-1.cumulative=false
			 Minimum : y.serie1-1.func=MIN&y.serie1-1.cumulative=false
			 Moyenne : y.serie1-1.func=AVG&y.serie1-1.cumulative=false
			 Compte : y.serie1-1.func=COUNT&y.serie1-1.cumulative=false
			*/
			$func = $y["func"]; $f = "";
			if (is_numeric($y["expr"][0])){
				$y["expr"] = ''.$y["expr"].'';
			}
			switch ($func) {
				case "COUNT":
					$f = "cast(count(". $y["expr"] .") as integer)";
					break;
				case "AVG":
					$f = "cast(avg(". $y["expr"] .") as DOUBLE PRECISION)";
					break;
				case "MIN":
					$f = "cast(min(". $y["expr"] .") as DOUBLE PRECISION)";
					break;
				case "MAX":
					$f = "cast(max(". $y["expr"] .") as DOUBLE PRECISION)";
					break;
				case "STDDEV":
					$f = "cast(stddev_pop(". $y["expr"] .") as DOUBLE PRECISION)";
					break;
				case "SUM":
					$f = "cast(sum(". $y["expr"] .") as DOUBLE PRECISION)";
					break;
				case "QUANTILES":
					$n = $y["subsets"];
					$f = "percentile_cont(".($n/100).") within group ( order by ". $y["expr"] .")";
					break;
				default:
					$f = "cast(count(". $y["expr"] .") as integer)";
					break;
			}
			
			if($y["subsets"] == NULL){
				$fields .= $f . " as \"". $y["name"] ."\",";
			} else {
				$fields .= $f . " as \"". $y["name"] .".".$y["subsets"]."\",";
			}
			
			//$fields .= $f . ",";
		}
		$fields = substr($fields, 0, -1);
/*		if($fields == ""){
			$fields = "count(*)";
		}
*/		
		//$xSeries = array();
		//foreach($query_params["x"] as $value) {
		//    $xSeries[] = explode(".", $value)[0];
		//}
		//$xSeries = array_unique($xSeries);
		$groupby = "";
		if(count($query_params["x"]) > 0){
			$groupby = " group by ";
			$fields .= ",";
			if(is_array($query_params["x"])){
				foreach($query_params["x"] as $x){
					$col = $x; $app = NULL;
					if(strpos($x, '.') !== false){
						$col = explode(".", $x)[0];
						$app = explode(".", $x)[1];
					}
					$groupby .= '"' . $x . '",';
					if($app != NULL){
						if($app == "weekday"){ $app = "dow";}
						if($app == "yearday"){ $app = "doy";}
						$fields .= "extract(".$app." from ". $col . ") as \"". $x ."\",";
					} else {
						$fields .= $x . ",";
					}
					
				}
			} else {
				$col = $query_params["x"]; $app = NULL;
				if(strpos($query_params["x"], '.') !== false){
					$col = explode(".", $query_params["x"])[0];
					$app = explode(".", $query_params["x"])[1];
				}
				$groupby .= "\"" . $query_params["x"] . "\",";
				if($app != NULL){
					if($app == "weekday"){ $app = "dow";}
					if($app == "yearday"){ $app = "doy";}
					$fields .= "extract(".$app." from ". $col . ") as \"". $query_params["x"] ."\",";
				} else {
					$fields .= $query_params["x"] . ",";
				}
			}
			
			$groupby = substr($groupby, 0, -1);
			$fields = substr($fields, 0, -1);
		}
		
		$orderby = "";
		
		if((is_array($query_params["sort"]) && count($query_params["sort"]) > 0) || $query_params["sort"] != ""){
			$orderby = " order by ";
			foreach(explode(',', $query_params["sort"]) as $sort){
				if(preg_match('/serie1-/i',$sort)){
					$orderby .=  "\"y." . $sort . "\",";
				} else {
					$orderby .=  "\"" . substr($sort, 2) . "\",";
				}
				
			}
			$orderby = substr($orderby, 0, -1);
		} else if(!is_array($query_params["x"])){
			$orderby = " order by " . $query_params["x"];
		}
/*		if(array_key_exists('rows', $query_params)){, extract(MONTH from daterun) as \"daterun.month\"
			$query_params['limit'] = $query_params['rows'];
			unset($query_params['rows']);
		}
		if(array_key_exists('start', $query_params)){
			$query_params['offset'] = $query_params['start'];
			unset($query_params['start']);
			$query_params['limit'] = $query_params['limit'] + $query_params['offset'];
		}
*/
		$where = "";
		$where = $this->getSQLWhereRecordsDownload($params);

		if(array_key_exists("maxpoints", $query_params) && $query_params['maxpoints'] != ""){
			$limit = " limit ".$query_params['maxpoints'];
		} else {
			$limit = " limit 1000"; //par defaut
		}
	

	  	$req = array();
		$sql = "Select ". $fields ." from \"" . $query_params['resource_id'] . "\"" . $where . $groupby . $orderby . $limit;
		
		//error_log($sql);
		
		$req['sql'] = $sql;
		//echo $sql;
		$url2 = http_build_query($req);
		$callUrl =  $this->urlCkan . "api/action/datastore_search_sql?" . $url2;
		//echo $callUrl . "\r\n";
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		//echo $result . "\r\n";
		
		error_log('ttt' . $result);
		
		$result = json_decode($result,true);
	
		$data_array = array();
		foreach($result["result"]["records"] as $value){
			$row = array();
			foreach($value as $key => $val){
				if(strpos($key, "y.") !== FALSE){
					if($val == null) continue;		   
					$cumul = $ySeries[$key]["cumulative"]; //true or false
					
					$key = str_replace("y.", "", $key);
					if($cumul == "true"){
						$row[$key] = $data_array[count($data_array)-1][$key] + $val;
					} else {
						if(strpos($key, ".") !== FALSE){
							$row[explode('.', $key)[0]][explode('.', $key)[1]] = $val;
						} else {
							$row[$key] = $val;
						}
						
					}
					
				} else {
					if($val == "" && $val != 0){ continue 2;}
					if(strpos($key, ".") !== FALSE){
						/*if(is_array($query_params["x"]) && count($query_params["x"])>1){
							$row["x"][explode('.', $key)[0]][explode('.', $key)[1]] = $val;
						} else {*/
							$row["x"][explode('.', $key)[1]] = $val;
						/*}*/
					} else {
						if(is_array($query_params["x"]) && count($query_params["x"])>1){
							$row["x"][$key] = $val;
						} else {
							$row["x"] = $val;
						}
						
					}
				}
				
			}
			if(count(array_keys($row)) == 1 && array_keys($row)[0] == "x") continue;
			$data_array[] = $row;
		}
		
		echo json_encode($data_array);
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		return $response;
		
	}
	
	public function callALternativeExport($datasetid, $resourceid) {
		$callUrl =  $this->urlCkan . "api/action/resource_show?id=" . $resourceid;
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getSimpleOptions());
		$res = curl_exec($curl);
		echo $res . "\r\n";
		curl_close($curl);
		$res = json_decode($res, true);
		
		header('Location: '.$res["result"]["url"]);
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	public function mapBuilder($idmap) {
		$method = $_SERVER['REQUEST_METHOD'];
		$table = "d4c_maps";
		$idUser = null;
		
		if(\Drupal::currentUser()->isAuthenticated()){
			$idUser = \Drupal::currentUser()->id();
    	} else {
			$response = new Response();
			$response->setStatusCode(404);
			$response->headers->set('Content-Type', 'application/json');
			
			return $response;
		}

		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		
		switch ($method) {
			case 'POST': //create map, no idMap
				//$data = json_decode(file_get_contents('php://input'), true);  
				$data = file_get_contents('php://input');  
				$data = json_decode($data, true);
				$name = str_replace(" ", "-", strtolower($data["title"]));
				$res = $this->getMaps($idUser, $name);
				if(count($res) > 0){
					$name .= (count($res)+1);
				}
				$data["persist_id"] = $name;
				$data = json_encode($data);
				$query = \Drupal::database()->insert($table);
				$query->fields([
					'map_id',
					'map_id_user',
					'map_name',
					'map_json'
				]);
				$query->values([
					0,
					$idUser,
					$name,
					$data
				]);

				$query->execute();
				
				$response->setStatusCode(200);
				echo $data;
				break;
			case 'PUT': //save existing map, get idMap
				if($idmap == ""){
					$response = new Response();
					$response->setStatusCode(500);
					$response->headers->set('Content-Type', 'application/json');
					
					return $response;	
				}
				//$data = json_decode(file_get_contents('php://input'), true);  
				$data = file_get_contents('php://input');
				
				$query = \Drupal::database()->update($table);
				$query->fields([
					'map_json' => $data
				]);
				$query->condition('map_name', $idmap);
				$query->condition('map_id_user', $idUser);
				$query->execute();
				
				$response->setStatusCode(200);

				break;
			case 'GET':  //if idMap => getMap, if not => listMap
				$res = $this->getMaps($idUser, $idmap);
				$data_array;
				if($idmap != '' && $idmap != null){//echo json_encode($res);
					$data_array = $res[0]->map_json;
					echo $data_array;
				} else {
					$data_array = array();
					foreach($res as $map){
						$data_array[] = json_decode($map->map_json, TRUE);	
					}
					echo json_encode($data_array);
				}
				
				/*if(count($res) == 1){//echo json_encode($res[0]);
					$res = $res[0]->json;
					echo $res;
				} else {
					echo json_encode($res);
				}*/
				
				break;
			case 'DELETE':  //delete existing map, get idMap
				if($idmap == ""){
					$response = new Response();
					$response->setStatusCode(500);
					$response->headers->set('Content-Type', 'application/json');
					
					return $response;	
				}
				$query = \Drupal::database()->delete($table);
				$query->condition('map_name', $idmap);
				$query->condition('map_id_user', $idUser);

				$query->execute();

				break;
		}
				
		return $response;
	}
	
	public function getMaps($idUser, $idMap) {
        Logger::logMessage("Getting maps for user : " . $idUser ."\r\n");

		$table = "d4c_maps";
		$query = \Drupal::database()->select($table, 'map');

		$query->fields('map', [
			'map_id',
			'map_id_user',
			'map_name',
			'map_json'
		]);
		
		$query->condition('map_id_user',$idUser);
		if($idMap != "" && $idMap != null){
			$query->condition('map_name',$idMap);
		}
		
		$prep=$query->execute();
		//$prep->setFetchMode(PDO::FETCH_OBJ);
		$res= array();
		while ($enregistrement = $prep->fetch()) {
			array_push($res, $enregistrement);
		}
		return $res;
	}
	
	public function isLoggedIn() {
		$data_array = array();
		$data_array["logged_in"] = \Drupal::currentUser()->isAuthenticated();
		$data_array["pending"] = false;
		echo json_encode($data_array);
		$response = new Response();
//		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		
		return $response;
	}
	public function getCustomView($idDataset) {
		$table = "d4c_custom_views";
		$query = \Drupal::database()->select($table, 'map');

		$query->fields('map', [
			'cv_id',
			'cv_name',
			'cv_title',
			'cv_icon',
			'cv_template'
		]);
		
		$query->condition('cv_dataset_id',$idDataset);		
		$prep=$query->execute();
		//$prep->setFetchMode(PDO::FETCH_OBJ);
		$res= array();
		while ($enregistrement = $prep->fetch()) {
			array_push($res, $enregistrement);
		}
		if(count($res) > 0){
			$cv = $res[count($res)-1];
			
			$table = "d4c_custom_views_html";
			$query = \Drupal::database()->select($table, 'map');

			$query->fields('map', [
				'cvh_html',
				'cvh_order'
			]);
			
			$query->condition('cvh_id_cv',$cv->cv_id);
			$query->orderBy('cvh_order', 'ASC');
			
			$prep=$query->execute();
			//$prep->setFetchMode(PDO::FETCH_OBJ);
			$html= array();
			while ($enregistrement = $prep->fetch()) {
				array_push($html, $enregistrement);
			}
			$cv->html = $html;
			
			return $cv;
		} else {
			return null;
		}
	}
	
	public function getMapLayers($type = null) {
		$jsonTiles = json_encode($this->config->map_tiles);

		$data_array = array();
		$tiles = json_decode($jsonTiles, true);

		if($type != null){
			foreach($tiles as $tile){
				if($tile["type"] == $type){
					$data_array["layers"][] = $tile;
				}
			}
		} else {
			$data_array["layers"] = $tiles;
		}

		Logger::logMessage("Found bounding box " . $this->config->client->default_bounding_box);
		
		$default_bbox = $this->config->client->default_bounding_box;
		if($default_bbox != null && $default_bbox != ""){
			$data_array["default_bbox"] = $default_bbox;
		} else {
			$data_array["default_bbox"] = null;
		}
		
		return $data_array;
	}
	
	public function callMapLayers($params) {
		$type = null;
		$query_params = $this->proper_parse_str($params);
		if(array_key_exists('type', $query_params)){
			$type = $query_params["type"];
		}
		$data_array = $this->getMapLayers($type);
		
		echo json_encode($data_array);
		
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		
		return $response;
	}
	
	public function addMapLayer($params) {
		if(is_array($params)){
			$tile = $params;
		} else {
			$tile = $this->proper_parse_str($params);
		}
		
		/*$tile = array();
		$tile["name"] = $query_params["name"];
		$tile["label"] = $query_params["label"];
		$tile["url"] = $query_params["url"];
		$tile["minZoom"] = $query_params["minZoom"];
		$tile["maxZoom"] = $query_params["maxZoom"];
		$tile["type"] = $query_params["type"];
		$tile["key"] = $query_params["key"];*/
		
		$this->config->map_tiles[] = $tile;
		//drupal_set_message(json_encode($this->config->map_tiles));
		//drupal_set_message(__DIR__ ."/../../config.json");
		//json_decode(file_get_contents(__DIR__ ."/../../config.json"));
		$res = file_put_contents(__DIR__ ."/../../config.json", json_encode($this->config, JSON_PRETTY_PRINT));
		
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		
		return $response;
	}
	
	public function updateMapLayer($params) {
		
		if(is_array($params)){
			$tile = $params;
		} else {
			$tile = json_decode($_POST["json"], true);
		}
		$content2 = $_POST["json"];
		error_log(json_encode($content2));
		$exists = false;
		foreach($this->config->map_tiles as &$layer){
			if($layer->name == $tile["name"]){
				$layer = $tile;
				$exists = true;
				break;
			}
		}
		if(!$exists){
			$this->config->map_tiles[] = $tile;
		}

		file_put_contents(__DIR__ ."/../../config.json", json_encode($this->config, JSON_PRETTY_PRINT));
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		
		return $response;
	}
	
	public function deleteMapLayer($idLayer) { //id directement
		/*if(is_array($params)){
			$tile = $params;
		} else {
			$tile = $this->proper_parse_str($params);
		}*/
		error_log(json_encode($this->config->map_tiles));
		$arr = array();
		foreach($this->config->map_tiles as $layer){
			if($layer->name != $idLayer){//drupal_set_message(json_encode($this->config->map_tiles[$layer]));
				//unset($this->config->map_tiles[$i]);
				$arr[] = $layer;
				//break;
			}
		}
		$this->config->map_tiles = $arr;
		error_log(json_encode($this->config->map_tiles));
		file_put_contents(__DIR__ ."/../../config.json", json_encode($this->config, JSON_PRETTY_PRINT));
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		
		return $response;
	}
	
	public function precluster() {
		$query = $_SERVER['QUERY_STRING'];
		$callUrl = $this->config->cluster->url . "precluster";
		$callUrl .= "?".$query;

		
		//echo $callUrl . "\r\n";
		$curl = curl_init($callUrl);
		$opt = $this->getSimpleGetOptions();
		//$data = json_encode($this->proper_parse_str($query));
		/*$opt[CURLOPT_POSTFIELDS] = $data;
		$opt[CURLOPT_HTTPHEADER] = array(                                                                          
			'Content-Type: application/json',                                                                                
			'Content-Length: ' . strlen($data)                                                                       
		); */                                        
		curl_setopt_array($curl, $opt);    
		$res = curl_exec($curl);
		curl_close($curl);
		//echo json_encode($opt) . "\r\n";
		
		echo $res;
		$response = new Response();
//		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	public function callPackageSearchWithRecordsCount($params) {
		$params = str_replace("qf=title^3.0 notes^1.0", "qf=title^3.0+notes^1.0", $params);	 
		$callUrl =  $this->urlCkan . "api/action/package_search";
		
        
        if(!is_null($params)){
			$callUrl .= "?" . $params;
		} 
        $cle = $this->config->ckan->api_key;
			$options = array (
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_HTTPHEADER => array (
						'Content-type:application/json',
						'Content-Length: ' . strlen ( $jsonData ),
						'Authorization:  ' .$cle 
				)
		);	
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $options);
		$result = curl_exec($curl);
		//echo $callUrl;
		curl_close($curl);

		$result = json_decode($result,true);
        $data = array();
		unset($result["help"]);//echo count($result["result"]["results"]);
		foreach($result["result"]["results"] as $i => $dataset) {
			foreach($dataset["resources"] as $j => $value) {
				//unset($result["result"]["results"][$i]["resources"][$j]["url"]);	//echo $value["url"];
				
				$format = $result["result"]["results"][$i]["resources"][$j]["format"];
				if(($format == "CSV" || $format = "XLS" || $format == "XLSX") && $result["result"]["results"][$i]["resources"][$j]["datastore_active"] == true){
					$req["sql"] = 'Select count(*) from "' . $result["result"]["results"][$i]["resources"][$j]["id"] . '"';
					$url2 = http_build_query($req);
					$callUrl =  $this->urlCkan . 'api/action/datastore_search_sql?'.$url2;
					$curl = curl_init($callUrl);
					curl_setopt_array($curl, $this->getSimpleOptions());
					$res = curl_exec($curl);
//					//echo $callUrl;
					curl_close($curl);
					$res = json_decode($res,true);
					$data[$dataset["id"]] = floatval($res["result"]["records"][0]["count"]);
					break;
				}	
			}
		}
				
		echo json_encode($data);
		$response = new Response();
		$response->setContent();
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}   
	
	
	function callVanillaUrlReports(){
    
        $this->config = json_decode(file_get_contents(__DIR__ ."/../../config.json"));
        $vanilla=$this->config->vanilla->url;
        $result = Query::callSolrServer($vanilla."/VanillaRuntime/vanillaExternalAccess?objecttype=url");
        $response = new Response();
		$response->setContent($result);
		$response->headers->set('Content-Type', 'application/json');
        
		return $response;    
        
        
    }
    
    function getCsvXls($params){



        $params = explode(";", $params);
        $url = $params[0];
		$url =str_replace('!', '/', $url);

	

        
        $format = $params[1];
        $site = $params[2];
        
        $site_2 = explode(":", $site);
   
        
        if($site=='Public.OpenDataSoft.com'){
           $url = 'https://public.opendatasoft.com/explore/dataset/'.$url.'/download/?format=csv&timezone=Europe/Madrid&use_labels_for_header=true'; 
        }
        
        if($site_2[0]=='odsall'){
			$url = 'https://'.$site_2[1].'/explore/dataset/'.$url.'/download/?format=csv&timezone=Europe/Madrid&use_labels_for_header=true';
        }
        
        if($site_2[0]=='socrata'){
			$url = 'https://'.$url.'/resource/'.$site_2[1].'.csv';   
        }
        
        if($site=='Ckan' ){
            if($_SERVER['HTTP_HOST']=='192.168.2.217'){
                $url = preg_replace("(^https?://)", "http://", $url );
            }
        }

		Logger::logMessage("Getting CSV / XLS with URL '" . $url . "' \r\n");
        
        $arr=array();
        if($format=='csv'|| $format=='CSV'){
                 
            $delimiter = $this->getFileDelimiter($url);
           
            $arr1 =file($url);
            $arr = array();
            $a=15;
            
            if(count($arr1)<15){
				$a=count($arr1);
			}

            for($i=0; $i<$a; $i++){

				$text = $arr1[$i];
				//Trying to detect encoding to convert automatically to UTF-8
				$arr[$i] = iconv(mb_detect_encoding($text, mb_detect_order(), true), "UTF-8", $text);

				// OLD way to keep track in case
				// $arr[$i] = utf8_decode(iconv("UTF-8", "ISO-8859-1//IGNORE", $arr1[$i]));
            }

            if($arr[0]==null || $arr[0]==''){
				$arr[0]="Pas d'accès de ligne ou de colonne aux tables non tabulaires";
			}
            $arr=array('delimiter'=>$delimiter, 'data'=>$arr);
        }
        else if($format=='XLS'|| $format=='xls' || $format=='XLSX'|| $format=='xlsx' ){
                
        }

        $response = new Response();
		$response->setContent(json_encode($arr));
		$response->headers->set('Content-Type', 'application/json');
        
		return $response; 
        
    }
    
    function getFileDelimiter($file, $checkLines = 2){
        $file = new SplFileObject($file);
        $delimiters = array(
          ',',
          '\t',
          ';',
          '|',
          ':'
        );
        $results = array();
        $i = 0;
         while($file->valid() && $i <= $checkLines){
            $line = $file->fgets();
            foreach ($delimiters as $delimiter){
                $regExp = '/['.$delimiter.']/';
                $fields = preg_split($regExp, $line);
                if(count($fields) > 1){
                    if(!empty($results[$delimiter])){
                        $results[$delimiter]++;
                    } else {
                        $results[$delimiter] = 1;
                    }   
                }
            }
           $i++;
        }
        $results = array_keys($results, max($results));
        return $results[0];
    }
    
    function nettoyage( $str, $charset='utf-8' ) {   
		$str = utf8_decode($str);
				
		$str = str_replace("?", "", $str);   
		//$label = preg_replace('@[^a-zA-Z0-9_]@','',$label);
		$str = str_replace("`", "_", $str);
		$str = str_replace("'", "_", $str);
		$str = str_replace("-", "_", $str);
		$str = str_replace(" ", "_", $str);
		$str = str_replace("%", "1", $str);
		$str = str_replace("(", "1", $str);
		$str = str_replace(")", "1", $str);
		$str = str_replace("*", "1", $str);
		$str = str_replace("!", "1", $str);
		$str = str_replace("@", "1", $str);
		$str = str_replace("#", "1", $str);
		$str = str_replace("$", "1", $str);
		$str = str_replace("^", "1", $str);
		$str = str_replace("&", "1", $str);
		$str = str_replace("+", "1", $str);
		$str = str_replace(":", "1", $str);
		$str = str_replace(">", "1", $str);
		$str = str_replace("<", "1", $str);
		$str = str_replace('\'', "_", $str);
		$str = str_replace("/", "_", $str);
		$str = str_replace("|", "_", $str);
		$str = strtolower($str);     
		$str = preg_replace( '#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str );
		$str = preg_replace( '#&([A-za-z]{2})(?:lig);#', '\1', $str );
		$str = preg_replace( '#&[^;]+;#', '', $str );      
		
		return $str;     
			 
	}
    
    function callInfocom94($params){
        
        $this->config = json_decode(file_get_contents(__DIR__ ."/../../config.json"));
        $siteSearch1=$this->config->sitesSearch;
        $siteSearch=array();
        foreach($siteSearch1 as &$val){
			if($_SERVER['HTTP_HOST']=='192.168.2.217'){
                if("http://".$_SERVER['HTTP_HOST']."/"!=$val){
					array_push($siteSearch, $val);  
				}
            }
            else{
                if("https://".$_SERVER['HTTP_HOST']."/"!=$val){
					array_push($siteSearch, $val);    
				}
            }
        }
        
//        $siteSearch=[
//        'https://mla.data4citizen.com/',    
//        'http://192.168.2.217/',
//        'https://infocom94.data4citizen.com/',
//        'https://chennevieres.data4citizen.com/',
//        'https://boissy.data4citizen.com/',
//        'https://sucy.data4citizen.com/',
//        /*'https://mandres.data4citizen.com/',
//        'https://nogent.data4citizen.com/',
//        'https://la-queue-en-brie.data4citizen.com/',
//        'https://perigny.data4citizen.com/',
//        'https://ormesson.data4citizen.com/',
//        'https://saintmaur.data4citizen.com/',
//        'https://creteil.data4citizen.com/',
//        'https://maisonsalfort.data4citizen.com/',
//        'https://limeil.data4citizen.com/',
//        'https://gpsea.data4citizen.com/',
//        'https://villiers.data4citizen.com/',
//        'https://saintmaurice.data4citizen.com/',
//        'https://villecresnes.data4citizen.com/',
//        'https://marolles.data4citizen.com/'*/
//            
//        ];

		Logger::logMessage("Searching in other Data4Citizen sites linked \r\n");
       
        $result=array();
        foreach($siteSearch as &$val){
			
			$callSolrUrl = $val . "api/datasets/2.0/search/q=" . $params;
			Logger::logMessage("Call '" . $callSolrUrl . "' \r\n");
            $t = Query::callSolrServer($callSolrUrl);
            $t = json_decode($t);
            
            foreach($t->result->results as &$dataset){
                $dataset->siteOfDataset = $val;
                $dataset->url=$val.'/visualisation/table/?id='.$dataset->id;
                array_push($result,$dataset);
                
                
                $this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
                $this->urlCkan = $this->config->ckan->url;
                
                $cle = $this->config->ckan->api_key;
                $optionst = array(
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_HTTPHEADER => array(
                        'Content-type:application/json',
                        'Content-Length: ' . strlen($jsonData),
                        'Authorization:  ' . $cle,
                    ),
                );

				for($i=0; $i<count($dataset->resources); $i++){
					
					$callUrl = $val . "/datasets/update/getresourcebyid/".$dataset->resources[$i]->id;
					$res= Query::callSolrServer($callUrl);

					$dataset->resources[$i]->url = json_decode($res);	
				}  
                
            }
        }
        
        
        
        $response = new Response();
		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
        
		return $response;    
        
    }
    
    function callD4c($params){
        
        $params = explode(";", $params);

        $result=array();
		/*$callUrl = 'https://'.$params[0]."/api/datasets/2.0/search/q=".$params[1];
		$curlOrg = curl_init($callUrl);
		curl_setopt_array($curlOrg, $this->getSimpleOptions());
        $t = curl_exec($curlOrg);
        curl_close($curlOrg);
        echo 'https://'.$params[0]."/api/datasets/2.0/search/q=".$params[1];*/
        $t = Query::callSolrServer('https://'.$params[0]."/api/datasets/2.0/search/q=".$params[1]);
		//echo $t; 
		$t = json_decode($t);
		
		Logger::logMessage("callD4c - Search on " . $params[0] . " with params = " . $params[1]);
		Logger::logMessage("Found " . count($t->result->results) . " results.");
           
		foreach($t->result->results as &$dataset){
			$dataset->siteOfDataset = $params[0];
			$dataset->url='https://'.$params[0].'/visualisation/table/?id='.$dataset->id;

            
			for($i=0; $i<count($dataset->resources); $i++){
				
				if($_SERVER['HTTP_HOST']=='192.168.2.217'){
					
					$res= Query::callSolrServer('http://'.$params[0]."/datasets/update/getresourcebyid/".$dataset->resources[$i]->id);   
				}
				else{
					$res= Query::callSolrServer('https://'.$params[0]."/datasets/update/getresourcebyid/".$dataset->resources[$i]->id);   
				}
            
                        
                $dataset->resources[$i]->url = json_decode($res);

                
			}  

			array_push($result,$dataset);
			
			//resource_show
                
                
                
		}
        
        
        
        
        $response = new Response();
		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
        
    return $response;    
        
    }
    
    function getResourceById($params){
       
        $this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
        $this->urlCkan = $this->config->ckan->url;
                
                $cle = $this->config->ckan->api_key;
                $optionst = array(
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_HTTPHEADER => array(
                        'Content-type:application/json',
                        'Content-Length: ' . strlen($jsonData),
                        'Authorization:  ' . $cle,
                    ),
                );
        
        $callUrl = $this->urlCkan . "/api/action/resource_show?id=".$params;   
                $curl = curl_init($callUrl);
                curl_setopt_array($curl, $optionst);
                $res  = curl_exec($curl);
                curl_close($curl);
                $res=json_decode($res);
        
        $response = new Response();
		$response->setContent(json_encode($res->result->url));
		$response->headers->set('Content-Type', 'application/json');
         return $response;
        
    }
    
    function getDataSetById($id){
    
		$this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
		$this->urlCkan = $this->config->ckan->url;
		$api = new Api;
		$cle = $this->config->ckan->api_key;
		$optionst = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => array(
                'Content-type:application/json',
                'Content-Length: ' . strlen($jsonData),
                'Authorization:  ' . $cle,
            ),
        );

        $callUrl = $this->urlCkan . "/api/action/package_show?id=".$id;

        $curl = curl_init($callUrl);
        curl_setopt_array($curl, $optionst);
        $res  = curl_exec($curl);
        curl_close($curl);
    //drupal_set_message('<pre>'. print_r($res, true) .'</pre>');
      
    
        $response = new Response();
		$response->setContent($res);
		$response->headers->set('Content-Type', 'application/json');
        return $response;
}
    
    function callSearchDataGouvOrg($params){
        $result = Query::callSolrServer("https://www.data.gouv.fr/api/1/organizations/?page_size=10000&q=".$params);
        $response = new Response();
		$response->setContent($result);
		$response->headers->set('Content-Type', 'application/json');
        
    return $response;    
    }
    
    function callSearchDataGouvDataset($params){
        $result = Query::callSolrServer("https://www.data.gouv.fr/api/1/datasets/?page_size=10000&q=".$params);
        $response = new Response();
		$response->setContent($result);
		$response->headers->set('Content-Type', 'application/json');
        
    return $response;    
    }
    
    function callSearchDataGouvDatasetByOrg($params){
        $result = Query::callSolrServer("https://www.data.gouv.fr/api/1/datasets/?page_size=10000&organization=".$params);
        $response = new Response();
		$response->setContent($result);
		$response->headers->set('Content-Type', 'application/json');
        
    return $response;    
    }
    
    function callSearchOpendatasoft($params){
        $result = Query::callSolrServer("https://public.opendatasoft.com/api/datasets/1.0/search/?q=".$params);
        
        
         //error_log("https://public.opendatasoft.com/api/v1/console/datasets/1.0/search/?q=".$params);
        
        $response = new Response();
		$response->setContent($result);
		$response->headers->set('Content-Type', 'application/json');
        
    return $response;    
    }
    
    function callSearchOpendatasoftAllSite($params){
        
         $params = explode(";", $params);
         $result = Query::callSolrServer("https://".$params[0]."/api/datasets/1.0/search/?q=".$params[1]);
        
        
         error_log("https://".$params[0]."/api/datasets/1.0/search/?q=".$params[1]);
        
        $response = new Response();
		$response->setContent($result);
		$response->headers->set('Content-Type', 'application/json');
        
    return $response;    
    }
    
	function callSearchSocrata($params){
        
        $params = explode(";", $params);
        
        //$result = Query::callSolrServer($params[0]."/api/catalog/v1?q=".$params[1]);
        $result = Query::callSolrServer("http://api.us.socrata.com/api/catalog/v1?domains=".$params[0]."&search_context=".$params[0]."&q=".$params[1]);
        
        
        $response = new Response();
		$response->setContent($result);
		$response->headers->set('Content-Type', 'application/json');
        
    return $response;    
    }	
    
    function ckanSearchCall($params){
        $params = explode(";", $params);
		
		$callUrl = 'https://' . $params[0] . "/api/3/action/package_search?rows=1000&q=" . $params[1];
        
		Logger::logMessage("ckanSearchCall - Search on " . $callUrl);
        
        //$result = Query::callSolrServer('https://'.$params[0]."/api/3/action/package_search?q=".$params[1]);
        $curl = curl_init($callUrl);
		$opt = $this->getSimpleGetOptions();                               
		curl_setopt_array($curl, $opt);    
		$result = curl_exec($curl);
		curl_close($curl);
		
		Logger::logMessage("ckanSearchCall - Found " . count(json_decode($result)->result->results) . " datasets");
        
        $response = new Response();
		$response->setContent($result);
		$response->headers->set('Content-Type', 'application/json');
        
    return $response;    
    }
    
    function callCustomView($params) {
        
      $idDataset=$params;
		$table = "d4c_custom_views";
		$query = \Drupal::database()->select($table, 'map');

		$query->fields('map', [
			'cv_id',
			'cv_name',
			'cv_title',
			'cv_icon',
			'cv_template'
		]);
		
		$query->condition('cv_dataset_id',$idDataset);		
		$prep=$query->execute();
        
        
        
		//$prep->setFetchMode(PDO::FETCH_OBJ);
		$res= array();
		while ($enregistrement = $prep->fetch()) {
			array_push($res, $enregistrement);
		}
        
        
		if(count($res) > 0){
			$cv = $res[count($res)-1];
			
			$table = "d4c_custom_views_html";
			$query = \Drupal::database()->select($table, 'map');

			$query->fields('map', [
				'cvh_html',
				'cvh_order'
			]);
			
			$query->condition('cvh_id_cv',$cv->cv_id);
			$query->orderBy('cvh_order', 'ASC');
			
			$prep=$query->execute();
			//$prep->setFetchMode(PDO::FETCH_OBJ);
			$html= array();
			while ($enregistrement = $prep->fetch()) {
				array_push($html, $enregistrement);
			}
			$cv->html = $html;
            
            
            
           
        $response = new Response();
		$response->setContent(json_encode($cv));
		$response->headers->set('Content-Type', 'application/json');
        
        return $response;  
		} else {
			return "null";
		}
//        $cv=$res;
//        
//      $response = new Response();
//		$response->setContent(json_encode($cv));
//		$response->headers->set('Content-Type', 'application/json');
//        
//        return $response;
        
        
	}
    
	function getPackageTheme(){
         

        $config_theme = \Drupal::service('config.factory')->getEditable('ckan_admin.themeForm');
        $t = $config_theme->get('themes');
//        $themes = json_decode($t);
         $response = new Response();
        $response->setContent($t);
		$response->headers->set('Content-Type', 'application/json');
		return $response;
         
        //return "test";
    }
    
    function updateNbDownload( $params ){
        $this->updatePackage($params,"nb_download");
        //$this->getThemeArray()
        $response = new Response();
		//$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
    }
    
    function updateNbViews( $params ){
        $this->updatePackage($params,"nb_views");
        $response = new Response();
//    	$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
    }
    
    function updatePackage($dataseId,$keyToUpdate){
        $result = $this->getPackageShow("id=".$dataseId);
        $keyExists = false;
        $index=0;
        for($i=0; $i< count($result['result']['extras']) ; $i++){
            
            if( $result['result']['extras'][$i]['key']==$keyToUpdate){
                $keyExists = true;
                $index= $i;
                break;
            }
        }        
        if( $keyExists ){
            //Incrementer
            //echo  "<h3>Cle existe deja --> incrementer </h3>";
            $result['result']['extras'][$index]['value'] = intval($result['result']['extras'][$index]['value']) +1 ;
        }    
        else{
            //Creer la cl�  et initialiser � 1
            //echo  "<h3>Cle n'existe pas --> Initialiser </h3>";
            $data = array(
                        "key" => $keyToUpdate,
                        "value" => "1"
            );
            array_push($result['result']['extras'] , $data  ) ;
            //echo "<h2> result apres ajout: </h2>" . "\r\n";
            //echo json_encode($result) ;
			
        }    
        $callUrl = $this->urlCkan."api/action/package_update";
		
        $return = $this->updateRequest($callUrl,$result['result'],"POST" );
    }

    function updateRequest($callUrl, $binaryData, $requestType) {
		$jsonData = json_encode( $binaryData );
        $cle = $this->config->ckan->api_key; 
		$options = array (
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST => $requestType,
				CURLOPT_POSTFIELDS => $jsonData,
				CURLOPT_HTTPHEADER => array (
						'Content-type:application/json',
						'Content-Length: ' . strlen ( $jsonData ),
						'Authorization:  ' .$cle 
				)
		);

		$curl = curl_init ( $callUrl );
		curl_setopt_array ( $curl, $options );
		$result = curl_exec ( $curl );
		curl_close ( $curl );
		return $result;
	}
	
	function externalCallDatapusher($resourceId) {
		$callUrl = 'http://127.0.0.1:8800/job';
		$cle = $this->config->ckan->api_key; 
		$url = $this->config->ckan->url; 
		$binaryData['api_key'] = $cle;
		$binaryData['job_type'] = 'push_to_datastore';
		$binaryData['metadata']['resource_id'] = $resourceId;
		$binaryData['metadata']['ckan_url'] = $url;
		$binaryData['api_key'] = $cle;
		// error_log(json_encode( $binaryData ));
		$jsonData = json_encode( $binaryData );
        
		$options = array (
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_POSTFIELDS => $jsonData,
				CURLOPT_HTTPHEADER => array (
					'Content-type:application/json',
					'Content-Length: ' . strlen ( $jsonData )
				)
		);
	
		$curl = curl_init ( $callUrl );
		curl_setopt_array ( $curl, $options );
		$result = curl_exec ( $curl );
		curl_close ( $curl );
		
		$response = new Response();
        $response->setContent('Done');
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	/**
	 * This method reload all resources from a Dataset in the Datastore
	 */
	function callDatapusher($resourceId) {
		$command = '/usr/lib/ckan/default/bin/paster --plugin=ckan datapusher submit_resource ' . $resourceId . ' -c /etc/ckan/default/production.ini';
        Logger::logMessage($command);

		$output = shell_exec($command);
        Logger::logMessage("Output " . $output);

		// $callUrl = 'http://127.0.0.1:8800/job';
		// $cle = $this->config->ckan->api_key; 
		// $url = $this->config->ckan->url; 
		// $binaryData['api_key'] = $cle;
		// $binaryData['job_type'] = 'push_to_datastore';
		// $binaryData['metadata']['resource_id'] = $resourceId;
		// $binaryData['metadata']['ckan_url'] = $url;
		// $binaryData['api_key'] = $cle;
		// // error_log(json_encode( $binaryData ));
		// $jsonData = json_encode( $binaryData );
        
		// $options = array (
		// 		CURLOPT_RETURNTRANSFER => true,
		// 		CURLOPT_CUSTOMREQUEST => 'POST',
		// 		CURLOPT_POSTFIELDS => $jsonData,
		// 		CURLOPT_HTTPHEADER => array (
		// 			'Content-type:application/json',
		// 			'Content-Length: ' . strlen ( $jsonData )
		// 		)
		// );
	
		// $curl = curl_init ( $callUrl );
		// curl_setopt_array ( $curl, $options );
		// $result = curl_exec ( $curl );
		// curl_close ( $curl );
		
		// $resp = json_decode($result);
		// // error_log($result);
		// $jobId = $resp->job_id;
		// $jobKey = $resp->job_key;
		// $i = 0;
		// while(true) {
		// 	$i = $i + 1;
		// 	if($i > 300) {
		// 		break;
		// 	}
		// 	sleep(1);
		// 	$options = array (
		// 		CURLOPT_RETURNTRANSFER => true,
		// 		CURLOPT_CUSTOMREQUEST => 'GET',
		// 		CURLOPT_HTTPHEADER => array (
		// 			'Authorization:  ' .$jobKey 
		// 		)
		// 	);
		// 	$curl = curl_init ( $callUrl . '/' . $jobId );
		// 	curl_setopt_array ( $curl, $options );
		// 	$result = curl_exec ( $curl );
		// 	curl_close ( $curl );
			
		// 	$resp = json_decode($result);
		// 	if($resp->status == 'complete' || $resp->status == 'error' || $resp->status == 'failed') {
		// 		break;
		// 	}
		// 	else {
		// 		$finished = false;
		// 		foreach ($resp->logs as $value) {
		// 			// error_log($value-> message);
		// 			$pos = strpos($value-> message, 'Saving chunk');
		// 			if($pos) {
		// 				$finished = true;
		// 				break;
		// 			}
		// 		}
		// 		if($finished) {
		// 			break;
		// 		}
		// 	}
		// }
	}
	
	// Old method keep for keeping tracks
	// function callDatapusher($resourceId) {
	// 	$callUrl = 'http://127.0.0.1:8800/job';
	// 	$cle = $this->config->ckan->api_key; 
	// 	$url = $this->config->ckan->url; 
	// 	$binaryData['api_key'] = $cle;
	// 	$binaryData['job_type'] = 'push_to_datastore';
	// 	$binaryData['metadata']['resource_id'] = $resourceId;
	// 	$binaryData['metadata']['ckan_url'] = $url;
	// 	$binaryData['api_key'] = $cle;
	// 	// error_log(json_encode( $binaryData ));
	// 	$jsonData = json_encode( $binaryData );
        
	// 	$options = array (
	// 			CURLOPT_RETURNTRANSFER => true,
	// 			CURLOPT_CUSTOMREQUEST => 'POST',
	// 			CURLOPT_POSTFIELDS => $jsonData,
	// 			CURLOPT_HTTPHEADER => array (
	// 				'Content-type:application/json',
	// 				'Content-Length: ' . strlen ( $jsonData )
	// 			)
	// 	);
	
	// 	$curl = curl_init ( $callUrl );
	// 	curl_setopt_array ( $curl, $options );
	// 	$result = curl_exec ( $curl );
	// 	curl_close ( $curl );
		
	// 	$resp = json_decode($result);
	// 	// error_log($result);
	// 	$jobId = $resp->job_id;
	// 	$jobKey = $resp->job_key;
	// 	$i = 0;
	// 	while(true) {
	// 		$i = $i + 1;
	// 		if($i > 300) {
	// 			break;
	// 		}
	// 		sleep(1);
	// 		$options = array (
	// 			CURLOPT_RETURNTRANSFER => true,
	// 			CURLOPT_CUSTOMREQUEST => 'GET',
	// 			CURLOPT_HTTPHEADER => array (
	// 				'Authorization:  ' .$jobKey 
	// 			)
	// 		);
	// 		$curl = curl_init ( $callUrl . '/' . $jobId );
	// 		curl_setopt_array ( $curl, $options );
	// 		$result = curl_exec ( $curl );
	// 		curl_close ( $curl );
			
	// 		$resp = json_decode($result);
	// 		if($resp->status == 'complete' || $resp->status == 'error' || $resp->status == 'failed') {
	// 			break;
	// 		}
	// 		else {
	// 			$finished = false;
	// 			foreach ($resp->logs as $value) {
	// 				// error_log($value-> message);
	// 				$pos = strpos($value-> message, 'Saving chunk');
	// 				if($pos) {
	// 					$finished = true;
	// 					break;
	// 				}
	// 			}
	// 			if($finished) {
	// 				break;
	// 			}
	// 		}
	// 	}
	// }
	
	function callDatapusherJobStatus($resourceId) {
		$result = $this->getDatapusherJobStatus($resourceId);

		echo json_encode($result);
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	function getDatapusherJobStatus($resourceId) {
		Logger::logMessage("Get datapusher status for resource '" . $resourceId . "' \r\n");

		$database = \Drupal\Core\Database\Database::getConnection('ckan', 'ckan');
		$query = $database->query("SELECT id, entity_id, value, state, error FROM task_status WHERE entity_id = '" . $resourceId . "' and task_type = 'datapusher'");
		$task = $query->fetchAssoc();

		if ($task) {
			$jobValue = json_decode($task["value"], true);
			$jobId = $jobValue["job_id"];

			$callUrl = 'http://127.0.0.1:8800/job/' .$jobId;
			Logger::logMessage("Getting datapusher infos '" . $callUrl . "' \r\n");
	
			$cle = $this->config->ckan->datapusher_key; 
			$url = $this->config->ckan->url; 
	
			$options = array (
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_CUSTOMREQUEST => 'GET',
					// CURLOPT_POSTFIELDS => $jsonData,
					CURLOPT_HTTPHEADER => array (
						'Authorization: ' . $cle
					)
			);
	
			$curl = curl_init ( $callUrl );
			curl_setopt_array ( $curl, $options );
			$result = curl_exec ( $curl );
			curl_close ( $curl );

			return $result;
		}

		throw new \Exception("Impossible de trouver une tâche associée à la ressource '" . $resourceId . "'");
	}

    function sortDatasetbyKey($key){
        //global $key ;
        //$key="nb_download";//"nb_download" "nb_views"
        $datasetList = $this->getPackageSearch("q=");
        //echo $datasetList->getContent() ;
        $data = json_decode($datasetList->getContent() );
        $dataJson = $datasetList->getContent() ;
       // echo "<!Doctype html><html>";
        $key_found=false;
        $listbyKey = $this->getdatasetListByKey($key);
        if( $key=="nb_download"){
            usort($listbyKey , function($a,$b){
                $key="nb_download";//"nb_download";
                return $b[$key] - $a[$key] ;
            }  ) ;
        }
        else if( $key=="nb_views" ) {
            usort($listbyKey , function($a,$b){
                $key="nb_views";//"nb_download";
                return $b[$key] - $a[$key] ;
            }  ) ;            
        }
        $result = $listbyKey;
        $response = new Response();
        $response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
    }
    
    function getdatasetListByKey($key){
        $datasetList = $this->getPackageSearch("q=");
        $dataJson = $datasetList->getContent() ;
        //echo "$dataJson" ;
        $data = json_decode($datasetList->getContent() );
        for($i=0; $i< count($data->result->results) ; $i++){
            $id=$data->result->results[$i]->id;
            $title=$data->result->results[$i]->title;
            $key_found=false;
            $nb_download=0;
			$theme="";
			$nb_view=0;
            $themeList = array();
            //if( $i==0 ) var_dump( $data->result->results[$i]->extras[0] ) ;
           /* if($key=="theme"){
                for($j=0; $j<count($data->result->results[$i]->extras) ; $j++ ){
                    if( $data->result->results[$i]->extras[$j]->key == "nb_download" ){
                        $nb_download = $data->result->results[$i]->extras[$j]->value;
                    }
                }                
            }
            
            for($j=0; $j<count($data->result->results[$i]->extras) ; $j++ ){
                
                if( $data->result->results[$i]->extras[$j]->key == $key ){
                    
                    if($key=="theme"){
                        $listbyKey[] = array( "id" => $id ,
                                         "title" => $title,
                                        "nb_download" => $nb_download, 
                                        "$key" => $data->result->results[$i]->extras[$j]->value
                        ) ;  
                    }
                    else{
                        $listbyKey[] = array( "id" => $id ,
                                         "title" => $title,
                                        "$key" => $data->result->results[$i]->extras[$j]->value
                        ) ;  
                    }

                }
                
            }*/
			for($j=0; $j<count($data->result->results[$i]->extras) ; $j++ ){
                if( $data->result->results[$i]->extras[$j]->key == "nb_download" ){
					$nb_download = $data->result->results[$i]->extras[$j]->value;
				}
				if( $data->result->results[$i]->extras[$j]->key == "nb_view" ){
					$nb_view = $data->result->results[$i]->extras[$j]->value;
				}
				if( $data->result->results[$i]->extras[$j]->key == "theme" ){
					$theme = $data->result->results[$i]->extras[$j]->value;
				}
            }
			$listbyKey[] = array( "id" => $id ,
                                  "title" => $title,
                                  "nb_download" => $nb_download,
                                  "nb_view" => $nb_view,
                                  "theme" => $theme
                        );
        }
        
        //var_dump($listbyKey);
        return $listbyKey ;
    }
    
    function getThemeArray(){
 
        $listbyKey = $this->getdatasetListByKey("theme") ;
        
        for($l=0; $l<count($listbyKey);$l++ ){
            
            $tmp = explode(',', $listbyKey[$l]["theme"] ) ;
            for($n=0; $n<count($tmp);$n++ ){
                $tmp[$n] = trim($tmp[$n] );
            }
                        
            for($k=0; $k <count($tmp); $k++ ){       
                if( !in_array( trim($tmp[$k]),$themeList )){
                    $themeList[] = $tmp[$k];
                }
            }
        }
        
        //echo " list apres" ;
        
       // echo json_encode($listbyKey);
        
        $result = $themeList;
        $response = new Response();
        $response->setContent(json_encode($themeList));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
      }
    
    //-------------
    function themebydownload(){
        
        $theme = $this->getThemeArray() ;
        $theme = json_decode ($theme->getContent() ); 
        $dataset = $this->getdatasetListByKey("theme");
        $list = array();
        //var_dump($theme);
        //($dataset);
        for($i=0; $i<count($theme) ; $i++){
            
            $list[$i] = array("theme" => $theme[$i],
                            "nb_download"=>0
                           );
        
            for($j=0; $j<count($dataset) ; $j++ ){
                
                if($dataset[$j]['theme'] !=null ){
                    $tmp = explode(',',$dataset[$j]['theme']) ;
                    $tmp = $this->trim_array($tmp);
                    //$dataset[$j]['theme'] !=null 
                   if( in_array( $theme[$i], $tmp  )   ){
                       //echo $theme[$i] ." existe dans ".$dataset[$j]['title'] ."\r";
                       $list[$i]["nb_download"] =    $list[$i]["nb_download"] + $dataset[$j]['nb_download'];
                    }
                }
            }
        }
        
        usort($list , function($a,$b){
            $key="nb_download";//"nb_download";
            return $b[$key] - $a[$key] ;
        }  ) ;  
        
        $result = $list;
        //$result = $dataset;
        $response = new Response();
        $response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
        
    }
    
    function datasetByTheme($theme){
        // $datasetList = $this->getPackageSearch("rows=1000");
		$datasetList = $this->callPackageSearch_public_private('include_private=true&rows=1000');
		
        // $dataJson = $datasetList ;
        // //echo $dataJson ;
        // $data = json_decode($datasetList);
        // $dataset_list = $data->result->results ;
		$datasetList = $datasetList->getContent();
		
		$dataset_list = json_decode($datasetList)->result->results;
		
        $selectedDataset = array();
         //echo " theme à chercher trimé:".trim($theme)."\r" ;
        
        //echo " count : ".count($dataset_list) ."\r";
        for($i=0; $i< count($dataset_list) ; $i++){
			
            $theme_found=false;
			for($j=0; $j<count($dataset_list[$i]->extras) ; $j++ ){
				
				if( $dataset_list[$i]->extras[$j]->key == "theme" ){
					$dataset_theme = $dataset_list[$i]->extras[$j]->value;
					// error_log('dataset2 : ' . $dataset_theme);
                   // echo " dataset a ajouter ". $dataset_list[$i]->title." theme :".trim($dataset_theme)." \r" ;
                    //echo " resultat de stristrt : " .stristr( trim($dataset_theme) , trim($theme)  )." \r";          
                    if ( stristr( trim($dataset_theme) , trim($theme)  ) !==False ) $selectedDataset[] = $dataset_list[$i] ;
                        //echo "  dataset ajouté ". $dataset_list[$i]->title." theme :".$dataset_theme." ! \r" ;
                    $theme_found = true ;
				}                
            }
            
            //echo " dataset a ajouter ". $dataset_list[$i]->title." theme :".$theme . " theme_found: ".$theme_found."\r";
            if( $theme=="default"  && $theme_found==false ){
                $selectedDataset[] = $dataset_list[$i] ;
                 //echo " dataset a ajouter ". $dataset_list[$i]->title." \r" ;
            }
            
            
        }
        
        //echo json_encode($selectedDataset );
        
        
        
        //$result = $list;
        //$result = $dataset;
        $response = new Response();
        $response->setContent(json_encode($selectedDataset));
		$response->headers->set('Content-Type', 'application/json');
		return $response;        
    }
    
    function trim_array($array){
        $res = array();
        for($n=0; $n<count($array);$n++ ){
            $res[] = trim($array[$n] );
        }        
        return $res;
    }
	
	function callSearchArcGIS($params){
		if($params == ''){ 
			$query_params = $_POST;
		} else {
			$query_params = $this->proper_parse_str($params);
		} 
        $result = Query::callSolrServer($query_params["url"]."?f=pjson");
    //error_log($result); 
        //error_log("https://".$params[0]."/api/datasets/1.0/search/?q=".$params[1]);
        
        $response = new Response();
		$response->setContent($result);
		$response->headers->set('Content-Type', 'application/json');
        
		return $response;    
    }
	
	function getAllOrganisations($allFields = TRUE, $include_extra = FALSE){
		$callUrlOrg =  $this->urlCkan . "api/action/organization_list?all_fields=".($allFields ? 'true' : 'false')."&include_extras=".($include_extra ? 'true' : 'false');
        $curlOrg = curl_init($callUrlOrg);
		curl_setopt_array($curlOrg, $this->getSimpleOptions());
        $orgs = curl_exec($curlOrg);
        curl_close($curlOrg);
        $orgs = json_decode($orgs, true);
		return $orgs["result"];
    }
	
	function callAllOrganisations($params){
		$query_params = $this->proper_parse_str($params);
		$all_fields = FALSE;
		$include_extras = FALSE;
		if($query_params["all_fields"]){
			$all_fields = TRUE;
		}
		if($query_params["include_extras"]){
			$include_extras = TRUE;
		}
		$result = $this->getAllOrganisations($all_fields, $include_extras);
		$response = new Response();
		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
        
		return $response;  
    }
	
	function calculateVisualisations($id, $blockDateModification=FALSE){
		//error_log($id);

		Logger::logMessage("Calculate visualisation for " . $id . "\r\n");
		
		$features = array(); //["timeserie", "analyze", "geo", "image", "calendar", "custom_view","wordcloud", timeline]
		$records_count = 0;
		$fields = array();
		
		//if($dataset == null){
			$dataset = $this->getPackageShow("id=".$id);
			$dataset = $dataset["result"];

			
			//Logger::logMessage("Found dataset " . json_encode($dataset) . "\r\n");
		//}
		//$id = $dataset["id"];
		
		//if(!$hasFields){
			$resourcesid = null;
			foreach ($dataset['resources'] as $value) {
				if(($value['format'] == 'CSV' || $value['format'] == 'XLS' || $value['format'] == 'XLSX') && $value["datastore_active"] == true){
					$resourcesid = $value['id'];
				}
			}
			if($resourcesid != null){
				$fields = $this->getAllFields($resourcesid, TRUE);
				$records_result = $this->getDatastoreApi("resource_id=".$resourcesid."&limit=0");
				$records_count = str_pad($records_result["result"]["total"], 10, "0", STR_PAD_LEFT);

				
				Logger::logMessage("Found ressource with id " .$resourcesid . " with record count = " . $records_count . "\r\n");
			}
		//}
	
		
		
		//$features[] = "analyze"; //tab chart

		if(count($fields)>0){
			Logger::logMessage("Search features" . "\r\n");

			$colStart = null;$colEnd = null;$colWordCount = null;$colTimeline=null;$colGeo=null;
			foreach($fields as $f){
				foreach($f["annotations"] as $a){
					if($a["name"] == "startDate"){
						$colStart = $f["name"];
					} else if($a["name"] == "endDate"){
						$colEnd = $f["name"];
					} else if($a["name"] == "date"){
						$colEnd = $f["name"];$colStart = $f["name"];
					} else if($a["name"] == "wordcount" || $a["name"] == "wordcountNumber"){
						$colWordCount = $f["name"];
					} else if($a["name"] == "date_timeLine"  || $a["name"] == "title_for_timeLine"  || $a["name"] == "descr_for_timeLine"){
						$colTimeline = $f["name"];
					}
				}
				/*if($colEnd != null && $colStart != null){
					//break;
				}*/
				if($f["type"] == "file"){
					$features[] = "image";
				}
				if($f["type"] == "geo_point_2d"  || $f["type"] == "geo_shape"){
					$colGeo = $f["name"];
				}
			}
			
			if($colStart != null && $colEnd != null){
				$features[] = "timeserie";
				$features[] = "calendar";
			}
			if($colWordCount != null){
				$features[] = "wordcloud";
			}
			if($colTimeline != null){
				$features[] = "timeline";
			}
			if($colGeo != null){
				$features[] = "geo";
			}
			
			$found = false;
			foreach($dataset['extras'] as $value){
				if($value["key"] == "dont_visualize_tab"){
					if(strpos($value["value"], "api") === false){
						$features[] = "api";
					}
					if(strpos($value["value"], "analize") === false){
						$features[] = "analyze";
					}
					$found = true;
					break;
				}
			}
			if(!$found){
				$features[] = "api";
				$features[] = "analyze";
			}
			$features[] = "table";
		}
		
		$customView = $this->getCustomView($dataset['id']);
		if($customView){
			Logger::logMessage("Found custom view" . "\r\n");

			$features[] = "custom_view";
		}
		
		$extras = $dataset["extras"];
		$foundFeat = false;
		$foundCount = false;
		$foundCV = false;
		$foundLM = false;
		foreach($extras as &$e){
			if($e["key"] == "records_count"){
				$e["value"] = $records_count;
				$foundCount = true;
			} else if($e["key"] == "features"){
				$e["value"] = implode(",", $features);
				$foundFeat = true;
			} else if($e["key"] == "custom_view" && $customView != null){
				$cv = array();
				$cv["title"] = $customView->cv_title;
				$cv["slug"] = $customView->cv_name;
				$cv["icon"] = $customView->cv_icon;
				$e["value"] = json_encode($cv);
				$foundCV = true;
			} else if($e["key"] == "date_moissonnage_last_modification"){
				$foundLM = true;
			}
		}
		if(!$foundCount){
			$extras[count($extras)]['key'] = 'records_count';
			$extras[(count($extras) - 1)]['value'] = $records_count;
		}
		if(!$foundFeat){
			$extras[count($extras)]['key'] = 'features';
			$extras[(count($extras) - 1)]['value'] = implode(",", $features);
		}
		if(!$foundCV && $customView != null){
			$extras[count($extras)]['key'] = 'custom_view';
			$cv = array();
			$cv["title"] = $customView->cv_title;
			$cv["slug"] = $customView->cv_name;
			$cv["icon"] = $customView->cv_icon;
			$extras[(count($extras) - 1)]['value'] = json_encode($cv);
		}
		if(!$foundLM){
			$extras[count($extras)]['key'] = 'date_moissonnage_last_modification';
			$extras[(count($extras) - 1)]['value'] = $dataset["metadata_modified"];
		}
		$dataset["extras"] = $extras;
		//error_log(json_encode($fields));
		//error_log(json_encode($dataset['extras']));
		if($blockDateModification){
			$dataset["modified_date_forced"] = true;
		}

		
		Logger::logMessage("Update package" . "\r\n");
		
		$callUrl = $this->urlCkan . "api/action/package_update";
		$this->updateRequest($callUrl, $dataset, "POST");
	}
	
	function callPackageSearchDownload($format, $params){
		$query_params = $this->proper_parse_str($params);
		$query_params["rows"] = 1000;
		$query_params["start"] = 0;
		unset($query_params["facet.field"]);
		
		$params = "";
		foreach($query_params as $key => $value){
			$params .= $key ."=". $value . "&";
		}
		$params = substr($params, 0, -1);
		//$params = implode("&",$query_params);
		//$params = http_build_query($query_params);
		
		$result = $this->getExtendedPackageSearch($params);
		
		foreach($result["result"]["results"] as &$dataset) {
			$dataset["metadata_imported"] = $dataset["metadata_modified"];
			$dataset["metadata_modified"] = current(array_filter($dataset["extras"], function($f){ return $f["key"] == "date_moissonnage_last_modification";}))["value"] ?: $dataset["metadata_modified"];
			$dataset["metadata_created"] = current(array_filter($dataset["extras"], function($f){ return $f["key"] == "date_moissonnage_creation";}))["value"] ?: $dataset["metadata_created"];
			
			foreach($dataset["resources"] as $j => $value) {
				unset($dataset["resources"][$j]["url"]);	//echo $value["url"];
			}
		}
		
		$result = $result["result"]["results"];
		
		if ($format == "csv") {
			header('Content-Type:text/csv');
			header('Content-Disposition:attachment; filename=datasets.csv');
		} else if ($format == "xls") {
			header('Content-Type:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			//header('Content-Disposition:attachment; filename='.$query_params['resource_id'].'.xls');
			header('Content-Disposition:attachment; filename=datasets.xlsx');
		} else if ($format == "json") {
			header('Content-Type:application/json');
			header('Content-Disposition:attachment; filename=datasets.json');
		} else {
			header('Content-Type:application/json');
			header('Content-Disposition:attachment; filename=datasets.json');
		}

		if ($format == "json") {
			echo json_encode($result);
		} else if ($format == "csv") {
			echo Export::getCSVfromJson($result);
		} else if ($format == "xls") {
			$csv = Export::getCSVfromJson($result);
			//We create a tmp file in which we write the result and an output file to convert
			$pathInput = tempnam(sys_get_temp_dir(), 'input_convert_geo_file_');
			$fileInput = fopen($pathInput, 'w');
			fwrite($fileInput, $csv);
			fclose($fileInput);

			//We rename the file because PhpSpreadsheet does not support conversion without
			rename($pathInput, $pathInput .= '.csv');

			$pathOutput = tempnam(sys_get_temp_dir(), 'output_convert_geo_file_');
			
			$reader = ReaderFactory::create(Type::CSV);
			$reader->setFieldDelimiter(';');
			$reader->setFieldEnclosure('"');
			$reader->setEndOfLineCharacter("\n");
			
			$writer = WriterFactory::create(Type::XLSX);
			
			$style = (new StyleBuilder())
			   //->setFontBold()
			   ->setFontSize(11)
			   ->setFontName('Calibri')
			   ->setShouldWrapText(false)
			   ->build();
			
			$reader->open($pathInput);
			$writer->openToFile($pathOutput); // write data to a file or to a PHP stream

			foreach ($reader->getSheetIterator() as $sheet) {
				foreach ($sheet->getRowIterator() as $row) {
					//$row->setStyle($style);
					$writer->addRowWithStyle($row, $style);
				}
			}//$writer->addRows($multipleRows); // add multiple rows at a time

			$reader->close();
			$writer->close();
			
			unlink($fileInput);

			header('Content-Length: ' . filesize($pathOutput));
			readfile($pathOutput);
		} else {
			echo json_encode($result);
		}

		$response = new Response();
		return $response;
	}
	
	function callCalculateVisualisations($id) {
		$this->calculateVisualisations($id);
		$response = new Response();
		return $response;
	}
	
	function reBuildAllDataset(){
		$allDatasets = $this->callPackageSearch_public_private("include_private=true&rows=10000");
		$allDatasets = $allDatasets->getContent();
        $allDatasets = json_decode($allDatasets, true);
		if($allDatasets["success"] == true){
			foreach($allDatasets["result"]["results"] as $d){
				$this->calculateVisualisations($d["id"], TRUE);
			}
			//echo count($allDatasets["result"]["results"]);
		}
		
		$response = new Response();
		return $response;
	}
	
	function callGetReuses($datasetid){
		$method = $_SERVER['REQUEST_METHOD'];
		
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		
		$data_array = array();
		
		switch ($method) {
			case 'POST': 
				$data = array();
				
				$r = date_default_timezone_set('Europe/Paris');
				
				if($_POST["recaptcha_response"] != ""){
					//check captcha
					$callUrl =  "https://www.google.com/recaptcha/api/siteverify";
					$data_string = array();
					$data_string["secret"] = "6LecPMcUAAAAAMUzjOwRKlPeAd43AR_PFFAhg8cb";
					$data_string["response"] = $_POST["recaptcha_response"];
					
					$curl = curl_init($callUrl);
					curl_setopt_array($curl, $this->getSimpleOptions());
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
					curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
						'Content-Type: application/json',                                                                                
						'Content-Length: ' . strlen($data_string))                                                                       
					); 
					$resp = curl_exec($curl);
					curl_close($curl);//error_log($resp);
					$resp = json_decode($resp, true);
					if($resp["success"] == false){
						$data_array["status"] = "captcha_failed";
						$data_array["message"] = json_encode($resp["error-codes"]);
						echo json_encode($data_array);
						return $response;
					}
				}
				
				if($_FILES['file']['size'] > 0){
					switch ($_FILES['upfile']['error']) {
						case UPLOAD_ERR_OK:
							break;
						case UPLOAD_ERR_NO_FILE:
							$data_array["status"] = "error";
							$data_array["message"] = 'No file sent.';
							echo json_encode($data_array);
							return $response;
							//throw new RuntimeException('No file sent.');
						case UPLOAD_ERR_INI_SIZE:
						case UPLOAD_ERR_FORM_SIZE:
							$data_array["status"] = "error";
							$data_array["message"] = 'Exceeded filesize limit.';
							echo json_encode($data_array);
							return $response;
							//throw new RuntimeException('Exceeded filesize limit.');
						default:
							$data_array["status"] = "error";
							$data_array["message"] = 'Unknown errors.';
							echo json_encode($data_array);
							return $response;
							//throw new RuntimeException('Unknown errors.');
					}

					// You should also check filesize here.
					if ($_FILES['upfile']['size'] > 1000000) {
						$data_array["status"] = "error";
						$data_array["message"] = 'Exceeded filesize limit.';
						echo json_encode($data_array);
						return $response;
						//throw new RuntimeException('Exceeded filesize limit.');
					}
					
					$finfo = new finfo(FILEINFO_MIME_TYPE);
					if (false === $ext = array_search(
						$finfo->file($_FILES['file']['tmp_name']),
						array(
							'jpg' => 'image/jpeg',
							'png' => 'image/png',
							'gif' => 'image/gif',
						),
						true
					)) { 
						$data_array["status"] = "error";
						$data_array["message"] = 'Invalid file format.';
						echo json_encode($data_array);
						return $response;
						//throw new RuntimeException('Invalid file format.');
					}
					
					//on ecrit le fichier
					$uploaddir = DRUPAL_ROOT . '/sites/default/files/reuses/';
					if (!file_exists($uploaddir)) {
						mkdir($uploaddir, 0777, true);
					}
					$uploadfile = $uploaddir . basename($_FILES['file']['name']);
					//error_log( "eeeeeeeee :: ".$uploadfile);
					
					if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
						error_log( "Le fichier est valide, et a été téléchargé avec succès. Voici plus d'informations :\n");
						$protocol = /*isset($_SERVER['HTTPS']) ? */'https://' /*: 'http://'*/;
						$url = $protocol.$_SERVER['HTTP_HOST'].'/sites/default/files/reuses/'.basename($_FILES['file']['name']);
					} else {
						error_log( "Attaque potentielle par téléchargement de fichiers. Voici plus d'informations :\n");
					}
					//error_log(json_encode($_FILES));
					$data["image"] = $url;
				} else {
					$data["image"] = $_POST["image"];
				}
				
				$name = str_replace(" ", "-", strtolower($_POST["title"]));
				
				if(\Drupal::currentUser()->isAuthenticated()){
					$user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
					//$data["author_name"] = $user->get('name')->value;
					$data["author_name"] = $_POST["author_name"];
					$data["author_email"] = $user->get('mail')->value;
					//error_log( "Connected !!");
				} else {
					//error_log( "Not connected ..");
					$data["author_name"] = $_POST["author_name"];
					$data["author_email"] = $_POST["author_email"];
				}
				
				$data["dataset_id"] = $datasetid;
				$data["dataset_title"] = $_POST["dataset_title"];
				$data["name"] = $name;
				$data["title"] = $_POST["title"];
				$data["description"] = $_POST["description"];
				$data["author_url"] = null;
				$data["url"] = $_POST["url"];
				//$data["image"] = $_POST["image"];
				$data["date"] = date("d/m/Y H:i:s");
				$data["status"] = 0;
				$data["type"] = $_POST["type"];
				
				$this->addReuse($data);
				
				$data_array = array();
				$data_array["status"] = "reuse_pending";
				echo json_encode($data_array);
				
				//reuse_pending
				//reuse_created
				//captcha_techerror
				//captcha_failed
				//error
				
				//envoie mail
				$sitename = \Drupal::config('system.site')->get('name');
				$langcode = \Drupal::config('system.site')->get('langcode');
				$module = 'ckan_admin';
				$key = 'addReuse';
				$to = \Drupal::config('system.site')->get('mail');
				$reply = NULL;
				$send = TRUE;

				$params['message'][] = t('Une réutilisation "@name" a été déposée par @author sur le site @sitename, et est en attente de validation de votre part.',
											array('@sitename' => $sitename,'@name' => $name,'@author' => $data["author_email"]));
				$params['message'][] = t('Titre de la réutilisation : @name', array('@name' => $name));
				$params['message'][] = t('Jeu de données concerné : @name', array('@name' => $data["dataset_title"]));
				$params['message'][] = t('Traiter la réutilisation : @url', array('@url' => "https://".$_SERVER['HTTP_HOST']."/admin/config/data4citizen/reusesManagement"));
				$params['message'][] = t("Cordialement.");
				$params['subject'] = t('Nouvelle réutilisation à valider');
				//$params['options']['username'] = "KMO";
				//$params['options']['title'] = t('gluglu');
				//$params['options']['footer'] = t('blabla');
				//$params['headers']['Content-Type'] = 'charset=UTF-8;';
				//$params['from'] = \Drupal::config('system.site')->get('mail');
				
				$mailManager = \Drupal::service('plugin.manager.mail');
				$mailManager->mail($module, $key, $to, $langcode, $params, $reply, $send);
				
				break;
			case 'GET':  
				$res = $this->getReuses(null, $datasetid, null, "online", null, null);
				echo json_encode($res);
				
				break;
		}
		
		return $response;
	}
	
	function getReuses($orga = null, $dataset = null, $q = null, $status = null, $rows = null, $start = null){
		
		$table = "d4c_reuses";
		$query = \Drupal::database()->select($table, 'reuse');

		$query->fields('reuse', [
			'reu_id',
			'reu_dataset_id',
			'reu_dataset_title',
			'reu_name',
			'reu_title',
			'reu_description',
			'reu_author_name',
			'reu_author_url',
			'reu_author_email',
			'reu_url',
			'reu_image',
			'reu_date',
			'reu_status',
			'reu_type'
		]);
		
		if ($dataset != null && $dataset != ""){
			$query->condition('reu_dataset_id',$dataset);
		} else if($orga != null && $orga != ""){
			
			$req = "include_private=true&rows=10000&q=organization:".$orga;

			$datasets = $this->getPackageSearch($req)["result"]["results"]; //error_log(json_encode($datasets));
			$ids = array();
			
			foreach($datasets as $row){
				$ids[] = $row["id"];
			}
			//$ids = implode(",", $ids);
			$query->condition('reu_dataset_id',$ids, "IN");
		} 

		if($q != null && $q != ""){
			$orGroup = $query->orConditionGroup()
				->condition('reu_title','%' . \Drupal::database()->escapeLike($q) . '%', 'LIKE')
				->condition('reu_description','%' . \Drupal::database()->escapeLike($q) . '%', 'LIKE');
  
			$query->condition($orGroup);
		}
		if ($status != null && $status != ""){
			if($status == "waiting"){
				$s = 0;
			} else if($status == "online"){
				$s = 1;
			} else if($status == "offline"){
				$s = 2;
			}
			$query->condition('reu_status',$s);
		}
		if ($rows != null && $rows != ""){
			if ($start != null && $start != ""){
				$query->range($start, $start+$rows);
			} else {
				$query->range(0, $rows);
			}			
		}
		$query->orderBy('reu_date', 'DESC');
		
		$prep=$query->execute();
		//$prep->setFetchMode(PDO::FETCH_OBJ);
		$res= array();
		while ($enregistrement = $prep->fetch()) {
			array_push($res, $enregistrement);
		}
		
		$query->range();
		$nhits= $query->countQuery()->execute()->fetchField();
		
		if(count($res) > 0){
			$data = array();
			$data["nhits"] = $nhits;
			
			foreach($res as $reu){
				$row = array();
				$row['id'] = $reu->reu_id;
				$row['dataset_id'] = $reu->reu_dataset_id;
				$row['dataset_title'] = $reu->reu_dataset_title;
				$row['name'] = $reu->reu_name;
				$row['title'] = $reu->reu_title;
				$row['description'] = $reu->reu_description;
				$row['author_name'] = $reu->reu_author_name;
				$row['author_url'] = $reu->reu_author_url;
				$row['author_email'] = $reu->reu_author_email;
				$row['url'] = $reu->reu_url;
				$row['image'] = $reu->reu_image;
				$row['date'] = $reu->reu_date;
				if($reu->reu_status == 0){
					$row['status'] = "waiting";
				} else if($reu->reu_status == 1){
					$row['status'] = "online";
				} else if($reu->reu_status == 2){
					$row['status'] = "offline";
				}
				$row['type'] = $reu->reu_type;
				$data["reuses"][] = $row;
			}
			
			//$data["reuses"] = $res;
			
			return $data;
		} else {
			$data = array();
			$data["nhits"] = $nhits;
			$data["reuses"] = array();
			return $data;
		}
	}
	
	function getReuse($id){
		$table = "d4c_reuses";
		$query = \Drupal::database()->select($table, 'reuse');

		$query->fields('reuse', [
			'reu_id',
			'reu_dataset_id',
			'reu_dataset_title',
			'reu_name',
			'reu_title',
			'reu_description',
			'reu_author_name',
			'reu_author_url',
			'reu_author_email',
			'reu_url',
			'reu_image',
			'reu_date',
			'reu_status',
			'reu_type'
		]);
		
		$query->condition('reu_id',$id);		
		$prep=$query->execute();
		//$prep->setFetchMode(PDO::FETCH_OBJ);
		$res= array();
		while ($enregistrement = $prep->fetch()) {
			array_push($res, $enregistrement);
		}
		if(count($res) > 0){
			$reu = $res[count($res)-1];
			$row = array();
			$row['id'] = $reu->reu_id;
			$row['dataset_id'] = $reu->reu_dataset_id;
			$row['dataset_title'] = $reu->reu_dataset_title;
			$row['name'] = $reu->reu_name;
			$row['title'] = $reu->reu_title;
			$row['description'] = $reu->reu_description;
			$row['author_name'] = $reu->reu_author_name;
			$row['author_url'] = $reu->reu_author_url;
			$row['author_email'] = $reu->reu_author_email;
			$row['url'] = $reu->reu_url;
			$row['image'] = $reu->reu_image;
			$row['date'] = $reu->reu_date;
			if($reu->reu_status == 0){
				$row['status'] = "waiting";
			} else if($reu->reu_status == 1){
				$row['status'] = "online";
			} else if($reu->reu_status == 2){
				$row['status'] = "offline";
			}
			$row['type'] = $reu->reu_type;
			return $row;
		} else {
			return null;
		}
	}
	
	function updateReuse($reuse){
		$reu_id = $reuse["id"];
		$query = \Drupal::database()->update('d4c_reuses');
		$query->fields([
			'reu_status' => $reuse["status"]			
		]);
		$query->condition('reu_id', $reu_id);
		$query->execute();
		
	}
	
	public function addReuse($params) {
		if(is_array($params)){
			$reuse = $params;
		} else {
			$reuse = $this->proper_parse_str($params);
		}
		//error_log(json_encode($reuse));
		$query = \Drupal::database()->insert('d4c_reuses');
		$query->fields([
			'reu_dataset_id',
			'reu_dataset_title',
			'reu_name',
			'reu_title',
			'reu_description',
			'reu_author_name',
			'reu_author_url',
			'reu_author_email',
			'reu_url',
			'reu_image',
			'reu_date',
			'reu_status',
			'reu_type'
		]);
		$query->values([
			$reuse["dataset_id"],
			$reuse["dataset_title"],
			$reuse["name"],
			$reuse["title"],
			$reuse["description"],
			$reuse["author_name"],
			$reuse["author_url"],
			$reuse["author_email"],
			$reuse["url"],
			$reuse["image"],
			$reuse["date"],
			$reuse["status"],
			$reuse["type"]
		]);

		$query->execute();
	}
	
	function callOrangeApiGetData($params){
		//ex : /webservice/?service=getData&key=nXG9o1MSJxHbs1qH&db=stationnement&table=disponibilite_parking&format=json
		
		$query_params = $this->proper_parse_str($params);
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json; charset=utf-8');
		if($query_params["service"] != "getData"){
			echo "Ce service n'est pas supporté";
			$response->setStatusCode(404);
			
		} else {
			$cle = $query_params["key"];
			$db = $query_params["db"];
			$dataset = $query_params["table"];
			$format = $query_params["format"];
			$limit = $query_params["limit"];
			$offset = $query_params["offset"];
			$start = "";$rows = "";
			if($limit != null && $limit != ""){
				if($offset != null && $offset != ""){
					$rows = "&rows=" . ($limit - $offset);
				} else {
					$rows = "&rows=" . $limit;
				}
			}
			if($offset != null && $offset != ""){
				$start = "&start=" . $offset;
			}
			
			$res = $this->getDatastoreRecord_v2("dataset=".$dataset.$rows.$start);
			//dataset q lang rows start sort facet refine exclude geofilter.distance geofilter.polygon timezone
			//echo json_encode($res);
			//$res = utf8_encode(json_encode($res));echo $res;
			//$res = mb_convert_encoding(json_encode($res), 'HTML-ENTITIES', 'UTF-8');echo $res;
			//$res = html_entity_decode(preg_replace("/U\+([0-9A-F]{4})/", "&#x\\1;", $string), ENT_NOQUOTES, 'UTF-8');echo $res;
			//$res = json_decode(json_encode($res), true);
			$url = (isset($_SERVER['HTTPS']) ? "https" : "https") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			$result = array();
			$opendata = array();
			$answer = array();
			$status = array();
			if($res["status"] == "success"){
				$status["code"] = 0;
				$status["message"] = "Success";
				$answer["status"] = $status;
				
				$data = array();
				//$data = array_column($res["records"], "fields");
				$data = array_map(function($d){ unset($d["fields"]["_full_text"]);unset($d["fields"]["_id"]);return $d["fields"];}, $res["records"]);
				$answer["data"] = $data;
			} else {
				$status["code"] = 7;
				$status["message"] = "Une erreur s'est produite lors de l’exécution de la requête.";
				$answer["status"] = $status;
			}
			
			$opendata["request"] = $url;
			$opendata["answer"] = $answer;
			$result["opendata"] = $opendata;
			
			echo json_encode($result);
		}

		
		return $response;
	}
	
	function updateResourceAndPushDatastore($resource){
		
		$callUrl =  $this->urlCkan . "api/action/datastore_search?resource_id=" . $resource["id"] . "&limit=0";
		
		//echo $callUrl;
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);//error_log($result);
		$fields = json_decode($result,true)["result"]["fields"];
		
		$resource["uuid"] = uniqid();
		$callUrl =  $this->urlCkan . "/api/action/resource_update";
		$return = $this->updateRequest($callUrl, $resource, "POST");
		//error_log($return);
		$fields2 = array();
		foreach($fields as $f){
			if($f["id"] != "_id"){
				$fields2[] = $f;
			}
		}
		for($i=0; $i<1; $i++){
			sleep(10);
			$callUrl =  $this->urlCkan . "/api/action/datastore_create";
			$data = array();
			$data["resource_id"] = $resource["id"];
			$data["force"] = true;
			$data["fields"] = $fields2;
			$data["uuid"] = uniqid();
			$res2 = $this->updateRequest($callUrl, $data, "POST");
			//error_log($res2);
		}
		
		
		return $return;
	}

	public function calculValueFromFiltre() {

		$req = array();

		$where ="";
		if($_POST['colonne_filtre'] && $_POST['valeur_filtre'] && $_POST['colonne_filtre']!= null && $_POST['valeur_filtre']!= null ) {
			$where = " where ";
			$where .= $_POST['colonne_filtre']." IN ( '".$_POST['valeur_filtre']. "' )";
		}
		
		$sql = "Select ".$_POST['operation']."(".$_POST['colonne'].") as result from \"" . $_POST['idRes'] . "\"" .$where ;
		
		$req['sql'] = $sql;

		$url2 = http_build_query($req);

		
		$callUrl =  $this->urlCkan . "api/action/datastore_search_sql?" . $url2;
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);

		curl_close($curl);
		$result = json_decode($result,true);

		
		$response = new Response(json_encode(array('result' =>$result["result"]["records"][0]["result"])));
		


		return $response;
	}

	public function addStory($params) {
		if(is_array($params)){
			$story = $params;
		} else {
			$story = $this->proper_parse_str($params);
		}

		$query = \Drupal::database()->insert('d4c_user_story');
		$scrolltime = (int)$story["scrolling_time"];

		$query->fields([
			'widget_label',
			'widget',
			'scroll_time',
			'image'
		]);
		$query->values([
			$story["label_widget"],
			$story["widget"],
			$scrolltime,
			$story["img_widget"]
			
		]);

		$query->execute();
	}

	public function getStories() {
		$res=array();
		$table = "d4c_user_story";
		$query2 = \Drupal::database()->select($table, 'story');


		$query2->fields('story', [
			'story_id',
			'widget_label',
			'widget',
			'scroll_time',
			'image'
		]);


		$prep=$query2->execute();
		$res= array();
		while ($enregistrement = $prep->fetch()) {
			array_push($res, $enregistrement);
		}

		return $res;
	}

	function updateStory($story){

		$story_id = $story["story_id"];
		$query = \Drupal::database()->update('d4c_user_story');
		$query->fields([
			'widget_label' => $story["label_widget"],
			'widget' => $story["widget"],
			'scroll_time' => (int)$story["scrolling_time"],
			'image' => $story["img_widget"]			
		]);

		$query->condition('story_id', $story_id);
		$query->execute();
		
	}

	function deleteStory($story_id){

		$query = \Drupal::database()->delete('d4c_user_story');


		$query->condition('story_id', $story_id);
		$query->execute();
	}
		
	/**
	 * This method update the current task for the dataset integration
	 * 
	 * (For now we use entity_id as ID, to see if we change this later)
	 * 
	 * EntityType can be
	 * > DATASET
	 * 
	 * TaskType can be
	 * > MANAGE_DATASET
	 * 
	 * Action can be one of the following 
	 * > UNKNOWN
	 * > CREATE_DATASET
	 * > UPDATE_DATASET
	 * > MANAGE_FILE
	 * > UPLOAD_CKAN
	 * > UPLOAD_DATASTORE
	 * > CREATE_CLUSTER
	 * 
	 * Status can be one of the following
	 * > SUCCESS
	 * > ERROR
	 * > PENDING
	 * 
	 * SQL Query to create the table
	 * 
	 * CREATE TABLE dpl_d4c_task_status (
	 * 	id text NOT NULL,
	 * 	entity_id text NOT NULL,
	 * 	entity_type text NOT NULL,
	 * 	task_type text NOT NULL,
	 * 	action text NOT NULL,
	 * 	status text,
	 * 	message text,
	 * 	last_updated timestamp without time zone NOT NULL DEFAULT (current_timestamp AT TIME ZONE 'UTC')
	 * );
	 * ALTER TABLE public.dpl_d4c_task_status OWNER TO user_d4c;
	 * 
	 * To update the table after running the update with the URL https://XXX.data4citizen.com/update.php
	 * 
	 * ALTER TABLE ONLY dpl_d4c_task_status_test ALTER COLUMN last_updated SET DEFAULT (current_timestamp AT TIME ZONE 'UTC');
	 * 
	 */
	function updateDatabaseStatus($isNew, $uniqId, $entityId, $entityType, $taskType, $action, $status, $message) {
		if ($entityId) {
			Logger::logMessage("Updating task status for resource '" . $entityId . "' \r\n");
		}
		else {
			Logger::logMessage("Updating task status with uniqId '" . $uniqId . "' \r\n");
		}
		$table = "d4c_task_status";

		if ($isNew) {
			$query = \Drupal::database()->insert($table);
			$query->fields([
				'id',
				'entity_id',
				'entity_type',
				'task_type',
				'action',
				'status',
				'message',
				'last_updated'
			]);
			$query->values([
				$uniqId,
				$entityId,
				$entityType,
				$taskType,
				$action,
				$status,
				$message,
				'now'
			]);
		}
		else {
			$query = \Drupal::database()->update($table);
			$query->fields([
				'entity_id' => $entityId,
				'action' => $action,
				'status' => $status,
				'message' => $message,
				'last_updated' => 'now'
			]);
			$query->condition(db_or()
					->condition('id', $uniqId)
					->condition('entity_id', $uniqId));
		}

		$query->execute();
	}

	/**
	 * This method retrieve the status for the last dataset integration define by the dataset ID
	 * 
	 */
	function getTaskStatus($id) {
		$table = "dpl_d4c_task_status";

		// $database = \Drupal\Core\Database\Database::getConnection('ckan', 'ckan');
		$sqlQuery = "SELECT action, status, message FROM " . $table . " WHERE (id = '" . $id ."' OR entity_id = '" . $id . "') and task_type = 'MANAGE_DATASET'";
		
		$query = \Drupal::database()->query($sqlQuery);
		$task = $query->fetchAssoc();
		
		if ($task) {
			$action = $task["action"];
			$status = $task["status"];
			$message = $task["message"];

			$status = ["id" => $id,
				"action" => $action,
				"status" => $status,
				"message" => $message
			];
		}
		else {
			$status = ["id" => $id,
				"action" => 'UNKNOWN',
				"status" => 'ERROR',
				"message" => ''
			];
		}

		$result = json_encode($status);
		Logger::logMessage("Task status for entity '" . $id . "' " . $result);

		$response = new Response();
		$response->setContent($result);
		$response->headers->set('Content-Type', 'application/json');
        
		return $response;  
	}



	public function callPackageReutilisation($params) {
		$reuses = $this->getReuses(null, null, null, "online", 1000, 0);

		$response = new Response();
		$response->setContent(json_encode($reuses));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}
    
}
