<?php
/**
 * @file
 * Contains \Drupal\search_api_solr_admin\Form\QueryForm.
 */

namespace Drupal\ckan_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ckan_admin\Utils\HelpFormBase;
use Drupal\ckan_admin\Utils\Api;

/**
 * Implements an example form.
 */
class GeolocForm extends HelpFormBase
{


	/**
	 * {@inheritdoc}
	 */
	public function getFormId()
	{
		return 'geoloc_form';
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state)
	{
        $form = parent::buildForm($form, $form_state);
		$form['#attached']['library'][] = 'ckan_admin/GeolocForm.form';
		$this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
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

		for ($i = 0; $i < count($orgs[result]); $i++) {
			$organizationList[$orgs[result][$i][name]] = $orgs[result][$i][display_name];
		}

		$form['text_message1'] = [
			'#prefix' => '<p>',
			'#suffix' => '</p>',
			'#markup' => $this->t('Pour créer un jeu de données avec cartographie, vous devez sélectionner un jeu de données contenant une ressource CSV 
				<br/> et renseigner les informations obligatoires.'),
		];

		$form['text_message2'] = [
			'#prefix' => '<p>',
			'#suffix' => '</p>',
			'#markup' => $this->t("Un fichier GeoJson ainsi que les nuages de points seront créés pour les jeux de données contenant une colonne géolocalisable.
				<br/> Pour les jeux de données ne contenant pas la cardinalité, il sera possible de récupérer si l'adresse est présente dans les données à l'aide de l'api BAN."),
		];

		$form['selected_org'] = array(
			'#type' => 'select',
			'#title' => t('*Organisation:'),
			'#options' => $organizationList,
			'#attributes' => array(
				'onchange' => 'getDatasets("' . $this->urlCkan . '", '.\Drupal::currentUser()->id().')'
			),
			'#empty_option' => t('----'),
		);

		$form['selected_dataset'] = array(
			'#type' => 'select',
			'#title' => t('*Jeu de données:'),
			'#attributes' => array(
				'onchange' => 'getResources("' . $this->urlCkan . '")'
			),
			'#empty_option' => t('----'),
			'#validated' => TRUE,
		);

		$form['selected_resource'] = array(
			'#type' => 'select',
			'#title' => t('*Ressource:'),
			'#attributes' => array(
				'onchange' => 'getFields("' . $this->urlCkan . '")'
			),
			'#empty_option' => t('----'),
			'#validated' => TRUE,
		);
        
        
        

		$form['separator'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('Séparateur (; par défaut):'),
			'#default_value' => t(';'),
		);

		$form['encoding'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('Encoding (UTF-8 par défaut):'),
			'#default_value' => t('UTF-8'),
		);

		$form['type_geoloc'] = array(
			'#type' => 'radios',
			'#title' => t('Type de jeu données'),
			'#attributes' => array(
				'onchange' => 'updateUI()'
			),
			'#default_value' => 'geoloc',
			'#options' => array('geoloc' => t('Avec une colonne géolocalisation (latitude/longitude avec séparateur)'), 'latlong' => t('Avec 2 colonnes (latitude/longitude)'), 'address' => t('Avec adresse')),
		);
		
		$form['div_adress'] = array(
			'#markup' => '<div id="div_adress">',
		);
		
		$form['selected_numero'] = array(
			'#type' => 'select',
			'#title' => t('Numéro:'),
			'#empty_option' => t('----'),
			'#validated' => TRUE,
		);
		
		$form['selected_rue'] = array(
			'#type' => 'select',
			'#title' => t('Rue:'),
			'#empty_option' => t('----'),
			'#validated' => TRUE,
		);
		
		$form['selected_ville'] = array(
			'#type' => 'select',
			'#title' => t('Ville:'),
			'#empty_option' => t('----'),
			'#validated' => TRUE,
		);

		$form['selected_address'] = array(
			'#type' => 'select',
			'#title' => t('Adresse:'),
			'#empty_option' => t('----'),
			'#validated' => TRUE,
		);

		$form['selected_postalcode'] = array(
			'#type' => 'select',
			'#title' => t('Code Postal:'),
			'#empty_option' => t('----'),
			'#validated' => TRUE,
		);
		
		$form['div_adress_end'] = array(
			'#markup' => '</div>',
		);
		
		$form['div_latlong'] = array(
			'#markup' => '<div id="div_latlong">',
		);
		
		$form['selected_lat'] = array(
			'#type' => 'select',
			'#title' => t('Latitude:'),
			'#empty_option' => t('----'),
			'#validated' => TRUE,
		);
		
		$form['selected_long'] = array(
			'#type' => 'select',
			'#title' => t('Longitude:'),
			'#empty_option' => t('----'),
			'#validated' => TRUE,
		);
		
		$form['div_latlong_end'] = array(
			'#markup' => '</div>',
		);

		$form['apply'] = array(
			'#type' => 'submit',
			'#value' => $this->t('Envoyer'),
		);

		return $form;
	}

	public function validateForm(array &$form, FormStateInterface $form_state)
	{
		$selectedDataset = $form_state->getValue('selected_dataset');
		$selectedResource = $form_state->getValue('selected_resource');
		$selectedTypeMap = $form_state->getValue('selected_type_map');

		$typeGeoloc = $form_state->getValue('type_geoloc');

		// $buildGeoloc = ($typeGeoloc == 'geoloc') ? 'false' : 'true';
		$selectedAddress = $form_state->getValue('selected_address');
		$selectedPostalCode = $form_state->getValue('selected_postalcode');
		$selectedNumero = $form_state->getValue('selected_numero');
		$selectedRue = $form_state->getValue('selected_rue');
		$selectedVille = $form_state->getValue('selected_ville');
		$selectedLat = $form_state->getValue('selected_lat');
		$selectedLong = $form_state->getValue('selected_long');

		if ($selectedDataset == '') {
			$form_state->setErrorByName('selected_dataset', $this->t('Ce champ est obligatoire.'));
		}
//        if ($selectedTypeMap  == '') {
//			$form_state->setErrorByName('selected_type_map', $this->t('Ce champ est obligatoire.2'));
//		}
		if ($selectedResource == '') {
			$form_state->setErrorByName('selected_resource', $this->t('Ce champ est obligatoire.'));
		}
		if ($typeGeoloc == '') {
			$form_state->setErrorByName('type_geoloc', $this->t('Ce champ est obligatoire.'));
		}
		
		if($typeGeoloc == 'address') {
			
		}
		else if($typeGeoloc == 'latlong') {
			if($selectedLat == '') {
				$form_state->setErrorByName('selected_lat', $this->t('Ce champ est obligatoire.'));
			}
			if($selectedLong == '') {
				$form_state->setErrorByName('selected_long', $this->t('Ce champ est obligatoire.'));
			}
		}
		
		// if (!$buildGeoloc) {
			// if ($selectedAddress == '') {
				// $form_state->setErrorByName('selected_address', $this->t('Ce champ est obligatoire.'));
			// }
			// if ($selectedPostalCode == '') {
				// $form_state->setErrorByName('selected_postalcode', $this->t('Ce champ est obligatoire.'));
			// }
		// }
	}

	public function submitForm(array &$form, FormStateInterface $form_state)
	{

		$selectedDataset = $form_state->getValue('selected_dataset');
		$selectedResource = $form_state->getValue('selected_resource');
		$selectedTypeMap = $form_state->getValue('selected_type_map');

		$selectedSeparator = $form_state->getValue('separator');
		$selectedEncoding = $form_state->getValue('encoding');

		$typeGeoloc = $form_state->getValue('type_geoloc');

		$selectedAddress = $form_state->getValue('selected_address');
		$selectedPostalCode = $form_state->getValue('selected_postalcode');
		$numero = $form_state->getValue('selected_numero');
		$rue = $form_state->getValue('selected_rue');
		$ville = $form_state->getValue('selected_ville');
		$lat = $form_state->getValue('selected_latitude');
		$long = $form_state->getValue('selected_longitude');

		$buildGeoloc = ($typeGeoloc == 'address') ? '1' : '0';
		if($buildGeoloc == '0') {
			$buildGeoloc = ($typeGeoloc == 'latlong') ? '2' : '0';
		}
		
		// $nodeUrl = 'https://localhost:1337/';
		$pathUserClient = '/home/user-client';
		$pathUserClientData = $pathUserClient . '/data';
		$onlyOneAddress = 'false';
        
        
        
        
		// $minimumScore = 60;
		// $pathTempFile = $pathUserClientData . '/temp';

		// $command = '/usr/bin/java -jar ' . $pathUserClientData . '/bpm.geoloc.creator_1.0.0.jar 
		// 	-g ' . $buildGeoloc . ' 
		// 	-n "' . $nodeUrl . '" 
		// 	-np "' . $pathUserClientData . '" 
		// 	-d "' . $this->urlCkan . '" 
		// 	-k "' . $this->config->ckan->api_key . '" 
		// 	-pid "' . $selectedDataset . '" 
		// 	-rid "' . $selectedResource . '" 
		// 	-rs "' . $selectedSeparator . '" 
		// 	-re "' . $selectedEncoding . '" 
		// 	-oa ' . $onlyOneAddress . ' 
		// 	-a "' . $selectedAddress . '" 
		// 	-p "' . $selectedPostalCode . '" 
		// 	-s ' . $minimumScore . ' 
		// 	-f "' . $pathTempFile . '"';
		' -g $g -n $n -np $np -d $d -k $k -pid $pid -rid $rid -rs "$rs" -re "$re" -oa $oa -a "$a" -p "$p" -s $s -f $f'
		$command = '/usr/bin/java -jar /home/user-client/data/bpm.geoloc.creator_1.0.0.jar -g ' . $buildGeoloc . ' -n https://localhost:1337/ -np /home/user-client/data/clusters -d ' . $this->urlCkan . ' -k ' . $this->config->ckan->api_key . 
		' -pid ' . $selectedDataset . ' -rid ' . $selectedResource . ' -rs ' . $selectedSeparator . ' -re ' . $selectedEncoding . ' -f /home/user-client/data/temp -s 10';
		
		if($buildGeoloc == '1') {
			if($selectedPostalCode == '') {
				$onlyOneAddress = 'true';
				$command = $command . ' -p ' . $selectedPostalCode;
			}
			$command = $command . ' -a ' . $numero . ' ' . $rue . ' ' . $ville . ' ' . $selectedAddress;
		}
		else if($buildGeoloc == '2') {
			$command = $command . ' -lat ' . $lat . ' -long ' . $long;
		}
		
		drupal_set_message($command);

		// $command = $pathUserClientData . '/geoloc.sh "' . $buildGeoloc . '" "' . $this->urlCkan . '" "' . $this->config->ckan->api_key . '" "' . $selectedDataset . '" "' . $selectedResource . '" "' . $selectedSeparator . '" "' . $selectedEncoding . '" "' . $onlyOneAddress . '" "' . $selectedAddress . '" "' . $selectedPostalCode . '"';
		// //error_log($command);

		// $output = shell_exec($command);
        
        
        // $validOutput = explode(" ", $output);
        
        if ($validOutput[count($validOutput)-1]=='defined.' && $validOutput[count($validOutput)-2]=='correctly' && $validOutput[count($validOutput)-3]=='not'){
            drupal_set_message($output, 'error');
        }
        else{
            drupal_set_message($output, 'status', false);
			sleep(20);
			$api = new Api();
			$api->calculateVisualisations($selectedDataset);
        }
        
		
	}
}
