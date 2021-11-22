<?php

/**
 * This is the model class for table "tbl_casual_staff_entitlement".
 *
 * The followings are the available columns in table 'tbl_casual_staff_entitlement':
 * @property integer $id
 * @property integer $staff_id
 * @property string $week_start_date
 * @property double $week_entitlement
 * @property integer $is_deleted
 * @property string $created
 *
 * The followings are the available model relations:
 * @property StaffPersonalDetails $staff
 */
class CasualStaffEntitlement extends CActiveRecord {

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'tbl_casual_staff_entitlement';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('staff_id, week_start_date, week_entitlement', 'required'),
			array('staff_id, is_deleted', 'numerical', 'integerOnly' => true),
			array('week_entitlement', 'numerical'),
			array('created', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, staff_id, week_start_date, week_entitlement, is_deleted, created', 'safe', 'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'staff' => array(self::BELONGS_TO, 'StaffPersonalDetails', 'staff_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'staff_id' => 'Staff',
			'week_start_date' => 'Week Start Date',
			'week_entitlement' => 'Week Entitlement',
			'is_deleted' => 'Is Deleted',
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
		$criteria->compare('staff_id', $this->staff_id);
		$criteria->compare('week_start_date', $this->week_start_date, true);
		$criteria->compare('week_entitlement', $this->week_entitlement);
		$criteria->compare('is_deleted', $this->is_deleted);
		$criteria->compare('created', $this->created, true);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return CasualStaffEntitlement the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public function getWeekEntitlement($staff_id, $week_start_date) {
		$model = CasualStaffEntitlement::model()->findByAttributes(array('staff_id' => $staff_id, 'week_start_date' => $week_start_date));
		if (!empty($model))
			return $model;
		else
			return false;
	}

	public function saveEntitlement($staff_id, $week_start_date, $week_entitlement, $id = NULL) {
		if ($id == NULL) {
			$model = new CasualStaffEntitlement;
			$model->staff_id = $staff_id;
			$model->week_start_date = $week_start_date;
			$model->week_entitlement = $week_entitlement;
			if ($model->save()) {
				return true;
			} else {
				return false;
			}
		} else {
			CasualStaffEntitlement::model()->updateByPk($id, ['week_entitlement' => $week_entitlement]);
			return true;
		}
	}

}
