<?php

return (object) array(
    'ckan' => (object) array(
		'url' => getenv('CKAN_URL'),
		'api_key' => getenv('CKAN_API_KEY'),
		'datapusher_key' => getenv('DATAPUSHER_KEY'),
		'datapusher_url' => getenv('DATAPUSHER_URL'),
		'db_ckan_name' => getenv('CKAN_DATABASE_NAME'),
		'db_datastore_name' => getenv('CKAN_DATASTORE_NAME'),
		'db_user' => getenv('CKAN_DATABASE_USERNAME'),
		'db_pass' => getenv('CKAN_DATABASE_PASSWORD'),
		'db_host' => getenv('CKAN_DATABASE_HOST'),
		'db_port' => getenv('CKAN_DATABASE_PORT')
	),
	'cluster' => (object) array(
		'url' => getenv('CLUSTER_URL')
	),
	'client' => (object) array(
		'cache_time' => getenv('CLIENT_CACHE_TIME'),
		'name' => getenv('CLIENT_NAME'),
		'domain' => getenv('CLIENT_DOMAIN'),
		'css_file' => getenv('CLIENT_CSS'),
		'default_bounding_box' => getenv('CLIENT_DEFAULT_BOUNDING_BOX'),
		'routing_prefix' => getenv('CLIENT_ROUTING_PREFIX'),
        'disqus' => getenv('CLIENT_DISQUS'),
        'ressources_download_links' => getenv('CLIENT_RESSOURCES_DOWNLOAD_LINKS'),
        'nutch' => getenv('CLIENT_NUTCH'),
        'nutch_url' => getenv('CLIENT_NUTCH_URL'),
        'check_rgpd' => getenv('CLIENT_CHECK_RGPD'),
		'protocol' => getenv('CLIENT_PROTOCOL'),
		'host' => getenv('CLIENT_HOST'),
		'port' => getenv('CLIENT_PORT')
	),
	'sitesSearch' => array(
		'https://yyy.data4citizen.com/',
        'https://zzz.data4citizen.com/'
	),
	'gravitee' => array(
		'url' => getenv('GRAVITEE_URL'),
		'api_key' => getenv('GRAVITEE_API_KEY')
	),
    'csw' => array(
		'enabled' => getenv('CSW_ENABLED'),
		'csw_server_path' => getenv('CSW_SERVER_PATH'),
		'csw_model' => getenv('CSW_MODEL')
	),
	'map_tiles' => array(
		(object) array(
            'name' => 'osm',
            'label' => 'OpenStreetMap',
            'provider' => 'osm',
            'url' => '',
            'minZoom' => 1,
            'maxZoom' => 19,
            'type' => 'tile',
            'key' => '',
            'attribution' => '',
			'mapId' => '',
			'layers' => ''
		)
	)
);

?>