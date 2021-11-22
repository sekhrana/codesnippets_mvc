<?php

class VatcodesController extends eyManController {

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
        $model = new Vatcodes;
        $branch = new Branch;
        $this->performAjaxValidation($model);
        if (isset($_POST['Vatcodes'])) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                $model->attributes = $_POST['Vatcodes'];
                $model->branch_id = Yii::app()->session['branch_id'];
                if (isset($_POST['global']) && $_POST['global'] == 1) {
                    $model->is_global = 1;
                    $model->global_id = Yii::app()->session['company_id'];
                    $model->branch_id = $branch->createBranchByGlobalId(Yii::app()->session['company_id']);
                }
                if ($model->save()) {
                    $global_vatcodes_id = $model->id;
                    if (isset($_POST['global']) && $_POST['global'] == 1) {

                        if (isset($_POST['Vatcodes']['create_for_existing']) && $_POST['Vatcodes']['create_for_existing'] == 1) {
                            $branchModel = Branch::model()->findAllByAttributes(array('is_active' => 1, 'company_id' => Yii::app()->session['company_id']));
                            if (!empty($branchModel)) {
                                foreach ($branchModel as $branch) {
                                    $model->isNewRecord = true;
                                    $model->id = null;
                                    $model->branch_id = $branch->id;
                                    $model->attributes = $_POST['Vatcodes'];
                                    $model->is_global = 0;
                                    $model->global_id = Yii::app()->session['company_id'];
                                    $model->create_for_existing = 0;
                                    $model->global_vatcodes_id = $global_vatcodes_id;
                                    if (!$model->save()) {
                                        throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
                                    }
                                }
                            }
                        }
                        $transaction->commit();
                        $this->redirect(array('global'));
                    }
                    $transaction->commit();
                    $this->redirect(array('index'));
                } else {
                    throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
                }
            } catch (Exception $ex) {
                Yii::app()->user->setFlash('error', $ex->getMessage());
                $transaction->rollback();
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
        $model = $this->loadModel($id);
        $this->performAjaxValidation($model);
        if (isset($_POST['Vatcodes'])) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                $model->attributes = $_POST['Vatcodes'];
                if ($model->save()) {
                    if (isset($_POST['global']) && $_POST['global'] == 1) {
                        if ($model->is_global = 1) {
                            $branchVatecodeModel = Vatcodes::model()->findAllByAttributes(array('global_vatcodes_id' => $model->id));
                            foreach ($branchVatecodeModel as $branchVateCode) {
                                $branchVateCode->name = $model->name;
                                $branchVateCode->description = $model->description;
                                $branchVateCode->rate = $model->rate;
                                $branchVateCode->date = $model->date;
                                $branchVateCode->rate_alt = $model->rate_alt;
                                $branchVateCode->date_alt = $model->date_alt;
                                if (!$branchVateCode->save()) {
                                    throw new Exception(CHtml::errorSummary($branchVateCode, "", "", array('class' => 'customErrors')));
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
        $this->pageTitle = 'Vatcodes| eyMan';
        if (isset(Yii::app()->session['global_id'])) {
            unset(Yii::app()->session['global_id']);
        }
        $model = new Vatcodes('search');
        $model->unsetAttributes();
        if (isset($_GET['Vatcodes'])) {
            $model->attributes = $_GET['Vatcodes'];
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
        $this->pageTitle = 'Vatcodes| eyMan';
        if (isset(Yii::app()->session['company_id'])) {
            Yii::app()->session['global_id'] = Yii::app()->session['company_id'];
        }
        $model = new Vatcodes('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['vatcodes']))
            $model->attributes = $_GET['vatcodes'];
        $this->render('index', array(
            'model' => $model,
        ));
    }

    /**
     * Manages all models.
     */
    public function actionAdmin() {
        $model = new Vatcodes('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['Vatcodes']))
            $model->attributes = $_GET['Vatcodes'];

        $this->render('admin', array(
            'model' => $model,
        ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return Vatcodes the loaded model
     * @throws CHttpException
     */
    public function loadModel($id) {
        $model = Vatcodes::model()->findByPk($id);
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }

    /**
     * Performs the AJAX validation.
     * @param Vatcodes $model the model to be validated
     */
    protected function performAjaxValidation($model) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'vatcodes-form') {
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
