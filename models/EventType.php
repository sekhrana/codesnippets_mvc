<?php
//Demo Commit
/**
 * This is the model class for table "tbl_event_type".
 *
 * The followings are the available columns in table 'tbl_event_type':
 * @property integer $id
 * @property string $name
 * @property string $description
 * @property string $title_date_1
 * @property string $title_date_2
 * @property string $title_description
 * @property string $title_notes
 * @property integer $for_staff
 * @property integer $for_child
 * @property integer $branch_id
 * @property integer $is_deleted
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $is_active
 * @property integer $is_global
 * @property integer $global_id
 * @property integer $create_for_existing
 * @property integer $global_event_id
 * @property integer $is_systen_event
 *
 * The followings are the available model relations:
 * @property ChildEventDetails[] $childEventDetails
 * @property Branch $branch
 * @property StaffEventDetails[] $staffEventDetails
 */
class EventType extends CActiveRecord {

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'tbl_event_type';
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
                $userMapping = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()));
                return array(
                    'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id =" . $userMapping->branch_id,
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
            array('name, description', 'required'),
            array('for_staff,is_active, for_child, branch_id, is_deleted, global_event_id, is_systen_event, created_by, updated_by', 'numerical', 'integerOnly' => true),
            array('name, title_date_1, title_date_2, title_description, title_notes', 'length', 'max' => 45),
            array('description', 'length', 'max' => 255),
            array('updated, created', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id,is_active,create_for_existing, name, description, title_date_1, title_date_2, title_description, title_notes, for_staff, for_child, branch_id, is_deleted, global_event_id, is_systen_event, updated, created, created_by, updated_by', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'childEventDetails' => array(self::HAS_MANY, 'ChildEventDetails', 'event_id'),
            'branch' => array(self::BELONGS_TO, 'Branch', 'branch_id'),
            'staffEventDetails' => array(self::HAS_MANY, 'StaffEventDetails', 'event_id'),
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
            'title_date_1' => 'Title Date 1',
            'title_date_2' => 'Title Date 2',
            'title_description' => 'Title Description',
            'title_notes' => 'Title Notes',
            'is_active' => 'Active',
            'for_staff' => 'For Staff',
            'for_child' => 'For Child',
            'branch_id' => 'Branch',
            'is_deleted' => 'Is Deleted',
            'created' => 'Created',
            'updated' => 'Updated',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'create_for_existing' => 'Create For Existing Branch/Nursery',
            'global_event_id' => 'Global Event',
            'is_systen_event' => 'Is Systen Event',
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
        $criteria->compare('is_active', $this->is_active);
        $criteria->compare('title_date_1', $this->title_date_1, true);
        $criteria->compare('title_date_2', $this->title_date_2, true);
        $criteria->compare('title_description', $this->title_description, true);
        $criteria->compare('title_notes', $this->title_notes, true);
        $criteria->compare('for_staff', $this->for_staff);
        $criteria->compare('for_child', $this->for_child);
        $criteria->compare('global_event_id', $this->global_event_id);
        if (isset(Yii::app()->session['global_id'])) {
            $criteria->compare('global_id', Yii::app()->session['global_id']);
            $criteria->compare('is_global', 1);
        } else {
            $criteria->compare('branch_id', Yii::app()->session['branch_id']);
            $criteria->compare('is_global', 0);
        }
        $criteria->compare('is_deleted', $this->is_deleted);
        $criteria->compare('is_systen_event', $this->is_systen_event);
        $criteria->compare('created', $this->created, true);
        $criteria->compare('updated', $this->updated, true);
        $criteria->compare('created_by', $this->created_by);
        $criteria->compare('updated_by', $this->updated_by);
        $criteria->order = "name";
        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
            'pagination' => array('pageSize' => 25),
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return EventType the static model class
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
