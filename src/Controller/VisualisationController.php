<?php
namespace Drupal\ckan_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\Logger;

/**
 * Provides route responses for the Example module.
 
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
 
 */
class VisualisationController extends ControllerBase {


	public function myPage(Request $request, $tab) {
		$id = $request->query->get('id');
		return $this->myPage2($id, $tab);
	}


	/**
	 * Returns a simple page.
	 *
	 * @return array
	 *   A simple renderable array.
	 */
	public function myPage2($id, $tab) {

		
		$config = json_decode(file_get_contents(__DIR__ ."/../../config.json"));
		$host = \Drupal::request()->getHost();
		$protocol = \Drupal::request()->getScheme()."://";
		$loggedIn = \Drupal::currentUser()->isAuthenticated();
		       
		
		$api = new API();
		$dataset = $api->getPackageShow2($id,"");
		echo"<pre>";
		var_dump($dataset);
		echo "</pre>";die;

		$name = $dataset["metas"]["title"];
		$description = $dataset["metas"]["description"];
		
		
		$url = $protocol . $host . "/visualisation?id=" . $dataset["datasetid"];
		$dateModified = $dataset["metas"]["modified"];
		$keywords = $dataset["metas"]["keyword"];
		$license = $dataset["metas"]["license"];
		$resources = array();
		$resourcesid = "";


		foreach($dataset["metas"]["resources"] as $value){
            if($value['format'] == 'CSV' || $value['format'] == 'XLS' || $value['format'] == 'XLSX'){
		 		$resourcesid = $value['id'];
                
		 	}
			if($value['format'] != 'CSV' && $value['format'] != 'XLS' && $value['format'] != 'XLSX' && $value['format'] != 'GeoJSON' && $value['format'] != 'JSON' && $value['format'] != 'KML' && $value['format'] != 'SHP'){
				$res = array();
				$res["@type"] = "DataDownload";
				$res["encodingFormat"] = $value['format'];
				$res["contentUrl"] = $protocol . $host . "/api/datasets/1.0/" . $dataset["datasetid"] . "/alternative_exports/" . $value['id'];
				$resources[] = $res;
			}
		}
		/*Logger::logMessage("ressource " . json_encode($resources));*/
		
		if($resourcesid != ""){
			$res = array();
			$res["@type"] = "DataDownload";
			$res["encodingFormat"] = "CSV";
			$res["contentUrl"] = $protocol . $host . "/api/records/2.0/downloadfile/format=csv&use_labels_for_header=true&resource_id=" . $resourcesid;
			$resources[] = $res;
			
			$res = array();
			$res["@type"] = "DataDownload";
			$res["encodingFormat"] = "JSON";
			$res["contentUrl"] = $protocol . $host . "/api/records/2.0/downloadfile/format=json&resource_id=" . $resourcesid;
			$resources[] = $res;
			
			$res = array();
			$res["@type"] = "DataDownload";
			$res["encodingFormat"] = "Excel";
			$res["contentUrl"] = $protocol . $host . "/api/records/2.0/downloadfile/format=xls&use_labels_for_header=true&resource_id=" . $resourcesid;
			$resources[] = $res;
			
			if($isGeo){
				$res = array();
				$res["@type"] = "DataDownload";
				$res["encodingFormat"] = "GeoJSON";
				$res["contentUrl"] = $protocol . $host . "/api/records/2.0/downloadfile/format=geojson&resource_id=" . $resourcesid;
				$resources[] = $res;
				
				$res = array();
				$res["@type"] = "DataDownload";
				$res["encodingFormat"] = "KML";
				$res["contentUrl"] = $protocol . $host . "/api/records/2.0/downloadfile/format=kml&resource_id=" . $resourcesid;
				$resources[] = $res;
				
				$res = array();
				$res["@type"] = "DataDownload";
				$res["encodingFormat"] = "Shapefile";
				$res["contentUrl"] = $protocol . $host . "/api/records/2.0/downloadfile/format=shp&resource_id=" . $resourcesid;
				$resources[] = $res;
			}
		}
        
        $met=$dataset[metas][extras];
        $LinkedDataSet='';
        //add mention legales bloc
        $mention_legales='';
        //add frequence bloc
        $frequence='';
        $themes='';
        $ftp_api ='<div class="d4c-dataset-metadata-block__metadata ng-scope" style="font-size: 1rem; margin: -0.8em  0 -1em 0;">
						<div class="d4c-dataset-metadata-block__metadata-name ng-binding" >Source</div>   
						<div class="d4c-dataset-metadata-block__metadata-value d4c-dataset-metadata-block__metadata-value--default ng-binding ng-scope"> FTP/SFTP</div>
					</div>';
        $source = '';
        
        /*$api_vis='';*/
        
        /*$analize_vis=' ';*/
        
        
        
        /*$table_vis='';*/

        
        
        $theme_label_ex=false;
        $theme=false;
		$visu = 1;
        for($i=0; $i < count($met); $i++){
            if($met[$i]['key']=='LinkedDataSet'){
                $links = $met[$i][value];
                $links = explode(";", $links);
                for($j=0; $j<count($links); $j++){
                    $link = explode(":", $links[$j]);
                    
                    if($link[0]!='false'){
                        $url = 'visualisation?id='. $link[1];
                         $LinkedDataSet = $LinkedDataSet.'&nbsp<p style="margin: -1.1em 0 -1em;" ><code style="cursor: pointer;" onclick="window.open(`'.$url.'`, `_blank`);">'.$link[0].'</code></p><br>';
                    }
                }
            }
            
            if($met[$i]['key']=='label_theme'){
                $theme_label_ex=true;
                $theme=true;
                $themes = $met[$i][value];
                
                $themes ='<div class="d4c-dataset-metadata-block__metadata ng-scope" style="font-size: 1rem; margin: -0.8em  0 -1em 0;"><div class="d4c-dataset-metadata-block__metadata-name ng-binding" >Thème</div>   <div class="d4c-dataset-metadata-block__metadata-value d4c-dataset-metadata-block__metadata-value--default ng-binding ng-scope">'.$themes.'</div></div>'; 
            }
			
			 if($met[$i]['key']=='default_visu'){
				 $visu = $met[$i][value];
			 }

            if($met[$i]['key']=='theme' && $theme_label_ex==false){
                $theme=true;
                $themes = $met[$i][value];
                $themes ='<div class="d4c-dataset-metadata-block__metadata ng-scope" style="font-size: 1rem; margin: -0.8em  0 -1em 0;"><div class="d4c-dataset-metadata-block__metadata-name ng-binding" >Thème</div>   <div class="d4c-dataset-metadata-block__metadata-value d4c-dataset-metadata-block__metadata-value--default ng-binding ng-scope">'.$themes.'</div></div>'; 
            }
            
            if($met[$i]['key']=='FTP_API'){
                if($met[$i][value]!='FTP'){
					$lab_source =  parse_url($met[$i][value]);
                    $ftp_api ='<div class="d4c-dataset-metadata-block__metadata ng-scope" style="font-size: 1rem; margin: -0.8em  0 -1em 0;"><div class="d4c-dataset-metadata-block__metadata-name ng-binding" >Site Source</div> <div class="d4c-dataset-metadata-block__metadata-value d4c-dataset-metadata-block__metadata-value--default ng-binding ng-scope">  '.$lab_source["host"].'</div></div>';
                  
                    $source = '<div class="d4c-dataset-metadata-block">
                                <div class="d4c-dataset-metadata-block__metadata"><div class="d4c-dataset-metadata-block__metadata ng-scope" style="font-size: 1rem; margin: -0.8em  0 -1em 0;"><div class="d4c-dataset-metadata-block__metadata-name ng-binding" >Données Source</div>   <div class="d4c-dataset-metadata-block__metadata-value d4c-dataset-metadata-block__metadata-value--default ng-binding ng-scope"><p ><code style="cursor: pointer;" onclick="window.open(`'.$met[$i][value].'`, `_blank`);">'.$met[$i][value].'</code></p></div></div> </div>
                            </div>';
                } 
            }

            // get source value
            if($met[$i]['key']=='source' && $met[$i][value]!= null){
                    $ftp_api ='<div class="d4c-dataset-metadata-block__metadata ng-scope" style="font-size: 1rem; margin: -0.8em  0 -1em 0;"><div class="d4c-dataset-metadata-block__metadata-name ng-binding" >Site Source</div> <div class="d4c-dataset-metadata-block__metadata-value d4c-dataset-metadata-block__metadata-value--default ng-binding ng-scope">  '.$met[$i][value].'</div></div>';
            }

            //get donnees source value
           	if($met[$i]['key']=='donnees_source' && $met[$i][value]!= null){
                  
                    $source = '<div class="d4c-dataset-metadata-block">
                                <div class="d4c-dataset-metadata-block__metadata"><div class="d4c-dataset-metadata-block__metadata ng-scope" style="font-size: 1rem; margin: -0.8em  0 -1em 0;"><div class="d4c-dataset-metadata-block__metadata-name ng-binding" >Données Source</div>   <div class="d4c-dataset-metadata-block__metadata-value d4c-dataset-metadata-block__metadata-value--default ng-binding ng-scope"><p ><code style="cursor: pointer;" onclick="window.open(`'.$met[$i][value].'`, `_blank`);">'.$met[$i][value].'</code></p></div></div> </div>
                            </div>';
            }

			if($met[$i]['key']=='custom_view'){
				$view = json_encode($met[$i][value]);
			}
            
            if($met[$i]['key']=='widgets'){
                $visWidget = $met[$i][value];
                $visWidget = explode('<.explode.>',$visWidget);
                $result_w = '';
                
                foreach($visWidget as &$val_w){
                    if(substr($val_w, -7)=='<.off.>'){}
                    else{
                        // $val_w = substr($val_w, 0, -3);
                        
                        $data_w = explode('<.info.>',$val_w);
                        
                        $url = filter_var($data_w[2], FILTER_SANITIZE_URL);
                        if (!filter_var($url, FILTER_VALIDATE_URL) === false) {
                            //$data_w[2] = '<a href="'.$data_w[2].'">'.$data_w[0].'</a>';
                            $data_w[2] ='<iframe style="width:100%; height:50em; border:none" src="'.$data_w[2].'"></iframe>';
                        } 
                        
						$result_w = $result_w.'<d4c-collapsible ng-if="ctx.dataset.has_records"
                                     class="d4c-dataset-visualization__schema"><d4c-collapsible-above-fold><h3 class="d4c-dataset-visualization__toggle-schema"><span>'.$data_w[0].'</span></h3></d4c-collapsible-above-fold><d4c-collapsible-fold><p>'.$data_w[1].'</p><br><div>'.$data_w[2].'</div></d4c-collapsible-fold></d4c-collapsible>'; 
                    }
                }
                
				$visWidget = $result_w;
			}

			// define mention legales bloc
			if($met[$i]['key']=='mention_legales'){

                    $mention_legales ='<div class="d4c-dataset-metadata-block__metadata ng-scope" style="font-size: 1rem; margin: -0.8em  0 -1em 0;"><div class="d4c-dataset-metadata-block__metadata-name ng-binding" >Mentions légales</div> <div class="d4c-dataset-metadata-block__metadata-value d4c-dataset-metadata-block__metadata-value--default ng-binding ng-scope">  '.$met[$i][value].'</div></div><br>';

         }

         // define frequence bloc
			if($met[$i]['key']=='frequence'){

                    $frequence ='<div class="d4c-dataset-metadata-block__metadata ng-scope" style="font-size: 1rem; margin: -0.8em  0 -1em 0;">
                    				<div class="d4c-dataset-metadata-block__metadata-name ng-binding" >Fréquence de maj</div>
                    				<div class="d4c-dataset-metadata-block__metadata-value d4c-dataset-metadata-block__metadata-value--default ng-binding ng-scope">  '.$met[$i][value].'</div></div><br>';

         }


		}

		if($visu == 0) {
			$tab = 'information';
		}
		else if($visu == 1) {
			$tab = 'table';
		}
		else if($visu == 2) {
			$tab = 'analyze';
		}
		else if($visu == 3) {
			$tab = 'map';
		}
		else if($visu == 4) {
			$tab = $view->title;
		}
		else if($visu == 5) {
			$tab = 'timeline';
		}
		else if($visu == 6) {
			$tab = 'calendar';
		}
		else if($visu == 7) {
			$tab = 'wordcloud';
		}

		Logger::logMessage("Launching visu = " . $visu);
		Logger::logMessage("Displaying tab = " . $tab);

		if(!isset($tab)) {
			$tab = 'table';
		}
		
        if($theme==false){
            $themes ='<div class="d4c-dataset-metadata-block__metadata ng-scope" style="font-size: 1rem; margin: -0.8em  0 -1em 0;"><div class="d4c-dataset-metadata-block__metadata-name ng-binding" >Thème</div>   <div class="d4c-dataset-metadata-block__metadata-value d4c-dataset-metadata-block__metadata-value--default ng-binding ng-scope">Default</div></div>'; 
        }
        
        if($LinkedDataSet!=''){
           $LinkedDataSet= '  <div class="d4c-dataset-metadata-block__metadata" style="font-size: 1rem; ">
                                    <div class="d4c-dataset-metadata-block__metadata-name" translate=""><span class="ng-scope">Dataset liés</span></div>
                                    <div class="d4c-dataset-metadata-block__metadata-value">'.$LinkedDataSet.'</div>
                                </div>';
        }
        

	/**
	 * This is disabled for now
	 * It is a developpement made for GE
	 * 
	 */
	//     $resourcesContent = ""; 
	//     $MapDetail = ""; 
	//     $featureCatalog = ""; 
	//     $DateDetail="";
	//     $dateUpdated ="";
	//     $shareSocialMedia="";
	//     $associatedResources ="";

	//     $xmlfile =false;
	//     foreach($dataset["metas"]["resources"] as $key=>$value){
	    	
	//     	if($value["format"] == "csw" || strpos($value["name"], "Vue XML des métadonnées")== true) {

	//     		$xmlfile = true;
	//     	$xml = file_get_contents($value['url']); 

	//     	if (!file_exists($_SERVER['DOCUMENT_ROOT']."/". $id)) {
	// 	    	mkdir($_SERVER['DOCUMENT_ROOT']."/". $id, 0777, true);
	// 		}
	// 		file_put_contents($_SERVER['DOCUMENT_ROOT']."/". $id."/metadata_xml_view.xml", $xml);
			
	// 		break;
	//     }

	//     }



	//     if (file_exists($_SERVER['DOCUMENT_ROOT']."/". $id."/metadata_xml_view.xml")) {

	// 		    $str=implode("\n",file($_SERVER['DOCUMENT_ROOT']."/". $id."/metadata_xml_view.xml"));


	// 			$fp=fopen($_SERVER['DOCUMENT_ROOT']."/".$id."/metadata_xml_view.xml",'w');
	// 			$str=str_replace('&','??',$str);
	// 			$str=str_replace(':','',$str);
	// 			fwrite($fp,$str,strlen($str));

	// 			$xml = simplexml_load_file($id."/metadata_xml_view.xml");

	// 			 /*foreach ($xml as $key => $value) {
	// 			 	echo "<pre>";
	// 			 	var_dump($value->gmdcontact);

	// 			 	echo "</pre>";
	// 			 }die;*/

	//     foreach ($xml as $key => $value) {
	//      	$MapDetail='<section class="gn-md-side-extent ng-scope" > 
	// 					<h2 style="font-size: 16px;"> <i class="fa fa-fw fa-map-marker"></i> 
	// 					<span data-translate="" class="ng-scope" >Extension spatiale</span>
	//  					</h2> ';

	//  		$MapDetail.='<ul> <li >'.$value->gmdidentificationInfo->gmdMD_DataIdentification->gmdextent->gmdEX_Extent->gmddescription->gcoCharacterString->__toString().'</li></ul> ';
	//  		$MapDetail.='<img class="gn-img-thumbnail img-thumbnail gn-img-extent" alt="Spatial extent" aria-label="Spatial extent" data-ng-src="https://www.geograndest.fr/geonetwork/srv/eng/region.getmap.png?mapsrs=EPSG:3857&width=250&background=settings&geomsrs=EPSG:4326&geom=Polygon(('.$value->gmdidentificationInfo->gmdMD_DataIdentification->gmdextent->gmdEX_Extent->gmdgeographicElement->gmdEX_GeographicBoundingBox->gmdwestBoundLongitude->gcoDecimal->__toString().'%20'.$value->gmdidentificationInfo->gmdMD_DataIdentification->gmdextent->gmdEX_Extent->gmdgeographicElement->gmdEX_GeographicBoundingBox->gmdsouthBoundLatitude->gcoDecimal->__toString().','.$value->gmdidentificationInfo->gmdMD_DataIdentification->gmdextent->gmdEX_Extent->gmdgeographicElement->gmdEX_GeographicBoundingBox->gmdeastBoundLongitude->gcoDecimal->__toString().'%20'.$value->gmdidentificationInfo->gmdMD_DataIdentification->gmdextent->gmdEX_Extent->gmdgeographicElement->gmdEX_GeographicBoundingBox->gmdsouthBoundLatitude->gcoDecimal->__toString().','.$value->gmdidentificationInfo->gmdMD_DataIdentification->gmdextent->gmdEX_Extent->gmdgeographicElement->gmdEX_GeographicBoundingBox->gmdeastBoundLongitude->gcoDecimal->__toString().'%20'.$value->gmdidentificationInfo->gmdMD_DataIdentification->gmdextent->gmdEX_Extent->gmdgeographicElement->gmdEX_GeographicBoundingBox->gmdnorthBoundLatitude->gcoDecimal->__toString().',8.23029041290283203125%2050.16764068603515625,8.23029041290283203125%2047.42026519775390625))" src="https://www.geograndest.fr/geonetwork/srv/eng/region.getmap.png?mapsrs=EPSG:3857&width=250&background=settings&geomsrs=EPSG:4326&geom=Polygon((8.23029041290283203125%2047.42026519775390625,3.3840906620025634765625%2047.42026519775390625,3.3840906620025634765625%2050.16764068603515625,8.23029041290283203125%2050.16764068603515625,8.23029041290283203125%2047.42026519775390625))">';

	//  		$MapDetail.="</section>";



	//  		$DateDetail='<section class="gn-md-side-dates ng-scope" > <h2> <i class="fa fa-fw fa-clock-o" style="font-size: 16px;"></i> <span data-translate="" class="ng-scope" style="font-size: 16px;">Étendue temporelle</span> </h2> <p> </p>';
					
	// 				foreach ($value->gmdidentificationInfo->gmdMD_DataIdentification->gmdcitation->gmdCI_Citation->gmddate as  $valuedate) {
						
	// 					if($valuedate->gmdCI_Date->gmddateType->gmdCI_DateTypeCode->__toString() == "publication") {
	// 						$DateDetail.='<dl > <dt data-translate="" class="ng-scope">La date de publication</dt>';

	// 					}

	// 					if($valuedate->gmdCI_Date->gmddateType->gmdCI_DateTypeCode->__toString() == "revision") {
	// 						$DateDetail.='<dl > <dt data-translate="" class="ng-scope">La date de révision</dt>';
							
	// 					}
	// 					$DateDetail.='<dd data-gn-humanize-time="'.$valuedate->gmdCI_Date->gmddate->gcoDate->__toString().'" data-format="YYYY-MM-DD" class="ng-scope ng-isolate-scope"><span title="3 months ago" class="ng-binding">'.$valuedate->gmdCI_Date->gmddate->gcoDate->__toString().'</span></dd> </dl>';
	// 				}
	// 				$DateDetail.='</section>';





	// 		$datestamps = explode("T", $value->gmddateStamp->gcoDateTime->__toString());

	// 		$dateUpdated='<section class="gn-md-side-calendar"> <h2 style="font-size:16px"> <i class="fa fa-fw fa-calendar"></i><span data-translate="" class="ng-scope">Modifié: </span> </h2>';
	// 		$now = time(); 
	// 		$your_date = strtotime($datestamps[0]);
	// 		$datediff = $now - $your_date - 1;
	// 		$days = round($datediff / (60 * 60 * 24)) - 1;

	// 		$dateUpdated.='<p><span data-gn-humanize-time="'.$value->gmddateStamp->gcoDateTime->__toString().'" data-from-now="" class="ng-isolate-scope"><span title="'.$value->gmddateStamp->gcoDateTime->__toString().'" class="ng-binding"> Il y a '.$days.' jour(s)</span></span> </p>';

	// 		$dateUpdated.='</section>';


	// 		$shareSocialMedia ='<section class="gn-md-side-social" style="margin-top: 20px" > <h2 style="font-size: 16px"> <i class="fa fa-fw fa-share-square-o"></i> <span data-translate="" class="ng-scope">Partager</span> </h2> 
	// <a data-ng-href="#" title="Share on Twitter" target="_blank" class="btn btn-default" href="#"><i class="fa fa-fw fa-twitter"></i></a>
	// <a data-ng-href="#" title="Share on Facebook" target="_blank" class="btn btn-default" href="#"><i class="fa fa-fw fa-facebook"></i></a> <a data-ng-href="#" title="Share on LinkedIn" target="_blank" class="btn btn-default" href="#"><i class="fa fa-fw fa-linkedin"></i></a> <a data-ng-href="#" title="Share by email" target="_blank" class="btn btn-default" href="#"><i class="fa fa-fw fa-envelope-o"></i></a> <a data-ng-click="mdService.getPermalink(md)" title="Permalink" class="btn btn-default"><i class="fa fa-fw fa-link"></i></a> </section>';


	// 		$featureCatalog ='<div><h2 style="font-size: 16px" class="ng-binding">À propos de cette ressource</h2></div>';
	// 		$associatedResources =" <div>";
	// 		$associatedResources .='<table class="table table-striped"><tbody> ';
	// 		foreach ($value->gmdidentificationInfo->gmdMD_DataIdentification->gmddescriptiveKeywords as $key2 => $value2) {
				
	// 			if($value2->gmdMD_Keywords->gmdthesaurusName) {
	// 				$associatedResources .='<tr > <th data-translate="" class="ng-scope">INSPIRE themes</th><td> <button data-ng-click="search({\'inspirethemewithac\': '.$value2->gmdMD_Keywords->gmdkeyword->gcoCharacterString->__toString().'})" class="btn btn-sm btn-default ps ps-en" title="Click to filter on  Sites protégés"> <i class="fa fa-download" style="color: #95c11f"></i> </button> </td>
	// 	  		</tr>';
	// 			} 

				
	// 		}

	// 		if($value->gmdidentificationInfo->gmdMD_DataIdentification->gmdtopicCategory->__toString() ) {
	// 				$associatedResources .='<tr> <th data-translate="" class="ng-scope">Categories</th> <td><button data-ng-click="search({\'topicCat\': cat})" class="btn btn-sm btn-default ng-binding ng-scope" title="Click to filter on  Environment"> <span class="fa gn-icon-environment topic-color"></span>&nbsp; Environment </button> </td> </tr>';

	// 			}
	// 		foreach ($value->gmdidentificationInfo->gmdMD_DataIdentification->gmddescriptiveKeywords as $key2 => $value2) {
	// 			if($value2->gmdMD_Keywords->gmdthesaurusName) {
	// 					$associatedResources .='<tr > <th data-translate="" class="ng-scope">'.$value2->gmdMD_Keywords->gmdthesaurusName->gmdCI_Citation->gmdtitle->gcoCharacterString->__toString().'</th><td><ul> 
	// 	  	 				<li > <span class="ng-binding">'.$value2->gmdMD_Keywords->gmdkeyword->gcoCharacterString->__toString().'</span> <a  href="" title="Click to filter on  Sites protégés" aria-label="Click to filter on  Sites protégés" data-ng-click="search({\'keyword\': '.$value2->gmdMD_Keywords->gmdkeyword->gcoCharacterString->__toString().'})"> <i class="fa fa-search"></i> </a> </li></ul></td>
	// 	  		</tr>';
	// 			}
	// 		}

	// 		$associatedResources .='<tr > <th data-translate="" class="ng-scope">Autres mots-clés</th><td>';
	// 		foreach ($value->gmdidentificationInfo->gmdMD_DataIdentification->gmddescriptiveKeywords as $key2 => $value2) {
	// 			if(!$value2->gmdMD_Keywords->gmdthesaurusName) {

	// 				$associatedResources .='<ul style="list-style-type: disc;"> 
	// 	  	 				<li > <span class="ng-binding">'.$value2->gmdMD_Keywords->gmdkeyword->gcoCharacterString->__toString().'</span> <a  href="" title="Click to filter on  Sites protégés" aria-label="Click to filter on  Sites protégés" data-ng-click="search({\'keyword\': '.$value2->gmdMD_Keywords->gmdkeyword->gcoCharacterString->__toString().'})"> <i class="fa fa-search"></i> </a> </li></ul>
	// 	  		';
	// 			}
	// 		}
	// 		$associatedResources .='</td></tr>';

	// 		$associatedResources .='<tr > <th data-translate="" class="ng-scope">Langue</th><td><ul> 
	// 	  	 				<li > <span class="ng-binding">'.$value->gmdlanguage->gmdLanguageCode->__toString().'</span> </li></ul></td>
	// 	  		</tr>';

	// 	  	$associatedResources .='<tr > <th data-translate="" class="ng-scope">Identificateur de ressource</th><td><ul> 
	// 	  	 				<li > <span class="ng-binding">'.$value->gmdidentificationInfo->gmdMD_DataIdentification->gmdcitation->gmdCI_Citation->gmdidentifier->gmdRS_Identifier->gmdcode->gcoCharacterString->__toString().'</span> </li></ul></td>
	// 	  		</tr>';

	// 	  	$associatedResources .='<tr > <th data-translate="" class="ng-scope">Contraintes légales</th><td>'; 
	// 	  	foreach ($value->gmdidentificationInfo->gmdMD_DataIdentification->gmdresourceConstraints as $key2 => $value2) {
	// 	  		if($value2->gmdMD_LegalConstraints->gmdotherConstraints != null){
	// 	  			$associatedResources .='<p>'.$value2->gmdMD_LegalConstraints->gmdotherConstraints->gcoCharacterString->__toString().'<p>';
	// 	  		}
	// 	  			foreach ($value2->gmdMD_LegalConstraints->gmduseLimitation as $value3) {
	// 	  				$associatedResources .='<p>'.$value3->gcoCharacterString->__toString().'<p>';

	// 	  			}


						
	// 				}
	// 	  	$associatedResources .='</td></tr>';
		  	
	// 	  	$associatedResources .='<tr > <th data-translate="" class="ng-scope">Contact pour la ressource</th><td><adresse> 
	// 	  	 				 <strong><i class="fa fa-envelope" style="margin-right: 10px"></i> '.$value->gmdcontact->gmdCI_ResponsibleParty->gmdorganisationName->gcoCharacterString->__toString().'</strong> </adresse>
	// 	  	 				 <p>'.$value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmddeliveryPoint->gcoCharacterString->__toString().', '.$value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmdcity->gcoCharacterString->__toString().', '.$value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmdpostalCode->gcoCharacterString->__toString().', '.$value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmdcountry->gcoCharacterString->__toString().'</p> <ul style="list-style-type: disc;"><li> <strong>Point de contact: </strong><a href="mailto:'.$value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmdelectronicMailAddress->gcoCharacterString->__toString().'"> '.$value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmdelectronicMailAddress->gcoCharacterString->__toString().'</a></li></ul>
	// 	  	 				 </td>
	// 	  		</tr>';
		  		
	// 	  	$associatedResources .='<tr > <th data-translate="" class="ng-scope">Statut</th><td>
	// 	  	 				  <ul style="list-style-type: disc;"><li>  '.$value->gmdidentificationInfo->gmdMD_DataIdentification->gmdstatus->gmdMD_ProgressCode->__toString().'</a></li></ul>
	// 	  	 				 </td>
	// 	  		</tr>';

	// 		$associatedResources .='</tbody> </table>';

	// 		$associatedResources .='<h4>Informations techniques</h4>';
	// 		$associatedResources .='<table class="table table-striped"><tbody> ';

		
	// 		$associatedResources .='<tr > <th data-translate="" class="ng-scope">Score</th><td>
	// 	  	 				  <ul style="list-style-type: disc;"><li>  '.$value->gmdidentificationInfo->gmdMD_DataIdentification->gmdspatialResolution->gmdMD_Resolution->gmdequivalentScale->gmdMD_RepresentativeFraction->gmddenominator->gcoInteger->__toString().'</a></li></ul>
	// 	  	 				 </td>
	// 	  		</tr>';

	// 	  	$associatedResources .='<tr > <th data-translate="" class="ng-scope">Format</th><td>
	// 	  	 				  <ul style="list-style-type: disc;"><li>  '.$value->gmddistributionInfo->gmdMD_Distribution->gmddistributionFormat->gmdMD_Format->gmdname->gcoCharacterString->__toString().'</a></li></ul>
	// 	  	 				 </td>
	// 	  		</tr>';

	// 	  	$associatedResources .='<tr > <th data-translate="" class="ng-scope">Lignée</th><td>
	// 	  	 				  <ul style="list-style-type: disc;"><li>  '.$value->gmddataQualityInfo->gmdDQ_DataQuality->gmdlineage->gmdLI_Lineage->gmdstatement->gcoCharacterString->__toString().'</a></li></ul>
	// 	  	 				 </td>
	// 	  		</tr>';



	// 		$associatedResources .='</tbody> </table>';

	// 		$associatedResources .='<h4>Metadata information</h4>';
	// 		$associatedResources .='<table class="table table-striped"><tbody> ';

	// 		$associatedResources .='<tr > <th data-translate="" class="ng-scope"><a class="btn btn-default gn-margin-bottom" href="../api/records/fr-120066022-jdd-d90ac948-9e07-47a6-9c1b-471888dbefd4/formatters/xml"> <i class="fa fa-fw fa-file-code-o"></i> <span data-translate="" class="ng-scope">Download metadata</span> </a></th>
	// 	  		</tr>';

	// 	  		$associatedResources .='<tr > <th data-translate="" class="ng-scope"><strong> Contact </strong> </th><td><adresse> 
	// 	  	 				 <strong><i class="fa fa-envelope" style="margin-right: 10px"></i> '.$value->gmdcontact->gmdCI_ResponsibleParty->gmdorganisationName->gcoCharacterString->__toString().'</strong> </adresse>
	// 	  	 				 <p>'.$value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmddeliveryPoint->gcoCharacterString->__toString().', '.$value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmdcity->gcoCharacterString->__toString().', '.$value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmdpostalCode->gcoCharacterString->__toString().', '.$value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmdcountry->gcoCharacterString->__toString().'</p> <ul style="list-style-type: disc;"><li> <strong>Point de contact: </strong><a href="mailto:'.$value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmdelectronicMailAddress->gcoCharacterString->__toString().'"> '.$value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmdelectronicMailAddress->gcoCharacterString->__toString().'</a></li></ul>
	// 	  	 				 </td>
	// 	  		</tr>';

	// 		$associatedResources .='</tbody> </table>';

	// 		$associatedResources .=" </div>";

	// 	/*	echo "<pre>";
	// 		var_dump();
	// 		echo "</pre>";*/


	//      }


	// 		} 
	// 		//die;
	//     if(sizeof($dataset["metas"]["resources"]) > 0 ) {
	//     	$resourcesContent = '<h4>Téléchargements et liens</h4>';

	//     foreach($dataset["metas"]["resources"] as $key=>$value){
	 
	//  		if (strpos($value["resource_locator_protocol"], 'download') !== false or strpos($value["resource_locator_protocol"], 'DOWNLOAD') !== false) {
	// 		    $resourcesContent .= '<div class="row" style="width:80% !impotant; padding: 10px;border: 1px solid;"><div class="col-sm-9"> <i style="margin-right: 12px; font-size: 20px" class="fa fa-download" fa-4x></i>'.$value["name"].' <br><a target="_blank" href="'.$value["url"].'">'.$value["url"].'</a></div>';
	// 		}
	// 		else {
	// 			$resourcesContent .= '<div class="row" style="width:80% !impotant; 	padding: 10px;border: 1px solid;"><div class="col-sm-9"> <i style="margin-right: 12px; font-size: 20px" class="fa fa-link" fa-4x></i>'.$value["name"].'<br><a target="_blank" href="'.$value["url"].'">'.$value["url"].'</a></div>';
	// 		}

	    	

	//     if (strpos($value["resource_locator_protocol"], 'download') !== false or strpos($value["resource_locator_protocol"], 'DOWNLOAD') !== false) {
	// 		    $resourcesContent .= '<div class="col-sm-3"><a class="btn btn-info" role="button" target="_blank" href="'.$value["url"].'" >Download</a></div>
	// 						</div>';
	// 		}

	// 		else {
	// 		    $resourcesContent .= '<div class="col-sm-3" ><a class="btn btn-info" role="button" target="_blank" href="'.$value["url"].'" >Consulter</a></div>
	// 						</div>';
	// 		}

	// 		}
	// 		$resourcesContent .="<br><br><br> ";
	//     }
	

		$ctx = str_replace(array("{", "}", '"'), array("\{", "\}", "&quot;"), json_encode($dataset));
		
		$element = array(
			'example one' => [
				'#type' => 'inline_template',
				/*'#attached' => array(
					'library' =>  array(
						'ckan_admin/anfr.angular',
							),
					),*/
				'#template' => '<body>

        <div class="d4c-content">
            <header class="ng-scope"></header>
            <main class="main--dataset">

				<div class="container-fluid d4c-app-explore-dataset ng-cloak"
					ng-app="d4c.frontend"
					ng-controller="ExploreDatasetController"
					d4c-dataset-context
					ng-init="toggleState={expandedFilters: false};"
					context="ctx"
					ctx-urlsync="true"
					ctx-dataset-schema="'.$ctx.'">

					<d4c-notification-handler></d4c-notification-handler>
        
					<div class="d4c-filters-summary"
						 ng-class="{\'d4c-filters-summary--expanded\': toggleState.expandedFilters}">
						<div class="d4c-filters-summary__count">
							
							<span class="d4c-filters-summary__count-number">\{\{ ctx.nhits | number \}\}</span>
							<span class="d4c-filters-summary__count-units" translate translate-n="ctx.nhits" translate-plural="records">record</span>
							
						</div>
						<button class="d4c-button d4c-filters-summary__toggle"
								ng-click="toggleMobileFilters()">
							<i class="fa" aria-hidden="true" ng-class="{\'fa-expand\': !toggleState.expandedFilters, \'fa-compress\': toggleState.expandedFilters}"></i>
							Filtres
						</button>
					</div>
					<div class="d4c-filters"
						 ng-class="{\'d4c-filters--expanded\': toggleState.expandedFilters}" ng-show="canAccessData()">
						
						<h2 class="d4c-filters__count">
								<span class="d4c-filters__count-number">\{\{ ctx.nhits | number \}\}</span>
								<span class="d4c-filters__count-units" translate translate-n="ctx.nhits" translate-plural="records">record</span>
							</h2>
							<h2 class="d4c-filters__filters-summary" ng-show="ctx.getActiveFilters().length">
								<span translate>Active filters</span>
								<d4c-clear-all-filters context="ctx"></d4c-clear-all-filters>
							</h2>
							<d4c-filter-summary context="ctx" clear-all-button="false"></d4c-filter-summary>
							<div ng-hide="ctx.getActiveFilters().length"
								 class="d4c-filters__no-filters">
								Aucun filtre actif.
							</div>

							<h2 class="d4c-filters__filters"><span translate>Filters</span></h2>
							<d4c-text-search context="ctx" placeholder="Rechercher..." autofocus></d4c-text-search>
							<d4c-facets context="ctx"></d4c-facets>
					
					</div><div class="d4c-dataset-visualization"
						   ng-class="{\'d4c-dataset-visualization--full-width\': !canAccessData()}">
					<div class="d4c-dataset-visualization__header">
						<h1 class="d4c-dataset-visualization__dataset-title">
							<d4c-social-buttons></d4c-social-buttons>
							

							<span>\{\{ ctx.dataset.metas.title \}\}</span>

							
							<!--   <div class="d4c-dataset-visualization__edit-dataset">
									<a href="/publish/das-telephonie-mobile/"
									   class="d4c-dataset-visualization__edit-dataset-link">
										<i class="fa fa-pencil" aria-hidden="true"></i>
										<span translate>Edit</span>
									</a>
								</div> -->
							
						</h1>
					</div>
					<d4c-tabs sync-to-url="true" sync-to-url-mode="path" name="main" default-tab="'.$tab.'">
					<d4c-pane pane-auto-unload="true" title="Information" icon="info-circle" translate="title" slug="information">

						<div>'.$description.'</div>

						<div class="row">
								<div class="col-sm-12"
									 ng-if="basicTemplate && interopTemplates">

									<div class="d4c-dataset-metadata-block">
										<div class="d4c-dataset-metadata-block__metadata">
											<div class="d4c-dataset-metadata-block__metadata-name" translate>Dataset Identifier</div>
											<div class="d4c-dataset-metadata-block__metadata-value"><code>\{\{ ctx.dataset.metas.name \}\}</code></div>
										</div>
									</div>

									<div class="d4c-dataset-metadata-block" ng-show="(ctx.dataset.metas.extras | filter:{key:\'date_dataset\'})[0].value">
										<div class="d4c-dataset-metadata-block__metadata">
											<div class="d4c-dataset-metadata-block__metadata-name" translate>Dataset date</div>
											<div class="d4c-dataset-metadata-block__metadata-value ng-binding">\{\{ (ctx.dataset.metas.extras | filter:\{key:\'date_dataset\'\})[0].value | formatMeta:\'date\' \}\}</div>
										</div>
									</div>

									<div class="d4c-dataset-metadata-block" ng-show="(ctx.dataset.metas.extras | filter:{key:\'nb_download\'})[0].value > 0">
										<div class="d4c-dataset-metadata-block__metadata">
											<div class="d4c-dataset-metadata-block__metadata-name" translate=""><span class="ng-scope">Téléchargements</span></div>
											<div class="d4c-dataset-metadata-block__metadata-value ng-binding">\{\{ (ctx.dataset.metas.extras | filter:\{key:\'nb_download\'\})[0].value \}\}</div>
										</div>
									</div>
							   
									
									<div class="d4c-dataset-metadata-block">
										<div class="d4c-dataset-metadata-block__metadata">
										   '.$themes.'
										</div>
									</div>

									<div class="d4c-dataset-metadata-block">
										<div class="d4c-dataset-metadata-block__metadata">
										   '.$ftp_api.'
										</div>
									</div>
									
										   '.$source.'
										   '.$frequence.'
									
									<d4c-dataset-metadata-block metadata-schema="basicTemplate" values="ctx.dataset.metas" blacklist="[\'theme\',\'title\',\'description\',\'records_count\',\'source_domain\',\'source_domain_title\',\'source_domain_address\',\'source_dataset\',\'data_processed\',\'metadata_processed\',\'parent_domain\',\'geographic_area_mode\']"></d4c-dataset-metadata-block>

										'.$LinkedDataSet.'
										'.$mention_legales.'
									
									
									 <div class="d4c-dataset-metadata-block d4c-dataset-metadata-block--subtle" ng-if="ctx.dataset.metas.data_processed || ctx.dataset.metas.metadata_processed">
										<div class="d4c-dataset-metadata-block__metadata">
											<div class="d4c-dataset-metadata-block__metadata-name" translate>Last processing</div>
											<div class="d4c-dataset-metadata-block__metadata-value">
												<span ng-if="ctx.dataset.metas.metadata_processed">\{\{ ctx.dataset.metas.metadata_processed|formatMeta:\'datetime\' \}\} (<span translate>metadata</span>)<br /></span>
												<span ng-if="ctx.dataset.metas.data_processed">\{\{ ctx.dataset.metas.data_processed|formatMeta:\'datetime\' \}\} (<span translate>data</span>)</span>
											</div>
										</div>
									</div>
							  
									<d4c-dataset-metadata-block-selector metadata-templates="interopTemplates" values="ctx.dataset.interop_metas"></d4c-dataset-metadata-block-selector>

								</div>
							</div>
							'.$visWidget.'
							<d4c-dataset-attachments dataset="ctx.dataset"></d4c-dataset-attachments>

							<d4c-collapsible ng-if="ctx.dataset.has_records"
											 class="d4c-dataset-visualization__schema">
								<d4c-collapsible-above-fold>
									<h3 class="d4c-dataset-visualization__toggle-schema">
										<span translate>Dataset schema</span>
									</h3>
								</d4c-collapsible-above-fold>
								<d4c-collapsible-fold>

									<d4c-dataset-schema context="ctx"></d4c-dataset-schema>

									<h4 translate>JSON Schema</h4>

									<p>
										<span translate>The following JSON object is a standardized description of your dataset\'s schema.</span>
										<a href="http://json-schema.org/" target="_blank" translate>More about JSON schema</a>.
									</p>

									<d4c-dataset-json-schema context="ctx"></d4c-dataset-json-schema>

								</d4c-collapsible-fold>
							</d4c-collapsible>

							<d4c-dataset-reuses readonly="false"
                                                max="1"
                                                anonymous-reuse="true"
                                                logged-in="'.$loggedIn.'" recaptcha-pub-key="6LecPMcUAAAAADTDNPWerqMD2Es7g9CFAG2R0u7R" dataset-title="'.$name.'"
                                                config="{&#39;is_unique&#39;: True, &#39;max_width&#39;: 4096, &#39;max_height&#39;: 4096, &#39;resize_width&#39;: 200, &#39;resize_height&#39;: 200, &#39;asset_type&#39;: &#39;image&#39;, &#39;max_size&#39;: 2097152}"></d4c-dataset-reuses>
							
						</d4c-pane>

						<d4c-pane pane-auto-unload="true" title="Table" icon="table" translate="title" slug="table">
							<d4c-table context="ctx" auto-resize="true" dataset-feedback="true"></d4c-table>
							
								<d4c-embed-control context="ctx"
												   force-embed-dataset-card="false"
												   anonymous-access="true"
												   embed-type="table"></d4c-embed-control>
							
						</d4c-pane>
						<d4c-pane pane-auto-unload="true" title="Map" icon="globe" translate="title" slug="map"
								  do-not-register="!ctx.dataset.hasFeature(\'geo\')"
								  class="d4c-dataset-visualization__tab-map">
							<d4c-map context="ctx" sync-to-url="true" auto-resize="true"></d4c-map>
							
								<d4c-embed-control context="ctx"
												   force-embed-dataset-card="false"
												   anonymous-access="true"
												   embed-type="map"></d4c-embed-control>
							
						</d4c-pane>
                    
						<d4c-pane pane-auto-unload="true" title="Analyze" icon="chart-bar" translate="title" slug="analyze"
								  do-not-register="!ctx.dataset.hasFeature(\'analyze\')">
							<d4c-analyze context="ctx" sync-to-url="true"></d4c-analyze>
							
								<d4c-embed-control context="ctx"
												   force-embed-dataset-card="false"
												   anonymous-access="true"
												   embed-type="analyze"></d4c-embed-control>
							
						</d4c-pane>
                    
						<d4c-pane pane-auto-unload="true" title="Images" icon="picture-o" translate="title" slug="images"
								  do-not-register="!ctx.dataset.hasFeature(\'image\')">
							<d4c-media-gallery context="ctx" d4c-auto-resize d4c-widget-tooltip></d4c-media-gallery>
							
								<d4c-embed-control context="ctx"
												   force-embed-dataset-card="false"
												   anonymous-access="true"
												   embed-type="media-gallery"></d4c-embed-control>
							
						</d4c-pane>
						<d4c-pane pane-auto-unload="true" title="Calendar" icon="calendar" translate="title" slug="calendar"
								  do-not-register="!ctx.dataset.hasFeature(\'calendar\')">
							<d4c-calendar context="ctx" sync-to-url="true"></d4c-calendar>
							
								<d4c-embed-control context="ctx"
												   force-embed-dataset-card="false"
												   anonymous-access="true"
												   embed-type="calendar"></d4c-embed-control>
							
						</d4c-pane>
						
						<d4c-pane pane-auto-unload="true"
								  title="\{\{ ctx.dataset.extra_metas.visualization.custom_view_title || DefaultCustomViewConfig.title \}\}"
								  slug="\{\{ ctx.dataset.extra_metas.visualization.custom_view_slug || DefaultCustomViewConfig.slug \}\}"
								  icon="\{\{ ctx.dataset.extra_metas.visualization.custom_view_icon || DefaultCustomViewConfig.icon \}\}"
								  do-not-register="!ctx.dataset.hasFeature(\'custom_view\')">
							<div d4c-bind-angular-content="ctx.dataset.extra_metas.visualization.custom_view_html" do-not-decode-content></div>
							<style type="text/css" d4c-bind-angular-content="ctx.dataset.extra_metas.visualization.custom_view_css"></style>
							
							
							<d4c-embed-control context="ctx"
											   force-embed-dataset-card="false"
											   anonymous-access="true"
											   embed-type="custom"></d4c-embed-control>
							
						</d4c-pane>
						<d4c-pane pane-auto-unload="true" title="Word Cloud" icon="cloud" translate="title" slug="wordcloud"
								  do-not-register="!ctx.dataset.hasFeature(\'wordcloud\')">
							<d4c-wordcloud context="ctx" sync-to-url="true"></d4c-wordcloud>
							
								<d4c-embed-control context="ctx"
												   force-embed-dataset-card="false"
												   anonymous-access="true"
												   embed-type="wordcloud"></d4c-embed-control>
							
						</d4c-pane>
						
						<d4c-pane pane-auto-unload="true" title="Frise chronologique" icon="history" translate="title" slug="timeline"
								  do-not-register="!ctx.dataset.hasFeature(\'timeline\')">
							<d4c-timeline context="ctx" sync-to-url="true"></d4c-timeline>
							
								<d4c-embed-control context="ctx"
												   force-embed-dataset-card="false"
												   anonymous-access="true"
												   embed-type="timeline"></d4c-embed-control>
							
						</d4c-pane>
						
						<d4c-pane pane-auto-unload="true" title="Export" icon="download" translate="title" slug="export">
							<d4c-dataset-export context="ctx"
												
												shapefile-export-limit="50000"
												
												snapshots="false"
												></d4c-dataset-export>
						</d4c-pane>
						
						<d4c-pane pane-auto-unload="true" title="API" icon="cogs"  translate="title" slug="api">
							<d4c-dataset-api-console context="ctx"></d4c-dataset-api-console>
						</d4c-pane>
                
					</d4c-tabs>
			
					<!--<d4c-disqus
						   shortname="data4citizen"
						   identifier="'.$host."_".$dataset["datasetid"].'">
					</d4c-disqus>-->
				</div>
			</div>

		</main>

	</div>

    <footer class="ng-scope"></footer>
        
	<script src="/sites/default/files/api/portail_d4c/js/jquery-3.2.1.js"></script>
	<script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/libraries.js"></script>
	<script type="text/javascript" src="/sites/default/files/api/portail_d4c/lib/qtip/jquery.qtip.min.js"></script>	
	<script type="text/javascript" src="/sites/default/files/api/portail_d4c/lib/fullcalendar/moment.min.js"></script>
	<script type="text/javascript" src="/sites/default/files/api/portail_d4c/lib/fullcalendar/fullcalendar.min.js"></script>
	<script type="text/javascript" src="/sites/default/files/api/portail_d4c/lib/fullcalendar/lang/fr.js"></script>
	<script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/angular-core.js"></script>
        
	<script type="text/javascript">
		$(".d4c-content").html($(".d4c-content").html().replace(/\\\{\\\{/g,\'\{\{\').replace(/\\\}\\\}/g,\'}}\').replace(/\\\{/g,\'\{\').replace(/\\\}/g,\'}\'));
		$(".main-container").removeClass("container").removeClass("main-container").css( "margin-top", "-20px" ).css( "margin-bottom", "-45px" );
		var mod = angular.module(\'d4c.core.config\', []);

		mod.factory("config", [function() {
			return {
				ID_DATASET: "'.$id.'",
				HOST: "'.$config->client->domain.'"
			}
		}]);
	</script>
	<script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/i18n.js"></script>
	<script src="/sites/default/files/api/portail_d4c/js/supported-browsers-message.js" type="text/javascript"></script>
    <script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/angular-visu.js"></script>
	<script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/popularDataset.js"></script>

	<script>
		//$("head").append("<link href=\"/sites/default/files/api/portail_d4c/css/visualisation.css\" rel=\"stylesheet\">");
		$("head").append("<link href=\"/sites/default/files/api/portail_d4c/css/normalize.css\" rel=\"stylesheet\">");
		//$("head").append("<link href=\"/sites/default/files/api/portail_d4c/css/d4cui.css\" rel=\"stylesheet\">");
		//$("head").append("<link href=\"/sites/default/files/api/portail_d4c/css/bootstrap.min.css\" rel=\"stylesheet\">");
		$("head").append("<link href=\"/sites/default/files/api/portail_d4c/css/'.$config->client->css_file.'\" rel=\"stylesheet\">");
		$("head").append("<link href=\"/sites/default/files/api/portail_d4c/css/font-awesome.min.css\" rel=\"stylesheet\">");
		$("head").append("<link href=\"/sites/default/files/api/portail_d4c/css/style.css\" rel=\"stylesheet\">");
		$("head").append("<base href=\"/\">");
            
	</script>
	
	<script type="application/ld+json">
    {
        "@context":"http://schema.org/",
        "@type":"Dataset",
        "name":"'.$name.'",
        "description":"'.$description.'",
        "url":"'.$url.'",
        "dateModified": "'.$dateModified.'"
        ,
        "keywords": '.json_encode($keywords).'
        
    ,
        "distribution": '.json_encode($resources, JSON_UNESCAPED_SLASHES).'
		
		,
		"license": "'.$license.'"
		
	}
	</script>

	<div class="d4c-tooltip" style="display: none;"></div>
	<div class="rd-container d4cwidgets-rd-container rd-container-attachment" style="display: none; top: 670px; left: 124.9px;"></div>
	<div class="rd-container d4cwidgets-rd-container rd-container-attachment" style="display: none; top: 693.2px; left: 124.063px;"></div>
</body>',
						
			],
		);
		$element['#attached']['library'][] = 'ckan_admin/visu.angular';
		return $element;
	}

}

