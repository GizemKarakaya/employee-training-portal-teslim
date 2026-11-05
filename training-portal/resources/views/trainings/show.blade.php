@extends('layouts.app')

@section('title', $training->title)

@section('content')
    <div class="breadcrumbs"><a href="{{ route('trainings.index') }}">Eğitimler</a><span aria-hidden="true">/</span><span>Eğitim Detayları</span></div>
    <div class="page-heading detail-heading">
        <div>
            <p class="eyebrow">EĞİTİM DETAYLARI</p>
            <h1>{{ $training->title }}</h1>
            <p class="muted">{{ $training->description }}</p>
        </div>
        <span class="count-tag count-large">{{ $training->assignments->count() }} atama</span>
    </div>

    @if ($personnelError)
        <div class="alert alert-warning" role="status">
            <strong>Personel servisine şu anda ulaşılamıyor.</strong>
            <p>{{ $personnelError }}</p>
            <span>Atama listesi korunuyor; personel adları API yeniden erişilebilir olduğunda gösterilir.</span>
        </div>
    @endif

    <section class="panel assignment-panel">
        <div class="panel-heading">
            <div>
                <h2>Personele eğitim ata</h2>
                <p class="muted">Çalışan listesi Personel Servisi API'sinden anlık alınır.</p>
            </div>
        </div>
        <form class="assign-form" method="POST" action="{{ route('trainings.assignments.store', $training) }}" data-loading>
            @csrf
            <div class="field assign-select">
                <label for="personnel_id">Çalışan</label>
                <select id="personnel_id" name="personnel_id" required @disabled($personnelError || count($employees) === 0)>
                    <option value="">Personel seçin</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee['id'] }}" @selected(old('personnel_id') == $employee['id'])>
                            {{ $employee['first_name'] }} {{ $employee['last_name'] }} · {{ $employee['department'] }}
                        </option>
                    @endforeach
                </select>
                @if (!$personnelError && count($employees) === 0)
                    <span class="field-hint">Personel Servisi içinde kayıtlı çalışan bulunamadı.</span>
                @endif
            </div>
            <button class="button button-primary" type="submit" data-loading-text="Atanıyor..." @disabled($personnelError || count($employees) === 0)>Eğitimi ata</button>
        </form>
    </section>

    <section class="panel assignment-list">
        <div class="panel-heading">
            <div>
                <h2>Atanmış Eğitimler</h2>
                <p class="muted">Eğitim durumu ve tamamlama tarihi.</p>
            </div>
        </div>
        @if ($training->assignments->isEmpty())
            <div class="empty-state small-empty">
                <h3>Bu eğitim için henüz atama yok</h3>
                <p class="muted">Yukarıdaki listeden bir çalışan seçerek atama yapabilirsiniz.</p>
            </div>
        @else
            @php($employeeById = collect($employees)->keyBy('id'))
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>ÇALIŞAN</th><th>ATANMA TARİHİ</th><th>DURUM</th><th>TAMAMLANMA</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($training->assignments as $assignment)
                            @php($employee = $employeeById->get($assignment->personnel_id))
                            <tr>
                                <td>
                                    @if ($personnelError)
                                        <span class="unavailable">Personel bilgisi şu anda alınamıyor</span>
                                    @elseif ($employee)
                                        <div class="assigned-person">
                                            <strong>{{ $employee['first_name'] }} {{ $employee['last_name'] }}</strong>
                                            <span>{{ $employee['email'] }} · {{ $employee['department'] }}</span>
                                        </div>
                                    @else
                                        <span class="unavailable">Personel bilgisi artık erişilebilir değil</span>
                                    @endif
                                </td>
                                <td>{{ $assignment->assigned_at->format('d.m.Y') }}</td>
                                <td><span class="status status-{{ $assignment->status }}">{{ $assignment->status === 'completed' ? 'Tamamlandı' : 'Bekliyor' }}</span></td>
                                <td>{{ $assignment->completed_at?->format('d.m.Y') ?? '—' }}</td>
                                <td class="table-end">
                                    @if ($assignment->status === 'pending')
                                        <form method="POST" action="{{ route('assignments.complete', $assignment) }}" data-loading>
                                            @csrf
                                            @method('PATCH')
                                            <button class="text-button" type="submit" data-loading-text="Güncelleniyor...">Tamamlandı işaretle</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
