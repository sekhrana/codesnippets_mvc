<?php

/**
 * This is the model class for table "tbl_child_general_details".
 *
 * The followings are the available columns in table 'tbl_child_general_details':
 * @property integer $id
 * @property integer $child_id
 * @property integer $payment_method_id
 * @property integer $payment_terms_id
 * @property integer $ethinicity_id
 * @property integer $religion_id
 * @property string $first_language
 * @property integer $vulnerability_id
 * @property integer $sen_provision_id
 * @property string $lead_professional
 * @property string $social_worker
 * @property string $local_authority
 * @property string $school_name
 * @property string $school_class
 * @property string $school_teacher
 * @property string $school_phone
 * @property string $school_day_end
 * @property string $collection_password
 * @property string $school_start_time
 * @property string $general_notes
 * @property string $notes
 * @property string $dietary_requirements
 * @property string $reason_for_leaving
 * @property string $sibling_details
 * @property integer $is_subsidy
 * @property integer $is_outings_on_foot
 * @property integer $is_published_content
 * @property integer $is_sun_cream
 * @property integer $is_face_paint
 * @property integer $is_social_networking
 * @property integer $is_press_releases
 * @property integer $is_nappy_cream
 * @property integer $is_promotional_material
 * @property integer $is_caf
 * @property integer $is_allow_photos
 * @property integer $is_allow_video
 * @property integer $is_child_in_nappies
 * @property string $caf_number
 * @property integer $is_sen
 * @property string $health_visitor_name
 * @property string $health_visitor_contact
 * @property string $social_worker_contact
 * @property integer $is_eal
 * @property integer $nationality_id
 * @property string  $health_visitor_address
 * @property string  $health_visitor_notes
 * @property integer $is_first_aid
 * @property integer $is_medication
 * @property integer $is_teething_gel
 * @property integer $is_emergency_treatment
 * @property integer $is_insect_bite_treatment
 *
 * The followings are the available model relations:
 * @property ChildPersonalDetails $child
 * @property PickSenProvision $senProvision
 * @property PickPaymentMethod $paymentMethod
 * @property PickPaymentTerms $paymentTerms
 * @property PickReligion $religion
 * @property PickEthinicity $ethinicity
 * @property PickVulnerability $vulnerability
 * 
 */
class ChildGeneralDetails extends CActiveRecord {

    public $date_columns = array('school_day_end');

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'tbl_child_general_details';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('child_id', 'required'),
            array('child_id, payment_method_id, payment_terms_id, ethinicity_id, religion_id, vulnerability_id, sen_provision_id,is_subsidy, is_outings_on_foot, is_published_content, is_sun_cream, is_face_paint, is_social_networking, is_press_releases, is_nappy_cream, is_promotional_material, is_caf, is_allow_photos, is_allow_video, is_child_in_nappies, is_sen, is_eal,nationality_id, is_first_aid, is_medication, is_teething_gel,is_emergency_treatment,is_insect_bite_treatment', 'numerical', 'integerOnly' => true),
            array('first_language, caf_number , lead_professional, social_worker, local_authority, school_name, school_class, school_teacher, school_phone, collection_password, health_visitor_contact, social_worker_contact', 'length', 'max' => 45),
            array('general_notes, dietary_requirements, reason_for_leaving, sibling_details, notes', 'safe'),
            array('health_visitor_name', 'length', 'max' => 255),
            array('school_day_end, school_start_time', 'default', 'setOnEmpty' => true, 'value' => NULL),
            array('health_visitor_contact, social_worker_contact, school_phone', 'length', 'max' => 50, 'message' => 'Please Input a Valid Phone Number'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id, child_id, payment_method_id, payment_terms_id, ethinicity_id, religion_id, first_language,caf_number, vulnerability_id, sen_provision_id, lead_professional, social_worker, local_authority, school_name, school_class, school_teacher, school_phone, school_day_end, collection_password, school_start_time, general_notes, dietary_requirements, reason_for_leaving, sibling_details, is_subsidy, is_outings_on_foot, is_published_content, is_sun_cream, is_face_paint, is_social_networking, is_press_releases, is_nappy_cream, is_promotional_material, is_caf, is_allow_photos, is_allow_video, is_child_in_nappies, is_sen, health_visitor_name, health_visitor_contact, social_worker_contact, notes, is_eal', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'senProvision' => array(self::BELONGS_TO, 'PickSenProvision', 'sen_provision_id'),
            'paymentMethod' => array(self::BELONGS_TO, 'PickPaymentMethod', 'payment_method_id'),
            'paymentTerms' => array(self::BELONGS_TO, 'PickPaymentTerms', 'payment_terms_id'),
            'religion' => array(self::BELONGS_TO, 'PickReligion', 'religion_id'),
            'ethinicity' => array(self::BELONGS_TO, 'PickEthinicity', 'ethinicity_id'),
            'vulnerability' => array(self::BELONGS_TO, 'PickVulnerability', 'vulnerability_id'),
        );
    }

    public function behaviors() {
        return array(
            'dateFormatter' => array(
                'class' => 'application.components.DateFormatter',
                'date_columns' => array('school_day_end')
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
            'payment_method_id' => 'Payment Method',
            'payment_terms_id' => 'Payment Terms',
            'ethinicity_id' => 'Ethnicity',
            'religion_id' => 'Religion',
            'first_language' => 'First Language',
            'vulnerability_id' => 'Vulnerability',
            'sen_provision_id' => 'Sen Provision',
            'lead_professional' => 'Lead Professional',
            'social_worker' => 'Social Worker',
            'local_authority' => 'Local Authority',
            'school_name' => 'School Name',
            'school_class' => 'School Class',
            'school_teacher' => 'School Teacher',
            'school_phone' => 'School Phone',
            'school_day_end' => 'School Day End',
            'collection_password' => 'Collection Password',
            'school_start_time' => 'School Start Time',
            'general_notes' => 'Allergies',
            'notes' => 'Notes',
            'dietary_requirements' => 'Dietary Requirements',
            'reason_for_leaving' => 'Reason For Leaving',
            'sibling_details' => 'Sibling Details',
            'is_subsidy' => 'Subsidy',
            'is_outings_on_foot' => 'Outings On Foot',
            'is_published_content' => 'Published Content',
            'is_sun_cream' => 'Sun Cream',
            'is_face_paint' => 'Face Paint',
            'is_social_networking' => 'Social Networking',
            'is_press_releases' => 'Press Releases',
            'is_nappy_cream' => 'Nappy Cream',
            'is_promotional_material' => 'Promotional Material',
            'is_caf' => 'Common Assessment Framework (CAF)',
            'is_allow_photos' => 'Allow Photos',
            'is_allow_video' => 'Allow Video',
            'is_child_in_nappies' => 'Child In Nappies',
            'caf_number' => 'CAF Number',
            'is_sen' => 'Special Edu. Needs & Disability (SEND)',
            'health_visitor_name' => 'Health Visitor Name',
            'health_visitor_contact' => 'Health Visitor Contact',
            'social_worker_contact' => 'Social Worker Contact',
            'is_eal' => 'English as an Additional Language (EAL)',
            'nationality_id' => 'Nationality',
            'health_visitor_address' => 'Health Visitor Address',
            'health_visitor_notes' => 'Health Visitor Notes',
            'is_first_aid' => 'Administer First Aid',
            'is_medication' => 'Administer Medication',
            'is_teething_gel' => 'Administer Teething Gel',
            'is_emergency_treatment' => 'Authorise Emergency Treatment',
            'is_insect_bite_treatment' => 'Authorise Insect Bite Treatment',
            
        );
    }

    public function search() {
        // @todo Please modify the following code to remove attributes that should not be searched.

        $criteria = new CDbCriteria;
        $criteria->compare('id', $this->id);
        $criteria->compare('child_id', $this->child_id);
        $criteria->compare('payment_method_id', $this->payment_method_id);
        $criteria->compare('payment_terms_id', $this->payment_terms_id);
        $criteria->compare('ethinicity_id', $this->ethinicity_id);
        $criteria->compare('religion_id', $this->religion_id);
        $criteria->compare('first_language', $this->first_language, true);
        $criteria->compare('caf_number', $this->caf_number, true);
        $criteria->compare('vulnerability_id', $this->vulnerability_id);
        $criteria->compare('sen_provision_id', $this->sen_provision_id);
        $criteria->compare('lead_professional', $this->lead_professional, true);
        $criteria->compare('social_worker', $this->social_worker, true);
        $criteria->compare('local_authority', $this->local_authority, true);
        $criteria->compare('school_name', $this->school_name, true);
        $criteria->compare('school_class', $this->school_class, true);
        $criteria->compare('school_teacher', $this->school_teacher, true);
        $criteria->compare('school_phone', $this->school_phone, true);
        $criteria->compare('school_day_end', $this->school_day_end, true);
        $criteria->compare('collection_password', $this->collection_password, true);
        $criteria->compare('school_start_time', $this->school_start_time, true);
        $criteria->compare('general_notes', $this->general_notes, true);
        $criteria->compare('notes', $this->notes, true);
        $criteria->compare('dietary_requirements', $this->dietary_requirements, true);
        $criteria->compare('reason_for_leaving', $this->reason_for_leaving, true);
        $criteria->compare('sibling_details', $this->sibling_details, true);
        $criteria->compare('is_subsidy', $this->is_subsidy);
        $criteria->compare('is_outings_on_foot', $this->is_outings_on_foot);
        $criteria->compare('is_published_content', $this->is_published_content);
        $criteria->compare('is_sun_cream', $this->is_sun_cream);
        $criteria->compare('is_face_paint', $this->is_face_paint);
        $criteria->compare('is_social_networking', $this->is_social_networking);
        $criteria->compare('is_press_releases', $this->is_press_releases);
        $criteria->compare('is_nappy_cream', $this->is_nappy_cream);
        $criteria->compare('is_promotional_material', $this->is_promotional_material);
        $criteria->compare('is_caf', $this->is_caf);
        $criteria->compare('is_allow_photos', $this->is_allow_photos);
        $criteria->compare('is_allow_video', $this->is_allow_video);
        $criteria->compare('is_child_in_nappies', $this->is_child_in_nappies);
        $criteria->compare('is_sen', $this->is_sen);
        $criteria->compare('health_visitor_name', $this->health_visitor_name, true);
        $criteria->compare('health_visitor_contact', $this->health_visitor_contact, true);
        $criteria->compare('social_worker_contact', $this->social_worker_contact, true);
        $criteria->compare('is_eal', $this->is_eal, true);


        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ChildGeneralDetails the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    protected function beforeSave() {
        $this->last_updated_by = Yii::app()->user->id;
        $this->last_updated = new CDbExpression('NOW()');
        return parent::beforeSave();
    }

    public function getColumnNames() {
        $unset_columns = array('id', 'child_id', 'last_updated', 'last_updated_by');
        $attributes = $this->getAttributes();
        return array_diff(array_keys($attributes), $unset_columns);
    }

    public function getFilter($column_name) {
        $response = array();
        if ($column_name == "is_subsidy" || $column_name == "is_outings_on_foot" || $column_name == "is_published_content" || $column_name == "is_sun_cream" || $column_name == "is_face_paint" || $column_name == "is_social_networking" || $column_name == "is_press_releases" || $column_name == "is_nappy_cream" || $column_name == "is_promotional_material" || $column_name == "is_allow_photos" || $column_name == "is_allow_video" || $column_name == "is_child_in_nappies" || $column_name == "is_sen" || $column_name == "is_caf") {
            $response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '!=' => "NOT EQUAL TO"), "filter_value" => array(0 => 0, 1 => 1));
        } else if ($column_name == "payment_method_id") {
            $response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '!=' => "NOT EQUAL TO", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => CHtml::listData(PickPaymentMethod::model()->findAll(), 'id', 'name'));
        } else if ($column_name == "payment_terms_id") {
            $response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '!=' => "NOT EQUAL TO", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => CHtml::listData(PickPaymentTerms::model()->findAll(), 'id', 'name'));
        } else if ($column_name == "ethinicity_id") {
            $response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '!=' => "NOT EQUAL TO", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => CHtml::listData(PickEthinicity::model()->findAll(), 'id', 'name'));
        } else if ($column_name == "religion_id") {
            $response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '!=' => "NOT EQUAL TO", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => CHtml::listData(PickReligion::model()->findAll(), 'id', 'name'));
        } else if ($column_name == "vulnerability_id") {
            $response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '!=' => "NOT EQUAL TO", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => CHtml::listData(PickVulnerability::model()->findAll(), 'id', 'first_name'));
        } else {
            $response[$column_name] = array("filter_condition" => array('LIKE' => 'LIKE', 'LIKE %--%' => "LIKE %--%", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL', 'EMPTY' => 'EMPTY'), "filter_value" => array());
        }
        return $response;
    }

    public function getColumnValue($column_name, $column_value) {
        if ($column_name == "is_subsidy" || $column_name == "is_outings_on_foot" || $column_name == "is_published_content" || $column_name == "is_sun_cream" || $column_name == "is_face_paint" || $column_name == "is_social_networking" || $column_name == "is_press_releases" || $column_name == "is_nappy_cream" || $column_name == "is_promotional_material" || $column_name == "is_allow_photos" || $column_name == "is_allow_video" || $column_name == "is_child_in_nappies" || $column_name == "is_sen" || $column_name == "is_caf") {
            $column_value = ($column_value == 1) ? "Yes" : "No";
        } else if ($column_name == "payment_method_id") {
            $column_value = PickPaymentMethod::model()->findByPk($column_value)->name;
        } else if ($column_name == "payment_terms_id") {
            $column_value = PickPaymentTerms::model()->findByPk($column_value)->name;
        } else if ($column_name == "ethinicity_id") {
            $column_value = PickEthinicity::model()->findByPk($column_value)->name;
        } else if ($column_name == "religion_id") {
            $column_value = PickReligion::model()->findByPk($column_value)->name;
        } else if ($column_name == "vulnerability_id") {
            $column_value = PickVulnerability::model()->findByPk($column_value)->name;
        } else {
            $column_value = $column_value;
        }
        return $column_value;
    }

}
