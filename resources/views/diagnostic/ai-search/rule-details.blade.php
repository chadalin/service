@extends('layouts.app')

@section('title', $title)

@push('styles')
<style>
    .repair-guide-container {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    @media (min-width: 992px) {
        .repair-guide-container {
            grid-template-columns: 2fr 1fr;
        }
    }
    
    .guide-section {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    
    .parts-list {
        max-height: 500px;
        overflow-y: auto;
    }
    
    .part-item {
        display: flex;
        align-items: center;
        padding: 0.75rem;
        border-bottom: 1px solid #f0f0f0;
        transition: background-color 0.2s;
    }
    
    .part-item:hover {
        background-color: #f8f9fa;
    }
    
    .part-image {
        width: 60px;
        height: 60px;
        object-fit: contain;
        margin-right: 1rem;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        padding: 0.25rem;
    }
    
    .part-info {
        flex: 1;
    }
    
    .part-name {
        font-weight: 500;
        margin-bottom: 0.25rem;
    }
    
    .part-sku {
        font-size: 0.85rem;
        color: #666;
        font-family: monospace;
    }
    
    .part-price {
        font-weight: bold;
        color: #2e7d32;
        text-align: right;
        min-width: 100px;
    }
    
    .step-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        background: #4CAF50;
        color: white;
        border-radius: 50%;
        font-weight: bold;
        margin-right: 0.75rem;
    }
    
    .tools-needed {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 1rem;
        margin: 1rem 0;
        border-radius: 0 4px 4px 0;
    }
    
    .complexity-indicator {
        display: inline-block;
        width: 100px;
        height: 10px;
        background: #e0e0e0;
        border-radius: 5px;
        overflow: hidden;
        margin-left: 1rem;
        vertical-align: middle;
    }
    
    .complexity-fill {
        height: 100%;
        background: #4CAF50;
        width: ${$rule->complexity_level * 10}%;
    }
</style>
@endpush

@section('content')
<div class="container py-4">
    <!-- Хлебные крошки -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('diagnostic.ai-search.index') }}">AI Поиск</a></li>
            <li class="breadcrumb-item active" aria-current="page">Инструкция по ремонту</li>
        </ol>
    </nav>
    
    <!-- Заголовок -->
    <div class="row mb-4">
        <div class="col">
            <h1 class="h2 mb-2">
                <i class="bi bi-tools text-primary me-2"></i>
                {{ $rule->symptom->name ?? 'Диагностика проблемы' }}
            </h1>
            @if($rule->brand)
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-info">
                        <i class="bi bi-car-front me-1"></i>
                        {{ $rule->brand->name }}
                    </span>
                    @if($rule->model)
                        <span class="badge bg-secondary">{{ $rule->model->name }}</span>
                    @endif
                    <span class="badge bg-warning">
                        Сложность: {{ $rule->complexity_level }}/10
                        <span class="complexity-indicator">
                            <span class="complexity-fill"></span>
                        </span>
                    </span>
                </div>
            @endif
        </div>
    </div>
    
    <div class="repair-guide-container">
        <!-- Основная инструкция -->
        <div>
            <!-- Диагностические шаги -->
            @if(!empty($repair_guide[0]['steps']))
                <div class="guide-section">
                    <h3 class="h5 mb-3">
                        <i class="bi bi-search text-primary me-2"></i>
                        Диагностические шаги
                    </h3>
                    <div class="list-group">
                        @foreach($repair_guide[0]['steps'] as $index => $step)
                            <div class="list-group-item border-0 py-2 px-0">
                                <div class="d-flex">
                                    <div class="step-number">{{ $index + 1 }}</div>
                                    <div class="flex-grow-1">
                                        {{ $step }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            
            <!-- Возможные причины -->
            @if(!empty($repair_guide[1]['steps']))
                <div class="guide-section">
                    <h3 class="h5 mb-3">
                        <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                        Возможные причины
                    </h3>
                    <div class="row">
                        @foreach($repair_guide[1]['steps'] as $cause)
                            <div class="col-md-6 mb-2">
                                <div class="card border-warning border-start-3">
                                    <div class="card-body py-2">
                                        <i class="bi bi-dot text-warning me-1"></i>
                                        {{ $cause }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            
            <!-- Инструкции по ремонту -->
            @if(!empty($repair_guide[3]['documents']))
                <div class="guide-section">
                    <h3 class="h5 mb-3">
                        <i class="bi bi-file-earmark-text text-info me-2"></i>
                        Инструкции по ремонту
                    </h3>
                    <div class="documents-list">
                        @foreach($repair_guide[3]['documents'] as $document)
                            <a href="{{ route('documents.show', $document->id) }}" 
                               target="_blank"
                               class="document-card text-decoration-none">
                                <div class="document-icon">
                                    <i class="bi bi-file-earmark-pdf fs-2 text-danger"></i>
                                </div>
                                <div class="document-info">
                                    <div class="document-title">{{ $document->title }}</div>
                                    <div class="document-meta">
                                        <span><i class="bi bi-file-earmark"></i> {{ $document->file_type }}</span>
                                        @if($document->total_pages)
                                            <span><i class="bi bi-file-text"></i> {{ $document->total_pages }} стр.</span>
                                        @endif
                                        @if($document->detected_system)
                                            <span><i class="bi bi-gear"></i> {{ $document->detected_system }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <i class="bi bi-arrow-right"></i>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
            
            <!-- Проверка и завершение -->
            @if(!empty($repair_guide[4]['steps']))
                <div class="guide-section">
                    <h3 class="h5 mb-3">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Проверка и завершение работ
                    </h3>
                    <div class="list-group">
                        @foreach($repair_guide[4]['steps'] as $index => $step)
                            <div class="list-group-item border-0 py-2 px-0">
                                <div class="d-flex align-items-center">
                                    <div class="bg-success text-white rounded-circle p-2 me-3">
                                        <i class="bi bi-check-lg"></i>
                                    </div>
                                    <div>{{ $step }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        
        <!-- Боковая панель с запчастями -->
        <div>
            <!-- Список запчастей -->
            @if(!empty($repair_guide[2]['parts']) && $repair_guide[2]['parts']->count() > 0)
                <div class="guide-section parts-list">
                    <h3 class="h5 mb-3">
                        <i class="bi bi-tools text-success me-2"></i>
                        Рекомендуемые запчасти
                    </h3>
                    
                    <div class="mb-3">
                        <small class="text-muted">Найдено {{ $repair_guide[2]['parts']->count() }} запчастей</small>
                    </div>
                    
                    @foreach($repair_guide[2]['parts'] as $part)
                        <div class="part-item">
                            @if($part->image_url)
                                <img src="{{ $part->image_url }}" 
                                     alt="{{ $part->name }}"
                                     class="part-image"
                                     onerror="this.src='/images/placeholder-part.png'">
                            @else
                                <div class="part-image d-flex align-items-center justify-content-center bg-light">
                                    <i class="bi bi-tools text-muted"></i>
                                </div>
                            @endif
                            
                            <div class="part-info">
                                <div class="part-name">{{ $part->name }}</div>
                                <div class="part-sku">{{ $part->sku }}</div>
                                @if($part->catalog_brand)
                                    <small class="text-muted">{{ $part->catalog_brand }}</small>
                                @endif
                            </div>
                            
                            <div class="part-price">
                                {{ number_format($part->price, 2, '.', ' ') }} ₽
                            </div>
                        </div>
                    @endforeach
                    
                    <!-- Сводка по стоимости -->
                    @php
                        $totalCost = $repair_guide[2]['parts']->sum('price');
                    @endphp
                    
                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Общая стоимость запчастей:</strong>
                            </div>
                            <div class="h4 mb-0 text-success">
                                {{ number_format($totalCost, 2, '.', ' ') }} ₽
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button class="btn btn-success w-100" onclick="addAllToCart()">
                                <i class="bi bi-cart-plus me-2"></i>
                                Добавить все в корзину
                            </button>
                        </div>
                    </div>
                </div>
            @endif
            
            <!-- Необходимые инструменты -->
            <div class="guide-section">
                <h3 class="h5 mb-3">
                    <i class="bi bi-wrench text-primary me-2"></i>
                    Необходимые инструменты
                </h3>
                <div class="tools-needed">
                    <ul class="mb-0">
                        <li>Набор гаечных ключей</li>
                        <li>Набор отверток</li>
                        <li>Мультиметр</li>
                        <li>Сканер ошибок OBD2</li>
                        <li>Защитные перчатки</li>
                    </ul>
                </div>
            </div>
            
            <!-- Оценка времени -->
            <div class="guide-section">
                <h3 class="h5 mb-3">
                    <i class="bi bi-clock text-info me-2"></i>
                    Оценка времени ремонта
                </h3>
                <div class="text-center py-3">
                    <div class="display-4 text-primary">{{ $rule->estimated_time ?? 60 }}</div>
                    <div class="text-muted">минут</div>
                    <small class="text-muted">* Время указано для опытного мастера</small>
                </div>
            </div>
            
            <!-- Консультация -->
            <div class="guide-section">
                <h3 class="h5 mb-3">
                    <i class="bi bi-chat-dots text-warning me-2"></i>
                    Нужна помощь?
                </h3>
                <div class="text-center">
                    <div class="h4 text-warning mb-3">
                        {{ number_format($rule->base_consultation_price ?? 3000, 0, '.', ' ') }} ₽
                    </div>
                    <p class="text-muted small mb-3">
                        Консультация опытного специалиста по вашему случаю
                    </p>
                    <button class="btn btn-warning w-100" onclick="orderConsultation({{ $rule->id }})">
                        <i class="bi bi-chat-dots me-2"></i>
                        Заказать консультацию
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function addAllToCart() {
    const parts = @json($repair_guide[2]['parts']->pluck('id'));
    
    parts.forEach(partId => {
        // Здесь реализация добавления в корзину
        console.log('Adding part to cart:', partId);
    });
    
    alert('Все запчасти добавлены в корзину');
}

function orderConsultation(ruleId) {
    window.location.href = '/diagnostic/consultation/order?rule_id=' + ruleId;
}
</script>
@endpush