<?php

namespace Drupal\ckan_admin\Model;

use JsonSerializable;

class Organization implements JsonSerializable {

    private $name;
    private $allowPrivate;
    
    public function __construct($name, $allowPrivate) {
        $this->name = $name;
        $this->allowPrivate = $allowPrivate;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getAllowPrivate() {
        return $this->allowPrivate;
    }

    public function setAllowPrivate($allowPrivate) {
        $this->allowPrivate = $allowPrivate;
    }

    public function getQuery() {
        return "(organization:(" . $this->getName() . ") AND (" . ($this->getAllowPrivate() ? "private:(true) OR private:(false)" : "private:(false)") . "))";
    }
    
    public function jsonSerialize() {
        return get_object_vars($this);
    }
}