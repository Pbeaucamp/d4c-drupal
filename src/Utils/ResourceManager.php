<?php

namespace Drupal\ckan_admin\Utils;
use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\HarvestManager;
use Drupal\file\Entity\File;
use Drupal\ckan_admin\Utils\Logger;
use ZipArchive;
use \PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use \PhpOffice\PhpSpreadsheet\Reader\Xls;
use \PhpOffice\PhpSpreadsheet\Writer\Csv;


class ResourceManager {

	const ROOT = '/home/user-client/drupal-d4c/';
	/**
	 * This constant represent the maximum time the user can wait for the datapusher to load in the datastore (success or error)
	 * In sec
	 */
	const DATAPUSHER_WAIT_TIME = 120;

	function __construct() {

        $this->config = json_decode(file_get_contents(__DIR__ . "/../../config.json"));
		$this->urlCkan = $this->config->ckan->url;
	}

	function updateDatabaseStatus($isNew, $uniqId, $datasetId, $action, $status, $message) {
		$api = new Api;
		$api->updateDatabaseStatus($isNew, $uniqId, $datasetId, 'DATASET', 'MANAGE_DATASET', $action, $status, $message);
	}

	function createDataset($uniqId, $datasetName, $title, $description, $licence, $organization, $isPrivate, $tags, $extras) {
		Logger::logMessage("Create new dataset with name '" . $datasetName . "'");
		$this->updateDatabaseStatus(true, $uniqId, '', 'CREATE_DATASET', 'PENDING', 'Création du jeu de données \'' . $datasetName . '\'');
	
		$urlRes = $this->urlCkan . "/dataset/" . $datasetName;
		$newData = ["name" => $datasetName,
			"title" => $title,
			"private" => $isPrivate,
			"author" => "",
			"author_email" => "",
			"maintainer" => "",
			"maintainer_email" => "",
			"license_id" => $licence,
			"notes" => $description,
			"url" => $urlRes,
			"version" => "",
			"state" => "active",
			"type" => "dataset",
			"resources" => [],
			"tags" => $tags,
			"extras" => $extras,
			"relationships_as_object" => [],
			"relationships_as_subject" => [],
			"groups" => [],
			"owner_org" => $organization,
		];

		Logger::logMessage("TRM - DATASET '" . json_encode($newData) . "'");
		
		$coll = array('0'=>'0', '1'=>'');
			
		$datasetId = $this->saveData($newData, $coll);
		$datasetId = $datasetId[1];

		Logger::logMessage("New dataset has been saved with id '" . $datasetId . "'");
		$this->updateDatabaseStatus(false, $uniqId, $datasetId, 'CREATE_DATASET', 'SUCCESS', 'Le jeu de données \'' . $datasetName . '\' a été créé');
		return $datasetId;
	}

	function updateDataset($uniqId, $datasetId, $datasetToUpdate, $datasetName, $title, $description, $licence, $organization, $isPrivate, $tags, $extras) {
		Logger::logMessage("Updating dataset '" . $datasetName . "' with id = " . $datasetId);
		$this->updateDatabaseStatus(true, $uniqId, $datasetId, 'UPDATE_DATASET', 'PENDING', 'Mise à jour du jeu de données \'' . $datasetName . '\'');
		
		$datasetToUpdate[title] = $title;
		$datasetToUpdate[notes] = $description;
		$datasetToUpdate[license_id] = $licence;
		$datasetToUpdate['private'] = $isPrivate;
		$datasetToUpdate[extras] = $extras;
		$datasetToUpdate["tags"] = $tags;

		$api = new Api;
        $callUrl = $this->urlCkan . "/api/action/package_update";
		$result = $api->updateRequest($callUrl, $datasetToUpdate, "POST");
		$result = json_decode($result);
		if ($result->success == true) {
			$currentOrganization = $datasetToUpdate[organization][id];
			Logger::logMessage("Comparing current organization '" . $currentOrganization . "' with selected organization '" . $organization . "'");

			if ($currentOrganization != $organization) {
				Logger::logMessage("Updating organization.");
			
				$callUrl = $this->urlCkan . "/api/action/package_owner_org_update";
				$result = $api->updateRequest($callUrl, ["id" => $datasetToUpdate[id], "organization_id" => $organization], "POST");
				$result = json_decode($result);
				if ($result->success != true) {
					$this->updateDatabaseStatus(false, $uniqId, $datasetId, 'UPDATE_DATASET', 'ERROR', "L'organisation ne peut pas être mise à jour ' (" . $result->error->message . ").");
					throw new \Exception("L'organisation ne peut pas être mise à jour ' (" . $result->error->message . ").");
				}
			}

			$this->updateDatabaseStatus(false, $uniqId, $datasetId, 'UPDATE_DATASET', 'SUCCESS', 'Le jeu de données \'' . $datasetName . '\' a été mis à jour');
			return $datasetId;
		}
		else {
			$this->updateDatabaseStatus(false, $uniqId, $datasetId, 'UPDATE_DATASET', 'ERROR', "Le jeu de données ne peut pas être mis à jour (" . $result->error->message . ").");
			throw new \Exception("Le jeu de données ne peut pas être mis à jour (" . $result->error->message . ").");
		}
	}

	function manageFile($file) {
		Logger::logMessage("Managing file from FORM POST");

		$file = File::load($file);

		$file->setPermanent();
		$file->save();

		$resourceUrl = $file->createFileUrl(FALSE);
		
		Logger::logMessage("TRM: Saving file with URL = " . $resourceUrl . ".");
		return $resourceUrl;
	}

	function manageFileWithPath($datasetId, $generateColumns, $isUpdate, $resourceId, $resourceUrl, $description, $encoding, $unzipZip = false, $fromPackage = false, $transformFile = true) {
		$results = array();

		//Managing file (filepath and filename)
		$fileName = parse_url($resourceUrl);

		$host = $fileName[host];
		$fileName = $fileName[path];
		$filePath = $fileName;

		$fileName = strtolower($fileName);
		$fileName = urldecode($fileName);
		$fileName = $this->nettoyage2($fileName);
		$fileName = explode("/", $fileName);
		$fileName = $fileName[(count($fileName)-1)];
		Logger::logMessage("TRM fileName " . $fileName);

		Logger::logMessage("TRM filePath " . $filePath);
		$filePathN = urldecode($filePath);
		$filePathN = $this->nettoyage2($filePathN);
		Logger::logMessage("TRM filePathN " . $filePathN);

		//Used (it was used for updating resource but not for new ones) ?
		// $filePathN = explode(".", $filePathN)[0] . uniqid() .".". explode(".", $filePathN)[1];
		Logger::logMessage("TRM filePathN " . $filePathN);

		rename(self::ROOT . urldecode($filePath), self::ROOT . $filePathN); 
		$filePath = $filePathN;
		Logger::logMessage("TRM filePath " . $filePath);

		$resourceUrl = str_replace('http:', 'https:', $resourceUrl);
		$resourceUrl = 'https://' . $host . '' . $filePath;
		Logger::logMessage("TRM resourceUrl " . $resourceUrl);
		
		Logger::logMessage("Managing file '" . $filePath . "'");

		$api = new Api;
		try {
			$filesize = filesize(self::ROOT . $filePath);
		} catch (\Exception $e) {
			$filesize = 0;
			error_log('Unable to get file size for ' . self::ROOT . $filePath);
		}

		try {
			$type = $this->extractFormat($filePath);
		} catch (\Exception $e) {
			Logger::logMessage("Impossible de récupérer le format du fichier (" . $e->getMessage() . ")");
		}
		
		Logger::logMessage("Found format " . $type);

		$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'MANAGE_FILE', 'PENDING', 'Traitement du fichier ' . $fileName . ' au format ' . $type . '');
		if ($type == 'csv') {

			//if files > 50MB we don't do the treatments.
			if ($transformFile && $filesize < 50000000) {
				$reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
				if ($encoding) {
					Logger::logMessage("Setting encoding to " . $encoding . "\r\n");
					$reader->setInputEncoding($encoding);
				}
				Logger::logMessage("TRM - Loading spreadsheet ROOT " . self::ROOT);
				Logger::logMessage("TRM - Loading spreadsheet filePath " . $filePath);
				$spreadsheet = $reader->load(self::ROOT . $filePath);
				$highestRow = $spreadsheet->getActiveSheet()->getHighestRow(); // e.g. 10
				$highestColumn = $spreadsheet->getActiveSheet()->getHighestColumn(); // e.g 'F'

				//We have an issue with number format. This line transform coordinate and it's not good. We comment it for now
				//Maybe we have to do the same for XLS, XLSX
				//$spreadsheet->getActiveSheet()->getStyle('A1:' . $highestColumn . $highestRow)->getNumberFormat()->setFormatCode('###.##');

				$existingCols = array();

				// TODO: We should save real column from the file and put it in the dictionnary
					
				if($generateColumns) {
					$spreadsheet->getActiveSheet()->insertNewRowBefore(1, 1);
				}

				$nbColumns = $this->lettersToNumber($highestColumn);
				for($i=1; $i<= $nbColumns; $i++) {
					if ($generateColumns) {
						$label = 'colonne_' . $i;
					}
					else {
						$label = $spreadsheet->getActiveSheet()->getCell($this->numberToLetters($i) . '1')->getValue();
					}

					$label = $this->nettoyage($label);
					if(in_array($label, $existingCols)) {
						$label = $label . $i;
					}
					$existingCols[] = $label;
					
					$spreadsheet->getActiveSheet()->getCell($this->numberToLetters($i) . '1')->setValue($label);
				}
					
				$writer = new Csv($spreadsheet);
				if ($generateColumns) {
					$filePath = str_ireplace('.csv', '_gencol.csv', $filePath);
					$resourceUrl = 'https://' . $host . $filePath;
				}
				$writer->save(self::ROOT . $filePath);
			}


			$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'MANAGE_FILE', 'SUCCESS', 'Traitement du fichier ' . $fileName . ' terminé.');
			$result = $this->uploadResourceToCKAN($api, $datasetId, $isUpdate, $resourceId, $resourceUrl, $fileName, $type, $description, true);
			$results[] = $result;
		}
		else if ($type == 'xls' || $type == 'xlsx') {

			//if files > 50MB we don't do the treatments.
			if ($transformFile && $filesize < 50000000) {

				$xls_file = self::ROOT . $filePath;
				
				$reader = new Xlsx();
			
				if(explode(".", $fileName)[1]  === 'xls' ||explode(".", $fileName)[1] === 'XLS') {
					$reader = new Xls();
				}
				$reader->setReadDataOnly(true);
				$spreadsheet = $reader->load($xls_file);

				$loadedSheetNames = $spreadsheet->getSheetNames();
				$highestRow = $spreadsheet->getActiveSheet()->getHighestRow(); // e.g. 10
				$highestColumn = $spreadsheet->getActiveSheet()->getHighestColumn(); // e.g 'F'
				$spreadsheet->getActiveSheet()->getStyle('A1:' . $highestColumn . $highestRow)->getNumberFormat()->setFormatCode('###.##');
				$writer = new Csv($spreadsheet);

					
				$csvpath = str_replace(array('.xlsx', '.xls', '.XLSX', '.XLS'), array('.csv', '.csv', '.csv', '.csv'), self::ROOT . $filePath);
				$resourceUrl = str_replace(array('.xlsx', '.xls', '.XLSX', '.XLS'), array('.csv', '.csv', '.csv', '.csv'), $resourceUrl);
				$fileName = str_replace(array('.xlsx', '.xls', '.XLSX', '.XLS'), array('.csv', '.csv', '.csv', '.csv'), $fileName);
				$filePath = str_replace(array('.xlsx', '.xls', '.XLSX', '.XLS'), array('.csv', '.csv', '.csv', '.csv'), $filePath);

				foreach($loadedSheetNames as $sheetIndex => $loadedSheetName) {
					$writer->setSheetIndex($sheetIndex);
					Logger::logMessage("Saving CSV for sheet at index " . $sheetIndex . " with path '" . $csvpath . "'");
					$writer->save($csvpath);
					break;
				}

				$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'MANAGE_FILE', 'SUCCESS', 'Traitement du fichier ' . $fileName . ' terminé.');
				$result = $this->manageFileWithPath($datasetId, $generateColumns, $isUpdate, $resourceId, $resourceUrl, $description, $encoding, false, $fromPackage);
				$results = array_merge($results, $result);
			}
			else {
				$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'MANAGE_FILE', 'SUCCESS', 'Traitement du fichier ' . $fileName . ' terminé.');
				$result = $this->uploadResourceToCKAN($api, $datasetId, $isUpdate, $resourceId, $resourceUrl, $fileName, $type, $description, true);
				$results[] = $result;
			}
		}
		else if ($type == 'zip') {
			//If we update the ZIP, we need to get the previous ZIP resource to update it and keep the same URL
			if ($isUpdate) {
				Logger::logMessage("Looking for the previous ZIP resource to update");
				$dataset = $api->getPackageShow("id=" . $datasetId);

				$resources = $dataset['result']['resources'];

				//We check if the zip was previously unzip by counting the resources
				$unzipZip = count($resources) > 1;

				Logger::logMessage("TRM - FOUND '" . count($resources) . "' RESOURCES " . json_encode($resources));
				foreach($resources as $resource){
					if (strpos($resource['name'], 'zip') !== false) {

						Logger::logMessage("TRM - FOUND RESOURCE " . json_encode($resource));

						$resourceId = $resource['id'];
						$name = $resource['name'];

						//We need to change the PATH of the zip file to match the previous version and backup the old one
						Logger::logMessage("Found the previous ZIP resource '" . $resourceId . "' with name '" . $name . "'");


						$rootDirectory = 'sites/default/files/dataset/';
						
						$rootOldFile = self::ROOT . $rootDirectory . $name;
						$oldFile = '/' . $rootDirectory . $name;
						$oldFileBackup = $rootDirectory . "backup_" . $name;
						Logger::logMessage("TRM - Rename '" . $rootOldFile . "' in '" . $oldFileBackup . "'");
						//We backup the old one
						rename($rootOldFile, $oldFileBackup);

						$newFile = $rootDirectory . $fileName;
						Logger::logMessage("TRM - Rename '" . $newFile . "' in '" . $oldFile . "'");
						//We rename the new file into the old file
						rename($newFile, $rootOldFile);

						Logger::logMessage("TRM - FILE PATH BEFORE '" . $filePath . "'");
						//We define the old value for the rest of the process
						$fileName = $name;
						$resourceUrl = $resource['url'];
						$filePath = $oldFile;
						Logger::logMessage("TRM - FILE PATH AFTER '" . $filePath . "'");

						break;
					}
				}  
			}

			$result = $this->uploadResourceToCKAN($api, $datasetId, $isUpdate, $resourceId, $resourceUrl, $fileName, $type, $description, false);
			$results[] = $result;

			if ($unzipZip) {
				$result = $this->manageZip($datasetId, $generateColumns, false, $resourceId, $filePath, $encoding);
				$results = array_merge($results, $result);
			}
		}
		else if ($type == 'json' || $type == 'geojson' || $type == 'kml' || $type == 'shp') {
			// We upload the geojson file as resource
			$result = $this->uploadResourceToCKAN($api, $datasetId, $isUpdate, $resourceId, $resourceUrl, $fileName, $type, $description, false);
			$results[] = $result;

			$csv = $this->manageGeoFiles($type, $resourceUrl, $filePath);
			$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'MANAGE_FILE', 'SUCCESS', 'Traitement du fichier ' . $fileName . ' terminé.');

			if ($csv != null) {
				//If we update the geojson, we need to get the previous CSV resource to update it
				if ($isUpdate) {
					Logger::logMessage("Looking for the previous CSV resource to update");
					$dataset = $api->getPackageShow("id=" . $datasetId);
					foreach($dataset['result']['resources'] as $resource){
						if (strpos($resource['name'], 'csv_gen') !== false) {
							$resourceId = $resource['id'];

							//We change the name in order to change the resource URL
							//If we don't do that, the file is not uploaded to the datapusher
							$name = "csv_gen_" . $datasetId . "_" . uniqid() . '.csv';
							Logger::logMessage("Found the previous CSV resource '" . $resourceId . "' with name '" . $name . "'");
							break;
						}
					}  
				}
				
				if (!$name) {
					$name = "csv_gen_" . $datasetId . "_" . uniqid() . '.csv';
					Logger::logMessage("Uploading CSV from GeoFile with name '" . $name . "'");

					$isUpdate = false;
				}
				
				$rootCsv = self::ROOT . 'sites/default/files/dataset/' . $name;
				$resourceUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/sites/default/files/dataset/' . $name;

				file_put_contents($rootCsv, $csv);

				$result = $this->manageFileWithPath($datasetId, $generateColumns, $isUpdate, $resourceId, $resourceUrl, '', $encoding, false, $fromPackage, false);
				$resourceId = $this->array_key_first($result[0]);
				$results = array_merge($results, $result);
				
				if ($result[0][$resourceId]['status'] == 'complete') {
					//We create the clusters
					$results[] = $this->createClusters($datasetId, $resourceId, ',', 'UTF-8', 'geo_point_2d', ',', $fileName);
				}

				return $results;
			}

			$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'MANAGE_FILE', 'ERROR', "Le fichier '" . $fileName . "' ne peut pas être converti en CSV pour être intégré à l\'application.");
			throw new \Exception("Le fichier '" . $fileName . "' ne peut pas être converti en CSV pour être intégré à l\'application.");
		}
		else {
			// We upload the file as resource
			$result = $this->uploadResourceToCKAN($api, $datasetId, $isUpdate, $resourceId, $resourceUrl, $fileName, $type, $description, false);
			$results[] = $result;
			// $this->updateDatabaseStatus(false, $datasetId, $datasetId, 'MANAGE_FILE', 'ERROR', 'Une erreur est survenue avec le fichier ' . $fileName . '.');
			// Logger::logMessage("We do not process the file '" . $filePath . "'");
		}

		return $results;
	}

	function array_key_first(array $array) { foreach ($array as $key => $value) { return $key; } }

	/**
	 * Create the clusters for a resource
	 */
	function createClusters($datasetId, $resourceId, $separator, $encoding, $colCoordinate, $coordinateSeparator, $fileName) {
		Logger::logMessage("createClusters");
	
		$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'CREATE_CLUSTER', 'PENDING', 'Création des clusters pour le fichier \'' . $fileName . '\'');

		$geolocHelper = new GeolocHelper();
		$result = $geolocHelper->buildGeoloc($datasetId, $resourceId, $separator, $encoding, 0, $colCoordinate, $coordinateSeparator, null, null, null, null, null, null, null, null, false);
		if ($result == 'SUCCESS') {
			Logger::logMessage("Clusters created with success");

			$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'CREATE_CLUSTER', 'SUCCESS', 'Les clusters ont été générés pour le fichier \'' . $fileName . '\'');

			return $this->buildResponse($resourceId, 'CLUSTER', null, 'complete', null, null);
		}
		else {
			Logger::logMessage("Clusters created with error (" . json_encode($result) . ")");

			$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'CREATE_CLUSTER', 'ERROR', "Impossible de générer les clusters pour le fichier \'' . $fileName . '\' (" . $result . ")");

			return $this->buildResponse($resourceId, 'CLUSTER', null, 'error', null, $result);
		}
	}

	/**
	 * This method build a response for the user
	 * We display this as a result of the dataset creation
	 * 
	 * $type -> CLUSTER, DATAPUSHER
	 * $status -> complete, error, pending
	 */
	function buildResponse($resourceId, $type, $fileName, $status, $resourceUrl, $message) {
		$result = array();
		$result[$resourceId]['type'] = $type;
		$result[$resourceId]['filename'] = $fileName;
		$result[$resourceId]['status'] = $status;
		$result[$resourceId]['resourceUrl'] = $resourceUrl;
		$result[$resourceId]['message'] = $message;
		return $result;
	}

	/**
	 * Generate a geojson file (if it does not exist) and a CSV file from various type of Geo format
	 * 
	 * $type can be geojson, json, kml and shp
	 * $id of the file
	 * $url of the file
	 * 
	 */
	function manageGeoFiles($type, $resourceUrl, $filePath) {
		Logger::logMessage("Manage " . $type . " file");

		Logger::logMessage("Retrieving file '" . $resourceUrl . "'");
		$fileContent = Query::callSolrServer($resourceUrl);

		if ($type == 'geojson' || $type == 'json'){
			$csv = $this->buildCSVFromGeojson($fileContent);
		}
		else if ($type == 'json') {
			$json_match = false;
			if ($type == 'json') {
				$json = file_get_contents($resourceUrl);
				$json = json_decode($json, true);
				if (isset($json["type"]) && $json["type"] == "FeatureCollection") {
					$json_match = true;
				}
			}

			if ($json_match) {
				$csv = $this->buildCSVFromGeojson($fileContent);
			}
			else {
				Logger::logMessage("No CSV file generated from Geo File");
				$csv = null;
			}
		}
		else if ($type == 'kml' || $type == 'shp') {
			//We create a tmp file in which we write the result and an output file to convert
			// $pathInput = tempnam(sys_get_temp_dir(), 'input_convert_geo_file_');
			// $fileInput = fopen($pathInput, 'w');
			// fwrite($fileInput, $fileContent);
			// fclose($fileInput);

			$scriptPath = self::ROOT . 'modules/ckan_admin/src/Utils/convert_geo_files_ogr2ogr.sh';
			$filePath = self::ROOT . $filePath;

			$typeConvert = 'GEOJSON';
			
			Logger::logMessage("Building Geojson from shape file '" . $resourceUrl . "' with file path '" . $filePath . "'");

			$rootJson= self::ROOT . 'sites/default/files/dataset/gen_'.uniqid().'.geojson';
			$command = $scriptPath." 2>&1 '" . $typeConvert . "' " . $rootJson . " " . $filePath . "";
			
			Logger::logMessage("OGR2OGR command '" . $command . "'");
			$message = shell_exec($command);
			Logger::logMessage("Result from shape conversion '" . json_encode($message) . "'");

			$json = file_get_contents ($rootJson);

			$csv = $this->buildCSVFromGeojson($json);
			unlink ($rootJson);
		}
		else {
			throw new \Exception('Le type de fichier ' . $type . ' is not supported.');
		}

		return $csv;
	}

	function manageGsheet($datasetId, $urlGsheet) {
		$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'CREATE_FILE', 'PENDING', 'Création du fichier depuis le fichier Google Sheet \'' . $urlGsheet . '\'');

        // récuperer l'url google sheet
        $jsonData = file_get_contents($urlGsheet);
        $rows = explode("\n", $jsonData);
        $contenturlsheet = array();
       
        foreach($rows as $row) {
            $contenturlsheet[] = str_getcsv($row);
		}

        // save the content of GSheeturl in csv file and get url of resource
		$data = $contenturlsheet;
		if (!file_exists($_SERVER['DOCUMENT_ROOT'] . "/sites/default/files/dataset/urlsheet/")) {
			mkdir($_SERVER['DOCUMENT_ROOT'] . "/sites/default/files/dataset/urlsheet/", 0777, true);
		}

		$fileName = $datasetId . ".csv";

		$fp = fopen($_SERVER['DOCUMENT_ROOT'] . "/sites/default/files/dataset/urlsheet/" . $fileName, "wb");
		fputs($fp, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
		foreach ( $data as $line ) {
			fputcsv($fp, $line);
		}
		fclose($fp);

		$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'CREATE_FILE', 'SUCCESS', 'Le fichier \'' . $fileName . '\' a été créé depuis le fichier Google Sheet \'' . $urlGsheet . '\'');

		return 'https://' . $_SERVER['HTTP_HOST'] . '/sites/default/files/dataset/urlsheet/' . $fileName;
	}

	function manageXmlfile($url) {
		$api = new Api;
        // récuperer l'url xml file
        $jsonData = file_get_contents($url);
        $rows = explode("\n", $jsonData);
        $contenturlsheet = array();
       
        foreach($rows as $row) {
        	$row = str_replace(";", ",", $row);
            $contenturlsheet[] = str_getcsv($row);
		}

        // save the content of xml url in csv file and get url of resource
		$data = $contenturlsheet;
		if (!file_exists($_SERVER['DOCUMENT_ROOT'] . "/sites/default/files/dataset/xmlfile/")) {
			mkdir($_SERVER['DOCUMENT_ROOT'] . "/sites/default/files/dataset/xmlfile/", 0777, true);
		}
		
		$query_params = $api->proper_parse_str($url);
		$fileName = $query_params["resource_id"] . "-xml" . ".csv";

		$fp = fopen($_SERVER['DOCUMENT_ROOT'] . "/sites/default/files/dataset/xmlfile/" . $fileName, "wb");
		fputs($fp, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
		foreach ( $data as $line ) {
			
			fputcsv($fp, $line);
		}

		fclose($fp);
		$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'CREATE_FILE', 'SUCCESS', 'Le fichier \'' . $fileName . '\' a été créé depuis le fichier xml \'' . $urlGsheet . '\'');

		return 'https://' . $_SERVER['HTTP_HOST'] . '/sites/default/files/dataset/xmlfile/' . $fileName;
	}
	
	function managePackage($uniqId, $resourceUrl, $security, $organization) {
		Logger::logMessage("Manage package file '" . $resourceUrl . "'");

		$fileName = parse_url($resourceUrl);

		$host = $fileName[host];
		$fileName = $fileName[path];
		$filePath = $fileName;

		$fileName = strtolower($fileName);
		$fileName = urldecode($fileName);
		$fileName = $this->nettoyage2($fileName);
		$fileName = explode("/", $fileName);
		$fileName = $fileName[(count($fileName)-1)];

		$filePathN = urldecode($filePath);
		$filePathN = $this->nettoyage2($filePathN);

		rename(self::ROOT . urldecode($filePath), self::ROOT . $filePathN); 
		$filePath = $filePathN;

		$resourceUrl = str_replace('http:', 'https:', $resourceUrl);
		$resourceUrl = 'https://' . $host . '' . $filePath;

		Logger::logMessage("Managing package with path '" . $filePath . "'");

		$directoryName = 'package_extraction_' . uniqid();
		$directoryPath = self::ROOT . 'sites/default/files/dataset/' . $directoryName;

		$zip = new ZipArchive;
		$res = $zip->open(self::ROOT . $filePath);
		if ($res === TRUE) {
			// extract it to the path we determined above
			$zip->extractTo($directoryPath);
			$zip->close();

			$files = $this->getDirContents($directoryPath);

			//We create the dataset and the metadata
			foreach($files as $key=>$file) {
				Logger::logMessage("Looking for metadata.json. Found '" . $file . "'");

				if (strpos($file, 'metadata.json') !== false) {
					Logger::logMessage("Found metadata.json, creating dataset.");

					$string = file_get_contents($file);
					$metadataJson = json_decode($string, true);

					$datasetName = $metadataJson['metas']['name'];
					$title = $metadataJson['metas']['title'];
					$description = $metadataJson['metas']['description'];
					$licence = $metadataJson['metas']['license_id'];
					$isPrivate = $metadataJson['metas']['private'];

					$tags = $metadataJson['metas']['tags'];
					$extras = $metadataJson['metas']['extras'];
					$fields = $metadataJson['dictionnary'];
				
					$datasetId = $this->createDataset($uniqId, $datasetName, $title, $description, $licence, $organization, $isPrivate, $tags, $extras);
				}
			}

			if ($datasetId == null) {
				Logger::logMessage("Unable to create dataset. Maybe metadata.json was not found.");
				throw new \Exception('Le package ne peut pas être extrait.');
			}

			//We upload the ressources
			$results = array();
			$results['datasetId'] = $datasetId;
			$results['resources'] = $this->manageFiles($datasetId, false, false, null, $directoryPath, 'UTF-8', true);

			// We reupload the dictionnary
			if ($fields != null) {
				Logger::logMessage("Reuploading dictionnary.");
				$api = new Api;

				// $filds = $api->getAllFieldsForTableParam($id_resource, 'true');
				$dataset = $api->getDataSetById($datasetId);
				$contentdataset = json_decode($dataset->getContent(), true);
				$resources = $contentdataset["result"]["resources"];

				//We retrieve the new CSV resource
				for($i=0; $i<count($resources); $i++){
					if ($resources[$i]['format'] == 'CSV') {
						$resourceId = $resources[$i]['id'];   
						break;
					}
				}

				if ($resourceId) {
					Logger::logMessage("Found resource CSV " . $resourceId);
					$callUrl =  $this->urlCkan . "/api/action/datastore_create";
					$data = array();
					$data["resource_id"] = $resourceId;
					$data["force"] = true;
					$data["fields"] = $fields;
					$data["uuid"] = uniqid();

					// Logger::logMessage("TRM - Reuploading dictionnary with data " . json_encode($data));

					$result = $api->updateRequest($callUrl, $data, "POST");

					Logger::logMessage("TRM - RESULT DICTIONNARY " . $result);
				}
				else {
					Logger::logMessage("Resource CSV not found. We do not reupload dictionnary.");
				}
			}

			return $results;
		}
		else {
			throw new \Exception('Le package ne peut pas être extrait.');
		}
	}
	
	function manageZip($datasetId, $generateColumns, $isUpdate, $resourceId, $filePath, $encoding) {
		Logger::logMessage("Manage zip file with path '" . self::ROOT . $filePath . "'");
		// $path = pathinfo(realpath($filePath), PATHINFO_DIRNAME);

		$directoryName = 'zip_extraction_' . uniqid();
		$directoryPath = self::ROOT . 'sites/default/files/dataset/' . $directoryName;

		$zip = new ZipArchive;
		$res = $zip->open(self::ROOT . $filePath);
		if ($res === TRUE) {
			// extract it to the path we determined above
			$zip->extractTo($directoryPath);
			$zip->close();

			return $this->manageFiles($datasetId, $generateColumns, $isUpdate, $resourceId, $directoryPath, $encoding, false);
		}
		else {
			throw new \Exception('Le fichier ne peut pas être extrait.');
		}
	}

	function manageFiles($datasetId, $generateColumns, $isUpdate, $resourceId, $directoryPath, $encoding, $fromPackage) {
		Logger::logMessage("Managing files in '" . $directoryPath . "'");
		$results = array();

		//We set all files to lowercase before processing
		$di = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($directoryPath, \FilesystemIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::LEAVES_ONLY
		);
		foreach($di as $name => $fio) {
			$newname = $fio->getPath() . DIRECTORY_SEPARATOR . strtolower( $fio->getFilename() );
			rename($name, $newname);
		}
		
		$files = $this->getDirContents($directoryPath);

		$color_array = [];
		foreach($files as $key=>$file) {
			if (strpos($file, 'routes.txt') !== false) {
				$key_rout_id="";
				$key_color_route ="";
				$array = explode("\n", file_get_contents($file));
				foreach ($array as $key => $value) {
					$line = explode(',', $value);
					if($key == 0 ){
						$key_rout_id = array_search('route_id', $line);
						$key_color_route = array_search('route_color', $line);
					}
					else {
						array_push($color_array,array("route_id"=>$line[$key_rout_id], "color_route"=>$line[$key_color_route]));
					}
				}
			}
		}
		// We check if the zip is a GTFS. 
		// We check if shapes.txt exist inside zip
		foreach($files as $key=>$file) {
			Logger::logMessage("Managing file '" . $file . "'");
			
			if (is_dir($file)) {
				Logger::logMessage("Ignoring '" . $file . "' because it is a folder");
				continue;
			}
			else if (strpos($file, 'shapes.txt') !== false) {
				Logger::logMessage("Found shapes.txt -> Managing GTFS");
				$resourceUrl = $this->convertTextFileToCsv($file, "csv", $color_array);
			}
			else if ($fromPackage && (strpos($file, 'metadata.json') !== false) || strpos($file, "csv_gen") !== false) {
				Logger::logMessage("Ignoring '" . $file . "' because it is autogenerated by D4C");
				//Skipping metadata.json and autogenerated D4C's file from a package. We don't add it to the dataset
				continue;
			}
			else {
				// $fileName = pathinfo($file, PATHINFO_FILENAME);
				// $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
				// $resourceUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/sites/default/files/dataset/' . $directoryName . '/' . $fileName . "." . $fileExtension;

				$resourceUrl = str_replace(self::ROOT, 'https://' . $_SERVER['HTTP_HOST'] . '/', $file);
				Logger::logMessage("TRM - Zip file URL '" . $resourceUrl . "'");
			}

			$result = $this->manageFileWithPath($datasetId, $generateColumns, $isUpdate, $resourceId, $resourceUrl, '', $encoding, false, $fromPackage);
			$results = array_merge($results, $result);
		}

		return $results;
	}

	//convert text file to csv
	function convertTextFileToCsv($filepath, $new_extension, $color_array) {

		// get content of text file
		$filepathContent = file_get_contents($filepath);

		// check if file contains the comma and replace it by semicolon  
		if (strpos(file_get_contents($filepath), ',') !== false) {
			/*$commaReplace = str_replace(",",";",$filepathContent);*/
			$pathinfo = pathinfo($filepath);
			$pathinfo["extension"] = $new_extension;

			$pathfiles = explode("/", $filepath);
			$pathfiles[sizeof($pathfiles) -1] = $pathinfo["filename"]. "." .$new_extension; 
			$newfile="";
			foreach ($pathfiles as $key => $value) {

				if($key == 0) {
					$newfile= $value;
				}
				else {
					$newfile .="/".$value;
				}
				
			}
			//create a new csv files contains the same content of text file
			file_put_contents($newfile, $filepathContent);

			$delimiter = "\t"; //your column separator
			$csv_data = array();
			$row = 1;
			if (($handle = fopen($newfile, 'r')) !== FALSE) {
				while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
					$csv_data[] = $data;
					$row++;
				}
				fclose($handle);
			}

			$extra_columns = array('coordinate' => null, 'geoshape' => null, 'route_color' => null);
			$latIndex = "";
			$lngIndex = "";
			$shapeIdIndex = "";
			$coordinatesGeojson=[];
			$coordinatearray = [];
			$Routes=[];

			foreach ($csv_data as $i => $data) {
				foreach ($data as $key => $value) {
					if($value == "shape_pt_lat") {
						$latIndex = $key;
					}
					if($value == "shape_pt_lon") {
						$lngIndex = $key;
					}
					if($value == "shape_id") {
						$shapeIdIndex = $key;
					}
					}
					if($i!=0) {
						if($i+1 < sizeof($csv_data)) {
						$data2 = $csv_data[$i+1];
						if($data[$shapeIdIndex] != $data2[$shapeIdIndex] ) {
							array_push($Routes, $i);
						}
						
					}

					}
					

			}
			$routesvalue=[];

			array_unshift($color_array,"");
			unset($color_array[0]);
			foreach ($Routes as $key => $value) {
				
				if($key == 0) {
					$firstindex = 1;
				}else {
					$firstindex = $Routes[$key -1 ] + 1;
				}

				$array1=[];
				$array2 =array();
				$array3=[];
			

					for ($i=$firstindex; $i <=$value ; $i++) { 

						$array2 =array((float)$csv_data[$i][$lngIndex],(float)$csv_data[$i][$latIndex]);
						$array3[] = $array2;
				}

				
					$routesvalue[$key+1] = json_encode(array('type' => "LineString",'coordinates' => $array3));

			}

			foreach ($csv_data as $i => $data) {
				
				/*$geo_shape = str_replace(",",";",$routesvalue[$i]);*/
				
				if ($i == 0) {
					$extra_columns = array('coordinate' => (float)$data[$latIndex] ."," . (float)$data[$lngIndex], 'geo_shape' => "", 'route_color' => null);
					$csv_data[$i] = array_merge($data, array_keys($extra_columns));
				} else {

						if (array_key_exists($i,$routesvalue)) {
							$geo_shape = $routesvalue[$i];
							if($color_array[$i]["color_route"] != null) {
								$color = $color_array[$i]["color_route"];
							} else {
								$random_color = str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
								 
								 $keycolor = array_search($random_color, array_column($color_array, 'color_route'));
								 if($keycolor ==false){
								 	$color = $random_color;
								 }
							}
							$extra_columns = array('coordinate' => (float)$data[$latIndex] ."," . (float)$data[$lngIndex], 'geo_shape' => $geo_shape, 'route_color' => $color);
									
						} 
						else {
							$extra_columns = array('coordinate' => (float)$data[$latIndex] ."," . (float)$data[$lngIndex], 'geo_shape' => "", 'route_color' => "");
						}
						
						$csv_data[$i] = $data = array_merge($data, $extra_columns);
				}


			}

			if (($handle = fopen($newfile, 'w')) !== FALSE) {
				foreach ($csv_data as $data) {
					fputcsv($handle, $data, ",");
				}
				fclose($handle);
			}
			$newfile = str_replace($_SERVER['DOCUMENT_ROOT'],'https://' . $_SERVER['HTTP_HOST'], $newfile);
			return $newfile;
		}
	}

	function getDirContents($dir, &$results = array()) {
		$files = scandir($dir);
	
		foreach ($files as $key => $value) {
			$path = realpath($dir . DIRECTORY_SEPARATOR . $value);
			if (!is_dir($path)) {
				$results[] = $path;
			}
			else if ($value != "." && $value != "..") {
				$this->getDirContents($path, $results);
				$results[] = $path;
			}
		}
	
		return $results;
	}

	function uploadResourceToCKAN($api, $datasetId, $isUpdate, $resourceId, $resourceUrl, $fileName, $type, $description, $pushToDataspusher, $format = null) {
		
		Logger::logMessage(($isUpdate ? "Updating " : "Uploading " ) . " resource '" . $fileName . "' on CKAN and monitoring the datapusher");
		$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'UPLOAD_CKAN', 'PENDING', 'Ajout du fichier \'' .  $fileName . '\' dans CKAN');
	
		if ($isUpdate) {
			$resource = [
				"id" => $resourceId,
				"url" => $resourceUrl,
				"name" => $fileName,
				"description" => $description,
				//TODO: Add format
				// "format" => "csv",
				"last_modified" => date('Y-m-d\TH:i:s'),
				"clear_upload" => true,
				"uuid" => uniqid()
			];

			Logger::logMessage("Keeping dictionnary backup.");
			// Get dictionnary for dataset
			$result = $api->getAllFieldsForTableParam($resourceId);
			$fields = $result["result"]["fields"];
			
			// We update the resource
			Logger::logMessage("Updating the resource.");
			$callUrl =  $this->urlCkan . "/api/action/resource_update";
			$return = $api->updateRequest($callUrl, $resource, "POST");

			$fieldsWithoutId = array();
			foreach ($fields as $field) {
				if ($field["id"] != "_id") {
					$fieldsWithoutId[] = $field;
				}
			}

			$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'UPLOAD_CKAN', 'SUCCESS', 'Le fichier \'' .  $fileName . '\' a été ajouté à CKAN');
			$api->addResourceVersion($datasetId, $resourceId, $resourceUrl);
			
			if ($type == 'csv' || $type == 'xls' || $type == 'xlsx') {
				// We monitore the datapusher
				$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'UPLOAD_DATASTORE', 'PENDING', 'Ajout des données du fichier \'' .  $fileName . '\' dans le magasin de données.');
				$datapusherResult = $this->manageDatapusher($api, null, $resourceId, $resourceUrl, $fileName, true, $pushToDataspusher);

				if ($datapusherResult[$resourceId]['status'] == 'error') {
					$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'UPLOAD_DATASTORE', 'ERROR', "Impossible d'ajouter les données du fichier \'' .  $fileName . '\' dans le magasin de données (" . $datapusherResult[$resourceId]['message'] . ")");
				}
				else if ($datapusherResult[$resourceId]['status'] == 'pending') {
					$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'UPLOAD_DATASTORE', 'PENDING', 'Les données du fichier \'' .  $fileName . '\' sont encore en train d\'être ajoutées au magasin de données. Cette opération peut durer plusieurs minutes.');
				}
				else {
					$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'UPLOAD_DATASTORE', 'SUCCESS', 'Les données du fichier \'' .  $fileName . '\' ont été ajouté dans le magasin de données.');
				}
			}
			else {
				return $this->buildResponse($resourceId, 'DATAPUSHER', $fileName, 'complete', $resourceUrl, null);
			}

			// We reupload the dictionnary
			Logger::logMessage("Reuploading dictionnary.");
			$callUrl =  $this->urlCkan . "/api/action/datastore_create";
			$data = array();
			$data["resource_id"] = $resourceId;
			$data["force"] = true;
			$data["fields"] = $fieldsWithoutId;
			$data["uuid"] = uniqid();
			$api->updateRequest($callUrl, $data, "POST");

			Logger::logMessage("TRM - Reuploading dictionnary with data " . json_encode($data));

			return $datapusherResult;
		}
		else {
			if ($format) {
				$resources = [    
					"package_id" => $datasetId,
					"url" => $resourceUrl,
					"description" => '',
					"name" => $fileName,
					"format" => $format
				];
			}
			else {
				$resources = [    
					"package_id" => $datasetId,
					"url" => $resourceUrl,
					"description" => '',
					"name" => $fileName
				];
			}
			$callUrluptres = $this->urlCkan . "/api/action/resource_create";
			$return = $api->updateRequest($callUrluptres, $resources, "POST");
			$return = json_decode($return);
			
			if ($return->success == true) {
			
				$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'UPLOAD_CKAN', 'SUCCESS', 'Le fichier \'' .  $fileName . '\' a été ajouté à CKAN');
				$resourceId = $return->result->id;
				$api->addResourceVersion($datasetId, $resourceId, $resourceUrl);
				
				if ($type == 'csv' || $type == 'xls' || $type == 'xlsx') {
					// We monitore the datapusher
					$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'UPLOAD_DATASTORE', 'PENDING', 'Ajout des données du fichier \'' .  $fileName . '\' dans le magasin de données.');
					
					$datapusherResult =  $this->manageDatapusher($api, null, $resourceId, $resourceUrl, $fileName, true, $pushToDataspusher);

					if ($datapusherResult[$resourceId]['status'] == 'error') {
						$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'UPLOAD_DATASTORE', 'ERROR', "Impossible d'ajouter les données du fichier \'' .  $fileName . '\' dans le magasin de données (" . $datapusherResult[$resourceId]['message'] . ")");
					}
					else if ($datapusherResult[$resourceId]['status'] == 'pending') {
						$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'UPLOAD_DATASTORE', 'PENDING', 'Les données du fichier \'' .  $fileName . '\' sont encore en train d\'être ajoutées au magasin de données. Cette opération peut durer plusieurs minutes.');
					}
					else {
						$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'UPLOAD_DATASTORE', 'SUCCESS', 'Les données du fichier \'' .  $fileName . '\' ont été ajouté dans le magasin de données.');
					}
				}
				else {
					return $this->buildResponse($resourceId, 'DATAPUSHER', $fileName, 'complete', $resourceUrl, null);
				}

				return $datapusherResult;
			} 
			else {
				$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'UPLOAD_CKAN', 'ERROR', "Impossible d'ajouter le fichier de resource '" . $fileName . "' (" . json_encode($return->error->message) . ")");
				throw new \Exception("Impossible d'ajouter le fichier de resource '" . $fileName . "' (" . json_encode($return->error->message) . ")");
			}
		}
	}

	/**
	 * This method call the datapusher for a specified resource ID to get the status
	 * If checkDatapusher is false, this means that the file is not push to the datastore, but we still need the status
	 * 
	 * According to the status, we do different action
	 * 	> Pending : We wait for 5 secondes and we check the status again. If the processus take longer than the constant DATAPUSHER_WAIT_TIME, we return the status 'pending'
	 *  > Error : If it is the first try, we try to push the file again in the datastore. If not we return the status 'error' and the associated message.
	 *  > Success : We return the status 'success'
	 * 
	 */
	function manageDatapusher($api, $startTime, $resourceId, $resourceUrl, $fileName, $firstTime, $pushToDataspusher) {
		//place this before any script you want to calculate time
		if ($startTime == null) {
			$startTime = microtime(true);
		}
		$currentTime = microtime(true);

		$result = array();

		if ($startTime + self::DATAPUSHER_WAIT_TIME < $currentTime) {
			Logger::logMessage("The datapusher has been running for more than " . self::DATAPUSHER_WAIT_TIME . " sec. We inform the user that the status is pending.");
			return $this->buildResponse($resourceId, 'DATAPUSHER', $fileName, 'pending', $resourceUrl, null);
		}

		if ($pushToDataspusher) {
			// Check status datapusher
			$datapusherStatus = $api->getDatapusherJobStatus($resourceId);
			$datapusherStatus = json_decode($datapusherStatus);
	
			if ($datapusherStatus->status == 'pending') {
				sleep(5);
				return $this->manageDatapusher($api, $startTime, $resourceId, $resourceUrl, $fileName, $firstTime, $pushToDataspusher);
			}
			else if ($datapusherStatus->status == 'error') {
				if ($firstTime) {
					Logger::logMessage("The datapusher had an error, we try to push the file again.");
					$api->callDatapusher($resourceId);
					return $this->manageDatapusher($api, $startTime, $resourceId, $resourceUrl, $fileName, false, $pushToDataspusher);
				}
				else {
					Logger::logMessage("The datapusher had an error again (" . json_encode($datapusherStatus) . ").");
					return $this->buildResponse($resourceId, 'DATAPUSHER', $fileName, 'error', null, $datapusherStatus->error->message);
				}
			}
			else if ($datapusherStatus->status == 'complete') {
				Logger::logMessage("The datapusher has inserted the file.");
				return $this->buildResponse($resourceId, 'DATAPUSHER', $fileName, 'complete', $resourceUrl, null);
			}
			else if ($datapusherStatus->status == null) {
				Logger::logMessage("An error occured during status's checking, we try to check the status again.");
				return $this->manageDatapusher($api, $startTime, $resourceId, $resourceUrl, $fileName, $firstTime, $pushToDataspusher);
			}
			else {
				throw new \Exception("Le datapusher a renvoyé un status inconnu '" . json_encode($datapusherStatus->status) . "', veuillez relancer l'insertion dans le datapusher.");
			}
		}
		else {
			Logger::logMessage("The datapusher do not need to push the file.");
			return $this->buildResponse($resourceId, 'DATAPUSHER', $fileName, 'complete', $resourceUrl, null);
		}
	}

	function deleteResource($resourceId) {
		$api = new Api;
		
		$delRes = [
			"id" => $resourceId,
			"force" => "True",
		];

		$callUrl = $this->urlCkan . "/api/action/resource_delete";
		$result = $api->updateRequest($callUrl, $delRes, "POST");

		if ($result->success != true) {
			throw new \Exception("La ressource n'a pas pu être supprimé ' (" . $result->error->message . ").");
		}
	}

	/**
	 * This method call a service to validate the data from a CSV
	 */
	function validateData($validataUrl) {
		$optionst = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => array(
				'Content-type:application/json',
				'Content-Length: ' . strlen($jsonData),
				'Authorization:  ' . $cle,
			),
		);

		$curlValid = curl_init($validataUrl);
		curl_setopt_array($curlValid, $optionst);
		
		$valid = curl_exec($curlValid);
		curl_close($curlValid);
		return json_decode($valid, true);
	}

	/**
	 * We need to modify and clean dataset name from the user defined title
	 */
	function defineDatasetName($title) {
		$label = $title;
		if(strlen($label) > 95) {
			$label = substr($label, 0, 95);
		}
		return $this->nettoyage($label);
	}

	/**
	 * This function save an uploaded image to be use as an icon
	 */
	function definePicto($imgPicto, $imgBack) {
		if (isset($imgPicto[0]) && !empty($imgPicto[0])) {

			$file = File::load($imgPicto[0]);
			$file->setPermanent();
			$file->save();
			$url_t = parse_url($file->createFileUrl(FALSE));
			$url_pict = $url_t["path"];

			$url_pict = explode("/", $url_pict);
			$url_pict = explode(".", $url_pict[(count($url_pict) - 1)]);
			$url_pict = "/sites/default/files/theme_logo/".$url_pict[0].".svg";

			return $url_pict;

		} 
		else {
			return "d4c-" . $imgBack;
		}
	}

	function defineBackground($imgBackground) {
		if (isset($imgBackground[0]) && !empty($imgBackground[0])) {

			$file = File::load($imgBackground[0]);
			$file->setPermanent();
			$file->save();
			$url_t = parse_url($file->createFileUrl(FALSE));
			$url_pict = $url_t["path"];

			return $url_pict;
		}
		return null;
	}

	function defineWidget($widget) {
		$widget_html='';
		$hasWidget = false;
        foreach($widget as $key =>$val){
            if ($val[name] != '' && $val[widget] != ''){
				$off = '';  
				if($val[offWidjet] == 1) {
					$off = '<.off.>'; 
				}

				$hasWidget = true;
				$widget_html = $widget_html .$val[name].'<.info.>'.$val[description].'<.info.> '.$val[widget].' '.$off.'<.explode.>';            
			} 
        }
        
        
        $widget = $hasWidget ? substr($widget_html, 0, -11) : null;
		return $widget;
	}

	function defineAnalyse($analyseDefault) {
        if (explode("=", $analyseDefault)[0] != 'dataChart') {
            $analyseDefault =  explode("&", $analyseDefault);
               
            foreach($analyseDefault as &$anal){
                if(explode("=", $anal)[0]=='dataChart'){
                    $analyseDefault_f = $anal;
                    break;
                }
                else{
                    $analyseDefault_f = "";
                }
			}  
            
			$analyseDefault =  explode('"', $analyseDefault_f);
			return $analyseDefault[0];
		}
		return $analyseDefault;
	}

	function defineLinkDatasets($linkDatasets) {
		$linkDatasetsStr = '';
		foreach ($linkDatasets as $key => &$val) {
			if ($val[dt] == 1) {
				$linkDatasetsStr = $linkDatasetsStr . ';' . $key;
			}
		}
		
		return substr($linkDatasetsStr, 1);
	}

	function defineTags($tags) {
		$tagsData = array();
		if ($tags == null || $tags == '') {
			$tagsData = [];
		} 
		else {
			$tags = explode(",", $tags);

			for ($j = 0; $j < count($tags); $j++) {
				$tagsData[$j] = ["vocabulary_id" => null, "state" => "active", "display_name" => $tags[$j], "name" => $tags[$j]];
			}
		}
		return $tagsData;
	}

	function defineSecurity($userId, $users) {
		$userlist = array();
		foreach($users as $user){
			$username = $user->get('name')->value;
			$uid = $user->get('uid')->value;
			$uroles = $user->getRoles();
			if($username != "" && (in_array("administrator", $uroles) || $uid == 1)){
				$userlist[] = "*".$uid."*";
			}
		}
		if ($userId) {
			$userlist[] = $userId;
		}
		$userlist = array_unique($userlist);
		if(count($userlist) == 1){
			$userlist = array($userlist);
		}
		return array("roles" => array("administrator"), "users" => $userlist);
	}
	
	function defineExtras($extras, $picto, $imgBackground, $removeBackground, $linkDatasets, $theme, $themeLabel,
			$selectedTypeMap, $selectedOverlays, $dont_visualize_tab, $widgets, $visu, 
			$dateDataset, $disableFieldsEmpty, $analyseDefault, $security, $producer=null, $source=null, $donnees_source=null, 
			$mention_legales=null, $frequence=null, $displayVersionning=false, $territory=null,
			$contactMail=null, $bbox_east_longb=null, $bbox_north_lat=null, $bbox_south_lat=null, $bbox_west_long=null, $spatial=null) {
		if ($extras == null) {
			$extras = array();
		}

		$hasPicto = false;
		$hasBackground = false;
		$hasLinkDatasets = false;
		$hasTheme = false;
		$hasThemeLabel = false;
		$hasTypeMap = false;
		$hasOverlays = false;
		$hasVisualizeTab = false;
		$hasFTP = false;
		$hasWidgets = false;
		$hasVisu = false;
		$hasDate = false;
		$hasDisableFieldsEmpty = false;
		$hasSecurity = false;
		$hasProducer = false;
		$hasFrequence = false;
		$hasSource = false;
		$hasDonneesSource = false;
		$hasMentionLegales = false;
		$hasDisplayVersionning = false;
		$hasTerritory = false;
		$hasContactMail = false;
		$hasBboxEastLongb = false;
		$hasBboxNorthLat = false;
		$hasBboxSouthLat = false;
		$hasBboxWestLong = false;
		$hasSpatial = false;
		
		if ($extras != null && count($extras) > 0) {
	
			for ($index = 0; $index < count($extras); $index++) {
				if ($extras[$index]['key'] == 'Picto') {
					$hasPicto = true;
					$extras[$index]['value'] = $picto;
				}
				
				if ($extras[$index]['key'] == 'img_backgr') {
					if ($removeBackground) {
						array_splice($extras, $index, 1);
					}
					else {
						$hasBackground = true;
						if ($imgBackground != null) {
							$extras[$index]['value'] = $imgBackground;
						}
					}
				}
				
				if ($extras[$index]['key'] == 'LinkedDataSet') {
					$hasLinkDatasets = true;
					$extras[$index]['value'] = $linkDatasets;
				}
												
				if ($extras[$index]['key'] == 'theme') {
					$hasTheme = true;
					$extras[$index]['value'] = $theme;
				}
				
				if ($extras[$index]['key'] == 'label_theme') {
					$hasThemeLabel = true;
					$extras[$index]['value'] = $themeLabel;
				}
					
				if ($extras[$index]['key'] == 'type_map') {
					$hasTypeMap = true;
					$extras[$index]['value'] = $selectedTypeMap;
				}
				
				if ($extras[$index]['key'] == 'overlays') {
					if ($selectedOverlays == null || $selectedOverlays == ""){
						array_splice($extras, $index, 1);
					}
					else {
						$hasOverlays = true;
						$extras[$index]['value'] = $selectedOverlays;
					}
				}
				
				if ($extras[$index]['key'] == 'dont_visualize_tab') {
					$hasVisualizeTab = true;
					$extras[$index]['value'] = $dont_visualize_tab;
				}
				
				if ($extras[$index]['key'] == 'FTP_API') {
					$hasFTP = true;
				}

				if ($extras[$index]['key'] == 'widgets') {
					$hasWidgets = true;
					$extras[$index]['value'] = $widgets;
				}
				
				if ($extras[$index]['key'] == 'default_visu') {
					$hasVisu = true;
					$extras[$index]['value'] = $visu;
				}
				
				if ($extras[$index]['key'] == 'date_dataset') {
					$hasDate = true;
					$extras[$index]['value'] = $dateDataset;
				}

				//producer
				if ($extras[$index]['key'] == 'producer') {
					$hasProducer = true;
					$extras[$index]['value'] = $producer;
				}

				//frequence
				if ($extras[$index]['key'] == 'frequence') {
					$hasFrequence = true;
					$extras[$index]['value'] = $frequence;
				}

				// source
				if ($extras[$index]['key'] == 'source') {
					$hasSource = true;
					$extras[$index]['value'] = $source;
				}

				// donnees source
				if ($extras[$index]['key'] == 'donnees_source') {
					$hasDonneesSource = true;
					$extras[$index]['value'] = $donnees_source;
				}

				// mention legales
				if ($extras[$index]['key'] == 'mention_legales') {
					$hasMentionLegales = true;
					$extras[$index]['value'] = $mention_legales;
				}

				// territory
				if ($extras[$index]['key'] == 'territory') {
					$hasTerritory = true;
					$extras[$index]['value'] = $territory;
				}

				// Contact mail
				if ($extras[$index]['key'] == 'contact_mail') {
					$hasContactMail = true;
					$extras[$index]['value'] = $contactMail;
				}

				// BB East long
				if ($extras[$index]['key'] == 'bbox-east-long') {
					$hasBboxEastLongb = true;
					$extras[$index]['value'] = $bbox_east_longb;
				}

				// BB North lat
				if ($extras[$index]['key'] == 'bbox-north-lat') {
					$hasBboxNorthLat = true;
					$extras[$index]['value'] = $bbox_north_lat;
				}

				// BB South lat
				if ($extras[$index]['key'] == 'bbox-south-lat') {
					$hasBboxSouthLat = true;
					$extras[$index]['value'] = $bbox_south_lat;
				}

				// BB West long
				if ($extras[$index]['key'] == 'bbox-west-long') {
					$hasBboxWestLong = true;
					$extras[$index]['value'] = $bbox_west_long;
				}

				// Spatial
				if ($extras[$index]['key'] == 'spatial') {
					$hasSpatial = true;
					$extras[$index]['value'] = $spatial;
				}
	
				if ($extras[$index]['key'] == 'disable_fields_empty') {
					$hasDisableFieldsEmpty  = true;
					$extras[$index]['value'] = $disableFieldsEmpty;
				}

				if ($extras[$index]['key'] == 'edition_security') {
					$hasSecurity = true;
				}
				
				if ($extras[$index]['key'] == 'analyse_default') {
					$hasAnalyse = true;
					$extras[$index]['value'] = $analyseDefault;
				}
	
				if ($extras[$index]['key'] == 'display_versionning') {
					$hasDisplayVersionning  = true;
					$extras[$index]['value'] = $displayVersionning;
				}
			}
		}

		if ($hasPicto == false) {
			$extras[count($extras)]['key'] = 'Picto';
			$extras[(count($extras) - 1)]['value'] = $picto;
		}

		if ($hasBackground == false && $imgBackground && $imgBackground != null && $imgBackground != '') {
			$extras[count($extras)]['key'] = 'img_backgr';
			$extras[(count($extras) - 1)]['value'] = $imgBackground;
		}
			
		if ($hasLinkDatasets == false) {		
			$extras[count($extras)]['key'] = 'LinkedDataSet';
			$extras[(count($extras) - 1)]['value'] = $linkDatasets;
		}

		if ($hasTheme == false) {
			$extras[count($extras)]['key'] = 'theme';
			$extras[(count($extras) - 1)]['value'] = $theme;
		}

		if ($hasThemeLabel == false) {
			$extras[count($extras)]['key'] = 'label_theme';
			$extras[(count($extras) - 1)]['value'] = $themeLabel;
		}

		if ($hasTypeMap == false && $selectedTypeMap != null && $selectedTypeMap != '') {
			$extras[count($extras)]['key'] = 'type_map';
			$extras[(count($extras) - 1)]['value'] = $selectedTypeMap;
		}

		if ($hasOverlays == false && $selectedOverlays != null && $selectedOverlays != ""){
			$extras[count($extras)]['key'] = 'overlays';
			$extras[(count($extras) - 1)]['value'] = $selectedOverlays;
		}

		if ($hasVisualizeTab == false) {
			$extras[count($extras)]['key'] = 'dont_visualize_tab';
			$extras[(count($extras) - 1)]['value'] = $dont_visualize_tab;
		}

		if ($hasFTP == false) {
			$extras[count($extras)]['key'] = 'FTP_API';
			$extras[(count($extras) - 1)]['value'] = 'FTP';
		}

		if ($hasWidgets == false && $widgets != null && $widgets != '') {
			$extras[count($extras)]['key'] = 'widgets';
			$extras[(count($extras) - 1)]['value'] = $widgets;
		}

		if ($hasVisu == false) {
			$extras[count($extras)]['key'] = 'default_visu';
			$extras[(count($extras) - 1)]['value'] = $visu;
		}

		if ($hasDate == false) {
			$extras[count($extras)]['key'] = 'date_dataset';
			$extras[(count($extras) - 1)]['value'] = $dateDataset;
		}

		if ($hasProducer == false) {
			$extras[count($extras)]['key'] = 'producer';
			$extras[(count($extras) - 1)]['value'] = $producer;
		}

		if ($hasFrequence == false) {
			$extras[count($extras)]['key'] = 'frequence';
			$extras[(count($extras) - 1)]['value'] = $frequence;
		}

		if ($hasSource == false) {
			$extras[count($extras)]['key'] = 'source';
			$extras[(count($extras) - 1)]['value'] = $source;
		}

		if ($hasDonneesSource == false) {
			$extras[count($extras)]['key'] = 'donnees_source';
			$extras[(count($extras) - 1)]['value'] = $donnees_source;
		}

		if ($hasMentionLegales == false) {
			$extras[count($extras)]['key'] = 'mention_legales';
			$extras[(count($extras) - 1)]['value'] = $mention_legales;
		}

		if ($hasTerritory == false) {
			$extras[count($extras)]['key'] = 'territory';
			$extras[(count($extras) - 1)]['value'] = $territory;
		}

		if ($hasContactMail == false) {
			$extras[count($extras)]['key'] = 'contact_mail';
			$extras[(count($extras) - 1)]['value'] = $contactMail;
		}

		if ($hasBboxEastLongb == false) {
			$extras[count($extras)]['key'] = 'bbox-east-long';
			$extras[(count($extras) - 1)]['value'] = $bbox_east_longb;
		}

		if ($hasBboxNorthLat == false) {
			$extras[count($extras)]['key'] = 'bbox-north-lat';
			$extras[(count($extras) - 1)]['value'] = $bbox_north_lat;
		}

		if ($hasBboxSouthLat == false) {
			$extras[count($extras)]['key'] = 'bbox-south-lat';
			$extras[(count($extras) - 1)]['value'] = $bbox_south_lat;
		}

		if ($hasBboxWestLong == false) {
			$extras[count($extras)]['key'] = 'bbox-west-long';
			$extras[(count($extras) - 1)]['value'] = $bbox_west_long;
		}

		if ($hasSpatial == false) {
			$extras[count($extras)]['key'] = 'spatial';
			$extras[(count($extras) - 1)]['value'] = $spatial;
		}

		if ($hasDisableFieldsEmpty  == false) {
			$extras[count($extras)]['key'] = 'disable_fields_empty';
			$extras[(count($extras) - 1)]['value'] = $disableFieldsEmpty;
		}
		
		if ($hasAnalyse == false && $analyseDefault != '') {
			$extras[count($extras)]['key'] = 'analyse_default';
			$extras[(count($extras) - 1)]['value'] = $analyseDefault; 
		}

		if ($hasSecurity == false) {
			$extras[count($extras)]['key'] = 'edition_security';
			$extras[(count($extras) - 1)]['value'] = json_encode($security);
		}

		if ($hasDisplayVersionning == false) {
			$extras[count($extras)]['key'] = 'display_versionning';
			$extras[(count($extras) - 1)]['value'] = $displayVersionning;
		}

		return $extras;
	}

	/**
	 * Extract the format of a file based on the filename
	 * and assign a type to help managing the file afterward
	 * 
	 * For now files can be of type jpg, jpeg, gif, png, txt, doc, xls, pdf, ppt, pps, odt, ods, odp, csv, json, xls, xlsx, geojson, zip
	 * 
	 */
	function extractFormat($filePath) {
		$format = pathinfo($filePath, PATHINFO_EXTENSION);
		if (strcasecmp($format , 'jpg') == 0) {
			return 'jpg';
		}
		else if (strcasecmp($format , 'jpeg') == 0) {
			return 'jpeg';
		}
		else if (strcasecmp($format , 'gif') == 0) {
			return 'gif';
		}
		else if (strcasecmp($format , 'png') == 0) {
			return 'png';
		}
		else if (strcasecmp($format , 'txt') == 0) {
			return 'txt';
		}
		else if (strcasecmp($format , 'doc') == 0) {
			return 'doc';
		}
		else if (strcasecmp($format , 'xls') == 0) {
			return 'xls';
		}
		else if (strcasecmp($format , 'pdf') == 0) {
			return 'pdf';
		}
		else if (strcasecmp($format , 'ppt') == 0) {
			return 'ppt';
		}
		else if (strcasecmp($format , 'pps') == 0) {
			return 'pps';
		}
		else if (strcasecmp($format , 'odt') == 0) {
			return 'odt';
		}
		else if (strcasecmp($format , 'ods') == 0) {
			return 'ods';
		}
		else if (strcasecmp($format , 'odp') == 0) {
			return 'odp';
		}
		else if (strcasecmp($format , 'csv') == 0) {
			return 'csv';
		}
		else if (strcasecmp($format , 'json') == 0) {
			return 'json';
		}
		else if (strcasecmp($format , 'geojson') == 0) {
			return 'geojson';
		}
		else if (strcasecmp($format , 'xls') == 0) {
			return 'xls';
		}
		else if (strcasecmp($format , 'xlsx') == 0) {
			return 'xlsx';
		}
		else if (strcasecmp($format , 'zip') == 0) {
			return 'zip';
		}
		return $format;
	}
	
	function buildCSVFromGeojson($json) {
		if($json == null || count($json) == 0){
			return "";
		}
		
		Logger::logMessage("Creating CSV from GeoJson");
		
		// If passed a string, turn it into an array
		if (is_array($json) === false) {
			//$json = utf8_encode($json);
			//$json = Export::convert_bad_characters($json);
			$json = json_decode($json, true, 512, JSON_UNESCAPED_UNICODE);
			//$json = json_decode($json, true);
		}
		
		if($json["type"] != "FeatureCollection"){
			return "";
		}
		//construction du csv
		$cols = array();
		$colNames = array();
		$data_csv = array();

		//Previously we were getting only the columns for the first feature but we could miss a lot of informations
		//We now go through all features but we have to check if it not too much time consuming
		$hasShapes = false;
		$index = 0;
		foreach($json["features"] as $feat) {
			foreach($feat["properties"] as $key => $val){

				if ($index == 1) {
					$cols[] = "geo_point_2d";
					$colNames[] = "geo_point_2d";
				}

				//We check if the key already exist
				if (!in_array($key, $cols)) {
					Logger::logMessage("Found column " . $key);

					$cols[] = $key;
					
					$label = $this->clearGeoProperties($key, $index);
					$label = $this->nettoyage($label);
					$colNames[] = $label;
					$index++;
				}
			}
			if ($feat["geometry"]["type"] != "Point") {
				$hasShapes = true;
			}
		}
		if ($hasShapes) {
			$cols[] = "geo_shape";
			$colNames[] = "geo_shape";
		}
		
		$rows = array();
		$colsTypes = array();
		foreach($json["features"] as $feat){
			$row = array();
			foreach($cols as $col){
				if($col == "geo_point_2d"){
					if ($hasShapes) {
						$str = json_encode($feat["geometry"]);
						preg_match('/\[([-]?[\d|.]+),([-]?[\d|.]+)/i', $str, $match);
						$coord = '"' . $match[2] . "," . $match[1] . '"';
						$row[] = $coord;
					}
					else {
						$str = json_encode($feat["geometry"]["coordinates"]);
						preg_match('/\[([-]?[\d|.]+),([-]?[\d|.]+)/i', $str, $match);
						$val = '"'.$match[2] .",". $match[1].'"';
						$row[] = $val;
					}
				}
				else if($col == "geo_shape") {
					$str = json_encode($feat["geometry"]);
					preg_match('/\[([-]?[\d|.]+),([-]?[\d|.]+)/i', $str, $match);
					$coord = '"' . $match[2] . "," . $match[1] . '"';

					//We replace " by "" to escape them
					$str = str_replace('"', "\"\"", $str);
					$row[] = '"' . $str . '"';
				}
				else if($col == "coordinates"){
					continue;
				}	
				else {
					$value = $feat["properties"][$col];
					if((isset($colsTypes[$col]) && $colsTypes[$col] == "text") || !$this->isNumericColumn($json,$col)){
						//We replace " by "" to escape them
						$value = str_replace('"', "\"\"", $value);

						$row[] = '"' . $value . '"';
						if(!isset($colsTypes[$col])){
							$colsTypes[$col] = "text";
						}
					} else {
						$row[] = $value;
						if(!isset($colsTypes[$col])){
							$colsTypes[$col] = "float";
						}
					}
				}
			}
			
			$rows[] = $row;
		}
		
		foreach($rows as &$row){
			if(count($row) < count($cols)){
				$row = array_pad($row, count($cols), "");
			}
			$row = implode($row, ",");
		}
		
		$data_csv = strtolower(implode($colNames, ","));
		array_unshift($rows, $data_csv);
		error_log("count ". (count($rows)));
		$res = implode($rows, "\n");
		return $res;
	}
	
	function isNumericColumn($json, $colName) {
		for($i=0; $i< 100; $i++){
			$val = $json["features"][$i]["properties"][$col];
			if( !is_numeric ($val)){
				return false;
			} 
		}
		return true;
	}

	function clearGeoProperties($colName, $index) {
		if(preg_match("/geo_point|coordin|coordon|geopoint|geoPoint|pav_positiont2d|geoloc|wgs84|equgpsy_x|geoban|codegeo|geometry/i",$colName)){
			return "colonne_renomme_" . $index;
		}
		else {
			return $colName;
		}
	}
    
    function saveData($newData, $data) {
        $coll = $data[0];
        
        $api = new Api;
		$callUrlNewData = $this->urlCkan . "/api/action/package_create";
		$return = $api->updateRequest($callUrlNewData, $newData, "POST");   
		$resnew = json_decode($return);

		$idNewData = $resnew->result->id;

		//TODO Rework this
		if ($resnew->success == true) {
			$idNewData = $resnew->result->id;
		} 
		else if($resnew->error->name[0]=='Cette URL est déjà utilisée.'){
			$coll++;
			
			if($coll==1){
				$newData[name]=$newData[name].'_'.$coll;
				$newData[title]=$newData[title].' '.$coll;
				$idNewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData));
				$idNewData = $idNewData[1];    
			}
			else if($coll>10){
				$newData[name]=substr($newData[name],0, -3);
				$newData[name]=$newData[name].'_'.$coll;
				$newData[title]=substr($newData[title],0, -3);
				$newData[title]=$newData[title].' '.$coll;
				$idNewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData));
				$idNewData = $idNewData[1];
			}
			else if($coll>100){
				$newData[name]=substr($newData[name],0, -4);
				$newData[name]=$newData[name].'_'.$coll;
				$newData[title]=substr($newData[title],0, -4);
				$newData[title]=$newData[title].' '.$coll;
				$idNewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData));
				$idNewData = $idNewData[1];    
			}
			else if($coll>1000){
				$newData[name]=substr($newData[name],0, -5);
				$newData[name]=$newData[name].'_'.$coll;
				$newData[title]=substr($newData[title],0, -5);
				$newData[title]=$newData[title].' '.$coll;
				$idNewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData));
				$idNewData = $idNewData[1];    
			}
			else if($coll>10000){
				$newData[name]=substr($newData[name],0, -6);
				$newData[name]=$newData[name].'_'.$coll;
				$newData[title]=substr($newData[title],0, -6);
				$newData[title]=$newData[title].' '.$coll;
				$idNewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData));
				$idNewData = $idNewData[1];
			}
			else{
				$newData[name]=substr($newData[name],0, -2);
				$newData[name]=$newData[name].'_'.$coll;
				$newData[title]=substr($newData[title],0, -2);
				$newData[title]=$newData[title].' '.$coll;
				$idNewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData));
				$idNewData = $idNewData[1];
			}
		}
		else {
			Logger::logMessage("Impossible de créer un nouveau jeu de données (" . json_encode($resnew) . ")");
			if ($resnew->error->message) {
				throw new \Exception("Impossible de créer un nouveau jeu de données (" . json_encode($resnew->error->message) . ")");
			}
			else {
				throw new \Exception("Impossible de créer un nouveau jeu de données (" . json_encode($resnew->error) . ")");
			}
		}

        return array('0'=>$coll, '1'=>$idNewData);
    }



	function deleteDataset($datasetId) {
		$callUrl = $this->urlCkan . "/api/action/package_delete";
            
		$delDataset = [
			"id" => $datasetId,
		];

        $api = new Api;
		$response = $api->updateRequest($callUrl, $delDataset, "POST");
		
		$response = json_decode($response, true);
		if ($response[success] == true) {
			$harvestManager = new HarvestManager;
			$harvestManager->deleteHarvest($datasetId);
			return true;
		}
		else {
			throw new \Exception('Impossible de supprimer le dataset (' . $response . ' is not supported.');
		}
	}
	
    function nettoyage( $str, $charset='utf-8') {
		if (!mb_detect_encoding($str, 'UTF-8', true)) {
			$str = iconv("UTF-8", "Windows-1252//TRANSLIT", $str);
		}
		
		//We remove whitespaces at the beggining and end of the label
		$str = trim($str);
		
		$unwanted_array = array(    'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
                            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
                            'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
                            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
                            'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
		$str = strtr( $str, $unwanted_array );
		
		$str = str_replace("?", "", $str);
		$str = str_replace("`", "_", $str);
		$str = str_replace("'", "_", $str);
		$str = str_replace("-", "_", $str);
		$str = str_replace(" ", "_", $str);
		$str = str_replace(",", "", $str);
		$str = str_replace("%", "", $str);
		$str = str_replace("(", "", $str);
		$str = str_replace(")", "", $str);
		$str = str_replace("*", "", $str);
		$str = str_replace("!", "", $str);
		$str = str_replace("@", "", $str);
		$str = str_replace("#", "", $str);
		$str = str_replace("$", "", $str);
		$str = str_replace("^", "", $str);
		$str = str_replace("&", "", $str);
		$str = str_replace("+", "", $str);
		$str = str_replace(":", "", $str);
		$str = str_replace(">", "", $str);
		$str = str_replace("<", "", $str);
		$str = str_replace('\'', "_", $str);
		$str = str_replace("/", "_", $str);
		$str = str_replace("|", "_", $str);
		$str = str_replace("[", "", $str);
		$str = str_replace("]", "", $str);
		$str = strtolower($str);
		$str = preg_replace( '#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str );
		$str = preg_replace( '#&([A-za-z]{2})(?:lig);#', '\1', $str );
		$str = preg_replace( '#&[^;]+;#', '', $str );
		$str = str_replace("-", "_", $str);
		return $str;
	}

    function nettoyage2( $str, $charset='utf-8' ) {
		$str = utf8_decode($str);
		$str = utf8_decode($str);

		$str = str_replace("?", "", $str);
		$str = str_replace("`", "_", $str);
		$str = str_replace("'", "_", $str);
		$str = str_replace("-", "_", $str);
		$str = str_replace(" ", "_", $str);
		$str = str_replace("%", "1", $str);
		$str = str_replace("(", "1", $str);
		$str = str_replace(")", "1", $str);
		$str = str_replace("*", "1", $str);
		$str = str_replace("!", "1", $str);
		$str = str_replace("@", "1", $str);
		$str = str_replace("#", "1", $str);
		$str = str_replace("$", "1", $str);
		$str = str_replace("^", "1", $str);
		$str = str_replace("&", "1", $str);
		$str = str_replace("+", "1", $str);
		$str = str_replace(":", "1", $str);
		$str = str_replace(">", "1", $str);
		$str = str_replace("<", "1", $str);
		$str = str_replace("|", "_", $str);
		$str = strtolower($str);
		$str = preg_replace( '#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str );
		$str = preg_replace( '#&([A-za-z]{2})(?:lig);#', '\1', $str );
		$str = preg_replace( '#&[^;]+;#', '', $str );
		$str = str_replace("-", "_", $str);    
		return $str;
	}
	
	function lettersToNumber($letters){
		$alphabet = range('A', 'Z');
		$number = 0;

		foreach(str_split(strrev($letters)) as $key=>$char){
			$number = $number + (array_search($char,$alphabet)+1)*pow(count($alphabet),$key);
		}
		return $number;
	}
	
	function numberToLetters($number) {
		$alphabet = range('A', 'Z');

		$count = count($alphabet);
        if ($number <= $count) {
            return $alphabet[$number - 1];
        }
        $alpha = '';
        while ($number > 0) {
            $modulo = ($number - 1) % $count;
            $alpha  = $alphabet[$modulo] . $alpha;
            $number = floor((($number - $modulo) / $count));
        }
        return $alpha;
	}



}
