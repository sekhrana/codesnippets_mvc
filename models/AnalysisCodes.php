<?php
//Demo Commit
/**
 * This is the model class for table "tbl_analysis_codes".
 *
 * The followings are the available columns in table 'tbl_analysis_codes':
 * @property integer $id
 * @property string $name
 * @property string $amount
 * @property string $description
 * @property integer $is_sales
 * @property integer $is_purchase
 * @property integer $global_analysiscode_id
 * @property integer $create_for_existing
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 */
class AnalysisCodes extends CActiveRecord {

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
            if (Yii::app()->session['role'] == "hrAdmin" || Yii::app()->session['role'] == "hrStandard") {
                return array(
                    'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".global_id =0",
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
        return 'tbl_analysis_codes';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('name, description', 'required'),
            array('is_sales,create_for_existing,is_active, is_purchase,is_deleted, global_analysiscode_id, created_by, updated_by', 'numerical', 'integerOnly' => true),
            array('amount', 'numerical', 'integerOnly' => FALSE),
            array('description', 'length', 'max' => 100),
            array('updated, created', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id,name,is_active,  amount, description, is_sales, is_purchase, global_analysiscode_id, create_for_existing, updated, created, created_by, updated_by', 'safe', 'on' => 'search'),
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
            'name' => 'Name',
            'amount' => 'Amount',
            'is_active' => 'Active',
            'description' => 'Description',
            'is_sales' => 'Sales',
            'is_purchase' => 'Purchase',
            'create_for_existing' => 'Create For Existing',
            'global_analysiscode_id' => 'Global Analysiscode',
            'create_for_existing' => 'Create For Existing',
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
        if (isset(Yii::app()->session['global_id'])) {
            $criteria->compare('global_id', Yii::app()->session['global_id']);
            $criteria->compare('is_global', 1);
        } else {
            $criteria->compare('branch_id', Yii::app()->session['branch_id']);
            $criteria->compare('is_global', 0);
        }
        $criteria->compare('name', $this->name, true);
        $criteria->compare('is_active', $this->is_active);
        $criteria->compare('amount', $this->amount, true);
        $criteria->compare('description', $this->description, true);
        $criteria->compare('is_sales', $this->is_sales);
        $criteria->compare('is_purchase', $this->is_purchase);
        $criteria->compare('global_analysiscode_id', $this->global_analysiscode_id);
        $criteria->compare('create_for_existing', $this->create_for_existing);
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
     * @return AnalysisCodes the static model class
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
