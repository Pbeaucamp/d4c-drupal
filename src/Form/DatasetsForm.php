<?php

namespace Drupal\ckan_admin\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;

use Drupal\ckan_admin\Utils\Api;

use Drupal\Core\Url;

/**
 * Implements an example form.
 */
abstract class DatasetsForm extends FormBase {
	
	// Const for the number of datasets per page
	const NUM_PER_PAGE = 20;

	public function getFormId() {
		return 'DatasetsForm';
	}

	public function buildFilters($isObservatory, $query, $selectedOrganisme) {
		$form['filters'] = [
			'#type'  => 'details',
			'#title' => t('Filtres'),
			'#open'  => true,
		];

		$form['filters']['query'] = [
			'#title' => t('Recherche'),
			'#type' => 'search',
			'#attributes' => array(
				'style' => "display: inline-block; width: 50%;",
			),
			'#default_value' => $query
		];

		if (!$isObservatory) {
			// TODO: Put a select with all orgs

			$form['filters']['organisme'] = array(
				'#title' => t('Observatoire'),
				'#type' => 'search',
				'#attributes' => array(
					'style' => "display: inline-block; width: 50%;",
				),
				'#default_value' => $selectedOrganisme
			);
		}

		$form['filters']['actions'] = [
			'#type' => 'actions'
		];

		$form['filters']['actions']['filter'] = [
			'#type'  => 'submit',
			'#value' => $this->t('Filter'),
			'#submit' => array([$this, 'submitfiltering'])
		];
        
        $form['filters']['actions']['clear'] = [
			'#type'  => 'submit',
			'#value' => $this->t('Effacer'),
			'#submit' => array([$this, 'submitclear'])
		];

		return $form;
	}

	public function loadDatasets($filterQuery, $organisme) {
		$currentUser = \Drupal::currentUser();
		$currentUserId = $currentUser->id();

		$page = pager_find_page();
		$offset = self::NUM_PER_PAGE * $page;
		$query = 'include_private=true&rows=' . self::NUM_PER_PAGE . '&start=' . $offset . $filterQuery;
		
		$api = new Api;
        $result = $api->callPackageSearch_public_private($query, $currentUserId, $organisme, true);
        $result = $result->getContent();
        return json_decode($result, true)[result];
	}

	public function buildDatasetsForm(array $form, FormStateInterface $form_state, $count, $headers, $rows) {			   
		pager_default_initialize($count, self::NUM_PER_PAGE);
		
		$form['grid']['table'] = array(
			'#type' => 'table',
			'#header' => $headers,
			'#rows' => $rows,
			'#weight' => 10,
			'#empty' => $this->t('Aucune connaissance disponible'),
		);

		$form['pager'] = [
			'#type' => 'pager',
			'#tags' => array(t('« Première page'), t('‹ Page précédente'),"", t('Page suivante ›'), t('Dernière page »')),
			'#submit' => array([$this, 'saveSelection'])
		];

		return $form;
	}

	abstract function getEmptyFilterQuery();
	abstract function getFilterQuery(FormStateInterface $form_state);
	
	public function submitclear(array &$form, FormStateInterface $form_state){
		$query = $this->getEmptyFilterQuery();
		$route_name = \Drupal::routeMatch()->getRouteName();
		$url = Url::fromRoute($route_name, [], ['query' => $query]);
		$form_state->setRedirectUrl($url);
	}

	public function submitfiltering(array &$form, FormStateInterface $form_state) {
		$query = $this->getFilterQuery($form_state);
		$route_name = \Drupal::routeMatch()->getRouteName();
		$url = Url::fromRoute($route_name, [], ['query' => $query]);
		$form_state->setRedirectUrl($url);
	}
}