<?php
//Demo Commit
/**
 * This is the model class for table "tbl_reports".
 *
 * The followings are the available columns in table 'tbl_reports':
 * @property integer $id
 * @property integer $branch_id
 * @property string $name
 * @property string $description
 * @property string $data
 * @property integer $saved_by
 * @property integer $save_as
 * @property integer $is_deleted
 * @property integer $created_by
 * @property string $created_at
 * @property integer $updated_by
 * @property string $updated_at
 */
class Reports extends CActiveRecord {

    public $condition;
    
    const TERM_TIME_FUNDING_REPORT = 1;
    const TWO_THREE_YEAR_FUNDING_REPORT = 3;
    const STAFF_WAGES_WEEKLY = 4;
    const STAFF_HOURS_REPORT = 5;
    const STAFF_SCHEDULING_REPORT = 8;
    const MONTHLY_DEBTORS = 9;
    const CHILDREN_WITHOUT_BOOKING = 10;
    const CHILD_NAPPY_RECORD = 11;
    const CHILD_SLEEP_RECORD = 12;
    const STAFF_SIGN_IN_OUT_REPORT = 13;
    const CHILD_REGISTER_REPORT = 14;
    const CHILD_SUNCREAM_REPORT = 15;
    const PAYMENT_REPORT = 16;
    const DOCUMENTS_REPORT = 17;
    const EVENTS_REPORT = 18;
    const ALLERGIES_REPORT = 19;
    const MONTHLY_NURSERY_REPORT = 20;
    const PARENT_EMAILS_REPORT = 21;
    const STAFF_EMAILS_REPORT = 22;
    const INVOICE_AMOUNT_REPORT = 23;
    const MINIMUM_WAGE_REPORT = 24;
    const STAFF_HOLIDAYS_USED_BALANCE_REPORT = 25;
    const UNINVOICED_BOOKINGS_REPORT = 26;
    const EMERGENCY_CONTACTS_REPORTS = 27;
    const MEAL_DETAILS_REPORT  = 28;
    const AVERAGE_WEEKLY_HOURS = 29;
    const CHILD_NOT_SUITABLE_IN_ROOM = 30;
    const ENQUIRY_REPORT = 31;
    const INCOME_FORECAST_REPORT = 32;
    
    /**
     * @return string the associated database table name
     */
    public $report_area;

    public function tableName() {
        return 'tbl_reports';
    }

    public function defaultScope() {
        if (get_class(Yii::app()) === "CWebApplication") {
            if (Yii::app()->session['role'] == "superAdmin") {
                return array(
                    'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0",
                );
            }

            if (Yii::app()->session['role'] == "companyAdministrator") {
                $company_id = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()))->company_id;
                $branchString = implode(',', customFunctions::getBranchesByCompany($company_id));
                return array(
                    'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")",
                );
            }

            if (Yii::app()->session['role'] == "areaManager") {
                $userMappingModal = UserBranchMapping::model()->findAllByAttributes(array('user_id' => Yii::app()->user->getId()));
                $branchString = implode(',', customFunctions::getBranchesOfAreaManager($userMappingModal));
                return array(
                    'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")",
                );
            }
            if (Yii::app()->session['role'] == "branchManager") {
                $userMapping = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()));
                return array(
                    'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id =" . $userMapping->branch_id,
                );
            }
            if (Yii::app()->session['role'] == "branchAdmin") {
                $userMapping = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()));
                return array(
                    'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id =" . $userMapping->branch_id,
                );
            }
            if (Yii::app()->session['role'] == "staff") {
                $branchId = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->id))->branch_id;
                return array(
                    'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " .
                    $this->getTableAlias(false, false) . ".branch_id =" . $branchId,
                );
            }
            if (Yii::app()->session['role'] == "hrAdmin" || Yii::app()->session['role'] == User::HR_STANDARD) {
                return array(
                    'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0",
                );
            }
            if (Yii::app()->session['role'] == "accountsAdmin") {
                $company_id = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()))->company_id;
                $branchString = implode(',', customFunctions::getBranchesByCompany($company_id));
                return array(
                    'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")",
                );
            }
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
            array('branch_id, name, saved_by, save_as', 'required'),
            array('branch_id, is_deleted, created_by, updated_by, saved_by, save_as', 'numerical', 'integerOnly' => true),
            array('name, description', 'length', 'max' => 255),
            array('data, created_at, updated_at', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id, branch_id, name, description, data, is_deleted, created_by, created_at, updated_by, updated_at, saved_by, save_as', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'branch_id' => 'Branch',
            'name' => 'Name',
            'description' => 'Description',
            'data' => 'Data',
            'is_deleted' => 'Is Deleted',
            'created_by' => 'Created By',
            'created_at' => 'Created At',
            'updated_by' => 'Updated By',
            'updated_at' => 'Updated At',
            'saved_by' => 'Saved By',
            'save_as' => 'Save As',
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
        $criteria->compare('branch_id', $this->branch_id);
        $criteria->compare('name', $this->name, true);
        $criteria->compare('description', $this->description, true);
        $criteria->compare('data', $this->data, true);
        $criteria->compare('is_deleted', $this->is_deleted);
        $criteria->compare('created_by', $this->created_by);
        $criteria->compare('created_at', $this->created_at, true);
        $criteria->compare('updated_by', $this->updated_by);
        $criteria->compare('updated_at', $this->updated_at, true);
        $criteria->compare('saved_by', $this->saved_by);
        $criteria->compare('save_as', $this->save_as);
        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Reports the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function beforeSave() {
        if (get_class(Yii::app()) === "CWebApplication") {
            if ($this->isNewRecord) {
                $this->created_by = Yii::app()->user->id;
                $this->created_at = new CDbExpression("NOW()");
            } else {
                $this->updated_by = Yii::app()->user->id;
                $this->updated_at = new CDbExpression("NOW()");
            }
        }
        if (get_class(Yii::app()) === "CConsoleApplication") {
            if ($this->isNewRecord) {
                $this->created_by = User::model()->findByEmail(Yii::app()->params['superAdminEmailId'][0]);
                $this->created_at = new CDbExpression("NOW()");
            } else {
                $this->updated_by = User::model()->findByEmail(Yii::app()->params['superAdminEmailId'][0]);
                $this->updated_at = new CDbExpression("NOW()");
            }
        }

        return parent::beforeSave();
    }

}
