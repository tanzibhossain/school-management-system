<?php

namespace App\Modules\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/** Public endpoint reached via a signed, expiring URL — no login required. */
class SetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The signature itself IS the authorization — anyone holding a valid,
        // unexpired signed URL for this specific user is presumed to be them
        // (the link was only ever sent to their own email).
        if (! $this->hasValidSignature()) {
            throw new AccessDeniedHttpException('This link is invalid or has expired.');
        }

        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
