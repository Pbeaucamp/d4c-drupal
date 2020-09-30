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
use Drupal\ckan_admin\Utils\HelpFormBase;

/**
 * Implements an example form.
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

    /**
     * {@inheritdoc}
     */

    public function buildForm(array $form, FormStateInterface $form_state){
        $form = parent::buildForm($form, $form_state);

        $form['#attached']['library'][] = 'ckan_admin/userstoryForm.form';
        
        $this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
        $this->urlCkan = $this->config->ckan->url;

        $api = new Api;
//        $dataSet = $api->callPackageSearch_public_private('include_private=true&rows=1000&sort=title_string%20asc');
//        $dataSet = $dataSet->getContent();
//        $dataSet = json_decode($dataSet, true);
        
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
        
        $callUrlOrg = $this->urlCkan . "api/action/organization_list?all_fields=true&include_extras=true";
        $curlOrg = curl_init($callUrlOrg);

        curl_setopt_array($curlOrg, $optionst);
        $orgs = curl_exec($curlOrg);
        $orgsData = $orgs;
        
        curl_close($curlOrg);
        $orgs = json_decode($orgs, true);
        $this->orgas = $orgs;

        $stories = $api->getStories();

        $widgets = $api->getWidgets();
/*        foreach ($stories as $key => $value) {
            $api->deleteStory($value->story_id);
        }
        var_dump(sizeof($stories));

        var_dump(sizeof($widgets));
        die;*/

        $idUser = \Drupal::currentUser()->id();
            
        
        if($idUser != 0) {
            $form['m1'] = array(
            '#markup' => '<div id="visibilityStories"></div>',
        ); 

        $ids = array();
        $ids["new"]="Sélectionner une histoire(Modifier/Supprimer) ";
        foreach($stories as &$ds) {
            $ids[$ds->story_id] = $ds->title_story;
        }


        $storiesjson = json_encode($stories,true);
        /*$storiesjson=null;*/
       $form['selected_data'] = array(
            '#type' => 'select',
            '#title' => t(''),
            '#options' => $ids,
            '#attributes' => array( 
                'onchange' => 'loadStory('.$storiesjson.')','style' => 'width: 50%;float: right;
            position: absolute;
            margin-top: -35px;
            margin-left: 30%;', 
                        'id' => ['selected_data'])
                );

        }
        

   
        $contentstories = json_encode($stories);

        

        $slide = "";


        $generalTitle ='<div class="text-center">
                            <h2 style="margin-top:20px !important;" class="section-heading text-uppercase" >Histoire de données</h2>
          </div>';

        $contentDiv="";
        foreach ($stories as $key => $story) {

            $titleStories='<div class="text-center">
                            <h2 style="margin-top:20px !important;" class="section-heading text-uppercase" >'.$story->title_story.'</h2>
            </div>';

            $contentwidget ='<div class="slideshow-container " id="slides-'.$key.'">';
            $widgets = $api->getWidgetByStory($story->story_id);
            $indocators ='<div style="text-align:center; margin-top: -20px !important;">';
    
            foreach ($widgets as $keywidget => $widget) {
                
                $contentwidget.='
                <div class="mySlides " data-index = "1" data-key = '.$keywidget.'>
                    <a href ="#" target="_blank">
                        <iframe id="iframejeu-'.$keywidget.'" src="'.$widget->widget.'" frameBorder="0" width = 100% height =645></iframe> 

                        <div class="text">'.$widget->widget_label.'</div>
                    </a>
                </div>';

                $indocators.='
                <img class="dot" onclick="currentSlide('.$key.','.$keywidget.')" src ="'.$widget->image.'" /> ';
            }

            
            $contentwidget.='</div>';
            $indocators .='</div>';

            $form['content-stories2'][] = array(
          'example one' => [
          '#type' => 'inline_template',
          '#template' =>'<div class="slidescontent" data-id = '.$key.' id="slidesContent-'.$key.'">'. $titleStories.'<br>
          '.$contentwidget.'

            <br>
            <div>
            <a class="prev" style="float:left; margin-top:-500px !important;margin-left: 100px;" onclick="plusSlides('.$key.',-1)">&#10094;</a>
            <a class="next" id="next" data-scrolltime ='.$story->scroll_time.' style="float:right; margin-top:-500px !important;margin-right: 100px;" onclick="plusSlides('.$key.',1)">&#10095;</a>
            </div>
            <br>
            '. $indocators.'

            <br>
            </div></div>'
          
        ],
    );
        $form['m2_2'] = array(
          '#markup' => '</div>',
        );

        }

      
/*        for ($i=0; $i <3 ; $i++) { 
        $contentwidget ='<div class="slideshow-container" id="slides">';
        $indocators ='<div style="text-align:center; margin-top: -20px !important;">';
        foreach ($stories as $key => $value) {
            $contentwidget.='
            <div class="mySlides " data-index = "1">
                <a href ="#" target="_blank">
                <iframe id="iframejeu" src="'.$value->widget.'" frameBorder="0" width = 100% height =645></iframe> 

                  <div class="text">'.$value->widget_label.'</div></a>
                </div>';

            $indocators.='
          <img class="dot" onclick="currentSlide('.$key.')" src ="'.$value->image.'" /> ';
        }

        $contentwidget.="</div>";
        $indocators.="</div>";


        $form['content-stories2'][] = array(
        'example one' => [
          '#type' => 'inline_template',
          '#template' => '
         <div class="text-center">
                            <h2 style="margin-top:20px !important;" class="section-heading text-uppercase" >Histoire de données</h2>
          </div>
          '.$contentwidget.'

            <br>
            <div>
            <a class="prev" style="float:left; margin-top:-500px !important;margin-left: 100px;" onclick="plusSlides(-1)">&#10094;</a>
            <a class="next" id="next" style="float:right; margin-top:-500px !important;margin-right: 100px;" onclick="plusSlides(1)">&#10095;</a>
            </div>
            <br>
            '. $indocators.'

    </div>'
          
        ],
    );
        $form['m2_2'] = array(
          '#markup' => '</div>',
        ); 

        }*/






        $form['m2'] = array(
            '#markup' => '<div id="visibilityModalStory">',
        ); 

        $form['story_title'] = array(
            '#markup' => '',
            '#type' => 'textfield',
            '#title' => $this->t('*Titre:'),
                '#attributes' => array('style' => 'width: 50%;'),
        );

         $form['scroll_tps'] = array(
            '#markup' => '',
            '#type' => 'textfield',
            '#title' => $this->t('*Temps de défilement :'),
                '#attributes' => array('style' => 'width: 50%;'),
        );

        $form['table_widgets'] = array(
            
            //'#prefix' =>'<div id="ConfigurationTab">',
            '#type' => 'table',
            '#header' => array(
                $this->t('label_widget'),
                $this->t('img_widget'),
                $this->t('Widget/URL'),
                $this->t('Supprimer')  
            ),
            //'#suffix' => '</div>',

        );
                for ($i = 1; $i <= 1; $i++) {
//titre
            $form['table_widgets'][$i]['label_widget'] = array(
                '#type' => 'textfield',
                '#size' => 30,
                '#maxlength' => null,
            );
//description
            $form['table_widgets'][$i]['img_widget'] = array(
                '#type' => 'managed_file',
                '#title' => t('Image de l\'histoire  :'),
                '#upload_location' => 'public://organization/',
                '#upload_validators' => array(
                    'file_validate_extensions' => array('png jpeg jpg svg gif WebP PNG JPEG JPG SVG GIF'),
                ),
                '#size' => 22,
            );


            
            $form['table_widgets'][$i]['widget'] = array(
            '#type' => 'textarea',
                '#attributes' => array('style' => 'height: 5em;width: 25em;'),
                '#maxlength' => null,

        );
            
            $form['table_widgets'][$i]['del'] = array(
            //'#type' => 'textarea',
        );
            
        }
        
        $form['id_story'] = array(
                '#type' => 'textfield',
                '#maxlength' => null,
                '#attributes' => array('style' => 'display: none;'),

            );
/*        $form['scroll_tps'] = array(
            '#markup' => '',
            '#type' => 'textfield',
            '#title' => $this->t('*Temps de défilement :'),
                '#attributes' => array('style' => 'width: 50%;'),
        );

        $form['img_widget'] = array(
            '#type' => 'managed_file',
            '#title' => t('Image de l\'histoire  :'),
            '#upload_location' => 'public://organization/',
            '#upload_validators' => array(
                'file_validate_extensions' => array('png jpeg jpg svg gif WebP PNG JPEG JPG SVG GIF'),
            ),
            '#size' => 22,
        );
        
        $form['label_widget'] = array(
            '#markup' => '',
            '#type' => 'textfield',
            '#title' => $this->t('*Label de widget :'),
                '#attributes' => array('style' => 'width: 50%;'),
        ); 

        $form['widget'] = array(
            '#type' => 'textarea',
            '#title' => $this->t('WIDGET :'),
            '#resizable' => true,
            '#attributes' => array('style' => 'width: 50%;'),
        ); 
*/

        $form['valider'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Valider'),
        );

        $form['button_del_story'] = array(
            '#type' => 'submit',
            '#name' => 'button_del_story_name',
            '#value' => $this->t('Supprimer'),
            '#attributes' => array('style' => 'color: #fcfcfa; background:#e1070799;position:absolute; margin-left:50%; margin-top:-31px'),
        );

        $form['del_story'] = array(
                '#type' => 'checkbox',
                '#attributes' => array('style' => 'display: none;'),
            );
        

        $form['m1_2'] = array(
          '#markup' => '</div>',
        ); 


        return $form;
    }
    


    public function submitForm(array &$form, FormStateInterface $form_state){
        
        $api = new Api;
        $stories = $api->getStories();

        $this->urlCkan = $this->config->ckan->url;
        $scrolling_time = $form_state->getValue('scroll_tps');
        $title_story = $form_state->getValue('story_title');
        $widget = $form_state->getValue('widget');
        $label_widget = $form_state->getValue('label_widget');
        $form_file = $form_state->getValue('img_widget');
        $idStroy = $form_state->getValue('id_story');
        $widget = $form_state->getValue('table_widgets');



        $data =array();
        $data["title_story"]=$title_story;
        $data["scrolling_time"]=$scrolling_time;
       
       /*echo "<pre>";*/
       
         /*$data["widget"]=$widget;
        $data["label_widget"]=$label_widget;*/
              
        foreach ($widget as $key => $value) {
          
            if (isset($value["img_widget"][0]) && !empty($value["img_widget"][0])) {

                $file = File::load($value["img_widget"][0]);
                $file->setPermanent();
                $file->save();
                $file->url();
                $value["img_widget"][0]= $file->url();
                $widget[$key]["urlimg"] = $file->url();

            }
        else {
            $value["img_widget"] = "http://kmo.data4citizen.com/sites/default/files/organization/img_v3.jpg";
            $widget[$key]["urlimg"] = "http://kmo.data4citizen.com/sites/default/files/organization/img_v3.jpg";
        }
       
  
        }
        $data["widget"] = $widget;

        $del_story = $form_state->getValue('del_story');


        if($idStroy != null ) {
            $data["story_id"]=$idStroy;
            if($del_story==true){
                $api->deleteStory($idStroy);
            }
            else {
                $api->updateStory($data);
            }
            


        }else {
/*            var_dump($data);die;
*/            $api->addStory($data);
            
        }

        header("Refresh:0");
        //error_log(json_encode($output . $code));
    }
    

    

}
