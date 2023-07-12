<?php

namespace Drupal\ckan_admin\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Form\MetadataForm;
use Drupal\ckan_admin\Utils\ResourceManager;
use Drupal\ckan_admin\Utils\Logger;
use Drupal\ckan_admin\Utils\PropertiesHelper;


class ManageDatasetForm extends MetadataForm
{

	public function getFormId()
	{
		return 'ManageDatasetForm';
	}

	/**
	 * data4citizen-type can be empty (classic dataset), 'visualization', 'api', 'sftp' or 'limesurvey'
	 */
	public function buildForm(array $form, FormStateInterface $form_state)
	{
		\Drupal::service('page_cache_kill_switch')->trigger();

		$type = $this->getType();

		$selectedDatasetId = \Drupal::request()->query->get('dataset-id');
		$tdbUrl = \Drupal::request()->query->get('tdb-url');

		$fileValidateExtensions = 'jpg jpeg gif png txt doc xls pdf ppt pps odt ods odp csv json xlsx geojson zip gml';
		$typesMime = $fileValidateExtensions;

		$includeSchemas = $type != 'visualization' && $type != 'tdb';
		$includeScheduler = $type == 'limesurvey' || $type == 'api' || $type == 'sftp' || $type == 'geo';
		$selectedDataset = parent::loadDataset($selectedDatasetId);

		$form['text']['#markup'] = t(isset($selectedDataset) ? '<h1>Modifier ' . $selectedDataset['metas']['title'] . '</h1>' : '<h1>Création d\'une connaissance</h1>');

		$form = parent::buildMetadataForm($form, $form_state, $selectedDataset, $includeSchemas, $includeScheduler, true);
		
        $form['progress-modal'] = array(
			'#markup' => '<div id="progress" class="progress-modal" display="none">
			</div>',
		);

		if (isset($selectedDataset)) {
			$datasetModel = parent::getDatasetModel($selectedDataset);
			$datasetModel = json_decode($datasetModel, true);

			$serviceType = $datasetModel['service-type'];
			$item = $datasetModel['item'];
			$format = $datasetModel['format'];
			$layer = $datasetModel['layer'];

			$authType = $datasetModel['auth-type'];
			$authKey = $datasetModel['auth-key'];
		}

		// Classic dataset
		if ($type == 'visualization') {
			// Do nothing
		}
		else if ($type == 'api') {
			// Add string box to enter API URL
			$form['api_url'] = array(
				'#type' => 'textfield',
				'#title' => t('URL de l\'API'),
				'#maxlength' => 1024,
				'#required' => TRUE,
				'#default_value' => isset($item) ? $item : '',
				'#description' => t('URL de l\'API à utiliser pour la connaissance'),
			);

			// Add format selection
			$form['format'] = array(
				'#type' => 'select',
				'#title' => t('Format'),
				'#options' => array(
					'json' => 'JSON',
					'xml' => 'XML',
					'csv' => 'CSV',
				),
				'#default_value' => isset($format) ? $format : 'csv',
				'#description' => t('Format de l\'API'),
			);

			// Add list of auth type (NO_AUTH, OAUTH20, API_KEY, BASIC_AUTH) - OAUTH2 and BASIC not implemented yet
			$form['auth_type'] = array(
				'#type' => 'select',
				'#title' => t('Type d\'authentification'),
				'#options' => array(
					'NO_AUTH' => 'Sans authentification',
					// 'BASIC_AUTH' => 'Authentification basique',
					'API_KEY' => 'Authentification par api key',
					// 'OAUTH20' => 'OAuth 2.0',
				),
				'#default_value' => isset($authType) ? $authType : 'none',
				'#description' => t('Type d\'authentification à utiliser pour l\'API'),
			);

			// Add string box to enter API key which is visible only if key is selected
			$form['auth_key'] = array(
				'#type' => 'textfield',
				'#title' => t('Nom de la clef'),
				'#default_value' => isset($authKey) ? $authKey : '',
				'#states' => array(
					'visible' => array(
						':input[name="auth_type"]' => array('value' => 'API_KEY'),
					),
				),
			);

			$form['auth_key_value'] = array(
				'#type' => 'textfield',
				'#title' => t('Valeur'),
				'#maxlength' => 2048,
				'#default_value' => '',
				'#description' => t('Cette valeur doit être renseigné à chaque fois pour des raisons de sécurité'),
				'#states' => array(
					'visible' => array(
						':input[name="auth_type"]' => array('value' => 'API_KEY'),
					),
				),
			);

			// Add button to try api
			$form['api_try'] = array(
				'#type' => 'button',
				'#value' => t('Tester l\'API'),
				'#ajax' => array(
					'callback' => '::tryApi',
					'wrapper' => 'api-try-wrapper',
					'effect' => 'fade',
				),
			);

			// Add div to display api try result
			$form['api_try_result'] = array(
				'#type' => 'markup',
				'#markup' => '<pre id="api-try-wrapper" class="api-try-response"></pre>',
			);
		}
		else if ($type == 'limesurvey') {
			// Do nothing
		}
		else if ($type == 'sftp') {
			$form['sftp_filter'] = array(
				'#type' => 'textfield',
				'#title' => t('Filtre sur le fichier (Expression régulière)'),
				'#maxlength' => 1024,
				'#required' => TRUE,
				'#default_value' => isset($item) ? $item : '',
				'#description' => t('Expression régulière pour filtrer le fichier à importer depuis le SFTP dédié à l\'observatoire'),
			);
		}
		else if ($type == 'tdb') {
			$form['tdb_url'] = array(
				'#type' => 'textfield',
				'#title' => t('URL du tableau de bord'),
				'#required' => TRUE,
				'#default_value' => isset($tdbUrl) ? $tdbUrl : (isset($item) ? $item : ''),
				'#description' => t('URL du tableau de bord à afficher'),
			);
		}
		else if ($type == 'geo') {
			// Add radio button to choose between WMS or WFS
			$form['service_type'] = array(
				'#type' => 'radios',
				'#title' => t('Type de service'),
				'#options' => array(
					'wms' => 'WMS',
					'wfs' => 'WFS',
				),
				'#default_value' => isset($serviceType) ? $serviceType : 'wms',
			);

			$form['scheduler']['#states'] = array(
				'visible' => array(
					':input[name="service_type"]' => array('value' => 'wfs'),
				),
			);

			$form['service_url'] = array(
				'#type' => 'textfield',
				'#title' => t('URL du service WMS/WFS'),
				'#required' => TRUE,
				'#default_value' => isset($item) ? $item : '',
				'#description' => t('URL du service WMS/WFS'),
			);

			$form['layer_name'] = array(
				'#type' => 'textfield',
				'#title' => t('Nom de la couche'),
				'#required' => TRUE,
				'#default_value' => isset($layer) ? $layer : '',
				'#description' => t('URL de la couche à afficher'),
			);
		}
		else {
			// Add message if a dataset is selected to say that the file is not mandatory
			if (isset($selectedDataset)) {
				$form['file']['#markup'] = t('<h4>Si vous souhaitez remplacer le fichier, veuillez le déposer ci-dessous. Sinon, laissez ce champ vide.</h4>');
			}

			$form['import_users_file'] = [
				'#type' => 'managed_file',
				'#title' => $this->t('Fichier à déposer'),
				'#progress_indicator' => 'bar',
				'#progress_message' => $this->t('Veuillez patienter...'),
				'#upload_location' => 'temporary://datasets',
				'#upload_validators' => [
					'file_validate_extensions' => array($typesMime),
				],
			];
		}

		// Group submit handlers in an actions element with a key of "actions" so
		// that it gets styled correctly, and so that other modules may add actions
		// to the form. This is not required, but is convention.
		$form['actions'] = [
			'#type' => 'actions',
		];

		$form['actions']['submit'] = [
			'#type' => 'submit',
			'#value' => isset($selectedDatasetId) ? $this->t('Modifier la connaissance') : $this->t('Créer la connaissance'),
			'#attributes' => [
				'onclick' => 'generateTaskUniqueId(); checkProgress();',
			],
		];

		if (isset($selectedDatasetId)) {
			$form['actions']['delete'] = [
				'#type' => 'submit',
				'#value' => $this->t('Supprimer la connaissance'),
				'#submit' => ['::deleteDataset'],
				'#limit_validation_errors' => [],
				'#attributes' => [
					'class' => ['btn', 'btn-danger'],
					'onclick' => 'if (!confirm("Voulez vous vraiment supprimer cette connaissance ?")) { return false; }'
				],
			];
		}
		
        $form['progress-modal'] = array(
			'#markup' => '<div id="progress" class="progress-modal" display="none">
			</div>',
		);

        $form['#attached']['library'][] = 'ckan_admin/ManageDatasetForm.form';
        $form['#attached']['library'][] = 'ckan_admin/editMetaDataFormModal.form';

		return $form;
	}

	/**
	 * Validate the title and the checkbox of the form
	 * 
	 * @param array $form
	 * @param \Drupal\Core\Form\FormStateInterface $form_state
	 * 
	 */
	public function validateForm(array &$form, FormStateInterface $form_state)
	{
		parent::validateForm($form, $form_state);

		$type = $this->getType();
		$selectedDatasetId = \Drupal::request()->query->get('dataset-id');
		if ($type == 'api') {
			$apiUrl = $form_state->getValue('api_url');
			if (empty($apiUrl)) {
				$form_state->setErrorByName('api_url', $this->t('L\'URL de l\'API est obligatoire'));
			}
		}
		else if ($type == 'tdb') {
			$apiUrl = $form_state->getValue('tdb_url');
			if (empty($apiUrl)) {
				$form_state->setErrorByName('tdb_url', $this->t('L\'URL du tableau de bord est obligatoire'));
			}
		}
		else if ($type == 'geo') {
			$apiUrl = $form_state->getValue('service_url');
			if (empty($apiUrl)) {
				$form_state->setErrorByName('service_url', $this->t('L\'URL du service est obligatoire'));
			}
		}
		else if (!isset($type) && !isset($selectedDatasetId)) {
			$importUsersFile = $form_state->getValue('import_users_file');
			if (empty($importUsersFile)) {
				$form_state->setErrorByName('import_users_file', $this->t('Le fichier à déposer est obligatoire'));
			}
		}
	}

	// try api callback
	function tryApi(array &$form, FormStateInterface $form_state) {
		// Get url API
		$apiUrl = $form_state->getValue('api_url');
		// Get format
		$format = $form_state->getValue('format');

		$authType = $form_state->getValue('auth_type');
		$authKey = $form_state->getValue('auth_key');
		$authValue = $form_state->getValue('auth_key_value');

		if ($authType == 'API_KEY') {
			$headerAuth = "$authKey: $authValue";
		}

		$options = array(
			CURLOPT_URL => $apiUrl,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => array(
				"cache-control: no-cache",
				"postman-token: 1b2b2b1b-2b2b-2b2b-2b2b-2b2b2b2b2b2b",
				$headerAuth
			),
		);

		// Call api with curl
		$curl = curl_init();
		curl_setopt_array($curl, $options);

		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);
		// Display result
		if ($err) {
			$result = "cURL Error #:" . $err;
		} else {
			$result = $response;
		}
		
		if ($format == 'json') {
			// Pretty print json
			$result = json_encode(json_decode($result), JSON_PRETTY_PRINT);
		}
		else if ($format == 'xml') {
			// Escape xml
			$result = htmlspecialchars($result);
		}
		else if ($format == 'csv') {
			// Pretty print csv
			$result = str_replace(";", " ; ", $result);
		}

		return [
			'#type' => 'markup',
			'#markup' => '<pre id="api-try-wrapper" class="api-try-response">' . $result . '</pre>',
		];
	}

	/**
	 * Form submission handler.
	 *
	 * @param array $form
	 *   An associative array containing the structure of the form.
	 * @param \Drupal\Core\Form\FormStateInterface $form_state
	 *   The current state of the form.
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$config = include(__DIR__ . "/../../config.php");

		$selectedDatasetId = \Drupal::request()->query->get('dataset-id');
		$type = $this->getType();
		$entityId = \Drupal::request()->query->get('entity-id');

		$api = new Api;
		try {
			// Type = visualization, api, limesurvey, sftp, document
			$datasetName = $this->getDatasetName($form_state);
			$organization = parent::getDatasetOrganisation($form_state);

			// Check if it is a modification
			$modifyIntegration = false;
			$isWMS = false;

			$datasetModel = array();
			$additionnalInfos = array();
			if ($type == 'api') {
				$integrationType = 'API';
				$item = $form_state->getValue('api_url');
				$format = $form_state->getValue('format');

				$authType = $form_state->getValue('auth_type');
				$authKey = $form_state->getValue('auth_key');
				$authValue = $form_state->getValue('auth_key_value');
				
				$datasetModel['item'] = $item;
				$datasetModel['format'] = $format;
				$datasetModel['auth-type'] = $authType;
				$datasetModel['auth-key'] = $authKey;

				$additionnalInfos['auth-type'] = $authType;
				if ($authType == 'API_KEY') {
					$additionnalInfos['auth-api-key-name'] = $authKey;
					$additionnalInfos['auth-api-key'] = $authValue;
				}
			}
			else if ($type == 'sftp') {
				$integrationType = 'SFTP';
				$item = $form_state->getValue('sftp_filter');
				$format = "";

				$datasetModel['item'] = $item;
				$datasetModel['format'] = $format;
			}
			else if ($type == 'limesurvey') {
				$integrationType = 'LIMESURVEY';
				$item = $entityId;
				$format = "";

				$datasetModel['item'] = $item;
				$datasetModel['format'] = $format;
			}
			else if ($type == 'visualization') {
				$modifyIntegration = false;

				// Get visualization to add dataset reference to metadata
				$visualization = $api->getVisualization($entityId);
				$datasetModel['item'] = $entityId;

				// We don't want to add reference to dataset for type cartograph and chartbuilder as it reference himself
				if ($visualization['type'] != 'cartograph' && $visualization['type'] != 'chartbuilder') {
					$referenceDatasetId = $visualization['dataset_id'];
					$datasetModel['reference-dataset-id'] = $referenceDatasetId;
				}
			}
			else if ($type == 'tdb') {
				$modifyIntegration = false;
				$item = $form_state->getValue('tdb_url');

				$datasetModel['item'] = $item;
			}
			else if ($type == 'geo') {
				$integrationType = 'API';

				$typeService = $form_state->getValue('service_type');
				$item = $form_state->getValue('service_url');
				$layerName = $form_state->getValue('layer_name');
				// TODO: Fix for now but we should test the service and check if json or gml (see vanilla)
				$format = "json";

				$datasetModel['service-type'] = $typeService;
				$datasetModel['item'] = $item;
				$datasetModel['layer'] = $layerName;
				$datasetModel['format'] = $format;

				$isWMS = $typeService == 'wms';

				$additionnalInfos['service-type'] = $typeService;
				$additionnalInfos['layer'] = $layerName;
			}
			else {
				$modifyIntegration = false;

				$integrationType = 'DOCUMENT';

				$documents = $form_state->getValue('import_users_file', 0);
				if (!empty($documents)) {
					$file = File::load($documents[0]);
					// $file->setPermanent();
					// $file->save();
					if ($file) {
						$filePath = $file->getFileUri();
						$filePath = \Drupal::service('file_system')->realpath($filePath);
	
						// Get file format from file
						$format = pathinfo($filePath, PATHINFO_EXTENSION);
					}
				}
			}

			$isUpdate = false;
			try {
				if (isset($selectedDatasetId) && isset($filePath) && !empty($filePath)) {
					$isUpdate = true;
				}
			} catch (\Exception $e) {
				\Drupal::messenger()->addMessage(t('Une erreur est survenue lors de la création ou modification de la connaissance. (Erreur: '. $e->getMessage() . ')'), 'error');
				$this->cleanResources($file);
				return;
			}

			$datasetId = $this->createOrUpdateDatasetId($form_state, $organization, $selectedDatasetId, $type, $entityId, json_encode($datasetModel));
			if ($datasetId == null) {
				$this->cleanResources($file);
				return;
			}

			if ($type == 'visualization') {
				$api->updateVisualization($entityId, null, $datasetId);
				
				$this->cleanResources($file);
				parent::redirectToDataset($form_state, $datasetId);
				return;
			}
			else if ($type == 'tdb') {
				//Do nothing
				
				$this->cleanResources($file);
				parent::redirectToDataset($form_state, $datasetId);
				return;
			}
			else if ($type == 'geo') {
				if ($isWMS) {
					// TODO: Manage multiples resources
					// For now we only manage one resource and delete the previous one if it has changed
					if ($modifyIntegration) {
						parent::deleteAllResources($datasetId);
					}
	
					parent::manageResourceUrl($datasetId, null, $item, $layerName, "", $typeService);
					
					$this->cleanResources($file);
					parent::redirectToDataset($form_state, $datasetId);
					return;
				}
				else {
					// TODO: Manage multiples resources
					// For now we only manage one resource and delete the previous one if it has changed
					if ($modifyIntegration) {
						parent::deleteAllResources($datasetId);
					}

					$this->manageWFS($api, $organization, $datasetId, $datasetName, $item, $layerName, false, $isUpdate, '', null, false);
					
					$this->cleanResources($file);
					parent::redirectToDataset($form_state, $datasetId);
					return;
				}
			}
			else {
				try {
					if ($integrationType == 'DOCUMENT' && isset($filePath) && !empty($filePath)) {
						
						// Not used for now
						// if ($urlGsheet) {
						// 	$resourceUrl = $resourceManager->manageGsheet($datasetId, $urlGsheet);
						// 	$this->manageResource($api, $resourceManager, $organization, $datasetId, $datasetName, $resourceId, $resourceUrl, $generateColumns, $isUpdate, '', $encoding, $validata, $unzipZip);
						// }
						$resourceId = null;
						if ($isUpdate) {
							$selectedDataset = parent::loadDataset($datasetId, false);
							$resources = $selectedDataset["metas"]["resources"];
							if (sizeof($resources) > 0 ) {
								$resourceId = $this->getLastDataResource($resources);
							}
						}

						$this->manageResource($api, $organization, $datasetId, $datasetName, $resourceId, $filePath, false, $isUpdate, '', null, false);

						$this->cleanResources($file);
						parent::redirectToDataset($form_state, $datasetId);
						return;
					}
					else {
						$this->cleanResources($file);
						parent::redirectToDataset($form_state, $datasetId);
						return;
					}
				} catch (\Exception $e) {
					\Drupal::messenger()->addMessage(t('Une erreur est survenue lors de la génération du processus de création de connaissance. (Erreur: '. $e->getMessage() . ')'), 'error');
					$this->cleanResources($file);
					return;
				}
			}

			$this->cleanResources($file);
			parent::redirectToDataset($form_state, $datasetId);
			return;
		} catch (\Exception $e) {
			Logger::logMessage($e->getMessage());
			\Drupal::messenger()->addMessage(t($e->getMessage()), 'error');
			$this->cleanResources($file);
		}
	}

	//TODO: Better management for resources (multiple resources)
	function getLastDataResource($resources) {
		$lastResource = null;
		foreach($resources as $key=>$value) {
			$resourceId = $value["id"];
			$mimeType = $value["mimetype"];
			$datastoreActive = $value["datastore_active"];
			
			if ($mimeType == "text/csv" && $datastoreActive == true) {
				$lastResource = $resourceId;
			}
		}
		return $lastResource;
	}

	function manageWFS($api, $organization, $datasetId, $datasetName, $urlWFS, $layerName, $generateColumns, $isUpdate, $description, $encoding, $unzipZip = true) {
		$resourceManager = new ResourceManager();
		$fileWFS = $resourceManager->manageWFS($urlWFS, $layerName);

		$results = $resourceManager->manageFileWithPath($datasetId, $generateColumns, $isUpdate, null, $fileWFS, $description, $encoding, $unzipZip);

		$this->displayResult($api, $datasetId, $datasetName, $organization, $resourceManager, $results);
	}

	function manageResource($api, $organization, $datasetId, $datasetName, $resourceId, $resourceUrl, $generateColumns, $isUpdate, $description, $encoding, $unzipZip = true) {
		$resourceManager = new ResourceManager();
		$results = $resourceManager->manageFileWithPath($datasetId, $generateColumns, $isUpdate, $resourceId, $resourceUrl, $description, $encoding, $unzipZip);

		$this->displayResult($api, $datasetId, $datasetName, $organization, $resourceManager, $results);
	}
	
	function displayResult($api, $datasetId, $datasetName, $organization, $resourceManager, $results) {
		$validataResources = array();
		foreach ($results as &$result) {

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

		// We validate the data, if the user ask for it (put it in ResourceManager someday)
		// Not used for now
		// if ($validata != "non_valider") {
	
		// 	for ($v=0; $v < count($validataResources); $v++) {

		// 		$validataUrl = "https://go.validata.fr/api/v1/validate?schema=https://git.opendatafrance.net/scdl/deliberations/raw/master/schema.json&url=" . $validataResources[$v];
		// 		$validataResult = $resourceManager->validateData($validataUrl);

		// 		if ($validataResult[report][valid] == false) {
		// 			$errorsValid = $validataResult[report][tables][0][errors];
		// 			for ($i = 0; $i < count($errorsValid); $i++) {
						
		// 				\Drupal::messenger()->addMessage(t(($i + 1) . '. Code:' . $errorsValid[$i][code] . ' | Message:' . $errorsValid[$i][message]), 'warning');
						
		// 				if($i>5){
		// 					break;
		// 				}
		// 			}
		// 		} 
		// 		else if ($validataResult[report][valid] == true) {
		// 			\Drupal::messenger()->addMessage('Les données ont été validées');
		// 		}
		// 	}
		// }

		//We update the visualisation's icons
		$api->calculateVisualisations($datasetId);

		//We generate the CSW File according to the config file
		$resourceManager->manageCSWXmlFile($organization, $datasetId, $datasetName);
	}

	function cleanResources($file)  {
		if (isset($file)) {
			$file->delete();
		}
	}

	function getType() {
		$type = \Drupal::request()->query->get('data4citizen-type');
		if (isset($type)) {
			return $type;
		}

		$currentPath = \Drupal::service('path.current')->getPath();
		if (strpos($currentPath, '/databfc/ro/datasets/manage/sftp') !== false) {
			return 'sftp';
		}
		else if (strpos($currentPath, '/databfc/ro/datasets/manage/api') !== false) {
			return 'api';
		}
		else if (strpos($currentPath, '/admin/config/data4citizen/manageGeoDatasetForm') !== false) {
			return 'geo';
		}
		else {
			return null;
		}
	}
}
