<?php

//Demo Commit
/**
 * This is the model class for table "tbl_enquiries".
 *
 * The followings are the available columns in table 'tbl_enquiries':
 * @property integer $id
 * @property integer $branch_id
 * @property string $enquiry_date_time
 * @property string $parent_first_name
 * @property string $parent_last_name
 * @property string $parent_address_1
 * @property string $parent_address_2
 * @property string $postcode
 * @property string $phone_home
 * @property string $phone_mobile
 * @property string $parent_email
 * @property string $child_first_name
 * @property string $child_last_name
 * @property string $child_dob
 * @property string $child_start_date
 * @property integer $is_enroll_child
 * @property integer $source_id
 * @property integer $contact_id
 * @property integer $reason_id
 * @property string $visit_date_time
 * @property integer $staff_taking_enuiry_id
 * @property integer $staff_taking_visit_id
 * @property string $enuiry_follow_up
 * @property string $enuiry_additional_notes
 * @property string $enuiry_marketing_notes
 * @property integer $is_visit_attended
 * @property integer $is_brochure_given
 * @property integer $unsubscribe_mailing_list
 * @property integer $is_deleted
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $waitlisted_room
 * @property integer $is_waitlisted
 * @property string $preferred_session
 * @property integer $status
 * @property string $visit_time
 * @property string $enuiry_follow_up_time
 * @property string $is_submitted
 * 
 * The followings are the available model relations:
 * @property Branch $branch
 * @property Branch $source
 * @property PickEnquiriesContactMethod $contact
 * @property PickEnquiriesReasonCare $reason
 * @property integer $is_followed_up
 * @property string $preferred_time
 * @property integer $lost_reason
 * @property string $child_detail
 * @property string $enquiry_url
 * @property string $latitude
 * @property string $longitude
 * @property string $view_url
 */
class Enquiries extends CActiveRecord {

	public $branch_name;
	public $date_columns = array('child_dob', 'child_start_date', 'visit_date_time', 'enuiry_follow_up');
	public $allEnquiry;
	public $have_parent_detail;
	public $child_id;
	public $parent_id;
	public $agree_terms;
	public $address;

	CONST NOT_ENROLED = 0;
	CONST ENROLED = 1;
	CONST WAITLISTED = 2;
	CONST LOST = 3;

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
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'tbl_enquiries';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('parent_first_name, child_first_name, child_last_name', 'required', 'except' => 'registerYourChild'),
			array('child_first_name, child_last_name', 'required', 'on' => 'registerYourChild'),
//            array('agree_terms', 'compare', 'compareValue' => true,  'message' => 'Please agree the terms to continue.', 'on' => 'registerYourChild'),
//            array('agree_terms', 'required', 'on' => 'registerYourChild'),
			array('waitlisted_room, child_first_name, child_last_name', 'required', 'on' => 'waitlisted'),
			array('enuiry_additional_notes', 'required', 'on' => 'lost'),
			array('parent_email', 'email', 'message' => 'Please enter a valid email address'),
			array('phone_mobile', 'numerical', 'message' => 'Please enter a valid phone number'),
//            array('parent_email', 'unique', 'message' => 'Parent Email already exists!'),
			array('branch_id, source_id, contact_id, reason_id, staff_taking_enuiry_id, staff_taking_visit_id, created_by, updated_by, waitlisted_room, is_waitlisted, is_followed_up,is_submitted', 'numerical', 'integerOnly' => true),
			array('parent_first_name, parent_last_name, postcode, phone_home, phone_mobile, parent_email, child_first_name, child_last_name,latitude, longitude', 'length', 'max' => 45),
			array('child_dob, child_start_date, visit_date_time, enuiry_follow_up', 'date', 'format' => 'dd-mm-yyyy', 'message' => 'Please input a valid date type.', 'allowEmpty' => TRUE),
			array('child_dob, child_start_date, visit_date_time, enuiry_follow_up', 'default', 'setOnEmpty' => true, 'value' => NULL),
			array('parent_address_1, parent_address_2', 'length', 'max' => 255),
			array('is_enroll_child, is_visit_attended, is_brochure_given, unsubscribe_mailing_list', 'length', 'max' => 1),
			array('child_dob, enquiry_date_time, enuiry_additional_notes, enuiry_marketing_notes, updated, created, preferred_session, status, visit_time, enuiry_follow_up_time , preferred_time,have_parent_detail,lost_reason , child_detail , enquiry_url,view_url,is_submitted', 'safe'),
			array('parent_email', 'validateParentContactFields', 'filter', 'filter' => 'trim', 'except' => 'registerYourChild'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, branch_id, enquiry_date_time, parent_first_name,parent_last_name,  parent_address_1, parent_address_2, postcode, phone_home, phone_mobile, parent_email, child_first_name, child_last_name, child_dob, child_start_date, is_enroll_child, source_id, contact_id, reason_id, visit_date_time, staff_taking_enuiry_id, staff_taking_visit_id, enuiry_follow_up, enuiry_additional_notes, enuiry_marketing_notes, is_visit_attended, is_brochure_given, unsubscribe_mailing_list, branch_name, updated, created, created_by, updated_by, waitlisted_room, is_waitlisted ,preferred_session , status, visit_time, enuiry_follow_up_time, is_followed_up , preferred_time,lost_reason , child_detail,latitude, longitude, enquiry_url,view_url,is_submitted', 'safe', 'on' => 'search'),
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
			'room' => array(self::BELONGS_TO, 'Room', 'waitlisted_room'),
			'source' => array(self::BELONGS_TO, 'Branch', 'source_id'),
			'contact' => array(self::BELONGS_TO, 'PickEnquiriesContactMethod', 'contact_id'),
			'reason' => array(self::BELONGS_TO, 'PickEnquiriesReasonCare', 'reason_id'),
		);
	}

	public function behaviors() {
		return array(
			'dateFormatter' => array(
				'class' => 'application.components.DateFormatter',
				'date_columns' => array('child_dob', 'child_start_date', 'visit_date_time', 'enuiry_follow_up')
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
			'enquiry_date_time' => 'Date/Time Enquiry Made',
			'parent_first_name' => 'Parent First Name',
			'parent_last_name' => 'Parent Last Name',
			'parent_address_1' => 'Address 1',
			'parent_address_2' => 'Address 2',
			'postcode' => 'Postcode',
			'phone_home' => 'Home Phone',
			'phone_mobile' => 'Parent Phone',
			'parent_email' => 'Parent Email',
			'child_first_name' => 'Child First Name',
			'child_last_name' => 'Child Last Name',
			'child_dob' => 'Child Date of Birth',
			'child_start_date' => 'Start Date',
			'is_enroll_child' => 'Enroll Child',
			'source_id' => 'Source',
			'contact_id' => 'Contact',
			'reason_id' => 'Reason',
			'visit_date_time' => 'Visit Date',
			'staff_taking_enuiry_id' => 'Staff Taking Enquiry',
			'staff_taking_visit_id' => 'Staff Taking Visit',
			'enuiry_follow_up' => 'Follow Up Date',
			'enuiry_additional_notes' => 'Enquiry Additional Notes',
			'enuiry_marketing_notes' => 'Enquiry Marketing Notes',
			'is_visit_attended' => 'Visit Attended',
			'is_brochure_given' => 'Brochure Given',
			'unsubscribe_mailing_list' => 'Unsubscribe Mailing List',
			'created' => 'Created',
			'updated' => 'Updated',
			'created_by' => 'Created By',
			'updated_by' => 'Updated By',
			'waitlisted_room' => 'Waitlisted Room',
			'is_waitlisted' => 'Waitlisted',
			'preferred_session' => 'Preferred Session',
			'status' => 'Status',
			'visit_time' => 'Visit Time',
			'enuiry_follow_up_time' => 'Follow Up Time',
			'is_followed_up' => 'Followed Up',
			'have_parent_detail' => "A parent with the same email address already exists. Press Save to mark this child as a sibling or Cancel to change the parent's email address.",
			'lost_reason' => 'Lost Reason',
			'enquiry_url' => 'Enquiry Url',
			'latitude' => 'Latitude',
			'longitude' => 'Longitude',
                        'view_url'  => 'View Url'
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
		$criteria->compare('t.enquiry_date_time', $this->enquiry_date_time, true);
		$criteria->compare('parent_first_name', $this->parent_first_name, true);
		$criteria->compare('parent_last_name', $this->parent_last_name, true);
		$criteria->compare('t.parent_address_1', $this->parent_address_1, true);
		$criteria->compare('t.parent_address_2', $this->parent_address_2, true);
		$criteria->compare('t.postcode', $this->postcode, true);
		$criteria->compare('t.phone_home', $this->phone_home, true);
		$criteria->compare('t.phone_mobile', $this->phone_mobile, true);
		$criteria->compare('t.parent_email', $this->parent_email, true);
		$criteria->compare('t.child_first_name', $this->child_first_name, true);
		$criteria->compare('t.child_last_name', $this->child_last_name, true);
		$criteria->compare('t.child_dob', $this->child_dob, true);
		$criteria->compare('t.child_start_date', $this->child_start_date, true);
		$criteria->compare('t.is_enroll_child', $this->is_enroll_child, true);
		$criteria->compare('t.source_id', $this->source_id);
		$criteria->compare('t.contact_id', $this->contact_id);
		$criteria->compare('t.reason_id', $this->reason_id);
		$criteria->compare('t.visit_date_time', $this->visit_date_time, true);
		$criteria->compare('t.staff_taking_enuiry_id', $this->staff_taking_enuiry_id);
		$criteria->compare('t.staff_taking_visit_id', $this->staff_taking_visit_id);
		$criteria->compare('t.enuiry_follow_up', $this->enuiry_follow_up, true);
		$criteria->compare('t.enuiry_additional_notes', $this->enuiry_additional_notes, true);
		$criteria->compare('t.enuiry_marketing_notes', $this->enuiry_marketing_notes, true);
		$criteria->compare('t.is_visit_attended', $this->is_visit_attended, true);
		$criteria->compare('t.is_brochure_given', $this->is_brochure_given, true);
		$criteria->compare('t.unsubscribe_mailing_list', $this->unsubscribe_mailing_list, true);
		$criteria->compare('t.branch_id', Yii::app()->session['branch_id']);
		$criteria->compare('is_deleted', $this->is_deleted);
		$criteria->compare('created', $this->created, true);
		$criteria->compare('updated', $this->updated, true);
		$criteria->compare('created_by', $this->created_by);
		$criteria->compare('updated_by', $this->updated_by);
		$criteria->compare('waitlisted_room', $this->waitlisted_room);
		$criteria->compare('is_waitlisted', $this->is_waitlisted);
		$criteria->compare('status', $this->status);
		$criteria->compare('visit_time', $this->visit_time, true);
		$criteria->compare('enuiry_follow_up_time', $this->enuiry_follow_up_time, true);
		$criteria->compare('is_followed_up', $this->is_followed_up);
		$criteria->compare('latitude', $this->latitude, true);
		$criteria->compare('longitude', $this->longitude, true);
                $criteria->compare('is_submitted', $this->is_submitted, true);
		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'sort' => array(
				'defaultOrder' => 't.id ASC',
				'attributes' => array(
					'branch_name' => array(
						'asc' => 'branch.name ASC',
						'desc' => 'branch.name DESC',
					),
					'*',
				),
			),
			'pagination' => array(
				'pageSize' => 15
			),
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Enquiries the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public function validateParentContactFields($attributes, $params) {
		if (!($this->parent_email) && !($this->phone_mobile)) {
			$this->addError('parent_email', 'Parent Email / Parent Mobile is mandatory.');
			$this->addError('phone_mobile', 'Parent Email / Parent Mobile is mandatory.');
		}
	}

	public function beforeSave() {
		if (get_class(Yii::app()) === "CWebApplication") {
			if ($this->isNewRecord) {
				$this->status = 0;
				$this->created_by = Yii::app()->user->id;
				$this->created = new CDbExpression("NOW()");
				$this->enquiry_date_time = new CDbExpression("NOW()");
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

		if (!empty($this->preferred_session)) {
			$this->preferred_session = implode(",", $this->preferred_session);
		}

		if (!empty($this->preferred_time)) {
			$this->preferred_time = json_encode($this->preferred_time, true);
		}
		$this->getLatLong();
		return parent::beforeSave();
	}

	public function afterFind() {
		if (!empty($this->preferred_session)) {
			$this->preferred_session = explode(",", $this->preferred_session);
		}
		if (!empty($this->preferred_time)) {
			$this->preferred_time = json_decode($this->preferred_time, true);
		}
		return parent::afterFind();
	}

	public static function getStatus($status = NULL) {
		$status_array = array(
			0 => 'Not Enrolled',
			1 => 'Enrolled',
			2 => 'Waitlisted',
			3 => 'Lost'
		);
		if ($status == NULL)
			return $status_array;
		return $status_array[$status];
	}

	public static function getEnquiryPreferredSessionAndTime($preferred_session, $preferred_time) {
		$preferredSessionAndTime = "";
		$daylist = array(0 => 'Su', 1 => 'M', 2 => 'Tu', 3 => 'W', 4 => 'Th', 5 => 'F', 6 => 'Sa');
		$sessionrates = SessionRates::model()->findAll([
			'select' => 'id , name',
			'condition' => 'id IN (' . implode(",", $preferred_session) . ') AND branch_id = :branch_id ',
			'params' => [':branch_id' => Branch::currentBranch()->id],
			'order' => 'name'
		]);
		if (!empty($sessionrates)) {
			foreach ($sessionrates as $srkey => $srvalue) {
				$preferredSessionAndTime = $preferredSessionAndTime . "" . $srvalue['name'];
				if (isset($preferred_time[$srvalue['id']]) && !empty($preferred_time[$srvalue['id']])) {
					$preferredSessionAndTime = $preferredSessionAndTime . " : ";
					$count = 1;
					foreach ($preferred_time[$srvalue['id']] as $ptkey => $ptvalue) {
						$preferredSessionAndTime = $preferredSessionAndTime . "" . $daylist[$ptvalue];
						if ($count !== count($preferred_time[$srvalue['id']]))
							$preferredSessionAndTime = $preferredSessionAndTime . " , ";
						$count++;
					}
				}
				$preferredSessionAndTime = $preferredSessionAndTime . "<br>";
			}
		}
		return $preferredSessionAndTime;
	}

	public function getLatLong() {
		if (isset($this->postcode) && !empty($this->postcode)) {
			$arrContextOptions = array(
				"ssl" => array(
					"verify_peer" => false,
					"verify_peer_name" => false,
				),
			);
			$geocode = file_get_contents('https://maps.google.com/maps/api/geocode/json?address=' . urlencode(trim($this->postcode)) . '&sensor=false', false, stream_context_create($arrContextOptions));
			$output = json_decode($geocode);
			$latitude = isset($output->results[0]->geometry->location->lat) ? $output->results[0]->geometry->location->lat : NULL;
			$longitude = isset($output->results[0]->geometry->location->lng) ? $output->results[0]->geometry->location->lng : NULL;
			$this->latitude = $latitude;
			$this->longitude = $longitude;
		} else {
			$this->latitude = NULL;
			$this->longitude = NULL;
		}
	}

}
