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
class CubeForm extends HelpFormBase {


	/**
	 * {@inheritdoc}
	 */
	public function getFormId() {
		return 'CubeForm';
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
		
		$this->config = include(__DIR__ . "/../../config.php");
        $this->urlCkan = $this->config->ckan->url;
		
		$cle = $this->config->ckan->api_key;
		$optionst = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_HTTPHEADER => array(
				'Content-type:application/json',
				'Content-Length: ' . strlen($jsonData),
				'Authorization:  ' . $cle
			)
		);


		$callUrlOrg =  $this->urlCkan . "api/action/organization_list?all_fields=true";
		$curlOrg = curl_init($callUrlOrg);

		curl_setopt_array($curlOrg, $optionst);
		$orgs = curl_exec($curlOrg);
		curl_close($curlOrg);
		$orgs = json_decode($orgs, true);

		$organizationList = array();

		for ($i = 0; $i < (is_countable($orgs['result']) ? count($orgs['result']) : 0); $i++) {
			$organizationList[$orgs['result'][$i]['name']] = $orgs['result'][$i]['display_name'];
		}
		
        $form = parent::buildForm($form, $form_state);
		
		$form['#attached']['library'][] = 'ckan_admin/VanillaForm.form';
		$form['#attached']['library'][] = 'ckan_admin/GeolocForm.form';
		
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
		
		$form['itemName'] = array(
            '#type' => 'textfield',
			'#title' => $this->t('Nom du cube :'),
			'#attributes' => [
				'placeholder' => 'D4CCube',
				'id' => 'txtitemname'
			],
			'#default_value' => 'D4CCube',
        );
		
		$form['selected_org'] = array(
			'#type' => 'select',
			'#title' => t('Organisation :'),
			'#options' => $organizationList,
			'#attributes' => array(
				'onchange' => 'getDatasets("' . $this->urlCkan . '", '.\Drupal::currentUser()->id().')'
			),
			'#empty_option' => t('----'),
			'#validated' => TRUE
		);

		$form['selected_dataset'] = array(
			'#type' => 'select',
			'#title' => t('Jeu de données :'),
			'#attributes' => array(
				'onchange' => 'loadFields("' . $this->urlCkan . '")'
			),
			'#empty_option' => t('----'),
			'#validated' => TRUE
		);
		
		$form['m2'] = array(
			'#markup' => '<div id="datasetTab">',
		);
		
		$form['Dataset_lies_table'] = array(
            '#type' => 'table',
            '#header' => array(
               	$this->t('Nom'),
				$this->t('Type'),
				$this->t("Parent"),
            ),
            '#attributes' => array('style' => 'width: 100%;height: 450px;overflow:auto;', 'id' => 'edit-table'),
			'#validated' => TRUE
        );
		
		$form['m2_2'] = array(
		  '#markup' => '</div>',
		);   
		
		$form['search'] = array(
				'#type' => 'submit',
				'#value' => $this->t('Création/mise à jour cube'),
		);
		
		$form['dimensions'] = array(
            '#type' => 'textfield',
			'#attributes' => [
				'id' => 'dimensions',
				'style' => 'display:none;'
			],
			'#validated' => TRUE
        );
		
		$form['measures'] = array(
            '#type' => 'textfield',
			'#attributes' => [
				'id' => 'measures',
				'style' => 'display:none;'
			],
			'#validated' => TRUE
        );
		
		return $form;
	}
	
	public function validateForm(array &$form, FormStateInterface $form_state) {
		if ($form_state->getValue('itemid') == '') {
			$form_state->setErrorByName('itemid', $this->t('La sélection d\'un dossier ou Cube est obligatoire.'));
		}
		
	}

	public function submitForm(array &$form, FormStateInterface $form_state){
		$this->config = include(__DIR__ . "/../../config.php");
		
		$itemName = $form_state->getValue('itemName');
		
		$itemId = $form_state->getValue('itemid');
		$idparts= explode(':', $itemId);
		$type = $idparts[0];
		$id = $idparts[1];
		$up = 0;
		if($type == 'item') {
			$up = 1;
		}
		
		$dimensions = $form_state->getValue('dimensions');
		$measures = $form_state->getValue('measures');
		
		$string_dataset_lies = $form_state->getValue('selected_dataset');
		
        $urlCkan = $this->config->ckan->url;
		$cle = $this->config->ckan->api_key;
		$connection = Database::getConnectionInfo('default');
		\Drupal::messenger()->addMessage("cd /home/user-client && java -cp d4cmetadata.jar bpm.metadata.tools.D4CCube -u \"" . $urlCkan . "\" -k \"" . $cle . "\" -o \"infogreffe\" -j \"jdbc:postgresql://" . $connection['default']['host'] . ":" . $connection['default']['port'] . "/" . str_replace('drupal_d4c', 'datastore', $connection['default']['database']) . "\" -l \"" . $connection['default']['username'] . "\" -p \"" . $connection['default']['password'] . "\" -n \"" . $itemName . "\" -d " . $id . " -up " . $up . " -ds \"" . $string_dataset_lies . "\" -dim \"" . $dimensions . "\" -mes \"" . $measures . "\" > cubelogs.txt");
		exec("cd /home/user-client && java -cp d4cmetadata.jar bpm.metadata.tools.D4CCube -u \"" . $urlCkan . "\" -k \"" . $cle . "\" -o \"infogreffe\" -j \"jdbc:postgresql://" . $connection['default']['host'] . ":" . $connection['default']['port'] . "/" . str_replace('drupal_d4c', 'datastore', $connection['default']['database']) . "\" -l \"" . $connection['default']['username'] . "\" -p \"" . $connection['default']['password'] . "\" -n \"" . $itemName . "\" -d " . $id . " -up " . $up . " -ds \"" . $string_dataset_lies . "\" -dim \"" . $dimensions . "\" -mes \"" . $measures . "\" > cubelogs.txt");
	}
}