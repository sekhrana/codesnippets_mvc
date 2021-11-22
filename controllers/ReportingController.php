<?php

Yii::app()->clientScript->registerScript('helpers', '                                                           
          yii = {                                                                                                     
              urls: {                                                                                                 
                  getColumns: ' . CJSON::encode(Yii::app()->createUrl('reporting/getColumns')) . ',                                   
              }                                                                                                       
          };                                                                                                          
      ', CClientScript::POS_END);

class ReportingController extends Controller {

    public $layout = '//layouts/dashboard';

    public function actionIndex() {
        if (isset($_POST['generate_report'])) {
            $column_name = "";
            $condition = "";
            foreach ($_POST['filter_column'] as $key => $value) {
                $column_name = $column_name . $value . ", ";
                if (empty($_POST['filter'][$key])) {
                    $condition = $condition;
                } else {
                    $condition = $condition . " " . $value . " " . $_POST['filter'][$key] . " " . "'" . $_POST['filter_value'][$key] . "'" . " AND ";
                }
            }
            $condition = ($condition == "") ? "1" : substr($condition, 0, -4);
            $column_name = substr($column_name, 0, -2);
            $sql = "SELECT " . $column_name . " FROM " . $_POST['reports_for'] . " WHERE " . $condition;
            echo $sql;
            $result = Yii::app()->db->createCommand($sql)->queryAll(true);
            print_r($result);
            die;
        }
        $this->render('index');
    }

    public function actionGetColumns() {
        if (isset($_POST['reportsFor'])) {
            $table_model_mapping = array(
                'tbl_child_personal_details' => 'ChildPersonalDetails',
                'tbl_staff_personal_details' => 'StaffPersonalDetails'
            );
            $columns = $table_model_mapping[$_POST['reportsFor']]::model()->getTableSchema()->getColumnNames();
            echo CJSON::encode($columns);
        } else {
            throw new CHttpException(404, 'Your request is invalid');
        }
    }

}
