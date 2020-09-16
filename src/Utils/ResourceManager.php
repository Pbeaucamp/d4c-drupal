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
		$this->updateDatabaseStatus(true, $uniqId, '', 'CREATE_DATASET', 'PENDING', '');
	
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
		
		$coll = array('0'=>'0', '1'=>'');
			
		$datasetId = $this->saveData($newData, $coll);
		$datasetId = $datasetId[1];

		Logger::logMessage("New dataset has been saved with id '" . $datasetId . "'");
		$this->updateDatabaseStatus(false, $uniqId, $datasetId, 'CREATE_DATASET', 'SUCCESS', '');
		return $datasetId;
	}

	function updateDataset($datasetId, $datasetToUpdate, $datasetName, $title, $description, $licence, $organization, $isPrivate, $tags, $extras) {
		Logger::logMessage("Updating dataset '" . $datasetName . "'");
		$this->updateDatabaseStatus(true, $datasetId, $datasetId, 'UPDATE_DATASET', 'PENDING', '');
		
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
				
				if ($result->success != true) {
					$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'UPDATE_DATASET', 'ERROR', "L'organisation ne peut pas être mise à jour ' (" . $result->error->message . ").");
					throw new \Exception("L'organisation ne peut pas être mise à jour ' (" . $result->error->message . ").");
				}
			}

			$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'UPDATE_DATASET', 'SUCCESS', "");
		}
		else {
			$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'UPDATE_DATASET', 'ERROR', "Le jeu de données ne peut pas être mis à jour (" . $result->error->message . ").");
			throw new \Exception("Le jeu de données ne peut pas être mis à jour (" . $result->error->message . ").");
		}
	}

		//convert text file to csv
	function convertTextFileToCsv($filepath, $new_extension) {

		// get content of text file
	 	$filepathContent = file_get_contents($filepath);
	 	// check if file contains the comma and replace it by semicolon  
	 	if (strpos(file_get_contents($filepath), ',') !== false) {
	 		/*$commaReplace = str_replace(",",";",$filepathContent);*/
	 		$pathinfo = pathinfo($filepath);
	 		$pathinfo["extension"] = $new_extension;
	 		
	 		$filepath = str_replace($_SERVER['DOCUMENT_ROOT'],'https://' . $_SERVER['SERVER_NAME'], $filepath);

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
	 		return $newfile;

	 	}
	
	}

	function manageFiles($datasetId, $generateColumns, $isUpdate, $resourceId, $filesDirectory, $encoding) {
		Logger::logMessage("Managing files in '" . $filesDirectory . "'");

		// $files = scandir($filesDirectory);
		$files = $this->getDirContents($filesDirectory);
		$csv="";
	
		//check if shapes.txt exist inside zip
		$shapesExistIndex = null;
		foreach($files as $key=>$file) {
			if (strpos($file, 'shapes.txt') !== false) {
				//get the index of file
			    $shapesExistIndex = $key;
			}
			
		}
		//convert shapes file from txt to csv if exist
		if($shapesExistIndex != null ) {
			$titlesFile = explode("/", $files[$shapesExistIndex]);
			//convert file txt to csv
			$csv = $this->convertTextFileToCsv($files[$shapesExistIndex], "csv");

		}
		else {
			foreach($files as $file) {

				//TODO: Remake
				$csv = $this->manageFile($datasetId, $generateColumns, $isUpdate, $resourceId, $file, $encoding);
			
		}
		}
		if ($csv != null) {
				return $csv;
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

	function manageFile($file) {
		Logger::logMessage("Managing file from FORM POST");

		$file = File::load($file);
		$file->setPermanent();
		$file->save();

		$resourceUrl = $file->url();
		
		Logger::logMessage("TRM: Saving file with URL = " . $resourceUrl . ".");
		return $resourceUrl;
	}

	function manageFileWithPath($datasetId, $generateColumns, $isUpdate, $resourceId, $resourceUrl, $description, $encoding) {
		//Managing file (filepath and filename)
		$fileName = parse_url($resourceUrl);
		Logger::logMessage("TRM fileName " . $fileName);


		$host = $fileName[host];
		Logger::logMessage("TRM host " . $host);
		$fileName = $fileName[path];
		Logger::logMessage("TRM fileName " . $fileName);
		$filePath = $fileName;
		Logger::logMessage("TRM filePath " . $filePath);

		$fileName = strtolower($fileName);
		$fileName = urldecode($fileName);
		$fileName = $this->nettoyage2($fileName);
		$fileName = explode("/", $fileName);
		$fileName = $fileName[(count($fileName)-1)];
		Logger::logMessage("TRM fileName " . $fileName);

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
			if ($filesize < 50000000) {
				$reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
				if ($encoding) {
					Logger::logMessage("Setting encoding to " . $encoding . "\r\n");
					$reader->setInputEncoding($encoding);
				}
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


			$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'MANAGE_FILE', 'SUCESS', 'Traitement du fichier ' . $fileName . ' terminé.');
			return $this->uploadResourceToCKAN($api, $datasetId, $isUpdate, $resourceId, $resourceUrl, $fileName, $description);
		}
		else if ($type == 'xls' || $type == 'xlsx') {

			//if files > 50MB we don't do the treatments.
			if ($filesize < 50000000) {

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

				$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'MANAGE_FILE', 'SUCESS', 'Traitement du fichier ' . $fileName . ' terminé.');
				return $this->manageFileWithPath($datasetId, $generateColumns, $isUpdate, $resourceId, $resourceUrl, $description, $encoding);
			}
			else {
				$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'MANAGE_FILE', 'SUCESS', 'Traitement du fichier ' . $fileName . ' terminé.');
				return $this->uploadResourceToCKAN($api, $datasetId, $isUpdate, $resourceId, $resourceUrl, $fileName, $description);
			}
		}
		else if ($type == 'zip') {
			$resourceUrl = $this->manageZip($datasetId, $generateColumns, $isUpdate, $resourceUrl, $filePath, $encoding);
			$fileName = pathinfo($resourceUrl)["filename"];
			
			$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'MANAGE_FILE', 'SUCESS', 'Traitement du fichier ' . $fileName . ' terminé.');
			return $this->uploadResourceToCKAN($api, $datasetId, $isUpdate, $resourceId, $resourceUrl, $fileName, $description);

			
		}
		else if ($type == 'json' || $type == 'geojson' || $type == 'kml' || $type == 'shp') {
			$csv = $this->manageGeoFiles($type, $resourceUrl, $filePath);

			if ($csv != null) {
				$name = "csv_gen_" . $datasetId . "_" . uniqid();
				Logger::logMessage("Uploading CSV from GeoFile with name '" . $name . "'");
	
				$rootCsv = self::ROOT . 'sites/default/files/dataset/' . $name . '.csv';
				$resourceUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/sites/default/files/dataset/' . $name . '.csv';
	
				file_put_contents($rootCsv, $csv);

				$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'MANAGE_FILE', 'SUCESS', 'Traitement du fichier ' . $fileName . ' terminé.');
				return $this->manageFileWithPath($datasetId, $generateColumns, $isUpdate, $resourceId, $resourceUrl, '', $encoding);
			}

			$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'MANAGE_FILE', 'ERROR', "Le fichier '" . $fileName . "' ne peut pas être converti en CSV pour être intégré à l\'application.");
			throw new \Exception("Le fichier '" . $fileName . "' ne peut pas être converti en CSV pour être intégré à l\'application.");
		}
		else {
			$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'MANAGE_FILE', 'ERROR', 'Une erreur est survenue avec le fichier ' . $fileName . '.');
			Logger::logMessage("We do not process the file '" . $filePath . "'");
		}
	}
	
	function manageZip($datasetId, $generateColumns, $isUpdate, $resourceUrl, $filePath, $encoding) {
		Logger::logMessage("Manage zip file");
		// $path = pathinfo(realpath($filePath), PATHINFO_DIRNAME);

		$outputDirectory = '/home/user-client/drupal-d4c/sites/default/files/dataset/zip_extraction_' . uniqid();
		$zip = new ZipArchive;
		$res = $zip->open($filePath);
		if ($res === TRUE) {
			// extract it to the path we determined above
			$zip->extractTo($outputDirectory);
			$zip->close();

			return $this->manageFiles($datasetId, $generateColumns, $isUpdate, $resourceUrl, $outputDirectory, $encoding);
		}
		else {
			throw new \Exception('Le fichier ne peut pas être extrait.');
		}
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

			$scriptPath = '/home/user-client/drupal-d4c/modules/ckan_admin/src/Utils/convert_geo_files_ogr2ogr.sh';

			$typeConvert = 'GEOJSON';
			
			$rootJson='/home/user-client/drupal-d4c/sites/default/files/dataset/gen_'.uniqid().'.geojson';
			$command = $scriptPath." 2>&1 '" . $typeConvert . "' " . $rootJson . " " . $filePath . "";
			$message = shell_exec($command);
			$json = file_get_contents ($rootJson);

			$csv = $this->buildCSVFromGeojson($json);
			unlink ($rootJson);
		}
		else {
			throw new \Exception('Le type de fichier ' . $type . ' is not supported.');
		}

		return $csv;
	}

	function uploadResourceToCKAN($api, $datasetId, $isUpdate, $resourceId, $resourceUrl, $fileName, $description) {
		
		Logger::logMessage(($isUpdate ? "Updating " : "Uploading " ) . " resource on CKAN and monitoring the datapusher");
		$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'UPLOAD_CKAN', 'PENDING', 'Ajout des données dans le magasin de données.');
	
		if ($isUpdate) {
			$resource = [
				"id" => $resourceId,
				"url" => $resourceUrl,
				"name" => $fileName,
				"description" => $description,
				//TODO: Add format
				// "format" => "csv",
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

			// We monitore the datapusher
			$datapusherResult = $this->manageDatapusher($api, null, $resourceId, $resourceUrl, $fileName, true);

			// We reupload the dictionnary
			Logger::logMessage("Reuploading dictionnary.");
			$callUrl =  $this->urlCkan . "/api/action/datastore_create";
			$data = array();
			$data["resource_id"] = $resourceId;
			$data["force"] = true;
			$data["fields"] = $fieldsWithoutId;
			$data["uuid"] = uniqid();
			$api->updateRequest($callUrl, $data, "POST");

			if ($datapusherResult[$resourceId]['status'] == 'error') {
				$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'UPLOAD_CKAN', 'ERROR', "Impossible d'ajouter les données dans le magasin de données (" . $datapusherResult[$resourceId]['message'] . ")");
			}
			else if ($datapusherResult[$resourceId]['status'] == 'pending') {
				$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'UPLOAD_CKAN', 'PENDING', 'Les données sont encore en train d\'être ajoutées au magasin de données. Cette opération peut durer plusieurs minutes.');
			}
			else {
				$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'UPLOAD_CKAN', 'SUCCESS', '');
			}

			return $datapusherResult;
		}
		else {
			$resources = [    
				"package_id" => $datasetId,
				"url" => $resourceUrl,
				"description" => '',
				"name" => $fileName
				// Put this ?
				// "format" => 'csv'
			];
			$callUrluptres = $this->urlCkan . "/api/action/resource_create";
			$return = $api->updateRequest($callUrluptres, $resources, "POST");
			$return = json_decode($return);
			
			if ($return->success == true) {
				$resourceId = $return->result->id;
				$datapusherResult =  $this->manageDatapusher($api, null, $resourceId, $resourceUrl, $fileName, true);

				if ($datapusherResult[$resourceId]['status'] == 'error') {
					$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'UPLOAD_CKAN', 'ERROR', "Impossible d'ajouter les données dans le magasin de données (" . $datapusherResult[$resourceId]['message'] . ")");
				}
				else if ($datapusherResult[$resourceId]['status'] == 'pending') {
					$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'UPLOAD_CKAN', 'PENDING', 'Les données sont encore en train d\'être ajoutées au magasin de données. Cette opération peut durer plusieurs minutes.');
				}
				else {
					$this->updateDatabaseStatus(false, $datasetId, $datasetId, 'UPLOAD_CKAN', 'SUCCESS', '');
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
	 * According to the status, we do different action
	 * 	> Pending : We wait for 5 secondes and we check the status again. If the processus take longer than the constant DATAPUSHER_WAIT_TIME, we return the status 'pending'
	 *  > Error : If it is the first try, we try to push the file again in the datastore. If not we return the status 'error' and the associated message.
	 *  > Success : We return the status 'success'
	 * 
	 */
	function manageDatapusher($api, $startTime, $resourceId, $resourceUrl, $fileName, $firstTime) {
		//place this before any script you want to calculate time
		if ($startTime == null) {
			$startTime = microtime(true);
		}
		$currentTime = microtime(true);

		$result = array();

		if ($startTime + self::DATAPUSHER_WAIT_TIME < $currentTime) {
			Logger::logMessage("The datapusher has been running for more than " . self::DATAPUSHER_WAIT_TIME . " sec. We inform the user that the status is pending.");
			$result[$resourceId]['filename'] = $fileName;
			$result[$resourceId]['status'] = 'pending';
			$result[$resourceId]['resourceUrl'] = $resourceUrl;
			return $result;
		}

		// Check status datapusher
		$datapusherStatus = $api->getDatapusherJobStatus($resourceId);
		$datapusherStatus = json_decode($datapusherStatus);

		if ($datapusherStatus->status == 'pending') {
			sleep(5);
			return $this->manageDatapusher($api, $startTime, $resourceId, $resourceUrl, $fileName, $firstTime);
		}
		else if ($datapusherStatus->status == 'error') {
			if ($firstTime) {
				Logger::logMessage("The datapusher had an error, we try to push the file again.");
				$api->callDatapusher($resourceId);
				return $this->manageDatapusher($api, $startTime, $resourceId, $resourceUrl, $fileName, false);
			}
			else {
				Logger::logMessage("The datapusher had an error again (" . json_encode($datapusherStatus) . ").");
				$result[$resourceId]['filename'] = $fileName;
				$result[$resourceId]['status'] = 'error';
				$result[$resourceId]['message'] = $datapusherStatus->error->message;
				return $result;
			}
		}
		else if ($datapusherStatus->status == 'complete') {
			Logger::logMessage("The datapusher has inserted the file.");
			$result[$resourceId]['filename'] = $fileName;
			$result[$resourceId]['status'] = 'complete';
			$result[$resourceId]['resourceUrl'] = $resourceUrl;
			return $result;
		}
		else if ($datapusherStatus->status == null) {
			Logger::logMessage("An error occured during status's checking, we try to check the status again.");
			return $this->manageDatapusher($api, $startTime, $resourceId, $resourceUrl, $fileName, $firstTime);
		}
		else {
			throw new \Exception("Le datapusher a renvoyé un status inconnu '" . json_encode($datapusherStatus->status) . "', veuillez relancer l'insertion dans le datapusher.");
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
			$url_t = parse_url($file->url());
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
			$url_t = parse_url($file->url());
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
		$userlist[] = $userId;
		$userlist = array_unique($userlist);
		if(count($userlist) == 1){
			$userlist = array($userlist);
		}
		return array("roles" => array("administrator"), "users" => $userlist);
	}
	
	function defineExtras($extras, $picto, $imgBackground, $removeBackground, $linkDatasets, $theme, $themeLabel,
			$selectedTypeMap, $selectedOverlays, $dont_visualize_tab, $widgets, $visu, 
			$dateDataset, $disableFieldsEmpty, $analyseDefault, $security) {
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

				//We check if the key already exist
				if (!in_array($key, $cols)) {
					Logger::logMessage("Found column " . $key);

					$cols[] = $key;
					$colNames[] = $this->clearGeoProperties($key, $index);
					$index++;
				}
			}
			if ($feat["geometry"]["type"] != "Point") {
				$hasShapes = true;
			}
		}
		if ($hasShapes) {
			$cols[] = "coordinates";
			$cols[] = "geo_shape";
			$colNames[] = "coordinates";
			$colNames[] = "geo_shape";
		}
		else {
			$cols[] = "geo_point_2d";
			$colNames[] = "geo_point_2d";
		}
		
		$rows = array();
		$colsTypes = array();
		foreach($json["features"] as $feat){
			$row = array();
			foreach($cols as $col){
				if($col == "geo_point_2d"){
					$str = json_encode($feat["geometry"]["coordinates"]);
					preg_match('/\[([-]?[\d|.]+),([-]?[\d|.]+)/i', $str, $match);
					$val = '"'.$match[2] .",". $match[1].'"';
					$row[] = $val;
				} else if($col == "geo_shape") {
					$str = json_encode($feat["geometry"]);
					preg_match('/\[([-]?[\d|.]+),([-]?[\d|.]+)/i', $str, $match);
					$coord = '"' . $match[2] . "," . $match[1] . '"';

					//We replace " by "" to escape them
					$str = str_replace('"', "\"\"", $str);

					$row[] = $coord;
					$row[] = '"' . $str . '"';
				} else if($col == "coordinates"){
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
			// drupal_set_message('Les données ont été sauvegardées');
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
			throw new \Exception("Impossible de créer un nouveau jeu de données (" . json_encode($resnew->error->message) . ")");
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