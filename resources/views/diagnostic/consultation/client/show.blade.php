@extends('layouts.app')

@section('title', '1Консультация #' . $consultation->id)

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <!-- Левая колонка - информация о консультации -->
        <div class="col-lg-4 mb-4 mb-lg-0">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        @if($consultation->case)
                            Технический запрос #{{ substr($consultation->case->id, 0, 8) }}
                        @else
                           Консультация #{{ $consultation->id }}
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Информация о случае -->
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">Автомобиль</h6>
                        <div class="alert alert-light">
                            @if($consultation->case && $consultation->case->brand && $consultation->case->model)
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>{{ $consultation->case->brand->name ?? 'Бренд не указан' }} 
                                                {{ $consultation->case->model->name ?? 'Модель не указана' }}</strong>
                                        <div class="text-muted small mt-1">
                                            @if($consultation->case->year)
                                                <div><i class="bi bi-calendar"></i> Год выпуска: {{ $consultation->case->year }}</div>
                                            @endif
                                            @if($consultation->case->mileage)
                                                <div><i class="bi bi-speedometer2"></i> Пробег: {{ number_format($consultation->case->mileage) }} км</div>
                                            @endif
                                            @if($consultation->case->engine_type)
                                                <div><i class="bi bi-gear"></i> Двигатель: {{ $consultation->case->engine_type }}</div>
                                            @endif
                                            @if($consultation->case->vin)
                                                <div><i class="bi bi-upc"></i> VIN: {{ $consultation->case->vin }}</div>
                                            @endif
                                        </div>
                                    </div>
                                    @if($consultation->case->brand->logo ?? false)
                                        <img src="{{ asset('storage/' . $consultation->case->brand->logo) }}" 
                                             alt="{{ $consultation->case->brand->name }}" 
                                             style="width: 60px; height: 60px; object-fit: contain;">
                                    @endif
                                </div>
                            @else
                                <span class="text-muted">Информация об автомобиле не указана</span>
                            @endif
                        </div>
                    </div>

                    <!-- Обращение клиента -->
                    @if($consultation->case && $consultation->case->description)
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">Обращение клиента</h6>
                        <div class="card border">
                            <div class="card-body p-3">
                                <p class="mb-0">{{ $consultation->case->description }}</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Симптомы и проблемы -->
                    @if($consultation->case && $consultation->case->symptoms && is_array($consultation->case->symptoms) && count($consultation->case->symptoms) > 0)
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">Симптомы и проблемы</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @php
                                $symptomColors = [
                                    'engine' => 'danger',
                                    'transmission' => 'warning',
                                    'brakes' => 'primary',
                                    'electrical' => 'info',
                                    'suspension' => 'success',
                                    'other' => 'secondary'
                                ];
                            @endphp
                            @foreach($consultation->case->symptoms as $symptom)
                                @if(is_array($symptom))
                                    @if(isset($symptom['name']) || isset($symptom['description']))
                                        <span class="badge bg-{{ $symptomColors[$symptom['type'] ?? 'other'] ?? 'secondary' }}">
                                            {{ $symptom['name'] ?? $symptom['description'] ?? 'Симптом' }}
                                        </span>
                                    @endif
                                @elseif(is_string($symptom))
                                    <span class="badge bg-secondary">{{ $symptom }}</span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Вложенные файлы -->
                    @if($consultation->case && $consultation->case->uploaded_files && is_array($consultation->case->uploaded_files) && count($consultation->case->uploaded_files) > 0)
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">Приложенные файлы</h6>
                        <div class="list-group">
                            @foreach($consultation->case->uploaded_files as $file)
                                @if(is_array($file) && isset($file['path']))
                                    <a href="{{ asset('storage/' . $file['path']) }}" 
                                       target="_blank" 
                                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="bi bi-file-earmark me-2"></i>
                                            <small>{{ $file['original_name'] ?? 'Файл' }}</small>
                                        </div>
                                        <small class="text-muted">{{ isset($file['size']) ? round($file['size'] / 1024) . ' KB' : '—' }}</small>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Детали консультации -->
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">Детали консультации</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td class="text-muted">Тип:</td>
                                <td>
                                    @switch($consultation->type)
                                        @case('basic') 
                                            <span class="badge bg-info">Базовая</span>
                                            @break
                                        @case('premium') 
                                            <span class="badge bg-warning">Премиум</span>
                                            @break
                                        @case('expert') 
                                            <span class="badge bg-danger">Экспертная</span>
                                            @break
                                        @default 
                                            <span class="badge bg-secondary">{{ $consultation->type }}</span>
                                    @endswitch
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Статус:</td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'scheduled' => 'info',
                                            'in_progress' => 'primary',
                                            'completed' => 'success',
                                            'cancelled' => 'danger'
                                        ];
                                        $statusLabels = [
                                            'pending' => 'Ожидание оплаты',
                                            'scheduled' => 'Запланирована',
                                            'in_progress' => 'В процессе',
                                            'completed' => 'Завершена',
                                            'cancelled' => 'Отменена'
                                        ];
                                    @endphp
                                    <span class="badge bg-{{ $statusColors[$consultation->status] ?? 'secondary' }}">
                                        {{ $statusLabels[$consultation->status] ?? $consultation->status }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Цена:</td>
                                <td><strong>{{ number_format($consultation->price, 0) }} ₽</strong></td>
                            </tr>
                            @if($consultation->scheduled_at)
                                <tr>
                                    <td class="text-muted">Назначена на:</td>
                                    <td>
                                        <i class="bi bi-calendar-event"></i> 
                                        {{ \Carbon\Carbon::parse($consultation->scheduled_at)->format('d.m.Y') }}
                                        <i class="bi bi-clock ms-2"></i>
                                        {{ \Carbon\Carbon::parse($consultation->scheduled_at)->format('H:i') }}
                                    </td>
                                </tr>
                            @endif
                            @if($consultation->duration)
                                <tr>
                                    <td class="text-muted">Длительность:</td>
                                    <td>{{ $consultation->duration }} минут</td>
                                </tr>
                            @endif
                            <tr>
                                <td class="text-muted">Статус оплаты:</td>
                                <td>
                                    @php
                                        $paymentColors = [
                                            'pending' => 'warning',
                                            'paid' => 'success',
                                            'failed' => 'danger',
                                            'refunded' => 'secondary'
                                        ];
                                        $paymentLabels = [
                                            'pending' => 'Ожидает оплаты',
                                            'paid' => 'Оплачено',
                                            'failed' => 'Ошибка оплаты',
                                            'refunded' => 'Возврат'
                                        ];
                                    @endphp
                                    <span class="badge bg-{{ $paymentColors[$consultation->payment_status] ?? 'secondary' }}">
                                        {{ $paymentLabels[$consultation->payment_status] ?? $consultation->payment_status }}
                                    </span>
                                </td>
                            </tr>
                            @if($consultation->paid_at)
                                <tr>
                                    <td class="text-muted">Оплачено:</td>
                                    <td>{{ \Carbon\Carbon::parse($consultation->paid_at)->format('d.m.Y H:i') }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td class="text-muted">Создана:</td>
                                <td>{{ $consultation->created_at->format('d.m.Y H:i') }}</td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Участники -->
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">Участники</h6>
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <div class="avatar avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-2">
                                <small class="text-muted d-block">Клиент</small>
                                <div class="fw-bold">{{ $consultation->user->name }}</div>
                                <small class="text-muted">{{ $consultation->user->email }}</small>
                                @if($consultation->user->company_name)
                                    <div class="text-muted small">{{ $consultation->user->company_name }}</div>
                                @endif
                            </div>
                        </div>
                        
                        @if($consultation->expert)
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar avatar-sm bg-success text-white rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="bi bi-person-badge-fill"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-2">
                                    <small class="text-muted d-block">Эксперт</small>
                                    <div class="fw-bold">{{ $consultation->expert->name }}</div>
                                    @if($consultation->expert->expert_specialization)
                                        <small class="text-muted">{{ $consultation->expert->expert_specialization }}</small>
                                    @endif
                                    @if($consultation->expert->company_name)
                                        <div class="text-muted small">{{ $consultation->expert->company_name }}</div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="alert alert-warning py-2">
                                <small><i class="bi bi-clock me-1"></i> Ожидание назначения эксперта</small>
                            </div>
                        @endif
                    </div>

                    <!-- Отчет диагностики -->
                    @if($consultation->case && $consultation->case->analysis_result && is_array($consultation->case->analysis_result))
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">Результаты диагностики</h6>
                        <div class="card border">
                            <div class="card-body p-3">
                                @php
                                    $analysis = $consultation->case->analysis_result;
                                @endphp
                                @if(isset($analysis['summary']))
                                    @if(is_array($analysis['summary']))
                                        @foreach($analysis['summary'] as $summary)
                                            <p class="mb-1">{{ $summary }}</p>
                                        @endforeach
                                    @else
                                        <p class="mb-2"><strong>Вывод:</strong> {{ $analysis['summary'] }}</p>
                                    @endif
                                @endif
                                
                                @if(isset($analysis['possible_causes']) && is_array($analysis['possible_causes']))
                                    <p class="mb-1 mt-2"><strong>Возможные причины:</strong></p>
                                    <ul class="mb-2">
                                        @foreach($analysis['possible_causes'] as $cause)
                                            @if(is_array($cause))
                                                <li>{{ $cause['name'] ?? $cause['description'] ?? 'Причина' }}</li>
                                            @else
                                                <li>{{ $cause }}</li>
                                            @endif
                                        @endforeach
                                    </ul>
                                @endif
                                
                                @if(isset($analysis['recommended_actions']) && is_array($analysis['recommended_actions']))
                                    <p class="mb-1 mt-2"><strong>Рекомендуемые действия:</strong></p>
                                    <ul class="mb-2">
                                        @foreach($analysis['recommended_actions'] as $action)
                                            @if(is_array($action))
                                                <li>{{ $action['title'] ?? $action['description'] ?? 'Действие' }}</li>
                                            @else
                                                <li>{{ $action }}</li>
                                            @endif
                                        @endforeach
                                    </ul>
                                @endif
                                
                                @if($consultation->case->price_estimate)
                                    <p class="mb-2 mt-2"><strong>Примерная стоимость ремонта:</strong> {{ number_format($consultation->case->price_estimate, 0) }} ₽</p>
                                @endif
                                
                                @if($consultation->case->time_estimate)
                                    <p class="mb-0"><strong>Примерное время ремонта:</strong> {{ $consultation->case->time_estimate }} часов</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Заметки эксперта -->
                    @if($consultation->expert_notes)
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">Заметки эксперта</h6>
                        <div class="card border">
                            <div class="card-body p-3">
                                <p class="mb-0">{{ $consultation->expert_notes }}</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Обратная связь клиента -->
                    @if($consultation->customer_feedback)
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">Обратная связь клиента</h6>
                        <div class="card border">
                            <div class="card-body p-3">
                                <p class="mb-0">{{ $consultation->customer_feedback }}</p>
                                @if($consultation->rating)
                                    <div class="mt-2">
                                        <small class="text-muted">Оценка:</small>
                                        <div class="star-rating">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="bi bi-star{{ $i <= $consultation->rating ? '-fill text-warning' : '' }}"></i>
                                            @endfor
                                            <span class="ms-2">{{ $consultation->rating }}/5</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    <!-- Действия -->
                    <div class="mt-4">
                        @if(auth()->user()->id === $consultation->user_id && in_array($consultation->status, ['pending', 'scheduled']))
                            <form action="{{ route('diagnostic.consultation.cancel', $consultation->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Отменить консультацию?')">
                                    <i class="bi bi-x-circle me-1"></i> Отменить
                                </button>
                            </form>
                        @endif
                        
                        @if(auth()->user()->id === $consultation->expert_id && $consultation->status === 'scheduled')
                            <form action="{{ route('diagnostic.consultation.start', $consultation->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="bi bi-play-circle me-1"></i> Начать
                                </button>
                            </form>
                        @endif
                        
                        @if($consultation->expert_id === auth()->user()->id && $consultation->status === 'in_progress')
                            <form action="{{ route('diagnostic.consultation.complete', $consultation->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-check-circle me-1"></i> Завершить
                                </button>
                            </form>
                        @endif

                        @if($consultation->case)
                            <a href="{{ route('diagnostic.report.show', $consultation->case->id) }}" 
                               class="btn btn-info btn-sm ms-1" 
                               target="_blank">
                                <i class="bi bi-file-earmark-text me-1"></i> Отчет
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Правая колонка - чат -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Чат консультации</h5>
                    <div>
                        @if($consultation->status === 'in_progress')
                            <span class="badge bg-success pulse-animation">
                                <i class="bi bi-circle-fill"></i> В процессе
                            </span>
                        @endif
                        <span class="badge bg-primary ms-2">Сообщений: {{ $consultation->messages->count() }}</span>
                    </div>
                </div>
                
                <!-- Область сообщений -->
                <div class="card-body p-0" style="height: 600px; overflow-y: auto;" id="chatMessages">
                    <div class="p-3" id="messagesContainer">
                        @if($consultation->messages->count() > 0)
                            @foreach($consultation->messages->sortBy('created_at') as $message)
                                @include('partials.message', ['message' => $message, 'consultation' => $consultation])
                            @endforeach
                        @else
                            <div class="text-center py-5">
                                <i class="bi bi-chat-dots display-4 text-muted"></i>
                                <p class="text-muted mt-2">Чат пуст. Начните общение!</p>
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Форма отправки сообщения -->
                <div class="card-footer">
                    @if(in_array($consultation->status, ['in_progress', 'scheduled', 'pending']) && 
                        (auth()->user()->id === $consultation->user_id || 
                         auth()->user()->id === $consultation->expert_id ||
                         auth()->user()->role === 'admin'))
                        <form id="messageForm" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="consultation_id" value="{{ $consultation->id }}">
                            
                            <div class="row g-2">
                                <div class="col">
                                    <textarea name="message" 
                                              class="form-control" 
                                              rows="2" 
                                              placeholder="Введите сообщение..." 
                                              required
                                              id="messageInput"></textarea>
                                </div>
                            </div>
                            
                            <div class="row g-2 mt-2">
                                <div class="col">
                                    <div class="input-group">
                                        <input type="file" 
                                               name="attachments[]" 
                                               class="form-control form-control-sm" 
                                               multiple
                                               id="fileInput"
                                               accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt,.mp4,.avi,.mp3">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('fileInput').click()">
                                            <i class="bi bi-paperclip"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Можно прикрепить несколько файлов (до 10MB каждый)</small>
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-primary" id="sendButton">
                                        <i class="bi bi-send me-1"></i> Отправить
                                    </button>
                                </div>
                            </div>
                        </form>
                    @elseif($consultation->status === 'completed')
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-2"></i> Консультация завершена. Чат закрыт для новых сообщений.
                        </div>
                    @elseif($consultation->status === 'cancelled')
                        <div class="alert alert-danger mb-0">
                            <i class="bi bi-x-circle me-2"></i> Консультация отменена. Чат закрыт.
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-clock me-2"></i> Чат будет доступен после начала консультации.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .avatar {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .avatar-sm {
        width: 32px;
        height: 32px;
    }
    
    .message-date {
        font-size: 0.75rem;
        color: #6c757d;
    }
    
    .message-attachment {
        max-width: 300px;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        overflow: hidden;
    }
    
    .pulse-animation {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
    }
    
    .star-rating {
        color: #ffc107;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatMessages = document.getElementById('chatMessages');
    const messageForm = document.getElementById('messageForm');
    const messageInput = document.getElementById('messageInput');
    const sendButton = document.getElementById('sendButton');
    const messagesContainer = document.getElementById('messagesContainer');
    
    // Прокрутка вниз при загрузке
    scrollToBottom();
    
    // Отправка сообщения
    if (messageForm) {
        messageForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const originalButtonText = sendButton.innerHTML;
            
            // Отключаем кнопку
            sendButton.disabled = true;
            sendButton.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Отправка...';
            
            try {
                const response = await fetch('{{ route("diagnostic.consultation.message", $consultation->id) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Очищаем форму
                    messageInput.value = '';
                    document.getElementById('fileInput').value = '';
                    
                    // Добавляем новое сообщение в чат
                    if (data.message) {
                        const messageHtml = createMessageHtml(data.message);
                        messagesContainer.innerHTML += messageHtml;
                        scrollToBottom();
                    }
                    
                    // Обновляем счетчик сообщений
                    updateMessageCount();
                } else {
                    alert(data.message || 'Ошибка отправки сообщения');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ошибка сети или сервера');
            } finally {
                // Включаем кнопку обратно
                sendButton.disabled = false;
                sendButton.innerHTML = originalButtonText;
            }
        });
    }
    
    // Автообновление чата
    let lastMessageId = {{ $consultation->messages->last()->id ?? 0 }};
    setInterval(checkNewMessages, 10000); // Проверка каждые 10 секунд
    
    async function checkNewMessages() {
        if (!('{{ $consultation->status }}' === 'in_progress' || '{{ $consultation->status }}' === 'scheduled')) {
            return;
        }
        
        try {
            const response = await fetch('{{ route("diagnostic.consultation.messages", $consultation->id) }}?last_id=' + lastMessageId);
            const data = await response.json();
            
            if (data.success && data.messages && data.messages.length > 0) {
                data.messages.forEach(message => {
                    if (message.id > lastMessageId) {
                        const messageHtml = createMessageHtml(message);
                        messagesContainer.innerHTML += messageHtml;
                        lastMessageId = message.id;
                    }
                });
                
                if (data.messages.length > 0) {
                    scrollToBottom();
                    updateMessageCount();
                }
            }
        } catch (error) {
            console.error('Error checking new messages:', error);
        }
    }
    
    function createMessageHtml(message) {
        const isOwnMessage = message.user_id === {{ auth()->id() }};
        const isClient = message.user_id === {{ $consultation->user_id }};
        const messageDate = new Date(message.created_at);
        const formattedTime = messageDate.toLocaleTimeString('ru-RU', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        
        let attachmentsHtml = '';
        if (message.metadata && message.metadata.attachments && Array.isArray(message.metadata.attachments)) {
            attachmentsHtml = `
                <div class="mt-2">
                    ${message.metadata.attachments.map(attachment => `
                        <div class="message-attachment p-2 mb-2 bg-white rounded">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-paperclip me-2"></i>
                                <small class="text-truncate">${attachment.original_name || 'Файл'}</small>
                                <a href="/storage/${attachment.path || ''}" 
                                   target="_blank" 
                                   class="ms-2 btn btn-sm btn-outline-primary">
                                    <i class="bi bi-download"></i>
                                </a>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }
        
        return `
            <div class="mb-3 ${isOwnMessage ? 'text-end' : ''}">
                <div class="d-flex ${isOwnMessage ? 'flex-row-reverse' : ''}">
                    <div class="flex-shrink-0">
                        <div class="avatar avatar-sm ${isClient ? 'bg-primary' : 'bg-success'} text-white rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi ${isClient ? 'bi-person-fill' : 'bi-person-badge-fill'}"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-2" style="max-width: 70%;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted">
                                ${message.user_name}
                                <span class="badge ${isClient ? 'bg-secondary' : 'bg-success'} ms-1">
                                    ${isClient ? 'Клиент' : 'Эксперт'}
                                </span>
                            </small>
                            <small class="text-muted">${formattedTime}</small>
                        </div>
                        <div class="card ${isOwnMessage ? 'bg-primary text-white' : 'bg-light'}">
                            <div class="card-body p-2">
                                <p class="mb-0">${message.message}</p>
                                ${attachmentsHtml}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    function scrollToBottom() {
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }
    
    function updateMessageCount() {
        const badge = document.querySelector('.badge.bg-primary');
        if (badge) {
            const currentCount = parseInt(badge.textContent.replace('Сообщений: ', ''));
            badge.textContent = `Сообщений: ${currentCount + 1}`;
        }
    }
});
</script>
@endpush