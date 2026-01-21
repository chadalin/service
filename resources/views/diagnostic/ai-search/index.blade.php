@extends('layouts.app')

@section('title', 'AI поиск по симптомам и правилам диагностики')

@push('styles')
<style>
    /* Основные стили */
    .ai-search-container {
        min-height: calc(100vh - 200px);
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    
    @media (min-width: 992px) {
        .ai-search-container {
            flex-direction: row;
        }
        
        .search-sidebar {
            flex: 0 0 400px;
            max-height: 600px;
            position: sticky;
            top: 1rem;
        }
        
        .results-main {
            flex: 1;
            min-height: 500px;
        }
    }
    
    /* Стили формы поиска */
    .search-input {
        resize: vertical;
        min-height: 120px;
        font-size: 1rem;
    }
    
    .search-btn {
        height: 56px;
        font-size: 1.1rem;
        font-weight: 600;
    }
    
    .advanced-search {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
    }
    
    .advanced-search.show {
        max-height: 500px;
    }
    
    /* Стили AI ответа */
    .ai-response-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        animation: gradientBG 15s ease infinite;
        background-size: 400% 400%;
    }
    
    @keyframes gradientBG {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    
    .ai-response-content {
        white-space: pre-line;
        line-height: 1.6;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .ai-response-content strong {
        color: #ffd700;
    }
    
    /* Стили результатов */
    .result-card {
        border-left: 5px solid #4CAF50;
        transition: all 0.3s ease;
        margin-bottom: 1rem;
    }
    
    .result-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .result-card.symptom-only {
        border-left-color: #FF9800;
    }
    
    .relevance-badge {
        font-size: 0.9rem;
        padding: 0.25rem 0.75rem;
    }
    
    /* Стили статистики */
    .stats-card {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .stat-item {
        text-align: center;
        padding: 0.5rem;
    }
    
    .stat-value {
        font-size: 1.8rem;
        font-weight: bold;
        color: #007bff;
        line-height: 1;
    }
    
    .stat-label {
        font-size: 0.85rem;
        color: #6c757d;
        margin-top: 0.25rem;
    }
    
    /* Анимации */
    .pulse {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
    }
    
    .fade-in {
        animation: fadeIn 0.5s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* Стили для списков */
    .diagnostic-list {
        list-style: none;
        padding-left: 0;
        margin-bottom: 0;
    }
    
    .diagnostic-list li {
        padding: 0.25rem 0;
        position: relative;
        padding-left: 1.5rem;
    }
    
    .diagnostic-list li:before {
        content: '✓';
        position: absolute;
        left: 0;
        color: #28a745;
        font-weight: bold;
    }
    
    /* Стили для загрузки */
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        border-radius: 12px;
    }
    
    /* Кастомный скроллбар */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
</style>
@endpush

@section('content')
<div class="ai-search-container">
    <!-- Левая колонка - форма поиска -->
    <div class="search-sidebar">
        <div class="card shadow-lg h-100">
            <div class="card-header bg-primary text-white">
                <div class="d-flex align-items-center">
                    <i class="bi bi-robot me-2 fs-4"></i>
                    <h4 class="mb-0">🤖 AI Поиск симптомов</h4>
                </div>
            </div>
            
            <div class="card-body position-relative">
                <form id="aiSearchForm" novalidate>
                    @csrf
                    
                    <!-- Статистика -->
                    <div class="stats-card mb-4">
                        <div class="row g-2">
                            <div class="col-4">
                                <div class="stat-item">
                                    <div class="stat-value" id="statsSymptoms">{{ $stats['symptoms_count'] }}</div>
                                    <div class="stat-label">Симптомов</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <div class="stat-value" id="statsRules">{{ $stats['rules_count'] }}</div>
                                    <div class="stat-label">Правил</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <div class="stat-value">{{ $stats['brands_count'] }}</div>
                                    <div class="stat-label">Марок</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Описание проблемы -->
                    <div class="mb-4">
                        <label for="query" class="form-label fw-bold">
                            <i class="bi bi-chat-left-text me-1"></i>Опишите проблему
                        </label>
                        <textarea class="form-control search-input" 
                                  id="query" 
                                  name="query" 
                                  placeholder="Например:
🚗 Автомобиль не заводится с утра
🔊 Слышен стук в двигателе при разгоне
⚠️ Загорается лампочка Check Engine
📉 Падает мощность двигателя
💨 Дымит выхлоп при запуске
🎯 Проблемы с холостым ходом"
                                  rows="5"
                                  required></textarea>
                        <div class="form-text mt-1">
                            Опишите максимально подробно для точного анализа
                        </div>
                    </div>

                    <!-- Фильтры -->
                    <div class="row g-2 mb-4">
                        <div class="col-md-6 mb-2">
                            <label for="brand_id" class="form-label">
                                <i class="bi bi-car-front me-1"></i>Марка (опционально)
                            </label>
                            <select name="brand_id" 
                                    id="brand_id" 
                                    class="form-select">
                                <option value="">Все марки</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}">
                                        {{ $brand->name }}
                                        @if($brand->name_cyrillic)
                                            ({{ $brand->name_cyrillic }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-2">
                            <label for="model_id" class="form-label">
                                <i class="bi bi-card-checklist me-1"></i>Модель
                            </label>
                            <select name="model_id" 
                                    id="model_id" 
                                    class="form-select"
                                    disabled>
                                <option value="">Сначала выберите марку</option>
                            </select>
                        </div>
                    </div>

                    <!-- Тип поиска -->
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="bi bi-gear me-1"></i>Тип поиска
                        </label>
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="radio" 
                                   name="search_type" 
                                   id="search_basic" 
                                   value="basic" 
                                   checked>
                            <label class="form-check-label" for="search_basic">
                                Базовый (быстрый)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="radio" 
                                   name="search_type" 
                                   id="search_advanced" 
                                   value="advanced">
                            <label class="form-check-label" for="search_advanced">
                                Расширенный (детальный)
                            </label>
                        </div>
                    </div>

                    <!-- Кнопка поиска -->
                    <button type="submit" 
                            class="btn btn-primary btn-lg w-100 search-btn" 
                            id="searchBtn">
                        <span class="d-flex align-items-center justify-content-center">
                            <i class="bi bi-search me-2"></i>
                            <span id="searchText">Начать AI-анализ</span>
                            <span id="searchSpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
                        </span>
                    </button>
                    
                    <!-- Быстрые действия -->
                    <div class="mt-3 text-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary me-2" 
                                onclick="clearSearch()">
                            <i class="bi bi-x-circle me-1"></i>Очистить
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info" 
                                onclick="toggleAdvanced()" id="toggleAdvancedBtn">
                            <i class="bi bi-sliders me-1"></i>Больше настроек
                        </button>
                    </div>
                    
                    <!-- Дополнительные настройки -->
                    <div class="advanced-search mt-3" id="advancedOptions">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="mb-3">
                                    <i class="bi bi-tools me-1"></i>Дополнительные параметры
                                </h6>
                                
                                <div class="mb-3">
                                    <label for="complexity" class="form-label">Уровень сложности</label>
                                    <select name="complexity" id="complexity" class="form-select">
                                        <option value="">Любой</option>
                                        <option value="1-3">Низкий (1-3)</option>
                                        <option value="4-6">Средний (4-6)</option>
                                        <option value="7-10">Высокий (7-10)</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="max_results" class="form-label">Макс. результатов</label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="max_results" 
                                           name="max_results" 
                                           min="5" 
                                           max="50" 
                                           value="10">
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="only_with_rules" 
                                           name="only_with_rules">
                                    <label class="form-check-label" for="only_with_rules">
                                        Только с правилами диагностики
                                    </label>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="group_by_brand" 
                                           name="group_by_brand">
                                    <label class="form-check-label" for="group_by_brand">
                                        Группировать по маркам
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                
                <!-- Индикатор загрузки -->
                <div id="formLoading" class="loading-overlay d-none">
                    <div class="text-center">
                        <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
                        <h5 class="text-primary">AI анализирует запрос...</h5>
                        <p class="text-muted">Ищем симптомы и правила диагностики</p>
                    </div>
                </div>
            </div>
            
            <div class="card-footer">
                <small class="text-muted">
                    <i class="bi bi-lightbulb me-1"></i>
                    <strong>Совет:</strong> Чем подробнее описание, тем точнее AI-анализ
                </small>
            </div>
        </div>
    </div>

    <!-- Правая колонка - результаты -->
    <div class="results-main">
        <div class="card shadow-lg h-100">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-file-earmark-text me-2"></i>Результаты AI-поиска
                </h5>
                <div>
                    <span class="badge bg-secondary me-2" id="resultsStats">Ожидание запроса</span>
                    <button class="btn btn-sm btn-outline-primary" id="refreshBtn" onclick="refreshResults()">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div id="searchResults" class="h-100 p-3 custom-scrollbar" style="max-height: 600px; overflow-y: auto;">
                    <!-- Начальное состояние -->
                    <div class="text-center py-5 fade-in">
                        <div class="mb-4">
                            <i class="bi bi-robot display-1 text-primary pulse"></i>
                        </div>
                        <h3 class="text-primary mb-3">AI-помощник по диагностике</h3>
                        <p class="text-muted mb-4">
                            Опишите проблему с автомобилем, и AI найдет<br>
                            соответствующие симптомы и правила диагностики
                        </p>
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>База содержит:</strong><br>
                                    • {{ $stats['symptoms_count'] }} симптомов<br>
                                    • {{ $stats['rules_count'] }} правил диагностики<br>
                                    • {{ $stats['brands_count'] }} марок автомобилей
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-footer d-none" id="resultsFooter">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted" id="searchInfo"></small>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-secondary" id="exportBtn" onclick="exportResults()">
                            <i class="bi bi-download me-1"></i>Экспорт
                        </button>
                        <button class="btn btn-sm btn-outline-primary" id="consultationBtn" onclick="orderConsultation()">
                            <i class="bi bi-chat-dots me-1"></i>Консультация
                        </button>
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
let allModels = @json($models);
let currentSearchData = null;
let isLoading = false;
let currentResults = [];

document.addEventListener('DOMContentLoaded', function() {
    console.log('AI Search page loaded');
    
    // Инициализация
    initBrandModelSelect();
    initEventListeners();
    loadPopularSymptoms();
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

// Инициализация обработчиков событий
function initEventListeners() {
    const searchForm = document.getElementById('aiSearchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            await performAISearch();
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

// Выполнение AI поиска
async function performAISearch() {
    if (isLoading) return;
    
    const queryInput = document.getElementById('query');
    const searchBtn = document.getElementById('searchBtn');
    const searchText = document.getElementById('searchText');
    const searchSpinner = document.getElementById('searchSpinner');
    const resultsDiv = document.getElementById('searchResults');
    const resultsFooter = document.getElementById('resultsFooter');
    const searchInfo = document.getElementById('searchInfo');
    const formLoading = document.getElementById('formLoading');
    
    // Валидация
    if (!queryInput.value.trim()) {
        showToast('Введите описание проблемы', 'warning');
        queryInput.focus();
        return;
    }
    
    // Настройка UI
    isLoading = true;
    searchBtn.disabled = true;
    searchText.textContent = 'AI анализирует...';
    searchSpinner.classList.remove('d-none');
    formLoading.classList.remove('d-none');
    
    // Собираем параметры
    const searchParams = {
        query: queryInput.value.trim(),
        brand_id: document.getElementById('brand_id').value || null,
        model_id: document.getElementById('model_id').value || null,
        search_type: document.querySelector('input[name="search_type"]:checked').value,
        _token: document.querySelector('meta[name="csrf-token"]').content
    };
    
    // Дополнительные параметры если открыты
    const advancedOptions = document.getElementById('advancedOptions');
    if (advancedOptions.classList.contains('show')) {
        searchParams.complexity = document.getElementById('complexity').value;
        searchParams.max_results = document.getElementById('max_results').value;
        searchParams.only_with_rules = document.getElementById('only_with_rules').checked;
        searchParams.group_by_brand = document.getElementById('group_by_brand').checked;
    }
    
    // Сохраняем текущие параметры
    currentSearchData = searchParams;
    
    // Показываем состояние загрузки
    resultsDiv.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
            <h4 class="text-primary">AI анализирует проблему...</h4>
            <p class="text-muted">Ищем симптомы и правила диагностики</p>
            <div class="progress mt-3" style="height: 6px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
            </div>
        </div>
    `;
    
    resultsFooter.classList.add('d-none');
    
    try {
        const response = await fetch('{{ route("diagnostic.ai.search") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': searchParams._token
            },
            body: JSON.stringify(searchParams)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('AI Search response:', data);
        
        if (data.success) {
            currentResults = data.results || [];
            displayAIResults(data);
            
            // Обновляем статистику в футере
            if (searchInfo) {
                searchInfo.innerHTML = `
                    Найдено за ${data.execution_time || '0'} мс. | 
                    Симптомов: ${data.stats?.symptoms_found || 0} | 
                    Правил: ${data.stats?.rules_found || 0}
                `;
            }
            
            // Показываем футер
            resultsFooter.classList.remove('d-none');
            
            // Обновляем счетчик
            const resultsStats = document.getElementById('resultsStats');
            if (resultsStats) {
                resultsStats.textContent = `Найдено: ${data.count}`;
                resultsStats.className = data.count > 0 ? 'badge bg-success' : 'badge bg-secondary';
            }
            
            // Показываем уведомление
            showToast(`AI нашел ${data.count} результатов`, 'success');
            
        } else {
            throw new Error(data.message || 'Ошибка AI поиска');
        }
    } catch (error) {
        console.error('AI Search error:', error);
        
        resultsDiv.innerHTML = `
            <div class="text-center py-5">
                <i class="bi bi-exclamation-triangle display-1 text-danger mb-3"></i>
                <h4 class="text-danger mb-3">Ошибка AI-поиска</h4>
                <p class="text-muted">${error.message}</p>
                <button class="btn btn-primary mt-2" onclick="performAISearch()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Повторить
                </button>
            </div>
        `;
        
        showToast(`Ошибка: ${error.message}`, 'danger');
    } finally {
        // Восстанавливаем UI
        isLoading = false;
        searchBtn.disabled = false;
        searchText.textContent = 'Начать AI-анализ';
        searchSpinner.classList.add('d-none');
        formLoading.classList.add('d-none');
    }
}

// Отображение AI результатов
function displayAIResults(data) {
    const resultsDiv = document.getElementById('searchResults');
    
    if (!data || !data.results) {
        resultsDiv.innerHTML = `
            <div class="text-center py-5">
                <i class="bi bi-inbox display-1 text-muted mb-3"></i>
                <h4 class="text-muted mb-3">Нет результатов</h4>
                <p class="text-muted">Попробуйте изменить параметры поиска</p>
            </div>
        `;
        return;
    }

    const results = Array.isArray(data.results) ? data.results : [];
    const count = data.count || results.length;
    
    if (count === 0) {
        resultsDiv.innerHTML = `
            <div class="text-center py-5">
                <i class="bi bi-search display-1 text-muted mb-3"></i>
                <h4 class="text-muted mb-3">Ничего не найдено</h4>
                <p class="text-muted">
                    AI не смог найти подходящих симптомов.<br>
                    Попробуйте:
                    • Изменить формулировку<br>
                    • Убрать фильтры марки/модели<br>
                    • Использовать другие ключевые слова
                </p>
            </div>
        `;
        return;
    }

    let html = '';
    
    // AI ответ
    if (data.ai_response) {
        html += `
            <div class="card ai-response-card mb-4 fade-in">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-white rounded-circle p-2 me-3">
                            <i class="bi bi-robot text-primary fs-4"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0">🤖 AI-анализ</h5>
                            <small class="text-white-50">На основе базы симптомов и правил</small>
                        </div>
                    </div>
                    <div class="ai-response-content">
                        ${formatAIResponse(data.ai_response)}
                    </div>
                </div>
            </div>
        `;
    }
    
    // Результаты
    html += `<h5 class="mb-3">Найдено решений: <span class="badge bg-primary">${count}</span></h5>`;
    
    results.forEach((item, index) => {
        html += createResultCard(item, index);
    });
    
    resultsDiv.innerHTML = html;
    
    // Инициализация тултипов
    initTooltips();
}

// Форматирование AI ответа
function formatAIResponse(response) {
    // Заменяем маркеры на эмодзи и форматирование
    let formatted = response
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/🔍/g, '<i class="bi bi-search text-warning"></i>')
        .replace(/🤖/g, '<i class="bi bi-robot text-info"></i>')
        .replace(/🔧/g, '<i class="bi bi-tools text-primary"></i>')
        .replace(/🚗/g, '<i class="bi bi-car-front text-success"></i>')
        .replace(/⚠️/g, '<i class="bi bi-exclamation-triangle text-warning"></i>')
        .replace(/⏱️/g, '<i class="bi bi-clock text-secondary"></i>')
        .replace(/💰/g, '<i class="bi bi-cash text-success"></i>')
        .replace(/📊/g, '<i class="bi bi-graph-up text-info"></i>')
        .replace(/🎯/g, '<i class="bi bi-bullseye text-danger"></i>')
        .replace(/💡/g, '<i class="bi bi-lightbulb text-warning"></i>')
        .replace(/\n/g, '<br>');
    
    return formatted;
}

// Создание карточки результата
function createResultCard(item, index) {
    const relevancePercent = Math.min(100, Math.round((item.relevance_score || 0.5) * 100));
    let relevanceColor = 'secondary';
    let relevanceIcon = 'bi-circle';
    
    if (relevancePercent > 80) {
        relevanceColor = 'success';
        relevanceIcon = 'bi-check-circle-fill';
    } else if (relevancePercent > 60) {
        relevanceColor = 'primary';
        relevanceIcon = 'bi-check-circle';
    } else if (relevancePercent > 40) {
        relevanceColor = 'warning';
        relevanceIcon = 'bi-exclamation-circle';
    }
    
    let html = `
        <div class="card result-card ${item.type === 'symptom' && !item.has_rules ? 'symptom-only' : ''} mb-3 fade-in" 
             style="animation-delay: ${index * 0.1}s">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="flex-grow-1">
                        <h6 class="card-title mb-1">
                            ${item.type === 'rule' ? 
                                '<i class="bi bi-clipboard-check text-success me-2"></i>' : 
                                '<i class="bi bi-exclamation-triangle text-warning me-2"></i>'}
                            ${item.title}
                        </h6>
                        ${item.description ? `
                            <p class="card-text text-muted small mb-2">
                                ${item.description}
                            </p>
                        ` : ''}
                    </div>
                    <span class="badge relevance-badge bg-${relevanceColor} ms-2">
                        <i class="bi ${relevanceIcon} me-1"></i>
                        ${relevancePercent}%
                    </span>
                </div>
                
                <div class="mb-3">
                    ${item.brand ? `
                        <span class="badge bg-info me-2 mb-1">
                            <i class="bi bi-car-front me-1"></i>
                            ${item.brand}
                        </span>
                    ` : ''}
                    ${item.model ? `
                        <span class="badge bg-secondary me-2 mb-1">
                            ${item.model}
                        </span>
                    ` : ''}
                    ${item.complexity_level ? `
                        <span class="badge bg-warning me-2 mb-1">
                            Сложность: ${item.complexity_level}/10
                        </span>
                    ` : ''}
                    ${item.estimated_time ? `
                        <span class="badge bg-primary me-2 mb-1">
                            ~${item.estimated_time} мин.
                        </span>
                    ` : ''}
                </div>
    `;
    
    // Диагностические шаги
    if (item.diagnostic_steps && Array.isArray(item.diagnostic_steps) && item.diagnostic_steps.length > 0) {
        const steps = item.diagnostic_steps.slice(0, 3);
        html += `
            <div class="mb-3">
                <small class="text-muted d-block mb-1"><strong>Шаги диагностики:</strong></small>
                <ul class="diagnostic-list small">
                    ${steps.map(step => `<li>${step}</li>`).join('')}
                    ${item.diagnostic_steps.length > 3 ? 
                        `<li class="text-muted">... и еще ${item.diagnostic_steps.length - 3} шагов</li>` : ''}
                </ul>
            </div>
        `;
    }
    
    // Возможные причины
    if (item.possible_causes && Array.isArray(item.possible_causes) && item.possible_causes.length > 0) {
        const causes = item.possible_causes.slice(0, 3);
        html += `
            <div class="mb-3">
                <small class="text-muted d-block mb-1"><strong>Возможные причины:</strong></small>
                <div class="d-flex flex-wrap gap-1">
                    ${causes.map(cause => `
                        <span class="badge bg-light text-dark">${cause}</span>
                    `).join('')}
                    ${item.possible_causes.length > 3 ? 
                        `<span class="badge bg-light text-dark">+${item.possible_causes.length - 3}</span>` : ''}
                </div>
            </div>
        `;
    }
    
    // Кнопки действий
    html += `
        <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-muted">
                ${item.consultation_price ? `
                    <i class="bi bi-cash me-1"></i>
                    Консультация: <strong>${item.consultation_price.toLocaleString()} ₽</strong>
                ` : 'Симптом без конкретных правил'}
            </small>
            <div class="btn-group">
                ${item.type === 'rule' ? `
                    <button class="btn btn-sm btn-outline-primary" 
                            onclick="viewRuleDetails(${item.id})"
                            data-bs-toggle="tooltip" 
                            title="Подробнее о правиле">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-success" 
                            onclick="orderConsultationForRule(${item.symptom_id}, ${item.brand_id || 'null'}, ${item.model_id || 'null'})"
                            data-bs-toggle="tooltip" 
                            title="Заказать консультацию">
                        <i class="bi bi-chat-dots"></i> Консультация
                    </button>
                ` : `
                    <button class="btn btn-sm btn-outline-warning" 
                            onclick="viewSymptomDetails(${item.symptom_id})">
                        <i class="bi bi-info-circle"></i> Подробнее
                    </button>
                `}
            </div>
        </div>
    `;
    
    html += `</div></div>`;
    
    return html;
}

// Вспомогательные функции
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
}

function clearSearch() {
    document.getElementById('query').value = '';
    document.getElementById('brand_id').value = '';
    resetModelSelect();
    document.getElementById('search_basic').checked = true;
    
    // Скрываем расширенные настройки
    document.getElementById('advancedOptions').classList.remove('show');
    document.getElementById('toggleAdvancedBtn').innerHTML = '<i class="bi bi-sliders me-1"></i>Больше настроек';
    
    showToast('Поиск очищен', 'info');
}

function toggleAdvanced() {
    const advancedOptions = document.getElementById('advancedOptions');
    const toggleBtn = document.getElementById('toggleAdvancedBtn');
    
    if (advancedOptions.classList.contains('show')) {
        advancedOptions.classList.remove('show');
        toggleBtn.innerHTML = '<i class="bi bi-sliders me-1"></i>Больше настроек';
    } else {
        advancedOptions.classList.add('show');
        toggleBtn.innerHTML = '<i class="bi bi-sliders me-1"></i>Скрыть настройки';
    }
}

function refreshResults() {
    if (currentSearchData) {
        performAISearch();
    } else {
        showToast('Сначала выполните поиск', 'warning');
    }
}

function exportResults() {
    if (currentResults.length === 0) {
        showToast('Нет результатов для экспорта', 'warning');
        return;
    }
    
    // Создаем CSV
    let csv = 'Название;Описание;Марка;Модель;Шаги диагностики;Возможные причины;Сложность;Время;Цена\n';
    
    currentResults.forEach(item => {
        const steps = Array.isArray(item.diagnostic_steps) ? 
            item.diagnostic_steps.join('; ') : '';
        const causes = Array.isArray(item.possible_causes) ? 
            item.possible_causes.join('; ') : '';
        
        csv += `"${item.title || ''}";"${item.description || ''}";"${item.brand || ''}";"${item.model || ''}";"${steps}";"${causes}";${item.complexity_level || ''};${item.estimated_time || ''};${item.consultation_price || ''}\n`;
    });
    
    // Создаем и скачиваем файл
    const blob = new Blob(["\uFEFF" + csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', `ai_search_results_${new Date().toISOString().slice(0,10)}.csv`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showToast('Результаты экспортированы в CSV', 'success');
}

function viewRuleDetails(ruleId) {
    window.open(`/admin/diagnostic/rules/${ruleId}`, '_blank');
}

function viewSymptomDetails(symptomId) {
    window.open(`/admin/diagnostic/symptoms/${symptomId}`, '_blank');
}

function orderConsultation() {
    if (currentResults.length === 0) {
        showToast('Нет результатов для консультации', 'warning');
        return;
    }
    
    // Перенаправляем на страницу заказа консультации
    const query = document.getElementById('query').value;
    const brandId = document.getElementById('brand_id').value;
    
    let url = '/diagnostic/consultation/order?ai_search=true';
    
    if (query) {
        url += `&query=${encodeURIComponent(query)}`;
    }
    
    if (brandId) {
        url += `&brand_id=${brandId}`;
    }
    
    window.location.href = url;
}

function orderConsultationForRule(symptomId, brandId, modelId) {
    let url = `/diagnostic/consultation/order?symptom_id=${symptomId}`;
    
    if (brandId && brandId !== 'null') {
        url += `&brand_id=${brandId}`;
    }
    
    if (modelId && modelId !== 'null') {
        url += `&model_id=${modelId}`;
    }
    
    window.location.href = url;
}

async function loadPopularSymptoms() {
    try {
        const response = await fetch('/diagnostic/ai/popular-symptoms');
        const data = await response.json();
        
        if (data.success && data.symptoms.length > 0) {
            // Можно обновить интерфейс популярными симптомами
            console.log('Popular symptoms loaded:', data.symptoms);
        }
    } catch (error) {
        console.error('Error loading popular symptoms:', error);
    }
}

function showToast(message, type = 'info') {
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-bg-${type}" 
             role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                        data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 3000
    });
    toast.show();
    
    toastElement.addEventListener('hidden.bs.toast', function() {
        this.remove();
    });
}
</script>
@endpush