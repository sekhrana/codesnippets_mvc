<?php

class TagsController extends RController {

	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout = '//layouts/systemSettings';

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
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id, $global = 0) {
		if ($global == 1) {
			$this->layout = 'global';
		}
		$this->render('view', array(
			'model' => $this->loadModel($id)
		));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate($global = 0) {
		if ($global == 1) {
			$this->layout = 'global';
		}
		$model = new Tags;
		$this->performAjaxValidation($model);
		if (isset($_POST['Tags'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model->attributes = $_POST['Tags'];
				$model->branch_id = Branch::currentBranch()->id;
				if (isset($_POST['global']) && $_POST['global'] == 1) {
					$model->is_global = 1;
					$model->global_id = Yii::app()->session['company_id'];
					$model->branch_id = Branch::model()->createBranchByGlobalId(Yii::app()->session['company_id']);
				}
				if ($model->save()) {
					$global_tags_id = $model->id;
					if (isset($_POST['global']) && $_POST['global'] == 1) {
						if (isset($_POST['Tags']['create_for_existing']) && $_POST['Tags']['create_for_existing'] == 1) {
							$branchModel = Branch::model()->findAllByAttributes(array(
								'is_active' => 1,
								'company_id' => Company::currentCompany()->id
							));
							if (!empty($branchModel)) {
								foreach ($branchModel as $branch) {
									$model->isNewRecord = true;
									$model->id = null;
									$model->branch_id = $branch->id;
									$model->attributes = $_POST['Tags'];
									$model->is_global = 0;
									$model->global_id = Company::currentCompany()->id;
									$model->create_for_existing = 0;
									$model->global_tags_id = $global_tags_id;
									if (!$model->save())
										throw new Exception(CHtml::errorSummary($model, "", "", array(
											'class' => 'customErrors'
										)));
								}
							}
						}
						$transaction->commit();
						$this->redirect(array(
							'global'
						));
					}
					$transaction->commit();
					$this->redirect(array(
						'index'
					));
				}else {
					throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors')));
				}
			} catch (Exception $ex) {
				Yii::app()->user->setFlash('error', $ex->getMessage());
				$transaction->rollback();
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
		$model = $this->loadModel($id);
		if ($global == 1) {
			$this->layout = 'global';
		}
		// Uncomment the following line if AJAX validation is needed
		$this->performAjaxValidation($model);

		if (isset($_POST['Tags'])) {
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$model->attributes = $_POST['Tags'];
				if ($model->save()) {
					if (isset($_POST['global']) && $_POST['global'] == 1) {
						if ($model->is_global = 1) {
							$branchTagsModel = Tags::model()->findAllByAttributes(array(
								'global_tags_id' => $model->id
							));
							foreach ($branchTagsModel as $branchTagsCode) {
								$branchTagsCode->name = $model->name;
								$branchTagsCode->description = $model->description;
								$branchTagsCode->for_staff = $model->for_staff;
								$branchTagsCode->for_child = $model->for_child;
								$branchTagsCode->color = $model->color;
								if (!$branchTagsCode->save())
									throw new Exception(CHtml::errorSummary($branchTagsCode, "", "", array(
										'class' => 'customErrors'
									)));
							}
						}
						$transaction->commit();
						$this->redirect(array(
							'global'
						));
					}
					$transaction->commit();
					$this->redirect(array(
						'index'
					));
				} else {
					throw new Exception(CHtml::errorSummary($model, "", "", array(
						'class' => 'customErrors'
					)));
				}
			} catch (Exception $ex) {
				Yii::app()->user->setFlash('error', $ex->getMessage());
				$transaction->rollback();
				$this->refresh();
			}
		}
		$this->render('update', array(
			'model' => $model
		));
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete() {
		if (Yii::app()->request->isAjaxRequest) {
			$response = array(
				'status' => '1'
			);
			if (Tags::model()->updateByPk($_POST['id'], ['is_deleted' => 1])) {
				echo CJSON::encode($response);
				Yii::app()->end();
			} else {
				$response = array(
					'status' => '0'
				);
				echo CJSON::encode($response);
				Yii::app()->end();
			}
		} else {
			throw new CHttpException(404, "Your request is not valid.");
		}
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex() {
		$this->pageTitle = 'Tags| eyMan';
		if (isset(Yii::app()->session['global_id'])) {
			unset(Yii::app()->session['global_id']);
		}
		$model = new Tags('search');
		$model->unsetAttributes(); // clear any default values
		if (isset($_GET['Tags']))
			$model->attributes = $_GET['Tags'];

		$this->render('index', array(
			'model' => $model
		));
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin() {
		$model = new Tags('search');
		$model->unsetAttributes(); // clear any default values
		if (isset($_GET['Tags']))
			$model->attributes = $_GET['Tags'];

		$this->render('admin', array(
			'model' => $model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return Tags the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id) {
		$model = Tags::model()->findByPk($id);
		if ($model === null)
			throw new CHttpException(404, 'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param Tags $model the model to be validated
	 */
	protected function performAjaxValidation($model) {
		if (isset($_POST['ajax']) && $_POST['ajax'] === 'tags-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	public function actionStatus($id, $global = 0) {
		$model = Tags::model()->findByPk($id);
		if ($model->is_active == 1) {
			Tags::model()->updateByPk($id, ['is_active' => 0]);
		} else {
			Tags::model()->updateByPk($id, ['is_active' => 1]);
		}
		$this->redirect(['index']);
	}

	public function actionGlobal() {
		$this->layout = 'global';
		$this->pageTitle = 'Tags | eyMan';
		if (isset(Yii::app()->session['company_id'])) {
			Yii::app()->session['global_id'] = Yii::app()->session['company_id'];
		}
		$model = new Tags('search');
		$model->unsetAttributes(); // clear any default values
		if (isset($_GET['Tags']))
			$model->attributes = $_GET['Tags'];
		$model->is_global = 1;
		$this->render('index', array(
			'model' => $model
		));
	}

	public function actionGetTags() {
		if (Yii::app()->request->isAjaxRequest) {
			$classToAdd = NULL;
                        $classToRemove = NULL;
			if (Yii::app()->request->getPost('for_child') == 1) {
				$child_id = Yii::app()->request->getPost('child_id');
				$childModel = ChildPersonalDetails::model()->findByPk($child_id);
				Tags::$child_id = $child_id;
				Tags::$type = Tags::CHILD_TAG;
				$criteria = new CDbCriteria([
					'scopes' => 'deleted',
					'condition' => 'branch_id = :branch_id AND for_child = 1',
					'params' => [':branch_id' => $childModel->branch_id]
				]);
                                $classToAdd = "addTagToChild";
                                $classToRemove = "deleteTagFromChild";
			} else {
				$staff_id = Yii::app()->request->getPost('staff_id');
				$type = Tags::STAFF_TAG;
				$staffModel = StaffPersonalDetails::model()->findByPk($staff_id);
				Tags::$staff_id = $staff_id;
				Tags::$type = Tags::STAFF_TAG;
				$criteria = new CDbCriteria([
					'scopes' => 'deleted',
					'condition' => 'branch_id = :branch_id AND for_staff = 1',
					'params' => [':branch_id' => $staffModel->branch_id]
				]);
                                $classToAdd = "addTagToStaff";
                                $classToRemove = "deleteTagFromStaff";
			}
			$dataProvider = new CActiveDataProvider('Tags', [
				'criteria' => $criteria
			]);
			$grid = $this->widget('zii.widgets.grid.CGridView', array(
				'id' => 'tags-grid',
				'htmlOptions' => array('class' => 'table-responsive'),
				'itemsCssClass' => 'table',
				'summaryText' => '',
				'dataProvider' => $dataProvider,
				'enablePagination' => false,
				'pagerCssClass' => 'text-center',
				'enableSorting' => false,
				'ajaxUpdate'=>true,
				'columns' => array(
					array(
						'class' => 'DataColumn',
						'name' => 'color',
						'value' => '',
						'filter' => '',
						'evaluateHtmlOptions' => TRUE,
						'htmlOptions' => array(
							'style' => '"width:50px;padding-right:40px;background:{$data->color}"'
						),
					),
					'name',
					'description',
					array(
						'header' => '<div class="addnewchild"></div>',
						'class' => 'CButtonColumn',
						'template' => '{addTag}{removeTag}',
						'buttons' => array(
							'addTag' => array(
								'url' => '$data->id',
								'options' => array('class' => 'action '. $classToAdd, 'title' => 'Add Tag', 'alt' => 'Add Tag', 'data-child_id' => $child_id , 'data-staff_id' => $staff_id),
								'label' => '<i class="fa fa-plus-circle"></i>',
								'imageUrl' => false,
								'visible' => '($data->checkTagExists($data->id) == true) ? false : true'
							),
							'removeTag' => array(
								'url' => '$data->id',
								'options' => array('class' => 'action '.$classToRemove, 'title' => 'Delete Tag', 'alt' => 'Delete Tag', 'data-child_id' => $child_id , 'data-staff_id' => $staff_id),
								'label' => '<i class="fa fa-trash-o"></i>',
								'imageUrl' => false,
								'visible' => '($data->checkTagExists($data->id) == true) ? true : false'
							)
						),
					),
				),
			));
			return $grid;
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionAddTagToChild() {
		if (Yii::app()->request->isAjaxRequest) {
			$model = ChildTagsMapping::model()->deleted()->findByAttributes(['tag_id' => $_POST['tag_id'], 'child_id' => $_POST['child_id']]);
			if (empty($model)) {
				$model = new ChildTagsMapping;
				$model->tag_id = $_POST['tag_id'];
				$model->child_id = $_POST['child_id'];
				if ($model->save()) {
					$tag_data = array();
					$childAssignedTags = ChildTagsMapping::model()->deleted()->findAllByAttributes(['child_id' => $_POST['child_id']]);
					if (!empty($childAssignedTags)) {
						foreach ($childAssignedTags as $childAssignedTag) {
							$tag_data[] = ['tag_id' => $childAssignedTag->tag_id, 'tag_name' => $childAssignedTag->tag->name, 'tag_color' => $childAssignedTag->tag->color];
						}
					}
					echo CJSON::encode([
						'status' => 1,
						'message' => 'Tag has been sucessfully added to child.',
						'errors' => [],
						'tags' => $tag_data
					]);
				}
			} else {
				echo CJSON::encode([
					'status' => 0,
					'message' => 'Tag is already assigned to child.',
					'errors' => [],
					'tags' => []
				]);
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

	public function actionDeleteTagFromChild() {
		if (Yii::app()->request->isAjaxRequest) {
			$model = ChildTagsMapping::model()->deleted()->updateAll(['is_deleted' => 1], 'tag_id = :tag_id AND child_id = :child_id', [':tag_id' => $_POST['tag_id'], ':child_id' => $_POST['child_id']]);
			if ($model) {
				$tag_data = array();
				$childAssignedTags = ChildTagsMapping::model()->deleted()->findAllByAttributes(['child_id' => $_POST['child_id']]);
				if (!empty($childAssignedTags)) {
					foreach ($childAssignedTags as $childAssignedTag) {
						$tag_data[] = ['tag_id' => $childAssignedTag->tag_id, 'tag_name' => $childAssignedTag->tag->name, 'tag_color' => $childAssignedTag->tag->color];
					}
				}
				echo CJSON::encode([
					'status' => 1,
					'message' => 'Tag has been sucessfully removed from child.',
					'errors' => [],
					'tags' => $tag_data
				]);
			} else {
				echo CJSON::encode([
					'status' => 0,
					'message' => 'Tag has been already removed from child.',
					'errors' => [],
					'tags' => []
				]);
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}
        
        public function actionAddTagToStaff() {
		if (Yii::app()->request->isAjaxRequest) {
			$model = StaffTagsMapping::model()->deleted()->findByAttributes(['tag_id' => $_POST['tag_id'], 'staff_id' => $_POST['staff_id']]);
			if (empty($model)) {
				$model = new StaffTagsMapping;
				$model->tag_id = $_POST['tag_id'];
				$model->staff_id = $_POST['staff_id'];
				if ($model->save()) {
					$tag_data = array();
					$staffAssignedTags = StaffTagsMapping::model()->deleted()->findAllByAttributes(['staff_id' => $_POST['staff_id']]);
					if (!empty($staffAssignedTags)) {
						foreach ($staffAssignedTags as $staffAssignedTag) {
							$tag_data[] = ['tag_id' => $staffAssignedTag->tag_id, 'tag_name' => $staffAssignedTag->tag->name, 'tag_color' => $staffAssignedTag->tag->color];
						}
					}
					echo CJSON::encode([
						'status' => 1,
						'message' => 'Tag has been sucessfully added to staff.',
						'errors' => [],
						'tags' => $tag_data
					]);
				}
			} else {
				echo CJSON::encode([
					'status' => 0,
					'message' => 'Tag is already assigned to staff.',
					'errors' => [],
					'tags' => []
				]);
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}
        
        public function actionDeleteTagFromStaff() {
		if (Yii::app()->request->isAjaxRequest) {
			$model = StaffTagsMapping::model()->deleted()->updateAll(['is_deleted' => 1], 'tag_id = :tag_id AND staff_id = :staff_id', [':tag_id' => $_POST['tag_id'], ':staff_id' => $_POST['staff_id']]);
			if ($model) {
				$tag_data = array();
				$staffAssignedTags = StaffTagsMapping::model()->deleted()->findAllByAttributes(['staff_id' => $_POST['staff_id']]);
				if (!empty($staffAssignedTags)) {
					foreach ($staffAssignedTags as $staffAssignedTag) {
						$tag_data[] = ['tag_id' => $staffAssignedTag->tag_id, 'tag_name' => $staffAssignedTag->tag->name, 'tag_color' => $staffAssignedTag->tag->color];
					}
				}
				echo CJSON::encode([
					'status' => 1,
					'message' => 'Tag has been sucessfully removed from staff.',
					'errors' => [],
					'tags' => $tag_data
				]);
			} else {
				echo CJSON::encode([
					'status' => 0,
					'message' => 'Tag has been already removed from staff.',
					'errors' => [],
					'tags' => []
				]);
			}
		} else {
			throw new CHttpException(404, 'Your request is not valid.');
		}
	}

}
