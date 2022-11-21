<?php

namespace Drupal\ckan_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\ckan_admin\Utils\MetadataDefinition;
use Drupal\ckan_admin\Utils\Logger;

abstract class MetadataForm extends FormBase {

	public function getFormId() {
		return 'MetadataForm';
	}

	public function buildForm(array $form, FormStateInterface $form_state) {

		$form['integration'] = [
			'#type' => 'details',
			'#title' => $this->t('Métadonnées'),
			'#open' => TRUE,
			'#tree' => TRUE,
		];

		// Add helper text for users
		$form['integration']['help'] = [
			'#type' => 'markup',
			'#markup' => $this->t('Les métadonnées sont des informations sur les connaissances. Elles sont utilisées pour décrire les connaissances et les rendre plus facilement accessibles. Elles sont également utilisées pour décrire les connaissances lors de leur publication sur l\'observatoire.'),
			// Add style to make the text bigger and a padding
			'#attributes' => [
				'style' => 'font-size: 1.2em; padding: 1em;',
			],
		];

		$form['integration']['dataset_name'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Nom de la connaissance'),
			'#required' => TRUE
		];

		$form['integration']['dataset_description'] = [
			'#type' => 'textarea',
			'#title' => $this->t('Description')
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
		return $form_state->getValue(['integration','dataset_name']);
	}

	public function getDescription(FormStateInterface $form_state) {
		return $form_state->getValue(['integration','dataset_description']);
	}

	public function getMetadata(FormStateInterface $form_state) {
		$description = $this->getDescription($form_state);
		
		$metadata = array();
		$metadata[] = new MetadataDefinition('description', $description);
		// For now we set the dataset to private. TODO: make it configurable
		$metadata[] = new MetadataDefinition('dataset-private', 'true');
		return $metadata;
	}
}
