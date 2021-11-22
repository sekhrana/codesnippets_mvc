<?php

/**
 * This is the model class for table "tbl_staff_general_details".
 *
 * The followings are the available columns in table 'tbl_staff_general_details':
 * @property integer $id
 * @property integer $staff_id
 * @property string $disabled_reg_no
 * @property integer $ethinicity_id
 * @property string $nationality
 * @property string $cultural_preferences
 * @property string $first_language
 * @property string $esol
 * @property integer $religion_id
 * @property string $disability_details
 * @property string $dependents_details
 * @property string $dietary_requirements
 * @property string $medical_requirements
 * @property string $additional_learning_needs
 * @property string $additional_social_needs
 * @property integer $is_driver
 * @property integer $is_disabled
 * @property integer $is_pregnant
 * @property integer $has_promotional_material
 * @property integer $has_christmas_card
 * @property integer $has_birthday_card
 * @property integer $is_tupe
 * @property integer $is_babysitting
 * @property integer $has_pension_friends_life
 * @property integer $has_hadland_workplace
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 *
 * The followings are the available model relations:
 * @property StaffPersonalDetails $staff
 * @property PickEthinicity $ethinicity
 * @property PickReligion $religion
 */
class StaffGeneralDetails extends CActiveRecord {

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'tbl_staff_general_details';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('staff_id', 'required'),
            array('staff_id, is_driver,ethinicity_id, religion_id, is_disabled, is_pregnant, has_promotional_material, has_christmas_card, has_birthday_card, is_tupe, is_babysitting, has_pension_friends_life, has_hadland_workplace, created_by, updated_by', 'numerical', 'integerOnly' => true),
            array('disabled_reg_no, nationality, cultural_preferences, first_language', 'length', 'max' => 45),
            array('esol', 'length', 'max' => 7),
            array('esol', 'default', 'setOnEmpty' => true, 'value' => NULL),
            array('disability_details, dependents_details, dietary_requirements, medical_requirements, additional_learning_needs, additional_social_needs,updated, created', 'safe'),
            //array('nationality, cultural_preferences, first_language', 'match', 'pattern' => '/^[A-Za-z]+$/u', 'message' => 'Only Alphabets are allowed'),
            //array('disabled_reg_no', 'numerical', 'integerOnly' => true, 'message' => 'Only Numbers are allowed'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id, staff_id, disabled_reg_no, ethinicity_id, nationality, cultural_preferences, first_language, esol, religion_id, disability_details, dependents_details, dietary_requirements, medical_requirements, additional_learning_needs, additional_social_needs, is_driver, is_disabled, is_pregnant, has_promotional_material, has_christmas_card, has_birthday_card, is_tupe, is_babysitting, has_pension_friends_life, has_hadland_workplace, updated, created, created_by, updated_by', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'staff' => array(self::BELONGS_TO, 'StaffPersonalDetails', 'staff_id'),
            'ethinicity' => array(self::BELONGS_TO, 'PickEthinicity', 'ethinicity_id'),
            'religion' => array(self::BELONGS_TO, 'PickReligion', 'religion_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'staff_id' => 'Staff',
            'disabled_reg_no' => 'Disabled reg No',
            'ethinicity_id' => 'Ethnic origin',
            'nationality' => 'Nationality',
            'cultural_preferences' => 'Cultural Preferences',
            'first_language' => 'First Language',
            'esol' => 'ESOL',
            'religion_id' => 'Religion',
            'disability_details' => 'Details of disability',
            'dependents_details' => 'Details of dependants',
            'dietary_requirements' => 'Dietary requirements',
            'medical_requirements' => 'Medical Requirements',
            'additional_learning_needs' => 'Additional learning Needs',
            'additional_social_needs' => 'Additional social needs',
            'is_driver' => 'Driver',
            'is_disabled' => 'Disabled',
            'is_pregnant' => 'Pregnant',
            'has_promotional_material' => 'Promotional Material',
            'has_christmas_card' => 'Card - Christmas',
            'has_birthday_card' => 'Card - Birthday',
            'is_tupe' => 'TUPE',
            'is_babysitting' => 'Babysitting',
            'has_pension_friends_life' => 'Pension - Friends Life',
            'has_hadland_workplace' => 'Pension - Hadland Workplace',
            'created' => 'Created',
            'updated' => 'Updated',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By'
        );
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
        $criteria->compare('staff_id', $this->staff_id);
        $criteria->compare('disabled_reg_no', $this->disabled_reg_no, true);
        $criteria->compare('ethinicity_id', $this->ethinicity_id);
        $criteria->compare('nationality', $this->nationality, true);
        $criteria->compare('cultural_preferences', $this->cultural_preferences, true);
        $criteria->compare('first_language', $this->first_language, true);
        $criteria->compare('esol', $this->esol, true);
        $criteria->compare('religion_id', $this->religion_id);
        $criteria->compare('disability_details', $this->disability_details, true);
        $criteria->compare('dependents_details', $this->dependents_details, true);
        $criteria->compare('dietary_requirements', $this->dietary_requirements, true);
        $criteria->compare('medical_requirements', $this->medical_requirements, true);
        $criteria->compare('additional_learning_needs', $this->additional_learning_needs, true);
        $criteria->compare('additional_social_needs', $this->additional_social_needs, true);
        $criteria->compare('is_driver', $this->is_driver);
        $criteria->compare('is_disabled', $this->is_disabled);
        $criteria->compare('is_pregnant', $this->is_pregnant);
        $criteria->compare('has_promotional_material', $this->has_promotional_material);
        $criteria->compare('has_christmas_card', $this->has_christmas_card);
        $criteria->compare('has_birthday_card', $this->has_birthday_card);
        $criteria->compare('is_tupe', $this->is_tupe);
        $criteria->compare('is_babysitting', $this->is_babysitting);
        $criteria->compare('has_pension_friends_life', $this->has_pension_friends_life);
        $criteria->compare('has_hadland_workplace', $this->has_hadland_workplace);
        $criteria->compare('created', $this->created, true);
        $criteria->compare('updated', $this->updated, true);
        $criteria->compare('created_by', $this->created_by);
        $criteria->compare('updated_by', $this->updated_by);

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return StaffGeneralDetails the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function getColumnNames() {
        $unset_columns = array('id', 'staff_id', 'is_disabled', 'created', 'created_by', 'updated', 'updated_by');
        $attributes = $this->getAttributes();
        return array_diff(array_keys($attributes), $unset_columns);
    }

    public function getFilter($column_name) {
        $response = array();
        if ($column_name == "has_hadland_workplace" || $column_name == "has_pension_friends_life" || $column_name == "is_babysitting" || $column_name == "is_tupe" || $column_name == "has_birthday_card" || $column_name == "has_christmas_card" || $column_name == "has_promotional_material" || $column_name == "is_pregnant" || $column_name == "is_driver" || $column_name == "is_allow_photos" || $column_name == "is_allow_video" || $column_name == "is_child_in_nappies" || $column_name == "is_sen" || $column_name == "is_caf") {
            $response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '!=' => "NOT EQUAL TO"), "filter_value" => array(0 => 0, 1 => 1));
        } else if ($column_name == "ethinicity_id") {
            $response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '!=' => "NOT EQUAL TO", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => CHtml::listData(PickEthinicity::model()->findAll(), 'id', 'name'));
        } else if ($column_name == "religion_id") {
            $response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '!=' => "NOT EQUAL TO", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => CHtml::listData(PickReligion::model()->findAll(), 'id', 'name'));
        } else if ($column_name == "esol") {
            $response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '!=' => "NOT EQUAL TO", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => array('GOOD' => 'Good', 'AVERAGE' => 'Average', "POOR" => 'Poor'));
        } else {
            $response[$column_name] = array("filter_condition" => array('LIKE' => 'LIKE', 'LIKE %--%' => "LIKE %--%", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL', 'EMPTY' => 'EMPTY'), "filter_value" => array());
        }
        return $response;
    }

    public function getColumnValue($column_name, $column_value) {
        if ($column_name == "has_hadland_workplace" || $column_name == "has_pension_friends_life" || $column_name == "is_babysitting" || $column_name == "is_tupe" || $column_name == "has_birthday_card" || $column_name == "has_christmas_card" || $column_name == "has_promotional_material" || $column_name == "is_pregnant" || $column_name == "is_driver" || $column_name == "is_allow_photos" || $column_name == "is_allow_video" || $column_name == "is_child_in_nappies" || $column_name == "is_sen" || $column_name == "is_caf") {
            $column_value = ($column_value == 1) ? "Yes" : "No";
        } else if ($column_name == "ethinicity_id") {
            $column_value = PickEthinicity::model()->findByPk($column_value)->name;
        } else if ($column_name == "religion_id") {
            $column_value = PickReligion::model()->findByPk($column_value)->name;
        } else {
            $column_value = $column_value;
        }
        return $column_value;
    }

    public function beforeSave() {
        if (get_class(Yii::app()) === "CWebApplication") {
            if ($this->isNewRecord) {
                $this->created_by = Yii::app()->user->id;
                $this->created = new CDbExpression("NOW()");
            } else {
                $this->updated_by = Yii::app()->user->id;
                $this->updated = new CDbExpression("NOW()");
            }
        }
        if (get_class(Yii::app()) === "CConsoleApplication") {
            if ($this->isNewRecord) {
                $this->created_by = User::model()->findByEmail(Yii::app()->params['superAdminEmailId'][0]);
                $this->created = new CDbExpression("NOW()");
            } else {
                $this->updated_by = User::model()->findByEmail(Yii::app()->params['superAdminEmailId'][0]);
                $this->updated = new CDbExpression("NOW()");
            }
        }

        return parent::beforeSave();
    }

}
