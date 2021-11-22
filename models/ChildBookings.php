<?php

//Demo Commit
/**
 * This is the model class for table "tbl_child_bookings".
 *
 * The followings are the available columns in table 'tbl_child_bookings':
 * @property integer $id
 * @property integer $branch_id
 * @property integer $room_id
 * @property integer $child_id
 * @property string $start_date
 * @property string $finish_date
 * @property string $start_time
 * @property string $finish_time
 * @property string $booking_type
 * @property string $created
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $updated
 * @property integer $is_deleted
 * @property integer $session_type_id
 * @property integer $repeat_type_id
 * @property integer $is_invoiced
 * @property integer $included_in_invoice_amount
 * @property integer $exclude_funding
 * @property integer $invoice_id
 *
 * The followings are the available model relations:
 * @property ChildPersonalDetails $child
 * @property Branch $branch
 * @property Room $room
 * @property User $createdBy
 * @property ChildBookingsDetails[] $childBookingsDetails
 * SessionRates $sessionType
 */
class ChildBookings extends CActiveRecord {

	public $booking_session_id;

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
		return 'tbl_child_bookings';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
// NOTE: you should only define rules for those attributes that
// will receive user inputs.
		return array(
			array('branch_id, room_id, child_id, start_date, finish_date, session_type_id, repeat_type_id, start_time,finish_time', 'required'),
			array('finish_date', 'compare', 'compareAttribute' => 'start_date', 'operator' => '>=', 'allowEmpty' => false, 'message' => '{attribute} must be greater than "{compareValue}".'),
			array('finish_time', 'compare', 'compareAttribute' => 'start_time', 'operator' => '>', 'allowEmpty' => false, 'message' => '{attribute} must be greater than "{compareAttribute}".'),
			array('branch_id, room_id, child_id, created_by,is_deleted, session_type_id, repeat_type_id, is_invoiced, created_by, updated_by,included_in_invoice_amount, invoice_id, exclude_funding', 'numerical', 'integerOnly' => true),
			array('booking_type', 'length', 'max' => 9),
			array('updated, created', 'safe'),
			// The following rule is used by search().
// @todo Please remove those attributes that should not be searched.
			array('id, branch_id, room_id, child_id, start_date, finish_date,start_time,finish_time,booking_type, created, created_by,updated_by,updated, is_deleted, is_invoiced, session_type_id, repeat_type_id, included_in_invoice_amount, invoice_id, exclude_funding', 'safe', 'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
// NOTE: you may need to adjust the relation name and the related
// class name for the relations automatically generated below.
		return array(
			'child' => array(self::BELONGS_TO, 'ChildPersonalDetails', 'child_id'),
			'childNds' => array(self::BELONGS_TO, 'ChildPersonalDetailsNds', 'child_id'),
			'branch' => array(self::BELONGS_TO, 'Branch', 'branch_id'),
			'room' => array(self::BELONGS_TO, 'Room', 'room_id'),
			'createdBy' => array(self::BELONGS_TO, 'User', 'created_by'),
			'childBookingsDetails' => array(self::HAS_ONE, 'ChildBookingsDetails', 'booking_id'),
			'sessionType' => array(self::BELONGS_TO, 'SessionRates', 'session_type_id'),
			'invoice' => array(self::BELONGS_TO, 'ChildInvoice', 'invoice_id'),
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
			'child_id' => 'Child',
			'start_date' => 'Start Date',
			'finish_date' => 'Finish Date',
			'start_time' => 'Start Time',
			'finish_time' => 'Finish Time',
			'booking_type' => 'Booking Type',
			'created' => 'Created',
			'created_by' => 'Created By',
			'updated' => 'Updated',
			'updated_by' => 'Updated By',
			'is_deleted' => 'Is Deleted',
			'is_invoiced' => 'Is Invoiced',
			'session_type_id' => 'Session Type',
			'repeat_type_id' => 'Repeat On',
			'included_in_invoice_amount' => 'Included In Invoice Amount',
			'invoice_id' => 'Invoice',
			'exclude_funding' => 'Extra Sessions / Exclude from funding',
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
		$criteria->compare('child_id', $this->child_id);
		$criteria->compare('start_date', $this->start_date, true);
		$criteria->compare('finish_date', $this->finish_date, true);
		$criteria->compare('start_time', $this->start_time, true);
		$criteria->compare('finish_time', $this->finish_time, true);
		$criteria->compare('booking_type', $this->booking_type, true);
		$criteria->compare('created', $this->created, true);
		$criteria->compare('created_by', $this->created_by);
		$criteria->compare('updated', $this->updated);
		$criteria->compare('updated_by', $this->updated_by);
		$criteria->compare('is_deleted', $this->is_deleted);
		$criteria->compare('is_invoiced', $this->is_invoiced);
		$criteria->compare('session_type_id', $this->session_type_id);
		$criteria->compare('repeat_type_id', $this->repeat_type_id);
		$criteria->compare('included_in_invoice_amount', $this->included_in_invoice_amount);
		$criteria->compare('invoice_id', $this->invoice_id);
		$criteria->compare('exclude_funding', $this->exclude_funding);


		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return ChildBookings the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public function customValidation($child_id, $session_id, $childBookingModel) {
		$childModal = ChildPersonalDetails::model()->findByPk($child_id);
		$sessionModal = SessionRates::model()->findByPk($session_id);
		$response = true;
		if (!empty($childModal->enroll_date) && !empty($this->start_date)) {
			if (strtotime($this->start_date) < strtotime($childModal->enroll_date)) {
				$this->addError('start_date', 'Session can only be booked after child Enrollment Date in nursery');
				$response = FALSE;
			}
		}
		if (!empty($childModal->leave_date) && !empty($this->finish_date)) {
			if (strtotime($this->finish_date) > strtotime($childModal->leave_date)) {
				$this->addError('finish_date', 'Session can only be booked before child Finish Date in nursery');
				$response = FALSE;
			}
		}

		if ($sessionModal->is_minimum == 1) {
			$difference = round(abs(strtotime($this->finish_time) - strtotime($this->start_time)) / 60, 2);
			if ($difference < ($sessionModal->minimum_time)) {
				$this->addError('session_type_id', 'The minimum time for this session is ' . $sessionModal->minimum_time . " minutes");
				$response = FALSE;
			}
		}

		if ($sessionModal->is_minimum == 1) {
			if (strtotime(date('H:i:s', strtotime($this->start_time))) < strtotime(date('H:i:s', strtotime($sessionModal->start_time)))) {
				$this->addError('start_time', 'Booking start time can not be less than session start time.');
				$response = FALSE;
			}
		}
		if ($sessionModal->is_minimum == 1) {
			if (strtotime(date("H:i:s", strtotime($this->finish_time))) > strtotime(date("H:i:s", strtotime($sessionModal->finish_time)))) {
				$this->addError('finish_time', 'Booking finish time can not be more than session finish time.');
				$response = FALSE;
			}
		}
		return $response;
	}

	public function getColumnNames() {
		$unset_columns = array('id', 'branch_id', 'created', 'created_by', 'is_deleted');
		$attributes = $this->getAttributes();
		return array_diff(array_keys($attributes), $unset_columns);
	}

	public function getFilter($column_name) {
		$response = array();
		if ($column_name == "child_id") {
			$response[$column_name] = array("filter_condition" => array('=' => 'EQUAL', '!=' => "NOT EQUAL", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => CHtml::listData(ChildPersonalDetails::model()->findAllByAttributes(array('branch_id' => Yii::app()->session['branch_id'])), 'id', 'first_name'));
		} else if ($column_name == "start_date" || $column_name == "finish_date" || $column_name == "start_time" || $column_name == "finish_time") {
			$response[$column_name] = array("filter_condition" => array('=' => 'EQUAL', '>' => "GREATER THAN", '<' => 'SMALLER THAN', '>=' => 'GREATER THAN EQUAL TO', '<=' => 'SMALLER THAN EQUAL TO', 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => array());
		} else if ($column_name == "booking_type") {
			$response[$column_name] = array("filter_condition" => array('=' => 'EQUAL', '!=' => "NOT EQUAL", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => array('SHIFT' => 'SHIFT', 'PERMANENT' => 'PERMANENT'));
		} else if ($column_name == "room_id") {
			$response[$column_name] = array("filter_condition" => array('=' => 'EQUAL', '!=' => "NOT EQUAL", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => CHtml::listData(Room::model()->findAllByAttributes(array('branch_id' => Yii::app()->session['branch_id'])), 'id', 'name'));
		} else if ($column_name == "session_type_id") {
			$response[$column_name] = array("filter_condition" => array('=' => 'EQUAL', '!=' => "NOT EQUAL", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => CHtml::listData(SessionRates::model()->findAllByAttributes(array('branch_id' => Yii::app()->session['branch_id'])), 'id', 'name'));
		} else if ($column_name == "repeat_type_id") {
			$response[$column_name] = array("filter_condition" => array('=' => 'EQUAL', '!=' => "NOT EQUAL", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL'), "filter_value" => $this->getRepeatType());
		} else {
			$response[$column_name] = array("filter_condition" => array('LIKE' => 'LIKE', 'LIKE %--%' => "LIKE %--%", 'IS NULL' => 'IS NULL', 'IS NOT NULL' => 'IS NOT NULL', 'EMPTY' => 'EMPTY'), "filter_value" => array());
		}
		return $response;
	}

	public function getRepeatType() {
		$repeat_options = array(
			0 => "No Repeat (Only On This Day)",
			5 => "Weekly (On Selected Days)",
			1 => "Daily (Including Weekends)",
			2 => "Every Weekday(Monday To Friday)",
			3 => "Every Monday, Wednesday & Friday",
			4 => "Every Tuesday & Thursday"
		);
		return $repeat_options;
		;
	}

	public function getColumnValue($column_name, $column_value) {
		if ($column_name == "child_id") {
			$column_value = ChildPersonalDetails::model()->findByPk($column_value)->first_name . " " . ChildPersonalDetails::model()->findByPk($pk)->last_name;
		} else if ($column_name == "room_id") {
			$column_value = Room::model()->findByPk($column_value)->name;
		} else if ($column_name == "session_type_id") {
			$column_value = SessionRates::model()->findByPk($column_value)->name;
		} else {
			$column_value = $column_value;
		}
		return $column_value;
	}

	public function getRelatedAttributes() {
		$attributes = array();
		$attributes['ChildPersonalDetails'] = ChildPersonalDetails::model()->getRelatedAttributesNames();
		$attributes['Room'] = Room::model()->getRelatedAttributesNames();
		return $attributes;
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

	/**
	 * Function to get all the child bookings based on the parameters.
	 * @param type $start_date
	 * @param type $finish_date
	 * @param type $branch_id
	 * @param type $start_time
	 * @param type $finish_time
	 * @param type $child_id
	 * @param type $room_id
	 * @param type $session_id
	 */
	public function getBookings($start_date, $finish_date, $branch_id, $child_id = NULL, $room_id = NULL, $session_id = NULL, $included_in_invoice_amount = NULL, $is_invoiced = NULL, $exclude_funding = NULL) {
		$criteria = new CDbCriteria();
		$criteria->condition = "t.branch_id = :branch_id AND
                    ((t.start_date >= :start_date and t.start_date <= :finish_date) OR
                    (t.finish_date >= :start_date and t.finish_date <= :finish_date) OR
                    (t.start_date <= :start_date and t.finish_date >= :finish_date))";
		if ($child_id !== NULL) {
			$criteria->addCondition("child_id = $child_id", "AND");
		}
		if ($room_id !== NULL) {
			$criteria->addCondition("room_id = $room_id", "AND");
		}
		if ($session_id !== NULL) {
			$criteria->addCondition("session_type_id = $session_id", "AND");
		}
		if ($included_in_invoice_amount !== NULL) {
			$criteria->addCondition("included_in_invoice_amount = $included_in_invoice_amount", "AND");
		}
		if ($is_invoiced !== NULL) {
			$criteria->addCondition("is_invoiced = $is_invoiced", "AND");
		}
		if ($exclude_funding != NULL) {
			$criteria->addCondition("exclude_funding = $exclude_funding", "AND");
		}
		$criteria->join = "INNER JOIN tbl_child_bookings_details ON (t.id = tbl_child_bookings_details.booking_id)";
		$criteria->params = ['branch_id' => $branch_id, ':start_date' => date("Y-m-d", strtotime($start_date)), ':finish_date' => date("Y-m-d", strtotime($finish_date))];
		$criteria->together = TRUE;
		$model = ChildBookings::model()->findAll($criteria);
		return $model;
	}

	/**
	 * Function to break the booking series between two dates.
	 * @param type $model
	 */
	public function breakSeries($start_date, $finish_date, $branch_id, $child_id = NULL, $model = NULL) {
		if ($model == NULL) {
			$model = ChildBookings::model()->getBookings($start_date, $finish_date, $branch_id, $child_id);
		}
		if (!empty($model)) {
			foreach ($model as $bookings) {
				if (strtotime($bookings->start_date) < strtotime($start_date) AND strtotime($bookings->finish_date) > strtotime($finish_date)) {
					$previousChildBookings = new ChildBookings;
					$previousChildBookingDetails = new ChildBookingsDetails;
					$previousChildBookings->attributes = $bookings->attributes;
					$previousChildBookingDetails->attributes = $bookings->childBookingsDetails->attributes;
					$previousChildBookings->isNewRecord = TRUE;
					$previousChildBookingDetails->isNewRecord = TRUE;
					$nextChildBookings = new ChildBookings;
					$nextChildBookingDetails = new ChildBookingsDetails;
					$nextChildBookings->attributes = $bookings->attributes;
					$nextChildBookingDetails->attributes = $bookings->childBookingsDetails->attributes;
					$nextChildBookings->isNewRecord = TRUE;
					$nextChildBookingDetails->isNewRecord = TRUE;
					$bookings->start_date = $start_date;
					$bookings->finish_date = $finish_date;
					if ($bookings->save()) {
						$previousChildBookings->finish_date = date("Y-m-d", strtotime("-1 day", strtotime($start_date)));
						if ($previousChildBookings->save()) {
							$previousChildBookingDetails->booking_id = $previousChildBookings->id;
							if (!$previousChildBookingDetails->save()) {
								throw new Exception("Their seems to be some problem breaking the series.");
							}
							$nextChildBookings->start_date = date("Y-m-d", strtotime("+1 day", strtotime($finish_date)));
							if ($nextChildBookings->save()) {
								$nextChildBookingDetails->booking_id = $nextChildBookings->id;
								if (!$nextChildBookingDetails->save()) {
									throw new Exception("Their seems to be some problem breaking the series.");
								}
							} else {
								throw new Exception("Their seems to be some problem breaking the series.");
							}
						} else {
							throw new Exception("Their seems to be some problem breaking the series.");
						}
					} else {
						throw new Exception("Their seems to be some problem breaking the series.");
					}
				} else if (strtotime($bookings->start_date) < strtotime($start_date) AND strtotime($bookings->finish_date) <= strtotime($finish_date)) {
					$newChildBookings = new ChildBookings;
					$newChildBookingDetails = new ChildBookingsDetails;
					$newChildBookings->attributes = $bookings->attributes;
					$newChildBookingDetails->attributes = $bookings->childBookingsDetails->attributes;
					$newChildBookings->isNewRecord = TRUE;
					$newChildBookingDetails->isNewRecord = TRUE;
					$bookings->finish_date = date("Y-m-d", strtotime("-1 day", strtotime($start_date)));
					if ($bookings->save()) {
						$newChildBookings->start_date = $start_date;
						if (!$newChildBookings->save()) {
							throw new Exception("Their seems to be some problme breaking the series.");
						}
						$newChildBookingDetails->booking_id = $newChildBookings->id;
						if (!$newChildBookingDetails->save()) {
							throw new Exception("Their seems to be some problme breaking the series.");
						}
					} else {
						throw new Exception("Their seems to be some problme breaking the series.");
					}
				} else if (strtotime($bookings->start_date) >= strtotime($start_date) AND strtotime($bookings->finish_date) > strtotime($finish_date)) {
					$newChildBookings = new ChildBookings;
					$newChildBookingDetails = new ChildBookingsDetails;
					$newChildBookings->attributes = $bookings->attributes;
					$newChildBookingDetails->attributes = $bookings->childBookingsDetails->attributes;
					$newChildBookings->isNewRecord = TRUE;
					$newChildBookingDetails->isNewRecord = TRUE;
					$bookings->finish_date = $finish_date;
					if ($bookings->save()) {
						$newChildBookings->start_date = date("Y-m-d", strtotime("+1 day", strtotime($finish_date)));
						if (!$newChildBookings->save()) {
							throw new Exception("Their seems to be some problme breaking the series.");
						}
						$newChildBookingDetails->booking_id = $newChildBookings->id;
						if (!$newChildBookingDetails->save()) {
							throw new Exception("Their seems to be some problme breaking the series.");
						}
					} else {
						throw new Exception("Their seems to be some problme breaking the series.");
					}
				} else {
					continue;
				}
			}
		}
	}

	public static function getBookingProductsForDay($products, $day_number) {
		$response = array();
		if (!empty($products)) {
			foreach ($products as $id => $days) {
				if (in_array($day_number, $days)) {
					$response[$id][] = $day_number;
				}
			}
			return $response;
		}
		return NULL;
	}

	public static function getBookingProducts($from_date, $to_date, $childModel) {
		$model = ChildBookings::model()->getBookings($from_date, $to_date, $childModel->branch_id, $childModel->id);
		if (!empty($model)) {
			$products = array();
			$allDates = customFunctions::getDatesOfDays($from_date, $to_date, explode(",", $childModel->branch->nursery_operation_days));
			foreach ($model as $booking) {
				$bookingDays = customFunctions::getDatesOfDays($booking->start_date, $booking->finish_date, explode(",", $booking->childBookingsDetails->booking_days));
				$bookingDays = array_intersect($allDates, $bookingDays);
				$childHolidays = array();
				$childHolidayModal = ChildHolidays::model()->findAll([
					'condition' => 'date BETWEEN :start_date and :finish_date and child_id = :child_id',
					'params' => [
						':start_date' => $from_date,
						':finish_date' => $to_date,
						':child_id' => $booking->child_id
					]
				]);
				$childHolidayDays = array();
				if (!empty($childHolidayModal)) {
					foreach ($childHolidayModal as $childHoliday) {
						array_push($childHolidayDays, $childHoliday->date);
					}
				}
				if (!empty($childHolidayDays)) {
					$bookingDays = array_diff($bookingDays, $childHolidayDays);
				}
				if (!empty($bookingDays)) {
					$bookingDays = array_values($bookingDays);
					if (!empty($booking->childBookingsDetails->booking_products) && $booking->childBookingsDetails->booking_products != NULL) {
						$products_array = CJSON::decode($booking->childBookingsDetails->booking_products);
						foreach ($products_array as $productId => $productDays) {
							$productDays = customFunctions::getDatesOfDays($booking->start_date, $booking->finish_date, $productDays);
							$productDays = array_intersect($bookingDays, $productDays);
							if (!empty($productDays)) {
								$products[$productId] += count($productDays);
							}
						}
					}
				}
			}
			$productsArray = [];
			if (!empty($products)) {
				foreach ($products as $productId => $quantity) {
					Products::$as_of = $from_date;
					$productModel = Products::model()->resetScope(true)->findByPk($productId);
					if (!empty($productModel)) {
						$productsArray['id'][] = $productModel->id;
						$productsArray['description'][] = $productModel->description;
						$productsArray['quantity'][] = $quantity;
						$productsArray['price'][] = customFunctions::round($productModel->price, 2);
						$productsArray['discount'][] = customFunctions::round($childModel->discount, 2);
						$amount = customFunctions::round($productModel->price, 2) * $quantity;
						$discount = $amount * 0.01 * customFunctions::round($childModel->discount, 2);
						$amount = $amount - $discount;
						$productsArray['amount'][] = customFunctions::round($amount, 2);
					}
				}
				return customFunctions::getProductsDataForManuaInvoice($productsArray['id'], $productsArray['description'], $productsArray['quantity'], $productsArray['price'], $productsArray['discount'], $productsArray['amount']);
			}
			return false;
		}
		return false;
	}

}
