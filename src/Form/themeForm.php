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

use Drupal\file\Entity\File;

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



		//drupal_get_messages('error');
     
class themeForm extends HelpFormBase {

	/**
	 * {@inheritdoc}
	 */
	public function getFormId() {
		return 'themeForm';
	}

	/**
	 * {@inheritdoc}
	 */
    
	public function buildForm(array $form, FormStateInterface $form_state) {
        $form = parent::buildForm($form, $form_state);
         
        $config = \Drupal::service('config.factory')->getEditable('ckan_admin.themeForm');
        $form['#attached']['library'][] = 'ckan_admin/theme.form';
        
        
        if($config->get('themes')==null ){
           
            $themes_defolt = array(
				array("title"=>"administration-gouvernement-finances-publiques-citoyennete", "label"=>"Administration gouvernement finances publiques citoyennete", "url"=>"/sites/default/files/api/portail_d4c/img/theme-administration-gouvernement-finances-publiques-citoyennete.svg"),
                     
				array( "title"=>"amenagement-du-territoire-urbanisme-batiments-equipements-logement", "label"=>"Amenagement du territoire urbanisme batiments equipements logement","url"=>"/sites/default/files/api/portail_d4c/img/theme-amenagement-du-territoire-urbanisme-batiments-equipements-logement.svg"),
                     
				array("title"=>"culture-patrimoine","label"=>"Culture patrimoine","url"=>"/sites/default/files/api/portail_d4c/img/theme-culture-patrimoine.svg"),
                     
				array("title"=>"economie-business-pme-developpement-economique-emploi","label"=>"Economie business pme developpement economique emploi","url"=>"/sites/default/files/api/portail_d4c/img/theme-economie-business-pme-developpement-economique-emploi.svg"),
                     
				array("title"=>"education-formation-recherche-enseignement","label"=>"Education formation recherche enseignement","url"=>"/sites/default/files/api/portail_d4c/img/theme-education-formation-recherche-enseignement.svg"),
                     
				array("title"=>"environnement","label"=>"Environnement","url"=>"/sites/default/files/api/portail_d4c/img/theme-environnement.svg"),
                     
				array("title"=>"services-sociaux","label"=>"Services sociaux","url"=>"/sites/default/files/api/portail_d4c/img/theme-services-social.svg"),
                     
				array("title"=>"transports-deplacements","label"=>"Transports deplacements","url"=>"/sites/default/files/api/portail_d4c/img/theme-transports-deplacements.svg"),
                     
				array("title"=>"default","label"=>"Default","url"=>"/sites/default/files/api/portail_d4c/img/theme-default.png"),
       
			);
            
			$config->set('themes',json_encode($themes_defolt))->save();
        }
        
		//$config->set('themes',null)->save();
		$t = $config->get('themes'); 
		$themes = json_decode($t);
        
        if(!$themes[0]->label || !$themes[1]->label || !$themes[2]->label || !$themes[3]->label || !$themes[4]->label || !$themes[5]->label || !$themes[6]->label || !$themes[7]->label || !$themes[8]->label){
            
            $themes_defolt = array();
            
            $themes_defolt[0]->label = "Administration gouvernement finances publiques citoyennete";
            $themes_defolt[0]->url = '/sites/default/files/api/portail_d4c/img/theme-administration-gouvernement-finances-publiques-citoyennete.svg';
            $themes_defolt[0]->title = 'administration-gouvernement-finances-publiques-citoyennete';
            
            $themes_defolt[1]->label = "Amenagement du territoire urbanisme batiments equipements logement";
            $themes_defolt[1]->url = '/sites/default/files/api/portail_d4c/img/theme-amenagement-du-territoire-urbanisme-batiments-equipements-logement.svg';
            $themes_defolt[1]->title = 'amenagement-du-territoire-urbanisme-batiments-equipements-logement';
            
            $themes_defolt[2]->title = 'culture-patrimoine';
            $themes_defolt[2]->label = "Culture patrimoine";
            $themes_defolt[2]->url = '/sites/default/files/api/portail_d4c/img/theme-culture-patrimoine.svg';
            
            $themes_defolt[3]->title = 'economie-business-pme-developpement-economique-emploi';
            $themes_defolt[3]->label = "Economie business pme developpement economique emploi";
            $themes_defolt[3]->url = '/sites/default/files/api/portail_d4c/img/theme-economie-business-pme-developpement-economique-emploi.svg';
            
            $themes_defolt[4]->title = 'education-formation-recherche-enseignement';
            $themes_defolt[4]->label = "Education formation recherche enseignement";
            $themes_defolt[4]->url = '/sites/default/files/api/portail_d4c/img/theme-education-formation-recherche-enseignement.svg';
            
            $themes_defolt[5]->title = 'environnement';
            $themes_defolt[5]->label = "Environnement";
            $themes_defolt[5]->url = '/sites/default/files/api/portail_d4c/img/theme-environnement.svg';
            
            $themes_defolt[6]->title = 'services-social';
            $themes_defolt[6]->label = "Services social";
            $themes_defolt[6]->url = '/sites/default/files/api/portail_d4c/img/theme-services-social.svg';
           
            $themes_defolt[7]->title = 'transports-deplacements';
            $themes_defolt[7]->label = "Transports deplacements";
            $themes_defolt[7]->url = '/sites/default/files/api/portail_d4c/img/theme-transports-deplacements.svg';
            
			$themes_defolt[8]->title = 'default';
            $themes_defolt[8]->label = "Default";
			$themes_defolt[8]->url = '/sites/default/files/api/portail_d4c/img/theme-default.png';
            
			for($i=9; $i<count($themes); $i++){
				if(!$themes[$i]->label){
					$themes[$i]->label=$themes[$i]->title;
					array_push($themes_defolt,$themes[$i]);
				}  
			} 
            
			$themes = $themes_defolt;
            //drupal_set_message('<pre>'. print_r($themes, true) .'</pre>');
            
            $config->set('themes',json_encode($themes))->save();
        }
        
		$valuesForSelect=array();   
		for($i=0; $i<count($themes); $i++){
			$valuesForSelect[$i."%".$themes[$i]->url."%".$themes[$i]->title]=$themes[$i]->label;  
		}
        
		$valuesForSelect["new_theme"]='Nouveau thème'; 
        
		
		$form['selected'] = array(
			'#type' => 'select',
			//'#title' => t('Selected'),
			'#options' => $valuesForSelect, 
			'#attributes' => array(
				'onchange' => 'getDataForUpt()'),
		);
        
		$form['theme'] = array(
			'#type' => 'textfield', 
			'#title' => $this->t('Nom:'),
				 
		);
        
		$form['img_theme'] = array(
			'#type' => 'managed_file',
			'#title' => t('Sélectionner une image:'),
			'#upload_location' => 'public://theme_logo/',
			'#upload_validators' => array(
				'file_validate_extensions' => array('jpeg png jpg svg gif WebP PNG JPG JPEG SVG GIF'),
			),
			'#size' => 22,
		); 
        
		$form['m1'] = array(
			'#markup' => '<div ng-app="d4c.frontend" id="app">
							<label for="app">ou un pictogramme : </label>
							<d4c-pictopicker ng-model="theme" default-color="#E5E5E5"></d4c-pictopicker>
							<!--<div id="btnImgHide"></div>-->
							<div id= "old_img"></div><br>
							<!--<div style=" overflow:scroll; height:15em; overflow-x: hidden; display: none;  width: 30%;" id="pickImg"></div>
							
						</div></br>
							<script type="text/javascript">
							
						</script>',
			'#allowed_tags' => ['label', 'div', 'd4c-pictopicker', 'br', 'script']
		);          
        
		$form['valider'] = array(
			'#type' => 'submit',
			'#value' => $this->t('Valider'),
		);
        
        //$directory = "sites/default/files/api/portail_d4c/img/set-v2/";
        //$images = glob($directory . "*.svg");
        //$imgs = '';
        
    
        //foreach ($images as $image) {
        //    $imgs = $imgs . ';' . $image;
        //}
    
     
		/*$form['imgimg'] = array(

            '#type' => 'textarea',
            '#attributes' => [
                'value' => $imgs,
                'style' => 'display: none',
            ],
            '#default_value' => $imgs,
        );*/
        
        $form['imgBack'] = array(
            '#type' => 'textfield',
            '#attributes' => [
                'style' => 'display: none',
            ],
        );
		
		return $form;
	}
    
	public function submitForm(array &$form, FormStateInterface $form_state){
		
        $config = \Drupal::service('config.factory')->getEditable('ckan_admin.themeForm');
        
        $t = $config->get('themes');
        
        $themes = json_decode($t);
        $selectThem = $form_state->getValue('selected');
        
        if($selectThem=="new_theme"){
            
			$themeValid = $form_state->getValue('theme');
			$theme_label = $themeValid;
            
			$themeValid = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $themeValid)));
			$themeValid = str_replace(" ", "_", $themeValid);
			$themeValid = strtolower($themeValid);
			$themeValid = htmlentities($themeValid, ENT_NOQUOTES, $charset);
			$themeValid = preg_replace('#\&([A-za-z])(?:acute|cedil|circ|grave|ring|tilde|uml)\;#', '\1', $themeValid);
			$themeValid = preg_replace('#\&([A-za-z]{2})(?:lig)\;#', '\1', $themeValid); // pour les ligatures e.g. '&oelig;'
			$themeValid = preg_replace('#\&[^;]+\;#', '', $themeValid); // supprime les autres caractères
			$themeValid = preg_replace('@[^a-zA-Z0-9_]@','',$themeValid);
           
            $themes[count($themes)]->title = $themeValid;  
            $themes[count($themes)-1]->label = $theme_label;  
            
			$form_file = $form_state->getValue('img_theme');
            
			if (isset($form_file[0]) && !empty($form_file[0])) {

				$file = File::load($form_file[0]);
				$file->setPermanent();
				$file->save();
				$url_t=parse_url($file->url());
				$themes[count($themes)-1]->url =$url_t["path"];
			
			}
			else if($form_state->getValue('imgBack')!=''|| $form_state->getValue('imgBack')!=null){
				$themes[count($themes)-1]->url = "/sites/default/files/api/portail_d4c/img/set-v3/pictos/".$form_state->getValue('imgBack').".svg";
            }
            else{
				$themes[count($themes)-1]->url ="/sites/default/files/api/portail_d4c/img/theme-default.png";
            }
            
            //drupal_set_message(print_r($themes, true));
            
            $config->set('themes',null)->save();
            $config->set('themes',json_encode($themes))->save();
            
            drupal_set_message('Les données ont été sauvegardées','status',false);
        }
        else{
			$selectThem = explode("%", $selectThem);
			$them_old =$selectThem[2];
			$selectThem = $selectThem[0];
          
            if($selectThem=='8'){
                 
				$themes[$selectThem]->title ='default';
				$themes[$selectThem]->label = "Default"; 
            
				$form_file = $form_state->getValue('img_theme', 0);
            
				if (isset($form_file[0]) && !empty($form_file[0])) {

					$file = File::load($form_file[0]);
					$file->setPermanent();
					$file->save();
					$url_t=parse_url($file->url());
					$themes[$selectThem]->url =$url_t["path"];
				}
            }
            else{
				$themeValid = $form_state->getValue('theme');
				$theme_label=$themeValid;
                
				$themeValid = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $themeValid)));
				$themeValid = str_replace(" ", "_", $themeValid);
				$themeValid = strtolower($themeValid);
				$themeValid = htmlentities($themeValid, ENT_NOQUOTES, $charset);
				$themeValid = preg_replace('#\&([A-za-z])(?:acute|cedil|circ|grave|ring|tilde|uml)\;#', '\1', $themeValid);
				$themeValid = preg_replace('#\&([A-za-z]{2})(?:lig)\;#', '\1', $themeValid); // pour les ligatures e.g. '&oelig;'
				$themeValid = preg_replace('#\&[^;]+\;#', '', $themeValid); // supprime les autres caractères
				$themeValid = preg_replace('@[^a-zA-Z0-9_]@','',$themeValid);
                
				$themes[$selectThem]->title =$themeValid;
				$themes[$selectThem]->label = $theme_label; 
            
				$form_file = $form_state->getValue('img_theme', 0);
            
                if (isset($form_file[0]) && !empty($form_file[0])) {

					$file = File::load($form_file[0]);
					$file->setPermanent();
					$file->save();
					$url_t=parse_url($file->url());
					$themes[$selectThem]->url =$url_t["path"];
                }
                else if($form_state->getValue('imgBack')!=''|| $form_state->getValue('imgBack')!=null){
                    $themes[$selectThem]->url = "/sites/default/files/api/portail_d4c/img/set-v3/pictos/".$form_state->getValue('imgBack').".svg";
                }
            
            }

            $config->set('themes',json_encode($themes))->save(); 
            drupal_set_message('Les données ont été sauvegardées','status',false);
            
			///// replace theme in dataset 
            
			$this->config = json_decode(file_get_contents(__DIR__ ."/../../config.json"));
			$this->urlCkan = $this->config->ckan->url; 
        
			$api = new Api;
		
			$dataSet= $api->callPackageSearch_public_private('rows=100000');
			
			$dataSet = $dataSet->getContent();
			$dataSet = json_decode($dataSet,true);
			$dataSet = $dataSet[result][results];
			$callUrl = $this->urlCkan . "/api/action/package_update";
        
			for($i=0; $i<count($dataSet); $i++){  
				$them_label_ex = false;
				if($dataSet[$i][extras]){
					$cout_extras =count($dataSet[$i][extras]);
					if($cout_extras!=0){
						for($j=0; $j<count($dataSet[$i][extras]); $j++){
							if($dataSet[$i][extras][$j]['key']=='theme' && $dataSet[$i][extras][$j]['value']==$them_old){
								$dataSet[$i][extras][$j]['value']=$themeValid;
								
								for($jj=0; $jj<count($dataSet[$i][extras]); $jj++){
									if($dataSet[$i][extras][$jj]['key']=='label_theme'){
										$them_label_ex = true;
										$dataSet[$i][extras][$jj]['value']=$theme_label;
									} 
								}
								$return = $api->updateRequest($callUrl,$dataSet[$i],"POST");

							}
						}
						if($them_label_ex==false){
							$dataSet[$i][extras][count($dataSet[$i][extras])]['key'] = 'label_theme';
							$dataSet[$i][extras][(count($dataSet[$i][extras])-1)]['value']=$theme_label;
						}
					}
				}								   
			}
        }
        
        
	}
    
    public function validateForm(array &$form, FormStateInterface $form_state) {
        
        $theme = $form_state->getValue('theme');
     
        if( $theme == '') $form_state->setErrorByName('theme', $this->t('Aucune donnée sélectionnée'));   
        
    }   
    
    
   
    

}


