<?php
/**
 * @file
* Contains \Drupal\search_api_solr_admin\Form\QueryForm.
*/

namespace Drupal\ckan_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ckan_admin\Utils\Query;
use Drupal\ckan_admin\Utils\DataSet;
use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\ResourceManager;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\ckan_admin\Utils\HelpFormBase;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Drupal\Component\Render\FormattableMarkup; 
use Drupal\ckan_admin\Utils\Logger;



/**
 * Implements an example form.
 */
class PackageXMLFileForm extends HelpFormBase {
	
	protected $tiles;
	/**
	 * {@inheritdoc}
	 */
    
	public function getFormId() {
		return 'extension_package';
	}

public function buildForm(array $form, FormStateInterface $form_state) {
        $form = parent::buildForm($form, $form_state);
		

		// attach library to form
        $form['#attached']['library'][] = 'ckan_admin/PackagesForm.form';
        

        // get contents of config json file
		$this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
        $this->urlCkan = $this->config->ckan->url;

        // call api entity
        $api = new Api();
		
		$option_org=array();
		
		// pagination
		$page = pager_find_page();
		$num_per_page = 10;
		$offset = $num_per_page * $page;
		
		$filterQuery = "";
		if ($_GET["orga"] != "" || $_GET["q"] != "" || $_GET["type"] != "") {
			$filterQuery = "&q=";
			$qo = "";
			$qt = "";
			$qs = "";
			if($_GET["orga"] != ""){
				$qo = 'organization:"'.$_GET["orga"].'" AND ';
			}
			if($_GET["q"] != ""){
				$qs = 'text:"*'.strtolower($_GET["q"]).'*" AND ';
			}
			if($_GET["type"] != ""){
				$qt = $_GET["type"] == "private" ?  'private:"true" AND ' : 'private:"false" AND ';
			}
			$filterQuery .= $qo . $qs . $qt;
			if(strlen($filterQuery) > 5){
				$filterQuery = substr($filterQuery, 0, -5);
			}
			
		}

		$query = 'include_private=true&rows='.$num_per_page.'&start='.$offset.$filterQuery;
        $result = $api->callPackageSearch_public_private($query);

        $result = $result->getContent();
        $result = json_decode($result, true)[result];

        // get datasets
        $datasets = $result[results];
		
        //-------------------- Filter form ---------------------------
		$form['top'] = [
			'#type'  => 'container',
			'#attributes' => array(
				'style' => "height:37px;display:block",
			)
		];
		
		$form['filters'] = [
			'#type'  => 'details',
			'#title' => t('Filtres'),
			'#open'  => true,
		];

		// ---------------Filter by organisation-------------------------

		// get all organisation
		$orgas = $api->getAllOrganisations();
	
        foreach ($orgas as $value) {
            $option_org[$value["name"]] = $value["display_name"];
        }

		$form['filters']['selected_org'] = array(
            '#type' => 'select',
            '#title' => t('Organisation :'),
            '#options' => $option_org,
            '#empty_option' => t('----'),          
			'#attributes' => array(
				'style' => "display: inline-block;width: 50%;",
			),
			'#default_value' => $_GET["orga"]
        );
		//----------------End filter by organisaton --------------------------


		//---------------- filter by name of dataset --------------------------
		
        $form['filters']['selected_text'] = [
			'#title' => t('Recherche :'),
			'#type' => 'search',
			'#attributes' => array(
				'style' => "display: inline-block;width: 50%;",
			),
			'#default_value' => $_GET["q"]
		];

		//---------------- end filter by name of dataset --------------------------


		//---------------- filter by visibility of dataset --------------------------
		$form['filters']['selected_vis'] = array(
            '#type' => 'select',
            '#title' => t('Visibilité :'),
            '#options' => array('private'=>'Privé', 'public'=>'Public'),
            '#empty_option' => t('Tous'),  
			'#attributes' => array(
				'style' => "display: inline-block;width: 50%;",
			),
			'#default_value' => $_GET["type"]
        );
		//---------------- end filter by visibility of dataset --------------------------


		$form['filters']['actions'] = [
			'#type'       => 'actions'
		];

		//Submit button 
		$form['filters']['actions']['filter'] = [
			'#type'  => 'submit',
			'#value' => $this->t('Filter'),
			'#submit' => array([$this, 'submitfiltering'])
		];
        

        //Clear filter
        $form['filters']['actions']['clear'] = [
			'#type'  => 'submit',
			'#value' => $this->t('Effacer'),
			'#submit' => array([$this, 'submitclear'])
		];

//-------------------------End filter form -------------------------------------------------------

    $form['jdd'] = array(
	'#title' => t('Importer un JDD : '),
	'#type' => 'managed_file',
	'#upload_location' => 'public://dataset/',
	'#upload_validators' => array(
		'file_validate_extensions' => array('xls xlsx xml'),
	),
	'#required' => FALSE,
	'#size' => 10,
    '#suffix' => '</div>',
);

    $form['orga_selected_input'] = array(
            '#markup' => '',
            '#type' => 'textfield',
            '#attributes' => array('style' => 'width: 50%;'),
			'display' => nona,
			'#maxlength' => 300
		);


	$form['importer'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Importer'),
            '#attributes' => array(
				'style' => "margin-bottom: 20px !important;",
			),
        );
		

// -------------------------------------Show all datasets in table------------------------------- 

		// create modal export block
		$form['modalexport'] = array(
			'#markup' => '<div id="visibilityModalExport"></div>',
		); 

		// intialize pagination
		pager_default_initialize($result["count"], $num_per_page);
		
		//  create table header
		$header =  array(
			"name" => $this->t('Nom'),
			"orga" => $this->t('Organisation'),
			"last_modif" => $this->t("Dernière Modification"),  
			"export" => $this->t(''),    
		);

		$output = array();
		foreach ($datasets as $row) {

			$saveTimeZone = date_default_timezone_get();
			date_default_timezone_set('Europe/Paris');

			// create body table with data of datasets
			$uirow = [
				'name' => array('data' => array('#markup' => $row["title"])),
				'orga' => array('data' => array('#markup' => $row["organization"]["title"])),
				'last_modif' => array('data' => array('#markup' => date('Y-m-d H:i:s', strtotime($row["metadata_modified"] . " UTC")))),
				'export' => array('data' => new FormattableMarkup('<input id="exportdataset" type="button" onclick=":action" class="button" style="border-radius: 10px;font-size: 11px;padding: 4px 5px;" value=":name" data-id=":id" id=":id"/>', 
											[':action' => "openExportPopup(event,true)", 
											':name' => $this->t('Exporter'),
											
											':id' => $row["id"]])
				),
			];

			date_default_timezone_set($saveTimeZone);
			
			$output[] = $uirow;
			 
		}

		// create table
      	$form['table'] = array(
			'#type' => 'table',
			'#header' => $header,
			'#rows' => $output,
		);

// -------------------------------------end Show all datasets in table------------------------------- 
      	// pagination
		$form['pager'] = [
		  '#type' => 'pager',
		  '#tags' => array(t('« Première page'), t('‹ Page précédente'),"", t('Page suivante ›'), t('Dernière page »')),
		  '#submit' => array([$this, 'submitfiltering'])
		];



// -------------------------------------Add import button ------------------------------- 

	$form['valider'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Valider'),
        );
		return $form;
	}
    
    function gen_uuid() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,

        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}
    //submit form
	public function submitForm(array &$form, FormStateInterface $form_state)
	{

		$this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
        $this->urlCkan = $this->config->ckan->url;
        $api = new Api();
        $resourceManager = new ResourceManager;

        $orgavalue = $form_state->getValue('orga_selected_input');
        
        $organization="";
	    $orga = $api->getAllOrganisations();
		    foreach ($orga as $key => $value) {
		    	if($value["display_name"] == $orgavalue || $value["title"] == $orgavalue || $value["name"] == $orgavalue) {
		    		$organization = $value["id"];
		    	}	
		    }

        $dataset_file = $form_state->getValue('jdd', 0);
       if(sizeof($dataset_file) > 0 ) {
        	$resourceUrl = $resourceManager->manageFile($dataset_file[0]);

       
		$resourceUrl = str_replace('http://' . $_SERVER['HTTP_HOST'],$_SERVER['DOCUMENT_ROOT'], $resourceUrl);


		if (file_exists(urldecode($resourceUrl))) {
			$str=implode("\n",file(urldecode($resourceUrl)));
			$fp=fopen(urldecode($resourceUrl),'w');
				$str=str_replace('&','??',$str);
				$str=str_replace(':','',$str);
				fwrite($fp,$str,strlen($str));
				$xml = simplexml_load_file(urldecode($resourceUrl));

				$visu=0;
				$imgPicto = array();
				$imgPicto = $resourceManager->definePicto($imgPicto, $imgBack);
				$imgBackground = array();

				$imgBackground = $resourceManager->defineBackground($imgBackground);
				$removeBackground = 0;
				$removeBackground = isset($removeBackground);

				$widgets =  array();
				$widgets = $resourceManager->defineWidget($widgets);
				$analize_false = 0;
        		$api_false = 0;

        		$dont_visualize_tab = '';
		        if ($api_false == 1) {
					$dont_visualize_tab = $dont_visualize_tab . 'api;';
				}
		        if ($analize_false == 1) {
		            $dont_visualize_tab = $dont_visualize_tab . 'analize;';
				}

				$analyseDefault = "";

				$analyseDefault = $resourceManager->defineAnalyse($analyseDefault);

				$theme = "default%Default";
		        $theme = explode("%", $theme);
		        $themeLabel = $theme[1];
				$theme = $theme[0];
				$selectedTypeMap = "";

				$selectedOverlays = "";
				if ($selectedTypeMap != NULL) {
					$selectedOverlays = implode(",", array_keys(array_filter($form_state->getValue('authorized_overlays_map'))));
				}
				$linkDatasets = "";

				$linkDatasets = $resourceManager->defineLinkDatasets($linkDatasets);
				$private = 0;
				if ($private == '1') {
				$isPrivate = true;
				} 
				else {
					$isPrivate = false;
				}
				$tags = array();
				$userId = "*" . \Drupal::currentUser()->id() . "*";
				$users = \Drupal\user\Entity\User::loadMultiple();
				$title="";
				$datasetName="";
				$description = "";
				$licence ="";
				$disableFieldsEmpty = 1;
				$generatedTaskId = $this->gen_uuid();
				$resourceUrlval="";
				$generateColumns =0;
				$unzipZip =0;
				$encoding ="UTF-8";
				$validata ="non_valider";

			/*	foreach ($xml as $key => $value) {
					echo "<pre>";
					if($key == "gmdidentificationInfo") {
						$title = $value->gmdMD_DataIdentification->gmdcitation->gmdCI_Citation->gmdtitle->gcoCharacterString->__toString();
						$datasetName = $resourceManager->defineDatasetName($title);
						var_dump($datasetName);
					}
					echo "</pre>";

				}die;*/

				foreach ($xml as $key => $value) {
		

					if($key == "gmdidentificationInfo") {
						
						$title = $value->gmdMD_DataIdentification->gmdcitation->gmdCI_Citation->gmdtitle->gcoCharacterString->__toString();
						$datasetName = $resourceManager->defineDatasetName($title);
						$datasetName = str_replace(".", "-", $datasetName);
						$description = $value->gmdMD_DataIdentification->gmdabstract->gcoCharacterString->__toString();
						

						

						foreach ($value->gmdMD_DataIdentification->gmdcitation->gmdCI_Citation->gmddate as $key3 => $value3) {
							
						if($value3->gmdCI_Date->gmddateType->gmdCI_DateTypeCode->__toString() == "creation") {
							
							$dateDataset = $value3->gmdCI_Date->gmddate->gcoDate->__toString();
						}
						}

						foreach ($value->gmdMD_DataIdentification->gmdresourceConstraints as $key2 => $value2) {
	
							if($value2->gmdMD_LegalConstraints->gmduseConstraints->gmdMD_RestrictionCode != null ){
								$licence = $value2->gmdMD_LegalConstraints->gmduseConstraints->gmdMD_RestrictionCode->__toString();
								
							}

						}

					}

					/*if($key == "gmdcontact") {
						$organization = $value->gmdCI_ResponsibleParty->gmdorganisationName->gcoCharacterString->__toString();

					}*/

					if($key == "gmddistributionInfo") {
						
						/*$resourceUrlval = urldecode($value->gmdMD_Distribution->gmdtransferOptions->gmdMD_DigitalTransferOptions->gmdonLine[0]->gmdCI_OnlineResource->gmdlinkage->gmdURL->__toString());

						echo "<pre>";*/
						/*var_dump($value->gmdMD_Distribution->gmdtransferOptions->gmdMD_DigitalTransferOptions);*/
						foreach ($value->gmdMD_Distribution->gmdtransferOptions->gmdMD_DigitalTransferOptions->gmdonLine as $k => $f) {
				

							if($f->gmdCI_OnlineResource->gmdname->gcoCharacterString->__toString() == 'csv' || $f->gmdCI_OnlineResource->gmdname->gcoCharacterString->__toString() == 'CSV' ){
								
								$resourceUrlval = urldecode($f->gmdCI_OnlineResource->gmdlinkage->gmdURL->__toString());

							}
							
						}
						

					
						$resourceUrlval = $resourceManager->manageXmlfile($resourceUrlval);
				/*		var_dump($resourceUrlval);die;*/

						/*var_dump($resourceUrlval);die;*/
						// $newfile="";
						// $filepathContent = file_get_contents($resourceUrlval);
						
						//  $delimiter = $api->getFileDelimiter($resourceUrlval);
					
						//  $arr1 =file($resourceUrlval);
			   //          $arr = array();
			   //          $a=15;
			            
			   //          if(count($arr1)<15){
						// 	$a=count($arr1);
						// }

			   //          for($i=0; $i<$a; $i++){

						// 	$text = $arr1[$i];
						// 	$arr[$i] = iconv(mb_detect_encoding($text, mb_detect_order(), true), "UTF-8", $text);
			   //          }

			   //          if($arr[0]==null || $arr[0]==''){
						// $arr[0]="Pas d'accès de ligne ou de colonne aux tables non tabulaires";
						// }

						// $resourceUrlval = str_replace('https://' . $_SERVER['HTTP_HOST'],$_SERVER['DOCUMENT_ROOT'], $resourceUrlval);
		    //         	$fp = fopen($resourceUrlval, 'wb');
						// foreach ( $arr as $line ) {
						//     $val = explode($delimiter, $line);
						//     foreach ($val as $key22 => $value22) {
						//     	$value22 = str_replace('","', ',', $value22);
						//     	$val[$key22] = trim($value22, '"');
						    	
						//     }
						//     fputcsv($fp, $val);
						// }
						// fclose($fp);
						// $resourceUrlval = str_replace($_SERVER['DOCUMENT_ROOT'],'https://' . $_SERVER['HTTP_HOST'], $resourceUrlval);
						/*if (strpos(file_get_contents($resourceUrlval), ';') !== false) {
							

							$commaReplace = str_replace(";",",",$filepathContent);
							$commaReplace = str_replace('"','',$filepathContent);
							
							$pathinfo = pathinfo($resourceUrlval);

							$pathfiles = explode("/", $resourceUrlval);
							
							foreach ($pathfiles as $key => $value) {

								if($key == 0) {
									$newfile= $value;
								}
								else {
									$newfile .="/".$value;
								}
								
							}
							//create a new csv files contains the same content of text file
							file_put_contents($newfile, $commaReplace);
							$resourceUrlval = str_replace($_SERVER['DOCUMENT_ROOT'],'https://' . $_SERVER['HTTP_HOST'], $newfile);
						
						}
*/



					}
					
				
				}
				$extras = $resourceManager->defineExtras(null, $imgPicto, $imgBackground, $removeBackground, $linkDatasets, $theme, $themeLabel,
							$selectedTypeMap, $selectedOverlays, $dont_visualize_tab, $widgets, $visu, 
							$dateDataset, $disableFieldsEmpty, $analyseDefault, $security);
				
							drupal_set_message("Le jeu de données '" . $datasetName ."' a été créé.");
				       

				$datasetId = $resourceManager->createDataset($generatedTaskId, $datasetName, $title, $description, $licence, $organization, $isPrivate, $tags, $extras);
			
				$this->manageResource($api, $resourceManager, $datasetId, null, $resourceUrlval, $generateColumns, false, '', $encoding, $validata, $unzipZip);

		}
        }
		$callUrl = $this->urlCkan . "/api/action/package_update";
		$return = $api->updateRequest($callUrl, $oldDataset, "POST");
       
	}

	function manageResource($api, $resourceManager, $datasetId, $resourceId, $resourceUrl, $generateColumns, $isUpdate, $description, $encoding, $validata, $unzipZip) {
		$validataResources = array();

		$results = $resourceManager->manageFileWithPath($datasetId, $generateColumns, $isUpdate, $resourceId, $resourceUrl, $description, $encoding, $unzipZip);

		foreach ($results as &$result) {

			foreach ($result as $key => $value) {
				if ($value['status'] == 'complete') {
					if ($value['type'] == 'DATAPUSHER') {
						$validataResources[] = $value['resourceUrl'];

						drupal_set_message("La ressource '" . $value['filename'] ."' a été ajouté sur le jeu de données.");
					}
					else if ($value['type'] == 'CLUSTER') {
						drupal_set_message("Les clusters ont été générés.");
					}
				}
				else if ($value['status'] == 'pending') {
					$validataResources[] = $value['resourceUrl'];

					drupal_set_message("La ressource '" . $value['filename'] ."' est en cours d'insertion dans l'application, le processus peut durer quelques minutes en fonction de la taille du fichier.", 'warning');
				}
				else if ($value['status'] == 'error') {
					if ($value['type'] == 'DATAPUSHER') {
						drupal_set_message("Une erreur est survenue lors de l'ajout de '" . $value['filename'] . "' (" . $value['message'] . ")", 'error');
					}
					else if ($value['type'] == 'CLUSTER') {
						drupal_set_message("Une erreur est survenue lors de la création des clusters (" . $value['message'] . ")", 'error');
					}
				}
			}
		}

		// We validate the data, if the user ask for it (put it in ResourceManager someday)
		if ($validata != "non_valider") {
	
			for ($v=0; $v < count($validataResources); $v++) {

				$validataUrl = "https://go.validata.fr/api/v1/validate?schema=https://git.opendatafrance.net/scdl/deliberations/raw/master/schema.json&url=" . $validataResources[$v];
				$validataResult = $resourceManager->validateData($validataUrl);

				if ($validataResult[report][valid] == false) {
					$errorsValid = $validataResult[report][tables][0][errors];
					for ($i = 0; $i < count($errorsValid); $i++) {
						
						drupal_set_message(t(($i + 1) . '. Code:' . $errorsValid[$i][code] . ' | Message:' . $errorsValid[$i][message]), 'warning');
						
						if($i>5){
							break;
						}
					}
				} 
				else if ($validataResult[report][valid] == true) {
					drupal_set_message('Les données ont été validées');
				}
			}
		}

		//We update the visualisation's icons
		$api->calculateVisualisations($datasetId);
	}

	// filter function
	public function submitfiltering(array &$form, FormStateInterface $form_state){ 
		// Set the provided filter value in the storage.
		$filters = array();
		$filters["orga"] = $form_state->getValue("selected_org");
		$filters["q"] = $form_state->getValue("selected_text");
		$filters["type"] = $form_state->getValue("selected_vis");
		$url = Url::fromRoute('ckan_admin.extension_package_fileXML', [], ['query' => ["page" => 0, 'orga' => $filters["orga"], 'q' => $filters["q"], 'type' => $filters["type"]]]);
		$form_state->setRedirectUrl($url);
	}

	// clear filter function
	public function submitclear(array &$form, FormStateInterface $form_state){ 
		// Set the provided filter value in the storage.
		$filters = array();
		$filters["orga"] = "";
		$filters["q"] = "";
		$filters["type"] = "";
		$url = Url::fromRoute('ckan_admin.extension_package_fileXML', [], ['query' => ["page" => 0, 'orga' => $filters["orga"], 'q' => $filters["q"], 'type' => $filters["type"]]]);
		$form_state->setRedirectUrl($url);
	}

    

}
