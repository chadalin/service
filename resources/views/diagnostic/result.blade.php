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

<div class="max-w-4xl mx-auto">
    <!-- Заголовок -->
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Результат диагностики</h1>
        <p class="text-gray-600 text-sm">На основе предоставленных данных</p>
    </div>

    <!-- Информация о кейсе -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-gray-800 text-lg mb-1">
                    @if($case->brand)
                        {{ $case->brand->name }}
                    @else
                        Автомобиль
                    @endif
                    @if($case->model)
                        {{ $case->model->name }}
                    @endif
                </h2>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($case->symptoms as $symptomId)
                        @php
                            $symptom = \App\Models\Diagnostic\Symptom::find($symptomId);
                        @endphp
                        @if($symptom)
                            <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">
                                {{ $symptom->name }}
                            </span>
                        @endif
                    @endforeach
                </div>
            </div>
            
            <div class="flex items-center space-x-3">
                <div class="text-center">
                    <div class="text-xs text-gray-600 mb-0.5">Сложность</div>
                    <div class="complexity-badge complexity-{{ $complexity }} text-xs px-2 py-0.5">
                        {{ $complexity }}/10
                    </div>
                </div>
                
                <div class="text-center">
                    <div class="text-xs text-gray-600 mb-0.5">Время</div>
                    <div class="text-sm font-bold text-gray-800">{{ $estimatedTime }} мин</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Возможные причины -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4">
        <div class="flex items-center mb-3">
            <div class="bg-red-100 p-1.5 rounded-lg mr-2">
                <i class="fas fa-exclamation-circle text-red-600 text-sm"></i>
            </div>
            <h3 class="font-bold text-gray-800">Возможные причины</h3>
        </div>
        
        <div class="space-y-2">
            @foreach($result['possible_causes'] ?? [] as $index => $cause)
                <div class="p-3 border border-gray-200 rounded-lg hover:border-red-200 transition-colors">
                    <div class="flex items-start">
                        <div class="mr-2 flex-shrink-0">
                            <span class="flex items-center justify-center w-5 h-5 bg-red-100 text-red-700 rounded-full text-xs font-bold">
                                {{ $index + 1 }}
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-800 text-sm mb-0.5">{{ $cause }}</p>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">
                                    Вероятность: 
                                    <span class="font-bold {{ $index === 0 ? 'text-green-600' : 'text-yellow-600' }}">
                                        {{ 100 - ($index * 20) }}%
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- План диагностики -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4">
        <div class="flex items-center mb-3">
            <div class="bg-blue-100 p-1.5 rounded-lg mr-2">
                <i class="fas fa-clipboard-list text-blue-600 text-sm"></i>
            </div>
            <h3 class="font-bold text-gray-800">План диагностики</h3>
        </div>
        
        <div class="space-y-2">
            @foreach($result['diagnostic_steps'] ?? [] as $index => $step)
                <div class="flex items-start p-3 bg-blue-50 rounded-lg">
                    <div class="mr-2 flex-shrink-0">
                        <span class="flex items-center justify-center w-6 h-6 bg-blue-100 text-blue-700 rounded-full text-xs font-bold">
                            {{ $index + 1 }}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-gray-800 text-sm">{{ $step }}</p>
                        @if($index === 0)
                            <p class="text-xs text-gray-500 mt-0.5">
                                <i class="fas fa-star text-yellow-500 mr-0.5"></i>
                                Начните с этого шага
                            </p>
                        @endif
                    </div>
                    <div class="ml-2 text-xs text-gray-500 whitespace-nowrap">
                        ~{{ ceil($estimatedTime / max(count($result['diagnostic_steps'] ?? []), 1)) }} мин
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Что понадобится -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
        <div class="flex items-center mb-3">
            <div class="bg-yellow-100 p-1.5 rounded-lg mr-2">
                <i class="fas fa-tools text-yellow-600 text-sm"></i>
            </div>
            <h3 class="font-bold text-gray-800">Что потребуется</h3>
        </div>
        
        <div class="flex flex-wrap gap-2">
            @foreach($result['required_data'] ?? [] as $item)
                <div class="px-3 py-1.5 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-500 text-xs mr-1.5"></i>
                        <span class="text-gray-800 text-xs">{{ $item }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Варианты отчётов -->
    <div class="mb-6">
        <div class="flex items-center mb-3">
            <div class="bg-green-100 p-1.5 rounded-lg mr-2">
                <i class="fas fa-file-alt text-green-600 text-sm"></i>
            </div>
            <h3 class="font-bold text-gray-800">Получить отчёт</h3>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <!-- Базовый -->
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-center mb-3">
                    <div class="text-xs text-gray-500 mb-1">Базовый</div>
                    <div class="text-xl font-bold text-blue-700 mb-1">500 ₽</div>
                    <p class="text-xs text-gray-600">Отчёт + план</p>
                </div>
                <ul class="space-y-1.5 text-xs text-gray-700 mb-4">
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 text-xs mr-1.5 mt-0.5"></i>
                        <span>Полный анализ</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 text-xs mr-1.5 mt-0.5"></i>
                        <span>PDF отчёт</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 text-xs mr-1.5 mt-0.5"></i>
                        <span>Список запчастей</span>
                    </li>
                </ul>
                <button type="button" 
                        onclick="selectPlan('basic')"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 text-xs rounded-lg transition-colors">
                    Получить
                </button><a href="{{ route('consultation.order.form', ['case' => $case->id, 'type' => 'basic']) }}" 
   class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 text-xs rounded-lg transition-colors text-center">
    Получить
</a>
            </div>
            
            <!-- Премиум -->
            <div class="bg-white border border-purple-200 rounded-xl p-4 relative">
                <div class="absolute top-0 right-0 bg-purple-600 text-white px-2 py-0.5 rounded-bl-lg text-xs font-bold">
                    ЛУЧШИЙ
                </div>
                <div class="text-center mb-3">
                    <div class="text-xs text-gray-500 mb-1">Премиум</div>
                    <div class="text-xl font-bold text-purple-700 mb-1">1 500 ₽</div>
                    <p class="text-xs text-gray-600">Расширенный</p>
                </div>
                <ul class="space-y-1.5 text-xs text-gray-700 mb-4">
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 text-xs mr-1.5 mt-0.5"></i>
                        <span>Всё из базового</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 text-xs mr-1.5 mt-0.5"></i>
                        <span>Видео-инструкции</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 text-xs mr-1.5 mt-0.5"></i>
                        <span>Рейтинг сервисов</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 text-xs mr-1.5 mt-0.5"></i>
                        <span>Чат с помощником</span>
                    </li>
                </ul>
                <a href="{{ route('consultation.order.form', ['case' => $case->id, 'type' => 'basic']) }}" 
   class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 text-xs rounded-lg transition-colors text-center">
    Получить
</a>
            </div>
            
            <!-- Эксперт -->
            <div class="bg-white border border-green-200 rounded-xl p-4">
                <div class="text-center mb-3">
                    <div class="text-xs text-gray-500 mb-1">Эксперт</div>
                    <div class="text-xl font-bold text-green-700 mb-1">{{ number_format($estimatedPrice, 0, '', ' ') }} ₽</div>
                    <p class="text-xs text-gray-600">Личная консультация</p>
                </div>
                <ul class="space-y-1.5 text-xs text-gray-700 mb-4">
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 text-xs mr-1.5 mt-0.5"></i>
                        <span>Всё из премиум</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 text-xs mr-1.5 mt-0.5"></i>
                        <span>Личный разбор</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 text-xs mr-1.5 mt-0.5"></i>
                        <span>Выбор сервиса</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 text-xs mr-1.5 mt-0.5"></i>
                        <span>Гарантия точности</span>
                    </li>
                </ul>
               <a href="{{ route('consultation.order.form', ['case' => $case->id, 'type' => 'expert']) }}" 
   class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 text-xs rounded-lg transition-colors text-center">
    Консультация
</a>
            </div>
        </div>
    </div>

    <!-- Форма выбора плана -->
    <form id="orderForm" action="{{ route('diagnostic.consultation.order', $case->id) }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="type" id="selectedPlan">
    </form>

    <!-- Кнопки действий -->
    <div class="flex flex-col sm:flex-row justify-between items-center gap-3 mb-6">
        <div class="order-2 sm:order-1">
            <div class="flex items-center text-gray-600 text-xs">
                <i class="fas fa-shield-alt mr-1.5"></i>
                <span>Данные защищены</span>
            </div>
        </div>
        
        <div class="flex space-x-2 w-full sm:w-auto order-1 sm:order-2">
            <a href="{{ route('diagnostic.start') }}" 
               class="btn-secondary py-2.5 px-4 text-xs flex-1 sm:flex-none text-center">
                <i class="fas fa-redo mr-1.5"></i> Новая
            </a>
            <button type="button" onclick="window.print()" 
                    class="btn-secondary py-2.5 px-4 text-xs flex-1 sm:flex-none text-center">
                <i class="fas fa-print mr-1.5"></i> Печать
            </button>
        </div>
    </div>

    <!-- Важная информация -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
        <div class="flex items-start">
            <div class="mr-2">
                <i class="fas fa-info-circle text-blue-600"></i>
            </div>
            <div>
                <h3 class="font-bold text-blue-800 mb-1 text-sm">Важная информация</h3>
                <div class="text-blue-700 text-xs space-y-1.5">
                    <p>• Система не заменяет очный осмотр специалиста</p>
                    <p>• Сохраните отчёт для показа специалисту</p>
                    <p>• При сложных случаях рекомендуем консультацию эксперта</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function selectPlan(type) {
    window.location.href = "{{ route('consultation.order.form', [$case->id, '']) }}" + type;
}

// Сохранение кейса в localStorage
window.addEventListener('DOMContentLoaded', function() {
    const caseData = {
        id: '{{ $case->id }}',
        symptoms: @json($case->symptoms),
        brand: '{{ $case->brand->name ?? "" }}',
        model: '{{ $case->model->name ?? "" }}',
        timestamp: new Date().toISOString()
    };
    
    localStorage.setItem('lastDiagnosticCase', JSON.stringify(caseData));
});

// Адаптивность для мобильных
function adjustForMobile() {
    if (window.innerWidth < 640) {
        // Уменьшаем отступы на мобильных
        document.querySelectorAll('.p-4').forEach(el => {
            el.classList.replace('p-4', 'p-3');
        });
    }
}

adjustForMobile();
window.addEventListener('resize', adjustForMobile);
</script>

<style>
/* Дополнительные стили для мобильных */
@media (max-width: 640px) {
    .grid-cols-1.md\:grid-cols-3 {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .btn-secondary, .btn-primary {
        padding: 10px 14px;
        font-size: 13px;
    }
    
    .complexity-badge {
        font-size: 11px;
        padding: 2px 6px;
    }
}

/* Анимации */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.bg-white {
    animation: fadeInUp 0.3s ease-out;
}

.bg-white:nth-child(1) { animation-delay: 0.1s; }
.bg-white:nth-child(2) { animation-delay: 0.2s; }
.bg-white:nth-child(3) { animation-delay: 0.3s; }
</style>
@endpush
@endsection