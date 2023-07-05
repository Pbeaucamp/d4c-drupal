<?php

namespace Drupal\ckan_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\ckan_admin\Utils\PropertiesHelper;

class ManageD4CPropertiesForm extends FormBase
{

	public function getFormId()
	{
		return 'ManageD4CPropertiesForm';
	}

	public function buildForm(array $form, FormStateInterface $form_state)
	{
		$propertiesHelper = new PropertiesHelper();
		$reservedColumnsGeopoint = $propertiesHelper->getProperty(PropertiesHelper::RESERVED_COLUMNS_GEOPOINT);
		$reservedColumnsGeoshape = $propertiesHelper->getProperty(PropertiesHelper::RESERVED_COLUMNS_GEOSHAPE);

		// Account information.
		$form['d4c'] = [
			'#type'   => 'container',
			'#weight' => -10,
		];

		$form['d4c']['reserved_columns'] = [
			'#type' => 'markup',
			'#markup' => t('<h4>Gestion des colonnes géographiques réservées</h4>')
		];

		$form['d4c']['reserved_columns_geopoint'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Colonne géographique réservée pour les points'),
			'#description' => $this->t('Liste des noms de colonnes permettant de définir une colonne en tant que geo_point_2d (Séparées par des virgules)'),
			'#default_value' => $reservedColumnsGeopoint
		];

		$form['d4c']['reserved_columns_geoshape'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Colonne géographique réservée pour les polygones'),
			'#description' => $this->t('Liste des noms de colonnes permettant de définir une colonne en tant que geo_shape (Séparées par des virgules)'),
			'#default_value' => $reservedColumnsGeoshape
		];

		// Add a submit button that handles the submission of the form.
		$form['d4c']['submit'] = [
			'#type' => 'submit',
			'#value' => $this->t('Save'),
			// Set focus
			'#attributes' => [
				'autofocus' => 'autofocus'
			]
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

	/**
	 * Form submission handler.
	 *
	 * @param array $form
	 *   An associative array containing the structure of the form.
	 * @param \Drupal\Core\Form\FormStateInterface $form_state
	 *   The current state of the form.
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$reservedColumnsGeopoint = $form_state->getValue('reserved_columns_geopoint');
		$reservedColumnsGeoshape = $form_state->getValue('reserved_columns_geoshape');

		$propertiesHelper = new PropertiesHelper();
		$propertiesHelper->setProperty(PropertiesHelper::RESERVED_COLUMNS_GEOPOINT, $reservedColumnsGeopoint);
		$propertiesHelper->setProperty(PropertiesHelper::RESERVED_COLUMNS_GEOSHAPE, $reservedColumnsGeoshape);

		return $form;
	}
}
