<?php

/**
 * ForgotForm class.
 * ForgotForm is the data structure for keeping
 * forgot form data. It is used by the 'contact' action of 'SiteController'.
 */
class VerifyForm extends CFormModel {

    public $password;
    public $repeatPassword;

    /**
     * Declares the validation rules.
     */
    public function rules() {
        return array(
            array('password, repeatPassword', 'required'),
            array('repeatPassword', 'compare', 'compareAttribute' => 'password', 'message' => "Passwords don't match"),
        );
    }

    /**
     * Declares customized attribute labels.
     * If not declared here, an attribute would have a label that is
     * the same as its name with the first letter in upper case.
     */
    public function attributeLabels() {
        return array(
            'password' => 'New Password',
            'repeatPassword' => 'Re-enter new Password',
        );
    }

}
