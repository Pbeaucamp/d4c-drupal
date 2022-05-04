<?php
namespace Drupal\ckan_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Drupal\ckan_admin\Utils\Api;

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
class ExportPLUController extends ControllerBase {


	

	/**
	 * Returns a simple page.
	 *
	 * @return array
	 *   A simple renderable array.
	 */
	public function myPage(Request $request) {
		// $config = json_decode(file_get_contents(__DIR__ ."/../../config.json"));
		$config = include(__DIR__ . "/../../config.php");

		$content = '<body>

        <div class="d4c-content">

			<main class="main--page">
				<div id="page-" ng-app="d4c.frontend" ng-controller="PageController">
					<ng-include src="layout"></ng-include>
				</div>
				<script type="text/ng-template" id="templates/custom.html">
						<div class="page-layout ng-cloak">
							<style type="text/css" d4c-bind-angular-content="blocks.custom_css"></style>
							<div d4c-bind-angular-content="blocks.main"></div>
						</div>

				</script>
			</main>
        </div>
        
        <script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/libraries.js"></script>
        <script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/bootstrap.min.js"></script>
        <script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/angular-core.js"></script>
        <script type="text/javascript">
            var mod = angular.module("d4c.core.config", []);
			
			mod.factory("domainConfig", [function() {
                return {};
            }]);

            mod.factory("config", [function() {
                return {
                    HOST: "'.$config->client->domain.'"
                }
            }]);
        </script>
        <script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/i18n.js"></script>
        <script src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/supported-browsers-message.js" type="text/javascript"></script>
    <script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/angular-exportplu.js"></script>

   <script>
			$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/visualisation.css\" rel=\"stylesheet\">");
			$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/normalize.css\" rel=\"stylesheet\">");
			$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/d4cui.css\" rel=\"stylesheet\">");
			//$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/bootstrap.min.css\" rel=\"stylesheet\">");
			$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/'.$config->client->css_file.'\" rel=\"stylesheet\">");
			$("head").append("<base href=\"/\">");
	</script>

</body>';
		
		
		$headless = $request->query->get('headless');
		if($headless == "true"){
			echo $content . '<footer class="ng-scope"></footer>';
			$response = new Response();

		$response->headers->set('Content-Type', 'text/html');
			return $response;
		}
		else {
		//$api = new API();

		$element = array(
				'example one' => [
						'#type' => 'inline_template',
						
						'#template' => $content
						
				]
		);
		//$element['#attached']['library'][] = 'ckan_admin/anfr.angular';
		return $element;
		
		}
	}

}

