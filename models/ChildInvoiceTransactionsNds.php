<?php

/**
 * This is the model class for table "tbl_child_invoice_transactions".
 * The followings are the available model relations:
 * @property ChildInvoice $invoice
 */
class ChildInvoiceTransactionsNds extends ChildInvoiceTransactions {

	public function defaultScope() {
		return [];
	}

	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

}
