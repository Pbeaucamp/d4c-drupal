<?php

namespace Drupal\ckan_admin\Utils;

Class Query{
	
	static function callSolrServer($callUrl) {
		$options = array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HEADER         => false,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_MAXREDIRS      => 10,
				CURLOPT_ENCODING       => "UTF-8",
				CURLOPT_AUTOREFERER    => true,
				CURLOPT_CONNECTTIMEOUT => 120,
				CURLOPT_TIMEOUT        => 120,
		);
		
		Logger::logMessage("callSolrServer - " . $callUrl);
		try {
			$curl = curl_init($callUrl);
			curl_setopt_array($curl, $options);
			$result = curl_exec($curl);
			// Check the return value of curl_exec(), too
			if ($result === false) {
				throw new \Exception(curl_error($curl), curl_errno($curl));
			}

			// Close curl handle
			curl_close($curl);
		} catch(\Exception $e) {
		
			Logger::logMessage(sprintf('callSolrServer - Curl failed with error #%d: %s', $e->getCode(), $e->getMessage()));
		
		}

		return $result;
	}
	
	function putSolrRequest($callUrl, $binaryData, $requestType) {
		$jsonData = json_encode ( $binaryData );

		//$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
		$config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
		//$clef = $config->get('clef');
		$clef = $config->ckan->api_key;
		
		$options = array (
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST => $requestType,
				CURLOPT_POSTFIELDS => $jsonData,
				CURLOPT_HTTPHEADER => array (
						'Content-type:application/json',
						'Content-Length: ' . strlen ( $jsonData ),
						'Authorization:  ' .$clef
				)
		);
	
		$curl = curl_init ( $callUrl );
		curl_setopt_array ( $curl, $options );
		$result = curl_exec ( $curl );
	
		curl_close ( $curl );
		return $result;
	}
	
}