<?php
//Demo Commit
/**
 * This is the model class for table "tbl_payments".
 *
 * The followings are the available columns in table 'tbl_payments':
 * @property integer $id
 * @property integer $branch_id
 * @property string $child_id
 * @property string $date_of_payment
 * @property integer $payment_mode
 * @property string $payment_reference
 * @property double $amount
 * @property integer $is_deleted
 * @property integer $status
 * @property string $notes
 * @property string $created
 * @property integer $created_by
 * @property string $updated
 * @property integer $updated_by
 */
class Payments extends CActiveRecord {

    public $balance_amount;

    const ALLOCATED = 1;
    const NOT_ALLOCATED = 0;

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'tbl_payments';
    }

    public function defaultScope() {
        if (get_class(Yii::app()) === "CWebApplication") {
            if (Yii::app()->session['role'] == "superAdmin") {
                return array(
                    'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0",
                );
            }

            if (Yii::app()->session['role'] == "companyAdministrator") {
                $company_id = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()))->company_id;
                $branchString = implode(',', customFunctions::getBranchesByCompany($company_id));
                return array(
                    'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")",
                );
            }

            if (Yii::app()->session['role'] == "areaManager") {
                $userMappingModal = UserBranchMapping::model()->findAllByAttributes(array('user_id' => Yii::app()->user->getId()));
                $branchString = implode(',', customFunctions::getBranchesOfAreaManager($userMappingModal));
                return array(
                    'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")",
                );
            }
            if (Yii::app()->session['role'] == "branchManager") {
                $userMapping = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()));
                return array(
                    'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id =" . $userMapping->branch_id,
                );
            }
            if (Yii::app()->session['role'] == "branchAdmin") {
                $userMapping = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()));
                return array(
                    'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id =" . $userMapping->branch_id,
                );
            }
            if (Yii::app()->session['role'] == "staff") {
                $branchId = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->id))->branch_id;
                return array(
                    'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " .
                    $this->getTableAlias(false, false) . ".branch_id =" . $branchId,
                );
            }
            if (Yii::app()->session['role'] == "hrAdmin" || Yii::app()->session['role'] == User::HR_STANDARD) {
                return array(
                    'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0",
                );
            }
            if (Yii::app()->session['role'] == "accountsAdmin") {
                $company_id = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()))->company_id;
                $branchString = implode(',', customFunctions::getBranchesByCompany($company_id));
                return array(
                    'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")",
                );
            }
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
            array('branch_id, date_of_payment, payment_mode, amount', 'required'),
            array('payment_reference', 'required', 'on' => 'createPayment, updatePayment'),
            array('branch_id, payment_mode, created_by, updated_by, status, is_deleted', 'numerical', 'integerOnly' => true),
            array('amount', 'compare', 'compareValue' => 0, 'operator' => '>', 'message' => 'Payment amount must be greater than zero.'),
            array('amount', 'numerical'),
            array('payment_reference', 'length', 'max' => 45),
            array('notes, created, updated', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id, branch_id, child_id, date_of_payment, payment_mode, payment_reference, amount, created, created_by, is_deleted, updated, updated_by, status, notes', 'safe', 'on' => 'search'),
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
            'branch_id' => 'Branch',
            'child_id' => 'Child',
            'date_of_payment' => 'Date Of Payment',
            'payment_mode' => 'Payment Mode',
            'payment_reference' => 'Payment Reference',
            'amount' => 'Amount Paid',
            'is_deleted' => 'Is Deleted',
            'status' => 'Status',
            'notes' => 'Notes',
            'created' => 'Created',
            'created_by' => 'Created By',
            'updated' => 'Updated',
            'updated_by' => 'Updated By',
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
        $criteria->compare('t.id', $this->id);
        $criteria->compare('t.branch_id', Yii::app()->session['branch_id']);
        $criteria->addSearchCondition('t.child_id', $this->child_id, true, "AND", "LIKE");
        $criteria->compare('t.date_of_payment', (!empty($this->date_of_payment)) ? date("Y-m-d", strtotime($this->date_of_payment)) : "", true);
        $criteria->compare('t.payment_mode', $this->payment_mode);
        $criteria->compare('t.payment_reference', $this->payment_reference, true);
        $criteria->compare('t.amount', $this->amount);
        $criteria->compare('t.is_deleted', $this->is_deleted);
        $criteria->compare('t.status', $this->status);
        $criteria->compare('notes', $this->notes, true);
        $criteria->compare('t.created', $this->created, true);
        $criteria->compare('t.created_by', $this->created_by);
        $criteria->compare('t.updated', $this->updated, true);
        $criteria->compare('t.updated_by', $this->updated_by);

        $sort = new CSort();
        $sort->attributes = array(
            'defaultOrder' => 't.date_of_payment DESC'
        );
        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
            'pagination' => array('pageSize' => 25),
            'sort' => $sort
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Payments the static model class
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

    /*
     * Function to get the total payments of a child for a month.
     */

    public function getTotalPaymentsForMonth($child_id, $monthStartDate, $monthEndDate, $branch_id = NULL) {
        $criteria = new CDbCriteria();
        $criteria->condition = "(date_of_payment BETWEEN :month_start_date AND :month_end_date) AND branch_id = :branch_id";
        $criteria->params = array(':month_start_date' => $monthStartDate, ':month_end_date' => $monthEndDate, ':branch_id' => $branch_id);
        $model = Payments::model()->findAll($criteria);
        $paymentAmount = 0;
        foreach ($model as $payment) {
            $childArray = explode(",", $payment->child_id);
            if ($child_id == $childArray[0]) { /** Logic modification by rachit to include only payment of first child* */
                $paymentTransacionsModel = PaymentsTransactions::model()->findAllByAttributes(['payment_id' => $payment->id]);
                $paymentAmount += $payment->amount;
                if (!empty($paymentTransacionsModel)) {
                    foreach ($paymentTransacionsModel as $transaction) {
                        if ($transaction->invoice->is_deposit == 1 && strtotime($transaction->invoice->invoice_date) <= strtotime($monthEndDate)) {
                            $paymentAmount -= $transaction->paid_amount;
                        }
												if($transaction->invoice->is_money_received == 0 && $transaction->invoice->invoice_type == 3){
														$paymentAmount -= $transaction->paid_amount;
												}
                    }
                }
            }
        }
        $refundAmount = 0;
        $branchModel = Branch::currentBranch();
        $invoiceCriteria = new CDbCriteria();
        $invoiceCriteria->condition = "invoice_type IN (2,3) AND child_id = :child_id AND is_deposit = 0";
        $invoiceCriteria->params = array(
            ':child_id' => $child_id,
        );
        $invoiceModel = ChildInvoice::model()->findAll($invoiceCriteria);
        foreach ($invoiceModel as $model) {
            $invoiceTransactions = ChildInvoiceTransactions::model()->findAll([
                'condition' => '(is_refund = 1 OR is_refund = 2) AND (date_of_payment BETWEEN :month_start_date AND :month_end_date) AND invoice_id = :invoice_id',
                'params' => [
                    ':month_start_date' => $monthStartDate,
                    ':month_end_date' => $monthEndDate,
                    ':invoice_id' => $model->id
                ]
            ]);
            if (! empty($invoiceTransactions)) {
                foreach ($invoiceTransactions as $transaction) {
                    $refundAmount += ($transaction->paid_amount);
                }
            }
        }
        return round($paymentAmount - $refundAmount, 2);
    }

}
