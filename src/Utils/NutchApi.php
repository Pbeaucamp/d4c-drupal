<?php

namespace Drupal\ckan_admin\Utils;
use Drupal\ckan_admin\Utils\Logger;

class NutchApi {

	function callNutch($api, $params, $result) {
		//Manage params
		$query_params = $api->proper_parse_str($params);

		$organization = explode(":", $query_params["fq"])[1]; // TODO: Improve GET organization
		$organization = str_replace('(', '', $organization);
		$organization = str_replace(')', '', $organization);
		
		$query = '*' . explode(":", $query_params["q"])[1] . '*'; // TODO: Improve GET query
		$query = str_replace('+-+', ' ', $query);
		$query = str_replace('+:+', ' ', $query);
		$query = str_replace('%3A', '', $query);
		$query = str_replace('/', '', $query);
		$query = str_replace('%2F', '', $query);
		$query = str_replace('+(', ' ', $query);
		$query = str_replace(')+', ' ', $query);
		$query = preg_replace('{3,}', '', $query);
		$query = urlencode($query);
		$query = str_replace('+', '*&q=title:*', $query);
		$query = str_replace(' ', '*&q=title:*', $query);
		// $query = str_replace('', '', $query);
		// $query = str_replace('%20', '*&q=title:*', $query);
		
		$start = $query_params["start"];
		$rows = $query_params["rows"];
		
		$solrItems = array();
		
		Logger::logMessage("Cyprien : " . $query);
		// id:(*vivea.fr* *chlorofil.fr* *gouv* )
		if ($organization != null) {
			$datasets_orga = $api->getOrganization("id=" . $organization . "&include_datasets=true&include_dataset_count=true");
			if ($datasets_orga['result']['package_count'] > 0) {
				$query = $query . "&fq=id:(";
			}
			foreach ($datasets_orga['result']['packages'] as $dataset_orga) {
				$query = $query .  '*' .parse_url($dataset_orga["url"])['host'] . '*%20';
			}
			if ($datasets_orga['result']['package_count'] > 0) {
				$query = $query . ")";
			}
		}
		
		$resultCustomSolr = $this->searchCustomSolr($api, $query, $rows, $start);
			
		$items = $resultCustomSolr['response']['docs'];
		foreach ($items as $item) {
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
			else {
				Logger::logMessage("TRM - Dataset not found for url: " . $url);
			}
		}
		$result["result"]["count"] = $resultCustomSolr['response']['numFound'];
		$result["result"]["results"] = $solrItems;

		return $result;
	}

	function searchCustomSolr($api, $query, $rows, $start) {
		//TODO: Put in config
		try {
			$solrUrl = $api->getConfig()->client->nutch_url . "/solr/nutch/select?q=title:" . $query . "&wt=json&start=" . $start . "&rows=" . $rows . "&indent=true";
			
			Logger::logMessage("TRM - SOLR Query " . $solrUrl);
			Logger::logMessage("Cyprien : " . $solrUrl);

			$curl = curl_init($solrUrl);
			curl_setopt_array($curl, $api->getStoreOptions());
			$result = curl_exec($curl);
			curl_close($curl);
			
			// Logger::logMessage("Cyprien : " . json_encode($result, true));
			
			return json_decode($result, true);
		} catch (\Exception $e) {
			Logger::logMessage($e->getMessage());
			return null;
		}
	}
	
	function foundDatasetFromSolrItem($api, $organization, $solrItemUrl) {
		$solrItemUrl = parse_url($solrItemUrl);
  		$solrItemUrl = $solrItemUrl['host'];

		$datasets = $api->getPackageSearch("fq=url:*" . $solrItemUrl . "*");
		
		foreach ($datasets['result']['results'] as $dataset) {
			if ($organization == null  || $organization == $dataset['organization']['name']) {
				return $dataset;
			}
		}
		return null;
	}
}
