<?php

namespace Drupal\ckan_admin\Model;

use JsonSerializable;

class D4CMetadata implements JsonSerializable {

    private $key;
    private $value;
    private $isDefine = false;
    private $addToData = false;

    public function __construct($key, $value) {
        $this->key = $key;
        $this->value = $value;
    }

    public function getKey() {
        return $this->key;
    }

    public function getValue() {
        return $this->value;
    }

    public function isDefine() {
        return $this->isDefine;
    }

    public function setDefine($isDefine) {
        $this->isDefine = $isDefine;
    }

    public function addToData() {
        return $this->addToData;
    }

    public function setAddToData($addToData) {
        $this->addToData = $addToData;
    }
    
    public function jsonSerialize() {
        return get_object_vars($this);
    }
}