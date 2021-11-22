<?php

/**
 * This is the model class for table "tbl_child_medical_details".
 *
 * The followings are the available columns in table 'tbl_child_medical_details':
 * @property integer $id
 * @property integer $child_id
 * @property string $doctor_name
 * @property string $doctor_address
 * @property string $doctor_phone
 * @property string $medical_notes
 * @property string $has_convulsions
 * @property integer $has_calpol
 * @property integer $has_plaster
 * @property integer $has_measels
 * @property integer $has_mumps
 * @property integer $has_rubella
 * @property integer $has_mmr
 * @property integer $has_hib
 * @property integer $has_polio
 * @property integer $has_tetanus
 * @property integer $has_diptheria
 * @property integer $has_menc
 * @property integer $has_menb
 * @property integer $has_chickpox
 * @property integer $has_whopping_cough
 * @property integer $has_scarlet_fever
 * @property integer $has_rotavirus
 * @property integer $has_hepatitis_a
 * @property integer $has_hepatitis_b
 * @property integer $has_pneumonia
 * @property integer $has_visual_impairment
 * @property integer $has_respiratory_problems
 * @property integer $has_regular_medication
 * 
 * The followings are the available model relations:
 * @property ChildPersonalDetails $child
 */
class ChildMedicalDetails extends CActiveRecord {

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'tbl_child_medical_details';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('child_id', 'required'),
			array('child_id, has_calpol, has_plaster, has_measels, has_mumps, has_rubella, has_mmr, has_hib, has_polio, has_tetanus, has_diptheria, has_menc, has_menb ,has_chickpox, has_whopping_cough, has_scarlet_fever, has_rotavirus,has_hepatitis_a,has_hepatitis_b,has_hepatitis_b,has_visual_impairment,has_respiratory_problems,has_regular_medication', 'numerical', 'integerOnly' => true),
			array('doctor_name', 'length', 'max' => 45),
			array('doctor_phone', 'length', 'max' => 50),
			array('medical_notes', 'length', 'max' => 500),
			//array('doctor_phone','numerical', 'integerOnly' => true),
			array('doctor_address, has_convulsions', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, child_id, doctor_name, doctor_address, doctor_phone, medical_notes, has_convulsions, has_calpol, has_plaster, has_measels, has_mumps, has_rubella, has_mmr, has_hib, has_polio, has_tetanus, has_diptheria, has_menc,has_menb, has_chickpox, has_whopping_cough, has_scarlet_fever, has_rotavirus', 'safe', 'on' => 'search'),
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
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'child_id' => 'Child',
			'doctor_name' => 'Doctor Name',
			'doctor_address' => 'Doctor Address',
			'doctor_phone' => 'Doctor Phone',
			'medical_notes' => 'Medical Notes / Medication',
			'has_convulsions' => 'Convulsions',
			'has_calpol' => 'Calpol',
			'has_plaster' => 'Plasters',
			'has_measels' => 'Measles',
			'has_mumps' => 'Mumps',
			'has_rubella' => 'Rubella',
			'has_mmr' => 'MMR',
			'has_hib' => 'HIB',
			'has_polio' => 'Polio',
			'has_tetanus' => 'Tetanus',
			'has_diptheria' => 'Diptheria',
			'has_menc' => 'Men C',
			'has_menb' => 'Men B',
			'has_chickpox' => 'Chickpox',
			'has_whopping_cough' => 'Whooping Cough',
			'has_scarlet_fever' => 'Scarlet Fever',
			'has_rotavirus' => 'Rotavirus',
                        'has_hepatitis_a' => 'Hepatitis A',
                        'has_hepatitis_b' => 'Hepatitis b',
                        'has_pneumonia' => 'Pneumonia',
                        'has_visual_impairment' => 'Visual Impairment',
                        'has_respiratory_problems' => 'Respiratory Problems',
                        'has_regular_medication' => 'Regular Medication'
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
		$criteria->compare('doctor_name', $this->doctor_name, true);
		$criteria->compare('doctor_address', $this->doctor_address, true);
		$criteria->compare('doctor_phone', $this->doctor_phone, true);
		$criteria->compare('medical_notes', $this->medical_notes, true);
		$criteria->compare('has_convulsions', $this->has_convulsions, true);
		$criteria->compare('has_calpol', $this->has_calpol);
		$criteria->compare('has_plaster', $this->has_plaster);
		$criteria->compare('has_measels', $this->has_measels);
		$criteria->compare('has_mumps', $this->has_mumps);
		$criteria->compare('has_rubella', $this->has_rubella);
		$criteria->compare('has_mmr', $this->has_mmr);
		$criteria->compare('has_hib', $this->has_hib);
		$criteria->compare('has_polio', $this->has_polio);
		$criteria->compare('has_tetanus', $this->has_tetanus);
		$criteria->compare('has_diptheria', $this->has_diptheria);
		$criteria->compare('has_menc', $this->has_menc);
		$criteria->compare('has_menb', $this->has_menb);
		$criteria->compare('has_chickpox', $this->has_chickpox);
		$criteria->compare('has_whopping_cough', $this->has_whopping_cough);
		$criteria->compare('has_scarlet_fever', $this->has_scarlet_fever);
		$criteria->compare('has_rotavirus', $this->has_rotavirus);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return ChildMedicalDetails the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	protected function beforeSave() {
		$this->last_updated_by = Yii::app()->user->id;
		$this->last_updated = new CDbExpression('NOW()');
		return parent::beforeSave();
	}

	public function getColumnNames() {
		$unset_columns = array('id', 'child_id', 'last_updated', 'last_updated_by');
		$attributes = $this->getAttributes();
		return array_diff(array_keys($attributes), $unset_columns);
	}

	public function getFilter($column_name) {
		$response = array();
		if ($column_name == "doctor_name" || $column_name == "doctor_address" || $column_name == "doctor_phone" || $column_name == "medical_notes") {
			$response[$column_name] = array("filter_condition" => array('LIKE' => 'LIKE', 'LIKE %--%' => "LIKE %--%", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL', 'EMPTY' => 'EMPTY'), "filter_value" => array());
		} else {
			$response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '!=' => "NOT EQUAL TO"), "filter_value" => array(0 => 0, 1 => 1));
		}
		return $response;
	}

	public function getColumnValue($column_name, $column_value) {
		if ($column_name == "has_convulsions" || $column_name == "has_calpol" || $column_name == "has_plaster" || $column_name == "has_measels" || $column_name == "has_mumps" || $column_name == "has_rubella" || $column_name == "has_mmr" || $column_name == "has_hib" || $column_name == "has_polio" || $column_name == "has_tetanus" || $column_name == "has_diptheria" || $column_name == "has_menc" || $column_name == "has_menb" || $column_name == "has_chickpox" || $column_name == "has_whopping_cough" || $column_name == "has_scarlet_fever" || $column_name == "has_rotavirus") {
			$column_value = ($column_value == 1) ? "Yes" : "No";
		} else {
			$column_value = $column_value;
		}
		return $column_value;
	}

}
