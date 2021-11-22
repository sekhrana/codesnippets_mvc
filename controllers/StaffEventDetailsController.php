<?php
Yii::app ()->clientScript->registerScript ( 'eventHelpers', '                                                           
          yii = {                                                                                                     
              urls: {                                                                                                 
                  staffEventData: ' . CJSON::encode ( Yii::app ()->createUrl ( 'eventType/getEventData' ) ) . ',    
                  staffCreateEvent: ' . CJSON::encode ( Yii::app ()->createUrl ( 'staffEventDetails/create' ) ) . ',
                  staffUpdateEventData: ' . CJSON::encode ( Yii::app ()->createUrl ( 'staffEventDetails/update' ) ) . ',
              }                                                                                                       
          };                                                                                                          
      ', CClientScript::POS_END );

Yii::app ()->clientScript->registerScript ( 'helpers', '                                                           
          eyMan = {                                                                                                     
              urls: {                                                                                                                                   
                  staffEventChangeStatus: ' . CJSON::encode ( Yii::app ()->createUrl ( 'staffEventDetails/changeStatus' ) ) . ' 
              }                                                                                                       
          };                                                                                                          
      ', CClientScript::POS_END );
Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseUrl . '/js/min-js/staffEventDetails.min.js?version=1.0.0', CClientScript::POS_END);
class StaffEventDetailsController extends eyManController {
	
	/**
	 *
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 *      using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout = '//layouts/dashboard';
	public function filters() {
		return array (
				'rights' 
		);
	}
	public function allowedActions() {
		return '';
	}
	
	/**
	 * Displays a particular model.
	 * 
	 * @param integer $id
	 *        	the ID of the model to be displayed
	 */
	public function actionView($id) {
		$this->render ( 'view', array (
				'model' => $this->loadModel ( $id ) 
		) );
	}
	
	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate() {
		$successCreate = array (
				'success' => 1,
				'message' => 'Event has been successfully created' 
		);
		$successUpdate = array (
				'success' => 1,
				'message' => 'Event has been successfully updated' 
		);
		if (isset ( $_POST ['StaffEventDetails'] ) && ! empty ( $_POST ['StaffEventDetails'] )) {
			if (isset ( $_POST ['event_staff_update_hidden'] ) && ! empty ( $_POST ['event_staff_update_hidden'] )) {
				$model = $this->loadModel ( $_POST ['event_staff_update_hidden'] );
				$model->attributes = $_POST ['StaffEventDetails'];
				if ($model->validate () && $model->save ()) {
					echo CJSON::encode ( $successUpdate );
				} else {
					echo CJSON::encode ( $model->getErrors () );
				}
			}
			if (empty ( $_POST ['event_staff_update_hidden'] )) {
				$model = new StaffEventDetails ();
				$model->attributes = $_POST ['StaffEventDetails'];
				$model->staff_id = $_POST ['event_staff_hidden'];
				if ($model->validate () && $model->save ()) {
					echo CJSON::encode ( $successCreate );
				} else {
					echo CJSON::encode ( $model->getErrors () );
				}
			}
		}
	}
	
	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * 
	 * @param integer $id
	 *        	the ID of the model to be updated
	 */
	public function actionUpdate($staff_id, $id, $event_id) {
		$staffEventData = StaffEventDetails::model ()->findByAttributes ( array (
				'staff_id' => $staff_id,
				'id' => $id 
		) );
		$eventData = $staffEventData->event;
		$eventArray = array ();
		foreach ( $eventData as $key => $value ) {
			$eventArray [$key] = $value;
		}
		$staffEventArray = array ();
		foreach ( $staffEventData as $key => $value ) {
			$staffEventArray [$key] = $value;
		}
		echo CJSON::encode ( array_merge ( $staffEventArray, $eventArray ) );
	}
	
	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * 
	 * @param integer $id
	 *        	the ID of the model to be deleted
	 */
	public function actionDelete() {
		if (isset ( $_POST ) && $_POST ['isAjaxRequest'] == 1) {
			$response = array (
					'status' => '1' 
			);
			$model = $this->loadModel ( $_POST ['id'] );
			$model->is_deleted = 1;
			if ($model->save ()) {
				$holidayEntitlementDetails = StaffHolidaysEntitlementDetails::model ()->findByAttributes ( array (
						'staff_event_id' => $model->id 
				) );
				$holidayEntitlementDetails->is_deleted = 1;
				$holidayEntitlementDetails->save ();
				echo CJSON::encode ( $response );
			} else {
				$response = array (
						'status' => '0' 
				);
				echo CJSON::encode ( $response );
			}
		}
	}
	
	/**
	 * Lists all models.
	 */
	public function actionIndex($staff_id) {
		$this->pageTitle = 'Event Type | eyMan';
		$model = new StaffEventDetails ( 'search' );
		$model->unsetAttributes ();
		if (isset ( $_GET ['StaffEventDetails'] ))
			$model->attributes = $_GET ['StaffEventDetails'];
		
		$this->render ( 'index', array (
				'model' => $model 
		) );
	}
	
	/**
	 * Manages all models.
	 */
	public function actionAdmin() {
		$model = new StaffEventDetails ( 'search' );
		$model->unsetAttributes (); // clear any default values
		if (isset ( $_GET ['StaffEventDetails'] ))
			$model->attributes = $_GET ['StaffEventDetails'];
		
		$this->render ( 'admin', array (
				'model' => $model 
		) );
	}
	
	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * 
	 * @param integer $id
	 *        	the ID of the model to be loaded
	 * @return StaffEventDetails the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id) {
		$model = StaffEventDetails::model ()->findByPk ( $id );
		if ($model === null)
			throw new CHttpException ( 404, 'The requested page does not exist.' );
		return $model;
	}
	
	/**
	 * Performs the AJAX validation.
	 * 
	 * @param StaffEventDetails $model
	 *        	the model to be validated
	 */
	protected function performAjaxValidation($model) {
		if (isset ( $_POST ['ajax'] ) && $_POST ['ajax'] === 'staff-event-details-form') {
			echo CActiveForm::validate ( $model );
			Yii::app ()->end ();
		}
	}
	public function actionChangeStatus() {
		if (Yii::app ()->request->isAjaxRequest) {
			$model = StaffEventDetails::model ()->findByPk ( Yii::app ()->request->getPost ( 'id' ) );
			if (! empty ( $model )) {
				if ($model->status == StaffEventDetails::COMPLETED) {
					echo CJSON::encode ( array (
							'status' => 1,
							"message" => "Event has been already marked as completed." 
					) );
					Yii::app ()->end ();
				}
				$model->status = StaffEventDetails::COMPLETED;
				if ($model->save ())
					echo CJSON::encode ( array (
							'status' => 1,
							"message" => "Event has been successfully marked as completed." 
					) );
				else
					echo CJSON::encode ( array (
							'status' => 0,
							"message" => "Theri seems to be some problem changing the status." 
					) );
			}
		} else {
			throw new CHttpException ( 404, "This request is not valid." );
		}
	}
}
