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

use Drupal\file\Entity\File;
use Drupal\ckan_admin\Utils\Api;
use Symfony\Component\HttpFoundation\Response;
use Drupal\ckan_admin\Utils\HelpFormBase;
use Drupal\Component\Render\FormattableMarkup; 
use Drupal\ckan_admin\Utils\Logger;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * Implements an example form.
 */
class typeColumnsForm extends HelpFormBase {


	protected $datasets;
	/**
	 * {@inheritdoc}
	 */
	public function getFormId() {
		return 'typeColumnsForm';
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
		\Drupal::service('page_cache_kill_switch')->trigger();
		
        $form = parent::buildForm($form, $form_state);
        $form['#attached']['library'][] = 'ckan_admin/typeColumns.form'; 

		$this->config = include(__DIR__ . "/../../config.php");
		$this->urlCkan = $this->config->ckan->url; 
		
		$selectedDatasetId = \Drupal::request()->query->get('dataset-id');
		$mainDataset = $this->loadDataset($selectedDatasetId);
        
		// Get all observatory
        $api = new Api;
		$orgs = $api->getAllOrganisations(true, false, true);
		$organizationOptions = array();
		$organizationOptions["-1"] = "----";
        foreach ($orgs as &$value) {
			$organizationOptions[$value[name]] = $value[display_name];
		}
		
			
		// select for table
		$form['filter_organisation'] = [
			'#type' => 'select',
			'#title' => $this->t('Sélection de l\'observatoire'),
			'#prefix' => '<div class="select-metadata">',
			'#suffix' => '</div>',
			'#options' => $organizationOptions,
			'#default_value' => isset($mainOrganisationId) ? $mainOrganisationId : null,
			'#ajax' => [
				'callback' => '::getMainDatasetsCallback',
      			'wrapper' => 'dataset-autocomplete-wrapper',
				'disable-refocus' => FALSE,
				'event' => 'change',
				'progress' => [
					'type' => 'throbber',
					'message' => $this->t('Chargement des connaissances...'),
				],
			],
		];
		
		$selectedOrganization = $form_state->getValue(['filter_organisation']);
		$form['selected_data'] = [
			'#type' => 'textfield',
			'#title' => t('Choix de la connaissance'),
			'#autocomplete_route_name' => 'ckan_admin.api.autocomplete.datasets',
			'#autocomplete_route_parameters' => ['organization' => $selectedOrganization],
			'#required' => TRUE,
			'#default_value' => isset($mainDataset) ? $mainDataset['metas']['name'] : null,
			// for some reason you  need to set '#validated' => 'true' other wise tou get :
			// An illegal choice has been detected. Please contact the site administrator.
			'#validated' => 'true',
			'#prefix' => '<div id="dataset-autocomplete-wrapper">',
			'#suffix' => '</div>',
			'#ajax' => [
				'callback' => '::getColumns',
				'disable-refocus' => FALSE,
				'event' => 'autocompleteclose',
				'progress' => [
					'type' => 'throbber',
					'message' => $this->t('Chargement des colonnes...'),
				]
			],
		];

		
		// $form['filtr_org'] = array(
        //     //'#prefix' =>'',
        //     '#type' => 'select',
        //     '#title' => t('Filtres :'),
        //     '#options' => $organizationList,
        //     '#empty_option' => t('----'),
        //     '#attributes' => array('style' => 'width: 50%;','onchange' => 'baba();'),
        //     '#ajax'         => [
        //         'callback'  => '::datasetCallback',
        //         'wrapper'   => 'selected_data',
		// 	],
        // );

        // $ids = array();
		// $form['selected_data'] = array(
		// 	'#type' => 'select',
		// 	'#options' => $ids,
		// 	'#attributes' => array(
		// 		'onchange' => 'getTableById()',
		// 		'id' => 'selected_data'
		// 	),
		// 	'#prefix' =>'<div id="selected_data">',
		// 	'#suffix' =>'</div>',
		// );
        
        $form['selected_data_id'] = array(
            '#type' => 'textfield',
            '#attributes' => array('style' => 'display:none'),
		);
			
		// table form 
			
		$form['table'] = array(
			'#type' => 'table',
			'#caption' => $this->t('Typage colonnes'),
			'#header' => array(
				"name" => $this->t(''),
				"intitule" => $this->t('Intitulé'),
				"intituleFacet" => $this->t("Intitulé FACETTE"),
				"facet" => array('data' => new FormattableMarkup('<div class="headerCheckbox"><input id="checkboxFacet" type="checkbox" onclick=":action" style="border-radius: 10px; font-size: 11px; margin: 3px 6px;">@name</input></div>',
					['@name' => $this->t('FACETTE'),
					':action' => 'checkAll("-facet","checkboxFacet")'])
				),

				"facetM" => array('data' => new FormattableMarkup('<div class="headerCheckbox"><input id="checkboxFacetM" type="checkbox" onclick=":action" style="border-radius: 10px; font-size: 11px; margin: 3px 6px;">@name</input></div>',
					['@name' => $this->t('FACETTE Multiple'),
					':action' => 'checkAll("-disjunctive","checkboxFacetM")'])
				),
				"tableau" => array('data' => new FormattableMarkup('<div class="headerCheckbox"><input id="checkboxTableau" type="checkbox" onclick=":action" style="border-radius: 10px; font-size: 11px; margin: 3px 6px;">@name</input></div>',
					['@name' => $this->t('Tableau'),
					':action' => 'checkAll("-table","checkboxTableau")'])
				),
				//$this->t('Infobulle carte'),
				"tri" => array('data' => new FormattableMarkup('<div class="headerCheckbox"><input id="checkboxTri" type="checkbox" onclick=":action" style="border-radius: 10px; font-size: 11px; margin: 3px 6px;">@name</input></div>',
					['@name' => $this->t('Tri'),
					':action' => 'checkAll("-sortable","checkboxTri")'])
				),
				//add export/api checkbox 
				"ExportDownload" => array('data' => new FormattableMarkup('<div class="headerCheckbox"><input id="checkboxExportDownload" type="checkbox" onclick=":action" style="border-radius: 10px; font-size: 11px; margin: 3px 6px;">@name</input></div>',
					['@name' => $this->t('Cacher à l\'export'),
					':action' => 'checkAll("-exportApi","checkboxExportDownload")'])
				),
				//add hideColumnsApi checkbox 
				"HideColumnsApi" => array('data' => new FormattableMarkup('<div class="headerCheckbox"><input id="checkboxHideColumnsApi" type="checkbox" onclick=":action" style="border-radius: 10px; font-size: 11px; margin: 3px 6px;">@name</input></div>',
					['@name' => $this->t('Cacher pour l\'API'),
					':action' => 'checkAll("-hideColumnsApi","checkboxHideColumnsApi")'])
				),
				"datePonctuel" => array('data' => new FormattableMarkup('<div class="headerCheckbox"><input id="checkboxDatePonctuel" type="checkbox" onclick=":action" style="border-radius: 10px; font-size: 11px; margin: 3px 6px;">@name</input></div>',
					['@name' => $this->t('Date ponctuel'),
					':action' => 'checkAll("-date","checkboxDatePonctuel")'])
				),
				"dateDebut" => array('data' => new FormattableMarkup('<div class="headerCheckbox"><input id="checkboxDateDebut" type="checkbox" onclick=":action" style="border-radius: 10px; font-size: 11px; margin: 3px 6px;">@name</input></div>',
					['@name' => $this->t('Date Début'),
					':action' => 'checkAll("-startdate","checkboxDateDebut")'])
				),
				"dateFin" => array('data' => new FormattableMarkup('<div class="headerCheckbox"><input id="checkboxDateFin" type="checkbox" onclick=":action" style="border-radius: 10px; font-size: 11px; margin: 3px 6px;">@name</input></div>',
					['@name' => $this->t('Date fin'),
					':action' => 'checkAll("-enddate","checkboxDateFin")'])
				),
				"images" => array('data' => new FormattableMarkup('<div class="headerCheckbox"><input id="checkboxImages" type="checkbox" onclick=":action" style="border-radius: 10px; font-size: 11px; margin: 3px 6px;">@name</input></div>',
					['@name' => $this->t('Images'),
					':action' => 'checkAll("-images","checkboxImages")'])
				),
				"nuageDeMot" => array('data' => new FormattableMarkup('<div class="headerCheckbox"><input id="checkboxNuageDeMot" type="checkbox" onclick=":action" style="border-radius: 10px; font-size: 11px; margin: 3px 6px;">@name</input></div>',
					['@name' => $this->t('Nuage de mot'),
					':action' => 'checkAll("-wordcount","checkboxNuageDeMot")'])
				),
				"nuageDeMotNombre" => array('data' => new FormattableMarkup('<div class="headerCheckbox"><input id="checkboxNuageDeMotNombre" type="checkbox" onclick=":action" style="border-radius: 10px; font-size: 11px; margin: 3px 6px;">@name</input></div>',
					['@name' => $this->t('Nuage de mot(nombre)'),
					':action' => 'checkAll("-wordcountnumber","checkboxNuageDeMotNombre")'])
				),
				"dateEtHeure" => array('data' => new FormattableMarkup('<div class="headerCheckbox"><input id="checkboxDateEtHeure" type="checkbox" onclick=":action" style="border-radius: 10px; font-size: 11px; margin: 3px 6px;">@name</input></div>',
					['@name' => $this->t('DATE ET HEURE'),
					':action' => 'checkAll("-dateTime","checkboxDateEtHeure")'])
				),
				"description" => $this->t("Description"),
				"libelleFriseChrono" => array('data' => new FormattableMarkup('<div class="headerCheckbox"><input id="checkboxLibelleFriseChrono" type="checkbox" onclick=":action" style="border-radius: 10px; font-size: 11px; margin: 3px 6px;">@name</input></div>',
					['@name' => $this->t('Libellé de Frise Chronologique'),
					':action' => 'checkAll("-title_for_timeLine","checkboxLibelleFriseChrono")'])
				),
				"descriptionFriseChrono" => array('data' => new FormattableMarkup('<div class="headerCheckbox"><input id="checkboxDescriptionFriseChrono" type="checkbox" onclick=":action" style="border-radius: 10px; font-size: 11px; margin: 3px 6px;">@name</input></div>',
					['@name' => $this->t('Description pour Frise Chronologique'),
					':action' => 'checkAll("-descr_for_timeLine","checkboxDescriptionFriseChrono")'])
				),
				"dateFriseChrono" => array('data' => new FormattableMarkup('<div class="headerCheckbox"><input id="checkboxDateFriseChrono" type="checkbox" onclick=":action" style="border-radius: 10px; font-size: 11px; margin: 3px 6px;">@name</input></div>',
					['@name' => $this->t('DATE pour Frise Chronologique'),
					':action' => 'checkAll("-date_timeline","checkboxDateFriseChrono")'])
				),
				"canEdit" => array('data' => new FormattableMarkup('<div class="headerCheckbox"><input id="checkboxCanEdit" type="checkbox" onclick=":action" style="border-radius: 10px; font-size: 11px; margin: 3px 6px;">@name</input></div>',
					['@name' => $this->t('Editable'),
					':action' => 'checkAll("-can_edit","checkboxCanEdit")'])
				),
				"mapDisplay" => array('data' => new FormattableMarkup('<div class="headerCheckbox"><input id="checkboxMapDisplay" type="checkbox" onclick=":action" style="border-radius: 10px; font-size: 11px; margin: 3px 6px;">@name</input></div>',
					['@name' => $this->t('Afficher sur la carte'),
					':action' => 'checkAll("-map_display","checkboxMapDisplay")'])
				),
				"poids" => $this->t('Poids'),

				//$this->t("image_url"),
			),
		);
        
		for ($i = 1; $i <= 1; $i++) {

			$form['table'][$i]['name'] = array(
				'#markup' => 'Name'      
			);
			//Intitulé
			$form['table'][$i]['Intitulé'] = array(
				'#type' => 'textfield',
				'#size' => 15,
			);
			//<!--facet_name?Opérateurs (nb de systèmes)-->   
			$form['table'][$i]['intitule_facette'] = array(
				'#type' => 'textfield',
				'#size' => 15,
			);      
		
			// facet
			$form['table'][$i]['facet'] = array(
				'#type' => 'checkbox',
			);

			// exportApi
			$form['table'][$i]['exportApi'] = array(
				'#type' => 'checkbox',
			);

			// hideColumnsApi
			$form['table'][$i]['hideColumnsApi'] = array(
				'#type' => 'checkbox',
			);
			
		
			// disjunctive  
			$form['table'][$i]['disjunctive'] = array(
				'#type' => 'checkbox',
			); 
		
			// table   
			$form['table'][$i]['table'] = array(
				'#type' => 'checkbox',
			);    
		
			/*// tooltip   
			$form['table'][$i]['tooltip'] = array(
				'#type' => 'checkbox',
			);*/
		
			// sortable  
			$form['table'][$i]['sortable'] = array(
				'#type' => 'checkbox',
			);   
			// date  
			$form['table'][$i]['date'] = array(
				'#type' => 'checkbox',
			); 
			// startDate  
			$form['table'][$i]['startDate'] = array(
				'#type' => 'checkbox',
			);
			// endDate  
			$form['table'][$i]['endDate'] = array(
				'#type' => 'checkbox',
			);    
			// images  
			$form['table'][$i]['images'] = array(
				'#type' => 'checkbox',
			);   

			// wordCount  
			$form['table'][$i]['wordCount'] = array(
				'#type' => 'checkbox',
			);

			 // wordCountNumber  
			$form['table'][$i]['wordCountNumber'] = array(
				'#type' => 'checkbox',
			); 
		
			//date 
			$form['table'][$i]['datetime'] = array(
				'#type' => 'checkbox',
			);
	 

			//Description
			$form['table'][$i]['description'] = array(
				'#type' => 'textfield',
				'#size' => 15,
			); 
		
			//title_for_timeLine
			$form['table'][$i]['title_for_timeLine'] = array(
				'#type' => 'textfield',
				'#size' => 15,
			);

			//descr_for_timeLine
			$form['table'][$i]['descr_for_timeLine'] = array(
				'#type' => 'textfield',
				'#size' => 15,
			);     

			//image_url
			//$form['table'][$i]['image_url'] = array(
			//    '#type' => 'textfield',
			//    '#size' => 15,
			//  );
		
			$form['table'][$i]['date_timeline'] = array(
				'#type' => 'textfield',
				'#size' => 15,
			); 
		
			$form['table'][$i]['can_edit'] = array(
				'#type' => 'textfield',
				'#size' => 15,
			); 
		
			$form['table'][$i]['map_display'] = array(
				'#type' => 'checkbox',
			); 

			//Poids
			$form['table'][$i]['Poids'] = array(
				'#type' => 'number',
				'#size' => 15,
			);
		}
		
		//tooltip
		$form['T3'] = array(
			'#markup' => '<h2 class="title">'.t('Configuration des Filtres prédéfinis').'</h2>',
		);

        $form['predefined_filter'] = array(
            '#markup' => '',
            '#type' => 'textfield',
            '#title' => $this->t('Filtres prédéfinis:'),
            '#attributes' => array('style' => 'width: 50%;'),
            '#required' => FALSE,
            '#maxlength' => 300
        );
		
		$form['text_message1'] = [
			'#prefix' => '<p>',
			'#suffix' => '</p>',
			'#markup' => $this->t('Vous pouvez ajouter les filtres sous la forme \'nom==valeur\' (séparer les filtres par des virgules)'),
		];
		
		$form['text_message2'] = [
			'#prefix' => '<p>',
			'#suffix' => '</p>',
			'#markup' => $this->t('Exemple: Filtre Opel et Mercedes==refine.marque=OPEL&refine.marque=MERCEDES,Filtre Opel==refine.marque=OPEL'),
		];
		
		//tooltip
		$form['T1'] = array(
			'#markup' => '<h2 class="title">'.t('Configuration des Infobulles Carte et Calendrier').'</h2>',
		);
		
		
		$form['tooltip'] = array(
			'#type' => 'container',
			'#title' => t(''),
		);
		
		$form['tooltip']["type"] = array(
			'#type' => 'select',
			'#title' => t('Type'),
			'#options' => array("standard" => "Standard", "html" => "Avancé (HTML)"),
		);
		
		$form['tooltip']["standard"] = array(
			'#type' => 'container',
			'#states' => array(
				'visible' => array(
					'#edit-type' => array('value' => 'standard'),
				),
			),
		);
		
		$form['tooltip']["standard"]["title"] = array(
			'#type' => 'select',
			'#title' => t('Titre'),
			'#options' => array(),
			'#validated' => TRUE
		);
		
		$form['tooltip']["standard"]["columns"] = array(
			'#markup' => '<div id="tooltip-standard" data-cols="' . $cols . '"></div>'
		);
		
		$form['tooltip']["standard"]["fields"] = array(
			'#type' => "textarea",
			'#attributes' => [
				'style' => 'display:none;'
			],
			'#validated' => TRUE,
			'#maxlength' => 4096
		);
		
		$form['tooltip']["html"] = array(
			'#type' => 'container',
			'#states' => array(
				'visible' => array(
					'#edit-type' => array('value' => 'html'),
				),
			),
		);
		
		$form['tooltip']["html"]["template"] = array(
			'#type' => "textarea",
			//'#type' => "text_format",
			'#resizable' => "vertical",
			"#rows" => 10,
			//'#format' => 'full_html',
			//'#allowed_formats' => 'full_html',
			//'#default_value' => '<p>The quick brown fox jumped over the lazy dog.</p>',
			'#prefix' => '<div class="row"><div class="col-md-4 col-xs-12">',
			'#suffix' => '</div>',
			//'#value' => 'test'
			// '#default_value' => '<h2 class="d4cwidget-map-tooltip__header" ng-show="!!getTitle(record)">\n'.
			// '<span ng-bind="getTitle(record)">'.
			// '</span>'.
			// '</h2>'.
			// '<ul style="display: block; list-style-type: none; color: #2c3f56; padding:0; margin:0;">'.
			// '<li  ng-repeat="field in context.dataset.extra_metas.visualization.map_tooltip_fields">'.
			// '<strong>{{field}}</strong> : {{record.fields[field]}}</li>'.
			// '</ul>',
		);
		
		$form['tooltip']["html"]["preview"] = array(
			'#markup' => '<div class="col-md-4 col-xs-12">
				<input type="button" class="btn-preview" value="Visualiser"/>
				<div id="preview" class="leaflet-container"></div>
			</div>',
			'#allowed_tags' => ['input', 'div']
		);
		
		$form['tooltip']["html"]["help"] = array(
			'#markup' => '<div class="col-md-4 col-xs-12 help"><p>Ce code html peut être dynamique, écrit en syntaxe AngularJs. De la même manière que les widget et vues personnalisées.</p>
						<p>La variable <code>record</code> est disponible et contient les champs : <ul> <li><code>record.fields</code> (liste des valeurs de l\'enregistrement)</li> <li><code>records.recordid</code> (identifiant de l\'enregistrement concerné)</li> <li><code>record.datasetid</code> (identifiant du jeu de données)</li></ul></p>
						<p>Tout widget, lié ou non au jeu de données, peut être intégré à l\'infobulle.</p>
						<p>Note : les rapports Vanilla liés au jeu de données seront automatiquement intégrés en fin de l\'infobulle.</p></div></div>'
		);

		// Couleur des points sur la carte en fonction d'un champ
		$form['T2'] = array(
			'#markup' => '<h2 class="title">'.t('Configuration de la carte').'</h2>',
		);
		
		$form['colorfield'] = array(
			'#type' => 'container',
			'#title' => t(''),
		);
		
		$form['colorfield']["selected_field"] = array(
			'#type' => 'select',
			'#title' => t('Colonne couleur:'),
			'#empty_option' => t('----'),
			'#validated' => TRUE,
		);
		

		$form['valider'] = array(
			'#type' => 'submit',
			'#value' => $this->t('Valider'),
		);
		 
		return $form;
	}

	function getMainDatasetsCallback(array &$form, FormStateInterface $form_state) {
		Logger::logMessage("getMainDatasetsCallback 1");

		$selectedOrganization = $form_state->getValue(['filter_organisation']);
		$form['selected_data']['#autocomplete_route_parameters'] = ['organization' => $selectedOrganization];
		return $form['selected_data'];
	}

	public function getColumns(array &$form, FormStateInterface $form_state) {
		$selectedDataset = $form_state->getValue(['selected_data']);

		$dataset = $this->loadDataset($selectedDataset);
		$resourceId = null;

		foreach ($dataset[metas][resources] as $resource) {
			if ($resource[format] == 'CSV') {
				$resourceId = $resource[id];
				break;
			}
		}

		$response = new AjaxResponse();
		$response->addCommand(new InvokeCommand(NULL, 'loadDataset', [$selectedDataset, $resourceId]));
		return $response;
	}

	public function loadDataset($datasetId, $applySecurity = true) {
		$api = new Api();
		return isset($datasetId) ? $api->getPackageShow2($datasetId, null, true, $applySecurity) : null;
	}
    
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$api = new Api;
		
		$selected_org = $form_state->getValue('filtr_org');
		$orgaFilter = "";
		if($selected_org!=''){
			$orgaFilter = '&q=organization:"'.$selected_org.'"';
		}
        $dataSet = $api->callPackageSearch_public_private('include_private=true&rows=1000&sort=title_string asc'.$orgaFilter, \Drupal::currentUser()->id());
			
        $dataSet = $dataSet->getContent();
        $dataSet = json_decode($dataSet, true);
		$dataSet = $dataSet[result][results];
		
		uasort($dataSet, function($a, $b) {
			$res =  strcasecmp($a['title'], $b['title']);
			return $res;
		});
		
		$this->datasets = $dataSet;


        //$form['#attached']['library'][] = 'ckan_admin/typeColumns.form';

		// $selectData = $form_state->getValue('selected_data');
        $selectData = $form_state->getValue('selected_data_id');
		$selectData = explode("%", $selectData);
        $id_data = $selectData[0];
        $id_resource = $selectData[1];   
        
        $fields = $api->getAllFieldsForTableParam($id_resource, 'true');

        $table_data = $form_state->getValue('table');
        
		$json=array();
        $json["resource_id"]=$fields[result][resource_id];
        $json["force"]='true';
        $json["fields"]=array();
        
        //array_push($json["fields"], $fields[result][fields][0]);

        for( $i=1; $i<count($fields[result][fields]); $i++){
            
            $notes='';
            $title='';
            $poids ='';
			 
            if ($table_data[$i][Intitulé]){
				$title = $table_data[$i][Intitulé];
            }

            if ($table_data[$i][Poids]){
				$poids = $table_data[$i][Poids];
            }
                     
            if ($table_data[$i][facet]){
               $facet = $table_data[$i][facet];
                if($facet==1){
                    $notes=$notes.'<!--facet-->,';
                }
//              else{
//                  $notes=$notes.'<!---->';
//              }
            }

            // check if exportapi field is true and add to notes array 
            if ($table_data[$i][exportApi]){
               $exportapi = $table_data[$i][exportApi];
                if($exportapi==1){
                    $notes=$notes.'<!--exportApi-->,';
                }
//              else{
//                  $notes=$notes.'<!---->';
//              }
            }

            // check if hideColumnsApi field is true and add to notes array 
            if ($table_data[$i][hideColumnsApi]){
               $hideColumnsApi = $table_data[$i][hideColumnsApi];
                if($hideColumnsApi==1){
                    $notes=$notes.'<!--hideColumnsApi-->,';
                }
//              else{
//                  $notes=$notes.'<!---->';
//              }
            }
            
            if ($table_data[$i][table]){
               $table = $table_data[$i][table];
                if($table==1){
                     $notes=$notes.'<!--table-->,'; 
                 }
//                 else{
//                    $notes=$notes.'<!---->';
//                }
            }

//            if ($table_data[$i][tooltip]){
//               $tooltip = $table_data[$i][tooltip];
//                 if($tooltip==1){
//                     $notes=$notes.'<!--tooltip-->,'; 
//                 }
////                  else{
////                    $notes=$notes.'<!----> ';
////                }
//            }

            if ($table_data[$i][sortable]){
               $sortable = $table_data[$i][sortable];
                if($sortable==1){
                    $notes=$notes.'<!--sortable-->,'; 
                }
//              else{
//                  $notes=$notes.'<!----> ';
//              }
            }
            
            if ($table_data[$i][disjunctive]){
				$disjunctive = $table_data[$i][disjunctive];
                if($disjunctive==1){
                    $notes=$notes.'<!--disjunctive-->,'; 
                }
//              else{
//                  $notes=$notes.'<!----> ';
//              }
            }

            if ($table_data[$i]['date']){
				$date = $table_data[$i]['date'];
                if($date==1){
                    $notes=$notes.'<!--date-->,'; 
                }
//              else{
//                  $notes=$notes.'<!----> ';
//              }
            }

            if ($table_data[$i][startDate]){
                $startdate = $table_data[$i][startDate];
                if($startdate==1){
                    $notes=$notes.'<!--startDate-->,'; 
                }
//              else{
//                  $notes=$notes.'<!----> ';
//              }
            }

            if ($table_data[$i][endDate]){
                $enddate = $table_data[$i][endDate];
                if($enddate==1){
                    $notes=$notes.'<!--endDate-->,';
                }
//              else{
//                  $notes=$notes.'<!----> ';
//              }
            }

            if ($table_data[$i][images]){
                $images = $table_data[$i][images];
                if($images==1){
                    $notes=$notes.'<!--images-->,';
                }
//              else{
//                  $notes=$notes.'<!----> ';
//              }
            }

            if ($table_data[$i][wordCount]){
                $wordcount = $table_data[$i][wordCount];
                if($wordcount==1){
                    $notes=$notes.'<!--wordcount-->,';
                }
//              else{
//                  $notes=$notes.'<!----> ';
//              }
            }

            if ($table_data[$i][wordCountNumber]){
                $wordcountNumber = $table_data[$i][wordCountNumber];
                if($wordcountNumber==1){
                    $notes=$notes.'<!--wordcountNumber-->,';
                }
//              else{
//                  $notes=$notes.'';
//              }
            }
            
            if ($table_data[$i][dateTime]){
                $dateTime = $table_data[$i][dateTime];
                if($dateTime==1){
                    $notes=$notes.'<!--timeserie_precision-->,';
                }
//              else{
//                  $notes=$notes.'';
//              }
            }
            
            if ($table_data[$i][intitule_facette]){
				$notes =$notes.'<!--facet_name?'.str_replace(' ', '_', $table_data[$i][intitule_facette]).'-->,';
            }
            
            if ($table_data[$i][description]){
				$notes =$notes.'<!--description?'.str_replace(' ', '_', $table_data[$i][description]).'-->,';
				//$notes =$notes.'<!--description?'.$table_data[$i][description].'-->,';
            }
            
            if ($table_data[$i][title_for_timeLine]){
                $title_for_timeLine = $table_data[$i][title_for_timeLine];
                if($title_for_timeLine == 1){
                    $notes=$notes.'<!--title_for_timeLine-->,';
                }
            }
            
            if ($table_data[$i][descr_for_timeLine]){
                $descr_for_timeLine = $table_data[$i][descr_for_timeLine];
                if($descr_for_timeLine == 1){
                    $notes=$notes.'<!--descr_for_timeLine-->,';
                }
            }
             
            if ($table_data[$i][image_url]){
                $image_url = $table_data[$i][image_url];
                if($image_url==1){
                    $notes=$notes.'<!--image_url-->,';
				}
            }
            
            if ($table_data[$i]['date_timeline']){
				$date = $table_data[$i]['date_timeline'];
                if($date==1){
					$notes=$notes.'<!--date_timeLine-->,'; 
				}
//              else{
//                  $notes=$notes.'<!----> ';
//              }
            }
            
            if ($table_data[$i][can_edit]){
                $canEdit = $table_data[$i][can_edit];
                if ($canEdit == 1){
                    $notes = $notes.'<!--can_edit-->,';
                }
            }

            if ($table_data[$i][map_display]) {
               $mapDisplay = $table_data[$i][map_display];
                if ($mapDisplay == 1) {
                    $notes=  $notes.'<!--map_display-->,'; 
                }
            }
			
			$notes = substr($notes, 0, -1);
			$fields[result][fields][$i][info][notes] = $notes;  
			$fields[result][fields][$i][info][label] = $title;
			$fields[result][fields][$i][info][poids] = $poids;
        
			array_push($json["fields"], $fields[result][fields][$i]);
		}

        $callUrl = $this->urlCkan . "/api/action/datastore_create";//create
        
		$return = $api->updateRequest($callUrl,$json,"POST");
		
		
		//tooltip
		$oldDataset = null;
		foreach($this->datasets as $d){
			Logger::logMessage("TRM - Datasets ");
			if($d["id"] == $id_data){
				Logger::logMessage("TRM - Test ");
				$oldDataset = $d;
			}
		}

		$tooltip = array();
		$tooltip["type"] = $form_state->getValue('type');
		if($tooltip["type"] == "html"){
			$tooltip["value"] = $form_state->getValue('template');
		} else {
			$tooltip["value"] = array();
			$tooltip["value"]["title"] = $form_state->getValue('title');
			$tooltip["value"]["fields"] = $form_state->getValue('fields');
		}
		
		$json = json_encode($tooltip);
		
		$extras = $oldDataset["extras"];
		$found = false;
		foreach($extras as &$e){
			if($e["key"] == "tooltip"){
				$e["value"] = $json;
				$found = true;
				break;
			}
		}
		if(!$found){
			$extras[count($extras)]['key'] = 'tooltip';
			$extras[(count($extras) - 1)]['value'] = $json;
		}

		//Couleur des points
		$selectedFieldColor = $form_state->getValue('selected_field');

		Logger::logMessage("Selected Field Color " . $selectedFieldColor);

		$found = false;
		foreach($extras as &$e){
			if($e["key"] == "FieldColor"){
				if (!($selectedFieldColor == '' || $selectedFieldColor == '----')) {
					Logger::logMessage("Setting field color " . $selectedFieldColor);

					$e["value"] = $selectedFieldColor;
				}
				else {
					Logger::logMessage("Clearing field color");

					$e["value"] = '';
				}
				$found = true;
				break;
			}
		}
		if(!$found && !($selectedFieldColor == '' || $selectedFieldColor == '----')) {
			Logger::logMessage("Setting field color " . $selectedFieldColor);
			
			$extras[count($extras)]['key'] = 'FieldColor';
			$extras[(count($extras) - 1)]['value'] = $selectedFieldColor;
		}

		//Predefined filters
		$predefinedFilters = $form_state->getValue('predefined_filter');

		Logger::logMessage("Predefined filters " . $predefinedFilters);

		$found = false;
		foreach($extras as &$e){
			if ($e["key"] == "PredefinedFilters"){
				if (!($predefinedFilters == '')) {
					Logger::logMessage("Setting predefined filters " . $predefinedFilters);

					$e["value"] = $predefinedFilters;
				}
				else {
					Logger::logMessage("Clearing predefined filters");

					$e["value"] = '';
				}
				$found = true;
				break;
			}
		}
		if(!$found && !($predefinedFilters == '')) {
			$extras[count($extras)]['key'] = 'PredefinedFilters';
			$extras[(count($extras) - 1)]['value'] = $predefinedFilters;
		}
		
		$oldDataset["extras"] = $extras;


		$callUrl = $this->urlCkan . "/api/action/package_update";
		$return = $api->updateRequest($callUrl, $oldDataset, "POST");
   
		$api->calculateVisualisations($id_data);
		
		\Drupal::messenger()->addMessage('Les données ont été sauvegardées');
	}

	// public function datasetCallback(array &$form, FormStateInterface $form_state){
	// 	$api = new Api;
		
	// 	$selected_org = $form_state->getValue('filtr_org');
    //     $dataSet = $api->callPackageSearch_public_private('include_private=true&rows=1000&sort=title_string asc', \Drupal::currentUser()->id(), $selected_org);
			
    //     $dataSet = $dataSet->getContent();
    //     $dataSet = json_decode($dataSet, true);
	// 	$dataSet = $dataSet[result][results];
		
	// 	uasort($dataSet, function($a, $b) {
	// 		$res =  strcasecmp($a['title'], $b['title']);
	// 		return $res;
	// 	});

	// 	$ids = array();
    //     $ids["new"] = "";
    //     // $tableData=array();
    //     for($i=0; $i<count($dataSet); $i++){
    //         for($j=0; $j<count($dataSet[$i][resources]); $j++){
    //             if($dataSet[$i][resources][$j][format]=='CSV'){
	// 				// $fields = $api->getAllFieldsForTableParam($dataSet[$i][resources][$j][id], 'true');
	// 				// $tableData[$i]=$fields;
						
	// 				$ids[$dataSet[$i][id].'%'.$dataSet[$i][resources][$j][id]]=$dataSet[$i][title];    
						
	// 				break;
    //             }
    //         }
	// 	}

	// 	$elem = [
    //         '#type' => 'select',
    //         '#options' => $ids,
    //         '#attributes' => [
    //             'onchange' => 'getTableById()', 
	// 			'id' => 'selected_data'
	// 		],
       
	// 	];
		
	// 	return $elem;
	// }
}