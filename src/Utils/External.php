<?php

namespace Drupal\ckan_admin\Utils;

use Drupal\ckan_admin\Utils\Api;
use Symfony\Component\HttpFoundation\Response;
//ini_set('memory_limit', '2048M'); // or you could use 1G

class External {
	
	protected $api;
	
	public function __construct(){
        $this->api = new Api();
		$this->config = include(__DIR__ . "/../../config.php");
    }
	
	private function getSimpleGetOptions(){
		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST =>  0
		);
		return $options;
	}
	
	function callFacetsFromSource($params){
		$query_params = $this->api->proper_parse_str($params);
		$type = $query_params["type"];
		$url = $query_params["url"];
		$id = $query_params["id"];
		unset($query_params["type"]);
		unset($query_params["url"]);
		unset($query_params["id"]);
		unset($query_params["facet"]);
		unset($query_params["sort"]);
		
		$request = http_build_query($query_params);
		//on récupère les données sous forme de csv
		$result = $this->getFacetsFromSource($type, $url, $id, $request);
		
		$response = new Response();
		$response->setContent($result);
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	function getFacetsFromSource($typeSource, $url, $idDataset, $request){
		$result = "";
		if($typeSource == "ods"){
			$result = $this->getFacetsFromOds($url, $idDataset, $request);
		} else if($typeSource == "d4c"){
			$result = $this->getFacetsFromD4c($url, $idDataset, $request);
		} else if($typeSource == "ckan"){
			$result = $this->getFacetsFromCkan($url, $idDataset, $request);
		}
		return $result;
	}
	
	function getFacetsFromOds($url, $idDataset, $request){

		//on récupère la liste des champs
		$requestUrl = $url . "/api/records/1.0/search/?rows=1&dataset=".$idDataset;
		
		$curl = curl_init($requestUrl);
		$opt = $this->getSimpleGetOptions();
		curl_setopt_array($curl, $opt);    
		$res = curl_exec($curl);
		curl_close($curl);
		//echo $res;
		$res = json_decode($res, true);
		if(array_key_exists("error", $res)){
			return null;
		} 
		
		$fields = array();
		foreach($res["records"][0]["fields"] as $k => $value){
			if(!is_array($value)){
				$fields[] = "facet=" . $k;
			}
		}
		
		$facetReq = implode("&", $fields);
		if($request != "") $request = "&".$request;
		$requestUrl = $url . "/api/records/1.0/search/?".$facetReq."&dataset=".$idDataset.$request;
		//echo $requestUrl;
		$curl = curl_init($requestUrl);
		$opt = $this->getSimpleGetOptions();
		curl_setopt_array($curl, $opt);    
		$res2 = curl_exec($curl);
		//echo $res2;
		curl_close($curl);
		$res2 = json_decode($res2, true);
		
		if(array_key_exists("error", $res2)){
			return null;
		} 
		
		return json_encode($res2);
	}
	
	function getFacetsFromD4c($url, $idDataset, $request){
		if($request != "") $request = "&".$request;
		if(substr($url, -1) === "/"){
			$url = substr($url, 0, -1);
		}
		$requestUrl = $url . $this->config->client->routing_prefix . "/d4c/api/records/1.0/download/dataset=".$idDataset. $request;
		//echo $requestUrl;
		$curl = curl_init($requestUrl);
		$opt = $this->getSimpleGetOptions();
		curl_setopt_array($curl, $opt);    
		$res = curl_exec($curl);
		curl_close($curl);
		//echo $res;
		$res = json_decode($res, true);
		if(array_key_exists("error", $res)){
			return null;
		} 
		//if(strpos($request, "geo_digest") !== false || strpos($request, "geo_simplify") !== false || strpos($request, "start") !== false){
		//	unset($res["facet_groups"]);
		//	return json_encode($res);
		//} else {
			return json_encode($res);
		//}
	}
	
	function getFacetsFromCkan($url, $idDataset, $request){
		//TODO
	}
	
	function callRecordsFromSource($params){
		$query_params = $this->api->proper_parse_str($params);
		$type = $query_params["type"];
		$url = $query_params["url"];
		$id = $query_params["id"];
		unset($query_params["type"]);
		unset($query_params["url"]);
		unset($query_params["id"]);
		
		$request = http_build_query($query_params);
		//on récupère les données sous forme de csv
		$result = $this->getRecordsFromSource($type, $url, $id, $request);
		
		$response = new Response();
		$response->setContent($result);
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	function getRecordsFromSource($typeSource, $url, $idDataset, $request){
		$result = "";
		if($typeSource == "ods"){
			$result = $this->getRecordsFromOds($url, $idDataset, $request);
		} else if($typeSource == "d4c"){
			$result = $this->getRecordsFromD4c($url, $idDataset, $request);
		} else if($typeSource == "ckan"){
			$result = $this->getRecordsFromCkan($url, $idDataset, $request);
		}
		
		return $result;
	}
	
	function getRecordsFromOds($url, $idDataset, $request){
		if($request != "") $request = "&".$request;
		$requestUrl = $url . "/api/records/1.0/search/?dataset=".$idDataset. $request;
		//echo $requestUrl;
		$curl = curl_init($requestUrl);
		$opt = $this->getSimpleGetOptions();
		curl_setopt_array($curl, $opt);    
		$res = curl_exec($curl);
		curl_close($curl);
		//echo $res;
		$res = json_decode($res, true);
		if(array_key_exists("error", $res)){
			return null;
		} 
		if(strpos($request, "geo_digest") !== false || strpos($request, "geo_simplify") !== false || strpos($request, "start") !== false){
			unset($res["facet_groups"]);
			return json_encode($res);
		} else {
			return json_encode($res);
		}
		
	}
	
	function getRecordsFromD4c($url, $idDataset, $request){
		if($request != "") $request = "&".$request;
		if(substr($url, -1) === "/"){
			$url = substr($url, 0, -1);
		}
		if(strpos($request, "geo_digest") !== false || strpos($request, "geo_simplify") !== false || (strpos($request, "geofilter.distance") !== false && strpos($request, "start") === false)){
			$requestUrl = $url . $this->config->client->routing_prefix . "/d4c/api/records/2.0/download/id=".$idDataset. $request;
		} else {
			$requestUrl = $url . $this->config->client->routing_prefix . "/d4c/api/records/1.0/download/id=".$idDataset. $request;
		}
		//echo $requestUrl;
		$curl = curl_init($requestUrl);
		$opt = $this->getSimpleGetOptions();
		curl_setopt_array($curl, $opt);    
		$res = curl_exec($curl);
		curl_close($curl);
		//echo $res;
		$res = json_decode($res, true);
		if(array_key_exists("error", $res)){
			return null;
		} 
		//if(strpos($request, "geo_digest") !== false || strpos($request, "geo_simplify") !== false || strpos($request, "start") !== false){
		//	unset($res["facet_groups"]);
		//	return json_encode($res);
		//} else {
			return json_encode($res);
		//}
	}
	
	function getRecordsFromCkan($url, $idDataset, $request){
		
	}
	
	function callDatasetFromSource($params){
		$query_params = $this->api->proper_parse_str($params);
		$query_params["url"] = preg_replace('/_slash_/i',"/",$query_params["url"]);
		//on récupère les données sous forme de csv
		$result = $this->getDatasetFromSource($query_params["type"], $query_params["url"], $query_params["id"]);
		
		$response = new Response();
		$response->setContent($result);
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	function getDatasetFromSource($typeSource, $url, $idDataset){
		$result = "";
		if($typeSource == "ods"){
			$result = $this->getDatasetFromOds($url, $idDataset);
		} else if($typeSource == "d4c"){
			$result = $this->getDatasetFromD4c($url, $idDataset);
		} else if($typeSource == "ckan"){
			$result = $this->getDatasetFromCkan($url, $idDataset);
		}
		
		return $result;
	}
	
	function getDatasetFromOds($url, $idDataset){
		
		$requestUrl = $url . "/api/datasets/1.0/" . $idDataset . "/?extrametas=true&interopmetas=true";
		
		$curl = curl_init($requestUrl);
		$opt = $this->getSimpleGetOptions();
		curl_setopt_array($curl, $opt);    
		$res = curl_exec($curl);
		curl_close($curl);
		//echo $res;
		$res = json_decode($res, true);
		if(array_key_exists("error", $res)){
			return null;
		} 
		
		return json_encode($res);
	}
	
	function getDatasetFromD4c($url, $idDataset, $request){
		$requestUrl = $url . $this->config->client->routing_prefix . "/d4c/api/datasets/1.0/".$idDataset."/";
		
		$curl = curl_init($requestUrl);
		$opt = $this->getSimpleGetOptions();
		curl_setopt_array($curl, $opt);    
		$res = curl_exec($curl);
		curl_close($curl);
		//echo $res;
		$res = json_decode($res, true);
		if(array_key_exists("error", $res)){
			return null;
		} 
		
		return json_encode($res);
	}
	
	function getDatasetFromCkan($url, $idDataset, $request){
		
	}
	
	function callBoundingBoxFromSource($params){
		$query_params = $this->api->proper_parse_str($params);
		$type = $query_params["type"];
		$url = $query_params["url"];
		$id = $query_params["id"];
		unset($query_params["type"]);
		unset($query_params["url"]);
		unset($query_params["id"]);
		
		$request = http_build_query($query_params);
		//on récupère les données sous forme de csv
		$result = $this->getBoundingBoxFromSource($type, $url, $id, $request);
		
		$response = new Response();
		$response->setContent($result);
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	function getBoundingBoxFromSource($typeSource, $url, $idDataset, $request){
		$result = "";
		if($typeSource == "ods"){
			$result = $this->getBoundingBoxFromOds($url, $idDataset, $request);
		} else if($typeSource == "d4c"){
			$result = $this->getBoundingBoxFromD4c($url, $idDataset, $request);
		} else if($typeSource == "ckan"){
			$result = $this->getBoundingBoxFromCkan($url, $idDataset, $request);
		}

		
		return $result;
	}
	
	function getBoundingBoxFromOds($url, $idDataset, $request){
		if($request != "") $request = "&".$request;
		$requestUrl = $url . "/api/records/1.0/boundingbox/?dataset=".$idDataset. $request;
		//echo $requestUrl;
		$curl = curl_init($requestUrl);
		$opt = $this->getSimpleGetOptions();
		curl_setopt_array($curl, $opt);    
		$res = curl_exec($curl);
		curl_close($curl);
		//echo $res;
		$res = json_decode($res, true);
		if(array_key_exists("error", $res)){
			return null;
		} 
		
		return json_encode($res);
	}
	
	function getBoundingBoxFromD4c($url, $idDataset, $request){
		if($request != "") $request = "&".$request;
		if(substr($url, -1) === "/"){
			$url = substr($url, 0, -1);
		}
		$requestUrl = $url . $this->config->client->routing_prefix . "/d4c/api/records/1.0/boundingbox/id=".$idDataset. $request;
		//echo $requestUrl;
		$curl = curl_init($requestUrl);
		$opt = $this->getSimpleGetOptions();
		curl_setopt_array($curl, $opt);    
		$res = curl_exec($curl);
		curl_close($curl);
		//echo $res;
		$res = json_decode($res, true);
		if(array_key_exists("error", $res)){
			return null;
		} 
		
		return json_encode($res);
	
	}
	
	function getBoundingBoxFromCkan($url, $idDataset, $request){
		
	}
	
	function callGeoPreviewFromSource($params){
		$query_params = $this->api->proper_parse_str($params);
		$type = $query_params["type"];
		$url = $query_params["url"];
		$id = $query_params["id"];
		unset($query_params["type"]);
		unset($query_params["url"]);
		unset($query_params["id"]);
		
		$request = http_build_query($query_params);
		//on récupère les données sous forme de csv
		$result = $this->getGeoPreviewFromSource($type, $url, $id, $request);
		
		$response = new Response();
		$response->setContent($result);
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	function getGeoPreviewFromSource($typeSource, $url, $idDataset, $request){
		$result = "";
		if($typeSource == "ods"){
			$result = $this->getGeoPreviewFromOds($url, $idDataset, $request);
		} else if($typeSource == "d4c"){
			$result = $this->getGeoPreviewFromD4c($url, $idDataset, $request);
		} else if($typeSource == "ckan"){
			$result = $this->getGeoPreviewFromCkan($url, $idDataset, $request);
		}

		
		return $result;
	}
	
	function getGeoPreviewFromOds($url, $idDataset, $request){
		if($request != "") $request = "&".$request;
		$requestUrl = $url . "/api/records/1.0/geopreview/?dataset=".$idDataset. $request;
		//echo $requestUrl;
		$curl = curl_init($requestUrl);
		$opt = $this->getSimpleGetOptions();
		curl_setopt_array($curl, $opt);    
		$res = curl_exec($curl);
		curl_close($curl);
		//echo $res;
		$res = json_decode($res, true);
		if(array_key_exists("error", $res)){
			return null;
		} 
		
		return json_encode($res);
	}
	
	function getGeoPreviewFromD4c($url, $idDataset, $request){
		if($request != "") $request = "&".$request;
		if(substr($url, -1) === "/"){
			$url = substr($url, 0, -1);
		}
		$requestUrl = $url . $this->config->client->routing_prefix . "/d4c/api/records/1.0/geopreview/id=".$idDataset. $request;
		//echo $requestUrl;
		$curl = curl_init($requestUrl);
		$opt = $this->getSimpleGetOptions();
		curl_setopt_array($curl, $opt);    
		$res = curl_exec($curl);
		curl_close($curl);
		//echo $res;
		$res = json_decode($res, true);
		if(array_key_exists("error", $res)){
			return null;
		} 
		
		return json_encode($res);
	}
	
	function getGeoPreviewFromCkan($url, $idDataset, $request){
		
	}
	
	function callGeoClusterFromSource($params){
		$query_params = $this->api->proper_parse_str($params);
		$type = $query_params["type"];
		$url = $query_params["url"];
		$id = $query_params["id"];
		unset($query_params["type"]);
		unset($query_params["url"]);
		unset($query_params["id"]);
		if($type == "ods"){
			$query_params["clusterprecision"] = $query_params["clusterprecision"] -1;
		}
		
		$request = http_build_query($query_params);
		//on récupère les données sous forme de csv
		$result = $this->getGeoClusterFromSource($type, $url, $id, $request);
		
		$response = new Response();
		$response->setContent($result);
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	function getGeoClusterFromSource($typeSource, $url, $idDataset, $request){
		$result = "";
		if($typeSource == "ods"){
			$result = $this->getGeoClusterFromOds($url, $idDataset, $request);
		} else if($typeSource == "d4c"){
			$result = $this->getGeoClusterFromD4c($url, $idDataset, $request);
		} else if($typeSource == "ckan"){
			$result = $this->getGeoClusterFromCkan($url, $idDataset, $request);
		}

		
		return $result;
	}
	
	function getGeoClusterFromOds($url, $idDataset, $request){
		if($request != "") $request = "&".$request;
		$requestUrl = $url . "/api/records/1.0/geocluster/?dataset=".$idDataset. $request;
		//echo $requestUrl;
		$curl = curl_init($requestUrl);
		$opt = $this->getSimpleGetOptions();
		curl_setopt_array($curl, $opt);    
		$res = curl_exec($curl);
		curl_close($curl);
		//echo $res;
		$res = json_decode($res, true);
		if(array_key_exists("error", $res)){
			return null;
		} 
		
		return json_encode($res);
	}
	
	function getGeoClusterFromD4c($url, $idDataset, $request){
		if($request != "") $request = "&".$request;
		if(substr($url, -1) === "/"){
			$url = substr($url, 0, -1);
		}
		$requestUrl = $url . $this->config->client->routing_prefix . "/d4c/api/records/1.0/geocluster/dataset=".$idDataset. $request;
		//echo $requestUrl;
		$curl = curl_init($requestUrl);
		$opt = $this->getSimpleGetOptions();
		curl_setopt_array($curl, $opt);    
		$res = curl_exec($curl);
		curl_close($curl);
		//echo $res;
		$res = json_decode($res, true);
		if(array_key_exists("error", $res)){
			return null;
		} 
		
		return json_encode($res);
	}
	
	function getGeoClusterFromCkan($url, $idDataset, $request){
		
	}
	
	function callDownloadFromSource($params){
		$query_params = $this->api->proper_parse_str($params);
		$type = $query_params["type"];
		$url = $query_params["url"];
		$id = $query_params["id"];
		unset($query_params["type"]);
		unset($query_params["url"]);
		unset($query_params["id"]);
		
		$request = http_build_query($query_params);
		//on récupère les données sous forme de csv
		$result = $this->getDownloadFromSource($type, $url, $id, $request);
		
		$response = new Response();
		$response->setContent($result);
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	function getDownloadFromSource($typeSource, $url, $idDataset, $request){
		$result = "";
		if($typeSource == "ods"){
			$result = $this->getDownloadFromOds($url, $idDataset, $request);
		} else if($typeSource == "d4c"){
			$result = $this->getDownloadFromD4c($url, $idDataset, $request);
		} else if($typeSource == "ckan"){
			$result = $this->getDownloadFromCkan($url, $idDataset, $request);
		}
		
		//write csv file
		$fileName = 'req_'.$typeSource."_".$idDataset;
		$filePath = $fileName. "_" . uniqid().'.csv';

		$rootCsv = $this->config->client->drupal_root . '/sites/default/files/dataset/' . $filePath;
		//error_log($rootCsv);
		file_put_contents($rootCsv, $result);
		
		$protocol = /*isset($_SERVER['HTTPS']) ? */'https://' /*: 'http://'*/;
		$url = $protocol.$_SERVER['HTTP_HOST'] . $this->config->client->routing_prefix . '/sites/default/files/dataset/' . $filePath;
		//error_log($url);
		$res = array();
		$res["url"] = $url;
		$res["name"] = $fileName;
		
		return $res;
	}
	
	function getDownloadFromOds($url, $idDataset, $request){
		if($request != "") $request = "&".$request;
		$requestUrl = $url . "/api/records/1.0/download/?dataset=".$idDataset. $request;
		//error_log( $requestUrl);
		$curl = curl_init($requestUrl);
		$opt = $this->getSimpleGetOptions();
		curl_setopt_array($curl, $opt);    
		$res = curl_exec($curl);
		curl_close($curl);
		//error_log( $res);
		/*$res = json_decode($res, true);
		if(array_key_exists("error", $res)){
			return null;
		} */
		
		return $res;
		
	}
	
	function getDownloadFromD4c($url, $idDataset, $request){
		if($request != "") $request = "&".$request;
		if(substr($url, -1) === "/"){
			$url = substr($url, 0, -1);
		}
		$requestUrl = $url . $this->config->client->routing_prefix . "/d4c/api/records/2.0/download/format=csv&use_labels_for_header=true&dataset=".$idDataset. $request;
		//error_log( $requestUrl);
		$curl = curl_init($requestUrl);
		$opt = $this->getSimpleGetOptions();
		curl_setopt_array($curl, $opt);    
		$res = curl_exec($curl);
		curl_close($curl);
		$arr = explode(PHP_EOL, $res);
		foreach($arr as &$row){
			$cols = explode(";", $row);
			unset($cols[0]);
			$row = implode(";", $cols);
		}
		$res = implode(PHP_EOL, $arr);
		//error_log( $res);
		/*$res = json_decode($res, true);
		if(array_key_exists("error", $res)){
			return null;
		} */
		
		return $res;
	}
	
	function getDownloadFromCkan($url, $idDataset, $request){
		
	}
	
}