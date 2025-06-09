<?php

namespace Kartikey\Payment\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Kartikey\Payment\Facades\Payment as PaymentFacade;
use Kartikey\Payment\Payment;
use Kartikey\Payment\Repository\PaymentRepository;
use Kartikey\Sales\Repository\OrderPaymentRepository;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        include __DIR__ . '/../Http/helpers.php';

        // $this->app->register(EventServiceProvider::class);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerFacades();

        $this->registerConfig();
    }

    /**
     * Register Bouncer as a singleton.
     *
     * @return void
     */
    protected function registerFacades()
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('payment', PaymentFacade::class);

        $this->app->singleton('payment', function ($app) {
            return new Payment(
                $app->make(PaymentRepository::class)
            );
        });
    }

    /**
     * Register package config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/paymentmethods.php',
            'payment_methods'
        );
    }
}
