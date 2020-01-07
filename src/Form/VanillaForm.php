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
				$form['search'] = array(
					'#type' => 'submit',
					'#value' => $this->t('Création/mise à jour métadata'),
			);
		
			return $form;
	}

	public function submitForm(array &$form, FormStateInterface $form_state){
		$this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
        $urlCkan = $this->config->ckan->url;
		$cle = $this->config->ckan->api_key;
		$connection = Database::getConnectionInfo('default');
		drupal_set_message(print_r($connection,true));
		drupal_set_message("cd /home/user-client && java -cp d4cmetadata.jar bpm.metadata.tools.TestD4cMetadata -u \"" . $urlCkan . "\" -k \"" . $cle . "\" -o \"infogreffe\" -j \"jdbc:postgresql://" . $connection['default']['host'] . ":" . $connection['default']['port'] . "/" . str_replace('drupal_d4c', 'datastore', $connection['default']['database']) . "\" -l \"" . $connection['default']['username'] . "\" -p \"" . $connection['default']['password'] . "\" -d 5");
		exec("cd /home/user-client && java -cp d4cmetadata.jar bpm.metadata.tools.TestD4cMetadata -u \"" . $urlCkan . "\" -k \"" . $cle . "\" -o \"infogreffe\" -j \"jdbc:postgresql://" . $connection['default']['host'] . ":" . $connection['default']['port'] . "/" . str_replace('drupal_d4c', 'datastore', $connection['default']['database']) . "\" -l \"" . $connection['default']['username'] . "\" -p \"" . $connection['default']['password'] . "\" -d 5 > log.txt");
	}
}