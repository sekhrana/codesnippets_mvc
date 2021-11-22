<?php

// Demo Commit
/**
 * This is the model class for table "tbl_products".
 *
 * The followings are the available columns in table 'tbl_products':
 *
 * @property integer $id
 * @property integer $branch_id
 * @property string $name
 * @property string $description
 * @property string $type
 * @property double $price
 * @property double $cost
 * @property integer $price_override
 * @property integer $cost_override
 * @property integer $sales_code
 * @property integer $purchase_code
 * @property string $narrative
 * @property integer $is_vat_applied
 * @property integer $vat_id
 * @property integer $apply_as_discount
 * @property integer $allow_multiple_instances
 * @property integer $allow_quantity_override
 * @property integer $allow_recurring_item
 * @property integer $allow_booking_screen
 * @property integer $is_deleted
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $is_active
 * @property integer $is_global
 * @property integer $global_id
 * @property integer $create_for_existing
 * @property integer $create_invoice
 * @property integer $global_products_id The followings are the available model relations:
 * @property AnalysisCodes $salesCode
 * @property AnalysisCodes $purchaseCode
 * @property Branch $branch
 * @property Vatcodes $vat
 * @property integer $is_modified
 *
 */
class Products extends CActiveRecord {

	public static $as_of;
	public $effective_date;

	/**
	 *
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'tbl_products';
	}

	public function scopes() {
		if (get_class(Yii::app()) === "CWebApplication") {
			if (Yii::app()->session['role'] == "superAdmin") {
				return array(
					'active' => array(
						'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 AND " . $this->getTableAlias(false, false) . ".is_active = 1"
					)
				);
			}

			if (Yii::app()->session['role'] == "companyAdministrator") {
				$company_id = UserBranchMapping::model()->findByAttributes(array(
						'user_id' => Yii::app()->user->getId()
					))->company_id;
				$branchString = implode(',', customFunctions::getBranchesByCompany($company_id));
				return array(
					'active' => array(
						'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ") AND " . $this->getTableAlias(false, false) . ".is_active = 1"
					)
				);
			}

			if (Yii::app()->session['role'] == "areaManager") {
				$userMappingModal = UserBranchMapping::model()->findAllByAttributes(array(
					'user_id' => Yii::app()->user->getId()
				));
				$branchString = implode(',', customFunctions::getBranchesOfAreaManager($userMappingModal));
				return array(
					'active' => array(
						'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ") AND " . $this->getTableAlias(false, false) . ".is_active = 1"
					)
				);
			}
			if (Yii::app()->session['role'] == "branchManager") {
				$userMapping = UserBranchMapping::model()->findByAttributes(array(
					'user_id' => Yii::app()->user->getId()
				));
				return array(
					'active' => array(
						'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id =" . $userMapping->branch_id . " AND " . $this->getTableAlias(false, false) . ".is_active = 1"
					)
				);
			}
			if (Yii::app()->session['role'] == "branchAdmin") {
				$userMapping = UserBranchMapping::model()->findByAttributes(array(
					'user_id' => Yii::app()->user->getId()
				));
				return array(
					'active' => array(
						'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id =" . $userMapping->branch_id . " AND " . $this->getTableAlias(false, false) . ".is_active = 1"
					)
				);
			}
			if (Yii::app()->session['role'] == "staff") {
				$branchId = UserBranchMapping::model()->findByAttributes(array(
						'user_id' => Yii::app()->user->id
					))->branch_id;
				return array(
					'active' => array(
						'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id =" . $branchId . " AND " . $this->getTableAlias(false, false) . ".is_active = 1"
					)
				);
			}
			if (Yii::app()->session['role'] == "hrAdmin" || Yii::app()->session['role'] == User::HR_STANDARD) {
				return array(
					'active' => array(
						'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 " . " AND " . $this->getTableAlias(false, false) . ".is_active = 1"
					)
				);
			}
			if (Yii::app()->session['role'] == "accountsAdmin") {
				$company_id = UserBranchMapping::model()->findByAttributes(array(
						'user_id' => Yii::app()->user->getId()
					))->company_id;
				$branchString = implode(',', customFunctions::getBranchesByCompany($company_id));
				return array(
					'active' => array(
						'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")" . " AND " . $this->getTableAlias(false, false) . ".is_active = 1"
					)
				);
			}
		}
	}

	public function defaultScope() {
		if (get_class(Yii::app()) === "CWebApplication") {
			if (Yii::app()->session['role'] == "superAdmin") {
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0"
				);
			}

			if (Yii::app()->session['role'] == "companyAdministrator") {
				$company_id = UserBranchMapping::model()->findByAttributes(array(
						'user_id' => Yii::app()->user->getId()
					))->company_id;
				$branchString = implode(',', customFunctions::getBranchesByCompany($company_id));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")"
				);
			}

			if (Yii::app()->session['role'] == "areaManager") {
				$userMappingModal = UserBranchMapping::model()->findAllByAttributes(array(
					'user_id' => Yii::app()->user->getId()
				));
				$branchString = implode(',', customFunctions::getBranchesOfAreaManager($userMappingModal));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")"
				);
			}
			if (Yii::app()->session['role'] == "branchManager") {
				$userMapping = UserBranchMapping::model()->findByAttributes(array(
					'user_id' => Yii::app()->user->getId()
				));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id =" . $userMapping->branch_id
				);
			}
			if (Yii::app()->session['role'] == "branchAdmin") {
				$userMapping = UserBranchMapping::model()->findByAttributes(array(
					'user_id' => Yii::app()->user->getId()
				));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id =" . $userMapping->branch_id
				);
			}
			if (Yii::app()->session['role'] == "staff") {
				$branchId = UserBranchMapping::model()->findByAttributes(array(
						'user_id' => Yii::app()->user->id
					))->branch_id;
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id =" . $branchId
				);
			}
			if (Yii::app()->session['role'] == "hrAdmin" || Yii::app()->session['role'] == User::HR_STANDARD) {
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0"
				);
			}
			if (Yii::app()->session['role'] == "accountsAdmin") {
				$company_id = UserBranchMapping::model()->findByAttributes(array(
						'user_id' => Yii::app()->user->getId()
					))->company_id;
				$branchString = implode(',', customFunctions::getBranchesByCompany($company_id));
				return array(
					'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0 and " . $this->getTableAlias(false, false) . ".branch_id IN(" . $branchString . ")"
				);
			}
		}
		if (get_class(Yii::app()) === "CConsoleApplication") {
			return array(
				'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0"
			);
		}
	}

	/**
	 *
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array(
				'name, description, type',
				'required'
			),
			array(
				'effective_date', 'required', 'on' => 'modify'
			),
			array(
				'branch_id, price_override, cost_override, sales_code, purchase_code, is_vat_applied, vat_id, apply_as_discount, allow_multiple_instances, allow_quantity_override, allow_recurring_item, allow_booking_screen, is_deleted, is_global, global_id, create_for_existing, create_invoice, global_products_id, created_by, updated_by, is_modified',
				'numerical',
				'integerOnly' => true
			),
			array(
				'price, cost',
				'numerical'
			),
			array(
				'name',
				'length',
				'max' => 45
			),
			array(
				'description',
				'length',
				'max' => 255
			),
			array(
				'type',
				'length',
				'max' => 9
			),
			array(
				'narrative, created, updated',
				'safe'
			),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array(
				'id,is_active,create_for_existing, branch_id, name, description, type, price, cost, price_override, cost_override, sales_code, purchase_code, narrative, is_vat_applied, vat_id, apply_as_discount, allow_multiple_instances, allow_quantity_override, allow_recurring_item, allow_booking_screen, is_deleted, is_global, global_id, create_for_existing, create_invoice, global_products_id, updated, created, created_by, updated_by, is_modified',
				'safe',
				'on' => 'search'
			)
		);
	}

	/**
	 *
	 * @return array relational rules.
	 */
	public function relations() {
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'salesCode' => array(
				self::BELONGS_TO,
				'AnalysisCodes',
				'sales_code'
			),
			'purchaseCode' => array(
				self::BELONGS_TO,
				'AnalysisCodes',
				'purchase_code'
			),
			'branch' => array(
				self::BELONGS_TO,
				'Branch',
				'branch_id'
			),
			'vat' => array(
				self::BELONGS_TO,
				'Vatcodes',
				'vat_id'
			),
			'modified' => array(
				self::HAS_MANY,
				'SettingsHistory',
				'previous_id'
			)
		);
	}

	/**
	 *
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'branch_id' => 'Branch',
			'name' => 'Name',
			'description' => 'Description',
			'type' => 'Type',
			'price' => 'Default Price',
			'cost' => 'Default Cost',
			'price_override' => 'Allow Price Override',
			'cost_override' => 'Allow Cost Override',
			'sales_code' => 'Sales Code',
			'purchase_code' => 'Purchase Code',
			'narrative' => 'Comments / Notes',
			'is_vat_applied' => 'VAT applies',
			'vat_id' => 'VAT Code',
			'apply_as_discount' => 'Apply as discount',
			'allow_multiple_instances' => 'Allow multiple instances',
			'allow_quantity_override' => 'Allow quantity override',
			'allow_recurring_item' => 'Allow to be a recurring item',
			'allow_booking_screen' => 'Show on Child Scheduling Screen',
			'is_deleted' => 'Deleted',
			'created' => 'Created',
			'updated' => 'Updated',
			'created_by' => 'Created By',
			'updated_by' => 'Updated By',
			'is_active' => 'Active',
			'is_global' => 'Is Global',
			'global_id' => 'Global',
			'create_for_existing' => 'Create For Existing Nursery/Branch',
			'create_invoice' => 'Price included in session rates',
			'global_products_id' => 'Global Products',
			'is_modified' => 'Modified',
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
	 *         based on the search/filter conditions.
	 */
	public function search() {
		// @todo Please modify the following code to remove attributes that should not be searched.
		$criteria = new CDbCriteria();

		$criteria->compare('id', $this->id);
		if (isset(Yii::app()->session['global_id'])) {
			$criteria->compare('global_id', Yii::app()->session['global_id']);
			$criteria->compare('is_global', 1);
		} else {
			$criteria->compare('branch_id', Yii::app()->session['branch_id']);
			$criteria->compare('is_global', 0);
		}
		$criteria->compare('name', $this->name, true);
		$criteria->compare('is_active', $this->is_active);
		$criteria->compare('description', $this->description, true);
		$criteria->compare('type', $this->type, true);
		$criteria->compare('price', $this->price);
		$criteria->compare('cost', $this->cost);
		$criteria->compare('price_override', $this->price_override);
		$criteria->compare('cost_override', $this->cost_override);
		$criteria->compare('sales_code', $this->sales_code);
		$criteria->compare('purchase_code', $this->purchase_code);
		$criteria->compare('narrative', $this->narrative, true);
		$criteria->compare('is_vat_applied', $this->is_vat_applied, true);
		$criteria->compare('vat_id', $this->vat_id);
		$criteria->compare('apply_as_discount', $this->apply_as_discount);
		$criteria->compare('allow_multiple_instances', $this->allow_multiple_instances);
		$criteria->compare('allow_quantity_override', $this->allow_quantity_override);
		$criteria->compare('allow_recurring_item', $this->allow_recurring_item);
		$criteria->compare('allow_booking_screen', $this->allow_booking_screen);
		$criteria->compare('is_deleted', $this->is_deleted);
		$criteria->compare('created', $this->created, true);
		$criteria->compare('updated', $this->updated, true);
		$criteria->compare('created_by', $this->created_by);
		$criteria->compare('updated_by', $this->updated_by);
		$criteria->compare('is_active', $this->is_active);
		$criteria->compare('is_global', $this->is_global);
		$criteria->compare('global_id', $this->global_id);
		$criteria->compare('create_for_existing', $this->create_for_existing);
		$criteria->compare('create_invoice', $this->create_invoice);
		$criteria->compare('global_products_id', $this->global_products_id);
		$criteria->compare('is_modified', 0);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'pagination' => array(
				'pageSize' => 25
			)
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 *
	 * @param string $className
	 *            active record class name.
	 * @return Products the static model class
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

	public function afterFind() {
		if(!isset(self::$as_of)){
			Products::$as_of = date("Y-m-d");
		}
		if (isset(self::$as_of)) {
			$modifiedProductModel = SettingsHistory::model()->find([
				'condition' => 'type = 1 AND previous_id = :id AND :effective_date >= date',
				'order' => 'date DESC',
				'params' => [
					':id' => $this->id,
					':effective_date' => self::$as_of
				]
			]);
			if (!empty($modifiedProductModel)) {
				$model = Products::model()->findByPk($modifiedProductModel->new_id);
				if (!empty($model)) {
					$this->price = $model->price;
					$this->cost = $model->cost;
				}
			}
		}
		return parent::afterFind();
	}

}
