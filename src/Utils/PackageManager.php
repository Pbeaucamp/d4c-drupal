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

        //Create json file 
         /*$datasetJson= [
		  "dataset" => [
		    'license_title' => $contentdataset["result"]["license_title"],
		    'maintainer' => $contentdataset["result"]["maintainer"],
		    'relationships_as_object' => $contentdataset["result"]["relationships_as_object"],
		    'private' => $contentdataset["result"]["private"],
		    'maintainer_email' => $contentdataset["result"]["maintainer_email"],
		    'num_tags' => $contentdataset["result"]["num_tags"],
		    'id' => $contentdataset["result"]["id"],
		    'metadata_created' => $contentdataset["result"]["metadata_created"],
		    'metadata_modified' => $contentdataset["result"]["metadata_modified"],
		    'author' => $contentdataset["result"]["author"],
		    'author_email' => $contentdataset["result"]["author_email"],
		    'state' => $contentdataset["result"]["state"],
		    'version' => $contentdataset["result"]["version"],
		    'creator_user_id' => $contentdataset["result"]["creator_user_id"],
		    'type' => $contentdataset["result"]["type"],
		    'num_resources' => $contentdataset["result"]["num_resources"],
		    'tags' => $contentdataset["result"]["tags"],
		    'groups' => $contentdataset["result"]["groups"],
		    'license_id' => $contentdataset["result"]["license_id"],
		    'relationships_as_subject' => $contentdataset["result"]["relationships_as_subject"],
		    'organization' => $contentdataset["result"]["organization"],
		    'name' => $contentdataset["result"]["name"],
		    'isopen' => $contentdataset["result"]["isopen"],
		    'url' => $contentdataset["result"]["url"],
		    'notes' => $contentdataset["result"]["notes"],
		    'owner_org' => $contentdataset["result"]["owner_org"],
		    'extras' => $contentdataset["result"]["extras"],
		    'title' => $contentdataset["result"]["title"],
		    'revision_id' => $contentdataset["result"]["revision_id"],
		  ]
		];*/

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

		/*****       create datasetinfo json file   *****/
       // search dataset data by id in array of all datasets 
        $datasetinfo = $this->getDatasetInformations($id);

        if (!file_exists($_SERVER['DOCUMENT_ROOT']."/packageDataset/".$id)) {
        	
		    mkdir($_SERVER['DOCUMENT_ROOT']."/packageDataset/".$id, 0777, true);
		}
		if (!file_exists($_SERVER['DOCUMENT_ROOT']."/packageDataset/".$id."/Ressources")) {
        	
		    mkdir($_SERVER['DOCUMENT_ROOT']."/packageDataset/".$id."/Ressources", 0777, true);
		}

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
	

		return $response;

	}

	


}
