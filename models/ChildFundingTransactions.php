<?php

/**
 * This is the model class for table "tbl_child_funding_transactions".
 *
 * The followings are the available columns in table 'tbl_child_funding_transactions':
 * @property integer $id
 * @property integer $branch_id
 * @property integer $funding_id
 * @property integer $child_id
 * @property integer $invoice_id
 * @property string $week_start_date
 * @property string $week_finish_date
 * @property double $two_year_funding_rate
 * @property double $three_year_funding_rate
 * @property double $funded_hours_avaliable
 * @property double $funded_hours_used
 * @property double $funded_hours_used_prev
 * @property integer $is_deleted
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $type_of_week
 *
 * The followings are the available model relations:
 * @property ChildPersonalDetails $child
 * @property ChildInvoice $invoice
 * @property Branch $branch
 * @property ChildFundingDetails $funding
 */
class ChildFundingTransactions extends CActiveRecord {

    const TERM_TIME_WEEK = 0;
    const HOLIDAY_WEEK = 1;
    const PARTIAL_HOLIDAY_WEEK = 2;

    public $group_invoice_id;
    public $group_funded_hours_avaliable;
    public $group_funded_hours_used;

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'tbl_child_funding_transactions';
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
            array('branch_id, funding_id, child_id, week_start_date, week_finish_date, funded_hours_avaliable', 'required'),
            array('branch_id, funding_id, child_id, is_deleted, created_by, updated_by, type_of_week', 'numerical', 'integerOnly' => true),
            array('two_year_funding_rate, three_year_funding_rate,funded_hours_avaliable, funded_hours_used, funded_hours_used_prev', 'numerical'),
            array('updated, created', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id, branch_id, funding_id, child_id, invoice_id, week_start_date, week_finish_date,two_year_funding_rate, three_year_funding_rate, funded_hours_avaliable, funded_hours_used_prev, funded_hours_used, is_deleted, updated, created, created_by, updated_by, type_of_week', 'safe', 'on' => 'search'),
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
            'invoice' => array(self::BELONGS_TO, 'ChildInvoice', 'invoice_id'),
            'branch' => array(self::BELONGS_TO, 'Branch', 'branch_id'),
            'funding' => array(self::BELONGS_TO, 'ChildFundingDetails', 'funding_id'),
						'childNds' => array(self::BELONGS_TO, 'ChildPersonalDetailsNds', 'child_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'branch_id' => 'Branch',
            'funding_id' => 'Funding',
            'child_id' => 'Child',
            'invoice_id' => 'Invoice',
            'week_start_date' => 'Week Start Date',
            'week_finish_date' => 'Week Finish Date',
            'two_year_funding_rate' => 'Two Year Funding Rate',
            'three_year_funding_rate' => 'Three Year Funding Rate',
            'funded_hours_avaliable' => 'Funded Hours Avaliable',
            'funded_hours_used' => 'Funded Hours Used',
            'funded_hours_used_prev' => 'Funded Hours Used Prev',
            'is_deleted' => 'Is Deleted',
            'created' => 'Created',
            'updated' => 'Updated',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'type_of_week' => 'Type Of Week',
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
        $criteria->compare('funding_id', $this->funding_id);
        $criteria->compare('child_id', $this->child_id);
        $criteria->compare('invoice_id', $this->invoice_id);
        $criteria->compare('week_start_date', $this->week_start_date, true);
        $criteria->compare('week_finish_date', $this->week_finish_date, true);
        $criteria->compare('two_year_funding_rate', $this->two_year_funding_rate);
        $criteria->compare('three_year_funding_rate', $this->three_year_funding_rate);
        $criteria->compare('funded_hours_avaliable', $this->funded_hours_avaliable);
        $criteria->compare('funded_hours_used', $this->funded_hours_used);
        $criteria->compare('is_deleted', $this->is_deleted);
        $criteria->compare('created', $this->created, true);
        $criteria->compare('updated', $this->updated, true);
        $criteria->compare('created_by', $this->created_by);
        $criteria->compare('updated_by', $this->updated_by);
        $criteria->compare('type_of_week', $this->type_of_week);
        $criteria->compare('funded_hours_used_prev',$this->funded_hours_used_prev);
        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ChildFundingTransactions the static model class
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

    public function getBalanceFundedHours($total_funded_hours, $this_week_hours, $funding_id) {
        $fundingTransactionModel = ChildFundingTransactions::model()->findAllByAttributes(['funding_id' => $funding_id]);
        foreach ($fundingTransactionModel as $transaction) {
            $allocated += $transaction->funded_hours_avaliable;
        }
        $balance = $total_funded_hours - $allocated;
        if ($this_week_hours > $balance) {
            return customFunctions::roundToPointFive($balance);
        } else {
            return customFunctions::roundToPointFive($this_week_hours);
        }
    }

    public function getTotalBalanceFundedHours($total_funded_hours, $funding_id) {
        $fundingTransactionModel = ChildFundingTransactions::model()->findAllByAttributes(['funding_id' => $funding_id]);
        foreach ($fundingTransactionModel as $transaction) {
            $allocated += $transaction->funded_hours_avaliable;
        }
        $balance = $total_funded_hours - $allocated;
        return customFunctions::roundToPointFive($balance);
    }

}
