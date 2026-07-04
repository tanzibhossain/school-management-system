<?php

namespace App\Modules\Platform\Http\Controllers;

use App\Modules\Platform\Http\Requests\StoreCheckoutRequest;
use App\Modules\Platform\Services\SelfServeSignupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class CheckoutController extends Controller
{
    public function __construct(private readonly SelfServeSignupService $service) {}

    /**
     * POST /v2/platform/signup/checkout — public. Starts a Stripe Checkout session
     * for a paid plan; the school itself isn't created until the webhook confirms
     * payment (see StripeWebhookController).
     */
    public function store(StoreCheckoutRequest $request): JsonResponse
    {
        $result = $this->service->startPaidCheckout($request->validated());

        return response()->json($result, 201);
    }
}
