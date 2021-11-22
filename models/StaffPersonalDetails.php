<?php

//Demo Commit
/**
 * This is the model class for table "tbl_staff_personal_details".
 *
 * The followings are the available columns in table 'tbl_staff_personal_details':
 * @property integer $id
 * @property integer $branch_id
 * @property integer $room_id
 * @property string $staff_urn
 * @property string $first_name
 * @property string $last_name
 * @property string $preffered_name
 * @property string $gender
 * @property string $holiday_contract_date
 * @property integer $is_salaried
 * @property string $address_1
 * @property string $address_2
 * @property string $address_3
 * @property string $postcode
 * @property integer $position
 * @property integer $additional_role
 * @property string $phone_1
 * @property string $phone_2
 * @property string $email_1
 * @property string $email_2
 * @property string $email_3
 * @property string $dob
 * @property integer $is_dob_unavaliable
 * @property string $start_date
 * @property string $leave_date
 * @property integer $title
 * @property integer $marital_status
 * @property string $level
 * @property string $ni_number
 * @property double $hourly_cost_uplift
 * @property double $hourly_rate_basic
 * @property string $contract_hours
 * @property string $holiday_contract
 * @property double $holiday_contract_original
 * @property integer $is_override_entitlement
 * @property string $no_of_days
 * @property string $holiday_entitlement
 * @property string $trainig_entitlement
 * @property string $profile_photo
 * @property string $profile_photo_thumb
 * @property integer $contract_day_monday
 * @property integer $contract_day_tuesday
 * @property integer $contract_day_wednesday
 * @property integer $contract_day_thursday
 * @property integer $contract_day_friday
 * @property integer $contract_day_saturday
 * @property integer $contract_day_sunday
 * @property integer $is_deleted
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $is_active
 * @property string $kin_1_name
 * @property string $kin_1_relationship
 * @property string $kin_1_phone_1
 * @property string $kin_1_phone_2
 * @property string $kin_1_email
 * @property string $kin_2_name
 * @property string $kin_2_relationship
 * @property string $kin_2_phone_1
 * @property string $kin_2_phone_2
 * @property string $kin_2_email
 * @property integer $is_term_time
 * @property integer $is_casual_staff
 * @property double $initial_contract_hours
 * @property integer $preffered_activity
 * @property integer $external_id
 * @property integer $is_moved
 * @property integer $is_entitlement_created
 * @property integer $can_publish_observations
 * @property integer $is_reviewer
 * @property integer $dbs_number
 *
 * The followings are the available model relations:
 * @property StaffBankDetails[] $staffBankDetails
 * @property StaffDocumentDetails[] $staffDocumentDetails
 * @property StaffEventDetails[] $staffEventDetails
 * @property StaffGeneralDetails[] $staffGeneralDetails
 * @property Branch $branch
 * @property PickStaffPosition $position0
 * @property PickStaffAdditionalRole $additionalRole
 */
class StaffPersonalDetails extends CActiveRecord {

	const STAFF_API_PATH = "/api-eyman/staff";
	const CASUAL_STAFF_ENTITLEMENT_CONSTANT = 12.07;

	public $prevProfilePhoto;
	public $date_columns = array('dob', 'start_date', 'leave_date');
	public $import_file;
	public $isWebcamPhoto;
	public $newBranch;
	public $previousBranch;
	public $effective_date;
	public $max_staff_urn;
	public $data_as_of;
	public static $holiday_contract_as_of;
	public static $contract_hours_as_of;
	protected $oldAttributes;
	public $profile_photo_integration;
	public $file_name;
	public $profile_photo_raw;
	public $profile_photo_thumb_raw;
	public $previous_image;
    public $entitlementYear;

	public function defaultScope() {
		$status = " AND " . $this->getTableAlias(false, false) . ".is_active = 1";
		$inactiveStatus = " AND " . $this->getTableAlias(false, false) . ".is_active = 0";
		if (get_class(Yii::app()) === "CWebApplication") {
			if (Yii::app()->session['role'] == "superAdmin") {
				if (isset(Yii::app()->user->skipActiveScopeStaff) && (Yii::app()->user->skipActiveScopeStaff === TRUE)) {
					return array(
						'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0" . $inactiveStatus,
					);
				} else {
					return array(
						'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0" . $status,
					);
				}
			}

			if (Yii::app()->session['role'] == "companyAdministrator") {
				$company_id = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()))->company_id;
				$branchString = implode(',', customFunctions::getBranchesByCompany($company_id));
				if (isset(Yii::app()->user->skipActiveScopeStaff) && (Yii::app()->user->skipActiveScopeStaff === TRUE)) {
					return array(
						'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")" . $inactiveStatus,
					);
				} else {
					return array(
						'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")" . $status,
					);
				}
			}

			if (Yii::app()->session['role'] == "areaManager") {
				$userMappingModal = UserBranchMapping::model()->findAllByAttributes(array('user_id' => Yii::app()->user->getId()));
				$branchString = implode(',', customFunctions::getBranchesOfAreaManager($userMappingModal));
				if (isset(Yii::app()->user->skipActiveScopeStaff) && (Yii::app()->user->skipActiveScopeStaff === TRUE)) {
					return array(
						'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")" . $inactiveStatus,
					);
				} else {
					return array(
						'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")" . $status,
					);
				}
			}
			if (Yii::app()->session['role'] == "branchManager") {
				$userMapping = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()));
				if (isset(Yii::app()->user->skipActiveScopeStaff) && (Yii::app()->user->skipActiveScopeStaff === TRUE)) {
					return array(
						'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id =" . $userMapping->branch_id . $inactiveStatus,
					);
				} else {
					return array(
						'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id =" . $userMapping->branch_id . $status,
					);
				}
			}
			if (Yii::app()->session['role'] == "branchAdmin") {
				$userMapping = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()));
				if (isset(Yii::app()->user->skipActiveScopeStaff) && (Yii::app()->user->skipActiveScopeStaff === TRUE)) {
					return array(
						'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id =" . $userMapping->branch_id . $inactiveStatus,
					);
				} else {
					return array(
						'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id =" . $userMapping->branch_id . $status,
					);
				}
			}
			if (Yii::app()->session['role'] == "staff") {
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " .
					$this->getTableAlias(false, false) . ".id =" . Yii::app()->session['staff_id'],
				);
			}
			if (Yii::app()->session['role'] == "hrAdmin" || Yii::app()->session['role'] == User::HR_STANDARD) {
				if (isset(Yii::app()->user->skipActiveScopeStaff) && (Yii::app()->user->skipActiveScopeStaff === TRUE)) {
					return array(
						'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0" . $inactiveStatus,
					);
				} else {
					return array(
						'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0" . $status,
					);
				}
			}
			if (Yii::app()->session['role'] == "accountsAdmin") {
				$company_id = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()))->company_id;
				$branchString = implode(',', customFunctions::getBranchesByCompany($company_id));
				if (isset(Yii::app()->user->skipActiveScopeStaff) && (Yii::app()->user->skipActiveScopeStaff === TRUE)) {
					return array(
						'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")" . $inactiveStatus,
					);
				} else {
					return array(
						'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")" . $status,
					);
				}
			}

			if (Yii::app()->session['role'] == "parent") {
				$branchId = Yii::app()->session['branch_id'];
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " .
					$this->getTableAlias(false, false) . ".id = " . $branchId,
				);
			}
		}
		if (get_class(Yii::app()) === "CConsoleApplication") {
			return array(
				'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0" . $status,
			);
		}
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'tbl_staff_personal_details';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('first_name, last_name', 'required'),
			array('first_name', 'required', 'on' => 'toggleStatus'),
            array('entitlementYear', 'checkEntitlementAlreadyCreated', 'on' => 'addEntitlement'),
			array('email_1, email_2,kin_1_email, kin_2_email', 'email', 'message' => 'Please enter a valid email ID', 'except' => 'toggleStatus'),
			array('branch_id, room_id, position, additional_role, is_dob_unavaliable,title, marital_status, contract_day_monday, contract_day_tuesday, contract_day_wednesday, contract_day_thursday, contract_day_friday, contract_day_saturday, contract_day_sunday, is_deleted, is_active, is_term_time, is_casual_staff, is_salaried, preffered_activity, external_id, created_by, updated_by, is_moved, is_override_entitlement, is_entitlement_created', 'numerical', 'integerOnly' => true),
			array('hourly_cost_uplift, hourly_rate_basic,contract_hours, holiday_contract, no_of_days, holiday_entitlement, holiday_contract_original', 'numerical', 'except' => 'toggleStatus'),
			array('first_name, last_name, preffered_name, address_1, address_2, address_3, email_1, email_2, email_3, profile_photo,  kin_1_name, kin_1_relationship, kin_2_name, kin_2_relationship', 'length', 'max' => 255, 'except' => 'toggleStatus'),
			array('gender', 'length', 'max' => 6, 'except' => 'toggleStatus'),
			array('email_1', 'checkEmailUnique', 'except' => 'toggleStatus'),
			array('dob, start_date, leave_date', 'date', 'format' => 'dd-mm-yyyy', 'message' => 'Please input a valid date types.', 'allowEmpty' => TRUE, 'except' => 'toggleStatus'),
			array('dob, start_date, leave_date', 'default', 'setOnEmpty' => true, 'value' => NULL, 'except' => 'toggleStatus'),
			array('staff_urn, postcode, phone_1, phone_2, level, ni_number, holiday_contract, no_of_days, holiday_entitlement, trainig_entitlement, kin_1_phone_1, kin_1_phone_2, kin_1_email, kin_2_phone_1, kin_2_phone_2, kin_2_email, dbs_number', 'length', 'max' => 45, 'except' => 'toggleStatus'),
			array('dob, start_date, leave_date, contract_hours, contract_type, holiday_contract_date,updated, created,entitlementYear', 'safe'),
			array('staff_urn', 'validateUrn', 'except' => 'toggleStatus'),
			array('contract_type, gender', 'default', 'setOnEmpty' => true, 'value' => NULL),
			array('profile_photo', 'file', 'types' => 'jpg, gif, png', 'allowEmpty' => true, 'on' => 'update'),
			array('can_publish_observations, is_reviewer', 'length', 'max' => 1),
			//array('first_name, last_name', 'match', 'pattern' => '/^[A-Za-z ]+$/u', 'message' => 'Only Alphabets are allowed'),
			//array('start_date', 'checkStartLeaveDate'),
			array('import_file', 'file', 'allowEmpty' => true, 'types' => 'xls,csv', 'on' => 'import'),
			array('staff_urn', 'validateExternalId', 'on' => 'eyLogDataImport'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, branch_id, room_id, staff_urn, first_name, last_name, preffered_name, gender, address_1, address_2, address_3, postcode, position, additional_role, phone_1, phone_2, email_1, email_2, email_3, dob, is_dob_unavaliable, start_date, leave_date, level, ni_number, hourly_cost_uplift, hourly_rate_basic, contract_hours, holiday_contract, no_of_days, holiday_entitlement, trainig_entitlement, profile_photo, contract_day_monday, contract_day_tuesday, contract_day_wednesday, contract_day_thursday, contract_day_friday, contract_day_saturday, contract_day_sunday, is_deleted,is_active, kin_1_name, kin_1_relationship, kin_1_phone_1, kin_1_phone_2, kin_1_email, kin_2_name, kin_2_relationship, kin_2_phone_1, kin_2_phone_2, kin_2_email, is_term_time, is_casual_staff, is_salaried, preffered_activity, external_id, updated, created, created_by, updated_by, title, marital_status, is_moved, holiday_contract_original, is_override_entitlement, is_entitlement_created, profile_photo_thumb,can_publish_observations,is_reviewer, dbs_number', 'safe', 'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'staffBankDetails' => array(self::HAS_ONE, 'StaffBankDetails', 'staff_id'),
			'staffDocumentDetails' => array(self::HAS_ONE, 'StaffDocumentDetails', 'staff_id'),
			'staffEventDetails' => array(self::HAS_ONE, 'StaffEventDetails', 'staff_id'),
			'staffGeneralDetails' => array(self::HAS_ONE, 'StaffGeneralDetails', 'staff_id'),
			'branch' => array(self::BELONGS_TO, 'Branch', 'branch_id'),
			'room' => array(self::BELONGS_TO, 'Room', 'room_id'),
			'position0' => array(self::BELONGS_TO, 'PickStaffPosition', 'position'),
			'additionalRole' => array(self::BELONGS_TO, 'PickStaffAdditionalRole', 'additional_role'),
			'tags' => array(self::HAS_MANY, 'StaffTagsMapping', 'staff_id', 'with' => 'tag')
		);
	}

	public function behaviors() {
		return array(
			'dateFormatter' => array(
				'class' => 'application.components.DateFormatter',
				'date_columns' => array('dob', 'start_date', 'leave_date')
			)
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'branch_id' => 'Branch',
			'room_id' => 'Room',
			'staff_urn' => 'Staff URN',
			'first_name' => 'First Name',
			'last_name' => 'Last Name',
			'preffered_name' => 'Preferred Name',
			'gender' => 'Gender',
			'is_salaried' => 'Salaried',
			'contract_type' => 'Contract Type',
			'address_1' => 'Address 1',
			'address_2' => 'Address 2',
			'address_3' => 'Address 3',
			'postcode' => 'Postcode',
			'position' => 'Position',
			'additional_role' => 'Additional Role',
			'phone_1' => 'Phone 1',
			'phone_2' => 'Phone 2',
			'email_1' => 'Primary Email',
			'email_2' => 'Secondary Email',
			'email_3' => 'Email 3',
			'dob' => 'DOB',
			'is_dob_unavaliable' => 'DOB Unavailable',
			'start_date' => 'Start Date',
			'leave_date' => 'Leave Date',
			'level' => 'Level',
			'ni_number' => 'Ni Number',
			'holiday_contract_date' => 'Holiday Contract Date',
			'hourly_cost_uplift' => 'Hourly Cost Uplift',
			'hourly_rate_basic' => 'Hourly Rate Basic',
			'contract_hours' => 'Contract Hours / Week',
			'holiday_contract' => 'Holiday Contract ( No. of Days/Year)',
			'no_of_days' => 'Contract No. of Days/Week',
			'holiday_entitlement' => 'Holiday Entitlement ( No.of Hours/Year)',
			'trainig_entitlement' => 'Training Entitlement ( No.of Hours/Year)',
			'profile_photo' => 'Profile Photo',
			'contract_day_monday' => 'M',
			'contract_day_tuesday' => 'Tu',
			'contract_day_wednesday' => 'W',
			'contract_day_thursday' => 'Th',
			'contract_day_friday' => 'F',
			'contract_day_saturday' => 'Sa',
			'contract_day_sunday' => 'Su',
			'is_deleted' => 'Deleted',
			'created' => 'Created',
			'updated' => 'Updated',
			'created_by' => 'Created By',
			'updated_by' => 'Updated By',
			'is_active' => 'Active',
			'kin_1_name' => 'Next of Kin 1 Name',
			'kin_1_relationship' => 'Next of Kin 1 Relationship',
			'kin_1_phone_1' => 'Next of Kin 1 Phone 1',
			'kin_1_phone_2' => 'Next of Kin 1 Phone 2',
			'kin_1_email' => 'Next of Kin 1 Email',
			'kin_2_name' => 'Next of Kin 2 Name',
			'kin_2_relationship' => 'Next of Kin 2 Relationship',
			'kin_2_phone_1' => 'Next of Kin 2 Phone 1',
			'kin_2_phone_2' => 'Next of Kin 2 Phone 2',
			'kin_2_email' => 'Next of Kin 2 Email',
			'is_term_time' => 'Term Time Only',
			'is_casual_staff' => 'Temp/Casual Staff',
			'previousBranch' => 'Current Branch',
			'newBranch' => 'New Branch',
			'preffered_activity' => 'Preferred Activity',
			'external_id' => 'External Id',
			'title' => 'Title',
			'marital_status' => 'Marital Status',
			'is_moved' => 'Moved',
			'holiday_contract_original' => 'Holiday Contract Original ( No. of Days/Year)',
			'is_override_entitlement' => 'Override Entitlement',
			'profile_photo_thumb' => 'Profile Photo Thumb',
			'can_publish_observations' => 'Can Publish Observations',
			'is_reviewer' => 'Is Reviewer',
			'dbs_number' => 'DBS Number'
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
		$criteria->compare('room_id', $this->room_id);
		$criteria->compare('staff_urn', $this->staff_urn, true);
		$criteria->compare('first_name', $this->first_name, true);
		$criteria->compare('last_name', $this->last_name, true);
		$criteria->compare('preffered_name', $this->preffered_name, true);
		$criteria->compare('gender', $this->gender, true);
		$criteria->compare('contract_type', $this->contract_type, true);
		$criteria->compare('address_1', $this->address_1, true);
		$criteria->compare('address_2', $this->address_2, true);
		$criteria->compare('address_3', $this->address_3, true);
		$criteria->compare('postcode', $this->postcode, true);
		$criteria->compare('position', $this->position);
		$criteria->compare('additional_role', $this->additional_role);
		$criteria->compare('phone_1', $this->phone_1, true);
		$criteria->compare('phone_2', $this->phone_2, true);
		$criteria->compare('email_1', $this->email_1, true);
		$criteria->compare('email_2', $this->email_2, true);
		$criteria->compare('email_3', $this->email_3, true);
		$criteria->compare('is_salaried', $this->is_salaried);
		$criteria->compare('dob', $this->dob, true);
		$criteria->compare('is_dob_unavaliable', $this->is_dob_unavaliable);
		$criteria->compare('start_date', $this->start_date, true);
		$criteria->compare('leave_date', $this->leave_date, true);
		$criteria->compare('level', $this->level, true);
		$criteria->compare('ni_number', $this->ni_number, true);
		$criteria->compare('hourly_cost_uplift', $this->hourly_cost_uplift);
		$criteria->compare('hourly_rate_basic', $this->hourly_rate_basic);
		$criteria->compare('contract_hours', $this->contract_hours, true);
		$criteria->compare('holiday_contract', $this->holiday_contract, true);
		$criteria->compare('no_of_days', $this->no_of_days, true);
		$criteria->compare('holiday_entitlement', $this->holiday_entitlement, true);
		$criteria->compare('trainig_entitlement', $this->trainig_entitlement, true);
		$criteria->compare('profile_photo', $this->profile_photo, true);
		$criteria->compare('contract_day_monday', $this->contract_day_monday);
		$criteria->compare('contract_day_tuesday', $this->contract_day_tuesday);
		$criteria->compare('contract_day_wednesday', $this->contract_day_wednesday);
		$criteria->compare('contract_day_thursday', $this->contract_day_thursday);
		$criteria->compare('contract_day_friday', $this->contract_day_friday);
		$criteria->compare('contract_day_saturday', $this->contract_day_saturday);
		$criteria->compare('contract_day_sunday', $this->contract_day_sunday);
		$criteria->compare('is_deleted', $this->is_deleted);
		$criteria->compare('is_active', $this->is_active);
		$criteria->compare('kin_1_name', $this->kin_1_name, true);
		$criteria->compare('kin_1_relationship', $this->kin_1_relationship, true);
		$criteria->compare('kin_1_phone_1', $this->kin_1_phone_1, true);
		$criteria->compare('kin_1_phone_2', $this->kin_1_phone_2, true);
		$criteria->compare('kin_1_email', $this->kin_1_email, true);
		$criteria->compare('kin_2_name', $this->kin_2_name, true);
		$criteria->compare('kin_2_relationship', $this->kin_2_relationship, true);
		$criteria->compare('kin_2_phone_1', $this->kin_2_phone_1, true);
		$criteria->compare('kin_2_phone_2', $this->kin_2_phone_2, true);
		$criteria->compare('kin_2_email', $this->kin_2_email, true);
		$criteria->compare('is_term_time', $this->is_term_time);
		$criteria->compare('is_casual_staff', $this->is_casual_staff);
		$criteria->compare('preffered_activity', $this->preffered_activity);
		$criteria->compare('external_id', $this->external_id);
		$criteria->compare('created', $this->created, true);
		$criteria->compare('updated', $this->updated, true);
		$criteria->compare('created_by', $this->created_by);
		$criteria->compare('updated_by', $this->updated_by);
		$criteria->compare('title', $this->title);
		$criteria->compare('marital_status', $this->marital_status);
		$criteria->compare('is_moved', $this->is_moved);
		$criteria->compare('holiday_contract_original', $this->holiday_contract_original);
		$criteria->compare('is_override_entitlement', $this->is_override_entitlement);
		$criteria->compare('is_entitlement_created', $this->is_entitlement_created);
		$criteria->compare('profile_photo_thumb', $this->profile_photo_thumb, true);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return StaffPersonalDetails the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public function afterValidate() {
		if (!isset($this->profile_photo) && empty($this->profile_photo)) {
			$this->profile_photo = $this->previous_image;
		}
		parent::afterValidate();
	}

	function getFullName() {
		return $this->first_name . ' ' . $this->last_name;
	}

	public function checkStartLeaveDate($attributes, $params) {
		if (!empty($this->start_date) && !empty($this->leave_date)) {
			if (strtotime($this->start_date) > strtotime($this->leave_date)) {
				$this->addError('start_date', 'Start Date must be smaller than leave Date.');
				$this->addError('leave_date', 'Leave Date must be greater than start Date.');
			}
		}
	}
    
   public function checkEntitlementAlreadyCreated($attributes, $params) {
      $staffHolidaysEntitlement = StaffHolidaysEntitlement::model()->find(array(
            'condition' => 'staff_id = :staff_id AND year = :year AND is_deleted = 0' , 
             'params' => array(
                 ':staff_id' => $this->id,
                 ':year' => $this->entitlementYear
                 )
         ));
		if (!empty($staffHolidaysEntitlement)) {
            $this->addError('entitlementYear', 'Entitlement already created for selected year.');
        }else if(!empty($this->start_date) && (int)date("Y" , $this->start_date) > (int)$this->entitlementYear){
            $this->addError('entitlementYear', 'Entitlement year is less than staff joining date.');
        }else if(!empty($this->leave_date) && (int)date("Y" , $this->leave_date) < (int)$this->entitlementYear){
            $this->addError('entitlementYear', 'Entitlement year is greater than staff leave date.');
        }
	} 

	public function checkEmailUnique($attributes, $params) {
		if (isset($this->email_1) && !empty(trim($this->email_1))) {
			if ($this->isNewRecord) {
				$staffModel = StaffPersonalDetails::model()->findAll(['condition' => 'branch_id = :branch_id AND email_1 = :email_1', 'params' => [':branch_id' => $this->branch_id, ':email_1' => $this->email_1]]);
				if (!empty($staffModel)) {
					$this->addError('email_1', 'Staff with same email Id is already present on the system.');
				}
			} else {
				$staffModel = StaffPersonalDetails::model()->findAll(['condition' => 'branch_id = :branch_id AND email_1 = :email_1 AND id != :id', 'params' => [':branch_id' => $this->branch_id, ':email_1' => $this->email_1, ':id' => $this->id]]);
				if (!empty($staffModel)) {
					$this->addError('email_1', 'Staff with same email Id is already present on the system.');
				}
			}
		}
	}

	public function getColumnNames() {
		$unset_columns = array('id', 'is_active', 'created', 'updated', 'updated_by', 'created_by', 'is_dob_unavaliable', 'is_deleted', 'initial_contract_hours', 'contract_day_monday', 'contract_day_tuesday', 'contract_day_wednesday', 'contract_day_thursday', 'contract_day_friday', 'contract_day_saturday', 'contract_day_sunday');
		$attributes = $this->getAttributes();
		return array_diff(array_keys($attributes), $unset_columns);
	}

	public function getFilter($column_name) {
		$response = array();
		if ($column_name == "is_term_time" || $column_name == "is_casual_staff" || $column_name == "contract_day_monday" || $column_name == "contract_day_tuesday" || $column_name == "contract_day_wednesday" || $column_name == "contract_day_thursday" || $column_name == "contract_day_friday" || $column_name == "contract_day_saturday" || $column_name == "contract_day_sunday") {
			$response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '!=' => "NOT EQUAL TO"), "filter_value" => array(0 => 0, 1 => 1));
		} else if ($column_name == "start_date" || $column_name == "leave_date" || $column_name == "dob") {
			$response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '>' => "GREATER THAN", '<' => 'SMALLER THAN', '>=' => 'GREATER THAN EQUAL TO', '<=' => 'SMALLER THAN EQUAL TO', 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => array());
		} else if ($column_name == "position") {
			$response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '!=' => "NOT EQUAL TO", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => CHtml::listData(PickStaffPosition::model()->findAll(), 'id', 'name'));
		} else if ($column_name == "additional_role") {
			$response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '!=' => "NOT EQUAL TO", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => CHtml::listData(PickStaffAdditionalRole::model()->findAll(), 'id', 'name'));
		} else if ($column_name == "gender") {
			$response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '!=' => "NOT EQUAL TO", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => array('MALE' => 'MALE', 'FEMALE' => 'FEMALE'));
		} else if ($column_name == "room_id") {
			$response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '!=' => "NOT EQUAL TO", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => CHtml::listData(Room::model()->findAllByAttributes(array('branch_id' => Yii::app()->session['branch_id'])), 'id', 'name'));
		} else if ($column_name == "branch_id") {
			$response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '!=' => "NOT EQUAL TO", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => CHtml::listData(Branch::model()->findAllByPk(Yii::app()->session['branch_id']), 'id', 'name'));
		} else {
			$response[$column_name] = array("filter_condition" => array('LIKE' => 'LIKE', 'LIKE %--%' => "LIKE %--%", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL', 'EMPTY' => 'EMPTY'), "filter_value" => array());
		}
		return $response;
	}

	public function getColumnValue($column_name, $column_value) {
		if ($column_name == "is_term_time" || $column_name == "is_casual_staff" || $column_name == "contract_day_monday" || $column_name == "contract_day_tuesday" || $column_name == "contract_day_wednesday" || $column_name == "contract_day_thursday" || $column_name == "contract_day_friday" || $column_name == "contract_day_saturday" || $column_name == "contract_day_sunday") {
			$column_value = ($column_value == 1) ? "Yes" : "No";
		} else if ($column_name == "room_id") {
			$column_value = Room::model()->findByPk($column_value)->name;
		} else if ($column_name == "position") {
			$column_value = PickStaffPosition::model()->findByPk($column_value)->name;
		} else if ($column_name == "additional_role") {
			$column_value = PickStaffAdditionalRole::model()->findByPk($column_value)->name;
		} else if ($column_name == "branch_id") {
			$column_value = Branch::model()->findByPk($column_value)->name;
		} else {
			$column_value = $column_value;
		}
		return $column_value;
	}

	public function getRelatedAttributesNames() {
		$attributes = array('first_name', 'last_name', 'preffered_name', 'gender', 'address_1', 'address_2', 'address_3', 'postcode', 'position', 'additional_role');
		return $attributes;
	}

	public function getName() {
		if ($this->is_active == 0) {
			return $this->first_name . " " . $this->last_name . " *";
		} else {
			return $this->first_name . " " . $this->last_name;
		}
	}

	public static function casualStaffEntitlement($staff_id, $date_of_schedule) {
		$model = StaffPersonalDetails::model()->findByAttributes(array('id' => $staff_id, 'is_casual_staff' => 1));
		if (!empty($model)) {
			$staffHolidayEntitlement = StaffHolidaysEntitlement::model()->find([
				'condition' => 'year = :year AND staff_id = :staff_id',
				'params' => [
					':year' => date('Y', strtotime($date_of_schedule)),
					':staff_id' => $staff_id
				]
			]);
			if (!empty($staffHolidayEntitlement)) {
				$staffHolidaysEntitlementEvent = StaffHolidaysEntitlementEvents::model()->findByAttributes([
					'holiday_id' => $staffHolidayEntitlement->id,
					'opening_balance' => 0,
					'is_changed' => 0,
					'is_transferred' => 0,
					'is_overriden' => 0
				]);
				if (!empty($staffHolidaysEntitlementEvent)) {
					$staffBookingsHours = StaffBookings::staffBookingHoursBetweenDates($staffHolidaysEntitlementEvent->start_date, $staffHolidaysEntitlementEvent->finish_date, $model->id);
					$entitlement = ((self::CASUAL_STAFF_ENTITLEMENT_CONSTANT * $staffBookingsHours) / 100);
					$checkOpeningBalance = StaffHolidaysEntitlementEvents::model()->find([
						'select' => 'sum(entitlement) AS entitlement',
						'condition' => 'holiday_id = :holiday_id AND opening_balance = 1',
						'params' => [
							':holiday_id' => $staffHolidayEntitlement->id,
						]
					]);
					if ($checkOpeningBalance) {
						StaffHolidaysEntitlement::model()->updateByPk($staffHolidayEntitlement->id, [
							'holiday_entitlement_per_year' => customFunctions::roundToQuarter($entitlement + $checkOpeningBalance->entitlement)
						]);
						StaffHolidaysEntitlementEvents::model()->updateByPk($staffHolidaysEntitlementEvent->id, ['entitlement' => customFunctions::roundToQuarter($entitlement)]);
					} else {
						StaffHolidaysEntitlement::model()->updateByPk($staffHolidayEntitlement->id, [
							'holiday_entitlement_per_year' => customFunctions::roundToQuarter($entitlement)
						]);
						StaffHolidaysEntitlementEvents::model()->updateByPk($staffHolidaysEntitlementEvent->id, ['entitlement' => customFunctions::roundToQuarter($entitlement)]);
					}
				}
			}
			return true;
		} else {
			return false;
		}
	}

	public static function getCasualStaffEntitlement($staff_id, $start_date, $finish_date) {
		$entitlement = 0;
		$bookingsHours = StaffBookings::staffBookingHoursBetweenDates($start_date, $finish_date, $staff_id);
		$entitlement = ((self::CASUAL_STAFF_ENTITLEMENT_CONSTANT * $bookingsHours) / 100);
		return customFunctions::roundToQuarter($entitlement);
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
		if ($this->is_dob_unavaliable == 1) {
			$this->dob = NULL;
		}
		if ($this->is_reviewer) {
			$this->can_publish_observations = 1;
		}
		return parent::beforeSave();
	}

	/**
	 * Deleting all the staff bookings once the leave date is set for the staff
	 */
	public function afterSave() {
		if (isset($this->leave_date) && !empty($this->leave_date) && ($this->is_moved == 0)) {
			$criteria = new CDbCriteria();
			$criteria->condition = "date_of_schedule > :leave_date and staff_id = :staff_id";
			$criteria->params = array(':leave_date' => date("Y-m-d", strtotime($this->leave_date)), ':staff_id' => $this->id);
			$staffBookingModel = StaffBookings::model()->findAll($criteria);
			foreach ($staffBookingModel as $booking) {
				$booking->is_deleted = 1;
				$booking->save();
			}
			$criteria = new CDbCriteria();
			$criteria->condition = "start_date > :leave_date and staff_id = :staff_id";
			$criteria->params = array(':leave_date' => date("Y-m-d", strtotime($this->leave_date)), ':staff_id' => $this->id);
			$staffHolidayModel = StaffHolidays::model()->findAll($criteria);
			foreach ($staffHolidayModel as $holiday) {
				StaffHolidays::model()->updateByPk($holiday->id, [
					'is_deleted' => 1
				]);
			}
		}

		if (isset($this->leave_date) && !empty($this->leave_date)) {
			if ($this->oldAttributes['leave_date'] != date("Y-m-d", strtotime($this->leave_date))) {
				$staffHolidaysEntitlement = StaffHolidaysEntitlement::model()->findByAttributes(array('year' => date("Y", strtotime($this->leave_date)), 'staff_id' => $this->id));
				if (!empty($staffHolidaysEntitlement)) {
					$staffHolidaysEntitlementEvents = StaffHolidaysEntitlementEvents::model()->find(array('condition' => ':date BETWEEN start_date AND finish_date AND holiday_id = :holiday_id AND is_transferred = 0 AND is_overriden = 0 AND opening_balance = 0', 'params' => array(':holiday_id' => $staffHolidaysEntitlement->id, ':date' => date("Y-m-d", strtotime($this->leave_date)))));
					if (!empty($staffHolidaysEntitlementEvents)) {
						$entitlement = $staffHolidaysEntitlement->getEntitlement($staffHolidaysEntitlement->year, $staffHolidaysEntitlementEvents->start_date, date("Y-m-d", strtotime($this->leave_date)), $staffHolidaysEntitlementEvents->contract_hours, $staffHolidaysEntitlement->days_per_year);
						$staffHolidaysEntitlementEvents->entitlement = $entitlement;
						$staffHolidaysEntitlementEvents->finish_date = date("Y-m-d", strtotime($this->leave_date));
						if (!$staffHolidaysEntitlementEvents->save()) {
							throw new Exception(CHtml::errorSummary($staffHolidaysEntitlementEvents, "", "", array('class' => 'customErrors')));
						}
						$toBeDeletedEvents = StaffHolidaysEntitlementEvents::model()->findAll(array('condition' => 'start_date >= :start_date AND holiday_id = :holiday_id', 'params' => array(':holiday_id' => $staffHolidaysEntitlementEvents->holiday_id, ':start_date' => date("Y-m-d", strtotime($staffHolidaysEntitlementEvents->finish_date)))));
						if (!empty($toBeDeletedEvents)) {
							foreach ($toBeDeletedEvents as $toBeDeletedEvent) {
								$toBeDeletedEvent->is_deleted = 1;
								if (!$toBeDeletedEvent->save()) {
									throw new Exception(CHtml::errorSummary($toBeDeletedEvent, "", "", array('class' => 'customErrors')));
								}
							}
						}
						$staffHolidaysEntitlement->holiday_entitlement_per_year = StaffHolidaysEntitlementEvents::model()->find(array('select' => 'sum(entitlement) AS total_entitlement', 'condition' => 'holiday_id = :holiday_id', 'params' => array(':holiday_id' => $staffHolidaysEntitlement->id)))->total_entitlement;
						$staffHolidaysEntitlement->finish_date = date("Y-m-d", strtotime($this->leave_date));
						if (!$staffHolidaysEntitlement->save()) {
							throw new Exception(CHtml::errorSummary($staffHolidaysEntitlement, "", "", array('class' => 'customErrors')));
						}
					}
				}
				$toBeDeletedEntitlement = StaffHolidaysEntitlement::model()->findAll(array('condition' => 'year > :year AND staff_id = :staff_id', 'params' => array(':year' => date("Y", strtotime($this->leave_date)), ':staff_id' => $this->id)));
				if (!empty($toBeDeletedEntitlement)) {
					foreach ($toBeDeletedEntitlement as $events) {
						$events->is_deleted = 1;
						if (!$events->save()) {
							throw new Exception(CHtml::errorSummary($events, "", "", array('class' => 'customErrors')));
						}
					}
				}
			}
		}

		if (isset($this->start_date) && !empty($this->start_date)) {
			if ($this->oldAttributes['start_date'] != date("Y-m-d", strtotime($this->start_date))) {
				$staffHolidaysEntitlement = StaffHolidaysEntitlement::model()->findByAttributes(array('year' => date("Y", strtotime($this->start_date)), 'staff_id' => $this->id));
				if (!empty($staffHolidaysEntitlement)) {
					$staffHolidaysEntitlementEvents = StaffHolidaysEntitlementEvents::model()->find(array('condition' => ':date BETWEEN start_date AND finish_date AND holiday_id = :holiday_id AND is_transferred = 0 AND is_overriden = 0 AND opening_balance = 0', 'params' => array(':holiday_id' => $staffHolidaysEntitlement->id, ':date' => date("Y-m-d", strtotime($this->start_date)))));
					if (!empty($staffHolidaysEntitlementEvents)) {
						$entitlement = $staffHolidaysEntitlement->getEntitlement($staffHolidaysEntitlement->year, date("Y-m-d", strtotime($this->start_date)), $staffHolidaysEntitlementEvents->finish_date, $staffHolidaysEntitlementEvents->contract_hours, $staffHolidaysEntitlement->days_per_year);
						$staffHolidaysEntitlementEvents->entitlement = $entitlement;
						$staffHolidaysEntitlementEvents->start_date = date("Y-m-d", strtotime($this->start_date));
						if (!$staffHolidaysEntitlementEvents->save()) {
							throw new Exception(CHtml::errorSummary($staffHolidaysEntitlementEvents, "", "", array('class' => 'customErrors')));
						}
						$toBeDeletedEvents = StaffHolidaysEntitlementEvents::model()->findAll(array('condition' => 'finish_date <= :finish_date AND holiday_id = :holiday_id', 'params' => array(':holiday_id' => $staffHolidaysEntitlementEvents->holiday_id, ':finish_date' => date("Y-m-d", strtotime($staffHolidaysEntitlementEvents->start_date)))));
						if (!empty($toBeDeletedEvents)) {
							foreach ($toBeDeletedEvents as $toBeDeletedEvent) {
								$toBeDeletedEvent->is_deleted = 1;
								if (!$toBeDeletedEvent->save()) {
									throw new Exception(CHtml::errorSummary($toBeDeletedEvent, "", "", array('class' => 'customErrors')));
								}
							}
						}
						$staffHolidaysEntitlement->holiday_entitlement_per_year = StaffHolidaysEntitlementEvents::model()->find(array('select' => 'sum(entitlement) AS total_entitlement', 'condition' => 'holiday_id = :holiday_id', 'params' => array(':holiday_id' => $staffHolidaysEntitlement->id)))->total_entitlement;
						$staffHolidaysEntitlement->start_date = date("Y-m-d", strtotime($this->start_date));
						if (!$staffHolidaysEntitlement->save()) {
							throw new Exception(CHtml::errorSummary($staffHolidaysEntitlement, "", "", array('class' => 'customErrors')));
						}
					}
				}
				$toBeDeletedEntitlement = StaffHolidaysEntitlement::model()->findAll(array('condition' => 'year < :year AND staff_id = :staff_id', 'params' => array(':year' => date("Y", strtotime($this->start_date)), ':staff_id' => $this->id)));
				if (!empty($toBeDeletedEntitlement)) {
					foreach ($toBeDeletedEntitlement as $events) {
						$events->is_deleted = 1;
						if (!$events->save()) {
							throw new Exception(CHtml::errorSummary($events, "", "", array('class' => 'customErrors')));
						}
					}
				}
			}
		}
		parent::afterSave();
	}

	public function staffUrn() {
		if ($this->isNewRecord) {
			$companyModel = Company::currentCompany();
			$criteria = new CDbCriteria();
			$criteria->select = "max(cast(staff_urn as unsigned)) AS max_staff_urn";
			$max_staff_urn = StaffPersonalDetails::model()->resetScope()->find($criteria)->max_staff_urn;
			if (isset($companyModel->staff_urn_number) && !empty($companyModel->staff_urn_number)) {
				if ($companyModel->staff_urn_number > $max_staff_urn) {
					return (int) $companyModel->staff_urn_number;
				} else {
					return (int) $max_staff_urn + 1;
				}
			} else {
				return (int) $max_staff_urn + 1;
			}
		} else {
			if (empty(trim($this->staff_urn))) {
				$companyModel = Company::currentCompany();
				$criteria = new CDbCriteria();
				$criteria->select = "max(cast(staff_urn as unsigned)) AS max_staff_urn";
				$max_staff_urn = StaffPersonalDetails::model()->resetScope()->find($criteria)->max_staff_urn;
				if (isset($companyModel->staff_urn_number) && !empty($companyModel->staff_urn_number)) {
					if ($companyModel->staff_urn_number > $max_staff_urn) {
						return (int) $companyModel->staff_urn_number;
					} else {
						return (int) $max_staff_urn + 1;
					}
				} else {
					return (int) $max_staff_urn + 1;
				}
			} else {
				return $this->staff_urn;
			}
		}
	}

	public static function getHourlyRate($staff_id, $date) {
		$model = self::model()->findByPk($staff_id);
		if (!empty($model)) {
			$eventsModel = StaffEventDetails::model()->findAll(['condition' => 'staff_id = :staff_id AND (status is NULL OR status = 0)', 'order' => 'title_date_1_value, title_date_2_value', 'params' => [':staff_id' => $staff_id]]);
			if (!empty($eventsModel)) {
				$rate = $model->hourly_rate_basic;
				foreach ($eventsModel as $events) {
					if ($events->event->name == "Pay Increase" || $events->event->name == "Pay Decrease") {
						if (!empty($events->title_date_1_value) || ($events->title_date_1_value != NULL)) {
							if (!empty($events->title_date_2_value) || ($events->title_date_2_value != NULL)) {
								if (strtotime($date) >= strtotime(date("Y-m-d", strtotime($events->title_date_1_value))) && strtotime($date) <= strtotime(date("Y-m-d", strtotime($events->title_date_2_value)))) {
									$rate = $events->title_description_value;
									break;
								} else {
									if (($events->event_audit_value != NULL) && (!empty($events->event_audit_value))) {
										$rate = $events->event_audit_value;
									} else {
										$rate = $model->hourly_rate_basic;
									}
								}
							} else {
								if (strtotime(date("Y-m-d", strtotime($events->title_date_1_value))) <= strtotime($date)) {
									$rate = $events->title_description_value;
									break;
								} else {
									if (($events->event_audit_value != NULL) && (!empty($events->event_audit_value))) {
										$rate = $events->event_audit_value;
									} else {
										$rate = $model->hourly_rate_basic;
									}
								}
							}
						}
					} else {
						continue;
					}
				}
				return $rate;
			} else {
				return $model->hourly_rate_basic;
			}
		}
	}

	public function validateUrn($attributes, $params) {
		if (!empty($this->staff_urn) && ($this->staff_urn != NULL)) {
			if ($this->isNewRecord) {
				$companyModel = Company::currentCompany();
				if ($companyModel->urn_type == Company::BRANCH_LEVEL_URN) {
					$branchModel = Branch::currentBranch();
					$criteria = new CDbCriteria();
					$criteria->select = "staff_urn = :staff_urn";
					$criteria->condition = "branch_id = :branch_id AND staff_urn = :staff_urn and is_moved =0";
					$criteria->params = array(':branch_id' => $branchModel->id, ':staff_urn' => $this->staff_urn);
					if (StaffPersonalDetails::model()->resetScope()->count($criteria) > 0) {
						$this->addError('staff_urn', 'Staff Urn has already been taken.');
					}
				} else {
					$criteria = new CDbCriteria();
					$criteria->condition = "staff_urn = :staff_urn and is_moved =0 ";
					$criteria->params = array(':staff_urn' => $this->staff_urn);
					if (StaffPersonalDetails::model()->resetScope()->count($criteria) > 0) {
						$this->addError('staff_urn', 'Staff Urn has already been taken.');
					}
				}
			} else {
				$companyModel = Company::currentCompany();
				if ($companyModel->urn_type == Company::BRANCH_LEVEL_URN) {
					$branchModel = Branch::currentBranch();
					$criteria = new CDbCriteria();
					$criteria->select = "staff_urn = :staff_urn";
					$criteria->condition = "branch_id = :branch_id AND staff_urn = :staff_urn AND id != :id and is_moved = 0";
					$criteria->params = array(':branch_id' => $branchModel->id, ':staff_urn' => $this->staff_urn, ':id' => $this->id);
					if (StaffPersonalDetails::model()->resetScope()->count($criteria) > 0) {
						$this->addError('staff_urn', 'Staff Urn has already been taken.');
					}
				} else {
					$criteria = new CDbCriteria();
					$criteria->condition = "staff_urn = :staff_urn AND id != :id and is_moved = 0";
					$criteria->params = array(':staff_urn' => $this->staff_urn, ':id' => $this->id);
					if (StaffPersonalDetails::model()->resetScope()->count($criteria) > 0) {
						$this->addError('staff_urn', 'Staff Urn has already been taken.');
					}
				}
			}
		}
	}

	public function afterFind() {
		$staffHolidaysEntitlementModel = StaffHolidaysEntitlement::model()->findByAttributes(array('year' => date("Y"), 'staff_id' => $this->id));
		if (!empty($staffHolidaysEntitlementModel)) {
			$this->holiday_contract = $staffHolidaysEntitlementModel->days_per_year;
			$this->holiday_entitlement = $staffHolidaysEntitlementModel->holiday_entitlement_per_year;
			if (isset(self::$contract_hours_as_of)) {
				$holidayEntitlementEvents = StaffHolidaysEntitlementEvents::model()->find(array('condition' => ':date BETWEEN start_date and finish_date AND holiday_id = :holiday_id', 'params' => array(':date' => self::$contract_hours_as_of, ':holiday_id' => $staffHolidaysEntitlementModel->id)));
			} else {
				$holidayEntitlementEvents = StaffHolidaysEntitlementEvents::model()->find(array('condition' => ':date BETWEEN start_date and finish_date AND holiday_id = :holiday_id', 'params' => array(':date' => date("Y-m-d"), ':holiday_id' => $staffHolidaysEntitlementModel->id)));
			}
			if (!empty($holidayEntitlementEvents)) {
				$this->contract_hours = $holidayEntitlementEvents->contract_hours;
				$this->no_of_days = $holidayEntitlementEvents->no_of_days;
			}
		}
		$this->oldAttributes = $this->attributes;
		$hrSettingsModel = HrSetting::model()->findByAttributes(['branch_id' => Branch::currentBranch()->id]);
		if (!empty($hrSettingsModel)) {
			if (!empty($this->start_date) && isset($this->start_date)) {
				$differenceInYears = date("Y") - date("Y", strtotime($this->start_date));
				if (isset(self::$holiday_contract_as_of)) {
					$differenceInYears = self::$holiday_contract_as_of - date("Y", strtotime($this->start_date));
				}
				if ($differenceInYears > $hrSettingsModel->max_recursive_year) {
					$differenceInYears = $hrSettingsModel->max_recursive_year + 1;
				}
				$differenceInYears = $differenceInYears - 1;
				$incremental_value = (int) ($differenceInYears / $hrSettingsModel->recursive_year);
				$incremental_value = $incremental_value * $hrSettingsModel->holiday_number;
				$this->holiday_contract = customFunctions::round($this->holiday_contract_original + $incremental_value, 2);
			}
		}
		self::$holiday_contract_as_of = NULL;
		self::$contract_hours_as_of = NULL;
		$this->previous_image = $this->profile_photo;
		return parent::afterFind();
	}

	public static function getHolidayEntitlementHoursPerYear($staff_id, $date) {
		$staffHolidaysEntitlementModel = StaffHolidaysEntitlement::model()->findByAttributes(array('year' => date("Y", strtotime($date)), 'staff_id' => $staff_id));
		if (!empty($staffHolidaysEntitlementModel)) {
			return customFunctions::round($staffHolidaysEntitlementModel->holiday_entitlement_per_year, 2);
		}
		return customFunctions::round(0, 2);
	}

	public function getStaffWorkingDays() {
		$workingDays = array();
		if ($this->contract_day_monday == 1) {
			$workingDays[] = 1;
		}
		if ($this->contract_day_tuesday == 1) {
			$workingDays[] = 2;
		}
		if ($this->contract_day_wednesday == 1) {
			$workingDays[] = 3;
		}
		if ($this->contract_day_thursday == 1) {
			$workingDays[] = 4;
		}
		if ($this->contract_day_friday == 1) {
			$workingDays[] = 5;
		}
		if ($this->contract_day_saturday == 1) {
			$workingDays[] = 6;
		}
		if ($this->contract_day_sunday == 1) {
			$workingDays[] = 7;
		}
		return $workingDays;
	}

	public function getTags() {
		$tags = array();
		if (!empty($this->tags)) {
			foreach ($this->tags as $tag) {
				$tags[] = $tag->tag->name;
			}
		}
		return implode(", ", $tags);
	}

	public function eyLogIntegration() {
		if ($this->branch->is_integration_enabled == 1) {
			if (!empty($this->branch->api_key) && !empty($this->branch->api_password) && !empty($this->branch->api_url)) {
				$ch = curl_init($this->branch->api_url . StaffPersonalDetails::STAFF_API_PATH);
				$staff_data = array(
					'api_key' => $this->branch->api_key,
					'api_password' => $this->branch->api_password,
					'staff' => array(
						[
							'first_name' => $this->first_name,
							'last_name' => $this->last_name,
							'email' => $this->email_1,
							'group_name' => isset($this->room_id) ? ($this->room->name) : NULL,
							'external_id' => 'eyman-' . $this->id,
							'photo' => $this->profile_photo_integration,
							'group_external_id' => isset($this->room_id) ? ($this->room->external_id) : NULL,
							'can_publish_observations' => isset($this->can_publish_observations) ? $this->can_publish_observations : 0,
							'is_reviewer' => isset($this->is_reviewer) ? $this->is_reviewer : 0,
						]
					)
				);
				$staff_data = json_encode($staff_data);
				curl_setopt_array($ch, array(
					CURLOPT_FOLLOWLOCATION => 1,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_POST => 1,
					CURLOPT_POSTFIELDS => $staff_data,
					CURLOPT_HEADER => 0,
					CURLOPT_USERAGENT => "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)",
					CURLOPT_SSL_VERIFYPEER => false,
				));
				$response = curl_exec($ch);
				$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				if ($httpcode == "404") {
					throw new Exception("Please check whether API path is a valid URL");
				}
				if (curl_errno($ch) && $httpcode != "404") {
					throw new Exception(curl_error($ch));
				}
				curl_close($ch);
				$response = json_decode($response, TRUE);
				if ($response['status'] == "failure") {
					throw new Exception($response['message']);
				}
				if ($response['response'][0]['message'] != "Added" && $response['response'][0]['message'] != "Updated") {
					throw new Exception($response['response'][0]['message']);
				}
				if ($response['response'][0]['message'] == "Added") {
					$external_id = !empty($response['response'][0]['id']) ? $response['response'][0]['id'] : NULL;
					StaffPersonalDetails::model()->updateByPk($this->id, ['external_id' => $external_id]);
				}
			} else {
				throw new Exception("API key/password/url are not set in Branch Settings");
			}
		}
	}

	public function getProfileImageThumb() {
		if ($this->profile_photo == NULL || empty($this->profile_photo)) {
			return Yii::app()->request->getBaseUrl(TRUE) . "/images/create-user.jpg";
		} else {
			return GlobalPreferences::getSslUrl() . $this->profile_photo_thumb;
		}
	}

	public function getProfileImage() {
		if ($this->profile_photo == NULL || empty($this->profile_photo) || $this->isNewRecord) {
			return Yii::app()->request->getBaseUrl(TRUE) . "/images/create-user.jpg";
		} else {
			return GlobalPreferences::getSslUrl() . $this->profile_photo;
		}
	}

	public function validateExternalId($attributes, $params) {
		if ($this->isNewRecord) {
			if (isset($this->external_id) && !empty($this->external_id)) {
				$model = StaffPersonalDetails::model()->resetScope(true)->findByAttributes([
					'branch_id' => $this->branch_id,
					'external_id' => $this->external_id
				]);
				if (!empty($model)) {
					$this->addError('child_urn', 'Staff with same external id is already present - ' . $model->id);
				}
			}
		}
	}

	public function uploadProfilePhoto() {
		$rackspace = new eyManRackspace();
		$rackspace->uploadObjects([[
			'name' => "/images/staff/" . $this->file_name,
			'body' => $this->profile_photo_raw->image()
			], [
				'name' => "/images/staff/thumbs/" . $this->file_name,
				'body' => $this->profile_photo_thumb_raw->image()
			]
		]);
		$this->profile_photo = "/images/staff/" . $this->file_name;
		$this->profile_photo_thumb = "/images/staff/thumbs/" . $this->file_name;
	}

	public function staffTagsForList() {
		$staffDetails = $this->with(['tags:deleted'])->findByPk($this->id);
		$active_tags = $staffDetails->tags;
		if (!empty($active_tags)) {
			foreach ($active_tags as $tag) {
				if (isset($tag['tag']['name'][0]) && !empty($tag['tag']['color']) && !empty($tag['tag']['name'][0])) {
					$color = $tag['tag']['color'];
					$first_letter = $tag['tag']['name'][0];
					echo "<li><span class='$color' style='border-radius: 0%;'>$first_letter</span></li>";
				}
			}
		}
	}

}
