@extends('layouts.app')

@section('title', 'Personel Yönetimi')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">PERSONEL YÖNETİM SİSTEMİ</p>
            <h1>Personel Yönetimi</h1>
            <p class="muted">Çalışan kayıtlarını yönetin ve güncel bilgileri görüntüleyin.</p>
        </div>
        <a class="button button-primary" href="{{ route('employees.create') }}"><span aria-hidden="true">＋</span> Yeni Personel Ekle</a>
    </div>

    <section class="summary-card">
        <div class="summary-icon" aria-hidden="true">♙</div>
        <div>
            <span class="summary-label">Toplam çalışan</span>
            <strong class="summary-value">{{ $totalEmployees }}</strong>
        </div>
        <span class="summary-caption">Kayıtlı çalışan</span>
    </section>

    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Personel Listesi</h2>
                <p class="muted">Personel bilgileri bu servisin kendi veritabanında saklanır.</p>
            </div>
            <form class="filter-form" method="GET" action="{{ route('employees.index') }}">
                <label class="sr-only" for="department">Departmana göre filtrele</label>
                <select id="department" name="department">
                    <option value="">Tüm departmanlar</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department }}" @selected($selectedDepartment === $department)>{{ $department }}</option>
                    @endforeach
                </select>
                <button class="button button-secondary" type="submit">Filtrele</button>
            </form>
        </div>

        @if ($employees->isEmpty())
            <div class="empty-state">
                <div class="empty-icon" aria-hidden="true">⌕</div>
                <h3>{{ $selectedDepartment ? 'Bu departmanda personel bulunamadı' : 'Henüz personel kaydı yok' }}</h3>
                <p class="muted">{{ $selectedDepartment ? 'Başka bir departman seçebilir veya filtreyi temizleyebilirsiniz.' : 'İlk çalışan kaydını ekleyerek personel dizinini oluşturmaya başlayın.' }}</p>
                @if ($selectedDepartment)
                    <a class="button button-secondary" href="{{ route('employees.index') }}">Filtreyi temizle</a>
                @else
                    <a class="button button-primary" href="{{ route('employees.create') }}">Yeni Personel Ekle</a>
                @endif
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ÇALIŞAN</th>
                            <th>E-POSTA</th>
                            <th>DEPARTMAN</th>
                            <th class="actions-heading">İŞLEMLER</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($employees as $employee)
                            <tr>
                                <td>
                                    <div class="person-cell">
                                        <span class="avatar">{{ mb_strtoupper(mb_substr($employee->first_name, 0, 1).mb_substr($employee->last_name, 0, 1)) }}</span>
                                        <span class="person-name">{{ $employee->first_name }} {{ $employee->last_name }}</span>
                                    </div>
                                </td>
                                <td><a class="email-link" href="mailto:{{ $employee->email }}">{{ $employee->email }}</a></td>
                                <td><span class="department-tag">{{ $employee->department }}</span></td>
                                <td>
                                    <div class="row-actions">
                                        <a class="text-button" href="{{ route('employees.edit', $employee) }}">Düzenle</a>
                                        <form method="POST" action="{{ route('employees.destroy', $employee) }}" data-loading data-confirm="Bu personel kaydını silmek istediğinize emin misiniz?">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-button text-danger" type="submit" data-loading-text="Siliniyor...">Sil</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
