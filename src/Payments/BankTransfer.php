<?php

namespace Kartikey\Payment\Payments;
use Kartikey\Payment\Payments\Payment as PaymentClass;
use Illuminate\Support\Facades\Storage;

class BankTransfer extends PaymentClass
{

    protected $code = 'banktransfer'; //* Kartikey\Payment\Payments\BankTransfer in DB
    protected $status = 1;

    public function isAvailable()
    {
        return true;
    }

        /**
     * Get redirect url.
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return route('shop.cart.checkout.order.store',['gateway' => 'cod', 'gateway_callback' => true]);
    }


    public function getCode()
    {
        return $this->code;
    }
}
