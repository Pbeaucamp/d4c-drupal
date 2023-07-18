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

use Drupal\file\Entity\File;
use Drupal\ckan_admin\Utils\Api;
use Symfony\Component\HttpFoundation\Response;
use Drupal\ckan_admin\Utils\HelpFormBase;


/**
 * Implements an example form.
 */



		//drupal_get_messages('error');
        
      


class addThemeInDatasetForm extends HelpFormBase {
	/**
	 * {@inheritdoc}
	 */
	public function getFormId() {
		return 'addThemeInDatasetForm';
	}

	/**
	 * {@inheritdoc}
	 */
    

	public function buildForm(array $form, FormStateInterface $form_state) {
        $form = parent::buildForm($form, $form_state);
        
        $form['#attached']['library'][] = 'ckan_admin/addThemeInDatasetForm.form';
        
		$this->config = include(__DIR__ . "/../../config.php");
		$this->urlCkan = $this->config->ckan->url; 
        
        $api = new Api;
        $dataSet= $api->callPackageSearch_public_private('include_private=true&rows=1000&sort=title_string asc', \Drupal::currentUser()->id());
        $dataSet = $dataSet->getContent();
        $dataSet = json_decode($dataSet,true);
        $dataSet = $dataSet['result']['results'];
        $ids=array();
        $ids[$dataSet[$i]["0"]]="---";
        
        for($i=0; $i<(is_countable($dataSet) ? count($dataSet) : 0); $i++){
            
          
            $label_theme = 'Default';
            $theme = 'default';
            
            for($j=0; $j<(is_countable($dataSet[$i]['extras']) ? count($dataSet[$i]['extras']) : 0);$j++){
                
                if($dataSet[$i]['extras'][$j]['key']=='theme'){
               $theme = $dataSet[$i]['extras'][$j]['value'];
                    
                }
                
                if($dataSet[$i]['extras'][$j]['key']=='label_theme'){
                    
                 $label_theme = $dataSet[$i]['extras'][$j]['value'];  
                }
                
            }
           
                $ids[$dataSet[$i]['id'].'|'.$theme.'%'.$label_theme]=$dataSet[$i]['title'];
            
            
        }
    
        $config = \Drupal::service('config.factory')->getEditable('ckan_admin.addThemeInDatasetForm');
        $themConfig = \Drupal::service('config.factory')->getEditable('ckan_admin.themeForm');
        $t = $themConfig->get('themes');
        $themes = json_decode($t);
        $valuesForSelect=array();  
        
        for($i=0; $i<(is_countable($themes) ? count($themes) : 0); $i++){
            $valuesForSelect[$themes[$i]->title.'%'.$themes[$i]->label]=$themes[$i]->label;  
        }
        
       
        $form['selected_data'] = array(
       '#type' => 'select',
       '#title' => t('Sélectionner un jeu de données'),
       '#options' => $ids,
            '#attributes' => array( 
                'onchange' => 'select_theme()'),
        
        );
            
        $form['selected_theme'] = array(
       '#type' => 'select',
       '#title' => t('Sélectionner le thème'),
       '#options' => $valuesForSelect,
            '#default_value' => t('default'),
            '#attributes' => array( 
                'id' => ['selected_theme'])
            
       );
        

		$form['valider'] = array(
				'#type' => 'submit',
				'#value' => $this->t('Valider'),
		);
		
		
		

		return $form;
	} 
    
	public function submitForm(array &$form, FormStateInterface $form_state){
        
		$this->config = include(__DIR__ . "/../../config.php");
        $this->urlCkan = $this->config->ckan->url; 
        
        $selectData = $form_state->getValue('selected_data');
        $selectData = explode("|", $selectData);
        $selectData = $selectData[0];
        $selectThem = $form_state->getValue('selected_theme');
        $selectThem =  explode("%", $selectThem); 
        $label_them =  $selectThem[1]; 
        $selectThem = $selectThem[0]; 
        $api = new Api;
        $dataSet= $api->callPackageSearch_public_private('rows=100000');
        $dataSet = $dataSet->getContent();
        $dataSet = json_decode($dataSet,true);
        $dataSet = $dataSet['result']['results'];
        $callUrl = $this->urlCkan . "/api/action/package_update";
        
        for($i=0; $i<(is_countable($dataSet) ? count($dataSet) : 0); $i++){
            
            
            
            if($dataSet[$i]['id']==$selectData){
                
                $cout_extras =is_countable($dataSet[$i]['extras']) ? count($dataSet[$i]['extras']) : 0;

                if($cout_extras!=0){
                    
                    $themEx=false;
                    $them_label_ex = false;
                    for($j=0; $j<(is_countable($dataSet[$i]['extras']) ? count($dataSet[$i]['extras']) : 0); $j++){
                        
                        if($dataSet[$i]['extras'][$j]['key']=='theme'){
                            $themEx=true;
                            $dataSet[$i]['extras'][$j]['value']=$selectThem;
                            
                            $return = $api->updateRequest($callUrl,$dataSet[$i],"POST" );

                        }
                        
                        if($dataSet[$i]['extras'][$j]['key']=='label_theme'){
                            
                            $them_label_ex=true;
                            $dataSet[$i]['extras'][$j]['value']=$label_them;
                            
                            $return = $api->updateRequest($callUrl,$dataSet[$i],"POST" );

                        }
                        
                        
                    }
                    
                    if($them_label_ex==false){
                    $dataSet[$i]['extras'][is_countable($dataSet[$i]['extras']) ? count($dataSet[$i]['extras']) : 0]['key']='label_theme';
                    $dataSet[$i]['extras'][(is_countable($dataSet[$i]['extras']) ? count($dataSet[$i]['extras']) : 0)-1]['value']=$label_them;
                    $return = $api->updateRequest($callUrl,$dataSet[$i],"POST" ); 
                    }
                    
                    if($themEx==false){
                        
                    $dataSet[$i]['extras'][is_countable($dataSet[$i]['extras']) ? count($dataSet[$i]['extras']) : 0]['key']='theme';
                    $dataSet[$i]['extras'][(is_countable($dataSet[$i]['extras']) ? count($dataSet[$i]['extras']) : 0)-1]['value']=$selectThem;
                    $return = $api->updateRequest($callUrl,$dataSet[$i],"POST" ); 
                                 
                                               }
                    
            
                }
                else{
                    $dataSet[$i]['extras'][0]['key']='theme';
                    $dataSet[$i]['extras'][0]['value']=$selectThem;
                    
                    $dataSet[$i]['extras'][1]['key']='label_theme';
                    $dataSet[$i]['extras'][1]['value']=$label_them; 

                   $return = $api->updateRequest($callUrl,$dataSet[$i],"POST" ); 
             
                }
            }
                                               
                                               
                                               
                                               
        }
  
	}
    
    public function validateForm(array &$form, FormStateInterface $form_state){
        
        $selected_data = $form_state->getValue('selected_data');
        $selected_theme = $form_state->getValue('selected_data');
     
        if( $selected_data == '') $form_state->setErrorByName('selected_data', $this->t('Aucune donnée sélectionnée'));   
        if( $selected_theme == '') $form_state->setErrorByName('selected_theme', $this->t('Aucune donnée sélectionnée'));   
        
    }  
    
    
    
    
    
    

}