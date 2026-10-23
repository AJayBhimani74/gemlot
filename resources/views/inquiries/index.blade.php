@extends('layouts.app')

@section('title', 'Inquiries')

@section('content')
    <div class="card">
        <h1>Inquiries</h1>

        @if ($inquiries->isEmpty())
            <p class="muted">Nothing here yet.</p>
        @else
            <table>
                <thead>
                    <tr><th>Stone</th><th>{{ auth()->user()->isSeller() ? 'Buyer' : 'Asking' }}</th><th>Offer</th><th>Message</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($inquiries as $inquiry)
                        <tr>
                            <td>{{ $inquiry->stone->sku }}<br><span class="muted">{{ $inquiry->stone->carat }} ct {{ $inquiry->stone->shape }}</span></td>
                            <td>
                                @if (auth()->user()->isSeller())
                                    {{ $inquiry->buyer->company_name }}
                                @else
                                    ${{ number_format($inquiry->stone->total_price, 2) }}
                                @endif
                            </td>
                            <td>{{ $inquiry->offered_price ? '$'.number_format($inquiry->offered_price, 2) : '-' }}</td>
                            <td>{{ Str::limit($inquiry->message, 60) }}</td>
                            <td><span class="badge">{{ $inquiry->status }}</span></td>
                            <td class="actions">
                                @can('respond', $inquiry)
                                    <form method="POST" action="{{ route('inquiries.accept', $inquiry) }}">@csrf<button class="link-btn">Accept</button></form>
                                    <form method="POST" action="{{ route('inquiries.decline', $inquiry) }}">@csrf<button class="link-btn">Decline</button></form>
                                @endcan
                                @can('withdraw', $inquiry)
                                    <form method="POST" action="{{ route('inquiries.withdraw', $inquiry) }}">@csrf<button class="link-btn">Withdraw</button></form>
                                @endcan
                                @if ($inquiry->order)
                                    <a href="{{ route('orders.show', $inquiry->order) }}">View order</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{ $inquiries->links() }}
        @endif
    </div>
@endsection
