<?php
/**
 * @file
 * Contains \Drupal\search_api_solr_admin\Form\QueryForm.
 */

namespace Drupal\ckan_admin\Form;

use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\Query;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use \PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use \PhpOffice\PhpSpreadsheet\Reader\Xls;
use \PhpOffice\PhpSpreadsheet\Writer\Csv;
use Drupal\ckan_admin\Utils\HelpFormBase;							  

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

class editMetaDataForm extends HelpFormBase
{
    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'editMetaDataForm';
    }

    /**
     * {@inheritdoc}
     */

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

        $dataSet = $api->callPackageSearch_public_private('include_private=true&rows=1000&sort=title_string asc', \Drupal::currentUser()->id());
		
     
        $dataSet = $dataSet->getContent();
        $dataSet2 = json_encode($dataSet, true);
        $dataSet = json_decode($dataSet, true);
        $dataSet = $dataSet[result][results];
		//$dataSet = array();
		
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
		//error_log($cle, true);
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
   
        foreach($dataSet as &$ds) {
            $ids[$ds[id]] = $ds[title];
        }

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
                'onchange' => 'addData('.$dataSet2.')','style' => 'width: 50%;', 
                'id' => ['selected_data'])
        );
        
        $form['selected_data_id'] = array(
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
        
        $form['description'] = array(
            '#type' => 'textarea',
            '#title' => $this->t('Description :'),
            '#resizable' => true,
            '#attributes' => array('style' => 'width: 50%;'),

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

        );

        $form['selected_org'] = array(
            '#type' => 'select',
            '#title' => t('*Organisation :'),
            '#options' => $organizationList,
            '#empty_option' => t('----'),
            '#attributes' => array('style' => 'width: 50%;'),

        );

        $form['selected_private'] = array(
            '#type' => 'select',
            '#title' => t('*Visibilité :'),
            '#options' => array('Publique', 'Privée'),
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
				'file_validate_extensions' => array('jpg jpeg gif png txt doc xls pdf ppt pps odt ods odp csv json xls xlsx geojson'),
			),
			'#size' => 10,
            '#suffix' => '</div>',

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
		foreach($layers as $layer){
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
		foreach($overlays as $layer){
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
			
			$form['table'][$i]['file'] = array(
				'#type' => 'managed_file',
				'#upload_location' => 'public://dataset/',
				'#upload_validators' => array(
					'file_validate_extensions' => array('jpg jpeg gif png txt doc xls pdf ppt pps odt ods odp csv json xls xlsx geojson'),
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


        /*$directory = "sites/default/files/api/portail_d4c/img/set-v2/";
        $images = glob($directory . "*.svg");
        $imgs = '';
        
    
        foreach ($images as $image) {
            $imgs = $imgs . ';' . $image;
        }
    
     
    $form['imgimg'] = array(

            '#type' => 'textarea',
            '#attributes' => [
                'value' => $imgs,
                'style' => 'display: none',
            ],
            '#default_value' => $imgs,

        );*/

        $form['imgBack'] = array(

            '#type' => 'textarea',
            '#attributes' => [
                'style' => 'display: none',
            ],

        );

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state){

        $validataCurl = array();
        $idNewData = '';
        $api = new Api;
        $dataSet = $api->callPackageSearch_public_private('include_private=true&rows=1000&sort=title_string%20asc');
        $dataSet = $dataSet->getContent();
        $dataSet = json_decode($dataSet, true);
        $dataSet = $dataSet[result][results];

        $callUrl = $this->urlCkan . "/api/action/package_update";
        
        $title = $form_state->getValue('title');
        $data_id = $form_state->getValue('selected_data_id');
        $description = $form_state->getValue('description');
        $tags = $form_state->getValue('tags');
        $licence = $form_state->getValue('selected_lic');
        $organization = $form_state->getValue('selected_org');
        $private = $form_state->getValue('selected_private');
        
        $widget = $form_state->getValue('table_widgets');
        $widget_html='';
        
        foreach($widget as $key =>$val){
            if($val[name]!='' && $val[widget]!=''){
				$off ='';  
				if($val[offWidjet]==1){
					$off = '<.off.>'; 
				}
				$widget_html = $widget_html .$val[name].'<.info.>'.$val[description].'<.info.> '.$val[widget].' '.$off.'<.explode.>';            
			} 
        }
        
        $widget = substr($widget_html, 0, -11);
        
        $analize_false = $form_state->getValue('analize_false');
        $api_false = $form_state->getValue('api_false');
        $dont_visualize_tab='';
        
        if($api_false==1){
			$dont_visualize_tab= $dont_visualize_tab.'api;';
		}
        
        if($analize_false==1){
			$dont_visualize_tab=$dont_visualize_tab.'analize;';
		}
        
        $them = $form_state->getValue('selected_theme');
        $them = explode("%",$them);
        $them_label =$them[1];
        $them = $them[0];
        
        $selectedTypeMap = $form_state->getValue('selected_type_map');
		$selectedOverlays = "";
		if ($form_state->getValue('authorized_overlays_map') != NULL) {
			/*foreach ($form_state->getValue('authorized_overlays_map') as $key => $val) {drupal_set_message($val);
				if ($val != 0) {drupal_set_message("ok ".$key);
					$selectedOverlays .= $key . ",";
				}
			}
			if($selectedOverlays != "") $selectedOverlays = substr($selectedOverlays, 0, -1);*/
			$selectedOverlays = implode(",", array_keys(array_filter($form_state->getValue('authorized_overlays_map'))));
		}
        //drupal_set_message(json_encode($selectedOverlays));
        $analyse_default = $form_state->getValue('analyse_default');
        
        if(explode("=", $analyse_default)[0]!='dataChart'){
            $analyse_default =  explode("&", $analyse_default);
               
            foreach($analyse_default as &$anal){
                if(explode("=", $anal)[0]=='dataChart'){
                    $analyse_default_f=$anal;
                    break;
                }
                else{
                    $analyse_default_f="";
                }
			}  
            
			$analyse_default =  explode('"', $analyse_default_f);
			$analyse_default = $analyse_default[0];
		}
        
        $del_dataset = $form_state->getValue('del_dataset');
        
        if($del_dataset==true){
			$callUrl = $this->urlCkan . "/api/action/package_delete";
            
            $delDataset = [
				"id" => $data_id,
				//"force" => "True",
			];

            $return = $api->updateRequest($callUrl, $delDataset, "POST");
            
            $return = json_decode($return, true);
                   
			if ($return[success] == true) {
				drupal_set_message(t('Le jeu de données a été supprimé!'), 'warning');

				$config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
				$dataForUpdateDatasets = $config->get('dataForUpdateDatasets');         
				$dataForUpdateDatasets = json_decode($dataForUpdateDatasets);
				//drupal_set_message('<fdsfds>'.print_r($dataForUpdateDatasets,true).'<fdsfds>');                 
				foreach($dataForUpdateDatasets as &$value){
					foreach($value->datasets as $key => $dataset){
						if($dataset->id_data == $data_id){
							unset($value->datasets[$key]);
							break; 
						} 
					}
					$value->datasets = array_values($value->datasets);
                          
				}
                            
                //$config->set('dataForUpdateDatasets', null)->save();            
                            
				$config->set('dataForUpdateDatasets', json_encode($dataForUpdateDatasets))->save();
			   //  $dataForUpdateDatasets = $config->get('dataForUpdateDatasets');                   
			   // drupal_set_message(print_r($dataForUpdateDatasets, true)); 
             
			}
        }
        else{

			$Dataset_lies_table = $form_state->getValue('Dataset_lies_table');
			$string_dataset_lies = '';
            
			foreach ($Dataset_lies_table as $key => &$val) {
				if ($val[dt] == 1) {
					$string_dataset_lies = $string_dataset_lies . ';' . $key;
				}
			}
            
			$string_dataset_lies = substr($string_dataset_lies, 1);
            
			if ($private == '1') {
				$private = true;
			} 
			else {
				$private = false;
			}

			if ($data_id == 'new') {
                
				$tagsData = array();
				if ($tags == '') {

					$tagsData = [];
				} 
				else {
					$tags = explode(",", $tags);

					for ($j = 0; $j < count($tags); $j++) {
						$tagsData[$j] = ["vocabulary_id" => null, "state" => "active", "display_name" => $tags[$j], "name" => $tags[$j]];
					}
				}
                    
				$extras = array();

				$form_file = $form_state->getValue('img_picto');

				if (isset($form_file[0]) && !empty($form_file[0])) {

					$file = File::load($form_file[0]);
					$file->setPermanent();
					$file->save();
					$url_t = parse_url($file->url());
					$url_pict = $url_t["path"];

					$url_pict = explode("/", $url_pict);
					$url_pict = explode(".", $url_pict[(count($url_pict) - 1)]);
					$url_pict = "/sites/default/files/theme_logo/".$url_pict[0].".svg";

					$extras[count($extras)]['key'] = 'Picto';
					$extras[(count($extras) - 1)]['value'] = $url_pict;

				} 
				else {
					$extras[count($extras)]['key'] = 'Picto';
					$extras[(count($extras) - 1)]['value'] = "d4c-".$form_state->getValue('imgBack');
				}
				$form_file = $form_state->getValue('img_backgr');
				if (isset($form_file[0]) && !empty($form_file[0])) {

					$file = File::load($form_file[0]);
					$file->setPermanent();
					$file->save();
					$url_t = parse_url($file->url());
					$url_pict = $url_t["path"];

//                        $url_pict = explode("/", $url_pict);
//                        $url_pict = explode(".", $url_pict[(count($url_pict) - 1)]);
//                        $url_pict = $url_pict[0];

					$extras[count($extras)]['key'] = 'img_backgr';
					$extras[(count($extras) - 1)]['value'] = $url_pict;

				} 
//              else {
//                  $extras[count($extras)]['key'] = 'img_backgr';
//                 	$extras[(count($extras) - 1)]['value'] = $form_state->getValue('imgBack');
//              }
                    

				$extras[count($extras)]['key'] = 'LinkedDataSet';
				$extras[(count($extras) - 1)]['value'] = $string_dataset_lies;
				 
				$extras[count($extras)]['key'] = 'theme';
				$extras[(count($extras) - 1)]['value'] = $them;
				
				$extras[count($extras)]['key'] = 'label_theme';
				$extras[(count($extras) - 1)]['value'] = $them_label;
				
				$extras[count($extras)]['key'] = 'type_map';
				$extras[(count($extras) - 1)]['value'] = $selectedTypeMap;
				
				if($selectedOverlays != ""){
					$extras[count($extras)]['key'] = 'overlays';
					$extras[(count($extras) - 1)]['value'] = $selectedOverlays;
				}
				
				$extras[count($extras)]['key'] = 'dont_visualize_tab';
				$extras[(count($extras) - 1)]['value'] = $dont_visualize_tab;
				
				$extras[count($extras)]['key'] = 'FTP_API';
				$extras[(count($extras) - 1)]['value'] = 'FTP';
				
				$extras[count($extras)]['key'] = 'widgets';
				$extras[(count($extras) - 1)]['value'] = $widget;
				
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
				$security = array("roles" => array("administrator"), "users" => $userlist);
				
				$extras[count($extras)]['key'] = 'edition_security';
				$extras[(count($extras) - 1)]['value'] = json_encode($security);
				
				#######################

				$label = $title;
				/*$label = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $label)));
				$label = str_replace(" ", "_", $label);
				$label = str_replace("`", "_", $label);
				$label = str_replace("'", "_", $label);
				$label = str_replace("-", "_", $label);
				$label = strtolower($label);
				$label = htmlentities($label, ENT_NOQUOTES, $charset);
				$label = preg_replace('#\&([A-za-z])(?:acute|cedil|circ|grave|ring|tilde|uml)\;#', '\1', $label);
				$label = preg_replace('#\&([A-za-z]{2})(?:lig)\;#', '\1', $label); // pour les ligatures e.g. '&oelig;'
				$label = preg_replace('#\&[^;]+\;#', '', $label); // supprime les autres caractères
				$label = preg_replace('@[^a-zA-Z0-9_]@','',$label);*/
				$label = $this->nettoyage($title);
				
				$urlRes = $this->urlCkan ."/dataset/".$label;
				
				$newData = ["name" => $label,
					"title" => $title,
					"private" => $private,
					"author" => "",
					"author_email" => "",
					"maintainer" => "",
					"maintainer_email" => "",
					"license_id" => $licence,
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
					"owner_org" => $organization,
				];
				
				$coll=array('0'=>'0', '1'=>'');
                    
                $idNewData= $this->saveData($newData, $coll);
                $idNewData= $idNewData[1];

				//$api->calculateVisualisations($idNewData);
            }    
			else {
				$check=false;
				foreach ($dataSet as &$value) {
                    if ($value[id] == $data_id) {
                        $check=true;
                        
                        $extras = array();
                        $cout_extras = count($value[extras]);
                        $pict = false;
                        $pict2 = false;
                        $dataset_lies = false;
                        $them_t = false;
                        $theme_label_ex = false;
                        $analyse = false;
                        $typeMap_ex = false;
                        $overlaysMap_ex = false;
                        $dnt_viz_api = false;
                        $widget_ex = false;
                        
                        if ($cout_extras != 0) {

                            $url_pict = '';

                            $form_file = $form_state->getValue('img_picto');

                            if (isset($form_file[0]) && !empty($form_file[0])) {

                                $file = File::load($form_file[0]);
                                $file->setPermanent();
                                $file->save();
                                $url_t = parse_url($file->url());
                                $url_pict = $url_t["path"];

                                $url_pict = explode("/", $url_pict);
                                $url_pict = explode(".", $url_pict[(count($url_pict) - 1)]);
                                $url_pict = $url_pict[0];
								$url_pict = "/sites/default/files/theme_logo/".$url_pict.".svg";

                            } 
                            else {
                                $url_pict = "d4c-".$form_state->getValue('imgBack');
                            }
                            
                            $form_file = $form_state->getValue('img_backgr');
                            if (isset($form_file[0]) && !empty($form_file[0])) {

                                $file = File::load($form_file[0]);
                                $file->setPermanent();
                                $file->save();
                                $url_t = parse_url($file->url());
                                $url_pict2 = $url_t["path"];

                            } 

                            for ($j = 0; $j < count($value[extras]); $j++) {
                                //$theme_label_ex = false;
                                if ($value[extras][$j]['key'] == 'Picto') {
                                    $pict = true;
                                    if ($url_pict != '') {
                                        $value[extras][$j]['value'] = $url_pict;
                                    }
                                }
                                
                                if ($value[extras][$j]['key'] == 'img_backgr') {
                                    $pict2 = true;
                                    if ($url_pict2 != '') {
                                        $value[extras][$j]['value'] = $url_pict2;
                                    }
                                }
                                
                                if ($value[extras][$j]['key'] == 'LinkedDataSet') {
                                    $dataset_lies = true;
                                    $value[extras][$j]['value'] = $string_dataset_lies;
                                }
                                
                                if ($value[extras][$j]['key'] == 'dont_visualize_tab') {
                                    $dnt_viz_api = true;
                                    $value[extras][$j]['value'] = $dont_visualize_tab;
                                }
                                								
                                if ($value[extras][$j]['key'] == 'theme') {
                                    $them_t = true;
                                    $value[extras][$j]['value'] = $them;
                                }
								
                                if ($value[extras][$j]['key'] == 'label_theme') {
                                    $theme_label_ex = true;
                                    $value[extras][$j]['value'] = $them_label;
                                }
                                
                                if ($value[extras][$j]['key'] == 'analyse_default') {
                                    $analyse = true;
                                    $value[extras][$j]['value'] = $analyse_default;
                                }
                                    
                                if ($value[extras][$j]['key'] == 'type_map') {
                                    $typeMap_ex = true;
                                    $value[extras][$j]['value'] = $selectedTypeMap;
                                }
                                
                                if ($value[extras][$j]['key'] == 'overlays') {
                                    $overlaysMap_ex = true;
                                    $value[extras][$j]['value'] = $selectedOverlays;
                                }
                                
                                if ($value[extras][$j]['key'] == 'widgets') {
                                    $widget_ex = true;
                                    $value[extras][$j]['value'] = $widget;
                                }

                            }

                        }
                        
                        if ($pict == false) {

                            $value[extras][count($value[extras])]['key'] = 'Picto';
                            $value[extras][count($value[extras]) - 1]['value'] = $url_pict;
                        }
                        
                        if ($pict2 == false) {
                            if($url_pict2 || $url_pict2!='' || $url_pict2!=null){
								$value[extras][count($value[extras])]['key'] = 'img_backgr';
								$value[extras][count($value[extras]) - 1]['value'] = $url_pict2;
							}
                        }

                        if ($dataset_lies == false) {
                            $value[extras][count($value[extras])]['key'] = 'LinkedDataSet';
                            $value[extras][count($value[extras]) - 1]['value'] = $string_dataset_lies;
                        }
                        
                        if ($dnt_viz_api == false) {
                            $value[extras][count($value[extras])]['key'] = 'dont_visualize_tab';
                            $value[extras][count($value[extras]) - 1]['value'] = $dont_visualize_tab;
                        }
                        
                        if($theme_label_ex==false){
							$value[extras][count($value[extras])]['key'] = 'label_theme';
							$value[extras][count($value[extras]) - 1]['value'] = $them_label;
                        }

                        if ($them_t == false) {
                            $value[extras][count($value[extras])]['key'] = 'theme';
                            $value[extras][count($value[extras]) - 1]['value'] = $them; 
                        }
                        
                        if ($analyse == false && $analyse_default!='') {
                            $value[extras][count($value[extras])]['key'] = 'analyse_default';
                            $value[extras][count($value[extras]) - 1]['value'] = $analyse_default; 
                        }
                        
                        if ($typeMap_ex == false && $selectedTypeMap!='') {
                            $value[extras][count($value[extras])]['key'] = 'type_map';
                            $value[extras][count($value[extras]) - 1]['value'] = $selectedTypeMap; 
                        } 
                        
                        if ($overlaysMap_ex == false && $selectedOverlays!='') {
                            $value[extras][count($value[extras])]['key'] = 'overlays';
                            $value[extras][count($value[extras]) - 1]['value'] = $selectedOverlays; 
                        } 
                        
                        if ($widget_ex == false && $widget!='') {
                            $value[extras][count($value[extras])]['key'] = 'widgets';
                            $value[extras][count($value[extras]) - 1]['value'] = $widget; 
                        }
                        
                        $value[title] = $title;
                        $value[notes] = $description;
                        $value[license_id] = $licence;
                        $value['private'] = $private;

                        //tags//
                        
                        $tagsFin = array();
                        if($tags!=null || $tags!='') {
                            $tagsFin = explode(",", $tags);
                        }
                        for ($j = 0; $j < count($tagsFin); $j++) {
                            $tagsData[$j] = ["vocabulary_id" => null, "state" => "active", "display_name" => $tagsFin[$j], "name" => $tagsFin[$j], "resources" => $resources];
                        } 
                        if($tagsData==null){
                            $tagsData=array();
                        }
                        $value["tags"] = $tagsData;
                        
                        
                        //tags end//
                        

                        $return = $api->updateRequest($callUrl, $value, "POST");
                        $return = json_decode($return);
                        if ($return->success == true) {
                            drupal_set_message('Les données ont été sauvegardées');
                             
                        } else {
                             
                            
                            drupal_set_message(t('les données n`ont pas été ajoutées!'), 'error');
							drupal_set_message("Raison: " . $return->error->message);
                        }
                        
                        $callUrluptOwner = $this->urlCkan . "/api/action/package_owner_org_update";
                        $return = $api->updateRequest($callUrluptOwner, ["id" => $data_id, "organization_id" => $organization], "POST");

						
                        break;
                    }

                }
            
				if($check==false){
					// drupal_set_message(t('id not find'), 'error');
                
				}
            }
        

////////////////////////////////////////resources////////////////////////////////////////////////////////////////

			$table_data = $form_state->getValue('table');
			$validata = $form_state->getValue('validata');
				
			if($_SERVER['HTTP_HOST']=='192.168.2.217'){
				$root='/home/bpm/drupal-8.6.15/';
			}
			else{
				$root='/home/user-client/drupal-d4c/';
			}
            
        
			if ($data_id == 'new') {
            
				////////resurce file/////
				$form_file = $form_state->getValue('resours', 0);

				if (isset($form_file[0]) && !empty($form_file[0])) {
					$file = File::load($form_file[0]);
					$file->setPermanent();
					$file->save();
					$fileName = parse_url($file->url());
					//drupal_set_message('<pre>'. print_r($fileName,true) .'</pre>');
					$host=$fileName[host];/////////////////////////////////////
					$fileName = $fileName[path];
					$filepath = $fileName;
					
					$fileName= strtolower($fileName);
					$fileName =urldecode($fileName);
					$fileName = $this->nettoyage2($fileName);
					
						
					$fileName =explode("/", $fileName);
					$fileName = $fileName[(count($fileName)-1)];
					
					$url_res = $file->url();
					$url_res = str_replace('http:', 'https:', $url_res);
						
					//$filepathN = strtolower($filepath);
					$filepathN =urldecode($filepath);
					$filepathN = $this->nettoyage2($filepathN);

					rename($root.''.urldecode($filepath), $root.''.$filepathN); 
					
					$filepath=$filepathN;
					
					
					if($_SERVER['HTTP_HOST']=='192.168.2.217'){
						
						 $url_res = 'http://'.$host.''.$filepath;
					}
					else{
						$url_res = 'https://'.$host.''.$filepath;
					}
            
					if(explode(".", $fileName)[1]  === 'xls' || explode(".", $fileName)[1] === 'XLS' || explode(".", $fileName)[1]  === 'xlsx' || explode(".", $fileName)[1] === 'XLSX') {
						$xls_file = $root.''.$filepath;
						//\PhpOffice\PhpSpreadsheet\Settings::setLocale('fr');
						$reader = new Xlsx();
                    
						if(explode(".", $fileName)[1]  === 'xls' ||explode(".", $fileName)[1] === 'XLS') {
							$reader = new Xls();
						}
                
						$spreadsheet = $reader->load($xls_file);

						$loadedSheetNames = $spreadsheet->getSheetNames();
						$highestRow = $spreadsheet->getActiveSheet()->getHighestRow(); // e.g. 10
						$highestColumn = $spreadsheet->getActiveSheet()->getHighestColumn(); // e.g 'F'
						$spreadsheet->getActiveSheet()->getStyle('A1:' . $highestColumn . $highestRow)->getNumberFormat()->setFormatCode('###.##');

						$writer = new Csv($spreadsheet);

						foreach($loadedSheetNames as $sheetIndex => $loadedSheetName) {
							$writer->setSheetIndex($sheetIndex);
							
							$csvpath = str_replace(array('.xlsx', '.xls', '.XLSX', '.XLS'), array('.csv', '.csv', '.csv', '.csv'), $root.''.$filepath);
							$url_res = str_replace(array('.xlsx', '.xls', '.XLSX', '.XLS'), array('.csv', '.csv', '.csv', '.csv'), $url_res);
							$fileName = str_replace(array('.xlsx', '.xls', '.XLSX', '.XLS'), array('.csv', '.csv', '.csv', '.csv'), $fileName);
							$filepath = str_replace(array('.xlsx', '.xls', '.XLSX', '.XLS'), array('.csv', '.csv', '.csv', '.csv'), $filepath);
							$writer->save($csvpath);
							break;
						}
						$has_csv = true;
					}
            
					if(explode(".", $fileName)[1]  === 'csv' ||explode(".", $fileName)[1] === 'CSV') {
                  
						array_push($validataCurl, 'https://go.validata.fr/api/v1/validate?schema=https://git.opendatafrance.net/scdl/deliberations/raw/master/schema.json&url='.$url_res );
                  
					   // read into array
					   //$arr = file('/home/user-client/drupal-d4c'.$filepath);
					   
					    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
						$spreadsheet = $reader->load($root.''.$filepath);
					    //$arr = $spreadsheet->getActiveSheet()->toArray();
						$highestColumn = $spreadsheet->getActiveSheet()->getHighestColumn(); // e.g 'F'
						$existingCols = array();
						for($i=1; $i<= $this->lettersToNumber($highestColumn) ; $i++){
							$label = $spreadsheet->getActiveSheet()->getCell($this->numberToLetters($i) . '1')->getValue();
							$label = utf8_decode($label);
							$label = $this->nettoyage($label);
							$label = strtolower($label);
							$label = str_replace("?", "", $label);
							$label = preg_replace("/\r|\n/", "", $label);
							if(in_array($label, $existingCols)) {
								$label = $label . $i;
							}
							$existingCols[] = $label;
							
							$spreadsheet->getActiveSheet()->getCell($this->numberToLetters($i) . '1')->setValue($label);
						}
						
						$writer = new Csv($spreadsheet);
						
						$writer->save($root.''.$filepath);
						
						// drupal_set_message(json_encode($arr),'status');
						
						//$arr = file($root.''.$filepath);
						// $label = utf8_decode($arr[0]);
						// $label = $this->nettoyage($label);
						// $label = strtolower($label);
						// $label = str_replace("?", "", $label);
                
						// //drupal_set_message('<pre>'. print_r($label, true) .'</pre>');
						// // edit first line
						// $arr[0] = $label;
                  
						// write back to file
						//file_put_contents('/home/user-client/drupal-d4c'.$filepath, implode($arr));
						// for($i=0; $i< count($arr) ; $i++){
							// if($i == 0) {
								// for($j=0; $j< count($arr[$i]) ; $j++){
									// $label = utf8_decode($arr[$i][$j]);
									// $label = $this->nettoyage($label);
									// $label = strtolower($label);
									// $label = str_replace("?", "", $label);
									// $arr[$i][$j] = $label;
								// }
							// }
							// $arr[$i] = implode(';',$arr[$i]);
						// }
						// // drupal_set_message(json_encode($arr),'status');
						// file_put_contents($root.''.$filepath, implode(PHP_EOL,$arr));
						$has_csv = true;
					}
            
					$resources = [    "package_id" => $idNewData,
						"url" => $url_res,
						"description" => '',
						"name" =>$fileName,
					];
					error_log("ddddddddddd .".json_encode($resources));
					$callUrluptres = $this->urlCkan . "/api/action/resource_create";
					$return = $api->updateRequest($callUrluptres, $resources, "POST");
					$return = json_decode($return, true);                
					sleep(20);
				}
				
				$api->calculateVisualisations($idNewData);
			} 
			else {
				////////resurce file/////
            
				$form_file = $form_state->getValue('resours', 0);

				if (isset($form_file[0]) && !empty($form_file[0])) {
					$file = File::load($form_file[0]);
					$file->setPermanent();
					$file->save();
					$fileName = parse_url($file->url());//error_log(json_encode($fileName));
					$host=$fileName[host];
					$fileName = $fileName[path];
					$filepath = $fileName;
					
					$fileName= strtolower($fileName);
					$fileName =urldecode($fileName);
					$fileName = $this->nettoyage2($fileName);
					
						
					$fileName =explode("/", $fileName);
					$fileName = $fileName[(count($fileName)-1)];
					
					$url_res = $file->url();
					$url_res = str_replace('http:', 'https:', $url_res);
						
					//$filepathN = strtolower($filepath);
					$filepathN =urldecode($filepath);
					$filepathN = $this->nettoyagePath($filepathN);

					rename($root.''.urldecode($filepath), $root.''.$filepathN);

					rename($root.''.urldecode($filepath), $root.''.$filepathN);
					
					
					$filepath=$filepathN;
					
					if($_SERVER['HTTP_HOST']=='192.168.2.217'){
						
						 $url_res = 'http://'.$host.''.$filepath;
					}
					else{
						$url_res = 'https://'.$host.''.$filepath;
					}
            
					if(explode(".", $fileName)[1]  === 'xls' || explode(".", $fileName)[1] === 'XLS' || explode(".", $fileName)[1]  === 'xlsx' || explode(".", $fileName)[1] === 'XLSX') {
				   
						$xls_file = $root.''.$filepath;
					
						$reader = new Xlsx();
                    
						if(explode(".", $fileName)[1]  === 'xls' ||explode(".", $fileName)[1] === 'XLS') {
							$reader = new Xls();
						}
                
						$spreadsheet = $reader->load($xls_file);

						$loadedSheetNames = $spreadsheet->getSheetNames();

						$writer = new Csv($spreadsheet);

						foreach($loadedSheetNames as $sheetIndex => $loadedSheetName) {
							$writer->setSheetIndex($sheetIndex);
							
							$csvpath = str_replace(array('.xlsx', '.xls', '.XLSX', '.XLS'), array('.csv', '.csv', '.csv', '.csv'), $root.''.$filepath);
							$url_res = str_replace(array('.xlsx', '.xls', '.XLSX', '.XLS'), array('.csv', '.csv', '.csv', '.csv'), $url_res);
							$fileName = str_replace(array('.xlsx', '.xls', '.XLSX', '.XLS'), array('.csv', '.csv', '.csv', '.csv'), $fileName);
							$filepath = str_replace(array('.xlsx', '.xls', '.XLSX', '.XLS'), array('.csv', '.csv', '.csv', '.csv'), $filepath);
							$writer->save($csvpath);
							break;
						}
						
					}
            
					if(explode(".", $fileName)[1]  === 'csv' ||explode(".", $fileName)[1] === 'CSV') {
                  
						array_push($validataCurl, 'https://go.validata.fr/api/v1/validate?schema=https://git.opendatafrance.net/scdl/deliberations/raw/master/schema.json&url='.$url_res );
                  
				  		$reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
						$spreadsheet = $reader->load($root.''.$filepath);
					    //$arr = $spreadsheet->getActiveSheet()->toArray();
						$highestColumn = $spreadsheet->getActiveSheet()->getHighestColumn(); // e.g 'F'
						$existingCols = array();
						for($i=1; $i<= $this->lettersToNumber($highestColumn) ; $i++){
							$label = $spreadsheet->getActiveSheet()->getCell($this->numberToLetters($i) . '1')->getValue();
							$label = utf8_decode($label);
							$label = $this->nettoyage($label);
							$label = strtolower($label);
							$label = str_replace("?", "", $label);
							$label = preg_replace("/\r|\n/", "", $label);
							if(in_array($label, $existingCols)) {
								$label = $label . $i;
							}
							$existingCols[] = $label;
							
							$spreadsheet->getActiveSheet()->getCell($this->numberToLetters($i) . '1')->setValue($label);
						}
						
						$writer = new Csv($spreadsheet);
						
						$writer->save($root.''.$filepath);
				  
						// read into array
						//$arr = file('/home/user-client/drupal-d4c'.$filepath);
						// $arr = file($root.''.$filepath);
						// $label = utf8_decode($arr[0]);
						// $label = str_replace(" ", "_", $label);
						// $label = $this->nettoyage($label);
						// $label = strtolower($label);
						// $label = str_replace("?", "", $label);
                
						// // edit first line
						// $arr[0] = $label;
                
						// // write back to file
						// //file_put_contents('/home/user-client/drupal-d4c'.$filepath, implode($arr));
						// file_put_contents($root.''.$filepath, implode($arr));
						// write back to file
						//file_put_contents('/home/user-client/drupal-d4c'.$filepath, implode($arr));
					}
            
					$resources = [     
						"package_id" => $data_id,
						"url" => $url_res,
						"description" => '',
						"name" =>$fileName,
					];

					$callUrluptres = $this->urlCkan . "/api/action/resource_create";
					$return = $api->updateRequest($callUrluptres, $resources, "POST");
					$return = json_decode($return, true);                
					sleep(20);
				}
				$has_csv = false;
				
				for ($i = 1; $i <= count($table_data); $i++) {
					// del res
					//error_log(json_encode($table_data[$i]));
					if ($table_data[$i][status][1] == 1) {

						$delRes = [
							"id" => $table_data[$i][status][3],
							"force" => "True",
						];

						$callUrldelres = $this->urlCkan . "/api/action/resource_delete";
						$return = $api->updateRequest($callUrldelres, $delRes, "POST");

						
					} 
					
					else if ($table_data[$i][status][2] == 1) {
				   
						error_log("update ".$table_data[$i]['name']);
						
						$url = "";
						$url = $table_data[$i][donnees];
						if($url != ""){
							$fileName = parse_url($url);
							$host=$fileName[host];
							$fileName = $fileName[path];
							$filepath = $fileName;
							
							$fileName= strtolower($fileName);
							$fileName =urldecode($fileName);
							$fileName = $this->nettoyage2($fileName);
							
								
							$fileName =explode("/", $fileName);
							$fileName = $fileName[(count($fileName)-1)];
							//$table_data[$i][status][3] = $fileName;
							
							$url_res = $url;
							$url_res = str_replace('http:', 'https:', $url_res);
								
							//$filepathN = strtolower($filepath);
							$filepathN = urldecode($filepath);
							$filepathN = $this->nettoyagePath($filepathN);
							$filepathN = explode(".", $filepathN)[0] . uniqid() .".". explode(".", $filepathN)[1];

							rename($root.''.urldecode($filepath), $root.''.$filepathN);
							$filepath=$filepathN;
							
							$url_res = 'https://'.$host.''.$filepath;
						
							if(explode(".", $fileName)[1]  === 'xls' || explode(".", $fileName)[1] === 'XLS' || explode(".", $fileName)[1]  === 'xlsx' || explode(".", $fileName)[1] === 'XLSX') {
								
								$xls_file = $root.''.$filepath;
							
								$reader = new Xlsx();
							
								if(explode(".", $fileName)[1]  === 'xls' ||explode(".", $fileName)[1] === 'XLS') {
									$reader = new Xls();
								}
						
								$spreadsheet = $reader->load($xls_file);

								$loadedSheetNames = $spreadsheet->getSheetNames();

								$writer = new Csv($spreadsheet);

								foreach($loadedSheetNames as $sheetIndex => $loadedSheetName) {
									$writer->setSheetIndex($sheetIndex);
									
									$csvpath = str_replace(array('.xlsx', '.xls', '.XLSX', '.XLS'), array('.csv', '.csv', '.csv', '.csv'), $root.''.$filepath);
									$url_res = str_replace(array('.xlsx', '.xls', '.XLSX', '.XLS'), array('.csv', '.csv', '.csv', '.csv'), $url_res);
									$fileName = str_replace(array('.xlsx', '.xls', '.XLSX', '.XLS'), array('.csv', '.csv', '.csv', '.csv'), $fileName);
									$filepath = str_replace(array('.xlsx', '.xls', '.XLSX', '.XLS'), array('.csv', '.csv', '.csv', '.csv'), $filepath);
									$writer->save($csvpath);
									break;
								}
								$has_csv = true;
							}
					
							if(explode(".", $fileName)[1]  === 'csv' ||explode(".", $fileName)[1] === 'CSV') {
						  
								array_push($validataCurl, 'https://go.validata.fr/api/v1/validate?schema=https://git.opendatafrance.net/scdl/deliberations/raw/master/schema.json&url='.$url_res );
						  
								// read into array
								//$arr = file('/home/user-client/drupal-d4c'.$filepath);
								$arr = file($root.''.$filepath);
								//$label = utf8_decode($arr[0]);
								$label = $arr[0];
								$label = str_replace(" ", "_", $label);
								$label = $this->nettoyage($label);
								$label = strtolower($label);
								$label = str_replace("?", "", $label);
						
								// edit first line
								$arr[0] = $label;
						
								// write back to file
								//file_put_contents('/home/user-client/drupal-d4c'.$filepath, implode($arr));
								file_put_contents($root.''.$filepath, implode($arr));
								$has_csv = true;
							}
					
							/*$resources = [     
								"package_id" => $data_id,
								"url" => $url_res,
								"description" => '',
								"name" =>$fileName,
							];

							$callUrluptres = $this->urlCkan . "/api/action/resource_create";
							$return = $api->updateRequest($callUrluptres, $resources, "POST");
							$return = json_decode($return, true);                
							sleep(20);
							*/
							
							$resources = [
								//"package_id" => $data_id,
								"id" => $table_data[$i][status][3],
								"url" => $url_res,
								//"upload" => curl_file_create($url_res),
								"description" => $table_data[$i]['description'],
								"name" => $table_data[$i]['name'],
								"format" => strtoupper(explode(".", $fileName)[1]),
								"clear_upload" => true
							];
							//error_log(json_encode($resources));
							/*$callUrluptres = $this->urlCkan . "/api/action/resource_update";
							$return = $api->updateRequest($callUrluptres, $resources, "POST");*/
							$return = $api->updateResourceAndPushDatastore($resources);
						}
					}
				}
				if($has_csv == TRUE){
					sleep(20);
					
				}
				$api->calculateVisualisations($data_id);
			}
			
			// validata
			$optionst = array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST => "GET",
				CURLOPT_HTTPHEADER => array(
					'Content-type:application/json',
					'Content-Length: ' . strlen($jsonData),
					'Authorization:  ' . $cle,
				),
			);
        
			if ($form_state->getValue('validata') != "non_valider") {
            
				//drupal_set_message('<pre>'. print_r($validataCurl, true) .'</pre>');  
				for($v=0; $v < count($validataCurl); $v++ ){
                
					$curlValid = curl_init($validataCurl[$v]);
					curl_setopt_array($curlValid, $optionst);
					$valid = curl_exec($curlValid);
					curl_close($curlValid);
					$resValidata = json_decode($valid, true);
					//drupal_set_message('<pre>'. print_r($resValidata, true) .'</pre>');
                
					$errorsValid = $resValidata[report][tables][0][errors];

                
					if ($resValidata[report][valid] == false) {
						for ($i = 0; $i < count($errorsValid); $i++) {
							
							drupal_set_message(t(($i + 1) . '. Code:' . $errorsValid[$i][code] . ' | Message:' . $errorsValid[$i][message]), 'warning');
							
							if($i>5){
							   break;
							}
						}
                    } 
					else if ($resValidata[report][valid] == true) {
						drupal_set_message('Les données ont été validées');
					}
                }
			}
        }
    }
	
	function lettersToNumber($letters){
		$alphabet = range('A', 'Z');
		$number = 0;

		foreach(str_split(strrev($letters)) as $key=>$char){
			$number = $number + (array_search($char,$alphabet)+1)*pow(count($alphabet),$key);
		}
		return $number;
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
					$form_state->setErrorByName('analyse_default', $this->t('Erreur de valeur "Analyse par défaut"'));
				}   
			}
        }
	}
    
    public function saveData($newData, $data){
        $coll = $data[0];
        
        //drupal_set_message('<pre>'.$data[0].'</pre>');
        error_log(json_encode($newData));
        $api = new Api;
		$callUrlNewData = $this->urlCkan . "/api/action/package_create";
		$return = $api->updateRequest($callUrlNewData, $newData, "POST");
                   
		//drupal_set_message(print_r($return,true));
                    
		$resnew = json_decode($return);

		$idNewData = $resnew->result->id;

		if ($resnew->success == true) {
			drupal_set_message('Les données ont été sauvegardées');
			$idNewData = $resnew->result->id;
		} 
		else if($resnew->error->name[0]=='Cette URL est déjà utilisée.'){
			$coll++;
			
			if($coll==1){
				$newData[name]=$newData[name].'_'.$coll;
				$newData[title]=$newData[title].' '.$coll;
				$idNewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData));
				$idNewData = $idNewData[1];    
			}
			else if($coll>10){
				$newData[name]=substr($newData[name],0, -3);
				$newData[name]=$newData[name].'_'.$coll;
				$newData[title]=substr($newData[title],0, -3);
				$newData[title]=$newData[title].' '.$coll;
				$idNewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData));
				$idNewData = $idNewData[1];
			}
			else if($coll>100){
				$newData[name]=substr($newData[name],0, -4);
				$newData[name]=$newData[name].'_'.$coll;
				$newData[title]=substr($newData[title],0, -4);
				$newData[title]=$newData[title].' '.$coll;
				$idNewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData));
				$idNewData = $idNewData[1];    
			}
			else if($coll>1000){
				$newData[name]=substr($newData[name],0, -5);
				$newData[name]=$newData[name].'_'.$coll;
				$newData[title]=substr($newData[title],0, -5);
				$newData[title]=$newData[title].' '.$coll;
				$idNewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData));
				$idNewData = $idNewData[1];    
			}
			else if($coll>10000){
				$newData[name]=substr($newData[name],0, -6);
				$newData[name]=$newData[name].'_'.$coll;
				$newData[title]=substr($newData[title],0, -6);
				$newData[title]=$newData[title].' '.$coll;
				$idNewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData));
				$idNewData = $idNewData[1];
			}
			else{
				$newData[name]=substr($newData[name],0, -2);
				$newData[name]=$newData[name].'_'.$coll;
				$newData[title]=substr($newData[title],0, -2);
				$newData[title]=$newData[title].' '.$coll;
				$idNewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData));
				$idNewData = $idNewData[1];
			}
		}
		else {
			//drupal_set_message(print_r($resnew,true));
			drupal_set_message(t('les données n`ont pas été ajoutées!'), 'error');
			drupal_set_message("Raison: " . json_encode($resnew->error->name));

		}
        
        //console.log($idNewData);
        //drupal_set_message('<pre>'.print_r($idNewData, true).'</pre>');
        
        return array('0'=>$coll, '1'=>$idNewData);
    }

    function nettoyage( $str, $charset='utf-8' ) {
		$str = utf8_decode($str);
	   // $str = htmlentities( $str, ENT_NOQUOTES, $charset );
		
		$str = utf8_decode($str);
			 
		   
		$str = str_replace("?", "", $str);   
		//$label = preg_replace('@[^a-zA-Z0-9_]@','',$label);
		$str = str_replace("`", "_", $str);
		$str = str_replace("'", "_", $str);
		$str = str_replace("-", "_", $str);
		$str = str_replace(" ", "_", $str);
		$str = str_replace("%", "", $str);
		$str = str_replace("(", "", $str);
		$str = str_replace(")", "", $str);
		$str = str_replace("*", "", $str);
		$str = str_replace("!", "", $str);
		$str = str_replace("@", "", $str);
		$str = str_replace("#", "", $str);
		$str = str_replace("$", "", $str);
		$str = str_replace("^", "", $str);
		$str = str_replace("&", "", $str);
		$str = str_replace("+", "", $str);
		$str = str_replace(":", "", $str);
		$str = str_replace(">", "", $str);
		$str = str_replace("<", "", $str);
		$str = str_replace('\'', "_", $str);
		$str = str_replace("/", "_", $str);
		$str = str_replace("|", "_", $str);
		$str = strtolower($str);     
		$str = preg_replace( '#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str );
		$str = preg_replace( '#&([A-za-z]{2})(?:lig);#', '\1', $str );
		$str = preg_replace( '#&[^;]+;#', '', $str );      
		
			
			
		$str = str_replace("-", "_", $str);    
		return $str;
	}

    function nettoyage2( $str, $charset='utf-8' ) {
		$str = utf8_decode($str);
		// $str = htmlentities( $str, ENT_NOQUOTES, $charset );
		
		$str = utf8_decode($str);
			 
		   
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
		$str = strtolower($str);     
		$str = preg_replace( '#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str );
		$str = preg_replace( '#&([A-za-z]{2})(?:lig);#', '\1', $str );
		$str = preg_replace( '#&[^;]+;#', '', $str );      
		
			
			
		$str = str_replace("-", "_", $str);    
		return $str;
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
    
}
