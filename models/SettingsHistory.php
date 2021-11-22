<?php

/**
 * This is the model class for table "tbl_settings_history".
 *
 * The followings are the available columns in table 'tbl_settings_history':
 * @property string $id
 * @property integer $type
 * @property string $date
 * @property integer $previous_id
 * @property integer $new_id
 * @property string $created
 * @property integer $created_by
 */
class SettingsHistory extends CActiveRecord {

	const PRODUCTS = 1;

	public $date_columns = array('date');

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'tbl_settings_history';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('type, date, previous_id, new_id', 'required'),
			array('type, previous_id, new_id, created_by', 'numerical', 'integerOnly' => true),
			array('created', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, type, date, previous_id, new_id, created, created_by', 'safe', 'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'modifiedProducts' => array(self::BELONGS_TO, 'Products', 'new_id'),
			'products' => array(self::BELONGS_TO, 'Products', 'previous_id'),
		);
	}

	public function behaviors() {
		return array(
			'dateFormatter' => array(
				'class' => 'application.components.DateFormatter',
				'date_columns' => array('date')
			)
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'type' => 'Type',
			'date' => 'Date',
			'previous_id' => 'Previous',
			'new_id' => 'New',
			'created' => 'Created',
			'created_by' => 'Created By',
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

		$criteria->compare('id', $this->id, true);
		$criteria->compare('type', $this->type);
		$criteria->compare('date', $this->date, true);
		$criteria->compare('previous_id', $this->previous_id);
		$criteria->compare('new_id', $this->new_id);
		$criteria->compare('created', $this->created, true);
		$criteria->compare('created_by', $this->created_by);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return SettingsHistory the static model class
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

	public function validate($attributes = null, $clearErrors = true) {
		if (isset($this->date) && !empty($this->date)) {
			$model = SettingsHistory::model()->find([
				'select' => 'max(date) as date',
				'condition' => 'previous_id = :previous_id AND type = :type',
				'params' => [
					':previous_id' => $this->previous_id,
					':type' => $this->type
				]
			]);
			if ($model) {
				if (strtotime($this->date) <= strtotime($model->date)) {
					$this->addError('date', 'Effective date can not be less than ' . $model->date);
					return false;
				}
			}
		}
		return parent::validate($attributes, $clearErrors);
	}

}
