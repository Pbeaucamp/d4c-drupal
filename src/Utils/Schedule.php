<?php

namespace Drupal\ckan_admin\Utils;

use JsonSerializable;

class Schedule implements JsonSerializable {

	private $workflowId;

    /* Possible values: YEAR, MONTH, WEEK, DAY, HOUR, MINUTE */
	private $period;
	private $interval;

	private $beginDate;
	private $stopDate;

    public function __construct($period, $interval, $beginDate, $stopDate = null) {
        $this->period = $period;
        $this->interval = $interval;
        $this->beginDate = $beginDate;
        $this->stopDate = $stopDate;
    }

    public function getWorkflowId() {
        return $this->workflowId;
    }

    public function setWorkflowId($workflowId) {
        $this->workflowId = $workflowId;
    }

    public function getPeriod() {
        return $this->period;
    }

    public function getInterval() {
        return $this->interval;
    }

    public function getBeginDate() {
        return $this->beginDate;
    }

    public function getStopDate() {
        return $this->stopDate;
    }
    
    public function jsonSerialize() {
        return get_object_vars($this);
    }
}