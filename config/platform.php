<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stripe (vendor billing — separate from Payment module's per-school
    | student-fee gateways)
    |--------------------------------------------------------------------------
    */
    'stripe' => [
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'api_base' => env('STRIPE_API_BASE', 'https://api.stripe.com/v1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Seed plans (placeholder pricing — editable via Super Admin afterwards,
    | never hardcoded into billing/limit logic). See CLAUDE.md's Platform
    | Module spec for the market-research reasoning behind these numbers.
    |--------------------------------------------------------------------------
    */
    'seed_plans' => [
        [
            'name' => 'Demo',
            'slug' => 'demo',
            'price_monthly' => null,
            'price_yearly' => null,
            'max_students' => 20,
            'max_staff' => 10,
            'trial_days' => null,
            'is_self_serve' => false,
            'sort_order' => 0,
        ],
        [
            'name' => 'Trial',
            'slug' => 'trial',
            'price_monthly' => null,
            'price_yearly' => null,
            'max_students' => 100,
            'max_staff' => 15,
            'trial_days' => 30,
            'is_self_serve' => true,
            'sort_order' => 1,
        ],
        [
            'name' => 'Basic',
            'slug' => 'basic',
            'price_monthly' => 19.00,
            'price_yearly' => 190.00,
            'max_students' => 500,
            'max_staff' => 40,
            'trial_days' => null,
            'is_self_serve' => true,
            'sort_order' => 2,
        ],
        [
            'name' => 'Pro',
            'slug' => 'pro',
            'price_monthly' => 49.00,
            'price_yearly' => 490.00,
            'max_students' => null,
            'max_staff' => null,
            'trial_days' => null,
            'is_self_serve' => true,
            'sort_order' => 3,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Demo school reset cadence
    |--------------------------------------------------------------------------
    | User explicitly overrode the DevPlan's 24h to 14h. Standard cron can't
    | express a true "every 14 hours" interval (24 isn't evenly divisible by
    | 14), so this runs at two fixed times a day (00:00 and 14:00) as the
    | closest practical approximation — see Platform\Console\ResetDemoSchool.
    */
    'demo_reset_cron' => '0 0,14 * * *',

    /*
    |--------------------------------------------------------------------------
    | Demo login credentials — deliberately fixed/public (the whole point of the
    | demo is that anyone can see and use them). NOT a real secret.
    |--------------------------------------------------------------------------
    */
    'demo_password' => env('PLATFORM_DEMO_PASSWORD', 'demo-password-1234'),

    /*
    |--------------------------------------------------------------------------
    | Subscription renewal reminders (days before subscription_expires_at)
    |--------------------------------------------------------------------------
    */
    'reminder_days' => [7, 1],
];
