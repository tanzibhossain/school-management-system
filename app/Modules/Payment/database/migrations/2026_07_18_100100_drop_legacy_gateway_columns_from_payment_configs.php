<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contract phase of the generic-gateway-store migration: carry any values still in
 * the legacy per-gateway columns into the encrypted `gateways` JSON store, then drop
 * those columns. Raw DB + Crypt so it does not depend on the model's cast state.
 */
return new class extends Migration
{
    private const LEGACY_COLUMNS = [
        'bkash_enabled', 'sslcommerz_enabled',
        'bkash_app_key', 'bkash_app_secret', 'bkash_username', 'bkash_password', 'bkash_base_url',
        'sslcommerz_store_id', 'sslcommerz_store_pass', 'sslcommerz_base_url',
        'bkash_fee_pct', 'sslcommerz_fee_pct',
    ];

    public function up(): void
    {
        if (Schema::hasColumn('payment_configs', 'bkash_app_key')) {
            foreach (DB::table('payment_configs')->get() as $row) {
                $store = $this->decodeStore($row->gateways ?? null);

                $this->merge($store, 'bkash', array_filter([
                    'app_key' => $this->dec($row->bkash_app_key ?? null),
                    'app_secret' => $this->dec($row->bkash_app_secret ?? null),
                    'username' => $this->dec($row->bkash_username ?? null),
                    'password' => $this->dec($row->bkash_password ?? null),
                    'base_url' => $row->bkash_base_url ?? null,
                ], fn ($v) => filled($v)), $row->bkash_enabled ?? false, $row->bkash_fee_pct ?? 0);

                $this->merge($store, 'sslcommerz', array_filter([
                    'store_id' => $this->dec($row->sslcommerz_store_id ?? null),
                    'store_pass' => $this->dec($row->sslcommerz_store_pass ?? null),
                    'base_url' => $row->sslcommerz_base_url ?? null,
                ], fn ($v) => filled($v)), $row->sslcommerz_enabled ?? false, $row->sslcommerz_fee_pct ?? 0);

                if ($store !== []) {
                    DB::table('payment_configs')->where('id', $row->id)
                        ->update(['gateways' => Crypt::encryptString(json_encode($store))]);
                }
            }
        }

        Schema::table('payment_configs', function (Blueprint $table): void {
            $table->dropColumn(self::LEGACY_COLUMNS);
        });
    }

    public function down(): void
    {
        Schema::table('payment_configs', function (Blueprint $table): void {
            $table->boolean('bkash_enabled')->default(false);
            $table->boolean('sslcommerz_enabled')->default(false);
            $table->text('bkash_app_key')->nullable();
            $table->text('bkash_app_secret')->nullable();
            $table->text('bkash_username')->nullable();
            $table->text('bkash_password')->nullable();
            $table->string('bkash_base_url')->nullable();
            $table->text('sslcommerz_store_id')->nullable();
            $table->text('sslcommerz_store_pass')->nullable();
            $table->string('sslcommerz_base_url')->nullable();
            $table->decimal('bkash_fee_pct', 5, 2)->default(0);
            $table->decimal('sslcommerz_fee_pct', 5, 2)->default(0);
        });
    }

    private function decodeStore(?string $raw): array
    {
        if (! filled($raw)) {
            return [];
        }
        try {
            return json_decode(Crypt::decryptString($raw), true) ?: [];
        } catch (Throwable) {
            return [];
        }
    }

    private function dec(mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            return null;
        }
    }

    /** Only backfill a slug the JSON store doesn't already own. */
    private function merge(array &$store, string $slug, array $creds, mixed $enabled, mixed $feePct): void
    {
        if (isset($store[$slug]) || ($creds === [] && ! $enabled)) {
            return;
        }

        $store[$slug] = [
            'enabled' => (bool) $enabled,
            'fee_pct' => (float) $feePct,
            'credentials' => $creds,
        ];
    }
};
