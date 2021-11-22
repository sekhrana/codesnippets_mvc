<?php

class CompanyController extends eyManController {

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
		$this->render('view', array(
			'model' => $this->loadModel($id),
		));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate() {
		$model = new Company;
		$this->performAjaxValidation($model);
		if (isset($_POST['Company'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model->attributes = $_POST['Company'];
				$uploadedFile = CUploadedFile::getInstance($model, 'logo');
				if ($model->validate()) {
					if ($uploadedFile) {
						$model->file_name = time() . '_' . uniqid() . '.' . $uploadedFile->extensionName;
						$model->company_logo_raw = fopen($uploadedFile->tempName, 'r+');
						$model->uploadCompanyLogo();
					}
					if ($model->save()) {
						$branch = new Branch;
						$createBranch = $branch->createBranchWithZeroId($model->id);
						$model->insertTemplateData($createBranch, $model->id);
						$transaction->commit();
						$this->redirect(array('site/dashboard'));
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
		$model = $this->loadModel($id);
		$this->performAjaxValidation($model);

		if (isset($_POST['Company'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model->attributes = $_POST['Company'];
				$uploadedFile = CUploadedFile::getInstance($model, 'logo');
				if ($model->validate()) {
					if ($uploadedFile) {
						$model->file_name = time() . '_' . uniqid() . '.' . $uploadedFile->extensionName;
						$model->company_logo_raw = fopen($uploadedFile->tempName, 'r+');
						$model->uploadCompanyLogo();
					}
					if ($model->save()) {
						$transaction->commit();
						$this->redirect(array('company/view', 'id' => $model->id));
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
			'model' => $model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return Company the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id) {
		$model = Company::model()->findByPk($id);
		if ($model === null)
			throw new CHttpException(404, 'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param Company $model the model to be validated
	 */
	protected function performAjaxValidation($model) {
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'company-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

}
