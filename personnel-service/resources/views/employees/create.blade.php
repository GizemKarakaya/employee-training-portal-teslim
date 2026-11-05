@extends('layouts.app')

@section('title', 'Yeni Personel Ekle')

@section('content')
    <div class="page-heading compact-heading">
        <div>
            <p class="eyebrow">PERSONEL YÖNETİM SİSTEMİ</p>
            <h1>Yeni Personel Ekle</h1>
            <p class="muted">Çalışan bilgilerini girerek personel kaydını oluşturun.</p>
        </div>
    </div>
    @include('employees.form', [
        'employee' => null,
        'action' => route('employees.store'),
        'method' => 'POST',
        'submitLabel' => 'Personeli kaydet',
    ])
@endsection
