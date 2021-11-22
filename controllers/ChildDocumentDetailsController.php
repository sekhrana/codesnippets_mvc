<?php

Yii::app()->clientScript->registerScript('documentHelpers', '
          yii = {
              urls: {
                  documentData: ' . CJSON::encode(Yii::app()->createUrl('documentType/getDocumentData')) . ',
                  createDocument: ' . CJSON::encode(Yii::app()->createUrl('childDocumentDetails/create')) . ',
                  updateData: ' . CJSON::encode(Yii::app()->createUrl('childDocumentDetails/update')) . ',
                  deleteDocUrl: ' . CJSON::encode(Yii::app()->createUrl('childDocumentDetails/deleteDoc')) . ',
              }
          };
      ', CClientScript::POS_END);
Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseUrl . '/js/min-js/childDocumentDetails.min.js?version=1.0.0', CClientScript::POS_END);

class ChildDocumentDetailsController extends eyManController {

	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout = '//layouts/dashboard';

	public function filters() {
		return array(
			'rights',
		);
	}

	public function allowedActions() {

		return '';
	}

	public function actionCreate() {
		if (isset($_POST['ChildDocumentDetails']) && !empty($_POST['ChildDocumentDetails'])) {
			if (empty($_POST['document_child_update_hidden'])) {
				$transaction = Yii::app()->db->beginTransaction();
				try {
					$model = new ChildDocumentDetails;
					$model->attributes = $_POST['ChildDocumentDetails'];
					$model->child_id = $_POST['document_child_hidden'];
					$uploadedFile = CUploadedFile::getInstance($model, 'document_1');
					if (!$model->validate()) {
						throw new JsonException($model->getErrors());
					}
					if ($uploadedFile) {
						$model->file_name = time() . '_' . uniqid() . '.' . $uploadedFile->extensionName;
						$model->file_raw = fopen($uploadedFile->tempName, 'r+');
						$model->uploadDocument();
					}
					if (!$model->save()) {
						throw new Exception($model->getErrors());
					}
					$transaction->commit();
					echo CJSON::encode([
						'success' => 1,
						'message' => 'Document has been successfully created.'
					]);
					Yii::app()->end();
				} catch (JsonException $ex) {
					$transaction->rollback();
					echo CJSON::encode($ex->getMessage());
					Yii::app()->end();
				}
			}
		}
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser  will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate() {
		if (Yii::app()->request->isAjaxRequest) {
			$model = $this->loadModel($_POST['document_child_update_hidden']);
			if (!empty($model)) {
				$transaction = Yii::app()->db->beginTransaction();
				try {
					$model->attributes = $_POST['ChildDocumentDetails'];
					$uploadedFile = CUploadedFile::getInstance($model, 'document_1');
					if (!$model->validate()) {
						throw new JsonException($model->getErrors());
					}
					if ($uploadedFile) {
						$model->file_name = time() . '_' . uniqid() . '.' . $uploadedFile->extensionName;
						$model->file_raw = fopen($uploadedFile->tempName, 'r+');
						$model->uploadDocument();
					}
					if (!$model->save()) {
						throw new Exception($model->getErrors());
					}
					$transaction->commit();
					echo CJSON::encode([
						'status' => 1,
						'message' => 'Document has been successfully updated.'
					]);
				} catch (Exception $ex) {
					$transaction->rollback();
					echo CJSON::encode($ex->getMessage());
					Yii::app()->end();
				}
			} else {
				echo CJSON::encode([
					'status' => 0,
					'message' => 'Document does not exists.'
				]);
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid');
		}
	}

	public function actionGetDocumentData() {
		if (Yii::app()->request->isAjaxRequest) {
			$documentModel = ChildDocumentDetails::model()->findByPk($_POST['id']);
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
					$docUrl = $this->createUrl('childDocumentDetails/downloadFile', ['filename' => $documentModel->document_1]);
					$deleteDocUrl = $this->createUrl('childDocumentDetails/deleteDocs', ['filename' => $documentModel->document_1]);
				}
				echo CJSON::encode(array_merge($childDocumentArray, $documentArray, ['downloadDocUrl' => $docUrl, 'delectDocUlr' => $deleteDocUrl]));
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid');
		}
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex($child_id) {
		$this->pageTitle = 'Document Type | eyMan';
		$model = new ChildDocumentDetails('search');
		$model->unsetAttributes();
		if (isset($_GET['ChildDocumentDetails']))
			$model->attributes = $_GET['ChildDocumentDetails'];
		$this->render('index', array(
			'model' => $model,
		));
	}

	public function actionDelete() {
		if (Yii::app()->request->isAjaxRequest) {
			$model = $this->loadModel($_POST['id']);
			if (ChildDocumentDetails::model()->updateByPk($model->id, ['is_deleted' => 1])) {
				echo CJSON::encode(array('status' => 1));
			} else {
				echo CJSON::encode(array('status' => 0));
			}
		}
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return ChildDocumentDetails the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id) {
		$model = ChildDocumentDetails::model()->findByPk($id);
		if ($model === null)
			throw new CHttpException(404, 'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param ChildDocumentDetails $model the model to be validated
	 */
	protected function performAjaxValidation($model) {
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'child-document-details-form') {
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
			$docName = $_POST['docName'];
			$id = $_POST['id'];
			$response = array('status' => 1);
			$model = $this->loadModel($id);
			if (ChildDocumentDetails::model()->updateByPk($model->id, ['document_1' => NULL])) {
				echo CJSON::encode(['status' => 1, 'message' => 'Document has been successfully deleted.']);
			} else {
				echo CJSON::encode(['status' => 1, 'message' => 'Seems some problem deleting the document.']);
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

}
