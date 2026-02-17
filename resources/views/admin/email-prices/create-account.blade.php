{{-- resources/views/admin/email-prices/create-account.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4>Добавление почтового аккаунта</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.email-prices.store-account') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Название аккаунта *</label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Например: "JLR Inchcape"</small>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email адрес *</label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email') }}" 
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="imap_host" class="form-label">IMAP сервер *</label>
                                    <input type="text" 
                                           class="form-control @error('imap_host') is-invalid @enderror" 
                                           id="imap_host" 
                                           name="imap_host" 
                                           value="{{ old('imap_host', 'imap.mail.ru') }}" 
                                           required>
                                    @error('imap_host')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="imap_port" class="form-label">Порт *</label>
                                    <input type="number" 
                                           class="form-control @error('imap_port') is-invalid @enderror" 
                                           id="imap_port" 
                                           name="imap_port" 
                                           value="{{ old('imap_port', 993) }}" 
                                           required>
                                    @error('imap_port')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="imap_encryption" class="form-label">Шифрование</label>
                                    <select class="form-control @error('imap_encryption') is-invalid @enderror" 
                                            id="imap_encryption" 
                                            name="imap_encryption">
                                        <option value="ssl" {{ old('imap_encryption') == 'ssl' ? 'selected' : '' }}>SSL</option>
                                        <option value="tls" {{ old('imap_encryption') == 'tls' ? 'selected' : '' }}>TLS</option>
                                        <option value="" {{ old('imap_encryption') == '' ? 'selected' : '' }}>Нет</option>
                                    </select>
                                    @error('imap_encryption')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Имя пользователя *</label>
                                    <input type="text" 
                                           class="form-control @error('username') is-invalid @enderror" 
                                           id="username" 
                                           name="username" 
                                           value="{{ old('username') }}" 
                                           required>
                                    @error('username')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Обычно полный email адрес</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Пароль *</label>
                                    <input type="password" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           id="password" 
                                           name="password" 
                                           required>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="mailbox" class="form-label">Папка</label>
                                    <input type="text" 
                                           class="form-control @error('mailbox') is-invalid @enderror" 
                                           id="mailbox" 
                                           name="mailbox" 
                                           value="{{ old('mailbox', 'INBOX') }}">
                                    @error('mailbox')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="check_interval" class="form-label">Интервал проверки (минуты) *</label>
                                    <input type="number" 
                                           class="form-control @error('check_interval') is-invalid @enderror" 
                                           id="check_interval" 
                                           name="check_interval" 
                                           value="{{ old('check_interval', 60) }}" 
                                           required>
                                    @error('check_interval')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
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
                                <label class="form-check-label" for="is_active">Активен</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.email-prices.index') }}" class="btn btn-secondary">Отмена</a>
                            <button type="submit" class="btn btn-primary">Создать аккаунт</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection