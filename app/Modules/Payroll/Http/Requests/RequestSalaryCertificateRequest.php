<?php

namespace App\Modules\Payroll\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Self-service — any staff-linked login can request their OWN certificate; ownership is resolved in the controller. */
class RequestSalaryCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        foreach (['admin:*', 'accountant:*', 'teacher:*', 'staff:*', 'librarian:*', 'receptionist:*'] as $ability) {
            if ($user->tokenCan($ability)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'purpose' => ['required', 'string', 'max:500'],
        ];
    }
}
