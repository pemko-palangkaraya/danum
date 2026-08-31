<header class="nav">
    <div class="logo">DANUM</div>
    <div class="nav-actions">
        @auth
            <a class="btn btn-secondary" href="{{ route('dashboard') }}">Dashboard</a>
            <a class="btn btn-primary" href="{{ route('dashboard') }}">Masuk ke Aplikasi</a>
        @else
            <a class="btn btn-primary" href="{{ route('login') }}">Masuk</a>
        @endauth
    </div>
</header>