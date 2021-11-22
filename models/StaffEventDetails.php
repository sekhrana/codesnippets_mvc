<?php

/**
 * This is the model class for table "tbl_staff_event_details".
 *
 * The followings are the available columns in table 'tbl_staff_event_details':
 * @property integer $id
 * @property integer $staff_id
 * @property integer $event_id
 * @property string $title_date_1_value
 * @property string $title_date_2_value
 * @property string $title_description_value
 * @property string $title_notes_value
 * @property string $event_audit_value
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $status
 * 
 * @property integer $is_deleted
 *
 * The followings are the available model relations:
 * @property StaffPersonalDetails $staff
 * @property EventType $event
 */
class StaffEventDetails extends CActiveRecord {

    public $date_columns = array('title_date_1_value', 'title_date_2_value');

    const PENDING = 0;
    const COMPLETED = 1;

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'tbl_staff_event_details';
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
            array('staff_id, event_id', 'required'),
            array('staff_id, event_id, is_deleted, created_by, updated_by, status', 'numerical', 'integerOnly' => true),
            array('title_date_1_value, title_date_2_value', 'default', 'setOnEmpty' => true, 'value' => NULL),
            array('event_audit_value', 'length', 'max'=>45),
            array('title_date_1_value, title_date_2_value, title_description_value, title_notes_value, created, updated', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id, staff_id, event_id, title_date_1_value, title_date_2_value, title_description_value, title_notes_value, created, is_deleted, updated, created_by, updated_by, status, event_audit_value', 'safe', 'on' => 'search'),
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
            'event' => array(self::BELONGS_TO, 'EventType', 'event_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'staff_id' => 'Staff',
            'event_id' => 'Event',
            'title_date_1_value' => 'Title Date 1 Value',
            'title_date_2_value' => 'Title Date 2 Value',
            'title_description_value' => 'Title Description Value',
            'title_notes_value' => 'Title Notes Value',
            'created' => 'Created',
            'updated' => 'Updated',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'is_deleted' => 'Is Deleted',
            'status' => 'Completed',
            'event_audit_value' => 'Event Audit Value'
        );
    }

    public function behaviors() {
        return array(
            'dateFormatter' => array(
                'class' => 'application.components.DateFormatter',
                'date_columns' => array('title_date_2_value', 'title_date_1_value')
            )
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
        $criteria->compare('staff_id', Yii::app()->request->getParam('staff_id'));
        $criteria->compare('event_id', $this->event_id);
        $criteria->compare('title_date_1_value', $this->title_date_1_value, true);
        $criteria->compare('title_date_2_value', $this->title_date_2_value, true);
        $criteria->compare('title_description_value', $this->title_description_value, true);
        $criteria->compare('title_notes_value', $this->title_notes_value, true);
        $criteria->compare('created', $this->created, true);
        $criteria->compare('updated', $this->updated, true);
        $criteria->compare('created_by', $this->created_by);
        $criteria->compare('updated_by', $this->updated_by);
        $criteria->compare('is_deleted', $this->is_deleted);
        $criteria->compare('status', $this->status);
        $criteria->compare('event_audit_value',$this->event_audit_value,true);

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return StaffEventDetails the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function beforeSave() {
        if (get_class(Yii::app()) === "CWebApplication") {
            if ($this->isNewRecord) {
                $this->created_by = Yii::app()->user->id;
                $this->created = new CDbExpression("NOW()");
                if ($this->event->name == "Pay Increase" && $this->event->is_systen_event == 1) {
                    if (strtotime($this->title_date_1_value) <= strtotime(date("Y-m-d"))) {
                        $this->event_audit_value = StaffPersonalDetails::getHourlyRate($this->staff_id, date("Y-m-d"));
                        $this->staff->hourly_rate_basic = $this->title_description_value;
                        if (!$this->staff->save()) {
                            echo CJSON::encode($this->staff->getErrors());
                            return false;
                        }
                    }
                }
                if ($this->event->name == "Pay Decrease") {
                    if (strtotime($this->title_date_1_value) <= strtotime(date("Y-m-d"))) {
                        $this->event_audit_value = StaffPersonalDetails::getHourlyRate($this->staff_id, date("Y-m-d"));
                        $this->staff->hourly_rate_basic = $this->title_description_value;
                        if (!$this->staff->save()) {
                            echo CJSON::encode($this->staff->getErrors());
                            return false;
                        }
                    }
                }
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
    
    public function getColumnNames() {
        $unset_columns = array('id', 'created','is_deleted',
            'updated', 'created_by', 'updated_by', 'status');
        $attributes = $this->getAttributes();
        return array_diff(array_keys($attributes), $unset_columns);
    }
    
    public function getFilter($column_name) {
        $response = array();
        if ($column_name == "event_id") {
            $response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '!=' => "NOT EQUAL TO", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => CHtml::listData(EventType::model()->findAllByAttributes(array('branch_id' => Yii::app()->session['branch_id'])), 'id', 'name'));
        } else if ($column_name == "title_date_1_value" || $column_name == "title_date_2_value") {
            $response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '>' => "GREATER THAN", '<' => 'SMALLER THAN', '>=' => 'GREATER THAN EQUAL TO', '<=' => 'SMALLER THAN EQUAL TO', 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => array());
        } else if ($column_name == "staff_id") {
            $response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '!=' => "NOT EQUAL TO", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => CHtml::listData(StaffPersonalDetails::model()->findAllByAttributes(array('branch_id' => Yii::app()->session['branch_id'])), 'id', 'name'));
        }  else {
            $response[$column_name] = array("filter_condition" => array('LIKE' => 'LIKE', 'LIKE %--%' => "LIKE %--%", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL', 'EMPTY' => 'EMPTY'), "filter_value" => array());
        }
        return $response;
    }

    public function getColumnValue($column_name, $column_value) {
        if ($column_name == "staff_id") {
            $column_value = StaffPersonalDetails::model()->resetScope()->findByPk($column_value)->name;
        } else if ($column_name == "event_id") {
            $column_value = EventType::model()->findByPk($column_value)->name;
        } else {
            $column_value = $column_value;
        }
        return $column_value;
    }
    
}
