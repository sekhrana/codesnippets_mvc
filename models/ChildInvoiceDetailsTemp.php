<?php

/**
 * This is the model class for table "tbl_child_invoice_details_temp".
 *
 * The followings are the available columns in table 'tbl_child_invoice_details_temp':
 * @property integer $id
 * @property integer $invoice_id
 * @property string $session_data
 * @property string $session_room_data
 * @property string $products_data
 * @property string $week_start_date
 * @property string $week_end_date
 * @property integer $session_id
 * @property integer $session_type
 * @property double $rate
 * @property double $average_rate
 * @property integer $total_days
 * @property double $total_hours
 * @property double $funded_hours
 * @property double $funded_rate
 * @property double $discount
 * @property string $created
 * @property string $updated
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $is_minimum_booking
 * @property integer $is_extras
 * @property integer $exclude_funding
 * @property integer $is_deleted
 *
 * The followings are the available model relations:
 * @property ChildInvoiceTemp $invoice
 */
class ChildInvoiceDetailsTemp extends ChildInvoiceDetails
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'tbl_child_invoice_details_temp';
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return ChildInvoiceDetailsTemp the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
