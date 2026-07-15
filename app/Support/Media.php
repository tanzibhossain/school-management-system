<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Resolve a stored media value to a browser URL. Accepts either an already-absolute
 * URL / root-relative path (returned as-is) or a path stored on the "public" disk
 * (e.g. "site/logo.png"), which is resolved via Storage. Keeps views simple and
 * portable across local, public-disk, and S3/MinIO storage.
 */
class Media
{
    public static function url(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://', '//', '/'])) {
            return $value;
        }

        return Storage::disk('public')->url($value);
    }
}
