<?php

namespace Drupal\ckan_admin\Model;

use JsonSerializable;

class Schedule implements JsonSerializable {

	private $workflowId;

    private $isOn;

    /* Possible values: YEAR, MONTH, WEEK, DAY, HOUR, MINUTE */
	private $period;
	private $interval;

	private $beginDate;
	private $stopDate;

    public function __construct($isOn, $period, $interval, $beginDate, $stopDate = null) {
        $this->isOn = $isOn;
        $this->period = $period;
        $this->interval = $interval;
        $this->beginDate = $beginDate;
        $this->stopDate = $stopDate;
    }

    public function isOn() {
        return $this->isOn;
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
        $array = array();
        $array['on'] = $this->isOn;
        $array['period'] = $this->period;
        $array['interval'] = $this->interval;
        // Format date in string as ISO 8601 format
        $array['beginDate'] = $this->beginDate->format(\DateTime::ISO8601);
        if ($this->stopDate != null) {
            $array['stopDate'] = $this->stopDate;
        }
        return $array;
    }
}