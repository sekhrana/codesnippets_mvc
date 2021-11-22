<?php
//Demo Commit
/**
 * This is the model class for table "tbl_age_ratio".
 *
 * The followings are the available columns in table 'tbl_age_ratio':
 * @property integer $id
 * @property string $name
 * @property string $description
 * @property integer $age_group_lower
 * @property integer $age_group_upper
 * @property integer $ratio
 * @property integer $branch_id
 * @property integer $is_active
 * @property integer $is_deleted
 * @property integer $is_global
 * @property integer $global_id
 * @property integer $create_for_existing
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 * 
 * The followings are the available model relations:
 * @property Branch $branch
 */
class AgeRatio extends CActiveRecord {

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
        return 'tbl_age_ratio';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('name, branch_id,age_group_lower, age_group_upper,ratio', 'required'),
            array('age_group_lower, age_group_upper, ratio, branch_id, is_active, is_deleted, is_global, global_id, create_for_existing, created_by, updated_by', 'numerical', 'integerOnly' => true),
            array('name, description', 'length', 'max' => 255),
            array(
                'age_group_upper', 'compare', 'compareAttribute' => 'age_group_lower', 'operator' => '>',
                'allowEmpty' => true, 'message' => 'Age Group Upper must be greater than Age Group Lower.'
            ),
            array('updated, created', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id, name, description, age_group_lower, age_group_upper, ratio, branch_id, is_active, is_deleted, is_global, global_id, create_for_existing, updated, created, created_by, updated_by', 'safe', 'on' => 'search'),
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
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'name' => 'Name',
            'description' => 'Description',
            'age_group_lower' => 'Age Group Lower',
            'age_group_upper' => 'Age Group Upper',
            'ratio' => 'Ratio',
            'branch_id' => 'Branch',
            'is_active' => 'Is Active',
            'is_deleted' => 'Is Deleted',
            'is_global' => 'Is Global',
            'global_id' => 'Global',
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
        $criteria->compare('name', $this->name, true);
        $criteria->compare('description', $this->description, true);
        $criteria->compare('age_group_lower', $this->age_group_lower);
        $criteria->compare('age_group_upper', $this->age_group_upper);
        $criteria->compare('ratio', $this->ratio);
        if (isset(Yii::app()->session['global_id'])) {
            $criteria->compare('global_id', Yii::app()->session['global_id']);
            $criteria->compare('is_global', 1);
        } else {
            $criteria->compare('branch_id', Yii::app()->session['branch_id']);
            $criteria->compare('is_global', 0);
        }
        $criteria->compare('is_active', $this->is_active);
        $criteria->compare('is_deleted', $this->is_deleted);
        $criteria->compare('is_global', $this->is_global);
        $criteria->compare('create_for_existing', $this->create_for_existing);
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
     * @return AgeRatio the static model class
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
