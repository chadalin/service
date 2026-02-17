{{-- resources/views/admin/email-prices/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Настройки импорта прайсов с почты</h1>
        <a href="{{ route('admin.email-prices.create-account') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Добавить почтовый аккаунт
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @foreach($accounts as $account)
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">{{ $account->name }}</h5>
                    <small class="text-muted">{{ $account->email }}</small>
                </div>
                <div>
                    <span class="badge {{ $account->is_active ? 'bg-success' : 'bg-secondary' }} me-2">
                        {{ $account->is_active ? 'Активен' : 'Неактивен' }}
                    </span>
                    <button class="btn btn-sm btn-info check-now" data-id="{{ $account->id }}">
                        <i class="fas fa-sync-alt"></i> Проверить сейчас
                    </button>
                    <a href="{{ route('admin.email-prices.edit-account', $account) }}" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i>
                    </a>
                    <form action="{{ route('admin.email-prices.destroy-account', $account) }}" method="POST" class="d-inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Удалить аккаунт?')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <h6>Правила импорта:</h6>
                
                @if($account->rules->isEmpty())
                    <p class="text-muted">Нет правил импорта</p>
                @else
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Название</th>
                                <th>Бренд</th>
                                <th>Паттерн темы</th>
                                <th>Паттерны файлов</th>
                                <th>Приоритет</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($account->rules as $rule)
                                <tr>
                                    <td>{{ $rule->name }}</td>
                                    <td>{{ $rule->brand->name }}</td>
                                    <td><code>{{ $rule->email_subject_pattern }}</code></td>
                                    <td>
                                        @foreach($rule->filename_patterns ?? [] as $pattern)
                                            <code>{{ $pattern }}</code><br>
                                        @endforeach
                                    </td>
                                    <td>{{ $rule->priority }}</td>
                                    <td>
                                        <span class="badge {{ $rule->is_active ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $rule->is_active ? 'Активно' : 'Неактивно' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.email-prices.edit-rule', $rule) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.email-prices.destroy-rule', $rule) }}" method="POST" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Удалить правило?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
                
                <a href="{{ route('admin.email-prices.create-rule', $account) }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-plus"></i> Добавить правило
                </a>
            </div>
            <div class="card-footer text-muted">
                Последняя проверка: {{ $account->last_checked_at ? $account->last_checked_at->format('d.m.Y H:i') : 'никогда' }}
                | Интервал: {{ $account->check_interval }} мин.
            </div>
        </div>
    @endforeach

    <div class="mt-4">
        <a href="{{ route('admin.email-prices.logs') }}" class="btn btn-info">
            <i class="fas fa-history"></i> Просмотр логов импорта
        </a>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.check-now').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Проверка...';
        
        fetch(`/admin/email-prices/check/${id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
        })
        .catch(error => {
            alert('Ошибка при проверке');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt"></i> Проверить сейчас';
        });
    });
});
</script>
@endpush
@endsection