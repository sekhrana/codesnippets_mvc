<?php

class StaffGeneralDetailsController extends eyManController {

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
    public function actionCreate($staff_id) {
        $this->layout = 'dashboard';
        $this->pageTitle = 'Create Staff | eyMan';

        if (isset($_GET['staff_id']) && !isset($_POST['Previous'])) {
            $staffPersonalDetails = StaffPersonalDetails::model()->findByPk($_GET['staff_id']);

            if (empty($staffPersonalDetails))
                throw new CHttpException(400, "No staff found for the above id");

            if (!empty($staffPersonalDetails)) {
                $model = new StaffGeneralDetails;
                $this->performAjaxValidation($model);
                if (isset($_POST['StaffGeneralDetails']) && isset($_POST['Save']) && !isset($_POST['Next'])) {
                    try {
                        $model->attributes = $_POST['StaffGeneralDetails'];
                        $model->staff_id = $staff_id;
                        if ($model->save()) {

                            $this->redirect(array('update', 'staff_id' => $staff_id, 'general_id' => $model->id));
                        }
                    } catch (Exception $ex) {
                        echo $model->getErrors();
                    }
                }

                if (isset($_POST['StaffGeneralDetails']) && isset($_POST['Next']) && !isset($_POST['Save'])) {
                    try {
                        $model->attributes = $_POST['StaffGeneralDetails'];
                        $model->staff_id = $staff_id;
                        if ($model->save()) {
                            $bankDetails = StaffBankDetails::model()->findByAttributes(array('staff_id' => $staff_id));
                            if (empty($bankDetails))
                                $this->redirect(array('staffBankDetails/create', 'staff_id' => $staff_id));

                            if (!empty($bankDetails))
                                $this->redirect(array('staffBankDetails/update', 'staff_id' => $staff_id, 'bank_id' => $bankDetails->id));
                        }
                    } catch (Exception $ex) {
                        echo $model->getErrors();
                    }
                }
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
    public function actionUpdate($staff_id, $general_id) {

        $this->layout = 'dashboard';
        $model = $this->loadModel($general_id);
        $this->performAjaxValidation($model);

        if (isset($_POST['StaffGeneralDetails']) && isset($_POST['Update']) && !isset($_POST['Previous'])) {
            if(Yii::app()->session['role'] == "staff"){
                throw new CHttpException(404, 'You are not authorized to perform this action');
            }
            $model->attributes = $_POST['StaffGeneralDetails'];
            if ($model->save())
                $this->redirect(array('update', 'staff_id' => $staff_id, 'general_id' => $general_id));
        }

        if (isset($_POST['StaffGeneralDetails']) && isset($_POST['Next']) && !isset($_POST['Previous'])) {
            if(Yii::app()->session['role'] == "staff"){
                throw new CHttpException(404, 'You are not authorized to perform this action');
            }
            $model->attributes = $_POST['StaffGeneralDetails'];
            if ($model->save()) {
                $bankDetails = StaffBankDetails::model()->findByAttributes(array('staff_id' => $staff_id));
                if (empty($bankDetails))
                    $this->redirect(array('staffBankDetails/create', 'staff_id' => $staff_id));

                if (!empty($bankDetails))
                    $this->redirect(array('staffBankDetails/update', 'staff_id' => $staff_id, 'bank_id' => $bankDetails->id));
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
    public function actionDelete($id) {
        $this->loadModel($id)->delete();

        // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
        if (!isset($_GET['ajax']))
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
    }

    /**
     * Lists all models.
     */
    public function actionIndex() {
        $dataProvider = new CActiveDataProvider('StaffGeneralDetails');
        $this->render('index', array(
            'dataProvider' => $dataProvider,
        ));
    }

    /**
     * Manages all models.
     */
    public function actionAdmin() {
        $model = new StaffGeneralDetails('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['StaffGeneralDetails']))
            $model->attributes = $_GET['StaffGeneralDetails'];

        $this->render('admin', array(
            'model' => $model,
        ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return StaffGeneralDetails the loaded model
     * @throws CHttpException
     */
    public function loadModel($id) {
        $model = StaffGeneralDetails::model()->findByPk($id);
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }

    /**
     * Performs the AJAX validation.
     * @param StaffGeneralDetails $model the model to be validated
     */
    protected function performAjaxValidation($model) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'staff-general-details-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

}
