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
	public function buildForm(array $form, FormStateInterface $form_state) {
        $form = parent::buildForm($form, $form_state);
        $form['#attached']['library'][] = 'ckan_admin/updateControlForm.form';
        
		$this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
        $this->urlCkan = $this->config->ckan->url;
        $api = new Api;

        $config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
		$dataForUpdateDatasets = $config->get('dataForUpdateDatasets');
		$dataForUpdateDatasets2 = $dataForUpdateDatasets;
        $dataForUpdateDatasets = json_decode($dataForUpdateDatasets);
        $option_org=array();
        $datasets=array();
        
  
        
        // drupal_set_message('<pre>'+print_r($dataForUpdateDatasets,true)+'</pre>');
        foreach($dataForUpdateDatasets as &$value){
                
            $option_org[$value->id_org]=$value->name_org;
            $datasets=array_merge($datasets, $value->datasets);
            
            
        }
        
        
        
        
        $form['selected_org'] = array(
            '#type' => 'select',
            '#title' => t('Organisation :'),
            '#options' => $option_org,
            '#empty_option' => t('----'),
            '#attributes' => array('onchange'=>'fillTable('.$dataForUpdateDatasets2.');'),
            
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
    
//typy join/update    
//if($value->site_infocom=='joinDataset'){
//   $form['table'][$i]['type'] = array(
//      '#markup' => 'Jointure'      
//  ); 
//}
//else{
     $form['table'][$i]['type'] = array(
      '#markup' => '.'      
  ); 
//}    
         

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
    $i++;
  
}   
        
        
	
		$form['search'] = array(
				'#type' => 'submit',
				'#value' => $this->t('Envoyer'),
		);
	
		return $form;
	}
    
		public function submitForm(array &$form, FormStateInterface $form_state){ 
        
       $this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
        $this->urlCkan = $this->config->ckan->url;
        $api = new Api;

        $config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
		$dataForUpdate = $config->get('dataForUpdateDatasets');
		
        $dataForUpdate = json_decode($dataForUpdate);
    
        
        $table = $form_state->getValue('table');
        $org = $form_state->getValue('selected_org');
         
        
        if($org!=''|| $org!=null){
            
            foreach($table as &$res){
                
                for ($i = 0; $i<count($dataForUpdate); $i++){
                    
                    if($dataForUpdate[$i]->id_org == $org){
                        

                        $datasets = $dataForUpdate[$i]->datasets;
                        for($j = 0; $j<count($datasets); $j++){
                            
                            if($datasets[$j]->id_data == $res[name][2]){
                                
                                $datasets[$j]->periodic_update = $res[period][1].';'.$res[period][2].';'.$res[status];
                        
                                 
                                
                                break;
                            }
                            
                        }
                        $dataForUpdate[$i]->datasets = $datasets;
                       break; 
                    }
                    
                }
                
            }
            
            
            //drupal_set_message('<pre>'.json_encode($dataForUpdate).'</pre>');
            
            $config->set('dataForUpdateDatasets', json_encode($dataForUpdate))->save(); 
            
            
            
            
        }

        
	
	}
    

    

}