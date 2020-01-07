<?php

namespace Drupal\ckan_admin\Utils;

Class Query{
	
	static function callSolrServer($callUrl) {
		$options = array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HEADER         => false,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_MAXREDIRS      => 10,
				CURLOPT_ENCODING       => "",
				CURLOPT_AUTOREFERER    => true,
				CURLOPT_CONNECTTIMEOUT => 120,
				CURLOPT_TIMEOUT        => 120,
		);
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $options);
		$result = curl_exec($curl);
		curl_close($curl);
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