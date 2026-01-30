@extends('layouts.app')

@section('title', 'AI поиск с запчастями и документами')

@push('styles')
<style>
    .enhanced-search-container {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
        min-height: calc(100vh - 150px);
    }
    
    @media (min-width: 1200px) {
        .enhanced-search-container {
            grid-template-columns: 400px 1fr;
        }
    }
    
    /* Стили для секций результатов */
    .results-section {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    
    .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .section-title {
        font-size: 1.25rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    /* Стили для списка запчастей */
    .parts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .part-card {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 1rem;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .part-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        border-color: #4CAF50;
    }
    
    .part-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.75rem;
    }
    
    .part-sku {
        background: #e3f2fd;
        color: #1565c0;
        padding: 0.25rem 0.75rem;
        border-radius: 4px;
        font-size: 0.85rem;
        font-family: monospace;
    }
    
    .part-price {
        font-size: 1.5rem;
        font-weight: bold;
        color: #2e7d32;
    }
    
    .part-name {
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #333;
    }
    
    .part-description {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 1rem;
        line-height: 1.4;
    }
    
    .part-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 0.75rem;
        border-top: 1px solid #f0f0f0;
    }
    
    .availability-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.85rem;
    }
    
    .availability-instock {
        background: #c8e6c9;
        color: #2e7d32;
    }
    
    .availability-low {
        background: #ffecb3;
        color: #f57c00;
    }
    
    .availability-out {
        background: #ffcdd2;
        color: #c62828;
    }
    
    /* Стили для документов */
    .documents-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .document-card {
        display: flex;
        align-items: flex-start;
        padding: 1rem;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .document-card:hover {
        background: #f8f9fa;
        border-color: #4CAF50;
        transform: translateX(5px);
    }
    
    .document-icon {
        font-size: 2rem;
        color: #666;
        margin-right: 1rem;
        min-width: 40px;
    }
    
    .document-info {
        flex: 1;
    }
    
    .document-title {
        font-weight: 600;
        margin-bottom: 0.25rem;
        color: #333;
    }
    
    .document-meta {
        display: flex;
        gap: 1rem;
        font-size: 0.85rem;
        color: #666;
        margin-top: 0.5rem;
    }
    
    /* Стили для инструкции по ремонту */
    .repair-guide {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
    }
    
    .guide-step {
        background: rgba(255,255,255,0.1);
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        backdrop-filter: blur(10px);
    }
    
    .guide-step:last-child {
        margin-bottom: 0;
    }
    
    .step-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }
    
    .step-title {
        font-weight: 600;
        font-size: 1.1rem;
    }
    
    .step-content ul {
        margin: 0;
        padding-left: 1.5rem;
    }
    
    .step-content li {
        margin-bottom: 0.5rem;
        padding-left: 0.5rem;
    }
    
    /* Анимации */
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
    
    /* Кастомные вкладки */
    .nav-tabs-custom .nav-link {
        border: none;
        border-bottom: 3px solid transparent;
        color: #666;
        font-weight: 500;
        padding: 0.75rem 1.5rem;
        transition: all 0.3s;
    }
    
    .nav-tabs-custom .nav-link:hover {
        color: #4CAF50;
        border-bottom-color: #ddd;
    }
    
    .nav-tabs-custom .nav-link.active {
        color: #4CAF50;
        border-bottom-color: #4CAF50;
        background: none;
    }
    
    /* Кнопки действий */
    .action-buttons {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
    }
    
    .btn-order {
        background: linear-gradient(135deg, #4CAF50 0%, #2e7d32 100%);
        color: white;
        border: none;
        padding: 0.5rem 1.5rem;
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.3s;
    }
    
    .btn-order:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
    }
</style>
@endpush

@section('content')
<div class="enhanced-search-container">
    <!-- Левая колонка - форма поиска -->
    <div>
        <div class="card shadow-lg sticky-top" style="top: 1rem;">
            <div class="card-header bg-primary text-white d-flex align-items-center">
                <i class="bi bi-robot fs-4 me-2"></i>
                <h4 class="mb-0">🤖 AI Поиск с запчастями</h4>
            </div>
            
            <div class="card-body position-relative">
                <form id="enhancedSearchForm">
                    @csrf
                    
                    <!-- Быстрая статистика -->
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="bg-light p-2 rounded text-center">
                                <small class="text-muted d-block">Запчастей</small>
                                <strong class="text-primary" id="partsCount">{{ $stats['price_items_count'] }}</strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light p-2 rounded text-center">
                                <small class="text-muted d-block">Документов</small>
                                <strong class="text-primary" id="docsCount">{{ $stats['documents_count'] }}</strong>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Описание проблемы -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Опишите проблему</label>
                        <textarea class="form-control" 
                                  id="query" 
                                  name="query" 
                                  rows="4"
                                  placeholder="Пример: Не заводится двигатель, слышен щелчок при повороте ключа"
                                  required></textarea>
                    </div>
                    
                    <!-- Фильтры -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Марка авто</label>
                            <select class="form-select" id="brand_id" name="brand_id">
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
                        <div class="col-md-6">
                            <label class="form-label">Модель</label>
                            <select class="form-select" id="model_id" name="model_id" disabled>
                                <option value="">Сначала выберите марку</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Дополнительные опции -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Что искать?</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="show_parts" name="show_parts" checked>
                            <label class="form-check-label" for="show_parts">
                                Показывать запчасти
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="show_docs" name="show_docs" checked>
                            <label class="form-check-label" for="show_docs">
                                Показывать документы
                            </label>
                        </div>
                    </div>
                    
                    <!-- Кнопка поиска -->
                    <button type="submit" class="btn btn-primary w-100" id="searchBtn">
                        <i class="bi bi-search me-2"></i>
                        <span>Начать AI-поиск</span>
                        <span class="spinner-border spinner-border-sm ms-2 d-none" id="searchSpinner"></span>
                    </button>
                </form>
                
                <!-- Индикатор загрузки -->
                <div class="loading-overlay d-none" id="loadingOverlay">
                    <div class="text-center text-white">
                        <div class="spinner-border mb-3"></div>
                        <p class="mb-0">AI анализирует проблему...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Правая колонка - результаты -->
    <div>
        <!-- Вкладки -->
        <ul class="nav nav-tabs nav-tabs-custom mb-3" id="resultsTabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#tab-results">
                    <i class="bi bi-search me-1"></i> Результаты
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab-parts">
                    <i class="bi bi-tools me-1"></i> Запчасти
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab-docs">
                    <i class="bi bi-files me-1"></i> Документы
                </a>
            </li>
        </ul>
        
        <!-- Контент вкладок -->
        <div class="tab-content" id="resultsContent">
            <!-- Вкладка результатов -->
            <div class="tab-pane fade show active" id="tab-results">
                <div id="aiResponse" class="results-section fade-in-up">
                    <div class="section-header">
                        <h5 class="section-title">
                            <i class="bi bi-robot"></i> AI-анализ
                        </h5>
                        <span class="badge bg-secondary" id="resultsCount">Ожидание запроса</span>
                    </div>
                    <div id="aiResponseContent" class="ai-response-content">
                        <div class="text-center py-5">
                            <i class="bi bi-robot display-1 text-muted mb-3"></i>
                            <h4 class="text-muted">AI-помощник по диагностике</h4>
                            <p class="text-muted">Опишите проблему для получения анализа</p>
                        </div>
                    </div>
                </div>
                
                <div id="symptomsResults" class="results-section d-none">
                    <!-- Сюда будут добавляться результаты -->
                </div>
            </div>
            
            <!-- Вкладка запчастей -->
            <div class="tab-pane fade" id="tab-parts">
                <div id="partsResults" class="results-section">
                    <div class="section-header">
                        <h5 class="section-title">
                            <i class="bi bi-tools"></i> Рекомендуемые запчасти
                        </h5>
                        <span class="badge bg-success" id="partsCountBadge">0</span>
                    </div>
                    <div id="partsContent">
                        <div class="text-center py-5">
                            <i class="bi bi-tools display-1 text-muted mb-3"></i>
                            <p class="text-muted">Выполните поиск для просмотра запчастей</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Вкладка документов -->
            <div class="tab-pane fade" id="tab-docs">
                <div id="docsResults" class="results-section">
                    <div class="section-header">
                        <h5 class="section-title">
                            <i class="bi bi-files"></i> Инструкции и документы
                        </h5>
                        <span class="badge bg-info" id="docsCountBadge">0</span>
                    </div>
                    <div id="docsContent">
                        <div class="text-center py-5">
                            <i class="bi bi-files display-1 text-muted mb-3"></i>
                            <p class="text-muted">Выполните поиск для просмотра документов</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно деталей -->
<div class="modal fade" id="ruleDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Детали диагностики</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="ruleDetailsContent">
                <!-- Контент будет загружаться динамически -->
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Глобальные переменные
let allModels = @json($models);
let currentResults = null;

document.addEventListener('DOMContentLoaded', function() {
    initForm();
    initEventListeners();
});

function initForm() {
    const brandSelect = document.getElementById('brand_id');
    const modelSelect = document.getElementById('model_id');
    
    if (brandSelect) {
        brandSelect.addEventListener('change', function() {
            const brandId = this.value;
            updateModelSelect(brandId);
        });
    }
}

function initEventListeners() {
    const form = document.getElementById('enhancedSearchForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            performEnhancedSearch();
        });
    }
}

function updateModelSelect(brandId) {
    const modelSelect = document.getElementById('model_id');
    const models = allModels[brandId] || [];
    
    modelSelect.innerHTML = '<option value="">Все модели</option>';
    
    if (models.length > 0) {
        models.forEach(model => {
            const displayName = model.name || model.name_cyrillic;
            let yearInfo = '';
            
            if (model.year_from) {
                yearInfo = ` (${model.year_from}`;
                if (model.year_to && model.year_to !== model.year_from) {
                    yearInfo += `-${model.year_to}`;
                }
                yearInfo += ')';
            }
            
            modelSelect.innerHTML += `<option value="${model.id}">${displayName}${yearInfo}</option>`;
        });
        modelSelect.disabled = false;
    } else {
        modelSelect.innerHTML = '<option value="">Нет доступных моделей</option>';
        modelSelect.disabled = true;
    }
}

async function performEnhancedSearch() {
    const form = document.getElementById('enhancedSearchForm');
    const searchBtn = document.getElementById('searchBtn');
    const searchSpinner = document.getElementById('searchSpinner');
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    const formData = new FormData(form);
    const query = formData.get('query');
    
    if (!query || query.trim().length < 3) {
        showToast('Введите описание проблемы (минимум 3 символа)', 'warning');
        return;
    }
    
    // Показываем загрузку
    searchBtn.disabled = true;
    searchSpinner.classList.remove('d-none');
    loadingOverlay.classList.remove('d-none');
    
    try {
        const response = await fetch('{{ route("diagnostic.ai.enhanced.search") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                query: formData.get('query'),
                brand_id: formData.get('brand_id') || null,
                model_id: formData.get('model_id') || null,
                search_type: 'full',
                show_parts: document.getElementById('show_parts').checked,
                show_docs: document.getElementById('show_docs').checked,
                max_results: 15
            })
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Ошибка сервера');
        }
        
        if (!data.success) {
            throw new Error(data.message || 'Ошибка поиска');
        }
        
        currentResults = data;
        displayEnhancedResults(data);
        
    } catch (error) {
        console.error('Search error:', error);
        showToast('Ошибка поиска: ' + error.message, 'danger');
    } finally {
        searchBtn.disabled = false;
        searchSpinner.classList.add('d-none');
        loadingOverlay.classList.add('d-none');
    }
}

function displayEnhancedResults(data) {
    // Обновляем AI ответ
    updateAIResponse(data.ai_response);
    
    // Обновляем счетчики
    updateCounters(data);
    
    // Отображаем симптомы и правила
    displaySymptoms(data.results);
    
    // Отображаем запчасти если есть
    if (data.parts && data.parts.length > 0) {
        displayParts(data.parts);
    }
    
    // Отображаем документы если есть
    if (data.documents && data.documents.length > 0) {
        displayDocuments(data.documents);
    }
    
    // Показываем уведомление
    const total = (data.results?.length || 0) + (data.parts?.length || 0) + (data.documents?.length || 0);
    showToast(`Найдено ${total} результатов за ${data.execution_time}мс`, 'success');
}

function updateAIResponse(response) {
    const aiContent = document.getElementById('aiResponseContent');
    if (!aiContent) return;
    
    // Форматируем AI ответ
    const formattedResponse = response
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\n/g, '<br>')
        .replace(/🤖/g, '<i class="bi bi-robot"></i>')
        .replace(/🔍/g, '<i class="bi bi-search"></i>')
        .replace(/🎯/g, '<i class="bi bi-bullseye"></i>')
        .replace(/🛒/g, '<i class="bi bi-cart"></i>')
        .replace(/📄/g, '<i class="bi bi-file-earmark-text"></i>')
        .replace(/🔧/g, '<i class="bi bi-tools"></i>')
        .replace(/⚠️/g, '<i class="bi bi-exclamation-triangle"></i>')
        .replace(/⏱️/g, '<i class="bi bi-clock"></i>')
        .replace(/💰/g, '<i class="bi bi-currency-ruble"></i>')
        .replace(/📦/g, '<i class="bi bi-box"></i>')
        .replace(/🏷️/g, '<i class="bi bi-tag"></i>')
        .replace(/📈/g, '<i class="bi bi-graph-up"></i>')
        .replace(/💡/g, '<i class="bi bi-lightbulb"></i>');
    
    aiContent.innerHTML = `
        <div class="ai-response-content bg-light p-3 rounded">
            ${formattedResponse}
        </div>
    `;
}

function updateCounters(data) {
    // Обновляем счетчик результатов
    const resultsCount = document.getElementById('resultsCount');
    if (resultsCount) {
        const count = data.results?.length || 0;
        resultsCount.textContent = count + ' найдено';
        resultsCount.className = count > 0 ? 'badge bg-success' : 'badge bg-secondary';
    }
    
    // Обновляем счетчик запчастей
    const partsBadge = document.getElementById('partsCountBadge');
    if (partsBadge && data.parts) {
        partsBadge.textContent = data.parts.length;
        partsBadge.className = data.parts.length > 0 ? 'badge bg-success' : 'badge bg-secondary';
    }
    
    // Обновляем счетчик документов
    const docsBadge = document.getElementById('docsCountBadge');
    if (docsBadge && data.documents) {
        docsBadge.textContent = data.documents.length;
        docsBadge.className = data.documents.length > 0 ? 'badge bg-success' : 'badge bg-secondary';
    }
}

function displaySymptoms(symptoms) {
    const container = document.getElementById('symptomsResults');
    if (!container) return;
    
    if (!symptoms || symptoms.length === 0) {
        container.classList.add('d-none');
        return;
    }
    
    let html = `
        <div class="section-header">
            <h5 class="section-title">
                <i class="bi bi-clipboard-check"></i> Симптомы и решения
            </h5>
            <span class="badge bg-primary">${symptoms.length}</span>
        </div>
    `;
    
    symptoms.forEach((item, index) => {
        const relevancePercent = Math.round((item.relevance_score || 0.5) * 100);
        const badgeClass = relevancePercent > 70 ? 'bg-success' : 
                          relevancePercent > 40 ? 'bg-warning' : 'bg-secondary';
        
        html += `
            <div class="card mb-3 fade-in-up" style="animation-delay: ${index * 0.1}s">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="card-title mb-0">
                            ${item.type === 'rule' ? 
                                '<i class="bi bi-clipboard-check text-success me-2"></i>' : 
                                '<i class="bi bi-exclamation-triangle text-warning me-2"></i>'}
                            ${item.title}
                        </h6>
                        <span class="badge ${badgeClass}">
                            ${relevancePercent}%
                        </span>
                    </div>
                    
                    ${item.description ? `
                        <p class="card-text text-muted small mb-3">
                            ${item.description.substring(0, 150)}${item.description.length > 150 ? '...' : ''}
                        </p>
                    ` : ''}
                    
                    ${item.brand || item.model ? `
                        <div class="mb-3">
                            ${item.brand ? `
                                <span class="badge bg-info me-2">
                                    <i class="bi bi-car-front"></i> ${item.brand}
                                </span>
                            ` : ''}
                            ${item.model ? `
                                <span class="badge bg-secondary me-2">
                                    ${item.model}
                                </span>
                            ` : ''}
                        </div>
                    ` : ''}
                    
                    ${item.possible_causes && item.possible_causes.length > 0 ? `
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">
                                <strong>Возможные причины:</strong>
                            </small>
                            <div class="d-flex flex-wrap gap-1">
                                ${item.possible_causes.slice(0, 3).map(cause => 
                                    `<span class="badge bg-light text-dark">${cause}</span>`
                                ).join('')}
                            </div>
                        </div>
                    ` : ''}
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            ${item.type === 'rule' ? `
                                <i class="bi bi-clock"></i> ~${item.estimated_time} мин. | 
                                <i class="bi bi-currency-ruble"></i> ${item.consultation_price?.toLocaleString() || '0'}
                            ` : 'Требуется дополнительная диагностика'}
                        </small>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewRuleDetails(${item.type === 'rule' ? item.id : item.symptom_id})">
                                <i class="bi bi-eye"></i> Подробнее
                            </button>
                            ${item.type === 'rule' ? `
                                <button class="btn btn-sm btn-success" 
                                        onclick="orderConsultation(${item.id})">
                                    <i class="bi bi-chat-dots"></i> Консультация
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    container.classList.remove('d-none');
}

function displayParts(parts) {
    const container = document.getElementById('partsContent');
    if (!container) return;
    
    let html = '';
    
    if (parts.length === 0) {
        html = '<div class="text-center py-3 text-muted">Запчасти не найдены</div>';
    } else {
        parts.forEach((part, index) => {
            const availabilityClass = part.availability === 'В наличии' ? 'availability-instock' :
                                    part.availability === 'Мало' ? 'availability-low' : 'availability-out';
            
            html += `
                <div class="part-card fade-in-up" style="animation-delay: ${index * 0.1}s">
                    <div class="part-header">
                        <span class="part-sku">${part.sku}</span>
                        <div class="part-price">${part.formatted_price} ₽</div>
                    </div>
                    
                    <div class="part-name">${part.name}</div>
                    
                    ${part.description ? `
                        <div class="part-description">
                            ${part.description.substring(0, 100)}${part.description.length > 100 ? '...' : ''}
                        </div>
                    ` : ''}
                    
                    <div class="part-meta">
                        <div>
                            ${part.brand ? `
                                <span class="badge bg-light text-dark me-2">
                                    ${part.brand}
                                </span>
                            ` : ''}
                            <span class="availability-badge ${availabilityClass}">
                                ${part.availability}
                            </span>
                        </div>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-outline-primary" onclick="viewPartDetails(${part.id})">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-success" onclick="addToCart(${part.id})">
                                <i class="bi bi-cart-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
    }
    
    container.innerHTML = html;
}

function displayDocuments(documents) {
    const container = document.getElementById('docsContent');
    if (!container) return;
    
    let html = '';
    
    if (documents.length === 0) {
        html = '<div class="text-center py-3 text-muted">Документы не найдены</div>';
    } else {
        documents.forEach((doc, index) => {
            html += `
                <div class="document-card fade-in-up" style="animation-delay: ${index * 0.1}s"
                     onclick="openDocument(${doc.id})">
                    <div class="document-icon">
                        <i class="bi ${doc.icon}"></i>
                    </div>
                    <div class="document-info">
                        <div class="document-title">${doc.title}</div>
                        ${doc.excerpt ? `
                            <div class="text-muted small mb-2">
                                ${doc.excerpt}
                            </div>
                        ` : ''}
                        <div class="document-meta">
                            <span><i class="bi bi-file-earmark"></i> ${doc.file_type}</span>
                            ${doc.total_pages ? `<span><i class="bi bi-file-text"></i> ${doc.total_pages} стр.</span>` : ''}
                            ${doc.views ? `<span><i class="bi bi-eye"></i> ${doc.views}</span>` : ''}
                        </div>
                    </div>
                </div>
            `;
        });
    }
    
    container.innerHTML = html;
}

// Вспомогательные функции
function viewRuleDetails(ruleId) {
    window.open(`/diagnostic/rules/${ruleId}/with-parts`, '_blank');
}

function orderConsultation(ruleId) {
    window.location.href = `/diagnostic/consultation/order?rule_id=${ruleId}`;
}

function viewPartDetails(partId) {
    window.open(`/price-items/${partId}`, '_blank');
}

function addToCart(partId) {
    // Реализация добавления в корзину
    showToast('Запчасть добавлена в корзину', 'success');
}

function openDocument(docId) {
    window.open(`/documents/${docId}`, '_blank');
}

function showToast(message, type = 'info') {
    // Используем существующий механизм нотаций или создаем простой
    alert(message); // Временная реализация
}
</script>
@endpush