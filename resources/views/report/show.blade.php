@extends('layouts.diagnostic')

@section('title', 'Диагностический отчет')

@section('content')
@php
    // Простая функция для форматирования денег
    function money($value) {
        if (!is_numeric($value)) return '—';
        $num = floatval($value);
        if ($num <= 0) return '—';
        return number_format($num, 0, '', ' ') . ' ₽';
    }
    
    // Простая функция для форматирования чисел
    function num($value, $decimals = 0) {
        if (!is_numeric($value)) return '—';
        return number_format(floatval($value), $decimals, '', ' ');
    }
@endphp
@php
    $report = $case->activeReport;
    $brand = $case->brand ?? new class {
        public $name = 'Не указана';
        public $id = null;
    };
    $model = $case->model ?? new class {
        public $name = 'Не указана';
        public $id = null;
    };
    
    // Безопасный доступ к данным отчета
    $summary = is_array($report->summary ?? null) ? $report->summary : [];
    $possibleCauses = is_array($report->possible_causes ?? null) ? $report->possible_causes : [];
    $diagnosticPlan = is_array($report->diagnostic_plan ?? null) ? $report->diagnostic_plan : [];
    $estimatedCosts = is_array($report->estimated_costs ?? null) ? $report->estimated_costs : [];
    $recommendedActions = is_array($report->recommended_actions ?? null) ? $report->recommended_actions : [];
    $partsList = is_array($report->parts_list ?? null) ? $report->parts_list : [];
    
    // Функция для безопасного форматирования чисел
    function formatPrice($value) {
        if (!is_numeric($value)) {
            return '—';
        }
        
        $numericValue = (float) $value;
        
        if ($numericValue <= 0) {
            return '—';
        }
        
        return number_format($numericValue, 0, '', ' ') . ' ₽';
    }
    
    // Функция для безопасного форматирования названий
    function formatCostType($type) {
        $typeNames = [
            'diagnostic' => 'Диагностика',
            'work' => 'Работы',
            'total_parts' => 'Запчасти',
            'parts' => 'Запчасти',
            'labor' => 'Работа мастера',
            'total_labor' => 'Работа мастера',
            'materials' => 'Материалы',
            'other' => 'Прочее',
        ];
        
        return $typeNames[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }
@endphp

<div class="min-h-screen bg-gray-50">
    <!-- Шапка отчета -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white">
        <div class="container mx-auto px-4 py-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                <div>
                    <h1 class="text-2xl font-bold mb-2">Диагностический отчет</h1>
                    <div class="text-blue-100 text-sm">
                        <div class="flex items-center mb-1">
                            <i class="fas fa-car mr-2"></i>
                            <span>{{ $brand->name }} {{ $model->name }}</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-calendar mr-2"></i>
                            <span>Год: {{ $case->year ?? '—' }} • Пробег: {{ $case->mileage && is_numeric($case->mileage) ? number_format((float)$case->mileage, 0, '', ' ') . ' км' : '—' }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 md:mt-0">
                    <div class="bg-blue-500/20 backdrop-blur-sm rounded-lg p-4">
                        <div class="text-center">
                            <div class="text-3xl font-bold">{{ formatPrice($report->getTotalCost()) }}</div>
                            <div class="text-sm text-blue-200">Ориентировочная стоимость</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ID отчета -->
            <div class="mt-4 text-sm">
                <span class="bg-blue-500/30 px-3 py-1 rounded-full">ID: {{ substr($case->id, 0, 8) }}</span>
                <span class="ml-2">{{ $report->created_at->format('d.m.Y H:i') }}</span>
            </div>
        </div>
    </div>

    <!-- Основное содержимое -->
    <div class="container mx-auto px-4 py-6">
        <!-- Кнопки действий -->
        <div class="mb-6 flex flex-col sm:flex-row gap-3">
            <a href="{{ route('diagnostic.report.pdf', $case->id) }}" 
               class="btn-primary flex items-center justify-center py-3 px-4">
                <i class="fas fa-file-pdf mr-2"></i> Скачать PDF
            </a>
            
            <button onclick="printReport()" 
                    class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-3 px-4 rounded-lg shadow hover:shadow-md transition-all duration-300 flex items-center justify-center">
                <i class="fas fa-print mr-2"></i> Печать
            </button>
            
            <button onclick="shareReport()" 
                    class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg shadow hover:shadow-md transition-all duration-300 flex items-center justify-center">
                <i class="fas fa-share-alt mr-2"></i> Поделиться
            </button>
        </div>

        <!-- Краткая сводка -->
        @if(!empty($summary))
        <div class="bg-white rounded-xl shadow-sm p-4 md:p-6 mb-6">
            <div class="flex items-center mb-4">
                <div class="bg-blue-100 p-2 rounded-lg mr-3">
                    <i class="fas fa-clipboard-check text-blue-600"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-800">Краткая сводка</h2>
            </div>
            
            <div class="space-y-3">
                @foreach($summary as $key => $item)
                    @if(is_string($item))
                    <div class="flex items-start">
                        <div class="mr-3 mt-1">
                            <i class="fas fa-circle text-blue-500 text-xs"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-gray-700">{{ $item }}</p>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        @endif

        <!-- Возможные причины -->
        @if(!empty($possibleCauses))
        <div class="bg-white rounded-xl shadow-sm p-4 md:p-6 mb-6">
            <div class="flex items-center mb-4">
                <div class="bg-red-100 p-2 rounded-lg mr-3">
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-800">Возможные причины</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($possibleCauses as $index => $cause)
                    @if(is_string($cause) || is_array($cause))
                    <div class="border border-gray-200 rounded-lg p-4 hover:border-red-300 transition-colors">
                        <div class="flex items-start">
                            <div class="mr-3">
                                <span class="flex items-center justify-center w-6 h-6 bg-red-100 text-red-700 rounded-full text-sm font-bold">
                                    {{ $index + 1 }}
                                </span>
                            </div>
                            <div>
                                @if(is_array($cause))
                                    <h3 class="font-semibold text-gray-800 mb-1">{{ $cause['title'] ?? 'Причина' }}</h3>
                                    @if(isset($cause['description']) && is_string($cause['description']))
                                        <p class="text-sm text-gray-600">{{ $cause['description'] }}</p>
                                    @endif
                                    @if(isset($cause['probability']) && is_numeric($cause['probability']))
                                        <div class="mt-2">
                                            <div class="text-xs text-gray-500 mb-1">Вероятность</div>
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div class="bg-red-500 h-2 rounded-full" style="width: {{ min(100, max(0, (float)$cause['probability'])) }}%"></div>
                                            </div>
                                            <div class="text-xs text-red-600 font-bold mt-1">{{ (float)$cause['probability'] }}%</div>
                                        </div>
                                    @endif
                                @else
                                    <h3 class="font-semibold text-gray-800 mb-1">{{ $cause }}</h3>
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
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        @endif

        <!-- План диагностики -->
        @if(!empty($diagnosticPlan))
        <div class="bg-white rounded-xl shadow-sm p-4 md:p-6 mb-6">
            <div class="flex items-center mb-4">
                <div class="bg-yellow-100 p-2 rounded-lg mr-3">
                    <i class="fas fa-tools text-yellow-600"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-800">План диагностики</h2>
            </div>
            
            <div class="space-y-3">
                @foreach($diagnosticPlan as $index => $step)
                    @if(is_string($step) || is_array($step))
                    <div class="flex items-start p-4 bg-gray-50 rounded-lg hover:bg-yellow-50 transition-colors">
                        <div class="mr-4">
                            <span class="flex items-center justify-center w-8 h-8 bg-yellow-100 text-yellow-700 rounded-full font-bold">
                                {{ $index + 1 }}
                            </span>
                        </div>
                        <div class="flex-1">
                            @if(is_array($step))
                                <p class="text-gray-800">{{ $step['title'] ?? 'Шаг диагностики' }}</p>
                                @if(isset($step['description']) && is_string($step['description']))
                                    <p class="text-sm text-gray-600 mt-1">{{ $step['description'] }}</p>
                                @endif
                                @if(isset($step['estimated_time']) && is_numeric($step['estimated_time']))
                                    <div class="mt-2 text-sm text-gray-500">
                                        <i class="fas fa-clock mr-1"></i>
                                        Примерное время: {{ (float)$step['estimated_time'] }} мин.
                                    </div>
                                @endif
                            @else
                                <p class="text-gray-800">{{ $step }}</p>
                                @if($index === 0)
                                    <p class="text-sm text-gray-500 mt-1">
                                        <i class="fas fa-clock mr-1"></i>
                                        Рекомендуем начать с этого шага
                                    </p>
                                @endif
                            @endif
                        </div>
                        @if(is_array($step) && isset($step['estimated_time']) && is_numeric($step['estimated_time']))
                            <div class="text-sm text-gray-500">
                                ~{{ ceil((float)$step['estimated_time']) }} мин
                            </div>
                        @endif
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        @endif

        <!-- Список запчастей -->
        @if(!empty($partsList))
        <div class="bg-white rounded-xl shadow-sm p-4 md:p-6 mb-6">
            <div class="flex items-center mb-4">
                <div class="bg-green-100 p-2 rounded-lg mr-3">
                    <i class="fas fa-box text-green-600"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-800">Рекомендуемые запчасти</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Название</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Артикул</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Кол-во</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Цена</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($partsList as $part)
                            @if(is_array($part))
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-800">{{ $part['name'] ?? '—' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 font-mono">{{ $part['code'] ?? '—' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                    @if(isset($part['quantity']) && is_numeric($part['quantity']))
                                        {{ (float)$part['quantity'] }}
                                    @else
                                        1
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-green-600">
                                    {{ formatPrice($part['price'] ?? null) }}
                                </td>
                            </tr>
                            @endif
                        @endforeach
                        @if(isset($estimatedCosts['total_parts']) && is_numeric($estimatedCosts['total_parts']))
                        <tr class="bg-gray-50 font-bold">
                            <td colspan="3" class="px-4 py-3 text-right text-sm">Итого запчасти:</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-green-700">
                                {{ formatPrice($estimatedCosts['total_parts']) }}
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Ориентировочная стоимость -->
      <!-- Ориентировочная стоимость -->
@if(!empty($estimatedCosts))
<div class="bg-white rounded-xl shadow-sm p-4 md:p-6 mb-6">
    <div class="flex items-center mb-4">
        <div class="bg-purple-100 p-2 rounded-lg mr-3">
            <i class="fas fa-calculator text-purple-600"></i>
        </div>
        <h2 class="text-xl font-bold text-gray-800">Ориентировочная стоимость</h2>
    </div>
    
    <div class="space-y-4">
        @if(isset($estimatedCosts['diagnostic']))
        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
            <span class="text-gray-700">Диагностика</span>
            <span class="font-semibold">{{ number_format(floatval($estimatedCosts['diagnostic']), 0, '', ' ') }} ₽</span>
        </div>
        @endif
        
        @if(isset($estimatedCosts['work']))
        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
            <span class="text-gray-700">Работы</span>
            <span class="font-semibold">{{ number_format(floatval($estimatedCosts['work']), 0, '', ' ') }} ₽</span>
        </div>
        @endif
        
        @if(isset($estimatedCosts['total_parts']))
        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
            <span class="text-gray-700">Запчасти</span>
            <span class="font-semibold">{{ number_format(floatval($estimatedCosts['total_parts']), 0, '', ' ') }} ₽</span>
        </div>
        @endif
        
        @if(isset($estimatedCosts['total']))
        <div class="flex justify-between items-center p-4 bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg border border-blue-200">
            <span class="text-lg font-bold text-blue-800">Итого</span>
            <span class="text-2xl font-bold text-blue-800">{{ number_format(floatval($estimatedCosts['total']), 0, '', ' ') }} ₽</span>
        </div>
        @endif
    </div>
    
    @if(isset($estimatedCosts['note']))
    <div class="mt-4 text-sm text-gray-600 bg-yellow-50 border border-yellow-200 rounded-lg p-3">
        <i class="fas fa-info-circle text-yellow-600 mr-1"></i>
        {{ $estimatedCosts['note'] }}
    </div>
    @endif
</div>
@endif

        <!-- Рекомендации -->
        @if(!empty($recommendedActions))
        <div class="bg-white rounded-xl shadow-sm p-4 md:p-6 mb-6">
            <div class="flex items-center mb-4">
                <div class="bg-indigo-100 p-2 rounded-lg mr-3">
                    <i class="fas fa-lightbulb text-indigo-600"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-800">Рекомендации</h2>
            </div>
            
            <div class="space-y-4">
                @foreach($recommendedActions as $action)
                    @if(is_array($action))
                    <div class="flex items-start p-4 bg-indigo-50 border border-indigo-100 rounded-lg">
                        <div class="mr-3 mt-1">
                            @if(isset($action['priority']) && $action['priority'] === 'high')
                                <i class="fas fa-exclamation-circle text-red-500"></i>
                            @elseif(isset($action['priority']) && $action['priority'] === 'medium')
                                <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                            @else
                                <i class="fas fa-check-circle text-green-500"></i>
                            @endif
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-800 mb-1">{{ $action['title'] ?? 'Рекомендация' }}</h3>
                            @if(isset($action['description']) && is_string($action['description']))
                                <p class="text-sm text-gray-600">{{ $action['description'] }}</p>
                            @endif
                            @if(isset($action['deadline']) && is_string($action['deadline']))
                                <div class="mt-2 text-sm">
                                    <span class="text-gray-500">Срок выполнения:</span>
                                    <span class="font-semibold ml-1 {{ strtotime($action['deadline']) < time() ? 'text-red-600' : 'text-green-600' }}">
                                        {{ $action['deadline'] }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        @endif

        <!-- Контакты -->
        @if($report->is_white_label && $report->partner_name)
        <div class="bg-white rounded-xl shadow-sm p-4 md:p-6 mb-6">
            <div class="flex items-center mb-4">
                <div class="bg-gray-100 p-2 rounded-lg mr-3">
                    <i class="fas fa-store text-gray-600"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-800">{{ $report->partner_name }}</h2>
            </div>
            
            <div class="space-y-3">
                @if(isset($report->partner_contacts['phone']) && is_string($report->partner_contacts['phone']))
                <div class="flex items-center">
                    <i class="fas fa-phone text-blue-600 mr-3 w-5"></i>
                    <a href="tel:{{ $report->partner_contacts['phone'] }}" class="text-blue-600 hover:text-blue-800">
                        {{ $report->partner_contacts['phone'] }}
                    </a>
                </div>
                @endif
                
                @if(isset($report->partner_contacts['email']) && is_string($report->partner_contacts['email']))
                <div class="flex items-center">
                    <i class="fas fa-envelope text-blue-600 mr-3 w-5"></i>
                    <a href="mailto:{{ $report->partner_contacts['email'] }}" class="text-blue-600 hover:text-blue-800">
                        {{ $report->partner_contacts['email'] }}
                    </a>
                </div>
                @endif
                
                @if(isset($report->partner_contacts['address']) && is_string($report->partner_contacts['address']))
                <div class="flex items-start">
                    <i class="fas fa-map-marker-alt text-blue-600 mr-3 mt-1 w-5"></i>
                    <span class="text-gray-700">{{ $report->partner_contacts['address'] }}</span>
                </div>
                @endif
                
                @if(isset($report->partner_contacts['website']) && is_string($report->partner_contacts['website']))
                <div class="flex items-center">
                    <i class="fas fa-globe text-blue-600 mr-3 w-5"></i>
                    <a href="{{ $report->partner_contacts['website'] }}" target="_blank" class="text-blue-600 hover:text-blue-800">
                        @php
                            $host = parse_url($report->partner_contacts['website'], PHP_URL_HOST);
                            echo $host ?: $report->partner_contacts['website'];
                        @endphp
                    </a>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Важная информация -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 md:p-6 mb-6">
            <div class="flex items-start">
                <div class="mr-4">
                    <i class="fas fa-shield-alt text-blue-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-blue-800 mb-3">Важная информация</h3>
                    <div class="space-y-3 text-blue-700 text-sm">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle mr-2 mt-1 text-blue-500"></i>
                            <span>Отчет носит рекомендательный характер и не является окончательным диагнозом.</span>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-info-circle mr-2 mt-1 text-blue-500"></i>
                            <span>Фактическая стоимость ремонта может отличаться в зависимости от состояния автомобиля.</span>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-info-circle mr-2 mt-1 text-blue-500"></i>
                            <span>Рекомендуем проконсультироваться со специалистом перед началом работ.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Форма отправки на email -->
        <div class="bg-white rounded-xl shadow-sm p-4 md:p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Отправить отчет на email</h3>
            <form action="{{ route('diagnostic.report.send-email', $case->id) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2" for="email">Email адрес</label>
                    <input type="email" 
                           name="email" 
                           id="email"
                           required
                           placeholder="ваш@email.com"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <button type="submit" class="btn-primary w-full py-3">
                    <i class="fas fa-paper-plane mr-2"></i> Отправить отчет
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function printReport() {
    window.print();
}

async function shareReport() {
    const shareData = {
        title: 'Диагностический отчет',
        text: 'Диагностический отчет для {{ $brand->name }} {{ $model->name }}',
        url: window.location.href,
    };
    
    try {
        if (navigator.share) {
            await navigator.share(shareData);
        } else {
            // Fallback: копирование ссылки
            await navigator.clipboard.writeText(window.location.href);
            alert('Ссылка скопирована в буфер обмена');
        }
    } catch (err) {
        console.log('Ошибка при попытке поделиться:', err);
    }
}

// Адаптивность для мобильных
document.addEventListener('DOMContentLoaded', function() {
    // Добавляем классы для мобильного отображения
    if (window.innerWidth < 768) {
        document.querySelectorAll('table').forEach(table => {
            table.classList.add('text-xs');
        });
        
        document.querySelectorAll('.container').forEach(container => {
            container.classList.add('px-3');
        });
    }
});
</script>

<style>
@media print {
    .container {
        max-width: 100% !important;
        padding: 0 !important;
    }
    
    .bg-gradient-to-r {
        background: #2563eb !important;
    }
    
    button, .flex-col, .sm\\:flex-row {
        display: none !important;
    }
    
    .break-before {
        page-break-before: always;
    }
    
    .break-after {
        page-break-after: always;
    }
    
    .break-inside {
        page-break-inside: avoid;
    }
}

@media (max-width: 640px) {
    .text-2xl {
        font-size: 1.25rem;
    }
    
    .text-xl {
        font-size: 1.125rem;
    }
    
    .p-6 {
        padding: 1rem;
    }
    
    .grid-cols-2 {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush
@endsection