<?php

namespace Drupal\ckan_admin\Utils;
use Drupal\ckan_admin\Utils\Logger;

/**
 * This class manage the creation of CSW node if enable in config.php
 */
class CSWManager {

	const NODE_FOLDER = '{$NODE_FOLDER}';
	const FLUX_NAME = '{$FLUX_NAME}';

	function __construct() {
		$this->config = include(__DIR__ . "/../../config.php");
		$this->isCSWEnabled = $this->config->csw->enabled;
		$this->cswServerPath = $this->config->csw->csw_server_path;
		$this->cswModel = $this->config->csw->csw_model;
	}

	function buildCSWNode($organizationName, $organizationTitle) {
		//If Node creation is disabled we do nothing
		if (!$this->isCSWEnabled) {
			Logger::logMessage("CSW - CSW node creation is disabled.");
			return;
		}

		//Checking that the node does not exist yet
		$cswNodePath = $this->cswServerPath . '/csw/' . $organizationName . '.php';
		Logger::logMessage("CSW - Checking if '" . $cswNodePath . "' exist.");
		if (file_exists($cswNodePath)) {
			Logger::logMessage("CSW - Node exist aldready.");
			return;
		}

		//We create the folder with the organization name
		$cswNodeFolder = $this->cswServerPath . '/nodes/' . $organizationName;
		Logger::logMessage("CSW - Creating node folder '" . $cswNodeFolder . "' if it does not exist.");
		if (!file_exists($cswNodeFolder )) {
			mkdir($cswNodeFolder, 0777, true);
		}

		//We copy the model to create a new node
		$modelNode = __DIR__ . "/../../" . $this->cswModel;
		Logger::logMessage("CSW - Creating node '" . $cswNodePath . "' with model '" . $modelNode . "' and copy in CSW server.");
		copy($modelNode, $cswNodePath);
		Logger::logMessage("CSW - Updating constant in CSW Node with Organization name '" . $organizationName . "' and Organization title '" . $organizationTitle . "'.");
		file_put_contents($cswNodePath, str_replace(self::NODE_FOLDER, $organizationName, file_get_contents($cswNodePath)));
		file_put_contents($cswNodePath, str_replace(self::FLUX_NAME, $organizationTitle, file_get_contents($cswNodePath)));

		Logger::logMessage("CSW - Node '" . $cswNodePath . "' is created and ready.");
	}
}
