<?php

Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseUrl . '/js/singleStaffBooking.js?version=1.0.17', CClientScript::POS_END);
Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseUrl . '/js/selectableScroll.js', CClientScript::POS_END);
Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseUrl . '/js/staffBooking.js?v=1.0.0', CClientScript::POS_END);
Yii::app()->clientScript->registerScript('helpers', '
          eyMan = {
              urls: {
                  confirmStaffSessions: ' . CJSON::encode(Yii::app()->createUrl("staffBookings/confirmSessions")) . ',
                  staffPrefferedActivity: ' . CJSON::encode(Yii::app()->createUrl('staffPersonalDetails/getPefferedActivity')) . ',
                  getStaffUsedHours: ' . CJSON::encode(Yii::app()->createUrl('staffBookings/getStaffUsedHours')) . '
              }
          };
      ', CClientScript::POS_END);

class StaffBookingsController extends eyManController {

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

		return '';
	}

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id) {
		$this->render('view', array(
			'model' => $this->loadModel($id),
		));
	}

	public function actiongetAllStaffSchedule() {
		$model = new StaffBookings;
		if (isset($_POST)) {
			if (isset(Yii::app()->session['branch_id'])) {
				$model->branch_id = Yii::app()->session['branch_id'];
				$branch_ageratio_model = AgeRatio::model()->findByAttributes(array('branch_id' => Yii::app()->session['branch_id']));
				if (!empty($branch_ageratio_model)) {
					$branchModel = Branch::model()->findByAttributes(array('id' => $model->branch_id));
					if (!empty($branchModel)) {
						Yii::app()->session['operation_start_time'] = $branchModel->operation_start_time;
						Yii::app()->session['operation_finish_time'] = $branchModel->operation_finish_time;
					} else {
						Yii::app()->session['operation_start_time'] = '08:00:00';
						Yii::app()->session['operation_finish_time'] = '20:00:00';
					}
					if (Company::currentCompany()->minimum_booking_type == Company::MINIMUM_BOOKING_PER_SETTINGS) {
						Yii::app()->session['minimum_session'] = Company::currentCompany()->minimum_booking_time . ' mins';
					} else {
						$criteria = new CDbCriteria();
						$criteria->select = "id, MIN(minimum_time) AS minimum_time";
						$criteria->condition = "is_active = :is_active AND branch_id = :branch_id AND is_minimum = 1";
						$criteria->params = array(":is_active" => 1, 'branch_id' => Yii::app()->session['branch_id']);
						$minimumBookingSession = SessionRates::model()->find($criteria);
						if (!empty($minimumBookingSession) && trim($minimumBookingSession->minimum_time) != "") {
							Yii::app()->session['minimum_session'] = $minimumBookingSession->minimum_time . ' ' . 'mins';
						} else {
							Yii::app()->session['minimum_session'] = '30 mins';
						}
					}
					$model->room_id = $_POST['change_room_id'];
					$model->branch_id = Yii::app()->session['branch_id'];
					$model->staff_id = $_POST['change_staff_id'];
					$model->activity_id = $_POST['change_activity_id'];
					$model->start_date = $_POST['start_date'];
					$model->finish_date = $_POST['finish_date'];
					if (strtotime($model->finish_date) > strtotime(date("d-m-Y", date(strtotime("+6 day", strtotime($model->start_date)))))) {
						$json_array['error'] = 1;
						$json_array['message'] = "<b class='text-center' align='center'>Date Range can not be more than a week.</b>";
						echo json_encode($json_array);
					} else if (strtotime($model->finish_date) < strtotime($model->start_date)) {
						$json_array['error'] = 1;
						$json_array['message'] = "<b class='text-center' align='center'>Start date must be smaller than finish date.</b>";
						echo json_encode($json_array);
					} else {
						$range = customFunctions::create_time_range(Yii::app()->session['operation_start_time'], Yii::app()->session['operation_finish_time'], Yii::app()->session['minimum_session']);
						$all_dates = customFunctions::getDatesOfDays($model->start_date, $model->finish_date, array(0, 1, 2, 3, 4, 5, 6));
						foreach ($all_dates as $key => $date) {
							if (!in_array(date('w', strtotime($date)), explode(",", $branchModel->nursery_operation_days))) {
								unset($all_dates[$key]);
							}
						}
						$json_array['all_dates'] = array_values($all_dates);
						$json_array['time_range'] = $range;
						Yii::app()->session['time_range'] = $range;
						$all_rooms = customFunctions::getRooms($model->branch_id, $model->room_id, $model->staff_id);
						if (count($all_rooms) > 0) {
							foreach ($all_dates as $date) {
								$json_array['days'][] = date('l', strtotime($date));
								$json_array['data'][] = customFunctions::GetCompleteBookingData($model->branch_id, $date, $model->room_id, $model->start_date, $model->finish_date, $model->staff_id, $model->activity_id, $date);
							}
							echo json_encode($json_array);
						} else {
							$json_array['error'] = 1;
							$json_array['message'] = "<b class='text-center' align='center'>Please select a Room / Group</b>";
							echo json_encode($json_array);
						}
					}
				} else {
					$json_array['error'] = 1;
					$json_array['message'] = "<b class='text-center' align='center'>Please set up Age Ratios for this branch first</b>";
					echo json_encode($json_array);
				}
			}
		} else {
			echo 'Invalid Request';
		}
	}

	/**
	 * function to render all staff scheduling screen
	 */
	public function actionstaffScheduling() {

		$model = new StaffBookings;
		if (isset(Yii::app()->session['branch_id'])) {
			$model->branch_id = Yii::app()->session['branch_id'];
			$branchModel = Branch::model()->findByAttributes(array('id' => $model->branch_id));
			if (!empty($branchModel)) {
				Yii::app()->session['operation_start_time'] = $branchModel->operation_start_time;
				Yii::app()->session['operation_finish_time'] = $branchModel->operation_finish_time;
			} else {
				Yii::app()->session['operation_start_time'] = '08:00:00';
				Yii::app()->session['operation_finish_time'] = '20:00:00';
			}
			$sessionModel = SessionRates::model()->findAllByAttributes(array('branch_id' => $model->branch_id, 'is_minimum' => 1));
			if (!empty($sessionModel)) {
				Yii::app()->session['minimum_session'] = $sessionModel->minimum_time . ' ' . 'mins';
			} else {
				Yii::app()->session['minimum_session'] = '30 mins';
			}
		} else {
			throw new CHttpException(400, 'Branch is not present in the system / Please Create a Branch or select the Old One.');
		}
		$this->render('create_schedule', array(
			'model' => $model,
		));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate() {
		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1)) {
			$model = new StaffBookings;
			$model->room_id = $_POST['room_id'];
			$model->branch_id = Yii::app()->session['branch_id'];
			$model->staff_id = $_POST['staff_id'];
			$model->activity_id = $_POST['activity_id'];
			$model->start_time = date("H:i", strtotime($_POST['start_time']));
			$model->finish_time = date("H:i", strtotime($_POST['finish_time']));
			$model->start_date = $_POST['start_date'];
			$model->finish_date = $_POST['finish_date'];
			$model->date_of_schedule = $_POST['date_of_schedule'];
			$model->booking_group_id = uniqid();
			$model->booking_group_repeat_id = 0;
			$model->booking_group_booking_days = date('w', strtotime($_POST['date_of_schedule']));
			$model->is_booking_override = $_POST['override_existing_schedule'];
			$model->is_step_up = $_POST['is_step_up'];
			$model->notes = $_POST['notes'];
			$criteria = new CDbCriteria();
			$criteria->condition = "date_of_schedule = :date_of_schedule AND staff_id = :staff_id AND is_booking_override = 0 AND (
                    (start_time > :start_time and start_time < :finish_time) OR
                    (finish_time > :start_time and finish_time < :finish_time) OR
                    (start_time < :start_time and finish_time > :finish_time) OR
                    (start_time <= :start_time and finish_time >= :finish_time))";
			$criteria->params = array(":date_of_schedule" => $model->date_of_schedule, ":staff_id" => $model->staff_id, ":start_time" => date("H:i:s", strtotime($model->start_time)), ":finish_time" => date("H:i:s", strtotime($model->finish_time)));
			$checkStaffBookings = StaffBookings::model()->findAll($criteria);
			$error = "";
			if (!empty($checkStaffBookings)) {
				$error = "<span style='color:red;'>Error:Booking can not be made becasue staff is already scheduled at this time range.</span>";
				echo CJSON::encode(array('status' => 0, 'message' => $error));
			} else {
				if ($model->save()) {
					echo CJSON::encode(array('status' => 1, 'message' => '<span style="color:green;">Booking has been made successfully</span>'));
				} else {
					$error = $model->getErrors();
					$error = $error['activity_id'][0];
				}
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate() {
		if (isset($_POST) && ($_POST['isAjaxRequest'] == 1)) {
			$response = array('status' => 1, 'message' => 'Booking has been successfully updated.');
			$staffBookingModel = $this->loadModel($_POST['booking_id']);
			if (!empty($staffBookingModel)) {
				if ($staffBookingModel->booking_group_repeat_id == 0) {
					$staffBookingModel->staff_id = $_POST['staff_id'];
					$staffBookingModel->room_id = $_POST['room_id'];
					$staffBookingModel->activity_id = $_POST['activity_id'];
					$staffBookingModel->start_time = $_POST['start_time'];
					$staffBookingModel->finish_time = $_POST['finish_time'];
					$staffBookingModel->date_of_schedule = $_POST['date_of_schedule'];
					$staffBookingModel->is_step_up = $_POST['is_step_up'];
					$staffBookingModel->booking_group_id = uniqid();
					$staffBookingModel->booking_group_repeat_id = 0;
					$staffBookingModel->booking_group_booking_days = date('N', strtotime($_POST['date_of_schedule']));
					$staffBookingModel->notes = $_POST['notes'];
					$criteria = new CDbCriteria();
					$criteria->condition = "date_of_schedule = :date_of_schedule AND staff_id = :staff_id AND is_booking_override = 0 AND (
                    (start_time > :start_time and start_time < :finish_time) OR
                    (finish_time > :start_time and finish_time < :finish_time) OR
                    (start_time < :start_time and finish_time > :finish_time) OR
                    (start_time <= :start_time and finish_time >= :finish_time)) AND id NOT IN (:id)";
					$criteria->params = array(":date_of_schedule" => $staffBookingModel->date_of_schedule, ":staff_id" => $staffBookingModel->staff_id, ":start_time" => date("H:i:s", strtotime($staffBookingModel->start_time)), ":finish_time" => date("H:i:s", strtotime($staffBookingModel->finish_time)), ":id" => $staffBookingModel->id);
					$checkStaffBookings = StaffBookings::model()->findAll($criteria);
					if (!empty($checkStaffBookings)) {
						$response['status'] = 1;
						$response['message'] = "<span style='color:red;'>Error:Booking can not be made becasue staff is already scheduled at this time range.</span>";
						echo CJSON::encode($response);
						Yii::app()->end();
					}
					if ($staffBookingModel->save()) {
						echo CJSON::encode($response);
					} else {
						$response['status'] = 0;
						$response['message'] = "Their seems to be some problem updating the booking";
						echo CJSON::encode($response);
					}
				} else {
					$criteria = new CDbCriteria();
					$criteria->condition = "booking_group_id = :booking_group_id AND date_of_schedule > :date_of_schedule AND is_booking_override = 0";
					$criteria->params = array(':booking_group_id' => $staffBookingModel->booking_group_id, ':date_of_schedule' => $staffBookingModel->date_of_schedule);
					$nextStaffBookingModel = StaffBookings::model()->findAll($criteria);
					$staffBookingModel = StaffBookings::model()->findByAttributes(array('booking_group_id' => $staffBookingModel->booking_group_id, 'date_of_schedule' => $staffBookingModel->date_of_schedule));
					$transaction = Yii::app()->db->beginTransaction();
					$batch_id = uniqid();
					$response = array('status' => 1, 'message' => 'Booking has been successfully updated.');
					try {
						if (!empty($nextStaffBookingModel)) {
							$next_batch_id = uniqid();
							foreach ($nextStaffBookingModel as $nextBookings) {
								$nextBookings->booking_group_id = $next_batch_id;
								$nextBookings->save();
							}
						}
						$staffBookingModel->room_id = $_POST['room_id'];
						$staffBookingModel->activity_id = $_POST['activity_id'];
						$staffBookingModel->start_time = $_POST['start_time'];
						$staffBookingModel->finish_time = $_POST['finish_time'];
						$staffBookingModel->booking_group_repeat_id = 0;
						$staffBookingModel->booking_group_id = $batch_id;
						$staffBookingModel->date_of_schedule = $_POST['date_of_schedule'];
						$staffBookingModel->booking_group_booking_days = date('w', strtotime($_POST['date_of_schedule']));
						$staffBookingModel->is_step_up = $_POST['is_step_up'];
						$staffBookingModel->notes = $_POST['notes'];
						$criteriaCheck = new CDbCriteria();
						$criteriaCheck->condition = "date_of_schedule = :date_of_schedule AND staff_id = :staff_id AND is_booking_override = 0 AND (
                    (start_time > :start_time and start_time < :finish_time) OR
                    (finish_time > :start_time and finish_time < :finish_time) OR
                    (start_time < :start_time and finish_time > :finish_time) OR
                    (start_time <= :start_time and finish_time >= :finish_time)) AND id NOT IN (:id)";
						$criteriaCheck->params = array(":date_of_schedule" => $staffBookingModel->date_of_schedule, ":staff_id" => $staffBookingModel->staff_id, ":start_time" => date("H:i:s", strtotime($staffBookingModel->start_time)), ":finish_time" => date("H:i:s", strtotime($staffBookingModel->finish_time)), ":id" => $staffBookingModel->id);
						$checkStaffBookings = StaffBookings::model()->findAll($criteriaCheck);
						if (!empty($checkStaffBookings)) {
							$response['status'] = 1;
							$response['message'] = "<span style='color:red;'>Error:Booking can not be made becasue staff is already scheduled at this time range.</span>";
							echo CJSON::encode($response);
							Yii::app()->end();
						}
						if ($staffBookingModel->save()) {
							$transaction->commit();
							echo CJSON::encode($response);
						} else {
							$response = array('status' => '0', 'message' => 'Their seems to be some problem updating the bookings.');
						}
					} catch (Exception $ex) {
						$transaction->rollback();
						$response = array('status' => '0', 'message' => 'Their seems to be some problem updating the bookings.');
						echo CJSON::encode($response);
					}
				}
			} else {
				$response['status'] = 1;
				$response['message'] = "No bookings could be found for the current selection";
				echo CJSON::encode($response);
			}
		} else {
			throw new CHttpException(404, "Your request is not valid");
		}
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete() {
		if (isset($_POST) && ($_POST['isAjaxRequest'] == 1)) {
			$response = array(
				'status' => 0,
				'message' => 'No bookings could be found for the current selection'
			);
			$staffBookingModel = StaffBookings::model()->findByPk($_POST['booking_id']);
			if (!empty($staffBookingModel)) {
				if ($staffBookingModel->booking_group_repeat_id == 0) {
					$staffBookingModel->is_deleted = 1;
					if ($staffBookingModel->save()) {
						$response['status'] = 1;
						$response['message'] = 'Booking has been successfully deleted';
						echo CJSON::encode($response);
					} else {
						$response['status'] = 1;
						$response['message'] = 'Their seems to be some problem deleting the booking';
						echo CJSON::encode($response);
					}
				} else {
					$criteria = new CDbCriteria();
					$criteria->condition = "booking_group_id = :booking_group_id AND date_of_schedule > :date_of_schedule AND is_booking_override = 0";
					$criteria->params = array(':booking_group_id' => $staffBookingModel->booking_group_id, ':date_of_schedule' => $staffBookingModel->date_of_schedule);
					$nextStaffBookingModel = StaffBookings::model()->findAll($criteria);
					$staffBookingModel = StaffBookings::model()->findByAttributes(array('booking_group_id' => $staffBookingModel->booking_group_id, 'date_of_schedule' => $staffBookingModel->date_of_schedule));
					$transaction = Yii::app()->db->beginTransaction();
					$batch_id = uniqid();
					$response = array('status' => 1, 'message' => 'Booking has been successfully deleted.');
					try {
						if (!empty($nextStaffBookingModel)) {
							$next_batch_id = uniqid();
							foreach ($nextStaffBookingModel as $nextBookings) {
								$nextBookings->booking_group_id = $next_batch_id;
								$nextBookings->save();
							}
						}
						$staffBookingModel->is_deleted = 1;
						if ($staffBookingModel->save()) {
							$transaction->commit();
							echo CJSON::encode($response);
						} else {
							$response = array('status' => 1, 'message' => 'Their seems to be some problem deleting the bookings.');
						}
					} catch (Exception $ex) {
						$transaction->rollback();
						$response = array('status' => 1, 'message' => 'Their seems to be some problem deleting the bookings.');
						echo CJSON::encode($response);
					}
				}
			} else {
				echo CJSON::encode($response);
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid');
		}
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex($staff_id) {
		$model = new StaffBookings('singleStaffScheduling');
		$staffModel = StaffPersonalDetails::model()->findByPk($staff_id);
		if (empty($staffModel)) {
			throw new CHttpException(404, "This page does not exists.");
		}
		Yii::app()->session['branch_id'] = $staffModel->branch_id;
		$branchModal = Branch::currentBranch();
		$isBranchEmpty = true;
		if (!empty($branchModal)) {
			$isBranchEmpty = FALSE;
		}
		if (Company::currentCompany()->minimum_booking_type == Company::MINIMUM_BOOKING_PER_SETTINGS) {
			$minimumBookingSessionTime = "00:" .Company::currentCompany()->minimum_booking_time.":00";
		} else {
			$criteria = new CDbCriteria();
			$criteria->select = "id, MIN(minimum_time) AS minimum_time";
			$criteria->condition = "is_active = :is_active AND branch_id = :branch_id AND is_minimum = 1";
			$criteria->params = array(":is_active" => 1, 'branch_id' => $branchModal->id);
			$minimumBookingSession = SessionRates::model()->find($criteria);
			if (!empty($minimumBookingSession) && $minimumBookingSession->minimum_time < 30 && $minimumBookingSession->minimum_time != "") {
				$minimumBookingSessionTime = "00:" . $minimumBookingSession->minimum_time . ":00";
			} else {
				$minimumBookingSessionTime = "00:30:00";
			}
		}
		$nurseryOperationDays = array_diff(array(0, 1, 2, 3, 4, 5, 6), explode(",", $branchModal->nursery_operation_days));
		$nurseryOperationDays = implode(",", $nurseryOperationDays);
		$contracted_hours = sprintf("%0.2f", $staffModel->contract_hours);
		$used_hours = sprintf("%0.2f", $this->actionGetStaffUsedHours($staffModel->id, customFunctions::currentWeekRange()[0], customFunctions::currentWeekRange()[1]));
		$this->render('index', array(
			'model' => $model,
			'staff_id' => $staff_id,
			'branchModal' => $branchModal,
			'isBranchEmpty' => $isBranchEmpty,
			'staffModel' => $staffModel,
			'minimumBookingSessionTime' => $minimumBookingSessionTime,
			'nurseryOperationDays' => $nurseryOperationDays,
			'used_hours' => $used_hours,
			'contracted_hours' => $contracted_hours,
			'holidayModel' => new StaffHolidays
		));
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin() {
		$model = new StaffBookings('search');
		$model->unsetAttributes(); // clear any default values
		if (isset($_GET['StaffBookings']))
			$model->attributes = $_GET['StaffBookings'];

		$this->render('admin', array(
			'model' => $model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return StaffBookings the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id) {
		$model = StaffBookings::model()->findByPk($id);
		if ($model === null)
			throw new CHttpException(404, 'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param StaffBookings $model the model to be validated
	 */
	protected function performAjaxValidation($model) {
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'staff-bookings-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	public function actionGetStaffBookingById() {
		if (isset($_POST) && ($_POST['isAjaxRequest'] == 1)) {
			$response = array('status' => 1, 'message' => 'No bookings could be found for the current selection.');
			$bookingId = $_POST['bookingId'];
			$staffBookingModel = StaffBookings::model()->findByPk($bookingId);
			if (!empty($staffBookingModel)) {
				$changes_allowed = ($staffBookingModel->is_booking_confirm == 1 && Yii::app()->session['role'] != "hrAdmin") ? 0 : 1;
				echo CJSON::encode(array('data' => $staffBookingModel, 'changes_allowed' => $changes_allowed));
			} else {
				echo CJSON::encode($response);
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid');
		}
	}

	public function actionCreateSingleStaffScheduling() {
		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1)) {
			$model = new StaffBookings;
			$model->setScenario('singleStaffScheduling');
			$model->attributes = $_POST['StaffBookings'];
			$model->start_time = date("H:i", strtotime($_POST['StaffBookings']['start_time']));
			$model->finish_time = date("H:i", strtotime($_POST['StaffBookings']['finish_time']));
			if ($model->validate(array('staff_id', 'branch_id', 'room_id', 'activity_id', 'start_date', 'finish_date', 'start_time', 'finish_time'))) {
				$startDate = preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$3-$2-$1", $_POST['StaffBookings']['start_date']);
				$finishDate = preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$3-$2-$1", $_POST['StaffBookings']['finish_date']);
				$bookingDays = customFunctions::getDatesOfDays($startDate, $finishDate, explode(',', $_POST['StaffBookings']['booking_days']));
				$bookingDaysString = "'" . implode("','", $bookingDays) . "'";
				$response = array('status' => 1, 'message' => 'Booking has been successfully created');
				$criteriaCheck = new CDbCriteria();
				$criteriaCheck->condition = "date_of_schedule IN (" . $bookingDaysString . ") AND staff_id = :staff_id AND is_booking_override = 0 AND (
                    (start_time > :start_time and start_time < :finish_time) OR
                    (finish_time > :start_time and finish_time < :finish_time) OR
                    (start_time < :start_time and finish_time > :finish_time) OR
                    (start_time <= :start_time and finish_time >= :finish_time))";
				$criteriaCheck->params = array(":date_of_schedule" => $bookingDaysString, ":staff_id" => $_POST['StaffBookings']['staff_id'], ":start_time" => date("H:i:s", strtotime($_POST['StaffBookings']['start_time'])), ":finish_time" => date("H:i:s", strtotime($_POST['StaffBookings']['finish_time'])));
				$checkStaffBookings = StaffBookings::model()->findAll($criteriaCheck);
				if (!empty($checkStaffBookings)) {
					echo CJSON::encode(array('status' => 1, 'message' => "<span style='color:red;'>Error:Booking can not be made becasue staff is already scheduled at this time range.</span>"));
					exit();
				}
				$transaction = Yii::app()->db->beginTransaction();
				$flag = TRUE;
				$batch_id = uniqid();
				try {
					foreach ($bookingDays as $day) {
						$staffBookingModel = new StaffBookings;
						$staffBookingModel->attributes = $_POST['StaffBookings'];
						$staffBookingModel->date_of_schedule = $day;
						$staffBookingModel->booking_group_id = $batch_id;
						$staffBookingModel->booking_group_booking_days = $_POST['StaffBookings']['booking_days'];
						$staffBookingModel->booking_group_repeat_id = $_POST['StaffBookings']['repeat_id'];
						$staffBookingModel->is_step_up = $_POST['StaffBookings']['is_step_up'];
						if ($staffBookingModel->save()) {
							$flag = true;
						} else {
							$flag = false;
							$transaction->rollback();
							return false;
						}
					}
					if ($flag) {
						$transaction->commit();
						echo CJSON::encode($response);
					} else {
						echo CJSON::encode($staffBookingModel->getErrors());
					}
				} catch (Exception $ex) {
					$transaction->rollback();
					$response = array('status' => '0', 'message' => 'Something went wrong. Please try again back later.');
				}
			} else {
				echo CJSON::encode($model->getErrors());
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionGetSingleStaffScheduling($staff_id, $start, $end) {
		if (Yii::app()->request->isAjaxRequest) {
			$staffDetail = StaffPersonalDetails::model()->findByPk($staff_id);
			$sql = "SELECT *, booking_group_id AS this_booking_group_id, (select min(date_of_schedule) from tbl_staff_bookings where booking_group_id = this_booking_group_id AND is_deleted = 0 ) AS booking_group_start_date, (select max(date_of_schedule) from tbl_staff_bookings where booking_group_id = this_booking_group_id AND is_deleted = 0) AS booking_group_finish_date FROM tbl_staff_bookings where staff_id = :staff_id AND is_deleted = 0 AND date_of_schedule BETWEEN :start AND :end";
			$model = StaffBookings::model()->findAllBySql($sql, array(':staff_id' => $staff_id, ':start' => date("Y-m-d,", strtotime($start)), ':end' => date("Y-m-d", strtotime($end))));
			$data = array();
			if (!empty($model)) {
				foreach ($model as $bookings) {
					$stepup = '';
					if ($bookings->is_step_up == 1) {
						$stepup = " (StepUp)";
					}
					$temp = array();
					$temp['id'] = $bookings->id;
					$temp['title'] = date('D', strtotime($bookings->date_of_schedule)) . $stepup;
					$temp['start'] = $bookings->date_of_schedule . " " . $bookings->start_time;
					$temp['end'] = $bookings->date_of_schedule . " " . $bookings->finish_time;
					$temp['start_date'] = $bookings->booking_group_start_date;
					$temp['finish_date'] = $bookings->booking_group_finish_date;
					$temp['date_of_schedule'] = $bookings->date_of_schedule;
					$temp['activity_id'] = $bookings->activity_id;
					$temp['backgroundColor'] = ($bookings->is_booking_confirm == 0) ? "#" . $bookings->activity->color : "#5484ed";
					$temp['borderColor'] = '#000000';
					$temp['start_time'] = $bookings->start_time;
					$temp['finish_time'] = $bookings->finish_time;
					$temp['booking_group_id'] = $bookings->booking_group_id;
					$temp['booking_group_repeat_id'] = $bookings->booking_group_repeat_id;
					$temp['booking_group_booking_days'] = explode(",", $bookings->booking_group_booking_days);
					$temp['room_id'] = $bookings->room_id;
					$temp['staff_id'] = $bookings->staff_id;
					$temp['branch_id'] = $bookings->branch_id;
					$temp['is_step_up'] = $bookings->is_step_up;
					$temp['notes'] = $bookings->notes;
					$temp['isLeaveEvent'] = false;
					$temp['change_allowed'] = ($bookings->is_booking_confirm && Yii::app()->session['role'] != "hrAdmin") ? 0 : 1;
					$temp['isBranchCalendarHoliday'] = false;
					array_push($data, $temp);
				}
			}

			$staffHolidayModel = StaffHolidays::model()->findAll(array('condition' => '((start_date >= :start_date and start_date <= :return_date) OR (return_date >= :start_date and return_date <= :return_date) OR(start_date <= :start_date and return_date >= :return_date)) AND  staff_id = :staff_id', 'params' => array(':start_date' => date("Y-m-d", strtotime($start)), ':return_date' => date("Y-m-d", strtotime($end)), ':staff_id' => $staff_id)));
			if (!empty($staffHolidayModel)) {
				foreach ($staffHolidayModel as $staffHoliday) {
					$temp = array();
					$temp['id'] = "Staff_holiday_" . $staffHoliday->id;
					$temp['title'] = $staffHoliday->staffHolidaysType->type_of_absence;
					$temp['comments'] = $staffHoliday->comments;
					$temp['start'] = date('Y-m-d', strtotime($staffHoliday->start_date));
					$temp['end'] = date('Y-m-d', strtotime($staffHoliday->return_date . "+1 days"));
					$temp['allDay'] = true;
					$temp['reason'] = $staffHoliday->staff_holidays_reason_id;
					$temp['type'] = $staffHoliday->staff_holidays_type_id;
					$temp['description'] = $staffHoliday->description;
					$temp['today'] = $staffHoliday->today_date;
					$temp['used'] = $used;
					$temp['holiday_hours'] = $staffHoliday->holiday_hours;
					$temp['unpaid'] = $staffHoliday->is_unpaid;
					$temp['balance'] = $balance;
					$temp['backgroundColor'] = ($staffHoliday->is_confirmed == 0) ? $staffHoliday->staffHolidaysType->color : "#5484ed";
					$temp['borderColor'] = '#000000';
					$temp['isLeaveEvent'] = true;
					$temp['holidayId'] = $staffHoliday->id;
					$temp['isBranchCalendarHoliday'] = false;
					array_push($data, $temp);
				}
			}
			echo CJSON::encode($data);
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	public function actionDeleteThisSingleStaffScheduling() {
		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1)) {
			$criteria = new CDbCriteria();
			$criteria->condition = "booking_group_id = :booking_group_id AND date_of_schedule > :date_of_schedule";
			$criteria->params = array(':booking_group_id' => $_POST['booking_group_id'], ':date_of_schedule' => $_POST['date_of_schedule']);
			$nextStaffBookingModel = StaffBookings::model()->findAll($criteria);
			$staffBookingModel = StaffBookings::model()->findByAttributes(array('booking_group_id' => $_POST['booking_group_id'], 'date_of_schedule' => $_POST['date_of_schedule']));
			$transaction = Yii::app()->db->beginTransaction();
			$batch_id = uniqid();
			$response = array('status' => 1, 'message' => 'Booking has been successfully deleted.');
			try {
				if (!empty($nextStaffBookingModel)) {
					$next_batch_id = uniqid();
					foreach ($nextStaffBookingModel as $nextBookings) {
						$nextBookings->booking_group_id = $next_batch_id;
						$nextBookings->save();
					}
				}
				$staffBookingModel->is_deleted = 1;
				if ($staffBookingModel->save()) {
					$transaction->commit();
					echo CJSON::encode($response);
				} else {
					$response = array('status' => '0', 'message' => 'Something went wrong. Please try again back later.');
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				$response = array('status' => '0', 'message' => 'Something went wrong. Please try again back later.');
				echo CJSON::encode($response);
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionDeleteFollowingSingleStaffScheduling() {
		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1)) {
			$criteria = new CDbCriteria();
			$criteria->condition = "booking_group_id = :booking_group_id AND is_deleted = 0 AND date_of_schedule >= :date_of_schedule";
			$criteria->params = array(':booking_group_id' => $_POST['booking_group_id'], ':date_of_schedule' => $_POST['date_of_schedule']);
			$staffBookingModel = StaffBookings::model()->findAll($criteria);
			$transaction = Yii::app()->db->beginTransaction();
			$flag = TRUE;
			$response = array('status' => 1, 'message' => 'Booking has been successfully deleted');
			try {
				foreach ($staffBookingModel as $bookings) {
					$bookings->is_deleted = 1;
					if ($bookings->save()) {
						$flag = true;
					} else {
						$flag = false;
						$transaction->rollback();
						return false;
					}
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				$response = array('status' => '0', 'message' => 'Something went wrong. Please try again back later.');
				echo CJSON::encode($response);
			}
			if ($flag) {
				$transaction->commit();
				echo CJSON::encode($response);
			} else {
				echo CJSON::encode($bookings->getErrors());
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionDeleteAllSingleStaffScheduling() {
		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1)) {
			$staffBookingModel = StaffBookings::model()->findAllByAttributes(array('booking_group_id' => $_POST['booking_group_id']));
			$transaction = Yii::app()->db->beginTransaction();
			$flag = TRUE;
			$response = array('status' => 1, 'message' => 'Booking has been successfully deleted');
			try {
				foreach ($staffBookingModel as $bookings) {
					$bookings->is_deleted = 1;
					if ($bookings->save()) {
						$flag = true;
					} else {
						$flag = false;
						$transaction->rollback();
						return false;
					}
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				$response = array('status' => '0', 'message' => 'Something went wrong. Please try again back later.');
				echo CJSON::encode($response);
			}
			if ($flag) {
				$transaction->commit();
				echo CJSON::encode($response);
			} else {
				echo CJSON::encode($bookings->getErrors());
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionUpdateThisSingleStaffScheduling() {
		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1)) {
			$criteria = new CDbCriteria();
			$criteria->condition = "booking_group_id = :booking_group_id AND date_of_schedule > :date_of_schedule AND is_deleted = 0";
			$criteria->params = array(':booking_group_id' => $_POST['booking_group_id'], ':date_of_schedule' => $_POST['date_of_schedule']);
			$nextStaffBookingModel = StaffBookings::model()->findAll($criteria);
			$staffBookingModel = StaffBookings::model()->findByAttributes(array('booking_group_id' => $_POST['booking_group_id'], 'date_of_schedule' => $_POST['date_of_schedule']));
			$transaction = Yii::app()->db->beginTransaction();
			$batch_id = uniqid();
			$response = array('status' => 1, 'message' => 'Booking has been successfully updated.');
			try {
				if (!empty($nextStaffBookingModel)) {
					$next_batch_id = uniqid();
					foreach ($nextStaffBookingModel as $nextBookings) {
						$nextBookings->booking_group_id = $next_batch_id;
						$nextBookings->save();
					}
				}
				$staffBookingModel->room_id = $_POST['room_id'];
				$staffBookingModel->activity_id = $_POST['activity_id'];
				$staffBookingModel->start_time = $_POST['start_time'];
				$staffBookingModel->finish_time = $_POST['finish_time'];
				$staffBookingModel->booking_group_repeat_id = $_POST['repeat_id'];
				$staffBookingModel->booking_group_id = $batch_id;
				$staffBookingModel->date_of_schedule = $_POST['date_of_schedule'];
				$staffBookingModel->booking_group_booking_days = date('w', strtotime($_POST['date_of_schedule']));
				$staffBookingModel->is_step_up = $_POST['is_step_up'];
				$staffBookingModel->notes = $_POST['notes'];
				if ($staffBookingModel->save()) {
					$transaction->commit();
					echo CJSON::encode($response);
				} else {
					$response = array('status' => '0', 'message' => 'Something went wrong. Please try again back later.');
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				$response = array('status' => '0', 'message' => 'Something went wrong. Please try again back later.');
				echo CJSON::encode($response);
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionUpdateFollowingSingleStaffScheduling() {
		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1)) {
			$criteria = new CDbCriteria();
			$criteria->condition = "booking_group_id = :booking_group_id AND is_deleted = 0 AND date_of_schedule >= :date_of_schedule";
			$criteria->params = array(':booking_group_id' => $_POST['booking_group_id'], ':date_of_schedule' => $_POST['date_of_schedule']);
			$batch_id = uniqid();
			$staffBookingModel = StaffBookings::model()->findAll($criteria);
			$transaction = Yii::app()->db->beginTransaction();
			$flag = TRUE;
			$flag2 = TRUE;
			$response = array('status' => 1, 'message' => 'Booking has been successfully updated');
			try {
				foreach ($staffBookingModel as $bookings) {
					$bookings->is_deleted = 1;
					if ($bookings->save()) {
						$flag = true;
					} else {
						$flag = false;
						$transaction->rollback();
						return false;
					}
				}
				if ($flag == TRUE) {
					$bookingDays = customFunctions::getDatesOfDays(preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$3-$2-$1", $_POST['date_of_schedule']), preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$3-$2-$1", $_POST['finish_date']), explode(',', $_POST['booking_days']));
					foreach ($bookingDays as $day) {
						$model = new StaffBookings;
						$model->staff_id = $_POST['staff_id'];
						$model->room_id = $_POST['room_id'];
						$model->branch_id = $_POST['branch_id'];
						$model->activity_id = $_POST['activity_id'];
						$model->start_time = $_POST['start_time'];
						$model->finish_time = $_POST['finish_time'];
						$model->booking_group_repeat_id = $_POST['repeat_id'];
						$model->booking_group_id = $batch_id;
						$model->date_of_schedule = $day;
						$model->booking_group_booking_days = $_POST['booking_days'];
						$model->is_step_up = $_POST['is_step_up'];
						$model->notes = $_POST['notes'];
						if ($model->save()) {
							$flag2 = true;
						} else {
							$flag2 = false;
							$transaction->rollback();
							return FALSE;
						}
					}
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				$response = array('status' => '0', 'message' => 'Something went wrong. Please try again back later.');
				echo CJSON::encode($response);
			}
			if ($flag && $flag2) {
				$transaction->commit();
				echo CJSON::encode($response);
			} else {
				$transaction->rollback();
				$response = array('status' => '0', 'message' => 'Something went wrong. Please try again back later.');
				echo CJSON::encode($response);
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionUpdateAllSingleStaffScheduling() {
		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1)) {
			$staffBookingModel = StaffBookings::model()->findAllByAttributes(array('booking_group_id' => $_POST['booking_group_id']));
			$transaction = Yii::app()->db->beginTransaction();
			$flag = TRUE;
			$flag2 = TRUE;
			$response = array('status' => 1, 'message' => 'Booking has been successfully updated');
			try {
				foreach ($staffBookingModel as $bookings) {
					$bookings->is_deleted = 1;
					if ($bookings->save()) {
						$flag = true;
					} else {
						$flag = false;
						$transaction->rollback();
						return false;
					}
				}
				if ($flag == TRUE) {
					$bookingDays = customFunctions::getDatesOfDays(preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$3-$2-$1", $_POST['start_date']), preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$3-$2-$1", $_POST['finish_date']), explode(',', $_POST['booking_days']));
					foreach ($bookingDays as $day) {
						$model = new StaffBookings;
						$model->staff_id = $_POST['staff_id'];
						$model->room_id = $_POST['room_id'];
						$model->branch_id = $_POST['branch_id'];
						$model->activity_id = $_POST['activity_id'];
						$model->start_time = $_POST['start_time'];
						$model->finish_time = $_POST['finish_time'];
						$model->booking_group_repeat_id = $_POST['repeat_id'];
						$model->booking_group_id = $_POST['booking_group_id'];
						$model->date_of_schedule = $day;
						$model->booking_group_booking_days = $_POST['booking_days'];
						$model->is_step_up = $_POST['is_step_up'];
						$model->notes = $_POST['notes'];
						if ($model->save()) {
							$flag2 = true;
						} else {
							$flag2 = false;
							$transaction->rollback();
							return FALSE;
						}
					}
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				$response = array('status' => '0', 'message' => 'Something went wrong. Please try again back later.');
				echo CJSON::encode($response);
			}
			if ($flag && $flag2) {
				$transaction->commit();
				echo CJSON::encode($response);
			} else {
				$transaction->rollback();
				$response = array('status' => '0', 'message' => 'Something went wrong. Please try again back later.');
				echo CJSON::encode($response);
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	/*
	 * Function to get the Used & Balance hours on holiday modal
	 */

	public function actionGetUsedBalance($staff_id, $date, $return_date, $id = NULL) {
		if (Yii::app()->request->isAjaxRequest) {
			$model = StaffHolidaysEntitlement::model()->findByAttributes(array('staff_id' => $staff_id, 'year' => date("Y", strtotime($date))));
			$holidayEntitlementPerYear = StaffPersonalDetails::getHolidayEntitlementHoursPerYear($staff_id, $date);
			if (!empty($model)) {
				$totalUsed = 0;
				$criteria = new CDbCriteria();
				$criteria->select = "sum(holiday_hours) AS total_holiday_hours";
				$criteria->condition = "((start_date >= :start_date and start_date <= :return_date) OR " .
					"(return_date >= :start_date and return_date <= :return_date) OR" .
					"(start_date <= :start_date and return_date >= :return_date)) AND  staff_id = :staff_id AND is_unpaid = 0";
				$criteria->params = array(':start_date' => date("Y-m-d", strtotime($model->start_date)), ':return_date' => date("Y-m-d", strtotime($model->finish_date)), ':staff_id' => $model->staff_id);
				$staffHolidayModel = StaffHolidays::model()->find($criteria);
				$staffHolidayModel->total_holiday_hours = customFunctions::round($staffHolidayModel->total_holiday_hours, 2);
				$staffHolidayEntitlementEvents = StaffHolidaysEntitlementEvents::model()->find(array('condition' => ':date BETWEEN start_date and finish_date and holiday_id = :holiday_id AND is_overriden = 0', 'params' => array(':holiday_id' => $model->id, ':date' => date("Y-m-d", strtotime($date)))));
				$holidayHoursUsedThisHoliday = 0;
				if (!empty($staffHolidayEntitlementEvents)) {
					$holidayHoursUsedThisHoliday = customFunctions::round(($staffHolidayEntitlementEvents->contract_hours / $staffHolidayEntitlementEvents->no_of_days) * (((strtotime(date("Y-m-d", strtotime($return_date))) - strtotime(date("Y-m-d", strtotime($date)))) / 86400) + 1), 2);
					$allowedContractHoursPerWeek = $staffHolidayEntitlementEvents->contract_hours;
					$thisWeekDates = customFunctions::getWeekDates(date('W', strtotime($date)), date("Y", strtotime($date)));
					$criteria = new CDbCriteria();
					$criteria->select = "sum(holiday_hours) AS total_holiday_hours";
					if (isset($id) && !empty($id)) {
						$criteria->condition = "((start_date >= :start_date and start_date <= :return_date) OR " .
							"(return_date >= :start_date and return_date <= :return_date) OR" .
							"(start_date <= :start_date and return_date >= :return_date)) AND  staff_id = :staff_id AND is_unpaid = 0 AND id != :id";
						$criteria->params = array(':start_date' => $thisWeekDates['week_start_date'], ':return_date' => $thisWeekDates['week_end_date'], ':staff_id' => $model->staff_id, ':id' => $id);
					} else {
						$criteria->condition = "((start_date >= :start_date and start_date <= :return_date) OR " .
							"(return_date >= :start_date and return_date <= :return_date) OR" .
							"(start_date <= :start_date and return_date >= :return_date)) AND  staff_id = :staff_id AND is_unpaid = 0";
						$criteria->params = array(':start_date' => $thisWeekDates['week_start_date'], ':return_date' => $thisWeekDates['week_end_date'], ':staff_id' => $model->staff_id);
					}
					$holidayHoursUsedThisWeekModel = StaffHolidays::model()->find($criteria);
					$holidayHoursUsedThisWeek = 0;
					if (!empty($holidayHoursUsedThisWeekModel)) {
						$holidayHoursUsedThisWeek = customFunctions::round($holidayHoursUsedThisWeekModel->total_holiday_hours, 2);
					}
					if ($holidayHoursUsedThisHoliday > customFunctions::round(($allowedContractHoursPerWeek - $holidayHoursUsedThisWeek), 2)) {
						$holidayHoursUsedThisHoliday = customFunctions::round(($allowedContractHoursPerWeek - $holidayHoursUsedThisWeek), 2);
						if ($holidayHoursUsedThisHoliday > customFunctions::round(($model->holiday_entitlement_per_year - $staffHolidayModel->total_holiday_hours), 2)) {
							$holidayHoursUsedThisHoliday = customFunctions::round(($model->holiday_entitlement_per_year - $staffHolidayModel->total_holiday_hours), 2);
						}
					} else {
						if ($holidayHoursUsedThisHoliday > customFunctions::round(($model->holiday_entitlement_per_year - $staffHolidayModel->total_holiday_hours), 2)) {
							$holidayHoursUsedThisHoliday = customFunctions::round(($model->holiday_entitlement_per_year - $staffHolidayModel->total_holiday_hours), 2);
						}
					}
				}
				if (isset($id) && !empty($id)) {
					if ($holidayHoursUsedThisHoliday == 0) {
						$holidayHoursUsedThisHoliday = StaffHolidays::model()->findByPk($id)->holiday_hours;
					}
				}
				$response = array('status' => 1, 'message' => 'success', 'holidayHoursUsedThisHoliday' => $holidayHoursUsedThisHoliday, 'used' => customFunctions::round($staffHolidayModel->total_holiday_hours, 2), 'balance' => customFunctions::round(($model->holiday_entitlement_per_year - $staffHolidayModel->total_holiday_hours), 2), 'currentWeekHours' => 0, 'holidayEntitlementPerYear' => $holidayEntitlementPerYear);
				echo CJSON::encode($response);
				Yii::app()->end();
			} else {
				echo CJSON::encode(array('status' => 1, 'message' => 'success', 'used' => customFunctions::round(0, 2), 'balance' => customFunctions::round(0, 2), 'currentWeekHours' => 0, 'holidayEntitlementPerYear' => $holidayEntitlementPerYear, 'holidayHoursUsedThisHoliday' => $holidayHoursUsedThisHoliday));
				Yii::app()->end();
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public static function getDates($start_date, $finish_date) {
		$date_from = $start_date;
		$date_from = strtotime($date_from);
		$date_to = $finish_date;
		$date_to = strtotime($date_to);
		for ($i = $date_from; $i <= $date_to; $i += 86400) {
			$all_dates[] = date("Y-m-d", $i);
		}
		return $all_dates;
	}

	public function actionGetStaffBookingByDate() {
		if (isset($_POST)) {
			if ($_POST['isAjaxRequest'] == 1 && !empty($_POST['startDate']) && !empty($_POST['finishDate'])) {
				$startDate = $_POST['startDate'];
				$finishDate = $_POST['finishDate'];
				$data = customFunctions::staffWagesMonthlyPercentReport($startDate, $finishDate);
				$wagePercent = sprintf("%0.2f", ($data[5]['totalAmount'] * 100) / $data[5]['totalIncomeAmount']);
				if (is_array($data) && !empty($data)) {
					echo CJSON::encode(array('status' => 1, 'data' => $wagePercent));
				} else {
					echo CJSON::encode(array('status' => 0, 'data' => ''));
				}
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionConfirmSessions() {
		if (Yii::app()->request->isAjaxRequest) {
			$events = CJSON::decode($_POST['events']);
			if (!empty($events)) {
				foreach ($events as $event) {
					if ($event['isLeaveEvent'] == 1) {
						StaffHolidays::model()->updateByPk($event['holidayId'], array('is_confirmed' => 1));
					}
					if ($event['isLeaveEvent'] == 0) {
						$model = StaffBookings::model()->findByPk($event['id']);
						$model->is_booking_confirm = 1;
						$model->save(false);
					}
				}
				echo CJSON::encode(array('status' => 1, 'message' => 'Staff Bookings / Holidays has been successfully confirmed.'));
			} else {
				echo CJSON::encode(array('status' => 1, 'message' => 'No Events Present to be confirmed.'));
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionGetStaffUsedHours($staff_id, $week_start_date, $week_end_date) {
		$staffModel = StaffPersonalDetails::model()->findByPk($staff_id);
		if (!empty($staffModel)) {
			$days = customFunctions::getDatesOfDays($week_start_date, $week_end_date, explode(",", $staffModel->branch->nursery_operation_days));
			$total_hours = 0;
			foreach ($days as $date) {
				$day_hours = 0;
				$booking = StaffBookings::model()->findAllByAttributes(['staff_id' => $staffModel->id, 'date_of_schedule' => $date]);
				if (!empty($booking)) {
					foreach ($booking as $data) {
						if ($data->activity->is_unpaid == 0) {
							$day_hours += customFunctions::getHours($data->start_time, $data->finish_time);
						}
					}
				}
				$holidayCriteria = new CDbCriteria();
				$holidayCriteria->condition = "staff_id = :staff_id AND :date BETWEEN start_date AND return_date";
				$holidayCriteria->params = array(":staff_id" => $staffModel->id, ':date' => $date);
				$staffHolidayModal = StaffHolidays::model()->findAll($holidayCriteria);
				if (!empty($staffHolidayModal)) {
					foreach ($staffHolidayModal as $holiday) {
						$holidayDates = customFunctions::getDatesOfDays($holiday->start_date, $holiday->return_date, array(0, 1, 2, 3, 4, 5, 6));
						$holidayEntitlement = ($holiday->holiday_hours / count($holidayDates));
						if ($holiday->is_unpaid == 0) {
							$day_hours += $holidayEntitlement;
						}
					}
				}
				$reductionHours = customFunctions::getStaffBookingHoursPerDay($booking, $staffModel);
				$reductionHours = $reductionHours['working_hours_reduction'];
				$day_hours = $day_hours - $reductionHours;
				$day_hours = ($day_hours < 0) ? 0 : $day_hours;
				$total_hours += $day_hours;
			}
			if (Yii::app()->request->isAjaxRequest) {
				echo CJSON::encode(array('status' => 1, 'hours' => sprintf("%0.2f", $total_hours)));
			} else {
				return sprintf("%0.2f", $total_hours);
			}
		} else {
			if (Yii::app()->request->isAjaxRequest) {
				echo CJSON::encode(array('status' => 0, 'hours' => sprintf("%0.2f", 0)));
			} else {
				return 0;
			}
		}
	}

}
