<?php

//Demo Commit
class ChildMedicalDetailsController extends eyManController {

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
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate($child_id) {
        $this->layout = 'dashboard';
        $this->pageTitle = 'Create Medical Details | eyMan';

        if (isset($child_id)) {
            $childPersonalDetails = ChildPersonalDetails::model()->findByPk($child_id);
            if (empty($childPersonalDetails))
                throw new CHttpException(400, "No child found for the above id");
            if (!empty($childPersonalDetails)) {
                $model = new ChildMedicalDetails;
                $this->performAjaxValidation($model);
                if (isset($_POST['ChildMedicalDetails']) && isset($_POST['Save']) && !isset($_POST['Next'])) {
                    try {
                        $model->attributes = $_POST['ChildMedicalDetails'];
                        $model->child_id = $child_id;
                        if ($model->save()) {
                            $branchModal = Branch::model()->findByPk(Yii::app()->session['branch_id']);
                            $childPersonalDetails = ChildPersonalDetails::model()->findByPk($child_id);
                            $childGeneralDetails = ChildGeneralDetails::model()->findByAttributes(['child_id' => $child_id]);
                            $childFundingDetails = ChildFundingDetails::model()->countByAttributes(array('child_id' => $child_id));
                            if ($branchModal->is_integration_enabled == 1) {
                                if (!empty($branchModal->api_key) && !empty($branchModal->api_password) && !empty($branchModal->api_url)) {
                                    $ch = curl_init($branchModal->api_url . ChildPersonalDetails::CHILDREN_API_PATH);
                                    $child_data = array(
                                        'api_key' => $branchModal->api_key,
                                        'api_password' => $branchModal->api_password,
                                        'children' => array(
                                            array(
                                                'id' => (isset($childPersonalDetails->external_id) && !empty($childPersonalDetails->external_id)) ? $childPersonalDetails->external_id : NULL,
                                                'first_name' => $childPersonalDetails->first_name,
                                                'last_name' => $childPersonalDetails->last_name,
                                                'middle_name' => $childPersonalDetails->middle_name,
                                                'gender' => ($childPersonalDetails->gender == "MALE") ? "m" : "f",
                                                'dob' => date('Y-m-d', strtotime($childPersonalDetails->dob)),
                                                'start_date' => isset($childPersonalDetails->start_date) ? date("Y-m-d", strtotime($childPersonalDetails->start_date)) : NULL,
                                                'group_external_id' => isset($childPersonalDetails->room_id) ? $childPersonalDetails->room->external_id : NULL,
                                                'key_person_id' => !empty($childPersonalDetails->key_person) ? $childPersonalDetails->keyPerson->external_id : "",
                                                'key_person_external_id' => !empty($childPersonalDetails->key_person) ? 'eyman-' . $childPersonalDetails->key_person : NULL,
                                                'group_name' => isset($childPersonalDetails->room_id) ? ($childPersonalDetails->room->name) : "",
                                                'religion' => isset($childGeneralDetails->religion_id) ? $childGeneralDetails->religion_id : "",
                                                'medical_notes' => isset($model->medical_notes) ? $model->medical_notes : "",
                                                'child_notes' => isset($childGeneralDetails->notes) ? $childGeneralDetails->notes : "",
                                                'allergies' => isset($childGeneralDetails->general_notes) ? $childGeneralDetails->general_notes : "",
                                                'language' => isset($childGeneralDetails->first_language) ? $childGeneralDetails->first_language : "",
                                                'ethnicity' => isset($childGeneralDetails->ethinicity_id) ? trim($childGeneralDetails->ethinicity->name) : "",
                                                'dietary_requirements' => isset($childGeneralDetails->dietary_requirements) ? $childGeneralDetails->dietary_requirements : "",
                                                'eal' => (strtolower($childGeneralDetails->first_language) == "english") ? true : false,
                                                'sen' => ($childGeneralDetails->is_sen == 1) ? true : false,
                                                'funded' => ($childFundingDetails > 0) ? true : false,
                                                'external_id' => "eyman-" . $childPersonalDetails->id,
                                                'parents' => $childPersonalDetails->getParentsForEyLogIntegration()
                                            )
                                        ),
                                    );
                                    $child_data = json_encode($child_data);
                                    curl_setopt_array($ch, array(
                                        CURLOPT_FOLLOWLOCATION => 1,
                                        CURLOPT_RETURNTRANSFER => true,
                                        CURLOPT_POST => 1,
                                        CURLOPT_POSTFIELDS => $child_data,
                                        CURLOPT_HEADER => 0,
                                        CURLOPT_USERAGENT => "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)",
                                        CURLOPT_SSL_VERIFYPEER => false,
                                    ));
                                    $response = curl_exec($ch);
                                    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                    if (curl_errno($ch)) {
                                        curl_close($ch);
                                        Yii::app()->user->setFlash('integrationError', 'Curl Error - ' . curl_error($ch));
                                    } else {
                                        curl_close($ch);
                                        $response = json_decode($response, TRUE);
                                        if ($response['response'][0]['status'] == "success") {
                                            Yii::app()->user->setFlash('integrationSuccess', 'Child Data has been successfully saved to eyLog.');
                                            if (isset($response['response'][0]['parents'][0])) {
                                                if ($response['response'][0]['parents'][0]['status'] != "success") {
                                                    Yii::app()->user->setFlash('integrationError', $response['response'][0]['parents'][0]['message']);
                                                }
                                            }
                                            if (isset($response['response'][0]['parents'][1])) {
                                                if ($response['response'][0]['parents'][1]['status'] != "success") {
                                                    Yii::app()->user->setFlash('integrationError', $response['response'][0]['parents'][1]['message']);
                                                }
                                            }
                                        } else {
                                            Yii::app()->user->setFlash('integrationError', $response['response'][0]['message']);
                                        }
                                    }
                                } else {
                                    Yii::app()->user->setFlash('integrationError', "API key/password/url are not set in Branch Settings");
                                }
                            }

                            $this->redirect(array('update', 'child_id' => $child_id, 'medical_id' => $model->id));
                        }
                    } catch (Exception $ex) {
                        echo $model->getErrors();
                    }
                }

                if (isset($_POST['ChildMedicalDetails']) && isset($_POST['Next']) && !isset($_POST['Save'])) {
                    try {
                        $model->attributes = $_POST['ChildMedicalDetails'];
                        $model->child_id = $child_id;
                        if ($model->save()) {

                            //Integration API call starts here
                            $branchModal = Branch::model()->findByPk(Yii::app()->session['branch_id']);
                            $childPersonalDetails = ChildPersonalDetails::model()->findByPk($child_id);
                            $childGeneralDetails = ChildGeneralDetails::model()->findByAttributes(['child_id' => $child_id]);
                            $childFundingDetails = ChildFundingDetails::model()->countByAttributes(array('child_id' => $child_id));
                            if ($branchModal->is_integration_enabled == 1) {
                                if (!empty($branchModal->api_key) && !empty($branchModal->api_password) && !empty($branchModal->api_url)) {
                                    $ch = curl_init($branchModal->api_url . ChildPersonalDetails::CHILDREN_API_PATH);
                                    $child_data = array(
                                        'api_key' => $branchModal->api_key,
                                        'api_password' => $branchModal->api_password,
                                        'children' => array(
                                            array(
                                                'id' => (isset($childPersonalDetails->external_id) && !empty($childPersonalDetails->external_id)) ? $childPersonalDetails->external_id : NULL,
                                                'first_name' => $childPersonalDetails->first_name,
                                                'last_name' => $childPersonalDetails->last_name,
                                                'middle_name' => $childPersonalDetails->middle_name,
                                                'gender' => ($childPersonalDetails->gender == "MALE") ? "m" : "f",
                                                'dob' => date('Y-m-d', strtotime($childPersonalDetails->dob)),
                                                'start_date' => isset($childPersonalDetails->start_date) ? date("Y-m-d", strtotime($childPersonalDetails->start_date)) : NULL,
                                                'group_external_id' => isset($childPersonalDetails->room_id) ? $childPersonalDetails->room->external_id : NULL,
                                                'key_person_id' => !empty($childPersonalDetails->key_person) ? $childPersonalDetails->keyPerson->external_id : "",
                                                'key_person_external_id' => !empty($childPersonalDetails->key_person) ? 'eyman-' . $childPersonalDetails->key_person : NULL,
                                                'group_name' => isset($childPersonalDetails->room_id) ? ($childPersonalDetails->room->name) : "",
                                                'religion' => isset($childGeneralDetails->religion_id) ? $childGeneralDetails->religion_id : "",
                                                'medical_notes' => isset($model->medical_notes) ? $model->medical_notes : "",
                                                'child_notes' => isset($childGeneralDetails->notes) ? $childGeneralDetails->notes : "",
                                                'allergies' => isset($childGeneralDetails->general_notes) ? $childGeneralDetails->general_notes : "",
                                                'language' => isset($childGeneralDetails->first_language) ? $childGeneralDetails->first_language : "",
                                                'ethnicity' => isset($childGeneralDetails->ethinicity_id) ? trim($childGeneralDetails->ethinicity->name) : "",
                                                'dietary_requirements' => isset($childGeneralDetails->dietary_requirements) ? $childGeneralDetails->dietary_requirements : "",
                                                'eal' => (strtolower($childGeneralDetails->first_language) == "english") ? true : false,
                                                'sen' => ($childGeneralDetails->is_sen == 1) ? true : false,
                                                'funded' => ($childFundingDetails > 0) ? true : false,
                                                'external_id' => "eyman-" . $childPersonalDetails->id,
                                                'parents' => $childPersonalDetails->getParentsForEyLogIntegration()
                                            )
                                        ),
                                    );
                                    $child_data = json_encode($child_data);
                                    curl_setopt_array($ch, array(
                                        CURLOPT_FOLLOWLOCATION => 1,
                                        CURLOPT_RETURNTRANSFER => true,
                                        CURLOPT_POST => 1,
                                        CURLOPT_POSTFIELDS => $child_data,
                                        CURLOPT_HEADER => 0,
                                        CURLOPT_USERAGENT => "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)",
                                        CURLOPT_SSL_VERIFYPEER => false,
                                    ));
                                    $response = curl_exec($ch);
                                    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                    if (curl_errno($ch)) {
                                        curl_close($ch);
                                        Yii::app()->user->setFlash('integrationError', 'Curl Error - ' . curl_error($ch));
                                    } else {
                                        curl_close($ch);
                                        $response = json_decode($response, TRUE);
                                        if ($response['response'][0]['status'] == "success") {
                                            Yii::app()->user->setFlash('integrationSuccess', 'Child Data has been successfully saved to eyLog.');
                                            if (isset($response['response'][0]['parents'][0])) {
                                                if ($response['response'][0]['parents'][0]['status'] != "success") {
                                                    Yii::app()->user->setFlash('integrationError', $response['response'][0]['parents'][0]['message']);
                                                }
                                            }
                                            if (isset($response['response'][0]['parents'][1])) {
                                                if ($response['response'][0]['parents'][1]['status'] != "success") {
                                                    Yii::app()->user->setFlash('integrationError', $response['response'][0]['parents'][1]['message']);
                                                }
                                            }
                                        } else {
                                            Yii::app()->user->setFlash('integrationError', $response['response'][0]['message']);
                                        }
                                    }
                                } else {
                                    Yii::app()->user->setFlash('integrationError', "API key/password/url are not set in Branch Settings");
                                }
                            }
                            $this->redirect(array('childBookings/index', 'child_id' => $child_id));
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
    public function actionUpdate($child_id, $medical_id) {

        $this->layout = 'dashboard';
        $this->pageTitle = 'Update Medical Details | eyMan';
        $model = $this->loadModel($medical_id);
        $childPersonalDetails = ChildPersonalDetails::model()->findByPk($child_id);
        $this->performAjaxValidation($model);

        if (isset($_POST['ChildMedicalDetails']) && isset($_POST['Update']) && !isset($_POST['Next'])) {
            $model->attributes = $_POST['ChildMedicalDetails'];
            if ($model->save()) {
                //Integration API call starts here
                $branchModal = Branch::model()->findByPk(Yii::app()->session['branch_id']);
                $childPersonalDetails = ChildPersonalDetails::model()->findByPk($child_id);
                $childGeneralDetails = ChildGeneralDetails::model()->findByAttributes(['child_id' => $child_id]);
                $childFundingDetails = ChildFundingDetails::model()->countByAttributes(array('child_id' => $child_id));
                if ($branchModal->is_integration_enabled == 1) {
                    if (!empty($branchModal->api_key) && !empty($branchModal->api_password) && !empty($branchModal->api_url)) {
                        $ch = curl_init($branchModal->api_url . ChildPersonalDetails::CHILDREN_API_PATH);
                        $child_data = array(
                            'api_key' => $branchModal->api_key,
                            'api_password' => $branchModal->api_password,
                            'children' => array(
                                array(
                                    'id' => (isset($childPersonalDetails->external_id) && !empty($childPersonalDetails->external_id)) ? $childPersonalDetails->external_id : NULL,
                                    'first_name' => $childPersonalDetails->first_name,
                                    'last_name' => $childPersonalDetails->last_name,
                                    'middle_name' => $childPersonalDetails->middle_name,
                                    'gender' => ($childPersonalDetails->gender == "MALE") ? "m" : "f",
                                    'dob' => date('Y-m-d', strtotime($childPersonalDetails->dob)),
                                    'start_date' => isset($childPersonalDetails->start_date) ? date("Y-m-d", strtotime($childPersonalDetails->start_date)) : NULL,
                                    'group_external_id' => isset($childPersonalDetails->room_id) ? $childPersonalDetails->room->external_id : NULL,
                                    'key_person_id' => !empty($childPersonalDetails->key_person) ? $childPersonalDetails->keyPerson->external_id : "",
                                    'key_person_external_id' => !empty($childPersonalDetails->key_person) ? 'eyman-' . $childPersonalDetails->key_person : NULL,
                                    'group_name' => isset($childPersonalDetails->room_id) ? ($childPersonalDetails->room->name) : "",
                                    'religion' => isset($childGeneralDetails->religion_id) ? $childGeneralDetails->religion_id : "",
                                    'medical_notes' => isset($model->medical_notes) ? $model->medical_notes : "",
                                    'child_notes' => isset($childGeneralDetails->notes) ? $childGeneralDetails->notes : "",
                                    'allergies' => isset($childGeneralDetails->general_notes) ? $childGeneralDetails->general_notes : "",
                                    'language' => isset($childGeneralDetails->first_language) ? $childGeneralDetails->first_language : "",
                                    'ethnicity' => isset($childGeneralDetails->ethinicity_id) ? trim($childGeneralDetails->ethinicity->name) : "",
                                    'dietary_requirements' => isset($childGeneralDetails->dietary_requirements) ? $childGeneralDetails->dietary_requirements : "",
                                    'eal' => (strtolower($childGeneralDetails->first_language) == "english") ? true : false,
                                    'sen' => ($childGeneralDetails->is_sen == 1) ? true : false,
                                    'funded' => ($childFundingDetails > 0) ? true : false,
                                    'external_id' => "eyman-" . $childPersonalDetails->id,
                                    'parents' => $childPersonalDetails->getParentsForEyLogIntegration()
                                )
                            ),
                        );
                        $child_data = json_encode($child_data);
                        curl_setopt_array($ch, array(
                            CURLOPT_FOLLOWLOCATION => 1,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_POST => 1,
                            CURLOPT_POSTFIELDS => $child_data,
                            CURLOPT_HEADER => 0,
                            CURLOPT_USERAGENT => "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)",
                            CURLOPT_SSL_VERIFYPEER => false,
                        ));
                        $response = curl_exec($ch);
                        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        if (curl_errno($ch)) {
                            curl_close($ch);
                            Yii::app()->user->setFlash('integrationError', 'Curl Error - ' . curl_error($ch));
                        } else {
                            curl_close($ch);
                            $response = json_decode($response, TRUE);
                            if ($response['response'][0]['status'] == "success") {
                                Yii::app()->user->setFlash('integrationSuccess', 'Child Data has been successfully saved to eyLog.');
                                if (isset($response['response'][0]['parents'][0])) {
                                    if ($response['response'][0]['parents'][0]['status'] != "success") {
                                        Yii::app()->user->setFlash('integrationError', $response['response'][0]['parents'][0]['message']);
                                    }
                                }
                                if (isset($response['response'][0]['parents'][1])) {
                                    if ($response['response'][0]['parents'][1]['status'] != "success") {
                                        Yii::app()->user->setFlash('integrationError', $response['response'][0]['parents'][1]['message']);
                                    }
                                }
                            } else {
                                Yii::app()->user->setFlash('integrationError', $response['response'][0]['message']);
                            }
                        }
                    } else {
                        Yii::app()->user->setFlash('integrationError', "API key/password/url are not set in Branch Settings");
                    }
                }
            }
            $this->redirect(array('update', 'child_id' => $child_id, 'medical_id' => $model->id));
        }

        if (isset($_POST['ChildMedicalDetails']) && !isset($_POST['Update']) && isset($_POST['Next'])) {
            $model->attributes = $_POST['ChildMedicalDetails'];
            if ($model->save()) {
                //Integration API call starts here
                $branchModal = Branch::model()->findByPk(Yii::app()->session['branch_id']);
                $childGeneralDetails = ChildGeneralDetails::model()->findByAttributes(['child_id' => $child_id]);
                $childFundingDetails = ChildFundingDetails::model()->countByAttributes(array('child_id' => $child_id));
                if ($branchModal->is_integration_enabled == 1) {
                    if (!empty($branchModal->api_key) && !empty($branchModal->api_password) && !empty($branchModal->api_url)) {
                        $ch = curl_init($branchModal->api_url . ChildPersonalDetails::CHILDREN_API_PATH);
                        $child_data = array(
                            'api_key' => $branchModal->api_key,
                            'api_password' => $branchModal->api_password,
                            'children' => array(
                                array(
                                    'id' => (isset($childPersonalDetails->external_id) && !empty($childPersonalDetails->external_id)) ? $childPersonalDetails->external_id : NULL,
                                    'first_name' => $childPersonalDetails->first_name,
                                    'last_name' => $childPersonalDetails->last_name,
                                    'middle_name' => $childPersonalDetails->middle_name,
                                    'gender' => ($childPersonalDetails->gender == "MALE") ? "m" : "f",
                                    'dob' => date('Y-m-d', strtotime($childPersonalDetails->dob)),
                                    'start_date' => isset($childPersonalDetails->start_date) ? date("Y-m-d", strtotime($childPersonalDetails->start_date)) : NULL,
                                    'group_external_id' => isset($childPersonalDetails->room_id) ? $childPersonalDetails->room->external_id : NULL,
                                    'key_person_id' => !empty($childPersonalDetails->key_person) ? $childPersonalDetails->keyPerson->external_id : "",
                                    'key_person_external_id' => !empty($childPersonalDetails->key_person) ? 'eyman-' . $childPersonalDetails->key_person : NULL,
                                    'group_name' => isset($childPersonalDetails->room_id) ? ($childPersonalDetails->room->name) : "",
                                    'religion' => isset($childGeneralDetails->religion_id) ? $childGeneralDetails->religion_id : "",
                                    'medical_notes' => isset($model->medical_notes) ? $model->medical_notes : "",
                                    'child_notes' => isset($childGeneralDetails->notes) ? $childGeneralDetails->notes : "",
                                    'allergies' => isset($childGeneralDetails->general_notes) ? $childGeneralDetails->general_notes : "",
                                    'language' => isset($childGeneralDetails->first_language) ? $childGeneralDetails->first_language : "",
                                    'ethnicity' => isset($childGeneralDetails->ethinicity_id) ? trim($childGeneralDetails->ethinicity->name) : "",
                                    'dietary_requirements' => isset($childGeneralDetails->dietary_requirements) ? $childGeneralDetails->dietary_requirements : "",
                                    'eal' => (strtolower($childGeneralDetails->first_language) == "english") ? true : false,
                                    'sen' => ($childGeneralDetails->is_sen == 1) ? true : false,
                                    'funded' => ($childFundingDetails > 0) ? true : false,
                                    'external_id' => "eyman-" . $childPersonalDetails->id,
                                    'parents' => $childPersonalDetails->getParentsForEyLogIntegration()
                                )
                            ),
                        );
                        $child_data = json_encode($child_data);
                        curl_setopt_array($ch, array(
                            CURLOPT_FOLLOWLOCATION => 1,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_POST => 1,
                            CURLOPT_POSTFIELDS => $child_data,
                            CURLOPT_HEADER => 0,
                            CURLOPT_USERAGENT => "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)",
                            CURLOPT_SSL_VERIFYPEER => false,
                        ));
                        $response = curl_exec($ch);
                        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        if (curl_errno($ch)) {
                            curl_close($ch);
                            Yii::app()->user->setFlash('integrationError', 'Curl Error - ' . curl_error($ch));
                        } else {
                            curl_close($ch);
                            $response = json_decode($response, TRUE);
                            if ($response['response'][0]['status'] == "success") {
                                Yii::app()->user->setFlash('integrationSuccess', 'Child Data has been successfully saved to eyLog.');
                                if (isset($response['response'][0]['parents'][0])) {
                                    if ($response['response'][0]['parents'][0]['status'] != "success") {
                                        Yii::app()->user->setFlash('integrationError', $response['response'][0]['parents'][0]['message']);
                                    }
                                }
                                if (isset($response['response'][0]['parents'][1])) {
                                    if ($response['response'][0]['parents'][1]['status'] != "success") {
                                        Yii::app()->user->setFlash('integrationError', $response['response'][0]['parents'][1]['message']);
                                    }
                                }
                            } else {
                                Yii::app()->user->setFlash('integrationError', $response['response'][0]['message']);
                            }
                        }
                    } else {
                        Yii::app()->user->setFlash('integrationError', "API key/password/url are not set in Branch Settings");
                    }
                }
                $this->redirect(array('childBookings/index', 'child_id' => $child_id));
            } else {
                Yii::app()->user->setFlash('error', "Their seems to be some problem saving the medical details.");
                $this->render('update', array(
                    'model' => $model
                ));
                Yii::app()->end();
            }
        }

        $this->render('update', array(
            'model' => $model,
        ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return ChildMedicalDetails the loaded model
     * @throws CHttpException
     */
    public function loadModel($id) {
        $model = ChildMedicalDetails::model()->findByPk($id);
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }

    /**
     * Performs the AJAX validation.
     * @param ChildMedicalDetails $model the model to be validated
     */
    protected function performAjaxValidation($model) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'child-medical-details-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

}
