<?php

namespace Drupal\ckan_admin\Utils;
use Drupal\ckan_admin\Utils\Api;
use ZipArchive;
use Drupal\ckan_admin\Utils\Logger;


class HarvestManager {

	function deleteHarvest($datasetId) {
		$config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
		$dataForUpdateDatasets = $config->get('dataForUpdateDatasets');         
		$dataForUpdateDatasets = json_decode($dataForUpdateDatasets);

		foreach($dataForUpdateDatasets as &$value){
			foreach($value->datasets as $key => $dataset){
				if($dataset->id_data == $datasetId){
					Logger::logMessage("Deleting dataset '" . $datasetId . "' from harvest.");

					unset($value->datasets[$key]);
					break; 
				} 
			}
			$value->datasets = array_values($value->datasets);
				  
		}

		$config->set('dataForUpdateDatasets', json_encode($dataForUpdateDatasets))->save();
	}
}