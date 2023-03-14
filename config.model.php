<?php

return (object) array(
    'ckan' => (object) array(
		'url' => 'http://ckan:5000/',
		'api_key' => 'xxx',
		'datapusher_key' => 'DodDHpXihz7p9nr',
		'datapusher_url' => 'http://datapusher:8800/',
		'db_ckan_name' => 'ckan',
		'db_datastore_name' => 'datastore',
		'db_user' => 'ckan',
		'db_pass' => 'xxx',
		'db_host' => 'ckan-db',
		'db_port' => '5432'
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
		'host' => 'localhost',
		'port' => '80',
		'enable_mail' => false,
		'client_is_observatory' => false,
		'client_organisation' => 'master',
		'drupal_root' => '/home/user-client/drupal-d4c/web'
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
	)
);

?>
