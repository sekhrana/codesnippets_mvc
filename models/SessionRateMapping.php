<?php

/**
 * This is the model class for table "tbl_session_rate_mapping".
 *
 * The followings are the available columns in table 'tbl_session_rate_mapping':
 * @property integer $id
 * @property integer $session_id
 * @property integer $age_group
 * @property double $time_minimum
 * @property double $rate_minimum
 * @property double $time_1
 * @property double $rate_1
 * @property double $time_2
 * @property double $rate_2
 * @property double $time_3
 * @property double $rate_3
 * @property double $time_4
 * @property double $rate_4
 * @property double $time_5
 * @property double $rate_5
 * @property double $time_6
 * @property double $rate_6
 * @property double $time_7
 * @property double $rate_7
 * @property double $time_8
 * @property double $rate_8
 * @property double $time_9
 * @property double $rate_9
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 */
class SessionRateMapping extends CActiveRecord {

    public $max_age_group;

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'tbl_session_rate_mapping';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('session_id', 'required'),
            array('session_id, created_by, updated_by', 'numerical', 'integerOnly' => true),
            array('time_minimum, rate_minimum, age_group, time_1, rate_1, time_2, rate_2, time_3, rate_3, time_4, rate_4, time_5, rate_5, time_6, rate_6, time_7, rate_7, time_8, rate_8, time_9, rate_9', 'numerical', 'message' => 'Time should contain only numeric values'),
            array('created, updated', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id, session_id, age_group, time_minimum, rate_minimum, time_1, rate_1, time_2, rate_2, time_3, rate_3, time_4, rate_4, time_5, rate_5, time_6, rate_6, time_7, rate_7, time_8, rate_8, time_9, rate_9, created, updated, created_by, updated_by', 'safe', 'on' => 'search'),
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
            'session_id' => 'Session',
            'age_group' => 'Age Group',
            'time_1' => 'Time 1',
            'rate_1' => 'Rate 1',
            'time_2' => 'Time 2',
            'rate_2' => 'Rate 2',
            'time_3' => 'Time 3',
            'rate_3' => 'Rate 3',
            'time_4' => 'Time 4',
            'rate_4' => 'Rate 4',
            'time_5' => 'Time 5',
            'rate_5' => 'Rate 5',
            'time_6' => 'Time 6',
            'rate_6' => 'Rate 6',
            'time_7' => 'Time 7',
            'rate_7' => 'Rate 7',
            'time_8' => 'Time 8',
            'rate_8' => 'Rate 8',
            'time_9' => 'Time 9',
            'rate_9' => 'Rate 9',
            'created' => 'Created',
            'updated' => 'Updated',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'time_minimum' => 'Minimum Booking Time',
            'rate_minimum' => 'Minimum Booking Rate',
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
        $criteria->compare('time_minimum', $this->time_minimum);
        $criteria->compare('rate_minimum', $this->rate_minimum);
        $criteria->compare('time_1', $this->time_1);
        $criteria->compare('rate_1', $this->rate_1);
        $criteria->compare('time_2', $this->time_2);
        $criteria->compare('rate_2', $this->rate_2);
        $criteria->compare('time_3', $this->time_3);
        $criteria->compare('rate_3', $this->rate_3);
        $criteria->compare('time_4', $this->time_4);
        $criteria->compare('rate_4', $this->rate_4);
        $criteria->compare('time_5', $this->time_5);
        $criteria->compare('rate_5', $this->rate_5);
        $criteria->compare('time_6', $this->time_6);
        $criteria->compare('rate_6', $this->rate_6);
        $criteria->compare('time_7', $this->time_7);
        $criteria->compare('rate_7', $this->rate_7);
        $criteria->compare('time_8', $this->time_8);
        $criteria->compare('rate_8', $this->rate_8);
        $criteria->compare('time_9', $this->time_9);
        $criteria->compare('rate_9', $this->rate_9);
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
     * @return SessionRateMapping the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /*
     * Function to calculate the minimum booking rates for the current week.
     */

    public function getMinimumBookingRate($invoice_id, $childModel, $weekBookingHours, $weekStartDate, $weekEndDate, $age) {
        if (isset($childModel->preffered_session) && !empty($childModel->preffered_session) && ($childModel->prefferedSession->multiple_rates_type == 1) && ($childModel->is_funding == 0) && ($childModel->booking_type != ChildPersonalDetails::EMERGENCY_BOOKER)) {
            $prefferedSessionId = $childModel->preffered_session;
            $modifiedSessionsModel = SessionRatesHistory::model()->findAllByAttributes(array('session_id' => $prefferedSessionId));
            if (!empty($modifiedSessionsModel)) {
                $modifiedSessionId = NULL;
                foreach ($modifiedSessionsModel as $sessionModel) {
                    if (strtotime($sessionModel->effective_date) <= strtotime($weekStartDate)) {
                        $modifiedSessionId = $sessionModel->modified_session_id;
                    } else {
                        break;
                    }
                }
            }
            if ($modifiedSessionId != NULL) {
                $prefferedSessionId = $modifiedSessionId;
            }
            $max_age_group = SessionRateMapping::model()->findBySql("select max(age_group) as max_age_group from tbl_session_rate_mapping where session_id = " . $prefferedSessionId)->max_age_group;
            if ($age >= $max_age_group) {
                $age = $max_age_group - 0.5; // Logic for calucalting age group if age is greater than the max age group
            }
            if ($age < $max_age_group) {
                $criteria = new CDbCriteria();
                $criteria->condition = "age_group > :age AND session_id = :session_id";
                $criteria->order = "age_group";
                $criteria->limit = "1";
                $criteria->params = array(':age' => $age, ':session_id' => $prefferedSessionId);
                $mappingModel = SessionRateMapping::model()->find($criteria);
                if (!empty($mappingModel)) {
                    $minimumBookingHours = $mappingModel->time_minimum;
                    if ($weekBookingHours < $minimumBookingHours) {
                        $invoiceDetailsArray = array(
                            'invoice_id' => $invoice_id,
                            'session_data' => CJSON::encode(array($weekStartDate => array("Minimum Booking Fees"))),
                            'products_data' => NULL,
                            'week_start_date' => $weekStartDate,
                            'week_end_date' => $weekEndDate,
                            'session_id' => $prefferedSessionId,
                            'rate' => sprintf("%0.2f", $mappingModel->rate_minimum),
                            'total_days' => 1,
                            'total_hours' => ($minimumBookingHours - $weekBookingHours),
                            'funded_hours' => 0,
                            'discount' => $childModel->discount,
                            'session_type' => $childModel->prefferedSession->multiple_rates_type,
                            'is_minimum_booking' => 1
                        );
                        return $invoiceDetailsArray;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
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
