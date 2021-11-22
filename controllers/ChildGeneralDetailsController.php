<?php

//Demo Commit
class ChildGeneralDetailsController extends eyManController {

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
	public function actionView($child_id, $general_id) {
		if ($general_id == 0) {
			$model = new ChildGeneralDetails;
			$this->render('view', array(
				'model' => $model,
			));
		} else {
			$this->render('view', array(
				'model' => $this->loadModel($general_id),
			));
		}
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate($child_id) {

		$this->layout = 'dashboard';
		$this->pageTitle = 'eyMan | General Details';

		if (isset($child_id)) {
			$childPersonalDetails = ChildPersonalDetails::model()->findByPk($child_id);
			if (empty($childPersonalDetails))
				throw new CHttpException(400, "No child found for the above id");
			if (!empty($childPersonalDetails)) {
				$model = new ChildGeneralDetails;
				$child_id = $child_id;
				$this->performAjaxValidation($model);
				if (isset($_POST['ChildGeneralDetails']) && isset($_POST['Save']) && !isset($_POST['Next'])) {
					$transaction = Yii::app()->db->beginTransaction();
					try {
						$model->attributes = $_POST['ChildGeneralDetails'];
						$model->child_id = $child_id;
						if ($model->is_caf == 0) {
							$model->caf_number = NULL;
						}
						if ($model->is_sen == 0) {
							$model->sen_provision_id = NULL;
						}
						if ($model->save()) {
							//Integration API call starts here
							$branchModal = Branch::model()->findByPk(Yii::app()->session[branch_id]);
							$childFundingDetails = ChildFundingDetails::model()->countByAttributes(array('child_id' => $child_id));
							if ($branchModal->is_integration_enabled == 1) {
								if (!empty($branchModal->api_key) && !empty($branchModal->api_password) && !empty($branchModal->api_url)) {
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
												'religion' => isset($model->religion_id) ? $model->religion_id : "",
												'child_notes' => isset($model->notes) ? $model->notes : "",
												'allergies' => isset($model->general_notes) ? $model->general_notes : "",
												'ethnicity' => isset($model->ethinicity_id) ? trim($model->ethinicity->name) : "",
												'dietary_requirements' => isset($model->dietary_requirements) ? $model->dietary_requirements : "",
												'language' => isset($model->first_language) ? trim($model->first_language) : "",
												'eal' => (strtolower($model->first_language) == "english") ? true : false,
												'sen' => ($model->is_sen == 1) ? true : false,
												'funded' => ($childFundingDetails > 0) ? true : false,
												'external_id' => "eyman-" . $childPersonalDetails->id,
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
							$this->redirect(array('update', 'child_id' => $child_id, 'general_id' => $model->id));
						} else {
							throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
						}
					} catch (Exception $ex) {
						$transaction->rollback();
						$model->isNewRecord = true;
						Yii::app()->user->setFlash('error', $ex->getMessage());
						$this->refresh();
						Yii::app()->end();
					}
				}

				if (isset($_POST['ChildGeneralDetails']) && isset($_POST['Next'])) {
					$transaction = Yii::app()->db->beginTransaction();
					try {
						$model->attributes = $_POST['ChildGeneralDetails'];
						$model->child_id = $child_id;
						if ($model->is_caf == 0) {
							$model->caf_number = NULL;
						}
						if ($model->is_sen == 0) {
							$model->sen_provision_id = NULL;
						}
						if ($model->save()) {
							//Integration API call starts here
							$branchModal = Branch::model()->findByPk(Yii::app()->session[branch_id]);
							$childFundingDetails = ChildFundingDetails::model()->countByAttributes(array('child_id' => $child_id));
							if ($branchModal->is_integration_enabled == 1) {
								if (!empty($branchModal->api_key) && !empty($branchModal->api_password) && !empty($branchModal->api_url)) {
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
												'religion' => isset($model->religion_id) ? $model->religion_id : "",
												'child_notes' => isset($model->notes) ? $model->notes : "",
												'allergies' => isset($model->general_notes) ? $model->general_notes : "",
												'ethnicity' => isset($model->ethinicity_id) ? trim($model->ethinicity->name) : "",
												'dietary_requirements' => isset($model->dietary_requirements) ? $model->dietary_requirements : "",
												'language' => isset($model->first_language) ? trim($model->first_language) : "",
												'eal' => (strtolower($model->first_language) == "english") ? true : false,
												'sen' => ($model->is_sen == 1) ? true : false,
												'funded' => ($childFundingDetails > 0) ? true : false,
												'external_id' => "eyman-" . $childPersonalDetails->id,
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
							$medicalDetails = ChildMedicalDetails::model()->findByAttributes(array('child_id' => $model->child_id));
							if (empty($medicalDetails))
								$this->redirect(array('childMedicalDetails/create', 'child_id' => $child_id));

							if (!empty($medicalDetails))
								$this->redirect(array('childMedicalDetails/update', 'child_id' => $child_id, 'medical_id' => $medicalDetails->id));
						} else {
							throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
						}
					} catch (Exception $ex) {
						$transaction->rollback();
						$model->isNewRecord = true;
						Yii::app()->user->setFlash('error', $ex->getMessage());
						$this->refresh();
						Yii::app()->end();
					}
				}
			}
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
	public function actionUpdate($child_id, $general_id) {
		$this->layout = 'dashboard';
		$model = $this->loadModel($general_id);
		$this->performAjaxValidation($model);
		if (isset($_POST['ChildGeneralDetails']) && isset($_POST['Update'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$childPersonalDetails = ChildPersonalDetails::model()->findByPk($child_id);
				if (empty($childPersonalDetails))
					throw new CHttpException(400, "No child found for the above id");
				$model->attributes = $_POST['ChildGeneralDetails'];
				if ($model->is_caf == 0) {
					$model->caf_number = NULL;
				}
				if ($model->is_sen == 0) {
					$model->sen_provision_id = NULL;
				}

				if ($model->save()) {
					//Integration API call starts here
					$branchModal = Branch::model()->findByPk(Yii::app()->session[branch_id]);
					$medical = ChildMedicalDetails::model()->findByPk($childPersonalDetails->childMedicalDetails->id);
					$childFundingDetails = ChildFundingDetails::model()->countByAttributes(array('child_id' => $child_id));
					if ($branchModal->is_integration_enabled == 1) {
						if (!empty($branchModal->api_key) && !empty($branchModal->api_password) && !empty($branchModal->api_url)) {
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
										'religion' => isset($model->religion_id) ? $model->religion_id : "",
										'medical_notes' => isset($medical->medical_notes) ? $medical->medical_notes : "",
										'language' => isset($model->first_language) ? $model->first_language : "",
										'child_notes' => isset($model->notes) ? $model->notes : "",
										'allergies' => isset($model->general_notes) ? $model->general_notes : "",
										'ethnicity' => isset($model->ethinicity_id) ? trim($model->ethinicity->name) : "",
										'dietary_requirements' => isset($model->dietary_requirements) ? $model->dietary_requirements : "",
										'eal' => (strtolower($model->first_language) == "english") ? true : false,
										'sen' => ($model->is_sen == 1) ? true : false,
										'funded' => ($childFundingDetails > 0) ? true : false,
										'external_id' => "eyman-" . $childPersonalDetails->id,
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
					$this->redirect(array('update', 'child_id' => $child_id, 'general_id' => $general_id));
				} else {
					throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				$model->isNewRecord = true;
				Yii::app()->user->setFlash('error', $ex->getMessage());
				$this->refresh();
				Yii::app()->end();
			}
		}

		if (isset($_POST['ChildGeneralDetails']) && isset($_POST['Next'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$childPersonalDetails = ChildPersonalDetails::model()->findByPk($child_id);
				if (empty($childPersonalDetails))
					throw new CHttpException(400, "No child found for the above id");
				$model->attributes = $_POST['ChildGeneralDetails'];
				if ($model->is_caf == 0) {
					$model->caf_number = NULL;
				}
				if ($model->is_sen == 0) {
					$model->sen_provision_id = NULL;
				}
				if ($model->save()) {
					//Integration API call starts here
					$branchModal = Branch::model()->findByPk(Yii::app()->session[branch_id]);
					$medical = ChildMedicalDetails::model()->findByPk($childPersonalDetails->childMedicalDetails->id);
					$childFundingDetails = ChildFundingDetails::model()->countByAttributes(array('child_id' => $child_id));
					if ($branchModal->is_integration_enabled == 1) {
						if (!empty($branchModal->api_key) && !empty($branchModal->api_password) && !empty($branchModal->api_url)) {
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
										'medical_notes' => isset($medical->medical_notes) ? $medical->medical_notes : "",
										'religion' => isset($model->religion_id) ? $model->religion_id : "",
										'child_notes' => isset($model->notes) ? $model->notes : "",
										'allergies' => isset($model->general_notes) ? $model->general_notes : "",
										'language' => isset($model->first_language) ? $model->first_language : "",
										'ethnicity' => isset($model->ethinicity_id) ? trim($model->ethinicity->name) : "",
										'dietary_requirements' => isset($model->dietary_requirements) ? $model->dietary_requirements : "",
										'eal' => (strtolower($model->first_language) == "english") ? true : false,
										'sen' => ($model->is_sen == 1) ? true : false,
										'funded' => ($childFundingDetails > 0) ? true : false,
										'external_id' => "eyman-" . $childPersonalDetails->id,
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
					$medicalDetails = ChildMedicalDetails::model()->findByAttributes(array('child_id' => $child_id));
					if (empty($medicalDetails))
						$this->redirect(array('childMedicalDetails/create', 'child_id' => $child_id));

					if (!empty($medicalDetails))
						$this->redirect(array('childMedicalDetails/update', 'child_id' => $child_id, 'medical_id' => $medicalDetails->id));
				} else {
					throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				$model->isNewRecord = true;
				Yii::app()->user->setFlash('error', $ex->getMessage());
				$this->refresh();
				Yii::app()->end();
			}
		}
		$this->render('update', array(
			'model' => $model,
		));
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
	public function actionIndex() {
		$dataProvider = new CActiveDataProvider('ChildGeneralDetails');
		$this->render('index', array(
			'dataProvider' => $dataProvider,
		));
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin() {
		$model = new ChildGeneralDetails('search');
		$model->unsetAttributes(); // clear any default values
		if (isset($_GET['ChildGeneralDetails']))
			$model->attributes = $_GET['ChildGeneralDetails'];

		$this->render('admin', array(
			'model' => $model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return ChildGeneralDetails the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id) {
		$model = ChildGeneralDetails::model()->findByPk($id);
		if ($model === null)
			throw new CHttpException(404, 'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param ChildGeneralDetails $model the model to be validated
	 */
	protected function performAjaxValidation($model) {
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'child-general-details-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

}
