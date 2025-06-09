<?php

namespace Kartikey\Payment\Repository;

use Stegback\Checkout\Interface\Cart;
use Kartikey\Core\Eloquent\Repository;

class PaymentRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Kartikey\Core\Models\PaymentGateway';
    }
}
