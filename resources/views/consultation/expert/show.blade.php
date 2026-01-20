@extends('layouts.diagnostic')

@section('title', 'Консультация #' . substr($consultation->id, 0, 8))

@section('content')
@php
    $case = $consultation->case;
    $report = $case->activeReport;
    $customer = $consultation->user;
    
    // Собираем статистику по сообщениям
    $unreadCount = $consultation->messages()->where('user_id', '!=', auth()->id())->where('is_read', false)->count();
@endphp

<div class="min-h-screen bg-gray-50">
    <!-- Шапка консультации -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white">
        <div class="container mx-auto px-4 py-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                <div class="flex-1">
                    <div class="flex items-center mb-2">
                        <a href="{{ route('consultations.expert.dashboard') }}" 
                           class="text-blue-200 hover:text-white mr-3">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <h1 class="text-2xl font-bold">Консультация #{{ substr($consultation->id, 0, 8) }}</h1>
                    </div>
                    
                    <div class="text-blue-100 text-sm">
                        <div class="flex flex-wrap gap-4">
                            <div class="flex items-center">
                                <i class="fas fa-car mr-2"></i>
                                <span>{{ $case->brand->name ?? 'Марка' }} {{ $case->model->name ?? '' }}</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-user mr-2"></i>
                                <span>{{ $customer->name }}</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-tag mr-2"></i>
                                <span>{{ $consultation->type_label }}</span>
                            </div>
                            <div class="flex items-center">
                                <span class="px-3 py-1 bg-blue-500/30 rounded-full text-xs">
                                    {{ $consultation->status_label }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 md:mt-0">
                    @if($consultation->status === 'in_progress')
                    <button onclick="completeConsultation()" 
                            class="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-3 rounded-lg transition-colors">
                        <i class="fas fa-check-circle mr-2"></i> Завершить консультацию
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Основное содержимое -->
    <div class="container mx-auto px-4 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Левая колонка - информация о случае -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Симптомы и описание -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Описание проблемы</h2>
                    
                    @if($case->description)
                    <div class="mb-6">
                        <h3 class="font-semibold text-gray-700 mb-2">Подробное описание от клиента:</h3>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <p class="text-gray-700">{{ $case->description }}</p>
                        </div>
                    </div>
                    @endif
                    
                    @if(!empty($case->symptoms))
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-3">Выбранные симптомы:</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($case->symptoms as $symptomId)
                                @php
                                    $symptom = \App\Models\Diagnostic\Symptom::find($symptomId);
                                @endphp
                                @if($symptom)
                                    <span class="px-4 py-2 bg-blue-50 text-blue-700 rounded-lg border border-blue-200">
                                        <i class="fas fa-exclamation-circle mr-2"></i>
                                        {{ $symptom->name }}
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Отчет системы (если есть) -->
                @if($report)
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-robot text-blue-600 mr-2"></i> Отчет системы
                    </h2>
                    
                    <div class="space-y-4">
                        @if(!empty($report->possible_causes))
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-2">Возможные причины:</h3>
                            <div class="space-y-2">
                                @foreach($report->possible_causes as $index => $cause)
                                    <div class="flex items-start p-3 bg-gray-50 rounded-lg">
                                        <span class="mr-3 mt-1 text-blue-600 font-bold">{{ $index + 1 }}.</span>
                                        <span class="text-gray-700">
                                            @if(is_array($cause))
                                                {{ $cause['title'] ?? $cause }}
                                            @else
                                                {{ $cause }}
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        
                        @if(!empty($report->diagnostic_plan))
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-2">План диагностики:</h3>
                            <div class="space-y-2">
                                @foreach($report->diagnostic_plan as $index => $step)
                                    <div class="flex items-start p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                        <span class="mr-3 mt-1 text-yellow-600 font-bold">{{ $index + 1 }}.</span>
                                        <span class="text-gray-700">
                                            @if(is_array($step))
                                                {{ $step['title'] ?? $step }}
                                            @else
                                                {{ $step }}
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Анализ эксперта -->
                <div class="bg-white rounded-xl shadow-sm p-6" id="expert-analysis">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-user-tie text-purple-600 mr-2"></i> Ваш анализ
                    </h2>
                    
                    @if($consultation->expert_analysis)
                    <div class="mb-6">
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <p class="text-gray-700">{{ $consultation->expert_analysis }}</p>
                        </div>
                        
                        @if(!empty($consultation->recommendations))
                        <div class="mt-4">
                            <h3 class="font-semibold text-gray-700 mb-2">Ваши рекомендации:</h3>
                            <div class="space-y-2">
                                @foreach($consultation->recommendations as $index => $recommendation)
                                    <div class="flex items-start p-3 bg-green-50 border border-green-200 rounded-lg">
                                        <span class="mr-3 mt-1 text-green-600">
                                            <i class="fas fa-check-circle"></i>
                                        </span>
                                        <span class="text-gray-700">{{ $recommendation }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif
                    
                    <!-- Форма для добавления анализа -->
                    <div x-data="{ showAnalysisForm: {{ $consultation->expert_analysis ? 'false' : 'true' }} }">
                        <button @click="showAnalysisForm = !showAnalysisForm" 
                                class="btn-primary mb-4">
                            <i class="fas fa-edit mr-2"></i>
                            {{ $consultation->expert_analysis ? 'Изменить анализ' : 'Добавить анализ' }}
                        </button>
                        
                        <form x-show="showAnalysisForm" x-cloak 
                              action="{{ route('consultations.expert.analysis', $consultation->id) }}" 
                              method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">
                                    Детальный анализ проблемы
                                </label>
                                <textarea name="analysis" 
                                          rows="6"
                                          required
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                          placeholder="Опишите ваши выводы, предположения, возможные причины...">{{ $consultation->expert_analysis ?? '' }}</textarea>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">
                                    Рекомендации (каждая с новой строки)
                                </label>
                                <textarea name="recommendations[]" 
                                          rows="4"
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                          placeholder="1. Проверить...
2. Заменить...
3. Обратиться к..."></textarea>
                                <p class="text-sm text-gray-500 mt-1">Вводите каждую рекомендацию с новой строки</p>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="submit" class="btn-primary px-6">
                                    <i class="fas fa-save mr-2"></i> Сохранить анализ
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Правая колонка - чат и действия -->
            <div class="space-y-6">
                <!-- Чат консультации -->
                <div class="bg-white rounded-xl shadow-sm" id="chat">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-comment-alt text-blue-600 mr-2"></i> Чат консультации
                            @if($unreadCount > 0)
                            <span class="ml-2 px-2 py-1 bg-red-100 text-red-700 text-xs font-bold rounded-full">
                                {{ $unreadCount }} нов.
                            </span>
                            @endif
                        </h2>
                    </div>
                    
                    <div class="p-4">
                        <!-- История сообщений -->
                        <div id="chat-messages" class="h-96 overflow-y-auto mb-4 space-y-4 p-2">
                            <!-- Сообщения будут загружены через AJAX -->
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-comments text-2xl mb-2"></i>
                                <p>Загрузка сообщений...</p>
                            </div>
                        </div>
                        
                        <!-- Форма отправки сообщения -->
                        <form id="message-form" class="space-y-3">
                            @csrf
                            <div class="flex space-x-2">
                                <input type="text" 
                                       id="message-input"
                                       placeholder="Введите сообщение..."
                                       class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       required>
                                <button type="submit" 
                                        class="btn-primary px-6">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                            
                            <div class="flex flex-wrap gap-2">
                                <button type="button" 
                                        onclick="requestAdditionalData()"
                                        class="text-sm text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-question-circle mr-1"></i> Запросить данные
                                </button>
                                <button type="button" 
                                        onclick="showFileUpload()"
                                        class="text-sm text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-paperclip mr-1"></i> Прикрепить файл
                                </button>
                                <button type="button" 
                                        onclick="sendQuickQuestion()"
                                        class="text-sm text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-bolt mr-1"></i> Быстрый вопрос
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Быстрые действия -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="font-bold text-gray-800 mb-4">Быстрые действия</h3>
                    
                    <div class="space-y-3">
                        <button onclick="requestPhotos()" 
                                class="w-full text-left p-3 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition-colors">
                            <div class="flex items-center">
                                <i class="fas fa-camera text-blue-600 mr-3"></i>
                                <div>
                                    <div class="font-medium text-gray-800">Запросить фото</div>
                                    <div class="text-sm text-gray-600">Попросить клиента сфотографировать проблему</div>
                                </div>
                            </div>
                        </button>
                        
                        <button onclick="requestScannerLogs()" 
                                class="w-full text-left p-3 bg-yellow-50 hover:bg-yellow-100 border border-yellow-200 rounded-lg transition-colors">
                            <div class="flex items-center">
                                <i class="fas fa-file-code text-yellow-600 mr-3"></i>
                                <div>
                                    <div class="font-medium text-gray-800">Запросить логи сканера</div>
                                    <div class="text-sm text-gray-600">Попросить скинуть коды ошибок</div>
                                </div>
                            </div>
                        </button>
                        
                        <button onclick="scheduleCall()" 
                                class="w-full text-left p-3 bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg transition-colors">
                            <div class="flex items-center">
                                <i class="fas fa-phone text-green-600 mr-3"></i>
                                <div>
                                    <div class="font-medium text-gray-800">Предложить звонок</div>
                                    <div class="text-sm text-gray-600">Обсудить проблему по телефону</div>
                                </div>
                            </div>
                        </button>
                        
                        <button onclick="recommendService()" 
                                class="w-full text-left p-3 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-lg transition-colors">
                            <div class="flex items-center">
                                <i class="fas fa-tools text-purple-600 mr-3"></i>
                                <div>
                                    <div class="font-medium text-gray-800">Рекомендовать сервис</div>
                                    <div class="text-sm text-gray-600">Посоветовать проверенный сервис</div>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Информация о клиенте -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="font-bold text-gray-800 mb-4">Информация о клиенте</h3>
                    
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <i class="fas fa-user text-gray-400 mr-3 w-5"></i>
                            <span class="text-gray-700">{{ $customer->name }}</span>
                        </div>
                        
                        <div class="flex items-center">
                            <i class="fas fa-envelope text-gray-400 mr-3 w-5"></i>
                            <span class="text-gray-700">{{ $customer->email }}</span>
                        </div>
                        
                        @if($customer->phone)
                        <div class="flex items-center">
                            <i class="fas fa-phone text-gray-400 mr-3 w-5"></i>
                            <a href="tel:{{ $customer->phone }}" class="text-blue-600 hover:text-blue-800">
                                {{ $customer->phone }}
                            </a>
                        </div>
                        @endif
                        
                        <div class="flex items-center">
                            <i class="fas fa-calendar text-gray-400 mr-3 w-5"></i>
                            <span class="text-gray-700">
                                Клиент с {{ $customer->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для завершения консультации -->
<div id="complete-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 m-4 max-w-md w-full">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Завершение консультации</h3>
        
        <form action="{{ route('consultations.expert.complete', $consultation->id) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-gray-700 font-medium mb-2">
                    Краткое резюме
                </label>
                <textarea name="summary" 
                          rows="4"
                          required
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg"
                          placeholder="Опишите основные выводы и рекомендации..."></textarea>
            </div>
            
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="rating_available" value="1" class="mr-2" checked>
                    <span class="text-gray-700">Разрешить клиенту оставить отзыв</span>
                </label>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" 
                        onclick="closeCompleteModal()"
                        class="px-4 py-2 text-gray-600 hover:text-gray-800 font-medium">
                    Отмена
                </button>
                <button type="submit" 
                        class="btn-primary px-6">
                    Завершить
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Модальное окно для запроса данных -->
<div id="data-request-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 m-4 max-w-md w-full">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Запрос дополнительных данных</h3>
        
        <form id="data-request-form" class="space-y-4">
            @csrf
            <div>
                <label class="block text-gray-700 font-medium mb-2">
                    Какие данные нужны?
                </label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="data_types[]" value="photos" class="mr-2" checked>
                        <span class="text-gray-700">Фотографии проблемы</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="data_types[]" value="video" class="mr-2">
                        <span class="text-gray-700">Видео работы двигателя</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="data_types[]" value="scanner_logs" class="mr-2" checked>
                        <span class="text-gray-700">Логи диагностического сканера</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="data_types[]" value="vin_details" class="mr-2">
                        <span class="text-gray-700">Подробности VIN</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="data_types[]" value="maintenance_history" class="mr-2">
                        <span class="text-gray-700">История обслуживания</span>
                    </label>
                </div>
            </div>
            
            <div>
                <label class="block text-gray-700 font-medium mb-2">
                    Сообщение для клиента
                </label>
                <textarea name="message" 
                          rows="3"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg"
                          placeholder="Пожалуйста, предоставьте..."></textarea>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" 
                        onclick="closeDataRequestModal()"
                        class="px-4 py-2 text-gray-600 hover:text-gray-800 font-medium">
                    Отмена
                </button>
                <button type="submit" 
                        class="btn-primary px-6">
                    Отправить запрос
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// Загрузка сообщений чата
function loadMessages() {
    fetch(`{{ route('consultations.messages', $consultation->id) }}`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('chat-messages');
            container.innerHTML = '';
            
            data.messages.forEach(message => {
                const isMe = message.user_id === {{ auth()->id() }};
                const time = new Date(message.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                
                const messageHtml = `
                    <div class="flex ${isMe ? 'justify-end' : 'justify-start'}">
                        <div class="max-w-xs lg:max-w-md ${isMe ? 'bg-blue-100' : 'bg-gray-100'} rounded-xl p-3">
                            <div class="text-xs text-gray-500 mb-1">
                                ${message.user.name} • ${time}
                            </div>
                            <div class="text-gray-800">
                                ${escapeHtml(message.message)}
                            </div>
                            ${message.attachments ? `
                                <div class="mt-2">
                                    ${message.attachments.map(att => `
                                        <a href="/storage/${att.path}" target="_blank" 
                                           class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-paperclip mr-1"></i> ${att.name}
                                        </a>
                                    `).join('')}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', messageHtml);
            });
            
            // Прокрутка вниз
            container.scrollTop = container.scrollHeight;
            
            // Пометить как прочитанные
            markAsRead();
        });
}

// Отправка сообщения
document.getElementById('message-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const input = document.getElementById('message-input');
    const message = input.value.trim();
    
    if (!message) return;
    
    fetch(`{{ route('consultations.message', $consultation->id) }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            message: message,
            type: 'text'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            loadMessages();
        }
    });
});

// Пометить сообщения как прочитанные
function markAsRead() {
    fetch(`{{ route('consultations.read', $consultation->id) }}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    });
}

// Завершение консультации
function completeConsultation() {
    document.getElementById('complete-modal').classList.remove('hidden');
}

function closeCompleteModal() {
    document.getElementById('complete-modal').classList.add('hidden');
}

// Запрос дополнительных данных
function requestAdditionalData() {
    document.getElementById('data-request-modal').classList.remove('hidden');
}

function closeDataRequestModal() {
    document.getElementById('data-request-modal').classList.add('hidden');
}

// Быстрые действия
function requestPhotos() {
    document.getElementById('data-request-form').querySelector('textarea').value = 
        'Пожалуйста, сделайте фото: 1) приборной панели с горящими лампочками, 2) моторного отсека, 3) места, откуда идет шум/дым.';
    document.getElementById('data-request-modal').classList.remove('hidden');
}

function requestScannerLogs() {
    document.getElementById('data-request-form').querySelector('textarea').value = 
        'Пожалуйста, снимите коды ошибок с помощью диагностического сканера и пришлите фото или скриншот.';
    document.getElementById('data-request-modal').classList.remove('hidden');
}

function scheduleCall() {
    const message = 'Для более детального обсуждения можем созвониться. В какое время вам удобно?';
    sendQuickMessage(message);
}

function recommendService() {
    const message = 'Рекомендую обратиться в проверенный сервис: [название сервиса], тел.: [номер], адрес: [адрес]. Они специализируются на Land Rover.';
    sendQuickMessage(message);
}

// Отправка быстрого сообщения
function sendQuickMessage(message) {
    document.getElementById('message-input').value = message;
    document.getElementById('message-form').dispatchEvent(new Event('submit'));
}

// Вспомогательная функция для экранирования HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    loadMessages();
    
    // Обновление сообщений каждые 10 секунд
    setInterval(loadMessages, 10000);
    
    // Обработка модальных окон
    document.getElementById('data-request-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {
            data_types: Array.from(formData.getAll('data_types[]')),
            message: formData.get('message')
        };
        
        fetch(`{{ route('consultations.expert.request-data', $consultation->id) }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeDataRequestModal();
                location.reload();
            }
        });
    });
    
    // Закрытие модальных окон по клику на фон
    document.querySelectorAll('[id$="-modal"]').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    });
});
</script>
@endpush
@endsection