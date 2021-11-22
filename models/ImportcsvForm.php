<?php

/**
 * ForgotForm class.
 * ForgotForm is the data structure for keeping
 * forgot form data. It is used by the 'contact' action of 'SiteController'.
 */
class ImportcsvForm extends CFormModel {

    /**
     * Declares the validation rules.
     */
    public function rules() {
        return array(
            array('csv_file', 'file', 'types' => 'csv', 'maxSize' => 5242880, 'allowEmpty' => true, 'wrongType' => 'Only csv allowed.', 'tooLarge' => 'File too large! 5MB is the limit')
        );
    }

    /**
     * Declares customized attribute labels.
     * If not declared here, an attribute would have a label that is
     * the same as its name with the first letter in upper case.
     */
    public function attributeLabels() {
        return array(
            'csv_file' => 'Upload CSV File',
        );
    }

}
