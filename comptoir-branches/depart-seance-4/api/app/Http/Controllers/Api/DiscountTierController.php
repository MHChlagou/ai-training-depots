<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDiscountTierRequest;
use App\Http\Resources\DiscountTierResource;
use App\Models\DiscountTier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DiscountTierController extends Controller
{
    /**
     * GET /api/discount-tiers : tous les paliers, par seuil croissant.
     */
    public function index(): AnonymousResourceCollection
    {
        return DiscountTierResource::collection(
            DiscountTier::query()->orderBy('min_subtotal_cents')->get()
        );
    }

    /**
     * POST /api/discount-tiers
     */
    public function store(StoreDiscountTierRequest $request): JsonResponse
    {
        $tier = DiscountTier::create([
            'label' => $request->validated('label'),
            'min_subtotal_cents' => $request->validated('min_subtotal_cents'),
            'rate_bp' => (int) round($request->validated('rate_percent') * 100),
            'active' => $request->validated('active', true),
        ]);

        return DiscountTierResource::make($tier)->response()->setStatusCode(201);
    }
}
