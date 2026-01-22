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
        line-height: 1.5;
    }
    
    .search-btn {
        height: 56px;
        font-size: 1.1rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .search-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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
        overflow: hidden;
    }
    
    .result-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.12);
    }
    
    .result-card.symptom-only {
        border-left-color: #FF9800;
    }
    
    .result-card.rule-match {
        border-left-color: #2196F3;
    }
    
    .relevance-badge {
        font-size: 0.9rem;
        padding: 0.25rem 0.75rem;
    }
    
    /* Стили раскрывающихся списков */
    .expandable-list {
        max-height: 150px;
        overflow: hidden;
        transition: max-height 0.3s ease;
        position: relative;
    }
    
    .expandable-list.expanded {
        max-height: 1000px;
    }
    
    .expandable-list:not(.expanded)::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 40px;
        background: linear-gradient(to top, rgba(255,255,255,0.9), transparent);
        pointer-events: none;
    }
    
    .expand-btn {
        background: none;
        border: none;
        color: #007bff;
        cursor: pointer;
        font-size: 0.9rem;
        padding: 0.25rem 0.5rem;
        margin-top: 0.5rem;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        transition: color 0.2s;
    }
    
    .expand-btn:hover {
        color: #0056b3;
        text-decoration: underline;
    }
    
    .expand-btn i {
        transition: transform 0.3s ease;
    }
    
    .expand-btn.expanded i {
        transform: rotate(180deg);
    }
    
    /* Стили для списков */
    .diagnostic-list {
        list-style: none;
        padding-left: 0;
        margin-bottom: 0;
    }
    
    .diagnostic-list li {
        padding: 0.5rem 0;
        position: relative;
        padding-left: 1.5rem;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .diagnostic-list li:last-child {
        border-bottom: none;
    }
    
    .diagnostic-list li:before {
        content: '✓';
        position: absolute;
        left: 0;
        color: #28a745;
        font-weight: bold;
    }
    
    .causes-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 0;
    }
    
    .cause-badge {
        background: #e3f2fd;
        color: #1565c0;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
        transition: all 0.2s;
    }
    
    .cause-badge:hover {
        background: #bbdefb;
        transform: scale(1.05);
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
    
    /* Загрузка */
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.95);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        border-radius: 12px;
        backdrop-filter: blur(2px);
    }
    
    /* Группировка по брендам */
    .brand-group {
        margin-bottom: 1.5rem;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .brand-group-header {
        background: #f8f9fa;
        padding: 1rem;
        border-bottom: 1px solid #dee2e6;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .brand-group-count {
        background: #007bff;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
    }
    
    /* Кастомный скроллбар */
    .custom-scrollbar::-webkit-scrollbar {
        width: 8px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    
    /* Соответствие ключевых слов */
    .matched-keywords {
        background: #fff3cd;
        border-left: 3px solid #ffc107;
        padding: 0.5rem;
        margin: 0.5rem 0;
        border-radius: 0 4px 4px 0;
    }
    
    .keyword-tag {
        background: #ffc107;
        color: #856404;
        padding: 0.15rem 0.5rem;
        border-radius: 3px;
        font-size: 0.8rem;
        margin-right: 0.25rem;
        display: inline-block;
        margin-bottom: 0.25rem;
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
                            <div class="form-text small">Оставьте пустым для поиска по всем моделям</div>
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
                        <div class="progress mt-3" style="width: 200px; height: 6px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-footer">
                <small class="text-muted">
                    <i class="bi bi-lightbulb me-1"></i>
                    <strong>Совет:</strong> Используйте ключевые слова для более точного поиска
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
                    <button class="btn btn-sm btn-outline-primary" id="refreshBtn" onclick="refreshResults()" title="Обновить">
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
                        <div class="mt-4">
                            <h6>🎯 Примеры поиска:</h6>
                            <div class="d-flex flex-wrap justify-content-center gap-2 mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="setExample('не заводится двигатель')">
                                    не заводится двигатель
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="setExample('стук в двигателе')">
                                    стук в двигателе
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="setExample('горит check engine')">
                                    горит check engine
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="setExample('плохо греет печка')">
                                    плохо греет печка
                                </button>
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
let expandedItems = new Set();

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
    expandedItems.clear(); // Сбрасываем раскрытые элементы
    
    // Показываем состояние загрузки
    resultsDiv.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
            <h4 class="text-primary">AI анализирует проблему...</h4>
            <p class="text-muted">Ищем симптомы и правила диагностики</p>
            <div class="progress mt-3" style="height: 6px; width: 300px; margin: 0 auto;">
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
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || `HTTP error! status: ${response.status}`);
        }
        
        if (!data.success) {
            throw new Error(data.message || 'Ошибка сервера');
        }
        
        console.log('AI Search response:', data);
        
        currentResults = data.results || [];
        displayAIResults(data);
        
        // Обновляем статистику в футере
        if (searchInfo) {
            const time = data.execution_time || '0';
            const found = data.stats?.symptoms_found || 0;
            const rules = data.stats?.rules_found || 0;
            searchInfo.innerHTML = `
                Найдено за ${time} мс | Симптомов: ${found} | Правил: ${rules}
            `;
        }
        
        // Показываем футер
        resultsFooter.classList.remove('d-none');
        
        // Обновляем счетчик
        const resultsStats = document.getElementById('resultsStats');
        if (resultsStats) {
            const count = data.count || currentResults.length || 0;
            resultsStats.textContent = `Найдено: ${count}`;
            resultsStats.className = count > 0 ? 'badge bg-success' : 'badge bg-secondary';
        }
        
        // Показываем уведомление
        const count = data.count || 0;
        showToast(`AI нашел ${count} результатов`, 'success');
        
    } catch (error) {
        console.error('AI Search error:', error);
        
        let errorMessage = error.message;
        if (errorMessage.includes('422')) {
            errorMessage = 'Ошибка валидации. Проверьте параметры поиска.';
        } else if (errorMessage.includes('500')) {
            errorMessage = 'Ошибка сервера. Попробуйте позже.';
        }
        
        resultsDiv.innerHTML = `
            <div class="text-center py-5">
                <i class="bi bi-exclamation-triangle display-1 text-danger mb-3"></i>
                <h4 class="text-danger mb-3">Ошибка AI-поиска</h4>
                <p class="text-muted">${errorMessage}</p>
                <button class="btn btn-primary mt-2" onclick="performAISearch()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Повторить
                </button>
            </div>
        `;
        
        showToast(`Ошибка: ${errorMessage}`, 'danger');
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
    
    if (!data || (!data.results && !data.ai_response)) {
        resultsDiv.innerHTML = `
            <div class="text-center py-5">
                <i class="bi bi-inbox display-1 text-muted mb-3"></i>
                <h4 class="text-muted mb-3">Нет результатов</h4>
                <p class="text-muted">Попробуйте изменить параметры поиска</p>
            </div>
        `;
        return;
    }

    const results = data.results || [];
    const count = data.count || results.length;
    const isGrouped = data.search_type === 'advanced' && document.getElementById('group_by_brand')?.checked;
    
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
    
    if (count === 0) {
        html += `
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
        
        resultsDiv.innerHTML = html;
        return;
    }
    
    // Результаты
    html += `<h5 class="mb-3">Найдено решений: <span class="badge bg-primary">${count}</span></h5>`;
    
    if (isGrouped && typeof results === 'object' && !Array.isArray(results)) {
        // Группированные результаты
        Object.values(results).forEach((group, groupIndex) => {
            html += createBrandGroup(group, groupIndex);
        });
    } else {
        // Обычные результаты
        results.forEach((item, index) => {
            html += createResultCard(item, index);
        });
    }
    
    resultsDiv.innerHTML = html;
    
    // Инициализация тултипов
    initTooltips();
}

// Создание группы по бренду
function createBrandGroup(group, groupIndex) {
    return `
        <div class="brand-group fade-in" style="animation-delay: ${groupIndex * 0.1}s">
            <div class="brand-group-header">
                <span>${group.brand}</span>
                <span class="brand-group-count">${group.count}</span>
            </div>
            <div class="p-3">
                ${group.results.map((item, index) => createResultCard(item, index)).join('')}
            </div>
        </div>
    `;
}

// Создание карточки результата
function createResultCard(item, index) {
    const relevancePercent = Math.min(100, Math.round((item.relevance_score || 0.5) * 100));
    const itemId = `result-${item.type}-${item.id}-${index}`;
    const isExpanded = expandedItems.has(itemId);
    
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
    
    // Определяем тип карточки
    let cardTypeClass = '';
    if (item.type === 'symptom' && !item.has_rules) {
        cardTypeClass = 'symptom-only';
    } else if (item.type === 'rule') {
        cardTypeClass = 'rule-match';
    }
    
    let html = `
        <div class="card result-card ${cardTypeClass} mb-3 fade-in" 
             style="animation-delay: ${index * 0.1}s"
             id="${itemId}">
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
                        
                        ${item.matched_keywords && item.matched_keywords.length > 0 ? `
                            <div class="matched-keywords small mt-2">
                                <span class="text-muted me-2">Совпадения:</span>
                                ${item.matched_keywords.map(keyword => 
                                    `<span class="keyword-tag">${keyword}</span>`
                                ).join('')}
                            </div>
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
        const steps = item.diagnostic_steps;
        const showSteps = 3;
        const shouldExpand = steps.length > showSteps;
        const isStepsExpanded = isExpanded || !shouldExpand;
        
        html += `
            <div class="mb-3">
                <small class="text-muted d-block mb-1">
                    <strong>Шаги диагностики:</strong>
                    <span class="text-muted ms-2">(${steps.length} шагов)</span>
                </small>
                <div class="expandable-list ${isStepsExpanded ? 'expanded' : ''}" id="steps-${itemId}">
                    <ul class="diagnostic-list small">
                        ${steps.map((step, i) => `<li>${i + 1}. ${step}</li>`).join('')}
                    </ul>
                </div>
                ${shouldExpand ? `
                    <button class="expand-btn ${isStepsExpanded ? 'expanded' : ''}" 
                            onclick="toggleExpand('steps-${itemId}', '${itemId}')">
                        <i class="bi bi-chevron-down"></i>
                        ${isStepsExpanded ? 'Свернуть шаги' : `Показать все ${steps.length} шагов`}
                    </button>
                ` : ''}
            </div>
        `;
    }
    
    // Возможные причины
    if (item.possible_causes && Array.isArray(item.possible_causes) && item.possible_causes.length > 0) {
        const causes = item.possible_causes;
        const showCauses = 3;
        const shouldExpand = causes.length > showCauses;
        const isCausesExpanded = isExpanded || !shouldExpand;
        
        html += `
            <div class="mb-3">
                <small class="text-muted d-block mb-1">
                    <strong>Возможные причины:</strong>
                    <span class="text-muted ms-2">(${causes.length} причин)</span>
                </small>
                <div class="expandable-list ${isCausesExpanded ? 'expanded' : ''}" id="causes-${itemId}">
                    <div class="causes-list">
                        ${causes.map(cause => `<span class="cause-badge">${cause}</span>`).join('')}
                    </div>
                </div>
                ${shouldExpand ? `
                    <button class="expand-btn ${isCausesExpanded ? 'expanded' : ''}" 
                            onclick="toggleExpand('causes-${itemId}', '${itemId}')">
                        <i class="bi bi-chevron-down"></i>
                        ${isCausesExpanded ? 'Свернуть причины' : `Показать все ${causes.length} причин`}
                    </button>
                ` : ''}
            </div>
        `;
    }
    
    // Требуемые данные
    if (item.required_data && Array.isArray(item.required_data) && item.required_data.length > 0) {
        const requiredData = item.required_data;
        html += `
            <div class="mb-3">
                <small class="text-muted d-block mb-1"><strong>Требуемые данные:</strong></small>
                <div class="d-flex flex-wrap gap-1">
                    ${requiredData.map(data => `<span class="badge bg-light text-dark">${data}</span>`).join('')}
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

// Форматирование AI ответа
function formatAIResponse(response) {
    return response
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
        .replace(/🔑/g, '<i class="bi bi-key text-primary"></i>')
        .replace(/📝/g, '<i class="bi bi-pencil text-info"></i>')
        .replace(/🏷️/g, '<i class="bi bi-tag text-success"></i>')
        .replace(/📌/g, '<i class="bi bi-pin-angle text-danger"></i>')
        .replace(/ℹ️/g, '<i class="bi bi-info-circle text-info"></i>')
        .replace(/\n/g, '<br>');
}

// Раскрытие/сворачивание списков
function toggleExpand(elementId, itemId) {
    const element = document.getElementById(elementId);
    const button = element?.nextElementSibling;
    
    if (!element || !button) return;
    
    element.classList.toggle('expanded');
    button.classList.toggle('expanded');
    
    const isNowExpanded = element.classList.contains('expanded');
    const itemText = elementId.includes('steps') ? 'шаги' : 'причины';
    
    if (isNowExpanded) {
        expandedItems.add(itemId);
        button.innerHTML = `<i class="bi bi-chevron-up"></i> Свернуть ${itemText}`;
    } else {
        expandedItems.delete(itemId);
        button.innerHTML = `<i class="bi bi-chevron-down"></i> ${button.textContent.includes('все') ? button.textContent : 'Показать все'}`;
    }
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
    
    // Сброс расширенных настроек
    document.getElementById('complexity').value = '';
    document.getElementById('max_results').value = '10';
    document.getElementById('only_with_rules').checked = false;
    document.getElementById('group_by_brand').checked = false;
    
    // Скрываем расширенные настройки
    document.getElementById('advancedOptions').classList.remove('show');
    document.getElementById('toggleAdvancedBtn').innerHTML = '<i class="bi bi-sliders me-1"></i>Больше настроек';
    
    showToast('Поиск очищен', 'info');
}

function setExample(text) {
    document.getElementById('query').value = text;
    document.getElementById('query').focus();
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
    
    let csv = 'Тип;Название;Описание;Марка;Модель;Шаги диагностики;Возможные причины;Требуемые данные;Сложность;Время (мин);Цена (руб);Релевантность (%)\n';
    
    currentResults.forEach(item => {
        const type = item.type === 'rule' ? 'Правило' : 'Симптом';
        const steps = Array.isArray(item.diagnostic_steps) ? 
            item.diagnostic_steps.join(' | ') : '';
        const causes = Array.isArray(item.possible_causes) ? 
            item.possible_causes.join(' | ') : '';
        const required = Array.isArray(item.required_data) ? 
            item.required_data.join(' | ') : '';
        const relevance = Math.round((item.relevance_score || 0) * 100);
        
        csv += `"${type}";"${item.title || ''}";"${item.description || ''}";"${item.brand || ''}";"${item.model || ''}";"${steps}";"${causes}";"${required}";${item.complexity_level || ''};${item.estimated_time || ''};${item.consultation_price || ''};${relevance}\n`;
    });
    
    // Создаем и скачиваем файл
    const blob = new Blob(["\uFEFF" + csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', `ai_diagnostic_results_${new Date().toISOString().slice(0,10)}.csv`);
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
    
    const query = document.getElementById('query').value;
    const brandId = document.getElementById('brand_id').value;
    const modelId = document.getElementById('model_id').value;
    
    let url = '/diagnostic/consultation/order?ai_search=true';
    
    if (query) {
        url += `&query=${encodeURIComponent(query)}`;
    }
    
    if (brandId) {
        url += `&brand_id=${brandId}`;
    }
    
    if (modelId) {
        url += `&model_id=${modelId}`;
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
            // Можно добавить отображение популярных симптомов
            console.log('Popular symptoms loaded:', data.symptoms.length);
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