<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::query()
            ->with(['stone', 'buyer:id,company_name', 'seller:id,company_name'])
            ->visibleTo($request->user())
            ->latest()
            ->paginate(20);

        return OrderResource::collection($orders);
    }

    public function show(Order $order): OrderResource
    {
        Gate::authorize('view', $order);

        return new OrderResource($order->load(['stone', 'buyer:id,company_name', 'seller:id,company_name']));
    }

    public function transition(Order $order, string $action, OrderService $orders)
    {
        Gate::authorize('transition', [$order, $action]);

        try {
            $order = $orders->transition($order, $action);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return new OrderResource($order->load(['stone', 'buyer:id,company_name', 'seller:id,company_name']));
    }
}
