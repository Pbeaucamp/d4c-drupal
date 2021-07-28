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

	public function myPage(Request $request, $tab) {
		$id = $request->query->get('id');
		return $this->myPage2($id, $tab);
	}

	/**
	 * Returns a simple page.
	 *
	 * @return array
	 *   A simple renderable array.
	 */
	public function myPage2($id, $tab) {
		\Drupal::service('page_cache_kill_switch')->trigger();

		Logger::logMessage("TRM - BUILD INFORMATIONS ");

		
		$config = json_decode(file_get_contents(__DIR__ ."/../../config.json"));
		$host = \Drupal::request()->getHost();
		$protocol = \Drupal::request()->getScheme()."://";
		
		$api = new API();

		$dataset = $api->getPackageShow2($id, "");


		$name = $dataset["metas"]["title"];
		$description = $dataset["metas"]["description"];
		$dateModified = $dataset["metas"]["modified"];
		$keywords = $dataset["metas"]["keyword"];
		$licence = $dataset["metas"]["license"];
        $metadataExtras = $dataset[metas][extras];

		$url = $protocol . $host . $this->config->client->routing_prefix . "/visualisation?id=" . $dataset["datasetid"];
		
		$resources = array();
		$resourcesid = "";
		//Last update date for the data (resources)
		$lastDataUpdateDate = null;
		foreach($dataset["metas"]["resources"] as $value) {
            if($value['format'] == 'CSV' || $value['format'] == 'XLS' || $value['format'] == 'XLSX'){
		 		$resourcesid = $value['id'];
		 	}

			if($value['format'] != 'CSV' && $value['format'] != 'XLS' && $value['format'] != 'XLSX' && $value['format'] != 'GeoJSON' && $value['format'] != 'JSON' && $value['format'] != 'KML' && $value['format'] != 'SHP'){
				$res = array();
				$res["@type"] = "DataDownload";
				$res["encodingFormat"] = $value['format'];
				$res["contentUrl"] = $protocol . $host . $this->config->client->routing_prefix . "/d4c/api/datasets/1.0/" . $dataset["datasetid"] . "/alternative_exports/" . $value['id'];
				$resources[] = $res;
			}

			//Defining the last data update date
			$currentDataDate = $value['last_modified'];
			if (!isset($currentDataDate)) {
				$currentDataDate = $value['created'];
			}

			//We compare with the others resources
			if (!isset($lastDataUpdateDate) || strtotime($currentDataDate) > strtotime($lastDataUpdateDate)) {
				$lastDataUpdateDate = $currentDataDate;
			}
		}

		if($resourcesid != ""){
			$res = array();
			$res["@type"] = "DataDownload";
			$res["encodingFormat"] = "CSV";
			$res["contentUrl"] = $protocol . $host . $this->config->client->routing_prefix . "/d4c/api/records/2.0/downloadfile/format=csv&use_labels_for_header=true&resource_id=" . $resourcesid;
			$resources[] = $res;
			
			$res = array();
			$res["@type"] = "DataDownload";
			$res["encodingFormat"] = "JSON";
			$res["contentUrl"] = $protocol . $host . $this->config->client->routing_prefix . "/d4c/api/records/2.0/downloadfile/format=json&resource_id=" . $resourcesid;
			$resources[] = $res;
			
			$res = array();
			$res["@type"] = "DataDownload";
			$res["encodingFormat"] = "Excel";
			$res["contentUrl"] = $protocol . $host . $this->config->client->routing_prefix . "/d4c/api/records/2.0/downloadfile/format=xls&use_labels_for_header=true&resource_id=" . $resourcesid;
			$resources[] = $res;
			
			if($isGeo){
				$res = array();
				$res["@type"] = "DataDownload";
				$res["encodingFormat"] = "GeoJSON";
				$res["contentUrl"] = $protocol . $host . $this->config->client->routing_prefix . "/d4c/api/records/2.0/downloadfile/format=geojson&resource_id=" . $resourcesid;
				$resources[] = $res;
				
				$res = array();
				$res["@type"] = "DataDownload";
				$res["encodingFormat"] = "KML";
				$res["contentUrl"] = $protocol . $host . $this->config->client->routing_prefix . "/d4c/api/records/2.0/downloadfile/format=kml&resource_id=" . $resourcesid;
				$resources[] = $res;
				
				$res = array();
				$res["@type"] = "DataDownload";
				$res["encodingFormat"] = "Shapefile";
				$res["contentUrl"] = $protocol . $host . $this->config->client->routing_prefix . "/d4c/api/records/2.0/downloadfile/format=shp&resource_id=" . $resourcesid;
				$resources[] = $res;
			}
		}
		
		//Build interface
		$body = $this->buildBody($config, $api, $host, $dataset, $tab, $id, $name, $description, $url, $dateModified, $licence, $keywords, $resources, $lastDataUpdateDate, $metadataExtras);
		 
		$element = array(
			'example one' => [
				'#type' => 'inline_template',
				'#template' => $body,
						
			],
		);
		$element['#attached']['library'][] = 'ckan_admin/visu.angular';
		return $element;
	}

	function buildBody($config, $api, $host, $dataset, $tab, $id, $name, $description, $url, $dateModified, $licence, $keywords, $resources, $lastDataUpdateDate, $metadataExtras) {
		
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

		$themes = $this->buildTheme($api, $config, $metadataExtras);
		$imgTheme = $themes[0];
		$themes = $themes[1];

		$filters = $this->buildFilters($config);
		$tabs = $this->buildTabs($config, $tab, $dataset, $id, $name, $description, $lastDataUpdateDate, $themes, $metadataExtras);
		$disqus = $this->buildDisqus($config, $host, $dataset);
		$imports = $this->buildImports($config, $id, $name, $description, $url, $dateModified, $licence, $keywords, $resources);

		// <a href="javascript:history.back()"><i class="fa fa-fw fa-twitter"></i></a>
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
							ctx-dataset-schema="' . $ctx . '">

							<d4c-notification-handler></d4c-notification-handler>

							<div class="d4c-dataset-visualization__header">
								<h1 class="d4c-dataset-visualization__dataset-title">
									<d4c-social-buttons></d4c-social-buttons>
									<div class="box_3">
										<div style=" background-image: url('. $imgTheme . '); display: inline-block; width: 40px; height: 40px; background-repeat: no-repeat; background-size: contain; vertical-align: middle; margin-right: 8px;"/>
									</div>
									<span>\{\{ ctx.dataset.metas.title \}\}</span>
								</h1>
							</div>
				
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

	function buildFilters($config) {
		return '
			<div class="d4c-filters-summary" ng-class="{\'d4c-filters-summary--expanded\': toggleState.expandedFilters}">
				<div class="d4c-filters-summary__count">
					<span class="d4c-filters-summary__count-number">\{\{ ctx.nhits | number \}\}</span>
					<span class="d4c-filters-summary__count-units" translate translate-n="ctx.nhits" translate-plural="records">record</span>
				</div>
				<button class="d4c-button d4c-filters-summary__toggle" ng-click="toggleMobileFilters()">
					<i class="fa" aria-hidden="true" ng-class="{\'fa-expand\': !toggleState.expandedFilters, \'fa-compress\': toggleState.expandedFilters}"></i>
					Filtres
				</button>
			</div>
			<div class="d4c-filters" ng-class="{\'d4c-filters--expanded\': toggleState.expandedFilters}" ng-show="canAccessData()">
				<h2 class="d4c-filters__count">
					<span class="d4c-filters__count-number">\{\{ ctx.nhits | number \}\}</span>
					<span class="d4c-filters__count-units" translate translate-n="ctx.nhits" translate-plural="records">record</span>
				</h2>
				<h2 class="d4c-filters__filters-summary" ng-show="ctx.getActiveFilters().length">
					<span translate>Active filters</span>
					<d4c-clear-all-filters context="ctx"></d4c-clear-all-filters>
				</h2>
				<d4c-filter-summary context="ctx" clear-all-button="false"></d4c-filter-summary>
				<div ng-hide="ctx.getActiveFilters().length"
						class="d4c-filters__no-filters">
					Aucun filtre actif.
				</div>

				<h2 class="d4c-filters__filters"><span translate>Filters</span></h2>
				<d4c-text-search context="ctx" placeholder="Rechercher..." autofocus></d4c-text-search>

				<!-- Predefined filters -->
				<h2 ng-if="ctx.dataset.getPredefinedFilters()" class="d4c-filters__filters"><span translate>Predefined Filters</span></h2>
				<ul class="d4c-dataset-export__format-choices" ng-if="ctx.dataset.getPredefinedFilters()">
					<li ng-repeat="(key, value) in ctx.dataset.getPredefinedFilters()" class="d4c-dataset-export__format-choice">
						<a href = "' . $config->client->routing_prefix . '/visualisation/table/?id=\{\{ ctx.dataset.metas.id \}\}&\{\{ value }\}">
							<span>\{\{ key }\}</span>
						</a>
					</li>
				</ul>

				<d4c-facets context="ctx"></d4c-facets>
			</div>';
	}

	function buildTabs($config, $tab, $dataset, $id, $name, $description, $lastDataUpdateDate, $themes, $metadataExtras) {
		$loggedIn = \Drupal::currentUser()->isAuthenticated();

		$tabInformation = $this->buildTabInformation($config, $loggedIn, $dataset, $name, $description, $lastDataUpdateDate, $themes, $metadataExtras);
		$tabTable = $this->buildTabTable();
		$tabMap = $this->buildTabMap();
		$tabAnalyze = $this->buildTabAnalyze();
		$tabImage = $this->buildTabImage();
		$tabCalendar = $this->buildTabCalendar();
		$tabCustomView = $this->buildTabCustomView();
		$tabWordCloud = $this->buildTabWordCloud();
		$tabTimeline = $this->buildTabTimeline();
		$tabExport = $this->buildTabExport();
		$tabAPI = $this->buildTabAPI();

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

				<!-- Enable this to enable dataset subscription -->
				<!-- <d4c-dataset-subscription context="ctx" logged-in="' . $loggedIn . '" dataset-id="' . $id . '" preset="ctx.dataset.is_subscribed"></d4c-dataset-subscription> -->
			</d4c-tabs>
		';
	}

	function buildTabInformation($config, $loggedIn, $dataset, $name, $description, $lastDataUpdateDate, $themes, $metadataExtras) {
		
		// $sources = $this->buildSources($metadataExtras);
		// $ftpApi = $sources[0];
		// $source = $sources[1];

		//IMAGE
		$image = $this->buildImage($metadataExtras);

		//LIMITES ET CONDITIONS D'UTILISATION
		$limitesEtConditionsUtilisation = $this->buildLimitesEtConditionsUtilisation($metadataExtras);

		//MÉTHODE DE PRODUCTION ET QUALITÉ
		$methodeProductionEtQualite = $this->buildMethodeProductionEtQualite($metadataExtras);

		//INFORMATIONS GÉOGRAPHIQUES
		$informationsGeo = $this->buildInformationsGeo($metadataExtras);

		//SYNTHÈSE
		$synthese = $this->buildSynthese($metadataExtras, $themes);

		//CONTACTS
		$contacts = $this->buildContacts($metadataExtras);

		//Linked datasets
		$linkedDataSets = $this->buildLinkedDatasets($config, $metadataExtras);
		
		$visWidget = $this->buildWidget($metadataExtras);

		$downloadsAndLinks = null;
		if ($config->client->ressources_download_links) {
			$downloadsAndLinks = $this->manageAdditionnalResources($dataset);
		}

		return '
			<d4c-pane pane-auto-unload="true" title="Information" icon="info-circle" translate="title" slug="information">
				<div class="row">
					<div class="col-sm-9">
						' . $this->buildCard('Description', $description) . '
						' . $this->buildCard('Limites et conditions d\'utilisation', $limitesEtConditionsUtilisation) . '
						' . $this->buildCard('Méthode de production et qualité', $methodeProductionEtQualite) . '
						' . $this->buildCard('Informations géographiques', $informationsGeo) . '
						' . ($downloadsAndLinks != null ? $this->buildCard('Téléchargements et liens', $downloadsAndLinks) : '') . '
					</div>
					<div class="col-sm-3">
						' . $this->buildCardImage($image) . '
						' . $this->buildCard('Synthèse', $synthese) . '
						' . $this->buildCard('Contacts', $contacts) . '
					</div>
				</div>

				<div class="row">
					<div class="col-sm-12" ng-if="basicTemplate && interopTemplates">
						' . $linkedDataSets . '
						<d4c-dataset-metadata-block-selector metadata-templates="interopTemplates" values="ctx.dataset.interop_metas"></d4c-dataset-metadata-block-selector>
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

				<d4c-dataset-reuses readonly="false"
					max="3"
					anonymous-reuse="true"
					logged-in="'.$loggedIn.'" recaptcha-pub-key="6LcT58UaAAAAAD_bIB7iAAeSJ6WggtNaFS74GbGk" dataset-title="'.$name.'"
					config="{&#39;is_unique&#39;: True, &#39;max_width&#39;: 4096, &#39;max_height&#39;: 4096, &#39;resize_width&#39;: 200, &#39;resize_height&#39;: 200, &#39;asset_type&#39;: &#39;image&#39;, &#39;max_size&#39;: 2097152}"></d4c-dataset-reuses>
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
	function exportExtras($metadata, $metadataName) {
		return current(array_filter($metadata, function($elem) use($metadataName){
			return $elem['key'] == $metadataName;
		}))["value"];
	}

	function buildLinkedDatasets($config, $metadataExtras) {
		$links = $this->exportExtras($metadataExtras, 'LinkedDataSet');
		$links = explode(";", $links);

		$linkedDatasets = null;
		for ($j=0; $j<count($links); $j++) {
			$link = explode(":", $links[$j]);
			
			if ($link[0] != 'false') {
				$url = $config->client->routing_prefix . '/visualisation?id='. $link[1];
				$linkedDatasets = $linkedDatasets . '&nbsp<p style="margin: -1.1em 0 -1em;" ><code style="cursor: pointer;" onclick="window.open(`'.$url.'`, `_blank`);">' . $link[0] . '</code></p><br>';
			}
		}

		if ($linkedDatasets != null) {
			return '
				<div class="d4c-dataset-metadata-block__metadata" style="font-size: 1rem; ">
					<div class="d4c-dataset-metadata-block__metadata-name" translate=""><span class="ng-scope">Dataset liés</span></div>
					<div class="d4c-dataset-metadata-block__metadata-value">' . $linkedDatasets . '</div>
				</div>
			';
		}
		else {
			return '';
		}
	}

	function buildTheme($api, $config, $metadataExtras) {
		//Getting themes to get theme's information
		$listOfThemes = $api->getPackageTheme();
    	$listOfThemes = json_decode($listOfThemes->getContent(), true);

		$labelTheme = $this->exportExtras($metadataExtras, 'label_theme');
		$theme = $this->exportExtras($metadataExtras, 'theme');

		$themesPart = '';
		if ($labelTheme != null) {
			$themeName = $labelTheme;
			$themesPart = '
				<div class="d4c-dataset-metadata-block__metadata ng-scope" style="font-size: 1rem; margin: -0.8em  0 -1em 0;">
					<div class="d4c-dataset-metadata-block__metadata-name ng-binding" >Thème</div>
					<div class="d4c-dataset-metadata-block__metadata-value d4c-dataset-metadata-block__metadata-value--default ng-binding ng-scope">' . $labelTheme . '</div>
				</div>
			'; 
		}
		else if ($theme != null) {
			$themeName = $theme;
			$themesPart = '
				<div class="d4c-dataset-metadata-block__metadata ng-scope" style="font-size: 1rem; margin: -0.8em  0 -1em 0;">
					<div class="d4c-dataset-metadata-block__metadata-name ng-binding" >Thème</div>
					<div class="d4c-dataset-metadata-block__metadata-value d4c-dataset-metadata-block__metadata-value--default ng-binding ng-scope">' . $theme . '</div>
				</div>
			'; 
		}
		else {
			$themeName = 'Default';
			$themesPart = '
				<div class="d4c-dataset-metadata-block__metadata ng-scope" style="font-size: 1rem; margin: -0.8em  0 -1em 0;">
					<div class="d4c-dataset-metadata-block__metadata-name ng-binding" >Thème</div>
					<div class="d4c-dataset-metadata-block__metadata-value d4c-dataset-metadata-block__metadata-value--default ng-binding ng-scope">Default</div>
				</div>
			'; 
		}

		$imgTheme = null;
		foreach ($listOfThemes as $value) {
			if ($value['label'] == $themeName) {
				$imgTheme = $value['url'];
				break;
			}
		}

		return [
			$imgTheme,
			$themesPart
		];
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
		return $customView != null ? json_encode($customView) : '';
	}

	function buildImage($metadataExtras) {
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

	function buildLimitesEtConditionsUtilisation($metadataExtras) {
		$licence = $this->exportExtras($metadataExtras, 'licence');
		$accessConstraints = $this->exportExtras($metadataExtras, 'access_constraints');
		$useConstraints = $this->exportExtras($metadataExtras, 'use-constraints');
		$mentionLegales = $this->exportExtras($metadataExtras, 'mention_legales');

		if ($licence != null) {
			$licenceJson = json_decode($licence, true);
			if (json_last_error() === JSON_ERROR_NONE) {
				$licence = $licenceJson;
				foreach ($licence as $value) {	
					$licence = '<li>' . html_entity_decode($value) . '</li>';
				}
			}
			else {
				$licence = '<li>' . $licence . '</li>';
			}
		}

		if ($accessConstraints != null) {
			$accessConstraints = json_decode($accessConstraints, true);

			foreach ($accessConstraints as $value) {	
				$accessConstraints = '<li>' . html_entity_decode($value) . '</li>';
			}
		}

		if ($useConstraints != null) {
			$useConstraints = $this->cleanSimpleJson($useConstraints);
			$useConstraints = '<li>' . $useConstraints . '</li>';
		}

		if ($mentionLegales != null) {
			$mentionLegales = '<li>' . $mentionLegales . '</li>';
		}

		return '
			<ul class="m-0">
				' . $licence . '
				' . $accessConstraints . '
				' . $useConstraints . '
				' . $mentionLegales . '
			</ul>
		';
	}

	function buildSynthese($metadataExtras, $themes) {
		$frequence = $this->exportExtras($metadataExtras, 'frequence');
	// 	return '
	// 	<div class="d4c-dataset-metadata-block__metadata ng-scope" style="font-size: 1rem; margin: -0.8em  0 -1em 0;">
	// 		<div class="d4c-dataset-metadata-block__metadata-name ng-binding" >Fréquence de mise à jour</div>
	// 		<div class="d4c-dataset-metadata-block__metadata-value d4c-dataset-metadata-block__metadata-value--default ng-binding ng-scope">  ' . $frequence . '</div>
	// 	</div>
	// ';

		// frequency-of-update	unknown
		
		$nbDownloads = '
			<div class="d4c-dataset-metadata-block" ng-show="(ctx.dataset.metas.extras | filter:{key:\'nb_download\'})[0].value > 0">
				<div class="d4c-dataset-metadata-block__metadata">
					<div class="d4c-dataset-metadata-block__metadata-name" translate=""><span class="ng-scope">Téléchargements</span></div>
					<div class="d4c-dataset-metadata-block__metadata-value ng-binding">\{\{ +((ctx.dataset.metas.extras | filter:\{key:\'nb_download\'\})[0].value) \}\}</div>
				</div>
			</div>
		';

		$lastDataUpdateDate = '
			<div class="d4c-dataset-metadata-block" ng-show="\'' . $lastDataUpdateDate . '\'">
				<div class="d4c-dataset-metadata-block__metadata">
					<div class="d4c-dataset-metadata-block__metadata-name" translate>Last data update</div>
					<div class="d4c-dataset-metadata-block__metadata-value ng-binding">\{\{\'' . $lastDataUpdateDate . '\' | formatMeta:\'datetime\' \}\}</div>
				</div>
			</div>
		';

		return '
			<div ng-show="(ctx.dataset.metas.extras | filter:{key:\'date_dataset\'})[0].value">
				<div translate>Dataset date</div>
				<div ng-binding">\{\{ (ctx.dataset.metas.extras | filter:\{key:\'date_dataset\'\})[0].value | formatMeta:\'date\' \}\}</div>
			</div>
		';
	}
	
	function buildMethodeProductionEtQualite($metadataExtras) {
		$lineage = $this->exportExtras($metadataExtras, 'lineage');
		return $lineage;
	}

	function buildInformationsGeo($metadataExtras) {
		$representationType = $this->exportExtras($metadataExtras, 'spatial-representation-type');

		$bboxEastLong = $this->exportExtras($metadataExtras, 'bbox-east-long');
		$bboxNorthLat = $this->exportExtras($metadataExtras, 'bbox-north-lat');
		$bboxSouthLat = $this->exportExtras($metadataExtras, 'bbox-south-lat');
		$bboxWestLong = $this->exportExtras($metadataExtras, 'bbox-west-long');
		
		$equivalentScale = $this->exportExtras($metadataExtras, 'equivalent-scale');
		if ($equivalentScale != null) {
			$equivalentScale = $this->cleanSimpleJson($equivalentScale);
		}
		
		$referenceSystem = $this->exportExtras($metadataExtras, 'spatial-reference-system');
		$resolution = $this->exportExtras($metadataExtras, 'spatial-resolution-units');

		// spatial-reference-system	2154
		return '
			<div class="row">
				<div class="col-sm-7">
					<p><strong>Type de représentation:</strong> ' . ($representationType != null ? $representationType : 'non renseignée') . '</p>
					<p><strong>Etendue géographique:</strong></p>
					<ul>
						<li>Ouest: ' . $bboxWestLong . '</li>
						<li>Est: ' . $bboxEastLong . '</li>
						<li>Sud: ' . $bboxSouthLat . '</li>
						<li>Nord: ' . $bboxNorthLat . '</li>
					</ul>
				</div>
				<div class="col-sm-3">
					<p><strong>Système de projection:</strong> ' . ($referenceSystem != null ? $referenceSystem : 'non renseignée') . '</p>
					<p><strong>Echelle:</strong> ' . ($equivalentScale != null ? '1/' . $equivalentScale : 'non renseignée') . '</p>
					<p><strong>Résolution:</strong> ' . ($resolution != null ? $resolution : 'non renseignée') . '</p>
				</div>
			</div>
		';
	}

	function cleanSimpleJson($value) {
		$value = str_replace('{', '', $value);
		$value = str_replace('}', '', $value);
		$value = str_replace('"', '', $value);
		return $value;
	}
	
	function buildContacts($metadataExtras) {
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

	function manageAdditionnalResources($dataset) {
		if (sizeof($dataset["metas"]["resources"]) > 0 ) {
			foreach($dataset["metas"]["resources"] as $key=>$value){
				$name = $value["name"];
				$url = $value["url"];
				$format = $value["format"];

				$classImg = strpos($value["resource_locator_protocol"], 'download') !== false or strpos($value["resource_locator_protocol"], 'DOWNLOAD') !== false ? 'fa-download' : 'fa-link';

				$button = '';
				if ($format == "WMS" || strpos($url, "wms") !== false) {
					$button = '
						<button class="btn btn-info" ng-click="openMapfishapp(\'' . $name . '\', \'' . $url . '\', \'wms\')">
							Ouvrir dans Mapfishapp
						</button>
					';
				}
				else {
					$buttonText = strpos($value["resource_locator_protocol"], 'download') !== false || strpos($value["resource_locator_protocol"], 'DOWNLOAD') !== false ? 'Télécharger' : 'Consulter';

					$button .= '
						<div class="col-sm-3">
							<a class="btn btn-info" role="button" target="_blank" href="' . $url . '" >' . $buttonText . '</a>
						</div>
					';
				}

				return '
					<div class="row">
						<div class="col-sm-9">
							<i style="margin-right: 12px; font-size: 20px" class="fa ' . $classImage . '" fa-4x></i>' . $name . '<br>
							<a target="_blank" href="' . $url . '">' . $url . '</a>
						</div>
						<div class="col-sm-3">
							' . $button . '
						</div>
					</div>
				';
			}
		}

		return null;
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

	function buildTabExport() {
		return '
			<d4c-pane pane-auto-unload="true" title="Export" icon="download" translate="title" slug="export">
				<d4c-dataset-export context="ctx" shapefile-export-limit="50000" snapshots="false"></d4c-dataset-export>
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
	
	function buildDisqus($config, $host, $dataset) {
		if ($config->client->disqus) {
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

	function buildImports($config, $id, $name, $description, $url, $dateModified, $licence, $keywords, $resources) {
		return '
			<script src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/jquery-3.2.1.js"></script>
			<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/bootstrap.min.js"></script>
			<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/libraries.js"></script>
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
						ID_DATASET: "'.$id.'",
						HOST: "'.$config->client->domain.'"
					}
				}]);
			</script>
		
			<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/i18n.js"></script>
			<script src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/supported-browsers-message.js" type="text/javascript"></script>
			<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/angular-visu.js"></script>
			<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/popularDataset.js"></script>
	
			<script>
				//$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/visualisation.css\" rel=\"stylesheet\">");
				$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/normalize.css\" rel=\"stylesheet\">");
				//$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/d4cui.css\" rel=\"stylesheet\">");
				//$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/bootstrap.min.css\" rel=\"stylesheet\">");
				$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/'.$config->client->css_file.'\" rel=\"stylesheet\">");
				$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/font-awesome.min.css\" rel=\"stylesheet\">");
				$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/style.css\" rel=\"stylesheet\">");
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

	// function manageXMLFile($dataset, $id) {
	// 	/**
	//  	* This is disabled for now
	//  	* It is a developpement made for GE
	//  	* 
	//  	*/
	// 	$MapDetail = ""; 
	// 	$featureCatalog = ""; 
	// 	$DateDetail="";
	// 	$dateUpdated ="";
	// 	$shareSocialMedia="";
	// 	$associatedResources ="";
		
	// 	foreach($dataset["metas"]["resources"] as $key=>$value){
	// 		// Logger::logMessage("TRM - Found resources " . $value["name"] . " and format = " . $value["format"] . " and test = " . (strpos($value["name"], "Vue XML des métadonnées") !== false));
	// 		if($value["format"] == "csw" || strpos($value["name"], "Vue XML des métadonnées") !== false) {
	// 			// Logger::logMessage("TRM - Found XML " . $value["name"]);

	// 			$xml = file_get_contents($value['url']); 

	// 			if (!file_exists($_SERVER['DOCUMENT_ROOT']."/". $id)) {
	// 				mkdir($_SERVER['DOCUMENT_ROOT']."/". $id, 0777, true);
	// 			}
	// 			file_put_contents($_SERVER['DOCUMENT_ROOT']."/". $id."/metadata_xml_view.xml", $xml);
				
	// 			break;
	// 		}
	// 	}

	// 	if (file_exists($_SERVER['DOCUMENT_ROOT']."/". $id."/metadata_xml_view.xml")) {
 
	// 		$str=implode("\n",file($_SERVER['DOCUMENT_ROOT']."/". $id."/metadata_xml_view.xml"));

	// 		$fp=fopen($_SERVER['DOCUMENT_ROOT']."/".$id."/metadata_xml_view.xml",'w');
	// 		$str=str_replace('&','??',$str);
	// 		$str=str_replace(':','',$str);
	// 		fwrite($fp,$str,strlen($str));

	// 		$xml = simplexml_load_file($id."/metadata_xml_view.xml");

	// 		foreach ($xml as $key => $value) {
	// 			$MapDetail='<section class="gn-md-side-extent ng-scope" > 
	// 					<h2 style="font-size: 16px;"> <i class="fa fa-fw fa-map-marker"></i> 
	// 					<span data-translate="" class="ng-scope" >Extension spatiale</span>
	// 				</h2> ';
						
	// 			$detailDescription = $value->gmdidentificationInfo->gmdMD_DataIdentification->gmdextent->gmdEX_Extent->gmddescription->gcoCharacterString->__toString();
	// 			$detailWestLongitude = $value->gmdidentificationInfo->gmdMD_DataIdentification->gmdextent->gmdEX_Extent->gmdgeographicElement[1]->gmdEX_GeographicBoundingBox->gmdwestBoundLongitude->gcoDecimal->__toString();
	// 			$detailSouthLatitude = $value->gmdidentificationInfo->gmdMD_DataIdentification->gmdextent->gmdEX_Extent->gmdgeographicElement[1]->gmdEX_GeographicBoundingBox->gmdsouthBoundLatitude->gcoDecimal->__toString();
	// 			$detailEastLongitude = $value->gmdidentificationInfo->gmdMD_DataIdentification->gmdextent->gmdEX_Extent->gmdgeographicElement[1]->gmdEX_GeographicBoundingBox->gmdeastBoundLongitude->gcoDecimal->__toString();
	// 			$detailNorthLatitude = $value->gmdidentificationInfo->gmdMD_DataIdentification->gmdextent->gmdEX_Extent->gmdgeographicElement[1]->gmdEX_GeographicBoundingBox->gmdnorthBoundLatitude->gcoDecimal->__toString();

	// 			$MapDetail .= '<ul> <li >' . $detailDescription . '</li></ul> ';
	// 			$MapDetail .= '<img class="gn-img-thumbnail img-thumbnail gn-img-extent" alt="Spatial extent" aria-label="Spatial extent" data-ng-src="https://www.geograndest.fr/geonetwork/srv/eng/region.getmap.png?mapsrs=EPSG:3857&width=250&background=settings&geomsrs=EPSG:4326&geom=Polygon((' . $detailWestLongitude . '%20' . $detailSouthLatitude . ',' . $detailEastLongitude . '%20' . $detailSouthLatitude . ',' . $detailEastLongitude . '%20' . $detailNorthLatitude . ',8.23029041290283203125%2050.16764068603515625,8.23029041290283203125%2047.42026519775390625))" src="https://www.geograndest.fr/geonetwork/srv/eng/region.getmap.png?mapsrs=EPSG:3857&width=250&background=settings&geomsrs=EPSG:4326&geom=Polygon((8.23029041290283203125%2047.42026519775390625,3.3840906620025634765625%2047.42026519775390625,3.3840906620025634765625%2050.16764068603515625,8.23029041290283203125%2050.16764068603515625,8.23029041290283203125%2047.42026519775390625))">';
	// 			$MapDetail .= "</section>";

	// 			$DateDetail = '<section class="gn-md-side-dates ng-scope" > <h2> <i class="fa fa-fw fa-clock-o" style="font-size: 16px;"></i> <span data-translate="" class="ng-scope" style="font-size: 16px;">Étendue temporelle</span> </h2> <p> </p>';	
	// 			foreach ($value->gmdidentificationInfo->gmdMD_DataIdentification->gmdcitation->gmdCI_Citation->gmddate as  $valuedate) {
					
	// 				if($valuedate->gmdCI_Date->gmddateType->gmdCI_DateTypeCode->__toString() == "publication") {
	// 					$DateDetail .= '<dl > <dt data-translate="" class="ng-scope">La date de publication</dt>';
	// 				}

	// 				if($valuedate->gmdCI_Date->gmddateType->gmdCI_DateTypeCode->__toString() == "revision") {
	// 					$DateDetail .= '<dl > <dt data-translate="" class="ng-scope">La date de révision</dt>';
	// 				}
	// 				$DateDetail .= '<dd data-gn-humanize-time="'.$valuedate->gmdCI_Date->gmddate->gcoDate->__toString().'" data-format="YYYY-MM-DD" class="ng-scope ng-isolate-scope"><span title="3 months ago" class="ng-binding">'.$valuedate->gmdCI_Date->gmddate->gcoDate->__toString().'</span></dd> </dl>';
	// 			}
	// 			$DateDetail .= '</section>';
				
	// 			$datestamps = explode("T", $value->gmddateStamp->gcoDateTime->__toString());
	// 			$now = time(); 
	// 			$your_date = strtotime($datestamps[0]);
	// 			$datediff = $now - $your_date - 1;
	// 			$days = round($datediff / (60 * 60 * 24)) - 1;

	// 			$dateUpdated = '<section class="gn-md-side-calendar"> <h2 style="font-size:16px"> <i class="fa fa-fw fa-calendar"></i><span data-translate="" class="ng-scope">Modifié: </span> </h2>';
	// 			$dateUpdated .= '<p><span data-gn-humanize-time="'.$value->gmddateStamp->gcoDateTime->__toString().'" data-from-now="" class="ng-isolate-scope"><span title="'.$value->gmddateStamp->gcoDateTime->__toString().'" class="ng-binding"> Il y a '.$days.' jour(s)</span></span> </p>';
	// 			$dateUpdated .= '</section>';

	// 			$shareSocialMedia ='<section class="gn-md-side-social" style="margin-top: 20px" > <h2 style="font-size: 16px"> <i class="fa fa-fw fa-share-square-o"></i> <span data-translate="" class="ng-scope">Partager</span> </h2> 
	// 				<a data-ng-href="#" title="Share on Twitter" target="_blank" class="btn btn-default" href="#"><i class="fa fa-fw fa-twitter"></i></a>
	// 				<a data-ng-href="#" title="Share on Facebook" target="_blank" class="btn btn-default" href="#"><i class="fa fa-fw fa-facebook"></i></a> <a data-ng-href="#" title="Share on LinkedIn" target="_blank" class="btn btn-default" href="#"><i class="fa fa-fw fa-linkedin"></i></a> <a data-ng-href="#" title="Share by email" target="_blank" class="btn btn-default" href="#"><i class="fa fa-fw fa-envelope-o"></i></a> <a data-ng-click="mdService.getPermalink(md)" title="Permalink" class="btn btn-default"><i class="fa fa-fw fa-link"></i></a> </section>';


	// 			$featureCatalog ='<div><h2 style="font-size: 16px" class="ng-binding">À propos de cette ressource</h2></div>';
	// 			$associatedResources =" <div>";
	// 			$associatedResources .= '<table class="table table-striped"><tbody> ';
	// 			foreach ($value->gmdidentificationInfo->gmdMD_DataIdentification->gmddescriptiveKeywords as $key2 => $value2) {
	// 				if($value2->gmdMD_Keywords->gmdthesaurusName) {
	// 					$associatedResources .= '<tr > <th data-translate="" class="ng-scope">INSPIRE themes</th><td> <button data-ng-click="search({\'inspirethemewithac\': '.$value2->gmdMD_Keywords->gmdkeyword->gcoCharacterString->__toString().'})" class="btn btn-sm btn-default ps ps-en" title="Click to filter on  Sites protégés"> <i class="fa fa-download" style="color: #95c11f"></i> </button> </td></tr>';
	// 				}
	// 			}
	// 			if($value->gmdidentificationInfo->gmdMD_DataIdentification->gmdtopicCategory->__toString() ) {
	// 				$associatedResources .= '<tr> <th data-translate="" class="ng-scope">Categories</th> <td><button data-ng-click="search({\'topicCat\': cat})" class="btn btn-sm btn-default ng-binding ng-scope" title="Click to filter on  Environment"> <span class="fa gn-icon-environment topic-color"></span>&nbsp; Environment </button> </td> </tr>';
	// 			}

	// 			foreach ($value->gmdidentificationInfo->gmdMD_DataIdentification->gmddescriptiveKeywords as $key2 => $value2) {
	// 				if($value2->gmdMD_Keywords->gmdthesaurusName) {
	// 					$associatedResources .= '<tr > <th data-translate="" class="ng-scope">'.$value2->gmdMD_Keywords->gmdthesaurusName->gmdCI_Citation->gmdtitle->gcoCharacterString->__toString().'</th><td><ul> 
	// 							<li > <span class="ng-binding">'.$value2->gmdMD_Keywords->gmdkeyword->gcoCharacterString->__toString().'</span> <a  href="" title="Click to filter on  Sites protégés" aria-label="Click to filter on  Sites protégés" data-ng-click="search({\'keyword\': '.$value2->gmdMD_Keywords->gmdkeyword->gcoCharacterString->__toString().'})"> <i class="fa fa-search"></i> </a> </li></ul></td>
	// 						</tr>';
	// 				}
	// 			}

	// 			$associatedResources .= '<tr > <th data-translate="" class="ng-scope">Autres mots-clés</th><td>';
	// 			foreach ($value->gmdidentificationInfo->gmdMD_DataIdentification->gmddescriptiveKeywords as $key2 => $value2) {
	// 				if(!$value2->gmdMD_Keywords->gmdthesaurusName) {
	// 					$associatedResources .= '<ul style="list-style-type: disc;"> 
	// 						<li > <span class="ng-binding">'.$value2->gmdMD_Keywords->gmdkeyword->gcoCharacterString->__toString().'</span> <a  href="" title="Click to filter on  Sites protégés" aria-label="Click to filter on  Sites protégés" data-ng-click="search({\'keyword\': '.$value2->gmdMD_Keywords->gmdkeyword->gcoCharacterString->__toString().'})"> <i class="fa fa-search"></i> </a> </li></ul>';
	// 				}
	// 			}
	// 			$associatedResources .= '</td></tr>';
	// 			$associatedResources .='<tr > <th data-translate="" class="ng-scope">Langue</th><td><ul> 
	// 					<li > <span class="ng-binding">'.$value->gmdlanguage->gmdLanguageCode->__toString().'</span> </li></ul></td>
	// 				</tr>';

	// 			$associatedResources .='<tr > <th data-translate="" class="ng-scope">Identificateur de ressource</th><td><ul> 
	// 					<li > <span class="ng-binding">'.$value->gmdidentificationInfo->gmdMD_DataIdentification->gmdcitation->gmdCI_Citation->gmdidentifier->gmdRS_Identifier->gmdcode->gcoCharacterString->__toString().'</span> </li></ul></td>
	// 				</tr>';

	// 			$associatedResources .= '<tr > <th data-translate="" class="ng-scope">Contraintes légales</th><td>'; 
	// 			foreach ($value->gmdidentificationInfo->gmdMD_DataIdentification->gmdresourceConstraints as $key2 => $value2) {
	// 				if($value2->gmdMD_LegalConstraints->gmdotherConstraints != null){
	// 					$associatedResources .= '<p>'.$value2->gmdMD_LegalConstraints->gmdotherConstraints->gcoCharacterString->__toString().'<p>';
	// 				}

	// 				foreach ($value2->gmdMD_LegalConstraints->gmduseLimitation as $value3) {
	// 					$associatedResources .= '<p>'.$value3->gcoCharacterString->__toString().'<p>';
	// 				}
	// 			}
	// 			$associatedResources .= '</td></tr>';
				
	// 			$associatedResources .= '<tr > <th data-translate="" class="ng-scope">Contact pour la ressource</th><td><adresse> 
	// 					<strong><i class="fa fa-envelope" style="margin-right: 10px"></i> '.$value->gmdcontact->gmdCI_ResponsibleParty->gmdorganisationName->gcoCharacterString->__toString().'</strong> </adresse>
	// 					<p>'.$value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmddeliveryPoint->gcoCharacterString->__toString().', '.$value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmdcity->gcoCharacterString->__toString().', '.$value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmdpostalCode->gcoCharacterString->__toString().', '.$value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmdcountry->gcoCharacterString->__toString().'</p> <ul style="list-style-type: disc;"><li> <strong>Point de contact: </strong><a href="mailto:'.$value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmdelectronicMailAddress->gcoCharacterString->__toString().'"> '.$value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmdelectronicMailAddress->gcoCharacterString->__toString().'</a></li></ul>
	// 					</td>
	// 				</tr>';
				
	// 			$associatedResources .= '<tr > <th data-translate="" class="ng-scope">Statut</th><td>
	// 					<ul style="list-style-type: disc;"><li>  '.$value->gmdidentificationInfo->gmdMD_DataIdentification->gmdstatus->gmdMD_ProgressCode->__toString().'</a></li></ul>
	// 					</td>
	// 				</tr>';

	// 			$associatedResources .= '</tbody> </table>';
	// 			$associatedResources .= '<h4>Informations techniques</h4>';
	// 			$associatedResources .= '<table class="table table-striped"><tbody> ';

	// 			$associatedResources .= '<tr > <th data-translate="" class="ng-scope">Score</th><td>
	// 					<ul style="list-style-type: disc;"><li>  '.$value->gmdidentificationInfo->gmdMD_DataIdentification->gmdspatialResolution->gmdMD_Resolution->gmdequivalentScale->gmdMD_RepresentativeFraction->gmddenominator->gcoInteger->__toString().'</a></li></ul>
	// 					</td>
	// 				</tr>';

	// 			$associatedResources .= '<tr > <th data-translate="" class="ng-scope">Format</th><td>
	// 					<ul style="list-style-type: disc;"><li>  '.$value->gmddistributionInfo->gmdMD_Distribution->gmddistributionFormat->gmdMD_Format->gmdname->gcoCharacterString->__toString().'</a></li></ul>
	// 					</td>
	// 				</tr>';

	// 			$associatedResources .= '<tr > <th data-translate="" class="ng-scope">Lignée</th><td>
	// 					<ul style="list-style-type: disc;"><li>  '.$value->gmddataQualityInfo->gmdDQ_DataQuality->gmdlineage->gmdLI_Lineage->gmdstatement->gcoCharacterString->__toString().'</a></li></ul>
	// 					</td>
	// 				</tr>';

	// 			$associatedResources .= '</tbody> </table>';
	// 			$associatedResources .= '<h4>Metadata information</h4>';
	// 			$associatedResources .= '<table class="table table-striped"><tbody> ';

	// 			$associatedResources .= '<tr > <th data-translate="" class="ng-scope"><a class="btn btn-default gn-margin-bottom" href="../api/records/fr-120066022-jdd-d90ac948-9e07-47a6-9c1b-471888dbefd4/formatters/xml"> <i class="fa fa-fw fa-file-code-o"></i> <span data-translate="" class="ng-scope">Download metadata</span> </a></th>
	// 				</tr>';

	// 			$associatedResources .= '<tr > <th data-translate="" class="ng-scope"><strong> Contact </strong> </th><td><adresse> 
	// 					<strong><i class="fa fa-envelope" style="margin-right: 10px"></i> '.$value->gmdcontact->gmdCI_ResponsibleParty->gmdorganisationName->gcoCharacterString->__toString().'</strong> </adresse>
	// 					<p>'.$value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmddeliveryPoint->gcoCharacterString->__toString().', '.$value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmdcity->gcoCharacterString->__toString().', '.$value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmdpostalCode->gcoCharacterString->__toString().', '.$value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmdcountry->gcoCharacterString->__toString().'</p> <ul style="list-style-type: disc;"><li> <strong>Point de contact: </strong><a href="mailto:'.$value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmdelectronicMailAddress->gcoCharacterString->__toString().'"> '.$value->gmdcontact->gmdCI_ResponsibleParty->gmdcontactInfo->gmdCI_Contact->gmdaddress->gmdCI_Address->gmdelectronicMailAddress->gcoCharacterString->__toString().'</a></li></ul>
	// 					</td>
	// 				</tr>';
	// 			$associatedResources .= '</tbody> </table>';
	// 			$associatedResources .= " </div>";
	// 		}
	// 	}

	// 	//For now we only return this but the methode needs to be refactor to use every infos that we need from CSW or XML

	// 	$xmlInformations = '';
	// 	$xmlInformations .= $featureCatalog;
	// 	$xmlInformations .= $associatedResources;
	// 	return $xmlInformations;
	// }
}

