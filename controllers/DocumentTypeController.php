<?php

class DocumentTypeController extends eyManController {

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
        $this->pageTitle = "Child/Staff Document Type";
        $model = new DocumentType;
        $branch = new Branch;
        $this->performAjaxValidation($model);
        if (isset($_POST['DocumentType'])) {
            $model->attributes = $_POST['DocumentType'];
            $model->branch_id = Yii::app()->session['branch_id'];
            if (isset($_POST['global']) && $_POST['global'] == 1) {
                $model->is_global = 1;
                $model->global_id = Yii::app()->session['company_id'];
                $model->branch_id = $branch->createBranchByGlobalId(Yii::app()->session['company_id']);
            }
            if ($model->save()) {
                $global_document_id = $model->id;
                if (isset($_POST['global']) && $_POST['global'] == 1) {
                    if (isset($_POST['DocumentType']['create_for_existing']) && $_POST['DocumentType']['create_for_existing'] == 1) {
                        $branchModel = Branch::model()->findAllByAttributes(array('is_active' => 1, 'company_id' => Yii::app()->session['company_id']));
                        if (!empty($branchModel)) {
                            foreach ($branchModel as $branch) {
                                $model->isNewRecord = true;
                                $model->id = null;
                                $model->branch_id = $branch->id;
                                $model->attributes = $_POST['DocumentType'];
                                $model->is_global = 0;
                                $model->global_id = Yii::app()->session['company_id'];
                                $model->create_for_existing = 0;
                                $model->global_document_id = $global_document_id;
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
        if ($global == 1) {
            $this->layout = 'global';
        }
        $this->pageTitle = "Child/Staff Document Type";
        $model = $this->loadModel($id);
        $this->performAjaxValidation($model);
        if (isset($_POST['DocumentType'])) {
            $model->attributes = $_POST['DocumentType'];
            if ($model->save()) {
                if (isset($_POST['global']) && $_POST['global'] == 1) {
                    if($model->is_global = 1) {
                      $branchDocumentsModel = DocumentType::model()->findAllByAttributes(array('global_document_id' => $model->id));
                      foreach($branchDocumentsModel as $branchDocument) {
                          $branchDocument->name = $model->name;
                          $branchDocument->description = $model->description;
                          $branchDocument->title_date_1 = $model->title_date_1;
                          $branchDocument->title_date_2 = $model->title_date_2;
                          $branchDocument->title_description = $model->title_description;
                          $branchDocument->title_notes = $model->title_notes;
                          $branchDocument->for_staff = $model->for_staff;
                          $branchDocument->for_child = $model->for_child;
                          $branchDocument->save();
                      }
                    }
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
        if (isset(Yii::app()->session['global_id'])) {
            unset(Yii::app()->session['global_id']);
        }
        $this->pageTitle = 'Document Type | eyMan';
        $model = new DocumentType('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['DocumentType']))
            $model->attributes = $_GET['DocumentType'];

        $this->render('index', array(
            'model' => $model,
        ));
    }

    /**
     * Lists all models.
     */
    public function actionGlobal() {
        $this->layout = 'global';
        $this->pageTitle = 'Document Type | eyMan';
        if (isset(Yii::app()->session['company_id'])) {
            Yii::app()->session['global_id'] = Yii::app()->session['company_id'];
        }
        $model = new DocumentType('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['DocumentType']))
            $model->attributes = $_GET['DocumentType'];

        $this->render('index', array(
            'model' => $model,
        ));
    }

    public function actionGetDocumentData() {
        if (isset($_POST) && !empty($_POST))
            if ($_POST['isAjaxRequest'] == true && isset($_POST['id'])) {
                $model = DocumentType::model()->findByPk($_POST['id']);
                echo CJSON::encode($model);
            }
    }

    /**
     * Manages all models.
     */
    public function actionAdmin() {
        $model = new DocumentType('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['DocumentType']))
            $model->attributes = $_GET['DocumentType'];

        $this->render('admin', array(
            'model' => $model,
        ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return DocumentType the loaded model
     * @throws CHttpException
     */
    public function loadModel($id) {
        $model = DocumentType::model()->findByPk($id);
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }

    /**
     * Performs the AJAX validation.
     * @param DocumentType $model the model to be validated
     */
    protected function performAjaxValidation($model) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'document-type-form') {
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
