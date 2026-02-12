{{-- resources/views/links/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Редактировать ссылку')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3>Редактировать ссылку</h3>
            </div>
            
            <div class="card-body">
                <form action="{{ route('links.update', $link) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Название *</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" 
                               id="title" name="title" value="{{ old('title', $link->title) }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="url" class="form-label">URL *</label>
                        <input type="url" class="form-control @error('url') is-invalid @enderror" 
                               id="url" name="url" value="{{ old('url', $link->url) }}" required>
                        @error('url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Описание</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3">{{ old('description', $link->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="auth_type" class="form-label">Тип авторизации</label>
                        <select class="form-select" id="auth_type" name="auth_type">
                            <option value="basic" {{ old('auth_type', $link->auth_type) == 'basic' ? 'selected' : '' }}>Basic Auth</option>
                            <option value="form" {{ old('auth_type', $link->auth_type) == 'form' ? 'selected' : '' }}>Form Auth</option>
                            <option value="none" {{ old('auth_type', $link->auth_type) == 'none' ? 'selected' : '' }}>Без авторизации</option>
                        </select>
                    </div>
                    
                    <div id="authFields">
                        <div class="mb-3">
                            <label for="login" class="form-label">Логин</label>
                            <input type="text" class="form-control @error('login') is-invalid @enderror" 
                                   id="login" name="login" value="{{ old('login', $link->login) }}">
                            @error('login')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Пароль</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                   id="password" name="password" placeholder="Оставьте пустым, чтобы не менять">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('links.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Назад
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Обновить
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const authType = document.getElementById('auth_type');
    const authFields = document.getElementById('authFields');
    
    function toggleAuthFields() {
        authFields.style.display = authType.value === 'none' ? 'none' : 'block';
    }
    
    toggleAuthFields();
    authType.addEventListener('change', toggleAuthFields);
});
</script>
@endpush
@endsection