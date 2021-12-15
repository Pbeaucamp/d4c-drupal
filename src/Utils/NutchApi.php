<?php

namespace Drupal\ckan_admin\Utils;
use Drupal\ckan_admin\Utils\Logger;

class NutchApi {

	function callNutch($api, $params, $result) {
		Logger::logMessage("TRM - TEST TEST ");
		
		//Manage params
		$query_params = $api->proper_parse_str($params);
		Logger::logMessage('Cyprien : ' . json_encode($query_params) );

		$organization = explode(":", $query_params["fq"])[1]; // TODO: Improve GET organization
		$query = explode(":", $query_params["q"])[1]; // TODO: Improve GET query
		
		$solrItems = array();

		$resultCustomSolr = $this->searchCustomSolr($api, $query);
		
		$items = $resultCustomSolr['response']['docs'];
		foreach ($items as $item) {
			// Logger::logMessage('Cyprien : ' . json_encode($item));
			$name = $item['title'];
			$url = $item['url'];
			$content = $item['content'];

			//We retrive the dataset from CKAN linked to the page
			$linkDataset = $this->foundDatasetFromSolrItem($api, $organization, $url);
			
			//If we find the dataset, we had it to the result
			if ($linkDataset != null) {
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
		}
		
		//Retrieve the number of results
		// $count = $result["result"]["count"];
		// $result["result"]["count"] = $count + count($solrItems);
		$result["result"]["count"] = count($solrItems);

		$result["result"]["results"] = $solrItems;

		return $result;
	}

	function foundDatasetFromSolrItem($api, $organization, $solrItemUrl) {
		$solrItemUrl = parse_url($solrItemUrl);
  		$solrItemUrl = $solrItemUrl['host'];

		// TODO: Check if host correspond to organization
		$datasets = $api->getPackageSearch("fq=url:*" . $solrItemUrl . "*");
		
		foreach ($datasets['result']['results'] as $dataset) {
			if ($organization == null  || $organization == '(' . $dataset['organization']['name'] . ')' ) {
				return $dataset;
			}
		}
		return null;
	}

	function searchCustomSolr($api, $query) {
		//TODO: Put in config
		try {
			Logger::logMessage("TRM - Nutch URL from config " . $api->getConfig()->client->nutch_url);

			// Logger::logMessage('Cyprien: '."https://spot-nutch-solr.data4citizen.com/solr/nutch/select?q=title:*" . $query . "*&wt=json&rows=100000&indent=true");
			$solrUrl = "https://spot-nutch-solr.data4citizen.com/solr/nutch/select?q=title:" . $query . "*&wt=json&rows=100000&indent=true";

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
}
