<?php

class PaymentsController extends eyManController {

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
	public function actionView($id) {
		$model = $this->loadModel($id);
		$model->child_id = explode(",", $model->child_id);
		$this->render('view', array(
			'model' => $model
		));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate() {
		$model = new Payments('createPayment');
		$this->performAjaxValidation($model);
		if (isset($_POST['Payments'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model->attributes = $_POST['Payments'];
				$model->child_id = (is_array($_POST['Payments']['child_id'])) ? implode(",", $_POST['Payments']['child_id']) : NULL;
				$model->branch_id = Yii::app()->session['branch_id'];
				if ($model->save()) {
					$transaction->commit();
					if (isset($_POST['Save_Allocate'])) {
						$this->redirect(array('allocatePayment', 'id' => $model->id));
					}
					$this->redirect(array('index'));
				} else {
					throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				Yii::app()->user->setFlash($ex->getMessage());
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
	public function actionUpdate($id) {
		$model = $this->loadModel($id);
		$model->setScenario('updatePayment');
		$model->child_id = (isset($model->child_id) && !empty($model->child_id)) ? explode(",", $model->child_id) : NULL;
		$this->performAjaxValidation($model);
		if (isset($_POST['Payments'])) {
			$model->attributes = $_POST['Payments'];
			$model->child_id = (isset($_POST['Payments']['child_id']) && !empty($_POST['Payments']['child_id'])) ? implode(",", $_POST['Payments']['child_id']) : NULL;
			$checkTransactions = PaymentsTransactions::model()->findAllByAttributes(array('payment_id' => $id));
			if (!empty($checkTransactions)) {
				Yii::app()->user->setFlash('error', "Payment can not be updated as it has been allocated.");
				$this->refresh();
			}
			if ($model->save())
				$this->redirect(array('view', 'id' => $model->id));
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
		if (Yii::app()->request->isAjaxRequest) {
			if(!(Yii::app()->user->checkAccess('Payments.*') && Yii::app()->user->checkAccess('Payments.Delete'))){
				throw new CHttpException(401, 'You are not allowed to access this resource.');
			}
			$checkPaymentTransactions = PaymentsTransactions::model()->findAllByAttributes(["payment_id" => Yii::app()->request->getPost("id")]);
			if (!empty($checkPaymentTransactions)) {
				echo CJSON::encode(array('status' => '0', 'message' => 'Payment can not be deleted as it has been already processed.'));
				Yii::app()->end();
			}
			if (!in_array(Yii::app()->session["role"], array("accountsAdmin", "superAdmin", "areaManager", "branchManager"))) {
				echo CJSON::encode(array('status' => '0', 'message' => 'Only Accounts administrator are allowed to delete the payments.'));
				Yii::app()->end();
			}
			$response = array('status' => '1');
			$model = $this->loadModel($_POST['id']);
			$model->is_deleted = 1;
			if ($model->save()) {
				echo CJSON::encode($response);
			} else {
				$response = array('status' => '0');
				echo CJSON::encode($response);
			}
		} else {
			throw new CHttpException(404, "This request is not valid.");
		}
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex() {
		$this->pageTitle = 'Payments | eyMan';
		$model = new Payments('search');
		$model->unsetAttributes();
		$model->status = Payments::NOT_ALLOCATED;
		if (isset($_GET['Payments']))
			$model->attributes = $_GET['Payments'];
		$this->render('index', array(
			'model' => $model,
		));
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin() {
		$model = new Payments('search');
		$model->unsetAttributes();	// clear any default values
		if (isset($_GET['Payments']))
			$model->attributes = $_GET['Payments'];

		$this->render('admin', array(
			'model' => $model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return Payments the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id) {
		$model = Payments::model()->findByPk($id);
		if ($model === null)
			throw new CHttpException(404, 'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param Payments $model the model to be validated
	 */
	protected function performAjaxValidation($model) {
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'payments-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	public function actionAllocatePayment($id) {
		$model = $this->loadModel($id);
		if ($model->child_id == NULL) {
			Yii::app()->user->setFlash("error", "Plese assign the payment to atleast on child.<a href=" . $this->createUrl('payments/update', array('id' => $id)) . ">Click here</a>");
		}
		$transactionModel = new PaymentsTransactions;
		$creditNoteModel = new ChildInvoice;
		$creditNoteModel->scenario = "credit_note";
		$creditNoteModel->is_money_received = 1;
		$criteria = new CDbCriteria();
		$criteria->condition = "(status = :status) AND is_regenrated = 0";
		$criteria->params = array(':status' => "AWAITING_PAYMENT");
		$criteria->addInCondition("child_id", explode(",", $model->child_id), "AND");
		$invoiceModel = ChildInvoice::model()->findAll($criteria);
		$invoiceGridArray = array();
		$paymentsTransactions = new CActiveDataProvider('PaymentsTransactions', array(
			'criteria' => array(
				'condition' => 'payment_id = :payment_id',
				'order' => 'id DESC',
				'params' => array(':payment_id' => $model->id)
			),
			'pagination' => array(
				'pageSize' => 20,
			),
		));
		foreach ($invoiceModel as $invoice) {
			$temp = array();
			$temp['id'] = $invoice->id;
			$temp['child_name'] = ChildPersonalDetails::model()->allowed()->findByPk($invoice->child_id)->name;
			$temp['invoice_date'] = $invoice->invoice_date;
			$temp['amount'] = $invoice->total;
			$temp['balance'] = customFunctions::getDueAmount($invoice->id);
			$invoiceGridArray[] = $temp;
		}
		$this->performAjaxValidation($model);
		if (isset($_POST['Allocate_Payment'])) {
			if (isset($_POST['invoice_checkbox'])) {
				$transaction = Yii::app()->db->beginTransaction();
				try {
					$flag = true;
					foreach ($_POST['invoice_checkbox'] as $key => $value) {
						if ($value == 1) {
							$invoice = ChildInvoice::model()->findByPk($key);
							$payment = Payments::model()->findByPk($id);
							if (!empty($invoice) && !empty($payment)) {
								$transactionAmount = 0;
								$payment_balance = customFunctions::getPaymentBalance($payment->id, $payment->amount);
								$paid_amount = $payment->amount;
								$invoiceDueAmount = customFunctions::getDueAmount($invoice->id);
								$invoiceAllocatedAmount = sprintf("%0.2f", $_POST['invoice_field'][$key]);
								if (($invoiceAllocatedAmount > $invoiceDueAmount) || ($invoiceAllocatedAmount > $payment_balance)) {
									throw new Exception("Allocated amount should be smaller than payment balance and invoice balance'");
								}
								$transactionAmount = $invoiceAllocatedAmount;
								$invoiceTransactionModel = new ChildInvoiceTransactions;
								$invoiceTransactionModel->invoice_id = $invoice->id;
								$invoiceTransactionModel->payment_refrence = $payment->payment_reference;
								$invoiceTransactionModel->invoice_amount = $invoice->total;
								$invoiceTransactionModel->paid_amount = $transactionAmount;
								$invoiceTransactionModel->date_of_payment = $payment->date_of_payment;
								$invoiceTransactionModel->payment_mode = $payment->payment_mode;
								if ($invoiceTransactionModel->save()) {
									$paymentTransactionModel = new PaymentsTransactions;
									$paymentTransactionModel->invoice_id = $invoice->id;
									$paymentTransactionModel->payment_id = $payment->id;
									$paymentTransactionModel->paid_amount = $transactionAmount;
									if ($paymentTransactionModel->save()) {
										$invoiceTransactionModel->payment_id = $paymentTransactionModel->id;
										$invoiceTransactionModel->save();
										$invoiceDueAmount = sprintf("%0.2f", customFunctions::getDueAmount($invoice->id));
										$paymentBalanceAmount = sprintf("%0.2f", customFunctions::getPaymentBalance($payment->id, $payment->amount));
										if ($paymentBalanceAmount == 0) {
											$payment->status = 1;
											$payment->save();
											if ($invoiceDueAmount == 0) {
												$invoice->status = "PAID";
												if ($invoice->save()) {
													$transaction->commit();
													Yii::app()->user->setFlash('success', 'Payment has been successfully processed');
													$this->refresh();
												} else {
													throw new Exception(CHtml::errorSummary($invoice));
												}
											} else {
												$transaction->commit();
												Yii::app()->user->setFlash('success', 'Payment has been successfully processed');
												$this->refresh();
											}
										} else {
											if ($invoiceDueAmount == 0) {
												$invoice->status = "PAID";
												if (!$invoice->save()) {
													$flag = flase;
												}
											}
										}
									} else {
										throw new Exception(CHtml::errorSummary($paymentTransactionModel));
									}
								} else {
									throw new Exception(CHtml::errorSummary($invoiceTransactionModel));
								}
							}
						}
					}
					if ($flag) {
						$transaction->commit();
						Yii::app()->user->setFlash('success', 'Payment has been successfully processed');
						$this->refresh();
					} else {
						Yii::app()->user->setFlash('success', 'Their seems to be some problem processing the payments.');
						$this->refresh();
					}
				} catch (Exception $ex) {
					$transaction->rollback();
					Yii::app()->user->setFlash('error', $ex->getMessage());
					$this->refresh();
					Yii::app()->end();
				}
			} else {
				Yii::app()->user->setFlash('error', "Please select at least one invoice.");
				$this->refresh();
				Yii::app()->end();
			}
		}

		$this->render('allocatePayment', array(
			'model' => $model,
			'transactionModel' => $transactionModel,
			'invoiceModel' => $invoiceModel,
			'invoiceGridArray' => $invoiceGridArray,
			'creditNoteModel' => $creditNoteModel,
			'paymentsTransactions' => $paymentsTransactions
		));
	}

	public function actionGetInvoiceData() {
		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1)) {
			if (isset($_POST['id']) && !empty($_POST['id'])) {
				$model = ChildInvoice::model()->findByPk($_POST['id']);
				$due_amount = customFunctions::getDueAmount($model->id);
				echo CJSON::encode(array('status' => 1, 'invoice_amount' => $model->total, 'invoice_balance' => $due_amount));
			} else {
				echo CJSON::encode(array('status' => 0, 'invoice_amount' => "", 'invoice_balance' => ""));
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	public function actionAllocateCreditNote() {
		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1)) {
			$paymentModel = Payments::model()->findByPk($_POST['payment_id']);
			$creditNoteModel = new ChildInvoice;
			$creditNoteModel->setScenario("credit_note");
			if (!empty($paymentModel)) {
				$transaction = Yii::app()->db->beginTransaction();
				try {
					$paymentBalance = customFunctions::getPaymentBalance($paymentModel->id, $paymentModel->amount);
					if (sprintf("%0.2f", $paymentBalance) < sprintf("%0.2f", $_POST['ChildInvoice']['total'])) {
						echo CJSON::encode(array('status' => 2, 'errors' => array('total' => array('Allocated amount should be smaller or equal to Payment amount.'))));
					} else {
						$creditNoteModel->attributes = $_POST['ChildInvoice'];
						$creditNoteModel->branch_id = Yii::app()->session['branch_id'];
						$creditNoteModel->status = ChildInvoice::NOT_ALLOCATED;
						$creditNoteModel->invoice_type = ChildInvoice::CREDIT_NOTE;
						$urn = customFunctions::getInvoiceUrn(Yii::app()->session['branch_id']);
						$creditNoteModel->urn_prefix = $urn['prefix'];
						$creditNoteModel->urn_number = $urn['number'];
						$creditNoteModel->urn_suffix = $urn['suffix'];
						$creditNoteModel->access_token = md5(time() . uniqid() . $urn);
						$creditNoteModel->due_date = $creditNoteModel->invoice_date;
						$creditNoteModel->total = $creditNoteModel->total;
						$creditNoteModel->is_money_received = 1;
						if ($creditNoteModel->validate()) {
							$creditNoteModel->total = -$creditNoteModel->total;
							$creditNoteModel->save();
							$paymentTransactionModal = new PaymentsTransactions;
							$paymentTransactionModal->payment_id = $paymentModel->id;
							$paymentTransactionModal->invoice_id = $creditNoteModel->id;
							$paymentTransactionModal->paid_amount = (-$creditNoteModel->total);
							if ($paymentTransactionModal->save()) {
								$creditNoteModel->credit_note_payment_id = $paymentTransactionModal->id;
								$creditNoteModel->save();
								if (sprintf("%0.2f", customFunctions::getPaymentBalance($paymentModel->id, $paymentModel->amount)) == 0) {
									$paymentModel->status = 1;
									$paymentModel->save();
								}
								$transaction->commit();
								echo CJSON::encode(array('status' => 1, 'message' => 'Payment has been allocated successfully.'));
							} else {
								echo CJSON::encode(array('status' => 0, 'message' => 'Their seems to be some problem.'));
							}
						} else {
							echo CJSON::encode(array('status' => 2, 'errors' => $creditNoteModel->getErrors()));
							Yii::app()->end();
						}
					}
				} catch (Exception $ex) {
					echo CJSON::encode(array('status' => 0, 'message' => 'Their seems to be some problem.'));
				}
			} else {
				echo CJSON::encode(array('status' => 0, 'message' => 'Their seems to be some problem.'));
			}
		} else {
			throw new CHttpException(404, "Your request is not valid");
		}
	}

	public function actionMarkPayments() {
		$model = ChildInvoice::model()->findAllByAttributes(array('description' => "Opening Balance", 'invoice_type' => 3));
		foreach ($model as $credit) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$paymentsModel = new Payments;
				$paymentsModel->attributes = $credit->attributes;
				$paymentsModel->date_of_payment = $credit->invoice_date;
				$paymentsModel->payment_reference = $credit->description;
				$paymentsModel->amount = -$credit->total;
				$paymentsModel->status = Payments::ALLOCATED;
				$paymentsModel->payment_mode = 9;
				$paymentsModel->child_id = $credit->child_id;
				if ($paymentsModel->save()) {
					$credit->credit_note_payment_id = $paymentsModel->id;
					if ($credit->save()) {
						$paymentTransactionModel = new PaymentsTransactions;
						$paymentTransactionModel->payment_id = $paymentsModel->id;
						$paymentTransactionModel->invoice_id = $credit->id;
						$paymentTransactionModel->paid_amount = $paymentsModel->amount;
						if ($paymentTransactionModel->save()) {
							echo "Credit Note with ID - $credit->id succesfully transferred" . "</br>";
							$transaction->commit();
						} else {
							throw new Exception("Credit Note with ID - $credit->id not saved. 3");
						}
					} else {
						throw new Exception("Credit Note with ID - $credit->id not saved. 2");
					}
				} else {
					throw new Exception("Credit Note with ID - $credit->id not saved. 1");
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				echo $ex->getMessage() . "</br>";
			}
		}
	}

	public function actionCreateCreditNote() {
		$this->layout = "dashboard";
		$model = new ChildInvoice;
		$model->setScenario('credit_note');
		if (Yii::app()->session['role'] == "branchAdmin") {
			throw new CHttpException(404, 'You are not allowed to access this page.');
		}
		if (isset($_POST['ChildInvoice']) && isset($_POST['Save'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model->attributes = $_POST['ChildInvoice'];
				$paymentModel = new Payments;
				$paymentModel->child_id = $_POST['ChildInvoice']['child_id'];
				$paymentModel->branch_id = Branch::currentBranch()->id;
				$paymentModel->amount = $model->total;
				$paymentModel->date_of_payment = $model->invoice_date;
				$paymentModel->payment_reference = $model->description;
				$paymentModel->payment_mode = 9;
				$paymentModel->status = Payments::ALLOCATED;
				if ($paymentModel->save()) {
					$model->branch_id = $paymentModel->branch_id;
					$model->is_money_received = 0;
					$model->child_id = $_POST['ChildInvoice']['child_id'];
					$model->status = 'NOT_ALLOCATED';
					$model->invoice_type = ChildInvoice::CREDIT_NOTE;
					$urn = customFunctions::getInvoiceUrn($paymentModel->branch_id);
					$model->urn_prefix = $urn['prefix'];
					$model->urn_number = $urn['number'];
					$model->urn_suffix = $urn['suffix'];
					$model->access_token = md5(time() . uniqid() . $urn);
					$model->due_date = $model->invoice_date;
					if ($model->total <= 0) {
						throw new Exception("Credit Note of value should be greater than zero.");
					}
					$model->total = -$model->total;
					if ($model->save()) {
						$paymentTransactionModel = new PaymentsTransactions;
						$paymentTransactionModel->payment_id = $paymentModel->id;
						$paymentTransactionModel->invoice_id = $model->id;
						$paymentTransactionModel->paid_amount = $paymentModel->amount;
						if ($paymentTransactionModel->save()) {
							$model->credit_note_payment_id = $paymentTransactionModel->id;
							if (!$model->save()) {
								throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
							}
							$transaction->commit();
							$this->redirect(array('payments/index'));
						} else {
							throw new Exception(CHtml::errorSummary($paymentTransactionModel, "", "", array('class' => 'customErrors')));
						}
					} else {
						throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
					}
				} else {
					throw new Exception(CHtml::errorSummary($paymentModel, "", "", array('class' => 'customErrors')));
				}
			} catch (Exception $ex) {
				$transaction->rollback();
				Yii::app()->user->setFlash('error', $ex->getMessage());
				$this->refresh();
			}
		}
		$this->render('createCreditNote', array(
			'model' => $model,
		));
	}

}
