<?php

/**
 * This is the model class for table "tbl_staff_tags_mapping".
 *
 * The followings are the available columns in table 'tbl_staff_tags_mapping':
 * @property integer $id
 * @property integer $tag_id
 * @property integer $staff_id
 * @property integer $is_deleted
 *
 * The followings are the available model relations:
 * @property StaffPersonalDetails $staff
 * @property Tags $tag
 */
class StaffTagsMapping extends CActiveRecord {

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'tbl_staff_tags_mapping';
	}
	
	public function scopes() {
		return [
			'deleted' => [
				'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0",
			]
		];
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('tag_id, staff_id', 'required'),
			array('tag_id, staff_id, is_deleted', 'numerical', 'integerOnly' => true),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, tag_id, staff_id, is_deleted', 'safe', 'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'staff' => array(self::BELONGS_TO, 'StaffPersonalDetails', 'staff_id'),
			'tag' => array(self::BELONGS_TO, 'Tags', 'tag_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'tag_id' => 'Tag',
			'staff_id' => 'Staff',
			'is_deleted' => 'Is Deleted',
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
		$criteria->compare('tag_id', $this->tag_id);
		$criteria->compare('staff_id', $this->staff_id);
		$criteria->compare('is_deleted', $this->is_deleted);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return StaffTagsMapping the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

}
