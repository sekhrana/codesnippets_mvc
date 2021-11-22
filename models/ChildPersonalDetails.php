<?php

//Demo Commit
/**
 * This is the model class for table "tbl_child_personal_details".
 *
 * The followings are the available columns in table 'tbl_child_personal_details':
 * @property integer $id
 * @property string $first_name
 * @property string $middle_name
 * @property string $last_name
 * @property string $child_urn
 * @property string $child_urn_prefix
 * @property string $child_urn_suffix
 * @property string $preffered_name
 * @property string $address_1
 * @property string $address_2
 * @property string $address_3
 * @property string $postcode
 * @property string $dob
 * @property string $birth_certificate
 * @property string $is_term_time
 * @property string $is_funding
 * @property string $booking_type
 * @property string $enroll_date
 * @property string $start_date
 * @property string $leave_date
 * @property string $gender
 * @property integer $client_id
 * @property string $profile_photo
 * @property string $profile_photo_thumb
 * @property integer $branch_id
 * @property integer $is_deleted
 * @property integer $is_active
 * @property integer $room_id
 * @property double $funded_hours
 * @property double $discount
 * @property string $link_to_staff
 * @property integer $key_person
 * @property integer $sibling_id
 * @property integer $preffered_session
 * @property string $last_updated
 * @property integer $last_updated_by
 * @property string $latitude
 * @property string $longitude
 * @property double $monthly_invoice_amount
 * @property string $monthly_invoice_start_date
 * @property string $monthly_invoice_finish_date
 * @property integer $external_id
 * @property integer $is_lac
 * @property integer $is_pupil_premium
 * @property integer $children_of_concern
 *
 * The followings are the available model relations:
 * @property ChildBookings[] $childBookings
 * @property ChildDocumentDetails[] $childDocumentDetails
 * @property ChildEventDetails[] $childEventDetails
 * @property ChildGeneralDetails[] $childGeneralDetails
 * @property ChildFundingDetails[] $childFundingDetails
 * @property ChildInvoice[] $childInvoices
 * @property ChildInvoiceTransactions[] $childInvoiceTransactions
 * @property ChildMedicalDetails[] $childMedicalDetails
 * @property ChildParentalDetails[] $childParentalDetails
 * @property Branch $branch
 *  @property Room $room
 * @property StaffPersonalDetails $keyPerson
 * @property SessionRates $prefferedSession
 * @property ParentChildMapping[] $parentChildMappings
 */
class ChildPersonalDetails extends CActiveRecord {

	const CHILDREN_API_PATH = "/api-eyman/children";

	public $prevProfilePhoto;
	public $import_file;
	public $date_columns = array('enroll_date', 'start_date', 'leave_date', 'dob', 'effective_date', 'monthly_invoice_start_date', 'monthly_invoice_finish_date');
	public $effective_date;
	public $full_name;
	public $age;
	public $invoice_from_date;
	public $invoice_to_date;
	public $invoice_from_month;
	public $invoice_to_month;
	public $is_all_child;
	public $max_child_urn;
	public $profile_photo_integration;
	public $isSibling = false;
	public $isSiblingParent = false;
	public $siblingChildModel;
	public $profile_photo_raw;
	public $profile_photo_thumb_raw;
	public $file_name;
	public $previous_image;
	public static $term_id;
	public $newBranch;
	public $previousBranch;
	public $address;

	const EMERGENCY_BOOKER = "EMERGENCY";
	const PERMANENT = "PERMANENT";
	const SHIFT = "SHIFT";

	public function defaultScope() {
		$status = " AND " . $this->getTableAlias(false, false) . ".is_active = 1";
		$inactiveStatus = " AND " . $this->getTableAlias(false, false) . ".is_active = 0";
		if (get_class(Yii::app()) === "CWebApplication") {
			if (Yii::app()->session['role'] == "superAdmin") {
				if (isset(Yii::app()->user->skipActiveScopeChild) && (Yii::app()->user->skipActiveScopeChild === TRUE)) {
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
				if (isset(Yii::app()->user->skipActiveScopeChild) && (Yii::app()->user->skipActiveScopeChild === TRUE)) {
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
				if (isset(Yii::app()->user->skipActiveScopeChild) && (Yii::app()->user->skipActiveScopeChild === TRUE)) {
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
				if (isset(Yii::app()->user->skipActiveScopeChild) && (Yii::app()->user->skipActiveScopeChild === TRUE)) {
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
				if (isset(Yii::app()->user->skipActiveScopeChild) && (Yii::app()->user->skipActiveScopeChild === TRUE)) {
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
				$branchId = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->id))->branch_id;
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " .
					$this->getTableAlias(false, false) . ".branch_id =" . $branchId,
				);
			}
			if (Yii::app()->session['role'] == "hrAdmin" || Yii::app()->session['role'] == User::HR_STANDARD) {
				if (isset(Yii::app()->user->skipActiveScopeChild) && (Yii::app()->user->skipActiveScopeChild === TRUE)) {
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
				if (isset(Yii::app()->user->skipActiveScopeChild) && (Yii::app()->user->skipActiveScopeChild === TRUE)) {
					return array(
						'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")" . $inactiveStatus,
					);
				} else {
					return array(
						'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")" . $status,
					);
				}
			}
		}
		if (get_class(Yii::app()) === "CConsoleApplication") {
			return array(
				'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0" . $status,
			);
		}
	}

	public function scopes() {
		$t = $this->getTableAlias(false, false);
		$this->resetScope();
		if (Yii::app()->session['role'] == "superAdmin") {
			return array(
				'allowed' => array(
					'condition' => $t . ".is_deleted = 0",
				)
			);
		}

		if (Yii::app()->session['role'] == "companyAdministrator") {
			$company_id = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()))->company_id;
			$branchString = implode(',', customFunctions::getBranchesByCompany($company_id));
			return array(
				'allowed' => array(
					'condition' => $t . ".is_deleted = 0 and " . $t . ".branch_id IN(" . $branchString . ")",
				)
			);
		}

		if (Yii::app()->session['role'] == "areaManager") {
			$userMappingModal = UserBranchMapping::model()->findAllByAttributes(array('user_id' => Yii::app()->user->getId()));
			$branchString = implode(',', customFunctions::getBranchesOfAreaManager($userMappingModal));
			return array(
				'allowed' => array(
					'condition' => $t . ".is_deleted = 0 and " . $t . ".branch_id IN(" . $branchString . ")",
				)
			);
		}
		if (Yii::app()->session['role'] == "branchManager") {
			$userMapping = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()));
			return array(
				'allowed' => array(
					'condition' => $t . ".is_deleted = 0 and " . $t . ".branch_id =" . $userMapping->branch_id,
				)
			);
		}
		if (Yii::app()->session['role'] == "branchAdmin") {
			$userMapping = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()));
			return array(
				'allowed' => array(
					'condition' => $t . ".is_deleted = 0 and " . $t . ".branch_id =" . $userMapping->branch_id,
				)
			);
		}
		if (Yii::app()->session['role'] == "staff") {
			$branchId = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->id))->branch_id;
			return array(
				'allowed' => array(
					'condition' => $t . ".is_deleted = 0 and " .
					$t . ".branch_id =" . $branchId,
				)
			);
		}
		if (Yii::app()->session['role'] == "hrAdmin" || Yii::app()->session['role'] == User::HR_STANDARD) {
			return array(
				'allowed' => array(
					'condition' => $t . ".is_deleted = 0",
				)
			);
		}
		if (Yii::app()->session['role'] == "accountsAdmin") {
			$company_id = UserBranchMapping::model()->findByAttributes(array('user_id' => Yii::app()->user->getId()))->company_id;
			$branchString = implode(',', customFunctions::getBranchesByCompany($company_id));
			return array(
				'allowed' => array(
					'condition' => $t . ".is_deleted = 0 and " . $t . ".branch_id IN(" . $branchString . ")",
				)
			);
		}
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'tbl_child_personal_details';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('first_name, last_name, dob, gender', 'required', 'except' => 'registerYourChild'),
			array('first_name, last_name, dob, gender', 'required', 'on' => 'registerYourChild'),
			array('client_id, branch_id, is_deleted, is_active, room_id, key_person, sibling_id, preffered_session, last_updated_by, external_id, is_lac, is_pupil_premium', 'numerical', 'integerOnly' => true),
			array('first_name, middle_name, last_name, preffered_name, address_1, address_2, address_3', 'length', 'max' => 255),
			//array('first_name','match', 'pattern'=>'/^[A-z]+$/', 'message'=>'Numbers cannot be used in first name'),
			array('funded_hours, discount, monthly_invoice_amount', 'numerical'),
			array('postcode, birth_certificate', 'length', 'max' => 20),
			array('is_term_time, is_funding, children_of_concern', 'length', 'max' => 1),
			array('booking_type', 'length', 'max' => 9),
			array('gender', 'length', 'max' => 6),
			array('monthly_invoice_start_date, monthly_invoice_finish_date', 'default', 'setOnEmpty' => true, 'value' => NULL),
			array('child_urn, link_to_staff, latitude, longitude, child_urn_prefix, child_urn_suffix', 'length', 'max' => 45),
			array('profile_photo', 'file', 'allowEmpty' => true, 'types' => 'jpg,jpeg,gif,png', 'on' => 'insert,update'),
			array('dob, enroll_date, start_date, leave_date', 'date', 'format' => 'dd-mm-yyyy', 'message' => 'Please input a valid date type.', 'allowEmpty' => true),
			array('dob, enroll_date, start_date, leave_date', 'default', 'setOnEmpty' => true, 'value' => NULL),
			array('start_date', 'checkStartLeaveDate'),
			array('start_date', 'checkStartDobDate'),
			array('start_date', 'checkStartEnrollDate'),
			array('child_urn', 'validateUrn'),
			array('external_id', 'validateExternalId', 'on' => 'eyLogDataImport'),
			array('monthly_invoice_start_date, monthly_invoice_finish_date', 'safe'),
			//array('room_id', 'checkCapacity'),
                        array('branch_id', 'checkChildCount'),
			array('import_file', 'file', 'allowEmpty' => true, 'types' => 'xls,csv', 'on' => 'import'),
			//array('preffered_name, first_name, last_name', 'match', 'pattern' => '/^[A-Za-z ]+$/u', 'message' => 'Only Alphabets are allowed'),
			//array('birth_certificate', 'numerical', 'integerOnly' => true, 'message' => 'Only Numbers are allowed'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, child_urn, child_urn_prefix, child_urn_suffix, first_name, middle_name, last_name, preffered_name, address_1, address_2, address_3, postcode, dob, birth_certificate, is_term_time, is_funding, booking_type, enroll_date, start_date, leave_date, gender, client_id, profile_photo,profile_photo_thumb, branch_id, is_deleted,is_active, room_id, key_person, discount, funded_hours, link_to_staff, sibling_id, preffered_session, last_updated, last_updated_by, latitude, longitude, age, monthly_invoice_amount, external_id, monthly_invoice_start_date, monthly_invoice_finish_date, is_lac, is_pupil_premium,children_of_concern', 'safe', 'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'childBookings' => array(self::HAS_MANY, 'ChildBookings', 'child_id'),
			'childDocumentDetails' => array(self::HAS_ONE, 'ChildDocumentDetails', 'child_id'),
			'childEventDetails' => array(self::HAS_ONE, 'ChildEventDetails', 'child_id'),
			'childMedicalDetails' => array(self::HAS_ONE, 'ChildMedicalDetails', 'child_id'),
			'childParentalDetails' => array(self::HAS_ONE, 'ChildParentalDetails', 'child_id'),
			'childGeneralDetails' => array(self::HAS_ONE, 'ChildGeneralDetails', 'child_id'),
			'branch' => array(self::BELONGS_TO, 'Branch', 'branch_id'),
			'room' => array(self::BELONGS_TO, 'Room', 'room_id'),
			'keyPerson' => array(self::BELONGS_TO, 'StaffPersonalDetails', 'key_person'),
			'prefferedSession' => array(self::BELONGS_TO, 'SessionRates', 'preffered_session'),
			'payments' => array(self::HAS_MANY, 'Payments', 'child_id'),
			'tags' => array(self::HAS_MANY, 'ChildTagsMapping', 'child_id', 'with' => 'tag'),
			'childFundingDetails' => array(self::HAS_MANY, 'ChildFundingDetails', 'child_id'),
			'childTermFunding' => array(self::HAS_ONE, 'ChildFundingDetails', 'child_id', 'condition' => 'childTermFunding.term_id = :term_id', 'params' => [':term_id' => self::$term_id]),
			'parentChildMappings' => array(self::HAS_MANY, 'ParentChildMapping', 'child_id'),
			'parents' => array(self::MANY_MANY, 'Parents', 'tbl_parent_child_mapping(child_id, parent_id)', 'condition' => 'parents_parents.is_deleted = 0'),
			'parentChildMapping' => array(self::HAS_ONE, 'ParentChildMapping', 'child_id'),
		);
	}

	public function behaviors() {
		return array(
			'dateFormatter' => array(
				'class' => 'application.components.DateFormatter',
				'date_columns' => array('enroll_date', 'start_date', 'leave_date', 'dob', 'effective_date', 'monthly_invoice_start_date', 'monthly_invoice_finish_date')
			)
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'child_urn' => 'Child URN',
			'child_urn_prefix' => 'Child Urn Prefix',
			'child_urn_suffix' => 'Child Urn Suffix',
			'first_name' => 'First Name',
			'middle_name' => 'Middle Name',
			'last_name' => 'Last Name',
			'preffered_name' => 'Preferred Name',
			'address_1' => 'Address 1',
			'address_2' => 'Address 2',
			'address_3' => 'Address 3',
			'postcode' => 'Postcode',
			'dob' => 'Date of Birth',
			'birth_certificate' => 'Birth Certificate No.',
			'is_term_time' => 'Term Time Only',
			'is_funding' => 'Funded Only',
			'booking_type' => 'Booking Type',
			'enroll_date' => 'Enrolment Date',
			'start_date' => 'Start Date',
			'leave_date' => 'Leave Date',
			'gender' => 'Gender',
			'client_id' => 'Client Reference',
			'profile_photo' => 'Profile Photo',
			'profile_photo' => 'Profile Photo Thumb',
			'branch_id' => 'Branch',
			'is_deleted' => 'Deleted',
			'is_active' => 'Active',
			'room_id' => 'Room',
			'key_person' => 'Key Person',
			'discount' => 'Default Discount (%)',
			'funded_hours' => 'Funded Hours',
			'link_to_staff' => 'Link To Staff',
			'sibling_id' => (!empty($this->getSiblings())) ? 'Siblings [ ' . implode(", ", $this->getSiblings()) . " ]" : "Siblings",
			'preffered_session' => 'Preferred Session',
			'latitude' => 'Latitude',
			'longitude' => 'Longitude',
			'monthly_invoice_amount' => 'Monthly Invoice Amount',
			'age' => 'Age',
			'external_id' => 'External ID',
			'invoice_from_date' => 'From Date',
			'invoice_to_date' => 'To Date',
			'monthly_invoice_start_date' => 'Monthly Invoice Start Date',
			'monthly_invoice_finish_date' => 'Monthly Invoice Finish Date',
			'invoice_from_month' => 'From Month',
			'invoice_to_month' => 'To Month',
			'is_all_child' => 'Calculate for all children',
			'is_lac' => 'Looked After Child (LAC)',
			'is_pupil_premium' => 'Pupil Premium',
			'children_of_concern' => 'Children Of Concern'
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
		$criteria->compare('child_urn', $this->child_urn, true);
		$criteria->compare('child_urn_prefix', $this->child_urn_prefix, true);
		$criteria->compare('child_urn_suffix', $this->child_urn_suffix, true);
		$criteria->compare('first_name', $this->first_name, true);
		$criteria->compare('middle_name', $this->middle_name, true);
		$criteria->compare('last_name', $this->last_name, true);
		$criteria->compare('preffered_name', $this->preffered_name, true);
		$criteria->compare('address_1', $this->address_1, true);
		$criteria->compare('address_2', $this->address_2, true);
		$criteria->compare('address_3', $this->address_3, true);
		$criteria->compare('postcode', $this->postcode, true);
		$criteria->compare('dob', $this->dob, true);
		$criteria->compare('birth_certificate', $this->birth_certificate, true);
		$criteria->compare('is_term_time', $this->is_term_time, true);
		$criteria->compare('is_funding', $this->is_funding, true);
		$criteria->compare('booking_type', $this->booking_type, true);
		$criteria->compare('enroll_date', $this->enroll_date, true);
		$criteria->compare('start_date', $this->start_date, true);
		$criteria->compare('leave_date', $this->leave_date, true);
		$criteria->compare('gender', $this->gender, true);
		$criteria->compare('client_id', $this->client_id);
		$criteria->compare('profile_photo', $this->profile_photo, true);
		$criteria->compare('profile_photo_thumb', $this->profile_photo_thumb, true);
		$criteria->compare('branch_id', Yii::app()->session['branch_id']);
		$criteria->compare('is_deleted', $this->is_deleted);
		$criteria->compare('is_active', $this->is_active);
		$criteria->compare('room_id', $this->room_id);
		$criteria->compare('key_person', $this->key_person);
		$criteria->compare('discount', $this->discount);
		$criteria->compare('funded_hours', $this->funded_hours);
		$criteria->compare('link_to_staff', $this->link_to_staff, true);
		$criteria->compare('sibling_id', $this->sibling_id);
		$criteria->compare('preffered_session', $this->preffered_session);
		$criteria->compare('latitude', $this->latitude, true);
		$criteria->compare('longitude', $this->longitude, true);
		$criteria->compare('monthly_invoice_amount', $this->monthly_invoice_amount);
		$criteria->compare('external_id', $this->external_id);
		$criteria->compare('monthly_invoice_start_date', $this->monthly_invoice_start_date, true);
		$criteria->compare('monthly_invoice_finish_date', $this->monthly_invoice_finish_date, true);
		$criteria->compare('is_lac', $this->is_lac);
		$criteria->compare('is_pupil_premium', $this->is_pupil_premium);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'pagination' => array(
				'pageSize' => 20,
			)
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return ChildPersonalDetails the static model class
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

	public function checkStartLeaveDate($attributes, $params) {
		if (!empty($this->start_date) && !empty($this->leave_date)) {
			if (strtotime($this->start_date) > strtotime($this->leave_date)) {
				$this->addError('start_date', 'Start Date must be smaller than leave Date.');
				$this->addError('leave_date', 'Leave Date must be greater than start Date.');
			}
		}
	}

	public function checkStartDobDate($attributes, $params) {
		if (!empty($this->start_date) && !empty($this->dob)) {
			if (strtotime($this->start_date) < strtotime($this->dob)) {
				$this->addError('start_date', 'Start date must be greater / equal to date of birth');
				$this->addError('dob', 'Date of birth must be smaller / equal to start date');
			}
		}
	}

	public function checkStartEnrollDate($attributes, $params) {
		if (!empty($this->start_date) && !empty($this->enroll_date)) {
			if (strtotime($this->start_date) < strtotime($this->enroll_date)) {
				$this->addError('start_date', 'Start date must be greater / equal to enrollment date.');
				$this->addError('enroll_date', 'Enrollment date must be smaller / equal to start date');
			}
		}
	}
        
        

	protected function beforeSave() {
		$this->last_updated_by = Yii::app()->user->id;
		$this->last_updated = new CDbExpression('NOW()');
		if (!empty(trim($this->postcode))) {
			$arrContextOptions = array(
				"ssl" => array(
					"verify_peer" => false,
					"verify_peer_name" => false,
				),
			);
			$geocode = file_get_contents('https://maps.google.com/maps/api/geocode/json?address='. urlencode(trim($this->postcode)) .'&sensor=false', false, stream_context_create($arrContextOptions));
			$output = json_decode($geocode);
			$this->latitude = isset($output->results[0]->geometry->location->lat) ? $output->results[0]->geometry->location->lat : NULL;
			$this->longitude = isset($output->results[0]->geometry->location->lng) ? $output->results[0]->geometry->location->lng : NULL;
		} else {
			$this->latitude = NULL;
			$this->longitude = NULL;
		}
//                $childModel = ChildPersonalDetails::model()->findByPk($this->id);
                //if($childModel->is_active == 0){
                    
//                    $branchModel = Branch::model()->currentBranch();
//                    $childPersonalDetailsModel = ChildPersonalDetailsNds::model()->findAll(array('condition' => 'is_active = 1 AND is_deleted = 0 AND branch_id = :branch_id',
//                                                            'params' => array(':branch_id' => $branchModel->id)));
//                    echo "child limit=>";
//                    var_dump((int)$branchModel->child_limit);
//                    echo "count=>";
//                    var_dump(count($childPersonalDetailsModel)+1);
//                    die("mdead");
                    //118                                       114
//                    if(count($childPersonalDetailsModel)+1 >= (int)$branchModel->child_limit ){
//                        throw new CHttpException(' Capacity has been exceeeeded.');
////                           $this->addError('is_active',  ' Capacity has been exceeeeded.');
//                    }
//                    echo "<pre>";
//                    echo "branch_id".$branchModel->id;
//                    print_r($childModel);
////                    print_r($childPersonalDetailsModel);
//                    die("Mdead");
//                }
                //die('hello');
		return parent::beforeSave();
	}

	protected function beforeValidate() {
		if (isset($this->dob) && !empty($this->dob)) {
			$this->dob = date("d-m-Y", strtotime($this->dob));
		}
		if (isset($this->start_date) && !empty($this->start_date)) {
			$this->start_date = date("d-m-Y", strtotime($this->start_date));
		}
		if (isset($this->leave_date) && !empty($this->leave_date)) {
			$this->leave_date = date("d-m-Y", strtotime($this->leave_date));
		}
		if (isset($this->enroll_date) && !empty($this->enroll_date)) {
			$this->enroll_date = date("d-m-Y", strtotime($this->enroll_date));
		}
		return parent::beforeValidate();
	}

	public function checkCapacity($attributes, $params) {
		if (Room::getChildCount($this->room_id) >= $this->room->capacity) {
			$this->addError('room_id', $this->room->name . ' Capacity has been exceeded.');
		}
	}
        
        public function checkChildCount($attributes, $params) {
		if(ChildPersonalDetailsNds::model()->countByAttributes(array('is_active'=> 1,'is_deleted'=> 0,'branch_id' =>Branch::currentBranch()->id)) >= Branch::currentBranch()->child_limit ) {
                    $this->addError('branch_id','Maximum child limit reached. Please mark children who have left as inactive or contact the eyLog/eyMan support team via email at support@eylog.co.uk.');
                }
	}

	public function getFullName() {
		return $this->first_name . " " . $this->middle_name . " " . $this->last_name;
	}

	public function getColumnNames() {
		$unset_columns = array('id', 'last_updated', 'external_id', 'last_updated_by', 'latitude', 'longitude', 'link_to_staff', 'sibling_id', 'client_id', 'monthly_invoice_start_date', 'monthly_invoice_finish_date');
		$attributes = $this->getAttributes();
		return array_diff(array_keys($attributes), $unset_columns);
	}

	public function getFilter($column_name) {
		$response = array();
		if ($column_name == "is_term_time" || $column_name == "is_funding" || $column_name == "is_deleted" || $column_name == "is_active") {
			$response[$column_name] = array("filter_condition" => array(' = ' => 'EQUAL', '!=' => "NOT EQUAL"), "filter_value" => array(0 => 0, 1 => 1));
		} else if ($column_name == "start_date" || $column_name == "leave_date" || $column_name == "dob" || $column_name == "enroll_date") {
			$response[$column_name] = array("filter_condition" => array(' = ' => 'EQUAL', '>' => "GREATER THAN", '<' => 'SMALLER THAN', '>=' => 'GREATER THAN EQUAL TO', '<=' => 'SMALLER THAN EQUAL TO', 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => array());
		} else if ($column_name == "booking_type") {
			$response[$column_name] = array("filter_condition" => array(' = ' => 'EQUAL', '!=' => "NOT EQUAL", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => array('SHIFT' => 'SHIFT', 'PERMANENT' => 'PERMANENT'));
		} else if ($column_name == "gender") {
			$response[$column_name] = array("filter_condition" => array(' = ' => 'EQUAL', '!=' => "NOT EQUAL", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => array('MALE' => 'MALE', 'FEMALE' => 'FEMALE'));
		} else if ($column_name == "room_id") {
			$response[$column_name] = array("filter_condition" => array(' = ' => 'EQUAL', '!=' => "NOT EQUAL", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => CHtml::listData(Room::model()->findAllByAttributes(array('branch_id' => Yii::app()->session['branch_id'])), 'id', 'name'));
		} else if ($column_name == "preffered_session") {
			$response[$column_name] = array("filter_condition" => array(' = ' => 'EQUAL', '!=' => "NOT EQUAL", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => CHtml::listData(SessionRates::model()->findAllByAttributes(array('branch_id' => Yii::app()->session['branch_id'])), 'id', 'name'));
		} else if ($column_name == "key_person") {
			$response[$column_name] = array("filter_condition" => array(' = ' => 'EQUAL', '!=' => "NOT EQUAL", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => CHtml::listData(StaffPersonalDetails::model()->findAllByAttributes(array('branch_id' => Yii::app()->session['branch_id'])), 'id', 'first_name'));
		} else if ($column_name == "branch_id") {
			$response[$column_name] = array("filter_condition" => array('=' => 'EQUAL TO', '!=' => "NOT EQUAL TO", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => CHtml::listData(Branch::model()->findAllByPk(Yii::app()->session['branch_id']), 'id', 'name'));
		} else {
			$response[$column_name] = array("filter_condition" => array('LIKE' => 'LIKE', 'LIKE % --%' => "LIKE %--%", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL', 'EMPTY' => 'EMPTY'), "filter_value" => array());
		}
		return $response;
	}

	public function getColumnValue($column_name, $column_value) {
		if ($column_name == "is_term_time" || $column_name == "is_funding" || $column_name == "is_active" || $column_name == "is_deleted") {
			$column_value = ($column_value == 1) ? "Yes" : "No";
		} else if ($column_name == "room_id") {
			$column_value = Room::model()->findByPk($column_value)->name;
		} else if ($column_name == "preffered_session") {
			$column_value = SessionRates::model()->findByPk($column_value)->name;
		} else if ($column_name == "key_person") {
			$column_value = StaffPersonalDetails::model()->findByPk($column_value)->first_name . " " . StaffPersonalDetails::model()->findByPk($column_value)->last_name;
		} else if ($column_name == "age") {
			$column_value = customFunctions::ageInMonths($column_value);
		} else if ($column_name == "branch_id") {
			$column_value = Branch::model()->findByPk($column_value)->name;
		} else {
			$column_value = $column_value;
		}
		return $column_value;
	}

	public function getRelatedAttributes() {
		$attributes = array();
		$attributes['Room'] = Room::model()->getRelatedAttributesNames();
		$attributes['SessionRates'] = SessionRates::model()->getRelatedAttributesNames();
		$attributes['StaffPersonalDetails'] = StaffPersonalDetails::model()->getRelatedAttributesNames();
		return $attributes;
	}

	public function getRelatedAttributesNames() {
		$attributes = array('first_name', 'middle_name', 'last_name', 'dob', 'preffered_name', 'dob', 'gender', 'address_1', 'address_2', 'address_3



        ');
		return $attributes;
	}

	public function getName() {
		if ($this->is_active == 0) {
			return $this->first_name . " " . $this->last_name . " *";
		} else {
			return $this->first_name . " " . $this->last_name;
		}
	}

	public function getChildLastNameFirst() {
		if ($this->is_active == 0 || $this->is_deleted == 1) {
			return $this->last_name . ", " . $this->first_name . " *";
		} else {
			return $this->last_name . ", " . $this->first_name;
		}
	}

	public function getDetails() {
		if (!empty($this->childParentalDetails)) {
			$name = array();
			if ($this->childParentalDetails->p1_is_bill_payer) {
				$name[] = $this->childParentalDetails->P1name;
			}
			if ($this->childParentalDetails->p2_is_bill_payer) {
				$name[] = $this->childParentalDetails->P2name;
			}
			if (!empty($name)) {
				return $this->name . " { " . implode(", ", $name) . " }";
			} else {
				return $this->name;
			}
		} else {
			return $this->name;
		}
	}

	public function childUrn() {
		if ($this->isNewRecord) {
			$companyModel = Company::currentCompany();
			if ($companyModel->urn_type == Company::BRANCH_LEVEL_URN) {
				$branchModel = Branch::currentBranch();
				$criteria = new CDbCriteria();
				$criteria->select = "max(cast(child_urn as unsigned)) AS max_child_urn";
				$criteria->condition = "branch_id = :branch_id";
				$criteria->params = array(':branch_id' => $branchModel->id);
				$max_child_urn = ChildPersonalDetails::model()->resetScope()->find($criteria)->max_child_urn;
				if (isset($branchModel->child_urn_number) && !empty($branchModel->child_urn_number)) {
					if ($branchModel->child_urn_number > $max_child_urn) {
						return (int) $branchModel->child_urn_number;
					} else {
						return (int) $max_child_urn + 1;
					}
				} else {
					return (int) $max_child_urn + 1;
				}
			} else {
				$criteria = new CDbCriteria();
				$criteria->select = "max(cast(child_urn as unsigned)) AS max_child_urn";
				$max_child_urn = ChildPersonalDetails::model()->resetScope()->find($criteria)->max_child_urn;
				if (isset($companyModel->child_urn_number) && !empty($companyModel->child_urn_number)) {
					if ($companyModel->child_urn_number > $max_child_urn) {
						return (int) $companyModel->child_urn_number;
					} else {
						return (int) $max_child_urn + 1;
					}
				} else {
					return (int) $max_child_urn + 1;
				}
			}
		} else {
			if (empty(trim($this->child_urn))) {
				$companyModel = Company::currentCompany();
				if ($companyModel->urn_type == Company::BRANCH_LEVEL_URN) {
					$branchModel = Branch::currentBranch();
					$criteria = new CDbCriteria();
					$criteria->select = "max(cast(child_urn as unsigned)) AS max_child_urn";
					$criteria->condition = "branch_id = :branch_id";
					$criteria->params = array(':branch_id' => $branchModel->id);
					$max_child_urn = ChildPersonalDetails::model()->resetScope()->find($criteria)->max_child_urn;
					if (isset($branchModel->child_urn_number) && !empty($branchModel->child_urn_number)) {
						if ($branchModel->child_urn_number > $max_child_urn) {
							return (int) $branchModel->child_urn_number;
						} else {
							return (int) $max_child_urn + 1;
						}
					} else {
						return (int) $max_child_urn + 1;
					}
				} else {
					$criteria = new CDbCriteria();
					$criteria->select = "max(cast(child_urn as unsigned)) AS max_child_urn";
					$max_child_urn = ChildPersonalDetails::model()->resetScope()->find($criteria)->max_child_urn;
					if (isset($companyModel->child_urn_number) && !empty($companyModel->child_urn_number)) {
						if ($companyModel->child_urn_number > $max_child_urn) {
							return (int) $companyModel->child_urn_number;
						} else {
							return (int) $max_child_urn + 1;
						}
					} else {
						return (int) $max_child_urn + 1;
					}
				}
			} else {
				return $this->child_urn;
			}
		}
	}

	public function validateUrn($attributes, $params) {
		if (!empty($this->child_urn) && ($this->child_urn != NULL)) {
			if ($this->isNewRecord) {
				$companyModel = Company::currentCompany();
				if ($companyModel->urn_type == Company::BRANCH_LEVEL_URN) {
					$branchModel = Branch::currentBranch();
					$criteria = new CDbCriteria();
					$criteria->select = "child_urn = :child_urn";
					$criteria->condition = "branch_id = :branch_id AND child_urn = :child_urn";
					$criteria->params = array(':branch_id' => $branchModel->id, ':child_urn' => $this->child_urn);
					if (ChildPersonalDetails::model()->resetScope()->count($criteria) > 0) {
						$this->addError('child_urn', 'Child Urn has already been taken.');
					}
				} else {
					$criteria = new CDbCriteria();
					$criteria->condition = "child_urn = :child_urn";
					$criteria->params = array(':child_urn' => $this->child_urn);
					if (ChildPersonalDetails::model()->resetScope()->count($criteria) > 0) {
						$this->addError('child_urn', 'Child Urn has already been taken.');
					}
				}
			} else {
				$companyModel = Company::currentCompany();
				if ($companyModel->urn_type == Company::BRANCH_LEVEL_URN) {
					$branchModel = Branch::currentBranch();
					$criteria = new CDbCriteria();
					$criteria->select = "child_urn = :child_urn";
					$criteria->condition = "branch_id = :branch_id AND child_urn = :child_urn AND id != :id";
					$criteria->params = array(':branch_id' => $branchModel->id, ':child_urn' => $this->child_urn, ':id' => $this->id);
					if (ChildPersonalDetails::model()->resetScope()->count($criteria) > 0) {
						$this->addError('child_urn', 'Child Urn has already been taken.');
					}
				} else {
					$criteria = new CDbCriteria();
					$criteria->condition = "child_urn = :child_urn  AND  id != :id";
					$criteria->params = array(':child_urn' => $this->child_urn, ':id' => $this->id);
					if (ChildPersonalDetails::model()->resetScope()->count($criteria) > 0) {
						$this->addError('child_urn', 'Child Urn has already been taken.');
					}
				}
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
		if ($this->profile_photo == NULL || empty($this->profile_photo)) {
			return Yii::app()->request->getBaseUrl(TRUE) . "/images/create-user.jpg";
		} else {
			return GlobalPreferences::getSslUrl() . $this->profile_photo;
		}
	}

	public function getAge() {
		if (!empty($this->dob) && strtotime($this->dob)) {
			$dob = new DateTime($this->dob);
			$now = new DateTime('00:00:00');
			$age = $now->diff($dob);
			if ($age->y == 0 && $age->m == 0 && $age->d != 0) {
				$result = sprintf('%d days', $age->d);
			} else
			if ($age->y == 0 && $age->m != 0 && $age->d != 0) {
				$result = sprintf('%d month, %d days', $age->m, $age->d);
			} else
			if ($age->y == 0 && $age->m == 0 && $age->d == 0) {
				$result = sprintf('%d days', $age->d);
			} else
			if ($age->y != 0 && $age->m == 0 && $age->d != 0) {
				$result = sprintf('%d years, %d days', $age->y, $age->d);
			} else {
				$result = sprintf('%d years, %d month, %d days', $age->y, $age->m, $age->d);
			}
			return $result;
		} else {
			return "Not Set";
		}
	}

	public function checkFundingExists() {
		$model = ChildFundingDetails::model()->with('term')->find([
			'condition' => ':date BETWEEN term.start_date AND term.finish_date AND t.child_id = :child_id',
			'params' => [
				':date' => date("Y-m-d"),
				':child_id' => $this->id
			]
		]);
		if ($model) {
			return true;
		} else {
			return false;
		}
	}

	public function validateExternalId($attributes, $params) {
		if ($this->isNewRecord) {
			if (isset($this->external_id) && !empty($this->external_id)) {
				$model = ChildPersonalDetails::model()->resetScope(true)->findByAttributes([
					'branch_id' => $this->branch_id,
					'external_id' => $this->external_id
				]);
				if (!empty($model)) {
					$this->addError('child_urn', 'Child with same external id is already present - ' . $model->id);
				}
			}
		}
	}

	public function getSiblings() {
		$siblings = [];
		if(!empty($this->parentChildMappings)){
			foreach($this->parentChildMappings as $parentChildMapping){
				$siblingMappings = ParentChildMapping::model()->findAll([
					'condition' => 'parent_id = :parent_id AND child_id != :child_id',
					'params' => [
						':parent_id' => $parentChildMapping->parent_id,
						':child_id' => $parentChildMapping->child_id
					]
				]);
				if(!empty($siblingMappings)){
					foreach($siblingMappings as $siblingMapping){
						if($siblingMapping->childNds->is_deleted == 0){
							$siblings[] = $siblingMapping->childNds->name." (".$siblingMapping->childNds->branch->name.")";
						}
					}
				}
			}
		}
		return $siblings;
	}

	public function afterSave() {
		if (!$this->isNewRecord && isset($this->leave_date) && !empty($this->leave_date)) {
			$childBookingsModel = ChildBookings::model()->findAll(array(
				'condition' => 'finish_date >= :leave_date and child_id = :child_id',
				'params' => array(':leave_date' => $this->leave_date, 'child_id' => $this->id)
			));
			foreach ($childBookingsModel as $bookings) {
				if (strtotime($bookings->start_date) > strtotime($this->leave_date)) {
					ChildBookings::model()->updateByPk($bookings->id, array('is_deleted' => 1));
				} else {
					ChildBookings::model()->updateByPk($bookings->id, array('finish_date' => $this->leave_date));
				}
			}
		}
		parent::afterSave();
	}

	public function uploadProfilePhoto() {
		$rackspace = new eyManRackspace();
		$rackspace->uploadObjects([[
			'name' => "/images/children/" . $this->file_name,
			'body' => $this->profile_photo_raw->image()
			], [
				'name' => "/images/children/thumbs/" . $this->file_name,
				'body' => $this->profile_photo_thumb_raw->image()
			]
		]);
		$this->profile_photo = "/images/children/" . $this->file_name;
		$this->profile_photo_thumb = "/images/children/thumbs/" . $this->file_name;
	}

	public function afterFind() {
		$this->previous_image = $this->profile_photo;
		return parent::afterFind();
	}

	public function getOrderedParents() {
		$model = [];
		$parents = Parents::model()->with(array(
				'parentChildMapping' => array(
					'condition' => 'parentChildMapping.child_id=:child_id',
					'params' => array(':child_id' => $this->id),
					'order' => 'parentChildMapping.order'
				)
			))->findAll(array(
			'select' => '*, parentChildMapping.order AS parent_order',
		));
		if (!empty($parents)) {
			foreach ($parents as $parent) {
				$parent->is_bill_payer = $parent->parentChildMapping->is_bill_payer;
				$parent->is_authorised = $parent->parentChildMapping->is_authorised;
				$parent->is_emergency_contact = $parent->parentChildMapping->is_emergency_contact;
				$model[$parent->parent_order] = $parent;
			}
		}
		return $model;
	}

	/*
	 * Function for finding the unique booking days of child for childPersonalDetails Index Page
	 */

	public function childBookingDaysForList() {
		$bookings = ChildBookings::model()->getBookings(date("Y-m-d", strtotime("monday this week")), date("Y-m-d", strtotime("sunday this week")), $this->branch_id, $this->id);
		$branchOperationDays = customFunctions::getDatesOfDays(date("Y-m-d", strtotime("monday this week")), date("Y-m-d", strtotime("sunday this week")), explode(",", $this->branch->nursery_operation_days));
		$bookingDays = [];
		if (!empty($bookings)) {
			foreach ($bookings as $booking) {
				$days = customFunctions::getDatesOfDays($booking->start_date, $booking->finish_date, explode(",", $booking->childBookingsDetails->booking_days));
				if (!empty($days)) {
					foreach ($days as $day) {
						$bookingDays[] = $day;
					}
				}
			}
		}
		$bookingDays = array_unique(array_intersect($bookingDays, $branchOperationDays));
		if (!empty($bookingDays)) {
			foreach ($bookingDays as $key => $value) {
				$value = date("w", strtotime($value));
				switch ($value) {
					case "1":
						echo "<li><span class='green'>M</span></li>";
						break;
					case "2":
						echo "<li><span class='blue'>Tu</span></li>";
						break;
					case "3":
						echo "<li><span class='yellow'>W</span></li>";
						break;
					case "4":
						echo "<li><span class='red'>Th</span></li>";
						break;
					case "5":
						echo "<li><span class='violet'>F</span></li>";
						break;
					case "6":
						echo "<li><span class='purple'>Sa</span></li>";
						break;
					case "0":
						echo "<li><span class='orange'>Su</span></li>";
						break;
				}
			}
		}
	}

	public function getBillPayers() {
		$model = [];
		$parents = Parents::model()->with(array(
				'parentChildMapping' => array(
					'condition' => 'parentChildMapping.child_id=:child_id AND parentChildMapping.is_bill_payer=1',
					'params' => array(':child_id' => $this->id),
					'order' => 'parentChildMapping.order'
				)
			))->findAll(array(
			'select' => '*, parentChildMapping.order AS parent_order',
		));
		if (!empty($parents)) {
			foreach ($parents as $parent) {
				$parent->is_bill_payer = $parent->parentChildMapping->is_bill_payer;
				$parent->is_authorised = $parent->parentChildMapping->is_authorised;
				$parent->is_emergency_contact = $parent->parentChildMapping->is_emergency_contact;
				$model[$parent->parent_order] = $parent;
			}
		}
		return $model;
	}

	public function getBillPayersWithMandates() {
		$model = [];
		$parents = Parents::model()->with(array(
				'parentChildMapping' => array(
					'condition' => 'parentChildMapping.child_id=:child_id AND parentChildMapping.is_bill_payer=1 AND t.gocardless_customer is NOT NULL',
					'params' => array(':child_id' => $this->id),
					'order' => 'parentChildMapping.order'
				)
			))->findAll(array(
			'select' => '*, parentChildMapping.order AS parent_order',
		));
		if (!empty($parents)) {
			foreach ($parents as $parent) {
				$parent->is_bill_payer = $parent->parentChildMapping->is_bill_payer;
				$parent->is_authorised = $parent->parentChildMapping->is_authorised;
				$parent->is_emergency_contact = $parent->parentChildMapping->is_emergency_contact;
				$model[$parent->parent_order] = $parent;
			}
		}
		return $model;
	}

	public function getFirstBillPayer() {
		$parent = Parents::model()->with(array(
				'parentChildMapping' => array(
					'condition' => 'parentChildMapping.child_id=:child_id AND parentChildMapping.is_bill_payer=1',
					'params' => array(':child_id' => $this->id),
					'order' => 'parentChildMapping.order'
				)
			))->find(array(
			'select' => '*, parentChildMapping.order AS parent_order',
		));
		if (!empty($parent)) {
			$parent->is_bill_payer = $parent->parentChildMapping->is_bill_payer;
			$parent->is_authorised = $parent->parentChildMapping->is_authorised;
			$parent->is_emergency_contact = $parent->parentChildMapping->is_emergency_contact;
		}
		return $parent;
	}

	public function getParentsForEyLogIntegration() {
		$model = [];
		$parents = Parents::model()->with(array(
				'parentChildMapping' => array(
					'condition' => 'parentChildMapping.child_id=:child_id AND parentChildMapping.order < 3',
					'params' => array(':child_id' => $this->id),
					'order' => 'parentChildMapping.order'
				)
			))->findAll(array(
			'select' => '*, parentChildMapping.order AS parent_order',
		));
		if (!empty($parents)) {
			foreach ($parents as $parent) {
				if (!empty($parent->first_name) && !empty($parent->last_name) && !empty($parent->email)) {
					$temp = [];
					$temp['first_name'] = $parent->first_name;
					$temp['last_name'] = $parent->last_name;
					$temp['email'] = $parent->email;
					$temp['mobile_number'] = isset($parent->mobile_phone) ? $parent->mobile_phone : "";
					$temp['relationship'] = isset($parent->relationship) ? $parent->relationship : "";
					$temp['external_id'] = "eyman-" . $parent->id;
					$model[] = $temp;
				}
			}
		}
		return $model;
	}

	public function childTagsForList() {
		$childDetails = $this->with(['tags:deleted'])->findByPk($this->id);
		$active_tags = $childDetails->tags;
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
