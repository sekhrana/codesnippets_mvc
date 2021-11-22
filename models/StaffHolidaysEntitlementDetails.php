<?php

/**
 * This is the model class for table "tbl_staff_holidays_entitlement_details".
 *
 * The followings are the available columns in table 'tbl_staff_holidays_entitlement_details':
 * @property integer $id
 * @property double $contract_hours
 * @property double $holiday_entitlement
 * @property string $start_date
 * @property string $finish_date
 * @property double $used
 * @property double $balance
 * @property integer $staff_id
 * @property integer $staff_event_id
 * @property double $previous_contract_hours
 * @property integer $is_deleted
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 * The followings are the available model relations:
 * @property StaffHolidaysEntitlementDetails $staffEvent
 * @property StaffHolidaysEntitlementDetails[] $tblStaffHolidaysEntitlementDetails
 * @property StaffPersonalDetails $staff
 */
class StaffHolidaysEntitlementDetails extends CActiveRecord {

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'tbl_staff_holidays_entitlement_details';
    }

    public function defaultScope() {
        if (get_class(Yii::app()) === "CWebApplication") {
            return array(
                'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0",
            );
        }
        if (get_class(Yii::app()) === "CConsoleApplication") {
            return array(
                'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0",
            );
        }
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('staff_id, staff_event_id', 'required'),
            array('staff_id, staff_event_id, is_deleted, created_by, updated_by', 'numerical', 'integerOnly' => true),
            array('contract_hours, holiday_entitlement, used, balance', 'numerical'),
            array('start_date, finish_date, updated, created', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id, contract_hours, holiday_entitlement, start_date, finish_date, used, balance, staff_id, staff_event_id, is_deleted, updated, created, created_by, updated_by', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'staffEvent' => array(self::BELONGS_TO, 'StaffHolidaysEntitlementDetails', 'staff_event_id'),
            'tblStaffHolidaysEntitlementDetails' => array(self::HAS_MANY, 'StaffHolidaysEntitlementDetails', 'staff_event_id'),
            'staff' => array(self::BELONGS_TO, 'StaffPersonalDetails', 'staff_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'contract_hours' => 'Contract Hours',
            'holiday_entitlement' => 'Holiday Entitlement',
            'start_date' => 'Start Date',
            'finish_date' => 'Finish Date',
            'used' => 'Used',
            'balance' => 'Balance',
            'staff_id' => 'Staff',
            'staff_event_id' => 'Staff Event',
            'is_deleted' => 'Is Deleted',
            'previous_contract_hours' => 'Previous Contract Hours',
            'created' => 'Created',
            'updated' => 'Updated',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
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
        $criteria->compare('contract_hours', $this->contract_hours);
        $criteria->compare('holiday_entitlement', $this->holiday_entitlement);
        $criteria->compare('previous_contract_hours', $this->previous_contract_hours);
        $criteria->compare('start_date', $this->start_date, true);
        $criteria->compare('finish_date', $this->finish_date, true);
        $criteria->compare('used', $this->used);
        $criteria->compare('balance', $this->balance);
        $criteria->compare('staff_id', $this->staff_id);
        $criteria->compare('staff_event_id', $this->staff_event_id);
        $criteria->compare('is_deleted', $this->is_deleted);
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
     * @return StaffHolidaysEntitlementDetails the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
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
