<?php

return [
    'banktransfer'  => [
        'code'        => 'banktransfer',
        'title'       => 'Cash On Delivery',
        'description' => 'Cash On Delivery',
        'class'       => 'Kartikey\Payment\Payments\BankTransfer',
        'active'      => true,
        'sort'        => 1,
    ],

    'paypal'   => [
        'code'        => 'moneytransfer',
        'title'       => 'Money Transfer',
        'description' => 'Money Transfer',
        'class'       => 'Kartikey\Payment\Payments\Paypal',
        'active'      => true,
        'sort'        => 2,
    ],
];
