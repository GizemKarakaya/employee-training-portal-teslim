<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Personel Yönetimi') · Ardıç Otomotiv</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="{{ route('employees.index') }}" aria-label="Ardıç Otomotiv Personel Yönetim Sistemi ana sayfası">
                <span class="brand-mark" aria-hidden="true">A</span>
                <span class="brand-copy">
                    <span class="brand-name">Ardıç Otomotiv |</span>
                    <span class="brand-product">Personel Yönetim Sistemi</span>
                </span>
            </a>
            <div class="topbar-actions">
                <span class="service-pill"><span class="dot"></span> Personel Servisi</span>
                <span class="signed-in-user">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-button" type="submit">Çıkış yap</button>
                </form>
            </div>
        </div>
    </header>
    <main class="container">
        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-error" role="alert">
                <strong>Formda düzeltmeniz gereken alanlar var.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </main>
    <footer class="footer"><strong>Teknik Değerlendirme Projesi</strong><span>·</span> Ardıç Otomotiv Personel Yönetim Sistemi</footer>
</body>
</html>
