<?php

/**
 * ForgotForm class.
 * ForgotForm is the data structure for keeping
 * forgot form data. It is used by the 'contact' action of 'SiteController'.
 */
class SendInvoicesEmailForm extends CFormModel {

    public $month;
    public $year;
    public $is_all_child;
    public $child_id;

    /**
     * Declares the validation rules.
     */
    public function rules() {
        return array(
            array('month, year', 'required'),
            array('child_id', 'checkSelectedChild')
        );
    }

    /**
     * Declares customized attribute labels.
     * If not declared here, an attribute would have a label that is
     * the same as its name with the first letter in upper case.
     */
    public function attributeLabels() {
        return array(
            'month' => 'Select Month',
            'year' => 'Select Year',
            'is_all_child' => 'Send For all Children',
            'child_id' => 'Select Child'
        );
    }

    public function checkSelectedChild($attributes, $params) {
        if (($this->is_all_child == 0) && empty($this->child_id)) {
            $this->addError('child_id', 'Please select a child.');
        }
    }

}
