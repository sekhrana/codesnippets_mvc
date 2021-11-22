<?php

/**
 * This is the model class for table "tbl_parent_child_mapping".
 *
 * The followings are the available columns in table 'tbl_parent_child_mapping':
 * @property integer $id
 * @property integer $child_id
 * @property integer $parent_id
 * @property integer $order
 * @property integer $is_emergency_contact
 * @property integer $is_bill_payer
 * @property integer $is_authorised
 * @property integer $is_deleted
 * @property string $created
 * @property integer $created_by
 * @property string $updated
 * @property integer $updated_by
 *
 * The followings are the available model relations:
 * @property ChildPersonalDetails $child
 * @property Parent $parent
 */
class ParentChildMapping extends CActiveRecord {

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'tbl_parent_child_mapping';
	}

	public function defaultScope() {
		if (get_class(Yii::app()) === "CWebApplication") {
			return array(
				'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0",
			);
		}
		if (get_class(Yii::app()) === "CConsoleApplication") {
			return array(
				'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0",
			);
		}
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('child_id, parent_id', 'required'),
			array('child_id, parent_id, order, is_deleted, created_by, updated_by, is_emergency_contact, is_bill_payer, is_authorised', 'numerical', 'integerOnly' => true),
			array('created, updated', 'safe'),
			array('child_id, parent_id', 'validateMapping'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, child_id, parent_id, order, is_deleted, created, created_by, updated, updated_by, is_emergency_contact, is_bill_payer, is_authorised', 'safe', 'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'child' => array(self::BELONGS_TO, 'ChildPersonalDetails', 'child_id'),
			'childNds' => array(self::BELONGS_TO, 'ChildPersonalDetailsNds', 'child_id'),
			'parent' => array(self::BELONGS_TO, 'Parent', 'parent_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'child_id' => 'Child',
			'parent_id' => 'Parent',
			'order' => 'Order',
			'is_deleted' => 'Is Deleted',
			'created' => 'Created',
			'created_by' => 'Created By',
			'updated' => 'Updated',
			'updated_by' => 'Updated By',
			'is_emergency_contact' => 'Emergency Contact',
			'is_bill_payer' => 'Bill Payer',
			'is_authorised' => 'Authorised To Collect',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search() {
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id);
		$criteria->compare('child_id', $this->child_id);
		$criteria->compare('parent_id', $this->parent_id);
		$criteria->compare('order', $this->order);
		$criteria->compare('is_deleted', $this->is_deleted);
		$criteria->compare('created', $this->created, true);
		$criteria->compare('created_by', $this->created_by);
		$criteria->compare('updated', $this->updated, true);
		$criteria->compare('updated_by', $this->updated_by);
		$criteria->compare('is_emergency_contact', $this->is_emergency_contact);
		$criteria->compare('is_bill_payer', $this->is_bill_payer);
		$criteria->compare('is_authorised', $this->is_authorised);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return ParentChildMapping the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public function validateMapping($attributes, $params) {
		if ($this->isNewRecord) {
			$model = ParentChildMapping::model()->findByAttributes([
				'parent_id' => $this->parent_id,
				'child_id' => $this->child_id
			]);
			if (!empty($model)) {
				$this->addError('child_id', 'Parent Child mapping already exists.');
			}
		} else {
			$model = ParentChildMapping::model()->findAll([
				'condition' => 'parent_id = :parent_id AND child_id = :child_id AND id != :id',
				'params' => [
					':parent_id' => $this->parent_id,
					':child_id' => $this->child_id,
					':id' => $this->id
				]
			]);
			if ($model) {
				$this->addError('child_id', 'Parent Child mapping already exists.');
			}
		}
	}

	public function beforeSave() {
		if (get_class(Yii::app()) === "CWebApplication") {
			if ($this->isNewRecord) {
				$this->created_by = Yii::app()->user->id;
				$this->created = new CDbExpression("NOW()");
			} else {
				$this->updated_by = Yii::app()->user->id;
				$this->updated = new CDbExpression("NOW()");
			}
		}
		return parent::beforeSave();
	}

}
