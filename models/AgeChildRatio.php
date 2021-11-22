<?php

/**
 * This is the model class for table "tbl_age_child_ratio".
 *
 * The followings are the available columns in table 'tbl_age_child_ratio':
 * @property integer $id
 * @property integer $branch_id
 * @property string $name
 * @property string $description
 * @property integer $default_ratio
 * @property integer $is_global
 * @property integer $global_id
 * @property integer $create_for_existing
 * @property string $created
 *
 * The followings are the available model relations:
 * @property Branch $branch
 * @property AgeRatioMapping[] $ageRatioMappings
 */
class AgeChildRatio extends CActiveRecord {

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'tbl_age_child_ratio';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('branch_id, name, description, default_ratio', 'required'),
            array('branch_id, default_ratio, is_global, global_id, create_for_existing', 'numerical', 'integerOnly' => true),
            array('name', 'length', 'max' => 45),
            array('description', 'length', 'max' => 255),
            array('created', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id, branch_id, name, description, default_ratio, is_global, global_id, create_for_existing, created', 'safe', 'on' => 'search'),
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
            'ageRatioMappings' => array(self::HAS_MANY, 'AgeRatioMapping', 'age_ratio_id'),
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
            'default_ratio' => 'Default Ratio',
            'is_global' => 'Is Global',
            'global_id' => 'Global',
            'create_for_existing' => 'Create For Existing',
            'created' => 'Created',
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
        $criteria->compare('default_ratio', $this->default_ratio);
        $criteria->compare('is_global', $this->is_global);
        $criteria->compare('global_id', $this->global_id);
        $criteria->compare('create_for_existing', $this->create_for_existing);
        $criteria->compare('created', $this->created, true);

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return AgeChildRatio the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

}
