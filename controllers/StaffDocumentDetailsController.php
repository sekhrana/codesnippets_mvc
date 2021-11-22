<?php

Yii::app()->clientScript->registerScript('eventHelpers', '
          yii = {
              urls: {
                  staffDocumentData: ' . CJSON::encode(Yii::app()->createUrl('documentType/getDocumentData')) . ',
                  staffCreateDocument: ' . CJSON::encode(Yii::app()->createUrl('staffDocumentDetails/create')) . ',
                  staffUpdateDocumentData: ' . CJSON::encode(Yii::app()->createUrl('staffDocumentDetails/update')) . ',
                  deleteDocUrl: ' . CJSON::encode(Yii::app()->createUrl('staffDocumentDetails/deleteDoc')) . ',
                  getDocument: ' . CJSON::encode(Yii::app()->createUrl('staffDocumentDetails/getDocumentData')) . ',
              }
          };
      ', CClientScript::POS_END);
Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseUrl . '/js/min-js/staffDocumentDetails.min.js?version=1.0.1', CClientScript::POS_END);

class StaffDocumentDetailsController extends eyManController {

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
		$successCreate = array('success' => 1, 'message' => 'Document has been successfully created');
		$successUpdate = array('success' => 1, 'message' => 'Document has been successfully updated');
		if (isset($_POST['StaffDocumentDetails']) && !empty($_POST['StaffDocumentDetails'])) {
			if (isset($_POST['document_staff_update_hidden']) && !empty($_POST['document_staff_update_hidden'])) {
				$model = $this->loadModel($_POST['document_staff_update_hidden']);
				$pervImage = $model->document_1;
				$model->attributes = $_POST['StaffDocumentDetails'];
				$uploadedFile = CUploadedFile::getInstance($model, 'document_1');
				if ($uploadedFile) {
					$model->file_name = time() . '_' . uniqid() . '.' . $uploadedFile->extensionName;
					$model->file_raw = fopen($uploadedFile->tempName, 'r+');
					$model->uploadDocument();
				}
				if ($model->validate() && $model->save()) {
					echo CJSON::encode($successUpdate);
				} else {
					echo CJSON::encode($model->getErrors());
				}
			}
			if (empty($_POST['document_staff_update_hidden'])) {
				$model = new StaffDocumentDetails;
				$model->attributes = $_POST['StaffDocumentDetails'];
				$model->staff_id = $_POST['document_staff_hidden'];
				$uploadedFile = CUploadedFile::getInstance($model, 'document_1');
				if ($uploadedFile) {
					$model->file_name = time() . '_' . uniqid() . '.' . $uploadedFile->extensionName;
					$model->file_raw = fopen($uploadedFile->tempName, 'r+');
					$model->uploadDocument();
				}
				if ($model->validate() && $model->save()) {
					echo CJSON::encode($successCreate);
				} else {
					echo CJSON::encode($model->getErrors());
				}
			}
		}
	}

	public function actionGetDocumentData() {
		if (($_POST['isAjaxRequest'] == 1) && isset($_POST['id'])) {
			$documentModel = StaffDocumentDetails::model()->findByAttributes(['id' => $_POST['id']]);

			if (!empty($documentModel)) {
				$documentData = $documentModel->document;
				$documentArray = array();
				foreach ($documentData as $key => $value) {
					$documentArray[$key] = $value;
				}
				$childDocumentArray = array();
				foreach ($documentModel as $key => $value) {
					$childDocumentArray[$key] = $value;
				}
				$docUrl = '';
				$deleteDocUrl = '';
				if (!empty($documentModel->document_1)) {
					$docUrl = $this->createUrl('staffDocumentDetails/downloadFile', ['filename' => $documentModel->document_1]);
					$deleteDocUrl = $this->createUrl('staffDocumentDetails/deleteDocs', ['filename' => $documentModel->document_1]);
				}
				echo CJSON::encode(array_merge($childDocumentArray, $documentArray, ['downloadDocUrl' => $docUrl, 'delectDocUlr' => $deleteDocUrl]));
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid');
		}
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($staff_id, $id, $document_id) {
		$staffDocumentData = StaffDocumentDetails::model()->findByAttributes(array('staff_id' => $staff_id, 'id' => $id));
		$documentData = $staffDocumentData->document;
		$documentArray = array();
		foreach ($documentData as $key => $value) {
			$documentArray[$key] = $value;
		}
		$staffDocumentArray = array();
		foreach ($staffDocumentData as $key => $value) {
			$staffDocumentArray[$key] = $value;
		}
		$docUrl = '';
		$deleteDocUrl = '';
		if (!empty($staffDocumentData->document_1)) {
			$docUrl = $this->createUrl('staffDocumentDetails/downloadFile', ['filename' => $staffDocumentData->document_1]);
			$deleteDocUrl = $this->createUrl('staffDocumentDetails/deleteDocs', ['filename' => $staffDocumentData->document_1]);
		}
		echo CJSON::encode(array_merge($staffDocumentArray, $documentArray, ['downloadDocUrl' => $docUrl, 'delectDocUlr' => $deleteDocUrl]));
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete() {
		if (Yii::app()->request->isAjaxRequest) {
			$response = array('status' => '1');
			if (StaffDocumentDetails::model()->updateByPk($_POST['id'], ['is_deleted' => 1])) {
				echo CJSON::encode(array('status' => 1, 'message' => 'Document has been successfuly deleted.'));
			} else {
				$response = array('status' => 0, 'message' => 'Seems some problem deleting the document.');
				echo CJSON::encode($response);
			}
		}
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex($staff_id) {
		$this->pageTitle = 'Document Type | eyMan';
		$model = new StaffDocumentDetails('search');
		$model->unsetAttributes();
		if (isset($_GET['StaffDocumentDetails']))
			$model->attributes = $_GET['StaffDocumentDetails'];

		$this->render('index', array(
			'model' => $model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return StaffDocumentDetails the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id) {
		$model = StaffDocumentDetails::model()->findByPk($id);
		if ($model === null)
			throw new CHttpException(404, 'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param StaffDocumentDetails $model the model to be validated
	 */
	protected function performAjaxValidation($model) {
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'staff-document-details-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	/*
	 * Download files from the rackspace or the local server
	 */

	public function actionDownloadFile($filename) {
		if (!empty($filename) && isset($filename)) {
			Yii::app()->getRequest()->sendFile($fileName, file_get_contents(GlobalPreferences::getSslUrl() . $filename));
		}
	}

	/*
	 * Function the delete Document from
	 * container
	 */

	public function actionDeleteDoc() {
		if (Yii::app()->request->isAjaxRequest) {
			$model = $this->loadModel($_POST['id']);
			if (empty($model)) {
				$response = array('status' => '0', 'message' => 'Document does not exists.');
				echo CJSON::encode($response);
				Yii::app()->end();
			}
			if (StaffDocumentDetails::model()->updateByPk($model->id, ['document_1' => NULL])) {
				echo CJSON::encode(array('status' => 1, 'message' => 'Document has been successfully deleted.'));
			} else {
				$response = array('status' => '0', 'message' => 'Seems some problem deleting the document.');
				echo CJSON::encode($response);
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

}
