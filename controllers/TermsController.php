<?php

Yii::app()->clientScript->registerScript('helpers', '
          eyMan = {
              urls: {
								saveChildFundingDetails: ' . CJSON::encode(Yii::app()->createUrl("terms/saveChildFundingDetails")) . ',
              }
          };
      ', CClientScript::POS_END);

class TermsController extends eyManController {

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
		$this->render('view', array(
			'model' => $this->loadModel($id),
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
		$model = new Terms;
		$this->performAjaxValidation($model);
		if (isset($_POST['Terms'])) {
			$model->attributes = $_POST['Terms'];
			if ($model->holiday_start_date_1 == '') {
				$model->holiday_start_date_1 = null;
			}
			if ($model->holiday_finish_date_1 == '') {
				$model->holiday_finish_date_1 = null;
			}
			if ($model->holiday_start_date_2 == '') {
				$model->holiday_start_date_2 = null;
			}
			if ($model->holiday_finish_date_2 == '') {
				$model->holiday_finish_date_2 = null;
			}
			if ($model->holiday_start_date_3 == '') {
				$model->holiday_start_date_3 = null;
			}
			if ($model->holiday_finish_date_3 == '') {
				$model->holiday_finish_date_3 = null;
			}
			$model->branch_id = Yii::app()->session['branch_id'];
			if (isset($_POST['global']) && $_POST['global'] == 1) {
				$model->is_global = 1;
				$model->global_id = Yii::app()->session['company_id'];
				Branch::model()->resetScope(true);
				$branch = Branch::model()->findByAttributes(array('is_active' => 1, 'global_id' => Yii::app()->session['company_id'], 'is_deleted' => 0));
				Branch::model()->resetScope(false);
				if (empty($branch)) {
					$branch_insert = new Branch;
					$branch_insert->isNewRecord = true;
					$branch_insert->company_id = Yii::app()->session['company_id'];
					$branch_insert->name = 'Global Branch';
					$branch_insert->global_id = Yii::app()->session['company_id'];
					$branch_insert->county = 'global';
					$branch_insert->country = 'global';
					$branch_insert->town = 'global';
					$branch_insert->phone = '1234567890';
					$branch_insert->address_1 = 'global';
					$branch_insert->postcode = '12345';
					$branch_insert->email = 'global@eylog.uk';
					$branch_insert->operation_start_time = '08:00:00';
					$branch_insert->operation_finish_time = '20:00:00';
					$branch_insert->validate();
					if ($branch_insert->save()) {
						$model->branch_id = $branch_insert->id;
					}
				} else {
					$model->branch_id = $branch->id;
				}
			}
			if ($model->save()) {
				if (isset($_POST['global']) && $_POST['global'] == 1) {
					if (isset($_POST['Terms']['create_for_existing']) && $_POST['Terms']['create_for_existing'] == 1) {
						$branchModel = Branch::model()->findAllByAttributes(array('is_active' => 1, 'company_id' => Yii::app()->session['company_id']));
						if (!empty($branchModel)) {
							foreach ($branchModel as $branch) {
								$model->isNewRecord = true;
								$model->id = null;
								$model->branch_id = $branch->id;
								$model->attributes = $_POST['Terms'];
								$model->is_global = 0;
								$model->global_id = Yii::app()->session['company_id'];
								$model->create_for_existing = 0;
								$model->save();
							}
						}
					}
					$this->redirect(array(
						'global'
					));
				}
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
	public function actionUpdate($id, $global = 0) {
		$model = $this->loadModel($id);
		if ($global == 1) {
			$this->layout = 'global';
		}
		$this->performAjaxValidation($model);
		if (isset($_POST['Terms'])) {
			$model->attributes = $_POST['Terms'];
			if ($model->holiday_start_date_1 == '') {
				$model->holiday_start_date_1 = null;
			}
			if ($model->holiday_finish_date_1 == '') {
				$model->holiday_finish_date_1 = null;
			}
			if ($model->holiday_start_date_2 == '') {
				$model->holiday_start_date_2 = null;
			}
			if ($model->holiday_finish_date_2 == '') {
				$model->holiday_finish_date_2 = null;
			}
			if ($model->holiday_start_date_3 == '') {
				$model->holiday_start_date_3 = null;
			}
			if ($model->holiday_finish_date_3 == '') {
				$model->holiday_finish_date_3 = null;
			}
			if ($model->save()) {
				if (isset($_POST['global']) && $_POST['global'] == 1) {
					$this->redirect(array(
						'global'
					));
				}
				$this->redirect(array('index'));
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
		if (isset($_POST) && $_POST['isAjaxRequest'] == 1) {
			$response = array('status' => '1');
			$model = $this->loadModel($_POST['id']);
			$model->is_deleted = 1;
			if ($model->save()) {
				echo CJSON::encode($response);
			} else {
				$response = array('status' => '0', 'error' => $model->getErrors());
				echo CJSON::encode($response);
			}
		}
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex() {
		if (isset($_POST['Branch']) && isset($_POST['Save']) == "Update") {
			$branchModel = Branch::model()->findByPk(Yii::app()->session['branch_id']);
			$branchModel->maximum_funding_hours_day = $_POST['Branch']['maximum_funding_hours_day'];
			$branchModel->maximum_funding_hours_week = $_POST['Branch']['maximum_funding_hours_week'];
			$branchModel->is_funding_applicable_on_weekend = $_POST['Branch']['is_funding_applicable_on_weekend'];
			$branchModel->funding_allocation_type = $_POST['Branch']['funding_allocation_type'];
			$branchModel->is_round_off_entitlement = $_POST['Branch']['is_round_off_entitlement'];
			$branchModel->is_exclude_funding = $_POST['Branch']['is_exclude_funding'];
			if ($branchModel->validate()) {
				$branchModel->save();
			} else {
				$branchModel->getErrors();
			}
		}
		$this->pageTitle = 'Terms| eyMan';
		if (isset(Yii::app()->session['global_id'])) {
			unset(Yii::app()->session['global_id']);
		}
		$model = new Terms('search');
		$model->unsetAttributes(); // clear any default values
		if (isset($_GET['Terms'])) {
			$model->attributes = $_GET['Terms'];
			if ($model->start_date != '')
				$model->start_date = date('Y-m-d', strtotime($model->start_date));
			if ($model->finish_date != '')
				$model->finish_date = date('Y-m-d', strtotime($model->finish_date));
		}
		$this->render('index', array(
			'model' => $model,
		));
	}

	/**
	 * Lists all models.
	 */
	public function actionGlobal() {
		$this->layout = 'global';
		$this->pageTitle = 'Terms| eyMan';
		if (isset(Yii::app()->session['company_id'])) {
			Yii::app()->session['global_id'] = Yii::app()->session['company_id'];
		}
		$model = new Terms('search');
		$model->unsetAttributes(); // clear any default values
		if (isset($_GET['Terms'])) {
			$model->attributes = $_GET['Terms'];
			$model->start_date = date('Y-m-d', strtotime($model->start_date));
			$model->finish_date = date('Y-m-d', strtotime($model->finish_date));
		}

		$this->render('index', array(
			'model' => $model,
		));
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin() {
		$model = new Terms('search');
		$model->unsetAttributes(); // clear any default values
		if (isset($_GET['Terms']))
			$model->attributes = $_GET['Terms'];

		$this->render('admin', array(
			'model' => $model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return Terms the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id) {
		$model = Terms::model()->findByPk($id);
		if ($model === null)
			throw new CHttpException(404, 'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param Terms $model the model to be validated
	 */
	protected function performAjaxValidation($model) {
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'terms-form') {
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

	public function actionChildFundingDetails($id) {
		$model = $this->loadModel($id);
		ChildPersonalDetails::$term_id = $id;
		if ($model) {
			$childModel = new CActiveDataProvider('ChildPersonalDetails', array(
				'criteria' => array(
					'condition' => 'branch_id = :branch_id',
					'order' => 'first_name, last_name',
					'params' => array(':branch_id' => $model->branch_id)
				),
				'pagination' => array(
					'pageSize' => 500,
				),
			));
			$this->render('childFundingDetails', array(
				'childModel' => $childModel,
			));
		} else {
			throw new CHttpException(404, "This page does not exists.");
		}
	}

	public function actionSaveChildFundingDetails() {
		if (Yii::app()->request->isAjaxRequest) {
			if (isset($_POST['child']) && !empty($_POST['child'])) {
				$termModel = Terms::model()->findByPk($_POST['term_id']);
				$errors = array();
				foreach ($_POST['child'] as $key => $value) {
					$childModel = ChildPersonalDetails::model()->findByPk($value);
					if (!empty($childModel)) {
						$transaction = Yii::app()->db->beginTransaction();
						try {
							$childFundingDetails = new ChildFundingDetails;
							$childFundingDetails->child_id = $childModel->id;
							$childFundingDetails->branch_id = $childModel->branch_id;
							$childFundingDetails->sf = (isset($_POST['sf'][$key])) ? 1 : 0;
							$childFundingDetails->pdf = (isset($_POST['pdf'][$key])) ? 1 : 0;
							$childFundingDetails->term_id = $_POST['term_id'];
							$childFundingDetails->type_of_entitlement = 0;
							$entitlementDetails = ChildFundingDetails::calculateEntitlement($childFundingDetails->term_id, $childFundingDetails->sf, $_POST['weekly_hours'][$key], 1);
							$childFundingDetails->week_count = $entitlementDetails['week_count'];
							$childFundingDetails->funded_hours = $entitlementDetails['entitlement'];
							$childFundingDetails->funded_hours_weekly = $_POST['weekly_hours'][$key];
							if(!$childFundingDetails->save()){
								throw new JsonException($childFundingDetails->getErrors());
							}
							ChildFundingDetails::createFundingTransactionsWeekly($termModel, $childFundingDetails);
							$transaction->commit();
							Yii::app()->user->setFlash("funding_status_".$childModel->id, "alert alert-success");
						} catch (JsonException $ex) {
							$transaction->rollback();
							Yii::app()->user->setFlash("funding_status_".$childModel->id, "alert alert-danger");
							$errors[$childModel->id][] = $ex->getOptions();
						}
					}
				}
				echo CJSON::encode(['status' => 1, 'message' => 'Funding has been successfuly created.', 'error' => $errors]);
			} else {
				echo CJSON::encode(['status' => 0, 'message' => 'Please select atleast one children.', 'error' => []]);
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

}
