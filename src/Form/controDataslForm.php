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
class controDataslForm extends HelpFormBase {


	/**
	 * {@inheritdoc}
	 */
	public function getFormId() {
		return 'controDataslForm';
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
        $form = parent::buildForm($form, $form_state);
        
        
        $form['#attached']['library'][] = 'ckan_admin/controDataslForm.form';
		$this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
        $this->urlCkan = $this->config->ckan->url;
        


      $config = \Drupal::service('config.factory')->getEditable('ckan_admin.control_dataset_form');
        $siteList = $config->get('siteList'); 
        $siteList2 = $siteList;
        $siteList = json_decode($siteList);
        
        
        $listUrl = array();
        
          $listUrl['new']='new';
        
        foreach($siteList as &$value){
            
           $listUrl[$value->url]=$value->url;
            
        }
        
        
       
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

        
$organizationList = array();
 
        for ($i = 0; $i < count($orgs[result]); $i++) {
            $organizationList[$orgs[result][$i][id]] = $orgs[result][$i][display_name];
        }        
        
        $optioons_site =array();
        					   
			$form['selected_site'] = array(
            '#type' => 'select',
            '#title' => t('Site :'),
            '#options' => $listUrl,
            '#attributes' => array('style' => 'width: 50%;', 'onchange'=>'fillData('.$siteList2.');'),
            
        );
        
        
        $form['url'] = array(
            '#markup' => '',
            '#type' => 'textfield',
           
             '#attributes' => array('style' => 'width: 50%;'),

        );
        
        
        
        
        $form['ids_org'] = array(
                '#prefix' => '<div id="idsDiv" >',
				'#type' => 'checkboxes',
				'#title' => $this->t('Choix des jeux de donnees'),
                '#suffix' => '</div>',
				'#options' => $organizationList,
		);
		
        
        $form['search'] = array(
				'#type' => 'submit',
				'#value' => $this->t('Envoyer'),
		);
        
       
     

		return $form;
	}
	public function submitForm(array &$form, FormStateInterface $form_state)
	{
        $config = \Drupal::service('config.factory')->getEditable('ckan_admin.control_dataset_form');
        $siteList = $config->get('siteList'); 
        $siteList = json_decode($siteList);
        if($siteList==null){
            $siteList=array();
        }
        $selected_site = $form_state->getValue('selected_site');
        $selected_url = $form_state->getValue('url');
        $selected_org = $form_state->getValue('ids_org');
        $org_id=array();
        
        
        
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

        
$organizationList = array();
 
        for ($i = 0; $i < count($orgs[result]); $i++) {
            $organizationList[$orgs[result][$i][id]] = $orgs[result][$i][id];
        }        
        
        
        foreach($selected_org as &$value){
            if($value!=0 || $value!=null){
                
                array_push($org_id,$value);
                
            } 
        }
        
        
        
        
         $org_id = \array_diff($organizationList, $org_id);
         $org_id = array_values($org_id);
        
       
        
        if($selected_site=='new'){
            
        $data['orgs']=$org_id;
        $data['url']=$selected_url;
        array_push($siteList,$data);
            
        }
        
        else{
            foreach($siteList as &$value){
                
                if($value->url==$selected_site){
               
                  $value->url = $selected_url;
                  $value->orgs = $org_id;
                    
                }
                
            }
            
            
        }
          
        
        $config->set('siteList', json_encode($siteList))->save();
        
        
		
	}
    
    
    
public function validateForm(array &$form, FormStateInterface $form_state) {
        
        $config = \Drupal::service('config.factory')->getEditable('ckan_admin.control_dataset_form');
        $siteList = $config->get('siteList'); 
        $siteList = json_decode($siteList);
        
        $selected_site = $form_state->getValue('selected_site');
        $selected_url = $form_state->getValue('url');
        $selected_org = $form_state->getValue('ids_org');
    
    
    if($selected_site == 'new'){
        
        foreach($siteList as &$value){
            
            if( $value->url == $selected_url){
                $form_state->setErrorByName('url', $this->t('URL existe deja'));
                
            }
            
        }
    }
    
    else if($selected_site != $selected_url){
        
         foreach($siteList as &$value){
            
            if( $value->url == $selected_url){
                
                $form_state->setErrorByName('url', $this->t('Site .........'));
            }
            
        }
        
        
    }
    
    else if($selected_url==''){
        
        $form_state->setErrorByName('url', $this->t('URL existe deja'));
        
    }
    
    
    
    
  
    
}
   
    
    

 
    

}