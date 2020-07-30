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

        	/*var_dump($met[$i]);die;*/
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
		}

        // drupal_set_message($visu);
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
        


	    $resourcesContent = ""; 
	    $MapDetail = ""; 
	    $featureCatalog = ""; 

	    $xmlfile =false;
	    foreach($dataset["metas"]["resources"] as $key=>$value){
	    	
	    	if($value["format"] == "csw") {
	    		$xmlfile = true;
	    	$xml = file_get_contents($value['url']); 

			file_put_contents($_SERVER['DOCUMENT_ROOT']."/testxml.xml", $xml);
			if (file_exists($_SERVER['DOCUMENT_ROOT']."/testxml.xml")) {
			    $str=implode("\n",file($_SERVER['DOCUMENT_ROOT']."/testxml.xml"));

				$fp=fopen($_SERVER['DOCUMENT_ROOT']."/testxml.xml",'w');
				$str=str_replace('&','??',$str);
				$str=str_replace(':','',$str);
				fwrite($fp,$str,strlen($str));
			} else {
			    exit('Echec lors de l\'ouverture du fichier test.xml.');
			}
			break;
	    }

	    }



	    if(sizeof($dataset["metas"]["resources"]) > 0 ) {
	    	$resourcesContent = '<h4>Download and links</h4>';

	    foreach($dataset["metas"]["resources"] as $key=>$value){
	 
	 		if (strpos($value["resource_locator_protocol"], 'download') !== false or strpos($value["resource_locator_protocol"], 'DOWNLOAD') !== false) {
			    $resourcesContent .= '<div class="row" style="width:80% !impotant; padding: 10px;border: 1px solid;"><div class="col-sm-9"> <i style="margin-right: 12px; font-size: 20px" class="fa fa-download" fa-4x></i>'.$value["name"].' <br><a target="_blank" href="'.$value["url"].'">'.$value["url"].'</a></div>';
			}
			else {
				$resourcesContent .= '<div class="row" style="width:80% !impotant; 	padding: 10px;border: 1px solid;"><div class="col-sm-9"> <i style="margin-right: 12px; font-size: 20px" class="fa fa-link" fa-4x></i>'.$value["name"].'<br><a target="_blank" href="'.$value["url"].'">'.$value["url"].'</a></div>';
			}

	    	

	    if (strpos($value["resource_locator_protocol"], 'download') !== false or strpos($value["resource_locator_protocol"], 'DOWNLOAD') !== false) {
			    $resourcesContent .= '<div class="col-sm-3"><a class="btn btn-info" role="button" target="_blank" href="'.$value["url"].'" >Download</a></div>
							</div>';
			}

			else {
			    $resourcesContent .= '<div class="col-sm-3" ><a class="btn btn-info" role="button" target="_blank" href="'.$value["url"].'" >Open link</a></div>
							</div>';
			}

			}
			$resourcesContent .="<br><br><br> ";
	    }
	
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

							<div class="row">
								<div class="col-sm-8">
									<div class="row"> <div>'.$description.'</div></div>
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
									   
									<d4c-dataset-metadata-block metadata-schema="basicTemplate" values="ctx.dataset.metas" blacklist="[\'theme\',\'title\',\'description\',\'records_count\',\'source_domain\',\'source_domain_title\',\'source_domain_address\',\'source_dataset\',\'data_processed\',\'metadata_processed\',\'parent_domain\',\'geographic_area_mode\']"></d4c-dataset-metadata-block>

										'.$LinkedDataSet.'
										<!--    <div class="d4c-dataset-metadata-block">
												<div class="d4c-dataset-metadata-block__metadata">
													<div class="d4c-dataset-metadata-block__metadata-name" translate>Follow</div>
													<div class="d4c-dataset-metadata-block__metadata-value">
														<d4c-dataset-subscription preset="false"
																				  dataset-id="ctx.dataset.metas.name"
																				  logged-in="true"></d4c-dataset-subscription>
													</div>
												</div>
											</div>
										-->
									
									
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
							'.$resourcesContent.'
							'.$featureCatalog.'
							
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
								</div>

							<div class="col-sm-4">
							'.$MapDetail.'


							</div>


							</div>
							
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

