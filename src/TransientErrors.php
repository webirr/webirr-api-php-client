<?php

namespace WeBirr;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;

class TransientErrors
{
    public static function isTransient($error): bool
    {
        if ($error instanceof RequestException) {
            if (!$error->hasResponse()) {
                return true;
            }

            $statusCode = $error->getResponse()->getStatusCode();
            return $statusCode >= 500 || $statusCode === 429 || $statusCode === 408;
        }

        return $error instanceof TransferException;
    }
}
