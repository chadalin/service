@extends('layouts.consultation')

@section('title', 'Мои консультации')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Заголовок и кнопка создания -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Мои консультации</h1>
            <p class="text-gray-600">Управление вашими диагностическими консультациями</p>
        </div>
        
        @php
            // Ищем активный случай для создания консультации
            use App\Models\Diagnostic\DiagnosticCase;
            $activeCase = DiagnosticCase::where('user_id', auth()->id())
                ->whereIn('status', ['report_ready', 'consultation_pending'])
                ->latest()
                ->first();
        @endphp
        
        @if($activeCase)
            <a href="{{ route('diagnostic.consultation.order', ['case' => $activeCase->id]) }}" 
               class="btn-primary inline-flex items-center">
                <i class="fas fa-plus-circle mr-2"></i>
                Новая консультация
            </a>
        @else
            <div class="space-y-2">
                <a href="{{ route('diagnostic.start') }}" 
                   class="btn-primary inline-flex items-center">
                    <i class="fas fa-stethoscope mr-2"></i>
                    Начать диагностику
                </a>
                <p class="text-sm text-gray-500">Сначала создайте диагностический случай</p>
            </div>
        @endif
    </div>

    <!-- Фильтры и статистика -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Статистика -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-700 mb-4">Статистика</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ $consultations->total() }}</div>
                    <div class="text-sm text-gray-500">Всего</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">
                        {{ $consultations->where('status', 'completed')->count() }}
                    </div>
                    <div class="text-sm text-gray-500">Завершено</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">
                        {{ $consultations->where('status', 'in_progress')->count() }}
                    </div>
                    <div class="text-sm text-gray-500">В работе</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-yellow-600">
                        {{ $consultations->where('status', 'pending')->count() }}
                    </div>
                    <div class="text-sm text-gray-500">Ожидание</div>
                </div>
            </div>
        </div>

        <!-- Фильтры -->
        <div class="bg-white rounded-xl shadow-sm p-6 col-span-1 lg:col-span-2">
            <h3 class="font-semibold text-gray-700 mb-4">Фильтры</h3>
            <div class="flex flex-wrap gap-2">
                <a href="?status=all" 
                   class="px-4 py-2 rounded-full {{ $status == 'all' ? 'bg-blue-100 text-blue-700 border border-blue-300' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Все
                </a>
                <a href="?status=pending" 
                   class="px-4 py-2 rounded-full {{ $status == 'pending' ? 'bg-yellow-100 text-yellow-700 border border-yellow-300' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Ожидание
                </a>
                <a href="?status=scheduled" 
                   class="px-4 py-2 rounded-full {{ $status == 'scheduled' ? 'bg-blue-100 text-blue-700 border border-blue-300' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Запланирована
                </a>
                <a href="?status=in_progress" 
                   class="px-4 py-2 rounded-full {{ $status == 'in_progress' ? 'bg-purple-100 text-purple-700 border border-purple-300' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    В процессе
                </a>
                <a href="?status=completed" 
                   class="px-4 py-2 rounded-full {{ $status == 'completed' ? 'bg-green-100 text-green-700 border border-green-300' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Завершена
                </a>
                <a href="?status=cancelled" 
                   class="px-4 py-2 rounded-full {{ $status == 'cancelled' ? 'bg-red-100 text-red-700 border border-red-300' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Отменена
                </a>
            </div>
        </div>
    </div>

    <!-- Список консультаций -->
    @if($consultations->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($consultations as $consultation)
                <div class="consultation-card p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <div class="flex items-center space-x-2 mb-2">
                                <span class="status-badge status-{{ $consultation->status }}">
                                    @switch($consultation->status)
                                        @case('pending') ⏳ Ожидание @break
                                        @case('scheduled') 📅 Запланирована @break
                                        @case('in_progress') 🔄 В работе @break
                                        @case('completed') ✅ Завершена @break
                                        @case('cancelled') ❌ Отменена @break
                                    @endswitch
                                </span>
                                <span class="type-badge type-{{ $consultation->type }}">
                                    @switch($consultation->type)
                                        @case('basic') Базовая @break
                                        @case('premium') Премиум @break
                                        @case('expert') Экспертная @break
                                    @endswitch
                                </span>
                            </div>
                            <h3 class="font-bold text-lg text-gray-800">Консультация #{{ $consultation->id }}</h3>
                            <p class="text-sm text-gray-600 mt-1">
                                @if($consultation->case)
                                    {{ $consultation->case->brand->name ?? '' }} {{ $consultation->case->model->name ?? '' }}
                                @endif
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-gray-800">
                                {{ number_format($consultation->price, 0) }} ₽
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3 mb-6">
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-user-tie mr-2 w-5"></i>
                            <span>
                                @if($consultation->expert)
                                    {{ $consultation->expert->name }}
                                @else
                                    <span class="text-yellow-600">Эксперт не назначен</span>
                                @endif
                            </span>
                        </div>
                        
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-calendar-alt mr-2 w-5"></i>
                            <span>
                                Создана: {{ $consultation->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                        
                        @if($consultation->scheduled_at)
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-clock mr-2 w-5"></i>
                                <span>
                                    Назначена: {{ \Carbon\Carbon::parse($consultation->scheduled_at)->format('d.m.Y H:i') }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <div class="flex justify-between items-center pt-4 border-t border-gray-100">
                        <a href="{{ route('diagnostic.consultation.show', $consultation) }}" 
                           class="text-blue-600 hover:text-blue-800 font-medium inline-flex items-center">
                            <i class="fas fa-eye mr-1"></i> Подробнее
                        </a>
                        
                        @if(in_array($consultation->status, ['pending', 'scheduled']))
                            <form action="{{ route('diagnostic.consultation.cancel', $consultation) }}" 
                                  method="POST" 
                                  onsubmit="return confirm('Вы уверены, что хотите отменить консультацию?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm">
                                    <i class="fas fa-times mr-1"></i> Отменить
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Пагинация -->
        <div class="mt-8">
            {{ $consultations->links('vendor.pagination.tailwind') }}
        </div>
    @else
        <!-- Пустой список -->
        <div class="text-center py-12">
            <div class="mb-6">
                <i class="fas fa-comments text-gray-300 text-6xl"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">Консультаций пока нет</h3>
            <p class="text-gray-600 mb-6 max-w-md mx-auto">
                У вас еще нет диагностических консультаций. Начните с создания диагностического случая.
            </p>
            <a href="{{ route('diagnostic.start') }}" class="btn-primary inline-flex items-center">
                <i class="fas fa-stethoscope mr-2"></i>
                Начать диагностику
            </a>
        </div>
    @endif
</div>

@push('scripts')
<script>
    // Автоматическое обновление статусов
    document.addEventListener('DOMContentLoaded', function() {
        // Автоматически скрываем алерты через 5 секунд
        setTimeout(() => {
            document.querySelectorAll('.bg-green-50, .bg-red-50, .bg-blue-50').forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Обновление уведомлений каждые 30 секунд
        function updateNotificationCount() {
            fetch('/api/consultations/unread-count')
                .then(response => response.json())
                .then(data => {
                    const badge = document.querySelector('a[href*="consultation"] .bg-red-500');
                    if (data.unread_count > 0) {
                        if (!badge) {
                            const link = document.querySelector('a[href*="consultation"]');
                            const newBadge = document.createElement('span');
                            newBadge.className = 'absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center';
                            newBadge.textContent = data.unread_count;
                            link.appendChild(newBadge);
                        } else if (badge.textContent != data.unread_count) {
                            badge.textContent = data.unread_count;
                        }
                    } else if (badge) {
                        badge.remove();
                    }
                });
        }

        // Обновляем каждые 30 секунд
        setInterval(updateNotificationCount, 30000);
    });
</script>
@endpush
@endsection