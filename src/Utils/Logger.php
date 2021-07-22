<?php

namespace Drupal\ckan_admin\Utils;

class Logger {

    static function logMessage($message) {
        error_log($message, 3, '/home/user-client/php_message.log');
        error_log("\r\n", 3, '/home/user-client/php_message.log');    
    }
    
}