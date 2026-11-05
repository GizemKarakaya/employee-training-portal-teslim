<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Yönetici Girişi · Ardıç Otomotiv</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <main class="login-shell">
        <section class="panel login-card">
            <a class="brand" href="{{ route('login') }}" aria-label="Ardıç Otomotiv Eğitim Yönetim Portalı">
                <span class="brand-mark" aria-hidden="true">A</span>
                <span class="brand-copy">
                    <span class="brand-name">Ardıç Otomotiv |</span>
                    <span class="brand-product">Eğitim Yönetim Portalı</span>
                </span>
            </a>
            <h1>Yönetici Girişi</h1>
            <p class="muted">Eğitimleri ve atamaları yönetmek için giriş yapın.</p>
            @if ($errors->any())
                <div class="alert alert-error" role="alert">
                    <strong>Giriş yapılamadı.</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="POST" action="{{ route('login') }}" data-loading>
                @csrf
                <div class="field">
                    <label for="email">E-posta</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
                </div>
                <div class="field">
                    <label for="password">Parola</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <button class="button button-primary" type="submit" data-loading-text="Giriş yapılıyor...">Giriş yap</button>
            </form>
            <p class="project-note">Teknik Değerlendirme Projesi</p>
        </section>
    </main>
</body>
</html>
