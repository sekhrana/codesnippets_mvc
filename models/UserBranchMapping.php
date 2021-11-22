<?php

/**
 * This is the model class for table "tbl_user_branch_mapping".
 *
 * The followings are the available columns in table 'tbl_user_branch_mapping':
 * @property integer $id
 * @property integer $user_id
 * @property integer $company_id
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $branch_id
 * @property integer $staff_id
 *
 * The followings are the available model relations:
 * @property User $user
 */
class UserBranchMapping extends CActiveRecord {

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'tbl_user_branch_mapping';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('user_id', 'required'),
            array('user_id, company_id, branch_id, staff_id,created_by, updated_by', 'numerical', 'integerOnly' => true),
            array('created, updated', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id, user_id, company_id, created, branch_id, staff_id, updated,created_by, updated_by', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'user' => array(self::BELONGS_TO, 'User', 'user_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'user_id' => 'User',
            'company_id' => 'Company',
            'created' => 'Created',
            'updated' => 'Updated',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'branch_id' => 'Branch',
            'staff_id' => 'Staff',
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
        $criteria->compare('user_id', $this->user_id);
        $criteria->compare('company_id', $this->company_id);
        $criteria->compare('created', $this->created, true);
        $criteria->compare('updated', $this->updated, true);
        $criteria->compare('created_by', $this->created_by);
        $criteria->compare('updated_by', $this->updated_by);
        $criteria->compare('branch_id', $this->branch_id);
        $criteria->compare('staff_id', $this->staff_id);

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return UserBranchMapping the static model class
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
