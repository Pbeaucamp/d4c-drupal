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
    	$host = \Drupal::request()->getHost();
		$protocol = \Drupal::request()->getScheme()."://";
    	$dataset = $api->getDataSetById($id);

        $contentdataset = json_decode($dataset->getContent(),true);
        $theme = "";
        $vignette = "";
    /*    echo "<pre>";*/
    /*	var_dump($contentdataset["result"]["extras"]);*/
    	foreach ($contentdataset["result"]["extras"] as $key => $value) {
    		if($value["key"] == "theme") {
    			$theme = $value["value"];
    			break;
    		}

    	}
    	
    	$themes = $api->getPackageTheme();
    	$themes = json_decode($themes->getContent(),true);
    
    	foreach ($themes as $key => $value) {
    		if($value["title"] == $theme ) {
    			$vignette = $protocol . $host .$value["url"];
    		}
    	}


		$dataset = $api->getPackageShow2($id,"");
		$resources = array();
		$resourcesid = "";
		$resourcesname = "";

/*		foreach($dataset["metas"]["resources"] as $value){
			if($value['format'] == 'CSV' || $value['format'] == 'XLS' || $value['format'] == 'XLSX'){
		 		$resourcesid = $value['id'];
		 		$resourcesname = $value['name'];
                
		 	}
			if($value['format'] != 'CSV' && $value['format'] != 'XLS' && $value['format'] != 'XLSX' && $value['format'] != 'GeoJSON' && $value['format'] != 'JSON' && $value['format'] != 'KML' && $value['format'] != 'SHP'){
				$res = array();
				$res["@type"] = "DataDownload";
				$res["name"] = $value['name'];
				$res["format"] = $value['format'];
				$res["url"] = $protocol . $host . "/api/datasets/1.0/" . $dataset["datasetid"] . "/alternative_exports/" . $value['id'];
				$resources[] = $res;
			}

		}
		
		if($resourcesid != ""){
			$res = array();
			$res["@type"] = "DataDownload";
			$res["name"] = $resourcesname;
			$res["format"] = "CSV";
			$res["url"] = $protocol . $host . "/api/records/2.0/downloadfile/format=csv&use_labels_for_header=true&resource_id=" . $resourcesid;
			$resources[] = $res;
			
			$res = array();
			$res["@type"] = "DataDownload";
			$res["format"] = "json";
			$res["name"] = $resourcesname;
			$res["url"] = $protocol . $host . "/api/records/2.0/downloadfile/format=json&resource_id=" . $resourcesid;
			$resources[] = $res;
			
			$res = array();
			$res["@type"] = "DataDownload";
			$res["format"] = "xls";
			$res["name"] = $resourcesname;
			$res["url"] = $protocol . $host . "/api/records/2.0/downloadfile/format=xls&use_labels_for_header=true&resource_id=" . $resourcesid;
			$resources[] = $res;
			

			$res = array();
			$res["@type"] = "DataDownload";
			$res["format"] = "geojson";
			$res["name"] = $resourcesname;
			$res["url"] = $protocol . $host . "/api/records/2.0/downloadfile/format=geojson&resource_id=" . $resourcesid;
			$resources[] = $res;
			
			$res = array();
			$res["@type"] = "DataDownload";
			$res["format"] = "kml";
			$res["name"] = $resourcesname;
			$res["url"] = $protocol . $host . "/api/records/2.0/downloadfile/format=kml&resource_id=" . $resourcesid;
			$resources[] = $res;
			
			$res = array();
			$res["@type"] = "DataDownload";
			$res["format"] = "shp";
			$res["name"] = $resourcesname;
			$res["url"] = $protocol . $host . "/api/records/2.0/downloadfile/format=shp&resource_id=" . $resourcesid;
			$resources[] = $res;
		}*/
       

        //Create json file 
         $datasetJson= [
		  "datasetResources" => [
		    'resources' => $contentdataset["result"]["resources"]
		  ]
		];

		if($vignette != "" ) {
			$res = array();
				$res["url_type"] = "vignette";
				$res["name"] = $theme;
				$res["format"] =  pathinfo($vignette, PATHINFO_EXTENSION);
				$res["url"] = $vignette;
				$resources[] = $res;
		}

		
		$contentdataset["result"]["resources"][] = $res;
/*		foreach ($contentdataset["result"]["resources"] as $key => $value) {
			echo "<pre>";
			var_dump($value);
			echo "</pre>";
		}
die;*/
       return $contentdataset["result"]["resources"];
      /*   return $resources;*/

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

		//$_GET['xml'] = "true";
		if(isset($_GET['xml']) && $_GET['xml'] == "true") {
			$response = new Response(json_encode(array('filename' => $_GET['xml'])));

        	$contentdataset = json_decode($datasetinfo->getContent(),true);


    	 	$xmlfile = $this->createXMLFile($contentdataset);
    	 	

			$response = new Response(json_encode(array('filename' => $xmlfile)));
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
  			
  			
  			if($value["url_type"] == "vignette") {
  			

			file_put_contents($_SERVER['DOCUMENT_ROOT']."/packageDataset/".$id."/Ressources/".$value["name"].".".$value["format"], file_get_contents($value["url"]));

  			} else {
  				$format =$value["format"];

 
	        if($value["format"] == "SHP" || $value["format"] == "shp" || $value["format"] == "Shapefile") {
	        	$value["format"] = "zip";
	        }

        	$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $value["url"]);
			$fp = fopen($_SERVER['DOCUMENT_ROOT']."/packageDataset/".$id."/Ressources/".$value["name"].".".$value["format"],"w");
			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_exec ($ch);
			curl_close ($ch);
			fclose($fp);
  			}
	        

			// add dataset resources json to zip
			$zip->addFile("packageDataset/".$id."/Ressources/".$value["name"].".".$value["format"],"/Ressources/".$value["name"].".".$value["format"]);
        }
     
		// close and save archive
		$zip->close(); 


		$response = new Response(json_encode(array('filename' => "/packageDataset/".$id.".zip")));
		}

		return $response;
		

	}

    public function createXMLFile($contentdataset){
    	$doc = new \DOMDocument('1.0',"UTF-8");

    	$api = new API();
    	$dataset = $api->getPackageShow2($contentdataset["result"]["id"],"");
    	$host = \Drupal::request()->getHost();
		$protocol = \Drupal::request()->getScheme()."://";
		$loggedIn = \Drupal::currentUser()->isAuthenticated();


    	$description = $dataset["metas"]["description"];
    	$dateModified = $dataset["metas"]["modified"];
		$keywords = $dataset["metas"]["keyword"];
		$license = $dataset["metas"]["license"];
		$thesaurusValue="";
		$thesaruskeyword ="";
		$thesarustype ="";
		$otherkeyword =[];
		$first = true;
		$codepostal ="";
		$cityValue ="";
		$adresseValue="";
		$urllogo = $protocol . $host . "/visualisation?id=" . $dataset["datasetid"];
		$resources = array();
		$resourcesid = "";
		$useLimitationsContent =array();
		$otherrestriction="";
		$accessconstraint="";
		$useconstraintvalue="";
		$topiccategory = "";
		$reporttitle = "";
		$reportdate = "";
		$explanationtitle="";
		$extentdescription ="";
		$westBoundLongitudeValue="";
		$eastBoundLongitudeValue="";
		$southBoundLatitudeValue="";
		$northBoundLatitudeValue="";
		$lineagetitle ="";

		/*var_dump($keywords);die;*/

		foreach($dataset["metas"]["resources"] as $value){

            if($value['format'] == 'CSV' || $value['format'] == 'XLS' || $value['format'] == 'XLSX'){
		 		$resourcesid = $value['id'];
                
		 	}
			if($value['format'] != 'CSV' && $value['format'] != 'XLS' && $value['format'] != 'XLSX' && $value['format'] != 'GeoJSON' && $value['format'] != 'JSON' && $value['format'] != 'KML' && $value['format'] != 'SHP'){
				$res = array();
				$url = $value["url"];
				$array = get_headers($url);
				$string = $array[0];
				if(strpos($string,"200")){
					    $res["type"] = "WWW:LINK-1.0-http--link";
					  }
				else{
					    $res["type"] = "WWW:DOWNLOAD-1.0-http--download";
				}
				
				$res["encodingFormat"] = $value['name'];
				$res["contentUrl"] = $protocol . $host . "/api/datasets/1.0/" . $dataset["datasetid"] . "/alternative_exports/" . $value['id'];
				$resources[] = $res;
		

			}
		}
	
			if($resourcesid != ""){
				$res = array();
				$res["type"] = "WWW:DOWNLOAD-1.0-http--download";
				$res["encodingFormat"] = "CSV";
				$res["contentUrl"] = $protocol . $host . "/api/records/2.0/downloadfile/format=csv&use_labels_for_header=true&resource_id=" . $resourcesid;
				$resources[] = $res;
				
				$res = array();
				$res["type"] = "WWW:DOWNLOAD-1.0-http--download";
				$res["encodingFormat"] = "JSON";
				$res["contentUrl"] = $protocol . $host . "/api/records/2.0/downloadfile/format=json&resource_id=" . $resourcesid;
				$resources[] = $res;
				
				$res = array();
				$res["type"] = "WWW:DOWNLOAD-1.0-http--download";
				$res["encodingFormat"] = "Excel";
				$res["contentUrl"] = $protocol . $host . "/api/records/2.0/downloadfile/format=xls&use_labels_for_header=true&resource_id=" . $resourcesid;
				$resources[] = $res;
				
		
				$res["type"] = "WWW:DOWNLOAD-1.0-http--download";
				$res["encodingFormat"] = "GeoJSON";
				$res["contentUrl"] = $protocol . $host . "/api/records/2.0/downloadfile/format=geojson&resource_id=" . $resourcesid;
				$resources[] = $res;
				
				$res = array();
				$res["type"] = "WWW:KML";
				$res["encodingFormat"] = "KML";
				$res["contentUrl"] = $protocol . $host . "/api/records/2.0/downloadfile/format=kml&resource_id=" . $resourcesid;
				$resources[] = $res;
				
				$res = array();
				$res["type"] = "WWW:DOWNLOAD-1.0-http--download";
				$res["encodingFormat"] = "Shapefile";
				$res["contentUrl"] = $protocol . $host . "/api/records/2.0/downloadfile/format=shp&resource_id=" . $resourcesid;
				$resources[] = $res;
		}

		$xmlfile =false;
	    foreach($dataset["metas"]["resources"] as $key=>$value){
	    	
	    	if($value["format"] == "csw" || strpos($value["name"], "Vue XML des métadonnées")== true) {

	    		$xmlfile = true;
	    	$xml = file_get_contents($value['url']); 

	    	if (!file_exists($_SERVER['DOCUMENT_ROOT']."/". $id)) {
		    	mkdir($_SERVER['DOCUMENT_ROOT']."/". $id, 0777, true);
			}
			file_put_contents($_SERVER['DOCUMENT_ROOT']."/". $id."/metadata_xml_view.xml", $xml);
			
			break;
	    }

	    }

		if (file_exists($_SERVER['DOCUMENT_ROOT']."/". $contentdataset["result"]["id"]."/metadata_xml_view.xml")) {
		 		$xml = simplexml_load_file($contentdataset["result"]["id"]."/metadata_xml_view.xml");

		 		foreach ($xml as $key => $value) {
		 			foreach ($value->gmdidentificationInfo->gmdMD_DataIdentification->gmddescriptiveKeywords as $key2 => $value2) {
		 				/*echo "<pre>";
		 				var_dump($key2);*/
		 				
		 				if($value2->gmdMD_Keywords->gmdthesaurusName) {
		 					//var_dump($value2->gmdMD_Keywords->gmdtype->gmdMD_KeywordTypeCode["codeListValue"]->__toString());
		 					$thesaurusValue = $value2->gmdMD_Keywords->gmdthesaurusName->gmdCI_Citation->gmdtitle->gcoCharacterString->__toString();
		 					$thesaruskeyword = $value2->gmdMD_Keywords->gmdkeyword->gcoCharacterString->__toString();
		 					$thesarustype = $value2->gmdMD_Keywords->gmdtype->gmdMD_KeywordTypeCode["codeListValue"]->__toString();
		 					break;
		 				}
		 				else {

		 					array_push($otherkeyword, $value2->gmdMD_Keywords->gmdkeyword->gcoCharacterString->__toString());
		
		 				}
					}

					//echo "</pre>";
					$codepostal = $value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmdpostalCode->gcoCharacterString->__toString();
					$cityValue = $value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmdcity->gcoCharacterString->__toString();
					$mailadress = $value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmdelectronicMailAddress->gcoCharacterString->__toString();
					$adresseValue = $value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmddeliveryPoint->gcoCharacterString->__toString();


					foreach ($value->gmdidentificationInfo->gmdMD_DataIdentification->gmdresourceConstraints as $key2 => $value2) {
				
						foreach ($value2->gmdMD_LegalConstraints->gmduseLimitation as $limitation) {
							array_push($useLimitationsContent, $limitation->gcoCharacterString->__toString());
						}	

						if($value2->gmdMD_LegalConstraints->gmdotherConstraints != null ){
							$otherrestriction = $value2->gmdMD_LegalConstraints->gmdotherConstraints->gcoCharacterString->__toString();
						}

						if($value2->gmdMD_LegalConstraints->gmdaccessConstraints != null )
						 	$accessconstraint = $value2->gmdMD_LegalConstraints->gmdaccessConstraints->gmdMD_RestrictionCode->__toString();


						if($value2->gmdMD_LegalConstraints->gmduseConstraints != null )
						 	$useconstraintvalue = $value2->gmdMD_LegalConstraints->gmduseConstraints->gmdMD_RestrictionCode->__toString();
					}

					$topiccategory = $value->gmdidentificationInfo->gmdMD_DataIdentification->gmdtopicCategory->gmdMD_TopicCategoryCode->__toString();
					$extentdescription = $value->gmdidentificationInfo->gmdMD_DataIdentification->gmdextent->gmdEX_Extent->gmddescription->gcoCharacterString->__toString();
					$westBoundLongitudeValue=$value->gmdidentificationInfo->gmdMD_DataIdentification->gmdextent->gmdEX_Extent->gmdgeographicElement->gmdEX_GeographicBoundingBox->gmdwestBoundLongitude->gcoDecimal->__toString();
					$eastBoundLongitudeValue=$value->gmdidentificationInfo->gmdMD_DataIdentification->gmdextent->gmdEX_Extent->gmdgeographicElement->gmdEX_GeographicBoundingBox->gmdeastBoundLongitude->gcoDecimal->__toString();
					$southBoundLatitudeValue=$value->gmdidentificationInfo->gmdMD_DataIdentification->gmdextent->gmdEX_Extent->gmdgeographicElement->gmdEX_GeographicBoundingBox->gmdsouthBoundLatitude->gcoDecimal->__toString();
					$northBoundLatitudeValue=$value->gmdidentificationInfo->gmdMD_DataIdentification->gmdextent->gmdEX_Extent->gmdgeographicElement->gmdEX_GeographicBoundingBox->gmdnorthBoundLatitude->gcoDecimal->__toString();

					/*echo "<pre>";
					var_dump($value->gmddataQualityInfo->gmdDQ_DataQuality->gmdlineage->gmdLI_Lineage->gmdstatement->gcoCharacterString->__toString());
					echo "</pre>";*/
					$lineagetitle = $value->gmddataQualityInfo->gmdDQ_DataQuality->gmdlineage->gmdLI_Lineage->gmdstatement->gcoCharacterString->__toString();

					$reporttitle = $value->gmddataQualityInfo->gmdDQ_DataQuality->gmdreport->gmdDQ_DomainConsistency->gmdresult->gmdDQ_ConformanceResult->gmdspecification->gmdCI_Citation->gmdtitle->gcoCharacterString->__toString();
				$reportdate = $value->gmddataQualityInfo->gmdDQ_DataQuality->gmdreport->gmdDQ_DomainConsistency->gmdresult->gmdDQ_ConformanceResult->gmdspecification->gmdCI_Citation->gmddate->gmdCI_Date->gmddate->gcoDate->__toString();
				$explanationtitle = $value->gmddataQualityInfo->gmdDQ_DataQuality->gmdreport->gmdDQ_DomainConsistency->gmdresult->gmdDQ_ConformanceResult->gmdexplanation->gcoCharacterString->__toString();
					
		 		}
		 		
				
		 }

	//die;
    		// create Ms_Metadata element with attributes
			$metadata = $doc->createElement("gmd:MD_Metadata");
			$metadata->setAttribute("xmlns:gmd","http://www.isotc211.org/2005/gmd");
			$metadata->setAttribute("xmlns:gmx","http://www.isotc211.org/2005/gmx");
			$metadata->setAttribute("xmlns:gco","http://www.isotc211.org/2005/gco");
			$metadata->setAttribute("xmlns:xsi","http://www.w3.org/2001/XMLSchema-instance");
			$metadata->setAttribute("xmlns:gml","http://www.opengis.net/gml");
			$metadata->setAttribute("xmlns:xlink","http://www.w3.org/1999/xlink");
			$metadata->setAttribute("xmlns:geonet","http://www.fao.org/geonetwork");
			$metadata->setAttribute("xsi:schemaLocation","http://www.isotc211.org/2005/gmd http://schemas.opengis.net/iso/19139/20060504/gmd/gmd.xsd");
			$doc->appendChild($metadata);

				// create fileIdentifier element with attributes
				$gmdfileIdentifier = $doc->createElement("gmd:fileIdentifier");
				$gcoCharacterStringFileIdentifier = $doc->createElement("gco:CharacterString", "FR-" .$contentdataset["result"]["id"]);
				$gmdfileIdentifier->appendChild($gcoCharacterStringFileIdentifier);
				$metadata->appendChild($gmdfileIdentifier);	

				// create language element 
				$gmdlanguage = $doc->createElement("gmd:language");
				if($dataset["metas"]["language"] == "fr") {
					$langue = "fre";
				}
				if($dataset["metas"]["language"] == "en") {
					$langue = "eng";
				}
				if($dataset["metas"]["language"] == "ge") {
					$langue = "ger";
				}
				else {
					$langue = "fre";
				}
						
				$gmdlanguagecode = $doc->createElement("gmd:LanguageCode", $langue);
				$gmdlanguagecode->setAttribute("codeListValue",$langue);
				$gmdlanguagecode->setAttribute("codeList","http://www.loc.gov/standards/iso639-2/");
				$gmdlanguage->appendChild($gmdlanguagecode);
				$metadata->appendChild($gmdlanguage);

				// create characterSet element with attributes
				$gmdcharacterSet = $doc->createElement("gmd:characterSet");
				$MD_CharacterSetCode = $doc->createElement("gmd:MD_CharacterSetCode", "utf8");
				$MD_CharacterSetCode->setAttribute("codeList","http://www.isotc211.org/2005/resources/codeList.xml#MD_CharacterSetCode");
				$MD_CharacterSetCode->setAttribute("codeListValue","utf8");
				$gmdcharacterSet->appendChild($MD_CharacterSetCode);
				$metadata->appendChild($gmdcharacterSet);	

				// create hierarchyLevel element
				$hierarchyLevel = $doc->createElement("gmd:hierarchyLevel");
				$MD_ScopeCode = $doc->createElement("gmd:MD_ScopeCode", "dataset");
				$MD_ScopeCode->setAttribute("codeList","http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/Codelist/ML_gmxCodelists.xml#MD_ScopeCode");
				$MD_ScopeCode->setAttribute("codeListValue","dataset");
				$hierarchyLevel->appendChild($MD_ScopeCode);
				$metadata->appendChild($hierarchyLevel);

			/*$hierarchyLevelName = $doc->createElement("gmd:hierarchyLevelName");
			$gcoCharacterString = $doc->createElement("gco:gcoCharacterString", "jeu de données");
			$hierarchyLevelName->appendChild($gcoCharacterString);
			$metadata->appendChild($hierarchyLevelName);*/	

				// create contact element
				$contact = $doc->createElement("gmd:contact");

					// create CI_ResponsibleParty element
					$CI_ResponsibleParty = $doc->createElement("gmd:CI_ResponsibleParty");

						// create individualName element
						$individualName = $doc->createElement("gmd:individualName");
						$gcoCharacterStringcontact = $doc->createElement("gco:CharacterString", "Cyprien");
						$individualName->appendChild($gcoCharacterStringcontact);

						// create organisationName element
						$organisationName = $doc->createElement("gmd:organisationName");
						$gcoCharacterStringorganisation = $doc->createElement("gco:CharacterString", $contentdataset["result"]['organization']["title"]);
						$organisationName->appendChild($gcoCharacterStringorganisation);

						// create positionName element
						$positionName = $doc->createElement("gmd:positionName");
						$gcoCharacterStringorganisation = $doc->createElement("gco:CharacterString", "consultant");
						$positionName->appendChild($gcoCharacterStringorganisation);

						// create contactInfo element
						$contactInfo = $doc->createElement("gmd:contactInfo");

							// create CI_Contact element
							$CI_Contact = $doc->createElement("gmd:CI_Contact");

								// create phone element
								$phone = $doc->createElement("gmd:phone");
									// create CI_Telephone element
									$CI_Telephone = $doc->createElement("gmd:CI_Telephone");
											// create voice element
										$voice = $doc->createElement("gmd:voice");
											$gcoCharacterStringorganisation = $doc->createElement("gco:CharacterString", "");
										$voice->appendChild($gcoCharacterStringorganisation);

									$CI_Telephone->appendChild($voice);
								$phone->appendChild($CI_Telephone);
							$CI_Contact->appendChild($phone);

								// create address element
								$address = $doc->createElement("gmd:address");
									// create CI_Address element
									$CI_Address = $doc->createElement("gmd:CI_Address");

										// create deliveryPoint element
										$deliveryPoint = $doc->createElement("gmd:deliveryPoint");
											$gcoCharacterString = $doc->createElement("gco:CharacterString", $adresseValue);
										$deliveryPoint->appendChild($gcoCharacterString);

										// create city element
										$city = $doc->createElement("gmd:city");
											$gcoCharacterString = $doc->createElement("gco:CharacterString", $cityValue);
										$city->appendChild($gcoCharacterString);

										// create postalCode element
										$postalCode = $doc->createElement("gmd:postalCode");
											$gcoCharacterString = $doc->createElement("gco:CharacterString", $codepostal);
										$postalCode->appendChild($gcoCharacterString);

										// create electronicMailAddress element
										$electronicMailAddress = $doc->createElement("gmd:electronicMailAddress");
											$gcoCharacterString = $doc->createElement("gco:CharacterString", $mailadress);
										$electronicMailAddress->appendChild($gcoCharacterString);

									$CI_Address->appendChild($deliveryPoint);
									$CI_Address->appendChild($city);
									$CI_Address->appendChild($postalCode);
									$CI_Address->appendChild($electronicMailAddress);
								$address->appendChild($CI_Address);
							$CI_Contact->appendChild($address);

								// create contactInstructions element
								$contactInstructions = $doc->createElement("gmd:contactInstructions");
									// create FileName element
									$FileName = $doc->createElement("gmx:FileName");
									$FileName->setAttribute("src",$urllogo);
								$contactInstructions->appendChild($FileName);	
							$CI_Contact->appendChild($contactInstructions);



								/*$OnlineResource = $doc->createElement("gmd:OnlineResource");
									$CI_OnlineResource = $doc->createElement("gmd:CI_OnlineResource");
										$CI_linkage = $doc->createElement("gmd:linkage");
											$URL = $doc->createElement("gmd:URL", "ND");

										$CI_linkage->appendChild($URL);
									$CI_OnlineResource->appendChild($CI_linkage);
								$OnlineResource->appendChild($CI_OnlineResource);
							$CI_Contact->appendChild($OnlineResource);*/

						$contactInfo->appendChild($CI_Contact);

						// create role element
						$role = $doc->createElement("gmd:role");
							$CI_RoleCode = $doc->createElement("gmd:CI_RoleCode","pointOfContact");
							$CI_RoleCode->setAttribute("codeList","http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/Codelist/ML_gmxCodelists.xml#CI_RoleCode");
							$CI_RoleCode->setAttribute("codeListValue","pointOfContact");

						$role->appendChild($CI_RoleCode);

					
					$CI_ResponsibleParty->appendChild($individualName);
					$CI_ResponsibleParty->appendChild($organisationName);
					$CI_ResponsibleParty->appendChild($contactInfo);
					$CI_ResponsibleParty->appendChild($role);

				$contact->appendChild($CI_ResponsibleParty);
			$metadata->appendChild($contact);

				// create dateStamp element
				$dateStamp = $doc->createElement("gmd:dateStamp");
					$Date = $doc->createElement("gco:Date","ND");
				$dateStamp->appendChild($Date);
			$metadata->appendChild($dateStamp);

				// create metadataStandardName element
				$metadataStandardName = $doc->createElement("gmd:metadataStandardName");
					$CharacterString = $doc->createElement("gco:CharacterString","ISO 19115/19139");
				$metadataStandardName->appendChild($CharacterString);
			$metadata->appendChild($metadataStandardName);

				// create metadataStandardVersion element
				$metadataStandardVersion = $doc->createElement("gmd:metadataStandardVersion");
					$CharacterString = $doc->createElement("gco:CharacterString","Cor 1:2006");
				$metadataStandardVersion->appendChild($CharacterString);
			$metadata->appendChild($metadataStandardVersion);

				// create identificationInfo element
				$identificationInfo = $doc->createElement("gmd:identificationInfo");
					// create MD_DataIdentification element
					$MD_DataIdentification = $doc->createElement("gmd:MD_DataIdentification");
						// create citation element
						$citation = $doc->createElement("gmd:citation");
							// create CI_Citation element
							$CI_Citation = $doc->createElement("gmd:CI_Citation");
								// create title element
								$title = $doc->createElement("gmd:title");
									$CharacterString = $doc->createElement("gco:CharacterString",$contentdataset["result"]["title"]);
								$title->appendChild($CharacterString);
							// add title
							$CI_Citation->appendChild($title);

								// create date element
								$date = $doc->createElement("gmd:date");
									$CI_Date = $doc->createElement("gmd:CI_Date");
										$date2 = $doc->createElement("gmd:date");
											$datestamps = explode("T", $dataset["metas"]["metadata_created"]);
											$date3 = $doc->createElement("gco:Date",$datestamps[0]);
										$date2->appendChild($date3);

										$dateType = $doc->createElement("gmd:dateType");
											$CI_DateTypeCode = $doc->createElement("gmd:CI_DateTypeCode","creation");
											$CI_DateTypeCode->setAttribute("codeList","http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/Codelist/ML_gmxCodelists.xml#CI_DateTypeCode");
											$CI_DateTypeCode->setAttribute("codeListValue","creation");
										$dateType->appendChild($CI_DateTypeCode);
			
									$CI_Date->appendChild($date2);
									$CI_Date->appendChild($dateType);
								$date->appendChild($CI_Date);

							// add date
							$CI_Citation->appendChild($date);


								$date = $doc->createElement("gmd:date");
									$CI_Date = $doc->createElement("gmd:CI_Date");
										$date2 = $doc->createElement("gmd:date");
											$datestamps = explode("T", $dataset["metas"]["metadata_modified"]);
											$date3 = $doc->createElement("gco:Date",$datestamps[0]);
										$date2->appendChild($date3);

										$dateType = $doc->createElement("gmd:dateType");
											$CI_DateTypeCode = $doc->createElement("gmd:CI_DateTypeCode","edition");
											$CI_DateTypeCode->setAttribute("codeList","http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/Codelist/ML_gmxCodelists.xml#CI_DateTypeCode");
											$CI_DateTypeCode->setAttribute("codeListValue","edition");
										$dateType->appendChild($CI_DateTypeCode);
			
									$CI_Date->appendChild($date2);
									$CI_Date->appendChild($dateType);
								$date->appendChild($CI_Date);

							// add date
							$CI_Citation->appendChild($date);


								$date = $doc->createElement("gmd:date");
									$CI_Date = $doc->createElement("gmd:CI_Date");
										$date2 = $doc->createElement("gmd:date");
											$datestamps = explode("T", $dataset["metas"]["metadata_processed"]);
											$date3 = $doc->createElement("gco:Date",$datestamps[0]);
										$date2->appendChild($date3);

										$dateType = $doc->createElement("gmd:dateType");
											$CI_DateTypeCode = $doc->createElement("gmd:CI_DateTypeCode","publication");
											$CI_DateTypeCode->setAttribute("codeList","http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/Codelist/ML_gmxCodelists.xml#CI_DateTypeCode");
											$CI_DateTypeCode->setAttribute("codeListValue","publication");
										$dateType->appendChild($CI_DateTypeCode);
			
									$CI_Date->appendChild($date2);
									$CI_Date->appendChild($dateType);
								$date->appendChild($CI_Date);

							// add date
							$CI_Citation->appendChild($date);


								$identifier = $doc->createElement("gmd:identifier");
									$MD_Identifier = $doc->createElement("gmd:MD_Identifier");
										$code = $doc->createElement("gmd:code");
											$CharacterString = $doc->createElement("gco:CharacterString",$contentdataset["result"]["id"]);

										$code->appendChild($CharacterString);
									$MD_Identifier->appendChild($code);
								$identifier->appendChild($MD_Identifier);

							//add identifier
							$CI_Citation->appendChild($identifier);

						$citation->appendChild($CI_Citation);
					$MD_DataIdentification->appendChild($citation);


						$abstract = $doc->createElement("gmd:abstract");
						
						$description = str_replace('<br>', '', $description);
						$pieces = explode(".", htmlentities($description));

							$CharacterString = $doc->createElement("gco:CharacterString",htmlentities($pieces[0])."".htmlentities($pieces[1])."".htmlentities($pieces[3])."".htmlentities($pieces[4])."".htmlentities($pieces[5])."".htmlentities($pieces[6])."".htmlentities($pieces[7])."".htmlentities($pieces[8]));
						$abstract->appendChild($CharacterString);
					$MD_DataIdentification->appendChild($abstract);

					//var_dump(html_entity_decode(htmlentities($pieces[0])));die;

						$pointOfContact = $doc->createElement("gmd:pointOfContact");
							$CI_ResponsibleParty = $doc->createElement("gmd:CI_ResponsibleParty");

								$individualName = $doc->createElement("gmd:individualName");
									$CharacterString = $doc->createElement("gco:CharacterString","Cyprien");
								$individualName->appendChild($CharacterString);
							$CI_ResponsibleParty->appendChild($individualName);

								$organisationName = $doc->createElement("gmd:organisationName");
									$CharacterString = $doc->createElement("gco:CharacterString",$contentdataset["result"]['organization']["title"]);
								$organisationName->appendChild($CharacterString);
							$CI_ResponsibleParty->appendChild($organisationName);

								$positionName = $doc->createElement("gmd:positionName");
									$CharacterString = $doc->createElement("gco:CharacterString","consultant");
								$positionName->appendChild($CharacterString);
							$CI_ResponsibleParty->appendChild($positionName);


								$contactInfo = $doc->createElement("gmd:contactInfo");
									$CI_Contact = $doc->createElement("gmd:CI_Contact");

										$phone = $doc->createElement("gmd:phone");
											$CI_Telephone = $doc->createElement("gmd:CI_Telephone");
												$voice = $doc->createElement("gmd:voice");
													$CharacterString = $doc->createElement("gco:CharacterString"," ");
												$voice->appendChild($CharacterString);
											$CI_Telephone->appendChild($voice);
										$phone->appendChild($CI_Telephone);
									$CI_Contact->appendChild($phone);

										$address = $doc->createElement("gmd:address");
											$CI_Address = $doc->createElement("gmd:CI_Address");
												$deliveryPoint = $doc->createElement("gmd:deliveryPoint");
													$CharacterString = $doc->createElement("gco:CharacterString",$adresseValue);
												$deliveryPoint->appendChild($CharacterString);
											$CI_Address->appendChild($deliveryPoint);
												$city = $doc->createElement("gmd:city");
													$CharacterString = $doc->createElement("gco:CharacterString",$cityValue);
												$city->appendChild($CharacterString);
											$CI_Address->appendChild($city);

												$postalCode = $doc->createElement("gmd:postalCode");
													$CharacterString = $doc->createElement("gco:CharacterString",$codepostal);
												$postalCode->appendChild($CharacterString);
											$CI_Address->appendChild($postalCode);

												$electronicMailAddress = $doc->createElement("gmd:electronicMailAddress");
													$CharacterString = $doc->createElement("gco:CharacterString",$mailadress);
												$electronicMailAddress->appendChild($CharacterString);
											$CI_Address->appendChild($electronicMailAddress);
										$address->appendChild($CI_Address);
									$CI_Contact->appendChild($address);

										$contactInstructions = $doc->createElement("gmd:contactInstructions");
											$FileName = $doc->createElement("gmx:FileName");
											$FileName->setAttribute("src",$urllogo);
										$contactInstructions->appendChild($FileName);	
									$CI_Contact->appendChild($contactInstructions);

								$contactInfo->appendChild($CI_Contact);
							$CI_ResponsibleParty->appendChild($contactInfo);

								$role = $doc->createElement("gmd:role");
									$CI_RoleCode = $doc->createElement("gmd:CI_RoleCode","pointOfContact");
									$CI_RoleCode->setAttribute("codeList","http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/Codelist/ML_gmxCodelists.xml#CI_RoleCode");
									$CI_RoleCode->setAttribute("codeListValue","pointOfContact");
								$role->appendChild($CI_RoleCode);

							$CI_ResponsibleParty->appendChild($role);

						$pointOfContact->appendChild($CI_ResponsibleParty);
					//add point of contact
					$MD_DataIdentification->appendChild($pointOfContact);

						$resourceMaintenance = $doc->createElement("gmd:resourceMaintenance");
							$MD_MaintenanceInformation = $doc->createElement("gmd:MD_MaintenanceInformation");
								$maintenanceAndUpdateFrequency = $doc->createElement("gmd:maintenanceAndUpdateFrequency");
									
									$MD_MaintenanceFrequencyCode = $doc->createElement("gmd:MD_MaintenanceFrequencyCode","ND");
									$MD_MaintenanceFrequencyCode->setAttribute("codeList","http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/Codelist/ML_gmxCodelists.xml#MD_MaintenanceFrequencyCode");
									$MD_MaintenanceFrequencyCode->setAttribute("codeListValue","ND");

								$maintenanceAndUpdateFrequency->appendChild($MD_MaintenanceFrequencyCode);

							$MD_MaintenanceInformation->appendChild($maintenanceAndUpdateFrequency);
						$resourceMaintenance->appendChild($MD_MaintenanceInformation);
					$MD_DataIdentification->appendChild($resourceMaintenance);


					$graphicOverview = $doc->createElement("gmd:graphicOverview");
							$MD_BrowseGraphic = $doc->createElement("gmd:MD_BrowseGraphic");
								$fileName = $doc->createElement("gmd:fileName");
									$CharacterString = $doc->createElement("gco:CharacterString",$urllogo);
								$fileName->appendChild($CharacterString);
							$MD_BrowseGraphic->appendChild($fileName);


								$description = str_replace('<br>', '', $description);
								$pieces = explode(".", htmlentities($description));
								$fileDescription = $doc->createElement("gmd:fileDescription");
									$CharacterString = $doc->createElement("gco:CharacterString",htmlentities($pieces[0]));
								$fileDescription->appendChild($CharacterString);	
							$MD_BrowseGraphic->appendChild($fileDescription);
							
								/*$fileType = $doc->createElement("gmd:fileDescription");
									$CharacterString = $doc->createElement("gco:CharacterString","ND");
								$fileDescription->appendChild($CharacterString);	
							$MD_BrowseGraphic->appendChild($fileType);*/

						$graphicOverview->appendChild($MD_BrowseGraphic);
					$MD_DataIdentification->appendChild($graphicOverview);


						$resourceConstraints = $doc->createElement("gmd:resourceConstraints");
							$MD_SecurityConstraints = $doc->createElement("gmd:MD_SecurityConstraints");
								$classification = $doc->createElement("gmd:classification");
									$MD_ClassificationCode = $doc->createElement("gmd:MD_ClassificationCode","unclassified");
									$MD_ClassificationCode->setAttribute("codeList","http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/Codelist/ML_gmxCodelists.xml#MD_ClassificationCode");
									$MD_ClassificationCode->setAttribute("codeListValue","ND");

								$classification->appendChild($MD_ClassificationCode);

							$MD_SecurityConstraints->appendChild($classification);
						$resourceConstraints->appendChild($MD_SecurityConstraints);
					$MD_DataIdentification->appendChild($resourceConstraints);


						$resourceConstraints = $doc->createElement("gmd:resourceConstraints");
							$MD_LegalConstraints = $doc->createElement("gmd:MD_LegalConstraints");
								//$useLimitationsContent
							foreach ($useLimitationsContent as $key => $value) {
								
								$useLimitation = $doc->createElement("gmd:useLimitation");
									$CharacterString = $doc->createElement("gco:CharacterString",$value);
								$useLimitation->appendChild($CharacterString);
							$MD_LegalConstraints->appendChild($useLimitation);

							}


								$useConstraints = $doc->createElement("gmd:useConstraints");
									$MD_RestrictionCode = $doc->createElement("gmd:MD_RestrictionCode",$useconstraintvalue);
									$MD_RestrictionCode->setAttribute("codeList","http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/Codelist/ML_gmxCodelists.xml#MD_RestrictionCode");
									$MD_RestrictionCode->setAttribute("codeListValue",$useconstraintvalue);
								$useConstraints->appendChild($MD_RestrictionCode);
							$MD_LegalConstraints->appendChild($useConstraints);

								$accessConstraints = $doc->createElement("gmd:accessConstraints");
									$MD_RestrictionCode = $doc->createElement("gmd:MD_RestrictionCode",$accessconstraint);
									$MD_RestrictionCode->setAttribute("codeList","http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/Codelist/ML_gmxCodelists.xml#MD_RestrictionCode");
									$MD_RestrictionCode->setAttribute("codeListValue",$accessconstraint);
								$accessConstraints->appendChild($MD_RestrictionCode);
							$MD_LegalConstraints->appendChild($accessConstraints);

								$otherConstraints = $doc->createElement("gmd:otherConstraints");

									$MD_RestrictionCode = $doc->createElement("gco:CharacterString",$otherrestriction);
									
								$otherConstraints->appendChild($MD_RestrictionCode);
							$MD_LegalConstraints->appendChild($otherConstraints);

						$resourceConstraints->appendChild($MD_LegalConstraints);
					$MD_DataIdentification->appendChild($resourceConstraints);

						$spatialRepresentationType = $doc->createElement("gmd:spatialRepresentationType");
							$MD_SpatialRepresentationTypeCode = $doc->createElement("gmd:MD_SpatialRepresentationTypeCode");
							$MD_SpatialRepresentationTypeCode->setAttribute("codeList","http://www.isotc211.org/2005/resources/codeList.xml#MD_SpatialRepresentationTypeCode");

						$spatialRepresentationType->appendChild($MD_SpatialRepresentationTypeCode);
					$MD_DataIdentification->appendChild($spatialRepresentationType);

					
						$language = $doc->createElement("gmd:language");
						if($dataset["metas"]["language"] == "fr") {
							$langue = "fre";
						}
						if($dataset["metas"]["language"] == "en") {
							$langue = "eng";
						}
						if($dataset["metas"]["language"] == "ge") {
							$langue = "ger";
						}
						else {
							$langue = "fre";
						}
						
							$LanguageCode = $doc->createElement("gmd:LanguageCode",$langue);
	
							$LanguageCode->setAttribute("codeList","http://www.loc.gov/standards/iso639-2/");
							$LanguageCode->setAttribute("codeListValue",$langue);

						$language->appendChild($LanguageCode);
					$MD_DataIdentification->appendChild($language);

					$MdtopicCategory = $doc->createElement("gmd:topicCategory");
							$MD_TopicCategoryCode = $doc->createElement("gmd:MD_TopicCategoryCode",$topiccategory);

						$MdtopicCategory->appendChild($MD_TopicCategoryCode);
					$MD_DataIdentification->appendChild($MdtopicCategory);

					//var_dump($MdtopicCategory);

						$extent = $doc->createElement("gmd:extent");
							$EX_Extent = $doc->createElement("gmd:EX_Extent");

								$description = $doc->createElement("gmd:description");
									$CharacterString = $doc->createElement("gco:CharacterString",$extentdescription);
								$description->appendChild($CharacterString);
							$EX_Extent->appendChild($description);
						$extent->appendChild($EX_Extent);

							$geographicElement = $doc->createElement("gmd:geographicElement");
								$EX_GeographicBoundingBox = $doc->createElement("gmd:EX_GeographicBoundingBox");

									$westBoundLongitude = $doc->createElement("gmd:westBoundLongitude");
										$Decimal = $doc->createElement("gco:Decimal",$westBoundLongitudeValue);
									$westBoundLongitude->appendChild($Decimal);
								$EX_GeographicBoundingBox->appendChild($westBoundLongitude);

									$eastBoundLongitude = $doc->createElement("gmd:eastBoundLongitude");
										$Decimal = $doc->createElement("gco:Decimal",$eastBoundLongitudeValue);
									$eastBoundLongitude->appendChild($Decimal);
								$EX_GeographicBoundingBox->appendChild($eastBoundLongitude);

									$southBoundLatitude = $doc->createElement("gmd:southBoundLatitude");
										$Decimal = $doc->createElement("gco:Decimal",$southBoundLatitudeValue);
									$southBoundLatitude->appendChild($Decimal);
								$EX_GeographicBoundingBox->appendChild($southBoundLatitude);

									$northBoundLatitude = $doc->createElement("gmd:northBoundLatitude");
										$Decimal = $doc->createElement("gco:Decimal",$northBoundLatitudeValue);
									$northBoundLatitude->appendChild($Decimal);
								$EX_GeographicBoundingBox->appendChild($northBoundLatitude);


							$geographicElement->appendChild($EX_GeographicBoundingBox);
						$extent->appendChild($geographicElement);

					$MD_DataIdentification->appendChild($extent);

					foreach ($keywords as $key => $value) {
						$descriptiveKeywords = $doc->createElement("gmd:descriptiveKeywords");
							$MD_Keywords = $doc->createElement("gmd:MD_Keywords");

								$keyword = $doc->createElement("gmd:keyword");
										$CharacterString = $doc->createElement("gco:CharacterString",$value);
								$keyword->appendChild($CharacterString);
							$MD_Keywords->appendChild($keyword);

							$type = $doc->createElement("gmd:type");
									$MD_KeywordTypeCode = $doc->createElement("gmd:MD_KeywordTypeCode");
									$MD_KeywordTypeCode->setAttribute("codeListValue","theme");
									$MD_KeywordTypeCode->setAttribute("codeList","standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/gmxCodelists.xml#MD_KeywordTypeCode");
								$type->appendChild($MD_KeywordTypeCode);
							$MD_Keywords->appendChild($type);
						$descriptiveKeywords->appendChild($MD_Keywords);
					$MD_DataIdentification->appendChild($descriptiveKeywords);

					}

						$descriptiveKeywords = $doc->createElement("gmd:descriptiveKeywords");
							$MD_Keywords = $doc->createElement("gmd:MD_Keywords");
							
		
								$keyword = $doc->createElement("gmd:keyword");
									$CharacterString = $doc->createElement("gco:CharacterString",$thesaruskeyword);
								$keyword->appendChild($CharacterString);
							$MD_Keywords->appendChild($keyword);
							
								$type = $doc->createElement("gmd:type");
									$MD_KeywordTypeCode = $doc->createElement("gmd:MD_KeywordTypeCode");
									$MD_KeywordTypeCode->setAttribute("codeListValue","theme");
									$MD_KeywordTypeCode->setAttribute("codeList","standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/gmxCodelists.xml#MD_KeywordTypeCode");
								$type->appendChild($MD_KeywordTypeCode);
							$MD_Keywords->appendChild($type);

								$thesaurusName = $doc->createElement("gmd:thesaurusName");
									$CI_Citation = $doc->createElement("gmd:CI_Citation");
										$title = $doc->createElement("gmd:title");

												$CharacterString = $doc->createElement("gco:CharacterString",$thesaurusValue);

										$title->appendChild($CharacterString);

									$CI_Citation->appendChild($title);
								

										$date = $doc->createElement("gmd:date");
											$CI_Date = $doc->createElement("gmd:CI_Date");
												$gmddate = $doc->createElement("gmd:date");
													$datestamps = explode("T", $dataset["metas"]["metadata_processed"]);
													$gcoDate = $doc->createElement("gco:Date",$datestamps[0]);
												$gmddate->appendChild($gcoDate);	
											$CI_Date->appendChild($gmddate);
										$date->appendChild($CI_Date);	
										
									$CI_Citation->appendChild($date);
									
								$thesaurusName->appendChild($CI_Citation);
							$MD_Keywords->appendChild($thesaurusName);

						$descriptiveKeywords->appendChild($MD_Keywords);
						
					$MD_DataIdentification->appendChild($descriptiveKeywords);


					

				$identificationInfo->appendChild($MD_DataIdentification);
			$metadata->appendChild($identificationInfo);

				$distributionInfo = $doc->createElement("gmd:distributionInfo");
					$MD_Distribution = $doc->createElement("gmd:MD_Distribution");

						$distributionFormat = $doc->createElement("gmd:distributionFormat");
							$MD_Format = $doc->createElement("gmd:MD_Format");
								$name = $doc->createElement("gmd:name");
									$CharacterString = $doc->createElement("gco:CharacterString","ESRI Shapefile (SHP) ");
								$name->appendChild($CharacterString);
							$MD_Format->appendChild($name);
						$distributionFormat->appendChild($MD_Format);
					$MD_Distribution->appendChild($distributionFormat);

						$transferOptions = $doc->createElement("gmd:transferOptions");
							$MD_DigitalTransferOptions = $doc->createElement("gmd:MD_DigitalTransferOptions");

							foreach ($resources as $key => $value) {
								$onLine = $doc->createElement("gmd:onLine");
									$CI_OnlineResource = $doc->createElement("gmd:CI_OnlineResource");

										$linkage = $doc->createElement("gmd:linkage");
										$urlvalue = $value["contentUrl"];
										
											$URL = $doc->createElement("gmd:URL",urlencode($urlvalue));
										$linkage->appendChild($URL);
									$CI_OnlineResource->appendChild($linkage);
								
										$name = $doc->createElement("gmd:name");
											$CharacterString = $doc->createElement("gco:CharacterString",$value["encodingFormat"]);
										$name->appendChild($CharacterString);
									$CI_OnlineResource->appendChild($name);

										$protocol = $doc->createElement("gmd:protocol");
											$CharacterString = $doc->createElement("gco:CharacterString",$value["type"]);
										$protocol->appendChild($CharacterString);
									$CI_OnlineResource->appendChild($protocol);

								$onLine->appendChild($CI_OnlineResource);
							$MD_DigitalTransferOptions->appendChild($onLine);
							}
							/*foreach ($contentdataset["result"]['resources'] as $key => $value) {

								$onLine = $doc->createElement("gmd:onLine");
									$CI_OnlineResource = $doc->createElement("gmd:CI_OnlineResource");

										$linkage = $doc->createElement("gmd:linkage");
											$URL = $doc->createElement("gmd:URL",$value["url"]);
										$linkage->appendChild($URL);
									$CI_OnlineResource->appendChild($linkage);

										$name = $doc->createElement("gmd:name");
											$CharacterString = $doc->createElement("gco:CharacterString",$value["name"]);
										$name->appendChild($CharacterString);
									$CI_OnlineResource->appendChild($name);

										$protocol = $doc->createElement("gmd:protocol");
											$CharacterString = $doc->createElement("gco:CharacterString",$value["mimetype"]);
										$protocol->appendChild($CharacterString);
									$CI_OnlineResource->appendChild($protocol);

								$onLine->appendChild($CI_OnlineResource);
							$MD_DigitalTransferOptions->appendChild($onLine);

							}*/
								
							
						$transferOptions->appendChild($MD_DigitalTransferOptions);
					$MD_Distribution->appendChild($transferOptions);
				$distributionInfo->appendChild($MD_Distribution);
			
			$metadata->appendChild($distributionInfo);

				$dataQualityInfo = $doc->createElement("gmd:dataQualityInfo");
					$DQ_DataQuality = $doc->createElement("gmd:DQ_DataQuality");

						$scope = $doc->createElement("gmd:scope");
							$DQ_Scope = $doc->createElement("gmd:DQ_Scope");
								$level = $doc->createElement("gmd:level");
									$MD_ScopeCode = $doc->createElement("gmd:MD_ScopeCode");
									$MD_ScopeCode->setAttribute("codeList","http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/gmxCodelists.xml#MD_ScopeCode");
								$level->appendChild($MD_ScopeCode);
							$DQ_Scope->appendChild($level);
						$scope->appendChild($DQ_Scope);
					$DQ_DataQuality->appendChild($scope);

			$report = $doc->createElement("gmd:report");
			$DQ_DomainConsistency = $doc->createElement("gmd:DQ_DomainConsistency");
			$result = $doc->createElement("gmd:result");
			$DQ_ConformanceResult = $doc->createElement("gmd:DQ_ConformanceResult");

			$specification = $doc->createElement("gmd:specification");
			$CI_Citation = $doc->createElement("gmd:CI_Citation");

			$gmdtitle = $doc->createElement("gmd:title");
				$CharacterString = $doc->createElement("gco:CharacterString",$reporttitle);
			
			$gmdtitle->appendChild($CharacterString);
			$CI_Citation->appendChild($gmdtitle);


			$date = $doc->createElement("gmd:date");
			$CI_Date = $doc->createElement("gmd:CI_Date");

			$gmddate = $doc->createElement("gmd:dateType");
			$gcoDate = $doc->createElement("gco:Date",$reportdate);
			
			$gmddate->appendChild($gcoDate);
			$CI_Date->appendChild($gmddate);


			$dateType = $doc->createElement("gmd:dateType");
			$CI_DateTypeCode = $doc->createElement("gmd:CI_DateTypeCode");
			$CI_DateTypeCode->setAttribute("codeList","http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#CI_DateTypeCode");
			$dateType->appendChild($CI_DateTypeCode);
			$CI_Date->appendChild($dateType);

			$date->appendChild($CI_Date);
			$CI_Citation->appendChild($date);


			$specification->appendChild($CI_Citation);
			$DQ_ConformanceResult->appendChild($specification);

			$gmdexplanation = $doc->createElement("gmd:explanation");

				$CharacterString = $doc->createElement("gco:CharacterString",$explanationtitle);
			
			$gmdexplanation->appendChild($CharacterString);

			$DQ_ConformanceResult->appendChild($gmdexplanation);



			$result->appendChild($DQ_ConformanceResult);
			$DQ_DomainConsistency->appendChild($result);
			$report->appendChild($DQ_DomainConsistency);
			$DQ_DataQuality->appendChild($report);


			$lineage = $doc->createElement("gmd:lineage");
			$LI_Lineage = $doc->createElement("gmd:LI_Lineage");
			$statement = $doc->createElement("gmd:statement");
			$CharacterString = $doc->createElement("gco:CharacterString",$lineagetitle);
			$statement->appendChild($CharacterString);
			$LI_Lineage->appendChild($statement);
			$lineage->appendChild($LI_Lineage);
			$DQ_DataQuality->appendChild($lineage);

			$dataQualityInfo->appendChild($DQ_DataQuality);
			$metadata->appendChild($dataQualityInfo);

			//var_dump($lineage);die;

			$gmdfileIdentifier->setAttribute("id",1);
			$doc->save($_SERVER['DOCUMENT_ROOT']."/packageDataset/".$id."/".$contentdataset["result"]["name"].".xml") or die("Error, ot created");
			

			return "/packageDataset/".$id."/".$contentdataset["result"]["name"].".xml";
    }
}
