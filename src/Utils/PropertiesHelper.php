<?php

namespace Drupal\ckan_admin\Utils;

use Drupal\Component\Serialization\Yaml;
use Exception;

class PropertiesHelper {

	const DATABASE_TABLE = 'd4c_properties';

	// Create constants for the keys of the properties we want to use.
	const PACKAGE_DOWNLOAD_LIMIT = 'package_download_limit';
	const CO_LINKED_SURVEYS = 'co_linked_surveys';
	const MESSAGE_MAIL_RGPD = 'message_mail_rgpd';
	const MESSAGE_RGPD = 'message_rgpd';
	const TYPES_MIME = 'types_mime';
	const STOCKAGE_ALERT_THRESHOLD = 'stockage_alert_threshold';
	const STOCKAGE_ALERT_STATUS = 'stockage_alert_status';
	const RESERVED_COLUMNS_GEOPOINT = 'reserved_columns_geopoint';
	const RESERVED_COLUMNS_GEOSHAPE = 'reserved_columns_geoshape';

	private $config;

	public function __construct() {
		$this->config = include(__DIR__ . "/../../config.php");
	}

	private function getApiOptions() {
		$login = $this->config->client->master_api_login;
		$password = $this->config->client->master_api_password;

		$authenticationToken = base64_encode($login . ':' . $password);

		$headr = array();
		$headr[] = 'Content-length: 0';
		$headr[] = 'Content-type: application/json';
		$headr[] = 'Authorization: Basic ' . $authenticationToken;

		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST =>  0,
			CURLOPT_HTTPHEADER     => $headr,
		);
		return $options;
	}

	public function getProperty($property, $callApi = false, $decodeBase64 = false) {
		if ($callApi) {
			$callUrl =  $this->config->client->master_url . "/d4c/api/v1/properties/" . $property;
			$curl = curl_init($callUrl);
			curl_setopt_array($curl, $this->getApiOptions(true));
			$result = curl_exec($curl);
			curl_close($curl);

			$result = json_decode($result, true);
			if ($result["status"] == "success") {
				$value = $result["result"];
				if ($decodeBase64)
					$value = base64_decode($value);
				return $value;
			}

			throw new \Exception("Error while getting property '$property' from API '$callUrl'.");
		} else {
			return $this->extractProperty($property, $decodeBase64);
		}
	}

	private function extractProperty($key, $decodeBase64) {
		$query = \Drupal::database()->select(self::DATABASE_TABLE, "properties");
		$query->fields('properties', [
			'value'
		]);
		$query->condition('key', $key);

		$prep = $query->execute();
        $data = $prep->fetchAll();

		if (empty($data)) {
			return null;
		}

		$value = $data[0]->value;

		if ($decodeBase64)
			$value = base64_decode($value);
		return $value;
	}

	public function setProperty($key, $value, $encodeBase64 = false) {
		if ($encodeBase64)
			$value = base64_encode($value);

			$database = \Drupal::database();
			$query = $database->upsert(self::DATABASE_TABLE)
				->fields([
					'key',
					'value',
				])
				->values([
					$key,
					$value,
				])
				->key('key');
	
			$query->execute();
	}

	/**
	 * This method is used to update the swagger file of the API.
	 */
	public function updateSwagger() {
		$graviteeUrl = $this->config->gravitee->url;
		$graviteeHeaderKey = $this->config->gravitee->header_key;
		$graviteeApiKey = $this->config->gravitee->api_key;

		Logger::logMessage("TRM - Gravity URL: $graviteeUrl");

		// Check if gravitee URL is defined
		if (!empty($graviteeUrl)) {
			// We need to update the file openapi_data4citizen.yaml which is located in the current directory
			$swaggerFile = __DIR__ . "/../../openapi_data4citizen.yaml";
			$swaggerFileContent = file_get_contents($swaggerFile);

			// Parse the YAML content
			try {
				$data = Yaml::decode($swaggerFileContent);

				// Modify the servers URL
				$data['servers'][0]['url'] = $graviteeUrl;
	
				// Convert the updated data back to YAML
				$updatedYaml =Yaml::encode($data);
	
				// Save the modified content back to the file
				file_put_contents($swaggerFile, $updatedYaml);
			} catch (Exception $exception) {
				Logger::logMessage('Unable to parse the YAML string: ' . $exception->getMessage());
			}
		}
	}
}