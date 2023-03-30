<?php

use Drupal\ckan_admin\Utils\Logger;

namespace Drupal\ckan_admin\Utils;

use FilesystemIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;

class KpisHelper {

    protected $config;
    
	function __construct() {
		$this->config = include(__DIR__ . "/../../config.php");
    }

    function getDatastoreSpace() {
        try {
            // Database data
            $tableSchema = 'datastore';
            $database = \Drupal\Core\Database\Database::getConnection('ckan', 'ckan');
            $query = $database->query("SELECT pg_database_size('$tableSchema')");
            $result = $query->fetchAssoc();

            $databaseSize = $result['pg_database_size'];

            Logger::logMessage("Datastore database size: $databaseSize");
            return $databaseSize;
        } catch (\Exception $e) {
            Logger::logMessage("Error getting the datastore space: " . $e->getMessage());
        }

        return 0;
    }

    function getDatasetFolderSpace() {
        try {
            // Folder data
            $datasetFolder = $this->config->client->drupal_root . $this->config->client->routing_prefix . '/sites/default/files/dataset/';
            $datasetFolderSize = $this->getDirectorySize($datasetFolder);

            Logger::logMessage("Dataset folder size: $datasetFolderSize");
            return $datasetFolderSize;
        } catch (\Exception $e) {
            Logger::logMessage("Error getting the dataset folder space: " . $e->getMessage());
        }

        return 0;
    }

    function getDrupalSpace($datasetFolderSpace) {
        try {
            // Folder data
            $drupalFolder = $this->config->client->drupal_root;
            $drupalFolderSize = $this->getDirectorySize($drupalFolder);

            $drupalFolderSize -= $datasetFolderSpace;

            Logger::logMessage("Drupal folder size: $drupalFolderSize");
            return $drupalFolderSize;
        } catch (\Exception $e) {
            Logger::logMessage("Error getting the drupal space: " . $e->getMessage());
        }

        return 0;
    }

    function getDirectorySize($path){
        $bytestotal = 0;
        $path = realpath($path);
        if($path!==false && $path!='' && file_exists($path)){
            foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object){
                $bytestotal += $object->getSize();
            }
        }
        return $bytestotal;
    }

    function getNbDatasets() {
		$organizationName = $this->config->client->client_organisation;

        try {
            $apiManager = new Api();
            $result = $apiManager->getOrganization("id=" . $organizationName);

            if ($result["success"] == true) {
                return (int) $result["result"]["package_count"];
            }
        } catch (\Exception $e) {
            Logger::logMessage("Error getting the number of datasets: " . $e->getMessage());
        }

        return 0;
    }

    function getNbDatasetsVisu() {
        $apiManager = new Api();
        try {
            $params = "";
            $params .= "rows=0";
            $params .= "&start=0";
            $params .= "&fq=data4citizen-type:(visualization)";
            $result = $apiManager->getExtendedPackageSearch($params);

            if ($result["success"] == true) {
                return (int) $result["result"]["count"];
            }
        } catch (\Exception $e) {
            Logger::logMessage("Error getting the number of visualizations: " . $e->getMessage());
        }
        return 3;
    }
}