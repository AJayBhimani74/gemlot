@extends('layouts.app')

@section('title', 'Orders')

@section('content')
    <div class="card">
        <h1>Orders</h1>

        @if ($orders->isEmpty())
            <p class="muted">No orders yet.</p>
        @else
            <table>
                <thead>
                    <tr><th>Reference</th><th>Stone</th><th>Buyer</th><th>Seller</th><th>Price</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td><a href="{{ route('orders.show', $order) }}">{{ $order->reference }}</a></td>
                            <td>{{ $order->stone->sku }}</td>
                            <td>{{ $order->buyer->company_name }}</td>
                            <td>{{ $order->seller->company_name }}</td>
                            <td>${{ number_format($order->agreed_price, 2) }}</td>
                            <td><span class="badge">{{ $order->status }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{ $orders->links() }}
        @endif
    </div>
@endsection
