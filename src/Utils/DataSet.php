<?php

namespace Drupal\ckan_admin\Utils;
use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\External;
use Drupal\ckan_admin\Utils\Query;
use Symfony\Component\HttpFoundation\Response;

use Drupal\file\Entity\File;
use \PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use \PhpOffice\PhpSpreadsheet\Reader\Xls;
use \PhpOffice\PhpSpreadsheet\Writer\Csv;;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Drupal\ckan_admin\Utils\Logger;


class DataSet{
	
	static function checkConnexion()
	{
		$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
		//$ckan = $config->get('ckan');
		$ckan = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
         $ckan = $ckan->ckan->url;
		$query = Query::callSolrServer($ckan . "/api/action/organization_list");
		$results = json_decode($query);
		
		return $results->success;
	}
	
	static function getDataSetListByIds($id, $config)
	{
				//$config = \Drupal::service('config.factory')->getEditable('bfc_odl_admin.organisationForm');
		//$ids = $config->get('ids');
		
		// $resources = array();
		$names = array();
		$datasets = array();
		
		//$file_path = drupal_realpath('public://').'/api/portail_anfr';
		//$file_name = "liste_autocomplete.json";
		//if(!file_exists("$file_path/$file_name"))
		//{	
			//Dataset::initJsonFile();
		//}
		
		//$json = $config->get('json');
			$query = Query::callSolrServer("http://www.data.gouv.fr/api/1/datasets/?page_size=10000&organization=" . $id);
			$results = json_decode($query);	
			$i = 0;
			
			while(true && $i<100){
				if($results->data[$i]->organization->name != null){
					$names[$id] = $results->data[$i]->organization->name;
					break;
				}
			 $i++;
			}
			//error_log(print_r($results, true));
			foreach ($results->data as $data){
				// $nb_data;
				// $n = 0;
				// $resources = array();
				
				// foreach($data->resources as $resource){
					
					// $resources[$n]['url'] = $resource->url;
					
					// $resources[$n]['title'] = $resource->title;
					
					// $resources[$n]['description'] = $resource->description;
					
					// $resources[$n]['format'] = $resource->format;

					// $n++;
				// }
				
				// $spatial = array();				
				// $spatial['geom'] = $data->spatial->geom;
				// $spatial['granularity'] = $data->spatial->granularity;
				
				//var_dump($spatial);
				
				// $reuses = $data->metrics->reuses;
					
				
				// $tags = array();		
				// foreach($data->tags as $tag)
				// {
					// array_push($tags, $tag);
				// }
				
				// $json[$names[$id]][$nb_data] = $data->title;

				/*if(!in_array($data->id,$json[$names[$id]]))
				{
					$json[$names[$id]][] = $data->title;
				}*/
					
				
				$datasets[$data->id] = $data->title;
				
			
		}
		//$config->set('json',$json)->save();

		//Dataset::updateJsonFile();
		
		$config->set('names', $names)->save();		
		return $datasets;
	}
	
	static function getDataSetByIds($ids, $config)
	{
		//$config = \Drupal::service('config.factory')->getEditable('bfc_odl_admin.organisationForm');
		//$ids = $config->get('ids');
		
		$resources = array();
		$names = array();
		$datasets = array();
		
		$file_path = drupal_realpath('public://').'/api/portail_anfr';
		$file_name = "liste_autocomplete.json";
		if(!file_exists("$file_path/$file_name"))
		{	
			Dataset::initJsonFile();
		}
		
		$json = $config->get('json');
		
		foreach ($ids as $id)
		{
			$query = Query::callSolrServer("http://www.data.gouv.fr/api/1/datasets/?page_size=10000&organization=" . $id);
			$results = json_decode($query);	
			$i = 0;
			
			while(true && $i<100){
				if($results->data[$i]->organization->name != null){
					$names[$id] = $results->data[$i]->organization->name;
					break;
				}
				$i++;
			}
			foreach ($results->data as $data){
				$nb_data;
				$n = 0;
				$resources = array();
				
				foreach($data->resources as $resource){
					
					$resources[$n]['url'] = $resource->url;
					
					$resources[$n]['title'] = $resource->title;
					
					$resources[$n]['description'] = $resource->description;
					
					$resources[$n]['format'] = $resource->format;

					$n++;
				}
				
				$spatial = array();				
				$spatial['geom'] = $data->spatial->geom;
				$spatial['granularity'] = $data->spatial->granularity;
				
				//var_dump($spatial);
				
				$reuses = $data->metrics->reuses;
					
				
				$tags = array();		
				foreach($data->tags as $tag)
				{
					array_push($tags, $tag);
				}
				
				$json[$names[$id]][$nb_data] = $data->title;

				/*if(!in_array($data->id,$json[$names[$id]]))
				{
					$json[$names[$id]][] = $data->title;
				}*/
					
				
				$datasets[$id][] = [$data->title,$data->description,$resources,$data->license,$data->last_update,$tags,$spatial,$reuses,$data->id];
				
			}
		}
		$config->set('json',$json)->save();

		Dataset::updateJsonFile();
		
		$config->set('names', $names)->save();		
		return $datasets;
	}

	static function getDataSet()
	{
		$config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
		$ids = $config->get('ids');
	
		return DataSet::getDataSetByIds($ids, $config);
	}
	
	static function getDataSetCron()
	{
		$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
		$ids = $config->get('ids_cron');
	
		return DataSet::getDataSetByIds($ids, $config);
	}
	
	
	static function createDatasets($orga, $selIds, $config)
	{
		ini_set('log_errors_max_len', 0);
		//$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
		
		//error_log('Selected names    creation    ' . print_r($config->get('names')));
		
		//var_dump($datasets);
		//foreach($config->get('names') as $id => $name){
			error_log('Selected datasets    creation    ' . print_r($selIds, true));
			$datasets = DataSet::getDataSetByIds(array($orga), $config);
			
			if(!DataSet::orgaExist(DataSet::correctName($orga)))
			{
				DataSet::createOrganization($orga,$orga);// label/id
				
			}	
			foreach ($datasets[$orga] as $dataset)
			{	
				
				if(in_array($dataset[8], $selIds)) {
				error_log('FOUND DATASET 0   ' . print_r($dataset[0], true));
				error_log('FOUND DATASET    ' . print_r($id, true));
				error_log('FOUND DATASET  1  ' . print_r($dataset[1], true));
				error_log('FOUND DATASET 3   ' . print_r($dataset[3], true));
				error_log('FOUND DATASET  4  ' . print_r($dataset[4], true));
				error_log('FOUND DATASET  5  ' . print_r($dataset[5], true));
				error_log('FOUND DATASET  6  ' . print_r($dataset[6], true));
				error_log('FOUND DATASET  7  ' . print_r($dataset[7], true));
				error_log('FOUND DATASET  8  ' . print_r($dataset[8], true));
					if(DataSet::packageExist($dataset[8]))
					{
						$name_dataset = DataSet::updatePackage($dataset[0],$orga,$dataset[1],$dataset[3],$dataset[5],$dataset[7],$dataset[8]);
					}
					else
					{
						$name_dataset = DataSet::createPackage($dataset[0],$orga,$dataset[1],$dataset[3],$dataset[4],$dataset[5],$dataset[6],$dataset[7],$dataset[8]);
					}
					
					

					foreach ($dataset[2] as $resource)
					{
						$idResource = DataSet::resourceExist($name_dataset,$resource['title']);
						
						
							$query = DataSet::createResource($name_dataset,$resource['url'],$resource['description'],$resource['title'],$resource['format'],$idResource);
					}
				}
			}
					

		//}
	}
	
	static function sendDataSet()
	{
		
		$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
		
		if(!DataSet::checkConnexion())
		{
			return false;
		}
		
		
		
		$datasets = DataSet::getDataSet();
		
		//var_dump($datasets);
		foreach($config->get('names') as $id => $name){

			
			if(!DataSet::orgaExist(DataSet::correctName($name)))
			{
				DataSet::createOrganization($id,$name);
				
			}	
			
			foreach ($datasets[$id] as $dataset)
			{	
				if(DataSet::packageExist($dataset[8]))
				{
					$name_dataset = DataSet::updatePackage($dataset[0],$id,$dataset[1],$dataset[3],$dataset[4],$dataset[7],$dataset[8]);
				}
				else
				{
					$name_dataset = DataSet::createPackage($dataset[0],$id,$dataset[1],$dataset[3],$dataset[4],$dataset[5],$dataset[6],$dataset[7],$dataset[8]);
				}
				
				

				foreach ($dataset[2] as $resource)
				{
					$idResource = DataSet::resourceExist($name_dataset,$resource['title']);
					
					//if(!DataSet::resourceExist($name_dataset,$resource['url']))
					//{
						$query = DataSet::createResource($name_dataset,$resource['url'],$resource['description'],$resource['title'],$resource['format'],$idResource);
					//}
					//else
					//{
						//$query = DataSet::createResource($name_dataset,$resource['url'],$resource['description'],$resource['title'],$resource['format'],true);
					//}
				}
			}
					

		}
	}
	
	static function sendDataSetCron() {
		$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
	
		if(!DataSet::checkConnexion())
		{
			echo '<p>adresse invalide</p>';
			return false;
		}
	
		$datasets = DataSet::getDataSetCron();
	
		//var_dump($datasets);
		foreach($config->get('names') as $id => $name){
	
				
			if(!DataSet::orgaExist(DataSet::correctName($name)))
			{
				DataSet::createOrganization($id,$name);
	
			}
				
			foreach ($datasets[$id] as $dataset)
			{
	
				$name_dataset = DataSet::createPackage($dataset[0],$id,$dataset[1],$dataset[3],$dataset[4],$dataset[5],$dataset[6],$dataset[7],$dataset[8]);
	
	
				foreach ($dataset[2] as $resource)
				{
					$idResource = DataSet::resourceExist($name_dataset,$resource['title']);
						
					//if(!DataSet::resourceExist($name_dataset,$resource['url']))
					//{
					$query = DataSet::createResource($name_dataset,$resource['url'],$resource['description'],$resource['title'],$resource['format'],$idResource);
					//}
					//else
					//{
					//$query = DataSet::createResource($name_dataset,$resource['url'],$resource['description'],$resource['title'],$resource['format'],true);
					//}
				}
			}
				
	
		}
	}
	
	static function getListOrga()
	{
		$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
		$ckan = $config->get('ckan');
		
		$query = Query::callSolrServer($ckan . "/api/action/organization_list");
		$results = json_decode($query);
		$list = array();
		
		foreach($results->result as $orga){
			$list[] = $orga;
		}

		return $list;
	}
	
	static function orgaExist($name)
	{
		$list = DataSet::getListOrga();
		return in_array($name, $list);
	}
	
	static function getListPackage()
	{
		$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
		$ckan = $config->get('ckan');
		
		$query = Query::callSolrServer($ckan . "/api/action/package_list");
		$results = json_decode($query);
		$list = array();
		
		return $results->result ;

		
	}
	
	/*static function packageExist($name)
	{
		$list = DataSet::getListPackage();
		return in_array($name, $list);
	}*/
	
	
	static function packageExist($name)
	{
		$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
		$ckan = $config->get('ckan');
		
		
		$binaryData->id = $name;
		$query = Query::putSolrRequest($ckan . '/api/action/package_show', $binaryData, 'POST');
		$results = json_decode($query);
		return $results->success;
			
	}
	
	static function resourceExist($package_name,$name)
	{	
		$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
		$ckan = $config->get('ckan');
		
		
		$binaryData->id = $package_name;
		//drupal_set_message($package_name,'status');
		$query = Query::putSolrRequest($ckan . '/api/action/package_show', $binaryData, 'POST');
		$results = json_decode($query);
		
		
		foreach ($results->result->resources as $resource)
		{
			if($resource->name === $name)
			{
				return $resource->id;
			}
		}
		
		return "false";
		
	}
	
	static function correctName($chaine)
	{
		$chaine = strtolower($chaine);
		$accents = Array("/é/", "/è/", "/ê/","/ë/", "/ç/", "/à/", "/â/","/á/","/ä/","/ã/","/å/", "/î/", "/ï/", "/í/", "/ì/", "/ù/", "/ô/", "/ò/", "/ó/", "/ö/");
		$sans = Array("e", "e", "e", "e", "c", "a", "a","a", "a","a", "a", "i", "i", "i", "i", "u", "o", "o", "o", "o");
		$chaine = preg_replace($accents, $sans,$chaine);
		$chaine = preg_replace('#[^A-Za-z0-9]#','-',$chaine);
		
		return $chaine;
		
	}
	
	static function createOrganization($id,$name)
	{
		$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
		$ckan = $config->get('ckan');
		
		$binaryData->name = DataSet::correctName($name);
		$binaryData->title = $name;
		$binaryData->id = $id;
		
		$result = Query::putSolrRequest($ckan . '/api/action/organization_create', $binaryData, 'POST');
		$r = json_decode($result);
		var_dump($r);
	}
	
	static function createPackage($name,$owner_id,$description,$license,$update,$tags,$spatial,$reuses,$id_dataset)
	{
		//$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
		//$ckan = $config->get('ckan');
		$ckan = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
         $ckan = $ckan->ckan->url;
		
		
		if(strlen($name) > 99){
			$name = mb_strimwidth($name, 0, 90, "");
		}
		
		$dict = array();
		$last_update->key = 'last_update(data.gouv)';
		$last_update->value = $update;
		$dict[] = $last_update;
		
		$nb_reuses->key = 'utilisations';
		$nb_reuses->value = $reuses;
		$dict[] = $nb_reuses;
		
		$granularity->key = 'granularite';
		$granularity->value = $spatial['granularity'];
		$dict[] = $granularity;
		
		$id->key = 'id_datagouv';
		$id->value = $id_dataset;
		$dict[] = $id;
		
		/*$geom->key = 'spatial';
		$geom->value = $spatial['geom'];
		$dict[] = $geom;*/
		
		$listTag = array();
		foreach ($tags as $tag)
		{
			$tagg = new \stdClass();
			$tagg->name = $tag;
			array_push($listTag, $tagg);
		}

		
		$binaryData->name = $id_dataset;
		$binaryData->title = $name;
		$binaryData->owner_org = $owner_id;
		$binaryData->notes = $description;
		$binaryData->license_id = $license;
		$binaryData->extras = $dict;
		$binaryData->tags = $listTag;
		
		$query = Query::putSolrRequest($ckan . '/api/action/package_create', $binaryData, 'POST');
		//$r = json_decode($query);
		//var_dump($r);
		return $binaryData->name;
	}
	
	static function updatePackage($name, $owner_id, $description, $license, $tags, $id_dataset, $extras) {
        
//		$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
//		$ckan = $config->get('ckan');
		$ckan = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
         $ckan = $ckan->ckan->url;
		
		if(strlen($name) > 99){
			$name = mb_strimwidth($name, 0, 90, "");
		}
		
/*		$dict = array();
		$last_update->key = 'last_update(data.gouv)';
		$last_update->value = $update;
		$dict[] = $last_update;
		
		$nb_reuses->key = 'utilisations';
		$nb_reuses->value = $reuses;
		$dict[] = $nb_reuses;
		
		$granularity->key = 'granularite';
		$granularity->value = $spatial['granularity'];
		$dict[] = $granularity;
		
		$id->key = 'id_datagouv';
		$id->value = $id_dataset;
		$dict[] = $id;*/
//		
//		$geom->key = 'spatial';
//		$geom->value = $spatial['geom'];
//		$dict[] = $geom;
		
		$listTag = array();
		foreach ($tags as $tag)
		{
			$tagg = new \stdClass();
			$tagg->name = $tag;
			array_push($listTag, $tagg);
		}
		
		$binaryData->id = $id_dataset;
		$binaryData->title = $name;
		$binaryData->owner_org = $owner_id;
		$binaryData->notes = $description;
		$binaryData->license_id = $license;
		$binaryData->extras = $extras;
		$binaryData->tags = $listTag;
		
		$query = Query::putSolrRequest($ckan . '/api/action/package_patch', $binaryData, 'POST');
        
        
        /*
        foreach ($reuses as &$value){
            
            //$query2 = DataSet::createResource($id_dataset,$value->url,$value->description,$value->title, $value->format,$value->id);
            //drupal_set_message('<pre>'. print_r($query2, true) .'</pre>'); 
            
            
            $query2 = Query::putSolrRequest($ckan .'/api/action/resource_create', $value, 'POST');
           // drupal_set_message('<pre>'. print_r($query2, true) .'</pre>'); 
            
        }
        */
        
        
		//$r = json_decode($query);
		//var_dump($r);
		return $query;
	}
	
	
	static function createResource($id_dataSet,$url,$description,$name,$format,$idResource)
	{

		//$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
//		$config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
//		$ckan = $config->ckan->url;
//		
        $ckan = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
         $ckan = $ckan->ckan->url;
        
		$binaryData->description = $description;
		$binaryData->name = $name;
		$binaryData->format = $format;
		
		if($format == 'JSON' || $format == 'CSV')
		{
            
            
            
			$query=DataSet::addResource($id_dataSet,$url,$description,$name,$format,$idResource);
            $r = json_decode($query);
            //drupal_set_message(print_r($r,true));    
				//drupal_set_message(strval($r),'status');
            
            
            
            
		}
		else {
			$binaryData->url = $url;
			if($idResource != "false")
			{
                
                
				$binaryData->id = $idResource;
				$query = Query::putSolrRequest($ckan . '/api/action/resource_update', $binaryData, 'POST');
				$r = json_decode($query);
				//drupal_set_message(strval($r),'status');
				//drupal_set_message(print_r($r, true));
				//var_dump($r);
			}
			else
			{
                
                
				$binaryData->package_id = $id_dataSet;
				$query = Query::putSolrRequest($ckan . '/api/action/resource_create', $binaryData, 'POST');
				$r = json_decode($query);
                //drupal_set_message(print_r($r, true));
				//var_dump($r);
				//drupal_set_message(strval($r),'status');
			}
			
		}

	}
	
	function addResource($id_dataSet,$url,$description,$name,$format,$idResource)
	{
	
		$config2 = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
		$config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
        
		$clef = $config->ckan->api_key;

		$resource = array();
		
		
		$resource['description'] = $description;
		$resource['name'] = $name;
		$resource['format'] = 'geojson';
		
		// create curl resource
		$cu = curl_init();
		// set url
		curl_setopt($cu, CURLOPT_URL, $url);
		//return the transfer as a string
		curl_setopt($cu, CURLOPT_RETURNTRANSFER, 1);
		//enable headers
		curl_setopt($cu, CURLOPT_HEADER, 1);
		//curl_setopt($cu, CURLOPT_STDERR, $out);
		curl_setopt($cu, CURLOPT_SSL_VERIFYPEER, false);
		$output = curl_exec($cu);
		$headersize = curl_getinfo($cu, CURLINFO_HEADER_SIZE);
		// close curl resource to free up system resources
		curl_close($cu);
		$header = substr($output, 0, $headersize);
		$body = substr($output, $headersize);
		$pos = strpos($header, "filename=");
		$file_name = substr($header, $pos+9);
		$file_name = str_replace('"', '', $file_name);
		$file_name = trim($file_name);			
		$file_path = drupal_realpath('public://').'/tmp';
		$file = file_put_contents("$file_path/$file_name", $body);
		$resource['upload'] = curl_file_create("$file_path/$file_name");
		
		if($idResource != "false")
		{   
            //$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
			$ch = curl_init($config2->get('ckan') . "/api/action/resource_update");
			$resource['id'] = $idResource;
		}
		else
		{ 
            //$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
			$ch = curl_init($config2->get('ckan') . "/api/action/resource_create");
			$resource['package_id'] = $id_dataSet;
		}

			
		
		curl_setopt_array($ch,
				array(CURLOPT_HTTPHEADER => array('Authorization: ' . $clef),
						CURLOPT_CUSTOMREQUEST => 'POST',
						CURLOPT_POSTFIELDS => $resource,
						CURLOPT_RETURNTRANSFER => TRUE)
				);
		
		$response = curl_exec($ch);
        
        //drupal_set_message("sdfsdfdsfsd:". print_r($response,true) );  
		//var_dump($response);
		curl_close($ch);
		
		unlink("$file_path/$file_name");
        
        
        
	}
	
	static function deleteAll()
	{
		$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
		//$ckan = $config->get('ckan');
		
        $ckan = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
         $ckan = $ckan->ckan->url;
        
		$config->set('ids_cron', null)->save();
		
		foreach (DataSet::getListPackage() as $package)
		{
			$binaryData->id = $package; 
			$query = Query::putSolrRequest($ckan . '/api/action/dataset_purge', $binaryData, 'POST');
		}
		
		foreach (DataSet::getListOrga() as $orga)
		{
			$binaryData2->id = $orga;
			$query = Query::putSolrRequest($ckan . '/api/action/organization_purge', $binaryData2, 'POST');

		}
		
		$config->set('json',null)->save();
		$file_path = drupal_realpath('public://').'/api/portail_anfr';
		$file_name = "liste_autocomplete.json";
		$file = file_put_contents("$file_path/$file_name", "");
	}
	
	static function deleteOrgas($ids)
	{	
		$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
		//$ckan = $config->get('ckan');
		$ckan = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
         $ckan = $ckan->ckan->url;
		$cronids = $config->get('ids_cron');
		
		$json = $config->get('json');
		
		foreach ($ids as $key => $id)
		{
			$binaryData->id = $id;
			$binaryData->include_datasets = true;
			$binaryData->include_extras = false;
			$binaryData->include_users = false;
			$binaryData->include_groups = false;
			$binaryData->include_tags =  false;
			$binaryData->include_followers = false;
			
			$query = Query::putSolrRequest($ckan . '/api/action/organization_show', $binaryData, 'POST');
			$result = json_decode($query);
			
			unset($json[$result->result->title]);
			
			foreach ($result->result->packages as $package)
			{
				$binaryData2->id = $package->name;
				$query = Query::putSolrRequest($ckan . '/api/action/dataset_purge', $binaryData2, 'POST');
			}
			
			$binaryData3->id = $id;
			$query = Query::putSolrRequest($ckan . '/api/action/organization_purge', $binaryData3, 'POST');
			unset($cronids[$id]);
		}
		
		$config->set('json',$json)->save();
		Dataset::updateJsonFile();
		
		$config->set('ids_cron', $cronids)->save();
		
	}
	
	static function updateJsonFile()
	{
		$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
		$json = $config->get('json');
		$json_string = json_encode($json);
		$file_path = drupal_realpath('public://').'/api/portail_anfr';
		$file_name = "liste_autocomplete.json";
		$file = file_put_contents("$file_path/$file_name", $json_string);
	}
	
	static function initJsonFile()
	{
		$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
		drupal_set_message("init",'status');
		$json = array();
		$ids = $config->get('ids_cron');
		foreach ($ids as $id)
		{
			$query = Query::callSolrServer("http://www.data.gouv.fr/api/1/datasets/?page_size=10000&organization=" . $id);
			$results = json_decode($query);
				
			$i = 0;
			$names = array();
			while(true && $i<100){
				if($results->data[$i]->organization->name != null){
					$names[$id] = $results->data[$i]->organization->name;
					break;
				}
				$i++;
			}
		
			foreach ($results->data as $data){
				if(!in_array($data->title,$json[$names[$id]]))
				{
					$json[$names[$id]][] = $data->title;
				}
			}
		}
		
		$config->set('json',$json);
		$json_string = json_encode($json);
		$file_path = drupal_realpath('public://').'/api/portail_anfr';
		$file_name = "liste_autocomplete.json";
		$file = file_put_contents("$file_path/$file_name", $json_string);
		drupal_set_message($file,'status');
		
	}
	
    static function callUpdateDatasetDataGouv()
    {
       
        $config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
		$dataForUpdateDatasets = $config->get('dataForUpdateDatasets');
        //drupal_set_message('<arr>'.print_r($dataForUpdateDatasets,true).'<arr>');
        $dataForUpdateDatasets = json_decode($dataForUpdateDatasets);
        date_default_timezone_set('Europe/Paris');
        
        //$config->set('dataForUpdateDatasets', null)->save();

        foreach($dataForUpdateDatasets as &$value){
            
            $id_org=$value->id_org;
            foreach($value->datasets as &$dataset){
				Logger::logMessage("Check harvest for dataset " . json_encode($dataset) ."\r\n");

				$last_update = $dataset->last_update;
				$periodic_update = $dataset->periodic_update;
                
                if($periodic_update==null||$periodic_update==""){

                    if(date("d")!=date("d",strtotime($last_update)) && date("H")==5){ 

						Logger::logMessage("Harvest dataset because date '" . date("d") . "' not equal to '" . date("d",strtotime($last_update)) . "' and '" . date("H") . "' = 5 \r\n\r\n");

						if($dataset->site_infocom){
							$site_inf =$dataset->site_infocom;
						}
						else{
							$site_inf='';
						}
					  	$date_last_filtre= null;
					  	if (property_exists($dataset, 'date_last_filtre')) {
	            			$date_last_filtre = $dataset->date_last_filtre;
	            		}
	            		$date_last_moissonnage = null;
		            	if (property_exists($dataset, 'date_last_moissonnage')) {
	            			$date_last_moissonnage = $dataset->date_last_moissonnage;
	            		}
						$query =  DataSet::updateDatasetFromDataGouv($dataset->id_data_site, $dataset->id_data, $id_org, $dataset->site, $site_inf,$dataset->title_data, $dataset->parameters, $date_last_filtre, $date_last_moissonnage);
						$dataset->last_update = date("m/d/Y H:i:s");
						//if (property_exists($dataset, 'date_last_moissonnage')) {
	            			$dataset->date_last_moissonnage = date("m/d/Y H:i:s");
	            		//}
						$dataset->periodic_update = "D;1;A";
						$config->set('dataForUpdateDatasets', json_encode($dataForUpdateDatasets))->save();
                    }
					else {
						Logger::logMessage("We do not harvest dataset because '" . date("d") . "' is equal to '" . date("d",strtotime($last_update)) . "' or '" . date("H") . "' != 5 \r\n\r\n");
					}
                }
                else{
                    $periodic_update=explode(";", $periodic_update);
           
					if($periodic_update[2]=='A'){
						$date=1;
						if($periodic_update[1]==''){
							$periodic_update[1]=1;
						}
					  
						switch ($periodic_update[0]) {
							  case 'Mi':
								$date = $periodic_update[1] * 60;
								break;
							case 'H':
								 $date = $periodic_update[1] * 3600;
								break;
							case 'D':
								 $date = $periodic_update[1] * 86400;
								break;
							case 'W':
								 $date = $periodic_update[1] * 604800;
								break;
							case 'M':
								 $date = $periodic_update[1] * 2592000;
								break;
							case 'Y':
								 $date = $periodic_update[1] * 31536000;
								break;
							default:
								$date=0;
						}
                      
						$last_update = strtotime($last_update);
						$next_update = $last_update+$date;
						//error_log(print_r($dataset->title_data, true));
						//error_log(print_r($next_update, true));
						//error_log(print_r(strtotime("now"), true));
                      
						if(strtotime("now") >= $next_update){
					  
							$nowLog = date('m/d/Y H:i:s', strtotime("now"));
							$nextUpdateLog = date('m/d/Y H:i:s',$next_update);

							Logger::logMessage("Harvest dataset because next update '" . $nextUpdateLog . "' is inferior to '" . $nowLog . "' \r\n\r\n");

							if($dataset->site_infocom){
								$site_inf =$dataset->site_infocom;
							}
							else{
								$site_inf='';
							}
                          
                          	$date_last_filtre= null;
						  	if (property_exists($dataset, 'date_last_filtre')) {
		            			$date_last_filtre = $dataset->date_last_filtre;
		            		}
		            		$date_last_moissonnage = null;
		            		if (property_exists($dataset, 'date_last_moissonnage')) {
	            				$date_last_moissonnage = $dataset->date_last_moissonnage;
	            			}
							$query =  DataSet::updateDatasetFromDataGouv($dataset->id_data_site, $dataset->id_data, $id_org, $dataset->site, $site_inf,$dataset->title_data, $dataset->parameters, $date_last_filtre, $date_last_moissonnage);
                            $dataset->last_update = date("m/d/Y H:i:s");
                            //if (property_exists($dataset, 'date_last_moissonnage')) {
	            				$dataset->date_last_moissonnage = date("m/d/Y H:i:s");
	            			//}
                            $config->set('dataForUpdateDatasets', json_encode($dataForUpdateDatasets))->save();
						}
						else {
							$nowLog = date('m/d/Y H:i:s', strtotime("now"));
							$nextUpdateLog = date('m/d/Y H:i:s',$next_update);

							Logger::logMessage("We do not harvest dataset because next update '" . $nextUpdateLog . "' is superior to '" . $nowLog . "' \r\n\r\n");
						}
					}
                }
			}           
        }
        $result="ok";
        $response = new Response();
        $response->setContent($result);
		//$response->headers->set('Content-Type', 'application/json');
		return $response;
    }
    
    static function updateDatasetFromDataGouv($id_dataset_gouv, $id_dataset, $id_org, $site, $site_search, $name, $parameters, $date_last_filtre=null, $date_last_moi =null)
    {
    	
		error_log('moissonage datagouv id : ' . print_r($name,true));
        $api = new Api();    
        
        $config_file = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
        $ckan = $config_file->ckan->url;
        
        if($site=='InfoCom94'){
           
            $query = Query::callSolrServer($site_search."/api/datasets/2.0/searchdatasetres/id=".$id_dataset_gouv);
            $results = json_decode($query);
            $results = $results->result;
			
			
			$result2 = $api->getPackageShow("id=".$id_dataset);		
			$result2 = $result2["result"];
			$extras2 = $result2[extras];
			
			$lastmod = $results->metadata_modified;
			
			$prevmod = "1970-01-01T00:00:00";
			foreach($extras as &$ext){
				if($ext['key'] == 'date_moissonnage_last_modification') {
					$prevmod = $ext['value'];
					break;
				}
			}
			if($prevmod) {
				//error_log('moissonage datagouv id : ' . $name . ' test : ' . strtotime($lastmod) . ' -- ' . strtotime($prevmod));
				if($date_last_moi != null) {
					if(max(strtotime($lastmod), strtotime($date_last_filtre)) <= strtotime($date_last_moi) ) {
						error_log('moissonage id : ' . print_r($name,true) . ' pas de moissonnage');
						return;
					}
				} else {
					if(max(strtotime($lastmod), strtotime($date_last_filtre)) <= strtotime($prevmod) ) {
						error_log('moissonage id : ' . print_r($name,true) . ' pas de moissonnage');
						return;
					}
				}
			}
        
			$extras = $results->extras;
			$ex_Ftp=false;
			$ex_dmlm=false;
			$ex_dmc=false;
			
            for($i= 0; $i<count($extras); $i++ ){
				if($extras[$i]->key == 'FTP_API'){
					$ex_Ftp=true;
					$extras[$i]->value  == $site_search."visualisation/?id=".$id_dataset_gouv;

				}
				if($extras[$i]->key == 'date_moissonnage_last_modification'){
					$ex_dmlm=true;
				}
				if($extras[$i]->key == 'date_moissonnage_creation'){
					$ex_dmc=true;
				}
			}

            if( $ex_Ftp==false){
                $extras[count($extras)]->key = 'FTP_API';
                $extras[(count($extras) - 1)]->value = $site_search."visualisation/?id=".$id_dataset_gouv;  
            }
			if($ex_dmlm==false){
				$extras[count($extras)]->key = 'date_moissonnage_last_modification';
				$extras[(count($extras) - 1)]->value = $results->metadata_modified;
			}
			if($ex_dmc==false){
				$extras[count($extras)]->key = 'date_moissonnage_creation';
				$extras[(count($extras) - 1)]->value = $results->metadata_created;
			}
			
            if($result2){
				$binaryData = json_decode(json_encode($result2));
                //$binaryData->id = $id_dataset;
                $binaryData->owner_org = $id_org;
                $binaryData->notes = $results->notes;
                $binaryData->title = $results->title;
                $binaryData->tags = $results->tags;
                //$binaryData->resources = [];
                $binaryData->extras = json_decode(DataSet::mixExtras(json_encode($extras2), json_encode($extras)));
				
				$query = Query::putSolrRequest($ckan . '/api/action/package_patch', $binaryData, 'POST');
            }

			$old_resources = $result2[resources];
			$add_tres = false;
            foreach($results->resources as &$res){
				$host = $_SERVER['HTTP_HOST']; 
				$editId = null;
				foreach($old_resources as $oldRes){
					if($oldRes["name"] == $res->name && strtolower($oldRes["format"]) == strtolower($res->format)){
						$editId = $oldRes["id"];
						break;
					}
				}
                 
           
				if($_SERVER['HTTP_HOST']=='192.168.2.217'){
					$root='/home/bpm/drupal-8.6.15/sites/default/files/dataset/';
					$url_res = 'http://'.$host.'/sites/default/files/dataset/';
				}
				else{
					$root='/home/user-client/drupal-d4c/sites/default/files/dataset/';
					$url_res = 'https://'.$host.'/sites/default/files/dataset/';
                }
            
				if($res->format == 'CSV' || $res->format == 'XLS' || $res->format == 'XLSX' || $res->format == 'csv' || $res->format == 'xls' || $res->format == 'xlsx'){
					$add_tres=true;
					
					$filepathN = $res->url;
					$filepathN = explode('/',$filepathN);
					$filepathN = $filepathN[count($filepathN)-1];
					//$filepathN = explode('.',$filepathN)[0]; 
					$filepathN =urldecode($filepathN);  
					$filepathN = strtolower($filepathN);
                  
					$url_res = $res->url;
				  
					if($res->format == 'csv' || $res->format == 'CSV') {
                  
						//$filepathN = explode(".",$filepathN)[0].'.csv';
						$url_res = $url_res.''.$filepathN;
                
						// read into array
						$arr = file($res->url);
						$label = utf8_decode($arr[0]);
						$label = DataSet::nettoyage($label);  
                
						// edit first line
						$arr[0] = $label;
                
						// write back to file
						file_put_contents($root.''. $filepathN, implode($arr));
					}
					if($editId == null){
						$resources = [
							"package_id" => $id_dataset,
							"url" => $url_res,
							"description" => $res->description,
							"name" =>$res->name,
							"format"=>$res->format
						];

						$callUrluptres = $ckan . "/api/action/resource_create";
						$return = $api->updateRequest($callUrluptres, $resources, "POST"); 
					} else {
						$resources = [
							//"package_id" => $id_dataset,
							"id" => $editId,
							"url" => $url_res,
							"description" => $res->description,
							"name" => $res->name,
							"format" => $res->format,
							"clear_upload" => true
						];
						
						/*$callUrluptres = $ckan . "/api/action/resource_update";
						$return = $api->updateRequest($callUrluptres, $resources, "POST");*/
						$return = $api->updateResourceAndPushDatastore($resources);
					}
					
				}
				else{
                    $url_res = $res->url;
                    //$query = DataSet::createResource($idNewData,$url_res,$res->description,$res->name, $res->format,'false');
                  
					if($editId == null){
						$resources = [
							"package_id" => $id_dataset,
							"fields" => $url_res,
							"description" => $res->description,
							"name" =>$res->name,
							"format"=>$res->format
						];

						$callUrluptres = $ckan . "/api/action/resource_create";
						$return = $api->updateRequest($callUrluptres, $resources, "POST");
					} else {
						$resources = [
							//"package_id" => $id_dataset,
							"id" => $editId,
							"url" => $url_res,
							"description" => $res->description,
							"name" => $res->name,
							"format" => $res->format,
							//"clear_upload" => true
						];
						
						$callUrluptres = $ckan . "/api/action/resource_update";
						$return = $api->updateRequest($callUrluptres, $resources, "POST");
					}
                } 
            }
			if($add_tres){
				sleep(20);
			}
			$api->calculateVisualisations($id_dataset);

        }
        else if($site=='d4c'){
            if(strpos($site_search, 'https://') === false){
				$site_search = 'https://'.$site_search;
			}
            $query = Query::callSolrServer($site_search."/api/datasets/2.0/searchdatasetres/id=".$id_dataset_gouv);
            
            //drupal_set_message($site_search."/api/datasets/2.0/searchdatasetres/id=".$id_dataset_gouv);
            
            $results = json_decode($query);
            $results = $results->result;
        
            $result2 = $api->getPackageShow("id=".$id_dataset);		
			$result2 = $result2["result"];
			$extras2 = $result2[extras];
			
			$lastmod = $results->metadata_modified;
			
			$prevmod = "1970-01-01T00:00:00";
			foreach($extras as &$ext){
				if($ext['key'] == 'date_moissonnage_last_modification') {
					$prevmod = $ext['value'];
					break;
				}
			}
			if($prevmod) {
				//error_log('moissonage datagouv id : ' . $name . ' test : ' . strtotime($lastmod) . ' -- ' . strtotime($prevmod));
				if($date_last_moi != null) {
					if(max(strtotime($lastmod), strtotime($date_last_filtre)) <= strtotime($date_last_moi) ) {
						error_log('moissonage id : ' . print_r($name,true) . ' pas de moissonnage');
						return;
					}
				} else {
					if(max(strtotime($lastmod), strtotime($date_last_filtre)) <= strtotime($prevmod) ) {
						error_log('moissonage id : ' . print_r($name,true) . ' pas de moissonnage');
						return;
					}
				}
				
			}
        
			$extras = $results->extras;
			$ex_Ftp=false;
			$ex_dmlm=false;
			$ex_dmc=false;
			
            for($i= 0; $i<count($extras); $i++ ){
				if($extras[$i]->key == 'FTP_API'){
					$ex_Ftp=true;
					$extras[$i]->value  == $site_search."visualisation/?id=".$id_dataset_gouv;

				}
				if($extras[$i]->key == 'date_moissonnage_last_modification'){
					$ex_dmlm=true;
				}
				if($extras[$i]->key == 'date_moissonnage_creation'){
					$ex_dmc=true;
				}
			}

            if( $ex_Ftp==false){
                $extras[count($extras)]->key = 'FTP_API';
                $extras[(count($extras) - 1)]->value = $site_search."visualisation/?id=".$id_dataset_gouv;  
            }
			if($ex_dmlm==false){
				$extras[count($extras)]->key = 'date_moissonnage_last_modification';
				$extras[(count($extras) - 1)]->value = $results->metadata_modified;
			}
			if($ex_dmc==false){
				$extras[count($extras)]->key = 'date_moissonnage_creation';
				$extras[(count($extras) - 1)]->value = $results->metadata_created;
			}
			
            if($result2){
				$binaryData = json_decode(json_encode($result2));
                //$binaryData->id = $id_dataset;
                $binaryData->owner_org = $id_org;
                $binaryData->notes = $results->notes;
                $binaryData->title = $results->title;
                $binaryData->tags = $results->tags;
                //$binaryData->resources = [];
                $binaryData->extras = json_decode(DataSet::mixExtras(json_encode($extras2), json_encode($extras)));
				
				$query = Query::putSolrRequest($ckan . '/api/action/package_patch', $binaryData, 'POST');
            }
            
			$old_resources = $result2[resources];
			$add_tres = false;
            foreach($results->resources as &$res){
				$host = $_SERVER['HTTP_HOST']; 
				$editId = null;
				foreach($old_resources as $oldRes){
					if($oldRes["name"] == $res->name && strtolower($oldRes["format"]) == strtolower($res->format)){
						$editId = $oldRes["id"];
						break;
					}
				}
           
				if($_SERVER['HTTP_HOST']=='192.168.2.217'){
					$root='/home/bpm/drupal-8.6.15/sites/default/files/dataset/';
					$url_res = 'http://'.$host.'/sites/default/files/dataset/';
				}
				else{
					$root='/home/user-client/drupal-d4c/sites/default/files/dataset/';
					$url_res = 'https://'.$host.'/sites/default/files/dataset/';
                }
            
				if($res->format == 'CSV' || $res->format == 'XLS' || $res->format == 'XLSX' || $res->format == 'csv' || $res->format == 'xls' || $res->format == 'xlsx'){
					$add_tres=true;
                  
					$filepathN = $res->url;
					$filepathN = explode('/',$filepathN);
					$filepathN = $filepathN[count($filepathN)-1];
					$filepathN = explode('.',$filepathN)[0]; 
					$filepathN =urldecode($filepathN);  
					$filepathN = strtolower($filepathN);
                  
					//if($res->format == 'csv' || $res->format == 'CSV') {
						/*
						$filepathN = explode(".",$filepathN)[0].'.csv';
						$url_res = $url_res.''.$filepathN;
                
						// read into array
						$arr = file($res->url);
						$label = utf8_decode($arr[0]);
						$label = DataSet::nettoyage($label);  
                
						// edit first line
						$arr[0] = $label;
                
						// write back to file
						file_put_contents($root.''. $filepathN, implode($arr));*/
						$parameters->resource_id = $res->id;
						$api_ext = new External();
						$download = $api_ext->getDownloadFromSource("d4c", $site_search, $results->id, http_build_query($parameters));
						$url = $download["url"];
						$fileName = $download["name"];
 
						if($editId == null){
							$resources = [
								"package_id" => $id_dataset,
								"url" => $url,
								"description" => $res->description,
								"name" =>$fileName,
								"format"=>$res->format
							];

							$callUrluptres = $ckan . "/api/action/resource_create";
							$return = $api->updateRequest($callUrluptres, $resources, "POST"); 
						} else {
							$resources = [
								//"package_id" => $id_dataset,
								"id" => $editId,
								"url" => $url,
								"description" => $res->description,
								"name" => $fileName,
								"format" => $res->format,
								"clear_upload" => true
							];
							
							/*$callUrluptres = $ckan . "/api/action/resource_update";
							$return = $api->updateRequest($callUrluptres, $resources, "POST");*/
							$return = $api->updateResourceAndPushDatastore($resources);
						}
					//}
				}
				else{
                    $url_res = $res->url;
                    //$query = DataSet::createResource($idNewData,$url_res,$res->description,$res->name, $res->format,'false');
					
					if($editId == null){
						$resources = [
							"package_id" => $id_dataset,
							"url" => $url_res,
							"description" => $res->description,
							"name" =>$res->name,
							"format"=>$res->format
						];
						$callUrluptres = $ckan . "/api/action/resource_create";
						$return = $api->updateRequest($callUrluptres, $resources, "POST"); 
					} else {
						$resources = [
							//"package_id" => $id_dataset,
							"id" => $editId,
							"url" => $url_res,
							"description" => $res->description,
							"name" => $res->name,
							"format" => $res->format,
							//"clear_upload" => true
						];
						
						$callUrluptres = $ckan . "/api/action/resource_update";
						$return = $api->updateRequest($callUrluptres, $resources, "POST");
					}
                } 
            }
            if($add_tres){
				sleep(20);
			}
            $api->calculateVisualisations($id_dataset);
             
        }
        else if($site=='Data_Gouv_fr'){
			return Dataset::harvestDataGouv($ckan, $api, $id_dataset, $id_dataset_gouv, $name, $id_org, $update, $resource,$date_last_moi);
        }
        else if($site=='Public_OpenDataSoft_com'){
            //drupal_set_message('<pre>Public_OpenDataSoft_com</pre>');

            $query = Query::callSolrServer("https://public.opendatasoft.com/api/datasets/1.0/".$id_dataset_gouv.'/');

			$query2 = json_decode($query);    
			$results = $query2->metas;
			
			$tagsData = array();
			if ($results->keyword == '' || count($results->keyword)==0 || !$results->keyword) {
				$tagsData = [];
			} 
			else {
				$tags = $results->keyword;
				for ($j = 0; $j < count($tags); $j++) {
					if($tags[$j]!=''){
						$val = Dataset::nettoyage($tags[$j]);
						array_push($tagsData, ["vocabulary_id" => null, "state" => "active", "display_name" => $val, "name" => $val]);
					}
				}  
			}  
			
			$result2 = $api->getPackageShow("id=".$id_dataset);		
			$result2 = $result2["result"];
			$extras = $result2[extras];
			
			$lastmod = $results->metadata_processed;
			
			$prevmod = "1970-01-01T00:00:00";
			foreach($extras as &$ext){
				if($ext['key'] == 'date_moissonnage_last_modification') {
					$prevmod = $ext['value'];
					break;
				}
			}
			if($prevmod) {
				if($date_last_moi != null) {
					if(max(strtotime($lastmod), strtotime($date_last_filtre)) <= strtotime($date_last_moi) ) {
						error_log('moissonage id : ' . print_r($name,true) . ' pas de moissonnage');
						return;
					}
				} else {
					if(max(strtotime($lastmod), strtotime($date_last_filtre)) <= strtotime($prevmod) ) {
						error_log('moissonage id : ' . print_r($name,true) . ' pas de moissonnage');
						return;
					}
				}	
			}
        
			$exists = false;
			foreach($extras as &$ext){
				if($ext['key'] == 'date_moissonnage_last_modification') {
					$ext['value'] = $lastmod;
					//error_log('extras found');
					$exists = true;
					break;
				}
			}
			if(!$exists) {
				//error_log('extras added');
				$extras[count($extras)]['key'] = 'date_moissonnage_last_modification';
				$extras[(count($extras) - 1)]['value'] = $lastmod; 
			}
			
            if($result2){
				$binaryData = json_decode(json_encode($result2));
                $binaryData->notes = $results->description;
                $binaryData->title = $results->title;
                $binaryData->tags = $tagsData;
                //$binaryData->resources = [];
                $binaryData->extras = $extras;
				
				$query = Query::putSolrRequest($ckan . '/api/action/package_patch', $binaryData, 'POST');
            }
			
			$idNewData=$id_dataset;
			$old_resources = $result2[resources];


            //resources//
			/*
            $fileName = str_replace('-', '_', $query2->datasetid);

			if($_SERVER['HTTP_HOST']=='192.168.2.217'){
				$root='/home/bpm/drupal-8.6.15/sites/default/files/dataset/'.$fileName.'.csv';
				$url = 'http://'.$_SERVER['HTTP_HOST'].'/sites/default/files/dataset/'.$fileName.'.csv';
			}
			else{
				$root='/home/user-client/drupal-d4c/sites/default/files/dataset/'.$fileName.'.csv';
				$url = 'https://'.$_SERVER['HTTP_HOST'].'/sites/default/files/dataset/'.$fileName.'.csv';
			}

            $url_resource = 'https://public.opendatasoft.com/explore/dataset/'.$query2->datasetid.'/download/?format=csv&timezone=Europe/Madrid&use_labels_for_header=true';

			// read into array
			$arr = file($url_resource);
			$label = utf8_decode($arr[0]);
			$label = Dataset::nettoyage($label);  
			$arr[0] = $label;
			 //  
			// write back to file
			file_put_contents($root, implode($arr));
			*/
			
			$api_ext = new External();
			$download = $api_ext->getDownloadFromSource("ods", "https://public.opendatasoft.com", $query2->datasetid, http_build_query($parameters));
			$url = $download["url"];
			$fileName = $download["name"];
			
			$editId = null;
			foreach($old_resources as $oldRes){
				if($oldRes["name"] == $fileName && strtolower($oldRes["format"]) == "csv"){
					$editId = $oldRes["id"];
					break;
				}
			}
			
			if($editId == null){
				$resources = [
					"package_id" => $idNewData,
					"url" => $url,
					"description" => '',
					"name" =>$fileName,
					"format"=>'csv'
				];
				$callUrluptres = $ckan . "/api/action/resource_create";
				$return = $api->updateRequest($callUrluptres, $resources, "POST"); 
			} else {
				$resources = [
					//"package_id" => $idNewData,
					"id" => $editId,
					"url" => $url,
					"description" => '',
					"name" => $fileName,
					"format" => 'csv',
					"clear_upload" => true
				];
				
				/*$callUrluptres = $ckan . "/api/action/resource_update";
				$return = $api->updateRequest($callUrluptres, $resources, "POST");*/
				$return = $api->updateResourceAndPushDatastore($resources);
			}
			
			
			sleep(20);
			$api->calculateVisualisations($idNewData);

		}
        else if($site=='odsall'){
            //drupal_set_message('<pre>odsall</pre>');
			if(strpos($site_search, 'https://') === false){
				$site_search = 'https://'.$site_search;
			}
            $query = Query::callSolrServer($site_search."/api/datasets/1.0/".$id_dataset_gouv.'/');

			$query2 = json_decode($query);    
			$results = $query2->metas;
			
			$tagsData = array();
			if ($results->keyword == '' || count($results->keyword)==0 || !$results->keyword) {
				$tagsData = [];
			} 
			else {
				$tags = $results->keyword;
				for ($j = 0; $j < count($tags); $j++) {
					if($tags[$j]!=''){
						$val = Dataset::nettoyage($tags[$j]);
						array_push($tagsData, ["vocabulary_id" => null, "state" => "active", "display_name" => $val, "name" => $val]);
					}
				}  
			}  
			
			$result2 = $api->getPackageShow("id=".$id_dataset);		
			$result2 = $result2["result"];
			$extras = $result2[extras];
			
			$lastmod = $results->metadata_processed;
			
			$prevmod = "1970-01-01T00:00:00";
			foreach($extras as &$ext){
				if($ext['key'] == 'date_moissonnage_last_modification') {
					$prevmod = $ext['value'];
					break;
				}
			}
			if($prevmod) {
				if($date_last_moi != null) {
					if(max(strtotime($lastmod), strtotime($date_last_filtre)) <= strtotime($date_last_moi) ) {
						error_log('moissonage id : ' . print_r($name,true) . ' pas de moissonnage');
						return;
					}
				} else {
					if(max(strtotime($lastmod), strtotime($date_last_filtre)) <= strtotime($prevmod) ) {
						error_log('moissonage id : ' . print_r($name,true) . ' pas de moissonnage');
						return;
					}
				}	
			}
        
			$exists = false;
			foreach($extras as &$ext){
				if($ext['key'] == 'date_moissonnage_last_modification') {
					$ext['value'] = $lastmod;
					//error_log('extras found');
					$exists = true;
					break;
				}
			}
			if(!$exists) {
				//error_log('extras added');
				$extras[count($extras)]['key'] = 'date_moissonnage_last_modification';
				$extras[(count($extras) - 1)]['value'] = $lastmod; 
			}
			
            if($result2){
				$binaryData = json_decode(json_encode($result2));
                $binaryData->notes = $results->description;
                $binaryData->title = $results->title;
                $binaryData->tags = $tagsData;
                //$binaryData->resources = [];
                $binaryData->extras = $extras;
				
				$query = Query::putSolrRequest($ckan . '/api/action/package_patch', $binaryData, 'POST');
            }

			$idNewData=$id_dataset;
			$old_resources = $result2[resources];
			
            //resources//

			/*$fileName = str_replace('-', '_', $query2->datasetid);

			if($_SERVER['HTTP_HOST']=='192.168.2.217'){
				$root='/home/bpm/drupal-8.6.15/sites/default/files/dataset/'.$fileName.'.csv';
				$url = 'http://'.$_SERVER['HTTP_HOST'].'/sites/default/files/dataset/'.$fileName.'.csv';
			}
			else{
				$root='/home/user-client/drupal-d4c/sites/default/files/dataset/'.$fileName.'.csv';
				$url = 'https://'.$_SERVER['HTTP_HOST'].'/sites/default/files/dataset/'.$fileName.'.csv';
			}
			$url_resource = 'https://'.$site_search.'/explore/dataset/'.$query2->datasetid.'/download/?format=csv&timezone=Europe/Madrid&use_labels_for_header=true';

			// read into array
			$arr = file($url_resource);
			$label = utf8_decode($arr[0]);
			$label = Dataset::nettoyage($label);  
			$arr[0] = $label;
			  
			// write back to file
			file_put_contents($root, implode($arr));*/
			
			$api_ext = new External();
			$download = $api_ext->getDownloadFromSource("ods", $site_search, $query2->datasetid, http_build_query($parameters));
			$url = $download["url"];
			$fileName = $download["name"];
			
			$editId = null;
			foreach($old_resources as $oldRes){
				if($oldRes["name"] == $fileName && strtolower($oldRes["format"]) == "csv"){
					$editId = $oldRes["id"];
					break;
				}
			}

			
			if($editId == null){
				$resources = [
					"package_id" => $idNewData,
					"url" => $url,
					"description" => '',
					"name" =>$fileName,
					"format"=>'csv'
				];
				$callUrluptres = $ckan . "/api/action/resource_create";
				$return = $api->updateRequest($callUrluptres, $resources, "POST"); 
			} else {
				$resources = [
					//"package_id" => $idNewData,
					"id" => $editId,
					"url" => $url,
					"description" => '',
					"name" => $fileName,
					"format" => 'csv',
					"clear_upload" => true
				];
				
				/*$callUrluptres = $ckan . "/api/action/resource_update";
				$return = $api->updateRequest($callUrluptres, $resources, "POST");*/
				$return = $api->updateResourceAndPushDatastore($resources);
			}
			
			sleep(20);
			$api->calculateVisualisations($idNewData);
		}
        else if ($site=='socrata'){
            
			$query = Query::callSolrServer("https://".$site_search."/api/views/metadata/v1/".$id_dataset_gouv.".json");
			//drupal_set_message('<result>'. print_r($query, true) .'</result>');
            $results = json_decode($query);
            $tagsData = array();
            
            if ($results->tags == '' || count($results->tags)==0 || !$results->tags) {
				$tagsData = [];
			} 
            else {
				$tags = $results->tags;
				for ($j = 0; $j < count($tags); $j++) {
					if($tags[$j]!=''){
						$val = DataSet::nettoyage($tags[$j]);
						array_push($tagsData, ["vocabulary_id" => null, "state" => "active", "display_name" => $val, "name" => $val]);
					}
				} 
			}
			
			$result2 = $api->getPackageShow("id=".$id_dataset);		
			$result2 = $result2["result"];
			$extras = $result2[extras];
			
			$lastmod = $results->updatedAt;
			
			$prevmod = "1970-01-01T00:00:00";
			foreach($extras as &$ext){
				if($ext['key'] == 'date_moissonnage_last_modification') {
					$prevmod = $ext['value'];
					break;
				}
			}error_log($lastmod ." ".$prevmod);
			if($prevmod) {
				if($date_last_moi != null) {
					if(max(strtotime($lastmod), strtotime($date_last_filtre)) <= strtotime($date_last_moi) ) {
						error_log('moissonage id : ' . print_r($name,true) . ' pas de moissonnage');
						return;
					}
				} else {
					if(max(strtotime($lastmod), strtotime($date_last_filtre)) <= strtotime($prevmod) ) {
						error_log('moissonage id : ' . print_r($name,true) . ' pas de moissonnage');
						return;
					}
				}
			}
        
			$exists = false;
			foreach($extras as &$ext){
				if($ext['key'] == 'date_moissonnage_last_modification') {
					$ext['value'] = $lastmod;
					//error_log('extras found');
					$exists = true;
					break;
				}
			}
			if(!$exists) {
				//error_log('extras added');
				$extras[count($extras)]['key'] = 'date_moissonnage_last_modification';
				$extras[(count($extras) - 1)]['value'] = $lastmod; 
			}
			
            if($result2){
				$binaryData = json_decode(json_encode($result2));
                $binaryData->notes = $results->description;
                $binaryData->title = $results->name;
                $binaryData->tags = $tagsData;
                //$binaryData->resources = [];
                $binaryData->extras = json_decode(json_encode($extras));
				
				$query = Query::putSolrRequest($ckan . '/api/action/package_patch', $binaryData, 'POST');
            }
		
    
             /////////////////////resources////////////    
            $host = $_SERVER['HTTP_HOST']; 
			$old_resources = $result2[resources];
			
			$editId = null;
			foreach($old_resources as $oldRes){
				if($oldRes["name"] == $id_dataset_gouv && strtolower($oldRes["format"]) == "csv"){
					$editId = $oldRes["id"];
					break;
				}
			}
           
            if($_SERVER['HTTP_HOST']=='192.168.2.217'){
                $root='/home/bpm/drupal-8.6.15/sites/default/files/dataset/';
                $url_res = 'http://'.$host.'/sites/default/files/dataset/';
            }
            else{
                $root='/home/user-client/drupal-d4c/sites/default/files/dataset/';
                $url_res = 'https://'.$host.'/sites/default/files/dataset/';
			}
               
			$filepathN = $site_search.'/resource/'.$id_dataset_gouv.'.csv';
			$filepathN = explode('/',$filepathN);
			$filepathN = $filepathN[count($filepathN)-1];
			$filepathN = explode('.',$filepathN)[0]; 
			$filepathN =urldecode($filepathN);  
			$filepathN = strtolower($filepathN);
                  
                  
			$filepathN = explode(".",$filepathN)[0]. "_" . uniqid().'.csv';
			$url_res = $url_res.''.$filepathN;
                
			// read into array
			$arr = file('https://'.$site_search.'/resource/'.$id_dataset_gouv.'.csv');
                        
			//$label = utf8_decode($arr[0]);
			$label = $arr[0];
			$label = DataSet::nettoyage($label);

			// edit first line
			$arr[0] = $label;

			// write back to file
			file_put_contents($root.''. $filepathN, implode($arr));
			
			if($editId == null){
				$resources = [
					"package_id" => $id_dataset,
					"url" => $url_res,
					"description" => '',
					"name" =>$id_dataset_gouv,
					"format"=>'csv'
				];
				$callUrluptres = $ckan . "/api/action/resource_create";
				$return = $api->updateRequest($callUrluptres, $resources, "POST"); 
			} else {
				$resources = [
					//"package_id" => $id_dataset,
					"id" => $editId,
					"url" => $url_res,
					"description" => '',
					"name" => $id_dataset_gouv,
					"format" => 'csv',
					"clear_upload" => true
				];
				
				/*$callUrluptres = $ckan . "/api/action/resource_update";
				$return = $api->updateRequest($callUrluptres, $resources, "POST");*/
				$return = $api->updateResourceAndPushDatastore($resources);
			}
			
			sleep(20);
			$api->calculateVisualisations($id_dataset);
		} 
        else if ($site=='ckan'){
            
			$query = Query::callSolrServer($site_search."/api/3/action/package_show?id=".$id_dataset_gouv);
    
            $results = json_decode($query);
            $results = $results->result;
    
			
			$result2 = $api->getPackageShow("id=".$id_dataset);		
			$result2 = $result2["result"];
			$extras = $result2[extras];
			
			$lastmod = $results->metadata_modified;
			
			$prevmod = "1970-01-01T00:00:00";
			foreach($extras as &$ext){
				if($ext['key'] == 'date_moissonnage_last_modification') {
					$prevmod = $ext['value'];
					break;
				}
			}
			if($prevmod) {
				if($date_last_moi != null) {
					if(max(strtotime($lastmod), strtotime($date_last_filtre)) <= strtotime($date_last_moi) ) {
						error_log('moissonage id : ' . print_r($name,true) . ' pas de moissonnage');
						return;
					}
				} else {
					if(max(strtotime($lastmod), strtotime($date_last_filtre)) <= strtotime($prevmod) ) {
						error_log('moissonage id : ' . print_r($name,true) . ' pas de moissonnage');
						return;
					}
				}
			}
        
			$exists = false;
			foreach($extras as &$ext){
				if($ext['key'] == 'date_moissonnage_last_modification') {
					$ext['value'] = $lastmod;
					//error_log('extras found');
					$exists = true;
					break;
				}
			}
			if(!$exists) {
				//error_log('extras added');
				$extras[count($extras)]['key'] = 'date_moissonnage_last_modification';
				$extras[(count($extras) - 1)]['value'] = $lastmod; 
			}
			
            if($result2){
				$binaryData = json_decode(json_encode($result2));
                $binaryData->notes = $results->notes;
                $binaryData->title = $results->title;
                $binaryData->tags = $results->tags;
                //$binaryData->resources = [];
                $binaryData->extras = json_decode(json_encode($extras));
				
				$query = Query::putSolrRequest($ckan . '/api/action/package_patch', $binaryData, 'POST');
            }
    
			$add_tres = false;
			$old_resources = $result2[resources];
			/// resources update
			foreach($results->resources as &$res){
				
				$editId = null;
				foreach($old_resources as $oldRes){
					if($oldRes["name"] == $res->name && strtolower($oldRes["format"]) == strtolower($res->format)){
						$editId = $oldRes["id"];
						break;
					}
				}
                
				$host = $_SERVER['HTTP_HOST']; 
			   
				if($_SERVER['HTTP_HOST']=='192.168.2.217'){
					$root='/home/bpm/drupal-8.6.15/sites/default/files/dataset/';
					$url_res = 'http://'.$host.'/sites/default/files/dataset/';
				}
				else{
					$root='/home/user-client/drupal-d4c/sites/default/files/dataset/';
					$url_res = 'https://'.$host.'/sites/default/files/dataset/';
                }
            
				if($res->format == 'CSV' || $res->format == 'XLS' || $res->format == 'XLSX' || $res->format == 'csv' || $res->format == 'xls' || $res->format == 'xlsx'){
					$add_tres=true;
                  
					$filepathN = $res->url;
					$filepathN = explode('/',$filepathN);
					$filepathN = $filepathN[count($filepathN)-1];
					$filepathN = explode('.',$filepathN)[0]; 
					$filepathN =urldecode($filepathN);  
					$filepathN = strtolower($filepathN);
                  
					if( $res->format == 'XLS' || $res->format == 'XLSX'  || $res->format == 'xls' || $res->format == 'xlsx'){
               
						$title_f= $res->title.'_xls';
						switch ($res->format) {
							case 'XLS':
								$filepathN = explode(".",$filepathN)[0]. "_" . uniqid().'.XLS';
								$filepathDell =  $filepathN;
								$reader = new Xls();
								break;
							case 'XLSX':
								$filepathN = explode(".",$filepathN)[0]. "_" . uniqid().'.XLSX';
								$filepathDell =  $filepathN;
								$reader = new Xlsx();
								break;
							case 'xls':
								$filepathN = explode(".",$filepathN)[0]. "_" . uniqid().'.xls';
								$filepathDell =  $filepathN;
								$reader = new Xls();
								break;
							case 'xlsx':
								$filepathN = explode(".",$filepathN)[0]. "_" . uniqid().'.xlsx';
								$filepathDell =  $filepathN;
								$reader = new Xlsx();
								break;                    
						}
                
						$url_res = $url_res.'xls_'.$filepathN; 
                
						$file=$res->url;
						$host=$root.''.$filepathN;
						copy($file, $host);//copy file xls
						chmod($host, 0777);

						$xls_file = $root.''.$filepathN;
                  
						$spreadsheet = $reader->load($xls_file);

						$loadedSheetNames = $spreadsheet->getSheetNames();
                    
						$writer = new Csv($spreadsheet);

						foreach($loadedSheetNames as $sheetIndex => $loadedSheetName) {
							$writer->setSheetIndex($sheetIndex);
							
							$csvpath = str_replace(array('.xlsx', '.xls', '.XLSX', '.XLS'), array('.csv', '.csv', '.csv', '.csv'), $root.'xls_'.$filepathN);
							$url_res = str_replace(array('.xlsx', '.xls', '.XLSX', '.XLS'), array('.csv', '.csv', '.csv', '.csv'), $url_res);
							$fileName = str_replace(array('.xlsx', '.xls', '.XLSX', '.XLS'), array('.csv', '.csv', '.csv', '.csv'), $filepathN);
							
							
							$writer->save($csvpath);
							
							$dell_old_xls = unlink($root.''.$filepathDell);
							
							break;
						}
                
						$arr = file($url_res);
						//$label = utf8_decode($arr[0]);
						$label = $arr[0];
                 
						$label = DataSet::nettoyage($label);  
						// edit first line
						$arr[0] = $label;
                
                
						// write back to file
						file_put_contents($root.'xls_'.$fileName, implode($arr));
            
						//$query = DataSet::createResource($id_dataset,$url_res,$res->description, $title_f, 'csv','false');
              
						
						if($editId == null){
							$resources = [
								"package_id" => $id_dataset,
								"url" => $url_res,
								"description" => $res->description,
								"name" =>$res->name,
								"format"=>$res->format
							];
							$callUrluptres = $ckan . "/api/action/resource_create";
							$return = $api->updateRequest($callUrluptres, $resources, "POST"); 
						} else {
							$resources = [
								//"package_id" => $id_dataset,
								"id" => $editId,
								"url" => $url_res,
								"description" => $res->description,
								"name" => $res->name,
								"format" => $res->format,
								"clear_upload" => true
							];
							
							/*$callUrluptres = $ckan . "/api/action/resource_update";
							$return = $api->updateRequest($callUrluptres, $resources, "POST");*/
							$return = $api->updateResourceAndPushDatastore($resources);
						}
                
					}
            
					if($res->format == 'csv' || $res->format == 'CSV') {
                  
						$filepathN = explode(".",$filepathN)[0]. "_" . uniqid().'.csv';
						$url_res = $url_res.''.$filepathN;
                
						// read into array
						//$arr = file('/home/user-client/drupal-d4c'.$filepath);
						$arr = file($res->url);
						if($arr == false || $arr == ""){error_log("retentative..");
							$arrContextOptions=array(
								"ssl"=>array(
									"verify_peer"=>false,
									"verify_peer_name"=>false,
								),
							);
							$arr = file($res->url, 0, stream_context_create($arrContextOptions));
						}
						//$label = utf8_decode($arr[0]);
						$label = $arr[0];
						$label = DataSet::nettoyage($label);  
                
						// edit first line
						$arr[0] = $label;
                
						// write back to file
						file_put_contents($root.''. $filepathN, implode($arr));
                
						//$query = DataSet::createResource($idNewData,$url_res,$res->description,$res->name, $res->format,'false');
						
						if($editId == null){
							$resources = [
								"package_id" => $id_dataset,
								"url" => $url_res,
								"description" => $res->description,
								"name" =>$res->name,
								"format"=>$res->format
							];
							$callUrluptres = $ckan . "/api/action/resource_create";
							$return = $api->updateRequest($callUrluptres, $resources, "POST"); 
						} else {
							$resources = [
								//"package_id" => $id_dataset,
								"id" => $editId,
								"url" => $url_res,
								"description" => $res->description,
								"name" => $res->name,
								"format" => $res->format,
								"clear_upload" => true
							];
							
							/*$callUrluptres = $ckan . "/api/action/resource_update";
							$return = $api->updateRequest($callUrluptres, $resources, "POST");*/
							$return = $api->updateResourceAndPushDatastore($resources);
						}
                
						//drupal_set_message("REZ:". print_r($return,true) );    
                
						//$return = json_decode($return, true);
					}
				}
                else{
                    $url_res = $res->url;
                    //$query = DataSet::createResource($id_dataset,$url_res,$res->description,$res->name, $res->format,'false');

					if($editId == null){
						$resources = [
							"package_id" => $id_dataset,
							"url" => $url_res,
							"description" => $res->description,
							"name" =>$res->name,
							"format"=>$res->format
						];
						$callUrluptres = $ckan . "/api/action/resource_create";
						$return = $api->updateRequest($callUrluptres, $resources, "POST"); 
					} else {
						$resources = [
							//"package_id" => $id_dataset,
							"id" => $editId,
							"url" => $url_res,
							"description" => $res->description,
							"name" => $res->name,
							"format" => $res->format,
							//"clear_upload" => true
						];
						
						$callUrluptres = $ckan . "/api/action/resource_update";
						$return = $api->updateRequest($callUrluptres, $resources, "POST");
					}
                }  
			}  
			if($add_tres){
				sleep(20);
			}
			$api->calculateVisualisations($id_dataset);
		}
        else if ($site=='joinDataset'){
            $ckan = $config->get('ckan');
            $datasetUpt = $api->getDataSetById($id_dataset);
            $datasetUpt =  json_decode($datasetUpt->getContent());
            $datasetUpt =  $datasetUpt->result;
			$extras = json_decode(json_encode($datasetUpt->extras), true);
            
            $jdd1= $api->getDataSetById($site_search[0]);
            $jdd1 = json_decode($jdd1->getContent());
            $jdd1 =  $jdd1->result;
            $jdd2= $api->getDataSetById($site_search[1]);
            $jdd2= json_decode($jdd2->getContent());
            $jdd2 =  $jdd2->result;
			
			$lastmod1 = $jdd1->metadata_modified;
			$lastmod2 = $jdd2->metadata_modified;
			//$lastmod;
			
			$prevmod = "1970-01-01T00:00:00";
			foreach($extras as &$ext){
				if($ext['key'] == 'date_moissonnage_last_modification') {
					$prevmod = $ext['value'];
					break;
				}
			}
			if($prevmod) {
				if($date_last_moi != null) {
					if(max(strtotime($lastmod1), strtotime($date_last_filtre)) <= strtotime($date_last_moi) && max(strtotime($lastmod2), strtotime($date_last_filtre)) <= strtotime($date_last_moi)) {
						error_log('moissonage id : ' . print_r($name,true) . ' pas de moissonnage');
						return;
				}
				} else {
					if(max(strtotime($lastmod1), strtotime($date_last_filtre)) <= strtotime($prevmod) && max(strtotime($lastmod2), strtotime($date_last_filtre)) <= strtotime($prevmod)) {
						error_log('moissonage id : ' . print_r($name,true) . ' pas de moissonnage');
						return;
				}
				}
			}
        
			$exists = false;
			foreach($extras as &$ext){
				if($ext['key'] == 'date_moissonnage_last_modification') {
					$ext['value'] = (strtotime($lastmod1) >= strtotime($lastmod2))?  $lastmod1 : $lastmod2;
					//error_log('extras found');
					$exists = true;
					break;
				}
			}
			if(!$exists) {
				//error_log('extras added');
				$extras[count($extras)]['key'] = 'date_moissonnage_last_modification';
				$extras[(count($extras) - 1)]['value'] = (strtotime($lastmod1) >= strtotime($lastmod2))?  $lastmod1 : $lastmod2; 
			}
			
           // if($result2){
				$binaryData = $datasetUpt;
                //$binaryData->resources = [];
                $binaryData->extras = json_decode(json_encode($extras));
				
				$query = Query::putSolrRequest($ckan . '/api/action/package_patch', $binaryData, 'POST');
           // }
			
            $columns_data=$site_search[2];
            $columns_data2=$site_search[3];
            
            $tagsData = array();
			if ($datasetUpt->tags == '' || count($datasetUpt->tags)==0 || !$datasetUpt->tags) {
				$tagsData = [];
			} 
			else {
				$tags = $datasetUpt->tags;
				for ($j = 0; $j < count($tags); $j++) {
					if($tags[$j]!=''){
						array_push($tagsData, $tags[$j]->name);
					}
				} 
			}
			
			$query =  DataSet::updatePackage($datasetUpt->title, $datasetUpt->owner_org, $datasetUpt->notes, $datasetUpt->license_id, $tagsData, $datasetUpt->id, array());    
			$idNewData = $id_dataset;  
            
			$csv1='';
			$csv2='';
			$old_resources = json_decode(json_encode($datasetUpt->resources), true);
    
			foreach($jdd1->resources as &$value){
				if($value->format=="CSV" || $value->format=="csv"){
					
					$csv1=$value->url;   
					
				}
				else{
					$editId = null;
					foreach($old_resources as $oldRes){
						if($oldRes["name"] == $value->name && strtolower($oldRes["format"]) == strtolower($value->format)){
							$editId = $oldRes["id"];
							break;
						}
					}
					
					if($editId == null){
						$resources = [
							"package_id" => $idNewData,
							"url" => $value->url,
							"description" => $value->description,
							"name" =>$value->name,
							"format"=>$value->format
						];
						$callUrluptres = $ckan . "/api/action/resource_create";
						$return = $api->updateRequest($callUrluptres, $resources, "POST"); 
					} else {
						$resources = [
							//"package_id" => $idNewData,
							"id" => $editId,
							"url" => $value->url,
							"description" => $value->description,
							"name" => $value->name,
							"format" => $value->format,
							//"clear_upload" => true
						];
						
						$callUrluptres = $ckan . "/api/action/resource_update";
						$return = $api->updateRequest($callUrluptres, $resources, "POST");
					}
				}
			}
    
			foreach($jdd2->resources as &$value){
				if($value->format=="CSV" || $value->format=="csv"){
					$csv2=$value->url;   
				}
				else{
					$editId = null;
					foreach($old_resources as $oldRes){
						if($oldRes["name"] == $value->name && strtolower($oldRes["format"]) == strtolower($value->format)){
							$editId = $oldRes["id"];
							break;
						}
					}
					
					if($editId == null){
						$resources = [
							"package_id" => $idNewData,
							"url" => $value->url,
							"description" => $value->description,
							"name" =>$value->name,
							"format"=>$value->format
						];
						$callUrluptres = $ckan . "/api/action/resource_create";
						$return = $api->updateRequest($callUrluptres, $resources, "POST"); 
					} else {
						$resources = [
							//"package_id" => $idNewData,
							"id" => $editId,
							"url" => $value->url,
							"description" => $value->description,
							"name" => $value->name,
							"format" => $value->format,
							//"clear_upload" => true
						];
						
						$callUrluptres = $ckan . "/api/action/resource_update";
						$return = $api->updateRequest($callUrluptres, $resources, "POST");
					}
				}
			}
			
			
    
			if($csv1!='' && $csv2!=''){
				$resName = $jdd1->name.'_'.$jdd2->name;
				$urlFileNew = DataSet::join2csv($csv1, $csv2, $resName, $columns_data, $columns_data2);
				//drupal_set_message('<pre>'. print_r($idNewData, true) .'</pre>'); 
				
				$editId = null;
				foreach($old_resources as $oldRes){
					if($oldRes["name"] == $resName && strtolower($oldRes["format"]) == "csv"){
						$editId = $oldRes["id"];
						break;
					}
				}
				
				if($editId == null){
					$resources = [
						"package_id" => $idNewData,
						"url" => $urlFileNew,
						"description" => '',
						"name" =>$resName,
						"format"=>"csv"
					];
					$callUrluptres = $ckan . "/api/action/resource_create";
					$return = $api->updateRequest($callUrluptres, $resources, "POST"); 
				} else {
					$resources = [
						//"package_id" => $idNewData,
						"id" => $editId,
						"url" => $urlFileNew,
						"description" => '',
						"name" => $resName,
						"format" => "csv",
						"clear_upload" => true
					];
					
					/*$callUrluptres = $ckan . "/api/action/resource_update";
					$return = $api->updateRequest($callUrluptres, $resources, "POST");*/
					$return = $api->updateResourceAndPushDatastore($resources);
				}
			}   
			sleep(20);
			$api->calculateVisualisations($idNewData);
        }
		else if($site=='arcgis'){
            
			$query = Query::callSolrServer($site_search.'?f=pjson');
			$results = json_decode($query);
			$private = true;
	   
			$extras = array();
			/*$extras[count($extras)]['key'] = 'date_moissonnage_last_modification';
			$extras[(count($extras) - 1)]['value'] = $results->XXXXXXX;*/
			
			/*$extras[count($extras)]['key'] = 'date_moissonnage_creation';
			$extras[(count($extras) - 1)]['value'] = $results->XXXXXXX;*/
			
			$geometryType = $results->geometryType;
			$hasAttachments = $results->hasAttachments;
			$htmlPopupType = $results->htmlPopupType;
			$displayField = $results->displayField;
			$capabilities = $results->capabilities;
			$supportedQueryFormats = $results->supportedQueryFormats;
			
			$fields = $results->fields;
			$ftypes = array();
			foreach($fields as $f){
				$ftypes[$f->name] = $f->type;
			}
			
			$tagsData = array();
		
			$idNewData= $id_dataset;
		
			$resources=array();

			$extras =array();
			$result2 = $api->getPackageShow("id=".$id_dataset);		
			$result2 = json_decode(json_encode($result2["result"]));
			$extras = $result2->extras;

            if($result2){
				$binaryData = $result2;
                $binaryData->id = $id_dataset;
                $binaryData->owner_org = $id_org;
                $binaryData->notes = $results->description;
                $binaryData->relationships = $results->relationships;
                $binaryData->tags = $tagsData;
                //$binaryData->resources = array();
                $binaryData->extras = $extras;
				
				$query = Query::putSolrRequest($ckan . '/api/action/package_patch', $binaryData, 'POST');
            }
			
			
			$fileName = str_replace('-', '_', $result2->name);
			//error_log($supportedQueryFormats);
			$add_tres = false;
			$old_resources = $result2->resources;
			if(strpos($supportedQueryFormats, "geoJSON") !== false){
				$add_tres = true;
				
				$editId = null;
				foreach($old_resources as $oldRes){
					if($oldRes["name"] == ($fileName.".csv") && strtolower($oldRes["format"]) == "csv"){
						$editId = $oldRes["id"];
						break;
					}
				}
				
				$root='/home/user-client/drupal-d4c/sites/default/files/dataset/'.$fileName.'.geojson';
				$url = 'https://'.$_SERVER['HTTP_HOST'].'/sites/default/files/dataset/'.$fileName.'.geojson';
				 
				$url_resource = $site_search.'/query?where=1%3D1&text=&objectIds=&time=&geometry=&geometryType=esriGeometryEnvelope&inSR=&spatialRel=esriSpatialRelIntersects&relationParam=&outFields=*&returnGeometry=true&returnTrueCurves=false&maxAllowableOffset=&geometryPrecision=&outSR=&returnIdsOnly=false&returnCountOnly=false&orderByFields=&groupByFieldsForStatistics=&outStatistics=&returnZ=false&returnM=false&gdbVersion=&returnDistinctValues=false&resultOffset=&resultRecordCount=&queryByDistance=&returnExtentsOnly=false&datumTransformation=&parameterValues=&rangeValues=&f=geojson';
				$arr = Query::callSolrServer($url_resource);
				
				/*file_put_contents($root, $arr);

				$resources = [     
					"package_id" => $idNewData,
					"url" => $url,
					"description" => '',
					"name" =>$fileName.".geojson",
					"format"=>'geojson'
				];

				$callUrluptres = $this->urlCkan . "/api/action/resource_create";
				$return = $api->updateRequest($callUrluptres, $resources, "POST");*/
				
				//construction du csv
				$json = json_decode($arr, true);
				$cols = array();
				$data_csv = array();
				$sample = $json["features"][0];
				foreach($sample["properties"] as $key => $val){
					$cols[] = $key;
				}
				if($sample["geometry"]["type"] == "Point"){
					$cols[] = "geo_point_2d";
				} else {
					$cols[] = "coordinates";
					$cols[] = "geo_shape";
					
				}
				//error_log($arr);
				$nb_att = 0;
				if($hasAttachments){
					
					$url_attach = $site_search . "/".$sample["id"]."/attachments?f=pjson";
					$res = Query::callSolrServer($url_attach);
					
					$res = json_decode($res, true);
					foreach($res["attachmentInfos"] as $key => $attach){
						$cols[] = "attachment_" . $key . "_name";
						$cols[] = "attachment_" . $key . "_url";
						$nb_att++;
					}
				}
				
				$crs = $json["crs"]["properties"]["name"];
				
				$rows = array();
				foreach($json["features"] as $feat){
					$row = array();
					
					foreach($cols as $col){
						if($col == "geo_point_2d"){
							$str = json_encode($feat["geometry"]["coordinates"]);
							//preg_match('/\[(\d+),(\d+)\]/i',$feat["geometry"]["coordinates"], $matches);
							//$str = preg_replace('/\[([-]?[\d|.]+),([-]?[\d|.]+)\]/i', "[$2,$1]", $str);
							//$row[] = str_replace(array("[","]"), array("",""), $str);
							preg_match('/\[([-]?[\d|.]+),([-]?[\d|.]+)/i', $str, $match);
							$row[] = '"'.$match[2] .",". $match[1].'"';
						} else if($col == "geo_shape") {
							$str = json_encode($feat["geometry"]);
							//$str = preg_replace('/\[([\d|.]+),([\d|.]+)(,[\d|\w]+,[\d|\w]+)*\]/i', "[$2,$1$3]", $str);
							preg_match('/\[([-]?[\d|.]+),([-]?[\d|.]+)/i', $str, $match);
							$coord = '"'.$match[2] .",". $match[1].'"';
							$row[] = $coord;
							$row[] = $str;
						} else if(preg_match('/attachment_[\d]+_name/i',$col)){
							$url_attach = $site_search . "/".$feat["id"]."/attachments?f=pjson";
							$res = Query::callSolrServer($url_attach);
							$res = json_decode($res, true);
							if(count($res["attachmentInfos"]) > $nb_att){
								
								$cols[] = "attachment_" . $nb_att . "_name";
								$cols[] = "attachment_" . $nb_att . "_url";
								$nb_att++;
							}
							preg_match('/attachment_([\d]+)_name/i',$col, $matches);
							$c = floatval($matches[1]);
							//error_log(json_encode($c));
							if(count($res["attachmentInfos"]) > $c){ 
								$att = $res["attachmentInfos"][$c];
								//error_log(json_encode($att));
								$name = $att["name"];
								$url = $site_search . "/".$feat["id"]."/attachments/" . $att["id"];
								$row[] = $name;
								$row[] = $url;
							} else {
								$row[] = "";
								$row[] = "";
							}
						} else if(preg_match('/attachment_[\d]+_url/i',$col)){
							continue;
						} else if($col == "coordinates"){
							continue;
						}	
						else {
							if($ftypes[$col] == "esriFieldTypeString"){
								$row[] = '"'.$feat["properties"][$col].'"';
							} else {
								$row[] = $feat["properties"][$col];
							}
							
						}
					}
					
					$rows[] = $row;//implode($row, ";");
				}
				
				foreach($rows as &$row){
					if(count($row) < count($cols)){
						$row = array_pad($row, count($cols), "");
					}
					$row = implode($row, ";");
				}
				
				$data_csv[] = strtolower(implode($cols, ";"));
				$data_csv = array_merge($data_csv, $rows);
				$resname = $fileName . "_" . uniqid();
				$rootCsv='/home/user-client/drupal-d4c/sites/default/files/dataset/'.$fileName.'.csv';
				$urlCsv = 'https://'.$_SERVER['HTTP_HOST'].'/sites/default/files/dataset/'.$fileName.'.csv';
				
				file_put_contents($rootCsv, implode($data_csv, "\n"));
		
				
				if($editId == null){
					$resources = [
						"package_id" => $idNewData,
						"url" => $urlCsv,
						"description" => '',
						"name" =>$fileName.".csv",
						"format"=>'csv'
					];
					$callUrluptres = $ckan . "/api/action/resource_create";
					$return = $api->updateRequest($callUrluptres, $resources, "POST"); 
				} else {
					$resources = [
						//"package_id" => $idNewData,
						"id" => $editId,
						"url" => $urlCsv,
						"description" => '',
						"name" => $fileName.".csv",
						"format" => 'csv',
						"clear_upload" => true
					];
					
					/*$callUrluptres = $ckan . "/api/action/resource_update";
					$return = $api->updateRequest($callUrluptres, $resources, "POST");*/
					$return = $api->updateResourceAndPushDatastore($resources);
				}
				$return = json_decode($return, true);
				
				// Deactivated for now
				// $pathUserClient = '/home/user-client';
				// $pathUserClientData = $pathUserClient . '/data';
				// $buildGeoloc = 'false';
				// $selectedSeparator = ";";
				// $selectedEncoding = "UTF-8";
				// $onlyOneAddress = 'false';
				// $selectedAddress = "";
				// $selectedPostalCode = "";
				// $command = $pathUserClientData . '/geoloc.sh "' . $buildGeoloc . '" "' . $ckan . '" "' . $config_file->ckan->api_key . '" "' . $result2->name . '" "' . $return["result"]["id"] . '" "' . $selectedSeparator . '" "' . $selectedEncoding . '" "' . $onlyOneAddress . '" "' . $selectedAddress . '" "' . $selectedPostalCode . '"';
				// error_log($command);
				// sleep (15); 
				// $output = shell_exec($command);
				// error_log($output);
			} else {
				
				$editId = null;
				foreach($old_resources as $oldRes){
					if($oldRes["name"] == ($fileName.".json") && strtolower($oldRes["format"]) == "json"){
						$editId = $oldRes["id"];
						break;
					}
				}
				
				$root='/home/user-client/drupal-d4c/sites/default/files/dataset/'.$fileName.'.json';
				$url = 'https://'.$_SERVER['HTTP_HOST'].'/sites/default/files/dataset/'.$fileName.'.json';
				
				$url_resource = $site_search.'/query?where=1%3D1&text=&objectIds=&time=&geometry=&geometryType=esriGeometryEnvelope&inSR=&spatialRel=esriSpatialRelIntersects&relationParam=&outFields=*&returnGeometry=true&returnTrueCurves=false&maxAllowableOffset=&geometryPrecision=&outSR=&returnIdsOnly=false&returnCountOnly=false&orderByFields=&groupByFieldsForStatistics=&outStatistics=&returnZ=false&returnM=false&gdbVersion=&returnDistinctValues=false&resultOffset=&resultRecordCount=&queryByDistance=&returnExtentsOnly=false&datumTransformation=&parameterValues=&rangeValues=&f=json';
				$arr = file($url_resource);
				
				file_put_contents($root, $arr);

				
				if($editId == null){
					$resources = [
						"package_id" => $idNewData,
						"url" => $url,
						"description" => '',
						"name" =>$fileName.".json",
						"format"=>'json'
					];
					$callUrluptres = $ckan . "/api/action/resource_create";
					$return = $api->updateRequest($callUrluptres, $resources, "POST"); 
				} else {
					$resources = [
						//"package_id" => $idNewData,
						"id" => $editId,
						"url" => $url,
						"description" => '',
						"name" => $fileName.".json",
						"format" => 'json',
						//"clear_upload" => true
					];
					
					$callUrluptres = $ckan . "/api/action/resource_update";
					$return = $api->updateRequest($callUrluptres, $resources, "POST");
				}
			}
			if($add_tres){
				sleep(20);
			}
			$api->calculateVisualisations($idNewData);
        }
       
                        
    }
	
	static function harvestDataGouv($ckan, $api, $id_dataset, $id_dataset_gouv, $name, $id_org, $update, $resource,$date_last_moi) {
		Logger::logMessage("Harvest DataGouvFR " . $id_dataset_gouv ."\r\n");

		$query = Query::callSolrServer("https://www.data.gouv.fr/api/1/datasets/".$id_dataset_gouv);
		$results = json_decode($query);

		Logger::logMessage(" -> Calling 'https://www.data.gouv.fr/api/1/datasets/". $id_dataset_gouv . "' ... \r\n");

		$tagsData = array();
		if ($results->tags == '' || count($results->tags)==0 || !$results->tags) {
			$tagsData = [];
		} 
		else {
			$tags = $results->tags;
			for ($j = 0; $j < count($tags); $j++) {
				if($tags[$j]!=''){
					$val = DataSet::nettoyage($tags[$j]);
					array_push($tagsData, ["vocabulary_id" => null, "state" => "active", "display_name" => $val, "name" => $val]);
				}
			}  
		}

		Logger::logMessage(" -> Getting tags ... \r\n");

		$resources=array();

		$extras =array();
		
		$result2 = $api->getPackageShow("id=".$id_dataset);		
		$result2 = $result2["result"];
		$extras = $result2[extras];
		
		$lastmod = $results->last_modified;
		$externalDatasetLastModificationLog = date('m/d/Y H:i:s', strtotime($lastmod));

		Logger::logMessage(" -> Checking previous modification ... \r\n");
		
		$prevmod = "1970-01-01T00:00:00";
		foreach($extras as &$ext){
			if($ext['key'] == 'date_moissonnage_last_modification') {
				$prevmod = $ext['value'];
				break;
			}
		}
		if($prevmod) {
			//error_log('moissonage datagouv id : ' . $name . ' test : ' . strtotime($lastmod) . ' -- ' . strtotime($prevmod));
			if($date_last_moi != null){
				if (strtotime($lastmod) <= strtotime($date_last_moi)) {

				$datasetLastModificationLog = date('m/d/Y H:i:s', strtotime($date_last_moi));
				
				Logger::logMessage(" -> Dataset last modification " . $datasetLastModificationLog ." is superior to external dataset modification '" . $externalDatasetLastModificationLog . "' \r\n");

				$lastHarvest = "1970-01-01T00:00:00";
				Logger::logMessage(" -> Checking extra harvest last update ... \r\n");
				if ($results->extras && $results->extras->{'harvest:last_update'}) {
					$lastHarvest = $results->extras->{'harvest:last_update'};
				}

				$lastmod = $lastHarvest;
				$externalDatasetLastModificationLog = date('m/d/Y H:i:s', strtotime($lastmod));

				Logger::logMessage(" -> Found last harvest '" . $externalDatasetLastModificationLog .  "' \r\n");

				if (strtotime($lastmod) <= strtotime($date_last_moi)) {
					Logger::logMessage(" -> We do not harvest the dataset because dataset last modification " . $datasetLastModificationLog ." is superior to external dataset modification '" . $externalDatasetLastModificationLog . "' and extra harvest:last_update '" . $externalDatasetLastModificationLog . "'\r\n\r\n");
					return;
				}
			}
			}
			else {
				if (strtotime($lastmod) <= strtotime($prevmod)) {

				$datasetLastModificationLog = date('m/d/Y H:i:s', strtotime($prevmod));
				
				Logger::logMessage(" -> Dataset last modification " . $datasetLastModificationLog ." is superior to external dataset modification '" . $externalDatasetLastModificationLog . "' \r\n");

				$lastHarvest = "1970-01-01T00:00:00";
				Logger::logMessage(" -> Checking extra harvest last update ... \r\n");
				if ($results->extras && $results->extras->{'harvest:last_update'}) {
					$lastHarvest = $results->extras->{'harvest:last_update'};
				}

				$lastmod = $lastHarvest;
				$externalDatasetLastModificationLog = date('m/d/Y H:i:s', strtotime($lastmod));

				Logger::logMessage(" -> Found last harvest '" . $externalDatasetLastModificationLog .  "' \r\n");

				if (strtotime($lastmod) <= strtotime($prevmod)) {
					Logger::logMessage(" -> We do not harvest the dataset because dataset last modification " . $datasetLastModificationLog ." is superior to external dataset modification '" . $externalDatasetLastModificationLog . "' and extra harvest:last_update '" . $externalDatasetLastModificationLog . "'\r\n\r\n");
					return;
				}
			}
			}
		}
		
		Logger::logMessage(" -> Modification check is OK ... \r\n");

		//error_log('moissonage datagouv id : ' .$name . ' moissonnage');
		if(count($extras)==0){
			$extras[count($extras)]['key'] = 'LinkedDataSet';
			$extras[(count($extras) - 1)]['value'] = '';

			$extras[count($extras)]['key'] = 'theme';
			$extras[(count($extras) - 1)]['value'] = 'default';

			$extras[count($extras)]['key'] = 'label_theme';
			$extras[(count($extras) - 1)]['value'] = 'Default';

			//$extras[count($extras)]['key'] = 'type_map';
			//$extras[(count($extras) - 1)]['value'] = 'osm';

			$extras[count($extras)]['key'] = 'FTP_API';
			$extras[(count($extras) - 1)]['value'] = 'https://www.data.gouv.fr/fr/datasets/'.$id_dataset_gouv.'/'; 
		}

		//set new date in extras

		Logger::logMessage(" -> Setting new date in extra ... \r\n");

		$exists = false;
		foreach($extras as &$ext){
			if($ext['key'] == 'date_moissonnage_last_modification') {
				$ext['value'] = $lastmod;
				$exists = true;
				
				Logger::logMessage(" -> Extra found, we set it to '" . $externalDatasetLastModificationLog . "' \r\n");
				break;
			}
		}
		if(!$exists) {
			Logger::logMessage(" -> Extra not found, we had it '" . $externalDatasetLastModificationLog . "' \r\n");
			$extras[count($extras)]['key'] = 'date_moissonnage_last_modification';
			$extras[(count($extras) - 1)]['value'] = $lastmod; 
		}
		
		$description = $results->description;
			
		$description = preg_replace("/\\n/", "<br>", $description);
		$description = preg_replace("/(?:http(s)?:\/\/)?[\w.-]+(?:\.[\w\.-]+)+[\w\-\._~:\/?#[\]@!\$&'\*\+,;=.]+/", "<a href='$0' target='_blank'>$0</a>", $description);
		
		// FIXME: Should be done at the end because if something goes wrong the modification date is false
		Logger::logMessage(" -> We update the package \r\n");
		$query = DataSet::updatePackage($name, $id_org, $description, $results->license, $tags, $id_dataset, $extras);

		$idNewData = $id_dataset;
		$old_resources = $result2[resources];
		$add_tres = false;
		$geo_res = array();
		/////////////////////resources////////////
		foreach($results->resources as &$res){

			Logger::logMessage(" -> Checking resource '" . $res->title . "' \r\n");
			
			$editId = null;
			$root='/home/user-client/drupal-d4c/sites/default/files/dataset/';
			$host = $_SERVER['HTTP_HOST'];

			Logger::logMessage(" -> Resource format is '" . $res->format . "' \r\n");

			if ($res->format == 'CSV' || $res->format == 'XLS' || $res->format == 'XLSX' || $res->format == 'csv' || $res->format == 'xls' || $res->format == 'xlsx'){

				$add_tres=true;

				$filepathN = $res->url;
				$filepathN = explode('/',$filepathN);
				$filepathN = $filepathN[count($filepathN)-1];
				$filepathN = explode('.',$filepathN)[0]; 
				$filepathN =urldecode($filepathN);  
				$filepathN = strtolower($filepathN);

				$url_res = 'https://'.$host.'/sites/default/files/dataset/';    

				if( $res->format == 'XLS' || $res->format == 'XLSX'  || $res->format == 'xls' || $res->format == 'xlsx') {
					
					Logger::logMessage(" -> Managing XLS or XLSX \r\n");

					$title_f= $res->title.'_xls';
					switch ($res->format) {
						case 'XLS':
							$filepathN = explode(".",$filepathN)[0]. "_" . uniqid().'.XLS';
							$filepathDell =  $filepathN;
							$reader = new Xls();
							break;
						case 'XLSX':
							$filepathN = explode(".",$filepathN)[0]. "_" . uniqid().'.XLSX';
							$filepathDell =  $filepathN;
							$reader = new Xlsx();
							break;
						case 'xls':
							$filepathN = explode(".",$filepathN)[0]. "_" . uniqid().'.xls';
							$filepathDell =  $filepathN;
							$reader = new Xls();
							break;
						case 'xlsx':
							$filepathN = explode(".",$filepathN)[0]. "_" . uniqid().'.xlsx';
							$filepathDell =  $filepathN;
							$reader = new Xlsx();
							break;                    
					}

					$url_res = $url_res.'xls_'.$filepathN; 

					$file=$res->url;
					$host=$root.''.$filepathN;
					copy($file, $host);//copy file xls
					chmod($host, 0777);

					$xls_file = $root.''.$filepathN;

					$spreadsheet = $reader->load($xls_file);

					$loadedSheetNames = $spreadsheet->getSheetNames();

					$writer = new Csv($spreadsheet);

					foreach($loadedSheetNames as $sheetIndex => $loadedSheetName) {
						$writer->setSheetIndex($sheetIndex);

						$csvpath = str_replace(array('.xlsx', '.xls', '.XLSX', '.XLS'), array('.csv', '.csv', '.csv', '.csv'), $root.'xls_'.$filepathN);
						$url_res = str_replace(array('.xlsx', '.xls', '.XLSX', '.XLS'), array('.csv', '.csv', '.csv', '.csv'), $url_res);
						$fileName = str_replace(array('.xlsx', '.xls', '.XLSX', '.XLS'), array('.csv', '.csv', '.csv', '.csv'), $filepathN);

						$writer->save($csvpath);

						$dell_old_xls = unlink($root.''.$filepathDell);

						break;
					}

					$arr = file($url_res);
					//$label = utf8_decode($arr[0]);
					$label = $arr[0];

					$label = DataSet::nettoyage($label);  
					// edit first line
					$arr[0] = $label;

					// write back to file
					file_put_contents($root.'xls_'.$fileName, implode($arr));

					Logger::logMessage(" -> Checking for correspondance with old resource \r\n");
					//$query = DataSet::createResource($idNewData,$url_res,$res->description, $title_f, 'csv','false');
					foreach($old_resources as $oldRes){
						Logger::logMessage(" -> Checking old resource name '" . $oldRes["name"] . "' with '" . $res->id . "' and '" . strtolower($oldRes["format"]) . "' == csv ... \r\n");
						if($oldRes["name"] == $res->id && strtolower($oldRes["format"]) == "csv"){
							$editId = $oldRes["id"];
							break;
						}
					}
					
					if($editId == null){
						Logger::logMessage(" -> Edit ID is null, we call updateRequest() with resource_create \r\n");

						$resources = [
							"package_id" => $idNewData,
							"url" => $url_res,
							"description" => $res->description,
							"name" =>$res->id,
							"format"=>'csv'
						];
						$callUrluptres = $ckan . "/api/action/resource_create";
						$return = $api->updateRequest($callUrluptres, $resources, "POST"); 
					} else {
						Logger::logMessage(" -> Edit ID is not null, we call updateResourceAndPushDatastore() \r\n");

						$resources = [
							//"package_id" => $idNewData,
							"id" => $editId,
							"url" => $url_res,
							"description" => $res->description,
							"name" => $res->id,
							"format" =>'csv',
							"clear_upload" => true
						];
						
						/*$callUrluptres = $ckan . "/api/action/resource_update";
						$return = $api->updateRequest($callUrluptres, $resources, "POST");*/
						$return = $api->updateResourceAndPushDatastore($resources);
					}
				}
				else if($res->format == 'csv' || $res->format == 'CSV') {

					Logger::logMessage(" -> Managing CSV \r\n");

					$filepathN = explode(".",$filepathN)[0]. "_" . uniqid().'.csv';
					$url_res = $url_res.''.$filepathN;

					// read into array
					//$arr = file('/home/user-client/drupal-d4c'.$filepath);
					$arr = file($res->url);
					//$label = utf8_decode($arr[0]);
					$label = $arr[0];
					$label = DataSet::nettoyage($label);  

					// edit first line
					$arr[0] = $label;

					// write back to file
					file_put_contents($root.''. $filepathN, implode($arr));
					
					Logger::logMessage(" -> Checking for correspondance with old resource \r\n");
					foreach($old_resources as $oldRes){
						Logger::logMessage(" -> Checking old resource name '" . $oldRes["name"] . "' with '" . $res->id . "' and old format '" . strtolower($oldRes["format"]) . "' = '" . strtolower($res->format) . "' ... \r\n");
						if($oldRes["name"] == $res->id && strtolower($oldRes["format"]) == strtolower($res->format)){
							$editId = $oldRes["id"];
							break;
						}
					}

					// $query = DataSet::createResource($idNewData,$url_res,$res->description,$res->title, $res->format,'false'); 
					
					if($editId == null) {
						Logger::logMessage(" -> Edit ID is null, we call updateRequest() with resource_create \r\n");

						$resources = [
							"package_id" => $idNewData,
							"url" => $url_res,
							"description" => $res->description,
							"name" =>$res->id,
							"format"=>$res->format
						];
						$callUrluptres = $ckan . "/api/action/resource_create";
						$return = $api->updateRequest($callUrluptres, $resources, "POST"); 
					} else {
						Logger::logMessage(" -> Edit ID is not null, we call updateResourceAndPushDatastore() \r\n");

						$resources = [
							//"package_id" => $idNewData,
							"id" => $editId,
							"url" => $url_res,
							"description" => $res->description,
							"name" => $res->id,
							"format" =>$res->format,
							"clear_upload" => true
						];
						
						/*$callUrluptres = $ckan . "/api/action/resource_update";
						$return = $api->updateRequest($callUrluptres, $resources, "POST");*/
						$return = $api->updateResourceAndPushDatastore($resources);
					}
					
				}
			}
			else {
				Logger::logMessage(" -> Managing other format \r\n");

				$url_res = $res->url;
				//$query = DataSet::createResource($idNewData,$url_res,$res->description,$res->title, $res->format,'false');
			  
				Logger::logMessage(" -> Checking for correspondance with old resource \r\n");
				foreach($old_resources as $oldRes){
					Logger::logMessage(" -> Checking old resource name '" . $oldRes["name"] . "' with '" . $res->id . "' and old format '" . strtolower($oldRes["format"]) . "' = '" . strtolower($res->format) . "' ... \r\n");
					if ($oldRes["name"] == $res->id && strtolower($oldRes["format"]) == strtolower($res->format)){
						$editId = $oldRes["id"];
						break;
					}
				}
			  
				
				if($editId == null){
					Logger::logMessage(" -> Edit ID is null, we call updateRequest() with resource_create \r\n");

					$resources = [
						"package_id" => $idNewData,
						"url" => $url_res,
						"description" => $res->description,
						"name" =>$res->id,
						"format"=>$res->format
					];
					$callUrluptres = $ckan . "/api/action/resource_create";
					$return = $api->updateRequest($callUrluptres, $resources, "POST"); 
				} else {
					Logger::logMessage(" -> Edit ID is not null, we call updateRequest() with resource_update \r\n");

					$resources = [
						//"package_id" => $idNewData,
						"id" => $editId,
						"url" => $url_res,
						"description" => $res->description,
						"name" => $res->id,
						"format" =>$res->format,
						//"clear_upload" => true
					];
					
					$callUrluptres = $ckan . "/api/action/resource_update";
					$return = $api->updateRequest($callUrluptres, $resources, "POST");
				}
				
				if (strtolower($res->format) == 'geojson' || strtolower($res->format) == 'kml' || (strtolower($res->format) == 'json' && (strpos(strtolower($res->title), "export geojson") !== false || strpos(strtolower($res->description), "export geojson") !== false))) {
					
					Logger::logMessage(" -> Format is a geo format so we add it to geo_res array \r\n");

					$geo_res[strtolower($res->format)] = $res->url;
				}
			}  
		}
			
		$command = NULL;
		if($add_tres){
			Logger::logMessage(" -> Add ressource, we wait 20 sec \r\n");

			sleep(20);
		}
		else if($add_tres == FALSE && count($geo_res) > 0){
			Logger::logMessage(" -> We do not add classic resource and we have a geo resource. We create a CSV \r\n");

			// We create a CSV
			$name = $label;
			$rootCsv='/home/user-client/drupal-d4c/sites/default/files/dataset/'.$name . "_" . uniqid().'.csv';
			$rootJson='/home/user-client/drupal-d4c/sites/default/files/dataset/'.$name.'.geojson';
			$urlCsv = 'https://'.$_SERVER['HTTP_HOST'].'/sites/default/files/dataset/'.$name . "_" . uniqid().'.csv';
			if($geo_res["geojson"] != null){
				$url = $geo_res["geojson"];
				$json = Query::callSolrServer($url);
				$csv = Export::createCSVfromGeoJSON($json);
				
				file_put_contents($rootCsv, $csv);
			}
			else if($geo_res["json"] != null){
				$url = $geo_res["json"];
				$json = Query::callSolrServer($url);
				$csv = Export::createCSVfromGeoJSON($json);
				
				file_put_contents($rootCsv, $csv);
			}
			else {
				$url = $geo_res["kml"];
				//We create a tmp file in which we write the result and an output file to convert
				$pathInput = tempnam(sys_get_temp_dir(), 'input_convert_geo_file_');
				$fileInput = fopen($pathInput, 'w');
				$kml = Query::callSolrServer($url);
				fwrite($fileInput, $kml);
				fclose($fileInput);

				//Get current Php directory to call the script
				$dir = dirname(__FILE__);
				$scriptPath = $dir.'/../Utils/convert_geo_files_ogr2ogr.sh';

				$typeConvert = 'GeoJSON';
			
				$command = $scriptPath." 2>&1 '".$typeConvert."' ".$rootJson." ".$pathInput."";
				$message = shell_exec($command);
				$json = file_get_contents ($rootJson);
				$csv = Export::createCSVfromGeoJSON($json);
				
				file_put_contents($rootCsv, $csv);
				
				unlink ($pathInput);
				unlink ($rootJson);
			}
			
			Logger::logMessage(" -> Checking for correspondance with old resource \r\n");
			foreach($old_resources as $oldRes){
				Logger::logMessage(" -> Checking old resource name '" . $oldRes["name"] . "' with '" . $res->id . "' and old format '" . strtolower($oldRes["format"]) . "' = csv ... \r\n");
				if($oldRes["name"] == ($name.".csv") && strtolower($oldRes["format"]) == "csv"){
					$editId = $oldRes["id"];
					break;
				}
			}
			
			if($editId == null){
				Logger::logMessage(" -> Edit ID is null, we call updateRequest() with resource_create \r\n");

				$resources = [
					"package_id" => $idNewData,
					"url" => $urlCsv,
					"description" => '',
					"name" =>$name.".csv",
					"format"=>'csv'
				];
				$callUrluptres = $ckan . "/api/action/resource_create";
				$return = $api->updateRequest($callUrluptres, $resources, "POST"); 
				// $this->renderResourceLog($resource["name"], $return);
			}
			else {
				Logger::logMessage(" -> Edit ID is not null, we call updateResourceAndPushDatastore() \r\n");

				$resources = [
					//"package_id" => $idNewData,
					"id" => $editId,
					"url" => $urlCsv,
					"description" => '',
					"name" => $name.".csv",
					"format" =>'csv',
					"clear_upload" => true
				];
				
				$callUrluptres = $ckan . "/api/action/resource_update";
				/*$return = $api->updateRequest($callUrluptres, $resources, "POST");
				$this->renderResourceLog($resource["name"], $return);*/
				$return = $api->updateResourceAndPushDatastore($resources);
			}
			
			# Deactivated for now
			// $pathUserClient = '/home/user-client';
			// $pathUserClientData = $pathUserClient . '/data';
			// $buildGeoloc = 'false';
			// $selectedSeparator = ",";
			// $selectedEncoding = "UTF-8";
			// $onlyOneAddress = 'false';
			// $selectedAddress = "";
			// $selectedPostalCode = "";
			// $command = $pathUserClientData . '/geoloc.sh "' . $buildGeoloc . '" "' . $ckan . '" "' . $config_file->ckan->api_key . '" "' . $result2["name"] . '" "' . $return["result"]["id"] . '" "' . $selectedSeparator . '" "' . $selectedEncoding . '" "' . $onlyOneAddress . '" "' . $selectedAddress . '" "' . $selectedPostalCode . '"';
			
			Logger::logMessage(" -> We wait 20 sec \r\n");

			sleep(20);
		}
		
		Logger::logMessage(" -> Visualisation calculation ... \r\n");

		$api->calculateVisualisations($idNewData);
		
		Logger::logMessage(" -> DataGouv dataset management ended. \r\n\r\n\r\n");
		
		# Deactivated for now
		// if($command != NULL){
		// 	error_log($command);
		// 	$output = shell_exec($command);
		// 	error_log($output);
		// }
		
		return $query;
	}
    
    static function nettoyage( $str, $charset='utf-8' ) {
          
		//$str = utf8_decode($str);
			 
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
		$str = str_replace(".", "_", $str);
		$str = strtolower($str);     
		$str = preg_replace( '#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str );
		$str = preg_replace( '#&([A-za-z]{2})(?:lig);#', '\1', $str );
		$str = preg_replace( '#&[^;]+;#', '', $str );        
		
		return $str;     
			 
	}
    
    
    function join2csv($url1, $url2, $nameFile, $columns_data, $columns_data2){
    
		if($_SERVER['HTTP_HOST']=='192.168.2.217'){
			$root='/home/bpm/drupal-8.6.15/sites/default/files/dataset/';
		}
		else{
			$root='/home/user-client/drupal-d4c/sites/default/files/dataset/';
		}
    
		$host = $_SERVER['HTTP_HOST'];
		if($_SERVER['HTTP_HOST']=='192.168.2.217'){
                
			 $url_res = 'http://'.$host.'/sites/default/files/dataset/';
		}
		else{
			$url_res = 'https://'.$host.'/sites/default/files/dataset/';
		} 
         
        $url_res = $url_res.$nameFile. "_" . uniqid().'.csv';       
    
        $fh = fopen($url1, 'r');
        $fhg = fopen($url2, 'r');
    
    
		$arr1 = file($url1);
		$arr2 = file($url2);
		$separator1 = ',';
		$separator2 = ',';
		if(strpos(utf8_decode($arr1[0]), ';')!== false){
		   $separator1 = ';'; 
			
		}
		if(strpos(utf8_decode($arr2[0]), ';')!== false){
		   $separator2 = ';'; 
			
		}
		if(strpos(utf8_decode($arr2[0]), ' ')!== false){
		   $separator2 = ' '; 
			
		}
    
    
		while (($data = fgetcsv($fh, 0, $separator1)) !== FALSE) {
            $csv1[]=$data;
        }
		while (($data = fgetcsv($fhg, 0, $separator2)) !== FALSE) {
            $csv2[]=$data;
            $csv2_2[]=$data;
        }
        

		$index_column_join = array_search($columns_data, $csv1[0]);
		$index_column_join2 = array_search($columns_data2, $csv2[0]);
		
		$arr_dupl_column_csv1=array();
		
		$arr_csv1 = array();
		$arr_csv2 = array();
		
		for($a = 1; $a<count($csv1); $a++){

			$arr=array();
        
			for($b = 0; $b<count($csv1[$a]); $b++){
			   $arr[$csv1[0][$b]]= $csv1[$a][$b];
			}
        
			$arr_csv1[] = $arr;
    
		}
    
		for($a = 1; $a<count($csv2); $a++){

			$arr=array();
        
			for($b = 0; $b<count($csv2[$a]); $b++){
				$arr[$csv2[0][$b]]= $csv2[$a][$b];
			}
        
			$arr_csv2[] = $arr;
    
		}
    
    
		unset($csv1[0][$index_column_join]);
		$nome_column_new=array_unique(array_merge($csv2[0],$csv1[0]));
		//drupal_set_message('<pre>'. print_r($nome_column_new, true) .'</pre>');    
			
		
		for($x=0;$x< count($arr_csv2);$x++)
		{
       
            $deadlook=0;
			for($y=0;$y < count($arr_csv1);$y++)
			{
				if($arr_csv1[$y][$columns_data] == $arr_csv2[$x][$columns_data2]){
					unset($arr_csv1[$y][$columns_data]);
					$line[$x]=array_merge($arr_csv2[$x],$arr_csv1[$y]);
					$deadlook=1;
					unset($arr_csv1[$y]);
					
				}
			
			}
            
            if($deadlook==0) $line[$x]=$arr_csv2[$x];
		}
    
		$arr_csv1=array_values($arr_csv1);
    
//    	drupal_set_message('<line>'. print_r(json_encode($line), true) .'</line>'); 
//    	drupal_set_message('<csv1>'. print_r($arr_csv1, true) .'</csv1>'); 
    
		for($x=0;$x< count($arr_csv1);$x++){
		   
			$arr_csv1[$x][$columns_data2]=$arr_csv1[$x][$columns_data];
			if($columns_data!=$columns_data2){
				unset($arr_csv1[$x][$columns_data]);
			}    
			$line[]=$arr_csv1[$x];    
		}
    
		//drupal_set_message('<line>'. print_r(json_encode($line), true) .'</line>');
    
    
		$res_arr = array();
		$nome_column_new=array_values($nome_column_new);
		$res_arr[0] = $nome_column_new;
    
    
		for($x=0; $x<count($line); $x++){
			for($y=0; $y <count($nome_column_new); $y++){
			   
				$val='';
			   
				//drupal_set_message('<pre>'.$nome_column_new[$y].'</pre>');
			   
				if($line[$x][$nome_column_new[$y]]) $val=$line[$x][$nome_column_new[$y]];
			   
				$res_arr[$x+1][$y] = $val;
			} 
		}
    
    
		// 3 section     
        $fp = fopen($root.$nameFile.'.csv', 'w');//output file set here

        foreach ( $res_arr as $fields) {
            fputcsv($fp, $fields);
        }
        fclose($fp);
    
	// drupal_set_message('<pre>'. print_r($url_res, true) .'</pre>');
    
    
		return $url_res;
	}    

	static function mixExtras($exCible, $exSource) {
		$exCible = json_decode($exCible, true);
		$exSource = json_decode($exSource, true);
		//error_log("ddddddddddddddaaaaad  ".json_encode(array_column($exCible, "key")));
		foreach($exSource as $itSource){//error_log("S ".$itSource["key"]);
			if($itSource["key"] == "date_moissonnage_last_modification"){
				foreach($exCible as $k=>$itCible){
					if($itCible["key"] == "date_moissonnage_last_modification"){
						$exCible[$k]["value"] = $itSource["value"];
						//error_log("ddddddddddddddd  ". $itCible["value"]);
						break;
					}
				}
			} else {
				$bFound = false;
				foreach($exCible as $itCible){
					if($itCible["key"] == $itSource["key"]){//error_log("C ".$itSource["key"]);
						$bFound = true;
						break;
					}
				}
				if(!$bFound){//error_log("NC ".$itSource["key"]);
					$it = array();
					$it["key"] = $itSource["key"];
					$it["value"] = $itSource["value"];
					$exCible[] = $it;
				}
			}
		}//error_log("ddddddddddddddtttttttd  ".json_encode(array_column($exCible, "key")));
		return json_encode($exCible);
	}
	
	static function getHarvestDatasets() {
        $config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
		return $config->get('dataForUpdateDatasets');
	}
	
	static function getHarvestDatasetInformations() {
		$api = new Api();

		$harvestDatasetJson = Dataset::getHarvestDatasets();
		$harvestDatasets = json_decode($harvestDatasetJson);
		
        foreach($harvestDatasets as &$harvestDataset) {

              foreach ($harvestDataset->datasets as $key2 => &$dataset) {
				
				//Getting harvest's URL
                $datasetInfos = $api->getPackageShow2($dataset->id_data, "");
                $met = $datasetInfos[metas][extras];
				for($i=0; $i < count($met); $i++){
                    if($met[$i]['key']=='FTP_API'){
						if($met[$i][value]!='FTP'){
                        	$dataset->siteUrl =  $met[$i][value];
                        } 
					}
				}

				//Retrieve last update date and next execution
				$last_update = $dataset->last_update;
				$periodic_update = $dataset->periodic_update;
                
                if ($periodic_update == null || $periodic_update == "") {
					//The dataset is updated every day at 5
					$dataset->next_update = date("Y-m-d H:i:s", mktime(5,0,0, date('n'), date('j')+1, date('Y')));
                }
                else{
                    $periodic_update = explode(";", $periodic_update);
           
					if($periodic_update[2]=='A'){
						$date=1;
						if($periodic_update[1] == ''){
							$periodic_update[1] = 1;
						}
					  
						switch ($periodic_update[0]) {
						case 'Mi':
							$date = $periodic_update[1] * 60;
							break;
						case 'H':
							$date = $periodic_update[1] * 3600;
							break;
						case 'D':
							$date = $periodic_update[1] * 86400;
							break;
						case 'W':
							$date = $periodic_update[1] * 604800;
							break;
						case 'M':
							$date = $periodic_update[1] * 2592000;
							break;
						case 'Y':
							$date = $periodic_update[1] * 31536000;
							break;
						default:
							$date=0;
						}
					  
						//Calculate next update
						$last_update = strtotime($last_update);
						$next_update = $last_update + $date;

						$dataset->next_update = date("m/d/Y H:i:s", $next_update);
					}
                }
            }

			Logger::logMessage("Get harvest datasets : ");
			Logger::logMessage(json_encode($harvestDataset));
		}

		return $harvestDatasets;
	}
}