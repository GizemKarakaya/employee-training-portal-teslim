@extends('layouts.app')

@section('title', 'Genel Bakış')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">EĞİTİM YÖNETİM PORTALI</p>
            <h1>Genel Bakış</h1>
            <p class="muted">Eğitim ve çalışan atamalarının güncel durumu.</p>
        </div>
        <a class="button button-primary" href="{{ route('trainings.create') }}"><span aria-hidden="true">＋</span> Yeni Eğitim Oluştur</a>
    </div>

    <section class="stats-grid" aria-label="Eğitim istatistikleri">
        <article class="stat-card">
            <span class="stat-icon icon-blue" aria-hidden="true">▤</span>
            <span class="stat-label">Toplam eğitim</span>
            <strong class="stat-value">{{ $trainingCount }}</strong>
            <span class="stat-foot">Tanımlı program</span>
        </article>
        <article class="stat-card">
            <span class="stat-icon icon-violet" aria-hidden="true">♧</span>
            <span class="stat-label">Toplam atama</span>
            <strong class="stat-value">{{ $assignmentCount }}</strong>
            <span class="stat-foot">Çalışanlara atanan</span>
        </article>
        <article class="stat-card">
            <span class="stat-icon icon-green" aria-hidden="true">✓</span>
            <span class="stat-label">Tamamlanan Eğitimler</span>
            <strong class="stat-value">{{ $completedCount }}</strong>
            <span class="stat-foot">Tamamlanmış eğitim ataması</span>
        </article>
        <article class="stat-card">
            <span class="stat-icon icon-amber" aria-hidden="true">◷</span>
            <span class="stat-label">Bekleyen Eğitimler</span>
            <strong class="stat-value">{{ $pendingCount }}</strong>
            <span class="stat-foot">Tamamlanmayı bekleyen atama</span>
        </article>
    </section>

    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Son eğitimler</h2>
                <p class="muted">Son oluşturulan eğitim programları.</p>
            </div>
            <a class="text-button" href="{{ route('trainings.index') }}">Tümünü görüntüle <span aria-hidden="true">→</span></a>
        </div>
        @if ($recentTrainings->isEmpty())
            <div class="empty-state">
                <div class="empty-icon" aria-hidden="true">▤</div>
                <h3>Henüz eğitim tanımlanmamış</h3>
                <p class="muted">İlk eğitim programınızı oluşturarak başlayın.</p>
                <a class="button button-primary" href="{{ route('trainings.create') }}">Yeni Eğitim Oluştur</a>
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>EĞİTİM</th><th>ATAMA SAYISI</th><th>OLUŞTURULMA</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($recentTrainings as $training)
                            <tr>
                                <td><a class="training-link" href="{{ route('trainings.show', $training) }}">{{ $training->title }}</a></td>
                                <td>{{ $training->assignments_count }}</td>
                                <td>{{ $training->created_at->format('d.m.Y') }}</td>
                                <td class="table-end"><a class="text-button" href="{{ route('trainings.show', $training) }}">Detaylar <span aria-hidden="true">→</span></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
