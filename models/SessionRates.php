<?php

//Demo Commit
/**
 * This is the model class for table "tbl_session_rates".
 *
 * The followings are the available columns in table 'tbl_session_rates':
 * @property integer $id
 * @property string $name
 * @property string $description
 * @property integer $branch_id
 * @property integer $is_minimum
 * @property integer $minimum_time
 * @property string $start_time
 * @property string $finish_time
 * @property double $rate_max
 * @property double $rate_flat
 * @property string $is_deleted
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 * @property string $color
 * @property integer $is_active
 * @property integer $is_multiple_rates
 * @property integer $is_incremental_rates
 * @property integer $rate_flat_type
 * @property integer $multiple_rates_type
 * @property integer $is_modified
 * @property integer $priority
 * @property integer $is_global
 * @property integer $global_id
 * @property integer $create_for_exixting
 * @property integer $is_override_max_funded_hours_per_day
 * @property double $session_max_funded_hours_per_day
 * @property integer $include_on_registration_form
 * 
 * The followings are the available model relations:
 * @property Branch $branch
 */
class SessionRates extends CActiveRecord {

	public $effective_date;

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'tbl_session_rates';
	}

	public function afterFind() {
		$this->start_time = date("H:i", strtotime($this->start_time));
		$this->finish_time = date("H:i", strtotime($this->finish_time));
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
				$branchId = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->id))->branch_id;
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " .
					$this->getTableAlias(false, false) . ".branch_id =" . $branchId,
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
			array('name, description, color', 'required'),
			array('effective_date', 'required', 'on' => 'session_modify'),
			array('minimum_time, branch_id, is_minimum, is_active, is_multiple_rates,is_incremental_rates, rate_flat_type, multiple_rates_type, is_modified, created_by, updated_by, priority, is_override_max_funded_hours_per_day,include_on_registration_form', 'numerical', 'integerOnly' => true),
			array('rate_max, rate_flat, session_max_funded_hours_per_day', 'numerical'),
			array('name, is_deleted, color', 'length', 'max' => 45),
			array('description', 'length', 'max' => 255),
			array('start_time, finish_time, created, updated', 'safe'),
			array('finish_time', 'checkWithStartTime'),
			array('is_multiple_rates', 'selectedMultipleRateType'),
			array('start_time', 'checkBranchStartTime'),
			array('finish_time', 'checkBranchFinishTime'),
			array('priority', 'checkPriorityExists', 'except' => 'session_modify'),
			array('minimum_time', 'checkMinimumTimeDuration'),
			//array('name, description', 'match','pattern' => '/^[A-Za-z ]+$/u', 'message' => 'Only Alphabets are allowed'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, name, description,is_active branch_id, is_minimum, minimum_time, start_time, finish_time, rate_max, rate_flat,is_active, is_multiple_rates, is_deleted, created, color, rate_flat_type, multiple_rates_type, is_modified, updated, created_by, updated_by, priority, is_incremental_rates, is_override_max_funded_hours_per_day, session_max_funded_hours_per_day', 'safe', 'on' => 'search'),
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
			'branch_id' => 'Branch',
			'is_minimum' => 'Allow booking b/w start and finish time',
			'is_active' => 'Active',
			'minimum_time' => 'Minimum Booking Time (Minutes)',
			'start_time' => 'Start Time',
			'finish_time' => 'Finish Time',
			'rate_max' => 'Rate Max',
			'rate_flat' => 'Rate',
			'rate_flat_type' => 'Rate Type',
			'is_deleted' => 'Deleted',
			'created' => 'Created',
			'updated' => 'Updated',
			'created_by' => 'Created By',
			'updated_by' => 'Updated By',
			'color' => 'Color',
			'is_active' => 'Is Active',
			'is_multiple_rates' => 'Multiple Rates',
			'is_incremental_rates' => 'Incremental Rates',
			'multiple_rates_type' => 'Multiple Rates Type',
			'is_modified' => 'Is Modified',
			'effective_date' => 'Effective Date',
			'priority' => 'Priority for funding allocation',
			'is_override_max_funded_hours_per_day' => 'Override Max Funded Hours/Day',
			'session_max_funded_hours_per_day' => 'Maximum Funded Hours/Day',
                        'include_on_registration_form' => 'Show on Child Registration Form'
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
		if (isset(Yii::app()->session['global_id'])) {
			$criteria->compare('global_id', Yii::app()->session['global_id']);
			$criteria->compare('is_global', 1);
		} else {
			$criteria->compare('branch_id', Yii::app()->session['branch_id']);
			$criteria->compare('is_global', 0);
		}
		$criteria->compare('is_minimum', $this->is_minimum);
		$criteria->compare('minimum_time', $this->minimum_time);
		$criteria->compare('start_time', $this->start_time, true);
		$criteria->compare('finish_time', $this->finish_time, true);
		$criteria->compare('rate_max', $this->rate_max);
		$criteria->compare('rate_flat', $this->rate_flat);
		$criteria->compare('rate_flat_type', $this->rate_flat_type);
		$criteria->compare('is_deleted', $this->is_deleted, true);
		$criteria->compare('created', $this->created, true);
		$criteria->compare('updated', $this->updated, true);
		$criteria->compare('created_by', $this->created_by);
		$criteria->compare('updated_by', $this->updated_by);
		$criteria->compare('color', $this->color, true);
		$criteria->compare('is_active', $this->is_active);
		$criteria->compare('is_multiple_rates', $this->is_multiple_rates);
		$criteria->compare('is_incremental_rates', $this->is_incremental_rates);
		$criteria->compare('multiple_rates_type', $this->multiple_rates_type);
		$criteria->compare('is_modified', 0);
		$criteria->compare('priority', $this->priority);
		$criteria->compare('is_override_max_funded_hours_per_day', $this->is_override_max_funded_hours_per_day);
		$criteria->compare('session_max_funded_hours_per_day', $this->session_max_funded_hours_per_day);



		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'pagination' => array('pageSize' => 25),
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return SessionRates the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public function selectedMultipleRateType($attributes, $params) {
		if (($this->is_multiple_rates == 1) && ($this->multiple_rates_type == NULL)) {
			$this->addError('multiple_rates_type', 'Please select multiple rate type.');
		}
	}

	public function checkPriorityExists($attributes, $params) {
		if ($this->isNewRecord) {
			if (!empty($this->priority) && isset($this->priority)) {
				$model = SessionRates::model()->findByAttributes(['priority' => $this->priority, 'branch_id' => $this->branch_id]);
				if (!empty($model)) {
					$this->addError('priority', 'A session type with same priority already exists.');
				}
			}
		} else {
			if (!empty($this->priority) && isset($this->priority)) {
				$model = SessionRates::model()->find(['condition' => 'priority = :priority AND branch_id = :branch_id AND id != :id', 'params' => [':priority' => $this->priority, ':branch_id' => $this->branch_id, 'id' => $this->id]]);
				if (!empty($model)) {
					$this->addError('priority', 'A session type with same priority already exists.');
				}
			}
		}
	}

	public function checkBranchStartTime($attributes, $params) {
		Branch::model()->resetScope(true);
		$branchStartTime = Branch::model()->findByPk($this->branch_id)->operation_start_time;
		Branch::model()->resetScope(false);
		if (strtotime($this->start_time) < strtotime($branchStartTime)) {
			$this->addError('start_time', 'Session start time should be greater than branch operation start time.');
		}
	}

	public function checkBranchFinishTime($attributes, $params) {
		Branch::model()->resetScope(true);
		$branchFinishTime = Branch::model()->findByPk($this->branch_id)->operation_finish_time;
		Branch::model()->resetScope(false);
		if (strtotime($this->finish_time) > strtotime($branchFinishTime)) {
			$this->addError('finish_time', 'Session finish time should be smaller than branch operation finish time.');
		}
	}

	public function checkWithStartTime($attributes, $params) {
		if (strtotime($this->start_time) > strtotime($this->finish_time)) {
			$this->addError('finish_time', 'Finish time must be greater than start time.');
		}
	}

	public function getFilter($column_name) {
		$response = array();
		if ($column_name == "description" || $column_name == "start_time" || $column_name == "finish_time") {
			$response[$column_name] = array("filter_condition" => array('LIKE' => 'LIKE', 'LIKE %--%' => "LIKE %--%", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL', 'EMPTY' => 'EMPTY'), "filter_value" => array());
		}
		return $response;
	}

	public function getColumnValue($column_name, $column_value) {
		return $column_value;
	}

	public function getRelatedAttributesNames() {
		$attributes = array('description', 'start_time', 'finish_time');
		return $attributes;
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

	public function getPriorityValues() {
		$totalSessionCount = SessionRates::model()->countByAttributes(['branch_id' => $this->branch_id]);
		$priority = array();
		for ($i = 1; $i <= $totalSessionCount; $i++) {
			$priority[$i] = $i;
		}
		return $priority;
	}

	public function getPriorityDropdown($id, $value) {
		return CHtml::dropDownList('select_priority', $value, $this->getPriorityValues(), array('id' => 'select_priority_' . $id, 'data-id' => $id, 'empty' => 'Not Set', 'onChange' => 'updatePriority(this)'));
	}

	public function getModifiedSession($session_id, $weekStartDate) {
		$modifiedSessionsModel = SessionRatesHistory::model()->findAllByAttributes(array('session_id' => $session_id));
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
			$session_id = $modifiedSessionId;
		}
		return $session_id;
	}

	public static function actionGetMultipleRate($age, $session_id, $booking_hours, $funded_hours, $weekStartDate, $weekEndDate) {
		$modifiedSessionsModel = SessionRatesHistory::model()->findAllByAttributes(array(
			'session_id' => $session_id));
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
			$session_id = $modifiedSessionId;
		}
		$sessionModel = SessionRates::model()->findByPk($session_id);
		$rate = FALSE;
		$chargeable_hours = 0;
		$max_age_group = SessionRateMapping::model()->findBySql("select max(age_group) as max_age_group from tbl_session_rate_mapping where session_id = " . $session_id)->max_age_group;
		if ($age >= $max_age_group) {
			$age = $max_age_group - 0.5; // Logic for calucalting age group if age is greater than the max age group
		}
		if ($age < $max_age_group) {
			$criteria = new CDbCriteria();
			$criteria->condition = "age_group > :age AND session_id = :session_id";
			$criteria->order = "age_group";
			$criteria->limit = "1";
			$criteria->params = array(':age' => $age, ':session_id' => $session_id);
			$mappingModel = SessionRateMapping::model()->find($criteria);
			$time_rate_array = array();
			for ($i = 1; $i <= 9; $i++) {
				$time = "time_" . $i;
				$rate = "rate_" . $i;
				$time_rate_array[$mappingModel->$time] = $mappingModel->$rate;
			}
			$branchModel = Branch::currentBranch();
			if ($branchModel->funding_allocation_type == Branch::AS_PER_AVERAGE || $branchModel->funding_allocation_type == Branch::AS_PER_FUNDING_RATES) {
				$chargeable_hours = $booking_hours;
			} else {
				$chargeable_hours = ($booking_hours - $funded_hours) < 0 ? 0 : ($booking_hours - $funded_hours);
			}
			if ($sessionModel->is_incremental_rates == 1) {
				return (customFunctions::incremental_time($time_rate_array, $chargeable_hours));
			} else {
				return (customFunctions::closest_time($time_rate_array, $chargeable_hours));
			}
		} else {
			return $rate;
		}
	}

	public static function actionGetMultipleRatesWeekdays($age, $session_id, $total_booking_days, $weekStartDate, $weekEndDate) {
		$modifiedSessionsModel = SessionRatesHistory::model()->findAllByAttributes(array(
			'session_id' => $session_id));
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
			$session_id = $modifiedSessionId;
		}
		$rate = FALSE;
		$sessionModal = SessionRates::model()->findByPk($session_id);
		$average_rate = 0;
		$max_age_group = SessionRateWeekdayMapping::model()->findBySql("select max(age_group) as max_age_group from tbl_session_rate_weekday_mapping where session_id = " . $session_id)->max_age_group;
		if ($age >= $max_age_group) {
			$age = $max_age_group - 0.5; // Logic for calucalting age group if age is greater than the max age group
		}
		if ($age < $max_age_group) {
			$day_number = 1;
			$criteria = new CDbCriteria();
			$criteria->condition = "age_group > :age AND session_id = :session_id AND day_" . $day_number . " = :day_number";
			$criteria->order = "age_group";
			$criteria->limit = "1";
			$criteria->params = array(':age' => $age, ':session_id' => $session_id, ':day_number' => $day_number);
			$mappingModel = SessionRateWeekdayMapping::model()->find($criteria);
			$rate = "rate_" . $day_number;
			$average_rate = $mappingModel->$rate;
			if (count($total_booking_days) != 0) {
				$rate = sprintf('%0.2f', $average_rate / count($total_booking_days));
			}
			return $rate;
		} else {
			return $rate;
		}
	}

	public static function actionGetMultipleRatesTotalWeekdays($age, $session_id, $total_booking_days, $weekStartDate, $weekEndDate) {
		$modifiedSessionsModel = SessionRatesHistory::model()->findAllByAttributes(array(
			'session_id' => $session_id));
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
			$session_id = $modifiedSessionId;
		}
		$rate = FALSE;
		$sessionModal = SessionRates::model()->findByPk($session_id);
		$max_age_group = SessionRateTotalWeekdayMapping::model()->findBySql("select max(age_group) as max_age_group from tbl_session_rate_total_weekday_mapping where session_id = " . $session_id)->max_age_group;
		if ($age >= $max_age_group) {
			$age = $max_age_group - 0.5; // Logic for calucalting age group if age is greater than the max age group
		}
		if ($age < $max_age_group) {
			$criteria = new CDbCriteria();
			$criteria->condition = "age_group > :age AND session_id = :session_id";
			$criteria->order = "age_group";
			$criteria->limit = "1";
			$criteria->params = array(':age' => $age, ':session_id' => $session_id);
			$mappingModel = SessionRateTotalWeekdayMapping::model()->find($criteria);
			$total_weekday_rate_array = array();
			for ($i = 1; $i <= 7; $i++) {
				$total_day = "total_day_" . $i;
				$rate = "rate_" . $i;
				$total_weekday_rate_array[$mappingModel->$total_day] = $mappingModel->$rate;
			}
			$rate = sprintf('%0.2f', (customFunctions::closest_day($total_weekday_rate_array, $total_booking_days)));
			return $rate;
		} else {
			return $rate;
		}
	}

	public function getSessionRate($hours, $actualWeekStartDate, $actualWeekEndDate, $child) {
		$age = customFunctions::getAge(date("Y-m-d", strtotime($child->dob)), date("Y-m-d", strtotime("-1 day", strtotime($actualWeekStartDate))));
		$rate = 0;
		if ($this->is_multiple_rates == 1 && $this->multiple_rates_type == 1) {
			$calculated_rate = self::actionGetMultipleRate($age, $this->id, $hours, 0, $actualWeekStartDate, $actualWeekEndDate);
			if ($calculated_rate == FALSE) {
				$rate = 0;
			} else {
				$rate = self::actionGetMultipleRate($age, $this->id, $hours, 0, $actualWeekStartDate, $actualWeekEndDate);
			}
		} else if ($this->is_multiple_rates == 1 && $this->multiple_rates_type == 2) {
			$calculated_rate = self::actionGetMultipleRatesWeekdays($age, $this->id, 1, $actualWeekStartDate, $actualWeekEndDate);
			if ($calculated_rate == FALSE) {
				$rate = 0;
			} else {
				$rate = customFunctions::round($calculated_rate / $hours, 2);
			}
		} else if ($this->is_multiple_rates == 1 && $this->multiple_rates_type == 3) {
			$calculated_rate = self::actionGetMultipleRatesTotalWeekdays($age, $this->id, 1, $actualWeekStartDate, $actualWeekEndDate);
			if ($calculated_rate == FALSE) {
				$rate = 0;
			} else {
				$rate = customFunctions::round($calculated_rate / $hours, 2);
			}
		} else {
			$rate = customFunctions::getRateForMonhlyInvoicing($child->id, $this->id, $hours, $actualWeekStartDate, $actualWeekEndDate);
		}
		return $rate;
	}

	/*
	 * Function to check whether the minimum time duration is between session time
	 */

	public function checkMinimumTimeDuration($attributes, $params) {
		if ($this->is_minimum == 1) {
			$difference = round(abs(strtotime($this->finish_time) - strtotime($this->start_time)) / 60, 2);
			if ($this->minimum_time > $difference) {
				$this->addError('minimum_time', 'Minimum booking time exceeds session length - please adjust the session start/finish time or reduce the minimum booking time');
			}
		}
	}

}
