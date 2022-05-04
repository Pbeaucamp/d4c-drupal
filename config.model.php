<?php

return (object) array(
    'ckan' => (object) array(
		'url' => 'http://ckan:5000/',
		'api_key' => 'xxx',
		'datapusher_key' => 'DodDHpXihz7p9nr',
		'datapusher_url' => 'http://datapusher:8800/'
	),
	'cluster' => (object) array(
		'url' => 'https://127.0.0.1:1337/'
	),
	'client' => (object) array(
		'cache_time' => 1,
		'name' => 'CLIENT_NAME',
		'domain' => 'CLIENT_NAME.data4citizen.com',
		'css_file' => 'client.css',
		'default_bounding_box' => '9,14.644846,-61.013067',
		'routing_prefix' => '',
        'disqus' => false,
        'ressources_download_links' => true,
        'nutch' => false,
        'nutch_url' => '',
        'check_rgpd' => true,
		'protocol' => 'http',
		'host' => 'localhost'
	),
	'sitesSearch' => array(
		'https://yyy.data4citizen.com/',
        'https://zzz.data4citizen.com/'
	),
	'gravitee' => array(
		'url' => '',
		'api_key' => ''
	),
    'csw' => array(
		'enabled' => false,
		'csw_server_path' => '/home/user-client/csw-server',
		'csw_model' => 'csw_server_model'
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
            'attribution' => ''
		)
	)
);

?>
