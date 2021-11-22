<?php
//Demo Commit
/**
 * This is the model class for table "tbl_child_holidays".
 *
 * The followings are the available columns in table 'tbl_child_holidays':
 * @property integer $id
 * @property integer $branch_id
 * @property integer $child_id
 * @property string $date
 * @property string $notes
 * @property integer $holiday_reason
 * @property integer $exclude_from_invoice
 * @property integer $is_deleted
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 *
 * The followings are the available model relations:
 * @property ChildPersonalDetails $child
 * @property Branch $branch
 */
class ChildHolidays extends CActiveRecord {

		public $holidays;

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
     * @return string the associated database table name
     */
    public function tableName() {
        return 'tbl_child_holidays';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('child_id, date, branch_id', 'required'),
            array('child_id, branch_id, holiday_reason, exclude_from_invoice, is_deleted, created_by, updated_by', 'numerical', 'integerOnly' => true),
            array('notes', 'length', 'max' => 255),
            array('updated, created', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id, branch_id, child_id, date, notes, holiday_reason, exclude_from_invoice, is_deleted, updated, created, created_by, updated_by', 'safe', 'on' => 'search'),
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
            'branch' => array(self::BELONGS_TO, 'Branch', 'branch_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'child_id' => 'Child',
            'branch_id' => 'Branch',
            'date' => 'Date',
            'notes' => 'Notes',
            'holiday_reason' => 'Holiday Reason',
            'exclude_from_invoice' => 'Exclude From Invoice',
            'is_deleted' => 'Is Deleted',
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
        $criteria->compare('child_id', $this->child_id);
        $criteria->compare('branch_id', $this->branch_id);
        $criteria->compare('date', $this->date, true);
        $criteria->compare('notes', $this->notes, true);
        $criteria->compare('holiday_reason', $this->holiday_reason);
        $criteria->compare('exclude_from_invoice', $this->exclude_from_invoice);
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
     * @return ChildHolidays the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function getColumnNames() {
        $unset_columns = array('id', 'branch_id', 'is_deleted', 'updated_by', 'updated', 'created', 'created_by');
        $attributes = $this->getAttributes();
        return array_diff(array_keys($attributes), $unset_columns);
    }

    public function getFilter($column_name) {
        $response = array();
        if ($column_name == "exclude_from_invoice") {
            $response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '!=' => "NOT EQUAL TO"), "filter_value" => array(0 => 0, 1 => 1));
        } else if ($column_name == "date") {
            $response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '>' => "GREATER THAN", '<' => 'SMALLER THAN', '>=' => 'GREATER THAN EQUAL TO', '<=' => 'SMALLER THAN EQUAL TO', 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => array());
        } else if ($column_name == "child_id") {
            $response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '!=' => "NOT EQUAL TO", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => CHtml::listData(ChildPersonalDetails::model()->findAllByAttributes(array('branch_id' => Yii::app()->session['branch_id'])), 'id', 'first_name'));
        } else if ($column_name == "holiday_reason") {
            $response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '!=' => "NOT EQUAL TO", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => array(0 => 'Sick', 1 => 'Holiday'));
        } else {
            $response[$column_name] = array("filter_condition" => array('LIKE' => 'LIKE', 'LIKE %--%' => "LIKE %--%", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL', 'EMPTY' => 'EMPTY'), "filter_value" => array());
        }
        return $response;
    }

    public function getColumnValue($column_name, $column_value) {
        if ($column_name == "exclude_from_invoice") {
            $column_value = ($column_value == 1) ? "Yes" : "No";
        } else if ($column_name == "holiday_reason") {
            $column_value = ($column_value == 0) ? "Sick" : "Holiday";
        } else if ($column_name == "child_id") {
            $column_value = ChildPersonalDetails::model()->findByPk($column_value)->first_name . " " . ChildPersonalDetails::model()->findByPk($pk)->last_name;
        } else {
            $column_value = $column_value;
        }
        return $column_value;
    }

    public function getRelatedAttributes() {
        $attributes = array();
        $attributes['ChildPersonalDetails'] = ChildPersonalDetails::model()->getRelatedAttributesNames();
        return $attributes;
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
