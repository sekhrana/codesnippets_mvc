<?php

//Demo Commit
/**
 * This is the model class for table "tbl_branch".
 *
 * The followings are the available columns in table 'tbl_branch':
 * @property integer $id
 * @property string $name
 * @property string $address_1
 * @property string $address_2
 * @property string $address_3
 * @property string $town
 * @property string $county
 * @property integer $country
 * @property string $postcode
 * @property string $phone
 * @property string $email
 * @property string $region_urn_ofsted
 * @property string $region_urn_dfe
 * @property double $wage_lower
 * @property double $wage_upper
 * @property string $header
 * @property string $content
 * @property string $fees_link
 * @property string $dda_statement
 * @property string $ofsted
 * @property double $stepup_rate
 * @property string $map
 * @property string $map_latitude
 * @property string $map_longitude
 * @property string $map_point
 * @property string $manager_statement
 * @property string $facebook_address
 * @property string $quote_1
 * @property string $quote_2
 * @property string $quote_3
 * @property double $funded_rate
 * @property integer $capacity
 * @property string $analysis_code_t
 * @property string $analysis_code_r
 * @property integer $is_deleted
 * @property integer $company_id
 * @property string $operation_start_time
 * @property string $operation_finish_time
 * @property integer $global_id
 * @property string $api_key
 * @property string $api_password
 * @property string $api_url
 * @property integer $is_integration_enabled
 * @property string $nursery_operation_days
 * @property string $website_link
 * @property double $maximum_funding_hours_day
 * @property double $maximum_funding_hours_week
 * @property double $default_ratio
 * @property integer $is_funding_applicable_on_weekend
 * @property string $currency_sign
 * @property string $rackspace_container
 * @property integer $external_id
 * @property integer $branch_start_month
 * @property integer $branch_end_month
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $funding_allocation_type
 * @property integer $is_round_off_entitlement
 * @property integer $change_session_rate
 * @property integer $is_minimum_booking_rate_enabled
 * @property string $staff_urn_prefix
 * @property string $staff_urn_suffix
 * @property string $child_urn_prefix
 * @property string $child_urn_suffix
 * @property integer $is_exclude_funding
 * @property integer $include_last_month_uninvoiced_sessions
 * @property integer $can_add_child
 * @property string $child_bookings_start_time
 * @property string $child_bookings_finish_time
 * @property string $staff_bookings_start_time
 * @property string $staff_bookings_finish_time
 * @property string $unique_url
 * @property integer $child_limit
 *
 * The followings are the available model relations:
 * @property Company $company
 * @property ChildBookings[] $childBookings
 * @property ChildPersonalDetails[] $childPersonalDetails
 * @property Enquiries[] $enquiries
 * @property Enquiries[] $enquiries1
 * @property GocardlessAccounts[] $gocardlessAccounts
 * @property Room[] $rooms
 * @property StaffPersonalDetails[] $staffPersonalDetails
 * @property UserBranchMapping[] $userBranchMappings
 * @property InvoiceSetting[] $invoiceSettings
 * @property HrSetting[] $hrSettings
 */
class Branch extends CActiveRecord {

	const AS_PER_SESSIONS = 0;
	const AS_PER_FUNDING_RATES = 1;
	const AS_PER_AVERAGE = 2;

	public function defaultScope() {
		if (get_class(Yii::app()) === "CWebApplication") {
			if (Yii::app()->session['role'] == "superAdmin") {
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".global_id =0",
				);
			}

			if (Yii::app()->session['role'] == "companyAdministrator") {
				$company_id = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".company_id =" . $company_id->company_id . " and " . $this->getTableAlias(false, false) . ".global_id =0",
				);
			}

			if (Yii::app()->session['role'] == "areaManager") {
				$userMappingModal = UserBranchMapping::model()->findAllByAttributes(array('user_id' => Yii::app()->user->getId()));
				$branchString = implode(',', customFunctions::getBranchesOfAreaManager($userMappingModal));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".id IN(" . $branchString . ") and " . $this->getTableAlias(false, false) . ".global_id =0",
				);
			}
			if (Yii::app()->session['role'] == "branchManager") {
				$userMapping = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".company_id =" . $userMapping->company_id . " and " .
					$this->getTableAlias(false, false) . ".id =" . $userMapping->branch_id . " and " . $this->getTableAlias(false, false) . ".global_id =0",
				);
			}
			if (Yii::app()->session['role'] == "branchAdmin") {
				$userMapping = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".company_id =" . $userMapping->company_id . " and " .
					$this->getTableAlias(false, false) . ".id =" . $userMapping->branch_id . " and " . $this->getTableAlias(false, false) . ".global_id =0",
				);
			}
			if (Yii::app()->session['role'] == "staff") {
				$branchId = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->id))->branch_id;
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " .
					$this->getTableAlias(false, false) . ".id =" . $branchId,
				);
			}
			if (Yii::app()->session['role'] == "hrAdmin" || Yii::app()->session['role'] == "hrStandard") {
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".global_id =0",
				);
			}
			if (Yii::app()->session['role'] == "accountsAdmin") {
				$company_id = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()))->company_id;
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".company_id =" . $company_id . " and " . $this->getTableAlias(false, false) . ".global_id =0",
				);
			}
			if (Yii::app()->session['role'] == "parent") {
				$branchIdsArray = implode(',', Yii::app()->session['branchIds_array']);
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 AND " .
					$this->getTableAlias(false, false) . ".id IN(" . $branchIdsArray . ")"
				);
			}
		}

		if (get_class(Yii::app()) === "CConsoleApplication") {
			return array(
				'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".global_id =0",
			);
		}
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'tbl_branch';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('name, address_1, town, county, country, postcode, phone, email, operation_start_time, operation_finish_time, nursery_operation_days', 'required'),
			array('is_active, capacity,phone, is_deleted, company_id, is_integration_enabled, is_funding_applicable_on_weekend, branch_start_month, branch_end_month,external_id, created_by, updated_by, funding_allocation_type, is_round_off_entitlement, change_session_rate, is_minimum_booking_rate_enabled, is_exclude_funding, include_last_month_uninvoiced_sessions,child_limit', 'numerical', 'integerOnly' => true),
			array('wage_lower, wage_upper, funded_rate,maximum_funding_hours_day, maximum_funding_hours_week, default_ratio, stepup_rate', 'numerical'),
			array('name, address_1, address_2, address_3, api_key, api_password, api_url, website_link', 'length', 'max' => 255),
			array('town, county, postcode, phone, email, region_urn_ofsted, region_urn_dfe, map_latitude, map_longitude, analysis_code_t, analysis_code_r, currency_sign, staff_urn_prefix, staff_urn_suffix, child_urn_prefix, child_urn_suffix', 'length', 'max' => 45),
			array('header, content, fees_link, dda_statement, ofsted, map, map_point, manager_statement, facebook_address, quote_1, quote_2, quote_3,rackspace_container, updated, created , can_add_child, child_bookings_start_time, child_bookings_finish_time, staff_bookings_start_time, staff_bookings_finish_time, capacity,child_limit', 'safe'),
			array('email', 'email'),
			array('name', 'checkBranchAlreadyExistInCompany'),
			array('rackspace_container', 'length', 'max' => 50),
			array('operation_finish_time', 'compare', 'compareAttribute' => 'operation_start_time', 'operator' => '>', 'message' => '{attribute} must be greater than "{compareValue}"'),
			array('unique_url', 'safe'),
                        array('child_limit','checkChildCount'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, name,is_active, address_1, address_2, address_3, town, county, country, postcode, phone, email, region_urn_ofsted, region_urn_dfe, wage_lower, wage_upper, header, content, fees_link, dda_statement, ofsted, map, map_latitude, map_longitude, map_point, manager_statement, facebook_address, quote_1, quote_2, quote_3, funded_rate, capacity, analysis_code_t, analysis_code_r, is_deleted, company_id, api_key, api_password, api_url, is_integration_enabled, nursery_operation_days, website_link, maximum_funding_hours_day, maximum_funding_hours_week, default_ratio, currency_sign, stepup_rate, branch_start_month, branch_end_month,external_id, updated, created, created_by, updated_by, funding_allocation_type, is_round_off_entitlement, change_session_rate, is_minimum_booking_rate_enabled, staff_urn_prefix, staff_urn_suffix, child_urn_prefix, child_urn_suffix, is_exclude_funding, include_last_month_uninvoiced_sessions , can_add_child, child_bookings_start_time, child_bookings_finish_time, staff_bookings_start_time, staff_bookings_finish_time, unique_url,child_limit', 'safe', 'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'company' => array(self::BELONGS_TO, 'Company', 'company_id'),
			'childBookings' => array(self::HAS_MANY, 'ChildBookings', 'branch_id'),
			'childPersonalDetails' => array(self::HAS_MANY, 'ChildPersonalDetails', 'branch_id'),
			'enquiries' => array(self::HAS_MANY, 'Enquiries', 'branch_id'),
			'enquiries1' => array(self::HAS_MANY, 'Enquiries', 'source_id'),
			'gocardlessAccounts' => array(self::HAS_MANY, 'GocardlessAccounts', 'branch_id'),
			'rooms' => array(self::HAS_MANY, 'Room', 'branch_id'),
			'staffPersonalDetails' => array(self::HAS_MANY, 'StaffPersonalDetails', 'branch_id'),
			'userBranchMappings' => array(self::HAS_MANY, 'UserBranchMapping', 'branch_id'),
			'invoiceSettings' => array(self::HAS_ONE, 'InvoiceSetting', 'branch_id'),
			'hrSettings' => array(self::HAS_ONE, 'HrSetting', 'branch_id'),
                        'roomWithShowBookings' => array(self::HAS_MANY, 'Room', 'branch_id', 'condition' => 'roomWithShowBookings.show_on_bookings = 1'),
                        'countries' => array(self::BELONGS_TO, 'Country', 'country'),
                        'session_rates' => array(self::HAS_MANY, 'SessionRatesNds', 'branch_id', 'condition' => 'session_rates.is_deleted = 0 AND session_rates.is_active = 1'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'name' => 'Name',
			'address_1' => 'Address 1',
			'address_2' => 'Address 2',
			'address_3' => 'Address 3',
			'town' => 'Town',
			'county' => 'County',
			'country' => 'Country',
			'postcode' => 'Postcode',
			'is_active' => 'Active',
			'phone' => 'Phone',
			'email' => 'Email',
			'region_urn_ofsted' => 'Reg\'n URN (OFSTED)',
			'region_urn_dfe' => 'Reg\'n URN (DfE)',
			'wage_lower' => 'Wage % Lower',
			'wage_upper' => 'Wage % Upper',
			'header' => 'Website Page Head Info',
			'content' => 'Website Page Content',
			'fees_link' => 'Fees Link',
			'dda_statement' => 'DDA Statement',
			'ofsted' => 'Ofsted',
			'stepup_rate' => 'Step-up Rate',
			'map' => 'Map',
			'map_latitude' => 'Map Latitude',
			'map_longitude' => 'Map Longitude',
			'map_point' => 'Map Point',
			'manager_statement' => 'Manager Statement',
			'facebook_address' => 'Facebook Address',
			'quote_1' => 'Website Quote 1',
			'quote_2' => 'Website Quote 2',
			'quote_3' => 'Website Quote 3',
			'funded_rate' => 'Funded Rate',
			'capacity' => 'Capacity',
			'analysis_code_t' => 'Analysis Code (T)',
			'analysis_code_r' => 'Analysis Code (R)',
			'is_deleted' => 'Active',
			'company_id' => 'Select a Company',
			'operation_start_time' => 'Operation Start Time',
			'operation_finish_time' => 'Operation Finish Time',
			'api_key' => 'eyLog API Key',
			'api_password' => 'eyLog API Password',
			'api_url' => 'eyLog API Url',
			'is_integration_enabled' => 'eyLog Integration Enabled',
			'nursery_operation_days' => 'Nursery Operation Days',
			'website_link' => 'Website Link',
			'maximum_funding_hours_day' => 'Maximum Funding Hours/Day',
			'maximum_funding_hours_week' => 'Maximum Funding Hours/Week',
			'default_ratio' => 'Default Ratio',
			'is_funding_applicable_on_weekend' => 'Funding Applicable On Weekend',
			'currency_sign' => 'Currency Sign',
			'external_id' => 'External',
			'branch_start_month' => 'Branch/Nursery Start Month',
			'branch_end_month' => 'Branch/Nursery End Month',
			'created' => 'Created',
			'updated' => 'Updated',
			'created_by' => 'Created By',
			'updated_by' => 'Updated By',
			'funding_allocation_type' => 'Funding Allocation',
			'is_round_off_entitlement' => 'Round Off Entitlement',
			'change_session_rate' => 'Session Rate Changes',
			'is_minimum_booking_rate_enabled' => 'Apply Minimum Booking Fees',
			'staff_urn_start_number' => 'Staff Urn Number',
			'child_urn_start_number' => 'Child Urn Number',
			'staff_urn_prefix' => 'Staff Urn Prefix',
			'staff_urn_suffix' => 'Staff Urn Suffix',
			'child_urn_prefix' => 'Child Urn Prefix',
			'child_urn_suffix' => 'Child Urn Suffix',
			'is_exclude_funding' => 'Enable Exclude Funding',
			'include_last_month_uninvoiced_sessions' => 'Include Last Month Un-invoiced Sessions',
                        'can_add_child' => 'Can Add Child',
			'child_bookings_start_time' => 'Child Bookings - Start Time',
			'child_bookings_finish_time' => 'Child Bookings -  Finish Time',
			'staff_bookings_start_time' => 'Staff Bookings - Start Time',
			'staff_bookings_finish_time' => 'Staff Bookings - Finish Time',
			'unique_url' => 'Unique Url',
                        'child_limit' => 'Child Limit'
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

		$criteria->compare('id', Yii::app()->session['branch_id']);
		$criteria->compare('name', $this->name, true);
		$criteria->compare('is_active', $this->is_active);
		$criteria->compare('address_1', $this->address_1, true);
		$criteria->compare('address_2', $this->address_2, true);
		$criteria->compare('address_3', $this->address_3, true);
		$criteria->compare('town', $this->town, true);
		$criteria->compare('county', $this->county, true);
		$criteria->compare('country', $this->country, true);
		$criteria->compare('postcode', $this->postcode, true);
		$criteria->compare('phone', $this->phone, true);
		$criteria->compare('email', $this->email, true);
		$criteria->compare('region_urn_ofsted', $this->region_urn_ofsted, true);
		$criteria->compare('region_urn_dfe', $this->region_urn_dfe, true);
		$criteria->compare('wage_lower', $this->wage_lower);
		$criteria->compare('wage_upper', $this->wage_upper);
		$criteria->compare('header', $this->header, true);
		$criteria->compare('content', $this->content, true);
		$criteria->compare('fees_link', $this->fees_link, true);
		$criteria->compare('dda_statement', $this->dda_statement, true);
		$criteria->compare('ofsted', $this->ofsted, true);
		$criteria->compare('stepup_rate', $this->stepup_rate);
		$criteria->compare('map', $this->map, true);
		$criteria->compare('map_latitude', $this->map_latitude, true);
		$criteria->compare('map_longitude', $this->map_longitude, true);
		$criteria->compare('map_point', $this->map_point, true);
		$criteria->compare('manager_statement', $this->manager_statement, true);
		$criteria->compare('facebook_address', $this->facebook_address, true);
		$criteria->compare('quote_1', $this->quote_1, true);
		$criteria->compare('quote_2', $this->quote_2, true);
		$criteria->compare('quote_3', $this->quote_3, true);
		$criteria->compare('funded_rate', $this->funded_rate);
		$criteria->compare('capacity', $this->capacity);
		$criteria->compare('analysis_code_t', $this->analysis_code_t, true);
		$criteria->compare('analysis_code_r', $this->analysis_code_r, true);
		$criteria->compare('is_deleted', $this->is_deleted);
		$criteria->compare('company_id', $this->company_id);
		$criteria->compare('operation_start_time', $this->operation_start_time, true);
		$criteria->compare('operation_finish_time', $this->operation_finish_time, true);
		$criteria->compare('global_id', $this->global_id);
		$criteria->compare('api_password', $this->api_password, true);
		$criteria->compare('api_url', $this->api_url, true);
		$criteria->compare('is_integration_enabled', $this->is_integration_enabled);
		$criteria->compare('nursery_operation_days', $this->nursery_operation_days, true);
		$criteria->compare('website_link', $this->website_link, true);
		$criteria->compare('maximum_funding_hours_day', $this->maximum_funding_hours_day);
		$criteria->compare('maximum_funding_hours_week', $this->maximum_funding_hours_week);
		$criteria->compare('default_ratio', $this->default_ratio);
		$criteria->compare('is_funding_applicable_on_weekend', $this->is_funding_applicable_on_weekend);
		$criteria->compare('currency_sign', $this->currency_sign, true);
		$criteria->compare('branch_start_month', $this->branch_start_month);
		$criteria->compare('branch_end_month', $this->branch_end_month);
		$criteria->compare('external_id', $this->external_id);
		$criteria->compare('created', $this->created, true);
		$criteria->compare('updated', $this->updated, true);
		$criteria->compare('created_by', $this->created_by);
		$criteria->compare('updated_by', $this->updated_by);
		$criteria->compare('funding_allocation_type', $this->funding_allocation_type);
		$criteria->compare('is_round_off_entitlement', $this->is_round_off_entitlement);
		$criteria->compare('change_session_rate', $this->change_session_rate);
		$criteria->compare('is_minimum_booking_rate_enabled', $this->is_minimum_booking_rate_enabled);
		$criteria->compare('staff_urn_prefix', $this->staff_urn_prefix, true);
		$criteria->compare('staff_urn_suffix', $this->staff_urn_suffix, true);
		$criteria->compare('child_urn_prefix', $this->child_urn_prefix, true);
		$criteria->compare('child_urn_suffix', $this->child_urn_suffix, true);
		$criteria->compare('is_exclude_funding', $this->is_exclude_funding);
		$criteria->compare('include_last_month_uninvoiced_sessions', $this->include_last_month_uninvoiced_sessions);
                $criteria->compare('can_add_child', $this->can_add_child);
		$criteria->compare('child_bookings_start_time',$this->child_bookings_start_time,true);
		$criteria->compare('child_bookings_finish_time',$this->child_bookings_finish_time,true);
		$criteria->compare('staff_bookings_start_time',$this->staff_bookings_start_time,true);
		$criteria->compare('staff_bookings_finish_time',$this->staff_bookings_finish_time,true);
		$criteria->compare('unique_url',$this->unique_url,true);
		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Branch the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	/*
	 * Check branch name is already exist in branch
	 */

	public function checkBranchAlreadyExistInCompany($attributes, $params) {
		if (!empty($this->name) && isset($this->name)) {
			$branchModel = self::model()->findAllByAttributes(['company_id' => $this->company_id]);
			if (!empty($branchModel)) {
				foreach ($branchModel as $branch) {
					if ($branch->id != $this->id && strtolower(trim($branch->name)) == strtolower(trim($this->name))) {
						$this->addError('name', 'Branch name already exist in current company.');
						return false;
					}
				}
			}
		}
	}

	/*
	 * Function to return branch external id
	 * @Param, $branchId
	 */

	public function branchExternalId($branchId) {
		$model = self::model()->findByPk($branchId);
		if (!empty($model) && !empty($model->external_id)) {
			return $model->external_id;
		} else {
			return 0;
		}
	}

	/*
	 * Function to create the Branch in table
	 * with the zero ID
	 */

	public function createBranchWithZeroId($companyId) {
		$brancId = $this->getLastNegativeBranchId() - 1;
		$this->isNewRecord = true;
		$this->id = $brancId;
		$this->company_id = $companyId;
		$this->name = 'Global-' . $brancId;
		$this->global_id = $companyId;
		$this->county = 'global';
		$this->country = 244;
		$this->town = 'global';
		$this->phone = '1234567890';
		$this->address_1 = 'global';
		$this->postcode = '12345';
		$this->email = 'global@eylog.uk';
		$this->operation_start_time = '04:00:00';
		$this->operation_finish_time = '24:00:00';
		$this->validate();
		if (!$this->save()) {
			throw new Exception(CHtml::errorSummary($this, '', '', array('class' => 'customErrors')));
		} else {
			return $this->id;
		}
	}

	public function createBranchByGlobalId($companyId) {
		self::model()->resetScope(true);
		$branch = self::model()->findByAttributes(array('is_active' => 1, 'global_id' => $companyId, 'is_deleted' => 0));
		self::model()->resetScope(FALSE);
		if (empty($branch)) {
			$brancId = $this->getLastNegativeBranchId() - 1;
			$this->isNewRecord = true;
			$this->id = $brancId;
			$this->company_id = $companyId;
			$this->name = 'Global-' . $brancId;
			$this->global_id = $companyId;
			$this->county = 'global';
			$this->country = 244;
			$this->town = 'global';
			$this->phone = '1234567890';
			$this->address_1 = 'global';
			$this->postcode = '12345';
			$this->email = 'global@eylog.uk';
			$this->operation_start_time = '04:00:00';
			$this->operation_finish_time = '24:00:00';
			$this->validate();
			if (!$this->save()) {
				throw new Exception(CHtml::errorSummary($this, '', '', array('class' => 'customErrors')));
			} else {
				return $this->id;
			}
		} else {
			return $branch->id;
		}
	}

	public function getLastNegativeBranchId() {
		self::model()->resetScope(true);
		$branch = self::model()->findAllByAttributes(array('is_active' => 1, 'is_deleted' => 0), ['order' => 'id desc']);
		self::model()->resetScope(FALSE);
		if (!empty($branch)) {
			$branchId = '';
			foreach ($branch as $data) {
				$branchId = $data->id;
			}
			if ($branchId < 0) {
				return $branchId;
			} else {
				return 0;
			}
		} else {
			return 0;
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

		return parent::beforeSave();
	}

	public function insertAgeRatio($branch_id) {
		$ageRatio = [
			["Ratio 1", "Ratio for age group 0 to 1", 0, 1, 3],
			["Ratio 2", "Ratio for age group 1 to 2", 1, 2, 3],
			["Ratio 3", "Ratio for age group 2 to 3", 2, 3, 4],
			["Ratio 4", "Ratio for age group 3 to 5", 3, 5, 8]
		];
		foreach ($ageRatio as $ratio) {
			$ageRatioModel = new AgeRatio;
			list($ageRatioModel->name, $ageRatioModel->description, $ageRatioModel->age_group_lower, $ageRatioModel->age_group_upper, $ageRatioModel->ratio) = $ratio;
			$ageRatioModel->branch_id = $branch_id;
			if (!$ageRatioModel->save()) {
				throw new Exception(CHtml::errorSummary($ageRatioModel, '', '', array('class' => 'customErrors')));
			}
		}
	}

	public static function currentBranch() {
		$model = Branch::model()->findByPk(Yii::app()->session['branch_id']);
		if (!empty($model)) {
			return $model;
		} else {
			return FALSE;
		}
	}
        public function checkChildCount($attributes, $params) {
            $child_count = ChildPersonalDetailsNds::model()->countByAttributes(array('is_active'=> 1,'is_deleted'=> 0,'branch_id' =>$this->id));
		if( $this->child_limit >  $child_count) {
                    $this->addError('child_limit','Already '.$this->id. ' children enrolled. Please increase child limit for the branch');
                }
	}
        
}
