<?php

class HrSettingController extends eyManController {

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

    public function actionView() {
        $branchId = Yii::app()->session['branch_id'];
        $model = HrSetting::model()->findByAttributes(['branch_id' => $branchId]);
        if (empty($model)) {
            $this->redirect(array('hrSetting/create'));
        }
        $this->render('index', array(
            'model' => $model,
        ));
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate() {
        $this->pageTitle = 'Create HR Setting | eyMan';
        $model = new HrSetting;
        $this->performAjaxValidation($model);
        if (isset($_POST['HrSetting'])) {
            $model->attributes = $_POST['HrSetting'];
            $model->branch_id = Yii::app()->session['branch_id'];
            if ($model->save()) {
                $this->redirect(array('hrSetting/view'));
            }
        }
        $this->render('index', array(
            'model' => $model,
        ));
    }

    /**
     * Performs the AJAX validation.
     * @param Branch $model the model to be validated
     */
    protected function performAjaxValidation($model) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'hrsetting-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionUpdate() {
        $this->pageTitle = 'Update HR Setting | eyMan';
        $branchId = Yii::app()->session['branch_id'];
        $model = HrSetting::model()->findByAttributes(['branch_id' => $branchId]);
       
        $this->performAjaxValidation($model);
        if (isset($_POST['HrSetting'])) {
            $model->attributes = $_POST['HrSetting'];
            if ($model->save()) {
                $this->redirect(array('hrSetting/view'));
            } else {
                print_r($model->getErrors());
                
            }
        }
        $this->render('index', array(
            'model' => $model,
        ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return Branch the loaded model
     * @throws CHttpException
     */
    public function loadModel($id) {
        $model = HrSetting::model()->findByPk($id);
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }

}
