<?php
/**
 * @file
 * Contains \Drupal\search_api_solr_admin\Form\QueryForm.
 */

namespace Drupal\ckan_admin\Form;

use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\Query;
use Drupal\ckan_admin\Utils\Export;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use \PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use \PhpOffice\PhpSpreadsheet\Reader\Xls;
use \PhpOffice\PhpSpreadsheet\Writer\Csv;
use Drupal\ckan_admin\Utils\HelpFormBase;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Drupal\Core\Url;
use Drupal\ckan_admin\Utils\Logger;

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

class userStoryForm extends HelpFormBase
{
    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'userStoryForm';
    }


    
    public function buildForm(array $form, FormStateInterface $form_state){


        $form = parent::buildForm($form, $form_state);
      
        // $form['#attached']['library'][] = 'ckan_admin/iconpicker.form';

        $form['#attached']['library'][] = 'ckan_admin/userstory.form';
        $this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
        $this->urlCkan = $this->config->ckan->url;

        $api = new Api;



        $cle = $this->config->ckan->api_key;
        $optionst = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => array(
                'Content-type:application/json',
                'Content-Length: ' . strlen($jsonData),
                'Authorization:  ' . $cle,
            ),
        );



        //////////////////INFORMATION GENERALE/////////////////////////////////////////        
                $form['m1'] = array(
            '#markup' => '<div id="visibilityStories"></div>',
        ); 
        
        $form['m2'] = array(
            '#markup' => '<div id="visibilityModalStory">',
        ); 
        
        $form['title'] = array(
            '#markup' => '',
            '#type' => 'textfield',
            '#title' => $this->t('Le temps de défilement :'),
             '#attributes' => array('style' => 'width: 50%;'),
             '#required' => TRUE,
             '#maxlength' => 300
        );
        
        $form['img_widget'] = array(
            '#type' => 'managed_file',
            '#title' => t("L'image de l'histoire de données :"),
            '#upload_location' => 'public://dataset/',
            '#upload_validators' => array(
                'file_validate_extensions' => array('jpeg png jpg svg gif WebP PNG JPG JPEG SVG GIF'),
            ),
            '#size' => 22,
        );

        $form['label_widget'] = array(
            '#markup' => '',
            '#type' => 'textfield',
            '#title' => $this->t('Label widget :'),
             '#attributes' => array('style' => 'width: 50%;'),
             '#required' => TRUE,
             '#maxlength' => 300
        );
        
        $form['widget'] = array(
            '#type' => 'textarea',
            '#title' => $this->t('Widget/URL :'),
            '#resizable' => true,
            '#attributes' => array('style' => 'width: 50%;'),

        );
        
        $form['valider'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Valider')
        );

        $form['m1_2'] = array(
          '#markup' => '</div>',
        );         
            
        //////////////////INFORMATION GENERALE/////////////////////////////////////////          
                
                

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state){

         $scroll_time  = $form_state->getValue('title');
         $label = $form_state->getValue('label_widget');
         $form_file = $form_state->getValue('img_widget');
         $widget = $form_state->getValue('widget');

         $context =[
            'scroll_time '=>$scroll_time ,
            'widget_label '=>$widget_label,
            'widget '=>$widget ,
        ];

         if (isset($form_file[0]) && !empty($form_file[0])) {

                                $file = File::load($form_file[0]);
                                $file->setPermanent();
                                $file->save();
                                $file->url();
                                $context[image]= $file->url();

          } 

          $api = new Api;
          $callUrlCreate = $this->urlCkan . "/api/action/story_create";
            $return = $api->updateRequest($callUrlCreate, $context, "POST");
            $return = json_decode($return, true);
       var_dump($return);die;

    }
    

}
