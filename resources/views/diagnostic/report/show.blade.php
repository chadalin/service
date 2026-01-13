@extends('layouts.diagnostic')

@section('title', ' - Просмотр отчёта')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Заголовок и действия -->
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Отчёт по диагностике</h1>
            <p class="text-gray-600">
                Кейс #{{ substr($case->id, 0, 8) }} • 
                Создан {{ $case->created_at->format('d.m.Y H:i') }}
            </p>
        </div>
        
        <div class="flex space-x-3 mt-4 md:mt-0">
            <a href="{{ route('diagnostic.report.pdf', $case->id) }}" 
               class="btn-secondary">
                <i class="fas fa-file-pdf mr-2"></i> PDF
            </a>
            
            <button onclick="window.print()" class="btn-secondary">
                <i class="fas fa-print mr-2"></i> Печать
            </button>
            
            <button onclick="shareReport()" class="btn-secondary">
                <i class="fas fa-share-alt mr-2"></i> Поделиться
            </button>
        </div>
    </div>

    <!-- Основной отчёт -->
    <div class="bg-white shadow-lg rounded-xl overflow-hidden mb-8">
        <!-- Шапка отчёта -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white p-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                <div>
                    <h2 class="text-2xl font-bold mb-2">ОТЧЁТ ПО ДИАГНОСТИКЕ</h2>
                    <p class="text-blue-100">LR Diagnostic Flow • Экспертная система диагностики</p>
                </div>
                <div class="mt-4 md:mt-0 text-right">
                    <div class="text-3xl font-bold">{{ $case->brand->name }}</div>
                    <div class="text-xl">{{ $case->model->name ?? 'Модель не указана' }}</div>
                </div>
            </div>
        </div>

        <!-- Содержимое отчёта -->
        <div class="p-8">
            <!-- Блок с данными авто -->
            <div class="mb-10">
                <h3 class="text-xl font-bold text-gray-800 border-b pb-2 mb-4">Данные автомобиля</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <div class="text-sm text-gray-600">Год выпуска</div>
                        <div class="font-semibold">{{ $case->year ?? 'Не указан' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600">Тип двигателя</div>
                        <div class="font-semibold">{{ $case->engine_type ?? 'Не указан' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600">Пробег</div>
                        <div class="font-semibold">
                            {{ $case->mileage ? number_format($case->mileage, 0, '', ' ') . ' км' : 'Не указан' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600">VIN</div>
                        <div class="font-semibold font-mono">{{ $case->vin ?? 'Не указан' }}</div>
                    </div>
                </div>
            </div>

            <!-- Описанные симптомы -->
            <div class="mb-10">
                <h3 class="text-xl font-bold text-gray-800 border-b pb-2 mb-4">Описанные симптомы</h3>
                <div class="space-y-3">
                    @foreach($case->symptoms as $symptomId)
                        @php
                            $symptom = \App\Models\Diagnostic\Symptom::find($symptomId);
                        @endphp
                        @if($symptom)
                            <div class="flex items-start p-3 bg-blue-50 rounded-lg">
                                <i class="fas fa-exclamation-circle text-blue-500 mr-3 mt-1"></i>
                                <div>
                                    <div class="font-semibold text-gray-800">{{ $symptom->name }}</div>
                                    @if($symptom->description)
                                        <div class="text-sm text-gray-600 mt-1">{{ $symptom->description }}</div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
                
                @if($case->description)
                    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <div class="text-sm text-gray-600 mb-1">Дополнительное описание:</div>
                        <div class="text-gray-800">{{ $case->description }}</div>
                    </div>
                @endif
            </div>

            <!-- Анализ и рекомендации -->
            @if($report = $case->activeReport)
                <div class="mb-10">
                    <h3 class="text-xl font-bold text-gray-800 border-b pb-2 mb-4">Анализ и рекомендации</h3>
                    
                    <!-- Возможные причины -->
                    <div class="mb-6">
                        <h4 class="font-bold text-lg text-gray-700 mb-3 flex items-center">
                            <i class="fas fa-search mr-2 text-blue-500"></i>
                            Возможные причины проблемы
                        </h4>
                        <div class="space-y-2">
                            @foreach($report->possible_causes as $index => $cause)
                                <div class="flex items-start">
                                    <span class="flex-shrink-0 w-6 h-6 bg-red-100 text-red-700 rounded-full flex items-center justify-center text-sm font-bold mr-3">
                                        {{ $index + 1 }}
                                    </span>
                                    <span class="text-gray-700">{{ $cause }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- План диагностики -->
                    <div class="mb-6">
                        <h4 class="font-bold text-lg text-gray-700 mb-3 flex items-center">
                            <i class="fas fa-list-ol mr-2 text-green-500"></i>
                            Пошаговый план диагностики
                        </h4>
                        <div class="space-y-3">
                            @foreach($report->diagnostic_plan as $index => $step)
                                <div class="flex items-start p-3 border border-gray-200 rounded-lg">
                                    <span class="flex-shrink-0 w-8 h-8 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center font-bold mr-4">
                                        {{ $index + 1 }}
                                    </span>
                                    <div class="flex-1">
                                        <div class="text-gray-800">{{ $step }}</div>
                                        @if($index === 0)
                                            <div class="text-sm text-gray-500 mt-1">
                                                <i class="fas fa-lightbulb mr-1"></i>
                                                Рекомендуем начать с этого шага
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Оценка стоимости -->
                    @if($report->estimated_costs)
                        <div class="mb-6">
                            <h4 class="font-bold text-lg text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-calculator mr-2 text-purple-500"></i>
                                Оценка стоимости ремонта
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                @foreach($report->estimated_costs as $type => $cost)
                                    @if($type !== 'total')
                                        <div class="p-4 bg-gray-50 rounded-lg">
                                            <div class="text-sm text-gray-600 mb-1 capitalize">{{ str_replace('_', ' ', $type) }}</div>
                                            <div class="text-xl font-bold text-gray-800">{{ number_format($cost, 0, '', ' ') }} ₽</div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            @if(isset($report->estimated_costs['total']))
                                <div class="mt-4 p-4 bg-gradient-to-r from-blue-50 to-blue-100 border border-blue-200 rounded-lg">
                                    <div class="flex justify-between items-center">
                                        <div class="text-lg font-bold text-gray-800">Общая ориентировочная стоимость</div>
                                        <div class="text-2xl font-bold text-blue-700">
                                            {{ number_format($report->estimated_costs['total'], 0, '', ' ') }} ₽
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Рекомендуемые действия -->
                    @if($report->recommended_actions)
                        <div>
                            <h4 class="font-bold text-lg text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-check-circle mr-2 text-green-500"></i>
                                Рекомендуемые действия
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($report->recommended_actions as $action)
                                    <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                                        <div class="flex items-center">
                                            <i class="fas fa-check text-green-500 mr-2"></i>
                                            <span class="text-gray-700">{{ $action }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Загруженные файлы -->
            @if($case->uploaded_files && count($case->uploaded_files) > 0)
                <div class="mb-10">
                    <h3 class="text-xl font-bold text-gray-800 border-b pb-2 mb-4">Загруженные материалы</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @foreach($case->uploaded_files as $file)
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <div class="aspect-square bg-gray-100 flex items-center justify-center">
                                    @if(str_contains($file['type'] ?? '', 'image'))
                                        <i class="fas fa-image text-gray-400 text-3xl"></i>
                                    @elseif(str_contains($file['type'] ?? '', 'video'))
                                        <i class="fas fa-video text-gray-400 text-3xl"></i>
                                    @else
                                        <i class="fas fa-file text-gray-400 text-3xl"></i>
                                    @endif
                                </div>
                                <div class="p-2">
                                    <p class="text-xs text-gray-600 truncate">{{ $file['name'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Информация для сервиса -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-10">
                <h3 class="font-bold text-yellow-800 mb-3 flex items-center">
                    <i class="fas fa-tools mr-2"></i>
                    Информация для сервисного центра
                </h3>
                <div class="text-yellow-700 text-sm space-y-2">
                    <p>• Данный отчёт содержит предварительную диагностику на основе описанных симптомов</p>
                    <p>• Для точной диагностики требуется инструментальная проверка</p>
                    <p>• Рекомендуем проверить все указанные в отчёте возможные причины</p>
                    <p>• При возникновении вопросов, покажите этот отчёт специалисту</p>
                </div>
            </div>

            <!-- Контакты поддержки -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h3 class="font-bold text-blue-800 mb-3">Нужна помощь специалиста?</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-blue-700">
                    <div class="flex items-center">
                        <i class="fas fa-headset text-xl mr-3"></i>
                        <div>
                            <div class="font-semibold">Консультация эксперта</div>
                            <div class="text-sm">Личный разбор вашего случая</div>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <i class="fab fa-telegram text-xl mr-3"></i>
                        <div>
                            <div class="font-semibold">Telegram-бот</div>
                            <div class="text-sm">@lrdiagnostic_bot</div>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-phone text-xl mr-3"></i>
                        <div>
                            <div class="font-semibold">Телефон</div>
                            <div class="text-sm">+7 (999) 123-45-67</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Футер отчёта -->
        <div class="bg-gray-800 text-white p-6">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div>
                    <div class="text-sm text-gray-300">Сгенерировано системой LR Diagnostic Flow</div>
                    <div class="text-xs text-gray-400">{{ now()->format('d.m.Y H:i:s') }}</div>
                </div>
                <div class="mt-4 md:mt-0">
                    <div class="text-sm text-gray-300">www.lrdiagnostic.ru</div>
                    <div class="text-xs text-gray-400">ID отчёта: {{ $case->id }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Кнопки действий -->
    <div class="flex justify-center space-x-4">
        <a href="{{ route('diagnostic.result', $case->id) }}" class="btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Вернуться к результатам
        </a>
        
        @if(!$case->consultation)
            <form action="{{ route('diagnostic.consultation.order', $case->id) }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="expert">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-headset mr-2"></i> Заказать консультацию эксперта
                </button>
            </form>
        @else
            <a href="#" class="btn-primary">
                <i class="fas fa-check mr-2"></i> Консультация уже заказана
            </a>
        @endif
    </div>
</div>

@push('scripts')
<script>
function shareReport() {
    if (navigator.share) {
        navigator.share({
            title: 'Отчёт по диагностике Land Rover',
            text: 'Отчёт по диагностике моего Land Rover через LR Diagnostic Flow',
            url: window.location.href
        });
    } else {
        // Fallback для копирования ссылки
        navigator.clipboard.writeText(window.location.href);
        alert('Ссылка на отчёт скопирована в буфер обмена');
    }
}

// Печатная версия
window.addEventListener('beforeprint', function() {
    document.querySelector('header').style.display = 'none';
    document.querySelector('footer').style.display = 'none';
    document.querySelector('main > div:first-child').style.display = 'none';
});

window.addEventListener('afterprint', function() {
    document.querySelector('header').style.display = '';
    document.querySelector('footer').style.display = '';
    document.querySelector('main > div:first-child').style.display = '';
});
</script>
@endpush
@endsection