<?php

Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseUrl . '/js/childFundingDetails.js?version=1.0.1', CClientScript::POS_END);

class ChildFundingDetailsController extends eyManController {

	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout = '//layouts/dashboard';

	public function filters() {
		return array(
			'rights',
		);
	}

	public function allowedActions() {

		return '';
	}

	public function actionView() {
		if (Yii::app()->request->isAjaxRequest) {
			$model = ChildFundingDetails::model()->findByPk(Yii::app()->request->getPost('id'));
			if (!empty($model)) {
				$response = array();
				$usedHours = sprintf("%0.2f", customFunctions::getUsedFundedHours($model->id));
				$balanceHours = sprintf("%0.2f", ($model->funded_hours - $usedHours));
				$response['branchCalendar'] = array();
				$criteria = new CDbCriteria();
				$criteria->condition = "((start_date >= :start_date and start_date <= :finish_date) OR " .
					"(finish_date >= :start_date and finish_date <= :finish_date) OR" .
					"(start_date <= :start_date and finish_date >= :finish_date)) AND is_funding_applicable = 0";
				$criteria->params = array(':start_date' => date("Y-m-d", strtotime($model->term->start_date)), ':finish_date' => date("Y-m-d", strtotime($model->term->finish_date)));
				$branchHolidayModel = BranchCalendar::model()->findAll($criteria);
				if (!empty($branchHolidayModel)) {
					foreach ($branchHolidayModel as $branchHoliday) {
						$response['branchCalendar'][] = $branchHoliday;
					}
				}
				$response['balance'] = $balanceHours;
				$response['used'] = $usedHours;
				$response['termModel'] = $model->term;
				$response['model'] = $model;
				$response['status'] = 1;
				$response['terms'] = Terms::model()->findAllByAttributes(['branch_id' => $model->branch_id, 'is_active' => 1, 'is_global' => 0]);
				echo CJSON::encode($response);
			} else {
				echo CJSON::encode(array('status' => 0, 'message' => 'Their seems to be some problem.'));
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	public function actionUpdate() {
		if (Yii::app()->request->isAjaxRequest) {
			$model = ChildFundingDetails::model()->findByPk(Yii::app()->request->getPost('id'));
			if (!empty($model)) {
				$response = array();
				$usedHours = sprintf("%0.2f", customFunctions::getUsedFundedHours($model->id));
				$balanceHours = sprintf("%0.2f", ($model->funded_hours - $usedHours));
				$response['branchCalendar'] = array();
				$criteria = new CDbCriteria();
				$criteria->condition = "((start_date >= :start_date and start_date <= :finish_date) OR " .
					"(finish_date >= :start_date and finish_date <= :finish_date) OR" .
					"(start_date <= :start_date and finish_date >= :finish_date)) AND is_funding_applicable = 0";
				$criteria->params = array(':start_date' => date("Y-m-d", strtotime($model->term->start_date)), ':finish_date' => date("Y-m-d", strtotime($model->term->finish_date)));
				$branchHolidayModel = BranchCalendar::model()->findAll($criteria);
				if (!empty($branchHolidayModel)) {
					foreach ($branchHolidayModel as $branchHoliday) {
						$response['branchCalendar'][] = $branchHoliday;
					}
				}
				$response['balance'] = $balanceHours;
				$response['used'] = $usedHours;
				$response['termModel'] = $model->term;
				$response['model'] = $model;
				$response['status'] = 1;
				$response['terms'] = Terms::model()->findAllByAttributes(['branch_id' => $model->branch_id, 'is_active' => 1, 'is_global' => 0]);
				echo CJSON::encode($response);
			} else {
				echo CJSON::encode(array('status' => 0, 'message' => 'Their seems to be some problem.'));
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	public function actionUpdateFunding() {
		if (Yii::app()->request->isAjaxRequest) {
			$model = $this->loadModel(Yii::app()->request->getPost('funding_id'));
			if (!empty($model)) {
				$transaction = Yii::app()->db->beginTransaction();
				try {
					$fundingTransactionModel = ChildFundingTransactions::model()->findAll([
						'condition' => 'funding_id = :funding_id AND invoice_id is NOT NULL',
						'params' => ['funding_id' => $model->id]
					]);
					if (!empty($fundingTransactionModel)) {
						foreach ($fundingTransactionModel as $fundingTransaction) {
							if (ChildInvoice::model()->findByPk($fundingTransaction->invoice_id)->is_locked == 1) {
								throw new Exception("Funding can not be updated as invoice are already locked.");
							}
						}
					}
					$weekCount = $model->week_count;
					$model->attributes = $_POST['ChildFundingDetails'];
					$model->sf = $_POST['sf'];
					$model->pdf = $_POST['pdf'];
					$model->week_count = $weekCount;
					if (isset($_POST['ChildFundingDetails']['week_count']) && !empty($_POST['ChildFundingDetails']['week_count'])) {
						$model->week_count = $_POST['ChildFundingDetails']['week_count'];
					}
					if ($model->save()) {
						$this->actionUpdateFundingTransactionsWeekly($model->term, $model);
						$transaction->commit();
						echo CJSON::encode(array('status' => 1, 'message' => 'Funding has been successfully updated.'));
					} else {
						$transaction->rollback();
						echo CJSON::encode(array('status' => 0, 'message' => $model->getErrors()));
					}
				} catch (Exception $ex) {
					$transaction->rollback();
					echo CJSON::encode(['status' => 2, 'message' => $ex->getMessage()]);
				}
			} else {
				echo CJSON::encode(array('status' => 0, 'response' => 'This page does not exists.'));
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate() {
		if (Yii::app()->request->isAjaxRequest) {
			$model = new ChildFundingDetails;
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model->attributes = $_POST['ChildFundingDetails'];
				$model->sf = $_POST['sf'];
				$model->pdf = $_POST['pdf'];
				$model->week_count = $_POST['ChildFundingDetails']['week_count'];
				if ($model->save()) {
					$this->actionCreateFundingTransactionsWeekly($model->term, $model);
					$transaction->commit();
					echo CJSON::encode(array('status' => 1, 'message' => 'Funding has been successfully created.'));
				} else {
					$transaction->rollback();
					echo CJSON::encode(array('status' => 0, 'message' => $model->getErrors()));
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				echo CJSON::encode(array('status' => 0, 'message' => CJSON::encode($ex)));
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete() {
		if (Yii::app()->request->isAjaxRequest) {
			$response = array('status' => '1');
			$model = $this->loadModel($_POST['id']);
			if (ChildFundingDetails::model()->updateByPk($model->id, ['is_deleted' => 1])) {
				$fundingTransactionsModel = ChildFundingTransactions::model()->updateAll(['is_deleted' => 1], "funding_id = :funding_id", [':funding_id' => $_POST['id']]);
				echo CJSON::encode($response);
			} else {
				$response = array('status' => '0');
				echo CJSON::encode($response);
			}
		}
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex() {
		$this->pageTitle = 'Funding Details | eyMan';
		$model = new ChildFundingDetails('search');
		$model->unsetAttributes();
		$termModel = new Terms;
		if (isset($_GET['ChildFundingDetails']))
			$model->attributes = $_GET['ChildFundingDetails'];

		$this->render('index', array(
			'model' => $model,
			'termModel' => $termModel
		));
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin() {
		$model = new ChildFundingDetails('search');
		$model->unsetAttributes();	// clear any default values
		if (isset($_GET['ChildFundingDetails']))
			$model->attributes = $_GET['ChildFundingDetails'];

		$this->render('admin', array(
			'model' => $model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return ChildFundingDetails the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id) {
		$model = ChildFundingDetails::model()->findByPk($id);
		if ($model === null)
			throw new CHttpException(404, 'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param ChildFundingDetails $model the model to be validated
	 */
	protected function performAjaxValidation($model) {
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'child-funding-details-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	/**
	 * function to get all terms in a year ,when a year is selected
	 * @throws CHttpException
	 */
	public function actionGetFundingTerms() {
		if (isset($_POST) && ($_POST['isAjaxRequest'] == 1)) {

			$year = $_POST['year'];
			$terms = Terms::model()->findAllByAttributes(array('year' => $year, 'branch_id' => Yii::app()->session['branch_id']));
			if (!empty($terms)) {

				$term_arr = array();
				foreach ($terms as $term) {
					$termarr['id'] = $term->id;
					$termarr['name'] = $term->name;
					array_push($term_arr, $termarr);
				}
				$response = array('status' => 1, 'terms' => $term_arr);
			} else {
				$response = array('status' => 0, 'terms' => '');
			}
			echo CJSON::encode($response);
		} else {
			throw new CHttpException(404, "Your request is not valid");
		}
	}

	public function actionGetFundingTermsDetails() {
		if (Yii::app()->request->isAjaxRequest) {
			$termModel = Terms::model()->findByPk($_POST['term']);
			if (!empty($termModel)) {
				$response = array();
				$response['status'] = 1;
				$response['term'] = $termModel;
				$response['branchCalendar'] = array();
				$criteria = new CDbCriteria();
				$criteria->condition = "((start_date >= :start_date and start_date <= :finish_date) OR " .
					"(finish_date >= :start_date and finish_date <= :finish_date) OR" .
					"(start_date <= :start_date and finish_date >= :finish_date)) AND is_funding_applicable = 0 AND branch_id = :branch_id";
				$criteria->params = array(':start_date' => date("Y-m-d", strtotime($termModel->start_date)), ':finish_date' => date("Y-m-d", strtotime($termModel->finish_date)), ':branch_id' => $termModel->branch->id);
				$branchHolidayModel = BranchCalendar::model()->findAll($criteria);
				if (!empty($branchHolidayModel)) {
					foreach ($branchHolidayModel as $branchHoliday) {
						$response['branchCalendar'][] = $branchHoliday;
					}
				}
				echo CJSON::encode($response);
			} else {
				echo CJSON::encode(array('status' => 0, 'message' => array('term_id' => 'Selected term is not present on the system.')));
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public static function getDates($start_date, $finish_date) {
		$date_from = $start_date;
		$date_from = strtotime($date_from); // Convert date to a UNIX timestamp
		// Specify the end date. This date can be any English textual format
		$date_to = $finish_date;
		$date_to = strtotime($date_to); // Convert date to a UNIX timestamp
		// Loop from the start date to end date and output all dates inbetween
		for ($i = $date_from; $i <= $date_to; $i += 86400) {
			$all_dates[] = date("d-m-Y", $i);
		}
		return $all_dates;
	}

	public function actionUpdateWeeklyFunding($id, $child_id) {
		$fundingDetailsModel = ChildFundingDetails::model()->findByPk($id);
		$max_funded_hours_weekly = $fundingDetailsModel->term->branch->maximum_funding_hours_week;
		$total_funded_hours_term = $max_funded_hours_weekly * $fundingDetailsModel->week_count;
		$model = ChildFundingTransactions::model()->findAll([
			'select' => '*, group_concat(invoice_id) group_invoice_id, funded_hours_avaliable group_funded_hours_avaliable, sum(funded_hours_used) group_funded_hours_used',
			'condition' => 'funding_id = :funding_id',
			'group' => 'week_start_date',
			'params' => array(':funding_id' => $id),
			'order' => 'week_start_date'
		]);
		if (!empty($model)) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				if (isset($_POST['Update_Funding']) && isset($_POST['ChildFundingTransactions']['funded_hours_avaliable'])) {
					foreach ($_POST['ChildFundingTransactions']['funded_hours_avaliable'] as $key => $value) {
						$fundingTransactionModel = ChildFundingTransactions::model()->findByPk($key);
						$total_funded_hours += $fundingTransactionModel->funded_hours_avaliable;
						$fundingTransactionModel = ChildFundingTransactions::model()->findAllByAttributes(['funding_id' => $fundingTransactionModel->funding_id, 'week_start_date' => $fundingTransactionModel->week_start_date, 'week_finish_date' => $fundingTransactionModel->week_finish_date]);
						$thisTransactionUsed = 0;
						foreach ($fundingTransactionModel as $fundingTransaction) {
							$thisTransactionUsed += $fundingTransaction->funded_hours_used;
							if ($value > $max_funded_hours_weekly) {
								throw new Exception("Funded hours for week " . date("d-m-Y", strtotime($fundingTransaction->week_start_date)) . " cannot be more than  " . $max_funded_hours_weekly);
							}
							$fundingTransaction->funded_hours_avaliable = $value;
							if ($fundingTransaction->funded_hours_avaliable < $fundingTransaction->funded_hours_used) {
								throw new Exception("Funded hours for week " . date("d-m-Y", strtotime($fundingTransaction->week_start_date)) . " cannot be less than used by invoice. Please try deleting the invoice and then change funded hours.");
							}
							if (!$fundingTransaction->save()) {
								throw new Exception(CHtml::errorSummary($fundingTransaction, "", "", array('class' => 'customErrors')));
							}
						}
					}
					$previousTermFundedHours = $fundingDetailsModel->funded_hours;
					$total_funded_hours_array = array();
					$transactionModel = ChildFundingTransactions::model()->findAllByAttributes(['funding_id' => $id]);
					if (!empty($transactionModel)) {
						foreach ($transactionModel as $transactionSum) {
							$total_funded_hours_array[$transactionSum->week_start_date] = $transactionSum->funded_hours_avaliable;
						}
					}
					$fundingDetailsModel->funded_hours = customFunctions::round(array_sum(array_values($total_funded_hours_array)), 2);
					if ($fundingDetailsModel->funded_hours > $total_funded_hours_term) {
						throw new Exception("Total funded hours for term can not be greater than " . $total_funded_hours_term);
					}
					if (!$fundingDetailsModel->save()) {
						throw new Exception(CHtml::errorSummary($fundingDetailsModel, "", "", array('class' => 'customErrors')));
					}
					$transaction->commit();
					$this->redirect(array('childFundingDetails/index', 'child_id' => $child_id));
				}
				$funded_hours_used = customFunctions::getUsedFundedHours($id);
				$this->render('updateWeeklyFunding', array('model' => $model, 'fundingDetailsModel' => $fundingDetailsModel, 'funded_hours_used' => sprintf("%0.2f", $funded_hours_used), 'total_funded_hours' => sprintf("%0.2f", $fundingDetailsModel->funded_hours)));
			} catch (Exception $ex) {
				$transaction->rollback();
				Yii::app()->user->setFlash('error', $ex->getMessage());
				$this->refresh();
			}
		} else {
			throw new CHttpException(404, 'The requested page does not exists.');
		}
	}

	public function actionCalculateEntitlement() {
		if (Yii::app()->request->isAjaxRequest) {
			$termModel = Terms::model()->findByPk($_POST['term_id']);
			$weekDays = ($termModel->branch->is_funding_applicable_on_weekend == 1) ? array(0, 1, 2, 3, 4, 5, 6) : array(1, 2, 3, 4, 5);
			$branchHolidays = customFunctions::getBranchFundingNotApplicableHolidays(date("Y-m-d", strtotime($termModel->start_date)), date('Y-m-d', strtotime($termModel->finish_date)));
			$week_in_term = customFunctions::getWeekBetweenDate($termModel->start_date, $termModel->finish_date);
			$holidayDays = array();
			if (!empty($termModel)) {
				if ($_POST['sf'] == 0) {
					if (isset($termModel->holiday_start_date_1) && isset($termModel->holiday_finish_date_1)) {
						$holiday1Days = customFunctions::getDatesOfDays($termModel->holiday_start_date_1, $termModel->holiday_finish_date_1, $weekDays);
						foreach ($holiday1Days as $holiday1) {
							array_push($holidayDays, $holiday1);
						}
					}
				}
				if ($_POST['sf'] == 0) {
					if (isset($termModel->holiday_start_date_2) && isset($termModel->holiday_finish_date_2)) {
						$holiday2Days = customFunctions::getDatesOfDays($termModel->holiday_start_date_2, $termModel->holiday_finish_date_2, $weekDays);
						foreach ($holiday2Days as $holiday2) {
							array_push($holidayDays, $holiday2);
						}
					}
				}
				if ($_POST['sf'] == 0) {
					if (isset($termModel->holiday_start_date_3) && isset($termModel->holiday_finish_date_3)) {
						$holiday3Days = customFunctions::getDatesOfDays($termModel->holiday_start_date_3, $termModel->holiday_finish_date_3, $weekDays);
						foreach ($holiday3Days as $holiday3) {
							array_push($holidayDays, $holiday3);
						}
					}
				}
				sort($holidayDays);
				$week_to_reduce = 0;
				if ($_POST['sf'] == 0) {
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
				if ($_POST['sf'] == 1) {
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
				if ($_POST['calculation_type'] == 0) {
					$entitlement = ($termModel->branch->is_round_off_entitlement == 1) ? customFunctions::roundToPointFive($_POST['entitlement'] / $week_count) : sprintf("%0.2f", $_POST['entitlement'] / $week_count);
					echo CJSON::encode(array('status' => 1, 'week_count' => $week_count, 'entitlement' => $entitlement, 'calculated_entitlement' => sprintf("%0.2f", $_POST['entitlement'] / $week_count)));
				} else if ($_POST['calculation_type'] == 1) {
					$entitlement = ($termModel->branch->is_round_off_entitlement == 1) ? customFunctions::roundToPointFive($_POST['entitlement'] * $week_count) : sprintf("%0.2f", $_POST['entitlement'] * $week_count);
					echo CJSON::encode(array('status' => 1, 'week_count' => $week_count, 'entitlement' => $entitlement, 'calculated_entitlement' => sprintf("%0.2f", $_POST['entitlement'] * $week_count)));
				} else {
					echo CJSON::encode(array('status' => 0, 'message' => 'Their seems to be some problem calulating the entitlement.'));
				}
			} else {
				echo CJSON::encode(array('status' => 0, 'message' => array('term_id' => 'Selected term is not present.')));
			}
		} else {
			throw new CHttpException(404, "You are not allowed to access this page.");
		}
	}

	public function actionCreateFundingTransactionsWeekly($termModel, $model) {
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
			echo CJSON::encode(array('status' => 0, 'message' => array('term_id' => 'Selected term does not exists.')));
		}
	}

	public function actionUpdateFundingTransactionsWeekly($termModel, $model) {
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
						$fundingTransactionModel = ChildFundingTransactions::model()->findAllByAttributes(['funding_id' => $model->id, 'week_start_date' => $week['week_start_date'], 'week_finish_date' => $week['week_end_date']]);
						foreach ($fundingTransactionModel as $fundingTransaction) {
							$fundingTransaction->funded_hours_avaliable = $model->funded_hours_weekly;
							if ($currentWeekDaysInTermCount == 0) {
								$fundingTransaction->type_of_week = ChildFundingTransactions::HOLIDAY_WEEK;
								$fundingTransaction->funded_hours_avaliable = 0;
							} else if ($currentWeekDaysInTermCount > 0 && $currentWeekDaysInTermCount < $weekDaysCount && $weekDaysCount != $currentWeekDaysInTermCount) {
								$fundingTransaction->type_of_week = ChildFundingTransactions::PARTIAL_HOLIDAY_WEEK;
							} else {
								$fundingTransaction->type_of_week = ChildFundingTransactions::TERM_TIME_WEEK;
							}
							if (!$fundingTransaction->save()) {
								throw new Exception("Their seems to be some problem creating the funding.");
							}
						}
					} else {
						$currentWeekDaysInTermCount = array();
						$daysThisWeek = customFunctions::getDatesOfDays($week['actual_week_start_date'], $week['actual_week_end_date'], $weekDays);
						$daysThisWeek = array_diff($daysThisWeek, $branchHolidays);
						$currentWeekDaysInTermCount = count(array_diff($daysThisWeek, $holidayDays));
						$fundingTransactionModel = ChildFundingTransactions::model()->findAllByAttributes(['funding_id' => $model->id, 'week_start_date' => $week['week_start_date'], 'week_finish_date' => $week['week_end_date']]);
						foreach ($fundingTransactionModel as $fundingTransaction) {
							$fundingTransaction->funded_hours_avaliable = ($currentWeekDaysInTermCount * $model->funded_hours_weekly) / $weekDaysCount;
							if ($currentWeekDaysInTermCount == 0) {
								$fundingTransaction->type_of_week = ChildFundingTransactions::HOLIDAY_WEEK;
								$fundingTransaction->funded_hours_avaliable = 0;
							} else if ($currentWeekDaysInTermCount > 0 && $currentWeekDaysInTermCount < $weekDaysCount) {
								$fundingTransaction->type_of_week = ChildFundingTransactions::PARTIAL_HOLIDAY_WEEK;
							} else {
								$fundingTransaction->type_of_week = ChildFundingTransactions::TERM_TIME_WEEK;
							}
							if (!$fundingTransaction->save()) {
								throw new Exception("Their seems to be some problem creating the funding.");
							}
						}
					}
				}
			} else {
				foreach ($week_in_term as $week) {
					if ($termModel->term_type == Terms::COMPLETE_WEEK_TERM) {
						$daysThisWeek = customFunctions::getDatesOfDays($week['week_start_date'], $week['week_end_date'], $weekDays);
						$fundingTransactionModel = ChildFundingTransactions::model()->findAllByAttributes(['funding_id' => $model->id, 'week_start_date' => $week['week_start_date'], 'week_finish_date' => $week['week_end_date']]);
						foreach ($fundingTransactionModel as $fundingTransaction) {
							$fundingTransaction->funded_hours_avaliable = $model->funded_hours_weekly;
							$fundingTransaction->type_of_week = ChildFundingTransactions::TERM_TIME_WEEK;
							if (!$fundingTransaction->save()) {
								throw new Exception("Their seems to be some problem creating the funding.");
							}
						}
					} else {
						$daysThisWeek = customFunctions::getDatesOfDays($week['actual_week_start_date'], $week['actual_week_end_date'], $weekDays);
						$fundingTransactionModel = ChildFundingTransactions::model()->findAllByAttributes(['funding_id' => $model->id, 'week_start_date' => $week['week_start_date'], 'week_finish_date' => $week['week_end_date']]);
						foreach ($fundingTransactionModel as $fundingTransaction) {
							$fundingTransaction->funded_hours_avaliable = (count($daysThisWeek) * $model->funded_hours_weekly) / count($weekDays);
							$fundingTransaction->type_of_week = ChildFundingTransactions::TERM_TIME_WEEK;
							if (!$fundingTransaction->save()) {
								throw new Exception("Their seems to be some problem creating the funding.");
							}
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
			echo CJSON::encode(array('status' => 0, 'message' => array('term_id' => 'Selected term does not exists.')));
		}
	}

	public function actionUpdateFundingTransaction() {
		ini_set("max_execution_time", 0);
		$fundingModel = ChildFundingDetails::model()->findAllByAttributes(['term_id' => 3]);
		foreach ($fundingModel as $model) {
			$this->actionCreateFundingTransactionsWeekly($model->term, $model);
		}
	}

	public function actionUpdateData() {
		ini_set("max_execution_time", 0);
		$model = ChildFundingTransactions::model()->findAllByAttributes(['is_deleted' => 0]);
		foreach ($model as $transaction) {
			$weekModel = ChildFundingTransactions::model()->findAllByAttributes(['funding_id' => $transaction->funding_id, 'week_start_date' => $transaction->week_start_date, 'is_deleted' => 2]);
			foreach ($weekModel as $week) {
				$week->invoice_id = $transaction->invoice_id;
				$week->funded_hours_used = $transaction->funded_hours_used;
				if ($week->save()) {
					echo "Details saved for week id - " . $week->id . "</br>";
				} else {
					echo "Details not saved for week id - " . $week->id . "</br>";
				}
			}
		}
	}

}
