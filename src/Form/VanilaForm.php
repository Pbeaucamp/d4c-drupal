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
use Drupal\file\Entity\File;
use Drupal\ckan_admin\Utils\HelpFormBase;
/**
 * Implements an example form.
 */
class VanilaForm extends HelpFormBase {


	/**
	 * {@inheritdoc}
	 */
	public function getFormId() {
		return 'VanilaForm';
	}

	/**
	 * {@inheritdoc}
	 */
public function buildForm(array $form, FormStateInterface $form_state) {
        $form = parent::buildForm($form, $form_state);
    
         $form['xxx'] = array(
          '#markup' => '<div id="content_place"></div>',
        );   
    
    
        $form['xxx1'] = array(
          '#markup' => '<div id="content2_place" >',
            '#attributes' => array('style' => 'display:none'),
        );   
    
        
        $form['#attached']['library'][] = 'ckan_admin/VanilaForm.form';
		$this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
        $this->urlCkan = $this->config->ckan->url;
        $api = new Api;
        $config = \Drupal::service('config.factory')->getEditable('ckan_admin.VanilaForm');
		$datas = $config->get('setting_back_office');
        $datas = json_decode($datas);
    
        if(!$datas[0]){
        
        
       
            $new_data['name'] = $this->nettoyage($title); ;
            $new_data['title'] = $title;
            $new_data['img_url'] = $url_pict;
            $new_data['url_1'] = $url_1;
            $new_data['url_2'] = $url_2;
        
        $datas = array(
                     array("name"=>"Ckan","title"=>"Ckan","img_url"=>"https://www.edawax.de/wp-content/uploads/2013/09/250_logo-ckan.jpg","url_1"=>"","url_2"=>""),
                     array("name"=>"vanilla_hub","title"=>"Vanilla Hub","img_url"=>"https://upload.wikimedia.org/wikipedia/commons/9/9e/Vanilla_Logo_1.png","url_1"=>"","url_2"=>""),
                     array("name"=>"matomo","title"=>"Matomo","img_url"=>"https://blog.saasweb.net/wp-content/uploads/2018/10/matomo-logo.png","url_1"=>"","url_2"=>""),
        
        
        );
        
        
        $config->set('setting_back_office', json_encode($datas))->save();
        $datas = $config->get('setting_back_office');
        $datas = json_decode($datas);
        
        
    }
    
        $datas2 = json_encode($datas);
        $datas_arr=array();
        $datas_arr['new']='Сréer';

        for ($i=0; $i<count($datas); $i++){
            
           $datas_arr[$datas[$i]->name]= $datas[$i]->title;  
            
        }
    
    
         $form['json'] = array(
                '#type' => 'checkbox',
                '#attributes' => array('value'=>$datas2, 'style'=>'display:none'),

            );
    
    
         $form['datas'] = array(
                '#type' => 'select',
                '#title' => t('Paramètre:'),
                '#options' => $datas_arr,
                '#attributes' => array('style' => 'width: 50%;', 'onchange'=>'fillData('.$datas2.')' ), 
            );

    
        $form['title'] = array(
                '#markup' => '',
                '#type' => 'textfield',
                '#title' => $this->t('Titre :'),
                 '#attributes' => array('style' => 'width: 50%;'),
            );
     
         $form['img_url'] = array(
                '#type' => 'managed_file',
                '#title' => t('Image:'),
                '#upload_location' => 'public://api/portail_d4c/img',
                '#upload_validators' => array(
                    'file_validate_extensions' => array('jpeg png jpg svg gif WebP PNG JPG JPEG SVG GIF'),
                ),
                '#size' => 22,
            );
    
           $form['m1'] = array(
              '#markup' => '<div id="img_form"></div>',
            );   
    
         $form['url_1'] = array(
                '#type' => 'textarea',
                '#title' => $this->t('Lien:'),
                '#attributes' => array('style' => 'width: 50%; height: 2em;'),

            );
    

    
          $form['url_2'] = array(
                '#type' => 'textarea',
                //'#title' => $this->t('URL 2:'),
                '#attributes' => array('style' => 'width: 50%; height: 2em; display:none'),

            );
    
 
        $form['valider'] = array(
                '#type' => 'submit',
                '#value' => $this->t('Valider'),
            );
    
        $form['delete'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Supprimer'),
            '#submit' => array('::delete'),
            '#attributes' => array('style' => 'color: #fcfcfa; background:#e1070799;'),
        );
    
    
        $form['xxx2'] = array(
          '#markup' => '</div>',
        );   

		return $form;
	}
       
public function submitForm(array &$form, FormStateInterface $form_state){
        
        
        $this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
        $this->urlCkan = $this->config->ckan->url;
        $api = new Api;
        
        $config = \Drupal::service('config.factory')->getEditable('ckan_admin.VanilaForm');
		$datas = $config->get('setting_back_office');
        $datas = json_decode($datas);
    
        $datas_name = $form_state->getValue('datas');
        $title = $form_state->getValue('title');
        $url_1 = $form_state->getValue('url_1');
        $url_2 = $form_state->getValue('url_2');
        
       
        $form_file = $form_state->getValue('img_url');

         if (isset($form_file[0]) && !empty($form_file[0])) {

                        $file = File::load($form_file[0]);
                        $file->setPermanent();
                        $file->save();
                        $url_t = parse_url($file->createFileUrl(FALSE));
                        $url_pict = $url_t["path"];

                    }
         else{
                        
                        $url_pict='default';
                    }
    
        
    
    
        if($datas_name == 'new'){
            
            $new_data = array();
            
            $new_data['name'] = $this->nettoyage($title); ;
            $new_data['title'] = $title;
            $new_data['img_url'] = $url_pict;
            $new_data['url_1'] = $url_1;
            $new_data['url_2'] = $url_2;
            
            $ex = false;
            
            foreach($datas as &$value){
                
                if($value->name==$new_data->name){
                    $ex=true;
                    
                    \Drupal::messenger()->addMessage(t('exist'), 'error');
                    
                }
                  
            }
            
            
            if($ex==false){
            
               $datas[] = $new_data;
                
                
               
                
               $config->set('setting_back_office', json_encode($datas))->save();
                
                
            }
            
            
            
            
            
        }
        else{
        
         foreach($datas as &$value){
             
             
                
                if($value->name==$datas_name){
                   $ex=false;
                    
                     foreach($datas as &$value2){
                         
                         if($value2->name==$this->nettoyage($title) && $value2->name != $datas_name){
                             $ex=true;
                    
                             \Drupal::messenger()->addMessage(t('exist'), 'error');
                             
                         }
                         
                         
                     }
                    
                    
                    if($ex==false){
                        
                   $value->name = $this->nettoyage($title);
                   $value->title = $title;
                   $value->img_url = $url_pict;
                   $value->url_1 = $url_1;
                   $value->url_2 = $url_2;
                        
                    $config->set('setting_back_office', json_encode($datas))->save();    
                        
                        break;
                           
                    }   
                    
                }
                  
            }
        
    }
    
    
        
    
    
    
    
        
	}
    
public function validateForm(array &$form, FormStateInterface $form_state){
        
        $title = $form_state->getValue('title');
        $url_1 = $form_state->getValue('url_1');
        $title = $form_state->getValue('title');
     
        if( $title == '') $form_state->setErrorByName('title', $this->t('Aucune donnée sélectionnée'));   
        if( $url_1 == '') $form_state->setErrorByName('url_1', $this->t('Aucune donnée sélectionnée'));   
        if( $title == '') $form_state->setErrorByName('title', $this->t('Aucune donnée sélectionnée'));   
        
    }     
    
public function delete(array &$form, FormStateInterface $form_state){
    
    
        $this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
        $this->urlCkan = $this->config->ckan->url;
        $api = new Api;
        $datas_name = $form_state->getValue('datas');
        $config = \Drupal::service('config.factory')->getEditable('ckan_admin.VanilaForm');
		$datas = $config->get('setting_back_office');
        $datas = json_decode($datas);
        

        for ($i=0; $i<count($datas); $i++){

            if($datas[$i]->name ==$datas_name){
             unset($datas[$i]);
                
                break;
   
            }
   
        }
    
     
    
        $config->set('setting_back_office', json_encode(array_values($datas)))->save();
    
    
    
		
	} 

function nettoyage( $str, $charset='utf-8' ) {
 $str = utf8_decode($str);
   // $str = htmlentities( $str, ENT_NOQUOTES, $charset );
    
    $str = utf8_decode($str);
         
       
    $str = str_replace("?", "", $str);   
    //$label = preg_replace('@[^a-zA-Z0-9_]@','',$label);
    $str = str_replace("`", "_", $str);
    $str = str_replace("'", "_", $str);
    $str = str_replace("-", "_", $str);
    $str = str_replace(" ", "_", $str);
    $str = str_replace("%", "", $str);
    $str = str_replace("(", "", $str);
    $str = str_replace(")", "", $str);
    $str = str_replace("*", "", $str);
    $str = str_replace("!", "", $str);
    $str = str_replace("@", "", $str);
    $str = str_replace("#", "", $str);
    $str = str_replace("$", "", $str);
    $str = str_replace("^", "", $str);
    $str = str_replace("&", "", $str);
    $str = str_replace("+", "", $str);
    $str = str_replace(":", "", $str);
    $str = str_replace(">", "", $str);
    $str = str_replace("<", "", $str);
    $str = str_replace('\'', "_", $str);
    $str = str_replace("/", "_", $str);
    $str = str_replace("|", "_", $str);
    $str = strtolower($str);     
    $str = preg_replace( '#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str );
    $str = preg_replace( '#&([A-za-z]{2})(?:lig);#', '\1', $str );
    $str = preg_replace( '#&[^;]+;#', '', $str );      
    
        
        
    $str = str_replace("-", "_", $str);    
    return $str;
} 

    

    
    

 
    

}