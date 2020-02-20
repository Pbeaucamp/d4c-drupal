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
 
 This file uses a library under MIT Licence :

ods-widgets -- https://github.com/opendatasoft/ods-widgets
Copyright (c) 2014 - Opendatasoft

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
 
 */
class customViewsForm extends HelpFormBase {
	
	
	/**
	 * {@inheritdoc}
	 */
    
	public function getFormId() {
        
		return 'custom_views_form';
	}

	/**
	 * {@inheritdoc}
	 */
    
    public function getCustomView($idDataset) {
        
      
		$table = "d4c_custom_views";
		$query = \Drupal::database()->select($table, 'map');

		$query->fields('map', [
			'cv_id',
			'cv_name',
			'cv_title',
			'cv_icon',
			'cv_template'
		]);
		
		$query->condition('cv_dataset_id',$idDataset);		
		$prep=$query->execute();
        
        
        
		//$prep->setFetchMode(PDO::FETCH_OBJ);
		$res= array();
		while ($enregistrement = $prep->fetch()) {
			array_push($res, $enregistrement);
		}
		if(count($res) > 0){
			$cv = $res[count($res)-1];
			
			$table = "d4c_custom_views_html";
			$query = \Drupal::database()->select($table, 'map');

			$query->fields('map', [
				'cvh_html',
				'cvh_order'
			]);
			
			$query->condition('cvh_id_cv',$cv->cv_id);
			$query->orderBy('cvh_order', 'ASC');
			
			$prep=$query->execute();
			//$prep->setFetchMode(PDO::FETCH_OBJ);
			$html= array();
			while ($enregistrement = $prep->fetch()) {
				array_push($html, $enregistrement);
			}
			$cv->html = $html;
            
           
			
			return $cv;
		} else {
			return null;
		}
        
        
//        $api = new API();
//      $result = json_decode($api->callCustomView($idDataset));
//      
//        return $result;
        
	}
    
	public function buildForm(array $form, FormStateInterface $form_state) {

        $form = parent::buildForm($form, $form_state);
       
        $form['#attached']['library'][] = 'ckan_admin/custom_views.form';
        
		$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
		$config->set('ids', null)->save();
		$organisations = $config->get('organisations');
        $api = new API();
		$dataset = $api->callPackageSearch_public_private('include_private=true&rows=1000&sort=title_string asc', \Drupal::currentUser()->id());
        $dataset = json_decode($dataset->getContent());
        $dataset= $dataset->result->results;
        $Select_data = array();
        
        
        for ($i = 0; $i < count($dataset); $i++){
            $Select_data[$dataset[$i]->id] = $dataset[$i]->title;  
        }
        
        
        $form['selected_Data'] = array(
        
           '#type' => 'select',
           '#title' => t('Sélectionner des données:'),
           '#options' => $Select_data,

            '#attributes' => [
                'onchange' => 'getData()',
            ],
       );
        
		// $form['name'] = array(
				// '#type' => 'textfield',
				// '#title' => $this->t('Nom:'),
                
		// );
        
        $form['title'] = array(
				'#type' => 'textfield',
				'#title' => $this->t('Titre:'),
                '#maxlength' => 50
		);
        
                
        $form['selected_templ'] = array(
           '#type' => 'select',
           '#title' => t('Modèle sélectionné:'),
           '#options' =>array(
               1=>'1',
               2=>'2',
               3=>'3',
               4=>'4'
           ),
            '#attributes' => [
                'onchange' => 'getTemplate()',
            ],
       );
        $form['template_1'] = array(
            '#markup' => '',
            '#type' => 'textarea',
            '#title' => t('Modèle 1:'),
        );
        
        $form['template_2'] = array(
            '#markup' => '',
            '#type' => 'textarea',
            '#title' => t('Modèle 2:'),
        );
        
        $form['template_3'] = array(
            '#markup' => '',
            '#type' => 'textarea',
            '#title' => t('Modèle 3:'),
        );
        
        $form['template_4'] = array(
            '#markup' => '',
            '#type' => 'textarea',
            '#title' => t('Modèle 4:'),
        );
        
        
         $form['valider'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Enregistrer'),
        );
        
        $form['m5_2'] = array(
			'#markup' => '<span>&nbsp;&nbsp;&nbsp;&nbsp;</span>',
        ); 
    

        $form['dell'] = [
			'#type' => 'submit',
			'#value' => $this->t('Supprimer'),
			'#name' => 'dell_btn',
			'#submit' => array([$this, 'delCustomView']),
			'#attributes' => array('style' => 'color: #fcfcfa; background:#e1070799;'),

		];
    
		if($_POST["getD"]){
			$data = $this->getCustomView($_POST["getD"]);
			echo json_encode($data,true);
		}
	
		return $form;
	}
    
	public function submitForm(array &$form, FormStateInterface $form_state){ 
        $selected_templ = $form_state->getValue('selected_templ');
        
        $data = array();
        $data["cv_dataset_id"]=$form_state->getValue('selected_Data');
        $data["cv_name"]=$form_state->getValue('title');
        $data["cv_title"]=$form_state->getValue('title');
        $data["cv_icon"]='tachometer';
        $data["cv_template"]=$selected_templ;
        $data["html"]=array(array());
        
        for ($i = 0; $i < $selected_templ; $i++){
 
            $html_str = $form_state->getValue('template_'.($i+1).'');
			$reg_ = '/<d4c-dataset-context[^>]*>/i';
			$reg__ = '/<\/d4c-dataset-context>/i';
			$reg___ = '/ context="[^"]*"/i';echo $html_str;
			$html_str_fin = preg_replace($reg_,"",$html_str);echo $html_str_fin;
			$html_str_fin = preg_replace($reg__,"",$html_str_fin);echo $html_str_fin;
			$html_str_fin = preg_replace($reg___,' context="ctx"',$html_str_fin);echo $html_str_fin;
            $data["html"][$i]["cvh_html"]= $html_str_fin;
            $data["html"][$i]["cvh_order"]= $i+1; 

        }     

        $old_Data = $this->getCustomView($data["cv_dataset_id"]);
        // add data to db
        if($old_Data != null){
            
            $cv_id = $old_Data->cv_id;
            $query = \Drupal::database()->update('d4c_custom_views');
            $query->fields([
                'cv_name' => $data["cv_name"],
                'cv_title' => $data["cv_title"],
                'cv_icon' => $data["cv_icon"],
                'cv_template' => $data["cv_template"]
                
            ]);
            $query->condition('cv_id', $cv_id);
            $query->execute();
            
            // delet old templ 
            
			$query = \Drupal::database()->delete('d4c_custom_views_html')
            ->condition('cvh_id_cv', $cv_id)
            ->execute();
            
            
            
             // isert data html in custom_views_html
            
            for ($i = 0; $i < $data["cv_template"]; $i++){

				$query_html = \Drupal::database()->insert('d4c_custom_views_html');  
				$query_html->fields([
					'cvh_id_cv',
					'cvh_html',
					'cvh_order'  
				]);
				$query_html->values([
					$cv_id,
					$data["html"][$i]["cvh_html"],
					$data["html"][$i]["cvh_order"]                
				]);

				$query_html->execute(); 

			}
        }
        else{

            $query = \Drupal::database()->insert('d4c_custom_views');
            $query->fields([
                'cv_dataset_id',
                'cv_name',
                'cv_title',
                'cv_icon',
                'cv_template'
                
            ]);
            $query->values([
                $data["cv_dataset_id"],
                $data["cv_name"],
                $data["cv_title"],
                $data["cv_icon"],
                $data["cv_template"]
                
            ]);

            $query->execute();
            

            $new_custom_view = $this->getCustomView($data["cv_dataset_id"]);
            $new_id_cv = $new_custom_view->cv_id;
            
			// isert data html in custom_views_html
            
            for ($i = 0; $i < $data["cv_template"]; $i++){
  
				$query_html = \Drupal::database()->insert('d4c_custom_views_html');  
				$query_html->fields([
					'cvh_id_cv',
					'cvh_html',
					'cvh_order'
					
					
				]);
				$query_html->values([
					$new_id_cv,
					$data["html"][$i]["cvh_html"],
					$data["html"][$i]["cvh_order"]               
					
				]);

				$query_html->execute(); 

			}   
             
        }
		
		$api = new Api();
		$api->calculateVisualisations($data["cv_dataset_id"]);
        
       drupal_set_message('Les données ont été sauvegardées');
        
      // drupal_set_message(t(' %title.', ['%title' => $form_state->getValue('template_'.$i.'')])); 
   //}
	}
    
    public function validateForm(array &$form, FormStateInterface $form_state){
        
        $selected_Data = $form_state->getValue('selected_Data');
        //$name = $form_state->getValue('name');
        $title = $form_state->getValue('title');
     
        if( $selected_Data == '') $form_state->setErrorByName('selected_Data', $this->t('Aucune donnée sélectionnée'));   
       // if( $name == '') $form_state->setErrorByName('name', $this->t('Aucune donnée sélectionnée'));   
        if( $title == '') $form_state->setErrorByName('title', $this->t('Aucune donnée sélectionnée'));   
        
    } 

    public function delCustomView(array &$form, FormStateInterface $form_state) {
         
		$id_dataset = $form_state->getValue('selected_Data');
         
        $new_custom_view = $this->getCustomView($id_dataset);
        $cv_id = $new_custom_view->cv_id;
         
		$query = \Drupal::database()->delete('d4c_custom_views_html')
		->condition('cvh_id_cv', $cv_id)
		->execute();
         
		$query2 = \Drupal::database()->delete('d4c_custom_views')
		->condition('cv_id', $cv_id)
		->execute();
            
		$api = new Api();
		$api->calculateVisualisations($id_dataset);
	}

    
}
