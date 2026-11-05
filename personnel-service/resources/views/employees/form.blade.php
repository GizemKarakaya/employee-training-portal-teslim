<section class="panel form-panel">
    <form method="POST" action="{{ $action }}" data-loading>
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif
        <div class="form-grid">
            <div class="field">
                <label for="first_name">Ad <span class="required">*</span></label>
                <input id="first_name" name="first_name" value="{{ old('first_name', $employee?->first_name) }}" maxlength="100" required autocomplete="given-name">
            </div>
            <div class="field">
                <label for="last_name">Soyad <span class="required">*</span></label>
                <input id="last_name" name="last_name" value="{{ old('last_name', $employee?->last_name) }}" maxlength="100" required autocomplete="family-name">
            </div>
            <div class="field field-full">
                <label for="email">E-posta <span class="required">*</span></label>
                <input id="email" type="email" name="email" value="{{ old('email', $employee?->email) }}" maxlength="255" required autocomplete="email">
            </div>
            <div class="field field-full">
                <label for="department">Departman <span class="required">*</span></label>
                <input id="department" name="department" value="{{ old('department', $employee?->department) }}" maxlength="120" required placeholder="Örn. İnsan Kaynakları">
            </div>
        </div>
        <div class="form-footer">
            <a class="button button-secondary" href="{{ route('employees.index') }}">Vazgeç</a>
            <button class="button button-primary" type="submit" data-loading-text="Kaydediliyor...">{{ $submitLabel }}</button>
        </div>
    </form>
</section>
