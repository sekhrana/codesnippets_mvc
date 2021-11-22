<?php

/**
 * This is the model class for table "tbl_staff_holidays_entitlement".
 *
 * The followings are the available columns in table 'tbl_staff_holidays_entitlement':
 * @property integer $id
 * @property integer $branch_id
 * @property integer $staff_id
 * @property string $year
 * @property string $start_date
 * @property string $finish_date
 * @property double $contract_hours_per_week
 * @property double $days_per_week
 * @property double $days_per_year
 * @property double $holiday_entitlement_per_year
 * @property integer $is_deleted
 * @property integer $is_overridden
 * @property integer $opening_balance
 */
class StaffHolidaysEntitlement extends CActiveRecord {

	public $date_columns = array(
		'start_date',
		'finish_date'
	);
	public $used;
	public $balance;
	public $effective_date;
	public $previous_contract_hours;
	public $new_contract_hours;
	public $new_entitlement;
	public $transferred_entitlement;
	public $transfer_from;
	public $transfer_to;
	public $previous_contract_no_of_days;
	public $new_contract_no_of_days;
	public $reset_future_entitlement;
	public $opening_balance_entitlement;
	public $previous_contract_no_of_days_per_year;
	public $new_contract_no_of_days_per_year;
	public $reset_future_contract_type;

	/**
	 *
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'tbl_staff_holidays_entitlement';
	}

	public function defaultScope() {
		if (get_class(Yii::app()) === "CWebApplication") {
			return array(
				'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0"
			);
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
				'branch_id, staff_id, year, start_date, finish_date',
				'required',
				'except' => 'changeContractType'
			),
			array(
				'new_entitlement',
				'required',
				'on' => 'overrideEntitlement'
			),
			array(
				'opening_balance_entitlement',
				'required',
				'on' => 'openingBalanceEntitlement'
			),
			array(
				'transferred_entitlement, transfer_to',
				'required',
				'on' => 'transferEntitlement'
			),
			array(
				'branch_id, staff_id, year, start_date, finish_date, effective_date, previous_contract_hours, new_contract_hours, previous_contract_no_of_days, new_contract_no_of_days',
				'required',
				'on' => 'changeEntitlement'
			),
			array(
				'branch_id, staff_id, effective_date, previous_contract_hours, new_contract_hours, previous_contract_no_of_days, new_contract_no_of_days, previous_contract_no_of_days_per_year, new_contract_no_of_days_per_year',
				'required',
				'on' => 'changeContractType'
			),
			array(
				'branch_id, staff_id, is_deleted, is_overridden',
				'numerical',
				'integerOnly' => true
			),
			array(
				'contract_hours_per_week, days_per_week, days_per_year, holiday_entitlement_per_year',
				'numerical'
			),
			array(
				'year',
				'length',
				'max' => 45
			),
			array(
				'used, balance',
				'safe'
			),
			array(
				'effective_date',
				'checkEffectiveDate',
				'on' => 'changeEntitlement'
			),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array(
				'id, branch_id, staff_id, year, start_date, finish_date, contract_hours_per_week, days_per_week, days_per_year, holiday_entitlement_per_year, is_deleted, used, balance, is_overridden , opening_balance',
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
			'staffHolidaysEntitlementEvents' => array(
				self::HAS_MANY,
				'StaffHolidaysEntitlementEvents',
				'holiday_id'
			),
			'staff' => array(self::BELONGS_TO, 'StaffPersonalDetails', 'staff_id'),
			'staffNds' => array(self::BELONGS_TO, 'StaffPersonalDetailsNds', 'staff_id'),
		);
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
			'year' => 'Year',
			'start_date' => 'Start Date',
			'finish_date' => 'Finish Date',
			'contract_hours_per_week' => 'Contract Hours Per Week',
			'days_per_week' => 'Days Per Week',
			'days_per_year' => 'Days Per Year',
			'holiday_entitlement_per_year' => 'Holiday Entitlement Per Year',
			'is_deleted' => 'Deleted',
			'is_overridden' => 'Overridden',
			'previous_contract_no_of_days' => 'Previous Contract No. Of Days/Week',
			'new_contract_no_of_days' => 'New Contract No. Of Days/Week',
			'reset_future_entitlement' => 'Reset Future Entitlement (This will reset entitlement for future years.)',
			'opening_balance' => 'Opening Balance',
			'reset_future_contract_type' => 'Reset Future Values (This will reset for years.)',
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
	 *         based on the search/filter conditions.
	 */
	public function search() {
		// @todo Please modify the following code to remove attributes that should not be searched.
		$criteria = new CDbCriteria();

		$criteria->compare('id', $this->id);
		$criteria->compare('branch_id', $this->branch_id);
		$criteria->compare('staff_id', $_GET['staff_id']);
		$criteria->compare('year', $this->year, true);
		$criteria->compare('start_date', $this->start_date, true);
		$criteria->compare('finish_date', $this->finish_date, true);
		$criteria->compare('contract_hours_per_week', $this->contract_hours_per_week);
		$criteria->compare('days_per_week', $this->days_per_week);
		$criteria->compare('days_per_year', $this->days_per_year);
		$criteria->compare('holiday_entitlement_per_year', $this->holiday_entitlement_per_year);
		$criteria->compare('is_deleted', $this->is_deleted);
		$criteria->compare('is_overridden', $this->is_overridden);
		$criteria->compare('opening_balance', $this->opening_balance);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'sort' => array(
				'defaultOrder' => 'year, start_date, finish_date ASC'
			),
			'pagination' => array(
				'pageSize' => 5
			)
		));
	}

	public function behaviors() {
		return array(
			'dateFormatter' => array(
				'class' => 'application.components.DateFormatter',
				'date_columns' => array(
					'finish_date',
					'start_date'
				)
			)
		);
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 *
	 * @param string $className
	 *            active record class name.
	 * @return StaffHolidaysEntitlement the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public function setEntitlement($staff_id) {
		$model = StaffPersonalDetails::model()->findByPk($staff_id);
		if (!empty($model)) {
			$staffStartDate = date("Y-m-d", strtotime($model->start_date));
			$staffLeaveDate = date("Y-m-d", strtotime($model->leave_date));
			if (empty($model->start_date)) {
				$staffStartDate = date("Y") . "-01-01";
			}
			if (date("Y", strtotime($staffStartDate)) < date("Y")) {
				$staffStartDate = date("Y") . "-01-01";
			}
			if (empty($model->leave_date)) {
				$staffLeaveDate = (date("Y") + 4) . "-12-31";
			}
			$staffStartYear = date("Y", strtotime($staffStartDate));
			$staffFinishYear = date("Y", strtotime($staffLeaveDate));
			for ($i = date("Y", strtotime($staffStartDate)); $i <= date("Y", strtotime($staffLeaveDate)); $i ++) {
				StaffPersonalDetails::$holiday_contract_as_of = $i;
				$model = StaffPersonalDetails::model()->findByPk($staff_id);
				$yearStartDate = $i . "-01-01";
				$yearFinishDate = $i . "-12-31";
				if (date("Y", strtotime($staffStartDate)) == $i && strtotime(date("Y-m-d", strtotime($staffStartDate))) > strtotime(date("Y-m-d", strtotime($yearStartDate)))) {
					$yearStartDate = $staffStartDate;
				}
				if (date("Y", strtotime($staffLeaveDate)) == $i && strtotime(date("Y-m-d", strtotime($staffLeaveDate))) < strtotime(date("Y-m-d", strtotime($yearFinishDate)))) {
					$yearFinishDate = $staffLeaveDate;
				}
				$daysInYear = (date("L", strtotime($year . "-01-01")) == 1) ? 366 : 365;
				if (date("m-d", strtotime($yearFinishDate)) == "12-31" && date("m-d", strtotime($yearStartDate)) == "01-01") {
					$staffDays = $daysInYear;
				} else {
					$daysInBetween = customFunctions::getDatesOfDays(date("Y-m-d", strtotime($yearStartDate)), date("Y-m-d", strtotime($yearFinishDate)), array(
							1,
							2,
							3,
							4,
							5,
							6,
							0
					));
					$daysInBetween = count($daysInBetween);
					$staffDays = $daysInBetween;
				}
				$entitlement = ($staffDays / $daysInYear) * $model->holiday_contract * ($model->contract_hours / 5);
				$entitlement = number_format($entitlement, 2, '.', '');
				$entitlement = ceil($entitlement / 0.25) * 0.25;
				$holidayEntitlementModel = new StaffHolidaysEntitlement();
				$holidayEntitlementModel->branch_id = $model->branch_id;
				$holidayEntitlementModel->staff_id = $model->id;
				$holidayEntitlementModel->start_date = $yearStartDate;
				$holidayEntitlementModel->finish_date = $yearFinishDate;
				$holidayEntitlementModel->holiday_entitlement_per_year = $entitlement;
				$holidayEntitlementModel->year = $i;
				$holidayEntitlementModel->contract_hours_per_week = $model->contract_hours;
				$holidayEntitlementModel->days_per_week = $model->no_of_days;
				$holidayEntitlementModel->days_per_year = $model->holiday_contract;
				$checkEntitlementExists = StaffHolidaysEntitlement::model()->findByAttributes(array(
					'staff_id' => $holidayEntitlementModel->staff_id,
					'start_date' => $holidayEntitlementModel->start_date,
					'finish_date' => $holidayEntitlementModel->finish_date,
					'year' => $holidayEntitlementModel->year
				));
				if ($checkEntitlementExists) {
					$checkEntitlementExists->attributes = $holidayEntitlementModel->attributes;
					if (!$checkEntitlementExists->save()) {
						throw new Exception(CHtml::errorSummary($checkEntitlementExists, "", "", array(
							'class' => 'customErrors'
						)));
					}
					$staffHolidaysEntitlementEvents = new StaffHolidaysEntitlementEvents();
					$staffHolidaysEntitlementEvents->holiday_id = $checkEntitlementExists->id;
					$staffHolidaysEntitlementEvents->start_date = $checkEntitlementExists->start_date;
					$staffHolidaysEntitlementEvents->finish_date = $checkEntitlementExists->finish_date;
					$staffHolidaysEntitlementEvents->entitlement = $checkEntitlementExists->holiday_entitlement_per_year;
					$staffHolidaysEntitlementEvents->contract_hours = $checkEntitlementExists->contract_hours_per_week;
					$staffHolidaysEntitlementEvents->branch_id = $model->branch_id;
					$staffHolidaysEntitlementEvents->no_of_days = $checkEntitlementExists->days_per_week;
					if (!$staffHolidaysEntitlementEvents->save()) {
						throw new Exception(CHtml::errorSummary($staffHolidaysEntitlementEvents, "", "", array(
							'class' => 'customErrors'
						)));
					}
				} else {
					if (!$holidayEntitlementModel->save()) {
						throw new Exception(CHtml::errorSummary($holidayEntitlementModel, "", "", array(
							'class' => 'customErrors'
						)));
					}
					$staffHolidaysEntitlementEvents = new StaffHolidaysEntitlementEvents();
					$staffHolidaysEntitlementEvents->holiday_id = $holidayEntitlementModel->id;
					$staffHolidaysEntitlementEvents->start_date = $holidayEntitlementModel->start_date;
					$staffHolidaysEntitlementEvents->finish_date = $holidayEntitlementModel->finish_date;
					$staffHolidaysEntitlementEvents->entitlement = $holidayEntitlementModel->holiday_entitlement_per_year;
					$staffHolidaysEntitlementEvents->contract_hours = $holidayEntitlementModel->contract_hours_per_week;
					$staffHolidaysEntitlementEvents->branch_id = $model->branch_id;
					$staffHolidaysEntitlementEvents->no_of_days = $holidayEntitlementModel->days_per_week;
					if (!$staffHolidaysEntitlementEvents->save()) {
						throw new Exception(CHtml::errorSummary($staffHolidaysEntitlementEvents, "", "", array(
							'class' => 'customErrors'
						)));
					}
				}
			}
		}
	}
    
    	public function setEntitlementOneYear($staff_id , $entitlementYear) {
		$model = StaffPersonalDetails::model()->findByPk($staff_id);
		if (!empty($model)) {
			$staffStartDate = $entitlementYear."-01-01";
			$staffLeaveDate = $entitlementYear."-12-31";

            if(!empty($model->start_date)){
              if(date("Y", strtotime($model->start_date)) == $entitlementYear){
                $staffStartDate = $model->start_date;
              }
            }
            
			
            if(!empty($model->leave_date)){
              if(date("Y", strtotime($model->leave_date)) == $entitlementYear){
                $staffLeaveDate = $model->leave_date;
              }
            }  
			$staffStartYear = date("Y", strtotime($staffStartDate));
			$staffFinishYear = date("Y", strtotime($staffLeaveDate));
			for ($i = date("Y", strtotime($staffStartDate)); $i <= date("Y", strtotime($staffLeaveDate)); $i++) {
				StaffPersonalDetails::$holiday_contract_as_of = $i;
				$model = StaffPersonalDetails::model()->findByPk($staff_id);
				$yearStartDate = $i . "-01-01";
				$yearFinishDate = $i . "-12-31";
				if (date("Y", strtotime($staffStartDate)) == $i && strtotime(date("Y-m-d", strtotime($staffStartDate))) > strtotime(date("Y-m-d", strtotime($yearStartDate)))) {
					$yearStartDate = $staffStartDate;
				}
				if (date("Y", strtotime($staffLeaveDate)) == $i && strtotime(date("Y-m-d", strtotime($staffLeaveDate))) < strtotime(date("Y-m-d", strtotime($yearFinishDate)))) {
					$yearFinishDate = $staffLeaveDate;
				}
				$daysInYear = (date("L", strtotime($year . "-01-01")) == 1) ? 366 : 365;
				if (date("m-d", strtotime($yearFinishDate)) == "12-31" && date("m-d", strtotime($yearStartDate)) == "01-01") {
					$staffDays = $daysInYear;
				} else {
					$daysInBetween = customFunctions::getDatesOfDays(date("Y-m-d", strtotime($yearStartDate)), date("Y-m-d", strtotime($yearFinishDate)), array(
							1,
							2,
							3,
							4,
							5,
							6,
							0
					));
					$daysInBetween = count($daysInBetween);
					$staffDays = $daysInBetween;
				}
				$entitlement = ($staffDays / $daysInYear) * $model->holiday_contract * ($model->contract_hours / 5);
				$entitlement = number_format($entitlement, 2, '.', '');
				$entitlement = ceil($entitlement / 0.25) * 0.25;
				$holidayEntitlementModel = new StaffHolidaysEntitlement();
				$holidayEntitlementModel->branch_id = $model->branch_id;
				$holidayEntitlementModel->staff_id = $model->id;
				$holidayEntitlementModel->start_date = $yearStartDate;
				$holidayEntitlementModel->finish_date = $yearFinishDate;
				$holidayEntitlementModel->holiday_entitlement_per_year = $entitlement;
				$holidayEntitlementModel->year = $i;
				$holidayEntitlementModel->contract_hours_per_week = $model->contract_hours;
				$holidayEntitlementModel->days_per_week = $model->no_of_days;
				$holidayEntitlementModel->days_per_year = $model->holiday_contract;
				$checkEntitlementExists = StaffHolidaysEntitlement::model()->findByAttributes(array(
					'staff_id' => $holidayEntitlementModel->staff_id,
					'start_date' => $holidayEntitlementModel->start_date,
					'finish_date' => $holidayEntitlementModel->finish_date,
					'year' => $holidayEntitlementModel->year
				));
				if ($checkEntitlementExists) {
					$checkEntitlementExists->attributes = $holidayEntitlementModel->attributes;
					if (!$checkEntitlementExists->save()) {
						throw new Exception(CHtml::errorSummary($checkEntitlementExists, "", "", array(
							'class' => 'customErrors'
						)));
					}
					$staffHolidaysEntitlementEvents = new StaffHolidaysEntitlementEvents();
					$staffHolidaysEntitlementEvents->holiday_id = $checkEntitlementExists->id;
					$staffHolidaysEntitlementEvents->start_date = $checkEntitlementExists->start_date;
					$staffHolidaysEntitlementEvents->finish_date = $checkEntitlementExists->finish_date;
					$staffHolidaysEntitlementEvents->entitlement = $checkEntitlementExists->holiday_entitlement_per_year;
					$staffHolidaysEntitlementEvents->contract_hours = $checkEntitlementExists->contract_hours_per_week;
					$staffHolidaysEntitlementEvents->branch_id = $model->branch_id;
					$staffHolidaysEntitlementEvents->no_of_days = $checkEntitlementExists->days_per_week;
					if (!$staffHolidaysEntitlementEvents->save()) {
						throw new Exception(CHtml::errorSummary($staffHolidaysEntitlementEvents, "", "", array(
							'class' => 'customErrors'
						)));
					}
				} else {
					if (!$holidayEntitlementModel->save()) {
						throw new Exception(CHtml::errorSummary($holidayEntitlementModel, "", "", array(
							'class' => 'customErrors'
						)));
					}
					$staffHolidaysEntitlementEvents = new StaffHolidaysEntitlementEvents();
					$staffHolidaysEntitlementEvents->holiday_id = $holidayEntitlementModel->id;
					$staffHolidaysEntitlementEvents->start_date = $holidayEntitlementModel->start_date;
					$staffHolidaysEntitlementEvents->finish_date = $holidayEntitlementModel->finish_date;
					$staffHolidaysEntitlementEvents->entitlement = $holidayEntitlementModel->holiday_entitlement_per_year;
					$staffHolidaysEntitlementEvents->contract_hours = $holidayEntitlementModel->contract_hours_per_week;
					$staffHolidaysEntitlementEvents->branch_id = $model->branch_id;
					$staffHolidaysEntitlementEvents->no_of_days = $holidayEntitlementModel->days_per_week;
					if (!$staffHolidaysEntitlementEvents->save()) {
						throw new Exception(CHtml::errorSummary($staffHolidaysEntitlementEvents, "", "", array(
							'class' => 'customErrors'
						)));
					}
				}
			}
		}
	}

	public function setEntitlementAprilToMarch($staff_id) {
		$model = StaffPersonalDetails::model()->findByPk($staff_id);
		if (!empty($model)) {
			$staffStartDate = date("Y-m-d", strtotime($model->start_date));
			$staffLeaveDate = date("Y-m-d", strtotime($model->leave_date));
			if (empty($model->start_date)) {
				$staffStartDate = date("Y") . "-04-01";
			}
			if (date("Y", strtotime($staffStartDate)) < date("Y")) {
				$staffStartDate = date("Y") . "-04-01";
			}
			$loopLeaveYear = date("Y", strtotime($model->leave_date));
			if (empty($model->leave_date)) {
				$loopLeaveYear = (date("Y") + 4);
				$staffLeaveDate = (date("Y") + 5) . "-03-31";
			}
			$staffStartYear = date("Y", strtotime($staffStartDate));
			$staffFinishYear = date("Y", strtotime($staffLeaveDate));
			for ($i = date("Y", strtotime($staffStartDate)); $i <= $loopLeaveYear; $i ++) {
				StaffPersonalDetails::$holiday_contract_as_of = $i;
				$model = StaffPersonalDetails::model()->findByPk($staff_id);
				$yearStartDate = $i . "-04-01";
				$yearFinishDate = ($i + 1) . "-03-31";
				if (date("Y", strtotime($staffStartDate)) == $i) {
					if (strtotime($staffStartDate) > strtotime($yearStartDate)) {
						$yearStartDate = $staffStartDate;
					}
				}
				if (date("Y", strtotime($staffLeaveDate)) == $i) {
					if (strtotime($staffLeaveDate) < strtotime($yearStartDate)) {
						continue;
					}
					$yearFinishDate = $staffLeaveDate;
				}
				if (date("Y", strtotime($staffLeaveDate)) == $i + 1) {
					if (strtotime($staffLeaveDate) < strtotime($yearFinishDate)) {
						$yearFinishDate = $staffLeaveDate;
					}
				}
				$daysInYear = (date("L", strtotime($year . "-01-01")) == 1) ? 366 : 365;
				$daysInBetween = customFunctions::getDatesOfDays(date("Y-m-d", strtotime($yearStartDate)), date("Y-m-d", strtotime($yearFinishDate)), array(
						1,
						2,
						3,
						4,
						5,
						6,
						0
				));
				$daysInBetween = count($daysInBetween);
				$staffDays = $daysInBetween;
				$entitlement = ($staffDays / $daysInYear) * $model->holiday_contract * ($model->contract_hours / 5);
				$entitlement = number_format($entitlement, 2, '.', '');
				$entitlement = ceil($entitlement / 0.25) * 0.25;
				$holidayEntitlementModel = new StaffHolidaysEntitlement();
				$holidayEntitlementModel->branch_id = $model->branch_id;
				$holidayEntitlementModel->staff_id = $model->id;
				$holidayEntitlementModel->start_date = $yearStartDate;
				$holidayEntitlementModel->finish_date = $yearFinishDate;
				$holidayEntitlementModel->holiday_entitlement_per_year = $entitlement;
				$holidayEntitlementModel->year = $i;
				$holidayEntitlementModel->contract_hours_per_week = $model->contract_hours;
				$holidayEntitlementModel->days_per_week = $model->no_of_days;
				$holidayEntitlementModel->days_per_year = $model->holiday_contract;
				$checkEntitlementExists = StaffHolidaysEntitlement::model()->findByAttributes(array(
					'staff_id' => $holidayEntitlementModel->staff_id,
					'start_date' => $holidayEntitlementModel->start_date,
					'finish_date' => $holidayEntitlementModel->finish_date,
					'year' => $holidayEntitlementModel->year
				));
				if ($checkEntitlementExists) {
					$checkEntitlementExists->attributes = $holidayEntitlementModel->attributes;
					if (!$checkEntitlementExists->save()) {
						throw new Exception(CHtml::errorSummary($checkEntitlementExists, "", "", array(
							'class' => 'customErrors'
						)));
					}
					$staffHolidaysEntitlementEvents = new StaffHolidaysEntitlementEvents();
					$staffHolidaysEntitlementEvents->holiday_id = $checkEntitlementExists->id;
					$staffHolidaysEntitlementEvents->start_date = $checkEntitlementExists->start_date;
					$staffHolidaysEntitlementEvents->finish_date = $checkEntitlementExists->finish_date;
					$staffHolidaysEntitlementEvents->entitlement = $checkEntitlementExists->holiday_entitlement_per_year;
					$staffHolidaysEntitlementEvents->contract_hours = $checkEntitlementExists->contract_hours_per_week;
					$staffHolidaysEntitlementEvents->branch_id = $model->branch_id;
					$staffHolidaysEntitlementEvents->no_of_days = $checkEntitlementExists->days_per_week;
					if (!$staffHolidaysEntitlementEvents->save()) {
						throw new Exception(CHtml::errorSummary($staffHolidaysEntitlementEvents, "", "", array(
							'class' => 'customErrors'
						)));
					}
				} else {
					if (!$holidayEntitlementModel->save()) {
						throw new Exception(CHtml::errorSummary($holidayEntitlementModel, "", "", array(
							'class' => 'customErrors'
						)));
					}
					$staffHolidaysEntitlementEvents = new StaffHolidaysEntitlementEvents();
					$staffHolidaysEntitlementEvents->holiday_id = $holidayEntitlementModel->id;
					$staffHolidaysEntitlementEvents->start_date = $holidayEntitlementModel->start_date;
					$staffHolidaysEntitlementEvents->finish_date = $holidayEntitlementModel->finish_date;
					$staffHolidaysEntitlementEvents->entitlement = $holidayEntitlementModel->holiday_entitlement_per_year;
					$staffHolidaysEntitlementEvents->contract_hours = $holidayEntitlementModel->contract_hours_per_week;
					$staffHolidaysEntitlementEvents->branch_id = $model->branch_id;
					$staffHolidaysEntitlementEvents->no_of_days = $holidayEntitlementModel->days_per_week;
					if (!$staffHolidaysEntitlementEvents->save()) {
						throw new Exception(CHtml::errorSummary($staffHolidaysEntitlementEvents, "", "", array(
							'class' => 'customErrors'
						)));
					}
				}
			}
		}
	}
    
    public function setEntitlementAprilToMarchOneYear($staff_id , $entitlementYear) {
		$model = StaffPersonalDetails::model()->findByPk($staff_id);
		if (!empty($model)) {
          
          
            $staffStartDate = $entitlementYear. "-04-01";
			$staffLeaveDate = ((int)$entitlementYear + 1)."-03-31";

            if(!empty($model->start_date)){
              if(date("Y", strtotime($model->start_date)) == $entitlementYear && strtotime($model->start_date) > strtotime($staffStartDate ) ){
                $staffStartDate = $model->start_date;
              }
            }
            
			$loopLeaveYear = $entitlementYear;
            if(!empty($model->leave_date)){
              if( strtotime($model->leave_date) < strtotime($staffLeaveDate )){
//                $loopLeaveYear = date("Y", strtotime($model->leave_date));
                $staffLeaveDate = $model->leave_date;
              }
            }

			$staffStartYear = date("Y", strtotime($staffStartDate));
			$staffFinishYear = date("Y", strtotime($staffLeaveDate));
			for ($i = date("Y", strtotime($staffStartDate)); $i <= $loopLeaveYear; $i ++) {
				StaffPersonalDetails::$holiday_contract_as_of = $i;
				$model = StaffPersonalDetails::model()->findByPk($staff_id);
				$yearStartDate = $i . "-04-01";
				$yearFinishDate = ($i + 1) . "-03-31";
				if (date("Y", strtotime($staffStartDate)) == $i) {
					if (strtotime($staffStartDate) > strtotime($yearStartDate)) {
						$yearStartDate = $staffStartDate;
					}
				}
				if (date("Y", strtotime($staffLeaveDate)) == $i) {
					if (strtotime($staffLeaveDate) < strtotime($yearStartDate)) {
						continue;
					}
					$yearFinishDate = $staffLeaveDate;
				}
				if (date("Y", strtotime($staffLeaveDate)) == $i + 1) {
					if (strtotime($staffLeaveDate) < strtotime($yearFinishDate)) {
						$yearFinishDate = $staffLeaveDate;
					}
				}
				$daysInYear = (date("L", strtotime($year . "-01-01")) == 1) ? 366 : 365;
				$daysInBetween = customFunctions::getDatesOfDays(date("Y-m-d", strtotime($yearStartDate)), date("Y-m-d", strtotime($yearFinishDate)), array(
						1,
						2,
						3,
						4,
						5,
						6,
						0
				));
				$daysInBetween = count($daysInBetween);
				$staffDays = $daysInBetween;
				$entitlement = ($staffDays / $daysInYear) * $model->holiday_contract * ($model->contract_hours / 5);
				$entitlement = number_format($entitlement, 2, '.', '');
				$entitlement = ceil($entitlement / 0.25) * 0.25;
				$holidayEntitlementModel = new StaffHolidaysEntitlement();
				$holidayEntitlementModel->branch_id = $model->branch_id;
				$holidayEntitlementModel->staff_id = $model->id;
				$holidayEntitlementModel->start_date = $yearStartDate;
				$holidayEntitlementModel->finish_date = $yearFinishDate;
				$holidayEntitlementModel->holiday_entitlement_per_year = $entitlement;
				$holidayEntitlementModel->year = $i;
				$holidayEntitlementModel->contract_hours_per_week = $model->contract_hours;
				$holidayEntitlementModel->days_per_week = $model->no_of_days;
				$holidayEntitlementModel->days_per_year = $model->holiday_contract;
				$checkEntitlementExists = StaffHolidaysEntitlement::model()->findByAttributes(array(
					'staff_id' => $holidayEntitlementModel->staff_id,
					'start_date' => $holidayEntitlementModel->start_date,
					'finish_date' => $holidayEntitlementModel->finish_date,
					'year' => $holidayEntitlementModel->year
				));
				if ($checkEntitlementExists) {
					$checkEntitlementExists->attributes = $holidayEntitlementModel->attributes;
					if (!$checkEntitlementExists->save()) {
						throw new Exception(CHtml::errorSummary($checkEntitlementExists, "", "", array(
							'class' => 'customErrors'
						)));
					}
					$staffHolidaysEntitlementEvents = new StaffHolidaysEntitlementEvents();
					$staffHolidaysEntitlementEvents->holiday_id = $checkEntitlementExists->id;
					$staffHolidaysEntitlementEvents->start_date = $checkEntitlementExists->start_date;
					$staffHolidaysEntitlementEvents->finish_date = $checkEntitlementExists->finish_date;
					$staffHolidaysEntitlementEvents->entitlement = $checkEntitlementExists->holiday_entitlement_per_year;
					$staffHolidaysEntitlementEvents->contract_hours = $checkEntitlementExists->contract_hours_per_week;
					$staffHolidaysEntitlementEvents->branch_id = $model->branch_id;
					$staffHolidaysEntitlementEvents->no_of_days = $checkEntitlementExists->days_per_week;
					if (!$staffHolidaysEntitlementEvents->save()) {
						throw new Exception(CHtml::errorSummary($staffHolidaysEntitlementEvents, "", "", array(
							'class' => 'customErrors'
						)));
					}
				} else {
					if (!$holidayEntitlementModel->save()) {
						throw new Exception(CHtml::errorSummary($holidayEntitlementModel, "", "", array(
							'class' => 'customErrors'
						)));
					}
					$staffHolidaysEntitlementEvents = new StaffHolidaysEntitlementEvents();
					$staffHolidaysEntitlementEvents->holiday_id = $holidayEntitlementModel->id;
					$staffHolidaysEntitlementEvents->start_date = $holidayEntitlementModel->start_date;
					$staffHolidaysEntitlementEvents->finish_date = $holidayEntitlementModel->finish_date;
					$staffHolidaysEntitlementEvents->entitlement = $holidayEntitlementModel->holiday_entitlement_per_year;
					$staffHolidaysEntitlementEvents->contract_hours = $holidayEntitlementModel->contract_hours_per_week;
					$staffHolidaysEntitlementEvents->branch_id = $model->branch_id;
					$staffHolidaysEntitlementEvents->no_of_days = $holidayEntitlementModel->days_per_week;
					if (!$staffHolidaysEntitlementEvents->save()) {
						throw new Exception(CHtml::errorSummary($staffHolidaysEntitlementEvents, "", "", array(
							'class' => 'customErrors'
						)));
					}
				}
			}
		}
	}

	public function setEntitlementSepToAugust($staff_id) {
		$model = StaffPersonalDetails::model()->findByPk($staff_id);
		if (!empty($model)) {
			$staffStartDate = date("Y-m-d", strtotime($model->start_date));
			$staffLeaveDate = date("Y-m-d", strtotime($model->leave_date));
			if (empty($model->start_date)) {
				$staffStartDate = date("Y") . "-09-01";
			}
			if (date("Y", strtotime($staffStartDate)) < date("Y")) {
				$staffStartDate = date("Y") . "-09-01";
			}
			$loopLeaveYear = date("Y", strtotime($model->leave_date));
			if (empty($model->leave_date)) {
				$loopLeaveYear = (date("Y") + 4);
				$staffLeaveDate = (date("Y") + 5) . "-08-31";
			}
			$staffStartYear = date("Y", strtotime($staffStartDate));
			$staffFinishYear = date("Y", strtotime($staffLeaveDate));
			for ($i = date("Y", strtotime($staffStartDate)); $i <= $loopLeaveYear; $i ++) {
				StaffPersonalDetails::$holiday_contract_as_of = $i;
				$model = StaffPersonalDetails::model()->findByPk($staff_id);
				$yearStartDate = $i . "-09-01";
				$yearFinishDate = ($i + 1) . "-08-31";
				if (date("Y", strtotime($staffStartDate)) == $i) {
					if (strtotime($staffStartDate) > strtotime($yearStartDate)) {
						$yearStartDate = $staffStartDate;
					}
				}
				if (date("Y", strtotime($staffLeaveDate)) == $i) {
					if (strtotime($staffLeaveDate) < strtotime($yearStartDate)) {
						continue;
					}
					$yearFinishDate = $staffLeaveDate;
				}
				if (date("Y", strtotime($staffLeaveDate)) == $i + 1) {
					if (strtotime($staffLeaveDate) < strtotime($yearFinishDate)) {
						$yearFinishDate = $staffLeaveDate;
					}
				}
				$daysInYear = (date("L", strtotime($year . "-01-01")) == 1) ? 366 : 365;
				$daysInBetween = customFunctions::getDatesOfDays(date("Y-m-d", strtotime($yearStartDate)), date("Y-m-d", strtotime($yearFinishDate)), array(
						1,
						2,
						3,
						4,
						5,
						6,
						0
				));
				$daysInBetween = count($daysInBetween);
				$staffDays = $daysInBetween;
				$entitlement = ($staffDays / $daysInYear) * $model->holiday_contract * ($model->contract_hours / 5);
				$entitlement = number_format($entitlement, 2, '.', '');
				$entitlement = ceil($entitlement / 0.25) * 0.25;
				$holidayEntitlementModel = new StaffHolidaysEntitlement();
				$holidayEntitlementModel->branch_id = $model->branch_id;
				$holidayEntitlementModel->staff_id = $model->id;
				$holidayEntitlementModel->start_date = $yearStartDate;
				$holidayEntitlementModel->finish_date = $yearFinishDate;
				$holidayEntitlementModel->holiday_entitlement_per_year = $entitlement;
				$holidayEntitlementModel->year = $i;
				$holidayEntitlementModel->contract_hours_per_week = $model->contract_hours;
				$holidayEntitlementModel->days_per_week = $model->no_of_days;
				$holidayEntitlementModel->days_per_year = $model->holiday_contract;
				$checkEntitlementExists = StaffHolidaysEntitlement::model()->findByAttributes(array(
					'staff_id' => $holidayEntitlementModel->staff_id,
					'start_date' => $holidayEntitlementModel->start_date,
					'finish_date' => $holidayEntitlementModel->finish_date,
					'year' => $holidayEntitlementModel->year
				));
				if ($checkEntitlementExists) {
					$checkEntitlementExists->attributes = $holidayEntitlementModel->attributes;
					if (!$checkEntitlementExists->save()) {
						throw new Exception(CHtml::errorSummary($checkEntitlementExists, "", "", array(
							'class' => 'customErrors'
						)));
					}
					$staffHolidaysEntitlementEvents = new StaffHolidaysEntitlementEvents();
					$staffHolidaysEntitlementEvents->holiday_id = $checkEntitlementExists->id;
					$staffHolidaysEntitlementEvents->start_date = $checkEntitlementExists->start_date;
					$staffHolidaysEntitlementEvents->finish_date = $checkEntitlementExists->finish_date;
					$staffHolidaysEntitlementEvents->entitlement = $checkEntitlementExists->holiday_entitlement_per_year;
					$staffHolidaysEntitlementEvents->contract_hours = $checkEntitlementExists->contract_hours_per_week;
					$staffHolidaysEntitlementEvents->branch_id = $model->branch_id;
					$staffHolidaysEntitlementEvents->no_of_days = $checkEntitlementExists->days_per_week;
					if (!$staffHolidaysEntitlementEvents->save()) {
						throw new Exception(CHtml::errorSummary($staffHolidaysEntitlementEvents, "", "", array(
							'class' => 'customErrors'
						)));
					}
				} else {
					if (!$holidayEntitlementModel->save()) {
						throw new Exception(CHtml::errorSummary($holidayEntitlementModel, "", "", array(
							'class' => 'customErrors'
						)));
					}
					$staffHolidaysEntitlementEvents = new StaffHolidaysEntitlementEvents();
					$staffHolidaysEntitlementEvents->holiday_id = $holidayEntitlementModel->id;
					$staffHolidaysEntitlementEvents->start_date = $holidayEntitlementModel->start_date;
					$staffHolidaysEntitlementEvents->finish_date = $holidayEntitlementModel->finish_date;
					$staffHolidaysEntitlementEvents->entitlement = $holidayEntitlementModel->holiday_entitlement_per_year;
					$staffHolidaysEntitlementEvents->contract_hours = $holidayEntitlementModel->contract_hours_per_week;
					$staffHolidaysEntitlementEvents->branch_id = $model->branch_id;
					$staffHolidaysEntitlementEvents->no_of_days = $holidayEntitlementModel->days_per_week;
					if (!$staffHolidaysEntitlementEvents->save()) {
						throw new Exception(CHtml::errorSummary($staffHolidaysEntitlementEvents, "", "", array(
							'class' => 'customErrors'
						)));
					}
				}
			}
		}
	}
    
    public function setEntitlementSepToAugustOneYear($staff_id) {
		$model = StaffPersonalDetails::model()->findByPk($staff_id);
		if (!empty($model)) {
            
            $staffStartDate = $entitlementYear. "-09-01";
			$staffLeaveDate = ((int)$entitlementYear + 1)."-08-31";

            if(!empty($model->start_date)){
              if(date("Y", strtotime($model->start_date)) == $entitlementYear && strtotime($model->start_date) > strtotime($staffStartDate ) ){
                $staffStartDate = $model->start_date;
              }
            }
            
			$loopLeaveYear = $entitlementYear; //date("Y", strtotime($staffStartDate));
            if(!empty($model->leave_date)){
              if( strtotime($model->leave_date) < strtotime($staffLeaveDate )){
//                $loopLeaveYear = date("Y", strtotime($model->leave_date));
                $staffLeaveDate = $model->leave_date;
              }
            }
            
            
			$staffStartYear = date("Y", strtotime($staffStartDate));
			$staffFinishYear = date("Y", strtotime($staffLeaveDate));
			for ($i = date("Y", strtotime($staffStartDate)); $i <= $loopLeaveYear; $i ++) {
				StaffPersonalDetails::$holiday_contract_as_of = $i;
				$model = StaffPersonalDetails::model()->findByPk($staff_id);
				$yearStartDate = $i . "-09-01";
				$yearFinishDate = ($i + 1) . "-08-31";
				if (date("Y", strtotime($staffStartDate)) == $i) {
					if (strtotime($staffStartDate) > strtotime($yearStartDate)) {
						$yearStartDate = $staffStartDate;
					}
				}
				if (date("Y", strtotime($staffLeaveDate)) == $i) {
					if (strtotime($staffLeaveDate) < strtotime($yearStartDate)) {
						continue;
					}
					$yearFinishDate = $staffLeaveDate;
				}
				if (date("Y", strtotime($staffLeaveDate)) == $i + 1) {
					if (strtotime($staffLeaveDate) < strtotime($yearFinishDate)) {
						$yearFinishDate = $staffLeaveDate;
					}
				}
				$daysInYear = (date("L", strtotime($year . "-01-01")) == 1) ? 366 : 365;
				$daysInBetween = customFunctions::getDatesOfDays(date("Y-m-d", strtotime($yearStartDate)), date("Y-m-d", strtotime($yearFinishDate)), array(
						1,
						2,
						3,
						4,
						5,
						6,
						0
				));
				$daysInBetween = count($daysInBetween);
				$staffDays = $daysInBetween;
				$entitlement = ($staffDays / $daysInYear) * $model->holiday_contract * ($model->contract_hours / 5);
				$entitlement = number_format($entitlement, 2, '.', '');
				$entitlement = ceil($entitlement / 0.25) * 0.25;
				$holidayEntitlementModel = new StaffHolidaysEntitlement();
				$holidayEntitlementModel->branch_id = $model->branch_id;
				$holidayEntitlementModel->staff_id = $model->id;
				$holidayEntitlementModel->start_date = $yearStartDate;
				$holidayEntitlementModel->finish_date = $yearFinishDate;
				$holidayEntitlementModel->holiday_entitlement_per_year = $entitlement;
				$holidayEntitlementModel->year = $i;
				$holidayEntitlementModel->contract_hours_per_week = $model->contract_hours;
				$holidayEntitlementModel->days_per_week = $model->no_of_days;
				$holidayEntitlementModel->days_per_year = $model->holiday_contract;
				$checkEntitlementExists = StaffHolidaysEntitlement::model()->findByAttributes(array(
					'staff_id' => $holidayEntitlementModel->staff_id,
					'start_date' => $holidayEntitlementModel->start_date,
					'finish_date' => $holidayEntitlementModel->finish_date,
					'year' => $holidayEntitlementModel->year
				));
				if ($checkEntitlementExists) {
					$checkEntitlementExists->attributes = $holidayEntitlementModel->attributes;
					if (!$checkEntitlementExists->save()) {
						throw new Exception(CHtml::errorSummary($checkEntitlementExists, "", "", array(
							'class' => 'customErrors'
						)));
					}
					$staffHolidaysEntitlementEvents = new StaffHolidaysEntitlementEvents();
					$staffHolidaysEntitlementEvents->holiday_id = $checkEntitlementExists->id;
					$staffHolidaysEntitlementEvents->start_date = $checkEntitlementExists->start_date;
					$staffHolidaysEntitlementEvents->finish_date = $checkEntitlementExists->finish_date;
					$staffHolidaysEntitlementEvents->entitlement = $checkEntitlementExists->holiday_entitlement_per_year;
					$staffHolidaysEntitlementEvents->contract_hours = $checkEntitlementExists->contract_hours_per_week;
					$staffHolidaysEntitlementEvents->branch_id = $model->branch_id;
					$staffHolidaysEntitlementEvents->no_of_days = $checkEntitlementExists->days_per_week;
					if (!$staffHolidaysEntitlementEvents->save()) {
						throw new Exception(CHtml::errorSummary($staffHolidaysEntitlementEvents, "", "", array(
							'class' => 'customErrors'
						)));
					}
				} else {
					if (!$holidayEntitlementModel->save()) {
						throw new Exception(CHtml::errorSummary($holidayEntitlementModel, "", "", array(
							'class' => 'customErrors'
						)));
					}
					$staffHolidaysEntitlementEvents = new StaffHolidaysEntitlementEvents();
					$staffHolidaysEntitlementEvents->holiday_id = $holidayEntitlementModel->id;
					$staffHolidaysEntitlementEvents->start_date = $holidayEntitlementModel->start_date;
					$staffHolidaysEntitlementEvents->finish_date = $holidayEntitlementModel->finish_date;
					$staffHolidaysEntitlementEvents->entitlement = $holidayEntitlementModel->holiday_entitlement_per_year;
					$staffHolidaysEntitlementEvents->contract_hours = $holidayEntitlementModel->contract_hours_per_week;
					$staffHolidaysEntitlementEvents->branch_id = $model->branch_id;
					$staffHolidaysEntitlementEvents->no_of_days = $holidayEntitlementModel->days_per_week;
					if (!$staffHolidaysEntitlementEvents->save()) {
						throw new Exception(CHtml::errorSummary($staffHolidaysEntitlementEvents, "", "", array(
							'class' => 'customErrors'
						)));
					}
				}
			}
		}
	}

	public function setEntitlementForCasualStaff($staff_id) {
		$model = StaffPersonalDetails::model()->findByPk($staff_id);
		if (!empty($model)) {
			$staffStartDate = date("Y-m-d", strtotime($model->start_date));
			$staffLeaveDate = date("Y-m-d", strtotime($model->leave_date));
			if (empty($model->start_date)) {
				$staffStartDate = date("Y") . "-01-01";
			}
			if (date("Y", strtotime($staffStartDate)) < date("Y")) {
				$staffStartDate = date("Y") . "-01-01";
			}
			if (empty($model->leave_date)) {
				$staffLeaveDate = (date("Y") + 4) . "-12-31";
			}
			$staffStartYear = date("Y", strtotime($staffStartDate));
			$staffFinishYear = date("Y", strtotime($staffLeaveDate));
			for ($i = date("Y", strtotime($staffStartDate)); $i <= date("Y", strtotime($staffLeaveDate)); $i ++) {
				StaffPersonalDetails::$holiday_contract_as_of = $i;
				$model = StaffPersonalDetails::model()->findByPk($staff_id);
				$yearStartDate = $i . "-01-01";
				$yearFinishDate = $i . "-12-31";
				if (date("Y", strtotime($staffStartDate)) == $i && strtotime(date("Y-m-d", strtotime($staffStartDate))) > strtotime(date("Y-m-d", strtotime($yearStartDate)))) {
					$yearStartDate = $staffStartDate;
				}
				if (date("Y", strtotime($staffLeaveDate)) == $i && strtotime(date("Y-m-d", strtotime($staffLeaveDate))) < strtotime(date("Y-m-d", strtotime($yearFinishDate)))) {
					$yearFinishDate = $staffLeaveDate;
				}
				$entitlement = StaffPersonalDetails::getCasualStaffEntitlement($model->id, $yearStartDate, $yearFinishDate);
				$holidayEntitlementModel = new StaffHolidaysEntitlement();
				$holidayEntitlementModel->branch_id = $model->branch_id;
				$holidayEntitlementModel->staff_id = $model->id;
				$holidayEntitlementModel->start_date = $yearStartDate;
				$holidayEntitlementModel->finish_date = $yearFinishDate;
				$holidayEntitlementModel->holiday_entitlement_per_year = $entitlement;
				$holidayEntitlementModel->year = $i;
				$holidayEntitlementModel->contract_hours_per_week = $model->contract_hours;
				$holidayEntitlementModel->days_per_week = $model->no_of_days;
				$holidayEntitlementModel->days_per_year = $model->holiday_contract;
				$checkEntitlementExists = StaffHolidaysEntitlement::model()->findByAttributes(array(
					'staff_id' => $holidayEntitlementModel->staff_id,
					'start_date' => $holidayEntitlementModel->start_date,
					'finish_date' => $holidayEntitlementModel->finish_date,
					'year' => $holidayEntitlementModel->year
				));
				if ($checkEntitlementExists) {
					$checkEntitlementExists->attributes = $holidayEntitlementModel->attributes;
					if (!$checkEntitlementExists->save()) {
						throw new Exception(CHtml::errorSummary($checkEntitlementExists, "", "", array(
							'class' => 'customErrors'
						)));
					}
					$staffHolidaysEntitlementEvents = new StaffHolidaysEntitlementEvents();
					$staffHolidaysEntitlementEvents->holiday_id = $checkEntitlementExists->id;
					$staffHolidaysEntitlementEvents->start_date = $checkEntitlementExists->start_date;
					$staffHolidaysEntitlementEvents->finish_date = $checkEntitlementExists->finish_date;
					$staffHolidaysEntitlementEvents->entitlement = $checkEntitlementExists->holiday_entitlement_per_year;
					$staffHolidaysEntitlementEvents->contract_hours = $checkEntitlementExists->contract_hours_per_week;
					$staffHolidaysEntitlementEvents->branch_id = $model->branch_id;
					$staffHolidaysEntitlementEvents->no_of_days = $checkEntitlementExists->days_per_week;
					if (!$staffHolidaysEntitlementEvents->save()) {
						throw new Exception(CHtml::errorSummary($staffHolidaysEntitlementEvents, "", "", array(
							'class' => 'customErrors'
						)));
					}
				} else {
					if (!$holidayEntitlementModel->save()) {
						throw new Exception(CHtml::errorSummary($holidayEntitlementModel, "", "", array(
							'class' => 'customErrors'
						)));
					}
					$staffHolidaysEntitlementEvents = new StaffHolidaysEntitlementEvents();
					$staffHolidaysEntitlementEvents->holiday_id = $holidayEntitlementModel->id;
					$staffHolidaysEntitlementEvents->start_date = $holidayEntitlementModel->start_date;
					$staffHolidaysEntitlementEvents->finish_date = $holidayEntitlementModel->finish_date;
					$staffHolidaysEntitlementEvents->entitlement = $entitlement;
					$staffHolidaysEntitlementEvents->contract_hours = $holidayEntitlementModel->contract_hours_per_week;
					$staffHolidaysEntitlementEvents->branch_id = $model->branch_id;
					$staffHolidaysEntitlementEvents->no_of_days = $holidayEntitlementModel->days_per_week;
					if (!$staffHolidaysEntitlementEvents->save()) {
						throw new Exception(CHtml::errorSummary($staffHolidaysEntitlementEvents, "", "", array(
							'class' => 'customErrors'
						)));
					}
				}
			}
		}
	}

    
    	public function setEntitlementForCasualStaffOneYear($staff_id, $entitlementYear) {
		$model = StaffPersonalDetails::model()->findByPk($staff_id);
		if (!empty($model)) {
			 
            $staffStartDate = $entitlementYear."-01-01";
			$staffLeaveDate = $entitlementYear."-12-31";

            if(!empty($model->start_date)){
              if(date("Y", strtotime($model->start_date)) == $entitlementYear){
                $staffStartDate = $model->start_date;
              }
            }
            
			
            if(!empty($model->leave_date)){
              if(date("Y", strtotime($model->leave_date)) == $entitlementYear){
                $staffLeaveDate = $model->leave_date;
              }
            }
            
            
			$staffStartYear = date("Y", strtotime($staffStartDate));
			$staffFinishYear = date("Y", strtotime($staffLeaveDate));
			for ($i = date("Y", strtotime($staffStartDate)); $i <= date("Y", strtotime($staffLeaveDate)); $i ++) {
				StaffPersonalDetails::$holiday_contract_as_of = $i;
				$model = StaffPersonalDetails::model()->findByPk($staff_id);
				$yearStartDate = $i . "-01-01";
				$yearFinishDate = $i . "-12-31";
				if (date("Y", strtotime($staffStartDate)) == $i && strtotime(date("Y-m-d", strtotime($staffStartDate))) > strtotime(date("Y-m-d", strtotime($yearStartDate)))) {
					$yearStartDate = $staffStartDate;
				}
				if (date("Y", strtotime($staffLeaveDate)) == $i && strtotime(date("Y-m-d", strtotime($staffLeaveDate))) < strtotime(date("Y-m-d", strtotime($yearFinishDate)))) {
					$yearFinishDate = $staffLeaveDate;
				}
				$entitlement = StaffPersonalDetails::getCasualStaffEntitlement($model->id, $yearStartDate, $yearFinishDate);
				$holidayEntitlementModel = new StaffHolidaysEntitlement();
				$holidayEntitlementModel->branch_id = $model->branch_id;
				$holidayEntitlementModel->staff_id = $model->id;
				$holidayEntitlementModel->start_date = $yearStartDate;
				$holidayEntitlementModel->finish_date = $yearFinishDate;
				$holidayEntitlementModel->holiday_entitlement_per_year = $entitlement;
				$holidayEntitlementModel->year = $i;
				$holidayEntitlementModel->contract_hours_per_week = $model->contract_hours;
				$holidayEntitlementModel->days_per_week = $model->no_of_days;
				$holidayEntitlementModel->days_per_year = $model->holiday_contract;
				$checkEntitlementExists = StaffHolidaysEntitlement::model()->findByAttributes(array(
					'staff_id' => $holidayEntitlementModel->staff_id,
					'start_date' => $holidayEntitlementModel->start_date,
					'finish_date' => $holidayEntitlementModel->finish_date,
					'year' => $holidayEntitlementModel->year
				));
				if ($checkEntitlementExists) {
					$checkEntitlementExists->attributes = $holidayEntitlementModel->attributes;
					if (!$checkEntitlementExists->save()) {
						throw new Exception(CHtml::errorSummary($checkEntitlementExists, "", "", array(
							'class' => 'customErrors'
						)));
					}
					$staffHolidaysEntitlementEvents = new StaffHolidaysEntitlementEvents();
					$staffHolidaysEntitlementEvents->holiday_id = $checkEntitlementExists->id;
					$staffHolidaysEntitlementEvents->start_date = $checkEntitlementExists->start_date;
					$staffHolidaysEntitlementEvents->finish_date = $checkEntitlementExists->finish_date;
					$staffHolidaysEntitlementEvents->entitlement = $checkEntitlementExists->holiday_entitlement_per_year;
					$staffHolidaysEntitlementEvents->contract_hours = $checkEntitlementExists->contract_hours_per_week;
					$staffHolidaysEntitlementEvents->branch_id = $model->branch_id;
					$staffHolidaysEntitlementEvents->no_of_days = $checkEntitlementExists->days_per_week;
					if (!$staffHolidaysEntitlementEvents->save()) {
						throw new Exception(CHtml::errorSummary($staffHolidaysEntitlementEvents, "", "", array(
							'class' => 'customErrors'
						)));
					}
				} else {
					if (!$holidayEntitlementModel->save()) {
						throw new Exception(CHtml::errorSummary($holidayEntitlementModel, "", "", array(
							'class' => 'customErrors'
						)));
					}
					$staffHolidaysEntitlementEvents = new StaffHolidaysEntitlementEvents();
					$staffHolidaysEntitlementEvents->holiday_id = $holidayEntitlementModel->id;
					$staffHolidaysEntitlementEvents->start_date = $holidayEntitlementModel->start_date;
					$staffHolidaysEntitlementEvents->finish_date = $holidayEntitlementModel->finish_date;
					$staffHolidaysEntitlementEvents->entitlement = $entitlement;
					$staffHolidaysEntitlementEvents->contract_hours = $holidayEntitlementModel->contract_hours_per_week;
					$staffHolidaysEntitlementEvents->branch_id = $model->branch_id;
					$staffHolidaysEntitlementEvents->no_of_days = $holidayEntitlementModel->days_per_week;
					if (!$staffHolidaysEntitlementEvents->save()) {
						throw new Exception(CHtml::errorSummary($staffHolidaysEntitlementEvents, "", "", array(
							'class' => 'customErrors'
						)));
					}
				}
			}
		}
	}
    
    
	public function getEntitlement($year, $start_date, $finish_date, $contract_hours_per_week, $holiday_contract) {
		$entitlement = 0;
		$daysInYear = (date("L", strtotime($year . "-01-01")) == 1) ? 366 : 365;
		if (date("m-d", strtotime($finish_date)) == "12-31" && date("m-d", strtotime($start_date)) == "01-01") {
			$staffDays = $daysInYear;
		} else {
			$daysInBetween = customFunctions::getDatesOfDays(date("Y-m-d", strtotime($start_date)), date("Y-m-d", strtotime($finish_date)), array(
					1,
					2,
					3,
					4,
					5,
					6,
					0
			));
			$daysInBetween = count($daysInBetween);
			$staffDays = $daysInBetween;
		}
		$entitlement = ($staffDays / $daysInYear) * $holiday_contract * ($contract_hours_per_week / 5);
		$entitlement = number_format($entitlement, 2, '.', '');
		$entitlement = ceil($entitlement / 0.25) * 0.25;
		return $entitlement;
	}

	public function getUsed($id) {
		$totalUsed = 0;
		$model = self::model()->findByPk($id);
		$criteria = new CDbCriteria();
		$criteria->condition = "((start_date >= :start_date and start_date <= :return_date) OR " . "(return_date >= :start_date and return_date <= :return_date) OR" . "(start_date <= :start_date and return_date >= :return_date)) AND  staff_id = :staff_id AND is_unpaid = 0";
		$criteria->params = array(
			':start_date' => $model->year . "-01-01",
			':return_date' => $model->year . "-12-31",
			':staff_id' => $model->staff_id
		);
		$staffHolidayModel = StaffHolidays::model()->findAll($criteria);
		if (!empty($staffHolidayModel)) {
			foreach ($staffHolidayModel as $holiday) {
				$totalUsed += $holiday->holiday_hours;
			}
		}
		return $totalUsed;
	}

	public function getBalance($id) {
		return $this->holiday_entitlement_per_year;
	}

	public function getTotalEntitlement($id) {
		$totalEntitlement = 0;
		$model = StaffHolidaysEntitlement::model()->findByPk($id);
		if (!empty($model)) {
			$staffHolidaysEntitlementModel = StaffHolidaysEntitlement::model()->findAllByAttributes(array(
				'staff_id' => $model->staff_id,
				'year' => $model->year
			));
			if (!empty($staffHolidaysEntitlementModel)) {
				foreach ($staffHolidaysEntitlementModel as $entitlement) {
					$totalEntitlement += $entitlement->holiday_entitlement_per_year;
				}
			}
		}
		return customFunctions::round($entitlement, 2);
	}

	public function checkEffectiveDate($attributes, $params) {
		if (isset($this->effective_date) && !empty(trim($this->effective_date))) {
			$model = StaffHolidaysEntitlementEvents::model()->find(array(
				'condition' => '(start_date = :effective_date OR finish_date = :effective_date) AND holiday_id = :holiday_id',
				'params' => array(
					':effective_date' => date("Y-m-d", strtotime($this->effective_date)),
					':holiday_id' => $this->id
				)
			));
			if (!empty($model)) {
				$this->addError('effective_date', 'Effective Date should be between Start and Finish Date of previous entitlements.');
			}

			if ((strtotime(date("Y-m-d", strtotime($this->effective_date))) < strtotime(date("Y-m-d", strtotime($this->start_date)))) || (strtotime(date("Y-m-d", strtotime($this->effective_date))) > strtotime(date("Y-m-d", strtotime($this->finish_date))))) {
				$this->addError('effective_date', 'The effective date should be within the current entitlement year.');
			}
		}
	}

	public function afterFind() {
		$holidayEntitlementEvents = StaffHolidaysEntitlementEvents::model()->find(array('condition' => ':date BETWEEN start_date and finish_date AND holiday_id = :holiday_id', 'params' => array(':date' => date("Y-m-d"), ':holiday_id' => $this->id)));
		if (!empty($holidayEntitlementEvents)) {
			$this->days_per_week = customFunctions::round($holidayEntitlementEvents->no_of_days, 2);
		}
		return parent::afterFind();
	}

	public function getEntitlementStartDate() {
		if (isset($this->staff->branch->hrSettings) && !empty($this->staff->branch->hrSettings)) {
			if ($this->staff->branch->hrSettings->holiday_year == HrSetting::JANUARY_DECEMBER) {
				$staffStartDate = date("Y-m-d", strtotime($this->staff->start_date));
				if (empty($this->staff->start_date)) {
					$staffStartDate = $this->year . "-01-01";
				} else {
					if (strtotime(date("Y-m-d", strtotime($staffStartDate))) < strtotime(date("Y-m-d", strtotime($this->year . "-01-01")))) {
						$staffStartDate = $this->year . "-01-01";
					}
				}
				return $staffStartDate;
			} else if ($this->staff->branch->hrSettings->holiday_year == HrSetting::SEPTEMBER_AUGUST) {
				$staffStartDate = date("Y-m-d", strtotime($this->staff->start_date));
				if (empty($this->staff->start_date)) {
					$staffStartDate = date("Y") . "-09-01";
				} else {
					if (strtotime(date("Y-m-d", strtotime($staffStartDate))) < strtotime(date("Y-m-d", strtotime($this->year . "-09-01")))) {
						$staffStartDate = $this->year . "-09-01";
					}
				}
				return $staffStartDate;
			} else if ($this->staff->branch->hrSettings->holiday_year == HrSetting::APRIL_MARCH) {
				$staffStartDate = date("Y-m-d", strtotime($this->staff->start_date));
				if (empty($this->staff->start_date)) {
					$staffStartDate = date("Y") . "-04-01";
				} else {
					if (strtotime(date("Y-m-d", strtotime($staffStartDate))) < strtotime(date("Y-m-d", strtotime($this->year . "-04-01")))) {
						$staffStartDate = $this->year . "-04-01";
					}
				}
				return $staffStartDate;
			}
		} else {
			$staffStartDate = date("Y-m-d", strtotime($this->staff->start_date));
			if (empty($this->staff->start_date)) {
				$staffStartDate = $this->year . "-01-01";
			} else {
				if (strtotime(date("Y-m-d", strtotime($staffStartDate))) < strtotime(date("Y-m-d", strtotime($this->year . "-01-01")))) {
					$staffStartDate = $this->year . "-01-01";
				}
			}
			return $staffStartDate;
		}
	}

	public function getEntitlementFinishDate() {
		if (isset($this->staff->branch->hrSettings) && !empty($this->staff->branch->hrSettings)) {
			if ($this->staff->branch->hrSettings->holiday_year == HrSetting::JANUARY_DECEMBER) {
				$staffLeaveDate = date("Y-m-d", strtotime($this->staff->leave_date));
				if (empty($this->staff->leave_date)) {
					$staffLeaveDate = $this->year . "-12-31";
				} else {
					if (strtotime(date("Y-m-d", strtotime($staffLeaveDate))) > strtotime(date("Y-m-d", strtotime(($this->year + 1) . "-12-31")))) {
						$staffLeaveDate = $this->year . "-12-31";
					}
				}
				return $staffLeaveDate;
			} else if ($this->staff->branch->hrSettings->holiday_year == HrSetting::SEPTEMBER_AUGUST) {
				$staffLeaveDate = date("Y-m-d", strtotime($this->staff->leave_date));
				if (empty($this->staff->leave_date)) {
					$staffLeaveDate = ($this->year + 1) . "-08-31";
				} else {
					if (strtotime(date("Y-m-d", strtotime($staffLeaveDate))) > strtotime(date("Y-m-d", strtotime(($this->year + 1) . "-08-31")))) {
						$staffLeaveDate = ($this->year + 1) . "-08-31";
					}
				}
				return $staffLeaveDate;
			} else if ($this->staff->branch->hrSettings->holiday_year == HrSetting::APRIL_MARCH) {
				$staffLeaveDate = date("Y-m-d", strtotime($this->staff->leave_date));
				if (empty($this->staff->leave_date)) {
					$staffLeaveDate = ($this->year + 1) . "-03-31";
				} else {
					if (strtotime(date("Y-m-d", strtotime($staffLeaveDate))) > strtotime(date("Y-m-d", strtotime(($this->year + 1) . "-03-31")))) {
						$staffLeaveDate = ($this->year + 1) . "-03-31";
					}
				}
				return $staffLeaveDate;
			}
		} else {
			$staffLeaveDate = date("Y-m-d", strtotime($this->staff->leave_date));
			if (empty($this->staff->leave_date)) {
				$staffLeaveDate = $this->year . "-12-31";
			} else {
				if (strtotime(date("Y-m-d", strtotime($staffLeaveDate))) > strtotime(date("Y-m-d", strtotime(($this->year + 1) . "-12-31")))) {
					$staffLeaveDate = $this->year . "-12-31";
				}
			}
			return $staffLeaveDate;
		}
	}
    
    public static function getYear(){
      $yearArray = array();
      for($i=2017 ; $i < 2030 ; $i++){
        $yearArray[$i] = $i;
      }
      return $yearArray;
    }

}
