<?php

namespace WeBirr;

class Bill {

    public $amount;  //String
    public $customerCode; //String
    public $customerName; //String
    public $time;  //Date formatted as string. 
    public $description;  //String
    public $billReference;  //String
    public $merchantID;  //String

    function toArray() {
        return
            [
                'amount' => $this->amount,
                'customerCode' => $this->customerCode,
                'customerName' => $this->customerName,
                'time' => $this->time,
                'description'=>$this->description,
                'billReference' => $this->billReference,
                'merchantID' => $this->merchantID   
            ];
    }
}