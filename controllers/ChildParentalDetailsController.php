<?php
//Demod
Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseUrl . '/js/min-js/parentImage.min.js?version=1.0.0', CClientScript::POS_END);
Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseUrl . '/js/parentDetail.js?version=0.0.3', CClientScript::POS_END);
Yii::app()->clientScript->registerScript('helpers', '
          eyMan = {
              urls: {
                  createChildParentalDetails: ' . CJSON::encode(Yii::app()->createUrl('childParentalDetails/create', ['child_id' => $_GET['child_id']])) . ' ,
                updateChildParentalDetails: ' . CJSON::encode(Yii::app()->createUrl('childParentalDetails/update', ['child_id' => $_GET['child_id']])) . ' ,
                checkParentEmail: ' . CJSON::encode(Yii::app()->createUrl('childParentalDetails/checkParentEmail', ['child_id' => $_GET['child_id']])) . ' ,
              }
          };
      ', CClientScript::POS_END);

class ChildParentalDetailsController extends eyManController {

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
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate($child_id) {
		$this->pageTitle = 'Create Child | eyMan';
		$this->layout = 'dashboard';
		$totalParents = Yii::app()->params['maxParents'];
		$childPersonalDetails = ChildPersonalDetails::model()->findByPk($child_id);
		if ($childPersonalDetails) {
			$model = new Parents;
			$parentModels = array();
			for ($i = 1; $i <= $totalParents; $i++) {
				$parentModels[$i] = new Parents;
			}
			if (isset($_POST['Parents']) && !empty($_POST["Parents"]) && Yii::app()->request->isAjaxRequest) {
				$transaction = Yii::app()->db->beginTransaction();
				try {
					foreach ($_POST["Parents"] as $key => $value) {
						foreach ($value as $key1 => $value1) {
							$parentModels[$key1 + 1]->$key = $value1;
						}
					}
					foreach ($parentModels as $index => $model) {
						if (isset($model->id) && !empty($model->id)) {
							$model = Parents::model()->findByPk($model->id);
							$model->attributes = $parentModels[$index]->attributes;
							$model->is_bill_payer = $parentModels[$index]->is_bill_payer;
							$model->is_emergency_contact = $parentModels[$index]->is_emergency_contact;
							$model->is_authorised = $parentModels[$index]->is_authorised;
						} else {
							$model->id = NULL;
						}
						if ($index == 1) {
							if (!$model->save()) {
								throw new JsonException($model->getErrors(), $index);
							}
						} else {
							$detailsSet = false;
							foreach ($model->attributes as $attribute) {
								if (isset($attribute) && !empty($attribute)) {
									$detailsSet = TRUE;
									break;
								}
							}
							if ($detailsSet) {
								if (!$model->save()) {
									throw new JsonException($model->getErrors(), $index);
								}
							}
						}
						if (!$model->isNewRecord) {
							$parentChildMapping = new ParentChildMapping;
							$parentChildMapping->parent_id = $model->id;
							$parentChildMapping->child_id = $childPersonalDetails->id;
							$parentChildMapping->order = $index;
							$parentChildMapping->is_bill_payer = $model->is_bill_payer;
							$parentChildMapping->is_authorised = $model->is_authorised;
							$parentChildMapping->is_emergency_contact = $model->is_emergency_contact;
							if (!$parentChildMapping->save()) {
								throw new JsonException($parentChildMapping->getErrors(), $index);
							}
						}
					}
					Yii::app()->user->setFlash('integrationSuccess', 'Parent Details has been successfully updated for child - ' . $childPersonalDetails->name);
					$branchModal = $childPersonalDetails->branch;
					if ($branchModal->is_integration_enabled == 1) {
						if (!empty($branchModal->api_key) && !empty($branchModal->api_password) && !empty($branchModal->api_url)) {
							$childFundingDetails = ChildFundingDetails::model()->countByAttributes(array('child_id' => $childPersonalDetails->id));
							$ch = curl_init($branchModal->api_url . ChildPersonalDetails::CHILDREN_API_PATH);
							$child_data = array(
								'api_key' => $branchModal->api_key,
								'api_password' => $branchModal->api_password,
								'children' => array(
									array(
										'id' => (isset($childPersonalDetails->external_id) && !empty($childPersonalDetails->external_id)) ? $childPersonalDetails->external_id : NULL,
										'first_name' => $childPersonalDetails->first_name,
										'last_name' => $childPersonalDetails->last_name,
										'middle_name' => $childPersonalDetails->middle_name,
										'gender' => ($childPersonalDetails->gender == "MALE") ? "m" : "f",
										'dob' => date('Y-m-d', strtotime($childPersonalDetails->dob)),
										'start_date' => isset($childPersonalDetails->start_date) ? date("Y-m-d", strtotime($childPersonalDetails->start_date)) : NULL,
										'group_external_id' => isset($childPersonalDetails->room_id) ? $childPersonalDetails->room->external_id : NULL,
										'key_person_id' => !empty($childPersonalDetails->key_person) ? $childPersonalDetails->keyPerson->external_id : "",
										'key_person_external_id' => !empty($childPersonalDetails->key_person) ? 'eyman-' . $childPersonalDetails->key_person : NULL,
										'group_name' => isset($childPersonalDetails->room_id) ? ($childPersonalDetails->room->name) : "",
										'religion' => isset($childPersonalDetails->childGeneralDetails->religion_id) ? $childPersonalDetails->childGeneralDetails->religion_id : "",
										'child_notes' => isset($childPersonalDetails->childGeneralDetails->notes) ? $childPersonalDetails->childGeneralDetails->notes : "",
										'allergies' => isset($childPersonalDetails->childGeneralDetails->general_notes) ? $childPersonalDetails->childGeneralDetails->general_notes : "",
										'ethnicity' => isset($childPersonalDetails->childGeneralDetails->ethinicity_id) ? trim($childPersonalDetails->childGeneralDetails->ethinicity->name) : "",
										'dietary_requirements' => isset($childPersonalDetails->childGeneralDetails->dietary_requirements) ? $childPersonalDetails->childGeneralDetails->dietary_requirements : "",
										'language' => isset($childPersonalDetails->childGeneralDetails->first_language) ? trim($childPersonalDetails->childGeneralDetails->first_language) : "",
										'eal' => (strtolower($childPersonalDetails->childGeneralDetails->first_language) == "english") ? true : false,
										'sen' => ($childPersonalDetails->childGeneralDetails->is_sen == 1) ? true : false,
										'funded' => ($childFundingDetails > 0) ? true : false,
										'external_id' => "eyman-" . $childPersonalDetails->id,
										'medical_notes' => isset($childPersonalDetails->childMedicalDetails->medical_notes) ? $childPersonalDetails->childMedicalDetails->medical_notes : "",
										'parents' => $childPersonalDetails->getParentsForEyLogIntegration()
									)
								),
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
								if ($response['response'][0]['status'] == "success") {
									Yii::app()->user->setFlash('integrationSuccess', 'Child Data has been successfully saved to eyLog.');
									if (isset($response['response'][0]['parents'][0])) {
										if ($response['response'][0]['parents'][0]['status'] != "success") {
											Yii::app()->user->setFlash('integrationError', $response['response'][0]['parents'][0]['message']);
										}
									}
									if (isset($response['response'][0]['parents'][1])) {
										if ($response['response'][0]['parents'][1]['status'] != "success") {
											Yii::app()->user->setFlash('integrationError', $response['response'][0]['parents'][1]['message']);
										}
									}
								} else {
									Yii::app()->user->setFlash('integrationError', $response['response'][0]['message']);
								}
							}
						} else {
							Yii::app()->user->setFlash('integrationError', "API key/password/url are not set in Branch Settings");
						}
					}
					$transaction->commit();
					echo CJSON::encode([
						'status' => 1,
						'next_url' => !empty($childPersonalDetails->childGeneralDetails) ? Yii::app()->createAbsoluteUrl('childGeneralDetails/update', ['child_id' => $childPersonalDetails->id, 'general_id' => $childPersonalDetails->childGeneralDetails->id]) : Yii::app()->createAbsoluteUrl('childGeneralDetails/create', ['child_id' => $childPersonalDetails->id]),
						'this_url' => Yii::app()->createAbsoluteUrl('childParentalDetails/update', ['child_id' => $childPersonalDetails->id]),
					]);
					Yii::app()->end();
				} catch (JsonException $ex) {
					$transaction->rollback();
					echo CJSON::encode([
						'status' => 0,
						'error' => $ex->getOptions(),
						'count' => $ex->getCode()
					]);
					Yii::app()->end();
				}
			}
			$this->render('create', array(
				'personalDetails' => $childPersonalDetails,
				'parentModels' => $parentModels,
			));
		} else {
			throw new CHttpException(404, "This page does not exists.");
		}
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($child_id) {
		$this->pageTitle = 'Update Child | eyMan';
		$this->layout = 'dashboard';
		$totalParents = Yii::app()->params['maxParents'];
		$childPersonalDetails = ChildPersonalDetails::model()->findByPk($child_id);
                
		if ($childPersonalDetails) {
			$parentModels = array();
			$parents = $childPersonalDetails->getOrderedParents();
			for ($i = 1; $i <= $totalParents; $i++) {
				if (isset($parents[$i])) {
					$parentModels[$i] = $parents[$i];
				} else {
					$parentModels[$i] = new Parents;
				}
			}
			if (isset($_POST['Parents']) && !empty($_POST["Parents"]) && Yii::app()->request->isAjaxRequest) {
				$transaction = Yii::app()->db->beginTransaction();
				try {
					foreach ($_POST["Parents"] as $key => $value) {
						foreach ($value as $key1 => $value1) {
							$parentModels[$key1 + 1]->$key = $value1;
						}
					}
					foreach ($parentModels as $index => $model) {
						if (isset($model->id) && !empty($model->id)) {
							$model = Parents::model()->findByPk($model->id);
							$model->attributes = $parentModels[$index]->attributes;
							$model->is_bill_payer = $parentModels[$index]->is_bill_payer;
							$model->is_emergency_contact = $parentModels[$index]->is_emergency_contact;
							$model->is_authorised = $parentModels[$index]->is_authorised;
						} else {
							$model->id = NULL;
						}
						if ($index == 1) {
							if (!$model->save()) {
								throw new JsonException($model->getErrors(), ($index));
							}
						} else {
							$detailsSet = false;
							foreach ($model->attributes as $attribute) {
								if (isset($attribute) && !empty($attribute)) {
									$detailsSet = TRUE;
									break;
								}
							}
							if ($detailsSet) {
								if (!$model->save()) {
									throw new JsonException($model->getErrors(), ($index));
								}
							}
						}
						if (!$model->isNewRecord) {
							$checkParentMapping = ParentChildMapping::model()->findByAttributes([
								'parent_id' => $model->id,
								'child_id' => $childPersonalDetails->id,
								'order' => $index
							]);
							if (empty($checkParentMapping)) {
								$parentChildMapping = new ParentChildMapping;
								$parentChildMapping->parent_id = $model->id;
								$parentChildMapping->child_id = $childPersonalDetails->id;
								$parentChildMapping->order = $index;
								$parentChildMapping->is_bill_payer = $model->is_bill_payer;
								$parentChildMapping->is_authorised = $model->is_authorised;
								$parentChildMapping->is_emergency_contact = $model->is_emergency_contact;
								if (!$parentChildMapping->save()) {
									throw new JsonException($parentChildMapping->getErrors(), $index);
								}
							} else {
								$checkParentMapping->is_bill_payer = $model->is_bill_payer;
								$checkParentMapping->is_authorised = $model->is_authorised;
								$checkParentMapping->is_emergency_contact = $model->is_emergency_contact;
								if (!$checkParentMapping->save()) {
									throw new JsonException($checkParentMapping->getErrors(), $index);
								}
							}
						}
					}
					if (isset($_POST['deletedParents']) && !empty($_POST['deletedParents'])) {
						$deletedParents = explode(",", $_POST['deletedParents']);
						if (!empty($deletedParents)) {
							foreach ($deletedParents as $deletedParent) {
								ParentChildMapping::model()->updateAll(['is_deleted' => 1], 'parent_id = :parent_id AND child_id = :child_id', [':parent_id' => $deletedParent, ':child_id' => $childPersonalDetails->id]);
							}
						}
					}
					Yii::app()->user->setFlash('integrationSuccess', 'Parent Details has been successfully updated for child - ' . $childPersonalDetails->name);
					$branchModal = $childPersonalDetails->branch;
					if ($branchModal->is_integration_enabled == 1) {
						if (!empty($branchModal->api_key) && !empty($branchModal->api_password) && !empty($branchModal->api_url)) {
							$childFundingDetails = ChildFundingDetails::model()->countByAttributes(array('child_id' => $childPersonalDetails->id));
							$ch = curl_init($branchModal->api_url . ChildPersonalDetails::CHILDREN_API_PATH);
							$child_data = array(
								'api_key' => $branchModal->api_key,
								'api_password' => $branchModal->api_password,
								'children' => array(
									array(
										'id' => (isset($childPersonalDetails->external_id) && !empty($childPersonalDetails->external_id)) ? $childPersonalDetails->external_id : NULL,
										'first_name' => $childPersonalDetails->first_name,
										'last_name' => $childPersonalDetails->last_name,
										'middle_name' => $childPersonalDetails->middle_name,
										'gender' => ($childPersonalDetails->gender == "MALE") ? "m" : "f",
										'dob' => date('Y-m-d', strtotime($childPersonalDetails->dob)),
										'start_date' => isset($childPersonalDetails->start_date) ? date("Y-m-d", strtotime($childPersonalDetails->start_date)) : NULL,
										'group_external_id' => isset($childPersonalDetails->room_id) ? $childPersonalDetails->room->external_id : NULL,
										'key_person_id' => !empty($childPersonalDetails->key_person) ? $childPersonalDetails->keyPerson->external_id : "",
										'key_person_external_id' => !empty($childPersonalDetails->key_person) ? 'eyman-' . $childPersonalDetails->key_person : NULL,
										'group_name' => isset($childPersonalDetails->room_id) ? ($childPersonalDetails->room->name) : "",
										'religion' => isset($childPersonalDetails->childGeneralDetails->religion_id) ? $childPersonalDetails->childGeneralDetails->religion_id : "",
										'child_notes' => isset($childPersonalDetails->childGeneralDetails->notes) ? $childPersonalDetails->childGeneralDetails->notes : "",
										'allergies' => isset($childPersonalDetails->childGeneralDetails->general_notes) ? $childPersonalDetails->childGeneralDetails->general_notes : "",
										'ethnicity' => isset($childPersonalDetails->childGeneralDetails->ethinicity_id) ? trim($childPersonalDetails->childGeneralDetails->ethinicity->name) : "",
										'dietary_requirements' => isset($childPersonalDetails->childGeneralDetails->dietary_requirements) ? $childPersonalDetails->childGeneralDetails->dietary_requirements : "",
										'language' => isset($childPersonalDetails->childGeneralDetails->first_language) ? trim($childPersonalDetails->childGeneralDetails->first_language) : "",
										'eal' => (strtolower($childPersonalDetails->childGeneralDetails->first_language) == "english") ? true : false,
										'sen' => ($childPersonalDetails->childGeneralDetails->is_sen == 1) ? true : false,
										'funded' => ($childFundingDetails > 0) ? true : false,
										'external_id' => "eyman-" . $childPersonalDetails->id,
										'medical_notes' => isset($childPersonalDetails->childMedicalDetails->medical_notes) ? $childPersonalDetails->childMedicalDetails->medical_notes : "",
										'parents' => $childPersonalDetails->getParentsForEyLogIntegration()
									)
								),
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
								if ($response['response'][0]['status'] == "success") {
									Yii::app()->user->setFlash('integrationSuccess', 'Child Data has been successfully saved to eyLog.');
									if (isset($response['response'][0]['parents'][0])) {
										if ($response['response'][0]['parents'][0]['status'] != "success") {
											Yii::app()->user->setFlash('integrationError', $response['response'][0]['parents'][0]['message']);
										}
									}
									if (isset($response['response'][0]['parents'][1])) {
										if ($response['response'][0]['parents'][1]['status'] != "success") {
											Yii::app()->user->setFlash('integrationError', $response['response'][0]['parents'][1]['message']);
										}
									}
								} else {
									Yii::app()->user->setFlash('integrationError', $response['response'][0]['message']);
								}
							}
						} else {
							Yii::app()->user->setFlash('integrationError', "API key/password/url are not set in Branch Settings");
						}
					}
					$transaction->commit();
					echo CJSON::encode([
						'status' => 1,
						'next_url' => !empty($childPersonalDetails->childGeneralDetails) ? Yii::app()->createAbsoluteUrl('childGeneralDetails/update', ['child_id' => $childPersonalDetails->id, 'general_id' => $childPersonalDetails->childGeneralDetails->id]) : Yii::app()->createAbsoluteUrl('childGeneralDetails/create', ['child_id' => $childPersonalDetails->id]),
						'this_url' => Yii::app()->createAbsoluteUrl('childParentalDetails/update', ['child_id' => $childPersonalDetails->id]),
					]);
					Yii::app()->end();
				} catch (JsonException $ex) {
					$transaction->rollback();
					echo CJSON::encode([
						'status' => 0,
						'error' => $ex->getOptions(),
						'count' => $ex->getCode()
					]);
					Yii::app()->end();
				}
			}
			$this->render('update', array(
				'personalDetails' => $childPersonalDetails,
				'parentModels' => $parentModels,
			));
		} else {
			throw new CHttpException(404, "This page does not exists.");
		}
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return ChildParentalDetails the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id) {
		$model = Parents::model()->findByPk($id);
		if ($model === null)
			throw new CHttpException(404, 'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param ChildParentalDetails $model the model to be validated
	 */
	protected function performAjaxValidation($model) {
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'child-parental-details-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	public function actionAddGocardless($id) {
		$model = $this->loadModel($id);
		$gcCustomerClient = customFunctions::getCustomerGoCardlessClient();
		if (!$gcCustomerClient) {
			throw new CHttpException(500, 'Direct Debit Client account does not exist.');
		}
		try {
			$sessionToken = time() . "-" . rand(1, 1000);
			$redirectFlow = $gcCustomerClient->redirectFlows()->create(array(
				"params" => array(
					"description" => Company::currentCompany()->name,
					"session_token" => $sessionToken,
					"success_redirect_url" => $this->createAbsoluteUrl('/site/goCardlessSuccess', array('id' => $model->id)),
					// Optionally, prefill customer details on the payment page
					"prefilled_customer" => array(
						"given_name" => $model->first_name,
						"family_name" => $model->last_name,
						"email" => $model->email,
						"address_line1" => $model->address_1,
						"postal_code" => $model->postcode,
					)
				)
			));
			if ($redirectFlow->id != null) {
				$model->gocardless_customer_id = $redirectFlow->id;
				$model->gocardless_session_token = $sessionToken;
				if ($model->save()) {
					echo $redirectFlow->redirect_url;
					//$this->redirect($redirectFlow->redirect_url);
				} else {
					throw new Exception('Direct Debit could not be connected.');
				}
			} else {
				throw new Exception('Direct Debit could not be connected.');
			}
		} catch (Exception $e) {
			throw new CHttpException(500, 'Direct Debit could not be connected.');
		}
	}

	public function actionRemoveGocardless($id) {
		if (Parents::model()->updateByPk($id, ['gocardless_customer' => NULL, 'gocardless_mandate' => NULL])) {
			Yii::app()->user->setFlash('integrationSuccess', "Direct Debit account disconnected successfully.");
			$this->redirect(array('childParentalDetails/update', 'parent_id' => $id));
		} else {
			Yii::app()->user->setFlash('integrationError', "Failed to disconnect Direct Debit account.");
			$this->redirect(array('childParentalDetails/update', 'parent_id' => $id));
		}
	}

	public function actionCheckParentEmail() {
		if (isset($_POST['email']) && !empty($_POST["email"]) && Yii::app()->request->isAjaxRequest) {
			$parentModel = Parents::model()->findByAttributes(['email' => $_POST['email']]);
			if (!empty($parentModel)) {
				echo CJSON::encode([
					'status' => 1,
					'parentModel' => $parentModel
				]);
			} else {
				echo CJSON::encode([
					'status' => 0
				]);
			}
			Yii::app()->end();
		} else {
			throw new CHttpException(404, "This page does not exists.");
		}
	}

}
