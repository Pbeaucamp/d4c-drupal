<?php
/**
 * @file
* Contains \Drupal\search_api_solr_admin\Form\QueryForm.
*/

namespace Drupal\ckan_admin\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\ResourceManager;
use Drupal\ckan_admin\Utils\HelpFormBase;
use Drupal\Core\Url;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\ckan_admin\Utils\Logger;



/**
 * Implements an example form.
 */
class PackagesForm extends HelpFormBase {
	
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
		
        $form['progress-modal'] = array(
			'#markup' => '<div id="progress" class="progress-modal" display="none">
			</div>',
		);

		$form['jdd'] = array(
			'#title' => t('Importer un jeu de données : '),
			'#type' => 'managed_file',
			'#upload_location' => 'public://dataset/',
			'#upload_validators' => array(
				'file_validate_extensions' => array('zip'),
			),
			'#required' => FALSE,
			'#size' => 10,
			'#suffix' => '</div>',
		);

    	$form['orga_selected_input'] = array(
            '#markup' => '',
            '#type' => 'textfield',
            '#attributes' => array('style' => 'width: 50%;'),
			'display' => none,
			'#maxlength' => 300
		);

		$form['generated_task_id'] = array(
            '#type' => 'textfield',
            '#attributes' => array('style' => 'display:none'),
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
											[':action' => "openExportPopup(event)", 
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


    //submit form
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
		$this->urlCkan = $this->config->ckan->url;

		$userId = "*" . \Drupal::currentUser()->id() . "*";
		$users = \Drupal\user\Entity\User::loadMultiple();
		
        $api = new Api();
        $resourceManager = new ResourceManager();

        $orgavalue = $form_state->getValue('orga_selected_input');
		$generatedTaskId = $form_state->getValue('generated_task_id');
		
		// Define security
		$security = $resourceManager->defineSecurity($userId, $users);
		
		$organization="";
	    $orga = $api->getAllOrganisations();
		foreach ($orga as $key => $value) {
			if($value["display_name"] == $orgavalue || $value["title"] == $orgavalue || $value["name"] == $orgavalue) {
				$organization = $value["id"];
			}	
		}

        $resources = $form_state->getValue('jdd', 0);
        if (isset($resources[0]) && !empty($resources[0])) {
			$resourceUrl = $resourceManager->manageFile($resources[0]);

			$this->manageResource($api, $resourceManager, $generatedTaskId, $resourceUrl, $security, $organization);
		}
	}
	
	function manageResource($api, $resourceManager, $generatedTaskId, $resourceUrl, $security, $organization) {
		$validataResources = array();

		$results = $resourceManager->managePackage($generatedTaskId, $resourceUrl, $security, $organization);

		Logger::logMessage("Package manager result '" . json_encode($results) . "'");
	  
		$datasetId = $results['datasetId'];
		$resources = $results['resources'];
		foreach ($resources as &$result) {

			foreach ($result as $key => $value) {
				if ($value['status'] == 'complete') {
					if ($value['type'] == 'DATAPUSHER') {
						$validataResources[] = $value['resourceUrl'];

						\Drupal::messenger()->addMessage("La ressource '" . $value['filename'] ."' a été ajouté sur le jeu de données.");
					}
					else if ($value['type'] == 'CLUSTER') {
						\Drupal::messenger()->addMessage("Les clusters ont été générés.");
					}
				}
				else if ($value['status'] == 'pending') {
					$validataResources[] = $value['resourceUrl'];

					\Drupal::messenger()->addMessage("La ressource '" . $value['filename'] ."' est en cours d'insertion dans l'application, le processus peut durer quelques minutes en fonction de la taille du fichier.", 'warning');
				}
				else if ($value['status'] == 'error') {
					if ($value['type'] == 'DATAPUSHER') {
						\Drupal::messenger()->addMessage("Une erreur est survenue lors de l'ajout de '" . $value['filename'] . "' (" . $value['message'] . ")", 'error');
					}
					else if ($value['type'] == 'CLUSTER') {
						\Drupal::messenger()->addMessage("Une erreur est survenue lors de la création des clusters (" . $value['message'] . ")", 'error');
					}
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
		$url = Url::fromRoute('ckan_admin.extension_package_data4citizen', [], ['query' => ["page" => 0, 'orga' => $filters["orga"], 'q' => $filters["q"], 'type' => $filters["type"]]]);
		$form_state->setRedirectUrl($url);
	}

	// clear filter function
	public function submitclear(array &$form, FormStateInterface $form_state){ 
		// Set the provided filter value in the storage.
		$filters = array();
		$filters["orga"] = "";
		$filters["q"] = "";
		$filters["type"] = "";
		$url = Url::fromRoute('ckan_admin.extension_package_data4citizen', [], ['query' => ["page" => 0, 'orga' => $filters["orga"], 'q' => $filters["q"], 'type' => $filters["type"]]]);
		$form_state->setRedirectUrl($url);
	}

}
