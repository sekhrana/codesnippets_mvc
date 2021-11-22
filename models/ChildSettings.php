<?php

/**
 * This is the model class for table "tbl_child_settings".
 *
 * The followings are the available columns in table 'tbl_child_settings':
 * @property integer $id
 * @property integer $child_id
 * @property integer $invoice_generate_type
 * @property integer $invoice_generate_month_count
 * @property integer $is_strict_dates
 * @property integer $is_deleted
 * @property string $created
 * @property string $type
 * @property string $invoice_generate_month
 * @property integer $invoice_day
 * @property integer $invoice_due_day
 *
 * The followings are the available model relations:
 * @property TblChildPersonalDetails $child
 */
class ChildSettings extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'tbl_child_settings';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('child_id, invoice_generate_type,  is_strict_dates, type, invoice_day, invoice_due_day', 'required'),
			array('child_id, invoice_generate_type, invoice_generate_month_count, is_strict_dates, is_deleted, invoice_day, invoice_due_day', 'numerical', 'integerOnly'=>true),
            array('type', 'length', 'max'=>7),
            array('invoice_generate_month', 'length', 'max'=>4),
            array('invoice_generate_type', 'validateGenerationType'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, child_id, invoice_generate_type, invoice_generate_month_count, is_strict_dates, is_deleted, created , type, invoice_generate_month, invoice_day, invoice_due_day', 'safe', 'on'=>'search'),
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
			'child' => array(self::BELONGS_TO, 'ChildPersonalDetails', 'child_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'child_id' => 'Child',
			'invoice_generate_type' => 'Invoice Generate Type',
			'invoice_generate_month_count' => 'Invoice Generate Month Count',
			'is_strict_dates' => 'Generate invoice as per',
			'is_deleted' => 'Is Deleted',
			'created' => 'Created',
            'type' => 'Invoice Frequency',
            'invoice_generate_month' => 'Invoice For (Month)',
            'invoice_day' => 'Invoice Date',
            'invoice_due_day' => 'Invoice Due Date',
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
		$criteria->compare('child_id',$this->child_id);
		$criteria->compare('invoice_generate_type',$this->invoice_generate_type);
		$criteria->compare('invoice_generate_month_count',$this->invoice_generate_month_count);
		$criteria->compare('is_strict_dates',$this->is_strict_dates);
		$criteria->compare('is_deleted',$this->is_deleted);
		$criteria->compare('created',$this->created,true);
        $criteria->compare('type',$this->type,true);
        $criteria->compare('invoice_generate_month',$this->invoice_generate_month,true);
        $criteria->compare('invoice_day',$this->invoice_day);
        $criteria->compare('invoice_due_day',$this->invoice_due_day);


		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return ChildSettings the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
    
    public function validateGenerationType($attributes, $params) {
		if ($this->invoice_generate_type == InvoiceSetting::EQUAL_SPREAD_MONTHS && (empty($this->invoice_generate_month_count) || $this->invoice_generate_month_count == NULL)) {
			$this->addError('invoice_generate_month_count', 'Please Enter the month count.');
		}
	}
}
