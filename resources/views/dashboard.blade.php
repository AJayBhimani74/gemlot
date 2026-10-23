@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="card">
        <h1>Welcome, {{ $user->name }}</h1>
        <p class="muted">
            {{ $user->company_name }} &middot; <span class="badge">{{ ucfirst($user->role) }}</span>
        </p>

        @if ($user->isSeller() && ! $user->is_approved)
            <p>Your seller account is waiting for approval. You will be able to list stones once it has been reviewed.</p>
        @endif
    </div>
@endsection
