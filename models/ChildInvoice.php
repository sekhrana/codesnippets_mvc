<?php

// Demo Commit
/**
 * This is the model class for table "tbl_child_invoice".
 *
 * The followings are the available columns in table 'tbl_child_invoice':
 *
 * @property integer $id
 * @property integer $child_id
 * @property integer $branch_id
 * @property string $from_date
 * @property string $to_date
 * @property string $due_date
 * @property string $status
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 * @property double $total
 * @property string $access_token
 * @property integer $is_email_sent
 * @property integer $invoice_type
 * @property string $invoice_date
 * @property integer $is_regenrated
 * @property integer $is_deposit
 * @property string $urn_prefix
 * @property string $urn_number
 * @property string $urn_suffix
 * @property string $description
 * @property integer $credit_note_invoice_id
 * @property integer $is_deleted
 * @property integer $credit_note_payment_id
 * @property integer $is_monthly_invoice
 * @property integer $is_money_received
 * @property integer $payment_mode
 * @property string $month
 * @property string $year
 * @property integer $is_email_sent_2
 * @property string $email_1_mandrill_id
 * @property string $email_2_mandrill_id
 * @property integer $is_locked The followings are the available model relations:
 * @property Branch $branch
 * @property Child $childNds
 * @property ChildPersonalDetails $child
 * @property ChildInvoiceDetails[] $childInvoiceDetail
 * @property ChildInvoiceDetails[] $childInvoiceDetails
 * @property ChildInvoiceTransactions[] $childInvoiceTransactions
 */
class ChildInvoice extends CActiveRecord {

	public $invoice_total;
	public $invoice_month;
  public $pending;
  public $balance;
	public $date_columns = array(
		'from_date',
		'to_date',
		'due_date',
		'invoice_date'
	);
	public $last_urn;
	public $total_deposit_amount;
	public $deposit_id;
	public $child_search;
	public $parent_search;
	public $amount_pending;

	const AUTOMATIC_INVOICE = 0;
	const MANUAL_INVOICE = 1;
	const AUTOMATIC_CREDIT_NOTE = 2;
	const CREDIT_NOTE = 3;
	const DEPOSIT = 1;
	const MONEY_RECEIVED = 0;
	const PENDING_PAYMENT = "AWAITING_PAYMENT";
	const PAID = "PAID";
	const ALLOCATED = "ALLOCATED";
	const NOT_ALLOCATED = "NOT_ALLOCATED";
	const GOCARDLESS_PAYMENT_MODE_ID = 15;

	public function defaultScope() {
		if (get_class(Yii::app()) === "CWebApplication") {
			if (Yii::app()->session['role'] == "superAdmin") {
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0"
				);
			}

			if (Yii::app()->session['role'] == "companyAdministrator") {
				$company_id = UserBranchMapping::model()->findByAttributes(array(
						'user_id' => Yii::app()->user->getId()
					))->company_id;
				$branchString = implode(',', customFunctions::getBranchesByCompany($company_id));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")"
				);
			}

			if (Yii::app()->session['role'] == "areaManager") {
				$userMappingModal = UserBranchMapping::model()->findAllByAttributes(array(
					'user_id' => Yii::app()->user->getId()
				));
				$branchString = implode(',', customFunctions::getBranchesOfAreaManager($userMappingModal));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")"
				);
			}
			if (Yii::app()->session['role'] == "branchManager") {
				$userMapping = UserBranchMapping::model()->findByAttributes(array(
					'user_id' => Yii::app()->user->getId()
				));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id =" . $userMapping->branch_id
				);
			}
			if (Yii::app()->session['role'] == "branchAdmin") {
				$userMapping = UserBranchMapping::model()->findByAttributes(array(
					'user_id' => Yii::app()->user->getId()
				));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id =" . $userMapping->branch_id
				);
			}
			if (Yii::app()->session['role'] == "staff") {
				$branchId = UserBranchMapping::model()->findByAttributes(array(
						'user_id' => Yii::app()->user->id
					))->branch_id;
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id =" . $branchId
				);
			}
			if (Yii::app()->session['role'] == "hrAdmin" || Yii::app()->session['role'] == User::HR_STANDARD) {
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0"
				);
			}
			if (Yii::app()->session['role'] == "accountsAdmin") {
				$company_id = UserBranchMapping::model()->findByAttributes(array(
						'user_id' => Yii::app()->user->getId()
					))->company_id;
				$branchString = implode(',', customFunctions::getBranchesByCompany($company_id));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")"
				);
			}
			if (Yii::app()->session['role'] == "parent") {
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0"
				);
			}
		}
		if (get_class(Yii::app()) === "CConsoleApplication") {
			return array(
				'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0"
			);
		}
	}

	/**
	 *
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'tbl_child_invoice';
	}

	/**
	 *
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array(
				'urn_number, child_id, branch_id, invoice_date, due_date, access_token',
				'required'
			),
			array(
				'urn_number, child_id, branch_id, invoice_date, access_token, total, description',
				'required',
				'on' => 'credit_note'
			),
			array(
				'is_money_received',
				'validateCreditNote',
				'on' => 'credit_note'
			),
			array(
				'child_id, branch_id, invoice_type, is_email_sent, is_regenrated, credit_note_invoice_id, is_deposit, is_deleted, credit_note_payment_id, is_monthly_invoice, created_by, updated_by, is_money_received, payment_mode, is_locked, is_email_sent_2',
				'numerical',
				'integerOnly' => true
			),
			array(
				'total',
				'numerical'
			),
			array(
				'status',
				'length',
				'max' => 25
			),
			array(
				'access_token, description, email_1_mandrill_id, email_2_mandrill_id',
				'length',
				'max' => 255
			),
			array(
				'urn_prefix, urn_number, urn_suffix, month, year',
				'length',
				'max' => 45
			),
			array(
				'created, from_date, to_date, year, updated, created, amount_pending',
				'safe'
			),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array(
				'id, child_id, urn_prefix, urn_number, urn_suffix, branch_id, invoice_type, description, from_date, invoice_date, to_date, due_date, status, created, total, access_token, is_email_sent, is_regenrated, invoice_month, invoice_urn, year, amount_pending, amount_paid, credit_note_invoice_id, is_deleted, credit_note_payment_id, is_monthly_invoice, updated, created_by, updated_by,is_money_received, payment_mode, month, year, is_locked, is_email_sent_2, email_1_mandrill_id, email_2_mandrill_id',
				'safe',
				'on' => 'search'
			),
			array(
				'id, child_id, urn_prefix, urn_number, urn_suffix, branch_id, invoice_type, description, from_date, invoice_date, to_date, due_date, status, created, total, access_token, is_email_sent, is_regenrated, invoice_month, invoice_urn, year, amount_pending, amount_paid, credit_note_invoice_id, is_deleted, credit_note_payment_id, is_monthly_invoice, updated, created_by, updated_by,is_money_received, payment_mode, month, year, is_locked, is_email_sent_2, email_1_mandrill_id, email_2_mandrill_id, child_search',
				'safe',
				'on' => 'invoiceList'
			)
		);
	}

	/**
	 *
	 * @return array relational rules.
	 */
	public function relations() {
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'branch' => array(
				self::BELONGS_TO,
				'Branch',
				'branch_id'
			),
			'child' => array(
				self::BELONGS_TO,
				'ChildPersonalDetails',
				'child_id'
			),
			'childNds' => array(
				self::BELONGS_TO,
				'ChildPersonalDetailsNds',
				'child_id'
			),
			'childInvoiceDetails' => array(
				self::HAS_MANY,
				'ChildInvoiceDetails',
				'invoice_id'
			),
			'childInvoiceDetail' => array(
				self::HAS_ONE,
				'ChildInvoiceDetails',
				'invoice_id'
			),
			'childInvoiceTransactions' => array(
				self::HAS_MANY,
				'ChildInvoiceTransactions',
				'invoice_id'
			)
		);
	}

	public function behaviors() {
		return array(
			'dateFormatter' => array(
				'class' => 'application.components.DateFormatter',
				'date_columns' => array(
					'from_date',
					'to_date',
					'due_date',
					'invoice_date'
				)
			)
		);
	}

	/**
	 *
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'invoice_type' => 'Invoice Type',
			'urn_prefix' => 'Urn Prefix',
			'urn_number' => 'Urn Number',
			'urn_suffix' => 'Urn Suffix',
			'child_id' => 'Child',
			'branch_id' => 'Branch',
			'from_date' => 'From Date',
			'to_date' => 'To Date',
			'due_date' => 'Due Date',
			'status' => 'Status',
			'created' => 'Created',
			'updated' => 'Updated',
			'created_by' => 'Created By',
			'updated_by' => 'Updated By',
			'total' => 'Total',
			'is_deposit' => 'Deposit',
			'access_token' => 'Access Token',
			'is_email_sent' => 'Email Sent - Parent 1',
			'invoice_date' => 'Invoice Date',
			'is_regenrated' => 'Regenerate',
			'year' => 'Year',
			'description' => 'Description',
			'credit_note_invoice_id' => 'Credit Note Invoice',
			'is_deleted' => 'Is Deleted',
			'credit_note_payment_id' => 'Credit Note Payment',
			'is_monthly_invoice' => 'Is Monthly Invoice',
			'is_money_received' => 'Money Received',
			'payment_mode' => 'Payment Mode',
			'month' => 'Month',
			'year' => 'Year',
			'is_locked' => 'Is Locked',
			'is_email_sent_2' => 'Email Sent - Parent 1',
			'email_1_mandrill_id' => 'Email 1  - Mandrill ID',
			'email_2_mandrill_id' => 'Email 2 - Mandrill ID',
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
	 *         based on the search/filter conditions.
	 */
	public function search($pagesize = true) {;
      if($pagesize){
        $pagesize = 50;
      }else{
        $pagesize = 500;
      }
		// @todo Please modify the following code to remove attributes that should not be searched.
		$criteria = new CDbCriteria();
		$criteria->compare('id', $this->id);
		$criteria->compare('child_id', $this->child_id);
		$criteria->compare('branch_id', $this->branch_id);
		$criteria->compare('urn_prefix', $this->urn_prefix, true);
		$criteria->compare('urn_number', $this->urn_number, true);
		$criteria->compare('urn_suffix', $this->urn_suffix, true);
		$criteria->compare('invoice_type', $this->invoice_type);
		$criteria->compare('invoice_date', $this->invoice_date, true);
		$criteria->compare('from_date', $this->from_date, true);
		$criteria->compare('to_date', $this->to_date, true);
		$criteria->compare('due_date', $this->due_date, true);
		$criteria->compare('status', $this->status, true);
		$criteria->compare('created', $this->created, true);
		$criteria->compare('updated', $this->updated, true);
		$criteria->compare('created_by', $this->created_by);
		$criteria->compare('updated_by', $this->updated_by);
		$criteria->compare('total', $this->total);
		$criteria->compare('access_token', $this->access_token, true);
		$criteria->compare('is_email_sent', $this->is_email_sent);
		$criteria->compare('invoice_date', $this->invoice_date, true);
		$criteria->compare('is_deposit', $this->is_deposit);
		$criteria->compare('is_regenrated', 0); // To hide the regenerated invoice from the user
		$criteria->compare('description', $this->description, true);
		$criteria->compare('credit_note_invoice_id', $this->credit_note_invoice_id);
		$criteria->compare('is_deleted', $this->is_deleted);
		$criteria->compare('credit_note_payment_id', $this->credit_note_payment_id);
		$criteria->compare('is_monthly_invoice', $this->is_monthly_invoice);
		$criteria->compare('is_money_received', $this->is_money_received);
		$criteria->compare('payment_mode', $this->payment_mode);
		$criteria->compare('is_locked', $this->is_locked);
		$criteria->compare('month', $this->month, true);
		$criteria->compare('year', $this->year, true);
		$criteria->compare('is_email_sent_2', $this->is_email_sent_2);
		$criteria->compare('email_1_mandrill_id',$this->email_1_mandrill_id,true);
		$criteria->compare('email_2_mandrill_id',$this->email_2_mandrill_id,true);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'pagination' => array(
				'pageSize' => $pagesize
			),
			'sort' => array(
				'defaultOrder' => 'invoice_date DESC'
			)
		));
	}

	public function invoiceList() {
		// @todo Please modify the following code to remove attributes that should not be searched.
		$criteria = new CDbCriteria();
		$criteria->with = array('childNds');
		$criteria->compare('t.id', $this->id);
		$criteria->compare('t.child_id', $this->child_id);
		$criteria->compare('childNds.first_name', $this->child_search, true);
		$criteria->compare('childNds.is_deleted', 0);
		$criteria->compare('t.branch_id', Branch::currentBranch()->id);
		$criteria->compare('t.urn_prefix', $this->urn_prefix, true);
		$criteria->compare('t.urn_number', $this->urn_number, true);
		$criteria->compare('t.urn_suffix', $this->urn_suffix, true);
		$criteria->compare('t.invoice_date', $this->invoice_date, true);
		$criteria->compare('t.from_date', $this->from_date, true);
		$criteria->compare('t.to_date', $this->to_date, true);
		$criteria->compare('t.due_date', $this->due_date, true);
		$criteria->compare('t.status', $this->status, true);
		$criteria->compare('t.created', $this->created, true);
		$criteria->compare('t.updated', $this->updated, true);
		$criteria->compare('t.created_by', $this->created_by);
		$criteria->compare('t.updated_by', $this->updated_by);
		$criteria->compare('t.total', $this->total);
		$criteria->compare('t.access_token', $this->access_token, true);
		$criteria->compare('t.is_email_sent', $this->is_email_sent);
		$criteria->compare('t.invoice_date', $this->invoice_date, true);
		$criteria->compare('t.is_regenrated', 0); // To hide the regenerated invoice from the user
		$criteria->compare('t.description', $this->description, true);
		$criteria->compare('t.credit_note_invoice_id', $this->credit_note_invoice_id);
		$criteria->compare('t.is_deleted', 0);
		$criteria->compare('t.credit_note_payment_id', $this->credit_note_payment_id);
		$criteria->compare('t.is_monthly_invoice', $this->is_monthly_invoice);
		$criteria->compare('t.is_money_received', $this->is_money_received);
		$criteria->compare('t.payment_mode', $this->payment_mode);
		$criteria->compare('t.is_locked', $this->is_locked);
		$criteria->compare('t.month', $this->month, true);
		$criteria->compare('t.year', $this->year, true);
		$criteria->addInCondition('t.invoice_type', array(
			0,
			1
		));
		$criteria->together = true;
		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'pagination' => array(
				'pageSize' => 20
			),
			'sort' => array(
				'attributes' => array(
					'child_search' => array(
						'asc' => 'childNds.first_name',
						'desc' => 'childNds.first_name DESC'
					),
					'*'
				),
				'defaultOrder' => 'childNds.first_name, childNds.last_name, t.total, t.invoice_date'
			)
		));
	}

		public function invoiceListForGC() {
		// @todo Please modify the following code to remove attributes that should not be searched.
		$criteria = new CDbCriteria();
		$criteria->with = array('childNds');
		$criteria->compare('t.id', $this->id);
		$criteria->compare('t.child_id', $this->child_id);
		$criteria->compare('childNds.first_name', $this->child_search, true);
		$criteria->compare('childNds.is_deleted', 0);
		$criteria->compare('t.branch_id', Branch::currentBranch()->id);
		$criteria->compare('t.urn_prefix', $this->urn_prefix, true);
		$criteria->compare('t.urn_number', $this->urn_number, true);
		$criteria->compare('t.urn_suffix', $this->urn_suffix, true);
		$criteria->compare('t.invoice_date', $this->invoice_date, true);
		$criteria->compare('t.from_date', $this->from_date, true);
		$criteria->compare('t.to_date', $this->to_date, true);
		$criteria->compare('t.due_date', $this->due_date, true);
		$criteria->compare('t.status', $this->status, true);
		$criteria->compare('t.created', $this->created, true);
		$criteria->compare('t.updated', $this->updated, true);
		$criteria->compare('t.created_by', $this->created_by);
		$criteria->compare('t.updated_by', $this->updated_by);
		$criteria->compare('t.total', $this->total);
		$criteria->compare('t.access_token', $this->access_token, true);
		$criteria->compare('t.is_email_sent', $this->is_email_sent);
		$criteria->compare('t.invoice_date', $this->invoice_date, true);
		$criteria->compare('t.is_regenrated', 0); // To hide the regenerated invoice from the user
		$criteria->compare('t.description', $this->description, true);
		$criteria->compare('t.credit_note_invoice_id', $this->credit_note_invoice_id);
		$criteria->compare('t.is_deleted', 0);
		$criteria->compare('t.credit_note_payment_id', $this->credit_note_payment_id);
		$criteria->compare('t.is_monthly_invoice', $this->is_monthly_invoice);
		$criteria->compare('t.is_money_received', $this->is_money_received);
		$criteria->compare('t.payment_mode', $this->payment_mode);
		$criteria->compare('t.is_locked', $this->is_locked);
		$criteria->compare('t.month', $this->month, true);
		$criteria->compare('t.year', $this->year, true);
		$criteria->addCondition('t.total > 0', 'AND');
		$criteria->addInCondition('t.invoice_type', array(
			0,
			1
		));
		$criteria->together = true;
		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'pagination' => array(
				'pageSize' => 20
			),
			'sort' => array(
				'attributes' => array(
					'child_search' => array(
						'asc' => 'childNds.first_name',
						'desc' => 'childNds.first_name DESC'
					),
					'*'
				),
				'defaultOrder' => 'childNds.first_name, childNds.last_name, t.total, t.invoice_date'
			)
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 *
	 * @param string $className
	 *            active record class name.
	 * @return ChildInvoice the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public function getColumnNames() {
		$unset_columns = array(
			'id',
			'branch_id',
			'created',
			'access_token',
			'urn_prefix',
			'urn_number',
			'urn_suffix'
		);
		$attributes = $this->getAttributes();
		return array_diff(array_keys($attributes), $unset_columns);
	}

	public function getFilter($column_name) {
		$response = array();
		if ($column_name == "is_email_sent" || $column_name == "is_regenrated") {
			$response[$column_name] = array(
				"filter_condition" => array(
					'=' => 'EQUAL TO',
					'!=' => "NOT EQUAL TO"
				),
				"filter_value" => array(
					0 => 0,
					1 => 1
				)
			);
		} else
		if ($column_name == "child_id") {
			$response[$column_name] = array(
				"filter_condition" => array(
					'=' => 'EQUAL TO',
					'!=' => "NOT EQUAL TO",
					'IS NULL' => 'IS NULL',
					'IS NOT NULL' => 'IS NOT NULL'
				),
				"filter_value" => CHtml::listData(ChildPersonalDetails::model()->findAllByAttributes(array(
						'branch_id' => Yii::app()->session['branch_id']
					)), 'id', 'first_name')
			);
		} else
		if ($column_name == "from_date" || $column_name == "to_date" || $column_name == "due_date" || $column_name == "invoice_date") {
			$response[$column_name] = array(
				"filter_condition" => array(
					'=' => 'EQUAL TO',
					'>' => "GREATER THAN",
					'<' => 'SMALLER THAN',
					'>=' => 'GREATER THAN EQUAL TO',
					'<=' => 'SMALLER THAN EQUAL TO',
					'IS NULL' => 'IS NULL',
					'IS NOT NULL' => 'IS NOT NULL'
				),
				"filter_value" => array()
			);
		} else
		if ($column_name == "invoice_type") {
			$response[$column_name] = array(
				"filter_condition" => array(
					'=' => 'EQUAL TO',
					'!=' => "NOT EQUAL TO",
					'IS NULL' => 'IS NULL',
					'IS NOT NULL' => 'IS NOT NULL'
				),
				"filter_value" => array(
					0 => 'Automatic',
					1 => 'Manual'
				)
			);
		} else
		if ($column_name == "status") {
			$response[$column_name] = array(
				"filter_condition" => array(
					'=' => 'EQUAL TO',
					'!=' => "NOT EQUAL TO",
					'IS NULL' => 'IS NULL',
					'IS NOT NULL' => 'IS NOT NULL'
				),
				"filter_value" => array(
					'AWAITING_PAYMENT' => 'Pending Payment',
					'PAID' => 'Paid'
				)
			);
		} else {
			$response[$column_name] = array(
				"filter_condition" => array(
					'LIKE' => 'LIKE',
					'LIKE %--%' => "LIKE %--%",
					'IS NULL' => 'IS NULL',
					'IS NOT NULL' => 'IS NOT NULL',
					'EMPTY' => 'EMPTY'
				),
				"filter_value" => array()
			);
		}
		return $response;
	}

	public function getColumnValue($column_name, $column_value) {
		if ($column_name == "is_email_sent" || $column_name == "is_regenrated") {
			$column_value = ($column_value == 1) ? "Yes" : "No";
		} else
		if ($column_name == "child_id") {
			$column_value = ChildPersonalDetails::model()->findByPk($column_value)->name;
		} else
		if ($column_name == "invoice_type") {
			$column_value = ($column_value == 0) ? "Automatic" : "Manual";
		} else {
			$column_value = $column_value;
		}
		return $column_value;
	}

	public function getRelatedAttributes() {
		$attributes = array();
		$attributes['ChildPersonalDetails'] = ChildPersonalDetails::model()->getRelatedAttributesNames();
		return $attributes;
	}

	public function getRelatedAttributesNames() {
		$attributes = array(
			'child_id'
		);
		return $attributes;
	}

	public function getInvoiceUrn() {
		return $this->urn_prefix . $this->urn_number . $this->urn_suffix;
	}

	public function getTotalAmount($keys) {
		$total = 0;
		$currency_sign = Branch::model()->findByPk(Yii::app()->session['branch_id'])->currency_sign;
		foreach ($keys as $key => $value) {
			$model = ChildInvoice::model()->findByPk($value);
			if ($model->is_deposit == 0) {
				$total += $model->total;
			}
		}
		return ($total < 0) ? $currency_sign . "(" . sprintf('%0.2f', - $total) . ")" : $currency_sign . sprintf('%0.2f', $total);
	}

	public function getTotalPaidAmount($keys) {
		$total = 0;
		$currency_sign = Branch::model()->findByPk(Yii::app()->session['branch_id'])->currency_sign;
		foreach ($keys as $key => $value) {
			$model = ChildInvoice::model()->findByPk($value);
			if ($model->is_deposit == 0) {
				$paid_amount = customFunctions::getPaidAmount($model->id);
				$total += $paid_amount;
			}
		}
		return ($total < 0) ? $currency_sign . "(" . sprintf('%0.2f', - $total) . ")" : $currency_sign . sprintf('%0.2f', $total);
	}

	public function getTotalDueAmount($keys) {
		$total = 0;
		$currency_sign = Branch::model()->findByPk(Yii::app()->session['branch_id'])->currency_sign;
		foreach ($keys as $key => $value) {
			$model = ChildInvoice::model()->findByPk($value);
			if ($model->is_deposit == 0) {
				$due_amount = customFunctions::getDueAmount($model->id);
				$total += $due_amount;
			}
		}
		return ($total < 0) ? $currency_sign . "(" . sprintf('%0.2f', - $total) . ")" : $currency_sign . sprintf('%0.2f', $total);
	}

	/**
	 * Function check whether fundng exists for Invoice, Used for hiding the funded and chargeable hours column from the invoice.
	 *
	 * @param type $invoice_id
	 * @return boolean true|false
	 */
	public function checkFundingExists($invoice_id) {
		$invoiceDetailsModel = ChildInvoiceDetails::model()->findAllByAttributes(array(
			'invoice_id' => $invoice_id
		));
		if (!empty($invoiceDetailsModel)) {
			$fundedHours = 0;
			foreach ($invoiceDetailsModel as $invoiceDetails) {
				$fundedHours += $invoiceDetails->funded_hours;
			}
			$fundedHours = sprintf("%0.2f", $fundedHours);
			if ($fundedHours > 0) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/*
	 * Function to set the invoice generation dates as per the settings.
	 */

	public function setInvoiceDates($invoiceSettingModal, $month, $year, $isMonthlyInvoiceDates = 0) {
		if ($isMonthlyInvoiceDates == 0) {
			$generate_month = $invoiceSettingModal->invoice_generate_month;
			if ($invoiceSettingModal->is_strict_dates == InvoiceSetting::STRICT_CALENDAR_DATES) {
				$invoiceFromDate = date("Y-m-d", strtotime($year . "-" . $month . "-01"));
				$invoiceToDate = date("Y-m-t", strtotime($invoiceFromDate));
			} else {
				$previousMonthDate = new DateTime(date("Y-m-d", strtotime($year . "-" . $month . "-01")));
				$previousMonthDate = $previousMonthDate->modify('previous month')->format('Y-m-d');
				$invoiceFromDate = customFunctions::getLastFriday(date("m", strtotime($previousMonthDate)), date("Y", strtotime($previousMonthDate)));
				$invoiceToDate = customFunctions::getLastFriday($month, $year);
				$invoiceFromDate = date("Y-m-d", strtotime($invoiceFromDate . "+3 days"));
				$invoiceToDate = date("Y-m-d", strtotime($invoiceToDate . "+2 days"));
			}
		} else {
			if ($invoiceSettingModal->is_strict_dates == InvoiceSetting::STRICT_CALENDAR_DATES) {
				$invoiceFromDate = date("Y-m-d", strtotime($year . "-" . $month . "-01"));
				$invoiceToDate = date("Y-m-t", strtotime("first day of +$invoiceSettingModal->invoice_generate_month_count month"));
			} else {
				$previousMonthDate = new DateTime(date("Y-m-d", strtotime($year . "-" . $month . "-01")));
				$previousMonthDate = $previousMonthDate->modify('previous month')->format('Y-m-d');
				$invoiceFromDate = customFunctions::getLastFriday(date("m", strtotime($previousMonthDate)), date("Y", strtotime($previousMonthDate)));
				$invoiceFromDate = date("Y-m-d", strtotime($invoiceFromDate . "+3 days"));
				$month = date('m', strtotime("$invoiceFromDate +$invoiceSettingModal->invoice_generate_month_count month"));
				$year = date('Y', strtotime("$invoiceFromDate +$invoiceSettingModal->invoice_generate_month_count month"));
				$invoiceToDate = customFunctions::getLastFriday($month, $year);
				$invoiceToDate = date("Y-m-d", strtotime($invoiceToDate . "+2 days"));
			}
		}
		return array(
			'invoice_from_date' => $invoiceFromDate,
			'invoice_to_date' => $invoiceToDate
		);
	}

	public function setInvoiceDatesForMonthlyInvoice($invoiceSettingModal, $month, $year, $isMonthlyInvoiceDates = 0) {
		if ($isMonthlyInvoiceDates == 0) {
			$generate_month = $invoiceSettingModal->invoice_generate_month;
			if ($invoiceSettingModal->is_strict_dates == InvoiceSetting::STRICT_CALENDAR_DATES) {
				$invoiceFromDate = date("Y-m-d", strtotime($year . "-" . $month . "-01"));
				$invoiceToDate = date("Y-m-t", strtotime($invoiceFromDate));
			} else {
				$previousMonthDate = new DateTime(date("Y-m-d", strtotime($year . "-" . $month . "-01")));
				$previousMonthDate = $previousMonthDate->modify('previous month')->format('Y-m-d');
				$invoiceFromDate = customFunctions::getLastSaturday(date("m", strtotime($previousMonthDate)), date("Y", strtotime($previousMonthDate)));
				$invoiceToDate = customFunctions::getLastFriday($month, $year);
				$invoiceFromDate = date("Y-m-d", strtotime($invoiceFromDate . "+2 days"));
				$invoiceToDate = date("Y-m-d", strtotime($invoiceToDate . "+2 days"));
			}
		} else {
			if ($invoiceSettingModal->is_strict_dates == InvoiceSetting::STRICT_CALENDAR_DATES) {
				$invoiceFromDate = date("Y-m-d", strtotime($year . "-" . $month . "-01"));
				$invoiceToDate = date("Y-m-t", strtotime("first day of +$invoiceSettingModal->invoice_generate_month_count month"));
			} else {
				$previousMonthDate = new DateTime(date("Y-m-d", strtotime($year . "-" . $month . "-01")));
				$previousMonthDate = $previousMonthDate->modify('previous month')->format('Y-m-d');
				$invoiceFromDate = customFunctions::getLastSaturday(date("m", strtotime($previousMonthDate)), date("Y", strtotime($previousMonthDate)));
				$invoiceFromDate = date("Y-m-d", strtotime($invoiceFromDate . "+2 days"));
				$month = date('m', strtotime("$invoiceFromDate +$invoiceSettingModal->invoice_generate_month_count month"));
				$year = date('Y', strtotime("$invoiceFromDate +$invoiceSettingModal->invoice_generate_month_count month"));
				$invoiceToDate = customFunctions::getLastFriday($month, $year);
				$invoiceToDate = date("Y-m-d", strtotime($invoiceToDate . "+2 days"));
			}
		}
		return array(
			$invoiceFromDate,
			$invoiceToDate
		);
	}

	/*
	 * Function to calculate the invoice dates of a month
	 */

	public function setInvoiceDatesOfMonth($invoiceSettingModal, $month) {
		if ($invoiceSettingModal->is_strict_dates == 0) {
			$monthStartDate = date("Y-m-d", strtotime($month));
			$monthEndDate = date("Y-m-t", strtotime($month));
		} else {
			$previousMonthDate = new DateTime(date("Y-m-d", strtotime($month)));
			$previousMonthDate = $previousMonthDate->modify('previous month')->format('Y-m-d');
			$monthStartDate = customFunctions::getLastSaturday(date("m", strtotime($previousMonthDate)), date("Y", strtotime($previousMonthDate)));
			$monthEndDate = customFunctions::getLastFriday(date("m", strtotime($month)), date("Y", strtotime($month)));
			$monthStartDate = date("Y-m-d", strtotime($monthStartDate . "+2 days"));
			$monthEndDate = date("Y-m-d", strtotime($monthEndDate . "+3 days"));
		}
		return array(
			'month_start_date' => $monthStartDate,
			'month_end_date' => $monthEndDate
		);
	}

	public function beforeSave() {
		if (get_class(Yii::app()) === "CWebApplication") {
			if ($this->isNewRecord) {
				$this->created_by = Yii::app()->user->id;
				$this->created = new CDbExpression("NOW()");
				if ($this->is_money_received == 0) {
					$this->payment_mode = NULL;
				}
			} else {
				if ($this->is_money_received == 0) {
					$this->payment_mode = NULL;
				}
				$this->updated_by = Yii::app()->user->id;
				$this->updated = new CDbExpression("NOW()");
			}
		}
		if (get_class(Yii::app()) === "CConsoleApplication") {
			if ($this->isNewRecord) {
				if ($this->is_money_received == 0) {
					$this->payment_mode = NULL;
				}
				$this->created_by = User::model()->findByEmail(Yii::app()->params['superAdminEmailId'][0]);
				$this->created = new CDbExpression("NOW()");
			} else {
				if ($this->is_money_received == 0) {
					$this->payment_mode = NULL;
				}
				$this->updated_by = User::model()->findByEmail(Yii::app()->params['superAdminEmailId'][0]);
				$this->updated = new CDbExpression("NOW()");
			}
		}

		return parent::beforeSave();
	}

	public function getInvoiceAmount() {
		$invoiceDetailsModel = $this->childInvoiceDetails;
		$total = 0;
		$subTotal = 0;
		$total_discount = 0;
		foreach ($invoiceDetailsModel as $invoiceDetails) {
			if ($invoiceDetails->session_data != NULL) {
				if ($invoiceDetails->session_type == 2 || $invoiceDetails->session_type == 3) {
					$total_price = $invoiceDetails->rate * $invoiceDetails->total_days;
				} else {
					$chargeable_hours = (($invoiceDetails->total_hours - $invoiceDetails->funded_hours) < 0) ? 0 : $invoiceDetails->total_hours - $invoiceDetails->funded_hours;
					$total_price = $invoiceDetails->rate * $chargeable_hours;
				}
				$total_discount = $total_discount + $invoiceDetails->discount * $total_price * 0.01;
				$subTotal = $subTotal + $total_price;
			}
			if ($invoiceDetails->products_data != NULL) {
				foreach (json_decode($invoiceDetails->products_data) as $product => $days) {
					$total_price = count($days) * customFunctions::getProductPrice($product);
					$subTotal = $subTotal + $total_price;
				}
			}
		}
		return round(sprintf("%0.2f", ($subTotal - $total_discount)), 2);
	}

	/*
	 * Method to calculate the total due amount for a month
	 */

	public function getTotalDueForMonth($child_id, $monthStartDate, $monthEndDate, $month) {
		$model = ChildInvoice::model()->find([
			'select' => 'sum(total) as total_deposit_amount',
			'condition' => 'month = :month AND year = :year AND child_id = :child_id AND invoice_type IN (0,1) AND is_regenrated = 0',
			'params' => [
				'month' => (int) date('m', strtotime($month)),
				':year' => date('Y', strtotime($month)),
				':child_id' => $child_id
			]
		]);
		$moneyNotReceivedCreditNoteModel = ChildInvoice::model()->find([
			'select' => 'abs(sum(total)) as total_deposit_amount',
			'condition' => 'child_id = :child_id AND invoice_type = 3 AND is_money_received = 0 AND invoice_date BETWEEN :month_start_date AND :month_end_date',
			'params' => [
				':month_start_date' => date('Y-m-d', strtotime($monthStartDate)),
				':month_end_date' => date('Y-m-d', strtotime($monthEndDate)),
				':child_id' => $child_id
			]
		]);
		if (!empty($model)) {
			$dueAmount = $model->total_deposit_amount;
		} else {
			$dueAmount = 0;
		}
		if ($moneyNotReceivedCreditNoteModel) {
			$moneyNotReceivedAmount = $moneyNotReceivedCreditNoteModel->total_deposit_amount;
		} else {
			$moneyNotReceivedAmount = 0;
		}
		return round($dueAmount - $moneyNotReceivedAmount, 2);
	}

	public function getChildDeposits($child_id, $month_end_date = NULL) {
		if ($month_end_date !== NULL) {
			$model = ChildInvoice::model()->find([
				'select' => 'sum(abs(total)) AS total_deposit_amount, group_concat(id) as deposit_id',
				'condition' => 'is_deposit = :is_deposit AND is_money_received = 1 AND child_id = :child_id AND invoice_date <= :month_end_date',
				'params' => [
					':is_deposit' => self::DEPOSIT,
					':child_id' => $child_id,
					':month_end_date' => $month_end_date
				]
			]);
		} else {
			$model = ChildInvoice::model()->find([
				'select' => 'sum(abs(total)) AS total_deposit_amount, group_concat(id) as deposit_id',
				'condition' => 'is_deposit = :is_deposit AND is_money_received = 1 AND child_id = :child_id',
				'params' => [
					':is_deposit' => self::DEPOSIT,
					':child_id' => $child_id
				]
			]);
		}
		if (!empty($model->deposit_id)) {
			$invoiceTransactionsModel = ChildInvoiceTransactions::model()->find([
				'select' => 'sum(abs(paid_amount)) as total_paid_amount',
				'condition' => 'invoice_id IN (' . $model->deposit_id . ') AND date_of_payment <= :month_end_date',
				'params' => [
					':month_end_date' => $month_end_date
				]
			]);
			return round(($model->total_deposit_amount - $invoiceTransactionsModel->total_paid_amount), 2);
		} else {
			return round(0, 2);
		}
	}

	public function getDepositsUsedAsFees($child_id, $month_start_date, $month_end_date) {
		$usedAsFees = 0;
		$invoiceTransactioModel = ChildInvoiceTransactions::model()->with([
				'invoice' => [
					'joinType' => 'INNER JOIN',
					'condition' => 'invoice.child_id = :child_id AND is_deposit = 1',
					'params' => [
						':child_id' => $child_id
					]
				]
			])->find([
			'select' => 'sum(paid_amount) AS total_paid_amount',
			'condition' => 'is_refund = 0 AND date_of_payment BETWEEN :start_date AND :finish_date',
			'params' => [
				':start_date' => date("Y-m-d", strtotime($month_start_date)),
				':finish_date' => date("Y-m-d", strtotime($month_end_date))
			]
		]);
		if (!empty($invoiceTransactioModel)) {
			$usedAsFees = $invoiceTransactioModel->total_paid_amount;
		}
		return round($usedAsFees, 2);
	}

	public function validateCreditNote($attributes, $params) {
		if (($this->is_deposit == 1) && ($this->is_money_received == 0)) {
			$this->addError('is_money_received', 'Deposit Credit Note should be money received as well.');
		}
		if (($this->is_money_received == 1) && (!(isset($this->payment_mode) || ($this->payment_mode == NULL) || ($this->payment_mode == "")))) {
			$this->addError('payment_mode', 'Payment mode can not be blank for money received credit note');
		}
	}

	public function getOwedAmountForMonth($month, $child_id, $monthStartDate, $monthFinishDate, $branch_id = NULL, $previousMonth = NULL) {
		$invoiceDueAmount = 0;
		$invoiceCriteria = new CDbCriteria();
		$invoiceCriteria->select = "sum(total) AS total_deposit_amount";
		$invoiceCriteria->condition = "invoice_type IN (0,1) AND child_id = :child_id and is_regenrated = 0 AND STR_TO_DATE(CONCAT(year,'-',month,'-01'), '%Y-%m-%d') < :date";
		$invoiceCriteria->params = array(
			':date' => date("Y-m-d", strtotime($month)),
			':child_id' => $child_id
		);
		$moneyNotReceivedCreditNoteModel = ChildInvoice::model()->find([
			'select' => 'abs(sum(total)) as total_deposit_amount',
			'condition' => 'child_id = :child_id AND invoice_type = 3 AND is_money_received = 0 AND invoice_date < :month_start_date',
			'params' => [
				':month_start_date' => date('Y-m-d', strtotime($monthStartDate)),
				':child_id' => $child_id
			]
		]);
		$invoiceModel = ChildInvoice::model()->find($invoiceCriteria);
		$paymentAmount = 0;
		$paymentCriteria = new CDbCriteria();
		$paymentCriteria->condition = "date_of_payment < :month_start_date AND branch_id = :branch_id";
		$paymentCriteria->params = array(
			':month_start_date' => $monthStartDate,
			':branch_id' => $branch_id
		);
		$paymentModel = Payments::model()->findAll($paymentCriteria);
		foreach ($paymentModel as $model) {
			if (in_array($child_id, explode(",", $model->child_id))) {
				$paymentTransacionsModel = PaymentsTransactions::model()->with(['invoice' => [
							'condition' => 'invoice.is_deposit = 1 AND invoice.invoice_date <= :date AND t.payment_id = :id',
							'params' => [':date' => date("Y-m-d", strtotime($monthStartDate)), ':id' => $model->id]
					]])->findAll();
				$paymentAmount += $model->amount;
				if (!empty($paymentTransacionsModel)) {
					foreach ($paymentTransacionsModel as $transaction) {
						$paymentAmount -= $transaction->paid_amount;
					}
				}
				/** Block to remove money not received payments* */
				$moneyNotReceivedTransacionsModel = PaymentsTransactions::model()->with(['invoice' => [
							'condition' => 'invoice.is_money_received = 0 AND invoice.invoice_type = 3 AND t.payment_id = :id',
							'params' => [':id' => $model->id]
					]])->findAll();
				if (!empty($moneyNotReceivedTransacionsModel)) {
					foreach ($moneyNotReceivedTransacionsModel as $transaction) {
						$paymentAmount -= $transaction->paid_amount;
					}
				}
			}
		}
		$usedAsFees = 0;
		$invoiceTransactioModel = ChildInvoiceTransactions::model()->with([
				'invoice' => [
					'joinType' => 'INNER JOIN',
					'condition' => 'invoice.child_id = :child_id AND is_deposit = 1',
					'params' => [
						':child_id' => $child_id
					]
				]
			])->find([
			'select' => 'sum(paid_amount) AS total_paid_amount',
			'condition' => 'is_refund = 0 AND date_of_payment <= :finish_date',
			'params' => [
				':finish_date' => date("Y-m-d", strtotime($month)),
			]
		]);
		if (!empty($invoiceTransactioModel)) {
			$usedAsFees = $invoiceTransactioModel->total_paid_amount;
		}
		$refundAmount = 0;
		$branchModel = Branch::currentBranch();
		$invoiceDueAmount = 0;
		$invoiceCriteria = new CDbCriteria();
		$invoiceCriteria->condition = "invoice_type IN (2,3) AND child_id = :child_id and is_deposit = 0 AND invoice_date <= :month_end_date";
		$invoiceCriteria->params = array(
			':child_id' => $child_id,
			':month_end_date' => $monthStartDate
		);

		$refundInvoiceModel = ChildInvoice::model()->findAll($invoiceCriteria);
		foreach ($refundInvoiceModel as $refund) {
			$invoiceTransactions = ChildInvoiceTransactions::model()->findAll([
				'condition' => '(is_refund = 1 OR is_refund = 2) AND date_of_payment < :month_end_date AND invoice_id = :invoice_id',
				'params' => [
					':month_end_date' => $monthStartDate,
					':invoice_id' => $refund->id
				]
			]);
			if (!empty($invoiceTransactions)) {
				foreach ($invoiceTransactions as $transaction) {
					$refundAmount += ($transaction->paid_amount);
				}
			}
		}
		if (!empty($invoiceModel)) {
			if (!empty($moneyNotReceivedCreditNoteModel)) {
				return round(($invoiceModel->total_deposit_amount - $moneyNotReceivedCreditNoteModel->total_deposit_amount - $paymentAmount - $usedAsFees + $refundAmount), 2);
			} else {
				return round(($invoiceModel->total_deposit_amount - $paymentAmount - $usedAsFees + $refundAmount), 2);
			}
		} else {
			if (!empty($moneyNotReceivedCreditNoteModel)) {
				return round((0 - $moneyNotReceivedCreditNoteModel->total_deposit_amount - $paymentAmount - $usedAsFees + $refundAmount), 2);
			} else {
				return round((0 - $paymentAmount - $usedAsFees + $refundAmount), 2);
			}
		}
	}

	public function getRefundsForMonth($month, $child_id, $monthStartDate, $monthFinishDate) {
		$refundAmount = 0;
		$branchModel = Branch::currentBranch();
		$invoiceDueAmount = 0;
		$invoiceCriteria = new CDbCriteria();
		$invoiceCriteria->condition = "invoice_type IN (2,3) AND child_id = :child_id and invoice_date <= :month_end_date";
		$invoiceCriteria->params = array(
			':child_id' => $child_id,
			':month_end_date' => $monthFinishDate
		);

		$invoiceModel = ChildInvoice::model()->findAll($invoiceCriteria);
		foreach ($invoiceModel as $model) {
			$invoiceTransactions = ChildInvoiceTransactions::model()->findAll([
				'condition' => '(is_refund = 1 OR is_refund = 2) AND date_of_payment <= :month_end_date AND invoice_id = :invoice_id',
				'params' => [
					':month_end_date' => $monthFinishDate,
					':invoice_id' => $model->id
				]
			]);
			if (!empty($invoiceTransactions)) {
				foreach ($invoiceTransactions as $transaction) {
					$refundAmount += ($transaction->paid_amount);
				}
			}
		}
		return round($refundAmount, 2);
	}

	public static function getBalanceAmount($child_id) {
		$model = ChildInvoice::model()->findAll([
			'condition' => 'child_id = :child_id AND is_regenrated = 0 AND is_deposit is NULL',
			'params' => [
				':child_id' => $child_id
			]
		]);
		if (!empty($model)) {
			$total_due = 0;
			foreach ($model as $invoice) {
				$total_due += customFunctions::getDueAmount($invoice->id);
			}
			return round($total_due, 2);
		}
		return round(0, 2);
	}

	public function checkRegenerateAllowed($invoice_id) {
		if (Branch::currentBranch()->include_last_month_uninvoiced_sessions == 1) {
			$model = ChildInvoice::model()->findByPk($invoice_id);
			if (!empty($model)) {
				$next_invoice_date = date("Y-m-d", strtotime($model->year . "-" . $model->month . "-01"));
				$next_invoice_date = date("Y-m-d", strtotime("+1 month", strtotime($next_invoice_date)));
				if (!empty(ChildInvoice::model()->findByAttributes(array(
							'is_regenrated' => 0,
							'child_id' => $model->child_id,
							'invoice_type' => 0,
							'month' => date("m", strtotime($next_invoice_date)),
							'year' => date("Y", strtotime($next_invoice_date))
					)))) {
					return false;
				} else {
					return true;
				}
			}
			return false;
		}
		return true;
	}

	public function getEmail1Status() {
		switch ($this->is_email_sent):
			case 0:
				return "Not Sent";
			case 1:
				return "Sent";
			case 2:
				return "Sent";
			case 3:
				return "Scheduled";
				return;
		endswitch;
	}

	public function getEmail2Status() {
		switch ($this->is_email_sent_2):
			case 0:
				return "Not Sent";
			case 1:
				return "Sent";
			case 2:
				return "Sent";
			case 3:
				return "Scheduled";
				return;
		endswitch;
	}

    public function createGcInvoiceTransaction($paymentModel, $parent_id) {
        $invoiceTransactionModel = new ChildInvoiceTransactions;
        $invoiceTransactionModel->attributes = array(
            'invoice_id' => $this->id,
            'payment_refrence' => 'System',
            'invoice_amount' => $this->total,
            'paid_amount' => customFunctions::getDueAmount($this->id),
            'date_of_payment' => date("Y-m-d", strtotime("now")),
            'payment_mode' => ChildInvoiceTransactions::PAYMENT_MODE_GOCARDLESS,
            'payment_id' => $paymentModel->id,
            'status' => 1,
            'parent_id' => $parent_id,
            'pg_status' => ChildInvoiceTransactions::PG_STATUS_SUBMITTED,
        );
        $paymentTransaction = new PaymentsTransactions;
        $paymentTransaction->invoice_id = $this->id;
        $paymentTransaction->payment_id = $paymentModel->id;
        $paymentTransaction->paid_amount = $invoiceTransactionModel->paid_amount;
        if ($paymentTransaction->save()) {
            $invoiceTransactionModel->payment_id = $paymentTransaction->id;
            if ($invoiceTransactionModel->save()) {
                return $invoiceTransactionModel;
            } else {
                throw new Exception('Failed to create invoice transaction');
            }
        } else {
            throw new Exception('Failed to create payment transaction');
        }
    }

    public function createGcPayment() {
        $paymentModel = new Payments;
        $paymentModel->attributes = $this->attributes;
        $paymentModel->date_of_payment = date("Y-m-d", strtotime("now"));
        $paymentModel->payment_mode = ChildInvoiceTransactions::PAYMENT_MODE_GOCARDLESS;
        $paymentModel->payment_reference = 'System';
        $paymentModel->amount = customFunctions::getDueAmount($this->id);
        $paymentModel->status = 1;
        $paymentModel->child_id = $this->child_id;
        if (!$paymentModel->save()) {
						throw new Exception("Payment model not saved.");
        }
				return $paymentModel;
    }

    public function recordGoCardlessPayment($parentModel, $invoiceTransactionModel = null, $paymentModel = null) {
        if (!$paymentModel) {
            $paymentModel = $this->createGcPayment();
        }
        if (!$invoiceTransactionModel) {
            $invoiceTransactionModel = $this->createGcInvoiceTransaction($paymentModel, $parentModel->id);
        }
        $gcCustomerClient = customFunctions::getCustomerGoCardlessClient();
        if (!$gcCustomerClient) {
            throw new CHttpException(500, 'Direct Debit Client account does not exist.');
        }
        if ($parentModel && $parentModel->hasMandate()) {
            $mandate = $parentModel->gocardless_mandate;
            $amount = $paymentModel->amount;
            $paybleAmount = ($amount <= $this->total) ? $amount : $this->total;
            try {
                $resp = $gcCustomerClient->payments()->create([
                    "params" => [
                        "amount" => (int)($paybleAmount * 100),
                        "currency" => "GBP",
                        "metadata" => [
                            "order_dispatch_date" => date('Y-m-d', strtotime($this->created)),
                            "apiUrl" => Yii::app()->controller->createAbsoluteUrl('/site/updateInvoiceGoCardless', array('invoice_id' => $this->id, 'signature' => '__signature__')),
                        ],
                        "links" => [
                            "mandate" => $mandate
                        ]
                    ]
                ]);
            } catch (Exception $e) {
                if ($e->getMessage() == 'Mandate not found') {
                    $parentModel->removeMandate();
                }
                throw new Exception($e->getMessage(), $e->getCode());
            }

            $invoiceTransactionModel->payment_refrence = $resp->id;
            $invoiceTransactionModel->pg_transaction_id = $resp->id;
            if (!$invoiceTransactionModel->save()) {
                throw new Exception("Error saving ChildInvoiceTransaction");
            }

            $currentGoCardless = customFunctions::getCurrentGoCardless();
            $adminGcTransaction = new GocardlessTransaction;
            $adminGcTransaction->gc_access_token = $currentGoCardless->gc_access_token;
            $adminGcTransaction->gc_payment_id = $resp->id;
            if (!$adminGcTransaction->save()) {
                throw new Exception("Error saving GocardlessTransaction");
            }
            $invoiceTransactionModel->pg_status = $resp->status;
            if (!$invoiceTransactionModel->save()) {
                throw new Exception("Error saving ChildInvoiceTransaction, GoCardless transaction is complete.");
            }
            $this->status = ChildInvoice::PAID;
            if (!$this->save(false)) {
                throw new Exception("Error saving ChildInvoice, GoCardless transaction is complete.");
            }
        } else {
            throw new Exception("Direct debit is not setup for parent.");
        }
    }

}
