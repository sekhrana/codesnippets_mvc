<?php

class StaffPersonalDetailsNds extends StaffPersonalDetails {

	public function defaultScope() {
		return array();
	}

	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

}
