<?php

namespace WeBirr;

// Bank or wallet channel configured for the merchant.
// Returned by getSupportedBanks().
class SupportedBank
{
    public string $bankID = "";
    public string $name = "";

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
