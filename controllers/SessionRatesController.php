<?php

Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseUrl . '/js/form-validator.js?version=1.0.0', CClientScript::POS_END);
Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseUrl . '/js/sessionRates.js?version=1.0.0', CClientScript::POS_END);

class SessionRatesController extends eyManController {

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
        $model = $this->loadModel($id);
        $sessionModifiedModel = new CActiveDataProvider('SessionRatesHistory', array(
            'criteria' => array(
                'condition' => 'session_id = :session_id',
                'order' => 'id DESC',
                'params' => array(':session_id' => $model->id)
            ),
            'pagination' => array(
                'pageSize' => 20,
            ),
        ));
        if ($model->is_multiple_rates == 1 && $model->multiple_rates_type == 1) {
            $sessionRatesMappingModel = SessionRateMapping::model()->findAllByAttributes(array('session_id' => $model->id));
            $age_group = array();
            $time = array();
            $rate_age_group = array();
            foreach ($sessionRatesMappingModel as $sessionRatesMapping) {
                $temp = array();
                $temp2 = array();
                array_push($age_group, $sessionRatesMapping->age_group);
                $temp['rate_minimum'] = $sessionRatesMapping->rate_minimum;
                $temp['rate_1'] = $sessionRatesMapping->rate_1;
                $temp['rate_2'] = $sessionRatesMapping->rate_2;
                $temp['rate_3'] = $sessionRatesMapping->rate_3;
                $temp['rate_4'] = $sessionRatesMapping->rate_4;
                $temp['rate_5'] = $sessionRatesMapping->rate_5;
                $temp['rate_6'] = $sessionRatesMapping->rate_6;
                $temp['rate_7'] = $sessionRatesMapping->rate_7;
                $temp['rate_8'] = $sessionRatesMapping->rate_8;
                $temp['rate_9'] = $sessionRatesMapping->rate_9;
                array_push($rate_age_group, $temp);
                $temp2['time_minimum'] = $sessionRatesMapping->time_minimum;
                $temp2['time_1'] = $sessionRatesMapping->time_1;
                $temp2['time_2'] = $sessionRatesMapping->time_2;
                $temp2['time_3'] = $sessionRatesMapping->time_3;
                $temp2['time_4'] = $sessionRatesMapping->time_4;
                $temp2['time_5'] = $sessionRatesMapping->time_5;
                $temp2['time_6'] = $sessionRatesMapping->time_6;
                $temp2['time_7'] = $sessionRatesMapping->time_7;
                $temp2['time_8'] = $sessionRatesMapping->time_8;
                $temp2['time_9'] = $sessionRatesMapping->time_9;
                array_push($time, $temp2);
            }
        }
        if ($model->is_multiple_rates == 1 && $model->multiple_rates_type == 2) {
            $sessionRatesWeekdayMappingModel = SessionRateWeekdayMapping::model()->findAllByAttributes(array('session_id' => $model->id));
            $age_group = array();
            $weekDay = array();
            $rate_age_group = array();
            foreach ($sessionRatesWeekdayMappingModel as $sessionRatesWeekdayMapping) {
                $temp = array();
                $temp2 = array();
                array_push($age_group, $sessionRatesWeekdayMapping->age_group);
                $temp['rate_1'] = $sessionRatesWeekdayMapping->rate_1;
                $temp['rate_2'] = $sessionRatesWeekdayMapping->rate_2;
                $temp['rate_3'] = $sessionRatesWeekdayMapping->rate_3;
                $temp['rate_4'] = $sessionRatesWeekdayMapping->rate_4;
                $temp['rate_5'] = $sessionRatesWeekdayMapping->rate_5;
                $temp['rate_6'] = $sessionRatesWeekdayMapping->rate_6;
                $temp['rate_7'] = $sessionRatesWeekdayMapping->rate_7;
                array_push($rate_age_group, $temp);
                $temp2['day_1'] = $sessionRatesWeekdayMapping->day_1;
                $temp2['day_2'] = $sessionRatesWeekdayMapping->day_2;
                $temp2['day_3'] = $sessionRatesWeekdayMapping->day_3;
                $temp2['day_4'] = $sessionRatesWeekdayMapping->day_4;
                $temp2['day_5'] = $sessionRatesWeekdayMapping->day_5;
                $temp2['day_6'] = $sessionRatesWeekdayMapping->day_6;
                $temp2['day_7'] = $sessionRatesWeekdayMapping->day_7;
                array_push($weekDay, $temp2);
            }
        }
        if ($model->is_multiple_rates == 1 && $model->multiple_rates_type == 3) {
            $sessionRatesTotalWeekdayMappingModel = SessionRateTotalWeekdayMapping::model()->findAllByAttributes(array('session_id' => $model->id));
            $age_group = array();
            $totalWeekDay = array();
            $rate_age_group = array();
            foreach ($sessionRatesTotalWeekdayMappingModel as $sessionRatesTotalWeekdayMapping) {
                $temp = array();
                $temp2 = array();
                array_push($age_group, $sessionRatesTotalWeekdayMapping->age_group);
                $temp['rate_1'] = $sessionRatesTotalWeekdayMapping->rate_1;
                $temp['rate_2'] = $sessionRatesTotalWeekdayMapping->rate_2;
                $temp['rate_3'] = $sessionRatesTotalWeekdayMapping->rate_3;
                $temp['rate_4'] = $sessionRatesTotalWeekdayMapping->rate_4;
                $temp['rate_5'] = $sessionRatesTotalWeekdayMapping->rate_5;
                $temp['rate_6'] = $sessionRatesTotalWeekdayMapping->rate_6;
                $temp['rate_7'] = $sessionRatesTotalWeekdayMapping->rate_7;
                $temp['rate_monthly'] = $sessionRatesTotalWeekdayMapping->rate_monthly;
                array_push($rate_age_group, $temp);
                $temp2['total_day_1'] = $sessionRatesTotalWeekdayMapping->total_day_1;
                $temp2['total_day_2'] = $sessionRatesTotalWeekdayMapping->total_day_2;
                $temp2['total_day_3'] = $sessionRatesTotalWeekdayMapping->total_day_3;
                $temp2['total_day_4'] = $sessionRatesTotalWeekdayMapping->total_day_4;
                $temp2['total_day_5'] = $sessionRatesTotalWeekdayMapping->total_day_5;
                $temp2['total_day_6'] = $sessionRatesTotalWeekdayMapping->total_day_6;
                $temp2['total_day_7'] = $sessionRatesTotalWeekdayMapping->total_day_7;
                array_push($totalWeekDay, $temp2);
            }
        }
        if ($model->is_multiple_rates == 1 && $model->multiple_rates_type == 1) {
            $this->render('view', array(
                'model' => $model, 'age_group' => $age_group, 'time' => $time, 'rate_age_group' => $rate_age_group, 'sessionModifiedModel' => $sessionModifiedModel
            ));
        } else if ($model->is_multiple_rates == 1 && $model->multiple_rates_type == 2) {
            $this->render('view', array(
                'model' => $model, 'age_group' => $age_group, 'time' => $weekDay, 'rate_age_group' => $rate_age_group, 'sessionModifiedModel' => $sessionModifiedModel
            ));
        } else if ($model->is_multiple_rates == 1 && $model->multiple_rates_type == 3) {
            $this->render('view', array(
                'model' => $model, 'age_group' => $age_group, 'time' => $totalWeekDay, 'rate_age_group' => $rate_age_group, 'sessionModifiedModel' => $sessionModifiedModel
            ));
        } else {
            $this->render('view', array(
                'model' => $model,
                'sessionModifiedModel' => $sessionModifiedModel
            ));
        }
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate($global = 0) {
        if ($global == 1) {
            $this->layout = 'global';
        }
        $model = new SessionRates;
        $branch = new Branch;
        $sessionRatesMappingModel = new SessionRateMapping;
        $sessionRatesWeekdayMappingModel = new SessionRateWeekdayMapping;
        $sessionRatesTotalWeekdayMappingModel = new SessionRateTotalWeekdayMapping;
        if ($global == 1) {
            $model->branch_id = $branch->createBranchByGlobalId(Yii::app()->session['company_id']);
        }
        $model->branch_id = Yii::app()->session['branch_id'];
        $this->performAjaxValidation($model);
        $this->performAjaxValidation($sessionRatesMappingModel);
        if (isset($_POST['SessionRates']) && isset($_POST['SessionRateMapping']) && isset($_POST['SessionRateWeekdayMapping']) && isset($_POST['SessionRateTotalWeekdayMapping'])) {
            $model->attributes = $_POST['SessionRates'];
            $model->branch_id = Yii::app()->session['branch_id'];
            if (isset($_POST['global']) && $_POST['global'] == 1) {
                $model->is_global = 1;
                $model->global_id = Yii::app()->session['company_id'];
                $model->branch_id = $branch->createBranchByGlobalId(Yii::app()->session['company_id']);
            }
            if ($model->is_minimum == 0) {
                $model->minimum_time = 0;
            }
            if ($model->is_override_max_funded_hours_per_day == 0) {
                $model->session_max_funded_hours_per_day = NULL;
            }
            if ($model->is_multiple_rates == 1) {
                $model->rate_flat = 0.00;
            }

            if ($model->is_multiple_rates == 1 && $model->multiple_rates_type != 1) {
                $model->is_incremental_rates = 0;
            }

            if ($model->save()) {
                if ($model->is_multiple_rates == 1 && $model->multiple_rates_type == 1) {
                    foreach ($_POST['SessionRateMapping']['age_group'] as $index => $value) {
                        $sessionRatesMappingModel = new SessionRateMapping;
                        $sessionRatesMappingModel->session_id = $model->id;
                        $sessionRatesMappingModel->age_group = $value;
                        $sessionRatesMappingModel->time_minimum = $_POST['SessionRateMapping']['time_minimum'];
                        $sessionRatesMappingModel->rate_minimum = $_POST['SessionRateMapping']['rate_minimum'][$index];
                        $sessionRatesMappingModel->time_1 = $_POST['SessionRateMapping']['time_1'];
                        $sessionRatesMappingModel->rate_1 = $_POST['SessionRateMapping']['rate_1'][$index];
                        $sessionRatesMappingModel->time_2 = $_POST['SessionRateMapping']['time_2'];
                        $sessionRatesMappingModel->rate_2 = $_POST['SessionRateMapping']['rate_2'][$index];
                        $sessionRatesMappingModel->time_3 = $_POST['SessionRateMapping']['time_3'];
                        $sessionRatesMappingModel->rate_3 = $_POST['SessionRateMapping']['rate_3'][$index];
                        $sessionRatesMappingModel->time_4 = $_POST['SessionRateMapping']['time_4'];
                        $sessionRatesMappingModel->rate_4 = $_POST['SessionRateMapping']['rate_4'][$index];
                        $sessionRatesMappingModel->time_5 = $_POST['SessionRateMapping']['time_5'];
                        $sessionRatesMappingModel->rate_5 = $_POST['SessionRateMapping']['rate_5'][$index];
                        $sessionRatesMappingModel->time_6 = $_POST['SessionRateMapping']['time_6'];
                        $sessionRatesMappingModel->rate_6 = $_POST['SessionRateMapping']['rate_6'][$index];
                        $sessionRatesMappingModel->time_7 = $_POST['SessionRateMapping']['time_7'];
                        $sessionRatesMappingModel->rate_7 = $_POST['SessionRateMapping']['rate_7'][$index];
                        $sessionRatesMappingModel->time_8 = $_POST['SessionRateMapping']['time_8'];
                        $sessionRatesMappingModel->rate_8 = $_POST['SessionRateMapping']['rate_8'][$index];
                        $sessionRatesMappingModel->time_9 = $_POST['SessionRateMapping']['time_9'];
                        $sessionRatesMappingModel->rate_9 = $_POST['SessionRateMapping']['rate_9'][$index];
                        $sessionRatesMappingModel->save();
                    }
                }
                if ($model->is_multiple_rates == 1 && $model->multiple_rates_type == 2) {
                    foreach ($_POST['SessionRateWeekdayMapping']['age_group'] as $index => $value) {
                        $sessionRatesWeekdayMappingModel = new SessionRateWeekdayMapping;
                        $sessionRatesWeekdayMappingModel->session_id = $model->id;
                        $sessionRatesWeekdayMappingModel->age_group = $value;
                        $sessionRatesWeekdayMappingModel->day_1 = $_POST['SessionRateWeekdayMapping']['day_1'];
                        $sessionRatesWeekdayMappingModel->rate_1 = $_POST['SessionRateWeekdayMapping']['rate_1'][$index];
                        $sessionRatesWeekdayMappingModel->day_2 = $_POST['SessionRateWeekdayMapping']['day_2'];
                        $sessionRatesWeekdayMappingModel->rate_2 = $_POST['SessionRateWeekdayMapping']['rate_2'][$index];
                        $sessionRatesWeekdayMappingModel->day_3 = $_POST['SessionRateWeekdayMapping']['day_3'];
                        $sessionRatesWeekdayMappingModel->rate_3 = $_POST['SessionRateWeekdayMapping']['rate_3'][$index];
                        $sessionRatesWeekdayMappingModel->day_4 = $_POST['SessionRateWeekdayMapping']['day_4'];
                        $sessionRatesWeekdayMappingModel->rate_4 = $_POST['SessionRateWeekdayMapping']['rate_4'][$index];
                        $sessionRatesWeekdayMappingModel->day_5 = $_POST['SessionRateWeekdayMapping']['day_5'];
                        $sessionRatesWeekdayMappingModel->rate_5 = $_POST['SessionRateWeekdayMapping']['rate_5'][$index];
                        $sessionRatesWeekdayMappingModel->day_6 = $_POST['SessionRateWeekdayMapping']['day_6'];
                        $sessionRatesWeekdayMappingModel->rate_6 = $_POST['SessionRateWeekdayMapping']['rate_6'][$index];
                        $sessionRatesWeekdayMappingModel->day_7 = $_POST['SessionRateWeekdayMapping']['day_7'];
                        $sessionRatesWeekdayMappingModel->rate_7 = $_POST['SessionRateWeekdayMapping']['rate_7'][$index];
                        $sessionRatesWeekdayMappingModel->save();
                    }
                }
                if ($model->is_multiple_rates == 1 && $model->multiple_rates_type == 3) {
                    foreach ($_POST['SessionRateTotalWeekdayMapping']['age_group'] as $index => $value) {
                        $sessionRatesTotalWeekdayMappingModel = new SessionRateTotalWeekdayMapping;
                        $sessionRatesTotalWeekdayMappingModel->session_id = $model->id;
                        $sessionRatesTotalWeekdayMappingModel->age_group = $value;
                        $sessionRatesTotalWeekdayMappingModel->total_day_1 = $_POST['SessionRateTotalWeekdayMapping']['total_day_1'];
                        $sessionRatesTotalWeekdayMappingModel->rate_1 = $_POST['SessionRateTotalWeekdayMapping']['rate_1'][$index];
                        $sessionRatesTotalWeekdayMappingModel->total_day_2 = $_POST['SessionRateTotalWeekdayMapping']['total_day_2'];
                        $sessionRatesTotalWeekdayMappingModel->rate_2 = $_POST['SessionRateTotalWeekdayMapping']['rate_2'][$index];
                        $sessionRatesTotalWeekdayMappingModel->total_day_3 = $_POST['SessionRateTotalWeekdayMapping']['total_day_3'];
                        $sessionRatesTotalWeekdayMappingModel->rate_3 = $_POST['SessionRateTotalWeekdayMapping']['rate_3'][$index];
                        $sessionRatesTotalWeekdayMappingModel->total_day_4 = $_POST['SessionRateTotalWeekdayMapping']['total_day_4'];
                        $sessionRatesTotalWeekdayMappingModel->rate_4 = $_POST['SessionRateTotalWeekdayMapping']['rate_4'][$index];
                        $sessionRatesTotalWeekdayMappingModel->total_day_5 = $_POST['SessionRateTotalWeekdayMapping']['total_day_5'];
                        $sessionRatesTotalWeekdayMappingModel->rate_5 = $_POST['SessionRateTotalWeekdayMapping']['rate_5'][$index];
                        $sessionRatesTotalWeekdayMappingModel->total_day_6 = $_POST['SessionRateTotalWeekdayMapping']['total_day_6'];
                        $sessionRatesTotalWeekdayMappingModel->rate_6 = $_POST['SessionRateTotalWeekdayMapping']['rate_6'][$index];
                        $sessionRatesTotalWeekdayMappingModel->total_day_7 = $_POST['SessionRateTotalWeekdayMapping']['total_day_7'];
                        $sessionRatesTotalWeekdayMappingModel->rate_7 = $_POST['SessionRateTotalWeekdayMapping']['rate_7'][$index];
                        $sessionRatesTotalWeekdayMappingModel->rate_monthly = floatval($_POST['SessionRateTotalWeekdayMapping']['rate_monthly'][$index]);
                        $sessionRatesTotalWeekdayMappingModel->save();
                    }
                }
                if (isset($_POST['global']) && $_POST['global'] == 1) {
                    $this->redirect(array(
                        'global'
                    ));
                }
                $this->redirect(array('index'));
            } else {
                Yii::app()->user->setFlash('error', CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
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
    public function actionUpdate($id, $global = 0) {
        if ($global == 1) {
            $this->layout = 'global';
        }
        $model = $this->loadModel($id);
        $sessionModifiedModel = new CActiveDataProvider('SessionRatesHistory', array(
            'criteria' => array(
                'condition' => 'session_id = :session_id',
                'order' => 'id DESC',
                'params' => array(':session_id' => $model->id)
            ),
            'pagination' => array(
                'pageSize' => 20,
            ),
        ));
        if ($model->is_multiple_rates == 1 && $model->multiple_rates_type == 1) {
            $sessionRatesMappingModel = SessionRateMapping::model()->findAllByAttributes(array('session_id' => $model->id));
            $sessionRatesWeekdayMappingModel = SessionRateWeekdayMapping::model()->findAllByAttributes(array('session_id' => $model->id));
            $sessionRatesTotalWeekdayMappingModel = SessionRateTotalWeekdayMapping::model()->findAllByAttributes(array('session_id' => $model->id));
            $age_group = array();
            $time = array();
            $rate_age_group = array();
            foreach ($sessionRatesMappingModel as $sessionRatesMapping) {
                $temp = array();
                $temp2 = array();
                array_push($age_group, $sessionRatesMapping->age_group);
                $temp['rate_minimum'] = $sessionRatesMapping->rate_minimum;
                $temp['rate_1'] = $sessionRatesMapping->rate_1;
                $temp['rate_2'] = $sessionRatesMapping->rate_2;
                $temp['rate_3'] = $sessionRatesMapping->rate_3;
                $temp['rate_4'] = $sessionRatesMapping->rate_4;
                $temp['rate_5'] = $sessionRatesMapping->rate_5;
                $temp['rate_6'] = $sessionRatesMapping->rate_6;
                $temp['rate_7'] = $sessionRatesMapping->rate_7;
                $temp['rate_8'] = $sessionRatesMapping->rate_8;
                $temp['rate_9'] = $sessionRatesMapping->rate_9;
                array_push($rate_age_group, $temp);
                $temp2['time_minimum'] = $sessionRatesMapping->time_minimum;
                $temp2['time_1'] = $sessionRatesMapping->time_1;
                $temp2['time_2'] = $sessionRatesMapping->time_2;
                $temp2['time_3'] = $sessionRatesMapping->time_3;
                $temp2['time_4'] = $sessionRatesMapping->time_4;
                $temp2['time_5'] = $sessionRatesMapping->time_5;
                $temp2['time_6'] = $sessionRatesMapping->time_6;
                $temp2['time_7'] = $sessionRatesMapping->time_7;
                $temp2['time_8'] = $sessionRatesMapping->time_8;
                $temp2['time_9'] = $sessionRatesMapping->time_9;
                array_push($time, $temp2);
            }
        }
        if ($model->is_multiple_rates == 1 && $model->multiple_rates_type == 2) {
            $sessionRatesWeekdayMappingModel = SessionRateWeekdayMapping::model()->findAllByAttributes(array('session_id' => $model->id));
            $age_group = array();
            $weekDay = array();
            $rate_age_group = array();
            foreach ($sessionRatesWeekdayMappingModel as $sessionRatesWeekdayMapping) {
                $temp = array();
                $temp2 = array();
                array_push($age_group, $sessionRatesWeekdayMapping->age_group);
                $temp['rate_1'] = $sessionRatesWeekdayMapping->rate_1;
                $temp['rate_2'] = $sessionRatesWeekdayMapping->rate_2;
                $temp['rate_3'] = $sessionRatesWeekdayMapping->rate_3;
                $temp['rate_4'] = $sessionRatesWeekdayMapping->rate_4;
                $temp['rate_5'] = $sessionRatesWeekdayMapping->rate_5;
                $temp['rate_6'] = $sessionRatesWeekdayMapping->rate_6;
                $temp['rate_7'] = $sessionRatesWeekdayMapping->rate_7;
                array_push($rate_age_group, $temp);
                $temp2['day_1'] = $sessionRatesWeekdayMapping->day_1;
                $temp2['day_2'] = $sessionRatesWeekdayMapping->day_2;
                $temp2['day_3'] = $sessionRatesWeekdayMapping->day_3;
                $temp2['day_4'] = $sessionRatesWeekdayMapping->day_4;
                $temp2['day_5'] = $sessionRatesWeekdayMapping->day_5;
                $temp2['day_6'] = $sessionRatesWeekdayMapping->day_6;
                $temp2['day_7'] = $sessionRatesWeekdayMapping->day_7;
                array_push($weekDay, $temp2);
            }
        }
        if ($model->is_multiple_rates == 1 && $model->multiple_rates_type == 3) {
            $sessionRatesTotalWeekdayMappingModel = SessionRateTotalWeekdayMapping::model()->findAllByAttributes(array('session_id' => $model->id));
            $age_group = array();
            $totalWeekDay = array();
            $rate_age_group = array();
            foreach ($sessionRatesTotalWeekdayMappingModel as $sessionRatesTotalWeekdayMapping) {
                $temp = array();
                $temp2 = array();
                array_push($age_group, $sessionRatesTotalWeekdayMapping->age_group);
                $temp['rate_1'] = $sessionRatesTotalWeekdayMapping->rate_1;
                $temp['rate_2'] = $sessionRatesTotalWeekdayMapping->rate_2;
                $temp['rate_3'] = $sessionRatesTotalWeekdayMapping->rate_3;
                $temp['rate_4'] = $sessionRatesTotalWeekdayMapping->rate_4;
                $temp['rate_5'] = $sessionRatesTotalWeekdayMapping->rate_5;
                $temp['rate_6'] = $sessionRatesTotalWeekdayMapping->rate_6;
                $temp['rate_7'] = $sessionRatesTotalWeekdayMapping->rate_7;
                $temp['rate_monthly'] = $sessionRatesTotalWeekdayMapping->rate_monthly;
                array_push($rate_age_group, $temp);
                $temp2['total_day_1'] = $sessionRatesTotalWeekdayMapping->total_day_1;
                $temp2['total_day_2'] = $sessionRatesTotalWeekdayMapping->total_day_2;
                $temp2['total_day_3'] = $sessionRatesTotalWeekdayMapping->total_day_3;
                $temp2['total_day_4'] = $sessionRatesTotalWeekdayMapping->total_day_4;
                $temp2['total_day_5'] = $sessionRatesTotalWeekdayMapping->total_day_5;
                $temp2['total_day_6'] = $sessionRatesTotalWeekdayMapping->total_day_6;
                $temp2['total_day_7'] = $sessionRatesTotalWeekdayMapping->total_day_7;
                array_push($totalWeekDay, $temp2);
            }
        }
        $this->performAjaxValidation($model);
        $this->performAjaxValidation($sessionRatesMappingModel);
        if (isset($_POST['SessionRates']) && isset($_POST['SessionRateMapping']) && isset($_POST['SessionRateWeekdayMapping']) && isset($_POST['SessionRateTotalWeekdayMapping'])) {
            $model->attributes = $_POST['SessionRates'];
            if ($model->is_minimum == 0) {
                $model->minimum_time = 0;
            }
            if ($model->is_override_max_funded_hours_per_day == 0) {
                $model->session_max_funded_hours_per_day = NULL;
            }
            if ($model->is_multiple_rates == 1) {
                $model->rate_flat = 0.00;
            }
            if ($model->is_multiple_rates == 0) {
                $model->multiple_rates_type = NULL;
            }
            if ($model->is_multiple_rates == 1 && $model->multiple_rates_type != 1) {
                $model->is_incremental_rates = 0;
            }

            if ($model->save()) {
                if ($model->is_multiple_rates == 1 && $model->multiple_rates_type == 1) {
                    if (!empty($sessionRatesMappingModel)) {
                        foreach ($sessionRatesMappingModel as $key => $sessionRatesMappingRow) {
                            $sessionRatesMappingRow->session_id = $model->id;
                            $sessionRatesMappingRow->age_group = $_POST['SessionRateMapping']['age_group'][$key];
                            $sessionRatesMappingRow->time_minimum = $_POST['SessionRateMapping']['time_minimum'];
                            $sessionRatesMappingRow->rate_minimum = $_POST['SessionRateMapping']['rate_minimum'][$key];
                            $sessionRatesMappingRow->time_1 = $_POST['SessionRateMapping']['time_1'];
                            $sessionRatesMappingRow->rate_1 = $_POST['SessionRateMapping']['rate_1'][$key];
                            $sessionRatesMappingRow->time_2 = $_POST['SessionRateMapping']['time_2'];
                            $sessionRatesMappingRow->rate_2 = $_POST['SessionRateMapping']['rate_2'][$key];
                            $sessionRatesMappingRow->time_3 = $_POST['SessionRateMapping']['time_3'];
                            $sessionRatesMappingRow->rate_3 = $_POST['SessionRateMapping']['rate_3'][$key];
                            $sessionRatesMappingRow->time_4 = $_POST['SessionRateMapping']['time_4'];
                            $sessionRatesMappingRow->rate_4 = $_POST['SessionRateMapping']['rate_4'][$key];
                            $sessionRatesMappingRow->time_5 = $_POST['SessionRateMapping']['time_5'];
                            $sessionRatesMappingRow->rate_5 = $_POST['SessionRateMapping']['rate_5'][$key];
                            $sessionRatesMappingRow->time_6 = $_POST['SessionRateMapping']['time_6'];
                            $sessionRatesMappingRow->rate_6 = $_POST['SessionRateMapping']['rate_6'][$key];
                            $sessionRatesMappingRow->time_7 = $_POST['SessionRateMapping']['time_7'];
                            $sessionRatesMappingRow->rate_7 = $_POST['SessionRateMapping']['rate_7'][$key];
                            $sessionRatesMappingRow->time_8 = $_POST['SessionRateMapping']['time_8'];
                            $sessionRatesMappingRow->rate_8 = $_POST['SessionRateMapping']['rate_8'][$key];
                            $sessionRatesMappingRow->time_9 = $_POST['SessionRateMapping']['time_9'];
                            $sessionRatesMappingRow->rate_9 = $_POST['SessionRateMapping']['rate_9'][$key];
                            $sessionRatesMappingRow->save();
                        }
                    } else {
                        foreach ($_POST['SessionRateMapping']['age_group'] as $index => $value) {
                            $sessionRatesMappingModel = new SessionRateMapping;
                            $sessionRatesMappingModel->session_id = $model->id;
                            $sessionRatesMappingModel->age_group = $value;
                            $sessionRatesMappingModel->time_minimum = $_POST['SessionRateMapping']['time_minimum'];
                            $sessionRatesMappingModel->rate_minimum = $_POST['SessionRateMapping']['rate_minimum'][$index];
                            $sessionRatesMappingModel->time_1 = $_POST['SessionRateMapping']['time_1'];
                            $sessionRatesMappingModel->rate_1 = $_POST['SessionRateMapping']['rate_1'][$index];
                            $sessionRatesMappingModel->time_2 = $_POST['SessionRateMapping']['time_2'];
                            $sessionRatesMappingModel->rate_2 = $_POST['SessionRateMapping']['rate_2'][$index];
                            $sessionRatesMappingModel->time_3 = $_POST['SessionRateMapping']['time_3'];
                            $sessionRatesMappingModel->rate_3 = $_POST['SessionRateMapping']['rate_3'][$index];
                            $sessionRatesMappingModel->time_4 = $_POST['SessionRateMapping']['time_4'];
                            $sessionRatesMappingModel->rate_4 = $_POST['SessionRateMapping']['rate_4'][$index];
                            $sessionRatesMappingModel->time_5 = $_POST['SessionRateMapping']['time_5'];
                            $sessionRatesMappingModel->rate_5 = $_POST['SessionRateMapping']['rate_5'][$index];
                            $sessionRatesMappingModel->time_6 = $_POST['SessionRateMapping']['time_6'];
                            $sessionRatesMappingModel->rate_6 = $_POST['SessionRateMapping']['rate_6'][$index];
                            $sessionRatesMappingModel->time_7 = $_POST['SessionRateMapping']['time_7'];
                            $sessionRatesMappingModel->rate_7 = $_POST['SessionRateMapping']['rate_7'][$index];
                            $sessionRatesMappingModel->time_8 = $_POST['SessionRateMapping']['time_8'];
                            $sessionRatesMappingModel->rate_8 = $_POST['SessionRateMapping']['rate_8'][$index];
                            $sessionRatesMappingModel->time_9 = $_POST['SessionRateMapping']['time_9'];
                            $sessionRatesMappingModel->rate_9 = $_POST['SessionRateMapping']['rate_9'][$index];
                            $sessionRatesMappingModel->save();
                        }
                    }
                }
                if ($model->is_multiple_rates == 1 && $model->multiple_rates_type == 2) {
                    if (!empty($sessionRatesWeekdayMappingModel)) {
                        foreach ($sessionRatesWeekdayMappingModel as $key => $sessionRatesWeekdayMapping) {
                            $sessionRatesWeekdayMapping->session_id = $model->id;
                            $sessionRatesWeekdayMapping->age_group = $_POST['SessionRateWeekdayMapping']['age_group'][$key];
                            $sessionRatesWeekdayMapping->day_1 = $_POST['SessionRateWeekdayMapping']['day_1'];
                            $sessionRatesWeekdayMapping->rate_1 = $_POST['SessionRateWeekdayMapping']['rate_1'][$key];
                            $sessionRatesWeekdayMapping->day_2 = $_POST['SessionRateWeekdayMapping']['day_2'];
                            $sessionRatesWeekdayMapping->rate_2 = $_POST['SessionRateWeekdayMapping']['rate_2'][$key];
                            $sessionRatesWeekdayMapping->day_3 = $_POST['SessionRateWeekdayMapping']['day_3'];
                            $sessionRatesWeekdayMapping->rate_3 = $_POST['SessionRateWeekdayMapping']['rate_3'][$key];
                            $sessionRatesWeekdayMapping->day_4 = $_POST['SessionRateWeekdayMapping']['day_4'];
                            $sessionRatesWeekdayMapping->rate_4 = $_POST['SessionRateWeekdayMapping']['rate_4'][$key];
                            $sessionRatesWeekdayMapping->day_5 = $_POST['SessionRateWeekdayMapping']['day_5'];
                            $sessionRatesWeekdayMapping->rate_5 = $_POST['SessionRateWeekdayMapping']['rate_5'][$key];
                            $sessionRatesWeekdayMapping->day_6 = $_POST['SessionRateWeekdayMapping']['day_6'];
                            $sessionRatesWeekdayMapping->rate_6 = $_POST['SessionRateWeekdayMapping']['rate_6'][$key];
                            $sessionRatesWeekdayMapping->day_7 = $_POST['SessionRateWeekdayMapping']['day_7'];
                            $sessionRatesWeekdayMapping->rate_7 = $_POST['SessionRateWeekdayMapping']['rate_7'][$key];
                            $sessionRatesWeekdayMapping->save();
                        }
                    } else {
                        foreach ($_POST['SessionRateWeekdayMapping']['age_group'] as $index => $value) {
                            $sessionRatesWeekdayMappingModel = new SessionRateWeekdayMapping;
                            $sessionRatesWeekdayMappingModel->session_id = $model->id;
                            $sessionRatesWeekdayMappingModel->age_group = $value;
                            $sessionRatesWeekdayMappingModel->day_1 = $_POST['SessionRateWeekdayMapping']['day_1'];
                            $sessionRatesWeekdayMappingModel->rate_1 = $_POST['SessionRateWeekdayMapping']['rate_1'][$index];
                            $sessionRatesWeekdayMappingModel->day_2 = $_POST['SessionRateWeekdayMapping']['day_2'];
                            $sessionRatesWeekdayMappingModel->rate_2 = $_POST['SessionRateWeekdayMapping']['rate_2'][$index];
                            $sessionRatesWeekdayMappingModel->day_3 = $_POST['SessionRateWeekdayMapping']['day_3'];
                            $sessionRatesWeekdayMappingModel->rate_3 = $_POST['SessionRateWeekdayMapping']['rate_3'][$index];
                            $sessionRatesWeekdayMappingModel->day_4 = $_POST['SessionRateWeekdayMapping']['day_4'];
                            $sessionRatesWeekdayMappingModel->rate_4 = $_POST['SessionRateWeekdayMapping']['rate_4'][$index];
                            $sessionRatesWeekdayMappingModel->day_5 = $_POST['SessionRateWeekdayMapping']['day_5'];
                            $sessionRatesWeekdayMappingModel->rate_5 = $_POST['SessionRateWeekdayMapping']['rate_5'][$index];
                            $sessionRatesWeekdayMappingModel->day_6 = $_POST['SessionRateWeekdayMapping']['day_6'];
                            $sessionRatesWeekdayMappingModel->rate_6 = $_POST['SessionRateWeekdayMapping']['rate_6'][$index];
                            $sessionRatesWeekdayMappingModel->day_7 = $_POST['SessionRateWeekdayMapping']['day_7'];
                            $sessionRatesWeekdayMappingModel->rate_7 = $_POST['SessionRateWeekdayMapping']['rate_7'][$index];
                            $sessionRatesWeekdayMappingModel->save();
                        }
                    }
                }

                if ($model->is_multiple_rates == 1 && $model->multiple_rates_type == 3) {
                    if (!empty($sessionRatesTotalWeekdayMappingModel)) {
                        foreach ($sessionRatesTotalWeekdayMappingModel as $key => $sessionRatesTotalWeekdayMapping) {
                            $sessionRatesTotalWeekdayMapping->session_id = $model->id;
                            $sessionRatesTotalWeekdayMapping->age_group = $_POST['SessionRateTotalWeekdayMapping']['age_group'][$key];
                            $sessionRatesTotalWeekdayMapping->total_day_1 = $_POST['SessionRateTotalWeekdayMapping']['total_day_1'];
                            $sessionRatesTotalWeekdayMapping->rate_1 = $_POST['SessionRateTotalWeekdayMapping']['rate_1'][$key];
                            $sessionRatesTotalWeekdayMapping->total_day_2 = $_POST['SessionRateTotalWeekdayMapping']['total_day_2'];
                            $sessionRatesTotalWeekdayMapping->rate_2 = $_POST['SessionRateTotalWeekdayMapping']['rate_2'][$key];
                            $sessionRatesTotalWeekdayMapping->total_day_3 = $_POST['SessionRateTotalWeekdayMapping']['total_day_3'];
                            $sessionRatesTotalWeekdayMapping->rate_3 = $_POST['SessionRateTotalWeekdayMapping']['rate_3'][$key];
                            $sessionRatesTotalWeekdayMapping->total_day_4 = $_POST['SessionRateTotalWeekdayMapping']['total_day_4'];
                            $sessionRatesTotalWeekdayMapping->rate_4 = $_POST['SessionRateTotalWeekdayMapping']['rate_4'][$key];
                            $sessionRatesTotalWeekdayMapping->total_day_5 = $_POST['SessionRateTotalWeekdayMapping']['total_day_5'];
                            $sessionRatesTotalWeekdayMapping->rate_5 = $_POST['SessionRateTotalWeekdayMapping']['rate_5'][$key];
                            $sessionRatesTotalWeekdayMapping->total_day_6 = $_POST['SessionRateTotalWeekdayMapping']['total_day_6'];
                            $sessionRatesTotalWeekdayMapping->rate_6 = $_POST['SessionRateTotalWeekdayMapping']['rate_6'][$key];
                            $sessionRatesTotalWeekdayMapping->total_day_7 = $_POST['SessionRateTotalWeekdayMapping']['total_day_7'];
                            $sessionRatesTotalWeekdayMapping->rate_7 = $_POST['SessionRateTotalWeekdayMapping']['rate_7'][$key];
                            $sessionRatesTotalWeekdayMapping->rate_monthly = (floatval($_POST['SessionRateTotalWeekdayMapping']['rate_monthly'][$key]));
                            $sessionRatesTotalWeekdayMapping->save();
                        }
                    } else {
                        foreach ($_POST['SessionRateTotalWeekdayMapping']['age_group'] as $index => $value) {
                            $sessionRatesTotalWeekdayMappingModel = new SessionRateTotalWeekdayMapping;
                            $sessionRatesTotalWeekdayMappingModel->session_id = $model->id;
                            $sessionRatesTotalWeekdayMappingModel->age_group = $value;
                            $sessionRatesTotalWeekdayMappingModel->total_day_1 = $_POST['SessionRateTotalWeekdayMapping']['total_day_1'];
                            $sessionRatesTotalWeekdayMappingModel->rate_1 = $_POST['SessionRateTotalWeekdayMapping']['rate_1'][$index];
                            $sessionRatesTotalWeekdayMappingModel->total_day_2 = $_POST['SessionRateTotalWeekdayMapping']['total_day_2'];
                            $sessionRatesTotalWeekdayMappingModel->rate_2 = $_POST['SessionRateTotalWeekdayMapping']['rate_2'][$index];
                            $sessionRatesTotalWeekdayMappingModel->total_day_3 = $_POST['SessionRateTotalWeekdayMapping']['total_day_3'];
                            $sessionRatesTotalWeekdayMappingModel->rate_3 = $_POST['SessionRateTotalWeekdayMapping']['rate_3'][$index];
                            $sessionRatesTotalWeekdayMappingModel->total_day_4 = $_POST['SessionRateTotalWeekdayMapping']['total_day_4'];
                            $sessionRatesTotalWeekdayMappingModel->rate_4 = $_POST['SessionRateTotalWeekdayMapping']['rate_4'][$index];
                            $sessionRatesTotalWeekdayMappingModel->total_day_5 = $_POST['SessionRateTotalWeekdayMapping']['total_day_5'];
                            $sessionRatesTotalWeekdayMappingModel->rate_5 = $_POST['SessionRateTotalWeekdayMapping']['rate_5'][$index];
                            $sessionRatesTotalWeekdayMappingModel->total_day_6 = $_POST['SessionRateTotalWeekdayMapping']['total_day_6'];
                            $sessionRatesTotalWeekdayMappingModel->rate_6 = $_POST['SessionRateTotalWeekdayMapping']['rate_6'][$index];
                            $sessionRatesTotalWeekdayMappingModel->total_day_7 = $_POST['SessionRateTotalWeekdayMapping']['total_day_7'];
                            $sessionRatesTotalWeekdayMappingModel->rate_7 = $_POST['SessionRateTotalWeekdayMapping']['rate_7'][$index];
                            $sessionRatesTotalWeekdayMappingModel->rate_monthly = floatval($_POST['SessionRateTotalWeekdayMapping']['rate_monthly'][$index]);
                            $sessionRatesTotalWeekdayMappingModel->save();
                        }
                    }
                }

                if ($model->is_multiple_rates == 0 && $model->multiple_rates_type == 1) {
                    if (!empty($sessionRatesMappingModel)) {
                        foreach ($sessionRatesMappingModel as $sessionRatesMappingRow) {
                            $sessionRatesMappingRow->delete();
                        }
                    }
                }
                if ($model->is_multiple_rates == 0 && $model->multiple_rates_type == 3) {
                    if (!empty($sessionRatesTotalWeekdayMappingModel)) {
                        foreach ($sessionRatesTotalWeekdayMappingModel as $sessionRatesTotalWeekdayMapping) {
                            $sessionRatesTotalWeekdayMapping->delete();
                        }
                    }
                }
                if ($model->is_multiple_rates == 0 && $model->multiple_rates_type == 2) {
                    if (!empty($sessionRatesWeekdayMappingModel)) {
                        foreach ($sessionRatesWeekdayMappingModel as $sessionRatesWeekdayMapping) {
                            $sessionRatesWeekdayMapping->delete();
                        }
                    }
                }
                if (isset($_POST['global']) && $_POST['global'] == 1) {
                    $this->redirect(array(
                        'global'
                    ));
                }
                $this->redirect(array('index'));
            }
        }
        if ($model->is_multiple_rates == 1 && $model->multiple_rates_type == 1) {
            $this->render('view', array(
                'model' => $model, 'age_group' => $age_group, 'time' => $time, 'rate_age_group' => $rate_age_group, 'sessionModifiedModel' => $sessionModifiedModel
            ));
        } else if ($model->is_multiple_rates == 1 && $model->multiple_rates_type == 2) {
            $this->render('view', array(
                'model' => $model, 'age_group' => $age_group, 'time' => $weekDay, 'rate_age_group' => $rate_age_group, 'sessionModifiedModel' => $sessionModifiedModel
            ));
        } else if ($model->is_multiple_rates == 1 && $model->multiple_rates_type == 3) {
            $this->render('view', array(
                'model' => $model, 'age_group' => $age_group, 'time' => $totalWeekDay, 'rate_age_group' => $rate_age_group, 'sessionModifiedModel' => $sessionModifiedModel
            ));
        } else {
            $this->render('view', array(
                'model' => $model,
                'sessionModifiedModel' => $sessionModifiedModel
            ));
        }
    }

    /**
     * Deletes a particular model.
     * If deletion is successful, the browser will be redirected to the 'admin' page.
     * @param integer $id the ID of the model to be deleted
     */
    public function actionDelete($id) {
        $model = $this->loadModel($id);
        $model->is_deleted = 1;
        $model->save();
        if (!isset($_GET['ajax']))
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
    }

    /**
     * Lists all models.
     */
    public function actionIndex() {
        $this->pageTitle = 'Session Rates | eyMan';
        if (isset(Yii::app()->session['global_id'])) {
            unset(Yii::app()->session['global_id']);
        }
        $model = new SessionRates('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['SessionRates']))
            $model->attributes = $_GET['SessionRates'];
        $this->render('index', array(
            'model' => $model,
        ));
    }

    /**
     * Lists all models.
     */
    public function actionGlobal() {
        $this->pageTitle = 'Session Rates | eyMan';
        $this->layout = 'global';
        if (isset(Yii::app()->session['company_id'])) {
            Yii::app()->session['global_id'] = Yii::app()->session['company_id'];
        }
        $model = new SessionRates('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['SessionRates']))
            $model->attributes = $_GET['SessionRates'];
        $this->render('index', array(
            'model' => $model,
        ));
    }

    /**
     * Manages all models.
     */
    public function actionAdmin() {
        $model = new SessionRates('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['SessionRates']))
            $model->attributes = $_GET['SessionRates'];

        $this->render('admin', array(
            'model' => $model,
        ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return SessionRates the loaded model
     * @throws CHttpException
     */
    public function loadModel($id) {
        $model = SessionRates::model()->findByPk($id);
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }

    /**
     * Performs the AJAX validation.
     * @param SessionRates $model the model to be validated
     */
    protected function performAjaxValidation($model) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'session-rates-form') {
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
        if (!isset($_GET['ajax'])) {
            if ($global == 1)
                $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('global'));
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
        }
    }

    public function actionModifyRates($session_id) {
        $model = $this->loadModel($session_id);
        $model->setScenario('session_modify');
        $modifiedSessionModel = new SessionRates();
        $modifiedSessionModel->setScenario('session_modify');
        $modifiedSessionRatesMappingModel = new SessionRateMapping;
        $modifiedSessionRatesWeekdayMappingModel = new SessionRateWeekdayMapping;
        $modifiedSessionRatesTotalWeekdayMappingModel = new SessionRateTotalWeekdayMapping;
        if ($model->is_multiple_rates == 1 && $model->multiple_rates_type == 1) {
            $sessionRatesMappingModel = SessionRateMapping::model()->findAllByAttributes(array('session_id' => $model->id));
            $sessionRatesWeekdayMappingModel = SessionRateWeekdayMapping::model()->findAllByAttributes(array('session_id' => $model->id));
            $sessionRatesTotalWeekdayMappingModel = SessionRateTotalWeekdayMapping::model()->findAllByAttributes(array('session_id' => $model->id));
            $age_group = array();
            $time = array();
            $rate_age_group = array();
            foreach ($sessionRatesMappingModel as $sessionRatesMapping) {
                $temp = array();
                $temp2 = array();
                array_push($age_group, $sessionRatesMapping->age_group);
                $temp['rate_minimum'] = $sessionRatesMapping->rate_minimum;
                $temp['rate_1'] = $sessionRatesMapping->rate_1;
                $temp['rate_2'] = $sessionRatesMapping->rate_2;
                $temp['rate_3'] = $sessionRatesMapping->rate_3;
                $temp['rate_4'] = $sessionRatesMapping->rate_4;
                $temp['rate_5'] = $sessionRatesMapping->rate_5;
                $temp['rate_6'] = $sessionRatesMapping->rate_6;
                $temp['rate_7'] = $sessionRatesMapping->rate_7;
                $temp['rate_8'] = $sessionRatesMapping->rate_8;
                $temp['rate_9'] = $sessionRatesMapping->rate_9;
                array_push($rate_age_group, $temp);

                $temp2['time_minimum'] = $sessionRatesMapping->time_minimum;
                $temp2['time_1'] = $sessionRatesMapping->time_1;
                $temp2['time_2'] = $sessionRatesMapping->time_2;
                $temp2['time_3'] = $sessionRatesMapping->time_3;
                $temp2['time_4'] = $sessionRatesMapping->time_4;
                $temp2['time_5'] = $sessionRatesMapping->time_5;
                $temp2['time_6'] = $sessionRatesMapping->time_6;
                $temp2['time_7'] = $sessionRatesMapping->time_7;
                $temp2['time_8'] = $sessionRatesMapping->time_8;
                $temp2['time_9'] = $sessionRatesMapping->time_9;
                array_push($time, $temp2);
            }
        }
        if ($model->is_multiple_rates == 1 && $model->multiple_rates_type == 2) {
            $sessionRatesWeekdayMappingModel = SessionRateWeekdayMapping::model()->findAllByAttributes(array('session_id' => $model->id));
            $age_group = array();
            $weekDay = array();
            $rate_age_group = array();
            foreach ($sessionRatesWeekdayMappingModel as $sessionRatesWeekdayMapping) {
                $temp = array();
                $temp2 = array();
                array_push($age_group, $sessionRatesWeekdayMapping->age_group);
                $temp['rate_1'] = $sessionRatesWeekdayMapping->rate_1;
                $temp['rate_2'] = $sessionRatesWeekdayMapping->rate_2;
                $temp['rate_3'] = $sessionRatesWeekdayMapping->rate_3;
                $temp['rate_4'] = $sessionRatesWeekdayMapping->rate_4;
                $temp['rate_5'] = $sessionRatesWeekdayMapping->rate_5;
                $temp['rate_6'] = $sessionRatesWeekdayMapping->rate_6;
                $temp['rate_7'] = $sessionRatesWeekdayMapping->rate_7;
                array_push($rate_age_group, $temp);
                $temp2['day_1'] = $sessionRatesWeekdayMapping->day_1;
                $temp2['day_2'] = $sessionRatesWeekdayMapping->day_2;
                $temp2['day_3'] = $sessionRatesWeekdayMapping->day_3;
                $temp2['day_4'] = $sessionRatesWeekdayMapping->day_4;
                $temp2['day_5'] = $sessionRatesWeekdayMapping->day_5;
                $temp2['day_6'] = $sessionRatesWeekdayMapping->day_6;
                $temp2['day_7'] = $sessionRatesWeekdayMapping->day_7;
                array_push($weekDay, $temp2);
            }
        }
        if ($model->is_multiple_rates == 1 && $model->multiple_rates_type == 3) {
            $sessionRatesTotalWeekdayMappingModel = SessionRateTotalWeekdayMapping::model()->findAllByAttributes(array('session_id' => $model->id));
            $age_group = array();
            $totalWeekDay = array();
            $rate_age_group = array();
            foreach ($sessionRatesTotalWeekdayMappingModel as $sessionRatesTotalWeekdayMapping) {
                $temp = array();
                $temp2 = array();
                array_push($age_group, $sessionRatesTotalWeekdayMapping->age_group);
                $temp['rate_1'] = $sessionRatesTotalWeekdayMapping->rate_1;
                $temp['rate_2'] = $sessionRatesTotalWeekdayMapping->rate_2;
                $temp['rate_3'] = $sessionRatesTotalWeekdayMapping->rate_3;
                $temp['rate_4'] = $sessionRatesTotalWeekdayMapping->rate_4;
                $temp['rate_5'] = $sessionRatesTotalWeekdayMapping->rate_5;
                $temp['rate_6'] = $sessionRatesTotalWeekdayMapping->rate_6;
                $temp['rate_7'] = $sessionRatesTotalWeekdayMapping->rate_7;
                $temp['rate_monthly'] = $sessionRatesTotalWeekdayMapping->rate_monthly;
                array_push($rate_age_group, $temp);
                $temp2['total_day_1'] = $sessionRatesTotalWeekdayMapping->total_day_1;
                $temp2['total_day_2'] = $sessionRatesTotalWeekdayMapping->total_day_2;
                $temp2['total_day_3'] = $sessionRatesTotalWeekdayMapping->total_day_3;
                $temp2['total_day_4'] = $sessionRatesTotalWeekdayMapping->total_day_4;
                $temp2['total_day_5'] = $sessionRatesTotalWeekdayMapping->total_day_5;
                $temp2['total_day_6'] = $sessionRatesTotalWeekdayMapping->total_day_6;
                $temp2['total_day_7'] = $sessionRatesTotalWeekdayMapping->total_day_7;
                array_push($totalWeekDay, $temp2);
            }
        }
        $this->performAjaxValidation($modifiedSessionModel);
        $this->performAjaxValidation($sessionRatesMappingModel);
        if (isset($_POST['SessionRates']) && isset($_POST['SessionRateMapping']) && isset($_POST['SessionRateWeekdayMapping']) && isset($_POST['SessionRateTotalWeekdayMapping'])) {
            $modifiedSessionModel->attributes = $_POST["SessionRates"];
            $modifiedSessionModel->attributes = $model->attributes;
            $modifiedSessionModel->rate_flat = $_POST["SessionRates"]["rate_flat"];
            $modifiedSessionModel->branch_id = Yii::app()->session['branch_id'];
            $modifiedSessionModel->is_modified = 1;
            if ($modifiedSessionModel->is_minimum == 0) {
                $modifiedSessionModel->minimum_time = 0;
            }
            if ($modifiedSessionModel->is_multiple_rates == 1) {
                $modifiedSessionModel->rate_flat = 0.00;
            }
            $branchModel = Branch::model()->findByPk(Yii::app()->session['branch_id']);
            if (!isset($modifiedSessionModel->effective_date) || empty($modifiedSessionModel->effective_date)) {
                Yii::app()->user->setFlash("error", "Effectives Date of sesssion can not be blank.");
                $this->refresh();
                Yii::app()->end();
            }
            $lastEffectiveTransactionDate = SessionRatesHistory::model()->findByAttributes(array('session_id' => $model->id), array('order' => "id asc"));
            if (strtotime($lastEffectiveTransactionDate->effective_date) >= strtotime(date("Y-m-d", strtotime($modifiedSessionModel->effective_date)))) {
                Yii::app()->user->setFlash('error', 'Effective date of session can not be smaller than previous Sessions');
                $this->refresh();
                Yii::app()->end();
            }
            if (strtotime($modifiedSessionModel->start_time) < strtotime($branchModel->operation_start_time)) {
                Yii::app()->user->setFlash('error', 'Session Start Time can not be smaller than Branch Operation Start Time');
                $this->refresh();
                Yii::app()->end();
            }
            if (strtotime($modifiedSessionModel->finish_time) > strtotime($branchModel->operation_finish_time)) {
                Yii::app()->user->setFlash('error', 'Session Finish Time can not be greater than Branch Operation Finish Time');
                $this->refresh();
                Yii::app()->end();
            }
            if ($modifiedSessionModel->save()) {
                $mappingHistoryModel = new SessionRatesHistory;
                $mappingHistoryModel->session_id = $session_id;
                $mappingHistoryModel->modified_session_id = $modifiedSessionModel->id;
                $mappingHistoryModel->effective_date = date("Y-m-d", strtotime($_POST['SessionRates']['effective_date']));
                $mappingHistoryModel->save();

                if ($modifiedSessionModel->is_multiple_rates == 1 && $modifiedSessionModel->multiple_rates_type == 1) {
                    foreach ($_POST['SessionRateMapping']['age_group'] as $index => $value) {
                        $modifiedSessionRatesMappingModel = new SessionRateMapping;
                        $modifiedSessionRatesMappingModel->session_id = $modifiedSessionModel->id;
                        $modifiedSessionRatesMappingModel->age_group = $value;
                        $modifiedSessionRatesMappingModel->time_minimum = $_POST['SessionRateMapping']['time_minimum'];
                        $modifiedSessionRatesMappingModel->rate_minimum = $_POST['SessionRateMapping']['rate_minimum'][$index];
                        $modifiedSessionRatesMappingModel->time_1 = $_POST['SessionRateMapping']['time_1'];
                        $modifiedSessionRatesMappingModel->rate_1 = $_POST['SessionRateMapping']['rate_1'][$index];
                        $modifiedSessionRatesMappingModel->time_2 = $_POST['SessionRateMapping']['time_2'];
                        $modifiedSessionRatesMappingModel->rate_2 = $_POST['SessionRateMapping']['rate_2'][$index];
                        $modifiedSessionRatesMappingModel->time_3 = $_POST['SessionRateMapping']['time_3'];
                        $modifiedSessionRatesMappingModel->rate_3 = $_POST['SessionRateMapping']['rate_3'][$index];
                        $modifiedSessionRatesMappingModel->time_4 = $_POST['SessionRateMapping']['time_4'];
                        $modifiedSessionRatesMappingModel->rate_4 = $_POST['SessionRateMapping']['rate_4'][$index];
                        $modifiedSessionRatesMappingModel->time_5 = $_POST['SessionRateMapping']['time_5'];
                        $modifiedSessionRatesMappingModel->rate_5 = $_POST['SessionRateMapping']['rate_5'][$index];
                        $modifiedSessionRatesMappingModel->time_6 = $_POST['SessionRateMapping']['time_6'];
                        $modifiedSessionRatesMappingModel->rate_6 = $_POST['SessionRateMapping']['rate_6'][$index];
                        $modifiedSessionRatesMappingModel->time_7 = $_POST['SessionRateMapping']['time_7'];
                        $modifiedSessionRatesMappingModel->rate_7 = $_POST['SessionRateMapping']['rate_7'][$index];
                        $modifiedSessionRatesMappingModel->time_8 = $_POST['SessionRateMapping']['time_8'];
                        $modifiedSessionRatesMappingModel->rate_8 = $_POST['SessionRateMapping']['rate_8'][$index];
                        $modifiedSessionRatesMappingModel->time_9 = $_POST['SessionRateMapping']['time_9'];
                        $modifiedSessionRatesMappingModel->rate_9 = $_POST['SessionRateMapping']['rate_9'][$index];
                        $modifiedSessionRatesMappingModel->save();
                    }
                }
                if ($modifiedSessionModel->is_multiple_rates == 1 && $modifiedSessionModel->multiple_rates_type == 2) {
                    foreach ($_POST['SessionRateWeekdayMapping']['age_group'] as $index => $value) {
                        $modifiedSessionRatesWeekdayMappingModel = new SessionRateWeekdayMapping;
                        $modifiedSessionRatesWeekdayMappingModel->session_id = $modifiedSessionModel->id;
                        $modifiedSessionRatesWeekdayMappingModel->age_group = $value;
                        $modifiedSessionRatesWeekdayMappingModel->day_1 = $_POST['SessionRateWeekdayMapping']['day_1'];
                        $modifiedSessionRatesWeekdayMappingModel->rate_1 = $_POST['SessionRateWeekdayMapping']['rate_1'][$index];
                        $modifiedSessionRatesWeekdayMappingModel->day_2 = $_POST['SessionRateWeekdayMapping']['day_2'];
                        $modifiedSessionRatesWeekdayMappingModel->rate_2 = $_POST['SessionRateWeekdayMapping']['rate_2'][$index];
                        $modifiedSessionRatesWeekdayMappingModel->day_3 = $_POST['SessionRateWeekdayMapping']['day_3'];
                        $modifiedSessionRatesWeekdayMappingModel->rate_3 = $_POST['SessionRateWeekdayMapping']['rate_3'][$index];
                        $modifiedSessionRatesWeekdayMappingModel->day_4 = $_POST['SessionRateWeekdayMapping']['day_4'];
                        $modifiedSessionRatesWeekdayMappingModel->rate_4 = $_POST['SessionRateWeekdayMapping']['rate_4'][$index];
                        $modifiedSessionRatesWeekdayMappingModel->day_5 = $_POST['SessionRateWeekdayMapping']['day_5'];
                        $modifiedSessionRatesWeekdayMappingModel->rate_5 = $_POST['SessionRateWeekdayMapping']['rate_5'][$index];
                        $modifiedSessionRatesWeekdayMappingModel->day_6 = $_POST['SessionRateWeekdayMapping']['day_6'];
                        $modifiedSessionRatesWeekdayMappingModel->rate_6 = $_POST['SessionRateWeekdayMapping']['rate_6'][$index];
                        $modifiedSessionRatesWeekdayMappingModel->day_7 = $_POST['SessionRateWeekdayMapping']['day_7'];
                        $modifiedSessionRatesWeekdayMappingModel->rate_7 = $_POST['SessionRateWeekdayMapping']['rate_7'][$index];
                        $modifiedSessionRatesWeekdayMappingModel->save();
                    }
                }
                if ($modifiedSessionModel->is_multiple_rates == 1 && $modifiedSessionModel->multiple_rates_type == 3) {
                    foreach ($_POST['SessionRateTotalWeekdayMapping']['age_group'] as $index => $value) {
                        $modifiedSessionRatesTotalWeekdayMappingModel = new SessionRateTotalWeekdayMapping;
                        $modifiedSessionRatesTotalWeekdayMappingModel->session_id = $modifiedSessionModel->id;
                        $modifiedSessionRatesTotalWeekdayMappingModel->age_group = $value;
                        $modifiedSessionRatesTotalWeekdayMappingModel->total_day_1 = $_POST['SessionRateTotalWeekdayMapping']['total_day_1'];
                        $modifiedSessionRatesTotalWeekdayMappingModel->rate_1 = $_POST['SessionRateTotalWeekdayMapping']['rate_1'][$index];
                        $modifiedSessionRatesTotalWeekdayMappingModel->total_day_2 = $_POST['SessionRateTotalWeekdayMapping']['total_day_2'];
                        $modifiedSessionRatesTotalWeekdayMappingModel->rate_2 = $_POST['SessionRateTotalWeekdayMapping']['rate_2'][$index];
                        $modifiedSessionRatesTotalWeekdayMappingModel->total_day_3 = $_POST['SessionRateTotalWeekdayMapping']['total_day_3'];
                        $modifiedSessionRatesTotalWeekdayMappingModel->rate_3 = $_POST['SessionRateTotalWeekdayMapping']['rate_3'][$index];
                        $modifiedSessionRatesTotalWeekdayMappingModel->total_day_4 = $_POST['SessionRateTotalWeekdayMapping']['total_day_4'];
                        $modifiedSessionRatesTotalWeekdayMappingModel->rate_4 = $_POST['SessionRateTotalWeekdayMapping']['rate_4'][$index];
                        $modifiedSessionRatesTotalWeekdayMappingModel->total_day_5 = $_POST['SessionRateTotalWeekdayMapping']['total_day_5'];
                        $modifiedSessionRatesTotalWeekdayMappingModel->rate_5 = $_POST['SessionRateTotalWeekdayMapping']['rate_5'][$index];
                        $modifiedSessionRatesTotalWeekdayMappingModel->total_day_6 = $_POST['SessionRateTotalWeekdayMapping']['total_day_6'];
                        $modifiedSessionRatesTotalWeekdayMappingModel->rate_6 = $_POST['SessionRateTotalWeekdayMapping']['rate_6'][$index];
                        $modifiedSessionRatesTotalWeekdayMappingModel->total_day_7 = $_POST['SessionRateTotalWeekdayMapping']['total_day_7'];
                        $modifiedSessionRatesTotalWeekdayMappingModel->rate_7 = $_POST['SessionRateTotalWeekdayMapping']['rate_7'][$index];
                        $modifiedSessionRatesTotalWeekdayMappingModel->rate_monthly = floatval($_POST['SessionRateTotalWeekdayMapping']['rate_monthly'][$index]);
                        $modifiedSessionRatesTotalWeekdayMappingModel->save();
                    }
                }
                $this->redirect(array('index'));
            } else {
                Yii::app()->user->setFlash('error', CHtml::errorSummary($modifiedSessionModel, "", "", array('class' => 'customErrors')));
                $this->refresh();
            }
        }

        if ($model->is_multiple_rates == 1 && $model->multiple_rates_type == 1) {
            $this->render('update', array(
                'model' => $model, 'age_group' => $age_group, 'time' => $time, 'rate_age_group' => $rate_age_group
            ));
        } else if ($model->is_multiple_rates == 1 && $model->multiple_rates_type == 2) {
            $this->render('update', array(
                'model' => $model, 'age_group' => $age_group, 'time' => $weekDay, 'rate_age_group' => $rate_age_group
            ));
        } else if ($model->is_multiple_rates == 1 && $model->multiple_rates_type == 3) {
            $this->render('update', array(
                'model' => $model, 'age_group' => $age_group, 'time' => $totalWeekDay, 'rate_age_group' => $rate_age_group
            ));
        } else {
            $this->render('update', array(
                'model' => $model
            ));
        }
    }

    public function actionUpdatePriority() {
        if (Yii::app()->request->isAjaxRequest) {
            $model = SessionRates::model()->findByPk(Yii::app()->request->getPost('id'));
            if (!empty($model)) {
                $model->priority = Yii::app()->request->getPost('priority');
                if ($model->save()) {
                    echo CJSON::encode(['status' => 1, 'message' => 'Priority has been successfully updated.']);
                } else {
                    echo CJSON::encode(['status' => 0, 'message' => $model->getErrors()]);
                }
            } else {
                echo CJSON::encode(['status' => 0, 'message' => 'Session type does not exists.']);
            }
        } else {
            throw new CHttpException('Your request is not valid.');
        }
    }

}
