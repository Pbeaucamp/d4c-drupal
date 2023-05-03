<?php

namespace Drupal\ckan_admin\Controller;

use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\Logger;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a route controller for watches autocomplete form elements.
 */
class AutocompleteController extends ControllerBase {

  /**
   * Handler for autocomplete request.
   */
  public function handleAutocomplete(Request $request) {
    $organization = $request->query->get('organization');
    $input = $request->query->get('q');

    // Logger::logMessage("AutocompleteController:handleAutocomplete: organization: $organization, input: $input");
    
    // Get the typed string from the URL, if it exists.
    if (!$input) {
        return new JsonResponse($input);
    }

    $query = "q=name:\"$input\" OR title:\"$input\"&include_private=false&rows=20&sort=title_string asc";

    $api = new API();
    $datasets = $api->callPackageSearch_public_private($query, null, $organization);

    $datasets = $datasets->getContent();
    $datasets = json_decode($datasets, true);
    $datasets = $datasets[result][results];
    
    $datasetOptions = array();
    // $datasetOptions["-1"] = "----";
    foreach($datasets as &$ds) {
        $item = array();
        $item["value"] = $ds[name];
        $item["label"] = $ds[title];
        $datasetOptions[] = $item;
    }

    return new JsonResponse($datasetOptions);
  }
}