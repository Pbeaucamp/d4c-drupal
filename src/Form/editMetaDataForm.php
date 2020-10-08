<?php
/**
 * @file
 * Contains \Drupal\search_api_solr_admin\Form\QueryForm.
 */

namespace Drupal\ckan_admin\Form;

use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\ResourceManager;
use Drupal\ckan_admin\Utils\Query;
use Drupal\ckan_admin\Utils\Export;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use \PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use \PhpOffice\PhpSpreadsheet\Reader\Xls;
use \PhpOffice\PhpSpreadsheet\Writer\Csv;
use Drupal\ckan_admin\Utils\HelpFormBase;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Drupal\Core\Url;
use Drupal\ckan_admin\Utils\Logger;

/**
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
 
 */

class editMetaDataForm extends HelpFormBase {

    public function getFormId() {
        return 'editMetaDataForm';
    }

    function dummy_preprocess_page(&$variables) {
		if (\Drupal::service('path.matcher')->isFrontPage()) {
			$variables['#attached']['library'][] = 'ckan_admin/editMetaDataFormModal.form';
		}
	}
    
    public function buildForm(array $form, FormStateInterface $form_state){


		$form = parent::buildForm($form, $form_state);
      
		// $form['#attached']['library'][] = 'ckan_admin/iconpicker.form';

        $form['#attached']['library'][] = 'ckan_admin/editMetaDataForm.form';
        $form['#attached']['library'][] = 'ckan_admin/editMetaDataFormModal.form';
        $this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
        $this->urlCkan = $this->config->ckan->url;

        $api = new Api;

        // $dataSet = $api->callPackageSearch_public_private('include_private=true&rows=1000&sort=title_string asc', \Drupal::currentUser()->id());
        // $dataSet = $dataSet->getContent();
		// $dataSet2 = json_encode($dataSet, true);

        // $dataSet = json_decode($dataSet, true);
        // $dataSet = $dataSet[result][results];
		$selectedDatasetId = \Drupal::request()->query->get('id');
		if ($selectedDatasetId) {
			Logger::logMessage("Selected dataset id " . $selectedDatasetId);
			$selectedDataset = $api->getPackageShow2($selectedDatasetId, null);
			$selectedDataset = $selectedDataset['metas'];

			//We simulate the previous process - We have to encode it twice
			$selectedData = array();
			$selectedData[result][results][] = $selectedDataset;
			$selectedData = json_encode($selectedData);
			$selectedData = json_encode($selectedData, true);
		}
		
		// uasort($dataSet, function($a, $b) {
		// 	$res =  strcasecmp($a['title'], $b['title']);
		// 	return $res;
		// });
		
		///////////////////////////////organization_list////

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

        $callUrlOrg = $this->urlCkan . "api/action/organization_list?all_fields=true";
        $curlOrg = curl_init($callUrlOrg);
		//error_log($callUrlOrg, true);
        curl_setopt_array($curlOrg, $optionst);
        $orgs = curl_exec($curlOrg);
        curl_close($curlOrg);
        $orgs = json_decode($orgs, true);
        
		///////////////////////////////organization_list////

		///////////////////////////////license_list////
           
        $callUrllic = $this->urlCkan . "api/action/license_list";
        $curllic = curl_init($callUrllic);

        curl_setopt_array($curllic, $optionst);
        $lic = curl_exec($curllic);

        curl_close($curllic);
        $lic = json_decode($lic, true);

		///////////////////////////////license_list////

        $ids = array();



		$ids["new"] = "Сréer un jeu de données";
		if ($selectedDataset) {
			Logger::logMessage("Set dataset with id " . $selectedDataset[id] . " and name " . $selectedDataset[name]);
			$ids[$selectedDataset[id]] = $selectedDataset[title];
		}
        // foreach($dataSet as &$ds) {
        //     $ids[$ds[id]] = $ds[title];
        // }

         

        $organizationList = array();
        $organizationList2 = array();

        foreach ($orgs[result] as &$value) {
            $organizationList[$value[id]] = $value[display_name];
            $organizationList2[$value[name]] = $value[display_name];
        }


		
        $licList = array();

        foreach ($lic[result] as &$value) {
            $licList[$value[id]] = $value[title];

        }

		///// themes /////

        $config = \Drupal::service('config.factory')->getEditable('ckan_admin.addThemeInDatasetForm');
        //$form['#attached']['library'][] = 'ckan_admin/addThemeInDatasetForm';

        $themConfig = \Drupal::service('config.factory')->getEditable('ckan_admin.themeForm');

        $t = $themConfig->get('themes');

        $themes = json_decode($t);

        
        $valuesForSelect = array();
        foreach ($themes as &$value) {
            $valuesForSelect[$value->title.'%'.$value->label] = $value->label;
        }
        
	
        
		$form['m0'] = array(
			'#markup' => '<div id="filters">',
		);  

		
        $form['filtr_org'] = array(
            //'#prefix' =>'',
            '#type' => 'select',
            '#title' => t('Organisation :'),
            '#options' => $organizationList2,
            '#empty_option' => t('----'),
            '#attributes' => array('style' => 'width: 50%;','onchange' => 'clear();'),
            '#ajax'         => [
                'callback'  => '::datasetCallback',
                'wrapper'   => 'selected_data',
			],
        );

        
        
		$form['selected_data'] = array(
            '#type' => 'select',
            '#title' => t('*Sélectionner un jeu de données :'),
            '#options' => $ids,
            '#attributes' => array(
                'onchange' => 'addData('.$selectedData.')','style' => 'width: 50%;', 
                'id' => ['selected_data'])
        );
        
        $form['selected_data_id'] = array(
            '#type' => 'textfield',
            '#attributes' => array('style' => 'display:none'),
		);
		
		$form['generated_task_id'] = array(
            '#type' => 'textfield',
            '#attributes' => array('style' => 'display:none'),
        );
		
		$form['m0_2'] = array(
			'#markup' => '</div>',
		); 
		
		//////////////////INFORMATION GENERALE/////////////////////////////////////////        
        $form['m1'] = array(
			'#markup' => '<div id="infoTab">',
		); 
		


        $form['title'] = array(
            '#markup' => '',
            '#type' => 'textfield',
            '#title' => $this->t('Titre :'),
            '#attributes' => array('style' => 'width: 50%;'),
			'#required' => TRUE,
			'#maxlength' => 300
		);
		
        $form['progress-modal'] = array(
			'#markup' => '<div id="progress" class="progress-modal" display="none">
			</div>',
		);
   
        
        $form['img_backgr'] = array(
            '#type' => 'managed_file',
            '#title' => t("L'image de fond du jeu de données :"),
            '#upload_location' => 'public://dataset/',
            '#upload_validators' => array(
                'file_validate_extensions' => array('jpeg png jpg svg gif WebP PNG JPG JPEG SVG GIF'),
            ),
            '#size' => 22,
        );
		
		$form['del_img'] = array(
			'#type' => 'checkbox',
			'#title' => $this->t('Supprimer l\'image de fond'),
		);
        
        $form['description'] = array(
            '#type' => 'textarea',
            '#title' => $this->t('Description :'),
            '#resizable' => true,
            '#attributes' => array('style' => 'width: 50%;'),

		);

		$form['date_dataset'] = array(
            '#type' => 'date',
            '#title' => $this->t('Date du jeu de données'),
            '#date_date_format' => 'd/m/Y'
        );
        
        $form['tags'] = array(
            '#type' => 'textarea',
            '#title' => $this->t('Mots-clés (séparer par des virgules, les seuls symboles autorisés sont -"_"):'),
            '#attributes' => array('style' => 'width: 50%; height: 2em;'),

        );

        $form['selected_lic'] = array(
            '#type' => 'select',
            '#title' => t('*Licence :'),
            '#options' => $licList,
            '#empty_option' => t('----'),
            '#attributes' => array('style' => 'width: 50%;'),
			 '#required' => TRUE,

        );

        $form['selected_org'] = array(
            '#type' => 'select',
            '#title' => t('*Organisation :'),
            '#options' => $organizationList,
            '#empty_option' => t('----'),
            '#attributes' => array('style' => 'width: 50%;'),
			 '#required' => TRUE,

        );

        $form['selected_private'] = array(
            '#type' => 'select',
            '#title' => t('*Visibilité :'),
            '#options' => array('Publique', 'Privée'),
            '#attributes' => array('style' => 'width: 50%;'),
        );
		
		
        $form['selected_visu'] = array(
            '#type' => 'select',
            '#title' => t('*Visuallisation par défaut :'),
            '#options' => array('Informations', 'Tableau', 'Analyse', 'Carte', 'Vues personalisées', 'Frise', 'Calendrier', 'Nuage de mots'),
            '#attributes' => array('style' => 'width: 50%;'),
        );
        

        $form['selected_theme'] = array(
            '#type' => 'select',
            '#title' => t('Choisir un thème :'),
            '#options' => $valuesForSelect,
            '#default_value' => t('default'),
            '#attributes' => array('style' => 'width: 50%;'),

        );
        
        $form['analyse_default'] = array(
            '#prefix' => '<div id="analyse_def_div" >',
            '#type' => 'textarea',
            '#title' => $this->t('Analyse par défaut :'),
            '#resizable' => true,
            '#attributes' => array('style' => 'width: 50%; height: 2em;'),
            '#suffix' => '</div>',
        
        );
        
      
        $form['analize_false'] = array(
			'#type' => 'checkbox',
			'#title' => $this->t('Ne pas afficher les analyses'),
		);
        
        $form['api_false'] = array(
			'#type' => 'checkbox',
			'#title' => $this->t('Ne pas afficher les API'),
		);

        $form['resours'] = array(
			'#title' => t('Nouvelles ressources : '),
			'#type' => 'managed_file',
			'#upload_location' => 'public://dataset/',
			'#upload_validators' => array(
				'file_validate_extensions' => array('jpg jpeg gif png txt doc xls pdf ppt pps odt ods odp csv json xls xlsx geojson zip'),
			),
			'#size' => 10,
            '#suffix' => '</div>',
		);
		
		$form['unzip_zip'] = array(
			'#type' => 'checkbox',
			'#title' => $this->t('Décompresser les fichiers ZIP'),
		); 
		
		$form['generate_cols'] = array(
			'#type' => 'checkbox',
			'#title' => $this->t('Générer des noms de colonnes (pour CSV ou XLS)'),
		); 
		
        $form['encoding'] = array(
            '#type' => 'textfield',
			'#title' => $this->t('Encoding :'),
            '#default_value' => t('UTF-8'),
            '#attributes' => array('style' => 'width: 50%;'),
			'#required' => FALSE
		);
		
		
		$form['text_message1'] = [
			'#prefix' => '<p>',
			'#suffix' => '</p>',
			'#markup' => $this->t('Au lieu d\'ajouter un fichier, vous pouvez renseigner une URL Google Sheets afin de créer un fichier CSV'),
		];

        $form['url_Gsheet'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('Saisir une url Google Sheets :'),
			'#attributes' => array('style' => 'width: 50%;'),
			'#maxlength' => null,
        );
        
		// $form['#suffix'] = '</div>';
        
		$form['m1_2'] = array(
		  '#markup' => '</div>',
		);         
			
		//////////////////INFORMATION GENERALE/////////////////////////////////////////          
				
		/////////////////CARTOGRAPHIE///////////////////////////////////////////////////
        
      
		$form['m2'] = array(
			'#markup' => '<div id="cartoTab">',
		);
        
		$layers = $api->getMapLayers("tile");
		$overlays = $api->getMapLayers("layer");
		$values = array();

		foreach($layers['layers'] as $layer){
			$values[$layer["name"]] = $layer["label"];
		}
        $form['selected_type_map'] = array(
            //'#prefix' =>'<div id="CartoTab">',
			'#type' => 'select',
			'#title' => t('Fond de carte:'),
			/*'#options' => array("opencycle" => "opencycle","osmtransport" => "osmtransport","mapbox." => "mapbox.","mapbox" => "mapbox","osm" => "osm","stamen." => "stamen.","jawg." => "jawg.","mapquest" => "mapquest","custom" => "custom"),*/
			'#options' => $values,
			'#empty_option' => t('----'),
            '#attributes' => array('style' => 'width: 50%;'),
            
		);

		
		$values = array();
		foreach($overlays['layers'] as $layer){
			$values[$layer["name"]] = $layer["label"];
		}
        $form['authorized_overlays_map'] = array(
			'#title' => t('Couches intermédiaires disponibles'),
			'#type' => 'checkboxes',
			'#options' => $values,
			'#attributes' => array('style' => 'overflow: auto;max-width: 50%;max-height: 150px;'),
		);

        $form['img_picto'] = array(
            '#type' => 'managed_file',
            '#title' => t('Pictogramme à utiliser sur la carte :'),
            '#upload_location' => 'public://theme_logo/',
            '#upload_validators' => array(
                'file_validate_extensions' => array('svg'),
            ),
            '#size' => 22,
            //'#suffix' => '</div>',
        );        
		
		$form['pictos'] = array(
			'#markup' => '<div ng-app="d4c.frontend" id="app">
							<label for="app">ou un pictogramme : </label>
							<d4c-pictopicker ng-model="theme" default-color="#E5E5E5"></d4c-pictopicker>
							<!--<div id="btnImgHide"></div>-->
							<div id= "old_img"></div><br>
							<div style=" overflow:scroll; height:15em; overflow-x: hidden; display: none;  width: 30%;" id="pickImg"></div>
							
						</div></br>
							<script type="text/javascript">
							
						</script>',
			'#allowed_tags' => ['label', 'div', 'd4c-pictopicker', 'br', 'script']
		);
        


         $form['disable_fields_map'] = array(
            '#type' => 'checkbox',
            '#id'=> 'disable_fields_empty',
            '#title' => $this->t('Cacher les champs vides'),
        );
        
        
		//$form['#suffix'] = '</div>';   
          
		$form['m2_2'] = array(
			'#markup' => '</div>',
		);        
      
		/////////////////CARTOGRAPHIE///////////////////////////////////////////////////        
        
        
		////////////////RESSOURCES ET VALIDATION///////////////////////////////////////

		$form['m3'] = array(
			'#markup' => '<div id="resEtValidTab">',
		);       
		$form['validata'] = array(
            '#prefix' =>'<div id="resAndValidTab">',
            '#type' => 'select',
            '#title' => t('Valider les jeux de données : '),
            '#options' => array("non_valider" => "Non validé", "valider" => "Validé"),
            '#attributes' => array('style' => 'width: 50%;'),

        );
        
        
		// table form

        $form['table'] = array(
            
            '#type' => 'table',

            '#header' => array(
                $this->t('Titre'),
                $this->t('Description'),
                $this->t('Données'),
                $this->t('Encoding'),
                $this->t('Mettre à jour'),
                $this->t('Supprimer'),
            ),
            
            
            '#suffix' => '</div>',

        );

        for ($i = 1; $i <= 20; $i++) {
			//titre
            $form['table'][$i]['name'] = array(
                '#type' => 'textfield',
                '#size' => 30,
                '#maxlength' => null,
			);
			
			//description
            $form['table'][$i]['description'] = array(
                '#type' => 'textarea',
                '#attributes' => array('style' => 'height: 5em;width: 25em;'),
                '#maxlength' => null,

            );

            $form['table'][$i]['donnees'] = array(
                '#type' => 'textarea',
                '#attributes' => array('style' => 'height: 2em;width: 19em;'),
            );


            $form['table'][$i]['encoding'] = array(
                '#type' => 'textfield',
                '#size' => 30
            );
			

			
			$form['table'][$i]['file'] = array(
				'#type' => 'managed_file',
				'#upload_location' => 'public://dataset/',
				'#upload_validators' => array(
					'file_validate_extensions' => array('jpg jpeg gif png txt doc xls pdf ppt pps odt ods odp csv json xls xlsx geojson zip'),
				),
				'#size' => 1,
            );
			
			$form['table'][$i]['editer'] = array(
                '#type' => 'textfield',
                '#maxlength' => null,
                '#attributes' => array('style' => 'display: none;'),

            );

			// supprimer
            $form['table'][$i]['status'][1] = array(
                '#type' => 'checkbox',
                '#maxlength' => null,

            );
            $form['table'][$i]['status'][2] = array(
                '#type' => 'checkbox',
                '#maxlength' => null,

            );
            $form['table'][$i]['status'][3] = array(
                '#type' => 'textfield',
                '#maxlength' => null,
                '#attributes' => array('style' => 'display: none;'),

            );
			
			$form['table'][$i]['donnees_old'] = array(
                '#type' => 'textarea',
                '#attributes' => array('style' => 'height: 2em;width: 19em;display:none;'),
            );

        }        
        
		$form['m3_2'] = array(
			'#markup' => '</div>',
		);         
        
		////////////////RESSOURCES ET VALIDATION///////////////////////////////////////        

        
        
        
		////////////////CONFIGURATION///////////////////////////////////////
      
		$form['oku'] = array(
			'#markup' => '<div id="formModal"></div>',
		);              
        
        
		$form['m4'] = array(
			'#markup' => '<div id="configurationTab">',
		);
	  
		$form['table_widgets'] = array(
			
			//'#prefix' =>'<div id="ConfigurationTab">',
			'#type' => 'table',
			'#header' => array(
				$this->t('Titre'),
				$this->t('Description'),
				$this->t('Widget/URL'),
				$this->t('Désactiver'),
				$this->t('Supprimer')  
			),
			//'#suffix' => '</div>',
		);
        
        
        for ($i = 1; $i <= 1; $i++) {
			//titre
            $form['table_widgets'][$i]['name'] = array(
                '#type' => 'textfield',
                '#size' => 30,
                '#maxlength' => null,
			);
			
			//description
            $form['table_widgets'][$i]['description'] = array(
                '#type' => 'textarea',
                '#attributes' => array('style' => 'height: 5em;width: 25em;'),
                '#maxlength' => null,
            );


            
            $form['table_widgets'][$i]['widget'] = array(
            '#type' => 'textarea',
                '#attributes' => array('style' => 'height: 5em;width: 25em;'),
                '#maxlength' => null,

        	);
            
            $form['table_widgets'][$i]['offWidjet'] = array(
                '#type' => 'checkbox',
            );
            
            $form['table_widgets'][$i]['del'] = array(
            	//'#type' => 'textarea',
        	);
    	}
        
		$form['m4_2'] = array(
		'#markup' => '</div>',
		);       
           
		////////////////CONFIGURATION/////////////////////////////////////// 
        
        
		////////////////Jeux de donnees lies///////////////////////////////////////

		$form['m5'] = array(
			'#markup' => '<div id="datasetLies" >',
    	); 
        
		$form['dataset_lies'] = array(
			'#type' => 'textfield',
			//'#title' => $this->t('Dataset liés:'),
			'#attributes' => array('style' => 'width: 50%; display: none;'),
		);

		$form['Dataset_lies_table'] = array(
			'#type' => 'table',
			//'#prefix' => '<div id="datasetLies" >',
			'#header' => array(
				$this->t('Jeux de données liés'),
			),
			'#attributes' => array('style' => 'width: 100%;'),
			//'#attributes' => array('style' =>'display: none'),
			//            '#suffix' => '</div>',
		);

		foreach ($dataSet as &$value) {
			$form['Dataset_lies_table'][$value[name] . ':' . $value[id]]['dt'] = array(
				'#prefix' => '<div id="id_row_'.$value[id].'" >',
				'#type' => 'checkbox',
				'#title' => $this->t($value[title]),
				'#suffix' => '</div>',

			);
		}
        
        
		$form['m5_2'] = array(
		'#markup' => '</div>',
		);

		////////////////Jeux de donnees lies///////////////////////////////////////        
		$form['valider'] = array(
			'#type' => 'submit',
			'#value' => $this->t('Valider'),
		);
		
		$form['del_button_dataset'] = array(
			'#type' => 'submit',
			'#value' => $this->t('Supprimer'),
			'#attributes' => array('style' => 'color: #fcfcfa; background:#e1070799;'),
		);
		
		
		$form['del_dataset'] = array(
				'#type' => 'checkbox',
				'#attributes' => array('style' => 'display: none;'),
		);

		$form['imgBack'] = array(

			'#type' => 'textarea',
			'#attributes' => [
				'style' => 'display: none',
			],

		);

        return $form;
	}



    public function submitForm(array &$form, FormStateInterface $form_state) {
		$userId = "*" . \Drupal::currentUser()->id() . "*";
		$users = \Drupal\user\Entity\User::loadMultiple();

		$api = new Api;
		$resourceManager = new ResourceManager;
        

        $title = $form_state->getValue('title');
        $datasetId = $form_state->getValue('selected_data_id');
        $generatedTaskId = $form_state->getValue('generated_task_id');
        $description = $form_state->getValue('description');
        $dateDataset = $form_state->getValue('date_dataset');
        $tags = $form_state->getValue('tags');
        $licence = $form_state->getValue('selected_lic');
        $organization = $form_state->getValue('selected_org');
        $private = $form_state->getValue('selected_private');
		$visu = $form_state->getValue('selected_visu');
		$disableFieldsEmpty = $form_state->getValue('disable_fields_map');
		$imgBack = $form_state->getValue('imgBack');
		$encoding = $form_state->getValue('encoding');
		$generateColumns = $form_state->getValue('generate_cols');
		$unzipZip = $form_state->getValue('unzip_zip');
		
		// Resources part
		$table_data = $form_state->getValue('table');
		$validata = $form_state->getValue('validata');
		$resources = $form_state->getValue('resours', 0);
        $urlGsheet = $form_state->getValue('url_Gsheet');

		// Define Dataset name
		$datasetName = $resourceManager->defineDatasetName($title);

		// Define picto
		$imgPicto = $form_state->getValue('img_picto');
		$imgPicto = $resourceManager->definePicto($imgPicto, $imgBack);
		
		// Define background
		$imgBackground = $form_state->getValue('img_backgr');
		$imgBackground = $resourceManager->defineBackground($imgBackground);
		
		//Check if the user wants to delete the background
		$removeBackground = $form_state->getValue('del_img');
		$removeBackground = isset($removeBackground);
		
		// Define widgets
		$widgets = $form_state->getValue('table_widgets');
		$widgets = $resourceManager->defineWidget($widgets);
		
		// Define analyse and API
        $analize_false = $form_state->getValue('analize_false');
        $api_false = $form_state->getValue('api_false');

        $dont_visualize_tab = '';
        if ($api_false == 1) {
			$dont_visualize_tab = $dont_visualize_tab . 'api;';
		}
        if ($analize_false == 1) {
            $dont_visualize_tab = $dont_visualize_tab . 'analize;';
		}

		$analyseDefault = $form_state->getValue('analyse_default');
		$analyseDefault = $resourceManager->defineAnalyse($analyseDefault);
		
		// Define theme
        $theme = $form_state->getValue('selected_theme');
        $theme = explode("%", $theme);
        $themeLabel = $theme[1];
		$theme = $theme[0];
		
		// Define maps and overlays
        $selectedTypeMap = $form_state->getValue('selected_type_map');
		$selectedOverlays = "";
		if ($form_state->getValue('authorized_overlays_map') != NULL) {
			$selectedOverlays = implode(",", array_keys(array_filter($form_state->getValue('authorized_overlays_map'))));
		}

		// Define link dataset
		$linkDatasets = $form_state->getValue('Dataset_lies_table');
		$linkDatasets = $resourceManager->defineLinkDatasets($linkDatasets);

		// Define if it is private
		if ($private == '1') {
			$isPrivate = true;
		} 
		else {
			$isPrivate = false;
		}

		// Define tags
		$tags = $resourceManager->defineTags($tags);

		// Define security
		$security = $resourceManager->defineSecurity($userId, $users);

		try {
			$deleteDataset = $form_state->getValue('del_dataset');
			if ($deleteDataset) {
				if ($resourceManager->deleteDataset($datasetId)) {
					drupal_set_message(t('Le jeu de données a été supprimé!'), 'warning');
					$datasetId = null;
				}
			}
			else {
				if ($datasetId == 'new') {
					// We build extras
					$extras = $resourceManager->defineExtras(null, $imgPicto, $imgBackground, $removeBackground, $linkDatasets, $theme, $themeLabel,
						$selectedTypeMap, $selectedOverlays, $dont_visualize_tab, $widgets, $visu, 
						$dateDataset, $disableFieldsEmpty, $analyseDefault, $security);
					
					$datasetId = $resourceManager->createDataset($generatedTaskId, $datasetName, $title, $description, $licence, $organization, $isPrivate, $tags, $extras);

					drupal_set_message("Le jeu de données '" . $datasetName ."' a été créé.");

					//Managing resources
					$this->manageFileResource($api, $resourceManager, $datasetId, null, $resources, $generateColumns, false, $encoding, $validata, $urlGsheet, $unzipZip);
				}
				else {
					//Fow now we use the old system but after we should look the dataset by ID
					$datasets = $api->callPackageSearch_public_private('include_private=true&rows=1000&sort=title_string%20asc');
					$datasets = $datasets->getContent();
					$datasets = json_decode($datasets, true);
					$datasets = $datasets[result][results];

					$datasetToUpdate = null;
					foreach ($datasets as &$value) {
						if ($value[id] == $datasetId) {
							$datasetToUpdate = $value;
							break;
						}
					}

					$datasetName = $datasetToUpdate[name];

					//Update extras
					$extras = $datasetToUpdate[extras];
					$extras = $resourceManager->defineExtras($extras, $imgPicto, $imgBackground, $removeBackground, $linkDatasets, $theme, $themeLabel,
						$selectedTypeMap, $selectedOverlays, $dont_visualize_tab, $widgets, $visu, 
						$dateDataset, $disableFieldsEmpty, $analyseDefault, $security);

					$datasetId = $resourceManager->updateDataset($generatedTaskId, $datasetId, $datasetToUpdate, $datasetName, $title, $description, $licence, $organization, $isPrivate, $tags, $extras);
					drupal_set_message("Le jeu de données '" . $datasetName ."' a été mis à jour.");

					//Managing resources
					Logger::logMessage("TRM - Managing resource");
					$this->manageFileResource($api, $resourceManager, $datasetId, null, $resources, $generateColumns, false, $encoding, $validata, null, $unzipZip);

					// Manage other resources
					Logger::logMessage("TRM - " . count($table_data));
					for ($i = 1; $i <= count($table_data); $i++) {
						$datasetName = $table_data[$i][name];
						$resourceDescription = $table_data[$i][description];
						$needToBeDelete = $table_data[$i][status][1];
						$needUpdate = $table_data[$i][status][2];
						$resourceId = $table_data[$i][status][3];
						$resourceUrl = $table_data[$i][donnees];
						$encoding = $table_data[$i][encoding];
						$oldname = $table_data[$i][donnees_old];
						$generateColumns = strpos($oldname, '_gencol.csv') !== false;
						$unzipZip = false;

						Logger::logMessage("TRM - Found " . json_encode($table_data[$i]));

						if ($needToBeDelete == 1) {
							$resourceManager->deleteResource($resourceId);
							drupal_set_message("La ressource '" . $datasetName ."' a été mis supprimé.");
						}
						else if ($needUpdate == 1 && $resourceUrl != "") {
							Logger::logMessage("TRM - Need update " . $needUpdate);
							$this->manageResource($api, $resourceManager, $datasetId, $resourceId, $resourceUrl, $generateColumns, true, $resourceDescription, $encoding, $validata, $unzipZip);
						}
					}
				}
			}
			
			Logger::logMessage("We redirect user");
			$redirect_path = "/admin/config/data4citizen/editMetaDataForm" . ($datasetId != null ? '?id=' . $datasetId : '');
			$url = url::fromUserInput($redirect_path);

			// set redirect
			$form_state->setRedirectUrl($url);
			Logger::logMessage("User redirected");
		} catch (\Exception $e) {
			Logger::logMessage($e->getMessage());
			drupal_set_message(t($e->getMessage()), 'error');
		}
	}

	function manageFileResource($api, $resourceManager, $datasetId, $resourceId, $resources, $generateColumns, $isUpdate, $encoding, $validata, $urlGsheet, $unzipZip) {
		if ($urlGsheet) {
			Logger::logMessage("TRM - Integrating GSheet '" . $urlGsheet . "'");
			$resourceUrl = $resourceManager->manageGsheet($datasetId, $urlGsheet);

			Logger::logMessage("TRM - Found resource '" . $resourceUrl . "'");
			$this->manageResource($api, $resourceManager, $datasetId, $resourceId, $resourceUrl, $generateColumns, $isUpdate, '', $encoding, $validata, $unzipZip);
		}
		else if (isset($resources[0]) && !empty($resources[0])) {

			$resourceUrl = $resourceManager->manageFile($resources[0]);
        

			$this->manageResource($api, $resourceManager, $datasetId, $resourceId, $resourceUrl, $generateColumns, $isUpdate, '', $encoding, $validata, $unzipZip);
		}
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
    
    public function validateForm(array &$form, FormStateInterface $form_state) {
        
        $del_dataset = $form_state->getValue('del_dataset');
        
        if($del_dataset!=true){
        
			$data_id = $form_state->getValue('selected_data_id');
			$description = $form_state->getValue('description');
			$tags = $form_state->getValue('tags');
			$licence = $form_state->getValue('selected_lic');
			$organization = $form_state->getValue('selected_org');
			$private = $form_state->getValue('selected_private');
			$them = $form_state->getValue('selected_theme');
			$title = $form_state->getValue('title');
			$title = str_replace(' ', '', $title);
        
			$analyse_default = $form_state->getValue('analyse_default');
        
			if( explode("=", $analyse_default)[0]!='dataChart'){
        
				$analyse_default1=$analyse_default;
				$analyse_default =  explode("&", $analyse_default);
            
				$analyse_default2=$analyse_default;
				$analyse_default =  $analyse_default[0];
            
				$analyse_ok=false;       
				foreach($analyse_default2 as &$anal){
					if(explode("=", $anal)[0]=='dataChart'){
						// $analyse_default_f=$anal;
						$analyse_ok=true;
						break;
					}  
				}    
		
				$analyse_default = explode("?", $analyse_default);
        
				if($analyse_default[1]!=''){
					$analyse_default = $analyse_default[1];
					$analyse_default= substr($analyse_default, 3);											 
				}
				else{
					$analyse_default = $analyse_default[0];
					$analyse_default= substr($analyse_default, 3);
				}
			}
			else{
				$analyse_default= $data_id;
				$analyse_default1 = $analyse_default;
			}
        
			if($data_id == 'new'){ 
				if ($data_id == '') {
					$form_state->setErrorByName('selected_data_id', $this->t('Aucune donnée sélectionnée'));
				}
				if ($organization == '') {
					$form_state->setErrorByName('selected_org', $this->t('Aucune organisation sélectionnée'));
				}
				if ($licence == '') {
					 $form_state->setErrorByName('selected_lic', $this->t('Aucune licence sélectionnée'));
				}
				if ($title == '') {
					$form_state->setErrorByName('title', $this->t("Veuillez saisir un titre"));
				}
            }
			else{
				if ($title == '') {
					$form_state->setErrorByName('title', $this->t("Veuillez saisir un titre"));
				}
				if ($data_id == '') {
					$form_state->setErrorByName('selected_data_id', $this->t('Aucune donnée sélectionnée'));
				}
				if ($organization == '') {
					$form_state->setErrorByName('selected_org', $this->t('Aucune organisation sélectionnée'));
				}
				if ($licence == '') {
					 $form_state->setErrorByName('selected_lic', $this->t('Aucune licence sélectionnée'));
				}	
				if(($analyse_default!=$data_id && $analyse_default!='') || ($analyse_default1!='' && $analyse_default=='') || ($analyse_ok==false && $analyse_default1!='' && $analyse_default1!=$analyse_default)) {
					//$form_state->setErrorByName('analyse_default', $this->t('Erreur de valeur "Analyse par défaut"'));
				}   
			}
        }
		$this->applyErrorsInline($form, $form_state);
		// if ($errors = $form_state->getErrors()) {
			// // Add error to fields using Symfony Accessor
			// $accessor = PropertyAccess::createPropertyAccessor();
			// foreach ($errors as $field => $error) {
				// if ($accessor->getValue($form, $field)) {
					// $accessor->setValue($form, $field.'[#prefix]', '<div class="form-group error">');
					// $accessor->setValue($form, $field.'[#suffix]', '<div class="input-error-desc">' .$error. '</div></div>');
				// }
			// }
		// }
	}
	
	public function applyErrorsInline(array &$form, FormStateInterface $form_state) {
		Logger::logMessage("EditMetadataForm errors " . json_encode($form_state->getErrors()));
		// If validation errors, add inline errors.
		if ($errors = $form_state->getErrors()) {
		  // Add error to fields using Symfony Accessor.
		  $accessor = PropertyAccess::createPropertyAccessor();
		  foreach ($errors as $field_accessor => $error) {
			try {
			  $accessor->getValue($form, $field_accessor);
			  if ($field = $accessor->getValue($form, $field_accessor)) {

				$prefix = str_replace('form-group', 'form-group has-danger error', $field['#prefix']);
				$suffix = '<div class="input-error-desc" id="' . $field['#id'] . '-error">' . $error . '</div>' . $field['#suffix'];

				$accessor->setValue($form, $field_accessor . '[#prefix]', $prefix);
				$accessor->setValue($form, $field_accessor . '[#suffix]', $suffix);

				$accessor->setValue($form, $field_accessor . '[#attributes][aria-invalid]', 'true');
				$accessor->setValue($form, $field_accessor . '[#attributes][aria-describedby]', $field['#id'] . '-error');
			  }
			}
			catch (\Exception $e) {

			}
		  }
		}
	  }
	
	function nettoyagePath($str) {
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
	//    $str = str_replace('\'', "_", $str);
	//    $str = str_replace("/", "_", $str);
		$str = str_replace("|", "_", $str);
		$str = preg_replace( '#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str );
		$str = preg_replace( '#&([A-za-z]{2})(?:lig);#', '\1', $str );
		$str = preg_replace( '#&[^;]+;#', '', $str );      
		
		$str = str_replace("-", "_", $str);    
		return $str;
	}
	
    public function datasetCallback(array &$form, FormStateInterface $form_state){
		//drupal_set_message('<pre>'. print_r($_SESSION, true) .'</pre>'); 
    

        $api = new Api;
		
		$selected_org = $form_state->getValue('filtr_org');
		$orgaFilter = "";
		if($selected_org!=''){
			$orgaFilter = '&q=organization:"'.$selected_org.'"';
		}

        $dataSet = $api->callPackageSearch_public_private('include_private=true&rows=1000&sort=title_string asc'.$orgaFilter, \Drupal::currentUser()->id());
			
        $dataSet = $dataSet->getContent();
        $dataSet2 = json_encode($dataSet, true);
        $dataSet = json_decode($dataSet, true);
        $dataSet = $dataSet[result][results];
        
		$ids = array();

        $ids["new"] = "Сréer un jeu de données";
   
		/*if($selected_org==''){*/
			foreach($dataSet as &$ds) {
				$ids[$ds[id]] = $ds[title];
			} 
		/*}
		else{
			foreach($dataSet as &$ds) {
				if($ds[organization][id]==$selected_org){
					$ids[$ds[id]] = $ds[title];
				}
			}
		}*/
      
		$elem = [
            '#type' => 'select',
            '#options' => $ids,
            '#attributes' => [
                'onchange' => 'addData('.$dataSet2.')','style' => 'width: 50%;', 
                'id' => 'selected_data'],
       
        ];

		return $elem;
	}
	
	function numberToLetters($number) {
		$alphabet = range('A', 'Z');

		$count = count($alphabet);
        if ($number <= $count) {
            return $alphabet[$number - 1];
        }
        $alpha = '';
        while ($number > 0) {
            $modulo = ($number - 1) % $count;
            $alpha  = $alphabet[$modulo] . $alpha;
            $number = floor((($number - $modulo) / $count));
        }
        return $alpha;
	}
}
