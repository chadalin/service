@extends('layouts.admin')

@section('title', 'Правила диагностики')
@section('subtitle', 'Управление правилами диагностики')

@section('content')
<div class="admin-card p-6">
    <!-- Заголовок и кнопки -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-lg font-bold text-gray-800">Список правил</h2>
            <p class="text-sm text-gray-600">Всего правил: {{ $rules->total() }}</p>
        </div>
        
        <div class="flex space-x-2">
            <a href="{{ route('admin.diagnostic.rules.create') }}" class="btn-admin-primary">
            
                <i class="fas fa-plus mr-2"></i> Добавить правило
            </a>
            <a href="{{ route('admin.diagnostic.rules.create') }}" class="btn-admin-secondary">
                <i class="fas fa-file-import mr-2"></i> Импорт
            </a>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
        <form method="GET" action="{{ route('admin.diagnostic.rules.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Симптом</label>
                <select name="symptom_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Все симптомы</option>
                    @foreach($symptoms as $symptom)
                        <option value="{{ $symptom->id }}" {{ request('symptom_id') == $symptom->id ? 'selected' : '' }}>
                            {{ $symptom->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Бренд</label>
                <select name="brand_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Все бренды</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>
                            {{ $brand->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Статус</label>
                <select name="is_active" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Все</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Активные</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Неактивные</option>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="btn-admin-primary w-full">
                    <i class="fas fa-filter mr-2"></i> Фильтровать
                </button>
            </div>
        </form>
    </div>

    <!-- Таблица -->
    <div class="overflow-x-auto">
        <table class="table-admin">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Симптом</th>
                    <th>Бренд / Модель</th>
                    <th>Сложность</th>
                    <th>Цена</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rules as $rule)
                    <tr>
                        <td class="font-mono text-xs">{{ $rule->id }}</td>
                        <td>
                            <div class="font-medium">{{ $rule->symptom->name ?? '—' }}</div>
                            <div class="text-xs text-gray-500 truncate max-w-xs">
                                {{ $rule->symptom->description ?? '' }}
                            </div>
                        </td>
                        <td>
                            <div class="font-medium">{{ $rule->brand->name ?? '—' }}</div>
                            <div class="text-xs text-gray-500">
                                {{ $rule->model->name ?? 'Все модели' }}
                            </div>
                        </td>
                        <td>
                            <div class="flex items-center">
                                <span class="complexity-badge complexity-{{ $rule->complexity_level }} mr-2">
                                    {{ $rule->complexity_level }}/10
                                </span>
                                <span class="text-xs text-gray-500">
                                    {{ $rule->estimated_time }} мин
                                </span>
                            </div>
                        </td>
                        <td class="font-medium">
                            {{ number_format($rule->base_consultation_price, 0, '', ' ') }} ₽
                        </td>
                        <td>
                            @if($rule->is_active)
                                <span class="badge-success">Активно</span>
                            @else
                                <span class="badge-danger">Неактивно</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex space-x-1">
                                <a href="{{ route('diagnostic.rules.edit', $rule->id) }}" 
                                   class="px-2 py-1 bg-blue-100 text-blue-600 rounded text-xs hover:bg-blue-200">
                                    <i class="fas fa-edit mr-1"></i>
                                </a>
                                <a href="{{ route('admin.rules.show', $rule->id) }}" 
                                   class="px-2 py-1 bg-green-100 text-green-600 rounded text-xs hover:bg-green-200">
                                    <i class="fas fa-eye mr-1"></i>
                                </a>
                                <form action="{{ route('admin.rules.destroy', $rule->id) }}" 
                                      method="POST" 
                                      class="inline"
                                      onsubmit="return confirm('Удалить правило?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="px-2 py-1 bg-red-100 text-red-600 rounded text-xs hover:bg-red-200">
                                        <i class="fas fa-trash mr-1"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-8 text-gray-500">
                            <i class="fas fa-inbox text-3xl mb-2 block"></i>
                            Правила не найдены
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Пагинация -->
    @if($rules->hasPages())
        <div class="mt-6">
            {{ $rules->withQueryString()->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
// Быстрое переключение статуса
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.status-toggle').forEach(button => {
        button.addEventListener('click', function() {
            const ruleId = this.dataset.id;
            const currentStatus = this.dataset.status === '1';
            const newStatus = !currentStatus;
            
            fetch(`/admin/rules/${ruleId}/toggle-status`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ is_active: newStatus })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        });
    });
});
</script>
@endpush
@endsection