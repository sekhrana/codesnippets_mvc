<?php

Yii::app()->clientScript->registerScript('helpers', '
          eyMan = {
              urls: {
                  monthlyInvoiceAmount: ' . CJSON::encode(Yii::app()->createUrl("childInvoice/invoiceMonthlyAmount")) . ',
				  getTags: ' . CJSON::encode(Yii::app()->createUrl("tags/getTags")) . ',
				  addTagToChild: ' . CJSON::encode(Yii::app()->createUrl("tags/addTagToChild")) . ',
				  deleteTagFromChild: ' . CJSON::encode(Yii::app()->createUrl("tags/deleteTagFromChild")) . ',
                                  toggleChildStatus: ' . CJSON::encode(Yii::app()->createUrl("childPersonalDetails/toggleChildStatus")) . ',
                                  getRoomStaff: ' . CJSON::encode(Yii::app()->createUrl("childPersonalDetails/getRoomStaff")) . ',
              }
          };
      ', CClientScript::POS_END);

class ChildPersonalDetailsController extends eyManController {

	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout = '//layouts/dashboard';
	public $step;
	public $imageName;

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

	public function saveUploadImage($imageName) {
		return true;
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate() {
		$this->pageTitle = "Create Child | eyMan";
		$this->layout = "dashboard";
		$model = new ChildPersonalDetails;
		$invoiceSettingModel = InvoiceSetting::model()->findByAttributes(array('branch_id' => Yii::app()->session['branch_id']));
		$model->child_urn = $model->childUrn();
		if (Yii::app()->request->getParam('enrollSibling') != NULL) {
			$siblingModel = ChildPersonalDetails::model()->findByPk(Yii::app()->request->getParam('enrollSibling'));
			$model->address_1 = $siblingModel->address_1;
			$model->address_2 = $siblingModel->address_2;
			$model->address_3 = $siblingModel->address_3;
			$model->postcode = $siblingModel->postcode;
		}
		$this->performAjaxValidation($model);
		if (isset($_POST['ChildPersonalDetails']) && isset($_POST['Save']) && !isset($_POST['Next'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model->attributes = $_POST['ChildPersonalDetails'];
				$model->branch_id = Yii::app()->session['branch_id'];
				if (Yii::app()->request->getParam('enrollSibling') != NULL) {
					$model->sibling_id = Yii::app()->request->getParam('enrollSibling');
				}
				if (!$model->is_funding) {
					$model->funded_hours = 0.00;
				}
				$uploadedFile = CUploadedFile::getInstance($model, 'profile_photo');
				if (!$model->validate()) {
					throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
				}
				if ($uploadedFile) {
					$model->file_name = time() . '_' . uniqid() . '.' . $uploadedFile->extensionName;
					$profile_image = new EasyImage($uploadedFile->getTempName());
					$thumb_image = new EasyImage($uploadedFile->getTempName());
					$profile_image->resize($_POST['original_img_width'], $_POST['original_img_height'])->crop($_POST['img_width'], $_POST['img_height'], $_POST['offset_x'], $_POST['offset_y']);
					$thumb_image->resize($_POST['original_img_width'], $_POST['original_img_height'])->crop($_POST['img_width'], $_POST['img_height'], $_POST['offset_x'], $_POST['offset_y'])->resize(70, 71);
					$model->profile_photo_raw = $profile_image;
					$model->profile_photo_thumb_raw = $thumb_image;
					$model->uploadProfilePhoto();
					if (!empty($model->file_name)) {
						$base64string = base64_encode(file_get_contents(GlobalPreferences::getSslUrl() . $model->profile_photo));
						$model->profile_photo_integration = implode(',', array(pathinfo($model->profile_photo, PATHINFO_EXTENSION), $base64string));
					}
				}

				if ($model->save()) {
					$branchModal = $model->branch;
					if ($branchModal->is_integration_enabled == 1) {
						if (!empty($branchModal->api_key) && !empty($branchModal->api_password) && !empty($branchModal->api_url)) {
							$ch = curl_init($branchModal->api_url . ChildPersonalDetails::CHILDREN_API_PATH);
							$child_data = array(
								'api_key' => $branchModal->api_key,
								'api_password' => $branchModal->api_password,
								'children' => array(
									array(
										'first_name' => $model->first_name,
										'last_name' => $model->last_name,
										'middle_name' => $model->middle_name,
										'gender' => ($model->gender == "MALE") ? "m" : "f",
										'dob' => date('Y-m-d', strtotime($model->dob)),
										'start_date' => isset($model->start_date) ? date("Y-m-d", strtotime($model->start_date)) : NULL,
										'key_person_id' => !empty($model->key_person) ? $model->keyPerson->external_id : "",
										'key_person_external_id' => !empty($model->key_person) ? 'eyman-' . $model->key_person : NULL,
										'group_name' => isset($model->room_id) ? ($model->room->name) : "",
										'external_id' => "eyman-" . $model->id,
										'photo' => $model->profile_photo_integration,
										'group_external_id' => $model->room->external_id
									)
								)
							);
							$child_data = json_encode($child_data);
							curl_setopt_array($ch, array(
								CURLOPT_FOLLOWLOCATION => 1,
								CURLOPT_RETURNTRANSFER => true,
								CURLOPT_POST => 1,
								CURLOPT_POSTFIELDS => $child_data,
								CURLOPT_HEADER => 0,
								CURLOPT_USERAGENT => "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)",
								CURLOPT_SSL_VERIFYPEER => false,
							));
							$response = curl_exec($ch);
							$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
							if (curl_errno($ch)) {
								Yii::app()->user->setFlash('integrationError', 'Curl Error - ' . curl_error($ch));
								curl_close($ch);
							} else {
								curl_close($ch);
								$response = json_decode($response, TRUE);
								if ($response['response'][0]['status'] == 'failure' && $response['status'] == 'failure') {
									Yii::app()->user->setFlash('integrationError', $response['response'][0]['message']);
								}
								if ($response['response'][0]['message'] == "Updated") {
									Yii::app()->user->setFlash('integrationError', "Child With same details already exists in eyLog");
								}
								if ($response['response'][0]['status'] == 'success' && $response['response'][0]['message'] == 'Added') {
									Yii::app()->user->setFlash('integrationSuccess', "Data has been successfully updated on eyLog.");
									$childModel = ChildPersonalDetails::model()->findByPk($model->id);
									$childModel->external_id = $response['response'][0]['id'];
									if (!$childModel->save()) {
										Yii::app()->user->setFlash('error', 'External reference id not updated on eyMan.');
									}
								}
								if ($response['response'][0]['message'] != "Updated" && $response['response'][0]['message'] != "Added") {
									Yii::app()->user->setFlash('integrationError', $response['response'][0]['message']);
								}
							}
						} else {
							Yii::app()->user->setFlash('integrationError', "API key/password/url are not set in Branch Settings");
						}
					}

					if (Yii::app()->request->getParam('enrollSibling') != NULL) {
						if (!empty($siblingModel->getOrderedParents())) {
							foreach($siblingModel->getOrderedParents() as $order => $parentModel){
								$parentChildMapping = new ParentChildMapping;
								$parentChildMapping->child_id = $model->id;
								$parentChildMapping->parent_id = $parentModel->id;
								$parentChildMapping->is_bill_payer = $parentModel->is_bill_payer;
								$parentChildMapping->is_emergency_contact = $parentModel->is_emergency_contact;
								$parentChildMapping->is_bill_payer = $parentModel->is_bill_payer;
								$parentChildMapping->order = $order;
								$parentChildMapping->save();
							}
						}
					}
					$transaction->commit();
					$this->redirect(array('childPersonalDetails/update', 'child_id' => $model->id));
				} else {
					throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				Yii::app()->user->setFlash('error', $ex->getMessage());
				$this->refresh();
			}
		}

		if (isset($_POST['ChildPersonalDetails']) && isset($_POST['Next']) && !isset($_POST['Save'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model->attributes = $_POST['ChildPersonalDetails'];
				$model->branch_id = Yii::app()->session['branch_id'];
				if (Yii::app()->request->getParam('enrollSibling') != NULL) {
					$model->sibling_id = Yii::app()->request->getParam('enrollSibling');
				}
				if (!$model->is_funding) {
					$model->funded_hours = 0.00;
				}
				$uploadedFile = CUploadedFile::getInstance($model, 'profile_photo');
				if (!$model->validate()) {
					throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
				}
				if ($uploadedFile) {
					$model->file_name = time() . '_' . uniqid() . '.' . $uploadedFile->extensionName;
					$profile_image = new EasyImage($uploadedFile->getTempName());
					$thumb_image = new EasyImage($uploadedFile->getTempName());
					$profile_image->resize($_POST['original_img_width'], $_POST['original_img_height'])->crop($_POST['img_width'], $_POST['img_height'], $_POST['offset_x'], $_POST['offset_y']);
					$thumb_image->resize($_POST['original_img_width'], $_POST['original_img_height'])->crop($_POST['img_width'], $_POST['img_height'], $_POST['offset_x'], $_POST['offset_y'])->resize(70, 71);
					$model->profile_photo_raw = $profile_image;
					$model->profile_photo_thumb_raw = $thumb_image;
					$model->uploadProfilePhoto();
					if (!empty($model->file_name)) {
						$base64string = base64_encode(file_get_contents(GlobalPreferences::getSslUrl() . $model->profile_photo));
						$model->profile_photo_integration = implode(',', array(pathinfo($model->profile_photo, PATHINFO_EXTENSION), $base64string));
					}
				}
				if ($model->save()) {
					$branchModal = $model->branch;
					if ($branchModal->is_integration_enabled == 1) {
						if (!empty($branchModal->api_key) && !empty($branchModal->api_password) && !empty($branchModal->api_url)) {
							$ch = curl_init($branchModal->api_url . ChildPersonalDetails::CHILDREN_API_PATH);
							$child_data = array(
								'api_key' => $branchModal->api_key,
								'api_password' => $branchModal->api_password,
								'children' => array(
									array(
										'first_name' => $model->first_name,
										'last_name' => $model->last_name,
										'middle_name' => $model->middle_name,
										'gender' => ($model->gender == "MALE") ? "m" : "f",
										'dob' => date('Y-m-d', strtotime($model->dob)),
										'start_date' => isset($model->start_date) ? date("Y-m-d", strtotime($model->start_date)) : NULL,
										'key_person_id' => !empty($model->key_person) ? $model->keyPerson->external_id : "",
										'key_person_external_id' => !empty($model->key_person) ? 'eyman-' . $model->key_person : NULL,
										'group_name' => isset($model->room_id) ? ($model->room->name) : "",
										'external_id' => "eyman-" . $model->id,
										'photo' => $model->profile_photo_integration,
										'group_external_id' => $model->room->external_id
									)
								)
							);
							$child_data = json_encode($child_data);
							curl_setopt_array($ch, array(
								CURLOPT_FOLLOWLOCATION => 1,
								CURLOPT_RETURNTRANSFER => true,
								CURLOPT_POST => 1,
								CURLOPT_POSTFIELDS => $child_data,
								CURLOPT_HEADER => 0,
								CURLOPT_USERAGENT => "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)",
								CURLOPT_SSL_VERIFYPEER => false,
							));
							$response = curl_exec($ch);
							$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
							if (curl_errno($ch)) {
								Yii::app()->user->setFlash('integrationError', 'Curl Error - ' . curl_error($ch));
								curl_close($ch);
							} else {
								curl_close($ch);
								$response = json_decode($response, TRUE);
								if ($response['response'][0]['status'] == 'failure' && $response['status'] == 'failure') {
									Yii::app()->user->setFlash('integrationError', $response['response'][0]['message']);
								}

								if ($response['response'][0]['message'] == "Updated") {
									Yii::app()->user->setFlash('integrationError', "Child With same details already exists in eyLog");
								}
								if ($response['response'][0]['message'] != "Updated" && $response['response'][0]['message'] != "Added") {
									Yii::app()->user->setFlash('integrationError', $response['response'][0]['message']);
								}
								if ($response['response'][0]['status'] == 'success' && $response['response'][0]['message'] == 'Added') {
									Yii::app()->user->setFlash('integrationSuccess', "Data has been successfully updated on eyLog.");
									$childModel = ChildPersonalDetails::model()->findByPk($model->id);
									$childModel->external_id = $response['response'][0]['id'];
									if (!$childModel->save()) {
										Yii::app()->user->setFlash('error', 'External reference id not updated on eyMan.');
									}
								}
							}
						} else {
							Yii::app()->user->setFlash('integrationError', "API key/password/url are not set in Branch Settings");
						}
					}
					if (Yii::app()->request->getParam('enrollSibling') != NULL) {
						if (!empty($siblingModel->getOrderedParents())) {
							foreach($siblingModel->getOrderedParents() as $order => $parentModel){
								$parentChildMapping = new ParentChildMapping;
								$parentChildMapping->child_id = $model->id;
								$parentChildMapping->parent_id = $parentModel->id;
								$parentChildMapping->is_bill_payer = $parentModel->is_bill_payer;
								$parentChildMapping->is_emergency_contact = $parentModel->is_emergency_contact;
								$parentChildMapping->is_bill_payer = $parentModel->is_bill_payer;
								$parentChildMapping->order = $order;
								$parentChildMapping->save();
							}
						}
					}
					$transaction->commit();
					if (empty($model->parents)) {
						$this->redirect(array('/childParentalDetails/create', 'child_id' => $model->id));
					} else {
						$this->redirect(array('/childParentalDetails/update', 'child_id' => $model->id));
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
		$this->render('create', array(
			'model' => $model,
			'invoiceSettingModel' => $invoiceSettingModel
		));
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($child_id) {
		$this->pageTitle = "Update | eyMan";
		$this->layout = "dashboard";
		$model = ChildPersonalDetails::model()->with(['tags:deleted'])->findByPk($child_id);
		$model->child_urn = $model->childUrn();
		$invoiceSettingModel = InvoiceSetting::model()->findByAttributes(array('branch_id' => Yii::app()->session['branch_id']));
		$this->performAjaxValidation($model);
		$previousRoom = $model->room_id;
		$childPerPicture = '';
		if (isset($_POST['ChildPersonalDetails']) && isset($_POST['Next']) && !isset($_POST['Update'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model->attributes = $_POST['ChildPersonalDetails'];
				if (!$model->is_funding) {
					$model->funded_hours = 0.00;
				}
				$uploadedFile = CUploadedFile::getInstance($model, 'profile_photo');
				if (!$model->validate()) {
					throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
				}
				if ($uploadedFile) {
					$model->file_name = time() . '_' . uniqid() . '.' . $uploadedFile->extensionName;
					$profile_image = new EasyImage($uploadedFile->getTempName());
					$thumb_image = new EasyImage($uploadedFile->getTempName());
					$profile_image->resize($_POST['original_img_width'], $_POST['original_img_height'])->crop($_POST['img_width'], $_POST['img_height'], $_POST['offset_x'], $_POST['offset_y']);
					$thumb_image->resize($_POST['original_img_width'], $_POST['original_img_height'])->crop($_POST['img_width'], $_POST['img_height'], $_POST['offset_x'], $_POST['offset_y'])->resize(70, 71);
					$model->profile_photo_raw = $profile_image;
					$model->profile_photo_thumb_raw = $thumb_image;
					$model->uploadProfilePhoto();
					if (!empty($model->file_name)) {
						$base64string = base64_encode(file_get_contents(GlobalPreferences::getSslUrl() . $model->profile_photo));
						$model->profile_photo_integration = implode(',', array(pathinfo($model->profile_photo, PATHINFO_EXTENSION), $base64string));
					}
				}
				if ($model->save()) {
					/** Block for changing the room of sessions  starts here* */
					if ($previousRoom != $model->room_id) {
						$criteria = new CDbCriteria();
						$criteria->condition = "((:effective_date BETWEEN start_date and finish_date) OR (start_date >= :effective_date AND finish_date >= :effective_date)) AND is_deleted = 0 AND child_id = :child_id AND room_id = :room_id";
						$criteria->params = array(':effective_date' => date("Y-m-d", strtotime($_POST['ChildPersonalDetails']['effective_date'])), ':child_id' => $model->id, ":room_id" => $previousRoom);
						$childBookingsModel = ChildBookings::model()->findAll($criteria);
						if (!empty($childBookingsModel)) {
							foreach ($childBookingsModel as $bookings) {
								$childBookingDetailsModel = ChildBookingsDetails::model()->findByAttributes(array('booking_id' => $bookings->id));
								if (strtotime($bookings->start_date) == strtotime($bookings->finish_date)) {
									$bookings->room_id = $model->room_id;
									$bookings->save();
								} else if (strtotime($bookings->start_date) >= strtotime(date("Y-m-d", strtotime($_POST['ChildPersonalDetails']['effective_date'])))) {
									$bookings->room_id = $model->room_id;
									$bookings->save();
								} else {
									$actualFinishDate = $bookings->finish_date;
									$bookings->finish_date = date('Y-m-d', strtotime('-1 day', strtotime($_POST['ChildPersonalDetails']['effective_date'])));
									$bookings->save();
									$newBookingModel = new ChildBookings();
									$newBookingModel->attributes = $bookings->attributes;
									$newBookingModel->start_date = date("Y-m-d", strtotime($_POST['ChildPersonalDetails']['effective_date']));
									$newBookingModel->finish_date = $actualFinishDate;
									$newBookingModel->room_id = $model->room_id;
									if ($newBookingModel->save()) {
										$newBookingDetailsModel = new ChildBookingsDetails();
										$newBookingDetailsModel->attributes = $childBookingDetailsModel->attributes;
										$newBookingDetailsModel->booking_id = $newBookingModel->id;
										$newBookingDetailsModel->save();
									}
								}
							}
						}
					}
					/** Block for changing the room of sessions  ends here* */
					$branchModal = $model->branch;
					$childFundingDetails = ChildFundingDetails::model()->countByAttributes(array('child_id' => $child_id));
					$childGeneralDetails = ChildGeneralDetails::model()->findByAttributes(['child_id' => $child_id]);
					$medical = ChildMedicalDetails::model()->findByPk($model->childMedicalDetails->id);
					if ($branchModal->is_integration_enabled == 1) {
						if (!empty($branchModal->api_key) && !empty($branchModal->api_password) && !empty($branchModal->api_url)) {
							$ch = curl_init($branchModal->api_url . ChildPersonalDetails::CHILDREN_API_PATH);
							$child_data = array(
								'api_key' => $branchModal->api_key,
								'api_password' => $branchModal->api_password,
								'children' => array(
									array(
										'id' => (isset($model->external_id) && !empty($model->external_id)) ? $model->external_id : NULL,
										'first_name' => $model->first_name,
										'last_name' => $model->last_name,
										'middle_name' => $model->middle_name,
										'gender' => ($model->gender == "MALE") ? "m" : "f",
										'dob' => date('Y-m-d', strtotime($model->dob)),
										'start_date' => isset($model->start_date) ? date("Y-m-d", strtotime($model->start_date)) : NULL,
										'group_external_id' => isset($childPersonalDetails->room_id) ? $childPersonalDetails->room->external_id : NULL,
										'key_person_external_id' => !empty($model->key_person) ? 'eyman-' . $model->key_person : "",
										'key_person_id' => !empty($model->key_person) ? $model->keyPerson->external_id : "",
										'group_name' => isset($model->room_id) ? ($model->room->name) : "",
										'external_id' => "eyman-" . $model->id,
										'photo' => $model->profile_photo_integration,
										'group_external_id' => $model->room->external_id,
										'religion' => isset($childGeneralDetails->religion_id) ? $childGeneralDetails->religion_id : "",
										'medical_notes' => isset($medical->medical_notes) ? $medical->medical_notes : "",
										'child_notes' => isset($childGeneralDetails->notes) ? $childGeneralDetails->notes : "",
										'allergies' => isset($childGeneralDetails->general_notes) ? $childGeneralDetails->general_notes : "",
										'language' => isset($childGeneralDetails->first_language) ? $childGeneralDetails->first_language : "",
										'ethnicity' => isset($childGeneralDetails->ethinicity_id) ? trim($childGeneralDetails->ethinicity->name) : "",
										'dietary_requirements' => isset($childGeneralDetails->dietary_requirements) ? $childGeneralDetails->dietary_requirements : "",
										'eal' => (strtolower($childGeneralDetails->first_language) == "english") ? true : false,
										'sen' => ($childGeneralDetails->is_sen == 1) ? true : false,
										'funded' => ($childFundingDetails > 0) ? true : false,
										'parents' => $model->getParentsForEyLogIntegration()
									)
								)
							);
							$child_data = json_encode($child_data);
							curl_setopt_array($ch, array(
								CURLOPT_FOLLOWLOCATION => 1,
								CURLOPT_RETURNTRANSFER => true,
								CURLOPT_POST => 1,
								CURLOPT_POSTFIELDS => $child_data,
								CURLOPT_HEADER => 0,
								CURLOPT_USERAGENT => "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)",
								CURLOPT_SSL_VERIFYPEER => false,
							));
							$response = curl_exec($ch);
							$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
							if (curl_errno($ch)) {
								Yii::app()->user->setFlash('integrationError', 'Curl Error - ' . curl_error($ch));
								curl_close($ch);
							} else {
								curl_close($ch);
								$response = json_decode($response, TRUE);
								if ($response['response'][0]['message'] != "Updated" && $response['response'][0]['message'] != "Added") {
									Yii::app()->user->setFlash('integrationError', $response['response'][0]['message']);
								}

								if ($response['response'][0]['status'] == 'success' && $response['response'][0]['message'] == 'Updated') {
									Yii::app()->user->setFlash('integrationSuccess', "Data has been successfully updated on eyLog.");
									$childModel = ChildPersonalDetails::model()->findByPk($model->id);
									$childModel->external_id = $response['response'][0]['id'];
									if (!$childModel->save()) {
										Yii::app()->user->setFlash('error', 'External reference id not updated on eyMan.');
									}
								}
							}
						} else {
							Yii::app()->user->setFlash('integrationError', "API key/password/url are not set in Branch Settings");
						}
					}
					$transaction->commit();
					if (!empty($model->parents))
						$this->redirect(array('childParentalDetails/update', 'child_id' => $child_id));

					if (empty($model->parents))
						$this->redirect(array('childParentalDetails/create', 'child_id' => $model->id));
				} else {
					throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				Yii::app()->user->setFlash('error', $ex->getMessage());
				$this->refresh();
			}
		}

		if (isset($_POST['ChildPersonalDetails']) && isset($_POST['Update']) && !isset($_POST['Next'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$oldImageName = $model->profile_photo;
				$model->attributes = $_POST['ChildPersonalDetails'];
				if (!$model->is_funding) {
					$model->funded_hours = 0.00;
				}
				$uploadedFile = CUploadedFile::getInstance($model, 'profile_photo');
				if (!$model->validate()) {
					throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
				}
				if ($uploadedFile) {
					$model->file_name = time() . '_' . uniqid() . '.' . $uploadedFile->extensionName;
					$profile_image = new EasyImage($uploadedFile->getTempName());
					$thumb_image = new EasyImage($uploadedFile->getTempName());
					$profile_image->resize($_POST['original_img_width'], $_POST['original_img_height'])->crop($_POST['img_width'], $_POST['img_height'], $_POST['offset_x'], $_POST['offset_y']);
					$thumb_image->resize($_POST['original_img_width'], $_POST['original_img_height'])->crop($_POST['img_width'], $_POST['img_height'], $_POST['offset_x'], $_POST['offset_y'])->resize(70, 71);
					$model->profile_photo_raw = $profile_image;
					$model->profile_photo_thumb_raw = $thumb_image;
					$model->uploadProfilePhoto();
					if (!empty($model->file_name)) {
						$base64string = base64_encode(file_get_contents(GlobalPreferences::getSslUrl() . $model->profile_photo));
						$model->profile_photo_integration = implode(',', array(pathinfo($model->profile_photo, PATHINFO_EXTENSION), $base64string));
					}
				}
				if ($model->save()) {
					/** Block for changing the room of sessions  starts here* */
					if ($previousRoom != $model->room_id) {
						$criteria = new CDbCriteria();
						$criteria->condition = "((:effective_date BETWEEN start_date and finish_date) OR (start_date >= :effective_date AND finish_date >= :effective_date)) AND is_deleted = 0 AND child_id = :child_id AND room_id = :room_id";
						$criteria->params = array(':effective_date' => date("Y-m-d", strtotime($_POST['ChildPersonalDetails']['effective_date'])), ':child_id' => $model->id, ":room_id" => $previousRoom);
						$childBookingsModel = ChildBookings::model()->findAll($criteria);
						if (!empty($childBookingsModel)) {
							foreach ($childBookingsModel as $bookings) {
								$childBookingDetailsModel = ChildBookingsDetails::model()->findByAttributes(array('booking_id' => $bookings->id));
								if (strtotime($bookings->start_date) == strtotime($bookings->finish_date)) {
									$bookings->room_id = $model->room_id;
									$bookings->save();
								} else if (strtotime($bookings->start_date) >= strtotime(date("Y-m-d", strtotime($_POST['ChildPersonalDetails']['effective_date'])))) {
									$bookings->room_id = $model->room_id;
									$bookings->save();
								} else {
									$actualFinishDate = $bookings->finish_date;
									$bookings->finish_date = date('Y-m-d', strtotime('-1 day', strtotime($_POST['ChildPersonalDetails']['effective_date'])));
									$bookings->save();
									$newBookingModel = new ChildBookings();
									$newBookingModel->attributes = $bookings->attributes;
									$newBookingModel->start_date = date("Y-m-d", strtotime($_POST['ChildPersonalDetails']['effective_date']));
									$newBookingModel->finish_date = $actualFinishDate;
									$newBookingModel->room_id = $model->room_id;
									if ($newBookingModel->save()) {
										$newBookingDetailsModel = new ChildBookingsDetails();
										$newBookingDetailsModel->attributes = $childBookingDetailsModel->attributes;
										$newBookingDetailsModel->booking_id = $newBookingModel->id;
										$newBookingDetailsModel->save();
									}
								}
							}
						}
					}
					/** Block for changing the room of sessions  ends here* */
					$branchModal = $model->branch;
					$childFundingDetails = ChildFundingDetails::model()->countByAttributes(array('child_id' => $child_id));
					$childGeneralDetails = ChildGeneralDetails::model()->findByAttributes(['child_id' => $child_id]);
					$medical = ChildMedicalDetails::model()->findByPk($model->childMedicalDetails->id);
					if ($branchModal->is_integration_enabled == 1) {
						if (!empty($branchModal->api_key) && !empty($branchModal->api_password) && !empty($branchModal->api_url)) {
							$ch = curl_init($branchModal->api_url . ChildPersonalDetails::CHILDREN_API_PATH);
							$child_data = array(
								'api_key' => $branchModal->api_key,
								'api_password' => $branchModal->api_password,
								'children' => array(
									array(
										'id' => (isset($model->external_id) && !empty($model->external_id)) ? $model->external_id : NULL,
										'first_name' => $model->first_name,
										'last_name' => $model->last_name,
										'middle_name' => $model->middle_name,
										'gender' => ($model->gender == "MALE") ? "m" : "f",
										'dob' => date('Y-m-d', strtotime($model->dob)),
										'start_date' => isset($model->start_date) ? date("Y-m-d", strtotime($model->start_date)) : NULL,
										'group_external_id' => isset($childPersonalDetails->room_id) ? $childPersonalDetails->room->external_id : NULL,
										'key_person_id' => !empty($model->key_person) ? $model->keyPerson->external_id : "",
										'key_person_external_id' => !empty($model->key_person) ? 'eyman-' . $model->key_person : NULL,
										'group_name' => isset($model->room_id) ? ($model->room->name) : "",
										'external_id' => "eyman-" . $model->id,
										'photo' => $model->profile_photo_integration,
										'group_external_id' => $model->room->external_id,
										'religion' => isset($childGeneralDetails->religion_id) ? $childGeneralDetails->religion_id : "",
										'medical_notes' => isset($medical->medical_notes) ? $medical->medical_notes : "",
										'child_notes' => isset($childGeneralDetails->notes) ? $childGeneralDetails->notes : "",
										'allergies' => isset($childGeneralDetails->general_notes) ? $childGeneralDetails->general_notes : "",
										'language' => isset($childGeneralDetails->first_language) ? $childGeneralDetails->first_language : "",
										'ethnicity' => isset($childGeneralDetails->ethinicity_id) ? trim($childGeneralDetails->ethinicity->name) : "",
										'dietary_requirements' => isset($childGeneralDetails->dietary_requirements) ? $childGeneralDetails->dietary_requirements : "",
										'eal' => (strtolower($childGeneralDetails->first_language) == "english") ? true : false,
										'sen' => ($childGeneralDetails->is_sen == 1) ? true : false,
										'funded' => ($childFundingDetails > 0) ? true : false,
										'parents' => $model->getParentsForEyLogIntegration()
									)
								)
							);
							$child_data = json_encode($child_data);
							curl_setopt_array($ch, array(
								CURLOPT_FOLLOWLOCATION => 1,
								CURLOPT_RETURNTRANSFER => true,
								CURLOPT_POST => 1,
								CURLOPT_POSTFIELDS => $child_data,
								CURLOPT_HEADER => 0,
								CURLOPT_USERAGENT => "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)",
								CURLOPT_SSL_VERIFYPEER => false,
							));
							$response = curl_exec($ch);
							$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
							if (curl_errno($ch)) {
								Yii::app()->user->setFlash('integrationError', 'Curl Error - ' . curl_error($ch));
								curl_close($ch);
							} else {
								curl_close($ch);
								$response = json_decode($response, TRUE);
								if ($response['response'][0]['status'] == "failure") {
									Yii::app()->user->setFlash('integrationError', $response['response'][0]['message']);
								}
								if ($response['response'][0]['message'] != "Updated" && $response['response'][0]['message'] != "Added") {
									Yii::app()->user->setFlash('integrationError', $response['response'][0]['message']);
								}
								if ($response['response'][0]['status'] == 'success' && $response['response'][0]['message'] == 'Updated') {
									Yii::app()->user->setFlash('integrationSuccess', "Data has been successfully updated on eyLog.");
									$model->external_id = $response['response'][0]['id'];
									if (!$model->save()) {
										Yii::app()->user->setFlash('error', 'External reference id not updated on eyMan.');
									}
								}
							}
						} else {
							Yii::app()->user->setFlash('integrationError', "API key/password/url are not set in Branch Settings");
						}
					}
					$transaction->commit();
					$this->redirect(array('update', 'child_id' => $child_id));
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
			'model' => $model,
			'invoiceSettingModel' => $invoiceSettingModel
		));
	}

	public function actionDelete() {
		if (Yii::app()->request->isAjaxRequest) {
			$response = array('status' => '1');
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model = $this->loadModel($_POST['id']);
				$model->is_deleted = 1;
				$criteria = new CDbCriteria();
				$criteria->condition = "finish_date >= :today and child_id = :child_id";
				$criteria->params = array(':today' => date('Y-m-d'), ':child_id' => $_POST['id']);
				$childBookingsModel = ChildBookings::model()->findAll($criteria);
				foreach ($childBookingsModel as $bookings) {
					$yesterday = new DateTime();
					$yesterday = $yesterday->modify('-1 day');
					$yesterday = $yesterday->format('Y-m-d');
					if (strtotime($bookings->start_date) > strtotime(date('Y-m-d'))) {
						$bookings->is_deleted = 1;
					} else {
						$bookings->finish_date = $yesterday;
					}
					$bookings->save();
				}
				if (ChildPersonalDetails::model()->updateByPk($model->id, ['is_deleted' => 1])) {
					$branchModal = Branch::model()->findByPk(Yii::app()->session['branch_id']);
					if ($branchModal->is_integration_enabled == 1) {
						if (!empty($branchModal->api_key) && !empty($branchModal->api_password) && !empty($branchModal->api_url)) {
							$ch = curl_init($branchModal->api_url . ChildPersonalDetails::CHILDREN_API_PATH . "/" . $model->external_id);
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
				} else {
					$response = array('status' => '0');
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
		if (Yii::app()->request->isAjaxRequest) {
			$model = ChildPersonalDetails::model()->findByPk($_POST['id']);
			if ($model->is_active == 1) {
				$active = 0;
				$status = 1;
				$message = "Child has been successfully inactive.";
			} else {
				$active = 1;
				$status = 1;
				$message = "Child has been successfully active.";
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
		$dataProvider = new CActiveDataProvider('ChildPersonalDetails');
		$criteria = new CDbCriteria();
		$criteria->addCondition('branch_id = :branch_id');
		$criteria->params = array(':branch_id' => Yii::app()->session['branch_id']);
		$criteria->order = "trim(t.first_name), t.room_id";
		if (isset($_GET['sortBy']) && !empty($_GET['sortBy'])) {
			$criteria->order = "trim(t." . $_GET['sortBy'] . ")";
		}
		$dataProvider->criteria = $criteria;
		if (isset($_GET['query'])) {
			$_GET['query'] = trim($_GET['query']);
			$criteria = new CDbCriteria();
			$criteria->addSearchCondition('t.first_name', $_GET['query'], true);
			$criteria->addSearchCondition('t.last_name', $_GET['query'], true, 'OR');
			$criteria->addSearchCondition('t.middle_name', $_GET['query'], true, 'OR');
			$criteria->addSearchCondition('concat(t.first_name," ", t.middle_name, " ", t.last_name)', $_GET['query'], true, 'OR');
			$criteria->addSearchCondition('concat(t.first_name, " ", t.last_name)', $_GET['query'], true, 'OR');
			$criteria->addSearchCondition('t.gender', $_GET['query'], true, 'OR');
			$criteria->with = array('keyPerson', 'room');
			$criteria->addSearchCondition('room.name', $_GET['query'], true, 'OR');
			$criteria->addSearchCondition('keyPerson.first_name', $_GET['query'], true, 'OR');
			$criteria->addSearchCondition('t.branch_id', Yii::app()->session['branch_id'], true, 'AND');
			$criteria->order = "trim(t.first_name), t.room_id";
			$dataProvider->criteria = $criteria;
		}
		$dataProvider->pagination->pageSize = 15;
		$this->render('index', array(
			'dataProvider' => $dataProvider,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return ChildPersonalDetails the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id) {
		$model = ChildPersonalDetails::model()->findByPk($id);
		if ($model === null)
			throw new CHttpException(404, 'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param ChildPersonalDetails $model the model to be validated
	 */
	protected function performAjaxValidation($model) {
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'child-personal-details-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	public function actionGetRoom() {
		$rooms = Room::model()->findAll('branch_id=:branch_id', array('branch_id' => (int) $_POST['ChildPersonalDetails']['branch_id']));
		$rooms = CHtml::listData($rooms, 'id', 'name');
		foreach ($rooms as $id => $name) {
			echo CHtml::tag('option', array('value' => $id), CHtml::encode($name), true);
		}
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionImport() {
		$this->pageTitle = "Import Children | eyMan";
		$this->layout = "importChild";
		if (isset(Yii::app()->session['branch_id'])) {
			$model = new ChildPersonalDetails;
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
					$unset_columns = array('id', 'sibling_id', 'preffered_session', 'client_id', 'key_person', 'branch_id', 'is_deleted'
						, 'profile_photo', 'link_to_staff', 'is_term_time', 'is_funding', 'funded_hours', 'booking_type', 'latitude', 'longitude', 'is_active', 'monthly_invoice_amount', 'last_updated', 'last_updated_by', 'external_id');
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
					$handle = fopen("$csv->tempName", 'r');
					if ($handle !== FALSE) {
						$rowCounter = 0;
						$csv_header1 = array();
						$csv_header2 = array();
						while (($rowData = fgetcsv($handle, 0, ",")) && $rowData[0]) {
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
						}
						if ($csv_header2[0] != '') {
							Yii::app()->session['csv_header'] = $csv_header2;
						}
						Yii::app()->session['csv_data'] = $assocData;
					} else {
						Yii::app()->user->setFlash('error', "No data found in CSV");
						$this->render('import_form', array(
							'model' => $model,
						));
						Yii::app()->end();
					}
					Yii::app()->session['step'] = "personal";
					$this->render('import_step_2', array(
						'model' => $model,
					));
				} else {
					Yii::app()->user->setFlash('error', "Please upload a  valid CSV file");
					$this->render('import_form', array(
						'model' => $model,
					));
				}
			}

			if (isset($_POST['save_child_profile'])) {
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
				$personal_data_error = array();
				foreach ($personal_data as $key => $value) {
					foreach ($value as $key1 => $value1) {
						$model->isNewRecord = TRUE;
						$model->id = NULL;
						$key1 = trim($key1);
						$value1 = trim($value1);
						if ($value1 == 'null' || $value1 == 'NULL') {
							$value1 = "";
						}
						if ($key1 == 'gender') {
							$value1 = strtolower($value1);
							if ($value1 == 1 || $value1 == 2 || $value1 == 'm' || $value1 == 'f' ||
								$value1 == 'male' || $value1 == 'female') {
								if ($value1 == 1 || $value1 == "1")
									$value1 = 'Female';
								if ($value1 == 2 || $value1 == "2")
									$value1 = 'Male';
								if ($value1 == 'M' || $value1 == 'm')
									$value1 = 'Male';
								if ($value1 == 'F' || $value1 == 'f')
									$value1 = 'Female';
							}else {
								$personal_data_error[] = $key;
								continue;
							}
							$value1 = strtoupper($value1);
						}

						if ($key1 == "dob" || $key1 == "enroll_date" || $key1 == "start_date" || $key1 == "leave_date") {
							if ($value1 == '00-00-0000' || $value1 == '0000-00-00' || $value1 == '') {
								$value1 = NULL;
							} else {
								if (strpos($value1, '@') != false) {
									$personal_data_error[] = $key;
									continue;
								}
								if (strpos($value1, '/') == false && strpos($value1, '-') == false && strpos($value1, '.') == false) {
									$personal_data_error[] = $key;
									continue;
								}
								$value1 = str_replace('/', '-', $value1);
								$value1 = str_replace('.', '-', $value1);
								$value1 = date('d-m-Y', strtotime($value1));
							}
						}
						if ($key1 == "room_id") {
							$roomModel = Room::model()->findByAttributes(array('name' => trim($value1)));
							if (!empty($roomModel)) {
								$model->room_id = $roomModel->id;
							} else {
								$model->room_id = NULL;
							}
						}
						if ($key1 == "p1_title" || $key1 == "p2_title" || $key1 == "p3_title" || $key1 == "p4_title" ||
							$key1 == "p5_title" || $key1 == "p6_title") {
							$value1 = ucfirst($value1) . '.';
						}
						if ($key1 != "room_id") {
							$model->$key1 = $value1;
						}
						$model->branch_id = Yii::app()->session['branch_id'];
					}
					if ($model->validate()) {
						$model->save();
						$inserted_ids[] = $model->id;
					} else {
						$error_message[] = "Child Personal details cannot be imported,There is some error at row:-" . $count_row;
						$errors[] = $model->getErrors();
						$personal_data_error[] = $key;
					}
					$count_row++;
				}

				if (isset($error_message)) {
					Yii::app()->session['error_message'] = $error_message;
					Yii::app()->session['errors'] = $errors;
					foreach ($personal_data_error as $key3 => $value3) {
						unset($next_tab[$value3]);
					}
				}
				if (isset($new_first_row))
					Yii::app()->session['first_row'] = $new_first_row;
				if (isset($new_header))
					Yii::app()->session['csv_header'] = $new_header;

				Yii::app()->session['csv_data'] = $next_tab;
				Yii::app()->session['inserted_child_ids'] = $inserted_ids;
				$parental_tbl_columns = ChildParentalDetails::model()->getTableSchema()->getColumnNames();
				$unset_columns = array('id', 'child_id', 'is_deleted', 'p1_profile_photo', 'p2_profile_photo', 'p3_profile_photo'
					, 'p4_profile_photo', 'p5_profile_photo', 'p6_profile_photo', 'p1_is_authorised', 'p2_is_authorised', 'p3_is_authorised'
					, 'p4_is_authorised', 'p5_is_authorised', 'p6_is_authorised',
					'p1_is_bill_payer', 'p2_is_bill_payer', 'p3_is_bill_payer', 'p4_is_bill_payer', 'p5_is_bill_payer'
					, 'p6_is_bill_payer');

				$parentModel = new ChildParentalDetails();
				foreach ($parental_tbl_columns as $key => $value) {
					if ($parentModel->isAttributeRequired($value)) {
						$parental_tbl_columns[$key] = $value . "1";
					} else {
						$parental_tbl_columns[$key] = $value . "0";
					}
					if (in_array($value, $unset_columns)) {
						unset($parental_tbl_columns[$key]);
					}
				}
				Yii::app()->session['tbl_columns'] = $parental_tbl_columns;
				Yii::app()->session['step'] = 'parent';
				$this->render('import_step_2', array(
					'model' => $model,
				));
			}

			if (isset($_POST['save_child_parent'])) {
				$old_csv_data = Yii::app()->session['csv_data'];
				$parental_data = array();
				foreach (Yii::app()->session['csv_data'] as $key => $value) {
					$i = 0;
					foreach ($value as $key1 => $value1) {
						if (isset($_POST['column_selected_' . $i]) && $_POST['column_selected_' . $i] != "") {
							$parental_data[$key][$_POST['column_selected_' . $i]] = $value1;
						} else {
							$general_tab[$key][$i] = $value1;
							$general_header_keys = array_keys($general_tab[$key]);
						}
						$i++;
					}
				}

				if (!empty($general_header_keys)) {
					foreach ($general_header_keys as $key => $value) {
						$new_header_general[] = Yii::app()->session['csv_header'][$value];
						$new_first_row_general = Yii::app()->session['first_row'][$value];
					}
				}

				$count_row = 1;
				$inserted_ids = array();
				$data_model = new ChildParentalDetails();
				$parental_data_error = array();
				foreach ($parental_data as $key => $value) {
					foreach ($value as $key1 => $value1) {
						$key1 = trim($key1);
						$value1 = trim($value1);
						$data_model->isNewRecord = TRUE;
						$data_model->id = NULL;
						$data_model->child_id = Yii::app()->session['inserted_child_ids'][$key];
						if ($value1 == 'null' || $value1 == 'NULL') {
							$value1 = NULL;
						}
						if ($key1 == "p1_dob" || $key1 == "p2_dob") {
							if ($value1 == '00-00-0000' || $value1 == '0000-00-00' || $value1 == '') {
								$value1 = NULL;
							} else {
								if (strpos($value1, '/') == false && strpos($value1, '-') == false && strpos($value1, '.') == false) {
									$parental_data_error[] = $key;
									continue;
								}
								$value1 = str_replace('/', '-', $value1);
								$value1 = str_replace('.', '-', $value1);
								$value1 = date('d-m-Y', strtotime($value1));
							}
						}
						if ($key1 == "p1_mobile_phone" || $key1 == "p2_mobile_phone" || $key1 == "p3_mobile_phone" || $key1 == "p4_mobile_phone" || $key1 == "p5_mobile_phone" || $key1 == "p6_mobile_phone" || $key1 == "p1_home_phone" || $key1 == "p2_home_phone" || $key1 == "p3_home_phone" || $key1 == "p4_home_phone" || $key1 == "p5_home_phone" || $key1 == "p6_home_phone") {
							if (!is_numeric($value1)) {
								$parental_data_error[] = $key;
								continue;
							}
						}

						$data_model->$key1 = $value1;
					}
					if ($data_model->validate()) {
						$data_model->save();
						$inserted_ids[] = $data_model->id;
					} else {
						$error_message[] = "Child Parental details cannot be imported,There is some error at row:-" . $count_row;
						$errors[] = $data_model->getErrors();
						$parental_data_error[] = $key;
					}
					$count_row++;
				}

				if (isset($error_message)) {
					Yii::app()->session['step'] = 'parent';
					Yii::app()->session['error_message'] = $error_message;
					Yii::app()->session['errors'] = $errors;
				}

				if (isset($new_first_row_general))
					Yii::app()->session['first_row'] = $new_first_row_general;
				if (isset($new_header_general))
					Yii::app()->session['csv_header'] = $new_header_general;

				Yii::app()->session['csv_data'] = $general_tab;

				$general_tbl_columns = ChildGeneralDetails::model()->getTableSchema()->getColumnNames();
				$unset_columns = array('id', 'child_id', 'is_subsidy', 'is_outings_on_foot', 'is_published_content', 'is_sun_cream', 'is_face_paint',
					'is_social_networking', 'is_press_releases', 'is_nappy_cream', 'is_promotional_material', 'is_caf', 'is_allow_photos', 'is_allow_video',
					'is_child_in_nappies', 'is_sen');

				$generalModel = new ChildGeneralDetails();
				foreach ($general_tbl_columns as $key => $value) {
					if ($generalModel->isAttributeRequired($value)) {
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
				$this->render('import_step_2', array(
					'model' => $model,
				));
			}

			if (isset($_POST['save_child_general'])) {
				$old_csv_data = Yii::app()->session['csv_data'];
				$general_data = array();
				foreach (Yii::app()->session['csv_data'] as $key => $value) {
					$i = 0;
					foreach ($value as $key1 => $value1) {
						if (isset($_POST['column_selected_' . $i]) && $_POST['column_selected_' . $i] != "") {
							$general_data[$key][$_POST['column_selected_' . $i]] = $value1;
						} else {
							$medical_tab[$key][$i] = $value1;
							$medical_header_keys = array_keys($medical_tab[$key]);
						}
						$i++;
					}
				}
				if (!empty($medical_header_keys)) {
					foreach ($medical_header_keys as $key => $value) {
						$new_header_medical[] = Yii::app()->session['csv_header'][$value];
						$new_first_row_medical = Yii::app()->session['first_row'][$value];
					}
				}

				$count_row = 1;
				$inserted_ids = array();
				$data_model = new ChildGeneralDetails();
				$general_data_error = array();
				foreach ($general_data as $key => $value) {
					foreach ($value as $key1 => $value1) {
						$key1 = trim($key1);
						$value1 = trim($value1);
						$data_model->isNewRecord = TRUE;
						$data_model->id = NULL;
						$data_model->child_id = Yii::app()->session['inserted_child_ids'][$key];
						if ($value1 == 'null' || $value1 == 'NULL') {
							$value1 = NULL;
						}
						if ($key1 == "payment_method_id") {
							$childPaymentModel = PickPaymentMethod::model()->findByAttributes(array('name' => trim($value1)));
							if (!empty($childPaymentModel)) {
								$value1 = $childPaymentModel->id;
							} else {
								$value1 = NULL;
							}
						}
						if ($key1 == "payment_terms_id") {
							$childPaymentTermsModel = PickPaymentTerms::model()->findByAttributes(array('name' => trim($value1)));
							if (!empty($childPaymentTermsModel)) {
								$value1 = $childPaymentTermsModel->id;
							} else {
								$value1 = NULL;
							}
						}

						if ($key1 == "ethinicity_id") {
							$childEthinicityModel = PickEthinicity::model()->findByAttributes(array('name' => trim($value1)));
							if (!empty($childEthinicityModel)) {
								$value1 = $childEthinicityModel->id;
							} else {
								$value1 = NULL;
							}
						}

						if ($key1 == "religion_id") {
							$childReligionModel = PickReligion::model()->findByAttributes(array('name' => trim($value1)));
							if (!empty($childReligionModel)) {
								$value1 = $childReligionModel->id;
							} else {
								$value1 = NULL;
							}
						}

						if ($key1 == "vulnerability_id") {
							$childVulnerabilityModel = PickVulnerability::model()->findByAttributes(array('name' => trim($value1)));
							if (!empty($childVulnerabilityModel)) {
								$value1 = $childVulnerabilityModel->id;
							} else {
								$value1 = NULL;
							}
						}

						$data_model->$key1 = $value1;
					}
					if ($data_model->validate()) {
						$data_model->save();
						$inserted_ids[] = $data_model->id;
					} else {
						$error_message[] = "Child general details cannot be imported,There is some error at row:-" . $count_row;
						$errors[] = $data_model->getErrors();
						$general_data_error[] = $key;
					}
					$count_row++;
				}
				if (isset($error_message)) {
					Yii::app()->session['step'] = 'medical';
					Yii::app()->session['error_message'] = $error_message;
					Yii::app()->session['errors'] = $errors;
				}

				if (isset($new_first_row_medical))
					Yii::app()->session['first_row'] = $new_first_row_medical;
				if (isset($new_header_medical))
					Yii::app()->session['csv_header'] = $new_header_medical;

				Yii::app()->session['csv_data'] = $medical_tab;

				$medical_tbl_columns = ChildMedicalDetails::model()->getTableSchema()->getColumnNames();
				$unset_columns = array('id', 'child_id');

				$medicalModel = new ChildMedicalDetails();
				foreach ($medical_tbl_columns as $key => $value) {
					if ($medicalModel->isAttributeRequired($value)) {
						$medical_tbl_columns[$key] = $value . "1";
					} else {
						$medical_tbl_columns[$key] = $value . "0";
					}
					if (in_array($value, $unset_columns)) {
						unset($medical_tbl_columns[$key]);
					}
				}
				Yii::app()->session['tbl_columns'] = $medical_tbl_columns;
				Yii::app()->session['step'] = 'medical';
				$this->render('import_step_2', array(
					'model' => $model,
				));
			}

			if (isset($_POST['save_child_medical'])) {
				$old_csv_data = Yii::app()->session['csv_data'];
				$medical_data = array();
				foreach (Yii::app()->session['csv_data'] as $key => $value) {
					$i = 0;
					foreach ($value as $key1 => $value1) {
						if (isset($_POST['column_selected_' . $i]) && $_POST['column_selected_' . $i] != "") {
							$medical_data[$key][$_POST['column_selected_' . $i]] = $value1;
						}
						$i++;
					}
				}

				$count_row = 1;
				$inserted_ids = array();
				$data_model = new ChildMedicalDetails();
				$medical_data_error = array();
				foreach ($medical_data as $key => $value) {
					foreach ($value as $key1 => $value1) {
						$key1 = trim($key1);
						$value1 = trim($value1);
						$data_model->isNewRecord = TRUE;
						$data_model->id = NULL;
						$data_model->child_id = Yii::app()->session['inserted_child_ids'][$key];
						if ($value1 == 'null' || $value1 == 'NULL') {
							$value1 = NULL;
						}

						$data_model->$key1 = $value1;
					}
					if ($data_model->validate()) {
						$data_model->save();
						$inserted_ids[] = $data_model->id;
					} else {
						$error_message[] = "Child medical details cannot be imported,There is some error at row:-" . $count_row;
						$errors[] = $data_model->getErrors();
						$medical_data_error[] = $key;
					}
					$count_row++;
				}
				if (isset($error_message)) {
					Yii::app()->session['step'] = 'medical';
					Yii::app()->session['error_message'] = $error_message;
					Yii::app()->session['errors'] = $errors;
				}

				$this->redirect('childPersonalDetails/import');
			}

			if (!isset($_POST['upload_csv']) && !isset($_POST['save_child_profile']) && !isset($_POST['save_child_parent'])) {
				$this->render('import_form', array(
					'model' => $model,
				));
			}
		} else {
			throw new CHttpException(404, 'Please create branch in the system');
		}
	}

	public function actionMap() {
		Yii::app()->clientScript->registerScriptFile('https://maps.googleapis.com/maps/api/js?key=AIzaSyCcv_FsDJCo3UlJ1JMBPUMvgRUuo8JW_t8', CClientScript::POS_END);
		Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseUrl . '/js/childMaps.js?version=1.0.1', CClientScript::POS_END);
		$this->render('map');
	}

	public function actionGetMapData() {
		if (Yii::app()->request->isPostRequest) {
			$data = [];
			$model = ChildPersonalDetails::model()->findAll([
				'condition' => 'branch_id = :branch_id AND latitude is not NULL AND longitude is not NULL',
				'params' => [':branch_id' => Branch::currentBranch()->id]
			]);
			if (!empty($model)) {
				foreach ($model as $child) {
					$child->profile_photo = customFunctions::showImage($child->id);
					$child->first_name = $child->name;
					$data[] = $child->attributes;
				}
			}
			$base_url = Yii::app()->request->getBaseUrl(TRUE);
			echo CJSON::encode(array('status' => 1, 'data' => $data, 'base_url' => $base_url));
		} else {
			throw new CHttpException(404, 'Your request is not valid');
		}
	}

	public function actionToggleStatus() {
		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1)) {
			if ($_POST['status'] == 1) {
				Yii::app()->user->setState('skipActiveScopeChild', TRUE);
			} else {
				Yii::app()->user->setState('skipActiveScopeChild', FALSE);
			}
			echo CJSON::encode(array('status' => 1));
		} else {
			throw new CHttpException(404, 'Your request is not Valid.');
		}
	}

	public function actionToggleChildStatus() {
		if (Yii::app()->request->isAjaxRequest) {
			$model = ChildPersonalDetails::model()->findByPk($_POST['child_id']);
			$model->is_active = ($model->is_active == 1) ? 0 : 1;
			$transaction = Yii::app()->db->beginTransaction();
			try {
				//$childPersonalDetail = ChildPersonalDetails::model()->updateByPk($_POST['child_id'], array(
				//	'is_active' => $model->is_active
				//));
                                $bool = ($model->is_active == 1)? true : false;
				if ($model->save($bool)) {
                                        $status = ($model->is_active == 1) ? "active." : "inactive.";
					$criteria = new CDbCriteria();
					$criteria->condition = "finish_date >= :today and child_id = :child_id";
					$criteria->params = array(':today' => date('Y-m-d'), ':child_id' => $_POST['child_id']);
					$childBookingsModel = ChildBookings::model()->findAll($criteria);
					foreach ($childBookingsModel as $bookings) {
						$yesterday = new DateTime();
						$yesterday = $yesterday->modify('-1 day');
						$yesterday = $yesterday->format('Y-m-d');
						if (strtotime($bookings->start_date) > strtotime(date('Y-m-d'))) {
							$bookings->is_deleted = 1;
						} else {
							$bookings->finish_date = $yesterday;
						}
						if (!$bookings->save()) {
							throw new CHttpException("Some problem occur while deleting future booking.");
						}
					}

					$transaction->commit();
					$flashMessageSuccess = $model->name . " has been successfully marked as " . $status;
					Yii::app()->user->setFlash("success", $flashMessageSuccess);
					echo CJSON::encode([
						'status' => 1,
						'url' => $this->createAbsoluteUrl('childPersonalDetails/index')
					]);
					Yii::app()->end();
				} else {
//                                    echo "<pre>";
//                                    print_r($model->getErrors());
//                                    die("mdead");
					$flashMessageError = "Their seems some problem marking the child " . $status;
					Yii::app()->user->setFlash("success", $flashMessageError);
					echo CJSON::encode([
						'status' => 0,
                                                'message' => $model->getErrors(),
						'url' => $this->createAbsoluteUrl('childPersonalDetails/update?child_id='.$model->id)
					]);
					Yii::app()->end();
				}
			} catch (Exception $ex) {
                            //print_r($ex->getMessage());
                            //die("dead");
				$transaction->rollback();
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionGetRoomStaff() {
		if (Yii::app()->request->isAjaxRequest) {
//			$model = StaffPersonalDetails::model()->findAllByAttributes(array('room_id' => $_POST['room_id'] , 'is_deleted' => 0));
			$model = TbHtml::listData(StaffPersonalDetails::model()->findAllByAttributes(array('branch_id' => Yii::app()->session['branch_id'], 'room_id' => $_POST['room_id'], 'is_deleted' => 0)), 'id', 'name');
			if ($model) {
				echo CJSON::encode([
					'status' => 1,
					'data' => $model
				]);
				Yii::app()->end();
			} else {
				echo CJSON::encode([
					'status' => 0
				]);
				Yii::app()->end();
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionUploadToCdn() {
		ini_set('max_execution_time' , -1);
		ini_set("memory_limit", "6192M");
		$childModel = ChildPersonalDetailsNds::model()->findAll(['condition' => 'is_active = 0 OR is_deleted = 1']);
		if (!empty($childModel)) {
			foreach ($childModel as $child) {
				if (isset($child->profile_photo) && !empty($child->profile_photo)) {
					$photo = Yii::app()->basePath . '/../uploaded_images/' . $child->profile_photo;
					$thumb = Yii::app()->basePath . '/../uploaded_images/thumbs/' . $child->profile_photo;
					if (file_exists($photo) && file_exists($thumb)) {
						$rackspace = new eyManRackspace();
						$rackspace->uploadObjects([[
							'name' => "/images/children/" . $child->profile_photo,
							'body' => @fopen($photo, 'r+')
							],
							[
								'name' => "/images/children/thumbs/" . $child->profile_photo,
								'body' => @fopen($thumb, 'r+')
							]
						]);
						ChildPersonalDetailsNds::model()->updateByPk($child->id, [
							'profile_photo' => "/images/children/" . $child->profile_photo,
							'profile_photo_thumb' => "/images/children/thumbs/" . $child->profile_photo
						]);
						echo "Image uplaoded for child - " . $child->name . "</br>";
					} else {
						echo "Image not uplaoded for child - " . $child->name . "</br>";
					}
				}
			}
		}

		$staffModel = StaffPersonalDetailsNds::model()->findAll(['condition' => 'is_active = 0 OR is_deleted = 1']);
		if (!empty($staffModel)) {
			foreach ($staffModel as $staff) {
				if (isset($staff->profile_photo) && !empty($staff->profile_photo)) {
					$photo = Yii::app()->basePath . '/../uploaded_images/' . $staff->profile_photo;
					$thumb = Yii::app()->basePath . '/../uploaded_images/thumbs/' . $staff->profile_photo;
					if (file_exists($photo) && file_exists($thumb)) {
						$rackspace = new eyManRackspace();
						$rackspace->uploadObjects([[
							'name' => "/images/staff/" . $staff->profile_photo,
							'body' => @fopen($photo, 'r+')
							],
							[
								'name' => "/images/staff/thumbs/" . $staff->profile_photo,
								'body' => @fopen($thumb, 'r+')
							]
						]);
						StaffPersonalDetailsNds::model()->updateByPk($staff->id, [
							'profile_photo' => "/images/staff/" . $staff->profile_photo,
							'profile_photo_thumb' => "/images/staff/thumbs/" . $staff->profile_photo
						]);
						echo "Image uplaoded for staff - " . $staff->name . "</br>";
					} else {
						echo "Image not uplaoded for staff - " . $staff->name . "</br>";
					}
				}
			}
		}

//		$roomModel = Room::model()->findAll();
//		if (!empty($roomModel)) {
//			foreach ($roomModel as $room) {
//				if (isset($room->logo) && !empty($room->logo)) {
//					$photo = Yii::app()->basePath . '/../uploaded_images/room_logos/' . $room->logo;
//					if (file_exists($photo)) {
//						$rackspace = new eyManRackspace();
//						$rackspace->uploadObjects([[
//							'name' => "/images/room/" . $room->logo,
//							'body' => @fopen($photo, 'r+')
//							]
//						]);
//						Room::model()->updateByPk($room->id, [
//							'logo' => "/images/room/" . $room->logo,
//						]);
//						echo "Image uplaoded for room - " . $room->name . "</br>";
//					} else {
//						echo "Image not uplaoded for room - " . $room->name . "</br>";
//					}
//				}
//			}
//		}
//		$companyModel = Company::model()->findAll();
//		if (!empty($companyModel)) {
//			foreach ($companyModel as $company) {
//				if (isset($company->logo) && !empty($company->logo)) {
//					$photo = Yii::app()->basePath . '/../uploaded_images/company_logos/' . $company->logo;
//					if (file_exists($photo)) {
//						$rackspace = new eyManRackspace();
//						$rackspace->uploadObjects([[
//							'name' => "/images/company/" . $company->logo,
//							'body' => @fopen($photo, 'r+')
//							]
//						]);
//						Company::model()->updateByPk($company->id, [
//							'logo' => "/images/company/" . $company->logo
//						]);
//						echo "Image uplaoded for company - " . $company->name . "</br>";
//					} else {
//						echo "Image not uplaoded for company - " . $company->name . "</br>";
//					}
//				}
//			}
//		}
	}

	public function actionInvoiceHeaderToCdn() {
		ini_set('max_execution_time' , -1);
		ini_set("memory_limit", "6192M");
		$invoiceSettingsModel = InvoiceSetting::model()->findAll();
		if (!empty($invoiceSettingsModel)) {
			foreach ($invoiceSettingsModel as $invoiceSettings) {
				if (isset($invoiceSettings->invoice_pdf_header_image) && !empty($invoiceSettings->invoice_pdf_header_image)) {
					$header = Yii::app()->basePath . '/../uploaded_images/invoice_pdf_header/' . $invoiceSettings->invoice_pdf_header_image;
					if (file_exists($header) && file_exists($header)) {
						$rackspace = new eyManRackspace();
						$rackspace->uploadObjects([[
							'name' => "/images/invoice/" . $invoiceSettings->invoice_pdf_header_image,
							'body' => @fopen($header, 'r+')
							]
						]);
						InvoiceSetting::model()->updateByPk($invoiceSettings->id, [
							'invoice_pdf_header_image' => "/images/invoice/" . $invoiceSettings->invoice_pdf_header_image,
						]);
						echo "Pdf Header uploaded - " . $invoiceSettings->branch->name . "</br>";
					} else {
						echo "Pdf Header not uploaded - " . $invoiceSettings->branch->name . "</br>";
					}
				}
			}
		}
	}

	public function actionChildDocsToCdn() {
		ini_set('max_execution_time' , -1);
		ini_set("memory_limit", "6192M");
		$childDocuments = ChildDocumentDetails::model()->findAll(['condition' => 'id > 661']);
		if (!empty($childDocuments)) {
			foreach ($childDocuments as $document) {
				if (isset($document->document_1) && !empty($document->document_1)) {
					if (isset($document->childNds->branch->rackspace_container) && !empty($document->childNds->branch->rackspace_container)) {
						$images = Yii::app()->rackspaceconnect->authenticate('eylogdev', '95c27d36226a4db6b76000969656d33f')->get_container($document->childNds->branch->rackspace_container);
						$url = $images->make_public();
						$file = $url . '/' . $document->document_1;
						if (customFunctions::isUrlValid($file)) {
							$rackspace = new eyManRackspace();
							$rackspace->uploadObjects([[
								'name' => "/child_documents/" . $document->document_1,
								'body' => @file_get_contents($file)
								]
							]);
							ChildDocumentDetails::model()->updateByPk($document->id, [
								'document_1' => "/child_documents/" . $document->document_1
							]);
							echo "Child Document uploaded from rackspace- " . $document->document_1 . "</br>";
						} else {
							$file = Yii::app()->basePath . '/../uploaded_images/child_docs/' . $document->document_1;
							if (file_exists($file)) {
								$rackspace = new eyManRackspace();
								$rackspace->uploadObjects([[
									'name' => "/child_documents/" . $document->document_1,
									'body' => @fopen($file, 'r+')
									]
								]);
								ChildDocumentDetails::model()->updateByPk($document->id, [
									'document_1' => "/child_documents/" . $document->document_1
								]);
								echo "Child Document uploaded from server - " . $document->document_1 . "</br>";
							} else {
								echo "Child Document not uploaded from server - " . $document->document_1 . "</br>";
							}
						}
					} else {
						$file = Yii::app()->basePath . '/../uploaded_images/child_docs/' . $document->document_1;
						if (file_exists($file)) {
							$rackspace = new eyManRackspace();
							$rackspace->uploadObjects([[
								'name' => "/child_documents/" . $document->document_1,
								'body' => @fopen($file, 'r+')
								]
							]);
							ChildDocumentDetails::model()->updateByPk($document->id, [
								'document_1' => "/child_documents/" . $document->document_1
							]);
							echo "Child Document uploaded from server- " . $document->document_1 . "</br>";
						} else {
							echo "Child Document not uploaded from server- " . $document->document_1 . "</br>";
						}
					}
				}
			}
		}
	}

	public function actionStaffDocsToCdn() {
		ini_set('max_execution_time' , -1);
		ini_set("memory_limit", "6192M");
		$staffDocuments = StaffDocumentDetails::model()->findAll();
		if (!empty($staffDocuments)) {
			foreach ($staffDocuments as $document) {
				if (isset($document->document_1) && !empty($document->document_1)) {
					if (isset($document->staffNds->branch->rackspace_container) && !empty($document->staffNds->branch->rackspace_container)) {
						$url = $images->make_public();
						$file = $url . '/' . $document->document_1;
						if (customFunctions::isUrlValid($file)) {
							$rackspace = new eyManRackspace();
							$rackspace->uploadObjects([[
								'name' => "/staff_documents/" . $document->document_1,
								'body' => @file_get_contents($file)
								]
							]);
							StaffDocumentDetails::model()->updateByPk($document->id, [
								'document_1' => "/staff_documents/" . $document->document_1
							]);
							echo "Staff Document uploaded - " . $document->document_1 . "</br>";
						} else {
							$file = Yii::app()->basePath . '/../uploaded_images/child_docs/' . $document->document_1;
							if (file_exists($file)) {
								$rackspace = new eyManRackspace();
								$rackspace->uploadObjects([[
									'name' => "/staff_documents/" . $document->document_1,
									'body' => @fopen($file, 'r+')
									]
								]);
								StaffDocumentDetails::model()->updateByPk($document->id, [
									'document_1' => "/staff_documents/" . $document->document_1
								]);
								echo "Staff Document uploaded - " . $document->document_1 . "</br>";
							} else {
								echo "Staff Document not uploaded - " . $document->document_1 . "</br>";
							}
						}
					} else {
						$file = Yii::app()->basePath . '/../uploaded_images/child_docs/' . $document->document_1;
						if (file_exists($file)) {
							$rackspace = new eyManRackspace();
							$rackspace->uploadObjects([[
								'name' => "/staff_documents/" . $document->document_1,
								'body' => @fopen($file, 'r+')
								]
							]);
							StaffDocumentDetails::model()->updateByPk($document->id, [
								'document_1' => "/staff_documents/" . $document->document_1
							]);
							echo "Child Document uploaded - " . $document->document_1 . "</br>";
						} else {
							echo "Child Document not uploaded - " . $document->document_1 . "</br>";
						}
					}
				}
			}
		}
	}

	/*
	 * Function for moving the child from one branch to other
	 */
	public function actionUpdateChildBranch() {
		if (isset($_GET['previousBranch']) && !empty($_GET['previousBranch']) && isset($_GET['newBranch']) && !empty($_GET['newBranch']) && isset($_GET['child_id']) && !empty($_GET['child_id'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$newBranchModel = $_GET['newBranch'];
				$effective_date = date("Y-m-d");
				if (!empty($newBranchModel)) {
					$model = ChildPersonalDetails::model()->findByPk($_GET['child_id']);
					if (!empty($model)) {
						$newChildPersonalDetailsModel = new ChildPersonalDetails;
						$newChildParentalDetailsModel = new ChildParentalDetails;
						$newChildGeneralDetailsModel = new ChildGeneralDetails;
						$newChildMedicalDetailsModel = new ChildMedicalDetails;
						$newChildPersonalDetailsModel->attributes = $model->attributes;
						$newChildPersonalDetailsModel->room_id = NULL;
						$newChildPersonalDetailsModel->key_person = NULL;
						$newChildPersonalDetailsModel->sibling_id = NULL;
						$newChildPersonalDetailsModel->branch_id = $newBranchModel;
						$newChildPersonalDetailsModel->is_active = 1;
						$newChildPersonalDetailsModel->child_urn = NULL;
						if ($newChildPersonalDetailsModel->save()) {
							if (!empty($model->childGeneralDetails)) {
								$newChildGeneralDetailsModel->attributes = $model->childGeneralDetails->attributes;
								$newChildGeneralDetailsModel->child_id = $newChildPersonalDetailsModel->id;
								if (!$newChildGeneralDetailsModel->save()) {
									$transaction->rollback();
									echo CJSON::encode($newChildGeneralDetailsModel->getErrors());
									die("Failed while saving general details ");
								}
							}
							if (!empty($model->childParentalDetails)) {
								$newChildParentalDetailsModel->attributes = $model->childParentalDetails->attributes;
								$newChildParentalDetailsModel->child_id = $newChildPersonalDetailsModel->id;
								if (!$newChildParentalDetailsModel->save()) {
									$transaction->rollback();
									echo CJSON::encode($newChildParentalDetailsModel->getErrors());
									die("Failed while saving parental details ");
								}
							}
							if (!empty($model->childMedicalDetails)) {
								$newChildMedicalDetailsModel->attributes = $model->childMedicalDetails->attributes;
								$newChildMedicalDetailsModel->child_id = $newChildPersonalDetailsModel->id;
								if (!$newChildMedicalDetailsModel->save()) {
									$transaction->rollback();
									echo CJSON::encode($newChildMedicalDetailsModel->getErrors());
									die("Failed while saving mdecal details ");
								}
							}
							$model->leave_date = $effective_date;
							$model->is_active = 0;
							if (!$model->save()) {
								$transaction->rollback();
								echo CJSON::encode($model->getErrors());
							}
							$transaction->commit();
							echo CJSON::encode(array(
								'status' => 1,
								'message' => 'Branch has been succesfully changed.'
							));
						} else {
							$transaction->rollback();
							echo CJSON::encode($newChildPersonalDetailsModel->getErrors());
							die("Failed while saving new child personal details ");
						}
					} else {
						$transaction->rollback();
						echo CJSON::encode(['message' => ['Child is not present in the nursery.']]);
						die();
					}
				} else {
					$transaction->rollback();
					echo CJSON::encode(['message' => ['Branch is not present on the system.']]);
					die();
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				echo CJSON::encode(['message' => ['Their seems to be some problem.']]);
				die();
			}
		} else {
			echo CJSON::encode(array('message' => ['Please select Current/New Branch/Effective Date.']));
			die();
		}
	}

}
