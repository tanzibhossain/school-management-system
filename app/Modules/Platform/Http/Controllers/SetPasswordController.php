<?php

namespace App\Modules\Platform\Http\Controllers;

use App\Models\User;
use App\Modules\Platform\Http\Requests\SetPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;

class SetPasswordController extends Controller
{
    /**
     * POST /v2/platform/set-password — public, reached via the signed URL emailed
     * by SchoolProvisioningService. SetPasswordRequest::authorize() already checked
     * hasValidSignature() before this method runs.
     *
     * NOTE: deliberately only ONE Request-family parameter here — FormRequest
     * extends Illuminate\Http\Request, and Laravel's controller dependency
     * resolver treats a type-hinted `Request $x` as already satisfied whenever a
     * FormRequest subclass is already present earlier in the signature, silently
     * skipping it. A second `Request $rawRequest` param here would never actually
     * be passed (ArgumentCountError at call time) — use $request itself instead,
     * since it inherits every Request method (query(), etc.).
     */
    public function store(SetPasswordRequest $request): JsonResponse
    {
        $user = User::findOrFail($request->query('user'));

        $user->update(['password' => Hash::make($request->validated('password'))]);
        $user->tokens()->delete();

        return response()->json(['message' => 'Password set. You can now log in.']);
    }
}
