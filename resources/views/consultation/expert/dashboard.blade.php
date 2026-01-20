@extends('layouts.consultation')

@section('title', 'Дашборд эксперта')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Заголовок и быстрые действия -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Дашборд эксперта</h1>
            <p class="text-gray-600">Управление консультациями и расписанием</p>
        </div>
        
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('expert.profile.edit') }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg">
                <i class="fas fa-user-circle mr-2"></i> Профиль
            </a>
            <a href="{{ route('expert.schedule.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg">
                <i class="fas fa-calendar-alt mr-2"></i> Расписание
            </a>
            <a href="{{ route('expert.analytics.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg">
                <i class="fas fa-chart-line mr-2"></i> Аналитика
            </a>
        </div>
    </div>

    <!-- Статистика -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-700 mb-1">Всего консультаций</p>
                    <p class="text-3xl font-bold text-blue-800">{{ $stats['total'] }}</p>
                </div>
                <i class="fas fa-comments text-blue-500 text-2xl"></i>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-200 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-yellow-700 mb-1">В ожидании</p>
                    <p class="text-3xl font-bold text-yellow-800">{{ $stats['pending'] }}</p>
                </div>
                <i class="fas fa-clock text-yellow-500 text-2xl"></i>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-purple-700 mb-1">В работе</p>
                    <p class="text-3xl font-bold text-purple-800">{{ $stats['in_progress'] }}</p>
                </div>
                <i class="fas fa-spinner text-purple-500 text-2xl"></i>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-green-700 mb-1">Средний рейтинг</p>
                    <p class="text-3xl font-bold text-green-800">{{ number_format($stats['avg_rating'], 1) }}/5</p>
                </div>
                <i class="fas fa-star text-green-500 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Основной контент с табами -->
    <div class="bg-white rounded-xl shadow-sm mb-8">
        <!-- Табы -->
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <a href="{{ route('diagnostic.consultation.expert.dashboard') }}?status=all" 
                   class="py-4 px-6 font-medium {{ $status == 'all' ? 'tab-active' : 'tab-inactive' }}">
                    Все ({{ $consultations->total() }})
                </a>
                <a href="{{ route('diagnostic.consultation.expert.dashboard') }}?status=pending" 
                   class="py-4 px-6 font-medium {{ $status == 'pending' ? 'tab-active' : 'tab-inactive' }}">
                    Ожидание ({{ $stats['pending'] }})
                </a>
                <a href="{{ route('diagnostic.consultation.expert.dashboard') }}?status=in_progress" 
                   class="py-4 px-6 font-medium {{ $status == 'in_progress' ? 'tab-active' : 'tab-inactive' }}">
                    В работе ({{ $stats['in_progress'] }})
                </a>
                <a href="{{ route('diagnostic.consultation.expert.dashboard') }}?status=scheduled" 
                   class="py-4 px-6 font-medium {{ $status == 'scheduled' ? 'tab-active' : 'tab-inactive' }}">
                    Запланированы
                </a>
                <a href="{{ route('diagnostic.consultation.expert.dashboard') }}?status=completed" 
                   class="py-4 px-6 font-medium {{ $status == 'completed' ? 'tab-active' : 'tab-inactive' }}">
                    Завершено ({{ $stats['completed'] }})
                </a>
            </nav>
        </div>

        <!-- Список консультаций -->
        <div class="p-6">
            @if($consultations->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Клиент</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Автомобиль</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Тип</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Создана</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($consultations as $consultation)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">#{{ $consultation->id }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $consultation->user->name ?? 'Не указан' }}
                                    </div>
                                    <div class="text-sm text-gray-500">{{ $consultation->user->email ?? '' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        @if($consultation->case && $consultation->case->brand && $consultation->case->model)
                                            {{ $consultation->case->brand->name }} {{ $consultation->case->model->name }}
                                        @else
                                            <span class="text-gray-400">Не указан</span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        @if($consultation->case)
                                            {{ $consultation->case->year ?? '' }} г.
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="type-badge type-{{ $consultation->type }}">
                                        @switch($consultation->type)
                                            @case('basic') Базовая @break
                                            @case('premium') Премиум @break
                                            @case('expert') Экспертная @break
                                        @endswitch
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="status-badge status-{{ $consultation->status }}">
                                        @switch($consultation->status)
                                            @case('pending') ⏳ Ожидание @break
                                            @case('scheduled') 📅 Запланирована @break
                                            @case('in_progress') 🔄 В работе @break
                                            @case('completed') ✅ Завершена @break
                                            @case('cancelled') ❌ Отменена @break
                                        @endswitch
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $consultation->created_at->format('d.m.Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="{{ route('diagnostic.consultation.expert.show', $consultation) }}" 
                                       class="text-blue-600 hover:text-blue-900 mr-4">
                                        <i class="fas fa-eye"></i> Открыть
                                    </a>
                                    @if($consultation->status == 'scheduled')
                                        <form action="{{ route('diagnostic.consultation.expert.start', $consultation) }}" 
                                              method="POST" 
                                              class="inline">
                                            @csrf
                                            <button type="submit" 
                                                    class="text-green-600 hover:text-green-900">
                                                <i class="fas fa-play"></i> Начать
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Пагинация -->
                <div class="mt-6">
                    {{ $consultations->links('vendor.pagination.tailwind') }}
                </div>
            @else
                <div class="text-center py-12">
                    <div class="mb-6">
                        <i class="fas fa-comments text-gray-300 text-6xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Консультаций пока нет</h3>
                    <p class="text-gray-600 mb-6 max-w-md mx-auto">
                        У вас нет консультаций в выбранном статусе.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Графики активности -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Активность по дням</h3>
            <div class="h-64">
                <canvas id="activityChart"></canvas>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Распределение по типам</h3>
            <div class="h-64">
                <canvas id="typeChart"></canvas>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // График активности
        const activityCtx = document.getElementById('activityChart').getContext('2d');
        const activityChart = new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],
                datasets: [{
                    label: 'Консультации',
                    data: [3, 5, 2, 8, 4, 6, 1],
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // График распределения по типам
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        const typeChart = new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: ['Базовая', 'Премиум', 'Экспертная'],
                datasets: [{
                    data: [12, 8, 5],
                    backgroundColor: [
                        '#9ca3af',
                        '#3b82f6',
                        '#8b5cf6'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    });
</script>
@endpush
@endsection