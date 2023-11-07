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
use Drupal\Core\Render\Element;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\ckan_admin\Utils\HelpFormBase;
use Drupal\ckan_admin\Utils\Tools;

/**
 * Implements an example form.
 */
class MapTilesHarvestForm extends HelpFormBase {
	
	protected $layers;
	protected $selectedLayers;
	/**
	 * {@inheritdoc}
	 */
    
	public function getFormId() {
        
		return 'harvest_map_tiles';
	}


    
	public function buildForm(array $form, FormStateInterface $form_state) {
		$form = parent::buildForm($form, $form_state);
		
		$form['#attached']['library'][] = 'ckan_admin/mapTilesHarvestForm';
		$form['#attached']['drupalSettings']['variable'] = json_encode(array());
		$form['#attached']['drupalSettings']['maxBounds'] = json_encode(array());
	
		$form['lstServer'] = array(
			'#type' => 'select',
			'#title' => t('Sélectionner un serveur / service:'),
			/*'#attributes' => [
				'id' => 'idlayers',
			],*/
			'#options' => array(
				"wms" => t("Service WMS / WMTS"),
				"arcgis" => t("Service ArcGIS")
			),
			/*'#ajax' => [
				'callback' => [$this, 'onLayerChange'],
				'event' => 'change',
				'wrapper' => "data",
			],*/
			
		);
		
		$form['url'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('URL:'),
			'#description' => t('url du serveur.'),
			'#maxlength' => 255,
		);
		
		$form['search'] = array(
			'#type' => 'button',
			'#value' => t('Moissonner'),
			//'#submit' => array([$this, 'searchLayers']),
			'#states' => array(
				"disabled" => array(
					"#edit-url" => array("value" => "")
				),
				"invisibe" => array(
					"#panel-layers" => array("value" => "filled")
				),
			),
			'#ajax' => [
				'callback' => [$this, 'searchLayers'],
				'event' => 'click',
				'wrapper' => "panel-layers",
			],
			'#name' => "search"
        );
		
		$form['panel'] = array(
			'#type' => 'container',
			'#attributes' => [
				'style'=> 'max-height:300px;min-height:40px', 
				'class' => 'form-item',
				'id' => 'panel-global'
			],
		);
		
		$form['panel']['panelLayers'] = array(
			'#type' => 'container',
			'#title' => $this->t('Résultat:'),
			'#attributes' => [
				'id' => 'panel-layers',
				'style'=> 'height:300px;overflow:auto;width:calc(100% - 410px);display:inline-block',
			],
			'#weight' => 2
		);
		
		$form['panel']['panelMap'] = array(
			'#type' => 'container',
			'#attributes' => [
				'id' => 'panel-map',
				'style'=> 'height:300px;overflow:hidden;width:400px;display:inline-block;float:right',
			],
			'#weight' => 3
		);
		
		if ($form_state->getTriggeringElement()["#name"] == 'search' || $form_state->getTriggeringElement()["#name"] == 'selectMapServer') {
			$type = $form_state->getValue('lstServer');
			
			$url = $form_state->getValue('url');
			$urlWsm = $form_state->getUserInput()['selectMapServer'];
			
			if($type == "wms" || ($type == "arcgis" && $urlWsm != "")){
				$url = $type == "wms" ? $url : $urlWsm;
				
				//protection parameters empty
				if(strpos($url, "?") === false){ 
					$url .= "?";
				}
				$params = substr($url, strpos($url, "?"), strlen($url)-1);
				
				if(strpos(strtolower($params), "service") === false){
					$url .= ((is_countable($params) ? count($params) : 0) > 0)? "&service=WMS" : "service=WMS";
					$params .= "service=WMS";
					$form['url']["#value"] = $url;
				}	
				if(strpos(strtolower($params), "request") === false){
					$url .= ((is_countable($params) ? count($params) : 0) > 0)? "&request=GetCapabilities" : "request=GetCapabilities";
					$params .= "request=GetCapabilities";
					$form['url']["#value"] = $url;
				}	
				
				$curl = curl_init($url);
				curl_setopt_array($curl, array(
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_SSL_VERIFYHOST =>  0
				));
				$result = curl_exec($curl);
				//error_log( $url);
				curl_close($curl);
				
				$this->layers = array();
				$this->selectedLayers = array();
				$xml = simplexml_load_string($result);//error_log($xml);
				if($xml != FALSE){
					$ns = $xml->getNamespaces()[""];
				
					if($ns == "http://www.opengis.net/wms"){ //WMS
						//foreach($xml->xpath('/Capability/Layer/Layer') as $l){
						foreach($xml->Capability->Layer as $l){
							$attribution = $l->Attribution->Title."";
							//if($l->attributes()["queryable"] == 1){
								foreach($l->Layer as $lay){
									//if($lay->attributes()["queryable"] == 1){
										$layer = array();
										$layer['name'] = $lay->Name."";
										$layer['label'] = $lay->Title."";
										$layer['provider'] = "custom_wms";
										$layer['url'] = $url;
										if(strpos(strtolower($layer["url"]), "service=wms&request=getcapabilities") !== false){
											$layer["url"] = substr($layer["url"], 0, strpos(strtolower($layer["url"]), "service=wms&request=getcapabilities"));
										} else if(strpos(strtolower($layer["url"]), "request=getcapabilities&service=wms") !== false){
											$layer["url"] = substr($layer["url"], 0, strpos(strtolower($layer["url"]), "request=getcapabilities&service=wms"));
										}
										$layer['minZoom'] = 1;
										$layer['maxZoom'] = 19;
										$layer['type'] = "layer";
										$layer['attribution'] = $attribution;
										$layer['layers'] = $lay->Name."";
										
										foreach($lay->BoundingBox as $bbox){
											if($bbox->attributes()["CRS"] == "EPSG:4326"){
												$layer['bbox'] = array(floatval($bbox->attributes()["minx"]), floatval($bbox->attributes()["miny"]), floatval($bbox->attributes()["maxx"]), floatval($bbox->attributes()["maxy"]));
											}
										}
										
										$this->layers[] = $layer;
										//$this->selectedLayers[] = $layer['name'];
									//}
								}
							//}
						}
					} else if($ns == "http://www.opengis.net/wmts/1.0"){ //WMTS
						//"url": "https:\/\/openstreetmap.data.grandlyon.com\/3857\/wmts\/1.0.0\/osm_grandlyon_nb\/default\/GoogleMapsCompatible\/{z}\/{y}\/{x}.png",
						//"url": "https://openstreetmap.data.grandlyon.com/3857/wmts/1.0.0/osm_grandlyon_nb/default/{TileMatrixSet}/{TileMatrix}/{TileRow}/{TileCol}.png"
						//$xml = simplexml_load_string($result,null, 0, 'ows', true);
						//$xml->registerXPathNamespace('ows', 'http://www.opengis.net/ows/1.1');
						$attribution = $xml->children("http://www.opengis.net/ows/1.1")->ServiceIdentification->children("http://www.opengis.net/ows/1.1")->Title."";
						foreach($xml->Contents->Layer as $lay){
							$layer = array();
							$layer['name'] = $lay->children("http://www.opengis.net/ows/1.1")->Identifier."";
							$layer['label'] = $lay->children("http://www.opengis.net/ows/1.1")->Title."";
							$layer['provider'] = "custom";
							$layer['url'] = $lay->ResourceURL->attributes()["template"]."";
							$layer['minZoom'] = 1;
							$layer['maxZoom'] = 19;
							$layer['type'] = "tile";
							$layer['attribution'] = $attribution;
							$layer['layers'] = "";
							
							if($lay->children("http://www.opengis.net/ows/1.1")->BoundingBox != null && strpos($lay->children("http://www.opengis.net/ows/1.1")->BoundingBox->attributes()["crs"]."", "EPSG::2154") !== false){
								$layer['crs'] = "EPSG:2154";
								$matrixSet = $lay->TileMatrixSetLink->TileMatrixSet."";
								
								foreach($xml->Contents->TileMatrixSet as $tms){
									if($tms->children("http://www.opengis.net/ows/1.1")->Identifier."" == $matrixSet){
										$c = 0;
										$origin = null;
										$resolutions = array();
										$matrixW = array();
										$matrixH = array();
										foreach($tms->TileMatrix as $zoom){
											if($origin == null){
												$origin = explode(" ", $zoom->TopLeftCorner."");
												$origin[0] = (float)$origin[0];
												$origin[1] = (float)$origin[1];
											}
											$resolutions[] = (float)$zoom->ScaleDenominator * 0.00028;
											$matrixW[] = (float)$zoom->MatrixWidth;
											$matrixH[] = (float)$zoom->MatrixHeight;
											$c++;
										}
										
										$layer['origin'] = $origin;
										$layer['resolutions'] = $resolutions;
										$layer['matrixWidths'] = $matrixW;
										$layer['matrixHeights'] = $matrixH;
										$layer['maxZoom'] = $c;
										break;
									}
								}
								$lc = explode(" ", $lay->children("http://www.opengis.net/ows/1.1")->BoundingBox->children("http://www.opengis.net/ows/1.1")->LowerCorner);
								$uc = explode(" ", $lay->children("http://www.opengis.net/ows/1.1")->BoundingBox->children("http://www.opengis.net/ows/1.1")->UpperCorner);
								//$layer['bbox'] = array(floatval($lc[0]), floatval($lc[1]), floatval($uc[0]), floatval($uc[1]));
								
							}
							
							$layer['url'] = str_replace(array("{TileMatrix}","{TileRow}","{TileCol}"), array("{z}","{y}","{x}"), $layer['url']);
							if(preg_match_all("/{(\w+)}/i", $layer['url'], $matches)){
								//$lay->registerXPathNamespace('ows', 'http://www.opengis.net/ows/1.1');
								$jsonLay = json_decode(json_encode($lay), true);
								
								for($i=0; $i<(is_countable($matches[0]) ? count($matches[0]) : 0)-3;$i++){
									$val = $this->array_key_value_r($matches[1][$i], $jsonLay, $lay);
									//error_log("m: ".$matches[1][$i]. " - v: ". $val);
									if($val != null){
										$layer['url'] = str_replace("{".$matches[1][$i]."}", $val, $layer['url']);
									}
								}
								
								//$val = $xml->xpath('//'.$matches[1]);
								//$val = $lay->TileMatrixSetLink->xpath('TileMatrixSet');
							}
							//$layer['url'] = str_replace("{TileMatrixSet}", "default028mm", $layer['url']);
							//$layer['url'] = str_replace("{Style}", "default", $layer['url']);
							//$layer['label'] = $layer['url'];
							$this->layers[] = $layer;
							//echo json_encode($lay->ResourceURL->attributes()["template"]."");
							//$this->selectedLayers[] = $layer['name'];
						}
					} else {
						$form['panel']['panelLayers']['error'] = array(
							'#type' => 'markup',
							'#markup' => '<div id="result-message" class="messages messages--error">La ressource demandée est indisponible ou n\'est pas un service WMS/WMTS</div>'
						);
						$this->layers = array();
					}
				} else {
					$form['panel']['panelLayers']['error'] = array(
						'#type' => 'markup',
						'#markup' => '<div id="result-message" class="messages messages--error">La ressource demandée est indisponible ou n\'est pas un service WMS/WMTS</div>'
					);
					$this->layers = array();
				}
				//echo json_encode($xml);
				//error_log("xml: ".json_encode($xml));
				$res = array();
				/*$form['panel']['panelLayers'] = array(
					'#type' => 'container',
					'#title' => $this->t('Résultat:'),
					'#attributes' => [
						'id' => 'panel-layers',
						'style'=> 'height:300px;overflow:auto;width:calc(100% - 410px);display:inline-block',
					],
					'#weight' => 2
				);*/
				$form['panel']['panelLayers']['subLayers'] = array(
					'#type' => 'container',
					'#title' => $this->t('Résultat:'),
					'#attributes' => [
						'id' => 'sub-layers'
					],
					'#weight' => 2
				);
				array_multisort(array_column($this->layers, 'label'), SORT_ASC, $this->layers);
				foreach($this->layers as $l){
					$el = array(
						'#type' => 'checkbox',
						'#title' => $l["label"],
						'#size' => 10,
						'#maxlength' => 255,
						'#default_value' => in_array($l["name"], $this->selectedLayers) ? 1 : 0,
						/*'#options' => array(
						   0 => t('Yes'),
						   1 => t('No')
						  ),*/
						//'#return_value' => false,
						'#attributes' => array(
							'name' => $l["name"],
							"checked" => in_array($l["name"], $this->selectedLayers) ? "checked" : false,
						),
						'#ajax' => [
							'callback' => [$this, 'onCbChange'],
							'event' => 'change',
						],
						'#name' => "cb-".$l["name"]
					);
					$form['panel']['panelLayers']['subLayers'][] = $el;
				}
			} else if($type == "arcgis"){
				$curl = curl_init($url."?f=pjson");
				curl_setopt_array($curl, array(
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_SSL_VERIFYHOST =>  0
				));
				$result = curl_exec($curl);
				//echo $callUrl;
				curl_close($curl);
				$result = json_decode($result, true);
				$services = array();
				foreach($result["services"] as $serv){
					$servName = $serv["name"];
					$servType = $serv["type"];
					$servUrl;
					if(substr($url, -1) == "/"){
						$url = substr($url, 0, -1);
					}
					$split = explode("/", $url);
					$split2 = explode("/", $servName);
					if($split[count($split)-1] == $split2[0]){
						unset($split2[0]);
						$servName = Tools::implode("/", $split2);
						$servUrl = $url . "/" . $servName . "/" . $servType;
					} else {
						$servUrl = $url . "/" . $servName . "/" . $servType;
					}
					$curl = curl_init($servUrl);
					curl_setopt_array($curl, array(
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_SSL_VERIFYPEER => false,
						CURLOPT_SSL_VERIFYHOST =>  0
					));
					$html = curl_exec($curl);
					//echo $callUrl;
					curl_close($curl);
					$ex = explode("/", $url);
					//$host = substr($url, 0, strpos($url, '/', 3));
					$host = $ex[0]."/".$ex[1]."/".$ex[2];
					
					if(strpos($html, "WMS") !== false){
						preg_match('/<a\s*href="(\S*)"\s*>WMS<\/a>/i', $html, $matches);
						//error_log("ss: ". $servName." - ".$host ." - ". json_encode($matches));
						$wmsUrl = $matches[1];
						$wmsUrl = str_replace("http://", "https://", $wmsUrl);
						//$wmsUrl = $servUrl . "/WMSServer?request=GetCapabilities&service=WMS";
						//$services[$servName] = $wmsUrl;
						$services[$wmsUrl] = $servName ." [WMS]";
					} 
					if (strpos($html, "WMTS") !== false){
						preg_match('/<a\s*href="(\S*)"\s*>WMTS<\/a>/i', $html, $matches);
						$wmtsUrl = $host . $matches[1];
						//$wmtsUrl = $servUrl . "/WMTS/1.0.0/WMTSCapabilities.xml";
						//$services[$servName] = $wmtsUrl;
						$services[$wmtsUrl] = $servName." [WMTS]";
					}
				}
				
				//error_log(json_encode($services));
				/*$form['panel']['panelLayers'] = array(
					'#type' => 'container',
					'#title' => $this->t('Résultat:'),
					'#attributes' => [
						'id' => 'panel-layers',
						'style'=> 'height:300px;overflow:auto;width:calc(100% - 410px);display:inline-block',
					],
					'#weight' => 2
				);*/
				$form['panel']['panelLayers']['lstWms'] = array(
					'#type' => 'select',
					'#title' => t('Sélectionner un service disponible:'),
					'#attributes' => [
						'style'=> 'display:inline-block', 
					],
					'#options' => array_merge(array("" => t("---")), $services),
					//'#default_value' => "ferty",//isset($form_state->getValues()['lstWms']) ? $form_state->getValues()['lstWms'] : '',
					'#ajax' => [
						'callback' => [$this, 'onServiceChange'],
						'event' => 'change',
						'wrapper' => "sub-layers",
						'id' => "dudu"
					],
					'#name' => "selectMapServer",
					'#weight' => 0,
					'#id' => "dudu"
				);
				$form['panel']['panelLayers']['subLayers'] = array(
					'#type' => 'container',
					'#title' => $this->t('Résultat:'),
					'#attributes' => [
						'id' => 'sub-layers'
					],
					'#weight' => 2
				);
			}
			
		} else if (strpos($form_state->getTriggeringElement()["#name"], "cb-") == 0) {
			$name = substr($form_state->getTriggeringElement()["#name"], 3);
			if (($key = array_search($name, $this->selectedLayers)) !== false) {
				unset($this->selectedLayers[$key]);
			} else {
				$this->selectedLayers[] = $name;
			}
			/*$form['panel']['panelLayers'] = array(
				'#type' => 'container',
				'#title' => $this->t('Résultat:'),
				'#attributes' => [
					'id' => 'panel-layers',
					'style'=> 'height:300px;overflow:auto;width:calc(100% - 410px);display:inline-block',
				],
				'#weight' => 2
			);*/
			
			foreach($this->layers as $l){
				$el = array(
					'#type' => 'checkbox',
					'#title' => $l["label"],
					'#size' => 10,
					'#maxlength' => 255,
					'#default_value' => 1,
					//'#return_value' => false,
					'#attributes' => array(
						'name' => $l["name"],
						"checked" => "checked",
					),
					'#ajax' => [
						'callback' => [$this, 'onCbChange'],
						'event' => 'change',
					],
					'#name' => "cb-".$l["name"],
				);
				$form['panel']['panelLayers']['subLayers'][] = $el;
			}
			
		}

		
		$form['visualize'] = array(
			'#type' => 'button',
			'#value' => t('Visualiser'),
			//'#submit' => array([$this, 'searchLayers']),
			'#states' => array(
				"disabled" => array(
					"#edit-url" => array("value" => "")
				),
				"invisibe" => array(
					"#panel-layers" => array("value" => "filled")
				),
			),
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
			'#weight' => 19,
			'#states' => array(
				"visibe" => array(
					"#panel-layers input:first" => array("value" => "0")
				),
			),
        );
		
		return $form;
	}
    
    
	public function submitForm(array &$form, FormStateInterface $form_state)
	{
		$api = new API();
		$res = array();
		foreach($this->layers as $lay){
			if(in_array($lay["name"], $this->selectedLayers)){
				$res[] = $lay;
				$api->addMapLayer($lay);
			}
		}
	
		\Drupal::messenger()->addMessage('Les '. count($res) .' couches sélectionnées ont été sauvegardées');
	}

	public function searchLayers(array &$form, FormStateInterface $form_state)
	{
		
		return $form['panel']["panelLayers"];
	}
	
	public function onServiceChange(array &$form, FormStateInterface $form_state)
	{
			
		return $form['panel']["panelLayers"]["subLayers"];
	}
	
	public function onCbChange(array &$form, FormStateInterface $form_state)
	{
		$name = $form_state->getTriggeringElement()["#attribute"]["name"];
		//$this->selectedLayers[] = $name;
		//echo("ok");
		$this->selectedLayers = "rrr";
		return array("#markup" => $name);
	}
	
	public function refreshMap(array &$form, FormStateInterface $form_state)
	{
		//$form['#attached']['drupalSettings']['ckan_admin']['mapTilesHarvestForm'] = "blabla";
		//return $form['panel']['panelMap'];
		$res = array();
		$bbox = null;
		foreach($this->layers as $lay){
			if(in_array($lay["name"], $this->selectedLayers)){
				$res[] = $lay;
				if($lay["bbox"] != null){
					if($bbox == null) $bbox = array(180, 90, -180, -90);
					$bbox[0] = min($bbox[0], $lay["bbox"][0]);
					$bbox[1] = min($bbox[1], $lay["bbox"][1]);
					$bbox[2] = max($bbox[2], $lay["bbox"][2]);
					$bbox[3] = max($bbox[3], $lay["bbox"][3]);
				}
			}
		}
		$ajax_response = new AjaxResponse();
		$ajax_response->addCommand(new SettingsCommand([
		   'variable' => json_encode($res),
		   'maxBounds' => json_encode($bbox),
		], TRUE));
		return $ajax_response;
	}
	
	public function array_key_value_r( $key, $array, $xml ) {
		if( array_key_exists( $key, $array ) ) {
			if(is_array($array[$key])){//error_log(json_encode($xml));error_log(json_encode($xml[$key]));error_log(json_encode($xml->$key));
				return $xml->$key->children("http://www.opengis.net/ows/1.1")->Identifier."";
			} else {
				return $array[$key];
			}
		} else {
			foreach( $array as $value ) {
				if( is_array( $value ) ) {
					$res = $this->array_key_value_r( $key, $value, $xml->$value );
					if( $res != null ) return $res;
				}
			}
		}
		return null;
	}
}
