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
class MapTilesForm extends HelpFormBase {
	
	protected $tiles;
	/**
	 * {@inheritdoc}
	 */
    
	public function getFormId() {
		return 'manage_map_tiles';
	}

	public function buildForm(array $form, FormStateInterface $form_state) {
        $form = parent::buildForm($form, $form_state);
		
        $form['#attached']['library'][] = 'ckan_admin/mapTilesHarvestForm';
		$form['#attached']['drupalSettings']['variable'] = json_encode(array());
		$form['#attached']['drupalSettings']['maxBounds'] = json_encode(array());

        $api = new API();
		$this->tiles = $api->getMapLayers()["layers"];
        
        $values = array();
		$values["new"] = t("Ajouter une couche");
		foreach($this->tiles as $tile){
			$values[$tile["name"]] = $tile["label"];
		}
		
        $form['lstTiles'] = array(
			'#type' => 'select',
			'#title' => t('Sélectionner un layer:'),
			/*'#attributes' => [
				'id' => 'idlayers',
			],*/
			'#options' => $values,
			'#ajax' => [
				'callback' => [$this, 'onLayerChange'],
				'event' => 'change',
				'wrapper' => "data",
			],
			
		);
        //{"name": "osm", "label":"OpenStreetMap", "provider": "osm", "url":"", "minZoom": 0, "maxZoom": 19, "type":"TILE", "key":"", "attribution":""}
		$form['name'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('Nom/id:'),
			'#description' => t('nom unique du layer.'),
			'#states' => array(
				"enabled" => array(
					"#edit-lsttiles" => array("value" => "new")
				),
			),
			/*'#attributes' => [
				'id' => 'my-ajax-wrapper',
			],*/
			'#prefix' => '<div id="data">',
		);
        
        $form['label'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('Label:'),
			'#description' => t('nom affiché sur le visualisateur.'),
			
		);
		
		$form['lstProvider'] = array(
			'#type' => 'select',
			'#title' => t('Fournisseur:'),
			'#options' =>array(
				   'custom'=>'custom / service WMTS','custom_wms'=>'custom / service WMS','osm'=>'osm','mapbox'=>'mapbox','mapbox.street'=>'mapbox.street','mapbox.satellite'=>'mapbox.satellite','jawg.streets'=>'jawg.streets','jawg.terrain'=>'jawg.terrain','jawg.dark'=>'jawg.dark','osmtransport'=>'osmtransport','stamen.toner'=>'stamen.toner','stamen.terrain'=>'stamen.terrain','stamen.watercolor'=>'stamen.watercolor','mapquest'=>'mapquest','opencycle'=>'opencycle'
			   ) ,
			'#attributes' => [
				'id' => 'edit-lstprovider',
			],
			'#description' => t('voici une liste de fournisseurs pré-configurés. Choisir custom pour tout autre serveur de fond de carte ou configuration spécifique')
		);
        
		$form['url'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('URL:'),
			'#description' => t('url du serveur.'),
			'#maxlength' => 1024,
			'#states' => array(
				"visible" => array(
					array("#edit-lstprovider" => array("value" => "custom")),
					"or",
					array("#edit-lstprovider" => array("value" => "custom_wms"))
				),
			)
		);
		
		$form['minZoom'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('minZoom:'),
			'#attributes' => array(
				' type' => 'number'
			),
			'#description' => t('zoom minimal disponible. (compris entre 0 et 22)'),
			'#states' => array(
				"visible" => array(
					array("#edit-lstprovider" => array("value" => "custom")),
					"or",
					array("#edit-lstprovider" => array("value" => "mapbox")),
					"or",
					array("#edit-lstprovider" => array("value" => "osm")),
					"or",
					array("#edit-lstprovider" => array("value" => "custom_wms"))
				),
			)
		);
		
		$form['maxZoom'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('maxZoom:'),
			'#attributes' => array(
				' type' => 'number'
			),
			'#description' => t('zoom maximal disponible. (compris entre 0 et 22)'),
			'#states' => array(
				"visible" => array(
					array("#edit-lstprovider" => array("value" => "custom")),
					"or",
					array("#edit-lstprovider" => array("value" => "mapbox")),
					"or",
					array("#edit-lstprovider" => array("value" => "osm")),
					"or",
					array("#edit-lstprovider" => array("value" => "custom_wms"))
				),
			)
		);
		
        $form['lstType'] = array(
			'#type' => 'select',
			'#title' => t('Type:'),
			'#options' => array(
				   'tile'=>'Fond de Carte','layer'=>'Sous-couche WMS'
			),
			'#description' => t('catégorie de la couche géographique.'),
			'#states' => array(
				"visible" => array(
					/*array("#edit-lstprovider" => array("value" => "custom")),
					"or",*/
					array("#edit-lstprovider" => array("value" => "custom_wms"))
				),
			),
			'#attributes' => [
				'id' => 'edit-lsttype',
			],
		);
        
        $form['key'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('Clé personnelle:'),
			'#description' => t('clé d\'accès au service cartographique.'),
			'#states' => array(
				"visible" => array(
					array("#edit-lstprovider" => array("value" => "custom")),
					"or",
					array("#edit-lstprovider" => array("value" => "mapquest")),
					"or",
					array("#edit-lstprovider" => array("value" => "jawg.streets")),
					"or",
					array("#edit-lstprovider" => array("value" => "jawg.dark")),
					"or",
					array("#edit-lstprovider" => array("value" => "jawg.terrain")),
					"or",
					array("#edit-lstprovider" => array("value" => "osmtransport")),
					"or",
					array("#edit-lstprovider" => array("value" => "mapbox")),
					"or",
					array("#edit-lstprovider" => array("value" => "mapbox.street")),
					"or",
					array("#edit-lstprovider" => array("value" => "mapbox.satellite")),
					"or",
					array("#edit-lstprovider" => array("value" => "opencycle")),
					"or",
					array("#edit-lstprovider" => array("value" => "custom_wms"))
				),
			)
		);
        
        $form['attribution'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('Contribution:'),
			'#description' => t('texte légal d\'utilisation de la carte.'),
			'#states' => array(
				"visible" => array(
					array("#edit-lstprovider" => array("value" => "custom")),
					"or",
					array("#edit-lstprovider" => array("value" => "custom_wms"))
				),
			)
		);
		
		$form['mapId'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('MapBox ID:'),
			'#states' => array(
				"visible" => array(
					"#edit-lstprovider" => array("value" => "mapbox")
				),
			),
		);
		
		$form['layer'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('Nom du Layer:'),
			'#states' => array(
				"visible" => array(
					array("#edit-lstprovider" => array("value" => "custom_wms")/*, "#edit-lsttype" => array("value" => "layer")*/)
				),
			),
			'#suffix' => '</div>',
		);
		
		$form['panelMap'] = array(
			'#type' => 'container',
			'#attributes' => [
				'id' => 'panel-map',
				'style'=> 'height:300px;width:400px;display:none;margin-bottom:5px',
			],
		);
		
		$form['visualize'] = array(
			'#type' => 'button',
			'#value' => t('Visualiser'),
			//'#submit' => array([$this, 'searchLayers']),
			'#ajax' => [
				'callback' => [$this, 'refreshMap'],
				'event' => 'click',
				'wrapper' => "panel-map",
			],
			/*'#attributes' => array(
   				'onclick' => 'initMap('.json_encode($res).')'
			),*/
			'#name' => "visu"
        );
        
        $form['validate'] = array(
			'#type' => 'submit',
			'#value' => t('Enregistrer'),
			'#weight' => 19
        );
        
		$form['delete'] = array(
			'#type' => 'submit',
			'#value' => t('Supprimer'),
			'#weight' => 19,
			'#submit' => array([$this, 'deleteLayer']),
			'#states' => array(
				"disabled" => array(
					"#edit-lsttiles" => array("value" => "new")
				),
			),
        );
		
		/*$form['my_ajax_container'] = [
			'#type' => 'container',
			'#attributes' => [
				'id' => $ajax_wrapper,
			]
		];*/
        
        
		/*if($_POST["getD"]){
				 
		$data = $this->getCustomView($_POST["getD"]);
		echo json_encode($data,true);

		   
		}*/
	
		return $form;
	}
    
    
	public function submitForm(array &$form, FormStateInterface $form_state)
	{
		
		$tile = $this->buildLayer($form, $form_state);
		$stat = "";
		$api = new API();
        if($form_state->getValue('lstTiles') == "new"){
			//add tile
			$api->addMapLayer($tile);
			$stat = "créée";
		} else {
			//edit tile
			$api->updateMapLayer($tile);	
			$stat = "mise à jour";
		}
        
		\Drupal::messenger()->addMessage('La couche ' . $tile["label"] . " a été " . $stat . " avec succès");
       
	}
    
    public function onLayerChange(array &$form, FormStateInterface $form_state) {
		$id = $form_state->getValue('lstTiles');
		$selected;
		foreach($this->tiles as $tile){
			if($tile["name"] == $id){
				$selected = $tile;
				break;
			}
		}
		
		/*if($selected["provider"] == "custom_wms"){
			$selected["provider"] = "custom";
		}*/

		$form['name']["#value"] = $selected["name"];
		$form['label']["#value"] = $selected["label"];
		$form['lstProvider']["#value"] = $selected["provider"];
		$form['url']["#value"] = $selected["url"];
		$form['minZoom']["#value"] = $selected["minZoom"];
		$form['maxZoom']["#value"] = $selected["maxZoom"];
		$form['lstType']["#value"] = $selected["type"];
		$form['key']["#value"] = $selected["key"];
		$form['attribution']["#value"] = $selected["attribution"];
		$form['layer']["#value"] = $selected["layers"];
		$form['mapId']["#value"] = $selected["mapId"];/**/
		return array($form['name'],$form['label'],$form['lstProvider'],$form['url'],$form['minZoom'],$form['maxZoom'],$form['lstType'],$form['key'],$form['attribution'],$form['mapId'],$form['layer']/**/) ;

	}

    public function deleteLayer(array &$form, FormStateInterface $form_state) {
		$id = $form_state->getValue('lstTiles');
		$api = new API();
		$api->deleteMapLayer($id);
		
		\Drupal::messenger()->addMessage('La couche a été supprimée');
	}
	
	public function refreshMap(array &$form, FormStateInterface $form_state)
	{
		$lay = $this->buildLayer($form, $form_state);
		$res = array();
		$bbox = null;
		
		$res[] = $lay;
		/*if($lay["bbox"] != null){
			if($bbox == null) $bbox = array(180, 90, -180, -90);
			$bbox[0] = min($bbox[0], $lay["bbox"][0]);
			$bbox[1] = min($bbox[1], $lay["bbox"][1]);
			$bbox[2] = max($bbox[2], $lay["bbox"][2]);
			$bbox[3] = max($bbox[3], $lay["bbox"][3]);
		}*/
		
		$ajax_response = new AjaxResponse();
		$ajax_response->addCommand(new SettingsCommand([
		   'variable' => json_encode($res),
		   'maxBounds' => json_encode($bbox),
		], TRUE));
		return $ajax_response;
	}
	
	public function buildLayer(array &$form, FormStateInterface $form_state) {
		$tile = array();
		$tile["name"] = $form_state->getValue('name');
		$tile["label"] = $form_state->getValue('label');
		$tile["provider"] = $form_state->getValue('lstProvider');
		$tile["url"] = $form_state->getValue('url');
		if(strpos(strtolower($tile["url"]), "service=wms&request=getcapabilities") !== false){
			$tile["url"] = substr($tile["url"], 0, strpos(strtolower($tile["url"]), "service=wms&request=getcapabilities"));
		} else if(strpos(strtolower($tile["url"]), "request=getcapabilities&service=wms") !== false){
			$tile["url"] = substr($tile["url"], 0, strpos(strtolower($tile["url"]), "request=getcapabilities&service=wms"));
		}
		$tile["minZoom"] = $form_state->getValue('minZoom');
		$tile["maxZoom"] = $form_state->getValue('maxZoom');
		$tile["type"] = $form_state->getValue('lstType');
		$tile["key"] = $form_state->getValue('key');
		$tile["attribution"] = $form_state->getValue('attribution');
		$tile["mapId"] = $form_state->getValue('mapId');
		$tile["layers"] = $form_state->getValue('layer');
		
		/*if($tile["provider"] == "custom" && $tile["type"] == "layer"){
			$tile["provider"] = "custom_wms";
		}*/
		 error_log(json_encode($tile));
		return $tile;
	}
}
