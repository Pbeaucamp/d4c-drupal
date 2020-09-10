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

class organizationsManagementForm extends HelpFormBase
{
    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'organizationsManagementForm';
    }

    /**
     * {@inheritdoc}
     */

    public function buildForm(array $form, FormStateInterface $form_state){
        $form = parent::buildForm($form, $form_state);

        $form['#attached']['library'][] = 'ckan_admin/organizationsManagementForm.form';
        
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

		$organizationList = array();
		$organizationList["new"] = "Créer une organisation";
        for ($i = 0; $i < count($orgs[result]); $i++) {
            $organizationList[$orgs[result][$i][id]] = $orgs[result][$i][display_name];
        }
        
		$form['selected_org'] = array(
           
            '#type' => 'select',
            '#title' => t('*Organisation :'),
            '#options' => $organizationList,
            '#attributes' => array('onchange' => 'addData('.$orgsData.')',
				'style' => 'width: 50%;'),
        ); 
        
		$form['title'] = array(
            '#markup' => '',
            '#type' => 'textfield',
            '#title' => $this->t('*Titre :'),
				'#attributes' => array('style' => 'width: 50%;'),
        );
            
		$form['description'] = array(
            '#type' => 'textarea',
            '#title' => $this->t('Description :'),
            '#resizable' => true,
            '#attributes' => array('style' => 'width: 50%;'),
        );    
        
		$form['img_org'] = array(
			'#type' => 'managed_file',
			'#title' => t('Logo de l’organisation  :'),
			'#upload_location' => 'public://organization/',
			'#upload_validators' => array(
				'file_validate_extensions' => array('png jpeg jpg svg gif WebP PNG JPEG JPG SVG GIF'),
			),
			'#size' => 22,
		);

		$form['selected_private'] = array(
            '#type' => 'select',
            '#title' => t('*Visibilité :'),
            '#options' => array('Privée', 'Publique'),
            '#attributes' => array('style' => 'width: 50%;'),
        );
        
        $form['valider'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Valider'),
        );
		
		$form['delete'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Supprimer'),
			'#submit' => array('::deleteOrga'),
        );

        $form['marque_blanche'] = array(
            '#markup' => '',
            '#type' => 'textarea',
            '#title' => t('Widget Marque Blanche:'),
        );

        return $form;
    }
	
	public function deleteOrga(array &$form, FormStateInterface $form_state){
		$api = new Api;
		$this->urlCkan = $this->config->ckan->url;
		$selected_org = $form_state->getValue('selected_org');		
		for ($i = 0; $i < count($this->orgas[result]); $i++) {
			if($this->orgas[result][$i][id] == $selected_org) {
				$this->org = $this->orgas[result][$i];
				break;
			}
        }
		
		if($this->org[package_count] > 0) {
			drupal_set_message('Cette organisation contient des jeux de données. Ils doivent être supprimés avant de pouvoir supprimer cette organisation.','error');
		}
		else {
			$context[id]=$this->org[id];
			$callUrlUpdate = $this->urlCkan . "/api/action/organization_delete";
			$return = $api->updateRequest($callUrlUpdate, $context, "POST");
		}
		
	}

    public function submitForm(array &$form, FormStateInterface $form_state){
        
        $api = new Api;
        $this->urlCkan = $this->config->ckan->url;
        $selected_org = $form_state->getValue('selected_org');
        $title = $form_state->getValue('title');
        $description = $form_state->getValue('description');
        $selected_private = $form_state->getValue('selected_private');
        $form_file = $form_state->getValue('img_org');
        var_dump($form_file);die;
        $private=$form_state->getValue('selected_private');
        
        if ($private == '0') {
            $private = true;
        } 
        else {
            $private = false;
        }
        
        $name = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $title)));
        $name = str_replace(" ", "_", $name);
        $name = strtolower($name);
        $name = htmlentities($name, ENT_NOQUOTES, $charset);
	    $name = preg_replace('#\&([A-za-z])(?:acute|cedil|circ|grave|ring|tilde|uml)\;#', '\1', $name);
	    $name = preg_replace('#\&([A-za-z]{2})(?:lig)\;#', '\1', $name); // pour les ligatures e.g. '&oelig;'
	    $name = preg_replace('#\&[^;]+\;#', '', $name); // supprime les autres caractères
	    $name = preg_replace('@[^a-zA-Z0-9_]@','',$name);
        
        $extras=array();
        
        array_push($extras,['key'=>'private', 'value'=>$private]);
        
        $context =[
			'name'=>$name,
			'title'=>$title,
			'description'=>$description,
			'state'=>'active',//'active'/ 'deleted' /draft
			'extras'=>$extras,
			'packages'=>array(),
			'users'=>array()
		];
        
        if($selected_org=='new'){
            
            if (isset($form_file[0]) && !empty($form_file[0])) {
				$file = File::load($form_file[0]);
				$file->setPermanent();
				$file->save();
				$file->url();
				$context[image_url]= $file->url();
			}
            
            $callUrlCreate = $this->urlCkan . "/api/action/organization_create";
            $return = $api->updateRequest($callUrlCreate, $context, "POST");
            $return = json_decode($return, true);
            
            if ($return[success] == true) {
				drupal_set_message('Les données ont été sauvegardées');
			}
            else {
				drupal_set_message(t('les données n`ont pas été ajoutées! '.$return[error][name][0]), 'error');
                $context =[
					'id'=>$name,
					'state'=>'active',//'active'/ 'deleted' /draft
				];
            
				$callUrlUpdate = $this->urlCkan . "/api/action/organization_update";
				$return = $api->updateRequest($callUrlUpdate, $context, "POST");
				//drupal_set_message(print_r($return));    
			}
            
            //drupal_set_message(print_r($return, true));
            
        }
        else{
            $context[id]=$selected_org;
            
            if (isset($form_file[0]) && !empty($form_file[0])) {
				$file = File::load($form_file[0]);
				$file->setPermanent();
				$file->save();
				$url_t = parse_url($file->url());
				$url_pict = $url_t["path"];
				$context[image_url]=$file->url();
			}
            
            $callUrlUpdate = $this->urlCkan . "/api/action/organization_update";
            $return = $api->updateRequest($callUrlUpdate, $context, "POST");
            $return = json_decode($return, true);
            //drupal_set_message($return[success]); 
            if ($return[success] == true) {
				drupal_set_message('Les données ont été sauvegardées');
			}
            else {
				drupal_set_message(t('les données n`ont pas été ajoutées!'), 'error');
			}
          
        }
		exec("/usr/lib/ckan/default/bin/paster --plugin=ckan search-index rebuild -c /etc/ckan/default/production.ini > /dev/null &", $output, $code);
		//error_log(json_encode($output . $code));
    }
    
    public function validateForm(array &$form, FormStateInterface $form_state) {
        
        $title = $form_state->getValue('title');
        
        if ($title == '') {
            $form_state->setErrorByName('title', $this->t('Titre'));
        }
        
	}
    

}
