<?php

/**
 * This is the model class for table "tbl_child_invoice_details".
 *
 * The followings are the available columns in table 'tbl_child_invoice_details':
 * @property integer $id
 * @property integer $invoice_id
 * @property string $session_data
 * @property string $session_room_data
 * @property string $products_data
 * @property string $week_start_date
 * @property string $week_end_date
 * @property integer $session_id
 * @property double $rate
 * @property double $average_rate
 * @property double $total_hours
 * @property double $funded_hours
 * @property double $funded_rate
 * @property double $discount
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $session_type
 * @property integer $is_minimum_bookings
 * @property integer $is_extras
 * @property integer $exclude_funding
 * @property integer $is_deleted
 *
 * The followings are the available model relations:
 * @property ChildInvoice $invoice
 */
class ChildInvoiceDetails extends CActiveRecord {

    public $product_id;
    public $product_description;
    public $product_quantity;
    public $product_price;
    public $deleted_product_id;

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'tbl_child_invoice_details';
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
            array('invoice_id', 'required'),
            array('invoice_id, session_id, session_type, total_days, is_minimum_booking, created_by, updated_by, is_deleted, is_extras, exclude_funding', 'numerical', 'integerOnly' => true),
            array('rate, average_rate, total_hours, funded_hours, discount,funded_rate', 'numerical'),
            array('session_data, session_room_data, products_data, products_data, week_start_date, week_end_date, updated, created', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id, invoice_id,session_room_data, session_data, products_data, week_start_date, week_end_date, session_id, rate, total_hours, funded_hours, funded_rate, discount, session_type, total_days, is_minimum_booking, updated, created, created_by, updated_by, is_deleted, is_extras, average_rate, exclude_funding', 'safe', 'on' => 'search'),
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
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'invoice_id' => 'Invoice',
            'session_data' => 'Session Data',
            'session_room_data' => 'Session Room Data',
            'products_data' => 'Products Data',
            'week_start_date' => 'Week Start Date',
            'week_end_date' => 'Week End Date',
            'session_id' => 'Session',
            'rate' => 'Rate',
            'average_rate' => 'Average Rate',
            'total_hours' => 'Total Hours',
            'funded_hours' => 'Funded Hours',
            'funded_rate' => 'Funded Rate',
            'discount' => 'Discount',
            'created' => 'Created',
            'updated' => 'Updated',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'session_type' => 'Session Type',
            'total_days' => 'Total Days',
            'is_minimum_booking' => 'Minimum Booking',
            'is_deleted' => "Deleted",
            'is_extras' => 'Is Extras',
            'exclude_funding' => 'Exclude Funding',
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
        $criteria->compare('session_data', $this->session_data, true);
        $criteria->compare('session_room_data',$this->session_room_data,true);
        $criteria->compare('products_data', $this->products_data, true);
        $criteria->compare('week_start_date', $this->week_start_date, true);
        $criteria->compare('week_end_date', $this->week_end_date, true);
        $criteria->compare('session_id', $this->session_id);
        $criteria->compare('rate', $this->rate);
        $criteria->compare('average_rate', $this->average_rate);
        $criteria->compare('total_hours', $this->total_hours);
        $criteria->compare('funded_hours', $this->funded_hours);
        $criteria->compare('funded_rate', $this->funded_rate);
        $criteria->compare('discount', $this->discount);
        $criteria->compare('is_minimum_booking', $this->is_minimum_booking);
        $criteria->compare('created', $this->created, true);
        $criteria->compare('updated', $this->updated, true);
        $criteria->compare('created_by', $this->created_by);
        $criteria->compare('updated_by', $this->updated_by);
        $criteria->compare('is_deleted', $this->is_deleted);
        $criteria->compare('is_extras', $this->is_extras);
        $criteria->compare('exclude_funding',$this->exclude_funding);
        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ChildInvoiceDetails the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
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

        return parent::beforeSave();
    }

    public function deleteMinimumBookingFees() {
        $transaction = Yii::app()->db->beginTransaction();
        try {
            if ($this->is_minimum_booking == 1) {
                $invoiceAmount = ChildInvoice::model()->findByPk($this->invoice_id)->total;
                $invoiceDueAmount = round(sprintf("%0.2f", customFunctions::getDueAmount($this->invoice_id)), 2);
                $minimumFeesAmount = $this->rate * $this->total_hours;
                $minimumFeesAmount = round(sprintf("%0.2f", $minimumFeesAmount - ($this->discount * 0.01 * $minimumFeesAmount)), 2);
                if ($invoiceDueAmount > $minimumFeesAmount) {
                    $this->is_deleted = 1;
                } else if ($invoiceDueAmount == $minimumFeesAmount) {
                    $this->is_deleted = 1;
                    $this->invoice->status = ChildInvoice::PAID;
                } else {
                    throw new Exception("To remove this minimum bookng fess please remove payments from invoice.");
                }
                if ($this->save()) {
                    $this->invoice->total = sprintf("%0.2f", ($invoiceAmount - $minimumFeesAmount));
                    if ($this->invoice->save()) {
                        $transaction->commit();
                        return array('status' => 1, 'message' => "Minimum Booking Fees has been removed successfully.");
                    } else {
                        throw new Exception("Their seems to be some problem removing the minimum booking Fees. Please try again later.");
                    }
                } else {
                    throw new Exception("Their seems to be some problem removing the minimum booking Fees. Please try again later.");
                }
            } else {
                throw new Exception("Fees you are trying to remove is not minimum booking fees.");
            }
        } catch (Exception $ex) {
            $transaction->rollback();
            return array('status' => 0, 'message' => $ex->getMessage());
        }
    }

    public function deleteAdditonalItem() {
        $transaction = Yii::app()->db->beginTransaction();
        try {
            if ($this->is_extras == 1) {
                $product_array = CJSON::decode($this->products_data);
                $deleted_product_array = $product_array[$this->deleted_product_id];
                $product_amount = round(sprintf("%0.2f", ($deleted_product_array[5] - ($deleted_product_array[4] * 0.01 * $deleted_product_array[5]))), 2);
                $invoiceDueAmount = round(sprintf("%0.2f", customFunctions::getDueAmount($this->invoice_id)), 2);
                if (count($product_array) == 1) {
                    $this->is_deleted = 1;
                } else {
                    unset($product_array[$this->deleted_product_id]);
                    $this->products_data = CJSON::encode($product_array);
                }
                if ($invoiceDueAmount == $product_amount) {
                    $this->invoice->status = ChildInvoice::PAID;
                }
                if ($invoiceDueAmount < $product_amount) {
                    throw new Exception("To remove this item please first remove the payments allocated to this invoice.");
                }
                if ($this->save()) {
                    $this->invoice->total = $this->invoice->total - $product_amount;
                    if ($this->invoice->save()) {
                        if ($invoiceDueAmount > 0) {
                            $this->invoice->status = ChildInvoice::PENDING_PAYMENT;
                        } else {
                            $this->invoice->status = ChildInvoice::PAID;
                        }
                        if (!$this->invoice->save()) {
                            throw new Exception("Their seems to be some error removing the items");
                        }
                        $transaction->commit();
                        return array('status' => 1, 'message' => "Additional item has been successfully removed from the invoice.");
                    } else {
                        throw new Exception("Their seems to be some error removing the items");
                    }
                } else {
                    throw new Exception("Their seems to be some error removing the items");
                }
            } else {
                throw new Exception("Item you are trying to remove is not an additional Item.");
            }
        } catch (Exception $ex) {
            $transaction->rollback();
            return array('status' => 0, 'message' => $ex->getMessage());
        }
    }

}
