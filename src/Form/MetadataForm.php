<?php

namespace Drupal\ckan_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;

use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\MetadataDefinition;
use Drupal\ckan_admin\Utils\Logger;
use Drupal\ckan_admin\Utils\Schedule;
use Drupal\ckan_admin\Utils\ResourceManager;

use Drupal\data_bfc\Utils\VanillaApiManager;

abstract class MetadataForm extends FormBase {

	public function getFormId() {
		return 'MetadataForm';
	}

	public function buildMetadataForm(array $form, FormStateInterface $form_state, $selectedDatasetId = null, $includeSchemas = false, $includeScheduler = false) {
		$config = include(__DIR__ . "/../../config.php");
		$organization = $config->client->client_organisation;

		$hasDataBfc = \Drupal::moduleHandler()->moduleExists('data_bfc');

		// Get drupal username
		$account = \Drupal::currentUser();
		$username = $account->getAccountName();

        $api = new Api;

		if ($selectedDatasetId) {
			Logger::logMessage("Selected dataset id " . $selectedDatasetId);
			$selectedDataset = $api->getPackageShow2($selectedDatasetId, null, true, true);
			$selectedDataset = $selectedDataset['metas'];

			$tags = $selectedDataset['keyword'] ? $tags = implode(",", $selectedDataset['keyword']) : '';

			// $extras = $selectedDataset['extras'];
			// $mentionLegales = '';
			// foreach ($extras as $value) {
			// 	if ($value['key'] == 'mention_legales') {
			// 		$mentionLegales = $value['value'];
			// 	}
			// }

			$dateDataset = array_filter($selectedDataset["extras"], function ($f) {
				return $f["key"] == "date_dataset";
			});
			$dateDataset = array_values($dateDataset)[0]["value"];

			$dateDeposit = array_filter($selectedDataset["extras"], function ($f) {
				return $f["key"] == "date_deposit";
			});
			$dateDeposit = array_values($dateDeposit)[0]["value"];
		}

		$licences = $api->getLicenses();
		
		
        $licenceOptions = array();
		$selectedLicence = '';
        foreach ($licences['result'] as &$value) {
            $licenceOptions[$value['id']] = $value['title'];

			if ($selectedDataset && $selectedDataset['license'] == $value['title']) {
				$selectedLicence = $value['id'];
			}
        }

		// Add helper text for users
		// $form['integration']['help'] = [
		// 	'#type' => 'markup',
		// 	'#markup' => $this->t('Les métadonnées sont des informations sur les connaissances. Elles sont utilisées pour décrire les connaissances et les rendre plus facilement accessibles. Elles sont également utilisées pour décrire les connaissances lors de leur publication sur l\'observatoire.'),
		// 	// Add style to make the text bigger and a padding
		// 	'#attributes' => [
		// 		'style' => 'font-size: 1.2em; padding: 1em;',
		// 	],
		// ];

		$form['dataset_name'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Nom de la connaissance'),
			'#required' => TRUE,
			'#default_value' => $selectedDataset != null ? $selectedDataset['title'] : '',
		];
		
		$form['dataset_licence'] = array(
            '#type' => 'select',
            '#title' => t('Licence :'),
            '#options' => $licenceOptions,
            '#empty_option' => t('----'),
			'#required' => TRUE,
			'#default_value' => $selectedDataset != null ? $selectedLicence : '',
        );

		$form['dataset_private'] = array(
            '#type' => 'select',
            '#title' => t('Visibilité :'),
            '#options' => array('Publique', 'Privée'),
			'#required' => TRUE,
			'#default_value' => $selectedDataset != null && $selectedDataset['private'] == 1 ? 1 : 0,
        );

		$form['integration_option'] = [
			'#type' => 'details',
			'#title' => $this->t('PLUS DE METADONNEES'),
			'#open' => false,
			'#tree' => TRUE,
		];

		// Add field contributeur
		$form['integration_option']['dataset_contributor'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Contributeur'),
			'#default_value' => $selectedDataset != null ? $selectedDataset['producer'] : '',
		];

		$form['integration_option']['dataset_description'] = [
			'#type' => 'textarea',
			'#title' => $this->t('Description'),
			'#default_value' => $selectedDataset != null ? $selectedDataset['description'] : '',
		];

		// Add date field and set default value to today
		$form['integration_option']['dataset_date'] = [
			'#type' => 'date',
			'#title' => $this->t('Date de création'),
			'#default_value' => isset($dateDataset) ? $dateDataset : date('Y-m-d'),
		];

		// Add date field and set default value to today
		$form['integration_option']['dataset_deposit_date'] = [
			'#type' => 'date',
			'#title' => $this->t('Date de versement'),
			'#default_value' => isset($dateDeposit) ? $dateDeposit : date('Y-m-d'),
		];

		// Add field organisation
		$form['integration_option']['dataset_organisation'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Propriétaire'),
			'#default_value' => $organization,
			'#disabled' => true,
		];

		// Add user field
		$form['integration_option']['dataset_user'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Utilisateur'),
			'#default_value' => $username,
			'#disabled' => true,
		];

		$form['integration_option']['dataset_tags'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Mots-clés (séparer par des virgules, les seuls symboles autorisés sont -"_")'),
			'#required' => FALSE,
			'#maxlength' => 300,
			'#default_value' => $tags,
		];

		// Add field encoding with utf-8 as default
		$form['integration_option']['dataset_encoding'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Encodage'),
			'#default_value' => 'UTF-8'
		];

		// Check if we need to include schemas
		if ($includeSchemas && $hasDataBfc) {
			$vanillaManager = new VanillaApiManager();
			try {
				$schemas = $vanillaManager->getValidationSchemas();
			} catch (\Exception $e) {
				$schemas = array();
			}

			$schemasOptions = array();
			foreach ($schemas as $schema) {
				$schemasOptions[$schema] = $schema;
			}

			$form['integration_option']['schemas'] = [
			  '#type' => 'checkboxes',
			  '#title' => $this->t('Schémas de validation'),
			  '#options' => $schemasOptions
			];
		}

		// Check if we need to include scheduler
		if ($includeScheduler && $hasDataBfc) {

			$form['scheduler'] = [
				'#type' => 'details',
				'#title' => $this->t('Planification'),
				'#open' => false,
				'#tree' => TRUE,
			];

			// Add date and time field
			$form['scheduler']['scheduler_date'] = [
				'#type' => 'datetime',
				'#title' => $this->t('Date de lancement'),
				'#default_value' => DrupalDateTime::createFromTimestamp(time()),
				'#date_format' => 'd/m/Y H:i:s',
				// '#attributes' => [
				// 	'step' => 60,
				// ]
			];

			// Add a list box for the period (YEAR, MONTH, WEEK, DAY, HOUR)
			$form['scheduler']['scheduler_period'] = [
				'#type' => 'select',
				'#title' => $this->t('Période'),
				'#options' => [
					'HOUR' => $this->t('Toutes les X heures'),
					'DAY' => $this->t('Tous les X jours'),
					'WEEK' => $this->t('Toutes les X semaines'),
					'MONTH' => $this->t('Tous les X mois'),
					'YEAR' => $this->t('Toutes les X années'),
				],
				'#default_value' => 'DAY',
			];
			

			// Add numeric field for interval
			$form['scheduler']['scheduler_interval'] = [
				'#type' => 'number',
				'#title' => $this->t('Intervalle'),
				'#default_value' => 1,
			];
		}

		// Add section for inspire metadata
		$form['integration_option']['inspire_option'] = [
			'#type' => 'details',
			'#title' => $this->t('METADONNEES INSPIRE'),
			'#open' => false,
			'#tree' => TRUE,
		];

		// Add markup to explain that the section is not implemented yet
		$form['integration_option']['inspire_option']['inspire_not_implemented'] = [
			'#markup' => '<p>La section "METADONNEES INSPIRE" est en cours de développement.</p>',
		];

		return $form;
	}

	/**
	 * Validate the title and the checkbox of the form
	 * 
	 * @param array $form
	 * @param \Drupal\Core\Form\FormStateInterface $form_state
	 * 
	 */
	public function validateForm(array &$form, FormStateInterface $form_state) {
		parent::validateForm($form, $form_state);
	}

	public function getDatasetTitle(FormStateInterface $form_state) {
		return $form_state->getValue('dataset_name');
	}

	public function getDatasetLicence(FormStateInterface $form_state) {
		return $form_state->getValue('dataset_licence');
	}

	public function getDatasetIsPrivate(FormStateInterface $form_state) {
		return $form_state->getValue('dataset_private') == '1';
	}

	public function getDescription(FormStateInterface $form_state) {
		return $form_state->getValue(['integration_option','dataset_description']);
	}

	public function getTags(FormStateInterface $form_state) {
		return $form_state->getValue(['integration_option','dataset_tags']);
	}

	public function getEncoding(FormStateInterface $form_state) {
		return $form_state->getValue(['integration_option','dataset_encoding']);
	}

	public function getDatasetDate(FormStateInterface $form_state) {
		return $form_state->getValue(['integration_option','dataset_date']);
	}

	public function getDatasetDepositDate(FormStateInterface $form_state) {
		return $form_state->getValue(['integration_option','dataset_deposit_date']);
	}

	public function getDatasetOrganisation(FormStateInterface $form_state) {
		return $form_state->getValue(['integration_option','dataset_organisation']);
	}

	public function getDatasetContributor(FormStateInterface $form_state) {
		return $form_state->getValue(['integration_option','dataset_contributor']);
	}

	public function getDatasetUsername(FormStateInterface $form_state) {
		return $form_state->getValue(['integration_option','dataset_user']);
	}

	public function getMetadata(FormStateInterface $form_state) {
		$description = $this->getDescription($form_state);
		
		$metadata = array();
		$metadata[] = new MetadataDefinition('description', $description);
		// For now we set the dataset to private. TODO: make it configurable
		$metadata[] = new MetadataDefinition('dataset-private', 'true');
		return $metadata;
	}

	public function getSchemas(FormStateInterface $form_state) {
		$schemasOptions = $form_state->getValue(['integration_option','schemas']);

		$schemas = array();
		foreach ($schemasOptions as $key => $value) {
			if (strcmp($key, $value) == 0) {
				$schemas[] = $key;
			}
		}
		return $schemas;
	}

	public function getSchedule(FormStateInterface $form_state) {
		$schedulerDate = $form_state->getValue(['scheduler','scheduler_date']);
		$schedulerPeriod = $form_state->getValue(['scheduler','scheduler_period']);
		$schedulerInterval = $form_state->getValue(['scheduler','scheduler_interval']);
		$schedulerInterval = (int) $schedulerInterval;

		return new Schedule($schedulerPeriod, $schedulerInterval, $schedulerDate);
	}

	public function getDatasetName($form_state) {
        $title = $this->getDatasetTitle($form_state);

		$resourceManager = new ResourceManager;
		return $resourceManager->defineDatasetName($title);
	}

	public function createOrUpdateDatasetId($form_state, $organization, $selectedDatasetId, $type, $entityId = null) {
		$userId = "*" . \Drupal::currentUser()->id() . "*";

		$api = new Api;
		$users = $api->getAdministrators();
		$resourceManager = new ResourceManager;
        
		$datasetName = $this->getDatasetName($form_state);
        $title = $this->getDatasetTitle($form_state);
        $description = $this->getDescription($form_state);
        $dateDataset = $this->getDatasetDate($form_state);
        $tags = $this->getTags($form_state);
        $licence = $this->getDatasetLicence($form_state);
        $isPrivate = $this->getDatasetIsPrivate($form_state);
		$contributor = $this->getDatasetContributor($form_state);
		$dateDeposit = $this->getDatasetDepositDate($form_state);
		$username = $this->getDatasetUsername($form_state);

		// Not used for now
		$mention_legales = "";
        // $mention_legales = $form_state->getValue('dataset_mention_legales');
		$themes = "";
		// if ($form_state->getValue('selected_themes') != NULL) {
		// 	$selectedThemes = array_keys(array_filter($form_state->getValue('selected_themes')));
		// 	$themes = json_encode($selectedThemes);
		// }
		$source = "";

		$tags = $resourceManager->defineTags($tags);
		$security = $resourceManager->defineSecurity($userId, $users);

		try {
			$generatedTaskId = uniqid();
			if (isset($selectedDatasetId)) {
				$datasetToUpdate = $api->findDataset($selectedDatasetId);

				$datasetName = $datasetToUpdate[name];

				//Update extras
				$extras = $datasetToUpdate[extras];
				$extras = $resourceManager->defineExtras($extras, null, null, null, null, $themes, "", null, null, null, null, null, $dateDataset, 
					null, null, $security, $contributor, null, null, $mention_legales, null, null, null, $type, $entityId, $dateDeposit, $username);

				$datasetId = $resourceManager->updateDataset($generatedTaskId, $selectedDatasetId, $datasetToUpdate, $datasetName, $title, $description, 
					$licence, $organization, $isPrivate, $tags, $extras, null);
				\Drupal::messenger()->addMessage("La connaissance '" . $datasetName ."' a été mise à jour.");
			}
			else {
				// We build extras
				$extras = $resourceManager->defineExtras(null, null, null, null, null, $themes, "", null, null, null, null, null,  $dateDataset, 
					null, null, $security, $contributor, null, null, $mention_legales, null, null, null, $type, $entityId, $dateDeposit, $username);

				Logger::logMessage("Create dataset " . $datasetName);
				Logger::logMessage(" with extras " . json_encode($extras));
				Logger::logMessage(" and tags " . json_encode($tags));
				Logger::logMessage(" and security " . json_encode($security));
				Logger::logMessage(" and isPrivate " . $isPrivate);
				Logger::logMessage(" and licence " . $licence);
				Logger::logMessage(" and organization " . $organization);
				Logger::logMessage(" and description " . $description);
				Logger::logMessage(" and title " . $title);
				Logger::logMessage(" and datasetName " . $datasetName);
				Logger::logMessage(" and generatedTaskId " . $generatedTaskId);
				Logger::logMessage(" and source " . $source);

				$datasetId = $resourceManager->createDataset($generatedTaskId, $datasetName, $title, $description, $licence, $organization, $isPrivate, $tags, $extras, $source);

				\Drupal::messenger()->addMessage("La connaissance '" . $datasetName ."' a été créé.");
			}

			return $datasetId;
		} catch (\Exception $e) {
			Logger::logMessage($e->getMessage());
			\Drupal::messenger()->addMessage(t($e->getMessage()), 'error');
		}

		return null;
	}
}
