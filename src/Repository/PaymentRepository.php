<?php

namespace Kartikey\Payment\Repository;

use Stegback\Checkout\Interface\Cart;
use Stegback\Core\Eloquent\Repository;

class PaymentRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Stegback\Core\Models\PaymentGateway';
    }
}
