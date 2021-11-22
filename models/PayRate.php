<?php

/**
 * This is the model class for table "tbl_pay_rate".
 *
 * The followings are the available columns in table 'tbl_pay_rate':
 * @property integer $id
 * @property string $payroll_id
 * @property string $week_number
 * @property string $total_hours
 * @property string $pay_rate
 */
class PayRate extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'tbl_pay_rate';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('payroll_id, week_number, total_hours, pay_rate', 'required'),
			array('payroll_id, week_number, total_hours, pay_rate', 'length', 'max'=>45),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, payroll_id, week_number, total_hours, pay_rate', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'payroll_id' => 'Payroll',
			'week_number' => 'Week Number',
			'total_hours' => 'Total Hours',
			'pay_rate' => 'Pay Rate',
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
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('payroll_id',$this->payroll_id,true);
		$criteria->compare('week_number',$this->week_number,true);
		$criteria->compare('total_hours',$this->total_hours,true);
		$criteria->compare('pay_rate',$this->pay_rate,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return PayRate the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
