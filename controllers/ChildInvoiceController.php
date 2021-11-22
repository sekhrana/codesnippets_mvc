<?php

//Demo Commit
Yii::app()->clientScript->registerScript('invoicingHelpers', '
          yii = {
              urls: {
                  childSessionData: ' . CJSON::encode(Yii::app()->createUrl('childInvoice/getSessionData')) . ',
                  getInvoicePayments: ' . CJSON::encode(Yii::app()->createUrl('childInvoice/getInvoicePayments')) . ',
                  payInvoice: ' . CJSON::encode(Yii::app()->createUrl('childInvoice/payInvoice')) . ',
                  viewInvoice: ' . CJSON::encode(Yii::app()->createUrl('childInvoice/viewInvoice')) . ',
                  sendInvoiceEmail: ' . CJSON::encode(Yii::app()->createUrl('childInvoice/sendInvoiceEmail')) . ',
              }
          };
      ', CClientScript::POS_END);

Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseUrl . '/js/childInvoicing.js?version=1.0.19', CClientScript::POS_END);

Yii::app()->clientScript->registerScript('helpers', '
          eyMan = {
              urls: {
                  removeMinimumBookingFees: ' . CJSON::encode(Yii::app()->createUrl('childInvoice/removeMinimumBookingFees')) . ' ,
                  removeAdditionalItem: ' . CJSON::encode(Yii::app()->createUrl('childInvoice/removeAdditionalItem')) . ',
                  uninvoicedSessions: ' . CJSON::encode(Yii::app()->createUrl('childBookings/uninvoicedSessions')) . ',
                  GoCardlessPaymentModeId: ' . CJSON::encode(ChildInvoice::GOCARDLESS_PAYMENT_MODE_ID) . ',
                  generateInvoices: ' . CJSON::encode(Yii::app()->createUrl('childInvoice/generateInvoices')) . ',
              }
          };
      ', CClientScript::POS_END);

class ChildInvoiceController extends eyManController {

	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout = '//layouts/dashboard';

	/**
	 * @return array action filters
	 */
	public function filters() {
		return array(
			'rights',
		);
	}

	public function allowedActions() {

		return 'invoiceEmailView';
	}

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($child_id, $invoice_id) {
		$this->pageTitle = 'Invoice - View | eyMan';
		$invoiceOverpaymentModal = new ChildInvoice;
		$creditNoteTransactionModal = new ChildInvoiceTransactions;
		$day_array = array(
			0 => 'Sun',
			1 => 'Mon',
			2 => 'Tue',
			3 => 'Wed',
			4 => 'Thu',
			5 => 'Fri',
			6 => 'Sat'
		);
		$model = ChildInvoice::model()->findByAttributes([
			'id' => $invoice_id,
			'child_id' => $child_id
		]);
		if(empty($model)){
			throw new CHttpException(404, 'This page does not exists.');
		}
		$childModel = ChildPersonalDetailsNds::model()->findByPk($child_id);
		$creditNotes = $this->actionGetCreditNotes($child_id);
		$parentModel = $childModel->getFirstBillPayer();
		$invoiceDetaisModel = ChildInvoiceDetails::model()->findAllByAttributes(array(
			'invoice_id' => $model->id, 'is_extras' => 0));
		$invoiceExtrasModel = ChildInvoiceDetails::model()->findAllByAttributes(array(
			'invoice_id' => $model->id, 'is_extras' => 1));
		$regenratedInvoiceModel = ChildInvoice::model()->findAllByAttributes(array('urn_number' => $model->urn_number,
			'branch_id' => $model->branch_id, 'is_regenrated' => 1), array('order' => 'created'));
		$temp = array();
		foreach ($invoiceDetaisModel as $invoiceDetails) {
			array_push($temp, $invoiceDetails->attributes);
		}
		$weekStartDates = array_unique(array_column($temp, 'week_start_date'));
		$invoiceDetailsArray = array();
		foreach ($weekStartDates as $key => $value) {
			foreach ($invoiceDetaisModel as $invoiceDetails) {
				$columns = $invoiceDetails->attributes;
				if ($columns['week_start_date'] == $value) {
					$invoiceDetailsArray[$value . ":" . $columns['week_end_date']][] = $columns;
				}
			}
		}
		$invoiceTransactionModel = new ChildInvoiceTransactions;
		$oldTransactions = new CActiveDataProvider('ChildInvoiceTransactionsNds', array(
			'criteria' => array(
				'condition' => 'invoice_id = :invoice_id',
				'order' => 'id DESC',
				'params' => array(':invoice_id' => $invoice_id)
			),
			'pagination' => array(
				'pageSize' => 20,
			),
		));
		if (isset($_POST['ChildInvoiceTransactions']) && isset($_POST['pay_invoice'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$invoiceModel = ChildInvoice::model()->findByPk($_POST['ChildInvoiceTransactions']['invoice_id']);
				$paid_amount = customFunctions::getPaidAmount($invoiceModel->id);
				if (!empty($invoiceModel)) {
					$invoiceTransactionModel->attributes = $_POST['ChildInvoiceTransactions'];
					$invoiceTransactionModel->invoice_amount = $invoiceModel->total;
					if (($invoiceTransactionModel->paid_amount + $paid_amount) < $invoiceModel->total) {
						$invoiceModel->status = 'AWAITING_PAYMENT';
					} else {
						$invoiceModel->status = 'PAID';
					}
					$paymentModel = new Payments;
					$paymentModel->attributes = $invoiceModel->attributes;
					$paymentModel->date_of_payment = $invoiceTransactionModel->date_of_payment;
					$paymentModel->payment_mode = $invoiceTransactionModel->payment_mode;
					$paymentModel->payment_reference = $invoiceTransactionModel->payment_refrence;
					$paymentModel->amount = $invoiceTransactionModel->paid_amount;
					$paymentModel->status = 1;
					$paymentModel->child_id = $invoiceModel->child_id;
					if ($paymentModel->save()) {
						$paymentTransaction = new PaymentsTransactions;
						$paymentTransaction->invoice_id = $invoiceModel->id;
						$paymentTransaction->payment_id = $paymentModel->id;
						$paymentTransaction->paid_amount = $invoiceTransactionModel->paid_amount;
						if ($paymentTransaction->save()) {
							$invoiceTransactionModel->payment_id = $paymentTransaction->id;
							if ($invoiceTransactionModel->save()) {
								if ($invoiceModel->save()) {
									if ($invoiceTransactionModel->payment_mode == ChildInvoiceTransactions::PAYMENT_MODE_GOCARDLESS) {
										$invoiceModel->recordGoCardlessPayment(Parents::model()->findByPk($_POST['ChildInvoiceTransactions']['parent_id']), $invoiceTransactionModel, $paymentModel);
									}
									$transaction->commit();
									$this->redirect(array('childInvoice/view', 'child_id' => $child_id, 'invoice_id' => $invoice_id));
								} else {
									throw new Exception(CHtml::errorSummary($invoiceModel, "", "", array('class' => 'customErrors')));
								}
							} else {
								throw new Exception(CHtml::errorSummary($invoiceTransactionModel, "", "", array(
									'class' => 'customErrors')));
							}
						} else {
							throw new Exception(CHtml::errorSummary($paymentTransaction, "", "", array(
								'class' => 'customErrors')));
						}
					} else {
						throw new Exception(CHtml::errorSummary($paymentModel, "", "", array('class' => 'customErrors')));
					}
				} else {
					throw new Exception("Invoice you are trying to pay does not exists.");
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				Yii::app()->user->setFlash('error_date_of_payment', $ex->getMessage());
				$invoiceTransactionModel->isNewRecord = true;
				$this->refresh();
			}
		}
		$this->render('view', array(
			'model' => $model,
			'childModel' => $childModel,
			'parentModel' => $parentModel,
			'invoiceDetaisModel' => $invoiceDetaisModel,
			'day_array' => $day_array,
			'invoiceTransactionModel' => $invoiceTransactionModel,
			'oldTransactions' => $oldTransactions,
			'invoiceDetailsArray' => $invoiceDetailsArray,
			'regenratedInvoiceModel' => $regenratedInvoiceModel,
			'invoiceOverpaymentModal' => $invoiceOverpaymentModal,
			'creditNotes' => $creditNotes,
			'creditNoteTransactionModal' => $creditNoteTransactionModal,
			'invoiceExtrasModel' => $invoiceExtrasModel));
	}

	/*
	 * This action generates the automatic invoices as per the invocie settings.
	 */

	public function actionInvoice($month, $year, $child_id_array, $is_all_child, $invoice_date, $invoice_due_date, $return_error = 0) {
		$branch_id = Yii::app()->session['branch_id'];
		$invoiceSettingModal = InvoiceSetting::model()->findByAttributes(array('branch_id' => $branch_id));
		if (!empty($invoiceSettingModal)) {
			$invoiceDates = ChildInvoice::model()->setInvoiceDates($invoiceSettingModal, $month, $year);
			$invoice_dates_array = ['invoice_from_date' => $invoiceDates['invoice_from_date'],
				'invoice_to_date' => $invoiceDates['invoice_to_date'], 'invoice_due_date' => $invoice_due_date,
				'invoice_date' => $invoice_date];
			$actualInvoiceFromDate = $invoice_dates_array['invoice_from_date'];
			$actualInvoiceToDate = $invoice_dates_array['invoice_to_date'];
			if ($is_all_child == 1) {
				$childModal = ChildPersonalDetails::model()->findAllByAttributes(array('branch_id' => $branch_id));
			} else {
				$criteria = new CDbCriteria();
				$criteria->addInCondition('id', $child_id_array);
				$childModal = ChildPersonalDetails::model()->findAll($criteria);
			}
			if (!empty($childModal)) {
				$invoiceDueDate = $invoice_dates_array['invoice_due_date'];
				$logging_messages = array();
				foreach ($childModal as $childData) {
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
						$monthlySessionModal = SessionRates::model()->findAllByAttributes(array('is_modified' => 0, 'multiple_rates_type' => 3, 'branch_id' => $childData->branch_id));
						if (!empty($monthlySessionModal)) {
							$childAge = customFunctions::getAge(date("Y-m-d", strtotime($childData->dob)), date("Y-m-d", strtotime("-1 day", strtotime($invoiceFromDate))));
							foreach ($monthlySessionModal as $monthlySession) {
								if ($this->actionCheckMonthlyRateExists($childAge, $monthlySession->id) != FALSE) {
									$flag = customFunctions::checkMonthlySessionExists($childData->id, $invoiceFromDate, $invoiceToDate, $monthlySession->id);
									if ($flag) {
										$monthlySessionTime = customFunctions::getHours($monthlySession->start_time, $monthlySession->finish_time);
										$monthlySessionId = $monthlySession->id;
										$isMonthlyInvoice = true;
										$monthlyAmount = $this->actionCheckMonthlyRateExists($childAge, $monthlySession->id);
										break;
									}
								}
							}
						}
						$weekArray = customFunctions::getWeekBetweenDate($invoiceFromDate, $invoiceToDate);
						/** Block for Including last month uninvoiced sessions starts here* */
						if (Branch::currentBranch()->include_last_month_uninvoiced_sessions == 1) {
							if ($month == 1) {
								$lastYear = $year - 1;
								$lastMonth = 12;
							} else {
								$lastYear = $year;
								$lastMonth = ((int) $month - 1);
							}
							$lastMonthDates = ChildInvoice::model()->setInvoiceDates($invoiceSettingModal, $lastMonth, $lastYear);
							$lastMonthFromDate = $lastMonthDates['invoice_from_date'];
							$lastMonthToDate = $lastMonthDates['invoice_to_date'];
							if (!empty($childData->start_date) && (strtotime(date("Y-m-d", strtotime($childData->start_date))) < strtotime($invoiceFromDate))) {

								if (!empty($childData->start_date) && ((strtotime(date("Y-m-d", strtotime($childData->start_date))) > strtotime($lastMonthFromDate)) && (strtotime(date("Y-m-d", strtotime($childData->start_date))) < strtotime($lastMonthToDate)))) {
									$lastMonthFromDate = date("Y-m-d", strtotime($childData->start_date));
								}
								if (!empty($childData->leave_date) && ((strtotime(date("Y-m-d", strtotime($childData->leave_date))) > strtotime($lastMonthFromDate)) && (strtotime(date("Y-m-d", strtotime($childData->leave_date))) < strtotime($lastMonthToDate)))) {
									$lastMonthToDate = date("Y-m-d", strtotime($childData->leave_date));
								}
								$lastMonthWeeks = customFunctions::getWeekBetweenDate($lastMonthFromDate, $lastMonthToDate, 1);
								$weekArray = array_merge($weekArray, $lastMonthWeeks);
								$lastMonthBookingsModel = ChildBookings::model()->getBookings($lastMonthFromDate, $lastMonthToDate, $childData->branch_id, $childData->id, NULL, NULL, NULL, 0);
								ChildBookings::model()->breakSeries($lastMonthFromDate, $lastMonthToDate, $childData->branch_id, $childData->id, $lastMonthBookingsModel);
							}
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
									if (!$booking->save(false)) {
										throw new Exception("There seems to be some problem updating the invoice id for child - $childData->name");
									}
								}
								if ($isMonthlyInvoice) {
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
										if ($week['is_last_month'] == 1 && strtotime($week['week_start_date']) < strtotime($lastMonthFromDate)) {
											$isIncompleteWeekStart = true;
											$week['week_start_date'] = $lastMonthFromDate;
										}
										if (strtotime($week['week_end_date']) > strtotime($lastMonthToDate) && $week['is_last_month'] == 1) {
											$isIncompleteWeekEnd = true;
											$week['week_end_date'] = $lastMonthToDate;
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
													$calculated_rate = $this->actionGetMultipleRate($age, $sessionId, $total_hours_multiple_rates, $invoiceDetailsModel->funded_hours, $actualWeekStartDate, $actualWeekEndDate);
													if ($calculated_rate == FALSE) {
														$invoiceDetailsModel->rate = 0;
													} else {
														$invoiceDetailsModel->rate = $this->actionGetMultipleRate($age, $sessionId, $total_hours_multiple_rates, $invoiceDetailsModel->funded_hours, $actualWeekStartDate, $actualWeekEndDate);
													}
												} else if ($sessionRateModel->is_multiple_rates == 1 && $sessionRateModel->multiple_rates_type == 2) {
													$calculated_rate = $this->actionGetMultipleRatesWeekdays($age, $sessionId, $total_booking_days, $actualWeekStartDate, $actualWeekEndDate);
													if ($calculated_rate == FALSE) {
														$invoiceDetailsModel->rate = 0;
													} else {
														$invoiceDetailsModel->rate = $calculated_rate;
													}
												} else if ($sessionRateModel->is_multiple_rates == 1 && $sessionRateModel->multiple_rates_type == 3) {
													$calculated_rate = $this->actionGetMultipleRatesTotalWeekdays($age, $sessionId, $actualWeekBookingDays, $actualWeekStartDate, $actualWeekEndDate);
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
									customFunctions::getInvoiceAmount($model->id);
								}
							} else {
								throw new Exception("Some problem occured saving the Invoice for child - " . $childId);
							}
							$checkInvoiceModel = ChildInvoice::model()->findByPk($model->id);
							$checkInvoiceDetails = $checkInvoiceModel->childInvoiceDetails;
							if (empty($checkInvoiceDetails)) {
								if ($childData->branch->is_minimum_booking_rate_enabled == 1) {
									throw new Exception('Invoice not generated for child - ' . $childName . ' as there is no preffered session selected.');
								} else {
									throw new Exception('Invoice not generated for child - ' . $childName . ' as there are no sessions scheduled for current month.');
								}
							} else {
								if ($invoiceSettingModal->auto_send_invoice == 1) {
									$this->actionSendInvoiceEmail($model->id);
								}
								foreach ($bookingsModel as $booking) {
									$booking->is_invoiced = 1;
									if (!$booking->save()) {
										throw new Exception("There seems to be some problem marking the sessions as invoiced for child - $childData->name");
									}
								}
								if (Branch::currentBranch()->include_last_month_uninvoiced_sessions == 1) {
									if (!empty($lastMonthBookingsModel)) {
										foreach ($lastMonthBookingsModel as $lastMonthBooking) {
											$lastMonthBooking->invoice_id = $model->id;
											$lastMonthBooking->is_invoiced = 1;
											if (!$lastMonthBooking->save(false)) {
												throw new Exception("There seems to be some problem updating the invoice id for child - $childData->name");
											}
										}
									}
								}
								$transaction->commit();
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

	/*
	 * This action generates the monthly invoices as per the child monthly amount.
	 */

	public function actionInvoiceMonthly($month, $year, $child_id_array, $is_all_child, $invoice_date, $invoice_due_date) {
		$branch_id = Yii::app()->session['branch_id'];
		$invoiceSettingModal = InvoiceSetting::model()->findByAttributes(array('branch_id' => $branch_id));
		if (!empty($invoiceSettingModal)) {
			$invoiceDates = ChildInvoice::model()->setInvoiceDates($invoiceSettingModal, $month, $year);
			$invoiceFromDate = $invoiceDates['invoice_from_date'];
			$invoiceToDate = $invoiceDates['invoice_to_date'];
			$weekArray = customFunctions::getWeekBetweenDate($invoiceFromDate, $invoiceToDate);
			if ($is_all_child == 1) {
				$childModal = ChildPersonalDetails::model()->findAllByAttributes(array('branch_id' => $branch_id));
			} else {
				$criteria = new CDbCriteria();
				$criteria->addInCondition('id', $child_id_array);
				$childModal = ChildPersonalDetails::model()->findAll($criteria);
			}
			if (!empty($childModal)) {
				$invoiceDueDate = $invoice_due_date;
				$log = array();
				foreach ($childModal as $childData) {
					if (isset($childData->start_date) && !empty($childData->start_date)) {
						if (strtotime($childData->start_date) > strtotime($invoiceFromDate)) {
							$actualInvoiceLog = $this->actionInvoice($month, $year, [$childData->id], 0, $invoice_date, $invoice_due_date, 1);
							if ($actualInvoiceLog['status'] == 1) {
								$log[] = $actualInvoiceLog['message'][0];
							} else {
								echo CJSON::encode(['status' => $actualInvoiceLog['status'], 'message' => $actualInvoiceLog['message'][0]]);
								Yii::app()->end();
							}
							continue;
						}
					}
					$transaction = Yii::app()->db->beginTransaction();
					try {
						if (!isset($childData->monthly_invoice_amount, $childData->monthly_invoice_start_date, $childData->monthly_invoice_finish_date)) {
							$monthlyAmountData = $this->actionInvoiceMonthlyAmountForInvoice($childData->id, $month . "-" . $year, date("m-Y", strtotime("+" . ($invoiceSettingModal->invoice_generate_month_count - 1) . " month", strtotime(date("Y-m-d", strtotime("01-" . $month . "-" . $year))))));
							if ($monthlyAmountData['status'] == 0) {
								throw new Exception($monthlyAmountData['message']);
							} else if ($monthlyAmountData['status'] == 2) {
								throw new Exception($monthlyAmountData['message']);
							} else {
								$childData->monthly_invoice_amount = $monthlyAmountData['amount'];
								ChildPersonalDetails::model()->updateByPk($childData->id, [
									'monthly_invoice_amount' => $monthlyAmountData['amount'],
									'monthly_invoice_start_date' => $monthlyAmountData['invoice_from_date_monthlyInvoice'],
									'monthly_invoice_finish_date' => $monthlyAmountData['invoice_to_date_monthlyInvoice']
								]);
							}
						}
						if (!customFunctions::checkInvoiceExists($invoiceFromDate, $invoiceToDate, $childData->id)) {
							$bookingsModel = ChildBookings::model()->getBookings($invoiceDates['invoice_from_date'], $invoiceDates['invoice_to_date'], $childData->branch_id, $childData->id, NULL, NULL, 1, NULL);
							ChildBookings::model()->breakSeries($invoiceDates['invoice_from_date'], $invoiceDates['invoice_to_date'], $childData->branch_id, $childData->id, $bookingsModel);
							$urn = customFunctions::getInvoiceUrn($branch_id);
							$model = new ChildInvoice;
							$model->child_id = $childData->id;
							$model->branch_id = $branch_id;
							$model->from_date = $invoiceFromDate;
							$model->to_date = $invoiceToDate;
							$model->urn_prefix = empty($urn['prefix']) ? NULL : $urn['prefix'];
							$model->urn_number = $urn['number'];
							$model->urn_suffix = empty($urn['suffix']) ? NULL : $urn['suffix'];
							$model->status = 'AWAITING_PAYMENT';
							$model->due_date = $invoice_due_date;
							$model->invoice_date = $invoice_date;
							$model->invoice_type = 0;
							$model->total = sprintf("%0.2f", ($childData->monthly_invoice_amount - (($childData->discount * $childData->monthly_invoice_amount * 0.01))));
							$model->access_token = md5(time() . uniqid() . $model->id . $model->child_id);
							$model->is_monthly_invoice = 1;
							$model->month = $month;
							$model->year = $year;
							if ($model->save()) {
								foreach ($bookingsModel as $booking) {
									ChildBookings::model()->updateByPk($booking->id, [
										'is_invoiced' => 1,
										'invoice_id' => $model->id
									]);
								}
								$funded_hours = Terms::getTermByInvoiceMonth($model->branch_id, $model->year, $model->month, $childData, $model);
								$invoiceDetailsModel = new ChildInvoiceDetails;
								$invoiceDetailsModel->invoice_id = $model->id;
								$invoiceDetailsModel->week_start_date = $model->from_date;
								$invoiceDetailsModel->week_end_date = $model->to_date;
								$invoiceDetailsModel->discount = $childData->discount;
								$invoiceDetailsModel->rate = $childData->monthly_invoice_amount;
								ChildFundingDetails::updateFundingInvoiceIdForMonthlyInvoice($funded_hours['funding_id'], $model->id, $model->month);
								$invoiceDetailsModel->funded_hours = ($funded_hours) ? $funded_hours['funded_hours'] : 0;
								$invoiceDetailsModel->funded_rate = ($funded_hours) ? $funded_hours['funding_rate'] : 0;
								$invoiceDetailsModel->products_data = ChildBookings::getBookingProducts($model->from_date, $model->to_date, $model->child);
								if ($invoiceDetailsModel->save()) {
									if (!empty($invoiceDetailsModel->products_data) && ($invoiceDetailsModel->products_data != NULL)) {
										$product_total = 0;
										$products_data = CJSON::decode($invoiceDetailsModel->products_data);
										if (!empty($products_data)) {
											foreach ($products_data as $product) {
												$product_total += $product[5];
											}
										}
									}
									$model->total = $childData->monthly_invoice_amount + $product_total - $invoiceDetailsModel->funded_rate;
									$model->total = sprintf("%0.2f", ($model->total - (0.01 * $invoiceDetailsModel->discount * $model->total)));
									$model->total = ($model->total < 0) ? 0 : $model->total;
									if ($model->save()) {
										$transaction->commit();
										$log[] = "<span style='color:green'>Invoice successfully generated for child - <b>" . $childData->name . "</b></span>";
									} else {
										throw new Exception("Invoice Amount could not be saved for child - " . $childData->name);
									}
								} else {
									throw new Exception("Invoice details could not be saved for child - " . $childData->name);
								}
							}
						} else {
							throw new Exception("Invoice Exists for child - " . $childData->name);
						}
					} catch (Exception $ex) {
						$transaction->rollback();
						$log[] = "<span style='color:red'>" . $ex->getMessage() . "</span>";
					}
				}
				echo CJSON::encode(array('status' => 1, 'message' => $log));
			} else {
				echo CJSON::encode(['status' => 2, 'message' => "<span style='color:red'>Child details for the current selection could not be found.</span>"]);
				Yii::app()->end();
			}
		} else {
			echo CJSON::encode(['status' => 2, 'message' => "<span style='color:red'>Please create invoice Settings for current Branch / Nursery</span>"]);
			Yii::app()->end();
		}
	}

	public function actionInvoiceMonthlyAmountForInvoice($child_id, $from_month, $to_month) {
		if ($child_id == NULL)
			return ['status' => 0, 'message' => 'Child ID is not set in the request'];
		if ($from_month == NULL || $to_month == NULL)
			return ['status' => 0, 'message' => "Invoice From and to date are not set in the request."];
		$invoiceFromMonth_monthlyInvoice = date("Y-m-d", strtotime("01-" . $from_month));
		$invoiceToMonth_monthlyInvoice = date("Y-m-d", strtotime("01-" . $to_month));
		$invoiceDueDate_monthlyInvoice = date("Y-m-d", strtotime($invoiceFromMonth_monthlyInvoice));
		$invoiceSettingsModel = InvoiceSetting::model()->findByAttributes(['branch_id' => ChildPersonalDetails::model()->findByPk($child_id)->branch_id]);
		$invoiceFromDate_monthlyInvoice = ChildInvoice::model()->setInvoiceDatesForMonthlyInvoice($invoiceSettingsModel, date("m", strtotime($invoiceFromMonth_monthlyInvoice)), date("Y", strtotime($invoiceFromMonth_monthlyInvoice)))[0];
		$invoiceToDate_monthlyInvoice = ChildInvoice::model()->setInvoiceDatesForMonthlyInvoice($invoiceSettingsModel, date("m", strtotime($invoiceToMonth_monthlyInvoice)), date("Y", strtotime($invoiceToMonth_monthlyInvoice)))[1];
		$numberOfMonths = ((date("Y", strtotime($invoiceToDate_monthlyInvoice)) - date("Y", strtotime($invoiceFromDate_monthlyInvoice))) * 12) + (date("m", strtotime($invoiceToDate_monthlyInvoice)) - date("m", strtotime($invoiceFromDate_monthlyInvoice)));
		if (strtotime($invoiceToDate_monthlyInvoice) < strtotime($invoiceFromDate_monthlyInvoice))
			return ['status' => 0, 'message' => "Invoice from date can not be smaller than invoice to date."];
		$childData = ChildPersonalDetails::model()->findByPk($child_id);
		if (!empty($childData)) {
			//Checking the child start and leave date and accordingly setting the dates of invoice
			if (!empty($childData->start_date) && (strtotime(date("Y-m-d", strtotime($childData->start_date))) > strtotime($invoiceToDate_monthlyInvoice))) {
				return ['status' => 2, 'message' => "Invoice amount can not be calculated as start date of child - $childData->name is greater than the invoice to date."];
			}
			if (!empty($childData->leave_date) && (strtotime(date("Y-m-d", strtotime($childData->leave_date))) < strtotime($invoiceFromDate_monthlyInvoice))) {
				return ['status' => 2, 'message' => "Invoice amount can not be calculated as leave date of child - $childData->name is smaller than the invoice from date."];
			}
			//Checking the child start and leave date ends here
			$monthlyTotalAmount = 0;
			$childId = $childData->id;
			ChildBookings::model()->breakSeries($invoiceFromDate_monthlyInvoice, $invoiceToDate_monthlyInvoice, $childData->branch_id, $childData->id);
			$bookingsModel = ChildBookings::model()->getBookings($invoiceFromDate_monthlyInvoice, $invoiceToDate_monthlyInvoice, $childData->branch_id, $childData->id);
			foreach ($bookingsModel as $bookings) {
				$bookings->included_in_invoice_amount = 1;
				if (!$bookings->save()) {
					return ['status' => 2, 'message' => "There seems to be some problem marking the bookings as invoiced for child $childData->name"];
				}
			}
			$weekArray = customFunctions::getWeekBetweenDate($invoiceFromDate_monthlyInvoice, $invoiceToDate_monthlyInvoice);
			if (!customFunctions::checkInvoiceExists($invoiceFromDate_monthlyInvoice, $invoiceToDate_monthlyInvoice, $childId)) {
				foreach ($weekArray as $week) {
					$actualWeekBookingDays = 0;
					$actualWeekBookingDaysNew = array();
					$isIncompleteWeekStart = false;
					$isIncompleteWeekEnd = false;
					$incompleteSessionDays = "";
					$incompleteBookingDays = false;
					$actualWeekStartDate = $week['week_start_date'];
					$actualWeekEndDate = $week['week_end_date'];
					if (strtotime($week['week_start_date']) < strtotime($invoiceFromDate_monthlyInvoice)) {
						$isIncompleteWeekStart = true;
						$week['week_start_date'] = $invoiceFromDate_monthlyInvoice;
					}
					if (strtotime($week['week_end_date']) > strtotime($invoiceToDate_monthlyInvoice)) {
						$isIncompleteWeekEnd = true;
						$week['week_end_date'] = $invoiceToDate_monthlyInvoice;
					}
					$sessionData = customFunctions::getChildSessionDataForInvoicing($childId, $week['week_start_date'], $week['week_end_date'], $actualWeekStartDate, $actualWeekEndDate);
					$branchHoliday = customFunctions::getBranchHolidays($week['week_start_date'], $week['week_end_date']);
					if (!empty($sessionData)) {
						$sessionBooked = array_unique(array_column($sessionData, 'session_type_id'));
						foreach ($sessionBooked as $sessionId) {
							$checkSessionNotEmpty = true;
							$total_hours = 0;
							$total_days = 0;
							$total_booking_days = array();
							$session_data = array();
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
							foreach ($sessionData as $key => $value) {
								if ($value['session_type_id'] == $sessionId) {
									array_push($current_session_data, $sessionData[$key]);
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
										$session_data[$day][] = $value['start_time'] . "-" . $value['finish_time'];
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
										$session_data[$day][] = $value['start_time'] . "-" . $value['finish_time'];
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
							$invoiceDetailsModel->total_hours = $total_hours;
							$invoiceDetailsModel->session_id = $sessionId;
							$invoiceDetailsModel->total_days = count($session_data);
							$invoiceDetailsModel->funded_hours = 0;
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
								$calculated_rate = $this->actionGetMultipleRate($age, $sessionId, $total_hours_multiple_rates, $invoiceDetailsModel->funded_hours, $actualWeekStartDate, $actualWeekEndDate);
								if ($calculated_rate == FALSE) {
									$invoiceDetailsModel->rate = 0;
								} else {
									$invoiceDetailsModel->rate = $this->actionGetMultipleRate($age, $sessionId, $total_hours_multiple_rates, $invoiceDetailsModel->funded_hours, $actualWeekStartDate, $actualWeekEndDate);
								}
							} else if ($sessionRateModel->is_multiple_rates == 1 && $sessionRateModel->multiple_rates_type == 2) {
								$calculated_rate = $this->actionGetMultipleRatesWeekdays($age, $sessionId, $total_booking_days, $actualWeekStartDate, $actualWeekEndDate);
								if ($calculated_rate == FALSE) {
									$invoiceDetailsModel->rate = 0;
								} else {
									$invoiceDetailsModel->rate = $calculated_rate;
								}
							} else if ($sessionRateModel->is_multiple_rates == 1 && $sessionRateModel->multiple_rates_type == 3) {
								$calculated_rate = $this->actionGetMultipleRatesTotalWeekdays($age, $sessionId, $actualWeekBookingDays, $actualWeekStartDate, $actualWeekEndDate);
								$invoiceDetailsModel->rate = $calculated_rate;
								if ($calculated_rate == FALSE) {
									$invoiceDetailsModel->rate = 0;
								}
							} else {
								$invoiceDetailsModel->rate = customFunctions::getRateForInvoicing($childId, $sessionId, $total_hours, $actualWeekStartDate, $actualWeekEndDate);
							}
							$invoiceDetailsModel->discount = $childData->discount;
							if ($checkSessionNotEmpty) {
								$monthlyTotalAmount += customFunctions::getInvoiceAmountForMonthlyInvoice($invoiceDetailsModel);
							}
						}
					}
				}
				return array('status' => 1, 'invoice_from_date_monthlyInvoice' => $invoiceFromDate_monthlyInvoice,
					'invoice_to_date_monthlyInvoice' => $invoiceToDate_monthlyInvoice, 'amount' => sprintf("%0.2f", $monthlyTotalAmount / ($numberOfMonths + 1)),
					'message' => "Monthly Amount has been calculated successfully.");
			} else {
				return ['status' => 2, 'message' => "Invoice already exists for the given dates for child - $childData->name"];
			}
		} else {
			return ['status' => 2, 'message' => "Child - $childData->name does not exists in the nursery."];
		}
	}

	public function actionInvoiceMonthlyAmount() {
		if (Yii::app()->request->isAjaxRequest) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				if (Yii::app()->request->getPost("child_id") == NULL)
					throw new Exception("Child ID is not set in the request");
				if (Yii::app()->request->getPost("from_month") == NULL || Yii::app()->request->getPost("to_month") == NULL)
					throw new Exception("Invoice From and to date are not set.");
				$invoiceFromMonth = date("Y-m-d", strtotime("01-" . Yii::app()->request->getPost("from_month")));
				$invoiceToMonth = date("Y-m-d", strtotime("01-" . Yii::app()->request->getPost("to_month")));
				$invoiceDueDate = date("Y-m-d", strtotime(Yii::app()->request->getPost("from_date")));
				$invoiceSettingsModel = InvoiceSetting::model()->findByAttributes(['branch_id' => ChildPersonalDetails::model()->findByPk(Yii::app()->request->getPost("child_id"))->branch_id]);
				$invoiceFromDate = ChildInvoice::model()->setInvoiceDates($invoiceSettingsModel, date("m", strtotime($invoiceFromMonth)), date("Y", strtotime($invoiceFromMonth)))['invoice_from_date'];
				$invoiceToDate = ChildInvoice::model()->setInvoiceDates($invoiceSettingsModel, date("m", strtotime($invoiceToMonth)), date("Y", strtotime($invoiceToMonth)))['invoice_to_date'];
				$numberOfMonths = ((date("Y", strtotime($invoiceToDate)) - date("Y", strtotime($invoiceFromDate))) * 12) + (date("m", strtotime($invoiceToDate)) - date("m", strtotime($invoiceFromDate)));
				if (strtotime($invoiceToDate) < strtotime($invoiceFromDate))
					throw new Exception("Invoice from date can not be smaller than invoice to date.");
				$childData = ChildPersonalDetails::model()->findByPk(Yii::app()->request->getPost("child_id"));
				if (!empty($childData)) {
//Checking the child start and leave date and accordingly setting the dates of invoice
					if (!empty($childData->start_date) && (strtotime(date("Y-m-d", strtotime($childData->start_date))) > strtotime($invoiceToDate))) {
						throw new Exception("Invoice amount can not be calculated as start date of child is greater than the invoice to date.");
					}
					if (!empty($childData->leave_date) && (strtotime(date("Y-m-d", strtotime($childData->leave_date))) < strtotime($invoiceFromDate))) {
						throw new Exception("Invoice amount can not be calculated as leave date of child is smaller than the invoice from date.");
					}
					if (!empty($childData->start_date) && ((strtotime(date("Y-m-d", strtotime($childData->start_date))) > strtotime($invoiceFromDate)) && (strtotime(date("Y-m-d", strtotime($childData->start_date))) < strtotime($invoiceToDate)))) {
						$invoiceFromDate = date("Y-m-d", strtotime($childData->start_date));
					}
					if (!empty($childData->leave_date) && ((strtotime(date("Y-m-d", strtotime($childData->leave_date))) > strtotime($invoiceFromDate)) && (strtotime(date("Y-m-d", strtotime($childData->leave_date))) < strtotime($invoiceToDate)))) {
						$invoiceToDate = date("Y-m-d", strtotime($childData->leave_date));
					}
//Checking the child start and leave date ends here
					$monthlyTotalAmount = 0;
					$childId = $childData->id;
					ChildBookings::model()->breakSeries($invoiceFromDate, $invoiceToDate, $childData->branch_id, $childData->id);
					$bookingsModel = ChildBookings::model()->getBookings($invoiceFromDate, $invoiceToDate, $childData->branch_id, $childData->id);
					foreach ($bookingsModel as $bookings) {
						$bookings->included_in_invoice_amount = 1;
						if (!$bookings->save()) {
							throw new Exception("There seems to be some problem marking the bookings as invoiced.");
						}
					}
					$weekArray = customFunctions::getWeekBetweenDate($invoiceFromDate, $invoiceToDate);
					if (!customFunctions::checkInvoiceExists($invoiceFromDate, $invoiceToDate, $childId)) {
						foreach ($weekArray as $week) {
							$isIncompleteWeekStart = false;
							$isIncompleteWeekEnd = false;
							$incompleteSessionDays = "";
							$incompleteBookingDays = false;
							$actualWeekStartDate = $week['week_start_date'];
							$actualWeekEndDate = $week['week_end_date'];
							if (strtotime($week['week_start_date']) < strtotime($invoiceFromDate)) {
								$isIncompleteWeekStart = true;
								$week['week_start_date'] = $invoiceFromDate;
							}
							if (strtotime($week['week_end_date']) > strtotime($invoiceToDate)) {
								$isIncompleteWeekEnd = true;
								$week['week_end_date'] = $invoiceToDate;
							}
							$sessionData = customFunctions::getChildSessionDataForInvoicing($childId, $week['week_start_date'], $week['week_end_date'], $actualWeekStartDate, $actualWeekEndDate);
							$branchHoliday = customFunctions::getBranchHolidays($week['week_start_date'], $week['week_end_date']);
							if (!empty($sessionData)) {
								$sessionBooked = array_unique(array_column($sessionData, 'session_type_id'));
								foreach ($sessionBooked as $sessionId) {
									$actualWeekBookingDays = 0;
									$actualWeekBookingDaysNew = array();
									$checkSessionNotEmpty = true;
									$total_hours = 0;
									$total_days = 0;
									$total_booking_days = array();
									$session_data = array();
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
									foreach ($sessionData as $key => $value) {
										if ($value['session_type_id'] == $sessionId) {
											array_push($current_session_data, $sessionData[$key]);
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
												$session_data[$day][] = $value['start_time'] . "-" . $value['finish_time'];
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
												$session_data[$day][] = $value['start_time'] . "-" . $value['finish_time'];
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
									$invoiceDetailsModel->total_hours = $total_hours;
									$invoiceDetailsModel->session_id = $sessionId;
									$invoiceDetailsModel->total_days = count($session_data);
									$invoiceDetailsModel->funded_hours = 0;
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
										$age = customFunctions::getAge(date("Y-m-d", strtotime($childData->dob)), date("Y-m-d", strtotime("-1 day", strtotime($invoiceFromDate))));
									}
									if ($sessionRateModel->is_multiple_rates == 1 && $sessionRateModel->multiple_rates_type == 1) {
										$calculated_rate = $this->actionGetMultipleRate($age, $sessionId, $total_hours_multiple_rates, $invoiceDetailsModel->funded_hours, $actualWeekStartDate, $actualWeekEndDate);
										if ($calculated_rate == FALSE) {
											$invoiceDetailsModel->rate = 0;
										} else {
											$invoiceDetailsModel->rate = $this->actionGetMultipleRate($age, $sessionId, $total_hours_multiple_rates, $invoiceDetailsModel->funded_hours, $actualWeekStartDate, $actualWeekEndDate);
										}
									} else if ($sessionRateModel->is_multiple_rates == 1 && $sessionRateModel->multiple_rates_type == 2) {
										$calculated_rate = $this->actionGetMultipleRatesWeekdays($age, $sessionId, $total_booking_days, $actualWeekStartDate, $actualWeekEndDate);
										if ($calculated_rate == FALSE) {
											$invoiceDetailsModel->rate = 0;
										} else {
											$invoiceDetailsModel->rate = $calculated_rate;
										}
									} else if ($sessionRateModel->is_multiple_rates == 1 && $sessionRateModel->multiple_rates_type == 3) {
										$calculated_rate = $this->actionGetMultipleRatesTotalWeekdays($age, $sessionId, $actualWeekBookingDays, $actualWeekStartDate, $actualWeekEndDate);
										$invoiceDetailsModel->rate = $calculated_rate;
										if ($calculated_rate == FALSE) {
											$invoiceDetailsModel->rate = 0;
										}
									} else {
										$invoiceDetailsModel->rate = customFunctions::getRateForInvoicing($childId, $sessionId, $total_hours, $actualWeekStartDate, $actualWeekEndDate);
									}
									$invoiceDetailsModel->discount = $childData->discount;
									if ($checkSessionNotEmpty) {
										$monthlyTotalAmount += customFunctions::getInvoiceAmountForMonthlyInvoice($invoiceDetailsModel);
									}
								}
							}
						}
						$transaction->commit();
						echo CJSON::encode(array('status' => 1, 'invoice_from_date' => $invoiceFromDate,
							'invoice_to_date' => $invoiceToDate, 'amount' => sprintf("%0.2f", $monthlyTotalAmount / ($numberOfMonths + 1)),
							'message' => "Monthly Amount has been calculated successfully."));
					} else {
						throw new Exception("Invoice already exists for the given dates.");
					}
				} else {
					throw new Exception("Child does not exists in the nursery.");
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				echo CJSON::encode(array('status' => 0, 'message' => $ex->getMessage()));
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	public function actionInvoiceMonthlyAmountForMultipleChild() {
		if (Yii::app()->request->isAjaxRequest) {
			if ((Yii::app()->request->getPost("is_all_child") == 0) && (Yii::app()->request->getPost('child_id') == "")) {
				echo CJSON::encode(array('status' => 0, 'message' => 'Please Select atleast one Child.'));
				Yii::app()->end();
			}

			if (Yii::app()->request->getPost("from_month") == NULL || Yii::app()->request->getPost("to_month") == NULL) {
				echo CJSON::encode(array('status' => 0, 'message' => 'Please set From and To month.'));
				Yii::app()->end();
			}
			if (Yii::app()->request->getPost("is_all_child") == 1) {
				$childModal = ChildPersonalDetails::model()->findAllByAttributes(array('branch_id' => Yii::app()->session['branch_id']));
			} else {
				$criteria = new CDbCriteria();
				$criteria->addInCondition('id', Yii::app()->request->getPost('child_id'));
				$childModal = ChildPersonalDetails::model()->findAll($criteria);
			}

			$log = array();
			if (!empty($childModal)) {
				foreach ($childModal as $childData) {
					$transaction = Yii::app()->db->beginTransaction();
					try {
						$invoiceFromMonth = date("Y-m-d", strtotime("01-" . Yii::app()->request->getPost("from_month")));
						$invoiceToMonth = date("Y-m-d", strtotime("01-" . Yii::app()->request->getPost("to_month")));
						$invoiceDueDate = date("Y-m-d", strtotime(Yii::app()->request->getPost("from_date")));
						$invoiceSettingsModel = InvoiceSetting::model()->findByAttributes(['branch_id' => $childData->branch_id]);
						$invoiceFromDate = ChildInvoice::model()->setInvoiceDates($invoiceSettingsModel, date("m", strtotime($invoiceFromMonth)), date("Y", strtotime($invoiceFromMonth)))['invoice_from_date'];
						$invoiceToDate = ChildInvoice::model()->setInvoiceDates($invoiceSettingsModel, date("m", strtotime($invoiceToMonth)), date("Y", strtotime($invoiceToMonth)))['invoice_to_date'];
						$numberOfMonths = ((date("Y", strtotime($invoiceToDate)) - date("Y", strtotime($invoiceFromDate))) * 12) + (date("m", strtotime($invoiceToDate)) - date("m", strtotime($invoiceFromDate)));
						if (strtotime($invoiceToDate) < strtotime($invoiceFromDate))
							throw new Exception("Invoice from date can not be smaller than invoice to date.");
						if (!empty($childData)) {
							//Checking the child start and leave date and accordingly setting the dates of invoice
							if (!empty($childData->start_date) && (strtotime(date("Y-m-d", strtotime($childData->start_date))) > strtotime($invoiceToDate))) {
								throw new Exception("Invoice amount can not be calculated as start date of child - $childData->name is greater than the invoice to date.");
							}
							if (!empty($childData->leave_date) && (strtotime(date("Y-m-d", strtotime($childData->leave_date))) < strtotime($invoiceFromDate))) {
								throw new Exception("Invoice amount can not be calculated as leave date of child - $childData->name is smaller than the invoice from date.");
							}
							if (!empty($childData->start_date) && ((strtotime(date("Y-m-d", strtotime($childData->start_date))) > strtotime($invoiceFromDate)) && (strtotime(date("Y-m-d", strtotime($childData->start_date))) < strtotime($invoiceToDate)))) {
								$invoiceFromDate = date("Y-m-d", strtotime($childData->start_date));
							}
							if (!empty($childData->leave_date) && ((strtotime(date("Y-m-d", strtotime($childData->leave_date))) > strtotime($invoiceFromDate)) && (strtotime(date("Y-m-d", strtotime($childData->leave_date))) < strtotime($invoiceToDate)))) {
								$invoiceToDate = date("Y-m-d", strtotime($childData->leave_date));
							}
							//Checking the child start and leave date ends here
							$monthlyTotalAmount = 0;
							$childId = $childData->id;
							ChildBookings::model()->breakSeries($invoiceFromDate, $invoiceToDate, $childData->branch_id, $childData->id);
							$bookingsModel = ChildBookings::model()->getBookings($invoiceFromDate, $invoiceToDate, $childData->branch_id, $childData->id);
							foreach ($bookingsModel as $bookings) {
								$bookings->included_in_invoice_amount = 1;
								if (!$bookings->save()) {
									throw new Exception("There seems to be some problem marking the bookings as invoiced for Child - $childData->name");
								}
							}
							$weekArray = customFunctions::getWeekBetweenDate($invoiceFromDate, $invoiceToDate);
							if (!customFunctions::checkInvoiceExists($invoiceFromDate, $invoiceToDate, $childId)) {
								foreach ($weekArray as $week) {
									$isIncompleteWeekStart = false;
									$isIncompleteWeekEnd = false;
									$incompleteSessionDays = "";
									$incompleteBookingDays = false;
									$actualWeekStartDate = $week['week_start_date'];
									$actualWeekEndDate = $week['week_end_date'];
									if (strtotime($week['week_start_date']) < strtotime($invoiceFromDate)) {
										$isIncompleteWeekStart = true;
										$week['week_start_date'] = $invoiceFromDate;
									}
									if (strtotime($week['week_end_date']) > strtotime($invoiceToDate)) {
										$isIncompleteWeekEnd = true;
										$week['week_end_date'] = $invoiceToDate;
									}
									$sessionData = customFunctions::getChildSessionDataForInvoicing($childId, $week['week_start_date'], $week['week_end_date'], $actualWeekStartDate, $actualWeekEndDate);
									$branchHoliday = customFunctions::getBranchHolidays($week['week_start_date'], $week['week_end_date']);
									if (!empty($sessionData)) {
										$sessionBooked = array_unique(array_column($sessionData, 'session_type_id'));
										foreach ($sessionBooked as $sessionId) {
											$actualWeekBookingDaysNew = array();
											$actualWeekBookingDays = 0;
											$checkSessionNotEmpty = true;
											$total_hours = 0;
											$total_days = 0;
											$total_booking_days = array();
											$session_data = array();
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
											foreach ($sessionData as $key => $value) {
												if ($value['session_type_id'] == $sessionId) {
													array_push($current_session_data, $sessionData[$key]);
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
														$session_data[$day][] = $value['start_time'] . "-" . $value['finish_time'];
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
														$session_data[$day][] = $value['start_time'] . "-" . $value['finish_time'];
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
											$invoiceDetailsModel->total_hours = $total_hours;
											$invoiceDetailsModel->session_id = $sessionId;
											$invoiceDetailsModel->total_days = count($session_data);
											$invoiceDetailsModel->funded_hours = 0;
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
												$age = customFunctions::getAge(date("Y-m-d", strtotime($childData->dob)), date("Y-m-d", strtotime("-1 day", strtotime($invoiceFromDate))));
											}
											if ($sessionRateModel->is_multiple_rates == 1 && $sessionRateModel->multiple_rates_type == 1) {
												$calculated_rate = $this->actionGetMultipleRate($age, $sessionId, $total_hours_multiple_rates, $invoiceDetailsModel->funded_hours, $actualWeekStartDate, $actualWeekEndDate);
												if ($calculated_rate == FALSE) {
													$invoiceDetailsModel->rate = 0;
												} else {
													$invoiceDetailsModel->rate = $this->actionGetMultipleRate($age, $sessionId, $total_hours_multiple_rates, $invoiceDetailsModel->funded_hours, $actualWeekStartDate, $actualWeekEndDate);
												}
											} else if ($sessionRateModel->is_multiple_rates == 1 && $sessionRateModel->multiple_rates_type == 2) {
												$calculated_rate = $this->actionGetMultipleRatesWeekdays($age, $sessionId, $total_booking_days, $actualWeekStartDate, $actualWeekEndDate);
												if ($calculated_rate == FALSE) {
													$invoiceDetailsModel->rate = 0;
												} else {
													$invoiceDetailsModel->rate = $calculated_rate;
												}
											} else if ($sessionRateModel->is_multiple_rates == 1 && $sessionRateModel->multiple_rates_type == 3) {
												$calculated_rate = $this->actionGetMultipleRatesTotalWeekdays($age, $sessionId, $actualWeekBookingDays, $actualWeekStartDate, $actualWeekEndDate);
												$invoiceDetailsModel->rate = $calculated_rate;
												if ($calculated_rate == FALSE) {
													$invoiceDetailsModel->rate = 0;
												}
											} else {
												$invoiceDetailsModel->rate = customFunctions::getRateForInvoicing($childId, $sessionId, $total_hours, $actualWeekStartDate, $actualWeekEndDate);
											}
											$invoiceDetailsModel->discount = $childData->discount;
											if ($checkSessionNotEmpty) {
												$monthlyTotalAmount += customFunctions::getInvoiceAmountForMonthlyInvoice($invoiceDetailsModel);
											}
										}
									}
								}
								ChildPersonalDetails::model()->updateByPk($childData->id, [
									'monthly_invoice_amount' => customFunctions::round(($monthlyTotalAmount / ($numberOfMonths + 1)), 2),
									'monthly_invoice_start_date' => $invoiceFromDate,
									'monthly_invoice_finish_date' => $invoiceToDate
								]);
								$transaction->commit();
								$log[] = "Monthly Amount has been calculated successfully for child " . $childData->name;
							} else {
								throw new Exception("Invoice already exists for this month for child $childData->name");
							}
						} else {
							throw new Exception("Child - $childData->name does not exists in the nursery.");
						}
					} catch (Exception $ex) {
						$transaction->rollback();
						$log[] = $ex->getMessage();
					}
				}
				echo CJSON::encode(['status' => 1, 'message' => $log]);
			} else {
				echo CJSON::encode(array('status' => 0, 'message' => 'No child could be found on the system.'));
				Yii::app()->end();
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	/*
	 * Action to regenrate single invoice.
	 */

	public function actionRegenerateInvoice($child_id, $invoice_id) {
		$oldTransactions = ChildInvoiceTransactions::model()->findAllByAttributes(array(
			'invoice_id' => $invoice_id));
		$oldPaymentTransactions = PaymentsTransactions::model()->findAllByAttributes(array(
			'invoice_id' => $invoice_id));
		$childModal = ChildPersonalDetails::model()->findByPk($child_id);
		$invoiceModal = ChildInvoice::model()->findByPk($invoice_id);
		if ($invoiceModal->is_locked == 1) {
			Yii::app()->user->setFlash('error', 'Invoice can not be regenrated as invoice is locked.');
			$this->redirect(array('view', 'child_id' => $invoiceModal->child_id, 'invoice_id' => $invoiceModal->id));
		}
		if (!$invoiceModal->checkRegenerateAllowed($invoiceModal->id)) {
			Yii::app()->user->setFlash('error', 'Invoice can not be regenrated as invoice for next month is already generated.');
			$this->redirect(array('view', 'child_id' => $invoiceModal->child_id, 'invoice_id' => $invoiceModal->id));
		}
		if ($invoiceModal->is_regenrated == 0) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$fundingTransactions = ChildFundingTransactions::model()->findAllByAttributes(array(
					'invoice_id' => $invoice_id));
				foreach ($fundingTransactions as $fundingTransaction) {
					$fundingTransaction->funded_hours_used = NULL;
					$fundingTransaction->invoice_id = NULL;
					if (!$fundingTransaction->save()) {
						throw new Exception("There seems to be some problem unallocating the funding of previous invoice.");
					}
				}
				$invoiceModal->is_regenrated = 1;
				if ($invoiceModal->save()) {
					$childBookings = ChildBookings::model()->findAllByAttributes(array('invoice_id' => $invoiceModal->id,
						'is_invoiced' => 1));
					if (!empty($childBookings)) {
						foreach ($childBookings as $bookings) {
							$bookings->is_invoiced = 0;
							$bookings->invoice_id = NULL;
							if (!$bookings->save(false)) {
								throw new Exception("There seems to be some problem removing the invoice link from previous invoice.");
							}
						}
					}
					if (!empty($childModal) && !empty($invoiceModal)) {
						$invoiceFromDate = $invoiceModal->from_date;
						$invoiceToDate = $invoiceModal->to_date;
						$invoiceDueDate = $invoiceModal->due_date;
						$actualInvoiceFromDate = $invoiceModal->from_date;
						$actualInvoiceToDate = $invoiceModal->to_date;
						//Checking the child start and leave date and accordingly setting the dates of invoice
						if (!empty($childModal->start_date) && (strtotime(date("Y-m-d", strtotime($childModal->start_date))) > strtotime($invoiceToDate))) {
							throw new Exception("Invoice not generated with child id $childModal->id as start date of child is greater than the invoice to date.");
						}
						if (!empty($childModal->leave_date) && (strtotime(date("Y-m-d", strtotime($childModal->leave_date))) < strtotime($invoiceFromDate))) {
							throw new Exception("Invoice not generated with child id $childModal->id as leave date of child is smaller than the invoice from date.");
						}
						if (!empty($childModal->start_date) && ((strtotime(date("Y-m-d", strtotime($childModal->start_date))) > strtotime($invoiceFromDate)) && (strtotime(date("Y-m-d", strtotime($childModal->start_date))) < strtotime($invoiceToDate)))) {
							$invoiceFromDate = date("Y-m-d", strtotime($childModal->start_date));
						}
						if (!empty($childModal->leave_date) && ((strtotime(date("Y-m-d", strtotime($childModal->leave_date))) > strtotime($invoiceFromDate)) && (strtotime(date("Y-m-d", strtotime($childModal->leave_date))) < strtotime($invoiceToDate)))) {
							$invoiceToDate = date("Y-m-d", strtotime($childModal->leave_date));
						}
						//Checking the child start and leave date ends here.
						$childId = $childModal->id;
						$weekArray = customFunctions::getWeekBetweenDate($invoiceFromDate, $invoiceToDate);
						/** Block for Including last month uninvoiced sessions starts here* */
						if (Branch::currentBranch()->include_last_month_uninvoiced_sessions == 1) {
							if ((int) $invoiceModal->month == 1) {
								$lastYear = $invoiceModal->year - 1;
								$lastMonth = 12;
							} else {
								$lastYear = $invoiceModal->year;
								$lastMonth = ((int) $invoiceModal->month - 1);
							}
							$lastMonthDates = ChildInvoice::model()->setInvoiceDates(InvoiceSetting::model()->findByAttributes(array(
									'branch_id' => $invoiceModal->branch_id)), $lastMonth, $lastYear);
							$lastMonthFromDate = $lastMonthDates['invoice_from_date'];
							$lastMonthToDate = $lastMonthDates['invoice_to_date'];
							if (!empty($childModal->start_date) && (strtotime(date("Y-m-d", strtotime($childModal->start_date))) < strtotime($invoiceFromDate))) {
								if (!empty($childModal->start_date) && ((strtotime(date("Y-m-d", strtotime($childModal->start_date))) > strtotime($lastMonthFromDate)) && (strtotime(date("Y-m-d", strtotime($childModal->start_date))) < strtotime($lastMonthToDate)))) {
									$lastMonthFromDate = date("Y-m-d", strtotime($childModal->start_date));
								}
								if (!empty($childModal->leave_date) && ((strtotime(date("Y-m-d", strtotime($childModal->leave_date))) > strtotime($lastMonthFromDate)) && (strtotime(date("Y-m-d", strtotime($childModal->leave_date))) < strtotime($lastMonthToDate)))) {
									$lastMonthToDate = date("Y-m-d", strtotime($childModal->leave_date));
								}
								$lastMonthWeeks = customFunctions::getWeekBetweenDate($lastMonthFromDate, $lastMonthToDate, 1);
								$weekArray = array_merge($weekArray, $lastMonthWeeks);
								$lastMonthBookingsModel = ChildBookings::model()->getBookings($lastMonthFromDate, $lastMonthToDate, $childModal->branch_id, $childModal->id, NULL, NULL, NULL, 0);
								ChildBookings::model()->breakSeries($lastMonthFromDate, $lastMonthToDate, $childModal->branch_id, $childModal->id, $lastMonthBookingsModel);
							}
						}
						/** Block for Including last month uninvoiced sessions end here* */
						$bookingsModel = ChildBookings::model()->getBookings($invoiceFromDate, $invoiceToDate, $childModal->branch_id, $childModal->id, NULL, NULL, NULL, NULL);
						ChildBookings::model()->breakSeries($invoiceFromDate, $invoiceToDate, $childModal->branch_id, $childModal->id, $bookingsModel);
						$urn = customFunctions::getInvoiceUrn($invoiceModal->branch_id);
						$model = new ChildInvoice;
						$model->child_id = $childId;
						$model->branch_id = $invoiceModal->branch_id;
						$model->urn_prefix = $invoiceModal->urn_prefix;
						$model->urn_number = $invoiceModal->urn_number;
						$model->urn_suffix = $invoiceModal->urn_suffix;
						$model->from_date = $actualInvoiceFromDate;
						$model->to_date = $actualInvoiceToDate;
						$model->status = 'AWAITING_PAYMENT';
						$model->due_date = $invoiceDueDate;
						$model->invoice_date = $invoiceModal->invoice_date;
						$model->invoice_type = ChildInvoice::AUTOMATIC_INVOICE;
						$model->month = $invoiceModal->month;
						$model->year = $invoiceModal->year;
						$model->access_token = md5(time() . uniqid() . $model->id . $model->child_id);
						if ($model->save()) {
							foreach ($bookingsModel as $booking) {
								$booking->invoice_id = $model->id;
								if (!$booking->save(false)) {
									throw new Exception("There seems to be some problem updating the invoice id for child - $childModal->name");
								}
							}
							foreach ($weekArray as $week) {
								$isIncompleteWeekStart = false;
								$isIncompleteWeekEnd = false;
								$incompleteSessionDays = "";
								$incompleteBookingDays = false;
								$actualWeekStartDate = $week['week_start_date'];
								$actualWeekEndDate = $week['week_end_date'];
								if (strtotime($week['week_start_date']) < strtotime($invoiceFromDate) && $week['is_last_month'] == 0) {
									$isIncompleteWeekStart = true;
									$week['week_start_date'] = $invoiceFromDate;
								}
								if (strtotime($week['week_end_date']) > strtotime($invoiceToDate) && $week['is_last_month'] == 0) {
									$isIncompleteWeekEnd = true;
									$week['week_end_date'] = $invoiceToDate;
								}
								if ($week['is_last_month'] == 1 && strtotime($week['week_start_date']) < strtotime($lastMonthFromDate)) {
									$isIncompleteWeekStart = true;
									$week['week_start_date'] = $lastMonthFromDate;
								}
								if (strtotime($week['week_end_date']) > strtotime($lastMonthToDate) && $week['is_last_month'] == 1) {
									$isIncompleteWeekEnd = true;
									$week['week_end_date'] = $lastMonthToDate;
								}
								$sessionData = customFunctions::getChildSessionDataForInvoicing($childId, $week['week_start_date'], $week['week_end_date'], $actualWeekStartDate, $actualWeekEndDate, $childModal->branch->funding_allocation_type, NULL, $week['is_last_month']);
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
										$actualWeekBookingDays = 0;
										$actualWeekBookingDaysNew = array();
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
											$invoiceDetailsModel->funded_hours = customFunctions::getFundedHours($model->child_id, $week['week_start_date'], $week['week_end_date'], $actualWeekBookingDays, $model->id, $checkSessionNotEmpty, $total_hours, $total_booking_days, $invoiceDetailsModel->session_id, 1);
										}

										if (Branch::currentBranch()->is_exclude_funding == 1) {
											if ($excludeFunding[$sessionId] == 0) {
												$invoiceDetailsModel->funded_hours = customFunctions::getFundedHours($model->child_id, $week['week_start_date'], $week['week_end_date'], $actualWeekBookingDays, $model->id, $checkSessionNotEmpty, $total_hours, $total_booking_days, $invoiceDetailsModel->session_id, 1);
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
										if ($childModal->branch->change_session_rate == 0) {
											$age = customFunctions::getAge(date("Y-m-d", strtotime($childModal->dob)), date("Y-m-d", strtotime("-1 day", strtotime($actualWeekStartDate))));
										} else {
											$age = customFunctions::getAge(date("Y-m-d", strtotime($childModal->dob)), date("Y-m-d", strtotime("-1 day", strtotime($model->from_date))));
										}
										if ($sessionRateModel->is_multiple_rates == 1 && $sessionRateModel->multiple_rates_type == 1) {
											$calculated_rate = $this->actionGetMultipleRate($age, $sessionId, $total_hours_multiple_rates, $invoiceDetailsModel->funded_hours, $actualWeekStartDate, $actualWeekEndDate);
											if ($calculated_rate == FALSE) {
												$invoiceDetailsModel->rate = 0;
											} else {
												$invoiceDetailsModel->rate = $this->actionGetMultipleRate($age, $sessionId, $total_hours_multiple_rates, $invoiceDetailsModel->funded_hours, $actualWeekStartDate, $actualWeekEndDate);
											}
										} else if ($sessionRateModel->is_multiple_rates == 1 && $sessionRateModel->multiple_rates_type == 2) {
											$calculated_rate = $this->actionGetMultipleRatesWeekdays($age, $sessionId, $total_booking_days, $actualWeekStartDate, $actualWeekEndDate);
											if ($calculated_rate == FALSE) {
												$invoiceDetailsModel->rate = 0;
											} else {
												$invoiceDetailsModel->rate = $calculated_rate;
											}
										} else if ($sessionRateModel->is_multiple_rates == 1 && $sessionRateModel->multiple_rates_type == 3) {
											$calculated_rate = $this->actionGetMultipleRatesTotalWeekdays($age, $sessionId, $actualWeekBookingDays, $actualWeekStartDate, $actualWeekEndDate);
											$invoiceDetailsModel->rate = $calculated_rate;
											if ($calculated_rate == FALSE) {
												$invoiceDetailsModel->rate = 0;
											}
										} else {
											$invoiceDetailsModel->rate = customFunctions::getRateForInvoicing($childId, $sessionId, $total_hours, $actualWeekStartDate, $actualWeekEndDate);
										}
										$invoiceDetailsModel->discount = $childModal->discount;

										if (Branch::currentBranch()->is_exclude_funding == 0) {
											$invoiceDetailsModel->funded_rate = customFunctions::getFundedAmount($invoiceDetailsModel, $childModal, $actualWeekStartDate);
											$invoiceDetailsModel->average_rate = customFunctions::getAverageRate($invoiceDetailsModel, $childModal, $actualWeekStartDate);
										}

										if (Branch::currentBranch()->is_exclude_funding == 1) {
											if ($excludeFunding[$sessionId] == 1) {
												$invoiceDetailsModel->exclude_funding = 1;
											}
											$invoiceDetailsModel->funded_rate = customFunctions::getFundedAmount($invoiceDetailsModel, $childModal, $actualWeekStartDate);
											$invoiceDetailsModel->average_rate = customFunctions::getAverageRate($invoiceDetailsModel, $childModal, $actualWeekStartDate);
										}
										if ($checkSessionNotEmpty) {
											$invoiceDetailsModel->save();
											if (!$invoiceDetailsModel->save()) {
												throw new Exception(CHtml::errorSummary($invoiceDetailsModel, "", "", array(
													'class' => 'customErrors')));
											}
										}
										$sessionTotalHours += $total_hours_multiple_rates;
										if (Branch::currentBranch()->is_exclude_funding == 1) {
											$excludeFunding[$sessionId] = 1;
										}
									}
								} else {
									$sessionTotalHours = 0;
								}
								if ($childModal->branch->is_minimum_booking_rate_enabled == 1 && $week['is_last_month'] == 0) {
									/** Block for minimum booking fees starts here* */
									$invoiceDetailsModel = new ChildInvoiceDetails;
									$sessionRatesMappingModel = new SessionRateMapping;
									$childAge = round(abs(strtotime(date("Y-m-d", strtotime("-1 day", strtotime($actualWeekStartDate)))) - strtotime(date("Y-m-d", strtotime($childModal->dob)))) / (365 * 60 * 60 * 24), 2);
									$invoiceDetailsArray = $sessionRatesMappingModel->getMinimumBookingRate($model->id, $childModal, $sessionTotalHours, $week['week_start_date'], $week['week_end_date'], $childAge);
									if ($invoiceDetailsArray !== false) {
										$invoiceDetailsModel->attributes = $invoiceDetailsArray;
										if (!$invoiceDetailsModel->save())
											throw new Exception("Problem occured saving the minimum booking fee for child - " . $childId);
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
							customFunctions::getInvoiceAmount($model->id);
							$totalAmount = ChildInvoice::model()->findByPk($model->id)->total;
							/** Adding additional Items* */
							$additionalAmount = 0;
							$additionalItemsModal = ChildInvoiceDetails::model()->findAllByAttributes([
								'invoice_id' => $invoice_id, 'is_extras' => 1]);
							foreach ($additionalItemsModal as $additionalItem) {
								$invoiceDetailsAdditionalItemsModel = new ChildInvoiceDetails;
								$invoiceDetailsAdditionalItemsModel->attributes = $additionalItem->attributes;
								$invoiceDetailsAdditionalItemsModel->invoice_id = $model->id;
								$invoiceDetailsAdditionalItemsModel->save();
								$product_array = CJSON::decode($additionalItem->products_data);
								foreach ($product_array AS $deleted_product_array) {
									$additionalAmount += round(sprintf("%0.2f", ($deleted_product_array[5] - ($deleted_product_array[4] * 0.01 * $deleted_product_array[5]))), 2);
								}
							}
							$model->total = $totalAmount + floatval(sprintf("%0.2f", $additionalAmount));
							if (!$model->save()) {
								throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
							}
							foreach ($bookingsModel as $booking) {
								$booking->is_invoiced = 1;
								$booking->invoice_id = $model->id;
								if (!$booking->save()) {
									throw new Exception("There seems to be some problem marking the sessions as invoiced for child - $childData->name");
								}
							}
							if (Branch::currentBranch()->include_last_month_uninvoiced_sessions == 1) {
								if (!empty($lastMonthBookingsModel)) {
									foreach ($lastMonthBookingsModel as $lastMonthBooking) {
										$lastMonthBooking->invoice_id = $model->id;
										$lastMonthBooking->is_invoiced = 1;
										if (!$lastMonthBooking->save()) {
											throw new Exception("Their seems to be some problem updating the invoice id for child - $childData->name");
										}
									}
								}
							}
							if (empty($oldTransactions)) {
								$transaction->commit();
								Yii::app()->user->setFlash('invoiceRegenerateSuccess', "Invoice has been successfully re-generated.");
								$this->redirect(array('childInvoice/view', 'child_id' => $childId, 'invoice_id' => $model->id));
							} else {
								$oldInvoiceAmount = $invoiceModal->total;
								$newInvoiceAmount = ChildInvoice::model()->findByPk($model->id)->total;
								if ($newInvoiceAmount > $oldInvoiceAmount) {
									foreach ($oldTransactions as $oldInvoiceTransaction) {
										$oldInvoiceTransaction->invoice_id = $model->id;
										$oldInvoiceTransaction->invoice_amount = $newInvoiceAmount;
										if (!$oldInvoiceTransaction->save()) {
											throw new Exception(CHtml::errorSummary($oldInvoiceTransaction, "", "", array(
												'class' => 'customErrors')));
										}
									}
									foreach ($oldPaymentTransactions as $payments) {
										$payments->invoice_id = $model->id;
										if (!$payments->save()) {
											throw new Exception(CHtml::errorSummary($payments, "", "", array('class' => 'customErrors')));
										}
									}
									$checkCreditNote = ChildInvoice::model()->findAllByAttributes(array('credit_note_invoice_id' => $invoiceModal->id));
									if (!empty($checkCreditNote)) {
										foreach ($checkCreditNote as $creditNote) {
											$creditNote->credit_note_invoice_id = $model->id;
											if (!$creditNote->save()) {
												throw new Exception(CHtml::errorSummary($creditNote, "", "", array('class' => 'customErrors')));
											}
										}
									}
									$invoiceDueAmount = floatval(customFunctions::getDueAmount($model->id));
									$model->status = ($invoiceDueAmount == 0) ? 'PAID' : 'AWAITING_PAYMENT';
									if (!$model->save()) {
										throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
									}
									$transaction->commit();
									Yii::app()->user->setFlash('invoiceRegenerateSuccess', "Invoice has been successfully re-generated.");
									$this->redirect(array('childInvoice/view', 'child_id' => $childId, 'invoice_id' => $model->id));
								} else if ($newInvoiceAmount == $oldInvoiceAmount) {
									foreach ($oldTransactions as $oldInvoiceTransaction) {
										$oldInvoiceTransaction->invoice_id = $model->id;
										$oldInvoiceTransaction->invoice_amount = $newInvoiceAmount;
										if (!$oldInvoiceTransaction->save()) {
											throw new Exception(CHtml::errorSummary($oldInvoiceTransaction, "", "", array(
												'class' => 'customErrors')));
										}
									}
									foreach ($oldPaymentTransactions as $payments) {
										$payments->invoice_id = $model->id;
										if (!$payments->save()) {
											throw new Exception(CHtml::errorSummary($payments, "", "", array('class' => 'customErrors')));
										}
									}
									$checkCreditNote = ChildInvoice::model()->findAllByAttributes(array('credit_note_invoice_id' => $invoiceModal->id));
									if (!empty($checkCreditNote)) {
										foreach ($checkCreditNote as $creditNote) {
											$creditNote->credit_note_invoice_id = $model->id;
											if (!$creditNote->save()) {
												throw new Exception(CHtml::errorSummary($creditNote, "", "", array('class' => 'customErrors')));
											}
										}
									}
									$invoiceDueAmount = floatval(customFunctions::getDueAmount($model->id));
									$model->status = ($invoiceDueAmount == 0) ? 'PAID' : 'AWAITING_PAYMENT';
									if (!$model->save()) {
										throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
									}
									$transaction->commit();
									Yii::app()->user->setFlash('invoiceRegenerateSuccess', "Invoice has been successfully re-generated.");
									$this->redirect(array('childInvoice/view', 'child_id' => $childId, 'invoice_id' => $model->id));
								} else {
									$total_payments_for_old_invoice = 0;
									foreach ($oldTransactions as $oldInvoiceTransaction) {
										$total_payments_for_old_invoice += $oldInvoiceTransaction->paid_amount;
									}
									$total_payments_for_old_invoice = floatval(sprintf("%0.2f", $total_payments_for_old_invoice));
									if ($newInvoiceAmount >= $total_payments_for_old_invoice) {
										foreach ($oldTransactions as $oldInvoiceTransaction) {
											$oldInvoiceTransaction->invoice_id = $model->id;
											$oldInvoiceTransaction->invoice_amount = $newInvoiceAmount;
											if (!$oldInvoiceTransaction->save()) {
												throw new Exception(CHtml::errorSummary($oldInvoiceTransaction, "", "", array(
													'class' => 'customErrors')));
											}
										}
										foreach ($oldPaymentTransactions as $payments) {
											$payments->invoice_id = $model->id;
											if (!$payments->save()) {
												throw new Exception(CHtml::errorSummary($payments, "", "", array('class' => 'customErrors')));
											}
										}
										$checkCreditNote = ChildInvoice::model()->findAllByAttributes(array('credit_note_invoice_id' => $invoiceModal->id));
										if (!empty($checkCreditNote)) {
											foreach ($checkCreditNote as $creditNote) {
												$creditNote->credit_note_invoice_id = $model->id;
												if (!$creditNote->save()) {
													throw new Exception(CHtml::errorSummary($creditNote, "", "", array(
														'class' => 'customErrors')));
												}
											}
										}
										$invoiceDueAmount = floatval(customFunctions::getDueAmount($model->id));
										$model->status = ($invoiceDueAmount == 0) ? 'PAID' : 'AWAITING_PAYMENT';
										if (!$model->save()) {
											throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
										}
										$transaction->commit();
										Yii::app()->user->setFlash('invoiceRegenerateSuccess', "Invoice has been successfully re-generated.");
										$this->redirect(array('childInvoice/view', 'child_id' => $childId, 'invoice_id' => $model->id));
									} else {
										throw new Exception("Invoice can not be regenerated as new Invoice amount- (" . $newInvoiceAmount . ") is less than the payments added. Please remove payments and try again.");
									}
								}
							}
						} else {
							throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
						}
					} else {
						throw new Exception("Respective child or invoice does not exists on the system.");
					}
				} else {
					throw new Exception(CHtml::errorSummary($invoiceModal, "", "", array('class' => 'customErrors')));
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				Yii::app()->user->setFlash("invoiceRegenerateErrro", $ex->getMessage());
				$this->redirect(array('view', 'child_id' => $invoiceModal->child_id, 'invoice_id' => $invoiceModal->id));
			}
		} else {
			Yii::app()->user->setFlash('invoiceRegenerateErrro', "Already regenerated invoice can not be regenerated again.");
			$this->refresh();
		}
	}

	/*
	 * Action to regenerate all the invoices.
	 */

	public function actionRegenerateInvoices() {
    ini_set('max_execution_time' , -1);
		if (Yii::app()->request->isAjaxRequest) {
			$model = new RegenerateInvoicesForm;
			$model->attributes = $_POST['RegenerateInvoicesForm'];
			$model->is_all_child = $_POST['is_all_child'];
			if ($model->validate()) {
				if ($model->is_all_child == 1) {
					$childModel = ChildPersonalDetails::model()->findAllByAttributes(array('branch_id' => Branch::currentBranch()->id));
				} else {
					$criteria = new CDbCriteria();
					$criteria->addInCondition('id', $model->child_id);
					$childModel = ChildPersonalDetails::model()->findAll($criteria);
				}
				$log = [];
				if (!empty($childModel)) {
					foreach ($childModel as $childModal) {
						$log[] = "****----------------------------------------------------------------*****";
						$log[] = "Started regenrating invoice for child - " . $childModal->name;
						$invoiceModal = ChildInvoice::model()->findByAttributes([
							'month' => $model->month,
							'year' => $model->year,
							'child_id' => $childModal->id,
							'invoice_type' => ChildInvoice::AUTOMATIC_INVOICE,
							'is_regenrated' => 0
						]);
						if (empty($invoiceModal)) {
							$log[] = "No invoice found for child - " . $childModal->name;
							$log[] = "****----------------------------------------------------------------*****";
							continue;
						}
						if ($invoiceModal->is_locked == 1) {
							$log[] = "Invoice can not be regenrated as it is locked for child - " . $childModal->name;
							$log[] = "****----------------------------------------------------------------*****";
							continue;
						}
						if (!$invoiceModal->checkRegenerateAllowed($invoiceModal->id)) {
							$log[] = "Invoice can not be regenrated as invoice for next month is already generated for child - " . $childModal->name;
							$log[] = "****----------------------------------------------------------------*****";
							continue;
						}
						if ($invoiceModal->is_regenrated == 0) {
							$invoice_id = $invoiceModal->id;
							$oldTransactions = ChildInvoiceTransactions::model()->findAllByAttributes(array(
								'invoice_id' => $invoice_id));
							$oldPaymentTransactions = PaymentsTransactions::model()->findAllByAttributes(array(
								'invoice_id' => $invoice_id));
							$transaction = Yii::app()->db->beginTransaction();
							try {
								$fundingTransactions = ChildFundingTransactions::model()->findAllByAttributes(array(
									'invoice_id' => $invoiceModal->id));
								foreach ($fundingTransactions as $fundingTransaction) {
									$fundingTransaction->funded_hours_used = NULL;
									$fundingTransaction->invoice_id = NULL;
									if (!$fundingTransaction->save()) {
										throw new Exception("There seems to be some problem unallocating the funding of previous invoice");
									}
								}
								$invoiceModal->is_regenrated = 1;
								if ($invoiceModal->save()) {
									$childBookings = ChildBookings::model()->findAllByAttributes(array('invoice_id' => $invoiceModal->id,
										'is_invoiced' => 1));
									if (!empty($childBookings)) {
										foreach ($childBookings as $bookings) {
											$bookings->is_invoiced = 0;
											$bookings->invoice_id = NULL;
											if (!$bookings->save(false)) {
												throw new Exception("There seems to be some problem removing the invoice link from previous invoice");
											}
										}
									}
									if (!empty($childModal) && !empty($invoiceModal)) {
										$invoiceFromDate = $invoiceModal->from_date;
										$invoiceToDate = $invoiceModal->to_date;
										$invoiceDueDate = $invoiceModal->due_date;
										$actualInvoiceFromDate = $invoiceModal->from_date;
										$actualInvoiceToDate = $invoiceModal->to_date;
										//Checking the child start and leave date and accordingly setting the dates of invoice
										if (!empty($childModal->start_date) && (strtotime(date("Y-m-d", strtotime($childModal->start_date))) > strtotime($invoiceToDate))) {
											throw new Exception("Invoice not regenerated as start date of child is greater than the invoice to date");
										}
										if (!empty($childModal->leave_date) && (strtotime(date("Y-m-d", strtotime($childModal->leave_date))) < strtotime($invoiceFromDate))) {
											throw new Exception("Invoice not regenerated as leave date of child is smaller than the invoice from date");
										}
										if (!empty($childModal->start_date) && ((strtotime(date("Y-m-d", strtotime($childModal->start_date))) > strtotime($invoiceFromDate)) && (strtotime(date("Y-m-d", strtotime($childModal->start_date))) < strtotime($invoiceToDate)))) {
											$invoiceFromDate = date("Y-m-d", strtotime($childModal->start_date));
										}
										if (!empty($childModal->leave_date) && ((strtotime(date("Y-m-d", strtotime($childModal->leave_date))) > strtotime($invoiceFromDate)) && (strtotime(date("Y-m-d", strtotime($childModal->leave_date))) < strtotime($invoiceToDate)))) {
											$invoiceToDate = date("Y-m-d", strtotime($childModal->leave_date));
										}
										//Checking the child start and leave date ends here.
										$childId = $childModal->id;
										$weekArray = customFunctions::getWeekBetweenDate($invoiceFromDate, $invoiceToDate);
										/** Block for Including last month uninvoiced sessions starts here* */
										if (Branch::currentBranch()->include_last_month_uninvoiced_sessions == 1) {
											if ((int) $invoiceModal->month == 1) {
												$lastYear = $invoiceModal->year - 1;
												$lastMonth = 12;
											} else {
												$lastYear = $invoiceModal->year;
												$lastMonth = ((int) $invoiceModal->month - 1);
											}
											$lastMonthDates = ChildInvoice::model()->setInvoiceDates(InvoiceSetting::model()->findByAttributes(array(
													'branch_id' => $invoiceModal->branch_id)), $lastMonth, $lastYear);
											$lastMonthFromDate = $lastMonthDates['invoice_from_date'];
											$lastMonthToDate = $lastMonthDates['invoice_to_date'];
											if (!empty($childModal->start_date) && (strtotime(date("Y-m-d", strtotime($childModal->start_date))) < strtotime($invoiceFromDate))) {
												if (!empty($childModal->start_date) && ((strtotime(date("Y-m-d", strtotime($childModal->start_date))) > strtotime($lastMonthFromDate)) && (strtotime(date("Y-m-d", strtotime($childModal->start_date))) < strtotime($lastMonthToDate)))) {
													$lastMonthFromDate = date("Y-m-d", strtotime($childModal->start_date));
												}
												if (!empty($childModal->leave_date) && ((strtotime(date("Y-m-d", strtotime($childModal->leave_date))) > strtotime($lastMonthFromDate)) && (strtotime(date("Y-m-d", strtotime($childModal->leave_date))) < strtotime($lastMonthToDate)))) {
													$lastMonthToDate = date("Y-m-d", strtotime($childModal->leave_date));
												}
												$lastMonthWeeks = customFunctions::getWeekBetweenDate($lastMonthFromDate, $lastMonthToDate, 1);
												$weekArray = array_merge($weekArray, $lastMonthWeeks);
												$lastMonthBookingsModel = ChildBookings::model()->getBookings($lastMonthFromDate, $lastMonthToDate, $childModal->branch_id, $childModal->id, NULL, NULL, NULL, 0);
												ChildBookings::model()->breakSeries($lastMonthFromDate, $lastMonthToDate, $childModal->branch_id, $childModal->id, $lastMonthBookingsModel);
											}
										}
										/** Block for Including last month uninvoiced sessions end here* */
										$bookingsModel = ChildBookings::model()->getBookings($invoiceFromDate, $invoiceToDate, $childModal->branch_id, $childModal->id, NULL, NULL, NULL, NULL);
										ChildBookings::model()->breakSeries($invoiceFromDate, $invoiceToDate, $childModal->branch_id, $childModal->id, $bookingsModel);
										$urn = customFunctions::getInvoiceUrn($invoiceModal->branch_id);
										$model = new ChildInvoice;
										$model->child_id = $childId;
										$model->branch_id = $invoiceModal->branch_id;
										$model->urn_prefix = $invoiceModal->urn_prefix;
										$model->urn_number = $invoiceModal->urn_number;
										$model->urn_suffix = $invoiceModal->urn_suffix;
										$model->from_date = $actualInvoiceFromDate;
										$model->to_date = $actualInvoiceToDate;
										$model->status = 'AWAITING_PAYMENT';
										$model->due_date = $invoiceDueDate;
										$model->invoice_date = $invoiceModal->invoice_date;
										$model->invoice_type = ChildInvoice::AUTOMATIC_INVOICE;
										$model->month = $invoiceModal->month;
										$model->year = $invoiceModal->year;
										$model->access_token = md5(time() . uniqid() . $model->id . $model->child_id);
										if ($model->save()) {
											foreach ($bookingsModel as $booking) {
												$booking->invoice_id = $model->id;
												if (!$booking->save()) {
													throw new Exception("Seems some problem stamping the sessions with invoice id");
												}
											}
											foreach ($weekArray as $week) {
												$isIncompleteWeekStart = false;
												$isIncompleteWeekEnd = false;
												$incompleteSessionDays = "";
												$incompleteBookingDays = false;
												$actualWeekStartDate = $week['week_start_date'];
												$actualWeekEndDate = $week['week_end_date'];
												if (strtotime($week['week_start_date']) < strtotime($invoiceFromDate) && $week['is_last_month'] == 0) {
													$isIncompleteWeekStart = true;
													$week['week_start_date'] = $invoiceFromDate;
												}
												if (strtotime($week['week_end_date']) > strtotime($invoiceToDate) && $week['is_last_month'] == 0) {
													$isIncompleteWeekEnd = true;
													$week['week_end_date'] = $invoiceToDate;
												}
												if ($week['is_last_month'] == 1 && strtotime($week['week_start_date']) < strtotime($lastMonthFromDate)) {
													$isIncompleteWeekStart = true;
													$week['week_start_date'] = $lastMonthFromDate;
												}
												if (strtotime($week['week_end_date']) > strtotime($lastMonthToDate) && $week['is_last_month'] == 1) {
													$isIncompleteWeekEnd = true;
													$week['week_end_date'] = $lastMonthToDate;
												}
												$sessionData = customFunctions::getChildSessionDataForInvoicing($childId, $week['week_start_date'], $week['week_end_date'], $actualWeekStartDate, $actualWeekEndDate, $childModal->branch->funding_allocation_type, NULL, $week['is_last_month']);
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
														$actualWeekBookingDays = 0;
														$actualWeekBookingDaysNew = array();
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
															$invoiceDetailsModel->funded_hours = customFunctions::getFundedHours($model->child_id, $week['week_start_date'], $week['week_end_date'], $actualWeekBookingDays, $model->id, $checkSessionNotEmpty, $total_hours, $total_booking_days, $invoiceDetailsModel->session_id, 1);
														}

														if (Branch::currentBranch()->is_exclude_funding == 1) {
															if ($excludeFunding[$sessionId] == 0) {
																$invoiceDetailsModel->funded_hours = customFunctions::getFundedHours($model->child_id, $week['week_start_date'], $week['week_end_date'], $actualWeekBookingDays, $model->id, $checkSessionNotEmpty, $total_hours, $total_booking_days, $invoiceDetailsModel->session_id, 1);
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
														if ($childModal->branch->change_session_rate == 0) {
															$age = customFunctions::getAge(date("Y-m-d", strtotime($childModal->dob)), date("Y-m-d", strtotime("-1 day", strtotime($actualWeekStartDate))));
														} else {
															$age = customFunctions::getAge(date("Y-m-d", strtotime($childModal->dob)), date("Y-m-d", strtotime("-1 day", strtotime($model->from_date))));
														}
														if ($sessionRateModel->is_multiple_rates == 1 && $sessionRateModel->multiple_rates_type == 1) {
															$calculated_rate = $this->actionGetMultipleRate($age, $sessionId, $total_hours_multiple_rates, $invoiceDetailsModel->funded_hours, $actualWeekStartDate, $actualWeekEndDate);
															if ($calculated_rate == FALSE) {
																$invoiceDetailsModel->rate = 0;
															} else {
																$invoiceDetailsModel->rate = $this->actionGetMultipleRate($age, $sessionId, $total_hours_multiple_rates, $invoiceDetailsModel->funded_hours, $actualWeekStartDate, $actualWeekEndDate);
															}
														} else if ($sessionRateModel->is_multiple_rates == 1 && $sessionRateModel->multiple_rates_type == 2) {
															$calculated_rate = $this->actionGetMultipleRatesWeekdays($age, $sessionId, $total_booking_days, $actualWeekStartDate, $actualWeekEndDate);
															if ($calculated_rate == FALSE) {
																$invoiceDetailsModel->rate = 0;
															} else {
																$invoiceDetailsModel->rate = $calculated_rate;
															}
														} else if ($sessionRateModel->is_multiple_rates == 1 && $sessionRateModel->multiple_rates_type == 3) {
															$calculated_rate = $this->actionGetMultipleRatesTotalWeekdays($age, $sessionId, $actualWeekBookingDays, $actualWeekStartDate, $actualWeekEndDate);
															$invoiceDetailsModel->rate = $calculated_rate;
															if ($calculated_rate == FALSE) {
																$invoiceDetailsModel->rate = 0;
															}
														} else {
															$invoiceDetailsModel->rate = customFunctions::getRateForInvoicing($childId, $sessionId, $total_hours, $actualWeekStartDate, $actualWeekEndDate);
														}
														$invoiceDetailsModel->discount = $childModal->discount;

														if (Branch::currentBranch()->is_exclude_funding == 0) {
															$invoiceDetailsModel->funded_rate = customFunctions::getFundedAmount($invoiceDetailsModel, $childModal, $actualWeekStartDate);
															$invoiceDetailsModel->average_rate = customFunctions::getAverageRate($invoiceDetailsModel, $childModal, $actualWeekStartDate);
														}

														if (Branch::currentBranch()->is_exclude_funding == 1) {
															if ($excludeFunding[$sessionId] == 1) {
																$invoiceDetailsModel->exclude_funding = 1;
															}
															$invoiceDetailsModel->funded_rate = customFunctions::getFundedAmount($invoiceDetailsModel, $childModal, $actualWeekStartDate);
															$invoiceDetailsModel->average_rate = customFunctions::getAverageRate($invoiceDetailsModel, $childModal, $actualWeekStartDate);
														}
														if ($checkSessionNotEmpty) {
															$invoiceDetailsModel->save();
															if (!$invoiceDetailsModel->save()) {
																throw new Exception(CHtml::errorSummary($invoiceDetailsModel, "", "", array(
																	'class' => 'customErrors')));
															}
														}
														$sessionTotalHours += $total_hours_multiple_rates;
														if (Branch::currentBranch()->is_exclude_funding == 1) {
															$excludeFunding[$sessionId] = 1;
														}
													}
												} else {
													$sessionTotalHours = 0;
												}
												if ($childModal->branch->is_minimum_booking_rate_enabled == 1 && $week['is_last_month'] == 0) {
													/** Block for minimum booking fees starts here* */
													$invoiceDetailsModel = new ChildInvoiceDetails;
													$sessionRatesMappingModel = new SessionRateMapping;
													$childAge = round(abs(strtotime(date("Y-m-d", strtotime("-1 day", strtotime($actualWeekStartDate)))) - strtotime(date("Y-m-d", strtotime($childModal->dob)))) / (365 * 60 * 60 * 24), 2);
													$invoiceDetailsArray = $sessionRatesMappingModel->getMinimumBookingRate($model->id, $childModal, $sessionTotalHours, $week['week_start_date'], $week['week_end_date'], $childAge);
													if ($invoiceDetailsArray !== false) {
														$invoiceDetailsModel->attributes = $invoiceDetailsArray;
														if (!$invoiceDetailsModel->save())
															throw new Exception("Seems some problem saving the minimum booking fee");
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
											customFunctions::getInvoiceAmount($model->id);
											$totalAmount = ChildInvoice::model()->findByPk($model->id)->total;
											/** Adding additional Items* */
											$additionalAmount = 0;
											$additionalItemsModal = ChildInvoiceDetails::model()->findAllByAttributes([
												'invoice_id' => $invoice_id, 'is_extras' => 1]);
											foreach ($additionalItemsModal as $additionalItem) {
												$invoiceDetailsAdditionalItemsModel = new ChildInvoiceDetails;
												$invoiceDetailsAdditionalItemsModel->attributes = $additionalItem->attributes;
												$invoiceDetailsAdditionalItemsModel->invoice_id = $model->id;
												$invoiceDetailsAdditionalItemsModel->save();
												$product_array = CJSON::decode($additionalItem->products_data);
												foreach ($product_array AS $deleted_product_array) {
													$additionalAmount += round(sprintf("%0.2f", ($deleted_product_array[5] - ($deleted_product_array[4] * 0.01 * $deleted_product_array[5]))), 2);
												}
											}
											$model->total = $totalAmount + floatval(sprintf("%0.2f", $additionalAmount));
											if (!$model->save()) {
												throw new Exception("Seems some problem saving the invoice amount");
											}
											foreach ($bookingsModel as $booking) {
												$booking->is_invoiced = 1;
												$booking->invoice_id = $model->id;
												if (!$booking->save()) {
													throw new Exception("Seems some problem stamping the sessions with invoice id");
												}
											}
											if (Branch::currentBranch()->include_last_month_uninvoiced_sessions == 1) {
												if (!empty($lastMonthBookingsModel)) {
													foreach ($lastMonthBookingsModel as $lastMonthBooking) {
														$lastMonthBooking->invoice_id = $model->id;
														$lastMonthBooking->is_invoiced = 1;
														if (!$lastMonthBooking->save()) {
															throw new Exception("Seems some problem stamping the sessions with invoice id");
														}
													}
												}
											}
											if (empty($oldTransactions)) {
												$transaction->commit();
												$log[] = "Invoice successfuly regenrated";
												$log[] = "****----------------------------------------------------------------*****";
											} else {
												$oldInvoiceAmount = $invoiceModal->total;
												$newInvoiceAmount = ChildInvoice::model()->findByPk($model->id)->total;
												if ($newInvoiceAmount > $oldInvoiceAmount) {
													foreach ($oldTransactions as $oldInvoiceTransaction) {
														$oldInvoiceTransaction->invoice_id = $model->id;
														$oldInvoiceTransaction->invoice_amount = $newInvoiceAmount;
														if (!$oldInvoiceTransaction->save()) {
															throw new Exception(CHtml::errorSummary($oldInvoiceTransaction, "", "", array(
																'class' => 'customErrors')));
														}
													}
													foreach ($oldPaymentTransactions as $payments) {
														$payments->invoice_id = $model->id;
														if (!$payments->save()) {
															throw new Exception(CHtml::errorSummary($payments, "", "", array('class' => 'customErrors')));
														}
													}
													$checkCreditNote = ChildInvoice::model()->findAllByAttributes(array('credit_note_invoice_id' => $invoiceModal->id));
													if (!empty($checkCreditNote)) {
														foreach ($checkCreditNote as $creditNote) {
															$creditNote->credit_note_invoice_id = $model->id;
															if (!$creditNote->save()) {
																throw new Exception(CHtml::errorSummary($creditNote, "", "", array('class' => 'customErrors')));
															}
														}
													}
													$invoiceDueAmount = floatval(customFunctions::getDueAmount($model->id));
													$model->status = ($invoiceDueAmount == 0) ? 'PAID' : 'AWAITING_PAYMENT';
													if (!$model->save()) {
														throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
													}
													$transaction->commit();
													$log[] = "Invoice successfuly regenrated";
													$log[] = "****----------------------------------------------------------------*****";
												} else if ($newInvoiceAmount == $oldInvoiceAmount) {
													foreach ($oldTransactions as $oldInvoiceTransaction) {
														$oldInvoiceTransaction->invoice_id = $model->id;
														$oldInvoiceTransaction->invoice_amount = $newInvoiceAmount;
														if (!$oldInvoiceTransaction->save()) {
															throw new Exception(CHtml::errorSummary($oldInvoiceTransaction, "", "", array(
																'class' => 'customErrors')));
														}
													}
													foreach ($oldPaymentTransactions as $payments) {
														$payments->invoice_id = $model->id;
														if (!$payments->save()) {
															throw new Exception(CHtml::errorSummary($payments, "", "", array('class' => 'customErrors')));
														}
													}
													$checkCreditNote = ChildInvoice::model()->findAllByAttributes(array('credit_note_invoice_id' => $invoiceModal->id));
													if (!empty($checkCreditNote)) {
														foreach ($checkCreditNote as $creditNote) {
															$creditNote->credit_note_invoice_id = $model->id;
															if (!$creditNote->save()) {
																throw new Exception(CHtml::errorSummary($creditNote, "", "", array('class' => 'customErrors')));
															}
														}
													}
													$invoiceDueAmount = floatval(customFunctions::getDueAmount($model->id));
													$model->status = ($invoiceDueAmount == 0) ? 'PAID' : 'AWAITING_PAYMENT';
													if (!$model->save()) {
														throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
													}
													$transaction->commit();
													$log[] = "Invoice successfuly regenrated";
													$log[] = "****----------------------------------------------------------------*****";
												} else {
													$total_payments_for_old_invoice = 0;
													foreach ($oldTransactions as $oldInvoiceTransaction) {
														$total_payments_for_old_invoice += $oldInvoiceTransaction->paid_amount;
													}
													$total_payments_for_old_invoice = floatval(sprintf("%0.2f", $total_payments_for_old_invoice));
													if ($newInvoiceAmount >= $total_payments_for_old_invoice) {
														foreach ($oldTransactions as $oldInvoiceTransaction) {
															$oldInvoiceTransaction->invoice_id = $model->id;
															$oldInvoiceTransaction->invoice_amount = $newInvoiceAmount;
															if (!$oldInvoiceTransaction->save()) {
																throw new Exception(CHtml::errorSummary($oldInvoiceTransaction, "", "", array(
																	'class' => 'customErrors')));
															}
														}
														foreach ($oldPaymentTransactions as $payments) {
															$payments->invoice_id = $model->id;
															if (!$payments->save()) {
																throw new Exception(CHtml::errorSummary($payments, "", "", array('class' => 'customErrors')));
															}
														}
														$checkCreditNote = ChildInvoice::model()->findAllByAttributes(array('credit_note_invoice_id' => $invoiceModal->id));
														if (!empty($checkCreditNote)) {
															foreach ($checkCreditNote as $creditNote) {
																$creditNote->credit_note_invoice_id = $model->id;
																if (!$creditNote->save()) {
																	throw new Exception(CHtml::errorSummary($creditNote, "", "", array(
																		'class' => 'customErrors')));
																}
															}
														}
														$invoiceDueAmount = floatval(customFunctions::getDueAmount($model->id));
														$model->status = ($invoiceDueAmount == 0) ? 'PAID' : 'AWAITING_PAYMENT';
														if (!$model->save()) {
															throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
														}
														$transaction->commit();
														$log[] = "Invoice successfuly regenrated";
														$log[] = "****----------------------------------------------------------------*****";
													} else {
														throw new Exception("Invoice can not be regenerated as new Invoice amount- (" . $newInvoiceAmount . ") is less than the payments added. Please remove payments and try again.");
													}
												}
											}
										} else {
											throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
										}
									} else {
										throw new Exception("Respective child or invoice does not exists on the system.");
									}
								} else {
									throw new Exception(CHtml::errorSummary($invoiceModal, "", "", array('class' => 'customErrors')));
								}
							} catch (Exception $ex) {
								$transaction->rollback();
								$log[] = $ex->getMessage() . " for child " . $invoiceModal->child->name . " - Invoice No. -" . $invoiceModal->invoiceUrn;
								$log[] = "****----------------------------------------------------------------*****";
							}
						} else {
							$log[] = "Invoice can not be regenrated as it was alrady regenrated for child - " . $childModal->name;
							$log[] = "****----------------------------------------------------------------*****";
							continue;
						}
					}
				}
				echo CJSON::encode(['status' => 1, 'message' => $log]);
				Yii::app()->end();
			} else {
				echo CJSON::encode(['status' => 0, 'message' => $model->getErrors()]);
				Yii::app()->end();
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionGetInvoicePayments() {
		if (isset($_POST) && !empty($_POST)) {
			$criteria = new CDbCriteria();
			$criteria->select = 'invoice_amount,invoice_id,child_id,sum(paid_amount) AS amount_paid';
			$criteria->condition = "invoice_id = :invoice_id";
			$criteria->params = array(':invoice_id' => $_POST['invoice_id']);
			if (ChildInvoiceTransactions::model()->exists($criteria)) {
				$result = ChildInvoiceTransactions::model()->find($criteria);
				$row = array(
					'invoice_amount' => $result->invoice_amount,
					'amount_paid' => $result->amount_paid,
					'invoice_id' => $result->invoice_id,
					'child_id' => $result->child_id,
					'flag' => 1,
				);
				echo CJSON::encode($row);
			} else {
				$result = ChildInvoice::model()->findByPk($_POST['invoice_id']);
				$row = array(
					'id' => $result->id,
					'child_id' => $result->child_id,
					'total_amount' => $result->total_amount,
					'status' => $result->status,
					'flag' => 0
				);
				echo CJSON::encode($row);
			}
		}
	}

	public function actionGeneratePdf($child_id, $invoice_id) {
		if (isset($_GET['child_id']) && isset($_GET['invoice_id'])) {
			$day_array = array(
				0 => 'Sun',
				1 => 'Mon',
				2 => 'Tue',
				3 => 'Wed',
				4 => 'Thu',
				5 => 'Fri',
				6 => 'Sat'
			);
			$model = $this->loadModel($invoice_id);
			$childModel = ChildPersonalDetails::model()->findByPk($child_id);
			$parentModel = ChildPersonalDetails::model()->findByPk($child_id)->getFirstBillPayer();
			$invoiceDetailsModel = ChildInvoiceDetails::model()->findAllByAttributes(array(
				'invoice_id' => $model->id, 'is_extras' => 0));
			$invoiceExtrasModel = ChildInvoiceDetails::model()->findAllByAttributes(array(
				'invoice_id' => $model->id, 'is_extras' => 1));
			$temp = array();
			foreach ($invoiceDetailsModel as $invoiceDetails) {
				array_push($temp, $invoiceDetails->attributes);
			}
			$weekStartDates = array_unique(array_column($temp, 'week_start_date'));
			$invoiceDetailsArray = array();
			foreach ($weekStartDates as $key => $value) {
				foreach ($invoiceDetailsModel as $invoiceDetails) {
					$columns = $invoiceDetails->attributes;
					if ($columns['week_start_date'] == $value) {
						$invoiceDetailsArray[$value . ":" . $columns['week_end_date']][] = $columns;
					}
				}
			}
			$invoiceSettingsModel = InvoiceSetting::model()->findByAttributes(array('branch_id' => Yii::app()->session['branch_id']));
			if (!empty($invoiceSettingsModel)) {
				$invoiceParams = array();
				$companyModel = Company::model()->findByPk(Yii::app()->session['company_id']);
				$invoiceParams['footer_text'] = customFunctions::getFooterTextForInvoicePdf($invoiceSettingsModel->invoice_pdf_footer_text, $childModel->branch_id, $companyModel->id);
				$invoiceParams['registration_number'] = $companyModel->registration_number;
				$invoiceParams['vat_number'] = $companyModel->vat_number;
				$invoiceParams['company_name'] = $companyModel->name;
				$invoiceParams['header_color'] = $invoiceSettingsModel->invoice_header_color;
				if ($invoiceSettingsModel->invoice_logo == "Company") {
					$invoiceParams['website'] = $companyModel->website;
					$invoiceParams['email'] = $companyModel->email;
					$invoiceParams['phone'] = $companyModel->telephone;
				} else {
					$branchModel = Branch::model()->findByPk(Yii::app()->session['branch_id']);
					$invoiceParams['website'] = $branchModel->website_link;
					$invoiceParams['email'] = $branchModel->email;
					$invoiceParams['phone'] = $branchModel->phone;
				}
			}
			$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 45, 30, 2.5, 1, 'P');
			$mpdf->WriteHTML($this->renderPartial('invoicePdf', array('model' => $model, 'childModel' => $childModel,
					'parentModel' => $parentModel, 'invoiceDetailsModel' => $invoiceDetailsModel,
					'invoiceParams' => $invoiceParams, 'day_array' => $day_array, 'invoiceDetailsArray' => $invoiceDetailsArray,
					'invoiceSettingsModel' => $invoiceSettingsModel, 'invoiceExtrasModel' => $invoiceExtrasModel), TRUE));
			$mpdf->Output('Invoice_' . $model->child->first_name . " " . $model->child->middle_name . " " . $model->child->last_name . "_" . $model->from_date . '-' . $model->to_date . '.pdf', "D");
			exit();
		} else {
			throw new CHttpException(404, 'Your request is not valid');
		}
	}

	public function actionAutomaticInvoicePdfAttachment($child_id, $invoice_id) {
		$day_array = array(
			0 => 'Sun',
			1 => 'Mon',
			2 => 'Tue',
			3 => 'Wed',
			4 => 'Thu',
			5 => 'Fri',
			6 => 'Sat'
		);
		$model = $this->loadModel($invoice_id);
		$childModel = ChildPersonalDetails::model()->findByPk($child_id);
		$parentModel = $childModel->getFirstBillPayer();
		$invoiceDetailsModel = ChildInvoiceDetails::model()->findAllByAttributes(array(
			'invoice_id' => $model->id, 'is_extras' => 0));
		$invoiceExtrasModel = ChildInvoiceDetails::model()->findAllByAttributes(array(
			'invoice_id' => $model->id, 'is_extras' => 1));
		$temp = array();
		foreach ($invoiceDetailsModel as $invoiceDetails) {
			array_push($temp, $invoiceDetails->attributes);
		}
		$weekStartDates = array_unique(array_column($temp, 'week_start_date'));
		$invoiceDetailsArray = array();
		foreach ($weekStartDates as $key => $value) {
			foreach ($invoiceDetailsModel as $invoiceDetails) {
				$columns = $invoiceDetails->attributes;
				if ($columns['week_start_date'] == $value) {
					$invoiceDetailsArray[$value . ":" . $columns['week_end_date']][] = $columns;
				}
			}
		}
		$invoiceSettingsModel = InvoiceSetting::model()->findByAttributes(array('branch_id' => Yii::app()->session['branch_id']));
		if (!empty($invoiceSettingsModel)) {
			$invoiceParams = array();
			$companyModel = Company::model()->findByPk(Yii::app()->session['company_id']);
			$invoiceParams['footer_text'] = customFunctions::getFooterTextForInvoicePdf($invoiceSettingsModel->invoice_pdf_footer_text, $childModel->branch_id, $companyModel->id);
			$invoiceParams['registration_number'] = $companyModel->registration_number;
			$invoiceParams['vat_number'] = $companyModel->vat_number;
			$invoiceParams['company_name'] = $companyModel->name;
			$invoiceParams['header_color'] = $invoiceSettingsModel->invoice_header_color;
			if ($invoiceSettingsModel->invoice_logo == "Company") {
				$invoiceParams['website'] = $companyModel->website;
				$invoiceParams['email'] = $companyModel->email;
				$invoiceParams['phone'] = $companyModel->telephone;
			} else {
				$branchModel = Branch::model()->findByPk(Yii::app()->session['branch_id']);
				$invoiceParams['website'] = $branchModel->website_link;
				$invoiceParams['email'] = $branchModel->email;
				$invoiceParams['phone'] = $branchModel->phone;
			}
		}
		$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 45, 30, 2.5, 1, 'P');
		$mpdf->WriteHTML($this->renderPartial('invoicePdf', array('model' => $model, 'childModel' => $childModel,
				'parentModel' => $parentModel, 'invoiceDetailsModel' => $invoiceDetailsModel,
				'invoiceParams' => $invoiceParams, 'day_array' => $day_array, 'invoiceDetailsArray' => $invoiceDetailsArray,
				'invoiceSettingsModel' => $invoiceSettingsModel, 'invoiceExtrasModel' => $invoiceExtrasModel), TRUE));
		return $mpdf->Output('', "S");
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id) {
		$this->loadModel($id)->delete();

// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
		if (!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex($child_id) {
		$this->pageTitle = 'Child Invoices | eyMan';
		$model = new ChildInvoice('search');
		$model->unsetAttributes();
		$model->child_id = $child_id;
		if (isset($_GET['ChildInvoice'])) {
			$model->attributes = $_GET['ChildInvoice'];
		}
		$balanceStatementModel = new ChildBalanceStatementForm();
        $generateInvoicesModel = new GenerateInvoicesForm;
		$this->render('index', array(
			'model' => $model,
			'balanceStatementModel' => $balanceStatementModel,
            'generateInvoicesModel' => $generateInvoicesModel
		));
	}

	public function actionInvoiceList() {
		$this->pageTitle = 'Child Invoices | eyMan';
		$model = new ChildInvoice('invoiceList');
		$model->unsetAttributes();
		if (isset($_GET['ChildInvoice'])) {
			$model->attributes = $_GET['ChildInvoice'];
			$model->month = $_GET['ChildInvoice']['month'];
			$model->year = $_GET['ChildInvoice']['year'];
		}
		$this->render('invoiceList', array(
			'model' => $model,
		));
		;
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin() {
		$model = new ChildInvoice('search');
		$model->unsetAttributes(); // clear any default values
		if (isset($_GET['ChildInvoice']))
			$model->attributes = $_GET['ChildInvoice'];

		$this->render('admin', array(
			'model' => $model,
		));
	}

	public function loadModel($id) {
		$model = ChildInvoice::model()->findByPk($id);
		if ($model === null)
			throw new CHttpException(404, 'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param ChildInvoice $model the model to be validated
	 */
	protected function performAjaxValidation($model) {
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'child-invoice-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	public function actionSendInvoiceEmail($invoice_id) {
		$invoiceSettings = Branch::currentBranch()->invoiceSettings;
		if (!empty($invoiceSettings)) {
			$invoiceModel = ChildInvoice::model()->findByPk($invoice_id);
			if (!empty($invoiceModel)) {
				try {
					$parentalDetailsModel = $invoiceModel->childNds->getBillPayers();
					if ($parentalDetailsModel) {
						$recipients = array();
						if (isset($parentalDetailsModel[1]) && !empty($parentalDetailsModel[1]->email)) {
							$recipients['p1'] = [
								'email' => $parentalDetailsModel[1]->email,
								'name' => $parentalDetailsModel[1]->Fullname,
								'type' => 'to'
							];
						}
						if (isset($parentalDetailsModel[2]) && !empty($parentalDetailsModel[2]->email)) {
							$recipients['p2'] = [
								'email' => $parentalDetailsModel[2]->email,
								'name' => $parentalDetailsModel[2]->Fullname,
								'type' => 'to'
							];
						}
						if (!empty($recipients)) {
							if ($invoiceModel->invoice_type == ChildInvoice::AUTOMATIC_INVOICE) {
								$attachment = [
									'type' => 'application/pdf',
									'name' => md5(uniqid()) . ".pdf",
									'content' => base64_encode($this->actionAutomaticInvoicePdfAttachment($invoiceModel->child_id, $invoiceModel->id))
								];
							} else {
								$attachment = [
									'type' => 'application/pdf',
									'name' => md5(uniqid()) . ".pdf",
									'content' => base64_encode($this->actionManualInvoicePdfAttachment($invoiceModel->child_id, $invoiceModel->id))
								];
							}
							foreach ($recipients as $parent_number => $recipient) {
								$subject = customFunctions::getInvoiceEmailSuject($invoiceModel, $invoiceSettings, $recipient['name']);
								$content = customFunctions::getInvoiceEmailBody($invoiceModel, $invoiceSettings, $recipient['name']);
								$metadata = [
									'rcpt' => $recipient['email'],
									'values' => ['invoice_id' => $invoiceModel->id, 'parent_type' => $parent_number, 'company' => $invoiceModel->branch->company->name]
								];
								$mandrill = new EymanMandril($subject, $content, $invoiceModel->branch->company->name, [$recipient], $invoiceSettings->from_email, [$attachment], [$metadata]);
								$response = $mandrill->sendEmail();
								if ($parent_number == "p1") {
									if (!empty($response)) {
										foreach ($response as $email) {
											if (EymanMandril::getEmailStatus($email['status']) == 0) {
												Yii::app()->user->setFlash('p1_error', 'Seeems some problem sending email to : ' . $email['email']);
											} else {
												Yii::app()->user->setFlash('p1_success', 'Email has been successfully ' . $email['status'] . ' to ' . $email['email']);
											}
											ChildInvoice::model()->updateByPk($invoiceModel->id, ['is_email_sent' => EymanMandril::getEmailStatus($email['status']), 'email_1_mandrill_id' => $email['_id']]);
										}
									}
								}
								if ($parent_number == "p2") {
									if (!empty($response)) {
										foreach ($response as $email) {
											if (EymanMandril::getEmailStatus($email['status']) == 0) {
												Yii::app()->user->setFlash('p2_error', 'Seeems some problem sending email to : ' . $email['email']);
											} else {
												Yii::app()->user->setFlash('p2_success', 'Email has been successfully ' . $email['status'] . ' to ' . $email['email']);
											}
											ChildInvoice::model()->updateByPk($invoiceModel->id, ['is_email_sent_2' => EymanMandril::getEmailStatus($email['status']), 'email_2_mandrill_id' => $email['_id']]);
										}
									}
								}
							}
							if ($invoiceModel->invoice_type == ChildInvoice::AUTOMATIC_INVOICE)
								$this->redirect(array('childInvoice/view', 'child_id' => $invoiceModel->child_id,
									'invoice_id' => $invoiceModel->id));
							else
								$this->redirect(array('childInvoice/viewManualInvoice', 'child_id' => $invoiceModel->child_id,
									'invoice_id' => $invoiceModel->id));
						} else {
							throw new Exception("Please check that email is present of the parent or one of the parent is marked as bill payer.");
						}
					} else {
						throw new Exception("Email can not be send as parental details are empty for the Child.");
					}
				} catch (Mandrill_Error $e) {
					Yii::app()->user->setFlash('error', 'Seems some problem sending email from mandrill : ' . get_class($e));
					if ($invoiceModel->invoice_type == ChildInvoice::AUTOMATIC_INVOICE)
						$this->redirect(array('childInvoice/view', 'child_id' => $invoiceModel->child_id,
							'invoice_id' => $invoiceModel->id));
					else
						$this->redirect(array('childInvoice/viewManualInvoice', 'child_id' => $invoiceModel->child_id,
							'invoice_id' => $invoiceModel->id));
				} catch (Exception $ex) {
					Yii::app()->user->setFlash('error', $ex->getMessage());
					if ($invoiceModel->invoice_type == ChildInvoice::AUTOMATIC_INVOICE)
						$this->redirect(array('childInvoice/view', 'child_id' => $invoiceModel->child_id,
							'invoice_id' => $invoiceModel->id));
					else
						$this->redirect(array('childInvoice/viewManualInvoice', 'child_id' => $invoiceModel->child_id,
							'invoice_id' => $invoiceModel->id));
				}
			}else {
				throw new CHttpException(404, 'Invoice does not exists.');
			}
		} else {
			$this->refresh();
		}
	}

	public function actionGetMultipleRate($age, $session_id, $booking_hours, $funded_hours, $weekStartDate, $weekEndDate) {
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

	public function actionGetMultipleRatesWeekdays($age, $session_id, $total_booking_days, $weekStartDate, $weekEndDate) {
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

	public function actionCheckMonthlyRateExists($age, $session_id) {
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
			if (!empty($mappingModel->rate_monthly) && ($mappingModel->rate_monthly != NULL) && ($mappingModel->rate_monthly != 0)) {
				return $mappingModel->rate_monthly;
			}
		} else {
			return false;
		}
	}

	public function actionGetMultipleRatesTotalWeekdays($age, $session_id, $total_booking_days, $weekStartDate, $weekEndDate) {
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

	public function actionCreateManualInvoice($child_id) {
		$this->layout = "dashboard";
		$model = new ChildInvoice;
		$invoiceDetails = new ChildInvoiceDetails;
		$parentModel = ChildPersonalDetails::model()->findByPk($child_id)->getFirstBillPayer();
		$childModel = ChildPersonalDetails::model()->findByPk($child_id);
		if (isset($_POST['ChildInvoice']) && isset($_POST['ChildInvoiceDetails'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model->attributes = $_POST['ChildInvoice'];
				$model->branch_id = Yii::app()->session['branch_id'];
				$model->child_id = $child_id;
				$model->status = ChildInvoice::PENDING_PAYMENT;
				$model->invoice_type = 1;
				$urn = customFunctions::getInvoiceUrn(Yii::app()->session['branch_id']);
				$model->urn_prefix = $urn['prefix'];
				$model->urn_number = $urn['number'];
				$model->urn_suffix = $urn['suffix'];
				$model->access_token = md5(time() . uniqid() . $model->id . $model->child_id);
				$model->total = $_POST['total_amount'];
				$model->year = date("Y", strtotime($model->invoice_date));
				$model->month = date("m", strtotime($model->invoice_date));
				if ($model->save()) {
					$invoiceDetails = new ChildInvoiceDetails;
					$invoiceDetails->invoice_id = $model->id;
					$invoiceDetails->products_data = customFunctions::getProductsDataForManuaInvoice($_POST['ChildInvoiceDetails']['product_id'], $_POST['ChildInvoiceDetails']['product_description'], $_POST['ChildInvoiceDetails']['product_quantity'], $_POST['ChildInvoiceDetails']['product_price'], $_POST['ChildInvoiceDetails']['discount'], $_POST['ChildInvoiceDetails']['amount'], $_POST['ChildInvoiceDetails']['type']);
					$invoiceDetails->discount = $childModel->discount;
					if (!$invoiceDetails->save()) {
						throw new Exception("There seems to be some problem saving the manual Invoice.");
					}
					$sessionData = CJSON::decode($_POST['manual_invoice_session_data']);
					if (!empty($sessionData) && is_array($sessionData)) {
						foreach ($sessionData as $key => $value) {
							$booking_id = explode("_", $key)[0];
							$result = ChildBookings::model()->updateByPk($booking_id, [
								'is_invoiced' => 1,
								'invoice_id' => $model->id
							]);
							if (!$result) {
								throw new Exception("There seems to be some problem saving the manual Invoice.");
							}
						}
					}
					$transaction->commit();
					$this->redirect(array('childInvoice/index', 'child_id' => $child_id));
				} else {
					throw new Exception("Theri seems to be some problem saving the manual Invoice.");
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				Yii::app()->user->setFlash('error', $ex->getMessage());
				$this->refresh();
			}
		}
		$this->render('createManualInvoice', array(
			'model' => $model,
			'invoiceDetails' => $invoiceDetails,
			'parentModel' => $parentModel,
			'childModel' => $childModel
		));
	}

	public function actionGetProductDetails() {
		if (Yii::app()->request->isAjaxRequest) {
			$response = array('status' => 0, 'message' => 'There seems to be some problem');
			if (isset($_POST['invoice_from_date']) && !empty($_POST['invoice_from_date'])) {
				Products::$as_of = date("Y-m-d", strtotime($_POST['invoice_from_date']));
			}
			if (isset($_POST['invoice_date']) && !empty($_POST['invoice_date'])) {
				Products::$as_of = date("Y-m-d", strtotime($_POST['invoice_date']));
			}
			$productModel = Products::model()->findByPk($_POST['product_id']);
			if (!empty($productModel)) {
				echo CJSON::encode($productModel);
			} else {
				echo CJSON::encode($response);
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid');
		}
	}

	public function actionGetProducts() {
		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1)) {
			$response = array('status' => 0, 'message' => 'There seems to be some problem');
			$productModel = Products::model()->findAllByAttributes(array('branch_id' => Yii::app()->session['branch_id'], 'is_modified' => 0));
			if (!empty($productModel)) {
				echo CJSON::encode($productModel);
			} else {
				echo CJSON::encode($response);
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid');
		}
	}

	public function actionGetChildDiscount() {
		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1)) {
			$response = array('status' => 0, 'message' => 'There seems to be some problem');
			$childPersonalDetails = ChildPersonalDetails::model()->findByPk($_POST['child_id']);
			if (!empty($childPersonalDetails)) {
				echo CJSON::encode(array('discount' => $childPersonalDetails->discount));
			} else {
				echo CJSON::encode($response);
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid');
		}
	}

	public function actionViewManualInvoice($child_id, $invoice_id) {
		$this->layout = "dashboard";
		$invoiceOverpaymentModal = new ChildInvoice;
		$creditNoteTransactionModal = new ChildInvoiceTransactions;
		$model = ChildInvoice::model()->findByPk($invoice_id);
		if (empty($model)) {
			throw new CHttpException(404, 'This page does not exists');
		}
		$invoiceDetails = $model->childInvoiceDetail;
		$childModel = ChildPersonalDetails::model()->findByPk($child_id);
		$parentModel = ChildPersonalDetails::model()->findByPk($child_id)->getFirstBillPayer();
		$invoiceTransactionModel = new ChildInvoiceTransactions;
		$oldTransactions = new CActiveDataProvider('ChildInvoiceTransactionsNds', array(
			'criteria' => array(
				'condition' => 'invoice_id = :invoice_id',
				'order' => 'id DESC',
				'params' => array(':invoice_id' => $invoice_id)
			),
			'pagination' => array(
				'pageSize' => 20,
			),
		));
		$creditNotes = $this->actionGetCreditNotes($child_id);
		if (empty($oldTransactions)) {
			$disabled = false;
		} else {
			$disabled = true;
		}
		$invoiceData = array();
		if (!empty($invoiceDetails->products_data)) {
			foreach (CJSON::decode($invoiceDetails->products_data) as $key => $value) {
				if (isset($value[6]) && !empty($value[6])) {
					$temp = array();
					if ($value[6] == 0) {
						$productModel = Products::model()->findByPk($value[0]);
						$temp['item'] = $productModel->id;
						$temp['type'] = 0;
					} else {
						$temp['item'] = $value[0];
						$temp['type'] = $value[6];
					}
					$temp['description'] = $value[1];
					$temp['quantity'] = $value[2];
					$temp['price'] = sprintf('%0.2f', $value[3]);
					$temp['discount'] = $value[4];
					$temp['amount'] = sprintf('%0.2f', $value[5]);
					array_push($invoiceData, $temp);
				} else {
					$temp = array();
					$productModel = Products::model()->findByPk($value[0]);
					$temp['item'] = $productModel->id;
					$temp['description'] = $value[1];
					$temp['quantity'] = $value[2];
					$temp['price'] = sprintf('%0.2f', $value[3]);
					$temp['discount'] = $value[4];
					$temp['amount'] = sprintf('%0.2f', $value[5]);
					$temp['type'] = 0;
					array_push($invoiceData, $temp);
				}
			}
		}
		if (isset($_POST['ChildInvoiceTransactions']) && isset($_POST['pay_invoice'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$invoiceModel = ChildInvoice::model()->findByPk($_POST['ChildInvoiceTransactions']['invoice_id']);
				$paid_amount = customFunctions::getPaidAmount($invoiceModel->id);
				if (!empty($invoiceModel)) {
					$invoiceTransactionModel->attributes = $_POST['ChildInvoiceTransactions'];
					$invoiceTransactionModel->invoice_amount = $invoiceModel->total;
					if (($invoiceTransactionModel->paid_amount + $paid_amount) < $invoiceModel->total) {
						$invoiceModel->status = 'AWAITING_PAYMENT';
					} else {
						$invoiceModel->status = 'PAID';
					}
					$paymentModel = new Payments;
					$paymentModel->attributes = $invoiceModel->attributes;
					$paymentModel->date_of_payment = $invoiceTransactionModel->date_of_payment;
					$paymentModel->payment_mode = $invoiceTransactionModel->payment_mode;
					$paymentModel->payment_reference = $invoiceTransactionModel->payment_refrence;
					$paymentModel->amount = $invoiceTransactionModel->paid_amount;
					$paymentModel->status = 1;
					$paymentModel->child_id = $invoiceModel->child_id;
					if ($paymentModel->save()) {
						$paymentTransaction = new PaymentsTransactions;
						$paymentTransaction->invoice_id = $invoiceModel->id;
						$paymentTransaction->payment_id = $paymentModel->id;
						$paymentTransaction->paid_amount = $invoiceTransactionModel->paid_amount;
						if ($paymentTransaction->save()) {
							$invoiceTransactionModel->payment_id = $paymentTransaction->id;
							if ($invoiceTransactionModel->save()) {
								if ($invoiceModel->save()) {
									if ($invoiceTransactionModel->payment_mode == ChildInvoiceTransactions::PAYMENT_MODE_GOCARDLESS) {
										$invoiceModel->recordGoCardlessPayment(Parents::model()->findByPk($_POST['ChildInvoiceTransactions']['parent_id']), $invoiceTransactionModel, $paymentModel);
									}
									$transaction->commit();
									$this->redirect(array('childInvoice/viewManualInvoice', 'child_id' => $child_id,
										'invoice_id' => $invoice_id));
								} else {
									throw new Exception(CHtml::errorSummary($invoiceModel, "", "", array('class' => 'customErrors')));
								}
							} else {
								throw new Exception(CHtml::errorSummary($invoiceTransactionModel, "", "", array(
									'class' => 'customErrors')));
							}
						} else {
							throw new Exception(CHtml::errorSummary($paymentTransaction, "", "", array(
								'class' => 'customErrors')));
						}
					} else {
						throw new Exception(CHtml::errorSummary($paymentModel, "", "", array('class' => 'customErrors')));
					}
				} else {
					throw new Exception("Invoice you are trying to pay could not be found.");
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				Yii::app()->user->setFlash('error_date_of_payment', $ex->getMessage());
				$invoiceTransactionModel->isNewRecord = true;
				$this->refresh();
			}
		}
		if (isset($_POST['Update'])) {
			$model = $this->loadModel($invoice_id);
			$invoiceDetailsModel = ChildInvoiceDetails::model()->findAllByAttributes(array(
				'invoice_id' => $model->id));
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model->attributes = $_POST['ChildInvoice'];
				$model->branch_id = Yii::app()->session['branch_id'];
				$model->child_id = $child_id;
				$model->status = 'AWAITING_PAYMENT';
				$model->invoice_type = 1;
				$model->access_token = md5(time() . uniqid() . $model->id . $model->child_id);
				$model->total = $_POST['total_amount'];
				if ($model->save()) {
					foreach ($invoiceDetailsModel as $invoiceDetails) {
						$invoiceDetails->delete();
					}
					$invoiceDetails = new ChildInvoiceDetails;
					$invoiceDetails->invoice_id = $model->id;
					$invoiceDetails->products_data = customFunctions::getProductsDataForManuaInvoice($_POST['ChildInvoiceDetails']['product_id'], $_POST['ChildInvoiceDetails']['product_description'], $_POST['ChildInvoiceDetails']['product_quantity'], $_POST['ChildInvoiceDetails']['product_price'], $_POST['ChildInvoiceDetails']['discount'], $_POST['ChildInvoiceDetails']['amount']);
					$invoiceDetails->discount = $childModel->discount;
					$invoiceDetails->save();
					$transaction->commit();
					$this->redirect(array('childInvoice/index', 'child_id' => $child_id));
				}
			} catch (Exception $ex) {
				$transaction->rollback();
			}
		}
		$this->render('viewManualInvoice', array(
			'model' => $model,
			'invoiceDetails' => $invoiceDetails,
			'parentModel' => $parentModel,
			'invoiceData' => $invoiceData,
			'invoiceTransactionModel' => $invoiceTransactionModel,
			'oldTransactions' => $oldTransactions,
			'disabled' => $disabled,
			'invoiceOverpaymentModal' => $invoiceOverpaymentModal,
			'creditNotes' => $creditNotes,
			'creditNoteTransactionModal' => $creditNoteTransactionModal,
			'childModel' => $childModel
		));
	}

	public function actionManualInvoicePdf($child_id, $invoice_id) {
		if (isset($_GET['child_id']) && isset($_GET['invoice_id'])) {
			$model = $this->loadModel($invoice_id);
			$childModel = ChildPersonalDetails::model()->findByPk($child_id);
			$parentModel = $childModel->getFirstBillPayer();
			$invoiceDetailsModel = ChildInvoiceDetails::model()->findByAttributes(array('invoice_id' => $invoice_id));
			$invoiceData = array();
			if (!empty($invoiceDetailsModel->products_data)) {
				foreach (CJSON::decode($invoiceDetailsModel->products_data) as $key => $value) {
					$temp = array();
					if ($value[6] == 0) {
						$productModel = Products::model()->findByPk($value[0]);
						$temp['item'] = $productModel->name;
						$temp['type'] = 0;
					} else {
						$temp['item'] = $value[0];
						$temp['type'] = $value[6];
					}
					$temp['description'] = $value[1];
					$temp['quantity'] = $value[2];
					$temp['price'] = sprintf('%0.2f', $value[3]);
					$temp['discount'] = $value[4];
					$temp['amount'] = sprintf('%0.2f', $value[5]);
					array_push($invoiceData, $temp);
				}
			}
			$invoiceSettingsModel = InvoiceSetting::model()->findByAttributes(array('branch_id' => Yii::app()->session['branch_id']));
			if (!empty($invoiceSettingsModel)) {
				$invoiceParams = array();
				$companyModel = Company::model()->findByPk(Yii::app()->session['company_id']);
				$invoiceParams['footer_text'] = customFunctions::getFooterTextForInvoicePdf($invoiceSettingsModel->invoice_pdf_footer_text, $childModel->branch_id, $companyModel->id);
				$invoiceParams['registration_number'] = $companyModel->registration_number;
				$invoiceParams['vat_number'] = $companyModel->vat_number;
				$invoiceParams['company_name'] = $companyModel->name;
				$invoiceParams['header_color'] = $invoiceSettingsModel->invoice_header_color;
				if ($invoiceSettingsModel->invoice_logo == "Company") {
					$invoiceParams['website'] = $companyModel->website;
					$invoiceParams['email'] = $companyModel->email;
					$invoiceParams['phone'] = $companyModel->telephone;
				} else {
					$branchModel = Branch::model()->findByPk(Yii::app()->session['branch_id']);
					$invoiceParams['website'] = $branchModel->website_link;
					$invoiceParams['email'] = $branchModel->email;
					$invoiceParams['phone'] = $branchModel->phone;
				}
			}
			$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 45, 30, 2.5, 1, 'P');
			$mpdf->WriteHTML($this->renderPartial('manualInvoicePdf', array('model' => $model, 'childModel' => $childModel,
					'parentModel' => $parentModel, 'invoiceData' => $invoiceData, 'invoiceDetailsModel' => $invoiceDetailsModel,
					'invoiceParams' => $invoiceParams, 'invoiceSettingsModel' => $invoiceSettingsModel), TRUE));
			$mpdf->Output('Invoice' . $model->from_date . '-' . $model->to_date . '.pdf', "D");
			exit();
		} else {
			throw new CHttpException(404, 'Your request is not valid');
		}
	}

	public function actionManualInvoicePdfAttachment($child_id, $invoice_id) {
		$model = $this->loadModel($invoice_id);
		$childModel = ChildPersonalDetails::model()->findByPk($child_id);
		$parentModel = $childModel->getFirstBillPayer();
		$invoiceDetailsModel = ChildInvoiceDetails::model()->findByAttributes(array('invoice_id' => $invoice_id));
		$invoiceData = array();
		if (!empty($invoiceDetailsModel->products_data)) {
			foreach (CJSON::decode($invoiceDetailsModel->products_data) as $key => $value) {
				$temp = array();
				$productModel = Products::model()->findByPk($value[0]);
				$temp['item'] = $productModel->name;
				$temp['description'] = $value[1];
				$temp['quantity'] = $value[2];
				$temp['price'] = sprintf('%0.2f', $value[3]);
				$temp['discount'] = $value[4];
				$temp['amount'] = sprintf('%0.2f', $value[5]);
				array_push($invoiceData, $temp);
			}
		}
		$invoiceSettingsModel = InvoiceSetting::model()->findByAttributes(array('branch_id' => Yii::app()->session['branch_id']));
		if (!empty($invoiceSettingsModel)) {
			$invoiceParams = array();
			$companyModel = Company::model()->findByPk(Yii::app()->session['company_id']);
			$invoiceParams['footer_text'] = customFunctions::getFooterTextForInvoicePdf($invoiceSettingsModel->invoice_pdf_footer_text, $childModel->branch_id, $companyModel->id);
			$invoiceParams['registration_number'] = $companyModel->registration_number;
			$invoiceParams['vat_number'] = $companyModel->vat_number;
			$invoiceParams['company_name'] = $companyModel->name;
			$invoiceParams['header_color'] = $invoiceSettingsModel->invoice_header_color;
			if ($invoiceSettingsModel->invoice_logo == "Company") {
				$invoiceParams['website'] = $companyModel->website;
				$invoiceParams['email'] = $companyModel->email;
				$invoiceParams['phone'] = $companyModel->telephone;
			} else {
				$branchModel = Branch::model()->findByPk(Yii::app()->session['branch_id']);
				$invoiceParams['website'] = $branchModel->website_link;
				$invoiceParams['email'] = $branchModel->email;
				$invoiceParams['phone'] = $branchModel->phone;
			}
		}
		$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 45, 30, 2.5, 1, 'P');
		$mpdf->WriteHTML($this->renderPartial('manualInvoicePdf', array('model' => $model, 'childModel' => $childModel,
				'parentModel' => $parentModel, 'invoiceData' => $invoiceData, 'invoiceDetailsModel' => $invoiceDetailsModel,
				'invoiceParams' => $invoiceParams, 'invoiceSettingsModel' => $invoiceSettingsModel), TRUE));
		return $mpdf->Output('', "S");
	}

	public function actionDeleteInvoicePayment() {
		if (Yii::app()->request->isAjaxRequest) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$response = array('status' => '1');
				$model = ChildInvoiceTransactions::model()->findByPk($_POST['id']);
				$model->is_deleted = 1;
				$model->pg_status = ChildInvoiceTransactions::PG_STATUS_MANUALLY_DELETED;
				if ($model->save()) {
					$invoiceModel = ChildInvoice::model()->findByPk($model->invoice_id);
					$invoiceModel->status = ChildInvoice::PENDING_PAYMENT;
					if ($invoiceModel->save()) {
						/** Check if their is some credit note with this payment* */
						if ($model->credit_note_id != NULL) {
							$creditNoteTransactionModel = ChildInvoiceTransactions::model()->findByPk($model->credit_note_id);
							if (!empty($creditNoteTransactionModel)) {
								$creditNoteTransactionModel->is_deleted = 1;
								if ($creditNoteTransactionModel->save()) {
									$creditInvoiceModel = ChildInvoice::model()->findByPk($creditNoteTransactionModel->invoice_id);
									if (!empty($creditInvoiceModel)) {
										$creditInvoiceModel->status = ChildInvoice::NOT_ALLOCATED;
										if (!$creditInvoiceModel->save()) {
											throw new Exception("There seems to be some problem deleting the payment.");
										}
									}
								} else {
									throw new Exception("There seems to be some problem deleting the payment.");
								}
							}
						}
						/** Check is their is some payment associated with the transaction.* */
						if ($model->payment_id != NULL) {
							$paymentTransactionModel = PaymentsTransactions::model()->findByPk($model->payment_id);
							if (!empty($paymentTransactionModel)) {
								$paymentTransactionModel->is_deleted = 1;
								if ($paymentTransactionModel->save()) {
									$paymentModel = Payments::model()->findByPk($paymentTransactionModel->payment_id);
									if (!empty($paymentModel)) {
										$paymentModel->status = Payments::NOT_ALLOCATED;
										if (!$paymentModel->save())
											throw new Exception("There seems to be some problem deleting the payment.");
									}
								} else {
									throw new Exception("There seems to be some problem deleting the payment.");
								}
							}
						}
						$transaction->commit();
						echo CJSON::encode($response);
					} else {
						throw new Exception("There seems to be some problem deleting the payment.");
					}
				} else {
					throw new Exception("There seems to be some problem deleting the payment.");
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				$response = array('status' => '0', 'message' => $ex->getMessage());
				echo CJSON::encode($response);
			}
		}
	}

	public function actionInvoicePayment() {
		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1)) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$invoiceModel = ChildInvoice::model()->findByPk($_POST['ChildInvoiceTransactions']['invoice_id']);
				$paid_amount = customFunctions::getPaidAmount($invoiceModel->id);
				if (!empty($invoiceModel)) {
					$invoiceTransactionModel = new ChildInvoiceTransactions;
					$invoiceTransactionModel->attributes = $_POST['ChildInvoiceTransactions'];
					$invoiceTransactionModel->paid_amount = $_POST['ChildInvoiceTransactions']['paid_amount'] - $_POST['overpayment_amount'];
					$invoiceTransactionModel->invoice_amount = $invoiceModel->total;
					$invoiceTotal = sprintf("%0.2f", $invoiceModel->total);
					$payments = sprintf("%0.2f", $invoiceTransactionModel->paid_amount + $paid_amount);
					if ($payments < $invoiceTotal) {
						$invoiceModel->status = ChildInvoice::PENDING_PAYMENT;
					} else {
						$invoiceModel->status = ChildInvoice::PAID;
					}
					$paymentModel = new Payments;
					$paymentModel->attributes = $invoiceModel->attributes;
					$paymentModel->date_of_payment = $invoiceTransactionModel->date_of_payment;
					$paymentModel->payment_mode = $invoiceTransactionModel->payment_mode;
					$paymentModel->payment_reference = $invoiceTransactionModel->payment_refrence;
					$paymentModel->amount = $_POST['ChildInvoiceTransactions']['paid_amount'];
					$paymentModel->status = Payments::ALLOCATED;
					$paymentModel->child_id = $invoiceModel->child_id;
					if ($paymentModel->save()) {
						$paymentInvoiceTransaction = new PaymentsTransactions;
						$paymentInvoiceTransaction->payment_id = $paymentModel->id;
						$paymentInvoiceTransaction->invoice_id = $invoiceModel->id;
						$paymentInvoiceTransaction->paid_amount = $invoiceTransactionModel->paid_amount;
						if (!$paymentInvoiceTransaction->save())
							throw new Exception(CHtml::errorSummary($paymentInvoiceTransaction, "", "", array(
								'class' => 'customErrors')));

						$invoiceTransactionModel->payment_id = $paymentInvoiceTransaction->id;
						if (!$invoiceTransactionModel->save())
							throw new Exception(CHtml::errorSummary($invoiceTransactionModel, "", "", array(
								'class' => 'customErrors')));

						if (!$invoiceModel->save())
							throw new Exception(CHtml::errorSummary($invoiceModel, "", "", array('class' => 'customErrors')));

						$overpaymentModal = new ChildInvoice;
						$urn = customFunctions::getInvoiceUrn(Yii::app()->session['branch_id']);
						$overpaymentModal->urn_prefix = empty($urn['prefix']) ? NULL : $urn['prefix'];
						$overpaymentModal->urn_number = $urn['number'];
						$overpaymentModal->urn_suffix = empty($urn['suffix']) ? NULL : $urn['suffix'];
						$overpaymentModal->child_id = $invoiceModel->child_id;
						$overpaymentModal->branch_id = Yii::app()->session['branch_id'];
						$overpaymentModal->invoice_type = ChildInvoice::AUTOMATIC_CREDIT_NOTE;
						$overpaymentModal->description = $_POST['description'];
						$overpaymentModal->total = -$_POST['overpayment_amount'];
						$overpaymentModal->invoice_date = $paymentModel->date_of_payment;
						$overpaymentModal->due_date = $paymentModel->date_of_payment;
						$overpaymentModal->status = ChildInvoice::NOT_ALLOCATED;
						$overpaymentModal->access_token = md5(time() . uniqid() . $urn);
						$overpaymentModal->is_money_received = 1;
						$overpaymentModal->payment_mode = $invoiceTransactionModel->payment_mode;

						if (!$overpaymentModal->save())
							throw new Exception(CHtml::errorSummary($overpaymentModal, "", "", array(
								'class' => 'customErrors')));

						$paymentOverpaymentTransaction = new PaymentsTransactions;
						$paymentOverpaymentTransaction->payment_id = $paymentModel->id;
						$paymentOverpaymentTransaction->invoice_id = $overpaymentModal->id;
						$paymentOverpaymentTransaction->paid_amount = $_POST['overpayment_amount'];

						if (!$paymentOverpaymentTransaction->save())
							throw new Exception(CHtml::errorSummary($paymentOverpaymentTransaction, "", "", array(
								'class' => 'customErrors')));

						$overpaymentModal->credit_note_payment_id = $paymentOverpaymentTransaction->id;
						$overpaymentModal->credit_note_invoice_id = $invoiceModel->id;

						if (!$overpaymentModal->save())
							throw new Exception(CHtml::errorSummary($overpaymentModal, "", "", array(
								'class' => 'customErrors')));

						$transaction->commit();
						echo CJSON::encode(array('status' => 1, 'message' => "Payment has been successfully processed."));
					} else {
						throw new Exception(CHtml::errorSummary($paymentModel, "", "", array('class' => 'customErrors')));
					}
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				echo CJSON::encode(array('status' => 0, 'message' => $ex->getMessage()));
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid');
		}
	}

	public function actionCreateCreditNote($child_id) {
		if (Company::currentCompany()->is_enabled_credit_notes == 0 || Yii::app()->session['role'] == "branchAdmin") {
			throw new CHttpException(404, 'Your are not allowed to acces this page.');
		}
		$this->layout = "dashboard";
		$model = new ChildInvoice;
		$model->setScenario('credit_note');
		$personalDetails = ChildPersonalDetails::model()->findByPk($child_id);
		if (isset($_POST['ChildInvoice']) && isset($_POST['Save'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model->attributes = $_POST['ChildInvoice'];
				$paymentModel = new Payments;
				$paymentModel->child_id = $child_id;
				$paymentModel->branch_id = Branch::currentBranch()->id;
				$paymentModel->amount = $model->total;
				$paymentModel->date_of_payment = $model->invoice_date;
				$paymentModel->payment_reference = $model->description;
				$paymentModel->payment_mode = 9;
				$paymentModel->status = Payments::ALLOCATED;
				if ($paymentModel->save()) {
					$model->branch_id = $paymentModel->branch_id;
					$model->is_money_received = 0;
					$model->child_id = $child_id;
					$model->status = 'NOT_ALLOCATED';
					$model->invoice_type = ChildInvoice::CREDIT_NOTE;
					$urn = customFunctions::getInvoiceUrn(Yii::app()->session['branch_id']);
					$model->urn_prefix = $urn['prefix'];
					$model->urn_number = $urn['number'];
					$model->urn_suffix = $urn['suffix'];
					$model->access_token = md5(time() . uniqid() . $urn);
					$model->due_date = $model->invoice_date;
					if ($model->total <= 0) {
						throw new Exception("Credit Note of value should be greater than zero.");
					}
					$model->total = -$model->total;
					if ($model->save()) {
						$paymentTransactionModel = new PaymentsTransactions;
						$paymentTransactionModel->payment_id = $paymentModel->id;
						$paymentTransactionModel->invoice_id = $model->id;
						$paymentTransactionModel->paid_amount = $paymentModel->amount;
						if ($paymentTransactionModel->save()) {
							$model->credit_note_payment_id = $paymentTransactionModel->id;
							if (!$model->save()) {
								throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
							}
							$transaction->commit();
							$this->redirect(array('childInvoice/index', 'child_id' => $child_id));
						} else {
							throw new Exception(CHtml::errorSummary($paymentTransactionModel, "", "", array(
								'class' => 'customErrors')));
						}
					} else {
						throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
					}
				} else {
					throw new Exception(CHtml::errorSummary($paymentModel, "", "", array('class' => 'customErrors')));
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				Yii::app()->user->setFlash('error', $ex->getMessage());
				$this->refresh();
			}
		}
		$this->render('createCreditNote', array(
			'model' => $model,
			'personalDetails' => $personalDetails
		));
	}

	public function actionUpdateCreditNote($id, $child_id) {
		$this->layout = "dashboard";
		$model = $this->loadModel($id);
		$model->setScenario('credit_note');
		$personalDetails = $model->child;
		$model->total = sprintf("%0.2f", -$model->total);
		$this->performAjaxValidation($model);
		$refundTransactionsModel = new ChildInvoiceTransactions;
		$this->performAjaxValidation($refundTransactionsModel);
		$creditNoteTransactions = new CActiveDataProvider('ChildInvoiceTransactions', array(
			'criteria' => array(
				'condition' => 'invoice_id = :invoice_id',
				'order' => 'id DESC',
				'params' => array(':invoice_id' => $model->id)
			),
			'pagination' => array(
				'pageSize' => 20,
			),
		));
		if (isset($_POST['ChildInvoice']) && isset($_POST['Update'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$creditNoteTransactions = ChildInvoiceTransactions::model()->findAllByAttributes(array(
					'invoice_id' => $id));
				if (!empty($creditNoteTransactions)) {
					throw new Exception("Allocated credit notes can not be updated.");
				}
				$model->attributes = $_POST['ChildInvoice'];
				$paid_amount = customFunctions::getPaidAmount($model->id);
				if ($model->total > $paid_amount) {
					$model->status = ChildInvoice::NOT_ALLOCATED;
				}
				$model->total = -$model->total;
				if ($model->save()) {
					$transaction->commit();
					$this->redirect(array('childInvoice/index', 'child_id' => $child_id));
				} else {
					throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				Yii::app()->user->setFlash('error', $ex->getMessage());
				$this->refresh();
			}
		}

		if (isset($_POST['ChildInvoiceTransactions']) && isset($_POST['refund_deposit'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$invoiceModel = ChildInvoice::model()->findByPk($_GET['id']);
				$paid_amount = customFunctions::getPaidAmount($invoiceModel->id);
				if (!empty($invoiceModel)) {
					$refundTransactionsModel->attributes = $_POST['ChildInvoiceTransactions'];
					$refundTransactionsModel->is_refund = 1;
					$refundTransactionsModel->invoice_id = $invoiceModel->id;
					if (customFunctions::compareFloatNumbers((-$invoiceModel->total), ($paid_amount + $refundTransactionsModel->paid_amount), "<")) {
						$refundTransactionsModel->isNewRecord = true;
						Yii::app()->user->setFlash('error', 'Refund more than the value of credit note can not be made.');
						$this->render('updateCreditNote', array(
							'model' => $model,
							'personalDetails' => $personalDetails,
							'creditNoteTransactions' => $creditNoteTransactions,
							'refundTransactionsModel' => $refundTransactionsModel
						));
						Yii::app()->end();
					} else {
						$refundTransactionsModel->invoice_amount = $invoiceModel->total;
						if (($refundTransactionsModel->paid_amount + $paid_amount) < (-$invoiceModel->total)) {
							$invoiceModel->status = 'NOT_ALLOCATED';
						} else {
							$invoiceModel->status = 'ALLOCATED';
						}
						if ($refundTransactionsModel->save()) {
							if ($invoiceModel->save()) {
								$transaction->commit();
								$this->redirect(array('childInvoice/updateCreditNote', 'id' => $id, 'child_id' => $child_id));
							}
						} else {
							$refundTransactionsModel->isNewRecord = true;
							Yii::app()->user->setFlash('error', CHtml::errorSummary($refundTransactionsModel));
							$this->render('updateCreditNote', array(
								'model' => $model,
								'personalDetails' => $personalDetails,
								'creditNoteTransactions' => $creditNoteTransactions,
								'refundTransactionsModel' => $refundTransactionsModel
							));
							Yii::app()->end();
						}
					}
				}
			} catch (Exception $ex) {
				$transaction->rollback();
			}
		}

		if (isset($_POST['ChildInvoiceTransactions']) && isset($_POST['transfer_payment']) && isset($_POST['ChildInvoice'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				if ($_GET['child_id'] == $_POST['ChildInvoice']['child_id']) {
					Yii::app()->user->setFlash('error', 'Transfer Amount can not be done to the same child.');
					$this->render('updateCreditNote', array(
						'model' => $model,
						'personalDetails' => $personalDetails,
						'creditNoteTransactions' => $creditNoteTransactions,
						'refundTransactionsModel' => $refundTransactionsModel
					));
					Yii::app()->end();
				}
				$invoiceModel = ChildInvoice::model()->findByPk($_GET['id']);
				$paid_amount = customFunctions::getPaidAmount($invoiceModel->id);
				if (!empty($invoiceModel)) {
					$refundTransactionsModel->attributes = $_POST['ChildInvoiceTransactions'];
					$refundTransactionsModel->is_refund = 2;
					$refundTransactionsModel->invoice_id = $invoiceModel->id;
					if ((-$invoiceModel->total) < ($paid_amount + $refundTransactionsModel->paid_amount)) {
						$refundTransactionsModel->isNewRecord = true;
						Yii::app()->user->setFlash('error', 'Transfer more than the value of credit note can not be made.');
						$this->render('updateCreditNote', array(
							'model' => $model,
							'personalDetails' => $personalDetails,
							'creditNoteTransactions' => $creditNoteTransactions,
							'refundTransactionsModel' => $refundTransactionsModel
						));
						Yii::app()->end();
					} else {
						$refundTransactionsModel->invoice_amount = $invoiceModel->total;
						if (($refundTransactionsModel->paid_amount + $paid_amount) < (-$invoiceModel->total)) {
							$invoiceModel->status = 'NOT_ALLOCATED';
						} else {
							$invoiceModel->status = 'ALLOCATED';
						}
						if ($refundTransactionsModel->save()) {
							if ($invoiceModel->save()) {
								$paymentModel = new Payments;
								$paymentModel->child_id = $_POST['ChildInvoice']['child_id'];
								$paymentModel->branch_id = $invoiceModel->branch_id;
								$paymentModel->date_of_payment = $refundTransactionsModel->date_of_payment;
								$paymentModel->amount = $refundTransactionsModel->paid_amount;
								$paymentModel->payment_mode = $refundTransactionsModel->payment_mode;
								$paymentModel->payment_reference = "Transfer";
								$paymentModel->status = Payments::ALLOCATED;
								if ($paymentModel->save()) {
									$refundTransactionsModel->payment_id = $paymentModel->id;
									if ($refundTransactionsModel->save()) {
										$transferAmountInvoiceModel = new ChildInvoice('credit note');
										$transferAmountInvoiceModel->child_id = $paymentModel->child_id;
										$transferAmountInvoiceModel->branch_id = $paymentModel->branch_id;
										$transferAmountInvoiceModel->invoice_date = $paymentModel->date_of_payment;
										$transferAmountInvoiceModel->total = (-$paymentModel->amount);
										$transferAmountInvoiceModel->description = $refundTransactionsModel->payment_refrence;
										$transferAmountInvoiceModel->payment_mode = $refundTransactionsModel->payment_mode;
										$transferAmountInvoiceModel->status = ChildInvoice::NOT_ALLOCATED;
										$transferAmountInvoiceModel->invoice_type = ChildInvoice::CREDIT_NOTE;
										$urn = customFunctions::getInvoiceUrn($paymentModel->branch_id);
										$transferAmountInvoiceModel->urn_prefix = $urn['prefix'];
										$transferAmountInvoiceModel->urn_number = $urn['number'];
										$transferAmountInvoiceModel->urn_suffix = $urn['suffix'];
										$transferAmountInvoiceModel->access_token = md5(time() . uniqid() . $urn);
										$transferAmountInvoiceModel->due_date = $transferAmountInvoiceModel->invoice_date;
										$transferAmountInvoiceModel->is_money_received = 1;
										$transferAmountInvoiceModel->is_deposit = $_POST['ChildInvoice']['is_deposit'];
										if ($transferAmountInvoiceModel->is_deposit == 1) {
											$transferAmountInvoiceModel->description = "Deposit";
										}
										if ($transferAmountInvoiceModel->save()) {
											$paymentTransactionModel = new PaymentsTransactions;
											$paymentTransactionModel->payment_id = $paymentModel->id;
											$paymentTransactionModel->invoice_id = $transferAmountInvoiceModel->id;
											$paymentTransactionModel->paid_amount = (-$transferAmountInvoiceModel->total);
											if (!$paymentTransactionModel->save()) {
												Yii::app()->user->setFlash('error', CHtml::errorSummary($paymentTransactionModel));
												$this->render('updateCreditNote', array(
													'model' => $model,
													'personalDetails' => $personalDetails,
													'creditNoteTransactions' => $creditNoteTransactions,
													'refundTransactionsModel' => $refundTransactionsModel
												));
												Yii::app()->end();
											}
											$transferAmountInvoiceModel->credit_note_payment_id = $paymentTransactionModel->id;
											if (!$transferAmountInvoiceModel->save()) {

											}
											$transaction->commit();
											$this->redirect(array('childInvoice/updateCreditNote', 'id' => $id, 'child_id' => $child_id));
										}
									} else {
										Yii::app()->user->setFlash('error', CHtml::errorSummary($refundTransactionsModel));
										$this->render('updateCreditNote', array(
											'model' => $model,
											'personalDetails' => $personalDetails,
											'creditNoteTransactions' => $creditNoteTransactions,
											'refundTransactionsModel' => $refundTransactionsModel
										));
										Yii::app()->end();
									}
								} else {
									Yii::app()->user->setFlash('error', CHtml::errorSummary($paymentModel));
									$this->render('updateCreditNote', array(
										'model' => $model,
										'personalDetails' => $personalDetails,
										'creditNoteTransactions' => $creditNoteTransactions,
										'refundTransactionsModel' => $refundTransactionsModel
									));
									Yii::app()->end();
								}
							}
						} else {
							$refundTransactionsModel->isNewRecord = true;
							Yii::app()->user->setFlash('error', CHtml::errorSummary($refundTransactionsModel));
							$this->render('updateCreditNote', array(
								'model' => $model,
								'personalDetails' => $personalDetails,
								'creditNoteTransactions' => $creditNoteTransactions,
								'refundTransactionsModel' => $refundTransactionsModel
							));
							Yii::app()->end();
						}
					}
				}
			} catch (Exception $ex) {
				$transaction->rollback();
			}
		}

		$this->render('updateCreditNote', array(
			'model' => $model,
			'personalDetails' => $personalDetails,
			'creditNoteTransactions' => $creditNoteTransactions,
			'refundTransactionsModel' => $refundTransactionsModel
		));
	}

	public function actionGetCreditNotes($child_id) {
		$model = new CActiveDataProvider('ChildInvoice', array(
			'criteria' => array(
				'condition' => 'status = :status AND child_id = :child_id',
				'params' => array(':status' => ChildInvoice::NOT_ALLOCATED, ':child_id' => $child_id)
			),
			'pagination' => array(
				'pageSize' => 20,
			),
		));
		return $model;
	}

	public function actionGetCreditNoteAmount() {
		if (Yii::app()->request->isAjaxRequest) {
			$invoiceModel = ChildInvoice::model()->findByPk($_POST['invoice_id']);
			$creditNoteModel = ChildInvoice::model()->findByPk($_POST['credit_note_id']);
			$invoice_amount = $invoiceModel->total;
			$invoice_pending_amount = customFunctions::getDueAmount($_POST['invoice_id']);
			$creditNote_pending_amount = -customFunctions::getDueAmount($_POST['credit_note_id']);
			if ($invoice_pending_amount > $creditNote_pending_amount) {
				$amount_paid = $creditNote_pending_amount;
			} else {
				$amount_paid = $invoice_pending_amount;
			}
			echo CJSON::encode(array('status' => 1, 'invoice_amount' => customFunctions::round($invoice_amount, 2),
				'paid_amount' => customFunctions::round($amount_paid, 2)));
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	public function actionAllocateCreditNote() {
		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1)) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$paid_amount = sprintf("%0.2f", $_POST['ChildInvoiceTransactions']['paid_amount']);
				$original_paid_amount = sprintf("%0.2f", $_POST['original_paid_amount']);
				if ($paid_amount <= $original_paid_amount) {
					$invoiceModel = ChildInvoice::model()->findByPk($_POST['invoice_id']);
					$creditNoteModel = ChildInvoice::model()->findByPk($_POST['credit_id']);
					if (!empty($invoiceModel) && !empty($creditNoteModel)) {
						$invoicePreviousPayments = customFunctions::getPaidAmount($invoiceModel->id);
						$invoiceTransactionModel = new ChildInvoiceTransactions;
						$invoiceTransactionModel->attributes = $_POST['ChildInvoiceTransactions'];
						$invoiceTransactionModel->invoice_id = $invoiceModel->id;
						$invoiceTransactionModel->payment_mode = 9;
						if (($invoiceTransactionModel->paid_amount + $invoicePreviousPayments) < $invoiceModel->total) {
							$invoiceModel->status = ChildInvoice::PENDING_PAYMENT;
						} else {
							$invoiceModel->status = ChildInvoice::PAID;
						}
						if ($invoiceTransactionModel->save()) {
							if ($invoiceModel->save()) {
								$creditNotePreviousPayments = customFunctions::getPaidAmount($creditNoteModel->id);
								$creditNoteTransactionModel = new ChildInvoiceTransactions;
								$creditNoteTransactionModel->invoice_id = $creditNoteModel->id;
								$creditNoteTransactionModel->payment_refrence = $invoiceTransactionModel->payment_refrence;
								$creditNoteTransactionModel->invoice_amount = $creditNoteModel->total;
								$creditNoteTransactionModel->paid_amount = $invoiceTransactionModel->paid_amount;
								$creditNoteTransactionModel->date_of_payment = $invoiceTransactionModel->date_of_payment;
								$creditNoteTransactionModel->payment_mode = 9;
								if ((-$creditNoteModel->total) > ($creditNoteTransactionModel->paid_amount + $creditNotePreviousPayments)) {
									$creditNoteModel->status = ChildInvoice::NOT_ALLOCATED;
								} else {
									$creditNoteModel->status = ChildInvoice::ALLOCATED;
								}
								if ($creditNoteTransactionModel->save()) {
									if ($creditNoteModel->save()) {
										$invoiceTransactionModel->credit_note_id = $creditNoteTransactionModel->id;
										$invoiceTransactionModel->save();
										$transaction->commit();
										echo CJSON::encode(array('status' => 1, 'success' => 'Payment has been successfully processed.'));
									} else {
										echo CJSON::encode(array('status' => 0, 'error' => $creditNoteModel->getErrors()));
									}
								} else {
									echo CJSON::encode(array('status' => 0, 'error' => $creditNoteTransactionModel->getErrors()));
								}
							} else {
								echo CJSON::encode(array('status' => 0, 'error' => $invoiceModel->getErrors()));
							}
						} else {
							echo CJSON::encode(array('status' => 0, 'error' => $invoiceTransactionModel->getErrors()));
						}
					}
				} else {
					echo CJSON::encode(array('status' => 0, 'error' => array('ChildInvoiceTransactions_paid_amount' => 'Amount to be paid can not be greater than the amount of credit Note.')));
				}
			} catch (Exception $ex) {
				$transaction->rollback();
			}
		} else {
			throw new Exception(404, "Your request is not valid");
		}
	}

	public function actionViewAutomaticCreditNote($id, $child_id) {
		$this->layout = "dashboard";
		$model = ChildInvoice::model()->findByPk($id);
		$refundTransactionsModel = new ChildInvoiceTransactions;
		if ($model->invoice_type == ChildInvoice::AUTOMATIC_CREDIT_NOTE) {
			$personalDetails = ChildPersonalDetails::model()->findByPk($child_id);
			$model->setScenario('credit_note');
			$model->total = sprintf("%0.2f", -$model->total);
			$creditNoteTransactions = new CActiveDataProvider('ChildInvoiceTransactions', array(
				'criteria' => array(
					'condition' => 'invoice_id = :invoice_id',
					'order' => 'id DESC',
					'params' => array(':invoice_id' => $model->id)
				),
				'pagination' => array(
					'pageSize' => 20,
				),
			));
			if (isset($_POST['ChildInvoiceTransactions']) && isset($_POST['refund_deposit'])) {
				$transaction = Yii::app()->db->beginTransaction();
				try {
					$invoiceModel = ChildInvoice::model()->findByPk($_GET['id']);
					$paid_amount = customFunctions::getPaidAmount($invoiceModel->id);
					if (!empty($invoiceModel)) {
						$refundTransactionsModel->attributes = $_POST['ChildInvoiceTransactions'];
						$refundTransactionsModel->is_refund = 1;
						$refundTransactionsModel->invoice_id = $invoiceModel->id;
						if ((-$invoiceModel->total) < ($paid_amount + $refundTransactionsModel->paid_amount)) {
							$refundTransactionsModel->isNewRecord = true;
							Yii::app()->user->setFlash('error', 'Refund more than the value of credit note can not be made.');
							$this->render('viewAutomaticCreditNote', array(
								'model' => $model,
								'personalDetails' => $personalDetails,
								'creditNoteTransactions' => $creditNoteTransactions,
								'refundTransactionsModel' => $refundTransactionsModel
							));
							Yii::app()->end();
						} else {
							$refundTransactionsModel->invoice_amount = $invoiceModel->total;
							if (($refundTransactionsModel->paid_amount + $paid_amount) < (-$invoiceModel->total)) {
								$invoiceModel->status = 'NOT_ALLOCATED';
							} else {
								$invoiceModel->status = 'ALLOCATED';
							}
							if ($refundTransactionsModel->save()) {
								if ($invoiceModel->save()) {
									$transaction->commit();
									$this->redirect(array('childInvoice/viewAutomaticCreditNote', 'id' => $id,
										'child_id' => $child_id));
								}
							} else {
								$refundTransactionsModel->isNewRecord = true;
								Yii::app()->user->setFlash('error', CHtml::errorSummary($refundTransactionsModel));
								$this->render('viewAutomaticCreditNote', array(
									'model' => $model,
									'personalDetails' => $personalDetails,
									'creditNoteTransactions' => $creditNoteTransactions,
									'refundTransactionsModel' => $refundTransactionsModel
								));
								Yii::app()->end();
							}
						}
					}
				} catch (Exception $ex) {
					$transaction->rollback();
				}
			}
			$this->render('viewAutomaticCreditNote', array(
				'model' => $model,
				'personalDetails' => $personalDetails,
				'creditNoteTransactions' => $creditNoteTransactions,
				'refundTransactionsModel' => $refundTransactionsModel
			));
		} else {
			throw new CHttpException(404, "This page does not exists.");
		}
	}

	public function actionDeleteCreditNote() {
		if (Yii::app()->request->isAjaxRequest) {
			$creditNoteModal = ChildInvoice::model()->findByPk($_POST['id']);
			$transaction = Yii::app()->db->beginTransaction();
			try {
				if (!empty($creditNoteModal)) {
					$checkPreviousTransactions = ChildInvoiceTransactions::model()->findAllByAttributes([
						'invoice_id' => Yii::app()->request->getPost('id')]);
					if (!empty($checkPreviousTransactions)) {
						echo CJSON::encode(['status' => 0, 'message' => 'Payments has been already processed for this credit Note.',
							'url' => Yii::app()->createUrl('childInvoice/viewAutomaticCreditNote', ['id' => $_POST['id'],
								'child_id' => $_POST['child_id']])]);
					} else {
						$creditNoteModal->is_deleted = 1;
						if ($creditNoteModal->save()) {
							if ($creditNoteModal->credit_note_payment_id != NULL) {
								$paymentTransactionModel = PaymentsTransactions::model()->findByPk($creditNoteModal->credit_note_payment_id);
								$paymentModal = Payments::model()->findByPk($paymentTransactionModel->payment_id);
								if (!empty($paymentTransactionModel) && !empty($paymentModal)) {
									$paymentTransactionModel->is_deleted = 1;
									if ($paymentTransactionModel->save()) {
										$paymentModal->status = Payments::NOT_ALLOCATED;
										$paymentModal->save();
										$transaction->commit();
										echo CJSON::encode(['status' => 1, 'message' => 'Credit Note has been successfully deleted',
											'url' => Yii::app()->createUrl('childInvoice/index', ['child_id' => $_POST['child_id']])]);
									} else {
										echo CJSON::encode(['status' => 0, 'message' => 'There seems to be some problem deleting the credit note.',
											'url' => Yii::app()->createUrl('childInvoice/updateCreditNote', ['id' => $_POST['id'],
												'child_id' => $_POST['child_id']])]);
									}
								} else {
									$transaction->commit();
									echo CJSON::encode(['status' => 1, 'message' => 'Credit Note has been successfully deleted',
										'url' => Yii::app()->createUrl('childInvoice/index', ['child_id' => $_POST['child_id']])]);
								}
							} else {
								$transaction->commit();
								echo CJSON::encode(['status' => 1, 'message' => 'Credit Note has been successfully deleted',
									'url' => Yii::app()->createUrl('childInvoice/index', ['child_id' => $_POST['child_id']])]);
							}
						} else {
							echo CJSON::encode(['status' => 0, 'message' => 'There seems to be some problem deleting the credit note.',
								'url' => Yii::app()->createUrl('childInvoice/updateCreditNote', ['id' => $_POST['id'],
									'child_id' => $_POST['child_id']])]);
						}
					}
				} else {
					echo CJSON::encode(['status' => 0, 'message' => 'Credit Note you are trying to delete does not exists.',
						'url' => Yii::app()->createUrl('childInvoice/index', ['child_id' => $_POST['child_id']])]);
				}
			} catch (Exception $ex) {
				echo CJSON::encode(['status' => 0, 'message' => 'There seems to be some problem deleting the credit note.',
					'url' => Yii::app()->createUrl('childInvoice/updateCreditNote', ['id' => $_POST['id'],
						'child_id' => $_POST['child_id']])]);
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionCalculateMonthlySessions($child_id) {
		$childModel = ChildPersonalDetails::model()->findByPk($pk);
		if (!empty($childModel)) {
			if (isset($childModel->start_date) && !empty($childModel->start_date)) {
				if (date("d", strtotime($childModel->start_date)) != 1) {
					$first_day_of_month = date("Y-m-01", strtotime($childModel->start_date));
					$last_day_of_month = date("Y-m-t", strtotime($childModel->start_date));
				}
			}
		} else {
			throw new CHttpException(404, "This page does not exists.");
		}
	}

	public function actionPayments($child_id) {
		$this->pageTitle = 'Child Invoices | eyMan';
		$response = array();
		$criteria = new CDbCriteria();
		$criteria->condition = "child_id = :child_id AND (invoice_type = 0 OR invoice_type = 1)";
		$criteria->params = array(':child_id' => $child_id);
		$invoiceModel = ChildInvoice::model()->findAll($criteria);
		if (!empty($invoiceModel)) {
			foreach ($invoiceModel as $invoice) {
				$transactionModel = ChildInvoiceTransactions::model()->findAllByAttributes(array(
					'invoice_id' => $invoice->id, 'credit_note_id' => NULL, 'payment_id' => NULL));
				if (!empty($transactionModel)) {
					foreach ($transactionModel as $transaction) {
						$temp = array();
						$temp['id'] = "Invoice Payment-" . $transaction->id;
						$temp['amount'] = $transaction->paid_amount;
						$temp['date_of_payment'] = strtotime($transaction->date_of_payment);
						$temp['payment_mode'] = customFunctions::getPaymentOptionName($transaction->payment_mode);
						$temp['type'] = "Invoice Payment";
						$temp['url'] = Yii::app()->createUrl('childInvoice/view', array('child_id' => $invoice->child_id,
							'invoice_id' => $invoice->id));
						$response[] = $temp;
					}
				}
			}
		}
		$creditNotesModel = ChildInvoice::model()->findAllByAttributes(array('child_id' => $child_id,
			'invoice_type' => 3, 'is_deposit' => 1), array('order' => 'invoice_date'));
		if (!empty($creditNotesModel)) {
			foreach ($creditNotesModel as $creditNote) {
				$temp = array();
				$temp['id'] = "Credit Note-" . $creditNote->invoiceUrn;
				$temp['amount'] = sprintf("%0.2f", -$creditNote->total);
				$temp['date_of_payment'] = strtotime($creditNote->invoice_date);
				$temp['payment_mode'] = $creditNote->description;
				$temp['type'] = "Deposit";
				$temp['url'] = Yii::app()->createUrl('childInvoice/updateCreditNote', array(
					'id' => $creditNote->id, 'child_id' => $creditNote->child_id));
				$response[] = $temp;
			}
		}
		$paymentsModel = Payments::model()->findAllByAttributes(array('branch_id' => Yii::app()->session['branch_id']), array(
			'order' => 'date_of_payment'));
		if (!empty($paymentsModel)) {
			foreach ($paymentsModel as $payments) {
				if (in_array($child_id, explode(",", $payments->child_id))) {
					$temp = array();
					$temp['id'] = "Payment-" . $payments->id;
					$temp['amount'] = sprintf("%0.2f", $payments->amount);
					$temp['date_of_payment'] = strtotime($payments->date_of_payment);
					$temp['payment_mode'] = customFunctions::getPaymentOptionName($payments->payment_mode);
					$temp['type'] = "Payments";
					$temp['url'] = Yii::app()->createUrl('payments/view', array('id' => $payments->id));
					$response[] = $temp;
				}
			}
		}
		/*usort($response, function($i, $j) {
			$a = strtotime(date("Y-m-d", strtotime($i['date_of_payment'])));
			$b = strtotime(date("Y-m-d", strtotime($j['date_of_payment'])));
			if ($a == $b)
				return 0;
			elseif ($a > $b)
				return 1;
			else
				return -1;
		});*/
		$this->render('payments', array(
			'response' => $response,
		));
	}

	public function actionRemoveMinimumBookingFees() {
		if (Yii::app()->request->isAjaxRequest) {
			if (ChildInvoice::model()->findByPk(Yii::app()->request->getPost('invoice_id'))->is_locked == 1) {
				Yii::app()->user->setFlash('error', 'Additional item can not be removed as invoice is locked.');
				echo CJSON::encode(array('status' => 0, 'message' => 'Additional item can not be removed as invoice is locked.'));
				Yii::app()->end();
			}
			$model = ChildInvoiceDetails::model()->findByPk(Yii::app()->request->getPost('id'));
			$response = $model->deleteMinimumBookingFees();
			if ($response['status'] == 1) {
				Yii::app()->user->setFlash('success', $response['message']);
				echo CJSON::encode(array('status' => 1, 'message' => $response['message']));
			} else {
				Yii::app()->user->setFlash('error', $response['message']);
				echo CJSON::encode(array('status' => 0, 'message' => $response['message']));
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionAddRow($invoice_id) {
		$this->layout = "dashboard";
		$model = $this->loadModel($invoice_id);
		if ($model->is_locked == 1) {
			Yii::app()->user->setFlash('error', 'Item can not be added as invoices is locked.');
			$this->redirect(array('view', 'child_id' => $model->child_id, 'invoice_id' => $model->id));
		}
		$invoiceDetails = new ChildInvoiceDetails;
		if (isset($_POST['Add']) && isset($_POST['ChildInvoiceDetails'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model->status = ChildInvoice::PENDING_PAYMENT;
				$model->invoice_type = ChildInvoice::AUTOMATIC_INVOICE;
				if (ChildInvoice::model()->updateByPk($model->id, ['status' => ChildInvoice::PENDING_PAYMENT, 'invoice_type' => ChildInvoice::AUTOMATIC_INVOICE, 'total' => ($model->total + sprintf("%0.2f", $_POST['total_amount']))])) {
					$invoiceDueAmount = customFunctions::getDueAmount($model->id);
					if ($invoiceDueAmount < 0) {
						throw new Exception("Item smaller than the total due amount of invoice can not be added.");
					}
					if ($invoiceDueAmount <= 0) {
						ChildInvoice::model()->updateByPk($model->id, [
							'status' => ChildInvoice::PAID
						]);
					}
					$invoiceDetails = new ChildInvoiceDetails;
					$invoiceDetails->invoice_id = $model->id;
					$invoiceDetails->products_data = customFunctions::getProductsDataForManuaInvoice($_POST['ChildInvoiceDetails']['product_id'], $_POST['ChildInvoiceDetails']['product_description'], $_POST['ChildInvoiceDetails']['product_quantity'], $_POST['ChildInvoiceDetails']['product_price'], $_POST['ChildInvoiceDetails']['discount'], $_POST['ChildInvoiceDetails']['amount']);
					$invoiceDetails->discount = $model->child->discount;
					$invoiceDetails->is_extras = 1;
					if (!$invoiceDetails->save()) {
						throw new Exception("Seems some problem adding item to invoice.");
					}
					$transaction->commit();
					Yii::app()->user->setFlash('success', 'Item has been successfully added to the invoice.');
					$this->redirect(array('childInvoice/view', 'child_id' => $model->child_id, 'invoice_id' => $model->id));
				} else {
					throw new Exception("Seems some problem adding item to invoice.");
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				Yii::app()->user->setFlash('error', $ex->getMessage());
				$this->refresh();
			}
		}
		$this->render('addRow', array(
			'model' => $model,
			'invoiceDetails' => $invoiceDetails,
		));
	}

	public function actionRemoveAdditionalItem() {
		if (Yii::app()->request->isAjaxRequest) {
			$model = ChildInvoiceDetails::model()->findByPk(Yii::app()->request->getPost('id'));
			if (ChildInvoice::model()->findByPk(Yii::app()->request->getPost('invoice_id'))->is_locked == 1) {
				Yii::app()->user->setFlash('error', 'Additional item can not be removed as invoice is locked.');
				echo CJSON::encode(array('status' => 0, 'message' => 'Additional item can not be removed as invoice is locked.'));
				Yii::app()->end();
			}
			if (!empty($model)) {
				$model->deleted_product_id = Yii::app()->request->getPost('product_id');
				$response = $model->deleteAdditonalItem();
				if ($response['status'] == 1) {
					Yii::app()->user->setFlash('success', $response['message']);
					echo CJSON::encode(array('status' => 1, 'message' => $response['message']));
				} else {
					Yii::app()->user->setFlash('error', $response['message']);
					echo CJSON::encode(array('status' => 0, 'message' => $response['message']));
				}
			} else {
				echo CJSON::encode(array('status' => 0, 'message' => 'The page you are trying to access does not exists.'));
			}
		} else {
			throw new CHttpException(404, "This request is not valid.");
		}
	}

	public function actionSendInvoiceEmailToParent() {
		$model = ChildInvoice::model()->findAllByAttributes(['invoice_type' => 0, 'due_date' => '2016-08-01',
			'invoice_date' => '2016-07-20', 'is_regenrated' => 0, 'is_email_sent' => 0]);
		$invoiveSettings = InvoiceSetting::model()->findByAttributes(array('branch_id' => Yii::app()->session['branch_id']));
		if (!empty($invoiveSettings)) {
			if (!empty($model)) {
				foreach ($model as $invoiceModel) {
					try {
						$parentalDetailsModel = ChildPersonalDetails::model()->findByPk($invoiceModel->child_id)->getBillPayers();
						if (!empty($parentalDetailsModel)) {
							$to = array();
							$name = array();

							if (isset($parentalDetailsModel[1]) && !empty($parentalDetailsModel[1]->email)) {
								$to[] = $parentalDetailsModel[1]->email;
								$name[] = $parentalDetailsModel[1]->Fullname;
							}

							if (isset($parentalDetailsModel[2]) && !empty($parentalDetailsModel[2]->email)) {
								$to[] = $parentalDetailsModel[2]->email;
								$name[] = $parentalDetailsModel[2]->Fullname;
							}
							if (empty($to) && empty($name)) {
								throw new Exception("Email can not be send for invoice_id $invoiceModel->id as none of the parent is marked as the bill payer.");
							}
							if (!empty($to) && !empty($name)) {
								foreach ($to as $key => $parentEmail) {
									if (!empty($parentEmail)) {
										$isSent = false;
										$subject = customFunctions::getInvoiceEmailSuject($invoiceModel, $invoiveSettings, $name[$key]);
										$content = customFunctions::getInvoiceEmailBody($invoiceModel, $invoiveSettings, $name[$key]);
										if ($invoiceModel->invoice_type == ChildInvoice::AUTOMATIC_INVOICE) {
											$this->actionAutomaticInvoicePdfAttachment($invoiceModel->child_id, $invoiceModel->id);
										} else {
											$this->actionManualInvoicePdfAttachment($invoiceModel->child_id, $invoiceModel->id);
										}
										$isSent = customFunctions::sendInvoiceEmail($parentEmail, $name[$key], $subject, $content, $invoiveSettings->from_email, "Invoice.pdf", $invoiceModel->branch->company->name);
									}
								}
								if ($isSent == true) {
									$invoiceModel->is_email_sent = 1;
									if (!$invoiceModel->save())
										throw new Exception("Some problme has occured saving the invoice status. for invoice_id $invoiceModel->id");
									else
										echo "<p style='color:green;'>Email has been successfully sent for invoice id $invoiceModel->id </p>";
								} else {
									throw new Exception("There seems to be some problem sending the email for invoice_id $invoiceModel->id");
								}
							} else {
								throw new Exception("Email of parent not present for invoice_id $invoiceModel->id");
							}
						} else {
							throw new Exception("Parental details are empty for invoice_id $invoiceModel->id");
						}
					} catch (Exception $ex) {
						echo "<p style='color:red'>" . $ex->getMessage() . "</p>";
					}
				}
			} else {
				echo "No invoice is present for the selected month";
			}
		}
		echo "Invoices Could not be send because invoice setting are not present for the branch." . "</br>";
	}

	public function actionLockInvoices() {
		if (Yii::app()->request->isAjaxRequest) {
			if (Yii::app()->request->getPost('month') == NULL || Yii::app()->request->getPost('year') == NULL) {
				echo CJSON::encode(array('status' => 0, 'message' => "Please Select all the filters to lock down invoices."));
				Yii::app()->end();
			} else {
				$model = ChildInvoice::model()->updateAll(
					['is_locked' => 1], 'year = :year AND month = :month AND is_regenrated = 0 AND branch_id = :branch_id', [
					':year' => Yii::app()->request->getPost('year'), ':month' => Yii::app()->request->getPost('month'),
					':branch_id' => Branch::currentBranch()->id]);
				if (empty($model)) {
					echo CJSON::encode(['status' => 0, 'message' => 'No invoices could be found matching this criteria.']);
				} else {
					echo CJSON::encode(['status' => 1, 'message' => 'Invoices has been successfully locked.']);
				}
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionChildBalanceSheetStatement() {
		if (Yii::app()->request->isAjaxRequest) {
			$childModel = ChildPersonalDetails::model()->findByPk(Yii::app()->request->getPost('child_id'));
			$billPayerDetail = $childModel->getBillPayers();
			$invoiceSettings = InvoiceSetting::model()->findByAttributes(['branch_id' => $childModel->branch_id]);
			if (!empty($childModel) && !empty($billPayerDetail)) {
				$response = array();
				$parentName = array();
				$parentEmail = array();

				if (isset($billPayerDetail[1]) && !empty($billPayerDetail[1]->email)) {
					$parentEmail[] = $billPayerDetail[1]->email;
					$parentName[] = $billPayerDetail[1]->Fullname;
				}
				if (isset($billPayerDetail[2]) && !empty($billPayerDetail[2]->email)) {
					$parentEmail[] = $billPayerDetail[2]->email;
					$parentName[] = $billPayerDetail[2]->Fullname;
				}

				$response['parent_name'] = implode("/ ", $parentName);
				$response['to'] = implode(", ", $parentEmail);
				$response['from'] = (!empty($invoiceSettings)) ? $invoiceSettings->from_email : "";
				$response['reply_to'] = (!empty($invoiceSettings)) ? $invoiceSettings->reply_to_email : "";
				$response['subject'] = "Statement from " . $childModel->branch->company->name . " for " . $childModel->name;
				$response['message'] = "<p>Hi " . $response['parent_name'] . "</p>" . "<p>Here's your statement as at " . date("d M,Y") . "</p>" . "<p>If you have any questions, please let us know.</p>" . "<p>Thanks,</p>" . "<p>" . $childModel->branch->company->name . "</p>";
				$response['send_me'] = User::model()->findByPk(Yii::app()->user->id)->email;
				$response['status'] = 1;
				echo CJSON::encode($response);
			} else {
				echo CJSON::encode(array('status' => 0, 'message' => 'Child Details could not be found.'));
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	public function actionSendChildBalanceStatement() {
		if (Yii::app()->request->isAjaxRequest) {
			$model = new ChildBalanceStatementForm();
			$model->attributes = $_POST['ChildBalanceStatementForm'];
			$sendMe = ($_POST['is_send_me'] == "true") ? true : false;
			if ($model->validate()) {
				$childModel = ChildPersonalDetails::model()->findByPk($_POST['child_id']);
				if (empty($childModel)) {
					echo CJSON::encode(array('status' => 1, 'message' => 'Child Details not found in the nursery.'));
					Yii::app()->end();
				}
				$subject = $model->subject;
				$content = $model->message;
				$to = explode(",", $model->to);
				$sentResponse = array();
				foreach ($to as $key => $value) {
					$attachement = $this->actionPdfChildBalanceStatement($childModel->id, "S", false);
					$isSent = customFunctions::sendPdfAttachmentEmail($value, $_POST['parent_name'], $subject, $content, $model->from, $attachement, $childModel->branch->company->name);
					if ($isSent == true) {
						$sentResponse[] = "Email has been successfully sent to - " . $value;
					} else {
						$sentResponse[] = "There seems to be some problems sending statement to - " . $value;
					}
				}
				if ($sendMe === true) {
					$attachment = $this->actionPdfChildBalanceStatement($childModel->id, "S", false);
					$user_email = User::model()->findByPk(Yii::app()->user->id)->email;
					$isSent = customFunctions::sendPdfAttachmentEmail($user_email, Yii::app()->session['name'], $subject, $content, $model->from, $attachement, $childModel->branch->company->name);
					if ($isSent == true) {
						$sentResponse[] = "Email has been successfully sent to - " . $user_email;
					} else {
						$sentResponse[] = "There seems to be some problems sending statement to - " . $user_email;
					}
				}
				echo CJSON::encode(array('status' => 1, 'message' => $sentResponse));
				Yii::app()->end();
			} else {
				echo CJSON::encode(array('status' => 0, 'message' => $model->getErrors()));
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionPdfChildBalanceStatement($child_id, $attachment_type = "D", $exit = true) {
		$model = new ChildInvoice('search');
		$childModel = ChildPersonalDetails::model()->findByPk($child_id);
		$parentModel = ChildPersonalDetails::model()->findByPk($child_id)->getFirstBillPayer();
		if (!empty($childModel)) {
			$invoiceSettingsModel = InvoiceSetting::model()->findByAttributes(['branch_id' => Yii::app()->session['branch_id']]);
			if (!empty($invoiceSettingsModel)) {
				$invoiceParams = array();
				$invoiceParams['footer_text'] = customFunctions::getFooterTextForInvoicePdf($invoiceSettingsModel->invoice_pdf_footer_text, $childModel->branch_id, $invoiceSettingsModel->branch->company->id);
				$invoiceParams['registration_number'] = $invoiceSettingsModel->branch->company->registration_number;
				$invoiceParams['vat_number'] = $invoiceSettingsModel->branch->company->vat_number;
				$invoiceParams['company_name'] = $invoiceSettingsModel->branch->company->name;
				$invoiceParams['header_color'] = $invoiceSettingsModel->invoice_header_color;
				if ($invoiceSettingsModel->invoice_logo == "Company") {
					$invoiceParams['website'] = $invoiceSettingsModel->branch->company->website;
					$invoiceParams['email'] = $invoiceSettingsModel->branch->company->email;
					$invoiceParams['phone'] = $invoiceSettingsModel->branch->company->telephone;
				} else {
					$branchModel = Branch::model()->findByPk($invoiceSettingsModel->branch_id);
					$invoiceParams['website'] = $invoiceSettingsModel->branch->website_link;
					$invoiceParams['email'] = $invoiceSettingsModel->branch->email;
					$invoiceParams['phone'] = $invoiceSettingsModel->branch->phone;
				}
				$model->unsetAttributes();
				$model->child_id = $child_id;
				$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 45, 30, 2.5, 1, 'P');
				$mpdf->WriteHTML($this->renderPartial('childBalanceStatementPdf', array('model' => $model,
						'invoiceSettingsModel' => $invoiceSettingsModel, 'invoiceParams' => $invoiceParams,
						'parentalModel' => $parentModel, 'childModel' => $childModel), true));
				if ($attachment_type == "S") {
					return $mpdf->Output('', $attachment_type);
				}
				$mpdf->Output('BalanceStatement.pdf', $attachment_type);
				if ($exit) {
					exit();
				}
			} else {
				exit();
			}
		}
	}

	public function actionGenerateInvoices() {
		if (Yii::app()->request->isAjaxRequest) {
			$model = new GenerateInvoicesForm;
			$model->attributes = $_POST['GenerateInvoicesForm'];
			$model->is_all_child = $_POST['is_all_child'];
			if ($model->validate()) {
				$invoiceSettingsModel = InvoiceSetting::model()->findByAttributes(['branch_id' => Yii::app()->session['branch_id']]);
				if ($invoiceSettingsModel->invoice_generate_type == InvoiceSetting::EQUAL_SPREAD_MONTHS) {
					$this->actionInvoiceMonthly($model->month, $model->year, $model->child_id, $model->is_all_child, date("Y-m-d", strtotime($model->invoice_date)), date("Y-m-d", strtotime($model->invoice_due_date)));
				}
				if ($invoiceSettingsModel->invoice_generate_type == InvoiceSetting::ACTUAL_SESSION_MONTHLY) {
					$this->actionInvoice($model->month, $model->year, $model->child_id, $model->is_all_child, date("Y-m-d", strtotime($model->invoice_date)), date("Y-m-d", strtotime($model->invoice_due_date)));
				}
			} else {
				echo CJSON::encode(array('status' => 0, 'message' => $model->getErrors()));
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionDeleteInvoice($id) {
		$model = ChildInvoice::model()->findByPk($id);
		if (!empty($model)) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				if (!empty($model->childInvoiceTransactions)) {
					throw new Exception('To delete the invoice please remove the allocated payments.');
				}
				$fundingTransactions = ChildFundingTransactions::model()->findAllByAttributes([
					'invoice_id' => $model->id]);
				if (!empty($fundingTransactions)) {
					if ($model->is_monthly_invoice == 1) {
						foreach ($fundingTransactions as $fundingTransaction) {
							$fundingTransaction->invoice_id = NULL;
							if (!$fundingTransaction->save()) {
								throw new Exception("There seems to be some problem removing the funding.");
							}
						}
					} else {
						foreach ($fundingTransactions as $fundingTransaction) {
							$checkInvalidEntryExists = ChildFundingTransactions::model()->findAll(array(
								'condition' => 'invoice_id != :invoice_id AND week_start_date = :week_start_date and funding_id = :funding_id',
								'params' => array(':invoice_id' => $id, ':week_start_date' => $fundingTransaction->week_start_date,
									':funding_id' => $fundingTransaction->funding_id)));
							if (!empty($checkInvalidEntryExists)) {
								$fundingTransaction->is_deleted = 1;
								if (!$fundingTransaction->save()) {
									throw new Exception("There seems to be some problem removing the funding.");
								}
							}
						}
						foreach ($fundingTransactions as $fundingTransaction) {
							$fundingTransaction->invoice_id = NULL;
							$fundingTransaction->funded_hours_used = NULL;
							if (!$fundingTransaction->save()) {
								throw new Exception("There seems to be some problem removing the funding.");
							}
						}
					}
				}
				$childBookings = ChildBookings::model()->findAllByAttributes(array('invoice_id' => $model->id));
				if (!empty($childBookings)) {
					foreach ($childBookings as $booking) {
						$booking->invoice_id = NULL;
						$booking->is_invoiced = 0;
						if (!$booking->save()) {
							throw new Exception("Theri seems to be some problem deleting the invoice.");
						}
					}
				}
				$model->is_deleted = 1;
				if (!$model->save()) {
					throw new Exception("Theri seems to be some problem deleting the invoice.");
				}
				$transaction->commit();
				$this->redirect(['childInvoice/index', 'child_id' => $model->child_id]);
			} catch (Exception $ex) {
				$transaction->rollback();
				Yii::app()->user->setFlash("error", $ex->getMessage());
				$this->redirect(['childInvoice/view', 'child_id' => $model->child_id, 'invoice_id' => $model->id]);
				Yii::app()->end();
			}
		} else {
			throw new CHttpException(404, "This page does not exists.");
		}
	}

	public function actionSendInvoicesEmail() {
		ini_set('max_execution_time' , -1);
		if (Yii::app()->request->isAjaxRequest) {
			$model = new SendInvoicesEmailForm;
			$model->attributes = $_POST['SendInvoicesEmailForm'];
			$model->is_all_child = $_POST['is_all_child'];
			if ($model->validate()) {
				$invoiceSettings = InvoiceSetting::model()->findByAttributes(['branch_id' => Yii::app()->session['branch_id']]);
				if (empty($invoiceSettings)) {
					echo CJSON::encode(['status' => 0, 'message' => ['year' => ['Please create invoice settings for the branch.']]]);
					Yii::app()->end();
				}
				if ($model->is_all_child == 1) {
					$invoicesModel = ChildInvoice::model()->findAllByAttributes([
						'branch_id' => Yii::app()->session['branch_id'],
						'month' => $model->month,
						'year' => $model->year,
						'invoice_type' => ChildInvoice::AUTOMATIC_INVOICE,
						'is_regenrated' => 0]);
				} else {
					$invoicesModel = ChildInvoice::model()->findAll([
						'condition' => 'child_id IN (' . implode(",", $model->child_id) . ') AND month = :month AND year = :year AND invoice_type = :invoice_type AND is_regenrated = 0',
						'params' => [':month' => $model->month, ':year' => $model->year, ':invoice_type' => ChildInvoice::AUTOMATIC_INVOICE]]);
				}
				if (!empty($invoicesModel)) {
					customFunctions::sendPartialResponse(['status' => 1, 'message' => ["Invoices will be sent in background and you will be notified on email."]]);
					$log = array();
					foreach ($invoicesModel as $invoiceModel) {
						try {
							$parentalDetailsModel = $invoiceModel->childNds->getBillPayers();
							if (!empty($parentalDetailsModel)) {
								$recipients = array();
								if (isset($parentalDetailsModel[1]) && !empty($parentalDetailsModel[1]->email) && $invoiceModel->is_email_sent == 0) {

									$recipients['p1'] = [
										'email' => $parentalDetailsModel[1]->email,
										'name' => $parentalDetailsModel[1]->Fullname,
										'type' => 'to'
									];
								}
								if (isset($parentalDetailsModel[2]) && !empty($parentalDetailsModel[2]->email) && $invoiceModel->is_email_sent_2 == 0) {
									$recipients['p2'] = [
										'email' => $parentalDetailsModel[2]->email,
										'name' => $parentalDetailsModel[2]->Fullname,
										'type' => 'to'
									];
								}
								if (!empty($recipients)) {
									if ($invoiceModel->invoice_type == ChildInvoice::AUTOMATIC_INVOICE) {
										$attachment = [
											'type' => 'application/pdf',
											'name' => md5(uniqid()) . ".pdf",
											'content' => base64_encode($this->actionAutomaticInvoicePdfAttachment($invoiceModel->child_id, $invoiceModel->id))
										];
									} else {
										$attachment = [
											'type' => 'application/pdf',
											'name' => md5(uniqid()) . ".pdf",
											'content' => base64_encode($this->actionManualInvoicePdfAttachment($invoiceModel->child_id, $invoiceModel->id))
										];
									}
									$log[] = "**---------------------------------------------------------------------------------------------**";
									$log[] = "";
									$log[] = "Started sending Invoice " . $invoiceModel->invoiceUrn . " for child " . $invoiceModel->child->name;
									foreach ($recipients as $parent_number => $recipient) {
										$subject = customFunctions::getInvoiceEmailSuject($invoiceModel, $invoiceSettings, $recipient['name']);
										$content = customFunctions::getInvoiceEmailBody($invoiceModel, $invoiceSettings, $recipient['name']);
										$metadata = [
											'rcpt' => $recipient['email'],
											'values' => ['invoice_id' => $invoiceModel->id, 'parent_type' => $parent_number, 'company' => $invoiceModel->branch->company->name]
										];
										$mandrill = new EymanMandril($subject, $content, $invoiceModel->branch->company->name, [$recipient], $invoiceSettings->from_email, [$attachment], false, [$metadata]);
										$response = $mandrill->sendEmail();
										if ($parent_number == "p1") {
											if (!empty($response)) {
												foreach ($response as $email) {
													if (EymanMandril::getEmailStatus($email['status']) == 0) {
														$log[] = "Email not send to parent -" . $email['email'] . " for Invoice " . $invoiceModel->invoiceUrn . " of child " . $invoiceModel->child->name;
													} else {
														$log[] = "Email successfully send to parent - " . $email['email'] . " for Invoice " . $invoiceModel->invoiceUrn . " of child " . $invoiceModel->child->name;
													}
													ChildInvoice::model()->updateByPk($invoiceModel->id, ['is_email_sent' => EymanMandril::getEmailStatus($email['status']), 'email_1_mandrill_id' => $email['_id']]);
												}
											}
										}
										if ($parent_number == "p2") {
											if (!empty($response)) {
												foreach ($response as $email) {
													if (EymanMandril::getEmailStatus($email['status']) == 0) {
														$log[] = "Email not send to parent -" . $email['email'] . " for Invoice " . $invoiceModel->invoiceUrn . " of child " . $invoiceModel->child->name;
													} else {
														$log[] = "Email successfully send to parent -" . $email['email'] . " for Invoice " . $invoiceModel->invoiceUrn . " of child " . $invoiceModel->child->name;
													}
													ChildInvoice::model()->updateByPk($invoiceModel->id, ['is_email_sent_2' => EymanMandril::getEmailStatus($email['status']), 'email_2_mandrill_id' => $email['_id']]);
												}
											}
										}
									}
									$log[] = "Finished sending Invoice " . $invoiceModel->invoiceUrn . " for child " . $invoiceModel->child->name;
									$log[] = "";
									$log[] = "**---------------------------------------------------------------------------------------------**";
								} else {
									$log[] = "None of the parent email is present or marked as bill payer for invoice " . $invoiceModel->invoiceUrn . " of child " . $invoiceModel->child->name;
								}
							} else {
								$log[] = "Parental details are empty for invoice " . $invoiceModel->invoiceUrn . " of child " . $invoiceModel->child->name;
							}
						} catch (Mandrill_Error $e) {
							$log[] = "Seems some problem sending email from mandrill : " . get_class($e) . " for invoice " . $invoiceModel->invoiceUrn . " of child " . $invoiceModel->child->name;
						}
					}
					if (!file_exists(Yii::app()->basePath . '/../uploaded_images/invoice_logs')) {
						@mkdir(Yii::app()->basePath . '/../uploaded_images/invoice_logs', 0777, true);
						@chmod(Yii::app()->basePath . '/../uploaded_images/invoice_logs', 777);
					}
					$myfile = @fopen(Yii::app()->basePath . '/../uploaded_images/invoice_logs/' . "Invoice-logs-" . date("Y-m-d H:i:s") . ".log", "w");
					@fwrite($myfile, implode("\n", $log));
					@fclose($myfile);
					$logAttachment = ['type' => 'text/plain', 'name' => "Invoice-" . date("Y-m-d H:i:s") . ".log", 'content' => base64_encode(implode("\n", $log))];
					$mandrill = new EymanMandril("Invoice Email Status", "Please find attached file for the invoice logs.", $invoiceSettings->branch->company->name, [['name' => '', 'email' => User::currentUser()->email, 'type' => 'to']], $invoiceSettings->from_email, [$logAttachment]);
					$response = $mandrill->sendEmail();
					Yii::app()->end();
				} else {
					echo CJSON::encode(array('status' => 0, 'message' => ['year' => ['No invoices could be found for the selected criteria.']]));
					Yii::app()->end();
				}
			} else {
				echo CJSON::encode(array('status' => 0, 'message' => $model->getErrors()));
				Yii::app()->end();
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionDeleteAllInvoice($month, $year, $invoice_type) {
		$invoiceModel = ChildInvoice::model()->findAll(['condition' => 'month = :month AND year = :year AND invoice_type = :invoice_type',
			'params' => [':month' => $month, ':year' => $year, ':invoice_type' => $invoice_type]]);
		if (!empty($invoiceModel)) {
			foreach ($invoiceModel as $model) {
				$transaction = Yii::app()->db->beginTransaction();
				try {
					if (!empty($model->childInvoiceTransactions)) {
						throw new Exception('To delete the invoice please remove the allocated payments.');
					}
					$fundingTransactions = ChildFundingTransactions::model()->findAllByAttributes([
						'invoice_id' => $model->id]);
					if (!empty($fundingTransactions)) {
						foreach ($fundingTransactions as $fundingTransaction) {
							$fundingTransaction->invoice_id = NULL;
							$fundingTransaction->funded_hours_used = NULL;
							if (!$fundingTransaction->save()) {
								throw new Exception("Theri seems to be some problem removing the funding.");
							}
						}
					}
					$model->is_deleted = 1;
					if (!$model->save()) {
						throw new Exception("Theri seems to be some problem deleting the invoice.");
					}
					echo "Invoice has been successfully deleted." . "</br>";
					$transaction->commit();
				} catch (Exception $ex) {
					$transaction->rollback();
					echo $ex->getMessage() . "</br>";
				}
			}
		} else {
			throw new CHttpException(404, "This page does not exists.");
		}
	}

	public function actionGetInvoiceAmounts($month, $year) {
		$childPersonalDetailsModel = ChildPersonalDetails::model()->resetScope()->findAllByAttributes([
			'is_deleted' => 0, 'branch_id' => Yii::app()->session['branch_id']], ['order' => 'first_name, last_name']);
		$response = array();
		foreach ($childPersonalDetailsModel as $child) {
			$temp = array();
			$total = 0;
			$actualTotal = 0;
			$model = ChildInvoice::model()->findAll(['condition' => 'child_id = :child_id AND month = :month AND year = :year AND is_regenrated = 0 AND invoice_type IN (0,1)',
				'params' => [':month' => (int) $month, ':year' => $year, ':child_id' => $child->id]]);
			foreach ($model as $invoice) {
				if ($invoice->invoice_type == 0) {
					$total += customFunctions::returnnvoiceAmount($invoice->id);
					$actualTotal += $invoice->total;
				} else {
					$total += $invoice->total;
					$actualTotal += $invoice->total;
				}
			}
			$response[] = array('Name' => $child->name, 'Actual Total' => $actualTotal, 'Db Total' => $total,
				'Amount Difference' => ($actualTotal - $total));
		}
		$csv = new ECSVExport($response);
		$output = $csv->toCSV();
		Yii::app()->getRequest()->sendFile('Invoice_Report.csv', $output, "text/csv", false);
		exit();
	}

	public function actionDeleteRefund() {
		if (Yii::app()->request->isAjaxRequest) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$refundTransaction = ChildInvoiceTransactions::model()->findByPk($_POST['id']);
				if (!empty($refundTransaction)) {
					$refundTransaction->is_deleted = 1;
					if ($refundTransaction->save()) {
						$refundInvoiceModel = ChildInvoice::model()->findByPk($refundTransaction->invoice_id);
						if (!empty($refundInvoiceModel)) {
							if ($refundInvoiceModel->invoice_type == 0 || $refundInvoiceModel->invoice_type == 1) {
								$refundInvoiceModel->status = ChildInvoice::PENDING_PAYMENT;
							}
							if ($refundInvoiceModel->invoice_type == 2 || $refundInvoiceModel->invoice_type == 3) {
								$refundInvoiceModel->status = ChildInvoice::NOT_ALLOCATED;
							}
							if (!$refundInvoiceModel->save()) {
								throw new Exception("There seems to be some problem deleting the refund.");
							}
						}
						$transaction->commit();
						echo CJSON::encode(array('status' => 1, 'message' => 'Refund has been successfully deleted.'));
					} else {
						throw new Exception("There seems to be some problem deleting the refund.");
					}
				} else {
					throw new Exception("There seems to be some problem deleting the refund.");
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				$response = array('status' => '0', 'message' => $ex->getMessage());
				echo CJSON::encode($response);
			}
		} else {
			throw new CHttpException(404, 'Your requets is not valid.');
		}
	}

	public function actionCreateOpeningBalance($child_id) {
		$this->layout = "dashboard";
		$model = new ChildInvoice;
		$model->description = "Opening Balance";
		$personalDetails = ChildPersonalDetails::model()->findByPk($child_id);
		if (isset($_POST['ChildInvoice']) && isset($_POST['Save'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$amount = $_POST['ChildInvoice']['total'];
				if ($amount < 0) {
					$model->setScenario('credit_note');
					$model->attributes = $_POST['ChildInvoice'];
					$paymentModel = new Payments;
					$paymentModel->child_id = $child_id;
					$paymentModel->branch_id = Branch::currentBranch()->id;
					$paymentModel->amount = (-$model->total);
					$paymentModel->date_of_payment = $model->invoice_date;
					$paymentModel->payment_reference = $model->description;
					$paymentModel->payment_mode = 9;
					$paymentModel->status = Payments::ALLOCATED;
					if ($paymentModel->save()) {
						$model->branch_id = $paymentModel->branch_id;
						$model->is_money_received = 1;
						$model->child_id = $child_id;
						$model->status = 'NOT_ALLOCATED';
						$model->invoice_type = ChildInvoice::CREDIT_NOTE;
						$urn = customFunctions::getInvoiceUrn($model->branch_id);
						$model->urn_prefix = $urn['prefix'];
						$model->urn_number = $urn['number'];
						$model->urn_suffix = $urn['suffix'];
						$model->access_token = md5(time() . uniqid() . $urn);
						$model->due_date = $model->invoice_date;
						$model->total = $model->total;
						$model->payment_mode = 9;
						if ($model->save()) {
							$paymentTransactionModel = new PaymentsTransactions;
							$paymentTransactionModel->payment_id = $paymentModel->id;
							$paymentTransactionModel->invoice_id = $model->id;
							$paymentTransactionModel->paid_amount = $paymentModel->amount;
							if ($paymentTransactionModel->save()) {
								$model->credit_note_payment_id = $paymentTransactionModel->id;
								if (!$model->save()) {
									throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
								}
								$transaction->commit();
								$this->redirect(array('childInvoice/index', 'child_id' => $child_id));
							} else {
								throw new Exception(CHtml::errorSummary($paymentTransactionModel, "", "", array(
									'class' => 'customErrors')));
							}
						} else {
							throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
						}
					} else {
						throw new Exception(CHtml::errorSummary($paymentModel, "", "", array('class' => 'customErrors')));
					}
				} else if ($amount > 0) {
					$model->attributes = $_POST['ChildInvoice'];
					$model->branch_id = $personalDetails->branch_id;
					$model->is_money_received = 0;
					$model->child_id = $personalDetails->id;
					$model->status = ChildInvoice::PENDING_PAYMENT;
					$model->invoice_type = ChildInvoice::AUTOMATIC_INVOICE;
					$urn = customFunctions::getInvoiceUrn($model->branch_id);
					$model->urn_prefix = $urn['prefix'];
					$model->urn_number = $urn['number'];
					$model->urn_suffix = $urn['suffix'];
					$model->access_token = md5(time() . uniqid() . $urn);
					$model->due_date = $model->invoice_date;
					$model->month = date("m", strtotime($model->invoice_date));
					$model->year = date("Y", strtotime($model->invoice_date));
					$model->total = $model->total;
					$model->from_date = $model->to_date = $model->invoice_date;
					if ($model->save()) {
						$transaction->commit();
						$this->redirect(array('childInvoice/index', 'child_id' => $child_id));
					} else {
						throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
					}
				} else {
					throw new Exception("Opening Balance Amount can not be zero.");
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				Yii::app()->user->setFlash('error', $ex->getMessage());
				$this->refresh();
			}
		}
		$this->render('createOpeningBalance', array(
			'model' => $model,
			'personalDetails' => $personalDetails
		));
	}

	public function actionUnlockInvoice($id) {
		$model = ChildInvoice::model()->findByPk($id);
		if (!empty($model)) {
			if (!in_array(Yii::app()->session['role'], array('superAdmin', 'accountsAdmin'))) {
				Yii::app()->user->setFlash('error', "Only Accounts Administrator is allowed to unlock the invoice.");
				$this->redirect(array('childInvoice/view', 'child_id' => $model->child_id, 'invoice_id' => $model->id));
			}
			$model->is_locked = 0;
			if ($model->save()) {
				Yii::app()->user->setFlash('success', "Invoice has been successfully Unlocked.");
				$this->redirect(array('childInvoice/view', 'child_id' => $model->child_id, 'invoice_id' => $model->id));
			} else {
				Yii::app()->user->setFlash('error', "There seems to be some problem unlocking the invoice.");
				$this->redirect(array('childInvoice/view', 'child_id' => $model->child_id, 'invoice_id' => $model->id));
			}
		} else {
			throw new CHttpException(404, "This page does not exists.");
		}
	}

	public function actionMarkSessionAsInvoiced($date1, $date2) {
		$model = ChildBookings::model()->with('childBookingsDetails', 'child')->findAll(
			array(
				'condition' => 't.start_date >= :date1 and t.finish_date <= :date2 AND t.is_invoiced =0  and child.is_active = 1 and child.is_deleted = 0',
				'params' => array(':date1' => $date1, ':date2' => $date2)));
		if (!empty($model)) {
			foreach ($model as $booking) {
				$datesOfSession = customFunctions::getDatesOfDays($booking->start_date, $booking->finish_date, array(
						0, 1, 2, 3, 4, 5));
				$dates = customFunctions::getDatesOfDays($booking->start_date, $booking->finish_date, explode(",", $booking->childBookingsDetails->booking_days));
				foreach ($dates as $key => $value) {
					if (in_array($value, $datesOfSession)) {
						echo $booking->child->name . " - " . $booking->branch->name . " - [ " . $booking->start_date . " - " . $booking->finish_date . " ].->  Is Extra" . $booking->exclude_funding . " </br>";
						break;
					}
				}
			}
		}
		//ChildBookings::model()->breakSeries($invoiceFromDate, $invoiceToDate, $childData->branch_id, $childData->id, $bookingsModel);
	}
   
	public function actionGetPayment($id) {
		$gcCustomerClient = customFunctions::getCustomerGoCardlessClient();
		if (!$gcCustomerClient) {
			throw new CHttpException(500, 'Direct Debit Client account does not exist.');
		}
		$payment = $gcCustomerClient->payments()->get($id);
		echo "<pre>";
		print_r($payment);
		//gc_access_token
		//payment_id
	}

	public function actionGetPayout($id) {
		$gcCustomerClient = customFunctions::getCustomerGoCardlessClient();
		if (!$gcCustomerClient) {
			throw new CHttpException(500, 'Direct Debit Client account does not exist.');
		}
		$payout = $gcCustomerClient->payouts()->get($id);
		echo "<pre>";
		print_r($payout);
		//gc_access_token
		//payment_id
	}

	public function actionSaveChildInvoiceSetting() {
		if (Yii::app()->request->isAjaxRequest) {
			$childSettings = ChildSettings::model()->findByAttributes(['child_id' => $_POST['ChildSettings']['child_id'], 'is_deleted' => 0]);
			if (empty($childSettings)) {
				$childSettings = new ChildSettings();
			}
			$childSettings->attributes = $_POST['ChildSettings'];
			if ($childSettings->validate() && $childSettings->save()) {
				echo CJSON::encode(array('status' => 1, 'message' => "Invoice setting has been successfully saved."));
			} else {
				echo CJSON::encode(array('status' => 0, 'message' => $childSettings->getErrors()));
			}
			Yii::app()->end();
		}
	}

	public function actionRemoveChildInvoiceSetting() {
		if (Yii::app()->request->isAjaxRequest) {
			if (ChildSettings::model()->updateAll(['is_deleted' => 1], 'child_id = :child_id', [':child_id' => $_POST['ChildSettings']['child_id']])) {
				echo CJSON::encode(array('status' => 1, 'message' => "Invoice setting has been succesfully removed."));
			} else {
				echo CJSON::encode(array('status' => 0, 'message' => "Some problem occur while removing the invoice settings."));
			}
			Yii::app()->end();
		}
	}

	public function actionGetChildInvoiceSetting() {
		if (Yii::app()->request->isAjaxRequest && isset($_POST['child_id'])) {
			$childInvoiceSettingModel = ChildSettings::model()->findByAttributes(['child_id' => $_POST['child_id'], 'is_deleted' => 0]);
			$isInvoiceDetail = false;
			$invoiceSetting = '';
			if (empty($childInvoiceSettingModel)) {
				$childInvoiceSettingModel = Branch::currentBranch()->invoiceSettings;
				$isInvoiceDetail = true;
			}
			echo CJSON::encode(array('status' => 1, 'invoiceSetting' => $childInvoiceSettingModel, 'isInvoiceDetail' => $isInvoiceDetail));
			Yii::app()->end();
		}
	}

	public function actionSaveCalculatedMonthlyInvoice() {
		if (Yii::app()->request->isAjaxRequest && isset($_POST['child_id'])) {
			$childPersonalDetail = ChildPersonalDetailsNds::model()->updateByPk($_POST['child_id'], ['monthly_invoice_start_date' => $_POST['invoice_from_date'],
				'monthly_invoice_finish_date' => $_POST['invoice_to_date'], 'monthly_invoice_amount' => $_POST['amount']]);

			echo CJSON::encode(array('status' => 1));
			Yii::app()->end();
		}
	}

}
