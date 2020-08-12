<?php

namespace Drupal\ckan_admin\Utils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Drupal\ckan_admin\Utils\Export;
use ZipArchive;
use Drupal\file\Entity\File;
use Drupal\ckan_admin\Utils\Api;
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

class PackageManager {
	
	//protected $config = \Drupal::config('ckan_admin.settings');
	protected $urlCkan;// = "http://192.168.2.223/";
	//protected $urlCkan = file_get_contents(__DIR__ ."/../../config.json");
	protected $config;
    //-------------- 
    
	public function __construct(){
        $this->config = json_decode(file_get_contents(__DIR__ ."/../../config.json"));
		$this->urlCkan = $this->config->ckan->url;
    }



    // Get dataset information by id 
    public function getDatasetInformations($id) {
    	$api = new Api();
    	$dataset = $api->getDataSetById($id);
        $contentdataset = json_decode($dataset->getContent(),true);


        return $dataset;

    }



    // Get dataset resources by id 
    public function getResources($id) {
    	$api = new Api();
    	$dataset = $api->getDataSetById($id);
        $contentdataset = json_decode($dataset->getContent(),true);

        //Create json file 
         $datasetJson= [
		  "datasetResources" => [
		    'resources' => $contentdataset["result"]["resources"]
		  ]
		];


        return $contentdataset["result"]["resources"];

    }
    
	public function createPackageZip($id){

		$api = new Api();

		// search dataset data by id in array of all datasets 
        $datasetinfo = $this->getDatasetInformations($id);
        if (!file_exists($_SERVER['DOCUMENT_ROOT']."/packageDataset/".$id)) {
        	
		    mkdir($_SERVER['DOCUMENT_ROOT']."/packageDataset/".$id, 0777, true);
		}
		if (!file_exists($_SERVER['DOCUMENT_ROOT']."/packageDataset/".$id."/Ressources")) {
        	
		    mkdir($_SERVER['DOCUMENT_ROOT']."/packageDataset/".$id."/Ressources", 0777, true);
		}


		if(isset($_GET['xml']) && $_GET['xml'] == "true") {
			$response = new Response(json_encode(array('filename' => $_GET['xml'])));

        	$contentdataset = json_decode($datasetinfo->getContent(),true);

			$dataset = new \SimpleXMLElement('<Dataset></Dataset>');
			$dataset->addAttribute('url', 'https://kmo-backoffice.data4citizen.com/api/3/action/help_show?name=package_show');
			$dataset->addAttribute('type', 'dataset');

			$results = $dataset->addChild('results');
			if(array_key_exists('license_title', $contentdataset["result"])) {
				$results->addChild('license_title', $contentdataset["result"]["license_title"]);
			}
			if(array_key_exists('maintainer', $contentdataset["result"])) {
				if($contentdataset["result"]["maintainer"]) 
					$maintainer =$contentdataset["result"]["maintainer"];
				else 
					$maintainer='""';
				$results->addChild('maintainer', $maintainer);
			}
			if(array_key_exists('relationships_as_object', $contentdataset["result"])) {
				
				if(sizeof($contentdataset["result"]["relationships_as_object"]) > 0 ) {

				}
				else {
					$relationships_as_object ="[]";
				}
				$results->addChild('relationships_as_object', $relationships_as_object);
			}
		
			if(array_key_exists('private', $contentdataset["result"])) {
				if($contentdataset["result"]["private"]) 
					$private = "true";
				else
					$private ="false";
				$results->addChild('private', $private);
			}

			if(array_key_exists('maintainer_email', $contentdataset["result"])) {
				if($contentdataset["result"]["maintainer_email"]) 
					$maintainer_email =$contentdataset["result"]["maintainer_email"];
				else 
					$maintainer_email='""';
				$results->addChild('maintainer', $maintainer_email);
			}

			if(array_key_exists('num_tags', $contentdataset["result"])) {

				$results->addChild('num_tags', $contentdataset["result"]["num_tags"]);
			}
			if(array_key_exists('id', $contentdataset["result"])) {

				$results->addChild('id', $contentdataset["result"]["id"]);
			}
			if(array_key_exists('metadata_created', $contentdataset["result"])) {

				$results->addChild('metadata_created', $contentdataset["result"]["metadata_created"]);
			}

			if(array_key_exists('metadata_modified', $contentdataset["result"])) {

				$results->addChild('metadata_modified', $contentdataset["result"]["metadata_modified"]);
			}
			if(array_key_exists('author', $contentdataset["result"])) {
				if($contentdataset["result"]["author"]) 
					$author =$contentdataset["result"]["author"];
				else 
					$author='""';
				$results->addChild('author', $author);
			}

			if(array_key_exists('author_email', $contentdataset["result"])) {
				if($contentdataset["result"]["author_email"]) 
					$author_email =$contentdataset["result"]["author_email"];
				else 
					$author_email='""';
				$results->addChild('author_email', $author_email);
			}

			if(array_key_exists('state', $contentdataset["result"])) {

				$results->addChild('state', $contentdataset["result"]["state"]);
			}

			if(array_key_exists('version', $contentdataset["result"])) {
				if($contentdataset["result"]["version"]) 
					$version =$contentdataset["result"]["version"];
				else 
					$version='""';
				$results->addChild('version', $version);
			}

			if(array_key_exists('creator_user_id', $contentdataset["result"])) {

				$results->addChild('creator_user_id', $contentdataset["result"]["creator_user_id"]);
			}
			if(array_key_exists('type', $contentdataset["result"])) {

				$results->addChild('type', $contentdataset["result"]["type"]);
			}

			if(array_key_exists('resources', $contentdataset["result"])) {
				
				$resources = $results->addChild('resources');
				foreach ($contentdataset["result"]['resources'] as $key => $value) {
					$contentresource  = $resources->addChild('resource_'.$key);
					foreach ($value as $key2 => $value2) {
						
						$contentresource->addChild($key2 , $value2);
						
					}
				}
			}

			if(array_key_exists('num_resources', $contentdataset["result"])) {

				$results->addChild('num_resources', $contentdataset["result"]["num_resources"]);
			}

			if(array_key_exists('tags', $contentdataset["result"])) {
				
				$tags = $results->addChild('tags');
				foreach ($contentdataset["result"]['tags'] as $key => $value) {
					$contenttag  = $tags->addChild('tag_'.$key);
					foreach ($value as $key2 => $value2) {
						
						$contenttag->addChild($key2 , $value2);
						
					}
				}
			}

			if(array_key_exists('groups', $contentdataset["result"])) {
				
				$groups = $results->addChild('groups');
				foreach ($contentdataset["result"]['groups'] as $key => $value) {
					$contentgroup  = $groups->addChild('group_'.$key);
					foreach ($value as $key2 => $value2) {
						
						$contentgroup->addChild($key2 , $value2);
						
					}
				}
			}

			if(array_key_exists('license_id', $contentdataset["result"])) {

				$results->addChild('license_id', $contentdataset["result"]["license_id"]);
			}

			if(array_key_exists('relationships_as_subject', $contentdataset["result"])) {
				
				$relationships_as_subject = $results->addChild('relationships_as_subject');
				foreach ($contentdataset["result"]['relationships_as_subject'] as $key => $value) {
					$contentrelationships_as_subject  = $relationships_as_subject->addChild('relation_'.$key);
					foreach ($value as $key2 => $value2) {
						
						$contentrelationships_as_subject->addChild($key2 , $value2);
						
					}
				}
			}

			if(array_key_exists('organization', $contentdataset["result"])) {
			
				$organization = $results->addChild('organization');
				foreach ($contentdataset["result"]['organization'] as $key => $value) {

						$organization->addChild($key , $value);
					
				}
			}

			if(array_key_exists('name', $contentdataset["result"])) {

				$results->addChild('name', $contentdataset["result"]["name"]);
			}
			if(array_key_exists('isopen', $contentdataset["result"])) {
				if($contentdataset["result"]["isopen"]) {
					$isopen ="true";
				}
				$isopen ="false";
				$results->addChild('isopen', $isopen);
			}

			if(array_key_exists('url', $contentdataset["result"])) {

				$results->addChild('url', $contentdataset["result"]["url"]);
			}

			if(array_key_exists('notes', $contentdataset["result"])) {
				if($contentdataset["result"]["notes"]) 
					$notes =$contentdataset["result"]["notes"];
				else 
					$notes='""';
				$results->addChild('notes', $notes);
			}

			if(array_key_exists('owner_org', $contentdataset["result"])) {

				$results->addChild('owner_org', $contentdataset["result"]["owner_org"]);
			}

			if(array_key_exists('extras', $contentdataset["result"])) {
				
				$relationships_as_subject = $results->addChild('extras');
				foreach ($contentdataset["result"]['extras'] as $key => $value) {
					$contentextras  = $relationships_as_subject->addChild('extra_'.$key);
					foreach ($value as $key2 => $value2) {
						
						$contentextras->addChild($key2 , $value2);
						
					}
				}
			}
			if(array_key_exists('title', $contentdataset["result"])) {

				$results->addChild('title', $contentdataset["result"]["title"]);
			}
			if(array_key_exists('revision_id', $contentdataset["result"])) {

				$results->addChild('revision_id', $contentdataset["result"]["revision_id"]);
			}
			
			 Header('Content-type: text/xml');
			 file_put_contents($_SERVER['DOCUMENT_ROOT']."/packageDataset/".$id."/".$contentdataset["result"]["name"].".xml", $dataset->asXML());

			 $response = new Response(json_encode(array('filename' => "/packageDataset/".$id."/".$contentdataset["result"]["name"].".xml")));
		} else {
			/*****       create datasetinfo json file   *****/
       

		 // create archive
		$zip = new ZipArchive();

		$filename = $id.".zip";

		if(file_exists($_SERVER['DOCUMENT_ROOT']."/packageDataset/".$filename)) {

	        unlink ($_SERVER['DOCUMENT_ROOT']."/packageDataset/".$filename); 

		}
		
		if ($zip->open($_SERVER['DOCUMENT_ROOT']."/packageDataset/".$filename, ZipArchive::CREATE)!==TRUE) {
		    exit("Impossible d'ouvrir le fichier <$filename>\n");
		}

	
        //save json file in root directory
		$fp = fopen($_SERVER['DOCUMENT_ROOT']."/packageDataset/".$id."/metadata.json","w");
		if( $fp == false ){

		}else{
		    fwrite($fp,$datasetinfo);
		    fclose($fp);
		    // add datasetinfo json to zip
			$zip->addFile("packageDataset/".$id."/metadata.json","/metadata.json");
		}

		

		/*****       create dataset resources json file   *****/
		// get dataset resources 
        $datasetresources = $this->getResources($id);

        foreach ($datasetresources as $key => $value) {
	        $format =$value["format"];
	        if($value["format"] == "SHP" || $value["format"] == "shp") {
	        	$value["format"] = "zip";
	        }


        	$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $value["url"]);
			$fp = fopen($_SERVER['DOCUMENT_ROOT']."/packageDataset/".$id."/Ressources/".$value["name"].".".$value["format"],"w");
			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_exec ($ch);
			curl_close ($ch);
			fclose($fp);

			// add dataset resources json to zip
			$zip->addFile("packageDataset/".$id."/Ressources/".$value["name"],"/Ressources/".$value["name"].".".$value["format"]);
        }



		// close and save archive
		$zip->close(); 


		$response = new Response(json_encode(array('filename' => "/packageDataset/".$id.".zip")));
		}

		return $response;
		

	}

	


}
