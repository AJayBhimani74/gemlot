@extends('layouts.app')

@section('title', $order->reference)

@section('content')
    <p><a href="{{ route('orders.index') }}">&larr; All orders</a></p>

    <div class="card">
        <div class="head">
            <h1>{{ $order->reference }}</h1>
            <span class="badge">{{ $order->status }}</span>
        </div>

        <table>
            <tr><th>Stone</th><td>{{ $order->stone->sku }} &middot; {{ $order->stone->carat }} ct {{ $order->stone->shape }}</td></tr>
            <tr><th>Agreed price</th><td><strong>${{ number_format($order->agreed_price, 2) }}</strong></td></tr>
            <tr><th>Buyer</th><td>{{ $order->buyer->company_name }}</td></tr>
            <tr><th>Seller</th><td>{{ $order->seller->company_name }}</td></tr>
            <tr><th>Created</th><td>{{ $order->created_at->format('d M Y H:i') }}</td></tr>
            @foreach (['confirmed_at' => 'Confirmed', 'shipped_at' => 'Shipped', 'completed_at' => 'Completed'] as $field => $label)
                @if ($order->$field)
                    <tr><th>{{ $label }}</th><td>{{ $order->$field->format('d M Y H:i') }}</td></tr>
                @endif
            @endforeach
        </table>

        @if ($actions)
            <div class="actions-bar">
                @foreach ($actions as $action)
                    <form method="POST" action="{{ route('orders.transition', [$order, $action]) }}">
                        @csrf
                        <button class="btn {{ $action === 'cancel' ? 'btn-danger' : '' }}" type="submit">{{ ucfirst($action) }}</button>
                    </form>
                @endforeach
            </div>
        @endif
    </div>
@endsection
