<?php
/**
 * @file
* Contains \Drupal\search_api_solr_admin\Form\QueryForm.
*/

namespace Drupal\ckan_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ckan_admin\Utils\Query;
use Drupal\ckan_admin\Utils\DataSet;
use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\HelpFormBase;
/**
 * Implements an example form.
 */
class ckanForm extends HelpFormBase {


	/**
	 * {@inheritdoc}
	 */
	public function getFormId() {
		return 'ckanForm';
	}



	/**
	 * {@inheritdoc}
	 */
public function buildForm(array $form, FormStateInterface $form_state) {

        $form = parent::buildForm($form, $form_state);
           
        
        $form['#attached']['library'][] = 'ckan_admin/ckanForm.form';
		$this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
        $this->urlCkan = $this->config->ckan->url;
        
     
        

        $api = new Api;
    
    
   // $a=DataSet::callUpdateDatasetDataGouv();

        $config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
		$dataForUpdateDatasets = $config->get('dataForUpdateDatasets');
		$dataForUpdateDatasets2 = $dataForUpdateDatasets ;

        $dataForUpdateDatasets = json_decode($dataForUpdateDatasets);
        $option_org=array();
        $option_idDataset=array();
       // drupal_set_message('<pre>'. print_r($dataForUpdateDatasets, true) .'</pre>');
        foreach($dataForUpdateDatasets as &$value){
                
            $option_org[$value->id_org]=$value->name_org;
            
            
        }
        							  
								   
			$form['selected_org'] = array(
            '#type' => 'select',
            '#title' => t('Organisation :'),
            '#options' => $option_org,
            '#empty_option' => t('----'),
            '#attributes' => array('style' => 'width: 50%;', 'onchange'=>'select('+$dataForUpdateDatasets2+');'),
            '#ajax'         => [
                'callback'  => '::datasetCallback',
                'wrapper'   => 'selected_dataset',
      ],
            
        );
        					   
			$form['selected_dataset'] = array(
            '#type' => 'select',
            '#title' => t('Datasets:'),
            '#default_value' =>t('----'),
            '#empty_option' => t('----'),
            '#attributes' => [
                                    'id' => ['selected_dataset'],
                                    'style' => 'width: 50%;'
                                ],
            
            
        );
        
        
            $form['id_dataset_selected'] = array(
                '#type' => 'textfield',
                '#attributes' => array('style' => 'display:none'),
                '#maxlength' => null,
            );
        
        
        
        $form['A_P'] = array(
            '#type' => 'select',
            '#title' => t('Fréquence de moissonnage:'),
            '#attributes' => array('style' => 'width: 50%;', 'onchange'=>'hideTimiPick();'),
            '#options' => array('A'=>'Actif', 'P'=>'Passif'),
            
        );
      
        $form['time_up'] = array(
            '#type' => 'select',
            '#attributes' => array('style' => 'width: 50%;', 'onchange'=>'controlTimeUpdate()'),
            '#options' => array('Mi'=>'Minute', 'H'=>'Heure', 'D'=>'Jour', 'W'=>'Semaine', 'M'=>'Mois', 'Y'=>'Année'),
            
        
        );
        
         $form['time_up_value'] = array(
                '#type' => 'number',
                '#attributes' => array('style' => 'width: 50%;', 'min'=>'1', 'value'=>'1', 'max'=>'100'),
                '#maxlength' => null,
            );
        
        
         $form['valider'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Sauvegarder'),
        );
        
        $form['updateNow'] = array(
				'#type' => 'submit',
				'#value' => $this->t('Moissonnage instantané'),
				'#submit' => array('::updateDatasetNow')
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
        
        
        $org = $form_state->getValue('selected_org');
        $datasett = $form_state->getValue('id_dataset_selected');
        $A_P = $form_state->getValue('A_P');
        $time_up = $form_state->getValue('time_up');
        $time_up_value = $form_state->getValue('time_up_value');
       
        foreach($dataForUpdate as &$value){
            if($value->id_org==$org){
                foreach($value->datasets as &$dataset_value){
                    if($dataset_value->id_data==$datasett){
                        if($time_up_value==''){
                            $time_up_value=='1';
                        }
                       
                        $dataset_value->periodic_update=$time_up.';'.$time_up_value.';'.$A_P;
                    }
                } 
            }     
        }

        //drupal_set_message('<pre>'. print_r($dataForUpdate, true) .'</pre>');  
		$config->set('dataForUpdateDatasets', json_encode($dataForUpdate))->save();  
        
		drupal_set_message('Les données ont été sauvegardées');
		
	}
    
        
	public function datasetCallback(array &$form, FormStateInterface $form_state){
    
        $this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
        $this->urlCkan = $this->config->ckan->url;
        $api = new Api;

        $config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
		$dataForUpdateDatasets = $config->get('dataForUpdateDatasets'); 
		$dataForUpdateDatasets2 =  $dataForUpdateDatasets;
        $dataForUpdateDatasets = json_decode($dataForUpdateDatasets);
        $option_dataset=array();
        $option_dataset['default']='---';
        $selected_org = $form_state->getValue('selected_org');
    
        foreach($dataForUpdateDatasets as &$value){
            if($value->id_org==$selected_org){
                foreach($value->datasets as &$datasets){
                    $option_dataset[$datasets->id_data]=$datasets->title_data;
                }
                
				break;
            }
            //$option_org[$value->id_org]=$value->name_org;
        }
    
		$elem = [
            '#type' => 'select',
            //'#title' => t('Datasets:'),
            '#options' => $option_dataset,
            '#default_value' =>'default',
            '#attributes' => [
				'id' => ['selected_dataset'],
				'style' => 'width: 50%;',
				'onchange'=>'select('.$dataForUpdateDatasets2.');'
			],
        ];
		// $form_state->setRebuild();
		return $elem;
  }
    
    
	public function updateDatasetNow(array &$form, FormStateInterface $form_state){
    
		$this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
        $this->urlCkan = $this->config->ckan->url;
        $api = new Api;
    
		$config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
		$dataForUpdate = $config->get('dataForUpdateDatasets');
        
        
        $dataForUpdate = json_decode($dataForUpdate);
    
        $org = $form_state->getValue('selected_org');
        $datasett = $form_state->getValue('id_dataset_selected');
        $A_P = $form_state->getValue('A_P');
        $time_up = $form_state->getValue('time_up');
        $time_up_value = $form_state->getValue('time_up_value');
        $id_org = $form_state->getValue('selected_org');
        $id_dataset = $form_state->getValue('id_dataset_selected');
        
        $saveTimeZone = date_default_timezone_get();
        date_default_timezone_set('Europe/Paris');
    
        foreach($dataForUpdate as &$value){
            if($value->id_org==$org){
                foreach($value->datasets as &$dataset_value){
                    if($dataset_value->id_data==$datasett){
                        
                        //drupal_set_message('<pre>'. print_r($dataset_value,true) .'</pre>');
                        $id_dataset_gouv = $dataset_value->id_data_site;
                        $site = $dataset_value->site;
                        $site_infocom='';
                        $title_data=$dataset_value->title_data;
                        $parameters=$dataset_value->parameters;
                        if($dataset_value->site_infocom){
                            $site_infocom = $dataset_value->site_infocom; 
                        }
                    
                        $dataset_value->last_update = date("m/d/Y H:i:s");
                        $dataset_value->date_last_moissonnage = date("m/d/Y H:i:s");
                        //drupal_set_message('<pre>'. date("m/d/Y H:i:s") .'</pre>');
                        
//                         if($time_up_value==''){
//                            $time_up_value=='1';
//                        }
                          
                        //$dataset_value->periodic_update=$time_up.';'.$time_up_value.';'.$A_P;
                        
                        break 2;
                    }
                }
			}
        }
        
        date_default_timezone_set($saveTimeZone);

		$config->set('dataForUpdateDatasets', json_encode($dataForUpdate))->save(); 
		$query = DataSet::updateDatasetFromDataGouv($id_dataset_gouv, $id_dataset, $id_org,$site,$site_infocom, $title_data, $parameters);
		$query = json_decode($query);    
    
	}    
    
    

 
    

}