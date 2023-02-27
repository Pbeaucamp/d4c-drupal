<?php

namespace Drupal\ckan_admin\Utils;

class PropertiesHelper {

  // Create constants for the keys of the properties we want to use.
  const PACKAGE_DOWNLOAD_LIMIT = 'package_download_limit';
  const CO_LINKED_SURVEYS = 'co_linked_surveys';
  const MESSAGE_RGPD = 'message_rgpd';

  private $properties;

  public function __construct() {
    $this->properties = json_decode(file_get_contents(__DIR__ ."/../../properties.json"), true);
  }

  public function getProperty($key) {
    return $this->properties[$key];
  }

  public function setProperty($key, $value) {
    $this->properties[$key] = $value;
    file_put_contents(__DIR__ . "/../../properties.json", json_encode($this->properties, JSON_PRETTY_PRINT));
  }
}