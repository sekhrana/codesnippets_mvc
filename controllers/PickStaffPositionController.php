<?php

class PickStaffPositionController extends eyManController {

	public $layout = '//layouts/systemSettings';

	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */

	/**
	 * @return array action filters
	 */
	public function filters() {
		return array(
			'rights',
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
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
		$model = new PickStaffPosition;

		// Uncomment the following line if AJAX validation is needed
		 $this->performAjaxValidation($model);

		if (isset($_POST['PickStaffPosition'])) {
			$model->attributes = $_POST['PickStaffPosition'];
            if ($model->save()){
				$this->redirect(array('index'));
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

		// Uncomment the following line if AJAX validation is needed
		 $this->performAjaxValidation($model);

		if (isset($_POST['PickStaffPosition'])) {
			$model->attributes = $_POST['PickStaffPosition'];
			if ($model->save())
				$this->redirect(array('index'));
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
		if (isset($_POST) && $_POST['isAjaxRequest'] == 1) {
			$response = array('status' => '1');
			$model = $this->loadModel($_POST['id']);
			$model->is_deleted = 1;
			StaffPersonalDetails::model()->updateAll(['position' => NULL], "position = :position", [':position' => $_POST['id']]);
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

		$model = new PickStaffPosition('search');
		$model->unsetAttributes(); // clear any default values
		if (isset($_GET['PickStaffPosition']))
			$model->attributes = $_GET['PickStaffPosition'];
		$this->render('index', array(
			'model' => $model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return PickStaffPosition the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id) {
		$model = PickStaffPosition::model()->findByPk($id);
		if ($model === null)
			throw new CHttpException(404, 'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param PickStaffPosition $model the model to be validated
	 */
	protected function performAjaxValidation($model) {
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'pick-staff-position-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

}
