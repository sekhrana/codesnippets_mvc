<?php

Yii::app()->clientScript->registerScript('helpers', '
          yii = {
              urls: {
                  childBookingsData: ' . CJSON::encode(Yii::app()->createUrl('childBookings/bookingDetails')) . ',
                  getChildBookings: ' . CJSON::encode(Yii::app()->createUrl('childBookings/getChildBookings')) . ',
                  deleteChildEvent: ' . CJSON::encode(Yii::app()->createUrl('childBookings/delete')) . ',
                  sessionSetting: ' . CJSON::encode(Yii::app()->createUrl('childBookings/sessionSetting')) . ',
              }
          };
      ', CClientScript::POS_END);

Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseUrl . '/js/newChildBooking.js?version=1.0.12', CClientScript::POS_END);

class ChildBookingsController extends eyManController {

	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout = '//layouts/column2';

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

	public function actionBookingDetails($child_id) {
		$this->redirect(array('childBookings/index', 'child_id' => $child_id));
	}

	public function actionIndex($child_id) {
		$this->layout = 'dashboard';
		$model = new ChildBookings;
		$holidayModel = new ChildHolidays;
		$childModal = ChildPersonalDetails::model()->findByPk($child_id);
		$sessionModal = SessionRates::model()->findAllByAttributes(array('is_active' => '1', 'branch_id' => Yii::app()->session['branch_id']));
		$branchModal = Branch::model()->findByPk(Yii::app()->session['branch_id']);
		$nurseryOperationDays = array_diff(array(0, 1, 2, 3, 4, 5, 6), explode(",", $branchModal->nursery_operation_days));
		$nurseryOperationDays = implode(",", $nurseryOperationDays);
		$sessionData = $this->actionGetSessions($branchModal->id, $child_id);
		$criteria = new CDbCriteria();
		$criteria->select = "id, MIN(minimum_time) AS minimum_time";
		$criteria->condition = "is_active = :is_active AND branch_id = :branch_id AND is_minimum = 1 and is_modified = 0";
		$criteria->params = array(":is_active" => 1, 'branch_id' => Yii::app()->session['branch_id']);
		$minimumBookingSession = SessionRates::model()->find($criteria);
		if (!empty($minimumBookingSession) && $minimumBookingSession->minimum_time < 30 && $minimumBookingSession->minimum_time != "") {
			$minimumBookingSessionTime = "00:" . $minimumBookingSession->minimum_time . ":00";
		} else {
			$minimumBookingSessionTime = "00:30:00";
		}
		$defaultSessionModal = SessionRates::model()->findByPk($childModal->preffered_session);
		$this->render('index', array('model' => $model, 'holidayModel' => $holidayModel, 'child_id' => $child_id, 'session_data' => $sessionData, 'isSessionPresent' => $isSessionPresent, 'branch' => $branchModal, 'minimumBookingSessionTime' => $minimumBookingSessionTime, 'nurseryOperationDays' => $nurseryOperationDays, 'childModal' => $childModal, 'defaultSessionModal' => $defaultSessionModal));
	}

	public function actionSessionSetting() {
		if (isset($_POST['isAjaxRequest']) && isset($_POST['childId']) && ($_POST['isAjaxRequest'] == 1)) {
			$child_id = $_POST['childId'];
			$branch_id = ChildPersonalDetails::model()->findByPk($child_id)->branch_id;
			$sessionModal = SessionRates::model()->findByAttributes(array('branch_id' => $branch_id, 'status' => 1));
			$sessionSettings = array();
			echo CJSON::encode($sessionModal);
		} else {
			throw new CHttpException(404, 'Your request is Invalid');
		}
	}

	public function actionGetSessions($branch_id, $child_id) {
		$childModal = ChildPersonalDetails::model()->findByPk($child_id);
		$sessionData = array();
		$sessionModal = SessionRates::model()->findAllByAttributes(array('branch_id' => $branch_id, 'is_active' => 1, 'is_modified' => 0));
		if (!empty($sessionModal)) {
			$sessionData = array();
			foreach ($sessionModal as $session) {
				$startDate = $childModal->start_date;
				$finishDate = $childModal->leave_date;
				$tempData['id'] = $session->id;
				$tempData['name'] = $session->name;
				$tempData['is_minimum'] = $session->is_minimum;
				$tempData['minimum_hours'] = $session->minimum_time;
				$tempData['start'] = date('m-d-Y', strtotime($startDate)) . " " . $session->start_time;
				$tempData['end'] = date('m-d-Y', strtotime($finishDate)) . " " . $session->finish_time;
				$tempData['rendering'] = "background";
				$tempData['color'] = $session->color;
				array_push($sessionData, $tempData);
			}
			return $sessionData;
		}
	}

	public function actionGetChildBookings() {
		if (Yii::app()->request->isAjaxRequest) {
			$childModel = ChildPersonalDetails::model()->findByPk($_GET['child_id']);
			if (empty($childModel)) {
				throw new CHttpException(404, "This page does not exists.");
			}
			$data = array();
			$start = date("Y-m-d", strtotime($_GET['start']));
			$end = date("Y-m-d", strtotime($_GET['end']));
			$model = ChildBookings::model()->getBookings($start, $end, $childModel->branch_id, $childModel->id);
			if (!empty($model)) {
				foreach ($model as $booking) {
					$productsData = array();
					$bookingDataProducts = CJSON::decode($booking->childBookingsDetails->booking_products);
					if (!empty($bookingDataProducts)) {
						foreach ($bookingDataProducts as $productId => $productDays) {
							$productDates = customFunctions::getDatesOfDays($booking->start_date, $booking->finish_date, $productDays);
							if (!empty($productDates)) {
								foreach ($productDates as $date) {
									$productsData[$date][] = Products::model()->findByPk($productId)->name;
								}
							}
						}
					}
					$sessionDays = customFunctions::getDatesOfDays($booking->start_date, $booking->finish_date, explode(',', $booking->childBookingsDetails->booking_days));
					foreach ($sessionDays as $key => $value) {
						$tempData = array();
						$tempData['id'] = $booking->id;
						$tempData['title'] = date('D', strtotime($value));
						$tempData['start'] = $value . " " . $booking->start_time;
						$tempData['end'] = $value . " " . $booking->finish_time;
						$tempData['booking_id'] = $booking->id;
						$tempData['start_date'] = $booking->start_date;
						$tempData['finish_date'] = $booking->finish_date;
						$tempData['backgroundColor'] = $booking->childBookingsDetails->booking_color;
						$tempData['borderColor'] = '#000000';
						$tempData['room_id'] = $booking->room_id;
						$tempData['session_type_id'] = $booking->session_type_id;
						$tempData['start_time'] = $booking->start_time;
						$tempData['finish_time'] = $booking->finish_time;
						$tempData['repeat_type_id'] = $booking->repeat_type_id;
						$tempData['booking_days'] = explode(",", $booking->childBookingsDetails->booking_days);
						$tempData['products'] = $booking->childBookingsDetails->booking_products;
						$tempData['exclude_from_funding'] = $booking->exclude_funding;
						$tempData['is_invoiced'] = $booking->is_invoiced;
						$tempData['invoice_id'] = $booking->invoice_id;
						$tempData['invoice_urn'] = $booking->invoice->invoiceUrn;
						$tempData['invoice_url'] = ($booking->is_invoiced == 1) ? $this->createUrl('childInvoice/view', array('child_id' => $booking->child_id, 'invoice_id' => $booking->invoice_id)) : NULL;
						$thisDayProduct = array();
						if (!empty($productsData)) {
							foreach ($productsData as $date => $products) {
								if ($date == $value) {
									if (!empty($products)) {
										foreach ($products as $key2 => $value2) {
											$thisDayProduct[] = $value2;
										}
									}
								}
							}
						}
						$tempData['thisDayProducts'] = (!empty($thisDayProduct)) ? implode(",", $thisDayProduct) : "";
						array_push($data, $tempData);
					}
				}
			}
			$calendarModel = BranchCalendar::model()->findAll(array('condition' => '((start_date BETWEEN :start_date AND :finish_date) OR (finish_date BETWEEN :start_date AND :finish_date)) AND branch_id = :branch_id AND is_deleted = 0', 'params' => array(':start_date' => $start, ':finish_date' => $end, ':branch_id' => $childModel->branch_id)));
			if (!empty($calendarModel)) {
				foreach ($calendarModel as $holiday) {
					$temp = array();
					$temp['id'] = "branch_holiday_" . $holiday->id;
					$temp['title'] = $holiday->name;
					$temp['description'] = $holiday->description;
					$temp['start'] = date('Y-m-d', strtotime($holiday->start_date));
					$temp['end'] = date('Y-m-d', strtotime($holiday->finish_date . "+1 days"));
					$temp['allDay'] = true;
					$temp['isHoliday'] = $holiday->is_holiday;
					$temp['backgroundColor'] = '#EEEDC6';
					array_push($data, $temp);
				}
			}
			$childHolidayModel = ChildHolidays::model()->findAll(array('condition' => 'date BETWEEN :start_date AND :finish_date AND child_id = :child_id', 'params' => array(':start_date' => $start, ':finish_date' => $end, ':child_id' => $childModel->id)));
			foreach ($childHolidayModel as $childHoliday) {
				$temp = array();
				$temp['id'] = "child_holiday_" . $childHoliday->id;
				$temp['title'] = ($childHoliday->holiday_reason == 0) ? 'Sick' : 'Holiday';
				$temp['notes'] = $childHoliday->notes;
				$temp['start'] = date('Y-m-d', strtotime($childHoliday->date));
				$temp['end'] = date('Y-m-d', strtotime($childHoliday->date . "+1 days"));
				$temp['allDay'] = true;
				$temp['reason'] = $childHoliday->holiday_reason;
				$temp['backgroundColor'] = '#f9a7a8';
				$temp['borderColor'] = '#000000';
				$temp['isLeaveEvent'] = true;
				$temp['holidayId'] = $childHoliday->id;
				$temp['excludeFromInvoice'] = $childHoliday->exclude_from_invoice;
				array_push($data, $temp);
			}
			/** Adding terms in the background* */
			$termsModel = Terms::model()->findAll(array('condition' => '((:start_date BETWEEN start_date AND finish_date) OR (:finish_date BETWEEN start_date AND finish_date)) AND branch_id = :branch_id AND is_deleted = 0', 'params' => array(':start_date' => $start, ':finish_date' => $end, ':branch_id' => $childModel->branch_id)));
			/** Adding term holidays in the background* */
			if (!empty($termsModel)) {
				foreach ($termsModel as $termHoliday) {
					if ($termHoliday->holiday_start_date_1 != NULL && $termHoliday->holiday_finish_date_1 != NULL) {
						$temp = array();
						$temp['id'] = "termHoliday_" . $termHoliday->id;
						$temp['title'] = "Terms/Funding Holiday";
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
						$temp['description'] = $termHoliday->description;
						$temp['start'] = date('Y-m-d', strtotime($termHoliday->holiday_start_date_3));
						$temp['end'] = date('Y-m-d', strtotime($termHoliday->holiday_finish_date_3 . "+1 days"));
						$temp['allDay'] = true;
						$temp['isTermHoliday'] = 1;
						$temp['backgroundColor'] = '#EEEDC6';
						array_push($data, $temp);
					}
				}
			}
			echo CJSON::encode($data);
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionBookSession() {
		if (isset($_POST) && Yii::app()->request->isAjaxRequest) {
			if ($_POST['exclude_from_funding'] == 1) {
				$response = array('status' => 1, 'message' => 'Session was successfully booked');
				$bookingDays = customFunctions::getDatesOfDays(preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$3-$1-$2", $_POST['start_date']), preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$3-$1-$2", $_POST['end_date']), explode(",", $_POST['repeatDayNumber']));
				if (!empty($bookingDays)) {
					$transaction = Yii::app()->db->beginTransaction();
					try {
						foreach ($bookingDays as $bookingDay) {
							$model = new ChildBookings;
							$bookingDetailsModel = new ChildBookingsDetails;
							$childModel = ChildPersonalDetails::model()->findByPk($_POST['child_id']);
							$model->child_id = $_POST['child_id'];
							$model->start_date = $bookingDay;
							$model->finish_date = $bookingDay;
							$model->room_id = $_POST['room_id'];
							$model->start_time = $_POST['start_time'];
							$model->finish_time = $_POST['end_time'];
							$model->branch_id = $childModel->branch->id;
							$model->booking_type = $childModel->booking_type;
							$model->session_type_id = $_POST['session_id'];
							$model->repeat_type_id = $_POST['repeat_type_id'];
							$model->createdBy = Yii::app()->user->id;
							$model->exclude_funding = $_POST['exclude_from_funding'];
							if ($model->validate() && $model->save() && $model->customValidation($model->child_id, $model->session_type_id, $model)) {
								$bookingDetailsModel->booking_id = $model->id;
								$bookingDetailsModel->booking_days = date("w", strtotime($model->start_date));
								$bookingDetailsModel->booking_products = isset($_POST['products']) ? CJSON::encode(ChildBookings::getBookingProductsForDay($_POST['products'], $bookingDetailsModel->booking_days)) : NULL;
								$bookingDetailsModel->booking_color = SessionRates::model()->findByPk($_POST['session_id'])->color;
								if (!$bookingDetailsModel->save()) {
									throw new JsonException($bookingDetailsModel->getErrors());
								}
							} else {
								throw new JsonException($model->getErrors());
							}
						}
						$transaction->commit();
						echo CJSON::encode(array('status' => 1, 'message' => 'Child booking was successfully created.'));
					} catch (JsonException $ex) {
						echo CJSON::encode($ex->getOptions());
						$transaction->rollback();
						Yii::app()->end();
					}
				}
			} else {
				$response = array('status' => 1, 'message' => 'Session was successfully booked');
				$transaction = Yii::app()->db->beginTransaction();
				try {
					$model = new ChildBookings;
					$bookingDetailsModel = new ChildBookingsDetails;
					$childModel = ChildPersonalDetails::model()->findByPk($_POST['child_id']);
					$model->child_id = $_POST['child_id'];
					$model->start_date = preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$3-$1-$2", $_POST['start_date']);
					$model->finish_date = preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$3-$1-$2", $_POST['end_date']);
					$model->room_id = $_POST['room_id'];
					$model->start_time = $_POST['start_time'];
					$model->finish_time = $_POST['end_time'];
					$model->branch_id = $childModel->branch->id;
					$model->booking_type = $childModel->booking_type;
					$model->session_type_id = $_POST['session_id'];
					$model->repeat_type_id = $_POST['repeat_type_id'];
					$model->createdBy = Yii::app()->user->id;
					$model->exclude_funding = $_POST['exclude_from_funding'];
					if ($model->validate() && $model->save() && $model->customValidation($model->child_id, $model->session_type_id, $model)) {
						$bookingDetailsModel->booking_id = $model->id;
						$bookingDetailsModel->booking_days = $_POST['repeatDayNumber'];
						$bookingDetailsModel->booking_products = isset($_POST['products']) ? CJSON::encode($_POST['products']) : NULL;
						$bookingDetailsModel->booking_color = SessionRates::model()->findByPk($_POST['session_id'])->color;
						if (!$bookingDetailsModel->save()) {
							throw new JsonException($bookingDetailsModel->getErrors());
						}
						$transaction->commit();
						echo CJSON::encode(array('status' => 1, 'message' => 'Child booking was successfully created.'));
					} else {
						throw new JsonException($model->getErrors());
					}
				} catch (JsonException $ex) {
					echo CJSON::encode($ex->getOptions());
					$transaction->rollback();
					Yii::app()->end();
				}
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid. ');
		}
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin() {
		$model = new ChildBookings('search');
		$model->unsetAttributes(); // clear any default values
		if (isset($_GET['ChildBookings']))
			$model->attributes = $_GET['ChildBookings'];

		$this->render('admin', array(
			'model' => $model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return ChildBookings the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id) {
		$model = ChildBookings::model()->findByPk($id);
		if ($model === null)
			throw new CHttpException(404, 'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param ChildBookings $model the model to be validated
	 */
	protected function performAjaxValidation($model) {
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'child-bookings-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	public function actionCreateChildHoliday() {
		if (isset($_POST['ChildHolidays']) && $_POST['isAjaxRequest'] == 1) {
			$response = array('status' => 1, 'message' => 'Holiday has been succsessfully created.');
			$model = new ChildHolidays;
			$model->attributes = $_POST['ChildHolidays'];
			$model->date = date('Y-m-d', strtotime($model->date));
			$model->branch_id = Yii::app()->session['branch_id'];
			if ($model->validate() && $model->save()) {
				echo CJSON::encode($response);
			} else {
				echo CJSON::encode($model->getErrors());
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid');
		}
	}

	public function actionUpdateChildHoliday($id) {
		if (isset($_POST['ChildHolidays']) && $_POST['isAjaxRequest'] == 1) {
			$response = array('status' => 1, 'message' => 'Holiday has been succsessfully updated.');
			$model = ChildHolidays::model()->findByPk($id);
			$model->attributes = $_POST['ChildHolidays'];
			$model->date = date('Y-m-d', strtotime($model->date));
			$model->branch_id = Yii::app()->session['branch_id'];
			if ($model->validate() && $model->save()) {
				echo CJSON::encode($response);
			} else {
				echo CJSON::encode($model->getErrors());
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid');
		}
	}

	public function actionDeleteChildHoliday($id) {
		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1)) {
			$response = array('status' => 1, 'message' => 'Holiday has been succsessfully deleted.');
			$model = ChildHolidays::model()->findByPk($id);
			$model->is_deleted = 1;
			if ($model->validate() && $model->save()) {
				echo CJSON::encode($response);
			} else {
				$response['status'] = 0;
				$response['message'] = "There seems to a problem deleting the event";
				echo CJSON::encode($response);
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid');
		}
	}

	public function actionUpdateAllChildScheduling() {
		if (isset($_POST) && ($_POST['isAjaxRequest'] == 1)) {
			$response = array('status' => 1, 'message' => 'Session was successfully updated');
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$bookingModel = ChildBookings::model()->findByPk($_POST['booking_id']);
				$bookingDetailsModel = ChildBookingsDetails::model()->findByAttributes(array('booking_id' => $bookingModel->id));
				$bookingModel->start_date = preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$3-$1-$2", $_POST['start_date']);
				$bookingModel->finish_date = preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$3-$1-$2", $_POST['end_date']);
				$bookingModel->room_id = $_POST['room_id'];
				$bookingModel->start_time = $_POST['start_time'];
				$bookingModel->finish_time = $_POST['end_time'];
				$bookingModel->booking_type = ChildPersonalDetails::model()->findByPk($_POST['child_id'])->booking_type;
				$bookingModel->session_type_id = $_POST['session_id'];
				$bookingModel->repeat_type_id = $_POST['repeat_type_id'];
				$bookingModel->exclude_funding = $_POST['exclude_from_funding'];
				if ($bookingModel->validate() && $bookingModel->save() && $bookingModel->customValidation($bookingModel->child_id, $bookingModel->session_type_id, $bookingModel)) {
					$bookingDetailsModel->booking_days = $_POST['repeatDayNumber'];
					$bookingDetailsModel->booking_products = isset($_POST['products']) ? CJSON::encode($_POST['products']) : NULL;
					$bookingDetailsModel->booking_color = SessionRates::model()->findByPk($_POST['session_id'])->color;
					if ($bookingDetailsModel->validate() && $bookingDetailsModel->save()) {
						$transaction->commit();
						echo CJSON::encode(array('status' => 1, 'message' => 'Child booking has been successfully updated'));
					} else {
						echo CJSON::encode($bookingDetailsModel->getErrors());
					}
				} else {
					echo CJSON::encode($bookingModel->getErrors());
				}
			} catch (Exception $ex) {
				$transaction->rollback();
			}
		} else {
			throw new CHttpException(404, 'You request is not valid.');
		}
	}

	public function actionUpdateFollowingChildScheduling() {
		if (isset($_POST) && ($_POST['isAjaxRequest'] == 1)) {
			$this_date = DateTime::createFromFormat('m-d-Y', preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$2-$3-$1", $_POST['clicked_event_date']));
			$this_date = $this_date->format('Y-m-d');
			$bookingModel = ChildBookings::model()->findByPk($_POST['booking_id']);
			if ($bookingModel->start_date == $bookingModel->finish_date || $this_date == $bookingModel->start_date) {
				$transaction = Yii::app()->db->beginTransaction();
				try {
					$bookingDetailsModel = ChildBookingsDetails::model()->findByAttributes(array('booking_id' => $bookingModel->id));
					$bookingModel->start_date = preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$3-$1-$2", $_POST['start_date']);
					$bookingModel->finish_date = preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$3-$1-$2", $_POST['end_date']);
					$bookingModel->room_id = $_POST['room_id'];
					$bookingModel->start_time = $_POST['start_time'];
					$bookingModel->finish_time = $_POST['end_time'];
					$bookingModel->booking_type = ChildPersonalDetails::model()->findByPk($_POST['child_id'])->booking_type;
					$bookingModel->session_type_id = $_POST['session_id'];
					$bookingModel->repeat_type_id = $_POST['repeat_type_id'];
					$bookingModel->exclude_funding = $_POST['exclude_from_funding'];
					if ($bookingModel->validate() && $bookingModel->save() && $bookingModel->customValidation($bookingModel->child_id, $bookingModel->session_type_id, $bookingModel)) {
						$bookingDetailsModel->booking_days = $_POST['repeatDayNumber'];
						$bookingDetailsModel->booking_products = isset($_POST['products']) ? CJSON::encode($_POST['products']) : NULL;
						$bookingDetailsModel->booking_color = SessionRates::model()->findByPk($_POST['session_id'])->color;
						if ($bookingDetailsModel->validate() && $bookingDetailsModel->save()) {
							$transaction->commit();
							echo CJSON::encode(array('status' => 1, 'message' => 'Child booking was successfully updated.'));
						} else {
							echo CJSON::encode($bookingDetailsModel->getErrors());
						}
					} else {
						echo CJSON::encode($bookingModel->getErrors());
					}
				} catch (Exception $ex) {
					$transaction->rollback();
				}
			} else {
				$transaction = Yii::app()->db->beginTransaction();
				try {
					$bookingModel = ChildBookings::model()->findByPk($_POST['booking_id']);
					$newBookingStartDate = DateTime::createFromFormat('m-d-Y', preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$2-$3-$1", $_POST['clicked_event_date']));
					$old_booking_finish_date = DateTime::createFromFormat('m-d-Y', preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$2-$3-$1", $_POST['clicked_event_date']));
					$old_booking_finish_date->modify('-1 day');
					$bookingModel->finish_date = $old_booking_finish_date->format('Y-m-d');
					if ($bookingModel->save()) {
						$newBookingModel = new ChildBookings;
						$newBookingModel->child_id = $_POST['child_id'];
						$newBookingModel->start_date = $newBookingStartDate->format('Y-m-d');
						$newBookingModel->finish_date = preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$3-$1-$2", $_POST['end_date']);
						$newBookingModel->room_id = $_POST['room_id'];
						$newBookingModel->start_time = $_POST['start_time'];
						$newBookingModel->finish_time = $_POST['end_time'];
						$newBookingModel->branch_id = ChildPersonalDetails::model()->findByPk($_POST['child_id'])->branch_id;
						$newBookingModel->booking_type = ChildPersonalDetails::model()->findByPk($_POST['child_id'])->booking_type;
						$newBookingModel->session_type_id = $_POST['session_id'];
						$newBookingModel->repeat_type_id = $_POST['repeat_type_id'];
						$newBookingModel->exclude_funding = $_POST['exclude_from_funding'];
						$newBookingModel->invoice_id = $bookingModel->invoice_id;
						$newBookingModel->is_invoiced = $bookingModel->is_invoiced;
						if ($newBookingModel->validate() && $newBookingModel->save() && $newBookingModel->customValidation($newBookingModel->child_id, $newBookingModel->session_type_id, $newBookingModel)) {
							$newBookingDetailsModel = new ChildBookingsDetails;
							$newBookingDetailsModel->booking_days = $_POST['repeatDayNumber'];
							$newBookingDetailsModel->booking_id = $newBookingModel->id;
							$newBookingDetailsModel->booking_products = isset($_POST['products']) ? CJSON::encode($_POST['products']) : NULL;
							$newBookingDetailsModel->booking_color = SessionRates::model()->findByPk($_POST['session_id'])->color;
							if ($newBookingDetailsModel->validate() && $newBookingDetailsModel->save()) {
								$transaction->commit();
								echo CJSON::encode(array('status' => 1, 'message' => 'Child booking was successfully updated.'));
							} else {
								echo CJSON::encode($newBookingDetailsModel->getErrors());
							}
						} else {
							echo CJSON::encode($newBookingModel->getErrors());
						}
					} else {
						echo CJSON::encode($bookingModel->getErrors());
					}
				} catch (Exception $ex) {
					$transaction->rollback();
				}
			}
		} else {
			throw new CHttpException(404, 'You request is not valid.');
		}
	}

	public function actionUpdateThisChildScheduling() {
		if (isset($_POST) && ($_POST['isAjaxRequest'] == 1)) {
			$bookingModel = $this->loadModel($_POST['booking_id']);
			if ($bookingModel->start_date == $bookingModel->finish_date) {
				$this_date = (DateTime::createFromFormat('m-d-Y', preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$2-$3-$1", $_POST['clicked_event_date'])));
				$this_date = $this_date->format('Y-m-d');
				$response = array('status' => 1, 'message' => 'Child Booking was successfully updated');
				$transaction = Yii::app()->db->beginTransaction();
				try {
					$bookingDetailsModel = ChildBookingsDetails::model()->findByAttributes(array('booking_id' => $bookingModel->id));
					$bookingModel->room_id = $_POST['room_id'];
					$bookingModel->start_time = $_POST['start_time'];
					$bookingModel->finish_time = $_POST['end_time'];
					$bookingModel->session_type_id = $_POST['session_id'];
					$bookingModel->repeat_type_id = 0;
					$bookingModel->exclude_funding = $_POST['exclude_from_funding'];
					if ($bookingModel->validate() && $bookingModel->save() && $bookingModel->customValidation($bookingModel->child_id, $bookingModel->session_type_id, $bookingModel)) {
						$bookingDetailsModel->booking_id = $bookingModel->id;
						$bookingDetailsModel->booking_days = date('N', strtotime($this_date));
						$bookingDetailsModel->booking_products = isset($_POST['products']) ? CJSON::encode($_POST['products']) : NULL;
						$bookingDetailsModel->booking_color = SessionRates::model()->findByPk($_POST['session_id'])->color;
						if ($bookingDetailsModel->validate() && $bookingDetailsModel->save()) {
							$transaction->commit();
							echo CJSON::encode($response);
						} else {
							echo CJSON::encode($bookingModel->getErrors());
						}
					} else {
						echo CJSON::encode($bookingModel->getErrors());
					}
				} catch (Exception $ex) {
					$transaction->rollback();
				}
			} else {
				$this_date = (DateTime::createFromFormat('m-d-Y', preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$2-$3-$1", $_POST['clicked_event_date'])));
				$this_date = $this_date->format('Y-m-d');
				if ($this_date == $bookingModel->start_date) {
					$response = array('status' => 1, 'message' => 'Child booking was successfully updated');
					$transaction = Yii::app()->db->beginTransaction();
					try {
						$oldBookingDetailsModel = ChildBookingsDetails::model()->findByAttributes(array('booking_id' => $bookingModel->id));
						$nextBookingModel = new ChildBookings;
						$nextBookingModel->attributes = $bookingModel->attributes;
						$clicked_event_date = (DateTime::createFromFormat('m-d-Y', preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$2-$3-$1", $_POST['clicked_event_date'])));
						$clicked_event_date->modify('+1 day');
						$nextBookingModel->start_date = $clicked_event_date->format('Y-m-d');
						if ($nextBookingModel->save()) {
							$nextBookingDetailsModel = new ChildBookingsDetails;
							$nextBookingDetailsModel->attributes = $oldBookingDetailsModel->attributes;
							$nextBookingDetailsModel->booking_id = $nextBookingModel->id;
							$nextBookingDetailsModel->save();
						}
						$bookingModel->child_id = $_POST['child_id'];
						$bookingModel->start_date = $this_date;
						$bookingModel->finish_date = $this_date;
						$bookingModel->room_id = $_POST['room_id'];
						$bookingModel->start_time = $_POST['start_time'];
						$bookingModel->finish_time = $_POST['end_time'];
						$bookingModel->exclude_funding = $_POST['exclude_from_funding'];
						$bookingModel->branch_id = ChildPersonalDetails::model()->findByPk($_POST['child_id'])->branch_id;
						$bookingModel->booking_type = ChildPersonalDetails::model()->findByPk($_POST['child_id'])->booking_type;
						$bookingModel->session_type_id = $_POST['session_id'];
						$bookingModel->repeat_type_id = 0;
						if ($bookingModel->validate() && $bookingModel->save() && $bookingModel->customValidation($bookingModel->child_id, $bookingModel->session_type_id, $bookingModel)) {
							$bookingDetailsModel = ChildBookingsDetails::model()->findByAttributes(array('booking_id' => $bookingModel->id));
							$bookingDetailsModel->booking_id = $bookingModel->id;
							$bookingDetailsModel->booking_days = date('N', strtotime($this_date));
							$bookingDetailsModel->booking_products = isset($_POST['products']) ? CJSON::encode(customFunctions::truncateProducts($bookingDetailsModel->booking_days, $_POST['products'])) : NULL;
							$bookingDetailsModel->booking_color = SessionRates::model()->findByPk($_POST['session_id'])->color;
							if ($bookingDetailsModel->validate() && $bookingDetailsModel->save()) {
								$transaction->commit();
								echo CJSON::encode($response);
							} else {
								echo CJSON::encode($bookingDetailsModel->getErrors());
							}
						} else {
							echo CJSON::encode($bookingModel->getErrors());
						}
					} catch (Exception $ex) {
						$transaction->rollback();
					}
				} else if ($this_date == $bookingModel->finish_date) {
					$response = array('status' => 1, 'message' => 'Child booking was successfully updated');
					$transaction = Yii::app()->db->beginTransaction();
					try {
						$oldBookingDetailsModel = ChildBookingsDetails::model()->findByAttributes(array('booking_id' => $bookingModel->id));
						$nextBookingModel = new ChildBookings;
						$nextBookingModel->attributes = $bookingModel->attributes;
						$clicked_event_date = (DateTime::createFromFormat('m-d-Y', preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$2-$3-$1", $_POST['clicked_event_date'])));
						$clicked_event_date = $clicked_event_date->modify('-1 day');
						$nextBookingModel->finish_date = $clicked_event_date->format('Y-m-d');
						if ($nextBookingModel->save()) {
							$nextBookingDetailsModel = new ChildBookingsDetails;
							$nextBookingDetailsModel->attributes = $oldBookingDetailsModel->attributes;
							$nextBookingDetailsModel->booking_id = $nextBookingModel->id;
							$nextBookingDetailsModel->save();
						}
						$bookingModel->child_id = $_POST['child_id'];
						$bookingModel->start_date = $this_date;
						$bookingModel->finish_date = $this_date;
						$bookingModel->room_id = $_POST['room_id'];
						$bookingModel->start_time = $_POST['start_time'];
						$bookingModel->finish_time = $_POST['end_time'];
						$bookingModel->exclude_funding = $_POST['exclude_from_funding'];
						$bookingModel->branch_id = ChildPersonalDetails::model()->findByPk($_POST['child_id'])->branch_id;
						$bookingModel->booking_type = ChildPersonalDetails::model()->findByPk($_POST['child_id'])->booking_type;
						$bookingModel->session_type_id = $_POST['session_id'];
						$bookingModel->repeat_type_id = 0;
						if ($bookingModel->validate() && $bookingModel->save() && $bookingModel->customValidation($bookingModel->child_id, $bookingModel->session_type_id, $bookingModel)) {
							$bookingDetailsModel = ChildBookingsDetails::model()->findByAttributes(array('booking_id' => $bookingModel->id));
							$bookingDetailsModel->booking_id = $bookingModel->id;
							$bookingDetailsModel->booking_days = date('N', strtotime($this_date));
							$bookingDetailsModel->booking_products = isset($_POST['products']) ? CJSON::encode(customFunctions::truncateProducts($bookingDetailsModel->booking_days, $_POST['products'])) : NULL;
							$bookingDetailsModel->booking_color = SessionRates::model()->findByPk($_POST['session_id'])->color;
							if ($bookingDetailsModel->validate() && $bookingDetailsModel->save()) {
								$transaction->commit();
								echo CJSON::encode($response);
							} else {
								echo CJSON::encode($bookingModel->getErrors());
							}
						} else {
							echo CJSON::encode($bookingModel->getErrors());
						}
					} catch (Exception $ex) {
						$transaction->rollback();
					}
				} else {
					$response = array('status' => 1, 'message' => 'Child booking was successfully updated');
					$transaction = Yii::app()->db->beginTransaction();
					try {
						$bookingDetailsModel = ChildBookingsDetails::model()->findByAttributes(array('booking_id' => $bookingModel->id));
						$nextBookingModel = new ChildBookings;
						$nextBookingModel->attributes = $bookingModel->attributes;
						$nextBookingDetailsModel = new ChildBookingsDetails;
						$nextBookingDetailsModel->attributes = $bookingDetailsModel->attributes;
						$clicked_event_date = (DateTime::createFromFormat('m-d-Y', preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$2-$3-$1", $_POST['clicked_event_date'])));
						$clicked_event_date = $clicked_event_date->modify('-1 day');
						$bookingModel->finish_date = $clicked_event_date->format('Y-m-d');
						if ($bookingModel->save()) {
							if ($bookingDetailsModel->save()) {
								$finish_date = (DateTime::createFromFormat('m-d-Y', preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$2-$3-$1", $_POST['clicked_event_date'])));
								$finish_date = $finish_date->modify('+1 day');
								$nextBookingModel->start_date = $finish_date->format('Y-m-d');
								if ($nextBookingModel->save()) {
									$nextBookingDetailsModel->booking_id = $nextBookingModel->id;
									if ($nextBookingDetailsModel->save()) {
										$model = new ChildBookings;
										$model->child_id = $_POST['child_id'];
										$model->start_date = $this_date;
										$model->finish_date = $this_date;
										$model->room_id = $_POST['room_id'];
										$model->start_time = $_POST['start_time'];
										$model->finish_time = $_POST['end_time'];
										$model->exclude_funding = $_POST['exclude_from_funding'];
										$model->branch_id = ChildPersonalDetails::model()->findByPk($_POST['child_id'])->branch_id;
										$model->booking_type = ChildPersonalDetails::model()->findByPk($_POST['child_id'])->booking_type;
										$model->session_type_id = $_POST['session_id'];
										$model->repeat_type_id = 0;
										$model->invoice_id = $bookingModel->invoice_id;
										$model->is_invoiced = $bookingModel->is_invoiced;
										if ($model->validate() && $model->save() && $model->customValidation($model->child_id, $model->session_type_id, $model)) {
											$bookingDetailsModel = new ChildBookingsDetails;
											$bookingDetailsModel->booking_id = $model->id;
											$bookingDetailsModel->booking_days = date('N', strtotime($this_date));
											$bookingDetailsModel->booking_products = isset($_POST['products']) ? CJSON::encode(customFunctions::truncateProducts($bookingDetailsModel->booking_days, $_POST['products'])) : NULL;
											$bookingDetailsModel->booking_color = SessionRates::model()->findByPk($_POST['session_id'])->color;
											if ($bookingDetailsModel->validate() && $bookingDetailsModel->save()) {
												$transaction->commit();
												echo CJSON::encode($response);
											} else {
												echo CJSON::encode($bookingDetailsModel->getErrors());
											}
										} else {
											echo CJSON::encode($model->getErrors());
										}
									} else {
										$transaction->rollback();
										echo CJSON::encode($nextBookingDetailsModel->getErrors());
									}
								} else {
									$transaction->rollback();
									echo CJSON::encode($nextBookingModel->getErrors());
								}
							} else {
								$transaction->rollback();
								echo CJSON::encode($bookingDetailsModel->getErros());
							}
						} else {
							echo CJSON::encode($bookingModel->getErrors());
						}
					} catch (Exception $ex) {
						$transaction->rollback();
					}
				}
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionDeleteAllChildScheduling() {
		if (isset($_POST) && ($_POST['isAjaxRequest'] == 1)) {
			$bookingModel = ChildBookings::model()->findByPk($_POST['booking_id']);
			$response = array('status' => 1, 'message' => 'Child booking was successfully deleted');
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$bookingModel->is_deleted = 1;
				if ($bookingModel->save()) {
					$transaction->commit();
					echo CJSON::encode($response);
				} else {
					echo CJSON::encode($bookingModel->getErrors());
				}
			} catch (Exception $ex) {
				$transaction->rollback();
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	public function actionDeleteFollowingChildScheduling() {
		if (isset($_POST) && ($_POST['isAjaxRequest'] == 1)) {
			$bookingModel = ChildBookings::model()->findByPk($_POST['booking_id']);
			$this_date = DateTime::createFromFormat('m-d-Y', preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$2-$3-$1", $_POST['clicked_event_date']));
			$this_date = $this_date->format('Y-m-d');
			if ($bookingModel->start_date == $bookingModel->finish_date || $bookingModel->start_date == $this_date) {
				$response = array('status' => 1, 'message' => 'Child booking was successfully deleted');
				$transaction = Yii::app()->db->beginTransaction();
				try {
					$bookingModel->is_deleted = 1;
					if ($bookingModel->save()) {
						$transaction->commit();
						echo CJSON::encode($response);
					} else {
						$transaction->rollback();
						echo CJSON::encode($bookingModel->getErrors());
					}
				} catch (Exception $ex) {
					$transaction->rollback();
				}
			} else {
				$this_date = DateTime::createFromFormat('m-d-Y', preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$2-$3-$1", $_POST['clicked_event_date']));
				$this_date->modify('-1 day');
				$response = array('status' => 1, 'message' => 'Child booking was successfully deleted');
				$transaction = Yii::app()->db->beginTransaction();
				try {
					$bookingModel->finish_date = $this_date->format('Y-m-d');
					if ($bookingModel->save()) {
						$transaction->commit();
						echo CJSON::encode($response);
					} else {
						$transaction->rollback();
						echo CJSON::encode($bookingModel->getErrors());
					}
				} catch (Exception $ex) {
					$transaction->rollback();
				}
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	public function actionDeleteThisChildScheduling() {
		if (isset($_POST) && ($_POST['isAjaxRequest'] == 1)) {
			$bookingModel = ChildBookings::model()->findByPk($_POST['booking_id']);
			if ($bookingModel->start_date == $bookingModel->finish_date) {
				$response = array('status' => 1, 'message' => 'Child booking was successfully deleted');
				$transaction = Yii::app()->db->beginTransaction();
				try {
					$bookingModel->is_deleted = 1;
					if ($bookingModel->save()) {
						$transaction->commit();
						echo CJSON::encode($response);
					} else {
						$transaction->rollback();
						echo CJSON::encode($bookingModel->getErrors());
					}
				} catch (Exception $ex) {
					$transaction->rollback();
				}
			} else {
				$this_date = (DateTime::createFromFormat('m-d-Y', preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$2-$3-$1", $_POST['clicked_event_date'])));
				$this_date = $this_date->format('Y-m-d');
				if ($this_date == $bookingModel->start_date) {
					$this_date = new DateTime($this_date);
					$this_date = $this_date->modify('+1 day');
					$response = array('status' => 1, 'message' => 'Child booking was successfully deleted');
					$transaction = Yii::app()->db->beginTransaction();
					try {
						$bookingModel->start_date = $this_date->format('Y-m-d');
						if ($bookingModel->save()) {
							$transaction->commit();
							echo CJSON::encode($response);
						} else {
							$transaction->rollback();
							echo CJSON::encode($bookingModel->getErrors());
						}
					} catch (Exception $ex) {
						$transaction->rollback();
					}
				} else if ($this_date == $bookingModel->finish_date) {
					$this_date = new DateTime($this_date);
					$this_date = $this_date->modify('-1 day');
					$response = array('status' => 1, 'message' => 'Child booking was successfully deleted');
					$transaction = Yii::app()->db->beginTransaction();
					try {
						$bookingModel->finish_date = $this_date->format('Y-m-d');
						if ($bookingModel->save()) {
							$transaction->commit();
							echo CJSON::encode($response);
						} else {
							$transaction->rollback();
							echo CJSON::encode($bookingModel->getErrors());
						}
					} catch (Exception $ex) {
						$transaction->rollback();
					}
				} else {
					$this_date = DateTime::createFromFormat('m-d-Y', preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$2-$3-$1", $_POST['clicked_event_date']));
					$bookingDetailsModel = ChildBookingsDetails::model()->findByAttributes(array('booking_id' => $bookingModel->id));
					$transaction = Yii::app()->db->beginTransaction();
					$response = array('status' => 1, 'message' => 'Child booking was successfully deleted');
					$yesterday = DateTime::createFromFormat('m-d-Y', preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$2-$3-$1", $_POST['clicked_event_date']));
					$yesterday = $yesterday->modify('-1 day');
					$yesterday = $yesterday->format('Y-m-d');
					$tommorow = DateTime::createFromFormat('m-d-Y', preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$2-$3-$1", $_POST['clicked_event_date']));
					$tommorow = $tommorow->modify('+1 day');
					$tommorow = $tommorow->format('Y-m-d');
					$newBookingModel = new ChildBookings;
					$newBookingDetailsModel = new ChildBookingsDetails;
					$newBookingModel->attributes = $bookingModel->attributes;
					$newBookingDetailsModel->attributes = $bookingDetailsModel->attributes;
					$bookingModel->finish_date = $yesterday;
					try {
						if ($bookingModel->save()) {
							$newBookingModel->start_date = $tommorow;
							if ($newBookingModel->save()) {
								$newBookingDetailsModel->booking_id = $newBookingModel->id;
								if ($newBookingDetailsModel->save()) {
									$transaction->commit();
									echo CJSON::encode($response);
								}
							} else {
								$transaction->rollback();
								echo CJSON::encode($newBookingModel->getErrors());
							}
						} else {
							$transaction->rollback();
							echo CJSON::encode($bookingModel->getErrors());
						}
					} catch (Exception $ex) {
						$transaction->rollback();
					}
				}
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	public function actionUninvoicedSessions() {
		if (Yii::app()->request->isAjaxRequest) {
			$invoiceSettingModal = InvoiceSetting::model()->findByAttributes(array('branch_id' => Branch::currentBranch()->id));
			$child_id = Yii::app()->request->getPost('child_id');
			if (!empty($invoiceSettingModal)) {
				if (Yii::app()->request->getPost('year') == NULL) {
					echo CJSON::encode([
						'status' => false,
						'data' => [
							'year' => 'Please Select Year.'
						]
					]);
					Yii::app()->end();
				}
				if (Yii::app()->request->getPost('month') == NULL) {
					echo CJSON::encode([
						'status' => false,
						'data' => [
							'year' => 'Please Select Month.'
						]
					]);
					Yii::app()->end();
				}
				if (Yii::app()->request->getPost('month') == 'all') {
					$invoiceFromDate = ChildInvoice::model()->setInvoiceDates($invoiceSettingModal, 01, Yii::app()->request->getPost('year'));
					$invoiceToDate = ChildInvoice::model()->setInvoiceDates($invoiceSettingModal, 12, Yii::app()->request->getPost('year'));
					$invoiceDates = array(
						'invoice_from_date' => $invoiceFromDate['invoice_from_date'],
						'invoice_to_date' => $invoiceToDate['invoice_to_date']
					);
				} else {
					$invoiceDates = ChildInvoice::model()->setInvoiceDates($invoiceSettingModal, Yii::app()->request->getPost('month'), Yii::app()->request->getPost('year'));
				}
				$model = ChildBookings::model()->getBookings($invoiceDates['invoice_from_date'], $invoiceDates['invoice_to_date'], Branch::currentBranch()->id, $child_id, NULL, NULL, NULL, 0, 1);
				$response = array();
				if (!empty($model)) {
					$data = array();
					$allDates = customFunctions::getDatesOfDays($invoiceDates['invoice_from_date'], $invoiceDates['invoice_to_date'], explode(",", Branch::currentBranch()->nursery_operation_days));
					foreach ($model as $booking) {
						$bookingDays = customFunctions::getDatesOfDays($booking->start_date, $booking->finish_date, explode(",", $booking->childBookingsDetails->booking_days));
						$bookingDays = array_intersect($allDates, $bookingDays);
						$childHolidays = array();
						$childHolidayModal = ChildHolidays::model()->findAll([
							'condition' => 'date BETWEEN :start_date and :finish_date and child_id = :child_id',
							'params' => [
								':start_date' => $invoiceDates['invoice_from_date'],
								':finish_date' => $invoiceDates['invoice_to_date'],
								':child_id' => $booking->child_id
							]
						]);
						$childHolidayDays = array();
						if (!empty($childHolidayModal)) {
							foreach ($childHolidayModal as $childHoliday) {
								array_push($childHolidayDays, $childHoliday->date);
							}
						}
						if (!empty($childHolidayDays)) {
							$bookingDays = array_diff($bookingDays, $childHolidayDays);
						}
						if (!empty($bookingDays)) {
							$bookingDays = array_values($bookingDays);
							$rate = 0;
							$hours = customFunctions::getHours($booking->start_time, $booking->finish_time);
							$weekDates = customFunctions::getWeekDates(date('W', strtotime($booking->start_date)), date('Y', strtotime($booking->start_date)));
							$actualWeekStartDate = $weekDates['week_start_date'];
							$actualWeekEndDate = $weekDates['week_end_date'];
							if ($booking->branch->change_session_rate == 0) {
								$age = customFunctions::getAge(date("Y-m-d", strtotime($booking->child->dob)), date("Y-m-d", strtotime("-1 day", strtotime($actualWeekStartDate))));
							} else {
								$age = customFunctions::getAge(date("Y-m-d", strtotime($booking->child->dob)), date("Y-m-d", strtotime("-1 day", strtotime($invoiceDates['invoice_from_date']))));
							}
							if ($booking->sessionType->is_multiple_rates == 1 && $booking->sessionType->multiple_rates_type == 1) {
								$calculated_rate = $this->actionGetMultipleRate($age, $booking->session_type_id, $hours, 0, $actualWeekStartDate, $actualWeekEndDate);
								if ($calculated_rate == FALSE) {
									$rate = 0;
								} else {
									$rate = $this->actionGetMultipleRate($age, $booking->session_type_id, $hours, 0, $actualWeekStartDate, $actualWeekEndDate);
								}
							} else if ($booking->sessionType->is_multiple_rates == 1 && $booking->sessionType->multiple_rates_type == 2) {
								$calculated_rate = $this->actionGetMultipleRatesWeekdays($age, $booking->session_type_id, 1, $actualWeekStartDate, $actualWeekEndDate);
								if ($calculated_rate == FALSE) {
									$rate = 0;
								} else {
									$rate = $calculated_rate;
								}
							} else if ($booking->sessionType->is_multiple_rates == 1 && $booking->sessionType->multiple_rates_type == 3) {
								$calculated_rate = $this->actionGetMultipleRatesTotalWeekdays($age, $booking->session_type_id, 1, $actualWeekStartDate, $actualWeekEndDate);
								if ($calculated_rate == FALSE) {
									$rate = 0;
								} else {
									$rate = $calculated_rate;
								}
							} else {
								$rate = customFunctions::getRateForManualInvoicing($booking->child_id, $booking->session_type_id, $hours, $actualWeekStartDate, $actualWeekEndDate);
							}
							$bookingProducts = array();
							$bookingProducts_name = array();
							if(!empty($booking->childBookingsDetails->booking_products) && $booking->childBookingsDetails->booking_products != NULL){
								$products = CJSON::decode($booking->childBookingsDetails->booking_products);
								foreach($products as $productId => $productDays){
									if(in_array(date("w", strtotime($booking->start_date)), $productDays)){
										Products::$as_of = date("Y-m-d", strtotime($_POST['invoice_date']));
										$productModel = Products::model()->findByPk($productId);
										$bookingProducts [] = $productModel;
										$bookingProducts_name[] = $productModel->name;
									}
								}
							}
							$data[] = [
								'booking_data' => $booking,
								'booking_days' => $bookingDays,
								'session_name' => $booking->sessionType->name,
								'rate' => $rate,
								'discount' => $booking->child->discount,
								'products_data' => $bookingProducts,
								'products_name' => implode(", ", $bookingProducts_name)
							];
						}
					}
					echo CJSON::encode([
						'status' => true,
						'data' => $data
					]);
				} else {
					echo CJSON::encode([
						'status' => true,
						'data' => []
					]);
				}
			} else {
				echo CJSON::encode([
					'status' => true,
					'data' => []
				]);
				Yii::app()->end();
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
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
        
        /**
	 * Returns Number of days and Day Name for startdate and enddate
	 * @param $startdate startdate
         * @param $enddate enddate
         * @param $numday numeric value for week day 
	 */
        
        public function getNumberOfDays($startdate = NULL, $enddate = NULL, $numday = NULL) {
            $startdate = strtotime($startdate);
            $enddate = strtotime($enddate);
            if(($startdate <= $enddate) && ($day<=7)) {
                $days_detail = [];
                $number_of_days = 0;
                $days = array(
                    0 => 'Monday',
                    1 => 'Tuesday',
                    2 => 'Wednesday',
                    3 => 'Thursday',
                    4 => 'Friday',
                    5 => 'Saturday',
                    6 => 'Sunday'
                );
                for($i = strtotime($days[$numday], $startdate); $i <= $enddate; $i = strtotime('+1 week', $i)){
                  $number_of_days++;
                }
                    $days_detail['number_of_days'] = $number_of_days;
                    $days_detail['day'] = $days[$numday];
                    return $days_detail;
            }
            else {
                return false;
            }
        }
        
        /**
	 * All the total products amount for the particular Booking Id
	 * @param $booking_id booking_id child_booking table 
	 */
        
        public function getProductBookingAmount($booking_id = NULL) {
            $childbooking = ChildBookings::model()->with('childBookingsDetails')->findByAttributes(array('id' => $booking_id));
            if(!$childbooking) {
                return false;
            }
            $booking_products = json_decode($childbooking['childBookingsDetails']['booking_products']);
            if(!$booking_products) {
                return false;
            }
            $product_usage_details = [];
            $i = 0;
            foreach($booking_products as $product_id => $numdays) {
               $number_of_days = 0;
               $product_detail = Products::model()->findByAttributes(array('id' => $product_id));
               $days = [];
               foreach($numdays as $numday) {
                   $days_detail  =  $this->getNumberOfDays($childbooking['start_date'], $childbooking['finish_date'], $numday);
                   $number_of_days += $days_detail['number_of_days'];
                   array_push($days, $days_detail['day']);
                   
               }
               $product_usage_details[$i]['name']                          = $product_detail['name'];
               $product_usage_details[$i]['description']                   = $product_detail['description'];
               $product_usage_details[$i]['number_of_days_availed']        = $number_of_days;
               $product_usage_details[$i]['cost']                          = (int)$number_of_days * (double)customFunctions::getProductPrice($product_id);
               $product_usage_details[$i]['days_availing']                 = $days;
               $product_usage_details[$i]['start_date']                    = date('y-M-d',  strtotime($childbooking['start_date']));
               $product_usage_details[$i]['end_date']                      = date('y-M-d',  strtotime($childbooking['finish_date']));
               $product_usage_details[$i]['start_time']                    = $childbooking['start_time'];
               $product_usage_details[$i]['finish_time']                   = $childbooking['finish_time'];
               $product_usage_details[$i]['is_invoiced']                   = $childbooking['is_invoiced'];
               $product_usage_details[$i]['invoice_id']                     = $childbooking['invoice_id'];
               $product_usage_details[$i]['product_id']                     = $product_id;
               $i++;
            }
            return $product_usage_details;
        }
        
        /**
	 * Returns Bookings with product details for the given month
	 * @param $month month in numeric
         * @param $year year in numeric
         * @param $child_id child_id 
	 */
        
        public function getBookingsProducts($month = NULL,$year = NULL,$child_id = NULL) {
            
            $startdate  = date('Y-m-d', strtotime($year.'-'.$month.'-01'));
            $enddate  =  date('Y-m-t', strtotime($startdate));
            $child_bookings = ChildBookings::model()->findAll([
                                'select' => 'id',
                                'condition' => 'start_date BETWEEN :start_date AND :enddate AND finish_date BETWEEN :start_date AND :enddate AND child_id = :child_id',
                                'params' => [
                                        ':start_date' => $startdate,
                                        ':enddate' => $enddate,
                                        ':child_id' => $child_id
                                ]
                        ]);
            $booking_product_details = [];
            foreach($child_bookings as $child_booking) {
                $product_usage_details = $this->getProductBookingAmount($child_booking['id']);
                if($product_usage_details) {
                    $booking_product_details = array_merge($booking_product_details, $product_usage_details);
                }
            }
            return $booking_product_details;
        }
        
         /**
	 * Returns Refined data for Products used
	 * @param $month month in numeric
	 */
        
        public function actionBookingsProductsDetails() {
           $booking_products =  $this->getBookingsProducts(06, 2017, 59);//pass month, year, child_id
           $product_ids = [];
           foreach($booking_products as $booking_product) {
               $product_ids[] = $booking_product['product_id'];
           }
           $product_ids = array_unique($product_ids);
           $unique_products = [];
           $unique_products_details = [];
           foreach ($booking_products as  $key => $bval) {
               foreach($product_ids as $product_id) {
                   if($bval['product_id'] == $product_id) {
                       $unique_products[$bval['name']][$key] = $bval;
                   }
               }
           }
           
          echo '<pre>'; print_r($unique_products); die;
        }

        /*public function actionBookingsProductsDetails() {
           $booking_products =  $this->getBookingsProducts(06, 2017, 59);
            $unique_products = [];
            //echo '<pre>'; print_r($booking_products); die;
               foreach($booking_products as $bkye => $bval) {
                   if(in_array($bval['product_id'], $unique_products)){
                       $unique_products['product_id']['number_of_days_availed'] += $bval['number_of_days_availed'];
                       $unique_products['product_id']['cost'] += $bval['cost'];
                       $unique_products['product_id']['days_availing'] += $bval['days_availing'];
                   }else{
                       $temp                                 = array();
                       $temp['name']                         = $bval['name'];
                       $temp['cost']                         = $bval['cost'];
                       $temp['description']                  = $bval['description'];
                       $temp['number_of_days_availed']       = $bval['number_of_days_availed'];
                       $temp['days_availing']                = $bval['days_availing'];
                       $unique_products[$bval['product_id']] = $temp;
                   }
                   
                   
               }
           echo '<pre>'; print_r($unique_products);die("mdead");
        }*/
        
        /*public function actionGetMonthlyBookings() {
            $year = 2017;
            $month = 04;
            $child_id = 59;
            $startdate  = date('Y-m-d', strtotime($year.'-'.$month.'-01'));
            $enddate  =  date('Y-m-t', strtotime($startdate));
            $child_bookings = ChildBookings::model()->with('childBookingsDetails')->findAll([
                                'select' => 'id,start_date,finish_date',
                                'condition' => 'start_date BETWEEN :start_date AND :enddate AND finish_date BETWEEN :start_date AND :enddate AND child_id = :child_id',
                                'params' => [
                                        ':start_date' => $startdate,
                                        ':enddate' => $enddate,
                                        ':child_id' => $child_id
                                ]
                        ]);
            $all_booking_products = [];
            $i = 0;
            foreach($child_bookings as $key => $child_booking) {
                
                if($child_booking['childBookingsDetails']['booking_products']) {
                    $all_booking_products[$i]['booking_product'] = json_decode($child_booking['childBookingsDetails']['booking_products']);
                    $all_booking_products[$i]['start_date'] = $child_booking['start_date'];
                    $all_booking_products[$i]['finish_date'] = $child_booking['finish_date'];
                    $i++;
                }
                
            }
            echo '<pre>'; print_r($all_booking_products); die;
        }*/
        
         
        
}
