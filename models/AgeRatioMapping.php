<?php

/**
 * This is the model class for table "tbl_age_ratio_mapping".
 *
 * The followings are the available columns in table 'tbl_age_ratio_mapping':
 * @property integer $id
 * @property integer $age_ratio_id
 * @property integer $age_group_lower
 * @property integer $age_group_upper
 * @property integer $ratio
 * @property string $created
 *
 * The followings are the available model relations:
 * @property AgeChildRatio $ageRatio
 */
class AgeRatioMapping extends CActiveRecord {

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'tbl_age_ratio_mapping';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('age_ratio_id, age_group_lower, age_group_upper, ratio', 'required'),
            array('age_ratio_id, age_group_lower, age_group_upper, ratio', 'numerical', 'integerOnly' => true),
            array('created', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id, age_ratio_id, age_group_lower, age_group_upper, ratio, created', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'ageRatio' => array(self::BELONGS_TO, 'AgeChildRatio', 'age_ratio_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'age_ratio_id' => 'Age Ratio',
            'age_group_lower' => 'Age Group Lower',
            'age_group_upper' => 'Age Group Upper',
            'ratio' => 'Ratio',
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
        $criteria->compare('age_ratio_id', $this->age_ratio_id);
        $criteria->compare('age_group_lower', $this->age_group_lower);
        $criteria->compare('age_group_upper', $this->age_group_upper);
        $criteria->compare('ratio', $this->ratio);
        $criteria->compare('created', $this->created, true);

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return AgeRatioMapping the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

}
