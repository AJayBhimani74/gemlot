@extends('layouts.app')

@section('title', 'Sellers')

@section('content')
    <div class="card">
        <div class="head">
            <h1>Sellers</h1>
            <div class="links">
                @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'all' => 'All'] as $key => $label)
                    <a href="{{ route('admin.sellers.index', ['filter' => $key]) }}" @style(['font-weight:700' => $filter === $key])>{{ $label }}</a>
                @endforeach
            </div>
        </div>

        @if ($sellers->isEmpty())
            <p class="muted">No sellers in this view.</p>
        @else
            <table>
                <thead>
                    <tr><th>Company</th><th>Contact</th><th>Country</th><th>Stones</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($sellers as $seller)
                        <tr>
                            <td>{{ $seller->company_name }}</td>
                            <td>{{ $seller->name }}<br><span class="muted">{{ $seller->email }}</span></td>
                            <td>{{ $seller->country }}</td>
                            <td>{{ $seller->stones_count }}</td>
                            <td><span class="badge">{{ $seller->is_approved ? 'approved' : 'pending' }}</span></td>
                            <td class="actions">
                                <form method="POST" action="{{ route($seller->is_approved ? 'admin.sellers.revoke' : 'admin.sellers.approve', $seller) }}">
                                    @csrf
                                    <button class="link-btn" type="submit">{{ $seller->is_approved ? 'Revoke' : 'Approve' }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{ $sellers->links() }}
        @endif
    </div>
@endsection
