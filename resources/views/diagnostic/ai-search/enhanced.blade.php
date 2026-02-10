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
// Глобальные переменные
// Глобальные переменные
let allModels = @json($models);
let currentSearchData = null;
let isLoading = false;
let currentResults = [];

document.addEventListener('DOMContentLoaded', function() {
    console.log('AI Search page loaded');
    
    // Инициализация
    initBrandModelSelect();
    initEventListeners();
});

// Инициализация выбора марки/модели
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

// Загрузка моделей для выбранной марки
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

// Сброс выбора модели
function resetModelSelect() {
    const modelSelect = document.getElementById('model_id');
    modelSelect.innerHTML = '<option value="">Сначала выберите марку</option>';
    modelSelect.disabled = true;
}

// Инициализация обработчиков событий
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
    
    // Настройка UI
    isLoading = true;
    searchBtn.disabled = true;
    searchSpinner.classList.remove('d-none');
    
    // Показываем состояние загрузки
    showLoadingState();
    
    try {
        // Используем FormData для корректной отправки данных
        const formData = new FormData(form);
        
        // Получаем значения и конвертируем пустые строки в null
        const brandIdValue = formData.get('brand_id');
        const modelIdValue = document.getElementById('model_id').disabled ? null : formData.get('model_id');
        
        // Создаем объект с данными
        const searchData = {
            query: formData.get('query'),
            brand_id: brandIdValue && brandIdValue !== '' ? parseInt(brandIdValue) : null,
            model_id: modelIdValue && modelIdValue !== '' ? parseInt(modelIdValue) : null,
            search_type: formData.get('search_type'),
        };
        
        // Проверяем валидность числовых значений
        if (searchData.brand_id !== null && isNaN(searchData.brand_id)) {
            searchData.brand_id = null;
        }
        if (searchData.model_id !== null && isNaN(searchData.model_id)) {
            searchData.model_id = null;
        }
        
        console.log('Sending search data:', searchData);
        
        // Сохраняем текущие параметры
        currentSearchData = searchData;
        
        // Отправляем запрос с правильными заголовками
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
        
        // Проверяем статус ответа
        if (!response.ok) {
            // Если ошибка валидации (422)
            if (response.status === 422) {
                const errorData = await response.json();
                console.error('Validation error:', errorData);
                
                // Формируем сообщение об ошибке
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
        
        // Показываем уведомление
        const totalResults = data.results.length + (data.parts?.length || 0) + (data.documents?.length || 0);
        showToast(`Найдено ${totalResults} результатов`, 'success');
        
    } catch (error) {
        console.error('Search error:', error);
        
        // Проверяем если это CSRF ошибка
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
    
    // Обновляем счетчик
    document.getElementById('resultsCounter').textContent = 'Поиск...';
    document.getElementById('resultsCounter').className = 'badge bg-warning';
}

function showErrorState(errorMessage) {
    const container = document.getElementById('resultsContainer');
    container.innerHTML = `
        <div class="text-center py-5">
            <i class="bi bi-exclamation-triangle display-1 text-danger mb-3"></i>
            <h4 class="text-danger mb-3">Ошибка поиска</h4>
            <p class="text-muted mb-4">${errorMessage}</p>
            <button class="btn btn-primary" onclick="performEnhancedSearch()">
                <i class="bi bi-arrow-clockwise me-1"></i>Повторить
            </button>
        </div>
    `;
    
    document.getElementById('resultsCounter').textContent = 'Ошибка';
    document.getElementById('resultsCounter').className = 'badge bg-danger';
}

function displayStructuredResults(data) {
    console.log('Displaying results:', data);
    
    const container = document.getElementById('resultsContainer');
    const counter = document.getElementById('resultsCounter');
    
    // Обновляем счетчик
    const totalResults = data.results?.length || 0;
    counter.textContent = `Найдено: ${totalResults}`;
    counter.className = totalResults > 0 ? 'badge bg-success' : 'badge bg-secondary';
    
    // Очищаем контейнер
    container.innerHTML = '';
    
    // Постепенно добавляем контент
    setTimeout(() => {
        addAIResponse(data.ai_response, container);
        
        setTimeout(() => {
            if (data.results && data.results.length > 0) {
                addSymptomsResults(data.results, container);
                
                setTimeout(() => {
                    if (data.parts && data.parts.length > 0) {
                        addPartsResults(data.parts, container);
                    }
                    
                    setTimeout(() => {
                        console.log('Trying to add documents:', data.documents);
                        if (data.documents && data.documents.length > 0) {
                            console.log('Documents exist, calling addDocumentsResults');
                            addDocumentsResults(data.documents, container);
                        } else {
                            console.log('No documents to display');
                        }
                    }, 300);
                }, 300);
            } else {
                console.log('No symptoms found');
            }
        }, 500);
    }, 300);
}

function addAIResponse(response, container) {
    const responseDiv = document.createElement('div');
    responseDiv.className = 'ai-response-box fade-in-up';
    
    // Форматируем ответ
    const formattedResponse = formatAIResponse(response);
    
    responseDiv.innerHTML = `
        <div class="ai-response-content">
            ${formattedResponse}
        </div>
    `;
    
    container.appendChild(responseDiv);
}

function addSymptomsResults(results, container) {
    // Показываем только топ-5 результатов
    const topResults = results.slice(0, 5);
    
    topResults.forEach((result, index) => {
        setTimeout(() => {
            const resultDiv = document.createElement('div');
            resultDiv.className = 'main-result-card fade-in-up';
            resultDiv.style.animationDelay = `${index * 0.2}s`;
            
            resultDiv.innerHTML = createSymptomCardHTML(result, index);
            container.appendChild(resultDiv);
            
            // Плавное появление
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
    console.log('addDocumentsResults called with:', docs);
    
    if (!docs || !Array.isArray(docs) || docs.length === 0) {
        console.log('No valid docs array');
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
        console.log(`Document ${index}:`, doc);
        try {
            docsHTML += createDocumentCardHTML(doc, index);
        } catch (error) {
            console.error('Error creating document card:', error, doc);
            docsHTML += `<div class="alert alert-danger">Ошибка отображения документа</div>`;
        }
    });
    
    docsHTML += `</div>`;
    docsDiv.innerHTML = docsHTML;
    container.appendChild(docsDiv);
}

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
    
    // Описание
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
    
    // Шаги диагностики (только для правил)
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
    
    // Возможные причины
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
    
    // Кнопки действий
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
    
    // Используем публичный URL
    const pageUrl = doc.page_url || 
                   doc.view_url || 
                   doc.source_url || 
                   '/documents/' + doc.id + '/pages/' + doc.page_number;
    
    const pageNumberText = doc.page_number ? ` (стр. ${doc.page_number})` : '';
    const highlightParam = doc.highlight_term ? `?highlight=${encodeURIComponent(doc.highlight_term)}` : '';
    
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
                </div>
            </div>
            
            ${doc.excerpt ? `
                <div class="document-excerpt">
                    ${escapeHtml(doc.excerpt)}
                </div>
            ` : ''}
            
            ${doc.content_preview ? `
                <div class="document-preview">
                    <div class="preview-content">
                        ${this.highlightSearchTerms(doc.content_preview, doc.search_terms_found || [])}
                    </div>
                </div>
            ` : ''}
            
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
                
                <a href="${pageUrl}${highlightParam}" 
                   target="_blank" 
                   class="btn btn-sm btn-primary float-end">
                    <i class="bi bi-arrow-up-right me-1"></i> Открыть страницу
                </a>
            </div>
        </div>
    `;
}

// Функция для подсветки найденных терминов
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

// Вспомогательные функции
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
    // Используйте существующую систему нотаций или Bootstrap Toast
    const toast = new bootstrap.Toast(document.getElementById('liveToast'));
    const toastBody = document.querySelector('.toast-body');
    if (toastBody) {
        toastBody.textContent = message;
        document.querySelector('.toast').className = `toast align-items-center text-bg-${type}`;
        toast.show();
    }
}

</script>

<!-- Добавьте в layout если нет -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-body"></div>
        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
</div>
@endpush