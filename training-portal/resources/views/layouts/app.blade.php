<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Eğitim Yönetim Portalı') · Ardıç Otomotiv</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body>
    <aside class="sidebar">
        <a class="brand" href="{{ route('dashboard') }}" aria-label="Ardıç Otomotiv Eğitim Yönetim Portalı ana sayfası">
            <span class="brand-mark" aria-hidden="true">A</span>
            <span class="brand-copy">
                <span class="brand-name">Ardıç Otomotiv |</span>
                <span class="brand-product">Eğitim Yönetim Portalı</span>
            </span>
        </a>
        <p class="nav-caption">ÇALIŞMA ALANI</p>
        <nav class="navigation" aria-label="Ana menü">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <span class="nav-icon" aria-hidden="true">⌂</span> Genel Bakış
            </a>
            <a class="nav-link {{ request()->routeIs('trainings.*') ? 'active' : '' }}" href="{{ route('trainings.index') }}">
                <span class="nav-icon" aria-hidden="true">▤</span> Eğitimler
            </a>
        </nav>
        <div class="sidebar-footer">
            <span class="service-indicator"></span>
            <span>Eğitim yönetimi</span>
        </div>
    </aside>
    <div class="main-shell">
        <header class="topbar">
            <span class="topbar-title">Ardıç Otomotiv | Eğitim Yönetim Portalı</span>
            <div class="topbar-actions">
                <span class="topbar-badge"><span class="service-indicator"></span> Eğitim Yönetim Portalı</span>
                <span class="signed-in-user">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-button" type="submit">Çıkış yap</button>
                </form>
            </div>
        </header>
        <main class="content">
            @if (session('success'))
                <div class="alert alert-success" role="status">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-error" role="alert">
                    <strong>İşlem tamamlanamadı.</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </main>
        <footer class="footer"><strong>Teknik Değerlendirme Projesi</strong><span>·</span> Personel bilgileri güvenli REST API üzerinden alınır</footer>
    </div>
</body>
</html>
