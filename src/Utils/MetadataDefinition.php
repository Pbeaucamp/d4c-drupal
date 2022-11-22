<?php

namespace Drupal\ckan_admin\Utils;

use JsonSerializable;

class MetadataDefinition implements JsonSerializable {

    private $metaKey;
    private $value;
    
    public function __construct($metaKey, $value) {
        $this->metaKey = $metaKey;
        $this->value = $value;
    }

    public function getMetaKey() {
        return $this->metaKey;
    }

    public function getValue() {
        return $this->value;
    }
    
    public function jsonSerialize() {
        return get_object_vars($this);
    }
}