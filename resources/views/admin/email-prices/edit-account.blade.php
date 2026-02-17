{{-- resources/views/admin/email-prices/edit-account.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4>Редактирование почтового аккаунта: {{ $account->name }}</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.email-prices.update-account', $account) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">Название аккаунта *</label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $account->name) }}" 
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email адрес *</label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $account->email) }}" 
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
                                           value="{{ old('imap_host', $account->imap_host) }}" 
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
                                           value="{{ old('imap_port', $account->imap_port) }}" 
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
                                        <option value="ssl" {{ old('imap_encryption', $account->imap_encryption) == 'ssl' ? 'selected' : '' }}>SSL</option>
                                        <option value="tls" {{ old('imap_encryption', $account->imap_encryption) == 'tls' ? 'selected' : '' }}>TLS</option>
                                        <option value="" {{ old('imap_encryption', $account->imap_encryption) == '' ? 'selected' : '' }}>Нет</option>
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
                                           value="{{ old('username', $account->username) }}" 
                                           required>
                                    @error('username')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Новый пароль</label>
                                    <input type="password" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           id="password" 
                                           name="password">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Оставьте пустым, чтобы не менять пароль</small>
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
                                           value="{{ old('mailbox', $account->mailbox) }}">
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
                                           value="{{ old('check_interval', $account->check_interval) }}" 
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
                                       {{ old('is_active', $account->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Активен</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.email-prices.index') }}" class="btn btn-secondary">Отмена</a>
                            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection