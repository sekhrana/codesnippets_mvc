<?php

/**
 * This is the model class for table "tbl_staff_holidays_entitlement_events".
 *
 * The followings are the available columns in table 'tbl_staff_holidays_entitlement_events':
 * @property integer $id
 * @property integer $branch_id
 * @property integer $holiday_id
 * @property string $start_date
 * @property string $finish_date
 * @property double $contract_hours
 * @property double $entitlement
 * @property integer $is_changed
 * @property integer $is_transferred
 * @property integer $is_deleted
 * @property integer $is_overriden
 * @property integer $no_of_days
 * @property integer $opening_balance
 *
 * The followings are the available model relations:
 * @property StaffHolidaysEntitlementDetails $holiday
 */
class StaffHolidaysEntitlementEvents extends CActiveRecord {

	public $date_columns = array(
		'start_date',
		'finish_date'
	);
	public $effective_date;
	public $total_entitlement;

	/**
	 *
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'tbl_staff_holidays_entitlement_events';
	}

	public function defaultScope() {
		if (get_class(Yii::app()) === "CWebApplication") {
			return array(
				'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0"
			);
		}
		if (get_class(Yii::app()) === "CConsoleApplication") {
			return array(
				'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0"
			);
		}
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
				'holiday_id, start_date, finish_date, contract_hours, no_of_days',
				'required'
			),
			array(
				'branch_id, holiday_id, is_changed, is_transferred, is_deleted, is_overriden, no_of_days',
				'numerical',
				'integerOnly' => true
			),
			array(
				'contract_hours, entitlement',
				'numerical'
			),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array(
				'id, branch_id, holiday_id, start_date, finish_date, contract_hours, entitlement, is_changed, is_transferred, is_deleted, is_overriden, no_of_days , opening_balance',
				'safe',
				'on' => 'search'
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
			'holiday' => array(
				self::BELONGS_TO,
				'StaffHolidaysEntitlementDetails',
				'holiday_id'
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
			'branch_id' => 'Branch',
			'holiday_id' => 'Holiday',
			'start_date' => 'Start Date',
			'finish_date' => 'Finish Date',
			'contract_hours' => 'Contract Hours',
			'entitlement' => 'Entitlement',
			'is_changed' => 'Changed',
			'is_transferred' => 'Transferred',
			'is_deleted' => 'Deleted',
			'is_overriden' => 'Overriden',
			'no_of_days' => 'Contract No. Of Days/Week',
                        'opening_balance' => 'Opening Balance'
		);
	}

	public function behaviors() {
		return array(
			'dateFormatter' => array(
				'class' => 'application.components.DateFormatter',
				'date_columns' => array(
					'start_date',
					'finish_date'
				)
			)
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
	public function search($id) {
		// @todo Please modify the following code to remove attributes that should not be searched.
		$criteria = new CDbCriteria();

		$criteria->compare('id', $this->id);
		$criteria->compare('branch_id', $this->branch_id);
		$criteria->compare('holiday_id', $id);
		$criteria->compare('start_date', $this->start_date, true);
		$criteria->compare('finish_date', $this->finish_date, true);
		$criteria->compare('contract_hours', $this->contract_hours);
		$criteria->compare('no_of_days', $this->no_of_days);
		$criteria->compare('entitlement', $this->entitlement);
		$criteria->compare('is_changed', $this->is_changed);
		$criteria->compare('is_transferred', $this->is_transferred);
		$criteria->compare('is_deleted', $this->is_deleted);
		$criteria->compare('is_overriden', $this->is_overriden);
                $criteria->compare('opening_balance', $this->opening_balance);
		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'sort' => array(
				'defaultOrder' => 'start_date, finish_date ASC'
			),
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 *
	 * @param string $className
	 *            active record class name.
	 * @return StaffHolidaysEntitlementEvents the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

}
