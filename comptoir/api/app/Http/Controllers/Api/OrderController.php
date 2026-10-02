<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    /**
     * GET /api/orders
     * Filtres optionnels : ?customer_id=42&status=pending
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = Order::query()
            ->when($request->filled('customer_id'), function ($query) use ($request) {
                $query->where('customer_id', $request->integer('customer_id'));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status'));
            })
            ->orderByDesc('placed_at')
            ->paginate(50);

        return OrderResource::collection($orders);
    }

    /**
     * POST /api/orders
     */
    public function store(StoreOrderRequest $request, OrderService $orders): JsonResponse
    {
        $order = $orders->place($request->validated());

        return OrderResource::make($order->load(['customer', 'lines.product']))
            ->response()
            ->setStatusCode(201);
    }
}
