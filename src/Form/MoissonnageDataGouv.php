<?php
/**
 * @file
* Contains \Drupal\search_api_solr_admin\Form\QueryForm.
*/

namespace Drupal\ckan_admin\Form;

use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\Query;
use Drupal\ckan_admin\Utils\External;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use \PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use \PhpOffice\PhpSpreadsheet\Reader\Xls;
use \PhpOffice\PhpSpreadsheet\Writer\Csv;;
use Drupal\ckan_admin\Utils\DataSet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Drupal\ckan_admin\Utils\HelpFormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\HtmlCommand;

	

/**
 * Implements an example form.
 
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
class MoissonnageDataGouv extends HelpFormBase {
	

    
    

	/**
	 * {@inheritdoc}
	 */
	public function getFormId() {
		return 'moissonnage_data_gouv_form';
	}

	/**
	 * {@inheritdoc}
	 */
    
    function dummy_preprocess_page(&$variables) {

		if (\Drupal::service('path.matcher')->isFrontPage()) {
			$variables['#attached']['library'][] = 'ckan_admin/editMetaDataForm.form';
		}
	}
    
	public function buildForm(array $form, FormStateInterface $form_state) {
        $form = parent::buildForm($form, $form_state);
        $form['#attached']['library'][] = 'ckan_admin/MoissonnageDataGouv.form';
        $form['#attached']['html_head'][] = [
			array(
			  '#tag' => 'base',
			  '#attributes' => array(
				'href' => '/admin/config/data4citizen/datagouvForm/'
			  ),
			),
		"dd"];
        
		$config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
		// $config->set('dataForUpdateDatasets', null)->save();
	//	$config->set('dataForUpdateDatasets', null)->save();
//        
//        
//            $dataForUpdateDatasets = $config->get('dataForUpdateDatasets'); 
//            $dataForUpdateDatasets = json_decode($dataForUpdateDatasets);
//        
          //$x = DataSet::callUpdateDatasetDataGouv();
		  
		date_default_timezone_set('Europe/Paris');
		
		$organisations = $config->get('organisations');

		$datasets = $config->get('datasets');
		if($organisations == null || !isset($organisations)) {
			$organisations = array('Veuillez faire une recherche' => 'Veuillez faire une recherche');
		}
//$config->set('dataForUpdateDatasets', '')->save();
        $form['m1'] = array(
			'#markup' => '<div id="formModal"></div>',
		);          
        
		
		$form['chercher'] = array(
				'#type' => 'search',
				'#title' => $this->t('Chercher :'),
		);
        
//         $form['ckan_search'] = array(
//				'#type' => 'search',
//                '#attributes' => array('style' => 'display: none;'),
//		);
        
		$form['site_search'] = array(
				'#type' => 'search',
                '#attributes' => array('style' => 'display: none;'),
		);
        
        $this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
        $cle = $this->config->ckan->api_key;
        $optionst = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => array(
                'Content-type:application/json',
                'Content-Length: ' . strlen($jsonData),
                'Authorization:  ' . $cle,
            ),
        );
        
        
        $this->urlCkan = $this->config->ckan->url;
        $callUrlOrg = $this->urlCkan . "api/action/organization_list?all_fields=true&include_extras=true";
        $curlOrg = curl_init($callUrlOrg);

        curl_setopt_array($curlOrg, $optionst);
        $orgs = curl_exec($curlOrg);
        $orgsData = $orgs;
        curl_close($curlOrg);
        $orgs = json_decode($orgs, true);

       
        $organizationList = array();
        for ($i = 0; $i < count($orgs[result]); $i++) {
            $organizationList[$orgs[result][$i][id]] = $orgs[result][$i][display_name];
        }
        //drupal_set_message(json_encode($this->config->site));
		if(isset($this->config->site) && count($this->config->site) > 0){
			//drupal_set_message("ok ");
			$form['domaine'] = array(
				'#markup' => '<div id="domaine">'. $this->config->client->domain .'</div>',
				'#type' => 'container',
				 '#attributes' => array('style' => 'display: none;'),
			);
		}
        $form['type_rech'] = array(
            '#markup' => '',
            '#type' => 'textfield',
             '#attributes' => array('style' => 'display: none;'),
        );
        
        $form['id_org'] = array(
            '#type' => 'textarea',
            '#attributes' => array('style' => 'display: none;'),
        );
	
		$form['ids'] = array(
                '#prefix' => '<div id="idsDiv" >',
				'#type' => 'checkboxes',
				'#title' => $this->t('Choix des jeux de donnees'),
                '#suffix' => '</div>'
				//'#options' => $datasets,
		);
        
        $form['org_def'] = array(
           
           '#prefix' => '<div id="org_div" >',
           
            '#type' => 'select',
            '#title' => t('*Organisation :'),
            '#options' => $organizationList,
            //'#attributes' => array('style' => ''),
            '#suffix' => '</div>',
        );
	
		$form['search'] = array(
				'#type' => 'submit',
				'#value' => $this->t('Envoyer'),
				'#ajax' => array(
					'progress'=> array('type' => 'throbber', "message" => "Moissonnage des données en cours... Cette opération peut prendre du temps."),
					'wrapper' => "edit-error",
					'callback' => [$this, 'loading'],
				)
		);
		
		
		$form['mfilter'] = array(
			'#markup' => '<div id="filterModal"><div class="modal modal-filter" data-modal="2"><div id="filterPlace" style="overflow:scroll; height:35em; "><div class="parcel-search-widget ng-scope" ng-app="d4c.frontend">
						<div class="d4c-dataset-selection-list__records" d4c-external-context="" context="externalcontext" externalcontext-type="" externalcontext-id="" externalcontext-url="" externalcontext-parameters="" ng-init="showMapFilter=true;filteringEnabled=true">
						</div>
					 </div></div></div><div class="overlay js-overlay-modal-filter"></div></div>',
		);
        
		return $form;
	}
    
	public function submitForm(array &$form, FormStateInterface $form_state){ 
        
        $this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
        $api = new Api;
        $this->urlCkan = $this->config->ckan->url;
        $site_search =  $org_id= $form_state->getValue('site_search');
		
		###### security #######
		$idUser = "*".\Drupal::currentUser()->id()."*";
		$users = \Drupal\user\Entity\User::loadMultiple();
		$userlist = array();
		foreach($users as $user){
			$username = $user->get('name')->value;
			$uid = $user->get('uid')->value;
			$uroles = $user->getRoles();
			if($username != "" && (in_array("administrator", $uroles) || $uid == 1)){
				$userlist[] = "*".$uid."*";
			}
		}
		$userlist[] = $idUser;
		$userlist = array_unique($userlist);
		if(count($userlist) == 1){
			$userlist = array($userlist);
		}
		$security = json_encode(array("roles" => array("administrator"), "users" => $userlist));
		#######################
        
        if($site_search=='InfoCom94'){
        
            $org_id = $form_state->getValue('org_def');
           
            $optionst = array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_HTTPHEADER => array(
                    'Content-type:application/json',
                    'Content-Length: ' . strlen($jsonData),
                    'Authorization:  ' . $cle,
                    ),
            );
           
            $callUrlOrg = $this->urlCkan . "api/action/organization_show?id=".$org_id;
            $curlOrg = curl_init($callUrlOrg);
            curl_setopt_array($curlOrg, $optionst);
            $orgs = curl_exec($curlOrg);
            curl_close($curlOrg);
            $org = json_decode($orgs, true);
           
            $org_name = $org[result][title];

            $selectedDatasets = array_filter($form_state->getValue('ids'));

            foreach($selectedDatasets as &$value){
            
                $config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
                $dataForUpdateDatasets = $config->get('dataForUpdateDatasets'); 
                $dataForUpdateDatasets = json_decode($dataForUpdateDatasets);   
                $count_datas=count($dataForUpdateDatasets);


                $value= explode("|", $value);

                $query = Query::callSolrServer($value[1]."api/datasets/2.0/searchdatasetres/id=".$value[0]);
                $results = json_decode($query);
                $results = $results->result;
                $private = true;
                $label = $results->title;
                $extras = $results->extras;
				
                $ex_Ftp=false;
				$ex_dmlm=false;
				$ex_dmc=false;
                for($i= 0; $i<count($extras); $i++ ){
                    if($extras[$i]->key == 'FTP_API'){
                        $ex_Ftp=true;
                        $extras[$i]->value  == $value[1]."visualisation/?id=".$value[0]; 
                    }
					if($extras[$i]->key == 'date_moissonnage_last_modification'){
                        $ex_dmlm=true;
                    }
					if($extras[$i]->key == 'date_moissonnage_creation'){
                        $ex_dmc=true;
                    }
                }
                
                if($ex_Ftp==false){
                    $extras[count($extras)]['key'] = 'FTP_API';
                    $extras[(count($extras) - 1)]['value'] = $value[1]."visualisation/?id=".$value[0];
                }
				if($ex_dmlm==false){
                    $extras[count($extras)]['key'] = 'date_moissonnage_last_modification';
					$extras[(count($extras) - 1)]['value'] = $results->metadata_modified;
                }
				if($ex_dmc==false){
                    $extras[count($extras)]['key'] = 'date_moissonnage_creation';
					$extras[(count($extras) - 1)]['value'] = $results->metadata_created;
                }
				
				$extras[count($extras)]['key'] = 'edition_security';
				$extras[(count($extras) - 1)]['value'] = $security;
                
                $newData = [
					"name" => $results->name,
					"title" => $results->title,
					"private" => $private,
					"author" => "",
					"author_email" => "",
					"maintainer" => "",
					"maintainer_email" => "",
					"license_id" => 'notspecified',
					"notes" => $results->notes,
					"url" => '',
					"version" => "",
					"state" => "active",
					"type" => "dataset",
					"resources" => [],
					"tags" => $results->tags,
					"extras" => $extras,
					"relationships_as_object" => [],
					"relationships_as_subject" => [],
					"groups" => [],
					"owner_org" => $org_id,
				];
            
                $coll=array('0'=>'0', '1'=>'', '2'=>'');
                $NewData= $this->saveData($newData, $coll);
                $idNewData= $NewData[1];
                $NewTitle= $NewData[2];
            
           
                $dataset_conf=[
						"id_data" => $idNewData,
						"id_data_site"=>$results->id,
						"title_data"=>$NewTitle,
						"last_update" =>date('m/d/Y H:i:s', time()),
						"periodic_update" =>'',
						"site"=>$site_search,
						"site_infocom"=>$value[1]
				];
           
                $controlEx =false;
            
                foreach($dataForUpdateDatasets as $key => $value){
					if($value->id_org == $org_id){
						array_push($dataForUpdateDatasets[$key]->datasets, $dataset_conf);
                   
						//drupal_set_message('<pre>'. print_r($dataForUpdateDatasets[$key]->datasets, true) .'</pre>'); 
						$controlEx = true;
						break;
					}
                }
				
            
                if($controlEx == false){
//                 array_push($dataForUpdateDatasets[$count_datas][datasets], $dataset_conf );
					$dataForUpdateDatasets[$count_datas]=[
						"id_org" =>$org_id,
						"name_org" =>$org_name,
						"datasets" =>[$dataset_conf],
					];
				}
          
                $config->set('dataForUpdateDatasets', json_encode($dataForUpdateDatasets))->save();
            
                foreach($results->resources as &$res){
            
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
                  
                
						if($res->format == 'csv' || $res->format == 'CSV') {
							  
							$filepathN = explode(".",$filepathN)[0].'.csv';
							$url_res = $url_res.''.$filepathN;
							
							   // read into array
							   //$arr = file('/home/user-client/drupal-d4c'.$filepath);
							$arr = file($res->url);
							$label = utf8_decode($arr[0]);
							$label = $this->nettoyage($label);  
							
							// edit first line
							$arr[0] = $label;
							
							// write back to file
							file_put_contents($root.''. $filepathN, implode($arr));
							
							//$query = DataSet::createResource($idNewData,$url_res,$res->description,$res->name, $res->format,'false');
							
							$resources = [
								"package_id" => $idNewData,
								"url" => $url_res,
								"description" => $res->description,
								"name" =>$res->name,
								"format"=>$res->format
							];

							$callUrluptres = $this->urlCkan . "/api/action/resource_create";
							$return = $api->updateRequest($callUrluptres, $resources, "POST");
							$this->renderResourceLog($resources["name"], $return);
						}
					}
					else{
						$url_res = $res->url;
						//$query = DataSet::createResource($idNewData,$url_res,$res->description,$res->name, $res->format,'false');
						$resources = [
                            "package_id" => $idNewData,
                            "url" => $url_res,
                            "description" => $res->description,
                            "name" =>$res->name,
                            "format"=>$res->format
                        ];

						$callUrluptres = $this->urlCkan . "/api/action/resource_create";
						$return = $api->updateRequest($callUrluptres, $resources, "POST");
						$this->renderResourceLog($resources["name"], $return);
					} 
            
				}
				sleep(20);
				$api->calculateVisualisations($idNewData);
			}
		}
        else if($site_search=='d4c'){
        
			$org_id= $form_state->getValue('org_def');
           
			$optionst = array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_HTTPHEADER => array(
					'Content-type:application/json',
					'Content-Length: ' . strlen($jsonData),
					'Authorization:  ' . $cle,
				),
			);
           
            $callUrlOrg = $this->urlCkan . "api/action/organization_show?id=".$org_id;
            $curlOrg = curl_init($callUrlOrg);
            curl_setopt_array($curlOrg, $optionst);
            $orgs = curl_exec($curlOrg);
            curl_close($curlOrg);
            $org = json_decode($orgs, true);
            $org_name = $org[result][title];
            $selectedDatasets = array_filter($form_state->getValue('ids'));

            foreach($selectedDatasets as &$value){
            
                $config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
                $dataForUpdateDatasets = $config->get('dataForUpdateDatasets'); 
                $dataForUpdateDatasets = json_decode($dataForUpdateDatasets);
                $count_datas=count($dataForUpdateDatasets);
                
                $jsonValue = json_decode($value, true);
				if(substr($jsonValue["url"], -1) === "/"){
					$jsonValue["url"] = substr($jsonValue["url"], 0, -1);
				}
                $query = Query::callSolrServer($jsonValue["url"]."/api/datasets/2.0/searchdatasetres/id=".$jsonValue["id"]);
                $results = json_decode($query);
                $results = $results->result;
                $private = true;
                $label = $results->title;
                $extras = $results->extras;
                $ex_Ftp=false;
				$ex_dmlm=false;
				$ex_dmc=false;
                for($i= 0; $i<count($extras); $i++ ){
                    if($extras[$i]->key == 'FTP_API'){
                        $ex_Ftp=true;
                        $extras[$i]->value  == $jsonValue["url"]."/visualisation/?id=".$jsonValue["id"]; 
                    }
					if($extras[$i]->key == 'date_moissonnage_last_modification'){
                        $ex_dmlm=true;
                    }
					if($extras[$i]->key == 'date_moissonnage_creation'){
                        $ex_dmc=true;
                    }
                }
                
                if($ex_Ftp==false){
                    $extras[count($extras)]['key'] = 'FTP_API';
                    $extras[(count($extras) - 1)]['value'] = $jsonValue["url"]."/visualisation/?id=".$jsonValue["id"];
                }
				if($ex_dmlm==false){
                    $extras[count($extras)]['key'] = 'date_moissonnage_last_modification';
					$extras[(count($extras) - 1)]['value'] = $results->metadata_modified;
                }
				if($ex_dmc==false){
                    $extras[count($extras)]['key'] = 'date_moissonnage_creation';
					$extras[(count($extras) - 1)]['value'] = $results->metadata_created;
                }				
				
				$extras[count($extras)]['key'] = 'edition_security';
				$extras[(count($extras) - 1)]['value'] = $security;
           
                $newData = [
					"name" => $results->name,
					"title" => $results->title,
					"private" => $private,
					"author" => "",
					"author_email" => "",
					"maintainer" => "",
					"maintainer_email" => "",
					"license_id" => 'notspecified',
					"notes" => $results->notes,
					"url" => '',
					"version" => "",
					"state" => "active",
					"type" => "dataset",
					"resources" => [],
					"tags" => $results->tags,
					"extras" => $extras,
					"relationships_as_object" => [],
					"relationships_as_subject" => [],
					"groups" => [],
					"owner_org" => $org_id,
				];
                $coll=array('0'=>'0', '1'=>'', '2'=>'');
                $NewData= $this->saveData($newData, $coll);
                $idNewData= $NewData[1];
                $NewTitle= $NewData[2];
                $dataset_conf=[
					"id_data" => $idNewData,
					"id_data_site"=>$results->id,
					"title_data"=>$NewTitle,
					"last_update" =>date('m/d/Y H:i:s', time()),
					"periodic_update" =>'',
					"site"=>$site_search,
					"site_infocom"=>$jsonValue["url"],
					"parameters" => $jsonValue["params"]
				];
           
                $controlEx =false;
            
                foreach($dataForUpdateDatasets as $key => $value){
					if($value->id_org == $org_id){
						array_push($dataForUpdateDatasets[$key]->datasets, $dataset_conf);
						$controlEx = true;
						break;
					}
                
				}
            
                if($controlEx ==false){
//                 	array_push($dataForUpdateDatasets[$count_datas][datasets], $dataset_conf );
					$dataForUpdateDatasets[$count_datas]=[
						"id_org" =>$org_id,
						"name_org" =>$org_name,
						"datasets" =>[$dataset_conf],
					];
				}
          
                $config->set('dataForUpdateDatasets', json_encode($dataForUpdateDatasets))->save();
            
                foreach($results->resources as &$res){
            
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
                  
						if($res->format == 'csv' || $res->format == 'CSV') {
                  
							/*$filepathN = explode(".",$filepathN)[0].'.csv';
							$url_res = $url_res.''.$filepathN;
                
							// read into array
							//$arr = file('/home/user-client/drupal-d4c'.$filepath);
							$arr = file($res->url);
							$label = utf8_decode($arr[0]);
							$label = $this->nettoyage($label);  
                
							// edit first line
							$arr[0] = $label;
                
							// write back to file
							file_put_contents($root.''. $filepathN, implode($arr));
                
                
							//$query = DataSet::createResource($idNewData,$url_res,$res->description,$res->name, $res->format,'false');
                */
							$jsonValue["params"]["resource_id"] = $res->id;
							$api_ext = new External();
							$download = $api_ext->getDownloadFromSource("d4c", $jsonValue["url"], $jsonValue["id"], http_build_query($jsonValue["params"]));
							$url = $download["url"];
							$fileName = $download["name"];
							
							$resources = [
								"package_id" => $idNewData,
								"url" => $url,
								"description" => $res->description,
								"name" =>$fileName,
								"format"=>$res->format
							];

							$callUrluptres = $this->urlCkan . "/api/action/resource_create";
							$return = $api->updateRequest($callUrluptres, $resources, "POST");
							$this->renderResourceLog($resources["name"], $return);
						}            
					} else {
						$url_res = $res->url;
						//$query = DataSet::createResource($idNewData,$url_res,$res->description,$res->name, $res->format,'false');
                  
						$resources = [
                            "package_id" => $idNewData,
                            "url" => $url_res,
                            "description" => $res->description,
                            "name" =>$res->name,
                            "format"=>$res->format
                        ];

						$callUrluptres = $this->urlCkan . "/api/action/resource_create";
						$return = $api->updateRequest($callUrluptres, $resources, "POST");
						$this->renderResourceLog($resources["name"], $return);
					} 
				}
				sleep(20);
				$api->calculateVisualisations($idNewData);
            }
		}         
        else if($site_search=='Data_Gouv_fr'){
            
            if($form_state->getValue('type_rech')=='organizations'){
				// $selected_org = $form_state->getValue('selected_org');
				$selected_org = $form_state->getValue('id_org');
				$cle = $this->config->ckan->api_key;
				$optionst = array(
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_CUSTOMREQUEST => "POST",
					CURLOPT_HTTPHEADER => array(
						'Content-type:application/json',
						'Content-Length: ' . strlen($jsonData),
						'Authorization:  ' . $cle,
					),
				);
        
				$callUrlOrg = $this->urlCkan . "api/action/organization_show?id=".$selected_org;
				$curlOrg = curl_init($callUrlOrg);
				curl_setopt_array($curlOrg, $optionst);
				$orgs = curl_exec($curlOrg);
				curl_close($curlOrg);
				$org = json_decode($orgs, true);
       
				$org_id='';
				$org_name='';
        
				if($org[success]==true){
					$org_id=$org[result][id];
					$org_name = $org[result][title];
						  
					$context =[
						 
						'id'=>$org_id,
						'state'=>'active',//'active'/ 'deleted' /draft
					];
					
					$callUrlUpdate = $this->urlCkan . "/api/action/organization_update";
					$return = $api->updateRequest($callUrlUpdate, $context, "POST");
					$return = json_decode($return, true);
					
				}
				else{   
					$org = Query::callSolrServer("https://www.data.gouv.fr/api/1/organizations/".$selected_org."/");
					$org = json_decode($org);
					
					$extras=array();
					array_push($extras,['key'=>'private', 'value'=>'false']);
            
					$org_id = $org->id;
					$org_name = $org->slug;
					$context =[
						'name'=>$org->slug,
						'id'=>$org->id,
						'title'=>$org->slug,
						'description'=>$org->description,
						'image_url'=>$org->logo,
						'state'=>'active',//'active'/ 'deleted' /draft
						'extras'=>$extras,
						'packages'=>array(),
						'users'=>array(),  
					];
            
					$callUrl = $this->urlCkan . "/api/action/organization_create";
        
					$return = $api->updateRequest($callUrl, $context, "POST");
            
					$return = json_decode($return, true);    
					$org_id=$return[result][id];   
				}
			}
            else{
				$org_id= $form_state->getValue('org_def');
           
				$optionst = array(
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_CUSTOMREQUEST => "POST",
					CURLOPT_HTTPHEADER => array(
						'Content-type:application/json',
						'Content-Length: ' . strlen($jsonData),
						'Authorization:  ' . $cle,
					),
				);
           
				$callUrlOrg = $this->urlCkan . "api/action/organization_show?id=".$org_id;
				$curlOrg = curl_init($callUrlOrg);
				curl_setopt_array($curlOrg, $optionst);
				$orgs = curl_exec($curlOrg);
				curl_close($curlOrg);
				$org = json_decode($orgs, true);
				$org_name = $org[result][title];
           
            }
        
            $array_datasets_for_config=array();
        
            $selectedDatasets = array_filter($form_state->getValue('ids'));
        
            foreach($selectedDatasets as &$value){
				
				$config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
				$dataForUpdateDatasets = $config->get('dataForUpdateDatasets'); 
				$dataForUpdateDatasets = json_decode($dataForUpdateDatasets);
				$count_datas=count($dataForUpdateDatasets);
				
				$query = Query::callSolrServer("https://www.data.gouv.fr/api/1/datasets/".$value."/");
				$results = json_decode($query);
				$private = true;
				
				$label = $results->slug;
				
				// drupal_set_message(print_r($query, true));
				
				$label = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $label)));
				$label = str_replace(" ", "_", $label);
				$label = strtolower($label);
				$label = htmlentities($label, ENT_NOQUOTES, $charset);
				$label = preg_replace('#\&([A-za-z])(?:acute|cedil|circ|grave|ring|tilde|uml)\;#', '\1', $label);
				$label = preg_replace('#\&([A-za-z]{2})(?:lig)\;#', '\1', $label); // pour les ligatures e.g. '&oelig;'
				$label = preg_replace('#\&[^;]+\;#', '', $label); // supprime les autres caractères
				$label = preg_replace('@[^a-zA-Z0-9_]@','',$label);
				$urlRes = $this->urlCkan ."/dataset/".$label;
				$tagsData = array();
				if ($results->tags == '' || count($results->tags)==0 || !$results->tags) {
					$tagsData = [];
				} 
				else {
					$tags = $results->tags;
					for ($j = 0; $j < count($tags); $j++) {
						if($tags[$j]!=''){
							$val = $this->nettoyage($tags[$j]);
							array_push($tagsData, ["vocabulary_id" => null, "state" => "active", "display_name" => $val, "name" => $val]);
						}
					}
				}
				
				$extras = array();
				
				$extras[count($extras)]['key'] = 'LinkedDataSet';
				$extras[(count($extras) - 1)]['value'] = '';     
				
				$extras[count($extras)]['key'] = 'theme';
				$extras[(count($extras) - 1)]['value'] = 'default';
						
				$extras[count($extras)]['key'] = 'label_theme';
				$extras[(count($extras) - 1)]['value'] = 'Default';
						
				$extras[count($extras)]['key'] = 'type_map';
				$extras[(count($extras) - 1)]['value'] = 'osm';
				
				$extras[count($extras)]['key'] = 'FTP_API';
				$extras[(count($extras) - 1)]['value'] = 'https://www.data.gouv.fr/fr/datasets/'.$value.'/';
				
				$extras[count($extras)]['key'] = 'date_moissonnage_last_modification';
				$extras[(count($extras) - 1)]['value'] = $results->last_modified;
				
				$extras[count($extras)]['key'] = 'date_moissonnage_creation';
				$extras[(count($extras) - 1)]['value'] = $results->created_at;
				
				$extras[count($extras)]['key'] = 'edition_security';
				$extras[(count($extras) - 1)]['value'] = $security;
				
				$description = $results->description;
				
				$description = preg_replace("/\\n/", "<br>", $description);
				$description = preg_replace("/(?:http(s)?:\/\/)?[\w.-]+(?:\.[\w\.-]+)+[\w\-\._~:\/?#[\]@!\$&'\*\+,;=.]+/", "<a href='$0' target='_blank'>$0</a>", $description);
				
				$newData = ["name" => substr($label, 0, 95),
					"title" => $results->title,
					"private" => $private,
					"author" => "",
					"author_email" => "",
					"maintainer" => "",
					"maintainer_email" => "",
					"license_id" => 'notspecified',
					"notes" => $description,
					"url" => $urlRes,
					"version" => "",
					"state" => "active",
					"type" => "dataset",
					"resources" => [],
					"tags" => $tagsData,
					"extras" => $extras,
					"relationships_as_object" => [],
					"relationships_as_subject" => [],
					"groups" => [],
					"owner_org" => $org_id,
					//"metadata_created"=>$results->created_at,
					//"metadata_modified"=>$results->last_modified,
				];
				$coll=array('0'=>'0', '1'=>'', '2'=>'');
				$NewData= $this->saveData($newData, $coll);
				$idNewData= $NewData[1];
				$NewTitle= $NewData[2];
				$dataset_conf=[
					"id_data" => $idNewData,
					"id_data_site"=>$results->id,
					"title_data"=>$NewTitle,
					"last_update" =>date('m/d/Y H:i:s', time()),
					"periodic_update" =>'',
					"site"=>$site_search,
				];
				$controlEx =false;
				
				foreach($dataForUpdateDatasets as $key => $value){
					
					if($value->id_org == $org_id){
					   
						array_push($dataForUpdateDatasets[$key]->datasets, $dataset_conf);
						$controlEx = true;
						//drupal_set_message('<tagFinal>'.print_r($dataset_conf,true).'<tagFinal>');
						break;
					}
				}
				
				if($controlEx ==false){
					//                 array_push($dataForUpdateDatasets[$count_datas][datasets], $dataset_conf );
					$dataForUpdateDatasets[$count_datas]=[
						"id_org" =>$org_id,
						"name_org" =>$org_name,
						"datasets" =>[$dataset_conf],
					];
				}
			
				$config->set('dataForUpdateDatasets', json_encode($dataForUpdateDatasets))->save();
				
				$add_tres=false;    
				foreach($results->resources as &$res){     
					if($_SERVER['HTTP_HOST']=='192.168.2.217'){
						$root='/home/bpm/drupal-8.6.15/sites/default/files/dataset/';
					}
					else{
						$root='/home/user-client/drupal-d4c/sites/default/files/dataset/';
					}
				   
					$host = $_SERVER['HTTP_HOST'];
					
					/////////////////////resources////////////
					if($res->format == 'CSV' || $res->format == 'XLS' || $res->format == 'XLSX' || $res->format == 'csv' || $res->format == 'xls' || $res->format == 'xlsx'){
						$add_tres=true;
					  
						$filepathN = $res->url;
						$filepathN = explode('/',$filepathN);
						$filepathN = $filepathN[count($filepathN)-1];
						$filepathN = explode('.',$filepathN)[0]; 
						$filepathN =urldecode($filepathN);  
						$filepathN = strtolower($filepathN);
					  
						if($_SERVER['HTTP_HOST']=='192.168.2.217'){
							$url_res = 'http://'.$host.'/sites/default/files/dataset/';
						}
						else{
							$url_res = 'https://'.$host.'/sites/default/files/dataset/';
						}        
					  
						if( $res->format == 'XLS' || $res->format == 'XLSX'  || $res->format == 'xls' || $res->format == 'xlsx'){
						   
							$title_f= $res->title.'_xls';
							switch ($res->format) {
								case 'XLS':
									$filepathN = explode(".",$filepathN)[0].'.XLS';
									$filepathDell =  $filepathN;
									$reader = new Xls();
									break;
								case 'XLSX':
									$filepathN = explode(".",$filepathN)[0].'.XLSX';
									$filepathDell =  $filepathN;
									$reader = new Xlsx();
									break;
								case 'xls':
									$filepathN = explode(".",$filepathN)[0].'.xls';
									$filepathDell =  $filepathN;
									$reader = new Xls();
									break;
								case 'xlsx':
									$filepathN = explode(".",$filepathN)[0].'.xlsx';
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
							$label = utf8_decode($arr[0]);
							 
							$label = $this->nettoyage($label);  
							// edit first line
							$arr[0] = $label;
							
							// write back to file
							file_put_contents($root.'xls_'.$fileName, implode($arr));
						
							//$query = DataSet::createResource($idNewData,$url_res,$res->description, $title_f, 'csv','false');
							
							$resources = [   "package_id" => $idNewData,
								"url" => $url_res,
								"description" => $res->description,
								"name" =>$title_f,
								"format"=>'csv'
							];

							$callUrluptres = $this->urlCkan . "/api/action/resource_create";
							$return = $api->updateRequest($callUrluptres, $resources, "POST");    
							$this->renderResourceLog($resources["name"], $return);
						}
				
						if($res->format == 'csv' || $res->format == 'CSV') {
						  
							$filepathN = explode(".",$filepathN)[0].'.csv';
							$url_res = $url_res.''.$filepathN;
						
							// read into array
							//$arr = file('/home/user-client/drupal-d4c'.$filepath);
							$arr = file($res->url);
							$label = utf8_decode($arr[0]);
							$label = $this->nettoyage($label);  
						
							// edit first line
							$arr[0] = $label;
						
						
							// write back to file
							file_put_contents($root.''. $filepathN, implode($arr));
						
							//$query = DataSet::createResource($idNewData,$url_res,$res->description,$res->title, $res->format,'false'); 
						
							$resources = [
								"package_id" => $idNewData,
								"url" => $url_res,
								"description" => $res->description,
								"name" =>$res->title,
								"format"=>$res->format
							];
						
							//drupal_set_message("RES:". print_r($resources,true) );

							$callUrluptres = $this->urlCkan . "/api/action/resource_create";
							$return = $api->updateRequest($callUrluptres, $resources, "POST");
							$this->renderResourceLog($resources["name"], $return);
						}
					}
					else{
						$url_res = $res->url;
						//$query = DataSet::createResource($idNewData,$url_res,$res->description,$res->title, $res->format,'false');
					  
						$resources = [
							"package_id" => $idNewData,
							"url" => $url_res,
							"description" => $res->description,
							"name" =>$res->title,
							"format"=>$res->format
						];

						$callUrluptres = $this->urlCkan . "/api/action/resource_create";
						$return = $api->updateRequest($callUrluptres, $resources, "POST");
						$this->renderResourceLog($resources["name"], $return);
					}              
				}
				
				if($results->metrics->reuses > 0){
					$query = Query::callSolrServer("https://www.data.gouv.fr/api/1/reuses/?page_size=10000&dataset=".$results->id);
					$reuses = json_decode($query, true);
					$reuses = $reuses["data"];
					
					foreach($reuses as $reu){
						$reuse = array();
						$reuse["dataset_id"] = $idNewData;
						$reuse["dataset_title"] = $results->title;
						$reuse["name"] = $reu["slug"];
						$reuse["title"] = $reu["title"];
						$reuse["description"] = $reu["description"];
						$reuse["author_name"] = $reu["organization"]["name"];
						$reuse["author_url"] = $reu["organization"]["page"];
						$reuse["author_email"] = null;
						$reuse["url"] = $reu["url"];
						$reuse["image"] = $reu["image"];
						$reuse["date"] = $reu["created_at"];
						$reuse["status"] = 1;
						$reuse["type"] = $reu["type"];
						
						$api->addReuse($reuse);
					}
				}
				sleep(20);
				$api->calculateVisualisations($idNewData);
			}
        }
        else if($site_search=='Public_OpenDataSoft_com'){
            
			$org_id= $form_state->getValue('org_def');
           
			$optionst = array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_HTTPHEADER => array(
					'Content-type:application/json',
					'Content-Length: ' . strlen($jsonData),
					'Authorization:  ' . $cle,
				),
			);
           
            $callUrlOrg = $this->urlCkan . "api/action/organization_show?id=".$org_id;
            $curlOrg = curl_init($callUrlOrg);
            curl_setopt_array($curlOrg, $optionst);
            $orgs = curl_exec($curlOrg);
            curl_close($curlOrg);
            $org = json_decode($orgs, true);
            $org_name = $org[result][title];
            $selectedDatasets = array_filter($form_state->getValue('ids'));
       
            foreach($selectedDatasets as &$value){
            
				$config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
				$dataForUpdateDatasets = $config->get('dataForUpdateDatasets'); 
				$dataForUpdateDatasets = json_decode($dataForUpdateDatasets);
				$count_datas=count($dataForUpdateDatasets);

				$jsonValue = json_decode($value, true);
				
				$query = Query::callSolrServer("https://public.opendatasoft.com/api/datasets/1.0/".$jsonValue["id"].'/');
				$query = json_decode($query);    

				$results = $query->metas;

				$private = true;

				$extras = array();
				$extras[count($extras)]['key'] = 'LinkedDataSet';
				$extras[(count($extras) - 1)]['value'] = '';
				
				$extras[count($extras)]['key'] = 'theme';
				$extras[(count($extras) - 1)]['value'] = 'default';
				
				$extras[count($extras)]['key'] = 'label_theme';
				$extras[(count($extras) - 1)]['value'] = 'Default';
				
				//$extras[count($extras)]['key'] = 'type_map';
				//$extras[(count($extras) - 1)]['value'] = 'osm';
		
				$extras[count($extras)]['key'] = 'FTP_API';
				$extras[(count($extras) - 1)]['value'] = 'https://public.opendatasoft.com/explore/dataset/'.$jsonValue["id"].'/';
				
				$extras[count($extras)]['key'] = 'date_moissonnage_last_modification';
				$extras[(count($extras) - 1)]['value'] = $results->metadata_processed;
				
				$extras[count($extras)]['key'] = 'date_moissonnage_creation';
				$extras[(count($extras) - 1)]['value'] = $results->modified;
				
				$extras[count($extras)]['key'] = 'edition_security';
				$extras[(count($extras) - 1)]['value'] = $security;
			
				$tagsData = array();
                
				if ($results->keyword == '' || count($results->keyword)==0 || !$results->keyword) {
					$tagsData = [];
				} 
				else {
					$tags = $results->keyword;
					for ($j = 0; $j < count($tags); $j++) {
						if($tags[$j]!=''){
							$val = $this->nettoyage($tags[$j]);
							array_push($tagsData, ["vocabulary_id" => null, "state" => "active", "display_name" => $val, "name" => $val]);
						}
					}  
				}    
 
				$newData = [
					"name" =>$query->datasetid,
					"title" => $results->title,
					"private" => $private,
					"author" => "",
					"author_email" => "",
					"maintainer" => "",
					"maintainer_email" => "",
					"license_id" => 'notspecified',
					"notes" => $results->description,
					"url" => '',
					"version" => "",
					"state" => "active",
					"type" => "dataset",
					"resources" => [],
					"tags" => $tagsData,
					"extras" => $extras,
					"relationships_as_object" => [],
					"relationships_as_subject" => [],
					"groups" => [],
					"owner_org" => $org_id,
				];
		
				$coll=array('0'=>'0', '1'=>'', '2'=>'');
				$NewData= $this->saveData($newData, $coll);
				$idNewData= $NewData[1];
				$NewTitle= $NewData[2];
				$dataset_conf=[
					"id_data" => $idNewData,
					"id_data_site"=>$query->datasetid,
					"title_data"=>$NewTitle,
					"last_update" =>date('m/d/Y H:i:s', time()),
					"periodic_update" =>'',
					"site"=>$site_search,
					"site_infocom"=>'',
					"parameters" => $jsonValue["params"]
				];
				
				error_log($dataset_conf);
				$controlEx =false;
		
				foreach($dataForUpdateDatasets as $key => $value){

					if($value->id_org == $org_id){

						array_push($dataForUpdateDatasets[$key]->datasets, $dataset_conf);
						$controlEx = true;
						break;
					}

				}

				if($controlEx ==false){
					//array_push($dataForUpdateDatasets[$count_datas][datasets], $dataset_conf );
					$dataForUpdateDatasets[$count_datas]=[
						"id_org" =>$org_id,
						"name_org" =>$org_name,
						"datasets" =>[$dataset_conf],
					];
				}
          
				$config->set('dataForUpdateDatasets', json_encode($dataForUpdateDatasets))->save();
            
                /*
				$fileName = str_replace('-', '_', $query->datasetid);
                
				if($_SERVER['HTTP_HOST']=='192.168.2.217'){
					$root='/home/bpm/drupal-8.6.15/sites/default/files/dataset/'.$fileName.'.csv';
					$url = 'http://'.$_SERVER['HTTP_HOST'].'/sites/default/files/dataset/'.$fileName.'.csv';
				}
				else{
					$root='/home/user-client/drupal-d4c/sites/default/files/dataset/'.$fileName.'.csv';
					$url = 'https://'.$_SERVER['HTTP_HOST'].'/sites/default/files/dataset/'.$fileName.'.csv';
				}
                
				$url_resource = 'https://public.opendatasoft.com/explore/dataset/'.$query->datasetid.'/download/?format=csv&timezone=Europe/Madrid&use_labels_for_header=true';
         
				// read into array
				$arr = file($url_resource);
				$label = utf8_decode($arr[0]);
				$label = $this->nettoyage($label);  
				$arr[0] = $label;
				// write back to file
				file_put_contents($root, implode($arr));
				*/
				$api_ext = new External();
				$download = $api_ext->getDownloadFromSource("ods", "https://public.opendatasoft.com", $jsonValue["id"], http_build_query($jsonValue["params"]));
				$url = $download["url"];
				$fileName = $download["name"];

				$resources = [     
					"package_id" => $idNewData,
					"url" => $url,
					"description" => '',
					"name" =>$fileName,
					"format"=>'csv'
				];
				$callUrluptres = $this->urlCkan . "/api/action/resource_create";
				$return = $api->updateRequest($callUrluptres, $resources, "POST");
				$this->renderResourceLog($resources["name"], $return);
				sleep(20);
				$api->calculateVisualisations($idNewData);
			}
        }
        else if($site_search=='odsall'){
           
			$org_id= $form_state->getValue('org_def');
			$optionst = array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_HTTPHEADER => array(
					'Content-type:application/json',
					'Content-Length: ' . strlen($jsonData),
					'Authorization:  ' . $cle,
				),
			);
           
            $callUrlOrg = $this->urlCkan . "api/action/organization_show?id=".$org_id;
            $curlOrg = curl_init($callUrlOrg);
            curl_setopt_array($curlOrg, $optionst);
            $orgs = curl_exec($curlOrg);
            curl_close($curlOrg);
            $org = json_decode($orgs, true);
            $org_name = $org[result][title];

            $selectedDatasets = array_filter($form_state->getValue('ids'));

            foreach($selectedDatasets as &$value){
                
                $config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
                $dataForUpdateDatasets = $config->get('dataForUpdateDatasets'); 
                $dataForUpdateDatasets = json_decode($dataForUpdateDatasets);  
                $count_datas=count($dataForUpdateDatasets);
				
				$jsonValue = json_decode($value, true);

                //$value= explode("|", $value);   
                //$site_search_url = $value[1];
                $site_search_url = $jsonValue["url"];
                $query = Query::callSolrServer($site_search_url."/api/datasets/1.0/".$jsonValue["id"].'/');
                $query = json_decode($query);    
                $results = $query->metas;
                $private = true;
           
                $extras = array();
                $extras[count($extras)]['key'] = 'LinkedDataSet';
                $extras[(count($extras) - 1)]['value'] = '';
                    
                $extras[count($extras)]['key'] = 'theme';
                $extras[(count($extras) - 1)]['value'] = 'default';
                    
                $extras[count($extras)]['key'] = 'label_theme';
                $extras[(count($extras) - 1)]['value'] = 'Default';
				
            
                $extras[count($extras)]['key'] = 'FTP_API';
                $extras[(count($extras) - 1)]['value'] = 'https://public.opendatasoft.com/explore/dataset/'.$jsonValue["id"].'/';
                
                $extras[count($extras)]['key'] = 'date_moissonnage_last_modification';
				$extras[(count($extras) - 1)]['value'] = $results->metadata_processed;
				
				$extras[count($extras)]['key'] = 'date_moissonnage_creation';
				$extras[(count($extras) - 1)]['value'] = $results->modified;
				
				$extras[count($extras)]['key'] = 'edition_security';
				$extras[(count($extras) - 1)]['value'] = $security;
				
                $tagsData = array();
                if ($results->keyword == '' || count($results->keyword)==0 || !$results->keyword) {
					$tagsData = [];
				} 
                else {
					$tags = $results->keyword;
					for ($j = 0; $j < count($tags); $j++) {
						if($tags[$j]!=''){
							$val = $this->nettoyage($tags[$j]);
							array_push($tagsData, ["vocabulary_id" => null, "state" => "active", "display_name" => $val, "name" => $val]);
						}
					}             
                }    

                $newData = [
					"name" =>$query->datasetid,
					"title" => $results->title,
					"private" => $private,
					"author" => "",
					"author_email" => "",
					"maintainer" => "",
					"maintainer_email" => "",
					"license_id" => 'notspecified',
					"notes" => $results->description,
					"url" => '',
					"version" => "",
					"state" => "active",
					"type" => "dataset",
					"resources" => [],
					"tags" => $tagsData,
					"extras" => $extras,
					"relationships_as_object" => [],
					"relationships_as_subject" => [],
					"groups" => [],
					"owner_org" => $org_id,
				];
            
                $coll=array('0'=>'0', '1'=>'', '2'=>'');
                $NewData= $this->saveData($newData, $coll);
                $idNewData= $NewData[1];
                $NewTitle= $NewData[2];
            
                $dataset_conf=[
					"id_data" => $idNewData,
					"id_data_site"=>$query->datasetid,
					"title_data"=>$NewTitle,
					"last_update" =>date('m/d/Y H:i:s', time()),
					"periodic_update" =>'',
					"site"=>$site_search,
					"site_infocom"=>$site_search_url,
					"parameters" => $jsonValue["params"]
				];
           
                $controlEx =false;
				
                foreach($dataForUpdateDatasets as $key => $value){
					if($value->id_org == $org_id){
						array_push($dataForUpdateDatasets[$key]->datasets, $dataset_conf);
						$controlEx = true;
						break;
					}
                }

                if($controlEx ==false){
                    //                 array_push($dataForUpdateDatasets[$count_datas][datasets], $dataset_conf );
					$dataForUpdateDatasets[$count_datas]=[
                        "id_org" =>$org_id,
                        "name_org" =>$org_name,
                        "datasets" =>[$dataset_conf],
					];
                }

                $config->set('dataForUpdateDatasets', json_encode($dataForUpdateDatasets))->save();
            
               /* $fileName = str_replace('-', '_', $query->datasetid);
                
                if($_SERVER['HTTP_HOST']=='192.168.2.217'){
                    $root='/home/bpm/drupal-8.6.15/sites/default/files/dataset/'.$fileName.'.csv';
                    $url = 'http://'.$_SERVER['HTTP_HOST'].'/sites/default/files/dataset/'.$fileName.'.csv';
                }
                else{
                    $root='/home/user-client/drupal-d4c/sites/default/files/dataset/'.$fileName.'.csv';
                    $url = 'https://'.$_SERVER['HTTP_HOST'].'/sites/default/files/dataset/'.$fileName.'.csv';
                }
  
                $url_resource = 'https://'.$site_search_url.'/explore/dataset/'.$query->datasetid.'/download/?format=csv&timezone=Europe/Madrid&use_labels_for_header=true';
         
                // read into array
                $arr = file($url_resource);
                $label = utf8_decode($arr[0]);
                $label = $this->nettoyage($label);  
                $arr[0] = $label;  
                // write back to file
                file_put_contents($root, implode($arr));
*/

				$api_ext = new External();
				$download = $api_ext->getDownloadFromSource("ods", $site_search_url, $jsonValue["id"], http_build_query($jsonValue["params"]));
				$url = $download["url"];
				$fileName = $download["name"];
				
                $resources = [     
					"package_id" => $idNewData,
					"url" => $url,
					"description" => '',
					"name" =>$fileName,
					"format"=>'csv'
				];

                $callUrluptres = $this->urlCkan . "/api/action/resource_create";
                $return = $api->updateRequest($callUrluptres, $resources, "POST");
				$this->renderResourceLog($resources["name"], $return);
				sleep(20);
				$api->calculateVisualisations($idNewData);
			}
        }        
        else if($site_search=='socrata'){
        
			$org_id= $form_state->getValue('org_def');
           
			$optionst = array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_HTTPHEADER => array(
					'Content-type:application/json',
					'Content-Length: ' . strlen($jsonData),
					'Authorization:  ' . $cle,
				),
			);
           
			$callUrlOrg = $this->urlCkan . "api/action/organization_show?id=".$org_id;
			$curlOrg = curl_init($callUrlOrg);
			curl_setopt_array($curlOrg, $optionst);
			$orgs = curl_exec($curlOrg);
			curl_close($curlOrg);
			$org = json_decode($orgs, true);
			$org_name = $org[result][title]; 
			$selectedDatasets = array_filter($form_state->getValue('ids'));

			foreach($selectedDatasets as &$value){
			  
				$config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
				$dataForUpdateDatasets = $config->get('dataForUpdateDatasets'); 
				$dataForUpdateDatasets = json_decode($dataForUpdateDatasets);   
				$count_datas=count($dataForUpdateDatasets); 
			 
				$value= explode("|", $value);  
				//$query = Query::callSolrServer($value[1]."/api/views/".$value[0].".json");
				$query = Query::callSolrServer($value[1]."/api/views/metadata/v1/".$value[0].".json");
				$results = json_decode($query);
				$private = true;
			   
					//$label = $results->title;
					
				$extras = array();
				$extras[count($extras)]['key'] = 'LinkedDataSet';
				$extras[(count($extras) - 1)]['value'] = '';
						
				$extras[count($extras)]['key'] = 'theme';
				$extras[(count($extras) - 1)]['value'] = 'default';
						
				$extras[count($extras)]['key'] = 'label_theme';
				$extras[(count($extras) - 1)]['value'] = 'Default';
						
				$extras[count($extras)]['key'] = 'type_map';
				$extras[(count($extras) - 1)]['value'] = 'osm';
				
				$extras[count($extras)]['key'] = 'FTP_API';
				$extras[(count($extras) - 1)]['value'] = $value[1].'/d/'.$value[0];
				
                $extras[count($extras)]['key'] = 'date_moissonnage_last_modification';
				$extras[(count($extras) - 1)]['value'] = $results->updatedAt;
				
				$extras[count($extras)]['key'] = 'date_moissonnage_creation';
				$extras[(count($extras) - 1)]['value'] = $results->createdAt;
				
				$extras[count($extras)]['key'] = 'edition_security';
				$extras[(count($extras) - 1)]['value'] = $security;
				
				$tagsData = array();
				
				if ($results->tags == '' || count($results->tags)==0 || !$results->tags) {
                    $tagsData = [];
				} 
				else {
					$tags = $results->tags;
					for ($j = 0; $j < count($tags); $j++) {
                        if($tags[$j]!=''){
							$val = $this->nettoyage($tags[$j]);
							array_push($tagsData, ["vocabulary_id" => null, "state" => "active", "display_name" => $val, "name" => $val]);
						}
					}             
                }
                
				$newData = [
					"name" => str_replace('-', '_',$this->nettoyage($results->name)),
					"title" => $results->name,
					"private" => $private,
					"author" => "",
					"author_email" => "",
					"maintainer" => "",
					"maintainer_email" => "",
					"license_id" => 'notspecified',
					"notes" => $results->description,
					"url" => '',
					"version" => "",
					"state" => "active",
					"type" => "dataset",
					"resources" => [],
					"tags" => $tagsData,
					"extras" => $extras,
					"relationships_as_object" => [],
					"relationships_as_subject" => [],
					"groups" => [],
					"owner_org" => $org_id,
				];
                
				$coll=array('0'=>'0', '1'=>'', '2'=>'');
				$NewData= $this->saveData($newData, $coll);
				$idNewData= $NewData[1];
				$NewTitle= $NewData[2];
				$dataset_conf=[
					"id_data" => $idNewData,
					"id_data_site"=>$results->id,
					"title_data"=>$NewTitle,
					"last_update" =>date('m/d/Y H:i:s', time()),
					"periodic_update" =>'',
					"site"=>$site_search,
					"site_infocom"=>$value[1]
				];
				$controlEx =false;
				
				foreach($dataForUpdateDatasets as $key => $value1){
					
				   if($value1->id_org == $org_id){
					   
					   array_push($dataForUpdateDatasets[$key]->datasets, $dataset_conf);
					   $controlEx = true;
					   break;
				   }
					
				}
            
				if($controlEx ==false){
					//                 array_push($dataForUpdateDatasets[$count_datas][datasets], $dataset_conf );
					$dataForUpdateDatasets[$count_datas]=[
						"id_org" =>$org_id,
						"name_org" =>$org_name,
						"datasets" =>[$dataset_conf],
					];
				}
          
				$config->set('dataForUpdateDatasets', json_encode($dataForUpdateDatasets))->save();
         
				/////////////////////resources////////////
         
				$host = $_SERVER['HTTP_HOST']; 
           
				if($_SERVER['HTTP_HOST']=='192.168.2.217'){
					$root='/home/bpm/drupal-8.6.15/sites/default/files/dataset/';
					$url_res = 'http://'.$host.'/sites/default/files/dataset/';
				}
				else{
					$root='/home/user-client/drupal-d4c/sites/default/files/dataset/';
					$url_res = 'https://'.$host.'/sites/default/files/dataset/';
                }
               
                $filepathN = $value[1].'/resource/'.$value[0].'.csv';
                $filepathN = explode('/',$filepathN);
                $filepathN = $filepathN[count($filepathN)-1];
                $filepathN = explode('.',$filepathN)[0]; 
                $filepathN =urldecode($filepathN);  
                $filepathN = strtolower($filepathN);
                  
				$filepathN = explode(".",$filepathN)[0].'.csv';
				$url_res = $url_res.''.$filepathN;
                
				// read into array
				//$arr = file('/home/user-client/drupal-d4c'.$filepath);
				$arr = file('https://'.$value[1].'/resource/'.$value[0].'.csv');
                        
				$label = utf8_decode($arr[0]);
				$label = $this->nettoyage($label);

				// edit first line
				$arr[0] = $label;
                
				// write back to file
				file_put_contents($root.''. $filepathN, implode($arr));
                
                //$query = DataSet::createResource($idNewData,$url_res,$res->description,$res->name, $res->format,'false');
                
				$resources = [
					"package_id" => $idNewData,
					"url" => $url_res,
					"description" => '',
					"name" =>$value[0],
					"format"=>'csv'
				];
                
				$callUrluptres = $this->urlCkan . "/api/action/resource_create";
				$return = $api->updateRequest($callUrluptres, $resources, "POST");
				$this->renderResourceLog($resources["name"], $return);
				sleep(20);
				$api->calculateVisualisations($idNewData);
			}	
		} 
        else if($site_search=='ckan'){
        
			$org_id= $form_state->getValue('org_def');
           
			$optionst = array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_HTTPHEADER => array(
					'Content-type:application/json',
					'Content-Length: ' . strlen($jsonData),
					'Authorization:  ' . $cle,
				),
			);
           
            $callUrlOrg = $this->urlCkan . "api/action/organization_show?id=".$org_id;
            $curlOrg = curl_init($callUrlOrg);
            curl_setopt_array($curlOrg, $optionst);
            $orgs = curl_exec($curlOrg);
            curl_close($curlOrg);
            $org = json_decode($orgs, true);

            $org_name = $org[result][title];

            $selectedDatasets = array_filter($form_state->getValue('ids'));
       

			foreach($selectedDatasets as &$value){
         
				$config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
				$dataForUpdateDatasets = $config->get('dataForUpdateDatasets'); 
				$dataForUpdateDatasets = json_decode($dataForUpdateDatasets);
				$count_datas=count($dataForUpdateDatasets);
				   
				$value= explode("|", $value);
				
				$query = Query::callSolrServer($value[1]."/api/3/action/package_show?id=".$value[0]);
					
				$results = json_decode($query);
				$results = $results->result;
				$private = true;
			   
				$label = $results->title;
					
				$extras = array();
				$extras[count($extras)]['key'] = 'LinkedDataSet';
				$extras[(count($extras) - 1)]['value'] = '';
						
				$extras[count($extras)]['key'] = 'theme';
				$extras[(count($extras) - 1)]['value'] = 'default';
						
				$extras[count($extras)]['key'] = 'label_theme';
				$extras[(count($extras) - 1)]['value'] = 'Default';
						
				$extras[count($extras)]['key'] = 'type_map';
				$extras[(count($extras) - 1)]['value'] = 'osm';
				
				$extras[count($extras)]['key'] = 'FTP_API';
				$extras[(count($extras) - 1)]['value'] = $value[1].'/dataset/'.$value[0];
				
                $extras[count($extras)]['key'] = 'date_moissonnage_last_modification';
				$extras[(count($extras) - 1)]['value'] = $results->metadata_modified;
				
				$extras[count($extras)]['key'] = 'date_moissonnage_creation';
				$extras[(count($extras) - 1)]['value'] = $results->metadata_created;
				
				$extras[count($extras)]['key'] = 'edition_security';
				$extras[(count($extras) - 1)]['value'] = $security;
				
				$tagsData = array();
				if ($results->tags == '' || count($results->tags)==0 || !$results->tags) {
					$tagsData = [];
				} 
				else {
					$tags = $results->tags;
					for ($j = 0; $j < count($tags); $j++) {
						if($tags[$j]!=''){
							array_push($tagsData, ["vocabulary_id" => null, "state" => "active", "display_name" => $tags[$j]->name, "name" => $tags[$j]->name]);
						}
					}             
                }
        
				$newData = [
					"name" => str_replace('-', '_',$results->name),
					"title" => $results->title,
					"private" => $private,
					"author" => $results->author,
					"author_email" => $results->author_email,
					"maintainer" => $results->maintainer,
					"maintainer_email" => $results->maintainer_email,
					"license_id" => $results->license_id,
					"notes" => $results->notes,
					"url" => '',
					"version" => $results->version,
					"state" => "active",
					"type" => $results->type,
					"resources" => [],
					"tags" => $tagsData,
					"extras" => $extras,
					"relationships_as_object" => $results->relationships_as_object,
					"relationships_as_subject" => $results->relationships_as_subject,
					"groups" => [],
					"owner_org" => $org_id,
				];
            
				$coll=array('0'=>'0', '1'=>'', '2'=>'');
				$NewData= $this->saveData($newData, $coll);
				$idNewData= $NewData[1];
				$NewTitle= $NewData[2];
				
				$dataset_conf=[
					"id_data" => $idNewData,
					"id_data_site"=>$results->id,
					"title_data"=>$NewTitle,
					"last_update" =>date('m/d/Y H:i:s', time()),
					"periodic_update" =>'',
					"site"=>$site_search,
					"site_infocom"=>$value[1]
				];
            
				$controlEx =false;
            
				foreach($dataForUpdateDatasets as $key => $value){
					if($value->id_org == $org_id){
						array_push($dataForUpdateDatasets[$key]->datasets, $dataset_conf);
						$controlEx = true;
						break;
					}
				}
            
				if($controlEx ==false){
                 //                 array_push($dataForUpdateDatasets[$count_datas][datasets], $dataset_conf );
					$dataForUpdateDatasets[$count_datas]=[
						"id_org" =>$org_id,
						"name_org" =>$org_name,
						"datasets" =>[$dataset_conf],
					];
				}
          
				$config->set('dataForUpdateDatasets', json_encode($dataForUpdateDatasets))->save();
            
				/////////////////////resources////////////
				foreach($results->resources as &$res){
             
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
									$filepathN = explode(".",$filepathN)[0].'.XLS';
									$filepathDell =  $filepathN;
									$reader = new Xls();
									break;
								case 'XLSX':
									$filepathN = explode(".",$filepathN)[0].'.XLSX';
									$filepathDell =  $filepathN;
									$reader = new Xlsx();
									break;
								case 'xls':
									$filepathN = explode(".",$filepathN)[0].'.xls';
									$filepathDell =  $filepathN;
									$reader = new Xls();
									break;
								case 'xlsx':
									$filepathN = explode(".",$filepathN)[0].'.xlsx';
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
							$label = utf8_decode($arr[0]);
					 
							$label = $this->nettoyage($label);  
							// edit first line
							$arr[0] = $label;
					
							// write back to file
							file_put_contents($root.'xls_'.$fileName, implode($arr));
				
							//$query = DataSet::createResource($idNewData,$url_res,$res->description, $title_f, 'csv','false');
					
							$resources = [
								"package_id" => $idNewData,
								"url" => $url_res,
								"description" => $res->description,
								"name" =>$res->name,
								"format"=>$res->format
							];
					
							//drupal_set_message("RES:". print_r($resources,true) );

							$callUrluptres = $this->urlCkan . "/api/action/resource_create";
							$return = $api->updateRequest($callUrluptres, $resources, "POST");
							$this->renderResourceLog($resources["name"], $return);
						}
				
						if($res->format == 'csv' || $res->format == 'CSV') {
					  
							$filepathN = explode(".",$filepathN)[0].'.csv';
							$url_res = $url_res.''.$filepathN;
					
							// read into array
							//$arr = file('/home/user-client/drupal-d4c'.$filepath);
							$arr = file($res->url);
							$label = utf8_decode($arr[0]);
							$label = $this->nettoyage($label);  
					
							// edit first line
							$arr[0] = $label;
					
							// write back to file
							file_put_contents($root.''. $filepathN, implode($arr));
					
							//$query = DataSet::createResource($idNewData,$url_res,$res->description,$res->name, $res->format,'false');
					
							$resources = [
								"package_id" => $idNewData,
								"url" => $url_res,
								"description" => $res->description,
								"name" =>$res->name,
								"format"=>$res->format
							];

							$callUrluptres = $this->urlCkan . "/api/action/resource_create";
							$return = $api->updateRequest($callUrluptres, $resources, "POST");
							$this->renderResourceLog($resources["name"], $return);
						}
								
					}
					else{
						$url_res = $res->url;
						//$query = DataSet::createResource($idNewData,$url_res,$res->description,$res->name, $res->format,'false');
					  
						$resources = [
							"package_id" => $idNewData,
							"url" => $url_res,
							"description" => $res->description,
							"name" =>$res->name,
							"format"=>$res->format
						];

						$callUrluptres = $this->urlCkan . "/api/action/resource_create";
						$return = $api->updateRequest($callUrluptres, $resources, "POST");
						$this->renderResourceLog($resources["name"], $return);
					  
					}   
				} 
				sleep(20);
				$api->calculateVisualisations($idNewData);
			}
     
		}        
        else if($site_search=='arcgis'){
            $org_id= $form_state->getValue('org_def');
			$optionst = array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_HTTPHEADER => array(
					'Content-type:application/json',
					'Content-Length: ' . strlen($jsonData),
					'Authorization:  ' . $cle,
				),
			);
           
            $callUrlOrg = $this->urlCkan . "api/action/organization_show?id=".$org_id;
            $curlOrg = curl_init($callUrlOrg);
            curl_setopt_array($curlOrg, $optionst);
            $orgs = curl_exec($curlOrg);
            curl_close($curlOrg);
            $org = json_decode($orgs, true);
            $org_name = $org[result][title];

            $selectedDatasets = array_filter($form_state->getValue('ids'));

            foreach($selectedDatasets as &$value){
                
                $config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
                $dataForUpdateDatasets = $config->get('dataForUpdateDatasets'); 
                $dataForUpdateDatasets = json_decode($dataForUpdateDatasets);  
                $count_datas=count($dataForUpdateDatasets);

                $value= explode("|", $value);   
                $site_search_url = $value[1]."/".$value[0];
                $query = Query::callSolrServer($site_search_url.'?f=pjson');
                $results = json_decode($query);
                $private = true;
           
                $extras = array();
                $extras[count($extras)]['key'] = 'LinkedDataSet';
                $extras[(count($extras) - 1)]['value'] = '';
                    
                $extras[count($extras)]['key'] = 'theme';
                $extras[(count($extras) - 1)]['value'] = 'default';
                    
                $extras[count($extras)]['key'] = 'label_theme';
                $extras[(count($extras) - 1)]['value'] = 'Default';
            
                $extras[count($extras)]['key'] = 'FTP_API';
                $extras[(count($extras) - 1)]['value'] = $site_search_url;
                
                //$extras[count($extras)]['key'] = 'date_moissonnage_last_modification';
				//$extras[(count($extras) - 1)]['value'] = $results->XXXXXXX;
				
				//$extras[count($extras)]['key'] = 'date_moissonnage_creation';
				//$extras[(count($extras) - 1)]['value'] = $results->XXXXXXX;
				
				$extras[count($extras)]['key'] = 'edition_security';
				$extras[(count($extras) - 1)]['value'] = $security;
				
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
                

                $newData = [
					"name" =>str_replace('_', '-', $this->nettoyage($results->name)),
					"title" => $results->name,
					"private" => $private,
					"author" => "",
					"author_email" => "",
					"maintainer" => "",
					"maintainer_email" => "",
					"license_id" => 'notspecified',
					"notes" => $results->description,
					"url" => '',
					"version" => "",
					"state" => "active",
					"type" => 'dataset',
					"resources" => [],
					"tags" => $tagsData,
					"extras" => $extras,
					"relationships_as_object" => $results->relationships,
					"relationships_as_subject" => [],
					"groups" => [],
					"owner_org" => $org_id,
				];
				error_log($this->nettoyage($results->name));
                $coll=array('0'=>'0', '1'=>'', '2'=>'');
                $NewData= $this->saveData($newData, $coll);
                $idNewData= $NewData[1];
                $NewTitle= $NewData[2];
            
                $dataset_conf=[
					"id_data" => $idNewData,
					"id_data_site"=>$results->id,
					"title_data"=>$NewTitle,
					"last_update" =>date('m/d/Y H:i:s', time()),
					"periodic_update" =>'',
					"site"=>$site_search,
					"site_infocom"=>$site_search_url
				];
           
                $controlEx =false;

                foreach($dataForUpdateDatasets as $key => $value){
					if($value->id_org == $org_id){
						array_push($dataForUpdateDatasets[$key]->datasets, $dataset_conf);
						$controlEx = true;
						break;
					}
                }

                if($controlEx ==false){
                    //                 array_push($dataForUpdateDatasets[$count_datas][datasets], $dataset_conf );
					$dataForUpdateDatasets[$count_datas]=[
                        "id_org" =>$org_id,
                        "name_org" =>$org_name,
                        "datasets" =>[$dataset_conf],
					];
                }

                $config->set('dataForUpdateDatasets', json_encode($dataForUpdateDatasets))->save();
            
                $fileName = str_replace('-', '_', $newData["name"]);
				
				if(strpos($supportedQueryFormats, "geoJSON") !== false){
					$root='/home/user-client/drupal-d4c/sites/default/files/dataset/'.$fileName.'.geojson';
					$url = 'https://'.$_SERVER['HTTP_HOST'].'/sites/default/files/dataset/'.$fileName.'.geojson';
					
					$url_resource = $site_search_url.'/query?where=1%3D1&text=&objectIds=&time=&geometry=&geometryType=esriGeometryEnvelope&inSR=&spatialRel=esriSpatialRelIntersects&relationParam=&outFields=*&returnGeometry=true&returnTrueCurves=false&maxAllowableOffset=&geometryPrecision=&outSR=&returnIdsOnly=false&returnCountOnly=false&orderByFields=&groupByFieldsForStatistics=&outStatistics=&returnZ=false&returnM=false&gdbVersion=&returnDistinctValues=false&resultOffset=&resultRecordCount=&queryByDistance=&returnExtentsOnly=false&datumTransformation=&parameterValues=&rangeValues=&f=geojson';
					$arr = Query::callSolrServer($url_resource);
					
					//construction du csv
					//$arr = utf8_encode($arr);
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
					
					$nb_att = 0;
					if($hasAttachments){
						
						$url_attach = $site_search_url . "/".$sample["id"]."/attachments?f=pjson";
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
					//$records = array();
					foreach($json["features"] as $feat){
						$row = array();
						//$record = array();
						foreach($cols as $col){
							if($col == "geo_point_2d"){
								$str = json_encode($feat["geometry"]["coordinates"]);
								//preg_match('/\[(\d+),(\d+)\]/i',$feat["geometry"]["coordinates"], $matches);
								//$str = preg_replace('/\[([-]?[\d|.]+),([-]?[\d|.]+)/i', "[$2,$1]", $str);-
								//$row[] = str_replace(array("[","]"), array("",""), $str);
								preg_match('/\[([-]?[\d|.]+),([-]?[\d|.]+)/i', $str, $match);
								$val = '"'.$match[2] .",". $match[1].'"';
								$row[] = $val;
								//$record[$col] = $val;
							} else if($col == "geo_shape") {
								$str = json_encode($feat["geometry"]);
								//$str = preg_replace('/\[([\d|.]+),([\d|.]+)(,[\d|\w]+,[\d|\w]+)*\]/i', "[$2,$1$3]", $str);
								preg_match('/\[([-]?[\d|.]+),([-]?[\d|.]+)/i', $str, $match);
								$coord = '"'.$match[2] .",". $match[1].'"';
								$row[] = $coord;
								$row[] = $str;
								//$record["coordinates"] = $coord;
								//$record[$col] = $str;
							} else if(preg_match('/attachment_[\d]+_name/i',$col)){
								$url_attach = $site_search_url . "/".$feat["id"]."/attachments?f=pjson";
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
									$url = $site_search_url . "/".$feat["id"]."/attachments/" . $att["id"];
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
									//$record[strtolower($col)] = '"'.$feat["properties"][$col].'"';
								} else {
									$row[] = $feat["properties"][$col];
									//$record[strtolower($col)] = $feat["properties"][$col];
								}
								
							}
						}
						
						$rows[] = $row;//implode($row, ";");
						//$records[] = $record;
					}
					
					foreach($rows as &$row){
						if(count($row) < count($cols)){
							$row = array_pad($row, count($cols), "");
						}
						$row = implode($row, ";");
					}
					
					$data_csv[] = strtolower(implode($cols, ";"));
					$data_csv = array_merge($data_csv, $rows);
					
					$rootCsv='/home/user-client/drupal-d4c/sites/default/files/dataset/'.$fileName.'.csv';
					$urlCsv = 'https://'.$_SERVER['HTTP_HOST'].'/sites/default/files/dataset/'.$fileName.'.csv';
					//$data_csv = mb_convert_encoding( $data_csv, 'Windows-1252', 'UTF-8');
					
					//$string = iconv('ASCII', 'UTF-8//IGNORE', implode($data_csv, "\n"));
					//error_log(mb_detect_encoding($string));
					file_put_contents($rootCsv, utf8_encode(implode($data_csv, "\n")));
					
					
					$resource = [     
						"package_id" => $idNewData,
						"url" => $urlCsv,
						"description" => '',
						"name" =>$fileName.".csv",
						"format"=>'csv'
					];

					$callUrluptres = $this->urlCkan . "/api/action/resource_create";
					$return = $api->updateRequest($callUrluptres, $resource, "POST");
					$this->renderResourceLog($resource["name"], $return);
					
					
				} else {
					
					$root='/home/user-client/drupal-d4c/sites/default/files/dataset/'.$fileName.'.json';
					$url = 'https://'.$_SERVER['HTTP_HOST'].'/sites/default/files/dataset/'.$fileName.'.json';
					
					$url_resource = $site_search_url.'/query?where=1%3D1&text=&objectIds=&time=&geometry=&geometryType=esriGeometryEnvelope&inSR=&spatialRel=esriSpatialRelIntersects&relationParam=&outFields=*&returnGeometry=true&returnTrueCurves=false&maxAllowableOffset=&geometryPrecision=&outSR=&returnIdsOnly=false&returnCountOnly=false&orderByFields=&groupByFieldsForStatistics=&outStatistics=&returnZ=false&returnM=false&gdbVersion=&returnDistinctValues=false&resultOffset=&resultRecordCount=&queryByDistance=&returnExtentsOnly=false&datumTransformation=&parameterValues=&rangeValues=&f=json';
					$arr = file($url_resource);
					
					file_put_contents($root, $arr);

					$resources = [     
						"package_id" => $idNewData,
						"url" => $url,
						"description" => '',
						"name" =>$fileName.".json",
						"format"=>'json'
					];

					$callUrluptres = $this->urlCkan . "/api/action/resource_create";
					$return = $api->updateRequest($callUrluptres, $resources, "POST");
					$this->renderResourceLog($resources["name"], $return);
				}
				
				
                sleep(20);
				$api->calculateVisualisations($idNewData);
			}
        }
		


	}
    
    public function saveData($newData, $data){
        $coll = $data[0];
        
		// drupal_set_message('<pre>'.$data[0].'</pre>');
        //error_log(json_encode($newData));
        $api = new Api;
		$callUrlNewData = $this->urlCkan . "/api/action/package_create";
		$return = $api->updateRequest($callUrlNewData, $newData, "POST");
		//drupal_set_message('<pre>'.$return.'</pre>');
		$resnew = json_decode($return);
		$idNewData = $resnew->result->id;
		$NewTitle = $resnew->result->title;
                           
		if ($resnew->success == true) {
			drupal_set_message('Le jeu de données '.$resnew->result->title.' a bien été créé');
			$idNewData = $resnew->result->id;
			$NewTitle = $resnew->result->title;
		} 
		else if($resnew->error->name[0]=='Cette URL est déjà utilisée.'){
			$coll++;
			
			if($coll==1){
				$newData[name]=$newData[name].'_'.$coll;
				$newData[title]=$newData[title].' '.$coll;
				$NewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData, '2'=>$NewTitle));
				$idNewData = $NewData[1];
				$NewTitle = $NewData[2];
			}
			else if($coll>10){
				$newData[name]=substr($newData[name],0, -3);
				$newData[name]=$newData[name].'_'.$coll;
				$newData[title]=substr($newData[title],0, -3);
				$newData[title]=$newData[title].' '.$coll;
				$NewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData, '2'=>$NewTitle));
				$idNewData = $NewData[1];
				$NewTitle = $NewData[2];
			}
			else if($coll>100){
				$newData[name]=substr($newData[name],0, -4);
				$newData[name]=$newData[name].'_'.$coll;
				$newData[title]=substr($newData[title],0, -4);
				$newData[title]=$newData[title].' '.$coll;
				$NewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData, '2'=>$NewTitle));
				$idNewData = $NewData[1];
				$NewTitle = $NewData[2];    
			}
			else if($coll>1000){
				$newData[name]=substr($newData[name],0, -5);
				$newData[name]=$newData[name].'_'.$coll;
				$newData[title]=substr($newData[title],0, -5);
				$newData[title]=$newData[title].' '.$coll;
				$NewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData, '2'=>$NewTitle));
				$idNewData = $NewData[1];
				$NewTitle = $NewData[2];    
			}
			else if($coll>10000){
				$newData[name]=substr($newData[name],0, -6);
				$newData[name]=$newData[name].'_'.$coll;
				$newData[title]=substr($newData[title],0, -6);
				$newData[title]=$newData[title].' '.$coll;
				$NewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData, '2'=>$NewTitle));
				$idNewData = $NewData[1];
				$NewTitle = $NewData[2];
			}
			else{
				$newData[name]=substr($newData[name],0, -2);
				$newData[name]=$newData[name].'_'.$coll;
				$newData[title]=substr($newData[title],0, -2);
				$newData[title]=$newData[title].' '.$coll;
				$NewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData, '2'=>$NewTitle));
				$idNewData = $NewData[1];
				$NewTitle = $NewData[2];
			}
		} 
		else {
			drupal_set_message('Le jeu de données '.$newData[title].' n\'a pas été créé : '. json_encode($resnew->error), 'error');
		}
        
        return array('0'=>$coll, '1'=>$idNewData, '2'=>$NewTitle);
        
    }
    
    function nettoyage( $str, $charset='utf-8' ) {
			
		$patterns[0] = '/á|â|à|å|ä/';
		$patterns[1] = '/ð|é|ê|è|ë/';
		$patterns[2] = '/í|î|ì|ï/';
		$patterns[3] = '/ó|ô|ò|ø|õ|ö/';
		$patterns[4] = '/ú|û|ù|ü/';
		$patterns[5] = '/æ/';
		$patterns[6] = '/ç/';
		$patterns[7] = '/ß/';
		$replacements[0] = 'a';
		$replacements[1] = 'e';
		$replacements[2] = 'i';
		$replacements[3] = 'o';
		$replacements[4] = 'u';
		$replacements[5] = 'ae';
		$replacements[6] = 'c';
		$replacements[7] = 'ss';
		$str = preg_replace($patterns, $replacements, $str);
			
		$str = utf8_decode($str);
				
		$str = str_replace("?", "", $str);   
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
	
	public function loading(array &$form, FormStateInterface $form_state)
	{

		$response = new AjaxResponse();
		  
		$status_messages = array('#type' => 'status_messages');
		$messages = \Drupal::service('renderer')->renderRoot($status_messages);

		if (!empty($messages)) {error_log(json_encode($messages));
			$response->addCommand(new InsertCommand(".region-highlighted", $messages));
		}

		return $response;
	}
	
	public function renderResourceLog($name, $return){
		$res = json_decode($return, true);
		if($res["success"] == true){
			$out = "Succès";
			drupal_set_message("Ajout de la ressource ".$name . " : " . $out);
		} else {
			$out = json_encode($res["error"]);
			drupal_set_message("Ajout de la ressource ".$name . " : " . $out, 'error');
		}
	}
}