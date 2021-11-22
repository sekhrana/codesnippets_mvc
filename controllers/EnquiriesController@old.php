<?php

use Firebase\JWT\JWT;

Yii::app()->clientScript->registerScript('helpers', '
          yii = {
              urls: {
                childIndex: ' . CJSON::encode(Yii::app()->createUrl('childPersonalDetails/index')) . ',
                waitList_enquiry: ' . CJSON::encode(Yii::app()->createUrl('enquiries/waitlistEnquiry')) . ',
                lost_enquiry: ' . CJSON::encode(Yii::app()->createUrl('enquiries/lostChildEnquiry')) . ',
                check_enquiry_email: ' . CJSON::encode(Yii::app()->createUrl('enquiries/checkEnquiryEmail')) . ',
                check_enquiry_phone: ' . CJSON::encode(Yii::app()->createUrl('enquiries/checkEnquiryPhone')) . ',
                enquiry_redirect_url: ' . CJSON::encode(Yii::app()->createAbsoluteUrl('enquiries/index')) . ',
                search_parent: ' . CJSON::encode(Yii::app()->createAbsoluteUrl('enquiries/searchParent')) . ',
                childPersonalDetails: ' . CJSON::encode(Yii::app()->createUrl('childPersonalDetails/update')) . ',
              }
          };
      ', CClientScript::POS_END);

class EnquiriesController extends eyManController {

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
		return 'registerYourChild, updateYourChild';
	}

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id) {
		$model = $this->loadModel($id);
        $prefereredSessions = SessionRates::model()->findAllByAttributes(['branch_id' => Branch::currentBranch()->id, 'is_modified' => 0], ['order' => 'name']);
		$this->render('view', array(
			'model' => $model,
            'prefereredSessions' => $prefereredSessions
		));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate() {
		$model = new Enquiries;
        $prefereredSessions = SessionRates::model()->findAllByAttributes(['branch_id' => Branch::currentBranch()->id, 'is_modified' => 0], ['order' => 'name']);
		$this->performAjaxValidation($model);
		if (isset($_POST['Next']) && isset($_POST['Enquiries'])) {
			$model->attributes = $_POST['Enquiries'];
			$model->branch_id = Yii::app()->session['branch_id'];
			if ($model->save())
				$this->redirect(array('update', 'id' => $model->id));
		}
		if (isset($_POST['Save']) && isset($_POST['Enquiries'])) {
			$model->attributes = $_POST['Enquiries'];
			$model->branch_id = Yii::app()->session['branch_id'];
			if ($model->save())
				$this->redirect(array('index'));
		}
		$this->render('create', array(
			'model' => $model,
            'prefereredSessions' => $prefereredSessions
		));
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id) {
		$model = $this->loadModel($id);
        $prefereredSessions = SessionRates::model()->findAllByAttributes(['branch_id' => Branch::currentBranch()->id, 'is_modified' => 0], ['order' => 'name']);
		$waitlistModel = clone $model;
		$waitlistModel->setScenario('waitlisted');
		$lostModel = clone $model;
		$lostModel->setScenario('lost');
		$this->performAjaxValidation($model);
		if (isset($_POST['Enquiries'])) {
			$model->attributes = $_POST['Enquiries'];
			if ($model->save()) {
				$this->redirect(array('index'));
			}
		}
		$this->render('update', array(
			'model' => $model,
			'waitlistModel' => $waitlistModel,
			'lostModel' => $lostModel,
            'prefereredSessions' => $prefereredSessions
		));
	}

	public function actionEnrollChild($id) {
		$model = $this->loadModel($id);
		$childPersonalDetails = new ChildPersonalDetails;
		if ($model->is_enroll_child == 1) {
			throw new CHttpException(400, 'Child is already enrolled');
		}
		$childPersonalDetails = new ChildPersonalDetails;
		$childPersonalDetails->first_name = $model->child_first_name;
		$childPersonalDetails->last_name = $model->child_last_name;
		$childPersonalDetails->branch_id = $model->branch_id;
		$this->render('enrollChild', array(
			'model' => $childPersonalDetails
		));
	}

	public function actionGetEnquiryData() {
		$model = new Enquiries;
		$enquiry = Enquiries::model()->findByPk($_POST['enquiry_id']);
		echo CJSON::encode($enquiry);
	}

	public function actionValidateEnquiryData() {
		$success = array('success' => 1);
		$model = new ChildPersonalDetails;
		if (isset($_POST['ChildPersonalDetails'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model->attributes = $_POST['ChildPersonalDetails'];
				$model->branch_id = Yii::app()->session['branch_id'];
				$enquiryModel = Enquiries::model()->findByPk($_POST['enquiry_id']);
				if ($model->validate() && $model->save()) {
					if (empty($enquiryModel->parent_email)) {
						$parentModel = '';
					} else {
						$parentModel = Parents::model()->findByAttributes(['email' => $enquiryModel->parent_email]);
					}

					if (empty($parentModel)) {
						$parentModel = new Parents;
						$parentModel->first_name = $enquiryModel->parent_first_name;
						$parentModel->last_name = $enquiryModel->parent_last_name;
						$parentModel->email = $enquiryModel->parent_email;
						$parentModel->mobile_phone = $enquiryModel->phone_mobile;
						if (!$parentModel->save()) {
							echo CJSON::encode($parentModel->getErrors());
							$transaction->rollback();
							Yii::app()->end();
						}
					}
					$parentChildMapping = new ParentChildMapping;
					$parentChildMapping->parent_id = $parentModel->id;
					$parentChildMapping->child_id = $model->id;
					$parentChildMapping->order = 1;

					if ($parentChildMapping->save()) {
						$enquiryModel->is_enroll_child = 1;
						$enquiryModel->status = 1;
						if ($enquiryModel->save()) {
							$success['child_id'] = $model->id;
							echo CJSON::encode($success);
							$transaction->commit();
						} else {
							echo CJSON::encode($enquiryModel->getErrors());
							$transaction->rollback();
							Yii::app()->end();
						}
					} else {
						echo CJSON::encode($parentChildMapping->getErrors());
						$transaction->rollback();
						Yii::app()->end();
					}
				} else {
					echo CJSON::encode($model->getErrors());
					$transaction->rollback();
				}
			} catch (Exception $ex) {
				$transaction->rollback();
			}
		}
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete() {
		if (isset($_POST) && $_POST['isAjaxRequest'] == 1) {
			$response = array('status' => '1');
			$model = $this->loadModel($_POST['id']);
			$model->is_deleted = 1;
			if ($model->save()) {
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
		$this->pageTitle = 'Enquiry | eyMan';
		$model = new Enquiries('search');
		$model->unsetAttributes(); // clear any default values
		$model->status = 0;
		if (isset($_GET['Enquiries']))
			$model->attributes = $_GET['Enquiries'];
		$this->render('index', array(
			'model' => $model,
		));
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin() {
		$model = new Enquiries('search');
		$model->unsetAttributes(); // clear any default values
		if (isset($_GET['Enquiries']))
			$model->attributes = $_GET['Enquiries'];

		$this->render('admin', array(
			'model' => $model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return Enquiries the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id) {
		$model = Enquiries::model()->findByPk($id);
		if ($model === null)
			throw new CHttpException(404, 'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param Enquiries $model the model to be validated
	 */
	protected function performAjaxValidation($models = array()) {
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'enquiries-form') {
			echo CActiveForm::validate($models);
			Yii::app()->end();
		}
	}

	public function actionEnrollEnquiry() {
		if (Yii::app()->request->isAjaxRequest) {
			$response = array('status' => 1, 'message' => "", 'enquiry_id' => '');
			$model = new Enquiries;
			$this->performAjaxValidation($model);
			if (isset($_POST['Enquiries'])) {
				$model->attributes = $_POST['Enquiries'];
				$model->branch_id = Yii::app()->session['branch_id'];
				if ($model->save()) {
					$response['enquiry_id'] = $model->id;
					echo CJSON::encode($response);
				} else {
					echo CJSON::encode($model->getErrors());
				}
			}
		} else {
			throw new CHttpException('404', "Your request is not Valid.");
		}
	}

	public function actionWaitlistEnquiry() {
		if (Yii::app()->request->isAjaxRequest) {
			$model = $this->loadModel($_POST['waitlist_enquiry_id']);
			$model->setScenario('waitlisted');
			$model->attributes = $_POST['Enquiries'];
			$model->is_waitlisted = 1;
			$model->status = Enquiries::WAITLISTED;
			if ($model->save()) {
				echo CJSON::encode(array('status' => 1, 'message' => 'Enquiry has been successfully waitlisted.'));
			} else {
				echo CJSON::encode($model->getErrors());
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	public function actionLostChildEnquiry() {
		if (Yii::app()->request->isAjaxRequest) {
			$model = $this->loadModel($_POST['lost_enquiry_id']);
			$model->setScenario('lost');
			$model->attributes = $_POST['Enquiries'];
			$model->status = Enquiries::LOST;
			if ($model->save()) {
				echo CJSON::encode(array('status' => 1, 'message' => 'Enquiry has been successfully marked as Lost.'));
			} else {
				echo CJSON::encode($model->getErrors());
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	public function actionCheckEnquiryEmail() {
		if (Yii::app()->request->isAjaxRequest) {
			$model = Enquiries::model()->findByAttributes([
				'parent_email' => $_POST['parent_email'],
				'branch_id' => Branch::currentBranch()->id
			]);
			if (!empty($model)) {
				echo CJSON::encode([
					'status' => 1,
					'model' => $model
				]);
			} else {
				echo CJSON::encode([
					'status' => 0,
				]);
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	public function actionCheckEnquiryPhone() {
		if (Yii::app()->request->isAjaxRequest) {
			$model = Enquiries::model()->findByAttributes([
				'parent_email' => $_POST['phone_mobile'],
				'branch_id' => Branch::currentBranch()->id
			]);
			if (!empty($model)) {
				echo CJSON::encode([
					'status' => 1,
					'model' => $model
				]);
			} else {
				echo CJSON::encode([
					'status' => 0,
				]);
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	public function actionGetEnquiryDetail() {
		if (Yii::app()->request->isAjaxRequest) {
			$response = array();
			$enquiry_color = [
				0 => '#fbd75b',
				1 => "#51B749",
				2 => '#FFB878',
				3 => 'red'
			];
			$start_date = date('Y-m-01', strtotime($_POST['currentMonth']));
			$finish_date = date('Y-m-t', strtotime($_POST['currentMonth']));
			$enquiryModal = Enquiries::model()->findAll([
				'select' => 'count(id) AS allEnquiry , status',
				'condition' => 'branch_id = :branch_id AND enquiry_date_time BETWEEN :start_date AND :finish_date ',
				'params' => [
					':branch_id' => Branch::currentBranch()->id,
					':start_date' => $start_date,
					':finish_date' => $finish_date,
				],
				'group' => 'status'
			]);
			if (!empty($enquiryModal)) {
				$response['color'] = array();
				$response['data'] = array();
				$temp[] = ['Enquiry Type', 'Total'];
				foreach ($enquiryModal as $key => $value) {
					$temp[] = [Enquiries::getStatus($value['status']), (int) $value['allEnquiry']];
					$response['color'][] = $enquiry_color[$value['status']];
				}
				$response['data'] = $temp;
				$response['error'] = false;
				echo CJSON::encode($response);
			} else {
				$response['error'] = true;
				echo CJSON::encode($response);
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionSearchParent() {
		if (Yii::app()->request->isAjaxRequest) {
			$response = array();
			$email = "";
			$home_phone = "";
			$parent_phone = "";
			if (isset($_POST['enquiry_id']) && !empty($_POST['enquiry_id'])) {
				$enquiryModel = Enquiries::model()->findByPk($_POST['enquiry_id']);
				$email = $enquiryModel->parent_email;
				$home_phone = $enquiryModel->phone_home;
				$parent_phone = $enquiryModel->phone_mobile;
			} else {
				$email = $_POST['email'];
				if (isset($_POST['home_phone']) && !empty($_POST['home_phone']))
					$home_phone = $_POST['home_phone'];
				$parent_phone = $_POST['parent_phone'];
			}
			$childParentModel = '';
			if (isset($email) && !empty($email)) {
				$childParentModel = Parents::model()->findByAttributes(['email' => $email]);
			}

			if (!empty($childParentModel)) {
				$response['parent_id'] = $childParentModel->id;
				$response['status'] = true;
				echo CJSON::encode($response);
			} else {
				$response['status'] = false;
				echo CJSON::encode($response);
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionGetOpenEnquiryDetail() {
		if (Yii::app()->request->isAjaxRequest) {
			$model = new Enquiries();
			$start_date = date('Y-m-t', strtotime($_POST['currentMonth']));
			$new_enquiries = Enquiries::model()->findAll([
				'condition' => 'branch_id = :branch_id AND status = 0 AND enquiry_date_time <= :start_date',
				'params' => [
					':branch_id' => Yii::app()->session['branch_id'],
					':start_date' => $start_date,
				],
				'order' => 'id DESC'
			]);
			foreach ($new_enquiries as $enquiry) {
				$enquiry->parent_first_name = $enquiry->branch->name;
			}
			echo CJSON::encode($new_enquiries);
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionRegisterYourChild($token) {
		$this->layout = '//layouts/registerYourChild';
		$newUrl = "";
		$token = (array) JWT::decode($token, Yii::app()->params['jwtKey'], array('HS256'));
		$branchModel = BranchNds::model()->findByPk($token['branch_id']);
		$companyModel = CompanyNds::model()->findByPk($token['company_id']);
		if ($branchModel && $companyModel) {
			$childPersonalDetails = new ChildPersonalDetailsNds('registerYourChild');
			$childParentalDetails = new ChildParentalDetails;
			$childGeneralDetails = new ChildGeneralDetails;
			$childMedicalDetails = new ChildMedicalDetails;
			$enquiryModel = new EnquiriesNds;
			if (isset($_POST) && !empty($_POST)) {
				$transaction = Yii::app()->db->beginTransaction();
				try {
					$enquiriesModel = new EnquiriesNds;
					$enquiriesModel->scenario = "registerYourChild";
					$enquiriesModel->child_first_name = $_POST['ChildPersonalDetailsNds']['first_name'];
					$enquiriesModel->child_last_name = $_POST['ChildPersonalDetailsNds']['last_name'];
					$enquiriesModel->child_dob = $_POST['ChildPersonalDetailsNds']['dob'];
					$enquiriesModel->parent_first_name = $_POST['ChildParentalDetails']['p1_first_name'];
					$enquiriesModel->parent_email = $_POST['ChildParentalDetails']['p1_email'];
					$enquiriesModel->preferred_session = $_POST['EnquiriesNds']['preferred_session'];
					$enquiriesModel->preferred_time = $_POST['EnquiriesNds']['preferred_time'];
					$enquiriesModel->branch_id = $branchModel->id;
					$enquiriesModel->child_detail = json_encode($_POST, true);
					if ($enquiriesModel->save()) {
						$token = \Firebase\JWT\JWT::encode([
								'branch_id' => $branchModel->id,
								'company_id' => $companyModel->id,
								'enquiry_id' => $enquiriesModel->id
								], Yii::app()->params['jwtKey']);
						$newUrl = Yii::app()->createAbsoluteUrl('enquiries/updateYourChild', ['token' => $token]);
						if (EnquiriesNds::model()->updateByPk($enquiriesModel->id, array('enquiry_url' => $newUrl))) {
							$subject = "Child Details Key";
							$content = "Enquiry has been successfully saved. Please use " . $newUrl . " to update child details.";
							$recipients = [
								'email' => $enquiriesModel->parent_email,
								'name' => $enquiriesModel->parent_first_name,
								'type' => 'to'
							];
							$mandrill = new EymanMandril($subject, $content, "no name", [$recipients], "info@teenyqueeny.co.uk");
							$response = $mandrill->sendEmail();
							if (!empty($response) && $response[0]['status'] == 'sent') {
								$flashMessageSuccess = "Enquiry has been successfully saved. Please use " . $newUrl . " to update child details.";
								Yii::app()->user->setFlash("success", $flashMessageSuccess);
								$transaction->commit();
							} else {
								$flashMessageSuccess = "Unable to save data";
								Yii::app()->user->setFlash("error", $flashMessageSuccess);
							}
//							$this->render('registerYourChild', array('newUrl' => $newUrl));
						} else {
							throw new Exception("Some error occur while saving enquirye.");
						}
					} else {
						throw new Exception("Some error occur while saving enquiry.");
					}
				} catch (Exception $ex) {
					$transaction->rollback();
					echo $ex->getMessage();
				}
			}
			$this->render('registerYourChild', array(
				'newUrl' => $newUrl,
				'childPersonalDetails' => $childPersonalDetails,
				'childParentalDetails' => $childParentalDetails,
				'childGeneralDetails' => $childGeneralDetails,
				'childMedicalDetails' => $childMedicalDetails,
				'enquiryModel' => $enquiryModel,
				'personalDetails' => "",
				'childDetails' => "",
				'generalDetails' => "",
				'parentalDetails' => ""));
		} else {
			throw new Exception('Your request is not valid.');
		}
	}

	public function actionUpdateYourChild($token) {
		$this->layout = '//layouts/registerYourChild';
		$newUrl = '';
		$token = (array) JWT::decode($token, Yii::app()->params['jwtKey'], array('HS256'));
		$branchModel = BranchNds::model()->findByPk($token['branch_id']);
		$companyModel = CompanyNds::model()->findByPk($token['company_id']);
		$enquiryModel = EnquiriesNds::model()->findByPk($token['enquiry_id']);


		if ($branchModel && $companyModel && $enquiryModel) {
			$childDetails = json_decode($enquiryModel->child_detail, true);
			$personalDetails = $childDetails['ChildPersonalDetailsNds'];
			$generalDetails = $childDetails['ChildGeneralDetails'];
			$parentalDetails = $childDetails['ChildParentalDetails'];
			$medicalDetails = $childDetails['ChildMedicalDetails'];
			$childPersonalDetails = new ChildPersonalDetailsNds;
			$childParentalDetails = new ChildParentalDetails;
			$childGeneralDetails = new ChildGeneralDetails;
			$childMedicalDetails = new ChildMedicalDetails;

			if (isset($_POST) && !empty($_POST)) {
				$transaction = Yii::app()->db->beginTransaction();
				try {
					$enquiriesModel = EnquiriesNds::model()->findByPk($_POST['EnquiriesNds']['id']);
					$enquiriesModel->scenario = "registerYourChild";
					$enquiriesModel->child_first_name = $_POST['ChildPersonalDetailsNds']['first_name'];
					$enquiriesModel->child_last_name = $_POST['ChildPersonalDetailsNds']['last_name'];
					$enquiriesModel->child_dob = $_POST['ChildPersonalDetailsNds']['dob'];
					$enquiriesModel->parent_first_name = $_POST['ChildParentalDetails']['p1_first_name'];
					$enquiriesModel->parent_email = $_POST['ChildParentalDetails']['p1_email'];
					$enquiriesModel->preferred_session = $_POST['EnquiriesNds']['preferred_session'];
					$enquiriesModel->preferred_time = $_POST['EnquiriesNds']['preferred_time'];
					$enquiriesModel->child_detail = json_encode($_POST, true);

					if ($enquiriesModel->save()) {
						$newUrl = $enquiriesModel->enquiry_url;
						$subject = "Child Details Key";
						$content = "Enquiry has been successfully updated. Please use " . $newUrl . " to update child details.";
						$recipients = [
							'email' => $enquiriesModel->parent_email,
							'name' => $enquiriesModel->parent_first_name,
							'type' => 'to'
						];
						$mandrill = new EymanMandril($subject, $content, "no name", [$recipients], "info@teenyqueeny.co.uk");
						$response = $mandrill->sendEmail();
						if (!empty($response) && $response[0]['status'] == 'sent') {

							$flashMessageSuccess = "Enquiry has been successfully updated. Please use " . $newUrl . " to further update child details.";
							Yii::app()->user->setFlash("success", $flashMessageSuccess);
							$transaction->commit();
						} else {
							$flashMessageSuccess = "Unable to save data";
							Yii::app()->user->setFlash("error", $flashMessageSuccess);
						}
					} else {
						throw new Exception("Some error occur while saving enquiry.");
					}
				} catch (Exception $ex) {
					$transaction->rollback();
					throw new Exception("Some error occur while saving enquiry.");
				}
			}
			$this->render('updateYourChild', array('newUrl' => $newUrl, 'childPersonalDetails' => $childPersonalDetails, 'childParentalDetails' => $childParentalDetails,
				'childGeneralDetails' => $childGeneralDetails, 'childMedicalDetails' => $childMedicalDetails, 'medicalDetails' => $medicalDetails, 'enquiryModel' => $enquiryModel, 'personalDetails' => $personalDetails, 'generalDetails' => $generalDetails,
				'parentalDetails' => $parentalDetails));
		} else {
			throw new Exception('Your request is not valid.');
		}
	}

	public function actionMap() {
		Yii::app()->clientScript->registerScriptFile('https://maps.googleapis.com/maps/api/js?key=AIzaSyCcv_FsDJCo3UlJ1JMBPUMvgRUuo8JW_t8', CClientScript::POS_END);
		Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseUrl . '/js/enquiryMaps.js?version=1.0.0', CClientScript::POS_END);
		$this->render('map');
	}

	public function actionGetMapData() {
		if (Yii::app()->request->isPostRequest) {
			$model = Enquiries::model()->findAllByAttributes(array('branch_id' => Branch::currentBranch()->id, 'status' => 0));
			echo CJSON::encode(array('status' => 1, 'data' => $model));
		} else {
			throw new CHttpException(404, 'Your request is not valid');
		}
	}

}
