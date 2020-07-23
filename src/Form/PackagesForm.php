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
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\ckan_admin\Utils\HelpFormBase;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Drupal\Component\Render\FormattableMarkup; 



/**
 * Implements an example form.
 */
class PackagesForm extends HelpFormBase {
	
	/**
	 * {@inheritdoc}
	 */
    
	public function getFormId() {
		return 'extension_package';
	}

	public function buildForm(array $form, FormStateInterface $form_state) {
        $form = parent::buildForm($form, $form_state);
		

		// attach library to form
        $form['#attached']['library'][] = 'ckan_admin/PackagesForm.form';
        

        // get contents of config json file
		$this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
        $this->urlCkan = $this->config->ckan->url;

        // call api entity
        $api = new Api();
		
		$option_org=array();
		
		// pagination
		$page = pager_find_page();
		$num_per_page = 10;
		$offset = $num_per_page * $page;
		
		$filterQuery = "";
		if ($_GET["orga"] != "" || $_GET["q"] != "" || $_GET["type"] != "") {
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

		$query = 'include_private=true&rows='.$num_per_page.'&start='.$offset.$filterQuery;
        $result = $api->callPackageSearch_public_private($query);

        $result = $result->getContent();
        $result = json_decode($result, true)[result];

        // get datasets
        $datasets = $result[results];
		
        //-------------------- Filter form ---------------------------
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
		];

		// ---------------Filter by organisation-------------------------

		// get all organisation
		$orgas = $api->getAllOrganisations();
	
        foreach ($orgas as $value) {
            $option_org[$value["name"]] = $value["display_name"];
        }

		$form['filters']['selected_org'] = array(
            '#type' => 'select',
            '#title' => t('Organisation :'),
            '#options' => $option_org,
            '#empty_option' => t('----'),          
			'#attributes' => array(
				'style' => "display: inline-block;width: 50%;",
			),
			'#default_value' => $_GET["orga"]
        );
		//----------------End filter by organisaton --------------------------


		//---------------- filter by name of dataset --------------------------
		
        $form['filters']['selected_text'] = [
			'#title' => t('Recherche :'),
			'#type' => 'search',
			'#attributes' => array(
				'style' => "display: inline-block;width: 50%;",
			),
			'#default_value' => $_GET["q"]
		];

		//---------------- end filter by name of dataset --------------------------


		//---------------- filter by visibility of dataset --------------------------
		$form['filters']['selected_vis'] = array(
            '#type' => 'select',
            '#title' => t('Visibilité :'),
            '#options' => array('private'=>'Privé', 'public'=>'Public'),
            '#empty_option' => t('Tous'),  
			'#attributes' => array(
				'style' => "display: inline-block;width: 50%;",
			),
			'#default_value' => $_GET["type"]
        );
		//---------------- end filter by visibility of dataset --------------------------


		$form['filters']['actions'] = [
			'#type'       => 'actions'
		];

		//Submit button 
		$form['filters']['actions']['filter'] = [
			'#type'  => 'submit',
			'#value' => $this->t('Filter'),
			'#submit' => array([$this, 'submitfiltering'])
		];
        

        //Clear filter
        $form['filters']['actions']['clear'] = [
			'#type'  => 'submit',
			'#value' => $this->t('Effacer'),
			'#submit' => array([$this, 'submitclear'])
		];

//-------------------------End filter form -------------------------------------------------------



// -------------------------------------Show all datasets in table------------------------------- 

		// create modal export block
		$form['modalexport'] = array(
			'#markup' => '<div id="visibilityModalExport"></div>',
		); 

		// intialize pagination
		pager_default_initialize($result["count"], $num_per_page);
		
		//  create table header
		$header =  array(
			"name" => $this->t('Nom'),
			"orga" => $this->t('Organisation'),
			"last_modif" => $this->t("Dernière Modification"),  
			"export" => $this->t(''),    
		);

		$output = array();
		foreach ($datasets as $row) {

			$saveTimeZone = date_default_timezone_get();
			date_default_timezone_set('Europe/Paris');

			// create body table with data of datasets
			$uirow = [
				'name' => array('data' => array('#markup' => $row["title"])),
				'orga' => array('data' => array('#markup' => $row["organization"]["title"])),
				'last_modif' => array('data' => array('#markup' => date('Y-m-d H:i:s', strtotime($row["metadata_modified"] . " UTC")))),
				'export' => array('data' => new FormattableMarkup('<input id="exportdataset" type="button" onclick=":action" class="button" style="border-radius: 10px;font-size: 11px;padding: 4px 5px;" value=":name" data-id=":id" id=":id"/>', 
											[':action' => "openExportPopup(event)", 
											':name' => $this->t('Exporter'),
											
											':id' => $row["id"]])
				),
			];

			date_default_timezone_set($saveTimeZone);
			
			$output[] = $uirow;
			 
		}

		// create table
      	$form['table'] = array(
			'#type' => 'table',
			'#header' => $header,
			'#rows' => $output,
		);

// -------------------------------------end Show all datasets in table------------------------------- 
      	// pagination
		$form['pager'] = [
		  '#type' => 'pager',
		  '#tags' => array(t('« Première page'), t('‹ Page précédente'),"", t('Page suivante ›'), t('Dernière page »')),
		  '#submit' => array([$this, 'submitfiltering'])
		];

		return $form;
	}

    
    //submit form
	public function submitForm(array &$form, FormStateInterface $form_state)
	{
		$this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
        $this->urlCkan = $this->config->ckan->url;
        $api = new Api();
		$callUrl = $this->urlCkan . "/api/action/package_update";
		$return = $api->updateRequest($callUrl, $oldDataset, "POST");
       
	}


	// filter function
	public function submitfiltering(array &$form, FormStateInterface $form_state){ 
		// Set the provided filter value in the storage.
		$filters = array();
		$filters["orga"] = $form_state->getValue("selected_org");
		$filters["q"] = $form_state->getValue("selected_text");
		$filters["type"] = $form_state->getValue("selected_vis");
		$url = Url::fromRoute('ckan_admin.extension_package_data4citizen', [], ['query' => ["page" => 0, 'orga' => $filters["orga"], 'q' => $filters["q"], 'type' => $filters["type"]]]);
		$form_state->setRedirectUrl($url);
	}

	// clear filter function
	public function submitclear(array &$form, FormStateInterface $form_state){ 
		// Set the provided filter value in the storage.
		$filters = array();
		$filters["orga"] = "";
		$filters["q"] = "";
		$filters["type"] = "";
		$url = Url::fromRoute('ckan_admin.extension_package_data4citizen', [], ['query' => ["page" => 0, 'orga' => $filters["orga"], 'q' => $filters["q"], 'type' => $filters["type"]]]);
		$form_state->setRedirectUrl($url);
	}

}
