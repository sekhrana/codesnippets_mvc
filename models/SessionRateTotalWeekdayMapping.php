<?php

/**
 * This is the model class for table "tbl_session_rate_total_weekday_mapping".
 *
 * The followings are the available columns in table 'tbl_session_rate_total_weekday_mapping':
 * @property integer $id
 * @property integer $session_id
 * @property integer $age_group
 * @property integer $total_day_1
 * @property double $rate_1
 * @property integer $total_day_2
 * @property double $rate_2
 * @property integer $total_day_3
 * @property double $rate_3
 * @property integer $total_day_4
 * @property double $rate_4
 * @property integer $total_day_5
 * @property double $rate_5
 * @property integer $total_day_6
 * @property double $rate_6
 * @property integer $total_day_7
 * @property double $rate_7
 * @property double $rate_monthly
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 *
 * The followings are the available model relations:
 * @property SessionRates $session
 */
class SessionRateTotalWeekdayMapping extends CActiveRecord {

    public $max_age_group;

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'tbl_session_rate_total_weekday_mapping';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('session_id', 'required'),
            array('session_id, total_day_1, total_day_2, total_day_3, total_day_4, total_day_5, total_day_6, total_day_7, rate_monthly, created_by, updated_by', 'numerical', 'integerOnly' => true),
            array('rate_1, rate_2, rate_3, rate_4, rate_5, rate_6, rate_7, age_group', 'numerical'),
            array('created, updated', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id, session_id, age_group, total_day_1, rate_1, total_day_2, rate_2, total_day_3, rate_3, total_day_4, rate_4, total_day_5, rate_5, total_day_6, rate_6, total_day_7, rate_7, rate_monthly, updated, created, created_by, updated_by', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'session' => array(self::BELONGS_TO, 'SessionRates', 'session_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'session_id' => 'Session',
            'age_group' => 'Age Group',
            'total_day_1' => 'Total Day 1',
            'rate_1' => 'Rate 1',
            'total_day_2' => 'Total Day 2',
            'rate_2' => 'Rate 2',
            'total_day_3' => 'Total Day 3',
            'rate_3' => 'Rate 3',
            'total_day_4' => 'Total Day 4',
            'rate_4' => 'Rate 4',
            'total_day_5' => 'Total Day 5',
            'rate_5' => 'Rate 5',
            'total_day_6' => 'Total Day 6',
            'rate_6' => 'Rate 6',
            'total_day_7' => 'Total Day 7',
            'rate_7' => 'Rate 7',
            'rate_monthly' => 'Rate Monthly',
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
        $criteria->compare('session_id', $this->session_id);
        $criteria->compare('age_group', $this->age_group);
        $criteria->compare('total_day_1', $this->total_day_1);
        $criteria->compare('rate_1', $this->rate_1);
        $criteria->compare('total_day_2', $this->total_day_2);
        $criteria->compare('rate_2', $this->rate_2);
        $criteria->compare('total_day_3', $this->total_day_3);
        $criteria->compare('rate_3', $this->rate_3);
        $criteria->compare('total_day_4', $this->total_day_4);
        $criteria->compare('rate_4', $this->rate_4);
        $criteria->compare('total_day_5', $this->total_day_5);
        $criteria->compare('rate_5', $this->rate_5);
        $criteria->compare('total_day_6', $this->total_day_6);
        $criteria->compare('rate_6', $this->rate_6);
        $criteria->compare('total_day_7', $this->total_day_7);
        $criteria->compare('rate_7', $this->rate_7);
        $criteria->compare('rate_monthly', $this->rate_monthly);
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
     * @return SessionRateTotalWeekdayMapping the static model class
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
