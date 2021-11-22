<?php

//
/**
 * This is the model class for table "tbl_parent".
 *
 * The followings are the available columns in table 'tbl_parent':
 * @property integer $id
 * @property integer $title
 * @property string $first_name
 * @property string $last_name
 * @property string $address_1
 * @property string $address_2
 * @property string $address_3
 * @property string $postcode
 * @property string $home_phone
 * @property string $mobile_phone
 * @property string $email
 * @property string $relationship
 * @property string $employer
 * @property string $department
 * @property string $work_phone
 * @property string $occupation
 * @property string $dob
 * @property string $disability
 * @property integer $is_authorised
 * @property integer $is_bill_payer
 * @property integer $is_emergency_contact
 * @property string $profile_photo
 * @property string $gocardless_customer_id
 * @property string $gocardless_mandate
 * @property string $gocardless_session_token
 * @property string $gocardless_customer
 * @property integer $is_deleted
 * @property string $created
 * @property integer $created_by
 * @property string $updated
 * @property integer $updated_by
 *
 * The followings are the available model relations:
 * @property ParentChildMapping[] $parentChildMappings
 */
class Parents extends CActiveRecord {

	public $parent_order;
	public $date_columns = array('dob');
	public $branch_id = null;
	public $is_authorised = 0;
	public $is_bill_payer = 0;
	public $is_emergency_contact = 0;
	public $child_search;

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'tbl_parent';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('first_name', 'required' , 'except' => 'registerYourChild' ),
                        array('first_name ,email', 'required' , 'on' => 'registerYourChild'),
			array('email', 'email'),
			array('email', 'filter', 'filter' => 'trim'),
			array('email', 'checkunique'),
			array('title, is_authorised, is_bill_payer, is_emergency_contact, is_deleted, created_by, updated_by', 'numerical', 'integerOnly' => true),
			array('first_name, last_name, address_1, address_2, address_3, email, employer, department, profile_photo, gocardless_customer_id, gocardless_mandate, gocardless_session_token, gocardless_customer', 'length', 'max' => 255),
			array('postcode, home_phone, mobile_phone, relationship, work_phone, occupation, disability', 'length', 'max' => 45),
			array('dob, created, updated, parent_order, branch_id', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, title, first_name, last_name, address_1, address_2, address_3, postcode, home_phone, mobile_phone, email, relationship, employer, department, work_phone, occupation, dob, disability, is_authorised, is_bill_payer, is_emergency_contact, profile_photo, gocardless_customer_id, gocardless_mandate, gocardless_session_token, gocardless_customer, is_deleted, created, created_by, updated, updated_by, child_search', 'safe', 'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		return array(
			'parentChildMappings' => array(self::HAS_MANY, 'ParentChildMapping', 'parent_id'),
			'parentChildMapping' => array(self::HAS_ONE, 'ParentChildMapping', 'parent_id'),
			'children' => array(self::MANY_MANY, 'ChildPersonalDetails', 'tbl_parent_child_mapping(parent_id, child_id)'),
			'childrenNds' => array(self::MANY_MANY, 'ChildPersonalDetailsNds', 'tbl_parent_child_mapping(parent_id, child_id)'),
			'titles' => array(self::BELONGS_TO, 'PickTitles', 'title')
		);
	}

	public function behaviors() {
		return array(
			'dateFormatter' => array(
				'class' => 'application.components.DateFormatter',
				'date_columns' => array('dob')
			)
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'title' => 'Title',
			'first_name' => 'First Name',
			'last_name' => 'Last Name',
			'address_1' => 'Address 1',
			'address_2' => 'Address 2',
			'address_3' => 'Address 3',
			'postcode' => 'Postcode',
			'home_phone' => 'Home Phone',
			'mobile_phone' => 'Mobile Phone',
			'email' => 'Email',
			'relationship' => 'Relationship',
			'employer' => 'Employer',
			'department' => 'Department',
			'work_phone' => 'Work Phone',
			'occupation' => 'Occupation',
			'dob' => 'Date Of Birth',
			'disability' => 'Disability',
			'profile_photo' => 'Profile Photo',
			'gocardless_customer_id' => 'Gocardless Customer',
			'gocardless_mandate' => 'Gocardless Mandate',
			'gocardless_session_token' => 'Gocardless Session Token',
			'gocardless_customer' => 'Gocardless Customer',
			'is_deleted' => 'Is Deleted',
			'created' => 'Created',
			'created_by' => 'Created By',
			'updated' => 'Updated',
			'updated_by' => 'Updated By',
			'is_authorised' => 'Authorised to collect',
			'is_bill_payer' => 'Bill Payer',
			'is_emergency_contact' => 'Emergency Contact'
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
		$criteria->with = array('childrenNds', 'parentChildMappings');
		$criteria->compare('t.id', $this->id);
		$criteria->compare('childrenNds.first_name', $this->child_search, true);
		$criteria->compare('t.title', $this->title);
		$criteria->compare('t.first_name', $this->first_name, true);
		$criteria->compare('t.last_name', $this->last_name, true);
		$criteria->compare('t.address_1', $this->address_1, true);
		$criteria->compare('t.address_2', $this->address_2, true);
		$criteria->compare('t.address_3', $this->address_3, true);
		$criteria->compare('t.postcode', $this->postcode, true);
		$criteria->compare('t.home_phone', $this->home_phone, true);
		$criteria->compare('t.mobile_phone', $this->mobile_phone, true);
		$criteria->compare('t.email', $this->email, true);
		$criteria->compare('t.relationship', $this->relationship, true);
		$criteria->compare('t.employer', $this->employer, true);
		$criteria->compare('t.department', $this->department, true);
		$criteria->compare('t.work_phone', $this->work_phone, true);
		$criteria->compare('t.occupation', $this->occupation, true);
		$criteria->compare('t.dob', $this->dob, true);
		$criteria->compare('t.disability', $this->disability, true);
		$criteria->compare('t.profile_photo', $this->profile_photo, true);
		$criteria->compare('t.gocardless_customer_id', $this->gocardless_customer_id, true);
		$criteria->compare('t.gocardless_mandate', $this->gocardless_mandate, true);
		$criteria->compare('t.gocardless_session_token', $this->gocardless_session_token, true);
		$criteria->compare('t.gocardless_customer', $this->gocardless_customer, true);
		$criteria->compare('t.is_deleted', 0);
		$criteria->compare('t.created', $this->created, true);
		$criteria->compare('t.created_by', $this->created_by);
		$criteria->compare('t.updated', $this->updated, true);
		$criteria->compare('t.updated_by', $this->updated_by);
		$criteria->together = true;
		$criteria->compare('childrenNds.branch_id', Branch::currentBranch()->id);
		$criteria->compare('childrenNds.is_deleted', 0);
		$criteria->compare('parentChildMappings.is_bill_payer', 1);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'pagination' => array(
				'pageSize' => 15
			),
			'sort' => array(
				'attributes' => array(
					'child_search' => array(
						'asc' => 'childrenNds.first_name',
						'desc' => 'childrenNds.first_name DESC'
					),
					'*'
				),
				'defaultOrder' => 't.first_name, t.last_name, childrenNds.first_name, childrenNds.last_name'
			)
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Parents the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public function hasGcSession() {
		return ($this->gocardless_session_token != NULL);
	}

	public function hasMandate() {
		return ($this->gocardless_mandate != NULL);
	}

	public function associateGcMandate($customerId, $mandateId) {
		$this->gocardless_customer = $customerId;
		$this->gocardless_mandate = $mandateId;
		return $this->save(false);
	}

	public function sendDirectDebitRequest($message = '') {
		$gcCustomerClient = customFunctions::getCustomerGoCardlessClient();
		if (!$gcCustomerClient) {
			throw new CHttpException(500, 'Direct Debit Client account does not exist.');
		}
		try {
			$companyModel = Company::currentCompany();
			$companyName = $companyModel->name;
			$companyLogo = customFunctions::getCompanyLogoForDashboard($companyModel->id);
			$sessionToken = time() . "-" . rand(1, 1000);
			$redirectFlow = $gcCustomerClient->redirectFlows()->create(array(
				"params" => array(
					"description" => $companyName,
					"session_token" => $sessionToken,
					"success_redirect_url" => Yii::app()->controller->createAbsoluteUrl('/site/goCardlessSuccess', array('id' => $this->id)),
					"prefilled_customer" => array(
						"given_name" => $this->first_name,
						"family_name" => $this->last_name,
						"email" => $this->email,
						"address_line1" => $this->address_1,
						"postal_code" => $this->postcode
					)
				)
			));
			if ($redirectFlow->id != null) {
				$this->gocardless_customer_id = $redirectFlow->id;
				$this->gocardless_session_token = $sessionToken;
				if ($this->save()) {
					$url = $redirectFlow->redirect_url;
					$name = $this->name;
					$to = array(array(
							'email' => 'nishant@metadesignsolutions.com', //$this->{$prefix."email"},
							'name' => $this->name,
							'type' => 'bcc'
						), array(
							'email' => 'rachit.chawla@eylog.co.uk', //$this->{$prefix."email"},
							'name' => $this->name,
							'type' => 'to'
						), array(
							'email' => 'lokesh@mds.asia', //$this->{$prefix."email"},
							'name' => $this->name,
							'type' => 'bcc'
					));
					if ($message != '') {
						$message = '<tr> <td> ' . $message . ' </td></tr>';
					}
					$template = '<table cellpadding="0" cellspacing="0" border="0" style="width: 470px; margin:10px auto; text-align: justify;font-family: \'Arial\', sans-serif;"> <thead> <tr> <td> <table border="0" cellpadding="0" cellspacing="0" width="100%" align="center"> <tr> <td align="center"> <img src=' . $companyLogo . '> </td></tr></table> <table border="0" cellpadding="0" cellspacing="0" width="100%" style="font-size: 20px;line-height: 27px; margin-top: 30px;"> <tr> <td> ' . $companyName . ' would like to set up a Direct Debit mandate to collect your fees</td></tr></table> <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 15px"> ' . $message . '</table> <table border="0" cellpadding="0" cellspacing="0" width="100%" style="line-height: 23px; margin-top: 15px;"> <tr> <td> You will be notified before ' . $companyName . ' collects any future <br>payments from you. </td></tr></table> <a href="' . $url . '" style="color: #fff; text-decoration: none;"> <table border="0" cellpadding="10" cellspacing="0" width="100%" style="line-height: 23px; margin-top: 15px"> <tr style="background: #660; color: #fff;"> <td align="center"> Authorise </td></tr></table> </a> <table border="0" cellpadding="0" cellspacing="0" width="100%" style="line-height: 23px; margin-top: 15px; font-weight: bold;"> <tr> <td> If there is an issue with this payment please contact ' . $companyName . '.</a> </td></tr></table>';
					$mandrill = new EymanMandril('Direct Debit Request', $template, $companyName, $to, 'no-reply@eylog.co.uk');
					$response = $mandrill->sendEmail();
				} else {
					throw new Exception('Direct Debit could not be connected.');
				}
			} else {
				throw new Exception('Direct Debit could not be connected.');
			}
		} catch (Exception $e) {
			throw new CHttpException(500, 'Direct Debit could not be connected.');
		}
	}

	public function beforeSave() {
		if (empty($this->dob) || !isset($this->dob)) {
			$this->dob = NULL;
		}
		if (get_class(Yii::app()) === "CWebApplication") {
			if ($this->isNewRecord) {
				$this->created_by = Yii::app()->user->id;
				$this->created = new CDbExpression("NOW()");
			} else {
				$this->updated_by = Yii::app()->user->id;
				$this->updated = new CDbExpression("NOW()");
			}
		}
		return parent::beforeSave();
	}

	public function getName() {
		return $this->first_name . " " . $this->last_name;
	}

	public function getFullname() {
		return $this->titles->name . " " . $this->first_name . " " . $this->last_name;
	}

	public function checkunique($attribute, $params) {
		if (isset($this->email) && !empty(trim($this->email))) {
			if ($this->isNewRecord) {
				$parent = Parents::model()->findAll('email=:email', array(':email' => $this->email));
				if (!empty($parent)) {
					$this->addError($attribute, 'Email Id is already used by another parent.');
				}
			} else {
				$parent = Parents::model()->findAll(['condition' => 'email = :email AND id != :id', 'params' => [':email' => $this->email, ':id' => $this->id]]);
				if (!empty($parent)) {
					$this->addError($attribute, 'Email Id is already used by another parent.');
				}
			}
		}
	}

	public function removeMandate() {
		$this->gocardless_customer_id = null;
		$this->gocardless_customer = null;
		$this->gocardless_mandate = null;
		$this->gocardless_session_token = null;
		$this->save(false);
	}

}
