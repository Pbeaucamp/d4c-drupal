<?php

use Drupal\ckan_admin\Utils\Logger;

$config = json_decode(file_get_contents(__DIR__ ."/config.json"));

$ckanUrl = getenv('CKAN_URL') ?: $config->ckan->url;
$ckanApiKey = getenv('CKAN_API_KEY') ?: $config->ckan->api_key;
$datapusherKey = getenv('DATAPUSHER_KEY') ?: $config->ckan->datapusher_key;
$datapusherUrl = getenv('DATAPUSHER_URL') ?: $config->ckan->datapusher_url;
$dbCkanName = getenv('CKAN_DATABASE_NAME') ?: $config->ckan->db_ckan_name;
$dbDatastoreName = getenv('CKAN_DATASTORE_NAME') ?: $config->ckan->db_datastore_name;
$dbUser = getenv('CKAN_DATABASE_USERNAME') ?: $config->ckan->db_user;
$dbPass = getenv('CKAN_DATABASE_PASSWORD') ?: $config->ckan->db_pass;
$dbHost = getenv('CKAN_DATABASE_HOST') ?: $config->ckan->db_host;
$dbPort = getenv('CKAN_DATABASE_PORT') ?: $config->ckan->db_port;

$clusterUrl = getenv('CLUSTER_URL') ?: $config->cluster->url;

$clientCacheTime = getenv('CLIENT_CACHE_TIME') ?: $config->client->cache_time;
$clientName = getenv('CLIENT_NAME') ?: $config->client->name;
$clientDomain = getenv('CLIENT_DOMAIN') ?: $config->client->domain;
$clientCssFile = getenv('CLIENT_CSS') ?: $config->client->css_file;
$clientDefaultBoundingBox = getenv('CLIENT_DEFAULT_BOUNDING_BOX') ?: $config->client->default_bounding_box;
$clientMapBoundingBox = getenv('CLIENT_MAP_BOUNDING_BOX') ?: $config->client->map_bounding_box;
$clientRoutingPrefix = getenv('CLIENT_ROUTING_PREFIX') ?: $config->client->routing_prefix;
$clientDisqus = getenv('CLIENT_DISQUS') ?: $config->client->disqus;
$clientRessourcesDownloadLinks = getenv('CLIENT_RESSOURCES_DOWNLOAD_LINKS') ?: $config->client->ressources_download_links;
$clientShapefileProjection = getenv('CLIENT_SHAPEFILE_PROJECTION') ?: $config->client->shapefile_projection;
$clientProxyUrl = getenv('CLIENT_PROXY_URL') ?: $config->client->proxy_url;
$clientNutch = getenv('CLIENT_NUTCH') ?: $config->client->nutch;
$clientNutchUrl = getenv('CLIENT_NUTCH_URL') ?: $config->client->nutch_url;
$clientCheckRgpd = getenv('CLIENT_CHECK_RGPD') ?: $config->client->check_rgpd;
$clientProtocol = getenv('CLIENT_PROTOCOL') ?: $config->client->protocol;
$clientHost = getenv('CLIENT_HOST') ?: $config->client->host;
$clientPort = getenv('CLIENT_PORT') ?: $config->client->port;
$clientIsObservatory = getenv('CLIENT_IS_OBSERVATORY') ?: $config->client->is_observatory;
$clientOrganisation = getenv('CLIENT_ORGANISATION') ?: $config->client->organisation;
$clientRoot = getenv('DRUPAL_ROOT') ?: $config->client->root;

$graviteeUrl = getenv('GRAVITEE_URL') ?: $config->gravitee->url;
$graviteeHeaderKey = getenv('GRAVITEE_HEADER_KEY') ?: $config->gravitee->header_key;
$graviteeApiKey = getenv('GRAVITEE_API_KEY') ?: $config->gravitee->api_key;

$cswEnabled = getenv('CSW_ENABLED') ?: $config->csw->enabled;
$cswServerPath = getenv('CSW_SERVER_PATH') ?: $config->csw->server_path;
$cswModel = getenv('CSW_MODEL') ?: $config->csw->model;

// TODO
// Missing csw_server
// "csw_server": {
// 	"csw_path": "/home/user-client/csw-server/nodes"
// }
// 
// "csw": {
// 	"enabled": true,
// 	"csw_server_path": "\/home\/user-client\/csw-server",
// 	"csw_model": "csw_server_model"
// }

// Missing map_tiles
// "map_tiles": [
// 	{
// 		"name": "relief",
// 		"label": "OpenStreetMap GeoGrandEst",
// 		"provider": "custom",
// 		"url": "https:\/\/osm.datagrandest.fr\/mapcache\/wmts\/1.0.0\/relief\/default\/webmercator\/{z}\/{y}\/{x}.png",
// 		"minZoom": 1,
// 		"maxZoom": 19,
// 		"type": "tile",
// 		"attribution": "Service OSM de GeoGrandEst",
// 		"layers": ""
// 	},
// 	{
// 		"name": "GGE_ORTHO_RVB_ACTUELLE",
// 		"label": "Orthophoto du Grand Est",
// 		"provider": "custom_wms",
// 		"url": "https:\/\/www.datagrandest.fr\/geoserver\/geograndest\/wms?&",
// 		"minZoom": 1,
// 		"maxZoom": 19,
// 		"type": "tile",
// 		"attribution": "",
// 		"layers": "GGE_ORTHO_RVB_ACTUELLE"
// 	}
// ]

return (object) array(
    'ckan' => (object) array(
		'url' => $ckanUrl,
		'api_key' => $ckanApiKey,
		'datapusher_key' => $datapusherKey,
		'datapusher_url' => $datapusherUrl,
		'db_ckan_name' => $dbCkanName,
		'db_datastore_name' => $dbDatastoreName,
		'db_user' => $dbUser,
		'db_pass' => $dbPass,
		'db_host' => $dbHost,
		'db_port' => $dbPort
	),
	'cluster' => (object) array(
		'url' => $clusterUrl
	),
	'client' => (object) array(
		'cache_time' => $clientCacheTime,
		'name' => $clientName,
		'domain' => $clientDomain,
		'css_file' => $clientCssFile,
		'default_bounding_box' => $clientDefaultBoundingBox,
		'map_bounding_box' => $clientMapBoundingBox,
		'routing_prefix' => $clientRoutingPrefix,
        'disqus' => $clientDisqus,
        'ressources_download_links' => $clientRessourcesDownloadLinks,
		'shapefile_projection' => $clientShapefileProjection,
        'nutch' => $clientNutch,
        'nutch_url' => $clientNutchUrl,
        'check_rgpd' => $clientCheckRgpd,
		'protocol' => $clientProtocol,
		'host' => $clientHost,
		'port' => $clientPort,
		'client_is_observatory' => $clientIsObservatory,
		'client_organisation' => $clientOrganisation,
		'drupal_root' => $clientRoot,
	),
	'sitesSearch' => array(
		'https://yyy.data4citizen.com/',
        'https://zzz.data4citizen.com/'
	),
	'gravitee' => array(
		'url' => $graviteeUrl,
		'header_key' => $graviteeHeaderKey,
		'api_key' => $graviteeApiKey
	),
    'csw' => array(
		'enabled' => $cswEnabled,
		'csw_server_path' => $cswServerPath,
		'csw_model' => $cswModel
	)
);

?>
