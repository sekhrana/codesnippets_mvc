<?php

/**
 * ForgotForm class.
 * ForgotForm is the data structure for keeping
 * forgot form data. It is used by the 'contact' action of 'SiteController'.
 */
class ChildBalanceStatementForm extends CFormModel {

    public $to;
    public $from;
    public $reply_to;
    public $subject;
    public $message;
    public $send_me;

    /**
     * Declares the validation rules.
     */
    public function rules() {
        return array(
            array('to, from, reply_to, subject, message', 'required'),
        );
    }

    /**
     * Declares customized attribute labels.
     * If not declared here, an attribute would have a label that is
     * the same as its name with the first letter in upper case.
     */
    public function attributeLabels() {
        return array(
            'to' => 'To',
            'from' => 'From',
            'reply_to' => 'Reply to',
            'subject' => 'Subject',
            'message' => 'Message',
            'send_me' => 'Send me a copy',
        );
    }

}
