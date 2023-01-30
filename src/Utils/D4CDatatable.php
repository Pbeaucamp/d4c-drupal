<?php

namespace Drupal\ckan_admin\Utils;

// Alias Editor classes so they are easy to use
use
	DataTables\Database,
	DataTables\Editor,
	DataTables\Editor\Field,
	DataTables\Editor\Format,
	DataTables\Editor\Mjoin,
	DataTables\Editor\Options,
	DataTables\Editor\Upload,
	DataTables\Editor\Validate,
	DataTables\Editor\ValidateOptions;

use Symfony\Component\HttpFoundation\Response;
use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\Logger;


class D4CDatatable {

	private $config;
	protected $db;

	public function __construct() {
		$this->config = include(__DIR__ . "/../../config.php");

		$dbHost = $this->config->ckan->db_host;
		$dbPort = $this->config->ckan->db_port;
		$dbName = $this->config->ckan->db_datastore_name;
		$dbUser = $this->config->ckan->db_user;
		$dbPass = $this->config->ckan->db_pass;

		$sql_details = array(
			"type" => "Postgres", // Database type: "Mysql", "Postgres", "Sqlserver", "Sqlite" or "Oracle"
			"user" => $dbUser,
			"pass" => $dbPass,
			"host" => $dbHost,
			"port" => $dbPort,
			"db"   => $dbName,
			"dsn"  => "",          // PHP DSN extra information. Set as `charset=utf8mb4` if you are using MySQL
			"pdoAttr" => array()   // PHP PDO attributes array. See the PHP documentation for all options
		);

		Logger::logMessage("Init D4CDatatable with dbHost: " . $dbHost . " dbPort: " . $dbPort . " dbName: " . $dbName . " dbUser: " . $dbUser . " dbPass: " . $dbPass);
		
		//
		// Database connection
		//   Database connection is globally available
		//
		$this->db = new Database($sql_details);
	}
	
	function manageData($params) {
		$api = new Api;
		$query_params = $api->proper_parse_str($params);

		// $datasetId = $query_params['datasetId'];
		$resourceId = $query_params['resource_id'];
		$fields = urldecode($query_params['fields']);
		// Split fields by comma
		$fields = explode(",", $fields);

		$api = new Api;
		$fieldsDefinition = $api->getAllFields($resourceId);		

		$tableName = $resourceId;
		$columnKey = '_id';

		// New array to store the fields
		$editorFields = array();
		// Go through array of fields
		foreach ($fields as $field) {
			$fieldDefinition = array_filter($fieldsDefinition, function($item) use ($field) {
				return $item['name'] == $field;
			});
			$fieldDefinition = array_values($fieldDefinition);
			$fieldType = $fieldDefinition[0]['type'];

			// If numeric, we need to set the value as null if empty
			if ($fieldType == 'double') {
				$editorFields[] = Field::inst($field)->setFormatter( Format::ifEmpty( null ) );
			}
			else {
				$editorFields[] = Field::inst($field);
			}
		}

		//TODO : Gestion des valeurs NULL et des types date !
		//https://www.drupal8.ovh/en/tutoriels/353/get-table-column-names-drupal-8

		// Build our Editor instance and process the data coming from _POST
		// Editor::inst($this->db, $tableName, $columnKey)
		Editor::inst($this->db, $tableName, $columnKey)
			->debug(true)
			->fields( $editorFields )
			->process( $_POST )
			->json();

		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
}