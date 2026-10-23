<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Gemlot') &middot; Gemlot</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header class="nav">
        <a class="logo" href="{{ route('home') }}">Gemlot</a>
        <nav class="links">
            @auth
                <a href="{{ route('dashboard') }}">Dashboard</a>
                @if (auth()->user()->isSeller() && auth()->user()->is_approved)
                    <a href="{{ route('seller.stones.index') }}">My stones</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="link-btn" type="submit">Log out</button>
                </form>
            @else
                <a href="{{ route('login') }}">Log in</a>
                <a href="{{ route('register') }}">Register</a>
            @endauth
        </nav>
    </header>

    <main class="container">
        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif

        @yield('content')
    </main>
</body>
</html>
