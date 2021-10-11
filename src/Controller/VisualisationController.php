<?php
namespace Drupal\ckan_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
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
class VisualisationController extends ControllerBase {

	private $config;
	private $locale;
    
	public function __construct(){
        $this->config = json_decode(file_get_contents(__DIR__ ."/../../config.json"));
		$this->locale = json_decode(file_get_contents(__DIR__ ."/../../locales.fr.json"), true);
    }

	public function myPage(Request $request, $tab) {
		$id = $request->query->get('id');
		$resourceId = $request->query->get('resourceId');
		return $this->myPage2($id, $resourceId, $tab);
	}

	/**
	 * Returns a simple page.
	 *
	 * @return array
	 *   A simple renderable array.
	 */
	public function myPage2($id, $resourceId, $tab) {
		\Drupal::service('page_cache_kill_switch')->trigger();

		$host = \Drupal::request()->getHost();
		$protocol = \Drupal::request()->getScheme()."://";
		
		$api = new API();

		$dataset = $api->getPackageShow2($id, "", true, false, $resourceId);

		$name = $dataset["metas"]["title"];
		$description = $dataset["metas"]["description"];
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
		$body = $this->buildBody($api, $host, $dataset, $tab, $id, $resourceId, $name, $description, $url, $dateModified, $licence, $keywords, $exports, $metadataExtras);
		 
		$element = array(
			'example one' => [
				'#type' => 'inline_template',
				'#template' => $body,
						
			],
		);
		$element['#attached']['library'][] = 'ckan_admin/visu.angular';
		return $element;
	}

	function buildBody($api, $host, $dataset, $tab, $id, $resourceId, $name, $description, $url, $dateModified, $licence, $keywords, $exports, $metadataExtras) {
		
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
			$tab = 'table';
		}

		$ctx = str_replace(array("{", "}", '"'), array("\{", "\}", "&quot;"), json_encode($dataset));

		$themes = $this->buildTheme($api, $metadataExtras);
		$datasetTitle = $this->buildDatasetTitle($themes);
		// $imgTheme = $themes[0];
		// $themes = $themes[1];
		
		$resourcesList = $this->buildResourcesList($id, $dataset, $resourceId);

		$filters = $this->buildFilters($id, $dataset, $resourceId);
		$tabs = $this->buildTabs($tab, $dataset, $id, $name, $description, $themes, $metadataExtras, $keywords, $resourceId);
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
								' . $filters . '<div class="d4c-dataset-visualization" ng-class="{\'d4c-dataset-visualization--full-width\': !canAccessData()}">
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
				<a class="d4c-button d4c-filters-summary__toggle" ng-click="toggleMobileFilters()" href="#d4c-filters">
					<i class="fa" aria-hidden="true" ng-class="{\'fa-expand\': !toggleState.expandedFilters, \'fa-compress\': toggleState.expandedFilters}"></i>
					Filtres
				</a>
			</div>
			<div id="d4c-filters" class="d4c-filters" ng-show="canDisplayFilters() && canAccessData()">
				<a class="closed" href="#">x</a>
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

	function buildResourcesList($datasetId, $dataset, $selectedResourceId) {
		$resources = $dataset["metas"]["resources"];
		$hasResources = false;

		$list = '<div class="d4c-resources-choices" ng-show="canDisplayFilters()">';
		$list .= '
				<select ng-model="selectedItem" class="form-control" ng-change="visualizeResource(\'' . $datasetId . '\', selectedItem)">
					<option value="" ng-if="false"></option>
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
					$hasResources = true;

					$isActif = ($selectedResourceId == null && $lastResourceId != null && $lastResourceId == $resourceId) || ($selectedResourceId == $resourceId);

					$list .= '
							<option value="' . $resourceId . '" ng-value="' . $resourceId . '" ' . ($isActif ? 'ng-selected="true"' : '') . '>' . $name . '</option>
					';
				}
			}
		}

		$list .= '	</select>';
		$list .= '</div>';

		return $hasResources ? $list : '';
	}

	function buildTabs($tab, $dataset, $id, $name, $description, $themes, $metadataExtras, $keywords, $selectedResourceId) {
		$loggedIn = \Drupal::currentUser()->isAuthenticated();

		$tabInformation = $this->buildTabInformation($loggedIn, $dataset, $id, $name, $description, $themes, $metadataExtras, $keywords, $selectedResourceId);
		$tabTable = $this->buildTabTable();
		$tabMap = $this->buildTabMap();
		$tabAnalyze = $this->buildTabAnalyze();
		$tabImage = $this->buildTabImage();
		$tabCalendar = $this->buildTabCalendar();
		$tabCustomView = $this->buildTabCustomView();
		$tabWordCloud = $this->buildTabWordCloud();
		$tabTimeline = $this->buildTabTimeline();
		$tabExport = $this->buildTabExport($dataset, $metadataExtras);
		$tabAPI = $this->buildTabAPI();
		$tabReuses = $this->buildTabReuses($loggedIn, $name);

		return '
			<d4c-tabs sync-to-url="true" sync-to-url-mode="path" name="main" default-tab="' . $tab . '">
				' . $tabInformation . '
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

				<!-- Enable this to enable dataset subscription -->
				<!-- <d4c-dataset-subscription context="ctx" logged-in="' . $loggedIn . '" dataset-id="' . $id . '" preset="ctx.dataset.is_subscribed"></d4c-dataset-subscription> -->
			</d4c-tabs>
		';
	}

	function buildTabInformation($loggedIn, $dataset, $datasetId, $name, $description, $themes, $metadataExtras, $keywords, $selectedResourceId) {
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
		$synthese = $this->buildSynthese($metadataExtras, $themes);

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

		return '
			<d4c-pane pane-auto-unload="true" title="Information" icon="info-circle" translate="title" slug="information">
				<div class="row">
					<div class="col-sm-9">
						' . $this->buildCard('Description', ($description != null && $description != '' ? $description : 'Aucune description des données renseigné')) . '
						' . $this->buildCard('Limites techniques d\'usage', ($limitesUtilisation != null ? $limitesUtilisation : 'Aucune limite technique d\'usage des données renseignée')) . '
						' . ($conditionsUtilisation != null ? $this->buildCard('Licences et conditions d\'utilisation', $conditionsUtilisation) : '') . '
						' . ($methodeProductionEtQualite != null ? $this->buildCard('Méthode de production et qualité', $methodeProductionEtQualite) : '') . '
						' . ($informationsGeo != null ? $this->buildCard('Informations géographiques', $informationsGeo) : '') . '
						' . ($downloadsAndLinks != null ? $this->buildCard('Données et ressources', $downloadsAndLinks) : '') . '
						' . ($keywordsPart != null ? $this->buildCard('Mots clefs', $keywordsPart) : '') . '
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
			$themeImage = $theme["url"];
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
		return $visu != null ? $visu : 1;
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

		// $useConstraints = $this->exportExtras($metadataExtras, 'use-constraints');

		// if ($useConstraints != null) {
		// 	$useConstraints = $this->cleanSimpleJson($useConstraints);
		// 	$useConstraints = '<li>' . $useConstraints . '</li>';
		// }

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
		if ($licence != null) {
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

		if ($accessConstraints != null) {
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

		if ($mentionLegales != null) {
			$hasValue = true;

			$mentionLegales = '<li>' . $mentionLegales . '</li>';
		}

		if (!$hasValue) {
			return null;
		}

		return '
			<ul class="m-0">
				' . $licence . '
				' . $accessConstraints . '
				' . $mentionLegales . '
			</ul>
		';
	}

	function buildSynthese($metadataExtras, $themes) {
		// TODO
		// $: isOpenData = helpers.arrayInArray(["opendata", "open data", "donnée ouverte", "données ouvertes"], dataKeywords);
		// $: dataTopicCategories = converter.getValue($storeMdjs, "dataTopicCategories") || [];
		// $: dataMaintenanceFrequency = converter.getValue($storeMdjs, "dataMaintenanceFrequency")[0] || "";
		// $: dataDate = getDataDate($storeMdjs);

		$frequence = $this->exportExtras($metadataExtras, 'frequence');
		$datasetDates = $this->exportExtras($metadataExtras, 'dataset-reference-date');

		$synthese = '';
	// 	return '
	// 	<div class="d4c-dataset-metadata-block__metadata ng-scope" style="font-size: 1rem; margin: -0.8em  0 -1em 0;">
	// 		<div class="d4c-dataset-metadata-block__metadata-name ng-binding" >Fréquence de mise à jour</div>
	// 		<div class="d4c-dataset-metadata-block__metadata-value d4c-dataset-metadata-block__metadata-value--default ng-binding ng-scope">  ' . $frequence . '</div>
	// 	</div>
	// ';

		// frequency-of-update	unknown


		// '<div class="my-3">
		// 	<button class="text-white btn btn-success btn-sm active" value="" style="">
		// 		<i class="bi-unlock-fill"></i>
		// 	</button>
		// 	<span class="ms-2">Données ouvertes</span>
		// </div>
		// <div class="my-3">
		// 	<button class="text-white btn btn-primary btn-sm active" value="" style="">
		// 		<i class="bi-geo-alt"></i>
		// 	</button>
		// 	<span class="ms-2">Donnée géographique</span>
		// </div>
		// <div class="my-3">
		// 	<button class="text-white btn btn-outline-secondary btn-sm active" value="" style="">
		// 		<i class="bi-clock"></i>
		// 	</button>
		// 	<span class="ms-2">Mise à jour inconnue</span>
		// </div>
		
		$nbDownloads = '
			<div class="d4c-dataset-metadata-block" ng-show="(ctx.dataset.metas.extras | filter:{key:\'nb_download\'})[0].value > 0">
				<div class="d4c-dataset-metadata-block__metadata">
					<div class="d4c-dataset-metadata-block__metadata-name" translate=""><span class="ng-scope">Téléchargements</span></div>
					<div class="d4c-dataset-metadata-block__metadata-value ng-binding">\{\{ +((ctx.dataset.metas.extras | filter:\{key:\'nb_download\'\})[0].value) \}\}</div>
				</div>
			</div>
		';

		// TODO: Date
		// Logger::logMessage("TRM - Last update date " . $lastDataUpdateDate);
		// [{"type": "creation", "value": "2019-11-12"}, {"type": "edition", "value": ""}, {"type": "publication", "value": "2019-11-12"}]
		// $synthese .= '
		// 	<div class="my-3" ng-show="\'' . $lastDataUpdateDate . '\'">
		// 		<i class="fa fa-clock-o"></i>>
		// 		<span class="ms-2" translate><strong>Last data update</strong></span>
		// 		<span>\{\{\'' . $lastDataUpdateDate . '\' | formatMeta:\'datetime\' \}\}</span>
		// 	</div>
		// ';

		$synthese .= '
			<div class="my-3">
				<i class="fa fa-clock-o"></i>
				<span class="ms-2">' . ($frequence != null ? $this->translateValue($this->locale["codelists"]["MD_MaintenanceFrequencyCode"], $frequence) : 'Mise à jour inconnue') . '</span>
			</div>
		';

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
	
	function buildMethodeProductionEtQualite($metadataExtras) {
		$lineage = $this->exportExtras($metadataExtras, 'lineage');
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
			$equivalentScale = json_decode($equivalentScale, true);
			// $equivalentScale = $this->cleanSimpleJson($equivalentScale);
		}
		
		$referenceSystem = $this->exportExtras($metadataExtras, 'spatial-reference-system');
		$resolution = $this->exportExtras($metadataExtras, 'spatial-resolution-units');

		if ($representationType == null && $bboxEastLong == null && $bboxNorthLat == null && $bboxSouthLat == null && $bboxWestLong
				&& $equivalentScale == null && $referenceSystem == null && $resolution == null) {
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
				$contactEmail = $organisation['contact-info']['email'];

				$contacts .= '
					<div class="d-flex align-items-center mt-3">
						<div class="flex-grow-1 ms-3"><strong id="displayContact1">' . $organisationName . '</strong>
							<p class="mb-0"><i class="fa fa-envelope"></i><a href="mailto:' . $contactEmail . '">contact</a></p>
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

		$resources = $dataset["metas"]["resources"];

		if (sizeof($resources) > 0 ) {
			// $lastResourceId = $this->getLastDataResource($resources);

			foreach($resources as $key=>$value){
				$resourceId = $value["id"];
				$name = $value["name"];
				$url = $value["url"];
				$format = $value["format"];
				$protocol = $value["resource_locator_protocol"];
				$mimeType = $value["mimetype"];
				$datastoreActive = $value["datastore_active"];

				$classImg = strpos($protocol, 'download') !== false || strpos($protocol, 'DOWNLOAD') !== false ? 'fa-download' : 'fa-link';

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
							Ouvrir dans Mapfishapp
						</button>
					';
				}
				else {
					$buttonText = strpos($protocol, 'download') !== false || strpos($protocol, 'DOWNLOAD') !== false ? 'Télécharger' : 'Consulter';

					$button .= '<a class="btn btn-info" role="button" target="_blank" href="' . $url . '" >' . $buttonText . '</a>';
				}

				// $isActif = ($selectedResourceId == null && $lastResourceId != null && $lastResourceId == $resourceId) || ($selectedResourceId == $resourceId) ? '<div class="inline download-active"></div>' : '';

				$additionnalResources .= '
					<div class="row">
						<div class="col-sm-9 download-item">
							' . $isActif . '
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
		if ($keyword == null) {
			return null;
		}

		$keywordsPart = '';

		foreach($keywords as $keyword) {
			$keywordsPart .= '<span class="text-uppercase badge bg-primary margin-right-1 padding-x-2 padding-y-1">' . $keyword . '</span>';
		}

		return $keywordsPart;
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

	/* END METADATA */

	function buildTabTable() {
		return '
			<d4c-pane pane-auto-unload="true" title="Table" icon="table" translate="title" slug="table">
				<d4c-table context="ctx" auto-resize="true" dataset-feedback="true"></d4c-table>
			
				<d4c-embed-control context="ctx"
					force-embed-dataset-card="false"
					anonymous-access="true"
					embed-type="table"></d4c-embed-control>
			</d4c-pane>
		';
	}

	function buildTabMap() {
		return '
			<d4c-pane pane-auto-unload="true" title="Map" icon="globe" translate="title" slug="map" do-not-register="!ctx.dataset.hasFeature(\'geo\')" class="d4c-dataset-visualization__tab-map">
				<d4c-map context="ctx" sync-to-url="true" auto-resize="true"></d4c-map>
			
				<d4c-embed-control context="ctx"
					force-embed-dataset-card="false"
					anonymous-access="true"
					embed-type="map"></d4c-embed-control>
			</d4c-pane>
		';
	}

	function buildTabAnalyze() {
		return '
			<d4c-pane pane-auto-unload="true" title="Analyze" icon="chart-bar" translate="title" slug="analyze" do-not-register="!ctx.dataset.hasFeature(\'analyze\')">
				<d4c-analyze context="ctx" sync-to-url="true"></d4c-analyze>
	
				<d4c-embed-control context="ctx"
					force-embed-dataset-card="false"
					anonymous-access="true"
					embed-type="analyze"></d4c-embed-control>
			</d4c-pane>
		';
	}

	function buildTabImage() {
		return '
			<d4c-pane pane-auto-unload="true" title="Images" icon="picture-o" translate="title" slug="images" do-not-register="!ctx.dataset.hasFeature(\'image\')">
				<d4c-media-gallery context="ctx" d4c-auto-resize d4c-widget-tooltip></d4c-media-gallery>
  
				<d4c-embed-control context="ctx"
					force-embed-dataset-card="false"
					anonymous-access="true"
					embed-type="media-gallery"></d4c-embed-control>
			</d4c-pane>
		';
	}

	function buildTabCalendar() {
		return '
			<d4c-pane pane-auto-unload="true" title="Calendar" icon="calendar" translate="title" slug="calendar" do-not-register="!ctx.dataset.hasFeature(\'calendar\')">
  				<d4c-calendar context="ctx" sync-to-url="true"></d4c-calendar>
  
				<d4c-embed-control context="ctx"
					force-embed-dataset-card="false"
					anonymous-access="true"
					embed-type="calendar"></d4c-embed-control>
			</d4c-pane>
		';
	}

	function buildTabCustomView() {
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
					embed-type="custom"></d4c-embed-control>
			</d4c-pane>
		';
	}

	function buildTabWordCloud() {
		return '
			<d4c-pane pane-auto-unload="true" title="Word Cloud" icon="cloud" translate="title" slug="wordcloud" do-not-register="!ctx.dataset.hasFeature(\'wordcloud\')">
				<d4c-wordcloud context="ctx" sync-to-url="true"></d4c-wordcloud>
		
				<d4c-embed-control context="ctx"
					force-embed-dataset-card="false"
					anonymous-access="true"
					embed-type="wordcloud"></d4c-embed-control>
			</d4c-pane>
		';
	}

	function buildTabTimeline() {
		return '
			<d4c-pane pane-auto-unload="true" title="Frise chronologique" icon="history" translate="title" slug="timeline" do-not-register="!ctx.dataset.hasFeature(\'timeline\')">
  				<d4c-timeline context="ctx" sync-to-url="true"></d4c-timeline>
  
				<d4c-embed-control context="ctx"
					force-embed-dataset-card="false"
					anonymous-access="true"
					embed-type="timeline"></d4c-embed-control>
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

		if ($type == "WMS") {
			$url = $serviceUrl . '?service=' . $type . '&version=1.1.0&request=GetMap&layers=' . $layerName . '&bbox=' . $bbox . '&width=' . $width . '&height=' . $height . '&srs=' . $srs . '&styles=&format=';
		}
		else if ($type == "WFS") {
			$url = $serviceUrl . '?service=' . $type . '&version=1.0.0&request=GetFeature&typeName=' . $layerName . '&outputFormat=';
		}

		Logger::logMessage("TRM - SERVICE URL " . $url);
		// &request=GetMap&layers=cd67%3ACD67_ACTIONS_CULTURELLES_POINT_BR_CC48&bbox=1998243.2536231296%2C7226690.428528496%2C2079043.9612258154%2C7324887.552493705&width=631&height=768&srs=EPSG%3A3948&styles=&format=
		// &request=GetFeature&typeName=cd67%3ACD67_ACTIONS_CULTURELLES_POINT_BR_CC48&maxFeatures=50&outputFormat=
		return $url;
	}

	function buildTabExport($dataset, $metadataExtras) {
		$resources = $dataset["metas"]["resources"];

		$maxFeatures = 50;

		if (sizeof($resources) > 0 ) {
			foreach($resources as $key=>$value) {
				$name = $value["name"];
				$url = $value["url"];
				$format = $value["format"];

				//Removing parameters
				$url = strtok($url, '?');

				if ($format == "WMS" || strpos($url, "wms") !== false) {
					$wmsURL = $this->buildServiceUrl($metadataExtras, $url, "WMS", $name, $maxFeatures);
				}
				else if ($format == "WFS" || strpos($url, "wfs") !== false) {
					$wfsURL = $this->buildServiceUrl($metadataExtras, $url, "WFS", $name, $maxFeatures);
				}
			}
		}

		// https://www.geograndest.fr/geoserver/cd67/wms?service=WMS&version=1.1.0&request=GetMap&layers=cd67%3ACD67_ACTIONS_CULTURELLES_POINT_BR_CC48&bbox=1998243.2536231296%2C7226690.428528496%2C2079043.9612258154%2C7324887.552493705&width=631&height=768&srs=EPSG%3A3948&styles=&format=
		// https://www.geograndest.fr/geoserver/cd67/ows?service=WFS&version=1.0.0&request=GetFeature&typeName=cd67%3ACD67_ACTIONS_CULTURELLES_POINT_BR_CC48&maxFeatures=50&outputFormat=

		if (isset($wmsURL) || isset($wfsURL)) {
			if (isset($wmsURL)) {
				$optionsWMS = '
					<optgroup label="WMS">
						<option value="application%2Fatom%20xml">AtomPub</option>
						<option value="application%2Fbil">BIL</option>
						<option value="image%2Fdds">DDS</option>
						<option value="image%2Fgif">GIF</option>
						<option value="rss">GeoRSS</option>
						<option value="image%2Fgeotiff">GeoTiff</option>
						<option value="image%2Fgeotiff8">GeoTiff 8-bits</option>
						<option value="image%2Fjpeg">JPEG</option>
						<option value="image%2Fvnd.jpeg-png">JPEG-PNG</option>
						<option value="image%2Fvnd.jpeg-png8">JPEG-PNG8</option>
						<option value="application%2Fvnd.google-earth.kmz%20xml">KML (compressé)</option>
						<option value="application%2Fvnd.google-earth.kml%2Bxml%3Bmode%3Dnetworklink">KML (lien réseau)</option>
						<option value="application%2Fvnd.google-earth.kml">KML (plain)</option>
						<option value="application%2Fx-sqlite3">MBTiles</option>
						<option value="text%2Fhtml%3B%20subtype%3Dopenlayers">OpenLayers</option>
						<option value="application%2Fopenlayers2">OpenLayers 2</option>
						<option value="application%2Fopenlayers3">OpenLayers 3</option>
						<option value="application%2Fpdf">PDF</option>
						<option value="image%2Fpng">PNG</option>
						<option value="image%2Fpng%3B%20mode%3D8bit">PNG 8bit</option>
						<option value="image%2Fsvg%20xml">SVG</option>
						<option value="image%2Ftiff">Tiff</option>
						<option value="image%2Ftiff8">Tiff 8-bits</option>
						<option value="application%2Fjson%3Btype%3Dutfgrid">UTFGrid</option>
					</optgroup>
				';
			}

			if (isset($wfsURL)) {
				$optionsWFS = '
					<optgroup label="WFS">
						<option value="csv">CSV</option>
						<option value="DXF-ZIP">DXF (Compressed)</option>
						<option value="DXF">DXF (plain)</option>
						<option value="excel">Excel (.xls)</option>
						<option value="excel2007">Excel 2007 (.xslx)</option>
						<option value="text%2Fxml%3B%20subtype%3Dgml%2F2.1.2">GML2</option>
						<option value="gml3">GML3.1</option>
						<option value="application%2Fgml%2Bxml%3B%20version%3D3.2">GML3.2</option>
						<option value="application%2Fjson">GeoJSON</option>
						<option value="application%2Fvnd.google-earth.kml%2Bxml">KML</option>
						<option value="SHAPE-ZIP">Shapefile</option>
					</optgroup>
				';
			}


			$exportGeo .= '
				<div class="d4c-dataset-export ng-scope ng-isolate-scope">
					<h3>Exports géographique</h3>
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
	
	function buildTabAPI() {
		return '
			<d4c-pane pane-auto-unload="true" title="API" icon="cogs"  translate="title" slug="api">
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
	
			<script>
				//$("head").append("<link href=\"'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/visualisation.css\" rel=\"stylesheet\">");
				$("head").append("<link href=\"'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/normalize.css\" rel=\"stylesheet\">");
				//$("head").append("<link href=\"'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/d4cui.css\" rel=\"stylesheet\">");
				//$("head").append("<link href=\"'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/bootstrap.min.css\" rel=\"stylesheet\">");
				$("head").append("<link href=\"'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/'.$this->config->client->css_file.'\" rel=\"stylesheet\">");
				$("head").append("<link href=\"'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/font-awesome.min.css\" rel=\"stylesheet\">");
				$("head").append("<link href=\"'. $this->config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/style.css\" rel=\"stylesheet\">");
				$("head").append("<base href=\"/\">");
					
			</script>
		
			<script type="application/ld+json">
			{
				"@context":"http://schema.org/",
				"@type":"Dataset",
				"name":"'.$name.'",
				"description":"'.$description.'",
				"url":"'.$url.'",
				"dateModified": "'.$dateModified.'"
				,
				"keywords": '.json_encode($keywords).'
				
			,
				"distribution": '.json_encode($resources, JSON_UNESCAPED_SLASHES).'
				
				,
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

	// Not working
	// function cleanSimpleJson($value, $decodeHtml = false) {
	// 	$value = str_replace('{', '', $value);
	// 	$value = str_replace('}', '', $value);
	// 	$value = str_replace('"', '', $value);

	// 	if ($decodeHtml) {
	// 		$value = $this->decodeHtml($value);
	// 	}

	// 	return $value;
	// }

	// function decodeHtml($value) {
	// 	$value = html_entity_decode($value);
	// 	return $value;
	// }
}

