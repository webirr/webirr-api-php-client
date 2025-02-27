<?php

namespace WeBirr;

// Basic statistics object about bills created & payments received over a time range
// returned by the getStat() API call
class Stat 
{
    public $nBills; // Number of bills created
    public $nBillsPaid; // Number of paid bills
    public $nBillsUnpaid; // Number of bills not yet paid
    public $amountBills; // Amount of bills
    public $amountPaid; // Amount of payment
    public $amountUnpaid; // Amount not yet paid

    public function __construct($data = null)
    {
        if ($data) {
            if (is_object($data)) {
                $data = (array)$data;
            }
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    if (property_exists($this, $key)) {
                        $this->$key = $value;
                    }
                }
            }
        }
    }
}