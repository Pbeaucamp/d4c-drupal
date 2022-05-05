<?php
/**
 * @file
* Contains \Drupal\search_api_solr_admin\Form\QueryForm.
*/

namespace Drupal\ckan_admin\Form;

use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\Query;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use \PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use \PhpOffice\PhpSpreadsheet\Reader\Xls;
use \PhpOffice\PhpSpreadsheet\Writer\Csv;;
use Drupal\ckan_admin\Utils\DataSet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Drupal\ckan_admin\Utils\HelpFormBase;
use Drupal\ckan_admin\Utils\External;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\ckan_admin\Utils\Logger;
  

/**
 * Implements an example form.
 */
class updateControlForm extends HelpFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'updateControlForm';
  }


  /**
   * {@inheritdoc}
   */

  function dummy_preprocess_page(&$variables) {
    if (\Drupal::service('path.matcher')->isFrontPage()) {
      $variables['#attached']['library'][] = 'ckan_admin/editMetaDataForm.form';
    }
  }

 
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'][] = 'ckan_admin/updateControlForm.form';
    $form['#attached']['html_head'][] = [
      array(
        '#tag' => 'base',
        '#attributes' => array(
        'href' => '/admin/config/data4citizen/updateControlForm/'
      ),
    ),
    "dd"];

		$this->config = include(__DIR__ . "/../../config.php");
    $this->urlCkan = $this->config->ckan->url;


    $api = new Api;
    date_default_timezone_set('Europe/Paris');

    $harvestDatasets = Dataset::getHarvestDatasetInformations();
    $harvestDatasetsJson = json_encode($harvestDatasets);

    $orgs = $api->getAllOrganisations(true, true);
    $option_org=array();
    for ($i = 0; $i < count($orgs); $i++) {
      $option_org[$orgs[$i][id]]=$orgs[$i][display_name];
    }
      
    $form['m1'] = array(
      '#markup' => '<div id="formModal"></div>',
    );

    $form['selected_org'] = array(
      '#type' => 'select',
      '#title' => t('Organisation :'),
      '#options' => $option_org,
      '#empty_option' => t('----'),
      '#attributes' => array('onchange'=>'fillTable('.$harvestDatasetsJson.');'),
    );

    $form['table'] = array(
      '#type' => 'table',
      '#header' => array(
        $this->t('Nom'),
        $this->t('Organisation'),
        $this->t("Origine"),
        $this->t("Site"),
        $this->t('Date de dernière réplication'),
        $this->t('Date de prochaine réplication'),
        $this->t('État'),
        $this->t('Fréquence de moissonnage'),  
        $this->t('Détails'),
      ),
    );

    $i=0;
        
    for($i=0;$i<1;$i++){

      //name
      $form['table'][$i]['name'][1] = array(
        '#markup' => '.'     
      );
        
      $form['table'][$i]['name'][2] = array(
        '#type' => 'textfield',               
      );
        
      $form['table'][$i]['organisation'][1] = array(
        '#markup' => '.'     
      );
      
      //site
      $form['table'][$i]['site'] = array(
        '#markup' =>'.'
      );

      $form['table'][$i]['type'] = array(
        '#markup' => '.'      
      );   

      //last update         
      $form['table'][$i]['last_update'] = array(
        '#markup' => '.'      
      );

      //future_update
      $form['table'][$i]['future_update'] = array(
        '#markup' => '.'      
      );
            
      $form['table'][$i]['status'] = array(
        '#type' => 'select',     
        '#options' => array('A'=>'Actif', 'P'=>'Passif'),      
      );

      $form['table'][$i]['period'][1] = array(
        '#type' => 'select',     
        '#options' => array('Mi'=>'Minute', 'H'=>'Heure', 'D'=>'Jour', 'W'=>'Semaine', 'M'=>'Mois', 'Y'=>'Année'),            
      );

      $form['table'][$i]['period'][2] = array(
        '#type' => 'number',               
      );
    
      $form['table'][$i]['details'] = array(
        '#type' => 'button',
        '#value' => t('Détails'),            
      );

      $i++;
    }

    $form['search'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Envoyer'),
    );

    $form['mfilter'] = array(
    '#markup' => '<div id="filterModal"><div class="modal modal-filter" data-modal="2"><div id="filterPlace" style="overflow:scroll; height:35em; "><div class="parcel-search-widget ng-scope" ng-app="d4c.frontend">
                <div class="d4c-dataset-selection-list__records" d4c-external-context="" context="externalcontext" externalcontext-type="" externalcontext-id="" externalcontext-url="" externalcontext-parameters="" ng-init="showMapFilter=true;filteringEnabled=true">
                </div>
                </div></div></div><div class="overlay js-overlay-modal-filter"></div></div>',
    );
    return $form;
  }

    public function submitForm(array &$form, FormStateInterface $form_state) {
      $dataForUpdate = Dataset::getHarvestDatasets();
      $dataForUpdate = json_decode($dataForUpdate);
     
      $table = $form_state->getValue('table');
      $org = $form_state->getValue('selected_org');

      if($org!=''|| $org!=null) {
        foreach($table as &$res) {
            for ($i = 0; $i<count($dataForUpdate); $i++) {
                if($dataForUpdate[$i]->id_org == $org){
                    $datasets = $dataForUpdate[$i]->datasets;
                    for($j = 0; $j<count($datasets); $j++){
                        
                        if($datasets[$j]->id_data == $res[name][2]) {
                            if($res[valuedetails_span] != null || $res[valuedetails_span] != "" ) {
                              $datasets[$j]->parameters = json_decode($res[valuedetails_span]); 
                              $datasets[$j]->date_last_filtre = date("Y-m-d H:i:s");
                            }
                            
                            $datasets[$j]->periodic_update = $res[period][1].';'.$res[period][2].';'.$res[status];
                            break;
                        }
                    }
                    $dataForUpdate[$i]->datasets = $datasets;
                    break; 
                }
            }
        }

        $config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
        $config->set('dataForUpdateDatasets', json_encode($dataForUpdate))->save(); 
    }
  }
}