<?php

namespace WeBirr;

class Bill {

    public string $amount;  //Decimal formated as String
    public string $customerCode; //String
    public string $customerName; //String
    public string $time;  //DateTime formatted as string  
    public string $description;  //String
    public string $billReference;  //String
    public string $merchantID;  //String
    public array $extras = [""=>""];  // Associative array(string,string) for extras

    function toArray() {
        return
            [
                'amount' => $this->amount,
                'customerCode' => $this->customerCode,
                'customerName' => $this->customerName,
                'time' => $this->time,
                'description'=>$this->description,
                'billReference' => $this->billReference,
                'merchantID' => $this->merchantID,
                'extras' => $this->extras   
            ];
    }
}