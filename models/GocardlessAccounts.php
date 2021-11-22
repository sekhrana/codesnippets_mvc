<?php

/**
 * This is the model class for table "tbl_gocardless_accounts".
 *
 * The followings are the available columns in table 'tbl_gocardless_accounts':
 * @property integer $id
 * @property integer $type
 * @property integer $company_id
 * @property integer $branch_id
 * @property string $gc_access_token
 * @property string $gc_organisation_id
 * @property integer $is_active
 * @property integer $is_deleted
 * @property integer $created_by
 * @property string $created
 * @property string $updated
 *
 * The followings are the available model relations:
 * @property Company $company
 * @property Branch $branch
 * @property User $createdBy
 */
class GocardlessAccounts extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'tbl_gocardless_accounts';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('gc_access_token', 'required'),
			array('type, company_id, branch_id, is_active, is_deleted, created_by', 'numerical', 'integerOnly'=>true),
			array('gc_access_token, gc_organisation_id', 'length', 'max'=>255),
			array('created, updated', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, type, company_id, branch_id, gc_access_token, gc_organisation_id, is_active, is_deleted, created_by, created, updated', 'safe', 'on'=>'search'),
		);
	}
    
    public function defaultScope() {
		return array(
			'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0",
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'company' => array(self::BELONGS_TO, 'Company', 'company_id'),
			'branch' => array(self::BELONGS_TO, 'Branch', 'branch_id'),
			'createdBy' => array(self::BELONGS_TO, 'User', 'created_by'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'type' => 'Type',
			'company_id' => 'Company',
			'branch_id' => 'Branch',
			'gc_access_token' => 'Gc Access Token',
			'gc_organisation_id' => 'Gc Organisation',
			'is_active' => 'Is Active',
			'is_deleted' => 'Is Deleted',
			'created_by' => 'Created By',
			'created' => 'Created',
			'updated' => 'Updated',
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
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('type',$this->type);
		$criteria->compare('company_id',$this->company_id);
		$criteria->compare('branch_id',$this->branch_id);
		$criteria->compare('gc_access_token',$this->gc_access_token,true);
		$criteria->compare('gc_organisation_id',$this->gc_organisation_id,true);
		$criteria->compare('is_active',$this->is_active);
		$criteria->compare('is_deleted',$this->is_deleted);
		$criteria->compare('created_by',$this->created_by);
		$criteria->compare('created',$this->created,true);
		$criteria->compare('updated',$this->updated,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return GocardlessAccounts the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
