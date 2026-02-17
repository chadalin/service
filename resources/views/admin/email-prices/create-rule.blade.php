{{-- resources/views/admin/email-prices/create-rule.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4>Создание правила импорта для аккаунта: {{ $account->name }}</h4>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.email-prices.store-rule', ['account' => $account->id]) }}" 
                          method="POST">
                        @csrf
                        <input type="hidden" name="account_id" value="{{ $account->id }}">
                        <div class="mb-3">
                            <label for="name" class="form-label">Название правила *</label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="brand_id" class="form-label">Бренд *</label>
                            <select class="form-control @error('brand_id') is-invalid @enderror" 
                                    id="brand_id" 
                                    name="brand_id" 
                                    required>
                                <option value="">Выберите бренд</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>
                                        {{ $brand->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('brand_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email_subject_pattern" class="form-label">Паттерн темы письма</label>
                            <input type="text" 
                                   class="form-control @error('email_subject_pattern') is-invalid @enderror" 
                                   id="email_subject_pattern" 
                                   name="email_subject_pattern" 
                                   value="{{ old('email_subject_pattern') }}"
                                   placeholder="/ALL JLR Inchcape/i">
                            @error('email_subject_pattern')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Регулярное выражение для поиска в теме письма</small>
                        </div>

                        <div class="mb-3">
                            <label for="email_sender_pattern" class="form-label">Паттерн отправителя</label>
                            <input type="text" 
                                   class="form-control @error('email_sender_pattern') is-invalid @enderror" 
                                   id="email_sender_pattern" 
                                   name="email_sender_pattern" 
                                   value="{{ old('email_sender_pattern') }}"
                                   placeholder="/partsm2jlr@borishof.ru/i">
                            @error('email_sender_pattern')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Паттерны имен файлов</label>
                            <div id="filename-patterns">
                                @if(old('filename_patterns'))
                                    @foreach(old('filename_patterns') as $pattern)
                                        <div class="input-group mb-2">
                                            <input type="text" 
                                                   class="form-control" 
                                                   name="filename_patterns[]" 
                                                   value="{{ $pattern }}"
                                                   placeholder="Rolls-Royce.*\.xlsx">
                                            <button class="btn btn-outline-danger remove-pattern" type="button">×</button>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="input-group mb-2">
                                        <input type="text" 
                                               class="form-control" 
                                               name="filename_patterns[]" 
                                               value="{{ old('filename_patterns.0') }}"
                                               placeholder="Rolls-Royce.*\.xlsx">
                                        <button class="btn btn-outline-danger remove-pattern" type="button">×</button>
                                    </div>
                                    <div class="input-group mb-2">
                                        <input type="text" 
                                               class="form-control" 
                                               name="filename_patterns[]" 
                                               value="{{ old('filename_patterns.1') }}"
                                               placeholder="Инчкейп.*\.xlsx">
                                        <button class="btn btn-outline-danger remove-pattern" type="button">×</button>
                                    </div>
                                @endif
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="add-pattern">
                                <i class="fas fa-plus"></i> Добавить паттерн
                            </button>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="priority" class="form-label">Приоритет</label>
                                    <input type="number" 
                                           class="form-control @error('priority') is-invalid @enderror" 
                                           id="priority" 
                                           name="priority" 
                                           value="{{ old('priority', 0) }}">
                                    @error('priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       id="update_existing" 
                                       name="update_existing" 
                                       value="1"
                                       {{ old('update_existing', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="update_existing">Обновлять существующие товары</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       id="match_symptoms" 
                                       name="match_symptoms" 
                                       value="1"
                                       {{ old('match_symptoms') ? 'checked' : '' }}>
                                <label class="form-check-label" for="match_symptoms">Сопоставлять с симптомами</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       id="is_active" 
                                       name="is_active" 
                                       value="1"
                                       {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Активно</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.email-prices.index') }}" class="btn btn-secondary">Отмена</a>
                            <button type="submit" class="btn btn-primary">Создать правило</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('add-pattern')?.addEventListener('click', function() {
    const container = document.getElementById('filename-patterns');
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <input type="text" class="form-control" name="filename_patterns[]" placeholder="Новый паттерн">
        <button class="btn btn-outline-danger remove-pattern" type="button">×</button>
    `;
    container.appendChild(div);
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-pattern')) {
        e.target.closest('.input-group').remove();
    }
});
</script>
@endpush
@endsection