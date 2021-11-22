<?php

//Demo Commit
/**
 * This is the model class for table "tbl_room".
 *
 * The followings are the available columns in table 'tbl_room':
 * @property integer $id
 * @property integer $branch_id
 * @property string  $name
 * @property integer $capacity
 * @property integer $age_group_lower
 * @property integer $age_group_upper
 * @property integer $is_deleted
 * @property string  $logo
 * @property string  $description
 * @property string  $color
 * @property integer $is_active
 * @property integer $create_for_existing
 * @property integer $external_id
 * @property string  $created
 * @property integer $created_by
 * @property string  $updated
 * @property integer $updated_by
 * @property integer $global_room_id
 * @property integer $show_on_bookings
 *
 * The followings are the available model relations:
 * @property ChildBookings[] $childBookings
 * @property Branch $branch
 */
class Room extends CActiveRecord {

	const ROOM_API_PATH = "/api-eyman/groups";
	const ROOM_IMPORT_API_PATH = "/api/children/getRooms";

	public $roomName = '';
	public $file_name;
	public $room_logo_raw;
	public $room_logo_integration;
	public $previous_image;

	/**
	 * @return string the associated database table name
	 */
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

			if (Yii::app()->session['role'] == "parent") {
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0"
				);
			}
		}
		if (get_class(Yii::app()) === "CConsoleApplication") {
			return array(
				'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0",
			);
		}
	}

	public function tableName() {
		return 'tbl_room';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('name, capacity, age_group_lower, age_group_upper, color, description', 'required'),
			array('branch_id, is_deleted, updated_by, created_by, external_id, create_for_existing, is_active, is_global, global_id, global_room_id', 'numerical', 'integerOnly' => true),
			array('age_group_lower, age_group_upper', 'numerical'),
			array('capacity', 'numerical', 'integerOnly' => true, 'max' => 500),
			array('name, logo, description', 'length', 'max' => 255),
			array('color', 'length', 'max' => 45),
                        array('show_on_bookings', 'length', 'max' => 1),
//			array('name', 'checkRoomAlreadyExistInBranch'),
			array('age_group_upper', 'compare', 'compareAttribute' => 'age_group_lower', 'operator' => '>=', 'message' => '{attribute} must be greater than "{compareValue}"'),
			array('logo', 'file','types'=>'jpg, gif, png', 'allowEmpty'=>true, 'on'=>'update'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, branch.name,create_for_existing, name,is_active, capacity, age_group_lower, age_group_upper, is_deleted, logo, description, color, external_id, created, created_by, updated, updated_by, global_room_id, show_on_bookings', 'safe', 'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'childBookings' => array(self::HAS_MANY, 'ChildBookings', 'room_id'),
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
			'branch.name' => 'Branch Name',
			'name' => 'Name',
			'is_active' => 'Active',
			'capacity' => 'Capacity',
			'age_group_lower' => 'Age Group Lower(In Months)',
			'age_group_upper' => 'Age Group Upper(In Months)',
			'is_deleted' => 'Deleted',
			'logo' => 'Logo',
			'description' => 'Description',
			'color' => 'Color',
			'create_for_existing' => 'Create For Existing',
			'external_id' => 'External',
			'created' => 'Created',
			'created_by' => 'Created By',
			'updated' => 'Updated',
			'updated_by' => 'Updated By',
			'global_room_id' => 'Global Room',
      'show_on_bookings' => 'Used for children sessions'
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
		$criteria->compare('t.id', $this->id);
		if (isset(Yii::app()->session['global_id'])) {
			$criteria->compare('global_id', Yii::app()->session['global_id']);
			$criteria->compare('is_global', 1);
		} else {
			$criteria->compare('branch_id', Yii::app()->session['branch_id']);
			$criteria->compare('is_global', 0);
		}
		$criteria->compare('t.name', $this->name, true);
		$criteria->compare('is_active', $this->is_active);
		$criteria->compare('t.capacity', $this->capacity);
		$criteria->compare('t.age_group_lower', $this->age_group_lower);
		$criteria->compare('t.age_group_upper', $this->age_group_upper);
		$criteria->compare('is_deleted', $this->is_deleted);
		$criteria->compare('logo', $this->logo, true);
		$criteria->compare('color', $this->color, true);
		$criteria->compare('description', $this->description, true);
		$criteria->compare('external_id', $this->external_id);
		$criteria->compare('created', $this->created, true);
		$criteria->compare('created_by', $this->created_by);
		$criteria->compare('updated', $this->updated, true);
		$criteria->compare('updated_by', $this->updated_by);
		$criteria->compare('global_room_id', $this->global_room_id);
		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	public function beforeSave() {
		if (get_class(Yii::app()) === "CWebApplication") {
			$this->age_group_lower = ($this->age_group_lower / 12);
			$this->age_group_upper = ($this->age_group_upper / 12);
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

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Room the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public static function getChildCount($room_id) {
		$count = ChildPersonalDetails::model()->countByAttributes(array('room_id' => $room_id));
		return $count;
	}

	public function getFilter($column_name) {
		$response = array();
		if ($column_name == "capacity" || $column_name == "age_group_lower" || $column_name == "age_group_upper" || $column_name == "description") {
			$response[$column_name] = array("filter_condition" => array('LIKE' => 'LIKE', 'LIKE %--%' => "LIKE %--%", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL', 'EMPTY' => 'EMPTY'), "filter_value" => array());
		}
		return $response;
	}

	public function getColumnValue($column_name, $column_value) {
		return $column_value;
	}

	public function getRelatedAttributesNames() {
		$attributes = array('capacity', 'age_group_lower', 'age_group_upper', 'description');
		return $attributes;
	}

	/*
	 * Check room name is already exist in branch
	 */

	public function checkRoomAlreadyExistInBranch($attributes, $params) {
		if (!empty($this->name) && isset($this->name)) {
			$criteria = new CDbCriteria();
			$roomModel = self::model()->findAllByAttributes(['branch_id' => $this->branch_id]);
			if (!empty($roomModel)) {
				foreach ($roomModel as $room) {
					if ($room->id != $this->id && strtolower(trim($room->name)) == strtolower(trim($this->name))) {
						$this->addError('name', 'Room name already exist in current branch.');
						return false;
					}
				}
			}
		}
	}

	public function importEylogRoom($external_id, $room_data) {
		$roomColor = array('orange', 'blue', 'green', 'red', 'yellow', 'violet', 'purple', 'darkorange', 'blueviolet');
		$model = Room::model()->findByAttributes(['external_id' => $external_id]);
		if (!empty($model)) {
			$model->attributes = $room_data;
		} else {
			$model = new Room;
			$model->attributes = $room_data;
			$model->color = $roomColor[array_rand($roomColor, 1)];
		}
		if ($model->save()) {
			return TRUE;
		} else {
			throw new Exception(CJSON::encode($model->getErrors()));
		}
	}

	public function getAgeGroupLower() {
		return (int) ($this->age_group_lower * 12);
	}

	public function getAgeGroupUpper() {
		return (int) ($this->age_group_upper * 12);
	}

	public function uploadRoomLogo() {
		$rackspace = new eyManRackspace();
		$rackspace->uploadObjects([[
			'name' => "/images/room/" . $this->file_name,
			'body' => $this->room_logo_raw
			]
		]);
		$this->logo = "/images/room/" . $this->file_name;
	}

	public function eylogIntegration() {
		if ($this->branch->is_integration_enabled == 1) {
			if (!empty($this->branch->api_key) && !empty($this->branch->api_password) && !empty($this->branch->api_url)) {
				$ch = curl_init($this->branch->api_url . Room::ROOM_API_PATH);
				$room_data = array(
					'api_key' => $this->branch->api_key,
					'api_password' => $this->branch->api_password,
					'groups' => array(
						array(
							'name' => $this->name,
							'description' => $this->description,
							'external_id' => "eyman-" . $this->id,
							'photo' => (isset($this->logo) && !empty($this->logo)) ? $this->logo : ""
						)
					)
				);
				$room_data = json_encode($room_data);
				curl_setopt_array($ch, array(
					CURLOPT_FOLLOWLOCATION => 1,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_POST => 1,
					CURLOPT_POSTFIELDS => $room_data,
					CURLOPT_HEADER => 0,
					CURLOPT_USERAGENT => "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)",
					CURLOPT_SSL_VERIFYPEER => false
				));
				$response = curl_exec($ch);
				$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				if ($httpcode == "404") {
					throw new Exception("Please check whether API path is a valid URL.");
				}
				if (curl_errno($ch)) {
					throw new Exception(curl_error($ch));
				}
				curl_close($ch);
				$response = json_decode($response, TRUE);
				if ($response['response'][0]['message'] == 'Added' && $response['response'][0]['status'] == 'success') {
					Room::model()->updateByPk($this->id, [
						'external_id' => $response['response'][0]['id']
					]);
				}
				if ($response['response'][0]['message'] != "Added" && $response['response'][0]['message'] != "Updated") {
					throw new Exception($response['response'][0]['message']);
				}
			} else {
				throw new Exception("API Key/Password/Url are not set in Branch/Nursery Settings.");
			}
		}
	}

	public function createRoomGlobalSettings() {
		$branchModel = Company::currentCompany()->branches;
		if (!empty($branchModel)) {
			foreach ($branchModel as $branch) {
				$model = new Room;
				$model->attributes = $this->attributes;
				$model->branch_id = $branch->id;
				$model->logo = $this->logo;
				$model->is_global = 0;
				$model->global_id = $branch->company_id;
				$model->create_for_existing = 0;
				$model->global_room_id = $this->id;
				if (!$model->save()) {
					throw new Exception(CHtml::errorSummary($model, "", "", array('class' => 'customErrors errorSummary')));
				}
			}
		}
	}

	public function updateRoomGlobalSettings() {
		if ($this->is_global = 1) {
			$branchRoomModel = Room::model()->findAllByAttributes(array('global_room_id' => $this->id));
			foreach ($branchRoomModel as $branchRoom) {
				$branchRoom->logo = $this->logo;
				$branchRoom->name = $this->name;
				$branchRoom->description = $this->description;
				$branchRoom->age_group_lower = $this->age_group_lower;
				$branchRoom->age_group_upper = $this->age_group_upper;
				$branchRoom->capacity = $this->capacity;
				$branchRoom->color = $this->color;
				$branchRoom->create_for_existing = 0;
				$branchRoom->is_global = 0;
				if (!$branchRoom->save()) {
					throw new Exception(CHtml::errorSummary($branchRoom, '', '', array('class' => 'customErrors')));
				}
			}
		}
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
