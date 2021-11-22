<?php

class RoomController extends eyManController {

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
	public function actionView($id, $global = 0) {
		if ($global == 1) {
			$this->layout = 'global';
		}
		$model = $this->loadModel($id);
		$this->render('view', array(
			'model' => $model,
		));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate($global = 0) {
		if ($global == 1) {
			$this->layout = 'global';
		}
		$this->pageTitle = 'Room - Create | eyMan';
		$model = new Room;
		$branch = new Branch;
		$companyModel = Company::currentCompany();
		$this->performAjaxValidation($model);
		if (isset($_POST['Room'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model->attributes = $_POST['Room'];
				$model->branch_id = Yii::app()->session['branch_id'];
				$uploadedFile = CUploadedFile::getInstance($model, 'logo');
				if (isset($_POST['global']) && $_POST['global'] == 1) {
					$model->is_global = 1;
					$model->global_id = $companyModel->id;
					$model->branch_id = $branch->createBranchByGlobalId($companyModel->id);
				}
				if ($model->validate()) {
					if ($uploadedFile) {
						$model->file_name = time() . '_' . uniqid() . '.' . $uploadedFile->extensionName;
						$model->room_logo_raw = fopen($uploadedFile->tempName, 'r+');
						$model->uploadRoomLogo();
					}
					if ($model->save()) {
						$model->eylogIntegration();
						if (isset($_POST['global']) && $_POST['global'] == 1) {
							if (isset($_POST['Room']['create_for_existing']) && $_POST['Room']['create_for_existing'] == 1) {
								$model->createRoomGlobalSettings();
							}
							$transaction->commit();
							$this->redirect(array(
								'global'
							));
						}
						$transaction->commit();
						$this->redirect(['index']);
					} else {
						throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors errorSummary')));
					}
				} else {
					throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors errorSummary')));
				}
			} catch (Exception $ex) {
				print_r($ex->getMessage());
				die;
				$transaction->rollback();
				Yii::app()->user->setFlash('error', $ex->getMessage());
				$this->refresh();
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
	public function actionUpdate($id, $global = 0) {
		$model = $this->loadModel($id);
		if ($global == 1) {
			$this->layout = 'global';
		}
		$this->performAjaxValidation($model);
		if (isset($_POST['Room'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model->attributes = $_POST['Room'];
				$uploadedFile = CUploadedFile::getInstance($model, 'logo');
				if ($model->validate()) {
					if ($uploadedFile) {
						$model->file_name = time() . '_' . uniqid() . '.' . $uploadedFile->extensionName;
						$model->room_logo_raw = fopen($uploadedFile->tempName, 'r+');
						$model->uploadRoomLogo();
 					}
					if ($model->save()) {
						$model->eylogIntegration();
						if (isset($_POST['global']) && $_POST['global'] == 1) {
							$model->updateRoomGlobalSettings();
							$transaction->commit();
							$this->redirect(array(
								'global'
							));
						}
						$transaction->commit();
						$this->redirect(['index']);
					} else {
						throw new Exception($model, "", "", array('class' => 'customErrors errorSummary'));
					}
				} else {
					throw new Exception($model, "", "", array('class' => 'customErrors errorSummary'));
				}
			} catch (Exception $ex) {
				Yii::app()->user->setFlash('error', $ex->getMessage());
				$transaction->rollback();
				$this->refresh();
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
	public function actionDelete() {
		if (Yii::app()->request->isAjaxRequest) {
			$responseMsg = array('status' => 1);
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model = $this->loadModel($_POST['id']);
				ChildPersonalDetails::model()->updateAll(['room_id' => NULL], 'room_id = :room_id', [':room_id' => $_POST['id']]);
				StaffPersonalDetails::model()->updateAll(['room_id' => NULL], 'room_id = :room_id', [':room_id' => $_POST['id']]);
				if (Room::model()->updateByPk($model->id, ['is_deleted' => 1])) {
					$branchModal = $model->branch;
					if ($branchModal->is_integration_enabled == 1) {
						if (!empty($branchModal->api_key) && !empty($branchModal->api_password) && !empty($branchModal->api_url)) {
							$ch = curl_init($branchModal->api_url . Room::ROOM_API_PATH . "?external_id=eyman-" . $model->id);
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
								echo CJSON::encode(array('status' => 1, 'message' => 'Room deleted successfully.'));
							}
						} else {
							throw new Exception("API key/password/url are not set in Branch Settings");
						}
					}
					$transaction->commit();
					echo CJSON::encode($responseMsg);
				}
			} catch (Exception $ex) {
				$transaction->rollback();
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex() {
		$this->pageTitle = 'Room | eyMan';
		if (isset(Yii::app()->session['global_id'])) {
			unset(Yii::app()->session['global_id']);
		}
		$dataProvider = new CActiveDataProvider('Room');
		$criteria = new CDbCriteria();
		$criteria->compare('branch_id', Yii::app()->session['branch_id']);
		$criteria->compare('is_global', 0);
		$dataProvider->criteria = $criteria;
		if (isset($_GET['query'])) {
			$criteria = new CDbCriteria();
			$criteria->addSearchCondition('t.name', $_GET['query'], true);
			$criteria->addSearchCondition('t.capacity', $_GET['query'], true, 'OR');
			$criteria->addSearchCondition('t.age_group_lower', $_GET['query'], true, 'OR');
			$criteria->addSearchCondition('t.age_group_upper', $_GET['query'], true, 'OR');
			$criteria->addSearchCondition('t.branch_id', Yii::app()->session['branch_id'], true, 'AND');
			$dataProvider->criteria = $criteria;
		}
		$dataProvider->pagination->pageSize = 15;
		$this->render('index', array(
			'dataProvider' => $dataProvider,
		));
	}

	/**
	 * Lists all models.
	 */
	public function actionGlobal($query = NULL) {
		$this->layout = 'global';
		$this->pageTitle = 'Room | eyMan';
		$dataProvider = new CActiveDataProvider('Room', array(
			'criteria' => array(
				'condition' => "global_id = :global_id AND is_global = :is_global",
				'params' => array(':global_id' => Yii::app()->session['company_id'], ':is_global' => 1),
			),
			'pagination' => array(
				'pageSize' => 15
			)
		));

		if (isset($_GET['query'])) {
			$criteria = new CDbCriteria();
			$criteria->addSearchCondition('t.name', $query, true);
			$criteria->addSearchCondition('t.capacity', $query, true, 'OR');
			$criteria->addSearchCondition('t.age_group_lower', $query, true, 'OR');
			$criteria->addSearchCondition('t.age_group_upper', $query, true, 'OR');
			$criteria->addSearchCondition('t.global_id', Yii::app()->session['company_id'], true, 'AND');
			$criteria->addSearchCondition('t.is_global', 1, true, 'AND');
			$dataProvider->criteria = $criteria;
		}

		$this->render('index', array(
			'dataProvider' => $dataProvider,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return Room the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id) {
		$model = Room::model()->findByPk($id);
		if ($model === null)
			throw new CHttpException(404, 'The requested page does not exist.');
		else {
			$model->age_group_lower = $model->getAgeGroupLower();
			$model->age_group_upper = $model->getAgeGroupUpper();
		}
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param Room $model the model to be validated
	 */
	protected function performAjaxValidation($model) {
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'room-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

}
