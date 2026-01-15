@extends('layouts.app')

@section('title', 'Умный поиск документации')

@push('styles')
<style>
    /* Мобильные стили для поиска */
    @media (max-width: 768px) {
        .search-container {
            padding: 0;
        }
        
        .search-form-card {
            position: sticky;
            top: var(--header-height);
            z-index: 100;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .results-container {
            margin-top: 1rem;
        }
        
        .filter-row {
            flex-direction: column;
        }
        
        .filter-row .col-md-6 {
            width: 100%;
            margin-bottom: 1rem;
        }
    }
    
    /* Десктоп стили */
    @media (min-width: 769px) {
        .search-container {
            display: flex;
            gap: 1.5rem;
            min-height: calc(100vh - 200px);
        }
        
        .search-form-card {
            flex: 0 0 400px;
            max-height: 600px;
            position: sticky;
            top: 1rem;
        }
        
        .results-container {
            flex: 1;
            min-height: 500px;
        }
    }
    
    /* Общие стили поиска */
    .search-input {
        resize: vertical;
        min-height: 100px;
    }
    
    .search-btn {
        height: 50px;
        font-size: 1.1rem;
        font-weight: 600;
    }
    
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
        z-index: 10;
        border-radius: 12px;
    }
    
    .result-item {
        border-left: 4px solid var(--primary-color);
        transition: all 0.3s ease;
    }
    
    .result-item:hover {
        border-left-color: #0056b3;
        background: #f8f9fa;
    }
    
    .model-year {
        font-size: 0.85rem;
        color: #6c757d;
    }
    
    /* Анимации */
    .pulse {
        animation: pulse 1.5s infinite;
    }
    
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
    }
</style>
@endpush

@section('content')
<div class="search-container">
    <!-- Левая колонка - Форма поиска -->
    <div class="search-form-card card">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center">
                <i class="bi bi-search me-2"></i>
                <h5 class="mb-0">🔍 Умный поиск</h5>
            </div>
        </div>
        
        <div class="card-body position-relative">
            <form id="searchForm" novalidate>
                @csrf
                
                <!-- Отладочная информация (только для разработки) -->
                @if(env('APP_DEBUG'))
                <div class="alert alert-info small mb-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong>Отладка:</strong><br>
                            Марок: {{ count($brands) }}<br>
                            Групп: {{ count($models) }}
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-info" 
                                onclick="debugModels()">
                            <i class="bi bi-bug"></i>
                        </button>
                    </div>
                </div>
                @endif
                
                <!-- Описание проблемы -->
                <div class="mb-4">
                    <label for="query" class="form-label">
                        <i class="bi bi-chat-text me-1"></i>Опишите проблему
                    </label>
                    <textarea class="form-control search-input" 
                              id="query" 
                              name="query" 
                              placeholder="Например: 
• Автомобиль не заводится
• Стучит в двигателе
• Проблемы с тормозами
• Загорается Check Engine"
                              rows="4"
                              required></textarea>
                    <div class="form-text mt-1">
                        Опишите максимально подробно, это улучшит поиск
                    </div>
                </div>

                <!-- Фильтры -->
                <div class="filter-row row mb-4">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="brand_id" class="form-label">
                            <i class="bi bi-car-front me-1"></i>Марка
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-tag"></i>
                            </span>
                            <select name="brand_id" 
                                    id="brand_id" 
                                    class="form-select form-select-lg"
                                    aria-label="Выберите марку">
                                <option value="">Все марки</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}" 
                                            data-name="{{ $brand->name }}">
                                        {{ $brand->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="car_model_id" class="form-label">
                            <i class="bi bi-card-checklist me-1"></i>Модель
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-car-front"></i>
                            </span>
                            <select name="car_model_id" 
                                    id="car_model_id" 
                                    class="form-select form-select-lg"
                                    disabled
                                    aria-label="Выберите модель">
                                <option value="">Сначала выберите марку</option>
                            </select>
                            <span class="input-group-text bg-light">
                                <div id="modelSpinner" class="spinner-border spinner-border-sm text-primary d-none" 
                                     role="status">
                                    <span class="visually-hidden">Загрузка...</span>
                                </div>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Кнопка поиска -->
                <button type="submit" 
                        class="btn btn-primary btn-lg w-100 search-btn" 
                        id="searchBtn">
                    <span class="d-flex align-items-center justify-content-center">
                        <span id="searchText">Найти решение</span>
                        <span id="searchSpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
                    </span>
                </button>
                
                <!-- Быстрые действия -->
                <div class="mt-3 text-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary me-2" 
                            onclick="clearFilters()">
                        <i class="bi bi-x-circle me-1"></i>Очистить
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info" 
                            onclick="showAdvanced()">
                        <i class="bi bi-sliders me-1"></i>Расширенный
                    </button>
                </div>
            </form>
            
            <!-- Индикатор загрузки -->
            <div id="formLoading" class="loading-overlay d-none">
                <div class="text-center">
                    <div class="spinner-border text-primary mb-2"></div>
                    <p>Загружаем модели...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Правая колонка - Результаты -->
    <div class="results-container">
        <div class="card h-100">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-file-text me-2"></i>📄 Результаты поиска
                    </h5>
                    <span id="resultsCount" class="badge bg-secondary">0</span>
                </div>
            </div>
            
            <div class="card-body">
                <div id="searchResults" class="h-100">
                    <!-- Начальное состояние -->
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="bi bi-search display-1 text-muted"></i>
                        </div>
                        <h4 class="text-muted mb-3">Готов к поиску</h4>
                        <p class="text-muted">
                            Опишите проблему и нажмите "Найти решение"<br>
                            Система проанализирует базу документации
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="card-footer d-none" id="resultsFooter">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted" id="searchStats"></small>
                    <button class="btn btn-sm btn-outline-primary" id="loadMoreBtn">
                        <i class="bi bi-arrow-clockwise me-1"></i>Загрузить еще
                    </button>
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
let currentSearchParams = {};
let currentPage = 1;
let isLoading = false;

document.addEventListener('DOMContentLoaded', function() {
    console.log('Search page loaded');
    console.log('Models data structure:', Object.keys(allModels).length, 'brands');
    
    // ==================== ИНИЦИАЛИЗАЦИЯ ====================
    const brandSelect = document.getElementById('brand_id');
    const modelSelect = document.getElementById('car_model_id');
    
    // Предзагрузка данных о моделях
    if (Object.keys(allModels).length > 0) {
        console.log('Models preloaded successfully');
    }
    
    // ==================== ВЫБОР МАРКИ ====================
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
    
    // ==================== ПОИСК ====================
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            await performSearch();
        });
    }
    
    // ==================== СОБЫТИЯ КЛАВИАТУРЫ ====================
    document.getElementById('query')?.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('searchBtn').click();
        }
    });
});

// ==================== ФУНКЦИИ ====================

// Загрузка моделей для выбранной марки
function loadModelsForBrand(brandId) {
    const modelSelect = document.getElementById('car_model_id');
    const modelSpinner = document.getElementById('modelSpinner');
    const formLoading = document.getElementById('formLoading');
    
    // Показываем индикаторы
    if (modelSpinner) modelSpinner.classList.remove('d-none');
    if (formLoading) formLoading.classList.remove('d-none');
    modelSelect.innerHTML = '<option value="">Загрузка моделей...</option>';
    modelSelect.disabled = true;
    
    // Используем предзагруженные данные
    setTimeout(() => {
        const models = allModels[brandId];
        console.log('Models for brand', brandId, ':', models);
        
        if (!models || models.length === 0) {
            // Пробуем загрузить через AJAX
            fetchModelsFromServer(brandId);
            return;
        }
        
        populateModelSelect(models);
        
        // Скрываем индикаторы
        if (modelSpinner) modelSpinner.classList.add('d-none');
        if (formLoading) formLoading.classList.add('d-none');
        modelSelect.disabled = false;
    }, 300);
}

// Загрузка моделей с сервера (резервный метод)
function fetchModelsFromServer(brandId) {
    fetch(`/admin/search/models/${brandId}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        console.log('Models from server:', data);
        if (data.success && data.models) {
            populateModelSelect(data.models);
        } else {
            showNoModels();
        }
    })
    .catch(error => {
        console.error('Error fetching models:', error);
        showNoModels();
    })
    .finally(() => {
        const modelSpinner = document.getElementById('modelSpinner');
        const formLoading = document.getElementById('formLoading');
        
        if (modelSpinner) modelSpinner.classList.add('d-none');
        if (formLoading) formLoading.classList.add('d-none');
    });
}

// Заполнение селекта моделей
function populateModelSelect(models) {
    const modelSelect = document.getElementById('car_model_id');
    
    if (!Array.isArray(models) || models.length === 0) {
        showNoModels();
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
    
    // Анимация появления
    modelSelect.style.opacity = '0';
    setTimeout(() => {
        modelSelect.style.transition = 'opacity 0.3s';
        modelSelect.style.opacity = '1';
    }, 10);
    
    console.log('Models loaded:', models.length);
}

// Показать сообщение "Нет моделей"
function showNoModels() {
    const modelSelect = document.getElementById('car_model_id');
    modelSelect.innerHTML = '<option value="">Нет доступных моделей</option>';
    modelSelect.disabled = true;
    
    // Показать уведомление
    showToast('Для выбранной марки нет моделей в базе', 'warning');
}

// Сброс выбора модели
function resetModelSelect() {
    const modelSelect = document.getElementById('car_model_id');
    modelSelect.innerHTML = '<option value="">Сначала выберите марку</option>';
    modelSelect.disabled = true;
}

// Выполнение поиска
async function performSearch() {
    if (isLoading) return;
    
    const queryInput = document.getElementById('query');
    const searchBtn = document.getElementById('searchBtn');
    const searchText = document.getElementById('searchText');
    const searchSpinner = document.getElementById('searchSpinner');
    const resultsDiv = document.getElementById('searchResults');
    const resultsCount = document.getElementById('resultsCount');
    const resultsFooter = document.getElementById('resultsFooter');
    const searchStats = document.getElementById('searchStats');
    
    // Валидация
    if (!queryInput.value.trim()) {
        showToast('Введите описание проблемы', 'warning');
        queryInput.focus();
        return;
    }
    
    // Настройка UI
    isLoading = true;
    searchBtn.disabled = true;
    searchText.textContent = 'Ищем...';
    searchSpinner.classList.remove('d-none');
    
    // Сохраняем параметры
    currentSearchParams = {
        query: queryInput.value,
        brand_id: document.getElementById('brand_id').value,
        car_model_id: document.getElementById('car_model_id').value,
        page: 1
    };
    
    // Показываем состояние загрузки
    resultsDiv.innerHTML = `
        <div class="spinner-container">
            <div class="text-center">
                <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
                <h5 class="text-muted">Ищем решения...</h5>
                <p class="text-muted small">Анализируем базу документации</p>
            </div>
        </div>
    `;
    
    resultsFooter.classList.add('d-none');
    
    try {
        const response = await fetch('/admin/search', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(currentSearchParams)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Search response:', data);
        
        if (data.success) {
            displayResults(data);
            
            // Обновляем счетчик
            const count = data.count || (Array.isArray(data.results) ? data.results.length : 0);
            resultsCount.textContent = count;
            resultsCount.className = count > 0 ? 'badge bg-success' : 'badge bg-secondary';
            
            // Обновляем статистику
            if (searchStats && data.search_type) {
                searchStats.textContent = `Найдено за ${data.execution_time || '0'} сек. (${data.search_type})`;
            }
            
            // Показываем футер если есть результаты
            if (count > 0) {
                resultsFooter.classList.remove('d-none');
            }
            
            // Показываем уведомление
            showToast(`Найдено ${count} документов`, 'success');
        } else {
            throw new Error(data.message || 'Ошибка поиска');
        }
    } catch (error) {
        console.error('Search error:', error);
        
        resultsDiv.innerHTML = `
            <div class="text-center py-5">
                <i class="bi bi-exclamation-triangle display-1 text-danger mb-3"></i>
                <h4 class="text-danger mb-3">Ошибка поиска</h4>
                <p class="text-muted">${error.message}</p>
                <button class="btn btn-primary mt-2" onclick="performSearch()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Повторить
                </button>
            </div>
        `;
        
        showToast(`Ошибка: ${error.message}`, 'danger');
    } finally {
        // Восстанавливаем UI
        isLoading = false;
        searchBtn.disabled = false;
        searchText.textContent = 'Найти решение';
        searchSpinner.classList.add('d-none');
    }
}

// Отображение результатов
function displayResults(data) {
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
                    Попробуйте:<br>
                    • Изменить формулировку запроса<br>
                    • Убрать фильтры марки/модели<br>
                    • Использовать другие ключевые слова
                </p>
            </div>
        `;
        return;
    }
    
    let html = '';
    
    results.forEach((doc, index) => {
        const relevanceScore = doc.relevance_score || doc.semantic_similarity || 0;
        const relevancePercent = Math.min(100, Math.round(relevanceScore * 100));
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
        } else {
            relevanceColor = 'secondary';
            relevanceIcon = 'bi-circle';
        }
        
        const carInfo = doc.car_model ? 
            `${doc.car_model.brand?.name || ''} ${doc.car_model.name || ''}`.trim() : 
            'Все модели';
        
        const previewText = doc.content_text ? 
            doc.content_text.substring(0, 200) + (doc.content_text.length > 200 ? '...' : '') : 
            'Описание недоступно';
        
        const date = doc.created_at ? 
            new Date(doc.created_at).toLocaleDateString('ru-RU', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }) : '';
        
        html += `
            <div class="card result-item mb-3" onclick="viewDocument(${doc.id})" 
                 style="cursor: pointer;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="card-title mb-0 flex-grow-1">
                            ${doc.title || 'Документ без названия'}
                        </h6>
                        <span class="badge relevance-badge bg-${relevanceColor} ms-2">
                            <i class="bi ${relevanceIcon} me-1"></i>
                            ${relevancePercent}%
                        </span>
                    </div>
                    
                    <div class="mb-2">
                        <span class="badge bg-light text-dark me-2">
                            <i class="bi bi-car-front me-1"></i>
                            ${carInfo || 'Все модели'}
                        </span>
                        ${doc.category ? `
                            <span class="badge bg-info">
                                <i class="bi bi-tag me-1"></i>
                                ${doc.category.name}
                            </span>
                        ` : ''}
                    </div>
                    
                    <p class="card-text text-muted mb-3">
                        ${previewText}
                    </p>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="bi bi-calendar me-1"></i>
                            ${date}
                        </small>
                        <button class="btn btn-sm btn-outline-primary" 
                                onclick="event.stopPropagation(); viewDocument(${doc.id})">
                            <i class="bi bi-eye me-1"></i>
                            Подробнее
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    resultsDiv.innerHTML = html;
}

// ==================== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ====================

// Показать тост
function showToast(message, type = 'info') {
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0" 
             role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                        data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    // Создаем контейнер для тостов если его нет
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
    
    // Удаляем после скрытия
    toastElement.addEventListener('hidden.bs.toast', function () {
        this.remove();
    });
}

// Просмотр документа
function viewDocument(id) {
    window.open(`/admin/documents/${id}`, '_blank');
}

// Очистить фильтры
function clearFilters() {
    document.getElementById('query').value = '';
    document.getElementById('brand_id').value = '';
    resetModelSelect();
    
    showToast('Фильтры очищены', 'info');
}

// Показать расширенный поиск
function showAdvanced() {
    showToast('Расширенный поиск в разработке', 'info');
}

// Отладка моделей
function debugModels() {
    console.log('All models:', allModels);
    
    let debugInfo = 'Доступные марки и модели:\n';
    Object.keys(allModels).forEach(brandId => {
        debugInfo += `\nМарка ID ${brandId}:\n`;
        allModels[brandId].forEach(model => {
            debugInfo += `  - ${model.name} (ID: ${model.id})\n`;
        });
    });
    
    alert(debugInfo);
}

// Загрузка дополнительных результатов
document.getElementById('loadMoreBtn')?.addEventListener('click', function() {
    // Реализация пагинации
    currentPage++;
    loadMoreResults();
});

async function loadMoreResults() {
    // Реализация загрузки дополнительных результатов
    showToast('Функция загрузки еще в разработке', 'info');
}
</script>

<!-- Инициализация отладочной информации -->
@if(env('APP_DEBUG'))
<script>
console.log('=== DEBUG INFO ===');
console.log('Brands count:', {{ count($brands) }});
console.log('Models groups count:', {{ count($models) }});

@if(count($models) > 0)
    @php
        $firstKey = array_key_first($models->toArray());
        $firstGroup = $models[$firstKey];
        $firstBrand = $brands->where('id', $firstKey)->first();
    @endphp
    console.log('First brand:', '{{ $firstBrand ? addslashes($firstBrand->name) : "Unknown" }}', 'ID:', {{ $firstKey }});
    console.log('Models in first brand:', @json($firstGroup));
@endif
</script>
@endif
@endpush