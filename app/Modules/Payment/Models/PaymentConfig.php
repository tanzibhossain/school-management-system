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
        'bounce_fee_amount',
    ];

    protected $casts = [
        // Generic gateway store: { slug: { enabled, fee_pct, credentials: {} } }
        'gateways' => 'encrypted:array',
        'invoice_last_seq' => 'integer',
        'receipt_last_seq' => 'integer',
        'bounce_fee_amount' => 'decimal:2',
    ];

    /** @var list<string> */
    protected $hidden = ['gateways'];

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
        return (bool) (($this->gateways ?? [])[$slug]['enabled'] ?? false);
    }

    public function gatewayMode(string $slug): ?string
    {
        return ($this->gateways ?? [])[$slug]['mode'] ?? null;
    }

    /** A single credential value for a gateway from the generic store. */
    public function credential(string $slug, string $key): ?string
    {
        $value = ($this->gateways ?? [])[$slug]['credentials'][$key] ?? null;

        return filled($value) ? (string) $value : null;
    }

    /** Refund processing-fee percentage for a gateway (0 when unset). */
    public function feePct(string $slug): float
    {
        $stored = ($this->gateways ?? [])[$slug]['fee_pct'] ?? null;

        return ($stored !== null && $stored !== '') ? (float) $stored : 0.0;
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
