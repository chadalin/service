@extends('layouts.app')

@section('title', 'Консультация #' . $consultation->id)

@push('styles')
<style>
    /* Основные стили */
    .consultation-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }
    
    .consultation-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.1);
        z-index: 1;
    }
    
    .consultation-header-content {
        position: relative;
        z-index: 2;
    }
    
    .status-badge {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        display: inline-block;
    }
    
    .status-pending { background: #fbbf24; color: #78350f; }
    .status-in_progress { background: #3b82f6; color: white; animation: pulse 2s infinite; }
    .status-completed { background: #10b981; color: white; }
    .status-cancelled { background: #ef4444; color: white; }
    
    /* Карточки */
    .info-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e5e7eb;
        margin-bottom: 1.5rem;
        overflow: hidden;
    }
    
    .info-card-header {
        border-bottom: 2px solid #3b82f6;
        padding: 1rem 1.5rem;
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .info-card-header h6 {
        margin: 0;
        color: #374151;
        font-weight: 600;
    }
    
    .info-card-body {
        padding: 1.5rem;
    }
    
    /* Стили для галереи файлов */
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }
    
    @media (max-width: 768px) {
        .gallery-grid {
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        }
    }
    
    .file-preview {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        position: relative;
        height: 150px;
        cursor: pointer;
    }
    
    .file-preview:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        border-color: #3b82f6;
    }
    
    .file-preview img,
    .file-preview video {
        width: 100%;
        height: 100px;
        object-fit: cover;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .file-preview .file-icon {
        width: 100%;
        height: 100px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f3f4f6;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .file-preview .file-icon i {
        font-size: 2.5rem;
        color: #6b7280;
    }
    
    .file-info {
        padding: 0.75rem;
        background: white;
    }
    
    .file-name {
        font-size: 0.8rem;
        color: #374151;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 0.25rem;
    }
    
    .file-size {
        font-size: 0.7rem;
        color: #6b7280;
    }
    
    .file-actions {
        position: absolute;
        top: 5px;
        right: 5px;
        display: none;
    }
    
    .file-preview:hover .file-actions {
        display: flex;
        gap: 2px;
    }
    
    .file-btn {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .file-btn:hover {
        transform: scale(1.1);
    }
    
    .file-btn-download {
        color: #10b981;
    }
    
    .file-btn-view {
        color: #3b82f6;
    }
    
    /* Чат стили */
    .chat-container {
        height: 600px;
        display: flex;
        flex-direction: column;
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }
    
    .chat-header {
        background: #f8fafc;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .messages-container {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .message {
        max-width: 70%;
        display: flex;
        gap: 0.75rem;
    }
    
    .message.own {
        align-self: flex-end;
        flex-direction: row-reverse;
    }
    
    .message-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-weight: 600;
    }
    
    .message-client .message-avatar {
        background: #3b82f6;
        color: white;
    }
    
    .message-expert .message-avatar {
        background: #10b981;
        color: white;
    }
    
    .message-system .message-avatar {
        background: #6b7280;
        color: white;
    }
    
    .message-content {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 0.75rem 1rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        position: relative;
    }
    
    .message.own .message-content {
        background: #eff6ff;
        border-color: #dbeafe;
    }
    
    .message-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }
    
    .message-user {
        font-weight: 600;
        color: #374151;
        font-size: 0.9rem;
    }
    
    .message-time {
        font-size: 0.75rem;
        color: #6b7280;
    }
    
    .message-text {
        color: #4b5563;
        line-height: 1.5;
        white-space: pre-line;
        margin-bottom: 0.5rem;
    }
    
    .message-attachments {
        margin-top: 0.75rem;
        border-top: 1px solid #e5e7eb;
        padding-top: 0.75rem;
    }
    
    .attachment-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem;
        background: #f9fafb;
        border-radius: 6px;
        margin-bottom: 0.5rem;
        border: 1px solid #e5e7eb;
    }
    
    .attachment-item:last-child {
        margin-bottom: 0;
    }
    
    .attachment-icon {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        border-radius: 6px;
        color: #6b7280;
    }
    
    .attachment-info {
        flex: 1;
        min-width: 0;
    }
    
    .attachment-name {
        font-size: 0.85rem;
        color: #374151;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .attachment-size {
        font-size: 0.75rem;
        color: #6b7280;
    }
    
    .attachment-download {
        color: #3b82f6;
        cursor: pointer;
        transition: color 0.2s;
    }
    
    .attachment-download:hover {
        color: #2563eb;
    }
    
    .chat-input {
        border-top: 1px solid #e5e7eb;
        padding: 1rem 1.5rem;
        background: #f8fafc;
    }
    
    /* Таблицы */
    .detail-table {
        width: 100%;
    }
    
    .detail-table td {
        padding: 0.5rem 0;
        vertical-align: top;
    }
    
    .detail-table td:first-child {
        color: #6b7280;
        font-weight: 500;
        width: 40%;
        padding-right: 1rem;
    }
    
    /* Анимации */
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .animate-slide-in {
        animation: slideIn 0.3s ease-out;
    }
    
    /* Модальное окно для изображений */
    .modal-image {
        max-width: 100%;
        max-height: 80vh;
        object-fit: contain;
    }
    
    /* Симптомы */
    .symptom-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: #dbeafe;
        color: #1e40af;
        border-radius: 20px;
        font-size: 0.875rem;
        margin: 0.25rem;
        text-decoration: none;
        transition: all 0.2s;
    }
    
    .symptom-tag:hover {
        background: #3b82f6;
        color: white;
        transform: translateY(-2px);
    }
    
    /* Индикаторы */
    .indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .indicator-success {
        background: #d1fae5;
        color: #065f46;
    }
    
    .indicator-warning {
        background: #fef3c7;
        color: #92400e;
    }
    
    .indicator-danger {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .indicator-info {
        background: #dbeafe;
        color: #1e40af;
    }
    
    /* Иконки */
    .icon-wrapper {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }
    
    .icon-primary { background: #eff6ff; color: #3b82f6; }
    .icon-success { background: #ecfdf5; color: #10b981; }
    .icon-warning { background: #fffbeb; color: #f59e0b; }
    .icon-danger { background: #fef2f2; color: #ef4444; }
    
    /* Участники */
    .participant-card {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: #f9fafb;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
    }
    
    .participant-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1.25rem;
    }
    
    .participant-client .participant-avatar {
        background: #3b82f6;
        color: white;
    }
    
    .participant-expert .participant-avatar {
        background: #10b981;
        color: white;
    }
    
    .participant-info h6 {
        margin: 0 0 0.25rem 0;
        color: #374151;
    }
    
    .participant-role {
        font-size: 0.875rem;
        color: #6b7280;
    }
    
    .participant-contact {
        font-size: 0.875rem;
        color: #6b7280;
    }
    
    /* Кнопки действий */
    .action-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    .action-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.625rem 1.25rem;
        border-radius: 8px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
    }
    
    .action-btn-primary {
        background: #3b82f6;
        color: white;
    }
    
    .action-btn-primary:hover {
        background: #2563eb;
        color: white;
    }
    
    .action-btn-success {
        background: #10b981;
        color: white;
    }
    
    .action-btn-success:hover {
        background: #059669;
        color: white;
    }
    
    .action-btn-danger {
        background: #ef4444;
        color: white;
    }
    
    .action-btn-danger:hover {
        background: #dc2626;
        color: white;
    }
    
    .action-btn-outline {
        background: white;
        border: 1px solid #e5e7eb;
        color: #374151;
    }
    
    .action-btn-outline:hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }
    
    /* Звездный рейтинг */
    .star-rating {
        display: flex;
        gap: 2px;
    }
    
    .star-rating i {
        font-size: 1.25rem;
    }
    
    .star-filled {
        color: #f59e0b;
    }
    
    .star-empty {
        color: #d1d5db;
    }
    
    /* Адаптивность */
    @media (max-width: 992px) {
        .message {
            max-width: 85%;
        }
    }
    
    @media (max-width: 768px) {
        .message {
            max-width: 90%;
        }
        
        .gallery-grid {
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        }
        
        .file-preview {
            height: 130px;
        }
        
        .file-preview img,
        .file-preview video,
        .file-preview .file-icon {
            height: 80px;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- Шапка консультации -->
    <div class="consultation-header">
        <div class="consultation-header-content">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h1 class="h3 mb-2">
                        <i class="bi bi-chat-dots me-2"></i>
                        Консультация #{{ $consultation->id }}
                    </h1>
                    <p class="mb-0 opacity-90">
                        {{ $consultation->created_at->translatedFormat('d F Y в H:i') }}
                    </p>
                </div>
                <div class="d-flex flex-column align-items-end gap-2">
                    <div>
                        <span class="status-badge status-{{ $consultation->status }}">
                            @switch($consultation->status)
                                @case('pending') ⏳ Ожидание эксперта @break
                                @case('in_progress') 🔄 В процессе @break
                                @case('completed') ✅ Завершена @break
                                @case('cancelled') ❌ Отменена @break
                                @default {{ $consultation->status }}
                            @endswitch
                        </span>
                    </div>
                    <div class="h4 mb-0">
                        {{ number_format($consultation->price, 0, '', ' ') }} ₽
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Левая колонка: Информация -->
        <div class="col-lg-4">
            <!-- Участники -->
            <div class="info-card">
                <div class="info-card-header">
                    <h6><i class="bi bi-people me-2"></i>Участники</h6>
                </div>
                <div class="info-card-body">
                    <div class="d-flex flex-column gap-3">
                        <!-- Клиент -->
                        <div class="participant-card participant-client">
                            <div class="participant-avatar">
                                {{ substr($consultation->user->name ?? 'К', 0, 1) }}
                            </div>
                            <div class="participant-info">
                                <h6>{{ $consultation->user->name ?? 'Клиент' }}</h6>
                                <div class="participant-role">👤 Клиент</div>
                                @if($consultation->user->email ?? false)
                                    <div class="participant-contact">{{ $consultation->user->email }}</div>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Эксперт -->
                        <div class="participant-card participant-expert">
                            <div class="participant-avatar">
                                {{ $consultation->expert ? substr($consultation->expert->name, 0, 1) : '?' }}
                            </div>
                            <div class="participant-info">
                                <h6>{{ $consultation->expert->name ?? 'Не назначен' }}</h6>
                                <div class="participant-role">👨‍🔧 Эксперт</div>
                                @if($consultation->expert)
                                    @if($consultation->expert->expert_specialization ?? false)
                                        <div class="participant-contact">{{ $consultation->expert->expert_specialization }}</div>
                                    @endif
                                    @if($consultation->expert->email ?? false)
                                        <div class="participant-contact">{{ $consultation->expert->email }}</div>
                                    @endif
                                @else
                                    <div class="participant-contact text-warning">
                                        <i class="bi bi-clock me-1"></i> Ожидание назначения
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Автомобиль -->
            <div class="info-card">
                <div class="info-card-header">
                    <h6><i class="bi bi-car-front me-2"></i>Автомобиль</h6>
                </div>
                <div class="info-card-body">
                    @php
                        // Получаем данные об автомобиле
                        $brand = $consultation->case->brand ?? null;
                        $model = $consultation->case->model ?? null;
                        $caseData = $consultation->case ?? null;
                    @endphp
                    
                    @if($brand || $model || ($caseData && ($caseData->year || $caseData->mileage || $caseData->engine_type)))
                        <div class="d-flex align-items-start gap-3 mb-3">
                            @if($brand && $brand->logo)
                                <img src="{{ asset('storage/' . $brand->logo) }}" 
                                     alt="{{ $brand->name }}" 
                                     style="width: 80px; height: 80px; object-fit: contain;">
                            @else
                                <div class="icon-wrapper icon-primary">
                                    <i class="bi bi-car-front"></i>
                                </div>
                            @endif
                            
                            <div>
                                <h5 class="mb-1">
                                    {{ $brand->name ?? ($caseData->brand_id ?? 'Марка не указана') }}
                                    @if($model)
                                        <span class="text-muted">/ {{ $model->name }}</span>
                                    @endif
                                </h5>
                                
                                <table class="detail-table">
                                    @if($caseData && $caseData->year)
                                        <tr>
                                            <td><i class="bi bi-calendar me-1"></i> Год</td>
                                            <td><strong>{{ $caseData->year }}</strong></td>
                                        </tr>
                                    @endif
                                    
                                    @if($caseData && $caseData->mileage)
                                        <tr>
                                            <td><i class="bi bi-speedometer2 me-1"></i> Пробег</td>
                                            <td><strong>{{ number_format($caseData->mileage, 0, '', ' ') }} км</strong></td>
                                        </tr>
                                    @endif
                                    
                                    @if($caseData && $caseData->engine_type)
                                        <tr>
                                            <td><i class="bi bi-gear me-1"></i> Двигатель</td>
                                            <td><strong>{{ $caseData->engine_type }}</strong></td>
                                        </tr>
                                    @endif
                                    
                                    @if($caseData && $caseData->vin)
                                        <tr>
                                            <td><i class="bi bi-upc me-1"></i> VIN</td>
                                            <td><code>{{ $caseData->vin }}</code></td>
                                        </tr>
                                    @endif
                                    
                                    @if($caseData && $caseData->rule)
                                        <tr>
                                            <td><i class="bi bi-exclamation-triangle me-1"></i> Правило</td>
                                            <td>
                                                <a href="{{ route('admin.diagnostic.rules.show', $caseData->rule->id) }}" 
                                                   target="_blank" 
                                                   class="text-decoration-none">
                                                    {{ $caseData->rule->code ?? '#' . $caseData->rule->id }}
                                                </a>
                                            </td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-3">
                            <i class="bi bi-car-front display-4 text-muted mb-3"></i>
                            <p class="text-muted mb-0">Информация об автомобиле не указана</p>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Код ошибки и симптомы -->
            @php
                // Получаем симптомы
                $symptoms = [];
                if ($caseData && $caseData->symptoms) {
                    if (is_string($caseData->symptoms)) {
                        $symptoms = json_decode($caseData->symptoms, true) ?? [];
                    } elseif (is_array($caseData->symptoms)) {
                        $symptoms = $caseData->symptoms;
                    }
                }
                
                // Получаем правило
                $rule = $caseData->rule ?? null;
            @endphp
            
            @if($rule || count($symptoms) > 0)
            <div class="info-card">
                <div class="info-card-header">
                    <h6><i class="bi bi-bug me-2"></i>Диагностика</h6>
                </div>
                <div class="info-card-body">
                    <!-- Код ошибки -->
                    @if($rule)
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">Код ошибки / Симптом</h6>
                            <div class="alert alert-warning">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-bold">{{ $rule->code ?? 'Симптом #' . $rule->id }}</div>
                                        @if($rule->symptom)
                                            <div class="mt-1">{{ $rule->symptom->name }}</div>
                                        @endif
                                    </div>
                                    <a href="{{ route('admin.diagnostic.rules.show', $rule->id) }}" 
                                       target="_blank" 
                                       class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-arrow-up-right-square me-1"></i> Подробнее
                                    </a>
                                </div>
                                
                                @if($rule->description)
                                    <div class="mt-2 text-muted small">
                                        {{ Str::limit($rule->description, 150) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                    
                    <!-- Список симптомов -->
                    @if(count($symptoms) > 0)
                        <div class="mb-3">
                            <h6 class="text-muted mb-2">Выбранные симптомы</h6>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($symptoms as $symptom)
                                    @if(is_array($symptom))
                                        @php
                                            $symptomId = $symptom['id'] ?? null;
                                            $symptomName = $symptom['name'] ?? $symptom['description'] ?? 'Симптом';
                                            $symptomType = $symptom['type'] ?? 'other';
                                            
                                            $typeColors = [
                                                'engine' => 'danger',
                                                'transmission' => 'warning',
                                                'brakes' => 'primary',
                                                'electrical' => 'info',
                                                'suspension' => 'success',
                                                'other' => 'secondary'
                                            ];
                                            $typeIcons = [
                                                'engine' => 'bi-gear',
                                                'transmission' => 'bi-shuffle',
                                                'brakes' => 'bi-brake-pad',
                                                'electrical' => 'bi-lightning-charge',
                                                'suspension' => 'bi-car-front',
                                                'other' => 'bi-exclamation-triangle'
                                            ];
                                        @endphp
                                        
                                        <a href="{{ $symptomId ? route('admin.diagnostic.symptoms.show', $symptomId) : '#' }}" 
                                           class="symptom-tag" 
                                           target="{{ $symptomId ? '_blank' : '_self' }}">
                                            <i class="bi {{ $typeIcons[$symptomType] ?? 'bi-exclamation-triangle' }}"></i>
                                            {{ Str::limit($symptomName, 25) }}
                                        </a>
                                    @elseif(is_string($symptom))
                                        <span class="symptom-tag">
                                            <i class="bi bi-exclamation-triangle"></i>
                                            {{ Str::limit($symptom, 25) }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    <!-- Описание проблемы -->
                    @if($caseData && $caseData->description)
                        <div class="mt-4">
                            <h6 class="text-muted mb-2">Описание проблемы</h6>
                            <div class="card border">
                                <div class="card-body">
                                    <p class="mb-0">{{ $caseData->description }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            @endif
            
            <!-- Приложенные файлы -->
            @php
                // Получаем файлы
                $uploadedFiles = [];
                if ($caseData && $caseData->uploaded_files) {
                    if (is_string($caseData->uploaded_files)) {
                        $uploadedFiles = json_decode($caseData->uploaded_files, true) ?? [];
                    } elseif (is_array($caseData->uploaded_files)) {
                        $uploadedFiles = $caseData->uploaded_files;
                    }
                }
                
                // Группируем файлы по типу
                $groupedFiles = [
                    'images' => [],
                    'videos' => [],
                    'documents' => [],
                    'other' => []
                ];
                
                foreach ($uploadedFiles as $file) {
                    if (is_array($file) && isset($file['mime_type'])) {
                        if (str_starts_with($file['mime_type'], 'image/')) {
                            $groupedFiles['images'][] = $file;
                        } elseif (str_starts_with($file['mime_type'], 'video/')) {
                            $groupedFiles['videos'][] = $file;
                        } elseif (in_array($file['mime_type'], [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                        ]) || isset($file['type']) && $file['type'] === 'protocol') {
                            $groupedFiles['documents'][] = $file;
                        } else {
                            $groupedFiles['other'][] = $file;
                        }
                    }
                }
                
                $totalFiles = count($uploadedFiles);
            @endphp
            
            @if($totalFiles > 0)
            <div class="info-card">
                <div class="info-card-header">
                    <h6><i class="bi bi-paperclip me-2"></i>Приложенные файлы ({{ $totalFiles }})</h6>
                </div>
                <div class="info-card-body">
                    <!-- Галерея изображений -->
                    @if(count($groupedFiles['images']) > 0)
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">📷 Фотографии ({{ count($groupedFiles['images']) }})</h6>
                            <div class="gallery-grid">
                                @foreach($groupedFiles['images'] as $file)
                                    <div class="file-preview" 
                                         data-bs-toggle="modal" 
                                         data-bs-target="#imageModal"
                                         data-image-src="{{ asset('storage/' . $file['path']) }}"
                                         data-image-name="{{ $file['original_name'] ?? 'Изображение' }}">
                                        <img src="{{ asset('storage/' . $file['path']) }}" 
                                             alt="{{ $file['original_name'] ?? 'Изображение' }}"
                                             loading="lazy">
                                        <div class="file-info">
                                            <div class="file-name">{{ Str::limit($file['original_name'] ?? 'Изображение', 15) }}</div>
                                            @if(isset($file['size']))
                                                <div class="file-size">{{ round($file['size'] / 1024) }} KB</div>
                                            @endif
                                        </div>
                                        <div class="file-actions">
                                            <button class="file-btn file-btn-view" 
                                                    onclick="event.stopPropagation(); window.open('{{ asset('storage/' . $file['path']) }}', '_blank');">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <a href="{{ asset('storage/' . $file['path']) }}" 
                                               download="{{ $file['original_name'] ?? 'image.jpg' }}" 
                                               class="file-btn file-btn-download"
                                               onclick="event.stopPropagation();">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    <!-- Видео -->
                    @if(count($groupedFiles['videos']) > 0)
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">🎥 Видео ({{ count($groupedFiles['videos']) }})</h6>
                            <div class="gallery-grid">
                                @foreach($groupedFiles['videos'] as $file)
                                    <div class="file-preview">
                                        <video controls preload="metadata">
                                            <source src="{{ asset('storage/' . $file['path']) }}" type="{{ $file['mime_type'] ?? 'video/mp4' }}">
                                        </video>
                                        <div class="file-info">
                                            <div class="file-name">{{ Str::limit($file['original_name'] ?? 'Видео', 15) }}</div>
                                            @if(isset($file['size']))
                                                <div class="file-size">{{ round($file['size'] / (1024 * 1024), 1) }} MB</div>
                                            @endif
                                        </div>
                                        <div class="file-actions">
                                            <a href="{{ asset('storage/' . $file['path']) }}" 
                                               download="{{ $file['original_name'] ?? 'video.mp4' }}" 
                                               class="file-btn file-btn-download">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    <!-- Документы -->
                    @if(count($groupedFiles['documents']) > 0)
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">📄 Документы ({{ count($groupedFiles['documents']) }})</h6>
                            <div class="list-group">
                                @foreach($groupedFiles['documents'] as $file)
                                    <a href="{{ asset('storage/' . $file['path']) }}" 
                                       target="_blank" 
                                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center gap-2">
                                            @php
                                                $ext = pathinfo($file['original_name'] ?? '', PATHINFO_EXTENSION);
                                                $icon = match(strtolower($ext)) {
                                                    'pdf' => 'bi-file-pdf text-danger',
                                                    'doc', 'docx' => 'bi-file-word text-primary',
                                                    'xls', 'xlsx' => 'bi-file-excel text-success',
                                                    'txt' => 'bi-file-text',
                                                    default => 'bi-file-earmark'
                                                };
                                            @endphp
                                            <i class="bi {{ $icon }}"></i>
                                            <span>{{ $file['original_name'] ?? 'Документ' }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-3">
                                            @if(isset($file['size']))
                                                <small class="text-muted">{{ round($file['size'] / 1024) }} KB</small>
                                            @endif
                                            <i class="bi bi-download"></i>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    <!-- Остальные файлы -->
                    @if(count($groupedFiles['other']) > 0)
                        <div>
                            <h6 class="text-muted mb-2">📦 Прочие файлы ({{ count($groupedFiles['other']) }})</h6>
                            <div class="list-group">
                                @foreach($groupedFiles['other'] as $file)
                                    <a href="{{ asset('storage/' . $file['path']) }}" 
                                       target="_blank" 
                                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-file-earmark"></i>
                                            <span>{{ $file['original_name'] ?? 'Файл' }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-3">
                                            @if(isset($file['size']))
                                                <small class="text-muted">{{ round($file['size'] / 1024) }} KB</small>
                                            @endif
                                            <i class="bi bi-download"></i>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            @endif
            
            <!-- Детали консультации -->
            <div class="info-card">
                <div class="info-card-header">
                    <h6><i class="bi bi-info-circle me-2"></i>Детали консультации</h6>
                </div>
                <div class="info-card-body">
                    <table class="detail-table">
                        <tr>
                            <td><i class="bi bi-chat-left-text me-1"></i> Тип</td>
                            <td>
                                @switch($consultation->type)
                                    @case('basic') 
                                        <span class="indicator indicator-info">Базовая</span>
                                        @break
                                    @case('premium') 
                                        <span class="indicator indicator-warning">Премиум</span>
                                        @break
                                    @case('expert') 
                                        <span class="indicator indicator-danger">Экспертная</span>
                                        @break
                                @endswitch
                            </td>
                        </tr>
                        
                        <tr>
                            <td><i class="bi bi-clock me-1"></i> Статус</td>
                            <td>
                                <span class="status-badge status-{{ $consultation->status }}">
                                    @switch($consultation->status)
                                        @case('pending') Ожидание @break
                                        @case('in_progress') В процессе @break
                                        @case('completed') Завершена @break
                                        @case('cancelled') Отменена @break
                                    @endswitch
                                </span>
                            </td>
                        </tr>
                        
                        <tr>
                            <td><i class="bi bi-currency-dollar me-1"></i> Цена</td>
                            <td><strong>{{ number_format($consultation->price, 0, '', ' ') }} ₽</strong></td>
                        </tr>
                        
                        <tr>
                            <td><i class="bi bi-credit-card me-1"></i> Оплата</td>
                            <td>
                                <span class="indicator indicator-{{ $consultation->payment_status === 'paid' ? 'success' : 'warning' }}">
                                    @if($consultation->payment_status === 'paid')
                                        <i class="bi bi-check-circle me-1"></i> Оплачено
                                    @else
                                        <i class="bi bi-clock me-1"></i> Ожидает оплаты
                                    @endif
                                </span>
                            </td>
                        </tr>
                        
                        @if($consultation->scheduled_at)
                            <tr>
                                <td><i class="bi bi-calendar-event me-1"></i> Назначена</td>
                                <td>
                                    {{ \Carbon\Carbon::parse($consultation->scheduled_at)->translatedFormat('d F Y в H:i') }}
                                </td>
                            </tr>
                        @endif
                        
                        @if($consultation->duration)
                            <tr>
                                <td><i class="bi bi-hourglass me-1"></i> Длительность</td>
                                <td>{{ $consultation->duration }} мин.</td>
                            </tr>
                        @endif
                        
                        @if($consultation->paid_at)
                            <tr>
                                <td><i class="bi bi-cash-coin me-1"></i> Оплачено</td>
                                <td>{{ \Carbon\Carbon::parse($consultation->paid_at)->translatedFormat('d.m.Y H:i') }}</td>
                            </tr>
                        @endif
                        
                        <tr>
                            <td><i class="bi bi-calendar-plus me-1"></i> Создана</td>
                            <td>{{ $consultation->created_at->translatedFormat('d F Y в H:i') }}</td>
                        </tr>
                        
                        @if($consultation->completed_at)
                            <tr>
                                <td><i class="bi bi-calendar-check me-1"></i> Завершена</td>
                                <td>{{ \Carbon\Carbon::parse($consultation->completed_at)->translatedFormat('d.m.Y H:i') }}</td>
                            </tr>
                        @endif
                    </table>
                    
                    <!-- Действия -->
                    @if(auth()->user()->id === $consultation->user_id || auth()->user()->role === 'admin')
                        <div class="action-buttons mt-4 pt-3 border-top">
                            @if(in_array($consultation->status, ['pending', 'scheduled']) && auth()->user()->id === $consultation->user_id)
                                <form action="{{ route('diagnostic.consultation.cancel', $consultation->id) }}" 
                                      method="POST" 
                                      onsubmit="return confirm('Вы уверены, что хотите отменить консультацию?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn action-btn-danger">
                                        <i class="bi bi-x-circle me-1"></i> Отменить
                                    </button>
                                </form>
                            @endif
                            
                            @if(auth()->user()->id === $consultation->expert_id && $consultation->status === 'scheduled')
                                <form action="{{ route('diagnostic.consultation.start', $consultation->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="action-btn action-btn-success">
                                        <i class="bi bi-play-circle me-1"></i> Начать
                                    </button>
                                </form>
                            @endif
                            
                            @if($consultation->expert_id === auth()->user()->id && $consultation->status === 'in_progress')
                                <form action="{{ route('diagnostic.consultation.complete', $consultation->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="action-btn action-btn-primary">
                                        <i class="bi bi-check-circle me-1"></i> Завершить
                                    </button>
                                </form>
                            @endif
                            
                           
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Обратная связь -->
            @if($consultation->customer_feedback || $consultation->rating)
            <div class="info-card">
                <div class="info-card-header">
                    <h6><i class="bi bi-star me-2"></i>Обратная связь</h6>
                </div>
                <div class="info-card-body">
                    @if($consultation->rating)
                        <div class="mb-3">
                            <h6 class="text-muted mb-2">Оценка</h6>
                            <div class="star-rating mb-2">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="bi bi-star{{ $i <= $consultation->rating ? '-fill star-filled' : '' }}"></i>
                                @endfor
                                <span class="ms-2 fw-bold">{{ $consultation->rating }}/5</span>
                            </div>
                        </div>
                    @endif
                    
                    @if($consultation->customer_feedback)
                        <div>
                            <h6 class="text-muted mb-2">Отзыв клиента</h6>
                            <div class="card border">
                                <div class="card-body">
                                    <p class="mb-0">{{ $consultation->customer_feedback }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
        
        <!-- Правая колонка: Чат -->
        <div class="col-lg-8">
            <div class="chat-container">
                <!-- Заголовок чата -->
                <div class="chat-header">
                    <div>
                        <h5 class="mb-0">Чат консультации</h5>
                        <small class="text-muted">{{ $consultation->messages->count() }} сообщений</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @if($consultation->status === 'in_progress')
                            <div class="pulse-animation">
                                <span class="badge bg-success">
                                    <i class="bi bi-circle-fill me-1"></i> Активен
                                </span>
                            </div>
                        @endif
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="scrollToBottom()">
                            <i class="bi bi-arrow-down"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Сообщения -->
                <div class="messages-container" id="messagesContainer">
                    @forelse($consultation->messages->sortBy('created_at') as $message)
                        @include('diagnostic.consultation.partials.message', [
                            'message' => $message,
                            'consultation' => $consultation
                        ])
                    @empty
                        <div class="text-center py-5 my-auto">
                            <i class="bi bi-chat-dots display-4 text-muted mb-3"></i>
                            <p class="text-muted mb-2">Чат пуст</p>
                            <p class="text-muted small">Начните общение с экспертом</p>
                        </div>
                    @endforelse
                </div>
                
                <!-- Форма отправки сообщения -->
                @if(in_array($consultation->status, ['in_progress', 'scheduled', 'pending']) && 
                    (auth()->user()->id === $consultation->user_id || 
                     auth()->user()->id === $consultation->expert_id ||
                     auth()->user()->role === 'admin'))
                <div class="chat-input" id="chatInput">
                    <form id="messageForm" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="consultation_id" value="{{ $consultation->id }}">
                        
                        <div class="mb-3">
                            <textarea name="message" 
                                      class="form-control" 
                                      rows="2" 
                                      placeholder="Введите ваше сообщение..." 
                                      required
                                      id="messageTextarea"></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1 me-3">
                                <input type="file" 
                                       name="attachments[]" 
                                       id="fileAttachment" 
                                       multiple 
                                       class="d-none"
                                       accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt,.mp4,.avi,.mov">
                                <button type="button" 
                                        class="btn btn-outline-secondary btn-sm"
                                        onclick="document.getElementById('fileAttachment').click()">
                                    <i class="bi bi-paperclip me-1"></i> Прикрепить файл
                                </button>
                                <small class="text-muted ms-2">до 10MB каждый</small>
                                
                                <div id="filePreview" class="mt-2"></div>
                            </div>
                            
                            <button type="submit" 
                                    class="btn btn-primary" 
                                    id="sendButton">
                                <i class="bi bi-send me-1"></i> Отправить
                            </button>
                        </div>
                    </form>
                </div>
                @elseif($consultation->status === 'completed')
                <div class="chat-input">
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-2"></i> Консультация завершена. Чат закрыт для новых сообщений.
                    </div>
                </div>
                @elseif($consultation->status === 'cancelled')
                <div class="chat-input">
                    <div class="alert alert-danger mb-0">
                        <i class="bi bi-x-circle me-2"></i> Консультация отменена. Чат закрыт.
                    </div>
                </div>
                @else
                <div class="chat-input">
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-clock me-2"></i> Чат будет доступен после начала консультации.
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для изображений -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">Просмотр изображения</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" alt="" class="modal-image img-fluid" id="modalImage">
                <p class="mt-3" id="imageName"></p>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-primary" id="downloadImage" download>
                    <i class="bi bi-download me-1"></i> Скачать
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Автопрокрутка вниз
    scrollToBottom();
    
    // Обработчик для модального окна изображений
    const imageModal = document.getElementById('imageModal');
    if (imageModal) {
        imageModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const imageSrc = button.getAttribute('data-image-src');
            const imageName = button.getAttribute('data-image-name');
            
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('imageName').textContent = imageName;
            document.getElementById('downloadImage').href = imageSrc;
            document.getElementById('downloadImage').download = imageName;
        });
    }
    
    // Обработка отправки сообщения
    const messageForm = document.getElementById('messageForm');
    if (messageForm) {
        messageForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const sendButton = document.getElementById('sendButton');
            const originalText = sendButton.innerHTML;
            
            // Показываем индикатор загрузки
            sendButton.disabled = true;
            sendButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Отправка...';
            
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
                    document.getElementById('messageTextarea').value = '';
                    document.getElementById('fileAttachment').value = '';
                    document.getElementById('filePreview').innerHTML = '';
                    
                    // Добавляем новое сообщение
                    if (data.message) {
                        addMessageToChat(data.message);
                        scrollToBottom();
                        updateMessageCounter();
                    }
                } else {
                    alert(data.message || 'Ошибка при отправке сообщения');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ошибка сети');
            } finally {
                // Возвращаем кнопку в исходное состояние
                sendButton.disabled = false;
                sendButton.innerHTML = originalText;
            }
        });
    }
    
    // Предпросмотр файлов перед отправкой
    const fileInput = document.getElementById('fileAttachment');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const preview = document.getElementById('filePreview');
            preview.innerHTML = '';
            
            Array.from(this.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'd-inline-flex align-items-center bg-light rounded p-2 me-2 mb-2';
                    
                    let content = '';
                    if (file.type.startsWith('image/')) {
                        content = `
                            <img src="${e.target.result}" 
                                 alt="${file.name}" 
                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;"
                                 class="me-2">
                        `;
                    } else {
                        const icon = getFileIcon(file.name);
                        content = `
                            <div class="me-2">
                                <i class="bi ${icon} fs-4 text-muted"></i>
                            </div>
                        `;
                    }
                    
                    div.innerHTML = content + `
                        <div>
                            <small class="d-block">${truncateFileName(file.name, 20)}</small>
                            <small class="text-muted">${formatFileSize(file.size)}</small>
                        </div>
                    `;
                    
                    preview.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        });
    }
    
    // Автообновление чата
    let lastMessageId = {{ $consultation->messages->last()->id ?? 0 }};
    setInterval(checkNewMessages, 5000);
    
    // Функции
    function scrollToBottom() {
        const container = document.getElementById('messagesContainer');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }
    
    function addMessageToChat(message) {
        const container = document.getElementById('messagesContainer');
        if (!container) return;
        
        const messageHtml = createMessageHtml(message);
        container.insertAdjacentHTML('beforeend', messageHtml);
        
        // Обновляем lastMessageId
        lastMessageId = message.id;
    }
    
    function createMessageHtml(message) {
        const isOwnMessage = message.user_id === {{ auth()->id() }};
        const isClient = message.user_id === {{ $consultation->user_id }};
        const isSystem = message.type === 'system';
        const date = new Date(message.created_at);
        const time = date.toLocaleTimeString('ru-RU', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        
        let attachmentsHtml = '';
        if (message.metadata && message.metadata.attachments) {
            attachmentsHtml = `
                <div class="message-attachments">
                    ${message.metadata.attachments.map(attachment => `
                        <div class="attachment-item">
                            <div class="attachment-icon">
                                <i class="bi ${getFileIcon(attachment.original_name || '')}"></i>
                            </div>
                            <div class="attachment-info">
                                <div class="attachment-name">${attachment.original_name || 'Файл'}</div>
                                ${attachment.size ? `<div class="attachment-size">${formatFileSize(attachment.size)}</div>` : ''}
                            </div>
                            <a href="/storage/${attachment.path || ''}" 
                               download="${attachment.original_name || 'file'}" 
                               class="attachment-download">
                                <i class="bi bi-download fs-5"></i>
                            </a>
                        </div>
                    `).join('')}
                </div>
            `;
        }
        
        if (isSystem) {
            return `
                <div class="message message-system animate-slide-in">
                    <div class="message-avatar">
                        <i class="bi bi-info-circle"></i>
                    </div>
                    <div class="message-content">
                        <div class="message-text">${message.message}</div>
                        ${attachmentsHtml}
                        <div class="message-time">${time}</div>
                    </div>
                </div>
            `;
        }
        
        return `
            <div class="message ${isOwnMessage ? 'own' : ''} ${isClient ? 'message-client' : 'message-expert'} animate-slide-in">
                <div class="message-avatar">
                    ${message.user_name ? message.user_name.charAt(0).toUpperCase() : '?'}
                </div>
                <div class="message-content">
                    <div class="message-header">
                        <div class="message-user">
                            ${message.user_name || (isClient ? 'Клиент' : 'Эксперт')}
                            <span class="badge bg-${isClient ? 'secondary' : 'success'} ms-1">
                                ${isClient ? 'Клиент' : 'Эксперт'}
                            </span>
                        </div>
                        <div class="message-time">${time}</div>
                    </div>
                    <div class="message-text">${message.message}</div>
                    ${attachmentsHtml}
                </div>
            </div>
        `;
    }
    
    async function checkNewMessages() {
        if (!['in_progress', 'scheduled', 'pending'].includes('{{ $consultation->status }}')) {
            return;
        }
        
        try {
            const response = await fetch('{{ route("diagnostic.consultation.messages", $consultation->id) }}?last_id=' + lastMessageId);
            const data = await response.json();
            
            if (data.success && data.messages && data.messages.length > 0) {
                data.messages.forEach(message => {
                    if (message.id > lastMessageId) {
                        addMessageToChat(message);
                        lastMessageId = message.id;
                    }
                });
                
                if (data.messages.length > 0) {
                    scrollToBottom();
                    updateMessageCounter();
                }
            }
        } catch (error) {
            console.error('Error checking messages:', error);
        }
    }
    
    function updateMessageCounter() {
        const counter = document.querySelector('.chat-header small.text-muted');
        if (counter) {
            const current = parseInt(counter.textContent.match(/\d+/)[0]) || 0;
            counter.textContent = (current + 1) + ' сообщений';
        }
    }
    
    function getFileIcon(filename) {
        const ext = filename.split('.').pop().toLowerCase();
        switch (ext) {
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
            case 'bmp':
                return 'bi-file-image';
            case 'pdf':
                return 'bi-file-pdf';
            case 'doc':
            case 'docx':
                return 'bi-file-word';
            case 'xls':
            case 'xlsx':
                return 'bi-file-excel';
            case 'mp4':
            case 'avi':
            case 'mov':
            case 'mkv':
                return 'bi-file-play';
            case 'mp3':
            case 'wav':
                return 'bi-file-music';
            default:
                return 'bi-file-earmark';
        }
    }
    
    function truncateFileName(name, maxLength) {
        if (name.length <= maxLength) return name;
        return name.substring(0, maxLength) + '...';
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }
    
    // Экспорт функций для глобального использования
    window.scrollToBottom = scrollToBottom;
});
</script>
@endpush