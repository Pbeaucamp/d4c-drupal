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
use \JsonMachine\JsonMachine;

class ResourceManager {

	const ROOT = '/home/user-client/drupal-d4c/web/';
	/**
	 * This constant represent the maximum time the user can wait for the datapusher to load in the datastore (success or error)
	 * In sec
	 */
	const DATAPUSHER_WAIT_TIME = 120;

	function __construct() {
		$this->config = include(__DIR__ . "/../../config.php");
		$this->urlCkan = $this->config->ckan->url;
		$this->protocol = isset($this->config->client->protocol) ? $this->config->client->protocol . '://' : 'https://';
		$this->host = $this->config->client->host;
		$this->port = isset($this->config->client->port) ? ':' . $this->config->client->port : '';
	}

	function getRoutingPrefix($includePreSlash) {
		if ($includePreSlash) {
			return isset($this->config->client->routing_prefix) ? $this->config->client->routing_prefix . '/' : '/';
		}
		else {
			return isset($this->config->client->routing_prefix) ? substr($this->config->client->routing_prefix, 1)  . '/' : '';
		}
	}

	function updateDatabaseStatus($isNew, $uniqId, $datasetId, $action, $status, $message) {
		$api = new Api;
		$api->updateDatabaseStatus($isNew, $uniqId, $datasetId, 'DATASET', 'MANAGE_DATASET', $action, $status, $message);
	}

	function createDataset($uniqId, $datasetName, $title, $description, $licence, $organization, $isPrivate, $tags, $extras, $source = null) {
		Logger::logMessage("Create new dataset with name '" . $datasetName . "'");
		$this->updateDatabaseStatus(true, $uniqId, '', 'CREATE_DATASET', 'PENDING', 'Création du jeu de données \'' . $datasetName . '\'');

		//We update the description if empty or equals to default.description
		if (isset($description) && strpos($description, 'default.description') !== false) {
			$description = "";
		}
	
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
			"url" => ($source != null ? $source : $urlRes),
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
		
		$coll = array('0'=>'0', '1'=>'');
			
		$datasetId = $this->saveData($newData, $coll);
		$datasetId = $datasetId[1];

		Logger::logMessage("New dataset has been saved with id '" . $datasetId . "'");
		$this->updateDatabaseStatus(false, $uniqId, $datasetId, 'CREATE_DATASET', 'SUCCESS', 'Le jeu de données \'' . $datasetName . '\' a été créé');
		return $datasetId;
	}

	function updateDataset($uniqId, $datasetId, $datasetToUpdate, $datasetName, $title, $description, $licence, $organization, $isPrivate, $tags, $extras, $source = null) {
		Logger::logMessage("Updating dataset '" . $datasetName . "' with id = " . $datasetId);
		$this->updateDatabaseStatus(true, $uniqId, $datasetId, 'UPDATE_DATASET', 'PENDING', 'Mise à jour du jeu de données \'' . $datasetName . '\'');
		
		$datasetToUpdate[title] = $title;
		$datasetToUpdate[notes] = $description;
		$datasetToUpdate[license_id] = $licence;
		$datasetToUpdate['private'] = $isPrivate;
		$datasetToUpdate[extras] = $extras;
		$datasetToUpdate["tags"] = $tags;
		if ($source != null) {
			$datasetToUpdate[url] = $source;
		}

		$api = new Api;
        $callUrl = $this->urlCkan . "api/action/package_update";
		$result = $api->updateRequest($callUrl, $datasetToUpdate, "POST");
		$result = json_decode($result);
		if ($result->success == true) {
			$currentOrganization = $datasetToUpdate[organization][id];
			Logger::logMessage("Comparing current organization '" . $currentOrganization . "' with selected organization '" . $organization . "'");

			if ($currentOrganization != $organization) {
				Logger::logMessage("Updating organization.");
			
				$callUrl = $this->urlCkan . "api/action/package_owner_org_update";
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

	function manageFileWithPath($datasetId, $generateColumns, $isUpdate, $resourceId, $resourceUrl, $description, $encoding, $unzipZip = false, $fromPackage = false, $transformFile = true, $customName = null, $moveFileToDatasetFolder = true) {
		$results = array();

		//Managing file (filepath and filename)
		$fileName = parse_url($resourceUrl);
		$datasetFolder = $this->generateDatasetFolder($datasetId);

		$host = isset($this->host) ? $this->host : $fileName[host];
		$fileName = $fileName[path];
		$filePath = $fileName;

		$fileName = $this->cleanFileName($fileName);
		Logger::logMessage("Manage file with name '" . $fileName . "' and path '" . $filePath . "'");

		if ($moveFileToDatasetFolder) {
			$newPath = $this->getRoutingPrefix(true) . 'sites/default/files/dataset/' . $datasetFolder . '/' . $fileName;
		}
		else {
			$newPath = urldecode($this->nettoyage2($filePath));
		}
		rename(self::ROOT . urldecode($filePath), self::ROOT . $newPath); 
		$filePath = $newPath;
		Logger::logMessage("File has been renamed to " . $filePath);

		$resourceUrl = $this->protocol . $host . $this->port . $filePath;
		Logger::logMessage("File resource " . $resourceUrl);

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

			// We try to detect the delimiter and we return false if it is not found
			$csvFile = self::ROOT . $filePath;

			$delimiter = $this->detectDelimiter($csvFile);
			Logger::logMessage("Found delimiter " . $delimiter);
			if ($delimiter != false) {
				$existingCols = array();

				//Cleaning column name to insert in CKAN and D4C
				if (($handle = fopen($csvFile, "r")) !== FALSE) {

					$tmpFile = '/tmp/test.csv';
					$fp = fopen($tmpFile, 'w');

					$firstRow = true;
					$referenceNumberColumn = -1;
					while (($data = fgetcsv($handle, 2000, $delimiter)) !== FALSE) {
						$num = count($data);

						//We don't write the line if there is only one column and the reference number of column is greater than 1
						if ($num <= 1 && $referenceNumberColumn > 1) {
							Logger::logMessage("Skipping line with content '" . json_encode($data) . "'");
							continue;
						}

						if ($firstRow) {
							$referenceNumberColumn = $num;

							for ($c=0; $c < $num; $c++) {
								$label = $data[$c];
	
								$label = $this->cleanColumnName($label);
								if ($label == '') {
									$label = 'unknown';
								}
								if(in_array($label, $existingCols)) {
									$label = $label . "_" . $c;
								}
								$existingCols[] = $label;
							}
	
							fputcsv($fp, $existingCols);
						}
						else {
							fputcsv($fp, $data);
						}
						$firstRow = false;
					}
					fclose($handle);
					fclose($fp);

					//We need to replace the file at the end
					rename($tmpFile, $csvFile);
				}
			}
			else {
				Logger::logMessage("Delimiter has not been found.");
			}


			// There is a hard cap for cell at 32767
			// It ends up truncating JSON value (for exemple)
			// We disable it for now and we'll replace it with another method if needed

			//if files > 50MB we don't do the treatments.
			// if ($transformFile && $filesize < 50000000) {
			// 	$reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
			// 	if ($encoding) {
			// 		Logger::logMessage("Setting encoding to " . $encoding . "\r\n");
			// 		$reader->setInputEncoding($encoding);
			// 	}
			// 	$spreadsheet = $reader->load(self::ROOT . $filePath);
			// 	$highestRow = $spreadsheet->getActiveSheet()->getHighestRow(); // e.g. 10
			// 	$highestColumn = $spreadsheet->getActiveSheet()->getHighestColumn(); // e.g 'F'

			// 	//We have an issue with number format. This line transform coordinate and it's not good. We comment it for now
			// 	//Maybe we have to do the same for XLS, XLSX
			// 	//$spreadsheet->getActiveSheet()->getStyle('A1:' . $highestColumn . $highestRow)->getNumberFormat()->setFormatCode('###.##');

			// 	$existingCols = array();

			// 	// TODO: We should save real column from the file and put it in the dictionnary
					
			// 	if($generateColumns) {
			// 		$spreadsheet->getActiveSheet()->insertNewRowBefore(1, 1);
			// 	}

			// 	$nbColumns = $this->lettersToNumber($highestColumn);
			// 	for($i=1; $i<= $nbColumns; $i++) {
			// 		if ($generateColumns) {
			// 			$label = 'colonne_' . $i;
			// 		}
			// 		else {
			// 			$label = $spreadsheet->getActiveSheet()->getCell($this->numberToLetters($i) . '1')->getValue();
			// 		}

			// 		$label = $this->nettoyage($label);
			// 		if(in_array($label, $existingCols)) {
			// 			$label = $label . $i;
			// 		}
			// 		$existingCols[] = $label;
					
			// 		$spreadsheet->getActiveSheet()->getCell($this->numberToLetters($i) . '1')->setValue($label);
			// 	}
					
			// 	$writer = new Csv($spreadsheet);
			// 	if ($generateColumns) {
			// 		$filePath = str_ireplace('.csv', '_gencol.csv', $filePath);
			// 		$resourceUrl = $this->protocol . $host . $this->port . $filePath;
			// 	}
			// 	$writer->save(self::ROOT . $filePath);
			// }


			$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'MANAGE_FILE', 'SUCCESS', 'Traitement du fichier ' . $fileName . ' terminé.');
			$result = $this->uploadResourceToCKAN($api, $datasetId, $isUpdate, $resourceId, $resourceUrl, $fileName, $type, $description, true, null, $customName);
			$results[] = $result;
		}
		else if ($type == 'xls' || $type == 'xlsx') {

			//if files > 50MB we don't do the treatments.
			if ($transformFile && $filesize < 50000000) {

				$xls_file = self::ROOT . $filePath;
				
				$reader = new Xlsx();
				if (explode(".", $fileName)[1]  === 'xls' ||explode(".", $fileName)[1] === 'XLS') {
					$reader = new Xls();
				}
				$reader->setReadDataOnly(true);
				$spreadsheet = $reader->load($xls_file);

				$loadedSheetNames = $spreadsheet->getSheetNames();
				$highestRow = $spreadsheet->getActiveSheet()->getHighestRow(); // e.g. 10
				$highestColumn = $spreadsheet->getActiveSheet()->getHighestColumn(); // e.g 'F'
				$spreadsheet->getActiveSheet()->getStyle('A1:' . $highestColumn . $highestRow)->getNumberFormat()->setFormatCode('###.##');
				
				// We upload the excel file and we convert it to insert data if we can
				$result = $this->manageFileWithPath($datasetId, false, false, $resourceId, $resourceUrl, '', $encoding, false, false, false, null, true);
				$results = array_merge($results, $result);

				$writer = new Csv($spreadsheet);

				$arrayXls = array('.xlsx', '.xls', '.XLSX', '.XLS');

				$originalRootFilePath = self::ROOT . $filePath;
				$originalResourceUrl = $resourceUrl;
				$originalFileName = $fileName;
				$originalFilePath = $filePath;

				foreach($loadedSheetNames as $sheetIndex => $loadedSheetName) {
					$csvExtension = $sheetIndex . '.csv';
					$arrayCsvWithIndex = array($csvExtension, $csvExtension, $csvExtension, $csvExtension);
	
					$csvpath = str_replace($arrayXls, $arrayCsvWithIndex, $originalRootFilePath);
					$resourceUrl = str_replace($arrayXls, $arrayCsvWithIndex, $originalResourceUrl);
					$fileName = str_replace($arrayXls, $arrayCsvWithIndex, $originalFileName);
					$filePath = str_replace($arrayXls, $arrayCsvWithIndex, $originalFilePath);

					Logger::logMessage("Saving CSV for sheet at index " . $sheetIndex . " with path '" . $csvpath . "'");

					$writer->setSheetIndex($sheetIndex);
					$writer->save($csvpath);

					$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'MANAGE_FILE', 'SUCCESS', 'Traitement du fichier ' . $fileName . ' terminé.');
					$result = $this->manageFileWithPath($datasetId, $generateColumns, $isUpdate, $resourceId, $resourceUrl, $description, $encoding, false, $fromPackage, true, $customName);
					$results = array_merge($results, $result);
				}
			}
			else {
				$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'MANAGE_FILE', 'SUCCESS', 'Traitement du fichier ' . $fileName . ' terminé.');
				$result = $this->uploadResourceToCKAN($api, $datasetId, $isUpdate, $resourceId, $resourceUrl, $fileName, $type, $description, true, null, $customName);
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

				foreach($resources as $resource){
					if (strpos($resource['name'], 'zip') !== false) {

						$resourceId = $resource['id'];
						$name = $resource['name'];

						//We need to change the PATH of the zip file to match the previous version and backup the old one
						Logger::logMessage("Found the previous ZIP resource '" . $resourceId . "' with name '" . $name . "'");


						$rootDirectory = $this->getRoutingPrefix(false) . 'sites/default/files/dataset/';
						
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

			$result = $this->uploadResourceToCKAN($api, $datasetId, $isUpdate, $resourceId, $resourceUrl, $fileName, $type, $description, false, null, $customName);
			$results[] = $result;

			if ($unzipZip) {
				$result = $this->manageZip($datasetId, $generateColumns, false, $resourceId, $filePath, $encoding);
				$results = array_merge($results, $result);
			}
		}
		else if ($type == 'json' || $type == 'geojson' || $type == 'kml' || $type == 'shp' || $type == 'gml') {
			// We upload the geojson file as resource except if it is a shp or kml file
			if ($type != 'kml' && $type != 'shp' && $type != 'gml') {
				$result = $this->uploadResourceToCKAN($api, $datasetId, $isUpdate, $resourceId, $resourceUrl, $fileName, $type, $description, false, null, $customName);
				$results[] = $result;
			}

			//If we update the geojson, we need to get the previous CSV resource to update it
			if ($isUpdate) {
				Logger::logMessage("Looking for the previous CSV resource to update");
				$dataset = $api->getPackageShow("id=" . $datasetId);
				foreach($dataset['result']['resources'] as $resource){
					if (strpos($resource['url'], 'csv_gen') !== false) {
						$resourceId = $resource['id'];

						//We change the name in order to change the resource URL
						//If we don't do that, the file is not uploaded to the datapusher
						$name = "csv_gen_" . $datasetId . "_" . uniqid() . '.csv';
						$customName = $resource['name'];
						Logger::logMessage("Found the previous CSV resource '" . $resourceId . "' with name '" . $name . "'");
						break;
					}
				}  
			}
			
			if (!$name) {
				$name = "csv_gen_" . $datasetId . "_" . uniqid() . '.csv';
				$customName = $fileName . '.csv';
				$customName = str_replace(array('.json', '.geojson', '.kml', '.shp'), '', $customName);

				Logger::logMessage("Uploading CSV from GeoFile with name '" . $name . "' and custom name '" . $customName . "'");

				$isUpdate = false;
			}
			
			$rootCsv = self::ROOT . $this->getRoutingPrefix(false) . 'sites/default/files/dataset/' . $datasetFolder . '/' . $name;

			$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'MANAGE_FILE', 'SUCCESS', 'Traitement du fichier ' . $fileName . ' terminé.');

			$csvGenerated = $this->manageGeoFiles($type, $resourceUrl, $filePath, $rootCsv, $datasetFolder);
			if ($csvGenerated) {
				$resourceUrl = $this->protocol . $_SERVER['HTTP_HOST'] . $this->port . $this->getRoutingPrefix(true) . 'sites/default/files/dataset/' . $datasetFolder . '/' . $name;
				
				$result = $this->manageFileWithPath($datasetId, $generateColumns, $isUpdate, $resourceId, $resourceUrl, '', $encoding, false, $fromPackage, false, $customName);
				$resourceId = $this->array_key_first($result[0]);
				$results = array_merge($results, $result);
				
				if ($result[0][$resourceId]['status'] == 'complete') {
					//We create the clusters
					$results[] = $this->createClusters($datasetId, $resourceId, ',', 'UTF-8', 'geo_point_2d', ',', $fileName);
				}
			}

			return $results;

			// Don't throw an error if the file is null
			// $this->updateDatabaseStatus(false, $datasetId, $datasetId, 'MANAGE_FILE', 'ERROR', "Le fichier '" . $fileName . "' ne peut pas être converti en CSV pour être intégré à l\'application.");
			// throw new \Exception("Le fichier '" . $fileName . "' ne peut pas être converti en CSV pour être intégré à l\'application.");
		}
		else {
			// We upload the file as resource
			$result = $this->uploadResourceToCKAN($api, $datasetId, $isUpdate, $resourceId, $resourceUrl, $fileName, $type, $description, false, null, $customName);
			$results[] = $result;
			// $this->updateDatabaseStatus(false, $datasetId, $datasetId, 'MANAGE_FILE', 'ERROR', 'Une erreur est survenue avec le fichier ' . $fileName . '.');
			// Logger::logMessage("We do not process the file '" . $filePath . "'");
		}

		return $results;
	}

	/**
	* @param string $csvFile Path to the CSV file
	* @return string Delimiter
	*/
	public function detectDelimiter($csvFile) {
		Logger::logMessage("Detecting delimiter...");

		$delimiters = [";" => 0, "," => 0, "\t" => 0, "|" => 0];

		$handle = fopen($csvFile, "r");
		$firstLine = fgets($handle);
		fclose($handle); 
		foreach ($delimiters as $delimiter => &$count) {
			$count = count(str_getcsv($firstLine, $delimiter));
		}

		if ( array_sum( $delimiters ) <= count( $delimiters ) ) return false;

		return array_search(max($delimiters), $delimiters);
	}

	function cleanFileName($fileName) {
		$fileName = strtolower($fileName);
		$fileName = urldecode($fileName);
		$fileName = $this->nettoyage2($fileName);
		$fileName = explode("/", $fileName);
		$fileName = $fileName[(count($fileName)-1)];

		return $fileName;
	}

	/**
	 * Generate a folder for the year, the month, the day and datasetId
	 * 
	 * @param $datasetId
	 * @return folder path
	 */
	function generateDatasetFolder($datasetId) {
		// Get the current year, month and day
		$date = new \DateTime();
		$year = $date->format('Y');
		$month = $date->format('m');
		$day = $date->format('d');

		$datasetFolder = $year . "/" . $month . "/" . $day . "/" . $datasetId;

		$folder = self::ROOT . $this->getRoutingPrefix(false) . 'sites/default/files/dataset/' . $datasetFolder;
		$folder = str_replace('//', '/', $folder);

		if (!file_exists($folder)) {
			mkdir($folder, 0777, true);
		}

		return $datasetFolder;
	}

	function manageCSWXmlFile($organization, $datasetId, $datasetName) {
		Logger::logMessage("Checking if CSW is configure");
		try {
			if ($this->config->csw_server) {
				$cswPath = $this->config->csw_server->csw_path;

				$api = new Api;
				$result = $api->getOrganization("id=" . $organization);
				$organizationName = $result['result']['name'];
	
				Logger::logMessage("Generate CSW XML");
				$cswPath = $cswPath . "/" . $organizationName;
				//The drupal unix user must have the right on the folder
				if (!file_exists($cswPath)) {
					Logger::logMessage("Creating node for organisation " . $organizationName);
					mkdir($cswPath, 0777, true);
				}

				//We generate an XML file to share with your CSW server
				$packageManager = new PackageManager;
				$result = $packageManager->generateMEditXML($datasetId);

				$cswFile = $cswPath . "/" . $datasetName . ".xml";
				
				Logger::logMessage("File has been generated in " . $result[0] . " and will be copied to " . $cswFile);
				copy($result[0], $cswFile);
			}
			else {
				Logger::logMessage("Skip CSW generation");
			}
		} catch (\Exception $e) {
			Logger::logMessage('Unable to generate CSW file ' . $e->getMessage());
		}
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
	function manageGeoFiles($type, $resourceUrl, $filePath, $newCSVPath, $datasetFolder) {
		Logger::logMessage("Manage " . $type . " file with url '" . $resourceUrl . "' and file path '" . $filePath . "'");

		$isFromPath = false;
		$file = '';

		//Now we try to use the file path if it exists
		if (isset($filePath) && $filePath != '') {
			$filePath = self::ROOT . substr($filePath, 1);
			
			$file = $filePath;
			$isFromPath = true;
		}
		else {
			Logger::logMessage("Retrieving file '" . $resourceUrl . "'");
			
			$file = Query::callSolrServer($resourceUrl);
			$isFromPath = false;
		}

		if ($type == 'geojson' || $type == 'json'){
			$csv = $this->buildCSVFromGeojson($file, $newCSVPath, $isFromPath);
		}
		else if ($type == 'json') {
			$json_match = false;
			if ($type == 'json') {
				if ($isFromPath) {
					$json = file_get_contents($file);
					$json = json_decode($json, true);
					if (isset($json["type"]) && $json["type"] == "FeatureCollection") {
						$json_match = true;
					}
				}
				else {
					$json = json_decode($file, true);
					if (isset($json["type"]) && $json["type"] == "FeatureCollection") {
						$json_match = true;
					}
				}
			}

			if ($json_match) {
				$csv = $this->buildCSVFromGeojson($file, $newCSVPath, $isFromPath);
			}
			else {
				Logger::logMessage("No CSV file generated from Geo File");
				$csv = null;
			}
		}
		else if ($type == 'kml' || $type == 'shp' || $type == 'gml') {
			$scriptPath = self::ROOT . $this->getRoutingPrefix(false) . 'modules/ckan_admin/src/Utils/convert_geo_files_ogr2ogr.sh';

			$typeConvert = 'GEOJSON';
			$projection = $this->config->client->shapefile_projection;
			
			Logger::logMessage("Building Geojson from geo file '" . $resourceUrl . "' with file path '" . $filePath . "'");

			$rootJson= self::ROOT . $this->getRoutingPrefix(false) . 'sites/default/files/dataset/' . $datasetFolder . '/gen_' . uniqid() . '.geojson';
			$command = $scriptPath." 2>&1 '" . $typeConvert . "' " . $rootJson . " " . $filePath . " " . $projection;
			
			Logger::logMessage("OGR2OGR command '" . $command . "'");
			$message = shell_exec($command);
			Logger::logMessage("Result from shape conversion '" . json_encode($message) . "'");

			//Checking file size
			$fileSize = filesize($rootJson);
			if ($fileSize > 1000000000) {
				throw new \Exception('The file is too big to integrate the data. Please upload a file smaller than 1GB.');
			}
			else {
				// $json = file_get_contents ($rootJson);
	
				$csv = $this->buildCSVFromGeojson($rootJson, $newCSVPath, true);
				unlink ($rootJson);
			}
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

		$datasetFolder = $this->generateDatasetFolder($datasetId);

        // save the content of GSheeturl in csv file and get url of resource
		$data = $contenturlsheet;
		if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $this->getRoutingPrefix(true) . "sites/default/files/dataset/" . $datasetFolder . "/urlsheet/")) {
			mkdir($_SERVER['DOCUMENT_ROOT'] . $this->getRoutingPrefix(true) . "sites/default/files/dataset/" . $datasetFolder . "/urlsheet/", 0777, true);
		}

		$fileName = $datasetId . ".csv";

		$fp = fopen($_SERVER['DOCUMENT_ROOT'] . $this->getRoutingPrefix(true) . "sites/default/files/dataset/" . $datasetFolder . "/urlsheet/" . $fileName, "wb");
		fputs($fp, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
		foreach ( $data as $line ) {
			fputcsv($fp, $line);
		}
		fclose($fp);

		$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'CREATE_FILE', 'SUCCESS', 'Le fichier \'' . $fileName . '\' a été créé depuis le fichier Google Sheet \'' . $urlGsheet . '\'');

		return $this->protocol . $_SERVER['HTTP_HOST'] . $this->port . $this->getRoutingPrefix(true) . 'sites/default/files/dataset/' . $datasetFolder . '/urlsheet/' . $fileName;
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
		if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $this->getRoutingPrefix(true) . "sites/default/files/dataset/xmlfile/")) {
			mkdir($_SERVER['DOCUMENT_ROOT'] . $this->getRoutingPrefix(true) . "sites/default/files/dataset/xmlfile/", 0777, true);
		}
		
		$query_params = $api->proper_parse_str($url);
		$fileName = $query_params["resource_id"] . "-xml" . ".csv";

		$fp = fopen($_SERVER['DOCUMENT_ROOT'] . $this->getRoutingPrefix(true) . "sites/default/files/dataset/xmlfile/" . $fileName, "wb");
		fputs($fp, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
		foreach ( $data as $line ) {
			
			fputcsv($fp, $line);
		}

		fclose($fp);
		$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'CREATE_FILE', 'SUCCESS', 'Le fichier \'' . $fileName . '\' a été créé depuis le fichier xml \'' . $urlGsheet . '\'');

		return $this->protocol . $_SERVER['HTTP_HOST'] . $this->port . $this->getRoutingPrefix(true) . 'sites/default/files/dataset/xmlfile/' . $fileName;
	}
	
	function managePackage($uniqId, $resourceUrl, $security, $organization) {
		Logger::logMessage("Manage package file '" . $resourceUrl . "'");

		$fileName = parse_url($resourceUrl);

		$host = isset($this->host) ? $this->host : $fileName['host'];
		$fileName = $fileName[path];
		$filePath = $fileName;

		$fileName = $this->cleanFileName($fileName);

		$filePathN = urldecode($filePath);
		$filePathN = $this->nettoyage2($filePathN);

		rename(self::ROOT . urldecode($filePath), self::ROOT . $filePathN); 
		$filePath = $filePathN;

		$resourceUrl = $this->protocol . $host . $this->port . $filePath;

		Logger::logMessage("Managing package with path '" . $filePath . "'");

		$directoryName = 'package_extraction_' . uniqid();
		$directoryPath = self::ROOT . $this->getRoutingPrefix(false) . 'sites/default/files/dataset/' . $directoryName;

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
					$callUrl =  $this->urlCkan . "api/action/datastore_create";
					$data = array();
					$data["resource_id"] = $resourceId;
					$data["force"] = true;
					$data["fields"] = $fields;
					$data["uuid"] = uniqid();

					$result = $api->updateRequest($callUrl, $data, "POST");
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
		$zipPath = self::ROOT . substr($filePath, 1);

		Logger::logMessage("Manage zip file with path '" . $zipPath . "'");
		// $path = pathinfo(realpath($filePath), PATHINFO_DIRNAME);

		$directoryName = 'zip_' . str_replace('-', '_', $datasetId) . '_' . uniqid();
		$directoryPath = self::ROOT . $this->getRoutingPrefix(false) . 'sites/default/files/dataset/zipresources/' . $directoryName;

		Logger::logMessage("Unzipping in path '" . $directoryPath . "'");
		//Create the directory
		if (!file_exists($directoryPath)) {
			Logger::logMessage("Creating directory '" . $directoryPath . "'");
			mkdir($directoryPath, 0777, true);
		}

		$zip = new ZipArchive;
		$res = $zip->open($zipPath);
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

		$isShape = false;
		$isGtfs = false;
		$color_array = [];
		foreach($files as $key=>$file) {
			//Checking if one the required GTFS files exist
			if (strpos($file, 'agency.txt') !== false || strpos($file, 'stops.txt') !== false
				|| strpos($file, 'trips.txt') !== false|| strpos($file, 'stop_times.txt') !== false) {
					$isGtfs = true;
			}
			// Getting route color if defined
			else if (strpos($file, 'routes.txt') !== false) {
				$isGtfs = true;

				$key_rout_id="";
				$key_color_route ="";

				$array = explode("\n", file_get_contents($file));
				foreach ($array as $key => $value) {
					$line = explode(',', $value);
					if ($key == 0 ) {
						$key_rout_id = array_search('route_id', $line);
						$key_color_route = array_search('route_color', $line);
					}
					else {
						array_push($color_array,array("route_id"=>$line[$key_rout_id], "color_route"=>$line[$key_color_route]));
					}
				}
			}
			else if (strpos($file, '.shp') !== false) {
				$isShape = true;
			}
		}

		if ($isShape) {
			//We need to rename the files
			foreach($files as $key=>$file) {
				if ($isShape && !$this->endsWith($file, '.shp')) {
					$fileName = basename($file);
					$newFileName = strtolower($fileName);
					$newFileName = urldecode($fileName);
					$newFileName = $this->nettoyage2($fileName);

					$newFilePath = str_replace($fileName, $newFileName, $file);

					Logger::logMessage("Renaming '" . $file . "' to '" . $newFilePath . "' and continue because it is linked to a shape");
			
					rename($file, $newFilePath); 

					continue;
				}
			}
		}

		try {
			// We check if the zip is a GTFS. 
			// We check if shapes.txt exist inside zip
			foreach($files as $key=>$file) {
				Logger::logMessage("Managing file '" . $file . "'");
				
				if (is_dir($file)) {
					Logger::logMessage("Ignoring '" . $file . "' because it is a folder");
					continue;
				}
				else if ($isGtfs && $this->endsWith($file, '.txt')) {
					Logger::logMessage("Found GTFS txt file converting to CSV");

					$isShapeFile = strpos($file, 'shapes.txt') !== false;
					$resourceUrl = $this->convertTextFileToCsv($file, $isShapeFile, "csv", $color_array);

					Logger::logMessage("New file to manage " . $resourceUrl);
				}
				else if ($fromPackage && (strpos($file, 'metadata.json') !== false) || strpos($file, "csv_gen") !== false) {
					Logger::logMessage("Ignoring '" . $file . "' because it is autogenerated by D4C");
					//Skipping metadata.json and autogenerated D4C's file from a package. We don't add it to the dataset
					continue;
				}
				else if ($isShape && !$this->endsWith($file, '.shp')) {
					continue;
				}
				else {
					$resourceUrl = str_replace(self::ROOT, $this->protocol . $_SERVER['HTTP_HOST'] . $this->port . '/', $file);
					Logger::logMessage("TRM - Zip file URL '" . $resourceUrl . "'");
				}

				//We don't move the file if it is a shape
				$result = $this->manageFileWithPath($datasetId, $generateColumns, $isUpdate, $resourceId, $resourceUrl, '', $encoding, false, $fromPackage, true, null, !$isShape);
				$results = array_merge($results, $result);
			}

			// According to parameters, we can delete the extracted files
			if ($isShape) {
				Logger::logMessage("Deleting extracted files in " . $directoryPath);
				$this->deleteDir($directoryPath);
			}
		} catch (\Exception $e) {
			// According to parameters, we can delete the extracted files
			if ($isShape) {
				Logger::logMessage("Deleting extracted files in " . $directoryPath);
				$this->deleteDir($directoryPath);
			}

			throw $e;
		}

		return $results;
	}

	function deleteDir($dir) {
		$it = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
		$files = new \RecursiveIteratorIterator($it,
					\RecursiveIteratorIterator::CHILD_FIRST);
		foreach($files as $file) {
			if ($file->isDir()){
				rmdir($file->getRealPath());
			} else {
				unlink($file->getRealPath());
			}
		}
		rmdir($dir);
	}

	//convert text file to csv
	function convertTextFileToCsv($filepath, $isShapeFile, $new_extension, $color_array) {

		// get content of text file
		$filepathContent = file_get_contents($filepath);

		$pathinfo = pathinfo($filepath);
		$pathinfo["extension"] = $new_extension;

		$pathfiles = explode("/", $filepath);
		$pathfiles[sizeof($pathfiles) -1] = $pathinfo["filename"] . "." . $new_extension; 

		$newfile = "";
		foreach ($pathfiles as $key => $value) {
			if ($key == 0) {
				$newfile = $value;
			}
			else {
				$newfile .= "/" . $value;
			}
		}

		//create a new csv files contains the same content of text file
		file_put_contents($newfile, $filepathContent);

		if ($isShapeFile) {
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
			$Routes = [];
	
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
					
				if ($i!=0) {
					if($i+1 < sizeof($csv_data)) {
						$data2 = $csv_data[$i+1];
						if ($data[$shapeIdIndex] != $data2[$shapeIdIndex] ) {
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
				}
				else {
					$firstindex = $Routes[$key -1 ] + 1;
				}
	
				$array2 =array();
				$array3=[];
				for ($i=$firstindex; $i <=$value ; $i++) {
					$array2 =array((float)$csv_data[$i][$lngIndex],(float)$csv_data[$i][$latIndex]);
					$array3[] = $array2;
				}
				
				$routesvalue[$key+1] = json_encode(array('type' => "LineString",'coordinates' => $array3));
			}
	
			foreach ($csv_data as $i => $data) {
				if ($i == 0) {
					$extra_columns = array('coordinate' => (float)$data[$latIndex] ."," . (float)$data[$lngIndex], 'geo_shape' => "", 'route_color' => null);
					$csv_data[$i] = array_merge($data, array_keys($extra_columns));
				}
				else {
					if (array_key_exists($i,$routesvalue)) {
						$geo_shape = $routesvalue[$i];
						if ($color_array[$i]["color_route"] != null) {
							$color = $color_array[$i]["color_route"];
						}
						else {
							$random_color = str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
							$keycolor = array_search($random_color, array_column($color_array, 'color_route'));
							if($keycolor ==false) {
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
		}

		$newfile = str_replace($_SERVER['DOCUMENT_ROOT'], $this->protocol . $_SERVER['HTTP_HOST'] . $this->port, $newfile);
		return $newfile;
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

	function uploadResourceToCKAN($api, $datasetId, $isUpdate, $resourceId, $resourceUrl, $fileName, $type, $description, $pushToDataspusher, $format = null, $customName = null) {
		
		Logger::logMessage(($isUpdate ? "Updating " : "Uploading " ) . " resource '" . $fileName . "' on CKAN and monitoring the datapusher");
		$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'UPLOAD_CKAN', 'PENDING', 'Ajout du fichier \'' .  $fileName . '\' dans CKAN');

		$resourceName = $customName != null ? $customName : $fileName;

		//We update the description if empty or equals to default.description
		if (isset($description) && strpos($description, 'default.description') !== false) {
			$description = "";
		}
	
		if ($isUpdate) {
			$resource = [
				"id" => $resourceId,
				"url" => $resourceUrl,
				"name" => $resourceName,
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
			$callUrl =  $this->urlCkan . "api/action/resource_update";
			$return = $api->updateRequest($callUrl, $resource, "POST");

			$fieldsWithoutId = array();
			foreach ($fields as $field) {
				if ($field["id"] != "_id") {
					$fieldsWithoutId[] = $field;
				}
			}

			$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'UPLOAD_CKAN', 'SUCCESS', 'Le fichier \'' .  $fileName . '\' a été ajouté à CKAN');
			$api->addResourceVersion($datasetId, $resourceId, $resourceUrl);
			
			if ($type == 'csv' || $type == 'xls'/* The datapusher does not support xlsx anymore || $type == 'xlsx'*/) {
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
			$callUrl =  $this->urlCkan . "api/action/datastore_create";
			$data = array();
			$data["resource_id"] = $resourceId;
			$data["force"] = true;
			$data["fields"] = $fieldsWithoutId;
			$data["uuid"] = uniqid();
			$api->updateRequest($callUrl, $data, "POST");

			return $datapusherResult;
		}
		else {
			if ($format) {
				$resources = [    
					"package_id" => $datasetId,
					"url" => $resourceUrl,
					"description" => '',
					"name" => $resourceName,
					"format" => $format
				];
			}
			else {
				$resources = [    
					"package_id" => $datasetId,
					"url" => $resourceUrl,
					"description" => '',
					"name" => $resourceName
				];
			}
			$callUrluptres = $this->urlCkan . "api/action/resource_create";
			$return = $api->updateRequest($callUrluptres, $resources, "POST");
			$return = json_decode($return);
			
			if ($return->success == true) {
			
				$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'UPLOAD_CKAN', 'SUCCESS', 'Le fichier \'' .  $fileName . '\' a été ajouté à CKAN');
				$resourceId = $return->result->id;
				$api->addResourceVersion($datasetId, $resourceId, $resourceUrl);
				
				if ($type == 'csv' || $type == 'xls'/* The datapusher does not support xlsx anymore || $type == 'xlsx'*/) {
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
				Logger::logMessage("An error occured during status's checking, we try to check the status again: " . json_encode($datapusherStatus));
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

	function deleteDatasetResources($datasetId) {
		$api = new Api;
		
		$dataset = $api->getDataSetById($datasetId);
		$contentdataset = json_decode($dataset->getContent(), true);
		$resources = $contentdataset["result"]["resources"];

		for($i=0; $i<count($resources); $i++) {
			$this->deleteResource($resources[$i]['id']);
		}
	}

	function deleteResource($resourceId) {
		$api = new Api;
		
		$delRes = [
			"id" => $resourceId,
			"force" => "True",
		];

		$callUrl = $this->urlCkan . "api/action/resource_delete";
		$result = $api->updateRequest($callUrl, $delRes, "POST");
		$result = json_decode($result);

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
		return $this->cleanColumnName($label, true);
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
			$url_pict = $this->getRoutingPrefix(true) . "sites/default/files/theme_logo/".$url_pict[0].".svg";

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
		// Changing method to allow space and accent in tags

		$tagsData = array();
		if ($tags == null || $tags == '') {
			$tagsData = [];
		}
		else if (is_array($tags)) {
			foreach ($tags as $value) {
				// OLD way
				// $cleanValue = $this->cleanTag($value);
				// if (mb_strlen($cleanValue) >= 2) {
				// 	$tagsData[] = ["vocabulary_id" => null, "state" => "active", "display_name" => $cleanValue, "name" => $cleanValue];
				// }
				
				$cleanValue = $this->cleanTag($value);
				$tagsData[] = ["name" => $cleanValue];
			}
		}
		else {
			$tags = explode(",", $tags);

			for ($j = 0; $j < count($tags); $j++) {
				// OLD way
				// $tagsData[$j] = ["vocabulary_id" => null, "state" => "active", "display_name" => $tags[$j], "name" => $tags[$j]];

				$tagsData[$j] = ["name" => $tags[$j]];
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
	
	function defineExtras($extras, $picto, $imgBackground, $removeBackground, $linkDatasets, $themes, $themeLabel,
			$selectedTypeMap, $selectedOverlays, $dont_visualize_tab, $widgets, $visu, 
			$dateDataset, $disableFieldsEmpty, $analyseDefault, $security, $producer=null, $source=null, $donnees_source=null, 
			$mention_legales=null, $frequence=null, $displayVersionning = null, $dataRgpd = null, $data4citizenType = null, $entityId = null,
			$dateDeposit = null, $uploader = null) {
		if ($extras == null) {
			$extras = array();
		}

		$hasPicto = false;
		$hasBackground = false;
		$hasLinkDatasets = false;
		$hasThemes = false;
		// $hasThemeLabel = false;
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
		$hasDataRgpd = false;
		$hasData4citizenType = false;
		$hasEntityId = false;
		$hasDateDeposit = false;
		$hasUsername = false;
		
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
												
				if ($extras[$index]['key'] == 'themes') {
					$hasThemes = true;
					if (isset($themes)) {
						$extras[$index]['value'] = $themes;
					}
				}
												
				// if ($extras[$index]['key'] == 'theme') {
				// 	$hasTheme = true;
				// 	$extras[$index]['value'] = $theme;
				// }
				
				// if ($extras[$index]['key'] == 'label_theme') {
				// 	$hasThemeLabel = true;
				// 	$extras[$index]['value'] = $themeLabel;
				// }
					
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
	
				if ($extras[$index]['key'] == 'disable_fields_empty') {
					$hasDisableFieldsEmpty  = true;
					$extras[$index]['value'] = $disableFieldsEmpty;
				}

				if ($extras[$index]['key'] == 'edition_security') {
					$hasSecurity = true;
				}
				
				if ($extras[$index]['key'] == 'analyse_default') {
					$hasAnalyse = true;

					if (isset($analyseDefault)) {
						$extras[$index]['value'] = $analyseDefault;
					}
				}
	
				if ($extras[$index]['key'] == 'display_versionning') {
					$hasDisplayVersionning  = true;
					$extras[$index]['value'] = $displayVersionning;
				}
	
				if ($extras[$index]['key'] == 'data_rgpd') {
					$hasDataRgpd  = true;
					$extras[$index]['value'] = $dataRgpd;
				}
	
				if ($extras[$index]['key'] == 'data4citizen-type') {
					$hasData4citizenType  = true;
					$extras[$index]['value'] = $data4citizenType;
				}
	
				if ($extras[$index]['key'] == 'data4citizen-entity-id') {
					$hasEntityId  = true;
					$extras[$index]['value'] = $entityId;
				}
				
				if ($extras[$index]['key'] == 'date_deposit') {
					$hasDateDeposit = true;
					$extras[$index]['value'] = $dateDeposit;
				}

				if ($extras[$index]['key'] == 'uploader') {
					$hasUsername = true;
					$extras[$index]['value'] = $uploader;
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

		if ($hasThemes == false) {
			$extras[count($extras)]['key'] = 'themes';
			$extras[(count($extras) - 1)]['value'] = $themes;
		}

		// if ($hasTheme == false) {
		// 	$extras[count($extras)]['key'] = 'theme';
		// 	$extras[(count($extras) - 1)]['value'] = $theme;
		// }

		// if ($hasThemeLabel == false) {
		// 	$extras[count($extras)]['key'] = 'label_theme';
		// 	$extras[(count($extras) - 1)]['value'] = $themeLabel;
		// }

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

		if ($hasDataRgpd == false) {
			$extras[count($extras)]['key'] = 'data_rgpd';
			$extras[(count($extras) - 1)]['value'] = $dataRgpd;
		}

		if ($hasData4citizenType == false) {
			$extras[count($extras)]['key'] = 'data4citizen-type';
			$extras[(count($extras) - 1)]['value'] = $data4citizenType;
		}

		if ($hasEntityId == false) {
			$extras[count($extras)]['key'] = 'data4citizen-entity-id';
			$extras[(count($extras) - 1)]['value'] = $entityId;
		}

		if ($hasDateDeposit == false) {
			$extras[count($extras)]['key'] = 'date_deposit';
			$extras[(count($extras) - 1)]['value'] = $dateDeposit;
		}

		if ($hasUsername == false) {
			$extras[count($extras)]['key'] = 'uploader';
			$extras[(count($extras) - 1)]['value'] = $uploader;
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
	
	function buildCSVFromGeojson($json, $newCSVPath, $isFromPath = false) {
		if ($json == null) {
			return "";
		}

		// Use a library for a better memory management
		// - https://github.com/halaxa/json-machine - composer require halaxa/json-machine
		if ($isFromPath) {
			Logger::logMessage("Creating CSV from GeoJson with path : " . $json);

			try {
				$types = JsonMachine::fromFile($json, '/type');
				$type = iterator_to_array($types)['type'];
	
				if ($type != "FeatureCollection") {
					return "";
				}
	
				$jsonItems = JsonMachine::fromFile($json, '/features');
			} catch (\Exception $e) {
				Logger::logMessage("Error while reading GeoJson file : " . $e->getMessage());
				return null;
			}
		}
		else {
			Logger::logMessage("Creating CSV from GeoJson from String");
		
			// If passed a string, turn it into an array
			if (is_array($json) === false) {
				
				$types = JsonMachine::fromFile($json, '/type');
				$type = iterator_to_array($types)['type'];

				if ($type != "FeatureCollection") {
					return "";
				}

				$jsonItems = JsonMachine::fromString($json, '/features');
				// $json = json_decode($json, true, 512, JSON_UNESCAPED_UNICODE);
			}
			else {
				if ($json['type'] != "FeatureCollection") {
					return "";
				}

				$jsonItems = $json['features'];
			}
		}

		//construction du csv
		$cols = array();
		$colNames = array();

		//Previously we were getting only the columns for the first feature but we could miss a lot of informations
		//We now go through 4000 features but we have to check if it not too much time consuming
		$hasShapes = false;
		$index = 0;
		$line = 0;
		foreach($jsonItems as $feat) {
			if ($line > 4000) {
				break;
			}
			$line++;

			foreach($feat["properties"] as $key => $val){

				if ($index == 1) {
					$cols[] = "geo_point_2d";
					$colNames[] = "geo_point_2d";
					$index++;
				}

				//We check if the key already exist
				if (!in_array($key, $cols)) {
					Logger::logMessage("Found column " . $key);

					$cols[] = $key;
					
					$label = $this->clearGeoProperties($key, $index);
					$label = $this->cleanColumnName($label);

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

		Logger::logMessage("Writing CSV file '" . $newCSVPath . "'");

		$fp = fopen($newCSVPath, 'wb');
		
		// Writing the header
		fputcsv($fp, $colNames, ',');

		// $rows = array();
		$colsTypes = array();
		foreach($jsonItems as $feat) {
			$row = array();
			foreach($cols as $col){
				if($col == "geo_point_2d"){
					if ($hasShapes) {
						$str = json_encode($feat["geometry"]);
						preg_match('/\[([-]?[\d|.]+),([-]?[\d|.]+)/i', $str, $match);
						$coord = '' . $match[2] . "," . $match[1] . '';
						$row[] = $coord;
					}
					else {
						$str = json_encode($feat["geometry"]["coordinates"]);
						preg_match('/\[([-]?[\d|.]+),([-]?[\d|.]+)/i', $str, $match);
						$val = '' . $match[2] . "," . $match[1] . '';
						$row[] = $val;
					}
				}
				else if($col == "geo_shape") {
					$str = json_encode($feat["geometry"]);
					preg_match('/\[([-]?[\d|.]+),([-]?[\d|.]+)/i', $str, $match);
					$coord = '' . $match[2] . "," . $match[1] . '';

					//We replace " by "" to escape them
					// $str = str_replace('"', "\"\"", $str);
					$row[] = '' . $str . '';
				}
				else if($col == "coordinates"){
					continue;
				}	
				else {
					$value = $feat["properties"][$col];
					if((isset($colsTypes[$col]) && $colsTypes[$col] == "text") || !$this->isNumericColumn($json,$col)){
						//We replace " by "" to escape them
						$value = str_replace('"', "\"\"", $value);

						$row[] = '' . $value . '';
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
			
			if (count($row) < count($cols)){
				$row = array_pad($row, count($cols), "");
			}

			// though CSV stands for "comma separated value"
			// in many countries (including France) separator is ";"
			fputcsv($fp, $row, ',');
		}

		fclose($fp);

		return true;
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
		$callUrlNewData = $this->urlCkan . "api/action/package_create";
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
		$callUrl = $this->urlCkan . "api/action/package_delete";
            
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
	
    function cleanColumnName($str, $isDatasetName = false) {
		if (!mb_detect_encoding($str, 'UTF-8', true)) {
			$str = iconv("UTF-8", "Windows-1252//TRANSLIT", $str);
		}
		
		//We remove whitespaces at the beggining and end of the label
		$str = trim($str);
		//We remove - or _ at the beggining
		$str = ltrim($str, '_');
		$str = ltrim($str, '-');
		
		$unwanted_array = array(    'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
                            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
                            'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
                            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
                            'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
		$str = strtr( $str, $unwanted_array );
		
		$str = str_replace("?", "", $str);
		$str = str_replace("`", "_", $str);
		$str = str_replace("'", "_", $str);
		$str = str_replace('’', "_", $str);
		// $str = str_replace("-", "_", $str);
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
		$str = str_replace("\"", "", $str);
		$str = str_replace(".", "_", $str);
		$str = strtolower($str);
		$str = preg_replace( '#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str );
		$str = preg_replace( '#&([A-za-z]{2})(?:lig);#', '\1', $str );
		$str = preg_replace( '#&[^;]+;#', '', $str );
		$str = str_replace("°", "", $str);

		if (!$isDatasetName) {
			$str = str_replace("-", "_", $str);
		}

		//We set the value to 63 characters as it is the limit of the database
		$str = substr($str, 0, 62);
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

    function cleanTag($str, $charset='utf-8' ) {
		if (!mb_detect_encoding($str, 'UTF-8', true)) {
			$str = iconv("UTF-8", "Windows-1252//TRANSLIT", $str);
		}
		
		//We remove whitespaces at the beggining and end of the label
		$str = trim($str);

		$str = str_replace("?", "", $str);
		$str = str_replace("`", "", $str);
		$str = str_replace("'", "", $str);
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
		$str = str_replace("/", "", $str);
		$str = str_replace("|", "", $str);
		$str = str_replace("=", "", $str);
		$str = str_replace("[", "", $str);
		$str = str_replace("]", "", $str);
		return $str;


		// OLD WAY
		// if (!mb_detect_encoding($str, 'UTF-8', true)) {
		// 	$str = iconv("UTF-8", "Windows-1252//TRANSLIT", $str);
		// }
		
		// //We remove whitespaces at the beggining and end of the label
		// $str = trim($str);
		
		// $unwanted_array = array(    'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
        //                     'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
        //                     'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
        //                     'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
        //                     'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
		// $str = strtr( $str, $unwanted_array );
		
		// $str = str_replace("?", "", $str);
		// $str = str_replace("`", "_", $str);
		// $str = str_replace("'", "_", $str);
		// $str = str_replace("-", "_", $str);
		// $str = str_replace(" ", "_", $str);
		// $str = str_replace(",", "", $str);
		// $str = str_replace("%", "", $str);
		// $str = str_replace("(", "", $str);
		// $str = str_replace(")", "", $str);
		// $str = str_replace("*", "", $str);
		// $str = str_replace("!", "", $str);
		// $str = str_replace("@", "", $str);
		// $str = str_replace("#", "", $str);
		// $str = str_replace("$", "", $str);
		// $str = str_replace("^", "", $str);
		// $str = str_replace("&", "", $str);
		// $str = str_replace("+", "", $str);
		// $str = str_replace(":", "", $str);
		// $str = str_replace(">", "", $str);
		// $str = str_replace("<", "", $str);
		// $str = str_replace('\'', "_", $str);
		// $str = str_replace("/", "_", $str);
		// $str = str_replace("|", "_", $str);
		// $str = strtolower($str);
		// $str = preg_replace( '#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str );
		// $str = preg_replace( '#&([A-za-z]{2})(?:lig);#', '\1', $str );
		// $str = preg_replace( '#&[^;]+;#', '', $str );
		// $str = str_replace("-", "_", $str);
		// $str = str_replace("=", "_", $str);
		// $str = str_replace("[", "", $str);
		// $str = str_replace("]", "", $str);
		// return $str;
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

	function endsWith( $haystack, $needle ) {
		$length = strlen( $needle );
		if( !$length ) {
			return true;
		}
		return substr( $haystack, -$length ) === $needle;
	}

}
