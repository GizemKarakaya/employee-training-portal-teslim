@extends('layouts.app')

@section('title', 'Yeni Eğitim Oluştur')

@section('content')
    <div class="page-heading compact-heading">
        <div>
            <p class="eyebrow">PROGRAM YÖNETİMİ</p>
            <h1>Yeni Eğitim Oluştur</h1>
            <p class="muted">Eğitim programı için temel bilgileri tanımlayın.</p>
        </div>
    </div>
    <section class="panel form-panel">
        <form method="POST" action="{{ route('trainings.store') }}" data-loading>
            @csrf
            <div class="form-grid">
                <div class="field field-full">
                    <label for="title">Eğitim adı <span class="required">*</span></label>
                    <input id="title" name="title" value="{{ old('title') }}" maxlength="160" required autofocus placeholder="Örn. Bilgi Güvenliği Farkındalığı">
                </div>
                <div class="field field-full">
                    <label for="description">Açıklama <span class="required">*</span></label>
                    <textarea id="description" name="description" rows="5" maxlength="5000" required placeholder="Eğitimin kısa açıklamasını yazın.">{{ old('description') }}</textarea>
                </div>
            </div>
            <div class="form-footer">
                <a class="button button-secondary" href="{{ route('trainings.index') }}">Vazgeç</a>
                <button class="button button-primary" type="submit" data-loading-text="Oluşturuluyor...">Eğitimi Oluştur</button>
            </div>
        </form>
    </section>
@endsection
