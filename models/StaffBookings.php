<?php

//Demo Commit
/**
 * This is the model class for table "tbl_staff_bookings".
 *
 * The followings are the available columns in table 'tbl_staff_bookings':
 * @property integer $id
 * @property integer $staff_id
 * @property integer $branch_id
 * @property integer $room_id
 * @property integer $activity_id
 * @property string $date_of_schedule
 * @property string $start_time
 * @property string $finish_time
 * @property string $notes
 * @property integer $is_booking_confirm
 * @property integer $is_booking_override
 * @property integer $is_step_up
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $is_deleted
 * @property integer $booking_group_id
 * @property integer $booking_group_repeat_id
 * @property string $booking_group_booking_days
 *
 * The followings are the available model relations:
 * @property PayType $activity
 * @property Branch $branch
 * @property Room $room
 * @property StaffPersonalDetails $staff
 * @property StaffPersonalDetailsNds $staffNds
 */
class StaffBookings extends CActiveRecord {

	public $start_date;
	public $finish_date;
	public $change_branch_id;
	public $change_room_id;
	public $change_staff_id;
	public $change_activity_id;
	public $repeat_type;
	public $min_schedule_date;
	public $this_booking_group_id;
	public $booking_group_start_date;
	public $booking_group_finish_date;
	public $booking_hours = 0;
	public $lunch_start_time;
	public $lunch_finish_time;

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'tbl_staff_bookings';
	}

	public function defaultScope() {
		if (get_class(Yii::app()) === "CWebApplication") {
			if (Yii::app()->session['role'] == "superAdmin") {
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 AND is_booking_override = 0",
				);
			}
			if (Yii::app()->session['role'] == "companyAdministrator") {
				$company_id = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()))->company_id;
				$branchString = implode(',', customFunctions::getBranchesByCompany($company_id));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 AND is_booking_override = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")",
				);
			}

			if (Yii::app()->session['role'] == "areaManager") {
				$userMappingModal = UserBranchMapping::model()->findAllByAttributes(array('user_id' => Yii::app()->user->getId()));
				$branchString = implode(',', customFunctions::getBranchesOfAreaManager($userMappingModal));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 AND is_booking_override = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")",
				);
			}
			if (Yii::app()->session['role'] == "branchManager") {
				$userMapping = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 AND is_booking_override = 0 and " . $this->getTableAlias(false, false) . ".branch_id =" . $userMapping->branch_id,
				);
			}
			if (Yii::app()->session['role'] == "branchAdmin") {
				$userMapping = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 AND is_booking_override = 0 and " . $this->getTableAlias(false, false) . ".branch_id =" . $userMapping->branch_id,
				);
			}
			if (Yii::app()->session['role'] == "staff") {
				$branchId = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->id))->branch_id;
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and is_booking_override = 0 and " .
					$this->getTableAlias(false, false) . ".staff_id =" . Yii::app()->session['staff_id'],
				);
			}
			if (Yii::app()->session['role'] == "hrAdmin" || Yii::app()->session['role'] == User::HR_STANDARD) {
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 AND is_booking_override = 0",
				);
			}
			if (Yii::app()->session['role'] == "accountsAdmin") {
				$company_id = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()))->company_id;
				$branchString = implode(',', customFunctions::getBranchesByCompany($company_id));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 AND is_booking_override = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")",
				);
			}
		}
		if (get_class(Yii::app()) === "CConsoleApplication") {
			return array(
				'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 AND is_booking_override = 0",
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
			array('staff_id, branch_id, room_id, activity_id, date_of_schedule, start_time, finish_time', 'required'),
			//array('staff_id, branch_id, room_id, activity_id, start_date, finish_date, start_time, finish_time', 'required', 'on' => 'singleStaffScheduling'),
			array('finish_time', 'compare', 'compareAttribute' => 'start_time', 'operator' => '>', 'allowEmpty' => false, 'message' => '{attribute} must be greater than "{compareAttribute}".'),
			array('staff_id, branch_id, room_id, activity_id, is_booking_confirm, is_booking_override, is_deleted, booking_group_repeat_id,is_step_up, created_by, updated_by,', 'numerical', 'integerOnly' => true),
			array('created,notes,updated', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, staff_id, branch_id, room_id, activity_id, date_of_schedule, start_time, finish_time,notes,is_booking_confirm, is_booking_override, created, is_step_up, is_deleted, booking_group_id, booking_group_repeat_id, booking_group_booking_days, updated, created_by, updated_by', 'safe', 'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'activity' => array(self::BELONGS_TO, 'PayType', 'activity_id'),
			'branch' => array(self::BELONGS_TO, 'Branch', 'branch_id'),
			'room' => array(self::BELONGS_TO, 'Room', 'room_id'),
			'staff' => array(self::BELONGS_TO, 'StaffPersonalDetails', 'staff_id'),
			'staffNds' => array(self::BELONGS_TO, 'StaffPersonalDetailsNds', 'staff_id'),
		);
	}

	public function getColumnNames() {
		$unset_columns = array('id',
			'is_deleted',
			'is_booking_confirm',
			'is_booking_override',
			'booking_group_booking_days',
			'booking_group_repeat_id',
			'created', 'start_time', 'finish_time', 'created_by', 'updated', 'updated_by', 'booking_group_id');
		$attributes = $this->getAttributes();
		return array_diff(array_keys($attributes), $unset_columns);
	}

	public function getRelatedAttributes() {
		$attributes = array();
		$attributes['StaffPersonalDetails'] = StaffPersonalDetails::model()->getRelatedAttributesNames();
		return $attributes;
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'staff_id' => 'Staff',
			'branch_id' => 'Branch',
			'room_id' => 'Room',
			'activity_id' => 'Activity',
			'date_of_schedule' => 'Date Of Schedule',
			'start_time' => 'Start Time',
			'finish_time' => 'Finish Time',
			'is_booking_confirm' => 'Is Booking Confirm',
			'notes' => 'Notes',
			'is_booking_override' => 'Is Booking Override',
			'is_step_up' => 'Step Up',
			'created' => 'Created',
			'updated' => 'Updated',
			'created_by' => 'Created By',
			'updated_by' => 'Updated By',
			'is_deleted' => 'Is Deleted',
			'booking_group_id' => 'Booking Group',
			'booking_group_repeat_id' => 'Booking Group Repeat',
			'booking_group_booking_days' => 'Booking Group Booking Days',
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
		$criteria->compare('branch_id', $this->branch_id);
		$criteria->compare('room_id', $this->room_id);
		$criteria->compare('activity_id', $this->activity_id);
		$criteria->compare('date_of_schedule', $this->date_of_schedule, true);
		$criteria->compare('start_time', $this->start_time, true);
		$criteria->compare('finish_time', $this->finish_time, true);
		$criteria->compare('is_booking_confirm', $this->is_booking_confirm);
		$criteria->compare('notes', $this->notes, true);
		$criteria->compare('is_booking_override', $this->is_booking_override);
		$criteria->compare('is_step_up', $this->is_step_up);
		$criteria->compare('created', $this->created, true);
		$criteria->compare('is_deleted', $this->is_deleted);
		$criteria->compare('updated', $this->updated, true);
		$criteria->compare('created_by', $this->created_by);
		$criteria->compare('updated_by', $this->updated_by);
		$criteria->compare('booking_group_id', $this->booking_group_id);
		$criteria->compare('booking_group_repeat_id', $this->booking_group_repeat_id);
		$criteria->compare('booking_group_booking_days', $this->booking_group_booking_days, true);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return StaffBookings the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public function getFilter($column_name) {
		$response = array();
		if ($column_name == "staff_id") {
			$response[$column_name] = array("filter_condition" => array('=' => 'EQUAL', '!=' => "NOT EQUAL", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => CHtml::listData(StaffPersonalDetails::model()->findAllByAttributes(array('branch_id' => Yii::app()->session['branch_id'])), 'id', 'first_name'));
		} else if ($column_name == "date_of_schedule") {
			$response[$column_name] = array("filter_condition" => array('=' => 'EQUAL', '>' => "GREATER THAN", '<' => 'SMALLER THAN', '>=' => 'GREATER THAN EQUAL TO', '<=' => 'SMALLER THAN EQUAL TO', 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => array());
		} else if ($column_name == "room_id") {
			$response[$column_name] = array("filter_condition" => array('=' => 'EQUAL', '!=' => "NOT EQUAL", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => CHtml::listData(Room::model()->findAllByAttributes(array('branch_id' => Yii::app()->session['branch_id'])), 'id', 'name'));
		} else if ($column_name == "session_type_id") {
			$response[$column_name] = array("filter_condition" => array('=' => 'EQUAL', '!=' => "NOT EQUAL", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => CHtml::listData(SessionRates::model()->findAllByAttributes(array('branch_id' => Yii::app()->session['branch_id'])), 'id', 'name'));
		} else if ($column_name == "branch_id") {
			$response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '!=' => "NOT EQUAL TO", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => CHtml::listData(Branch::model()->findAllByPk(Yii::app()->session['branch_id']), 'id', 'name'));
		} else {
			$response[$column_name] = array("filter_condition" => array('LIKE' => 'LIKE', 'LIKE %--%' => "LIKE %--%", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL', 'EMPTY' => 'EMPTY'), "filter_value" => array());
		}
		return $response;
	}

	public function getColumnValue($column_name, $column_value) {
		if ($column_name == "staff_id") {
			$column_value = StaffPersonalDetails::model()->findByPk($column_value)->name;
		} else if ($column_name == "room_id") {
			$column_value = Room::model()->findByPk($column_value)->name;
		} else if ($column_name == "activity_id") {
			$column_value = PayType::model()->findByPk($column_value)->abbreviation;
		} else if ($column_name == "branch_id") {
			$column_value = Branch::model()->findByPk($column_value)->name;
		} else if ($column_name == 'is_step_up') {
			$column_value = ($column_value == 1) ? "Yes" : "No";
		} else {
			$column_value = $column_value;
		}
		return $column_value;
	}

	/*
	 * Function to get all the confirmed shift between two dates.
	 */
	public static function staffBookingHoursBetweenDates($from_date, $to_date, $staff_id) {
		$criteria = new CDbCriteria();
		$criteria->condition = "(t.date_of_schedule BETWEEN :from_date AND :to_date) AND (t.staff_id = :staff_id) AND (t.is_booking_override = 0) AND (t.is_booking_confirm = 1) AND (activity.is_unpaid = 0)";
		$criteria->params = array(':from_date' => date("Y-m-d", strtotime($from_date)), ':to_date' => date("Y-m-d", strtotime($to_date)), ':staff_id' => $staff_id);
		$staffBookings = StaffBookings::model()->with('activity')->findAll($criteria);
		$hours = 0;
		if (!empty($staffBookings)) {
			foreach ($staffBookings AS $booking) {
				$hours += customFunctions::getHours($booking->start_time, $booking->finish_time);
			}
		}
		$reduction_hours = customFunctions::staffBookingsHoursReduction($staffBookings, $staffModel);
		$hours = $hours - $reduction_hours;
		if ($hours < 0) {
			$hours = 0;
		}
		return $hours;
	}

	public function afterSave() {
		if($this->is_booking_confirm == 1 && $this->staffNds->is_casual_staff == 1){
			StaffPersonalDetails::casualStaffEntitlement($this->staff_id, $this->date_of_schedule);
		}
		return parent::afterSave();
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
