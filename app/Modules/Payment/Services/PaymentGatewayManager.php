<?php

namespace App\Modules\Payment\Services;

use App\Modules\Payment\Gateways\BkashGateway;
use App\Modules\Payment\Gateways\PayPalGateway;
use App\Modules\Payment\Gateways\SslcommerzGateway;
use App\Modules\Payment\Gateways\StripeGateway;
use App\Modules\Payment\Models\PaymentConfig;
use RuntimeException;

/**
 * Resolves a gateway driver instance from a slug — the single place that maps a
 * gateway slug to its driver class. This is the seed of the pluggable
 * `PaymentGateway` contract (see docs/payment-gateway-architecture.md): as drivers
 * adopt a shared interface, callers depend on the manager rather than newing up a
 * concrete gateway.
 */
class PaymentGatewayManager
{
    /** @var array<string, class-string> slug => driver class */
    private const DRIVERS = [
        'bkash' => BkashGateway::class,
        'sslcommerz' => SslcommerzGateway::class,
        'stripe' => StripeGateway::class,
        'paypal' => PayPalGateway::class,
    ];

    public function supports(string $slug): bool
    {
        return isset(self::DRIVERS[$slug]);
    }

    /** @return list<string> */
    public function slugs(): array
    {
        return array_keys(self::DRIVERS);
    }

    /**
     * Build the driver for a slug, bound to a school's payment config.
     *
     * @return BkashGateway|SslcommerzGateway|StripeGateway|PayPalGateway
     */
    public function driver(string $slug, PaymentConfig $config): object
    {
        $class = self::DRIVERS[$slug] ?? null;

        if ($class === null) {
            throw new RuntimeException("No payment driver registered for gateway [{$slug}].");
        }

        return new $class($config);
    }
}
