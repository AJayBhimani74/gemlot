<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $orders = Order::query()
            ->with(['stone', 'buyer:id,company_name', 'seller:id,company_name'])
            ->when(! $user->hasRole('admin'), fn ($q) => $q->where(
                fn ($w) => $w->where('buyer_id', $user->id)->orWhere('seller_id', $user->id)
            ))
            ->latest()
            ->paginate(15);

        return view('orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order)
    {
        Gate::authorize('view', $order);

        $order->load(['stone', 'buyer', 'seller']);

        return view('orders.show', [
            'order' => $order,
            'actions' => $order->actionsFor($request->user()),
        ]);
    }

    public function transition(Order $order, string $action, OrderService $orders): RedirectResponse
    {
        Gate::authorize('transition', [$order, $action]);

        try {
            $orders->transition($order, $action);
        } catch (DomainException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('status', 'Order updated.');
    }
}
