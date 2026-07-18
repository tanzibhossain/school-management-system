<?php

/**
 * Central registry of online payment gateways and which are available per country.
 *
 * `gateways`   — every gateway the app knows: display label, supported currencies,
 *                whether a driver is `implemented` yet, and its credential fields
 *                (secret fields are stored encrypted; blank on edit keeps the saved
 *                value). Credentials live generically under the gateway slug in the
 *                encrypted `payment_configs.gateways` JSON — adding a gateway needs
 *                NO migration, just an entry here + a driver class.
 * `by_country` — which gateways a school may use, keyed by ISO-2 country code.
 * `default`    — gateways offered to any country not listed above.
 *
 * The Payment settings screen and the family checkout both read this — never
 * hardcode a gateway choice elsewhere. Only `implemented` gateways are offered.
 */
return [
    'gateways' => [
        'bkash' => [
            'label'       => 'bKash',
            'icon'        => 'bi-phone',
            'currencies'  => ['BDT'],
            'implemented' => true,
            'fields'      => [
                'app_key'    => ['label' => 'App key', 'secret' => false, 'required' => true],
                'app_secret' => ['label' => 'App secret', 'secret' => true, 'required' => true],
                'username'   => ['label' => 'Username', 'secret' => false, 'required' => true],
                'password'   => ['label' => 'Password', 'secret' => true, 'required' => true],
                'base_url'   => ['label' => 'Base URL', 'secret' => false, 'required' => false],
            ],
        ],
        'sslcommerz' => [
            'label'       => 'SSLCommerz',
            'icon'        => 'bi-credit-card-2-front',
            'currencies'  => ['BDT'],
            'implemented' => true,
            'fields'      => [
                'store_id'   => ['label' => 'Store ID', 'secret' => false, 'required' => true],
                'store_pass' => ['label' => 'Store password', 'secret' => true, 'required' => true],
                'base_url'   => ['label' => 'Base URL', 'secret' => false, 'required' => false],
            ],
        ],

        // ── International gateways — defined but not yet implemented. Add a driver
        //    class and flip `implemented` to true; no migration needed. ──────────
        'stripe' => [
            'label'       => 'Stripe',
            'icon'        => 'bi-stripe',
            'currencies'  => ['USD', 'EUR', 'GBP', 'AUD', 'CAD', 'SGD', 'INR', 'AED', 'JPY', 'NZD'],
            'implemented' => true,
            'fields'      => [
                // Hosted Checkout needs only the secret key; the publishable key is
                // for client-side SDKs and the webhook secret for async confirmation.
                'secret_key'      => ['label' => 'Secret key', 'secret' => true, 'required' => true],
                'publishable_key' => ['label' => 'Publishable key', 'secret' => false, 'required' => false],
                'webhook_secret'  => ['label' => 'Webhook signing secret', 'secret' => true, 'required' => false],
            ],
        ],
        'paypal' => [
            'label'       => 'PayPal',
            'icon'        => 'bi-paypal',
            'currencies'  => ['USD', 'EUR', 'GBP', 'AUD', 'CAD', 'SGD', 'JPY', 'NZD'],
            'implemented' => false,
            'fields'      => [
                'client_id'     => ['label' => 'Client ID', 'secret' => false, 'required' => true],
                'client_secret' => ['label' => 'Client secret', 'secret' => true, 'required' => true],
                'mode'          => ['label' => 'Mode (sandbox / live)', 'secret' => false, 'required' => false],
            ],
        ],
    ],

    // Country → gateways. Bangladesh uses the local rails; everywhere else the
    // international gateways (extend this map as more markets are onboarded).
    'by_country' => [
        'BD' => ['bkash', 'sslcommerz'],
    ],

    'default' => ['stripe', 'paypal'],
];
