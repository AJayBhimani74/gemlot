@extends('layouts.app')

@section('title', $stone->sku)

@section('content')
    <p><a href="{{ route('catalogue.index') }}">&larr; Back to catalogue</a></p>

    <div class="card detail">
        <div class="photo">
            @if ($stone->image_url)
                <img src="{{ $stone->image_url }}" alt="{{ $stone->shape }} {{ $stone->carat }}ct">
            @else
                <div class="thumb"></div>
            @endif
        </div>

        <div>
            <h1>{{ $stone->carat }} ct {{ $stone->shape }}</h1>
            <p class="muted">{{ $stone->sku }}</p>

            <table>
                <tr><th>Colour</th><td>{{ $stone->color }}</td></tr>
                <tr><th>Clarity</th><td>{{ $stone->clarity }}</td></tr>
                <tr><th>Cut</th><td>{{ $stone->cut ?? '-' }}</td></tr>
                <tr><th>Lab</th><td>{{ $stone->lab ?? '-' }} {{ $stone->certificate_no }}</td></tr>
                <tr><th>Price / ct</th><td>${{ number_format($stone->price_per_carat, 2) }}</td></tr>
                <tr><th>Total</th><td><strong>${{ number_format($stone->total_price, 2) }}</strong></td></tr>
                <tr><th>Seller</th><td>{{ $stone->seller->company_name }} ({{ $stone->seller->country }})</td></tr>
            </table>

            @if ($stone->notes)
                <p>{{ $stone->notes }}</p>
            @endif
        </div>
    </div>
@endsection
