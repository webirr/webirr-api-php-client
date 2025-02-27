<?php

namespace WeBirr;

// Payment class to hold the payment details
// returned by the getPaymentStatus() & getPayments() API calls
// this same object is also posted to Webhook callbacks
// It is also used to hold the payment details of a bill
class Payment
{
    public int $status; // Int 1. Payment in Progress (unconfirmed payment) 2. Paid  3. Reversed/Canceled
    public string $id;     // Int
    public string $bankID; // String
    public string $paymentReference; // String
    public string $paymentDate; // Date formatted as string 
    public string $time; // save value as $paymentDate for backward compatibility
    public bool $confirmed;  // Boolean
    public string $confirmedTime; // Date formatted as string
    public bool $canceled; // Boolean - indicates if the payment was reversed.
    public string $canceledTime; // Date formatted as string
    public string $amount; // Decimal formatted as string
    public string $wbcCode; // String
    public string $updateTimeStamp; // High Precision Timestamp formatted as readable string

    public function IsPaid() {
        return $this->status == 2;
    }

    public function IsReversed() {
        return $this->status == 3;
    }

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