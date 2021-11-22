<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ChildInvoiceNds
 *
 * @author nishant
 */
class ChildInvoiceNds extends ChildInvoice {

	//put your code here

	public function defaultScope() {
		return [];
	}

	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public function amountPending() {
    $due_amount = 0;
		$transactionModel = $this->childInvoiceTransactions;
		if (empty($transactionModel)) {
			$due_amount = $this->total;
		} else {
			$total_amount = $this->total;
			foreach ($transactionModel as $transaction) {
				$due_amount = $due_amount + $transaction->paid_amount;
			}
			if ($this->invoice_type == 0 || $this->invoice_type == 1) {
				$due_amount = $total_amount - $due_amount;
			} else {
				$due_amount = $total_amount + $due_amount;
			}
		}
		return customFunctions::round($due_amount, 2);
	}

	public  function amountPaid() {
    $paid_amount = 0;
    $transactionModel = $this->childInvoiceTransactions;
    if (!empty($transactionModel)) {
      foreach ($transactionModel as $transaction) {
        $paid_amount = $paid_amount + $transaction->paid_amount;
      }
    }
    return customFunctions::round($paid_amount, 2);
  }

}
