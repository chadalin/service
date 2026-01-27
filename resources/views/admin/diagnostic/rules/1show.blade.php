@extends('layouts.app')

@section('title', $title)

@push('styles')
<style>
    .rule-card {
        border-left: 5px solid #2196F3;
        transition: all 0.3s ease;
    }
    
    .rule-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(33, 150, 243, 0.1);
    }
    
    .badge-complexity {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-weight: 600;
    }
    
    .complexity-low { background: #4CAF50; color: white; }
    .complexity-medium { background: #FF9800; color: white; }
    .complexity-high { background: #F44336; color: white; }
    
    .list-steps {
        counter-reset: step-counter;
        list-style: none;
        padding-left: 0;
    }
    
    .list-steps li {
        position: relative;
        padding: 0.75rem 1rem 0.75rem 3rem;
        margin-bottom: 0.5rem;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 3px solid #2196F3;
    }
    
    .list-steps li:before {
        counter-increment: step-counter;
        content: counter(step-counter);
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        width: 24px;
        height: 24px;
        background: #2196F3;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.875rem;
    }
    
    .cause-badge {
        display: inline-block;
        padding: 0.375rem 0.75rem;
        margin: 0.25rem;
        background: #E3F2FD;
        color: #1565C0;
        border-radius: 20px;
        font-size: 0.875rem;
        transition: all 0.2s;
    }
    
    .cause-badge:hover {
        background: #BBDEFB;
        transform: scale(1.05);
    }
    
    /* Стили для загрузки файлов */
    .upload-area {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 2rem;
        text-align: center;
        transition: all 0.3s;
        cursor: pointer;
        background: #f8f9fa;
        position: relative;
        min-height: 150px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    
    .upload-area:hover {
        border-color: #2196F3;
        background: #e3f2fd;
    }
    
    .upload-area.dragover {
        border-color: #4CAF50;
        background: #e8f5e9;
    }
    
    .file-preview {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 15px;
    }
    
    .preview-item {
        position: relative;
        width: 100px;
        height: 100px;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #dee2e6;
    }
    
    .preview-item img, .preview-item video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .preview-item .remove-btn {
        position: absolute;
        top: 5px;
        right: 5px;
        width: 20px;
        height: 20px;
        background: #f44336;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 12px;
        border: none;
    }
    
    .file-icon {
        font-size: 3rem;
        color: #6c757d;
        margin-bottom: 1rem;
    }
    
    .file-info {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0,0,0,0.7);
        color: white;
        padding: 5px;
        font-size: 11px;
        text-overflow: ellipsis;
        overflow: hidden;
        white-space: nowrap;
    }
    
    .required-file-types {
        font-size: 0.85rem;
        color: #6c757d;
        margin-top: 10px;
    }
    
    .consultation-form-section {
        border: 1px solid #dee2e6;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        background: #fff;
    }
    
    .consultation-form-section h6 {
        border-bottom: 2px solid #2196F3;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    
    .consultation-price-badge {
        font-size: 1.5rem;
        font-weight: bold;
        color: #28a745;
        text-align: center;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">{{ $title }}</h4>
                            <nav aria-label="breadcrumb" class="mt-2">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Главная</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('admin.diagnostic.rules.index') }}">Правила диагностики</a></li>
                                    <li class="breadcrumb-item active">Просмотр</li>
                                </ol>
                            </nav>
                        </div>
                        <div>
                            <a href="{{ route('admin.diagnostic.rules.edit', $rule->id) }}" 
                               class="btn btn-warning btn-sm">
                                <i class="bi bi-pencil me-1"></i> Редактировать
                            </a>
                            <a href="{{ route('admin.diagnostic.rules.index') }}" 
                               class="btn btn-secondary btn-sm ms-2">
                                <i class="bi bi-arrow-left me-1"></i> Назад
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Основная информация -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <h5 class="text-primary">{{ $rule->symptom->name ?? 'Симптом не указан' }}</h5>
                                @if($rule->symptom->description)
                                    <p class="text-muted">{{ $rule->symptom->description }}</p>
                                @endif
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted mb-1">Марка:</label>
                                    <div class="d-flex align-items-center">
                                        @if($rule->brand)
                                            <span class="badge bg-info">
                                                <i class="bi bi-car-front me-1"></i>
                                                {{ $rule->brand->name }}
                                            </span>
                                        @else
                                            <span class="text-muted">Все марки</span>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted mb-1">Модель:</label>
                                    <div>
                                        @if($rule->model)
                                            <span class="badge bg-secondary">
                                                {{ $rule->model->name }}
                                            </span>
                                        @else
                                            <span class="text-muted">Все модели</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6 mb-3">
                                            <div class="text-muted small mb-1">Сложность</div>
                                            @php
                                                $complexityClass = 'complexity-medium';
                                                if ($rule->complexity_level <= 3) $complexityClass = 'complexity-low';
                                                elseif ($rule->complexity_level >= 7) $complexityClass = 'complexity-high';
                                            @endphp
                                            <div class="badge-complexity {{ $complexityClass }}">
                                                {{ $rule->complexity_level }}/10
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="text-muted small mb-1">Время</div>
                                            <div class="h5 mb-0">{{ $rule->estimated_time }} мин.</div>
                                        </div>
                                        <div class="col-12">
                                            <div class="text-muted small mb-1">Цена консультации</div>
                                            <div class="consultation-price-badge">{{ number_format($rule->base_consultation_price, 0, '', ' ') }} ₽</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Диагностические шаги -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="bi bi-clipboard-check text-primary me-2"></i>
                                Шаги диагностики
                            </h6>
                        </div>
                        <div class="card-body">
                            @if(!empty($rule->diagnostic_steps) && count($rule->diagnostic_steps) > 0)
                                <ol class="list-steps">
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
                    
                    <div class="row">
                        <!-- Возможные причины -->
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                                        Возможные причины
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @if(!empty($rule->possible_causes) && count($rule->possible_causes) > 0)
                                        <div class="d-flex flex-wrap">
                                            @foreach($rule->possible_causes as $cause)
                                                <span class="cause-badge">{{ $cause }}</span>
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
                        </div>
                        
                        <!-- Требуемые данные (Переделанный блок) -->
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="bi bi-clipboard-data text-success me-2"></i>
                                        Требуемые данные для консультации
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="consultation-form-section">
                                        <!-- Основные требуемые данные из правила -->
                                        @if(!empty($rule->required_data) && count($rule->required_data) > 0)
                                            <h6>Необходимо предоставить:</h6>
                                            <ul class="list-group list-group-flush mb-3">
                                                @foreach($rule->required_data as $data)
                                                    <li class="list-group-item d-flex align-items-center">
                                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                                        {{ $data }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                        
                                        <!-- Форма для заказа консультации -->
                                        <form id="consultationOrderForm" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="rule_id" value="{{ $rule->id }}">
                                            <input type="hidden" name="consultation_type" value="expert">
                                            
                                            <!-- 1. Описание своего симптома -->
                                            <div class="mb-4">
                                                <label for="symptom_description" class="form-label fw-bold">
                                                    <i class="bi bi-chat-left-text me-1"></i> Опишите ваш симптом подробнее
                                                </label>
                                                <textarea class="form-control" 
                                                          id="symptom_description" 
                                                          name="symptom_description" 
                                                          rows="3"
                                                          placeholder="Например: 
• Когда началась проблема?
• При каких условиях проявляется?
• Какие симптомы сопровождают?
• Что уже пробовали сделать?"
                                                          required></textarea>
                                                <div class="form-text">
                                                    Чем подробнее описание, тем точнее будет консультация
                                                </div>
                                            </div>
                                            
                                            <!-- 2. Загрузка протоколов диагностики -->
                                            <div class="mb-4">
                                                <label class="form-label fw-bold">
                                                    <i class="bi bi-file-earmark-text me-1"></i> Приложите протокол диагностики
                                                </label>
                                                <div class="required-file-types">
                                                    <small>Поддерживаемые форматы: PDF, DOC, DOCX, JPG, PNG, TXT</small>
                                                </div>
                                                
                                                <div class="upload-area" 
                                                     id="protocolUploadArea"
                                                     onclick="document.getElementById('protocol_files').click()">
                                                    <div class="file-icon">
                                                        <i class="bi bi-cloud-arrow-up"></i>
                                                    </div>
                                                    <p class="mb-1">Перетащите сюда файлы протоколов или нажмите для выбора</p>
                                                    <small class="text-muted">Можно загрузить несколько файлов</small>
                                                    <input type="file" 
                                                           id="protocol_files" 
                                                           name="protocol_files[]" 
                                                           multiple 
                                                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.txt"
                                                           class="d-none"
                                                           onchange="handleFileUpload(this, 'protocol-preview')">
                                                </div>
                                                
                                                <!-- Предпросмотр протоколов -->
                                                <div class="file-preview" id="protocol-preview"></div>
                                            </div>
                                            
                                            <!-- 3. Загрузка фото симптома -->
                                            <div class="mb-4">
                                                <label class="form-label fw-bold">
                                                    <i class="bi bi-camera me-1"></i> Фото симптома (если возможно)
                                                </label>
                                                <div class="required-file-types">
                                                    <small>Поддерживаемые форматы: JPG, JPEG, PNG (макс. 5MB)</small>
                                                </div>
                                                
                                                <div class="upload-area" 
                                                     id="photoUploadArea"
                                                     onclick="document.getElementById('symptom_photos').click()">
                                                    <div class="file-icon">
                                                        <i class="bi bi-image"></i>
                                                    </div>
                                                    <p class="mb-1">Загрузите фото симптома или проблемной зоны</p>
                                                    <small class="text-muted">Например: индикаторы на приборной панели, подозрительные детали и т.д.</small>
                                                    <input type="file" 
                                                           id="symptom_photos" 
                                                           name="symptom_photos[]" 
                                                           multiple 
                                                           accept="image/*"
                                                           class="d-none"
                                                           onchange="handleFileUpload(this, 'photo-preview')">
                                                </div>
                                                
                                                <!-- Предпросмотр фото -->
                                                <div class="file-preview" id="photo-preview"></div>
                                            </div>
                                            
                                            <!-- 4. Загрузка видео -->
                                            <div class="mb-4">
                                                <label class="form-label fw-bold">
                                                    <i class="bi bi-camera-video me-1"></i> Видео симптома (опционально)
                                                </label>
                                                <div class="required-file-types">
                                                    <small>Поддерживаемые форматы: MP4, AVI, MOV, MKV (макс. 50MB)</small>
                                                </div>
                                                
                                                <div class="upload-area" 
                                                     id="videoUploadArea"
                                                     onclick="document.getElementById('symptom_videos').click()">
                                                    <div class="file-icon">
                                                        <i class="bi bi-file-play"></i>
                                                    </div>
                                                    <p class="mb-1">Загрузите видео проявления симптома</p>
                                                    <small class="text-muted">Например: звук двигателя, поведение автомобиля</small>
                                                    <input type="file" 
                                                           id="symptom_videos" 
                                                           name="symptom_videos[]" 
                                                           multiple 
                                                           accept="video/*"
                                                           class="d-none"
                                                           onchange="handleFileUpload(this, 'video-preview')">
                                                </div>
                                                
                                                <!-- Предпросмотр видео -->
                                                <div class="file-preview" id="video-preview"></div>
                                            </div>
                                            
                                            <!-- 5. Дополнительная информация -->
                                            <div class="mb-4">
                                                <label for="additional_info" class="form-label fw-bold">
                                                    <i class="bi bi-info-circle me-1"></i> Дополнительная информация
                                                </label>
                                                <textarea class="form-control" 
                                                          id="additional_info" 
                                                          name="additional_info" 
                                                          rows="2"
                                                          placeholder="Любая другая важная информация о проблеме..."></textarea>
                                            </div>
                                            
                                            <!-- 6. Кнопка покупки -->
                                            <div class="text-center mt-4 pt-3 border-top">
                                                <div class="consultation-price-badge mb-3">
                                                    Стоимость консультации: {{ number_format($rule->base_consultation_price, 0, '', ' ') }} ₽
                                                </div>
                                                
                                                <button type="submit" 
                                                        class="btn btn-success btn-lg w-100"
                                                        id="buyConsultationBtn">
                                                    <i class="bi bi-credit-card me-2"></i>
                                                    <span id="btnText">Купить консультацию за {{ number_format($rule->base_consultation_price, 0, '', ' ') }} ₽</span>
                                                    <span class="spinner-border spinner-border-sm ms-2 d-none" id="loadingSpinner"></span>
                                                </button>
                                                
                                                <div class="mt-2">
                                                    <small class="text-muted">
                                                        <i class="bi bi-shield-check me-1"></i>
                                                        Консультация проводится экспертом. Гарантия точности диагноза.
                                                    </small>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Статистика и метаданные -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="bi bi-info-circle text-info me-2"></i>
                                Дополнительная информация
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <div class="text-muted small mb-1">Статус</div>
                                    <span class="badge {{ $rule->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $rule->is_active ? 'Активно' : 'Неактивно' }}
                                    </span>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="text-muted small mb-1">Порядок</div>
                                    <div>{{ $rule->order }}</div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="text-muted small mb-1">Создано</div>
                                    <div>{{ $rule->created_at->format('d.m.Y H:i') }}</div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="text-muted small mb-1">Обновлено</div>
                                    <div>{{ $rule->updated_at->format('d.m.Y H:i') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Кнопки действий -->
                    <div class="d-flex justify-content-between mt-4">
                        <div>
                            <a href="{{ route('admin.diagnostic.rules.index') }}" 
                               class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i> К списку правил
                            </a>
                        </div>
                        <div>
                            @if($rule->is_active)
                                @if(isset($rule->cases) && $rule->cases->count() > 0)
                                    <a href="{{ route('consultation.order.form', ['case' => $rule->cases->first()->id]) }}" 
                                       class="btn btn-success">
                                        <i class="bi bi-chat-dots me-1"></i> Заказать консультацию
                                    </a>
                                @else
                                    @php
                                        $params = [
                                            'rule_id' => $rule->id,
                                            'type' => 'expert',
                                        ];
                                        
                                        if ($rule->brand_id) {
                                            $params['brand_id'] = $rule->brand_id;
                                        }
                                        
                                        if ($rule->model_id) {
                                            $params['model_id'] = $rule->model_id;
                                        }
                                        
                                        if ($rule->symptoms && $rule->symptoms->count() > 0) {
                                            $params['symptoms'] = $rule->symptoms->pluck('id')->implode(',');
                                        }
                                        
                                        $queryString = http_build_query($params);
                                    @endphp
                                    
                                    <a href="{{ url('/diagnostic/consultation/order') }}?{{ $queryString }}" 
                                       class="btn btn-success">
                                        <i class="bi bi-chat-dots me-1"></i> Заказать консультацию
                                    </a>
                                @endif
                            @endif
                            
                            <a href="{{ route('admin.diagnostic.rules.edit', $rule->id) }}" 
                               class="btn btn-warning ms-2">
                                <i class="bi bi-pencil me-1"></i> Редактировать
                            </a>
                        </div>
                    </div>
                </div>
            </div>
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
});

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
    
    // Ограничиваем количество файлов
    if (files.length > 10) {
        alert('Максимально можно загрузить 10 файлов');
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
    
    if (fileType === 'image') {
        // Для изображений
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = previewItem.querySelector('img');
            if (img) img.src = e.target.result;
        };
        reader.readAsDataURL(file);
        content = `<img src="" alt="Preview">`;
    } else if (fileType === 'video') {
        // Для видео
        const reader = new FileReader();
        reader.onload = function(e) {
            const video = previewItem.querySelector('video');
            if (video) {
                video.src = e.target.result;
                video.load();
            }
        };
        reader.readAsDataURL(file);
        content = `<video controls><source src="" type="${file.type}"></video>`;
    } else {
        // Для документов и других файлов
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
            ${truncateFileName(file.name, 15)}<br>
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
    
    if (btn) {
        const text = btn.querySelector('#btnText');
        if (text) {
            if (totalFiles > 0) {
                text.textContent = `Купить консультацию (${totalFiles} файлов)`;
            } else {
                text.textContent = `Купить консультацию за {{ number_format($rule->base_consultation_price, 0, '', ' ') }} ₽`;
            }
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
        
        // Валидация
        const description = document.getElementById('symptom_description').value;
        if (!description.trim()) {
            alert('Пожалуйста, опишите ваш симптом');
            document.getElementById('symptom_description').focus();
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
                files.forEach((file, index) => {
                    formData.append(`${type}_files[${index}]`, file);
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
            
            if (result.success) {
                // Перенаправляем на оплату или страницу успеха
                if (result.payment_url) {
                    window.location.href = result.payment_url;
                } else if (result.order_id) {
                    window.location.href = `/consultation/order/${result.order_id}/success`;
                } else {
                    window.location.href = '/consultation/order/success';
                }
            } else {
                throw new Error(result.message || 'Ошибка при оформлении заказа');
            }
            
        } catch (error) {
            console.error('Error:', error);
            alert('Ошибка: ' + error.message);
            
            // Восстанавливаем кнопку
            btn.disabled = false;
            btnText.textContent = 'Купить консультацию за {{ number_format($rule->base_consultation_price, 0, '', ' ') }} ₽';
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
    return name.substr(0, maxLength) + '...' + name.split('.').pop();
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
</script>
@endpush