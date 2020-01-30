<?php
/**
 * @file
* Contains \Drupal\search_api_solr_admin\Form\VanillaForm.
*/

namespace Drupal\ckan_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ckan_admin\Utils\Query;
use Drupal\ckan_admin\Utils\DataSet;
use Drupal\ckan_admin\Utils\Api;
use Drupal\Core\Database\Database;
use Drupal\ckan_admin\Utils\HelpFormBase;
/**
 * Implements an example form.
 */
class VanillaForm extends HelpFormBase {


	/**
	 * {@inheritdoc}
	 */
	public function getFormId() {
		return 'VanillaForm';
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
		
		
		
        $form = parent::buildForm($form, $form_state);
		
		$form['#attached']['library'][] = 'ckan_admin/VanillaForm.form';
		
        $form['m1'] = array(
			'#markup' => '<div id="repositoryTab">',
		); 
			
		$form['login'] = array(
            '#type' => 'textfield',
			'#title' => $this->t('Utilisateur :'),
			'#attributes' => [
				'placeholder' => 'Utilisateur',
				'id' => 'txtlogin'
			],
			'#default_value' => 'system',
        );
		
		$form['pass'] = array(
            '#type' => 'password',
			'#title' => $this->t('Mot de passe :'),
			'#attributes' => [
				'placeholder' => 'Mot de passe',
				'id' => 'txtpass'
			],
			'#default_value' => 'system',
        );	
		
		$form['group'] = array(
            '#type' => 'textfield',
			'#title' => $this->t('Groupe :'),
			'#attributes' => [
				'placeholder' => 'Groupe',
				'id' => 'txtgroup'
			],
			'#default_value' => 'System',
        );
		
		$form['repository'] = array(
            '#type' => 'textfield',
			'#title' => $this->t('Référentiel :'),
			'#attributes' => [
				'placeholder' => 'Référentiel',
				'id' => 'txtrepo'
			],
			'#default_value' => 'Vanilla',
        );
			
			$form['load'] = array(
			'#type' => 'button',
			'#value' => t('Chargement référentiel'),
			'#attributes' => [
				'id' => 'repositorybtn',
				'onclick' => 'loadRepository(event);', 
			],
			"#name" => "help"
        );
			
			$form['repositorytree'] = [
				  '#type' => 'container',
				  '#attributes' => ['id' => 'repositoryDiv', 'style' => 'height:200px;overflow:auto;'],
				];
				
							$form['test'] = [
				  '#type' => 'container',
				  '#attributes' => ['id' => 'test'],
				];
			
			
			

		
		$form['itemid'] = array(
            '#type' => 'textfield',
			'#attributes' => [
				'id' => 'txtitemid',
				'style' => 'display:none;'
			],
        );
		
		$form['m1_2'] = array(
		  '#markup' => '</div>',
		);   
		
		$form['m2'] = array(
			'#markup' => '<div id="datasetTab">',
		); 
		
		$form['Dataset_lies_table'] = array(
            '#type' => 'table',
            '#header' => array(
                $this->t('Jeux de données'),
            ),
            '#attributes' => array('style' => 'width: 100%;height: 450px;overflow:auto;'),

        );
		
		$form['m2_2'] = array(
		  '#markup' => '</div>',
		);   
		
			$form['search'] = array(
					'#type' => 'submit',
					'#value' => $this->t('Création/mise à jour métadata'),
			);
		
		
		
		$this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
        $this->urlCkan = $this->config->ckan->url;

        $api = new Api;

        $dataSet = $api->callPackageSearch_public_private('include_private=true&rows=1000&sort=title_string%20asc');
							   
     
        $dataSet = $dataSet->getContent();
        $dataSet2 = json_encode($dataSet, true);
        $dataSet = json_decode($dataSet, true);
        $dataSet = $dataSet[result][results];

		error_log($dataSet);
		
        foreach ($dataSet as &$value) {

            $form['Dataset_lies_table'][$value[name] . ':' . $value[id]]['dt'] = array(
                '#prefix' => '<div id="id_row_'.$value[id].'" >',
                '#type' => 'checkbox',
                '#title' => $this->t($value[title]),
                '#suffix' => '</div>',

            );

        }
		
			return $form;
	}
	
	public function validateForm(array &$form, FormStateInterface $form_state) {
		if ($form_state->getValue('itemid') == '') {
			$form_state->setErrorByName('itemid', $this->t('La sélection d\'un dossier ou Metadata est obligatoire.'));
		}
		
	}

	public function submitForm(array &$form, FormStateInterface $form_state){
		$this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
		
		$Dataset_lies_table = $form_state->getValue('Dataset_lies_table');
		$string_dataset_lies = '';
		
		foreach ($Dataset_lies_table as $key => &$val) {
			if ($val[dt] == 1) {
				$string_dataset_lies = $string_dataset_lies . ';' . substr($key, strrpos($key, ':') + 1, strlen($key)-1);
			}
		}
		
		$itemId = $form_state->getValue('itemid');
		$idparts= explode(':', $itemId);
		$type = $idparts[0];
		$id = $idparts[1];
		$up = 0;
		if($type == 'item') {
			$up = 1;
		}
		
		$string_dataset_lies = substr($string_dataset_lies, 1);
		
        $urlCkan = $this->config->ckan->url;
		$cle = $this->config->ckan->api_key;
		$connection = Database::getConnectionInfo('default');
		//drupal_set_message(print_r($connection,true));
		drupal_set_message("cd /home/user-client && java -cp d4cmetadata.jar bpm.metadata.tools.TestD4cMetadata -u \"" . $urlCkan . "\" -k \"" . $cle . "\" -o \"infogreffe\" -j \"jdbc:postgresql://" . $connection['default']['host'] . ":" . $connection['default']['port'] . "/" . str_replace('drupal_d4c', 'datastore', $connection['default']['database']) . "\" -l \"" . $connection['default']['username'] . "\" -p \"" . $connection['default']['password'] . "\" -d " . $id . " -up " . $up . " -ds \"" . $string_dataset_lies . "\" > logs.txt");
		exec("cd /home/user-client && java -cp d4cmetadata.jar bpm.metadata.tools.TestD4cMetadata -u \"" . $urlCkan . "\" -k \"" . $cle . "\" -o \"infogreffe\" -j \"jdbc:postgresql://" . $connection['default']['host'] . ":" . $connection['default']['port'] . "/" . str_replace('drupal_d4c', 'datastore', $connection['default']['database']) . "\" -l \"" . $connection['default']['username'] . "\" -p \"" . $connection['default']['password'] . "\" -d " . $id . " -up " . $up . " -ds \"" . $string_dataset_lies . "\" > logs.txt");
	}
}