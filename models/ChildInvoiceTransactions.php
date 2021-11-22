<?php

/**
 * This is the model class for table "tbl_child_invoice_transactions".
 *
 * The followings are the available columns in table 'tbl_child_invoice_transactions':
 * @property integer $id
 * @property integer $invoice_id
 * @property string $payment_refrence
 * @property double $invoice_amount
 * @property double $paid_amount
 * @property string $date_of_payment
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $payment_mode
 * @property integer $is_deleted
 * @property integer $credit_note_id
 * @property integer $payment_id
 * @property integer $is_refund
 * @property integer $status
 * @property string $pg_status
 * @property string $pg_transaction_id
 * @property integer $parent_id
 *
 * The followings are the available model relations:
 * @property ChildInvoice $invoice
 * @property Parents $parent
 */
class ChildInvoiceTransactions extends CActiveRecord {

	public $date_columns = array('date_of_payment');
	public $total_paid_amount;

	const PAYMENT_MODE_GOCARDLESS = 15;
	// constants for pg_status fields
	const PG_STATUS_PROCESSED = 'processed';
	const PG_STATUS_PENDING_CUSTOMER_APPROVAL = 'pending_customer_approval';
	const PG_STATUS_PENDING_SUBMISSION = 'pending_submission';
	const PG_STATUS_SUBMITTED = 'submitted';
	const PG_STATUS_CONFIRMED = 'confirmed';
	const PG_STATUS_PAID_OUT = 'paid_out';
	const PG_STATUS_CANCELLED = 'cancelled';
	const PG_STATUS_CUSTOMER_APPROVAL_DENIED = 'customer_approval_denied';
	const PG_STATUS_FAILED = 'failed';
	const PG_STATUS_CHARGED_BACK = 'charged_back';
	const PG_STATUS_MANUALLY_DELETED = 'manually_deleted';

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'tbl_child_invoice_transactions';
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
			array('invoice_id, invoice_amount, paid_amount, payment_mode', 'required'),
			array('invoice_id, payment_mode, is_deleted, credit_note_id, payment_id, created_by, updated_by, is_refund, status, parent_id', 'numerical', 'integerOnly' => true),
			array('invoice_amount, paid_amount, parent_id', 'numerical'),
			array('payment_refrence, pg_status, pg_transaction_id', 'length', 'max' => 255),
			array('date_of_payment, created, updated, parent_id, pg_status, pg_transaction_id', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, invoice_id, payment_refrence, invoice_amount, paid_amount, date_of_payment, created, payment_mode, is_deleted, credit_note_id, payment_id, updated, created_by, updated_by, is_refund, status, parent_id, pg_status, pg_transaction_id', 'safe', 'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'invoice' => array(self::BELONGS_TO, 'ChildInvoice', 'invoice_id'),
			'parent' => array(self::BELONGS_TO, 'Parents', 'parent_id'),
		);
	}

	public function behaviors() {
		return array(
			'dateFormatter' => array(
				'class' => 'application.components.DateFormatter',
				'date_columns' => array('date_of_payment')
			)
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'invoice_id' => 'Invoice',
			'payment_refrence' => 'Payment Reference',
			'invoice_amount' => 'Invoice Amount',
			'paid_amount' => 'Amount Paid',
			'date_of_payment' => 'Date Of Payment',
			'created' => 'Created',
			'updated' => 'Updated',
			'created_by' => 'Created By',
			'updated_by' => 'Updated By',
			'payment_mode' => 'Payment Mode',
			'is_deleted' => 'Is Deleted',
			'credit_note_id' => 'Credit Note',
			'payment_id' => 'Payment',
			'is_refund' => 'Is Refund',
			'status' => 'Status',
			'parent_id' => 'Parent',
			'pg_status' => 'Payment Gateway Status',
			'pg_transaction_id' => 'Payment Gateway Transaction Id',
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
		$criteria->compare('invoice_id', $this->invoice_id);
		$criteria->compare('payment_refrence', $this->payment_refrence, true);
		$criteria->compare('invoice_amount', $this->invoice_amount);
		$criteria->compare('paid_amount', $this->paid_amount);
		$criteria->compare('date_of_payment', $this->date_of_payment, true);
		$criteria->compare('created', $this->created, true);
		$criteria->compare('updated', $this->updated, true);
		$criteria->compare('created_by', $this->created_by);
		$criteria->compare('updated_by', $this->updated_by);
		$criteria->compare('payment_mode', $this->payment_mode);
		$criteria->compare('is_deleted', $this->is_deleted);
		$criteria->compare('credit_note_id', $this->credit_note_id);
		$criteria->compare('payment_id', $this->payment_id);
		$criteria->compare('is_refund', $this->is_refund);
		$criteria->compare('status', $this->status);
		$criteria->compare('pg_status', $this->pg_status, true);
		$criteria->compare('pg_transaction_id', $this->pg_transaction_id, true);
		$criteria->compare('parent_id',$this->parent_id);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return ChildInvoiceTransactions the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public function getColumnNames() {
		$unset_columns = array('id', 'created');
		$attributes = $this->getAttributes();
		return array_diff(array_keys($attributes), $unset_columns);
	}

	public function getFilter($column_name) {
		$response = array();
		if ($column_name == "date_of_payment") {
			$response[$column_name] = array("filter_condition" => array('=' => 'EQUAL', '>' => "GREATER THAN", '<' => 'SMALLER THAN', '>=' => 'GREATER THAN EQUAL TO', '<=' => 'SMALLER THAN EQUAL TO', 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => array());
		} else if ($column_name == "payment_mode") {
			$response[$column_name] = array("filter_condition" => array('=' => 'EQUAL', '!=' => "NOT EQUAL"), "filter_value" => customFunctions::getPaymentOptions());
		} else {
			$response[$column_name] = array("filter_condition" => array('LIKE' => 'LIKE', 'LIKE %--%' => "LIKE %--%", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL', 'EMPTY' => 'EMPTY'), "filter_value" => array());
		}
		return $response;
	}

	public function getColumnValue($column_name, $column_value) {

		if ($column_name == "payment_mode") {
			$column_value = customFunctions::getPaymentOptions()[$column_value];
		}
		if ($column_name == "invoice_id") {
			$column_value = ChildInvoice::model()->findByPk($column_value)->invoiceurn;
		}
		return $column_value;
	}

	public function getRelatedAttributes() {
		$attributes = array();
		$attributes['ChildInvoice'] = ChildInvoice::model()->getRelatedAttributesNames();
		return $attributes;
	}

	public function beforeSave() {
		if($this->is_deleted == 1){
			$this->pg_status = self::PG_STATUS_MANUALLY_DELETED;
		}
		if (get_class(Yii::app()) === "CWebApplication") {
			if ($this->isNewRecord) {
				$this->created_by = Yii::app()->user->id;
				$this->created = new CDbExpression("NOW()");
			} else {
				$this->updated_by = Yii::app()->user->id;
				$this->updated = new CDbExpression("NOW()");
			}
		}
		if (get_class(Yii::app()) === "CConsoleApplication") {
			if ($this->isNewRecord) {
				$this->created_by = User::model()->findByEmail(Yii::app()->params['superAdminEmailId'][0]);
				$this->created = new CDbExpression("NOW()");
			} else {
				$this->updated_by = User::model()->findByEmail(Yii::app()->params['superAdminEmailId'][0]);
				$this->updated = new CDbExpression("NOW()");
			}
		}

		return parent::beforeSave();
	}

}
