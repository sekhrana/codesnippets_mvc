<?php

//Demo Commit
/**
 * This is the model class for table "tbl_company".
 *
 * The followings are the available columns in table 'tbl_company':
 * @property integer $id
 * @property string $name
 * @property string $address1
 * @property string $address2
 * @property string $city
 * @property string $county
 * @property string $postcode
 * @property integer $country
 * @property string $telephone
 * @property string $website
 * @property string $email
 * @property string $registration_number
 * @property string $logo
 * @property string $created
 * @property integer $is_deleted
 * @property integer $is_active
 * @property string $vat_number
 * @property integer $is_integration_enabled
 * @property integer $urn_type
 * @property string $child_urn_number
 * @property string $staff_urn_number
 * @property integer $is_enabled_credit_notes
 * @property integer $created_by
 * @property integer $updated_by
 * @property string $child_urn_prefix
 * @property string $child_urn_suffix
 * @property string $staff_urn_prefix
 * @property string $staff_urn_suffix
 * @property integer $minimum_booking_type
 * @property integer $minimum_booking_time
 *
 * The followings are the available model relations:
 * @property Branch[] $branches
 * @property GocardlessAccounts[] $gocardlessAccounts
 */
class Company extends CActiveRecord {

	const BASE_LOGO = "/images/logo.png";
	const LOGO_PATH = "/uploaded_images/company_logos/";
	const COMPANY_LEVEL_URN = 0;
	const BRANCH_LEVEL_URN = 1;
	const MINIMUM_BOOKING_PER_SESSIONS = 0;
	CONST MINIMUM_BOOKING_PER_SETTINGS = 1;

	public $file_name;
	public $company_logo_raw;
	public $company_logo_integration;
	public $previous_image;

	public function defaultScope() {
		if (get_class(Yii::app()) === "CWebApplication") {
			if (Yii::app()->session['role'] == "superAdmin") {
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0",
				);
			}

			if (Yii::app()->session['role'] == "companyAdministrator") {
				$company_id = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()))->company_id;
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".id=" . $company_id,
				);
			}

			if (Yii::app()->session['role'] == "areaManager") {
				$userMapping = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".id=" . $userMapping->company_id,
				);
			}
			if (Yii::app()->session['role'] == "branchManager") {
				$userMapping = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".id=" . $userMapping->company_id,
				);
			}
			if (Yii::app()->session['role'] == "branchAdmin") {
				$userMapping = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".id=" . $userMapping->company_id,
				);
			}
			if (Yii::app()->session['role'] == "hrAdmin" || Yii::app()->session['role'] == User::HR_STANDARD) {
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0",
				);
			}
			if (Yii::app()->session['role'] == "staff") {
				$companyId = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->id))->company_id;
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " .
					$this->getTableAlias(false, false) . ".id =" . $companyId,
				);
			}
			if (Yii::app()->session['role'] == "accountsAdmin") {
				$company_id = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()))->company_id;
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".id =" . $company_id,
				);
			}

			if (Yii::app()->session['role'] == "parent") {
				$companyIdArray = implode(',', Yii::app()->session['companyIds_array']);
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 AND " .
					$this->getTableAlias(false, false) . ".id IN(" . $companyIdArray . ")"
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
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'tbl_company';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('name, registration_number', 'required'),
			array('is_deleted,is_active , is_integration_enabled, created_by, updated_by, child_urn_number, staff_urn_number, urn_type, is_enabled_credit_notes, minimum_booking_type, minimum_booking_time', 'numerical', 'integerOnly' => true),
			array('name, address1, address2, city, county, website, email, logo', 'length', 'max' => 255),
			array('postcode, telephone, registration_number, vat_number, child_urn_number, staff_urn_number, child_urn_prefix, child_urn_suffix, staff_urn_prefix, staff_urn_suffix', 'length', 'max' => 45),
			array('created,country', 'safe'),
			array('logo', 'file','types'=>'jpg, gif, png', 'allowEmpty'=>true, 'on'=>'update'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id,is_active, name, address1, address2, city, county, postcode, country, telephone, website, email, registration_number, logo, created, is_deleted, vat_number, is_integration_enabled, created_by, updated_by, child_urn_number, staff_urn_number, child_urn_prefix, child_urn_suffix, staff_urn_prefix, staff_urn_suffix, urn_type, is_enabled_credit_notes, minimum_booking_type, minimum_booking_time', 'safe', 'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'branches' => array(self::HAS_MANY, 'Branch', 'company_id'),
			'gocardlessAccounts' => array(self::HAS_MANY, 'GocardlessAccounts', 'company_id'),
			'branchesNds' => array(self::HAS_MANY, 'BranchNds', 'company_id'),
			'countries' => array(self::BELONGS_TO, 'Country', 'country')
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'name' => 'Name',
			'address1' => 'Address1',
			'address2' => 'Address2',
			'city' => 'City',
			'county' => 'County',
			'postcode' => 'Postcode',
			'country' => 'Country',
			'telephone' => 'Telephone',
			'is_active' => 'Active',
			'website' => 'Website',
			'email' => 'Email',
			'registration_number' => 'Registration Number',
			'logo' => 'Logo',
			'created' => 'Created',
			'is_deleted' => 'Is Deleted',
			'vat_number' => 'VAT Number',
			'is_integration_enabled' => 'eyLog Integration Enabled',
			'created_by' => 'Created By',
			'updated_by' => 'Updated By',
			'child_urn_number' => 'Child URN Number',
			'staff_urn_number' => 'Staff URN Number',
			'child_urn_prefix' => 'Child URN Prefix',
			'child_urn_suffix' => 'Child URN Suffix',
			'staff_urn_prefix' => 'Staff URN Prefix',
			'staff_urn_suffix' => 'Staff URN Suffix',
			'urn_type' => 'Urn Type',
			'is_enabled_credit_notes' => 'Enable Credit Notes',
			'minimum_booking_type' => 'Minimum Booking Type',
			'minimum_booking_time' => 'Minimum Booking Time',
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
		$criteria->compare('name', $this->name, true);
		$criteria->compare('address1', $this->address1, true);
		$criteria->compare('address2', $this->address2, true);
		$criteria->compare('city', $this->city, true);
		$criteria->compare('county', $this->county, true);
		$criteria->compare('is_active', $this->is_active);
		$criteria->compare('postcode', $this->postcode, true);
		$criteria->compare('country', $this->country, true);
		$criteria->compare('telephone', $this->telephone, true);
		$criteria->compare('website', $this->website, true);
		$criteria->compare('email', $this->email, true);
		$criteria->compare('registration_number', $this->registration_number, true);
		$criteria->compare('logo', $this->logo, true);
		$criteria->compare('created', $this->created, true);
		$criteria->compare('is_deleted', $this->is_deleted);
		$criteria->compare('vat_number', $this->vat_number, true);
		$criteria->compare('updated_by', $this->updated_by);
		$criteria->compare('child_urn_number', $this->child_urn_number, true);
		$criteria->compare('staff_urn_number', $this->staff_urn_number, true);
		$criteria->compare('child_urn_prefix', $this->child_urn_prefix, true);
		$criteria->compare('child_urn_suffix', $this->child_urn_suffix, true);
		$criteria->compare('staff_urn_prefix', $this->staff_urn_prefix, true);
		$criteria->compare('staff_urn_suffix', $this->staff_urn_suffix, true);
		$criteria->compare('urn_type', $this->urn_type);
		$criteria->compare('is_enabled_credit_notes', $this->is_enabled_credit_notes);
		$criteria->compare('minimum_booking_type', $this->minimum_booking_type);
		$criteria->compare('minimum_booking_time', $this->minimum_booking_time);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Company the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public function beforeSave() {
		if (get_class(Yii::app()) === "CWebApplication") {
			if ($this->minimum_booking_type == self::MINIMUM_BOOKING_PER_SESSIONS) {
				$this->minimum_booking_time = NULL;
			}
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

	public function getCompanyLogo() {
		if (isset($this->logo) && !empty($this->logo)) {
			return GlobalPreferences::getSslUrl().$this->logo;
		} else {
			return Yii::app()->request->baseUrl.self::BASE_LOGO;
		}
		return $defaultLogo;
	}

	public function insertTemplateData($global_branch_id, $company_id) {
		$payTypeModel = new PayType;
		$payTypeModel->abbreviation = "CARE";
		$payTypeModel->description = "Child Care";
		$payTypeModel->is_counted_in_ratio = 1;
		$payTypeModel->color = "BDBB35";
		$payTypeModel->branch_id = $global_branch_id;
		$payTypeModel->is_active = 1;
		$payTypeModel->is_global = 1;
		$payTypeModel->include_in_wage_report = 1;
		$payTypeModel->is_unpaid = 0;
		$payTypeModel->global_id = $company_id;
		$payTypeModel->create_for_existing = 0;
		$payTypeModel->is_system_activity = 1;
		if (!$payTypeModel->save()) {
			throw new Exception(CHtml::errorSummary($payTypeModel, '', '', array('class' => 'customErrors')));
		}

		$eventArray = [
			["Pay Increase", "Pay Increase", "Effective From", "Valid To (if applicable)", "Amount", "Reason and Amount From - To"],
			["Pay Decrease", "Pay Decrease", "Effective From", "Valid To (if applicable)", "Amount", "Reason and Amount From - To"],
			["Maternity", "Maternity", "Start Date", "End Date", "90% Average Earnings", "Comments"]
		];
		foreach ($eventArray as $event) {
			$eventType = new EventType;
			$eventType->name = $event[0];
			$eventType->description = $event[1];
			$eventType->title_date_1 = $event[2];
			$eventType->title_date_2 = $event[3];
			$eventType->title_description = $event[4];
			$eventType->title_notes = $event[5];
			$eventType->for_staff = 1;
			$eventType->for_child = 0;
			$eventType->branch_id = $global_branch_id;
			$eventType->is_active = 1;
			$eventType->is_global = 1;
			$eventType->global_id = $company_id;
			$eventType->create_for_existing = 0;
			$eventType->is_systen_event = 1;
			if (!$eventType->save()) {
				throw new Exception(CHtml::errorSummary($eventType, '', '', array('class' => 'customErrors')));
			}
		}
	}

	public static function currentCompany() {
		$model = Company::model()->findByPk(Yii::app()->session['company_id']);
		if (!empty($model)) {
			return $model;
		} else {
			return FALSE;
		}
	}

	public function uploadCompanyLogo() {
		$rackspace = new eyManRackspace();
		$rackspace->uploadObjects([[
			'name' => "/images/company/" . $this->file_name,
			'body' => $this->company_logo_raw
			]
		]);
		$this->logo = "/images/company/" . $this->file_name;
	}

	public function afterFind() {
		$this->previous_image = $this->logo;
		return parent::afterFind();
	}

	public function afterValidate() {
		if(!isset($this->logo) && empty($this->logo)){
			$this->logo = $this->previous_image;
		}
		return parent::afterValidate();
	}

}
