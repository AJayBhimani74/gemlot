@extends('layouts.app')

@section('content')
    <div class="card">
        <h1>Wholesale gemstones, direct from verified sellers</h1>
        <p class="muted">Gemlot connects trade buyers with sellers. Browse certified stones, send inquiries and track orders in one place.</p>
        <p><a href="{{ route('register') }}">Create a trade account</a></p>
    </div>
@endsection
