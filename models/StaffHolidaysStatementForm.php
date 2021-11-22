<?php

/**
 * ForgotForm class.
 * ForgotForm is the data structure for keeping
 * forgot form data. It is used by the 'contact' action of 'SiteController'.
 */
class StaffHolidaysStatementForm extends CFormModel
{

    public $to;

    public $subject;

    public $message;

    /**
     * Declares the validation rules.
     */
    public function rules()
    {
        return array(
            array(
                'to, subject, message',
                'required'
            ),
            array(
                'to', 'checkValidEmailAddress'
            )
        );
    }

    /**
     * Declares customized attribute labels.
     * If not declared here, an attribute would have a label that is
     * the same as its name with the first letter in upper case.
     */
    public function attributeLabels()
    {
        return array(
            'to' => 'To (Please specify comman seperated email address.)',
            'subject' => 'Subject',
            'message' => 'Message'
        );
    }

    public function checkValidEmailAddress($attributes, $params)
    {
        if (isset($this->to) && ! empty(trim($this->to))) {
            $to = explode(",", $this->to);
            foreach ($to as $email) {
                if (filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false) {
                    $this->addError('to', $email." is not a valid email address.");
                    return false;
                }
            }
        }
    }
}
