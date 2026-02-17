{{-- resources/views/admin/email-prices/logs.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Логи импорта прайсов с почты</h1>
        <a href="{{ route('admin.email-prices.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Назад к настройкам
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-4">
                    <select name="account_id" class="form-control">
                        <option value="">Все аккаунты</option>
                        @foreach($accounts as $id => $name)
                            <option value="{{ $id }}" {{ request('account_id') == $id ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-control">
                        <option value="">Все статусы</option>
                        <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Успешно</option>
                        <option value="error" {{ request('status') == 'error' ? 'selected' : '' }}>Ошибка</option>
                        <option value="skipped" {{ request('status') == 'skipped' ? 'selected' : '' }}>Пропущено</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Фильтровать</button>
                    <a href="{{ route('admin.email-prices.logs') }}" class="btn btn-secondary">Сбросить</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Аккаунт</th>
                        <th>Правило</th>
                        <th>Бренд</th>
                        <th>Файл</th>
                        <th>Статус</th>
                        <th>Обработано</th>
                        <th>Создано</th>
                        <th>Обновлено</th>
                        <th>Пропущено</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d.m.Y H:i:s') }}</td>
                            <td>{{ $log->emailAccount?->name ?? '—' }}</td>
                            <td>{{ $log->rule?->name ?? '—' }}</td>
                            <td>{{ $log->brand?->name ?? '—' }}</td>
                            <td>
                                <small>{{ $log->filename ?? '—' }}</small>
                            </td>
                            <td>
                                @if($log->status == 'success')
                                    <span class="badge bg-success">Успешно</span>
                                @elseif($log->status == 'error')
                                    <span class="badge bg-danger">Ошибка</span>
                                @else
                                    <span class="badge bg-warning">{{ $log->status }}</span>
                                @endif
                            </td>
                            <td>{{ $log->items_processed }}</td>
                            <td>{{ $log->items_created }}</td>
                            <td>{{ $log->items_updated }}</td>
                            <td>{{ $log->items_skipped }}</td>
                            <td>
                                <button class="btn btn-sm btn-info" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#logModal{{ $log->id }}">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>

                        {{-- Модальное окно с деталями --}}
                        <div class="modal fade" id="logModal{{ $log->id }}" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Детали импорта</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        @if($log->email_subject)
                                            <p><strong>Тема письма:</strong> {{ $log->email_subject }}</p>
                                        @endif
                                        
                                        @if($log->email_from)
                                            <p><strong>Отправитель:</strong> {{ $log->email_from }}</p>
                                        @endif
                                        
                                        @if($log->error_message)
                                            <div class="alert alert-danger">
                                                <strong>Ошибка:</strong> {{ $log->