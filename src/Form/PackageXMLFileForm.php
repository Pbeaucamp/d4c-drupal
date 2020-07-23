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



/**
 * Implements an example form.
 */
class PackageXMLFileForm extends HelpFormBase {
	
	protected $tiles;
	/**
	 * {@inheritdoc}
	 */
    
	public function getFormId() {
		return 'extension_package';
	}

public function buildForm(array $form, FormStateInterface $form_state) {
        $form = parent::buildForm($form, $form_state);
		
        $form['#attached']['library'][] = 'ckan_admin/PackagesForm.form';
		

        $api = new API();
	
		return $form;
	}
    
	public function submitForm(array &$form, FormStateInterface $form_state)
	{
		
		
       
	}
    

}
