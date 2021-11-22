<?php

class ChildPersonalDetailsNds extends ChildPersonalDetails {

	public function defaultScope() {
		return array();
	}

	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public function getName() {
		if ($this->is_active == 0 || $this->is_deleted == 1) {
			return $this->first_name . " " . $this->last_name . " *";
		} else {
			return $this->first_name . " " . $this->last_name;
		}
	}

	public function getSiblings(){
		return [];
	}

}