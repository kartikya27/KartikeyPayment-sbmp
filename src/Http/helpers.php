<?php

use Kartikey\Payment\Payments\Payment as PaymentClass;

if (!function_exists('payment')) {
    function payment()
    {
        return new PaymentClass;
    }
}
