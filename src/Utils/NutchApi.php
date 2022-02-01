<?php

namespace Drupal\ckan_admin\Utils;
use Drupal\ckan_admin\Utils\Logger;

class NutchApi {

	function callNutch($api, $params, $result) {
		//Manage params
		$query_params = $api->proper_parse_str($params);
		$organizations = explode(":", $query_params["fq"])[1]; // TODO: Improve GET organization
		$organizations = str_replace('(', '', $organizations);
		$organizations = str_replace(')', '', $organizations);
		
		// Manage filter by coordinate on organization
		$coordinateParam = null;
		if (array_key_exists('coordReq', $query_params)){
			$coordinateParam = $query_params['coordReq'];
		}

		$query = '*' . explode(":", $query_params["q"])[1] . '*'; // TODO: Improve GET query
		$query = str_replace('+-+', ' ', $query);
		$query = str_replace('+:+', ' ', $query);
		$query = str_replace('%3A', ' ', $query);
		$query = str_replace('/', ' ', $query);
		$query = str_replace(' des ', ' ', $query);
		$query = str_replace(' de ', ' ', $query);
		$query = str_replace(' à ', ' ', $query);
		$query = str_replace(' et ', ' ', $query);
		$query = str_replace(' en ', ' ', $query);
		$query = str_replace(' la ', ' ', $query);
		$query = str_replace(' le ', ' ', $query);
		$query = str_replace(' les ', ' ', $query);
		$query = str_replace(' dans ', ' ', $query);
		$query = str_replace(' est ', ' ', $query);
		$query = str_replace(' sont ', ' ', $query);
		$query = str_replace(' un ', ' ', $query);
		$query = str_replace(' une ', ' ', $query);
		$query = str_replace(' aux ', ' ', $query);
		$query = str_replace(' est ', ' ', $query);
		$query = str_replace(' par ', ' ', $query);
		$query = str_replace(' du ', ' ', $query);
		$query = str_replace(' au ', ' ', $query);
		$query = str_replace('%2F', '', $query);
		$query = str_replace('(', '+', $query);
		$query = str_replace(')', '+', $query);
		$query = str_replace('  ', ' ', $query);
		$query = preg_replace('{3,}', '', $query);
		$query = str_replace(' ', '* OR *', $query);
		$query = str_replace('+', '* OR *', $query);
		$query = urlencode($query);
		$query = '(' . $query . ')';
		
		$start = $query_params["start"];
		$rows = $query_params["rows"];
		
		$solrItems = array();
		
		if ($coordinateParam != null || $organizations != null) {
			if ($organizations != null) {
				$organizations = explode(",", $organizations);
			} else {
				$organizations = $api->getAllOrganisations(FALSE, TRUE);
			}
		}

		if ($organizations != null) {
			$count = 0;
			$count_done = false;
			foreach ($organizations as $organizationId) {

				if ($organizationId != "test" && $organizationId != "bpm") {
					$inside = 1;
					if ($coordinateParam != null) {
						$inside = $this->checkOrganisationCoordinate($api, $coordinateParam, $organizationId);
					}

					if ($inside == 1) {
						$count += 1;
						$datasets_orga = $api->getOrganization("id=" . $organizationId . "&include_datasets=true&include_dataset_count=true");
						if ($datasets_orga['result']['package_count'] > 0 && $count == 1) {
							$query = $query . "&fq=id:(";
							$count_done = true;
						}
						foreach ($datasets_orga['result']['packages'] as $dataset_orga) {
							$query = $query .  '*' .parse_url($dataset_orga["url"])['host'] . '*%20';
						}
					}
				}
			}
			if ($count_done) {
				$query = $query . ")";
			}
		}
		$resultCustomSolr = $this->searchCustomSolr($api, $query, $rows, $start);
		$items = $resultCustomSolr['response']['docs'];
		$organizationInsideCoordinateArray = array();

		//We retrive the dataset from CKAN linked to the page
		$defaultDataset = $this->foundDatasetFromSolrItem($api, $organizations, "default");

		Logger::logMessage("Found default dataset: " . json_encode($defaultDataset));

		foreach ($items as $item) {
			$name = $item['title'];
			$url = $item['url'];
			$content = $item['content'];

			//We retrive the dataset from CKAN linked to the page
			$linkDataset = $this->foundDatasetFromSolrItem($api, $organizations, $url);
			if ($linkDataset == null) {
				$linkDataset = $defaultDataset;
			}
			
			//We build a dataset from a solr page found
			$solrDataset = array();
			$solrDataset['name'] = $name;
			$solrDataset['title'] = $name;
			$solrDataset['url'] = $url;
			$solrDataset['type'] = 'dataset';
			$solrDataset['notes'] = $content;
			$solrDataset['organization'] = $linkDataset['organization'];
			$solrDataset['num_resources'] = $linkDataset['num_resources'];
			$solrDataset['resources'] = $linkDataset['resources'];
			$solrDataset['tags'] = $linkDataset['resources'];
			$solrDataset['extras'] = $linkDataset['extras'];

			$solrDataset['author'] = '';
			$solrDataset['author_email'] = '';
			$solrDataset['creator_user_id'] = '';
			$solrDataset['id'] = '';
			$solrDataset['isopen'] = false;
			$solrDataset['license_id'] = '';
			$solrDataset['license_title'] = '';
			$solrDataset['maintainer'] = '';
			$solrDataset['maintainer_email'] = '';
			$solrDataset['metadata_created'] = '';
			$solrDataset['metadata_modified'] = '';
			$solrDataset['num_tags'] = 0;
			$solrDataset['owner_org'] = '';
			$solrDataset['private'] = false;
			$solrDataset['state'] = 'active';
			$solrDataset['version'] = '';
			$solrDataset['groups'] = null;
			$solrDataset['relationships_as_subject'] = null;
			$solrDataset['relationships_as_object'] = null;

			$solrItems[] = $solrDataset;
		}
		$result["result"]["count"] = $resultCustomSolr['response']['numFound'];
		$result["result"]["results"] = $solrItems;

		return $result;
	}

	function searchCustomSolr($api, $query, $rows, $start) {
		//TODO: Put in config
		try {
			$solrUrl = $api->getConfig()->client->nutch_url . "/solr/nutch/select?q=content:" . $query . "&wt=json&start=" . $start . "&rows=" . $rows . "&indent=true&fl=*,score";
			
			Logger::logMessage("TRM - SOLR Query " . $solrUrl);

			$curl = curl_init($solrUrl);
			curl_setopt_array($curl, $api->getStoreOptions());
			$result = curl_exec($curl);
			curl_close($curl);
			
			return json_decode($result, true);
		} catch (\Exception $e) {
			Logger::logMessage($e->getMessage());
			return null;
		}
	}
	
	function foundDatasetFromSolrItem($api, $organizations, $solrItemUrl) {
		if ($solrItemUrl != "default") {
			$solrItemUrl = parse_url($solrItemUrl);
			$solrItemUrl = $solrItemUrl['host'];
		}

		$datasets = $api->getPackageSearch("fq=url:*" . $solrItemUrl . "*");
		
		foreach ($datasets['result']['results'] as $dataset) {
			if ($organizations == null || in_array($dataset['organization']['name'], $organizations)) {
				// if ($coordinate == null || $this->checkOrganisationCoordinate($api, $organizationInsideCoordinateArray, $coordinate, $dataset['organization'])) {
					return $dataset;
				// }
			}
		}
		return null;
	}

	
// $inside = $this->checkOrganisationCoordinate($api, $coordinateParam, $organizationId);
	function checkOrganisationCoordinate($api, $coordinate, $organizationId) {
		$organization = $api->getOrganization("id=" . $organizationId . "&include_datasets=false&include_dataset_count=false");
		$organizationCoordinate = current(array_filter($organization['result']["extras"], function($f){ return $f["key"] == "coord";}))["value"] ?: null;
		$point = str_replace(",", " ", $organizationCoordinate);

		// Logger::logMessage("TRM - Searching " . $organizationCoordinate . " in box " . $coordinate);

		$polygon = array();
		$coordinate = explode("),", $coordinate);
		for ($i = 0; $i < count($coordinate); $i++) {
			// Replace ( and )
			$coordinate[$i] = str_replace(array("(", ")"), "", $coordinate[$i]);
			$coordinate[$i] = str_replace(",", " ", $coordinate[$i]);
			$polygon[] = $coordinate[$i];
		}

		if (sizeof($polygon) <= 3) {
			$polygon = explode(" ", $polygon[0]);
			$lat = $polygon[0];
			$long = $polygon[1];
			$dist = $polygon[2];
				
			return $this->isInsideCircle($point, $lat, $long, $dist);
		}
		else {
			$result = $this->pointInPolygon($point, $polygon);
			return $result == 'inside' || $result == 'border' || $result == 'vertex';
		}
	}

	function isInsideCircle($point, $latCenterCircle, $lonCenterCircle, $distance) {
		$point = $this->pointStringToCoordinates($point);
		$latPoint = $point['x'];
		$longPoint = $point['y'];

		if (($latPoint == $latCenterCircle) && ($longPoint == $lonCenterCircle)) {
			return true;
		}
		else {
			// Logger::logMessage("Checking if point " . $latPoint . " " . $longPoint . " is inside circle lat : " . $latCenterCircle . " long : " . $lonCenterCircle . " distance : " . $distance);
			$value = $this->haversineGreatCircleDistance($latCenterCircle, $lonCenterCircle, $latPoint, $longPoint);
			// Logger::logMessage("Value " . $value . " distance " . $distance . " result " . ($value <= $distance));
			return $value <= $distance;
		}
	}
	
	/**
	 * Calculates the great-circle distance between two points, with
	 * the Haversine formula.
	 * @param float $latitudeFrom Latitude of start point in [deg decimal]
	 * @param float $longitudeFrom Longitude of start point in [deg decimal]
	 * @param float $latitudeTo Latitude of target point in [deg decimal]
	 * @param float $longitudeTo Longitude of target point in [deg decimal]
	 * @param float $earthRadius Mean earth radius in [m]
	 * @return float Distance between points in [m] (same as earthRadius)
	 */
	function haversineGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000) {
		// convert from degrees to radians
		$latFrom = deg2rad($latitudeFrom);
		$lonFrom = deg2rad($longitudeFrom);
		$latTo = deg2rad($latitudeTo);
		$lonTo = deg2rad($longitudeTo);
	
		$latDelta = $latTo - $latFrom;
		$lonDelta = $lonTo - $lonFrom;
	
		$angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
		cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
		return $angle * $earthRadius;
	}

	// Partie gestion coordonnees
    function pointInPolygon($point, $polygon, $pointOnVertex = true) {
        $this->pointOnVertex = $pointOnVertex;
 
        // Transform string coordinates into arrays with x and y values
        $point = $this->pointStringToCoordinates($point);
        $vertices = array(); 
        foreach ($polygon as $vertex) {
            $vertices[] = $this->pointStringToCoordinates($vertex); 
        }
 
        // Check if the point sits exactly on a vertex
        if ($this->pointOnVertex == true and $this->pointOnVertex($point, $vertices) == true) {
            return "vertex";
        }
 
        // Check if the point is inside the polygon or on the boundary
        $intersections = 0; 
        $vertices_count = count($vertices);
 
        for ($i=1; $i < $vertices_count; $i++) {
            $vertex1 = $vertices[$i-1]; 
            $vertex2 = $vertices[$i];
            if ($vertex1['y'] == $vertex2['y'] and $vertex1['y'] == $point['y'] and $point['x'] > min($vertex1['x'], $vertex2['x']) and $point['x'] < max($vertex1['x'], $vertex2['x'])) { // Check if point is on an horizontal polygon boundary
                return "boundary";
            }
            if ($point['y'] > min($vertex1['y'], $vertex2['y']) and $point['y'] <= max($vertex1['y'], $vertex2['y']) and $point['x'] <= max($vertex1['x'], $vertex2['x']) and $vertex1['y'] != $vertex2['y']) { 
                $xinters = ($point['y'] - $vertex1['y']) * ($vertex2['x'] - $vertex1['x']) / ($vertex2['y'] - $vertex1['y']) + $vertex1['x']; 
                if ($xinters == $point['x']) { // Check if point is on the polygon boundary (other than horizontal)
                    return "boundary";
                }
                if ($vertex1['x'] == $vertex2['x'] || $point['x'] <= $xinters) {
                    $intersections++; 
                }
            } 
        } 
        // If the number of edges we passed through is odd, then it's in the polygon. 
        if ($intersections % 2 != 0) {
            return "inside";
        } else {
            return "outside";
        }
    }
 
    function pointOnVertex($point, $vertices) {
        foreach($vertices as $vertex) {
            if ($point == $vertex) {
                return true;
            }
        }
 
    }
 
    function pointStringToCoordinates($pointString) {
        $coordinates = explode(" ", $pointString);
        return array("x" => $coordinates[0], "y" => $coordinates[1]);
    }
}
