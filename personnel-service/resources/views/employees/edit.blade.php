@extends('layouts.app')

@section('title', 'Personel Düzenle')

@section('content')
    <div class="page-heading compact-heading">
        <div>
            <p class="eyebrow">PERSONEL YÖNETİM SİSTEMİ</p>
            <h1>Personel Düzenle</h1>
            <p class="muted">{{ $employee->first_name }} {{ $employee->last_name }} kaydını güncelleyin.</p>
        </div>
    </div>
    @include('employees.form', [
        'employee' => $employee,
        'action' => route('employees.update', $employee),
        'method' => 'PUT',
        'submitLabel' => 'Değişiklikleri kaydet',
    ])
@endsection
