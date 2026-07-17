<?php

namespace App\Modules\Payment\Models;

use App\Modules\School\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentConfig extends Model
{
    protected $fillable = [
        'school_id',
        'payment_mode', 'gateways',
        'invoice_prefix', 'invoice_last_seq',
        'receipt_prefix', 'receipt_last_seq',
        'bkash_fee_pct', 'sslcommerz_fee_pct', 'bounce_fee_amount',
        // Legacy per-gateway columns — kept as a read fallback and for the API.
        'bkash_enabled', 'sslcommerz_enabled',
        'bkash_app_key', 'bkash_app_secret', 'bkash_username', 'bkash_password', 'bkash_base_url',
        'sslcommerz_store_id', 'sslcommerz_store_pass', 'sslcommerz_base_url',
    ];

    protected $casts = [
        // Generic gateway store: { slug: { enabled, mode, credentials: {} } }
        'gateways'             => 'encrypted:array',
        'bkash_enabled'        => 'boolean',
        'sslcommerz_enabled'   => 'boolean',
        'invoice_last_seq'     => 'integer',
        'receipt_last_seq'     => 'integer',
        'bkash_fee_pct'        => 'decimal:2',
        'sslcommerz_fee_pct'   => 'decimal:2',
        'bounce_fee_amount'    => 'decimal:2',
        // Legacy per-gateway credentials stored encrypted
        'bkash_app_key'        => 'encrypted',
        'bkash_app_secret'     => 'encrypted',
        'bkash_username'       => 'encrypted',
        'bkash_password'       => 'encrypted',
        'sslcommerz_store_id'  => 'encrypted',
        'sslcommerz_store_pass' => 'encrypted',
    ];

    /** @var list<string> */
    protected $hidden = [
        'gateways',
        'bkash_app_key', 'bkash_app_secret', 'bkash_username', 'bkash_password',
        'sslcommerz_store_id', 'sslcommerz_store_pass',
    ];

    /** @return BelongsTo<School, PaymentConfig> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function onlineEnabled(): bool
    {
        return in_array($this->payment_mode, ['online', 'both'], true);
    }

    public function offlineEnabled(): bool
    {
        return in_array($this->payment_mode, ['offline', 'both'], true);
    }

    // ── Generic gateway access (JSON store first, legacy column fallback) ──────

    public function gatewayEnabled(string $slug): bool
    {
        $store = ($this->gateways ?? [])[$slug] ?? null;
        if (is_array($store) && array_key_exists('enabled', $store)) {
            return (bool) $store['enabled'];
        }

        return (bool) $this->getAttribute("{$slug}_enabled"); // legacy
    }

    public function gatewayMode(string $slug): ?string
    {
        return ($this->gateways ?? [])[$slug]['mode'] ?? null;
    }

    /** A single credential value for a gateway (JSON first, then legacy column). */
    public function credential(string $slug, string $key): ?string
    {
        $value = ($this->gateways ?? [])[$slug]['credentials'][$key] ?? null;
        if (filled($value)) {
            return $value;
        }

        // Legacy column mapping is "{slug}_{key}" (e.g. bkash_app_key).
        return $this->getAttribute("{$slug}_{$key}");
    }

    // ── Registry-driven availability (country) ────────────────────────────────

    /** Gateway keys a school in the given country may use. */
    public static function availableKeysFor(?string $country): array
    {
        $map = config('payment_gateways.by_country', []);

        return $map[strtoupper((string) $country)] ?? config('payment_gateways.default', []);
    }

    /**
     * Gateway definitions available to this config's school — filtered to those
     * with an implemented driver.
     *
     * @return array<string, array<string, mixed>>
     */
    public function availableGatewayDefs(): array
    {
        $country = $this->school?->country_code
            ?? School::whereKey($this->school_id)->value('country_code');

        $defs = config('payment_gateways.gateways', []);

        return collect(self::availableKeysFor($country))
            ->filter(fn ($key) => isset($defs[$key]) && ($defs[$key]['implemented'] ?? false))
            ->mapWithKeys(fn ($key) => [$key => $defs[$key]])
            ->all();
    }

    /**
     * Gateways ready for checkout — available in this country, enabled, and
     * holding all required credentials.
     *
     * @return list<array{key:string,label:string,icon:string}>
     */
    public function enabledGateways(): array
    {
        if (! $this->onlineEnabled()) {
            return [];
        }

        $out = [];
        foreach ($this->availableGatewayDefs() as $key => $def) {
            if (! $this->gatewayEnabled($key)) {
                continue;
            }
            $configured = true;
            foreach ($def['fields'] as $field => $meta) {
                if (! empty($meta['required']) && ! filled($this->credential($key, $field))) {
                    $configured = false;
                    break;
                }
            }
            if ($configured) {
                $out[] = ['key' => $key, 'label' => $def['label'], 'icon' => $def['icon'] ?? 'bi-credit-card'];
            }
        }

        return $out;
    }
}
