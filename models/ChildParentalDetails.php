<?php

/**
 * This is the model class for table "tbl_child_parental_details".
 *
 * The followings are the available columns in table 'tbl_child_parental_details':
 * @property integer $id
 * @property integer $child_id
 * @property string $p1_title
 * @property string $p1_first_name
 * @property string $p1_last_name
 * @property string $p1_address_1
 * @property string $p1_address_2
 * @property string $p1_address_3
 * @property string $p1_postcode
 * @property string $p1_home_phone
 * @property string $p1_mobile_phone
 * @property string $p1_email
 * @property string $p1_relationship
 * @property string $p1_employer
 * @property string $p1_department
 * @property string $p1_occupation
 * @property string $p1_work_phone
 * @property string $p1_dob
 * @property string $p1_disability
 * @property integer $p1_is_authorised
 * @property integer $p1_is_bill_payer
 * @property string $p1_profile_photo
 * @property string $p1_gocardless_customer_id
 * @property string $p1_gocardless_customer
 * @property string $p1_gocardless_mandate
 * @property string $p2_title
 * @property string $p2_first_name
 * @property string $p2_last_name
 * @property string $p2_address_1
 * @property string $p2_address_2
 * @property string $p2_address_3
 * @property string $p2_postcode
 * @property string $p2_home_phone
 * @property string $p2_mobile_phone
 * @property string $p2_email
 * @property string $p2_relationship
 * @property string $p2_employer
 * @property string $p2_department
 * @property string $p2_occupation
 * @property string $p2_work_phone
 * @property string $p2_dob
 * @property string $p2_disability
 * @property integer $p2_is_authorised
 * @property integer $p2_is_bill_payer
 * @property string $p2_profile_photo
 * @property string $p2_gocardless_customer_id
 * @property string $p2_gocardless_customer
 * @property string $p2_gocardless_mandate
 * @property string $p3_title
 * @property string $p3_first_name
 * @property string $p3_last_name
 * @property string $p3_mobile_phone
 * @property string $p3_email
 * @property string $p3_relationship
 * @property string $p4_title
 * @property string $p4_first_name
 * @property string $p4_last_name
 * @property string $p4_mobile_phone
 * @property string $p4_email
 * @property string $p4_relationship
 * @property integer $p3_is_authorised
 * @property integer $p4_is_authorised
 * @property string $p3_profile_photo
 * @property string $p4_profile_photo
 * @property string $p5_title
 * @property string $p5_first_name
 * @property string $p5_last_name
 * @property string $p5_mobile_phone
 * @property string $p5_email
 * @property string $p5_relationship
 * @property integer $p5_is_authorised
 * @property string $p6_title
 * @property string $p6_first_name
 * @property string $p6_last_name
 * @property string $p6_mobile_phone
 * @property string $p6_email
 * @property string $p6_relationship
 * @property integer $p6_is_authorised
 * @property string $p5_profile_photo
 * @property string $p6_profile_photo
 *
 * The followings are the available model relations:
 * @property ChildPersonalDetails $child
 */
class ChildParentalDetails extends CActiveRecord {

    const CHILDREN_API_PATH = "/api-eyman/children";

    public $prevProfilePhoto1;
    public $prevProfilePhoto2;
    public $prevProfilePhoto3;
    public $prevProfilePhoto4;
    public $prevProfilePhoto5;
    public $prevProfilePhoto6;
    public $date_columns = array('p1_dob', 'p2_dob');

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'tbl_child_parental_details';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('child_id, p1_first_name', 'required'),
            array('child_id, p1_is_authorised, p1_home_phone, p2_home_phone,p1_mobile_phone, p2_mobile_phone ,p1_is_bill_payer, p2_is_authorised, p2_is_bill_payer, p5_is_authorised, p6_is_authorised, p3_is_authorised, p4_is_authorised', 'numerical', 'integerOnly' => true),
            array('p1_title, p1_address_1, p1_address_2, p1_address_3, p1_postcode, p1_home_phone, p1_mobile_phone, p1_relationship, p1_employer, p1_department, p1_occupation, p1_work_phone, p1_disability, p2_title, p2_address_1, p2_address_2, p2_address_3, p2_postcode, p2_home_phone, p2_mobile_phone, p2_relationship, p2_employer, p2_department, p2_occupation, p2_work_phone, p2_disability, p3_title, p3_mobile_phone, p3_relationship, p4_title, p4_mobile_phone, p4_relationship, p5_title, p5_mobile_phone, p5_relationship, p6_title, p6_mobile_phone, p6_relationship, p4_profile_photo, p3_profile_photo, p5_profile_photo, p6_profile_photo', 'length', 'max' => 45),
            array('p1_first_name, p1_last_name, p1_email, p2_first_name, p2_last_name, p2_email, p3_first_name, p3_last_name, p3_email, p4_first_name, p4_last_name, p4_email, p5_first_name, p5_last_name, p5_email, p6_first_name, p6_last_name, p6_email', 'length', 'max' => 255),
            array('p1_profile_photo,p2_profile_photo,p3_profile_photo,p4_profile_photo,p5_profile_photo,p6_profile_photo', 'file', 'allowEmpty' => true, 'types' => 'jpg,jpeg,gif,png', 'on' => 'insert,update'),
            array('p1_email,p2_email', 'email'),
            //array('p1_email,p2_email', 'unique', 'message' => 'Parent with same email id already exists!'),
            array('p1_dob, p2_dob', 'date', 'format' => 'dd-mm-yyyy', 'message' => 'Please input a valid date type.', 'allowEmpty' => true),
            array('p2_dob, p1_dob', 'default', 'setOnEmpty' => true, 'value' => NULL),
            array('p1_email', 'checkUniqueEmail'),
            array('p2_title, p2_first_name, p2_last_name, p2_email', 'checkParent2MandatoryFields'),
            //array('p1_home_phone, p1_mobile_phone, p2_home_phone, p2_mobile_phone, p3_mobile_phone, p4_mobile_phone, p5_mobile_phone, p6_mobile_phone', 'numerical', 'integerOnly' => true, 'message' => 'Only numbers are allowed'),
            array('p1_home_phone, p1_mobile_phone, p2_home_phone, p2_mobile_phone, p3_mobile_phone, p4_mobile_phone, p5_mobile_phone, p6_mobile_phone', 'length', 'max' => 50, 'message' => 'Please input a valid phone number'),
            array('p1_email', 'validateParentContactFields', 'filter', 'filter' => 'trim'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id, child_id, p1_title, p1_first_name, p1_last_name, p1_address_1, p1_address_2, p1_address_3, p1_postcode, p1_home_phone, p1_mobile_phone, p1_email, p1_relationship, p1_employer, p1_department, p1_occupation, p1_work_phone, p1_dob, p1_disability, p1_is_authorised, p1_is_bill_payer, p1_profile_photo, p1_gocardless_customer_id, p1_gocardless_customer, p1_gocardless_mandate, p2_title, p2_first_name, p2_last_name, p2_address_1, p2_address_2, p2_address_3, p2_postcode, p2_home_phone, p2_mobile_phone, p2_email, p2_relationship, p2_employer, p2_department, p2_occupation, p2_work_phone, p2_dob, p2_disability, p2_is_authorised, p2_is_bill_payer, p2_profile_photo, p2_gocardless_customer_id, p2_gocardless_customer, p2_gocardless_mandate, p3_title, p3_first_name, p3_last_name, p3_mobile_phone, p3_email, p3_relationship, p3_is_authorised, p4_is_authorised , p4_title, p4_first_name, p4_last_name, p4_mobile_phone, p4_email, p4_relationship, p5_title, p5_first_name, p5_last_name, p5_mobile_phone, p5_email, p5_relationship, p5_is_authorised, p6_title, p6_first_name, p6_last_name, p6_mobile_phone, p6_email, p6_relationship, p6_is_authorised, p4_profile_photo, p3_profile_photo, p5_profile_photo, p6_profile_photo', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'child' => array(self::BELONGS_TO, 'ChildPersonalDetails', 'child_id'),
            'title' => array(self::BELONGS_TO, 'PickTitle', 'title'),
        );
    }

    public function behaviors() {
        return array(
            'dateFormatter' => array(
                'class' => 'application.components.DateFormatter',
                'date_columns' => array('p1_dob', 'p2_dob')
            )
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'child_id' => 'Child',
            'p1_title' => 'Title',
            'p1_first_name' => 'First Name',
            'p1_last_name' => 'Last Name',
            'p1_address_1' => 'Address 1',
            'p1_address_2' => 'Address 2',
            'p1_address_3' => 'Address 3',
            'p1_postcode' => 'Postcode',
            'p1_home_phone' => 'Home Phone',
            'p1_mobile_phone' => 'Mobile Phone',
            'p1_email' => 'Email',
            'p1_relationship' => 'Relationship',
            'p1_employer' => 'Employer',
            'p1_department' => 'Department',
            'p1_occupation' => 'Occupation',
            'p1_work_phone' => 'Work Phone',
            'p1_dob' => 'Date of Birth',
            'p1_disability' => 'Disability',
            'p1_is_authorised' => 'Authorised to collect',
            'p1_is_bill_payer' => 'Bill Payer',
            'p1_profile_photo' => 'Profile Photo',
            'p2_title' => 'Title',
            'p2_first_name' => 'First Name',
            'p2_last_name' => 'Last Name',
            'p2_address_1' => 'Address 1',
            'p2_address_2' => 'Address 2',
            'p2_address_3' => 'Address 3',
            'p2_postcode' => 'Postcode',
            'p2_home_phone' => 'Home Phone',
            'p2_mobile_phone' => 'Mobile Phone',
            'p2_email' => 'Email',
            'p2_relationship' => 'Relationship',
            'p2_employer' => 'Employer',
            'p2_department' => 'Department',
            'p2_occupation' => 'Occupation',
            'p2_work_phone' => 'Work Phone',
            'p2_dob' => 'Date of Birth',
            'p2_disability' => 'Disability',
            'p2_is_authorised' => 'Authorised to collect',
            'p2_is_bill_payer' => 'Bill Payer',
            'p2_profile_photo' => 'Profile Photo',
            'p3_title' => 'Title',
            'p3_first_name' => 'First Name',
            'p3_last_name' => 'Last Name',
            'p3_mobile_phone' => 'Mobile Phone',
            'p3_email' => 'Email',
            'p3_relationship' => 'Relationship',
            'p4_title' => 'Title',
            'p4_first_name' => 'First Name',
            'p4_last_name' => 'Last Name',
            'p4_mobile_phone' => 'Mobile Phone',
            'p4_email' => 'Email',
            'p4_relationship' => 'Relationship',
            'p3_profile_photo' => 'Profile Photo',
            'p4_profile_photo' => 'Profile Photo',
            'p5_profile_photo' => 'Profile Photo',
            'p6_profile_photo' => 'Profile Photo',
            'p5_title' => 'Title',
            'p5_first_name' => 'First Name',
            'p5_last_name' => 'Last Name',
            'p5_mobile_phone' => 'Mobile Phone',
            'p5_email' => 'Email',
            'p5_relationship' => 'Relationship',
            'p5_is_authorised' => 'Authorised to collect',
            'p6_title' => 'Title',
            'p6_first_name' => 'First Name',
            'p6_last_name' => 'Last Name',
            'p6_mobile_phone' => 'Mobile Phone',
            'p6_email' => 'Email',
            'p6_relationship' => 'Relationship',
            'p6_is_authorised' => 'Authorised to collect',
            'p3_is_authorised' => 'Authorised to collect',
            'p4_is_authorised' => 'Authorised to collect',
        );
    }

    public function attributeLabelsForReport($key) {
        $labels = array(
            'id' => 'ID',
            'child_id' => 'Child',
            'p1_title' => 'P1 Title',
            'p1_first_name' => 'P1 First Name',
            'p1_last_name' => 'P1 Last Name',
            'p1_address_1' => 'P1 Address 1',
            'p1_address_2' => 'P1 Address 2',
            'p1_address_3' => 'P1 Address 3',
            'p1_postcode' => 'P1 Postcode',
            'p1_home_phone' => 'P1 Home Phone',
            'p1_mobile_phone' => 'P1 Mobile Phone',
            'p1_email' => 'P1 Email',
            'p1_relationship' => 'P1 Relationship',
            'p1_employer' => 'P1 Employer',
            'p1_department' => 'P1 Department',
            'p1_occupation' => 'P1 Occupation',
            'p1_work_phone' => 'P1 Work Phone',
            'p1_dob' => 'P1 Date of Birth',
            'p1_disability' => 'P1 Disability',
            'p1_is_authorised' => 'P1 Authorised to collect',
            'p1_is_bill_payer' => 'P1 Bill Payer',
            'p1_profile_photo' => 'P1 Profile Photo',
            'p1_gocardless_customer_id' => 'GoCardless Customer ID',
            'p1_gocardless_customer' => 'P1 GoCardless Customer',
            'p1_gocardless_mandate' => 'P1 GoCardless Mandate',
            'p2_title' => 'P2 Title',
            'p2_first_name' => 'P2 First Name',
            'p2_last_name' => 'P2 Last Name',
            'p2_address_1' => 'P2 Address 1',
            'p2_address_2' => 'P2 Address 2',
            'p2_address_3' => 'P2 Address 3',
            'p2_postcode' => 'P2 Postcode',
            'p2_home_phone' => 'P2 Home Phone',
            'p2_mobile_phone' => 'P2 Mobile Phone',
            'p2_email' => 'P2 Email',
            'p2_relationship' => 'P2 Relationship',
            'p2_employer' => 'P2 Employer',
            'p2_department' => 'P2 Department',
            'p2_occupation' => 'P2 Occupation',
            'p2_work_phone' => 'P2 Work Phone',
            'p2_dob' => 'P2 Date of Birth',
            'p2_disability' => 'P2 Disability',
            'p2_is_authorised' => 'P2 Authorised to collect',
            'p2_is_bill_payer' => 'P2 Bill Payer',
            'p2_profile_photo' => 'P2 Profile Photo',
            'p2_gocardless_customer_id' => 'P2 GoCardless Customer ID',
            'p2_gocardless_customer' => 'P2 GoCardless Customer',
            'p2_gocardless_mandate' => 'P2 GoCardless Mandate',
            'p3_title' => 'P3 Title',
            'p3_first_name' => 'P3 First Name',
            'p3_last_name' => 'P3 Last Name',
            'p3_mobile_phone' => 'P3 Mobile Phone',
            'p3_email' => 'P3 Email',
            'p3_relationship' => 'P3 Relationship',
            'p4_title' => 'P4 Title',
            'p4_first_name' => 'P4 First Name',
            'p4_last_name' => 'P4 Last Name',
            'p4_mobile_phone' => 'P4 Mobile Phone',
            'p4_email' => 'P4 Email',
            'p4_relationship' => 'P4 Relationship',
            'p3_profile_photo' => 'P3 Profile Photo',
            'p4_profile_photo' => 'P4 Profile Photo',
            'p5_profile_photo' => 'P5 Profile Photo',
            'p6_profile_photo' => 'P6 Profile Photo',
            'p5_title' => 'P5 Title',
            'p5_first_name' => 'P5 First Name',
            'p5_last_name' => 'P5 Last Name',
            'p5_mobile_phone' => 'P5 Mobile Phone',
            'p5_email' => 'P5 Email',
            'p5_relationship' => 'P5 Relationship',
            'p5_is_authorised' => 'P5 Authorised to collect',
            'p6_title' => 'P6 Title',
            'p6_first_name' => 'P6 First Name',
            'p6_last_name' => 'P6 Last Name',
            'p6_mobile_phone' => 'P6 Mobile Phone',
            'p6_email' => 'P6 Email',
            'p6_relationship' => 'P6 Relationship',
            'p6_is_authorised' => 'P6 Authorised to collect',
            'p3_is_authorised' => 'P3 Authorised to collect',
            'p4_is_authorised' => 'P4 Authorised to collect',
        );
        return $labels[$key];
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * Typical usecase:
     * - Initialize the model fields with values from filter form.
     * - Execute this method to get CActiveDataProvider instance which will filter
     * models according to data in model fields.
     * - Pass data provider to CGridView, CListView or any similar widget.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
     */
    public function search() {
        // @todo Please modify the following code to remove attributes that should not be searched.

        $criteria = new CDbCriteria;

        $criteria->compare('id', $this->id);
        $criteria->compare('child_id', $this->child_id);
        $criteria->compare('p1_title', $this->p1_title, true);
        $criteria->compare('p1_first_name', $this->p1_first_name, true);
        $criteria->compare('p1_last_name', $this->p1_last_name, true);
        $criteria->compare('p1_address_1', $this->p1_address_1, true);
        $criteria->compare('p1_address_2', $this->p1_address_2, true);
        $criteria->compare('p1_address_3', $this->p1_address_3, true);
        $criteria->compare('p1_postcode', $this->p1_postcode, true);
        $criteria->compare('p1_home_phone', $this->p1_home_phone, true);
        $criteria->compare('p1_mobile_phone', $this->p1_mobile_phone, true);
        $criteria->compare('p1_email', $this->p1_email, true);
        $criteria->compare('p1_relationship', $this->p1_relationship, true);
        $criteria->compare('p1_employer', $this->p1_employer, true);
        $criteria->compare('p1_department', $this->p1_department, true);
        $criteria->compare('p1_occupation', $this->p1_occupation, true);
        $criteria->compare('p1_work_phone', $this->p1_work_phone, true);
        $criteria->compare('p1_dob', $this->p1_dob, true);
        $criteria->compare('p1_disability', $this->p1_disability, true);
        $criteria->compare('p1_is_authorised', $this->p1_is_authorised);
        $criteria->compare('p1_is_bill_payer', $this->p1_is_bill_payer);
        $criteria->compare('p1_profile_photo', $this->p1_profile_photo, true);
        $criteria->compare('p1_gocardless_customer_id', $this->p1_gocardless_customer_id, true);
        $criteria->compare('p1_gocardless_customer', $this->p1_gocardless_customer, true);
        $criteria->compare('p1_gocardless_mandate', $this->p1_gocardless_mandate, true);
        $criteria->compare('p2_title', $this->p2_title, true);
        $criteria->compare('p2_first_name', $this->p2_first_name, true);
        $criteria->compare('p2_last_name', $this->p2_last_name, true);
        $criteria->compare('p2_address_1', $this->p2_address_1, true);
        $criteria->compare('p2_address_2', $this->p2_address_2, true);
        $criteria->compare('p2_address_3', $this->p2_address_3, true);
        $criteria->compare('p2_postcode', $this->p2_postcode, true);
        $criteria->compare('p2_home_phone', $this->p2_home_phone, true);
        $criteria->compare('p2_mobile_phone', $this->p2_mobile_phone, true);
        $criteria->compare('p2_email', $this->p2_email, true);
        $criteria->compare('p2_relationship', $this->p2_relationship, true);
        $criteria->compare('p2_employer', $this->p2_employer, true);
        $criteria->compare('p2_department', $this->p2_department, true);
        $criteria->compare('p2_occupation', $this->p2_occupation, true);
        $criteria->compare('p2_work_phone', $this->p2_work_phone, true);
        $criteria->compare('p2_dob', $this->p2_dob, true);
        $criteria->compare('p2_disability', $this->p2_disability, true);
        $criteria->compare('p2_is_authorised', $this->p2_is_authorised);
        $criteria->compare('p2_is_bill_payer', $this->p2_is_bill_payer);
        $criteria->compare('p2_profile_photo', $this->p2_profile_photo, true);
        $criteria->compare('p2_gocardless_customer_id', $this->p2_gocardless_customer_id, true);
        $criteria->compare('p2_gocardless_customer', $this->p2_gocardless_customer, true);
        $criteria->compare('p2_gocardless_mandate', $this->p2_gocardless_mandate, true);
        $criteria->compare('p3_email', $this->p3_email, true);
        $criteria->compare('p3_relationship', $this->p3_relationship, true);
        $criteria->compare('p4_title', $this->p4_title, true);
        $criteria->compare('p4_first_name', $this->p4_first_name, true);
        $criteria->compare('p4_last_name', $this->p4_last_name, true);
        $criteria->compare('p4_mobile_phone', $this->p4_mobile_phone, true);
        $criteria->compare('p4_email', $this->p4_email, true);
        $criteria->compare('p4_relationship', $this->p4_relationship, true);
        $criteria->compare('p5_title', $this->p5_title, true);
        $criteria->compare('p5_first_name', $this->p5_first_name, true);
        $criteria->compare('p5_last_name', $this->p5_last_name, true);
        $criteria->compare('p5_mobile_phone', $this->p5_mobile_phone, true);
        $criteria->compare('p5_email', $this->p5_email, true);
        $criteria->compare('p5_relationship', $this->p5_relationship, true);
        $criteria->compare('p5_is_authorised', $this->p5_is_authorised);
        $criteria->compare('p6_title', $this->p6_title, true);
        $criteria->compare('p6_first_name', $this->p6_first_name, true);
        $criteria->compare('p6_last_name', $this->p6_last_name, true);
        $criteria->compare('p6_mobile_phone', $this->p6_mobile_phone, true);
        $criteria->compare('p6_email', $this->p6_email, true);
        $criteria->compare('p6_relationship', $this->p6_relationship, true);
        $criteria->compare('p6_is_authorised', $this->p6_is_authorised);
        $criteria->compare('p3_is_authorised', $this->p6_is_authorised);
        $criteria->compare('p4_is_authorised', $this->p6_is_authorised);
        $criteria->compare('p3_profile_photo', $this->p3_profile_photo, true);
        $criteria->compare('p4_profile_photo', $this->p4_profile_photo, true);
        $criteria->compare('p5_profile_photo', $this->p5_profile_photo, true);
        $criteria->compare('p6_profile_photo', $this->p6_profile_photo, true);


        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ChildParentalDetails the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function afterValidate() {
        if ($this->p1_profile_photo == '' && $this->prevProfilePhoto1 != '') {
            $this->p1_profile_photo = $this->prevProfilePhoto1;
        }
        if ($this->p2_profile_photo == '' && $this->prevProfilePhoto2 != '') {
            $this->p2_profile_photo = $this->prevProfilePhoto2;
        }
        if ($this->p3_profile_photo == '' && $this->prevProfilePhoto3 != '') {
            $this->p3_profile_photo = $this->prevProfilePhoto3;
        }
        if ($this->p4_profile_photo == '' && $this->prevProfilePhoto4 != '') {
            $this->p4_profile_photo = $this->prevProfilePhoto4;
        }
        if ($this->p5_profile_photo == '' && $this->prevProfilePhoto5 != '') {
            $this->p5_profile_photo = $this->prevProfilePhoto5;
        }
        if ($this->p6_profile_photo == '' && $this->prevProfilePhoto6 != '') {
            $this->p6_profile_photo = $this->prevProfilePhoto6;
        }

        parent::afterValidate();
    }

    public function checkUniqueEmail($attributes, $params) {
        if (!empty($this->p1_email) && !empty($this->p2_email)) {
            if ($this->p1_email == $this->p2_email) {
                $this->addError('p1_email', 'Parent 1 && Parent 2 Email should be Unique');
                $this->addError('p2_email', 'Parent 1 && Parent 2 Email should be Unique');
            }
        }
    }

    public function checkParent2MandatoryFields($attributes, $params) {
        $check_attributes = array(
            'p2_first_name',
        );
        if (trim($this->p2_title) != "" || trim($this->p2_first_name) != "" || trim($this->p2_last_name) != "") {
            foreach ($check_attributes as $key => $value) {
                if ($this->$value == "") {
                    $this->addError($value, 'Please set this field.');
                }
            }
        }
    }

    public function validateParentContactFields($attributes, $params) {
        if (!($this->p1_mobile_phone) && !($this->p1_email)) {
            $this->addError('p1_mobile_phone', 'Parent 1 Email / Mobile is mandatory.');
            $this->addError('p1_email', 'Parent 1 Email / Mobile is mandatory.');
        }
    }

    protected function beforeSave() {
        $this->last_updated_by = Yii::app()->user->id;
        $this->last_updated = new CDbExpression('NOW()');
        return parent::beforeSave();
    }

    public function getP1name() {
        return $this->p1_title . " " . $this->p1_first_name . " " . $this->p1_last_name;
    }

    public function getP2name() {
        return $this->p2_title . " " . $this->p2_first_name . " " . $this->p2_last_name;
    }
    
    public function getP3name() {
        return $this->p3_title . " " . $this->p3_first_name . " " . $this->p3_last_name;
    }
    
    public function getP4name() {
        return $this->p4_title . " " . $this->p4_first_name . " " . $this->p4_last_name;
    }

    public function getColumnNames() {
        $unset_columns = array('id', 'child_id', 'p1_profile_photo', 'p2_profile_photo', 'p3_profile_photo', 'p4_profile_photo', 'p5_profile_photo', 'p6_profile_photo', 'last_updated', 'last_updated_by');
        $attributes = $this->getAttributes();
        return array_diff(array_keys($attributes), $unset_columns);
    }

    public function getFilter($column_name) {
        $response = array();
        if ($column_name == "p1_is_bill_payer" || $column_name == "p2_is_bill_payer" || $column_name == "p1_is_authorised" || $column_name == "p2_is_authorised" || $column_name == "p3_is_authorised" || $column_name == "p4_is_authorised" || $column_name == "p5_is_authorised" || $column_name == "p6_is_authorised") {
            $response[$column_name] = array("filter_condition" => array('=' => 'EQUAL', '!=' => "NOT EQUAL"), "filter_value" => array(0 => 0, 1 => 1));
        } else if ($column_name == "p1_dob" || $column_name == "p2_dob") {
            $response[$column_name] = array("filter_condition" => array('=' => 'EQUAL', '>' => "GREATER THAN", '<' => 'SMALLER THAN', '>=' => 'GREATER THAN EQUAL TO', '<=' => 'SMALLER THAN EQUAL TO', 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => array());
        } else if ($column_name == "p1_title" || $column_name == "p2_title" || $column_name == "p3_title" || $column_name == "p4_title" || $column_name == "p5_title" || $column_name == "p6_title") {
            $response[$column_name] = array("filter_condition" => array('=' => 'EQUAL', '!=' => "NOT EQUAL", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => array('Mr.' => 'Mr.', 'Mrs.' => 'Mrs.', 'Ms.' => 'Ms.', 'Miss.' => 'Miss.', 'Dr.' => 'Dr.'));
        } else {
            $response[$column_name] = array("filter_condition" => array('LIKE' => 'LIKE', 'LIKE %--%' => "LIKE %--%", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL', 'EMPTY' => 'EMPTY'), "filter_value" => array());
        }
        return $response;
    }

    public function getColumnValue($column_name, $column_value) {
        if ($column_name == "p1_is_bill_payer" || $column_name == "p2_is_bill_payer" || $column_name == "p1_is_authorised" || $column_name == "p2_is_authorised" || $column_name == "p3_is_authorised" || $column_name == "p4_is_authorised" || $column_name == "p5_is_authorised" || $column_name == "p6_is_authorised") {
            $column_value = ($column_value == 1) ? "Yes" : "No";
        } else {
            $column_value = $column_value;
        }
        return $column_value;
    }
    
    public function hasGcSession($parent) {
        $parent = (int) $parent;
        // Check if parent passed is not greater than 2nd
        if ($parent < 1 || $parent > 2) {
            return false;
        }
        $prefix = "p".$parent."_";
        return ($this->{$prefix."gocardless_session_token"} != null);
    }
    
    public function hasMandate($parent) {
        $parent = (int) $parent;
        // Check if parent passed is not greater than 2nd
        if ($parent < 1 || $parent > 2) {
            return false;
        }
        $prefix = "p".$parent."_";
        return ($this->{$prefix."gocardless_mandate"} != null);
    }
    
    public function associateGcMandate($parent, $customerId, $mandateId) {
        $prefix = "p".$parent."_";
        $this->{$prefix."gocardless_customer"} = $customerId;
        $this->{$prefix."gocardless_mandate"} = $mandateId;
        return $this->save();
    }
    
    public function sendDirectDebitRequest($parent, $message = '') {
        $prefix = "p".$parent."_";
        $gcCustomerClient = customFunctions::getCustomerGoCardlessClient();
        if (!$gcCustomerClient) {
            throw new CHttpException(500, 'Direct Debit Client account does not exist.');
        }
        try {
            $sessionToken = time()."-".rand(1,1000);
            $redirectFlow = $gcCustomerClient->redirectFlows()->create(array(
                "params" => array(
                    "description" => "eyMan",
                    "session_token" => $sessionToken,
                    "success_redirect_url" => Yii::app()->controller->createAbsoluteUrl('/site/goCardlessSuccess', array('id' => $this->id, 'parent' => $parent)),
                    "prefilled_customer" => array(
                        "given_name" => $this->{$prefix."first_name"},
                        "family_name" => $this->{$prefix."last_name"},
                        "email" => $this->{$prefix."email"},
                        "address_line1" => $this->{$prefix."address_1"},
                        "postal_code" => $this->{$prefix."postcode"},
                    )
                )
            ));
            if ($redirectFlow->id != null) {
                $this->{$prefix."gocardless_customer_id"} = $redirectFlow->id;
                $this->{$prefix."gocardless_session_token"} = $sessionToken;
                if ($this->save()) {
                    $url = $redirectFlow->redirect_url;
                    $name = $this->{$prefix."first_name"}.' '.$this->{$prefix."last_name"};
                    $to = array(
                        'email' => 'lokesh@mds.asia',//$this->{$prefix."email"},
                        'name' => $name,
                        'type' => 'to'
                    );
                    
                    $companyName = 'eyManagement';
                    
//                    $content = 'Dear '.$name.'<br /><br />';
                    if ($message != '') {
//                        $content .= 'Message: '.$message.'<br />';
                        $message = '<tr> <td> '.$message.' </td></tr>';
                    } else {
                        $message = '<tr> <td> Kindly Subscribe </td></tr>';
                    }
                    
                    $template = '<table cellpadding="0" cellspacing="0" border="0" style="width: 470px; margin:10px auto; text-align: justify;font-family: \'Arial\', sans-serif;"> <thead> <tr> <td> <table border="0" cellpadding="0" cellspacing="0" width="100%" align="center"> <tr> <td align="center"> <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAH4AAAArCAYAAAC+YDzMAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyJpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMy1jMDExIDY2LjE0NTY2MSwgMjAxMi8wMi8wNi0xNDo1NjoyNyAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTNiAoV2luZG93cykiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6ODgyNzg4OUJERDAzMTFFNDk4NTBDNUU5RjhBRkQwNkMiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6ODgyNzg4OUNERDAzMTFFNDk4NTBDNUU5RjhBRkQwNkMiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDo4ODI3ODg5OUREMDMxMUU0OTg1MEM1RTlGOEFGRDA2QyIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDo4ODI3ODg5QUREMDMxMUU0OTg1MEM1RTlGOEFGRDA2QyIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PqKZgNEAABNhSURBVHja7FwJmBT1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"> </td></tr></table> <table border="0" cellpadding="0" cellspacing="0" width="100%" style="font-size: 20px;line-height: 27px; margin-top: 30px;"> <tr> <td> '.$companyName.' would like authorisation to <br>take payment from you </td></tr></table> <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 15px"> '.$message.'</table> <table border="0" cellpadding="0" cellspacing="0" width="100%" style="line-height: 23px; margin-top: 15px;"> <tr> <td> You will be notified before '.$companyName.' collects any future <br>payments from you. </td></tr></table> <a href="'.$url.'" style="color: #fff; text-decoration: none;"> <table border="0" cellpadding="10" cellspacing="0" width="100%" style="line-height: 23px; margin-top: 15px"> <tr style="background: #5092da; color: #fff;"> <td align="center"> Authorise </td></tr></table> </a> <table border="0" cellpadding="0" cellspacing="0" width="100%" style="line-height: 23px; margin-top: 15px; font-weight: bold;"> <tr> <td> If there is an issue with this payment please contact '.$companyName.'.</a> </td></tr></table>';
                    
                    $mandrill = new EymanMandril('Direct Debit Request', $template, 'eyMan Notifications', array($to), 'no-reply@eylog.co.uk');                    
                    $response = $mandrill->sendEmail();
                } else {
                    throw new Exception('Direct Debit could not be connected.');
                }
            } else {
                throw new Exception('Direct Debit could not be connected.');
            }
        } catch(Exception $e) {
            throw new CHttpException(500, 'Direct Debit could not be connected.');
        }
    }

}
