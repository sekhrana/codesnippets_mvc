<?php

class ProductsController extends eyManController {

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
		$productModifiedModel = new CActiveDataProvider('SettingsHistory', array(
            'criteria' => array(
                'condition' => 'previous_id = :previous_id',
                'order' => 'id DESC',
                'params' => array(':previous_id' => $id)
            ),
            'pagination' => array(
                'pageSize' => 20,
            ),
        ));
		$this->render('view', array(
			'model' => $this->loadModel($id),
			'productModifiedModel' => $productModifiedModel
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
		$this->pageTitle = 'Create Products & Services | eyMan';
		$model = new Products;
		$branch = new Branch;
		$this->performAjaxValidation($model);
		$model->branch_id = Yii::app()->session['branch_id'];
		if (isset($_POST['Products'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model->attributes = $_POST['Products'];
				if (isset($_POST['global']) && $_POST['global'] == 1) {
					$model->is_global = 1;
					$model->global_id = Yii::app()->session['company_id'];
					$model->branch_id = $branch->createBranchByGlobalId(Yii::app()->session['company_id']);
				}
				if ($model->save()) {
					$global_product_id = $model->id;
					if (isset($_POST['global']) && $_POST['global'] == 1) {

						if (isset($_POST['Products']['create_for_existing']) && $_POST['Products']['create_for_existing'] == 1) {

							$branchModel = Branch::model()->findAllByAttributes(array('is_active' => 1, 'company_id' => Yii::app()->session['company_id']));
							if (!empty($branchModel)) {
								foreach ($branchModel as $branch) {
									$model->isNewRecord = true;
									$model->id = null;
									$model->branch_id = $branch->id;
									$model->attributes = $_POST['Products'];
									$model->is_global = 0;
									$model->global_id = Yii::app()->session['company_id'];
									$model->create_for_existing = 0;
									$model->global_products_id = $global_product_id;
									if (!$model->save()) {
										throw new Exception(CHtml::errorSummary($model, '', '', array('class' => 'customErros')));
									}
								}
							}
						}
						$transaction->commit();
						$this->redirect(array(
							'global'
						));
					}
					$transaction->commit();
					$this->redirect(array('index'));
				} else {
					throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
				}
			} catch (Exception $ex) {
				Yii::app()->user->setFlash('error', $ex->getMessage());
				$transaction->rollback();
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
		if ($global == 1) {
			$this->layout = 'global';
		}
		$this->pageTitle = 'Update Products & Services | eyMan';
				$productModifiedModel = new CActiveDataProvider('SettingsHistory', array(
            'criteria' => array(
                'condition' => 'previous_id = :previous_id',
                'order' => 'id DESC',
                'params' => array(':previous_id' => $id)
            ),
            'pagination' => array(
                'pageSize' => 20,
            ),
        ));
		$model = $this->loadModel($id);
		$this->performAjaxValidation($model);

		if (isset($_POST['Products'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model->attributes = $_POST['Products'];
				if ($model->save()) {
					if (isset($_POST['global']) && $_POST['global'] == 1) {
						if ($model->is_global = 1) {
							$branchProductModel = Products::model()->findAllByAttributes(array('global_products_id' => $model->id));
							foreach ($branchProductModel as $branchProduct) {
								$branchProduct->attributes = $_POST['Products'];
								$branchProduct->create_for_existing = 0;
								$branchProduct->is_global = 0;
								if (!$branchProduct->save()) {
									throw new Exception(CHtml::errorSummary($model, '', '', array('class' => 'customErros')));
								}
							}
						}
						$transaction->commit();
						$this->redirect(array(
							'global'
						));
					}
					$transaction->commit();
					$this->redirect(array('index'));
				} else {
					throw new Exception(CHtml::errorSummary($model, '', '', array('class' => 'customErrors')));
				}
			} catch (Exception $ex) {
				Yii::app()->user->setFlash('error', $ex->getMessage());
				$transaction->rollback();
			}
		}
		$this->render('update', array(
			'model' => $model,
			'productModifiedModel' => $productModifiedModel
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

		$this->pageTitle = 'Products/Services | eyMan';
		if (isset(Yii::app()->session['global_id'])) {
			unset(Yii::app()->session['global_id']);
		}
		$model = new Products('search');
		$model->unsetAttributes(); // clear any default values
		if (isset($_GET['Products']))
			$model->attributes = $_GET['Products'];

		$this->render('index', array(
			'model' => $model,
		));
	}

	/**
	 * Lists all models.
	 */
	public function actionGlobal() {
		$this->layout = 'global';
		$this->pageTitle = 'Products/Services | eyMan';
		if (isset(Yii::app()->session['company_id'])) {
			Yii::app()->session['global_id'] = Yii::app()->session['company_id'];
		}
		$model = new Products('search');
		$model->unsetAttributes(); // clear any default values
		if (isset($_GET['Products']))
			$model->attributes = $_GET['Products'];

		$this->render('index', array(
			'model' => $model,
		));
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin() {
		$model = new Products('search');
		$model->unsetAttributes(); // clear any default values
		if (isset($_GET['Products']))
			$model->attributes = $_GET['Products'];

		$this->render('admin', array(
			'model' => $model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return Products the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id) {
		$model = Products::model()->findByPk($id);
		if ($model === null)
			throw new CHttpException(404, 'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param Products $model the model to be validated
	 */
	protected function performAjaxValidation($model) {
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'products-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	/**
	 * Inactive a particular model.
	 * If inactivation is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionStatus($id, $global = 0) {
		$model = $this->loadModel($id);
		if ($model->is_active == 1) {
			$model->is_active = 0;
		} else {
			$model->is_active = 1;
		}
		$model->save();
		// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
		if (!isset($_GET['ajax'])) {
			if ($global == 1)
				$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('global'));
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
		}
	}

	/*
	 * Modify Rates of products/services.
	 */
	public function actionModify($id) {
		$model = $this->loadModel($id);
		$model->scenario = "modify";
		$this->performAjaxValidation($model);
		if (isset($_POST['Products'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model->isNewRecord = true;
				$model->id = NULL;
				$model->attributes = $_POST['Products'];
				$model->is_modified = 1;
				if (!$model->save()) {
					throw new Exception(CHtml::errorSummary($model, '', '', array('class' => 'customErrors')));
				}
				$settingsHistory = new SettingsHistory();
				$settingsHistory->previous_id = $id;
				$settingsHistory->new_id = $model->id;
				$settingsHistory->date = $_POST['Products']['effective_date'];
				$settingsHistory->type = SettingsHistory::PRODUCTS;
				if (!$settingsHistory->save()) {
					throw new Exception(CHtml::errorSummary($settingsHistory, '', '', array('class' => 'customErrors')));
				}
				Yii::app()->user->setFlash('success', 'Products/Service has been successfully modified.');
				$transaction->commit();
				$this->redirect(['products/update', 'id' => $id]);
			} catch (Exception $ex) {
				Yii::app()->user->setFlash('error', $ex->getMessage());
				$transaction->rollback();
				$this->refresh();
			}
		}
		$this->render('modify', ['model' => $model]);
	}

}
