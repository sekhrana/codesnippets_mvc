<?php

class AgeRatioController extends eyManController {

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

    /*
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
        $model = new AgeRatio;
        $this->performAjaxValidation($model);
        if (isset($_POST['AgeRatio'])) {
            $model->attributes = $_POST['AgeRatio'];
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
                    if (isset($_POST['AgeRatio']['create_for_existing']) && $_POST['AgeRatio']['create_for_existing'] == 1) {

                        $branchModel = Branch::model()->findAllByAttributes(array('is_active' => 1, 'company_id' => Yii::app()->session['company_id']));
                        if (!empty($branchModel)) {
                            foreach ($branchModel as $branch) {

                                $model->isNewRecord = true;
                                $model->id = null;
                                $model->branch_id = $branch->id;
                                $model->attributes = $_POST['AgeRatio'];
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
        // Uncomment the following line if AJAX validation is needed
        $this->performAjaxValidation($model);

        if (isset($_POST['AgeRatio'])) {
            $model->attributes = $_POST['AgeRatio'];
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
                $response = array('status' => '0');
                echo CJSON::encode($response);
            }
        }
    }

    /**
     * Lists all models.
     */
    public function actionIndex() {
        if (isset($_POST['Branch'])) {
            $branchModel = Branch::model()->findByPk(Yii::app()->session['branch_id']);
            $branchModel->default_ratio = $_POST['Branch']['default_ratio'];
            if($branchModel->validate()){
              $branchModel->save();
            }  else {
                $branchModel->getErrors();
            }
        }
        $this->pageTitle = 'AgeRatio| eyMan';
        if (isset(Yii::app()->session['global_id'])) {
            unset(Yii::app()->session['global_id']);
        }
        $model = new AgeRatio('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['AgeRatio']))
            $model->attributes = $_GET['AgeRatio'];
        $this->render('index', array(
            'model' => $model,
        ));
    }

    /**
     * Lists all models.
     */
    public function actionGlobal() {
        $this->layout = 'global';
        $this->pageTitle = 'AgeRatio| eyMan';
        if (isset(Yii::app()->session['company_id'])) {
            Yii::app()->session['global_id'] = Yii::app()->session['company_id'];
        }
        $model = new AgeRatio('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['AgeRatio']))
            $model->attributes = $_GET['AgeRatio'];

        $this->render('index', array(
            'model' => $model,
        ));
    }

    /**
     * Manages all models.
     */
    public function actionAdmin() {
        $model = new AgeRatio('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['AgeRatio']))
            $model->attributes = $_GET['AgeRatio'];

        $this->render('admin', array(
            'model' => $model,
        ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return AgeRatio the loaded model
     * @throws CHttpException
     */
    public function loadModel($id) {
        $model = AgeRatio::model()->findByPk($id);
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }

    /**
     * Performs the AJAX validation.
     * @param AgeRatio $model the model to be validated
     */
    protected function performAjaxValidation($model) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'age-ratio-form') {
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

}
