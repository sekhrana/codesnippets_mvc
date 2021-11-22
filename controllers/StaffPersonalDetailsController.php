<?php

Yii::app()->clientScript->registerScript('helpers', '
          eyMan = {
              urls: {
                getTags: ' . CJSON::encode(Yii::app()->createUrl("tags/getTags")) . ',
                addTagToStaff: ' . CJSON::encode(Yii::app()->createUrl("tags/addTagToStaff")) . ',
                deleteTagFromStaff: ' . CJSON::encode(Yii::app()->createUrl("tags/deleteTagFromStaff")) . ',
                toggleStaffStatus: ' . CJSON::encode(Yii::app()->createUrl("staffPersonalDetails/toggleStaffStatus")) . ',
              }
          };
      ', CClientScript::POS_END);

class StaffPersonalDetailsController extends RController {

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

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate() {
		$this->pageTitle = "Create Staff | eyMan";
		$this->layout = "dashboard";
		$model = new StaffPersonalDetails;
		$userModel = new User;
		$this->performAjaxValidation($model);
		if (isset($_POST['StaffPersonalDetails']) && !empty($_POST['StaffPersonalDetails'])) {
			$branchModel = Branch::currentBranch();
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model->attributes = $_POST['StaffPersonalDetails'];
				$model->staff_urn = $model->staffUrn();
				$model->branch_id = $branchModel->id;
				$uploadedFile = CUploadedFile::getInstance($model, 'profile_photo');
				if ($model->validate()) {
					if ($uploadedFile) {
						$model->file_name = time() . '_' . uniqid() . '.' . $uploadedFile->extensionName;
						$profile_image = new EasyImage($uploadedFile->getTempName());
						$thumb_image = new EasyImage($uploadedFile->getTempName());
						$profile_image->resize($_POST['staff_original_img_width'], $_POST['staff_original_img_height'])->crop($_POST['staff_img_width'], $_POST['staff_img_height'], $_POST['staff_offset_x'], $_POST['staff_offset_y']);
						$thumb_image->resize($_POST['staff_original_img_width'], $_POST['staff_original_img_height'])->crop($_POST['staff_img_width'], $_POST['staff_img_height'], $_POST['staff_offset_x'], $_POST['staff_offset_y'])->resize(70, 71);
						$model->profile_photo_raw = $profile_image;
						$model->profile_photo_thumb_raw = $thumb_image;
						$model->uploadProfilePhoto();
						if (!empty($model->file_name)) {
							$base64string = base64_encode(file_get_contents(GlobalPreferences::getSslUrl() . $model->profile_photo));
							$model->profile_photo_integration = implode(',', array(pathinfo($model->profile_photo, PATHINFO_EXTENSION), $base64string));
						}
					}
					if ($model->save()) {
						if ($_POST['User']['is_login_allowed'] == 1) {
							$userModel->attributes = $_POST['StaffPersonalDetails'];
							$userModel->email = $_POST['StaffPersonalDetails']['email_1'];
							$userModel->is_login_allowed = $_POST['User']['is_login_allowed'];
							$userModel->activate_token = md5(time() . uniqid() . $userModel->email);
							$userModel->activate_token_expire = date("Y-m-d H:i:s", time() + 50400);
							$userModel->is_activate_token_valid = 1;
							$userModel->is_active = 0;
							if ($userModel->save()) {
								Yii::app()->authManager->assign("staff", $userModel->id);
								$userBranchMappingModel = new UserBranchMapping;
								$userBranchMappingModel->user_id = $userModel->id;
								$userBranchMappingModel->company_id = Company::currentCompany()->id;
								$userBranchMappingModel->branch_id = $model->branch_id;
								$userBranchMappingModel->staff_id = $model->id;
								if (!$userBranchMappingModel->save()) {
									throw new Exception("Seems some problem creating the Staff Login Details.");
								}
								$userModel->sendActivationEmail();
							} else {
								throw new Exception(CHtml::errorSummary($userModel, "", "", array('class' => 'customErrors')));
							}
						}
						$model->eyLogIntegration();
						$transaction->commit();
						if (isset($_POST['Next']))
							$this->redirect(array('/staffGeneralDetails/create', 'staff_id' => $model->id));

						if (isset($_POST['Save']))
							$this->redirect(array('/staffPersonalDetails/update', 'staff_id' => $model->id));
					} else {
						throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
					}
				} else {
					throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
				}
			} catch (Exception $ex) {
				$model->start_date = isset($model->start_date) ? date('d-m-Y', strtotime($model->start_date)) : "";
				$model->leave_date = isset($model->leave_date) ? date('d-m-Y', strtotime($model->leave_date)) : "";
				$model->dob = isset($model->dob) ? date('d-m-Y', strtotime($model->dob)) : "";
				$transaction->rollback();
				Yii::app()->user->setFlash('error', $ex->getMessage());
				$this->refresh();
			}
		}
		$this->render('create', array(
			'model' => $model, 'userModel' => $userModel,
		));
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($staff_id) {
		$this->pageTitle = "Update Staff | eyMan";
		$this->layout = "dashboard";
		$model = StaffPersonalDetails::model()->with(['tags:deleted'])->findByPk($staff_id);
		Yii::app()->session['branch_id'] = $model->branch_id;
		$model->staff_urn = $model->staffUrn();
		$model->hourly_rate_basic = StaffPersonalDetails::getHourlyRate($model->id, date('Y-m-d'));
		$userModel = User::model()->with('userBranchMappings')->find([
			'condition' => 'userBranchMappings.staff_id = :staff_id',
			'params' => [
				':staff_id' => $model->id,
			]
		]);
		if (empty($userModel)) {
			$userModel = new User;
		}
		$this->performAjaxValidation($model);
		if (isset($_POST['StaffPersonalDetails']) && !empty($_POST['StaffPersonalDetails'])) {
			if (Yii::app()->session['role'] == "staff") {
				throw new CHttpException(404, 'You are not authorized to perform this action');
			}
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model->attributes = $_POST['StaffPersonalDetails'];
				$uploadedFile = CUploadedFile::getInstance($model, 'profile_photo');
				if ($model->validate()) {
					if ($uploadedFile) {
						$model->file_name = time() . '_' . uniqid() . '.' . $uploadedFile->extensionName;
						$profile_image = new EasyImage($uploadedFile->getTempName());
						$thumb_image = new EasyImage($uploadedFile->getTempName());
						$profile_image->resize($_POST['staff_original_img_width'], $_POST['staff_original_img_height'])->crop($_POST['staff_img_width'], $_POST['staff_img_height'], $_POST['staff_offset_x'], $_POST['staff_offset_y']);
						$thumb_image->resize($_POST['staff_original_img_width'], $_POST['staff_original_img_height'])->crop($_POST['staff_img_width'], $_POST['staff_img_height'], $_POST['staff_offset_x'], $_POST['staff_offset_y'])->resize(70, 71);
						$model->profile_photo_raw = $profile_image;
						$model->profile_photo_thumb_raw = $thumb_image;
						$model->uploadProfilePhoto();
						if (!empty($model->file_name)) {
							$base64string = base64_encode(file_get_contents(GlobalPreferences::getSslUrl() . $model->profile_photo));
							$model->profile_photo_integration = implode(',', array(pathinfo($model->profile_photo, PATHINFO_EXTENSION), $base64string));
						}
					}
					if ($model->save()) {
						if($_POST['User']['is_login_allowed'] == 1){
							$userModel = User::model()->with('userBranchMappings')->find([
								'condition' => 'userBranchMappings.staff_id = :staff_id',
								'params' => [
									':staff_id' => $model->id,
								]
							]);
							if(empty($userModel)){
								$userModel = new User;
								$userModel->attributes = $_POST['StaffPersonalDetails'];
								$userModel->email = $_POST['StaffPersonalDetails']['email_1'];
								$userModel->is_login_allowed = $_POST['User']['is_login_allowed'];
								$userModel->activate_token = md5(time() . uniqid() . $userModel->email);
								$userModel->activate_token_expire = date("Y-m-d H:i:s", time() + 50400);
								$userModel->is_activate_token_valid = 1;
								$userModel->is_active = 0;
								if ($userModel->save()) {
									Yii::app()->authManager->assign("staff", $userModel->id);
									$userBranchMappingModel = new UserBranchMapping;
									$userBranchMappingModel->user_id = $userModel->id;
									$userBranchMappingModel->company_id = Company::currentCompany()->id;
									$userBranchMappingModel->branch_id = $model->branch_id;
									$userBranchMappingModel->staff_id = $model->id;
									if (!$userBranchMappingModel->save()) {
										throw new Exception("Seems some problem creating the Staff Login Details.");
									}
									$userModel->sendActivationEmail();
								} else {
									throw new Exception(CHtml::errorSummary($userModel, "", "", array('class' => 'customErrors')));
								}
							} else {
								User::model()->updateByPk($userModel->id, [
									'is_login_allowed' => 1
								]);
							}
						}
						if ($_POST['User']['is_login_allowed'] == 0) {
							$userModel = User::model()->with('userBranchMappings')->find([
								'condition' => 'userBranchMappings.staff_id = :staff_id',
								'params' => [
									':staff_id' => $model->id,
								]
							]);
							if (!empty($userModel)) {
								User::model()->updateByPk($userModel->id, [
									'is_login_allowed' => 0
								]);
							}
						}
						$model->eyLogIntegration();
						$transaction->commit();
						if (isset($_POST['Next'])) {
							if (!empty($model->staffGeneralDetails->staff_id))
								$this->redirect(array('staffGeneralDetails/update', 'staff_id' => $model->id, 'general_id' => $model->staffGeneralDetails->id));

							if (empty($model->staffGeneralDetails->staff_id))
								$this->redirect(array('staffGeneralDetails/create', 'staff_id' => $model->id));
						}
						if (isset($_POST['Update'])) {
							$this->redirect(array('update', 'staff_id' => $model->id));
						}
					} else {
						throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
					}
				} else {
					throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				Yii::app()->user->setFlash('error', $ex->getMessage());
				$this->refresh();
			}
		}
		$this->render('update', array(
			'model' => $model, 'userModel' => $userModel
		));
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete() {
		if (Yii::app()->request->isAjaxRequest) {
			$response = array('status' => '1');
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model = $this->loadModel($_POST['id']);
				$model->scenario = "toggleStatus";
				$userModel = User::model()->with('userBranchMappings')->find([
					'condition' => 'userBranchMappings.staff_id = :staff_id',
					'params' => [
						':staff_id' => $model->id,
					]
				]);
				if (!empty($userModel)) {
					User::model()->updateByPk($userModel->id, [
						'is_login_allowed' => 0,
						'is_deleted' => 1
					]);
				}
				ChildPersonalDetails::model()->updateAll(['key_person' => NULL], 'key_person = :key_person', [':key_person' => $_POST['id']]);
				StaffBookings::model()->updateAll(['is_deleted' => 1], 'date_of_schedule >= :today AND staff_id = :staff_id', [
					':today' => date('Y-m-d'),
					':staff_id' => $_POST['id']
				]);
				$model->is_deleted = 1;
				if (StaffPersonalDetails::model()->updateByPk($model->id, ['is_deleted' => 1])) {
					$branchModal = Branch::model()->findByPk(Yii::app()->session['branch_id']);
					if ($branchModal->is_integration_enabled == 1) {
						if (!empty($branchModal->api_key) && !empty($branchModal->api_password) && !empty($branchModal->api_url)) {
							$ch = curl_init($branchModal->api_url . StaffPersonalDetails::STAFF_API_PATH . "/" . $model->external_id);
							$room_data = array(
								'api_key' => $branchModal->api_key,
								'api_password' => $branchModal->api_password,
							);
							$room_data = json_encode($room_data);
							curl_setopt_array($ch, array(
								CURLOPT_FOLLOWLOCATION => 1,
								CURLOPT_RETURNTRANSFER => true,
								CURLOPT_POST => 1,
								CURLOPT_CUSTOMREQUEST => "DELETE",
								CURLOPT_POSTFIELDS => $room_data,
								CURLOPT_HEADER => 0,
								CURLOPT_USERAGENT => "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)",
								CURLOPT_SSL_VERIFYPEER => false
							));
							$response = curl_exec($ch);
							$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
							if ($httpcode == "404") {
								throw new Exception("Please check whether API path is a valid URL");
							}
							curl_close($ch);
							$response = json_decode($response, TRUE);
							if ($response['response']['status'] = "success" && $response['response'][0]['message'] == "Deleted") {
								echo CJSON::encode(array('status' => 1, 'message' => 'staff deleted successfully.'));
							}
						} else {
							throw new Exception("API key/password/url are not set in Branch Settings");
						}
					}
					$transaction->commit();
					echo CJSON::encode($response);
				}
			} catch (Exception $ex) {
				$transaction->rollback();
			}
		}
	}

	/*
	 * function to check the status of child
	 */

	public function actionChangeStatus() {
		if (isset($_POST) && $_POST['isAjaxRequest'] == 1) {
			$model = StaffPersonalDetails::model()->findByPk($_POST['id']);
			if ($model->is_active == 1) {
				$active = 0;
				$status = 1;
				$message = "Staff has been successfully inactive.";
			} else {
				$active = 1;
				$status = 1;
				$message = "Staff has been successfully active.";
			}
			$model->is_active = $active;
			if ($model->save()) {
				echo CJSON::encode(array('status' => $status, 'message' => $message));
			} else {
				$status = 0;
				$message = "Their seems to be some problem changing the status";
				echo CJSON::encode(array('status' => $status, 'message' => $message));
			}
		} else {
			throw new CHttpException(404, "Your request is not valid");
		}
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex() {
		$dataProvider = new CActiveDataProvider('StaffPersonalDetails');
		$criteria = new CDbCriteria();
		$criteria->order = "t.first_name, t.last_name, t.branch_id";
		if (isset($_GET['showAll']) && ($_GET['showAll'] == "true")) {
			$criteria->addInCondition('t.branch_id', explode(",", Branch::model()->find(['select' => 'group_concat(id) as id', 'condition' => 'company_id = :company_id', 'params' => [':company_id' => Yii::app()->session['company_id']]])->id), 'AND');
		} else {
			$criteria->addCondition('t.branch_id = :branch_id');
			$criteria->params = array(':branch_id' => Yii::app()->session['branch_id']);
		}
		if (isset($_GET['sortBy']) && !empty($_GET['sortBy'])) {
			$criteria->order = "t." . $_GET['sortBy'];
		}
		$dataProvider->criteria = $criteria;
		if (isset($_GET['query'])) {
			$criteria = new CDbCriteria();
			$criteria->order = "t.first_name, t.last_name, t.branch_id";
			if (isset($_GET['sortBy']) && !empty($_GET['sortBy'])) {
				$criteria->order = "t." . $_GET['sortBy'];
			}
			$criteria->addSearchCondition('first_name', $_GET['query'], true);
			$criteria->addSearchCondition('last_name', $_GET['query'], true, 'OR');
			$criteria->addSearchCondition('t.gender', $_GET['query'], true, 'OR');
			$criteria->addSearchCondition('t.email_1', $_GET['query'], true, 'OR');
			$criteria->with = array('position0', 'room', 'branch');
			$criteria->addSearchCondition('position0.name', $_GET['query'], true, 'OR');
			$criteria->addSearchCondition('room.name', $_GET['query'], true, 'OR');
			$criteria->addSearchCondition('branch.name', $_GET['query'], true, 'OR');
			if (isset($_GET['showAll']) && ($_GET['showAll'] == "true")) {
				$criteria->addInCondition('t.branch_id', explode(",", Branch::model()->find(['select' => 'group_concat(id) as id', 'condition' => 'company_id = :company_id', 'params' => [':company_id' => Yii::app()->session['company_id']]])->id), 'AND');
			} else {
				$criteria->addSearchCondition('t.branch_id', Yii::app()->session['branch_id'], true, 'AND');
			}
			$dataProvider->criteria = $criteria;
		}
		$dataProvider->pagination->pageSize = 15;

		$branch = Branch::model()->findByPk(Yii::app()->session['branch_id']);
		$model = new StaffPersonalDetails();

		$this->render('index', array(
			'model' => $model,
			'branchName' => $branch->name,
			'dataProvider' => $dataProvider,
		));
	}

	/*
	 * Update staff's branch
	 */

	public function actionUpdateStaffBranch() {
		if (Yii::app()->request->isAjaxRequest) {
			if (!empty($_POST['StaffPersonalDetails']['previousBranch']) && !empty($_POST['StaffPersonalDetails']['newBranch']) && !empty($_POST['StaffPersonalDetails']['effective_date'])) {
				$transaction = Yii::app()->db->beginTransaction();
				try {
					$newBranchModel = Branch::model()->findByPk($_POST['StaffPersonalDetails']['newBranch']);
					$effective_date = date("Y-m-d", strtotime($_POST['StaffPersonalDetails']['effective_date']));
					if (!empty($newBranchModel)) {
						$model = StaffPersonalDetails::model()->findByPk($_POST['user_id']);
						if (!empty($model)) {
							if ($model->is_moved == 1) {
								echo CJSON::encode(array('message' => ['Staff has been already moved.']));
								Yii::app()->end();
							}

							$newStaffPersonalDetailsModel = new StaffPersonalDetails;
							$newStaffGeneralDetailsModel = new StaffGeneralDetails;
							$newStaffBankDetailsModel = new StaffBankDetails;
							$newStaffPersonalDetailsModel->attributes = $model->attributes;
							$newStaffPersonalDetailsModel->staff_urn = NULL;
							$newStaffPersonalDetailsModel->room_id = NULL;
							$newStaffPersonalDetailsModel->branch_id = $newBranchModel->id;
							$newStaffPersonalDetailsModel->start_date = date("d-m-Y", strtotime($model->start_date));
							$newStaffPersonalDetailsModel->dob = (!empty($model->dob) && ($model->dob != NULL)) ? date("d-m-Y", strtotime($model->dob)) : NULL;
							$newStaffPersonalDetailsModel->leave_date = NULL;
							$newStaffPersonalDetailsModel->is_moved = 0;
							$newStaffPersonalDetailsModel->is_active = 1;
							if ($newStaffPersonalDetailsModel->save()) {
								if (!empty($model->staffGeneralDetails)) {
									$newStaffGeneralDetailsModel->attributes = $model->staffGeneralDetails->attributes;
									$newStaffGeneralDetailsModel->staff_id = $newStaffPersonalDetailsModel->id;
									if (!$newStaffGeneralDetailsModel->save()) {
										$transaction->rollback();
										echo CJSON::encode($newStaffGeneralDetailsModel->getErrors());
										Yii::app()->end();
									}
								}

								if (!empty($model->staffBankDetails)) {
									$newStaffBankDetailsModel->attributes = $model->staffBankDetails->attributes;
									$newStaffBankDetailsModel->staff_id = $newStaffPersonalDetailsModel->id;
									if (!$newStaffBankDetailsModel->save()) {
										$transaction->rollback();
										echo CJSON::encode($newStaffBankDetailsModel->getErrors());
										Yii::app()->end();
									}
								}

								$staffDocumentDetailsModel = StaffDocumentDetails::model()->findAllByAttributes(['staff_id' => $model->id]);
								if (!empty($staffDocumentDetailsModel)) {
									foreach ($staffDocumentDetailsModel as $documents) {
										$parentDocs = DocumentType::model()->findByPk($documents->document_id);
										if ($parentDocs) {
											$checkDocs = DocumentType::model()
												->findByAttributes(array(
												'name' => $parentDocs->name,
												'branch_id' => $_POST['StaffPersonalDetails']['newBranch']
											));
											if ($checkDocs) {
												$newDocumentModel = new StaffDocumentDetails;
												$newDocumentModel->attributes = $documents->attributes;
												$newDocumentModel->staff_id = $newStaffPersonalDetailsModel->id;
												$newDocumentModel->document_id = $checkDocs->id;
												if (!$newDocumentModel->save()) {
													$transaction->rollback();
													echo CJSON::encode($newDocumentModel->getErrors());
													Yii::app()->end();
												}
											}
										}
									}
								}
								$staffEventDetailsModel = StaffEventDetails::model()->findAllByAttributes(['staff_id' => $model->id]);
								if (!empty($staffEventDetailsModel)) {
									foreach ($staffEventDetailsModel as $events) {
										$parentEvent = EventType::model()->findByPk($events->event_id);
										if ($parentEvent) {
											$checkEvent = EventType::model()->findByAttributes(['name' => $parentEvent->name, 'branch_id' => $_POST['StaffPersonalDetails']['newBranch']]);
											if ($checkEvent) {
												$newEventModel = new StaffEventDetails;
												$newEventModel->attributes = $events->attributes;
												$newEventModel->staff_id = $newStaffPersonalDetailsModel->id;
												$newEventModel->event_id = $checkEvent->id;
												if (!$newEventModel->save()) {
													$transaction->rollback();
													echo CJSON::encode($newEventModel->getErrors());
													Yii::app()->end();
												}
											}
										}
									}
								}
								$staffBookings = StaffBookings::model()->findAll(['condition' => 'date_of_schedule >= :effective_date AND staff_id = :staff_id', 'params' => [':effective_date' => $effective_date, ':staff_id' => $model->id]]);
								if (!empty($staffBookings)) {
									foreach ($staffBookings as $bookings) {
										$bookings->is_deleted = 1;
										if (!$bookings->save()) {
											$transaction->rollback();
											echo CJSON::encode($bookings->getErrors());
											Yii::app()->end();
										}
									}
								}

								/** Block for transferring the holiday entitlement starts here.* */
								$staffHolidaysEntitlement = StaffHolidaysEntitlement::model()->findAllByAttributes(array('staff_id' => $model->id));
								if (!empty($staffHolidaysEntitlement)) {
									foreach ($staffHolidaysEntitlement as $staffHolidayEntitlement) {
										$newStaffHolidayEntitlement = new StaffHolidaysEntitlement;
										$newStaffHolidayEntitlement->attributes = $staffHolidayEntitlement->attributes;
										$newStaffHolidayEntitlement->branch_id = $newStaffPersonalDetailsModel->branch_id;
										$newStaffHolidayEntitlement->staff_id = $newStaffPersonalDetailsModel->id;
										if (!$newStaffHolidayEntitlement->save()) {
											$transaction->rollback();
											echo CJSON::encode($newStaffHolidayEntitlement->getErrors());
											Yii::app()->end();
										}
										$staffHolidaysEntitlementEvents = StaffHolidaysEntitlementEvents::model()->findAllByAttributes(array('holiday_id' => $staffHolidayEntitlement->id));
										if (!empty($staffHolidaysEntitlementEvents)) {
											foreach ($staffHolidaysEntitlementEvents as $staffHolidayEntitlementEvent) {
												$newStaffHolidayEntitlementEvent = new StaffHolidaysEntitlementEvents;
												$newStaffHolidayEntitlementEvent->attributes = $staffHolidayEntitlementEvent->attributes;
												$newStaffHolidayEntitlementEvent->holiday_id = $newStaffHolidayEntitlement->id;
												$newStaffHolidayEntitlementEvent->branch_id = $newStaffHolidayEntitlement->branch_id;
												if (!$newStaffHolidayEntitlementEvent->save()) {
													$transaction->rollback();
													echo CJSON::encode($newStaffHolidayEntitlementEvent->getErrors());
													Yii::app()->end();
												}
											}
										}
									}
								}
								/** Block for transferring the holiday entitlement ends here.* */
								/** Block for transferring the holidays starts here.* */
								$staffHolidays = StaffHolidays::model()->findAllByAttributes(['staff_id' => $model->id]);
								if (!empty($staffHolidays)) {
									foreach ($staffHolidays as $holiday) {
										$newStaffHolidayModel = new StaffHolidays;
										$newStaffHolidayModel->scenario = "branch_calendar_holiday";
										$newStaffHolidayModel->attributes = $holiday->attributes;
										$newStaffHolidayModel->branch_id = $newStaffPersonalDetailsModel->branch_id;
										$newStaffHolidayModel->staff_id = $newStaffPersonalDetailsModel->id;
										if (!$newStaffHolidayModel->save()) {
											$transaction->rollback();
											echo CJSON::encode($newStaffHolidayModel->getErrors());
											Yii::app()->end();
										}
									}
								}
								/** Block for transferring the holidays ends here.* */
								/*								 * Block for deleting the future holidays starts here* */
								$staffHolidaysModel = StaffHolidays::model()->findAll(['condition' => 'staff_id = :staff_id AND start_date >= :effective_date', 'params' => [':staff_id' => $model->id, ':effective_date' => date("Y-m-d", strtotime($_POST['StaffPersonalDetails']['effective_date']))]]);
								if (!empty($staffHolidaysModel)) {
									foreach ($staffHolidaysModel as $deleteHoliday) {
										$deleteHoliday->scenario = "branch_calendar_holiday";
										$deleteHoliday->is_deleted = 1;
										if (!$deleteHoliday->save()) {
											$transaction->rollback();
											echo CJSON::encode($deleteHoliday->getErrors());
											Yii::app()->end();
										}
									}
								}
								/** Block for deleting the future holidays ends here* */
								$model->leave_date = $_POST['StaffPersonalDetails']['effective_date'];
								$model->is_moved = 1;
								if (date('w', strtotime($model->leave_date)) != 1) {
									echo CJSON::encode(array('message' => ['Effective day to move staff can only be Monday.']));
									Yii::app()->end();
								}
								if (strtotime(date("Y-m-d", strtotime($_POST['StaffPersonalDetails']['effective_date']))) < strtotime(date("Y-m-d"))) {
									$model->is_active = 0;
								}
								if (!$model->save()) {
									$transaction->rollback();
									echo CJSON::encode($model->getErrors());
									Yii::app()->end();
								}
								$newStaffPersonalDetailsModel->staff_urn = $model->staff_urn;
								$newStaffPersonalDetailsModel->dob = (strtotime($newStaffPersonalDetailsModel->dob)) ? date("d-m-Y", strtotime($newStaffPersonalDetailsModel->dob)) : NULL;
								$newStaffPersonalDetailsModel->start_date = (strtotime($newStaffPersonalDetailsModel->start_date)) ? date("d-m-Y", strtotime($newStaffPersonalDetailsModel->start_date)) : NULL;
								if (!$newStaffPersonalDetailsModel->save()) {
									$transaction->rollback();
									echo CJSON::encode($newStaffPersonalDetailsModel->getErrors());
									Yii::app()->end();
								}
								$transaction->commit();
								echo CJSON::encode(array(
									'status' => 1,
									'message' => 'Branch has been succesfully changed.'
								));
								Yii::app()->end();
							} else {
								$transaction->rollback();
								echo CJSON::encode($newStaffPersonalDetailsModel->getErrors());
								Yii::app()->end();
							}
						} else {
							$transaction->rollback();
							echo CJSON::encode(['message' => ['Staff is not present in the nursery.']]);
							Yii::app()->end();
						}
					} else {
						$transaction->rollback();
						echo CJSON::encode(['message' => ['Branch is not present on the system.']]);
						Yii::app()->end();
					}
				} catch (Exception $ex) {
					$transaction->rollback();
					echo CJSON::encode(['message' => ['Their seems to be some problem.']]);
					Yii::app()->end();
				}
			} else {
				echo CJSON::encode(array('message' => ['Please select Current/New Branch/Effective Date.']));
				Yii::app()->end();
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin() {
		$model = new StaffPersonalDetails('search');
		$model->unsetAttributes(); // clear any default values
		if (isset($_GET['StaffPersonalDetails']))
			$model->attributes = $_GET['StaffPersonalDetails'];

		$this->render('admin', array(
			'model' => $model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return StaffPersonalDetails the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id) {
		$model = StaffPersonalDetails::model()->findByPk($id);
		if ($model === null)
			throw new CHttpException(404, 'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param StaffPersonalDetails $model the model to be validated
	 */
	protected function performAjaxValidation($model) {
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'staff-personal-details-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	public function actionGetInvalidModel() {
		$staffModel = StaffPersonalDetails::model()->findAll();
		foreach ($staffModel as $staff) {
			if (!$staff->validate()) {
				echo $staff->first_name . " " . $staff->last_name;
				echo "<pre>";
				print_r($staff->getErrors());
			}
		}
	}

	public function actionImport() {
		$this->pageTitle = "Import Staff | eyMan";
		$this->layout = "importChild";
		if (isset(Yii::app()->session['branch_id'])) {
			$model = new StaffPersonalDetails;
			unset(Yii::app()->session['step']);
			unset(Yii::app()->session['no_header']);
			if (isset(Yii::app()->session['error_message'])) {
				unset(Yii::app()->session['error_message']);
				unset(Yii::app()->session['errors']);
			}
			if (isset($_POST['upload_csv'])) {
				$csv = CUploadedFile::getInstance($model, 'import_file');
				$count = count($csv);
				if (!empty($csv) && strtolower($csv->extensionName) == 'csv') {
					$personal_tbl_columns = $model->getTableSchema()->getColumnNames();
					$unset_columns = array('id', 'branch_id', 'is_dob_unavaliable', 'profile_photo', 'contract_day_sunday', 'contract_day_tuesday', 'contract_day_wednesday', 'contract_day_thursday', 'contract_day_friday', 'contract_day_saturday', 'is_deleted');
					foreach ($personal_tbl_columns as $key => $value) {

						if ($model->isAttributeRequired($value)) {
							$personal_tbl_columns[$key] = $value . "1";
						} else {
							$personal_tbl_columns[$key] = $value . "0";
						}
						if (in_array($value, $unset_columns)) {
							unset($personal_tbl_columns[$key]);
						}
					}
					Yii::app()->session['tbl_columns'] = $personal_tbl_columns;

					$assocData = array();
					if (($handle = fopen("$csv->tempName", "r")) !== FALSE) {
						$rowCounter = 0;
						$csv_header1 = array();
						$csv_header2 = array();
						while (($rowData = fgetcsv($handle, 0, ",")) !== FALSE) {
							if (array(null) !== $rowData && !empty($rowData)) {
								if (0 === $rowCounter) {
									$headerRecord = $rowData;
									foreach ($rowData as $key => $value) {
										if ($value != ',')
											$csv_header1[] = strtolower(str_replace(' ', '_', $headerRecord[$key]));
									}
									Yii::app()->session['first_row'] = $rowData;
								} else {
									foreach ($rowData as $key => $value) {
										if ($rowCounter == 1 && $rowData != '') {
											$csv_header2[] = strtolower(str_replace(' ', '_', $headerRecord[$key]));
										}
										$assocData[$rowCounter - 1][] = $value;
									}
								}
								$rowCounter++;
							}
						}
						fclose($handle);
					}

					if (!empty($assocData) || count($csv_header1) > 0 || count($csv_header2) > 0) {
						if ($csv_header1[0] != '') {
							Yii::app()->session['csv_header'] = $csv_header1;
						} if ($csv_header2[0] != '') {
							Yii::app()->session['csv_header'] = $csv_header2;
						}
						Yii::app()->session['csv_data'] = $assocData;
					} else {
						Yii::app()->user->setFlash('error', "No data found in CSV");
						$this->render('importStaff', array(
							'model' => $model,
						));
						Yii::app()->end();
					}

					$this->render('importStaffDetails', array(
						'model' => $model,
					));
				} else {
					Yii::app()->user->setFlash('error', "Please upload a  valid CSV file");
					$this->render('importStaff', array(
						'model' => $model,
					));
				}
			} else if (isset($_POST['save_staff_profile_details'])) {
				$old_csv_data = Yii::app()->session['csv_data'];
				if (!isset($_POST['header_checkbox'])) {
					$csv_data = Yii::app()->session['csv_data'];
					$first_row = Yii::app()->session['first_row'];
					array_unshift($csv_data, $first_row);
					Yii::app()->session['csv_data'] = $csv_data;
					Yii::app()->session['no_header'] = 1;
				}
				$personal_data = array();
				foreach (Yii::app()->session['csv_data'] as $key => $value) {
					$i = 0;
					foreach ($value as $key1 => $value1) {
						if (isset($_POST['column_selected_' . $i]) && $_POST['column_selected_' . $i] != "") {
							$personal_data[$key][$_POST['column_selected_' . $i]] = $value1;
						} else {
							$next_tab[$key][$i] = $value1;
							$header_keys = array_keys($next_tab[$key]);
						}
						$i++;
					}
				}
				if (!empty($header_keys)) {
					foreach ($header_keys as $key => $value) {
						$new_header[] = Yii::app()->session['csv_header'][$value];
						$new_first_row = Yii::app()->session['first_row'][$value];
					}
				}

				$count_row = 1;
				$inserted_ids = array();
				//outer loop starts
				foreach ($personal_data as $key => $value) {

					foreach ($value as $key1 => $value1) {
						$model->isNewRecord = TRUE;
						$model->id = NULL;
						$value1 = trim($value1);
						if ($value1 == 'null' || $value1 == 'NULL') {
							$value1 = "";
						}
						if ($key1 == 'gender') {
							$value1 = strtolower($value1);
							if ($value1 == 1 || $value1 == 2 || $value1 == 'm' || $value1 == 'f' || $value1 == 'male' || $value1 == 'female') {
								if ($value1 == 1 || $value1 == "1")
									$value1 = 'FEMALE';
								if ($value1 == 2 || $value1 == "2")
									$value1 = 'MALE';
								if ($value1 == 'M' || $value1 == 'm')
									$value1 = 'MALE';
								if ($value1 == 'F' || $value1 == 'f')
									$value1 = 'FEMALE';
							}else {
								$value1 = NULL;
							}


							$value1 = strtoupper($value1);
						}

						if ($key1 == "dob" || $key1 == "start_date" || $key1 == "leave_date") {
							if ($value1 == '00-00-0000' || $value1 == '0000-00-00' || $value1 == '') {
								$value1 = "";
							} else {
								if (strpos($value1, '@') != false) {
									unset(Yii::app()->session['no_header']);
									Yii::app()->session['csv_data'] = $old_csv_data;
									Yii::app()->user->setFlash('error', "Please check Date Format");
									$this->render('importStaffDetails', array(
										'model' => $model,
									));
									Yii::app()->end();
								}
								if (strpos($value1, '/') == false && strpos($value1, '-') == false && strpos($value1, '.') == false) {
									unset(Yii::app()->session['no_header']);
									Yii::app()->session['csv_data'] = $old_csv_data;
									Yii::app()->user->setFlash('error', "Please check Date Format");
									$this->render('importStaffDetails', array(
										'model' => $model,
									));
									Yii::app()->end();
								}
								$value1 = str_replace('/', '-', $value1);
								$value1 = str_replace('.', '-', $value1);
								$value1 = date('d-m-Y', strtotime($value1));
							}
						}

						if ($key1 == "email_1" || $key1 == "email_2" || $key1 == "email_3" || $key1 == "kin_1_email" || $key1 == "kin_2_email") {
							$value1 = trim($value1);
							if (empty($value1) || $value1 == "") {
								$value1 = NULL;
							} else {
								if ($model->validate(array($key1))) {
									$value1 = $value1;
								} else {
									$value1 = NULL;
								}
							}
						}

						if ($key1 == "room_id") {
							$roomModel = Room::model()->findByAttributes(array('name' => trim($value1), 'branch_id' => Yii::app()->session['branch_id']));
							if (!empty($roomModel)) {
								$value1 = $roomModel->id;
							} else {
								$value1 = NULL;
							}
						}

						if ($key1 == "position") {
							$staffPositionModel = PickStaffPosition::model()->findByAttributes(array('name' => trim($value1)));
							if (!empty($staffPositionModel)) {
								$value1 = $staffPositionModel->id;
							} else {
								$value1 = NULL;
							}
						}

						if ($key1 == "additional_role") {
							$staffAdditionalRole = PickStaffAdditionalRole::model()->findByAttributes(array('name' => trim($value1)));
							if (!empty($staffAdditionalRole)) {
								$value1 = $staffAdditionalRole->id;
							} else {
								$value1 = NULL;
							}
						}
						if ($key1 == "contract_day_monday") {
							$contract_days = str_split(trim($value1));
							$model->contract_day_monday = $contract_days[0];
							$model->contract_day_tuesday = $contract_days[1];
							$model->contract_day_wednesday = $contract_days[2];
							$model->contract_day_thursday = $contract_days[3];
							$model->contract_day_friday = $contract_days[4];
							$model->contract_day_saturday = $contract_days[5];
							$model->contract_day_sunday = $contract_days[6];
						}
						if ($key1 != "contract_day_monday") {
							$model->$key1 = $value1;
						}
						$model->branch_id = Yii::app()->session['branch_id'];
					}
					if ($model->validate()) {
						$model->save();
						$inserted_ids[] = $model->id;
					} else {
						$error_message[] = "Staff Personal details cannot be imported,There is some error at row:-" . $count_row;
						$errors[] = $model->getErrors();
						break;
					}
					$count_row++;
				}
				//outer loop ends

				if (isset($error_message)) {
					Yii::app()->session['error_message'] = $error_message;
					Yii::app()->session['errors'] = $errors;
					if (!empty($inserted_ids)) {
						$delIds = implode(', ', $inserted_ids);
						$model->deleteAll('id IN (' . $delIds . ')');
					}
					$this->render('importStaffDetails', array(
						'model' => $model,
					));
				} else {
					if (isset($new_first_row))
						Yii::app()->session['first_row'] = $new_first_row;
					if (isset($new_header))
						Yii::app()->session['csv_header'] = $new_header;
					Yii::app()->session['csv_data'] = $next_tab;
					Yii::app()->session['inserted_staff_ids'] = $inserted_ids;
					$general_tbl_columns = StaffGeneralDetails::model()->getTableSchema()->getColumnNames();
					$unset_columns = array('id', 'staff_id', 'is_deleted');

					$staffGeneralDetailsModel = new StaffGeneralDetails();
					foreach ($general_tbl_columns as $key => $value) {

						if ($staffGeneralDetailsModel->isAttributeRequired($value)) {

							$general_tbl_columns[$key] = $value . "1";
						} else {
							$general_tbl_columns[$key] = $value . "0";
						}
						if (in_array($value, $unset_columns)) {
							unset($general_tbl_columns[$key]);
						}
					}
					Yii::app()->session['tbl_columns'] = $general_tbl_columns;
					Yii::app()->session['step'] = 'general';
					$this->render('importStaffDetails', array(
						'model' => $model,
					));
				}
			} else if (isset($_POST['save_staff_general'])) {
				$old_csv_data = Yii::app()->session['csv_data'];
				$general_data = array();
				foreach (Yii::app()->session['csv_data'] as $key => $value) {
					$i = 0;
					foreach ($value as $key1 => $value1) {
						if (isset($_POST['column_selected_' . $i]) && $_POST['column_selected_' . $i] != "") {
							$general_data[$key][$_POST['column_selected_' . $i]] = $value1;
						} else {
							$bank_tab[$key][$i] = $value1;
							$bank_header_keys = array_keys($bank_tab[$key]);
						}
						$i++;
					}
				}
				if (!empty($bank_header_keys)) {
					foreach ($bank_header_keys as $key => $value) {
						$new_header_bank[] = Yii::app()->session['csv_header'][$value];
						$new_first_row_bank = Yii::app()->session['first_row'][$value];
					}
				}
				$count_row = 1;
				$inserted_ids = array();
				//outer loop starts
				$data_model = new StaffGeneralDetails();
				foreach ($general_data as $key => $value) {

					foreach ($value as $key1 => $value1) {
						$data_model->isNewRecord = TRUE;
						$data_model->id = NULL;
						$data_model->staff_id = Yii::app()->session['inserted_staff_ids'][$key];
						$value1 = trim($value1);
						if ($value1 == 'null' || $value1 == 'NULL') {
							$value1 = "";
						}
						if ($key1 == "ethinicity_id") {
							$staffEthinicityModel = PickEthinicity::model()->findByAttributes(array('name' => trim($value1)));
							if (!empty($staffEthinicityModel)) {
								$value1 = $staffEthinicityModel->id;
							} else {
								$value1 = NULL;
							}
						}
						if ($key1 == "religion_id") {
							$staffReligionModel = PickReligion::model()->findByAttributes(array('name' => trim($value1)));
							if (!empty($staffReligionModel)) {
								$value1 = $staffReligionModel->id;
							} else {
								$value1 = NULL;
							}
						}
						if ($key1 == "esol" || $key1 == "esol") {
							if (trim(strtolower($value1)) == "good") {
								$value1 = "GOOD";
							} else if (trim(strtolower($value1)) == "average") {
								$value1 = "AVERAGE";
							} else if (trim(strtolower($value1)) == "poor") {
								$value1 = "POOR";
							} else {
								$value1 = NULL;
							}
						}

						$data_model->$key1 = $value1;
					}
					if ($data_model->validate()) {
						$data_model->save();
						$inserted_ids[] = $data_model->staff_id;
					} else {
						$error_message[] = "Staff General details cannot be imported,There is some error at row:-" . $count_row;
						$errors[] = $data_model->getErrors();
						break;
					}
					$count_row++;
				}
				//outer loop ends
				if (isset($error_message)) {
					Yii::app()->session['step'] = 'general';
					Yii::app()->session['error_message'] = $error_message;
					Yii::app()->session['errors'] = $errors;
					if (!empty($inserted_ids)) {
						$delIds = implode(', ', $inserted_ids);
						$data_model->deleteAll('id IN (' . $delIds . ')');
					}
					$this->render('importStaffDetails', array(
						'model' => $model,
					));
				} else {
					if (isset($new_first_row_bank))
						Yii::app()->session['first_row'] = $new_first_row_bank;
					if (isset($new_header_bank))
						Yii::app()->session['csv_header'] = $new_header_bank;
					Yii::app()->session['csv_data'] = $bank_tab;
					Yii::app()->session['inserted_staff_ids'] = $inserted_ids;
					$bank_tbl_columns = StaffBankDetails::model()->getTableSchema()->getColumnNames();
					$unset_columns = array('id', 'staff_id');

					$staffBankDetailsModel = new StaffBankDetails();
					foreach ($bank_tbl_columns as $key => $value) {

						if ($staffBankDetailsModel->isAttributeRequired($value)) {

							$bank_tbl_columns[$key] = $value . "1";
						} else {
							$bank_tbl_columns[$key] = $value . "0";
						}
						if (in_array($value, $unset_columns)) {
							unset($bank_tbl_columns[$key]);
						}
					}
					Yii::app()->session['tbl_columns'] = $bank_tbl_columns;
					Yii::app()->session['step'] = 'bank';
					$this->render('importStaffDetails', array(
						'model' => $model,
					));
				}
			} else if (isset($_POST['save_staff_bank'])) {
				$old_csv_data = Yii::app()->session['csv_data'];
				$bank_data = array();
				foreach (Yii::app()->session['csv_data'] as $key => $value) {
					$i = 0;
					foreach ($value as $key1 => $value1) {
						if (isset($_POST['column_selected_' . $i]) && $_POST['column_selected_' . $i] != "") {
							$bank_data[$key][$_POST['column_selected_' . $i]] = $value1;
						}
						$i++;
					}
				}
				$count_row = 1;
				$inserted_ids = array();
				//outer loop starts
				$bank_model = new StaffBankDetails();
				foreach ($bank_data as $key => $value) {

					foreach ($value as $key1 => $value1) {
						$bank_model->isNewRecord = TRUE;
						$bank_model->id = NULL;
						$bank_model->staff_id = Yii::app()->session['inserted_staff_ids'][$key];
						$value1 = trim($value1);
						if ($value1 == 'null' || $value1 == 'NULL') {
							$value1 = "";
						}
						$bank_model->$key1 = $value1;
					}
					if ($bank_model->validate()) {
						$bank_model->save();
						$inserted_ids[] = $bank_model->id;
					} else {
						$error_message[] = "Staff Bank details cannot be imported,There is some error at row:-" . $count_row;
						$errors[] = $bank_model->getErrors();
						break;
					}
					$count_row++;
				}
				//outer loop ends
				if (isset($error_message)) {
					Yii::app()->session['step'] = 'general';
					Yii::app()->session['error_message'] = $error_message;
					Yii::app()->session['errors'] = $errors;
					if (!empty($inserted_ids)) {
						$delIds = implode(', ', $inserted_ids);
						$bank_model->deleteAll('id IN (' . $delIds . ')');
					}
					$this->render('importStaffDetails', array(
						'model' => $model,
					));
				} else {
					$this->redirect(array('staffPersonalDetails/index'));
				}
			} else {
				$this->render('importStaff', array(
					'model' => $model,
				));
			}
		} else {
			throw new CHttpException(404, 'Please create branch in the system');
		}
	}

	public function actionCorrectName() {
		$model = StaffPersonalDetails::model()->findAllByAttributes(array('branch_id' => Yii::app()->session['branch_id']));
		foreach ($model as $staff) {
			$name = explode(" ", $staff->first_name);
			$staff->first_name = $name[0];
			$staff->last_name = $name[1];
			if ($staff->save()) {
				echo "Done for " . $staff->first_name . " " . $staff->last_name . "</br>";
			} else {
				echo "Not done for " . $staff->first_name . " " . $staff->last_name . "</br>";
			}
		}
	}

	public function actionToggleStatus() {
		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1)) {
			if ($_POST['status'] == 1) {
				Yii::app()->user->setState('skipActiveScopeStaff', TRUE);
			} else {
				Yii::app()->user->setState('skipActiveScopeStaff', FALSE);
			}
			echo CJSON::encode(array('status' => 1));
		} else {
			throw new CHttpException(404, 'Your request is not Valid.');
		}
	}

	public function actionGetPefferedActivity() {
		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1)) {
			$staffModel = StaffPersonalDetails::model()->findByPk($_POST['staff_id']);
			if (isset($staffModel->preffered_activity) && !empty($staffModel->preffered_activity) && $staffModel->preffered_activity != NULL) {
				echo CJSON::encode(array('status' => 1, 'activity_id' => $staffModel->preffered_activity));
			} else {
				echo CJSON::encode(array('status' => 0));
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	public function actionToggleStaffStatus() {
		if (Yii::app()->request->isAjaxRequest) {
			$model = StaffPersonalDetails::model()->resetScope()->findByPk($_POST['staff_id']);
			$model->scenario = "toggleStatus";
			$model->is_active = ($model->is_active == 1) ? 0 : 1;
			$status = ($model->is_active == 1) ? "active." : "inactive.";
			if ($model->save()) {
				$flashMessageSuccess = $model->name . " has been successfully marked as " . $status;
				Yii::app()->user->setFlash("success", $flashMessageSuccess);
				echo CJSON::encode([
					'status' => 1,
					'url' => $this->createAbsoluteUrl('staffPersonalDetails/index')
				]);
				Yii::app()->end();
			} else {
				$flashMessageError = "Their seems some problem marking the staff " . $status;
				Yii::app()->user->setFlash("success", $flashMessageError);
				echo CJSON::encode([
					'status' => 0,
					'url' => $this->createAbsoluteUrl('staffPersonalDetails/update', ['id' => $model->id])
				]);
				Yii::app()->end();
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

}
