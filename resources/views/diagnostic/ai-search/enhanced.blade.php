@extends('layouts.app')

@section('title', 'AI Диагностический поиск')

@push('styles')
<style>
    /* Основные стили */
    .ai-search-container {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    @media (min-width: 1200px) {
        .ai-search-container {
            grid-template-columns: 350px 1fr;
        }
    }
    
    /* Карточка формы */
    .search-form-card {
        position: sticky;
        top: 1rem;
        height: fit-content;
    }
    
    /* Результаты - компактный вид */
    .results-container {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    /* Основной результат - симптом с правилами */
    .main-result-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-bottom: 1rem;
        overflow: hidden;
    }
    
    .result-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.25rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .result-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .result-meta {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        align-items: center;
    }
    
    .meta-badge {
        background: rgba(255,255,255,0.2);
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
    }
    
    /* Контент результата */
    .result-content {
        padding: 1.5rem;
    }
    
    .result-section {
        margin-bottom: 1.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .result-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    /* Списки */
    .step-list, .cause-list, .data-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .step-list li {
        padding: 0.75rem 0;
        border-bottom: 1px solid #f5f5f5;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .step-list li:last-child {
        border-bottom: none;
    }
    
    .step-number {
        background: #667eea;
        color: white;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        flex-shrink: 0;
    }
    
    .cause-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .cause-tag {
        background: #e3f2fd;
        color: #1565c0;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: all 0.2s;
    }
    
    .cause-tag:hover {
        background: #bbdefb;
        transform: translateY(-2px);
    }
    
    /* Запчасти */
    .parts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }
    
    .part-card {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 1rem;
        transition: all 0.3s ease;
    }
    
    .part-card:hover {
        border-color: #4CAF50;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .part-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.75rem;
    }
    
    .part-sku {
        font-family: monospace;
        background: #f5f5f5;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.85rem;
        color: #666;
    }
    
    .part-price {
        font-weight: bold;
        color: #2e7d32;
        font-size: 1.25rem;
    }
    
    .part-name {
        font-weight: 500;
        margin-bottom: 0.5rem;
        line-height: 1.3;
    }
    
    .part-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1rem;
        padding-top: 0.75rem;
        border-top: 1px solid #f0f0f0;
    }
    
    /* Документы */
    .document-item {
        display: flex;
        align-items: center;
        padding: 1rem;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        margin-bottom: 0.75rem;
        transition: all 0.2s;
        text-decoration: none;
        color: inherit;
    }
    
    .document-item:hover {
        background: #f8f9fa;
        border-color: #4CAF50;
        transform: translateX(4px);
    }
    
    .document-icon {
        font-size: 1.5rem;
        color: #666;
        margin-right: 1rem;
        min-width: 40px;
    }
    
    .document-info {
        flex: 1;
    }
    
    .document-title {
        font-weight: 500;
        margin-bottom: 0.25rem;
    }
    
    .document-meta {
        font-size: 0.85rem;
        color: #666;
    }

    .document-result {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: all 0.2s;
}

.document-result:hover {
    border-color: #4CAF50;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.document-header {
    display: flex;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}

.document-icon {
    font-size: 1.5rem;
    color: #666;
    margin-right: 1rem;
    min-width: 40px;
}

.document-title {
    flex: 1;
}

.document-link {
    font-weight: 500;
    text-decoration: none;
    color: #1565c0;
}

.document-link:hover {
    text-decoration: underline;
    color: #0d47a1;
}

.document-page-title {
    font-size: 0.9rem;
    color: #666;
    margin-top: 0.25rem;
    font-style: italic;
}

.document-meta {
    text-align: right;
    min-width: 200px;
}

.document-excerpt {
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 6px;
    margin-bottom: 0.75rem;
    line-height: 1.5;
}

.document-tags {
    margin-bottom: 0.75rem;
}

.document-preview {
    font-size: 0.9rem;
    line-height: 1.4;
}

.preview-content {
    max-height: 150px;
    overflow-y: auto;
}

mark.bg-warning {
    padding: 0.1rem 0.2rem;
    border-radius: 3px;
}
    
    /* AI Ответ */
    .ai-response-box {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border-left: 4px solid #667eea;
    }
    
    .ai-response-content {
        white-space: pre-line;
        line-height: 1.6;
    }
    
    .ai-response-content strong {
        color: #667eea;
    }
    
    /* Анимации для постепенного появления */
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
    
    .fade-in-up {
        animation: fadeInUp 0.5s ease forwards;
    }
    
    /* Кастомный скролл */
    .custom-scroll {
        max-height: 70vh;
        overflow-y: auto;
        padding-right: 10px;
    }
    
    .custom-scroll::-webkit-scrollbar {
        width: 6px;
    }
    
    .custom-scroll::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    
    .custom-scroll::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
    }
    
    .custom-scroll::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    
    /* Загрузка с точками */
    .typing-indicator {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-left: 8px;
    }
    
    .typing-dot {
        width: 8px;
        height: 8px;
        background: #667eea;
        border-radius: 50%;
        animation: typing 1.4s infinite ease-in-out;
    }
    
    .typing-dot:nth-child(1) { animation-delay: -0.32s; }
    .typing-dot:nth-child(2) { animation-delay: -0.16s; }
    
    @keyframes typing {
        0%, 80%, 100% { transform: translateY(0); }
        40% { transform: translateY(-10px); }
    }

     /* ========== НОВЫЕ СТИЛИ ========== */
    
    /* Блок "Ничего не найдено" */
    .no-results-card {
        border: 2px dashed #ffc107;
        border-radius: 16px;
        background: linear-gradient(135deg, #fff9e6 0%, #ffffff 100%);
        margin-bottom: 2rem;
        animation: pulse-border 2s infinite;
    }
    
    @keyframes pulse-border {
        0% { border-color: #ffc107; }
        50% { border-color: #ff9800; }
        100% { border-color: #ffc107; }
    }
    
    .no-results-header {
        background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
        color: white;
        padding: 1.25rem;
        border-radius: 16px 16px 0 0;
    }
    
    /* Кнопка консультации - улучшенная */
    .btn-consultation-glow {
        background: linear-gradient(45deg, #667eea, #764ba2);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 50px;
        font-weight: 600;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .btn-consultation-glow:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        color: white;
    }
    
    .btn-consultation-glow::after {
        content: '';
        position: absolute;
        top: -50%;
        left: -60%;
        width: 200%;
        height: 200%;
        background: rgba(255, 255, 255, 0.1);
        transform: rotate(45deg);
        transition: all 0.5s;
    }
    
    .btn-consultation-glow:hover::after {
        left: 100%;
    }
    
    .btn-consultation-large {
        font-size: 1.2rem;
        padding: 1rem 2rem;
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    /* Форма создания случая */
    .case-form-section {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 1.5rem;
        margin-top: 1rem;
        border-left: 4px solid #667eea;
        transition: all 0.3s;
    }
    
    .case-form-section:hover {
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        background: #ffffff;
    }
    
    .form-label-required::after {
        content: '*';
        color: #dc3545;
        margin-left: 4px;
        font-weight: bold;
    }
    
    /* Превью файлов */
    .file-preview-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
    }
    
    .file-preview-item {
        position: relative;
        width: 100px;
        height: 100px;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid #dee2e6;
    }
    
    .file-preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .file-preview-remove {
        position: absolute;
        top: 2px;
        right: 2px;
        background: rgba(220, 53, 69, 0.9);
        color: white;
        border: none;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.2s;
    }
    
    .file-preview-remove:hover {
        background: #dc3545;
        transform: scale(1.1);
    }
    
    /* Прогресс-бар */
    .case-creation-progress {
        height: 4px;
        width: 100%;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
        margin: 10px 0;
    }
    
    .case-creation-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #667eea, #764ba2);
        width: 0%;
        transition: width 0.3s ease;
        border-radius: 4px;
    }
    
    /* Индикатор VIN */
    .vin-valid {
        border-color: #28a745 !important;
    }
    
    .vin-invalid {
        border-color: #dc3545 !important;
    }
    
    /* Анимация появления */
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .slide-in-right {
        animation: slideInRight 0.5s ease forwards;
    }
</style>
@endpush

@section('content')
<div class="ai-search-container">
    <!-- Левая колонка - форма поиска -->
    <div>
        <div class="card search-form-card shadow">
            <div class="card-header bg-primary text-white">
                <div class="d-flex align-items-center">
                    <i class="bi bi-robot fs-4 me-2"></i>
                    <h5 class="mb-0">🤖 ввAI Диагностика</h5>
                </div>
            </div>
            
            <div class="card-body">
                <form id="aiSearchForm">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Опишите проблему</label>
                        <textarea class="form-control" 
                                  id="query" 
                                  name="query" 
                                  rows="4"
                                  placeholder="Пример: Не заводится двигатель, щелкает стартер"
                                  required></textarea>
                    </div>
                    
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Марка авто</label>
                            <select class="form-select" id="brand_id" name="brand_id">
                                <option value="">Все марки</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}">
                                        {{ $brand->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Модель</label>
                            <select class="form-select" id="model_id" name="model_id" disabled>
                                <option value="">Все модели</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Тип поиска</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="search_type" 
                                   id="search_basic" value="basic" checked>
                            <label class="form-check-label" for="search_basic">
                                Базовый (быстрый)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="search_type" 
                                   id="search_advanced" value="advanced">
                            <label class="form-check-label" for="search_advanced">
                                Расширенный (точный)
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100" id="searchBtn">
                        <i class="bi bi-search me-2"></i>
                        <span>Начать диагностику</span>
                        <span class="spinner-border spinner-border-sm ms-2 d-none" id="searchSpinner"></span>
                    </button>
                </form>
                
                <div class="mt-3 text-center">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        База содержит {{ $stats['symptoms_count'] }} симптомов
                        и {{ $stats['rules_count'] }} правил диагностики
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Правая колонка - результаты -->
    <div>
        <div class="card shadow">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-file-earmark-text me-2"></i>Результаты диагностики
                </h5>
                <span class="badge bg-secondary" id="resultsCounter">Ожидание запроса</span>
            </div>
            
            <div class="card-body p-0">
                <div class="custom-scroll p-3" id="resultsContainer">
                    <!-- Начальное состояние -->
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="bi bi-robot display-1 text-primary"></i>
                        </div>
                        <h3 class="text-primary mb-3">AI-диагностика автомобиля</h3>
                        <p class="text-muted mb-4">
                            Опишите проблему, и AI найдет соответствующие симптомы,<br>
                            правила диагностики и рекомендации по ремонту
                        </p>
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="alert alert-info">
                                    <i class="bi bi-lightbulb me-2"></i>
                                    <strong>Примеры запросов:</strong><br>
                                    • Не заводится двигатель<br>
                                    • Горит Check Engine<br>
                                    • Стук в двигателе на холодную<br>
                                    • Не работает кондиционер
                                </div>
                            </div>
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
// ============================================
// ГЛОБАЛЬНЫЕ ПЕРЕМЕННЫЕ
// ============================================
let allModels = @json($models ?? []);
let currentSearchData = null;
let isLoading = false;
let currentResults = [];
let currentUser = @json($user ?? null);
let currentUserEmail = currentUser?.email || '';
let currentUserPhone = currentUser?.phone || '';
let currentUserName = currentUser?.name || currentUser?.email?.split('@')[0] || '';

// ============================================
// ИНИЦИАЛИЗАЦИЯ
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('AI Search page loaded');
    
    // Инициализация
    initBrandModelSelect();
    initEventListeners();
    initFileUploads();
    initVinValidation();
    
    // Делаем функцию toggleCaseForm глобально доступной
    window.toggleCaseForm = toggleCaseForm;
    window.showConsultationForm = showConsultationForm;
    window.showTelegramSupport = showTelegramSupport;
    window.showWhatsAppSupport = showWhatsAppSupport;
    window.createCaseFromSearch = createCaseFromSearch;
    window.removeFile = removeFile;
    window.togglePreview = togglePreview;
    window.viewDocumentDetails = viewDocumentDetails;
    window.viewRuleDetails = viewRuleDetails;
    window.viewSymptomDetails = viewSymptomDetails;
    window.orderConsultation = orderConsultation;
    window.viewPartDetails = viewPartDetails;
    window.addToCart = addToCart;
});

// ============================================
// ФУНКЦИИ ДЛЯ РАБОТЫ С МАРКАМИ/МОДЕЛЯМИ
// ============================================
function initBrandModelSelect() {
    const brandSelect = document.getElementById('brand_id');
    const modelSelect = document.getElementById('model_id');
    
    if (brandSelect) {
        brandSelect.addEventListener('change', function() {
            const brandId = this.value;
            console.log('Brand selected:', brandId);
            
            if (!brandId) {
                resetModelSelect();
                return;
            }
            
            loadModelsForBrand(brandId);
        });
    }
}

function loadModelsForBrand(brandId) {
    const modelSelect = document.getElementById('model_id');
    const models = allModels[brandId] || [];
    
    if (!Array.isArray(models) || models.length === 0) {
        modelSelect.innerHTML = '<option value="">Нет доступных моделей</option>';
        modelSelect.disabled = true;
        return;
    }
    
    let options = '<option value="">Все модели</option>';
    
    models.forEach(model => {
        const displayName = model.name || model.name_cyrillic || `Модель ${model.id}`;
        let yearInfo = '';
        
        if (model.year_from) {
            if (model.year_to && model.year_to !== model.year_from) {
                yearInfo = ` (${model.year_from}-${model.year_to})`;
            } else {
                yearInfo = ` (${model.year_from})`;
            }
        }
        
        options += `<option value="${model.id}">${displayName}${yearInfo}</option>`;
    });
    
    modelSelect.innerHTML = options;
    modelSelect.disabled = false;
    
    // Анимация
    modelSelect.style.opacity = '0';
    setTimeout(() => {
        modelSelect.style.transition = 'opacity 0.3s';
        modelSelect.style.opacity = '1';
    }, 10);
}

function resetModelSelect() {
    const modelSelect = document.getElementById('model_id');
    modelSelect.innerHTML = '<option value="">Сначала выберите марку</option>';
    modelSelect.disabled = true;
}

// ============================================
// ФУНКЦИИ ДЛЯ РАБОТЫ С ФАЙЛАМИ
// ============================================
function initFileUploads() {
    const photoInput = document.getElementById('symptom_photos');
    const videoInput = document.getElementById('symptom_videos');
    
    if (photoInput) {
        // Удаляем старый обработчик и добавляем новый
        photoInput.removeEventListener('change', handlePhotoPreview);
        photoInput.addEventListener('change', handlePhotoPreview);
    }
    
    if (videoInput) {
        // Удаляем старый обработчик и добавляем новый
        videoInput.removeEventListener('change', handleVideoPreview);
        videoInput.addEventListener('change', handleVideoPreview);
    }
}

function handlePhotoPreview(e) {
    previewFiles(e.target, 'photo-preview-container');
}

function handleVideoPreview(e) {
    previewFiles(e.target, 'video-preview-container');
}

function previewFiles(input, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    container.innerHTML = '';
    container.style.display = 'flex';
    
    if (input.files && input.files.length > 0) {
        Array.from(input.files).forEach((file, index) => {
            const previewItem = document.createElement('div');
            previewItem.className = 'file-preview-item';
            
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewItem.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <button type="button" class="file-preview-remove" onclick="removeFile(this, '${input.id}', ${index})">
                            <i class="bi bi-x"></i>
                        </button>
                    `;
                };
                reader.readAsDataURL(file);
            } else {
                previewItem.innerHTML = `
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; background: #f8f9fa; padding: 10px;">
                        <i class="bi bi-file-play" style="font-size: 2rem; color: #6c757d;"></i>
                        <small style="font-size: 0.7rem;">${file.name.substring(0, 10)}...</small>
                    </div>
                    <button type="button" class="file-preview-remove" onclick="removeFile(this, '${input.id}', ${index})">
                        <i class="bi bi-x"></i>
                    </button>
                `;
            }
            
            container.appendChild(previewItem);
        });
    } else {
        container.style.display = 'none';
    }
}

function removeFile(button, inputId, fileIndex) {
    const input = document.getElementById(inputId);
    if (input && input.files) {
        const dt = new DataTransfer();
        const files = Array.from(input.files);
        files.splice(fileIndex, 1);
        files.forEach(file => dt.items.add(file));
        input.files = dt.files;
        
        // Обновить предпросмотр
        previewFiles(input, inputId === 'symptom_photos' ? 'photo-preview-container' : 'video-preview-container');
    }
}

// ============================================
// ВАЛИДАЦИЯ VIN
// ============================================
function initVinValidation() {
    const vinInput = document.getElementById('vin');
    if (vinInput) {
        vinInput.addEventListener('input', function() {
            const vin = this.value.toUpperCase();
            this.value = vin;
            
            if (vin.length === 17) {
                if (/^[A-HJ-NPR-Z0-9]{17}$/.test(vin)) {
                    this.classList.add('vin-valid');
                    this.classList.remove('vin-invalid');
                } else {
                    this.classList.add('vin-invalid');
                    this.classList.remove('vin-valid');
                }
            } else {
                this.classList.remove('vin-valid', 'vin-invalid');
            }
        });
    }
}

// ============================================
// ИНИЦИАЛИЗАЦИЯ ОБРАБОТЧИКОВ СОБЫТИЙ
// ============================================
function initEventListeners() {
    const searchForm = document.getElementById('aiSearchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            await performEnhancedSearch();
        });
    }
    
    // Горячие клавиши
    document.getElementById('query')?.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('searchBtn').click();
        }
    });
}

// ============================================
// ОСНОВНОЙ ПОИСК
// ============================================
async function performEnhancedSearch() {
    if (isLoading) return;
    
    const form = document.getElementById('aiSearchForm');
    const searchBtn = document.getElementById('searchBtn');
    const searchSpinner = document.getElementById('searchSpinner');
    const queryInput = document.getElementById('query');
    
    const query = queryInput.value.trim();
    if (!query || query.length < 3) {
        showToast('Введите описание проблемы (минимум 3 символа)', 'warning');
        return;
    }
    
    isLoading = true;
    searchBtn.disabled = true;
    searchSpinner.classList.remove('d-none');
    
    showLoadingState();
    
    try {
        const formData = new FormData(form);
        const brandIdValue = formData.get('brand_id');
        const modelIdValue = document.getElementById('model_id').disabled ? null : formData.get('model_id');
        
        const searchData = {
            query: formData.get('query'),
            brand_id: brandIdValue && brandIdValue !== '' ? brandIdValue : null,
            model_id: modelIdValue && modelIdValue !== '' ? parseInt(modelIdValue) : null,
            search_type: formData.get('search_type'),
        };
        
        if (searchData.model_id !== null && isNaN(searchData.model_id)) {
            searchData.model_id = null;
        }
        
        console.log('Sending search data:', searchData);
        currentSearchData = searchData;
        
        const response = await fetch('{{ route("diagnostic.ai.enhanced.search") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(searchData)
        });
        
        if (!response.ok) {
            if (response.status === 422) {
                const errorData = await response.json();
                let errorMessage = 'Ошибка валидации: ';
                if (errorData.errors) {
                    Object.values(errorData.errors).forEach(errors => {
                        errorMessage += errors.join(', ') + ' ';
                    });
                } else {
                    errorMessage += errorData.message || 'Неизвестная ошибка';
                }
                throw new Error(errorMessage.trim());
            } else {
                throw new Error(`HTTP error ${response.status}`);
            }
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Ошибка поиска');
        }
        
        currentResults = data.results || [];
        displayStructuredResults(data);
        
        const totalResults = data.results.length + (data.parts?.length || 0) + (data.documents?.length || 0);
        showToast(`Найдено ${totalResults} результатов`, 'success');
        
    } catch (error) {
        console.error('Search error:', error);
        
        if (error.message.includes('419') || error.message.includes('CSRF')) {
            showErrorState('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            showToast('Ошибка безопасности. Обновите страницу.', 'danger');
        } else if (error.message.includes('Ошибка валидации')) {
            showErrorState(error.message);
            showToast(error.message, 'danger');
        } else {
            showErrorState(error.message || 'Ошибка поиска');
            showToast('Ошибка поиска: ' + error.message, 'danger');
        }
    } finally {
        isLoading = false;
        searchBtn.disabled = false;
        searchSpinner.classList.add('d-none');
    }
}

function showLoadingState() {
    const container = document.getElementById('resultsContainer');
    container.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
            <h4 class="text-primary mb-3">AI анализирует проблему</h4>
            <p class="text-muted">
                <span class="typing-indicator">
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                </span>
                Поиск симптомов и правил диагностики...
            </p>
        </div>
    `;
    
    document.getElementById('resultsCounter').textContent = 'Поиск...';
    document.getElementById('resultsCounter').className = 'badge bg-warning';
}

function showErrorState(errorMessage) {
    const container = document.getElementById('resultsContainer');
    container.innerHTML = `
        <div class="text-center py-5">
            <i class="bi bi-exclamation-triangle display-1 text-danger mb-3"></i>
            <h4 class="text-danger mb-3">Ошибка поиска</h4>
            <p class="text-muted mb-4">${escapeHtml(errorMessage)}</p>
            <button class="btn btn-primary" onclick="performEnhancedSearch()">
                <i class="bi bi-arrow-clockwise me-1"></i>Повторить
            </button>
        </div>
    `;
    
    document.getElementById('resultsCounter').textContent = 'Ошибка';
    document.getElementById('resultsCounter').className = 'badge bg-danger';
}

// ============================================
// ОТОБРАЖЕНИЕ РЕЗУЛЬТАТОВ
// ============================================
function displayStructuredResults(data) {
    console.log('Displaying results:', data);
    
    const container = document.getElementById('resultsContainer');
    const counter = document.getElementById('resultsCounter');
    
    const totalSymptoms = data.results?.length || 0;
    const totalDocs = data.documents?.length || 0;
    const totalParts = data.parts?.length || 0;
    const totalResults = totalSymptoms + totalDocs + totalParts;
    
    counter.textContent = totalResults > 0 ? `Найдено: ${totalResults}` : 'Нет совпадений';
    counter.className = totalResults > 0 ? 'badge bg-success' : 'badge bg-warning';
    
    container.innerHTML = '';
    
    if (totalResults > 0) {
        setTimeout(() => {
            addAIResponse(data.ai_response, container);
            
            setTimeout(() => {
                if (data.results && data.results.length > 0) {
                    addSymptomsResults(data.results, container);
                }
                
                setTimeout(() => {
                    if (data.documents && data.documents.length > 0) {
                        addDocumentsResults(data.documents, container);
                    }
                    
                    setTimeout(() => {
                        if (data.parts && data.parts.length > 0) {
                            addPartsResults(data.parts, container);
                        }
                        
                        setTimeout(() => {
                            addConsultationButton(data, container);
                        }, 300);
                    }, 300);
                }, 300);
            }, 500);
        }, 300);
    } else {
        setTimeout(() => {
            addAIResponse(data.ai_response, container);
            
            setTimeout(() => {
                addNoResultsWithCaseForm(data, container);
            }, 500);
        }, 300);
    }
}

function addAIResponse(response, container) {
    const responseDiv = document.createElement('div');
    responseDiv.className = 'ai-response-box fade-in-up';
    
    const formattedResponse = formatAIResponse(response || '');
    
    responseDiv.innerHTML = `
        <div class="ai-response-content">
            ${formattedResponse}
        </div>
    `;
    
    container.appendChild(responseDiv);
}

function addSymptomsResults(results, container) {
    const topResults = results.slice(0, 5);
    
    topResults.forEach((result, index) => {
        setTimeout(() => {
            const resultDiv = document.createElement('div');
            resultDiv.className = 'main-result-card fade-in-up';
            resultDiv.style.animationDelay = `${index * 0.2}s`;
            
            resultDiv.innerHTML = createSymptomCardHTML(result, index);
            container.appendChild(resultDiv);
            
            setTimeout(() => {
                resultDiv.style.opacity = '1';
            }, 100);
        }, index * 200);
    });
}

function addPartsResults(parts, container) {
    const partsDiv = document.createElement('div');
    partsDiv.className = 'main-result-card fade-in-up';
    partsDiv.style.animationDelay = '0.1s';
    
    let partsHTML = `
        <div class="result-header">
            <div class="result-title">
                <span><i class="bi bi-tools me-2"></i>Рекомендуемые запчасти</span>
                <span class="badge bg-light text-dark">${parts.length} шт.</span>
            </div>
        </div>
        <div class="result-content">
            <div class="parts-grid">
    `;
    
    parts.forEach((part, index) => {
        partsHTML += createPartCardHTML(part, index);
    });
    
    partsHTML += `
            </div>
        </div>
    `;
    
    partsDiv.innerHTML = partsHTML;
    container.appendChild(partsDiv);
}

function addDocumentsResults(docs, container) {
    if (!docs || !Array.isArray(docs) || docs.length === 0) {
        return;
    }
    
    const docsDiv = document.createElement('div');
    docsDiv.className = 'main-result-card fade-in-up';
    docsDiv.style.animationDelay = '0.1s';
    
    let docsHTML = `
        <div class="result-header">
            <div class="result-title">
                <span><i class="bi bi-files me-2"></i>Инструкции по ремонту</span>
                <span class="badge bg-light text-dark">${docs.length} шт.</span>
            </div>
        </div>
        <div class="result-content">
    `;
    
    docs.forEach((doc, index) => {
        try {
            docsHTML += createDocumentCardHTML(doc, index);
        } catch (error) {
            console.error('Error creating document card:', error);
        }
    });
    
    docsHTML += `</div>`;
    docsDiv.innerHTML = docsHTML;
    container.appendChild(docsDiv);
}

// ============================================
// ГЛАВНАЯ ФУНКЦИЯ - ФОРМА КОГДА НИЧЕГО НЕ НАЙДЕНО
// ============================================
function addNoResultsWithCaseForm(data, container) {
    const formDiv = document.createElement('div');
    formDiv.className = 'main-result-card no-results-card fade-in-up slide-in-right';
    
    const brandSelect = document.getElementById('brand_id');
    const modelSelect = document.getElementById('model_id');
    const selectedBrand = brandSelect.options[brandSelect.selectedIndex];
    const selectedModel = modelSelect.options[modelSelect.selectedIndex];
    
    const brandValue = brandSelect.value || '';
    const modelValue = modelSelect.value || '';
    
    const yearOptions = generateYearOptions();
    
    // Создаем HTML с явными ID для элементов
    formDiv.innerHTML = `
        <div class="no-results-header">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-1">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Совпадений не найдено
                    </h4>
                    <p class="mb-0 opacity-75">
                        Мы не нашли готовое решение в нашей базе. Опишите проблему подробнее — 
                        наши эксперты проведут индивидуальную диагностику.
                    </p>
                </div>
                <span class="badge bg-warning text-dark fs-6">
                    Требуется консультация
                </span>
            </div>
        </div>
        
        <div class="result-content">
            <!-- УЛУЧШЕННАЯ КНОПКА КОНСУЛЬТАЦИИ -->
            <div class="text-center mb-4 p-4 bg-light rounded">
                <div class="mb-3">
                    <i class="bi bi-headset display-3 text-primary"></i>
                </div>
                <h5 class="text-primary mb-2">Получите консультацию эксперта прямо сейчас!</h5>
                <p class="text-muted mb-3">
                    Наши специалисты проанализируют вашу проблему и предложат точное решение
                </p>
                <button class="btn btn-consultation-glow btn-consultation-large btn-pulse" 
                        onclick="toggleCaseForm()">
                    <i class="bi bi-chat-dots-fill me-2"></i>
                    ЗАКАЗАТЬ ИНДИВИДУАЛЬНУЮ ДИАГНОСТИКУ
                    <span class="badge bg-light text-dark ms-2">от 3000 ₽</span>
                </button>
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="bi bi-clock-history me-1"></i> Среднее время ответа: 15 минут
                    </small>
                </div>
            </div>
            
            <!-- ФОРМА СОЗДАНИЯ ДИАГНОСТИЧЕСКОГО СЛУЧАЯ -->
            <div id="caseFormContainer" style="display: none;" class="case-form-section">
                <form id="createCaseForm" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-tools fs-4 text-primary me-2"></i>
                        <h5 class="mb-0">Создание диагностического случая</h5>
                        <span class="badge bg-primary ms-2">Новый</span>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label form-label-required">Марка</label>
                            <input type="text" class="form-control" name="brand_id" 
                                   value="${brandValue}" readonly>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Модель</label>
                            <input type="text" class="form-control" name="model_id" 
                                   value="${modelValue}" readonly>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Год выпуска</label>
                            <select class="form-select" name="year">
                                <option value="">Выберите год</option>
                                ${yearOptions}
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Двигатель</label>
                            <input type="text" class="form-control" name="engine_type" 
                                   placeholder="1.6 MPI, 2.0 TDI...">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">VIN номер</label>
                            <input type="text" class="form-control" name="vin" id="vin"
                                   placeholder="17 символов" maxlength="17">
                            <div class="form-text text-muted small">
                                <i class="bi bi-info-circle"></i> Последние 17 символов СТС
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Пробег (км)</label>
                            <input type="number" class="form-control" name="mileage" 
                                   placeholder="0" min="0" max="1000000">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Телефон</label>
                            <input type="tel" class="form-control" name="contact_phone" 
                                   value="${currentUserPhone}" placeholder="+7 (999) 123-45-67">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="contact_email" 
                                   value="${currentUserEmail}" placeholder="email@example.com">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label form-label-required">Описание проблемы</label>
                            <textarea class="form-control" name="description" rows="4" 
                                      placeholder="Подробно опишите, что происходит, когда проявляется проблема, какие звуки, запахи, ошибки...">${escapeHtml(data.query || '')}</textarea>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Дополнительная информация</label>
                            <textarea class="form-control" name="additional_info" rows="2" 
                                      placeholder="Что уже проверяли, что меняли, какие были ремонты..."></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="bi bi-camera me-1"></i> Фото неисправности
                            </label>
                            <input type="file" class="form-control" name="symptom_photos[]" 
                                   id="symptom_photos" multiple accept="image/*">
                            <div id="photo-preview-container" class="file-preview-container" style="display: none;"></div>
                            <div class="form-text">
                                Макс. 10MB, формат: JPG, PNG
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="bi bi-camera-video me-1"></i> Видео неисправности
                            </label>
                            <input type="file" class="form-control" name="symptom_videos[]" 
                                   id="symptom_videos" multiple accept="video/*">
                            <div id="video-preview-container" class="file-preview-container" style="display: none;"></div>
                            <div class="form-text">
                                Макс. 50MB, формат: MP4, MOV
                            </div>
                        </div>
                        
                        <div class="col-12 mt-4">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-consultation-glow btn-lg">
                                    <i class="bi bi-check-circle me-2"></i>
                                    СОЗДАТЬ ДИАГНОСТИЧЕСКИЙ СЛУЧАЙ
                                </button>
                                <button type="button" class="btn btn-outline-secondary" 
                                        onclick="toggleCaseForm()">
                                    <i class="bi bi-x-lg me-1"></i>Отмена
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="mt-4 pt-3 border-top text-center">
                <p class="text-muted mb-2">Другие способы решения:</p>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="tel:+78001234567" class="btn btn-outline-secondary">
                        <i class="bi bi-telephone me-1"></i>Позвонить
                    </a>
                    <button class="btn btn-outline-primary" onclick="showTelegramSupport()">
                        <i class="bi bi-telegram me-1"></i>Telegram
                    </button>
                    <button class="btn btn-outline-success" onclick="showWhatsAppSupport()">
                        <i class="bi bi-whatsapp me-1"></i>WhatsApp
                    </button>
                </div>
            </div>
        </div>
    `;
    
    container.appendChild(formDiv);
    
    // Инициализируем обработчики для новой формы
    setTimeout(() => {
        initFileUploads();
        initVinValidation();
        
        const caseForm = document.getElementById('createCaseForm');
        if (caseForm) {
            caseForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                await createCaseFromSearch();
            });
        }
    }, 100);
}

// ============================================
// ФУНКЦИЯ ПЕРЕКЛЮЧЕНИЯ ФОРМЫ - ИСПРАВЛЕННАЯ!
// ============================================
function toggleCaseForm() {
    console.log('toggleCaseForm called');
    const container = document.getElementById('caseFormContainer');
    if (container) {
        if (container.style.display === 'none' || container.style.display === '') {
            container.style.display = 'block';
            console.log('Form shown');
            setTimeout(() => {
                container.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 100);
        } else {
            container.style.display = 'none';
            console.log('Form hidden');
        }
    } else {
        console.error('caseFormContainer not found!');
    }
}

// ============================================
// ФУНКЦИЯ ДЛЯ КНОПКИ КОНСУЛЬТАЦИИ
// ============================================
function addConsultationButton(data, container) {
    const consultationDiv = document.createElement('div');
    consultationDiv.className = 'main-result-card fade-in-up slide-in-right';
    consultationDiv.style.marginTop = '1.5rem';
    consultationDiv.style.border = '2px solid #667eea';
    consultationDiv.style.background = 'linear-gradient(135deg, #f5f7ff 0%, #ffffff 100%)';
    
    consultationDiv.innerHTML = `
        <div class="result-content text-center p-4">
            <div class="d-flex align-items-center justify-content-center mb-3">
                <div class="bg-primary rounded-circle p-3 me-3" style="width: 70px; height: 70px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-headset text-white fs-1"></i>
                </div>
                <div class="text-start">
                    <h4 class="mb-1 text-primary">Нужна помощь эксперта?</h4>
                    <p class="text-muted mb-0">Получите консультацию профессионального диагноста</p>
                </div>
            </div>
            
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="bg-light p-3 rounded">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <span class="ms-2">Разбор вашей ситуации</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bg-light p-3 rounded">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <span class="ms-2">Точный план действий</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bg-light p-3 rounded">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <span class="ms-2">Смета на ремонт</span>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-center gap-3">
                <button class="btn btn-consultation-glow btn-lg btn-pulse" onclick="showConsultationForm()">
                    <i class="bi bi-chat-dots-fill me-2"></i>
                    ЗАКАЗАТЬ КОНСУЛЬТАЦИЮ
                    <span class="badge bg-light text-dark ms-2">от 3000 ₽</span>
                </button>
                <button class="btn btn-outline-secondary btn-lg" onclick="toggleCaseForm()">
                    <i class="bi bi-file-earmark-plus me-1"></i>
                    Детальный случай
                </button>
            </div>
        </div>
    `;
    
    container.appendChild(consultationDiv);
}

// ============================================
// ГЕНЕРАЦИЯ ОПЦИЙ ДЛЯ ГОДА
// ============================================
function generateYearOptions() {
    const currentYear = new Date().getFullYear();
    let options = '';
    for (let year = currentYear; year >= 1990; year--) {
        options += `<option value="${year}">${year}</option>`;
    }
    return options;
}

// ============================================
// ПОКАЗАТЬ ФОРМУ КОНСУЛЬТАЦИИ
// ============================================
function showConsultationForm() {
    const brandSelect = document.getElementById('brand_id');
    const selectedBrand = brandSelect.options[brandSelect.selectedIndex];
    
    const consultationData = {
        brand_id: brandSelect.value,
        brand_name: selectedBrand ? selectedBrand.text : 'Не выбрана',
        model_id: document.getElementById('model_id').value,
        description: document.getElementById('query').value
    };
    
    localStorage.setItem('consultation_data', JSON.stringify(consultationData));
    window.location.href = '/diagnostic/consultation/order?from=search';
}

// ============================================
// СОЗДАНИЕ ДИАГНОСТИЧЕСКОГО СЛУЧАЯ
// ============================================
async function createCaseFromSearch() {
    if (isLoading) return;
    
    const form = document.getElementById('createCaseForm');
    const formData = new FormData(form);
    
    const query = document.getElementById('query').value;
    formData.append('query', query);
    
    isLoading = true;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Создание...';
    submitBtn.disabled = true;
    
    showCaseCreationProgress();
    
    try {
        const response = await fetch('{{ route("diagnostic.ai.create-case") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showCaseCreationSuccess(data);
            
            const caseCreatedDiv = document.createElement('div');
            caseCreatedDiv.className = 'alert alert-success mt-3 slide-in-right';
            caseCreatedDiv.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="bi bi-check-circle-fill fs-3"></i>
                    </div>
                    <div>
                        <h5 class="alert-heading mb-1">✅ Диагностический случай #${data.case_id} создан!</h5>
                        <p class="mb-1">${data.message}</p>
                        <div class="mt-2">
                            <strong>Автомобиль:</strong> ${data.case_data?.brand || 'Не указан'} ${data.case_data?.model || ''}<br>
                            <strong>Дата:</strong> ${data.case_data?.created_at || new Date().toLocaleString()}
                        </div>
                        <hr class="my-2">
                        <div class="d-flex gap-2">
                            <a href="${data.redirect_url}" class="btn btn-success btn-sm">
                                <i class="bi bi-chat-dots me-1"></i>Перейти к консультации
                            </a>
                            <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                                <i class="bi bi-search me-1"></i>Новый поиск
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            const caseFormSection = document.querySelector('.case-form-section');
            if (caseFormSection) {
                caseFormSection.innerHTML = '';
                caseFormSection.appendChild(caseCreatedDiv);
            }
            
            showToast(data.message, 'success');
        } else {
            if (data.errors) {
                Object.keys(data.errors).forEach(field => {
                    const input = document.querySelector(`[name="${field}"]`);
                    if (input) {
                        input.classList.add('is-invalid');
                        const feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback';
                        feedback.innerHTML = data.errors[field].join('<br>');
                        input.parentNode.appendChild(feedback);
                    }
                });
            }
            showToast(data.message || 'Ошибка создания случая', 'danger');
        }
        
    } catch (error) {
        console.error('Create case error:', error);
        showToast('Ошибка при создании диагностического случая: ' + error.message, 'danger');
    } finally {
        isLoading = false;
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        hideCaseCreationProgress();
    }
}

function showCaseCreationProgress() {
    let progressBar = document.querySelector('.case-creation-progress');
    if (!progressBar) {
        const form = document.getElementById('createCaseForm');
        if (form) {
            const progressDiv = document.createElement('div');
            progressDiv.className = 'case-creation-progress mt-3';
            progressDiv.innerHTML = '<div class="case-creation-progress-bar" style="width: 0%"></div>';
            form.appendChild(progressDiv);
            progressBar = progressDiv.querySelector('.case-creation-progress-bar');
        }
    }
    
    let width = 0;
    const interval = setInterval(() => {
        if (width >= 90) {
            clearInterval(interval);
        } else {
            width += 10;
            if (progressBar) {
                progressBar.style.width = width + '%';
            }
        }
    }, 200);
    
    window.caseCreationInterval = interval;
}

function hideCaseCreationProgress() {
    if (window.caseCreationInterval) {
        clearInterval(window.caseCreationInterval);
    }
    const progressBar = document.querySelector('.case-creation-progress-bar');
    if (progressBar) {
        progressBar.style.width = '100%';
        setTimeout(() => {
            const progressDiv = document.querySelector('.case-creation-progress');
            if (progressDiv) {
                progressDiv.remove();
            }
        }, 500);
    }
}

function showCaseCreationSuccess(data) {
    const counter = document.getElementById('resultsCounter');
    if (counter) {
        counter.textContent = 'Случай создан';
        counter.className = 'badge bg-success';
    }
    
    const container = document.getElementById('resultsContainer');
    if (container) {
        const systemMsg = document.createElement('div');
        systemMsg.className = 'alert alert-success slide-in-right';
        systemMsg.style.marginTop = '1rem';
        systemMsg.innerHTML = `
            <div class="d-flex">
                <div class="flex-shrink-0">
                    <i class="bi bi-check-circle-fill fs-4"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <strong>✅ Диагностический случай #${data.case_id} создан!</strong><br>
                    <small>Эксперт ответит в течение 15 минут</small>
                </div>
            </div>
        `;
        container.appendChild(systemMsg);
    }
}

// ============================================
// КАНАЛЫ ПОДДЕРЖКИ
// ============================================
function showTelegramSupport() {
    window.open('https://t.me/your_bot', '_blank');
    showToast('Открываем Telegram...', 'info');
}

function showWhatsAppSupport() {
    window.open('https://wa.me/78001234567', '_blank');
    showToast('Открываем WhatsApp...', 'success');
}

// ============================================
// ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ============================================
function createSymptomCardHTML(result, index) {
    const relevancePercent = Math.round((result.relevance_score || 0.5) * 100);
    const matchTypeBadge = result.match_type === 'exact' ? 'success' : 
                          result.match_type === 'keyword' ? 'primary' : 'secondary';
    const matchTypeText = result.match_type === 'exact' ? 'Точное совпадение' :
                         result.match_type === 'keyword' ? 'По ключевым словам' : 'Похожий симптом';
    
    let html = `
        <div class="result-header">
            <div class="result-title">
                <span>${index + 1}. ${escapeHtml(result.title || '')}</span>
                <div>
                    <span class="badge bg-${matchTypeBadge} me-2">${matchTypeText}</span>
                    <span class="badge bg-info">${relevancePercent}%</span>
                </div>
            </div>
            
            <div class="result-meta">
                ${result.type === 'rule' && result.brand ? `
                    <span class="meta-badge">
                        <i class="bi bi-car-front me-1"></i>${escapeHtml(result.brand)} ${escapeHtml(result.model || '')}
                    </span>
                ` : ''}
                
                ${result.complexity_level ? `
                    <span class="meta-badge">
                        <i class="bi bi-speedometer2 me-1"></i>Сложность: ${result.complexity_level}/10
                    </span>
                ` : ''}
                
                ${result.estimated_time ? `
                    <span class="meta-badge">
                        <i class="bi bi-clock me-1"></i>${result.estimated_time} мин.
                    </span>
                ` : ''}
            </div>
        </div>
        
        <div class="result-content">
    `;
    
    if (result.description) {
        html += `
            <div class="result-section">
                <div class="section-title">
                    <i class="bi bi-card-text"></i>Описание
                </div>
                <p>${escapeHtml(result.description)}</p>
            </div>
        `;
    }
    
    if (result.type === 'rule' && result.diagnostic_steps && result.diagnostic_steps.length > 0) {
        html += `
            <div class="result-section">
                <div class="section-title">
                    <i class="bi bi-list-check"></i>Шаги диагностики
                </div>
                <ol class="step-list">
        `;
        
        result.diagnostic_steps.forEach((step, stepIndex) => {
            html += `
                <li>
                    <div class="step-number">${stepIndex + 1}</div>
                    <div>${escapeHtml(step)}</div>
                </li>
            `;
        });
        
        html += `</ol></div>`;
    }
    
    if (result.possible_causes && result.possible_causes.length > 0) {
        html += `
            <div class="result-section">
                <div class="section-title">
                    <i class="bi bi-exclamation-triangle"></i>Возможные причины
                </div>
                <div class="cause-list">
        `;
        
        result.possible_causes.forEach(cause => {
            html += `<span class="cause-tag">${escapeHtml(cause)}</span>`;
        });
        
        html += `</div></div>`;
    }
    
    html += `
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div>
                ${result.type === 'rule' ? `
                    <small class="text-muted">
                        <i class="bi bi-currency-ruble"></i>
                        Консультация: <strong>${result.consultation_price?.toLocaleString() || '0'} ₽</strong>
                    </small>
                ` : 'Симптом требует дополнительной диагностики'}
            </div>
            <div class="btn-group">
                ${result.type === 'rule' ? `
                    <button class="btn btn-sm btn-primary" 
                            onclick="viewRuleDetails(${result.id})">
                        <i class="bi bi-eye me-1"></i>Подробнее
                    </button>
                    <button class="btn btn-sm btn-success" 
                            onclick="orderConsultation(${result.id})">
                        <i class="bi bi-chat-dots me-1"></i>Консультация
                    </button>
                ` : `
                    <button class="btn btn-sm btn-warning" 
                            onclick="viewSymptomDetails(${result.symptom_id || result.id})">
                        <i class="bi bi-info-circle me-1"></i>Подробнее о симптоме
                    </button>
                `}
            </div>
        </div>
    `;
    
    html += `</div>`;
    return html;
}

function createPartCardHTML(part, index) {
    return `
        <div class="part-card" style="animation-delay: ${index * 0.1}s">
            <div class="part-header">
                <span class="part-sku">${escapeHtml(part.sku || '')}</span>
                <div class="part-price">${escapeHtml(part.formatted_price || '0')} ₽</div>
            </div>
            
            <div class="part-name">${escapeHtml(part.name || '')}</div>
            
            ${part.description ? `
                <div class="text-muted small mb-2" style="font-size: 0.85rem;">
                    ${escapeHtml(part.description.substring(0, 80))}${part.description.length > 80 ? '...' : ''}
                </div>
            ` : ''}
            
            <div class="part-footer">
                <div>
                    ${part.brand ? `
                        <span class="badge bg-light text-dark me-2">${escapeHtml(part.brand)}</span>
                    ` : ''}
                    <span class="badge ${part.availability === 'В наличии' ? 'bg-success' : 
                                      part.availability === 'Мало' ? 'bg-warning' : 'bg-danger'}">
                        ${escapeHtml(part.availability || '')}
                    </span>
                </div>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-primary" 
                            onclick="viewPartDetails(${part.id})">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-success" 
                            onclick="addToCart(${part.id})">
                        <i class="bi bi-cart-plus"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
}

function createDocumentCardHTML(doc, index) {
    const icon = doc.icon || 'bi-file-earmark';
    const fileType = doc.file_type || 'документ';
    
    const pageUrl = doc.page_url || 
                   doc.view_url || 
                   doc.source_url || 
                   '/documents/' + doc.id + '/pages/' + doc.page_number;
    
    const highlightParam = doc.highlight_term ? `?highlight=${encodeURIComponent(doc.highlight_term)}` : '';
    
    let previewHTML = '';
    if (doc.preview_image) {
        previewHTML = `
            <div class="document-preview-image" style="float: right; margin-left: 1rem; margin-bottom: 0.5rem; width: 150px;">
                <img src="${doc.preview_image}" 
                     alt="${doc.preview_alt || 'Превью страницы'}" 
                     style="max-width: 150px; max-height: 150px; 
                            border: 1px solid #ddd; border-radius: 4px;
                            object-fit: cover;"
                     onerror="this.onerror=null; this.src='${getDefaultDocumentIcon(doc.file_type)}'; this.style.padding='20px'; this.style.backgroundColor='#f8f9fa'">
                <div class="text-center small text-muted mt-1">
                    <i class="bi bi-camera"></i> Страница ${doc.page_number}
                </div>
            </div>
        `;
    }
    
    return `
        <div class="document-result fade-in-up" style="animation-delay: ${index * 0.1}s">
            <div class="document-header">
                <div class="document-icon">
                    <i class="bi ${icon}"></i>
                </div>
                <div class="document-title">
                    <a href="${pageUrl}${highlightParam}" 
                       target="_blank" 
                       class="document-link">
                        ${escapeHtml(doc.title || 'Документ')}
                    </a>
                    <div class="document-page-title">
                        Страница ${doc.page_number || ''}
                        ${doc.brand ? ` • ${escapeHtml(doc.brand)}` : ''}
                        ${doc.model ? ` ${escapeHtml(doc.model)}` : ''}
                    </div>
                </div>
                <div class="document-meta">
                    <span class="badge bg-light text-dark">
                        <i class="bi bi-file-earmark"></i> ${fileType}
                    </span>
                    <span class="badge bg-secondary ms-1">
                        <i class="bi bi-eye"></i> ${doc.view_count || 0}
                    </span>
                </div>
            </div>
            
            <div style="overflow: hidden; position: relative;">
                ${previewHTML}
                
                ${doc.excerpt ? `
                    <div class="document-excerpt">
                        <i class="bi bi-quote text-muted me-1"></i>
                        ${escapeHtml(doc.excerpt)}
                    </div>
                ` : ''}
                
                ${doc.content_preview ? `
                    <div class="document-preview">
                        <div class="preview-content" style="max-height: 100px; overflow: hidden;">
                            ${highlightSearchTerms(doc.content_preview, doc.search_terms_found || [])}
                        </div>
                        <a href="#" class="small text-primary" onclick="togglePreview(this)">Показать больше</a>
                    </div>
                ` : ''}
            </div>
            
            <div class="document-tags">
                ${doc.detected_system ? `
                    <span class="badge bg-info me-1">
                        <i class="bi bi-gear"></i> ${escapeHtml(doc.detected_system)}
                    </span>
                ` : ''}
                
                ${doc.detected_component ? `
                    <span class="badge bg-secondary me-1">
                        <i class="bi bi-cpu"></i> ${escapeHtml(doc.detected_component)}
                    </span>
                ` : ''}
                
                <div class="float-end">
                    <a href="${pageUrl}${highlightParam}" 
                       target="_blank" 
                       class="btn btn-sm btn-primary">
                        <i class="bi bi-arrow-up-right me-1"></i> Открыть
                    </a>
                    <button class="btn btn-sm btn-outline-secondary ms-1" 
                            onclick="viewDocumentDetails(${doc.id}, ${doc.page_id})">
                        <i class="bi bi-info-circle"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
}

function getDefaultDocumentIcon(fileType) {
    const icons = {
        'pdf': 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/svgs/solid/file-pdf.svg',
        'doc': 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/svgs/solid/file-word.svg',
        'docx': 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/svgs/solid/file-word.svg',
        'xls': 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/svgs/solid/file-excel.svg',
        'xlsx': 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/svgs/solid/file-excel.svg',
    };
    
    return icons[fileType?.toLowerCase()] || 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/svgs/solid/file.svg';
}

function togglePreview(button) {
    const previewContent = button.previousElementSibling;
    if (previewContent.style.maxHeight === '100px') {
        previewContent.style.maxHeight = 'none';
        button.textContent = 'Скрыть';
    } else {
        previewContent.style.maxHeight = '100px';
        button.textContent = 'Показать больше';
    }
}

function viewDocumentDetails(documentId, pageId) {
    if (pageId) {
        window.open(`/documents/${documentId}/pages/${pageId}/details`, '_blank');
    } else {
        window.open(`/documents/${documentId}`, '_blank');
    }
}

function highlightSearchTerms(text, terms) {
    if (!text || !terms || terms.length === 0) {
        return escapeHtml(text || '');
    }
    
    let highlighted = escapeHtml(text);
    
    terms.forEach(term => {
        if (term && term.length > 2) {
            const regex = new RegExp(`(${escapeRegex(term)})`, 'gi');
            highlighted = highlighted.replace(regex, '<mark class="bg-warning">$1</mark>');
        }
    });
    
    return highlighted;
}

function escapeRegex(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function formatAIResponse(text) {
    if (!text) return '';
    
    return text
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/🤖/g, '<i class="bi bi-robot text-primary"></i>')
        .replace(/🔍/g, '<i class="bi bi-search text-info"></i>')
        .replace(/🎯/g, '<i class="bi bi-bullseye text-danger"></i>')
        .replace(/🛒/g, '<i class="bi bi-cart text-success"></i>')
        .replace(/📄/g, '<i class="bi bi-file-earmark-text text-info"></i>')
        .replace(/🔧/g, '<i class="bi bi-tools text-primary"></i>')
        .replace(/⚠️/g, '<i class="bi bi-exclamation-triangle text-warning"></i>')
        .replace(/⏱️/g, '<i class="bi bi-clock text-secondary"></i>')
        .replace(/💰/g, '<i class="bi bi-currency-ruble text-success"></i>')
        .replace(/✅/g, '<i class="bi bi-check-circle text-success"></i>')
        .replace(/🔗/g, '<i class="bi bi-link text-info"></i>')
        .replace(/💡/g, '<i class="bi bi-lightbulb text-warning"></i>')
        .replace(/\n/g, '<br>');
}

function viewRuleDetails(ruleId) {
    window.open(`/admin/diagnostic/rules/${ruleId}`, '_blank');
}

function viewSymptomDetails(symptomId) {
    window.open(`/admin/diagnostic/symptoms/${symptomId}`, '_blank');
}

function orderConsultation(ruleId) {
    window.location.href = `/diagnostic/consultation/order?rule_id=${ruleId}`;
}

function viewPartDetails(partId) {
    window.open(`/price-items/${partId}`, '_blank');
}

function addToCart(partId) {
    showToast('Запчасть добавлена в корзину', 'success');
}

function escapeHtml(text) {
    if (text === null || text === undefined) {
        return '';
    }
    
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    
    return String(text).replace(/[&<>"']/g, function(m) { 
        return map[m]; 
    });
}

function showToast(message, type = 'info') {
    if (typeof bootstrap === 'undefined') {
        alert(message);
        return;
    }
    
    const toastEl = document.getElementById('liveToast');
    if (toastEl) {
        const toastBody = toastEl.querySelector('.toast-body span') || toastEl.querySelector('.toast-body');
        if (toastBody) {
            toastBody.textContent = message;
        }
        toastEl.className = `toast align-items-center text-bg-${type} border-0`;
        
        try {
            const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
            toast.show();
        } catch (e) {
            console.log('Toast error:', e);
            alert(message);
        }
    }
}

</script>

<!-- Toast контейнер -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-body d-flex justify-content-between align-items-center">
            <span></span>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

@endpush