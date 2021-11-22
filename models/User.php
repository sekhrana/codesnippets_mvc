<?php

//Demo Commit
/**
 * This is the model class for table "tbl_user".
 *
 * The followings are the available columns in table 'tbl_user':
 * @property integer $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $password
 * @property string $last_login
 * @property string $last_modified
 * @property integer $is_active
 * @property string $profile_photo
 * @property string $forgot_token
 * @property string $forgot_token_expire
 * @property integer $is_forgot_token_valid
 * @property string $activate_token
 * @property string $activate_token_expire
 * @property integer $is_activate_token_valid
 * @property integer $is_login_allowed
 * @property integer $is_deleted
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $external_id
 * @property integer $is_manager_as_staff
 *
 * The followings are the available model relations:
 * @property ChildBookings[] $childBookings
 * @property UserBranchMapping[] $userBranchMappings
 * @property GocardlessAccounts[] $gocardlessAccounts
 */
class User extends CActiveRecord {

	//public $branch_id;
	//public $role;
	public $prevProfilePhoto;
	public $new_password;
	public $repeat_password;
	public $old_password;

	const MANAGER_IMPORT_API_PATH = "/api/children/getManagers";
	const BRANCH_MANAGER = "branchManager";
	const AREA_MANAGER = "areaManager";
	const HR_ADMIN = "hrAdmin";
	const COMPANY_ADMINISTRATOR = "companyAdministrator";
	const ACCOUNTS_ADMIN = "accountsAdmin";
	const HR_STANDARD = "hrStandard";
	const BRANCH_ADMIN = "branchAdmin";

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'tbl_user';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('first_name,last_name, email', 'required'),
			array('email', 'email', 'message' => 'Please enter a valid email ID'),
			array('email', 'checkunique'),
			array('is_active, is_forgot_token_valid, is_activate_token_valid, is_login_allowed, created_by, updated_by, is_manager_as_staff', 'numerical', 'integerOnly' => true),
			array('first_name, last_name, email, password, profile_photo, forgot_token, activate_token', 'length', 'max' => 255),
			array('last_login, last_modified, updated, created', 'safe'),
			array('profile_photo', 'file', 'allowEmpty' => true, 'types' => 'jpg,jpeg,gif,png'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, first_name, last_name, email, password, last_login, last_modified, is_active, profile_photo, forgot_token, forgot_token_expire, is_forgot_token_valid, is_login_allowed, role, updated, created, created_by, updated_by, external_id, is_manager_as_staff', 'safe', 'on' => 'search'),
			array('new_password, repeat_password', 'required', 'on' => 'createPassword'),
			array('repeat_password', 'compare', 'compareAttribute' => 'new_password', 'on' => 'createPassword'),
			array('new_password, repeat_password,old_password', 'required', 'on' => 'changePassword'),
			array('repeat_password', 'compare', 'compareAttribute' => 'new_password', 'on' => 'changePassword'),
			array('old_password', 'findPasswords', 'on' => 'changePassword'),
		);
	}

	public function checkunique($attribute, $params) {
		if (isset($this->email) && !empty(trim($this->email))) {
                            if ($this->isNewRecord) {
                                    $user = User::model()->findAll('email=:email AND is_deleted=:is_deleted', array(':email' => $this->email, ':is_deleted' => 0));
                                    if (!empty($user)) {
                                            $this->addError($attribute, 'Email Id is already used by another user.');
                                    }
                                    $staffModel = StaffPersonalDetails::model()->findAll(['condition' => 'email_1 = :email', 'params' => [':email' => $this->email]]);
                                    if (!empty($staffModel)) {
                                            $this->addError($attribute, 'Staff with same email Id is already present on the system.');
                                    }
                            } else {
                                    $user = User::model()->findAll(['condition' => 'email = :email AND is_deleted=:is_deleted AND id != :id', 'params' => [':email' => $this->email, ':is_deleted' => 0, ':id' => $this->id]]);
                                    if (!empty($user)) {
                                            $this->addError($attribute, 'Email Id is already used by another user.');
                                    }
                                    $staff_id = !empty(User::model()->findByPk($this->id)->userBranchMappings[0]->staff_id) ? User::model()->findByPk($this->id)->userBranchMappings[0]->staff_id : 0;
                                    $staffModel = StaffPersonalDetails::model()->findAll(['condition' => 'email_1 = :email AND id != :id', 'params' => [':email' => $this->email, ':id' => $staff_id]]);
                                    if (!empty($staffModel)) {
                                            $this->addError($attribute, 'Staff with same email Id is already present on the system.');
                                    }
                            }
		}
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
            'gocardlessAccounts' => array(self::HAS_MANY, 'GocardlessAccounts', 'created_by'),
			'childBookings' => array(self::HAS_MANY, 'ChildBookings', 'created_by'),
			'userBranchMappings' => array(self::HAS_MANY, 'UserBranchMapping', 'user_id'),
		);
	}

	public function defaultScope() {
		return array(
			'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0",
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'first_name' => 'First Name',
			'last_name' => 'Last Name',
			'email' => 'Email',
			'password' => 'Password',
			'last_login' => 'Last Login',
			'last_modified' => 'Last Modified',
			'is_active' => 'Active',
			'profile_photo' => 'Profile Photo',
			'forgot_token' => 'Forgot Token',
			'forgot_token_expire' => 'Forgot Token Expire',
			'is_forgot_token_valid' => 'Is Forgot Token Valid',
			'activate_token' => 'Activate Token',
			'activate_token_expire' => 'Activate Token Expire',
			'is_activate_token_valid' => 'Is Activate Token Valid',
			'is_login_allowed' => 'Login Allowed',
			'created' => 'Created',
			'updated' => 'Updated',
			'created_by' => 'Created By',
			'updated_by' => 'Updated By',
			'external_id' => 'External Id',
			'is_manager_as_staff' => 'Add Manager As Staff'
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

		$criteria->addInCondition('id', customFunctions::getUsersOfSession(Yii::app()->session['company_id'], Yii::app()->session['branch_id'], Yii::app()->session['role']), 'AND');
		$criteria->compare('CONCAT(first_name,last_name)', $this->first_name, true);
		$criteria->compare('email', $this->email, true);
		$criteria->compare('is_active', $this->is_active);
		$criteria->compare('profile_photo', $this->profile_photo, true);
		$criteria->compare('forgot_token', $this->forgot_token, true);
		$criteria->compare('forgot_token_expire', $this->forgot_token_expire, true);
		$criteria->compare('is_forgot_token_valid', $this->is_forgot_token_valid);
		$criteria->compare('activate_token', $this->activate_token, true);
		$criteria->compare('activate_token_expire', $this->activate_token_expire, true);
		$criteria->compare('is_activate_token_valid', $this->is_activate_token_valid);
		$criteria->compare('is_login_allowed', $this->is_login_allowed);
		$criteria->compare('external_id', $this->external_id);
		$criteria->compare('created', $this->created, true);
		$criteria->compare('updated', $this->updated, true);
		$criteria->compare('created_by', $this->created_by);
		$criteria->compare('updated_by', $this->updated_by);
		$criteria->compare('is_manager_as_staff', $this->is_manager_as_staff);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'pagination' => array('pageSize' => 25),
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return User the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public function getRole() {

		$roles = Rights::getAssignedRoles($this->id);
		$userRole = null;
		foreach ($roles as $role) {
			if ($userRole == null) {
				$userRole = $role->name;
			}
		}
		return $userRole;
	}

	//matching the old password with your existing password.
	public function findPasswords($attribute, $params) {
		$user = User::model()->findByPk(Yii::app()->user->id);
		if (!CPasswordHelper::verifyPassword($this->old_password, $user->password))
			$this->addError($attribute, 'Old password is incorrect.');
	}

	public static function getUserName($id) {
		$model = User::model()->findByPk($id);
		return $model->first_name . " " . $model->last_name;
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
                if(isset($this->is_manager_as_staff) && ($this->is_manager_as_staff == 1)){
			$this->is_login_allowed = 1;
		}

		return parent::beforeSave();
	}

	public function afterValidate() {
		if ($this->profile_photo == '' && $this->prevProfilePhoto != '') {
			$this->profile_photo = $this->prevProfilePhoto;
		}
		parent::afterValidate();
	}

	public function findByEmail($email) {
		$model = self::model()->findByAttributes(['email' => $email]);
		if (!empty($model)) {
			return $model->id;
		}
		return false;
	}

	public function importEylogManager($external_id, $manager_data, $branchModel, $role) {
		$model = new User;
		$model->attributes = $manager_data;
		$model->activate_token_expire = date("Y-m-d H:i:s", time() + 1209600);
		if ($model->save()) {
			Yii::app()->authManager->assign($role, $model->id);
			$userBranchMappingModel = new UserBranchMapping;
			$userBranchMappingModel->user_id = $model->id;
			$userBranchMappingModel->company_id = $branchModel->company_id;
			$userBranchMappingModel->branch_id = $branchModel->id;
			if ($userBranchMappingModel->save()) {
				return TRUE;
			} else {
				throw new Exception(CJSON::encode($userBranchMappingModel->getErrors()));
			}
		} else {
			throw new Exception(CJSON::encode($model->getErrors()));
		}
	}
    
    public function getGlobalGoCardlessAccount()
    {
        return GocardlessAccounts::model()->find('t.type=0 and t.is_active=1 and created_by=:user_id', array(':user_id' => $this->id));
//        return self::model()->with('gocardlessAccounts')->find('gocardlessAccounts.type=0 and gocardlessAccounts.is_active=1');
    }

	public static function currentUser() {
		return User::model()->findByPk(Yii::app()->user->id);
	}

	public function getFullName(){
		return $this->first_name." ".$this->last_name;
	}

	public function sendActivationEmail() {
		$url = Yii::app()->createAbsoluteUrl('user/activate', array('activateToken' => $this->activate_token));
		$subject = "Activate your account";
		$content = "Hello " . $this->getFullName() . "<br/><br/>";
		$content .= "<h5>You have successfully created your account. To activate your account please click below :</br></h5>";
		$content .= $url;
		return customFunctions::sendEmail($this->email, $this->getFullName(), $subject, $content);
	}

}
