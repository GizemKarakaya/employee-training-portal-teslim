@extends('layouts.app')

@section('title', 'Eğitimler')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">PROGRAM YÖNETİMİ</p>
            <h1>Eğitimler</h1>
            <p class="muted">Eğitim programlarını tanımlayın ve atamaları takip edin.</p>
        </div>
        <a class="button button-primary" href="{{ route('trainings.create') }}"><span aria-hidden="true">＋</span> Yeni Eğitim Oluştur</a>
    </div>
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Eğitim Programları</h2>
                <p class="muted">{{ $trainings->count() }} eğitim programı</p>
            </div>
        </div>
        @if ($trainings->isEmpty())
            <div class="empty-state">
                <div class="empty-icon" aria-hidden="true">▤</div>
                <h3>Henüz eğitim tanımlanmamış</h3>
                <p class="muted">Eğitim oluşturduğunuzda burada görüntülenecek.</p>
                <a class="button button-primary" href="{{ route('trainings.create') }}">Yeni Eğitim Oluştur</a>
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>EĞİTİM ADI</th><th>AÇIKLAMA</th><th>ATAMA</th><th>OLUŞTURULMA</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($trainings as $training)
                            <tr>
                                <td><a class="training-link" href="{{ route('trainings.show', $training) }}">{{ $training->title }}</a></td>
                                <td class="description-cell">{{ $training->description }}</td>
                                <td><span class="count-tag">{{ $training->assignments_count }}</span></td>
                                <td>{{ $training->created_at->format('d.m.Y') }}</td>
                                <td class="table-end"><a class="text-button" href="{{ route('trainings.show', $training) }}">Aç <span aria-hidden="true">→</span></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
