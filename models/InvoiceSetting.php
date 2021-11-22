<?php

/**
 * This is the model class for table "tbl_invoice_setting".
 *
 * The followings are the available columns in table 'tbl_invoice_setting':
 * @property integer $id
 * @property string $type
 * @property integer $invoice_day
 * @property integer $invoice_due_day
 * @property string $from_email
 * @property string $reply_to_email
 * @property string $subject
 * @property string $message
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $branch_id
 * @property string $invoice_logo
 * @property string $invoice_header_text
 * @property string $invoice_generate_month
 * @property string $invoice_header_color
 * @property integer $is_strict_dates
 * @property double $minimum_hours
 * @property integer $auto_send_invoice
 * @property string $invoice_number_prefix
 * @property integer $invoice_pdf_header_type
 * @property string $invoice_pdf_header_image
 * @property string $invoice_pdf_footer_text
 * @property string $invoice_number
 * @property string $invoice_number_suffix
 * @property integer $invoice_generate_type
 * @property integer $invoice_generate_month_count
 * @property integer $auto_collect_invoice
 * @property integer $auto_collect_invoice_when
 * @property integer $number_of_days
 *
 * The followings are the available model relations:
 * @property Branch $branch
 */
class InvoiceSetting extends CActiveRecord {

	const CUSTOM_HEADER = 0;
	const COMPLETE_HEADER_IMAGE = 1;
	const ACTUAL_SESSION_MONTHLY = 0;
	const EQUAL_SPREAD_MONTHS = 1;
	const STRICT_CALENDAR_DATES = 0;
	const WEEKLY_CALENDAR = 1;

	public $prevInvoiceHeaderImage;
	public $file_name;
	public $invoice_header_image_raw;
	public $invoice_header_integration;

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'tbl_invoice_setting';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('type, invoice_day, invoice_due_day, from_email, reply_to_email, subject, message, invoice_number', 'required'),
			array('invoice_day, invoice_due_day, is_strict_dates, auto_send_invoice, invoice_pdf_header_type, invoice_number, created_by, updated_by, invoice_generate_type, invoice_generate_month_count,auto_collect_invoice, auto_collect_invoice_when, number_of_days', 'numerical', 'integerOnly' => true),
			array('type', 'length', 'max' => 7),
			array('from_email, reply_to_email, invoice_header_color, invoice_number_prefix, invoice_number, invoice_number_suffix', 'length', 'max' => 45),
			array('subject', 'length', 'max' => 255),
			array('invoice_pdf_header_image', 'file','types'=>'jpg, jpeg, gif, png, gif', 'allowEmpty'=>true, 'on'=>'update'),
			array('type, invoice_logo', 'length', 'max' => 7),
			array('invoice_generate_month', 'length', 'max' => 4),
			array('created, updated, invoice_header_text, invoice_pdf_footer_text', 'safe'),
			array('invoice_pdf_header_type', 'validateHeaderType'),
			array('invoice_generate_type', 'validateGenerationType'),
            array('auto_collect_invoice', 'validateAutoCollectInvoice'),
            array('auto_collect_invoice_when', 'validateAutoCollectInvoiceDays'),
			//array('invoice_due_day','compare','compareAttribute' => 'invoice_day','operator' => '>=', 'message' => '{attribute} must be greater than "{compareAttribute}"'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, type, invoice_day, invoice_due_day, from_email, reply_to_email, subject, message, created, branch_id, invoice_logo, invoice_header_text, invoice_generate_month, invoice_header_color, is_strict_dates, auto_send_invoice, invoice_number_prefix, invoice_pdf_header_type, invoice_pdf_header_image, invoice_pdf_footer_text, invoice_number, invoice_number_suffix, updated, created_by, updated_by, invoice_generate_type,auto_collect_invoice, auto_collect_invoice_when, number_of_days', 'safe', 'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'branch' => array(self::BELONGS_TO, 'Branch', 'branch_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'type' => 'Invoice Frequency',
			'invoice_day' => 'Invoice Date',
			'invoice_due_day' => 'Invoice Due Date',
			'from_email' => 'From',
			'reply_to_email' => 'Reply to',
			'subject' => 'Subject',
			'message' => 'Message',
			'created' => 'Created',
			'updated' => 'Updated',
			'created_by' => 'Created By',
			'updated_by' => 'Updated By',
			'branch_id' => 'Branch',
			'invoice_logo' => 'Invoice Headler Logo',
			'invoice_header_text' => 'Invoice Header Text',
			'invoice_generate_month' => 'Invoice For (Month)',
			'invoice_header_color' => 'Invoice Header Color',
			'is_strict_dates' => 'Generate invoice as per ',
			'minimum_hours' => 'Minimum invoicing hours',
			'auto_send_invoice' => 'Auto. send invoice to parent',
			'invoice_number_prefix' => 'Invoice Number Prefix',
			'invoice_pdf_header_type' => 'Invoice Header Type',
			'invoice_pdf_header_image' => 'Invoice PDF Header Image',
			'invoice_pdf_footer_text' => 'Invoice PDF Footer Text',
			'invoice_number' => 'Invoice Number',
			'invoice_number_suffix' => 'Invoice Number Suffix',
			'invoice_generate_type' => 'Invoice Generate Type',
			'invoice_generate_month_count' => 'Invoice Generate Month Count',
            'auto_collect_invoice' => 'Auto Collect Invoice',
            'auto_collect_invoice_when' => 'Auto Collect Invoice When',
            'number_of_days' => 'Number Of Days',
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
		$criteria->compare('type', $this->type, true);
		$criteria->compare('invoice_day', $this->invoice_day);
		$criteria->compare('invoice_due_day', $this->invoice_due_day);
		$criteria->compare('from_email', $this->from_email, true);
		$criteria->compare('reply_to_email', $this->reply_to_email, true);
		$criteria->compare('subject', $this->subject, true);
		$criteria->compare('message', $this->message, true);
		$criteria->compare('created', $this->created, true);
		$criteria->compare('updated', $this->updated, true);
		$criteria->compare('created_by', $this->created_by);
		$criteria->compare('updated_by', $this->updated_by);
		$criteria->compare('branch_id', Yii::app()->session['branch_id']);
		$criteria->compare('invoice_logo', $this->invoice_logo, true);
		$criteria->compare('invoice_header_text', $this->invoice_header_text, true);
		$criteria->compare('invoice_generate_month', $this->invoice_generate_month, true);
		$criteria->compare('invoice_header_color', $this->invoice_header_color, true);
		$criteria->compare('is_strict_dates', $this->is_strict_dates);
		$criteria->compare('minimum_hours', $this->minimum_hours);
		$criteria->compare('auto_send_invoice', $this->auto_send_invoice);
		$criteria->compare('invoice_number_prefix', $this->invoice_number_prefix, true);
		$criteria->compare('invoice_pdf_header_type', $this->invoice_pdf_header_type);
		$criteria->compare('invoice_pdf_header_image', $this->invoice_pdf_header_image, true);
		$criteria->compare('invoice_pdf_footer_text', $this->invoice_pdf_footer_text, true);
		$criteria->compare('invoice_number', $this->invoice_number, true);
		$criteria->compare('invoice_number_suffix', $this->invoice_number_suffix, true);
		$criteria->compare('invoice_generate_type', $this->invoice_generate_type);
		$criteria->compare('invoice_generate_month_count', $this->invoice_generate_month_count);
        $criteria->compare('auto_collect_invoice',$this->auto_collect_invoice);
        $criteria->compare('auto_collect_invoice_when',$this->auto_collect_invoice_when);
        $criteria->compare('number_of_days',$this->number_of_days);


		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return InvoiceSetting the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public function validateHeaderType($attributes, $params) {
		if ($this->invoice_pdf_header_type == InvoiceSetting::CUSTOM_HEADER) {
			if (empty($this->invoice_logo) && empty($this->invoice_header_color) && empty($this->invoice_header_text)) {
				$this->addError('invoice_pdf_header_type', 'Please select invoice header color/logo/Text.');
			}
		}
		if ($this->invoice_pdf_header_type == InvoiceSetting::COMPLETE_HEADER_IMAGE) {
			if (empty($this->invoice_pdf_header_image) && empty($this->prevInvoiceHeaderImage)) {
				$this->addError('invoice_pdf_header_type', 'Invoice PDF header image can not be empty.');
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
		if (get_class(Yii::app()) === "CConsoleApplication") {
			if ($this->isNewRecord) {
				$this->created_by = User::model()->findByEmail(Yii::app()->params['superAdminEmailId'][0]);
				$this->created = new CDbExpression("NOW()");
			} else {
				$this->updated_by = User::model()->findByEmail(Yii::app()->params['superAdminEmailId'][0]);
				$this->updated = new CDbExpression("NOW()");
			}
		}
		if($this->invoice_generate_type == self::ACTUAL_SESSION_MONTHLY){
			$this->invoice_generate_month_count = NULL;
		}
        
        if($this->auto_collect_invoice == 0){
          $this->auto_collect_invoice_when = NULL;
          $this->number_of_days = NULL;
        }else if($this->auto_collect_invoice_when == 0 || $this->number_of_days == NULL){
          $this->number_of_days = NULL;
        }
		return parent::beforeSave();
	}

	public function validateGenerationType($attributes, $params) {
		if ($this->invoice_generate_type == InvoiceSetting::EQUAL_SPREAD_MONTHS && (empty($this->invoice_generate_month_count) || $this->invoice_generate_month_count == NULL)) {
			$this->addError('invoice_generate_month_count', 'Please Enter the month count.');
		}
	}
    
    public function validateAutoCollectInvoice($attributes, $params) {
		if ($this->auto_collect_invoice == 1 && $this->auto_collect_invoice_when == NULL ) {
			$this->addError('auto_collect_invoice_when', 'Please select time.');
		}
	}
    
    public function validateAutoCollectInvoiceDays($attributes, $params) {
		if ($this->auto_collect_invoice == 1 && $this->auto_collect_invoice_when != NULL && $this->auto_collect_invoice_when != 0 && $this->number_of_days == NULL) {
			$this->addError('number_of_days', 'Please select number of days.');
		}
	}

	public static function getMonthsForManualInvoice($start_date) {
		$invoiceSettingsModel = InvoiceSetting::model()->findByAttributes([
			'branch_id' => Branch::currentBranch()->id
		]);
		if ($start_date != NULL && !empty($invoiceSettingsModel)) {
			if (date("d", strtotime($start_date)) == "1") {
				return [
					'start_month' => date('m-Y', strtotime($start_date)),
					'end_month' => date("m-Y", strtotime("+" . ($invoiceSettingsModel->invoice_generate_month_count - 1) . " month", strtotime($start_date))),
				];
			} else {
				return [
					'start_month' => date("m-Y", strtotime("+1 month", strtotime($start_date))),
					'end_month' => date("m-Y", strtotime("+" . ($invoiceSettingsModel->invoice_generate_month_count - 1) . " month", strtotime(date("Y-m-d", strtotime("+1 month", strtotime($start_date)))))),
				];
			}
		} else {
			return [
				'start_month' => '',
				'end_month' => ''
			];
		}
	}

	public function afterFind() {
		$this->prevInvoiceHeaderImage = $this->invoice_pdf_header_image;
		return parent::afterFind();
	}

	public function afterValidate() {
		if(!isset($this->invoice_pdf_header_image) && empty($this->invoice_pdf_header_image)){
			$this->invoice_pdf_header_image = $this->prevInvoiceHeaderImage;
		}
		return parent::afterValidate();
	}

	public function uploadInvoiceHeaderImage() {
		$rackspace = new eyManRackspace();
		$rackspace->uploadObjects([[
			'name' => "/images/invoice/" . $this->file_name,
			'body' => $this->invoice_header_image_raw
			]
		]);
		$this->invoice_pdf_header_image = "/images/invoice/" . $this->file_name;
	}

}
