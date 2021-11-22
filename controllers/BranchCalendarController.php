<?php

Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseUrl . '/js/branchCalendar.js?version=1.0.4', CClientScript::POS_END);

class BranchCalendarController extends eyManController {

	/**
	 *
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 *      using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout = '//layouts/dashboard';

	/**
	 *
	 * @return array action filters
	 */
	public function filters() {
		return array(
			'rights'
		);
	}

	public function allowedActions() {
		return '';
	}

	/**
	 * Displays a particular model.
	 *
	 * @param integer $id
	 *            the ID of the model to be displayed
	 */
	public function actionView() {
		$model = BranchCalendar::model()->findAllByAttributes(array(
			'branch_id' => Yii::app()->session['branch_id'],
			'is_deleted' => 0
		));
		$data = array();
		foreach ($model as $holiday) {
			$temp = array();
			$temp['id'] = $holiday->id;
			$temp['title'] = $holiday->name;
			$temp['description'] = $holiday->description;
			$temp['start'] = date('Y-m-d', strtotime($holiday->start_date));
			$temp['end'] = date('Y-m-d', strtotime($holiday->finish_date . "+1 days"));
			$temp['allDay'] = true;
			$temp['isHoliday'] = $holiday->is_holiday;
			$temp['backgroundColor'] = '#EEEDC6';
			$temp['isFundingApplicable'] = $holiday->is_funding_applicable;
			$temp['isTermHoliday'] = 0;
			array_push($data, $temp);
		}
		/**
		 * Adding terms in the background*
		 */
		$termsModel = Terms::model()->findAllByAttributes(array(
			'branch_id' => Yii::app()->session['branch_id']
		));
		foreach ($termsModel as $term) {
			$temp = array();
			$temp['id'] = "term_" . $term->id;
			$temp['start'] = date('Y-m-d', strtotime($term->start_date));
			$temp['end'] = date('Y-m-d', strtotime($term->finish_date . "+1 days"));
			$temp['backgroundColor'] = $term->color;
			$temp['rendering'] = 'background';
			array_push($data, $temp);
		}
		/**
		 * Adding term holidays in the background*
		 */
		foreach ($termsModel as $termHoliday) {
			if ($termHoliday->holiday_start_date_1 != NULL && $termHoliday->holiday_finish_date_1 != NULL) {
				$temp = array();
				$temp['id'] = "termHoliday_" . $termHoliday->id;
				$temp['title'] = "Terms/Funding Holiday";
				$temp['name'] = $termHoliday->name;
				$temp['description'] = $termHoliday->description;
				$temp['start'] = date('Y-m-d', strtotime($termHoliday->holiday_start_date_1));
				$temp['end'] = date('Y-m-d', strtotime($termHoliday->holiday_finish_date_1 . "+1 days"));
				$temp['allDay'] = true;
				$temp['isTermHoliday'] = 1;
				$temp['backgroundColor'] = '#EEEDC6';
				array_push($data, $temp);
			}
			if ($termHoliday->holiday_start_date_2 != NULL && $termHoliday->holiday_finish_date_2 != NULL) {
				$temp = array();
				$temp['id'] = "termHoliday_" . $termHoliday->id;
				$temp['title'] = "Terms/Funding Holiday";
				$temp['name'] = $termHoliday->name;
				$temp['description'] = $termHoliday->description;
				$temp['start'] = date('Y-m-d', strtotime($termHoliday->holiday_start_date_2));
				$temp['end'] = date('Y-m-d', strtotime($termHoliday->holiday_finish_date_2 . "+1 days"));
				$temp['allDay'] = true;
				$temp['isTermHoliday'] = 1;
				$temp['backgroundColor'] = '#EEEDC6';
				array_push($data, $temp);
			}
			if ($termHoliday->holiday_start_date_3 != NULL && $termHoliday->holiday_finish_date_3 != NULL) {
				$temp = array();
				$temp['id'] = "termHoliday_" . $termHoliday->id;
				$temp['title'] = "Terms/Funding Holiday";
				$temp['name'] = $termHoliday->name;
				$temp['description'] = $termHoliday->description;
				$temp['start'] = date('Y-m-d', strtotime($termHoliday->holiday_start_date_3));
				$temp['end'] = date('Y-m-d', strtotime($termHoliday->holiday_finish_date_3 . "+1 days"));
				$temp['allDay'] = true;
				$temp['isTermHoliday'] = 1;
				$temp['backgroundColor'] = '#EEEDC6';
				$data[] = $temp;
			}
		}

		if (isset($_POST['isStaffHolidays']) && $_POST['isStaffHolidays'] == 1) {
			$staffHolidaysModels = StaffHolidays::model()->findAll([
				'condition' => 't.branch_id = :branch_id AND
                    ((t.start_date >= :start_date and t.start_date <= :return_date) OR
                    (t.return_date >= :start_date and t.return_date <= :return_date) OR
                    (t.start_date <= :start_date and t.return_date >= :return_date)) AND t.branch_calendar_holiday_id is NULL',
				'params' => [
					':branch_id' => Branch::currentBranch()->id,
					':start_date' => date("Y-m-d", strtotime($_POST['start'])),
					':return_date' => date("Y-m-d", strtotime($_POST['end']))
				]
			]);
			if (!empty($staffHolidaysModels)) {
				foreach ($staffHolidaysModels as $staffHolidaysModel) {
					$temp = array();
					$temp['id'] = "staffHoliday_" . $staffHolidaysModel->id;
					$temp['title'] = $staffHolidaysModel->staffHolidaysType->type_of_absence ." ( " . $staffHolidaysModel->staffNds->name . " )";
					$temp['name'] = "Staff Holidays";
					$temp['description'] = $staffHolidaysModel->description;
					$temp['start'] = date('Y-m-d', strtotime($staffHolidaysModel->start_date));
					$temp['end'] = date('Y-m-d', strtotime($staffHolidaysModel->return_date . "+1 days"));
					$temp['allDay'] = true;
					$temp['isStaffHoliday'] = 1;
					$temp['backgroundColor'] = $staffHolidaysModel->staffHolidaysType->color;
					$temp['borderColor'] = '#000000';
					$data[] = $temp;
				}
			}
		}

		echo CJSON::encode($data);
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate() {
		if (Yii::app()->request->isAjaxRequest) {
			$response = array(
				'status' => 1,
				'message' => 'Branch calendar holiday has been successfully created.'
			);
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model = new BranchCalendar();
				$model->attributes = $_POST['BranchCalendar'];
				$model->start_date = date('Y-m-d', strtotime($model->start_date));
				$model->finish_date = date('Y-m-d', strtotime($model->finish_date));
				$model->branch_id = Branch::currentBranch()->id;
				if ($model->save()) {
					if ($model->is_holiday == 1) {
						$staffModel = StaffPersonalDetails::model()->findAllByAttributes(array(
							'branch_id' => $model->branch_id,
							'is_casual_staff' => 0,
						));
						if (!empty($staffModel)) {
							foreach ($staffModel as $staff) {
								if (empty($staff->start_date)) {
									continue;
								}
								$staffHolidaysEntitlementModel = StaffHolidaysEntitlement::model()->findByAttributes(array(
									'staff_id' => $staff->id,
									'year' => date("Y", strtotime($model->start_date))
								));
								if (!empty($staffHolidaysEntitlementModel)) {
									$staffHolidayEntitlementEvents = StaffHolidaysEntitlementEvents::model()->find(array(
										'condition' => ':date BETWEEN start_date and finish_date and holiday_id = :holiday_id',
										'params' => array(
											':holiday_id' => $staffHolidaysEntitlementModel->id,
											':date' => date("Y-m-d", strtotime($model->start_date))
										)
									));
									if (!empty($staffHolidayEntitlementEvents)) {
										if ($staffHolidayEntitlementEvents->no_of_days == 0) {
											continue;
										}
										$holidayDates = customFunctions::getDatesOfDays($model->start_date, $model->finish_date, explode(",", $model->branch->nursery_operation_days));
										$staffWorkingDays = customFunctions::getDatesOfDays($model->start_date, $model->finish_date, $staff->getStaffWorkingDays());
										$holidayDates = array_intersect($holidayDates, $staffWorkingDays);
										if (!empty($holidayDates)) {
											foreach ($holidayDates as $holidayDate) {
												if (!empty($staff->start_date)) {
													if (strtotime(date("Y-m-d", strtotime($staff->start_date))) > strtotime(date("Y-m-d", strtotime($holidayDate)))) {
														continue;
													}
												}
												if (!empty($staff->leave_date)) {
													if (strtotime(date("Y-m-d", strtotime($holidayDate))) > strtotime(date("Y-m-d", strtotime($staff->leave_date)))) {
														continue;
													}
												}
												$checkHolidayExists = StaffHolidays::model()->find([
													'condition' => '(:start_date BETWEEN start_date and return_date) AND staff_id = :staff_id',
													'params' => [
														':start_date' => date("Y-m-d", strtotime($holidayDate)),
														':staff_id' => $staff->id
													]
												]);
												if ($checkHolidayExists) {
													continue;
												}
												/*												 * Check Maternity Event Exists* */
												$staffEventsModel = StaffEventDetails::model()->with('event')->find([
													'condition' => 'event.is_systen_event = 1 AND event.name = :name AND t.staff_id = :staff_id AND :holiday_date BETWEEN t.title_date_1_value AND t.title_date_2_value',
													'params' => [
														':staff_id' => $staff->id,
														':name' => 'Maternity',
														':holiday_date' => date("Y-m-d", strtotime($holidayDate))
													]
												]);
												if (!empty($staffEventsModel)) {
													continue;
												}
												$staffHolidaysModel = new StaffHolidays();
												$staffHolidaysModel->scenario = "branch_calendar_holiday";
												$staffHolidaysModel->branch_id = $model->branch_id;
												$staffHolidaysModel->start_date = $holidayDate;
												$staffHolidaysModel->return_date = $holidayDate;
												$staffHolidaysModel->today_date = date("Y-m-d");
												$staffHolidaysModel->staff_id = $staff->id;
												$staffHolidaysModel->staff_holidays_type_id = 1;
												$staffHolidaysModel->staff_holidays_reason_id = 1;
												$staffHolidaysModel->description = !empty($model->description) ? $model->name . " (" . $model->description . ")" : $model->name;
												$staffHolidaysModel->branch_calendar_holiday_id = $model->id;
												$staffHolidaysModel->holiday_hours = customFunctions::round(($staffHolidayEntitlementEvents->contract_hours / $staffHolidayEntitlementEvents->no_of_days) * (((strtotime(date("Y-m-d", strtotime($holidayDate))) - strtotime(date("Y-m-d", strtotime($holidayDate)))) / 86400) + 1), 2);
												if (!$staffHolidaysModel->save()) {
													continue;
												}
											}
										}
									}
								}
							}
						}
					}
					$transaction->commit();
					echo CJSON::encode($response);
				} else {
					throw new JsonException($model->getErrors());
				}
			} catch (JsonException $ex) {
				$transaction->rollback();
				echo CJSON::encode($ex->getOptions());
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid');
		}
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 *
	 * @param integer $id
	 * the ID of the model to be updated
	 */
	public function actionUpdate($id) {
		if (Yii::app()->request->isAjaxRequest) {
			$response = array(
				'status' => 1,
				'message' => 'Branch calendar holiday has been successfully updated.'
			);
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model = $this->loadModel($id);
				$model->attributes = $_POST['BranchCalendar'];
				$model->start_date = date('Y-m-d', strtotime($model->start_date));
				$model->finish_date = date('Y-m-d', strtotime($model->finish_date));
				$model->branch_id = Branch::currentBranch()->id;
				if ($model->save()) {
					if ($model->is_holiday == 1) {
						$result = StaffHolidays::model()->updateAll(['is_deleted' => 1], 'branch_calendar_holiday_id = :branch_calendar_holiday_id', [':branch_calendar_holiday_id' => $model->id]);
						$staffModel = StaffPersonalDetails::model()->findAllByAttributes(array(
							'branch_id' => $model->branch_id,
							'is_casual_staff' => 0
						));
						if (!empty($staffModel)) {
							foreach ($staffModel as $staff) {
								$staffHolidaysEntitlementModel = StaffHolidaysEntitlement::model()->findByAttributes(array(
									'staff_id' => $staff->id,
									'year' => date("Y", strtotime($model->start_date))
								));
								if (!empty($staffHolidaysEntitlementModel)) {
									$staffHolidayEntitlementEvents = StaffHolidaysEntitlementEvents::model()->find(array(
										'condition' => ':date BETWEEN start_date and finish_date and holiday_id = :holiday_id',
										'params' => array(
											':holiday_id' => $staffHolidaysEntitlementModel->id,
											':date' => date("Y-m-d", strtotime($model->start_date))
										)
									));
									if (!empty($staffHolidayEntitlementEvents)) {
										if ($staffHolidayEntitlementEvents->no_of_days == 0) {
											continue;
										}
										$holidayDates = customFunctions::getDatesOfDays($model->start_date, $model->finish_date, explode(",", $model->branch->nursery_operation_days));
										$staffWorkingDays = customFunctions::getDatesOfDays($model->start_date, $model->finish_date, $staff->getStaffWorkingDays());
										$holidayDates = array_intersect($holidayDates, $staffWorkingDays);
										if (!empty($holidayDates)) {
											foreach ($holidayDates as $holidayDate) {
												if (!empty($staff->start_date)) {
													if (strtotime(date("Y-m-d", strtotime($staff->start_date))) > strtotime(date("Y-m-d", strtotime($holidayDate)))) {
														continue;
													}
												}
												if (!empty($staff->leave_date)) {
													if (strtotime(date("Y-m-d", strtotime($holidayDate))) > strtotime(date("Y-m-d", strtotime($staff->leave_date)))) {
														continue;
													}
												}
												$checkHolidayExists = StaffHolidays::model()->find([
													'condition' => '(:start_date BETWEEN start_date and return_date) AND staff_id = :staff_id',
													'params' => [
														':start_date' => date("Y-m-d", strtotime($holidayDate)),
														':staff_id' => $staff->id
													]
												]);
												if ($checkHolidayExists) {
													continue;
												}
												/*												 * Check Maternity Event Exists* */
												$staffEventsModel = StaffEventDetails::model()->with('event')->find([
													'condition' => 'event.is_systen_event = 1 AND event.name = :name AND t.staff_id = :staff_id AND :holiday_date BETWEEN t.title_date_1_value AND t.title_date_2_value',
													'params' => [
														':staff_id' => $staff->id,
														':name' => 'Maternity',
														':holiday_date' => date("Y-m-d", strtotime($holidayDate))
													]
												]);
												if (!empty($staffEventsModel)) {
													continue;
												}
												$staffHolidaysModel = new StaffHolidays();
												$staffHolidaysModel->scenario = "branch_calendar_holiday";
												$staffHolidaysModel->branch_id = $model->branch_id;
												$staffHolidaysModel->start_date = $holidayDate;
												$staffHolidaysModel->return_date = $holidayDate;
												$staffHolidaysModel->today_date = date("Y-m-d");
												$staffHolidaysModel->staff_id = $staff->id;
												$staffHolidaysModel->staff_holidays_type_id = 1;
												$staffHolidaysModel->staff_holidays_reason_id = 1;
												$staffHolidaysModel->description = !empty($model->description) ? $model->name . " (" . $model->description . ")" : $model->name;
												$staffHolidaysModel->branch_calendar_holiday_id = $model->id;
												$staffHolidaysModel->holiday_hours = customFunctions::round(($staffHolidayEntitlementEvents->contract_hours / $staffHolidayEntitlementEvents->no_of_days) * (((strtotime(date("Y-m-d", strtotime($holidayDate))) - strtotime(date("Y-m-d", strtotime($holidayDate)))) / 86400) + 1), 2);
												if (!$staffHolidaysModel->save()) {
													continue;
												}
											}
										}
									}
								}
							}
						}
					}
					$transaction->commit();
					echo CJSON::encode($response);
				} else {
					throw new JsonException($model->getErrors());
				}
			} catch (JsonException $ex) {
				$transaction->rollback();
				echo CJSON::encode($ex->getOptions());
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid');
		}
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 *
	 * @param integer $id
	 *            the ID of the model to be deleted
	 */
	public function actionDelete($id) {
		if (Yii::app()->request->isAjaxRequest) {
			$response = array(
				'status' => 1,
				'message' => 'Branch calendar holiday has been successfully deleted.'
			);
			$model = $this->loadModel($id);
			$resultDeleteStaffHolidays = StaffHolidays::model()->updateAll(['is_deleted' => 1], 'branch_calendar_holiday_id = :branch_calendar_holiday_id', [':branch_calendar_holiday_id' => $model->id]);
			$resultDeleteBranchCalendarHoliday = BranchCalendar::model()->updateAll(['is_deleted' => 1], 'id = :id', [':id' => $model->id]);
			if ($resultDeleteBranchCalendarHoliday) {
				echo CJSON::encode($response);
			} else {
				$response['status'] = 0;
				$response['message'] = 'Their was some problem deleting the branch calendar holiday.';
				echo CJSON::encode($response);
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex() {
		if (!isset(Yii::app()->session['branch_id'])) {
			throw new CHttpException(404, 'Please set up branch in the session.');
		}
		$model = new BranchCalendar();
		$termsModel = Terms::model()->findAllByAttributes(array(
			'branch_id' => Yii::app()->session['branch_id'],
			'year' => date('Y')
		));

		$this->render('index', array(
			'model' => $model,
			'termsModel' => $termsModel
		));
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin() {
		$model = new BranchCalendar('search');
		$model->unsetAttributes(); // clear any default values
		if (isset($_GET['BranchCalendar']))
			$model->attributes = $_GET['BranchCalendar'];

		$this->render('admin', array(
			'model' => $model
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 *
	 * @param integer $id
	 *            the ID of the model to be loaded
	 * @return BranchCalendar the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id) {
		$model = BranchCalendar::model()->findByPk($id);
		if ($model === null)
			throw new CHttpException(404, 'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 *
	 * @param BranchCalendar $model
	 *            the model to be validated
	 */
	protected function performAjaxValidation($model) {
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'branch-calendar-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

}
