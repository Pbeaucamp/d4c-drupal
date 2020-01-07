<?php

use Drupal\ckan_admin\Utils\DataSet;

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

// Specify relative path to the drupal root.
$autoloader = require_once '/opt/drupal-anfr/web/autoload.php';
$request = Request::createFromGlobals();
// Bootstrap drupal to different levels
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
$kernel->boot();

$kernel->prepareLegacyRequest($request);

DataSet::sendDataSetCron();
