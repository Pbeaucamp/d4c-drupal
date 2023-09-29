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
class ReusesForm extends HelpFormBase {

	private $config;
	private $urlCkan;

	/**
	 * {@inheritdoc}
	 */
	public function getFormId() {
		return 'ReusesForm';
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
        $form['#attached']['library'][] = 'ckan_admin/reusesForm.form';
		
		$form = parent::buildForm($form, $form_state);
        
		$this->config = include(__DIR__ . "/../../config.php");
        $this->urlCkan = $this->config->ckan->url;
        $api = new Api();

        $option_org=array();
        $option_ds=array();
		
		$pager_parameters = \Drupal::service('pager.parameters');
  		$page = $pager_parameters->findPage(0);

		$num_per_page = 10;
		$offset = $num_per_page * $page;

		$orga = $_GET["orga"];
		$queryParam = $_GET["q"];
		$dataset = $_GET["dataset"];
		$status = $_GET["status"];

		if (strpos($queryParam, 'admin/config') !== false) {
			$queryParam = "";
		}

        $result = $api->getReuses($orga, $dataset, $queryParam, $status, $num_per_page, $offset);
		$result = json_decode(json_encode($result), true);
		$reuses = $result["reuses"];

		\Drupal::service('pager.manager')->createPager($result["nhits"], $num_per_page)->getCurrentPage();
		
		$header =  array(
			"name" => $this->t('Nom'),
			"dataset" => $this->t('Connaissance'),
			"author" => $this->t("Créateur"),
			"date" => $this->t("Date ajout"),
			"type" => $this->t("Type"),
			"description" => $this->t('Description'),
			"status" => $this->t("Statut"),
			"image" => $this->t('Image'),
			"view" => $this->t(''),
		);
		$output = array();
		
		foreach ($reuses as $row) {
			if(strlen($row["description"]) > 600){ 
				$row["description"] = substr($row["description"], 0, 600) . "...";
			}	
			if($row["status"] == "online" || $row["status"] == "offline"){
				$options = array('online'=>'Validé', 'offline' => 'Refusé', 'delete' => 'Supprimé');
			} else {
				$options = array('waiting'=>'En attente de validation', 'online'=>'Validé', 'offline' => 'Refusé', 'delete' => 'Supprimé');
			}
			
			$output[] = [
				'name' => array('data' => array('#markup' => $row["title"])),
				'dataset' => array('data' => array('#markup' => $row["dataset_title"])),
				'author' => array('data' => array('#markup' => $row["author_name"])),
				'last_modif' => array('data' => array('#markup' => date('Y-m-d H:i:s', strtotime($row["date"])))),
				'type' => array('data' => array('#markup' => $row["type"])),
				'description' => array('data' => array('#markup' => "<span><small>".$row["description"]."</small></span>")),
				'status' => array('data' => array(
					'#type' => 'select',     
					'#options' => $options,
					'#value' => $row["status"] ,
					'#attributes' => array(
						'onchange' => 'confirm(event)', 
						'data-id' => $row["id"],
						'data-old-status' => $row["status"]
					)
				)),
				'image' => array('data' => new FormattableMarkup('<img src=":link" class="img" style="height: 50px;width: auto" target="_blank"/>', 
					[':link' => $row["image"]])
				),
				'view' => array('data' => new FormattableMarkup('<a href=":link" class="button" style="border-radius: 10px;font-size: 11px;" target="_blank">@name</a>', 
					[':link' => $row["url"], 
					'@name' => $this->t('Visualiser')])
				)
			];
		}
        
        $orgas = $api->getAllOrganisations();
	
        foreach ($orgas as $value) {
            $option_org[$value["name"]] = $value["display_name"];
        }
		
		$req = "include_private=true&rows=10000";
		if ($orga != "") {
			$req .= "&q=organization:".$orga;
		}
		$datasets = $api->callPackageSearch_public_private($req, \Drupal::currentUser()->id());
		$datasets = $datasets->getContent();
		
        $datasets = json_decode($datasets, true)['result'];
        $datasets = $datasets['results'];
		foreach ($datasets as $value) {
            $option_ds[$value["id"]] = $value["title"];
        }
		
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
			'#default_value' => $orga
        );
		
		$form['filters']['selected_dataset'] = array(
			//'#prefix' => '<div class="container-inline">',
            '#type' => 'select',
            '#title' => t('Dataset :'),
            '#options' => $option_ds,
            '#empty_option' => t('----'),          
			'#attributes' => array(
				'style' => "display: inline-block;width: 50%;",
			),
			'#default_value' => $dataset
        );

		$form['filters']['selected_text'] = [
			'#title' => t('Recherche :'),
			'#type' => 'search',
			'#attributes' => array(
				'style' => "display: inline-block;width: 50%;",
			),
			'#default_value' => $queryParam
		];

		$form['filters']['selected_status'] = array(
            '#type' => 'select',
            '#title' => t('Statut :'),
            '#options' => array('waiting'=>'En attente de validation', 'online'=>'Validé', 'offline' => 'Refusé'),
            '#empty_option' => t('Tous'),  
			'#attributes' => array(
				'style' => "display: inline-block;width: 50%;",
			),
			'#default_value' => $status
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
		
		$form['selected_action'] = array(
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
        
		$this->config = include(__DIR__ . "/../../config.php");
        $this->urlCkan = $this->config->ckan->url;
        $api = new Api();
		
		$status = $form_state->getValue("selected_action");
		$id = $form_state->getValue("selected_id");
		
		$res = $api->getReuse($id);

		$label = '';
		if($status == "waiting"){
			$res["status"] = 0;
			$label = " a été mise en validation";
		} else if($status == "online"){
			$res["status"] = 1;
			$label = " a été mise en ligne";
		} else if($status == "offline"){
			$res["status"] = 2;
			$label = " a été retirée";
		} else if($status == "delete"){
			$res["status"] = 3;
			$label = " a été supprimée";
		}
		
		$api->updateReuse($res);
   
		\Drupal::messenger()->addMessage('La réutilisation '. $res["title"] . $label);
	}
	
	
	public function submitfiltering(array &$form, FormStateInterface $form_state){ 
		// Set the provided filter value in the storage.
		$filters = array();
		$filters["orga"] = $form_state->getValue("selected_org");
		$filters["q"] = $form_state->getValue("selected_text");
		$filters["status"] = $form_state->getValue("selected_status");
		$filters["dataset"] = $form_state->getValue("selected_dataset");
		
		//$form_state->setStorage($filters);
		//$form_state->setRebuild(TRUE);
		$url = Url::fromRoute('ckan_admin.form.reuses', [], ['query' => ["page" => 0, 'orga' => $filters["orga"], 'q' => $filters["q"], 'status' => $filters["status"], 'dataset' => $filters["dataset"]]]);
		$form_state->setRedirectUrl($url);
	}
	
	public function submitclear(array &$form, FormStateInterface $form_state){ 
		// Set the provided filter value in the storage.
		$filters = array();
		$filters["orga"] = "";
		$filters["q"] = "";
		$filters["status"] = "";
		$filters["dataset"] = "";
		
		//$form_state->setStorage($filters);
		//$form_state->setRebuild(False);
		$url = Url::fromRoute('ckan_admin.form.reuses', [], ['query' => ["page" => 0, 'orga' => $filters["orga"], 'q' => $filters["q"], 'status' => $filters["status"], 'dataset' => $filters["dataset"]]]);
		$form_state->setRedirectUrl($url);
	}
}