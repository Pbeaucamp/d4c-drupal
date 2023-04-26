<?php

namespace Drupal\ckan_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ckan_admin\Utils\Api;

class VisualizationsController extends ControllerBase {

  public function page() {
		\Drupal::service('page_cache_kill_switch')->trigger();

		$selectedName = $_GET["q"];
		$selectedType = $_GET["type"];

    $optionTable = array();
    $optionTable['name'] = 'table';
    $optionTable['value'] = 'Table';

    $optionAnalyze = array();
    $optionAnalyze['name'] = 'analyze';
    $optionAnalyze['value'] = 'Analyse';

    $optionAnalyzeGlobal = array();
    $optionAnalyzeGlobal['name'] = 'chartbuilder';
    $optionAnalyzeGlobal['value'] = 'Analyse globale';

    $optionMap = array();
    $optionMap['name'] = 'map';
    $optionMap['value'] = 'Carte';

    $optionMapGlobal = array();
    $optionMapGlobal['name'] = 'cartograph';
    $optionMapGlobal['value'] = 'Carte globale';
    
    $selectOptions = array();
    $selectOptions[] = $optionTable;
    $selectOptions[] = $optionAnalyze;
    $selectOptions[] = $optionAnalyzeGlobal;
    $selectOptions[] = $optionMap;
    $selectOptions[] = $optionMapGlobal;

    $data4citizenApi = new Api();
    try {
      $result = $data4citizenApi->getVisualizations(null, null, $selectedName, $selectedType);
      $result = json_decode($result, true);
      $result = $result['result'];

      //We encode the widget
      $visualizations = array();
      foreach ($result as $vis) {
        $widget = str_replace(array("'"), array("\'"), $vis['widget']);

        $vis['widget'] = $widget;
        $visualizations[] = $vis;
      }
    } catch (\Exception $e) {
			$errorMessage = $e->getMessage();
		}

    return [
      '#theme' => 'visualizations_template',
      '#page_title' => 'Gestion des visualisations',
      '#selectOptions' => $selectOptions,
      '#selectedName' => $selectedName,
      '#selectedType' => $selectedType,
      '#visualizations' => $visualizations,
      '#error' => $errorMessage,
      '#attached' => array(
        'library' =>
          array('ckan_admin/visualizations')
      ),
    ];
  }

}