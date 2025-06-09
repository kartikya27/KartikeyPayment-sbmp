<?php

namespace Kartikey\Payment;

use Kartikey\Sales\Models\Order;
use Kartikey\Sales\Models\OrderPayment;
use Kartikey\Sales\Repository\OrderPaymentRepository;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Kartikey\Core\Models\PaymentGateway;
use Kartikey\Payment\Repository\PaymentRepository;

class Payment
{
    const MODE = 1;
    const STATUS = 1;
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(
        protected PaymentRepository $paymentRepository,
    ) {
    }

    /**
     * Returns all supported payment methods
     *
     * @return array
     */
    public function getSupportedPaymentMethods()
    {
        return [
            'payment_methods'  => $this->getPaymentMethods(),
        ];
    }

    public function getPaymentMethods()
    {
        $listedPaymentGateway = $this->paymentRepository->where('status', 1)->get();
        $paymentMethods = [];

        foreach ($listedPaymentGateway as $paymentMethodConfig) {

            $paymentMethod = app(Config::get('payment_methods.'.$paymentMethodConfig->method.'.class'));

            if ($paymentMethod->isAvailable()) {
                $paymentMethods[] = [
                    'id'           => $paymentMethodConfig->id,
                    'method'       => $paymentMethod->getCode(),
                    'method_title' => $paymentMethodConfig->app_name,
                    'payment_status' => 'on-hold',
                ];
            }
        }

        return $paymentMethods;
    }


    /**
     * Returns payment redirect url if have any
     *
     * @param  \Stegback\Checkout\Contracts\Cart  $cart
     * @return string
     */
    public function getRedirectUrl($cart)
    {
        $payment = app(Config::get('payment_methods.'.$cart->payment->method.'.class'));

        return $payment->getRedirectUrl();
    }

    public static function validatePayment($cart, $paymentGateway, $requestData)
    {
        switch ($paymentGateway) {
            case 'stripe':
                return self::validateStripePayment($cart, $requestData);
            case 'cod': // Cash on Delivery
                return true; // COD requires no additional validation
            default:
                throw new \Exception("Unsupported payment gateway: $paymentGateway");
        }
    }

    private static function validateStripePayment($cart, $requestData)
    {
        if (!isset($requestData['session_id'])) {
            return false; // Missing session ID
        }
     
        \Stripe\Stripe::setApiKey(
            env('APP_ENV') === 'production' 
                ? env('STRIPE_LIVE_KEY') 
                : env('STRIPE_TEST_KEY')
        );

        try {
            // Retrieve session from Stripe
            $session = \Stripe\Checkout\Session::retrieve($requestData['session_id']);

            $CustomerDetails = [
                'Address' => [
                    'line1' => $session->customer_details?->address?->line1 ?? null,
                    'line2' => $session->customer_details?->address?->line2 ?? null,
                    'city' => $session->customer_details?->address?->city ?? null,
                    'state' => $session->customer_details?->address?->state ?? null,
                    'postal_code' => $session->customer_details?->address?->postal_code ?? null,
                    'country' => $session->customer_details?->address?->country ?? null,
                ],
                'Email' => $session->customer_details?->email ?? null,
                'Name' => $session->customer_details?->name ?? null,
                'Phone' => $session->customer_details?->phone ?? null,
                'TaxExempt' => $session->customer_details?->tax_exempt ?? 'none',
                'TaxIds' => $session->customer_details?->tax_ids ?? [],
            ];

            $PaymentDetails = [
                'PaymentId' => $session->payment_intent, // The payment intent ID
                'AmountSubtotal' => $session->amount_subtotal, // Total amount excluding taxes and shipping (in cents)
                'AmountTotal' => $session->amount_total, // Total amount including taxes and shipping (in cents)
                'Currency' => $session->currency, // Currency used for the payment
                'PaymentStatus' => $session->payment_status, // Payment status (e.g., 'paid')
                'SessionId' => $session->id, // Checkout session ID
                'SuccessUrl' => $session->success_url, // Redirect URL after successful payment
                'CancelUrl' => $session->cancel_url, // Redirect URL after canceled payment
                'ShippingCost' => $session->shipping_cost?->amount_total ?? 0, // Shipping cost (if available)
                'CustomerEmail' => $session->customer_details?->email ?? null, // Customer email (if available)
                'Created' => $session->created, // Payment session creation timestamp
                'ExpiresAt' => $session->expires_at,
                'Customer' => $CustomerDetails
            ];
            $cartTotal = intval($cart['grand_total']*100);
            
            // Verify payment amount matches cart total
            if ($session->payment_status === 'paid' && $session->amount_total === $cartTotal) {
                // Store transaction details
                $transactionDetails = [
                    'payment_id' => $session->payment_intent,
                    'payment_status' => Order::STATUS_PAYMENT_RECEIVED,
                    'method' => 'stripe',
                    'method_title' => $session->payment_method_types[0],
                    'additional' => [
                        'payment_details' => $PaymentDetails,
                        'customer_details' => $CustomerDetails,
                        'shipping' => [
                            'cost' => $session->shipping_cost['amount_total'] / 100,
                            'rate_id' => $session->shipping_cost['shipping_rate']
                        ],
                        'amount_total' => $session->amount_total / 100,
                        'currency' => $session->currency,
                        'session_id' => $session->id
                    ]
                ];
                // Save transaction details to the database
                // $orderPayment = OrderPayment::create($transactionDetails);
            }
            else{
                $transactionDetails = [
                    'payment_status' => Order::STATUS_PAYMENT_FAILED,
                    'additional' => [
                        'payment_details' => $PaymentDetails,
                        'payment_status' => $session->payment_status,
                        'customer_details' => $CustomerDetails,
                        'shipping' => [
                            'cost' => $session->shipping_cost['amount_total'] / 100,
                            'rate_id' => $session->shipping_cost['shipping_rate']
                        ],
                        'amount_total' => $session->amount_total / 100,
                        'currency' => $session->currency,
                        'session_id' => $session->id
                    ]
                ];
            }
            return $transactionDetails; // Payment is valid

            Log::warning("Payment validation failed: Amount mismatch or payment not completed", [
                'expected_amount' => $cartTotal,
                'received_amount' => $session->amount_total,
                'payment_status' => $session->payment_status
            ]);
        } catch (\Exception $e) {
            Log::error("Stripe validation error: " . $e->getMessage());
            return false;
        }

        return false;
    }
}
