<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ParentsNds extends Parents {

	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public function defaultScope() {
		return array(
		);
	}

}