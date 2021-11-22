<?php

use Firebase\JWT\JWT;

Yii::app()->clientScript->registerScript('helpers', '
          yii = {
              urls: {
                childIndex: ' . CJSON::encode(Yii::app()->createUrl('childPersonalDetails/index')) . ',
                waitList_enquiry: ' . CJSON::encode(Yii::app()->createUrl('enquiries/waitlistEnquiry')) . ',
                lost_enquiry: ' . CJSON::encode(Yii::app()->createUrl('enquiries/lostChildEnquiry')) . ',
                check_enquiry_email: ' . CJSON::encode(Yii::app()->createUrl('enquiries/checkEnquiryEmail')) . ',
                check_enquiry_phone: ' . CJSON::encode(Yii::app()->createUrl('enquiries/checkEnquiryPhone')) . ',
                enquiry_redirect_url: ' . CJSON::encode(Yii::app()->createAbsoluteUrl('enquiries/index')) . ',
                search_parent: ' . CJSON::encode(Yii::app()->createAbsoluteUrl('enquiries/searchParent')) . ',
                childPersonalDetails: ' . CJSON::encode(Yii::app()->createUrl('childPersonalDetails/update')) . ',
              }
          };
      ', CClientScript::POS_END);

class EnquiriesController extends eyManController {

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
        return 'updateYourChild,registerYourChild,appendParentForm,viewYourChild';
    }

    /**
     * Displays a particular model.
     * @param integer $id the ID of the model to be displayed
     */
    public function actionView($id) {
        $model = $this->loadModel($id);
        $prefereredSessions = SessionRates::model()->findAllByAttributes(['branch_id' => Branch::currentBranch()->id, 'is_modified' => 0], ['order' => 'name']);
        $this->render('view', array(
            'model' => $model,
            'prefereredSessions' => $prefereredSessions
        ));
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate() {
        $model = new Enquiries;
        $prefereredSessions = SessionRates::model()->findAllByAttributes(['branch_id' => Branch::currentBranch()->id, 'is_modified' => 0], ['order' => 'name']);
        $this->performAjaxValidation($model);
        if (isset($_POST['Next']) && isset($_POST['Enquiries'])) {
            $model->attributes = $_POST['Enquiries'];
            $model->branch_id = Yii::app()->session['branch_id'];
            $model->is_submitted = 1;
            if ($model->save())
                $this->redirect(array('update', 'id' => $model->id));
        }
        if (isset($_POST['Save']) && isset($_POST['Enquiries'])) {
            $model->attributes = $_POST['Enquiries'];
            $model->branch_id = Yii::app()->session['branch_id'];
            $model->is_submitted = 1;
            if ($model->save())
                $this->redirect(array('index'));
        }
        $this->render('create', array(
            'model' => $model,
            'prefereredSessions' => $prefereredSessions
        ));
    }

    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id the ID of the model to be updated
     */
    public function actionUpdate($id) {
        $model = $this->loadModel($id);
        $prefereredSessions = SessionRates::model()->findAllByAttributes(['branch_id' => Branch::currentBranch()->id, 'is_modified' => 0], ['order' => 'name']);
        $waitlistModel = clone $model;
        $waitlistModel->setScenario('waitlisted');
        $lostModel = clone $model;
        $lostModel->setScenario('lost');
        $this->performAjaxValidation($model);
        if (isset($_POST['Enquiries'])) {
            $model->attributes = $_POST['Enquiries'];
            if ($model->save()) {
                $this->redirect(array('index'));
            }
        }
        $this->render('update', array(
            'model' => $model,
            'waitlistModel' => $waitlistModel,
            'lostModel' => $lostModel,
            'prefereredSessions' => $prefereredSessions
        ));
    }

    public function actionEnrollChild($id) {
        $model = $this->loadModel($id);
        $childPersonalDetails = new ChildPersonalDetails;
        if ($model->is_enroll_child == 1) {
            throw new CHttpException(400, 'Child is already enrolled');
        }
        $childPersonalDetails = new ChildPersonalDetails;
        $childPersonalDetails->first_name = $model->child_first_name;
        $childPersonalDetails->last_name = $model->child_last_name;
        $childPersonalDetails->branch_id = $model->branch_id;
        $this->render('enrollChild', array(
            'model' => $childPersonalDetails
        ));
    }

    public function actionGetEnquiryData() {
        $model = new Enquiries;
        $enquiry = Enquiries::model()->findByPk($_POST['enquiry_id']);
        echo CJSON::encode($enquiry);
    }

    public function actionValidateEnquiryData() {
        $success = array('success' => 1);
        $model = new ChildPersonalDetails;
        if (isset($_POST['ChildPersonalDetails'])) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                $model->attributes = $_POST['ChildPersonalDetails'];
                $model->branch_id = Yii::app()->session['branch_id'];
                $enquiryModel = Enquiries::model()->findByPk($_POST['enquiry_id']);
                if (!empty($enquiryModel['child_detail'])) {

                    $child_detail = json_decode($enquiryModel['child_detail']);
                    $child_medical_details = $child_detail->ChildMedicalDetails;
                    $child_parental_details = $child_detail->Parents;
                    $child_parent_mappings = $child_detail->ParentChildMapping;
                    $child_general_details = $child_detail->ChildGeneralDetails;
                    $childDetails = $child_detail->ChildPersonalDetailsNds;

                    $no_of_children = count($childDetails->first_name);
                    $child_empty_key = '';
                    if (count($childDetails->first_name) > 1) {
                        foreach ($childDetails as $key => $value) {
                            if ($key == 'preffered_session') {
                                $value = json_encode($value);

                                // $child_details_array[0][$key] = $value;
                                // $child_details_array[1][$key] = $value;
                            } else {
                                foreach ($value as $key1 => $value1) {
                                    if ($key == 'first_name' && $value1 == '') {
                                        $child_empty_key = $key1;
                                    }

                                    $child_details_array['childPersonalDetails'][$key1][$key] = $value1;
                                }
                            }
                        }

                        unset($child_details_array['childPersonalDetails'][$child_empty_key]);
                    } else {
                        $child_details_array['childPersonalDetails'][0] = (array) $childDetails;
                    }

                    $child_details_array['childPersonalDetails'][0] += $_POST['ChildPersonalDetails'];

                    if (!empty($child_parental_details)) {
                        foreach ($child_parental_details as $key => $child_parental_detail) {
                            foreach ($child_parental_detail as $key1 => $parent_detail) {
                                if ($key == 'first_name' && $parent_detail == '') {
                                    $empty_key = $key1;
                                }
                                $child_details_array['childParentalDetails'][$key1][$key] = $parent_detail;
                            }
                        }
                        if (!empty($child_parent_mappings)) {
                            foreach ($child_parent_mappings as $key => $child_parent_mapping) {
                                foreach ($child_parent_mapping as $key1 => $parent_mapping) {
                                    $child_details_array['childParentalMapping'][$key1][$key] = $parent_mapping;
                                }
                            }
                        }
                    }

                    unset($child_details_array['childParentalMapping'][$empty_key]);
                    unset($child_details_array['childParentalDetails'][$empty_key]);

                    $child_general_details_array = array();
                    if (!empty($child_general_details)) {
                        $child_details_array['childGeneralDetails'][1]['ethinicity_id'] = isset($child_general_details->ethinicity_id[1]) ? ($child_general_details->ethinicity_id[1]) : NULL;
                        $child_details_array['childGeneralDetails'][1]['first_language'] = isset($child_general_details->first_language[1]) ? Language::model()->findByPk($child_general_details->first_language[1]) : NULL;
                        $child_details_array['childGeneralDetails'][1]['first_language'] = !empty($child_details_array['childGeneralDetails'][1]['first_language']) ? $child_details_array['childGeneralDetails'][1]['first_language']->name : NULL;
                        $child_details_array['childGeneralDetails'][1]['nationality_id'] = isset($child_general_details->nationality_id[1]) ? $child_general_details->nationality_id[1] : NULL;
                        $child_details_array['childGeneralDetails'][0]['ethinicity_id'] = isset($child_general_details->ethinicity_id[0]) ? $child_general_details->ethinicity_id[0] : NULL;
                        $child_details_array['childGeneralDetails'][0]['first_language'] = isset($child_general_details->first_language[0]) ? Language::model()->findByPk($child_general_details->first_language[0]) : NULL;
                        $child_details_array['childGeneralDetails'][0]['first_language'] = !empty($child_details_array['childGeneralDetails'][0]['first_language']) ? $child_details_array['childGeneralDetails'][0]['first_language']->name : NULL;
                        $child_details_array['childGeneralDetails'][0]['nationality_id'] = isset($child_general_details->ethinicity_id[0]) ? $child_general_details->ethinicity_id[0] : NULL;
                        unset($child_general_details->ethinicity_id, $child_general_details->first_language, $child_general_details->nationality_id);

                        $child_details_array['childGeneralDetails'][0] = array_merge($child_details_array['childGeneralDetails'][0], (array) $child_general_details);
                    }
                    unset($child_details_array['childGeneralDetails'][$child_empty_key]);
                    $child_details_array['childMedicalDetails'][0] = array_map(function($a) {
                        if ($a == 1) {
                            return (int) $a;
                        } else {
                            return $a;
                        }
                    }, (array) $child_medical_details);
                    $child_id = '';
                    $sibling_id = '';
                    if (!empty($child_details_array['childPersonalDetails'])) {
                        foreach ($child_details_array['childPersonalDetails'] as $ckey => $child_personal_detail) {
                            $childPersonalDetails = new ChildPersonalDetails;
                            $childPersonalDetails->attributes = $child_personal_detail;
                            $childPersonalDetails->branch_id = Yii::app()->session['branch_id'];

                            if ($childPersonalDetails->save()) {

                                if ($ckey == 0) {
                                    $child_id = $childPersonalDetails->id;
                                } else {
                                    $sibling_id = $childPersonalDetails->id;
                                }

                                foreach ($child_details_array['childParentalDetails'] as $pkey => $child_parental_detail) {
                                    $parent_id = '';
                                    if (!empty($child_parental_detail['email'])) {
                                        $parentModel = Parents::model()->findByAttributes(['email' => $child_parental_detail['email']]);
                                        $parent_id = $parentModel->id;
                                    }
                                    if (!$parent_id) {
                                        $parents = new Parents;
                                        $parents->attributes = $child_parental_detail;
                                        if (!$parents->save()) {
                                            echo CJSON::encode($parents->getErrors());
                                            $transaction->rollback();
                                            Yii::app()->end();
                                        }
                                    }
                                    $parentChildMapping = new ParentChildMapping;
                                    $parentChildMapping->attributes = $child_details_array['childParentalMapping'][$pkey];
                                    $parentChildMapping->child_id = ($ckey == 0) ? $child_id : $sibling_id;
                                    $parentChildMapping->parent_id = isset($parent_id) ? $parent_id : $parents->id;
                                    $parentChildMapping->order = $pkey + 1;
                                    if (!$parentChildMapping->save()) {
                                        echo CJSON::encode($parentChildMapping->getErrors());
                                        $transaction->rollback();
                                        Yii::app()->end();
                                    }
                                }
                                if (!empty($child_details_array['childGeneralDetails'])) {
                                    $childGeneralDetails = new ChildGeneralDetails;
                                    $child_details_array['childGeneralDetails'][$ckey] = array_map(function($a) {
                                        if ($a == 1) {
                                            return (int) $a;
                                        } else {
                                            return $a;
                                        }
                                    }, (array) $child_details_array['childGeneralDetails'][$ckey]);

                                    $childGeneralDetails->attributes = $child_details_array['childGeneralDetails'][$ckey];
                                    $childGeneralDetails->child_id = ($ckey == 0) ? $child_id : $sibling_id;
                                    if (!$childGeneralDetails->save()) {
                                        echo CJSON::encode($childGeneralDetails->getErrors());
                                        $transaction->rollback();
                                        Yii::app()->end();
                                    }
                                }


                                if (!empty($child_details_array['childMedicalDetails']) && $ckey == 0) {
                                    $childMedicalDetails = new ChildMedicalDetails;
                                    $childMedicalDetails->attributes = $child_details_array['childMedicalDetails'][$ckey];
                                    $childMedicalDetails->child_id = $child_id;
                                    if (!$childMedicalDetails->save()) {
                                        echo CJSON::encode($childMedicalDetails->getErrors());
                                        $transaction->rollback();
                                        Yii::app()->end();
                                    }
                                }
                            } else {
                                echo CJSON::encode($childPersonalDetails->getErrors());
                                $transaction->rollback();
                                Yii::app()->end();
                            }
                        }
                        $enquiryModel->is_enroll_child = 1;
                        $enquiryModel->status = 1;
                        $child_save = ChildPersonalDetails::model()->updateByPk($child_id, array('sibling_id' => !empty($sibling_id) ? $sibling_id : NULL));
                        if ($enquiryModel->save()) {
                            $success['child_id'] = $child_id;
                            echo CJSON::encode($success);
                            $transaction->commit();
                            Yii::app()->end();
                        }
                    }
                }
                if ($model->validate() && $model->save()) {
                    if (empty($enquiryModel->parent_email)) {
                        $parentModel = '';
                    } else {
                        $parentModel = Parents::model()->findByAttributes(['email' => $enquiryModel->parent_email]);
                    }

                    if (empty($parentModel)) {
                        $parentModel = new Parents;
                        $parentModel->first_name = $enquiryModel->parent_first_name;
                        $parentModel->last_name = $enquiryModel->parent_last_name;
                        $parentModel->email = $enquiryModel->parent_email;
                        $parentModel->mobile_phone = $enquiryModel->phone_mobile;
                        if (!$parentModel->save()) {
                            echo CJSON::encode($parentModel->getErrors());
                            $transaction->rollback();
                            Yii::app()->end();
                        }
                    }
                    $parentChildMapping = new ParentChildMapping;
                    $parentChildMapping->parent_id = $parentModel->id;
                    $parentChildMapping->child_id = $model->id;
                    $parentChildMapping->order = 1;

                    if ($parentChildMapping->save()) {
                        $enquiryModel->is_enroll_child = 1;
                        $enquiryModel->status = 1;
                        if ($enquiryModel->save()) {
                            $success['child_id'] = $model->id;
                            echo CJSON::encode($success);
                            $transaction->commit();
                        } else {
                            echo CJSON::encode($enquiryModel->getErrors());
                            $transaction->rollback();
                            Yii::app()->end();
                        }
                    } else {
                        echo CJSON::encode($parentChildMapping->getErrors());
                        $transaction->rollback();
                        Yii::app()->end();
                    }
                } else {
                    echo CJSON::encode($model->getErrors());
                    $transaction->rollback();
                }
            } catch (Exception $ex) {
                $transaction->rollback();
            }
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
    public function actionIndex() {
        $this->pageTitle = 'Enquiry | eyMan';
        $model = new Enquiries('search');
        $model->unsetAttributes(); // clear any default values
        $model->status = 0;
        $model->is_submitted = 1;
        if (isset($_GET['Enquiries']))
            $model->attributes = $_GET['Enquiries'];
        $this->render('index', array(
            'model' => $model,
        ));
    }

    /**
     * Manages all models.
     */
    public function actionAdmin() {
        $model = new Enquiries('search');
        $model->unsetAttributes(); // clear any default values
        if (isset($_GET['Enquiries']))
            $model->attributes = $_GET['Enquiries'];

        $this->render('admin', array(
            'model' => $model,
        ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return Enquiries the loaded model
     * @throws CHttpException
     */
    public function loadModel($id) {
        $model = Enquiries::model()->findByPk($id);
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }

    /**
     * Performs the AJAX validation.
     * @param Enquiries $model the model to be validated
     */
    protected function performAjaxValidation($models = array()) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'enquiries-form') {
            echo CActiveForm::validate($models);
            Yii::app()->end();
        }
    }

    public function actionEnrollEnquiry() {
        if (Yii::app()->request->isAjaxRequest) {
            $response = array('status' => 1, 'message' => "", 'enquiry_id' => '');
            $model = new Enquiries;
            $this->performAjaxValidation($model);
            if (isset($_POST['Enquiries'])) {
                $model->attributes = $_POST['Enquiries'];
                $model->branch_id = Yii::app()->session['branch_id'];
                if ($model->save()) {
                    $response['enquiry_id'] = $model->id;
                    echo CJSON::encode($response);
                } else {
                    echo CJSON::encode($model->getErrors());
                }
            }
        } else {
            throw new CHttpException('404', "Your request is not Valid.");
        }
    }

    public function actionWaitlistEnquiry() {
        if (Yii::app()->request->isAjaxRequest) {
            $model = $this->loadModel($_POST['waitlist_enquiry_id']);
            $model->setScenario('waitlisted');
            $model->attributes = $_POST['Enquiries'];
            $model->is_waitlisted = 1;
            $model->status = Enquiries::WAITLISTED;
            if ($model->save()) {
                echo CJSON::encode(array('status' => 1, 'message' => 'Enquiry has been successfully waitlisted.'));
            } else {
                echo CJSON::encode($model->getErrors());
            }
        } else {
            throw new CHttpException(404, "Your request is not valid.");
        }
    }

    public function actionLostChildEnquiry() {
        if (Yii::app()->request->isAjaxRequest) {
            $model = $this->loadModel($_POST['lost_enquiry_id']);
            $model->setScenario('lost');
            $model->attributes = $_POST['Enquiries'];
            $model->status = Enquiries::LOST;
            if ($model->save()) {
                echo CJSON::encode(array('status' => 1, 'message' => 'Enquiry has been successfully marked as Lost.'));
            } else {
                echo CJSON::encode($model->getErrors());
            }
        } else {
            throw new CHttpException(404, "Your request is not valid.");
        }
    }

    public function actionCheckEnquiryEmail() {
        if (Yii::app()->request->isAjaxRequest) {
            $model = Enquiries::model()->findByAttributes([
                'parent_email' => $_POST['parent_email'],
                'branch_id' => Branch::currentBranch()->id
            ]);
            if (!empty($model)) {
                echo CJSON::encode([
                    'status' => 1,
                    'model' => $model
                ]);
            } else {
                echo CJSON::encode([
                    'status' => 0,
                ]);
            }
        } else {
            throw new CHttpException(404, "Your request is not valid.");
        }
    }

    public function actionCheckEnquiryPhone() {
        if (Yii::app()->request->isAjaxRequest) {
            $model = Enquiries::model()->findByAttributes([
                'parent_email' => $_POST['phone_mobile'],
                'branch_id' => Branch::currentBranch()->id
            ]);
            if (!empty($model)) {
                echo CJSON::encode([
                    'status' => 1,
                    'model' => $model
                ]);
            } else {
                echo CJSON::encode([
                    'status' => 0,
                ]);
            }
        } else {
            throw new CHttpException(404, "Your request is not valid.");
        }
    }

    public function actionGetEnquiryDetail() {
        if (Yii::app()->request->isAjaxRequest) {
            $response = array();
            $enquiry_color = [
                0 => '#fbd75b',
                1 => "#51B749",
                2 => '#FFB878',
                3 => 'red'
            ];
            $start_date = date('Y-m-01', strtotime($_POST['currentMonth']));
            $finish_date = date('Y-m-t', strtotime($_POST['currentMonth']));
            $enquiryModal = Enquiries::model()->findAll([
                'select' => 'count(id) AS allEnquiry , status',
                'condition' => 'branch_id = :branch_id AND enquiry_date_time BETWEEN :start_date AND :finish_date ',
                'params' => [
                    ':branch_id' => Branch::currentBranch()->id,
                    ':start_date' => $start_date,
                    ':finish_date' => $finish_date,
                ],
                'group' => 'status'
            ]);
            if (!empty($enquiryModal)) {
                $response['color'] = array();
                $response['data'] = array();
                $temp[] = ['Enquiry Type', 'Total'];
                foreach ($enquiryModal as $key => $value) {
                    $temp[] = [Enquiries::getStatus($value['status']), (int) $value['allEnquiry']];
                    $response['color'][] = $enquiry_color[$value['status']];
                }
                $response['data'] = $temp;
                $response['error'] = false;
                echo CJSON::encode($response);
            } else {
                $response['error'] = true;
                echo CJSON::encode($response);
            }
        } else {
            throw new CHttpException(404, 'Your request is not valid.');
        }
    }

    public function actionSearchParent() {
        if (Yii::app()->request->isAjaxRequest) {
            $response = array();
            $email = "";
            $home_phone = "";
            $parent_phone = "";
            if (isset($_POST['enquiry_id']) && !empty($_POST['enquiry_id'])) {
                $enquiryModel = Enquiries::model()->findByPk($_POST['enquiry_id']);
                $email = $enquiryModel->parent_email;
                $home_phone = $enquiryModel->phone_home;
                $parent_phone = $enquiryModel->phone_mobile;
            } else {
                $email = $_POST['email'];
                if (isset($_POST['home_phone']) && !empty($_POST['home_phone']))
                    $home_phone = $_POST['home_phone'];
                $parent_phone = $_POST['parent_phone'];
            }
            $childParentModel = '';
            if (isset($email) && !empty($email)) {
                $childParentModel = Parents::model()->findByAttributes(['email' => $email]);
            }

            if (!empty($childParentModel)) {
                $response['parent_id'] = $childParentModel->id;
                $response['status'] = true;
                echo CJSON::encode($response);
            } else {
                $response['status'] = false;
                echo CJSON::encode($response);
            }
        } else {
            throw new CHttpException(404, 'Your request is not valid.');
        }
    }

    public function actionGetOpenEnquiryDetail() {
        if (Yii::app()->request->isAjaxRequest) {
            $model = new Enquiries();
            $start_date = date('Y-m-t', strtotime($_POST['currentMonth']));
            $new_enquiries = Enquiries::model()->findAll([
                'condition' => 'branch_id = :branch_id AND status = 0 AND enquiry_date_time <= :start_date',
                'params' => [
                    ':branch_id' => Yii::app()->session['branch_id'],
                    ':start_date' => $start_date,
                ],
                'order' => 'id DESC'
            ]);
            foreach ($new_enquiries as $enquiry) {
                $enquiry->parent_first_name = $enquiry->branch->name;
            }
            echo CJSON::encode($new_enquiries);
        } else {
            throw new CHttpException(404, 'Your request is not valid.');
        }
    }

    public function actionRegisterYourChild($token) {
        $this->layout = '//layouts/registerYourChild';
        $newUrl = "";
        $token = (array) JWT::decode($token, Yii::app()->params['jwtKey'], array('HS256'));
        $branchModel = BranchNds::model()->findByPk($token['branch_id']);
        $companyModel = CompanyNds::model()->findByPk($token['company_id']);
        $prefereredSessions = SessionRatesNds::model()->findAllByAttributes(['branch_id' => $token['branch_id'], 'is_modified' => 0, 'include_on_registration_form' => 1], ['order' => 'name']);
        if ($branchModel && $companyModel) {
            $childPersonalDetails = new ChildPersonalDetailsNds('registerYourChild');
            $childParentalDetails = new Parents;
            $childParentalDetails->scenario = "registerYourChild";
            $childGeneralDetails = new ChildGeneralDetails;
            $childMedicalDetails = new ChildMedicalDetails;
            $parentChildMapping = new ParentChildMapping;
            $enquiryModel = new EnquiriesNds;
            if (isset($_POST) && !empty($_POST)) {
                $_POST['ChildPersonalDetailsNds']['preffered_session'] =  !empty($_POST['ChildPersonalDetailsNds']['preffered_session'])? array_filter(array_map(function($a) {return(array_filter($a));}, $_POST['ChildPersonalDetailsNds']['preffered_session'])):$_POST['ChildPersonalDetailsNds']['preffered_session'];

                $transaction = Yii::app()->db->beginTransaction();
                try {
                    $enquiriesModel = new EnquiriesNds;
                    $enquiriesModel->scenario = "registerYourChild";
                    $enquiriesModel->child_first_name = $_POST['ChildPersonalDetailsNds']['first_name'][0];
                    $enquiriesModel->child_last_name = $_POST['ChildPersonalDetailsNds']['last_name'][0];
                    $enquiriesModel->child_dob = $_POST['ChildPersonalDetailsNds']['dob'][0];
                    $enquiriesModel->parent_first_name = $_POST['Parents']['first_name'][0];
                    $enquiriesModel->parent_last_name = $_POST['Parents']['last_name'][0];
                    $enquiriesModel->parent_email = $_POST['Parents']['email'][0];
                    $enquiriesModel->parent_address_1 = $_POST['Parents']['address_1'][0];
                    $enquiriesModel->parent_address_2 = $_POST['Parents']['address_2'][0];
                    $enquiriesModel->postcode = $_POST['Parents']['postcode'][0];
                    $enquiriesModel->phone_mobile = $_POST['Parents']['mobile_phone'][0];
                    $enquiriesModel->phone_home = $_POST['Parents']['home_phone'][0];
                    $enquiriesModel->preferred_session = $_POST['EnquiriesNds']['preferred_session'];
                    $enquiriesModel->preferred_time = $_POST['EnquiriesNds']['preferred_time'];
                    $enquiriesModel->branch_id = $branchModel->id;
                    $enquiriesModel->child_detail = json_encode($_POST, true);
                    if ($enquiriesModel->save()) {
                        $token = \Firebase\JWT\JWT::encode([
                                    'branch_id' => $branchModel->id,
                                    'company_id' => $companyModel->id,
                                    'enquiry_id' => $enquiriesModel->id
                                        ], Yii::app()->params['jwtKey']);
                        $newUrl = Yii::app()->createAbsoluteUrl('enquiries/updateYourChild', ['token' => $token]);
                        $viewUrl = Yii::app()->createAbsoluteUrl('enquiries/viewYourChild', ['token' => $token]);
                        if (EnquiriesNds::model()->updateAll(array('enquiry_url' => $newUrl, 'view_url' => $viewUrl), 'id=' . $enquiriesModel->id)) {
                            if ($_POST['button_value'] == 'draft') {
                                $subject = "Child Details Key";
                                $content = "Enquiry has been successfully saved. Please use " . $newUrl . " to update child details.";
                                $recipients = [
                                    'email' => $enquiriesModel->parent_email,
                                    'name' => $enquiriesModel->parent_first_name,
                                    'type' => 'to'
                                ];
                                $mandrill = new EymanMandril($subject, $content, "no name", [$recipients], "support@eylog.co.uk");
                                $flashMessageSuccess = "Enquiry has been successfully saved. Please use " . $newUrl . " to update child details.";
                            } else {
                                if(EnquiriesNds::model()->updateByPk($enquiriesModel->id, array("is_submitted"=> 1))){
                                    $subject = "Child Details Key";
                                    $subject = "New Child Enquiry";
                                    $content = "A new enquiry has been submitted.Please click on the <a href='" . $viewUrl . "'>link</a> to view the enquiry";
                                    $recipients = [
                                        'email' => $branchModel->email,
                                        'name' => $enquiriesModel->parent_first_name,
                                        'type' => 'to'
                                    ];
                                    $mandrill = new EymanMandril($subject, $content, "no name", [$recipients], "support@eylog.co.uk");
                                    $flashMessageSuccess = "Enquiry has been successfully submitted.";
                                }
                                else{
                                    $flashMessageSuccess = "Unable to save data";
                                    Yii::app()->user->setFlash("error", $flashMessageSuccess);
                                }
                            }

                            $response = $mandrill->sendEmail();
                            if (!empty($response) && $response[0]['status'] == 'sent') {

                                Yii::app()->user->setFlash("success", $flashMessageSuccess);
                                $transaction->commit();
                            } else {
                                $flashMessageSuccess = "Unable to save data";
                                Yii::app()->user->setFlash("error", $flashMessageSuccess);
                            }
                        } else {
                            throw new Exception("Some error occur while saving enquiry.");
                        }
                    } else {
                        throw new Exception("Some error occur while saving enquiry.");
                    }
                } catch (Exception $ex) {
                    $transaction->rollback();
                    echo $ex->getMessage();
                }
            }
            $this->render('registerYourChild', array(
                'newUrl' => $newUrl,
                'childPersonalDetails' => $childPersonalDetails,
                'childParentalDetails' => $childParentalDetails,
                'childGeneralDetails' => $childGeneralDetails,
                'childMedicalDetails' => $childMedicalDetails,
                'enquiryModel' => $enquiryModel,
                'parentChildMapping' => $parentChildMapping,
                'prefereredSessions' => $prefereredSessions,
                'branchModel' => $branchModel,
                'personalDetails' => "",
                'childDetails' => "",
                'generalDetails' => "",
                'parentalDetails' => ""));
        } else {
            throw new Exception('Your request is not valid.');
        }
    }

    public function actionUpdateYourChild($token) {

        $this->layout = '//layouts/registerYourChild';
        $newUrl = '';
        $token = (array) JWT::decode($token, Yii::app()->params['jwtKey'], array('HS256'));
        $branchModel = BranchNds::model()->findByPk($token['branch_id']);
        $companyModel = CompanyNds::model()->findByPk($token['company_id']);
        $enquiryModel = EnquiriesNds::model()->findByAttributes(['id' => $token['enquiry_id'],'is_submitted' => 0]);
        $prefereredSessions = SessionRatesNds::model()->findAllByAttributes(['branch_id' => $token['branch_id'], 'is_modified' => 0, 'include_on_registration_form' => 1], ['order' => 'name']);
        
        if (!empty($branchModel) && !empty($companyModel) && !empty($enquiryModel)) {
            $childDetails = json_decode($enquiryModel->child_detail, true);
            $personalDetails = $childDetails['ChildPersonalDetailsNds'];
            $generalDetails = $childDetails['ChildGeneralDetails'];
            $parentalDetails = $childDetails['Parents'];
            $medicalDetails = $childDetails['ChildMedicalDetails'];
            $parentChildMappingDetails = $childDetails['ParentChildMapping'];
            $childPersonalDetails = new ChildPersonalDetailsNds('registerYourChild');
            $childParentalDetails = new Parents;
            $childGeneralDetails = new ChildGeneralDetails;
            $childMedicalDetails = new ChildMedicalDetails;
            $parentChildMapping = new ParentChildMapping;
            if (isset($_POST) && !empty($_POST)) {
                $_POST['ChildPersonalDetailsNds']['preffered_session'] = !empty($_POST['ChildPersonalDetailsNds']['preffered_session'])? array_filter(array_map(function($a) {return(array_filter($a));}, $_POST['ChildPersonalDetailsNds']['preffered_session'])):$_POST['ChildPersonalDetailsNds']['preffered_session'];

                $transaction = Yii::app()->db->beginTransaction();
                try {
                    $enquiriesModel = EnquiriesNds::model()->findByPk($_POST['EnquiriesNds']['id']);
                    $enquiriesModel->scenario = "registerYourChild";
                    $enquiriesModel->child_first_name = $_POST['ChildPersonalDetailsNds']['first_name'][0];
                    $enquiriesModel->child_last_name = $_POST['ChildPersonalDetailsNds']['last_name'][0];
                    $enquiriesModel->child_dob = $_POST['ChildPersonalDetailsNds']['dob'][0];
                    $enquiriesModel->parent_first_name = $_POST['Parents']['first_name'][0];
                    $enquiriesModel->parent_last_name = $_POST['Parents']['last_name'][0];
                    $enquiriesModel->parent_email = $_POST['Parents']['email'][0];
                    $enquiriesModel->parent_address_1 = $_POST['Parents']['address_1'][0];
                    $enquiriesModel->parent_address_2 = $_POST['Parents']['address_2'][0];
                    $enquiriesModel->postcode = $_POST['Parents']['postcode'][0];
                    $enquiriesModel->phone_mobile = $_POST['Parents']['mobile_phone'][0];
                    $enquiriesModel->phone_home = $_POST['Parents']['home_phone'][0];
                    $enquiriesModel->preferred_session = $_POST['EnquiriesNds']['preferred_session'];
                    $enquiriesModel->preferred_time = $_POST['EnquiriesNds']['preferred_time'];
                    $enquiriesModel->child_detail = json_encode($_POST, true);
                    if ($enquiriesModel->save()) {
                        if ($_POST['button_value'] == 'draft') {
                            $newUrl = $enquiriesModel->enquiry_url;
                            $subject = "Child Details Key";
                            $content = "Enquiry has been successfully updated. Please use " . $newUrl . " to update child details.";
                            $recipients = [
                                'email' => $enquiriesModel->parent_email,
                                'name' => $enquiriesModel->parent_first_name,
                                'type' => 'to'
                            ];
                            $mandrill = new EymanMandril($subject, $content, "no name", [$recipients], "support@eylog.co.uk");
                            $flashMessageSuccess = "Enquiry has been successfully updated. Please use " . $newUrl . " to further update child details.";
                        } else {
                            if(EnquiriesNds::model()->updateByPk($enquiriesModel->id, array("is_submitted"=> 1))){
                              $newUrl = $enquiriesModel->view_url;
                              $subject = "New Child Enquiry";
                              $content = "A new enquiry has been submitted.Please click on the <a href='" . $newUrl . "'>link</a> to view the enquiry";
                              $recipients = [
                                  'email' => $branchModel->email,
                                  'name' => $enquiriesModel->parent_first_name,
                                  'type' => 'to'
                              ];
                              $mandrill = new EymanMandril($subject, $content, "no name", [$recipients], "support@eylog.co.uk");
                              $flashMessageSuccess = "Enquiry has been successfully submitted.";  
                            }
                            else{
                                $flashMessageSuccess = "Unable to save data";
                                Yii::app()->user->setFlash("error", $flashMessageSuccess);
                            }
                            
                        }
                        $response = $mandrill->sendEmail();
                        if (!empty($response) && $response[0]['status'] == 'sent') {
                            Yii::app()->user->setFlash("success", $flashMessageSuccess);
                            $transaction->commit();
                        } else {
                            $flashMessageSuccess = "Unable to save data";
                            Yii::app()->user->setFlash("error", $flashMessageSuccess);
                        }
                    } else {
                        throw new Exception("Some error occur while saving enquiry.");
                    }
                } catch (Exception $ex) {
                    $transaction->rollback();
                    throw new Exception("Some error occur while saving enquiry.");
                }
            }
            $this->render('updateYourChild', array('newUrl' => $newUrl, 'childPersonalDetails' => $childPersonalDetails, 'childParentalDetails' => $childParentalDetails,
                'childGeneralDetails' => $childGeneralDetails, 'childMedicalDetails' => $childMedicalDetails, 'medicalDetails' => $medicalDetails, 'enquiryModel' => $enquiryModel, 'personalDetails' => $personalDetails, 'generalDetails' => $generalDetails,
                'parentalDetails' => $parentalDetails, 'parentChildMapping' => $parentChildMapping, 'parentChildMappingDetails' => $parentChildMappingDetails, 'prefereredSessions' => $prefereredSessions, 'branchModel' => $branchModel));
        } else {
            throw new Exception('Your request is not valid.');
        }
    }

    public function actionViewYourChild($token) {

        $this->layout = '//layouts/registerYourChild';
        $newUrl = '';
        $token = (array) JWT::decode($token, Yii::app()->params['jwtKey'], array('HS256'));
        $branchModel = BranchNds::model()->with('session_rates')->findByPk($token['branch_id']);
        $companyModel = CompanyNds::model()->findByPk($token['company_id']);
        $enquiryModel = EnquiriesNds::model()->findByPk($token['enquiry_id']);
        $prefereredSessions = SessionRatesNds::model()->findAllByAttributes(['branch_id' => $token['branch_id'], 'is_modified' => 0, 'include_on_registration_form' => 1], ['order' => 'name']);

        if (!empty($branchModel) && !empty($companyModel) && !empty($enquiryModel)) {
            $childDetails = json_decode($enquiryModel->child_detail, true);
            $personalDetails = $childDetails['ChildPersonalDetailsNds'];
            $generalDetails = $childDetails['ChildGeneralDetails'];
            $parentalDetails = $childDetails['Parents'];
            $medicalDetails = $childDetails['ChildMedicalDetails'];
            $parentChildMappingDetails = $childDetails['ParentChildMapping'];
            $childPersonalDetails = new ChildPersonalDetailsNds('registerYourChild');
            $childParentalDetails = new Parents;
            $childGeneralDetails = new ChildGeneralDetails;
            $childMedicalDetails = new ChildMedicalDetails;
            $parentChildMapping = new ParentChildMapping;
            $this->render('viewYourChild', array('newUrl' => $newUrl, 'childPersonalDetails' => $childPersonalDetails, 'childParentalDetails' => $childParentalDetails,
                'childGeneralDetails' => $childGeneralDetails, 'childMedicalDetails' => $childMedicalDetails, 'medicalDetails' => $medicalDetails, 'enquiryModel' => $enquiryModel, 'personalDetails' => $personalDetails, 'generalDetails' => $generalDetails,
                'parentalDetails' => $parentalDetails, 'parentChildMapping' => $parentChildMapping, 'parentChildMappingDetails' => $parentChildMappingDetails, 'prefereredSessions' => $prefereredSessions, 'branchModel' => $branchModel));
        } else {
            throw new Exception('Your request is not valid.');
        }
    }

    public function actionMap() {
        Yii::app()->clientScript->registerScriptFile('https://maps.googleapis.com/maps/api/js?key=AIzaSyCcv_FsDJCo3UlJ1JMBPUMvgRUuo8JW_t8', CClientScript::POS_END);
        Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseUrl . '/js/enquiryMaps.js?version=1.0.0', CClientScript::POS_END);
        $this->render('map');
    }

    public function actionGetMapData() {
        if (Yii::app()->request->isPostRequest) {
            $model = Enquiries::model()->findAllByAttributes(array('branch_id' => Branch::currentBranch()->id, 'status' => 0));
            echo CJSON::encode(array('status' => 1, 'data' => $model));
        } else {
            throw new CHttpException(404, 'Your request is not valid');
        }
    }

    public function actionAppendParentForm($container_id) {
        $this->layout = '//layouts/registerYourChild';
        $childParentalDetails = new Parents;
        $parentChildMapping = new ParentChildMapping;
        $this->renderPartial('_appendParentForm', array('childParentalDetails' => $childParentalDetails, 'parentChildMapping' => $parentChildMapping, 'container_id' => $container_id), false, true);
        Yii::app()->end();
    }

}
