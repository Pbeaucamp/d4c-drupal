<?php

namespace Drupal\ckan_admin\Form;

use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\DatasetHelper;
use Drupal\Core\Form\FormStateInterface;

use Drupal\ckan_admin\Utils\Logger;
use Drupal\ckan_admin\Utils\ResourceManager;
use Drupal\Core\Url;

class DatasetsUpdateOrganizationForm extends DatasetsForm {
	
	/**
	 * {@inheritdoc}
	 */
	public function getFormId() {
		return 'DatasetsUpdateOrganizationForm';
	}

	public function buildForm(array $form, FormStateInterface $form_state) {
		$config = include(__DIR__ . "/../../config.php");

		$api = new Api;
		$organizations = $api->getAllOrganisations(false, false, false);
		$organizationOptions = array();
		foreach ($organizations as $organization) {
			$organizationOptions[$organization] = $organization;
		}

		$observatoryId = $_GET["observatoryId"];
		$selectedOrganisme = $_GET["observatory"];

		$result = parent::loadDatasets("", $selectedOrganisme);
        $datasets = $result['results'];
		$count = $result["count"];

		$headers = array();
		$headers["dataset_id"] = '';
		$headers["dataset"] = $this->t('CONNAISSANCE');
		$headers["observatory_source"] = $this->t('OBSERVATOIRE SOURCE');
		$headers["observatory_destination"] = $this->t("OBSERVATOIRE DESTINATION");

		$rows = array();
		$form = parent::buildDatasetsForm($form, $form_state, $count, $headers, $rows);

		$indexColumn = 0;
		foreach ($datasets as $dataset) {
			$datasetId = $dataset["id"];

			$datasetTitle = $dataset["title"];
			$datasetOrganisme = $dataset["organization"]["title"];

			// Create link to dataset
			$datasetTitle = '<a href="/visualisation?id=' . $datasetId . '" target="_blank">' . $datasetTitle . '</a>';

			// Add hidden column to store dataset id
			$form['grid']['table'][$indexColumn]['dataset_id'] = array(
				"#type" => "hidden",
				"#value" => $datasetId,
			);
			$form['grid']['table'][$indexColumn]['dataset'] = array(
				'#markup' => $datasetTitle
			);
			$form['grid']['table'][$indexColumn]['observatory_source'] = array(
				'#markup' => $datasetOrganisme
			);
			$form['grid']['table'][$indexColumn]['observatory_destination'] = array(
				"#type" => "select",
				"#options" => $organizationOptions,
				"#default_value" => isset($datasetOrganisme) ? $datasetOrganisme : null,
				"#attributes" => array(
					"style" => "width: 100%;",
				),
			);
			$indexColumn++;
		}

		// Add submit button
		$form["submit"] = array(
			"#type" => "submit",
			"#value" => $this->t("Assigner les connaissances"),
			"#attributes" => array(
				"class" => array("btn btn-primary"),
			),
		);

		return $form;
	}

	function getFilterQuery(FormStateInterface $form_state) {
		return [];
	}

	function getEmptyFilterQuery() {
		return [];
	}

	public function submitForm(array &$form, FormStateInterface $form_state) {
		$observatoryId = $_GET["observatoryId"];

		// Go through all table rows and update datasets
		$datasets = $form_state->getValue(['table']);

		$manager = new ResourceManager;
		foreach($datasets as $dataset) {
			$datasetId = $dataset["dataset_id"];
			$organizationDestination = $dataset["observatory_destination"];

			$manager->changeDatasetOrganization($datasetId, $organizationDestination);
		}

		$form_state->setRedirect('data_bfc.admin_obs_close', ['observatoryId' => $observatoryId]);
		return;
	}
}