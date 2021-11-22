<?php

/**
 * This is the model class for table "tbl_hr_setting".
 *
 * The followings are the available columns in table 'tbl_hr_setting':
 * @property integer $id
 * @property integer $branch_id
 * @property integer $recursive_year
 * @property integer $holiday_number
 * @property integer $max_recursive_year
 * @property integer $holiday_year
 * @property double $reduction_hours
 */
class HrSetting extends CActiveRecord {

	CONST JANUARY_DECEMBER = 0;
	CONST APRIL_MARCH = 1;
	CONST SEPTEMBER_AUGUST = 2;

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'tbl_hr_setting';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('recursive_year, holiday_number', 'required'),
			array('branch_id, recursive_year, holiday_number, max_recursive_year, holiday_year', 'numerical', 'integerOnly' => true),
			array('reduction_hours', 'numerical'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, branch_id, recursive_year, holiday_number, max_recursive_year, holiday_year, reduction_hours', 'safe', 'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'branch_id' => 'Branch',
			'recursive_year' => 'Recursive Year',
			'holiday_number' => 'Holiday Number',
			'max_recursive_year' => 'Maximum Recursive Year',
			'holiday_year' => 'Holiday Year',
			'reduction_hours' => 'Reduction Hours',
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
		$criteria->compare('branch_id', $this->branch_id);
		$criteria->compare('recursive_year', $this->recursive_year);
		$criteria->compare('holiday_number', $this->holiday_number);
		$criteria->compare('max_recursive_year', $this->max_recursive_year);
		$criteria->compare('holiday_year',$this->holiday_year);
		$criteria->compare('reduction_hours',$this->reduction_hours);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return HrSetting the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public static function currentHrSettings(){
		$model = HrSetting::model()->findByAttributes([
			'branch_id' => Branch::currentBranch()->id
		]);
		return $model;
	}

}
