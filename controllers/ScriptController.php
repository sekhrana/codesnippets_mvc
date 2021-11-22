<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ScriptController extends eyManController {

	public $layout = '//layouts/dashboard';

	public function filters() {
		return array(
			'rights',
		);
	}

	public function allowedActions() {
		return 'transferParents';
	}

	public function actionTransferParents() {
		ini_set("memory_limit", -1);
		ini_set("display_errors", true);
		$parentsModels = ChildParentalDetails::model()->findAll();
		if ($parentsModels) {
			foreach ($parentsModels as $model) {
				$transaction = Yii::app()->db->beginTransaction();
				try {
				$parentModel = new Parents;

				if (!empty($model->p1_first_name) || !empty($model->p1_last_name) || !empty($model->p1_email)) {
					$checkParentExists = new Parents;
					if (!empty($model->p1_email)) {
						$checkParentExists = Parents::model()->findByAttributes(['email' => $model->p1_email]);
					}
					if (isset($checkParentExists->id) && !empty($checkParentExists->id)) {
						$mapping = new ParentChildMapping;
						$mapping->child_id = $model->child_id;
						$mapping->parent_id = $checkParentExists->id;
						$mapping->order = 1;
						$mapping->is_bill_payer = $model->p1_is_bill_payer;
						$mapping->is_authorised = $model->p1_is_authorised;
						if (!$mapping->save()) {
							throw new JsonException("Error saving mapping for parent 1 for child - '.$model->child_id".CJSON::encode($mapping->getErrors()));
						}
					} else {
						$parentModel = new Parents;
						foreach ($parentModel->attributes as $key => $value) {
							if ($model->hasAttribute('p1_' . $key)) {
								$parentModel->$key = $model->{'p1_' . $key};
							}
						}
						$parentModel->title = NULL;
						$titleModel = PickTitles::model()->findByAttributes([
							'name' => $model->p1_title
						]);
						if ($titleModel) {
							$parentModel->title = $titleModel->id;
						}
						if (!$parentModel->save()) {
							throw new JsonException("Error saving parent model - '.$parentModel->id".CJSON::encode($parentModel->getErrors()));
						}
						$mapping = new ParentChildMapping;
						$mapping->child_id = $model->child_id;
						$mapping->parent_id = $parentModel->id;
						$mapping->order = 1;
						$mapping->is_bill_payer = $model->p1_is_bill_payer;
						$mapping->is_authorised = $model->p1_is_authorised;
						if (!$mapping->save()) {
							throw new JsonException("Error saving mapping for parent 1 for child - '.$model->child_id".CJSON::encode($mapping->getErrors()));
						}
					}
				}

				/** Parent 2 * */
				if (!empty($model->p2_first_name) || !empty($model->p2_last_name) || !empty($model->p2_email)) {
					$checkParentExists = new Parents;
					if (!empty($model->p2_email)) {
						$checkParentExists = Parents::model()->findByAttributes(['email' => $model->p2_email]);
					}
					if (isset($checkParentExists->id) && !empty($checkParentExists->id)) {
						$mapping = new ParentChildMapping;
						$mapping->child_id = $model->child_id;
						$mapping->parent_id = $checkParentExists->id;
						$mapping->order = 2;
						$mapping->is_bill_payer = $model->p2_is_bill_payer;
						$mapping->is_authorised = $model->p2_is_authorised;
						if (!$mapping->save()) {
							throw new JsonException("Error saving mapping for parent 21 for child - '.$model->child_id".CJSON::encode($mapping->getErrors()));
						}
					} else {
						$parentModel = new Parents;
						foreach ($parentModel->attributes as $key => $value) {
							if ($model->hasAttribute('p2_' . $key)) {
								$parentModel->$key = $model->{'p2_' . $key};
							}
						}
						$parentModel->title = NULL;
						$titleModel = PickTitles::model()->findByAttributes([
							'name' => $model->p2_title
						]);
						if ($titleModel) {
							$parentModel->title = $titleModel->id;
						}
						if (!$parentModel->save()) {
							throw new JsonException("Error saving parent model - '.$parentModel->id".CJSON::encode($parentModel->getErrors()));
						}
						$mapping = new ParentChildMapping;
						$mapping->child_id = $model->child_id;
						$mapping->parent_id = $parentModel->id;
						$mapping->order = 2;
						$mapping->is_bill_payer = $model->p2_is_bill_payer;
						$mapping->is_authorised = $model->p2_is_authorised;
						if (!$mapping->save()) {
							throw new JsonException("Error saving mapping for parent 22 for child - '.$model->child_id".CJSON::encode($mapping->getErrors()));
						}
					}
				}
				/** Parent 3 * */
				if (!empty($model->p3_first_name) || !empty($model->p3_last_name) || !empty($model->p3_email)) {
					$checkParentExists = new Parents;
					if (!empty($model->p2_email)) {
						$checkParentExists = Parents::model()->findByAttributes(['email' => $model->p3_email]);
					}
					if (isset($checkParentExists->id) && !empty($checkParentExists->id)) {
						$mapping = new ParentChildMapping;
						$mapping->child_id = $model->child_id;
						$mapping->parent_id = $checkParentExists->id;
						$mapping->order = 3;
						$mapping->is_bill_payer = 0;
						$mapping->is_authorised = $model->p3_is_authorised;
						if (!$mapping->save()) {
							throw new JsonException("Error saving mapping for parent 3 for child - '.$model->child_id".CJSON::encode($mapping->getErrors()));
						}
					} else {
						$parentModel = new Parents;
						foreach ($parentModel->attributes as $key => $value) {
							if ($model->hasAttribute('p3_' . $key)) {
								$parentModel->$key = $model->{'p3_' . $key};
							}
						}
						$parentModel->title = NULL;
						$titleModel = PickTitles::model()->findByAttributes([
							'name' => $model->p3_title
						]);
						if ($titleModel) {
							$parentModel->title = $titleModel->id;
						}
						if (!$parentModel->save()) {
							throw new JsonException("Error saving parent model - '.$parentModel->id".CJSON::encode($parentModel->getErrors()));
						}
						$mapping = new ParentChildMapping;
						$mapping->child_id = $model->child_id;
						$mapping->parent_id = $parentModel->id;
						$mapping->order = 3;
						$mapping->is_bill_payer = 0;
						$mapping->is_authorised = $model->p3_is_authorised;
						if (!$mapping->save()) {
							throw new JsonException("Error saving mapping for parent 3 for child - '.$model->child_id".CJSON::encode($mapping->getErrors()));
						}
					}
				}
				/** Parent 4 * */
				if (!empty($model->p4_first_name) || !empty($model->p4_last_name) || !empty($model->p4_email)) {
					$checkParentExists = new Parents;
					if (!empty($model->p4_email)) {
						$checkParentExists = Parents::model()->findByAttributes(['email' => $model->p3_email]);
					}
					if (isset($checkParentExists->id) && !empty($checkParentExists->id)) {
						$mapping = new ParentChildMapping;
						$mapping->child_id = $model->child_id;
						$mapping->parent_id = $checkParentExists->id;
						$mapping->order = 4;
						$mapping->is_bill_payer = 0;
						$mapping->is_authorised = $model->p4_is_authorised;
						if (!$mapping->save()) {
							throw new JsonException("Error saving mapping for parent 4 for child - '.$model->child_id".CJSON::encode($mapping->getErrors()));
						}
					} else {
						$parentModel = new Parents;
						foreach ($parentModel->attributes as $key => $value) {
							if ($model->hasAttribute('p4_' . $key)) {
								$parentModel->$key = $model->{'p4_' . $key};
							}
						}
						$parentModel->title = NULL;
						$titleModel = PickTitles::model()->findByAttributes([
							'name' => $model->p4_title
						]);
						if ($titleModel) {
							$parentModel->title = $titleModel->id;
						}
						if (!$parentModel->save()) {
							throw new JsonException("Error saving parent model - '.$parentModel->id".CJSON::encode($parentModel->getErrors()));
						}
						$mapping = new ParentChildMapping;
						$mapping->child_id = $model->child_id;
						$mapping->parent_id = $parentModel->id;
						$mapping->order = 4;
						$mapping->is_bill_payer = 0;
						$mapping->is_authorised = $model->p4_is_authorised;
						if (!$mapping->save()) {
							throw new JsonException("Error saving mapping for parent 4 for child - '.$model->child_id".CJSON::encode($mapping->getErrors()));
						}
					}
				}
				/** Parent 4 * */
				if (!empty($model->p5_first_name) || !empty($model->p5_last_name) || !empty($model->p5_email)) {
					$checkParentExists = new Parents;
					if (!empty($model->p5_email)) {
						$checkParentExists = Parents::model()->findByAttributes(['email' => $model->p5_email]);
					}
					if (isset($checkParentExists->id) && !empty($checkParentExists->id)) {
						$mapping = new ParentChildMapping;
						$mapping->child_id = $model->child_id;
						$mapping->parent_id = $checkParentExists->id;
						$mapping->order = 5;
						$mapping->is_bill_payer = 0;
						$mapping->is_authorised = $model->p5_is_authorised;
						if (!$mapping->save()) {
							throw new JsonException("Error saving mapping for parent 5 for child - '.$model->child_id".CJSON::encode($mapping->getErrors()));
						}
					} else {
						$parentModel = new Parents;
						foreach ($parentModel->attributes as $key => $value) {
							if ($model->hasAttribute('p5_' . $key)) {
								$parentModel->$key = $model->{'p5_' . $key};
							}
						}
						$parentModel->title = NULL;
						$titleModel = PickTitles::model()->findByAttributes([
							'name' => $model->p5_title
						]);
						if ($titleModel) {
							$parentModel->title = $titleModel->id;
						}
						if (!$parentModel->save()) {
							throw new JsonException("Error saving parent model - '.$parentModel->id".CJSON::encode($parentModel->getErrors()));
						}
						$mapping = new ParentChildMapping;
						$mapping->child_id = $model->child_id;
						$mapping->parent_id = $parentModel->id;
						$mapping->order = 5;
						$mapping->is_bill_payer = 0;
						$mapping->is_authorised = $model->p5_is_authorised;
						if (!$mapping->save()) {
							throw new JsonException("Error saving mapping for parent 5 for child - '.$model->child_id".CJSON::encode($mapping->getErrors()));
						}
					}
				}
				/** Parent 5 * */
				if (!empty($model->p6_first_name) || !empty($model->p6_last_name) || !empty($model->p6_email)) {
					$checkParentExists = new Parents;
					if (!empty($model->p6_email)) {
						$checkParentExists = Parents::model()->findByAttributes(['email' => $model->p6_email]);
					}
					if (isset($checkParentExists->id) && !empty($checkParentExists->id)) {
						$mapping = new ParentChildMapping;
						$mapping->child_id = $model->child_id;
						$mapping->parent_id = $checkParentExists->id;
						$mapping->order = 6;
						$mapping->is_bill_payer = 0;
						$mapping->is_authorised = $model->p6_is_authorised;
						if (!$mapping->save()) {
							throw new JsonException("Error saving mapping for parent 6 for child - '.$model->child_id".CJSON::encode($mapping->getErrors()));
						}
					} else {
						$parentModel = new Parents;
						foreach ($parentModel->attributes as $key => $value) {
							if ($model->hasAttribute('p6_' . $key)) {
								$parentModel->$key = $model->{'p6_' . $key};
							}
						}
						$parentModel->title = NULL;
						$titleModel = PickTitles::model()->findByAttributes([
							'name' => $model->p6_title
						]);
						if ($titleModel) {
							$parentModel->title = $titleModel->id;
						}
						if (!$parentModel->save()) {
							throw new JsonException("Error saving parent model - '.$parentModel->id".CJSON::encode($parentModel->getErrors()));
						}
						$mapping = new ParentChildMapping;
						$mapping->child_id = $model->child_id;
						$mapping->parent_id = $parentModel->id;
						$mapping->order = 6;
						$mapping->is_bill_payer = 0;
						$mapping->is_authorised = $model->p6_is_authorised;
						if (!$mapping->save()) {
							throw new JsonException("Error saving mapping for parent 6 for child - '.$model->child_id".CJSON::encode($mapping->getErrors()));
						}
					}
				}
				$transaction->commit();
				echo "Details saved successfuly - ".$model->id."</br> ";
				} catch (JsonException $ex) {
					$transaction->rollback();
					echo $ex->getMessage()."</br>";
				}
			}
		} else {
			echo "Parents Not Found";
		}
	}

	public function actionEylogIntegrationIssue(){
		$models = EylogParent::model()->findAll();
		foreach($models as $model){
			$eyManParent = Parents::model()->find([
				'condition' => 'first_name = :first_name AND last_name = :last_name AND email = :email',
				'params' => [
					':first_name' => $model->parent_first_name,
					':last_name' => $model->parent_last_name,
					':email' => $model->email
				]
			]);
			if(!empty($eyManParent)){
				$model->external_reference = "eyman-".$eyManParent->id;
				$model->save(false);
				echo "Parent Found for - ".$model->parent_id."</br>";
			} else {
				echo "Parent Not Found for - ".$model->parent_id."</br>";
			}
		}
	}

}
