<?php

Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseUrl . '/js/staffHolidaysEntitlement.js?version=1.0.4', CClientScript::POS_END);
Yii::app()->clientScript->registerScript('helpers', '
          eyMan = {
              urls: {
                  changeEntitlement: ' . CJSON::encode(Yii::app()->createUrl('staffHolidaysEntitlement/changeEntitlement')) . ' ,
                  transferEntitlement: ' . CJSON::encode(Yii::app()->createUrl('staffHolidaysEntitlement/transferEntitlement')) . ',
                  overrideEntitlement: ' . CJSON::encode(Yii::app()->createUrl('staffHolidaysEntitlement/overrideEntitlement')) . ',
                  lastChangedContractHours: ' . CJSON::encode(Yii::app()->createUrl('staffHolidaysEntitlement/lastChangedContractHours')) . ',
                  sendStaffHolidaysStatement: ' . CJSON::encode(Yii::app()->createUrl('staffHolidaysEntitlement/sendStaffHolidaysStatement')) . ',
                  refreshEntitlement: ' . CJSON::encode(Yii::app()->createUrl('staffHolidaysEntitlement/refreshEntitlement')) . ',
                  approveHoliday: ' . CJSON::encode(Yii::app()->createUrl('staffHolidaysEntitlement/approveHoliday')) . ',
                  openingBalanceEntitlement: ' . CJSON::encode(Yii::app()->createUrl('staffHolidaysEntitlement/openingBalanceEntitlement')) . ',
                  casualToPermanent: ' . CJSON::encode(Yii::app()->createUrl('staffHolidaysEntitlement/casualToPermanent', ['staff_id' => $_GET['staff_id']])) . ',
                  permanentToCasual: ' . CJSON::encode(Yii::app()->createUrl('staffHolidaysEntitlement/permanentToCasual', ['staff_id' => $_GET['staff_id']])) . ',
                  addOneYearEntitlement: ' . CJSON::encode(Yii::app()->createUrl('staffHolidaysEntitlement/addOneYearEntitlement', ['staff_id' => $_GET['staff_id']])) . ',
              }
          };
      ', CClientScript::POS_END);

class StaffHolidaysEntitlementController extends eyManController {

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
	public function actionView($id) {
		$this->render('view', array(
			'model' => $this->loadModel($id)
		));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate() {
		$model = new StaffHolidaysEntitlement();

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if (isset($_POST['StaffHolidaysEntitlement'])) {
			$model->attributes = $_POST['StaffHolidaysEntitlement'];
			if ($model->save())
				$this->redirect(array(
					'view',
					'id' => $model->id
				));
		}

		$this->render('create', array(
			'model' => $model
		));
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 *
	 * @param integer $id
	 *            the ID of the model to be updated
	 */
	public function actionUpdate($id) {
		$model = $this->loadModel($id);

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if (isset($_POST['StaffHolidaysEntitlement'])) {
			$model->attributes = $_POST['StaffHolidaysEntitlement'];
			if ($model->save())
				$this->redirect(array(
					'view',
					'id' => $model->id
				));
		}

		$this->render('update', array(
			'model' => $model
		));
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 *
	 * @param integer $id
	 *            the ID of the model to be deleted
	 */
	public function actionDelete($id) {
		$this->loadModel($id)->delete();

		// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
		if (!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array(
					'admin'
			));
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex($staff_id) {
		$model = StaffHolidaysEntitlement::model()->findByAttributes(array(
			'staff_id' => $staff_id
		));
		$model = new StaffHolidaysEntitlement();
		$overrideEntitlementModel = new StaffHolidaysEntitlement();
		$overrideEntitlementModel->scenario = "overrideEntitlement";
		$transferEntitlementModel = new StaffHolidaysEntitlement();
		$transferEntitlementModel->scenario = "transferEntitlement";

		$openingBalanceEntitlementModel = new StaffHolidaysEntitlement();
		$openingBalanceEntitlementModel->scenario = "openingBalanceEntitlement";

		$changeContractTypeModel = new StaffHolidaysEntitlement();
		$changeContractTypeModel->scenario = "changeContractType";

		$this->pageTitle = 'Contract / Entitlement Details | eyMan';
		$model = new StaffHolidaysEntitlement('search');
		$model->staff_id = $staff_id;
		$model->unsetAttributes();
		$staffPersonalDetails = StaffPersonalDetails::model()->findByPk($staff_id);
        
        $oneYearEntitlementModel  = StaffPersonalDetails::model()->findByPk($staff_id);
        $oneYearEntitlementModel->scenario = "addEntitlement";
        
		$isCasualStaff = $staffPersonalDetails->is_casual_staff;
		if (isset($_GET['StaffHolidaysEntitlement'])) {
			$model->attributes = $_GET['StaffHolidaysEntitlement'];
			$model->staff_id = $staff_id;
		}

		if (isset($_POST['StaffPersonalDetails']) && isset($_POST['Update'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$hrSettings = HrSetting::currentHrSettings();
				$staffPersonalDetails->attributes = $_POST['StaffPersonalDetails'];
				$staffPersonalDetails->prevProfilePhoto = $staffPersonalDetails->profile_photo;
				if ($staffPersonalDetails->save()) {
					if ($staffPersonalDetails->is_casual_staff == 0) {
						if ($staffPersonalDetails->is_entitlement_created == 0) {
							if (empty($hrSettings) || $hrSettings->holiday_year == HrSetting::JANUARY_DECEMBER) {
								StaffHolidaysEntitlement::model()->setEntitlement($staffPersonalDetails->id);
							} else if ($hrSettings->holiday_year == HrSetting::APRIL_MARCH) {
								StaffHolidaysEntitlement::model()->setEntitlementAprilToMarch($staffPersonalDetails->id);
							} else {
								StaffHolidaysEntitlement::model()->setEntitlementSepToAugust($staffPersonalDetails->id);
							}
							StaffPersonalDetails::model()->updateByPk($staffPersonalDetails->id, array(
								'is_entitlement_created' => 1
							));
							$eventType = EventType::model()->findByAttributes(array(
								'name' => 'Contracted Hours Change',
								'branch_id' => Branch::currentBranch()->id
							));
							if (empty($eventType)) {
								$eventType = new EventType();
								$eventType->branch_id = Branch::currentBranch()->id;
								$eventType->name = "Contracted Hours Change";
								$eventType->description = "Contracted hours change";
								$eventType->title_date_1 = "Effective From";
								$eventType->title_date_2 = NULL;
								$eventType->title_description = "Hours from - to";
								$eventType->title_notes = "Comments";
								$eventType->for_staff = 1;
								$eventType->is_systen_event = 1;
								$eventType->create_for_existing = 0;
								$eventType->save();
							}
							$staffEventDetailsModel = new StaffEventDetails();
							$staffEventDetailsModel->event_id = $eventType->id;
							$staffEventDetailsModel->title_date_1_value = date("Y-m-d", strtotime(date("Y-m-d")));
							$staffEventDetailsModel->title_description_value = 0 . " - " . $staffPersonalDetails->contract_hours;
							$staffEventDetailsModel->staff_id = $staffPersonalDetails->id;
							$staffEventDetailsModel->status = 1;
							$staffEventDetailsModel->save();
						}
						$branchCalendarHolidays = BranchCalendar::model()->findAllByAttributes(['is_holiday' => 1, 'branch_id' => $staffPersonalDetails->branch_id]);
						if (!empty($branchCalendarHolidays)) {
							foreach ($branchCalendarHolidays as $branchCalendarHoliday) {
								if (empty($staffPersonalDetails->start_date) || strtotime(date("Y-m-d", strtotime($staffPersonalDetails->start_date))) > strtotime(date("Y-m-d", strtotime($branchCalendarHoliday->start_date)))) {
									continue;
								}
								$checkBranchCalendarHolidayExists = StaffHolidays::model()->findByAttributes(['branch_calendar_holiday_id' => $branchCalendarHoliday->id, 'staff_id' => $staffPersonalDetails->id]);
								if (!empty($checkBranchCalendarHolidayExists)) {
									continue;
								}
								$staffHolidaysEntitlementModel = StaffHolidaysEntitlement::model()->findByAttributes(array(
									'staff_id' => $staffPersonalDetails->id,
									'year' => date("Y", strtotime($branchCalendarHoliday->start_date))
								));
								if (!empty($staffHolidaysEntitlementModel)) {
									$staffHolidayEntitlementEvents = StaffHolidaysEntitlementEvents::model()->find(array(
										'condition' => ':date BETWEEN start_date and finish_date and holiday_id = :holiday_id',
										'params' => array(
											':holiday_id' => $staffHolidaysEntitlementModel->id,
											':date' => date("Y-m-d", strtotime($branchCalendarHoliday->start_date))
										)
									));
									if (!empty($staffHolidayEntitlementEvents)) {
										if ($staffHolidayEntitlementEvents->no_of_days == 0) {
											continue;
										}
										$holidayDates = customFunctions::getDatesOfDays($branchCalendarHoliday->start_date, $branchCalendarHoliday->finish_date, explode(",", $branchCalendarHoliday->branch->nursery_operation_days));
										$staffWorkingDays = customFunctions::getDatesOfDays($branchCalendarHoliday->start_date, $branchCalendarHoliday->finish_date, $staffPersonalDetails->getStaffWorkingDays());
										$holidayDates = array_intersect($holidayDates, $staffWorkingDays);
										if (!empty($holidayDates)) {
											foreach ($holidayDates as $holidayDate) {
												if (empty($staffPersonalDetails->start_date)) {
													continue;
												}
												if (!empty($staffPersonalDetails->start_date)) {
													if (strtotime(date("Y-m-d", strtotime($staffPersonalDetails->start_date))) > strtotime(date("Y-m-d", strtotime($holidayDate)))) {
														continue;
													}
												}
												/** Don't schedule the holiday after leave date* */
												if (!empty($staffPersonalDetails->leave_date)) {
													if (strtotime(date("Y-m-d", strtotime($holidayDate))) > strtotime(date("Y-m-d", strtotime($staffPersonalDetails->leave_date)))) {
														continue;
													}
												}
												/** Check if their is a holiday on that date* */
												$checkHolidayExists = StaffHolidays::model()->find([
													'condition' => '(:start_date BETWEEN start_date and return_date) AND staff_id = :staff_id',
													'params' => [
														':start_date' => date("Y-m-d", strtotime($holidayDate)),
														':staff_id' => $staffPersonalDetails->id
													]
												]);
												if ($checkHolidayExists) {
													continue;
												}
												/*												 * Check Maternity Event Exists* */
												$staffEventsModel = StaffEventDetails::model()->with('event')->find([
													'condition' => 'event.is_systen_event = 1 AND event.name = :name AND t.staff_id = :staff_id AND :holiday_date BETWEEN t.title_date_1_value AND t.title_date_2_value',
													'params' => [
														':staff_id' => $staffPersonalDetails->id,
														':name' => 'Maternity',
														':holiday_date' => date("Y-m-d", strtotime($holidayDate))
													]
												]);
												if (!empty($staffEventsModel)) {
													continue;
												}
												$staffHolidaysModel = new StaffHolidays();
												$staffHolidaysModel->scenario = "branch_calendar_holiday";
												$staffHolidaysModel->branch_id = $branchCalendarHoliday->branch_id;
												$staffHolidaysModel->start_date = $holidayDate;
												$staffHolidaysModel->return_date = $holidayDate;
												$staffHolidaysModel->today_date = date("Y-m-d");
												$staffHolidaysModel->staff_id = $staffPersonalDetails->id;
												$staffHolidaysModel->staff_holidays_type_id = 1;
												$staffHolidaysModel->staff_holidays_reason_id = 1;
												$staffHolidaysModel->description = !empty($branchCalendarHoliday->description) ? $branchCalendarHoliday->name . " (" . $branchCalendarHoliday->description . ")" : $branchCalendarHoliday->name;
												$staffHolidaysModel->branch_calendar_holiday_id = $branchCalendarHoliday->id;
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
					if ($staffPersonalDetails->is_casual_staff == 1) {
						if ($staffPersonalDetails->is_entitlement_created == 0) {
							if (empty($hrSettings) || $hrSettings->holiday_year == 0) {
								StaffHolidaysEntitlement::model()->setEntitlementForCasualStaff($staffPersonalDetails->id);
							}
							StaffPersonalDetails::model()->updateByPk($staffPersonalDetails->id, array(
								'is_entitlement_created' => 1
							));
							$eventType = EventType::model()->findByAttributes(array(
								'name' => 'Contracted Hours Change',
								'branch_id' => Branch::currentBranch()->id
							));
							if (empty($eventType)) {
								$eventType = new EventType();
								$eventType->branch_id = Branch::currentBranch()->id;
								$eventType->name = "Contracted Hours Change";
								$eventType->description = "Contracted hours change";
								$eventType->title_date_1 = "Effective From";
								$eventType->title_date_2 = NULL;
								$eventType->title_description = "Hours from - to";
								$eventType->title_notes = "Comments";
								$eventType->for_staff = 1;
								$eventType->is_systen_event = 1;
								$eventType->create_for_existing = 0;
								$eventType->save();
							}
							$staffEventDetailsModel = new StaffEventDetails();
							$staffEventDetailsModel->event_id = $eventType->id;
							$staffEventDetailsModel->title_date_1_value = date("Y-m-d", strtotime(date("Y-m-d")));
							$staffEventDetailsModel->title_description_value = 0 . " - " . $staffPersonalDetails->contract_hours;
							$staffEventDetailsModel->staff_id = $staffPersonalDetails->id;
							$staffEventDetailsModel->status = 1;
							$staffEventDetailsModel->save();
						}
					}
					Yii::app()->user->setFlash('success', 'Contract / Entitlement Details has been successfully Updated.');
					$transaction->commit();
					$this->redirect(array(
						'index',
						'staff_id' => $staffPersonalDetails->id
					));
				} else {
					throw new Exception(CHtml::errorSummary($staffPersonalDetails, "", "", array(
						'class' => 'customErrors'
					)));
				}
			} catch (Exception $ex) {
				Yii::app()->user->setFlash('error', $ex->getMessage());
				$transaction->rollback();
				$this->refresh();
			}
		}

		$this->render('index', array(
			'model' => $model,
			'staffPersonalDetails' => $staffPersonalDetails,
			'overrideEntitlementModel' => $overrideEntitlementModel,
			'transferEntitlementModel' => $transferEntitlementModel,
			'openingBalanceEntitlementModel' => $openingBalanceEntitlementModel,
			'changeContractTypeModel' => $changeContractTypeModel,
            'oneYearEntitlementModel' => $oneYearEntitlementModel
		));
	}

    public function actionAddOneYearEntitlement(){
      if (Yii::app()->request->isAjaxRequest) {
            $staffPersonalDetails = StaffPersonalDetails::model()->findByPk($_GET['staff_id']);
            $staffPersonalDetails->scenario = "addEntitlement";
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$hrSettings = HrSetting::currentHrSettings();
				$staffPersonalDetails->attributes = $_POST['StaffPersonalDetails'];
				if ($staffPersonalDetails->validate() && $staffPersonalDetails->save()) {
					if ($staffPersonalDetails->is_casual_staff == 0) {
						if ($staffPersonalDetails->is_entitlement_created == 0 || $staffPersonalDetails->is_entitlement_created == 1) {
							if (empty($hrSettings) || $hrSettings->holiday_year == HrSetting::JANUARY_DECEMBER) {
								StaffHolidaysEntitlement::model()->setEntitlementOneYear($staffPersonalDetails->id , $_POST['StaffPersonalDetails']['entitlementYear']);
							} else if ($hrSettings->holiday_year == HrSetting::APRIL_MARCH) {
								StaffHolidaysEntitlement::model()->setEntitlementAprilToMarchOneYear($staffPersonalDetails->id ,$_POST['StaffPersonalDetails']['entitlementYear']);
							} else {
								StaffHolidaysEntitlement::model()->setEntitlementSepToAugustOneYear($staffPersonalDetails->id ,$_POST['StaffPersonalDetails']['entitlementYear']);
							}
							StaffPersonalDetails::model()->updateByPk($staffPersonalDetails->id, array(
								'is_entitlement_created' => 1
							));

						}
						$branchCalendarHolidays = BranchCalendar::model()->findAllByAttributes(['is_holiday' => 1, 'branch_id' => $staffPersonalDetails->branch_id]);
						if (!empty($branchCalendarHolidays)) {
							foreach ($branchCalendarHolidays as $branchCalendarHoliday) {
								if (empty($staffPersonalDetails->start_date) || strtotime(date("Y-m-d", strtotime($staffPersonalDetails->start_date))) > strtotime(date("Y-m-d", strtotime($branchCalendarHoliday->start_date)))) {
									continue;
								}
								$checkBranchCalendarHolidayExists = StaffHolidays::model()->findByAttributes(['branch_calendar_holiday_id' => $branchCalendarHoliday->id, 'staff_id' => $staffPersonalDetails->id]);
								if (!empty($checkBranchCalendarHolidayExists)) {
									continue;
								}
								$staffHolidaysEntitlementModel = StaffHolidaysEntitlement::model()->findByAttributes(array(
									'staff_id' => $staffPersonalDetails->id,
									'year' => date("Y", strtotime($branchCalendarHoliday->start_date))
								));
								if (!empty($staffHolidaysEntitlementModel)) {
									$staffHolidayEntitlementEvents = StaffHolidaysEntitlementEvents::model()->find(array(
										'condition' => ':date BETWEEN start_date and finish_date and holiday_id = :holiday_id',
										'params' => array(
											':holiday_id' => $staffHolidaysEntitlementModel->id,
											':date' => date("Y-m-d", strtotime($branchCalendarHoliday->start_date))
										)
									));
									if (!empty($staffHolidayEntitlementEvents)) {
										if ($staffHolidayEntitlementEvents->no_of_days == 0) {
											continue;
										}
										$holidayDates = customFunctions::getDatesOfDays($branchCalendarHoliday->start_date, $branchCalendarHoliday->finish_date, explode(",", $branchCalendarHoliday->branch->nursery_operation_days));
										$staffWorkingDays = customFunctions::getDatesOfDays($branchCalendarHoliday->start_date, $branchCalendarHoliday->finish_date, $staffPersonalDetails->getStaffWorkingDays());
										$holidayDates = array_intersect($holidayDates, $staffWorkingDays);
										if (!empty($holidayDates)) {
											foreach ($holidayDates as $holidayDate) {
												if (empty($staffPersonalDetails->start_date)) {
													continue;
												}
												if (!empty($staffPersonalDetails->start_date)) {
													if (strtotime(date("Y-m-d", strtotime($staffPersonalDetails->start_date))) > strtotime(date("Y-m-d", strtotime($holidayDate)))) {
														continue;
													}
												}
												/** Don't schedule the holiday after leave date* */
												if (!empty($staffPersonalDetails->leave_date)) {
													if (strtotime(date("Y-m-d", strtotime($holidayDate))) > strtotime(date("Y-m-d", strtotime($staffPersonalDetails->leave_date)))) {
														continue;
													}
												}
												/** Check if their is a holiday on that date* */
												$checkHolidayExists = StaffHolidays::model()->find([
													'condition' => '(:start_date BETWEEN start_date and return_date) AND staff_id = :staff_id',
													'params' => [
														':start_date' => date("Y-m-d", strtotime($holidayDate)),
														':staff_id' => $staffPersonalDetails->id
													]
												]);
												if ($checkHolidayExists) {
													continue;
												}
												/*												 * Check Maternity Event Exists* */
												$staffEventsModel = StaffEventDetails::model()->with('event')->find([
													'condition' => 'event.is_systen_event = 1 AND event.name = :name AND t.staff_id = :staff_id AND :holiday_date BETWEEN t.title_date_1_value AND t.title_date_2_value',
													'params' => [
														':staff_id' => $staffPersonalDetails->id,
														':name' => 'Maternity',
														':holiday_date' => date("Y-m-d", strtotime($holidayDate))
													]
												]);
												if (!empty($staffEventsModel)) {
													continue;
												}
												$staffHolidaysModel = new StaffHolidays();
												$staffHolidaysModel->scenario = "branch_calendar_holiday";
												$staffHolidaysModel->branch_id = $branchCalendarHoliday->branch_id;
												$staffHolidaysModel->start_date = $holidayDate;
												$staffHolidaysModel->return_date = $holidayDate;
												$staffHolidaysModel->today_date = date("Y-m-d");
												$staffHolidaysModel->staff_id = $staffPersonalDetails->id;
												$staffHolidaysModel->staff_holidays_type_id = 1;
												$staffHolidaysModel->staff_holidays_reason_id = 1;
												$staffHolidaysModel->description = !empty($branchCalendarHoliday->description) ? $branchCalendarHoliday->name . " (" . $branchCalendarHoliday->description . ")" : $branchCalendarHoliday->name;
												$staffHolidaysModel->branch_calendar_holiday_id = $branchCalendarHoliday->id;
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
					if ($staffPersonalDetails->is_casual_staff == 1) {
						if ($staffPersonalDetails->is_entitlement_created == 0 || $staffPersonalDetails->is_entitlement_created == 1) {
							if (empty($hrSettings) || $hrSettings->holiday_year == 0) {
								StaffHolidaysEntitlement::model()->setEntitlementForCasualStaffOneYear($staffPersonalDetails->id , $_POST['StaffPersonalDetails']['entitlementYear']);
							}
							StaffPersonalDetails::model()->updateByPk($staffPersonalDetails->id, array(
								'is_entitlement_created' => 1
							));

						}
					}

					$transaction->commit();
					echo CJSON::encode(array(
                      'status' => 1,
                      'message' => 'Entitlement has been successfully created.',
                      'error' => []
                    ));
                    Yii::app()->end();
				} else {

                echo CJSON::encode([
                  'status' => 0,
                  'error' => $staffPersonalDetails->getErrors(),
                ]);
                Yii::app()->end();
				}
			} catch (Exception $ex) {
				$transaction->rollback();
                echo CJSON::encode([
                    'status' => 0,
                    'error' => $ex->getOptions()
                ]);
                Yii::app()->end();

			}
      }else {
			throw new CHttpException(404, 'Your request is not valid.');
      }
    }

	/**
	 * Manages all models.
	 */
	public function actionAdmin() {
		$model = new StaffHolidaysEntitlement('search');
		$model->unsetAttributes(); // clear any default values
		if (isset($_GET['StaffHolidaysEntitlement']))
			$model->attributes = $_GET['StaffHolidaysEntitlement'];

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
	 * @return StaffHolidaysEntitlement the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id) {
		$model = StaffHolidaysEntitlement::model()->findByPk($id);
		if ($model === null)
			throw new CHttpException(404, 'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 *
	 * @param StaffHolidaysEntitlement $model
	 *            the model to be validated
	 */
	protected function performAjaxValidation($model) {
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'staff-holidays-entitlement-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	public function actionChangeEntitlement() {
		if (Yii::app()->request->isAjaxRequest) {
			$isResetFutureEntitlement = ($_POST['reset_future_entitlement'] == "false") ? false : true;
			$model = StaffHolidaysEntitlement::model()->findByPk($_POST['StaffHolidaysEntitlement']['id']);
			if (!empty($model)) {
				$model->setScenario('changeEntitlement');
				$model->attributes = $_POST['StaffHolidaysEntitlement'];
				$transaction = Yii::app()->db->beginTransaction();
				try {
					if (!$model->validate()) {
						throw new JsonException($model->getErrors());
					}
					$totalEntitlement = 0;
					$oldStaffHolidaysEvents = StaffHolidaysEntitlementEvents::model()->findAllByAttributes(array(
						'holiday_id' => $model->id,
						'is_overriden' => 0,
						'is_transferred' => 0,
						'opening_balance' => 0
						), array(
						'order' => 'start_date'
					));
					$effective_date = date("Y-m-d", strtotime($_POST['StaffHolidaysEntitlement']['effective_date']));
					if (!empty($oldStaffHolidaysEvents)) {
						foreach ($oldStaffHolidaysEvents as $oldEvent) {
							if ((strtotime($effective_date) >= strtotime(date("Y-m-d", strtotime($oldEvent->start_date)))) && (strtotime($effective_date) <= strtotime(date("Y-m-d", strtotime($oldEvent->finish_date))))) {
								$oldEventFinishDate = $oldEvent->finish_date;
								$oldEvent->finish_date = date("Y-m-d", strtotime("-1 day", strtotime($effective_date)));
								$oldEntitlement = StaffHolidaysEntitlement::model()->getEntitlement($model->year, date("Y-m-d", strtotime($oldEvent->start_date)), date("Y-m-d", strtotime($oldEvent->finish_date)), $oldEvent->contract_hours, $model->days_per_year);
								$oldEvent->entitlement = $oldEntitlement;
								if (!$oldEvent->save()) {
									throw new JsonException($oldEvent->getErrors());
								}
								$newHolidaysEntitlementEventModel = new StaffHolidaysEntitlementEvents();
								$newHolidaysEntitlementEventModel->branch_id = $model->branch_id;
								$newHolidaysEntitlementEventModel->holiday_id = $model->id;
								$newHolidaysEntitlementEventModel->start_date = $effective_date;
								$newHolidaysEntitlementEventModel->finish_date = $oldEventFinishDate;
								$newHolidaysEntitlementEventModel->contract_hours = $_POST['StaffHolidaysEntitlement']['new_contract_hours'];
								$newHolidaysEntitlementEventModel->is_changed = 1;
								$newHolidaysEntitlementEventModel->no_of_days = $_POST['StaffHolidaysEntitlement']['new_contract_no_of_days'];
								$newHolidaysEntitlementEventModel->entitlement = StaffHolidaysEntitlement::model()->getEntitlement($model->year, date("Y-m-d", strtotime($newHolidaysEntitlementEventModel->start_date)), date("Y-m-d", strtotime($newHolidaysEntitlementEventModel->finish_date)), $newHolidaysEntitlementEventModel->contract_hours, $model->days_per_year);
								if (!$newHolidaysEntitlementEventModel->save()) {
									throw new JsonException($newHolidaysEntitlementEventModel->getErrors());
								}
							}
						}
						$model->setScenario("");
						$model->holiday_entitlement_per_year = StaffHolidaysEntitlementEvents::model()->find(array(
								'select' => 'sum(entitlement) as total_entitlement',
								'condition' => 'holiday_id = :holiday_id AND is_overriden = 0',
								'params' => array(
									':holiday_id' => $model->id
								)
							))->total_entitlement;
						if (!$model->save()) {
							throw new JsonException($model->getErrors());
						}
						$eventType = EventType::model()->findByAttributes(array(
							'name' => 'Contracted Hours Change',
							'branch_id' => Branch::currentBranch()->id
						));
						if (empty($eventType)) {
							$eventType = new EventType();
							$eventType->branch_id = Branch::currentBranch()->id;
							$eventType->name = "Contracted Hours Change";
							$eventType->description = "Contracted hours change";
							$eventType->title_date_1 = "Effective From";
							$eventType->title_date_2 = NULL;
							$eventType->title_description = "Hours from - to";
							$eventType->title_notes = "Comments";
							$eventType->for_staff = 1;
							$eventType->is_systen_event = 1;
							$eventType->create_for_existing = 0;
							$eventType->save();
						}
						$staffEventDetailsModel = new StaffEventDetails();
						$staffEventDetailsModel->event_id = $eventType->id;
						$staffEventDetailsModel->title_date_1_value = date("Y-m-d", strtotime($_POST['StaffHolidaysEntitlement']['effective_date']));
						$staffEventDetailsModel->title_description_value = $_POST['StaffHolidaysEntitlement']['previous_contract_hours'] . " - " . $_POST['StaffHolidaysEntitlement']['new_contract_hours'];
						$staffEventDetailsModel->staff_id = $model->staff_id;
						$staffEventDetailsModel->status = 1;
						$staffEventDetailsModel->save();
						if ($isResetFutureEntitlement) {
							$futureEntitlementModel = StaffHolidaysEntitlement::model()->findAll([
								'condition' => 'year > :year and staff_id = :staff_id',
								'params' => [':year' => $model->year, ':staff_id' => $model->staff_id]
							]);
							if (!empty($futureEntitlementModel)) {
								foreach ($futureEntitlementModel as $futureEntitlement) {
									$result = StaffHolidaysEntitlementEvents::model()->updateAll(['is_deleted' => 1], "holiday_id = :holiday_id", [':holiday_id' => $futureEntitlement->id]);
									if (empty($result)) {
										throw new JsonException(['There seems to be some problem while refreshing the entitlement.']);
									}
									$entitlement = StaffHolidaysEntitlement::model()->getEntitlement($futureEntitlement->year, $futureEntitlement->start_date, $futureEntitlement->finish_date, $_POST['StaffHolidaysEntitlement']['new_contract_hours'], $futureEntitlement->days_per_year);
									$newHolidaysEntitlementEventModel = new StaffHolidaysEntitlementEvents();
									$newHolidaysEntitlementEventModel->branch_id = $futureEntitlement->branch_id;
									$newHolidaysEntitlementEventModel->holiday_id = $futureEntitlement->id;
									$newHolidaysEntitlementEventModel->start_date = $futureEntitlement->start_date;
									$newHolidaysEntitlementEventModel->finish_date = $futureEntitlement->finish_date;
									$newHolidaysEntitlementEventModel->contract_hours = $_POST['StaffHolidaysEntitlement']['new_contract_hours'];
									$newHolidaysEntitlementEventModel->no_of_days = (int) $_POST['StaffHolidaysEntitlement']['new_contract_no_of_days'];
									$newHolidaysEntitlementEventModel->entitlement = $entitlement;
									if (!$newHolidaysEntitlementEventModel->save()) {
										throw new JsonException($newHolidaysEntitlementEventModel->getErrors());
									}
									$futureEntitlement->holiday_entitlement_per_year = $newHolidaysEntitlementEventModel->entitlement;
									$futureEntitlement->contract_hours_per_week = $_POST['StaffHolidaysEntitlement']['new_contract_hours'];
									$futureEntitlement->days_per_week = $_POST['StaffHolidaysEntitlement']['new_contract_no_of_days'];
									if (!$futureEntitlement->save()) {
										throw new JsonException($futureEntitlement->getErrors());
									}
								}
							}
						}
						$transaction->commit();
						echo CJSON::encode(array(
							'status' => 1,
							'message' => 'Contract Hours has been successfully changed.',
							'error' => []
						));
						Yii::app()->end();
					}
				} catch (JsonException $ex) {
					$transaction->rollback();
					echo CJSON::encode([
						'status' => 0,
						'error' => $ex->getOptions()
					]);
					Yii::app()->end();
				}
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionViewEntitlementAllocation($id, $staff_id) {
		$staffHolidaysEntitlement = $this->loadModel($id);
		Yii::app()->clientScript->registerScript('staffHolidaysEntitlement', '
          eyman = {
              data: {
                  staff_id: ' . $staffHolidaysEntitlement->staff_id . ',
                  year: ' . $staffHolidaysEntitlement->year . ',
                  entitlement_id: ' . $staffHolidaysEntitlement->id . ',
              }
          };
      ', CClientScript::POS_END);
		$staffHolidaysStatementForm = new StaffHolidaysStatementForm();
		if (!empty($staffHolidaysEntitlement)) {
			$this->pageTitle = 'Contract / Entitlement Details | eyMan';
			$model = new StaffHolidaysEntitlementEvents('search');
			$model->holiday_id = $staffHolidaysEntitlement->id;
			$model->unsetAttributes();
			if (isset($_GET['StaffHolidaysEntitlementEvents'])) {
				$model->attributes = $_GET['StaffHolidaysEntitlementEvents'];
				$model->holiday_id = $staffHolidaysEntitlement->id;
			}

			$staffHolidaysModel = new StaffHolidays('search');
			$staffHolidaysModel->staff_id = $staff_id;
			$staffHolidaysModel->unsetAttributes();
			$staffHolidaysModel->is_unpaid = 0;
			if (isset($_GET['StaffHolidays'])) {
				$staffHolidaysModel->attributes = $_GET['StaffHolidays'];
				$staffHolidaysModel->staff_id = $staff_id;
			}
			$used = number_format($staffHolidaysEntitlement->getUsed($staffHolidaysEntitlement->id), 2, ".", " ");
			$balance = number_format(($staffHolidaysEntitlement->holiday_entitlement_per_year - $used), 2, ".", " ");
			$this->render('viewEntitlementAllocation', array(
				'model' => $model,
				'staffHolidaysModel' => $staffHolidaysModel,
				'staff_id' => $staff_id,
				'id' => $id,
				'staffHolidaysEntitlement' => $staffHolidaysEntitlement,
				'staffHolidaysStatementForm' => $staffHolidaysStatementForm,
				'used' => $used,
				'balance' => $balance
			));
		} else {
			throw new CHttpException(404, "This page does not exists.");
		}
	}

	public function actionRefreshEntitlement($id) {
		$holiday_contract = $_POST['StaffHolidaysEntitlement']['days_per_year'];
		$contract_hours_per_week = $_POST['StaffHolidaysEntitlement']['contract_hours_per_week'];
		$days_per_week = $_POST['StaffHolidaysEntitlement']['days_per_week'];

		if (Yii::app()->request->isAjaxRequest) {
			$model = StaffHolidaysEntitlement::model()->findByPk($id);
			if (!empty($model)) {
				$transaction = Yii::app()->db->beginTransaction();
				try {
					$result = StaffHolidaysEntitlementEvents::model()->updateAll(['is_deleted' => 1], "holiday_id = :holiday_id", [':holiday_id' => $id]);
					if (empty($result)) {
						throw new JsonException(['There seems to be some problem while refreshing the entitlement.']);
					}
					$model->start_date = $model->getEntitlementStartDate();
					$model->finish_date = $model->getEntitlementFinishDate();
					if(!$model->save()){
						throw new JsonException($model->getErrors());
					}
					$entitlement = StaffHolidaysEntitlement::model()->getEntitlement($model->year, $model->start_date, $model->finish_date, $contract_hours_per_week, $holiday_contract);
					$newHolidaysEntitlementEventModel = new StaffHolidaysEntitlementEvents();
					$newHolidaysEntitlementEventModel->branch_id = $model->branch_id;
					$newHolidaysEntitlementEventModel->holiday_id = $model->id;
					$newHolidaysEntitlementEventModel->start_date = $model->start_date;
					$newHolidaysEntitlementEventModel->finish_date = $model->finish_date;
					$newHolidaysEntitlementEventModel->contract_hours = $contract_hours_per_week;
					$newHolidaysEntitlementEventModel->no_of_days = (int) $days_per_week;
					$newHolidaysEntitlementEventModel->entitlement = $entitlement;
					if (!$newHolidaysEntitlementEventModel->save()) {
						throw new JsonException($newHolidaysEntitlementEventModel->getErrors());
					}
					$model->holiday_entitlement_per_year = $newHolidaysEntitlementEventModel->entitlement;
					$model->days_per_year = $holiday_contract;
					$model->contract_hours_per_week = $contract_hours_per_week;
					$model->days_per_week = $days_per_week;
					if (!$model->save()) {
						throw new JsonException($model->getErrors());
					}
					$transaction->commit();
					echo CJSON::encode([
						'status' => 1,
						'message' => 'Holiday entitlement has been successfully reset.',
						'errors' => []
					]);
				} catch (JsonException $ex) {
					$transaction->rollback();
					echo CJSON::encode([
						'status' => 0,
						'message' => 'There seems to be some problem while resetting the entitlement.',
						'errors' => $ex->getOptions()
					]);
				}
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	public function actionOverrideEntitlement() {
		if (Yii::app()->request->isAjaxRequest) {
			$model = StaffHolidaysEntitlement::model()->with('staffHolidaysEntitlementEvents')->findByPk(Yii::app()->request->getPost('StaffHolidaysEntitlement_id'));
			if (!empty($model)) {
				$transaction = Yii::app()->db->beginTransaction();
				try {
					foreach ($model->staffHolidaysEntitlementEvents as $staffHolidaysEntitlementEvents) {
						$staffHolidaysEntitlementEvents->is_overriden = 1;
						if (!$staffHolidaysEntitlementEvents->save()) {
							throw new JsonException($staffHolidaysEntitlementEvents->getErrors());
						}
					}
					$staffHolidaysEntitlementEvents = new StaffHolidaysEntitlementEvents();
					$staffHolidaysEntitlementEvents->attributes = $model->attributes;
					$staffHolidaysEntitlementEvents->holiday_id = $model->id;
					$staffHolidaysEntitlementEvents->entitlement = $_POST['StaffHolidaysEntitlement']['new_entitlement'];
					$staffHolidaysEntitlementEvents->contract_hours = $model->contract_hours_per_week;
					$staffHolidaysEntitlementEvents->no_of_days = (int) $model->days_per_week;
					if (!$staffHolidaysEntitlementEvents->save()) {
						throw new JsonException($staffHolidaysEntitlementEvents->getErrors());
					}
					$model->holiday_entitlement_per_year = $_POST['StaffHolidaysEntitlement']['new_entitlement'];
					if (!$model->save()) {
						throw new JsonException($model->getErrors());
					}
					$transaction->commit();
					echo CJSON::encode([
						'status' => 1,
						'message' => 'Holiday Entitlement has been successfully overriden.',
						'errors' => []
					]);
				} catch (JsonException $ex) {
					$transaction->rollback();
					echo CJSON::encode([
						'status' => 0,
						'message' => 'Their seems to be some problem overriding the entitlement.',
						'errors' => $ex->getOptions()
					]);
				}
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	public function actionOpeningBalanceEntitlement() {
		if (Yii::app()->request->isAjaxRequest) {
			$model = StaffHolidaysEntitlement::model()->with('staffHolidaysEntitlementEvents')->findByPk(Yii::app()->request->getPost('StaffHolidaysEntitlement_id'));
			if (!empty($model)) {
				$transaction = Yii::app()->db->beginTransaction();
				try {
					$staffHolidaysEntitlementEvents = new StaffHolidaysEntitlementEvents();
					$staffHolidaysEntitlementEvents->attributes = $model->attributes;
					$staffHolidaysEntitlementEvents->holiday_id = $model->id;
					$staffHolidaysEntitlementEvents->entitlement = $model->holiday_entitlement_per_year + $_POST['StaffHolidaysEntitlement']['opening_balance_entitlement'];
					$staffHolidaysEntitlementEvents->contract_hours = $model->contract_hours_per_week;
					$staffHolidaysEntitlementEvents->no_of_days = (int) $model->days_per_week;
					$staffHolidaysEntitlementEvents->opening_balance = 1;

					if (!$staffHolidaysEntitlementEvents->save()) {
						throw new JsonException($staffHolidaysEntitlementEvents->getErrors());
					}
					$model->holiday_entitlement_per_year = $model->holiday_entitlement_per_year + $_POST['StaffHolidaysEntitlement']['opening_balance_entitlement'];
					$model->opening_balance = 1;
					if (!$model->save()) {
						throw new JsonException($model->getErrors());
					}
					$transaction->commit();
					echo CJSON::encode([
						'status' => 1,
						'message' => 'Balance has been succesfully added.',
						'errors' => []
					]);
				} catch (JsonException $ex) {
					$transaction->rollback();
					echo CJSON::encode([
						'status' => 0,
						'message' => 'Their seems to be some problem while opening balance.',
						'errors' => $ex->getOptions()
					]);
				}
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	public function actionTransferEntitlement() {
		if (Yii::app()->request->isAjaxRequest) {
			$transferFromModel = StaffHolidaysEntitlement::model()->findByPk($_POST['transfer_from']);
			$transferToModel = StaffHolidaysEntitlement::model()->findByPk($_POST['StaffHolidaysEntitlement']['transfer_to']);
			$transferFromModel->scenario = "transferEntitlement";
			if (!empty($transferFromModel)) {
				$transaction = Yii::app()->db->beginTransaction();
				try {
					if (StaffHolidaysEntitlementEvents::model()->countByAttributes(array(
							'holiday_id' => $transferToModel->id,
							'is_overriden' => 1
						)) > 0) {
						$transferFromModel->addError('transferred_entitlement', "Entitlement to be transferred as the entitlement is overriden in that year.");
						throw new JsonException($transferFromModel->getErrors());
					}
					$transferFromModel->transfer_to = $_POST['StaffHolidaysEntitlement']['transfer_to'];
					$transferFromModel->transferred_entitlement = $_POST['StaffHolidaysEntitlement']['transferred_entitlement'];
					$balance = number_format(($transferFromModel->holiday_entitlement_per_year - $transferFromModel->getUsed($transferFromModel->id)), 2, ".", " ");
					if ($balance < $_POST['StaffHolidaysEntitlement']['transferred_entitlement']) {
						$transferFromModel->addError('transferred_entitlement', "Entitlement to be transferred can not be smaller than balance entitlement.");
						throw new JsonException($transferFromModel->getErrors());
					}
					if (!isset($_POST['StaffHolidaysEntitlement']['transfer_to'])) {
						$transferFromModel->addError('transfer_to', "Please select where the entitlement needs to be transferred.");
						throw new JsonException($transferFromModel->getErrors());
					}

					if ($transferFromModel->id == $transferToModel->id) {
						$transferFromModel->addError('transfer_to', "Entitlement can not be transferred to the same year.");
						throw new JsonException($transferFromModel->getErrors());
					}
					$transferToEvent = new StaffHolidaysEntitlementEvents();
					$transferToEvent->branch_id = $transferToModel->branch_id;
					$transferToEvent->start_date = date("Y-m-d");
					$transferToEvent->finish_date = date("Y-m-d");
					$transferToEvent->is_transferred = 1;
					$transferToEvent->holiday_id = $transferToModel->id;
					$transferToEvent->entitlement = $_POST['StaffHolidaysEntitlement']['transferred_entitlement'];
					$transferToEvent->contract_hours = 0;
					$transferToEvent->no_of_days = 0;
					if (!$transferToEvent->save()) {
						throw new JsonException($transferToEvent->getErrors());
					}
					$transferToModel->holiday_entitlement_per_year = customFunctions::round(($transferToModel->holiday_entitlement_per_year + $transferToEvent->entitlement), 2);
					if (!$transferToModel->save()) {
						throw new JsonException($transferToModel->getErrors());
					}
					$transferFromEvent = new StaffHolidaysEntitlementEvents();
					$transferFromEvent->branch_id = $transferFromModel->branch_id;
					$transferFromEvent->start_date = date("Y-m-d");
					$transferFromEvent->finish_date = date("Y-m-d");
					$transferFromEvent->is_transferred = 1;
					$transferFromEvent->holiday_id = $transferFromModel->id;
					$transferFromEvent->entitlement = (- $_POST['StaffHolidaysEntitlement']['transferred_entitlement']);
					$transferFromEvent->contract_hours = 0;
					$transferFromEvent->no_of_days = 0;
					if (!$transferFromEvent->save()) {
						throw new JsonException($transferFromEvent->getErrors());
					}
					$transferFromModel->holiday_entitlement_per_year = customFunctions::round(($transferFromModel->holiday_entitlement_per_year + $transferFromEvent->entitlement), 2);
					if (!$transferFromModel->save()) {
						throw new JsonException($transferFromModel->getErrors());
					}
					$transaction->commit();
					echo CJSON::encode([
						'status' => 1,
						'message' => 'Holiday entitlement has been successfully transferred.',
						'errors' => []
					]);
				} catch (JsonException $ex) {
					$transaction->rollback();
					echo CJSON::encode([
						'status' => 0,
						'message' => 'Their seems to be some problem transferring the entitlement.',
						'errors' => $ex->getOptions()
					]);
				}
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	public function actionlastChangedContractHours() {
		if (Yii::app()->request->isAjaxRequest) {
			$model = StaffHolidaysEntitlementEvents::model()->find([
				'condition' => 'holiday_id = :holiday_id and is_transferred = 0 and is_overriden = 0 AND opening_balance = 0',
				'params' => [
					':holiday_id' => $_POST['id']
				],
				'order' => 'id DESC'
			]);
			if (!empty($model)) {
				echo CJSON::encode([
					'status' => 1,
					'contract_hours' => $model->contract_hours,
					'contract_no_of_days' => $model->no_of_days
				]);
			} else {
				echo CJSON::encode([
					'status' => 1,
					'contract_hours' => 0,
					'contract_no_of_days' => 0
				]);
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionPdfStaffHolidaysStatement($staff_id, $entitlement_id, $entitlement_year, $attachment_type = "D", $exit = true, $staffHolidays = NULL) {
		$staff_holiday_start_date = NULL;
		$staff_holiday_finish_date = NULL;
		$staff_holiday_type = NULL;
		$staff_holiday_reason = NULL;
		$staff_holiday_description = NULL;
		$staff_holiday_hours = NULL;
		$staff_holiday_is_paid = NULL;

		$staffHolidayArray = NULL;
		if (isset($_GET['StaffHolidays'])) {
			$staffHolidayArray = $_GET['StaffHolidays'];
		} else if ($staffHolidays != NULL) {
			$staffHolidayArray = $staffHolidays['StaffHolidays'];
		}

		if ($staffHolidayArray != NULL) {
			if (isset($staffHolidayArray['start_date'])) {
				$staff_holiday_start_date = $staffHolidayArray['start_date'];
			}
			if (isset($staffHolidayArray['return_date'])) {
				$staff_holiday_finish_date = $staffHolidayArray['return_date'];
			}
			if (isset($staffHolidayArray['staff_holidays_reason_id'])) {
				$staff_holiday_reason = $staffHolidayArray['staff_holidays_reason_id'];
			}
			if (isset($staffHolidayArray['staff_holidays_type_id'])) {
				$staff_holiday_type = $staffHolidayArray['staff_holidays_type_id'];
			}
			if (isset($staffHolidayArray['description'])) {
				$staff_holiday_description = $staffHolidayArray['description'];
			}
			if (isset($staffHolidayArray['holiday_hours'])) {
				$staff_holiday_hours = $staffHolidayArray['holiday_hours'];
			}
			if (isset($staffHolidayArray['is_unpaid'])) {
				$staff_holiday_is_paid = $staffHolidayArray['is_unpaid'];
			}
		}

		$model = new StaffHolidays('search');
		$staffHolidaysEntitlementEvents = new StaffHolidaysEntitlementEvents('search');
		$staffModel = StaffPersonalDetails::model()->findByPk($staff_id);
		if (!empty($staffModel)) {
			$invoiceSettingsModel = InvoiceSetting::model()->findByAttributes([
				'branch_id' => $staffModel->branch->id
			]);
			if (!empty($invoiceSettingsModel)) {
				$invoiceParams = array();
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
				$model->staff_id = $staff_id;
				$staffHolidaysEntitlementEvents->unsetAttributes();
				$staffHolidaysEntitlementEvents->holiday_id = $entitlement_id;
				$used = number_format(StaffHolidaysEntitlement::model()->getUsed($entitlement_id), 2, ".", " ");
				$balance = number_format((StaffHolidaysEntitlement::model()->findByPk($entitlement_id)->holiday_entitlement_per_year - $used), 2, ".", " ");
				$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 45, 30, 2.5, 1, 'P');
				$mpdf->WriteHTML($this->renderPartial('staffHolidaysStatementPdf', array(
						'model' => $model,
						'staffHolidaysEntitlementEvents' => $staffHolidaysEntitlementEvents,
						'entitlement_year' => $entitlement_year,
						'entitlement_id' => $entitlement_id,
						'invoiceSettingsModel' => $invoiceSettingsModel,
						'invoiceParams' => $invoiceParams,
						'staffModel' => $staffModel,
						'used' => $used,
						'balance' => $balance,
						'staff_holiday_start_date' => $staff_holiday_start_date,
						'staff_holiday_finish_date' => $staff_holiday_finish_date,
						'staff_holiday_type' => $staff_holiday_type,
						'staff_holiday_reason' => $staff_holiday_reason,
						'staff_holiday_description' => $staff_holiday_description,
						'staff_holiday_hours' => $staff_holiday_hours,
						'staff_holiday_is_paid' => $staff_holiday_is_paid
						), true));
				if ($attachment_type == "D") {
					$mpdf->Output('Staff Holidays Statement.pdf', $attachment_type);
					if ($exit) {
						exit();
					}
				} else {
					$attachment = $mpdf->Output('Staff Holidays Statement.pdf', $attachment_type);
					return $attachment;
				}
			} else {
				exit();
			}
		}
	}

	public function actionSendStaffHolidaysStatement() {
		if (Yii::app()->request->isAjaxRequest) {
			$staffHolidays = NULL;
			if (isset($_GET['StaffHolidays'])) {
				$staffHolidays['StaffHolidays'] = $_GET['StaffHolidays'];
			}
			$model = new StaffHolidaysStatementForm();
			$model->attributes = $_POST['StaffHolidaysStatementForm'];
			$data = CJSON::decode($_POST['data']);
			if ($model->validate()) {
				$staffModel = StaffPersonalDetails::model()->findByPk($data['staff_id']);
				$subject = $model->subject;
				$content = $model->message;
				$to = explode(",", $model->to);
				$sentResponse = array();
				foreach ($to as $key => $value) {
					$attachment = $this->actionPdfStaffHolidaysStatement($data['staff_id'], $data['entitlement_id'], $data['year'], "S", false, $staffHolidays);
					$isSent = customFunctions::sendPdfAttachmentEmail($value, $staffModel->name, $subject, $content, "no-reply@eylog.co.uk", $attachment, $staffModel->branch->company->name);
					if ($isSent == true) {
						$sentResponse[] = "Email has been successfully sent to - " . $value;
					} else {
						$sentResponse[] = "Their seems to be some problems sending statement to - " . $value;
					}
				}
				echo CJSON::encode(array(
					'status' => 1,
					'message' => $sentResponse
				));
				Yii::app()->end();
			} else {
				echo CJSON::encode(array(
					'status' => 0,
					'message' => $model->getErrors()
				));
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	/*
	 * Function to move staff from casual to permanent
	 */

	public function actionCasualToPermanent($staff_id) {
		if (Yii::app()->request->isAjaxRequest) {
			$staffModel = StaffPersonalDetails::model()->findByPk($staff_id);
			if ($staffModel) {
				$isResetFutureContractType = ($_POST['reset_future_contract_type'] == "false") ? false : true;
				$validateStaffHolidaysEntitlementModel = new StaffHolidaysEntitlement;
				$validateStaffHolidaysEntitlementModel->scenario = "changeContractType";
				$validateStaffHolidaysEntitlementModel->attributes = $_POST['StaffHolidaysEntitlement'];
				$validateStaffHolidaysEntitlementModel->effective_date = $_POST['effective_date'];
				$validateStaffHolidaysEntitlementModel->staff_id = $staffModel->id;
				$validateStaffHolidaysEntitlementModel->branch_id = $staffModel->branch_id;
				if (!$validateStaffHolidaysEntitlementModel->validate()) {
					echo CJSON::encode([
						'status' => 0,
						'error' => $validateStaffHolidaysEntitlementModel->getErrors()
					]);
					Yii::app()->end();
				}
				$transaction = Yii::app()->db->beginTransaction();
				try {
					$staffHolidaysEntitlementsModel = StaffHolidaysEntitlement::model()->findAll([
						'condition' => 'staff_id = :staff_id AND year >= :year',
						'params' => [
							':staff_id' => $staffModel->id,
							':year' => date("Y", strtotime($_POST['effective_date']))
						]
					]);
					if (!empty($staffHolidaysEntitlementsModel)) {
						foreach ($staffHolidaysEntitlementsModel as $staffHolidaysEntitlement) {
							$pervStaffHolidaysEntitlement = clone $staffHolidaysEntitlement;
							if($staffHolidaysEntitlement->year == date("Y", strtotime($_POST['effective_date']))){
								$staffHolidaysEntitlement->contract_hours_per_week = $validateStaffHolidaysEntitlementModel->new_contract_hours;
								$staffHolidaysEntitlement->days_per_week = $validateStaffHolidaysEntitlementModel->new_contract_no_of_days;
								$staffHolidaysEntitlement->days_per_year = $validateStaffHolidaysEntitlementModel->new_contract_no_of_days_per_year;
								if (!$staffHolidaysEntitlement->save()) {
									throw new JsonException(
									$staffHolidaysEntitlement->getErrors()
									);
								}
							} else {
								if($isResetFutureContractType){
									$staffHolidaysEntitlement->contract_hours_per_week = $validateStaffHolidaysEntitlementModel->new_contract_hours;
									$staffHolidaysEntitlement->days_per_week = $validateStaffHolidaysEntitlementModel->new_contract_no_of_days;
									$staffHolidaysEntitlement->days_per_year = $validateStaffHolidaysEntitlementModel->new_contract_no_of_days_per_year;
									if (!$staffHolidaysEntitlement->save()) {
										throw new JsonException(
										$staffHolidaysEntitlement->getErrors()
										);
									}
								}
							}
							if ($staffHolidaysEntitlement->year == date("Y", strtotime($_POST['effective_date']))) {
								StaffHolidaysEntitlementEvents::model()->updateAll(['is_deleted' => 1], 'holiday_id = :holiday_id', [':holiday_id' => $staffHolidaysEntitlement->id]);
								$staffHolidaysEntitlementEvents = new StaffHolidaysEntitlementEvents();
								$staffHolidaysEntitlementEvents->holiday_id = $staffHolidaysEntitlement->id;
								$staffHolidaysEntitlementEvents->start_date = $staffHolidaysEntitlement->start_date;
								$staffHolidaysEntitlementEvents->finish_date = date("d-m-Y", strtotime('-1 day', strtotime($_POST['effective_date'])));
								$staffHolidaysEntitlementEvents->entitlement = StaffPersonalDetails::getCasualStaffEntitlement($staffModel->id, $staffHolidaysEntitlementEvents->start_date, $staffHolidaysEntitlementEvents->finish_date);
								$staffHolidaysEntitlementEvents->contract_hours = $pervStaffHolidaysEntitlement->contract_hours_per_week;
								$staffHolidaysEntitlementEvents->branch_id = $staffModel->branch_id;
								$staffHolidaysEntitlementEvents->no_of_days = (int) $pervStaffHolidaysEntitlement->days_per_week;
								$staffHolidaysEntitlementEvents->opening_balance = 1;
								if (!$staffHolidaysEntitlementEvents->save()) {
									throw new JsonException(
										$staffHolidaysEntitlementEvents->getErrors()
									);
								}
								$total_entitlement += $staffHolidaysEntitlementEvents->entitlement;
								$staffHolidaysEntitlementEvents = new StaffHolidaysEntitlementEvents();
								$staffHolidaysEntitlementEvents->holiday_id = $staffHolidaysEntitlement->id;
								$staffHolidaysEntitlementEvents->start_date = date("Y-m-d", strtotime($_POST['effective_date']));
								$staffHolidaysEntitlementEvents->finish_date = $staffHolidaysEntitlement->finish_date;
								$staffHolidaysEntitlementEvents->entitlement = $staffHolidaysEntitlement->getEntitlement(date("Y", strtotime($_POST['effective_date'])), $staffHolidaysEntitlementEvents->start_date, $staffHolidaysEntitlementEvents->finish_date, $staffHolidaysEntitlement->contract_hours_per_week, $staffHolidaysEntitlement->days_per_year);
								$staffHolidaysEntitlementEvents->contract_hours = $staffHolidaysEntitlement->contract_hours_per_week;
								$staffHolidaysEntitlementEvents->branch_id = $staffModel->branch_id;
								$staffHolidaysEntitlementEvents->no_of_days = (int) $staffHolidaysEntitlement->days_per_week;
								if (!$staffHolidaysEntitlementEvents->save()) {
									throw new JsonException(
										$staffHolidaysEntitlementEvents->getErrors()
									);
								}
								$total_entitlement += $staffHolidaysEntitlementEvents->entitlement;
								StaffHolidaysEntitlement::model()->updateByPk($staffHolidaysEntitlement->id, ['holiday_entitlement_per_year' => $total_entitlement]);
							} else {
								StaffHolidaysEntitlementEvents::model()->updateAll(['is_deleted' => 1], 'holiday_id = :holiday_id', [':holiday_id' => $staffHolidaysEntitlement->id]);
								$staffHolidaysEntitlementEvents = new StaffHolidaysEntitlementEvents();
								$staffHolidaysEntitlementEvents->holiday_id = $staffHolidaysEntitlement->id;
								$staffHolidaysEntitlementEvents->start_date = $staffHolidaysEntitlement->start_date;
								$staffHolidaysEntitlementEvents->finish_date = $staffHolidaysEntitlement->finish_date;
								$staffHolidaysEntitlementEvents->entitlement = $staffHolidaysEntitlement->getEntitlement(date("Y", strtotime($_POST['effective_date'])), $staffHolidaysEntitlement->start_date, $staffHolidaysEntitlement->finish_date, $staffHolidaysEntitlement->contract_hours_per_week, $staffHolidaysEntitlement->days_per_year);
								$staffHolidaysEntitlementEvents->contract_hours = $staffHolidaysEntitlement->contract_hours_per_week;
								$staffHolidaysEntitlementEvents->branch_id = $staffModel->branch_id;
								$staffHolidaysEntitlementEvents->no_of_days = (int) $staffHolidaysEntitlement->days_per_week;
								if (!$staffHolidaysEntitlementEvents->save()) {
									throw new JsonException(
										$staffHolidaysEntitlementEvents->getErrors()
									);
								}
								StaffHolidaysEntitlement::model()->updateByPk($staffHolidaysEntitlement->id, ['holiday_entitlement_per_year' => $staffHolidaysEntitlementEvents->entitlement]);
							}
						}
						StaffPersonalDetails::model()->updateByPk($staffModel->id, ['is_casual_staff' => 0]);
					}
					$transaction->commit();
					Yii::app()->user->setFlash('success', "Staff has been successfully marked as permanent.");
					echo CJSON::encode([
						'status' => 1,
						'url' => Yii::app()->createAbsoluteUrl('staffHolidaysEntitlement/index', ['staff_id' => $staffModel->id])
					]);
					Yii::app()->end();
				} catch (JsonException $ex) {
					$transaction->rollback();
					echo CJSON::encode([
						'status' => 0,
						'error' => 'Seems some problme changing the contract of staff.'
					]);
					Yii::app()->end();
				}
			} else {
				throw new CHttpException(404, 'Staff does not exists or is marked as deleted/Inactive.');
			}
		} else {
			throw new Exception(404, "This request is not valid.");
		}
	}

	/*
	 * Function to move staff from permanent to casual
	 */

	public function actionPermanentToCasual($staff_id) {
		if (Yii::app()->request->isAjaxRequest) {
			$staffModel = StaffPersonalDetails::model()->findByPk($staff_id);
			if ($staffModel) {
				$isResetFutureContractType = ($_POST['reset_future_contract_type'] == "false") ? false : true;
				$validateStaffHolidaysEntitlementModel = new StaffHolidaysEntitlement;
				$validateStaffHolidaysEntitlementModel->scenario = "changeContractType";
				$validateStaffHolidaysEntitlementModel->attributes = $_POST['StaffHolidaysEntitlement'];
				$validateStaffHolidaysEntitlementModel->effective_date = $_POST['effective_date'];
				$validateStaffHolidaysEntitlementModel->staff_id = $staffModel->id;
				$validateStaffHolidaysEntitlementModel->branch_id = $staffModel->branch_id;
				if (!$validateStaffHolidaysEntitlementModel->validate()) {
					echo CJSON::encode([
						'status' => 0,
						'error' => $validateStaffHolidaysEntitlementModel->getErrors()
					]);
					Yii::app()->end();
				}
				$transaction = Yii::app()->db->beginTransaction();
				try {
					$staffHolidaysEntitlementsModel = StaffHolidaysEntitlement::model()->findAll([
						'condition' => 'staff_id = :staff_id AND year >= :year',
						'params' => [
							':staff_id' => $staffModel->id,
							':year' => date("Y", strtotime($_POST['effective_date']))
						]
					]);
					if (!empty($staffHolidaysEntitlementsModel)) {
						foreach ($staffHolidaysEntitlementsModel as $staffHolidaysEntitlement) {
							$pervStaffHolidaysEntitlement = clone $staffHolidaysEntitlement;
							if($staffHolidaysEntitlement->year == date("Y", strtotime($_POST['effective_date']))){
								$staffHolidaysEntitlement->contract_hours_per_week = $validateStaffHolidaysEntitlementModel->new_contract_hours;
								$staffHolidaysEntitlement->days_per_week = $validateStaffHolidaysEntitlementModel->new_contract_no_of_days;
								$staffHolidaysEntitlement->days_per_year = $validateStaffHolidaysEntitlementModel->new_contract_no_of_days_per_year;
								if (!$staffHolidaysEntitlement->save()) {
									throw new JsonException(
									$staffHolidaysEntitlement->getErrors()
									);
								}
							} else {
								if($isResetFutureContractType){
									$staffHolidaysEntitlement->contract_hours_per_week = $validateStaffHolidaysEntitlementModel->new_contract_hours;
									$staffHolidaysEntitlement->days_per_week = $validateStaffHolidaysEntitlementModel->new_contract_no_of_days;
									$staffHolidaysEntitlement->days_per_year = $validateStaffHolidaysEntitlementModel->new_contract_no_of_days_per_year;
									if (!$staffHolidaysEntitlement->save()) {
										throw new JsonException(
										$staffHolidaysEntitlement->getErrors()
										);
									}
								}
							}
							if ($staffHolidaysEntitlement->year == date("Y", strtotime($_POST['effective_date']))) {
								StaffHolidaysEntitlementEvents::model()->updateAll(['is_deleted' => 1], 'holiday_id = :holiday_id', [':holiday_id' => $staffHolidaysEntitlement->id]);
								$total_entitlement = 0;
								$staffHolidaysEntitlementEvents = new StaffHolidaysEntitlementEvents();
								$staffHolidaysEntitlementEvents->holiday_id = $staffHolidaysEntitlement->id;
								$staffHolidaysEntitlementEvents->start_date = $staffHolidaysEntitlement->start_date;
								$staffHolidaysEntitlementEvents->finish_date = date("d-m-Y", strtotime('-1 day', strtotime($_POST['effective_date'])));
								$staffHolidaysEntitlementEvents->entitlement = $staffHolidaysEntitlement->getEntitlement(date("Y", strtotime($_POST['effective_date'])), $staffHolidaysEntitlementEvents->start_date, $staffHolidaysEntitlementEvents->finish_date, $pervStaffHolidaysEntitlement->contract_hours_per_week, $pervStaffHolidaysEntitlement->days_per_year);
								$staffHolidaysEntitlementEvents->contract_hours = $pervStaffHolidaysEntitlement->contract_hours_per_week;
								$staffHolidaysEntitlementEvents->branch_id = $staffModel->branch_id;
								$staffHolidaysEntitlementEvents->no_of_days = (int) $pervStaffHolidaysEntitlement->days_per_week;
								$staffHolidaysEntitlementEvents->opening_balance = 1;
								if (!$staffHolidaysEntitlementEvents->save()) {
									throw new Exception(CHtml::errorSummary($staffHolidaysEntitlementEvents, "", "", array(
										'class' => 'customErrors'
									)));
								}
								$total_entitlement += $staffHolidaysEntitlementEvents->entitlement;
								$staffHolidaysEntitlementEvents = new StaffHolidaysEntitlementEvents();
								$staffHolidaysEntitlementEvents->holiday_id = $staffHolidaysEntitlement->id;
								$staffHolidaysEntitlementEvents->start_date = date("Y-m-d", strtotime($_POST['effective_date']));
								$staffHolidaysEntitlementEvents->finish_date = $staffHolidaysEntitlement->finish_date;
								$staffHolidaysEntitlementEvents->entitlement = StaffPersonalDetails::getCasualStaffEntitlement($staffModel->id, $staffHolidaysEntitlementEvents->start_date, $staffHolidaysEntitlementEvents->finish_date);
								$staffHolidaysEntitlementEvents->contract_hours = $staffHolidaysEntitlement->contract_hours_per_week;
								$staffHolidaysEntitlementEvents->branch_id = $staffModel->branch_id;
								$staffHolidaysEntitlementEvents->no_of_days = (int) $staffHolidaysEntitlement->days_per_week;
								if (!$staffHolidaysEntitlementEvents->save()) {
									throw new Exception(CHtml::errorSummary($staffHolidaysEntitlementEvents, "", "", array(
										'class' => 'customErrors'
									)));
								}
								$total_entitlement += $staffHolidaysEntitlementEvents->entitlement;
								StaffHolidaysEntitlement::model()->updateByPk($staffHolidaysEntitlement->id, ['holiday_entitlement_per_year' => $total_entitlement]);
							} else {
								StaffHolidaysEntitlementEvents::model()->updateAll(['is_deleted' => 1], 'holiday_id = :holiday_id', [':holiday_id' => $staffHolidaysEntitlement->id]);
								$staffHolidaysEntitlementEvents = new StaffHolidaysEntitlementEvents();
								$staffHolidaysEntitlementEvents->holiday_id = $staffHolidaysEntitlement->id;
								$staffHolidaysEntitlementEvents->start_date = $staffHolidaysEntitlement->start_date;
								$staffHolidaysEntitlementEvents->finish_date = $staffHolidaysEntitlement->finish_date;
								$staffHolidaysEntitlementEvents->entitlement = StaffPersonalDetails::getCasualStaffEntitlement($staffModel->id, $staffHolidaysEntitlementEvents->start_date, $staffHolidaysEntitlementEvents->finish_date);
								$staffHolidaysEntitlementEvents->contract_hours = $staffHolidaysEntitlement->contract_hours_per_week;
								$staffHolidaysEntitlementEvents->branch_id = $staffModel->branch_id;
								$staffHolidaysEntitlementEvents->no_of_days = (int) $staffHolidaysEntitlement->days_per_week;
								if (!$staffHolidaysEntitlementEvents->save()) {
									throw new Exception(CHtml::errorSummary($staffHolidaysEntitlementEvents, "", "", array(
										'class' => 'customErrors'
									)));
								}
								StaffHolidaysEntitlement::model()->updateByPk($staffHolidaysEntitlement->id, ['holiday_entitlement_per_year' => $staffHolidaysEntitlementEvents->entitlement]);
							}
						}
						StaffPersonalDetails::model()->updateByPk($staffModel->id, ['is_casual_staff' => 1]);
					}
					$transaction->commit();
					Yii::app()->user->setFlash('success', "Staff has been successfully marked as casual.");
					echo CJSON::encode([
						'status' => 1,
						'url' => Yii::app()->createAbsoluteUrl('staffHolidaysEntitlement/index', ['staff_id' => $staffModel->id])
					]);
					Yii::app()->end();
				} catch (JsonException $ex) {
					$transaction->rollback();
					echo CJSON::encode([
						'status' => 0,
						'error' => 'Seems some problme changing the contract of staff.'
					]);
					Yii::app()->end();
				}
			} else {
				throw new CHttpException(404, 'Staff does not exists or is marked as deleted/Inactive.');
			}
		} else {
			throw new Exception(404, "This request is not valid.");
		}
	}

	public function actionRemoveCasualStaffEntitlement() {
		$staffModel = StaffPersonalDetails::model()->findAllByAttributes([
			'is_casual_staff' => 1,
		]);
		if ($staffModel) {
			foreach ($staffModel as $staff) {
				StaffHolidaysEntitlement::model()->updateAll(['is_deleted' => 1], 'staff_id = :staff_id', [':staff_id' => $staff->id]);
				StaffPersonalDetails::model()->updateAll(['is_entitlement_created' => 0, 'holiday_entitlement' => 0], 'id = :id', [':id' => $staff->id]);
				echo "Entitlement Removed for staff - " . $staff->name . " " . $staff->branch->name . "</br>";
			}
		} else {
			echo "No Staff Found" . "</br>";
		}
	}

	public function actionResetEntitlement($staff_id) {
		StaffHolidaysEntitlement::model()->updateAll(['is_deleted' => 1], 'staff_id = :staff_id', [
			':staff_id' => $staff_id
		]);
		StaffPersonalDetails::model()->updateByPk($staff_id, ['is_entitlement_created' => 0, 'holiday_entitlement' => NULL]);
		Yii::app()->user->setFlash('success', 'Entitlement has been successfuly reset.');
		$this->redirect(['staffHolidaysEntitlement/index', 'staff_id' => $staff_id]);
	}

}
