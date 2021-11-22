<?php

Yii::app()->clientScript->registerScript('invoicingHelpers', '
          eyMan = {
              urls: {
                  generateInvoices: ' . CJSON::encode(Yii::app()->createUrl('childInvoice/generateInvoices')) . ',
                  monthlyInvoiceAmount_invoiceSettings: ' . CJSON::encode(Yii::app()->createUrl('childInvoice/invoiceMonthlyAmountForMultipleChild')) . ',
                  sendInvoicesEmail: ' . CJSON::encode(Yii::app()->createUrl('childInvoice/sendInvoicesEmail')) . ',
									regenerateInvoices: ' . CJSON::encode(Yii::app()->createUrl('childInvoice/regenerateInvoices')) . ',
              }
          };
      ', CClientScript::POS_END);

class InvoiceSettingController extends eyManController {

	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout = '//layouts/systemSettings';

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
	public function actionCreate() {
		$model = new InvoiceSetting;
		$this->performAjaxValidation($model);
		if (isset($_POST['InvoiceSetting'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model->attributes = $_POST['InvoiceSetting'];
				if ($model->invoice_pdf_header_type == InvoiceSetting::COMPLETE_HEADER_IMAGE) {
					$uploadedFile = CUploadedFile::getInstance($model, 'invoice_pdf_header_image');
					if (!$model->validate()) {
						throw new Exception("Seems some problem saving the invoice settings.");
					}
					if ($uploadedFile) {
						$model->file_name = time() . '_' . uniqid() . '.' . $uploadedFile->extensionName;
						$model->invoice_header_image_raw = fopen($uploadedFile->tempName, 'r+');
						$model->uploadInvoiceHeaderImage();
					}
					$model->invoice_header_text = NULL;
					$model->invoice_header_color = NULL;
					$model->invoice_logo = NULL;
				} else {
					$model->invoice_pdf_header_image = NULL;
				}
				$model->branch_id = Yii::app()->session['branch_id'];
				if ($model->save()) {
					$transaction->commit();
					$this->redirect(array('update', 'id' => $model->id));
				}
			} catch (Exception $ex) {
				$transaction->rollback();
			}
		}

		$this->render('create', array(
			'model' => $model,
			'generateInvoicesModel' => $generateInvoicesModel
		));
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id) {
		$model = $this->loadModel($id);
		$generateInvoicesModel = new GenerateInvoicesForm;
		$sendInvoicesEmailModel = new SendInvoicesEmailForm;
		$regenerateInvoicesModel = new RegenerateInvoicesForm;
		$childPersonalDetailsModel = new ChildPersonalDetails;
		$this->performAjaxValidation($model);

		if (isset($_POST['InvoiceSetting'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model->attributes = $_POST['InvoiceSetting'];
				if ($model->invoice_pdf_header_type == InvoiceSetting::COMPLETE_HEADER_IMAGE) {
					$model->invoice_header_text = NULL;
					$model->invoice_header_color = NULL;
					$model->invoice_logo = NULL;
					$uploadedFile = CUploadedFile::getInstance($model, 'invoice_pdf_header_image');
					if ($uploadedFile) {
						$model->file_name = time() . '_' . uniqid() . '.' . $uploadedFile->extensionName;
						$model->invoice_header_image_raw = fopen($uploadedFile->tempName, 'r+');
						$model->uploadInvoiceHeaderImage();
					}
					if (!$model->validate()) {
						throw new Exception("Seems some problem updating the invoice Settings.");
					}
				} else {
					$model->invoice_pdf_header_image = NULL;
				}
				$model->branch_id = Yii::app()->session['branch_id'];

				if ($model->save()) {
					$transaction->commit();
					$this->redirect(array('update', 'id' => $model->id));
				}
			} catch (Exception $ex) {
				$transaction->rollback();
			}
		}

		$this->render('update', array(
			'model' => $model,
			'generateInvoicesModel' => $generateInvoicesModel,
			'childPersonalDetailsModel' => $childPersonalDetailsModel,
			'sendInvoicesEmailModel' => $sendInvoicesEmailModel,
			'regenerateInvoicesModel' => $regenerateInvoicesModel
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return InvoiceSetting the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id) {
		$model = InvoiceSetting::model()->findByPk($id);
		if ($model === null)
			throw new CHttpException(404, 'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param InvoiceSetting $model the model to be validated
	 */
	protected function performAjaxValidation($model) {
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'invoice-setting-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

}
