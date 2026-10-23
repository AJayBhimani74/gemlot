@extends('layouts.app')

@section('title', 'Log in')

@section('content')
    <div class="card narrow">
        <h1>Log in</h1>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email') <div class="error">{{ $message }}</div> @enderror

            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>

            <label><input type="checkbox" name="remember" style="width:auto"> Remember me</label>

            <button class="btn" type="submit">Log in</button>
        </form>
    </div>
@endsection
