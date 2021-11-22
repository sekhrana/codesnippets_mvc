<?php
//Demo Commit
Yii::app()->clientScript->registerScript('helpers', '
          yii = {
              urls: {
                  checkCustomerAdministrator: ' . CJSON::encode(Yii::app()->createUrl('user/checkCustomerAdministrator')) . ',
                  base: ' . CJSON::encode(Yii::app()->baseUrl) . ',
              }
          };
      ', CClientScript::POS_HEAD);

class UserController extends eyManController {

    /**
     * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
     * using two-column layout. See 'protected/views/layouts/column2.php'.
     */
    public $layout = '//layouts/systemSettings';
    public $msg = '';

    public function filters() {
        return array(
            'rights',
        );
    }

    public function allowedActions() {
        return 'activate';
    }

    /**
     * Displays a particular model.
     * @param integer $id the ID of the model to be displayed
     */
    public function actionView($id) {
        $model = $this->loadModel($id);
        $role = $model->getRole();
        if ($role == "branchManager") {
            $branch_id = UserBranchMapping::model()->findByAttributes(array('user_id' => $model->id))->branch_id;
        }
        $userBranchMappingModal = UserBranchMapping::model()->findByAttributes(array('user_id' => $model->id));
        if ($role == "companyAdministrator") {
            $mapping = UserBranchMapping::model()->findByAttributes(array('user_id' => $model->id));
            $this->render('view', array(
                'model' => $model,
                'role' => $role,
                'branch_id' => 0,
                'company_id' => $mapping->company_id,
                'area_id' => 0
            ));
        } else if ($role == "branchManager") {
            $mapping = UserBranchMapping::model()->findByAttributes(array('user_id' => $model->id));
            $this->render('view', array(
                'model' => $model,
                'role' => $role,
                'branch_id' => $mapping->branch_id,
                'company_id' => 0,
                'area_id' => 0,
            ));
        } else if ($role == "branchAdmin") {
            $mapping = UserBranchMapping::model()->findByAttributes(array('user_id' => $model->id));
            $this->render('view', array(
                'model' => $model,
                'role' => $role,
                'branch_id' => $mapping->branch_id,
                'company_id' => 0,
                'area_id' => 0,
            ));
        } else if ($role == "areaManager") {
            $mapping = UserBranchMapping::model()->findAllByAttributes(array('user_id' => $model->id));
            $area_id = array();
            foreach ($mapping as $branch) {
                array_push($area_id, $branch->branch_id);
            }
            $this->render('view', array(
                'model' => $model,
                'role' => $role,
                'area_id' => $area_id,
                'branch_id' => 0,
                'company_id' => 0,
            ));
        } else if ($role == "hrAdmin") {
            $mapping = UserBranchMapping::model()->findByAttributes(array('user_id' => $model->id));
            $this->render('view', array(
                'model' => $model,
                'role' => $role,
                'branch_id' => 0,
                'company_id' => $mapping->company_id,
                'area_id' => 0,
            ));
        } else if ($role == "hrStandard") {
            $mapping = UserBranchMapping::model()->findByAttributes(array('user_id' => $model->id));
            $this->render('view', array(
                'model' => $model,
                'role' => $role,
                'branch_id' => 0,
                'company_id' => $mapping->company_id,
                'area_id' => 0,
            ));
        } else if ($role == "accountsAdmin") {
            $mapping = UserBranchMapping::model()->findByAttributes(array('user_id' => $model->id));
            $this->render('view', array(
                'model' => $model,
                'role' => $role,
                'branch_id' => 0,
                'company_id' => $mapping->company_id,
                'area_id' => 0,
            ));
        } else {
            throw new CHttpException(404, 'You are not allowed to access this page');
        }
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate() {
        $model = new User;
        $this->performAjaxValidation($model);
        if (isset($_POST['User'])) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                $model->attributes = $_POST['User'];
                $model->is_active = 0;
                $uploadedFile = CUploadedFile::getInstance($model, 'profile_photo');
                if ($uploadedFile !== NULL) {
                    $extensionName = CUploadedFile::getInstance($model, 'profile_photo')->extensionName;
                    $fileName = time() . '_' . uniqid() . '.' . $extensionName;
                    $model->profile_photo = $fileName;
                }
                if ($model->save()) {
                    $role = $_POST['User']['role'];
                    if ($role == User::COMPANY_ADMINISTRATOR) {
                        Yii::app()->authManager->assign($_POST['User']['role'], $model->id);
                        $userBranchMappingModel = new UserBranchMapping;
                        $userBranchMappingModel->user_id = $model->id;
                        $this->performAjaxValidation($userBranchMappingModel);
                        $userBranchMappingModel->company_id = Yii::app()->session['company_id'];
                        $userBranchMappingModel->branch_id  = 0;
                        $userBranchMappingModel->staff_id   = 0;
                        if (!$userBranchMappingModel->save()) {
                            throw new Exception(CHtml::errorSummary($userBranchMappingModel, "", "", array('class' => 'customErrors')));
                        }
                    }
                    if ($role == User::BRANCH_MANAGER) {
                        if ($model->is_manager_as_staff == 1) {
                            $staffModel = new StaffPersonalDetails;
                            $staffModel->first_name       = $model->first_name;
                            $staffModel->last_name        = $model->last_name;
                            $staffModel->email_1          = $model->email;
                            $staffModel->profile_photo    = $model->profile_photo;
                            $staffModel->branch_id        = $_POST['UserBranchMapping']['branch_id'];
                            if (!$staffModel->save()) {
                                throw new Exception(CHtml::errorSummary($staffModel, "", "", array('class' => 'customErrors')));
                            }
                        }
                        Yii::app()->authManager->assign($_POST['User']['role'], $model->id);
                        $userBranchMappingModel = new UserBranchMapping;
                        $userBranchMappingModel->user_id = $model->id;
                        $userBranchMappingModel->company_id = Yii::app()->session['company_id'];
                        $userBranchMappingModel->branch_id = $_POST['UserBranchMapping']['branch_id'];
                        $userBranchMappingModel->staff_id = isset($staffModel->id) ? $staffModel->id : 0 ;
                        if ($userBranchMappingModel->save()) {
                            $branch = Branch::model()->findByPk(Yii::app()->session['branch_id']);
                            if ($branch->is_integration_enabled == 1 && !empty($branch->api_url)) {
                                $branchExternalId = $branch->branchExternalId($_POST['UserBranchMapping']['branch_id']);
                                $ch = curl_init($branch->api_url . '/api-eyman/manager');
                                $manager_data = array(
                                    'api_key' => $branch->api_key,
                                    'api_password' => $branch->api_password,
                                    'manager' => array(
                                        array(
                                            'first_name' => $model->first_name,
                                            'last_name' => $model->last_name,
                                            'email' => $model->email,
                                            'branch_id' => $branchExternalId,
                                            'external_id' => "eyman-" . $model->id,
                                            'manager_practitioner' => 0
                                        )
                                    ),
                                );
                                $manager_data = json_encode($manager_data);
                                curl_setopt_array($ch, array(
                                    CURLOPT_FOLLOWLOCATION => 1,
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_POST => 1,
                                    CURLOPT_POSTFIELDS => $manager_data,
                                    CURLOPT_HEADER => 0,
                                    CURLOPT_USERAGENT => "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)",
                                    CURLOPT_SSL_VERIFYPEER => false,
                                ));
                                $response = curl_exec($ch);
                                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                curl_close($ch);
                                $response = json_decode($response, TRUE);
                                if ($response['response'][0]['status'] == "success" && $response['response'][0]['message'] == 'Added') {
                                    $model->external_id = $response['response'][0]['id'];
                                    if (!$model->save()) {
                                        throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors errorSummary')));
                                    }
                                }

                                if ($response['response'][0]['status'] == "failure") {
                                    Yii::app()->user->setFlash('error', $response['response'][0]['message']);
                                    throw new Exception($response['response'][0]['message']);
                                }
                            }
                        }
                    }
                    if ($role == User::BRANCH_ADMIN) {
                        Yii::app()->authManager->assign($_POST['User']['role'], $model->id);
                            $userBranchMappingModel = new UserBranchMapping;
                            $userBranchMappingModel->user_id = $model->id;
                            $userBranchMappingModel->company_id = Yii::app()->session['company_id'];
                            $userBranchMappingModel->branch_id = $_POST['UserBranchMapping']['branch_id'];
                            if (!$userBranchMappingModel->save()) {
                                throw new Exception(CHtml::errorSummary($userBranchMappingModel, "", "", array('class' => 'customErrors')));
                            }
                    }
                    if ($role == User::AREA_MANAGER) {
                        Yii::app()->authManager->assign($_POST['User']['role'], $model->id);
                        foreach ($_POST['area_id'] as $key => $value) {
                            $userBranchMappingModel = new UserBranchMapping;
                            $userBranchMappingModel->user_id = $model->id;
                            $userBranchMappingModel->company_id = Yii::app()->session['company_id'];
                            $userBranchMappingModel->branch_id = $value;
                            if (!$userBranchMappingModel->save()) {
                                throw new Exception(CHtml::errorSummary($userBranchMappingModel, "", "", array('class' => 'customErrors')));
                            }
                        }
                    }

                    if ($role == User::HR_ADMIN) {
                        Yii::app()->authManager->assign($_POST['User']['role'], $model->id);
                        $userBranchMappingModel = new UserBranchMapping;
                        $userBranchMappingModel->user_id = $model->id;
                        $userBranchMappingModel->company_id = Yii::app()->session['company_id'];
                        $userBranchMappingModel->branch_id = 0;
                        $userBranchMappingModel->staff_id = 0;
                        if (!$userBranchMappingModel->save()) {
                            throw new Exception(CHtml::errorSummary($userBranchMappingModel, "", "", array('class' => 'customErrors')));
                        }
                    }

                    if ($role == User::HR_STANDARD) {
                        Yii::app()->authManager->assign($_POST['User']['role'], $model->id);
                        $userBranchMappingModel = new UserBranchMapping;
                        $userBranchMappingModel->user_id = $model->id;
                        $userBranchMappingModel->company_id = Yii::app()->session['company_id'];
                        if (!$userBranchMappingModel->save()) {
                            throw new Exception(CHtml::errorSummary($userBranchMappingModel, "", "", array('class' => 'customErrors')));
                        }
                    }

                    if ($role == User::ACCOUNTS_ADMIN) {
                        Yii::app()->authManager->assign($_POST['User']['role'], $model->id);
                        $userBranchMappingModel = new UserBranchMapping;
                        $userBranchMappingModel->user_id = $model->id;
                        $userBranchMappingModel->company_id = Yii::app()->session['company_id'];
                        if (!$userBranchMappingModel->save()) {
                            throw new Exception(CHtml::errorSummary($userBranchMappingModel, "", "", array('class' => 'customErrors')));
                        }
                    }
                    if ($uploadedFile !== NULL) {
                        $profile_image = new EasyImage($uploadedFile->getTempName());
                        $thumb_image = new EasyImage($uploadedFile->getTempName());
                        $profile_image->resize($_POST['user_original_img_width'], $_POST['user_original_img_height'])->crop($_POST['user_img_width'], $_POST['user_img_height'], $_POST['user_offset_x'], $_POST['user_offset_y']);
                        $thumb_image->resize($_POST['user_original_img_width'], $_POST['user_original_img_height'])->crop($_POST['user_img_width'], $_POST['user_img_height'], $_POST['user_offset_x'], $_POST['user_offset_y'])->resize(70, 71);
                        $profile_image->save(Yii::app()->basePath . '/../uploaded_images/' . $fileName);
                        $thumb_image->save(Yii::app()->basePath . '/../uploaded_images/thumbs/' . $fileName);
                    }
                    $activateToken = md5(time() . uniqid() . $model->email);
                    $url = $this->createAbsoluteUrl('user/activate', array('activateToken' => $activateToken));
                    $to = $model->email;
                    $name = $model->first_name . " " . $model->last_name;
                    $subject = "Activate your eyMan account";
                    $content = "Hello " . "<b>" . $name . "</b>" . "<br/><br/>";
                    $content .= "Welcome to eyMan - Early Years Management!" . "<br/><br/>";
                    $content .= "To get started, click on the following link to confirm and activate your account - " . "<a href=$url>Click Here</a>" . "<br/><br/>";
                    $content .= "Please note that for security reasons this link will expire in 14 days, after which you will need to be sent a new invitation.";
                    $isSent = customFunctions::sendEmail($to, $name, $subject, $content);
                    if ($isSent == true) {
                        $model->activate_token = $activateToken;
                        $model->activate_token_expire = date("Y-m-d H:i:s", time() + 1209600);
                        $model->is_activate_token_valid = 1;
                        $model->save();
                    } else {
                        Yii::app()->user->setFlash('error', 'There was some problem sending the mail.Please try after sometime.');
                        $this->refresh();
                    }
                } else {
                    throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
                }
                $transaction->commit();
                $this->redirect(array('index'));
            } catch (Exception $ex) {
                Yii::app()->user->setFlash('error', $ex->getMessage());
                $transaction->rollback();
                @unlink(Yii::app()->basePath . '/../uploaded_images/' . $fileName);
                @unlink(Yii::app()->basePath . '/../uploaded_images/thumbs/' . $fileName);
                $this->refresh();
            }
        }

        $this->render('create', array(
            'model' => $model,
        ));
    }

    public function actionActivate() {
        $this->layout = 'main_login';
        $this->pageTitle = 'Activate your account';
        if (!isset($_GET['activateToken'])) {
            throw new CHttpException(400, "Page not found");
        }
        if (isset($_GET['activateToken'])) {
            $activateToken = $_GET['activateToken'];
            $criteria = new CDbCriteria();
            $criteria->condition = 'activate_token_expire >= :activate_token_expire and activate_token = :activate_token and is_activate_token_valid = 1 and is_active=0';
            $criteria->params = array(':activate_token_expire' => date('Y-m-d H:i:s'), ':activate_token' => $activateToken);
            $model = User::model()->find($criteria);
            if (empty($model)) {
                throw new CHttpException(400, "Token has expired or link is not valid");
            }
            $model->setScenario('createPassword');
            if (!empty($model)) {
                if (isset($_POST['User'])) {
                    $model->attributes = $_POST['User'];
                    if ($model->validate()) {
                        $model->is_active = 1;
                        $model->is_activate_token_valid = 0;
                        $model->password = CPasswordHelper::hashPassword($model->new_password);
                        if ($model->save()) {
                            Yii::app()->user->setFlash('activateAccount', 'Your account has been activated successfully.');
                            $this->redirect(array('site/login'));
                        }
                    }
                }
                $this->render('activate', array(
                    'model' => $model,
                ));
            }
        }
    }

    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id the ID of the model to be updated
     */
    public function actionUpdate($id) {
        $model = $this->loadModel($id);
        $role = $model->getRole();
        $this->performAjaxValidation($model);
        $model->prevProfilePhoto = $model->profile_photo;
        if (isset($_POST['User'])) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                $model->attributes = $_POST['User'];
                $model->is_active = 1;
                $oldImageName = $model->profile_photo;
                $uploadedFile = CUploadedFile::getInstance($model, 'profile_photo');
                if ($uploadedFile !== NULL) {
                    $extensionName = CUploadedFile::getInstance($model, 'profile_photo')->extensionName;
                    $fileName = time() . '_' . uniqid() . '.' . $extensionName;
                    $model->profile_photo = $fileName;
                }
                $model->profile_photo = isset($uploadedFile) ? $fileName : '';
                if ($model->save()) {
                    $oldMapping = UserBranchMapping::model()->findAllByAttributes(array('user_id' => $id));
                    $staff_id = '';
                    if(!empty($oldMapping)) {
                        foreach ($oldMapping as $userMapping) {
                            if($userMapping->staff_id){
                                $staff_id = $userMapping->staff_id;
                            }
                            $userMapping->delete();
                        }
                    }
                        
                    
                    if (Yii::app()->authManager->revoke($role, $id)) {
                        Yii::app()->authManager->save();
                    }
                    $role = $_POST['User']['role'];
                    if ($role == "companyAdministrator") {
                        Yii::app()->authManager->assign($_POST['User']['role'], $model->id);
                        $userBranchMappingModel = new UserBranchMapping;
                        $userBranchMappingModel->user_id = $model->id;
                        $this->performAjaxValidation($userBranchMappingModel);
                        $userBranchMappingModel->company_id = Yii::app()->session['company_id'];
                        $userBranchMappingModel->save();
                    }
                    if ($role == "branchManager") {
                        Yii::app()->authManager->assign($_POST['User']['role'], $model->id);
                        if ($model->is_manager_as_staff == 1) {
                            //$staffdetails = StaffPersonalDetails::model()->findByAttributes(array('email_1'=>$model->email));
                            if(empty($staffModel)) {
                                if($staff_id) {
                                    $staffModel = StaffPersonalDetails::model()->findByPk($staff_id);
                                }
                                /*else if(!empty($staffdetails)) {
                                    $staffModel = $staffdetails;
                                }*/
                                else {
                                    $staffModel = new StaffPersonalDetails;
                                }

                                $staffModel->first_name       = $model->first_name;
                                $staffModel->last_name        = $model->last_name;
                                $staffModel->email_1          = $model->email;
                                $staffModel->profile_photo    = $model->profile_photo;
                                $staffModel->branch_id        = $_POST['UserBranchMapping']['branch_id'];
                                if (!$staffModel->save()) {
                                    //echo '<pre>';print_r($staffModel->getErrors()['email_1'][0]); die;
                                    throw new Exception(CHtml::errorSummary($staffModel, "", "", array('class' => 'customErrors')));
                                }
                            }
                        }
                        
                        
                        $userBranchMappingModel = new UserBranchMapping;
                        $userBranchMappingModel->user_id = $model->id;
                        $userBranchMappingModel->company_id = Yii::app()->session['company_id'];
                        $userBranchMappingModel->branch_id = $_POST['UserBranchMapping']['branch_id'];
                        $userBranchMappingModel->staff_id = isset($staffModel->id) ? $staffModel->id : 0 ;
                        if ($userBranchMappingModel->save()) {

                            $branch = Branch::model()->findByPk(Yii::app()->session['branch_id']);

                            if ($branch->is_integration_enabled == 1 && !empty($branch->api_url)) {
                                $ch = curl_init($branch->api_url . '/api-eyman/manager');
                                $branchExternalId = $branch->branchExternalId($_POST['UserBranchMapping']['branch_id']);
                                $manager_data = array(
                                    'api_key' => $branch->api_key,
                                    'api_password' => $branch->api_password,
                                    'manager' => array(
                                        array(
                                            'first_name' => $model->first_name,
                                            'last_name' => $model->last_name,
                                            'email' => $model->email,
                                            'branch_id' => $branchExternalId,
                                            'external_id' => "eyman-" . $model->id,
                                        )
                                    ),
                                );

                                $manager_data = json_encode($manager_data);
                                curl_setopt_array($ch, array(
                                    CURLOPT_FOLLOWLOCATION => 1,
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_POST => 1,
                                    CURLOPT_POSTFIELDS => $manager_data,
                                    CURLOPT_HEADER => 0,
                                    CURLOPT_USERAGENT => "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)",
                                    CURLOPT_SSL_VERIFYPEER => false,
                                ));
                                $response = curl_exec($ch);
                                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                                curl_close($ch);
                                $response = json_decode($response, TRUE);
                                if ($response['response'][0]['status'] == "success" && $response['response'][0]['message'] == 'Updated') {
                                    $model->external_id = $response['response'][0]['id'];
                                    if (!$model->save()) {
                                        throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors errorSummary')));
                                    }
                                }

                                if ($response['response'][0]['status'] == "failure") {
                                    Yii::app()->user->setFlash('error', $response['response'][0]['message']);
                                    throw new Exception($response['response'][0]['message']);
                                }
                            }
                        }
                    }
                    if ($role == "branchAdmin") {
                        Yii::app()->authManager->assign($_POST['User']['role'], $model->id);
                        $userBranchMappingModel = new UserBranchMapping;
                        $userBranchMappingModel->user_id = $model->id;
                        $userBranchMappingModel->company_id = Yii::app()->session['company_id'];
                        $userBranchMappingModel->branch_id = $_POST['UserBranchMapping']['branch_id'];
                        $userBranchMappingModel->save();
                    }
                    if ($role == "areaManager") {
                        Yii::app()->authManager->assign($_POST['User']['role'], $model->id);
                        foreach ($_POST['area_id'] as $key => $value) {
                            $userBranchMappingModel = new UserBranchMapping;
                            $userBranchMappingModel->user_id = $model->id;
                            $userBranchMappingModel->company_id = Yii::app()->session['company_id'];
                            $userBranchMappingModel->branch_id = $value;
                            $userBranchMappingModel->save();
                        }
                    }
                    if ($role == "hrAdmin") {
                        Yii::app()->authManager->assign($_POST['User']['role'], $model->id);
                        $userBranchMappingModel = new UserBranchMapping;
                        $userBranchMappingModel->user_id = $model->id;
                        $userBranchMappingModel->company_id = Yii::app()->session['company_id'];
                        $userBranchMappingModel->save();
                    }
                    if ($role == "hrStandard") {
                        Yii::app()->authManager->assign($_POST['User']['role'], $model->id);
                        $userBranchMappingModel = new UserBranchMapping;
                        $userBranchMappingModel->user_id = $model->id;
                        $userBranchMappingModel->company_id = Yii::app()->session['company_id'];
                        $userBranchMappingModel->save();
                    }
                    if ($role == "accountsAdmin") {
                        Yii::app()->authManager->assign($_POST['User']['role'], $model->id);
                        $userBranchMappingModel = new UserBranchMapping;
                        $userBranchMappingModel->user_id = $model->id;
                        $userBranchMappingModel->company_id = Yii::app()->session['company_id'];
                        //$userBranchMappingModel->branch_id = $_POST['UserBranchMapping']['branch_id'];
                        $userBranchMappingModel->save();
                    }
                    if ($uploadedFile !== NULL) {
                        $profile_image = new EasyImage($uploadedFile->getTempName());
                        $thumb_image = new EasyImage($uploadedFile->getTempName());
                        $profile_image->resize($_POST['user_original_img_width'], $_POST['user_original_img_height'])->crop($_POST['user_img_width'], $_POST['user_img_height'], $_POST['user_offset_x'], $_POST['user_offset_y']);
                        $thumb_image->resize($_POST['user_original_img_width'], $_POST['user_original_img_height'])->crop($_POST['user_img_width'], $_POST['user_img_height'], $_POST['user_offset_x'], $_POST['user_offset_y'])->resize(70, 71);
                        $profile_image->save(Yii::app()->basePath . '/../uploaded_images/' . $fileName);
                        $thumb_image->save(Yii::app()->basePath . '/../uploaded_images/thumbs/' . $fileName);
                    }
                    $model->save();
                }
                $transaction->commit();
                $this->redirect(array('index'));
            } catch (Exception $ex) {
                Yii::app()->user->setFlash('error', $ex->getMessage());
                $transaction->rollback();
                @unlink(Yii::app()->basePath . '/../uploaded_images/' . $fileName);
                @unlink(Yii::app()->basePath . '/../uploaded_images/thumbs/' . $fileName);
                Yii::app()->user->setFlash('error', 'There was some problem creating the user.');
            }
        }
        if ($role == "companyAdministrator") {
            $mapping = UserBranchMapping::model()->findByAttributes(array('user_id' => $model->id));
            $this->render('update', array(
                'model' => $model,
                'role' => $role,
                'branch_id' => 0,
                'company_id' => $mapping->company_id,
                'area_id' => 0
            ));
        }  else if ($role == "branchManager") {
            $mapping = UserBranchMapping::model()->findByAttributes(array('user_id' => $model->id));
            $this->render('update', array(
                'model' => $model,
                'role' => $role,
                'branch_id' => $mapping->branch_id,
                'company_id' => 0,
                'area_id' => 0,
            ));
        } else if ($role == "branchAdmin") {
            $mapping = UserBranchMapping::model()->findByAttributes(array('user_id' => $model->id));
            $this->render('update', array(
                'model' => $model,
                'role' => $role,
                'branch_id' => $mapping->branch_id,
                'company_id' => 0,
                'area_id' => 0,
            ));
        } else if ($role == "areaManager") {
            $mapping = UserBranchMapping::model()->findAllByAttributes(array('user_id' => $model->id));
            $area_id = array();
            foreach ($mapping as $branch) {
                array_push($area_id, $branch->branch_id);
            }
            $this->render('update', array(
                'model' => $model,
                'role' => $role,
                'area_id' => $area_id,
                'branch_id' => 0,
                'company_id' => 0,
            ));
        } else if ($role == "hrAdmin") {
            $mapping = UserBranchMapping::model()->findByAttributes(array('user_id' => $model->id));
            $this->render('update', array(
                'model' => $model,
                'role' => $role,
                'branch_id' => 0,
                'company_id' => $mapping->company_id,
                'area_id' => 0,
            ));
        } else if ($role == "hrStandard") {
            $mapping = UserBranchMapping::model()->findByAttributes(array('user_id' => $model->id));
            $this->render('update', array(
                'model' => $model,
                'role' => $role,
                'branch_id' => 0,
                'company_id' => $mapping->company_id,
                'area_id' => 0,
            )); 
        }
        else if ($role == "accountsAdmin") {
            $mapping = UserBranchMapping::model()->findByAttributes(array('user_id' => $model->id));
            $this->render('update', array(
                'model' => $model,
                'role' => $role,
                'branch_id' => 0,
                'company_id' => $mapping->company_id,
                'area_id' => 0,
            ));
        } else {
            throw new CHttpException(404, 'You are not allowed to access this page');
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
                if (Yii::app()->user->id == $_POST['id']) {
                    Yii::app()->session->clear();
                    Yii::app()->session->destroy();
                    Yii::app()->user->logout();
                    echo CJSON::encode(array('status' => 2, 'url' => $this->createAbsoluteUrl('site/login')));
                } else {
                    echo CJSON::encode($response);
                }
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
        $this->pageTitle = 'User | eyMan';
        $model = new User('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['User']))
            $model->attributes = $_GET['User'];
        $this->render('index', array(
            'model' => $model,
        ));
    }

    /**
     * Manages all models.
     */
    public function actionAdmin() {
        $model = new User('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['User']))
            $model->attributes = $_GET['User'];
        $this->render('admin', array(
            'model' => $model,
        ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return User the loaded model
     * @throws CHttpException
     */
    public function loadModel($id) {
        $model = User::model()->findByPk($id);
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }

    /**
     * Performs the AJAX validation.
     * @param User $model the model to be validated
     */
    protected function performAjaxValidation($model) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'user-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

    /**
     *
     */
    public function actionChangepassword() {

        $model = new User;
        $model = User::model()->findByAttributes(array('id' => Yii::app()->user->id));
        $model->setScenario('changePassword');
        if (isset($_POST['User'])) {
            $model->attributes = $_POST['User'];
            if ($model->validate()) {

                $model->password = CPasswordHelper::hashPassword($model->new_password);
                if ($model->save()) {
                    Yii::app()->user->setFlash('success', 'Your password has been changed successfully');
                    $this->redirect('changepassword');
                }
            }
        }
        $this->render('changepassword', array('model' => $model));
    }

    public function actionCheckCustomerAdministrator() {
        $success = array('status' => 1);
        $error = array('status' => 0);
        if ($_POST['isAjaxRequest'] == 1) {
            $model = Authassignment::model()->findAllByAttributes(array('itemname' => 'customerAdministrator'));
            if (!empty($model)) {
                echo CJSON::encode($success);
            } else {
                echo CJSON::encode($error);
            }
        } else {
            throw new CHttpException(404, 'Your request is invalid');
        }
    }

    public function actionSendActivationLink() {
        if (Yii::app()->request->isAjaxRequest) {
            $model = User::model()->findByPk(Yii::app()->request->getPost('id'));
            if (!empty($model)) {
                if ($model->is_active == 1) {
                    echo CJSON::encode([
                        'status' => 0,
                        'message' => "User account is already active."
                    ]);
                    Yii::app()->end();
                }
                $transaction = Yii::app()->db->beginTransaction();
                try {
                    $activateToken = md5(time() . uniqid() . $model->email);
                    $url = $this->createAbsoluteUrl('user/activate', array('activateToken' => $activateToken));
                    $to = $model->email;
                    $name = $model->first_name . " " . $model->last_name;
                    $subject = "Activate your eyMan account";
                    $content = "Hello " . "<b>" . $name . "</b>" . "<br/><br/>";
                    $content .= "Welcome to eyMan - Early Years Management!" . "<br/><br/>";
                    $content .= "To get started, click on the following link to confirm and activate your account - " . "<a href=$url>Click Here</a>" . "<br/><br/>";
                    $content .= "Please note that for security reasons this link will expire in 14 days, after which you will need to be sent a new invitation.";

                    $model->activate_token = $activateToken;
                    $model->activate_token_expire = date("Y-m-d H:i:s", time() + 1209600);
                    $model->is_activate_token_valid = 1;
                    $model->is_active = 0;
                    $model->password = NULL;
                    if ($model->save()) {
                        if (customFunctions::sendEmail($to, $name, $subject, $content)) {
                            $transaction->commit();
                            echo CJSON::encode([
                                'status' => 1,
                                'message' => "Activation link has been sent successfully."
                            ]);
                            Yii::app()->end();
                        } else {
                            throw new Exception("Their seems to be some problem sending the activation Link.");
                        }
                    } else {
                        throw new Exception("Their seems to be some problem saving the activation Link.");
                    }
                } catch (Exception $ex) {
                    $transaction->rollback();
                    echo CJSON::encode([
                        'status' => 0,
                        'message' => $ex->getMessage(),
                    ]);
                    Yii::app()->end();
                }
            } else {
                echo CJSON::encode([
                    'status' => 0,
                    'message' => "User is not present on the system."
                ]);
                Yii::app()->end();
            }
        } else {
            throw new CHttpException(404, "Your request is not valid");
        }
    }

}
