<?php
namespace Drupal\ckan_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\Logger;

/**
 * Provides route responses for the Example module.
 
 This file uses a library under MIT Licence :

ods-widgets -- https://github.com/opendatasoft/ods-widgets
Copyright (c) 2014 - Opendatasoft

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
 
 */
class MapController extends ControllerBase {


	public function myPage(Request $request) {
		\Drupal::service('page_cache_kill_switch')->trigger();

		$config = include(__DIR__ . "/../../config.php");

		$element = array(
				'example one' => [
						'#type' => 'inline_template',
						
						'#template' => '<body>

        <div class="d4c-content">

            <main class="main--mapbuilder d4c-mapbuilder__main">
    
				<div class="d4c-mapbuilder__container"
					 ng-class="{\'d4c-mapbuilder__map--with-searchbox\': interfaceMode === \'preview\' && mapbuilderController.mapObject.value.searchBox,
								\'d4c-mapbuilder__map--with-right-panel\': interfaceMode === \'preview\' && mapbuilderController.mapObject.value.groups.length}"
					 ng-app="d4c.frontend"
					 ng-controller="MapbuilderController as mapbuilderController">
					<div class="d4c-mapbuilder__map-container" ng-class="{\'d4c-mapbuilder__container--preview\': interfaceMode === \'preview\'}">
						<d4c-map class="d4c-mapbuilder__map"
								 ng-if="mapbuilderController.mapObject"
								 no-refit="true"
								 auto-resize="true"
								 location="' . $config->client->map_bounding_box . '"
								 display-control="interfaceMode === \'preview\' && mapbuilderController.mapObject.value.groups.length && mapbuilderController.mapObject.value.layerSelection"
								 display-control-single-layer="mapbuilderController.mapObject.value.singleLayer"
								 search-box="interfaceMode === \'preview\' && mapbuilderController.mapObject.value.searchBox"
								 auto-geolocation="false"
								 display-legend="interfaceMode === \'preview\'"
								 map-config="mapbuilderController.mapObject.value"
								 dynamic-config="mapbuilderController.dynamicConfig"
								 sync-to-object="mapbuilderController.mapObject.value.mapPresets"></d4c-map>
						<d4c-mapbuilder-main-panel ng-if="mapbuilderController.mapObject"
												   map-object="mapbuilderController.mapObject"
												   map-storage="mapbuilderController.mapStorage"></d4c-mapbuilder-main-panel>
					</div>
					<d4c-mapbuilder-datasets-panel ng-if="interfaceMode === \'edition\'" map-config="mapbuilderController.mapObject"></d4c-mapbuilder-datasets-panel>
				</div>
				
			</main>



        </div>
        
		<script src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/jquery-3.2.1.js"></script>
        <script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/bootstrap.min.js"></script>
        <script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/libraries.js"></script>
        <script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/angular-core.js"></script>
        <script type="text/javascript">
        	$(".d4c-content").html($(".d4c-content").html().replace(/\\\{\\\{/g,\'\{\{\').replace(/\\\}\\\}/g,\'}}\').replace(/\\\{/g,\'\{\').replace(/\\\}/g,\'}\'));
		$(".main-container").removeClass("container").removeClass("main-container").css( "margin-top", "-20px" ).css( "margin-bottom", "-45px" );
            var mod = angular.module(\'d4c.core.config\', []);

            mod.factory("config", [function() {
                return {
                    HOST: "'.$config->client->domain.'",
					LANGUAGE: "fr"
                }
            }]);
        </script>
        <script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/i18n.js"></script>
        <script src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/supported-browsers-message.js" type="text/javascript"></script>
    <script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/angular-carto.js"></script>


   <script>
			$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/visualisation.css\" rel=\"stylesheet\">");
			$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/normalize.css\" rel=\"stylesheet\">");
			//$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/d4cui.css\" rel=\"stylesheet\">");
			//$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/bootstrap.min.css\" rel=\"stylesheet\">");
			$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/'.$config->client->css_file.'\" rel=\"stylesheet\">");
			$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/font-awesome.min.css\" rel=\"stylesheet\">");
			$("head").append("<base href=\"'. $config->client->routing_prefix . '/carte/\">");
	</script>

</body>',
						
				],
		);
		//$element['#attached']['library'][] = 'ckan_admin/visu.angular';
		
		return $element;
		
		/*$response = new Response();
		$response->setContent(render($element));
		$response->headers->set('Content-Type', 'text/html');
		
		return $response;*/
	}
	
	public function manage(Request $request) {		
		$uri = \Drupal::urlGenerator()->generateFromRoute('<front>', [], ['absolute' => TRUE]);
		$id = uniqid();

		$location = $uri . 'carte/+' . $id . '/edit/';
		// header($location);
		
		// $response = new Response();
		// $response->headers->set('Content-Type', 'text/html');
		
		// return $response;
		$response = new RedirectResponse($location);
		$response->send();
		return;
	}

}

