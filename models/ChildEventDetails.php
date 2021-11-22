<?php

/**
 * This is the model class for table "tbl_child_event_details".
 *
 * The followings are the available columns in table 'tbl_child_event_details':
 * @property integer $id
 * @property integer $child_id
 * @property integer $event_id
 * @property string $title_date_1_value
 * @property string $title_date_2_value
 * @property string $title_description_value
 * @property string $title_notes_value
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $is_deleted
 * @property integer $status
 *
 * The followings are the available model relations:
 * @property ChildPersonalDetails $child
 * @property EventType $event
 */
class ChildEventDetails extends CActiveRecord {

    public $date_columns = array('title_date_1_value', 'title_date_2_value');

    const PENDING = 0;
    const COMPLETED = 1;

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'tbl_child_event_details';
    }

    public function defaultScope() {
        if (get_class(Yii::app()) === "CWebApplication") {
            return array(
                'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0",
            );
        }
        if (get_class(Yii::app()) === "CConsoleApplication") {
            return array(
                'condition' => $this->getTableAlias(false, false) . ".is_deleted = 0",
            );
        }
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('child_id, event_id', 'required'),
            array('child_id, event_id, is_deleted, created_by, updated_by, status', 'numerical', 'integerOnly' => true),
            array('title_date_1_value, title_date_2_value', 'default', 'setOnEmpty' => true, 'value' => NULL),
            array('title_date_1_value, title_date_2_value, title_description_value, title_notes_value, created,updated', 'safe'),
            array('title_date_1_value, title_date_2_value', 'default', 'setOnEmpty' => true, 'value' => NULL),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id, child_id, event_id, title_date_1_value, title_date_2_value, title_description_value, title_notes_value, created, created_by, updated_by, updated , is_deleted, status', 'safe', 'on' => 'search'),
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
            'event' => array(self::BELONGS_TO, 'EventType', 'event_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'child_id' => 'Child',
            'event_id' => 'Event',
            'title_date_1_value' => 'Title Date 1 Value',
            'title_date_2_value' => 'Title Date 2 Value',
            'title_description_value' => 'Title Description Value',
            'title_notes_value' => 'Title Notes Value',
            'created' => 'Created',
            'updated' => 'Updated',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'is_deleted' => 'Deleted',
            'status' => 'Status',
        );
    }

    public function behaviors() {
        return array(
            'dateFormatter' => array(
                'class' => 'application.components.DateFormatter',
                'date_columns' => array('title_date_2_value', 'title_date_1_value')
            )
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
        $criteria->compare('child_id', Yii::app()->request->getParam('child_id'));
        $criteria->compare('event_id', $this->event_id);
        $criteria->compare('title_date_1_value', $this->title_date_1_value, true);
        $criteria->compare('title_date_2_value', $this->title_date_2_value, true);
        $criteria->compare('title_description_value', $this->title_description_value, true);
        $criteria->compare('title_notes_value', $this->title_notes_value, true);
        $criteria->compare('created', $this->created, true);
        $criteria->compare('updated', $this->updated, true);
        $criteria->compare('created_by', $this->created_by);
        $criteria->compare('updated_by', $this->updated_by);
        $criteria->compare('is_deleted', $this->is_deleted);
        $criteria->compare('status', $this->status);

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
            'pagination' => array('pageSize' => 20),
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ChildEventDetails the static model class
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

}
