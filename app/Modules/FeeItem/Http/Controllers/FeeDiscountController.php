<?php

namespace App\Modules\FeeItem\Http\Controllers;

use App\Modules\FeeItem\Http\Requests\StoreFeeDiscountRequest;
use App\Modules\FeeItem\Http\Resources\FeeDiscountResource;
use App\Modules\FeeItem\Models\FeeDiscount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class FeeDiscountController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $discounts = FeeDiscount::where('school_id', app('current_school_id'))
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get();

        return FeeDiscountResource::collection($discounts);
    }

    public function store(StoreFeeDiscountRequest $request): JsonResponse
    {
        $discount = FeeDiscount::create(array_merge(
            $request->validated(),
            ['school_id' => app('current_school_id')],
        ));

        return (new FeeDiscountResource($discount))->response()->setStatusCode(201);
    }

    public function update(StoreFeeDiscountRequest $request, int $id): FeeDiscountResource
    {
        $discount = FeeDiscount::where('school_id', app('current_school_id'))->findOrFail($id);
        $discount->update($request->validated());

        return new FeeDiscountResource($discount->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $discount = FeeDiscount::where('school_id', app('current_school_id'))->findOrFail($id);
        $discount->delete();

        return response()->json(['message' => 'Discount deleted.']);
    }
}
