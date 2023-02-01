<?php

namespace Drupal\ckan_admin\Utils;

class DatasetHelper {

    static function extractMetadata($metadata, $key) {
        $metadata = array_filter($metadata, function ($f) use ($key) {
            return $f["key"] == $key;
        });
        return array_values($metadata)[0]["value"];
    }
}