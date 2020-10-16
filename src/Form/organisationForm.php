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
use Drupal\ckan_admin\Utils\HelpFormBase;

/**
 * Implements an example form.
 */
class organisationForm extends HelpFormBase {
	
	
	/**
	 * {@inheritdoc}
	 */
	public function getFormId() {
		return 'organisation_form';
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {	
        $form = parent::buildForm($form, $form_state);
		$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
		
		$config->set('ids', null)->save();
		
		$organisations = $config->get('organisations');
		
		$form['chercher'] = array(
				'#type' => 'search',
				'#title' => $this->t('Chercher :'),
		);
	
		$form['ids'] = array(
				'#type' => 'checkboxes',
				'#options' => $organisations,
				'#title' => $this->t('Choix des organnnisations'),
		);
	
		$form['search'] = array(
				'#type' => 'submit',
				'#value' => $this->t('Envoyer'),
		);
	
		return $form;
	}
	public function submitForm(array &$form, FormStateInterface $form_state)
	{
		$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
		$config->set('ids', array_filter($form_state->getValue('ids')))->save();
		
		if($config->get('ids_cron') != null) {
			$config->set('ids_cron', array_merge($config->get('ids_cron'), array_filter($form_state->getValue('ids'))))->save();
		}
		else {
			$config->set('ids_cron', array_filter($form_state->getValue('ids')))->save();
		}
		
		
		$requete = $form_state->getValue('chercher');

		if($requete != null && $requete!= "" && $requete != " ")
		{
			$query = Query::callSolrServer("http://www.data.gouv.fr/api/1/organizations/?page_size=10000&q=" .urlencode($requete). "");
			$results = json_decode($query);
		
			$organisations = array();
			
			foreach ($results->data as $orga)
			{
				$organisations[$orga->id]=$orga->name;
			}
			
			
			$config->set('organisations',$organisations)->save();
		}
		
		DataSet::sendDataSet();
		
		if(!empty($config->get('ids')))
		{
			\Drupal::messenger()->addMessage('Organisations moissonnées :', 'status');
			foreach ($config->get('names') as $nom)
			{
				\Drupal::messenger()->addMessage($nom,'status');
			}
			
		}
	
	}
}