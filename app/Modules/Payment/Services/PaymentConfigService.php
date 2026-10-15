<?php

namespace App\Modules\Payment\Services;

use App\Modules\Payment\Models\PaymentConfig;
use Illuminate\Validation\ValidationException;

/**
 * Updates a school's PaymentConfig: plain settings plus the generic per-gateway
 * store (`gateways` JSON: slug => {enabled, credentials, fee_pct}). Shared by
 * the admin settings form and the v2 API, which used to each carry their own
 * copy of the gateway merge rules.
 */
class PaymentConfigService
{
    /**
     * Merge per-gateway updates into the stored gateway JSON (not saved yet).
     * A blank credential keeps the stored value — credentials are write-only and
     * never sent back to the form/API, so "left blank" means "unchanged".
     *
     * @param  array<string, array<string, mixed>>  $updates  slug => {enabled: ?bool (null = keep stored), credentials: array, fee_pct: mixed}
     */
    public function applyGatewayUpdates(PaymentConfig $config, array $updates): void
    {
        $store = $config->gateways ?? [];

        foreach ($updates as $slug => $gw) {
            $creds = $store[$slug]['credentials'] ?? [];
            foreach ($gw['credentials'] as $key => $value) {
                if (filled($value)) {
                    $creds[$key] = $value;
                }
            }

            $store[$slug] = [
                'enabled' => $gw['enabled'] ?? (bool) ($store[$slug]['enabled'] ?? false),
                'credentials' => $creds,
                'fee_pct' => filled($gw['fee_pct']) ? (float) $gw['fee_pct'] : ($store[$slug]['fee_pct'] ?? 0.0),
            ];
        }

        $config->gateways = $store;
    }

    /**
     * Admin Finance → Payment settings form. Every gateway available to the
     * school's country is posted (gw[slug][enabled|fee_pct|cred][field]); an
     * unchecked box means disabled.
     *
     * @param  array<string, mixed>  $settings  validated payment_mode / invoice_prefix / receipt_prefix / bounce_fee_amount
     * @param  array<string, mixed>  $gw  the raw `gw` input
     *
     * @throws ValidationException when an enabled gateway is missing a required credential
     */
    public function updateFromAdminForm(PaymentConfig $config, array $settings, array $gw): void
    {
        $updates = [];
        foreach ($config->availableGatewayDefs() as $slug => $def) {
            $updates[$slug] = [
                'enabled' => filter_var($gw[$slug]['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                // Only this gateway's defined fields — never arbitrary posted keys.
                'credentials' => array_intersect_key((array) ($gw[$slug]['cred'] ?? []), $def['fields']),
                'fee_pct' => $gw[$slug]['fee_pct'] ?? null,
            ];
        }

        if (in_array($settings['payment_mode'], ['online', 'both'], true)) {
            $this->assertEnabledGatewaysHaveCredentials($config, $updates);
        }

        // Non-gateway settings (never null a NOT-NULL column).
        $config->payment_mode = $settings['payment_mode'];
        foreach (['invoice_prefix', 'receipt_prefix', 'bounce_fee_amount'] as $field) {
            if (filled($settings[$field] ?? null)) {
                $config->{$field} = $settings[$field];
            }
        }

        $this->applyGatewayUpdates($config, $updates);
        $config->save();
    }

    /**
     * Enabling a gateway requires each of its required credentials — either
     * submitted now or already stored. Error keys match the admin form's
     * field names (gw.{slug}.cred.{field}).
     *
     * @param  array<string, array<string, mixed>>  $updates
     *
     * @throws ValidationException
     */
    private function assertEnabledGatewaysHaveCredentials(PaymentConfig $config, array $updates): void
    {
        $missing = [];
        foreach ($config->availableGatewayDefs() as $slug => $def) {
            if (! ($updates[$slug]['enabled'] ?? false)) {
                continue;
            }
            foreach ($def['fields'] as $field => $meta) {
                if (empty($meta['required'])) {
                    continue;
                }
                if (! filled($updates[$slug]['credentials'][$field] ?? null) && ! filled($config->credential($slug, $field))) {
                    $missing["gw.{$slug}.cred.{$field}"] = ["{$def['label']} {$meta['label']} is required to enable this gateway."];
                }
            }
        }

        if ($missing) {
            throw ValidationException::withMessages($missing);
        }
    }
}
