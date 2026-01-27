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
        <div class="col-lg-7">
            <!-- Шаги диагностики -->
            <div class="info-card mb-4">
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
            <div class="info-card mb-4">
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
        </div>
        
       <!-- Правая колонка: Форма заказа консультации -->
<div class="col-lg-5">
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
                <input type="hidden" name="brand_id" value="{{ $rule->brand_id }}">
                
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
                              placeholder="• Когда началась проблема?&#10;• При каких условиях проявляется?&#10;• Какие симптомы сопровождают?&#10;• Что уже пробовали сделать?"
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
</script>
@endpush