<?php
namespace Drupal\ckan_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\Logger;
use Drupal\ckan_admin\Utils\Tools;
use Drupal\data_bfc\Utils\UserManager;
use Drupal\data_bfc\Utils\VanillaApiManager;
use \Parsedown;

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
class VisualisationController extends ControllerBase {

	private $config;
	private $locale;
    
	public function __construct(){
		$this->config = include(__DIR__ . "/../../config.php");

		$this->locale = json_decode(file_get_contents(__DIR__ ."/../../locales.fr.json"), true);
    }

	public function myPage(Request $request, $tab) {
		$id = $request->query->get('id');
		$resourceId = $request->query->get('resourceId');
		$location = $request->query->get('location');
		return $this->myPage2($id, $tab, $resourceId, $location);
	}

	/**
	 * Returns a simple page.
	 *
	 * @return array
	 *   A simple renderable array.
	 */
	public function myPage2($id, $tab, $resourceId, $location = null) {
		\Drupal::service('page_cache_kill_switch')->trigger();

		$host = \Drupal::request()->getHost();
		$protocol = \Drupal::request()->getScheme()."://";
		
		$api = new API();
		
		$pageId = $id;

		$dataset = $api->getPackageShow2($id, "", true, false, $resourceId, true);
		if (!isset($dataset["metas"]["id"])) {
			//Dataset is not found we set a 404 page
			$imports = $this->buildImports($id, null, null, null, null, null, null, null);
			$ctx = "";

			$errorPage = '<body>
				<div class="d4c-content">			
					<header class="ng-scope"></header>
					<main class="main--dataset">
						<div class="container-fluid d4c-app-explore-dataset ng-cloak"
								ng-app="d4c.frontend"
								ng-controller="ExploreDatasetController"
								d4c-dataset-context
								ng-init="toggleState={expandedFilters: false};"
								context="ctx"
								ctx-urlsync="true"
								ctx-dataset-schema="' . $ctx . '"
								ctx-selected-resource-id="' . $resourceId . '">
							<div class="d4c-dataset-visualization__header">
								<h1 class="d4c-dataset-visualization__dataset-title">
									<div class="box_3">
										<button class="d4c-button" ng-click="goBackToSearch()">
											<i class="fa fa-angle-left" aria-hidden="true"></i>
											RETOUR AUX RESULTATS DE RECHERCHE
										</button>
									</div>
								</h1>
								<div class="d4c-error-404">
									<span>La resource avec l\'ID \'' . $id . '\' n\'existe pas ou n\'est pas disponible.</span>
								</div>
							</div>
						</div>
					</main>
				</div>
				' . $imports . '
			</body>';

			$element = array(
				'example one' => [
					'#type' => 'inline_template',
					'#template' => $errorPage,
							
				],
			);
			$element['#attached']['library'][] = 'ckan_admin/visu.angular';
			return $element;
		}

		// We redefine the $id variable to be the dataset id because we can use the name id of the dataset in the url
		$id = $dataset["metas"]["id"];

		// $dataset["metas"]["description"] = strip_tags($dataset["metas"]["description"]);
		// $dataset["metas"]["notes"] = strip_tags($dataset["metas"]["notes"]);

		$name = $dataset["metas"]["title"];
		//Removing HTML tags
		$Parsedown = new Parsedown();
		$description = $Parsedown->text($dataset["metas"]["notes"]);

		$dateModified = $dataset["metas"]["modified"];
		$keywords = $dataset["metas"]["keyword"];
		$licence = $dataset["metas"]["license"];
        $metadataExtras = $dataset["metas"]["extras"];

		$url = $protocol . $host . $this->config->client->routing_prefix . "/visualisation?id=" . $dataset["datasetid"];
		
		$availableResources = $dataset["metas"]["resources"];

		$exports = array();
		$resourcesid = "";
		//Last update date for the data (resources)
		// $lastDataUpdateDate = null;
		foreach ($availableResources as $value) {
            if($value['format'] == 'CSV' || $value['format'] == 'XLS' || $value['format'] == 'XLSX'){
		 		$resourcesid = $value['id'];
		 	}

			if($value['format'] != 'CSV' && $value['format'] != 'XLS' && $value['format'] != 'XLSX' && $value['format'] != 'GeoJSON' && $value['format'] != 'JSON' && $value['format'] != 'KML' && $value['format'] != 'SHP'){
				$res = array();
				$res["@type"] = "DataDownload";
				$res["encodingFormat"] = $value['format'];
				$res["contentUrl"] = $protocol . $host . $this->config->client->routing_prefix . "/d4c/api/datasets/1.0/" . $dataset["datasetid"] . "/alternative_exports/" . $value['id'];
				$exports[] = $res;
			}

			// //Defining the last data update date
			// $currentDataDate = $value['last_modified'];
			// if (!isset($currentDataDate)) {
			// 	$currentDataDate = $value['created'];
			// }

			// //We compare with the others resources
			// if (!isset($lastDataUpdateDate) || strtotime($currentDataDate) > strtotime($lastDataUpdateDate)) {
			// 	$lastDataUpdateDate = $currentDataDate;
			// }
		}

		if($resourcesid != ""){
			$res = array();
			$res["@type"] = "DataDownload";
			$res["encodingFormat"] = "CSV";
			$res["contentUrl"] = $protocol . $host . $this->config->client->routing_prefix . "/d4c/api/records/2.0/downloadfile/format=csv&use_labels_for_header=true&resource_id=" . $resourcesid;
			$exports[] = $res;
			
			$res = array();
			$res["@type"] = "DataDownload";
			$res["encodingFormat"] = "JSON";
			$res["contentUrl"] = $protocol . $host . $this->config->client->routing_prefix . "/d4c/api/records/2.0/downloadfile/format=json&resource_id=" . $resourcesid;
			$exports[] = $res;
			
			$res = array();
			$res["@type"] = "DataDownload";
			$res["encodingFormat"] = "Excel";
			$res["contentUrl"] = $protocol . $host . $this->config->client->routing_prefix . "/d4c/api/records/2.0/downloadfile/format=xls&use_labels_for_header=true&resource_id=" . $resourcesid;
			$exports[] = $res;
			
			if($isGeo){
				$res = array();
				$res["@type"] = "DataDownload";
				$res["encodingFormat"] = "GeoJSON";
				$res["contentUrl"] = $protocol . $host . $this->config->client->routing_prefix . "/d4c/api/records/2.0/downloadfile/format=geojson&resource_id=" . $resourcesid;
				$exports[] = $res;
				
				$res = array();
				$res["@type"] = "DataDownload";
				$res["encodingFormat"] = "KML";
				$res["contentUrl"] = $protocol . $host . $this->config->client->routing_prefix . "/d4c/api/records/2.0/downloadfile/format=kml&resource_id=" . $resourcesid;
				$exports[] = $res;
				
				$res = array();
				$res["@type"] = "DataDownload";
				$res["encodingFormat"] = "Shapefile";
				$res["contentUrl"] = $protocol . $host . $this->config->client->routing_prefix . "/d4c/api/records/2.0/downloadfile/format=shp&resource_id=" . $resourcesid;
				$exports[] = $res;
			}
		}
		
		//Build interface
		$body = $this->buildBody($api, $host, $dataset, $tab, $pageId, $id, $resourceId, $name, $description, $url, $dateModified, $licence, $keywords, $exports, $metadataExtras, $location);
		 
		$element = array(
			'example one' => [
				'#type' => 'inline_template',
				'#template' => $body,
						
			],
		);
		$element['#attached']['library'][] = 'ckan_admin/visu.angular';
		return $element;
	}

	function buildBody($api, $host, $dataset, $tab, $pageId, $id, $resourceId, $name, $description, $url, $dateModified, $licence, $keywords, $exports, $metadataExtras, $location) {
		
		$visu = $this->buildVisu($metadataExtras);
		$customView = $this->buildCustomView($metadataExtras);

		if ($visu == 0) {
			$tab = 'information';
		}
		else if ($visu == 1) {
			$tab = 'table';
		}
		else if ($visu == 2) {
			$tab = 'analyze';
		}
		else if ($visu == 3) {
			$tab = 'map';
		}
		else if ($visu == 4) {
			$tab = $customView->title;
		}
		else if ($visu == 5) {
			$tab = 'timeline';
		}
		else if ($visu == 6) {
			$tab = 'calendar';
		}
		else if ($visu == 7) {
			$tab = 'wordcloud';
		}

		if (!isset($tab)) {
			$tab = 'information';
		}

		$ctx = str_replace(array("{", "}", '"'), array("\{", "\}", "&quot;"), json_encode($dataset));

		$themes = $this->buildTheme($api, $metadataExtras);
		$datasetTitle = $this->buildDatasetTitle($themes);
		// $imgTheme = $themes[0];
		// $themes = $themes[1];
		
		$resourcesList = $this->buildResourcesList($pageId, $dataset, $resourceId);

		$isConnected = \Drupal::currentUser()->isAuthenticated();
		$isRgpd = $this->exportExtras($metadataExtras, 'data_rgpd');
		$rgpdNonConnected = $this->config->client->check_rgpd && !$isConnected && $isRgpd;

		$filters = $this->buildFilters($id, $dataset, $resourceId);
		$tabs = $this->buildTabs($api, $tab, $dataset, $id, $name, $description, $themes, $metadataExtras, $keywords, $resourceId, $location, $isRgpd, $rgpdNonConnected);
		$disqus = $this->buildDisqus($host, $dataset);
		$imports = $this->buildImports($id, $name, $description, $url, $dateModified, $licence, $keywords, $exports);

		return '
			<body>
				<div class="d4c-content">
					<header class="ng-scope"></header>
					<main class="main--dataset">

						<div class="container-fluid d4c-app-explore-dataset ng-cloak"
							ng-app="d4c.frontend"
							ng-controller="ExploreDatasetController"
							d4c-dataset-context
							ng-init="toggleState={expandedFilters: false};"
							context="ctx"
							ctx-urlsync="true"
							ctx-dataset-schema="' . $ctx . '"
							ctx-selected-resource-id="' . $resourceId . '">

								<d4c-notification-handler></d4c-notification-handler>

								<div class="d4c-dataset-visualization__header">
									<h1 class="d4c-dataset-visualization__dataset-title">
										' . $datasetTitle . '
									</h1>
								</div>
				
								<div class="d4c-actif-filters" ng-show="canDisplayFilters() && canAccessData()">
									<h2 class="d4c-filters__filters-summary" ng-show="ctx.getActiveFilters().length">
										<span translate>Active filters</span>
										<d4c-clear-all-filters context="ctx"></d4c-clear-all-filters>
									</h2>
									<d4c-filter-summary context="ctx" clear-all-button="false"></d4c-filter-summary>
									<div ng-hide="ctx.getActiveFilters().length"
											class="d4c-filters__no-filters">
										Aucun filtre actif.
									</div>
								</div>

								<div class="d4c-search-filters" ng-show="canDisplayFilters() && canAccessData()">
									<d4c-text-search context="ctx" placeholder="Rechercher..." autofocus></d4c-text-search>
								</div>

								' . $resourcesList . '
								' . ($rgpdNonConnected ? '' : $filters) . 
							'<div class="d4c-dataset-visualization" ng-class="{\'d4c-dataset-visualization--full-width\': !canAccessData()}">
								' . $tabs . '
								' . $disqus . '
							</div>
						</div>
					</main>
				</div>

				<footer class="ng-scope"></footer>
					
				' . $imports . '

				<div class="d4c-tooltip" style="display: none;"></div>
				<div class="rd-container d4cwidgets-rd-container rd-container-attachment" style="display: none; top: 670px; left: 124.9px;"></div>
				<div class="rd-container d4cwidgets-rd-container rd-container-attachment" style="display: none; top: 693.2px; left: 124.063px;"></div>
			</body>
		';
	}

	function buildFilters($datasetId, $dataset, $selectedResourceId) {
		return '
			<div class="d4c-filters-summary" ng-show="canDisplayFilters()">
				<div class="d4c-filters-summary__count">
					<span class="d4c-filters-summary__count-number">\{\{ ctx.nhits | number \}\}</span>
					<span class="d4c-filters-summary__count-units" translate translate-n="ctx.nhits" translate-plural="records">record</span>
				</div>
				<a class="d4c-button d4c-filters-summary__toggle" ng-click="extendFilters(false)">
					<i class="fa" aria-hidden="true" ng-class="{\'fa-expand\': !toggleState.expandedFilters, \'fa-compress\': toggleState.expandedFilters}"></i>
					Filtres
				</a>
			</div>
			<div id="d4c-filters" class="d4c-filters" ng-show="canDisplayFilters() && canAccessData()">
				<a class="closed" ng-click="extendFilters(true)">x</a>
				<h2 class="d4c-filters__count">
					<span class="d4c-filters__count-number">\{\{ ctx.nhits | number \}\}</span>
					<span class="d4c-filters__count-units" translate translate-n="ctx.nhits" translate-plural="records">record</span>
				</h2>

				<h2 class="d4c-filters__filters"><span translate>Filters</span></h2>

				<!-- Predefined filters -->
				<h2 ng-if="ctx.dataset.getPredefinedFilters()" class="d4c-filters__filters"><span translate>Predefined Filters</span></h2>
				<ul class="d4c-dataset-export__format-choices" ng-if="ctx.dataset.getPredefinedFilters()">
					<li ng-repeat="(key, value) in ctx.dataset.getPredefinedFilters()" class="d4c-dataset-export__format-choice">
						<a href = "' . $this->config->client->routing_prefix . '/visualisation/table/?id=\{\{ ctx.dataset.metas.id \}\}&\{\{ value }\}">
							<span>\{\{ key }\}</span>
						</a>
					</li>
				</ul>

				<d4c-facets context="ctx"></d4c-facets>
			</div>';
	}

	function buildResourcesList($pageId, $dataset, $selectedResourceId) {
		$resources = $dataset["metas"]["resources"];
		$numberOfResources = 0;

		$list = '<div class="d4c-resources-choices" ng-show="canDisplayFilters()">';
		$list .= '
				<p>Jeu de données affiché : </p>
				<select ng-model="selectedItem" class="form-control" ng-change="visualizeResource(\'' . $pageId . '\', selectedItem)">
					<option value="" ng-if="false">Choix du jeu de données</option>
		';

		if (sizeof($resources) > 0 ) {
			$lastResourceId = $this->getLastDataResource($resources);
			
			foreach($resources as $key=>$value){
				$resourceId = $value["id"];
				$name = $value["name"];
				$mimeType = $value["mimetype"];
				$datastoreActive = $value["datastore_active"];

				$button = '';
				if ($mimeType == "text/csv" && $datastoreActive == true) {
					$numberOfResources = $numberOfResources + 1;

					$isActif = ($selectedResourceId == null && $lastResourceId != null && $lastResourceId == $resourceId) || ($selectedResourceId == $resourceId);

					$list .= '
							<option value="' . $resourceId . '" ng-value="' . $resourceId . '" ' . ($isActif ? 'ng-selected="true"' : '') . '>' . $name . '</option>
					';
				}
			}
		}

		$list .= '	</select>';
		$list .= '</div>';

		return $numberOfResources > 1 ? $list : '';
	}

	function buildTabs($api, $tab, $dataset, $id, $name, $description, $themes, $metadataExtras, $keywords, $selectedResourceId, $location, $isRgpd, $rgpdNonConnected) {
		$loggedIn = \Drupal::currentUser()->isAuthenticated();
		$data4citizenType = $this->exportExtras($metadataExtras, 'data4citizen-type');

		$tabInformation = $this->buildTabInformation($loggedIn, $dataset, $id, $name, $description, $themes, $metadataExtras, $keywords, $selectedResourceId, $isRgpd, $rgpdNonConnected);
		if (!$rgpdNonConnected) {

			if ($data4citizenType == 'visualization') {
				$tabVisualization = $this->buildTabVisualization($api, $metadataExtras);
			}
			else {
				$tabTable = $this->buildTabTable($loggedIn);
				$tabMap = $this->buildTabMap($loggedIn, $dataset, $metadataExtras, $location);
				$tabAnalyze = $this->buildTabAnalyze($loggedIn);
				$tabImage = $this->buildTabImage($loggedIn);
				$tabCalendar = $this->buildTabCalendar($loggedIn);
				$tabCustomView = $this->buildTabCustomView($loggedIn);
				$tabWordCloud = $this->buildTabWordCloud($loggedIn);
				$tabTimeline = $this->buildTabTimeline($loggedIn);
				$tabExport = $this->buildTabExport($dataset, $metadataExtras);
				$tabAPI = $this->buildTabAPI($dataset);
				$tabReuses = $this->buildTabReuses($loggedIn, $name);

				$isAdmin = $api->isConnectedUserAdmin();
				$isUserRO = false;
				
				//Checking if the module data_bfc exist and if the user is RO
				$moduleHandler = \Drupal::service('module_handler');
				if ($moduleHandler->moduleExists('data_bfc')) {

					$userManager = new UserManager();
					$isUserRO = $userManager->isConnectedUserRO();
				}

				if ($isAdmin || $isUserRO) {
					$tabAdmin = $this->buildTabAdmin($dataset, $name);
				}
			}
		}

		return '
			<d4c-tabs sync-to-url="true" sync-to-url-mode="path" name="main" default-tab="' . $tab . '">
				' . $tabInformation . '
				' . $tabVisualization . '
				' . $tabTable . '
				' . $tabMap . '
				' . $tabAnalyze . '
				' . $tabImage . '
				' . $tabCalendar . '
				' . $tabCustomView . '
				' . $tabWordCloud . '
				' . $tabTimeline . '
				' . $tabExport . '
				' . $tabAPI . '
				' . $tabReuses . '
				' . $tabAdmin . '
			</d4c-tabs>
		';
	}

	function buildTabInformation($loggedIn, $dataset, $datasetId, $name, $description, $themes, $metadataExtras, $keywords, $selectedResourceId, $isRgpd, $rgpdNonConnected) {
		// $sources = $this->buildSources($metadataExtras);
		// $ftpApi = $sources[0];
		// $source = $sources[1];

		//IMAGE
		$image = $this->buildImage($metadataExtras);

		//LIMITES ET CONDITIONS D'UTILISATION
		$limitesUtilisation = $this->buildLimitesUtilisation($metadataExtras);

		//LIMITES ET CONDITIONS D'UTILISATION
		$conditionsUtilisation = $this->buildConditionsUtilisation($metadataExtras);

		//MÉTHODE DE PRODUCTION ET QUALITÉ
		$methodeProductionEtQualite = $this->buildMethodeProductionEtQualite($metadataExtras);

		//INFORMATIONS GÉOGRAPHIQUES
		$informationsGeo = $this->buildInformationsGeo($metadataExtras);

		//SYNTHÈSE
		$synthese = $this->buildSynthese($metadataExtras, $themes, $keywords);

		//CONTACTS
		$contacts = $this->buildContacts($metadataExtras);

		//Linked datasets
		$linkedDataSets = $this->buildLinkedDatasets($metadataExtras);
		
		$visWidget = $this->buildWidget($metadataExtras);

		$downloadsAndLinks = null;
		if ($this->config->client->ressources_download_links) {
			$downloadsAndLinks = $this->manageAdditionnalResources($dataset, $datasetId, $selectedResourceId);
		}

		$keywordsPart = $this->buildKeywords($keywords);

		//Deactivated for now because the development is not finish
		// $ratingPart = $this->buildRating($loggedIn, $datasetId);
		$ratingPart = null;

		$kpiPart = $this->buildKPI($loggedIn, $datasetId, $dataset, $selectedResourceId);

		$rgpdPart = $this->buildRgpd($isRgpd, $rgpdNonConnected);

		return '
			<d4c-pane pane-auto-unload="true" title="Information" icon="info-circle" translate="title" slug="information">
				<div class="row">
					<div class="col-sm-9">
						' . ($rgpdPart != null ? $this->buildCard('RGPD', $rgpdPart) : '') . '
						' . $this->buildCard('Description', ($description != null && $description != '' ? $description : 'Aucune description des données renseigné')) . '
						' . $this->buildCard('Limites techniques d\'usage', (!$this->isNullOrEmptyString($limitesUtilisation) ? $limitesUtilisation : 'Aucune limite technique d\'usage des données renseignée')) . '
						' . ($conditionsUtilisation != null ? $this->buildCard('Licences et conditions d\'utilisation', $conditionsUtilisation) : '') . '
						' . ($methodeProductionEtQualite != null ? $this->buildCard('Méthode de production et qualité', $methodeProductionEtQualite) : '') . '
						' . ($informationsGeo != null ? $this->buildCard('Informations géographiques', $informationsGeo) : '') . '
						' . ($downloadsAndLinks != null ? $this->buildCard('Documents et ressources', $downloadsAndLinks) : '') . '
						' . ($keywordsPart != null ? $this->buildCard('Mots clefs', $keywordsPart) : '') . '
						' . ($ratingPart != null ? $this->buildCard('Notation', $ratingPart) : '') . '
						' . ($kpiPart != null ? $this->buildCard('Indicateurs', $kpiPart) : '') . '
						' . ($linkedDataSets != null ? $this->buildCard('Jeux de données liés', $linkedDataSets) : '') . '
					</div>
					<div class="col-sm-3">
						' . ($image != null ? $this->buildCardImage($image) : '') . '
						' . $this->buildCard('Synthèse', $synthese) . '
						' . $this->buildCard('Contacts', $contacts) . '
					</div>
				</div>
				
				' . $visWidget . '

				<d4c-dataset-attachments dataset="ctx.dataset"></d4c-dataset-attachments>

				<d4c-collapsible ng-if="ctx.dataset.has_records" class="d4c-dataset-visualization__schema">
					<d4c-collapsible-above-fold>
						<h3 class="d4c-dataset-visualization__toggle-schema">
							<span translate>Dataset schema</span>
						</h3>
					</d4c-collapsible-above-fold>
					<d4c-collapsible-fold>
						<d4c-dataset-schema context="ctx"></d4c-dataset-schema>

						<h4 translate>JSON Schema</h4>

						<p>
							<span translate>The following JSON object is a standardized description of your dataset\'s schema.</span>
							<a href="http://json-schema.org/" target="_blank" translate>More about JSON schema</a>.
						</p>

						<d4c-dataset-json-schema context="ctx"></d4c-dataset-json-schema>

					</d4c-collapsible-fold>
				</d4c-collapsible>

				<!-- Enable this to enable dataset subscription -->
				<d4c-dataset-subscription context="ctx" logged-in="' . $loggedIn . '" dataset-id="' . $datasetId . '" preset="ctx.dataset.is_subscribed"></d4c-dataset-subscription>
			</d4c-pane>
		';
	}

	function buildCard($title, $text) {
		return '
			<div class="card">
				<div class="card-body">
					<div class="h5 text-uppercase">'  . $title . '</div>
					<p class="card-text">' . $text . '</p>
				</div>
			</div>
		';
	}

	function buildCardImage($image) {
		return '
			<div class="card">
				<img src="' . $image . '" alt="Overview" class="card-image"/>
			</div>
		';
	}

	/* MANAGE METADATA */

	function buildLinkedDatasets($metadataExtras) {
		$links = $this->exportExtras($metadataExtras, 'LinkedDataSet');

		if ($links != null) {
			$links = explode(";", $links);

			$linkedDatasets = null;
			for ($j=0; $j<count($links); $j++) {
				$link = explode(":", $links[$j]);
				
				if ($link[0] != 'false') {
					$url = $this->config->client->routing_prefix . '/visualisation?id='. $link[1];
					$linkedDatasets = $linkedDatasets . '&nbsp<p style="margin: -1.1em 0 -1em;" ><code style="cursor: pointer;" onclick="window.open(`'.$url.'`, `_blank`);">' . $link[0] . '</code></p><br>';
				}
			}
	
			if ($linkedDatasets != null) {
				return $linkedDatasets;
			}
		}
			
		return null;
	}

	function buildTheme($api, $metadataExtras) {
		//Getting themes to get theme's information
		$listOfThemes = $api->getPackageTheme();
    	$listOfThemes = json_decode($listOfThemes->getContent(), true);

		$themeName = $this->exportExtras($metadataExtras, 'theme');
		$themesExtras = $this->exportExtras($metadataExtras, 'themes');

		$themesPart = '';
		if ($themesExtras != null) {
			$themesExtras = json_decode($themesExtras, true);

			$themes = array();
			foreach ($themesExtras as $value) {
				$selectedTheme = $this->findTheme($listOfThemes, $value);
				
				$theme = array();
				$theme["label"] = $selectedTheme["label"];
				$theme["title"] = $selectedTheme["title"];
				$theme["url"] = $selectedTheme["url"];
				$theme["url_light"] = $selectedTheme["url_light"];

				$themes[] = $theme;
				// $themesPart .= '
				// 	<div class="d4c-dataset-metadata-block__metadata ng-scope" style="font-size: 1rem; margin: -0.8em  0 -1em 0;">
				// 		<div class="d4c-dataset-metadata-block__metadata-name ng-binding" >Thème</div>
				// 		<div class="d4c-dataset-metadata-block__metadata-value d4c-dataset-metadata-block__metadata-value--default ng-binding ng-scope">' . $labelTheme . '</div>
				// 	</div>
				// '; 
			}
		}
		else if ($themeName != null) {
			$selectedTheme = $this->findTheme($listOfThemes, $themeName);

			$theme = array();
			$theme["label"] = $selectedTheme["label"];
			$theme["title"] = $selectedTheme["title"];
			$theme["url"] = $selectedTheme["url"];
			$theme["url_light"] = $selectedTheme["url_light"];
			$themeName = $theme;

			$themes = array();
			$themes[] = $theme;
			// $themesPart = '
			// 	<div class="d4c-dataset-metadata-block__metadata ng-scope" style="font-size: 1rem; margin: -0.8em  0 -1em 0;">
			// 		<div class="d4c-dataset-metadata-block__metadata-name ng-binding" >Thème</div>
			// 		<div class="d4c-dataset-metadata-block__metadata-value d4c-dataset-metadata-block__metadata-value--default ng-binding ng-scope">' . $theme . '</div>
			// 	</div>
			// '; 
		}
		else {
			$themeName = 'default';
			$selectedTheme = $this->findTheme($listOfThemes, $themeName);

			$theme = array();
			$theme["label"] = $selectedTheme["label"];
			$theme["title"] = $selectedTheme["title"];
			$theme["url"] = $selectedTheme["url"];
			$theme["url_light"] = $selectedTheme["url_light"];
			$themeName = $theme;

			$themes = array();
			$themes[] = $theme;

			// $themesPart = '
			// 	<div class="d4c-dataset-metadata-block__metadata ng-scope" style="font-size: 1rem; margin: -0.8em  0 -1em 0;">
			// 		<div class="d4c-dataset-metadata-block__metadata-name ng-binding" >Thème</div>
			// 		<div class="d4c-dataset-metadata-block__metadata-value d4c-dataset-metadata-block__metadata-value--default ng-binding ng-scope">Default</div>
			// 	</div>
			// '; 
		}

		return $themes;
	}

	function buildDatasetTitle($themes) {
		$themeImages = '<div class="box_3">';
		$themeImages .= '	<d4c-social-buttons></d4c-social-buttons>';
		
		$themeImages .= '	<button class="d4c-button" ng-click="goBackToSearch()">';
		$themeImages .= '		<i class="fa fa-angle-left" aria-hidden="true"></i>';
		$themeImages .= '		RETOUR AUX RESULTATS DE RECHERCHE';
		$themeImages .= '	</button>';

		foreach($themes as $theme) {
			$themeImage = isset($theme["url_light"]) ? $theme["url_light"] : $theme["url"];
			if ($themeImage != null) {
				$themeImages .= '	<div style=" background-image: url('. $themeImage . '); display: inline-block; width: 40px; height: 40px; background-repeat: no-repeat; background-size: contain; vertical-align: middle; margin-right: 8px;"></div>';
			}
		}
		$themeImages .= '	<span>\{\{ ctx.dataset.metas.title \}\}</span>';

		$themeImages .= '</div>';

		return $themeImages;
	}

	function findTheme($listOfThemes, $theme) {
		foreach ($listOfThemes as $value) {
			if ($value['title'] == $theme) {
				return $value;
			}
		}

		return null;
	}

	function buildVisu($metadataExtras) {
		$visu = $this->exportExtras($metadataExtras, 'default_visu');
		return $visu != null ? $visu : 0;
	}

	// function buildSources($metadataExtras) {
	// 	$ftpApi = $this->exportExtras($metadataExtras, 'FTP_API');
	// 	$source = $this->exportExtras($metadataExtras, 'source');
	// 	$donneesSource = $this->exportExtras($metadataExtras, 'donnees_source');

	// 	if ($ftpApi != null && $ftpApi != 'FTP') {
	// 		$labSource =  parse_url($ftpApi);
	// 		$ftp_api = '
	// 			<div class="d4c-dataset-metadata-block__metadata ng-scope" style="font-size: 1rem; margin: -0.8em  0 -1em 0;">
	// 				<div class="d4c-dataset-metadata-block__metadata-name ng-binding" >Source</div>
	// 				<div class="d4c-dataset-metadata-block__metadata-value d4c-dataset-metadata-block__metadata-value--default ng-binding ng-scope">' . $labSource["host"] . '</div>
	// 			</div>
	// 		';
			
	// 		$source = '
	// 			<div class="d4c-dataset-metadata-block">
	// 				<div class="d4c-dataset-metadata-block__metadata">
	// 					<div class="d4c-dataset-metadata-block__metadata ng-scope" style="font-size: 1rem; margin: -0.8em  0 -1em 0;">
	// 						<div class="d4c-dataset-metadata-block__metadata-name ng-binding" >Données Source</div>
	// 						<div class="d4c-dataset-metadata-block__metadata-value d4c-dataset-metadata-block__metadata-value--default ng-binding ng-scope">
	// 							<p ><code style="cursor: pointer;" onclick="window.open(`' . $ftpApi . '`, `_blank`);">' . $ftpApi . '</code></p>
	// 						</div>
	// 					</div>
	// 				</div>
	// 			</div>
	// 		';
	// 	}
	// 	else {
	// 		$ftp_api ='
	// 			<div class="d4c-dataset-metadata-block__metadata ng-scope" style="font-size: 1rem; margin: -0.8em  0 -1em 0;">
	// 				<div class="d4c-dataset-metadata-block__metadata-name ng-binding" >Source</div>   
	// 				<div class="d4c-dataset-metadata-block__metadata-value d4c-dataset-metadata-block__metadata-value--default ng-binding ng-scope">FTP/SFTP</div>
	// 			</div>
	// 		';
	// 	}

	// 	// get source value
	// 	if ($source != null) {
	// 		$ftp_api ='
	// 			<div class="d4c-dataset-metadata-block__metadata ng-scope" style="font-size: 1rem; margin: -0.8em  0 -1em 0;">
	// 				<div class="d4c-dataset-metadata-block__metadata-name ng-binding" >Source</div>
	// 				<div class="d4c-dataset-metadata-block__metadata-value d4c-dataset-metadata-block__metadata-value--default ng-binding ng-scope">' . $source . '</div>
	// 			</div>
	// 		';
	// 	}

	// 	//get donnees source value
	// 	if ($donneesSource != null) {
	// 		$source = '
	// 			<div class="d4c-dataset-metadata-block">
	// 				<div class="d4c-dataset-metadata-block__metadata">
	// 					<div class="d4c-dataset-metadata-block__metadata ng-scope" style="font-size: 1rem; margin: -0.8em  0 -1em 0;">
	// 						<div class="d4c-dataset-metadata-block__metadata-name ng-binding" >Données Source</div>
	// 						<div class="d4c-dataset-metadata-block__metadata-value d4c-dataset-metadata-block__metadata-value--default ng-binding ng-scope">
	// 							<p ><code style="cursor: pointer;" onclick="window.open(`' . $donneesSource . '`, `_blank`);">' . $donneesSource . '</code></p>
	// 						</div>
	// 					</div>
	// 				</div>
	// 			</div>
	// 		';
	// 	}

	// 	return [
	// 		$ftp_api,
	// 		$source
	// 	];
	// }

	function buildCustomView($metadataExtras) {
		$customView = $this->exportExtras($metadataExtras, 'custom_view');
		return $customView != null ? json_encode($customView) : null;
	}

	function buildImage($metadataExtras) {
		// $: image = getImage($storeMdjs);
		
		$image = $this->exportExtras($metadataExtras, 'graphic-preview-file');
		return $image != null ? $image : '';
	}

	function buildWidget($metadataExtras) {
		$widgets = $this->exportExtras($metadataExtras, 'widgets');

		if ($widgets != null) {
			$widgets = explode('<.explode.>', $widgets);
			$result_w = '';
			
			foreach($widgets as &$val_w){
				if(substr($val_w, -7)=='<.off.>'){}
				else {
					$data_w = explode('<.info.>', $val_w);
					
					$url = filter_var($data_w[2], FILTER_SANITIZE_URL);
					if (!filter_var($url, FILTER_VALIDATE_URL) === false) {
						$data_w[2] = '<iframe style="width:100%; height:50em; border:none" src="' . $data_w[2] . '"></iframe>';
					} 
					
					$result_w = $result_w . '
						<d4c-collapsible ng-if="ctx.dataset.has_records" class="d4c-dataset-visualization__schema">
							<d4c-collapsible-above-fold>
								<h3 class="d4c-dataset-visualization__toggle-schema"><span>' . $data_w[0] . '</span></h3>
							</d4c-collapsible-above-fold>
							<d4c-collapsible-fold>
								<p>' . $data_w[1] . '</p><br>
								<div>' . $data_w[2] . '</div>
							</d4c-collapsible-fold>
						</d4c-collapsible>
					'; 
				}
			}
			return $result_w;
		}
		else {
			return '';
		}
	}

	function buildLimitesUtilisation($metadataExtras) {
		$useConstraintsPart = '';

		for ($i = 1; $i <= 5; $i++) {
			$useConstraint = $this->exportExtras($metadataExtras, 'use-constraints-' . $i);

			if ($useConstraint != null) {
				$useConstraint = json_decode($useConstraint, true);
				$useConstraintsPart .= '<li>' . $useConstraint . '</li>';
			}
		}

		if ($this->isNullOrEmptyString($useConstraintsPart)) {
			return null;
		}

		return '
			<ul class="m-0">
				' . $useConstraintsPart . '
			</ul>
		';
	}

	function buildConditionsUtilisation($metadataExtras) {
		$licence = $this->exportExtras($metadataExtras, 'licence');
		$accessConstraints = $this->exportExtras($metadataExtras, 'access_constraints');
		$mentionLegales = $this->exportExtras($metadataExtras, 'mention_legales');

		$hasValue = false;
		if (!$this->isNullOrEmptyString($licence)) {
			$hasValue = true;

			$licenceJson = json_decode($licence, true);
			if (json_last_error() === JSON_ERROR_NONE) {
				$licence = $licenceJson;
				foreach ($licence as $value) {	
					$licence = '<li>' .$value . '</li>';
				}
			}
			else {
				$licence = '<li>' . $licence . '</li>';
			}
		}

		if (!$this->isNullOrEmptyString($accessConstraints)) {
			$accessConstraints = json_decode($accessConstraints, true);

			if (!empty($accessConstraints)) {
				$hasValue = true;
	
				foreach ($accessConstraints as $value) {	
					$accessConstraints = '<li>' . $value . '</li>';
				}
			}
			else {
				$accessConstraints = '';
			}
		}

		if (!$this->isNullOrEmptyString($mentionLegales)) {
			$hasValue = true;

			$mentionLegales = '<li>' . $mentionLegales . '</li>';
		}

		$resourceConstraintsPart = '';
		for ($i = 1; $i <= 5; $i++) {
			$resourceConstraints = $this->exportExtras($metadataExtras, 'resource-constraints-' . $i);
			if ($resourceConstraints != null) {
				$hasValue = true;

				$resourceConstraints = json_decode($resourceConstraints, true);
				$resourceConstraintsPart .= '<li>' . $resourceConstraints . '</li>';
			}
		}

		if (!$hasValue) {
			return null;
		}

		return '
			<ul class="m-0">
				' . $licence . '
				' . $accessConstraints . '
				' . $mentionLegales . '
				' . $resourceConstraintsPart . '
			</ul>
		';
	}

	function buildSynthese($metadataExtras, $themes, $keywords) {
		$isOpenData = $this->isOpenData($keywords);
		$frequence = $this->exportExtras($metadataExtras, 'frequency-of-update');
		$datasetDates = $this->exportExtras($metadataExtras, 'dataset-reference-date');
		$representationType = $this->exportExtras($metadataExtras, 'spatial-representation-type');
		$isGeo = $representationType == 'grid' || $representationType == 'vector';

		$synthese = '';
		if ($isOpenData) {
			$synthese .= '
				<div class="my-3">
					<i class="fa fa-unlock"></i>
					<span class="ms-2">Données ouvertes</span>
				</div>
			';
		}
		
		// $nbDownloads = '
		// 	<div class="d4c-dataset-metadata-block" ng-show="(ctx.dataset.metas.extras | filter:{key:\'nb_download\'})[0].value > 0">
		// 		<div class="d4c-dataset-metadata-block__metadata">
		// 			<div class="d4c-dataset-metadata-block__metadata-name" translate=""><span class="ng-scope">Téléchargements</span></div>
		// 			<div class="d4c-dataset-metadata-block__metadata-value ng-binding">\{\{ +((ctx.dataset.metas.extras | filter:\{key:\'nb_download\'\})[0].value) \}\}</div>
		// 		</div>
		// 	</div>
		// ';

		$synthese .= '
			<div class="my-3">
				<i class="fa fa-clock-o"></i>
				<span class="ms-2">' . ($frequence != null ? 'Mise à jour ' . $this->translateValue($this->locale["codelists"]["MD_MaintenanceFrequencyCode"], $frequence) : 'Mise à jour inconnue') . '</span>
			</div>
		';

		// [{"type": "creation", "value": "2019-11-12"}, {"type": "edition", "value": ""}, {"type": "publication", "value": "2019-11-12"}]
		$displayDate = null;
		$datasetDates = json_decode($datasetDates, true);
		foreach ($datasetDates as $date) {
			if ($date['type'] == "creation") {
				$displayDate = $date['value'];
				break;
			}
			else if ($date['type'] == "publication") {
				$displayDate = $date['value'];
			}
		}
		if ($displayDate != null) {
			$synthese .= '
				<div class="my-3">
					<i class="fa fa-pencil"></i>
					<span class="ms-2" translate>Publié le </span>
					<span>\{\{\'' . $displayDate . '\' | formatMeta:\'date\' \}\}</span>
				</div>
			';
		}

		if ($isGeo) {
			$synthese .= '
				<div class="my-3">
					<i class="fa fa-globe"></i>
					<span class="ms-2">Donnée géographique</span>
				</div>
			';
		}

		$synthese .= '
			<div class="my-3">
				<i class="fa fa-tag"></i>
				<span class="ms-2"><strong>Thèmes</strong></span>';
		if ($themes != null) {
			$synthese .= '	<ul>';
			foreach ($themes as $theme) {	
				$synthese .= '		<li>' . $theme["label"] . '</li>';
			}
			$synthese .= '	</ul>';
		}
		$synthese .= '</div>';

		return $synthese;
	}

	function isOpenData($keywords) {
		$arrayOpenData = ["opendata", "open data", "donnée ouverte", "données ouvertes"];
		return !empty(array_intersect($keywords, $arrayOpenData));
	}
	
	function buildMethodeProductionEtQualite($metadataExtras) {
		$lineage = $this->exportExtras($metadataExtras, 'lineage');
		if (isset($lineage)) {
			$Parsedown = new Parsedown();
			$lineage = $Parsedown->text($lineage);
		}
		return $lineage;
	}

	function buildInformationsGeo($metadataExtras) {
		// TODO
		// $: dataReferenceSystem = getReferenceSystem($storeMdjs);
		// $: dataSpatialRepresentationType = converter.getValue($storeMdjs, "dataSpatialRepresentationType")[0] || "";
		// $: bbox = getBbox($storeMdjs);
		// $: dataScaleDenominator = converter.getValue($storeMdjs, "dataScaleDenominator")[0] || "";
		// $: dataScaleDistance = converter.getValue($storeMdjs, "dataScaleDistance")[0] || "";

		$representationType = $this->exportExtras($metadataExtras, 'spatial-representation-type');

		$bboxEastLong = $this->exportExtras($metadataExtras, 'bbox-east-long');
		$bboxNorthLat = $this->exportExtras($metadataExtras, 'bbox-north-lat');
		$bboxSouthLat = $this->exportExtras($metadataExtras, 'bbox-south-lat');
		$bboxWestLong = $this->exportExtras($metadataExtras, 'bbox-west-long');
		
		$equivalentScale = $this->exportExtras($metadataExtras, 'equivalent-scale');
		if ($equivalentScale != null) {
			// Not working because scale is for exemple {5000}
			// $equivalentScale = json_decode($equivalentScale);
			$equivalentScale = $this->cleanSimpleJson($equivalentScale);
		}
		
		$referenceSystem = $this->exportExtras($metadataExtras, 'spatial-reference-system');
		$resolution = $this->exportExtras($metadataExtras, 'spatial-resolution-units');

		if ($this->isNullOrEmptyString($representationType) && $this->isNullOrEmptyString($bboxEastLong) && $this->isNullOrEmptyString($bboxNorthLat) && $this->isNullOrEmptyString($bboxSouthLat) && $this->isNullOrEmptyString($bboxWestLong)
				&& $this->isNullOrEmptyString($equivalentScale) && $this->isNullOrEmptyString($referenceSystem) && $this->isNullOrEmptyString($resolution)) {
			return null;
		}

		// spatial-reference-system	2154
		return '
			<div class="row">
				<div class="col-sm-7">
					<p><strong>Type de représentation:</strong> ' . ($representationType != null ? $this->translateValue($this->locale["codelists"]["MD_SpatialRepresentationTypeCode"], $representationType) : 'non renseignée') . '</p>
					<p><strong>Etendue géographique:</strong></p>
					<ul>
						<li>Ouest: ' . ($bboxWestLong != null ? number_format($bboxWestLong, 2) : "non renseignée") . '</li>
						<li>Est: ' . ($bboxEastLong != null ? number_format($bboxEastLong, 2) : "non renseignée") . '</li>
						<li>Sud: ' . ($bboxSouthLat != null ? number_format($bboxSouthLat, 2) : "non renseignée") . '</li>
						<li>Nord: ' . ($bboxNorthLat != null ? number_format($bboxNorthLat, 2) : "non renseignée") . '</li>
					</ul>
				</div>
				<div class="col-sm-3">
					<p><strong>Système de projection:</strong> ' . ($referenceSystem != null ? $this->translateValue($this->locale["codelists"]["MD_ReferenceSystemCode"], $referenceSystem) : 'non renseignée') . '</p>
					<p><strong>Echelle:</strong> ' . ($equivalentScale != null ? '1/' . $equivalentScale : 'non renseignée') . '</p>
					<p><strong>Résolution:</strong> ' . ($resolution != null ? $resolution : 'non renseignée') . '</p>
				</div>
			</div>
		';
	}
	
	function buildContacts($metadataExtras) {
		// TODO
		// $: {
		// 	const contacts = converter.getValue($storeMdjs, "dataPointOfContacts") || [];
		// 	dataPointOfContacts = getContacts(contacts);
		// }

		$contacts = '<div class="list-unstyled">';

		//We tried to get the first 5 responsible-organisation
		for ($i = 1; $i <= 5; $i++) {
			$organisation = $this->exportExtras($metadataExtras, 'responsible-organisation-' . $i);
			if ($organisation != null) {
				$organisation = json_decode($organisation, true);
				$organisationName = $organisation['organisation-name'];

				
				$address = $organisation['contact-info']['address'];
				$postalCode = $organisation['contact-info']['postal-code'];
				$city = $organisation['contact-info']['city'];
				$contactEmail = $organisation['contact-info']['email'];
				$phone = $organisation['contact-info']['phone'];

				$address = !$this->isNullOrEmptyString($address) ? '<p class="mb-0">' . $address . '</p>' : "";
				$postalCodeCity = !$this->isNullOrEmptyString($postalCode) || !$this->isNullOrEmptyString($city) ? '<p class="mb-0">' . $postalCode . ' ' . $city . '</p>' : '';
				$contactEmail = !$this->isNullOrEmptyString($contactEmail) ? '<p class="mb-0"><i class="fa fa-envelope"></i><a href="mailto:' . $contactEmail . '"> contact</a></p>' : '';
				$phone = !$this->isNullOrEmptyString($phone) ? '<p class="mb-0"><i class="fa fa-mobile"></i> ' . $phone . '</p>' : '';

				$contacts .= '
					<div class="d-flex align-items-center mt-3">
						<div class="flex-grow-1 ms-3"><strong id="displayContact1">' . $organisationName . '</strong>
							' . $address . '
							' . $postalCodeCity . '
							' . $contactEmail . '
							' . $phone . '
						</div>
					</div>
					<hr>
				';
			}
		}

		$contacts .= '</div>';
		return $contacts;
	}

	function manageAdditionnalResources($dataset, $datasetId, $selectedResourceId) {
		$additionnalResources = '';

		$excludeDataResources = true;
		$resources = $dataset["metas"]["resources"];

		if (sizeof($resources) > 0 ) {
			// $lastResourceId = $this->getLastDataResource($resources);

			$resourcesUrl = array();

			foreach($resources as $key=>$value){
				$resourceId = $value["id"];
				$name = $value["name"];
				$url = $value["url"];
				$format = $value["format"];
				$protocol = $value["resource_locator_protocol"];
				$mimeType = $value["mimetype"];
				$datastoreActive = $value["datastore_active"];

				// Checking if resource already exist
				if (in_array($url, $resourcesUrl)) {
					continue;
				}

				// $classImg = strpos($protocol, 'download') !== false || strpos($protocol, 'DOWNLOAD') !== false ? 'fa-download' : 'fa-link';
				$classImg = 'fa-link';
				if ($excludeDataResources && (strcasecmp($format , 'zip') == 0 || strcasecmp($format , 'xls') == 0 || strcasecmp($format , 'xlsx') == 0 
						|| strcasecmp($format , 'csv') == 0 || strcasecmp($format , 'json') == 0 || strcasecmp($format , 'geojson') == 0)) {
					continue;
				}

				//Deactivate WMS and WFS
				if (strcasecmp($format , 'wms') == 0 || strcasecmp($format , 'wfs') == 0 || strpos($url, "wms") !== false || strpos($url, "wfs") !== false || strpos($name, "wms") !== false || strpos($name, "wfs") !== false) {
					continue;
				}


				$button = '';
				if ($mimeType == "text/csv" && $datastoreActive == true) {
					$classImg = 'fa-table';

					$button .= '
						<button class="btn btn-info" ng-click="visualizeResource(\'' . $datasetId . '\', \'' . $resourceId . '\')">
							Visualiser
						</button>
					';
				}

				if ($format == "WMS" || strpos($url, "wms") !== false) {
					$classImg = 'fa-globe';

					$button .= '
						<button class="btn btn-info" ng-click="openMapfishapp(\'' . $name . '\', \'' . $url . '\', \'wms\')">
							Consulter
						</button>
					';
				}
				else {
					// $buttonText = strpos($protocol, 'download') !== false || strpos($protocol, 'DOWNLOAD') !== false ? 'Télécharger' : 'Consulter';
					$buttonText = 'Consulter';
					$button .= '<a class="btn btn-info" role="button" target="_blank" href="' . $url . '" >' . $buttonText . '</a>';
				}

				$resourcesUrl[] = $url;

				$additionnalResources .= '
					<div class="row">
						<div class="col-sm-9 download-item">
							<i class="fa ' . $classImg . ' inline download-img" fa-4x></i>
							<div class="inline">
								<div class="download-text">' . $name . '</div>
								<a target="_blank" href="' . $url . '" class="download-link">' . $url . '</a>
							</div>
						</div>
						<div class="col-sm-3">
							' . $button . '
						</div>
					</div>
				';

				// $index++;
			}
		}

		return $additionnalResources;
	}

	function buildKeywords($keywords) {
		if ($keywords == null) {
			return null;
		}

		$keywordsPart = '';

		foreach($keywords as $keyword) {
			$keywordsPart .= '<span class="text-uppercase badge bg-primary margin-right-1 padding-x-2 padding-y-1">' . $keyword . '</span>';
		}

		return $keywordsPart;
	}

	function buildRating($loggedIn, $datasetId) {
		return '<d4c-dataset-rating context="ctx" logged-in="' . $loggedIn . '" dataset-id="' . $datasetId . '" preset="ctx.dataset.is_subscribed"></d4c-dataset-rating>';
	}

	function buildKPI($loggedIn, $datasetId, $dataset, $selectedResourceId) {
		//We check if the module data_bfc exist and is enabled
		$moduleHandler = \Drupal::service('module_handler');
		if ($moduleHandler->moduleExists('data_bfc')) {

			$apiManager = new VanillaApiManager();
			$kpiInfos = $apiManager->getKpis($datasetId);

			// If there is an error, we do not display the create indicator part
			if ($kpiInfos['status'] == 'error') {
				return '
					<div>
						<p>Il y a une erreur dans la récupération des indicateurs (' . $kpiInfos['message'] . ')
					</div>
				';
			}

			$kpis = $kpiInfos['result'];
			
			//Getting current resourceId
			if ($selectedResourceId == null) {
				$resources = $dataset["metas"]["resources"];
				if (sizeof($resources) > 0 ) {
					$selectedResourceId = $this->getLastDataResource($resources);
				}
			}

			$selectedResource = $this->getDataResource($resources, $selectedResourceId);
			if ($selectedResource == null || $selectedResource["datastore_active"] != true) {
				return null;
			}

			// We disable this part for now
			// $newKPIPart = '';
			// $userManager = new UserManager();
			// if ($loggedIn && ($userManager->isConnectedUserAdmin() || $userManager->isConnectedUserRO())) {

			// 	$newKPIPart = '
			// 		<div class="row">
			// 			<a href="{{ path(\'data_bfc.ro_kpi_create\', { \'datasetId\': \'' . $datasetId . '\', \'resourceId\': \'' . $selectedResourceId . '\' }) }}" target="_self"><button class="btn btn-primary">Créer un indicateur</button></a>
			// 		</div>
			// 	';
			// }

			$kpiPart = '';
			if (isset($kpis)) {
				foreach ($kpis as $kpi) {
					$kpiPart .= '
						<div>
							<div class="col-sm-9 download-item">
								<i class="fa fa-gauge-high inline download-img" fa-4x></i>
								<div class="inline">
									<div class="download-text">' . $kpi['nameService'] . '</div>
								</div>
							</div>
							<div class="col-sm-3">
								' .
								// <a href="{{ path(\'data_bfc.ro_vanillahub_manage\', { \'vanillaHubId\': ' . $kpi['hubId'] . '}) }}" target="_self" class="use-ajax" data-dialog-type="modal" data-backdrop="static" ><button class="btn btn-primary">Gestion Vanilla Hub</button></a>
								'<a href="{{ path(\'ckan_admin.visualisation\', { \'id\': \'' . $kpi['targetDatasetName'] . '\'}) }}" target="_blank"><i class="fa fa-arrow-up-right-from-square" title="Ouvir la connaissance"></i></a>
							</div>
						</div>
					';
				}
			}

			return '
				<div>
					<div class="row">
						' . $kpiPart . '
					</div>
				</div>
			';
		}

		return null;
	}

	function getLastDataResource($resources) {
		$lastResource = null;
		foreach($resources as $key=>$value) {
			$resourceId = $value["id"];
			$mimeType = $value["mimetype"];
			$datastoreActive = $value["datastore_active"];
			
			if ($mimeType == "text/csv" && $datastoreActive == true) {
				$lastResource = $resourceId;
			}
		}
		return $lastResource;
	}

	function getDataResource($resources, $selectedResourceId) {
		foreach($resources as $key=>$value) {
			$resourceId = $value["id"];
			if ($selectedResourceId == $resourceId) {
				return $value;
			}
		}
		return null;
	}

	function buildRgpd($isRgpd, $rgpdNonConnected) {
		if (!$isRgpd) {
			return null;
		}

		if ($rgpdNonConnected) {
			$messageRgpd = "Ce jeu de données nécessite d'être connectée pour être consulté car il contient des données RGPD.";
		}
		else {
			$messageRgpd = "Cette connaissance contient des données RGPD. Les actions sur le jeu de données sont enregistrées.";
		}

		return '
			<div class="row">
				<div class="col-sm-12" style="color: red;">
					<p>' . $messageRgpd . '</p>
				</div>
			</div>
		';
	}

	/* END METADATA */

	function buildTabTable($loggedIn) {
		return '
			<d4c-pane title="Table" icon="table" translate="title" slug="table">
				<d4c-table context="ctx" auto-resize="true" dataset-feedback="true"></d4c-table>
			
				<d4c-embed-control context="ctx"
					force-embed-dataset-card="false"
					anonymous-access="true"
					embed-type="table"
					logged-in="' . $loggedIn . '"></d4c-embed-control>
			</d4c-pane>
		';
	}

	function buildTabMap($loggedIn, $dataset, $metadataExtras, $location) {
		$resources = $dataset["metas"]["resources"];

		$customBounds = '';

		if ($location != null) {
			$location = $location;
		}
		else {
			//Disable to let the map decide the bounds
			//$location = $this->config->client->default_bounding_box;

			$bboxEastLong = $this->exportExtras($metadataExtras, 'bbox-east-long');
			$bboxNorthLat = $this->exportExtras($metadataExtras, 'bbox-north-lat');
			$bboxSouthLat = $this->exportExtras($metadataExtras, 'bbox-south-lat');
			$bboxWestLong = $this->exportExtras($metadataExtras, 'bbox-west-long');
			
			$bounds = '';
			if (!$this->isNullOrEmptyString($bboxEastLong) && !$this->isNullOrEmptyString($bboxNorthLat) && !$this->isNullOrEmptyString($bboxSouthLat) && !$this->isNullOrEmptyString($bboxWestLong)) {
				$bounds = $bboxEastLong . ',' . $bboxNorthLat . ',' . $bboxSouthLat . ',' . $bboxWestLong;
			}

			$customBounds = 'custom-bounds="' . $bounds . '"';
		}

		if (sizeof($resources) > 0 ) {
			foreach($resources as $key=>$value) {
				$name = $value["name"];
				$url = $value["url"];
				$format = $value["format"];

				//Removing parameters
				// $url = strtok($url, '?');

				if ($format == "WMS" || strpos($url, "wms") !== false) {
					$btnMapFishapp = '<a class="d4c-map-flux-wms" target="_blank" href="#" ng-click="$event.preventDefault();openMapfishapp(\'' . $name . '\', \'' . $url . '\', \'wms\')"><i class="fa" aria-hidden="true"></i> <span translate=""><span class="ng-scope">Editer en mode avancé</span></span></a>';
				}
			}
		}

		return '
			<d4c-pane pane-auto-unload="true" title="Map" icon="globe" translate="title" slug="map" do-not-register="!ctx.dataset.hasFeature(\'geo\') && !ctx.dataset.hasWMS()" class="d4c-dataset-visualization__tab-map">
				<d4c-map context="ctx" location="' . $location . '" ' . $customBounds . ' sync-to-url="true" auto-resize="true"></d4c-map>

				' . $btnMapFishapp . '
			
				<d4c-embed-control context="ctx"
					force-embed-dataset-card="false"
					anonymous-access="true"
					embed-type="map"
					logged-in="' . $loggedIn . '"></d4c-embed-control>
			</d4c-pane>
		';
	}

	function buildTabAnalyze($loggedIn) {
		return '
			<d4c-pane pane-auto-unload="true" title="Analyze" icon="chart-bar" translate="title" slug="analyze" do-not-register="!ctx.dataset.hasFeature(\'analyze\')">
				<d4c-analyze context="ctx" sync-to-url="true"></d4c-analyze>
	
				<d4c-embed-control context="ctx"
					force-embed-dataset-card="false"
					anonymous-access="true"
					embed-type="analyze"
					logged-in="' . $loggedIn . '"></d4c-embed-control>
			</d4c-pane>
		';
	}

	function buildTabImage($loggedIn) {
		return '
			<d4c-pane pane-auto-unload="true" title="Images" icon="picture-o" translate="title" slug="images" do-not-register="!ctx.dataset.hasFeature(\'image\')">
				<d4c-media-gallery context="ctx" d4c-auto-resize d4c-widget-tooltip></d4c-media-gallery>
  
				<d4c-embed-control context="ctx"
					force-embed-dataset-card="false"
					anonymous-access="true"
					embed-type="media-gallery"
					logged-in="' . $loggedIn . '"></d4c-embed-control>
			</d4c-pane>
		';
	}

	function buildTabCalendar($loggedIn) {
		return '
			<d4c-pane pane-auto-unload="true" title="Calendar" icon="calendar" translate="title" slug="calendar" do-not-register="!ctx.dataset.hasFeature(\'calendar\')">
  				<d4c-calendar context="ctx" sync-to-url="true"></d4c-calendar>
  
				<d4c-embed-control context="ctx"
					force-embed-dataset-card="false"
					anonymous-access="true"
					embed-type="calendar"
					logged-in="' . $loggedIn . '"></d4c-embed-control>
			</d4c-pane>
		';
	}

	function buildTabCustomView($loggedIn) {
		return '
			<d4c-pane pane-auto-unload="true"
					title="\{\{ ctx.dataset.extra_metas.visualization.custom_view_title || DefaultCustomViewConfig.title \}\}"
					slug="\{\{ ctx.dataset.extra_metas.visualization.custom_view_slug || DefaultCustomViewConfig.slug \}\}"
					icon="\{\{ ctx.dataset.extra_metas.visualization.custom_view_icon || DefaultCustomViewConfig.icon \}\}"
					do-not-register="!ctx.dataset.hasFeature(\'custom_view\')">
				<div d4c-bind-angular-content="ctx.dataset.extra_metas.visualization.custom_view_html" do-not-decode-content></div>
				<style type="text/css" d4c-bind-angular-content="ctx.dataset.extra_metas.visualization.custom_view_css"></style>

				<d4c-embed-control context="ctx"
					force-embed-dataset-card="false"
					anonymous-access="true"
					embed-type="custom"
					logged-in="' . $loggedIn . '"></d4c-embed-control>
			</d4c-pane>
		';
	}

	function buildTabWordCloud($loggedIn) {
		return '
			<d4c-pane pane-auto-unload="true" title="Word Cloud" icon="cloud" translate="title" slug="wordcloud" do-not-register="!ctx.dataset.hasFeature(\'wordcloud\')">
				<d4c-wordcloud context="ctx" sync-to-url="true"></d4c-wordcloud>
		
				<d4c-embed-control context="ctx"
					force-embed-dataset-card="false"
					anonymous-access="true"
					embed-type="wordcloud"
					logged-in="' . $loggedIn . '"></d4c-embed-control>
			</d4c-pane>
		';
	}

	function buildTabTimeline($loggedIn) {
		return '
			<d4c-pane pane-auto-unload="true" title="Frise chronologique" icon="history" translate="title" slug="timeline" do-not-register="!ctx.dataset.hasFeature(\'timeline\')">
  				<d4c-timeline context="ctx" sync-to-url="true"></d4c-timeline>
  
				<d4c-embed-control context="ctx"
					force-embed-dataset-card="false"
					anonymous-access="true"
					embed-type="timeline"
					logged-in="' . $loggedIn . '"></d4c-embed-control>
			</d4c-pane>
		';
	}

	function buildServiceUrl($metadataExtras, $serviceUrl, $type, $layerName, $maxFeatures) {
		$bboxEastLong = $this->exportExtras($metadataExtras, 'bbox-east-long');
		$bboxNorthLat = $this->exportExtras($metadataExtras, 'bbox-north-lat');
		$bboxSouthLat = $this->exportExtras($metadataExtras, 'bbox-south-lat');
		$bboxWestLong = $this->exportExtras($metadataExtras, 'bbox-west-long');

		$bbox = $bboxWestLong . "," . $bboxSouthLat . "," . $bboxEastLong . "," . $bboxNorthLat;

		$width = "1024";
		$height = "1024";

		//Not used for now
		// $maxFeatures = '&maxFeatures=' . $maxFeatures;

		// Default projection
		$srs = "EPSG%3A4326";

		$serviceUrlWithoutParams = explode("?", $serviceUrl)[0];
		$queryString = explode("?", $serviceUrl)[1];
		
		if ($type == "WMS") {
			$queryString = Tools::updateQueryStringParameter($queryString, "service", $type);
			$queryString = Tools::updateQueryStringParameter($queryString, "version", '1.1.0');
			$queryString = Tools::updateQueryStringParameter($queryString, "request", 'GetMap');
			$queryString = Tools::updateQueryStringParameter($queryString, "layers", $layerName);
			$queryString = Tools::updateQueryStringParameter($queryString, "bbox", $bbox);
			$queryString = Tools::updateQueryStringParameter($queryString, "width", $width);
			$queryString = Tools::updateQueryStringParameter($queryString, "height", $height);
			$queryString = Tools::updateQueryStringParameter($queryString, "srs", $srs);
			$queryString = Tools::updateQueryStringParameter($queryString, "format", '');
			
			// $url = $serviceUrl . '?service=' . $type . '&version=1.3.0&request=GetMap&layers=' . $layerName . '&bbox=' . $bbox . '&width=' . $width . '&height=' . $height . '&srs=' . $srs . '&styles=&format=';
		}
		else if ($type == "WFS") {
			$queryString = Tools::updateQueryStringParameter($queryString, "service", $type);
			$queryString = Tools::updateQueryStringParameter($queryString, "version", '1.0.0');
			$queryString = Tools::updateQueryStringParameter($queryString, "request", 'GetFeature');
			$queryString = Tools::updateQueryStringParameter($queryString, "typeName", $layerName);
			$queryString = Tools::updateQueryStringParameter($queryString, "outputFormat", '');

			// $url = $serviceUrl . '?service=' . $type . '&version=1.0.0&request=GetFeature&typeName=' . $layerName . '&outputFormat=';
		}

		$url = $serviceUrlWithoutParams . '?' . $queryString;
		Logger::logMessage("TRM - Service URL: " . $url);

		// &request=GetMap&layers=cd67%3ACD67_ACTIONS_CULTURELLES_POINT_BR_CC48&bbox=1998243.2536231296%2C7226690.428528496%2C2079043.9612258154%2C7324887.552493705&width=631&height=768&srs=EPSG%3A3948&styles=&format=
		// &request=GetFeature&typeName=cd67%3ACD67_ACTIONS_CULTURELLES_POINT_BR_CC48&maxFeatures=50&outputFormat=
		return $url;
	}

	function getAvailableFormats($serviceUrl, $type) {
		$formats = array();

		$serviceUrlWithoutParams = explode("?", $serviceUrl)[0];
		$queryString = explode("?", $serviceUrl)[1];

		$queryString = Tools::updateQueryStringParameter($queryString, "service", $type);
		$queryString = Tools::updateQueryStringParameter($queryString, "request", 'GetCapabilities');
		$queryString = Tools::updateQueryStringParameter($queryString, "version", '1.1.0');
		$url = $serviceUrlWithoutParams . '?' . $queryString;

		$xml = simplexml_load_file($url);

		$formats = array();
		if ($type == "WMS") {
			foreach($xml->Capability->Request->GetMap->Format as $format) {
				$format = urlencode($format);
				$displayValue = $this->translateValue($this->locale["codelists"]["MD_FormatGeoExportWMS"], $format);

				$formatValue = array(
					"format" => $format,
					"displayValue" => $displayValue
				);
				$formats[] = $formatValue;
			}
		}
		else if ($type == "WFS") {
			$extractedFormats = $xml->xpath('//ows:OperationsMetadata/ows:Operation[@name="GetFeature"]/ows:Parameter[@name="outputFormat"]/ows:Value');
			foreach ($extractedFormats as $format) {
				$format = urlencode($format);
				$displayValue = $this->translateValue($this->locale["codelists"]["MD_FormatGeoExportWFS"], $format);
				
				$formatValue = array(
					"format" => $format,
					"displayValue" => $displayValue
				);
				$formats[] = $formatValue;
			}
		}

		usort($formats, function($a, $b) {
			return $a['displayValue'] <=> $b['displayValue'];
		});

		// Logger::logMessage("TRM - Available formats: " . json_encode($formats));

		return $formats;
	}

	function buildTabExport($dataset, $metadataExtras) {
		$resources = $dataset["metas"]["resources"];

		$exportGeo = '';
		$additionnalResources = '<ul class="d4c-dataset-export__format-choices">';
		$maxFeatures = 50;

		if (sizeof($resources) > 0 ) {
			foreach($resources as $key=>$value) {
				$name = $value["name"];
				$url = $value["url"];
				$format = $value["format"];

				//Removing parameters
				$displayName = strtok($url, '?');

				if ($format == "WMS" || strpos($url, "wms") !== false) {
					$wmsURL = $this->buildServiceUrl($metadataExtras, $url, "WMS", $name, $maxFeatures);
					$availableFormatsWMS = $this->getAvailableFormats($url, "WMS");

					$additionnalResources .= '
						<li class="d4c-dataset-export__format-choice ng-scope">
							<div class="d4c-dataset-export-link">
								<span class="d4c-dataset-export-link__format-name d4c-dataset-export-link__format-name--alternative geo-format">WMS</span>
								<span class="d4c-dataset-export-link__format-name--alternative geo-title " style="width: 30rem;display: inline-block;vertical-align: top;">' . $name . '</span>
								<span class="d4c-dataset-export-link__format-name--alternative geo-title " style="width: 30rem;display: inline-block;vertical-align: top;">&nbsp; - &nbsp;' . $displayName . '</span>
								<a class="d4c-dataset-export-link__link" target="_blank" href="#" ng-click="$event.preventDefault();openMapfishapp(\'' . $name . '\', \'' . $url . '\', \'wms\')"><i class="fa fa-link" aria-hidden="true"></i> <span translate=""><span class="ng-scope">Consulter</span></span></a>
								<a class="d4c-dataset-export-link__link" target="_blank" ng-href="" endverbatim="" href=""></a>
							</div>
						</li>
					';
				}
				else if ($format == "WFS" || strpos($url, "wfs") !== false) {
					$wfsURL = $this->buildServiceUrl($metadataExtras, $url, "WFS", $name, $maxFeatures);
					$availableFormatsWFS = $this->getAvailableFormats($url, "WFS");

					$additionnalResources .= '
						<li class="d4c-dataset-export__format-choice ng-scope">
							<div class="d4c-dataset-export-link">
								<span class="d4c-dataset-export-link__format-name d4c-dataset-export-link__format-name--alternative geo-format">WFS</span>
								<span class="d4c-dataset-export-link__format-name--alternative geo-title " style="width: 30rem;display: inline-block;vertical-align: top;">' . $name . '</span>
								<span class="d4c-dataset-export-link__format-name--alternative geo-title " style="width: 30rem;display: inline-block;vertical-align: top;">&nbsp; - &nbsp;' . $displayName . '</span>
								<a class="d4c-dataset-export-link__link" target="_blank" href="' . $url . '" ><i class="fa fa-link" aria-hidden="true"></i> <span translate=""><span class="ng-scope">Consulter</span></span></a>
								<a class="d4c-dataset-export-link__link" target="_blank" ng-href="" endverbatim="" href=""></a>
							</div>
						</li>
					';
				}
			}
		}

		$additionnalResources .= '</ul>';

		if (isset($wmsURL) || isset($wfsURL)) {
			if (isset($availableFormatsWMS) && count($availableFormatsWMS) > 0) {
				$optionsWMS = '<optgroup label="WMS">';

				foreach ($availableFormatsWMS as $format) {
					$optionsWMS .= '<option value="' . $format["format"] . '">' . $format["displayValue"] . '</option>';
				}

				$optionsWMS .= '</optgroup>';
			}

			if (isset($availableFormatsWFS) && count($availableFormatsWFS) > 0) {
				$optionsWFS = '<optgroup label="WFS">';

				foreach ($availableFormatsWFS as $format) {
					$optionsWFS .= '<option value="' . $format["format"] . '">' . $format["displayValue"] . '</option>';
				}

				$optionsWFS .= '</optgroup>';
			}


			$exportGeo .= '
				<div class="d4c-dataset-export ng-scope ng-isolate-scope">
					<h3>Exports géographiques</h3>
					' . $additionnalResources . '
					<select id="d4c-select-download-resource" ng-model="selectedItem" ng-change="downloadResource(\'' . $wmsURL . '\', \'' . $wfsURL . '\', selectedItem)">
						<option ng-selected="true">Choisir une couche</option>
						' . $optionsWMS . '
						' . $optionsWFS . '
					</select>
				</div>
			';
		}

		return '
			<d4c-pane pane-auto-unload="true" title="Export" icon="download" translate="title" slug="export">
				<d4c-dataset-export context="ctx" shapefile-export-limit="50000" snapshots="false"></d4c-dataset-export>

				' . $exportGeo . '
			</d4c-pane>
		';
	}
	
	function buildTabAPI($dataset) {
		$resources = $dataset["metas"]["resources"];

		$flux = '';

		if (sizeof($resources) > 0 ) {
			foreach($resources as $key=>$value) {
				$name = $value["name"];
				$url = $value["url"];
				$format = $value["format"];

				//Removing parameters
				// $url = strtok($url, '?');

				if ($format == "WMS" || strpos($url, "wms") !== false) {
					$flux .= '
						<div class="d4c-api-carto">
							<span class="d4c-api-carto-format">WMS</span>
							<div class="d4c-api-carto-flux">
								<span class="d4c-api-carto-flux-title" style="width: 30rem;display: inline-block;vertical-align: top;">Flux cartographique WMS</span>
								<span class="d4c-api-carto-flux-url" style="width: 30rem;display: inline-block;vertical-align: top;">' . $url . '</span>
							</div>
							<span class="d4c-api-carto-format">Couche: ' . $name . '</span>
						</div>
					';
				}
				else if ($format == "WFS" || strpos($url, "wfs") !== false) {
					$flux .= '
						<div class="d4c-api-carto">
							<span class="d4c-api-carto-format">WFS</span>
							<div class="d4c-api-carto-flux">
								<span class="d4c-api-carto-flux-title" style="width: 30rem;display: inline-block;vertical-align: top;">Flux cartographique WFS</span>
								<span class="d4c-api-carto-flux-url" style="width: 30rem;display: inline-block;vertical-align: top;">' . $url . '</span>
							</div>
							<span class="d4c-api-carto-format">Couche: ' . $name . '</span>
						</div>
					';
				}
			}
		}

		$apiGeo = '';
		if (!$this->isNullOrEmptyString($flux)) {
			$apiGeo .= '
				<h3>API Cartographiques</h3>
				' . $flux . '
			';
		}

		return '
			<d4c-pane pane-auto-unload="true" title="API" icon="cogs"  translate="title" slug="api">
				' . $apiGeo . '
			
				<h3>API Data4Citizen</h3>
				<d4c-dataset-api-console context="ctx"></d4c-dataset-api-console>
			</d4c-pane>
		';
	}
	
	function buildTabReuses($loggedIn, $name) {
		return '
			<d4c-pane pane-auto-unload="true" title="Reuses" icon="cogs"  translate="title" slug="reuses">
				<d4c-dataset-reuses readonly="false"
					max="3"
					anonymous-reuse="true"
					logged-in="'.$loggedIn.'" recaptcha-pub-key="6LcT58UaAAAAAD_bIB7iAAeSJ6WggtNaFS74GbGk" dataset-title="'.$name.'"
					config="{&#39;is_unique&#39;: True, &#39;max_width&#39;: 4096, &#39;max_height&#39;: 4096, &#39;resize_width&#39;: 200, &#39;resize_height&#39;: 200, &#39;asset_type&#39;: &#39;image&#39;, &#39;max_size&#39;: 2097152}"></d4c-dataset-reuses>
			</d4c-pane>
		';
	}
	
	function buildTabAdmin($dataset, $name) {
		$fields = $dataset["fields"];

		$tableHeader = '<tr>';

		$displayEditor = false;
		if (sizeof($fields) > 0 ) {
			foreach($fields as $key=>$value) {
				$canEdit = false;
				if (sizeof($value["annotations"]) > 0 ) {
					foreach($value["annotations"] as $key2=>$annotation) {
						if ($annotation["name"] == "can_edit") {
							$canEdit = true;
							break;
						}
					}
				}

				if ($canEdit) {
					$displayEditor = true;
					$tableHeader .= '<th>' . $value["name"] . '</th>';
				}
			}

			if (!$displayEditor) {
				// If no column has been mark as editable, we put all of them
				foreach($fields as $key=>$value) {
					$displayEditor = true;
					$tableHeader .= '<th>' . $value["name"] . '</th>';
				}
			}
		}

		$tableHeader .= '</tr>';

		$buttonEditor = $displayEditor ? '
			<a id="btn-edit-data" ng-click="editData()">
				<img alt="Editer le jeu de données" data-entity-type="file" data-entity-uuid="" src="/sites/default/files/api/portail_d4c/img/edit.png">
				<span>Editer le jeu de données</span>
			</a>' : '';

		$displayValidate = false;
		$buttonValidateData = $displayValidate ? '
			<a id="btn-validate-data" ng-click="validateData()">
				<img alt="Valider les données" data-entity-type="file" data-entity-uuid="" src="/sites/default/files/api/portail_d4c/img/checked.png">
				<span>Valider les données</span>
			</a>' : '';

		return '
			<d4c-pane pane-auto-unload="true" title="Administration" icon="cogs"  translate="title" slug="admin">
				<details open>
					<summary>Administration</summary>
					<div>
						' . $buttonEditor . '
						' . $buttonValidateData . '
					</div>
				</details>
				<div style="width: 100%;">
					<table id="edit_table" class="display" style="display: none; width: 100%">
						<thead>
							' . $tableHeader . '
						</thead>
					</table>
				</div>
			</d4c-pane>
		';
	}
	
	function buildDisqus($host, $dataset) {
		if ($this->config->client->disqus) {
			return '
				<d4c-disqus
					shortname="data4citizen"
					identifier="' . $host . "_" . $dataset["datasetid"] . '">
				</d4c-disqus>
			';
		}
		else {
			return '';
		}
	}

	function buildImports($id, $name, $description, $url, $dateModified, $licence, $keywords, $resources) {
		return '
			<script src="'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/jquery-3.2.1.js"></script>
			<script type="text/javascript" src="'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/bootstrap.min.js"></script>
			<script type="text/javascript" src="'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/libraries.js"></script>
			<script type="text/javascript" src="'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/lib/qtip/jquery.qtip.min.js"></script>	
			<script type="text/javascript" src="'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/lib/fullcalendar/moment.min.js"></script>
			<script type="text/javascript" src="'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/lib/fullcalendar/fullcalendar.min.js"></script>
			<script type="text/javascript" src="'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/lib/fullcalendar/lang/fr.js"></script>
			<script type="text/javascript" src="'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/angular-core.js"></script>
			
			<script type="text/javascript">
				$(".d4c-content").html($(".d4c-content").html().replace(/\\\{\\\{/g,\'\{\{\').replace(/\\\}\\\}/g,\'}}\').replace(/\\\{/g,\'\{\').replace(/\\\}/g,\'}\'));
				$(".main-container").removeClass("container").removeClass("main-container").css( "margin-top", "-20px" ).css( "margin-bottom", "-45px" );
				var mod = angular.module(\'d4c.core.config\', []);
		
				mod.factory("config", [function() {
					return {
						ID_DATASET: "'.$id.'",
						HOST: "'.$this->config->client->domain.'"
					}
				}]);
			</script>
		
			<script type="text/javascript" src="'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/i18n.js"></script>
			<script src="'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/supported-browsers-message.js" type="text/javascript"></script>
			<script type="text/javascript" src="'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/angular-visu.js"></script>
			<script type="text/javascript" src="'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/popularDataset.js"></script>
			<script type="text/javascript" src="'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/lib/DataTables/datatables.min.js"></script>
			<script type="text/javascript" src="'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/lib/DataTables/Plugins/ellipsis.js"></script>

			<script>
				//$("head").append("<link href=\"'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/visualisation.css\" rel=\"stylesheet\">");
				$("head").append("<link href=\"'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/normalize.css\" rel=\"stylesheet\">");
				//$("head").append("<link href=\"'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/d4cui.css\" rel=\"stylesheet\">");
				//$("head").append("<link href=\"'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/bootstrap.min.css\" rel=\"stylesheet\">");
				$("head").append("<link href=\"'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/font-awesome.min.css\" rel=\"stylesheet\">");
				$("head").append("<link href=\"'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/style.css\" rel=\"stylesheet\">");
				$("head").append("<link href=\"'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/lib/DataTables/datatables.min.css\" rel=\"stylesheet\" type=\"text/css\"/>");
				
				$("head").append("<base href=\"/\">");
					
			</script>
		
			<script type="application/ld+json">
				{
					"@context":"http://schema.org/",
					"@type":"Dataset",
					"name":"'.$name.'",
					"description":"'.$description.'",
					"url":"'.$url.'",
					"dateModified": "'.$dateModified.'",
					"keywords": '.json_encode($keywords).',
					"distribution": '.json_encode($resources, JSON_UNESCAPED_SLASHES).',
					"license": "'.$licence.'"
				}
			</script>
		';
	}

	// UTILS

	function exportExtras($metadata, $metadataName) {
		return current(array_filter($metadata, function($elem) use($metadataName){
			return $elem['key'] == $metadataName;
		}))["value"];
	}

	function translateValue($locales, $key) {
		$translatedValue = current(array_filter($locales, function($elem) use($key){
			return $elem['value'] == $key;
		}))["text"];

		return $translatedValue != null && $translatedValue != '' ? $translatedValue : $key;
	}

	function cleanSimpleJson($value, $decodeHtml = false) {
		$value = str_replace('{', '', $value);
		$value = str_replace('}', '', $value);
		$value = str_replace('"', '', $value);
		return $value != '' ? $value : null;
	}

	function isNullOrEmptyString($str){
		return (!isset($str) || trim($str) === '');
	}

	/* Other types of dataset */
	function buildTabVisualization($api, $metadataExtras) {
		$type = $this->exportExtras($metadataExtras, 'data4citizen-type');
		$entityId = $this->exportExtras($metadataExtras, 'data4citizen-entity-id');

		if ($type == 'visualization') {
			$visualization = $api->getVisualization($entityId);

			$visualizationPart = '';
			if (isset($visualization)) {
				$iframeUrl = $visualization['share_url'];
	
				$visualizationPart = '<iframe src=' . $iframeUrl . ' frameborder="0" width="100%" height="600px"></iframe>';
			}
			else {
				$visualizationPart = 'La visualisation n\'est pas disponible';
			}
	
			return '
				<d4c-pane pane-auto-unload="true" title="Visualization" icon="list" translate="title" slug="visualization">
					' . $visualizationPart . '
				</d4c-pane>
			';
		}
		else {
			return '';
		}
	}
}

