<?php

namespace Drupal\ckan_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;

use Drupal\ckan_admin\Model\D4CMetadata;
use Drupal\ckan_admin\Model\MetadataDefinition;
use Drupal\ckan_admin\Model\Schedule;

use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\Logger;
use Drupal\ckan_admin\Utils\ResourceManager;
use Drupal\ckan_admin\Utils\DatasetHelper;

use Drupal\data_bfc\Utils\VanillaApiManager;

abstract class MetadataForm extends FormBase {

	public function getFormId() {
		return 'MetadataForm';
	}

	public function loadDataset($datasetId, $applySecurity = true) {
		$api = new Api();
		return isset($datasetId) ? $api->getPackageShow2($datasetId, null, true, $applySecurity) : null;
	}

	public function getDatasetIntegration($dataset) {
		$hasDataBfc = \Drupal::moduleHandler()->moduleExists('data_bfc');
		if ($hasDataBfc) {
			$contractId = array_filter($dataset['metas']["extras"], function ($f) {
				return $f["key"] == "vanilla_contract";
			});
			$contractId = array_values($contractId)[0]["value"];

			if (isset($contractId)) {
				$vanillaManager = new VanillaApiManager();
				return $vanillaManager->getIntegrationByContractId($contractId);
			}
		}

		return null;
	}

	public function getDatasetModel($dataset) {
		$datasetModel = array_filter($dataset['metas']["extras"], function ($f) {
			return $f["key"] == "dataset-model";
		});
		return array_values($datasetModel)[0]["value"];
	}

	public function buildMetadataForm(array $form, FormStateInterface $form_state, $selectedDataset = null, $includeSchemas = false, $includeScheduler = false) {
		$config = include(__DIR__ . "/../../config.php");
		$locale = json_decode(file_get_contents(__DIR__ ."/../../locales.fr.json"), true);

		$datasetTitle = \Drupal::request()->query->get('dataset-title');

		$organization = $config->client->client_organisation;

		$hasDataBfc = \Drupal::moduleHandler()->moduleExists('data_bfc');

		// Get drupal username
		$account = \Drupal::currentUser();
		$username = $account->getAccountName();

        $api = new Api;

		if (isset($selectedDataset)) {
			Logger::logMessage("Selected dataset " . $selectedDataset['metas']['id']);

			$integration = $this->getDatasetIntegration($selectedDataset);
			$selectedDataset = $selectedDataset['metas'];

			$tags = $selectedDataset['keyword'] ? $tags = implode(",", $selectedDataset['keyword']) : '';

			$dateDataset = DatasetHelper::extractMetadata($selectedDataset["extras"], "date_dataset");
			$dateDeposit = DatasetHelper::extractMetadata($selectedDataset["extras"], "date_deposit");
			$dateModification = DatasetHelper::extractMetadata($selectedDataset["extras"], "date_modification");
			$encoding = DatasetHelper::extractMetadata($selectedDataset["extras"], "encoding");
			$vignette = DatasetHelper::extractMetadata($selectedDataset["extras"], "img_backgr");
			$dataRgpd = DatasetHelper::extractMetadata($selectedDataset["extras"], "data_rgpd") == '1';
			$dataInterop = DatasetHelper::extractMetadata($selectedDataset["extras"], "data_interop") == '1';
			$dataValidation = DatasetHelper::extractMetadata($selectedDataset["extras"], "data_validation");
			$selectedThemes = DatasetHelper::extractMetadata($selectedDataset["extras"], "themes");
			$selectedThemes = json_decode($selectedThemes, true);

			// Inspire
			$frequence = DatasetHelper::extractMetadata($selectedDataset["extras"], "frequency-of-update");
			$extentName = DatasetHelper::extractMetadata($selectedDataset["extras"], "extent-name");
			$extentBegin = DatasetHelper::extractMetadata($selectedDataset["extras"], "extent-begin");
			$extentEnd = DatasetHelper::extractMetadata($selectedDataset["extras"], "extent-end");

			$responsibleOrganization = DatasetHelper::extractMetadata($selectedDataset["extras"], "responsible-organisation-1");
			if (isset($responsibleOrganization)) {
				$responsibleOrganization = json_decode($responsibleOrganization, true);
				$organisationRole = $responsibleOrganization['organisation-role'];
				$organisationName = $responsibleOrganization['organisation-name'];
		
				$contactEmail = $responsibleOrganization['contact-info']['email'];
				$individualName = $responsibleOrganization['contact-info']['individual-name'];
				$function = $responsibleOrganization['contact-info']['position-name'];
				$phone = $responsibleOrganization['contact-info']['phone'];
				$address = $responsibleOrganization['contact-info']['address'];
				$postalCode = $responsibleOrganization['contact-info']['postal-code'];
				$city = $responsibleOrganization['contact-info']['city'];
				$country = $responsibleOrganization['contact-info']['country'];
			}

			$lineage = DatasetHelper::extractMetadata($selectedDataset["extras"], "lineage");

			$accessConstraints = DatasetHelper::extractMetadata($selectedDataset["extras"], "access_constraints");
			$mentionLegales = DatasetHelper::extractMetadata($selectedDataset["extras"], "mention_legales");
			$useConstraints = DatasetHelper::extractMetadata($selectedDataset["extras"], "use-constraints-1");
			$useConstraints = json_decode($useConstraints);

			$extent = DatasetHelper::extractMetadata($selectedDataset["extras"], "extent");
			$inspireTheme = DatasetHelper::extractMetadata($selectedDataset["extras"], "inspire-theme");
			$useLimitation = DatasetHelper::extractMetadata($selectedDataset["extras"], "use-limitation");
			
			$equivalentScale = DatasetHelper::extractMetadata($selectedDataset["extras"], "equivalent-scale");
			if ($equivalentScale != null) {
				$equivalentScale = $this->cleanSimpleJson($equivalentScale);
			}
			$referenceSystem = DatasetHelper::extractMetadata($selectedDataset["extras"], "reference-system");
			$spatialResolution = DatasetHelper::extractMetadata($selectedDataset["extras"], "spatial-resolution-units");
			$representationType = DatasetHelper::extractMetadata($selectedDataset["extras"], "spatial-representation-type");

			$bboxEastLong = DatasetHelper::extractMetadata($selectedDataset["extras"], "bbox-east-long");
			$bboxNorthLat = DatasetHelper::extractMetadata($selectedDataset["extras"], "bbox-north-lat");
			$bboxSouthLat = DatasetHelper::extractMetadata($selectedDataset["extras"], "bbox-south-lat");
			$bboxWestLong = DatasetHelper::extractMetadata($selectedDataset["extras"], "bbox-west-long");

			$hasGeographicData = isset($inspireTheme) || isset($representationType) || isset($referenceSystem) || isset($equivalentScale) || isset($spatialResolution);
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
		
		$themes = $api->getThemes(true, true);
		$themesOptions = array();
		foreach($themes as &$value){
			$themesOptions[$value["title"]] = $value["label"];
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
			'#default_value' => $selectedDataset != null ? $selectedDataset['title'] : ($datasetTitle != null ? $datasetTitle : ''),
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

		// Add date modification only if $selectedDataset is not null
		if ($selectedDataset != null) {
			$form['integration_option']['dataset_date_modification'] = [
				'#type' => 'date',
				'#title' => $this->t('Date de modification'),
				'#default_value' => date('Y-m-d'),
			];
		}

		// Add date field and set default value to today
		$form['integration_option']['dataset_deposit_date'] = [
			'#type' => 'date',
			'#title' => $this->t('Date de versement'),
			'#default_value' => isset($dateDeposit) ? $dateDeposit : date('Y-m-d'),
			'#access' => false,
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
			'#default_value' => isset($encoding) ? $encoding : 'UTF-8',
			'#disabled' => true,
		];

		$form['integration_option']['dataset_vignette'] = array(
            '#type' => 'managed_file',
            '#title' => t("Vignette de la connaissance"),
            '#upload_location' => 'public://datasets',
            '#upload_validators' => array(
                'file_validate_extensions' => array('jpeg png jpg svg gif WebP PNG JPG JPEG SVG GIF'),
            ),
            '#size' => 22,
        );
		
		if (isset($vignette) && $vignette != '') {
			$form['integration_option']['dataset_vignette_deletion'] = array(
				'#type' => 'checkbox',
				'#title' => $this->t('Supprimer la vignette'),
			);
		}

		// Add checkbox rgpd
		$form['integration_option']['dataset_rgpd'] = [
			'#type' => 'checkbox',
			'#title' => $this->t('Contient des données RGPD'),
			'#default_value' => $selectedDataset != null ? $dataRgpd : 0,
		];

		// Add checkbox interop
		$form['integration_option']['dataset_interop'] = [
			'#type' => 'checkbox',
			'#title' => $this->t('Contient des données intéropérables'),
			'#default_value' => $selectedDataset != null ? $dataInterop : 0,
		];

		// Check if we need to include schemas
		if ($includeSchemas && $hasDataBfc) {
			$vanillaManager = new VanillaApiManager();
			try {
				$schemas = $vanillaManager->getValidationSchemas();
			} catch (\Exception $e) {
				$schemas = array();
			}

			$selectedSchemas = $integration['validationSchemas'];

			$schemasOptions = array();
			foreach ($schemas as $schema) {
				$schemasOptions[$schema] = $schema;
			}

			$form['integration_option']['schemas'] = [
			  '#type' => 'checkboxes',
			  '#title' => $this->t('Schémas de validation'),
			  '#options' => $schemasOptions,
			  '#default_value' => $selectedSchemas,
			];

			if (isset($dataValidation) && $dataValidation != null) {
				$form['integration_option']['dataset_data_validation'] = [
					'#type' => 'textarea',
					'#title' => $this->t('Données de contrôle'),
					'#default_value' => $dataValidation,
				];
			}
		}

		$form['integration_option']['dataset_themes'] = [
			'#type' => 'checkboxes',
			'#title' => $this->t('Thèmes'),
			'#options' => $themesOptions,
			'#default_value' => isset($selectedThemes) ? $selectedThemes : array(),
		];

		// Check if we need to include scheduler
		if ($includeScheduler && $hasDataBfc) {

			$schedule = $integration['schedule'];

			$form['scheduler'] = [
				'#type' => 'details',
				'#title' => $this->t('Planification'),
				'#open' => false,
				'#tree' => TRUE,
			];

			// Add checkbox to activate planifiction or not
			$form['scheduler']['scheduler_active'] = [
				'#type' => 'checkbox',
				'#title' => $this->t('Activer la planification'),
				'#default_value' => isset($schedule) ? $schedule['on'] : true,
			];

			// Add date and time field
			$form['scheduler']['scheduler_date'] = [
				'#type' => 'datetime',
				'#title' => $this->t('Date de lancement'),
				'#default_value' => isset($schedule) ? new DrupalDateTime(date('Y-m-d H:i:s', strtotime($schedule['beginDate']))) : DrupalDateTime::createFromTimestamp(time()),
				'#date_format' => 'd/m/Y H:i:s',
				'#date_timezone' => 'Europe/Paris',
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
				'#default_value' => isset($schedule) ? $schedule['period'] : 'DAY',
			];
			

			// Add numeric field for interval
			$form['scheduler']['scheduler_interval'] = [
				'#type' => 'number',
				'#title' => $this->t('Intervalle'),
				'#default_value' => isset($schedule) ? $schedule['interval'] : 1,
			];

			// Add date field and set default value to today
			$form['scheduler']['scheduler_date_end'] = [
				'#type' => 'date',
				'#title' => $this->t('Date de fin de planification'),
				'#date_format' => 'Y-m-d',
				'#default_value' => isset($schedule) && isset($schedule['stopDate']) ? date('Y-m-d', strtotime($schedule['stopDate'])) : null,
			];
		}

		// Add section for inspire metadata
		$form['inspire_option'] = [
			'#type' => 'details',
			'#title' => $this->t('METADONNEES INSPIRE'),
			'#open' => false,
			'#tree' => TRUE,
		];

		$form['inspire_option']['dataMaintenanceFrequency'] = [
			'#type' => 'select',
			'#title' => $this->t('Fréquence de mise à jour'),
			'#options' => $this->getListValues($locale, "MD_MaintenanceFrequencyCode", true),
			'#default_value' => isset($frequence) ? $frequence : '',
		];
		
		// Emprise temporelle du jeu de données with description field and two date fields
		$form['inspire_option']['temporal_extent_description'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Emprise temporelle du jeu de données'),
			'#default_value' => isset($extentName) ? $extentName : '',
		];

		$form['inspire_option']['temporal_extent_start'] = [
			'#type' => 'date',
			'#title' => $this->t('Début'),
			'#date_format' => 'Y-m-d',
			'#default_value' => isset($extentBegin) ? date('Y-m-d', strtotime($extentBegin)) : null,
		];

		$form['inspire_option']['temporal_extent_end'] = [
			'#type' => 'date',
			'#title' => $this->t('Fin'),
			'#date_format' => 'Y-m-d',
			'#default_value' => isset($extentEnd) ? date('Y-m-d', strtotime($extentEnd)) : null,
		];

		// Add form title
		$form['inspire_option']['contact'] = [
			'#type' => 'details',
			'#title' => $this->t('Point de contact'),
			'#open' => false,
			'#tree' => FALSE,
		];

		// Add role field
		$form['inspire_option']['contact']['role'] = [
			'#type' => 'select',
			'#name' => 'role',
			'#title' => $this->t('Rôle'),
			'#options' => $this->getListValues($locale, "CI_RoleCode", true),
			'#default_value' => isset($organisationRole) ? $organisationRole : '',
		];

		// Add organisation name field
		$form['inspire_option']['contact']['organisationName'] = [
			'#type' => 'textfield',
			'#name' => 'organisationName',
			'#title' => $this->t('Organisme'),
			'#default_value' => isset($organisationName) ? $organisationName : '',
		];

		// Add email field
		$form['inspire_option']['contact']['email'] = [
			'#type' => 'textfield',
			'#name' => 'email',
			'#title' => $this->t('Email'),
			'#default_value' => isset($contactEmail) ? $contactEmail : '',
		];

		// Add individual name field
		$form['inspire_option']['contact']['individualName'] = [
			'#type' => 'textfield',
			'#name' => 'individualName',
			'#title' => $this->t('Nom et prénom'),
			'#default_value' => isset($individualName) ? $individualName : '',
		];

		// Add position name field
		$form['inspire_option']['contact']['positionName'] = [
			'#type' => 'textfield',
			'#name' => 'positionName',
			'#title' => $this->t('Fonction'),
			'#default_value' => isset($function) ? $function : '',
		];

		// Add phone voices field
		$form['inspire_option']['contact']['phoneVoices'] = [
			'#type' => 'textfield',
			'#name' => 'phoneVoices',
			'#title' => $this->t('Téléphone'),
			'#default_value' => isset($phone) ? $phone : '',
		];

		// Add address field
		$form['inspire_option']['contact']['address'] = [
			'#type' => 'textfield',
			'#name' => 'address',
			'#title' => $this->t('Adresse'),
			'#default_value' => isset($address) ? $address : '',
		];

		// Add postal code field
		$form['inspire_option']['contact']['postalCode'] = [
			'#type' => 'textfield',
			'#name' => 'postalCode',
			'#title' => $this->t('Code postal'),
			'#default_value' => isset($postalCode) ? $postalCode : '',
		];

		// Add city field
		$form['inspire_option']['contact']['city'] = [
			'#type' => 'textfield',
			'#name' => 'city',
			'#title' => $this->t('Ville'),
			'#default_value' => isset($city) ? $city : '',
		];

		// Add country text field
		$form['inspire_option']['contact']['country'] = [
			'#type' => 'textfield',
			'#name' => 'country',
			'#title' => $this->t('Pays'),
			'#default_value' => isset($country) ? $country : '',
		];

		$form['inspire_option']['technicaldescription'] = array(
			'#type' => 'details',
			'#title' => $this->t('Description techniques'),
			'#open' => false,
			'#tree' => FALSE,
		);

		//Emprise du jeu de données - Nom : extentName (epci)
		$form['inspire_option']['technicaldescription']['extentName'] = array(
			'#type' => 'textfield',
			'#name' => 'extentName',
			'#title' => t('Nom'),
			'#description' => t('Nom de l\'emprise du jeu de données (epci)'),
			'#default_value' => isset($extent) ? $extent : '',
		);

		//Qualité des données : statement (CharacterString long)
		$form['inspire_option']['technicaldescription']['statement'] = array(
			'#type' => 'textarea',
			'#name' => 'statement',
			'#title' => t('Qualité des données'),
			'#default_value' => isset($lineage) ? $lineage : '',
		);

		//Limites techniques d'usage : useLimitation (CharacterString)
		$form['inspire_option']['technicaldescription']['use_limitation'] = array(
			'#type' => 'textarea',
			'#name' => 'use_limitation',
			'#title' => t('Limites techniques d\'usage'),
			'#default_value' => isset($useLimitation) ? $useLimitation : '',
		);

		// Checkbox for geographic data
		$form['inspire_option']['technicaldescription']['geographic_data'] = array(
			'#type' => 'checkbox',
			'#name' => 'geographic_data',
			'#title' => t('Données géographiques'),
			'#default_value' => $hasGeographicData,
		);

		//Theme inspire
		$form['inspire_option']['technicaldescription']['theme_inspire'] = array(
			'#type' => 'select',
			'#name' => 'theme_inspire',
			'#title' => t('Thème inspire'),
			'#options' => $this->getListValues($locale, "MD_InspireTopicCategoryCode", true),
			'#default_value' => isset($inspireTheme) ? $inspireTheme : '',
			// Dependent on geographic data
			'#states' => array(
				'invisible' => array(
					':input[name="geographic_data"]' => array('checked' => FALSE),
				),
			),
		);

		//Type de données
		$form['inspire_option']['technicaldescription']['data_type'] = array(
			'#type' => 'select',
			'#name' => 'data_type',
			'#title' => t('Type de données'),
			'#options' => $this->getListValues($locale, "MD_SpatialRepresentationTypeCode", true),
			'#default_value' => isset($representationType) ? $representationType  : '',
			// Dependent on geographic data
			'#states' => array(
				'visible' => array(
					':input[name="geographic_data"]' => array('checked' => TRUE),
				),
			),
		);

		// Système de projection
		$form['inspire_option']['technicaldescription']['projection'] = array(
			'#type' => 'select',
			'#name' => 'projection',
			'#title' => t('Système de projection'),
			'#options' => $this->getListValues($locale, "MD_ReferenceSystemCode", true),
			'#default_value' => isset($referenceSystem) ? $referenceSystem : '',
			// Dependent on geographic data
			'#states' => array(
				'visible' => array(
					':input[name="geographic_data"]' => array('checked' => TRUE),
				),
			),
		);

		// Résolution spatiale
		$form['inspire_option']['technicaldescription']['spatial_resolution'] = array(
			'#type' => 'textfield',
			'#name' => 'spatial_resolution',
			'#title' => t('Résolution spatiale : Echelle optimale d\'utilisation'),
			'#default_value' => isset($equivalentScale) ? $equivalentScale : '',
			// Dependent on geographic data
			'#states' => array(
				'visible' => array(
					':input[name="geographic_data"]' => array('checked' => TRUE),
				),
			),
		);

		// Résolution spatiale
		$form['inspire_option']['technicaldescription']['spatial_resolution_distance'] = array(
			'#type' => 'textfield',
			'#name' => 'spatial_resolution_distance',
			'#title' => t('Résolution (mètre/pixel)'),
			'#default_value' => isset($spatialResolution) ? $spatialResolution : '',
			// Dependent on geographic data
			'#states' => array(
				'visible' => array(
					':input[name="geographic_data"]' => array('checked' => TRUE),
				),
			),
		);

		// Add title
		$form['inspire_option']['technicaldescription']['geographic_extent'] = array(
			'#type' => 'item',
			'#title' => t('Etendue géographique'),
			// Dependent on geographic data
			'#states' => array(
				'visible' => array(
					':input[name="geographic_data"]' => array('checked' => TRUE),
				),
			),
		);
		
		$form['inspire_option']['technicaldescription']['geographic_extent_west'] = array(
			'#type' => 'textfield',
			'#name' => 'geographic_extent_west',
			'#title' => t('Ouest'),
			'#default_value' => isset($bboxWestLong) ? $bboxWestLong : '',
			// Dependent on geographic data
			'#states' => array(
				'visible' => array(
					':input[name="geographic_data"]' => array('checked' => TRUE),
				),
			),
		);

		$form['inspire_option']['technicaldescription']['geographic_extent_east'] = array(
			'#type' => 'textfield',
			'#name' => 'geographic_extent_east',
			'#title' => t('Est'),
			'#default_value' => isset($bboxEastLong) ? $bboxEastLong : '',
			// Dependent on geographic data
			'#states' => array(
				'visible' => array(
					':input[name="geographic_data"]' => array('checked' => TRUE),
				),
			),
		);

		$form['inspire_option']['technicaldescription']['geographic_extent_south'] = array(
			'#type' => 'textfield',
			'#name' => 'geographic_extent_south',
			'#title' => t('Sud'),
			'#default_value' => isset($bboxSouthLat) ? $bboxSouthLat : '',
			// Dependent on geographic data
			'#states' => array(
				'visible' => array(
					':input[name="geographic_data"]' => array('checked' => TRUE),
				),
			),
		);

		$form['inspire_option']['technicaldescription']['geographic_extent_north'] = array(
			'#type' => 'textfield',
			'#name' => 'geographic_extent_north',
			'#title' => t('Nord'),
			'#default_value' => isset($bboxNorthLat) ? $bboxNorthLat : '',
			// Dependent on geographic data
			'#states' => array(
				'visible' => array(
					':input[name="geographic_data"]' => array('checked' => TRUE),
				),
			),
		);

		// Section Licence et droits d'usage :
		$form['inspire_option']['licence'] = array(
			'#type' => 'details',
			'#title' => $this->t('Licence et droits d\'usage'),
			'#open' => false,
			'#tree' => FALSE,
		);

		$form['inspire_option']['licence']['data_legal_access_constraints'] = array(
			'#type' => 'select',
			'#name' => 'data_legal_access_constraints',
			'#title' => t('Contraintes légales d\'accès'),
			'#options' => $this->getListValues($locale, "MD_RestrictionCode", true),
			'#default_value' => isset($accessConstraints) ? $accessConstraints : '',
		);

		$form['inspire_option']['licence']['data_legal_use_constraints'] = array(
			'#type' => 'select',
			'#name' => 'data_legal_use_constraints',
			'#title' => t('Contraintes légales d\'usage'),
			'#options' => $this->getListValues($locale, "MD_RestrictionCode", true),
			'#default_value' => isset($useConstraints) ? $useConstraints : '',
		);

		$form['inspire_option']['licence']['data_legal_use_limitations'] = array(
			'#type' => 'textfield',
			'#name' => 'data_legal_use_limitations',
			'#title' => t('Mentions et conditions légales d\'utilisation'),
			'#default_value' => isset($mentionLegales) ? $mentionLegales : '',
		);

		return $form;
	}

	function cleanSimpleJson($value, $decodeHtml = false) {
		$value = str_replace('{', '', $value);
		$value = str_replace('}', '', $value);
		$value = str_replace('"', '', $value);
		return $value != '' ? $value : null;
	}

	private function getListValues($locale, $listId, $addEmptyValue) {
		$frequencyCodes = $locale["codelists"][$listId];

		$codes = [];
		if ($addEmptyValue) {
			$codes[""] = "";
		}
		foreach ($frequencyCodes as $code) {
			$codes[$code["id"]] = $code["value"];
		}
		return $codes;
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

	public function getDatasetVignette(FormStateInterface $form_state, ResourceManager $resourceManager) {
		$datasetVignette = $form_state->getValue(['integration_option','dataset_vignette'], 0);
		return $resourceManager->defineBackground($datasetVignette);
	}

	public function getDatasetVignetteDeletion(FormStateInterface $form_state) {
		return $form_state->getValue(['integration_option','dataset_vignette_deletion']);
	}

	public function getDatasetRgpd(FormStateInterface $form_state) {
		return $form_state->getValue(['integration_option','dataset_rgpd']);
	}

	public function getDataValidation(FormStateInterface $form_state) {
		return $form_state->getValue(['integration_option','dataset_data_validation']);
	}

	public function getThemes(FormStateInterface $form_state) {
		$themesOptions = $form_state->getValue(['integration_option','dataset_themes']);
		
		$themes = array();
		foreach ($themesOptions as $key => $value) {
			if (strcmp($key, $value) == 0) {
				$themes[] = $key;
			}
		}
		return json_encode($themes);
	}

	public function getGeneralMetadata(FormStateInterface $form_state) {
		//WIP everything should be there

		$dataInterop = $form_state->getValue(['integration_option','dataset_interop']);
		$modificationDate = $form_state->getValue(['integration_option','dataset_date_modification']);

		$generalMetadata = array();
		$generalMetadata[] = new D4CMetadata("data_interop", $dataInterop);
		if ($modificationDate != null) {
			$generalMetadata[] = new D4CMetadata("date_modification", $modificationDate);
		}
		return $generalMetadata;
	}

	public function getInspireMetadata(FormStateInterface $form_state) {
		$inspireMetadata = array();

		$frequence = $form_state->getValue(['inspire_option','dataMaintenanceFrequency']);

		$extentName = $form_state->getValue(['inspire_option','temporal_extent_description']);
		$extentBegin = $form_state->getValue(['inspire_option','temporal_extent_start']);
		$extentEnd = $form_state->getValue(['inspire_option','temporal_extent_end']);

		$organisationRole = $form_state->getValue(['role']);
		$organisationName = $form_state->getValue(['organisationName']);
		$contactEmail = $form_state->getValue(['email']);
		$individualName = $form_state->getValue(['individualName']);
		$function = $form_state->getValue(['positionName']);
		$phone = $form_state->getValue(['phoneVoices']);
		$address = $form_state->getValue(['address']);
		$postalCode = $form_state->getValue(['postalCode']);
		$city = $form_state->getValue(['city']);
		$country = $form_state->getValue(['country']);

		$extent = $form_state->getValue(['extentName']);
		$lineage = $form_state->getValue(['statement']);
		$useLimitation = $form_state->getValue(['use_limitation']);
		
		$inspireTheme = $form_state->getValue(['theme_inspire']);
		$representationType = $form_state->getValue(['data_type']);
		$referenceSystem = $form_state->getValue(['projection']);
		$equivalentScale = $form_state->getValue(['spatial_resolution']);
		$spatialResolution = $form_state->getValue(['spatial_resolution_distance']);
		$bboxEastLong = $form_state->getValue(['geographic_extent_east']);
		$bboxNorthLat = $form_state->getValue(['geographic_extent_north']);
		$bboxSouthLat = $form_state->getValue(['geographic_extent_south']);
		$bboxWestLong = $form_state->getValue(['geographic_extent_west']);

		$accessConstraints = $form_state->getValue(['data_legal_access_constraints']);
		$useConstraints = $form_state->getValue(['data_legal_use_constraints']);
		$mentionLegales = $form_state->getValue(['data_legal_use_limitations']);

		if (isset($frequence) && $frequence != "") {
			$inspireMetadata[] = new D4CMetadata("frequency-of-update", $frequence);
		}

		if (isset($extentName) && $extentName != "") {
			$inspireMetadata[] = new D4CMetadata("extent-name", $extentName);
		}
		if (isset($extentBegin) && $extentBegin != "") {
			$inspireMetadata[] = new D4CMetadata("extent-begin", $extentBegin);
		}
		if (isset($extentEnd) && $extentEnd != "") {
			$inspireMetadata[] = new D4CMetadata("extent-end", $extentEnd);
		}
		if (isset($organisationRole) || isset($organisationName) || isset($contactEmail) || isset($individualName) || isset($function) 
				|| isset($phone) || isset($address) || isset($postalCode) || isset($city) || isset($country)) {
			
			$inspireMetadata[] = new D4CMetadata("responsible-organisation-1", json_encode(array(
				"organisation-role" => isset($organisationRole) ? $organisationRole : "",
				"organisation-name" => isset($organisationName) ? $organisationName : "",
				"contact-info" => array(
					"email" => isset($contactEmail) ? $contactEmail : "",
					"individual-name" => isset($individualName) ? $individualName : "",
					"position-name" => isset($function) ? $function : "",
					"phone" => isset($phone) ? $phone : "",
					"address" => isset($address) ? $address : "",
					"postal-code" => isset($postalCode) ? $postalCode : "",
					"city" => isset($city) ? $city : "",
					"country" => isset($country) ? $country : ""
				)
			)));
		}

		if (isset($extent) && $extent != "") {
			$inspireMetadata[] = new D4CMetadata("extent", $extent);
		}
		if (isset($lineage) && $lineage != "") {
			$inspireMetadata[] = new D4CMetadata("lineage", $lineage);
		}
		if (isset($useLimitation) && $useLimitation != "") {
			$inspireMetadata[] = new D4CMetadata("use-limitation", $useLimitation);
		}

		if (isset($inspireTheme) && $inspireTheme != "") {
			$inspireMetadata[] = new D4CMetadata("inspire-theme", $inspireTheme);
		}
		if (isset($representationType) && $representationType != "") {
			$inspireMetadata[] = new D4CMetadata("spatial-representation-type", $representationType);
		}
		if (isset($referenceSystem) && $referenceSystem != "") {
			$inspireMetadata[] = new D4CMetadata("spatial-reference-system", $referenceSystem);
		}
		if (isset($equivalentScale) && $equivalentScale != "") {
			$inspireMetadata[] = new D4CMetadata("equivalent-scale", json_encode($equivalentScale));
		}
		if (isset($spatialResolution) && $spatialResolution != "") {
			$inspireMetadata[] = new D4CMetadata("spatial-resolution-units", $spatialResolution);
		}
		if (isset($bboxEastLong) && $bboxEastLong != "") {
			$inspireMetadata[] = new D4CMetadata("bbox-east-long", $bboxEastLong);
		}
		if (isset($bboxNorthLat) && $bboxNorthLat != "") {
			$inspireMetadata[] = new D4CMetadata("bbox-north-lat", $bboxNorthLat);
		}
		if (isset($bboxSouthLat) && $bboxSouthLat != "") {
			$inspireMetadata[] = new D4CMetadata("bbox-south-lat", $bboxSouthLat);
		}
		if (isset($bboxWestLong) && $bboxWestLong != "") {
			$inspireMetadata[] = new D4CMetadata("bbox-west-long", $bboxWestLong);
		}

		if (isset($accessConstraints) && $accessConstraints != "") {
			$inspireMetadata[] = new D4CMetadata("access_constraints", $accessConstraints);
		}
		if (isset($useConstraints) && $useConstraints != "") {
			$inspireMetadata[] = new D4CMetadata("use-constraints-1", json_encode($useConstraints));
		}
		if (isset($mentionLegales) && $mentionLegales != "") {
			$inspireMetadata[] = new D4CMetadata("mention_legales", $mentionLegales);
		}

		return $inspireMetadata;
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
		// Check if planification is active
		$schedulerActive = $form_state->getValue(['scheduler', 'scheduler_active']);
		$schedulerDate = $form_state->getValue(['scheduler','scheduler_date']);
		$schedulerPeriod = $form_state->getValue(['scheduler','scheduler_period']);
		$schedulerInterval = $form_state->getValue(['scheduler','scheduler_interval']);
		$schedulerInterval = (int) $schedulerInterval;
		$schedulerDateFin = $form_state->getValue(['scheduler','scheduler_date_end']);

		return new Schedule($schedulerActive == 1, $schedulerPeriod, $schedulerInterval, $schedulerDate, $schedulerDateFin);
	}

	public function getDatasetName($form_state) {
        $title = $this->getDatasetTitle($form_state);

		$resourceManager = new ResourceManager;
		return $resourceManager->defineDatasetName($title);
	}

	public function createOrUpdateDatasetId($form_state, $organization, $selectedDatasetId, $type, $entityId = null, $datasetModel = null) {
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
		$dataRgpd = $this->getDatasetRgpd($form_state);
		$dataValidation = $this->getDataValidation($form_state);
		$themes = $this->getThemes($form_state);

		$generalMetadata = $this->getGeneralMetadata($form_state);
		$inspireMetadata = $this->getInspireMetadata($form_state);

		$mention_legales = "";
		$source = "";

		$datasetVignette = $this->getDatasetVignette($form_state, $resourceManager);
		$datasetVignetteDeletion = $this->getDatasetVignetteDeletion($form_state);

		$tags = $resourceManager->defineTags($tags);
		$security = $resourceManager->defineSecurity($userId, $users);

		try {
			$generatedTaskId = uniqid();
			if (isset($selectedDatasetId)) {
				$datasetToUpdate = $api->findDataset($selectedDatasetId);

				$datasetName = $datasetToUpdate[name];

				//Update extras
				$extras = $datasetToUpdate[extras];
				$extras = $resourceManager->defineExtras($extras, null, $datasetVignette, $datasetVignetteDeletion, null, $themes, "", null, null, null, 
					null, null, $dateDataset, null, null, $security, $contributor, null, null, $mention_legales, null, null, $dataRgpd, $type, $entityId, 
					$dateDeposit, $username, $datasetModel, $dataValidation, $generalMetadata, $inspireMetadata);

				$datasetId = $resourceManager->updateDataset($generatedTaskId, $selectedDatasetId, $datasetToUpdate, $datasetName, $title, $description, 
					$licence, $organization, $isPrivate, $tags, $extras, null);
				\Drupal::messenger()->addMessage("La connaissance '" . $datasetName ."' a été mise à jour.");
			}
			else {
				// We build extras
				$extras = $resourceManager->defineExtras(null, null, $datasetVignette, $datasetVignetteDeletion, null, $themes, "", null, null, null, null, 
					null,  $dateDataset, null, null, $security, $contributor, null, null, $mention_legales, null, null, $dataRgpd, $type, $entityId, 
					$dateDeposit, $username, $datasetModel, $dataValidation, $generalMetadata, $inspireMetadata);

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

			// If error contains "That URL is already in use"
			if (strpos($e->getMessage(), "That URL is already in use") !== false) {
				\Drupal::messenger()->addMessage("Le nom de la connaissance est déjà utilisé. Veuillez en choisir un autre.", 'error');
			}
			else {
				\Drupal::messenger()->addMessage("Une erreur est survenue lors de la création de la connaissance (" . t($e->getMessage()) . ").", 'error');
			}
		}

		return null;
	}

	function manageResourceUrl($datasetId, $resourceId, $resourceUrl, $resourceName, $description, $format) {
		$isUpdate = $resourceId != null;

		$api = new Api;
		$resourceManager = new ResourceManager;
		return $resourceManager->uploadResourceToCKAN($api, $datasetId, $isUpdate, $resourceId, $resourceUrl, $resourceName, "", $description, false, $format);
	}

	function deleteDataset(array &$form, FormStateInterface $form_state) {
		$selectedDatasetId = \Drupal::request()->query->get('dataset-id');

		if ($selectedDatasetId) {
			$resourceManager = new ResourceManager();
			if ($resourceManager->deleteDataset($selectedDatasetId)) {
				\Drupal::messenger()->addMessage(t('Le jeu de données a été supprimé!'), 'warning');

				$form_state->setRedirect('ckan_admin.portail');
			}
		}
	}

	function deleteAllResources($datasetId) {
		$resourceManager = new ResourceManager();
		$resourceManager->deleteDatasetResources($datasetId);
	}

	function redirectToDataset($form_state, $datasetId) {
		$form_state->setRedirect('ckan_admin.visualisation', ['id' => $datasetId]);
	}
}
