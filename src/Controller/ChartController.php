<?php
namespace Drupal\ckan_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\ckan_admin\Utils\Api;

/**
 * Provides route responses for the Example module.
 *
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
 *
 */
class ChartController extends ControllerBase {


	public function myPage(Request $request) {

		$config = json_decode(file_get_contents(__DIR__ ."/../../config.json"));
		$api = new Api();
		
				
		$element = array(
			'example one' => [
					'#type' => 'inline_template',
					
					'#template' => '
		<main class="main--chartbuilder">
				<div ng-app="d4c.frontend"
					 ng-controller="BigController">
					<div class="row">
						<div class="col-md-6 ng-cloak">
							<div class="d4c-box">
								<div d4c-highcharts-chart
									 context="fakeMultiChartContext"
									 contexts="contexts"
									 parameters="chartContext.dataChart"></div>
								<d4c-embed-control embed-type="chartbuilder"
												   widget-code="widgetCode.code"
												   anonymous-access="true"></d4c-embed-control>
							</div>
							 <d4c-notification-handler></d4c-notification-handler>
						</div>
						<div class="col-md-6 ng-cloak">
							<div advanced-chart-controls
									
								 urlsynchronize
									
								 advanced="true"
								 can-save="true"
								 chart-context="chartContext"
								 context="fakeMultiChartContext"></div>
						</div>
						
					</div>
				</div>
		</main>
		
        <script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/bootstrap.min.js"></script>
		<script src="/sites/default/files/api/portail_d4c/js/jquery-3.2.1.js"></script>
        <script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/libraries.js"></script>
        <script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/angular-core.js"></script>
        <script type="text/javascript">
        	//$(".d4c-content").html($(".d4c-content").html().replace(/\\\{\\\{/g,\'\{\{\').replace(/\\\}\\\}/g,\'}}\').replace(/\\\{/g,\'\{\').replace(/\\\}/g,\'}\'));
			$(".main-container").removeClass("container").removeClass("main-container").css( "margin-top", "-20px" ).css( "margin-bottom", "-45px" ).css( "background-color", "#eee" );
            var mod = angular.module(\'d4c.core.config\', []);
            
            mod.factory("config", [function() {
                return {
                    HOST: "'.$config->client->domain.'"
                }
            }]);
        </script>
        <script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/i18n.js"></script>
        <script src="/sites/default/files/api/portail_d4c/js/supported-browsers-message.js" type="text/javascript"></script>
        <script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/angular-chart.js"></script>

		<script>
			$("head").append("<link href=\"/sites/default/files/api/portail_d4c/css/visualisation.css\" rel=\"stylesheet\">");
			$("head").append("<link href=\"/sites/default/files/api/portail_d4c/css/normalize.css\" rel=\"stylesheet\">");
			//$("head").append("<link href=\"/sites/default/files/api/portail_d4c/css/d4cui.css\" rel=\"stylesheet\">");
			//$("head").append("<link href=\"/sites/default/files/api/portail_d4c/css/bootstrap.min.css\" rel=\"stylesheet\">");
			$("head").append("<link href=\"/sites/default/files/api/portail_d4c/css/'.$config->client->css_file.'\" rel=\"stylesheet\">");
			$("head").append("<link href=\"/sites/default/files/api/portail_d4c/css/font-awesome.min.css\" rel=\"stylesheet\">");
			$("head").append("<base href=\"/chart/\">");
		</script>',
						
			],
		);
		
		return $element;
	}
	
	public function myFrame(Request $request) {

		$config = json_decode(file_get_contents(__DIR__ ."/../../config.json"));		
				
		$element = '
		<head>
			<base href="/chart/frame">
			<link href="/sites/default/files/api/portail_d4c/css/visualisation.css" rel="stylesheet">
			<link href="/sites/default/files/api/portail_d4c/css/normalize.css" rel="stylesheet">
			<link href="/sites/default/files/api/portail_d4c/css/'.$config->client->css_file.'" rel="stylesheet">
			<style>main{position:absolute;top:0;bottom:0;right:0;left:0}main>div{position:absolute;top:0;bottom:0;right:0;left:0}.chart-container{position:absolute;top:0;bottom:0;right:0;left:0}</style>
		</head>
		<body>
		<main class="main--chartbuilder">
			<div ng-app="d4c.frontend" ng-controller="BigController" fullsize>
				<div no-controls="noControls"
					 advanced-chart-controls
					 advanced="true"
					 chart-context="chartContext"
					
					 urlsynchronize
					
					 context="fakeMultiChartContext"></div>
				<div class="ng-cloak chart-container">
					<div d4c-highcharts-chart
						 context="fakeMultiChartContext"
						 contexts="contexts"
						 parameters="chartContext.dataChart"></div>
				</div>
			</div>
		</main>
		
		<script src="/sites/default/files/api/portail_d4c/js/jquery-3.2.1.js"></script>
        <script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/bootstrap.min.js"></script>
        <script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/libraries.js"></script>
        <script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/angular-core.js"></script>
        <script type="text/javascript">
        	//$(".d4c-content").html($(".d4c-content").html().replace(/\\\{\\\{/g,\'\{\{\').replace(/\\\}\\\}/g,\'}}\').replace(/\\\{/g,\'\{\').replace(/\\\}/g,\'}\'));
			$(".main-container").removeClass("container").removeClass("main-container").css( "margin-top", "-20px" ).css( "margin-bottom", "-45px" );
            var mod = angular.module(\'d4c.core.config\', []);
            
            mod.factory("config", [function() {
                return {
                    HOST: "'.$config->client->domain.'"
                }
            }]);
        </script>
        <script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/i18n.js"></script>
        <script src="/sites/default/files/api/portail_d4c/js/supported-browsers-message.js" type="text/javascript"></script>
        <script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/angular-chart.js"></script>

		
		</body>';
		
		echo $element;
		
		$response = new Response();
		$response->headers->set('Content-Type', 'text/html');
		return $response;
	}

}

