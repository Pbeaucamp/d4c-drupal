<?php

namespace Drupal\ckan_admin\Utils;

class DatabaseHelper {

  function testQuery($databaseTarget, $databaseKey, $query, $limit = 1) {
    try {
			$database = \Drupal\Core\Database\Database::getConnection($databaseTarget, $databaseKey);
			$query = $database->query($query . ($limit != null ? " LIMIT " . $limit : ""));
      $query->execute();
		}  catch (\Exception $e) {
			throw $e;
		}
  }

  function executeQuery($databaseTarget, $databaseKey, $query, $limit = 20) {
    $database = \Drupal\Core\Database\Database::getConnection($databaseTarget, $databaseKey);
    $query = $database->query($query . " LIMIT 10");
    return $query->fetchAll();
  }
}