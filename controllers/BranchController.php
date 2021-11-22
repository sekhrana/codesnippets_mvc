<?php

Yii::app()->clientScript->registerScript('helpers', '
          eyMan = {
              urls: {
                generateUniqueUrl: ' . CJSON::encode(Yii::app()->createUrl('branch/generateUniqueUrl')) . ',
              }
          };
      ', CClientScript::POS_END);

class BranchController extends eyManController {

	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout = '//layouts/systemSettings';

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
		$model = $this->loadModel($id);
		$this->render('view', array(
			'model' => $model
		));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate() {
		$this->pageTitle = 'Create Branch | eyMan';
		$model = new Branch;
		$this->performAjaxValidation($model);
		$transaction = Yii::app()->db->beginTransaction();
		try {
			if (isset($_POST['Branch'])) {
				$model->attributes = $_POST['Branch'];
				$model->company_id = Yii::app()->session['company_id'];
				$nursery_working_days = array_filter($_POST['Branch']['nursery_operation_days']);
				if (array_search(7, $nursery_working_days) != FALSE) {
					$nursery_working_days[array_search(7, $nursery_working_days)] = 0;
				}
				$model->nursery_operation_days = implode(",", $nursery_working_days);
				if ($model->save()) {
					$model->insertAgeRatio($model->id);
					$company = Company::model()->findByPk(Yii::app()->session['company_id']);
					if ($company->is_integration_enabled == 1) {
						if (!empty($model->api_url)) {
							$ch = curl_init($model->api_url . '/api-eyman/nursery');
							$nursery_data = array(
								'nursery' => array(
									array(
										'name' => $model->name,
										'address1' => $model->address_1,
										'address2' => $model->address_2,
										'city' => $model->town,
										'county' => $model->county,
										'country' => $model->countries->name,
										'postcode' => $model->postcode,
										'telephone' => $model->phone,
										'website' => $model->website_link,
										'email' => $model->email,
										'server_url' => $model->api_url,
										'external_id' => "eyman-" . $model->id,
                                                                                'child_limit' => $model->child_limit
									)
								),
							);
							$nursery_data = json_encode($nursery_data);
							curl_setopt_array($ch, array(
								CURLOPT_FOLLOWLOCATION => 1,
								CURLOPT_RETURNTRANSFER => true,
								CURLOPT_POST => 1,
								CURLOPT_POSTFIELDS => $nursery_data,
								CURLOPT_HEADER => 0,
								CURLOPT_USERAGENT => "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)",
								CURLOPT_SSL_VERIFYPEER => false,
							));
							$response = curl_exec($ch);
							$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

							curl_close($ch);
							$response = json_decode($response, TRUE);
							if ($response['response'][0]['status'] == "success" && $response['response'][0]['message'] == 'Added' && $response['response'][1]['eylogAdmin']['status'] == 'success' && $response['response'][1]['eylogAdmin']['message'] == 'Added') {
								$model->external_id = $response['response'][0]['id'];
								$model->api_key = $response['response'][1]['eylogAdmin']['api_key'];
								$model->api_password = base64_encode($response['response'][1]['eylogAdmin']['api_password']);
								if (!$model->save()) {
									throw new Exception(CHtml::errorSummary($model, '', '', array('class' => 'customErrors')));
								}
							}

							if ($response['response'][0]['status'] == "failure") {
								Yii::app()->user->setFlash('error', $response['response'][0]['message']);
								throw new Exception($response['response'][0]['message']);
							}
						}
					}
					$transaction->commit();
					$branchModel = Branch::model()->findAllByAttributes(array('company_id' => Yii::app()->session['company_id']));
					if (count($branchModel) === 1) {
						Yii::app()->session['branch_id'] = $model->id;
					}
                                        Yii::app()->session['import_data'] = true;
					$this->redirect(array('update','id'=>$model->id));
					$this->redirect(array('site/dashboard'));
				} else {
					throw new Exception(CHtml::errorSummary($model, '', '', array('class' => 'customErrors')));
				}
			}
		} catch (Exception $ex) {
			Yii::app()->user->setFlash('error', $ex->getMessage());
			$transaction->rollback();
		}
		$this->render('create', array(
			'model' => $model,
		));
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id) {

		$this->pageTitle = 'Update Branch | eyMan';
		$model = $this->loadModel($id);
		$this->performAjaxValidation($model);
		$transaction = Yii::app()->db->beginTransaction();
		try {
			if (isset($_POST['Branch'])) {
				$model->attributes = $_POST['Branch'];
				$nursery_working_days = array_filter($_POST['Branch']['nursery_operation_days']);
				if (array_search(7, $nursery_working_days) != FALSE) {
					$nursery_working_days[array_search(7, $nursery_working_days)] = 0;
				}
				$model->nursery_operation_days = implode(",", $nursery_working_days);
				if ($model->save()) {
					$transaction->commit();
					$this->redirect(array('branch/view', 'id' => $model->id));
				} else {
					throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
				}
			}
		} catch (Exception $ex) {
			$transaction->rollback();
			Yii::app()->user->setFlash('error', $ex->getMessage());
			$this->refresh();
		}
		$this->render('update', array(
			'model' => $model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return Branch the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id) {
		$model = Branch::model()->findByPk($id);
		if ($model === null)
			throw new CHttpException(404, 'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param Branch $model the model to be validated
	 */
	protected function performAjaxValidation($model) {
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'branch-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	public function actionCheckEylogConnection($id) {
		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1)) {
			$response = array(
				'status' => 0,
				'message' => ''
			);
			$branchModal = Branch::model()->findByPk($id);
			if ($branchModal->is_integration_enabled == 1) {
				if (!empty($branchModal->api_key) && !empty($branchModal->api_password) && !empty($branchModal->api_url)) {
					$response['status'] = '1';
					$response['message'] = 'Successfully connected to eyLog';
					echo CJSON::encode($response);
				} else {
					$response['status'] = 0;
					$response['message'] = 'API Key/Password are incorrect';
					echo CJSON::encode($response);
				}
			} else {
				$response['status'] = 0;
				$response['message'] = 'Integration is not enabled in the branch Settings';
				echo CJSON::encode($response);
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionImportEylogData() {
		ini_set("max_execution_time", 0);
		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1) && isset($_POST['importBranchId'])) {
			if (($_POST['child_data'] == 1) || ($_POST['staff_data'] == 1) || ($_POST['room_data'] == 1) || ($_POST['manager_data'] == 1)) {
				$isChildData = Yii::app()->request->getPost('child_data');
				$isStaffData = Yii::app()->request->getPost('staff_data');
				$isRoomData = Yii::app()->request->getPost('room_data');
				$isManagerData = Yii::app()->request->getPost('manager_data');
				$roomColor = array('orange', 'blue', 'green', 'red', 'yellow', 'violet', 'purple', 'darkorange', 'blueviolet');
				$log_response = array();
				$branchModel = Branch::model()->findByPk($_POST['importBranchId']);
				if (!empty($branchModel)) {
					$data = array(
						'api_key' => $branchModel->api_key,
						'api_password' => $branchModel->api_password,
					);
					if ($isManagerData == 1) {
						$response = customFunctions::executeCurl($branchModel->api_url . User::MANAGER_IMPORT_API_PATH, $data);
						if ($response['status'] == "success") {
							if (!empty($response['manager_data'])) {
								$importedManager = array();
								$notImportedManager = array();
								foreach ($response['manager_data'] as $eyLogManager) {
									$transaction = Yii::app()->db->beginTransaction();
									try {
										if (!empty($eyLogManager)) {
											$manager_data = [
												'branch_id' => $_POST['importBranchId'],
												'first_name' => $eyLogManager['first_name'],
												'last_name' => $eyLogManager['last_name'],
												'email' => $eyLogManager['email'],
												'external_id' => $eyLogManager['manager_id']
											];
											if (isset($eyLogManager['photo']) && !empty(trim($eyLogManager['photo']))) {
												$rawPhoto = customFunctions::curlGetPhoto($branchModel->api_url . $eyLogManager['photo']);
												if ($rawPhoto != FALSE) {
													$extensionName = pathinfo($eyLogManager['photo'], PATHINFO_EXTENSION);
													$fileName = time() . '_' . uniqid() . '.' . $extensionName;
													$fp = @fopen(Yii::app()->basePath . '/../uploaded_images/' . $fileName, 'x');
													@fwrite($fp, $rawPhoto);
													@fclose($fp);
													$thumb_image = new EasyImage(Yii::app()->basePath . '/../uploaded_images/' . $fileName);
													$thumb_image->resize(70, 71);
													$thumb_image->save(Yii::app()->basePath . '/../uploaded_images/thumbs/' . $fileName);
													$eyLogManager['photo'] = $fileName;
												}
											}
											$activateToken = md5(time() . uniqid() . $eyLogManager['email']);
											$manager_data['activate_token'] = $activateToken;
											$manager_data['activate_token_expire'] = date("Y-m-d H:i:s", time() + 172800);
											$manager_data['is_activate_token_valid'] = 1;
											if (User::model()->importEylogManager($eyLogManager['manager_id'], $manager_data, $branchModel, User::BRANCH_MANAGER)) {
												$url = $this->createAbsoluteUrl('user/activate', array('activateToken' => $activateToken));
												$to = $manager_data['email'];
												$name = $manager_data['first_name'] . " " . $manager_data['last_name'];

												$subject = "Activate your eyMan account";
												$content = "Hello " . "<b>" . $name . "</b>" . "<br/><br/>";
												$content .= "Welcome to eyMan - Early Years Management!" . "<br/><br/>";
												$content .= "To get started, click on the following link to confirm and activate your account - " . "<a href=$url>Click Here</a>" . "<br/><br/>";
												$content .= "Please note that for security reasons this link will expire in 14 days, after which you will need to be sent a new invitation.";
												$isSent = customFunctions::sendEmail($to, $name, $subject, $content);
												if ($isSent) {
													$transaction->commit();
													$importedManager[] = $eyLogManager['first_name'] . "_" . $eyLogManager['manager_id'];
												} else {
													$transaction->rollback();
													$notImportedManager[] = [$eyLogManager['first_name'] . "_" . $eyLogManager['manager_id'], "Error sending the email."];
													@unlink(Yii::app()->basePath . '/../uploaded_images/' . $fileName);
													@unlink(Yii::app()->basePath . '/../uploaded_images/thumbs/' . $fileName);
												}
											}
										}
									} catch (Exception $ex) {
										$transaction->rollback();
										$notImportedManager[] = [$eyLogManager['first_name'] . "_" . $eyLogManager['manager_id'], $ex->getMessage()];
										@unlink(Yii::app()->basePath . '/../uploaded_images/' . $fileName);
										@unlink(Yii::app()->basePath . '/../uploaded_images/thumbs/' . $fileName);
									}
								}
							}
						}
						$log_response['manager_response'] = [$importedManager, $notImportedManager];
					}

					if ($isRoomData == 1) {
						$response = customFunctions::executeCurl($branchModel->api_url . Room::ROOM_IMPORT_API_PATH, $data);
						if ($response['status'] == "success") {
							if (!empty($response['room_data'])) {
								$importedRoom = array();
								$notImportedRoom = array();
								foreach ($response['room_data'] as $eyLogRoom) {
									$transaction = Yii::app()->db->beginTransaction();
									try {
										if (!empty($eyLogRoom)) {
											$room_data = [
												'branch_id' => $_POST['importBranchId'],
												'name' => $eyLogRoom['nursery_group_name'],
												'description' => $eyLogRoom['nursery_group_description'],
												'capacity' => 20,
												'age_group_lower' => 0,
												'age_group_upper' => 5,
												'external_id' => $eyLogRoom['group_id'],
												'logo' => $eyLogRoom['photo']
											];
											Room::model()->importEylogRoom($eyLogRoom['group_id'], $room_data);
										}
										$transaction->commit();
										$importedRoom[] = $eyLogRoom['nursery_group_name'] . "_" . $eyLogRoom['group_id'];
									} catch (Exception $ex) {
										$transaction->rollback();
										$notImportedRoom[] = $eyLogRoom['nursery_group_name'] . "_" . $eyLogRoom['group_id'];
										@unlink(Yii::app()->basePath . '/../uploaded_images/room_logos/' . $fileName);
									}
								}
							}
						}
						$log_response['room_response'] = [$importedRoom, $notImportedRoom];
					}
					if ($isStaffData == 1) {
						$ch = curl_init($branchModel->api_url . "/api/children/getStaff");
						$data = array(
							'api_key' => $branchModel->api_key,
							'api_password' => $branchModel->api_password,
						);
						$data = json_encode($data);
						curl_setopt_array($ch, array(
							CURLOPT_FOLLOWLOCATION => 1,
							CURLOPT_RETURNTRANSFER => true,
							CURLOPT_POST => 1,
							CURLOPT_POSTFIELDS => $data,
							CURLOPT_HEADER => 0,
							CURLOPT_USERAGENT => "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)",
							CURLOPT_SSL_VERIFYPEER => false,
						));
						$response = curl_exec($ch);
						$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
						curl_close($ch);
						$response = json_decode($response, TRUE);
						if ($response['status'] == "success") {
							if (!empty($response['staff_data'])) {
								$importedStaff = array();
								$notImportedStaff = array();
								foreach ($response['staff_data'] as $eyLogStaff) {
									$staffPersonalDetails = new StaffPersonalDetails;
									$staffPersonalDetails->scenario = "eyLogDataImport";
									$staffPersonalDetails->branch_id = $_POST['importBranchId'];
									$staffPersonalDetails->first_name = $eyLogStaff['first_name'];
									$staffPersonalDetails->last_name = $eyLogStaff['last_name'];
									$staffPersonalDetails->external_id = $eyLogStaff['practitioner_id'];
									$staffPersonalDetails->can_publish_observations = $eyLogStaff['allow_submit'];
									$staffPersonalDetails->is_reviewer = $eyLogStaff['group_leader'];
									$staffPersonalDetails->staff_urn = $staffPersonalDetails->staffUrn();
									$roomModel = Room::model()->findByAttributes(array('external_id' => $eyLogStaff['group_id']));
									$staffPersonalDetails->room_id = (empty($roomModel)) ? NULL : $roomModel->id;
									if ($staffPersonalDetails->save()) {
										if (isset($eyLogStaff['photo']) && !empty(trim($eyLogStaff['photo']))) {
												StaffPersonalDetails::model()->updateByPk($staffPersonalDetails->id, [
													'profile_photo' => $eyLogStaff['photo'],
													'profile_photo_thumb' => $eyLogStaff['photo']
												]);
												$importedStaff[] = $eyLogStaff['first_name'] . " " . $eyLogStaff['last_name'];
										} else {
											$importedStaff[] = $eyLogStaff['first_name'] . " " . $eyLogStaff['last_name'];
										}
									} else {
										$error_details = array();
										$error_details[$eyLogStaff['first_name'] . " " . $eyLogStaff['last_name'] . "_" . $eyLogStaff['practitioner_id']] = $staffPersonalDetails->getErrors();
										$notImportedStaff[] = $error_details;
									}
								}
							}
						}
						$staff_response = array();
						$staff_response['importedStaff'] = $importedStaff;
						$staff_response['notImportedStaff'] = $notImportedStaff;
						array_push($log_response, $staff_response);
					}
				}

				if ($isChildData == 1) {
					$ch = curl_init($branchModel->api_url . "/api/children/getChildParent");
					$data = array(
						'api_key' => $branchModel->api_key,
						'api_password' => $branchModel->api_password,
					);
					$data = json_encode($data);
					curl_setopt_array($ch, array(
						CURLOPT_FOLLOWLOCATION => 1,
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_POST => 1,
						CURLOPT_POSTFIELDS => $data,
						CURLOPT_HEADER => 0,
						CURLOPT_USERAGENT => "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)",
						CURLOPT_SSL_VERIFYPEER => false,
					));
					$response = curl_exec($ch);
					$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
					curl_close($ch);
					$response = json_decode($response, TRUE);
					if (!empty($response['child_data'])) {
						$importedChild = array();
						$notImportedChild = array();
						$importedParent = array();
						$notImportedParent = array();
						foreach ($response['child_data'] as $key => $value) {
							$eylogChild = $value['child'];
							$childPersonalDetailsModel = new ChildPersonalDetails;
							$childPersonalDetailsModel->scenario = "eyLogDataImport";
							$childPersonalDetailsModel->child_urn = $childPersonalDetailsModel->childUrn();
							$childPersonalDetailsModel->branch_id = $_POST['importBranchId'];
							$childPersonalDetailsModel->first_name = $eylogChild['first_name'];
							$childPersonalDetailsModel->middle_name = $eylogChild['middle_name'];
							$childPersonalDetailsModel->last_name = $eylogChild['last_name'];
							$childPersonalDetailsModel->is_lac = $eylogChild['lac'];
							$childPersonalDetailsModel->is_pupil_premium = $eylogChild['pupilpremium'];
							$childPersonalDetailsModel->dob = (!empty(trim($eylogChild['dob'])) && $eylogChild['dob'] != NULL) ? date("d-m-Y", strtotime($eylogChild['dob'])) : NULL;
							if ($eylogChild['gender'] == 1) {
								$eylogChild['gender'] = 'FEMALE';
							} else if ($eylogChild['gender'] == 2) {
								$eylogChild['gender'] = 'MALE';
							} else {
								$eylogChild['gender'] = NULL;
							}
							$childPersonalDetailsModel->gender = $eylogChild['gender'];
							if (trim($eylogChild['group_id']) != "" && $eylogChild['group_id'] != NULL) {
								$roomModel = Room::model()->findByAttributes(array('external_id' => trim($eylogChild['group_id'])));
								if (!empty($roomModel)) {
									$childPersonalDetailsModel->room_id = $roomModel->id;
								} else {
									$childPersonalDetailsModel->room_id = NULL;
								}
							}
							if (trim($eylogChild['practitioner_id']) != "" && $eylogChild['practitioner_id'] != NULL) {
								$keyPersonModel = StaffPersonalDetails::model()->findByAttributes(array('external_id' => trim($eylogChild['practitioner_id'])));
								if (!empty($keyPersonModel)) {
									$childPersonalDetailsModel->key_person = $keyPersonModel->id;
								} else {
									$childPersonalDetailsModel->key_person = NULL;
								}
							}
							$childPersonalDetailsModel->external_id = $eylogChild['child_id'];
							if ($childPersonalDetailsModel->save()) {
								if (isset($eylogChild['photo']) && ($eylogChild['photo'] != NULL)) {
										ChildPersonalDetails::model()->updateByPk($childPersonalDetailsModel->id, [
											'profile_photo' => $eylogChild['photo'],
											'profile_photo_thumb' => $eylogChild['photo']
										]);
								}
								$childGeneralDetailsModel = new ChildGeneralDetails;
								$childGeneralDetailsModel->child_id = $childPersonalDetailsModel->id;
								$childGeneralDetailsModel->dietary_requirements = trim($eylogChild['dietary_requirments']);
								$childGeneralDetailsModel->notes = trim($eylogChild['child_notes']);
								$childGeneralDetailsModel->general_notes = trim($eylogChild['allergies']);
								$childGeneralDetailsModel->first_language = trim($eylogChild['first_lang']);
								$childGeneralDetailsModel->save();
								$childMedicalDetails = new ChildMedicalDetails;
								$childMedicalDetails->child_id = $childPersonalDetailsModel->id;
								$childMedicalDetails->medical_notes = trim($eylogChild['medication']);
								$childMedicalDetails->save();

								array_push($importedChild, $childPersonalDetailsModel->name);
								$child_id = $childPersonalDetailsModel->id;
								$parents = $value['parent'];
								if (count($parents) > 0) {
									foreach ($parents as $pkey => $pval) {
										$parentModel = Parents::model()->findByAttributes(['email' => $pval[0]['email']]);
										if (empty($parentModel)) {
											$parentModel = new Parents;
											$parentModel->first_name = $pval[0]['parent_first_name'];
											$parentModel->last_name = $pval[0]['parent_first_name'];
											$parentModel->email = $pval[0]['email'];
											$parentModel->mobile_phone = $pval[0]['mobile_number'];
											$parentModel->relationship = $pval[0]['relationship'];
											if (!$parentModel->save()) {
												throw new CException("Failed to save parent detail");
											}
										}
										$checkParentMapping = ParentChildMapping::model()->findByAttributes([
											'parent_id' => $parentModel->id,
											'child_id' => $childPersonalDetailsModel->id,
											'order' => $pval[0]['order']
										]);
										if (empty($checkParentMapping)) {
											$parentChildMapping = new ParentChildMapping;
											$parentChildMapping->parent_id = $parentModel->id;
											$parentChildMapping->child_id = $childPersonalDetailsModel->id;
											$parentChildMapping->order = $pval[0]['parent_order'];
											if ($parentChildMapping->save()) {
												array_push($importedParent, $childPersonalDetailsModel->name);
											} else {
												array_push($notImportedParent, $childPersonalDetailsModel->name);
											}
										}
									}
								}
							} else {
								$error_details = array();
								$error_details[$childPersonalDetailsModel->name . "_" . $childPersonalDetailsModel->id] = $childPersonalDetailsModel->getErrors();
								array_push($notImportedChild, $error_details);
							}
						}
					}
					$child_response = array();
					$child_response['importedChild'] = $importedChild;
					$child_response['notImportedChild'] = $notImportedChild;
					$parent_response = array();
					$parent_response['importedParent'] = $importedParent;
					$parent_response['notImportedParent'] = $notImportedParent;
					array_push($log_response, $child_response);
					array_push($log_response, $parent_response);
				}
				echo CJSON::encode($log_response);
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid');
		}
	}

	public function actionBranchSettings() {

		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1) && isset($_POST['importBranchId'])) {
			$branchId = $_POST['importBranchId'];
			$room_data = $_POST['room_data'];
			$analysiscode_data = $_POST['analysiscode_data'];
			$vate_code = $_POST['vate_code'];
			$paytype_data = $_POST['paytype_data'];
			$sessionRates_data = $_POST['sessionRates_data'];
			$product_data = $_POST['product_data'];
			$document_data = $_POST['document_data'];
			$event_data = $_POST['event_data'];
			$terms_data = $_POST['terms_data'];
			$ageratio_data = $_POST['ageratio_data'];
			$log_response = array();
			try {
				//inserting all global rooms for current branch
				if ($room_data == 1) {
					$roomTransaction = Yii::app()->db->beginTransaction();

					$RoomModel = Room::model()->findAllByAttributes(array('is_active' => 1, 'is_global' => 1, 'global_id' => Yii::app()->session['company_id'], 'is_deleted' => 0));
					$addedRoom = array();
					$room_response = array();
					if (!empty($RoomModel)) {
						foreach ($RoomModel as $room) {
							$room->isNewRecord = true;
							$room->branch_id = $branchId;
							$room->attributes = $room;
							$room->is_global = 0;
							$room->global_room_id = $room->id;
							$room->id = null;
							if (!$room->save()) {
								$room_response['Rooms_Error'] = $room->getErrors();
							} else {
								array_push($addedRoom, $room->name);
							}
						}
						if (empty($room_response['Rooms_Error'])) {
							$roomTransaction->commit();
							$room_response['addedRooms'] = $addedRoom;
						} else {
							$roomTransaction->rollback();
						}
						array_push($log_response, $room_response);
					}
				}
				//inserting all global analysis codes for current branch
				if ($analysiscode_data == 1) {
					$addAnalysisCode = array();
					$analsisTransaction = Yii::app()->db->beginTransaction();
					$analysis_response = array();
					$AnalysisModel = AnalysisCodes::model()->findAllByAttributes(array('is_active' => 1, 'is_global' => 1, 'global_id' => Yii::app()->session['company_id'], 'is_deleted' => 0));
					if (!empty($AnalysisModel)) {
						foreach ($AnalysisModel as $analysis) {
							$analysis->isNewRecord = true;
							$analysis->branch_id = $branchId;
							$analysis->attributes = $analysis;
							$analysis->is_global = 0;
							$analysis->global_analysiscode_id = $analysis->id;
							$analysis->id = null;
							if (!$analysis->save()) {
								$analysis_response['Analysis_Errors'] = $analysis->getErrors();
							} else {
								array_push($addAnalysisCode, $analysis->name);
							}
						}
						if (empty($analysis_response['Analysis_Errors'])) {
							$analsisTransaction->commit();
							$analysis_response['addedAnalysis'] = $addAnalysisCode;
						} else {
							$analsisTransaction->rollback();
						}
						array_push($log_response, $analysis_response);
					}
				}
				if ($vate_code == 1) {
					//inserting all global vatcodes for current branch
					$addVateCode = array();
					$vatCodeTransaction = Yii::app()->db->beginTransaction();
					$vatecode_response = array();
					$VatModel = Vatcodes::model()->findAllByAttributes(array('is_active' => 1, 'is_global' => 1, 'global_id' => Yii::app()->session['company_id'], 'is_deleted' => 0));
					if (!empty($VatModel)) {
						foreach ($VatModel as $vat) {
							$vat->isNewRecord = true;
							$vat->branch_id = $branchId;
							$vat->attributes = $vat;
							$vat->is_global = 0;
							$vat->global_vatcodes_id = $vat->id;
							$vat->id = null;
							if (!$vat->save()) {
								$vatecode_response['VateCode_Errors'] = $vat->getErrors();
							} else {
								array_push($addVateCode, $vat->name);
							}
						}
						if (empty($vatecode_response['VateCode_Errors'])) {
							$vatCodeTransaction->commit();
							$vatecode_response['addedVatecode'] = $addVateCode;
						} else {
							$vatCodeTransaction->rollback();
						}
						array_push($log_response, $vatecode_response);
					}
				}
				if ($paytype_data == 1) {
					//inserting all global paytypes for current branch
					$addPaytype = array();
					$paytypeTransaction = Yii::app()->db->beginTransaction();
					$paytype_response = array();
					$PayModel = PayType::model()->findAllByAttributes(array('is_active' => 1, 'is_global' => 1, 'global_id' => Yii::app()->session['company_id'], 'is_deleted' => 0));
					if (!empty($PayModel)) {
						foreach ($PayModel as $pay) {
							$pay->isNewRecord = true;
							$pay->branch_id = $branchId;
							$pay->attributes = $pay;
							$pay->is_global = 0;
							$pay->global_paytype_id = $pay->id;
							$pay->id = null;
							if (!$pay->save()) {
								$paytype_response['Paytype_Errors'] = $pay->getErrors();
							} else {
								array_push($addPaytype, $pay->abbreviation);
							}
						}
						if (empty($paytype_response['Paytype_Errors'])) {
							$paytypeTransaction->commit();
							$paytype_response['addedPaytype'] = $addPaytype;
						} else {
							$paytypeTransaction->rollback();
						}
						array_push($log_response, $paytype_response);
					}
				}
				if ($product_data == 1) {
					//inserting all global products for current branch
					$addProduct = array();
					$productTransaction = Yii::app()->db->beginTransaction();
					$product_response = array();
					$ProductModel = Products::model()->findAllByAttributes(array('is_active' => 1, 'is_global' => 1, 'global_id' => Yii::app()->session['company_id'], 'is_deleted' => 0, 'is_modified' => 0));
					if (!empty($ProductModel)) {
						foreach ($ProductModel as $product) {
							$product->isNewRecord = true;
							$product->branch_id = $branchId;
							$product->attributes = $product;
							$product->is_global = 0;
							$product->global_products_id = $product->id;
							$product->id = null;
							if (!$product->save()) {
								$product_response['Product_Errors'] = $product->getErrors();
							} else {
								array_push($addProduct, $product->name);
							}
						}
						if (empty($product_response['Product_Errors'])) {
							$productTransaction->commit();
							$product_response['addedProduct'] = $addProduct;
						} else {
							$productTransaction->rollback();
						}
						array_push($log_response, $product_response);
					}
				}
				if ($document_data == 1) {
					$addDoc = array();
					$docTransa = Yii::app()->db->beginTransaction();
					$doc_response = array();
					$DocumentModel = DocumentType::model()->findAllByAttributes(array('is_active' => 1, 'is_global' => 1, 'global_id' => Yii::app()->session['company_id'], 'is_deleted' => 0));
					if (!empty($DocumentModel)) {
						foreach ($DocumentModel as $document) {
							$document->isNewRecord = true;
							$document->branch_id = $branchId;
							$document->attributes = $document;
							$document->is_global = 0;
							$document->global_document_id = $document->id;
							$document->id = null;
							if (!$document->save()) {
								$doc_response['Documents_Errors'] = $document->getErrors();
							} else {
								array_push($addDoc, $document->name);
							}
						}
						if (empty($doc_response['Documents_Errors'])) {
							$docTransa->commit();
							$doc_response['addedDoc'] = $addDoc;
						} else {
							$docTransa->rollback();
						}

						array_push($log_response, $doc_response);
					}
				}
				if ($event_data == 1) {
					$addEvent = array();
					$eventTrans = Yii::app()->db->beginTransaction();
					$event_response = array();
					$EventModel = EventType::model()->findAllByAttributes(array('is_active' => 1, 'is_global' => 1, 'global_id' => Yii::app()->session['company_id'], 'is_deleted' => 0));
					if (!empty($EventModel)) {
						foreach ($EventModel as $event) {
							$event->isNewRecord = true;
							$event->branch_id = $branchId;
							$event->attributes = $event;
							$event->is_global = 0;
							$event->global_event_id = $event->id;
							$event->id = null;
							//$event->global_document_id = $event->id;
							if (!$event->save()) {
								$event_response['Event_Errors'] = $event->getErrors();
							} else {
								array_push($addEvent, $event->name);
							}
						}
						if (empty($event_response['Event_Errors'])) {
							$eventTrans->commit();
							$event_response['addedEvent'] = $addEvent;
						} else {
							$eventTrans->rollback();
						}
						array_push($log_response, $event_response);
					}
				}
				if ($terms_data == 1) {
					$addTerms = array();
					$termsTransa = Yii::app()->db->beginTransaction();
					try {
						$TermModel = Terms::model()->findAllByAttributes(array('is_active' => 1, 'is_global' => 1, 'global_id' => Yii::app()->session['company_id'], 'is_deleted' => 0));
						//if(!empty($TermModel)){
						foreach ($TermModel as $term) {
							$term->isNewRecord = true;
							$term->branch_id = $branchId;
							$term->attributes = $document;
							$term->is_global = 0;
							$term->id = null;
							if (!$term->save()) {

								throw new Exception('Terms data data not added.');
							} else {

								array_push($addTerms, $term->name);
							}
						}
						$termsTransa->commit();
						$terms_response = array();
						$terms_response['addedTerms'] = $addTerms;
						array_push($log_response, $terms_response);
						//}
					} catch (Exception $ex) {
						$termsTransa->rollback();
						throw $ex;
					}
				}
				if ($ageratio_data == 1) {
					$addAge = array();
					$ageTrans = Yii::app()->db->beginTransaction();
					try {
						$AgeratioModel = AgeRatio::model()->findAllByAttributes(array('is_active' => 1, 'is_global' => 1, 'global_id' => Yii::app()->session['company_id'], 'is_deleted' => 0));
						if (!empty($AgeratioModel)) {
							foreach ($AgeratioModel as $ageratio) {
								$ageratio->isNewRecord = true;
								$ageratio->branch_id = $branchId;
								$ageratio->attributes = $event;
								$ageratio->is_global = 0;
								$ageratio->id = null;
								if (!$ageratio->save()) {
									throw new Exception('Age ratio data data not added.');
								} else {

									array_push($addAge, $ageratio->name);
								}
							}
							$ageTrans->commit();
							$age_response = array();
							$age_response['addedAge'] = $addAge;
							array_push($log_response, $age_response);
						}
					} catch (Exception $ex) {
						$ageTrans->rollback();
						throw $ex;
					}
				}
				if ($sessionRates_data == 1) {
					$addSession = array();
					$sessTrans = Yii::app()->db->beginTransaction();
					try {
						$SessionModel = SessionRates::model()->findAllByAttributes(array('is_active' => 1, 'is_global' => 1, 'global_id' => Yii::app()->session['company_id'], 'is_deleted' => 0));
						if (!empty($SessionModel)) {
							foreach ($SessionModel as $session) {
								$session->isNewRecord = true;
								$session->branch_id = $branchId;
								$session->attributes = $session;
								$session->is_global = 0;
								$session->id = null;
								if (!$session->save()) {
									throw new Exception(CHtml::errorSummary($session, "", "", array('class' => 'customErrors')));
								} else {
									array_push($addSession, $session->name);
								}
							}
							$sessTrans->commit();
							$ses_response = array();
							$ses_response['addedAge'] = $addSession;
							array_push($log_response, $ses_response);
						}
					} catch (Exception $ex) {
						$sessTrans->rollback();
						throw $ex;
					}
				}
				echo CJSON::encode($log_response);
			} catch (Exception $ex) {
				echo CJSON::encode(array('message' => $ex->getMessage()));
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid');
		}
	}

	public function actionGenerateUniqueUrl() {
		if (Yii::app()->request->isAjaxRequest) {
			$token = \Firebase\JWT\JWT::encode([
					'branch_id' => Branch::currentBranch()->id,
					'company_id' => Company::currentCompany()->id
					], Yii::app()->params['jwtKey']);
			echo CJSON::encode([
				'status' => 1,
				'token' => Yii::app()->createAbsoluteUrl('enquiries/registerYourChild', ['token' => $token])
			]);
		} else {
			throw new CHttpException(404, 'Your request is not valid');
		}
	}
        
        public function actionForgetImportData() {
            if (Yii::app()->request->isAjaxRequest) {
                if (isset(Yii::app()->session['import_data'])) {
                    unset(Yii::app()->session['import_data']);
                    echo 'session deleted';
                } else {
                    echo 'session not set';
                }
            }
    }

}
