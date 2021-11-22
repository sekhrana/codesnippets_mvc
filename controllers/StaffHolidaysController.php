<?php

class StaffHolidaysController extends eyManController {

	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout = '//layouts/column2';

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
	public function actionCreate() {
		if (Yii::app()->request->isAjaxRequest) {
			if (isset($_POST['StaffHolidays'])) {
				$model = new StaffHolidays;
				$model->attributes = $_POST['StaffHolidays'];
				$model->branch_id = Branch::currentBranch()->id;
				if ($model->save()) {
					echo CJSON::encode(array('status' => 1, 'message' => "Absence has been successfully created."));
				} else {
					echo CJSON::encode(array('status' => 0, 'message' => '', 'error' => $model->getErrors()));
				}
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionUpdate($id) {
		if (Yii::app()->request->isAjaxRequest) {
			if (isset($_POST['StaffHolidays'])) {
				$transaction = Yii::app()->db->beginTransaction();
				try {
					$model = $this->loadModel($id);
					if (empty($model)) {
						echo CJSON::encode(array('status' => 0, 'message' => "Absence does not exists or has been Deleted/Updated"));
						Yii::app()->end();
					}
					$model->attributes = $_POST['StaffHolidays'];
					$model->branch_id = Branch::currentBranch()->id;
					if (StaffHolidays::model()->updateByPk($id, array('is_deleted' => 1))) {
						if ($model->save()) {
							$transaction->commit();
							echo CJSON::encode(array('status' => 1, 'message' => "Absence has been successfully updated."));
							Yii::app()->end();
						} else {
							throw new JsonException($model->getErrors());
						}
					}
				} catch (JsonException $ex) {
					$transaction->rollback();
					echo CJSON::encode(array('status' => 0, 'message' => '', 'error' => $ex->getOptions()));
				}
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete() {
		if (Yii::app()->request->isAjaxRequest) {
			$response = array('status' => 1, 'message' => 'Absence has been succsessfully deleted.');
			if (StaffHolidays::model()->updateByPk(Yii::app()->request->getPost('id'), array('is_deleted' => 1))) {
				echo CJSON::encode($response);
			} else {
				$response['status'] = 0;
				$response['message'] = "Their seems to be some problem deleting the Absence";
				echo CJSON::encode($response);
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid');
		}
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return StaffHolidays the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id) {
		$model = StaffHolidays::model()->findByPk($id);
		if ($model === null)
			throw new CHttpException(404, 'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param StaffHolidays $model the model to be validated
	 */
	protected function performAjaxValidation($model) {
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'staff-holidays-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

}
