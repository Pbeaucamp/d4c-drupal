<?php

namespace Drupal\ckan_admin\Utils;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\Common\Creator\ReaderFactory;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;

use Drupal\ckan_admin\Utils\Export;
use Drupal\ckan_admin\Utils\Logger;
use Drupal\ckan_admin\Utils\ResourceManager;
use Drupal\ckan_admin\Utils\NutchApi;

use finfo;
use SplFileObject;
use ZipArchive;
use SimpleXMLElement;


ini_set('memory_limit', '4G'); // or you could use 1G
ini_set('max_execution_time', 2000);

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

class Api
{
	const ROOT = '/home/user-client/drupal-d4c';

	protected $urlCkan;
	protected $urlDatapusher;
	protected $config;
	protected $isSpecial;
	protected $isPostgis;
	protected $themes;

	protected $d4cUrl;
	protected $urlDataFolder;
	protected $dataFolder;

	public function __construct()
	{
		$this->config = include(__DIR__ . "/../../config.php");

		$this->urlCkan = $this->config->ckan->url;
		$this->urlDatapusher = $this->config->ckan->datapusher_url;
		$this->isSpecial = $this->config->client->name == 'cda2';
		$this->isPostgis = $this->config->client->name == 'cda2';

		$this->d4cUrl = (isset($this->config->client->protocol) ? $this->config->client->protocol : "https") . '://' . $this->config->client->domain;
		$this->urlDataFolder = $this->config->client->routing_prefix . '/sites/default/files/dataset/';
		$this->dataFolder = $this->config->client->drupal_root . $this->urlDataFolder;

		// Testing if map_tiles file exist and copy from model if not
		$mapTilesExist = file_exists(__DIR__ . "/../../map_tiles.json");
		if (!$mapTilesExist) {
			copy(__DIR__ . "/../../map_tiles_model.json", __DIR__ . "/../../map_tiles.json");
		}
	}

	public function getStoreOptions($applySecurity = true)
	{
		$headr = array();
		$headr[] = 'Content-length: 0';
		$headr[] = 'Content-type: application/json';
		//$headr[] = 'Authorization: 995efb3c-9349-43d7-965c-d7ce567b323a';
		if ($applySecurity) {
			$headr[] = 'Authorization: ' . $this->config->ckan->api_key;
		}
		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER     => $headr,
			CURLOPT_POST => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST =>  0,
			CURLOPT_ENCODING => 'UTF-8'
		);
		return $options;
	}

	public function getSimpleOptions()
	{
		$options = array(
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST =>  0,
			CURLOPT_POSTFIELDS => array()
		);
		return $options;
	}

	public function getSimpleGetOptions()
	{
		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST =>  0
		);
		return $options;
	}

	function getConfig()
	{
		return $this->config;
	}

	/**
	 * 
	 * This method is specifically made for CR Reunion
	 * The server does not react the same as the proxmox instance
	 * 
	 * We need to call that for the majority of the methods
	 * 
	 */
	function retrieveParameters($params)
	{
		if ($this->isSpecial) {
			if ($params == '') {
				$params = $_SERVER['QUERY_STRING'];

				//We decode parameters (replace %3D by = and + by a space)
				$params = str_replace('%3D', '=', $params);
				$params = str_replace('%C3%A2', 'â', $params);
				$params = str_replace('+', ' ', $params);
				$params = str_replace('%22', '"', $params);
			} else {
				$params;
			}
		}

		return $params;
	}

	function proper_parse_str($str)
	{
		if ($str == '') {
			$str = $_SERVER['QUERY_STRING'];
		} else {
			if (substr($str, 0, 1) == '?') {
				$str = substr($str, 1);
			}
		}
		$str = preg_replace('/_slash_/i', "/", $str);
		# result array
		$arr = array();

		# split on outer delimiter
		$pairs = explode('&', $str);

		# loop through each pair
		foreach ($pairs as $i) {
			#Adding a trick for subdirectory - if q=d4c/ is present we remove the parameter
			$query = 'q=d4c/';
			if (substr($i, 0, strlen($query)) === $query) {
				continue;
			}

			# split into name and value
			list($name, $value) = explode('=', $i, 2);

			# if name already exists
			if (isset($arr[$name])) {
				# stick multiple values into an array
				if (is_array($arr[$name])) {
					$arr[$name][] = $value;
				} else {
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


	public function callDatastoreApi($params)
	{
		$result = $this->getDatastoreApi($params);

		echo json_encode($result);
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	public function getDatastoreApi($params)
	{

		$patternRefine = '/refine./i';
		$patternDisj = '/disjunctive./i';
		$filters_init = array();
		//echo $params . "\r\n";
		$query_params = $this->proper_parse_str($params);
		if (array_key_exists('rows', $query_params)) {
			$query_params['limit'] = $query_params['rows'];
			unset($query_params['rows']);
		}
		if (array_key_exists('q', $query_params)) {
			if (strpos($query_params['q'], '{') == false) {
				if (strpos($query_params['q'], ':') != false && substr($query_params['q'], 0, 1) != '"') {
					$ex = explode(':', $query_params['q']);
					$query_params['q'] = '"' . $ex[0] . '":' .  $ex[1];
				}
				$query_params['q'] = '{' . $query_params['q'] . '}';
				//echo $query_params['q'];
			}
		}
		foreach ($query_params as $key => $value) {
			if (preg_match($patternRefine, $key)) {
				$filters_init[preg_replace($patternRefine, "", $key)] =  $value;

				unset($query_params[$key]);
				//echo preg_replace($pattern,"",$key);
			}
			if (preg_match($patternDisj, $key)) {
				unset($query_params[$key]);
				//$disj[] = preg_replace($patternDisj,"",$key);
			}
			if ($key == "id" || $key == "calendarview") {
				unset($query_params[$key]);
			}
		}
		if (!empty($filters_init)) {
			$query_params['filters'] = json_encode($filters_init);
		}


		$url2 = http_build_query($query_params);
		$callUrl =  $this->urlCkan . "api/action/datastore_search?" . $url2;

		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		//echo $result . "\r\n";
		curl_close($curl);

		$result = json_decode($result, true);
		foreach ($result['result']['fields'] as $value) {
			$description = $value['info']['notes'];
			if (preg_match("/<!--.*description.*-->/i", $description)) {
				preg_match_all('/(?<=<!--description\?)([^>]*)-->/', $description, $matches);

				if ($matches) {
					$description = $matches[1][0];
					$description = preg_replace('/_/i', ' ', $description);
				}
			}
		}
		unset($result["help"]);
		unset($result["result"]["_links"]);


		return $result;
	}

	private function constructReqQToSQL($value, $append = "")
	{
		$value = urldecode($value);
		//"q=emr_dt_service:[2018-04-21T22:00:00Z TO 2018-07-20T22:00:00Z]"
		//"q=emr_dt_service>=\"2018-04-02T22:00:00Z\""
		//q=nom_com:"lyon"
		//q=lyon
		//TODO améliorer boucle avec parenthèses
		$res = "";
		if (count(explode(" AND ", $value)) > 1) {
			//$res = " and (";
			foreach (explode(" AND ", $value) as $item) {
				$res .= $this->constructReqQToSQL($item, " and ");
			}
			$res = substr($res, 5);
		} else if (count(explode(" OR ", $value)) > 1) {
			//$res = " and (";
			foreach (explode(" OR ", $value) as $item) {
				$res .= $this->constructReqQToSQL($item, " or ");
			}
			$res = substr($res, 4);
		} else {
			if (count(explode(" TO ", $value)) > 1) {
				$field = explode(":", $value)[0];
				$datas = substr(explode(":", $value, 2)[1], 1, -1);
				$d1 = explode(" TO ", $datas)[0];
				$d2 = explode(" TO ", $datas)[1];
				if (is_numeric($d1)) {
					$res .=  $field . " >= " . $d1 . " and " . $field . " <= " . $d2;
				} else {
					$res .=  $field . " >= '" . $d1 . "' and " . $field . " <= '" . $d2 . "'";
				}
			} else if (count(explode(":", $value)) == 2) {
				$field = explode(":", $value)[0];
				$data = explode(":", $value)[1];
				if (strpos($field, "_id") !== false || is_numeric($data)) {
					$data = urldecode($data);
					if (strpos($data, "%27") !== false) {
						$data = str_replace("%27", "", $data);
					}
					$res .=  $field . " = " . $data;
				} else {
					$data = urldecode($data);
					if (substr($data, 0, 1) == '"') {
						$data = substr($data, 1, -1);
					}
					if (strpos($data, "%27") !== false) {
						$data = str_replace("%27", "", $data);
					}
					$res .= "CAST(" . $field . " AS TEXT)" . " ilike '%" . $data . "%'";
				}
			} else if (count(explode(">=", $value)) > 1 || count(explode("<=", $value)) > 1 || count(explode("=", $value)) > 1 || count(explode(">", $value)) > 1 || count(explode("<", $value)) > 1) {

				if (count(explode(">=", $value)) > 1) {
					$field = explode(">=", $value)[0];
					$data = explode(">=", $value)[1];
					if (is_numeric($data)) {
						$res .=  $field . " >= " . $data;
					} else {
						if (substr($data, 0, 1) == '"') $data = substr($data, 1, -1);
						$res .=  $field . " >= '" . $data . "'";
					}
				} else if (count(explode("<=", $value)) > 1) {
					$field = explode("<=", $value)[0];
					$data = explode("<=", $value)[1];
					if (is_numeric($data)) {
						$res .=  $field . " <= " . $data;
					} else {
						if (substr($data, 0, 1) == '"') $data = substr($data, 1, -1);
						$res .=  $field . " <= '" . $data . "'";
					}
				} else if (count(explode(">", $value)) > 1) {
					$field = explode(">", $value)[0];
					$data = explode(">", $value)[1];
					if (is_numeric($data)) {
						$res .=  $field . " > " . $data;
					} else {
						if (substr($data, 0, 1) == '"') $data = substr($data, 1, -1);
						$res .=  $field . " > '" . $data . "'";
					}
				} else if (count(explode("<", $value)) > 1) {
					$field = explode("<", $value)[0];
					$data = explode("<", $value)[1];
					if (is_numeric($data)) {
						$res .=  $field . " < " . $data;
					} else {
						if (substr($data, 0, 1) == '"') $data = substr($data, 1, -1);
						$res .=  $field . " < '" . $data . "'";
					}
				} else {
					$field = explode("=", $value)[0];
					$data = explode("=", $value)[1];
					if (is_numeric($data)) {
						$res .=  $field . " = " . $data;
					} else {
						if (substr($data, 0, 1) == '"') $data = substr($data, 1, -1);
						$res .=  $field . " = '" . $data . "'";
					}
				}
			} else if (count(explode("NOT #null(", $value)) > 1) {
				$field = substr(explode("NOT #null(", $value)[1], 0, -1);
				$res .=  $field . " not in ('', ',')";
			} else {
				$res .=  "_full_text @@ to_tsquery('" . $value . "')";
			}
		}

		if ($append == "") {
			$res = " and (" . $res . ")";
		} else {
			$res = $append . $res;
		}
		return $res;
	}


	public function callDatastoreApiFacet($params) {
		$result = $this->datastoreApiFacet($params);

		echo json_encode($result);
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	function datastoreApiFacet($params) {
		
		$params = $this->retrieveParameters($params);
		$query_params = $this->proper_parse_str($params);
		if (array_key_exists('fields', $query_params) || array_key_exists('facet', $query_params)) {
			$nhits = 0;
			$nhitsTotal = 0;
			$facet_groups = array();

			$fields = $this->getAllFields($query_params['resource_id']);
			$fieldCoordinates = "";
			foreach ($fields as $value) {
				if ($value['type'] == "geo_point_2d") {
					// If the field is created from lat/lon, we need to use the field sql definition
					if (isset($value['sql'])) {
						$fieldCoordinates = $value['sql'];
					} else {
						$fieldCoordinates = $value['name'];
					}
				}
			}

			$filters_init = array();
			$disj = array();
			$patternRefine = '/refine./i';
			$patternDisj = '/disjunctive./i';
			$patternSort = '/facetsort./i';
			$reqQfilter = "";
			$qField = "";
			$filterKey = "";
			$filterSort = "";

			foreach ($query_params as $key => $value) {
				if (preg_match($patternRefine, $key)) {
					$filters_init[preg_replace($patternRefine, "", $key)] =  $value;

					unset($query_params[$key]);
					//echo preg_replace($pattern,"",$key);
				}
				if (preg_match($patternDisj, $key)) {
					unset($query_params[$key]);
					$disj[] = preg_replace($patternDisj, "", $key);
				}
				if (preg_match($patternSort, $key)) {
					unset($query_params[$key]);
					$filterKey =  preg_replace($patternSort, "", $key);
					$filterSort =  $value;
				}
				if ($key == "q") {
					$reqQfilter = $this->constructReqQToSQL($value);
					$pattern = '/and (\w+) /i';
					preg_match($pattern, $reqQfilter, $qField);
				}
				if ($key == "id" || $key == "basemap" || $key == "location" || $key == "datasetcard" || $key == "static" || $key == "scrollWheelZoom") {
					unset($query_params[$key]);
				}
				if ($key == "geofilter.distance") {
					unset($query_params[$key]);
					$filters_init[$key] =  $value;
				}
				if ($key == "geofilter.polygon") {
					unset($query_params[$key]);
					$filters_init[$key] =  $value;
				}
			}

			if (array_key_exists('fields', $query_params)) {
				$facets = preg_split('/,/', $query_params['fields']);
			} else if (array_key_exists('facet', $query_params)) {
				if (is_array($query_params['facet'])) {
					$facets = $query_params['facet'];
				} else {
					$facets = array();
					$facets[] = $query_params['facet'];
				}
			}

			$nhits = 0;
			if (!array_key_exists('rows', $query_params) || $query_params["rows"] == 0) {

				for ($i = 0; $i < count($facets); ++$i) {
					$group = array();
					$query_params['fields'] = $facets[$i];
					$query_params['distinct'] = "true";
					if (count($filters_init) > 0) {
						$filters = array_merge(array(), $filters_init);
						if (in_array($facets[$i], $disj)) {
							unset($filters[$facets[$i]]);
						}
						$query_params['filters'] = json_encode($filters);
					}

					unset($query_params['limit']);


					$where = "";
					if (!empty($filters)) {
						$where = " where ";
						foreach ($filters as $key => $value) {
							if ($key == "geofilter.distance") {
								$coord = explode(',', $value);
								$lat = $coord[0];
								$long = $coord[1];
								if (count($coord) > 2) {
									$dist = $coord[2];
									//$bbox = $this->getBbox($lat,$long,$dist);
									//$bbox = explode(',', $bbox);
									//$minlat = $bbox[0];
									//$minlong = $bbox[1];
									//$maxlat = $bbox[2];
									//$maxlong = $bbox[3];
									//$where .= "box(point(" . $minlat . "," . $minlong . "),point(" . $maxlat . "," . $maxlong . ")) @> point(".$fieldCoordinates.") and ";
									//$where .= "circle(point(" . $lat . "," . $long . "), " . $dist/100000 . ") @> point(".$fieldCoordinates.") and ";
									$where .= "circle(point(" . $lat . "," . $long . "), " . $this->getRadius($lat, $long, $dist) . ") @> point(" . $fieldCoordinates . ") and ";
									//$where .= "circle(polygon(path '(" . $this->getLosangePath($lat,$long,$dist) . ")')) @> point(".$fieldCoordinates.") and ";
								} else {
									//$where .= "CAST(split_part(".$fieldCoordinates.",',',1)AS FLOAT) = " . $lat . " and ";
									//$where .= "CAST(split_part(".$fieldCoordinates.",',',2)AS FLOAT) = " . $long . " and ";
									$where .= "point(" . $lat . "," . $long . ") ~= point(" . $fieldCoordinates . ") and ";
								}

								$where .= $fieldCoordinates . " not in ('', ',') and ";
							} else if ($key == "geofilter.polygon") {
								//polygon(path '((0,0),(1,1),(2,0))')
								$where .= "polygon(path '(" . $value . ")') @> point(" . $fieldCoordinates . ") and ";
								$where .= $fieldCoordinates . " not in ('', ',') and ";
							} else {
								if (is_numeric($value) && $key != "insee_com" && $key != "code_insee" && $key != "sta_nm_dpt") {
									$where .= $key . "=" . $value . " and ";
								} else if (is_array($value)) {
									if ($key != "insee_com" && $key != "code_insee") {
										$value = implode(',', array_map(array($this, 'quotesArrayValue'), str_replace("'", "''", $value)));
										$value = urldecode($value);
										$where .= $key . " in (" . $value . ") and ";
									}
									else {
										$where .= $key . " in (" . implode(',', array_map(array($this, 'quotesStringArrayValue'), $value)) . ") and ";
									}
								} else {
									$value = urldecode($value);
									$where .= $key . "='" . str_replace("'", "''", $value) . "' and ";
								}
							}
						}
						$where = substr($where, 0, strlen($where) - 4);
						if ($reqQfilter != NULL) {
							$where .= $reqQfilter;
						}
					} else if ($reqQfilter != NULL) {
						$where = " where " . substr($reqQfilter, 5);
					}

					$req = array();
					$sql = "Select \"" . $query_params['fields'] . "\", count(\"" . $query_params['fields'] . "\") as total from \"" . $query_params['resource_id'] . "\"" . $where . "group by \"" . $query_params['fields'] . "\"";

					$req['sql'] = $sql;

					// Logger::logMessage("TRM - datastoreApiFacet - SQL : " . $sql);

					//echo $sql;
					$url2 = http_build_query($req);
					$callUrl =  $this->urlCkan . "api/action/datastore_search_sql?" . $url2;

					$curl = curl_init($callUrl);
					curl_setopt_array($curl, $this->getStoreOptions());
					$result = curl_exec($curl);
					//echo $callUrl;
					curl_close($curl);
					$result = json_decode($result, true);

					// Logger::logMessage("Result : " . json_encode($result));
					//echo count($result['result']['records']) . "\r\n";
					//$nhits = $result['result']['total'];
					//$nhits = count($result['result']['records']);
					//$nhitsTotal += $nhits;
					$nhitsTotal = 0;
					$nhitsRefined = 0;
					$values = array();

					for ($j = 0; $j < count($result['result']['records']); ++$j) {

						$value = array();
						$value['name'] = $result['result']['records'][$j][$facets[$i]];
						$value['path'] = $value['name'];
						//$value['count'] = $result2['result']['total'];
						$value['count'] = $result['result']['records'][$j]['total'];

						$bool = false;
						foreach ($filters_init as $k => $v) {
							$v = urldecode($v);

							if (is_array($v) && in_array($value['name'], $v) || $value['name'] == $v) {
								$bool = true;
								break;
							}
						}
						if ($qField != "" && $value['name'] == $qField) {
							$bool = true;
						}
						if ($bool) {
							$value['state'] = "refined";
							$nhitsRefined += $value['count'];
						} else {
							$value['state'] = "displayed";
						}
						if ($value['count'] > 0) {
							$values[] = $value;

							$nhitsTotal += $value['count'];
						}
					}

					if ($filterSort && $facets[$i] == $filterKey) {
						//For now we only filter by Alphanum. Need to support other filters
						array_multisort(array_column($values, "name"), SORT_ASC, $values);
					} else {
						array_multisort(array_column($values, "count"), SORT_DESC, $values);
					}

					//echo count($values)." ". $nhitsTotal; 
					if (count($values) > ($nhitsTotal - 5 * $nhitsTotal / 100)) { //protection interface
						$values = array_slice($values, 0, 500);
					}
					$group['name'] = $facets[$i];
					$group['facets'] = $values;

					$facet_groups[] = $group;
					if ($nhitsRefined == 0) {
						$nhitsRefined = $nhitsTotal;
					}
					if ($nhits == 0) {
						$nhits = $nhitsRefined;
					} else {
						$nhits = min($nhits, $nhitsRefined);
					}
				}
			}
			$data_array = array();
			$data_array['nhits'] = $nhits;
			$data_array['facet_groups'] = $facet_groups;
			foreach ($query_params as $key => $value) {
				if (!empty($key)) {
					$data_array["parameters"][$key] =  $value;
				}
			}

			if (array_key_exists("rows", $query_params) && $query_params["rows"] > 0) {
				$data = $this->getDatastoreRecord_v2($params);
				$data_array['records'] = $data['records'];
				$data_array['nhits'] = $data['nhits'];
			}

			return $data_array;
		}
		else {
			return $this->getDatastoreApi($params);
		}
	}

	private function getFacetValuebyName($name, $array)
	{
		foreach ($array as $row) {
			if ($row['name'] == $name) {
				return $row;
			}
		}
		return NULL;
	}



	public function callPackageShow($params)
	{
		$params = $this->retrieveParameters($params);

		$result = $this->getPackageShow($params);
		unset($result["help"]);
		foreach ($result["result"]["resources"] as $j => $value) {
			unset($result["result"]["resources"][$j]["url"]);
		}


		$result["result"]["metadata_imported"] = $result["result"]["metadata_modified"];
		$result["result"]["metadata_modified"] = current(array_filter($result["result"]["extras"], function ($f) {
			return $f["key"] == "date_moissonnage_last_modification";
		}))["value"] ?: $result["result"]["metadata_modified"];
		$result["result"]["metadata_created"] = current(array_filter($result["result"]["extras"], function ($f) {
			return $f["key"] == "date_moissonnage_creation";
		}))["value"] ?: $result["result"]["metadata_created"];

		echo json_encode($result);
		$response = new Response();
		//$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	public function callPackageShowForSearch($params)
	{
		$result = $this->getPackageShow($params);
		unset($result["help"]);

		echo json_encode($result);
		$response = new Response();
		//$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	public function getPackageShow($params)
	{
		$callUrl =  $this->urlCkan . "api/action/package_show?" . $params;
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		$result = json_decode($result, true);
		return $result;
	}

	public function getPackageSearch($params, $additionnalParameters = null, $rows = null, $start = null)
	{
		//$params = str_replace("qf=title^3.0 notes^1.0", "qf=title^3.0+notes^1.0", $params);	 
		$callUrl =  $this->urlCkan . "api/action/package_search";


		if (!is_null($params)) {
			$params = str_replace('&defType=edismax', '', $params);
			$callUrl .= "?" . $params;
			$callUrl = str_replace('%3D', '=', $callUrl);
			$callUrl = str_replace('%26', '&', $callUrl);
		}

		if ($additionnalParameters) {
			$callUrl = str_replace('%3D', '=', $callUrl);
		}

		// Logger::logMessage("Call search " . $callUrl);

		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);

		$result = json_decode($result, true);

		Logger::logMessage("Found " . count($result["result"]["results"]) . " datasets");

		//Here we have the result from CKAN
		//We need to filter those result according to the selected map area (if there is a selection)

		//First we get the coordinate from the map
		$coordmap = "";
		if ($additionnalParameters) {

			$coordmap = $additionnalParameters;

			//We put the coordinates in an array. Very ugly way to do but no time. To remake
			$coordmap = str_replace('%28', '(', $coordmap);
			$coordmap = str_replace('%29', ')', $coordmap);
			$coordmap = str_replace('%2C', ',', $coordmap);
			$coordinates = explode("),", $coordmap);

			for ($i = 0; $i < count($coordinates); ++$i) {
				$coordinates[$i] = str_replace('(', '', $coordinates[$i]);
				$coordinates[$i] = str_replace(')', '', $coordinates[$i]);
			}

			// Logger::logMessage("COORDINATES " . json_encode($coordinates));
			$dataSetscontent = [];



			// We browse the resources of all the dataset found to see if it contains a geoloc field
			foreach ($result["result"]["results"] as $keydataset => $dataset) {

				$resourceId = null;
				$fieldCoordinates = null;
				foreach ($dataset["resources"] as $value) {
					//We get the field for the dataset
					$fields = $this->getAllFields($value['id']);
					// Logger::logMessage("Dataset      " . $dataset['id'] . "    with resource     " . $value['id']);

					foreach ($fields as $field) {
						if ($field['type'] == "geo_point_2d") {
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
					if (sizeof($coordinates) <= 3) {
						if ($coord == null) {
							$coord = explode(',', $coordinates[0]);
						}
						$lat = $coord[0];
						$long = $coord[1];


						if (count($coord) > 2) {
							$dist = $coord[2];


							$sql = "Select count(*), min((point(" . $fieldCoordinates . "))[0]) as minLat, max((point(" . $fieldCoordinates . "))[0]) as maxLat, min((point(" . $fieldCoordinates . "))[1]) as minLong, max((point(" . $fieldCoordinates . "))[1]) as maxLong from \"" . $resourceId . "\"";
							$sql .= "where circle(point(" . $lat . "," . $long . "), " . $this->getRadius($lat, $long, $dist) . ") @> point(" . $fieldCoordinates . ")  ";
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

					if ((int)$resultSql["result"]["records"][0]["count"] > 0) {
						if (!in_array($dataset, $dataSetscontent)) {
							array_push($dataSetscontent, $dataset);
						}
					} else {

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
				$result["result"]["count"] = sizeof($dataSetscontent);
			}
		}

		return $result;
	}

	public function getExtendedPackageSearch($params, $exclude_private_orgas = TRUE/*, $return_visualisations = TRUE*/)
	{
		$query_params = $this->proper_parse_str($params);

		//error_log($params);
		if ($query_params["sort"] != null) {
			$query_params["sort"] = str_replace("title", "title_string", $query_params["sort"]);
		}

		//Apply security
		$isConnected = \Drupal::currentUser()->isAuthenticated();
		//If the user is not connected we do not apply security
		if ($isConnected) {
			$current_user = \Drupal::currentUser();

			// We include private datasets
			$query_params["include_private"] = true;

			$applySecurity = false;

			// If the user is an admin, we do not filter by organisation
			if (!in_array("administrator", $current_user->getRoles())) {
				//We include all public datasets
				if ($query_params["fq"] == null) {
					$query_params["fq"] = "(organization:(*) AND private:(false))";
				}
				else {
					$query_params["fq"] .= " AND " . "(organization:(*) AND private:(false))";
				}

				$applySecurity = true;
				if ($this->isObservatory()) {
					$allowedOrganizations = $this->getObservatoryOrganisations();
				}
				else {
					//We include private datasets from allowed organisations for the user
					$allowedOrganizations = $this->getAllOrganisations(false, false, true);
				}
			}
			else if ($this->isObservatory()) {
				$applySecurity = true;
				$allowedOrganizations = $this->getObservatoryOrganisations();
			}

			if ($applySecurity && $allowedOrganizations != null && sizeof($allowedOrganizations) > 0) {
				if ($query_params["fq"] == null) {
					$query_params["fq"] = "(";
				}
				else {
					$query_params["fq"] .= " AND (";
				}
				$isFirst = true;
				foreach ($allowedOrganizations as $org) {
					$query_params["fq"] .= $isFirst ? "" : " OR ";
					$query_params["fq"] .= "(organization:(" . $org . ") AND (private:(true) OR private:(false)))";
					$isFirst = false;
				}
				$query_params["fq"] .= ")";
			}
		}
		else if ($this->isObservatory()) {
			$allowedOrganizations = $this->getObservatoryOrganisations();
			foreach ($allowedOrganizations as $org) {
				if ($query_params["fq"] == null) {
					$query_params["fq"] .= "(organization:(" . $org . "))";
				}
				else {
					$query_params["fq"] .= " AND " . "(organization:(" . $org . "))";
				}
			}
		}

		Logger::logMessage("TRM - Query parameters " . $query_params["fq"]);
		
		$coordinateParam = null;
		if (array_key_exists('coordReq', $query_params)) {
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
			} else {
				$query_params["fq"] .= " AND features:(*geo*)";
			}
		}

		if ($exclude_private_orgas) {
			$callUrlOrg =  $this->urlCkan . "api/action/organization_list?all_fields=true&include_extras=true";
			$curlOrg = curl_init($callUrlOrg);
			curl_setopt_array($curlOrg, $this->getSimpleOptions());
			$orgs = curl_exec($curlOrg);
			curl_close($curlOrg);
			$orgs = json_decode($orgs, true);

			$orgs_private = [];
			$orgsPrivateIndex = [];
			for ($i = 0; $i <= count($orgs["result"]); $i++) {
				$org = $orgs["result"][$i];
				foreach ($org["extras"] as $extra) {
					if ($extra["key"] == "private") {
						if ($extra["value"] == "true") {
							$orgs_private[] = $org["name"];
							$orgsPrivateIndex[] = $i;
							// unset($orgs["result"][$key]);
						}
						break;
					}
				}
			}

			$nbRemove = 0;
			foreach ($orgsPrivateIndex as $index) {
				array_splice($orgs["result"], ($index - $nbRemove), 1);
				$nbRemove = $nbRemove + 1;
			}

			if (count($orgs_private) > 0) {
				$queryOrgs = implode($orgs_private, " OR ");
				$req = "-organization:(" . $queryOrgs . ")";

				if ($query_params["fq"] == null) {
					$query_params["fq"] = $req;
				} else {
					$query_params["fq"] .= " AND " . $req;
				}
			}
		}

		// If the dataset has been flagged rgpd, we only show if the user is connected
		// $isConnected = \Drupal::currentUser()->isAuthenticated();
		// if ($this->config->client->check_rgpd && !$isConnected) {
		// 	Disable for now
		// 	if ($query_params["fq"] == null) {
		// 		$query_params["fq"] = "-data_rgpd:(1)";
		// 	}
		// 	else {
		// 		$query_params["fq"] .= " AND -data_rgpd:(1)";
		// 	}
		// }

		$url2 = http_build_query($query_params);

		//echo $url2;
		$result = $this->getPackageSearch($url2, $coordinateParam, $rows, $start);
		$result["all_organizations"] = $orgs["result"];
		error_log($result["result"]["count"]);
		return $result;
	}

	public function callPackageSearch($params) {
		$arr = array();

		$result = $this->getExtendedPackageSearch($params);

		$hasFacetFeature = array_key_exists("features", $result["result"]["facets"]);
		$hasFacetThemes = array_key_exists("themes", $result["result"]["facets"]);

		unset($result["help"]);
		foreach ($result["result"]["results"] as &$dataset) {
			$dataset["metas"] = array();
			$dataset["metas"]["records_count"] = current(array_filter($dataset["extras"], function ($f) {
					return $f["key"] == "records_count";
				}))["value"] ?: 0;
			$dataset["metas"]["records_count"] = floatval($dataset["metas"]["records_count"]);

			$dataset["metas"]["features"] = current(array_filter($dataset["extras"], function ($f) {
					return $f["key"] == "features";
				}))["value"] ?: null;
			$dataset["metas"]["features"] = explode(",", $dataset["metas"]["features"]);
			if ($hasFacetFeature) {
				$arr[] = $dataset["metas"]["features"];
			}
			if ($hasFacetThemes) {
				$themes[] = $dataset["metas"]["themes"];
			}

			$dataset["metas"]["custom_view"] = current(array_filter($dataset["extras"], function ($f) {
					return $f["key"] == "custom_view";
				}))["value"] ?: null;
			$dataset["metas"]["custom_view"] = json_decode($dataset["metas"]["custom_view"], true);

			$dataset["metadata_imported"] = $dataset["metadata_modified"];
			$dataset["metadata_modified"] = current(array_filter($dataset["extras"], function ($f) {
					return $f["key"] == "date_moissonnage_last_modification";
				}))["value"] ?: $dataset["metadata_modified"];
			$dataset["metadata_created"] = current(array_filter($dataset["extras"], function ($f) {
					return $f["key"] == "date_moissonnage_creation";
				}))["value"] ?: $dataset["metadata_created"];

			foreach ($dataset["resources"] as $j => $value) {
				unset($dataset["resources"][$j]["url"]);	//echo $value["url"];
			}

			$lastDateModification = $this->extractLastModificationDate($dataset);
			$dataset["dataset_modification_date"] = $lastDateModification;
		}

		if ($hasFacetFeature) {
			$arr = array();
			foreach ($result["result"]["facets"]["features"] as $key => $count) {
				for ($i = 0; $i < $count; $i++) {
					$arr = array_merge($arr, explode(",", $key));
				}
			}

			$result["result"]["facets"]["features"] = array_count_values($arr);
			unset($result["result"]["facets"]["features"]["api"]);
			unset($result["result"]["facets"]["features"]["table"]);
			unset($result["result"]["facets"]["features"]["timeserie"]);

			$result["result"]["search_facets"]["features"]["items"] = array();
			foreach ($result["result"]["facets"]["features"] as $feat => $c) {
				$result["result"]["search_facets"]["features"]["items"][] = array(
					"count" => $c,
					"display_name" => $feat,
					"name" => $feat
				);
			}
		}

		if ($this->config->client->nutch === true) {
			Logger::logMessage("Nutch is enable. Calling nutch to search for page");
			$nutchApi = new NutchApi;
			$result = $nutchApi->callNutch($this, $params, $result);
		}

		if ($hasFacetThemes) {

			$themes = array();
			foreach ($result["result"]["facets"]["themes"] as $key => $count) {
				for ($i = 0; $i < $count; $i++) {
					$themes = array_merge($themes, json_decode($key, true));
				}
			}

			$result["result"]["facets"]["themes"] = array_count_values($themes);

			$result["result"]["search_facets"]["themes"]["items"] = array();
			foreach ($result["result"]["facets"]["themes"] as $theme => $c) {
				$result["result"]["search_facets"]["themes"]["items"][] = array(
					"count" => $c,
					"display_name" => $theme,
					"name" => $theme
				);
			}
		}

		$response = new Response();
		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	function extractLastModificationDate($dataset) {

		$referenceDate = null;

		// Manage geonetwork
		$datasetReferenceDateJson = DatasetHelper::extractMetadata($dataset['extras'], "dataset-reference-date");
		$datasetReferenceDate = json_decode($datasetReferenceDateJson, true);
		foreach ($datasetReferenceDate as $date) {
			if ($date['type'] == "creation") {
				$dateCreation = $date['value'];
			}
			else if ($date['type'] == "publication") {
				$datePublication = $date['value'];
			}
			else if ($date['type'] == "revision") {
				$dateRevision = $date['value'];
			}
		}

		$dateIssued = DatasetHelper::extractMetadata($dataset['extras'], "issued");
		$dateModified = DatasetHelper::extractMetadata($dataset['extras'], "modified");

		// Check if not null, compare them and return the most recent
		if ($dateCreation != null) {
			$referenceDate = new \DateTime($dateCreation);
		}
		if ($datePublication != null) {
			$datePublication = new \DateTime($datePublication);
			if ($referenceDate == null || $datePublication > $referenceDate) {
				$referenceDate = $datePublication;
			}
		}
		if ($dateRevision != null) {
			$dateRevision = new \DateTime($dateRevision);
			if ($referenceDate == null || $dateRevision > $referenceDate) {
				$referenceDate = $dateRevision;
			}
		}
		if ($dateIssued != null) {
			$dateIssued = new \DateTime($dateIssued);
			if ($referenceDate == null || $dateIssued > $referenceDate) {
				$referenceDate = $dateIssued;
			}
		}
		if ($dateModified != null) {
			$dateModified = new \DateTime($dateModified);
			if ($referenceDate == null || $dateModified > $referenceDate) {
				$referenceDate = $dateModified;
			}
		}

		if ($referenceDate != null) {
			return $referenceDate->format('Y-m-d');
		}

		// If OpenDataSoft not found manage ArcGIS
		$arcgisDcatIssued = DatasetHelper::extractMetadata($dataset['extras'], "dcat_issued");
		$arcgisDcatModified = DatasetHelper::extractMetadata($dataset['extras'], "dcat_modified");

		if ($arcgisDcatIssued != null) {
			$arcgisDcatIssued = new \DateTime($arcgisDcatIssued);
			if ($referenceDate == null || $arcgisDcatIssued > $referenceDate) {
				$referenceDate = $arcgisDcatIssued;
			}
		}

		if ($arcgisDcatModified != null) {
			$arcgisDcatModified = new \DateTime($arcgisDcatModified);
			if ($referenceDate == null || $arcgisDcatModified > $referenceDate) {
				$referenceDate = $arcgisDcatModified;
			}
		}

		$metadataModified = $dataset["metadata_modified"];
		if ($metadataModified != null) {
			$metadataModified = new \DateTime($metadataModified);
			if ($referenceDate == null || $metadataModified > $referenceDate) {
				$referenceDate = $metadataModified;
			}
		}

		foreach ($dataset["resources"] as $j => $value) {
			$resourceDateModified = $value['last_modified'];
			if ($resourceDateModified != null) {
				$resourceDateModified = new \DateTime($resourceDateModified);
				if ($referenceDate == null || $resourceDateModified > $referenceDate) {
					$referenceDate = $resourceDateModified;
				}
			}
		}

		// Format date and include the time and convert date to paris timezone
		if ($referenceDate != null)
			$referenceDate->setTimezone(new \DateTimeZone('Europe/Paris'));

		return $referenceDate != null ? $referenceDate->format('Y-m-d H:i') : null;
	}


	public function callPackageSearch_public_private($params, $iduser = NULL, $selectedOrg = null, $applyOrganizationSecurity = false)
	{
		$params = str_replace("qf=title^3.0 notes^1.0", "qf=title^3.0+notes^1.0", $params);
		$params = str_replace("+asc", " asc", str_replace("+desc", " desc", $params));

		$callUrl =  $this->urlCkan . "api/action/package_search";

		$query_params = $this->proper_parse_str($params);

		//If the user has a role for the organization we do not apply the security
		$allowedOrganizations = $this->getUserOrganisations();
		if (isset($selectedOrg) && $selectedOrg != '') {
			$organizationParameter = 'organization:"' . $selectedOrg . '"';

			if ($query_params["q"] == null) {
				$query_params["q"] = $organizationParameter;
			} else {
				$query_params["q"] .= " AND " . $organizationParameter;
			}
		} else if ($applyOrganizationSecurity) {
			$organizationParameter = $this->getUserOrganizationsParameter($allowedOrganizations);
			if (isset($organizationParameter)) {
				if ($query_params["q"] == null) {
					$query_params["q"] = $organizationParameter;
				} else {
					$query_params["q"] .= " AND " . $organizationParameter;
				}
			}
		}

		$current_user = \Drupal::currentUser();
		if (in_array("administrator", $current_user->getRoles())) {
			$isAdmin = true;
		}

		if ($iduser != NULL) {
			//If we apply security by organizations, we do not apply the user security which will probably disappear in a future version
			if (!$applyOrganizationSecurity && !$this->isOrganizationAllowed($selectedOrg, $allowedOrganizations)) {
				if ($isAdmin) {
					$req = "-(-edition_security:*administrator* OR edition_security:*)";
				} else {
					$req = "-(-edition_security:**" . $iduser . "** OR edition_security:*)";
				}

				if ($query_params["fq"] == null) {
					$query_params["fq"] = $req;
				} else {
					$query_params["fq"] .= " AND " . $req;
				}
			} else {
				Logger::logMessage("User has the role for organization " . $selectedOrg . ". We do not filter.");
			}
		}
		// else {
		// 	//We replace space here as we do not encode url again
		// 	$params = str_replace(" ", "+", $params);	 
		// }

		//We encode url again
		$params = http_build_query($query_params);

		if (!is_null($params)) {
			$callUrl .= "?" . $params;
		}

		// Logger::logMessage("TRM - Call URL : " . $callUrl);

		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		$result = json_decode($result, true);

		unset($result["help"]); //echo count($result["result"]["results"]);
		foreach ($result["result"]["results"] as $i => $dataset) {
			$result["result"]["results"][$i]["metas"] = array();
			$result["result"]["results"][$i]["metas"]["records_count"] = 0;
		}

		// foreach($arr_dell as &$value) {
		//     unset($result["result"]["results"][$value]);
		// }

		$result["result"]["results"] = array_merge($result["result"]["results"]);

		$response = new Response();
		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}




	public function callDatastoreApiBoundingBox($params)
	{
		$params = $this->retrieveParameters($params);


		$patternRefine = '/refine./i';
		$patternDisj = '/disjunctive./i';
		$patternBbox = '/geofilter.bbox/i';
		$patternDistance = '/geofilter.distance/i';
		$patternPolygon = '/geofilter.polygon/i';
		$reqQfilter = "";
		$qField = "";
		$filters_init = array();
		//echo $params . "\r\n";
		$query_params = $this->proper_parse_str($params);

		$fields = $this->getAllFields($query_params['resource_id']);

		$fieldCoordinates = "";
		$fieldGeometries = "";
		$fieldId = "_id";

		$coordinatesAlreadyDefined = false;
		$geometriesAlreadyDefined = false;
		foreach ($fields as $value) {
			if (!$coordinatesAlreadyDefined && $value['type'] == "geo_point_2d") {
				// If the field is created from lat/lon, we need to use the field sql definition
				if (isset($value['sql'])) {
					$fieldCoordinates = $value['sql'];
				} else {
					$fieldCoordinates = $value['name'];
				}
				$coordinatesAlreadyDefined = true;
			}
			if (!$geometriesAlreadyDefined && $value['type'] == "geo_shape") {
				$fieldGeometries = $value['name'];
				$geometriesAlreadyDefined = true;
			}
		}

		if (array_key_exists('rows', $query_params)) {
			$query_params['limit'] = $query_params['rows'];
			unset($query_params['rows']);
		}
		if (array_key_exists('q', $query_params)) {
			$reqQfilter = $this->constructReqQToSQL($query_params['q']);
		}
		foreach ($query_params as $key => $value) {
			Logger::logMessage("Found parameter " . $key . " with value " . $value . "\r\n");

			if (preg_match($patternRefine, $key)) {
				$filters_init[preg_replace($patternRefine, "", $key)] =  $value;

				unset($query_params[$key]);
				//echo preg_replace($pattern,"",$key);
			}
			if (preg_match($patternDisj, $key)) {
				unset($query_params[$key]);
				//$disj[] = preg_replace($patternDisj,"",$key);
			}
			if (preg_match($patternBbox, $key)) {
				unset($query_params[$key]);
				$filters_init[$key] =  $value;
			}
			if (preg_match($patternDistance, $key)) {
				unset($query_params[$key]);
				$filters_init[$key] =  $value;
			}
			if (preg_match($patternPolygon, $key)) {
				unset($query_params[$key]);
				$filters_init[$key] =  $value;
			}
		}
		$where = "";
		if (!empty($filters_init)) {
			Logger::logMessage("Filters exists");

			$where = " where ";
			foreach ($filters_init as $key => $value) {
				if ($key == "geofilter.bbox") {
					Logger::logMessage("Build query for geofilter.bbox \r\n");

					$bbox = explode(',', $value);
					$minlat = $bbox[0];
					$minlong = $bbox[1];
					$maxlat = $bbox[2];
					$maxlong = $bbox[3];
					//$where .= "CAST(split_part(".$fieldCoordinates.",',',1)AS FLOAT) between " . $minlat . " and " . $maxlat . " and ";
					//$where .= "CAST(split_part(".$fieldCoordinates.",',',2)AS FLOAT) between " . $minlong . " and " . $maxlong . " and ";
					$where .= "box(point(" . $minlat . "," . $minlong . "),point(" . $maxlat . "," . $maxlong . ")) @> point(" . $fieldCoordinates . ") and ";
					$where .= $fieldCoordinates . " not in ('', ',') and ";
				} else if ($key == "geofilter.distance") {
					Logger::logMessage("Build query for geofilter.distance \r\n");

					$coord = explode(',', $value);
					$lat = $coord[0];
					$long = $coord[1];
					if (count($coord) > 2) {
						$dist = $coord[2];
						//$bbox = $this->getBbox($lat,$long,$dist);
						//$bbox = explode(',', $bbox);
						//$minlat = $bbox[0];
						//$minlong = $bbox[1];
						//$maxlat = $bbox[2];
						//$maxlong = $bbox[3];
						//$where .= "box(point(" . $minlat . "," . $minlong . "),point(" . $maxlat . "," . $maxlong . ")) @> point(".$fieldCoordinates.") and ";
						//$where .= "circle(box(point(" . $minlat . "," . $minlong . "),point(" . $maxlat . "," . $maxlong . "))) @> point(".$fieldCoordinates.") and ";
						$where .= "circle(point(" . $lat . "," . $long . "), " . $this->getRadius($lat, $long, $dist) . ") @> point(" . $fieldCoordinates . ") and ";
						//$where .= "circle(polygon(path '(" . $this->getLosangePath($lat,$long,$dist) . ")')) @> point(".$fieldCoordinates.") and ";
						//echo $where;
					} else {
						//$where .= "CAST(split_part(".$fieldCoordinates.",',',1)AS FLOAT) = " . $lat . " and ";
						//$where .= "CAST(split_part(".$fieldCoordinates.",',',2)AS FLOAT) = " . $long . " and ";
						$where .= "point(" . $lat . "," . $long . ") ~= point(" . $fieldCoordinates . ") and ";
					}

					//$where .= $fieldCoordinates." not in ('', ',') and ";
				} else if ($key == "geofilter.polygon") {
					Logger::logMessage("Build query for geofilter.polygon \r\n");

					//polygon(path '((0,0),(1,1),(2,0))')
					$where .= "polygon(path '(" . $value . ")') @> point(" . $fieldCoordinates . ") and ";
				} else {
					Logger::logMessage("Build query without parameter \r\n");

					if (is_numeric($value) && $key != "insee_com" && $key != "code_insee" && $key != "sta_nm_dpt") {
						$where .= $key . "=" . $value . " and ";
					} else if (is_array($value)) {
						if ($key != "insee_com" && $key != "code_insee") {
							$value = implode(',', array_map(array($this, 'quotesArrayValue'), str_replace("'", "''", $value)));
							$value = urldecode($value);
							$where .= $key . " in (" . $value . ") and ";
						}
						else {
							$where .= $key . " in (" . implode(',', array_map(array($this, 'quotesStringArrayValue'), $value)) . ") and ";
						}
					} else {
						$value = urldecode($value);
						$where .= $key . "='" . str_replace("'", "''", $value) . "' and ";
					}
				}
			}
			$where = substr($where, 0, strlen($where) - 4);
			if ($reqQfilter != "") {
				$where .= $reqQfilter;
			}
		} else if ($reqQfilter != "") {
			Logger::logMessage("Req filter is not empty '" . $reqQfilter . "' and we put '" . substr($reqQfilter, 5) . "'");

			$where = " where " . substr($reqQfilter, 5);
		}


		$req = array();
		if ($fieldGeometries != ""/* && !empty($filters_init)*/) {
			$sql = "with latlong as ( Select cast(unnest(regexp_matches(" . $fieldGeometries . ", '\[([-]?[\d|.]*),', 'g')) as float) as longs, cast(unnest(regexp_matches(" . $fieldGeometries . ", ',[ ]?([-]?[\d|.]*)(?:,[\d|\w]+,[\d|\w]+)*\]', 'g')) as float) as lats, " . $fieldId . " as ids from \"" . $query_params['resource_id'] . "\"" . $where .
				" limit 1000) select count(distinct ids), min(lats) as minlat, max(lats) as maxlat, min(longs) as minlong, max(longs) as maxlong from latlong";

			$req['sql'] = $sql;
		} else {
			//$sql = "Select count(*) as count,min(CAST(split_part(".$fieldCoordinates.",',',1)AS FLOAT)) as minLat,max(CAST(split_part(".$fieldCoordinates.",',',1)AS FLOAT)) as maxLat,min(CAST(split_part(".$fieldCoordinates.",',',2)AS FLOAT)) as minLong,max(CAST(split_part(".$fieldCoordinates.",',',2)AS FLOAT)) as maxLong from \"" . $query_params['resource_id'] . "\"";
			$sql = "Select count(*), min((point(" . $fieldCoordinates . "))[0]) as minLat, max((point(" . $fieldCoordinates . "))[0]) as maxLat, min((point(" . $fieldCoordinates . "))[1]) as minLong, max((point(" . $fieldCoordinates . "))[1]) as maxLong from \"" . $query_params['resource_id'] . "\"";
			if (!empty($filters_init)) {
				$sql = $sql . $where . " and " . $fieldCoordinates . " not in ('', ',')";
			} else if ($reqQfilter != "") {
				$sql = $sql . $where . " and " . $fieldCoordinates . " not in ('', ',')";
			} else {
				$sql = $sql . " where " . $fieldCoordinates . " not in ('', ',')";
			}
			$req['sql'] = $sql;
		}

		// Logger::logMessage("TRM - callDatastoreApiBoundingBox - SQL : " . $req['sql']);

		$url2 = http_build_query($req);
		$callUrl =  $this->urlCkan . "api/action/datastore_search_sql?" . $url2;
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);

		// Logger::logMessage("Result query coordinate : " . $result);

		curl_close($curl);
		$result = json_decode($result, true);

		if ($fieldGeometries != "") {
			if (!empty($filters_init)) {
				$where = $where . " and " . $fieldGeometries . " not in ('', ',')";
			} else {
				$where = " where " . $fieldGeometries . " not in ('', ',')";
			}
			$sql = "Select cast(" . $fieldGeometries . "::json->'type' as text) as type_geom, count(*) from \"" . $query_params['resource_id'] . "\"" . $where . " group by type_geom";
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
			$result2 = json_decode($result2, true);
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
			$key = substr($value['type_geom'], 1, strlen($value['type_geom']) - 2);

			if (substr($key, 0, 5) == "Multi") {
				if (array_key_exists('GeometryCollection', $data_array['geometries'])) {
					$data_array['geometries']['GeometryCollection'] .= intval($value['count']);
				} else {
					$data_array['geometries']['GeometryCollection'] = intval($value['count']);
				}
			} else {
				$data_array['geometries'][$key] = intval($value['count']);
			}
			$c += intval($value['count']);
		}
		//$data_array['count'] = intval ($result["result"]["records"][0]["count"]);
		$data_array['count'] = $c;
		if ($data_array['count'] == 0) {
			$data_array['bbox'] = array();
		} else {

			$data_array['bbox'] = array(
				$result["result"]["records"][0]["minlong"],
				$result["result"]["records"][0]["maxlat"],
				$result["result"]["records"][0]["maxlong"],
				$result["result"]["records"][0]["minlat"]
			);
		}


		echo json_encode($data_array);
		$response = new Response();
		//		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	private function quotesStringArrayValue($item){
		return "'" . $item . "'";
	}

	private function quotesArrayValue($item){
		if(!is_numeric($item)) {
			return "'" . $item . "'";
		} else {
			return $item;
		}
	}

	public function getAllFields($id, $full = FALSE, $includeIdCkan = TRUE)
	{

		$callUrl = "";
		if ($full) {
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
		$result = json_decode($result, true);

		// We check if there is no sortable fields, we put all fields to sortable
		$allFieldsSortable = true;
		if ($full) {
			foreach ($result['result']['fields'] as $value) {
				$description = $value['info']['notes'];
				if (preg_match("/<!--.*sortable.*-->/i", $description)) {
					$allFieldsSortable = false;
					break;
				}
			}
		}


		$geoPointnb = 0;

		$data_array = array();
		$hasFacet = false;
		foreach ($result['result']['fields'] as $value) {
			if (!$includeIdCkan && $value['id'] == "_id") {
				continue;
			}
			$field = array();
			//if($value['id'] == "_id") continue;
			$field['name'] = $value['id'];

			$isFile = false;
			if ($full) {
				$annotations = array();
				$description = $value['info']['notes'];
				if (preg_match("/<!--.*facet.*-->/i", $description)) {
					$annotations[] = array("name" => "facet");
					$hasFacet = true; //echo "1";
				}


				if (preg_match("/<!--.*exportApi.*-->/i", $description)) {
					$annotations[] = array("name" => "exportApi");
				}
				if (preg_match("/<!--.*hideColumnsApi.*-->/i", $description)) {
					$annotations[] = array("name" => "hideColumnsApi");
				}
				if (preg_match("/<!--.*disjunctive.*-->/i", $description)) {
					$annotations[] = array("name" => "disjunctive");
				}
				if (preg_match("/<!--.*sortable.*-->/i", $description)) {
					$annotations[] = array("name" => "sortable");
				}
				if (preg_match("/<!--.*startDate.*-->/i", $description)) {
					$annotations[] = array("name" => "startDate");
				}
				if (preg_match("/<!--.*endDate.*-->/i", $description)) {
					$annotations[] = array("name" => "endDate");
				}
				if (preg_match("/<!--.*date.*-->/i", $description)) {
					$annotations[] = array("name" => "date");
				}
				if (preg_match("/<!--.*images.*-->/i", $description)) {
					$annotations[] = array("name" => "has_thumbnails");
					$isFile = true;
				}
				if (preg_match("/<!--.*wordcount-->/i", $description)) {
					$annotations[] = array("name" => "wordcount");
					$hasFacet = true; //echo "1";
				}
				if (preg_match("/<!--.*wordcountNumber.*-->/i", $description)) {
					$annotations[] = array("name" => "wordcountNumber");
					$hasFacet = true; //echo "1";
				}
				if (preg_match("/<!--.*timeserie_precision.*-->/i", $description)) {
					$annotations[] = array("name" => "timeserie_precision");
					$hasFacet = true; //echo "1";
				}
				if (preg_match("/<!--.*descr_for_timeLine.*-->/i", $description)) {
					$annotations[] = array("name" => "descr_for_timeLine");
					//$hasFacet = true; //echo "1";
				}
				if (preg_match("/<!--.*image_url.*-->/i", $description)) {
					$annotations[] = array("name" => "image_url");
					//$hasFacet = true; //echo "1";
				}
				if (preg_match("/<!--.*title_for_timeLine.*-->/i", $description)) {
					$annotations[] = array("name" => "title_for_timeLine");
					//$hasFacet = true; //echo "1";
				}
				if (preg_match("/<!--.*date_timeLine.*-->/i", $description)) {
					$annotations[] = array("name" => "date_timeLine");
					//$hasFacet = true; //echo "1";
				}
				if (preg_match("/<!--.*can_edit.*-->/i", $description)) {
					$annotations[] = array("name" => "can_edit");
				}
				if (preg_match("/<!--.*map_display.*-->/i", $description)) {
					$annotations[] = array("name" => "mapDisplay");
				}

				$descriptionLabel = $description;
				preg_match_all('/(?<=<!--description\?)([^>]*)-->/', $descriptionLabel, $matches);
				if ($matches) {
					$descriptionLabel = $matches[1][0];
					$descriptionLabel = preg_replace('/_/i', ' ', $descriptionLabel);
				} else {
					$descriptionLabel = '';
				}
				$field['descriptionlabel'] = $descriptionLabel;

				$field['description'] = $description;

				if (count($annotations) > 0) {
					$field['annotations'] = $annotations;
				}
				if ($value['info']['label'] != "") {
					$field['label'] = $value['info']['label'];
				} else {
					$field['label'] = $field['name'];
				}
			} else {
				$field['label'] = $field['name'];
			}

			$valueId = $value['id'];

			$propertiesHelper = new PropertiesHelper();

			$reservedColumnsGeopoint = $propertiesHelper->getProperty(PropertiesHelper::RESERVED_COLUMNS_GEOPOINT);
			if (empty($reservedColumnsGeopoint)) {
				$reservedColumnsGeopoint = 'geoloc,geo_point,coordin,coordon,geopoint,geoPoint,pav_positiont2d,wgs84,equgpsy_x,geoban,codegeo,latlon,lat_lon';
			}
			$reservedColumnsGeopoint = explode(',', $reservedColumnsGeopoint);

			$isGeopoint = false;
			foreach ($reservedColumnsGeopoint as $pattern) {
				if (preg_match('/' . $pattern . '/i', $valueId)) {
					$isGeopoint = true;
					break;
				}
			}

			$reservedColumnsGeoshape = $propertiesHelper->getProperty(PropertiesHelper::RESERVED_COLUMNS_GEOSHAPE);
			if (empty($reservedColumnsGeoshape)) {
				$reservedColumnsGeoshape = 'geo_shape,geome,geojson';
			}
			$reservedColumnsGeoshape = explode(',', $reservedColumnsGeoshape);

			$isGeoshape = false;
			foreach ($reservedColumnsGeoshape as $pattern) {
				if (preg_match('/' . $pattern . '/i', $valueId)) {
					$isGeoshape = true;
					break;
				}
			}

			if ($isGeopoint) {
				$field['type'] = "geo_point_2d";
			}
			else if ($isGeoshape) {
				$field['type'] = 'geo_shape';
			}
			else if ($value['type'] == "timestamp") {
				$field['type'] = "datetime";
			}
			else if ($value['type'] == "numeric") {
				$field['type'] = "double";
			}
			else if ($isFile) {
				$field['type'] = "file";
			}
			else {
				$field['type'] = $value['type'];
			}

			$field['poids'] = $value['info']['poids'];
			$data_array[] = $field;
		}

		if (!$hasFacet) {
			$hasTextCol = false;
			foreach ($data_array as $id => $field) {
				if ($field['type'] == "text") {
					$data_array[$id]['annotations'][] = array("name" => "facet");
					$hasTextCol = true;
					break;
				}
			}
			if (!$hasTextCol) {
				$data_array[0]['annotations'][] = array("name" => "facet");
			}
		}

		return $data_array;
	}


	public function getAllFieldsForTableParam($resourceId)
	{
		$callUrl =  $this->urlCkan . "api/action/datastore_search?resource_id=" . $resourceId . "&limit=0";
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		$result = json_decode($result, true);
		return $result;
	}

	public function callAllFieldsForTableParam($params)
	{

		$res = $this->getAllFieldsForTableParam($params);


		echo json_encode($res);
		$response = new Response();
		//$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;

		return $result;
	}




	public function getTableFields($id)
	{

		$callUrl =  $this->urlCkan . "api/action/datastore_search?resource_id=" . $id . "&limit=0";

		//echo $callUrl;
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		$result = json_decode($result, true);

		$fields = $result['result']['fields'];

		$res = array();
		$allFields = array();
		
		// Sort fields by poids if not null
		$sort = array();

		$fieldsSize = count($fields);
		$index = $fieldsSize;
		foreach ($fields as $key => $value) {
			$sort['poids'][$key] = isset($value['info']['poids']) && $value['info']['poids'] != "" ? ($value['info']['poids'] + $fieldsSize) : $index;
			$index--;
		}

		array_multisort($sort['poids'], SORT_DESC, $fields);

		foreach ($fields as $value) {
			$description = $value['info']['notes'];
			if (preg_match("/<!--\s*table\s*-->/i", $description)) {
				$res[] =  $value['id'];
			}
			if ($value['id'] == "_id") continue;
			$allFields[] =  $value['id'];
		}
		if (count($res) > 0) {
			return $res;
		} else {
			return $allFields;
		}
	}

	public function getMapTooltipFields($id)
	{

		$callUrl =  $this->urlCkan . "api/action/datastore_search?resource_id=" . $id . "&limit=0";
		//echo $callUrl;
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		$result = json_decode($result, true);

		$res = array();
		$allFields = array();

		foreach ($result['result']['fields'] as $value) {
			$description = $value['info']['notes'];
			if (preg_match("/<!--\s*tooltip\s*-->/i", $description)) {
				$res[] =  $value['id'];
			}
			if ($value['id'] == "_id") continue;
			$allFields[] =  $value['id'];
		}
		if (count($res) > 0) {
			return $res;
		} else {
			return $allFields;
		}
	}

	public function getRecordsDownload($params, $downloadFile = false)
	{
		$patternId = '/id|num|code|siren/i';
		$patternRefine = '/refine./i';
		$patternDisj = '/disjunctive./i';
		$patternBbox = '/geofilter.bbox/i';
		$patternDistance = '/geofilter.distance/i';
		$patternSerie = '/y.serie/i';
		$filters_init = array();
		$params = $this->retrieveParameters($params);
		$query_params = $this->proper_parse_str($params);

		if ($query_params['user_defined_fields']) {
			//array_pop($query_params);
			$exportUserField = $this->getAllFieldsForTableParam($query_params['resource_id'], 'true');
		}

		$fields = $this->getAllFields($query_params['resource_id'], FALSE, FALSE);

		//echo json_encode($fields);
		$fieldId = "_id";
		$reqFields = "";

		$fieldCoordinates = '';
		$fieldGeometries = '';

		$reqQfilter = null;

		$coordinatesAlreadyDefined = false;
		$geometriesAlreadyDefined = false;
		foreach ($fields as $value) {
			if (!$coordinatesAlreadyDefined && $value['type'] == "geo_point_2d") {
				$fieldCoordinates = $value['name'];
				$coordinatesAlreadyDefined = true;
			}
			if (!$geometriesAlreadyDefined && $value['type'] == "geo_shape") {
				$fieldGeometries = $value['name'];
				$geometriesAlreadyDefined = true;
			}
		}

		foreach ($query_params as $key => $value) {
			if (preg_match($patternRefine, $key)) {
				$filters_init[preg_replace($patternRefine, "", $key)] =  $value;

				unset($query_params[$key]);
				//echo preg_replace($pattern,"",$key);
			}
			if (preg_match($patternDisj, $key)) {
				unset($query_params[$key]);
				//$disj[] = preg_replace($patternDisj,"",$key);
			}
			if (preg_match($patternBbox, $key)) {
				unset($query_params[$key]);
				$filters_init[$key] =  $value;
				//$disj[] = preg_replace($patternDisj,"",$key);
			}
			if (preg_match($patternDistance, $key)) {
				unset($query_params[$key]);
				$filters_init[$key] =  $value;
			}
			if (preg_match($patternSerie, $key)) {
				unset($query_params[$key]);
			}
			if ($key == "geofilter.polygon") {
				unset($query_params[$key]);
				$filters_init[$key] =  $value;
			}
			if ($key == "geo_digest") {
				unset($query_params[$key]);
				$filters_init[$key] =  $value;
			}
			if ($key == "format") {
				$globalFormat = $value;

				if ($value == "json") {
					$format = "objects";
				} else if ($value == "csv" || $value == "xls") {
					$format = "csv";
				} else if ($value == "tsv") {
					$format = "tsv";
				} else if ($value == "geojson") {
					$format = "objects";
				} else {
					$format = "objects";
				}
				unset($query_params[$key]);
			}
			if ($key == "geo_simplify") {
				unset($query_params[$key]);
			}
			if ($key == "geo_simplify_zoom") {
				unset($query_params[$key]);
			}
			if ($key == "rows") {
				$query_params['limit'] = $value;
				unset($query_params['rows']);
			}
			if ($key == "fields") {
				$reqFields = $value;
				unset($query_params['fields']);
			}
			if ($key == "start") {
				$query_params['offset'] = $value;
				unset($query_params['start']);
				$query_params['limit'] = $query_params['limit'] + $query_params['offset'];
			}
			if ($key == "q") {
				$reqQfilter = $this->constructReqQToSQL($value);
				//$pattern = '/and (\w+) /i';
				//preg_match($pattern,$reqQfilter,$qField); 
			}

			if ($key == "id" || $key == "basemap" || $key == "location" || $key == "datasetcard" || $key == "static" || $key == "scrollWheelZoom" || $key == "clusterdistance" || $key == "dataset") {
				unset($query_params[$key]);
			}
			if ($key == "use_labels_for_header" || $key == "return_polygons" || $key == "calendarview" || $key == "dataChart") {
				unset($query_params[$key]);
			}
		}


		if ($reqFields == "") {
			$i = 0;
			foreach ($fields as $value) {
				if ($i > 0) {
					$reqFields .= ',';
				}
				if ($value['name'] != '_id' && $value['name'] != '_full_text') {
					$reqFields .= $value['name'];
					$i++;
				}
			}
		}

		unset($query_params["clusterprecision"]);
		unset($query_params["q"]);
		unset($query_params["resourceId"]);
		$where = "";
		$limit  = "";
		if (!empty($filters_init)) {
			$where = " where ";
			foreach ($filters_init as $key => $value) {
				if ($key == "geofilter.bbox") {
					$bbox = explode(',', $value);
					$minlat = $bbox[0];
					$minlong = $bbox[1];
					$maxlat = $bbox[2];
					$maxlong = $bbox[3];
					//$where .= "CAST(split_part(".$fieldCoordinates.",',',1)AS FLOAT) between " . $minlat . " and " . $maxlat . " and ";
					//$where .= "CAST(split_part(".$fieldCoordinates.",',',2)AS FLOAT) between " . $minlong . " and " . $maxlong . " and ";
					$where .= "box(point(" . $minlat . "," . $minlong . "),point(" . $maxlat . "," . $maxlong . ")) @> point(" . $fieldCoordinates . ") and ";
					$where .= $fieldCoordinates . " not in ('', ',') and ";
				} else if ($key == "geofilter.distance") {
					$coord = explode(',', $value);
					$lat = $coord[0];
					$long = $coord[1];
					if (count($coord) > 2) {
						$dist = $coord[2];
						//$bbox = $this->getBbox($lat,$long,$dist);
						//$bbox = explode(',', $bbox);
						//$minlat = $bbox[0];
						//$minlong = $bbox[1];
						//$maxlat = $bbox[2];
						//$maxlong = $bbox[3];
						//$where .= "box(point(" . $minlat . "," . $minlong . "),point(" . $maxlat . "," . $maxlong . ")) @> point(".$fieldCoordinates.") and ";
						//$where .= "circle(point(" . $lat . "," . $long . "), " . $dist/100000 . ") @> point(".$fieldCoordinates.") and ";
						$where .= "circle(point(" . $lat . "," . $long . "), " . $this->getRadius($lat, $long, $dist) . ") @> point(" . $fieldCoordinates . ") and ";
						//$where .= "circle(polygon(path '(" . $this->getLosangePath($lat,$long,$dist) . ")')) @> point(".$fieldCoordinates.") and ";
					} else {
						//$where .= "CAST(split_part(".$fieldCoordinates.",',',1)AS FLOAT) = " . $lat . " and ";
						//$where .= "CAST(split_part(".$fieldCoordinates.",',',2)AS FLOAT) = " . $long . " and ";
						$where .= "point(" . $lat . "," . $long . ") ~= point(" . $fieldCoordinates . ") and ";
					}

					$where .= $fieldCoordinates . " not in ('', ',') and ";
				} else if ($key == "geo_digest") {
					$where .= "md5(" . $fieldGeometries . ") = '" . $value . "' and ";
				} else if ($key == "geofilter.polygon") {
					//polygon(path '((0,0),(1,1),(2,0))')
					$where .= "polygon(path '(" . $value . ")') @> point(" . $fieldCoordinates . ") and ";

					//We add a Postgis function because polygon(path) use the bounding box and with the tolerance it can contains multiples point
					// if ($this->isPostgis) {
					// 	$where .= "ST_Intersects(polygon(path '(" . $value . ")')::geometry, point(geo_point_2d)::geometry) and ";
					// }

					$where .= $fieldCoordinates . " not in ('', ',') and ";
				} else {
					if (is_numeric($value) && $key != "insee_com" && $key != "code_insee" && $key != "sta_nm_dpt") {
						$where .= $key . "=" . $value . " and ";
					} else if (is_array($value)) {
						if ($key != "insee_com" && $key != "code_insee") {
							$value = implode(',', array_map(array($this, 'quotesArrayValue'), str_replace("'", "''", $value)));
							$value = urldecode($value);
							$where .= $key . " in (" . $value . ") and ";
						}
						else {
							$where .= $key . " in (" . implode(',', array_map(array($this, 'quotesStringArrayValue'), $value)) . ") and ";
						}
					} else {
						$value = urldecode($value);
						$where .= $key . "='" . str_replace("'", "''", $value) . "' and ";
					}
				}
			}
			$where = substr($where, 0, strlen($where) - 4);

			if ($reqQfilter != NULL) {
				$where .= $reqQfilter;
			}
		} else if ($reqQfilter != NULL) {
			$where = " where " . substr($reqQfilter, 5);
		}

		if (array_key_exists("limit", $query_params)) {
			$limit = " limit " . $query_params['limit'];
		}


		$req = array();
		$sql = "Select " . $fieldId . " as id from \"" . $query_params['resource_id'] . "\"" . $where . $limit;
		$req['sql'] = $sql;

		$url2 = http_build_query($req);
		$callUrl =  $this->urlCkan . "api/action/datastore_search_sql?" . $url2;
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);

		$result = json_decode($result, true);

		//We build the first row with the header's name
		$fieldsHeader = "";
		if ($reqFields != "") {
			$reqFieldsArray = explode(",", $reqFields);
		}

		$first = true;

		/* Check if exporUserField exist and not null, means, that user has changed the attributes names of datasets */
		$stack = array();



		if ($exportUserField  != null) {
			//set header fields with exportapi as true
			foreach ($exportUserField["result"]["fields"] as $keyfields => $valuefields) {
				if ($valuefields["info"] != null && ($valuefields["info"]["label"] != null or $valuefields["info"]["label"] != "")) {
					array_push($stack, $valuefields["id"]);
				}
			}
		}


		/* if exportUserField is not exist or is null, means, that the attributes names of dataset doest not changed by user, so assign the default name to fieldHeader value */

		foreach ($fields as $value) {
			//We skip the column _full_text because we don't get the data and it is created by postgres
			if ($value['name'] == "_full_text") {
				continue;
			}

			//set header fields with exportapi as true
			if ($exportUserField  != null) {

				/* get all fields from exportuserfield */
				if (in_array($value['name'], $stack)) {
					foreach ($exportUserField["result"]["fields"] as $keyfields => $valuefields) {

						if ($valuefields["id"] == $value['name']) {
							/* Get name of ever field user and assign it to fields Header*/
							if (isset($reqFieldsArray)) {
								if (in_array($valuefields["id"], $reqFieldsArray)) {
									$fieldsHeader .= (!$first ? "," : "") . $valuefields["info"]["label"];
									$first = false;
								}
							} else {
								$fieldsHeader .= (!$first ? "," : "") . $valuefields["info"]["label"];
								$first = false;
							}

							break;
						}
					}
				} else {
					if (isset($reqFieldsArray)) {
						if (in_array($value['name'], $reqFieldsArray)) {
							$fieldsHeader .= (!$first ? "," : "") . $value['name'];
							$first = false;
						}
					} else {
						$fieldsHeader .= (!$first ? "," : "") . $value['name'];
						$first = false;
					}
				}
			} else {
				if (isset($reqFieldsArray)) {
					if (in_array($value['name'], $reqFieldsArray)) {
						$fieldsHeader .= (!$first ? "," : "") . $value['name'];
						$first = false;
					}
				} else {
					$fieldsHeader .= (!$first ? "," : "") . $value['name'];
					$first = false;
				}
			}
		}

		$fieldsHeader .= "\n";

		$ids = array();
		foreach ($result["result"]["records"] as $value) {
			$ids[] = $value["id"];
		}
		$chunk = array_chunk($ids, 850);
		if ($format == "objects") {
			$records = array();
		}

		if ($downloadFile) {
			//If the result will be download we create a new temp file to store the data as big export can lead to problem in memory
			$tempFilePath = tempnam(sys_get_temp_dir(), strftime() . 'export.' . $format);

			$header = chr(239) . chr(187) . chr(191) . $fieldsHeader;
			file_put_contents($tempFilePath, $header, FILE_APPEND);
		}

		//For each chunk, we call the database to get the data
		foreach ($chunk as $_ids) {
			//Add ids in query
			$query_params['filters'] = json_encode(array($fieldId => $_ids));

			//Add fields in query
			if ($reqFields != "") {
				$query_params['fields'] = $reqFields;
			}

			//Add format
			$query_params['records_format'] = $format;

			//Set limit if none exist
			if (!array_key_exists('limit', $query_params)) {
				$query_params['limit'] = 100000000;
			}
			if ($query_params['user_defined_fields']) {
				//$query_params = array_pop($query_params);
				unset($query_params['user_defined_fields']);
			}

			$url2 = http_build_query($query_params);
			//echo $url2;
			$callUrl =  $this->urlCkan . "api/action/datastore_search?" . $url2;
			  
			$curl = curl_init($callUrl);
			curl_setopt_array($curl, $this->getStoreOptions());
			$result2 = curl_exec($curl);
			curl_close($curl);

			$data = json_decode($result2, true)["result"]["records"];
			if ($format == "objects") {
				$records = array_merge($records, $data);
			}
			else {
				if ($downloadFile) {
					$data = preg_replace('/,(?![^"]*",)/i', ',', $data);
					file_put_contents($tempFilePath, $data, FILE_APPEND);
				}
				else {
					$records .= $data;
				}
			}
		}
		$chunk = null;
		if ($format == "objects") {
			$data_array = Export::getExport($globalFormat, $fieldGeometries, $fieldCoordinates, $records, $query_params, $ids);
			
			$records = null;
			$ids = null;
			return json_encode($data_array);
		}
		else if ($downloadFile) {
			return $tempFilePath;
		}
		else {
			//We use to export the data with ; as separator, now we came back to , as separator
			// $records = chr(239) . chr(187) . chr(191) . $fieldsHeader . preg_replace('/,(?![^"]*",)/i', ';', $records);
			$records = chr(239) . chr(187) . chr(191) . $fieldsHeader . $records;
			return 	$records;
		}
	}

	public function callDatastoreApiDownload($params)
	{
		$result = $this->getRecordsDownload($params);

		echo $result;
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	public function callDatastoreApiResourceRecords($params) {
		return $this->callDatastoreApiDownloadFile($params, false);
	}

	public function callDatastoreApiDownloadFile($params, $download = true) {
		$query_params = $this->proper_parse_str($params);
		$format = $query_params['format'];
		$resourceId = $query_params['resource_id'];


		//$fields = $this->getAllFieldsForTableParam($query_params['resource_id'], 'true');

		$reqFields = "";
		$fieldsValue = $this->getAllFields($query_params['resource_id'], TRUE, FALSE);

		//We check if at least one column is hidden
		$oneColumnIsHidden = false;

		$i = 0;
		foreach ($fieldsValue as $value) {

			$exportval = true;

			foreach ($value["annotations"] as $keyAnnota => $annotat) {
				if ($annotat["name"] == "exportApi") {
					$exportval = false;
					$oneColumnIsHidden = true;
					break;
				}
			}
			if ($i > 0 && $exportval) {
				$reqFields .= ',';
			}
			if ($exportval) {
				$reqFields .= $value['name'];
				$i++;
			}
		}
		if ($oneColumnIsHidden && ($reqFields == null || $reqFields == "")) {
			$actual_link = $_SERVER['HTTP_REFERER'];

			echo "<script type='text/javascript'>alert('L\'administrateur du site a limité les téléchargements de ce jeu de données.');window.location.replace('$actual_link');</script>";
		} else {
			$resource = $this->getResource($query_params['resource_id']);
			$resource = json_decode($resource, true);
			$resourceName = $resource['result']['name'];
			//We remove the format if present
			// if format is present, we remove it
			if (strpos($resourceName, '.') !== false) {
				$resourceName = substr($resourceName, 0, strrpos($resourceName, '.'));
			}
			$filename = isset($resourceName) ? $resourceName : $query_params['resource_id'];

			if ($format == "csv") {
				header('Content-Type:text/csv');
				header('Content-Disposition:attachment; filename=' . $filename . '.csv');
			} else if ($format == "xls") {
				header('Content-Type:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
				header('Content-Disposition:attachment; filename=' . $filename . '.xlsx');
			} else if ($format == "json") {
				header('Content-Type:application/json');
				if ($download) {
					header('Content-Disposition:attachment; filename=' . $filename . '.json');
				}
			} else if ($format == "geojson") {
				header('Content-Type:application/vnd.geo+json');
				if ($download) {
					header('Content-Disposition:attachment; filename=' . $filename . '.geojson');
				}
			} else if ($format == "shp") {
				header('Content-Type:application/zip');
				header('Content-Disposition:attachment; filename=' . $filename . '.zip');
			} else if ($format == "kml") {
				header('Content-Type:application/vnd.google-earth.kml+xml');
				header('Content-Disposition:attachment; filename=' . $filename . '.kml');
			} else {
				$params = $params . "&format=json";
				header('Content-Type:application/json');
				if ($download) {
					header('Content-Disposition:attachment; filename=' . $filename . '.json');
				}
			}

			// if the query has no refine
			$hasRefine = false;
			$patternRefine = '/refine./i';
			foreach($query_params as $key => $value) {
				if (preg_match($patternRefine, $key)) {
					$hasRefine = true;
				}
			}
			if (!$hasRefine) {
				//First we check if the file has been generated or exist already
				$resource = $this->getResource($resourceId);
				$resource = json_decode($resource, true);
				$datasetId = $resource['result']['package_id'];

				$dataset = $this->getDataset($datasetId);
				$dataset = json_decode($dataset, true);
				$extras = $dataset['result']['extras'];

				$keyFormat = null;
				if (strcasecmp($format, 'CSV') == 0) {
					$keyFormat = "file_csv";
				}
				else if (strcasecmp($format, 'XLSX') == 0 || strcasecmp($format, 'XLS') == 0) {
					$keyFormat = "file_xlsx";
				}
				else if (strcasecmp($format, 'GEOJSON') == 0) {
					$keyFormat = "file_geojson";
				}
				else if (strcasecmp($format, 'JSON') == 0) {
					$keyFormat = "file_json";
				}
				else if (strcasecmp($format, 'KML') == 0) {
					$keyFormat = "file_kml";
				}
				else if (strcasecmp($format, 'SHP') == 0) {
					$keyFormat = "file_shp";
				}

				$fileToDownload = null;
				if ($keyFormat != null && $extras != null && count($extras) > 0) {
					for ($index = 0; $index < count($extras); $index++) {
						// If file_json, we check if geojson exist
						if ($keyFormat == "file_json" && $extras[$index]['key'] == 'file_geojson') {
							$fileToDownload = $extras[$index]['value'];
						}
						// If file_json and key is json, we set the file if it is not already defined with a geojson
						else if ($keyFormat == "file_json" && $extras[$index]['key'] == "file_json" && $fileToDownload == null) {
							$fileToDownload = $extras[$index]['value'];
						}
						else if ($extras[$index]['key'] == $keyFormat) {
							$fileToDownload = $extras[$index]['value'];
						}
					}
				}

				if ($fileToDownload != null) {
					Logger::logMessage("Existing file found, we redirect the user to " . $fileToDownload);

					header("Location: " . $fileToDownload);
					die();
				}
			}

			$result = $this->getRecordsDownload($params . "&fields=" . $reqFields, true);
			if ($format == "json" || $format == "geojson") {
				echo $result;
			}
			else if ($format == "csv") {
				header('Content-Length: ' . filesize($result));
				readfile($result);
			} else if ($format == "xls") {
				//We rename the file because PhpSpreadsheet does not support conversion without
				rename($result, $result .= '.csv');

				$pathOutput = $this->generateXLSX($result, sys_get_temp_dir());

				$pathOutput = tempnam(sys_get_temp_dir(), 'output_convert_geo_file_');
				

				$reader = ReaderEntityFactory::createCSVReader();
				// $reader->setFieldDelimiter(';');
				// $reader->setFieldEnclosure('"');
				// $reader->setEndOfLineCharacter("\r");

				$writer = WriterEntityFactory::createXLSXWriter();

				$reader->open($result);
				$writer->openToFile($pathOutput); // write data to a file or to a PHP stream

				foreach ($reader->getSheetIterator() as $sheet) {
					foreach ($sheet->getRowIterator() as $row) {
						$writer->addRow($row);
					}
				}

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
				$scriptPath = $dir . '/convert_geo_files_ogr2ogr.sh';

				if ($format == "shp") {
					$typeConvert = 'ESRI Shapefile';

					//We create a temp directory
					$pathOutput = $this->tempdir(null, 'output_convert_geo_file_');
				} else if ($format == "kml") {
					$typeConvert = 'KML';

					//We create a temp file
					$pathOutput = tempnam(sys_get_temp_dir(), 'output_convert_geo_file_');
				}

				$projection = $this->config->client->shapefile_projection;

				$command = $scriptPath . " 2>&1 '" . $typeConvert . "' " . $pathOutput . " " . $pathInput . " " . $projection . " " . $filename;
				$message = shell_exec($command);

				if ($format == "kml") {
					header('Content-Length: ' . filesize($pathOutput));
					readfile($pathOutput);
				} else if ($format == "shp") {
					$pathOutputZip = tempnam(sys_get_temp_dir(), 'output_zip_convert_geo_file_');

					$zip = new ZipArchive();
					if ($zip->open($pathOutputZip, ZipArchive::CREATE) !== TRUE) {
						echo "Problem creating the zip file";
					}
					if ($handle = opendir($pathOutput)) {
						while (false !== ($entry = readdir($handle))) {
							if ($entry != "." && $entry != ".." && !strstr($entry, '.php')) {
								$zip->addFile($pathOutput . "/" . $entry, $entry);
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
	public function tempdir($dir = null, $prefix = 'tmp_', $mode = 0700, $maxAttempts = 1000)
	{
		/* Use the system temp dir by default. */
		if (is_null($dir)) {
			$dir = sys_get_temp_dir();
		}

		/* Trim trailing slashes from $dir. */
		$dir = rtrim($dir, DIRECTORY_SEPARATOR);

		/* If we don't have permission to create a directory, fail, otherwise we will
		* be stuck in an endless loop.
		*/
		if (!is_dir($dir) || !is_writable($dir)) {
			return false;
		}

		/* Make sure characters in prefix are safe. */
		if (strpbrk($prefix, '\\/:*?"<>|') !== false) {
			return false;
		}

		/* Attempt to create a random directory until it works. Abort if we reach
		* $maxAttempts. Something screwy could be happening with the filesystem
		* and our loop could otherwise become endless.
		*/
		$attempts = 0;
		do {
			$path = sprintf('%s%s%s%s', $dir, DIRECTORY_SEPARATOR, $prefix, mt_rand(100000, mt_getrandmax()));
		} while (
			!mkdir($path, $mode) &&
			$attempts++ < $maxAttempts
		);

		return $path;
	}

	public function callPackageShow3($params)
	{
		$query_params = $this->proper_parse_str($params);
		$datasetid = $query_params['id'];
		return $this->callPackageShow2($datasetid, $params);
	}

	public function getPackageShow2($datasetid, $params, $callCkan = true, $applySecurity = false, $selectedResourceId = null, $includeAllowedPrivate = false)
	{
		$result = '';

		if ($callCkan) {
			$datasetid = filter_var($datasetid, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

			// $query_params = $this->proper_parse_str($params);
			//$callUrl =  $this->urlCkan . "api/action/package_show?" . $params . "&id=" . $datasetid;
			$callUrl =  $this->urlCkan . "api/action/package_show?id=" . urlencode($datasetid); //temporaire

			$curl = curl_init($callUrl);
			curl_setopt_array($curl, $this->getStoreOptions(true));
			$result = curl_exec($curl);
			curl_close($curl);
			//echo $callUrl. "\r\n";
			$result = json_decode($result, true);
		} else {
			$result = $datasetid;
		}

		if ($applySecurity) {
			$datasetOrganization = $result['result']['organization']['name'];
			$allowedOrganizations = $this->getUserOrganisations();
			if (!$this->isDatasetAllowed($datasetOrganization, $allowedOrganizations)) {
				return array();
			}
		}

		if ($callCkan && $includeAllowedPrivate && $result['result']['private'] == true) {
			Logger::logMessage("Dataset " . $datasetid . " is private. Checking if user is allowed to see it.");
			$datasetOrganization = $result['result']['organization']['name'];
			$allowedOrganizations = $this->getUserOrganisations();
			if (!$this->isDatasetAllowed($datasetOrganization, $allowedOrganizations)) {
				return array();
			}
		}

		$resourcesid = "";
		$isGeo = false;
		$resourceCSV = NULL;

		$alternative_exports = array();
		foreach ($result['result']['resources'] as $value) {
			if (($value['format'] == 'CSV' || $value['format'] == 'XLS' || $value['format'] == 'XLSX') && $value["datastore_active"] == true) {
				$resourcesid = $value['id'];
				$resourceCSV = $value;
			}

			if ($value['format'] == 'GeoJSON') {
				$isGeo = true;
			}

			if ($selectedResourceId != null && $selectedResourceId == $resourcesid) {
				//If the selected resource ID is defined, we select it
				break;
			}
		}
		foreach ($result['result']['resources'] as $value) {
			if ((($value['format'] != '' && $value['format'] != 'CSV' && $value['format'] != 'XLS' && $value['format'] != 'XLSX' && $value['format'] != 'GeoJSON' && $value['format'] != 'KML' && $value['format'] != 'SHP' && $value['format'] != 'PDF' && $value['format'] != 'WFS' && $value['format'] != 'WMS'))
				|| (($value['format'] == 'CSV' || $value['format'] == 'XLS' || $value['format'] == 'XLSX') && $value["datastore_active"] == false)
			) {

				$a = array();
				$a["title"] = $value['name'];
				$a["format"] = $value['format'];
				$a["id"] = $value['id'];
				$a["description"] = $value['description'];
				$alternative_exports[] = $a;
			}
		}

		$resources_versions = array();
		foreach ($result['result']['resources'] as $value) {
			$resourceId = $value['id'];
			$name = $value['name'];
			$versions = $this->getResourceVersions($resourceId);

			$resource = array();
			$resource['id'] = $resourceId;
			$resource['name'] = $name;
			$resource['versions'] = $versions;
			$resources_versions[] = $resource;
		}

		$data_array = array();
		$data_array['alternative_exports'] = $alternative_exports; //[]
		$data_array['resources_versions'] = $resources_versions; //[]

		$data_array['attachments'] = array(); //[]
		$data_array['data_visible'] = true;
		$data_array['datasetid'] = $result['result']['name'];
		$data_array['extra_metas'] = array();
		$data_array['extra_metas']['explore'] = ''; //{"feedback_enabled": true, "file_field_download_count": 0, "popularity_score": 143.7, "reuse_count": 0, "api_call_count": 18317845, "download_count": 12126, "attachment_download_count": 0}
		$data_array['extra_metas']['processing'] = ''; //{"processing_modified": "2018-06-06T09:19:06+02:00", "records_size": 137993224.0, "security_last_modified": "2018-06-06T11:45:40+02:00"}	

		$visu = array();

		$visu['map_tooltip_html'] = $this->getMapTooltip(false, null);
		$visu['image_tooltip_html_enabled'] = false;
		$visu['map_marker_color'] = "#df0ee3"; // "#0e7ce3"; //"#df0ee3";

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

		foreach ($result['result']['extras'] as $value) {
			if ($value["key"] == "type_map") {
				$visu["default_map"] = $value["value"];
			}
			if ($value["key"] == "overlays") {
				$visu["overlays"] = $value["value"];
			}
			if ($value["key"] == "tooltip") {
				$val = json_decode($value["value"], true);
				if ($val["type"] == "html") {
					$mapTooltip = $this->getMapTooltip(true, $val["value"]);
					$visu['map_tooltip_html_enabled'] = true;
					$visu['map_tooltip_html'] = $mapTooltip;
				} else {
					$visu['map_tooltip_html_enabled'] = false;
					$visu['map_tooltip_fields'] =  explode(",", $val["value"]["fields"]);
					$visu['map_tooltip_title'] = $val["value"]["title"];
				}
			}
			if ($value["key"] == "reports") {
				$visu['map_tooltip_html_enabled'] = true;
				$visu["reports"] = json_decode($value["value"]);
			}
			if ($value["key"] == "records_count") {
				$data_array["extra_metas"]["data_visible"] = $data_array["data_visible"];
				$data_array["extra_metas"]["records_count"] = floatval($value["value"]);
			}
			if ($value["key"] == "features") {
				$data_array['features'] = explode(",", $value["value"]);
			}
			if ($value["key"] == "Picto") {
				$visu['map_marker_picto'] = $value["value"];
			}
			if ($value["key"] == "FieldColor" && $value["value"] != '') {

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
			if ($value["key"] == "field_label" && $value["value"] != '') {

				$visu['map_marker_label'] = array();
				$visu['map_marker_label']['type'] = "field";
				$visu['map_marker_label']['field'] = $value["value"];
			}
			if ($value["key"] == "PredefinedFilters" && $value["value"] != '') {
				$filters = explode(",", $value["value"]);

				$data_array["extra_metas"]["predefined_filters"] = array();
				foreach ($filters as $filter) {
					$myFilter = explode("==", $filter);
					$filterName = $myFilter[0];
					$filterValue = $myFilter[1];

					$data_array["extra_metas"]["predefined_filters"][$filterName] = $filterValue;
				}
			}
		}

		if ($resourcesid == "") {
			$visu['table_fields'] =  array();
			//$visu['map_tooltip_fields'] =  array();
			//$visu['map_tooltip_title'] = '';

			$data_array['fields'] = array();
		} else {
			$visu['table_fields'] = $this->getTableFields($resourcesid); //["code_insee","en_service","mutualisation_public","sup_id","mutualisation","nom_reg","nom_com","nom_dept"]
			if (count($visu['map_tooltip_fields']) == 0) {
				$visu['map_tooltip_fields'] = $this->getMapTooltipFields($resourcesid); // ["emr_lb_systeme","emr_dt_service","generation","coord","nom_com","nom_dept","nom_reg"]fields
				$visu['map_tooltip_title'] = $visu['map_tooltip_fields'][0];
			}


			$data_array['fields'] = $this->getAllFields($resourcesid, TRUE, FALSE);
		}

		$visu['calendar_tooltip_html_enabled'] = false;
		$visu['analyze_default'] = ''; //"{\"queries\":[{\"charts\":[{\"type\":\"line\",\"func\":\"COUNT\",\"color\":\"range-Accent\",\"scientificDisplay\":true}],\"xAxis\":\"nom_com\",\"maxpoints\":\"\",\"timescale\":null,\"sort\":\"\",\"seriesBreakdown\":\"emr_lb_systeme\"}],\"timescale\":\"\",\"displayLegend\":true,\"alignMonth\":true}"

		//$data_array['features'] = array(); //["timeserie", "analyze", "geo", "image", "calendar", "custom_view","wordcloud"]
		//$data_array['features'][] = "analyze"; //tab chart
		if ($isGeo) {
			$data_array['features'][] = "geo"; //tab map 
		}
		//$data_array['features'][] = "analyze"; //tab chart 
		//$data_array['features'][] = "timeline"; //tab timeline 
		//$data_array['features'][] = "timeserie"; //unknown tab

		if (count($data_array['fields']) > 0) {
			$colStart = null;
			$colEnd = null;
			$colWordCount = null;
			$colTimeline = null;
			foreach ($data_array['fields'] as $f) {
				foreach ($f["annotations"] as $a) {
					if ($a["name"] == "startDate") {
						$colStart = $f["name"];
					} else if ($a["name"] == "endDate") {
						$colEnd = $f["name"];
					} else if ($a["name"] == "date") {
						$colEnd = $f["name"];
						$colStart = $f["name"];
					} else if ($a["name"] == "wordcount" || $a["name"] == "wordcountNumber") {
						$colWordCount = $f["name"];
					}
					//                    else if($a["name"] == "timeline"){
					//						$colTimeline = $f["name"];
					//					}
				}
				if ($colEnd != null && $colStart != null) {
					//break;
				}
				if ($f["type"] == "file") {
					//$data_array['features'][] = "image";
					$visu['image_title'] = $visu['map_tooltip_title'];
					$visu['image_fields'] = $visu['map_tooltip_fields'];
				}
			}

			if ($colStart != null && $colEnd != null) {
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

			if ($colWordCount != null) {
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
		if ($customView) { // TODO custom_view search

			//$data_array['features'][] = "custom_view";
			$visu["custom_view_title"] = $customView->cv_title;
			$visu["custom_view_slug"] = $customView->cv_name;
			$visu["custom_view_icon"] = $customView->cv_icon;
			$i = $customView->cv_template;
			$html = "";
			foreach ($customView->html as $key => $obj) {
				$customView->html[$key]->cvh_html = str_replace("d4c-chart ", 'd4c-chart d4c-order="' . $obj->cvh_order . '" ', $obj->cvh_html);
			}
			if ($i == 1) {
				if (isset($customView->html[0])) {
					$html = $customView->html[0]->cvh_html;
				}
			} elseif ($i == 2) {
				if (isset($customView->html[0])) {
					$html .= '<div class="col-md-12 col-sm-12"><div class="d4c-box">' . $customView->html[0]->cvh_html . '</div></div>';
				}
				if (isset($customView->html[1])) {
					$html .= '<div class="col-md-12 col-sm-12"><div class="d4c-box">' . $customView->html[1]->cvh_html . '</div></div>';
				}
			} elseif ($i == 3) {
				$html .= '<div class="row">';
				if (isset($customView->html[0])) {
					$html .= '<div class="col-md-12 col-sm-12"><div class="d4c-box">' . $customView->html[0]->cvh_html . '</div></div>';
				}
				if (isset($customView->html[1])) {
					$html .= '<div class="col-md-12 col-sm-12"><div class="d4c-box">' . $customView->html[1]->cvh_html . '</div></div>';
				}
				$html .= '</div>';
				$html .= '<div class="row"><div class="d4c-box">';
				if (isset($customView->html[2])) {
					$html .= $customView->html[2]->cvh_html;
				}
				$html .= '</div></div>';
			} elseif ($i == 4) {
				$html .= '<div class="row">';
				if (isset($customView->html[0])) {
					$html .= '<div class="col-md-12 col-sm-12"><div class="d4c-box">' . $customView->html[0]->cvh_html . '</div></div>';
				}
				if (isset($customView->html[1])) {
					$html .= '<div class="col-md-12 col-sm-12"><div class="d4c-box">' . $customView->html[1]->cvh_html . '</div></div>';
				}
				$html .= '</div>';
				$html .= '<div class="row">';
				if (isset($customView->html[2])) {
					$html .= '<div class="col-md-12 col-sm-12"><div class="d4c-box">' . $customView->html[2]->cvh_html . '</div></div>';
				}
				if (isset($customView->html[3])) {
					$html .= '<div class="col-md-12 col-sm-12"><div class="d4c-box">' . $customView->html[3]->cvh_html . '</div></div>';
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

		if (\Drupal::currentUser()->isAuthenticated()) {
			$idUser = \Drupal::currentUser()->id();
			$isSubscribed = $this->isSubscribed($datasetid, $idUser);
			$data_array['is_subscribed'] = $isSubscribed;
		}

		$data_array['extra_metas']['visualization'] = $visu;

		$data_array['has_records'] = true;
		$data_array['metas'] = $result['result'];

		$data_array["metas"]["domain"] = "";
		$data_array["metas"]["language"] = "fr";
		//$data_array["metas"]["title"]=$result["result"]["name"];
		$desc = str_replace(PHP_EOL, '<br>', $result["result"]["notes"]);
		$data_array["metas"]["description"] = $desc;
		$data_array["metas"]["modified"] = $this->findMostRecentDate(current(array_filter($result["result"]["extras"], function ($f) {
			return $f["key"] == "date_moissonnage_last_modification";
		}))["value"], $result["result"]["metadata_modified"]);
		$data_array["metas"]["visibility"] = "domain";
		$data_array["metas"]["metadata_processed"] = current(array_filter($result["result"]["extras"], function ($f) {
			return $f["key"] == "date_moissonnage_last_modification";
		}))["value"];
		$data_array["metas"]["license"] = $data_array["metas"]["license_title"];
		//$data_array["metas"]["data_processed"]="2018-07-05T12:07:03+00:00";
		$data_array["metas"]["publisher"] = $data_array["metas"]["organization"]["title"];
		// set default producer in metas array
		$data_array['metas']["producer"] = "";
		foreach ($data_array['metas']['extras'] as $value) {
			if ($value["key"] == "theme") {
				$data_array['metas']["theme"] = str_replace(",", ", ", $value["value"]);
			}
			if ($value["key"] == "themes") {
				$data_array['metas']["themes"] = $value["value"];
			}
			//add producer to metas dataset
			if ($value["key"] == "producer") {
				$data_array['metas']["producer"] = $value["value"];
			}
		}

		$dateMoissonnageLastModification = null;
		$dateMoissonnageCreation = null;
		if (is_array($result["result"]["results"][$i]["extras"])) {
			$dateMoissonnageLastModification = current(array_filter($result["result"]["results"][$i]["extras"], function($f){ return $f["key"] == "date_moissonnage_last_modification";}))["value"];
			$dateMoissonnageCreation = current(array_filter($result["result"]["results"][$i]["extras"], function($f){ return $f["key"] == "date_moissonnage_creation";}))["value"];
		}

		$result["result"]["results"][$i]["metadata_imported"] = $result["result"]["results"][$i]["metadata_modified"];
		$result["result"]["results"][$i]["metadata_modified"] = $dateMoissonnageLastModification ?: $result["result"]["results"][$i]["metadata_modified"];
		$result["result"]["results"][$i]["metadata_created"] = $dateMoissonnageCreation ?: $result["result"]["results"][$i]["metadata_created"];

		if (count($data_array["metas"]["tags"]) > 0) {
			$data_array["metas"]["keyword"] = array_column($data_array["metas"]["tags"], "display_name");
		} else {
			$data_array["metas"]["keyword"] = array();
		}


		/*if($resourceCSV != NULL){
			$records_result = $this->getDatastoreRecord_v2("dataset=".$data_array['datasetid']."&rows=1");
			
			$data_array["metas"]["data_visible"] = $data_array["data_visible"];
			$data_array["metas"]["records_count"] = $records_result["nhits"];
		}*/

		if (isset($data_array["extra_metas"]["records_count"])) {
			$data_array["metas"]["records_count"] = $data_array["extra_metas"]["records_count"];
		}

		return $data_array;
	}

	public function findMostRecentDate($firstDateStr, $lastDateStr)
	{
		if ($firstDateStr) {
			$firstDate = strtotime($firstDateStr);
			$lastDate = strtotime($lastDateStr);
			if ($firstDate > $lastDate) {
				return $firstDateStr;
			}
		}

		return $lastDateStr;
	}

	public function callPackageShow2($datasetid, $params)
	{



		$res = $this->getPackageShow2($datasetid, $params);


		echo json_encode($res);
		$response = new Response();
		//$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	public function callDatastoreApiGeoClusterOld($params, $nbOfTries = 0)
	{

		$params = $this->retrieveParameters($params);

		$patternRefine = '/refine./i';
		$patternDisj = '/disjunctive./i';
		/*$patternBbox = '/geofilter.bbox/i';*/
		$patternSerie = '/y.serie/i';
		$filters_init = array();
		$fieldId = "id";
		$fieldCoordinates = "";
		$reqQfilter = "";
		$ySeries = array();

		//echo $params . "\r\n";
		$query_params = $this->proper_parse_str($params);


		foreach ($query_params as $key => $value) {
			if (preg_match($patternRefine, $key)) {
				$filters_init[preg_replace($patternRefine, "", $key)] =  $value;

				unset($query_params[$key]);
				//echo preg_replace($pattern,"",$key);
			}
			if (preg_match($patternDisj, $key)) {
				unset($query_params[$key]);
				$disj[] = preg_replace($patternDisj, "", $key);
			}

			if ($key == "q") {
				$reqQfilter = $this->constructReqQToSQL($value);
				//$pattern = '/and (\w+) /i';
				//preg_match($pattern,$reqQfilter,$qField); 
			}
			if (preg_match($patternSerie, $key)) {
				$var = explode('.', $key);
				$nom = $var[1];
				$app = $var[2];

				if (array_key_exists($nom, $ySeries)) {
					$ySeries[$nom][$app] = $value;
				} else {
					$ySeries[$nom]["name"] = $nom;
					$ySeries[$nom][$app] = $value;
				}
			}
		}


		$clusterDistance = 50;
		if (array_key_exists("clusterdistance", $query_params)) {
			$clusterDistance = $query_params["clusterdistance"];
		}
		unset($query_params["clusterdistance"]);

		$clusterPrec = 5;
		if (array_key_exists("clusterprecision", $query_params)) {
			$clusterPrec = $query_params["clusterprecision"] - 2;
		}
		unset($query_params["clusterprecision"]);

		$return_polygons = false;
		if (array_key_exists("return_polygons", $query_params)) {
			$clusterDistance = $query_params["return_polygons"];
		}
		unset($query_params["return_polygons"]);


		$geofilter_bbox = explode(",", $query_params["geofilter.bbox"]);
		unset($query_params["geofilter.bbox"]);
		unset($query_params["q"]);
		$datasetId = $query_params["dataset"];
		//		unset($query_params["dataset"]);

		if (array_key_exists("geofilter.distance", $query_params) || array_key_exists("geofilter.polygon", $query_params)) {

			$callUrl =  $this->urlCkan . "api/action/package_show?id=" . $datasetId;
			$curl = curl_init($callUrl);
			curl_setopt_array($curl, $this->getStoreOptions());
			$package = curl_exec($curl);
			//echo $package . "\r\n";
			curl_close($curl);
			$package = json_decode($package, true);
			foreach ($package['result']['resources'] as $value) {
				if (($value['format'] == 'CSV' || $value['format'] == 'XLS' || $value['format'] == 'XLSX') && $value["datastore_active"] == true) {
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

			$geojson = $this->getRecordsDownload($params . "&format=geojson&resource_id=" . $resourceCSV);
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

			$dataset = curl_exec($curl);
			curl_close($curl);

			$dataset = json_decode($dataset, true);
			$data_array = array();
			$clusters = array();
			$allFeatures = json_decode($geojson, true)["features"];
			foreach ($dataset["features"] as $value) {
				$c = array();
				$c["clusters"] = array();
				$c["cluster_center"] = array_reverse($value["geometry"]["coordinates"]);
				$c["count"] = $value["properties"]["point_count"];
				if ($c["count"] == NULL) $c["count"] = 1;

				if (count($ySeries) > 0) {
					$c["series"] = array();
					$serFilteredValues = array();
					if ($c["count"] > 1) {
						$ids = array_flip($value["properties"]["ids"]);
						foreach ($allFeatures as $f) {
							if (isset($ids[$f["properties"]["_id"]])) {
								$serFilteredValues[] = $f["properties"];
							}
						}
					} else {
						$serFilteredValues[] = $value["properties"];
					}

					foreach ($ySeries as $y) {
						$colValues = array_column($serFilteredValues, $y["expr"]);
						$func = $y["func"];
						switch ($func) {
							case "AVG":
								$f = 0;
								if (count($colValues)) {
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
								if (count($colValues) > 1) {
									$rr = function ($x, $mean) {
										return pow($x - $mean, 2);
									};
									$f = sqrt(array_sum(array_map($rr, $colValues, array_fill(0, count($colValues), (array_sum($colValues) / count($colValues))))) / (count($colValues) - 1));
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
			foreach ($ySeries as $y) {
				$data_array["series"][$y["name"]] = array();
				$values = array_column(array_column($clusters, 'series'), $y["name"]);
				$data_array["series"][$y["name"]]["min"] = min($values);
				$data_array["series"][$y["name"]]["max"] = max($values);
			}
			$data_array['count'] = array();
			$counts = array_column($clusters, 'count');
			$data_array['count']['min'] = min($counts);
			$data_array['count']['max'] = max($counts);


			echo json_encode($data_array);

			$response = new Response();
			//		$response->setContent(json_encode($result));
			$response->headers->set('Content-Type', 'application/json');
			return $response;
		}

		$query = "idRes=" . $datasetId . "&zoom=" . $clusterPrec . "&minLat=" . $geofilter_bbox[0] . "&minLong=" . $geofilter_bbox[1] . "&maxLat=" . $geofilter_bbox[2] . "&maxLong=" . $geofilter_bbox[3];

		$callUrl = $this->config->cluster->url . "cluster?" . $query;


		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getSimpleGetOptions());
		$dataset = curl_exec($curl);
		curl_close($curl);

		$dataset = json_decode($dataset, true);
		if (empty($dataset["features"])) {
			if ($clusterPrec < 20 && $nbOfTries < 40) {
				$params = str_replace('clusterprecision=' . ($clusterPrec + 2), 'clusterprecision=' . ($clusterPrec + 4), $params);
				return $this->callDatastoreApiGeoClusterOld($params, $nbOfTries + 1);
			}
		}

		//recup resourceCSV
		$resourceCSV;
		$maxCount;
		$array_filter_id;
		$result2;

		if (count($filters_init) > 0 || $reqQfilter != "" || count($ySeries) > 0) {
			$callUrl =  $this->urlCkan . "api/action/package_show?id=" . $datasetId;
			$curl = curl_init($callUrl);
			curl_setopt_array($curl, $this->getStoreOptions());
			$package = curl_exec($curl);
			//echo $package . "\r\n";
			curl_close($curl);
			$package = json_decode($package, true);
			foreach ($package['result']['resources'] as $value) {
				if (($value['format'] == 'CSV' || $value['format'] == 'XLS' || $value['format'] == 'XLSX') && $value["datastore_active"] == true) {
					$resourceCSV = $value['id'];
					break;
				}
			}
			unset($query_params['dataset']);
			$query_params['resource_id'] = $resourceCSV;

			$fields = $this->getAllFields($query_params['resource_id']);
			$fieldId = "_id";
			foreach ($fields as $value) {
				if ($value['type'] == "geo_point_2d") {
					$fieldCoordinates = $value['name'];
				}
			}

			//series
			$series = "";
			if (count($ySeries) > 0) {
				foreach ($ySeries as $y) {
					if ($y["expr"] == NULL) {
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
					$f = "string_agg(" . $y["expr"] . "::text,',') as " . $y["name"] . ",";
					$series .= $f;
				}
				$series = ", " . substr($series, 0, -1);
			}


			//where
			$where = "";
			if (!empty($filters_init)) {
				$where = " where ";
				foreach ($filters_init as $key => $value) {
					if (is_numeric($value) && $key != "insee_com" && $key != "code_insee" && $key != "sta_nm_dpt") {
						$where .= $key . "=" . $value . " and ";
					} else if (is_array($value)) {
						if ($key != "insee_com" && $key != "code_insee") {
							$value = implode(',', array_map(array($this, 'quotesArrayValue'), str_replace("'", "''", $value)));
							$value = urldecode($value);
							$where .= $key . " in (" . $value . ") and ";
						}
						else {
							$where .= $key . " in (" . implode(',', array_map(array($this, 'quotesStringArrayValue'), $value)) . ") and ";
						}
					} else {
						$value = urldecode($value);
						$where .= $key . "='" . str_replace("'", "''", $value) . "' and ";
					}
				}
				$where = substr($where, 0, strlen($where) - 4);
				if ($reqQfilter != "") {
					$where .= $reqQfilter;
				}
			} else if ($reqQfilter != "") {
				$where = " where " . substr($reqQfilter, 5);
			}

			$req = array();
			$sql = "Select string_agg(" . $fieldId . "::text,',') as agg" . $series . " from \"" . $query_params['resource_id'] . "\"" . $where;
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
			$array_filter_id = explode(",", $result2["result"]["records"][0]["agg"]); //echo count($array_filter_id). "\r\n";

		}


		$data_array = array();

		$clusters = array();
		foreach ($dataset["features"] as $value) {
			$c = array();
			$c["clusters"] = array();
			$c["cluster_center"] = array_reverse($value["geometry"]["coordinates"]);
			//$c["cluster_center"] = $value["geometry"]["coordinates"];
			$c["count"] = $value["properties"]["point_count"]; //echo $c["count"]. "\r\n";
			if ($c["count"] == NULL) $c["count"] = 1;

			$ids = array();
			foreach ($value["properties"]["ids"] as $v) {
				$ids[] = $v;
			}

			if (count($filters_init) > 0 || $reqQfilter != "" || count($ySeries) > 0) {

				//$array = array_intersect($array_filter_id, $ids);
				$index = array_flip($array_filter_id);
				$second = array_flip($ids);

				$x = array_intersect_key($index, $second);
				$array = array_flip($x);

				//$array_filter_id = array_diff($array_filter_id, $ids);							  
				$c["count"] = count($array); //echo $c["count"]. "\r\n";
				if ($c["count"] == 1) {
					$id = array_values($array)[0];
					$req = array();
					$sql = "Select " . $fieldCoordinates . " as coord from \"" . $resourceCSV . "\" where " . $fieldId . "=" . $id;
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
					$record = json_decode($record, true);
					//echo $record["result"]["records"][0]["coord"];						
					$c["cluster_center"] = array_map('floatval', explode(',', $record["result"]["records"][0]["coord"]));
				}

				if (count($ySeries) > 0) {
					$c["series"] = array();
					foreach ($ySeries as $y) {
						$serAllValues = explode(",", $result2["result"]["records"][0][$y["name"]]);
						$serFilteredValues = array_intersect_key($serAllValues, $array);
						$func = $y["func"];
						switch ($func) {
							case "AVG":
								$f = 0;
								if (count($serFilteredValues)) {
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
								$rr = function ($x, $mean) {
									return pow($x - $mean, 2);
								};
								$f = sqrt(array_sum(array_map($rr, $serFilteredValues, array_fill(0, count($serFilteredValues), (array_sum($serFilteredValues) / count($serFilteredValues))))) / (count($serFilteredValues) - 1));
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
			if ($c["count"] != 0) {
				$clusters[] = $c;
			}
		}
		//echo count($array_filter_id);asort($array_filter_id);print_r($array_filter_id);
		$data_array['clusters'] = $clusters;

		$data_array['clusterprecision'] = $clusterPrec;
		$data_array['series'] = array();
		foreach ($ySeries as $y) {
			$data_array["series"][$y["name"]] = array();
			$values = array_column(array_column($clusters, 'series'), $y["name"]);
			$data_array["series"][$y["name"]]["min"] = min($values);
			$data_array["series"][$y["name"]]["max"] = max($values);
		}
		$data_array['count'] = array();
		$counts = array_column($clusters, 'count');
		$data_array['count']['min'] = min($counts);
		$data_array['count']['max'] = max($counts);


		echo json_encode($data_array);
		$response = new Response();
		//		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}


	public function callDatastoreApiGeoPreview($params)
	{

		$params = $this->retrieveParameters($params);
		$query_params = $this->proper_parse_str($params);

		$fields = $this->getAllFields($query_params['resource_id'], true);

		$fieldGeometries = "";
		$fieldColor = "";
		
		$fieldsMapDisplay = array();
		$fieldsMapDisplayQuery = "";

		foreach ($fields as $value) {
			if ($value['type'] == "geo_shape") $fieldGeometries = $value['name'];
			if ($value['name'] == "route_color") $fieldColor = $value['name'] . ", ";
			
			$description = $value['description'];
			if (preg_match("/<!--.*map_display.*-->/i", $description)) {
				$field = $value['name'];

				$fieldsMapDisplay[] = $field;
				$fieldsMapDisplayQuery .= $field . ", " ;
			}
		}

		if (array_key_exists("rows", $query_params)) {
			$limit = " limit " . $query_params['rows'];
		}

		$where = $this->getSQLWhereRecordsDownload($params, $query_params['resource_id']);
		$req = array();
		// $sql = "Select cast(".$fieldGeometries."::json->'type' as text) as geo from \"" . $query_params['resource_id'] . "\"" . $where . $limit;
		$sql = "Select  " . $fieldsMapDisplayQuery . $fieldColor . $fieldGeometries . " as geo from \"" . $query_params['resource_id'] . "\"" . $where . $limit;
		$req['sql'] = $sql;

		// Logger::logMessage("Geopreview query : " . $req['sql']);

		$url2 = http_build_query($req);
		$callUrl =  $this->urlCkan . "api/action/datastore_search_sql?" . $url2;
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);

		curl_close($curl);
		$result = json_decode($result, true);


		$data_array = array();
		foreach ($result["result"]["records"] as $value) {
			$res = array();
			$res['geo_digest'] = md5($value["geo"]); //3566411980376893035
			$res['route_color'] = $value["route_color"];

			$mapDisplay = array();
			foreach ($fieldsMapDisplay as $field) {
				$mapDisplay[] = $value[$field];
			}
			$res['map_display'] = $mapDisplay;

			try {
				// Logger::logMessage("Found geo  : " . $value["geo"]);
				$res['geometry'] = json_decode($value["geo"], true);
			} catch (Exception $e) {
				// Logger::logMessage("Found geo with cast error : " . $value["geo"]);
				$res['geometry'] = $value["geo"];
			}

			$data_array[] = $res;
		}

		echo json_encode($data_array);
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	public function getMapTooltip($hasCustomHtml, $html) {
		$res = "<div class=\"tooltipcustom\">";
		if ($hasCustomHtml) {
			$res = $html;
		} else {
			$res = "<h2 class=\"d4cwidget-map-tooltip__header\" ng-show=\"!!getTitle(record)\"><span ng-bind=\"getTitle(record)\"></span></h2>" .
				"<ul style=\"display: block; list-style-type: none; color: #2c3f56; padding:0; margin:0;\">" .
				"<li  ng-repeat=\"field in context.dataset.extra_metas.visualization.map_tooltip_fields\"><strong>{{field}}</strong> : {{record.fields[field]}}</li>" .
				"</ul>";
		}
		$res .= "<div  ng-repeat=\"report in context.dataset.extra_metas.visualization.reports\">" .
			"<strong>Rapport de d\u00e9tail</strong> : <a ng-href=\"{{getReportUrl(report[0], record)}}\" target=\"_blank\">Voir</a>" .
			"</div>";
		$res .= "</div>";
		//return utf8_encode ("<div class=\"tooltipcustom\"><h2 class=\"d4cwidget-map-tooltip__header\" ng-show=\"!!getTitle(record)\"><span ng-bind=\"getTitle(record)\"></span></h2><ul style=\"display: block; list-style-type: none; color: #2c3f56; padding:0; margin:0;\"><li  ng-repeat=\"field in context.dataset.extra_metas.visualization.map_tooltip_fields\">".			"<strong>{{field}}</strong> : {{record.fields[field]}}</li></ul><div  ng-repeat=\"report in context.dataset.extra_metas.visualization.reports\">".			"<strong>Rapport de d\u00e9tail</strong> : <a ng-href=\"{{getReportUrl(report[0], record)}}\" target=\"_blank\">Voir</a></div></div>");
		return utf8_encode($res);
	}

	public function getPackageShow2_v2($params)
	{
		$query_params = $this->proper_parse_str($params);

		unset($query_params["facet"]);
		$query_params["id"] = $query_params["DATASETID"];
		unset($query_params["DATASETID"]);
		$resourceId = $query_params["resource_id"];
		unset($query_params["resource_id"]);

		$url2 = http_build_query($query_params);

		$callUrl =  $this->urlCkan . "api/action/package_show?" . $url2; //temporaire


		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		//echo $result . "\r\n";
		$result = json_decode($result, true);

		$data_array = array();
		$data_array["datasetid"] = $result["result"]["id"];
		$data_array["metas"]["domain"] = "anfr";
		$data_array["metas"]["language"] = "fr";
		$data_array["metas"]["title"] = $result["result"]["name"];

		$data_array["metas"]["modified"] = $result["result"]["metadata_modified"];
		$data_array["metas"]["visibility"] = "domain";
		$data_array["metas"]["metadata_processed"] = $result["result"]["metadata_created"];
		//$data_array["metas"]["data_processed"]="2018-07-05T12:07:03+00:00";
		$data_array["attachments"] = "";
		$data_array["alternative_exports"] = "";
		$data_array["features"] = array("timeserie", "analyse", "geo");
		$resourceCSV = null;

		foreach ($result['result']['resources'] as $value) {
			if (isset($resourceId) && $resourceId != "") {
				if ($resourceId == $value['id']) {
					$resourceCSV = $value;
					break;
				}
			}
			else if (($value['format'] == 'CSV' || $value['format'] == 'XLS' || $value['format'] == 'XLSX') && $value["datastore_active"] == true) {
				$resourceCSV = $value;
				break;
			}
		}
		if ($resourceCSV != NULL) {
			$data_array["has_records"] = true;
			$data_array["data_visible"] = $resourceCSV['datastore_active'];
			$data_array["metas"]["data_visible"] = $data_array["data_visible"];
			$data_array["metas"]["records_count"] = $resourceCSV["size"];
			$fields = $this->getAllFields($resourceCSV['id'], TRUE);
			$data_array["fields"] = $fields;
		} else {
			$data_array["has_records"] = false;
		}


		return $data_array;
	}

	public function callPackageShow2_v2($params)
	{
		$res = $this->getPackageShow2_v2($params);
		echo json_encode($res);

		$response = new Response();
		//$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	public function callPackageSearch_v2($params)
	{
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


		foreach ($query_params as $key => $value) {
			if (!empty($key)) {
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
		foreach ($query_params as $key => $value) {
			if ($key == "refine.features") {
				if (is_array($query_params["refine.features"])) {
					$refineFeatures = $query_params["refine.features"];
				} else {
					$refineFeatures = array();
					$refineFeatures[] = $query_params["refine.features"];
				}
				$filters[preg_replace($patternRefine, "", $key)] =  "(*" . implode("* OR *", $refineFeatures) . "*)";
				unset($query_params[$key]);
			} else if ($key == "refine.themes") {
				if (is_array($query_params["refine.themes"])) {
					$refineThemes = $query_params["refine.themes"];
				} else {
					$refineThemes = array();
					$refineThemes[] = $query_params["refine.themes"];
				}
				$filters[preg_replace($patternRefine, "", $key)] =  "(*" . implode("* OR *", $refineThemes) . "*)";
				unset($query_params[$key]);
			} else if (preg_match($patternRefine, $key)) {
				$filters[preg_replace($patternRefine, "", $key)] =  $value;
				unset($query_params[$key]);
			} else  if (preg_match($patternExclude, $key)) {
				$filters["-" . preg_replace($patternExclude, "", $key)] =  $value;
				unset($query_params[$key]);
			}
		}
		if (!empty($filters)) {
			$reqQ = "";
			foreach ($filters as $key => $value) {
				if ($key != "features" && $key != "themes") {
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
		if (array_key_exists("facet", $query_params) || array_key_exists("fields", $query_params)) {
			$fac = array();
			if (array_key_exists("facet", $query_params)) {
				if (is_array($query_params["facet"])) {
					$fac = $query_params["facet"];
				} else {
					$fac = array();
					$fac[] = $query_params["facet"];
				}
			}
			if (array_key_exists("fields", $query_params)) {
				$fields = explode(",", $query_params["fields"]);
				$fac = array_merge($fac, $fields);
			}
			$reqFacet = "[";
			foreach ($fac as $f) {
				$reqFacet .= '"' . $f . '",';
			}
			$reqFacet = substr($reqFacet, 0, -1);
			$reqFacet .= "]";
			unset($query_params["facet"]);
			unset($query_params["fields"]);
		} else {

			$fac = array();
			foreach ($data_array["parameters"] as $key => $value) {
				if (preg_match($patternRefine, $key) && $key != "refine.features" && $key != "refine.themes") {
					$fac[] = preg_replace($patternRefine, "", $key);
				}
			}
			if (count($fac) > 0) {
				$reqFacet = "[";
				foreach ($fac as $f) {
					$reqFacet .= '"' . $f . '",';
				}
				$reqFacet = substr($reqFacet, 0, -1);
				$reqFacet .= "]";
			}
		}
		if ($reqFacet != null) {
			$query_params["facet.field"] = $reqFacet;
		}

		//echo json_encode($query_params);
		$url2 = http_build_query($query_params);


		//$callUrl =  $this->urlCkan . "api/action/package_search";
		$callUrl =  $this->urlCkan . "api/action/package_search?" . $url2;
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
		$result = json_decode($result, true);
		foreach ($result["result"]["results"] as $value) {
			if ($rows != null && count($datasets) >= $rows) {
				break;
			}

			$dataset = array();
			$isGeo = false;

			$dataset["datasetid"] = $value["id"];
			$dataset["metas"]["domain"] = "anfr";
			$dataset["metas"]["language"] = "fr";
			$dataset["metas"]["title"] = $value["name"];

			$dataset["metas"]["modified"] = $value["metadata_modified"];
			$dataset["metas"]["visibility"] = "domain";
			$dataset["metas"]["metadata_processed"] = $value["metadata_created"];
			$dataset["attachments"] = "";
			$dataset["alternative_exports"] = "";

			$resourceCSV;

			foreach ($value['resources'] as $v) {
				if (($v['format'] == 'CSV' || $v['format'] == 'XLS' || $v['format'] == 'XLSX') && $v["datastore_active"] == true) {
					$resourceCSV = $v;
				}
				if ($v['format'] == 'GeoJSON') {
					$isGeo = true;
				}
			}
			if ($resourceCSV != NULL) {
				$dataset["has_records"] = true;
				$dataset["data_visible"] = $resourceCSV['datastore_active'];
				$dataset["metas"]["data_visible"] = $dataset["data_visible"];
				//$dataset["metas"]["records_count"] = $resourceCSV["size"];
				$fields = $this->getAllFields($resourceCSV['id']);
				$dataset["fields"] = $fields;
			} else {
				$dataset["has_records"] = false;
			}

			$dataset['features'] = array(); //["timeserie", "analyze", "geo", "image", "calendar", "timeserie", "custom_view"]
			/*if($isGeo){
				$dataset['features'][] = "geo"; //tab map 
			}
			$dataset['features'][] = "analyze"; //tab chart 
			//$dataset['features'][] = "timeserie"; //unknown tab
			//echo json_encode($dataset['features']);*/

			foreach ($value['extras'] as $extra) {
				if ($extra["key"] == "features") {
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


			$datasets[] = $dataset;

			//echo $datasets;
		}
		$data_array["datasets"] = $datasets;

		$hasFacetThemes = array_key_exists("themes", $result["result"]["facets"]);
		if ($hasFacetThemes) {
			$themes = array();
			foreach ($result["result"]["facets"]["themes"] as $key => $count) {
				for ($i = 0; $i < $count; $i++) {
					$themes = array_merge($themes, json_decode($key, true));
				}
			}

			$result["result"]["facets"]["themes"] = array_count_values($themes);

			$result["result"]["search_facets"]["themes"]["items"] = array();
			foreach ($result["result"]["facets"]["themes"] as $theme => $c) {
				$result["result"]["search_facets"]["themes"]["items"][] = array(
					"count" => $c,
					"display_name" => $theme,
					"name" => $theme
				);
			}
		}

		foreach ($result["result"]["facets"] as $key => $value) {
			if (!empty((array) $value)) {
				$facet = array();
				$facet["name"] = $key;
				$facet["facets"] = array();
				foreach ($value as $val => $count) {
					$facetName = $val;
					if ($key == "themes") {
						$facetName = $this->getThemeLabel($val);

						if (isset($refineThemes) && is_array($refineThemes) && !in_array($val, $refineThemes)) {
							continue;
						}
					}

					$item = array();
					$item["name"] = $facetName;
					$item["path"] = $val;
					$item["count"] = $count;
					if (array_key_exists("refine." . $key, $data_array["parameters"])) {
						$filter = $data_array["parameters"]["refine." . $key];
						if ((is_array($filter) && in_array($val, $filter)) || (!is_array($filter) && $val = $filter)) {
							$item["state"] = "refined";
						} else {
							$item["state"] = "displayed";
						}
					} else {
						$item["state"] = "displayed";
					}

					$facet["facets"][] = $item;
				}

				usort($facet["facets"], function ($a, $b) {
					$key = "count";
					return  $b[$key] - $a[$key];
				});

				$facet_groups[] = $facet;
			}
		}

		$data_array["facet_groups"] = $facet_groups;
		$data_array["nhits"] = $result["result"]["count"]; //count($datasets);
		echo json_encode($data_array);
		$response = new Response();
		//		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	public function callDatastoreApi_v2($params)
	{
		$res = $this->getDatastoreRecord_v2($params);
		echo json_encode($res);
		$response = new Response();
		//		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	public function getDatastoreRecord_v2($params)
	{

		//dataset q lang rows start sort facet refine exclude geofilter.distance geofilter.polygon timezone
		$patternRefine = '/refine./i';
		$patternExclude = '/exclude./i';
		$patternDisj = '/disjunctive./i';
		$patternDistance = '/geofilter.distance/i';
		$filters_init = array();
		$fieldId = "_id";
		$fieldCoordinates = '';
		$fieldGeometries = '';
		$reqQfilter;

		$params = $this->retrieveParameters($params);

		$query_params = $this->proper_parse_str($params);
		$data_array = array();
		foreach ($query_params as $key => $value) {
			if (!empty($key)) {
				$data_array["parameters"][$key] =  $value;
			}
		}


		unset($query_params["lang"]);
		//unset($query_params["geofilter.distance"]); //TODO
		unset($query_params["geofilter.polygon"]); //TODO
		unset($query_params["timezone"]);
		unset($query_params["geo_simplify"]);
		unset($query_params["geo_simplify_zoom"]);


		$datasetId = "";
		if (!array_key_exists("resource_id", $query_params) && array_key_exists("dataset", $query_params)) {
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
				if (($value['format'] == 'CSV' || $value['format'] == 'XLS' || $value['format'] == 'XLSX') && $value["datastore_active"] == true) {
					$resourceCSV = $value['id'];
					break;
				}
			}
			unset($query_params['dataset']);
			$query_params['resource_id'] = $resourceCSV;
		}

		$fields = $this->getAllFields($query_params['resource_id'], TRUE);
		foreach ($fields as $value) {
			if ($value['type'] == "geo_point_2d") $fieldCoordinates = $value['name'];
		}

		foreach ($query_params as $key => $value) {
			if (preg_match($patternRefine, $key)) {

				$value = str_replace('_plussign_', '+', $value);

				$filters_init[preg_replace($patternRefine, "", $key)] =  $value;
				unset($query_params[$key]);
			}
			if (preg_match($patternDisj, $key)) {
				unset($query_params[$key]);
			}
			/*if (preg_match($patternBbox,$key)){
		    	unset($query_params[$key]);
		    	$filters_init[$key] =  $value;
		    }*/
			if (preg_match($patternExclude, $key)) {
				unset($query_params[$key]);
			}
			if (preg_match($patternDistance, $key)) {

				if (count(explode(',', $query_params[$key])) == 3) { //distance + meters
					$args = explode(',', $query_params[$key]);
					$filters_init["geofilter.bbox"] =  $this->getBbox($args[0], $args[1], $args[2]);
				} else { //distance precision
					$filters_init[$key] =  $value;
				}
				unset($query_params[$key]);
			}
			if ($key == "rows") {
				$query_params['limit'] = $query_params['rows'];
				unset($query_params['rows']);
			}
			if ($key == "start") {
				$query_params['offset'] = $value;
				unset($query_params['start']);
				$query_params['limit'] = $query_params['limit'] /*+ $query_params['offset']*/;
			}
			//$query_params['sort'] TODO 
			if (array_key_exists('facet', $query_params)) {
				$query_params['fields'] = implode(",", $query_params['facet']);
			}
			if ($key == "q") {
				$reqQfilter = $this->constructReqQToSQL($value);
			}
			if ($key == "id" || $key == "basemap" || $key == "location" || $key == "datasetcard" || $key == "static" || $key == "scrollWheelZoom") {
				unset($query_params[$key]);
			}
			/*if($key == "use_labels_for_header"){
			unset($query_params[$key]);
		    }*/
			if ($key == "recordid") {
				$filters_init[$fieldId] = $value;
				unset($query_params["recordid"]);
			}
		}

		$where = "";
		$limit  = "";
		$offset  = "";
		$orderby = "";

		if (!empty($filters_init)) {
			$where = " where ";
			foreach ($filters_init as $key => $value) {
				if ($key == "geofilter.bbox") {
					$bbox = explode(',', $value);
					$minlat = $bbox[0];
					$minlong = $bbox[1];
					$maxlat = $bbox[2];
					$maxlong = $bbox[3];
					//$where .= "CAST(split_part(".$fieldCoordinates.",',',1)AS FLOAT) between " . $minlat . " and " . $maxlat . " and ";
					//$where .= "CAST(split_part(".$fieldCoordinates.",',',2)AS FLOAT) between " . $minlong . " and " . $maxlong . " and ";
					$where .= "box(point(" . $minlat . "," . $minlong . "),point(" . $maxlat . "," . $maxlong . ")) @> point(" . $fieldCoordinates . ") and ";
					$where .= $fieldCoordinates . " not in ('', ',') and ";
				} else if ($key == "geofilter.distance") {
					$coord = explode(',', $value);
					$lat = $coord[0];
					$long = $coord[1];
					//$where .= "CAST(split_part(".$fieldCoordinates.",',',1)AS FLOAT) = " . $lat . " and ";
					//$where .= "CAST(split_part(".$fieldCoordinates.",',',2)AS FLOAT) = " . $long . " and ";
					$where .= "point(" . $lat . "," . $long . ") ~= point(" . $fieldCoordinates . ") and ";
					$where .= $fieldCoordinates . " not in ('', ',') and ";
				}/* else if($key == "geo_digest"){
					$where .= "md5(".$fieldGeometries.") = '". $value . "' and ";
				}*/ else {
					$ftype = null;
					foreach ($fields as $field) {
						if ($field["name"] == $key) {
							$ftype = $field["type"];
							break;
						}
					}
					if (($ftype != null && $ftype == "double") || ($ftype == null && is_numeric($value) && $key != "insee_com" && $key != "code_insee")) {
						$where .= $key . "=" . $value . " and ";
					} else if (is_array($value)) {
						if ($key != "insee_com" && $key != "code_insee" && $key != "sta_nm_dpt") {
							$value = implode(',', array_map(array($this, 'quotesArrayValue'), str_replace("'", "''", $value)));
							$value = urldecode($value);
							$where .= $key . " in (" . $value . ") and ";
						}
						else {
							$where .= $key . " in (" . implode(',', array_map(array($this, 'quotesStringArrayValue'), $value)) . ") and ";
						}
					} else {
						$value = urldecode($value);
						$where .= $key . "='" . str_replace("'", "''", $value) . "' and ";
					}
				}
			}
			$where = substr($where, 0, strlen($where) - 4);

			if ($reqQfilter != NULL) {
				$where .= $reqQfilter;
			}
		} else if ($reqQfilter != NULL) {
			$where = " where " . substr($reqQfilter, 5);
		}

		if (array_key_exists("limit", $query_params)) {
			$limit = " limit " . $query_params['limit'];
		} else {
			$limit = " limit 100"; //par defaut
		}

		if (array_key_exists("offset", $query_params)) {
			$offset = " offset " . $query_params['offset'];
		}

		if ((is_array($query_params["sort"]) && count($query_params["sort"]) > 0) || $query_params["sort"] != "") {
			$orderby = " order by ";
			foreach (explode(',', $query_params["sort"]) as $sort) {
				if (substr($sort, 0, 1) == "-") {
					$orderby .= substr($sort, 1) . " DESC,";
				} else {
					$orderby .= $sort . " ASC,";
				}
			}
			$orderby = substr($orderby, 0, -1);
		}

		$req = array();
		// if the $query_params['fields'] field exists, return only the values ​​of these fields from sql request
		if ($query_params['fields'] != null) {
			$sql = "Select " . $query_params['fields'] . ", count(*) OVER() AS total_count from \"" . $query_params['resource_id'] . "\"" . $where . $orderby . $limit . $offset;
		} else {
			$sql = "Select *, count(*) OVER() AS total_count from \"" . $query_params['resource_id'] . "\"" . $where . $orderby . $limit . $offset;
		}
		$req['sql'] = $sql;

		// Logger::logMessage("TRM - SQL getDatastoreRecord_v2 : " . $sql);

		//echo $sql;
		$url2 = http_build_query($req);
		$callUrl =  $this->urlCkan . "api/action/datastore_search_sql?" . $url2;

		// Logger::logMessage("TRM - SQL getDatastoreRecord_v2 : " . $callUrl);

		//echo $callUrl . "\r\n";
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		//echo $result . "\r\n";

		$result = json_decode($result, true);


		//$data_array["nhits"] = $result["result"]["total"];
		$colFile = null;
		foreach ($fields as $f) {
			if ($f["type"] == "file") {
				$colFile = $f["name"];
			}
		}
		$records = array();
		foreach ($result["result"]["records"] as $value) {

			$rec;
			$rec["datasetid"] = $datasetId;
			//$rec["recordid"]=$value["_id"];
			$rec["recordid"] = $value[$fieldId];
			foreach ($value as $k => $v) {
				$rec["fields"][$k] =  $v;
				/*if(preg_match("/id|num|code|siren/i",$k)){
					$rec["recordid"] = $value[$k];				
				}*/
				if ($colFile != null && $colFile == $k) {
					$rec["fields"][$k] = array();
					$rec["fields"][$k]["url"] = $v;
					if (strrpos($v, "/")) {
						$rec["fields"][$k]["filename"] = substr($v, strrpos($v, "/") + 1);
					}

					$size = getimagesize($v);
					$rec["fields"][$k]["width"] = $size[0];
					$rec["fields"][$k]["height"] = $size[1];
				}
			}

			$records[] = $rec;
		}
		$data_array["records"] = $records;
		//$data_array["nhits"] = count($records);
		if (count($records) == 0) {
			$data_array["nhits"] = 0;
		} else {
			$data_array["nhits"] = $result["result"]["records"][0]["total_count"];
		}
		$data_array["status"] = $result["success"] == true ? "success" : "error";
		return $data_array;
	}

	public function getDatastoreRecord_v2OLD($params)
	{
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
		$datasetId = "";
		if (!array_key_exists("resource_id", $query_params) && array_key_exists("dataset", $query_params)) {
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
				if (($value['format'] == 'CSV' || $value['format'] == 'XLS' || $value['format'] == 'XLSX') && $value["datastore_active"] == true) {
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

		if (array_key_exists('rows', $query_params)) {
			$query_params['limit'] = $query_params['rows'];
			unset($query_params['rows']);
		}
		if (array_key_exists('start', $query_params)) {
			$query_params['offset'] = $query_params['start'];
			unset($query_params['start']);
			$query_params['limit'] = $query_params['limit'] + $query_params['offset'];
		}
		//$query_params['sort'] TODO 
		if (array_key_exists('facet', $query_params)) {
			$query_params['fields'] = implode(",", $query_params['facet']);
		}


		$filters_init = array();

		foreach ($query_params as $key => $value) {
			if (preg_match($patternRefine, $key)) {
				$filters_init[preg_replace($patternRefine, "", $key)] =  $value;
				unset($query_params[$key]);
			}
			if (preg_match($patternExclude, $key)) {
				unset($query_params[$key]);
			}
			if (preg_match($patternDisj, $key)) {
				unset($query_params[$key]);
			}
			if ($key == "id" || $key == "basemap" || $key == "location" || $key == "datasetcard" || $key == "static" || $key == "scrollWheelZoom") {
				unset($query_params[$key]);
			}
		}
		if (!empty($filters_init)) {
			$query_params['filters'] = json_encode($filters_init);
		}


		$url2 = http_build_query($query_params);
		$callUrl =  $this->urlCkan . "api/action/datastore_search?" . $url2;

		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		//echo $result . "\r\n";

		$result = json_decode($result, true);
		$data_array = array();

		$data_array["nhits"] = $result["result"]["total"];
		foreach ($init_params as $key => $value) {
			if (!empty($key)) {
				$data_array["parameters"][$key] =  $value;
			}
		}
		$records = array();

		foreach ($result["result"]["records"] as $value) {
			$rec;
			$rec["datasetid"] = $datasetId;
			//$rec["recordid"]=$value["_id"];
			$rec["recordid"] = "";
			foreach ($value as $k => $v) {
				$rec["fields"][$k] =  $v;
				if (preg_match("/id|num|code|siren/i", $k)) {
					$rec["recordid"] = $value[$k];
				}
			}

			$records[] = $rec;
		}
		$data_array["records"] = $records;

		return $data_array;
	}

	public function metaBasic()
	{
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
		{"widget": null, "name": "producer", "uri": null, "search": true, "label": "Producer", "allow_empty": true, "type": "text"}, 
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

	public function metaInterop()
	{
		//dataset q lang rows start sort facet refine exclude geofilter.distance geofilter.polygon timezone
		$interop = '[]';

		echo $interop;
		$response = new Response();
		//		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	private function getBbox($lat, $long, $meters)
	{

		//Earth's radius, sphere
		$R = 6378137;

		//Coordinate offsets in radians
		$dLat = $meters / $R;
		$dLon = $meters / ($R * cos(pi() * $lat / 180));

		//OffsetPosition, decimal degrees
		$minLat = $lat - $dLat * 180 / pi();
		$minLong = $long - $dLon * 180 / pi();
		$maxLat = $lat + $dLat * 180 / pi();
		$maxLong = $long + $dLon * 180 / pi();
		return $minLat . "," . $minLong . "," . $maxLat . "," . $maxLong;
	}

	private function getRadius($lat, $long, $meters)
	{

		//Earth's radius, sphere
		$R = 6378137;

		//Coordinate offsets in radians
		$dLat = $meters / $R;
		$dLon = $meters / ($R * cos(pi() * $lat / 180));

		//radius
		$rad = /*($dLon * 180/pi() + */ $dLat * 180 / pi()/*)/2*/;
		return $rad;
	}

	private function getLosangePath($lat, $long, $meters)
	{

		//Earth's radius, sphere
		$R = 6378137;

		//Coordinate offsets in radians
		$dLat = $meters / $R;
		$dLon = $meters / ($R * cos(pi() * $lat / 180));

		//OffsetPosition, decimal degrees
		$minLat = $lat - $dLat * 180 / pi();
		$minLong = $long - $dLon * 180 / pi();
		$maxLat = $lat + $dLat * 180 / pi();
		$maxLong = $long + $dLon * 180 / pi();
		return "(" . $maxLat . "," . $long . "),(" . $lat . "," . $maxLong . "),(" . $minLat . "," . $long . "),(" . $lat . "," . $minLong . ")";
	}

	public function orgaShow($params)
	{
		$result = $this->getOrganization($params);

		echo json_encode($result);
		$response = new Response();
		//$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	function getLicenses()
	{
		$callUrl =  $this->urlCkan . "api/action/license_list";

		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getSimpleOptions());
		$result = curl_exec($curl);
		curl_close($curl);

		$result = json_decode($result, true);
		unset($result["help"]);

		return $result;
	}

	public function licenseList()
	{
		$result = $this->getLicenses();

		echo json_encode($result);
		$response = new Response();
		//$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	public function getPackageList()
	{
		$callUrl =  $this->urlCkan . "api/action/package_list";

		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getSimpleOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		//echo $result . "\r\n";

		$result = json_decode($result, true);
		unset($result["help"]);

		return $result;
	}

	public function packageList()
	{
		$result = $this->getPackageList();

		echo json_encode($result);
		$response = new Response();
		//$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	public function cluster($params)
	{

		$ckanurl = $this->urlCkan;
		if (substr($ckanurl, -1) == "/") {
			$ckanurl = substr($ckanurl, 0, -1);
		}
		$callUrl =  "192.168.2.184:1337/cluster?" . $params;


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

	public function getSQLWhereRecordsDownload($params, $resourceId)
	{

		$patternId = '/id|num|code|siren/i';
		$patternRefine = '/refine./i';
		$patternDisj = '/disjunctive./i';
		$patternBbox = '/geofilter.bbox/i';
		$patternPolygon = '/geofilter.polygon/i';
		$patternDistance = '/geofilter.distance/i';
		$filters_init = array();
		$query_params = $this->proper_parse_str($params);

		$fields = $this->getAllFields($resourceId);
		//echo json_encode($fields);
		$fieldId = "id";
		$reqFields = "";
		$fieldCoordinates = '';
		$fieldGeometries = '';
		$reqQfilter;
		foreach ($fields as $value) {
			if (preg_match("/id|num|code|siren/i", $value['name'])) {
				$fieldId = $value['name'];
				break;
			}
		}
		foreach ($fields as $value) {
			if ($value['type'] == "geo_point_2d") $fieldCoordinates = $value['name'];
			if ($value['type'] == "geo_shape") $fieldGeometries = $value['name'];
		}


		foreach ($query_params as $key => $value) {
			if (preg_match($patternRefine, $key)) {
				$filters_init[preg_replace($patternRefine, "", $key)] =  $value;

				unset($query_params[$key]);
				//echo preg_replace($pattern,"",$key);
			}
			if (preg_match($patternDisj, $key)) {
				unset($query_params[$key]);
				//$disj[] = preg_replace($patternDisj,"",$key);
			}
			if (preg_match($patternBbox, $key)) {
				unset($query_params[$key]);
				$filters_init[$key] =  $value;
				//$disj[] = preg_replace($patternDisj,"",$key);
			}
			if (preg_match($patternDistance, $key)) {
				unset($query_params[$key]);
				$filters_init[$key] =  $value;
				//$disj[] = preg_replace($patternDisj,"",$key);
			}
			if (preg_match($patternPolygon, $key)) {
				unset($query_params[$key]);
				$filters_init[$key] =  $value;
				//$disj[] = preg_replace($patternDisj,"",$key);
			}
			if ($key == "geo_digest") {
				unset($query_params[$key]);
				$filters_init[$key] =  $value;
			}
			if ($key == "format") {
				$globalFormat = $value;

				if ($value == "json") {
					$format = "objects";
				} else if ($value == "csv" || $value == "xls") {
					$format = "csv";
				} else if ($value == "tsv") {
					$format = "tsv";
				} else if ($value == "geojson") {
					$format = "objects";
				} else {
					$format = "objects";
				}
				unset($query_params[$key]);
			}
			if ($key == "geo_simplify") {
				unset($query_params[$key]);
			}
			if ($key == "geo_simplify_zoom") {
				unset($query_params[$key]);
			}
			if ($key == "rows") {
				$query_params['limit'] = $value;
				unset($query_params['rows']);
			}
			if ($key == "fields") {
				$reqFields = $value;
				unset($query_params['fields']);
			}
			if ($key == "start") {
				$query_params['offset'] = $value;
				unset($query_params['start']);
				$query_params['limit'] = $query_params['limit'] + $query_params['offset'];
			}
			if ($key == "q") {
				$reqQfilter = $this->constructReqQToSQL($value);
				//$pattern = '/and (\w+) /i';
				//preg_match($pattern,$reqQfilter,$qField); 
			}

			if ($key == "id" || $key == "basemap" || $key == "location" || $key == "datasetcard" || $key == "static" || $key == "scrollWheelZoom") {
				unset($query_params[$key]);
			}
			if ($key == "use_labels_for_header") {
				unset($query_params[$key]);
			}
		}
		unset($query_params["clusterprecision"]);
		unset($query_params["q"]);
		$where = "";
		$limit  = "";
		if (!empty($filters_init)) {
			$where = " where ";
			foreach ($filters_init as $key => $value) {
				if ($key == "geofilter.bbox") {
					$bbox = explode(',', $value);
					$minlat = $bbox[0];
					$minlong = $bbox[1];
					$maxlat = $bbox[2];
					$maxlong = $bbox[3];
					//$where .= "CAST(split_part(".$fieldCoordinates.",',',1)AS FLOAT) between " . $minlat . " and " . $maxlat . " and ";
					//$where .= "CAST(split_part(".$fieldCoordinates.",',',2)AS FLOAT) between " . $minlong . " and " . $maxlong . " and ";
					$where .= "box(point(" . $minlat . "," . $minlong . "),point(" . $maxlat . "," . $maxlong . ")) @> point(" . $fieldCoordinates . ") and ";
					$where .= $fieldCoordinates . " not in ('', ',') and ";
				} else if ($key == "geofilter.distance") {
					$coord = explode(',', $value);
					$lat = $coord[0];
					$long = $coord[1];
					//$where .= "CAST(split_part(".$fieldCoordinates.",',',1)AS FLOAT) = " . $lat . " and ";
					//$where .= "CAST(split_part(".$fieldCoordinates.",',',2)AS FLOAT) = " . $long . " and ";
					$where .= "point(" . $lat . "," . $long . ") ~= point(" . $fieldCoordinates . ") and ";
					$where .= $fieldCoordinates . " not in ('', ',') and ";
				} else if ($key == "geo_digest") {
					$where .= "md5(" . $fieldGeometries . ") = '" . $value . "' and ";
				} else if ($key == "geofilter.polygon") {
					Logger::logMessage("Build query for geofilter.polygon \r\n");

					//polygon(path '((0,0),(1,1),(2,0))')
					$where .= "polygon(path '(" . $value . ")') @> point(" . $fieldCoordinates . ") and ";
				} else {
					if (is_numeric($value) && $key != "insee_com" && $key != "code_insee" && $key != "sta_nm_dpt") {
						$where .= $key . "=" . $value . " and ";
					} else if (is_array($value)) {
						if ($key != "insee_com" && $key != "code_insee") {
							$value = implode(',', array_map(array($this, 'quotesArrayValue'), str_replace("'", "''", $value)));
							$value = urldecode($value);
							$where .= $key . " in (" . $value . ") and ";
						}
						else {
							$where .= $key . " in (" . implode(',', array_map(array($this, 'quotesStringArrayValue'), $value)) . ") and ";
						}
					} else {
						$value = urldecode($value);
						$where .= $key . "='" . str_replace("'", "''", $value) . "' and ";
					}
				}
			}
			$where = substr($where, 0, strlen($where) - 4);

			if ($reqQfilter != NULL) {
				$where .= $reqQfilter;
			}
		} else if ($reqQfilter != NULL) {
			$where = " where " . substr($reqQfilter, 5);
		}

		return $where;
	}

	public function renderFrame(Request $request, $tab)
	{
		$id = $request->query->get('id');

		$api = new API();
		$dataset = $api->getPackageShow2($id, "");
		$ctx = str_replace(array("{", "}", '"'), array("\{", "\}", "&quot;"), json_encode($dataset));
		$element =  '<body>
        <div class="d4c-content">

            <div class="d4c-app-embed-dataset d4c-app-embed-dataset--analyze ng-cloak"
				ng-app="d4c.frontend"
				d4c-dataset-context
				context="ctx"
				ctx-urlsync="true"
				ctx-dataset-schema="' . $ctx . '">';

		if ($tab == "information") {
			$element .= '';
		}
		if ($tab == "table") {
			$element .= '<d4c-table context="ctx" class="d4c-app-embed-dataset__visualization d4c-app-embed-dataset__visualization--table d4cwidget-table--embedded" ></d4c-table>';
		}
		if ($tab == "map") {
			$element .= '<d4c-map context="ctx" class="d4c-app-embed-dataset__visualization d4c-app-embed-dataset__visualization--map" sync-to-url="true" static-map=""></d4c-map>';
		}
		if ($tab == "analyze") {
			$element .= '<d4c-analyze context="ctx" class="d4c-app-embed-dataset__visualization d4c-app-embed-dataset__visualization--analyze" sync-to-url="true" auto-resize="true" no-controls="true"></d4c-analyze>';
		}
		if ($tab == "images") {
			$element .= '<d4c-media-gallery context="ctx" class="d4c-app-embed-dataset__visualization d4c-app-embed-dataset__visualization--images" d4c-auto-resize d4c-widget-tooltip display-mode="compact"></d4c-media-gallery>';
		}
		if ($tab == "calendar") {
			$element .= '<d4c-calendar context="ctx" class="d4c-app-embed-dataset__visualization d4c-app-embed-dataset__visualization--calendar"></d4c-calendar>';
		}
		if ($tab == "custom_view") {
			$element .= '<div d4c-bind-angular-content="ctx.dataset.extra_metas.visualization.custom_view_html" do-not-decode-content></div>
                        <style type="text/css" d4c-bind-angular-content="ctx.dataset.extra_metas.visualization.custom_view_css"></style>';
			$tab = "custom";
		}
		if ($tab == "wordcloud") {
			$element .= '<d4c-wordcloud context="ctx" class="d4c-app-embed-dataset__visualization d4c-app-embed-dataset__visualization--wordcloud"  sync-to-url="true"></d4c-wordcloud>';
		}
		if ($tab == "timeline") {
			$element .= '<d4c-timeline context="ctx" class="d4c-app-embed-dataset__visualization d4c-app-embed-dataset__visualization--timeline" sync-to-url="true"></d4c-timeline>';
		}
		if ($tab == "export") {
			$element .= '';
		}
		if ($tab == "api") {
			$element .= '';
		}


		$element .= '
			<a class="d4c-embed-watermark d4c-embed-watermark--' . $tab . '"
               target="_parent"
               href="' . str_replace("/frame/", "/", $request->getUri()) . '">
                <img class="d4c-embed-watermark__image" ng-src="' . $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/img/theme-default.png" />
            </a>
        
    </div>
		<script src="' . $this->config->client->routing_prefix . '/modules/ckan_admin/js/routing.js"></script>
        <script src="' . $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/jquery-3.2.1.js"></script>
        <script type="text/javascript" src="' . $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/bootstrap.min.js"></script>
        <script type="text/javascript" src="' . $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/libraries.js"></script>
		<script type="text/javascript" src="' . $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/lib/qtip/jquery.qtip.min.js"></script>	
		<script type="text/javascript" src="' . $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/lib/fullcalendar/moment.min.js"></script>
		<script type="text/javascript" src="' . $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/lib/fullcalendar/fullcalendar.min.js"></script>
		<script type="text/javascript" src="' . $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/lib/fullcalendar/lang/fr.js"></script>
		<script type="text/javascript" src="' . $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/underscore-min.js"></script>
        <script type="text/javascript" src="' . $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/angular-core.js"></script>
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
                    BRAND_HOSTNAME: "' . $this->config->client->domain . '",
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
        <script type="text/javascript" src="' . $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/i18n.js"></script>
        <script src="' . $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/supported-browsers-message.js" type="text/javascript"></script>
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
    <script type="text/javascript" src="' . $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/embed-dataset.js"></script>

   <script>
   			$("head").append("<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"/> ");
			$("head").append("<link href=\"' . $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/normalize.css\" rel=\"stylesheet\">");
			$("head").append("<link href=\"' . $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/d4cui.css\" rel=\"stylesheet\">");
			//$("head").append("<link href=\"' . $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/bootstrap.min.css\" rel=\"stylesheet\">");
			$("head").append("<link href=\"' . $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/visualisation.css\" rel=\"stylesheet\">");
			$("head").append("<base href=\"/\">");	
			$("head").append("<link href=\"' . $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/font-awesome.min.css\" rel=\"stylesheet\">");
	</script>

</body>';
		echo $element;
		$response = new Response();

		$response->headers->set('Content-Type', 'text/html');

		return $response;
	}

	public function getAnalyze($params)
	{
		$params = preg_replace('/_slash_/i', "/", $params);
		//echo $params;
		//dataset x sort y.serie1-{j} maxpoints y.serie1-1.expr y.serie1-2.func y.serie1-1.cumulative y.serie1-1-range-0.expr timezone
		$patternSerie = '/y.serie[\d]-/i';

		$query_params = $this->proper_parse_str($params);
		unset($query_params["timezone"]);


		$datasetId = "";
		if (!array_key_exists("resource_id", $query_params) && array_key_exists("dataset", $query_params)) {
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
				if (($value['format'] == 'CSV' || $value['format'] == 'XLS' || $value['format'] == 'XLSX') && $value["datastore_active"] == true) {
					$resourceCSV = $value['id'];
					break;
				}
			}
			unset($query_params['dataset']);
			$query_params['resource_id'] = $resourceCSV;
		}
		
		Logger::logMessage("TRM - Parameters " . json_encode($query_params));

		$ySeries = array();
		foreach ($query_params as $key => $value) {
			if (preg_match($patternSerie, $key)) {
				$var = explode('.', $key);
				$nom = $var[0] . '.' . $var[1];
				$app = $var[2];

				if (array_key_exists($nom, $ySeries)) {
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
		foreach ($ySeries as $y) {
			if ($y["expr"] == NULL) {
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
			$func = $y["func"];
			$f = "";
			if (is_numeric($y["expr"][0])) {
				$y["expr"] = '' . $y["expr"] . '';
			}
			switch ($func) {
				case "COUNT":
					$f = "cast(count(" . $y["expr"] . ") as integer)";
					break;
				case "AVG":
					$f = "cast(avg(cast(replace(cast(" . $y["expr"] . " as text), ',', '.') as DOUBLE PRECISION)) as DOUBLE PRECISION)";
					break;
				case "MIN":
					$f = "cast(min(cast(replace(cast(" . $y["expr"] . " as text), ',', '.') as DOUBLE PRECISION)) as DOUBLE PRECISION)";
					break;
				case "MAX":
					$f = "cast(max(cast(replace(cast(" . $y["expr"] . " as text), ',', '.') as DOUBLE PRECISION)) as DOUBLE PRECISION)";
					break;
				case "STDDEV":
					$f = "cast(stddev_pop(cast(replace(cast(" . $y["expr"] . " as text), ',', '.') as DOUBLE PRECISION)) as DOUBLE PRECISION)";
					break;
				case "SUM":
					$f = "cast(sum(cast(replace(cast(" . $y["expr"] . " as text), ',', '.') as DOUBLE PRECISION)) as DOUBLE PRECISION)";
					break;
				case "QUANTILES":
					$n = $y["subsets"];
					$f = "percentile_cont(" . ($n / 100) . ") within group ( order by " . $y["expr"] . ")";
					break;
				default:
					$f = "cast(count(cast(replace(cast(" . $y["expr"] . " as text), ',', '.') as DOUBLE PRECISION)) as integer)";
					break;
			}


			if ($y["subsets"] == NULL) {
				$fields .= $f . " as \"" . $y["name"] . "\",";
			} else {
				$fields .= $f . " as \"" . $y["name"] . "." . $y["subsets"] . "\",";
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
		if (count($query_params["x"]) > 0) {
			$groupby = " group by ";
			$fields .= ",";
			if (is_array($query_params["x"])) {
				foreach ($query_params["x"] as $x) {
					$col = $x;
					$app = NULL;
					if (strpos($x, '.') !== false) {
						$col = explode(".", $x)[0];
						$app = explode(".", $x)[1];
					}
					$groupby .= '"' . $x . '",';
					if ($app != NULL) {
						if ($app == "weekday") {
							$app = "dow";
						}
						if ($app == "yearday") {
							$app = "doy";
						}
						$fields .= "extract(" . $app . " from " . $col . ") as \"" . $x . "\",";
					} else {
						$fields .= $x . ",";
					}
				}
			} else {
				$col = $query_params["x"];
				$app = NULL;
				if (strpos($query_params["x"], '.') !== false) {
					$col = explode(".", $query_params["x"])[0];
					$app = explode(".", $query_params["x"])[1];
				}
				$groupby .= "\"" . $query_params["x"] . "\",";
				if ($app != NULL) {
					if ($app == "weekday") {
						$app = "dow";
					}
					if ($app == "yearday") {
						$app = "doy";
					}
					$fields .= "extract(" . $app . " from " . $col . ") as \"" . $query_params["x"] . "\",";
				} else {
					$fields .= $query_params["x"] . ",";
				}
			}

			$groupby = substr($groupby, 0, -1);
			$fields = substr($fields, 0, -1);
		}

		$orderby = "";

		if ((is_array($query_params["sort"]) && count($query_params["sort"]) > 0) || $query_params["sort"] != "") {
			$orderby = " order by ";
			foreach (explode(',', $query_params["sort"]) as $sort) {
				if (preg_match('/serie1-/i', $sort)) {
					$orderby .=  "\"y." . $sort . "\",";
				} else {
					$orderby .=  "\"" . substr($sort, 2) . "\",";
				}
			}
			$orderby = substr($orderby, 0, -1);
		} else if (!is_array($query_params["x"])) {
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
		$where = $this->getSQLWhereRecordsDownload($params, $query_params['resource_id']);

		if (array_key_exists("maxpoints", $query_params) && $query_params['maxpoints'] != "") {
			$limit = " limit " . $query_params['maxpoints'];
		} else {
			$limit = " limit 1000"; //par defaut
		}


		$req = array();
		$sql = "Select " . $fields . " from \"" . $query_params['resource_id'] . "\"" . $where . $groupby . $orderby . $limit;

		//error_log($sql);

		$req['sql'] = $sql;

		Logger::logMessage("TRM - SQL : " . $sql);

		$url2 = http_build_query($req);
		$callUrl =  $this->urlCkan . "api/action/datastore_search_sql?" . $url2;
		//echo $callUrl . "\r\n";
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		//echo $result . "\r\n";

		// error_log('ttt' . $result);

		$result = json_decode($result, true);

		$data_array = array();
		foreach ($result["result"]["records"] as $value) {
			$row = array();
			foreach ($value as $key => $val) {
				if (strpos($key, "y.") !== FALSE) {
					if ($val == null) continue;
					$cumul = $ySeries[$key]["cumulative"]; //true or false

					$key = str_replace("y.", "", $key);
					if ($cumul == "true") {
						$row[$key] = $data_array[count($data_array) - 1][$key] + $val;
					} else {
						if (strpos($key, ".") !== FALSE) {
							$row[explode('.', $key)[0]][explode('.', $key)[1]] = $val;
						} else {
							$row[$key] = $val;
						}
					}
				} else {
					if ($val == "" && $val != 0) {
						continue 2;
					}
					if (strpos($key, ".") !== FALSE) {
						/*if(is_array($query_params["x"]) && count($query_params["x"])>1){
							$row["x"][explode('.', $key)[0]][explode('.', $key)[1]] = $val;
						} else {*/
						$row["x"][explode('.', $key)[1]] = $val;
						/*}*/
					} else {
						if (is_array($query_params["x"]) && count($query_params["x"]) > 1) {
							$row["x"][$key] = $val;
						} else {
							$row["x"] = $val;
						}
					}
				}
			}
			if (count(array_keys($row)) == 1 && array_keys($row)[0] == "x") continue;
			$data_array[] = $row;
		}

		echo json_encode($data_array);
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	public function callALternativeExport($datasetid, $resourceid)
	{
		$callUrl =  $this->urlCkan . "api/action/resource_show?id=" . $resourceid;
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getSimpleOptions());
		$res = curl_exec($curl);
		echo $res . "\r\n";
		curl_close($curl);
		$res = json_decode($res, true);

		header('Location: ' . $res["result"]["url"]);
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	public function mapBuilder($idmap)
	{
		$method = $_SERVER['REQUEST_METHOD'];
		$table = "d4c_maps";
		$idUser = null;

		if (\Drupal::currentUser()->isAuthenticated()) {
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
				if (count($res) > 0) {
					$name .= (count($res) + 1);
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
				if ($idmap == "") {
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
				$data_array = array();
				if ($idmap != '' && $idmap != null) { //echo json_encode($res);
					$data_array = $res[0]->map_json;
					echo $data_array;
				} else {
					$data_array = array();
					foreach ($res as $map) {
						$data_array[] = json_decode($map->map_json, TRUE);
					}
					echo json_encode($data_array);
				}
				break;
			case 'DELETE':  //delete existing map, get idMap
				if ($idmap == "") {
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

	public function getMaps($idUser, $idMap)
	{
		Logger::logMessage("Getting maps for user : " . $idUser . " with map id : " . $idMap . "");

		$table = "d4c_maps";
		$query = \Drupal::database()->select($table, 'map');

		$query->fields('map', [
			'map_id',
			'map_id_user',
			'map_name',
			'map_json'
		]);

		if ($idUser != "" && $idUser != null) {
			$query->condition('map_id_user', $idUser);
		}
		if ($idMap != "" && $idMap != null) {
			$query->condition('map_name', $idMap);
		}

		$prep = $query->execute();
		//$prep->setFetchMode(PDO::FETCH_OBJ);
		$res = array();
		while ($enregistrement = $prep->fetch()) {
			array_push($res, $enregistrement);
		}
		return $res;
	}

	public function isLoggedIn()
	{
		$data_array = array();
		$data_array["logged_in"] = \Drupal::currentUser()->isAuthenticated();
		$data_array["pending"] = false;
		echo json_encode($data_array);
		$response = new Response();
		//		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}
	public function getCustomView($idDataset)
	{
		$table = "d4c_custom_views";
		$query = \Drupal::database()->select($table, 'map');

		$query->fields('map', [
			'cv_id',
			'cv_name',
			'cv_title',
			'cv_icon',
			'cv_template'
		]);

		$query->condition('cv_dataset_id', $idDataset);
		$prep = $query->execute();
		//$prep->setFetchMode(PDO::FETCH_OBJ);
		$res = array();
		while ($enregistrement = $prep->fetch()) {
			array_push($res, $enregistrement);
		}
		if (count($res) > 0) {
			$cv = $res[count($res) - 1];

			$table = "d4c_custom_views_html";
			$query = \Drupal::database()->select($table, 'map');

			$query->fields('map', [
				'cvh_html',
				'cvh_order'
			]);

			$query->condition('cvh_id_cv', $cv->cv_id);
			$query->orderBy('cvh_order', 'ASC');

			$prep = $query->execute();
			//$prep->setFetchMode(PDO::FETCH_OBJ);
			$html = array();
			while ($enregistrement = $prep->fetch()) {
				array_push($html, $enregistrement);
			}
			$cv->html = $html;

			return $cv;
		} else {
			return null;
		}
	}

	function getMapLayersFromFile() {
		return json_decode(file_get_contents(__DIR__ . "/../../map_tiles.json"), true);
	}

	function saveMapLayersFromFile($mapLayers) {
		file_put_contents(__DIR__ . "/../../map_tiles.json", json_encode($mapLayers, JSON_PRETTY_PRINT));
	}

	public function getMapLayers($type = null) {
		$mapLayers = $this->getMapLayersFromFile();
		$mapTiles = $mapLayers['map_tiles'];

		$data_array = array();
		if ($type != null) {
			foreach ($mapTiles as $tile) {
				if ($tile["type"] == $type) {
					$data_array["layers"][] = $tile;
				}
			}
		}
		else {
			$data_array["layers"] = $mapTiles;
		}

		$default_bbox = $this->config->client->default_bounding_box;
		if ($default_bbox != null && $default_bbox != "") {
			$data_array["default_bbox"] = $default_bbox;
		}
		else {
			$data_array["default_bbox"] = null;
		}

		return $data_array;
	}

	public function callMapLayers($params) {
		$type = null;
		$query_params = $this->proper_parse_str($params);
		if (array_key_exists('type', $query_params)) {
			$type = $query_params["type"];
		}
		$data_array = $this->getMapLayers($type);

		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent(json_encode($data_array));
		return $response;
	}

	public function addMapLayer($params) {
		if (is_array($params)) {
			$tile = $params;
		}
		else {
			$tile = $this->proper_parse_str($params);
		}

		$mapLayers = $this->getMapLayersFromFile();
		$mapLayers['map_tiles'][] = $tile;
		$this->saveMapLayersFromFile($mapLayers);

		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	public function updateMapLayer($params) {
		if (is_array($params)) {
			$tile = $params;
		}
		else {
			$tile = json_decode($_POST["json"], true);
		}

		$mapLayers = $this->getMapLayersFromFile();

		$exists = false;
		foreach ($mapLayers['map_tiles'] as &$layer) {
			if ($layer['name'] == $tile["name"]) {
				$layer = $tile;
				$exists = true;
				break;
			}
		}
		if (!$exists) {
			$mapLayers->map_tiles[] = $tile;
		}

		$this->saveMapLayersFromFile($mapLayers);

		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	public function deleteMapLayer($idLayer) {
		$mapLayers = $this->getMapLayersFromFile();

		$arr = array();
		foreach ($mapLayers['map_tiles'] as $layer) {
			if ($layer['name'] != $idLayer) {
				$arr[] = $layer;
			}
		}

		$newLayers = array();
		$newLayers['map_tiles'] = $arr;
		
		$this->saveMapLayersFromFile($newLayers);

		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	public function precluster()
	{
		$query = $_SERVER['QUERY_STRING'];
		$callUrl = $this->config->cluster->url . "precluster";
		$callUrl .= "?" . $query;


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

	public function callPackageSearchWithRecordsCount($params)
	{
		$params = str_replace("qf=title^3.0 notes^1.0", "qf=title^3.0+notes^1.0", $params);
		$callUrl =  $this->urlCkan . "api/action/package_search";


		if (!is_null($params)) {
			$callUrl .= "?" . $params;
		}
		$cle = $this->config->ckan->api_key;
		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_HTTPHEADER => array(
				'Content-type:application/json',
				'Content-Length: ' . strlen($jsonData),
				'Authorization:  ' . $cle
			)
		);
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $options);
		$result = curl_exec($curl);
		//echo $callUrl;
		curl_close($curl);

		$result = json_decode($result, true);
		$data = array();
		unset($result["help"]); //echo count($result["result"]["results"]);
		foreach ($result["result"]["results"] as $i => $dataset) {
			foreach ($dataset["resources"] as $j => $value) {
				//unset($result["result"]["results"][$i]["resources"][$j]["url"]);	//echo $value["url"];

				$format = $result["result"]["results"][$i]["resources"][$j]["format"];
				if (($format == "CSV" || $format = "XLS" || $format == "XLSX") && $result["result"]["results"][$i]["resources"][$j]["datastore_active"] == true) {
					$req["sql"] = 'Select count(*) from "' . $result["result"]["results"][$i]["resources"][$j]["id"] . '"';
					$url2 = http_build_query($req);
					$callUrl =  $this->urlCkan . 'api/action/datastore_search_sql?' . $url2;
					$curl = curl_init($callUrl);
					curl_setopt_array($curl, $this->getSimpleOptions());
					$res = curl_exec($curl);
					//					//echo $callUrl;
					curl_close($curl);
					$res = json_decode($res, true);
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

	// You need to set CKAN Api KEY and D4C URL for this function to work
	// drush cset ckan_admin.organisationForm clef 'MY_KEY'
	// drush cset ckan_admin.organisationForm d4c 'MY_KEY'
	function callGenerateDownloadFiles($params) {
		$query_params = $this->proper_parse_str($params);
		$datasetId = $query_params["datasetId"];

		//TEST TO REMOVE
		// $result = array();
		// $result["status"] = "success";
		// $result["message"] = $this->dataFolder;
		// $result["message3"] = $this->urlDataFolder;
		// $result["message4"] = $this->d4cUrl;

		// $response = new Response();
		// $response->setContent(json_encode($result));
		// $response->headers->set('Content-Type', 'application/json');

		// return $response;
		//END TEST

		try {
			$now = date("YmdHis");
			$uniqiD = uniqid();

			$hasCSV = false;
			$nameCSV = null;
			$idCSV = null;
			$urlCSV = null;

			$hasXLSX = false;
			$urlXLSX = null;

			$hasJSON = false;
			$urlJSON = null;

			$hasGEOJSON = false;
			$urlGEOJSON = null;

			$hasKML = false;
			$urlKML = null;

			$hasSHP = false;
			$urlSHP = null;

			// Getting dataset and checking if resource file already exist
			$dataset = $this->getDataset($datasetId);
			$dataset = json_decode($dataset, true);
			$dataset = $dataset['result'];

			// Going through resources to see if the files already exists
			$resources = $dataset['resources'];
			foreach ($resources as $resource) {
				$name = $resource['name'];
				$format = $resource['format'];
				$resourceId = $resource['id'];
				$url = $resource['url'];

				if (strcasecmp($format, 'CSV') == 0) {
					$hasCSV = true;
					$nameCSV = $name;
					$idCSV = $resourceId;
					$urlCSV = $url;
				}
				else if (strcasecmp($format, 'XLSX') == 0 || strcasecmp($format, 'XLS') == 0) {
					$hasXLSX = true;
					$urlXLSX = $url;
				}
				// We check if it is a geojson in format or in the name
				else if (strcasecmp($format, 'GEOJSON') == 0 || $this->str_contains($name, 'geojson') == 0) {
					$hasGEOJSON = true;
					$urlGEOJSON = $url;
				}
				else if (strcasecmp($format, 'JSON') == 0) {
					$hasJSON = true;
					$urlJSON = $url;
				}
				else if (strcasecmp($format, 'KML') == 0) {
					$hasKML = true;
					$urlKML = $url;
				}
				else if (strcasecmp($format, 'SHP') == 0) {
					$hasSHP = true;
					$urlSHP = $url;
				}
			}

			Logger::logMessage("We generate CSV for resource " . $idCSV);

			// We generate a CSV from the data because the date format does not fit ANFR and need to be yyyy-mm-dd
			$params = 'format=csv&use_labels_for_header=true&resource_id=' . $idCSV;
			$generatedUrlCSV = $this->getRecordsDownload($params, true);

			// Manage CSV - We write the file in data folder to use it
			$filenameCSV = $now . '_' . $nameCSV;
			$filePathCSV = $this->dataFolder . $filenameCSV;
			rename($generatedUrlCSV, $filePathCSV);

			$urlCSV = $this->d4cUrl . $this->urlDataFolder . $filenameCSV;

			Logger::logMessage("TRM - filePathCSV = " . $filePathCSV . " and generatedUrlCSV = " . $generatedUrlCSV . " and urlCSV = " . $urlCSV);

			Logger::logMessage("CSV has been generated at " . $urlCSV);

			// Generate XLSX
			if (!$hasXLSX) {
				Logger::logMessage("We generate XLSX from CSV file");

				$fileXLSX = $this->generateXLSX($filePathCSV, $this->dataFolder);
				$filenameXLSX = $now . "_" . $uniqiD . ".xlsx";
				rename($fileXLSX, $this->dataFolder . $filenameXLSX);

				$urlXLSX = $this->d4cUrl . "/" . $this->urlDataFolder . $filenameXLSX;

				Logger::logMessage("XLSX has been generated at " . $urlXLSX);
			}

			// TODO: Generate SHP and KML file (SHP has a 50k limit)

			// We reinit variable to define extras
			$hasCSV = false;
			$hasXLSX = false;
			$hasJSON = false;
			$hasGEOJSON = false;
			$hasKML = false;
			$hasSHP = false;

			$keyCSV = "file_csv";
			$keyXLSX = "file_xlsx";
			$keyJSON = "file_json";
			$keyGEOJSON = "file_geojson";
			$keyKML = "file_kml";
			$keySHP = "file_shp";

			// Add files to dataset's extras
			$extras = $dataset['extras'];
			if ($extras == null) {
				$extras = array();
			}

			if ($extras != null && count($extras) > 0) {
				for ($index = 0; $index < count($extras); $index++) {
					if ($extras[$index]['key'] == $keyCSV) {
						$hasCSV = true;
						$extras[$index]['value'] = $urlCSV;
					}

					if ($extras[$index]['key'] == $keyXLSX) {
						$hasXLSX = true;
						$extras[$index]['value'] = $urlXLSX;
					}

					if ($extras[$index]['key'] == $keyJSON) {
						$hasJSON = true;
						$extras[$index]['value'] = $urlJSON;
					}

					if ($extras[$index]['key'] == $keyGEOJSON) {
						$hasGEOJSON = true;
						$extras[$index]['value'] = $urlGEOJSON;
					}

					if ($extras[$index]['key'] == $keyKML) {
						$hasKML = true;
						$extras[$index]['value'] = $urlKML;
					}

					if ($extras[$index]['key'] == $keySHP) {
						$hasSHP = true;
						$extras[$index]['value'] = $urlSHP;
					}
				}
			}

			if ($hasCSV == false) {
				$extras[count($extras)]['key'] = $keyCSV;
				$extras[(count($extras) - 1)]['value'] = $urlCSV;
			}

			if ($hasXLSX == false) {
				$extras[count($extras)]['key'] = $keyXLSX;
				$extras[(count($extras) - 1)]['value'] = $urlXLSX;
			}

			if ($hasJSON == false) {
				$extras[count($extras)]['key'] = $keyJSON;
				$extras[(count($extras) - 1)]['value'] = $urlJSON;
			}

			if ($hasGEOJSON == false) {
				$extras[count($extras)]['key'] = $keyGEOJSON;
				$extras[(count($extras) - 1)]['value'] = $urlGEOJSON;
			}

			if ($hasKML == false) {
				$extras[count($extras)]['key'] = $keyKML;
				$extras[(count($extras) - 1)]['value'] = $urlKML;
			}

			if ($hasSHP == false) {
				$extras[count($extras)]['key'] = $keySHP;
				$extras[(count($extras) - 1)]['value'] = $urlSHP;
			}

			$dataset['extras'] = $extras;

			$callUrl = $this->urlCkan . "api/action/package_update";
			$return = Query::putSolrRequest($callUrl, $dataset, 'POST');
			
			$result = array();
			$result["status"] = "success";
			$result["message"] = $return;
		} catch (\Exception $e) {
			Logger::logMessage($e->getMessage());
			$data_array = array();
			$data_array["message"] = $e->getMessage();
			
			$result["result"] = $data_array;
			$result["status"] = "error";
		}

		$response = new Response();
		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	function getDataset($datasetId) {
		$callUrl =  $this->urlCkan . "api/action/package_show?id=" . $datasetId;
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		return $result;
	}

	function getResource($resourceId) {
		$callUrl =  $this->urlCkan . "api/action/resource_show?id=" . $resourceId;
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getSimpleOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		return $result;
	}

	function generateXLSX($inputPath, $outputFolder) {
		$outputPath = tempnam($outputFolder, 'xlsx_convertion');
		rename($outputPath, $outputPath .= '.xlsx');
		$outputPath = $outputPath . '.xlsx';

		// $reader = ReaderEntityFactory::createCSVReader();
		// $writer = WriterEntityFactory::createXLSXWriter();

		// $reader->open($inputPath);
		// $writer->openToFile($outputPath); // write data to a file or to a PHP stream

		$reader = ReaderEntityFactory::createCSVReader();
		$reader->open($inputPath);

		$writer = WriterEntityFactory::createXLSXWriter();
		$writer->openToFile($outputPath);

		foreach ($reader->getSheetIterator() as $sheet) {
			foreach ($sheet->getRowIterator() as $row) {
				$writer->addRow($row);
			}
		}

		$reader->close();
		$writer->close();

		return $outputPath;
	}

	function str_contains(string $haystack, string $needle): bool {
		return '' === $needle || false !== strpos($haystack, $needle);
	}


	function callVanillaUrlReports()
	{

		$this->config = include(__DIR__ . "/../../config.php");

		$vanilla = $this->config->vanilla->url;
		$result = Query::callSolrServer($vanilla . "/VanillaRuntime/vanillaExternalAccess?objecttype=url");
		$response = new Response();
		$response->setContent($result);
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	function getCsvXls($params)
	{



		$params = explode(";", $params);
		$url = $params[0];
		$url = str_replace('!', '/', $url);




		$format = $params[1];
		$site = $params[2];

		$site_2 = explode(":", $site);


		if ($site == 'Public.OpenDataSoft.com') {
			$url = 'https://public.opendatasoft.com/explore/dataset/' . $url . '/download/?format=csv&timezone=Europe/Madrid&use_labels_for_header=true';
		}

		if ($site_2[0] == 'odsall') {
			$url = 'https://' . $site_2[1] . '/explore/dataset/' . $url . '/download/?format=csv&timezone=Europe/Madrid&use_labels_for_header=true';
		}

		if ($site_2[0] == 'socrata') {
			$url = 'https://' . $url . '/resource/' . $site_2[1] . '.csv';
		}

		if ($site == 'Ckan') {
			if ($_SERVER['HTTP_HOST'] == '192.168.2.217') {
				$url = preg_replace("(^https?://)", "http://", $url);
			}
		}

		Logger::logMessage("Getting CSV / XLS with URL '" . $url . "' \r\n");

		$arr = array();
		if ($format == 'csv' || $format == 'CSV') {

			$delimiter = $this->getFileDelimiter($url);

			$arr1 = file($url);
			$arr = array();
			$a = 15;

			if (count($arr1) < 15) {
				$a = count($arr1);
			}

			for ($i = 0; $i < $a; $i++) {

				$text = $arr1[$i];
				//Trying to detect encoding to convert automatically to UTF-8
				$arr[$i] = iconv(mb_detect_encoding($text, mb_detect_order(), true), "UTF-8", $text);

				// OLD way to keep track in case
				// $arr[$i] = utf8_decode(iconv("UTF-8", "ISO-8859-1//IGNORE", $arr1[$i]));
			}

			if ($arr[0] == null || $arr[0] == '') {
				$arr[0] = "Pas d'accès de ligne ou de colonne aux tables non tabulaires";
			}
			$arr = array('delimiter' => $delimiter, 'data' => $arr);
		} else if ($format == 'XLS' || $format == 'xls' || $format == 'XLSX' || $format == 'xlsx') {
		}

		$response = new Response();
		$response->setContent(json_encode($arr));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	function getFileDelimiter($file, $checkLines = 2)
	{
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
		while ($file->valid() && $i <= $checkLines) {
			$line = $file->fgets();
			foreach ($delimiters as $delimiter) {
				$regExp = '/[' . $delimiter . ']/';
				$fields = preg_split($regExp, $line);
				if (count($fields) > 1) {
					if (!empty($results[$delimiter])) {
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

	function nettoyage($str, $charset = 'utf-8')
	{
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
		$str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
		$str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
		$str = preg_replace('#&[^;]+;#', '', $str);

		return $str;
	}

	function callInfocom94($params)
	{

		$this->config = include(__DIR__ . "/../../config.php");
		$siteSearch1 = $this->config->sitesSearch;
		$siteSearch = array();
		foreach ($siteSearch1 as &$val) {
			if ($_SERVER['HTTP_HOST'] == '192.168.2.217') {
				if ("http://" . $_SERVER['HTTP_HOST'] . "/" != $val) {
					array_push($siteSearch, $val);
				}
			} else {
				if ("https://" . $_SERVER['HTTP_HOST'] . "/" != $val) {
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

		$result = array();
		foreach ($siteSearch as &$val) {

			$callSolrUrl = $val . "api/datasets/2.0/search/q=" . $params;
			Logger::logMessage("Call '" . $callSolrUrl . "' \r\n");
			$t = Query::callSolrServer($callSolrUrl);
			$t = json_decode($t);

			foreach ($t->result->results as &$dataset) {
				$dataset->siteOfDataset = $val;
				$dataset->url = $val . $this->config->client->routing_prefix . '/visualisation/table/?id=' . $dataset->id;
				array_push($result, $dataset);


				$this->config = include(__DIR__ . "/../../config.php");
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

				for ($i = 0; $i < count($dataset->resources); $i++) {

					$callUrl = $val . $this->config->client->routing_prefix . "/d4c/datasets/update/getresourcebyid/" . $dataset->resources[$i]->id;
					$res = Query::callSolrServer($callUrl);

					$dataset->resources[$i]->url = json_decode($res);
				}
			}
		}



		$response = new Response();
		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	function callD4c($params)
	{

		$params = explode(";", $params);

		$result = array();
		/*$callUrl = 'https://'.$params[0] . $this->config->client->routing_prefix . "/d4c/api/datasets/2.0/search/q=".$params[1];
		$curlOrg = curl_init($callUrl);
		curl_setopt_array($curlOrg, $this->getSimpleOptions());
        $t = curl_exec($curlOrg);
        curl_close($curlOrg);
        echo 'https://'.$params[0] . $this->config->client->routing_prefix . "/d4c/api/datasets/2.0/search/q=".$params[1];*/
		$t = Query::callSolrServer('https://' . $params[0] . $this->config->client->routing_prefix . "/d4c/api/datasets/2.0/search/q=" . $params[1]);
		//echo $t; 
		$t = json_decode($t);

		Logger::logMessage("callD4c - Search on " . $params[0] . " with params = " . $params[1]);
		Logger::logMessage("Found " . count($t->result->results) . " results.");

		foreach ($t->result->results as &$dataset) {
			$dataset->siteOfDataset = $params[0];
			$dataset->url = 'https://' . $params[0] . $this->config->client->routing_prefix . '/visualisation/table/?id=' . $dataset->id;


			for ($i = 0; $i < count($dataset->resources); $i++) {

				if ($_SERVER['HTTP_HOST'] == '192.168.2.217') {

					$res = Query::callSolrServer('http://' . $params[0] . $this->config->client->routing_prefix . "/d4c/datasets/update/getresourcebyid/" . $dataset->resources[$i]->id);
				} else {
					$res = Query::callSolrServer('https://' . $params[0] . $this->config->client->routing_prefix . "/d4c/datasets/update/getresourcebyid/" . $dataset->resources[$i]->id);
				}


				$dataset->resources[$i]->url = json_decode($res);
			}

			array_push($result, $dataset);
		}




		$response = new Response();
		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	function getResourceById($params)
	{

		$this->config = include(__DIR__ . "/../../config.php");
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

		$res = $this->getResource($params);
		$res = json_decode($res);

		$response = new Response();
		$response->setContent(json_encode($res->result->url));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}


	function getDataSetById($id)
	{

		$this->config = include(__DIR__ . "/../../config.php");
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

		$callUrl = $this->urlCkan . "api/action/package_show?id=" . $id;

		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $optionst);
		$res  = curl_exec($curl);
		curl_close($curl);


		$response = new Response();
		$response->setContent($res);
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	function callSearchDataGouvOrg($params)
	{
		$result = Query::callSolrServer("https://www.data.gouv.fr/api/1/organizations/?page_size=10000&q=" . $params);
		$response = new Response();
		$response->setContent($result);
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	function callSearchDataGouvDataset($params)
	{
		$result = Query::callSolrServer("https://www.data.gouv.fr/api/1/datasets/?page_size=10000&q=" . $params);
		$response = new Response();
		$response->setContent($result);
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	function callSearchDataGouvDatasetByOrg($params)
	{
		$result = Query::callSolrServer("https://www.data.gouv.fr/api/1/datasets/?page_size=10000&organization=" . $params);
		$response = new Response();
		$response->setContent($result);
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	function callSearchOpendatasoft($params)
	{
		$result = Query::callSolrServer("https://public.opendatasoft.com/api/datasets/1.0/search/?q=" . $params);


		//error_log("https://public.opendatasoft.com/api/v1/console/datasets/1.0/search/?q=".$params);

		$response = new Response();
		$response->setContent($result);
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	function callSearchOpendatasoftAllSite($params)
	{
		$params = explode(";", $params);

		$url = "https://" . $params[0] . "/api/datasets/1.0/search/?rows=10000&q=" . $params[1];
		$result = Query::callSolrServer($url);
		Logger::logMessage("Calling ODS with url '" . $url . "'");

		$response = new Response();
		$response->setContent($result);
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	function callSearchSocrata($params)
	{

		$params = explode(";", $params);

		//$result = Query::callSolrServer($params[0]."/api/catalog/v1?q=".$params[1]);
		$result = Query::callSolrServer("http://api.us.socrata.com/api/catalog/v1?domains=" . $params[0] . "&search_context=" . $params[0] . "&q=" . $params[1]);


		$response = new Response();
		$response->setContent($result);
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	function ckanSearchCall($params)
	{
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

	function callCustomView($params)
	{

		$idDataset = $params;
		$table = "d4c_custom_views";
		$query = \Drupal::database()->select($table, 'map');

		$query->fields('map', [
			'cv_id',
			'cv_name',
			'cv_title',
			'cv_icon',
			'cv_template'
		]);

		$query->condition('cv_dataset_id', $idDataset);
		$prep = $query->execute();



		//$prep->setFetchMode(PDO::FETCH_OBJ);
		$res = array();
		while ($enregistrement = $prep->fetch()) {
			array_push($res, $enregistrement);
		}


		if (count($res) > 0) {
			$cv = $res[count($res) - 1];

			$table = "d4c_custom_views_html";
			$query = \Drupal::database()->select($table, 'map');

			$query->fields('map', [
				'cvh_html',
				'cvh_order'
			]);

			$query->condition('cvh_id_cv', $cv->cv_id);
			$query->orderBy('cvh_order', 'ASC');

			$prep = $query->execute();
			//$prep->setFetchMode(PDO::FETCH_OBJ);
			$html = array();
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

	function getPackageTheme()
	{
		$t = $this->getThemes(false);

		$response = new Response();
		$response->setContent($t);
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	function getThemes($returnJson = false, $sort = false)
	{
		if ($this->themes == null) {
			$themConfig = \Drupal::service('config.factory')->getEditable('ckan_admin.themeForm');
			$this->themes = $themConfig->get('themes');
		}
		if ($returnJson) {
			$themes = json_decode($this->themes, true);

			if ($sort) {
				usort($themes, function ($a, $b) {
					$key = "title";
					$result = strcmp(strtolower($a[$key]), strtolower($b[$key]));
					return $result;
				});
			}
			return $themes;
		} else {
			return $this->themes;
		}
	}

	function getThemeLabel($themeValue)
	{
		$themes = $this->getThemes(true);
		foreach ($themes as $theme) {
			if ($theme['title'] == $themeValue) {
				return isset($theme['label']) ? $theme['label'] : $themeValue;
			}
		}
		return $themeValue;
	}

	function updateNbDownload($params)
	{
		$this->updatePackage($params, "nb_download");
		//$this->getThemeArray()
		$response = new Response();
		//$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	function updateNbViews($params)
	{
		$this->updatePackage($params, "nb_views");
		$response = new Response();
		//    	$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	function updatePackage($dataseId, $keyToUpdate)
	{
		$result = $this->getPackageShow("id=" . $dataseId);
		$keyExists = false;
		$index = 0;
		for ($i = 0; $i < count($result['result']['extras']); $i++) {

			if ($result['result']['extras'][$i]['key'] == $keyToUpdate) {
				$keyExists = true;
				$index = $i;
				break;
			}
		}
		if ($keyExists) {
			$value = intval($result['result']['extras'][$index]['value']) + 1;
			$value = str_pad($value, 8, '0', STR_PAD_LEFT);
			$result['result']['extras'][$index]['value'] = $value;
		} else {
			$value = str_pad(1, 8, '0', STR_PAD_LEFT);
			$data = array(
				"key" => $keyToUpdate,
				"value" => $value
			);
			array_push($result['result']['extras'], $data);
		}
		$callUrl = $this->urlCkan . "api/action/package_update";

		$return = $this->updateRequest($callUrl, $result['result'], "POST");
	}

	function updateRequest($callUrl, $binaryData, $requestType)
	{
		$jsonData = json_encode($binaryData);
		$cle = $this->config->ckan->api_key;
		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => $requestType,
			CURLOPT_POSTFIELDS => $jsonData,
			CURLOPT_HTTPHEADER => array(
				'Content-type:application/json',
				'Content-Length: ' . strlen($jsonData),
				'Authorization:  ' . $cle
			)
		);

		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $options);
		$result = curl_exec($curl);
		curl_close($curl);
		return $result;
	}

	function externalCallDatapusher($resourceId)
	{
		$callUrl = 'http://127.0.0.1:8800/job';
		$cle = $this->config->ckan->api_key;
		$url = $this->config->ckan->url;
		$binaryData['api_key'] = $cle;
		$binaryData['job_type'] = 'push_to_datastore';
		$binaryData['metadata']['resource_id'] = $resourceId;
		$binaryData['metadata']['ckan_url'] = $url;
		$binaryData['api_key'] = $cle;
		// error_log(json_encode( $binaryData ));
		$jsonData = json_encode($binaryData);

		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => $jsonData,
			CURLOPT_HTTPHEADER => array(
				'Content-type:application/json',
				'Content-Length: ' . strlen($jsonData)
			)
		);

		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $options);
		$result = curl_exec($curl);
		curl_close($curl);

		$response = new Response();
		$response->setContent('Done');
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	/**
	 * This method reload all resources from a Dataset in the Datastore
	 */
	function callDatapusher($resourceId)
	{
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

	function callDatapusherJobStatus($resourceId)
	{
		$result = $this->getDatapusherJobStatus($resourceId);

		echo $result;
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	/**
	 * To test you can curl -H "Authorization: SECRET_KEY" URL_DATAPUSHER/job/JOB_ID
	 */
	function getDatapusherJobStatus($resourceId)
	{
		Logger::logMessage("Get datapusher status for resource '" . $resourceId . "'");

		$database = \Drupal\Core\Database\Database::getConnection('ckan', 'ckan');
		$query = $database->query("SELECT id, entity_id, value, state, error FROM task_status WHERE entity_id = '" . $resourceId . "' and task_type = 'datapusher'");
		$task = $query->fetchAssoc();

		Logger::logMessage("Found task '" . json_encode($task) . "'");

		if ($task) {
			$jobValue = json_decode($task["value"], true);
			$jobId = $jobValue["job_id"];

			if (isset($jobId)) {
				$callUrl = $this->urlDatapusher . 'job/' . $jobId;
				Logger::logMessage("Getting datapusher infos '" . $callUrl . "'");

				$cle = $this->config->ckan->datapusher_key;
				$url = $this->config->ckan->url;

				$options = array(
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_CUSTOMREQUEST => 'GET',
					// CURLOPT_POSTFIELDS => $jsonData,
					CURLOPT_HTTPHEADER => array(
						'Authorization: ' . $cle
					)
				);

				$curl = curl_init($callUrl);
				curl_setopt_array($curl, $options);
				$result = curl_exec($curl);
				curl_close($curl);

				return $result;
			}

			$result = array();
			$result["status"] = "pending";
			return json_encode($result);
		}

		throw new \Exception("Impossible de trouver une tâche associée à la ressource '" . $resourceId . "'");
	}

	function sortDatasetbyKey($key)
	{
		//global $key ;
		//$key="nb_download";//"nb_download" "nb_views"
		$datasetList = $this->getPackageSearch("q=");
		//echo $datasetList->getContent() ;
		$data = json_decode($datasetList->getContent());
		$dataJson = $datasetList->getContent();
		// echo "<!Doctype html><html>";
		$key_found = false;
		$listbyKey = $this->getdatasetListByKey($key);
		if ($key == "nb_download") {
			usort($listbyKey, function ($a, $b) {
				$key = "nb_download"; //"nb_download";
				return $b[$key] - $a[$key];
			});
		} else if ($key == "nb_views") {
			usort($listbyKey, function ($a, $b) {
				$key = "nb_views"; //"nb_download";
				return $b[$key] - $a[$key];
			});
		}
		$result = $listbyKey;
		$response = new Response();
		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	function getdatasetListByKey($key)
	{
		$datasetList = $this->getPackageSearch("q=");
		$dataJson = $datasetList->getContent();
		//echo "$dataJson" ;
		$data = json_decode($datasetList->getContent());
		for ($i = 0; $i < count($data->result->results); $i++) {
			$id = $data->result->results[$i]->id;
			$title = $data->result->results[$i]->title;
			$key_found = false;
			$nb_download = 0;
			$theme = "";
			$nb_view = 0;
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
			for ($j = 0; $j < count($data->result->results[$i]->extras); $j++) {
				if ($data->result->results[$i]->extras[$j]->key == "nb_download") {
					$nb_download = $data->result->results[$i]->extras[$j]->value;
				}
				if ($data->result->results[$i]->extras[$j]->key == "nb_view") {
					$nb_view = $data->result->results[$i]->extras[$j]->value;
				}
				if ($data->result->results[$i]->extras[$j]->key == "theme") {
					$theme = $data->result->results[$i]->extras[$j]->value;
				}
			}
			$listbyKey[] = array(
				"id" => $id,
				"title" => $title,
				"nb_download" => $nb_download,
				"nb_view" => $nb_view,
				"theme" => $theme
			);
		}

		//var_dump($listbyKey);
		return $listbyKey;
	}

	function getThemeArray()
	{

		$listbyKey = $this->getdatasetListByKey("theme");

		for ($l = 0; $l < count($listbyKey); $l++) {

			$tmp = explode(',', $listbyKey[$l]["theme"]);
			for ($n = 0; $n < count($tmp); $n++) {
				$tmp[$n] = trim($tmp[$n]);
			}

			for ($k = 0; $k < count($tmp); $k++) {
				if (!in_array(trim($tmp[$k]), $themeList)) {
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
	function themebydownload()
	{

		$theme = $this->getThemeArray();
		$theme = json_decode($theme->getContent());
		$dataset = $this->getdatasetListByKey("theme");
		$list = array();
		//var_dump($theme);
		//($dataset);
		for ($i = 0; $i < count($theme); $i++) {

			$list[$i] = array(
				"theme" => $theme[$i],
				"nb_download" => 0
			);

			for ($j = 0; $j < count($dataset); $j++) {

				if ($dataset[$j]['theme'] != null) {
					$tmp = explode(',', $dataset[$j]['theme']);
					$tmp = $this->trim_array($tmp);
					//$dataset[$j]['theme'] !=null 
					if (in_array($theme[$i], $tmp)) {
						//echo $theme[$i] ." existe dans ".$dataset[$j]['title'] ."\r";
						$list[$i]["nb_download"] =    $list[$i]["nb_download"] + $dataset[$j]['nb_download'];
					}
				}
			}
		}

		usort($list, function ($a, $b) {
			$key = "nb_download"; //"nb_download";
			return $b[$key] - $a[$key];
		});

		$result = $list;
		//$result = $dataset;
		$response = new Response();
		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	function datasetByTheme($theme)
	{
		$datasetList = $this->callPackageSearch_public_private('include_private=true&rows=10000');
		$datasetList = $datasetList->getContent();

		$dataset_list = json_decode($datasetList)->result->results;

		$selectedDataset = array();
		for ($i = 0; $i < count($dataset_list); $i++) {

			$theme_found = false;
			for ($j = 0; $j < count($dataset_list[$i]->extras); $j++) {

				if ($dataset_list[$i]->extras[$j]->key == "theme") {
					$dataset_theme = $dataset_list[$i]->extras[$j]->value;
					if (stristr(trim($dataset_theme), trim($theme)) !== False) $selectedDataset[] = $dataset_list[$i];
					$theme_found = true;
				}

				if ($dataset_list[$i]->extras[$j]->key == "themes") {
					$themes = $dataset_list[$i]->extras[$j]->value;

					if (stristr(trim($themes), trim($theme)) !== False) {
						$selectedDataset[] = $dataset_list[$i];
					}
					$theme_found = true;
				}
			}

			if ($theme == "default"  && $theme_found == false) {
				$selectedDataset[] = $dataset_list[$i];
			}
		}

		$response = new Response();
		$response->setContent(json_encode($selectedDataset));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	function trim_array($array)
	{
		$res = array();
		for ($n = 0; $n < count($array); $n++) {
			$res[] = trim($array[$n]);
		}
		return $res;
	}

	function callSearchArcGIS($params)
	{
		if ($params == '') {
			$query_params = $_POST;
		} else {
			$query_params = $this->proper_parse_str($params);
		}
		$result = Query::callSolrServer($query_params["url"] . "?f=pjson");
		//error_log($result); 
		//error_log("https://".$params[0] . $this->config->client->routing_prefix . "/d4c/api/datasets/1.0/search/?q=".$params[1]);

		$response = new Response();
		$response->setContent($result);
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	function getAllOrganisations($allFields = TRUE, $include_extra = FALSE, $applySecurity = true)
	{
		$callUrlOrg =  $this->urlCkan . "api/action/organization_list?all_fields=" . ($allFields ? 'true' : 'false') . "&include_extras=" . ($include_extra ? 'true' : 'false');

		$curlOrg = curl_init($callUrlOrg);
		curl_setopt_array($curlOrg, $this->getSimpleOptions());
		$orgs = curl_exec($curlOrg);
		curl_close($curlOrg);
		$orgs = json_decode($orgs, true);
		$orgs = $orgs["result"];

		if ($this->isObservatory() && $applySecurity) {
			$allowedOrganizations = $this->getObservatoryOrganisations();
			if (!$allFields && !$include_extra) {
				foreach ($orgs as $org) {
					if (!$this->isOrganizationAllowed($org, $allowedOrganizations)) {
						if (($key = array_search($org, $orgs)) !== false) {
							unset($orgs[$key]);
						}
					}
				}
			}
			else {
				foreach ($orgs as $valueKey => $org) {
					if (!$this->isOrganizationAllowed($org["name"], $allowedOrganizations)) {
						unset($orgs[$valueKey]);
					}
				}
			}
		}
		else if ($applySecurity) {
			$allowedOrganizations = $this->getUserOrganisations();
			if (!$allFields && !$include_extra) {
				foreach ($orgs as $org) {
					if (!$this->isOrganizationAllowed($org, $allowedOrganizations)) {
						if (($key = array_search($org, $orgs)) !== false) {
							unset($orgs[$key]);
						}
					}
				}
			}
			else {
				foreach ($orgs as $valueKey => $org) {
					if (!$this->isOrganizationAllowed($org["name"], $allowedOrganizations)) {
						unset($orgs[$valueKey]);
					}
				}
			}
		}

		return $orgs;
	}

	function callAllOrganisations($params)
	{
		$query_params = $this->proper_parse_str($params);
		$all_fields = FALSE;
		$include_extras = FALSE;
		if ($query_params["all_fields"]) {
			$all_fields = TRUE;
		}
		if ($query_params["include_extras"]) {
			$include_extras = TRUE;
		}
		$result = $this->getAllOrganisations($all_fields, $include_extras);
		$response = new Response();
		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	function calculateVisualisations($id, $blockDateModification = FALSE) {
		Logger::logMessage("Calculate visualisation for " . $id);

		$count = 0;

		$dataset = $this->getPackageShow("id=" . $id);
		$dataset = $dataset["result"];

		$hasTimeserie = false;
		$hasAnalyze = false;
		$hasGeo = false;
		$hasImage = false;
		$hasCalendar = false;
		$hasWordcloud = false;
		$hasTimeline = false;
		$hasApi = false;
		$hasTable = false;

		foreach ($dataset['resources'] as $value) {
			if (($value['format'] == 'CSV' || $value['format'] == 'XLS' || $value['format'] == 'XLSX') && $value["datastore_active"] == true) {
				$resourcesid = $value['id'];

				$resourceCount = $this->getDatastoreApi("resource_id=" . $resourcesid . "&limit=0");
				$resourceCount = $resourceCount["result"]["total"];
				$count += $resourceCount;

				Logger::logMessage("Found ressource with id " . $resourcesid . " with record count = " . $resourceCount);

				$fields = $this->getAllFields($resourcesid, TRUE);
				if (count($fields) > 0) {
					Logger::logMessage("Searching features for resource " . $resourcesid);
		
					$hasStart = false;
					$hasEnd = false;
					foreach ($fields as $f) {
						foreach ($f["annotations"] as $a) {
							if ($a["name"] == "startDate") {
								$hasStart = true;
							}
							else if ($a["name"] == "endDate") {
								$hasEnd = true;
							}
							else if ($a["name"] == "date") {
								$hasStart = true;
								$hasEnd = true;
							}
							else if ($a["name"] == "wordcount" || $a["name"] == "wordcountNumber") {
								$hasWordcloud = true;
							}
							else if ($a["name"] == "date_timeLine"  || $a["name"] == "title_for_timeLine"  || $a["name"] == "descr_for_timeLine") {
								$hasTimeline = true;
							}
						}
						if ($f["type"] == "file") {
							$hasImage = true;
						}
						if ($f["type"] == "geo_point_2d"  || $f["type"] == "geo_shape") {
							$hasGeo = true;
						}
					}
		
					if ($hasStart && $hasEnd) {
						$hasTimeserie = true;
						$hasCalendar = true;
					}

					$hasApi = true;
					$hasAnalyze = true;
					$hasTable = true;
				}
			}

			//Checking if we have a WMS resource to add the feature geo
			if (strcasecmp($value['format'], "WMS") == 0) {
				$hasGeo = true;
			}
		}
		
		foreach ($dataset['extras'] as $value) {
			if ($value["key"] == "dont_visualize_tab") {
				if (strpos($value["value"], "api") === false) {
					$hasApi = false;
				}
				if (strpos($value["value"], "analize") === false) {
					$hasAnalyze = false;
				}
				break;
			}
		}

		$features = array(); //["timeserie", "analyze", "geo", "image", "calendar", "custom_view","wordcloud", timeline]
		if ($hasTimeserie)
			$features[] = "timeserie";
		if ($hasAnalyze)
			$features[] = "analyze";
		if ($hasGeo)
			$features[] = "geo";
		if ($hasImage)
			$features[] = "image";
		if ($hasCalendar)
			$features[] = "calendar";
		if ($hasWordcloud)
			$features[] = "wordcloud";
		if ($hasTimeline)
			$features[] = "timeline";
		if ($hasApi)
			$features[] = "api";
		if ($hasTable)
			$features[] = "table";

		$records_count = str_pad($count, 10, "0", STR_PAD_LEFT);

		$customView = $this->getCustomView($dataset['id']);
		if ($customView) {
			Logger::logMessage("Found custom view");
			$features[] = "custom_view";
		}

		$extras = $dataset["extras"];
		$foundFeat = false;
		$foundCount = false;
		$foundCV = false;
		$foundLM = false;
		foreach ($extras as &$e) {
			if ($e["key"] == "records_count") {
				$e["value"] = $records_count;
				$foundCount = true;
			} else if ($e["key"] == "features") {
				$e["value"] = implode(",", $features);
				$foundFeat = true;
			} else if ($e["key"] == "custom_view" && $customView != null) {
				$cv = array();
				$cv["title"] = $customView->cv_title;
				$cv["slug"] = $customView->cv_name;
				$cv["icon"] = $customView->cv_icon;
				$e["value"] = json_encode($cv);
				$foundCV = true;
			} else if ($e["key"] == "date_moissonnage_last_modification") {
				$foundLM = true;
			}
		}
		if (!$foundCount) {
			$extras[count($extras)]['key'] = 'records_count';
			$extras[(count($extras) - 1)]['value'] = $records_count;
		}
		if (!$foundFeat) {
			$extras[count($extras)]['key'] = 'features';
			$extras[(count($extras) - 1)]['value'] = implode(",", $features);
		}
		if (!$foundCV && $customView != null) {
			$extras[count($extras)]['key'] = 'custom_view';
			$cv = array();
			$cv["title"] = $customView->cv_title;
			$cv["slug"] = $customView->cv_name;
			$cv["icon"] = $customView->cv_icon;
			$extras[(count($extras) - 1)]['value'] = json_encode($cv);
		}
		if (!$foundLM) {
			$extras[count($extras)]['key'] = 'date_moissonnage_last_modification';
			$extras[(count($extras) - 1)]['value'] = $dataset["metadata_modified"];
		}
		$dataset["extras"] = $extras;
		if ($blockDateModification) {
			$dataset["modified_date_forced"] = true;
		}

		$callUrl = $this->urlCkan . "api/action/package_update";
		$this->updateRequest($callUrl, $dataset, "POST");
	}

	function callPackageSearchDownload($format, $params)
	{
		$query_params = $this->proper_parse_str($params);
		$query_params["rows"] = 1000;
		$query_params["start"] = 0;
		unset($query_params["facet.field"]);

		$params = "";
		foreach ($query_params as $key => $value) {
			$params .= $key . "=" . $value . "&";
		}
		$params = substr($params, 0, -1);
		//$params = implode("&",$query_params);
		//$params = http_build_query($query_params);

		$result = $this->getExtendedPackageSearch($params);

		foreach ($result["result"]["results"] as &$dataset) {
			$dataset["metadata_imported"] = $dataset["metadata_modified"];
			$dataset["metadata_modified"] = current(array_filter($dataset["extras"], function ($f) {
				return $f["key"] == "date_moissonnage_last_modification";
			}))["value"] ?: $dataset["metadata_modified"];
			$dataset["metadata_created"] = current(array_filter($dataset["extras"], function ($f) {
				return $f["key"] == "date_moissonnage_creation";
			}))["value"] ?: $dataset["metadata_created"];

			foreach ($dataset["resources"] as $j => $value) {
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

			$reader = ReaderEntityFactory::createCSVReader();
			// $reader->setFieldDelimiter(';');
			// $reader->setFieldEnclosure('"');
			// $reader->setEndOfLineCharacter("\n");

			$writer = WriterEntityFactory::createXLSXWriter();

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
					$row->setStyle($style);
					$writer->addRow($row);
				}
			} //$writer->addRows($multipleRows); // add multiple rows at a time

			$reader->close();
			$writer->close();

			unlink($pathInput);

			header('Content-Length: ' . filesize($pathOutput));
			readfile($pathOutput);
		} else {
			echo json_encode($result);
		}

		$response = new Response();
		return $response;
	}

	function callCalculateVisualisations($id)
	{
		$this->calculateVisualisations($id);
		$response = new Response();
		return $response;
	}

	function reBuildAllDataset()
	{
		$allDatasets = $this->callPackageSearch_public_private("include_private=true&rows=10000");
		$allDatasets = $allDatasets->getContent();
		$allDatasets = json_decode($allDatasets, true);
		if ($allDatasets["success"] == true) {
			foreach ($allDatasets["result"]["results"] as $d) {
				$this->calculateVisualisations($d["id"], TRUE);
			}
			//echo count($allDatasets["result"]["results"]);
		}

		$response = new Response();
		return $response;
	}

	function callGetReuses($datasetid)
	{
		$method = $_SERVER['REQUEST_METHOD'];

		$dataset = $this->getPackageShow2($datasetid, "");
		//We define the dataset ID with the name
		$datasetid = $dataset["datasetid"];

		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');

		$data_array = array();

		switch ($method) {
			case 'POST':
				$data = array();

				$r = date_default_timezone_set('Europe/Paris');

				if ($_POST["recaptcha_response"] != "") {
					//check captcha
					$callUrl =  "https://www.google.com/recaptcha/api/siteverify";
					$data_string = array();
					$data_string["secret"] = "6LcT58UaAAAAAM3TgHCvTYpTv0ziCuOfGrfUGUt0";
					$data_string["response"] = $_POST["recaptcha_response"];

					$curl = curl_init($callUrl);
					curl_setopt_array($curl, $this->getSimpleOptions());
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
					curl_setopt(
						$ch,
						CURLOPT_HTTPHEADER,
						array(
							'Content-Type: application/json',
							'Content-Length: ' . strlen($data_string)
						)
					);
					$resp = curl_exec($curl);
					curl_close($curl); //error_log($resp);
					$resp = json_decode($resp, true);
					if ($resp["success"] == false) {
						$data_array["status"] = "captcha_failed";
						$data_array["message"] = json_encode($resp["error-codes"]);
						echo json_encode($data_array);
						return $response;
					}
				}

				if ($_FILES['file']['size'] > 0) {
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
					$uploaddir = $this->config->client->drupal_root . '/sites/default/files/reuses/';
					if (!file_exists($uploaddir)) {
						mkdir($uploaddir, 0777, true);
					}
					$uploadfile = $uploaddir . basename($_FILES['file']['name']);

					if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
						error_log("Le fichier est valide, et a été téléchargé avec succès. Voici plus d'informations :\n");
						$protocol = /*isset($_SERVER['HTTPS']) ? */ 'https://' /*: 'http://'*/;
						$url = $protocol . $_SERVER['HTTP_HOST'] . $this->config->client->routing_prefix . '/sites/default/files/reuses/' . basename($_FILES['file']['name']);
					} else {
						error_log("Attaque potentielle par téléchargement de fichiers. Voici plus d'informations :\n");
					}
					//error_log(json_encode($_FILES));
					$data["image"] = $url;
				} else {
					$data["image"] = $_POST["image"];
				}

				$name = str_replace(" ", "-", strtolower($_POST["title"]));

				if (\Drupal::currentUser()->isAuthenticated()) {
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
				// $data["date"] = date("d/m/Y H:i:s");
				$data["date"] = date("Y-m-d H:i:s");
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

				$params['message'][] = t(
					'Une réutilisation "@name" a été déposée par @author sur le site @sitename, et est en attente de validation de votre part.',
					array('@sitename' => $sitename, '@name' => $name, '@author' => $data["author_email"])
				);
				$params['message'][] = t('Titre de la réutilisation : @name', array('@name' => $name));
				$params['message'][] = t('Jeu de données concerné : @name', array('@name' => $data["dataset_title"]));
				$params['message'][] = t('Traiter la réutilisation : @url', array('@url' => "https://" . $_SERVER['HTTP_HOST'] . $this->config->client->routing_prefix . "/admin/config/data4citizen/reusesManagement"));
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

	function getReuses($orga = null, $dataset = null, $q = null, $status = null, $rows = null, $start = null)
	{

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

		if ($dataset != null && $dataset != "") {
			$query->condition('reu_dataset_id', $dataset);
		} else if ($orga != null && $orga != "") {

			$req = "include_private=true&rows=10000&q=organization:" . $orga;

			$datasets = $this->getPackageSearch($req)["result"]["results"]; //error_log(json_encode($datasets));
			$ids = array();

			foreach ($datasets as $row) {
				$ids[] = $row["name"];
			}
			//$ids = implode(",", $ids);
			$query->condition('reu_dataset_id', $ids, "IN");
		}

		if ($q != null && $q != "") {
			$orGroup = $query->orConditionGroup()
				->condition('reu_title', '%' . \Drupal::database()->escapeLike($q) . '%', 'LIKE')
				->condition('reu_description', '%' . \Drupal::database()->escapeLike($q) . '%', 'LIKE');

			$query->condition($orGroup);
		}
		if ($status != null && $status != "") {
			if ($status == "waiting") {
				$s = 0;
			} else if ($status == "online") {
				$s = 1;
			} else if ($status == "offline") {
				$s = 2;
			}
			$query->condition('reu_status', $s);
		}
		if ($rows != null && $rows != "") {
			if ($start != null && $start != "") {
				$query->range($start, $start + $rows);
			} else {
				$query->range(0, $rows);
			}
		}
		$query->orderBy('reu_date', 'DESC');

		$prep = $query->execute();
		//$prep->setFetchMode(PDO::FETCH_OBJ);
		$res = array();
		while ($enregistrement = $prep->fetch()) {
			array_push($res, $enregistrement);
		}

		$query->range();
		$nhits = $query->countQuery()->execute()->fetchField();

		if (count($res) > 0) {
			$data = array();
			$data["nhits"] = $nhits;

			foreach ($res as $reu) {
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
				if ($reu->reu_status == 0) {
					$row['status'] = "waiting";
				} else if ($reu->reu_status == 1) {
					$row['status'] = "online";
				} else if ($reu->reu_status == 2) {
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

	function getReuse($id)
	{
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

		$query->condition('reu_id', $id);
		$prep = $query->execute();
		//$prep->setFetchMode(PDO::FETCH_OBJ);
		$res = array();
		while ($enregistrement = $prep->fetch()) {
			array_push($res, $enregistrement);
		}
		if (count($res) > 0) {
			$reu = $res[count($res) - 1];
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
			if ($reu->reu_status == 0) {
				$row['status'] = "waiting";
			} else if ($reu->reu_status == 1) {
				$row['status'] = "online";
			} else if ($reu->reu_status == 2) {
				$row['status'] = "offline";
			}
			$row['type'] = $reu->reu_type;
			return $row;
		} else {
			return null;
		}
	}

	function updateReuse($reuse)
	{
		$reu_id = $reuse["id"];

		// Statut delete
		if ($reuse["status"] == 3) {
			$query = \Drupal::database()->delete('d4c_reuses');
			$query->condition('reu_id', $reu_id);
			$query->execute();
		} else {
			$query = \Drupal::database()->update('d4c_reuses');
			$query->fields([
				'reu_status' => $reuse["status"]
			]);
			$query->condition('reu_id', $reu_id);
			$query->execute();
		}
	}

	public function addReuse($params)
	{
		if (is_array($params)) {
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

	function callOrangeApiGetData($params)
	{
		//ex : /webservice/?service=getData&key=nXG9o1MSJxHbs1qH&db=stationnement&table=disponibilite_parking&format=json

		$query_params = $this->proper_parse_str($params);
		$response = new Response();
		$contentType = ($query_params['format'] == 'xml') ? 'application/xml' : 'application/json; charset=utf-8';
		$limit = (isset($query_params['limit'])) ? $query_params['limit'] : 1000;
		$response->headers->set('Content-Type', $contentType);
		if ($query_params["service"] != "getData") {
			echo "Ce service n'est pas supporté";
			$response->setStatusCode(404);
		} else {
			$cle = $query_params["key"];
			$db = $query_params["db"];
			$dataset = $query_params["table"];
			$format = $query_params["format"];
			// $limit = $query_params["limit"];
			$offset = $query_params["offset"];
			$start = "";
			$rows = "";
			if ($limit != null && $limit != "") {
				if ($offset != null && $offset != "") {
					$rows = "&rows=" . ($limit - $offset);
				} else {
					$rows = "&rows=" . $limit;
				}
			}
			if ($offset != null && $offset != "") {
				$start = "&start=" . $offset;
			}

			$res = $this->getDatastoreRecord_v2("dataset=" . $dataset . $rows . $start);
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
			if ($res["status"] == "success") {
				$status["code"] = 0;
				$status["message"] = "Success";
				$answer["status"] = $status;

				$data = array();
				//$data = array_column($res["records"], "fields");
				$data = array_map(function ($d) {
					unset($d["fields"]["_full_text"]);
					unset($d["fields"]["_id"]);
					return $d["fields"];
				}, $res["records"]);
				$answer["data"] = $data;
			} else {
				$status["code"] = 7;
				$status["message"] = "Une erreur s'est produite lors de l’exécution de la requête.";
				$answer["status"] = $status;
			}

			$opendata["request"] = $url;
			$opendata["answer"] = $answer;
			$result["opendata"] = $opendata;

			if ($query_params['format'] == 'xml'){


                $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><opendata/>');

                $status="";
                $attributs = array();

                array_walk_recursive($result, function ($value, $key) use ($xml) {
                    // Conformation de l'enveloppe à la façon de l'ancienne API La Rochelle avril 2020
                    global $status, $answer, $data, $row, $attributs, $nextRowAttribut;
                    if(!in_array($key,$attributs)){$attributs[]=$key;}
                    else if(!isset($nextRowAttribut)){$nextRowAttribut=$key;$row = $data->addChild("row");}
                    else if($key==$nextRowAttribut){$row = $data->addChild("row");}
                    if($key=="request"){$xml->addChild($key, htmlspecialchars($value));$answer = $xml->addChild("answer");}
                    else if($key=="code"){$status.="code=".$value;}
                    else if($key=="resume" || $key=="texte"){$row->addChild($key, str_replace("nbsp","#160",$value));}
                    else if($key=="message"){$status.=" message=\"".$value."\""; $answer->addChild("status", $status);$data = $answer->addChild("data");$row = $data->addChild("row");}
                    else if($key!="total_count"){$row->addChild($key, $value);}
               });

               echo $xml->asXML();

            }
			else{
                  echo json_encode($result);
            }

		}


		return $response;
	}

	function updateResourceAndPushDatastore($resource)
	{

		$callUrl =  $this->urlCkan . "api/action/datastore_search?resource_id=" . $resource["id"] . "&limit=0";

		//echo $callUrl;
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl); //error_log($result);
		$fields = json_decode($result, true)["result"]["fields"];

		$resource["uuid"] = uniqid();
		$callUrl =  $this->urlCkan . "api/action/resource_update";
		$return = $this->updateRequest($callUrl, $resource, "POST");
		//error_log($return);
		$fields2 = array();
		foreach ($fields as $f) {
			if ($f["id"] != "_id") {
				$fields2[] = $f;
			}
		}
		for ($i = 0; $i < 1; $i++) {
			sleep(10);
			$callUrl =  $this->urlCkan . "api/action/datastore_create";
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

	public function calculValueFromFiltre()
	{

		$req = array();

		$where = "";
		if ($_POST['colonne_filtre'] && $_POST['valeur_filtre'] && $_POST['colonne_filtre'] != null && $_POST['valeur_filtre'] != null) {
			$where = " where ";
			$where .= $_POST['colonne_filtre'] . " IN ( '" . $_POST['valeur_filtre'] . "' )";
		}

		$sql = "Select " . $_POST['operation'] . "(" . $_POST['colonne'] . ") as result from \"" . $_POST['idRes'] . "\"" . $where;

		$req['sql'] = $sql;

		$url2 = http_build_query($req);


		$callUrl =  $this->urlCkan . "api/action/datastore_search_sql?" . $url2;
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);

		curl_close($curl);
		$result = json_decode($result, true);


		$response = new Response(json_encode(array('result' => $result["result"]["records"][0]["result"])));



		return $response;
	}

	function _get_id($tableName, $fieldName)
	{

		$select = \Drupal::database()->select($tableName, 'o');
		$fields = array(
			$fieldName,
		);
		$select->fields('o', $fields);
		$result = $select->orderBy($fieldName)->execute()->fetchAll();
		return (int)$result[sizeof($result) - 1]->story_id;
	}


	public function addWidget($params, $lastid_story)
	{



		/*var_dump($imgUrl);*/
		$query_widget = \Drupal::database()->insert('d4c_user_story_widget');
		$query_widget->fields([
			'widget_label',
			'widget',
			'story_id',
			'image'
		]);
		$query_widget->values([
			$params["label_widget"],
			$params["widget"],
			$lastid_story,
			$params["urlimg"]

		]);



		$query_widget->execute();
	}
	public function addStory($params)
	{
		if (is_array($params)) {
			$story = $params;
		} else {
			$story = $this->proper_parse_str($params);
		}

		$query = \Drupal::database()->insert('d4c_user_story');


		$scrolltime = (int)$story["scrolling_time"];

		$query->fields([
			'scroll_time',
			'title_story'
		]);
		$query->values([
			$scrolltime,
			$story["title_story"]

		]);

		$query->execute();
		$lastId = $this->_get_id('d4c_user_story', 'story_id');

		foreach ($story["widget"] as $key => $value) {

			$this->addWidget($value, $lastId);
		}
	}

	function updatewidget($widget)
	{
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
	function updateStory($story)
	{


		$story_id = $story["story_id"];
		$query = \Drupal::database()->update('d4c_user_story');
		$query->fields([
			'scroll_time' => (int)$story["scrolling_time"],
			'title_story' => $story["title_story"]
		]);

		$query->condition('story_id', $story_id);
		$query->execute();

		$widgets = $this->getWidgetByStory($story_id);
		foreach ($widgets as $key => $value) {
			$this->deleteWidget($value->widget_id);
		}

		foreach ($story["widget"] as $key => $value) {

			$this->addWidget($value, $story_id);
		}
	}

	public function getStories()
	{
		$res = array();
		$table = "d4c_user_story";
		$query2 = \Drupal::database()->select($table, 'story');


		$query2->fields('story', [
			'story_id',
			'scroll_time',
			'title_story'
		]);


		$prep = $query2->execute();
		$res = array();
		while ($enregistrement = $prep->fetch()) {
			array_push($res, $enregistrement);
		}

		return $res;
	}

	public function getWidgets()
	{
		$res = array();
		$table = "d4c_user_story_widget";
		$query2 = \Drupal::database()->select($table, 'widget');


		$query2->fields('widget', [
			'widget_id',
			'widget_label',
			'widget',
			'story_id',
			'image'
		]);


		$prep = $query2->execute();
		$res = array();
		while ($enregistrement = $prep->fetch()) {
			array_push($res, $enregistrement);
		}

		return $res;
	}

	function getWidgetByStory($story_id)
	{
		$table = "d4c_user_story_widget";
		$query = \Drupal::database()->select($table, 'widget');

		$query->fields('widget', [
			'widget_id',
			'widget_label',
			'widget',
			'story_id',
			'image'
		]);

		$query->condition('story_id', $story_id);
		$prep = $query->execute();
		//$prep->setFetchMode(PDO::FETCH_OBJ);
		$res = array();
		while ($enregistrement = $prep->fetch()) {
			array_push($res, $enregistrement);
		}
		return $res;
	}





	function deleteWidget($widget_id)
	{

		$query_widget = \Drupal::database()->delete('d4c_user_story_widget');
		$query_widget->condition('widget_id', $widget_id);
		$query_widget->execute();
	}


	function deleteStory($story_id)
	{

		$query = \Drupal::database()->delete('d4c_user_story');

		$widgets = $this->getWidgetByStory($story_id);
		foreach ($widgets as $key => $value) {
			$this->deleteWidget($value->widget_id);
		}

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
	function updateDatabaseStatus($isNew, $uniqId, $entityId, $entityType, $taskType, $action, $status, $message)
	{
		if ($entityId) {
			Logger::logMessage("Updating task status for resource '" . $entityId . "' \r\n");
		} else {
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
		} else {
			$query = \Drupal::database()->update($table);
			$query->fields([
				'entity_id' => $entityId,
				'action' => $action,
				'status' => $status,
				'message' => $message,
				'last_updated' => 'now'
			]);
			$query->condition($query->orConditionGroup()
				->condition('id', $uniqId)
				->condition('entity_id', $uniqId));
		}

		$query->execute();
	}

	/**
	 * This method retrieve the status for the last dataset integration define by the dataset ID
	 * 
	 */
	function getTaskStatus($id)
	{
		$table = "dpl_d4c_task_status";

		// $database = \Drupal\Core\Database\Database::getConnection('ckan', 'ckan');
		$sqlQuery = "SELECT action, status, message FROM " . $table . " WHERE (id = '" . $id . "' OR entity_id = '" . $id . "') and task_type = 'MANAGE_DATASET'";

		$query = \Drupal::database()->query($sqlQuery);
		$task = $query->fetchAssoc();

		if ($task) {
			$action = $task["action"];
			$status = $task["status"];
			$message = $task["message"];

			$status = [
				"id" => $id,
				"action" => $action,
				"status" => $status,
				"message" => $message
			];
		} else {
			$status = [
				"id" => $id,
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

	public function callPackageReutilisation($params)
	{
		$reuses = $this->getReuses(null, null, null, "online", 1000, 0);

		$response = new Response();
		$response->setContent(json_encode($reuses));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	function deleteDataset($datasetId)
	{
		$ressourceManager = new ResourceManager();
		$result = $ressourceManager->deleteDataset($datasetId);

		if ($result) {
			return new Response("true");
		} else {
			throw new \Exception('Impossible de supprimer le dataset (' . $datasetId . ' is not supported.');
		}
	}

	function getThesaurus($params)
	{
		$query_params = $this->proper_parse_str($params);
		$spam_words = file(__DIR__ . "/../../thesaurus.txt", FILE_IGNORE_NEW_LINES);

		Logger::logMessage('Thesaurus with params ' . $query_params["query"]);

		$words = array();
		foreach ($spam_words as $word) {

			if (strpos($word, $query_params["query"]) === 0) {
				$wordValue = array();
				$wordValue["value"] = $word;
				$wordValue["data"] = $word;
				$words[] = $wordValue;
			}
		}

		$suggestions = ["suggestions" => $words];

		$response = new Response();
		$response->setContent(json_encode($suggestions));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	public function callSearchDatasets()
	{
		$query = $_POST['q'];

		try {
			$datasets = $this->searchDataset($query);

			$result["result"] = $datasets;
			$result["status"] = "success";
		} catch (\Exception $e) {
			Logger::logMessage($e->getMessage());
			$data_array = array();
			$data_array["message"] = $e->getMessage();

			$result["result"] = $data_array;
			$result["status"] = "error";
		}

		$response = new Response();
		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	private function searchDataset($params)
	{
		$callUrl =  $this->urlCkan . "api/action/package_search?" . $params;

		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$dataset = curl_exec($curl);
		curl_close($curl);

		$dataset = json_decode($dataset, true);
		return $dataset[result];
	}

	public function callFindDataset()
	{
		$datasetId = $_POST['dataset_id'];

		try {
			// Cleaning dataset_id if we search by name
			$ressourceManager = new ResourceManager();
			$datasetId = $ressourceManager->defineDatasetName($datasetId);
			$dataset = $this->findDataset($datasetId);

			$result["result"] = $dataset;
			$result["status"] = "success";
		} catch (\Exception $e) {
			Logger::logMessage($e->getMessage());
			$data_array = array();
			$data_array["message"] = $e->getMessage();

			$result["result"] = $data_array;
			$result["status"] = "error";
		}

		$response = new Response();
		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	public function findDataset($datasetId)
	{
		Logger::logMessage("findDataset with id = $datasetId");

		$callUrl =  $this->urlCkan . "api/action/package_show?id=" . $datasetId;

		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$dataset = curl_exec($curl);
		curl_close($curl);

		$dataset = json_decode($dataset, true);
		return $dataset[result];
	}

	public function callRemoveDataset()
	{
		$datasetId = $_POST['dataset_id'];

		try {
			Logger::logMessage("Delete dataset " . $datasetId);
			$this->deleteDataset($datasetId);

			$result["status"] = "success";
		} catch (\Exception $e) {
			Logger::logMessage($e->getMessage());
			$data_array = array();
			$data_array["message"] = $e->getMessage();

			$result["result"] = $data_array;
			$result["status"] = "error";
		}

		$response = new Response();
		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	public function callRemoveResources()
	{
		$datasetId = $_POST['dataset_id'];

		try {
			$resourceManager = new ResourceManager;

			Logger::logMessage("Delete dataset resources " . $datasetId);
			$resourceManager->deleteDatasetResources($datasetId);

			$result["status"] = "success";
		} catch (\Exception $e) {
			Logger::logMessage($e->getMessage());
			$data_array = array();
			$data_array["message"] = $e->getMessage();

			$result["result"] = $data_array;
			$result["status"] = "error";
		}

		$response = new Response();
		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	public function callManageDataset()
	{
		Logger::logMessage("Create or update dataset by API");
		// Taking too much time, we now load only admin users
		// $users = \Drupal\user\Entity\User::loadMultiple();

		$users = $this->getAdministrators();

		$resourceManager = new ResourceManager;

		$datasetName = $_POST['name'];
		$title = $_POST['title'];
		$description = $_POST['description'];
		$licence = $_POST['selected_lic'];
		$organization = $_POST['selected_org'];
		$isPrivate = $_POST['selected_private'] == "true" ? true : false;
		$extrasAsJson = $_POST['extras'];
		$tagsAsJson = $_POST['tags'];

		//Options for update
		$datasetId = $_POST['dataset_id'];

		// Define Dataset name
		if (!isset($datasetName)) {
			$datasetName = $resourceManager->defineDatasetName($title);
		}
		else {
			//Cleaning datasetName
			$datasetName = $resourceManager->defineDatasetName($datasetName);
		}

		// Define security
		$security = $resourceManager->defineSecurity(null, $users);

		// We build extras
		if (isset($extrasAsJson)) {
			$extrasAsJson = json_decode($extrasAsJson, true);
			$extras = array();
			foreach ($extrasAsJson as $key => $value) {
				$extraValue = array();
				$extraValue['key'] = $key;
				$extraValue['value'] = $value;
				$extras[] = $extraValue;
			}
		}

		$tags = $resourceManager->defineTags(json_decode($tagsAsJson, true));

		//We disable defining extras for now and use the one in the dataset
		if ($extras == null) {
			$extras = array();
		}

		$generatedTaskId = uniqid();
		try {
			if (!$datasetId) {
				if ($extras != null && count($extras) > 0) {
					for ($index = 0; $index < count($extras); $index++) {
						if ($extras[$index]['key'] == 'edition_security') {
							$hasSecurity = true;
							break;
						}
					}
				}

				if ($hasSecurity == false) {
					$extras[count($extras)]['key'] = 'edition_security';
					$extras[(count($extras) - 1)]['value'] = json_encode($security);
				}

				$datasetId = $resourceManager->createDataset($generatedTaskId, $datasetName, $title, $description, $licence, $organization, $isPrivate, $tags, $extras);
			}
			else {
				$datasetToUpdate = $this->findDataset($datasetId);

				$datasetName = $datasetToUpdate[name];

				$existingMetadata = $datasetToUpdate[extras];

				$keyAlreadyAdd = array();

				$updatedExtras = array();
				foreach ($existingMetadata as $meta) {
					$key = $meta['key'];
					$value = $meta['value'];

					$keyAlreadyAdd[] = $key;
					$updatedExtras[count($updatedExtras)]['key'] = $key;

					// Checking if value exist in extras
					$extraValue = array_filter($extras, function ($f) use ($key) {
						return $f["key"] == $key;
					});

					$value = isset($extraValue) && count($extraValue) > 0 ? array_values($extraValue)[0]["value"] : $value;
					$updatedExtras[(count($updatedExtras) - 1)]['value'] = $value;
				}

				foreach ($extras as $meta) {
					$key = $meta['key'];
					$value = $meta['value'];

					// Check if key not in $keyAlreadyAdd or continue
					if (in_array($key, $keyAlreadyAdd)) {
						continue;
					}

					$updatedExtras[count($updatedExtras)]['key'] = $key;
					$updatedExtras[(count($updatedExtras) - 1)]['value'] = $value;
				}

				$datasetId = $resourceManager->updateDataset($generatedTaskId, $datasetId, $datasetToUpdate, $datasetName, $title, $description, $licence, $organization, $isPrivate, $tags, $updatedExtras);
			}

			$result["result"] = $datasetId;
			$result["status"] = "success";
		} catch (\Exception $e) {
			Logger::logMessage($e->getMessage());
			$data_array = array();
			$data_array["message"] = $e->getMessage();

			$result["result"] = $data_array;
			$result["status"] = "error";
		}

		$response = new Response();
		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	public function callUploadResource()
	{
		Logger::logMessage("Upload resource by API");

		$result = array();

		$resourceManager = new ResourceManager;

		$datasetId = $_POST['selected_data_id'];

		$resourceName = $_POST['resource_name'];
		$resourceUrl = $_POST['resource_url'];

		$description = $_POST['description'];
		$format = $_POST['format'];
		$encoding = $_POST['encoding'];
		$unzipZip = $_POST['unzip_zip'] == "true" ? true : false;
		$manageFile = $_POST['manage_file'] == "true" ? true : false;
		$createCsv = $_POST['create_csv'] == "true" ? true : false;

		//Options for update
		$resourceId = $_POST['selected_resource_id'];

		Logger::logMessage("Upload informations");
		Logger::logMessage("Dataset ID: " . $datasetId);
		Logger::logMessage("Resource name: " . $resourceName);
		Logger::logMessage("Resource URL: " . $resourceUrl);
		Logger::logMessage("Description: " . $description);
		Logger::logMessage("Format: " . $format);
		Logger::logMessage("Encoding: " . $encoding);
		Logger::logMessage("Unzip ZIP: " . $unzipZip);
		Logger::logMessage("Manage file: " . $manageFile);
		Logger::logMessage("Resource ID: " . $resourceId);
		Logger::logMessage("Create CSV: " . $createCsv);

		//We check if we upload a resource by URL or a FILE
		if ($resourceUrl) {
			$results = array();
			try {
				if (!$resourceId) {
					if ($manageFile || strcasecmp($format, "CSV") == 0) {
						$manageFileResult = $this->manageFileByUrl($resourceManager, $resourceName, $format, $resourceUrl);

						if ($manageFileResult["status"] == "error") {
							throw new \Exception($manageFileResult["message"]);
						}
						else if ($manageFileResult["status"] == "success") {
							$resourceUrl = $manageFileResult["url"];
							$datapusherCheckTime = 0;
							//Managing resources
							$results = $resourceManager->manageFileWithPath($datasetId, null, false, null, $resourceUrl, $description, $encoding, $unzipZip, false, true, $resourceName, true, $datapusherCheckTime, $createCsv);

							//We update the visualisation's icons
							$this->calculateVisualisations($datasetId);
						}
					} else {
						$resultUpload = $resourceManager->uploadResourceToCKAN($this, $datasetId, false, null, $resourceUrl, $resourceName, "", $description, false, $format, null, false);
						$results[] = $resultUpload;
					}

					$result["result"] = $results;
					$result["status"] = "success";
				} else {
					if ($manageFile || strcasecmp($format, "CSV") == 0) {
						$manageFileResult = $this->manageFileByUrl($resourceManager, $resourceName, $format, $resourceUrl);

						if ($manageFileResult["status"] == "error") {
							throw new \Exception($manageFileResult["message"]);
						}
						else if ($manageFileResult["status"] == "success") {
							$resourceUrl = $manageFileResult["url"];
							$datapusherCheckTime = 0;
							//Managing resources
							$results = $resourceManager->manageFileWithPath($datasetId, null, true, $resourceId, $resourceUrl, $description, $encoding, $unzipZip, false, true, $resourceName, true, $datapusherCheckTime, $createCsv);

							//We update the visualisation's icons
							$this->calculateVisualisations($datasetId);
						}
					} else {
						$resultUpload = $resourceManager->uploadResourceToCKAN($this, $datasetId, true, $resourceId, $resourceUrl, $resourceName, "", $description, false, $format, null, false);
						$results[] = $resultUpload;
					}

					$result["result"] = $results;
					$result["status"] = "success";
				}
			} catch (\Exception $e) {
				Logger::logMessage($e->getMessage());
				$data_array = array();
				$data_array["message"] = $e->getMessage();

				$result["result"] = $data_array;
				$result["status"] = "error";
			}
		} else {
			$manageFileResult = $this->manageFile();
			if ($manageFileResult["status"] == "error") {
				$result["status"] = "error";
				$result["result"] = $manageFileResult;
			} else if ($manageFileResult["status"] == "success") {
				$resourceUrl = $manageFileResult["url"];

				try {
					if (!$resourceId) {
						//Managing resources
						$results = $resourceManager->manageFileWithPath($datasetId, null, false, null, $resourceUrl, $description, $encoding, $unzipZip, false, true, null, true, false, $createCsv);
					} else {
						//Managing resources
						$results = $resourceManager->manageFileWithPath($datasetId, null, true, $resourceId, $resourceUrl, $description, $encoding, $unzipZip, false, true, null, true, false, $createCsv);
					}

					//We update the visualisation's icons
					$this->calculateVisualisations($datasetId);

					$result["result"] = $results;
					$result["status"] = "success";
				} catch (\Exception $e) {
					Logger::logMessage($e->getMessage());
					$data_array = array();
					$data_array["message"] = $e->getMessage();

					$result["result"] = $data_array;
					$result["status"] = "error";
				}
			}
		}

		$response = new Response();
		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	function manageFileByUrl($resourceManager, $resourceName, $resourceFormat, $resourceUrl)
	{
		Logger::logMessage("Managing file received from POST with URL " . $resourceUrl);
		//Decode the URL
		$resourceUrl = urldecode($resourceUrl);

		$data_array = array();

		$fileName = basename($resourceUrl);
		//We remove parameters from the name if it exist (filename.csv?someparameters)
		$fileName = strtok($fileName, "?");

		//If the filename only contains the format, we need to change the name
		if (strcasecmp($fileName, $resourceFormat) == 0) {
			$fileName = $resourceManager->nettoyage2($resourceName) . "." . $resourceFormat;
		}

		$uploaddir = $this->config->client->drupal_root . '/sites/default/files/dataset/';
		$uploadfile = $uploaddir . $fileName;

		$encodingUrl = str_replace(' ', '%20', $resourceUrl);

		// Checking file size. If over 1GB, we return an error
		$file_size = $this->getFileSize($encodingUrl);
		if ($file_size > 1000000000) {

			Logger::logMessage("Exceeded filesize limit.");

			$data_array["message"] = "The file is too big. Please upload a file smaller than 1GB.";
			$data_array["status"] = "error";
			return $data_array;
		}

		Logger::logMessage("Downloading file from " . $encodingUrl . " to " . $uploadfile . "");

		if (($data = @file_put_contents($uploadfile, file_get_contents($encodingUrl))) === false) {
			$error = error_get_last();

			$data_array["status"] = "error";
			$data_array["message"] = $error['message'];
			Logger::logMessage("File downloading failed: " . $error['message']);
		} else {
			Logger::logMessage("File downloaded successfully");
			$url = 'https://' . $_SERVER['HTTP_HOST'] . $this->config->client->routing_prefix . '/sites/default/files/dataset/' . $fileName;

			$data_array["status"] = "success";
			$data_array["url"] = $url;
			return $data_array;
		}
		return $data_array;
	}

	function manageFile()
	{
		Logger::logMessage("Managing file received from POST");
		Logger::logMessage("File infos : " . json_encode($_FILES['upload_file']));

		$data_array = array();
		if (
			!isset($_FILES['upload_file']['error']) ||
			is_array($_FILES['upload_file']['error'])
		) {
			$data_array["status"] = "error";
			$data_array["message"] = 'Invalid parameters.';
			Logger::logMessage("Invalid parameters.");
			return $data_array;
		}

		switch ($_FILES['upload_file']['error']) {
			case UPLOAD_ERR_OK:
				break;
			case UPLOAD_ERR_NO_FILE:
				$data_array["status"] = "error";
				$data_array["message"] = 'No file sent.';
				Logger::logMessage("No file sent.");
				return $data_array;
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				$data_array["status"] = "error";
				$data_array["message"] = 'Exceeded filesize limit.';
				Logger::logMessage("Exceeded filesize limit.");
				return $data_array;
			default:
				$data_array["status"] = "error";
				$data_array["message"] = 'Unknown errors.';
				Logger::logMessage("Unknown errors.");
				return $data_array;
		}

		// You should also check filesize here.
		if ($_FILES['upload_file']['size'] > 1000000000) {
			$data_array["status"] = "error";
			$data_array["message"] = 'Exceeded filesize limit.';
			Logger::logMessage("Exceeded filesize limit.");
			return $data_array;
		}

		$finfo = new finfo(FILEINFO_MIME_TYPE);
		Logger::logMessage("Found format : " . $finfo->file($_FILES['upload_file']['tmp_name']));
		if (false === $ext = array_search(
			$finfo->file($_FILES['upload_file']['tmp_name']),
			array(
				'zip' => 'application/zip',
				'xls' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
				'xls' => 'application/vnd.ms-excel',
				'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
				'csv' => 'text/csv',
				'csv' => 'text/html',
				'text' => 'text/plain',
				'xml' => 'text/xml',
				'json' => 'application/json',
			),
			true
		)) {
			Logger::logMessage("Invalid file format.");
			$data_array["status"] = "error";
			$data_array["message"] = 'Invalid file format.';
			return $data_array;
		}

		$uploaddir = $this->config->client->drupal_root . '/sites/default/files/dataset/';
		$uploadfile = $uploaddir . basename($_FILES['upload_file']['name']);

		if (move_uploaded_file($_FILES['upload_file']['tmp_name'], $uploadfile)) {
			Logger::logMessage("The file is valid and has been uploaded with success");
			$url = 'https://' . $_SERVER['HTTP_HOST'] . $this->config->client->routing_prefix . '/sites/default/files/dataset/' . basename($_FILES['upload_file']['name']);
		} else {
			Logger::logMessage("Potential attack by file upload.");
			$data_array["status"] = "error";
			$data_array["message"] = 'Potential attack by file upload.';
			return $data_array;
		}

		$data_array["status"] = "success";
		$data_array["url"] = $url;
		return $data_array;
	}

	/**
	 * Returns the size of a file without downloading it, or -1 if the file
	 * size could not be determined.
	 *
	 * @param $url - The location of the remote file to download. Cannot
	 * be null or empty.
	 *
	 * @return The size of the file referenced by $url, or -1 if the size
	 * could not be determined.
	 */
	function getFileSize($url)
	{
		// Assume failure.
		$result = -1;

		$curl = curl_init($url);

		// Issue a HEAD request and follow any redirects.
		curl_setopt($curl, CURLOPT_NOBODY, true);
		curl_setopt($curl, CURLOPT_HEADER, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

		$data = curl_exec($curl);
		curl_close($curl);

		if ($data) {
			$content_length = "unknown";
			$status = "unknown";

			if (preg_match("/^HTTP\/1\.[01] (\d\d\d)/", $data, $matches)) {
				$status = (int)$matches[1];
			}

			if (preg_match("/Content-Length: (\d+)/", $data, $matches)) {
				$content_length = (int)$matches[1];
			}

			// http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
			if ($status == 200 || ($status > 300 && $status <= 308)) {
				$result = $content_length;
			}
		}

		return $result;
	}

	public function callRemoveResource() {
		$resourceId = $_POST['resource_id'];

		try {
			$resourceManager = new ResourceManager;

			Logger::logMessage("Delete resource " . $resourceId);
			$resourceManager->deleteResource($resourceId);

			$result["status"] = "success";
		} catch (\Exception $e) {
			Logger::logMessage($e->getMessage());
			$data_array = array();
			$data_array["message"] = $e->getMessage();

			$result["result"] = $data_array;
			$result["status"] = "error";
		}

		$response = new Response();
		$response->setContent(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}
	
	function callResourceDictionnary() {
		$userId = \Drupal::currentUser()->id();
		$isConnected = \Drupal::currentUser()->isAuthenticated();
		if (!$isConnected) {
			$response = new Response();
			$response->setStatusCode(503);
			$response->headers->set('Content-Type', 'application/json');

			return $response;
		}

		$method = $_SERVER['REQUEST_METHOD'];

		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');

		$resourceManager = new ResourceManager();

		switch ($method) {
		case 'GET':
			$resourceId = \Drupal::request()->query->get('resourceId');

			$fields = $resourceManager->getDictionnary($this, $resourceId);

			$result = array(
				"status" => "success",
				"result" => $fields
			);
			$response->setContent(json_encode($result));
			break;
		case 'POST':
			$request_body = file_get_contents('php://input');
			$data = json_decode($request_body);

			$resourceId = $data->resourceId;
			$fields = $data->fields;

			$result = $resourceManager->updateDictionnary($this, $resourceId, $fields);
			$response->setContent($result);
			break;
		case 'DELETE':
			// To implement
			break;
		}

		return $response;
	}


	/**
	 * This method add a version for a specified resource
	 * 
	 */
	function addResourceVersion($datasetId, $resourceId, $filePath)
	{
		Logger::logMessage("Adding version for resource '" . $resourceId . "' from dataset '" . $datasetId . "' \r\n");
		$table = "d4c_resource_version";

		$query = \Drupal::database()->insert($table);
		$query->fields([
			'dataset_id',
			'resource_id',
			'filepath',
			'creation_date'
		]);
		$query->values([
			$datasetId,
			$resourceId,
			$filePath,
			'now'
		]);

		$query->execute();
	}

	/**
	 * This method retrieve the versions for the resource
	 * 
	 */
	function getResourceVersions($resourceId)
	{
		$table = "dpl_d4c_resource_version";
		$sqlQuery = "SELECT filePath, creation_date FROM " . $table . " WHERE resource_id = '" . $resourceId . "' ORDER BY creation_date";

		$query = \Drupal::database()->query($sqlQuery);

		$res = array();
		while ($enregistrement = $query->fetch()) {
			array_push($res, $enregistrement);
		}

		return $res;

		// $result = json_encode($res);

		// $response = new Response();
		// $response->setContent($result);
		// $response->headers->set('Content-Type', 'application/json');

		// return $response;  
	}

	/**
	 * This method allow the user to subscribe to a dataset
	 * 
	 */
	function subscribeDataset($datasetId)
	{
		return $this->subscribe($datasetId, true);
	}

	/**
	 * This method allow the user to unsubscribe to a dataset
	 * 
	 */
	function unsubscribeDataset($datasetId)
	{
		return $this->subscribe($datasetId, false);
	}

	function subscribe($datasetId, $subscribe)
	{
		if (\Drupal::currentUser()->isAuthenticated()) {
			$userId = \Drupal::currentUser()->id();

			$isSubscribed = $this->isSubscribed($datasetId, $userId);

			$table = "d4c_dataset_subscription";
			if ($isSubscribed && !$subscribe) {
				Logger::logMessage("Unsubscribing for dataset '" . $datasetId . "'");

				$query = \Drupal::database()->delete($table);
				$query->condition($query->andConditionGroup()
					->condition('user_id', $userId)
					->condition('dataset_id', $datasetId));
				$query->execute();
			} else if (!$isSubscribed && $subscribe) {
				Logger::logMessage("Subscribing for dataset '" . $datasetId . "'");

				$query = \Drupal::database()->insert($table);
				$query->fields([
					'dataset_id',
					'user_id',
					'creation_date'
				]);
				$query->values([
					$datasetId,
					$userId,
					'now'
				]);
				$query->execute();
			}

			$status = ["result" => "success"];

			$result = json_encode($status);

			$response = new Response();
			$response->setContent($result);
			$response->headers->set('Content-Type', 'application/json');

			return $response;
		} else {
			$response = new Response();
			$response->setStatusCode(404);
			$response->headers->set('Content-Type', 'application/json');

			return $response;
		}
	}

	function isSubscribed($datasetId, $userId)
	{
		$table = "d4c_dataset_subscription";

		$query = \Drupal::database()->select($table, 's');
		$query->condition($query->andConditionGroup()
			->condition('s.user_id', $userId)
			->condition('s.dataset_id', $datasetId));

		$query->addExpression('COUNT(*)');
		$count = $query->execute()->fetchField();

		return $count > 0;
	}



	/* SECURITY PART WITH ROLES AND ORGANIZATION */

	function isObservatory() {
		$isObservatory = $this->config->client->client_is_observatory;
		return $isObservatory == "true";
	}

	function getObservatoryOrganisations() {
		$organizationName = $this->config->client->client_organisation;

		$allowedOrganizations = array();
		$allowedOrganizations[] = strtolower($organizationName);
		return $allowedOrganizations;
	}

	function getUserOrganisations()
	{
		$allowedOrganizations = array();

		$current_user = \Drupal::currentUser();
		if (in_array("administrator", $current_user->getRoles())) {
			$allowedOrganizations[] = "*";
			return $allowedOrganizations;
		}

		if ($this->isObservatory()) {
			$allowedOrganizations = $this->getObservatoryOrganisations();
		}

		foreach ($current_user->getRoles() as $role) {
			if (strpos($role, 'admin_') !== false) {
				$loadedRole = \Drupal::entityTypeManager()->getStorage('user_role')->load($role);
				$loadedRoleName = $loadedRole->label();

				//We extract the organization
				$organizationName = substr($loadedRoleName, strlen('admin_'), strlen($loadedRoleName));
				//We lowercase
				$organizationName = strtolower($organizationName);

				$allowedOrganizations[] = $organizationName;
			}
		}

		return $allowedOrganizations;
	}

	function getUserOrganizationsParameter($allowedOrganizations)
	{
		$hasParameter = false;

		//We add all the organization allowed for the user
		$organizationParameter = "(";
		foreach ($allowedOrganizations as $org) {
			if ($org == "*") {
				return null;
			}

			if ($hasParameter) {
				$organizationParameter = $organizationParameter . " OR ";
			}

			$organizationParameter = $organizationParameter . 'organization:"' . $org . '"';

			$hasParameter = true;
		}
		$organizationParameter = $organizationParameter . ")";

		return $hasParameter ? $organizationParameter : null;
	}

	function isOrganizationAllowed($organization, $allowedOrganizations)
	{
		foreach ($allowedOrganizations as $org) {
			if ($org == "*" || strcasecmp($org, $organization) == 0) {
				return true;
			}
		}
		return false;
	}

	function isDatasetAllowed($organization, $allowedOrganizations)
	{
		foreach ($allowedOrganizations as $org) {
			if ($org == "*" || strcasecmp($org, $organization) == 0) {
				return true;
			}
		}

		return false;
	}

	function getAdministrators()
	{
		$userStorage = \Drupal::entityTypeManager()->getStorage('user');

		$query = $userStorage->getQuery();
		$uids = $query
			->condition('status', '1')
			->condition('roles', 'administrator')
			->execute();

		return $userStorage->loadMultiple($uids);
	}

	/* END SECURITY PART WITH ROLES AND ORGANIZATION */

	function callVisualizations($visualizationId) {
		$userId = \Drupal::currentUser()->id();
		$isConnected = \Drupal::currentUser()->isAuthenticated();
		if (!$isConnected) {
			$response = new Response();
			$response->setStatusCode(503);
			$response->headers->set('Content-Type', 'application/json');

			return $response;
		}

		$method = $_SERVER['REQUEST_METHOD'];

		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');

		$result = array();

		$table = "d4c_dataset_visualization";

		switch ($method) {
		case 'POST':
			$request_body = file_get_contents('php://input');
			$data = json_decode($request_body);

			$datasetid = $data->datasetId;
			$type = $data->embedType;
			$name = $data->visualizationName;
			$shareUrl = $data->shareUrl;
			$iframe = $data->iframe;
			$widget = $data->widget;

			$query = \Drupal::database()->insert($table);
			$query->fields([
				'dataset_id',
				'user_id',
				'creation_date',
				'type',
				'name',
				'share_url',
				'iframe',
				'widget'
			]);
			$query->values([
				$datasetid,
				$userId,
				'now',
				$type,
				$name,
				$shareUrl,
				$iframe,
				$widget,
			]);
			$query->execute();

			$result = array();
			$result["status"] = "success";
			echo json_encode($result);

			break;
		case 'PUT':
			if ($visualizationId == "") {
				$response = new Response();
				$response->setStatusCode(500);
				$response->headers->set('Content-Type', 'application/json');

				return $response;
			}

			$request_body = file_get_contents('php://input');
			$data = json_decode($request_body);

			$publishDatasetId = $data->publish_dataset_id;
			$this->updateVisualization($visualizationId, $publishDatasetId);

			$response->setStatusCode(200);

			break;
		case 'DELETE':
			$request_body = file_get_contents('php://input');
			$data = json_decode($request_body);

			$visualizationId = $data->visualizationId;

			if ($visualizationId == "") {
				$response = new Response();
				$response->setStatusCode(500);
				$response->headers->set('Content-Type', 'application/json');

				return $response;
			}

			$visualization = $this->getVisualization($visualizationId);
			$publishDatasetId = $visualization['publish_dataset_id'];

			$query = \Drupal::database()->delete($table);
			$query->condition('id', $visualizationId);

			if (isset($publishDatasetId)) {
				$this->deleteDataset($publishDatasetId);
			}

			$query->execute();
			echo json_encode(array("status" => "success"));
			break;
		}

		return $response;
	}

	function updateVisualization($visualizationId, $publishDatasetId) {
		$table = "d4c_dataset_visualization";

		$query = \Drupal::database()->update($table);
		$query->fields([
			'publish_dataset_id' => $publishDatasetId
		]);
		$query->condition('id', $visualizationId);
		$query->execute();
	}

	function callGlobalVisualizations() {
		echo $this->getVisualizations();
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	function getVisualization($visualizationId) {
		$visualizations = $this->getVisualizations($visualizationId, null, null, null, null, true);
		$result = json_decode($visualizations, true);
		$result = $result['result'];

		if (isset($result) && sizeof($result) > 0) {
			return $result[0];
		}

		return null;
	}

	function getVisualizations($visualizationId = null, $datasetId = null, $queryName = null, $type = null, $currentUserId = null, $lightWeight = false) {
		Logger::logMessage("getVisualizations with datasetId = $datasetId and query = $queryName and type = $type");

		$table = "d4c_dataset_visualization";
		$query = \Drupal::database()->select($table, 'visualization');

		$query->fields('visualization', [
			'id',
			'dataset_id',
			'user_id',
			'creation_date',
			'type',
			'name',
			'share_url',
			'iframe',
			'widget',
			'publish_dataset_id'
		]);


		if (isset($visualizationId)) {
			$query->condition('id', $visualizationId);
		}
		if (isset($datasetId)) {
			$query->condition('dataset_id', $datasetId);
		}
		if (isset($queryName)) {
			$query->condition('name', '%' . db_like($queryName) . '%', 'LIKE');
		}
		if (isset($type)) {
			$query->condition('type', $type);
		}
		if (isset($currentUserId)) {
			$query->condition('user_id', $currentUserId);
		}

		$data = array();

		$prep = $query->execute();
		while ($enregistrement = $prep->fetch()) {

			if (!$lightWeight) {
				$recDatasetId = $enregistrement->dataset_id;

				$dataset = $this->findDataset($recDatasetId);
				$organization = $dataset['organization']['name'];
	
				$enregistrement->organization = $organization;
				$enregistrement->datasetName = $dataset['title'];

				
				$publishDatasetId = $enregistrement->publish_dataset_id;
				if (isset($publishDatasetId)) {
					$visualizationId = $enregistrement->id;
					$publishDataset = $this->findDataset($publishDatasetId);

					if (!isset($publishDataset) || $publishDataset['state'] == 'deleted') {
						Logger::logMessage("The dataset with ID '$publishDatasetId' does not exist anymore. We remove the link with the visualization.");
						$this->updateVisualization($visualizationId, null);
					}
					else {
						$enregistrement->hasIntegration = true;
					}
				}
			}

			$data[] = $enregistrement;

			//Not working for now
			// // Create an array entry for each organization
			// if (!array_key_exists($organization, $data)) {
			// 	$data[$organization] = array();
			// }

			// // Create an array entry for each type
			// if (!array_key_exists($recType, $data[$organization])) {
			// 	$data[$organization][$recType] = array();
			// }

			// array_push($data[$organization][$recType], $enregistrement);
		}

		$result = array();
		$result["result"] = $data;
		$result["status"] = "success";
		return json_encode($result);
	}

	// Part organizations
	function manageOrg($orgId, $orgTitle, $description, $extras, $updateOrg) {
		if ($updateOrg) {
			$context =[
				'id' => $orgId,
				'name' => $orgId,
				'title' => $orgTitle,
				'description' => $description,
				// 'state'=>'active',
				'extras' => $extras,
				// 'packages' => array(),
				// 'users' => array()
			];

            $callUrlUpdate = $this->urlCkan . "/api/action/organization_patch";
            $result = $this->updateRequest($callUrlUpdate, $context, "POST");

            $result = json_decode($result, true);
		}
		else {
			$context =[
				'name' => $orgId,
				'title' => $orgTitle,
				'description' => $description,
				'state'=>'active',
				'extras' => $extras,
				'packages' => array(),
				'users' => array()
			];

            $callUrlCreate = $this->urlCkan . "/api/action/organization_create";
            $result = $this->updateRequest($callUrlCreate, $context, "POST");

            $result = json_decode($result, true);
		}

		return $result;
	}

	public function getOrganization($params) {
		$callUrl =  $this->urlCkan . "api/action/organization_show?" . $params;

		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $this->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		//echo $result . "\r\n";

		$result = json_decode($result, true);
		unset($result["help"]);
		return $result;
	}

	function isConnectedUserAdmin() {
		$current_user = \Drupal::currentUser();
		return in_array("administrator", $current_user->getRoles());
	}

	function callProperties($params) {
		$propertiesHelper = new PropertiesHelper();
		$property = $propertiesHelper->getProperty($params, false, false);

		$result = array();
		$result["result"] = $property;
		$result["status"] = "success";
		
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent(json_encode($result));
		return $response;
	}
}
