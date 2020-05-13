<?php

namespace Drupal\ckan_admin\Utils;

class Logger {

    function logMessage($message) {
        error_log($message, 3, '/home/user-client/php_message.log');   
    }
    
}