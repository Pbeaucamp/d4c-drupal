<?php
namespace Drupal\ckan_admin\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the Example module.
 */
class MapControllerOLD extends ControllerBase {

	/**
	 * Returns a simple page.
	 *
	 * @return array
	 *   A simple renderable array.
	 */
	public function myPage() {

		// $config = json_decode(file_get_contents(__DIR__ ."/../../config.json"));
		$config = include(__DIR__ . "/../../config.php");

		$idUser = 0;
    	if(\Drupal::currentUser()->isAuthenticated()){
        		$idUser = \Drupal::currentUser()->id();
    	}

		

		$element = array(
				'example one' => [
						'#type' => 'inline_template',
						'#template' => '<body class="container-fluid">
						
<div id="main" class="my-map">
	<div id="globalMapPanel">

		<div id="map" class="map" data-map-id="0" data-u="'.$idUser.'">
			<div class="mapInfo" ></div>
		</div>

		<div id="info" class="info"></div>
		<div class="bckPanel">
			<img class="btnBackground" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/img/ic_map_layers_64.png" alt="Fond de Carte" onclick="$(\'#layers-panel\').show();"/>
		</div>
		<div id="layers-panel" class="layersMenu" style="display:none" onmouseleave="$(this).hide()">
			<ul class="layersList">
				<li onclick="changeTile(\'none\')">&lt;Pas de fond de carte&gt;</li>
				<li onclick="changeTile(\'osm\')">OSM</li>
				<li onclick="changeTile(\'toner\')">Toner</li>
				<li onclick="changeTile(\'toner-lite\')">Toner-Lite</li>
			</ul>
		</div>

		<div class="savePanel">
			<img id="saveMap" class="btnBackground" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/img/ic_save_48.png" alt="Sauvegarder"/>
		</div>

		<div class="mapTitle">
			<span>Titre</span>
			<a id="closeMap" >&#10006;</a>
		</div>
	
		<div class="map-loading _hidden">        
			<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" version="1.1" class="d4cwidget-spinner d4cwidget-spinner--svg">    
				<rect x="0" y="0" width="30" height="30" class="d4cwidget-spinner__cell-11"></rect>    
				<rect x="35" y="0" width="30" height="30" class="d4cwidget-spinner__cell-12"></rect>    
				<rect x="70" y="0" width="30" height="30" class="d4cwidget-spinner__cell-13"></rect>    
				<rect x="0" y="35" width="30" height="30" class="d4cwidget-spinner__cell-21"></rect>    
				<rect x="35" y="35" width="30" height="30" class="d4cwidget-spinner__cell-22"></rect>    
				<rect x="70" y="35" width="30" height="30" class="d4cwidget-spinner__cell-23"></rect>    
				<rect x="0" y="70" width="30" height="30" class="d4cwidget-spinner__cell-31"></rect>    
				<rect x="35" y="70" width="30" height="30" class="d4cwidget-spinner__cell-32"></rect>    
				<rect x="70" y="70" width="30" height="30" class="d4cwidget-spinner__cell-33"></rect>
			</svg>    
		</div>
		
		<div class="attributePanel">
			<div class="attr-osm"><a href="http://openlayers.org/" title="OpenLayer">OpenLayer</a> | ©<a href="http://openstreetmap.org/copyright">OpenStreetMap</a></div>
			<div class="attr-stamen"><a href="http://openlayers.org/" title="OpenLayer">OpenLayer</a> | Map tiles by <a href="http://stamen.com">Stamen Design</a>, under <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a>. Data by <a href="http://openstreetmap.org">OpenStreetMap</a>, under <a href="http://www.openstreetmap.org/copyright">ODbL</a>.</div>
		</div>
	</div>
	<div id=rightPanel>
		<ul class="nav nav-tabs nav-justified" role="tablist">
		  	<li class="nav-item active">
		    	<a class="nav-link " id="data-tab" data-toggle="tab" href="#data" role="tab" aria-controls="data" aria-selected="true">Jeux de données</a>
		  	</li>
		  	<li class="nav-item">
		    	<a class="nav-link" id="maps-tab" data-toggle="tab" href="#maps" role="tab" aria-controls="maps" aria-selected="false">Vos cartes</a>
		  	</li>
		</ul>
		<div class="data-panel tab-content">
			<div class="tab-pane fade active in" id="data" role="tabpanel" aria-labelledby="data-tab">
				<ul class="data-list">
				
				</ul>
			</div>
				<div class="tab-pane fade" id="maps" role="tabpanel" aria-labelledby="maps-tab">
					<ul class="maps-list">
				
				</ul>
				</div>
			
		</div>
	</div>
</div>				
	<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/js/jquery-1.12.0.min.js"></script>
	<script>
			$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/css/bootstrap.custom.css\" rel=\"stylesheet\">");
			$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/css/ol.css\" rel=\"stylesheet\">");
			$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/css/map_bfc.css\" rel=\"stylesheet\">");

	</script>
	<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/js/ol.js"></script>
	<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/js/d3.min.js"></script>
	<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/js/underscore-min.js"></script>
	<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/js/map_bfc.js"></script>
	<script>

			loadGlobalMap();
	</script>
	
						
</body>',
						
				],
		);
		return $element;
	}

}

