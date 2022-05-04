<?php

return (object) array(
    'ckan' => (object) array(
		'url' => getenv('CKAN_URL'),
		'api_key' => getenv('CKAN_API_KEY'),
		'datapusher_key' => getenv('DATAPUSHER_KEY'),
		'datapusher_url' => getenv('DATAPUSHER_URL')
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
		'protocol' => getenv('CLIENT_PROTOCOL'),
		'host' => getenv('CLIENT_HOST')
	),
	'sitesSearch' => array(
		'https://yyy.data4citizen.com/',
        'https://zzz.data4citizen.com/'
	),
	'gravitee' => array(
		'url' => getenv('GRAVITEE_URL'),
		'api_key' => getenv('GRAVITEE_API_KEY')
	),
	'map_tiles' => array(
		(object) array(
            'name' => 'osm',
            'label' => 'OpenStreetMap',
            'provider' => 'osm',
            'url' => '',
            'minZoom' => 0,
            'maxZoom' => 19,
            'type' => 'tile',
            'key' => '',
            'attribution' => ''
		)
	)
);

?>
