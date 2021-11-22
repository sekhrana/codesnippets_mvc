<?php

Yii::app()->clientScript->registerScript('eventHelpers', '                                                           
          yii = {                                                                                                     
              urls: {                                                                                                 
                  childEventData: ' . CJSON::encode(Yii::app()->createUrl('eventType/getEventData')) . ',    
                  childCreateEvent: ' . CJSON::encode(Yii::app()->createUrl('childEventDetails/create')) . ',
                  childUpdateEventData: ' . CJSON::encode(Yii::app()->createUrl('childEventDetails/update')) . ',
              }                                                                                                       
          };                                                                                                          
      ', CClientScript::POS_END);

Yii::app()->clientScript->registerScript('helpers', '                                                           
          eyMan = {                                                                                                     
              urls: {                                                                                                                                   
                  childEventChangeStatus: '.CJSON::encode(Yii::app()->createUrl('childEventDetails/changeStatus')).' 
              }                                                                                                       
          };                                                                                                          
      ', CClientScript::POS_END);
Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseUrl . '/js/min-js/childEventDetails.min.js?version=1.0.0', CClientScript::POS_END);
class ChildEventDetailsController extends eyManController {

    /**
     * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
     * using two-column layout. See 'protected/views/layouts/column2.php'.
     */
    public $layout = '//layouts/dashboard';

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
    public function actionView($child_id) {
        $this->pageTitle = 'Event Type | eyMan';
        $model = new ChildEventDetails('search');
        $model->unsetAttributes();
        if (isset($_GET['ChildEventDetails']))
            $model->attributes = $_GET['ChildEventDetails'];

        $this->render('view', array(
            'model' => $model,
        ));
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate() {
        $successCreate = array('success' => 1, 'message' => 'Event has been successfully created');
        if (isset($_POST['ChildEventDetails']) && !empty($_POST['ChildEventDetails'])) {
            $model = new ChildEventDetails;
            $model->attributes = $_POST['ChildEventDetails'];
            $model->child_id = $_POST['event_child_hidden'];
            if ($model->validate() && $model->save()) {
                echo CJSON::encode($successCreate);
            } else {
                echo CJSON::encode($model->getErrors());
            }
        }
    }

    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id the ID of the model to be updated
     */
    public function actionUpdate() {
        $response = array('status' => 1, 'message' => 'Event has been successfully updated.');
        if (isset($_POST)) {
            $childEventModel = $this->loadModel($_POST['event_child_update_hidden']);
            $childEventModel->attributes = $_POST['ChildEventDetails'];
            if ($childEventModel->validate() && $childEventModel->save()) {
                echo CJSON::encode($response);
            } else {
                echo CJSON::encode($childEventModel->getErrors());
            }
        } else {
            throw new CHttpException(404, "Your request is not valid");
        }
    }

    public function actionGetEventData() {
        if (($_POST['isAjaxRequest'] == 1) && isset($_POST['id'])) {
            $eventModel = ChildEventDetails::model()->findByPk($_POST['id']);
            if (!empty($eventModel)) {
                $eventData = $eventModel->event;
                $eventArray = array();
                foreach ($eventData as $key => $value) {
                    $eventArray[$key] = $value;
                }
                $childEventArray = array();
                foreach ($eventModel as $key => $value) {
                    $childEventArray[$key] = $value;
                }
                echo CJSON::encode(array_merge($childEventArray, $eventArray));
            }
        } else {
            throw new CHttpException(404, 'Your request is not valid');
        }
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
    public function actionIndex($child_id) {
        $this->pageTitle = 'Event Type | eyMan';
        $model = new ChildEventDetails('search');
        $model->unsetAttributes();
        if (isset($_GET['ChildEventDetails']))
            $model->attributes = $_GET['ChildEventDetails'];

        $this->render('index', array(
            'model' => $model,
        ));
    }

    /**
     * Manages all models.
     */
    public function actionAdmin() {
        $model = new ChildEventDetails('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['ChildEventDetails']))
            $model->attributes = $_GET['ChildEventDetails'];

        $this->render('admin', array(
            'model' => $model,
        ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return ChildEventDetails the loaded model
     * @throws CHttpException
     */
    public function loadModel($id) {
        $model = ChildEventDetails::model()->findByPk($id);
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }

    /**
     * Performs the AJAX validation.
     * @param ChildEventDetails $model the model to be validated
     */
    protected function performAjaxValidation($model) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'child-event-details-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }
    
    public function actionChangeStatus() {
        if (Yii::app()->request->isAjaxRequest) {
            $model = ChildEventDetails::model()->findByPk(Yii::app()->request->getPost('id'));
            if (!empty($model)) {
                if ($model->status == ChildEventDetails::COMPLETED) {
                    echo CJSON::encode(array('status' => 1, "message" => "Event has been already marked as completed."));
                    Yii::app()->end();
                }
                $model->status = ChildEventDetails::COMPLETED;
                if ($model->save())
                    echo CJSON::encode(array('status' => 1, "message" => "Event has been successfully marked as completed."));
                else 
                    echo CJSON::encode(array('status' => 0, "message" => "Theri seems to be some problem changing the status."));
            }
        } else {
            throw new CHttpException(404, "This request is not valid.");
        }
    }

}
