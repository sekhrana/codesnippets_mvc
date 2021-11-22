<?php

use \Firebase\JWT\JWT;

Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseUrl . '/js/goCardless.js?version=1.0.19', CClientScript::POS_END);

class PaymentGatewaySettingsController extends eyManController {

	public $layout = '//layouts/systemSettings';
	private $_goCardlessAccount = null;

	public function beforeAction($action) {
		if (in_array($action->id, array('gcremove'))) {
			$this->findGcAccount();
		}
		return parent::beforeAction($action);
	}

	public function actionGccustomers($after = null, $before = null) {
		$user = $this->getUser();
		// Fetching GoCardless OAuth Client for current Instance
		$gcCustomerClient = customFunctions::getCustomerGoCardlessClient();
		$goCardlessAccount = $user->getGlobalGoCardlessAccount();
		if ($goCardlessAccount) {
			$params = array('limit' => 50);
			if ($after != null) {
				$params['after'] = $after;
			} elseif ($before != null) {
				$params['before'] = $before;
			}
			$gcCustomers = $gcCustomerClient->customers()->list(array('params' => $params));
			$customersList = array();
			$customerIds = array();
			foreach ($gcCustomers->records as $customer) {
				$customerIds[] = $customer->id;
				$customersList[$customer->id] = $customer;
			}
			$pageAfter = $gcCustomers->after;
			$pageBefore = $gcCustomers->before;
		} else {
			$customersList = array();
			$pageAfter = "";
			$pageBefore = "";
		}

		$this->render('gccustomers', array(
			'user' => $user,
			'goCardlessAccount' => $goCardlessAccount,
			'customers' => $customersList,
			'pageAfter' => $pageAfter,
			'pageBefore' => $pageBefore,
		));
	}

	public function actionIndex() {
		$user = $this->getUser();
		$goCardlessAccount = $user->getGlobalGoCardlessAccount();
		$model = new Parents('search');
		$model->unsetAttributes(); // clear any default values
		if (isset($_GET['Parents']))
			$model->attributes = $_GET['Parents'];
		$this->render('index', array(
			'model' => $model,
			'user' => $user,
			'goCardlessAccount' => $goCardlessAccount,
		));
	}

	/**
	 * Action to connect user's GC Account
	 * Redirects user to GC
	 */
	public function actionConnect() {
		$data = array(
			'url' => Yii::app()->createAbsoluteUrl('/paymentGatewaySettings/auth', array('type' => 0))
		);
		$token = JWT::encode($data, Yii::app()->params['goCardless']['jwtKey']);
		$gcOauthClient = customFunctions::getGCOAuthClient();
		$authorizeUrl = $gcOauthClient->getAuthenticationUrl(
			Yii::app()->params['goCardless']['authorizeUrl'], Yii::app()->params['goCardless']['redirectUrl'], array(
			'scope' => 'read_write',
			'initial_view' => 'login',
			'prefill' => array('email' => $this->getUser()->email),
			'state' => (string) $token,
			)
		);
		$this->redirect($authorizeUrl);
	}

	public function actionAuth($code, $type = 0, $company_id = null, $branch_id = null) {
		$user = $this->getUser();
		$gcOauthClient = customFunctions::getGCOAuthClient();

		try {
			$response = $gcOauthClient->getAccessToken(
				Yii::app()->params['goCardless']['tokenUrl'], 'authorization_code', ['code' => $code, 'redirect_uri' => Yii::app()->params['goCardless']['redirectUrl']]
			);
			$gcAccount = GocardlessAccounts::model()->findByAttributes(array(
				'gc_organisation_id' => $response['result']['organisation_id'],
				'is_active' => 1,
			));
			if (!$gcAccount) {
				$oldGcAccounts = GocardlessAccounts::model()->findAllByAttributes(array(
					'is_active' => 1
				));
				$transaction = Yii::app()->db->beginTransaction();
				try {
					foreach ($oldGcAccounts as $oldGcAccount) {
						$oldGcAccount->is_deleted = 1;
						$oldGcAccount->is_active = 0;
						$oldGcAccount->save();
					}
					$gc = new GocardlessAccounts;
					$gc->attributes = array(
						'created_by' => $user->id,
						'gc_access_token' => $response['result']['access_token'],
						'gc_organisation_id' => $response['result']['organisation_id'],
						'type' => $type,
						'company_id' => $company_id,
						'branch_id' => $branch_id,
						'is_active' => 1,
					);
					if ($gc->save()) {
						$transaction->commit();
						Yii::app()->user->setFlash('success', 'Direct Debit Account connected successfully.');
						$this->redirect(array('paymentGatewaySettings/index'));
					} else {
						throw new Exception("Failed to add Direct Debit account!");
					}
				} catch (Exception $e) {
					$transaction->rollback();
					Yii::app()->user->setFlash('error', 'Error: ' . $e->getMessage());
					$this->redirect('paymentGatewaySettings/index');
				}
			} else {
				// account already associated
				Yii::app()->user->setFlash('success', 'Direct Debit Account connected successfully.');
				$this->redirect(array('paymentGatewaySettings/index'));
			}
		} catch (Exception $e) {
			Yii::app()->user->setFlash('error', $e->getMessage());
			$this->redirect(array('paymentGatewaySettings/index'));
		}
	}

	public function actionGcremove($id) {
		$this->_goCardlessAccount->is_deleted = 1;
		$this->_goCardlessAccount->is_active = 0;
		if ($this->_goCardlessAccount->save()) {
			Parents::model()->updateAll(array(
				'gocardless_customer_id' => null,
				'gocardless_customer' => null,
				'gocardless_mandate' => null,
				'gocardless_session_token' => null
			));
		}
		Yii::app()->user->setFlash('success', 'Direct Debit Account removed successfully.');
		$this->redirect(array('paymentGatewaySettings/index'));
	}

	private function findGcAccount() {
		$user = $this->getUser();
		$request = Yii::app()->getRequest();
		$id = $request->getParam('id', null);
		if ($id === null) {
			throw new CHttpException("Direct Debit Account not found", 404);
		}
		$this->_goCardlessAccount = GocardlessAccounts::model()->findByAttributes(array(
			'id' => $id,
			'created_by' => $user->id,
		));
		if (!$this->_goCardlessAccount) {
			throw new CHttpException("Direct Debit Account not found", 404);
		}
	}

	public function actionNonAllocatedParents($q) {
		if (!Yii::app()->request->isAjaxRequest) {
			throw new CHttpException(404, "Your request is not valid.");
		}
		$q = "%$q%";
		$parents = Parents::model()->with(array(
				'parentChildMappings' => array(
					'condition' => 'parentChildMappings.is_bill_payer = 1 AND t.gocardless_customer IS NULL AND (CONCAT(`t`.`first_name`, " ", `t`.`last_name`) LIKE :query)',
					'params' => array(':query' => $q),
					'limit' => 10
				),
				'parentChildMappings.childNds' => array(
					'condition' => '`childNds`.`branch_id` = :branch_id',
					'params' => array(':branch_id' => Branch::currentBranch()->id)
				)
			))->findAll();
		$res = CArray::map_recursive(function($parent) {
				return array(
					"text" => $parent->first_name . ' ' . $parent->last_name . ' {' . $parent->email . '}',
					"id" => $parent->id
				);
			}, $parents);
		echo CJSON::encode($res);
	}

	public function actionCustomerMandates($customer) {
		$gcCustomerClient = customFunctions::getCustomerGoCardlessClient();
		if (!$gcCustomerClient) {
			throw new CHttpException(500, 'Direct Debit Client account does not exist.');
		}
		$mandates = $gcCustomerClient->mandates()->list(array(
			"params" => array("customer" => $customer)
		));
		$options = '';
		if (count($mandates->records) > 1) {
			$options .= CHtml::tag('option', array('value' => ''), 'Select Mandate');
			foreach ($mandates->records as $mandate) {
				$options .= CHtml::tag('option', array('value' => $mandate->id), $mandate->id);
			}
		} elseif (count($mandates->records) == 1) {
			$mandate = $mandates->records[0];
			$options .= CHtml::tag('option', array('value' => $mandate->id), $mandate->id);
		}
		echo $options;
	}

	public function actionBatchUpdateParents() {
		$response = array(
			'success' => 0,
			'message' => 'Invalid Request',
		);
		if (Yii::app()->request->isAjaxRequest && isset($_POST['data'])) {
			$successful = array();
			$failed = array();
			foreach ($_POST['data'] as $obj) {
				$parentId = $obj['parent_id'];
				$customerId = $obj['customer_id'];
				$mandate = $obj['mandate'];
				$parent = Parents::model()->findByPk($parentId);
				if ($parent) {
					if ($parent->associateGcMandate($customerId, $mandate)) {
						$successful[] = array(
							'customer_id' => $customerId,
							'mandate' => $gcMandates[$customerId]
						);
					} else {
						$failed[$customerId] = array(
							'customer_id' => $customerId,
							'mandate' => $gcMandates[$customerId]
						);
					}
				} else {
					$failed[$customerId] = array(
						'customer_id' => $customerId,
						'mandate' => $gcMandates[$customerId]
					);
				}
			}
			$response['success'] = 1;
			$response['message'] = 'Parent mandate association successful';
			$response['successful'] = $successful;
			$response['failed'] = $failed;
		}
		echo CJSON::encode($response);
	}

	public function actionSendDirectDebitRequest() {
		if (Yii::app()->request->isAjaxRequest) {
			$response = array(
				'success' => 0,
				'message' => 'Invalid request',
			);
			$data = $_POST;
			if (!empty($data['parent_ids'])) {
				$message = $data['message'];
				$parentIds = explode(',', $data['parent_ids']);
				$failedParents = array();
				$sentToParents = array();
				foreach ($parentIds as $parentId) {
					$parent = Parents::model()->findByPk($parentId);
					if ($parent) {
						try {
							$parent->sendDirectDebitRequest($message);
							$sentToParents[] = $parent->id;
						} catch (Exception $e) {
							$failedParents[] = $parent->id;
						}
					} else {
						$failedParents[] = $parent->id;
					}
				}
				if (count($failedParents) == 0) {
					$response = array(
						'success' => '1',
						'message' => 'Direct debit request sent successfully.',
						'data' => array(
							'success' => $sentToParents,
							'failed' => $failedParents,
						)
					);
				} else {
					$response = array(
						'success' => '0',
						'message' => 'Direct debit requests sent partially.',
						'data' => array(
							'success' => $sentToParents,
							'failed' => $failedParents,
						)
					);
				}
			}
			echo CJSON::encode($response);
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionInvoices() {
		$this->layout = "dashboard";
		$user = $this->getUser();
		$goCardlessAccount = $user->getGlobalGoCardlessAccount();

		$model = new ChildInvoice('invoiceListForGc');
		$model->unsetAttributes();
		$model->status = 'AWAITING_PAYMENT';
		$model->year = date("Y");
		$model->month = date("m");
		if (isset($_GET['ChildInvoice'])) {
			$model->attributes = $_GET['ChildInvoice'];
			$model->child_search = $_GET['ChildInvoice']['child_search'];
			$model->month = $_GET['ChildInvoice']['month'];
			$model->year = $_GET['ChildInvoice']['year'];
		}
		$this->render('invoices', array(
			'user' => $user,
			'model' => $model,
			'goCardlessAccount' => $goCardlessAccount,
		));
	}

	public function actionCollectPaymentFromInvoices() {
		if (!Yii::app()->request->isAjaxRequest) {
			throw new CHttpException(404, 'Your request is not valid');
		}
		$response = array(
			'success' => '0',
			'message' => 'Invalid request',
		);
		$invoice_data = CJSON::decode($_POST['invoice_data']);
		if (!empty($invoice_data)) {
			$failedInvoices = array();
			$collectedInvoices = array();
			foreach ($invoice_data as $invoice_parent_data) {
				$invoiceModel = ChildInvoice::model()->findByPk($invoice_parent_data['invoice_id']);
				$parentModel = Parents::model()->findByPk($invoice_parent_data['parent_id']);
				if ($invoiceModel && $parentModel) {
					$transaction = Yii::app()->db->beginTransaction();
					try {
						$invoiceModel->recordGoCardlessPayment($parentModel);
						$collectedInvoices[] = $invoiceModel->id;
						$transaction->commit();
					} catch (Exception $e) {
						$transaction->rollback();
						if ($e->getMessage() == 'Mandate not found') {
							$parentModel->removeMandate();
						}
						$failedInvoices[$invoiceModel->id] = $e->getMessage();
					}
				} else {
					$failedInvoices[] = $invoiceModel->id;
				}
			}
			if (count($failedInvoices) == 0) {
				$response = array(
					'success' => '1',
					'message' => 'Collection successful',
					'data' => array(
						'success' => $collectedInvoices,
						'failed' => $failedInvoices,
					)
				);
			} else {
				$response = array(
					'success' => '0',
					'message' => 'Collection partially successful',
					'data' => array(
						'success' => $collectedInvoices,
						'failed' => $failedInvoices,
					)
				);
			}
		}
		echo CJSON::encode($response);
	}

}
