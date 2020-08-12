<?php
namespace Drupal\ckan_admin\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the Example module.
 */
class SliderController extends ControllerBase {

	/**
	 * Returns a simple page.
	 *
	 * @return array
	 *   A simple renderable array.
	 */
	public function myPage() {
		
		//$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
		$config = json_decode(file_get_contents(__DIR__ ."/../../config.json"));
		$element = array(
				'example one' => [
					'#type' => 'inline_template',
					'#template' => '<style type="text/css">
		
						.main-container.container.js-quickedit-main-content {
						    width: 100%!important;
							padding: 0px!important;
						    margin: 0px!important;
						}

						.d4c-box {

						    padding: 0 !important; 
						    margin-bottom: 0 !important; 
						}
						.d4cwidget-map__map {
						    height: 238px !important;
						    min-height: 100px !important;
						}

						.d4cwidget-map {
						    height: 238px !important;
    						min-height: 100px !important;
						}

						.leaflet-bottom  {
							display: none !important;
						}
						
						</style><div id ="main" class="widget-opendata">
						
		 <a href="/admin/config/data4citizen/userstory"  class="btn btn-info" role="button"> button test </a>

        <div class="slider-pro" id="my-slider">
			<div class="sp-slides">
				<!-- Slide 1 -->
				<div class="sp-slide">
					<img class="sp-image" src="path/to/image1.jpg"/>
				</div>
		
				<!-- Slide 2 -->
				<div class="sp-slide">
					<p>Lorem ipsum dolor sit amet</p>
				</div>
		
				<!-- Slide 3 -->
				<div class="sp-slide">
					<h3 class="sp-layer">Lorem ipsum dolor sit amet</h3>
					<p class="sp-layer">consectetur adipisicing elit</p>
				</div>
			</div>
		</div>

    <script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/jquery-3.2.1.js"/>
<script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/bootstrap.min.js"/>
    <script src="/sites/default/files/api/portail_d4c/js/script_portail.js"></script>

<script src="libs/js/jquery-1.11.0.min.js"></script>
<script src="dist/js/jquery.sliderPro.min.js"></script>
	<script type="text/javascript">
		jQuery( document ).ready(function( $ ) {
			$( '#my-slider' ).sliderPro();
		});
	</script>


<script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/i18n.js"/>
<script src="/sites/default/files/api/portail_d4c/js/jquery-ui-1.12.1.custom/jquery-ui.min.js"/>
<script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/angular-carto.js"/>
<script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/widget-card-dataset.js"/>
<link href="/sites/default/files/api/portail_d4c/css/visualisation.css" rel="stylesheet" />
<link href="/sites/default/files/api/portail_d4c/css/font-awesome.min.css" rel="stylesheet" />
<link href="/sites/default/files/api/portail_d4c/css/client.css" rel="stylesheet" />


<script src="/sites/default/files/api/portail_d4c/js/jquery-3.2.1.js"></script>
<script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/bootstrap.min.js"></script>

<script type="text/javascript" src="/modules/ckan_admin/js/libraries.js"></script>
<script type="text/javascript" src="/sites/default/files/api/portail_d4c/lib/qtip/jquery.qtip.min.js"></script>	
<script type="text/javascript" src="/sites/default/files/api/portail_d4c/lib/fullcalendar/moment.min.js"></script>
<script type="text/javascript" src="/sites/default/files/api/portail_d4c/lib/fullcalendar/fullcalendar.min.js"></script>
<script type="text/javascript" src="/sites/default/files/api/portail_d4c/lib/fullcalendar/lang/fr.js"></script>
<script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/filtre-map-emprise.js"></script>
        
	<script type="text/javascript">
		$(".d4c-content").html($(".d4c-content").html().replace(/\\\{\\\{/g,\'\{\{\').replace(/\\\}\\\}/g,\'}}\').replace(/\\\{/g,\'\{\').replace(/\\\}/g,\'}\'));
		$(".main-container").removeClass("container").removeClass("main-container").css( "margin-top", "-20px" ).css( "margin-bottom", "-45px" );
		var mod = angular.module(\'d4c.core.config\', []);

		mod.factory("config", [function() {
			return {
				ID_DATASET: "'.$id.'",
				HOST: "'.$config->client->domain.'"
			}
		}]);
	</script>
    <script type="text/javascript" src="/sites/default/files/api/portail_d4c/js/angular-visu-map.js"></script>
  
	<script src="/sites/default/files/api/portail_d4c/js/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="/sites/default/files/api/portail_d4c/js/bootstrap.min.js"></script>
    <script src="/sites/default/files/api/portail_d4c/js/script_portail.js"></script>
	<script>
			$(".main-container").removeClass("container").removeClass("main-container").css( "margin-top", "-20px" ).css( "margin-bottom", "-45px" );
			
			$("head").append("<link href=\"/sites/default/files/api/portail_d4c/css/bootstrap.custom.min.css\" rel=\"stylesheet\">");
			$("head").append("<link href=\"/sites/default/files/api/portail_d4c/css/style.css\" rel=\"stylesheet\">");
			$("head").append("<link href=\"/sites/default/files/api/portail_d4c/css/'.$config->client->css_file.'\" rel=\"stylesheet\">");
			$("head").append("<link rel=\"stylesheet\" type=\"text/css\" href=\"/sites/default/files/api/portail_d4c/js/jquery-ui-1.12.1.custom/jquery-ui.theme.min.css\">");
    		$("head").append("<link rel=\"stylesheet\" type=\"text/css\" href=\"/sites/default/files/api/portail_d4c/js/jquery-ui-1.12.1.custom/jquery-ui.min.css\">");
            $("head").append("<link rel=\"stylesheet\" type=\"text/css\" href=\"/sites/default/files/api/portail_d4c/css/font-awesome.min.css\">");
			$("head").append("<link rel="stylesheet" href="dist/css/slider-pro.min.css"/>");
			//$("head").append("<meta http-equiv=\"Content-Security-Policy\" content=\"upgrade-insecure-requests\">");
	</script>
						
						'
					
				],
		);
	$element['#attached']['library'][] = 'ckan_admin/visu.angular';
		return $element;
		
	}

}

