<?php

namespace Kartikey\Payment\Payments;

use Stegback\Checkout\Facades\Cart;

abstract class Payment
{


    /**
     * Cart.
     *
     * @var \Stegback\Checkout\Interface\Cart
     */
    protected $cart;

    /**
     * Set cart.
     *
     * @var void
     */
    public function setCart()
    {
        if (!$this->cart) {
            $this->cart = Cart::getCart();
        }
    }


    /**
     * Get cart.
     *
     * @return \Stegback\Checkout\Interface\Cart
     */
    public function getCart()
    {
        if (!$this->cart) {
            $this->setCart();
        }

        return $this->cart;
    }
}
