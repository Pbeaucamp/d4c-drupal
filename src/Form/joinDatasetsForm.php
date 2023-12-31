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
use Drupal\ckan_admin\Utils\ResourceManager;
use Drupal\ckan_admin\Utils\HelpFormBase;

/**
 * Implements an example form.
 */
class joinDatasetsForm extends HelpFormBase
{

	protected $organizationList;
	protected $datasets;
	/**
	 * {@inheritdoc}
	 */
	public function getFormId()
	{
		return 'joinDatasetsForm';
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state)
	{
		$form = parent::buildForm($form, $form_state);


		$form['#attached']['library'][] = 'ckan_admin/joinDatasetsForm.form';
		$this->config = include(__DIR__ . "/../../config.php");
		$this->urlCkan = $this->config->ckan->url;

		$api = new Api;
		$orgs = $api->getAllOrganisations(true, false, true);
		
		$this->organizationList = array();
        foreach ($orgs as &$value) {
			$this->organizationList[$value[id]] = $value[display_name];
		}

		$dataSet = $api->callPackageSearch_public_private('include_private=true&rows=1000&sort=title_string asc', \Drupal::currentUser()->id());
		$dataSet = $dataSet->getContent();
		$dataSet = json_decode($dataSet, true);
		$this->datasets = $dataSet[result][results];


		///////////////////////////////license_list////

		$lic = $api->getLicenses();

		$licList = array();
		foreach ($lic[result] as $value) {
			$licList[$value[id]] = $value[title];
		}

		///////////////////////////////license_list////
		// }

		$ids = array();

		foreach ($this->datasets as $ds) {
			$ids[$ds[id]] = $ds[title];
		}
		//error_log("ff-".count($ids));

		$form['title'] = array(
			'#markup' => '',
			'#type' => 'textfield',
			'#title' => $this->t('Titre :'),
			'#attributes' => array('style' => 'width: 50%;'),
		);

		$form['description'] = array(
			'#type' => 'textarea',
			'#title' => $this->t('Description :'),
			'#resizable' => true,
			'#attributes' => array('style' => 'width: 50%;'),

		);
		$form['Org_new'] = array(
			//'#prefix' =>'',
			'#type' => 'select',
			'#title' => t('Organisation:'),
			'#options' => $this->organizationList,
			'#empty_option' => t('----'),
			'#attributes' => array('style' => 'width: 50%;'),

		);
		$form['selected_lic'] = array(
			'#type' => 'select',
			'#title' => t('*Licence :'),
			'#options' => $licList,
			'#empty_option' => t('----'),
			'#attributes' => array('style' => 'width: 50%;'),
		);

		$form['m1'] = array(
			'#markup' => '<hr>',
		);

		$form['filtr_org'] = array(
			//'#prefix' =>'',
			'#type' => 'select',
			'#title' => t('Organisation 1:'),
			'#options' => $this->organizationList,
			'#empty_option' => t('----'),
			'#attributes' => array('style' => 'width: 50%;'),
			'#ajax'         => [
				'callback'  => [$this, 'datasetCallback'],
				'event' => 'change',
				'wrapper'   => 'selected_data1',
			],
			'#name' => "org1"
		);

		if ($form_state->getTriggeringElement()["#name"] == 'org1') {
			//$selected_org = $form_state->getValue('filtr_org');
			$selected_org = $form_state->getUserInput()['org1'];
			$idss = array();

			//$ids["new"] = "Сréer un jeu de données";
			if ($selected_org == '') {
				foreach ($this->datasets as $ds) {
					$idss[$ds[id]] = $ds[title];
				}
			} else {
				foreach ($this->datasets as $ds) {
					if ($ds[organization][id] == $selected_org) {
						$idss[$ds[id]] = $ds[title];
					}
				}
			}

			$form['selected_data'] = array(
				'#type' => 'select',
				'#title' => t('Sélectionner un jeu de données'),
				'#options' => $idss,
				'#empty_option' => t('----'),
				'#attributes' => array(
					'style' => 'width: 50%;'
				),
				'#ajax'         => [
					'callback'  => [$this, 'columnsCallback'],
					'wrapper'   => 'columns_data',
				],
				'#prefix' => '<div id="selected_data1">',
				'#suffix' => '</div>',
				'#name' => "ds1"
			);
		} else {
			$form['selected_data'] = array(
				'#type' => 'select',
				'#title' => t('Sélectionner un jeu de données'),
				'#options' => $ids,
				'#empty_option' => t('----'),
				'#attributes' => array(
					'style' => 'width: 50%;'
				),
				'#ajax'         => [
					'callback'  => [$this, 'columnsCallback'],
					'wrapper'   => 'columns_data',
				],
				'#prefix' => '<div id="selected_data1">',
				'#suffix' => '</div>',
				'#name' => "ds1"
			);
		}


		if ($form_state->getTriggeringElement()["#name"] == 'ds1') {
			$jdd = $form_state->getUserInput()['ds1'];
			/*$jdd = $this->getDataById($jdd);
			$jdd = $jdd[result];
			$jdd = $jdd[resources];*/

			foreach ($this->datasets as $d) {
				if ($d[id] == $jdd) {
					$jdd = $d[resources];
				}
			}

			$columns = array();
			$columns['empty'] = '---';

			foreach ($jdd as $value) {
				if ($value[format] == 'CSV' || $value[format] == 'csv') {

					/*$fh = fopen($value[url], 'r');
				
					while (($data = fgetcsv($fh, 0, ",")) !== FALSE) {
						$csv1[]=$data;
					}
				
					if(strpos($csv1[0][0], ';')>1){
						$arr = explode(";", $csv1[0][0]);
						foreach($arr as &$val){
							$columns[$val]  = $val;
						}
					}
					else{
						foreach($csv1[0] as &$val){
							$columns[$val]  = $val;
						}
					}*/

					$cols = $api->getAllFields($value[id]);
					foreach ($cols as $c) {
						$columns[$c["name"]]  = $c["name"];
					}
				}
			}

			$form['columns_data'] = array(
				'#type' => 'select',
				'#title' => t('Sélectionner une colonne'),
				'#options' => $columns,
				'#prefix' => '<div id="columns_data">',
				'#suffix' => '</div>',
				'#name' => "col1"
			);
		} else {
			$form['columns_data'] = array(
				'#type' => 'select',
				'#title' => t('Sélectionner une colonne'),
				'#options' => array(),
				'#prefix' => '<div id="columns_data">',
				'#suffix' => '</div>',
				'#name' => "col1"
			);
		}


		$form['column_join'] = array(
			'#type' => 'textfield',
			'#resizable' => true,
			'#attributes' => array('style' => 'width: 50%;display:none;'),
		);


		$form['m1f'] = array(
			'#markup' => '<hr>',
		);

		$form['filtr_org2'] = array(
			//'#prefix' =>'',
			'#type' => 'select',
			'#title' => t('Organisation 2:'),
			'#options' => $this->organizationList,
			'#empty_option' => t('----'),
			'#attributes' => array('style' => 'width: 50%;'),
			'#ajax'         => [
				'callback'  => [$this, 'datasetCallback2'],
				'event' => 'change',
				'wrapper'   => 'selected_data2',
			],
			'#name' => "org2"
		);

		if ($form_state->getTriggeringElement()["#name"] == 'org2') {
			//$selected_org = $form_state->getValue('filtr_org');
			$selected_org = $form_state->getUserInput()['org2'];
			$idss = array();

			//$ids["new"] = "Сréer un jeu de données";
			if ($selected_org == '') {
				foreach ($this->datasets as $ds) {
					$idss[$ds[id]] = $ds[title];
				}
			} else {
				foreach ($this->datasets as $ds) {
					if ($ds[organization][id] == $selected_org) {
						$idss[$ds[id]] = $ds[title];
					}
				}
			}
			//error_log("gg-".json_encode($idss));
			$form['selected_data2'] = array(
				'#type' => 'select',
				'#title' => t('Sélectionner un jeu de données'),
				'#options' => $idss,
				'#empty_option' => t('----'),
				'#attributes' => array(
					'style' => 'width: 50%;'
				),
				'#ajax'         => [
					'callback'  => [$this, 'columnsCallback2'],
					'wrapper'   => 'columns_data2',
				],
				'#prefix' => '<div id="selected_data2">',
				'#suffix' => '</div>',
				'#name' => "ds2"
			);
		} else {
			$form['selected_data2'] = array(
				'#type' => 'select',
				'#title' => t('Sélectionner un jeu de données 2'),
				'#options' => $ids,
				'#empty_option' => t('----'),
				'#attributes' => array(
					'style' => 'width: 50%;'
				),
				'#ajax'         => [
					'callback'  => [$this, 'columnsCallback2'],
					'wrapper'   => 'columns_data2',
				],
				'#prefix' => '<div id="selected_data2">',
				'#suffix' => '</div>',
				'#name' => "ds2"
			);
		}

		if ($form_state->getTriggeringElement()["#name"] == 'ds2') {
			$jdd = $form_state->getUserInput()['ds2'];

			foreach ($this->datasets as $d) {
				if ($d[id] == $jdd) {
					$jdd = $d[resources];
				}
			}

			$columns = array();
			$columns['empty'] = '---';

			foreach ($jdd as $value) {
				if ($value[format] == 'CSV' || $value[format] == 'csv') {
					$cols = $api->getAllFields($value[id]);
					foreach ($cols as $c) {
						$columns[$c["name"]]  = $c["name"];
					}
				}
			}

			$form['columns_data2'] = array(
				'#type' => 'select',
				'#title' => t('Sélectionner une colonne'),
				'#options' => $columns,
				'#prefix' => '<div id="columns_data2">',
				'#suffix' => '</div>',
				'#name' => "col2"
			);
		} else {
			$form['columns_data2'] = array(
				'#type' => 'select',
				'#title' => t('Sélectionner une colonne'),
				'#options' => array(),
				'#prefix' => '<div id="columns_data2">',
				'#suffix' => '</div>',
				'#name' => "col2"
			);
		}

		$form['column_join2'] = array(
			'#type' => 'textfield',
			'#resizable' => true,
			'#attributes' => array('style' => 'width: 50%; display:none;'),
		);


		$form['m2d'] = array(
			'#markup' => '<br>',
		);

		$form['valider'] = array(
			'#type' => 'submit',
			'#value' => $this->t('Faire la jointure'),
		);

		$form['m3d'] = array(
			'#markup' => '<span>Ceci effectue une jointure Full Join.</span>',
		);

		return $form;
	}


	public function submitForm(array &$form, FormStateInterface $form_state)
	{

		$this->config = include(__DIR__ . "/../../config.php");
		$this->urlCkan = $this->config->ckan->url;
		$api = new Api;

		$title = $form_state->getValue('title');
		$description = $form_state->getValue('description');
		$org_new = $form_state->getValue('Org_new');
		$selected_lic = $form_state->getValue('selected_lic');

		/*$jdd1_id = $form_state->getValue('selected_data');
        $jdd2_id = $form_state->getValue('selected_data2');
    
        $columns_data = $form_state->getValue('column_join');
        $columns_data2 = $form_state->getValue('column_join2');*/
		$jdd1_id = $form_state->getUserInput()['ds1'];
		$columns_data = $form_state->getUserInput()['col1'];
		$jdd2_id = $form_state->getUserInput()['ds2'];
		$columns_data2 = $form_state->getUserInput()['col2'];

		/*$jdd1 = $this->getDataById($jdd1);
        $jdd2 = $this->getDataById($jdd2);
    
        $jdd1 = $jdd1[result];
        $jdd2 = $jdd2[result];*/
		$jdd1;
		$jdd2;
		foreach ($this->datasets as $d) {
			if ($d[id] == $jdd1_id) {
				$jdd1 = $d;
			}
			if ($d[id] == $jdd2_id) {
				$jdd2 = $d;
			}
		}

		$urlRes = $this->urlCkan . "/dataset/" . $jdd1[name] . '_' . $jdd2[name];

		$extras[count($extras)]['key'] = 'FTP_API';
		$extras[(count($extras) - 1)]['value'] = 'FTP';

		###### security #######
		$idUser = "*" . \Drupal::currentUser()->id() . "*";
		// $users = \Drupal\user\Entity\User::loadMultiple();

		$users = $api->getAdministrators();
		$userlist = array();
		foreach ($users as $user) {
			$username = $user->get('name')->value;
			$uid = $user->get('uid')->value;
			$uroles = $user->getRoles();
			if ($username != "" && (in_array("administrator", $uroles) || $uid == 1)) {
				$userlist[] = "*" . $uid . "*";
			}
		}
		$userlist[] = $idUser;
		$userlist = array_unique($userlist);
		if (count($userlist) == 1) {
			$userlist = array($userlist);
		}
		$security = json_encode(array("roles" => array("administrator"), "users" => $userlist));
		#######################

		$extras[count($extras)]['key'] = 'edition_security';
		$extras[(count($extras) - 1)]['value'] = $security;

		if ($jdd1[tags] == null) $jdd1[tags] = array();
		if ($jdd2[tags] == null) $jdd2[tags] = array();

		$description = 'Ce DataSet a été créé par la jointure entre les jeux de données: "' . $jdd1[title] . '" et "' . $jdd2[title] . '". </br>'
			. $description . '</br>'
			. "[" . $jdd1[notes] . ']</br>'
			. "[" . $jdd2[notes] . ']</br>';

		$newData = [
			"name" => $this->nettoyage($title),
			"title" =>  $title,
			"private" => true,
			"author" => "",
			"author_email" => "",
			"maintainer" => "",
			"maintainer_email" => "",
			"license_id" => $selected_lic,
			"notes" => $description,
			"url" => $urlRes,
			"version" => "",
			"state" => "active",
			"type" => "dataset",
			"resources" => [],
			"tags" => array_merge($jdd1[tags], $jdd2[tags]),
			"extras" => $extras,
			"relationships_as_object" => [],
			"relationships_as_subject" => [],
			"groups" => [],
			"owner_org" => $org_new,
		];

		$idNewData = $this->saveData($newData, $coll);
		$idNewData = $idNewData[1];
		$NewTitle = $idNewData[2];

		$config = \Drupal::service('config.factory')->getEditable('ckan_admin.moissonnage_data_gouv_form');
		$dataForUpdateDatasets = $config->get('dataForUpdateDatasets');
		$dataForUpdateDatasets = json_decode($dataForUpdateDatasets);

		$org;
		$org_name;
		foreach ($this->organizationList as $o) {
			if ($o[id] == $org_new) {
				$org = $o;
				$org_name = $org[title];
			}
		}

		$dataset_conf = [
			"id_data" => $idNewData,
			"id_data_site" => $idNewData,
			"title_data" => $title,
			"last_update" => date('m/d/Y H:i:s', time()),
			"periodic_update" => '',
			"site" => 'joinDataset',
			"site_infocom" => [$jdd1_id, $jdd2_id, $columns_data, $columns_data2]
		];

		$controlEx = false;

		foreach ($dataForUpdateDatasets as $key => $value) {
			if ($value->id_org == $org_new) {
				array_push($dataForUpdateDatasets[$key]->datasets, $dataset_conf);
				$controlEx = true;
				break;
			}
		}

		if ($controlEx == false) {
			$dataForUpdateDatasets[count($dataForUpdateDatasets)] = [
				"id_org" => $org_new,
				"name_org" => $org_name,
				"datasets" => [$dataset_conf],
			];
		}

		$config->set('dataForUpdateDatasets', json_encode($dataForUpdateDatasets))->save();

		$csv1 = '';
		$csv2 = '';


		foreach ($jdd1[resources] as $value) {
			if (($value[format] == "CSV" || $value[format] == "csv" || $value[datastore_active] == true) && $csv1 == "") {
				$csv1 = $value[id];
			} else {
				$resources = [
					"package_id" => $idNewData,
					"url" => $value[url],
					"description" => $value[description],
					"name" => $value[name],
				];
				$callUrluptres = $this->urlCkan . "/api/action/resource_create";
				$return = $api->updateRequest($callUrluptres, $resources, "POST");
			}
		}

		foreach ($jdd2[resources] as $value) {
			if (($value[format] == "CSV" || $value[format] == "csv" || $value[datastore_active] == true) && $csv2 == "") {
				$csv2 = $value[id];
			} else {
				$resources = [
					"package_id" => $idNewData,
					"url" => $value[url],
					"description" => $value[description],
					"name" => $value[name],
				];
				$callUrluptres = $this->urlCkan . "/api/action/resource_create";
				$return = $api->updateRequest($callUrluptres, $resources, "POST");
			}
		}

		if ($csv1 != '' && $csv2 != '') {
			$urlFileNew = $this->join2csv($csv1, $csv2, $jdd1[name] . '_' . $jdd2[name], $columns_data, $columns_data2);

			//Managing resources
			$resourceManager = new ResourceManager;
			$results = $resourceManager->manageFileWithPath($idNewData, null, false, null, $urlFileNew, '', null, false, false, true, $jdd1[name] . '_' . $jdd2[name], true);

			// $resources = [
			// 	"package_id" => $idNewData,
			// 	"url" => $urlFileNew,
			// 	"description" => '',
			// 	"name" => $jdd1[name] . '_' . $jdd2[name],
			// ];

			// $callUrluptres = $this->urlCkan . "/api/action/resource_create";
			// $return = $api->updateRequest($callUrluptres, $resources, "POST");
		}

		//We update the visualisation's icons
		$api->calculateVisualisations($idNewData);
	}

	public function validateForm(array &$form, FormStateInterface $form_state)
	{

		$title = $form_state->getValue('title');
		$org_new = $form_state->getValue('Org_new');
		$selected_lic = $form_state->getValue('selected_lic');
		/*$selected_data = $form_state->getValue('selected_data');
        $columns_data = $form_state->getValue('columns_data');
        $selected_data2 = $form_state->getValue('selected_data2');
        $columns_data2 = $form_state->getValue('columns_data2');*/
		$selected_data = $form_state->getUserInput()['ds1'];
		$columns_data = $form_state->getUserInput()['col1'];
		$selected_data2 = $form_state->getUserInput()['ds2'];
		$columns_data2 = $form_state->getUserInput()['col2'];

		if ($title == '') $form_state->setErrorByName('title', $this->t('Aucune donnée sélectionnée'));
		if ($org_new == '') $form_state->setErrorByName('Org_new', $this->t('Aucune donnée sélectionnée'));
		if ($selected_lic == '') $form_state->setErrorByName('selected_lic', $this->t('Aucune donnée sélectionnée'));
		if ($selected_data == '') $form_state->setErrorByName('selected_data', $this->t('Aucune donnée sélectionnée'));
		if ($columns_data == '') $form_state->setErrorByName('columns_data', $this->t('Aucune donnée sélectionnée'));
		if ($selected_data2 == '') $form_state->setErrorByName('selected_data2', $this->t('Aucune donnée sélectionnée'));
		if ($columns_data2 == '') $form_state->setErrorByName('columns_data2', $this->t('Aucune donnée sélectionnée'));
	}

	public function datasetCallback(array &$form, FormStateInterface $form_state)
	{
		return $form['selected_data'];
	}

	public function datasetCallback2(array &$form, FormStateInterface $form_state)
	{
		return $form['selected_data2'];
	}

	public function columnsCallback(array &$form, FormStateInterface $form_state)
	{
		return $form['columns_data'];
	}

	public function columnsCallback2(array &$form, FormStateInterface $form_state)
	{
		return $form['columns_data2'];
	}

	public function saveData($newData, $data)
	{

		$coll = $data[0];


		$api = new Api;
		$callUrlNewData = $this->urlCkan . "/api/action/package_create";
		$return = $api->updateRequest($callUrlNewData, $newData, "POST");
		$resnew = json_decode($return);
		$idNewData = $resnew->result->id;
		$NewTitle = $resnew->result->title;

		if ($resnew->success == true) {
			\Drupal::messenger()->addMessage('Les données ont été sauvegardées');
			$idNewData = $resnew->result->id;
			$NewTitle = $resnew->result->title;
		} else if ($resnew->error->name[0] == 'Cette URL est déjà utilisée.') {
			$coll++;

			if ($coll == 1) {
				$newData[name] = $newData[name] . '_' . $coll;
				$newData[title] = $newData[title] . ' ' . $coll;
				$NewData = $this->saveData($newData, array('0' => $coll, '1' => $idNewData, '2' => $NewTitle));
				$idNewData = $NewData[1];
				$NewTitle = $NewData[2];
			} else if ($coll > 10) {
				$newData[name] = substr($newData[name], 0, -3);
				$newData[name] = $newData[name] . '_' . $coll;
				$newData[title] = substr($newData[title], 0, -3);
				$newData[title] = $newData[title] . ' ' . $coll;
				$NewData = $this->saveData($newData, array('0' => $coll, '1' => $idNewData, '2' => $NewTitle));
				$idNewData = $NewData[1];
				$NewTitle = $NewData[2];
			} else if ($coll > 100) {
				$newData[name] = substr($newData[name], 0, -4);
				$newData[name] = $newData[name] . '_' . $coll;
				$newData[title] = substr($newData[title], 0, -4);
				$newData[title] = $newData[title] . ' ' . $coll;
				$NewData = $this->saveData($newData, array('0' => $coll, '1' => $idNewData, '2' => $NewTitle));
				$idNewData = $NewData[1];
				$NewTitle = $NewData[2];
			} else if ($coll > 1000) {
				$newData[name] = substr($newData[name], 0, -5);
				$newData[name] = $newData[name] . '_' . $coll;
				$newData[title] = substr($newData[title], 0, -5);
				$newData[title] = $newData[title] . ' ' . $coll;
				$NewData = $this->saveData($newData, array('0' => $coll, '1' => $idNewData, '2' => $NewTitle));
				$idNewData = $NewData[1];
				$NewTitle = $NewData[2];
			} else if ($coll > 10000) {
				$newData[name] = substr($newData[name], 0, -6);
				$newData[name] = $newData[name] . '_' . $coll;
				$newData[title] = substr($newData[title], 0, -6);
				$newData[title] = $newData[title] . ' ' . $coll;
				$NewData = $this->saveData($newData, array('0' => $coll, '1' => $idNewData, '2' => $NewTitle));
				$idNewData = $NewData[1];
				$NewTitle = $NewData[2];
			} else {
				$newData[name] = substr($newData[name], 0, -2);
				$newData[name] = $newData[name] . '_' . $coll;
				$newData[title] = substr($newData[title], 0, -2);
				$newData[title] = $newData[title] . ' ' . $coll;
				$NewData = $this->saveData($newData, array('0' => $coll, '1' => $idNewData, '2' => $NewTitle));
				$idNewData = $NewData[1];
				$NewTitle = $NewData[2];
			}
		} else {
			\Drupal::messenger()->addMessage(t('les données n`ont pas été ajoutées!'), 'error');
		}

		return array('0' => $coll, '1' => $idNewData, '2' => $NewTitle);
	}

	function nettoyage($str, $charset = 'utf-8')
	{
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
		$str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
		$str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
		$str = preg_replace('#&[^;]+;#', '', $str);

		$str = str_replace("-", "_", $str);
		return $str;
	}

	function join2csv($url1, $url2, $nameFile, $columns_data, $columns_data2) {
		$filePath = $this->config->client->drupal_root . '/sites/default/files/dataset/' . $nameFile . '.csv';

		$this->protocol = isset($this->config->client->protocol) ? $this->config->client->protocol . '://' : 'https://';
		$this->host = $this->config->client->host;
		$this->port = isset($this->config->client->port) ? ':' . $this->config->client->port : '';

		$fileUrl = $this->protocol . $this->host . $this->port . $this->config->client->routing_prefix . '/sites/default/files/dataset/' . $nameFile . '.csv';

		$api = new Api();
		// on récupère les champs de la premiere table
		$req = array();
		$sql = "Select *from \"" . $url1 . "\" limit 0";
		$req['sql'] = $sql;
		$query = http_build_query($req);
		$callUrl =  $this->urlCkan . "api/action/datastore_search_sql?" . $query;

		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $api->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		$result = json_decode($result, true);
		$cols1 = array();
		if ($result["success"] == true) {
			foreach ($result["result"]["fields"] as $f) {
				if ($f["id"] != "_id" && $f["id"] != "_full_text") {
					$cols1[$f["id"]] = $f["id"];
				}
			}
		}

		// on récupère les champs de la seconde table
		$req = array();
		$sql = "Select *from \"" . $url2 . "\" limit 0";
		$req['sql'] = $sql;
		$query = http_build_query($req);
		$callUrl =  $this->urlCkan . "api/action/datastore_search_sql?" . $query;

		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $api->getStoreOptions());
		$result = curl_exec($curl);
		curl_close($curl);
		$result = json_decode($result, true);
		$cols2 = array();
		if ($result["success"] == true) {
			foreach ($result["result"]["fields"] as $f) {
				if ($f["id"] != "_id" && $f["id"] != "_full_text") {
					$cols2[$f["id"]] = $f["id"];
				}
			}
		}

		// on renomme les colonnes doublons
		foreach ($cols2 as $key => $value) {
			if (array_key_exists($key, $cols1)) {
				$cols2[$key] = $value . "2";
			}
		}

		//on lance la requete ultime		
		$req = array();
		$fields = array();
		foreach ($cols1 as $key => $value2) {
			$fields[] = "a." . $key . " as " . $value2;
		}
		foreach ($cols2 as $key => $value2) {
			$fields[] = "b." . $key . " as " . $value2;
		}
		$fieldreq = implode(", ", $fields);
		//error_log(json_encode($fields));
		$sql = "Select " . $fieldreq . " from \"" . $url1 . "\" as a full join \"" . $url2 . "\" as b on cast(a." . $columns_data . " as varchar) = cast(b." . $columns_data2 . " as varchar)";
		$req['sql'] = $sql;
		//error_log( $sql);
		$query = http_build_query($req);
		$callUrl =  $this->urlCkan . "api/action/datastore_search_sql?" . $query;

		//echo $callUrl;
		$curl = curl_init($callUrl);
		curl_setopt_array($curl, $api->getStoreOptions());
		$result = curl_exec($curl);
		//error_log($result);
		curl_close($curl);
		$result = json_decode($result, true);

		$res_arr = array();
		if (count($result["result"]["records"]) > 0) {
			$nome_column_new = array_merge(array_values($cols1), array_values($cols2));

			$res_arr[0] = $nome_column_new;

			foreach ($result["result"]["records"] as $record) {
				$line = array();
				foreach ($nome_column_new as $k => $col) {
					$val = $record[$col];
					$line[] = $val;
				}
				$res_arr[] = $line;
			}
		}

		// 3 section     
		$fp = fopen($filePath, 'w');
		foreach ($res_arr as $fields) {
			fputcsv($fp, $fields, ";");
		}
		fclose($fp);

		return $fileUrl;
	}
}
