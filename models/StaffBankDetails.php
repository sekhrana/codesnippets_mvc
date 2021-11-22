<?php

/**
 * This is the model class for table "tbl_staff_bank_details".
 *
 * The followings are the available columns in table 'tbl_staff_bank_details':
 * @property integer $id
 * @property integer $staff_id
 * @property string $payroll_co
 * @property string $payroll_id
 * @property string $bank_name
 * @property string $bank_account_name
 * @property string $bank_account_no
 * @property string $bank_sort_code
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 *
 * The followings are the available model relations:
 * @property StaffPersonalDetails $staff
 */
class StaffBankDetails extends CActiveRecord {

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'tbl_staff_bank_details';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('staff_id', 'required'),
            array('staff_id, created_by, updated_by', 'numerical', 'integerOnly' => true),
            array('payroll_co, payroll_id, bank_name, bank_account_name, bank_account_no, bank_sort_code', 'length', 'max' => 45),
            array('updated, created', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id, staff_id, payroll_co, payroll_id, bank_name, bank_account_name, bank_account_no, bank_sort_code, updated, created, created_by, updated_by', 'safe', 'on' => 'search'),
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
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'staff_id' => 'Staff',
            'payroll_co' => 'Payroll Co',
            'payroll_id' => 'Payroll ID',
            'bank_name' => 'Bank Name',
            'bank_account_name' => 'Bank Account Name',
            'bank_account_no' => 'Bank Account No',
            'bank_sort_code' => 'Bank Sort Code',
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
        $criteria->compare('staff_id', $this->staff_id);
        $criteria->compare('payroll_co', $this->payroll_co, true);
        $criteria->compare('payroll_id', $this->payroll_id, true);
        $criteria->compare('bank_name', $this->bank_name, true);
        $criteria->compare('bank_account_name', $this->bank_account_name, true);
        $criteria->compare('bank_account_no', $this->bank_account_no, true);
        $criteria->compare('bank_sort_code', $this->bank_sort_code, true);
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
     * @return StaffBankDetails the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function getColumnNames() {
        $unset_columns = array('id', 'staff_id', 'updated', 'updated_by', 'created', 'created_by');
        $attributes = $this->getAttributes();
        return array_diff(array_keys($attributes), $unset_columns);
    }

    public function getFilter($column_name) {
        $response = array();
        if ($column_name == "payroll_co" || $column_name == "payroll_id" || $column_name == "bank_name" || $column_name == "bank_account_name" || $column_name == "bank_account_no" || $column_name == "bank_sort_code") {
            $response[$column_name] = array("filter_condition" => array('LIKE' => 'LIKE', 'LIKE %--%' => "LIKE %--%", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL', 'EMPTY' => 'EMPTY'), "filter_value" => array());
        }
        return $response;
    }

    public function getColumnValue($column_name, $column_value) {
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
