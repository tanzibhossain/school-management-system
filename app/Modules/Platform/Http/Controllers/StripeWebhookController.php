<?php

namespace App\Modules\Platform\Http\Controllers;

use App\Modules\Platform\Services\StripeWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class StripeWebhookController extends Controller
{
    public function __construct(private readonly StripeWebhookService $service) {}

    /**
     * POST /v2/platform/webhooks/stripe — public, no auth (Stripe itself calls
     * this). Signature verification (inside the service) is what actually gates
     * trust here, not Sanctum.
     */
    public function handle(Request $request): JsonResponse
    {
        $this->service->handle($request->getContent(), $request->header('Stripe-Signature'));

        return response()->json(['received' => true]);
    }
}
