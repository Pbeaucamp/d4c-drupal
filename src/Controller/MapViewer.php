<?php

namespace Drupal\ckan_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\Logger;

class MapViewer extends ControllerBase
{

	public function myPage(Request $request)
	{
		\Drupal::service('page_cache_kill_switch')->trigger();

		$idMap = $request->get("idmap");

		$api = new Api;
		$maps = $api->getMaps(null, $idMap);

		$map = $maps[0]->map_json;
		$map = json_decode($map, true);

		$mapWidget = $map["widgetCode"];

		$config = include(__DIR__ . "/../../config.php");
		$element = array(
			'example one' => [
				'#type' => 'inline_template',
				'#template' => '<body>
					<div class="d4c-content ng-scope" ng-app="d4c-widgets">
						' . $mapWidget . '
					</div>

					<script src="' . $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/jquery-3.2.1.js"></script>
					<script type="text/javascript" src="' . $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/bootstrap.min.js"></script>
					<script type="text/javascript" src="' . $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/libraries.js"></script>
					<script type="text/javascript" src="' . $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/angular-core.js"></script>
					<script type="text/javascript">
						$(".main-container").removeClass("container").removeClass("main-container").css( "margin-top", "-20px" ).css( "margin-bottom", "-45px" );
							var mod = angular.module(\'d4c.core.config\', []);

							mod.factory("config", [function() {
								return {
									HOST: "' . $config->client->domain . '",
									LANGUAGE: "fr"
								}
							}]);
					</script>
					<script type="text/javascript" src="' . $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/i18n.js"></script>
					<script src="' . $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/supported-browsers-message.js" type="text/javascript"></script>
					<script type="text/javascript" src="' . $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/angular-carto.js"></script>
					<script>
						$("head").append("<link href=\"' . $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/visualisation.css\" rel=\"stylesheet\">");
						$("head").append("<link href=\"' . $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/normalize.css\" rel=\"stylesheet\">");
						$("head").append("<link href=\"' . $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/font-awesome.min.css\" rel=\"stylesheet\">");
						$("head").append("<base href=\"' . $config->client->routing_prefix . '/carte/\">");
					</script>
				</body>',
			],
		);
		return $element;
	}
}
