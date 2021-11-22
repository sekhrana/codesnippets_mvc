<?php

//Demo Commit
/**
 * This is the model class for table "tbl_branch_calendar".
 *
 * The followings are the available columns in table 'tbl_branch_calendar':
 * @property integer $id
 * @property integer $branch_id
 * @property string $name
 * @property string $description
 * @property string $start_date
 * @property string $finish_date
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $is_holiday
 * @property integer $is_deleted
 * @property integer $is_funding_applicable
 *
 * The followings are the available model relations:
 * @property Branch $branch
 */
class BranchCalendar extends CActiveRecord {

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'tbl_branch_calendar';
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
			if (Yii::app()->session['role'] == "hrAdmin" || Yii::app()->session['role'] == User::HR_STANDARD) {
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0",
				);
			}
			if (Yii::app()->session['role'] == "staff") {
				$branchId = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->id))->branch_id;
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " .
					$this->getTableAlias(false, false) . ".branch_id =" . $branchId,
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
			array('branch_id, name, start_date, finish_date', 'required'),
			array('branch_id, is_holiday, is_deleted, is_funding_applicable, created_by, updated_by', 'numerical', 'integerOnly' => true),
			array('finish_date', 'compare', 'compareAttribute' => 'start_date', 'operator' => '>=', 'allowEmpty' => false, 'message' => '{attribute} must be greater than "{compareValue}".'),
			array('name', 'length', 'max' => 45),
			array('description', 'length', 'max' => 255),
			array('created, updated', 'safe'),
			array('start_date', 'customValidation'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, branch_id, name, description, start_date, finish_date, created, is_holiday, is_deleted, is_funding_applicable, updated, created_by, updated_by', 'safe', 'on' => 'search'),
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
			'name' => 'Name',
			'description' => 'Description',
			'start_date' => 'Start Date',
			'finish_date' => 'Finish Date',
			'created' => 'Created',
			'updated' => 'Updated',
			'created_by' => 'Created By',
			'updated_by' => 'Updated By',
			'is_holiday' => 'Mark as Holiday (Any sessions on this day will not be invoiced.)',
			'is_deleted' => 'Is Deleted',
			'is_funding_applicable' => 'Funding Applicable',
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
		$criteria->compare('name', $this->name, true);
		$criteria->compare('description', $this->description, true);
		$criteria->compare('start_date', $this->start_date, true);
		$criteria->compare('finish_date', $this->finish_date, true);
		$criteria->compare('created', $this->created, true);
		$criteria->compare('updated', $this->updated, true);
		$criteria->compare('created_by', $this->created_by);
		$criteria->compare('updated_by', $this->updated_by);
		$criteria->compare('is_holiday', $this->is_holiday);
		$criteria->compare('is_deleted', $this->is_deleted);
		$criteria->compare('is_funding_applicable', $this->exclude_from_funding);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return BranchCalendar the static model class
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

	public function customValidation($attributes, $params) {
		if (!empty($this->start_date) && !empty($this->finish_date)) {
			if (date("W", strtotime($this->start_date)) != date("W", strtotime($this->finish_date))) {
				$this->addError('finish_date', 'Start Date and Finish Date of Branch Holiday should be present in the same week.');
			}

			if (date("Y", strtotime($this->start_date)) != date("Y", strtotime($this->finish_date))) {
				$this->addError('finish_date', 'Start Date and Finish Date of Branch Holiday should be present in the same Year.</br>');
			}
		}
	}

	/*
	 * Function to get the branch calendar holidays
	 * Return array of holidays
	 */
	public static function getBranchCalendarHolidays($start_date, $finish_date, $isHoliday = NULL, $isFundingApplicable = NULL){
		$branchModel = Branch::currentBranch();
		$holidays = array();
		$actualDates = customFunctions::getDatesOfDays($start_date, $finish_date, explode(",", $branchModel->nursery_operation_days));
		$criteria = new CDbCriteria();
		$criteria->condition = "t.branch_id = :branch_id AND
                    ((t.start_date >= :start_date and t.start_date <= :finish_date) OR
                    (t.finish_date >= :start_date and t.finish_date <= :finish_date) OR
                    (t.start_date <= :start_date and t.finish_date >= :finish_date))";
		if ($isHoliday !== NULL) {
			$criteria->addCondition("is_holiday = $isHoliday", "AND");
		}
		if ($isFundingApplicable !== NULL) {
			$criteria->addCondition("is_funding_applicable = $isFundingApplicable", "AND");
		}
		$criteria->params = ['branch_id' => $branchModel->id, ':start_date' => date("Y-m-d", strtotime($start_date)), ':finish_date' => date("Y-m-d", strtotime($finish_date))];
		$criteria->together = TRUE;
		$model = BranchCalendar::model()->findAll($criteria);
		if(!empty($model)){
			foreach($model as $holiday){
				$holidayDates = customFunctions::getDatesOfDays($holiday->start_date, $holiday->finish_date, explode(",", $branchModel->nursery_operation_days));
				$holidayDates = array_intersect($holidayDates, $actualDates);
				if(!empty($holidayDates)){
					foreach($holidayDates as $day){
						$holidays[] = $day;
					}
				}
			}
		}
		return $holidays;
	}

}
