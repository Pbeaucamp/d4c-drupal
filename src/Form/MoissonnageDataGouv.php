<?php
/**
 * @file
* Contains \Drupal\search_api_solr_admin\Form\QueryForm.
*/

namespace Drupal\ckan_admin\Form;

use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\ResourceManager;
use Drupal\ckan_admin\Utils\Export;
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
use Drupal\ckan_admin\Utils\Logger;
use Drupal\ckan_admin\Utils\Tools;
use Drupal\Core\Url;

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
	

	private $config;
    private $urlCkan;
    

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

		$currentUrl = Url::fromRoute('<current>');

        $form['#attached']['library'][] = 'ckan_admin/MoissonnageDataGouv.form';
        $form['#attached']['html_head'][] = [
			array(
			  '#tag' => 'base',
			  '#attributes' => array(
				'href' => $currentUrl->toString()
			  ),
			)
		];
        
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
        
		$this->config = include(__DIR__ . "/../../config.php");
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
        for ($i = 0; $i < (is_countable($orgs['result']) ? count($orgs['result']) : 0); $i++) {
            $organizationList[$orgs['result'][$i]['id']] = $orgs['result'][$i]['display_name'];
        }
		if(isset($this->config->sitesSearch) && (is_countable($this->config->sitesSearch) ? count($this->config->sitesSearch) : 0) > 0){
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
        
		$this->config = include(__DIR__ . "/../../config.php");
        $api = new Api;
        $resourceManager = new ResourceManager;
        $this->urlCkan = $this->config->ckan->url;
        $site_search =  $org_id= $form_state->getValue('site_search');
		
		###### security #######
		$idUser = "*".\Drupal::currentUser()->id()."*";

		// $users = \Drupal\user\Entity\User::loadMultiple();
		$users = $api->getAdministrators();

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

		Logger::logMessage("Harvest dataset from '" . $site_search . "' \r\n");
        
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
           
            $org_name = $org['result']['title'];

            $selectedDatasets = array_filter($form_state->getValue('ids'));

            foreach($selectedDatasets as &$value){

				Logger::logMessage("Manage dataset '" . $value[0] . "' \r\n");
            
                $config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
                $dataForUpdateDatasets = $config->get('dataForUpdateDatasets'); 
                $dataForUpdateDatasets = json_decode($dataForUpdateDatasets);   
                $count_datas=is_countable($dataForUpdateDatasets) ? count($dataForUpdateDatasets) : 0;


                $value= explode("|", $value);

				$callSolrUrl = $value[1]."api/datasets/2.0/searchdatasetres/id=".$value[0];
				Logger::logMessage("Searching resource " . $callSolrUrl . "\r\n");
				$query = Query::callSolrServer($callSolrUrl);

                $results = json_decode($query);
                $results = $results->result;
                $private = false;
                $label = $results->title;
                $extras = $results->extras;
				
                $ex_Ftp=false;
				$ex_dmlm=false;
				$ex_dmc=false;
				$ex_sec=false;
                for($i= 0; $i<(is_countable($extras) ? count($extras) : 0); $i++ ){
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
					if($extras[$i]->key == 'edition_security'){
                        $ex_sec=true;
                        $extras[$i]->value  == $security; 
                    }
                }
                
                if($ex_Ftp==false){
                    $extras[is_countable($extras) ? count($extras) : 0]['key'] = 'FTP_API';
                    $extras[((is_countable($extras) ? count($extras) : 0) - 1)]['value'] = $value[1]."visualisation/?id=".$value[0];
                }
				if($ex_dmlm==false){
                    $extras[is_countable($extras) ? count($extras) : 0]['key'] = 'date_moissonnage_last_modification';
					$extras[((is_countable($extras) ? count($extras) : 0) - 1)]['value'] = $results->metadata_modified;
                }
				if($ex_dmc==false){
                    $extras[is_countable($extras) ? count($extras) : 0]['key'] = 'date_moissonnage_creation';
					$extras[((is_countable($extras) ? count($extras) : 0) - 1)]['value'] = $results->metadata_created;
                }
				if ($ex_sec == false) {
					$extras[is_countable($extras) ? count($extras) : 0]['key'] = 'edition_security';
					$extras[((is_countable($extras) ? count($extras) : 0) - 1)]['value'] = $security;
				}

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
				$NewName= $NewData[3];
            
           
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
					$root='/home/user-client/drupal-d4c' . $this->config->client->routing_prefix . '/sites/default/files/dataset/';
					$url_res = 'https://'.$host . $this->config->client->routing_prefix . '/sites/default/files/dataset/';
            
					if($res->format == 'CSV' || $res->format == 'XLS' || $res->format == 'XLSX' || $res->format == 'csv' || $res->format == 'xls' || $res->format == 'xlsx'){
						$add_tres=true;
                  
						$filepathN = $res->url;
						$filepathN = explode('/',$filepathN);
						$filepathN = $filepathN[count($filepathN)-1];
						//$filepathN = explode('.',$filepathN)[0]; 
						$filepathN =urldecode($filepathN);  
						$filepathN = strtolower($filepathN);
                  
						//$url_res = $res->url;
						if($res->format == 'csv' || $res->format == 'CSV') {
							  
							//$filepathN = explode(".",$filepathN)[0].'.csv';
							// $filepathN = explode(".",$filepathN)[0].'.csv';
							// $url_res = $url_res.''.$filepathN;
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
						}	
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
				$resourceManager->manageCSWXmlFile($org_id, $idNewData, $NewName);
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
            $org_name = $org['result']['title'];
            $selectedDatasets = array_filter($form_state->getValue('ids'));

            foreach($selectedDatasets as &$value){
            
                $config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
                $dataForUpdateDatasets = $config->get('dataForUpdateDatasets'); 
                $dataForUpdateDatasets = json_decode($dataForUpdateDatasets);
                $count_datas=is_countable($dataForUpdateDatasets) ? count($dataForUpdateDatasets) : 0;
                
                $jsonValue = json_decode($value, true);
				if(substr($jsonValue["url"], -1) === "/"){
					$jsonValue["url"] = substr($jsonValue["url"], 0, -1);
				}
                $query = Query::callSolrServer($jsonValue["url"]."/d4c/api/datasets/2.0/searchdatasetres/id=".$jsonValue["id"]);
                $results = json_decode($query);
                $results = $results->result;
                $private = false;
                $label = $results->title;
                $extras = $results->extras;
                $ex_Ftp=false;
				$ex_dmlm=false;
				$ex_dmc=false;
                for($i= 0; $i<(is_countable($extras) ? count($extras) : 0); $i++ ){
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
                    $extras[is_countable($extras) ? count($extras) : 0]['key'] = 'FTP_API';
                    $extras[((is_countable($extras) ? count($extras) : 0) - 1)]['value'] = $jsonValue["url"]."/visualisation/?id=".$jsonValue["id"];
                }
				if($ex_dmlm==false){
                    $extras[is_countable($extras) ? count($extras) : 0]['key'] = 'date_moissonnage_last_modification';
					$extras[((is_countable($extras) ? count($extras) : 0) - 1)]['value'] = $results->metadata_modified;
                }
				if($ex_dmc==false){
                    $extras[is_countable($extras) ? count($extras) : 0]['key'] = 'date_moissonnage_creation';
					$extras[((is_countable($extras) ? count($extras) : 0) - 1)]['value'] = $results->metadata_created;
                }				
				
				$extras[is_countable($extras) ? count($extras) : 0]['key'] = 'edition_security';
				$extras[((is_countable($extras) ? count($extras) : 0) - 1)]['value'] = $security;
           
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
				$NewName= $NewData[3];
                $dataset_conf=[
					"id_data" => $idNewData,
					"id_data_site"=>$results->id,
					"title_data"=>$NewTitle,
					"last_update" =>date('m/d/Y H:i:s', time()),
					"periodic_update" =>'',
					"site"=>$site_search,
					"site_infocom"=>$jsonValue["url"],
					"parameters" => $jsonValue["params"],
					"date_last_filtre" => date("Y-m-d H:i:s"),
					"date_last_moissonnage" => date('m/d/Y H:i:s', time())
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
						$root='/home/user-client/drupal-d4c' . $this->config->client->routing_prefix . '/sites/default/files/dataset/';
						$url_res = 'https://'.$host . $this->config->client->routing_prefix . '/sites/default/files/dataset/';
					}
            
					if($res->format == 'CSV' || $res->format == 'XLS' || $res->format == 'XLSX' || $res->format == 'csv' || $res->format == 'xls' || $res->format == 'xlsx'){
						$add_tres=true;
                  
						$filepathN = $res->url;
						$filepathN = explode('/',$filepathN);
						$filepathN = $filepathN[count($filepathN)-1];
						//$filepathN = explode('.',$filepathN)[0]; 
						$filepathN =urldecode($filepathN);  
						$filepathN = strtolower($filepathN);
                  
						//if($res->format == 'csv' || $res->format == 'CSV') {
                  
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
						//}            
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
				$resourceManager->manageCSWXmlFile($org_id, $idNewData, $NewName);
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
        
				if($org['success']==true){
					$org_id=$org['result']['id'];
					$org_name = $org['result']['title'];
						  
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
					$org_id=$return['result']['id'];   
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
				$org_name = $org['result']['title'];
           
            }
        
            $array_datasets_for_config=array();
        
            $selectedDatasets = array_filter($form_state->getValue('ids'));
        
            foreach($selectedDatasets as &$value){
				
				$config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
				$dataForUpdateDatasets = $config->get('dataForUpdateDatasets'); 
				$dataForUpdateDatasets = json_decode($dataForUpdateDatasets);
				$count_datas=is_countable($dataForUpdateDatasets) ? count($dataForUpdateDatasets) : 0;
				
				$query = Query::callSolrServer("https://www.data.gouv.fr/api/1/datasets/".$value."/");
				$results = json_decode($query);
				$private = false;
				
				$label = $results->slug;
				
				
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
				if ($results->tags == '' || (is_countable($results->tags) ? count($results->tags) : 0)==0 || !$results->tags) {
					$tagsData = [];
				} 
				else {
					$tags = $results->tags;
					for ($j = 0; $j < (is_countable($tags) ? count($tags) : 0); $j++) {
						if($tags[$j]!=''){
							$val = $this->nettoyage($tags[$j]);
							array_push($tagsData, ["vocabulary_id" => null, "state" => "active", "display_name" => $val, "name" => $val]);
						}
					}
				}
				
				$extras = array();
				
				$extras[count($extras)]['key'] = 'LinkedDataSet';
				$extras[(count($extras) - 1)]['value'] = '';
				
				$themes = array();
				$themes[] = "default";
				
				$extras[count($extras)]['key'] = 'theme';
				$extras[(count($extras) - 1)]['value'] = json_encode($themes);
						
				// $extras[count($extras)]['key'] = 'label_theme';
				// $extras[(count($extras) - 1)]['value'] = 'Default';
						
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
				$NewName= $NewData[3];
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
				$geo_res = array();
				foreach($results->resources as &$res){     
					if($_SERVER['HTTP_HOST']=='192.168.2.217'){
						$root='/home/bpm/drupal-8.6.15/sites/default/files/dataset/';
					}
					else{
						$root='/home/user-client/drupal-d4c' . $this->config->client->routing_prefix . '/sites/default/files/dataset/';
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
							$url_res = 'https://'.$host . $this->config->client->routing_prefix . '/sites/default/files/dataset/';
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
								"name" =>$res->id,
								"format"=>'csv'
							];

							$callUrluptres = $this->urlCkan . "/api/action/resource_create";
							$return = $api->updateRequest($callUrluptres, $resources, "POST");    
							$this->renderResourceLog($resources["name"], $return);
						}
				
						if($res->format == 'csv' || $res->format == 'CSV') {
							//ini_set("auto_detect_line_endings", true);
							$filepathN = explode(".",$filepathN)[0].'.csv';
							$url_res = $url_res.''.$filepathN;
						
							// read into array
							//$arr = file('/home/user-client/drupal-d4c'.$filepath);
							$arr = file($res->url);
							//$label = utf8_decode($arr[0]);
							$label = $arr[0];
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
								"name" =>$res->id,
								"format"=>$res->format
							];
						

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
							"name" =>$res->id,
							"format"=>$res->format
						];

						$callUrluptres = $this->urlCkan . "/api/action/resource_create";
						$return = $api->updateRequest($callUrluptres, $resources, "POST");
						$this->renderResourceLog($resources["name"], $return);
						//error_log($res->format);
						if(strtolower($res->format) == 'geojson' || strtolower($res->format) == 'kml' || (strtolower($res->format) == 'json' && (strpos(strtolower($res->title), "export geojson") !== false || strpos(strtolower($res->description), "export geojson") !== false))) {
							$geo_res[strtolower($res->format)] = $res->url;
						}
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
				$command = NULL;
				if($add_tres == FALSE && count($geo_res) > 0){
					// on créé un csv
					error_log("on créée un csv");
					$name = $label . "_" . uniqid();
					$rootCsv='/home/user-client/drupal-d4c' . $this->config->client->routing_prefix . '/sites/default/files/dataset/'.$name.'.csv';
					$rootJson='/home/user-client/drupal-d4c' . $this->config->client->routing_prefix . '/sites/default/files/dataset/'.$name.'.geojson';
					$urlCsv = 'https://'.$_SERVER['HTTP_HOST'] . $this->config->client->routing_prefix . '/sites/default/files/dataset/'.$name.'.csv';
					if($geo_res["geojson"] != null){
						error_log("la source est un geojson");
						$url = $geo_res["geojson"];
						$json = Query::callSolrServer($url);
						error_log("fichier récuperé");
						$csv = Export::createCSVfromGeoJSON($json);
						error_log("fichier converti");
						file_put_contents($rootCsv, $csv);
					} else if($geo_res["json"] != null){
						error_log("la source est un json");
						$url = $geo_res["json"];
						$json = Query::callSolrServer($url);
						error_log("fichier récuperé");
						$csv = Export::createCSVfromGeoJSON($json);
						error_log("fichier converti");
						file_put_contents($rootCsv, $csv);
					} else {
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
					
					
					$resource = [     
						"package_id" => $idNewData,
						"url" => $urlCsv,
						"description" => '',
						"name" =>$name.".csv",
						"format"=>'csv'
					];

					$callUrluptres = $this->urlCkan . "/api/action/resource_create";
					$return = $api->updateRequest($callUrluptres, $resource, "POST");
					$this->renderResourceLog($resource["name"], $return);
					
					// Deactivated for now
					// $pathUserClient = '/home/user-client';
					// $pathUserClientData = $pathUserClient . '/data';
					// $buildGeoloc = 'false';
					// $selectedSeparator = ";";
					// $selectedEncoding = "UTF-8";
					// $onlyOneAddress = 'false';
					// $selectedAddress = "";
					// $selectedPostalCode = "";
					// $command = $pathUserClientData . '/geoloc.sh "' . $buildGeoloc . '" "' . $this->urlCkan . '" "' . $this->config->ckan->api_key . '" "' . $NewName . '" "' . $return["result"]["id"] . '" "' . $selectedSeparator . '" "' . $selectedEncoding . '" "' . $onlyOneAddress . '" "' . $selectedAddress . '" "' . $selectedPostalCode . '"';
					
				}
				
				sleep(20);
				$api->calculateVisualisations($idNewData);
				$resourceManager->manageCSWXmlFile($org_id, $idNewData, $NewName);
				// if($command != NULL){
				// 	error_log($command);
				// 	$output = shell_exec($command);
				// 	error_log($output);
				// }
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
            $org_name = $org['result']['title'];
            $selectedDatasets = array_filter($form_state->getValue('ids'));
       
            foreach($selectedDatasets as &$value){
            
				$config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
				$dataForUpdateDatasets = $config->get('dataForUpdateDatasets'); 
				$dataForUpdateDatasets = json_decode($dataForUpdateDatasets);
				$count_datas=is_countable($dataForUpdateDatasets) ? count($dataForUpdateDatasets) : 0;

				$jsonValue = json_decode($value, true);
				
				$query = Query::callSolrServer("https://public.opendatasoft.com/api/datasets/1.0/".$jsonValue["id"].'/');
				$query = json_decode($query);    

				$results = $query->metas;

				$private = false;

				$extras = array();
				$extras[count($extras)]['key'] = 'LinkedDataSet';
				$extras[(count($extras) - 1)]['value'] = '';
				
				$themes = array();
				$themes[] = "default";
				
				$extras[count($extras)]['key'] = 'theme';
				$extras[(count($extras) - 1)]['value'] = json_encode($themes);
						
				// $extras[count($extras)]['key'] = 'label_theme';
				// $extras[(count($extras) - 1)]['value'] = 'Default';
				
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
                
				if ($results->keyword == '' || (is_countable($results->keyword) ? count($results->keyword) : 0)==0 || !$results->keyword) {
					$tagsData = [];
				} 
				else {
					$tags = $results->keyword;
					for ($j = 0; $j < (is_countable($tags) ? count($tags) : 0); $j++) {
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
				$NewName= $NewData[3];
				$dataset_conf=[
					"id_data" => $idNewData,
					"id_data_site"=>$query->datasetid,
					"title_data"=>$NewTitle,
					"last_update" =>date('m/d/Y H:i:s', time()),
					"periodic_update" =>'',
					"site"=>$site_search,
					"site_infocom"=>'',
					"parameters" => $jsonValue["params"],
					"date_last_filtre" => date("Y-m-d H:i:s"),
					"date_last_moissonnage" => date('m/d/Y H:i:s', time())
				];
				
				//error_log($dataset_conf);
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
				$resourceManager->manageCSWXmlFile($org_id, $idNewData, $NewName);
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
            $org_name = $org['result']['title'];

            $selectedDatasets = array_filter($form_state->getValue('ids'));

            foreach($selectedDatasets as &$value){
                
                $config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
                $dataForUpdateDatasets = $config->get('dataForUpdateDatasets'); 
                $dataForUpdateDatasets = json_decode($dataForUpdateDatasets);  
                $count_datas=is_countable($dataForUpdateDatasets) ? count($dataForUpdateDatasets) : 0;
				
				$jsonValue = json_decode($value, true);

                //$value= explode("|", $value);   
                //$site_search_url = $value[1];
                $site_search_url = $jsonValue["url"];
                $query = Query::callSolrServer($site_search_url."/api/datasets/1.0/".$jsonValue["id"].'/');
                $query = json_decode($query);    
                $results = $query->metas;
                $private = false;
           
                $extras = array();
                $extras[count($extras)]['key'] = 'LinkedDataSet';
                $extras[(count($extras) - 1)]['value'] = '';
				
				$themes = array();
				$themes[] = "default";
                    
                $extras[count($extras)]['key'] = 'theme';
                $extras[(count($extras) - 1)]['value'] = json_encode($themes);
                    
                // $extras[count($extras)]['key'] = 'label_theme';
                // $extras[(count($extras) - 1)]['value'] = 'Default';
				
            
                $extras[count($extras)]['key'] = 'FTP_API';
                $extras[(count($extras) - 1)]['value'] = 'https://public.opendatasoft.com/explore/dataset/'.$jsonValue["id"].'/';
                
                $extras[count($extras)]['key'] = 'date_moissonnage_last_modification';
				$extras[(count($extras) - 1)]['value'] = $results->metadata_processed;
				
				$extras[count($extras)]['key'] = 'date_moissonnage_creation';
				$extras[(count($extras) - 1)]['value'] = $results->modified;
				
				$extras[count($extras)]['key'] = 'edition_security';
				$extras[(count($extras) - 1)]['value'] = $security;
				
                $tagsData = array();
                if ($results->keyword == '' || (is_countable($results->keyword) ? count($results->keyword) : 0)==0 || !$results->keyword) {
					$tagsData = [];
				} 
                else {
					$tags = $results->keyword;
					for ($j = 0; $j < (is_countable($tags) ? count($tags) : 0); $j++) {
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
				$NewName= $NewData[3];
            
                $dataset_conf=[
					"id_data" => $idNewData,
					"id_data_site"=>$query->datasetid,
					"title_data"=>$NewTitle,
					"last_update" =>date('m/d/Y H:i:s', time()),
					"periodic_update" =>'',
					"site"=>$site_search,
					"site_infocom"=>$site_search_url,
					"parameters" => $jsonValue["params"],
					"date_last_filtre" => date("Y-m-d H:i:s"),
					"date_last_moissonnage" => date('m/d/Y H:i:s', time())
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
				$resourceManager->manageCSWXmlFile($org_id, $idNewData, $NewName);
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
			$org_name = $org['result']['title']; 
			$selectedDatasets = array_filter($form_state->getValue('ids'));

			foreach($selectedDatasets as &$value){
			  
				$config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
				$dataForUpdateDatasets = $config->get('dataForUpdateDatasets'); 
				$dataForUpdateDatasets = json_decode($dataForUpdateDatasets);   
				$count_datas=is_countable($dataForUpdateDatasets) ? count($dataForUpdateDatasets) : 0; 
			 
				$value= explode("|", $value);  
				//$query = Query::callSolrServer($value[1]."/api/views/".$value[0].".json");
				$query = Query::callSolrServer($value[1]."/api/views/metadata/v1/".$value[0].".json");
				$results = json_decode($query);
				$private = false;
			   
					//$label = $results->title;
					
				$extras = array();
				$extras[count($extras)]['key'] = 'LinkedDataSet';
				$extras[(count($extras) - 1)]['value'] = '';

				$themes = array();
				$themes[] = "default";
						
				$extras[count($extras)]['key'] = 'theme';
				$extras[(count($extras) - 1)]['value'] = json_encode($themes);
						
				// $extras[count($extras)]['key'] = 'label_theme';
				// $extras[(count($extras) - 1)]['value'] = 'Default';
						
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
				
				if ($results->tags == '' || (is_countable($results->tags) ? count($results->tags) : 0)==0 || !$results->tags) {
                    $tagsData = [];
				} 
				else {
					$tags = $results->tags;
					for ($j = 0; $j < (is_countable($tags) ? count($tags) : 0); $j++) {
                        if($tags[$j]!=''){
							$val = $this->nettoyage($tags[$j]);
							array_push($tagsData, ["vocabulary_id" => null, "state" => "active", "display_name" => $val, "name" => $val]);
						}
					}             
                }
				
			    $name = $results->name;
				if(strlen($name) > 100) {
					$name = substr($name, 0, 99);
				}
                
				$newData = [
					"name" => str_replace('-', '_',$this->nettoyage($name)),
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
				$NewName= $NewData[3];
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
					$root='/home/user-client/drupal-d4c' . $this->config->client->routing_prefix . '/sites/default/files/dataset/';
					$url_res = 'https://'.$host . $this->config->client->routing_prefix . '/sites/default/files/dataset/';
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
				$resourceManager->manageCSWXmlFile($org_id, $idNewData, $NewName);
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

            $org_name = $org['result']['title'];

            $selectedDatasets = array_filter($form_state->getValue('ids'));
       

			foreach($selectedDatasets as &$value){
         
				$config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
				$dataForUpdateDatasets = $config->get('dataForUpdateDatasets'); 
				$dataForUpdateDatasets = json_decode($dataForUpdateDatasets);
				$count_datas=is_countable($dataForUpdateDatasets) ? count($dataForUpdateDatasets) : 0;
				   
				$value= explode("|", $value);
				//error_log(json_encode($value));
				//$query = Query::callSolrServer("https://".$value[1]."/api/3/action/package_show?id=".$value[0]);
				$curl = curl_init('https://'.$value[1]."/api/3/action/package_show?id=".$value[0]);
				$opt = $api->getSimpleGetOptions();                               
				curl_setopt_array($curl, $opt);    
				$query = curl_exec($curl);
				//error_log($query);
				curl_close($curl);
					
				$results = json_decode($query);
				$results = $results->result;
				$private = false;
			   
				$label = $results->title;
					
				$extras = array();
				$extras[count($extras)]['key'] = 'LinkedDataSet';
				$extras[(count($extras) - 1)]['value'] = '';

				$themes = array();
				$themes[] = "default";
						
				$extras[count($extras)]['key'] = 'theme';
				$extras[(count($extras) - 1)]['value'] = json_encode($themes);
						
				// $extras[count($extras)]['key'] = 'label_theme';
				// $extras[(count($extras) - 1)]['value'] = 'Default';
						
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
				if ($results->tags == '' || (is_countable($results->tags) ? count($results->tags) : 0)==0 || !$results->tags) {
					$tagsData = [];
				} 
				else {
					$tags = $results->tags;
					for ($j = 0; $j < (is_countable($tags) ? count($tags) : 0); $j++) {
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
				$NewName= $NewData[3];
				
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

					Logger::logMessage("Add resource " . $res->url);
             
					$host = $_SERVER['HTTP_HOST']; 
			   
					if($_SERVER['HTTP_HOST']=='192.168.2.217'){
						$root='/home/bpm/drupal-8.6.15/sites/default/files/dataset/';
						$url_res = 'http://'.$host.'/sites/default/files/dataset/';
					}
					else{
						$root='/home/user-client/drupal-d4c' . $this->config->client->routing_prefix . '/sites/default/files/dataset/';
						$url_res = 'https://'.$host . $this->config->client->routing_prefix . '/sites/default/files/dataset/';
					}
					
					if($res->format == 'CSV' || $res->format == 'XLS' || $res->format == 'XLSX' || $res->format == 'csv' || $res->format == 'xls' || $res->format == 'xlsx'){
						$add_tres=true;
					  
						$filepathN = $res->url;
						$filepathN = explode('/',$filepathN);
						$filepathN = $filepathN[count($filepathN)-1];
						$filepathN = explode('.',$filepathN)[0]; 
						$filepathN = urldecode($filepathN);  
						$filepathN = strtolower($filepathN);
						$filepathN = $this->nettoyage($filepathN);

						//If it does not contain the format, we put it automatically
						if (!(strpos($filepathN, '.') !== false)) {
							$filepathN = $filepathN . ".csv";
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
				
						if($res->format == 'csv' || $res->format == 'CSV') {

							Logger::logMessage("Add CSV file");
					  
							$filepathN = explode(".",$filepathN)[0].'.csv';
							$url_res = $url_res.''.$filepathN;

							Logger::logMessage("Resource URL " . $res->url);

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

							$label = utf8_decode($arr[0]);
							
							$label = $this->nettoyage($label);  
					
							// edit first line
							$arr[0] = $label;
					
							// write back to file
							file_put_contents($root.''. $filepathN, implode($arr));

							Logger::logMessage("Write file to " . $root . '' . $filepathN);
					
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
				$resourceManager->manageCSWXmlFile($org_id, $idNewData, $NewName);
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
            $org_name = $org['result']['title'];

            $selectedDatasets = array_filter($form_state->getValue('ids'));
			$tz = date_default_timezone_get();
			date_default_timezone_set('UTC');
            foreach($selectedDatasets as &$value){
                
                $config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
                $dataForUpdateDatasets = $config->get('dataForUpdateDatasets'); 
                $dataForUpdateDatasets = json_decode($dataForUpdateDatasets);  
                $count_datas=is_countable($dataForUpdateDatasets) ? count($dataForUpdateDatasets) : 0;

                $value= explode("|", $value);   
                $site_search_url = $value[1]."/".$value[0];
                $query = Query::callSolrServer($site_search_url.'?f=pjson');
                $results = json_decode($query);
                $private = false;
           
                $extras = array();
                $extras[count($extras)]['key'] = 'LinkedDataSet';
                $extras[(count($extras) - 1)]['value'] = '';

				$themes = array();
				$themes[] = "default";
                    
                $extras[count($extras)]['key'] = 'theme';
                $extras[(count($extras) - 1)]['value'] = json_encode($themes);
                    
                // $extras[count($extras)]['key'] = 'label_theme';
                // $extras[(count($extras) - 1)]['value'] = 'Default';
            
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
                
				$name = str_replace('_', '-', $this->nettoyage($results->name));
				if(strlen($name) > 100) {
					$name = substr($name, 0, 99);
				}
                $newData = [
					"name" => $name,
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
				//error_log($this->nettoyage($results->name));
                $coll=array('0'=>'0', '1'=>'', '2'=>'');
                $NewData= $this->saveData($newData, $coll);
                $idNewData= $NewData[1];
                $NewTitle= $NewData[2];
				$NewName = $NewData[3];
            
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
				$command = NULL;
				
				if(strpos($supportedQueryFormats, "geoJSON") !== false){
					$root='/home/user-client/drupal-d4c' . $this->config->client->routing_prefix . '/sites/default/files/dataset/'.$fileName.'.geojson';
					$url = 'https://'.$_SERVER['HTTP_HOST'] . $this->config->client->routing_prefix . '/sites/default/files/dataset/'.$fileName.'.geojson';
					
					$url_resource = $site_search_url.'/query?where=1%3D1&text=&objectIds=&time=&geometry=&geometryType=esriGeometryEnvelope&inSR=&spatialRel=esriSpatialRelIntersects&relationParam=&outFields=*&returnGeometry=true&returnTrueCurves=false&maxAllowableOffset=&geometryPrecision=&outSR=&returnIdsOnly=false&returnCountOnly=false&orderByFields=&groupByFieldsForStatistics=&outStatistics=&returnZ=false&returnM=false&gdbVersion=&returnDistinctValues=false&resultOffset=&resultRecordCount=&queryByDistance=&returnExtentsOnly=false&datumTransformation=&parameterValues=&rangeValues=&f=geojson';
					error_log($url_resource);
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
								if((is_countable($res["attachmentInfos"]) ? count($res["attachmentInfos"]) : 0) > $nb_att){
									
									$cols[] = "attachment_" . $nb_att . "_name";
									$cols[] = "attachment_" . $nb_att . "_url";
									$nb_att++;
								}
								preg_match('/attachment_([\d]+)_name/i',$col, $matches);
								$c = floatval($matches[1]);
								//error_log(json_encode($c));
								if((is_countable($res["attachmentInfos"]) ? count($res["attachmentInfos"]) : 0) > $c){ 
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
								if($ftypes[$col] == "esriFieldTypeDate"){
									$row[] = '"'.date("d/m/Y H:i:s", intval($feat["properties"][$col])/1000).'"';
									//$record[strtolower($col)] = '"'.$feat["properties"][$col].'"';
								} else if($ftypes[$col] == "esriFieldTypeString"){
									$str = str_replace('"', "'", $feat["properties"][$col]);
									$row[] = '"'.$str.'"';
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
						$row = Tools::implode(";", $row);
					}
					
					$data_csv[] = strtolower(Tools::implode(";", $cols));
					$data_csv = array_merge($data_csv, $rows);
					$fileName = $fileName . "_" . uniqid();
					$rootCsv='/home/user-client/drupal-d4c' . $this->config->client->routing_prefix . '/sites/default/files/dataset/'.$fileName.'.csv';
					$urlCsv = 'https://'.$_SERVER['HTTP_HOST'] . $this->config->client->routing_prefix . '/sites/default/files/dataset/'.$fileName.'.csv';
					//$data_csv = mb_convert_encoding( $data_csv, 'Windows-1252', 'UTF-8');
					
					//$string = iconv('ASCII', 'UTF-8//IGNORE', Tools::implode($data_csv, "\n"));
					//error_log(mb_detect_encoding($string));
					// file_put_contents($rootCsv, utf8_encode(Tools::implode($data_csv, "\n")));
					$res = iconv("UTF-8", "Windows-1252//TRANSLIT", (Tools::implode("\n", $data_csv)));
					file_put_contents($rootCsv, $res);
					
					
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
					// $command = $pathUserClientData . '/geoloc.sh "' . $buildGeoloc . '" "' . $this->urlCkan . '" "' . $this->config->ckan->api_key . '" "' . $NewName . '" "' . $return["result"]["id"] . '" "' . $selectedSeparator . '" "' . $selectedEncoding . '" "' . $onlyOneAddress . '" "' . $selectedAddress . '" "' . $selectedPostalCode . '"';
					
					
				} else {
					
					$root='/home/user-client/drupal-d4c' . $this->config->client->routing_prefix . '/sites/default/files/dataset/'.$fileName.'.json';
					$url = 'https://'.$_SERVER['HTTP_HOST'] . $this->config->client->routing_prefix . '/sites/default/files/dataset/'.$fileName.'.json';
					
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
				$resourceManager->manageCSWXmlFile($org_id, $idNewData, $NewName);
				if($command != NULL){
					error_log($command);
					$output = shell_exec($command);
					error_log($output);
				}
			}
			date_default_timezone_set($tz);
			error_log(date_default_timezone_get());
        }
		


	}
    
    public function saveData($newData, $data){
        $coll = $data[0];
        
        //error_log(json_encode($newData));
        $api = new Api;
		$callUrlNewData = $this->urlCkan . "/api/action/package_create";
		$return = $api->updateRequest($callUrlNewData, $newData, "POST");
		$resnew = json_decode($return);
		$idNewData = $resnew->result->id;
		$NewTitle = $resnew->result->title;
		$NewName = $resnew->result->name;
                           
		if ($resnew->success == true) {
			\Drupal::messenger()->addMessage('La connaissance '.$resnew->result->title.' a bien été créé');
			$idNewData = $resnew->result->id;
			$NewTitle = $resnew->result->title;
			$NewName = $resnew->result->name;
		} 
		else if($resnew->error->name[0]=='Cette URL est déjà utilisée.'){
			$coll++;
			
			if($coll==1){
				$newData['name']=$newData['name'].'_'.$coll;
				$newData['title']=$newData['title'].' '.$coll;
				$NewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData, '2'=>$NewTitle));
				$idNewData = $NewData[1];
				$NewTitle = $NewData[2];
				$NewName = $NewData[3];
			}
			else if($coll>10){
				$newData['name']=substr($newData['name'],0, -3);
				$newData['name']=$newData['name'].'_'.$coll;
				$newData['title']=substr($newData['title'],0, -3);
				$newData['title']=$newData['title'].' '.$coll;
				$NewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData, '2'=>$NewTitle));
				$idNewData = $NewData[1];
				$NewTitle = $NewData[2];
				$NewName = $NewData[3];
			}
			else if($coll>100){
				$newData['name']=substr($newData['name'],0, -4);
				$newData['name']=$newData['name'].'_'.$coll;
				$newData['title']=substr($newData['title'],0, -4);
				$newData['title']=$newData['title'].' '.$coll;
				$NewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData, '2'=>$NewTitle));
				$idNewData = $NewData[1];
				$NewTitle = $NewData[2];  
				$NewName = $NewData[3];  
			}
			else if($coll>1000){
				$newData['name']=substr($newData['name'],0, -5);
				$newData['name']=$newData['name'].'_'.$coll;
				$newData['title']=substr($newData['title'],0, -5);
				$newData['title']=$newData['title'].' '.$coll;
				$NewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData, '2'=>$NewTitle));
				$idNewData = $NewData[1];
				$NewTitle = $NewData[2]; 
				$NewName = $NewData[3];   
			}
			else if($coll>10000){
				$newData['name']=substr($newData['name'],0, -6);
				$newData['name']=$newData['name'].'_'.$coll;
				$newData['title']=substr($newData['title'],0, -6);
				$newData['title']=$newData['title'].' '.$coll;
				$NewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData, '2'=>$NewTitle));
				$idNewData = $NewData[1];
				$NewTitle = $NewData[2];
				$NewName = $NewData[3];
			}
			else{
				$newData['name']=substr($newData['name'],0, -2);
				$newData['name']=$newData['name'].'_'.$coll;
				$newData['title']=substr($newData['title'],0, -2);
				$newData['title']=$newData['title'].' '.$coll;
				$NewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData, '2'=>$NewTitle));
				$idNewData = $NewData[1];
				$NewTitle = $NewData[2];
				$NewName = $NewData[3];
			}
		} 
		else {
			\Drupal::messenger()->addMessage('La connaissance '.$newData['title'].' n\'a pas été créé : '. json_encode($resnew->error), 'error');
		}
        
        return array('0'=>$coll, '1'=>$idNewData, '2'=>$NewTitle, '3'=>$NewName);
        
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
			
		//$str = utf8_decode($str);
				
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
		$str = str_replace("=", "_", $str);
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
			\Drupal::messenger()->addMessage("Ajout de la ressource ".$name . " : " . $out);
		} else {
			$out = json_encode($res["error"]);
			\Drupal::messenger()->addMessage("Ajout de la ressource ".$name . " : " . $out, 'error');
		}
	}
}