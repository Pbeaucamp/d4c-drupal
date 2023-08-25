<?php

namespace Drupal\ckan_admin\Utils;

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
}