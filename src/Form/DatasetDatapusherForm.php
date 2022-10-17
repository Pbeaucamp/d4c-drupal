<?php
/**
 * @file
* Contains \Drupal\search_api_solr_admin\Form\QueryForm.
*/

namespace Drupal\ckan_admin\Form;

use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\HelpFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Render\FormattableMarkup; 
use Drupal\ckan_admin\Utils\Logger;

/**
 * This form is used to manage datapusher for a dataset
 * 
 */
class DatasetDatapusherForm extends HelpFormBase {
	

	/**
	 * {@inheritdoc}
	 */
	public function getFormId() {
		return 'DatasetDatapusherForm';
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
        $form['#attached']['library'][] = 'ckan_admin/datasetDatapusherForm.form';
		
		$form = parent::buildForm($form, $form_state);
        
		$this->config = include(__DIR__ . "/../../config.php");
		$this->urlCkan = $this->config->ckan->url;
		
		$datasetId = \Drupal::request()->query->get('datasetId');

		// Extracts ressources from dataset
		$api = new Api();
        $result = $api->getPackageShow("id=".$datasetId);
        $result = $result[result];

		$datasetName = $result[title];
        $resources = $result[resources];
		
		$header =  array(
			"name" => $this->t('Ressource'),
			"statutDatastore" => $this->t('Statut DataStore'),
			"logDatastore" => $this->t("Journal de chargement"),
			"downloadInDatastore" => $this->t(""),    
		);

		$output = array();
		foreach ($resources as $row) {
			$id = $row["id"];
			
			$title = $row["name"];
			$format = $row["format"];

			Logger::logMessage("Found resource '" . $datapusherResult . "' \r\n");

			//If the format is manageable by the datastore we display the options
			if(($format == 'CSV' || $format == 'XLS' || $format == 'XLSX')){
				$datapusherResult = $api->getDatapusherJobStatus($id);
				$datapusherInfos = json_decode($datapusherResult, true);
				
				$dpStatus = $datapusherInfos["status"];

				$form['#attached']['drupalSettings']['ckan'][$title] = $datapusherResult;

				$uirow = [
					'name' => array('data' => array('#markup' => $title)),
					'statutDatastore' => array('data' => array('#markup' => $dpStatus)),
					'logDatastore' => array('data' => new FormattableMarkup('<input type="button" onclick=":action" class="button" style="border-radius: 10px;font-size: 11px;padding: 4px 5px;" value=":name" data-id=":id" data-log=":infos"/>', 
						[':action' => "openLogPopup(event)", 
						':name' => $this->t('Logs d\'insertion'),
						':infos' => $datapusherResult,
						':id' => $title])
					),
					'downloadInDatastore' => array('data' => new FormattableMarkup('<input type="button" onclick=":action" class="button" style="border-radius: 10px;font-size: 11px;padding: 4px 5px;" value=":name" data-id=":id"/>', 
						[':action' => "pushToDatastore(event)", 
						':name' => $this->t('Relancer l\'intégration dans le Datastore'),
						':id' => $id])
					),
				];
				$output[] = $uirow;
			}
			else {
				$uirow = [
					'name' => array('data' => array('#markup' => $title))
				];
				$output[] = $uirow;
			}
		}

		//$form['#method'] = 'get';
		$form['top'] = [
			'#type'  => 'container',
			'#attributes' => array(
				'style' => "height:37px;display:block",
			)
		];

		$form['T1'] = array(
			'#markup' => '<h1 class="title">' . $datasetName . '</h2>',
		);
		
		$form['calculateVisualisation'] = array(
			'#type' => 'button',
			'#value' => t('Régénérer la visualisation'),
			'#ajax' => [
				'callback' => [$this, 'calculateVisu'],
				'event' => 'click',
				// 'wrapper' => "panel-map",
			],
			'#name' => "calcVisu"
        );

		$form['visuHelp'] = array(
			'#markup' => '<p class="tooltip">'.t('Cette fonction permet de régénerer les icones de visualisation sur la page d\'affichage des jeux de données si un problème est survenu.').'</h2>',
		);

		$form['T2'] = array(
			'#markup' => '<h2 class="title">'.t('Gestion du datastore du jeu de données').'</h2>',
		);

		$form['table'] = array(
			'#type' => 'table',
			'#header' => $header,
			'#rows' => $output,
		);

		$form['modalLog'] = array(
			'#markup' => '<div id="logModal"></div>',
		);
		
		$form['selected_resource'] = array(
            '#type' => 'textfield',
            '#attributes' => array(
				'style'=>'display:none;'
			),  
        );
		
		$form['datapusher'] = array(
			'#type' => 'submit',
			'#value' => $this->t('Envoyer'),
			'#attributes' => array(
				'style'=>'display:none;'
			),
		);

		return $form;
	}
    
	public function submitForm(array &$form, FormStateInterface $form_state){
		$this->config = include(__DIR__ . "/../../config.php");
        $this->urlCkan = $this->config->ckan->url;
		$api = new Api();
		
		$resourceId = $form_state->getValue("selected_resource");

		Logger::logMessage("Reupload dataset '" . $resourceId . "' to datastore \r\n");
		$api->callDatapusher($resourceId);

		\Drupal::messenger()->addMessage('La ressource est en cours de téléchargement dans le DataStore.');
	}

	public function calculateVisu(array &$form, FormStateInterface $form_state) {
		$datasetId = \Drupal::request()->query->get('datasetId');

		$api = new API();
		$api->calculateVisualisations($datasetId);
		
		\Drupal::messenger()->addMessage('Les icones de visualisation ont été régénérées.');
	}
}