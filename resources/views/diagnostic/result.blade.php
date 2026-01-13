@extends('layouts.diagnostic')

@section('title', ' - Результат диагностики')

@section('content')
@php
    $showProgress = true;
    $currentStep = 4;
    
    $result = $case->analysis_result;
    $complexity = $case->rule->complexity_level ?? 1;
    $estimatedPrice = $result['estimated_price'] ?? 0;
    $estimatedTime = $result['estimated_time'] ?? 0;
@endphp

<div class="max-w-6xl mx-auto">
    <!-- Заголовок -->
    <div class="text-center mb-10">
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Результат предварительной диагностики</h1>
        <p class="text-gray-600 text-lg">
            На основе ваших данных система подготовила анализ проблемы.
        </p>
    </div>

    <!-- Карточка с результатами -->
    <div class="diagnostic-card p-8 mb-8">
        <!-- Заголовок и мета-информация -->
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 pb-6 border-b border-gray-200">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">
                    {{ $case->brand->name }} 
                    @if($case->model)
                        {{ $case->model->name }}
                    @endif
                </h2>
                <div class="flex flex-wrap gap-2">
                    @foreach($case->symptoms as $symptomId)
                        @php
                            $symptom = \App\Models\Diagnostic\Symptom::find($symptomId);
                        @endphp
                        @if($symptom)
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">
                                {{ $symptom->name }}
                            </span>
                        @endif
                    @endforeach
                </div>
            </div>
            
            <div class="mt-4 md:mt-0">
                <div class="flex items-center space-x-4">
                    <div class="text-center">
                        <div class="text-sm text-gray-600 mb-1">Сложность</div>
                        <div class="complexity-badge complexity-{{ $complexity }}">
                            {{ $complexity }}/10
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <div class="text-sm text-gray-600 mb-1">Примерное время</div>
                        <div class="text-lg font-bold text-gray-800">{{ $estimatedTime }} мин</div>
                    </div>
                    
                    <div class="text-center">
                        <div class="text-sm text-gray-600 mb-1">ID кейса</div>
                        <div class="text-sm font-mono text-gray-500">{{ substr($case->id, 0, 8) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Основные результаты -->
        <div class="space-y-8">
            <!-- Возможные причины -->
            <section>
                <div class="flex items-center mb-4">
                    <div class="bg-red-100 p-2 rounded-lg mr-3">
                        <i class="fas fa-exclamation-circle text-red-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800">Возможные причины проблемы</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($result['possible_causes'] ?? [] as $index => $cause)
                        <div class="p-4 border border-gray-200 rounded-lg hover:border-red-300 transition-colors">
                            <div class="flex items-start">
                                <div class="mr-3 mt-1">
                                    <span class="flex items-center justify-center w-6 h-6 bg-red-100 text-red-700 rounded-full text-sm font-bold">
                                        {{ $index + 1 }}
                                    </span>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-800 mb-1">{{ $cause }}</h4>
                                    <p class="text-sm text-gray-600">
                                        Вероятность: 
                                        @php
                                            $probability = 100 - ($index * 20);
                                            $probability = max($probability, 10);
                                        @endphp
                                        <span class="font-bold {{ $index === 0 ? 'text-green-600' : 'text-yellow-600' }}">
                                            {{ $probability }}%
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <!-- План диагностики -->
            <section>
                <div class="flex items-center mb-4">
                    <div class="bg-blue-100 p-2 rounded-lg mr-3">
                        <i class="fas fa-clipboard-list text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800">Пошаговый план диагностики</h3>
                </div>
                
                <div class="space-y-3">
                    @foreach($result['diagnostic_steps'] ?? [] as $index => $step)
                        <div class="flex items-start p-4 bg-gray-50 rounded-lg">
                            <div class="mr-4">
                                <span class="flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-700 rounded-full font-bold">
                                    {{ $index + 1 }}
                                </span>
                            </div>
                            <div class="flex-1">
                                <p class="text-gray-800">{{ $step }}</p>
                                @if($index === 0)
                                    <p class="text-sm text-gray-500 mt-1">
                                        <i class="fas fa-clock mr-1"></i>
                                        Рекомендуем начать с этого шага
                                    </p>
                                @endif
                            </div>
                            <div class="text-sm text-gray-500">
                                ~{{ ceil($estimatedTime / count($result['diagnostic_steps'] ?? [1])) }} мин
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <!-- Необходимые данные -->
            <section>
                <div class="flex items-center mb-4">
                    <div class="bg-yellow-100 p-2 rounded-lg mr-3">
                        <i class="fas fa-tools text-yellow-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800">Что понадобится для диагностики</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($result['required_data'] ?? [] as $item)
                        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                <span class="font-medium text-gray-800">{{ $item }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <!-- Оценка стоимости -->
            <section>
                <div class="flex items-center mb-4">
                    <div class="bg-green-100 p-2 rounded-lg mr-3">
                        <i class="fas fa-ruble-sign text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800">Оценка стоимости</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="p-6 bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl">
                        <div class="text-center mb-4">
                            <i class="fas fa-chart-line text-blue-600 text-3xl mb-3"></i>
                            <h4 class="font-bold text-gray-800 mb-2">Базовая диагностика</h4>
                            <div class="text-3xl font-bold text-blue-700 mb-1">500 ₽</div>
                            <p class="text-sm text-gray-600">Подробный отчёт + пошаговый план</p>
                        </div>
                        <ul class="space-y-2 text-sm text-gray-700 mb-6">
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                Полный анализ проблемы
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                PDF отчёт с деталями
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                Список запчастей
                            </li>
                        </ul>
                        <button type="button" 
                                onclick="selectPlan('basic')"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition-colors">
                            Получить отчёт
                        </button>
                    </div>
                    
                    <div class="p-6 bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-xl relative">
                        <div class="absolute top-0 right-0 bg-purple-600 text-white px-3 py-1 rounded-bl-lg rounded-tr-xl text-sm font-bold">
                            ПОПУЛЯРНО
                        </div>
                        <div class="text-center mb-4">
                            <i class="fas fa-user-tie text-purple-600 text-3xl mb-3"></i>
                            <h4 class="font-bold text-gray-800 mb-2">Премиум отчёт</h4>
                            <div class="text-3xl font-bold text-purple-700 mb-1">1 500 ₽</div>
                            <p class="text-sm text-gray-600">Расширенный анализ + приоритет</p>
                        </div>
                        <ul class="space-y-2 text-sm text-gray-700 mb-6">
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                Всё из базового
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                Видео-инструкции
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                Рейтинг сервисов в вашем городе
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                Чат с помощником
                            </li>
                        </ul>
                        <button type="button" 
                                onclick="selectPlan('premium')"
                                class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 rounded-lg transition-colors">
                            Выбрать премиум
                        </button>
                    </div>
                    
                    <div class="p-6 bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl">
                        <div class="text-center mb-4">
                            <i class="fas fa-headset text-green-600 text-3xl mb-3"></i>
                            <h4 class="font-bold text-gray-800 mb-2">Консультация эксперта</h4>
                            <div class="text-3xl font-bold text-green-700 mb-1">{{ number_format($estimatedPrice, 0, '', ' ') }} ₽</div>
                            <p class="text-sm text-gray-600">Личная консультация + полный аудит</p>
                        </div>
                        <ul class="space-y-2 text-sm text-gray-700 mb-6">
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                Всё из премиум
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                Личный разбор от специалиста
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                Помощь в выборе сервиса
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                Гарантия правильности диагноза
                            </li>
                        </ul>
                        <button type="button" 
                                onclick="selectPlan('expert')"
                                class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 rounded-lg transition-colors">
                            Заказать консультацию
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Форма выбора плана (скрытая) -->
    <form id="orderForm" action="{{ route('diagnostic.consultation.order', $case->id) }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="type" id="selectedPlan">
    </form>

    <!-- Действия -->
    <div class="flex justify-between items-center mt-10">
        <div class="flex space-x-4">
            <a href="{{ route('diagnostic.report.pdf', $case->id) }}" 
               class="btn-secondary">
                <i class="fas fa-file-pdf mr-2"></i> Скачать PDF
            </a>
            <button type="button" onclick="window.print()" class="btn-secondary">
                <i class="fas fa-print mr-2"></i> Печать
            </button>
        </div>
        
        <div>
            <a href="{{ route('diagnostic.start') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                <i class="fas fa-redo mr-1"></i> Новая диагностика
            </a>
        </div>
    </div>

    <!-- Важная информация -->
    <div class="mt-12 p-6 bg-gradient-to-r from-blue-50 to-blue-100 border border-blue-200 rounded-xl">
        <div class="flex items-start">
            <div class="mr-4">
                <i class="fas fa-shield-alt text-blue-600 text-2xl"></i>
            </div>
            <div>
                <h3 class="font-bold text-blue-800 mb-2">Важная информация</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-blue-700">
                    <div>
                        <h4 class="font-semibold mb-1">Ограничения системы</h4>
                        <ul class="text-sm space-y-1">
                            <li>• Система не заменяет очный осмотр специалиста</li>
                            <li>• Рекомендации основаны на статистике и опыте</li>
                            <li>• Точный диагноз требует инструментальной проверки</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-1">Что делать дальше?</h4>
                        <ul class="text-sm space-y-1">
                            <li>• Сохраните этот отчёт для показа специалисту</li>
                            <li>• Следуйте пошаговому плану диагностики</li>
                            <li>• При сложных случаях рекомендуем консультацию эксперта</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function selectPlan(type) {
    document.getElementById('selectedPlan').value = type;
    document.getElementById('orderForm').submit();
}

// Сохранение кейса в localStorage для продолжения позже
window.addEventListener('DOMContentLoaded', function() {
    const caseData = {
        id: '{{ $case->id }}',
        symptoms: @json($case->symptoms),
        brand: '{{ $case->brand->name }}',
        model: '{{ $case->model->name ?? "" }}',
        timestamp: new Date().toISOString()
    };
    
    localStorage.setItem('lastDiagnosticCase', JSON.stringify(caseData));
});

// Поделиться результатом
function shareResult() {
    if (navigator.share) {
        navigator.share({
            title: 'Результат диагностики Land Rover',
            text: 'Получил предварительную диагностику моего Land Rover через LR Diagnostic Flow',
            url: window.location.href
        });
    } else {
        // Fallback для копирования ссылки
        navigator.clipboard.writeText(window.location.href);
        alert('Ссылка скопирована в буфер обмена');
    }
}
</script>
@endpush
@endsection