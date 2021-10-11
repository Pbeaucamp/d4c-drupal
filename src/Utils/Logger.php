<?php

namespace Drupal\ckan_admin\Utils;

class Logger {

    static function logMessage($message) {
        $d = date("j-M-Y H:i:s e");
        error_log('[' . $d .'] ' . $message . "\r\n", 3, '/tmp/php_message.log');
    }
    
}