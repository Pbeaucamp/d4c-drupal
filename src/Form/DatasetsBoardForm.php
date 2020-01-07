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
				$qo = 'organization:"'.$_GET["orga"].'"%20AND%20';
			}
			if($_GET["q"] != ""){
				$qs = 'text:"*'.strtolower($_GET["q"]).'*"%20AND%20';
			}
			if($_GET["type"] != ""){
				$qt = $_GET["type"] == "private" ?  'private:"true"%20AND%20' : 'private:"false"%20AND%20';
			}
			$filterQuery .= $qo . $qs . $qt;
			if(strlen($filterQuery) > 9){
				$filterQuery = substr($filterQuery, 0, -9);
			}
			
		}
        //rows=10&start=0&q=organization:ariam-idf%20AND%20text:*de*
		$query = 'include_private=true&rows='.$num_per_page.'&sort=title_string%20asc&start='.$offset.$filterQuery;
		//drupal_set_message($query);
        $result = $api->callPackageSearch_public_private($query);
							   
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
			"edit" => $this->t(''),
			"view" => $this->t(''),    
		);
		$output = array();
		foreach ($datasets as $row) {
			$output[] = [
				'name' => array('data' => array('#markup' => $row["title"])),
				'orga' => array('data' => array('#markup' => $row["organization"]["title"])),
				'last_modif' => array('data' => array('#markup' => date('Y-m-d H:i:s', strtotime($row["metadata_modified"])))),
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
				'edit' => array('data' => new FormattableMarkup('<a href=":link" class="button" style="border-radius: 10px;font-size: 11px;" target="_blank">@name</a>', 
					[':link' => "/admin/config/data4citizen/editMetaDataForm?id=".$row["id"], 
					'@name' => $this->t('Editer')])
				),
				'view' => array('data' => new FormattableMarkup('<a href=":link" class="button" style="border-radius: 10px;font-size: 11px;" target="_blank">@name</a>', 
					[':link' => "/visualisation/?id=".$row["name"], 
					'@name' => ($row["private"] ? $this->t('Prévisualiser') : $this->t('Visualiser'))])
				),
			];
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
		  '#parameters' => array("key" => "hhh"),
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
		
		$form['modal'] = array(
			'#markup' => '<div id="formModal"></div>',
		);   
		
		$form['search'] = array(
			'#type' => 'submit',
			'#value' => $this->t('Envoyer'),
			'#attributes' => array(
				'style'=>'display:none;'
			),
		);
	
		return $form;
	}
    
	public function submitForm(array &$form, FormStateInterface $form_state){ 
        
		$this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
        $this->urlCkan = $this->config->ckan->url;
        $api = new Api();
		
		$visibility = $form_state->getValue("selected_type");
		$id = $form_state->getValue("selected_id");
		//drupal_set_message($visibility ." " . $id);
		
		
		$res = $api->getPackageShow("id=".$id);
		$oldDataset = $res["result"];
		
		$oldDataset["private"] = ($visibility == "private" ? true : false);
		
		$callUrl = $this->urlCkan . "/api/action/package_update";
		$return = $api->updateRequest($callUrl, $oldDataset, "POST");
   
		drupal_set_message('Le jeu de données '. $oldDataset["title"] . ' a été rendu '. ($visibility == "private" ? "Privé" : "Public"));
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