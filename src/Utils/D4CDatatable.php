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
use Drupal\ckan_admin\Model\D4CMetadata;
use Symfony\Component\HttpFoundation\Response;
use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\Logger;
use Drupal\ckan_admin\Utils\ResourceManager;


class D4CDatatable {

	private $config;
	protected $sqlDetails;
	protected $db;
	protected $datasetId;
	protected $resourceId;

	public function __construct() {
		$this->config = include(__DIR__ . "/../../config.php");

		$dbHost = $this->config->ckan->db_host;
		$dbPort = $this->config->ckan->db_port;
		$dbName = $this->config->ckan->db_datastore_name;
		$dbUser = $this->config->ckan->db_user;
		$dbPass = $this->config->ckan->db_pass;

		$this->sqlDetails = array(
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
	}

	function previewData($params) {
		$api = new Api;
		$query_params = $api->proper_parse_str($params);

		$encodeSqlQuery = $query_params['query'];
		$decodeSqlQuery = base64_decode($encodeSqlQuery);

		try {
			$databaseHelper = new DatabaseHelper();
			$data = $databaseHelper->executeQuery('datastore', 'datastore', $decodeSqlQuery, 20);
			$data = json_encode($data);
		}  catch (\Exception $e) {
			// Return http error code 500 with message
			$response = new Response();
			$response->setStatusCode(500);
			$response->setContent($e->getMessage());
			return $response;
		}

		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent($data);
		return $response;
	}
	
	function manageData($params) {
		$this->db = new Database($this->sqlDetails);

		$api = new Api;
		$query_params = $api->proper_parse_str($params);

		$this->datasetId = $query_params['datasetId'];
		$this->resourceId = $query_params['resource_id'];
		$fields = urldecode($query_params['fields']);
		// Split fields by comma
		$fields = explode(",", $fields);

		$api = new Api;
		$fieldsDefinition = $api->getAllFields($this->resourceId);

		$tableName = $this->resourceId;
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

		$editor = Editor::inst($this->db, $tableName, $columnKey);
		// $editor->debug(true);
		$editor->fields($editorFields);
		$editor->on('postEdit', function ($id, $data, $row) {
			$this->addModificationMetadata($this->datasetId, 'edit');
		});
		$editor->on('postCreate', function ($id, $data, $row) {
			$this->addModificationMetadata($this->datasetId, 'create');
		});
		$editor->on('postRemove', function ($id, $data, $row) {
			$this->addModificationMetadata($this->datasetId, 'remove');
		});
		$editor->process($_POST)->json();

		// Editor::inst($this->db, $tableName, $columnKey)
		// 	->fields( $editorFields )
		// 	->process( $_POST )
		// 	// Not working for now
		// 	// ->on("postCreate", function ($editor, $id, &$values, &$row) {
		// 	// 	Logger::logMessage("TRM - Test create");
		// 	// 	Logger::logMessage("manageData with datasetId: " . $this->datasetId . " resourceId: " . $this->resourceId);
		// 	// })
		// 	// ->on("postEdit", function ($editor, $id, &$values, &$row) {
		// 	// 	Logger::logMessage("TRM - Test edit");
		// 	// 	Logger::logMessage("manageData with datasetId: " . $this->datasetId . " resourceId: " . $this->resourceId);
		// 	// })
		// 	// ->on("postRemove", function ($editor, $id, &$values) {
		// 	// 	Logger::logMessage("TRM - Test remove");
		// 	// 	Logger::logMessage("manageData with datasetId: " . $this->datasetId . " resourceId: " . $this->resourceId);
		// 	// })
		// 	->json();

		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	function addModificationMetadata($datasetId, $type) {
		Logger::logMessage("addModificationMetadata with datasetId: " . $datasetId . " type: " . $type);
		
		$currentUser = \Drupal::currentUser();
		$accountName = $currentUser->getAccountName();

		$modifyData = array();
		$modifyData['type'] = $type;
		$modifyData['user-modify'] = $accountName;
		$modifyData['date-modify'] = date('Y-m-d H:i:s');

		$data = array();
		$data[] = $modifyData;

		$datatableModification = new D4CMetadata('datatable-modification', json_encode($data));
		$datatableModification->setAddToData(true);

		$datasetModification = new D4CMetadata('date_modification', date('Y-m-d'));

		$resourceManager = new ResourceManager();
		$resourceManager->updateDatasetMetadata($datasetId, 'extras', [$datatableModification, $datasetModification]);
	}
}