@extends('layouts.app')

@section('title', 'Register')

@section('content')
    <div class="card narrow">
        <h1>Create an account</h1>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <label for="role">I want to</label>
            <select id="role" name="role">
                <option value="buyer" @selected(old('role') === 'buyer')>Buy stones</option>
                <option value="seller" @selected(old('role') === 'seller')>Sell stones</option>
            </select>
            @error('role') <div class="error">{{ $message }}</div> @enderror

            <label for="name">Full name</label>
            <input id="name" name="name" value="{{ old('name') }}" required>
            @error('name') <div class="error">{{ $message }}</div> @enderror

            <label for="company_name">Company</label>
            <input id="company_name" name="company_name" value="{{ old('company_name') }}" required>
            @error('company_name') <div class="error">{{ $message }}</div> @enderror

            <div class="row">
                <div>
                    <label for="country">Country (2-letter code)</label>
                    <input id="country" name="country" maxlength="2" value="{{ old('country') }}" required>
                    @error('country') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label for="phone">Phone</label>
                    <input id="phone" name="phone" value="{{ old('phone') }}">
                    @error('phone') <div class="error">{{ $message }}</div> @enderror
                </div>
            </div>

            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required>
            @error('email') <div class="error">{{ $message }}</div> @enderror

            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>
            @error('password') <div class="error">{{ $message }}</div> @enderror

            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required>

            <button class="btn" type="submit">Register</button>
        </form>
    </div>
@endsection
