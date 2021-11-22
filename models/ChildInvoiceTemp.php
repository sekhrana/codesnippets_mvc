<?php

/**
 * This is the model class for table "tbl_child_invoice_temp".
 *
 * The followings are the available columns in table 'tbl_child_invoice_temp':
 * @property integer $id
 * @property string $urn_prefix
 * @property string $urn_number
 * @property string $urn_suffix
 * @property integer $child_id
 * @property integer $branch_id
 * @property integer $invoice_type
 * @property string $description
 * @property string $month
 * @property string $year
 * @property string $invoice_date
 * @property string $from_date
 * @property string $to_date
 * @property string $due_date
 * @property string $status
 * @property string $created
 * @property integer $created_by
 * @property string $updated
 * @property integer $updated_by
 * @property double $total
 * @property string $access_token
 * @property integer $is_email_sent
 * @property integer $is_deposit
 * @property integer $is_regenrated
 * @property integer $is_deleted
 * @property integer $credit_note_invoice_id
 * @property integer $credit_note_payment_id
 * @property integer $is_monthly_invoice
 * @property integer $is_money_received
 * @property integer $payment_mode
 * @property integer $is_locked
 *
 * The followings are the available model relations:
 * @property ChildInvoiceDetailsTemp[] $childInvoiceDetailsTemps
 * @property Branch $branch
 * @property ChildPersonalDetails $child
 */
class ChildInvoiceTemp extends CActiveRecord {

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'tbl_child_invoice_temp';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('urn_number, child_id, branch_id, invoice_date, due_date, access_token', 'required'),
			array('child_id, branch_id, invoice_type, created_by, updated_by, is_email_sent, is_deposit, is_regenrated, is_deleted, credit_note_invoice_id, credit_note_payment_id, is_monthly_invoice, is_money_received, payment_mode, is_locked', 'numerical', 'integerOnly' => true),
			array('total', 'numerical'),
			array('urn_prefix, urn_number, urn_suffix, month, year', 'length', 'max' => 45),
			array('description, access_token', 'length', 'max' => 255),
			array('status', 'length', 'max' => 16),
			array('from_date, to_date, created, updated', 'safe'),
			array('child_id', 'checkUniqueInvoice'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, urn_prefix, urn_number, urn_suffix, child_id, branch_id, invoice_type, description, month, year, invoice_date, from_date, to_date, due_date, status, created, created_by, updated, updated_by, total, access_token, is_email_sent, is_deposit, is_regenrated, is_deleted, credit_note_invoice_id, credit_note_payment_id, is_monthly_invoice, is_money_received, payment_mode, is_locked', 'safe', 'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'childInvoiceDetails' => array(self::HAS_MANY, 'ChildInvoiceDetailsTemp', 'invoice_id'),
			'branch' => array(self::BELONGS_TO, 'Branch', 'branch_id'),
			'child' => array(self::BELONGS_TO, 'ChildPersonalDetails', 'child_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'urn_prefix' => 'Urn Prefix',
			'urn_number' => 'Urn Number',
			'urn_suffix' => 'Urn Suffix',
			'child_id' => 'Child',
			'branch_id' => 'Branch',
			'invoice_type' => 'Invoice Type',
			'description' => 'Description',
			'month' => 'Month',
			'year' => 'Year',
			'invoice_date' => 'Invoice Date',
			'from_date' => 'From Date',
			'to_date' => 'To Date',
			'due_date' => 'Due Date',
			'status' => 'Status',
			'created' => 'Created',
			'created_by' => 'Created By',
			'updated' => 'Updated',
			'updated_by' => 'Updated By',
			'total' => 'Total',
			'access_token' => 'Access Token',
			'is_email_sent' => 'Is Email Sent',
			'is_deposit' => 'Is Deposit',
			'is_regenrated' => 'Is Regenrated',
			'is_deleted' => 'Is Deleted',
			'credit_note_invoice_id' => 'Credit Note Invoice',
			'credit_note_payment_id' => 'Credit Note Payment',
			'is_monthly_invoice' => 'Is Monthly Invoice',
			'is_money_received' => 'Is Money Received',
			'payment_mode' => 'Payment Mode',
			'is_locked' => 'Is Locked',
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
		$criteria->compare('urn_prefix', $this->urn_prefix, true);
		$criteria->compare('urn_number', $this->urn_number, true);
		$criteria->compare('urn_suffix', $this->urn_suffix, true);
		$criteria->compare('child_id', $this->child_id);
		$criteria->compare('branch_id', $this->branch_id);
		$criteria->compare('invoice_type', $this->invoice_type);
		$criteria->compare('description', $this->description, true);
		$criteria->compare('month', $this->month, true);
		$criteria->compare('year', $this->year, true);
		$criteria->compare('invoice_date', $this->invoice_date, true);
		$criteria->compare('from_date', $this->from_date, true);
		$criteria->compare('to_date', $this->to_date, true);
		$criteria->compare('due_date', $this->due_date, true);
		$criteria->compare('status', $this->status, true);
		$criteria->compare('created', $this->created, true);
		$criteria->compare('created_by', $this->created_by);
		$criteria->compare('updated', $this->updated, true);
		$criteria->compare('updated_by', $this->updated_by);
		$criteria->compare('total', $this->total);
		$criteria->compare('access_token', $this->access_token, true);
		$criteria->compare('is_email_sent', $this->is_email_sent);
		$criteria->compare('is_deposit', $this->is_deposit);
		$criteria->compare('is_regenrated', $this->is_regenrated);
		$criteria->compare('is_deleted', $this->is_deleted);
		$criteria->compare('credit_note_invoice_id', $this->credit_note_invoice_id);
		$criteria->compare('credit_note_payment_id', $this->credit_note_payment_id);
		$criteria->compare('is_monthly_invoice', $this->is_monthly_invoice);
		$criteria->compare('is_money_received', $this->is_money_received);
		$criteria->compare('payment_mode', $this->payment_mode);
		$criteria->compare('is_locked', $this->is_locked);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return ChildInvoiceTemp the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public static function createInvoices($month, $year, $return_error = 0) {
		$invoice_date = date("Y-m-d");
		$invoice_due_date = date('Y-m-d');
		$branch_id = Yii::app()->session['branch_id'];
		$invoiceSettingModal = InvoiceSetting::model()->findByAttributes(array('branch_id' => $branch_id));
		if (!empty($invoiceSettingModal)) {
			$invoiceDates = ChildInvoice::model()->setInvoiceDates($invoiceSettingModal, $month, $year);
			$invoice_dates_array = ['invoice_from_date' => $invoiceDates['invoice_from_date'],
				'invoice_to_date' => $invoiceDates['invoice_to_date'], 'invoice_due_date' => $invoice_due_date,
				'invoice_date' => $invoice_date];
			$actualInvoiceFromDate = $invoice_dates_array['invoice_from_date'];
			$actualInvoiceToDate = $invoice_dates_array['invoice_to_date'];
			$childModal = ChildPersonalDetails::model()->findAllByAttributes(array('branch_id' => $branch_id));
			if (!empty($childModal)) {
				$invoiceDueDate = $invoice_dates_array['invoice_due_date'];
				$logging_messages = array();
				foreach ($childModal as $childData) {
					if (ChildInvoiceTemp::model()->findByAttributes(['month' => $month, 'year' => $year, 'invoice_type' => 0, 'child_id' => $childData->id])) {
						continue;
					}
					$transaction = Yii::app()->db->beginTransaction();
					try {
						$invoiceFromDate = $invoice_dates_array['invoice_from_date'];
						$invoiceToDate = $invoice_dates_array['invoice_to_date'];
						//Checking the child start and leave date and accordingly setting the dates of invoice
						if (!empty($childData->start_date) && (strtotime(date("Y-m-d", strtotime($childData->start_date))) > strtotime($invoiceToDate))) {
							throw new Exception("Invoice not generated for child - $childData->name as start date of child is greater than the invoice to date.");
						}
						if (!empty($childData->leave_date) && (strtotime(date("Y-m-d", strtotime($childData->leave_date))) < strtotime($invoiceFromDate))) {
							throw new Exception("Invoice not generated for child - $childData->name as leave date of child is smaller than the invoice from date.");
						}
						if (!empty($childData->start_date) && ((strtotime(date("Y-m-d", strtotime($childData->start_date))) > strtotime($invoiceFromDate)) && (strtotime(date("Y-m-d", strtotime($childData->start_date))) < strtotime($invoiceToDate)))) {
							$invoiceFromDate = date("Y-m-d", strtotime($childData->start_date));
						}
						if (!empty($childData->leave_date) && ((strtotime(date("Y-m-d", strtotime($childData->leave_date))) > strtotime($invoiceFromDate)) && (strtotime(date("Y-m-d", strtotime($childData->leave_date))) < strtotime($invoiceToDate)))) {
							$invoiceToDate = date("Y-m-d", strtotime($childData->leave_date));
						}
						//Checking the child start and leave date ends here.
						$childId = $childData->id;
						$childName = $childData->name;
						$monthlyAmount = 0;
						$isMonthlyInvoice = false;
						$monthlySessionId = false;
						$monthlySessionTime = 0;
//						$monthlySessionModal = SessionRates::model()->findAllByAttributes(array('is_modified' => 0,
//							'multiple_rates_type' => 3));
//						if (!empty($monthlySessionModal)) {
//							$childAge = customFunctions::getAge(date("Y-m-d", strtotime($childData->dob)), date("Y-m-d", strtotime("-1 day", strtotime($invoiceFromDate))));
//							foreach ($monthlySessionModal as $monthlySession) {
//								if ($this->actionCheckMonthlyRateExists($childAge, $monthlySession->id) != FALSE) {
//									$flag = customFunctions::checkMonthlySessionExists($childData->id, $invoiceFromDate, $invoiceToDate, $monthlySession->id);
//									if ($flag) {
//										$monthlySessionTime = customFunctions::getHours($monthlySession->start_time, $monthlySession->finish_time);
//										$monthlySessionId = $monthlySession->id;
//										$isMonthlyInvoice = true;
//										$monthlyAmount = $this->actionCheckMonthlyRateExists($childAge, $monthlySession->id);
//										break;
//									}
//								}
//							}
//						}
						$weekArray = customFunctions::getWeekBetweenDate($invoiceFromDate, $invoiceToDate);
						/** Block for Including last month uninvoiced sessions starts here* */
						if (Branch::currentBranch()->include_last_month_uninvoiced_sessions == 1 && false) {
							if ($month == 1) {
								$lastYear = $year - 1;
								$lastMonth = 12;
							} else {
								$lastYear = $year;
								$lastMonth = ((int) $month - 1);
							}
							$lastMonthDates = ChildInvoice::model()->setInvoiceDates($invoiceSettingModal, $lastMonth, $lastYear);
							$lastMonthWeeks = customFunctions::getWeekBetweenDate($lastMonthDates['invoice_from_date'], $lastMonthDates['invoice_to_date'], 1);
							$weekArray = array_merge($weekArray, $lastMonthWeeks);
							$lastMonthBookingsModel = ChildBookings::model()->getBookings($lastMonthDates['invoice_from_date'], $lastMonthDates['invoice_to_date'], $childData->branch_id, $childData->id, NULL, NULL, NULL, 0);
							ChildBookings::model()->breakSeries($lastMonthDates['invoice_from_date'], $lastMonthDates['invoice_to_date'], $childData->branch_id, $childData->id, $lastMonthBookingsModel);
						}
						/** Block for Including last month uninvoiced sessions end here* */
						if (!customFunctions::checkInvoiceExists($invoiceFromDate, $invoiceToDate, $childId)) {
							$bookingsModel = ChildBookings::model()->getBookings($invoiceFromDate, $invoiceToDate, $childData->branch_id, $childData->id, NULL, NULL, NULL, NULL);
							ChildBookings::model()->breakSeries($invoiceFromDate, $invoiceToDate, $childData->branch_id, $childData->id, $bookingsModel);

							$urn = customFunctions::getInvoiceUrn($branch_id);
							$model = new ChildInvoice;
							$model->child_id = $childId;
							$model->branch_id = $branch_id;
							$model->from_date = $actualInvoiceFromDate;
							$model->to_date = $actualInvoiceToDate;
							$model->urn_prefix = empty($urn['prefix']) ? NULL : $urn['prefix'];
							$model->urn_number = $urn['number'];
							$model->urn_suffix = empty($urn['suffix']) ? NULL : $urn['suffix'];
							$model->status = 'AWAITING_PAYMENT';
							$model->month = $month;
							$model->year = $year;
							$model->due_date = $invoice_dates_array['invoice_due_date'];
							$model->invoice_date = $invoice_dates_array['invoice_date'];
							$model->invoice_type = ChildInvoice::AUTOMATIC_INVOICE;
							$model->total = ($monthlyAmount - (($childData->discount * $monthlyAmount) / 100));
							$model->access_token = md5(time() . uniqid() . $model->id . $model->child_id);
							$model->is_monthly_invoice = ($isMonthlyInvoice == true) ? 1 : 0;
							if ($model->save()) {
								foreach ($bookingsModel as $booking) {
									$booking->invoice_id = $model->id;
									if (!$booking->save()) {
										throw new Exception("Their seems to be some problem updating the invoice id for child - $childData->name");
									}
								}
								if ($isMonthlyInvoice && false) { /** Commented code for monthly invoices * */
									$invoiceDetailsModel = new ChildInvoiceDetails;
									$invoiceDetailsModel->invoice_id = $model->id;
									$invoiceDetailsModel->session_id = $monthlySessionId;
									$invoiceDetailsModel->session_type = 3;
									$invoiceDetailsModel->week_start_date = $model->from_date;
									$invoiceDetailsModel->week_end_date = $model->to_date;
									$invoiceDetailsModel->rate = $monthlyAmount;
									$invoiceDetailsModel->discount = $childData->discount;
									$total_funded_hours = 0;
									$total_session_days = 0;
									$total_session_hours = 0;
									foreach ($weekArray as $week) {
										$week_funded_hours = 0;
										$actualWeekStartDate = $week['week_start_date'];
										$actualWeekEndDate = $week['week_end_date'];
										if ((strtotime($week['week_start_date']) < strtotime($invoiceFromDate)) && $week['is_last_month'] == 0) {
											$isIncompleteWeekStart = true;
											$week['week_start_date'] = $invoiceFromDate;
										}
										if ((strtotime($week['week_end_date']) > strtotime($invoiceToDate)) && $week['is_last_month'] == 0) {
											$isIncompleteWeekEnd = true;
											$week['week_end_date'] = $invoiceToDate;
										}
										$total_booking_days = customFunctions::getDatesOfDays($week['week_start_date'], $week['week_end_date'], explode(",", $model->branch->nursery_operation_days));
										$total_hours = count($total_booking_days) * $monthlySessionTime;
										$week_funded_hours = customFunctions::getFundedHours($model->child_id, $week['week_start_date'], $week['week_end_date'], count(explode(",", $model->branch->nursery_operation_days)), $model->id, true, $total_hours, $total_booking_days, $invoiceDetailsModel->session_id);
										$total_funded_hours += $week_funded_hours;
										$total_session_days += count($total_booking_days);
										$total_session_hours += $total_hours;
									}
									$invoiceDetailsModel->funded_hours = $total_funded_hours;
									$invoiceDetailsModel->total_hours = $total_session_hours;
									$invoiceDetailsModel->total_days = count($total_booking_days);
									$invoiceDetailsModel->funded_rate = customFunctions::getFundedAmount($invoiceDetailsModel, $model->child_id, $actualWeekStartDate, 1);
									$model->total = customFunctions::round(($monthlyAmount - (($childData->discount * $monthlyAmount) / 100) - $invoiceDetailsModel->funded_rate), 2);
									if (!$model->save()) {
										throw new Exception("Seems some problem saving amount for monthly invoice.");
									}
									if (!$invoiceDetailsModel->save()) {
										throw new Exception("Seems some problem saving details for monthly invoice.");
									}
								} else {
									foreach ($weekArray as $week) {
										$actualWeekBookingDaysNew = array();
										$actualWeekBookingDays = 0;
										$isIncompleteWeekStart = false;
										$isIncompleteWeekEnd = false;
										$incompleteSessionDays = "";
										$incompleteBookingDays = false;
										$actualWeekStartDate = $week['week_start_date'];
										$actualWeekEndDate = $week['week_end_date'];
										if ((strtotime($week['week_start_date']) < strtotime($invoiceFromDate)) && $week['is_last_month'] == 0) {
											$isIncompleteWeekStart = true;
											$week['week_start_date'] = $invoiceFromDate;
										}
										if ((strtotime($week['week_end_date']) > strtotime($invoiceToDate)) && $week['is_last_month'] == 0) {
											$isIncompleteWeekEnd = true;
											$week['week_end_date'] = $invoiceToDate;
										}
										if ($week['is_last_month'] == 1 && strtotime($week['week_start_date']) < strtotime($lastMonthDates['invoice_from_date'])) {
											$isIncompleteWeekStart = true;
											$week['week_start_date'] = $lastMonthDates['invoice_from_date'];
										}
										if (strtotime($week['week_end_date']) > strtotime($lastMonthDates['invoice_to_date']) && $week['is_last_month'] == 1) {
											$isIncompleteWeekEnd = true;
											$week['week_end_date'] = $lastMonthDates['invoice_to_date'];
										}
										$sessionData = customFunctions::getChildSessionDataForInvoicing($childId, $week['week_start_date'], $week['week_end_date'], $actualWeekStartDate, $actualWeekEndDate, $childData->branch->funding_allocation_type, NULL, $week['is_last_month']);
										$branchHoliday = customFunctions::getBranchHolidays($week['week_start_date'], $week['week_end_date']);
										if (!empty($sessionData)) {
											if (Branch::currentBranch()->is_exclude_funding == 0) {
												$sessionTotalHours = 0;
												$sessionBooked = array_unique(array_column($sessionData, 'session_type_id'));
											}

											if (Branch::currentBranch()->is_exclude_funding == 1) {
												$excludeFunding = array();
												$sessionTotalHours = 0;
												$sessionBooked = array_unique(array_column($sessionData, 'session_type_id'));
												foreach ($sessionBooked as $val) {
													$excludeFunding[$val] = 0;
												}
												$sessionBooked = array_merge($sessionBooked, $sessionBooked);
											}
											sort($sessionBooked);
											foreach ($sessionBooked as $sessionId) {
												$actualWeekBookingDaysNew = array();
												$actualWeekBookingDays = 0;
												$checkSessionNotEmpty = true;
												$total_hours = 0;
												$total_days = 0;
												$total_booking_days = array();
												$session_data = array();
												$session_room_data = array();
												$total_hours_multiple_rates = 0;
												$invoiceDetailsModel = new ChildInvoiceDetails;
												$invoiceDetailsModel->invoice_id = $model->id;
												$invoiceDetailsModel->week_start_date = $week['week_start_date'];
												$invoiceDetailsModel->week_end_date = $week['week_end_date'];
												if ($isIncompleteWeekStart == TRUE || $isIncompleteWeekEnd == TRUE) {
													$temp = array();
													$weekStartDate = $week['week_start_date'];
													$weekEndDate = $week['week_end_date'];
													while (strtotime($weekStartDate) <= strtotime($weekEndDate)) {
														$temp[] = date('Y-m-d', strtotime($weekStartDate));
														$weekStartDate = date('Y-m-d', strtotime($weekStartDate . "+1 days"));
													}
													$incompleteSessionDays = $temp;
												}
												$current_session_data = array();
												if (Branch::currentBranch()->is_exclude_funding == 0) {
													foreach ($sessionData as $key => $value) {
														if ($value['session_type_id'] == $sessionId) {
															array_push($current_session_data, $sessionData[$key]);
														}
													}
												}

												if (Branch::currentBranch()->is_exclude_funding == 1) {
													foreach ($sessionData as $key => $value) {
														if ($value['session_type_id'] == $sessionId) {
															if ($value['exclude_funding'] == 0) {
																if ($excludeFunding[$sessionId] == 0) {
																	array_push($current_session_data, $sessionData[$key]);
																}
															}
															if ($value['exclude_funding'] == 1) {
																if ($excludeFunding[$sessionId] == 1) {
																	array_push($current_session_data, $sessionData[$key]);
																}
															}
														}
													}
												}
												foreach ($current_session_data as $key => $value) {
													if ($isIncompleteWeekStart == TRUE || $isIncompleteWeekEnd == TRUE) {
														$actualWeekBookingDaysNew = array_merge($actualWeekBookingDaysNew, array_diff($value['sessionWeekDays'], $branchHoliday));
														$bookingDays = array_intersect($incompleteSessionDays, $value['sessionDays']);
														$bookingDays = array_diff($bookingDays, $branchHoliday);
														$temp = abs(strtotime($value['finish_time']) - strtotime($value['start_time'])) / 3600;
														$total_days += count($bookingDays);
														$total_hours += round(($temp * count($bookingDays)), 2);
														$total_hours_multiple_rates += round(($temp * count($value['sessionWeekDays'])), 2);
														foreach ($bookingDays as $day) {
															array_push($total_booking_days, $day);
															$session_data[$day][] = date("H:i", strtotime($value['start_time'])) . "-" . date("H:i", strtotime($value['finish_time']));
															$session_room_data[] = array('room_id' => $value['room_id'], 'total_hours' => round(($temp * count($bookingDays)), 2),
																'total_days' => count($bookingDays), 'data' => $value);
														}
													} else {
														$bookingDays = array_diff($value['sessionDays'], $branchHoliday);
														$total_days += count($bookingDays);
														$actualWeekBookingDaysNew = array_merge($actualWeekBookingDaysNew, array_diff($value['sessionDays'], $branchHoliday));
														$temp = abs(strtotime($value['finish_time']) - strtotime($value['start_time'])) / 3600;
														$total_hours += round(($temp * count($bookingDays)), 2);
														$total_hours_multiple_rates += round(($temp * count($value['sessionDays'])), 2);
														foreach ($bookingDays as $day) {
															array_push($total_booking_days, $day);
															$session_data[$day][] = date("H:i", strtotime($value['start_time'])) . "-" . date("H:i", strtotime($value['finish_time']));
															$session_room_data[] = array('room_id' => $value['room_id'], 'total_hours' => round(($temp * count($bookingDays)), 2),
																'total_days' => count($bookingDays), 'data' => $value);
														}
													}
												}
												$actualWeekBookingDays = count(array_unique($actualWeekBookingDaysNew));
												$bookingDaysForProducts = array_map(function($day) {
													return date('w', strtotime($day));
												}, array_keys($session_data));
												$tempProductArray = array();
												foreach ($current_session_data as $key_current => $value_current) {
													if (!empty($value_current['products_details'])) {
														foreach ($value_current['products_details'] as $productId => $allowedDays) {
															$include_in_invoice = Products::model()->findByPk($productId)->create_invoice;
															if ($include_in_invoice == 1) {
																unset($value_current['products_details'][$productId]);
															}
														}
													}
													$tempProductArray = customFunctions::arrayMergeRecursive($tempProductArray, $value_current['products_details']);
													if (!empty($tempProductArray)) {
														foreach ($tempProductArray as $key => $value) {
															$tempProductArray[$key] = array_values(array_intersect($bookingDaysForProducts, $value));
														}
													}
													$invoiceDetailsModel->products_data = (empty($tempProductArray)) ? NULL : CJSON::encode($tempProductArray);
												}
												if (empty($session_data)) {
													$checkSessionNotEmpty = FALSE;
												}
												$invoiceDetailsModel->session_data = CJSON::encode($session_data);
												$invoiceDetailsModel->session_room_data = CJSON::encode($session_room_data);
												$invoiceDetailsModel->total_hours = $total_hours;
												$invoiceDetailsModel->session_id = $sessionId;
												$invoiceDetailsModel->total_days = count($session_data);

												if (Branch::currentBranch()->is_exclude_funding == 0) {
													$invoiceDetailsModel->funded_hours = customFunctions::getFundedHours($model->child_id, $week['week_start_date'], $week['week_end_date'], $total_days, $model->id, $checkSessionNotEmpty, $total_hours, $total_booking_days, $invoiceDetailsModel->session_id);
												}

												if (Branch::currentBranch()->is_exclude_funding == 1) {
													if ($excludeFunding[$sessionId] == 0) {
														$invoiceDetailsModel->funded_hours = customFunctions::getFundedHours($model->child_id, $week['week_start_date'], $week['week_end_date'], $total_days, $model->id, $checkSessionNotEmpty, $total_hours, $total_booking_days, $invoiceDetailsModel->session_id);
													}
												}
												$sessionRateModel = SessionRates::model()->findByPk($sessionId);
												if ($sessionRateModel->is_multiple_rates == 1) {
													$invoiceDetailsModel->session_type = $sessionRateModel->multiple_rates_type;
												} else {
													if ($sessionRateModel->rate_flat_type == 0) {
														$invoiceDetailsModel->session_type = 4;
													} else {
														$invoiceDetailsModel->session_type = 0;
													}
												}
												if ($childData->branch->change_session_rate == 0) {
													$age = customFunctions::getAge(date("Y-m-d", strtotime($childData->dob)), date("Y-m-d", strtotime("-1 day", strtotime($actualWeekStartDate))));
												} else {
													$age = customFunctions::getAge(date("Y-m-d", strtotime($childData->dob)), date("Y-m-d", strtotime("-1 day", strtotime($model->from_date))));
												}
												if ($sessionRateModel->is_multiple_rates == 1 && $sessionRateModel->multiple_rates_type == 1) {
													$calculated_rate = self::actionGetMultipleRate($age, $sessionId, $total_hours_multiple_rates, $invoiceDetailsModel->funded_hours, $actualWeekStartDate, $actualWeekEndDate);
													if ($calculated_rate == FALSE) {
														$invoiceDetailsModel->rate = 0;
													} else {
														$invoiceDetailsModel->rate = self::actionGetMultipleRate($age, $sessionId, $total_hours_multiple_rates, $invoiceDetailsModel->funded_hours, $actualWeekStartDate, $actualWeekEndDate);
													}
												} else if ($sessionRateModel->is_multiple_rates == 1 && $sessionRateModel->multiple_rates_type == 2) {
													$calculated_rate = self::actionGetMultipleRatesWeekdays($age, $sessionId, $total_booking_days, $actualWeekStartDate, $actualWeekEndDate);
													if ($calculated_rate == FALSE) {
														$invoiceDetailsModel->rate = 0;
													} else {
														$invoiceDetailsModel->rate = $calculated_rate;
													}
												} else if ($sessionRateModel->is_multiple_rates == 1 && $sessionRateModel->multiple_rates_type == 3) {
													$calculated_rate = self::actionGetMultipleRatesTotalWeekdays($age, $sessionId, $actualWeekBookingDays, $actualWeekStartDate, $actualWeekEndDate);
													$invoiceDetailsModel->rate = $calculated_rate;
													if ($calculated_rate == FALSE) {
														$invoiceDetailsModel->rate = 0;
													}
												} else {
													$invoiceDetailsModel->rate = customFunctions::getRateForInvoicing($childId, $sessionId, $total_hours, $actualWeekStartDate, $actualWeekEndDate);
												}
												$invoiceDetailsModel->discount = $childData->discount;
												if (Branch::currentBranch()->is_exclude_funding == 0) {
													$invoiceDetailsModel->funded_rate = customFunctions::getFundedAmount($invoiceDetailsModel, $childData, $actualWeekStartDate);
													$invoiceDetailsModel->average_rate = customFunctions::getAverageRate($invoiceDetailsModel, $childData, $actualWeekStartDate);
												}

												if (Branch::currentBranch()->is_exclude_funding == 1) {
													if ($excludeFunding[$sessionId] == 1) {
														$invoiceDetailsModel->exclude_funding = 1;
													}
													$invoiceDetailsModel->funded_rate = customFunctions::getFundedAmount($invoiceDetailsModel, $childData, $actualWeekStartDate);
													$invoiceDetailsModel->average_rate = customFunctions::getAverageRate($invoiceDetailsModel, $childData, $actualWeekStartDate);
												}

												if ($checkSessionNotEmpty) {
													if (!$invoiceDetailsModel->save())
														throw new Exception("Some problem occured saving invoice details for child - " . $childId);
												}
												$sessionTotalHours += $total_hours_multiple_rates;

												if (Branch::currentBranch()->is_exclude_funding == 1) {
													$excludeFunding[$sessionId] = 1;
												}
											}
										} else {
											$sessionTotalHours = 0;
										}
										if ($childData->branch->is_minimum_booking_rate_enabled == 1 && $week['is_last_month'] == 0) {
											/** Block for minimum booking fees starts here* */
											$invoiceDetailsModel = new ChildInvoiceDetails;
											$sessionRatesMappingModel = new SessionRateMapping;
											if ($childData->branch->change_session_rate == 0) {
												$age = customFunctions::getAge(date("Y-m-d", strtotime($childData->dob)), date("Y-m-d", strtotime("-1 day", strtotime($actualWeekStartDate))));
											} else {
												$childAge = customFunctions::getAge(date("Y-m-d", strtotime($childData->dob)), date("Y-m-d", strtotime("-1 day", strtotime($model->from_date))));
											}
											$childAge = round(abs(strtotime(date("Y-m-d", strtotime("-1 day", strtotime($actualWeekStartDate)))) - strtotime(date("Y-m-d", strtotime($childData->dob)))) / (365 * 60 * 60 * 24), 2);
											$invoiceDetailsArray = $sessionRatesMappingModel->getMinimumBookingRate($model->id, $childData, $sessionTotalHours, $week['week_start_date'], $week['week_end_date'], $childAge);
											if ($invoiceDetailsArray !== false) {
												$invoiceDetailsModel->attributes = $invoiceDetailsArray;
												if (!$invoiceDetailsModel->save())
													throw new Exception("Problem occured saving the minimum booking fee for child - " . $childName);
											}
											/** Block for minimum booking fees ends here* */
										}
									}
									if (Branch::currentBranch()->is_exclude_funding == 1 && Branch::currentBranch()->funding_allocation_type == Branch::AS_PER_AVERAGE) {
										$setAverageRateModel = ChildInvoiceDetails::model()->findAllByAttributes([
											'invoice_id' => $model->id, 'exclude_funding' => 0]);
										if (!empty($setAverageRateModel)) {
											foreach ($setAverageRateModel as $averageRate) {
												$previousInvoiceDetails = ChildInvoiceDetails::model()->findAllByAttributes([
													'invoice_id' => $model->id, 'exclude_funding' => 1, 'week_start_date' => $averageRate->week_start_date]);
												foreach ($previousInvoiceDetails as $previousInvoiceDetail) {
													$previousInvoiceDetail->average_rate = $averageRate->average_rate;
													$previousInvoiceDetail->save();
												}
											}
										}
									}
									$model->total = customFunctions::returnnvoiceAmount($model->id);
								}
							} else {
								throw new Exception("Some problem occured saving the Invoice for child - " . $childId);
							}
							$checkInvoiceModel = ChildInvoice::model()->findByPk($model->id);
							$checkInvoiceDetails = $checkInvoiceModel->childInvoiceDetails;
							if (empty($checkInvoiceDetails)) {
								if ($childData->branch->is_minimum_booking_rate_enabled == 1) {
									throw new Exception('Invoice not generated for child - ' . $childName . ' as their is no preffered session selected.');
								} else {
									throw new Exception('Invoice not generated for child - ' . $childName . ' as thier are no sessions scheduled for current month.');
								}
							} else {
								if ($invoiceSettingModal->auto_send_invoice == 1) {
									$this->actionSendInvoiceEmail($model->id);
								}
								foreach ($bookingsModel as $booking) {
									$booking->is_invoiced = 1;
									if (!$booking->save()) {
										throw new Exception("Their seems to be some problem marking the sessions as invoiced for child - $childData->name");
									}
								}
								if (Branch::currentBranch()->include_last_month_uninvoiced_sessions == 1 && false) {
									foreach ($lastMonthBookingsModel as $lastMonthBooking) {
										$lastMonthBooking->invoice_id = $model->id;
										$lastMonthBooking->is_invoiced = 1;
										if (!$lastMonthBooking->save()) {
											throw new Exception("Their seems to be some problem updating the invoice id for child - $childData->name");
										}
									}
								}
								$tempChildInvoice = $model->attributes;
								$tempChildInvoiceDetails = $model->childInvoiceDetails;
								$transaction->rollback();
								$childInvoiceTemp = new ChildInvoiceTemp;
								$tempTransaction = Yii::app()->db->beginTransaction();
								try {
									$childInvoiceTemp->attributes = $tempChildInvoice;
									if ($childInvoiceTemp->save()) {
										foreach ($tempChildInvoiceDetails as $tempChildInvoiceDetail) {
											$childInvoiceDetailsTemp = new ChildInvoiceDetailsTemp;
											$childInvoiceDetailsTemp->attributes = $tempChildInvoiceDetail->attributes;
											$childInvoiceDetailsTemp->invoice_id = $childInvoiceTemp->id;
											if (!$childInvoiceDetailsTemp->save()) {
												throw new Exception("Errors Occured in temp invoice details.");
											}
										}
									} else {
										throw new Exception("Errors Occured in temp invoice.");
									}
									$tempTransaction->commit();
								} catch (Exception $tempException) {
									$tempTransaction->rollback();
								}
								$logging_messages[] = "Invoice successfully generated for child - " . $childName;
							}
						} else {
							throw new Exception("Invoice Exists for child - " . $childName);
						}
					} catch (Exception $ex) {
						$transaction->rollback();
						$logging_messages[] = $ex->getMessage();
					}
				}
				if ($return_error == 1) {
					return array('status' => 1, 'message' => $logging_messages);
				} else {
					echo CJSON::encode(array('status' => 1, 'message' => $logging_messages));
				}
			} else {
				if ($return_error == 1) {
					return ['status' => 2, 'message' => "<span style='color:red'>Child details for the current selection could not be found.</span>"];
					Yii::app()->end();
				} else {
					echo CJSON::encode(['status' => 2, 'message' => "<span style='color:red'>Child details for the current selection could not be found.</span>"]);
					Yii::app()->end();
				}
			}
		} else {
			if ($return_error == 1) {
				return ['status' => 2, 'message' => "<span style='color:red'>Please create invoice Settings for current Branch / Nursery</span>"];
				Yii::app()->end();
			} else {
				echo CJSON::encode(['status' => 2, 'message' => "<span style='color:red'>Please create invoice Settings for current Branch / Nursery</span>"]);
				Yii::app()->end();
			}
		}
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
			foreach ($total_booking_days as $booking_day) {
				$day_number = date('N', strtotime($booking_day));
				$criteria = new CDbCriteria();
				$criteria->condition = "age_group > :age AND session_id = :session_id AND day_" . $day_number . " = :day_number";
				$criteria->order = "age_group";
				$criteria->limit = "1";
				$criteria->params = array(':age' => $age, ':session_id' => $session_id, ':day_number' => $day_number);
				$mappingModel = SessionRateWeekdayMapping::model()->find($criteria);
				$rate = "rate_" . $day_number;
				$average_rate += $mappingModel->$rate;
			}
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

	public function checkUniqueInvoice($attribute, $params) {
		if ($this->isNewRecord) {
			$invoice = ChildInvoiceTemp::model()->findAll('child_id = :child_id AND month = :month AND year = :year AND invoice_type = :invoice_type', array(':child_id' => $this->child_id, ':month' => $this->month, ':year' => $this->year, ':invoice_type' => 0));
			if (!empty($invoice)) {
				$this->addError($attribute, 'Invoice Already Exists for this month.');
			}
		} else {
			$invoice = ChildInvoiceTemp::model()->findAll('child_id = :child_id AND month = :month AND year = :year AND invoice_type = :invoice_type AND id != :id', array(':child_id' => $this->child_id, ':month' => $this->month, ':year' => $this->year, ':invoice_type' => 0, ':id' => $this->id));
			if (!empty($invoice)) {
				$this->addError($attribute, 'Invoice Already Exists for this month.');
			}
		}
	}

}
