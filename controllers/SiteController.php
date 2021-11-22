<?php

// Demo Commit
error_reporting(E_ERROR | E_PARSE);
Yii::app()->clientScript->registerScript('helpers', '
          yii = {
              urls: {
                  base: ' . CJSON::encode(Yii::app()->baseUrl) . ',
                  createCompany: ' . CJSON::encode(Yii::app()->createUrl('site/createCompany')) . ',
                  createUser: ' . CJSON::encode(Yii::app()->createUrl('site/createUser')) . ',
                  getEnquiryDetails: ' . CJSON::encode(Yii::app()->createUrl('enquiries/getEnquiryDetail')) . ',
              }
          };
      ', CClientScript::POS_HEAD);

Yii::app()->clientScript->registerScript('helpers', '
          eyMan = {
              urls: {
                  getEnquiryDetails: ' . CJSON::encode(Yii::app()->createUrl('enquiries/getEnquiryDetail')) . ',
                  getOpenEnquiryDetails: ' . CJSON::encode(Yii::app()->createUrl('enquiries/getOpenEnquiryDetail')) . ',
									roomMonthlyOccupancy: ' . CJSON::encode(Yii::app()->createUrl('site/roomMonthlyOccupancy')) . ',
									roomMonthlyFte: ' . CJSON::encode(Yii::app()->createUrl('site/roomMonthlyFte')) . ',
              }
          };
      ', CClientScript::POS_END);

class SiteController extends Controller {

	// Testtest
	/**
	 * Declares class-based actions.
	 */
	public $defaultAction = 'index';

	public function actions() {
		return array(
			// captcha action renders the CAPTCHA image displayed on the contact page
			'captcha' => array('class' => 'CCaptchaAction', 'backColor' => 0xFFFFFF
			),
			// page action renders "static" pages stored under 'protected/views/site/pages'
			// They can be accessed via: index.php?r=site/page&view=FileName
			'page' => array('class' => 'CViewAction'
			)
		);
	}

	public function filters() {
		return array('accessControl', // perform access control for CRUD operations
			'postOnly + delete'
		); // we only allow deletion via POST request
	}

	public function accessRules() {
		return array(array('allow', // allow all users to perform 'index' and 'view' actions
				'actions' => array('login', 'forgotPassword', 'error', 'verifyPassword', 'index', 'setBranchInSession', 'setCompanyInSession', 'loginEylog', 'loginEyman', 'updateLatLong', 'import', 'updateHolidayDate', 'loginToEyMan', 'parentLogin', 'chkeckParentChild', 'goCardlessSuccess', 'goCardlessDone', 'webhook', 'updateInvoiceGoCardless'
				), 'users' => array('*'
				)
			), array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions' => array('roomMonthlyFte', 'roomMonthlyOccupancy', 'saveOriginalContractHours', 'contractHoursEvent', 'createStaffEntitlement', 'importPayRates', 'dashboard', 'importStaffHolidays', 'updateTopsBalances', 'globalProducts', 'globalPaytypes', 'importChildData', 'logout', 'loadEnquiry', 'getRoomsForChart', 'bookingDataForChart', 'loadUpcomingChildBirthDay', 'loadUpcomingStaffBirthDay', 'getOverDueInvoices', 'getChildEvents', 'getStaffEvents', 'incomeForecast', 'getMultipleRate', 'getMultipleRatesWeekdays', 'getMultipleRatesTotalWeekdays', 'getStaffHoliday', 'sessionIncomeForecast', 'globalEventType', 'globalDocumentType', 'loadNotifications'
				), 'users' => array('@'
				)
			), array('allow', // allow admin user to perform 'admin' and 'delete' actions
				'actions' => array('admin', 'delete'
				), 'users' => array('admin'
				)
			), array('deny', 'users' => array('*'
				)
			)
		);
	}

	/**
	 * This is the default 'index' action that is invoked
	 * when an action is not explicitly requested by users.
	 */
	public function actionIndex() {
		$this->layout = 'main_login';
		if (Yii::app()->user->isGuest) {
			$this->redirect('login');
		} else {
			$this->redirect('dashboard');
		}
	}

	/**
	 * This is the action to handle external exceptions.
	 */
	public function actionError() {
		$this->layout = 'main_login';
		$this->pageTitle = 'Error | eyMan';
		if ($error = Yii::app()->errorHandler->error) {
			if (Yii::app()->request->isAjaxRequest)
				echo $error['message'];
			else
				$this->render('error', $error);
		}
	}

	/**
	 * Displays the login page
	 */
	public function actionLogin() {
		$this->layout = 'main_login';
		$this->pageTitle = 'Login | eyMan';
		$model = new LoginForm();
		$this->performAjaxValidation($model);
		if (Yii::app()->user->isGuest) {
			if (isset($_POST['LoginForm'])) {
				$model->attributes = $_POST['LoginForm'];
				if ($model->validate() && $model->login()) {
					Yii::app()->user->setState("userName", customFunctions::encryptor("encrypt", $_POST['LoginForm']['username']));
					Yii::app()->user->setState("userPassword", customFunctions::encryptor("encrypt", $_POST['LoginForm']['password']));
					$model = User::model()->findByPk(Yii::app()->user->id);
					Yii::app()->session['userId'] = $model->id;
					Yii::app()->session['name'] = $model->first_name . (!empty($model->last_name) ? " " . $model->last_name : "");
					Yii::app()->session['role'] = $model->role;
					Yii::app()->session['email'] = $model->email;
					if (Yii::app()->session['role'] === "superAdmin") {
						$companyModel = Company::model()->find();
						if (empty($companyModel)) {
							Yii::app()->session['company_id'] = 0;
							Yii::app()->session['branch_id'] = 0;
						} else {
							Yii::app()->session['company_id'] = $companyModel->id;
							$branchModel = Branch::model()->findByAttributes(array('company_id' => $companyModel->id
							));
							if (empty($branchModel)) {
								Yii::app()->session['branch_id'] = 0;
							} else {
								Yii::app()->session['branch_id'] = $branchModel->id;
							}
						}
						$this->redirect(array('site/dashboard'
						));
					} else
					if (Yii::app()->session['role'] === "companyAdministrator") {
						$mappingModel = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->session['userId']
						));
						if (!empty($mappingModel)) {
							$branchModel = Branch::model()->findByAttributes(array('company_id' => $mappingModel->company_id
							));
							Yii::app()->session['company_id'] = $mappingModel->company_id;
							Yii::app()->session['branch_id'] = $branchModel->id;
						} else {
							Yii::app()->session['company_id'] = 0;
							Yii::app()->session['branch_id'] = 0;
						}
						$this->redirect(array('site/dashboard'
						));
					} else
					if (Yii::app()->session['role'] === "areaManager") {
						$mappingModel = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->session['userId']
						));
						if (!empty($mappingModel)) {
							Yii::app()->session['company_id'] = $mappingModel->company_id;
							Yii::app()->session['branch_id'] = $mappingModel->branch_id;
						} else {
							Yii::app()->session['company_id'] = 0;
							Yii::app()->session['branch_id'] = 0;
						}
						$this->redirect(array('site/dashboard'
						));
					} else
					if (Yii::app()->session['role'] === "branchManager") {
						$mappingModel = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->session['userId']
						));
						if (!empty($mappingModel)) {
							Yii::app()->session['company_id'] = $mappingModel->company_id;
							Yii::app()->session['branch_id'] = $mappingModel->branch_id;
						} else {
							Yii::app()->session['company_id'] = 0;
							Yii::app()->session['branch_id'] = 0;
						}
						$this->redirect(array('site/dashboard'
						));
					} else
					if (Yii::app()->session['role'] === "branchAdmin") {
						$mappingModel = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->session['userId']
						));
						if (!empty($mappingModel)) {
							Yii::app()->session['company_id'] = $mappingModel->company_id;
							Yii::app()->session['branch_id'] = $mappingModel->branch_id;
						} else {
							Yii::app()->session['company_id'] = 0;
							Yii::app()->session['branch_id'] = 0;
						}
						$this->redirect(array('site/dashboard'
						));
					} else
					if (Yii::app()->session['role'] === "hrAdmin") {
						$companyModel = Company::model()->find();
						if (empty($companyModel)) {
							Yii::app()->session['company_id'] = 0;
							Yii::app()->session['branch_id'] = 0;
						} else {
							Yii::app()->session['company_id'] = $companyModel->id;
							$branchModel = Branch::model()->findByAttributes(array('company_id' => $companyModel->id
							));
							if (empty($branchModel)) {
								Yii::app()->session['branch_id'] = 0;
							} else {
								Yii::app()->session['branch_id'] = $branchModel->id;
							}
						}
						$this->redirect(array('site/dashboard'
						));
					} else
					if (Yii::app()->session['role'] === "hrStandard") {
						$companyModel = Company::model()->find();
						if (empty($companyModel)) {
							Yii::app()->session['company_id'] = 0;
							Yii::app()->session['branch_id'] = 0;
						} else {
							Yii::app()->session['company_id'] = $companyModel->id;
							$branchModel = Branch::model()->findByAttributes(array('company_id' => $companyModel->id
							));
							if (empty($branchModel)) {
								Yii::app()->session['branch_id'] = 0;
							} else {
								Yii::app()->session['branch_id'] = $branchModel->id;
							}
						}
						$this->redirect(array('site/dashboard'
						));
					} else
					if (Yii::app()->session['role'] === "accountsAdmin") {
						$mappingModel = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->session['userId']
						));
						if (!empty($mappingModel)) {
							$branchModel = Branch::model()->findByAttributes(array('company_id' => $mappingModel->company_id
							));
							Yii::app()->session['company_id'] = $mappingModel->company_id;
							Yii::app()->session['branch_id'] = $branchModel->id;
						} else {
							Yii::app()->session['company_id'] = 0;
							Yii::app()->session['branch_id'] = 0;
						}
						$this->redirect(array('site/dashboard'
						));
					} else
					if (Yii::app()->session['role'] === "staff") {
						$mappingModel = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->session['userId']
						));
						if (!empty($mappingModel)) {
							Yii::app()->session['company_id'] = $mappingModel->company_id;
							Yii::app()->session['branch_id'] = $mappingModel->branch_id;
							Yii::app()->session['staff_id'] = $mappingModel->staff_id;
						} else {
							Yii::app()->session['company_id'] = 0;
							Yii::app()->session['branch_id'] = 0;
						}
						$this->redirect(array('staffPersonalDetails/update', 'staff_id' => $mappingModel->staff_id
						));
					} else {
						throw new CHttpException(404, "This Role is not allowed to access this page.");
					}
				}
			}
			// display the login form
			$this->render('login', array('model' => $model
			));
		} else {
			$this->redirect($this->createAbsoluteUrl('site/dashboard'));
		}
	}

	/**
	 * Logs out the current user and redirect to homepage.
	 */
	public function actionLogout() {
		Yii::app()->session->clear();
		Yii::app()->session->destroy();
		Yii::app()->user->logout();
		$this->redirect(Yii::app()->homeUrl);
		if (!(Yii::app()->user->isGuest)) {
			$this->redirect($this->redirect(array('site/dashboard'
			)));
		}
	}

	public function actionForgotPassword() {
		$this->layout = 'main_login';
		$this->pageTitle = 'Forgot Password | eyMan';
		$model = new ForgotForm();
		$this->performAjaxValidation($model);
		if (isset($_POST['ForgotForm'])) {
			$model->attributes = $_POST['ForgotForm'];
			if ($model->validate()) {
				$user = User::model()->findByAttributes(array('email' => $model->email
				));
				if (empty($user)) {
					Yii::app()->user->setFlash('error', 'Please enter a registered email Address.');
				}
				if (!empty($user)) {
					$token = md5(time() . uniqid());
					$url = $this->createAbsoluteUrl('site/verifyPassword', array('token' => $token
					));
					$to = $user->email;
					$name = "User";
					$subject = "eyMan - Password reset";
					$content = "Hello " . $name . "<br/><br/>";
					$content .= "<h5>Your password reset link is as below.Please click on this link to reset your password.</br></h5>";
					$content .= $url;
					$isSent = customFunctions::sendEmail($to, $name, $subject, $content);
					if ($isSent == true) {
						$user->forgot_token = $token;
						$user->forgot_token_expire = date("Y-m-d H:i:s", time() + 7200);
						$user->is_forgot_token_valid = 1;
						$user->save();
						Yii::app()->user->setFlash('success', 'Verification email has been sent.Please check your inbox');
						$this->refresh();
					} else
						Yii::app()->user->setFlash('warning', 'There was some problem sending the mail.Please try after sometime.');
				}
			}
		}
		$this->render('forgotPassword', array('model' => $model
		));
	}

	public function actionVerifyPassword() {
		$this->layout = 'main_login';
		$this->pageTitle = 'Verify Password | eyMan';
		$model = new VerifyForm();
		$this->performAjaxValidation($model);
		if (!isset($_GET['token'])) {
			throw new CHttpException(400, "Page not found");
		}
		if (isset($_GET['token'])) {
			$token = $_GET['token'];
			$criteria = new CDbCriteria();
			$criteria->condition = 'forgot_token_expire >= :forgot_token_expire and forgot_token = :forgot_token and is_forgot_token_valid = 1';
			$criteria->params = array(':forgot_token_expire' => date('Y-m-d H:i:s'), ':forgot_token' => $token
			);
			$userData = User::model()->find($criteria);
			if (empty($userData)) {
				throw new CHttpException(400, "Token has expired or link is not valid");
			}
			if (!empty($userData)) {
				if (isset($_POST['VerifyForm'])) {
					$model->attributes = $_POST['VerifyForm'];
					if ($model->validate()) {
						$user = User::model()->findByPk($userData->id);
						$user->password = CPasswordHelper::hashPassword($model->password);
						$user->is_forgot_token_valid = 0;
						$user->save();
						if ($user->save()) {
							Yii::app()->user->setFlash('success', 'Password has been successfully reset.');
							$this->redirect(array('/site/login'
							));
						}
					}
				}
			}
		}
		$this->render('verifyPassword', array('model' => $model
		));
	}

	public function actionDashboard() {
		$cs = Yii::app()->getClientScript();
		$cs->registerScriptFile("https://www.google.com/jsapi");
		$cs->registerScriptFile("https://www.gstatic.com/charts/loader.js");
		$cs->registerScriptFile(Yii::app()->request->baseUrl . "/js/childBookingChart.js?ver=1.0.3");
		$cs->registerScriptFile(Yii::app()->request->baseUrl . "/js/incomeForcastChart.js?version=1.0.4");
		$cs->registerScriptFile(Yii::app()->request->baseUrl . "/js/dashboardOccupancyChart.js?version=1.0.7");
		$cs->registerScriptFile(Yii::app()->request->baseUrl . "/js/upcoming_child_birthday.js?ver=1.0.2");
		$cs->registerScriptFile(Yii::app()->request->baseUrl . "/js/newEnquiry.js?version=1.0.0");
		$this->pageTitle = 'Dashboard | eyMan';
		$this->layout = 'dashboard';
		if (Yii::app()->user->isGuest) {
			Yii::app()->user->logout;
			$this->render('login');
		}
		if (!(Yii::app()->user->isGuest)) {
			if (!isset(Yii::app()->session['branch_id']) || !isset(Yii::app()->session['company_id']) || !isset(Yii::app()->session['role'])) {
				Yii::app()->session->destroy();
				$this->redirect('login');
			}
			$branchModal = Branch::model()->findByPk(Yii::app()->session['branch_id']);
			$dayArray = array(0 => '#chart_sunday', 1 => '#chart_monday', 2 => '#chart_tuesday', 3 => '#chart_wednesday', 4 => '#chart_thursday', 5 => '#chart_friday', 6 => '#chart_saturday'
			);
			$dayTabArray = array(0 => '#sun-tab', 1 => '#mon-tab', 2 => '#tue-tab', 3 => '#wed-tab', 4 => '#thu-tab', 5 => '#fri-tab', 6 => '#sat-tab'
			);
			$hiddenDayTabArray = array_diff_key($dayTabArray, array_flip(explode(",", $branchModal->nursery_operation_days)));
			$this->render('dashboard', array('nursery_operation_days' => explode(",", $branchModal->nursery_operation_days), 'dayArray' => $dayArray, 'dayTabArray' => $dayTabArray, 'hiddenDayTabArray' => $hiddenDayTabArray
			));
		}
	}

	public function actionLoadNotifications() {
		if (Yii::app()->request->isAjaxRequest) {
			$response = array();
			$criteria = new CDbCriteria();
			$criteria->with = ['staff' => ['condition' => 'branch_id = :branch_id', 'params' => [':branch_id' => Yii::app()->session['branch_id']
					]
				]
			];
			$criteria->together = true;
			$criteria->condition = "((title_date_1_value is not null and title_date_2_value is null and title_date_1_value = :event_date) OR (title_date_1_value is null and title_date_2_value is NOT null and title_date_2_value = :event_date) OR (title_date_1_value is not null and title_date_2_value is not null and :event_date BETWEEN title_date_1_value and title_date_2_value)) AND (status = 0 OR status is NULL)";
			$criteria->params = [':event_date' => date("Y-m-d")
			];
			$staffEvents = StaffEventDetails::model()->findAll($criteria);
			foreach ($staffEvents as $events) {
				$response[] = ['event_data' => $events->event, 'staff_event_data' => $events->attributes, 'staff_data' => $events->staff
				];
			}
			echo CJSON::encode($response);
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionLoadEnquiry() {
		if (Yii::app()->request->isAjaxRequest) {
			$model = new Enquiries();
			$start_date = date('Y-m-01', strtotime($_POST['currentMonth']));
			$finish_date = date('Y-m-t', strtotime($_POST['currentMonth']));
			$new_enquiries = Enquiries::model()->findAll([
				'condition' => 'branch_id = :branch_id AND enquiry_date_time BETWEEN :start_date AND :finish_date ',
				'params' => [
					':branch_id' => Yii::app()->session['branch_id'],
					':start_date' => $start_date,
					':finish_date' => $finish_date,
				],
				'order' => 'id DESC'
			]);
			foreach ($new_enquiries as $enquiry) {
				$enquiry->child_last_name = $enquiry->child_last_name . " [" . $enquiry->getStatus($enquiry->status) . " ]";
				$enquiry->parent_first_name = $enquiry->branch->name;
			}
			echo CJSON::encode($new_enquiries);
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionLoadUpcomingChildBirthDay() {
		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1) && isset($_POST['currentMonth'])) {
			if (strtotime(date('Y-m-d')) == strtotime($_POST['currentMonth'])) {
				$criteria = new CDbCriteria();
				$criteria->condition = "MONTH(dob) = MONTH(:currentMonth) AND DAY(dob) >= DAY(NOW()) AND branch_id = :branch_id AND YEAR(dob) <= YEAR(:currentMonth)";
				$criteria->params = array(':branch_id' => Yii::app()->session['branch_id'], ':currentMonth' => $_POST['currentMonth']
				);
				$criteria->order = 'DAY(dob) ASC';
			} else {
				$criteria = new CDbCriteria();
				$criteria->condition = "MONTH(dob) = MONTH(:currentMonth) AND branch_id = :branch_id AND YEAR(dob) <= YEAR(:currentMonth)";
				$criteria->params = array(':branch_id' => Yii::app()->session['branch_id'], ':currentMonth' => $_POST['currentMonth']
				);
				$criteria->order = 'DAY(dob) ASC';
			}
			$childModel = ChildPersonalDetails::model()->findAll($criteria);
			$birthDayData = array();
			$baseUrl = Yii::app()->request->baseUrl;
			foreach ($childModel as $child) {
				if (strtotime(date("d-m", strtotime($child->dob))) >= strtotime(date('d-m'))) {
					$temp = array();
					$temp['name'] = $child->first_name . " " . $child->last_name;
					$temp['room'] = isset($child->room_id) ? $child->room->name : "Not Assigned";
					$temp['age'] = customFunctions::ageInMonths($child->dob);
					$temp['birthday'] = date('jS F', strtotime($child->dob));
					$temp['profile_photo'] = $child->getProfileImageThumb();
					$temp['profile_url'] = Yii::app()->createUrl('childPersonalDetails/update', array('child_id' => $child->id
					));
					array_push($birthDayData, $temp);
				}
			}
			echo CJSON::encode($birthDayData);
		} else {
			throw new CHttpException(404, 'You are not allowed to access this page.');
		}
	}

	public function actionLoadUpcomingStaffBirthDay() {
		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1) && isset($_POST['currentMonth'])) {
			if (strtotime(date('Y-m-d')) == strtotime($_POST['currentMonth'])) {
				$criteria = new CDbCriteria();
				$criteria->condition = "MONTH(dob) = MONTH(:currentMonth) AND DAY(dob) >= DAY(NOW()) AND branch_id = :branch_id AND YEAR(dob) <= YEAR(:currentMonth) AND dob is NOT NULL";
				$criteria->params = array(':branch_id' => Yii::app()->session['branch_id'], ':currentMonth' => $_POST['currentMonth']
				);
				$criteria->order = 'DAY(dob) ASC';
			} else {
				$criteria = new CDbCriteria();
				$criteria->condition = "MONTH(dob) = MONTH(:currentMonth) AND branch_id = :branch_id AND YEAR(dob) <= YEAR(:currentMonth) AND dob is NOT NULL";
				$criteria->params = array(':branch_id' => Yii::app()->session['branch_id'], ':currentMonth' => $_POST['currentMonth']
				);
				$criteria->order = 'DAY(dob) ASC';
			}
			$staffModel = StaffPersonalDetails::model()->findAll($criteria);
			$birthDayData = array();
			$baseUrl = Yii::app()->request->baseUrl;
			foreach ($staffModel as $staff) {
				$temp = array();
				$temp['name'] = $staff->first_name . " " . $staff->last_name;
				$temp['room'] = isset($staff->room_id) ? $staff->room->name : "Not Assigned";
				$temp['age'] = customFunctions::ageInMonths($staff->dob);
				$temp['birthday'] = date('jS F', strtotime($staff->dob));
				$temp['profile_photo'] = $staff->getProfileImageThumb();
				$temp['profile_url'] = Yii::app()->createUrl('staffPersonalDetails/update', array('staff_id' => $staff->id
				));
				array_push($birthDayData, $temp);
			}
			echo CJSON::encode($birthDayData);
		} else {
			throw new CHttpException(404, 'You are not allowed to access this page.');
		}
	}

	/**
	 * Performs the AJAX validation.
	 *
	 * @param User $model
	 *        	the model to be validated
	 */
	protected function performAjaxValidation($model) {
		if (isset($_POST['ajax']) && ($_POST['ajax'] === 'forgot-form')) {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	public function actionCreateCompany() {
		if (isset($_POST['Company'])) {
			$response = array('status' => '1'
			);
			$companyModel = new Company();
			$companyModel->attributes = $_POST['Company'];
			if ($companyModel->save()) {
				echo CJSON::encode(array_merge(array('company_id' => $companyModel->id, 'company_name' => $companyModel->name
						), $response));
			} else {
				$response['status'] = 0;
				echo CJSON::encode(array_merge($response, $companyModel->getErrors()));
			}
		}
	}

	public function actionCreateUser() {
		if (isset($_POST['User']) && isset($_POST['UserBranchMapping'])) {
			$response = array('status' => '1'
			);
			$userModel = new User();
			$mappingModel = new UserBranchMapping();
			$userModel->attributes = $_POST['User'];
			if ($userModel->save()) {
				Yii::app()->authManager->assign("companyAdministrator", $userModel->id);
				$mappingModel->company_id = (int) $_POST['UserBranchMapping']['company_id'];
				$mappingModel->user_id = $userModel->id;
				if ($mappingModel->save()) {
					echo CJSON::encode($response);
				} else {
					print_r($mappingModel->getErrors());
				}
			} else {
				$response['status'] = 0;
				echo CJSON::encode(array_merge($response, $userModel->getErrors()));
			}
		}
	}

        public function actionSetBranchInSession() {
        if ((Yii::app()->request->isAjaxRequest) && isset($_POST['branch_id']) && isset($_POST['controller_name'])&& isset($_POST['controller_action'])) {
            if (isset(Yii::app()->session['branch_id'])) {
                $model = Branch::model()->findByPk($_POST['branch_id']);
                if (empty($model)) {
                    echo CJSON::encode([
                                'status' => 0
                    ]);
                    Yii::app()->end();
                }

                Yii::app()->session['branch_id'] = $model->id;
                $redirect_url = NULL;
                $controller = $_POST['controller_name'];
                $action  = $_POST['controller_action'];
                $index_url = array('enquiries','payments','branchCalendar','user','room','sessionRates','products','payType','documentType','eventType','terms');
                switch ($controller) {
                    case in_array($controller, $index_url) == 1:
                        $redirect_url = Yii::app()->createAbsoluteUrl($controller . "/index");
                        break;
                    case "reports":
                        if($action == 'occupancyChart') {
                            $append_url = $action;
                        }
                        else if($action == 'sessionOccupancyChart') {
                            $append_url = $action.'?isOccupancyChart=1';
                        }
                        else {
                            $append_url = 'index';
                        }
                        $redirect_url = Yii::app()->createAbsoluteUrl($controller .'/'.$append_url);
                        break;
                    case stristr($controller, 'child') == true:
                        $redirect_url = Yii::app()->createAbsoluteUrl("childPersonalDetails/index");
                        break;
                    case stristr($controller,'staff') == true:
                        if($action == 'staffScheduling'){
                            $redirect_url = Yii::app()->createAbsoluteUrl($controller . "/staffScheduling");
                        }
                        else {
                           $redirect_url = Yii::app()->createAbsoluteUrl("staffPersonalDetails/index");
                        }
                        break;
                    default:
                        $redirect_url = Yii::app()->createAbsoluteUrl("site/dashboard");
                }
                echo CJSON::encode(array('status' => 1, 'branch_id' => $_POST['branch_id'], 'name' => $model->name, 'redirect_url' => $redirect_url
                ));
                Yii::app()->end();
            } else {
                echo CJSON::encode([
                             'status' => 0
                ]);
                Yii::app()->end();
            }
        }
    }

    public function actionSetCompanyInSession($company_id) {
		if (isset($company_id)) {
			Yii::app()->session['company_id'] = $company_id;
			$branchModel = Branch::model()->findByAttributes(array('company_id' => $company_id
			));
			if (empty($branchModel)) {
				Yii::app()->session['branch_id'] = 0;
			} else {
				Yii::app()->session['branch_id'] = $branchModel->id;
			}
			$this->redirect('dashboard');
		} else {
			throw new CHttpException(404, "Your request is invalid");
		}
	}

	public function actionBookingDataForChart() {
		if (Yii::app()->request->isAjaxRequest) {
			@session_write_close();
			$response = array('status' => 0, 'data' => 0);
			if (isset($_POST['branch_id']) && isset($_POST['weekStartDate']) && isset($_POST['weekFinishDate']) && isset($_POST['currentDay'])) {
				$branchModal = Branch::currentBranch();
				$roomModal = $branchModal->roomWithShowBookings;
				$response = array();
				$bookingsCount = array();
				$currentDay = date("Y-m-d", strtotime($_POST['currentDay']));
				$roomOccupancy = [];
				$branchOperationTime = customFunctions::getHours($branchModal->child_bookings_start_time, $branchModal->child_bookings_finish_time);
				if (!empty($roomModal)) {
					$roomBookingCount = array();
					$roomName = array(0 => "Time");
					$room_ids = array();
					foreach ($roomModal as $room) {
						$response[strtolower(date('l', strtotime($_POST['currentDay'])))][0][] = $room->color;
						$roomBookingCount[$room->id] = 0;
						$roomName[$room->id] = $room->name;
						$room_ids[] += $room->id;
					}
					$operationStartTime = $branchModal->child_bookings_start_time;
					$operationFinishTime = $branchModal->child_bookings_finish_time;
					while (strtotime($operationStartTime) <= strtotime($operationFinishTime)) {
						$timeArray[] = date('H:i', strtotime($operationStartTime));
						$bookingsCount[date('H:i', strtotime($operationStartTime))] = $roomBookingCount;
						$operationStartTime = date('H:i', strtotime($operationStartTime . '+1 hours'));
					}
					$bookingsModel = ChildBookings::model()->getBookings($currentDay, $currentDay, $branchModal->id);
					if (!empty($bookingsModel)) {
						foreach ($bookingsModel as $bookings) {
							if (in_array($bookings->room_id, $room_ids)) {
								if ($bookings->childNds->is_deleted == 1) {
									continue;
								}
								if ($bookings->childNds->is_active == 0) {
									if (!empty($bookings->childNds->leave_date) && isset($bookings->childNds->leave_date)) {
										if (strtotime(date("Y-m-d", strtotime($currentDay))) > strtotime(date("Y-m-d", strtotime($bookings->childNds->leave_date)))) {
											continue;
										}
									} else if (!empty($bookings->childNds->last_updated) && isset($bookings->childNds->last_updated)) {
										if (strtotime(date("Y-m-d", strtotime($currentDay))) > strtotime(date("Y-m-d", strtotime($bookings->childNds->last_updated)))) {
											continue;
										}
									}
								}
								$bookingsDates = customFunctions::getDatesOfDays($bookings->start_date, $bookings->finish_date, explode(",", $bookings->childBookingsDetails->booking_days));
								if (is_array($bookingsDates)) {
									if (in_array($currentDay, $bookingsDates)) {
										$checkChildOnHoliday = ChildHolidays::model()->find([
											'condition' => 'date = :date AND exclude_from_invoice = 1 AND child_id = :child_id',
											'params' => [
												':date' => $currentDay,
												':child_id' => $bookings->child_id
											]
										]);
										if ($checkChildOnHoliday) {
											continue;
										}
										$roomOccupancy[$bookings->room_id] += customFunctions::round(customFunctions::getHours($bookings->start_time, $bookings->finish_time), 2);
										if (!empty($bookingsCount)) {
											foreach ($bookingsCount as $key => $value) {
												$time = strtotime(date("H:i:s", strtotime($key)));
												if ($time >= strtotime(date("H:i:s", strtotime($bookings->start_time))) && $time <= strtotime(date("H:i:s", strtotime($bookings->finish_time)))) {
													$bookingsCount[$key][$bookings->room_id] += 1;
												}
											}
										}
									}
								}
							}
						}
					}
					if (!empty($roomOccupancy)) {
						foreach ($roomOccupancy as $key => $value) {
							$model = Room::model()->findByPk($key);
							$roomName[$key] = $model->name . " [" . $model->capacity . "] - " . customFunctions::round(customFunctions::getDividedResult(($model->capacity * $branchOperationTime), $value) * 100, 2) . " %";
						}
					}
					$finalBookingsCount = array();
					$finalBookingsCount[] = array('Time') + array_values($roomName);
					if (!empty($bookingsCount)) {
						foreach ($bookingsCount as $key => $value) {
							$finalBookingsCount[] = array_values(array($key) + $value);
						}
					}
					$response[strtolower(date('l', strtotime($_POST['currentDay'])))][1] = $finalBookingsCount;
					echo CJSON::encode($response);
				} else {
					$response['status'] = 1;
					$response['data'] = 0;
					echo CJSON::encode($response);
				}
			} else {
				echo CJSON::encode($response);
				Yii::app()->end();
			}
		} else {
			throw new CHttpException(404, 'You are not allowed to access this page.');
		}
	}

	public function actionIncomeForecast() {
		ini_set('max_execution_time' , -1);
		@session_write_close();
		if (Yii::app()->request->isAjaxRequest) {
          customFunctions::getIncomeForeCast($_POST['incomeForecastMonth']);
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionSessionIncomeForecast() {
		ini_set('max_execution_time' , -1);
		@session_write_close();
		if (Yii::app()->request->isAjaxRequest) {
          customFunctions::getSessionIncomeForeCast($_POST['incomeForecastMonth']);
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionGetOverDueInvoices() {
		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1)) {
			$criteria = new CDbCriteria();
			$criteria->condition = "status = :status AND due_date <= :today AND branch_id = :branch_id AND total > 0 AND month = MONTH(:currentMonth) and year = YEAR(:currentMonth) and invoice_type = 0 AND is_regenrated = 0 AND (invoice_type = 0 OR invoice_type = 1)";
			$criteria->params = array(':status' => ChildInvoice::PENDING_PAYMENT, ':today' => date('Y-m-d'), ':branch_id' => Yii::app()->session['branch_id'], ':currentMonth' => $_POST['currentMonth']
			);
			$criteria->order = "due_date, total";
			$invoiceModel = ChildInvoice::model()->findAll($criteria);
			$data = array();
			foreach ($invoiceModel as $invoice) {
				$temp = array();
				$temp['name'] = $invoice->child->first_name . " " . $invoice->child->last_name;
				$temp['total'] = $invoice->branch->currency_sign . $invoice->total;
				$temp['due_date'] = date("d-M-Y", strtotime($invoice->due_date));
				$temp['url'] = ($invoice->invoice_type == 0) ? Yii::app()->createUrl('childInvoice/view', array('child_id' => $invoice->child_id, 'invoice_id' => $invoice->id
					)) : Yii::app()->createUrl('childInvoice/viewManualInvoice', array('child_id' => $invoice->child_id, 'invoice_id' => $invoice->id
				));
				array_push($data, $temp);
			}
			echo CJSON::encode($data);
		} else {
			throw new CHttpException(404, "Yourn request is not valid");
		}
	}

	public function actionGetChildEvents() {
		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1) && isset($_POST['currentMonth']) && (Yii::app()->session['branch_id'] != NULL)) {
			$criteria = new CDbCriteria();
			$criteria->join = "INNER JOIN tbl_child_personal_details pd ON t.child_id = pd.id AND pd.branch_id = :branch_id";
			$criteria->condition = "(MONTH(title_date_1_value) = MONTH(:currentMonth) AND YEAR(title_date_1_value) = YEAR(:currentMonth)) OR (MONTH(title_date_2_value) = MONTH(:currentMonth) AND YEAR(title_date_2_value) = YEAR(:currentMonth))";
			$criteria->order = "title_date_1_value";
			$criteria->params = array(':branch_id' => Yii::app()->session['branch_id'], ':currentMonth' => $_POST['currentMonth']
			);
			$eventModel = ChildEventDetails::model()->findAll($criteria);
			$data = array();
			foreach ($eventModel as $event) {
				$eventTypeModel = EventType::model()->findByPk($event->event_id);
				if (ChildPersonalDetails::model()->findByPk($event->child_id)) {
					$temp = array();
					$temp['name'] = ChildPersonalDetails::model()->findByPk($event->child_id)->first_name . " " . ChildPersonalDetails::model()->findByPk($event->child_id)->last_name;
					if (customFunctions::checkValidateDate($event->title_date_1_value) && customFunctions::checkValidateDate($event->title_date_2_value)) {
						$temp['duration'] = date('d-M-Y', strtotime($event->title_date_1_value)) . " - " . date('d-M-Y', strtotime($event->title_date_2_value));
					} else
					if (customFunctions::checkValidateDate($event->title_date_1_value) && !customFunctions::checkValidateDate($event->title_date_2_value)) {
						$temp['duration'] = date('d-M-Y', strtotime($event->title_date_1_value));
					} else
					if (!customFunctions::checkValidateDate($event->title_date_1_value) && customFunctions::checkValidateDate($event->title_date_2_value)) {
						$temp['duration'] = date('d-M-Y', strtotime($event->title_date_2_value));
					} else {
						$temp['duration'] = "Not Set";
					}
					$temp['event_description'] = $eventTypeModel->name;
					$temp['url'] = Yii::app()->createUrl('childEventDetails/index', array('child_id' => $event->child_id
					));
					array_push($data, $temp);
				}
			}
			echo CJSON::encode($data);
		} else {
			throw new CHttpException(404, "Yourn request is not valid");
		}
	}

	public function actionGetStaffEvents() {
		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1) && isset($_POST['currentMonth']) && (Yii::app()->session['branch_id'] != NULL)) {
			$criteria = new CDbCriteria();
			$criteria->join = "INNER JOIN tbl_staff_personal_details sd ON t.staff_id = sd.id AND sd.branch_id = :branch_id";
			$criteria->condition = "(MONTH(title_date_1_value) = MONTH(:currentMonth) AND YEAR(title_date_1_value) = YEAR(:currentMonth)) OR (MONTH(title_date_2_value) = MONTH(:currentMonth) AND YEAR(title_date_2_value) = YEAR(:currentMonth))";
			$criteria->order = "title_date_1_value";
			$criteria->params = array(':branch_id' => Yii::app()->session['branch_id'], ':currentMonth' => $_POST['currentMonth']
			);
			$eventModel = StaffEventDetails::model()->findAll($criteria);
			$data = array();
			foreach ($eventModel as $event) {
				$eventTypeModel = EventType::model()->findByPk($event->event_id);
				if (StaffPersonalDetails::model()->findByPk($event->staff_id)) {
					$temp = array();
					$temp['name'] = StaffPersonalDetails::model()->findByPk($event->staff_id)->first_name . " " . StaffPersonalDetails::model()->findByPk($event->staff_id)->last_name;
					// $temp['duration'] = date('d-M-Y', strtotime($event->title_date_1_value)) . " - " . date('d-M-Y', strtotime($event->title_date_2_value));
					if (customFunctions::checkValidateDate($event->title_date_1_value) && customFunctions::checkValidateDate($event->title_date_2_value)) {
						$temp['duration'] = date('d-M-Y', strtotime($event->title_date_1_value)) . " - " . date('d-M-Y', strtotime($event->title_date_2_value));
					} else
					if (customFunctions::checkValidateDate($event->title_date_1_value) && !customFunctions::checkValidateDate($event->title_date_2_value)) {
						$temp['duration'] = date('d-M-Y', strtotime($event->title_date_1_value));
					} else
					if (!customFunctions::checkValidateDate($event->title_date_1_value) && customFunctions::checkValidateDate($event->title_date_2_value)) {
						$temp['duration'] = date('d-M-Y', strtotime($event->title_date_2_value));
					} else {
						$temp['duration'] = "Not Set";
					}
					$temp['event_description'] = $eventTypeModel->name;
					$temp['url'] = Yii::app()->createUrl('staffEventDetails/index', array('staff_id' => $event->staff_id
					));
					array_push($data, $temp);
				}
			}
			echo CJSON::encode($data);
		} else {
			throw new CHttpException(404, "Yourn request is not valid");
		}
	}

	public function actionGetStaffHoliday() {
		if (isset($_POST['isAjaxRequest']) && ($_POST['isAjaxRequest'] == 1) && isset($_POST['currentMonth']) && (Yii::app()->session['branch_id'] != NULL)) {
			$criteria = new CDbCriteria();
			$criteria->condition = "(MONTH(start_date) = MONTH(:currentMonth) OR MONTH(return_date) = MONTH(:currentMonth)) AND branch_id = :branch_id";
			$criteria->order = "start_date";
			$criteria->params = array(':currentMonth' => $_POST['currentMonth'], ":branch_id" => Yii::app()->session['branch_id']
			);
			$holidayModel = StaffHolidays::model()->findAll($criteria);
			$data = array();
			foreach ($holidayModel as $holiday) {
				$temp = array();
				$temp['name'] = $holiday->staff->first_name . " " . $holiday->staff->last_name;
				$temp['duration'] = date('d-M-Y', strtotime($holiday->start_date)) . " - " . date('d-M-Y', strtotime($holiday->return_date));
				$temp['reason'] = StaffHolidaysTypesReason::model()->findByPk($holiday->staff_holidays_reason_id)->type_of_absence;
				array_push($data, $temp);
			}
			echo CJSON::encode($data);
		} else {
			throw new CHttpException(404, "Yourn request is not valid");
		}
	}

	public function actionGetMultipleRate($age, $session_id, $booking_hours, $funded_hours) {
		$rate = FALSE;
		$chargeable_hours = 0;
		$max_age_group = SessionRateMapping::model()->findBySql("select max(age_group) as max_age_group from tbl_session_rate_mapping where session_id = " . $session_id)->max_age_group;
		if ($age >= $max_age_group) {
			$age = $max_age_group - 0.5; // Logic for calucalting age group if age is greater than the max age group
		}
		if ($age < $max_age_group) {
			$criteria = new CDbCriteria();
			$criteria->condition = "age_group > :age AND session_id = :session_id";
			$criteria->order = "age_group";
			$criteria->limit = "1";
			$criteria->params = array(':age' => $age, ':session_id' => $session_id
			);
			$mappingModel = SessionRateMapping::model()->find($criteria);
			$time_rate_array = array();
			for ($i = 1; $i <= 9; $i ++) {
				$time = "time_" . $i;
				$rate = "rate_" . $i;
				$time_rate_array[$mappingModel->$time] = $mappingModel->$rate;
			}
			$chargeable_hours = ($booking_hours - $funded_hours) < 0 ? 0 : ($booking_hours - $funded_hours);
			return (customFunctions::closest_time($time_rate_array, $chargeable_hours));
		} else {
			return $rate;
		}
	}

	public function actionGetMultipleRatesWeekdays($age, $session_id, $total_booking_days) {
		$rate = FALSE;
		$sessionModal = SessionRates::model()->findByPk($session_id);
		$average_rate = 0;
		$max_age_group = SessionRateWeekdayMapping::model()->findBySql("select max(age_group) as max_age_group from tbl_session_rate_weekday_mapping where session_id = " . $session_id);
		if ($age >= $max_age_group) {
			$age = $max_age_group - 0.5; // Logic for calucalting age group if age is greater than the max age group
		}
		if ($age < $max_age_group) {
			foreach ($total_booking_days as $booking_day) {
				$day_number = date('N', strtotime($booking_day));
				$criteria = new CDbCriteria();
				$criteria->condition = "age_group > :age AND session_id = :session_id AND day_" . $day_number . " = :day_number";
				$criteria->order = "age_group";
				$criteria->limit = "1";
				$criteria->params = array(':age' => $age, ':session_id' => $session_id, ':day_number' => $day_number
				);
				$mappingModel = SessionRateWeekdayMapping::model()->find($criteria);
				$rate = "rate_" . $day_number;
				$average_rate += $mappingModel->$rate;
			}
			$rate = sprintf('%0.2f', $average_rate / count($total_booking_days));
			return $rate;
		} else {
			return $rate;
		}
	}

	public function actionGetMultipleRatesTotalWeekdays($age, $session_id, $total_booking_days) {
		$rate = FALSE;
		$sessionModal = SessionRates::model()->findByPk($session_id);
		$max_age_group = SessionRateTotalWeekdayMapping::model()->findBySql("select max(age_group) as max_age_group from tbl_session_rate_weekday_mapping where session_id = " . $session_id);
		if ($age >= $max_age_group) {
			$age = $max_age_group - 0.5; // Logic for calucalting age group if age is greater than the max age group
		}
		if ($age < $max_age_group) {
			$criteria = new CDbCriteria();
			$criteria->condition = "age_group > :age AND session_id = :session_id";
			$criteria->order = "age_group";
			$criteria->limit = "1";
			$criteria->params = array(':age' => $age, ':session_id' => $session_id
			);
			$mappingModel = SessionRateTotalWeekdayMapping::model()->find($criteria);
			$total_weekday_rate_array = array();
			for ($i = 1; $i <= 7; $i ++) {
				$total_day = "total_day_" . $i;
				$rate = "rate_" . $i;
				$total_weekday_rate_array[$mappingModel->$total_day] = $mappingModel->$rate;
			}
			$rate = sprintf('%0.2f', (customFunctions::closest_day($total_weekday_rate_array, $total_booking_days)));
			return $rate;
		} else {
			return $rate;
		}
	}

	public function actionLoginEyman($token) {
		if (Yii::app()->user->isGuest) {
			if (isset($_GET['token'])) {
				$token = customFunctions::encryptor('decrypt', $_GET['token']);
				$tokenValue = explode('@#@', $token);
				$username = $tokenValue[0];
				$password = $tokenValue[1];
				$time1 = $tokenValue[2];
				$time2 = time();
				$interval = abs($time2 - $time1);
				$minutes = round($interval / 60);
				if ($minutes < 10) {
					// $username = 'demo.superadmin@eylog.co.uk';
					// $password = 'eyLogM';
					$model = new LoginForm();
					$model->username = $username;
					$model->password = $password;
					Yii::app()->user->setState("userName", customFunctions::encryptor("encrypt", $username));
					Yii::app()->user->setState("userPassword", customFunctions::encryptor("encrypt", $password));
					if ($model->validate() && $model->login()) {
						$model = User::model()->findByPk(Yii::app()->user->id);
						Yii::app()->session['userId'] = $model->id;
						Yii::app()->session['name'] = $model->first_name . " " . $model->last_name;
						Yii::app()->session['role'] = $model->role;
						if (Yii::app()->session['role'] === "superAdmin") {
							$companyModel = Company::model()->find();
							if (empty($companyModel)) {
								Yii::app()->session['company_id'] = 0;
								Yii::app()->session['branch_id'] = 0;
							} else {
								Yii::app()->session['company_id'] = $companyModel->id;
								$branchModel = Branch::model()->findByAttributes(array('company_id' => $companyModel->id
								));
								if (empty($branchModel)) {
									Yii::app()->session['branch_id'] = 0;
								} else {
									Yii::app()->session['branch_id'] = $branchModel->id;
								}
							}
							$this->redirect(array('site/dashboard'
							));
						} else
						if (Yii::app()->session['role'] === "companyAdministrator") {
							$mappingModel = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->session['userId']
							));
							if (!empty($mappingModel)) {
								$branchModel = Branch::model()->findByAttributes(array('company_id' => $mappingModel->company_id
								));
								Yii::app()->session['company_id'] = $mappingModel->company_id;
								Yii::app()->session['branch_id'] = $branchModel->id;
							} else {
								Yii::app()->session['company_id'] = 0;
								Yii::app()->session['branch_id'] = 0;
							}
							$this->redirect(array('site/dashboard'
							));
						} else
						if (Yii::app()->session['role'] === "areaManager") {
							$mappingModel = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->session['userId']
							));
							if (!empty($mappingModel)) {
								Yii::app()->session['company_id'] = $mappingModel->company_id;
								Yii::app()->session['branch_id'] = $mappingModel->branch_id;
							} else {
								Yii::app()->session['company_id'] = 0;
								Yii::app()->session['branch_id'] = 0;
							}
							$this->redirect(array('site/dashboard'
							));
						} else
						if (Yii::app()->session['role'] === "branchManager") {
							$mappingModel = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->session['userId']
							));
							if (!empty($mappingModel)) {
								Yii::app()->session['company_id'] = $mappingModel->company_id;
								Yii::app()->session['branch_id'] = $mappingModel->branch_id;
							} else {
								Yii::app()->session['company_id'] = 0;
								Yii::app()->session['branch_id'] = 0;
							}
							$this->redirect(array('site/dashboard'
							));
						} else
						if (Yii::app()->session['role'] === "branchAdmin") {
							$mappingModel = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->session['userId']
							));
							if (!empty($mappingModel)) {
								Yii::app()->session['company_id'] = $mappingModel->company_id;
								Yii::app()->session['branch_id'] = $mappingModel->branch_id;
							} else {
								Yii::app()->session['company_id'] = 0;
								Yii::app()->session['branch_id'] = 0;
							}
							$this->redirect(array('site/dashboard'
							));
						} else
						if (Yii::app()->session['role'] === "hrAdmin") {
							$mappingModel = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->session['userId']
							));
							if (!empty($mappingModel)) {
								Yii::app()->session['company_id'] = $mappingModel->company_id;
								Yii::app()->session['branch_id'] = $mappingModel->branch_id;
							} else {
								Yii::app()->session['company_id'] = 0;
								Yii::app()->session['branch_id'] = 0;
							}
							$this->redirect(array('site/dashboard'
							));
						} else
						if (Yii::app()->session['role'] === "hrStandard") {
							$mappingModel = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->session['userId']
							));
							if (!empty($mappingModel)) {
								Yii::app()->session['company_id'] = $mappingModel->company_id;
								Yii::app()->session['branch_id'] = $mappingModel->branch_id;
							} else {
								Yii::app()->session['company_id'] = 0;
								Yii::app()->session['branch_id'] = 0;
							}
							$this->redirect(array('site/dashboard'
							));
						} else
						if (Yii::app()->session['role'] === "accountsAdmin") {
							$mappingModel = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->session['userId']
							));
							if (!empty($mappingModel)) {
								Yii::app()->session['company_id'] = $mappingModel->company_id;
								Yii::app()->session['branch_id'] = $mappingModel->branch_id;
							} else {
								Yii::app()->session['company_id'] = 0;
								Yii::app()->session['branch_id'] = 0;
							}
							$this->redirect(array('site/dashboard'
							));
						} else {
							throw new CHttpException(404, "This Role is not allowed to access this page.");
						}
					} else {
						throw new CHttpException(404, "Access token is not valid.");
					}
				} else {
					throw new CHttpException(404, "Token has been expired.");
				}
			}
			// display the login form
			$this->render('login', array('model' => $model
			));
		} else {
			$this->redirect($this->createAbsoluteUrl('site/dashboard'));
		}
	}

	public function actionLoginToEyMan() {
		if (isset($_POST['token']) && !empty($_POST['token'])) {
			$token = $_POST['token'];
			$time = customFunctions::encryptor('decrypt', $_POST['time']);
			$token = customFunctions::encryptor('decrypt', $token);
			$tokenValue = explode('@#@', $token);
			$username = $tokenValue[0];
			$password = $tokenValue[1];
			// $username = 'demo.superadmin@eylog.co.uk';
			// $password = 'eyLogM';
			$model = new LoginForm();
			$model->username = $username;
			$model->password = $password;
			if ($model->validate()) {
				$newToken = customFunctions::encryptor('encrypt', $username . '@#@' . $password . '@#@' . $time);
				echo CJSON::encode(array('status' => 1, 'url' => $this->createAbsoluteUrl('site/loginEyman') . '?token=' . $newToken
				));
			} else {
				echo CJSON::encode(array('status' => 2, 'message' => $model->getErrors()
				));
			}
		} else {
			echo CJSON::encode(array('status' => 0, 'message' => 'Invalide request.'
			));
		}
		exit();
	}

	public function actionLoginEylog() {
		if (isset($_POST['isAjaxRequest']) && $_POST['isAjaxRequest'] == 1) {
			$branch = Branch::model()->findByPk(Yii::app()->session['branch_id']);
			$serverurl = $branch->api_url;
			$userName = customFunctions::encryptor("decrypt", Yii::app()->user->getState("userName"));
			$userPassword = customFunctions::encryptor("decrypt", Yii::app()->user->getState("userPassword"));
			$domainhost = $_SERVER['HTTP_HOST'];
			$password = $userPassword;
			$arrayforpost['username'] = $userName;
			$arrayforpost['password'] = $password;
			$arrayforpost['url'] = $serverurl;
			$innerurl = $serverurl . '/parentapp/checkManager';
			$cSession = curl_init();
			$optionsarray = array(CURLOPT_URL => $innerurl, CURLOPT_RETURNTRANSFER => 1, CURLOPT_HEADER => false, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => $arrayforpost, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_RETURNTRANSFER => true
			);
			curl_setopt_array($cSession, $optionsarray);
			$curlresult = curl_exec($cSession);
			curl_close($cSession);
			$userlogindetails = json_decode($curlresult);
			if (!empty($userlogindetails) && $userlogindetails->status == 1) {
				$cookie = "cookie.txt";
				$postdata = "log=" . $userName . "&pwd=" . $password . "&wp-submit=Log%20In&redirect_to=" . $serverurl . "index.php/custom-login/&testcookie=1";
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $serverurl . "/wp-login.php");
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // Make SSL true in live server
				curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
				curl_setopt($ch, CURLOPT_TIMEOUT, 60);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_HEADER, 1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_REFERER, $serverurl);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
				curl_setopt($ch, CURLOPT_POST, 1);
				$result = curl_exec($ch);
				curl_close($ch);
				preg_match_all('/^Set-Cookie:\s*([^\r\n]*)/mi', $result, $matches);
				$cookies = array();
				foreach ($matches[1] as $m) {
					list ($name, $value) = explode('=', $m, 2);
					$cookies[$name] = $value;
				}
				foreach ($cookies as $key => $cookievalue) {
					$allCookieVal = explode(";", $cookievalue);
					$cookieValue = urldecode($allCookieVal[0]);
					$cookiepathGet = explode("=", $allCookieVal[1]);
					$cookiePath = $cookiepathGet[1];
					$ishttponly = (isset($allCookieVal[2]) && !empty($allCookieVal[2])) ? TRUE : FALSE;
					setcookie($key, $cookieValue, 0, $cookiePath, '', '', $ishttponly);
				}
				// setcookie('eymancoo', $this->createAbsoluteUrl('site/dashboard'), time() + (86400 * 30), "/");
				$token = customFunctions::encryptor('encrypt', $userName . '@#@' . $password);
				echo CJSON::encode(array('status' => 1, 'url' => $serverurl . '?fSWfdRxcDfhgd=' . $token
				));
				exit();
			} else {
				echo CJSON::encode(array('status' => 0, 'message' => 'Invalide User Name/Password or Account is deactivated.'
				));
			}
		} else {
			echo CJSON::encode(array('status' => 0, 'message' => 'Invalide request'
			));
		}
	}

	public function actionRoomMonthlyOccupancy() {
		if (Yii::app()->request->isAjaxRequest) {
			@session_write_close();
			$response = array('status' => 0, 'data' => 0);
			if (isset($_POST['occupancyMonth'])) {
				$branchModal = Branch::currentBranch();
				$branchOccupancy = 0;
				$roomModal = $branchModal->roomWithShowBookings;
				$response = array(
					array(array("Room", "Occupancy", array("role" => "style")))
				);
				$currentMonthStartDate = date("Y-m-01", strtotime($_POST['occupancyMonth']));
				$currentMonthFinishDate = date("Y-m-t", strtotime($_POST['occupancyMonth']));
				$branchOperationDays = customFunctions::getDatesOfDays($currentMonthStartDate, $currentMonthFinishDate, explode(",", $branchModal->nursery_operation_days));
				$branchOperationTime = customFunctions::getHours($branchModal->child_bookings_start_time, $branchModal->child_bookings_finish_time);
				if (!empty($roomModal)) {
					$roomOccupancy = array();
					$roomColor = array();
					$room_ids = array();
					foreach ($roomModal as $room) {
						$roomOccupancy[$room->id] = 0;
						$response[1][] = $room->color;
						$room_ids[] += $room->id;
					}
					$bookingsModel = ChildBookings::model()->getBookings($currentMonthStartDate, $currentMonthFinishDate, $branchModal->id);
					if (!empty($bookingsModel)) {
						foreach ($bookingsModel as $bookings) {
							if (in_array($bookings->room_id, $room_ids)) {
								if ($bookings->childNds->is_deleted == 1) {
									continue;
								}
								if ($bookings->childNds->is_active == 0) {
									if (isset($bookings->childNds->leave_date) && !empty($bookings->childNds->leave_date)) {
										if (strtotime($bookings->childNds->leave_date) < strtotime($currentMonthStartDate)) {
											continue;
										}
									}
								}
								$bookingsDates = customFunctions::getDatesOfDays($bookings->start_date, $bookings->finish_date, explode(",", $bookings->childBookingsDetails->booking_days));
								if (is_array($bookingsDates)) {
									$bookingsDates = array_intersect($bookingsDates, $branchOperationDays);
									$childHoliday = ChildHolidays::model()->find([
										'select' => 'group_concat(date) as date',
										'condition' => 'date BETWEEN :start_date AND :finish_date AND exclude_from_invoice = 1 AND child_id = :child_id',
										'params' => [
											':start_date' => $booking->start_date,
											':finish_date' => $booking->finish_date,
											':child_id' => $booking->child_id
										]
									]);
									if ($childHoliday) {
										$bookingsDates = array_diff($bookingsDates, explode(",", $childHoliday->date));
									}
									if (!empty($bookingsDates)) {
										$roomOccupancy[$bookings->room_id] += (count($bookingsDates)) * customFunctions::round(customFunctions::getHours($bookings->start_time, $bookings->finish_time), 2);
										$branchOccupancy += (count($bookingsDates)) * customFunctions::round(customFunctions::getHours($bookings->start_time, $bookings->finish_time), 2);
									}
								}
							}
						}
					}
					if (!empty($roomOccupancy)) {
						foreach ($roomOccupancy as $key => $value) {
							$model = Room::model()->findByPk($key);
							$occupancy = customFunctions::getDividedResult(($model->capacity * $branchOperationTime * count($branchOperationDays)), $value);
							$response[0][] = [$model->name . " [" . $model->capacity . "]", (float) round($occupancy, 2), "color: #000000"];
						}
					}
					$branchOccupancy = customFunctions::getDividedResult(($branchModal->capacity * $branchOperationTime * count($branchOperationDays)), $branchOccupancy);
					$response[2] = $branchOccupancy * 100;
					$response[3] = $branchModal->capacity;
					echo CJSON::encode($response);
				} else {
					$response['status'] = 1;
					$response['data'] = 0;
					echo CJSON::encode($response);
				}
			} else {
				echo CJSON::encode($response);
			}
		} else {
			throw new CHttpException(404, "You are not allowed to access this page.");
		}
	}

	public function actionRoomMonthlyFte() {
		if (Yii::app()->request->isAjaxRequest) {
			@session_write_close();
			$response = array('status' => 0, 'data' => 0);
			if (isset($_POST['occupancyMonth'])) {
				$branchModal = Branch::currentBranch();
				$branchOccupancy = 0;
				$roomModal = $branchModal->roomWithShowBookings;
				$response = array(
					array(array("Room", "FTE", array("role" => "style")))
				);
				$currentMonthStartDate = date("Y-m-01", strtotime($_POST['occupancyMonth']));
				$currentMonthFinishDate = date("Y-m-t", strtotime($_POST['occupancyMonth']));
				$branchOperationDays = customFunctions::getDatesOfDays($currentMonthStartDate, $currentMonthFinishDate, explode(",", $branchModal->nursery_operation_days));
				$branchOperationTime = customFunctions::getHours($branchModal->child_bookings_start_time, $branchModal->child_bookings_finish_time);
				if (!empty($roomModal)) {
					$roomOccupancy = array();
					$roomColor = array();
					$room_ids = array();
					foreach ($roomModal as $room) {
						$roomOccupancy[$room->id] = 0;
						$response[1][] = $room->color;
						$room_ids[] += $room->id;
					}
					$bookingsModel = ChildBookings::model()->getBookings($currentMonthStartDate, $currentMonthFinishDate, $branchModal->id);
					if (!empty($bookingsModel)) {
						foreach ($bookingsModel as $bookings) {
							if (in_array($bookings->room_id, $room_ids)) {
								if ($bookings->childNds->is_deleted == 1) {
									continue;
								}
								if ($bookings->childNds->is_active == 0) {
									if (isset($bookings->childNds->leave_date) && !empty($bookings->childNds->leave_date)) {
										if (strtotime($bookings->childNds->leave_date) < strtotime($currentMonthStartDate)) {
											continue;
										}
									}
								}
								$bookingsDates = customFunctions::getDatesOfDays($bookings->start_date, $bookings->finish_date, explode(",", $bookings->childBookingsDetails->booking_days));
								if (is_array($bookingsDates)) {
									$bookingsDates = array_intersect($bookingsDates, $branchOperationDays);
									$childHoliday = ChildHolidays::model()->find([
										'select' => 'group_concat(date) as date',
										'condition' => 'date BETWEEN :start_date AND :finish_date AND exclude_from_invoice = 1 AND child_id = :child_id',
										'params' => [
											':start_date' => $booking->start_date,
											':finish_date' => $booking->finish_date,
											':child_id' => $booking->child_id
										]
									]);
									if ($childHoliday) {
										$bookingsDates = array_diff($bookingsDates, explode(",", $childHoliday->date));
									}
									if (!empty($bookingsDates)) {
										$roomOccupancy[$bookings->room_id] += (count($bookingsDates)) * customFunctions::round(customFunctions::getHours($bookings->start_time, $bookings->finish_time) / $branchOperationTime, 2);
										$branchOccupancy += (count($bookingsDates)) * customFunctions::round(customFunctions::getHours($bookings->start_time, $bookings->finish_time) / $branchOperationTime, 2);
									}
								}
							}
						}
					}
					if (!empty($roomOccupancy)) {
						foreach ($roomOccupancy as $key => $value) {
							$model = Room::model()->findByPk($key);
							$response[0][] = [$model->name . " [" . $model->capacity . "]", (float) customFunctions::round(round($value / count($branchOperationDays)), 2), "color: #000000"];
						}
					}
					$branchOccupancy = customFunctions::round(round($branchOccupancy / count($branchOperationDays)), 2);
					$response[2] = $branchOccupancy;
					$response[3] = $branchModal->capacity;
					echo CJSON::encode($response);
				} else {
					$response['status'] = 1;
					$response['data'] = 0;
					echo CJSON::encode($response);
				}
			} else {
				echo CJSON::encode($response);
			}
		} else {
			throw new CHttpException(404, "You are not allowed to access this page.");
		}
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return ChildParentalDetails the loaded model
	 * @throws CHttpException
	 */
	public function loadParentModel($id) {
		$model = Parents::model()->findByPk($id);
		if ($model === null)
			throw new CHttpException(404, 'The requested page does not exist.');
		return $model;
	}

	public function actionGoCardlessSuccess($id, $redirect_flow_id) {
		$model = $this->loadParentModel($id);
		$gcCustomerClient = customFunctions::getCustomerGoCardlessClient();
		if (!$gcCustomerClient) {
			throw new CHttpException(500, 'Direct Debit could not be connected.');
		}
		try {
			$redirectFlow = $gcCustomerClient->redirectFlows()->complete(
				$redirect_flow_id, ["params" => ["session_token" => $model->gocardless_session_token]]
			);
			if (Parents::model()->updateByPk($model->id, ['gocardless_customer' => $redirectFlow->links->customer, 'gocardless_mandate' => $redirectFlow->links->mandate])) {
				$this->redirect(array('site/goCardlessDone'));
			} else {
				throw new Exception('Direct Debit could not be connected.');
			}
		} catch (Exception $e) {
			throw new CHttpException(500, $e->getMessage());
		}
	}

	public function actionGoCardlessDone() {
		$this->layout = 'main_login';
		$this->render('goCardlessSuccess', array(
			'message' => 'Thank you! Direct Debit mandate created successfully.'
		));
	}

	public function actionWebhook() {

	}

	/*
	 * Method is called by GC webhook through middleware sever to update payment staus
	 */

	public function actionUpdateInvoiceGoCardless($invoice_id, $signature) {
		$decoded = \Firebase\JWT\JWT::decode($signature, Yii::app()->params['goCardless']['jwtKey'], array('HS256'));
		if ($decoded->paymentId != null) {
			$childInvoiceTransaction = ChildInvoiceTransactionsNds::model()->findByAttributes(array(
				'pg_transaction_id' => $decoded->paymentId
			));
			if ($childInvoiceTransaction) {
				$gcCustomerClient = customFunctions::getCustomerGoCardlessClient();
				if (!$gcCustomerClient) {
					throw new CHttpException(500, 'Direct Debit Client account does not exist.');
				}
				try {
					$payment = $gcCustomerClient->payments()->get($decoded->paymentId);
					$childInvoiceTransaction->pg_status = $payment->status;
					if (in_array($payment->status, array('cancelled', 'customer_approval_denied', 'failed', 'charged_back'))) {
						$childInvoiceTransaction->is_deleted = 1;
					}
					$childInvoiceTransaction->save(false);
				} catch (Exception $e) {
					echo $e->getMessage();
				}
			}
		}
	}

}
