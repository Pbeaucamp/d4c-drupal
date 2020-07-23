<?php
/**
 * @file
* Contains \Drupal\search_api_solr_admin\Form\QueryForm.
*/

namespace Drupal\ckan_admin\Form;

use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\Query;
use Drupal\ckan_admin\Utils\HelpFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use \PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use \PhpOffice\PhpSpreadsheet\Reader\Xls;
use \PhpOffice\PhpSpreadsheet\Writer\Csv;;
use Drupal\ckan_admin\Utils\DataSet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Drupal\Component\Render\FormattableMarkup; 
	

/**
 * Implements an example form.
 */
class DatasetsBoardForm extends HelpFormBase {
	

	/**
	 * {@inheritdoc}
	 */
	public function getFormId() {
		return 'DatasetsBoardForm';
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
        $form['#attached']['library'][] = 'ckan_admin/datasetsBoardForm.form';
		
		$form = parent::buildForm($form, $form_state);
        
		$this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
        $this->urlCkan = $this->config->ckan->url;
        $api = new Api();
		
		$isAdmin = false;
		$current_user = \Drupal::currentUser();
		if(in_array("administrator", $current_user->getRoles())){
			$isAdmin = true;
		}

		
		
		
        $option_org=array();
		
		$page = pager_find_page();
		$num_per_page = 10;
		$offset = $num_per_page * $page;
		
		$filterQuery = "";
		if ($_GET["orga"] != "" || $_GET["q"] != "" || $_GET["type"] != "") {
			//drupal_set_message(json_encode($form_state->getStorage()));
			//$store = $form_state->getStorage();
			
			$filterQuery = "&q=";
			$qo = "";
			$qt = "";
			$qs = "";
			if($_GET["orga"] != ""){
				$qo = 'organization:"'.$_GET["orga"].'" AND ';
			}
			if($_GET["q"] != ""){
				$qs = 'text:"*'.strtolower($_GET["q"]).'*" AND ';
			}
			if($_GET["type"] != ""){
				$qt = $_GET["type"] == "private" ?  'private:"true" AND ' : 'private:"false" AND ';
			}
			$filterQuery .= $qo . $qs . $qt;
			if(strlen($filterQuery) > 5){
				$filterQuery = substr($filterQuery, 0, -5);
			}
			
		}
        //rows=10&start=0&q=organization:ariam-idf%20AND%20text:*de*
		$query = 'include_private=true&rows='.$num_per_page.'&start='.$offset.$filterQuery;
		// drupal_set_message($query);
		//error_log($query);
        $result = $api->callPackageSearch_public_private($query, $current_user->id());
							   
     //drupal_set_message($result); 
        $result = $result->getContent();
		
        $result = json_decode($result, true)[result];
        $datasets = $result[results];
		
		pager_default_initialize($result["count"], $num_per_page);
		
		$header =  array(
			"name" => $this->t('Nom'),
			"orga" => $this->t('Organisation'),
			"last_modif" => $this->t("Dernière Modification"),
			"display" => $this->t("Visibilité"),
			"datapusher" => $this->t(''),
			"edit" => $this->t(''),
			"view" => $this->t(''),    
		);
		if($isAdmin == true){
			array_splice($header, 4, 0, array("security" => $this->t("Sécurité")) );
		}
		
		$output = array();
		foreach ($datasets as $row) {
			
			//$default_chart
			// drupal_set_message(json_encode($row[extras]));
			 for ($j = 0; $j < count($row[extras]); $j++) {
				 if ($row[extras][$j]['key'] == 'analyse_default') {
					 $default_chart = $row[extras][$j]['value'];
					 break;
				 }
			 }
			$viewLink = "/visualisation/?id=".$row["name"];
			if(isset($default_chart)) {
				$viewLink = $viewLink . '&' . $default_chart;
			}

			$saveTimeZone = date_default_timezone_get();
			date_default_timezone_set('Europe/Paris');

			// drupal_set_message($viewLink);
			$uirow = [
				'name' => array('data' => array('#markup' => $row["title"])),
				'orga' => array('data' => array('#markup' => $row["organization"]["title"])),
				'last_modif' => array('data' => array('#markup' => date('Y-m-d H:i:s', strtotime($row["metadata_modified"] . " UTC")))),
				'display' => array('data' => array(
					'#type' => 'select',     
					'#options' => array('private'=>'Privé', 'public'=>'Public'),    
					'#value' => ($row["private"] ? "private" : "public") ,
					'#attributes' => array(
						'onchange' => 'confirm(event)', 
						'data-id' => $row["name"]
					)
				)),
				//$row["name"],
				
				
				
				'datapusher' => array('data' => new FormattableMarkup('<a href=":link" class="button" style="border-radius: 10px;font-size: 11px;">@name</a>', 
					[':link' => "/admin/config/data4citizen/datasetsManagement/datasetDatapusher?datasetId=".$row["id"], 
					'@name' => $this->t('Datapusher')])
				),
				'edit' => array('data' => new FormattableMarkup('<a href=":link" class="button" style="border-radius: 10px;font-size: 11px;" target="_blank">@name</a>', 
					[':link' => "/admin/config/data4citizen/editMetaDataForm?id=".$row["id"], 
					'@name' => $this->t('Editer')])
				),
				'view' => array('data' => new FormattableMarkup('<a data-toggle="modal" data-target="#myModal" href=":link" class="button" style="border-radius: 10px;font-size: 11px;" target="_blank">@name</a>', 
					[':link' => $viewLink, 
					'@name' => ($row["private"] ? $this->t('Prévisualiser') : $this->t('Visualiser'))])
				),
			];
			
			date_default_timezone_set($saveTimeZone);

			if($isAdmin == true){
				$dataSecurity = "";
				foreach($row["extras"] as $ext){
					if($ext['key'] == 'edition_security') {
						$dataSecurity = str_replace("*", "", $ext['value']);
						$dataSecurity = base64_encode($dataSecurity);
						break;
					}
				}
				array_splice($uirow, 4, 0, array(
											'data' => new FormattableMarkup('<input type="button" onclick=":action" class="button" style="border-radius: 10px;font-size: 11px;padding: 4px 5px;" value=":name" data-id=":id" data-security=":sec"/>', 
											[':action' => "openecurityPopup(event)", 
											':name' => $this->t('Gestion des droits'),
											':sec' => $dataSecurity,
											':id' => $row["name"]])
										));
			}
			
			$output[] = $uirow;
		}
        
        $orgas = $api->getAllOrganisations();
	
        foreach ($orgas as $value) {
            $option_org[$value["name"]] = $value["display_name"];
        }
		//$form['#method'] = 'get';
		$form['top'] = [
			'#type'  => 'container',
			'#attributes' => array(
				'style' => "height:37px;display:block",
			)
		];
		
		$form['filters'] = [
			'#type'  => 'details',
			'#title' => t('Filtres'),
			'#open'  => true,
			/*'#attributes' => array(
				'style' => "padding:18px;",
			),*/
		];
		
		$form['filters']['selected_org'] = array(
			//'#prefix' => '<div class="container-inline">',
            '#type' => 'select',
            '#title' => t('Organisation :'),
            '#options' => $option_org,
            '#empty_option' => t('----'),          
			'#attributes' => array(
				'style' => "display: inline-block;width: 50%;",
			),
			'#default_value' => $_GET["orga"]
        );

		$form['filters']['selected_text'] = [
			'#title' => t('Recherche :'),
			'#type' => 'search',
			'#attributes' => array(
				'style' => "display: inline-block;width: 50%;",
			),
			'#default_value' => $_GET["q"]
		];

		$form['filters']['selected_vis'] = array(
            '#type' => 'select',
            '#title' => t('Visibilité :'),
            '#options' => array('private'=>'Privé', 'public'=>'Public'),
            '#empty_option' => t('Tous'),  
			'#attributes' => array(
				'style' => "display: inline-block;width: 50%;",
			),
			'#default_value' => $_GET["type"]
			//'#suffix' => '</div>',
        );

		$form['filters']['actions'] = [
			'#type'       => 'actions'
		];

		$form['filters']['actions']['filter'] = [
			'#type'  => 'submit',
			'#value' => $this->t('Filter'),
			'#submit' => array([$this, 'submitfiltering'])
		];
        
        $form['filters']['actions']['clear'] = [
			'#type'  => 'submit',
			'#value' => $this->t('Effacer'),
			'#submit' => array([$this, 'submitclear'])
		];
		
		$form['table'] = array(
			'#type' => 'table',
			'#header' => $header,
			'#rows' => $output,
		);

		$form['pager'] = [
		  '#type' => 'pager',
		  //'#parameters' => array("key" => "hhh"),
		  '#tags' => array(t('« Première page'), t('‹ Page précédente'),"", t('Page suivante ›'), t('Dernière page »')),
		  '#submit' => array([$this, 'submitfiltering'])
		];
		
		$form['selected_type'] = array(
            '#type' => 'textfield',
            '#attributes' => array(
				'style'=>'display:none;'
			),  
        );
		
		$form['selected_id'] = array(
            '#type' => 'textfield',
            '#attributes' => array(
				'style'=>'display:none;'
			),  
        );
		
		$form['selected_users'] = array(
            '#type' => 'textfield',
            '#attributes' => array(
				'style'=>'display:none;'
			),  
        );
		
		$form['modal'] = array(
			'#markup' => '<div id="visibilityModal"></div>',
		); 


		$form['modalSecurity'] = array(
			'#markup' => '<div id="securityModal"></div>',
		);		
		
		$form['search'] = array(
			'#type' => 'submit',
			'#value' => $this->t('Envoyer'),
			'#attributes' => array(
				'style'=>'display:none;'
			),
		);
		
		
		//security popup
		$form['security'] = [
			'#type'  => 'container',
			'#attributes' => array(
				'style' => "display:none;"
			)
		];
		$form['security']['roles'] = [
			'#type'  => 'details',
			'#title' => t('Groupes'),
			'#open'  => false,
			/*'#attributes' => array(
				'style' => "padding:18px;",
			),*/
		];
		
		$roles = user_role_names();
		//drupal_set_message(json_encode($roles));
		unset($roles["anonymous"]);
		unset($roles["authenticated"]);
		$form['security']['roles']['roles_list'] = array(
			'#type'          => 'checkboxes',
		 // '#default_value' => $settings['tynt_roles'],
			'#options'       => $roles
		);
		
		$form['security']['users'] = [
			'#type'  => 'details',
			'#title' => t('Utilisateurs'),
			'#open'  => true
		];
		
		$users = \Drupal\user\Entity\User::loadMultiple();
		$userlist = array();
		$userListComplete = array();
		foreach($users as $user){
			$username = $user->get('name')->value;
			$uid = $user->get('uid')->value;
			$uroles = $user->getRoles();
			if($username != ""){
				$userlist[$uid] = $username;
				$userListComplete[$uid] = array("id" => $uid, "name" => $username, "roles" => $uroles);
			}
		}
		//drupal_set_message(json_encode($userListComplete));
		$form['security']['users']['users_list'] = array(
			'#type'          => 'checkboxes',
			// '#default_value' => $settings['tynt_roles'],
			'#options'       => $userlist
		);
		
		// drupal_add_js('$(document).ready(function(){
                       // $("#edit-users-list-1").attr("disabled", "disabled");
                    // });', 'inline');

		
		
		$form['#attached']['drupalSettings']['users'] = json_encode($userListComplete);
		// drupal_set_message('eee' . $current_user->id());
		$form['#attached']['drupalSettings']['currentuser'] = $current_user->id();
		return $form;
	}
    
	public function submitForm(array &$form, FormStateInterface $form_state){ 
        
		$this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
        $this->urlCkan = $this->config->ckan->url;
        $api = new Api();
		
		$visibility = $form_state->getValue("selected_type");
		$security = $form_state->getValue("selected_users");
		$id = $form_state->getValue("selected_id");
		//drupal_set_message($visibility ." " . $id);
		
		
		$res = $api->getPackageShow("id=".$id);
		$oldDataset = $res["result"];
		
		if($visibility != ""){
			$oldDataset["private"] = ($visibility == "private" ? true : false);
			
			drupal_set_message('Le jeu de données '. $oldDataset["title"] . ' a été rendu '. ($visibility == "private" ? "Privé" : "Public"));
		} 
		if($security != ""){
			$exists = false;
			foreach($oldDataset["extras"] as &$ext){
				if($ext['key'] == 'edition_security') {
					$ext['value'] = $security;
					//error_log('extras found');
					$exists = true;
					break;
				}
			}
			if(!$exists) {
				//error_log('extras added');
				$oldDataset["extras"][count($oldDataset["extras"])]['key'] = 'edition_security';
				$oldDataset["extras"][(count($oldDataset["extras"]) - 1)]['value'] = $security;
			}
			
			drupal_set_message('La sécurité sur le jeu de données '. $oldDataset["title"] . ' a été modifiée');
		}
		
		$callUrl = $this->urlCkan . "/api/action/package_update";
		$return = $api->updateRequest($callUrl, $oldDataset, "POST");
   
	}
	
	
	public function submitfiltering(array &$form, FormStateInterface $form_state){ 
		// Set the provided filter value in the storage.
		$filters = array();
		$filters["orga"] = $form_state->getValue("selected_org");
		$filters["q"] = $form_state->getValue("selected_text");
		$filters["type"] = $form_state->getValue("selected_vis");
		
		//$form_state->setStorage($filters);
		//$form_state->setRebuild(TRUE);
		$url = Url::fromRoute('ckan_admin.datasets_dashboard', [], ['query' => ["page" => 0, 'orga' => $filters["orga"], 'q' => $filters["q"], 'type' => $filters["type"]]]);
		$form_state->setRedirectUrl($url);
	}
	
	public function submitclear(array &$form, FormStateInterface $form_state){ 
		// Set the provided filter value in the storage.
		$filters = array();
		$filters["orga"] = "";
		$filters["q"] = "";
		$filters["type"] = "";
		
		//$form_state->setStorage($filters);
		//$form_state->setRebuild(False);
		$url = Url::fromRoute('ckan_admin.datasets_dashboard', [], ['query' => ["page" => 0, 'orga' => $filters["orga"], 'q' => $filters["q"], 'type' => $filters["type"]]]);
		$form_state->setRedirectUrl($url);
	}
}