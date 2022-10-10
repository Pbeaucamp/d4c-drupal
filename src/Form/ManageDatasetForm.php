<?php

namespace Drupal\ckan_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\Entity\DateFormat;

use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\ResourceManager;
use Drupal\ckan_admin\Utils\Logger;

class ManageDatasetForm extends FormBase
{

	public function getFormId()
	{
		return 'ManageDatasetForm';
	}

	public function buildForm(array $form, FormStateInterface $form_state)
	{
		$selectedDatasetId = \Drupal::request()->query->get('dataset-id');

        $api = new Api;

		if ($selectedDatasetId) {
			Logger::logMessage("Selected dataset id " . $selectedDatasetId);
			$selectedDataset = $api->getPackageShow2($selectedDatasetId, null, true, true);
			$selectedDataset = $selectedDataset['metas'];

			$tags = $selectedDataset['keyword'] ? $tags = implode(",", $selectedDataset['keyword']) : '';

			$extras = $selectedDataset['extras'];
			$mentionLegales = '';
			foreach ($extras as $value) {
				if ($value['key'] == 'mention_legales') {
					$mentionLegales = $value['value'];
				}
			}
		}

		$licences = $api->getLicenses();
		$themes = $api->getThemes(true, true);
		
		
        $licenceOptions = array();
		$selectedLicence = '';
        foreach ($licences[result] as &$value) {
            $licenceOptions[$value[id]] = $value[title];

			if ($selectedDataset && $selectedDataset['license'] == $value[title]) {
				$selectedLicence = $value[id];
			}
        }
			
		$themeOptions = array();
		// $selectedThemes = '';
		foreach($themes as &$value){
			$themeOptions[$value["title"]] = $value["label"];

			// if ($selectedDataset && $selectedDataset['license'] == $value[title]) {
			// 	$selectedLicence = $value[id];
			// }
		}

		$form['text']['#markup'] = t('<h1>Création d\'une connaissance</h1>');
		
        $form['progress-modal'] = array(
			'#markup' => '<div id="progress" class="progress-modal" display="none">
			</div>',
		);

		$form['dataset_title'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Nom de la connaissance'),
			'#required' => TRUE,
			'#maxlength' => 300,
			'#default_value' => $selectedDataset != null ? $selectedDataset['title'] : '',
		];

		$form['dataset_description'] = [
			'#type' => 'textarea',
			'#title' => $this->t('Description'),
            '#resizable' => true,
			'#default_value' => $selectedDataset != null ? $selectedDataset['description'] : '',
		];


		//TODO: Not working for now
		$date = date('d/m/Y');
		$form['dataset_date'] = [
			'#type' => 'date',
			'#title' => $this->t('Date de la connaissance'),
            '#date_date_format' => 'd/m/Y',
			'#default_value' => $date,
		];

		$form['dataset_mention_legales'] = [
			'#type' => 'textarea',
			'#title' => $this->t('Mentions et droits'),
            '#resizable' => true,
			'#default_value' => $selectedDataset != null ? $mentionLegales : '',
		];

		$form['dataset_tags'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Mots-clés (séparer par des virgules, les seuls symboles autorisés sont -"_")'),
			'#required' => FALSE,
			'#maxlength' => 300,
			'#default_value' => $tags,
		];
		
		$form['dataset_licence'] = array(
            '#type' => 'select',
            '#title' => t('*Licence :'),
            '#options' => $licenceOptions,
            '#empty_option' => t('----'),
			'#required' => TRUE,
			'#default_value' => $selectedDataset != null ? $selectedLicence : '',
        );

		$form['dataset_private'] = array(
            '#type' => 'select',
            '#title' => t('*Visibilité :'),
            '#options' => array('Publique', 'Privée'),
			'#required' => TRUE,
			'#default_value' => $selectedDataset != null && $selectedDataset['private'] == 1 ? 1 : 0,
        );

		//Not working for now
        $form['dataset_themes'] = array(
			'#title' => t('Thèmes disponibles'),
			'#type' => 'checkboxes',
			'#options' => $themeOptions,
		);

		// Group submit handlers in an actions element with a key of "actions" so
		// that it gets styled correctly, and so that other modules may add actions
		// to the form. This is not required, but is convention.
		$form['actions'] = [
			'#type' => 'actions',
		];

		$form['actions']['submit'] = [
			'#type' => 'submit',
			'#value' => $this->t('Sauvegarder'),
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
	public function validateForm(array &$form, FormStateInterface $form_state)
	{
		parent::validateForm($form, $form_state);
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
		$organization = $config->client->client_organisation;

		$userId = "*" . \Drupal::currentUser()->id() . "*";

		$selectedDatasetId = \Drupal::request()->query->get('dataset-id');
		$type = \Drupal::request()->query->get('data4citizen-type');
		$entityId = \Drupal::request()->query->get('entity-id');

		$api = new Api;
		$users = $api->getAdministrators();
		$resourceManager = new ResourceManager;
        
        $title = $form_state->getValue('dataset_title');
        $description = $form_state->getValue('dataset_description');
        $dateDataset = $form_state->getValue('dataset_date');
        $mention_legales = $form_state->getValue('dataset_mention_legales');
        $tags = $form_state->getValue('dataset_tags');
        $licence = $form_state->getValue('dataset_licence');
        $isPrivate = $form_state->getValue('dataset_private') == '1';
		$themes = "";
		if ($form_state->getValue('selected_themes') != NULL) {
			$selectedThemes = array_keys(array_filter($form_state->getValue('selected_themes')));
			$themes = json_encode($selectedThemes);
		}

		$datasetName = $resourceManager->defineDatasetName($title);
		$tags = $resourceManager->defineTags($tags);
		$security = $resourceManager->defineSecurity($userId, $users);

		try {
			$generatedTaskId = uniqid();

			//TODO: Add delete
			// $deleteDataset = $form_state->getValue('del_dataset');
			// if ($deleteDataset) {
			// 	if ($resourceManager->deleteDataset($datasetId)) {
			// 		\Drupal::messenger()->addMessage(t('Le jeu de données a été supprimé!'), 'warning');
			// 		$datasetId = null;
			// 	}
			// }
			if (isset($selectedDatasetId)) {
				$datasetToUpdate = $api->findDataset($selectedDatasetId);

				$datasetName = $datasetToUpdate[name];

				//Update extras
				$extras = $datasetToUpdate[extras];
				$extras = $resourceManager->defineExtras($extras, null, null, null, null, $themes, "", null, null, null, null, null, $dateDataset, 
					null, null, $security, null, null, null, $mention_legales, null, null, null, $type, $entityId);

				$datasetId = $resourceManager->updateDataset($generatedTaskId, $selectedDatasetId, $datasetToUpdate, $datasetName, $title, $description, 
					$licence, $organization, $isPrivate, $tags, $extras, null);
				\Drupal::messenger()->addMessage("La connaissance '" . $datasetName ."' a été mise à jour.");
			}
			else {
				// We build extras
				$extras = $resourceManager->defineExtras(null, null, null, null, null, $themes, "", null, null, null, null, null,  $dateDataset, 
					null, null, $security, null, null, null, $mention_legales, null, null, null, $type, $entityId);

				$datasetId = $resourceManager->createDataset($generatedTaskId, $datasetName, $title, $description, $licence, $organization, $isPrivate, $tags, 
					$extras, $source);

				\Drupal::messenger()->addMessage("La connaissance '" . $datasetName ."' a été créé.");
			}

			if ($type == 'visualization') {
				$api->updateVisualization($entityId, $datasetId);
				
				$form_state->setRedirect('data_bfc.ro_visualizations');
				return;
			}

			$form_state->setRedirect('<front>');
		} catch (\Exception $e) {
			Logger::logMessage($e->getMessage());
			\Drupal::messenger()->addMessage(t($e->getMessage()), 'error');
		}
	}
}
