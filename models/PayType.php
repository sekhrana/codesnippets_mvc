<?php
//Demo Commit
/**
 * This is the model class for table "tbl_pay_type".
 *
 * The followings are the available columns in table 'tbl_pay_type':
 * @property integer $id
 * @property string $abbreviation
 * @property string $description
 * @property integer $is_counted_in_ratio
 * @property integer $pay_id
 * @property integer $is_deleted
 * @property integer $include_in_wage_report
 * @property integer $is_unpaid
 * @property string $color
 * @property integer $branch_id
 * @property integer $is_system_activity
 * @property integer $global_paytype_id
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 *
 * The followings are the available model relations:
 * @property Branch $branch
 * @property StaffBookings[] $staffBookings
 */
class PayType extends CActiveRecord {

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'tbl_pay_type';
    }

    public function defaultScope() {
        if (get_class(Yii::app()) === "CWebApplication") {
            if (Yii::app()->session['role'] == "companyAdministrator") {
                $company_id = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()))->company_id;
                $branchString = implode(',', customFunctions::getBranchesByCompany($company_id));
                return array(
                    'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")",
                );
            }
            if (Yii::app()->session['role'] == "superAdmin") {
                return array(
                    'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0",
                );
            }
            if (Yii::app()->session['role'] == "customerAdministrator") {
                return array(
                    'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0",
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
            array('abbreviation, description, color', 'required'),
            array('is_counted_in_ratio, pay_id, include_in_wage_report, is_deleted, branch_id, is_system_activity, global_paytype_id,create_for_existing, created_by, updated_by, is_unpaid', 'numerical', 'integerOnly' => true),
            array('abbreviation, description', 'length', 'max' => 255),
            array('color', 'length', 'max' => 45),
            array('updated, created', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id, create_for_existing,abbreviation,is_active, description, is_counted_in_ratio,include_in_wage_report, pay_id, is_deleted, color, branch_id, is_system_activity, global_paytype_id, updated, created, created_by, updated_by, is_unpaid', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'branch' => array(self::BELONGS_TO, 'Branch', 'branch_id'),
            'staffBookings' => array(self::HAS_MANY, 'StaffBookings', 'activity_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'abbreviation' => 'Name',
            'description' => 'Description',
            'is_counted_in_ratio' => 'Counted In Ratio',
            'pay_id' => 'Payroll Reference',
            'is_deleted' => 'Deleted',
            'color' => 'Colour',
            'include_in_wage_report' => 'Include In Wage Report',
            'branch_id' => 'Branch',
            'create_for_existing' => 'Create For Existing',
            'is_system_activity' => 'Is System Activity',
            'global_paytype_id' => 'Global Paytype',
            'created' => 'Created',
            'updated' => 'Updated',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'is_unpaid' => 'Unpaid',
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
        $criteria->compare('abbreviation', $this->abbreviation, true);
        $criteria->compare('description', $this->description, true);
        $criteria->compare('is_active', $this->is_active);
        $criteria->compare('is_counted_in_ratio', $this->is_counted_in_ratio);
        $criteria->compare('pay_id', $this->pay_id);
        $criteria->compare('include_in_wage_report', $this->include_in_wage_report);
        $criteria->compare('is_unpaid', $this->is_unpaid);
        $criteria->compare('is_deleted', $this->is_deleted);
        $criteria->compare('color', $this->color, true);
        $criteria->compare('global_paytype_id', $this->global_paytype_id);
        if (isset(Yii::app()->session['global_id'])) {
            $criteria->compare('global_id', Yii::app()->session['global_id']);
            $criteria->compare('is_global', 1);
        } else {
            $criteria->compare('branch_id', Yii::app()->session['branch_id']);
            $criteria->compare('is_global', 0);
        }
        $criteria->compare('is_system_activity', $this->is_system_activity);
        $criteria->compare('created', $this->created, true);
        $criteria->compare('updated', $this->updated, true);
        $criteria->compare('created_by', $this->created_by);
        $criteria->compare('updated_by', $this->updated_by);
        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
            'pagination' => array('pageSize' => 25),
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return PayType the static model class
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
