@extends('layouts.app')

@section('title', 'My stones')

@section('content')
    <div class="card">
        <div class="head">
            <h1>My stones</h1>
            <a class="btn" href="{{ route('seller.stones.create') }}">Add stone</a>
        </div>

        @if ($stones->isEmpty())
            <p class="muted">You have not listed any stones yet.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>SKU</th><th>Shape</th><th>Carat</th><th>Colour</th><th>Clarity</th>
                        <th>Total</th><th>Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stones as $stone)
                        <tr>
                            <td>{{ $stone->sku }}</td>
                            <td>{{ $stone->shape }}</td>
                            <td>{{ $stone->carat }}</td>
                            <td>{{ $stone->color }}</td>
                            <td>{{ $stone->clarity }}</td>
                            <td>${{ number_format($stone->total_price, 2) }}</td>
                            <td><span class="badge">{{ str_replace('_', ' ', $stone->status) }}</span></td>
                            <td class="actions">
                                <a href="{{ route('seller.stones.edit', $stone) }}">Edit</a>
                                @can('delete', $stone)
                                    <form method="POST" action="{{ route('seller.stones.destroy', $stone) }}" onsubmit="return confirm('Remove this stone?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="link-btn" type="submit">Delete</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{ $stones->links() }}
        @endif
    </div>
@endsection
