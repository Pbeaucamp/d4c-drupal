<?php
namespace Drupal\ckan_admin\Controller;

use Drupal\Core\Controller\ControllerBase;

class ApiController extends ControllerBase {

	/**
	 * Returns a simple page.
	 *
	 * @return array
	 *   A simple renderable array.
	 */
	public function myPage() {
		$element = array(
			'api' => [
				'#type' => 'inline_template',
				'#template' => '
					<div class="interior-article-container">
						<div id="swagger-ui">&nbsp;</div>
					</div>
					<link href="https://unpkg.com/swagger-ui-dist@3.12.1/swagger-ui.css" rel="stylesheet" type="text/css" />
					<script src="https://unpkg.com/swagger-ui-dist@3.12.1/swagger-ui-standalone-preset.js"></script>
					<script src="https://unpkg.com/swagger-ui-dist@3.12.1/swagger-ui-bundle.js"></script>
					<script>
						window.onload = function() {
							// Build a system
							const ui = SwaggerUIBundle({
								url: "/modules/ckan_admin/openapi_data4citizen.yaml",
								dom_id: \'#swagger-ui\',
								deepLinking: true,
								presets: [
									SwaggerUIBundle.presets.apis,
								],
								plugins: [
									SwaggerUIBundle.plugins.DownloadUrl
								],
							})
							window.ui = ui
						}
					</script>
				',	
			],
		);
		return $element;
	}

}

