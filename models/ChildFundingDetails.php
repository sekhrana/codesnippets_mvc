<?php

/**
 * This is the model class for table "tbl_child_funding_details".
 *
 * The followings are the available columns in table 'tbl_child_funding_details':
 * @property integer $id
 * @property integer $branch_id
 * @property integer $child_id
 * @property double $funded_hours
 * @property integer $pdf
 * @property integer $sf
 * @property integer $is_deleted
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 * @property double $funded_hours_weekly
 * @property integer $term_id
 * @property double $week_count
 * @property integer $type_of_entitlement
 * The followings are the available model relations:
 * @property ChildPersonalDetails $child
 * @property Terms $term
 * @property double $monthly_funded_hours
 * @property double $monthly_funding_rate
 *
 */
class ChildFundingDetails extends CActiveRecord {

	public $year;

	const HOURS_PER_WEEK = 0;
	const HOURS_PER_TERM = 1;

	public $used;
	public $balance;

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'tbl_child_funding_details';
	}

	public function defaultScope() {
		if (get_class(Yii::app()) === "CWebApplication") {
			return array(
				'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0",
			);
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
			array('child_id, branch_id, funded_hours, funded_hours_weekly, term_id,type_of_entitlement', 'required'),
			array('child_id, branch_id, pdf, sf, is_deleted, term_id,type_of_entitlement, created_by, updated_by, term_id', 'numerical', 'integerOnly' => true),
			array('term_id', 'customValidation'),
			array('week_count', 'numerical'),
			array('updated, created', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, child_id,branch_id, year,funded_hours, pdf,type_of_entitlement, sf, is_deleted, funded_hours_weekly, term_id, updated, created, created_by, updated_by, term_id, week_count, monthly_funded_hours, monthly_funding_rate', 'safe', 'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'term' => array(self::BELONGS_TO, 'Terms', 'term_id'),
			'child' => array(self::BELONGS_TO, 'ChildPersonalDetails', 'child_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'child_id' => 'Child',
			'branch_id' => 'Branch',
			'funded_hours' => 'Entitlement (No. of Hours/Term)',
			'pdf' => 'Parent Declaration Form',
			'sf' => 'Stretched Funding',
			'year' => 'Year',
			'is_deleted' => 'Is Deleted',
			'term_id' => 'Term',
			'funded_hours_weekly' => 'Entitlement (No. of Hours/Week)',
			'type_of_entitlement' => 'Type Of Entitlement',
			'created' => 'Created',
			'updated' => 'Updated',
			'created_by' => 'Created By',
			'updated_by' => 'Updated By',
			'week_count' => 'Week Count',
			'monthly_funded_hours' => 'Monthly Funded Hours',
			'monthly_funding_rate' => 'Monthly Funding Rate',
		);
	}

	public function search() {
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria = new CDbCriteria;
		$criteria->compare('child_id', Yii::app()->request->getParam('child_id'));
		$criteria->compare('branch_id', $this->branch_id);
		$criteria->compare('funded_hours', $this->funded_hours);
		$criteria->compare('pdf', $this->pdf);
		$criteria->compare('sf', $this->sf);
		$criteria->compare('is_deleted', $this->is_deleted);
		$criteria->compare('funded_hours_weekly', $this->funded_hours_weekly);
		$criteria->compare('term_id', $this->term_id);
		$criteria->compare('year', $this->year);
		$criteria->compare('type_of_entitlement', $this->type_of_entitlement);
		$criteria->compare('created', $this->created, true);
		$criteria->compare('updated', $this->updated, true);
		$criteria->compare('created_by', $this->created_by);
		$criteria->compare('updated_by', $this->updated_by);
		$criteria->compare('week_count', $this->week_count);
		$criteria->compare('monthly_funded_hours', $this->monthly_funded_hours);
		$criteria->compare('monthly_funding_rate', $this->monthly_funding_rate);
		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return ChildFundingDetails the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public function getColumnNames() {
		$unset_columns = array('id', 'is_deleted', 'funded_hours_weekly');
		$attributes = $this->getAttributes();
		return array_diff(array_keys($attributes), $unset_columns);
	}

	public function getFilter($column_name) {
		$response = array();
		if ($column_name == "pdf" || $column_name == "sf") {
			$response[$column_name] = array("filter_condition" => array('=' => 'EQUAL', '!=' => "NOT EQUAL"), "filter_value" => array(0 => 0, 1 => 1));
		} else {
			$response[$column_name] = array("filter_condition" => array('LIKE' => 'LIKE', 'LIKE %--%' => "LIKE %--%", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL', 'EMPTY' => 'EMPTY'), "filter_value" => array());
		}
		return $response;
	}

	public function getColumnValue($column_name, $column_value) {
		if ($column_name == "pdf" || $column_name == "sf") {
			$column_value = ($column_value == 1) ? "Yes" : "No";
		} else {
			$column_value = $column_value;
		}
		return $column_value;
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

	public function customValidation($attributes, $params) {
		if (isset($this->child_id) && ($this->child->is_term_time == 1) && ($this->sf == 1)) {
			$this->addError('sf', 'Stretched Funding can not be selected for term time only child');
		}

		if (isset($this->term_id)) {
			if ($this->term->term_type == Terms::PARTIAL_WEEK_TERM) {
				if ($this->type_of_entitlement == ChildFundingDetails::HOURS_PER_TERM) {
					$this->addError('funded_hours', ' Entitlement (No. of Hours/term) is only allowed for complete week terms.');
				}
			}

			if ($this->isNewRecord) {
				$fundingModel = ChildFundingDetails::model()->findAllByAttributes(['child_id' => $this->child_id, 'term_id' => $this->term_id]);
				if (!empty($fundingModel)) {
					$this->addError('term_id', 'Funding has been already allocated to this term');
				}
			} else {
				$fundingModel = ChildFundingDetails::model()->findAll([
					'condition' => 'child_id = :child_id AND term_id = :term_id AND id != :id',
					'params' => [':child_id' => $this->child_id, ':term_id' => $this->term_id, ':id' => $this->id]]);
				if (!empty($fundingModel)) {
					$this->addError('term_id', 'Funding has been already allocated to this term');
				}

				$thisTermId = ChildFundingDetails::model()->findByPk($this->id)->term_id;
				if ($thisTermId != $this->term_id) {
					$this->addError('term_id', 'Term change is not allowed while update.');
				}
			}
		}

		if (isset($this->funded_hours) && isset($this->funded_hours_weekly)) {
			$branchFundedHours = Branch::model()->findByPk($this->branch_id)->maximum_funding_hours_week;
			$allowed_funded_hours_weekly = customFunctions::roundToPointFive($branchFundedHours);
			if ($this->isNewRecord) {
				$allowed_funded_hours_term = customFunctions::roundToPointFive($this->week_count * $allowed_funded_hours_weekly);
			} else {
				$weekCount = ChildFundingDetails::model()->findByPk($this->id)->week_count;
				$allowed_funded_hours_term = customFunctions::roundToPointFive($weekCount * $allowed_funded_hours_weekly);
			}
			if ($this->funded_hours > $allowed_funded_hours_term) {
				$this->addError('funded_hours', 'Maximum funded hours allowed in this term are - ' . $allowed_funded_hours_term);
			}
			if ($this->funded_hours_weekly > $allowed_funded_hours_weekly) {
				$this->addError('funded_hours_weekly', 'Maximum funded hours allowed weekly are - ' . $allowed_funded_hours_weekly);
			}
		}

		if (isset($this->funded_hours)) {
			if ($this->term->branch->is_round_off_entitlement == 1) {
				$funded_hours = sprintf("%0.2f", $this->funded_hours);
				$funded_hours = explode(".", $funded_hours)[1];
				if (($funded_hours !== '00') && ($funded_hours !== '50')) {
					$this->addError('funded_hours', 'Funded hours should be rounded off to lower decimal.' . $funded_hours);
				}
			} else {
				$funded_hours = $this->funded_hours;
				$funded_hours = explode(".", $funded_hours)[1];
				if (strlen($funded_hours) > 2) {
					$this->addError('funded_hours', 'Funded hours should be rounded off to lower decimal.');
				}
			}
		}

		if (isset($this->funded_hours_weekly)) {
			if ($this->term->branch->is_round_off_entitlement == 1) {
				$funded_hours = sprintf("%0.2f", $this->funded_hours_weekly);
				$funded_hours = explode(".", $funded_hours)[1];
				if (($funded_hours !== '00') && ($funded_hours !== '50')) {
					$this->addError('funded_hours_weekly', 'Funded hours should be rounded off to lower decimal.');
				}
			} else {
				$funded_hours = $this->funded_hours_weekly;
				$funded_hours = explode(".", $funded_hours)[1];
				if (strlen($funded_hours) > 2) {
					$this->addError('funded_hours_weekly', 'Funded hours should be rounded off to lower decimal.');
				}
			}
		}
	}

	public static function updateFundingHoursForMonthlyInvoice($funding_id) {
		$childFundingTransactions = ChildFundingTransactions::model()->findAllByAttributes(['funding_id' => $funding_id]);
		if(!empty($childFundingTransactions)){
			foreach($childFundingTransactions as $transaction) {
				$transaction->funded_hours_used = $transaction->funded_hours_avaliable;
				if(!$transaction->save()){
					throw new Exception("Their seems some problem updating funded hours monthly.");
				}
			}
		}
	}

	public static function updateFundingInvoiceIdForMonthlyInvoice($funding_id, $invoice_id, $month){
		$childFundingTransactions = ChildFundingTransactions::model()->findAllByAttributes(['funding_id' => $funding_id]);
		if(!empty($childFundingTransactions)){
			foreach($childFundingTransactions as $transaction){
				if(( date("m", strtotime($transaction->week_start_date)) == $month ||   date("m", strtotime($transaction->week_finish_date)) == $month )){
					if($transaction->invoice_id == NULL){
						$transaction->invoice_id = $invoice_id;
						if(!$transaction->save()){
							throw new Exception("Their seems some problem updating invoice id monthly.");
						}
					}
					if($transaction->invoice_id != NULL && $transaction->invoice_id != $invoice_id){
						$childFundingTransaction = new ChildFundingTransactions;
						$childFundingTransaction->attributes = $transaction->attributes;
						$childFundingTransaction->invoice_id = $invoice_id;
						$childFundingTransaction->funded_hours_used = 0;
						if(!$childFundingTransaction->save()){
							throw new Exception("Their seems some problem updating invoice id monthly.");
						}
					}
				}
			}
		}
	}

	/*
	 * Function to calculate the entitlement based on the term
	 */
	public static function calculateEntitlement($term_id, $sf, $entitlement,  $calculation_type) {
			$termModel = Terms::model()->findByPk($term_id);
			$weekDays = ($termModel->branch->is_funding_applicable_on_weekend == 1) ? array(0, 1, 2, 3, 4, 5, 6) : array(1, 2, 3, 4, 5);
			$branchHolidays = customFunctions::getBranchFundingNotApplicableHolidays(date("Y-m-d", strtotime($termModel->start_date)), date('Y-m-d', strtotime($termModel->finish_date)));
			$week_in_term = customFunctions::getWeekBetweenDate($termModel->start_date, $termModel->finish_date);
			$holidayDays = array();
			if (!empty($termModel)) {
				if ($sf == 0) {
					if (isset($termModel->holiday_start_date_1) && isset($termModel->holiday_finish_date_1)) {
						$holiday1Days = customFunctions::getDatesOfDays($termModel->holiday_start_date_1, $termModel->holiday_finish_date_1, $weekDays);
						foreach ($holiday1Days as $holiday1) {
							array_push($holidayDays, $holiday1);
						}
					}
				}
				if ($sf == 0) {
					if (isset($termModel->holiday_start_date_2) && isset($termModel->holiday_finish_date_2)) {
						$holiday2Days = customFunctions::getDatesOfDays($termModel->holiday_start_date_2, $termModel->holiday_finish_date_2, $weekDays);
						foreach ($holiday2Days as $holiday2) {
							array_push($holidayDays, $holiday2);
						}
					}
				}
				if ($sf == 0) {
					if (isset($termModel->holiday_start_date_3) && isset($termModel->holiday_finish_date_3)) {
						$holiday3Days = customFunctions::getDatesOfDays($termModel->holiday_start_date_3, $termModel->holiday_finish_date_3, $weekDays);
						foreach ($holiday3Days as $holiday3) {
							array_push($holidayDays, $holiday3);
						}
					}
				}
				sort($holidayDays);
				$week_to_reduce = 0;
				if ($sf == 0) {
					foreach ($week_in_term as $week) {
						if ($termModel->term_type == Terms::COMPLETE_WEEK_TERM) {
							$currentWeekDaysInTermCount = array();
							$daysThisWeek = customFunctions::getDatesOfDays($week['week_start_date'], $week['week_end_date'], $weekDays);
							$daysThisWeek = array_diff($daysThisWeek, $branchHolidays);
							$currentWeekDaysInTermCount = count(array_diff($daysThisWeek, $holidayDays));
							if ($currentWeekDaysInTermCount == 0) {
								$week_to_reduce += 1;
							}
						} else {
							$currentWeekDaysInTermCount = array();
							$daysThisWeek = customFunctions::getDatesOfDays($week['actual_week_start_date'], $week['actual_week_end_date'], $weekDays);
							$daysThisWeek = array_diff($daysThisWeek, $branchHolidays);
							$currentWeekDaysInTermCount = count(array_diff($daysThisWeek, $holidayDays));
							if ($currentWeekDaysInTermCount == 0) {
								$week_to_reduce += 1;
							} else {
								$week_to_reduce += (count($weekDays) - $currentWeekDaysInTermCount) / count($weekDays);
							}
						}
					}
				}
				if ($sf == 1) {
					foreach ($week_in_term as $week) {
						if ($termModel->term_type == Terms::COMPLETE_WEEK_TERM) {
							$currentWeekDaysInTermCount = array();
							$daysThisWeek = customFunctions::getDatesOfDays($week['week_start_date'], $week['week_end_date'], $weekDays);
							$week_to_reduce = 0;
						} else {
							$currentWeekDaysInTermCount = array();
							$daysThisWeek = customFunctions::getDatesOfDays($week['actual_week_start_date'], $week['actual_week_end_date'], $weekDays);
							$week_to_reduce += (count($weekDays) - count($daysThisWeek)) / count($weekDays);
						}
					}
				}
				$week_count = count($week_in_term) - $week_to_reduce;
				if ($calculation_type == 0) {
					$total_entitlement = ($termModel->branch->is_round_off_entitlement == 1) ? customFunctions::roundToPointFive($entitlement / $week_count) : sprintf("%0.2f", $entitlement / $week_count);
					return ['week_count' => $week_count, 'entitlement' => $entitlement, 'calculated_entitlement' => sprintf("%0.2f", $entitlement / $week_count)];
				} else if ($calculation_type == 1) {
					$total_entitlement = ($termModel->branch->is_round_off_entitlement == 1) ? customFunctions::roundToPointFive($entitlement * $week_count) : sprintf("%0.2f", $entitlement * $week_count);
					return ['week_count' => $week_count, 'entitlement' => $total_entitlement, 'calculated_entitlement' => sprintf("%0.2f", $entitlement * $week_count) ];
				} else {
					return false;
				}
			} else {
				return false;
			}
	}

	/*
	 * Function to create weekly entitlement in funding transaction table.
	 */
	public static function createFundingTransactionsWeekly($termModel, $model) {
		$weekDays = ($termModel->branch->is_funding_applicable_on_weekend == 1) ? array(0, 1, 2, 3, 4, 5, 6) : array(1, 2, 3, 4, 5);
		$branchHolidays = customFunctions::getBranchFundingNotApplicableHolidays(date("Y-m-d", strtotime($termModel->start_date)), date('Y-m-d', strtotime($termModel->finish_date)));
		$week_in_term = customFunctions::getWeekBetweenDate($termModel->start_date, $termModel->finish_date);
		$holidayDays = array();
		if (!empty($termModel)) {
			if ($model->sf == 0) {
				if (isset($termModel->holiday_start_date_1) && isset($termModel->holiday_finish_date_1)) {
					$holiday1Days = customFunctions::getDatesOfDays($termModel->holiday_start_date_1, $termModel->holiday_finish_date_1, $weekDays);
					foreach ($holiday1Days as $holiday1) {
						array_push($holidayDays, $holiday1);
					}
				}
			}
			if ($model->sf == 0) {
				if (isset($termModel->holiday_start_date_2) && isset($termModel->holiday_finish_date_2)) {
					$holiday2Days = customFunctions::getDatesOfDays($termModel->holiday_start_date_2, $termModel->holiday_finish_date_2, $weekDays);
					foreach ($holiday2Days as $holiday2) {
						array_push($holidayDays, $holiday2);
					}
				}
			}
			if ($model->sf == 0) {
				if (isset($termModel->holiday_start_date_3) && isset($termModel->holiday_finish_date_3)) {
					$holiday3Days = customFunctions::getDatesOfDays($termModel->holiday_start_date_3, $termModel->holiday_finish_date_3, $weekDays);
					foreach ($holiday3Days as $holiday3) {
						array_push($holidayDays, $holiday3);
					}
				}
			}
			sort($holidayDays);
			$week_to_reduce = 0;
			$weekDaysCount = count($weekDays);
			if ($model->sf == 0) {
				foreach ($week_in_term as $week) {
					if ($termModel->term_type == Terms::COMPLETE_WEEK_TERM) {
						$currentWeekDaysInTermCount = array();
						$daysThisWeek = customFunctions::getDatesOfDays($week['week_start_date'], $week['week_end_date'], $weekDays);
						$daysThisWeek = array_diff($daysThisWeek, $branchHolidays);
						$currentWeekDaysInTermCount = count(array_diff($daysThisWeek, $holidayDays));
						$fundingTransactionModel = new ChildFundingTransactions;
						$fundingTransactionModel->branch_id = $model->branch_id;
						$fundingTransactionModel->funding_id = $model->id;
						$fundingTransactionModel->child_id = $model->child_id;
						$fundingTransactionModel->invoice_id = NULL;
						$fundingTransactionModel->week_start_date = $week['week_start_date'];
						$fundingTransactionModel->week_finish_date = $week['week_end_date'];
						$fundingTransactionModel->funded_hours_avaliable = $model->funded_hours_weekly;
						$fundingTransactionModel->funded_hours_used = NULL;
						$fundingRateArray = customFunctions::getFundingType(ChildPersonalDetails::model()->findByPk($model->child_id)->dob, $termModel->id);
						$fundingTransactionModel->$fundingRateArray[0] = $fundingRateArray[1];
						if ($currentWeekDaysInTermCount == 0) {
							$fundingTransactionModel->type_of_week = ChildFundingTransactions::HOLIDAY_WEEK;
							$fundingTransactionModel->funded_hours_avaliable = 0;
						} else if ($currentWeekDaysInTermCount > 0 && $currentWeekDaysInTermCount < $weekDaysCount && $weekDaysCount != $currentWeekDaysInTermCount) {
							$fundingTransactionModel->type_of_week = ChildFundingTransactions::PARTIAL_HOLIDAY_WEEK;
						} else {
							$fundingTransactionModel->type_of_week = ChildFundingTransactions::TERM_TIME_WEEK;
						}
						if (!$fundingTransactionModel->save()) {
							throw new Exception("Their seems to be some problem creating the funding.");
						}
					} else {
						$currentWeekDaysInTermCount = array();
						$daysThisWeek = customFunctions::getDatesOfDays($week['actual_week_start_date'], $week['actual_week_end_date'], $weekDays);
						$daysThisWeek = array_diff($daysThisWeek, $branchHolidays);
						$currentWeekDaysInTermCount = count(array_diff($daysThisWeek, $holidayDays));
						$fundingTransactionModel = new ChildFundingTransactions;
						$fundingTransactionModel->branch_id = $model->branch_id;
						$fundingTransactionModel->funding_id = $model->id;
						$fundingTransactionModel->child_id = $model->child_id;
						$fundingTransactionModel->invoice_id = NULL;
						$fundingTransactionModel->week_start_date = $week['week_start_date'];
						$fundingTransactionModel->week_finish_date = $week['week_end_date'];
						$fundingTransactionModel->funded_hours_avaliable = ($currentWeekDaysInTermCount * $model->funded_hours_weekly) / $weekDaysCount;
						$fundingTransactionModel->funded_hours_used = NULL;
						$fundingRateArray = customFunctions::getFundingType(ChildPersonalDetails::model()->findByPk($model->child_id)->dob, $termModel->id);
						$fundingTransactionModel->$fundingRateArray[0] = $fundingRateArray[1];
						if ($currentWeekDaysInTermCount == 0) {
							$fundingTransactionModel->type_of_week = ChildFundingTransactions::HOLIDAY_WEEK;
							$fundingTransactionModel->funded_hours_avaliable = 0;
						} else if ($currentWeekDaysInTermCount > 0 && $currentWeekDaysInTermCount < $weekDaysCount && $weekDaysCount != $currentWeekDaysInTermCount) {
							$fundingTransactionModel->type_of_week = ChildFundingTransactions::PARTIAL_HOLIDAY_WEEK;
						} else {
							$fundingTransactionModel->type_of_week = ChildFundingTransactions::TERM_TIME_WEEK;
						}
						if (!$fundingTransactionModel->save()) {
							throw new Exception("Their seems to be some problem creating the funding.");
						}
					}
				}
			} else {
				foreach ($week_in_term as $week) {
					if ($termModel->term_type == Terms::COMPLETE_WEEK_TERM) {
						$daysThisWeek = customFunctions::getDatesOfDays($week['week_start_date'], $week['week_end_date'], $weekDays);
						$fundingTransactionModel = new ChildFundingTransactions;
						$fundingTransactionModel->branch_id = $model->branch_id;
						$fundingTransactionModel->funding_id = $model->id;
						$fundingTransactionModel->child_id = $model->child_id;
						$fundingTransactionModel->invoice_id = NULL;
						$fundingTransactionModel->week_start_date = $week['week_start_date'];
						$fundingTransactionModel->week_finish_date = $week['week_end_date'];
						$fundingTransactionModel->funded_hours_avaliable = $model->funded_hours_weekly;
						$fundingTransactionModel->funded_hours_used = NULL;
						$fundingTransactionModel->type_of_week = ChildFundingTransactions::TERM_TIME_WEEK;
						$fundingRateArray = customFunctions::getFundingType(ChildPersonalDetails::model()->findByPk($model->child_id)->dob, $termModel->id);
						$fundingTransactionModel->$fundingRateArray[0] = $fundingRateArray[1];
						if (!$fundingTransactionModel->save()) {
							throw new Exception("Their seems to be some problem creating the funding.");
						}
					} else {
						$daysThisWeek = customFunctions::getDatesOfDays($week['actual_week_start_date'], $week['actual_week_end_date'], $weekDays);
						$fundingTransactionModel = new ChildFundingTransactions;
						$fundingTransactionModel->branch_id = $model->branch_id;
						$fundingTransactionModel->funding_id = $model->id;
						$fundingTransactionModel->child_id = $model->child_id;
						$fundingTransactionModel->invoice_id = NULL;
						$fundingTransactionModel->week_start_date = $week['week_start_date'];
						$fundingTransactionModel->week_finish_date = $week['week_end_date'];
						$fundingTransactionModel->funded_hours_avaliable = (count($daysThisWeek) * $model->funded_hours_weekly) / count($weekDays);
						$fundingTransactionModel->funded_hours_used = NULL;
						$fundingTransactionModel->type_of_week = ChildFundingTransactions::TERM_TIME_WEEK;
						$fundingRateArray = customFunctions::getFundingType(ChildPersonalDetails::model()->findByPk($model->child_id)->dob, $termModel->id);
						$fundingTransactionModel->$fundingRateArray[0] = $fundingRateArray[1];
						if (!$fundingTransactionModel->save()) {
							throw new Exception("Their seems to be some problem creating the funding.");
						}
					}
				}
			}
			$balanceFundedHours = ChildFundingTransactions::model()->getTotalBalanceFundedHours($model->funded_hours, $model->id);
			$lastTransactionModel = ChildFundingTransactions::model()->find([
				'condition' => 'type_of_week IN (0, 2)  AND funding_id = :funding_id',
				'order' => 'id DESC',
				'params' => [':funding_id' => $model->id]
			]);
			if (!empty($lastTransactionModel)) {
				$lastTransactionModel->funded_hours_avaliable = $balanceFundedHours + $lastTransactionModel->funded_hours_avaliable;
				$lastTransactionModel->funded_hours_avaliable = ($lastTransactionModel->funded_hours_avaliable > $termModel->branch->maximum_funding_hours_week) ? $termModel->branch->maximum_funding_hours_week : $lastTransactionModel->funded_hours_avaliable;
				if (!$lastTransactionModel->save()) {
					throw new Exception("Their seems to be some problem creating the funding.");
				}
			}
		} else {
			throw new Exception("Selected term does not exists.");
		}
	}

}
