<?php
namespace Drupal\ckan_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ckan_admin\Utils\Logger;

/**
 * Provides route responses for the Example module.
 */
class portailController extends ControllerBase {

	/**
	 * Returns a simple page.
	 *
	 * @return array
	 *   A simple renderable array.
	 */
	public function myPage() {
		//$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
		$config = include(__DIR__ . "/../../config.php");

		$title = 'Jeux de données';
		$visualisations = '
			<h3> Visualisations</h3>
			<ul id="list-visu" class="list-group"></ul>
		';
		$themes = '
			<h3>Thèmes</h3>
			<ul id="list-theme" class="list-group">
				<input id="input-theme" type="hidden" class="hidden-filter">
			</ul>
		';

		$producteursPart = '';
		$typesPart = '';
		if ($config->client->client_is_observatory && $config->client->client_is_observatory == "true") {
			$producteursPart = '';
			$typesPart = '
				<h3>Type</h3>
				<ul id="list-type" class="list-group"></ul>
			';
		}
		else if ($config->client->nutch === true) {
			$title = 'pages de sites';
			$visualisations = '';
			$themes = '';

			$producteursPart = '
				<h3>Origine des sites</h3>
				<ul id="list-producteur" class="list-group"></ul>
			';
		}
		else {
			$producteursPart = '
				<h3>Organisations</h3>
				<ul id="list-producteur" class="list-group"></ul>
			';
		}

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

						.d4c-portail-map {
						    height: 238px;
    						min-height: 100px;
							overflow: hidden;
						}

						.leaflet-bottom  {
							display: none !important;
						}
						
						</style>
						<div id ="main" class="widget-opendata">

					        <div id="filter" class="col-md-2 content-body" >
					        	<div class="ng-scope d4c-portail-map" ng-app="d4c-widgets">
						
									<d4c-dataset-context style="height:100px" context="mapemprise" mapemprise-dataset="nodataset" class="ng-scope">
												<div class="row" >
													
													<div class="col-md-12 col-sm-4">
														<div class="d4c-box d4c-map-wp">
														<d4c-map context="mapemprise" location="' . $config->client->default_bounding_box . '" scroll-wheel-zoom="false" class="ng-isolate-scope">

																<d4c-map-layer border-color="#FFFFFF" border-opacity="1" border-pattern="solid" border-size="1" caption="false" color="#0e7ce3" context="mapemprise" exclude-from-refit="false"  picto="d4c-" show-marker="false" size-function="linear"> 
																</d4c-map-layer> 
															</d4c-map> 
														</div>
													</div>
												</div>
									</d4c-dataset-context>
								</div>

            					<h1> <span id="nb_jeux">0</span> ' . $title .'</h1>
								<input id="input-tag" type="text" class="hidden-filter">
                                						
								<div class="form-group">
									<label for="sel1">Trier par:</label>
									<select class="form-control" id="sel1">
										<option value="null" selected>Trier par</option>
										<!-- <option value="date">Date modification</option> -->
										<option value="alpha">Ordre alphabétique</option>
										<option value="alpha_reverse">Ordre anti alphabétique</option>
										<option value="date_recent">Récemment modifiés</option>
										<option value="date_old">Anciennement modifiés</option>
										<option value="imported_recent">Récemment importés</option>
										<option value="imported_old">Anciennement importés</option>
										<option value="enregistrement_plus">Le + d\'enregistrement</option>
										<option value="enregistrement_minus">Le - d\'enregistrement</option>
										<option value="telechargement_plus">Le + de téléchargements</option>
										<option value="telechargement_minus">Le - de téléchargements</option>
										<option value="populaire_plus">Les + populaires</option>
										<option value="populaire_minus">Les - populaires</option>
										<option value="producteur">Organisation</option>
										<!-- <option value="granularite">Echelle territoriale</option>
										<option value="reutilisation">Réutilisations</option> -->
									</select>
								</div>

								<div id="actif-filters">
									<h2>Filtres actifs <span id="reset-filters">Tout effacer</span></h2>
									<ul class="jetons"></ul>
								</div>
			
								<h2> Filtres </h2>
								
								<form id="search-form">
									<div class="input-group" id="barreRecherche">
										<input id="search_bar" type="text"  class="form-control" aria-label="recherche" placeholder="Rechercher un jeu de données...">
										<div class="input-group-btn">
											<button class="btn-filter btn btn-default" type="submit">
											<i class="glyphicon glyphicon-search"></i>
											</button>
										</div>
									</div>
									
								</form> 

								' . $visualisations . '
								' . $producteursPart . '
								' . $typesPart . '

								<input id="input-producteur" type="hidden" class="hidden-filter">
								<input id="input-types" type="hidden" class="hidden-filter">
								<input id="input-map-coordinate" type="hidden" class="hidden-filter">
								<input id="input-format" type="hidden" class="hidden-filter">
								<!--<h3>Echelle territoriale</h3>
								<ul id="list-granularite" class="list-group"></ul>
								<input id="input-granularite" type="hidden" class="hidden-filter">-->

								<!-- <h3>Formats ressources</h3>
								<ul id="list-format" class="list-group">-->

								</ul>
								<input id="input-format" type="hidden" class="hidden-filter">

								<h3>Mots Clés</h3>
								<ul id="list-tag" class="list-group"></ul>
								' . $themes . '

								<h2>Télécharger la liste des jeux de donnéess</h2>
								<ul id="list-cat" class="list-group">
									<li class="list-item" data-cat="csv"><i class="fa fa-file" aria-hidden="true"></i>CSV <span class="number_element"><i class="fa fa-download" aria-hidden="true"></i></span></li>
									<li class="list-item" data-cat="xls"><i class="fa fa-file" aria-hidden="true"></i>XLS <span class="number_element"><i class="fa fa-download" aria-hidden="true"></i></span></li>
									<li class="list-item" data-cat="json"><i class="fa fa-file" aria-hidden="true"></i>JSON <span class="number_element"><i class="fa fa-download" aria-hidden="true"></i></span></li>
								</ul>

        					</div>

					        <div class="col-md-10" style="display: flex; flex-direction: column;" >
								
								<div id="datasets">
										 
								</div>
								<div class="row-md-12">
					                <nav id="pagination">
					                    <ul class="pagination">
					                    </ul>
					                </nav>
					            </div>
								<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" version="1.1" class="d4cwidget-spinner d4cwidget-spinner--svg hidden">    <rect x="0" y="0" width="30" height="30" class="d4cwidget-spinner__cell-11"></rect>    <rect x="35" y="0" width="30" height="30" class="d4cwidget-spinner__cell-12"></rect>    <rect x="70" y="0" width="30" height="30" class="d4cwidget-spinner__cell-13"></rect>    <rect x="0" y="35" width="30" height="30" class="d4cwidget-spinner__cell-21"></rect>    <rect x="35" y="35" width="30" height="30" class="d4cwidget-spinner__cell-22"></rect>    <rect x="70" y="35" width="30" height="30" class="d4cwidget-spinner__cell-23"></rect>    <rect x="0" y="70" width="30" height="30" class="d4cwidget-spinner__cell-31"></rect>    <rect x="35" y="70" width="30" height="30" class="d4cwidget-spinner__cell-32"></rect>    <rect x="70" y="70" width="30" height="30" class="d4cwidget-spinner__cell-33"></rect></svg>
					        </div>
    					</div>

    <script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/jquery-3.2.1.js"/>
	<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/bootstrap.min.js"/>
    <script src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/script_portail.js"></script>
	<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/i18n.js"/>
	<script src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/jquery-ui-1.12.1.custom/jquery-ui.min.js"/>
	<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/angular-carto.js"/>
	<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/widget-card-dataset.js"/>
	<link href="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/visualisation.css" rel="stylesheet" />
	<link href="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/font-awesome.min.css" rel="stylesheet" />
	<link href="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/client.css" rel="stylesheet" />
	<script src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/jquery-3.2.1.js"></script>
	<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="'. $config->client->routing_prefix . '/modules/ckan_admin/js/libraries.js"></script>
	<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/lib/qtip/jquery.qtip.min.js"></script>	
	<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/lib/fullcalendar/moment.min.js"></script>
	<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/lib/fullcalendar/fullcalendar.min.js"></script>
	<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/lib/fullcalendar/lang/fr.js"></script>
	<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/angular-core.js"></script>
	<script type="text/javascript">
		$(".d4c-content").html($(".d4c-content").html().replace(/\\\{\\\{/g,\'\{\{\').replace(/\\\}\\\}/g,\'}}\').replace(/\\\{/g,\'\{\').replace(/\\\}/g,\'}\'));
		$(".main-container").removeClass("container").removeClass("main-container").css( "margin-top", "-20px" ).css( "margin-bottom", "-45px" );
		var mod = angular.module(\'d4c.core.config\', []);

		mod.factory("config", [function() {
			return {
				ID_DATASET: "",
				HOST: "'.$config->client->domain.'"
			}
		}]);
	</script>
    <script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/angular-visu.js"></script>
  
	<script src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/bootstrap.min.js"></script>
    <script src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/script_portail.js"></script>
	<script>
			$(".main-container").removeClass("container").removeClass("main-container").css( "margin-top", "-20px" ).css( "margin-bottom", "-45px" );
			
			$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/bootstrap.custom.min.css\" rel=\"stylesheet\">");
			$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/style.css\" rel=\"stylesheet\">");
			$("head").append("<link rel=\"stylesheet\" type=\"text/css\" href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/jquery-ui-1.12.1.custom/jquery-ui.theme.min.css\">");
    		$("head").append("<link rel=\"stylesheet\" type=\"text/css\" href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/jquery-ui-1.12.1.custom/jquery-ui.min.css\">");
            $("head").append("<link rel=\"stylesheet\" type=\"text/css\" href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/font-awesome.min.css\">");
			//$("head").append("<meta http-equiv=\"Content-Security-Policy\" content=\"upgrade-insecure-requests\">");
	</script>
						
						'
					
				],
		);
	$element['#attached']['library'][] = 'ckan_admin/visu.angular';
		return $element;
		
	}

}

