<?php

namespace Drupal\ckan_admin\Utils;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\SettingsCommand;

abstract class HelpFormBase extends FormBase {
	
	protected $bookmarkId;
	
	public function getBookmark(){
		return $this->getFormId();
	}
	
	public function buildForm(array $form, FormStateInterface $form_state) {
		$form['#attached']['library'][] = 'ckan_admin/helpForm';
		//$form['#attached']['drupalSettings']['bookmark'] = "";
		$form['help'] = array(
			'#type' => 'button',
			'#value' => t('Aide'),
			'#weight' => -100,
			/*'#ajax' => [
				'callback' => [$this, 'help'],
				'event' => 'click',
				'wrapper' => "help",
			],*/
			'#attributes' => [
				'id' => 'help',
				'style'=> 'float:right;border-radius:5px;',
				'onclick' => 'showHelp(event,"'.$this->getBookmark().'")', 
			],
			"#name" => "help"
        );
		$form['mHelp'] = array(
			'#markup' => '<div id="modalHelp"></div>',
		);

		return $form;
	}
	
	
	/*public function help(array &$form, FormStateInterface $form_state) {
		$ajax_response = new AjaxResponse();
		$ajax_response->addCommand(new SettingsCommand([
		   'bookmark' => $this->getBookmark()
		], TRUE));
		return $ajax_response;
	}*/
	
}