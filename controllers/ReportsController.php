<?php
Yii::app()->clientScript->registerScript('helpers', '
          eyMan = {
              urls: {
                  staffHourReportPdf : ' . CJSON::encode(Yii::app()->createUrl('reports/staffHourReportPdf')) . ',
                  staffHourReportCsv : ' . CJSON::encode(Yii::app()->createUrl('reports/staffHourReportCsv')) . ',
                  staffSchedulingReportPdf : ' . CJSON::encode(Yii::app()->createUrl('reports/staffSchedulingReportPdf')) . ',
                  staffSchedulingReportCsv : ' . CJSON::encode(Yii::app()->createUrl('reports/staffSchedulingReportCsv')) . ',
              }
          };
      ', CClientScript::POS_END);

class ReportsController extends RController {

	/**
	 *
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 *      using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout = '//layouts/dashboard';

	/**
	 *
	 * @return array action filters
	 */
	public function filters() {
		return array(
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
	 *            the ID of the model to be displayed
	 */
	public function actionView($id) {
		$model = $this->loadModel($id);
		$grid_columns = CJSON::decode($model->data)['grid_columns'];
		$column_name = CJSON::decode($model->data)['column-name'];
		$query = CJSON::decode($model->data)['query'];
		$report = Yii::app()->db->createCommand($query)->queryAll();
		$formatted_report = array();
		foreach ($report as $reportKey => $reportValue) {
			$temp = array();
			foreach ($reportValue as $reportColumnName => $reportColumnValue) {
				$prevReportColumnName = $reportColumnName;
				$reportColumnName = explode("-", $reportColumnName)[1];
				foreach ($column_name as $key => $value) {
					if ($reportColumnName == "age") {
						$temp[$prevReportColumnName] = $key::model()->getColumnValue($reportColumnName, $reportColumnValue);
					} else {
						if ($key::model()->hasAttribute($reportColumnName)) {
							$temp[$prevReportColumnName] = $key::model()->getColumnValue($reportColumnName, $reportColumnValue);
						}
					}
				}
			}
			$formatted_report[] = $temp;
		}
		$this->render('view', array(
			'model' => $model,
			'grid_columns' => $grid_columns,
			'formatted_report' => $formatted_report
		));
	}

	public function actionDownloadPdf($id) {
		$model = $this->loadModel($id);
		$grid_columns = CJSON::decode($model->data)['grid_columns'];
		$previous_headers = array();
		$new_headers = array();
		foreach ($grid_columns as $key => $value) {
			$previous_headers[] = $value['name'];
			$new_headers[] = $value['header'];
		}
		$column_name = CJSON::decode($model->data)['column-name'];
		$query = CJSON::decode($model->data)['query'];
		$report = Yii::app()->db->createCommand($query)->queryAll();
		$formatted_report = array();
		foreach ($report as $reportKey => $reportValue) {
			$temp = array();
			foreach ($reportValue as $reportColumnName => $reportColumnValue) {
				$prevReportColumnName = $reportColumnName;
				$reportColumnName = explode("-", $reportColumnName)[1];
				foreach ($column_name as $key => $value) {
					if ($reportColumnName == "age") {
						$temp[$prevReportColumnName] = $key::model()->getColumnValue($reportColumnName, $reportColumnValue);
					} else {
						if ($key::model()->hasAttribute($reportColumnName)) {
							$temp[$prevReportColumnName] = $key::model()->getColumnValue($reportColumnName, $reportColumnValue);
						}
					}
				}
			}
			$formatted_report[] = $temp;
		}
		$mpdf = new mPDF('A4', '', 0, '', 2.5, 2.5, 10, 10, 2.5, 2.5, 'L');
		$mpdf->WriteHTML($this->renderPartial('reportPdf', array(
				'formatted_report' => $formatted_report,
				'new_headers' => $new_headers
				), TRUE));
		$mpdf->Output('Report.pdf', "D");
		exit();
	}

	public function actionDownloadCsv($id) {
		$model = $this->loadModel($id);
		$grid_columns = CJSON::decode($model->data)['grid_columns'];
		$previous_headers = array();
		$new_headers = array();
		foreach ($grid_columns as $key => $value) {
			$previous_headers[] = $value['name'];
			$new_headers[] = $value['header'];
		}
		$column_name = CJSON::decode($model->data)['column-name'];
		$query = CJSON::decode($model->data)['query'];
		$report = Yii::app()->db->createCommand($query)->queryAll();
		$formatted_report = array();
		foreach ($report as $reportKey => $reportValue) {
			$temp = array();
			foreach ($reportValue as $reportColumnName => $reportColumnValue) {
				$prevReportColumnName = $reportColumnName;
				$reportColumnName = explode("-", $reportColumnName)[1];
				foreach ($column_name as $key => $value) {
					if ($reportColumnName == "age") {
						$temp[$prevReportColumnName] = $key::model()->getColumnValue($reportColumnName, $reportColumnValue);
					} else {
						if ($key::model()->hasAttribute($reportColumnName)) {
							$temp[$prevReportColumnName] = $key::model()->getColumnValue($reportColumnName, $reportColumnValue);
						}
					}
				}
			}
			$formatted_report[] = $temp;
		}
		$csv = new ECSVExport($formatted_report);
		$csv->setHeaders(array_combine($previous_headers, $new_headers));
		$output = $csv->toCSV();
		Yii::app()->getRequest()->sendFile('report.csv', $output, "text/csv", false);
		exit();
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate($tab_name) {
		if ($tab_name == "select-area" || $tab_name == "filter-options" || $tab_name == "save-report" || $tab_name == 'report') {
			$model = new Reports();
			$childPersonalDetailsModel = new ChildPersonalDetails();
			$childParentalDetailsModel = new ChildParentalDetails();
			$childGeneralDetailsModel = new ChildGeneralDetails();
			$childMedicalDetailsModel = new ChildMedicalDetails();
			$childBookingsModel = new ChildBookings();
			$childHolidayModel = new ChildHolidays();
			$childFundingModel = new ChildFundingDetails();
			$childInvoiceModel = new ChildInvoice();
			$childInvoiceTransactionModel = new ChildInvoiceTransactions();
			$staffPersonalDetailsModel = new StaffPersonalDetails();
			$staffGeneralDetailsModel = new StaffGeneralDetails();
			$staffBankDetailsModel = new StaffBankDetails();
			$staffBookingsModel = new StaffBookings();
			$staffHolidayModel = new StaffHolidays();
			$staffDocumentModel = new StaffDocumentDetails();
			$staffEventModel = new StaffEventDetails();
			if (isset($_POST['Next']) && ($_GET['tab_name'] == "select-area")) {
				$report_sub_area = $_POST['report_sub_area'];
				$column_names = array();
				$join_condition = array();
				switch ($report_sub_area) {
					case 'child_data':
						if (isset($_POST['ChildPersonalDetails'])) {
							$column_names['ChildPersonalDetails'] = $_POST['ChildPersonalDetails'];
						}
						if (isset($_POST['ChildParentalDetails'])) {
							$column_names['ChildParentalDetails'] = $_POST['ChildParentalDetails'];
						}
						if (isset($_POST['ChildGeneralDetails'])) {
							$column_names['ChildGeneralDetails'] = $_POST['ChildGeneralDetails'];
						}
						if (isset($_POST['ChildMedicalDetails'])) {
							$column_names['ChildMedicalDetails'] = $_POST['ChildMedicalDetails'];
						}
						if (isset($_POST['Room'])) {
							$column_names['Room'] = $_POST['Room'];
						}
						if (isset($_POST['SessionRates'])) {
							$column_names['SessionRates'] = $_POST['SessionRates'];
						}
						if (isset($_POST['StaffPersonalDetails'])) {
							$column_names['StaffPersonalDetails'] = $_POST['StaffPersonalDetails'];
						}

						/**
						 * Check for the join condition in the query *
						 */
						if (isset($_POST['ChildPersonalDetails'])) {
							if (isset($_POST['ChildParentalDetails'])) {
								$join_condition["tbl_child_parental_details"] = "tbl_child_parental_details.child_id = tbl_child_personal_details.id";
							}
							if (isset($_POST['ChildGeneralDetails'])) {
								$join_condition["tbl_child_general_details"] = "tbl_child_general_details.child_id = tbl_child_personal_details.id";
							}
							if (isset($_POST['ChildMedicalDetails'])) {
								$join_condition["tbl_child_medical_details"] = "tbl_child_medical_details.child_id = tbl_child_personal_details.id";
							}
							if (isset($_POST['Room'])) {
								$join_condition["tbl_room"] = "tbl_room.id = tbl_child_personal_details.room_id";
							}
							if (isset($_POST['SessionRates'])) {
								$join_condition["tbl_session_rates"] = "tbl_session_rates.id = tbl_child_personal_details.preffered_session";
							}
							if (isset($_POST['StaffPersonalDetails'])) {
								$join_condition["tbl_staff_personal_details"] = "tbl_staff_personal_details.id = tbl_child_personal_details.key_person";
							}
						} else {
							if (isset($_POST['ChildGeneralDetails']) && isset($_POST['ChildMedicalDetails'])) {
								$join_condition["tbl_child_medical_details"] = "tbl_child_medical_details.child_id = tbl_child_general_details.child_id";
							}
						}
						/**
						 * Check for the join condition ends here*
						 */
						break;

					case "child_session_data":
						if (isset($_POST['ChildBookings'])) {
							$column_names['ChildBookings'] = $_POST['ChildBookings'];
						}

						if (isset($_POST['ChildPersonalDetails'])) {
							$column_names['ChildPersonalDetails'] = $_POST['ChildPersonalDetails'];
						}

						if (isset($_POST['Room'])) {
							$column_names['Room'] = $_POST['Room'];
						}

						/**
						 * Check for the join condition in the query *
						 */
						if (isset($_POST['ChildBookings'])) {
							if (isset($_POST['ChildPersonalDetails'])) {
								$join_condition["tbl_child_personal_details"] = "tbl_child_bookings.child_id = tbl_child_personal_details.id";
							}
							if (isset($_POST['Room'])) {
								$join_condition["tbl_room"] = "tbl_room.id = tbl_child_bookings.room_id";
							}
						}
						/**
						 * Check for the join condition ends here*
						 */
						break;

					case 'staff_data':
						if (isset($_POST['StaffPersonalDetails'])) {
							$column_names['StaffPersonalDetails'] = $_POST['StaffPersonalDetails'];
						}
						if (isset($_POST['StaffGeneralDetails'])) {
							$column_names['StaffGeneralDetails'] = $_POST['StaffGeneralDetails'];
						}
						if (isset($_POST['StaffBankDetails'])) {
							$column_names['StaffBankDetails'] = $_POST['StaffBankDetails'];
						}

						/**
						 * Check for the join condition in the query *
						 */
						if (isset($_POST['StaffPersonalDetails'])) {
							if (isset($_POST['StaffGeneralDetails'])) {
								$join_condition["tbl_staff_general_details"] = "tbl_staff_general_details.staff_id = tbl_staff_personal_details.id";
							}
							if (isset($_POST['StaffBankDetails'])) {
								$join_condition["tbl_staff_bank_details"] = "tbl_staff_bank_details.staff_id = tbl_staff_personal_details.id";
							}
						} else {
							if (isset($_POST['StaffGeneralDetails']) && isset($_POST['StaffBankDetails'])) {
								$join_condition["tbl_staff_bank_details"] = "tbl_staff_bank_details.staff_id = tbl_staff_general_details.staff_id";
							}
						}
						/**
						 * Check for the join condition ends here*
						 */
						break;

					case "staff_session_data":
						if (isset($_POST['StaffBookings'])) {
							$column_names['StaffBookings'] = $_POST['StaffBookings'];
						}
						if (isset($_POST['StaffPersonalDetails'])) {
							$column_names['StaffPersonalDetails'] = $_POST['StaffPersonalDetails'];
						}
						/**
						 * Check for the join condition in the query *
						 */
						if (isset($_POST['StaffBookings'])) {
							if (isset($_POST['StaffPersonalDetails'])) {
								$join_condition["tbl_staff_personal_details"] = "tbl_staff_bookings.child_id = tbl_staff_personal_details.id";
							}
						}
						/**
						 * Check for the join condition ends here*
						 */
						break;

					case 'staff_holiday_data':
						if (isset($_POST['StaffHolidays'])) {
							$column_names['StaffHolidays'] = $_POST['StaffHolidays'];
						}
						if (isset($_POST['StaffPersonalDetails'])) {
							$column_names['StaffPersonalDetails'] = $_POST['StaffPersonalDetails'];
						}
						/**
						 * Check for the join condition in the query *
						 */
						if (isset($_POST['StaffHolidays'])) {
							if (isset($_POST['StaffPersonalDetails'])) {
								$join_condition["tbl_staff_personal_details"] = "tbl_staff_holidays.child_id = tbl_staff_personal_details.id";
							}
						}
						/**
						 * Check for the join condition ends here*
						 */
						break;
					case 'staff_document_data':
						if (isset($_POST['StaffDocumentDetails'])) {
							$column_names['StaffDocumentDetails'] = $_POST['StaffDocumentDetails'];
						}
						break;

					case 'staff_event_data':
						if (isset($_POST['StaffEventDetails'])) {
							$column_names['StaffEventDetails'] = $_POST['StaffEventDetails'];
						}
						break;
					case 'child_invoice_data':
						if (isset($_POST['ChildInvoice'])) {
							$column_names['ChildInvoice'] = $_POST['ChildInvoice'];
						}
						if (isset($_POST['ChildPersonalDetails'])) {
							$column_names['ChildPersonalDetails'] = $_POST['ChildPersonalDetails'];
						}
						/**
						 * Check for the join condition in the query *
						 */
						if (isset($_POST['ChildInvoice'])) {
							if (isset($_POST['ChildPersonalDetails'])) {
								$join_condition["tbl_child_personal_details"] = "tbl_child_invoice.child_id = tbl_child_personal_details.id";
							}
						}
						/**
						 * Check for the join condition ends here*
						 */
						break;

					case 'child_holiday_data':
						if (isset($_POST['ChildHolidays'])) {
							$column_names['ChildHolidays'] = $_POST['ChildHolidays'];
						}
						if (isset($_POST['ChildPersonalDetails'])) {
							$column_names['ChildPersonalDetails'] = $_POST['ChildPersonalDetails'];
						}
						/**
						 * Check for the join condition in the query *
						 */
						if (isset($_POST['ChildHolidays'])) {
							if (isset($_POST['ChildPersonalDetails'])) {
								$join_condition["tbl_child_personal_details"] = "tbl_child_holidays.child_id = tbl_child_personal_details.id";
							}
						}
						/**
						 * Check for the join condition ends here*
						 */
						break;

					case 'child_invoice_payment_data':
						if (isset($_POST['ChildInvoiceTransactions'])) {
							$column_names['ChildInvoiceTransactions'] = $_POST['ChildInvoiceTransactions'];
						}
						if (isset($_POST['ChildInvoice'])) {
							$column_names['ChildInvoice'] = $_POST['ChildInvoice'];
						}

						/**
						 * Check for the join condition in the query *
						 */
						if (isset($_POST['ChildInvoice'])) {
							$join_condition["tbl_child_invoice"] = "tbl_child_invoice_transactions.invoice_id = tbl_child_invoice.id";
						}
						/**
						 * Check for the join condition ends here*
						 */
						break;

					case 'child_funding_data':
						if (isset($_POST['ChildFundingDetails'])) {
							$column_names['ChildFundingDetails'] = $_POST['ChildFundingDetails'];
						}
						break;
				}
				$this->redirect(array(
					'reports/create',
					'tab_name' => 'filter-options',
					'column-name' => $column_names,
					'join_condition' => $join_condition,
					'report_area' => $_POST['report_area'],
					'report_sub_area' => $_POST['report_sub_area']
				));
			}
			if (isset($_POST['Next']) && ($_GET['tab_name'] == "filter-options")) {
				$columns = array();
				$tables = array();
				$where = "";
				$grid_columns = array();
				foreach ($_GET['column-name'] as $key => $value) {
					$tables[] = $key::model()->tableName();
					foreach ($value as $key2 => $value2) {
						if ($key2 == "age") {
							$columns[] = "tbl_child_personal_details.dob AS tbl_child_personal_details-age";
						} else {
							$columns[] = $key::model()->tableName() . "." . $key2 . " AS " . $key::model()->tableName() . "-" . $key2;
						}
						$grid_columns[] = array(
							'name' => $key::model()->tableName() . "-" . $key2,
							'header' => $key::model()->getAttributeLabel($key2)
						);
						$filter = $key . "=" . $key2 . "=filter";
						if (!empty($_POST[$filter])) {
							foreach ($_POST[$filter] as $key3 => $value3) {
								if (trim($value3) != "") {
									if (trim($value3) == "EMPTY") {
										$where = $where . " " . $key::model()->tableName() . "." . $key2 . " = ''" . " " . $_POST[$filter . "_condition"][$key3];
									} else
									if (trim($value3) == "IS NULL") {
										$where = $where . " " . $key::model()->tableName() . "." . $key2 . " IS NULL " . " " . $_POST[$filter . "_condition"][$key3];
									} else
									if (trim($value3) == "IS NOT NULL") {
										$where = $where . " " . $key::model()->tableName() . "." . $key2 . " IS NOT NULL " . " " . $_POST[$filter . "_condition"][$key3];
									} else
									if (trim($value3) == "LIKE %--%") {
										$where = $where . " " . $key::model()->tableName() . "." . $key2 . " " . " LIKE " . "'%" . $_POST[$filter . "_value"][$key3] . "%'" . " " . $_POST[$filter . "_condition"][$key3];
									} else {
										if (trim($_POST[$filter . "_value"][$key3]) == "") {
											$where = $where . " " . $key::model()->tableName() . "." . $key2 . " " . $value3 . " " . $_POST[$filter . "_condition"][$key3];
										} else {
											$where = $where . " " . $key::model()->tableName() . "." . $key2 . " " . $value3 . " " . "'" . $_POST[$filter . "_value"][$key3] . "'" . " " . $_POST[$filter . "_condition"][$key3];
										}
									}
								}
							}
						}
					}
				}
				$query = Yii::app()->db->createCommand()->select(implode(",", $columns));
				if (isset($_GET['join_condition'])) {
					foreach ($_GET['join_condition'] as $key => $value) {
						array_splice($tables, array_search($key, $tables), 1);
						$query->leftJoin($key, $value);
					}
				}
				$query->from(implode(",", $tables));
				$where = rtrim(rtrim($where, "AND"), "OR");
				if ($where == "") {
					$query->where(rtrim(rtrim($where, "AND"), "OR") . "  branch_id = " . Yii::app()->session['branch_id'] . " AND is_deleted = 0");
				} else {
					$query->where(rtrim(rtrim($where, "AND"), "OR") . " AND  branch_id = " . Yii::app()->session['branch_id'] . " AND is_deleted = 0");
				}
				$report = $query->queryAll();
				Yii::app()->session['query'] = trim(preg_replace('/\s+/', ' ', $query->text));
				$formatted_report = array();
				foreach ($report as $reportKey => $reportValue) {
					$temp = array();
					foreach ($reportValue as $reportColumnName => $reportColumnValue) {
						$prevReportColumnName = $reportColumnName;
						$reportColumnName = explode("-", $reportColumnName)[1];
						foreach ($_GET['column-name'] as $key => $value) {
							if ($reportColumnName == "age") {
								$temp[$prevReportColumnName] = $key::model()->getColumnValue($reportColumnName, $reportColumnValue);
							} else {
								if ($key::model()->hasAttribute($reportColumnName) && $value !== "age") {
									$temp[$prevReportColumnName] = $key::model()->getColumnValue($reportColumnName, $reportColumnValue);
								}
							}
						}
					}
					$formatted_report[] = $temp;
				}
				Yii::app()->session['report'] = $formatted_report;
				Yii::app()->session['grid_columns'] = $grid_columns;
				Yii::app()->session['column-name'] = $_GET['column-name'];
				Yii::app()->session['join_condition'] = $_GET['join_condition'];
				Yii::app()->session['report_area'] = $_GET['report_area'];
				Yii::app()->session['report_sub_area'] = $_GET['report_sub_area'];
				$this->redirect(array(
					'reports/create',
					'tab_name' => 'report'
				));
			}

			if (isset($_POST['Next']) && ($_GET['tab_name'] == "report")) {
				$this->redirect(array(
					'reports/create',
					'tab_name' => 'save-report'
				));
			}

			if (isset($_POST['Save']) && ($_GET['tab_name'] == "save-report")) {
				$model->attributes = $_POST['Reports'];
				$this->performAjaxValidation($model);
				$model->branch_id = Yii::app()->session['branch_id'];
				$model->saved_by = Yii::app()->user->id;
				$model->data = CJSON::encode(array(
						'query' => Yii::app()->session['query'],
						'grid_columns' => Yii::app()->session['grid_columns'],
						'column-name' => Yii::app()->session['column-name'],
						'join_condition' => Yii::app()->session['join_condition'],
						'report_area' => Yii::app()->session['report_area'],
						'report_sub_area' => Yii::app()->session['report_sub_area']
				));
				if ($model->save()) {
					$this->redirect(array(
						'reports/index'
					));
				} else {
					Yii::app()->user->setFlash('error', "Their seems to be some problem saving the reports.Please fill all the mandatory fields.");
					$this->refresh();
				}
			}
			$this->render('create', array(
				'model' => $model,
				'childPersonalDetailsModel' => $childPersonalDetailsModel,
				'childParentalDetailsModel' => $childParentalDetailsModel,
				'childGeneralDetailsModel' => $childGeneralDetailsModel,
				'childMedicalDetailsModel' => $childMedicalDetailsModel,
				'staffPersonalDetailsModel' => $staffPersonalDetailsModel,
				'staffGeneralDetailsModel' => $staffGeneralDetailsModel,
				'childBookingsModel' => $childBookingsModel,
				'childHolidayModel' => $childHolidayModel,
				'childFundingModel' => $childFundingModel,
				'childInvoiceModel' => $childInvoiceModel,
				'childInvoiceTransactionModel' => $childInvoiceTransactionModel,
				'staffPersonalDetailsModel' => $staffPersonalDetailsModel,
				'staffGeneralDetailsModel' => $staffGeneralDetailsModel,
				'staffBankDetailsModel' => $staffBankDetailsModel,
				'staffBookingsModel' => $staffBookingsModel,
				'staffHolidayModel' => $staffHolidayModel,
				'staffDocumentModel' => $staffDocumentModel,
				'staffEventModel' => $staffEventModel
			));
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 *
	 * @param integer $id
	 *            the ID of the model to be updated
	 */
	public function actionUpdate($id) {
		$model = $this->loadModel($id);

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if (isset($_POST['Reports'])) {
			$model->attributes = $_POST['Reports'];
			if ($model->save())
				$this->redirect(array(
					'view',
					'id' => $model->id
				));
		}

		$this->render('update', array(
			'model' => $model
		));
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 *
	 * @param integer $id
	 *            the ID of the model to be deleted
	 */
	public function actionDelete() {
		if (isset($_POST) && $_POST['isAjaxRequest'] == 1) {
			$response = array(
				'status' => '1'
			);
			$model = $this->loadModel($_POST['id']);
			$model->is_deleted = 1;
			if ($model->save()) {
				echo CJSON::encode($response);
			} else {
				$response = array(
					'status' => '0'
				);
				echo CJSON::encode($response);
			}
		}
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex() {
		$response = array();
		$model = Reports::model()->findAllByAttributes(array(
			'branch_id' => Yii::app()->session['branch_id']
			), array(
			'order' => 'name'
		));
		$pickReportsModel = PickReports::model()->findAll(array(
			'order' => 'name'
		));
		foreach ($model as $report) {
			$temp = array();
			$temp['id'] = $report->id;
			$temp['name'] = $report->name;
			$temp['description'] = $report->description;
			$temp['type'] = 0;
			$temp['is_standard_report'] = $report->is_standard_report;
			$temp['report_id'] = $report->report_id;
			$response[] = $temp;
		}
		$standard_reports = array();
		foreach ($response as $key => $value) {
			$standard_reports[] = $value['report_id'];
		}
		foreach ($pickReportsModel as $report) {
			if (!in_array($report->id, $standard_reports)) {
				$temp = array();
				$temp['id'] = $report->id;
				$temp['name'] = $report->name;
				$temp['type'] = 1;
				$response[] = $temp;
			}
		}
		$dataProvider = new CArrayDataProvider($response, array(
			'keyField' => false,
			'pagination' => array(
				'pageSize' => 30
			)
		));
		$this->render('index', array(
			'dataProvider' => $dataProvider
		));
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin() {
		$model = new Reports('search');
		$model->unsetAttributes(); // clear any default values
		if (isset($_GET['Reports']))
			$model->attributes = $_GET['Reports'];

		$this->render('admin', array(
			'model' => $model
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 *
	 * @param integer $id
	 *            the ID of the model to be loaded
	 * @return Reports the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id) {
		$model = Reports::model()->findByPk($id);
		if ($model === null)
			throw new CHttpException(404, 'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 *
	 * @param Reports $model
	 *            the model to be validated
	 */
	protected function performAjaxValidation($model) {
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'reports-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	public function actionCreateStandardReport($tab_name, $report_id) {
		$reportModel = PickReports::model()->findByPk($report_id);
		$model = new Reports();
		if (!empty($reportModel)) {
			if ($_GET['tab_name'] == 'filter-options' && isset($_POST['Next'])) {
				if ($_GET['report_id'] == 1) {
					Yii::app()->session['report'] = customFunctions::fundingTermTimeReport(Branch::currentBranch()->id, $_POST['term_id']);
					$grid_columns = array();
					if (!empty(Yii::app()->session['report'])) {
						foreach (Yii::app()->session['report'][0] as $key => $value) {
							if ($key == "Difference (Hours)") {
								$grid_columns[] = array(
									'name' => $key,
									'type' => 'raw',
									'cssClassExpression' => '($data["Difference (Hours)"] > 0) ? "reportBackgroundYellow" : ""'
								);
							} else {
								$grid_columns[] = array(
									'name' => $key
								);
							}
						}
					}
					Yii::app()->session['grid_columns'] = $grid_columns;
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'term_id' => $_POST['term_id'],
						'year' => $_POST['year']
					));
				}

				if ($_GET['report_id'] == 2) {
					Yii::app()->session['report'] = customFunctions::fundingMonthlyReport($_POST['start_date'], $_POST['finish_date']);
					$grid_columns = array();
					if (!empty(Yii::app()->session['report'])) {
						foreach (Yii::app()->session['report'][0] as $key => $value) {
							if ($key == "Difference (Hours)") {
								$grid_columns[] = array(
									'name' => $key,
									'type' => 'raw',
									'cssClassExpression' => '($data["Difference (Hours)"] > 0) ? "reportBackgroundYellow" : ""'
								);
							} else {
								$grid_columns[] = array(
									'name' => $key
								);
							}
						}
					}
					Yii::app()->session['grid_columns'] = $grid_columns;
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'start_date' => $_POST['start_date'],
						'finish_date' => $_POST['finish_date']
					));
				}

				if ($_GET['report_id'] == Reports::TWO_THREE_YEAR_FUNDING_REPORT) {
					Yii::app()->session['report'] = customFunctions::funding2and3YearOldReport($_POST['start_date'], $_POST['finish_date'], $_POST['branch_id']);
					Yii::app()->session['grid_columns'] = array();
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'start_date' => $_POST['start_date'],
						'finish_date' => $_POST['finish_date'],
						'branch_id' => $_POST['branch_id']
					));
				}

				if ($_GET['report_id'] == Reports::STAFF_WAGES_WEEKLY) {
					Yii::app()->session['report'] = customFunctions::staffWagesWeeklyPercentReport($_POST['start_date'], $_POST['finish_date'], $_POST['show_sums_only'], $_POST['additional_income'], $_POST['include_products_services']);
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'start_date' => $_POST['start_date'],
						'finish_date' => $_POST['finish_date'],
						'show_sums_only' => $_POST['show_sums_only'],
						'additional_income' => $_POST['additional_income'],
						'include_products_services' => $_POST['include_products_services']
					));
				}

				if ($_GET['report_id'] == Reports::STAFF_HOURS_REPORT) {
					Yii::app()->session['report'] = customFunctions::staffHoursReport($_POST['month'], $_POST['week1_code'], $_POST['week2_code'], $_POST['week3_code'], $_POST['week4_code'], $_POST['week5_code'], $_POST['stepUp_code'], $_POST['exclude_salaried'], $_POST['branch_id']);
					$grid_columns = array();
					if (!empty(Yii::app()->session['report'])) {
						foreach (Yii::app()->session['report'][0] as $key => $value) {
							$grid_columns[] = array(
								'name' => $key
							);
						}
					}
					Yii::app()->session['grid_columns'] = $grid_columns;
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'month' => $_POST['month'],
						'week1_code' => $_POST['week1_code'],
						'week2_code' => $_POST['week2_code'],
						'week3_code' => $_POST['week3_code'],
						'week4_code' => $_POST['week4_code'],
						'week5_code' => $_POST['week5_code'],
						'stepUp_code' => $_POST['stepUp_code'],
						'exclude_salaried' => $_POST['exclude_salaried']
					));
				}

				if ($_GET['report_id'] == 6) {
					Yii::app()->session['report'] = customFunctions::getStaffWagesMonthlyReport($_POST['month']);
					$grid_columns = array();
					if (!empty(Yii::app()->session['report'])) {
						foreach (Yii::app()->session['report'][0] as $key => $value) {
							$grid_columns[] = array(
								'name' => $key
							);
						}
					}
					Yii::app()->session['grid_columns'] = $grid_columns;
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'month' => $_POST['month']
					));
				}

				if ($_GET['report_id'] == 7) {
					Yii::app()->session['report'] = customFunctions::agedDebtorsReport($_POST['report_date']);
					$grid_columns = array();
					if (!empty(Yii::app()->session['report'])) {
						foreach (Yii::app()->session['report'][0] as $key => $value) {
							$grid_columns[] = array(
								'name' => $key
							);
						}
					}
					Yii::app()->session['grid_columns'] = $grid_columns;
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'report_date' => $_POST['report_date']
					));
				}

				if ($_GET['report_id'] == Reports::STAFF_SCHEDULING_REPORT) {
					Yii::app()->session['report'] = customFunctions::getStaffHoursData($_POST['start_date'], $_POST['finish_date'], $_POST['holiday_type_reason'], $_POST['absence_only'], $_POST['staff_id'], $_POST['group_by'], $_POST['order_by'], $_POST['minimum_data']);
					Yii::app()->session['grid_columns'] = array();
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'start_date' => $_POST['start_date'],
						'finish_date' => $_POST['finish_date'],
						'minimum_data' => $_POST['minimum_data'],
						'holiday_type_reason' => $_POST['holiday_type_reason'],
						'absence_only' => $_POST['absence_only'],
						'staff_id' => $_POST['staff_id'],
						'group_by' => $_POST['group_by']
					));
				}

				if ($_GET['report_id'] == Reports::MONTHLY_DEBTORS) {
					Yii::app()->session['report'] = customFunctions::getMonthlyDebtors($_POST['month']);
					Yii::app()->session['grid_columns'] = array();
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'month' => $_POST['month']
					));
				}
				if ($_GET['report_id'] == Reports::CHILDREN_WITHOUT_BOOKING) {
					Yii::app()->session['report'] = customFunctions::childrenWithoutBooking($_POST['start_date'], $_POST['finish_date']);
					Yii::app()->session['grid_columns'] = array();
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'start_date' => $_POST['start_date'],
						'finish_date' => $_POST['finish_date']
					));
				}
				if ($_GET['report_id'] == Reports::CHILD_NAPPY_RECORD) {
					Yii::app()->session['report'] = customFunctions::childNappyRecord($_POST['room_id'], $_POST['start_date'], $_POST['finish_date']);
					Yii::app()->session['grid_columns'] = array();
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'room_id' => $_POST['room_id'],
						'start_date' => $_POST['start_date'],
						'finish_date' => $_POST['finish_date']
					));
				}
				if ($_GET['report_id'] == Reports::CHILD_SLEEP_RECORD) {
					Yii::app()->session['report'] = customFunctions::childSleepRecord($_POST['room_id'], $_POST['start_date'], $_POST['finish_date']);
					Yii::app()->session['grid_columns'] = array();
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'room_id' => $_POST['room_id'],
						'start_date' => $_POST['start_date'],
						'finish_date' => $_POST['finish_date']
					));
				}
				if ($_GET['report_id'] == Reports::STAFF_SIGN_IN_OUT_REPORT) {
					Yii::app()->session['report'] = customFunctions::staffSignInOutReport($_POST['start_date'], $_POST['finish_date']);
					Yii::app()->session['grid_columns'] = array();
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'start_date' => $_POST['start_date'],
						'finish_date' => $_POST['finish_date']
					));
				}
				if ($_GET['report_id'] == Reports::CHILD_REGISTER_REPORT) {
					Yii::app()->session['report'] = customFunctions::childRegisterReport($_POST['room_id'], $_POST['start_date'], $_POST['finish_date']);
					Yii::app()->session['grid_columns'] = array();
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'room_id' => $_POST['room_id'],
						'start_date' => $_POST['start_date'],
						'finish_date' => $_POST['finish_date']
					));
				}
				if ($_GET['report_id'] == Reports::CHILD_SUNCREAM_REPORT) {
					Yii::app()->session['report'] = customFunctions::childSuncreamReport($_POST['room_id'], $_POST['start_date'], $_POST['finish_date']);
					Yii::app()->session['grid_columns'] = array();
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'room_id' => $_POST['room_id'],
						'start_date' => $_POST['start_date'],
						'finish_date' => $_POST['finish_date']
					));
				}
				if ($_GET['report_id'] == Reports::PAYMENT_REPORT) {
					Yii::app()->session['report'] = customFunctions::childPaymentReport($_POST['start_date'], $_POST['finish_date']);
					Yii::app()->session['grid_columns'] = array();
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'start_date' => $_POST['start_date'],
						'finish_date' => $_POST['finish_date']
					));
				}
				if ($_GET['report_id'] == Reports::DOCUMENTS_REPORT) {
					Yii::app()->session['report'] = customFunctions::documentsReport($_POST['report_for'], $_POST['document_id']);
					Yii::app()->session['grid_columns'] = array();
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'report_for' => $_POST['report_for'],
						'document_id' => $_POST['document_id']
					));
				}
				if ($_GET['report_id'] == Reports::EVENTS_REPORT) {
					Yii::app()->session['report'] = customFunctions::eventsReport($_POST['report_for'], $_POST['event_id']);
					Yii::app()->session['grid_columns'] = array();
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'report_for' => $_POST['report_for'],
						'event_id' => $_POST['event_id']
					));
				}
				if ($_GET['report_id'] == Reports::ALLERGIES_REPORT) {
					Yii::app()->session['report'] = customFunctions::allergiesReport($_POST['room_id']);
					Yii::app()->session['grid_columns'] = array();
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'room_id' => $_POST['room_id']
					));
				}
				if ($_GET['report_id'] == Reports::MONTHLY_NURSERY_REPORT) {
					Yii::app()->session['report'] = customFunctions::monthlyNurseryReport($_POST['month'], $_POST['products'], $_POST['show_sums_only']);
					Yii::app()->session['grid_columns'] = array();
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'month' => $_POST['month'],
						'products' => $_POST['products'],
						'show_sums_only' => $_POST['show_sums_only']
					));
				}
				if ($_GET['report_id'] == Reports::PARENT_EMAILS_REPORT) {
//					Yii::app()->session['report'] = customFunctions::parentEmailsReport($_POST['branch_id']);
					Yii::app()->session['grid_columns'] = array();
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'branch_id' => $_POST['branch_id']
					));
				}
				if ($_GET['report_id'] == Reports::STAFF_EMAILS_REPORT) {
					Yii::app()->session['report'] = customFunctions::staffEmailsReport($_POST['branch_id']);
					Yii::app()->session['grid_columns'] = array();
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'branch_id' => $_POST['branch_id']
					));
				}
				if ($_GET['report_id'] == Reports::INVOICE_AMOUNT_REPORT) {
					Yii::app()->session['report'] = customFunctions::invoiceAmountReport($_POST['branch_id'], $_POST['year'], $_POST['month'], $_POST['include_manual_invoice'], $_POST['child_last_name_first']);
					Yii::app()->session['grid_columns'] = array();
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'branch_id' => $_POST['branch_id'],
						'year' => $_POST['year'],
						'month' => $_POST['month'],
						'include_manual_invoice' => $_POST['include_manual_invoice'],
						'child_last_name_first' => $_POST['child_last_name_first']
					));
				}
				if ($_GET['report_id'] == Reports::MINIMUM_WAGE_REPORT) {
					Yii::app()->session['report'] = customFunctions::minimumWageReport($_POST['branch_id'], $_POST['age_as_of']);
					Yii::app()->session['grid_columns'] = array();
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'branch_id' => $_POST['branch_id'],
						'age_as_of' => $_POST['age_as_of']
					));
				}
				if ($_GET['report_id'] == Reports::STAFF_HOLIDAYS_USED_BALANCE_REPORT) {
					Yii::app()->session['report'] = customFunctions::staffHolidaysUsedBalanceReport($_POST['branch_id'], $_POST['year']);
					Yii::app()->session['grid_columns'] = array();
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'branch_id' => $_POST['branch_id'],
						'year' => $_POST['year']
					));
				}
				if ($_GET['report_id'] == Reports::UNINVOICED_BOOKINGS_REPORT) {
					$data = customFunctions::uninvoicedSessionsReport($_POST['branch_id'], $_POST['year'], $_POST['month']);
					Yii::app()->session['report'] = $data['model'];
					Yii::app()->session['monthDates'] = $data['monthDates'];
					Yii::app()->session['grid_columns'] = array();
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'branch_id' => $_POST['branch_id'],
						'year' => $_POST['year'],
						'month' => $_POST['month']
					));
				}
				if ($_GET['report_id'] == Reports::EMERGENCY_CONTACTS_REPORTS) {
					Yii::app()->session['report'] = customFunctions::emergencyContactsReport($_POST['room_id']);
					Yii::app()->session['grid_columns'] = array();
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'room_id' => $_POST['room_id']
					));
				}
				if ($_GET['report_id'] == Reports::MEAL_DETAILS_REPORT) {
					Yii::app()->session['report'] = customFunctions::mealDetailsReport($_POST['start_date'], $_POST['finish_date'], $_POST['products'], $_POST['include_children_names']);
					Yii::app()->session['grid_columns'] = array();
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'start_date' => $_POST['start_date'],
						'finish_date' => $_POST['finish_date'],
						'products' => $_POST['products'],
						'include_children_names' => $_POST['include_children_names']
					));
				}
				if ($_GET['report_id'] == Reports::AVERAGE_WEEKLY_HOURS) {
					Yii::app()->session['report'] = customFunctions::averageWeeklyHoursReport($_POST['start_date'], $_POST['finish_date'], $_POST['include_children_names'], $_POST['divide_by_value']);
					Yii::app()->session['grid_columns'] = array();
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'start_date' => $_POST['start_date'],
						'finish_date' => $_POST['finish_date'],
						'include_children_names' => $_POST['include_children_names'],
						'divide_by_value' => $_POST['divide_by_value']
					));
				}
				if ($_GET['report_id'] == Reports::CHILD_NOT_SUITABLE_IN_ROOM) {
					Yii::app()->session['report'] = customFunctions::childNotSuitableInRoom($_POST['select_date']);
					Yii::app()->session['grid_columns'] = array();
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'select_date' => $_POST['select_date']
					));
				}
				if ($_GET['report_id'] == Reports::ENQUIRY_REPORT) {
					Yii::app()->session['report'] = customFunctions::enquiryReport($_POST['start_date'], $_POST['finish_date']);
					Yii::app()->session['grid_columns'] = array();
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'start_date' => $_POST['start_date'],
						'finish_date' => $_POST['finish_date']
					));
				}
                
                if($_GET['report_id'] == Reports::INCOME_FORECAST_REPORT){
                    Yii::app()->session['report'] = customFunctions::incomeForecastReport($_POST['start_date'], $_POST['finish_date'] , $_POST['income_forecast_type'],$_POST['detail_type']);
                    Yii::app()->session['grid_columns'] = array();
					$this->redirect(array(
						'reports/createStandardReport',
						'tab_name' => 'report',
						'report_id' => $report_id,
						'start_date' => $_POST['start_date'],
						'finish_date' => $_POST['finish_date'],
                        'income_forecast_type' => $_POST['income_forecast_type'],
                        'detail_type' => $_POST['detail_type']
					));
                }
			}


			if ($_GET['tab_name'] == 'report' && isset($_POST['Next'])) {
				$this->redirect(array(
					'reports/createStandardReport',
					'tab_name' => 'save-report',
					'report-params' => $_GET
				));
			}

			if (($_GET['tab_name'] == 'save-report') && (isset($_POST['Save']))) {
				$model->attributes = $_POST['Reports'];
				$this->performAjaxValidation($model);
				$model->branch_id = Yii::app()->session['branch_id'];
				$model->saved_by = Yii::app()->user->id;
				$model->data = CJSON::encode($_GET['report-params']);
				$model->is_standard_report = 1;
				$model->report_id = $_GET['report-params']['report_id'];
				if ($model->save()) {
					$this->redirect(array(
						'reports/index'
					));
				} else {
					Yii::app()->user->setFlash('error', "Their seems to be some problem saving the reports.Please fill all the mandatory fields.");
					$this->refresh();
				}
			}
			$this->render('createStandardReport', array(
				'reportModel' => $reportModel,
				'model' => $model
			));
		}
	}

	public function actionUpdateStandardReport($id) {
		$model = Reports::model()->findByPk($id);
		$data = CJSON::decode($model->data);
		if ($_GET['tab_name'] == 'filter-options' && isset($_POST['Next'])) {
			if ($_GET['report_id'] == 1) {
				Yii::app()->session['report'] = customFunctions::fundingTermTimeReport(Yii::app()->session['branch_id'], $_POST['term_id']);
				$grid_columns = array();
				if (!empty(Yii::app()->session['report'])) {
					foreach (Yii::app()->session['report'][0] as $key => $value) {
						if ($key == "Difference (Hours)") {
							$grid_columns[] = array(
								'name' => $key,
								'type' => 'raw',
								'cssClassExpression' => '($data["Difference (Hours)"] > 0) ? "reportBackgroundYellow" : ""'
							);
						} else {
							$grid_columns[] = array(
								'name' => $key
							);
						}
					}
				}
				Yii::app()->session['grid_columns'] = $grid_columns;
				$this->redirect(array(
					'reports/updateStandardReport',
					'id' => $id,
					'tab_name' => 'report',
					'report_id' => $_GET['report_id'],
					'term_id' => $_POST['term_id'],
					'year' => $_POST['year']
				));
			}

			if ($_GET['report_id'] == 2) {
				Yii::app()->session['report'] = customFunctions::fundingMonthlyReport($_POST['start_date'], $_POST['finish_date'], $_POST['branch_id']);
				$grid_columns = array();
				if (!empty(Yii::app()->session['report'])) {
					foreach (Yii::app()->session['report'][0] as $key => $value) {
						if ($key == "Difference (Hours)") {
							$grid_columns[] = array(
								'name' => $key,
								'type' => 'raw',
								'cssClassExpression' => '($data["Difference (Hours)"] > 0) ? "reportBackgroundYellow" : ""'
							);
						} else {
							$grid_columns[] = array(
								'name' => $key
							);
						}
					}
				}
				Yii::app()->session['grid_columns'] = $grid_columns;
				$this->redirect(array(
					'reports/updateStandardReport',
					'id' => $id,
					'tab_name' => 'report',
					'report_id' => $_GET['report_id'],
					'start_date' => $_POST['start_date'],
					'finish_date' => $_POST['finish_date'],
					'branch_id' => $_POST['branch_id']
				));
			}

			if ($_GET['report_id'] == Reports::TWO_THREE_YEAR_FUNDING_REPORT) {
				Yii::app()->session['report'] = customFunctions::funding2and3YearOldReport($_POST['start_date'], $_POST['finish_date'], $_POST['branch_id']);
				Yii::app()->session['grid_columns'] = array();
				$this->redirect(array(
					'reports/updateStandardReport',
					'id' => $id,
					'tab_name' => 'report',
					'report_id' => $_GET['report_id'],
					'start_date' => $_POST['start_date'],
					'finish_date' => $_POST['finish_date'],
					'branch_id' => $_POST['branch_id']
				));
			}

			if ($_GET['report_id'] == 5) {
				Yii::app()->session['report'] = customFunctions::staffHoursReport($_POST['month'], $_POST['week1_code'], $_POST['week2_code'], $_POST['week3_code'], $_POST['week4_code'], $_POST['week5_code'], $_POST['stepUp_code'], $_POST['exclude_salaried']);
				$grid_columns = array();
				if (!empty(Yii::app()->session['report'])) {
					foreach (Yii::app()->session['report'][0] as $key => $value) {
						$grid_columns[] = array(
							'name' => $key
						);
					}
				}
				Yii::app()->session['grid_columns'] = $grid_columns;
				$this->redirect(array(
					'reports/updateStandardReport',
					'id' => $id,
					'tab_name' => 'report',
					'report_id' => $_GET['report_id'],
					'month' => $_POST['month'],
					'week1_code' => $_POST['week1_code'],
					'week2_code' => $_POST['week2_code'],
					'week3_code' => $_POST['week3_code'],
					'week4_code' => $_POST['week4_code'],
					'week5_code' => $_POST['week5_code'],
					'stepUp_code' => $_POST['stepUp_code'],
					'exclude_salaried' => $_POST['exclude_salaried']
				));
			}

			if ($_GET['report_id'] == 6) {
				Yii::app()->session['report'] = customFunctions::getStaffWagesMonthlyReport($_POST['month']);
				$grid_columns = array();
				if (!empty(Yii::app()->session['report'])) {
					foreach (Yii::app()->session['report'][0] as $key => $value) {
						$grid_columns[] = array(
							'name' => $key
						);
					}
				}
				Yii::app()->session['grid_columns'] = $grid_columns;
				$this->redirect(array(
					'reports/updateStandardReport',
					'id' => $id,
					'tab_name' => 'report',
					'report_id' => $_GET['report_id'],
					'month' => $_POST['month']
				));
			}

			if ($_GET['report_id'] == 7) {
				Yii::app()->session['report'] = customFunctions::agedDebtorsReport($_POST['report_date']);
				$grid_columns = array();
				if (!empty(Yii::app()->session['report'])) {
					foreach (Yii::app()->session['report'][0] as $key => $value) {
						$grid_columns[] = array(
							'name' => $key
						);
					}
				}
				Yii::app()->session['grid_columns'] = $grid_columns;
				$this->redirect(array(
					'reports/updateStandardReport',
					'id' => $id,
					'tab_name' => 'report',
					'report_id' => $_GET['report_id'],
					'report_date' => $_POST['report_date']
				));
			}

			if ($_GET['report_id'] == Reports::STAFF_SCHEDULING_REPORT) {
				Yii::app()->session['report'] = customFunctions::getStaffHoursData($_POST['start_date'], $_POST['finish_date'], $_POST['holiday_type_reason'], $_POST['absence_only']);
				$grid_columns = array();
				Yii::app()->session['grid_columns'] = $grid_columns;
				$this->redirect(array(
					'reports/updateStandardReport',
					'id' => $id,
					'tab_name' => 'report',
					'report_id' => $_GET['report_id'],
					'start_date' => $_POST['start_date'],
					'finish_date' => $_POST['finish_date']
				));
			}
			if ($_GET['report_id'] == Reports::MONTHLY_DEBTORS) {
				Yii::app()->session['report'] = customFunctions::getMonthlyDebtors($_POST['month']);
				Yii::app()->session['grid_columns'] = array();
				$this->redirect(array(
					'reports/updateStandardReport',
					'id' => $id,
					'tab_name' => 'report',
					'report_id' => $_GET['report_id'],
					'month' => $_POST['month']
				));
			}
			if ($_GET['report_id'] == Reports::CHILDREN_WITHOUT_BOOKING) {
				Yii::app()->session['report'] = customFunctions::childrenWithoutBooking($_POST['start_date'], $_POST['finish_date']);
				Yii::app()->session['grid_columns'] = array();
				$this->redirect(array(
					'reports/updateStandardReport',
					'id' => $id,
					'tab_name' => 'report',
					'report_id' => $_GET['report_id'],
					'start_date' => $_POST['start_date'],
					'finish_date' => $_POST['finish_date']
				));
			}

			if ($_GET['report_id'] == Reports::PARENT_EMAILS_REPORT) {
				Yii::app()->session['report'] = customFunctions::parentEmailsReport($_POST['branch_id']);
				Yii::app()->session['grid_columns'] = array();
				$this->redirect(array(
					'reports/updateStandardReport',
					'id' => $id,
					'tab_name' => 'report',
					'report_id' => $_GET['report_id'],
					'branch_id' => $_POST['branch_id']
				));
			}

			if ($_GET['report_id'] == Reports::STAFF_EMAILS_REPORT) {
				Yii::app()->session['report'] = customFunctions::staffEmailsReport($_POST['branch_id']);
				Yii::app()->session['grid_columns'] = array();
				$this->redirect(array(
					'reports/updateStandardReport',
					'id' => $id,
					'tab_name' => 'report',
					'report_id' => $_GET['report_id'],
					'branch_id' => $_POST['branch_id']
				));
			}
		}

		if ($_GET['tab_name'] == 'report' && isset($_POST['Next'])) {
			$this->redirect(array(
				'reports/updateStandardReport',
				'id' => $id,
				'tab_name' => 'save',
				'report-params' => $_GET,
				'report_id' => $_GET['report_id']
			));
		}

		if (($_GET['tab_name'] == 'save-report') && (isset($_POST['Update']))) {
			$model->attributes = $_POST['Reports'];
			$this->performAjaxValidation($model);
			$model->branch_id = Yii::app()->session['branch_id'];
			$model->saved_by = Yii::app()->user->id;
			$model->data = CJSON::encode($_GET['report-params']);
			$model->is_standard_report = 1;
			$model->report_id = $_GET['report-params']['report_id'];
			if ($model->save()) {
				$this->redirect(array(
					'reports/index'
				));
			} else {
				Yii::app()->user->setFlash('error', "Their seems to be some problem saving the reports.Please fill all the mandatory fields.");
				$this->refresh();
			}
		}

		$this->render('updateStandardReport', array(
			'model' => $model,
			'data' => $data
		));
	}

	public function actionViewStandardReport($id) {
		$model = Reports::model()->findByPk($id);
		$data = CJSON::decode($model->data);
		if (!empty($model)) {
			switch ($model->report_id) {
				case 1:
					$report = customFunctions::fundingTermTimeReport(Yii::app()->session['branch_id'], $data['term_id']);
					$grid_columns = array();
					if (!empty($report)) {
						foreach ($report[0] as $key => $value) {
							if ($key == "Difference (Hours)") {
								$grid_columns[] = array(
									'name' => $key,
									'type' => 'raw',
									'cssClassExpression' => '($data["Difference (Hours)"] > 0) ? "reportBackgroundYellow" : ""'
								);
							} else {
								$grid_columns[] = array(
									'name' => $key
								);
							}
						}
					}
					$this->render('viewStandardReport', array(
						'report' => $report,
						'grid_columns' => $grid_columns,
						'model' => $model
					));
					break;
				case 2:
					$report = customFunctions::fundingMonthlyReport($data['start_date'], $data['finish_date']);
					$grid_columns = array();
					if (!empty($report)) {
						foreach ($report[0] as $key => $value) {
							if ($key == "Difference (Hours)") {
								$grid_columns[] = array(
									'name' => $key,
									'type' => 'raw',
									'cssClassExpression' => '($data["Difference (Hours)"] > 0) ? "reportBackgroundYellow" : ""'
								);
							} else {
								$grid_columns[] = array(
									'name' => $key
								);
							}
						}
					}
					$this->render('viewStandardReport', array(
						'report' => $report,
						'grid_columns' => $grid_columns,
						'model' => $model
					));
					break;
				case 3:
					$report = customFunctions::funding2and3YearOldReport($data['start_date'], $data['finish_date']);
					$grid_columns = array();
					if (!empty($report)) {
						foreach ($report[0] as $key => $value) {
							if ($key == "Difference (Hours)") {
								$grid_columns[] = array(
									'name' => $key,
									'type' => 'raw',
									'cssClassExpression' => '($data["Difference (Hours)"] > 0) ? "reportBackgroundYellow" : ""'
								);
							} else {
								$grid_columns[] = array(
									'name' => $key
								);
							}
						}
					}
					$this->render('viewStandardReport', array(
						'report' => $report,
						'grid_columns' => $grid_columns,
						'model' => $model
					));
					break;
				case 4:
					$report = customFunctions::staffWagesMonthlyPercentReport($data['start_date'], $data['finish_date']);
					$this->render('viewStandardReport', array(
						'report' => $report,
						'grid_columns' => $grid_columns,
						'model' => $model
					));
					break;
				case 5:
					$report = customFunctions::staffHoursReport($data['month'], $data['week1_code'], $data['week2_code'], $data['week3_code'], $data['week4_code'], $data['week5_code'], $data['stepUp_code'], $data['exclude_salaried']);
					$grid_columns = array();
					if (!empty($report)) {
						foreach ($report[0] as $key => $value) {
							$grid_columns[] = array(
								'name' => $key
							);
						}
					}
					$this->render('viewStandardReport', array(
						'report' => $report,
						'grid_columns' => $grid_columns,
						'model' => $model
					));
					break;

				case 6:
					$report = customFunctions::getStaffWagesMonthlyReport($data['month']);
					$grid_columns = array();
					if (!empty($report)) {
						foreach ($report[0] as $key => $value) {
							$grid_columns[] = array(
								'name' => $key
							);
						}
					}
					$this->render('viewStandardReport', array(
						'report' => $report,
						'grid_columns' => $grid_columns,
						'model' => $model
					));
					break;

				case 7:
					$report = customFunctions::agedDebtorsReport($data['report_date']);
					$grid_columns = array();
					if (!empty($report)) {
						foreach ($report[0] as $key => $value) {
							$grid_columns[] = array(
								'name' => $key
							);
						}
					}
					$this->render('viewStandardReport', array(
						'report' => $report,
						'grid_columns' => $grid_columns,
						'model' => $model
					));
					break;
			}
		} else {
			throw new CHttpException(404, "The requested page does not exists.");
		}
	}

	public function actionPdfStandardReport($id) {
		$model = Reports::model()->findByPk($id);
		$data = CJSON::decode($model->data);
		if (!empty($model)) {
			switch ($model->report_id) {
				case 1:
					$report = customFunctions::fundingTermTimeReport(Yii::app()->session['branch_id'], $data['term_id']);
					$grid_columns = array();
					if (!empty($report)) {
						foreach ($report[0] as $key => $value) {
							$grid_columns[] = $key;
						}
					}
					break;
				case 2:
					$report = customFunctions::fundingMonthlyReport($data['start_date'], $data['finish_date']);
					$grid_columns = array();
					if (!empty($report)) {
						foreach ($report[0] as $key => $value) {
							$grid_columns[] = $key;
						}
					}
					break;
				case 3:
					$report = customFunctions::funding2and3YearOldReport($data['start_date'], $data['finish_date']);
					$grid_columns = array();
					if (!empty($report)) {
						foreach ($report[0] as $key => $value) {
							$grid_columns[] = $key;
						}
					}
					break;
				case 5:
					$report = customFunctions::staffHoursReport($data['month'], $data['week1_code'], $data['week2_code'], $data['week3_code'], $data['week4_code'], $data['week5_code'], $data['stepUp_code'], $data['exclude_salaried']);
					$grid_columns = array();
					if (!empty($report)) {
						foreach ($report[0] as $key => $value) {
							$grid_columns[] = $key;
						}
					}
					break;
				case 7:
					$report = customFunctions::agedDebtorsReport($data['report_date']);
					$grid_columns = array();
					if (!empty($report)) {
						foreach ($report[0] as $key => $value) {
							$grid_columns[] = $key;
						}
					}
					break;
				case 6:
					$report = customFunctions::getStaffWagesMonthlyReport($data['month']);
					$grid_columns = array();
					if (!empty($report)) {
						foreach ($report[0] as $key => $value) {
							$grid_columns[] = $key;
						}
					}
					break;
				case 8:
					$report = customFunctions::getStaffHoursData($data['start_date'], $data['finish_date'], $_POST['holiday_type_reason'], $_POST['absence_only']);
					$mpdf = new mPDF('A6', '', 0, '', 2.5, 2.5, 10, 10, 2.5, 2.5, 'L');
					$mpdf->WriteHTML($this->renderPartial('staffSchedulingReport', array(
							'report' => $report
							), TRUE));
					$mpdf->Output('Report.pdf', "D");
					exit();
					break;
			}
			$mpdf = new mPDF('A6', '', 0, '', 2.5, 2.5, 10, 10, 2.5, 2.5, 'L');
			$mpdf->WriteHTML($this->renderPartial('pdfStandardReport', array(
					'report' => $report,
					'grid_columns' => $grid_columns
					), TRUE));
			$mpdf->Output('Report.pdf', "D");
			exit();
		} else {
			throw new CHttpException(404, "The requested page does not exists.");
		}
	}

	public function actionResultPdfStandardReport($id) {
		ini_set("memory_limit", "-1");
		ini_set('max_execution_time' , -1);
		switch ($id) {
			case 2:
				$report = customFunctions::fundingMonthlyReport($_GET['report_params']['start_date'], $_GET['report_params']['finish_date']);
				$grid_columns = array();
				if (!empty($report)) {
					foreach ($report[0] as $key => $value) {
						$grid_columns[] = $key;
					}
				}
				break;
			case Reports::TWO_THREE_YEAR_FUNDING_REPORT:
				$report = customFunctions::funding2and3YearOldReport($_GET['report_params']['start_date'], $_GET['report_params']['finish_date'], $_GET['report_params']['branch_id']);
				$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'P');
				$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport3', array(
						'report' => $report
						), TRUE));
				$mpdf->Output('TWO_THREE_YEAR_FUNDING_REPORT.pdf', "D");
				exit();
				break;
			case Reports::STAFF_WAGES_WEEKLY:
				$report = customFunctions::staffWagesMonthlyPercentReport($_GET['report_params']['start_date'], $_GET['report_params']['finish_date']);
				$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'P');
				$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport4', array(
						'report' => $report
						), TRUE));
				$mpdf->Output('Staff_Wages_Weekly.pdf', "D");
				exit();
				break;
			case Reports::STAFF_HOURS_REPORT:
//				$report = customFunctions::staffHoursReport($_GET['report_params']['month'], $_GET['report_params']['week1_code'], $_GET['report_params']['week2_code'], $_GET['report_params']['week3_code'], $_GET['report_params']['week4_code'], $_GET['report_params']['week5_code'], $_GET['report_params']['stepUp_code'], $_GET['report_params']['exclude_salaried']);
				$report = Yii::app()->session['staffHoursReport'];
				$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'P');
				$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport5', array(
						'report' => $report
						), TRUE));
				$mpdf->Output('Staff_Hours_Report.pdf', "D");
				exit();
				break;
			case 6:
				$report = customFunctions::getStaffWagesMonthlyReport($_GET['report_params']['month']);
				$grid_columns = array();
				if (!empty($report)) {
					foreach ($report[0] as $key => $value) {
						$grid_columns[] = $key;
					}
				}
				break;

			case 7:
				$report = customFunctions::agedDebtorsReport($_GET['report_params']['report_date']);
				$grid_columns = array();
				if (!empty($report)) {
					foreach ($report[0] as $key => $value) {
						$grid_columns[] = $key;
					}
				}
				break;

			case Reports::STAFF_SCHEDULING_REPORT:
				$report = Yii::app()->session['getStaffHoursData'];
				$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'P');
				$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport8', array(
						'report' => $report
						), TRUE));
				$mpdf->Output('Staff_scheduling_report.pdf', "D");
				exit();
				break;
			case Reports::MONTHLY_DEBTORS:
//                $report = customFunctions::getMonthlyDebtors($_GET['report_params']['month']);
				$report = Yii::app()->session['getMonthlyDebtors'];
				$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'P');
				$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport9', array(
						'report' => $report
						), TRUE));
				$mpdf->Output('Monthly_debtors_report.pdf', "D");
				exit();
				break;
			case Reports::CHILDREN_WITHOUT_BOOKING:
				$report = customFunctions::childrenWithoutBooking($_GET['report_params']['start_date'], $_GET['report_params']['finish_date']);
				$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'P');
				$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport10', array(
						'report' => $report
						), TRUE));
				$mpdf->Output('Children_withour_booking.pdf', "D");
				exit();
				break;
			case Reports::CHILD_NAPPY_RECORD:
				$reportData = customFunctions::childNappyRecord($_GET['report_params']['room_id'], $_GET['report_params']['start_date'], $_GET['report_params']['finish_date']);
				$roomName = Room::model()->findByPk($_GET['report_params']['room_id'])->name;
				$mpdf = new mPDF('utf-8', 'A4-L', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'L');
				foreach ($reportData as $key => $report) {
					if (!empty($report)) {
						$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport11', array(
								'report' => $report,
								'emptyPage' => FALSE,
								'date' => $key,
								'roomName' => $roomName
								), TRUE));
						$mpdf->AddPage('L');
						$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport11', array(
								'report' => array(),
								'emptyPage' => TRUE,
								'date' => $key,
								'roomName' => $roomName
								), TRUE));
						$mpdf->AddPage('L');
					} else {
						$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport11', array(
								'report' => array(),
								'emptyPage' => TRUE,
								'date' => $key,
								'roomName' => $roomName
								), TRUE));
						$mpdf->AddPage('L');
					}
				}
				$mpdf->Output('Children_nappy_record.pdf', "D");
				exit();
				break;
			case Reports::CHILD_SLEEP_RECORD:
				$reportData = customFunctions::childSleepRecord($_GET['report_params']['room_id'], $_GET['report_params']['start_date'], $_GET['report_params']['finish_date']);
				$roomName = Room::model()->findByPk($_GET['report_params']['room_id'])->name;
				$mpdf = new mPDF('utf-8', 'A4-L', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'L');
				foreach ($reportData as $key => $report) {
					if (!empty($report)) {
						$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport12', array(
								'report' => $report,
								'date' => $key,
								'roomName' => $roomName
								), TRUE));
						$mpdf->AddPage('L');
					} else {
						$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport12', array(
								'report' => array(),
								'date' => $key,
								'roomName' => $roomName
								), TRUE));
						$mpdf->AddPage('L');
					}
				}
				$mpdf->Output('Child_sleep_record.pdf', "D");
				exit();
				break;
			case Reports::STAFF_SIGN_IN_OUT_REPORT:
				$reportData = customFunctions::staffSignInOutReport($_GET['report_params']['start_date'], $_GET['report_params']['finish_date']);
				$roomName = Room::model()->findByPk($_GET['report_params']['room_id'])->name;
				$mpdf = new mPDF('utf-8', 'A4-L', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'L');
				foreach ($reportData as $key => $report) {
					if (!empty($report)) {
						$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport13', array(
								'report' => $report,
								'date' => $key
								), TRUE));
						$mpdf->AddPage('L');
					} else {
						$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport13', array(
								'report' => array(),
								'date' => $key
								), TRUE));
						$mpdf->AddPage('L');
					}
				}
				$mpdf->Output('Staff_sign_in_out_report.pdf', "D");
				exit();
				break;
			case Reports::CHILD_REGISTER_REPORT:
				$roomModel = Room::model()->findByPk($_GET['report_params']['room_id']);
				$days = customFunctions::getDatesOfDays($_GET['report_params']['start_date'], $_GET['report_params']['finish_date'], explode(",", $roomModel->branch->nursery_operation_days));
				$report = customFunctions::childRegisterReport($_GET['report_params']['room_id'], $_GET['report_params']['start_date'], $_GET['report_params']['finish_date']);
				$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'P');
				foreach ($days as $key => $date) {
					$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport14', array(
							'report' => $report,
							'type' => 0,
							'date' => $date
							), TRUE));
					$mpdf->AddPage('P');
					$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport14', array(
							'report' => $report,
							'type' => 1,
							'date' => $date
							), TRUE));
					$mpdf->AddPage('P');
				}
				$mpdf->Output('Child_Register_' . $roomModel->name . '.pdf', "D");
				exit();
				break;
			case Reports::CHILD_SUNCREAM_REPORT:
				$reportData = customFunctions::childSuncreamReport($_GET['report_params']['room_id'], $_GET['report_params']['start_date'], $_GET['report_params']['finish_date']);
				$roomName = Room::model()->findByPk($_GET['report_params']['room_id'])->name;
				$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'P');
				foreach ($reportData as $key => $report) {
					$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport15', array(
							'report' => $report,
							'date' => $key,
							'roomName' => $roomName
							), TRUE));
					$mpdf->AddPage('P');
				}
				$mpdf->Output('Child_sun_cream_report.pdf', "D");
				exit();
				break;
			case Reports::PAYMENT_REPORT:
				$report = customFunctions::childPaymentReport($_GET['report_params']['start_date'], $_GET['report_params']['finish_date']);
				$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'P');
				$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport16', array(
						'report' => $report
						), TRUE));
				$mpdf->Output('Payment_report.pdf', "D");
				exit();
				break;
			case Reports::DOCUMENTS_REPORT:
				$report = customFunctions::documentsReport($_GET['report_params']['report_for'], $_GET['report_params']['document_id']);
				$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'P');
				$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport17', array(
						'report' => $report,
						'report_for' => $_GET['report_params']['report_for']
						), TRUE));
				$mpdf->Output('Documents_Report.pdf', "D");
				exit();
				break;
			case Reports::EVENTS_REPORT:
				$report = customFunctions::eventsReport($_GET['report_params']['report_for'], $_GET['report_params']['event_id']);
				$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'P');
				$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport18', array(
						'report' => $report,
						'report_for' => $_GET['report_params']['report_for']
						), TRUE));
				$mpdf->Output('Events_report.pdf', "D");
				exit();
				break;
			case Reports::ALLERGIES_REPORT:
				$report = customFunctions::allergiesReport($_GET['report_params']['room_id']);
				$mpdf = new mPDF('utf-8', 'A4-L', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'L');
				$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport19', array(
						'report' => $report,
						'report_for' => $_GET['report_params']['room_id']
						), TRUE));
				$mpdf->Output('Allergies_Report.pdf', "D");
				exit();
				break;
			case Reports::MONTHLY_NURSERY_REPORT:
				$report = customFunctions::monthlyNurseryReport($_GET['report_params']['month'], $_GET['report_params']['products'], $_GET['report_params']['show_sums_only']);
				$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'P');
				$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport20', array(
						'report' => $report,
						'month' => $_GET['report_params']['month'],
						'products' => $_GET['report_params']['products'],
						'show_sums_only' => $_GET['report_params']['show_sums_only']
						), TRUE));
				$mpdf->Output('Monthly_nursery_report.pdf', "D");
				exit();
				break;
			case Reports::PARENT_EMAILS_REPORT:
				ini_set("memory_limit", -1);
				ini_set("max_execution_time", -1);
				$report = customFunctions::parentEmailsReport($_GET['report_params']['branch_id']);
				$mpdf = new mPDF('utf-8', 'A4-L', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'L');
				$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport21', array(
						'report' => $report,
						'report_for' => $_GET['report_params']['branch_id']
						), TRUE));
				$mpdf->Output('Parent_Emails.pdf', "D");
				exit();
				break;
			case Reports::STAFF_EMAILS_REPORT:
				$report = customFunctions::staffEmailsReport($_GET['report_params']['branch_id']);
				$mpdf = new mPDF('utf-8', 'A4-L', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'L');
				$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport22', array(
						'report' => $report,
						'report_for' => $_GET['report_params']['branch_id']
						), TRUE));
				$mpdf->Output('Staff_Emails.pdf', "D");
				exit();
				break;
			case Reports::INVOICE_AMOUNT_REPORT:
				$report = customFunctions::invoiceAmountReport($_GET['report_params']['branch_id'], $_GET['report_params']['year'], $_GET['report_params']['month'], $_GET['report_params']['include_manual_invoice'], $_GET['report_params']['child_last_name_first']);
				$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'P');
				$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport23', array(
						'report' => $report,
						'branch_id' => $_GET['report_params']['branch_id'],
						'year' => $_GET['report_params']['year'],
						'month' => $_GET['report_params']['month'],
						'include_manual_invoice' => $_GET['report_params']['include_manual_invoice'],
						'child_last_name_first' => $_GET['report_params']['child_last_name_first']
						), TRUE));
				$mpdf->Output('Monthly_Nursery_Invoice_Report.pdf', "D");
				exit();
				break;
			case Reports::MINIMUM_WAGE_REPORT:
				$report = customFunctions::minimumWageReport($_GET['report_params']['branch_id'], $_GET['report_params']['age_as_of']);
				$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'P');
				$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport24', array(
						'report' => $report,
						'branch_id' => $_GET['report_params']['branch_id'],
						'age_as_of' => $_GET['report_params']['age_as_of']
						), TRUE));
				$mpdf->Output('Minimum_wage_report.pdf', "D");
				exit();
				break;
			case Reports::STAFF_HOLIDAYS_USED_BALANCE_REPORT:
				$report = customFunctions::staffHolidaysUsedBalanceReport($_GET['report_params']['branch_id'], $_GET['report_params']['year']);
				$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'P');
				$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport25', array(
						'report' => $report,
						'branch_id' => $_GET['report_params']['branch_id']
						), TRUE));
				$mpdf->Output('Staff_holiday_used_balance_report.pdf', "D");
				exit();
				break;
			case Reports::TERM_TIME_FUNDING_REPORT:
				$report = customFunctions::fundingTermTimeReport(Branch::currentBranch()->id, $_GET['report_params']['term_id']);
				$mpdf = new mPDF('utf-8', 'A4-L', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'L');
				$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport1', array(
						'report' => $report,
						'branch_id' => $_GET['report_params']['branch_id'],
						'term_id' => $_GET['report_params']['term_id']
						), TRUE));
				$mpdf->Output('Term_time_funding_report.pdf', "D");
				exit();
				break;
			case Reports::EMERGENCY_CONTACTS_REPORTS:
				$report = customFunctions::emergencyContactsReport($_GET['report_params']['room_id']);
				$mpdf = new mPDF('utf-8', 'A4-L', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'L');
				$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport27', array(
						'report' => $report
						), TRUE));
				$mpdf->Output('Emergency_contact_report.pdf', "D");
				exit();
				break;
			case Reports::MEAL_DETAILS_REPORT:
				$report = customFunctions::mealDetailsReport($_GET['report_params']['start_date'], $_GET['report_params']['finish_date'], $_GET['report_params']['products'], $_GET['report_params']['include_children_names']);
				$mpdf = new mPDF('utf-8', 'A4-L', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'L');
				$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport28', array(
						'report' => $report
						), TRUE));
				$mpdf->Output('Meal_numbers.pdf', "D");
				exit();
				break;
			case Reports::AVERAGE_WEEKLY_HOURS:
				$report = Yii::app()->session['averageWeeklyHoursReport'];
				$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'P');
				$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport29', array(
						'report' => $report
						), TRUE));
				$mpdf->Output('AVERAGE_WEEKLY_HOUR_REPORT.pdf', "D");
				exit();
				break;
			case Reports::CHILD_NOT_SUITABLE_IN_ROOM:
				$report = Yii::app()->session['childNotSuitableInRoom'];
				$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'P');
				$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport30', array(
						'report' => $report
						), TRUE));
				$mpdf->Output('CHILD_NOT_SUITABLE_IN_ROOM.pdf', "D");
				exit();
				break;
              
            case Reports::INCOME_FORECAST_REPORT:
				$report = Yii::app()->session['incomeForecastReport'];
				$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'P');
				$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport32', array(
						'report' => $report
						), TRUE));
				$mpdf->Output('INCOME_FORECAST_REPORT.pdf', "D");
				exit();
				break;
		}
		$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'P');
		$mpdf->WriteHTML($this->renderPartial('pdfStandardReport', array(
				'report' => $report,
				'grid_columns' => $grid_columns
				), TRUE));
		$mpdf->Output('Report.pdf', "D");
		exit();
	}

	public function actionCsvStandardReport($id) {
		$model = Reports::model()->findByPk($id);
		$data = CJSON::decode($model->data);
		if (!empty($model)) {
			switch ($model->report_id) {
				case 1:
					$report = customFunctions::fundingTermTimeReport(Yii::app()->session['branch_id'], $data['term_id']);
					$grid_columns = array();
					if (!empty($report)) {
						foreach ($report[0] as $key => $value) {
							$grid_columns[] = $key;
						}
					}
					break;
				case 2:
					$report = customFunctions::fundingMonthlyReport($data['start_date'], $data['finish_date']);
					$grid_columns = array();
					if (!empty($report)) {
						foreach ($report[0] as $key => $value) {
							$grid_columns[] = $key;
						}
					}
					break;
				case Reports::TWO_THREE_YEAR_FUNDING_REPORT:
					$report = customFunctions::funding2and3YearOldReport($data['start_date'], $data['finish_date']);
					$grid_columns = array();
					if (!empty($report)) {
						foreach ($report[0] as $key => $value) {
							$grid_columns[] = $key;
						}
					}
					break;
				case 5:
					$report = customFunctions::staffHoursReport($data['month'], $data['week1_code'], $data['week2_code'], $data['week3_code'], $data['week4_code'], $data['week5_code'], $data['stepUp_code'], $data['exclude_salaried']);
					$grid_columns = array();
					if (!empty($report)) {
						foreach ($report[0] as $key => $value) {
							$grid_columns[] = $key;
						}
					}
					break;
				case 7:
					$report = customFunctions::agedDebtorsReport($data['report_date']);
					$grid_columns = array();
					if (!empty($report)) {
						foreach ($report[0] as $key => $value) {
							$grid_columns[] = $key;
						}
					}
					break;
				case 6:
					$report = customFunctions::getStaffWagesMonthlyReport($data['month']);
					$grid_columns = array();
					if (!empty($report)) {
						foreach ($report[0] as $key => $value) {
							$grid_columns[] = $key;
						}
					}
					break;
				case 8:
					$report = customFunctions::getStaffHoursData($data['start_date'], $data['finish_date']);
					$grid_columns = array();
					if (!empty($report)) {
						foreach ($report[0] as $key => $value) {
							$grid_columns[] = $key;
						}
					}
					break;
			}
			$csv = new ECSVExport($report);
			$output = $csv->toCSV();
			Yii::app()->getRequest()->sendFile('report.csv', $output, "text/csv", false);
			exit();
		} else {
			throw new CHttpException(404, "The requested page does not exists.");
		}
	}

	public function actionResultCsvStandardReport($id) {
		switch ($id) {
			case 1:
				$report = customFunctions::fundingTermTimeReport(Yii::app()->session['branch_id'], $_GET['report_params']['term_id']);
				$grid_columns = array();
				if (!empty($report)) {
					foreach ($report[0] as $key => $value) {
						$grid_columns[] = $key;
					}
				}
				break;
			case 2:
				$report = customFunctions::fundingMonthlyReport($_GET['report_params']['start_date'], $_GET['report_params']['finish_date']);
				$grid_columns = array();
				if (!empty($report)) {
					foreach ($report[0] as $key => $value) {
						$grid_columns[] = $key;
					}
				}
				break;
			case Reports::TWO_THREE_YEAR_FUNDING_REPORT:
				$report = customFunctions::funding2and3YearOldReport($_GET['report_params']['start_date'], $_GET['report_params']['finish_date'], $_GET['report_params']['branch_id']);
				$grid_columns = array();
				if (!empty($report)) {
					foreach ($report[0] as $key => $value) {
						$grid_columns[] = $key;
					}
				}
				break;
			case Reports::STAFF_HOURS_REPORT:
//				$report = customFunctions::staffHoursReport($_GET['report_params']['month'], $_GET['report_params']['week1_code'], $_GET['report_params']['week2_code'], $_GET['report_params']['week3_code'], $_GET['report_params']['week4_code'], $_GET['report_params']['week5_code'], $_GET['report_params']['stepUp_code'], $_GET['report_params']['exclude_salaried']);
				$report = Yii::app()->session['staffHoursReport'];
				$grid_columns = array();
				if (!empty($report)) {
					foreach ($report[0] as $key => $value) {
						$grid_columns[] = $key;
					}
				}
				break;

			case 6:
				$report = customFunctions::getStaffWagesMonthlyReport($_GET['report_params']['month']);
				$grid_columns = array();
				if (!empty($report)) {
					foreach ($report[0] as $key => $value) {
						$grid_columns[] = $key;
					}
				}
				break;

			case 7:
				$report = customFunctions::agedDebtorsReport($_GET['report_params']['report_date']);
				$grid_columns = array();
				if (!empty($report)) {
					foreach ($report[0] as $key => $value) {
						$grid_columns[] = $key;
					}
				}
				break;
			case Reports::STAFF_SCHEDULING_REPORT:
				$reportData = Yii::app()->session['getStaffHoursData'];
				$report = array();
				if ($_GET['report_params']['group_by'] == "Week") {
					if ($_GET['report_params']['minimum_data'] == 0) :
						foreach ($reportData as $key => $value) {
							$report[] = array(
								$value['branch'],
								"Week Commencing " . date('d-M-Y', strtotime($key)),
								"",
								"",
								""
							);
							$report[] = array();
							foreach ($value['staff'] as $key2 => $value2) {
								if (!empty($value2['booking_data'])) :
									$report[] = array(
										$value2['staff_name'],
										$value2['position'],
										"",
										"",
										""
									);
									foreach ($value2['booking_data'] as $key3 => $value3) {
										$report[] = array(
											$value3['day'],
											$value3['time'],
											$value3['room'],
											$value3['activity'],
											$value3['notes'],
										);
									}
									$report[] = array(
										"Contracted Hours : " . $value2['contracted_hours'],
										"Total Hours: " . $value2['total_hours'],
										"Paid Hours: " . $value2['paid_hours'],
										"Unpaid Hours: " . $value2['unpaid_hours'],
										"StepUp Hours: " . $value2['step_up_hours']
									);
									$report[] = array();
								endif;
							}
						}
					endif;

					if ($_GET['report_params']['minimum_data'] == 1) :
						foreach ($reportData as $key => $value) {
							$report[] = array(
								$value['branch'],
								"Week Commencing " . date('d-M-Y', strtotime($key)),
								"",
								"",
								""
							);
							$report[] = array();
							foreach ($value['staff'] as $key2 => $value2) {
								if (!empty($value2['booking_data'])) :
									$notes = array();
									foreach ($value2['booking_data'] as $key3 => $value3) {
										if (!empty($value3['notes'])) {
											$notes[] = $value3['notes'];
										}
									}
									if (!empty($value2['holiday'])) {
										$report[] = array(
											$value2['staff_name'],
											$value2['position'],
											"Sickness : " . implode(", ", $value2['holiday']) . "; Notes : " . implode(", ", $notes),
											"",
											""
										);
									} else {
										$report[] = array(
											$value2['staff_name'],
											$value2['position'],
											"Notes : " . implode(", ", $notes),
											"",
											""
										);
									}
									$report[] = array(
										"Contracted Hours : " . $value2['contracted_hours'],
										"Total Hours: " . $value2['total_hours'],
										"Paid Hours: " . $value2['paid_hours'],
										"Unpaid Hours: " . $value2['unpaid_hours'],
										"StepUp Hours: " . $value2['step_up_hours']
									);
									$report[] = array();


								endif;
							}
						}
					endif;
				}

				if ($_GET['report_params']['group_by'] == "Staff") {
					if ($_GET['report_params']['minimum_data'] == 0) :
						if (!empty($reportData)) {
							foreach ($reportData['staff'] as $key2 => $value2) {
								if (!empty($value2['booking_data'])) {
									$report[] = array(
										$value2['staff_name'],
										$value2['position'],
										"",
										"",
										""
									);
									$report[] = array("", "", "", "", "");
									foreach ($value2['booking_data'] as $key3 => $value3) {
										$report[] = array(
											$reportData['branch'],
											"Week Commencing " . date('d-M-Y', strtotime($key3)),
											"",
											"",
											""
										);
										$report[] = array("", "", "", "", "");
										if (!empty($value3['booking_data'])) {
											foreach ($value3['booking_data'] as $key4 => $value4) {
												$report[] = array(
													$value4['day'],
													$value4['time'],
													$value4['room'],
													$value4['activity'],
													$value4['notes']
												);
											}
										}
										$report[] = array(
											"Contracted Hours : " . $value3['contracted_hours'],
											"Total Hours: " . $value3['total_hours'],
											"Paid Hours: " . $value3['paid_hours'],
											"Unpaid Hours: " . $value3['unpaid_hours'],
											"StepUp Hours: " . $value3['step_up_hours']
										);
										$report[] = array("", "", "", "", "");
									}
									$report[] = array(
										"Total -  Contracted Hours : " . $value2['total_contracted_hours'],
										"Total - Total Hours: " . $value2['total_total_hours'],
										"Total - Paid Hours: " . $value2['total_paid_hours'],
										"Total - Unpaid Hours: " . $value2['total_unpaid_hours'],
										"Total - StepUp Hours: " . $value2['total_step_up_hours']
									);
								}
							}
						}
					endif;

					if ($_GET['report_params']['minimum_data'] == 1) :
						if (!empty($reportData)) {
							foreach ($reportData['staff'] as $key2 => $value2) {
								if (!empty($value2['booking_data'])) {
									$report[] = array(
										$value2['staff_name'],
										$value2['position'],
										"",
										"",
										""
									);
									$report[] = array("", "", "", "", "");
									foreach ($value2['booking_data'] as $key3 => $value3) {
										$isWeekConfirmed = true;
										$notes = array();
										if (!empty($value3['booking_data'])) {
											foreach ($value3['booking_data'] as $key4 => $value4) {
												if ($value4['is_confirmed'] == 0): $isWeekConfirmed = false;
												endif;
												if (!empty($value4['notes'])) {
													$notes[] = $value4['notes'];
												}
											}
										}
										$report[] = array(
											$reportData['branch'],
											"Week Commencing " . date('d-M-Y', strtotime($key3)),
											"Sickness : " . implode(", ", $value3['holiday']) . "; Notes : " . implode(", ", $notes),
											"",
											""
										);
										$report[] = array(
											"Contracted Hours : " . $value3['contracted_hours'],
											"Total Hours: " . $value3['total_hours'],
											"Paid Hours: " . $value3['paid_hours'],
											"Unpaid Hours: " . $value3['unpaid_hours'],
											"StepUp Hours: " . $value3['step_up_hours']
										);
										$report[] = array("", "", "", "", "");
									}
									$report[] = array(
										"Total -  Contracted Hours : " . $value2['total_contracted_hours'],
										"Total - Total Hours: " . $value2['total_total_hours'],
										"Total - Paid Hours: " . $value2['total_paid_hours'],
										"Total - Unpaid Hours: " . $value2['total_unpaid_hours'],
										"Total - StepUp Hours: " . $value2['total_step_up_hours']
									);
								}
							}
						}
					endif;
				}

				$csv = new ECSVExport($report);
				if ($_GET['report_params']['minimum_data'] == 0) {
					$csv->setHeaders(array(
						"Day",
						"Time",
						"Room",
						"Activity",
						"Notes"
					));
				} else {
					$csv->setHeaders(array(
						"Staff Name",
						"Position",
						"Notes",
						"",
						""
					));
				}
				$output = $csv->toCSV();
				Yii::app()->request->sendFile('Staff_scheduling_report.csv', $output, "text/csv", false);
				exit();
				break;
			case Reports::MONTHLY_DEBTORS:
//                $reportData = customFunctions::getMonthlyDebtors($_GET['report_params']['month']);
				$reportData = Yii::app()->session['getMonthlyDebtors'];
				$report = array();
				foreach ($reportData['data'] as $data) {
					$paymentTermHeader = array(
						"",
						"",
						"",
						"",
						$data["paymentTermName"],
						$data["owedBeforeSum"],
						$data["totalDueThisMonthSum"],
						$data["paymentsReceivedSum"],
						""
					);
					$report[] = $paymentTermHeader;
					foreach ($data['data'] as $key2 => $value2) {
						$report[] = $value2;
					}
				}
				$total = array(
					"",
					"",
					"Total",
					$reportData['depositAmountSumOfReport'],
					"",
					$reportData['owedBeforeSumOfReport'],
					$reportData['totalDueThisMonthSumForReport'],
					$reportData['paymentsReceivedSumForReport'],
					""
				);
				$report[] = $total;
				$report[] = array();
				$report[] = array();
				$report[] = array(
					"",
					"",
					"",
					"",
					"",
					"",
					"Total in credit:",
					"",
					sprintf("%0.2f", $reportData['totalCredit'])
				);
				$report[] = array(
					"",
					"",
					"",
					"",
					"",
					"",
					"Total Owing:",
					($reportData["totalDueThisMonthSumForReport"] <= 0) ? "N/A" : sprintf("%0.2f", (($reportData['totalOwed'] * 100) / $reportData["totalDueThisMonthSumForReport"])),
					sprintf("%0.2f", $reportData['totalOwed'])
				);
				$report[] = array(
					"",
					"",
					"",
					"",
					"",
					"",
					"Grand Total:",
					"",
					sprintf("%0.2f", ($reportData['totalCredit'] + $reportData['totalOwed']))
				);
				$csv = new ECSVExport($report);
				$csv->setHeaders($reportData['columns']);
				$output = $csv->toCSV();
				Yii::app()->request->sendFile('report.csv', $output, "text/csv", false);
				exit();
				break;
			case Reports::CHILDREN_WITHOUT_BOOKING:
				$report = customFunctions::childrenWithoutBooking($_GET['report_params']['start_date'], $_GET['report_params']['finish_date']);
				$grid_columns = array();
				if (!empty($report)) {
					foreach ($report[0] as $key => $value) {
						$grid_columns[] = $key;
					}
				}
				break;
			case Reports::PAYMENT_REPORT:
				$report = customFunctions::childPaymentReport($_GET['report_params']['start_date'], $_GET['report_params']['finish_date']);
				$reportData = array();
				foreach ($report as $key => $value) {
					if (isset($value['data'])) :
						foreach ($value['data'] as $payment) {
							$temp = array();
							$temp["Child Name"] = customFunctions::getChildNameForPayments($payment['child_id']);
							$temp["Date of Payment"] = $payment['date_of_payment'];
							$temp["Payment Reference"] = $payment['payment_reference'];
							$temp["Amount"] = $payment['amount'];
							$temp["Deposit"] = sprintf("%0.2f", $payment['deposit']);
							$temp["Status"] = ($payment['status'] == 1) ? "ALLOCATED" : "NOT ALLOCATED";
							$temp["Notes"] = $payment["notes"];
							$temp['Payment Mode'] = $key;
							$reportData[] = $temp;
						}
					endif;
				}
				$csv = new ECSVExport($reportData);
				$csv->setHeaders([
					"Child Name",
					"Date of Payment",
					"Payment Reference",
					"Amount",
					"Status",
					"Notes",
					"Payment Mode"
				]);
				$output = $csv->toCSV();
				Yii::app()->request->sendFile('Payment_Report.csv', $output, "text/csv", false);
				exit();
				break;
			case Reports::DOCUMENTS_REPORT:
				$report = customFunctions::documentsReport($_GET['report_params']['report_for'], $_GET['report_params']['document_id']);
				$reportData = array();
				foreach ($report as $model) {
					$temp = array();
					$temp['Name'] = ($_GET['report_params']['report_for'] == "for_staff") ? $model->staff->name : $model->child->name;
					$temp['Document'] = $model->document->name;
					$temp['Title Date 1 Name : Value'] = $model->document->title_date_1 . " : " . $model->title_date_1_value;
					$temp['Title Date 2 Name : Value'] = $model->document->title_date_2 . " : " . $model->title_date_2_value;
					$temp['Title Description Name : Value'] = $model->document->title_description . " : " . $model->title_description_value;
					$temp['Title Notes Name : Value'] = $model->document->title_notes . " : " . $model->title_notes_value;
					$reportData[] = $temp;
				}
				$csv = new ECSVExport($reportData);
				$csv->setHeaders([
					"Name",
					"Document",
					"Title Date 1 Name : Value",
					"Title Date 2 Name : Value",
					"Title Description Name : Value",
					"Title Notes Name : Value"
				]);
				$output = $csv->toCSV();
				Yii::app()->request->sendFile('Documents_Report.csv', $output, "text/csv", false);
				exit();
				break;
			case Reports::EVENTS_REPORT:
				$report = customFunctions::eventsReport($_GET['report_params']['report_for'], $_GET['report_params']['event_id']);
				$reportData = array();
				foreach ($report as $model) {
					$temp = array();
					$temp['Name'] = ($_GET['report_params']['report_for'] == "for_staff") ? $model->staff->name : $model->child->name;
					$temp['Event'] = $model->event->name;
					$temp['Title Date 1 Name : Value'] = $model->event->title_date_1 . " : " . $model->title_date_1_value;
					$temp['Title Date 2 Name : Value'] = $model->event->title_date_2 . " : " . $model->title_date_2_value;
					$temp['Title Description Name : Value'] = $model->event->title_description . " : " . $model->title_description_value;
					$temp['Title Notes Name : Value'] = $model->event->title_notes . " : " . $model->title_notes_value;
					$reportData[] = $temp;
				}
				$csv = new ECSVExport($reportData);
				$csv->setHeaders([
					"Name",
					"Event",
					"Title Date 1 Name : Value",
					"Title Date 2 Name : Value",
					"Title Description Name : Value",
					"Title Notes Name : Value"
				]);
				$output = $csv->toCSV();
				Yii::app()->request->sendFile('Events_Report.csv', $output, "text/csv", false);
				exit();
				break;
			case Reports::ALLERGIES_REPORT:
				$report = customFunctions::allergiesReport($_GET['report_params']['room_id'], $_GET['report_params']['room_id']);
				$reportData = array();
				foreach ($report as $model) {
					$temp = array();
					$temp['Child Name'] = $model->name;
					$temp['Room'] = $model->room->name;
					$temp['Dietary Requirements'] = $model->childGeneralDetails->dietary_requirements;
					$temp['Medical notes'] = $model->childMedicalDetails->medical_notes;
					$temp['Allergies'] = $model->childGeneralDetails->general_notes;
					$reportData[] = $temp;
				}
				$csv = new ECSVExport($reportData);
				$output = $csv->toCSV();
				Yii::app()->request->sendFile('Allergies_Report.csv', $output, "text/csv", false);
				exit();
				break;
			case Reports::PARENT_EMAILS_REPORT:
				ini_set("memory_limit", -1);
				ini_set("max_execution_time", -1);
				$report = customFunctions::parentEmailsReport($_GET['report_params']['branch_id']);
				$reportData = array();
				foreach ($report as $model) {
                                        $parentModel = $model->getOrderedParents();
					$temp = array();
					$temp['Child First Name'] = $model->first_name;
					$temp['Child Last Name'] = $model->last_name;
					$temp['Child URN'] = $model->child_urn;
					$temp['Branch/Nursery'] = $model->branch->name;
					$temp['Room'] = $model->room->name;
					$temp['Booking Type'] = $model->booking_type;
					$temp['Preferred Session'] = $model->prefferedSession->name;
					$temp['Parent First Name'] = (isset($parentModel[1]))? $parentModel[1]->first_name : '';
					$temp['Parent Last Name'] = (isset($parentModel[1]))? $parentModel[1]->last_name : '';
					$temp['Parent Email'] = (isset($parentModel[1]))? $parentModel[1]->email : '';
					$reportData[] = $temp;
					$temp = array();
					$temp['Child First Name'] = $model->first_name;
					$temp['Child Last Name'] = $model->last_name;
					$temp['Child URN'] = $model->child_urn;
					$temp['Branch/Nursery'] = $model->branch->name;
					$temp['Room'] = $model->room->name;
					$temp['Booking Type'] = $model->booking_type;
					$temp['Preferred Session'] = $model->prefferedSession->name;
					$temp['Parent First Name'] = (isset($parentModel[2]))? $parentModel[2]->first_name : '';;
					$temp['Parent Last Name'] = (isset($parentModel[2]))? $parentModel[2]->last_name : '';
					$temp['Parent Email'] = (isset($parentModel[2]))? $parentModel[2]->email : '';
					$reportData[] = $temp;
				}
				$csv = new ECSVExport($reportData);
				$output = $csv->toCSV();
				Yii::app()->request->sendFile('Parent_Emails.csv', $output, "text/csv", false);
				exit();
				break;
			case Reports::STAFF_EMAILS_REPORT:
				$report = customFunctions::staffEmailsReport($_GET['report_params']['branch_id']);
				$reportData = array();
				foreach ($report as $model) {
					$temp = array();
					$temp['First Name'] = $model->first_name;
					$temp['Last Name'] = $model->last_name;
					$temp['URN'] = $model->staff_urn;
					$temp['Email'] = $model->email_1;
					$temp['Branch'] = $model->branch->name;
					$temp['Room'] = $model->room->name;
					$reportData[] = $temp;
				}
				$csv = new ECSVExport($reportData);
				$output = $csv->toCSV();
				Yii::app()->request->sendFile('Staff_Emails.csv', $output, "text/csv", false);
				exit();
				break;
			case Reports::INVOICE_AMOUNT_REPORT:
				$report = customFunctions::invoiceAmountReport($_GET['report_params']['branch_id'], $_GET['report_params']['year'], $_GET['report_params']['month'], $_GET['report_params']['include_manual_invoice'], $_GET['report_params']['child_last_name_first']);
				$reportData = array();
				$total_invoice_amount = 0;
				foreach ($report['model'] as $model) {
					$temp = array();
					$temp['Branch Name'] = $model->branch->name;
					$temp['Child Name'] = ($report['child_last_name_first']) ? $model->childNds->childLastNameFirst : $model->childNds->name;
					$temp['Child URN'] = $model->childNds->child_urn;
					$temp['Child Room'] = $model->childNds->room->name;
					$temp['Date Of Birth'] = $model->childNds->dob;
					$temp['Age'] = customFunctions::ageInMonths($model->childNds->dob, $model->invoice_date);
					$temp['Invoice Number'] = $model->invoiceUrn;
					$temp['Invoice Date'] = $model->invoice_date;
					$temp['Parent 1 Email Sent'] = $model->getEmail1Status();
					$temp['Parent 2 Email Sent'] = $model->getEmail2Status();
					$temp['Status'] = customFunctions::getInvoiceStatus($model->status);
					$temp['Amount'] = $model->total;


					$reportData[] = $temp;
					$total_invoice_amount += $model->total;
				}
				foreach ($report['creditNotes'] as $model) {
					$temp = array();
					$temp['Branch Name'] = $model->branch->name;
					$temp['Child Name'] = ($report['child_last_name_first']) ? $model->childNds->childLastNameFirst : $model->childNds->name;
					$temp['Child URN'] = $model->childNds->child_urn;
					$temp['Child Room'] = $model->childNds->room->name;
					$temp['Date Of Birth'] = $model->childNds->dob;
					$temp['Age'] = customFunctions::ageInMonths($model->childNds->dob, $model->invoice_date);
					$temp['Invoice Number'] = $model->invoiceUrn;
					$temp['Invoice Date'] = $model->invoice_date;
					$temp['Parent 1 Email Sent'] = $model->getEmail1Status();
					$temp['Parent 2 Email Sent'] = $model->getEmail2Status();
					$temp['Status'] = customFunctions::getInvoiceStatus($model->status);
					$temp['Amount'] = $model->total;


					$reportData[] = $temp;
					$total_invoice_amount += $model->total;
				}
				$temp = array();
				$temp = array('', '', '', '', '', '', '', '', '', '', 'Total', $total_invoice_amount);
				$reportData[] = $temp;
				$csv = new ECSVExport($reportData);
				$output = $csv->toCSV();
				Yii::app()->request->sendFile('Invoice_Amount_Report.csv', $output, "text/csv", false);
				exit();
				break;
			case Reports::MONTHLY_NURSERY_REPORT:
				$report = customFunctions::monthlyNurseryReport($_GET['report_params']['month'], $_GET['report_params']['products'], $_GET['report_params']['show_sums_only']);
				$reportData = array();
				foreach ($report as $key => $value) {
					$temp = array();
					$temp["Name"] = $value['name'];
					$temp["Total"] = $value['total'];
					$reportData[] = $temp;
				}
				$csv = new ECSVExport($reportData);
				$output = $csv->toCSV();
				Yii::app()->request->sendFile('Montlhy_Nursery_Report.csv', $output, "text/csv", false);
				exit();
				break;
			case Reports::MINIMUM_WAGE_REPORT:
				$report = customFunctions::minimumWageReport($_GET['report_params']['branch_id'], $_GET['report_params']['age_as_of']);
				$reportData = array();
				if (!empty($report)) :
					foreach ($report as $key => $value) :
						foreach ($value['data'] as $key2 => $value2) :
							$reportData[] = array(
								'Staff Name' => "",
								'Email' => "",
								'Branch' => "",
								'Age' => $key2 . " - " . $value['branchName'],
								'Predicted Age' => "",
								'Pay Rate' => "",
								'Position' => ""
							);
							foreach ($value2 as $key3 => $value3) :
								$reportData[] = array(
									'Staff Name' => $value3['Staff Name'],
									'Email' => $value3['Email'],
									'Branch' => $value3['Branch'],
									'Age' => $value3['Age'],
									'Predicted Age' => $value3['Predicted Age'],
									'Pay Rate' => $value3['Pay Rate'],
									'Position' => $value3['Position']
								);
							endforeach
							;
						endforeach
						;
					endforeach
					;


				endif;
				$csv = new ECSVExport($reportData);
				$output = $csv->toCSV();
				Yii::app()->request->sendFile('Minimum_wages_report.csv', $output, "text/csv", false);
				exit();
				break;
			case Reports::STAFF_HOLIDAYS_USED_BALANCE_REPORT:
				$report = customFunctions::staffHolidaysUsedBalanceReport($_GET['report_params']['branch_id'], $_GET['report_params']['year']);
				$reportData = array();
				foreach ($report as $model) {
					$temp = array();
					$temp["Staff Name"] = $model->staffNds->name;
					$temp["Email Id"] = $model->staffNds->email_1;
					$temp["Job Role"] = $model->staffNds->position0->name;
					$temp["Site"] = $model->staffNds->branch->name;
					$temp["Contract Hours / Entitlement"] = $model->holiday_entitlement_per_year;
					$temp["Used"] = number_format($model->getUsed($model->id), 2, ".", " ");
					$temp["Balance"] = number_format(($model->holiday_entitlement_per_year - $model->getUsed($model->id)), 2, ".", " ");
					$reportData[] = $temp;
				}
				$csv = new ECSVExport($reportData);
				$output = $csv->toCSV();
				Yii::app()->request->sendFile('Montlhy_Nursery_Report.csv', $output, "text/csv", false);
				exit();
				break;
			case Reports::EMERGENCY_CONTACTS_REPORTS:
				$report = customFunctions::emergencyContactsReport($_GET['report_params']['room_id']);
				$reportData = array();
				foreach ($report as $child) {
                                        $parentModel = $child->getOrderedParents();
					$temp = array();
					$temp['Room'] = $child->room->name;
					$temp['Child Name'] = $child->name;
					$temp['Parent 1'] = (isset($parentModel[1]))? $parentModel[1]->Fullname : '';
					$temp['Parent 1 Work No.'] = (isset($parentModel[1]))? $parentModel[1]->work_phone : '';
					$temp['Parent 1 Mobile No.'] = (isset($parentModel[1]))? $parentModel[1]->mobile_phone : '';
					$temp['Parent 2'] = (isset($parentModel[2]))? $parentModel[2]->Fullname : '';
					$temp['Parent 2 Work No.'] = (isset($parentModel[2]))? $parentModel[2]->work_phone : '';
					$temp['Parent 2 Mobile No.'] = (isset($parentModel[2]))? $parentModel[2]->mobile_phone : '';
					$temp['Emergency Contact 1'] = (isset($parentModel[3]))? $parentModel[3]->Fullname : '';
					$temp['Emergency Contact 1 No.'] = (isset($parentModel[3]))? $parentModel[3]->mobile_phone : '';
					$temp['Emergency Contact 2'] = (isset($parentModel[4]))? $parentModel[4]->Fullname : '';
					$temp['Emergency Contact 2 No.'] = (isset($parentModel[4]))? $parentModel[4]->mobile_phone : '';
					$reportData[] = $temp;
				}
				$csv = new ECSVExport($reportData);
				$output = $csv->toCSV();
				Yii::app()->request->sendFile('Emergency_contacts_report.csv', $output, "text/csv", false);
				exit();
				break;

			case Reports::AVERAGE_WEEKLY_HOURS:
				$report = Yii::app()->session['averageWeeklyHoursReport'];
				$reportData = array();
				if (!empty($report)) {
					foreach ($report as $key => $value) {
						$reportData[] = array(
							"Room Name",
							$value['room_name'],
							"",
							"",
							"Weekly Room Hour",
							$value['room_weekly_hour_45']
						);
						$reportData[] = array(
							"",
							"",
							"",
							"",
							"",
							""
						);
						$reportData[] = array(
							"Name",
							"Room",
							"Subsidy",
							"Not Subsidy",
							"Weekly Hours",
							"Weekly Spend Time"
						);
						if (!empty($value['booking_detail'])) {
							foreach ($value['booking_detail'] as $rkey => $rvalue) {
								$temp = array();
								$temp['Name'] = $rvalue['name'];
								$temp['Room'] = $rvalue['room'];
								$temp['Subsidy'] = $rvalue['subsidy'];
								$temp['Not Subsidy'] = $rvalue['not_subsidy'];
								$temp['Weekly Hours'] = $rvalue['weekly_hours'];
								$temp['Weekly Spend Time'] = $rvalue['weekly_45'];
								$reportData[] = $temp;
							}
						}


						$reportData[] = array(
							"Subsidy Hours",
							$value['subsidy_hour'],
							"",
							"",
							"Non Subsidy Hours",
							$value['non_subsidy_hour']
						);
						$reportData[] = array(
							"",
							"",
							"",
							"",
							"",
							""
						);
					}
				}


				$csv = new ECSVExport($reportData);
				$csv->setHeaders([
					"",
					"",
					"",
					"",
					"",
					""
				]);
				$output = $csv->toCSV();
				Yii::app()->request->sendFile('WEEKLY_HOURS_Report.csv', $output, "text/csv", false);
				exit();
				break;
			case Reports::CHILD_NOT_SUITABLE_IN_ROOM:
				$report = Yii::app()->session['childNotSuitableInRoom'];
				$reportData = array();
				if (!empty($report)) {
					foreach ($report as $key => $value) {
						$reportData[] = array(
							"Room Name",
							$value['room_name'],
							"Lower Age Group",
							$value['age_group_lower'],
							"Upper Age Group",
							$value['age_group_upper']
						);
						$reportData[] = array(
							"",
							"",
							"",
							"",
							"",
							""
						);
						$reportData[] = array(
							"Name",
							"Age ( In Months )",
							'Suitable Room'
						);
						if (!empty($value['child_data'])) {
							foreach ($value['child_data'] as $rkey => $rvalue) {
								$temp = array();
								$temp['Name'] = $rvalue['name'];
								$temp['Age'] = $rvalue['age'];
								$temp['Suitable Room'] = $rvalue['suitable_room'];
								$reportData[] = $temp;
							}
						}

						$reportData[] = array(
							"",
							"",
							"",
							"",
							"",
							""
						);
					}
				}


				$csv = new ECSVExport($reportData);
				$csv->setHeaders([
					"",
					"",
					"",
					"",
					"",
					""
				]);
				$output = $csv->toCSV();
				Yii::app()->request->sendFile('CHILD_NOT_SUITABLE_IN_ROOM.csv', $output, "text/csv", false);
				exit();
				break;
		}
		$csv = new ECSVExport($report);
		$output = $csv->toCSV();
		Yii::app()->getRequest()->sendFile('report.csv', $output, "text/csv", false);
		exit();
	}

	public function actionOccupancyChart() {
		Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseUrl . '/js/min-js/occupancyChart.min.js?version=1.0.4', CClientScript::POS_END);
		$branchModel = Branch::currentBranch();
		$criteria = new CDbCriteria();
		$criteria->select = "id, MIN(minimum_time) AS minimum_time";
		$criteria->condition = "is_active = :is_active AND branch_id = :branch_id AND is_minimum = 1 and is_modified = 0";
		$criteria->params = array(
			":is_active" => 1,
			'branch_id' => $branchModel->id
		);
		$minimumBookingSession = SessionRates::model()->find($criteria);
		if (!empty($minimumBookingSession) && $minimumBookingSession->minimum_time < 30 && $minimumBookingSession->minimum_time != "") {
			$minimumBookingSessionTime = "00:" . $minimumBookingSession->minimum_time . ":00";
		} else {
			$minimumBookingSessionTime = "00:30:00";
		}
		$nurseryOperationDays = implode(",", array_diff(array(
			0,
			1,
			2,
			3,
			4,
			5,
			6
				), explode(",", $branchModel->nursery_operation_days)));
		$this->render('occupancyChart', [
			'minimumBookingSessionTime' => $minimumBookingSessionTime,
			'branchModel' => $branchModel
		]);
	}

	public function actionOccupancyChartRoomDetails() {
		if (Yii::app()->request->isAjaxRequest) {
			$model = Room::model()->findAllByAttributes([
				'branch_id' => Branch::currentBranch()->id,
				'show_on_bookings' => 1
			]);
			$response = array();
			foreach ($model as $room) {
				$temp = array();
				$temp['id'] = $room->id;
				$temp['title'] = $room->name . " (" . $room->getAgeGroupLower() . " - " . $room->getAgeGroupUpper() . " )" . " - " . $room->capacity;
				$temp['children'] = array(
					array(
						'id' => "s" . $room->id,
						'title' => 'Children Starting'
					),
					array(
						'id' => "w" . $room->id,
						'title' => 'Children Waitlisted'
					)
				);
				$temp['capacity'] = $room->capacity;
				$response[] = $temp;
			}
			echo CJSON::encode($response);
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	public function actionGetRoomOccupancy() {
		if (Yii::app()->request->isAjaxRequest) {
			$branchModel = Branch::currentBranch();
			$minimumBookingSession = SessionRates::model()->find([
				'condition' => 'is_active = 1 AND branch_id = :branch_id AND is_minimum = 1 AND is_modified = 0',
				'params' => [
					':branch_id' => $branchModel->id
				]
			]);
			if (!empty($minimumBookingSession) && $minimumBookingSession->minimum_time < 30 && $minimumBookingSession->minimum_time != "") {
				$minimumBookingSessionTime = $minimumBookingSession->minimum_time;
			} else {
				$minimumBookingSessionTime = 30;
			}
			$events = array();
			$operationStartTime = $branchModel->child_bookings_start_time;
			$operationFinishTime = $branchModel->child_bookings_finish_time;
			$roomModel = Room::model()->findAllByAttributes([
				'branch_id' => $branchModel->id,
				'show_on_bookings' => 1
			]);
			$childBookingModel = ChildBookings::model()->getBookings(date("Y-m-d", strtotime($_POST['start'])), date("Y-m-d", strtotime($_POST['start'])), $branchModel->id);
			while (strtotime($operationStartTime) <= strtotime($operationFinishTime)) {
				foreach ($roomModel as $room) {
					$count = 0;
					$finishTime = date('H:i', strtotime($operationStartTime . '+' . ($minimumBookingSessionTime) . ' minutes'));
					$temp = array();
					$temp['id'] = uniqid();
					$temp['rendering'] = 'background';
					$temp['resourceId'] = $room->id;
					$temp['start'] = date("Y-m-d", strtotime($_POST['start'])) . "T" . date("H:i:s", strtotime($operationStartTime));
					$temp['end'] = date("Y-m-d", strtotime($_POST['start'])) . "T" . date("H:i:s", strtotime($finishTime));
					$childDetails = array();
					foreach ($childBookingModel as $booking) {
						if ($booking->room_id == $room->id && ((strtotime($operationStartTime) >= strtotime($booking->start_time) and strtotime($operationStartTime) <= strtotime($booking->finish_time)))) {
							if ($booking->childNds->is_deleted == 1) {
								continue;
							}
							if ($booking->childNds->is_active == 0) {
								if (!empty($booking->childNds->leave_date) && isset($booking->childNds->leave_date)) {
									if (strtotime(date("Y-m-d", strtotime($_POST['start']))) > strtotime(date("Y-m-d", strtotime($booking->childNds->leave_date)))) {
										continue;
									}
								} else if (!empty($booking->childNds->last_updated) && isset($booking->childNds->last_updated)) {
									if (strtotime(date("Y-m-d", strtotime($_POST['start']))) > strtotime(date("Y-m-d", strtotime($booking->childNds->last_updated)))) {
										continue;
									}
								}
							}
							$bookingsDates = customFunctions::getDatesOfDays($booking->start_date, $booking->finish_date, explode(",", $booking->childBookingsDetails->booking_days));
							if (is_array($bookingsDates)) {
								if (in_array(date("Y-m-d", strtotime($_POST['start'])), $bookingsDates)) {
									$checkChildOnHoliday = ChildHolidays::model()->find([
										'condition' => 'date = :date AND exclude_from_invoice = 1 AND child_id = :child_id',
										'params' => [
											':date' => date("Y-m-d", strtotime($_POST['start'])),
											':child_id' => $booking->child_id
										]
									]);
									if ($checkChildOnHoliday) {
										continue;
									}
									$count = $count + 1;
									$childDetails[] = array(
										'name' => $booking->childNds->name,
										'dob' => $booking->childNds->dob,
										'age' => $booking->childNds->getAge(),
										'bookingStartTime' => date("H:i:s", strtotime($booking->start_time)),
										'bookingFinishTime' => date("H:i:s", strtotime($booking->finish_time))
									);
								}
							}
						}
					}
					usort($childDetails, function($a, $b) {
						return strcmp(trim($a["name"], ' '), trim($b["name"], ' '));
					});
					if ($count == $room->capacity) {
						$temp['color'] = "#09ADF3";
					} else
					if ($count > $room->capacity) {
						$temp['color'] = "#E84B4B";
					} else {
						$temp['color'] = "#8FDF82";
					}
					$temp['type'] = "B";
					$temp['title'] = $count;
					$temp['child_details'] = $childDetails;
					$events[] = $temp;

					$startingChildrenModel = ChildPersonalDetails::model()->findAll([
						'condition' => 'start_date is not NULL and start_date != "" AND start_date >= :start_date AND branch_id = :branch_id AND room_id = :room_id',
						'params' => [
							':room_id' => $room->id,
							':start_date' => $_POST['start'],
							':branch_id' => $branchModel->id
						]
					]);
					$tempStarting = array();
					$tempStarting['id'] = uniqid();
					$tempStarting['rendering'] = 'background';
					$tempStarting['resourceId'] = "s" . $room->id;
					$tempStarting['start'] = date("Y-m-d", strtotime($_POST['start'])) . "T" . date("H:i:s", strtotime($branchModel->operation_start_time));
					$tempStarting['end'] = date("Y-m-d", strtotime($_POST['start'])) . "T" . date("H:i:s", strtotime($branchModel->operation_finish_time));
					$tempStarting['color'] = "#FFFEED";
					$tempStarting['type'] = "S";
					$countStartingChildren = 0;
					$startingChildDetails = array();
					if (!empty($startingChildrenModel)) {
						foreach ($startingChildrenModel as $startingChild) {
							++$countStartingChildren;
							$startingChildDetails[] = array(
								'name' => $startingChild->name,
								'start_date' => date("d-M-Y", strtotime($startingChild->start_date)),
								'dob' => $startingChild->dob,
								'age' => customFunctions::ageInMonths($startingChild->dob, $_POST['start'])
							);
						}
						usort($startingChildDetails, function($a, $b) {
							return strcmp(trim($a["name"], ' '), trim($b["name"], ' '));
						});
					}
					$tempStarting['child_details'] = $startingChildDetails;
					$tempStarting['title'] = $countStartingChildren;
					$events[] = $tempStarting;

					$waitListedEnquiryModel = Enquiries::model()->findAll([
						'condition' => 'is_waitlisted = 1 AND is_enroll_child = 0 AND waitlisted_room = :waitlisted_room',
						'params' => [
							':waitlisted_room' => $room->id
						]
					]);
					$tempWaitlisted = array();
					$tempWaitlisted['id'] = uniqid();
					$tempWaitlisted['rendering'] = 'background';
					$tempWaitlisted['resourceId'] = "w" . $room->id;
					$tempWaitlisted['start'] = date("Y-m-d", strtotime($_POST['start'])) . "T" . date("H:i:s", strtotime($branchModel->operation_start_time));
					$tempWaitlisted['end'] = date("Y-m-d", strtotime($_POST['start'])) . "T" . date("H:i:s", strtotime($branchModel->operation_finish_time));
					$tempWaitlisted['color'] = "#EEEDC6";
					$tempWaitlisted['type'] = "W";
					$countWaitlistedEnquiry = 0;
					$waitListedEnquiryDetails = array();
					if (!empty($waitListedEnquiryModel)) {
						foreach ($waitListedEnquiryModel as $waitlistedEnquiry) {
							++$countWaitlistedEnquiry;
							$waitListedEnquiryDetails[] = array(
								'name' => $waitlistedEnquiry->child_first_name . " " . $waitlistedEnquiry->child_last_name,
								'dob' => isset($waitlistedEnquiry->child_dob) ? $waitlistedEnquiry->child_dob : "",
								'age' => isset($waitlistedEnquiry->child_dob) ? customFunctions::ageInMonths($waitlistedEnquiry->child_dob, $_POST['start']) : "",
								'enquiry_date' => isset($waitlistedEnquiry->enquiry_date_time) ? date("d-m-Y", strtotime($waitlistedEnquiry->enquiry_date_time)) : ""
							);
						}
						usort($waitListedEnquiryDetails, function($a, $b) {
							return strcmp(trim($a["name"], ' '), trim($b["name"], ' '));
						});
					}
					$tempWaitlisted['title'] = $countWaitlistedEnquiry;
					$tempWaitlisted['child_details'] = $waitListedEnquiryDetails;
					$events[] = $tempWaitlisted;
				}
				$operationStartTime = date('H:i', strtotime($operationStartTime . '+' . ($minimumBookingSessionTime) . ' minutes'));
			}
			echo CJSON::encode($events);
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionSessionOccupancyChart() {
		Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseUrl . '/js/sessionOccupancyChart.js?version=1.0.4', CClientScript::POS_END);
		$branchModel = Branch::currentBranch();
		$this->render('sessionOccupancyChart', [
			'branchModel' => $branchModel
		]);
	}

	public function actionSessionOccupancyChartChildDetails() {
		if (Yii::app()->request->isAjaxRequest) {
			$branchModel = Branch::currentBranch();
			if (empty($branchModel)) {
				echo CJSON::encode([]);
				Yii::app()->end();
			}
			$roomModel = Room::model()->findAllByAttributes([
				'branch_id' => $branchModel->id,
				'show_on_bookings' => 1
				], ['order' => 'age_group_lower']);

			$model = ChildPersonalDetailsNds::model()->findAll(
				[
					'condition' => 'branch_id = :branch_id AND is_deleted = 0 AND (leave_date is NULL OR leave_date = "" OR leave_date >= :leave_date)',
					'params' => [
						':branch_id' => $branchModel->id,
						':leave_date' => date("Y-m-d", strtotime($_POST['start']))
					],
					'order' => 'room_id, first_name, last_name'
			]);
			$sessionModel = SessionRates::model()->findAllByAttributes(['branch_id' => $branchModel->id, 'is_modified' => 0], ['order' => 'start_time, finish_time, name']);
			$sessionData = array();
			$response = array();
			$response[$branchModel->id] = [
				'id' => "branch_" . $branchModel->id,
				'title' => $branchModel->name . " - " . $branchModel->capacity,
				'children' => [],
				'color' => '#EEEDC6'
			];
			if (!empty($roomModel)) {
				foreach ($roomModel as $room) {
					$response[$branchModel->id]['children'][$room->id] = [
						'id' => "room_" . $room->id,
						'title' => $room->name . " - " . $room->capacity,
						'children' => [],
						'color' => $room->color,
					];
				}
				$response[$branchModel->id]['children'][0] = [
					'id' => "room_0",
					'title' => "Not Assigned",
					'children' => [],
					'color' => '#8fdf82',
				];
			}
			foreach ($model as $child) {
				$temp = array();
				$temp['id'] = $child->id;
				$temp['title'] = $child->name;
				$sessionData = array();
				if (!empty($sessionModel)) {
					foreach ($sessionModel as $session) {
						$sessionData[] = [
							'id' => $child->id . "_" . $session->id,
							'title' => $session->name . " [" . date("h:i A", strtotime($session->start_time)) . " - " . date("h:i A", strtotime($session->finish_time)) . " ]"
						];
					}
				}
				$temp['children'] = $sessionData;
				$temp['type'] = 1;
				if (!empty($child->room_id)) {
					if ($child->room->show_on_bookings == 1) {
						$response[$branchModel->id]['children'][$child->room_id]['children'][] = $temp;
					}
				} else {
					$response[$branchModel->id]['children'][0]['children'][] = $temp;
				}
			}
			$response[$branchModel->id]['children'] = array_values($response[$branchModel->id]['children']);
			$response = array_values($response);
			echo CJSON::encode($response);
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	public function actionSessionOccupancy($isSessionOccupancy, $start, $end) {
		if (Yii::app()->request->isAjaxRequest) {
			ini_set("memory_limit", "1000M");
			$branchModel = Branch::currentBranch();
			$branchOperationTime = customFunctions::getHours($branchModel->child_bookings_start_time, $branchModel->child_bookings_finish_time);
			$events = array();
			$monthStartDate = $start;
			$monthFinishDate = $end;
			$branchOperationDays = customFunctions::getDatesOfDays($start, $end, explode(",", $branchModel->nursery_operation_days));
			$childModel = ChildPersonalDetailsNds::model()->findAll(
				[
					'condition' => 'branch_id = :branch_id AND is_deleted = 0 AND (leave_date is NULL OR leave_date = "" OR leave_date >= :leave_date)',
					'params' => [
						':branch_id' => $branchModel->id,
						':leave_date' => date("Y-m-d", strtotime($start))
					],
					'order' => 'first_name, last_name'
			]);
			$roomFte = [];
			$roomOccupancy = [];
			$branchFte = [];
			$branchOccupancy = [];
			foreach ($childModel as $child) {
				$totalFte = [];
				$totalOccupancy = [];
				$totalFteColor = [];
				$totalOccupancyColor = [];
				$childBookingModel = ChildBookings::model()->getBookings($monthStartDate, $monthFinishDate, $branchModel->id, $child->id);
				foreach ($childBookingModel as $booking) {
					if ($booking->room->show_on_bookings == 0) {
						continue;
					}
					$bookingDays = customFunctions::getDatesOfDays($booking->start_date, $booking->finish_date, explode(",", $booking->childBookingsDetails->booking_days));
					if (!empty($bookingDays)) {
						foreach ($bookingDays as $bookingDay) {
							$checkChildOnHoliday = ChildHolidays::model()->find([
								'condition' => 'date = :date AND exclude_from_invoice = 1 AND child_id = :child_id',
								'params' => [
									':date' => $bookingDay,
									':child_id' => $booking->child_id
								]
							]);
							if ($checkChildOnHoliday) {
								continue;
							}
							$fte = customFunctions::round(customFunctions::getHours($booking->start_time, $booking->finish_time) / $branchOperationTime, 2);
							$occupancy = customFunctions::round(customFunctions::getHours($booking->start_time, $booking->finish_time), 2);
							$totalFte[$bookingDay] += $fte;
							$totalOccupancy[$bookingDay] += $occupancy;
							$branchFte[$bookingDay] += $fte;
							$branchOccupancy[$bookingDay] += $occupancy;
							$roomFte[$bookingDay][$booking->room_id] += $fte;
							$roomOccupancy[$bookingDay][$booking->room_id] += $occupancy;
							$temp = array();
							$temp['id'] = uniqid();
							$temp['rendering'] = 'background';
							$temp['resourceId'] = $child->id . "_" . $booking->session_type_id;
							$temp['start'] = date("Y-m-d", strtotime($bookingDay));
							$temp['end'] = date("Y-m-d", strtotime($bookingDay));
							$temp['color'] = $booking->sessionType->color;
							$temp['title'] = ($isSessionOccupancy == 0) ? ($booking->room_id == $child->room_id) ? $fte : $fte . "*" : ($booking->room_id == $child->room_id) ? $occupancy : $occupancy . "*";
							$events[] = $temp;
							$temp = array();
							$temp['id'] = uniqid();
							$temp['rendering'] = 'background';
							$temp['resourceId'] = $child->id;
							$temp['start'] = date("Y-m-d", strtotime($bookingDay));
							$temp['end'] = date("Y-m-d", strtotime($bookingDay));
							$temp['color'] = $booking->sessionType->color;
							$temp['title'] = ($isSessionOccupancy == 0) ? customFunctions::round($totalFte[$bookingDay], 2) : customFunctions::round($totalOccupancy[$bookingDay], 2);
							if (empty($totalFteColor[$bookingDay])) {
								$events[] = $temp;
							}
							if (!empty($totalFteColor[$bookingDay])) {
								if ($fte >= max($totalFteColor[$bookingDay])) {
									$events[] = $temp;
								}
							} else {
								$events[] = $temp;
							}
							$totalFteColor[$bookingDay][] = $fte;
						}
					}
				}
			}
			if ($isSessionOccupancy == 1) {
				if (!empty($roomOccupancy)) {
					foreach ($roomOccupancy as $row => $value) {
						if (in_array($row, $branchOperationDays)) {
							if (!empty($value)) {
								foreach ($value as $room_id => $fte) {
									$model = Room::model()->findByPk($room_id);
									$temp['id'] = uniqid();
									$temp['rendering'] = 'background';
									$temp['resourceId'] = "room_" . $room_id;
									$temp['start'] = date("Y-m-d", strtotime($row));
									$temp['end'] = date("Y-m-d", strtotime($row));
									$temp['color'] = $model->color;
									$temp['title'] = customFunctions::round(customFunctions::getDividedResult(($model->capacity * $branchOperationTime), $fte) * 100, 2);
									$events[] = $temp;
								}
							}
						}
					}
				}
				if (!empty($branchOccupancy)) {
					foreach ($branchOccupancy as $day => $value) {
						if (in_array($day, $branchOperationDays)) {
							$temp['id'] = uniqid();
							$temp['rendering'] = 'background';
							$temp['resourceId'] = "branch_" . $branchModel->id;
							$temp['start'] = date("Y-m-d", strtotime($day));
							$temp['end'] = date("Y-m-d", strtotime($day));
							$temp['color'] = "#EEEDC6";
							$temp['title'] = customFunctions::round(customFunctions::getDividedResult(($branchModel->capacity * $branchOperationTime), $value) * 100, 2);
							$events[] = $temp;
						}
					}
				}
			} else {
				if (!empty($roomFte)) {
					foreach ($roomFte as $row => $value) {
						if (in_array($row, $branchOperationDays)) {
							if (!empty($value)) {
								foreach ($value as $room_id => $fte) {
									$model = Room::model()->findByPk($room_id);
									$temp['id'] = uniqid();
									$temp['rendering'] = 'background';
									$temp['resourceId'] = "room_" . $room_id;
									$temp['start'] = date("Y-m-d", strtotime($row));
									$temp['end'] = date("Y-m-d", strtotime($row));
									$temp['color'] = $model->color;
									$temp['title'] = $fte;
									$events[] = $temp;
								}
							}
						}
					}
				}
				if (!empty($branchFte)) {
					foreach ($branchFte as $day => $value) {
						if (in_array($day, $branchOperationDays)) {
							$temp['id'] = uniqid();
							$temp['rendering'] = 'background';
							$temp['resourceId'] = "branch_" . $branchModel->id;
							$temp['start'] = date("Y-m-d", strtotime($day));
							$temp['end'] = date("Y-m-d", strtotime($day));
							$temp['color'] = "#EEEDC6";
							$temp['title'] = $value;
							$events[] = $temp;
						}
					}
				}
			}
			echo CJSON::encode($events);
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionStaffHourReportPdf() {
		ini_set("memory_limit", "-1");
		ini_set('max_execution_time' , -1);
		if (Yii::app()->request->isAjaxRequest) {
			$branchModel = Branch::currentBranch();
			$invoiceSettings = $branchModel->invoiceSettings;
			if (empty($invoiceSettings)) {
				customFunctions::sendPartialResponse(['status' => 0, 'message' => "Please create invoice settings to send out the email."]);
				Yii::app()->end();
			} else {
				customFunctions::sendPartialResponse(['status' => 1, 'message' => "Staff hours report will be sent shortly on email."]);
			}
			$report = customFunctions::staffHoursReport($_POST['month'], $_POST['week1_code'], $_POST['week2_code'], $_POST['week3_code'], $_POST['week4_code'], $_POST['week5_code'], $_POST['stepUp_code'], $_POST['exclude_salaried'], $_POST['branch_id']);
			$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'P');
			$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport5', array('report' => $report), TRUE));
			$attachmentPdf = $mpdf->Output('', "S");
			$attachment = [
				'type' => 'application/pdf',
				'name' => "StaffHoursReport.pdf",
				'content' => base64_encode($attachmentPdf)
			];
			$recipient = [
				'email' => Yii::app()->session['email'],
				'name' => Yii::app()->session['name'],
				'type' => 'to'
			];
			$subject = "Staff Hours Report";
			$content = "Hi<br/>PFA,<br/><br/><b>Staff Hours Report</b>";
			$metadata = [
				'rcpt' => $recipient['email'],
				'values' => ['Staff Hours Report']
			];
			$mandrill = new EymanMandril($subject, $content, $branchModel->company->name, [$recipient], $invoiceSettings->from_email, [$attachment], [$metadata]);
			$response = $mandrill->sendEmail();
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionStaffHourReportCsv() {
		ini_set("memory_limit", "-1");
		ini_set('max_execution_time' , -1);
		if (Yii::app()->request->isAjaxRequest) {
			$branchModel = Branch::currentBranch();
			$invoiceSettings = $branchModel->invoiceSettings;
			if (empty($invoiceSettings)) {
				customFunctions::sendPartialResponse(['status' => 0, 'message' => "Please create invoice settings to send out the email."]);
				Yii::app()->end();
			} else {
				customFunctions::sendPartialResponse(['status' => 1, 'message' => "Staff hours report will be sent shortly on email."]);
			}
			$report = customFunctions::staffHoursReport($_POST['month'], $_POST['week1_code'], $_POST['week2_code'], $_POST['week3_code'], $_POST['week4_code'], $_POST['week5_code'], $_POST['stepUp_code'], $_POST['exclude_salaried'], $_POST['branch_id']);
			if (!empty($report)) {
				foreach ($report[0] as $key => $value) {
					$grid_columns[] = $key;
				}
			}
			$csv = new ECSVExport($report);
			$output = $csv->toCSV();
			$attachment = [
				'type' => 'text/csv',
				'name' => "StaffHoursReport.csv",
				'content' => base64_encode($output)
			];
			$recipient = [
				'email' => Yii::app()->session['email'],
				'name' => Yii::app()->session['name'],
				'type' => 'to'
			];
			$subject = "Staff Hours Report";
			$content = "Hi<br/>PFA,<br/><br/><b>Staff Hours Report</b>";
			$metadata = [
				'rcpt' => $recipient['email'],
				'values' => ['Staff Hours Report']
			];
			$mandrill = new EymanMandril($subject, $content, $branchModel->company->name, [$recipient], $invoiceSettings->from_email, [$attachment], [$metadata]);
			$response = $mandrill->sendEmail();
		}
	}

	public function actionStaffSchedulingReportPdf() {
		ini_set("memory_limit", "-1");
		ini_set('max_execution_time' , -1);
		if (Yii::app()->request->isAjaxRequest) {
			$branchModel = Branch::currentBranch();
			$invoiceSettings = $branchModel->invoiceSettings;
			if (empty($invoiceSettings)) {
				customFunctions::sendPartialResponse(['status' => 0, 'message' => "Please create invoice settings to send out the email."]);
				Yii::app()->end();
			} else {
				customFunctions::sendPartialResponse(['status' => 1, 'message' => "Staff scheduling report will be sent shortly on email."]);
			}
			$report = customFunctions::getStaffHoursData($_POST['start_date'], $_POST['finish_date'], $_POST['holiday_type_reason'], $_POST['absence_only'], $_POST['staff_id'], $_POST['group_by'], $_POST['order_by'], $_POST['minimum_data']);
			$mpdf = new mPDF('utf-8', 'A4-P', 0, 'Arial, "regular"', 2.5, 2.5, 30, 20, 2.5, 2.5, 'P');
			$mpdf->WriteHTML($this->renderPartial('pdfTemplates/pdfStandardReport8_email', array('report' => $report, 'data' => $_POST), TRUE));
			$attachmentPdf = $mpdf->Output('', "S");
			$attachment = [
				'type' => 'application/pdf',
				'name' => "StaffSchedulingReport.pdf",
				'content' => base64_encode($attachmentPdf)
			];
			$recipient = [
				'email' => Yii::app()->session['email'],
				'name' => Yii::app()->session['name'],
				'type' => 'to'
			];
			$subject = "Staff Scheduling Report";
			$content = "Hi<br/>PFA,<br/><br/><b>Staff Scheduling Report</b>";
			$metadata = [
				'rcpt' => $recipient['email'],
				'values' => ['Staff Scheduling Report']
			];
			$mandrill = new EymanMandril($subject, $content, $branchModel->company->name, [$recipient], $invoiceSettings->from_email, [$attachment], [$metadata]);
			$response = $mandrill->sendEmail();
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionStaffSchedulingReportCsv() {
		ini_set("memory_limit", "-1");
		ini_set('max_execution_time' , -1);
		if (Yii::app()->request->isAjaxRequest) {
			if (!isset($_POST['minimum_data'])) {
				$_POST['minimum_data'] = 0;
			}
			if (!isset($_POST['absence_only'])) {
				$_POST['absence_only'] = 0;
			}
			if (!isset($_POST['staff_id'])) {
				$_POST['staff_id'] = array();
			}
			$branchModel = Branch::currentBranch();
			$invoiceSettings = $branchModel->invoiceSettings;
			if (empty($invoiceSettings)) {
				customFunctions::sendPartialResponse(['status' => 0, 'message' => "Please create invoice settings to send out the email."]);
				Yii::app()->end();
			} else {
				customFunctions::sendPartialResponse(['status' => 1, 'message' => "Staff hours report will be sent shortly on email."]);
			}
			$reportData = customFunctions::getStaffHoursData($_POST['start_date'], $_POST['finish_date'], $_POST['holiday_type_reason'], $_POST['absence_only'], $_POST['staff_id'], $_POST['group_by'], $_POST['order_by'], $_POST['minimum_data']);
			$report = array();
			if (isset($_POST['group_by']) && $_POST['group_by'] == "Week") {
				if ($_POST['minimum_data'] == 0) :
					foreach ($reportData as $key => $value) {
						$report[] = array(
							$value['branch'],
							"Week Commencing " . date('d-M-Y', strtotime($key)),
							"",
							"",
							""
						);
						$report[] = array();
						foreach ($value['staff'] as $key2 => $value2) {
							if (!empty($value2['booking_data'])) :
								$report[] = array(
									$value2['staff_name'],
									$value2['position'],
									"",
									"",
									""
								);
								foreach ($value2['booking_data'] as $key3 => $value3) {
									$report[] = array(
										$value3['day'],
										$value3['time'],
										$value3['room'],
										$value3['activity'],
										$value3['notes'],
									);
								}
								$report[] = array(
									"Contracted Hours : " . $value2['contracted_hours'],
									"Total Hours: " . $value2['total_hours'],
									"Paid Hours: " . $value2['paid_hours'],
									"Unpaid Hours: " . $value2['unpaid_hours'],
									"StepUp Hours: " . $value2['step_up_hours']
								);
								$report[] = array();
							endif;
						}
					}
				endif;

				if ($_POST['minimum_data'] == 1) :
					foreach ($reportData as $key => $value) {
						$report[] = array(
							$value['branch'],
							"Week Commencing " . date('d-M-Y', strtotime($key)),
							"",
							"",
							""
						);
						$report[] = array();
						foreach ($value['staff'] as $key2 => $value2) {
							if (!empty($value2['booking_data'])) :
								$notes = array();
								foreach ($value2['booking_data'] as $key3 => $value3) {
									if (!empty($value3['notes'])) {
										$notes[] = $value3['notes'];
									}
								}
								if (!empty($value2['holiday'])) {
									$report[] = array(
										$value2['staff_name'],
										$value2['position'],
										"Sickness : " . implode(", ", $value2['holiday']) . "; Notes : " . implode(", ", $notes),
										"",
										""
									);
								} else {
									$report[] = array(
										$value2['staff_name'],
										$value2['position'],
										"Notes : " . implode(", ", $notes),
										"",
										""
									);
								}
								$report[] = array(
									"Contracted Hours : " . $value2['contracted_hours'],
									"Total Hours: " . $value2['total_hours'],
									"Paid Hours: " . $value2['paid_hours'],
									"Unpaid Hours: " . $value2['unpaid_hours'],
									"StepUp Hours: " . $value2['step_up_hours']
								);
								$report[] = array();


							endif;
						}
					}
				endif;
			}

			if ($_POST['group_by'] == "Staff") {
				if ($_POST['minimum_data'] == 0) :
					if (!empty($reportData)) {
						foreach ($reportData['staff'] as $key2 => $value2) {
							if (!empty($value2['booking_data'])) {
								$report[] = array(
									$value2['staff_name'],
									$value2['position'],
									"",
									"",
									""
								);
								$report[] = array("", "", "", "", "");
								foreach ($value2['booking_data'] as $key3 => $value3) {
									$report[] = array(
										$reportData['branch'],
										"Week Commencing " . date('d-M-Y', strtotime($key3)),
										"",
										"",
										""
									);
									$report[] = array("", "", "", "", "");
									if (!empty($value3['booking_data'])) {
										foreach ($value3['booking_data'] as $key4 => $value4) {
											$report[] = array(
												$value4['day'],
												$value4['time'],
												$value4['room'],
												$value4['activity'],
												$value4['notes']
											);
										}
									}
									$report[] = array(
										"Contracted Hours : " . $value3['contracted_hours'],
										"Total Hours: " . $value3['total_hours'],
										"Paid Hours: " . $value3['paid_hours'],
										"Unpaid Hours: " . $value3['unpaid_hours'],
										"StepUp Hours: " . $value3['step_up_hours']
									);
									$report[] = array("", "", "", "", "");
								}
								$report[] = array(
									"Total -  Contracted Hours : " . $value2['total_contracted_hours'],
									"Total - Total Hours: " . $value2['total_total_hours'],
									"Total - Paid Hours: " . $value2['total_paid_hours'],
									"Total - Unpaid Hours: " . $value2['total_unpaid_hours'],
									"Total - StepUp Hours: " . $value2['total_step_up_hours']
								);
							}
						}
					}
				endif;

				if ($_POST['minimum_data'] == 1) :
					if (!empty($reportData)) {
						foreach ($reportData['staff'] as $key2 => $value2) {
							if (!empty($value2['booking_data'])) {
								$report[] = array(
									$value2['staff_name'],
									$value2['position'],
									"",
									"",
									""
								);
								$report[] = array("", "", "", "", "");
								foreach ($value2['booking_data'] as $key3 => $value3) {
									$isWeekConfirmed = true;
									$notes = array();
									if (!empty($value3['booking_data'])) {
										foreach ($value3['booking_data'] as $key4 => $value4) {
											if ($value4['is_confirmed'] == 0): $isWeekConfirmed = false;
											endif;
											if (!empty($value4['notes'])) {
												$notes[] = $value4['notes'];
											}
										}
									}
									$report[] = array(
										$reportData['branch'],
										"Week Commencing " . date('d-M-Y', strtotime($key3)),
										"Sickness : " . implode(", ", $value3['holiday']) . "; Notes : " . implode(", ", $notes),
										"",
										""
									);
									$report[] = array(
										"Contracted Hours : " . $value3['contracted_hours'],
										"Total Hours: " . $value3['total_hours'],
										"Paid Hours: " . $value3['paid_hours'],
										"Unpaid Hours: " . $value3['unpaid_hours'],
										"StepUp Hours: " . $value3['step_up_hours']
									);
									$report[] = array("", "", "", "", "");
								}
								$report[] = array(
									"Total -  Contracted Hours : " . $value2['total_contracted_hours'],
									"Total - Total Hours: " . $value2['total_total_hours'],
									"Total - Paid Hours: " . $value2['total_paid_hours'],
									"Total - Unpaid Hours: " . $value2['total_unpaid_hours'],
									"Total - StepUp Hours: " . $value2['total_step_up_hours']
								);
							}
						}
					}
				endif;
			}

			$csv = new ECSVExport($report);
			if ($_POST['minimum_data'] == 0) {
				$csv->setHeaders(array(
					"Day",
					"Time",
					"Room",
					"Activity",
					"Notes"
				));
			} else {
				$csv->setHeaders(array(
					"Staff Name",
					"Position",
					"Notes",
					"",
					""
				));
			}
			$output = $csv->toCSV();
			$attachment = [
				'type' => 'text/csv',
				'name' => "StaffSchedulingReport.csv",
				'content' => base64_encode($output)
			];
			$recipient = [
				'email' => Yii::app()->session['email'],
				'name' => Yii::app()->session['name'],
				'type' => 'to'
			];
			$subject = "Staff Scheduling Report";
			$content = "Hi<br/>PFA,<br/><br/> <b>Staff Scheduling Report</b>";
			$metadata = [
				'rcpt' => $recipient['email'],
				'values' => ['Staff Scheduling Report']
			];
			$mandrill = new EymanMandril($subject, $content, $branchModel->company->name, [$recipient], $invoiceSettings->from_email, [$attachment], [$metadata]);
			$response = $mandrill->sendEmail();
		}
	}

}
