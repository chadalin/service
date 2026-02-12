@extends('layouts.app')

@section('title', $title)

@push('styles')
<style>
    /* Основные стили */
    .rule-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .complexity-badge {
        font-size: 0.9rem;
        padding: 0.25rem 1rem;
        border-radius: 20px;
    }
    
    .complexity-low { background: #10b981; }
    .complexity-medium { background: #f59e0b; }
    .complexity-high { background: #ef4444; }
    
    /* Карточки */
    .info-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e5e7eb;
        transition: transform 0.2s;
        margin-bottom: 1.5rem;
    }
    
    .info-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08);
    }
    
    .info-card-header {
        border-bottom: 2px solid #3b82f6;
        padding: 1rem 1.5rem;
        background: #f8fafc;
        border-radius: 12px 12px 0 0;
    }
    
    .info-card-body {
        padding: 1.5rem;
    }
    
    /* Мини-карта запчасти */
    .parts-card {
        border-left: 4px solid #f59e0b;
    }
    
    .parts-card .info-card-header {
        border-bottom-color: #f59e0b;
        background: #fffbeb;
    }
    
    .parts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }
    
    @media (max-width: 768px) {
        .parts-grid {
            grid-template-columns: 1fr;
        }
    }
    
    .part-card {
        background: white;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        padding: 1rem;
        transition: all 0.2s;
        position: relative;
        overflow: hidden;
    }
    
    .part-card:hover {
        border-color: #3b82f6;
        box-shadow: 0 4px 8px rgba(59, 130, 246, 0.1);
        transform: translateY(-2px);
    }
    
    .part-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #f59e0b, #3b82f6);
    }
    
    .part-sku {
        font-family: 'Courier New', monospace;
        font-size: 0.8rem;
        color: #6b7280;
        background: #f3f4f6;
        padding: 2px 6px;
        border-radius: 4px;
        display: inline-block;
        margin-bottom: 0.5rem;
    }
    
    .part-name {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 0.5rem;
        line-height: 1.4;
    }
    
    .part-brand {
        display: inline-block;
        font-size: 0.75rem;
        color: white;
        background: #6b7280;
        padding: 2px 8px;
        border-radius: 12px;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
    }
    
    .part-brand.original {
        background: #10b981;
    }
    
    .part-brand.bosch {
        background: #3b82f6;
    }
    
    .part-brand.denso {
        background: #ef4444;
    }
    
    .part-price {
        font-size: 1.25rem;
        font-weight: 700;
        color: #10b981;
        margin-top: 0.5rem;
    }
    
    .part-stock {
        display: inline-flex;
        align-items: center;
        font-size: 0.8rem;
        padding: 2px 8px;
        border-radius: 12px;
        margin-left: 0.5rem;
    }
    
    .stock-in {
        background: #d1fae5;
        color: #065f46;
    }
    
    .stock-low {
        background: #fef3c7;
        color: #92400e;
    }
    
    .stock-out {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .part-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
    }
    
    .part-btn {
        flex: 1;
        padding: 0.5rem;
        border-radius: 6px;
        font-size: 0.8rem;
        text-align: center;
        text-decoration: none;
        transition: all 0.2s;
    }
    
    .part-details {
        background: #3b82f6;
        color: white;
        border: 1px solid #3b82f6;
    }
    
    .part-details:hover {
        background: #2563eb;
        color: white;
    }
    
    .part-match {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fbbf24;
    }
    
    .part-match:hover {
        background: #fbbf24;
        color: #78350f;
    }
    
    .no-parts {
        text-align: center;
        padding: 2rem;
        color: #6b7280;
    }
    
    .no-parts i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    
    /* Форма консультации */
    .consultation-form {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e5e7eb;
    }
    
    .form-section {
        border-bottom: 1px solid #e5e7eb;
        padding: 1.5rem;
    }
    
    .form-section:last-child {
        border-bottom: none;
    }
    
    .form-section-title {
        font-size: 1rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
    }
    
    .form-section-title i {
        margin-right: 0.5rem;
        color: #3b82f6;
    }
    
    .required-field::after {
        content: '*';
        color: #ef4444;
        margin-left: 4px;
    }
    
    /* Загрузка файлов */
    .upload-area {
        border: 2px dashed #d1d5db;
        border-radius: 8px;
        padding: 1.5rem;
        text-align: center;
        transition: all 0.3s;
        cursor: pointer;
        background: #f9fafb;
    }
    
    .upload-area:hover {
        border-color: #3b82f6;
        background: #eff6ff;
    }
    
    .upload-area.dragover {
        border-color: #10b981;
        background: #ecfdf5;
    }
    
    .file-preview {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-top: 1rem;
    }
    
    .preview-item {
        position: relative;
        width: 80px;
        height: 80px;
        border-radius: 6px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
    }
    
    .preview-item img,
    .preview-item video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .preview-item .remove-btn {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 18px;
        height: 18px;
        background: #ef4444;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 10px;
        border: none;
    }
    
    .file-info {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0,0,0,0.7);
        color: white;
        padding: 4px;
        font-size: 10px;
        text-overflow: ellipsis;
        overflow: hidden;
        white-space: nowrap;
    }
    
    /* Стили для списков */
    .step-list {
        counter-reset: step-counter;
        list-style: none;
        padding-left: 0;
    }
    
    .step-list li {
        position: relative;
        padding: 0.75rem 1rem 0.75rem 3rem;
        margin-bottom: 0.75rem;
        background: #f8fafc;
        border-radius: 8px;
        border-left: 3px solid #3b82f6;
    }
    
    .step-list li:before {
        counter-increment: step-counter;
        content: counter(step-counter);
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        width: 24px;
        height: 24px;
        background: #3b82f6;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.875rem;
    }
    
    .cause-tag {
        display: inline-block;
        padding: 0.375rem 0.75rem;
        margin: 0.25rem;
        background: #dbeafe;
        color: #1e40af;
        border-radius: 20px;
        font-size: 0.875rem;
    }
    
    /* Цена и кнопка */
    .consultation-price {
        font-size: 1.75rem;
        font-weight: 700;
        color: #10b981;
        text-align: center;
    }
    
    .submit-btn {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        padding: 0.875rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 1rem;
        transition: all 0.3s;
        width: 100%;
    }
    
    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    .submit-btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
    }
    
    /* Гриды */
    .compact-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    @media (max-width: 768px) {
        .compact-grid {
            grid-template-columns: 1fr;
        }
        
        .form-section {
            padding: 1rem;
        }
    }

    /* Стили для консультаций */
.consultations-feed {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.consultation-preview {
    background: white;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    transition: all 0.2s;
}

.consultation-preview:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
    transform: translateY(-2px);
}

.consultation-preview-header {
    padding: 0.875rem 1.25rem;
    background: linear-gradient(to right, #f8fafc, #f1f5f9);
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.consultation-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.consultation-id {
    font-size: 0.85rem;
    font-weight: 600;
    color: #1e293b;
    background: #e2e8f0;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
}

.consultation-date {
    font-size: 0.8rem;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.consultation-status {
    font-size: 0.75rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.consultation-status.status-completed {
    background: #dcfce7;
    color: #166534;
}

.consultation-status.status-in_progress {
    background: #fef9c3;
    color: #854d0e;
}

.consultation-status.status-pending {
    background: #fee2e2;
    color: #991b1b;
}

.consultation-expert {
    font-size: 0.85rem;
    color: #3b82f6;
    display: flex;
    align-items: center;
    gap: 0.35rem;
    background: #dbeafe;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
}

.consultation-preview-body {
    padding: 1.25rem;
    display: flex;
    gap: 1.25rem;
}

@media (max-width: 768px) {
    .consultation-preview-body {
        flex-direction: column;
    }
}

.consultation-images {
    display: flex;
    gap: 0.5rem;
    flex-shrink: 0;
}

.consultation-image-wrapper {
    position: relative;
    width: 80px;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #e5e7eb;
    cursor: pointer;
    transition: transform 0.2s;
}

.consultation-image-wrapper:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.consultation-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.more-images-badge {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    font-weight: 600;
}

.no-images-placeholder {
    width: 80px;
    height: 80px;
    background: #f1f5f9;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    border: 1px dashed #cbd5e1;
}

.no-images-placeholder i {
    font-size: 1.5rem;
    margin-bottom: 0.25rem;
}

.no-images-placeholder span {
    font-size: 0.7rem;
}

.consultation-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.consultation-description {
    font-size: 0.95rem;
    color: #1e293b;
    line-height: 1.5;
}

.consultation-feedback {
    font-size: 0.85rem;
    color: #64748b;
    background: #f8fafc;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    border-left: 3px solid #f59e0b;
    font-style: italic;
}

.consultation-feedback i {
    color: #f59e0b;
    margin-right: 0.5rem;
    opacity: 0.7;
}

.consultation-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 0.5rem;
}

.consultation-car-info {
    font-size: 0.8rem;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 0.35rem;
    background: #f1f5f9;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
}

.btn-view-consultation {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.85rem;
    font-weight: 500;
    color: #3b82f6;
    text-decoration: none;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    background: #eff6ff;
    transition: all 0.2s;
}

.btn-view-consultation:hover {
    background: #3b82f6;
    color: white;
}

.btn-view-consultation i {
    transition: transform 0.2s;
}

.btn-view-consultation:hover i {
    transform: translateX(4px);
}

/* Пустое состояние */
.no-consultations {
    text-align: center;
    padding: 2.5rem;
    background: #f8fafc;
    border-radius: 12px;
    border: 2px dashed #cbd5e1;
}

.no-consultations i {
    font-size: 3rem;
    color: #94a3b8;
    margin-bottom: 1rem;
}

.no-consultations h5 {
    color: #334155;
    margin-bottom: 0.5rem;
}

.no-consultations p {
    max-width: 400px;
    margin: 0 auto;
}

/* Адаптивность */
@media (max-width: 768px) {
    .consultation-preview-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .consultation-meta {
        width: 100%;
    }
    
    .consultation-expert {
        width: 100%;
    }
    
    .consultation-images {
        width: 100%;
        justify-content: flex-start;
    }
    
    .consultation-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .btn-view-consultation {
        width: 100%;
        justify-content: center;
    }
}
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- Заголовок -->
    <div class="rule-header">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h4 class="mb-2">
                    <i class="bi bi-clipboard-check me-2"></i>
                    {{ $title }}
                </h4>
                <p class="mb-0 opacity-90">
                    {{ $rule->symptom->description ?? 'Подробное описание симптома' }}
                </p>
            </div>
            <div class="d-flex flex-column align-items-end gap-2">
                <div>
                    <span class="complexity-badge complexity-{{ $rule->complexity_level <= 3 ? 'low' : ($rule->complexity_level <= 6 ? 'medium' : 'high') }}">
                        Сложность: {{ $rule->complexity_level }}/10
                    </span>
                </div>
                <div class="consultation-price">
                    {{ number_format($rule->base_consultation_price, 0, '', ' ') }} ₽
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Левая колонка: Информация о правиле -->
        <div class="col-lg-8">
            <!-- Шаги диагностики -->
            <div class="info-card">
                <div class="info-card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-list-check me-2"></i>
                        Шаги диагностики
                    </h6>
                </div>
                <div class="info-card-body">
                    @if(!empty($rule->diagnostic_steps) && count($rule->diagnostic_steps) > 0)
                        <ol class="step-list">
                            @foreach($rule->diagnostic_steps as $step)
                                <li>{{ $step }}</li>
                            @endforeach
                        </ol>
                    @else
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Шаги диагностики не указаны
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Возможные причины -->
            <div class="info-card">
                <div class="info-card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Возможные причины
                    </h6>
                </div>
                <div class="info-card-body">
                    @if(!empty($rule->possible_causes) && count($rule->possible_causes) > 0)
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($rule->possible_causes as $cause)
                                <span class="cause-tag">{{ $cause }}</span>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Возможные причины не указаны
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Требуемые данные -->
            <div class="info-card">
                <div class="info-card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-clipboard-data me-2"></i>
                        Требуемые данные
                    </h6>
                </div>
                <div class="info-card-body">
                    @if(!empty($rule->required_data) && count($rule->required_data) > 0)
                        <ul class="list-group list-group-flush">
                            @foreach($rule->required_data as $data)
                                <li class="list-group-item d-flex align-items-center py-2 px-0">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    {{ $data }}
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Требуемые данные не указаны
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Мини-карта запчасти -->
            <div class="info-card parts-card">
                <div class="info-card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-tools me-2"></i>
                        💡 Возможно для ремонта понадобятся похожие запчасти
                    </h6>
                </div>
                <div class="info-card-body">
                    @if($matchedPriceItems && $matchedPriceItems->count() > 0)
                        <p class="text-muted mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            На основе симптома "{{ $rule->symptom->name ?? '' }}" и возможных причин найдены следующие запчасти:
                        </p>
                        
                        <div class="parts-grid">
                            @foreach($matchedPriceItems as $item)
                                <div class="part-card">
                                    <div class="part-sku">{{ $item->sku }}</div>
                                    <div class="part-name">{{ Str::limit($item->name, 60) }}</div>
                                    
                                    @if($item->catalog_brand || $item->brand)
                                        <div>
                                            @if($item->catalog_brand)
                                                <span class="part-brand {{ strtolower($item->catalog_brand) == 'original' ? 'original' : (in_array(strtolower($item->catalog_brand), ['bosch', 'denso', 'kyb', 'bilstein']) ? strtolower($item->catalog_brand) : '') }}">
                                                    {{ $item->catalog_brand }}
                                                </span>
                                            @endif
                                            @if($item->brand)
                                                <span class="part-brand">{{ $item->brand->name }}</span>
                                            @endif
                                        </div>
                                    @endif
                                    
                                    <div class="d-flex align-items-center justify-content-between mt-2">
                                        @if($item->price > 0)
                                            <div class="part-price">
                                                {{ number_format($item->price, 0, '', ' ') }} ₽
                                            </div>
                                        @else
                                            <div class="text-muted">Цена не указана</div>
                                        @endif
                                        
                                        <span class="part-stock {{ $item->quantity > 10 ? 'stock-in' : ($item->quantity > 0 ? 'stock-low' : 'stock-out') }}">
                                            <i class="bi bi-{{ $item->quantity > 10 ? 'check-circle' : ($item->quantity > 0 ? 'exclamation-triangle' : 'x-circle') }} me-1"></i>
                                            {{ $item->quantity > 0 ? $item->quantity . ' шт' : 'Нет в наличии' }}
                                        </span>
                                    </div>
                                    
                                    @if($item->description)
                                        <div class="text-muted small mt-2">
                                            {{ Str::limit($item->description, 80) }}
                                        </div>
                                    @endif
                                    
                                    <div class="part-actions">
                                        <a href="{{ route('admin.price.show', $item->id) }}" 
                                           class="part-btn part-details"
                                           target="_blank">
                                            <i class="bi bi-eye me-1"></i> Подробнее
                                        </a>
                                        <a href="{{ route('admin.price.index', ['search' => $item->sku]) }}" 
                                           class="part-btn part-match"
                                           target="_blank">
                                            <i class="bi bi-search me-1"></i> Поиск
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="mt-3 text-center">
                            <a href="{{ route('admin.price.index', ['search' => $rule->symptom->name ?? '']) }}" 
                               class="btn btn-outline-warning btn-sm">
                                <i class="bi bi-search me-1"></i> Найти больше запчастей
                            </a>
                        </div>
                    @else
                        <div class="no-parts">
                            <i class="bi bi-patch-question"></i>
                            <h5>Связанные запчасти не найдены</h5>
                            <p class="text-muted">
                                Запчасти, связанные с этим симптомом, пока не добавлены в базу.
                            </p>
                            <div class="mt-3">
                                <a href="{{ route('admin.price.import.select') }}" 
                                   class="btn btn-outline-primary btn-sm me-2">
                                    <i class="bi bi-upload me-1"></i> Импортировать прайс
                                </a>
                                <a href="{{ route('admin.price.index') }}" 
                                   class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-list-ul me-1"></i> Весь прайс-лист
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
            

        <!-- Правая колонка: Форма заказа консультации -->
        <div class="col-lg-4">
            <div class="consultation-form sticky-top" style="top: 1rem;">
                <!-- Основная информация -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-info-circle"></i>
                        Информация для консультации
                    </div>
                    
                    <form id="consultationOrderForm" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="rule_id" value="{{ $rule->id }}">
                        <input type="hidden" name="consultation_type" value="expert">
                        <input type="hidden" name="brand_id" value="{{ $rule->brand_id ?? '' }}">
                        
                        @if($rule->symptoms && $rule->symptoms->isNotEmpty())
                            @foreach($rule->symptoms as $symptom)
                                <input type="hidden" name="symptoms[]" value="{{ $symptom->id }}">
                            @endforeach
                        @endif
                        
                        <!-- Описание симптома -->
                        <div class="mb-3">
                            <label for="symptom_description" class="form-label fw-semibold required-field">
                                Опишите ваш симптом подробнее
                            </label>
                            <textarea class="form-control" 
                                      id="symptom_description" 
                                      name="symptom_description" 
                                      rows="3"
                                      placeholder= "{{ $rule->symptom->description ?? 'Подробное описание симптома' }}""• Когда началась проблема?&#10;• При каких условиях проявляется?&#10;• Какие симптомы сопровождают?&#10;• Что уже пробовали сделать?"
                                      required></textarea>
                        </div>
                        
                        <!-- Контактная информация -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold required-field">Контактная информация</label>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <input type="text" 
                                           name="contact_name" 
                                           class="form-control" 
                                           placeholder="Ваше имя" 
                                           required>
                                </div>
                                <div class="col-md-6">
                                    <input type="tel" 
                                           name="contact_phone" 
                                           class="form-control" 
                                           placeholder="Телефон" 
                                           required>
                                </div>
                                <div class="col-12 mt-2">
                                    <input type="email" 
                                           name="contact_email" 
                                           class="form-control" 
                                           placeholder="Email" 
                                           required>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Данные автомобиля -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Данные автомобиля</label>
                            <div class="compact-grid">
                                <div>
                                    <input type="number" 
                                           name="year" 
                                           class="form-control" 
                                           placeholder="Год выпуска"
                                           min="1990" 
                                           max="{{ date('Y') }}">
                                </div>
                                <div>
                                    <input type="number" 
                                           name="mileage" 
                                           class="form-control" 
                                           placeholder="Пробег, км"
                                           min="0" 
                                           max="1000000">
                                </div>
                                <div>
                                    <input type="text" 
                                           name="vin" 
                                           class="form-control" 
                                           placeholder="VIN код"
                                           maxlength="17">
                                </div>
                                <div>
                                    <select name="engine_type" class="form-select">
                                        <option value="">Тип двигателя</option>
                                        <option value="Бензин">Бензин</option>
                                        <option value="Дизель">Дизель</option>
                                        <option value="Гибрид">Гибрид</option>
                                        <option value="Электрический">Электрический</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Загрузка файлов -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Дополнительные материалы</label>
                            
                            <!-- Протоколы диагностики -->
                            <div class="mb-3">
                                <small class="text-muted d-block mb-2">Протоколы диагностики (PDF, DOC, JPG)</small>
                                <div class="upload-area" 
                                     onclick="document.getElementById('protocol_files').click()">
                                    <i class="bi bi-cloud-arrow-up fs-4 text-muted mb-2"></i>
                                    <p class="mb-1 small">Перетащите или нажмите для загрузки</p>
                                    <small class="text-muted">Можно загрузить несколько файлов</small>
                                    <input type="file" 
                                           id="protocol_files" 
                                           name="protocol_files[]" 
                                           multiple 
                                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                           class="d-none"
                                           onchange="handleFileUpload(this, 'protocol-preview')">
                                </div>
                                <div class="file-preview" id="protocol-preview"></div>
                            </div>
                            
                            <!-- Фото -->
                            <div class="mb-3">
                                <small class="text-muted d-block mb-2">Фото симптома (JPG, PNG)</small>
                                <div class="upload-area" 
                                     onclick="document.getElementById('symptom_photos').click()">
                                    <i class="bi bi-image fs-4 text-muted mb-2"></i>
                                    <p class="mb-1 small">Фото индикаторов, деталей и т.д.</p>
                                    <input type="file" 
                                           id="symptom_photos" 
                                           name="symptom_photos[]" 
                                           multiple 
                                           accept="image/*"
                                           class="d-none"
                                           onchange="handleFileUpload(this, 'photo-preview')">
                                </div>
                                <div class="file-preview" id="photo-preview"></div>
                            </div>
                            
                            <!-- Видео -->
                            <div class="mb-3">
                                <small class="text-muted d-block mb-2">Видео (MP4, AVI, MOV)</small>
                                <div class="upload-area" 
                                     onclick="document.getElementById('symptom_videos').click()">
                                    <i class="bi bi-camera-video fs-4 text-muted mb-2"></i>
                                    <p class="mb-1 small">Звуки, поведение автомобиля</p>
                                    <input type="file" 
                                           id="symptom_videos" 
                                           name="symptom_videos[]" 
                                           multiple 
                                           accept="video/*"
                                           class="d-none"
                                           onchange="handleFileUpload(this, 'video-preview')">
                                </div>
                                <div class="file-preview" id="video-preview"></div>
                            </div>
                        </div>
                        
                        <!-- Дополнительная информация -->
                        <div class="mb-3">
                            <label for="additional_info" class="form-label fw-semibold">Дополнительная информация</label>
                            <textarea class="form-control" 
                                      id="additional_info" 
                                      name="additional_info" 
                                      rows="2"
                                      placeholder="Любая другая важная информация..."></textarea>
                        </div>
                        
                        <!-- Согласие -->
                        <div class="form-check mb-4">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="agreement" 
                                   name="agreement"
                                   required>
                            <label class="form-check-label small" for="agreement">
                                Я согласен с условиями оказания услуг и обработкой персональных данных
                            </label>
                        </div>
                        
                        <!-- Кнопка отправки -->
                        <div class="text-center">
                            <div class="consultation-price mb-3">
                                {{ number_format($rule->base_consultation_price, 0, '', ' ') }} ₽
                            </div>
                            <button type="submit" 
                                    class="submit-btn"
                                    id="buyConsultationBtn">
                                <i class="bi bi-credit-card me-2"></i>
                                <span id="btnText">Заказать консультацию</span>
                                <span class="spinner-border spinner-border-sm ms-2 d-none" id="loadingSpinner"></span>
                            </button>
                            <small class="text-muted d-block mt-2">
                                <i class="bi bi-shield-check me-1"></i>
                                Консультация проводится сертифицированным экспертом
                            </small>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
            <!-- СВЯЗАННЫЕ КОНСУЛЬТАЦИИ -->
<div class="info-card mt-4">
    <div class="info-card-header">
        <h6 class="mb-0">
            <i class="bi bi-chat-dots me-2"></i>
            Консультации по данному симптому
        </h6>
    </div>
    <div class="info-card-body">
        @if($consultations && $consultations->count() > 0)
            <div class="consultations-feed">
                @foreach($consultations as $consultation)
                    <div class="consultation-preview">
                        <div class="consultation-preview-header">
                            <div class="consultation-meta">
                                <span class="consultation-id">
                                    <i class="bi bi-hash"></i> Консультация #{{ $consultation->id }}
                                </span>
                                <span class="consultation-date">
                                    <i class="bi bi-calendar3"></i> {{ $consultation->created_at->format('d.m.Y') }}
                                </span>
                                <span class="consultation-status status-{{ $consultation->status }}">
                                    {{ $consultation->status == 'completed' ? 'Завершена' : ($consultation->status == 'in_progress' ? 'В работе' : 'Ожидает') }}
                                </span>
                            </div>
                            @if($consultation->expert)
                                <div class="consultation-expert">
                                    <i class="bi bi-person-badge"></i>
                                    Эксперт: {{ $consultation->expert->name ?? 'Специалист' }}
                                </div>
                            @endif
                        </div>
                        
                        <div class="consultation-preview-body">


        <!-- Блок с фото консультации -->
@if($consultation->preview_images && count($consultation->preview_images) > 0)
    <div class="consultation-images">
        @foreach($consultation->preview_images as $index => $image)
            <div class="consultation-image-wrapper" onclick="openImageModal('{{ asset($image) }}')">
                <img src="{{ asset($image) }}" 
                     alt="Фото консультации"
                     class="consultation-image"
                     loading="lazy"
                     onerror="this.onerror=null; this.src='{{ asset('img/no-image.jpg') }}';">
                @if($index === 2 && count($consultation->preview_images) > 3)
                    <div class="more-images-badge">
                        +{{ count($consultation->preview_images) - 3 }}
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@else
    <div class="consultation-images">
        <div class="no-images-placeholder">
            <i class="bi bi-image"></i>
            <span>Нет фото</span>
        </div>
    </div>
@endif

<!-- Дополнительная информация о файлах -->
@if($consultation->case && !empty($consultation->case->uploaded_files))
    @php
        $files = is_string($consultation->case->uploaded_files) 
            ? json_decode($consultation->case->uploaded_files, true) 
            : $consultation->case->uploaded_files;
        
        $hasDocuments = isset($files['protocol_files']) && count($files['protocol_files']) > 0;
        $hasVideos = isset($files['symptom_videos']) && count($files['symptom_videos']) > 0;
    @endphp
    
    @if($hasDocuments || $hasVideos)
        <div class="consultation-files mt-2">
            @if($hasDocuments)
                <span class="file-badge">
                    <i class="bi bi-file-earmark-text"></i>
                    {{ count($files['protocol_files']) }} документов
                </span>
            @endif
            
            @if($hasVideos)
                <span class="file-badge">
                    <i class="bi bi-camera-video"></i>
                    {{ count($files['symptom_videos']) }} видео
                </span>
            @endif
        </div>
    @endif
@endif
                            
                            <div class="consultation-content">
                                <div class="consultation-description">
                                    {{ $consultation->short_description }}
                                </div>
                                
                                @if($consultation->customer_feedback)
                                    <div class="consultation-feedback">
                                        <i class="bi bi-quote"></i>
                                        {{ Str::limit($consultation->customer_feedback, 100) }}
                                    </div>
                                @endif
                                
                                <div class="consultation-footer">
                                    @if($consultation->case)
                                        <div class="consultation-car-info">
                                            <i class="bi bi-car-front"></i>
                                            @if($consultation->case->brand)
                                                {{ $consultation->case->brand->name ?? '' }}
                                            @endif
                                            @if($consultation->case->model)
                                                {{ $consultation->case->model->name ?? '' }}
                                            @endif
                                            @if($consultation->case->year)
                                                {{ $consultation->case->year }}
                                            @endif
                                        </div>
                                    @endif
                                    
                                    <a href="{{ route('admin.diagnostic.consultations.show', $consultation->id) }}" 
                                       class="btn-view-consultation">
                                        Подробнее
                                        <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
                
                @if($consultations->count() >= 6)
                    <div class="text-center mt-4">
                        <a href="{{ route('admin.diagnostic.consultations.index', ['rule_id' => $rule->id]) }}" 
                           class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-arrow-right-circle me-1"></i>
                            Все консультации по данному симптому
                        </a>
                    </div>
                @endif
            </div>
        @else
            <div class="no-consultations">
                <i class="bi bi-chat-square-text"></i>
                <h5>Пока нет консультаций</h5>
                <p class="text-muted">
                    По данному симптому еще не было консультаций. 
                    Будьте первым, кто закажет консультацию эксперта!
                </p>
                <div class="mt-3">
                    <button type="button" 
                            class="btn btn-primary btn-sm"
                            onclick="document.querySelector('.consultation-form').scrollIntoView({ behavior: 'smooth' })">
                        <i class="bi bi-plus-circle me-1"></i>
                        Заказать консультацию
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Модальное окно для просмотра изображений -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-0">
                <img src="" id="modalImage" class="img-fluid" alt="Просмотр изображения">
            </div>
        </div>
    </div>
</div>


@if(config('app.debug'))
    <div class="debug-info" style="background: #f1f5f9; padding: 1rem; margin: 1rem 0; border-radius: 8px; font-size: 0.8rem;">
        <strong>Debug:</strong>
        <div>Symptom ID: {{ $rule->symptom_id }}</div>
        <div>Consultations found: {{ $consultations ? $consultations->count() : 0 }}</div>
        @if($consultations && $consultations->count() > 0)
            <div>Consultation IDs: {{ $consultations->pluck('id')->join(', ') }}</div>
            <div>Case IDs: {{ $consultations->pluck('case_id')->join(', ') }}</div>
        @endif
    </div>
@endif

    <!-- Кнопки действий -->
    <div class="d-flex justify-content-between align-items-center mt-4 pt-4 border-top">
        <div>
            <a href="{{ route('admin.diagnostic.rules.index') }}" 
               class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> К списку правил
            </a>
        </div>
        <div>
            <a href="{{ route('admin.diagnostic.rules.edit', $rule->id) }}" 
               class="btn btn-warning">
                <i class="bi bi-pencil me-1"></i> Редактировать
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Глобальные переменные для хранения файлов
let uploadedFiles = {
    protocols: [],
    photos: [],
    videos: []
};

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    initFileUpload();
    initFormSubmit();
    initPhoneMask();
});

// Маска для телефона
function initPhoneMask() {
    const phoneInput = document.querySelector('input[name="contact_phone"]');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length > 0) {
                if (value[0] === '8') {
                    value = '7' + value.substring(1);
                }
                if (value.length === 1) {
                    value = '+7' + value;
                }
            }
            
            let formatted = value;
            if (value.length > 1) {
                formatted = '+7 (' + value.substring(1, 4);
            }
            if (value.length >= 5) {
                formatted += ') ' + value.substring(4, 7);
            }
            if (value.length >= 8) {
                formatted += '-' + value.substring(7, 9);
            }
            if (value.length >= 10) {
                formatted += '-' + value.substring(9, 11);
            }
            
            e.target.value = formatted.substring(0, 18);
        });
    }
}

// Инициализация загрузки файлов
function initFileUpload() {
    const uploadAreas = document.querySelectorAll('.upload-area');
    
    uploadAreas.forEach(area => {
        // Обработка перетаскивания
        area.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        area.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        area.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            const inputId = this.getAttribute('onclick').match(/'([^']+)'/)[1];
            const input = document.getElementById(inputId);
            
            // Создаем новый DataTransfer
            const dataTransfer = new DataTransfer();
            
            // Добавляем существующие файлы
            if (input.files) {
                for (let i = 0; i < input.files.length; i++) {
                    dataTransfer.items.add(input.files[i]);
                }
            }
            
            // Добавляем новые файлы
            for (let i = 0; i < files.length; i++) {
                dataTransfer.items.add(files[i]);
            }
            
            // Обновляем input
            input.files = dataTransfer.files;
            
            // Запускаем обработку
            const previewId = input.getAttribute('onchange').match(/'([^']+)'/)[1];
            handleFileUpload(input, previewId);
        });
    });
}

// Обработка загрузки файлов
function handleFileUpload(input, previewContainerId) {
    const previewContainer = document.getElementById(previewContainerId);
    const files = Array.from(input.files);
    const type = previewContainerId.split('-')[0]; // protocol, photo, video
    
    // Очищаем превью
    previewContainer.innerHTML = '';
    
    // Ограничиваем количество файлов (макс 10 каждого типа)
    if (files.length > 10) {
        alert('Максимально можно загрузить 10 файлов одного типа');
        files.splice(10);
    }
    
    // Обновляем глобальный массив
    uploadedFiles[type] = files;
    
    // Создаем превью для каждого файла
    files.forEach((file, index) => {
        const previewItem = createFilePreview(file, index, type);
        previewContainer.appendChild(previewItem);
    });
    
    // Обновляем счетчик файлов
    updateFileCount();
}

// Создание элемента предпросмотра файла
function createFilePreview(file, index, type) {
    const previewItem = document.createElement('div');
    previewItem.className = 'preview-item';
    previewItem.dataset.index = index;
    previewItem.dataset.type = type;
    
    let content = '';
    const fileType = file.type.split('/')[0];
    const reader = new FileReader();
    
    if (fileType === 'image') {
        // Для изображений
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.alt = 'Preview';
            previewItem.insertBefore(img, previewItem.firstChild);
        };
        reader.readAsDataURL(file);
        content = '';
    } else if (fileType === 'video') {
        // Для видео
        reader.onload = function(e) {
            const video = document.createElement('video');
            video.controls = true;
            video.innerHTML = `<source src="${e.target.result}" type="${file.type}">`;
            previewItem.insertBefore(video, previewItem.firstChild);
        };
        reader.readAsDataURL(file);
        content = '';
    } else {
        // Для документов
        const icon = getFileIcon(file);
        content = `
            <div class="d-flex flex-column align-items-center justify-content-center h-100">
                <i class="${icon} fs-3 text-secondary"></i>
                <small class="mt-2 text-center px-2">${file.name.split('.').pop().toUpperCase()}</small>
            </div>
        `;
    }
    
    // Информация о файле
    const fileInfo = `
        <div class="file-info">
            ${truncateFileName(file.name, 12)}<br>
            ${formatFileSize(file.size)}
        </div>
    `;
    
    // Кнопка удаления
    const removeBtn = `
        <button type="button" class="remove-btn" onclick="removeFilePreview(this)">
            <i class="bi bi-x"></i>
        </button>
    `;
    
    previewItem.innerHTML = content + fileInfo + removeBtn;
    return previewItem;
}

// Удаление файла из превью
function removeFilePreview(button) {
    const previewItem = button.closest('.preview-item');
    const index = parseInt(previewItem.dataset.index);
    const type = previewItem.dataset.type;
    
    // Удаляем из глобального массива
    uploadedFiles[type].splice(index, 1);
    
    // Удаляем элемент
    previewItem.remove();
    
    // Обновляем input файлов
    updateFileInput(type);
    
    // Обновляем счетчик
    updateFileCount();
}

// Обновление input файлов
function updateFileInput(type) {
    const inputId = {
        protocols: 'protocol_files',
        photos: 'symptom_photos',
        videos: 'symptom_videos'
    }[type];
    
    const input = document.getElementById(inputId);
    const dataTransfer = new DataTransfer();
    
    uploadedFiles[type].forEach(file => {
        dataTransfer.items.add(file);
    });
    
    input.files = dataTransfer.files;
    
    // Обновляем превью
    const previewId = type + '-preview';
    const previewContainer = document.getElementById(previewId);
    previewContainer.innerHTML = '';
    
    uploadedFiles[type].forEach((file, index) => {
        const previewItem = createFilePreview(file, index, type);
        previewContainer.appendChild(previewItem);
    });
}

// Обновление счетчика файлов
function updateFileCount() {
    const totalFiles = Object.values(uploadedFiles).reduce((sum, arr) => sum + arr.length, 0);
    const btn = document.getElementById('buyConsultationBtn');
    
    if (btn && totalFiles > 0) {
        const text = btn.querySelector('#btnText');
        if (text) {
            text.textContent = `Заказать консультацию (${totalFiles} файлов)`;
        }
    }
}

// Инициализация отправки формы
function initFormSubmit() {
    const form = document.getElementById('consultationOrderForm');
    const btn = document.getElementById('buyConsultationBtn');
    const btnText = document.getElementById('btnText');
    const loadingSpinner = document.getElementById('loadingSpinner');
    
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Валидация обязательных полей
        const requiredFields = [
            'symptom_description',
            'contact_name', 
            'contact_phone',
            'contact_email',
            'agreement'
        ];
        
        let isValid = true;
        let firstInvalidField = null;
        
        requiredFields.forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (!field || (field.type === 'checkbox' && !field.checked) || 
                (field.type !== 'checkbox' && !field.value.trim())) {
                isValid = false;
                if (!firstInvalidField) firstInvalidField = field;
                
                // Добавляем стиль ошибки
                if (field) {
                    field.classList.add('is-invalid');
                    field.addEventListener('input', function() {
                        this.classList.remove('is-invalid');
                    }, { once: true });
                }
            }
        });
        
        if (!isValid) {
            alert('Пожалуйста, заполните все обязательные поля');
            if (firstInvalidField) firstInvalidField.focus();
            return;
        }
        
        // Валидация email
        const emailField = form.querySelector('[name="contact_email"]');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailField.value)) {
            alert('Пожалуйста, введите корректный email адрес');
            emailField.focus();
            emailField.classList.add('is-invalid');
            return;
        }
        
        // Показываем загрузку
        btn.disabled = true;
        btnText.textContent = 'Оформление заказа...';
        loadingSpinner.classList.remove('d-none');
        
        try {
            // Создаем FormData
            const formData = new FormData(form);
            
            // Добавляем файлы из глобальных массивов
            Object.entries(uploadedFiles).forEach(([type, files]) => {
                files.forEach((file) => {
                    formData.append(`${type}_files[]`, file);
                });
            });
            
            // Отправляем запрос
            const response = await fetch('{{ route("consultation.order") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            });
            
            const result = await response.json();
            
            if (response.ok && result.success !== false) {
                // Перенаправляем на страницу успеха
                if (result.redirect_url) {
                    window.location.href = result.redirect_url;
                } else if (result.order_id) {
                    window.location.href = '/consultation/success/' + result.order_id;
                } else {
                    window.location.href = '{{ route("consultation.success", "new") }}';
                }
            } else {
                throw new Error(result.message || 'Ошибка при оформлении заказа');
            }
            
        } catch (error) {
            console.error('Error:', error);
            alert('Ошибка: ' + error.message);
            
            // Восстанавливаем кнопку
            btn.disabled = false;
            btnText.textContent = 'Заказать консультацию';
            loadingSpinner.classList.add('d-none');
        }
    });
}

// Вспомогательные функции
function getFileIcon(file) {
    const extension = file.name.split('.').pop().toLowerCase();
    
    if (['jpg', 'jpeg', 'png', 'gif', 'bmp'].includes(extension)) {
        return 'bi bi-file-image';
    } else if (['pdf'].includes(extension)) {
        return 'bi bi-file-pdf';
    } else if (['doc', 'docx'].includes(extension)) {
        return 'bi bi-file-word';
    } else if (['mp4', 'avi', 'mov', 'mkv'].includes(extension)) {
        return 'bi bi-file-play';
    } else {
        return 'bi bi-file-earmark';
    }
}

function truncateFileName(name, maxLength) {
    if (name.length <= maxLength) return name;
    const ext = name.split('.').pop();
    const nameWithoutExt = name.substring(0, name.length - ext.length - 1);
    return nameWithoutExt.substring(0, maxLength) + '...' + ext;
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}


// Функция для открытия модального окна с изображением
function openImageModal(imageUrl) {
    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
    const modalImage = document.getElementById('modalImage');
    modalImage.src = imageUrl;
    modal.show();
}

// Инициализация модального окна при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    // Добавляем модальное окно в DOM, если его нет
    if (!document.getElementById('imageModal')) {
        const modalHTML = `
            <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header border-0">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center p-0">
                            <img src="" id="modalImage" class="img-fluid" alt="Просмотр изображения">
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
});
</script>
@endpush