<?php

namespace Drupal\ckan_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\MetadataDefinition;
use Drupal\ckan_admin\Utils\Logger;

use Drupal\data_bfc\Utils\VanillaApiManager;

abstract class MetadataForm extends FormBase {

	public function getFormId() {
		return 'MetadataForm';
	}

	public function buildMetadataForm(array $form, FormStateInterface $form_state, $selectedDatasetId = null, $includeSchemas = false) {
		$config = include(__DIR__ . "/../../config.php");
		$organization = $config->client->client_organisation;

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
		}

		$licences = $api->getLicenses();
		
		
        $licenceOptions = array();
		$selectedLicence = '';
        foreach ($licences[result] as &$value) {
            $licenceOptions[$value[id]] = $value[title];

			if ($selectedDataset && $selectedDataset['license'] == $value[title]) {
				$selectedLicence = $value[id];
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
			'#default_value' => $selectedDataset != null ? $selectedDataset['author'] : '',
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
			'#default_value' => date('Y-m-d'),
		];

		// Add date field and set default value to today
		$form['integration_option']['dataset_deposit_date'] = [
			'#type' => 'date',
			'#title' => $this->t('Date de versement'),
			'#default_value' => date('Y-m-d'),
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

		// Check if data_bfc module exist
		if (\Drupal::moduleHandler()->moduleExists('data_bfc')) {
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

	public function getDatasetName(FormStateInterface $form_state) {
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
}
