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
		$form['#attached']['library'][] = 'ckan_admin/typeColumns.form';
        $form = parent::buildForm($form, $form_state);
         
        //$config = \Drupal::service('config.factory')->getEditable('ckan_admin.typeColumnsForm');
        
		
        $this->config = json_decode(file_get_contents(__DIR__ ."/../../config.json"));
		$this->urlCkan = $this->config->ckan->url; 
        
        $api = new Api;
        $dataSet= $api->callPackageSearch_public_private('include_private=true&rows=1000&sort=title_string asc', \Drupal::currentUser()->id());
        $dataSet = $dataSet->getContent();
        $dataSet = json_decode($dataSet,true);
        $dataSet = $dataSet[result][results];
		$this->datasets = $dataSet;
		
        $ids=array();
        $tableData=array();
       
        for($i=0; $i<count($dataSet); $i++){
            for($j=0; $j<count($dataSet[$i][resources]); $j++){
                if($dataSet[$i][resources][$j][format]=='CSV'){
					$filds = $api->getAllFieldsForTableParam($dataSet[$i][resources][$j][id], 'true');
					$tableData[$i]=$filds;
						
					$ids[$dataSet[$i][id].'%'.$dataSet[$i][resources][$j][id]]=$dataSet[$i][title];    
						
					break;
                }
                else{
                    $tableData[$i]='no_data_csv';
                    $ids[$dataSet[$i][id].'%no_data_csv']=$dataSet[$i][name];
                }
            }
        }
		
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
		error_log($callUrlOrg, true);
		error_log($cle, true);
        curl_setopt_array($curlOrg, $optionst);
        $orgs = curl_exec($curlOrg);
        curl_close($curlOrg);
        $orgs = json_decode($orgs, true);
        
		///////////////////////////////organization_list////
		
		$organizationList = array();

        foreach ($orgs[result] as &$value) {
            $organizationList[$value[name]] = $value[display_name];
        }
		
		//$rendered_message = \Drupal\Core\Render\Markup::create('<pre>' . print_r( $dataSet, true) . '</pre>');s
		// drupal_set_message($rendered_message);
			
		// select for table
		
		$form['filtr_org'] = array(
            //'#prefix' =>'',
            '#type' => 'select',
            '#title' => t('Organisation :'),
            '#options' => $organizationList,
            '#empty_option' => t('----'),
            '#attributes' => array('style' => 'width: 50%;','onchange' => 'baba();'),
            '#ajax'         => [
                'callback'  => '::datasetCallback',
                'wrapper'   => 'selected_data',
			],
        );

		$form['selected_data'] = array(
			'#type' => 'select',
			'#title' => t('Sélectionner des données'),
			'#options' => $ids,
			'#attributes' => array(
				'onchange' => 'getTableById()',
				//'id' => 'selected_data'
			),
			'#prefix' =>'<div id="selected_data">',
			'#suffix' =>'</div>',
		);
			
		// table form 
			
		$form['table'] = array(
			'#type' => 'table',
			'#caption' => $this->t('Table'),
			'#header' => array(
				$this->t(''),
				$this->t('Intitulé'),
				$this->t("Intitulé FACETTE"),
				$this->t('FACETTE'),
				$this->t('FACETTE Multiple'),
				$this->t('Tableau'),
				//$this->t('Infobulle carte'),
				$this->t('Tri'),
				$this->t('Date ponctuel'),
				$this->t('Date Début'),
				$this->t('Date fin'),    
				$this->t('Images'),    
				$this->t('Nuage de mot'),    
				$this->t('Nuage de mot(nombre)'),    
				$this->t("DATE ET HEURE"),    
				$this->t("Description"),
				$this->t("Libellé de Frise Chronologique"), 
				$this->t("Description pour Frise Chronologique"),
				$this->t("DATE pour Frise Chronologique"),  
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
		}
		
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
			'#type' => "textfield",
			'#attributes' => [
				'style' => 'display:none;'
			]
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
		

		$form['valider'] = array(
			'#type' => 'submit',
			'#value' => $this->t('Valider'),
		);
		 
		return $form;
	}
    
    
	public function submitForm(array &$form, FormStateInterface $form_state)
	{
        //$form['#attached']['library'][] = 'ckan_admin/typeColumns.form';

		$selectData = $form_state->getValue('selected_data');
        
        $selectData = explode("%", $selectData);
        $id_data = $selectData[0];
        $id_resource = $selectData[1];   
        
        $api = new Api;
        $filds = $api->getAllFieldsForTableParam($id_resource, 'true');

        $table_data = $form_state->getValue('table');
        
		$json=array();
        $json["resource_id"]=$filds[result][resource_id];
        $json["force"]='true';
        $json["fields"]=array();
        
        //array_push($json["fields"], $filds[result][fields][0]);
        
        for( $i=1; $i<count($filds[result][fields]); $i++){
            
            $notes='';
            $title='';
			 
            if ($table_data[$i][Intitulé]){
				$title = $table_data[$i][Intitulé];
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
			
			$notes =substr($notes, 0, -1);
			$filds[result][fields][$i][info][notes]=$notes;  
			$filds[result][fields][$i][info][label]=$title;
        
			array_push($json["fields"], $filds[result][fields][$i]);
        }

        $callUrl = $this->urlCkan . "/api/action/datastore_create";//create
        
		$return = $api->updateRequest($callUrl,$json,"POST");
		
		
		//tooltip
		$oldDataset;
		foreach($this->datasets as $d){
			if($d["id"] == $id_data){
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
		//drupal_set_message($json);
		
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
		$oldDataset["extras"] = $extras;
		
		$callUrl = $this->urlCkan . "/api/action/package_update";
		$return = $api->updateRequest($callUrl, $oldDataset, "POST");
   
		$api->calculateVisualisations($id_data);
		
		drupal_set_message('Les données ont été sauvegardées');
        //drupal_set_message('<pre>'. print_r(json_encode($filds),true).'</pre>');
	}

	public function datasetCallback(array &$form, FormStateInterface $form_state){
		//drupal_set_message('<pre>'. print_r($_SESSION, true) .'</pre>'); 
   
        $api = new Api;
		
		$selected_org = $form_state->getValue('filtr_org');
		$orgaFilter = "";
		if($selected_org!=''){
			$orgaFilter = '&q=organization:"'.$selected_org.'"';
		}
error_log($orgaFilter);
        $dataSet = $api->callPackageSearch_public_private('include_private=true&rows=1000&sort=title_string asc'.$orgaFilter, \Drupal::currentUser()->id());
			
        $dataSet = $dataSet->getContent();
        $dataSet = json_decode($dataSet, true);
        $dataSet = $dataSet[result][results];
        
		$ids = array();

		/*foreach($dataSet as &$ds) {
			$ids[$ds[id]] = $ds[title];
		}*/
		
        $tableData=array();
       
        for($i=0; $i<count($dataSet); $i++){
            for($j=0; $j<count($dataSet[$i][resources]); $j++){
                if($dataSet[$i][resources][$j][format]=='CSV'){
					$filds = $api->getAllFieldsForTableParam($dataSet[$i][resources][$j][id], 'true');
					$tableData[$i]=$filds;
						
					$ids[$dataSet[$i][id].'%'.$dataSet[$i][resources][$j][id]]=$dataSet[$i][title];    
						
					break;
                }
                else{
                    $tableData[$i]='no_data_csv';
                    $ids[$dataSet[$i][id].'%no_data_csv']=$dataSet[$i][name];
                }
            }
        }
          error_log(count($ids));
		$form['selected_data'] = [
            '#type' => 'select',
			'#title' => t('Sélectionner des données'),
			'#options' => $ids,
			'#attributes' => array(
				'onchange' => 'getTableById()',),
			'#prefix' =>'<div id="selected_data">',
			'#suffix' =>'</div>',
        ];

		return $form['selected_data'];
	}  
}