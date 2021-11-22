<?php

// Demo Commit
/**
 * This is the model class for table "tbl_staff_holidays".
 *
 * The followings are the available columns in table 'tbl_staff_holidays':
 *
 * @property integer $id
 * @property integer $branch_id
 * @property integer $staff_id
 * @property string $start_date
 * @property string $return_date
 * @property string $today_date
 * @property integer $staff_holidays_type_id
 * @property integer $staff_holidays_reason_id
 * @property string $description
 * @property string $comments
 * @property string $created_at
 * @property string $updated_at
 * @property integer $created_by
 * @property integer $updated_by
 * @property double $used
 * @property double $balance
 * @property double $holiday_hours
 * @property integer $is_unpaid
 * @property integer $is_confirmed
 * @property integer $branch_calendar_holiday_id The followings are the available model relations:
 * @property StaffHolidaysTypesReason $staffHolidaysReason
 * @property StaffPersonalDetails $staff
 * @property StaffHolidaysTypesReason $staffHolidaysType
 */
class StaffHolidays extends CActiveRecord {

	public $total_holiday_hours;

	/**
	 *
	 * @return string the associated database table name
	 */
	public $date_columns = array(
		'start_date',
		'return_date',
		'today_date'
	);

	public function tableName() {
		return 'tbl_staff_holidays';
	}

	public function defaultScope() {
		if (get_class(Yii::app()) === "CWebApplication") {
			if (Yii::app()->session['role'] == "superAdmin") {
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0"
				);
			}

			if (Yii::app()->session['role'] == "companyAdministrator") {
				$company_id = UserBranchMapping::model()->findByAttributes(array(
						'user_id' => Yii::app()->user->getId()
					))->company_id;
				$branchString = implode(',', customFunctions::getBranchesByCompany($company_id));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")"
				);
			}

			if (Yii::app()->session['role'] == "areaManager") {
				$userMappingModal = UserBranchMapping::model()->findAllByAttributes(array(
					'user_id' => Yii::app()->user->getId()
				));
				$branchString = implode(',', customFunctions::getBranchesOfAreaManager($userMappingModal));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")"
				);
			}
			if (Yii::app()->session['role'] == "branchManager") {
				$userMapping = UserBranchMapping::model()->findByAttributes(array(
					'user_id' => Yii::app()->user->getId()
				));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id =" . $userMapping->branch_id
				);
			}
			if (Yii::app()->session['role'] == "branchAdmin") {
				$userMapping = UserBranchMapping::model()->findByAttributes(array(
					'user_id' => Yii::app()->user->getId()
				));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id =" . $userMapping->branch_id
				);
			}
			if (Yii::app()->session['role'] == "hrAdmin" || Yii::app()->session['role'] == User::HR_STANDARD) {
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0"
				);
			}
			if (Yii::app()->session['role'] == "staff") {
				$userMapping = UserBranchMapping::model()->findByAttributes(array(
					'user_id' => Yii::app()->user->getId()
				));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id =" . $userMapping->branch_id . " and " . $this->getTableAlias(false, false) . ".staff_id = " . $userMapping->staff_id
				);
			}
			if (Yii::app()->session['role'] == "accountsAdmin") {
				$company_id = UserBranchMapping::model()->findByAttributes(array(
						'user_id' => Yii::app()->user->getId()
					))->company_id;
				$branchString = implode(',', customFunctions::getBranchesByCompany($company_id));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")"
				);
			}
		}
		if (get_class(Yii::app()) === "CConsoleApplication") {
			return array(
				'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0"
			);
		}
	}

	/**
	 *
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array(
				'branch_id, staff_id, staff_holidays_type_id, staff_holidays_reason_id,start_date,return_date',
				'required',
				'on' => 'branch_calendar_holiday'
			),
			array(
				'branch_id, staff_id, staff_holidays_type_id, staff_holidays_reason_id,start_date,return_date',
				'required',
				'except' => 'branch_calendar_holiday'
			),
			array(
				'branch_id, staff_id, staff_holidays_type_id, staff_holidays_reason_id, created_by, updated_by, is_unpaid, is_confirmed, branch_calendar_holiday_id',
				'numerical',
				'integerOnly' => true
			),
			array(
				'description, comments',
				'length',
				'max' => 255
			),
			array(
				'start_date, return_date, today_date, created_at, updated_at',
				'safe'
			),
			array(
				'finish_date, start_date',
				'validationsOnHolidaySave',
			),
			array(
				'staff_holidays_reason_id',
				'validateHolidayReason',
				'except' => 'branch_calendar_holiday'
			),
			array(
				'holiday_hours',
				'validateHolidayHours',
				'except' => 'branch_calendar_holiday'
			),
			array(
				'used, balance,holiday_hours',
				'numerical'
			),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array(
				'id, branch_id, staff_id, start_date, return_date, today_date, staff_holidays_type_id, staff_holidays_reason_id, description, comments, branch_calendar_holiday_id, created_at, updated_at, created_by, updated_by, is_confirmed, is_unpaid',
				'safe',
				'on' => 'search'
			)
		);
	}

	/**
	 *
	 * @return array relational rules.
	 */
	public function relations() {
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'staffHolidaysReason' => array(
				self::BELONGS_TO,
				'StaffHolidaysTypesReason',
				'staff_holidays_reason_id'
			),
			'staff' => array(
				self::BELONGS_TO,
				'StaffPersonalDetails',
				'staff_id'
			),
			'staffNds' => array(
				self::BELONGS_TO,
				'StaffPersonalDetailsNds',
				'staff_id'
			),
			'staffHolidaysType' => array(
				self::BELONGS_TO,
				'StaffHolidaysTypesReason',
				'staff_holidays_type_id'
			)
		);
	}

	public function getColumnNames() {
		$unset_columns = array(
			'id',
			'is_deleted',
			'created_at',
			'is_unpaid',
			'updated_at',
			'created_by',
			'updated_by',
			'comments',
			'used',
			'balance'
		);
		$attributes = $this->getAttributes();
		return array_diff(array_keys($attributes), $unset_columns);
	}

	public function getRelatedAttributes() {
		$attributes = array();
		$attributes['StaffPersonalDetails'] = StaffPersonalDetails::model()->getRelatedAttributesNames();
		return $attributes;
	}

	/**
	 *
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'branch_id' => 'Branch',
			'staff_id' => 'Staff',
			'start_date' => 'Start Date',
			'return_date' => 'Finish Date',
			'today_date' => 'Date of Request',
			'staff_holidays_type_id' => 'Type of Absence',
			'staff_holidays_reason_id' => 'Reason',
			'description' => 'Notes/Description',
			'comments' => 'Notes',
			'created_at' => 'Created At',
			'updated_at' => 'Updated At',
			'created_by' => 'Created By',
			'updated_by' => 'Updated By',
			'used' => 'Used',
			'balance' => 'Balance',
			'holiday_hours' => 'Holiday Hours(Hours used in current Holiday)',
			'is_unpaid' => 'Unpaid',
			'is_confirmed' => 'Confirmed',
			'branch_calendar_holiday_id' => 'Branch Calendar Holiday'
		);
	}

	public function behaviors() {
		return array(
			'dateFormatter' => array(
				'class' => 'application.components.DateFormatter',
				'date_columns' => array(
					'start_date',
					'return_date',
					'today_date'
				)
			)
		);
	}

	public function getFilter($column_name) {
		$response = array();
		if ($column_name == "exclude_from_invoice") {
			$response[$column_name] = array(
				"filter_condition" => array(
					'=' => 'EQUAL TO',
					'!=' => "NOT EQUAL TO"
				),
				"filter_value" => array(
					0 => 0,
					1 => 1
				)
			);
		} else
		if ($column_name == "start_date" || $column_name == "return_date" || $column_name == "today_date") {
			$response[$column_name] = array(
				"filter_condition" => array(
					'=' => 'EQUAL TO',
					'>' => "GREATER THAN",
					'<' => 'SMALLER THAN',
					'>=' => 'GREATER THAN EQUAL TO',
					'<=' => 'SMALLER THAN EQUAL TO',
					'IS NULL' => 'IS NULL',
					'IS NOT NULL' => 'IS NOT NULL'
				),
				"filter_value" => array()
			);
		} else
		if ($column_name == "staff_id") {
			$response[$column_name] = array(
				"filter_condition" => array(
					'=' => 'EQUAL TO',
					'!=' => "NOT EQUAL TO",
					'IS NULL' => 'IS NULL',
					'IS NOT NULL' => 'IS NOT NULL'
				),
				"filter_value" => CHtml::listData(StaffPersonalDetails::model()->findAllByAttributes(array(
						'branch_id' => Yii::app()->session['branch_id']
					)), 'id', 'first_name')
			);
		} else
		if ($column_name == "staff_holidays_type_id") {
			$response[$column_name] = array(
				"filter_condition" => array(
					'=' => 'EQUAL TO',
					'!=' => "NOT EQUAL TO",
					'IS NULL' => 'IS NULL',
					'IS NOT NULL' => 'IS NOT NULL'
				),
				"filter_value" => CHtml::listData(StaffHolidaysTypesReason::model()->findAll(), 'id', 'type_of_absence')
			);
		} else
		if ($column_name == "staff_holidays_reason_id") {
			$response[$column_name] = array(
				"filter_condition" => array(
					'=' => 'EQUAL TO',
					'!=' => "NOT EQUAL TO",
					'IS NULL' => 'IS NULL',
					'IS NOT NULL' => 'IS NOT NULL'
				),
				"filter_value" => CHtml::listData(StaffHolidaysTypesReason::model()->findAll(array(
						'condition' => 'reason != ""'
					)), 'id', 'reason')
			);
		} else
		if ($column_name == "branch_id") {
			$response[$column_name] = array(
				"filter_condition" => array(
					'=' => 'EQUAL TO',
					'!=' => "NOT EQUAL TO",
					'IS NULL' => 'IS NULL',
					'IS NOT NULL' => 'IS NOT NULL'
				),
				"filter_value" => CHtml::listData(Branch::model()->findAllByPk(Yii::app()->session['branch_id']), 'id', 'name')
			);
		} else {
			$response[$column_name] = array(
				"filter_condition" => array(
					'LIKE' => 'LIKE',
					'LIKE %--%' => "LIKE %--%",
					'IS NULL' => 'IS NULL',
					'IS NOT NULL' => 'IS NOT NULL',
					'EMPTY' => 'EMPTY'
				),
				"filter_value" => array()
			);
		}
		return $response;
	}

	public function getColumnValue($column_name, $column_value) {
		if ($column_name == "exclude_from_invoice") {
			$column_value = ($column_value == 1) ? "Yes" : "No";
		} else
		if ($column_name == "staff_holidays_type_id") {
			$column_value = StaffHolidaysTypesReason::model()->findByPk($column_value)->type_of_absence;
		} else
		if ($column_name == "staff_holidays_reason_id") {
			$column_value = StaffHolidaysTypesReason::model()->findByPk($column_value)->reason;
		} else
		if ($column_name == "staff_id") {
			$column_value = StaffPersonalDetails::model()->resetScope()->findByPk($column_value)->name;
		} else
		if ($column_name == "branch_id") {
			$column_value = Branch::model()->findByPk($column_value)->name;
		} else {
			$column_value = $column_value;
		}
		return $column_value;
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
	 *         based on the search/filter conditions.
	 */
	public function search($staff_id, $year, $is_unpaid = NULL, $start_date = NULL, $return_date = NULL, $staff_holidays_type_id = NULL, $staff_holidays_reason_id = NULL, $description = NULL, $holiday_hours = NULL) {
		// @todo Please modify the following code to remove attributes that should not be searched.
		if ($is_unpaid == NULL) {
			$is_unpaid = $this->is_unpaid;
		}
		if ($start_date == NULL) {
			$start_date = $this->start_date;
		}
		if ($return_date == NULL) {
			$return_date = $this->return_date;
		}
		if ($staff_holidays_type_id == NULL) {
			$staff_holidays_type_id = $this->staff_holidays_type_id;
		}
		if ($staff_holidays_reason_id == NULL) {
			$staff_holidays_reason_id = $this->staff_holidays_reason_id;
		}
		if ($description == NULL) {
			$description = $this->description;
		}
		if ($holiday_hours == NULL) {
			$holiday_hours = $this->holiday_hours;
		}

		$criteria = new CDbCriteria();
		$criteria->compare('id', $this->id);
		$criteria->compare('branch_id', $this->branch_id);
		$criteria->compare('staff_id', $staff_id);
		$criteria->compare('start_date', $start_date, true);
		$criteria->compare('return_date', $return_date, true);
		$criteria->compare('today_date', $this->today_date, true);
		$criteria->compare('staff_holidays_type_id', $staff_holidays_type_id);
		$criteria->compare('staff_holidays_reason_id', $staff_holidays_reason_id);
		$criteria->compare('description', $description, true);
		$criteria->compare('comments', $this->comments, true);
		$criteria->compare('is_unpaid', $is_unpaid);
		$criteria->compare('holiday_hours', $holiday_hours);
		$criteria->compare('is_confirmed', $this->is_confirmed, 'AND');
		$criteria->compare('branch_calendar_holiday_id', $this->branch_calendar_holiday_id);
		$criteria->addBetweenCondition('start_date', $year . "-01-01", $year . "-12-31", 'AND');
		$criteria->addBetweenCondition('return_date', $year . "-01-01", $year . "-12-31");

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'sort' => array(
				'defaultOrder' => 'start_date, return_date ASC'
			),
			'pagination' => array(
				'pageSize' => 100
			)
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 *
	 * @param string $className
	 *            active record class name.
	 * @return StaffHolidays the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public function validationsOnHolidaySave($attributes, $params) {
		if (isset($this->staff->start_date, $this->start_date) && !empty($this->staff->start_date) && !empty($this->start_date)) {
			if (strtotime(date("Y-m-d", strtotime($this->start_date))) < strtotime(date("Y-m-d", strtotime($this->staff->start_date)))) {
				$this->addError('start_date', 'Holiday can only be scheduled after Staff Start Date..');
			}
		}

		if (isset($this->staff->leave_date, $this->return_date) && !empty($this->staff->leave_date) && !empty($this->return_date)) {
			if (strtotime(date("Y-m-d", strtotime($this->return_date))) > strtotime(date("Y-m-d", strtotime($this->staff->leave_date)))) {
				$this->addError('start_date', 'Holiday can only be scheduled before Staff Leave Date..');
			}
		}

		if (!empty($this->start_date) && !empty($this->return_date)) {
			if (strtotime(date("Y-m-d", strtotime($this->start_date))) > strtotime(date("Y-m-d", strtotime($this->return_date)))) {
				$this->addError('return_date', 'Finish Date must be greater than or equals to holiday Start Date.');
			}
		}

		if (!empty($this->start_date) && !empty($this->return_date)) {
			if (date("W", strtotime($this->start_date)) != date("W", strtotime($this->return_date))) {
				$this->addError('return_date', 'Start Date and Finish Date of Holiday should be present in the same week.');
			}

			if (date("Y", strtotime($this->start_date)) != date("Y", strtotime($this->return_date))) {
				$this->addError('return_date', 'Start Date and Finish Date of Holiday should be present in the same Year..');
			}
		}
	}

	public function validateHolidayReason($attributes, $params) {
		if ($this->staff_holidays_reason_id == 3) {
			if (empty($this->description)) {
				$this->addError('description', 'Please provide description for Holiday.');
			}
		}
	}

	public function validateHolidayHours($attributes, $params) {
		if ($this->is_unpaid == 0) {
			if (isset($this->holiday_hours)) {
				if ($this->holiday_hours <= 0) {
					$this->addError('holiday_hours', "Holiday Hours in current holiday can not be - " . $this->holiday_hours);
				}
				$model = StaffHolidaysEntitlement::model()->findByAttributes(array(
					'staff_id' => $this->staff_id,
					'year' => date("Y", strtotime($this->start_date))
				));
				if (!empty($model)) {
					$criteria = new CDbCriteria();
					$criteria->select = "sum(holiday_hours) AS total_holiday_hours";
					$criteria->condition = "((start_date >= :start_date and start_date <= :return_date) OR " . "(return_date >= :start_date and return_date <= :return_date) OR" . "(start_date <= :start_date and return_date >= :return_date)) AND  staff_id = :staff_id AND is_unpaid = 0";
					$criteria->params = array(
						':start_date' => date("Y-m-d", strtotime($model->start_date)),
						':return_date' => date("Y-m-d", strtotime($model->finish_date)),
						':staff_id' => $model->staff_id
					);
					$staffHolidayModel = StaffHolidays::model()->find($criteria);
					$staffHolidayModel->total_holiday_hours = customFunctions::round($staffHolidayModel->total_holiday_hours, 2);
					if ($this->holiday_hours > customFunctions::round(($model->holiday_entitlement_per_year - $staffHolidayModel->total_holiday_hours), 2)) {
						$this->addError('holiday_hours', "Holiday Entitlement Allocation can not be more than Balance - " . customFunctions::round(($model->holiday_entitlement_per_year - $staffHolidayModel->total_holiday_hours), 2));
					}
					$thisWeekDates = customFunctions::getWeekDates(date('W', strtotime($this->start_date)), date("Y", strtotime($this->start_date)));
					$criteria = new CDbCriteria();
					$criteria->select = "sum(holiday_hours) AS total_holiday_hours";
					$criteria->condition = "((start_date >= :start_date and start_date <= :return_date) OR " . "(return_date >= :start_date and return_date <= :return_date) OR" . "(start_date <= :start_date and return_date >= :return_date)) AND  staff_id = :staff_id AND is_unpaid = 0";
					$criteria->params = array(
						':start_date' => $thisWeekDates['week_start_date'],
						':return_date' => $thisWeekDates['week_end_date'],
						':staff_id' => $model->staff_id
					);
					$holidayHoursUsedThisWeekModel = StaffHolidays::model()->find($criteria);
					if (!empty($holidayHoursUsedThisWeekModel)) {
						$staffHolidayEntitlementEvents = StaffHolidaysEntitlementEvents::model()->find(array(
							'condition' => ':date BETWEEN start_date and finish_date and holiday_id = :holiday_id',
							'params' => array(
								':holiday_id' => $model->id,
								':date' => date("Y-m-d", strtotime($this->start_date))
							)
						));
						if (!empty($staffHolidayEntitlementEvents)) {
							if ($this->staff->is_casual_staff == 0) {
								$allowedContractHoursPerWeek = $staffHolidayEntitlementEvents->contract_hours;
								if (customFunctions::round(($holidayHoursUsedThisWeekModel->total_holiday_hours + $this->holiday_hours), 2) > customFunctions::round($allowedContractHoursPerWeek, 2)) {
									$this->addError('holiday_hours', "Holiday Hours in this week can not exceed  " . customFunctions::round($allowedContractHoursPerWeek, 2));
								}
							}
						} else {
							$this->addError('holiday_hours', 'Holiday Entitlement Allocation Event is not present for year - ' . date("Y", strtotime($this->start_date)));
						}
					}
				} else {
					$this->addError('holiday_hours', 'Holiday Entitlement Allocation is not present for year - ' . date("Y", strtotime($this->start_date)));
				}
			} else {
				$this->addError('holiday_hours', "Please fill the applicable Holiday Hours.");
			}
		}
		if ($this->is_unpaid == 1) {
			if ($this->holiday_hours <= 0) {
				$this->addError('holiday_hours', 'Holiday Hours Should be greater than - ' . $this->holiday_hours);
			}
		}
	}

    public function checkHolidayHours($staffId, $holidayStartDate, $holidayFinishDate, $absenceType, $holidayHours, $isUnpaid)
    {
		if ($isUnpaid == 1) {
			return true;
		}
		$response = true;
		$noOfHolidays = floor((strtotime($holidayFinishDate) - strtotime($holidayStartDate)) / 86400) + 1;
		$staffModel = StaffPersonalDetails::model()->findByPk($staffId);
		if ($staffModel->is_casual_staff == 0) {
			$contractHoursPerWeek = sprintf("%0.2f", $staffModel->contract_hours);
			$contractDaysPerWeek = sprintf("%0.2f", $staffModel->no_of_days);
			$contractHoursPerDay = sprintf("%0.2f", ($contractHoursPerWeek / $contractDaysPerWeek));
			if ($absenceType == 1) {
				if (floatval(sprintf("%0.2f", $holidayHours)) < floatval((sprintf("%0.2f", $noOfHolidays * $contractHoursPerDay)))) {
					$this->addError('holiday_hours', 'Holiday hours marked are less than avaliable hours.');
					$response = false;
				}
			}
			return $response;
		} else {
			return true;
		}
	}

	public static function holidayHoursUsed($staff_id) {
		$model = StaffHolidays::model()->findAll([
			'condition' => 'staff_id = :staff_id AND is_unpaid = 0',
			'params' => array(
				':staff_id' => $staff_id
			)
		]);
		$usedHours = 0;
		if (!empty($model)) {
			foreach ($model as $holiday) {
				$usedHours += $holiday->holiday_hours;
			}
		}
		return $usedHours;
	}
}
