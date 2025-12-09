@extends('layouts.app')

@section('title', 'Семантический поиск')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">🧠 Семантический поиск</h5>
                </div>
                <div class="card-body">
                    <form id="semanticSearchForm">
                        <div class="mb-3">
                            <label for="query" class="form-label">Опишите проблему естественным языком</label>
                            <textarea class="form-control" id="query" name="query" 
                                      rows="4" placeholder="Например: машина плохо заводится по утрам, слышен стук в передней подвеске..."
                                      required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="brand_id" class="form-label">Бренд</label>
                                    <select class="form-select" id="brand_id" name="brand_id">
                                        <option value="">Все бренды</option>
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand->id }}">
                                                {{ $brand->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="car_model_id" class="form-label">Модель</label>
                                    <select class="form-select" id="car_model_id" name="car_model_id" disabled>
                                        <option value="">Все модели</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="searchBtn">
                            <span class="spinner-border spinner-border-sm d-none" id="searchSpinner"></span>
                            🧠 Найти семантические совпадения
                        </button>
                    </form>
                </div>
            </div>

            <div class="card mt-3 d-none" id="analysisCard">
                <div class="card-header">
                    <h6 class="mb-0">📊 Анализ семантики</h6>
                </div>
                <div class="card-body">
                    <div id="queryAnalysis"></div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">🎯 Семантические результаты</h5>
                    <div>
                        <span class="badge bg-info me-2 d-none" id="searchType">Семантический поиск</span>
                        <span class="badge bg-primary d-none" id="resultsCount">0 найдено</span>
                    </div>
                </div>
                <div class="card-body">
                    <div id="searchResults">
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-brain fa-3x mb-3"></i>
                            <h5>Семантический поиск</h5>
                            <p>Опишите проблему естественным языком для поиска релевантных решений</p>
                            <small class="text-muted">Поиск учитывает смысл запроса, а не только ключевые слова</small>
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
// Загрузка моделей
document.getElementById('brand_id').addEventListener('change', function() {
    const brandId = this.value;
    const modelSelect = document.getElementById('car_model_id');
    
    if (brandId) {
        modelSelect.disabled = false;
        modelSelect.innerHTML = '<option value="">Загрузка моделей...</option>';
        
        fetch(`/admin/documents/models/${brandId}`)
            .then(response => response.json())
            .then(models => {
                modelSelect.innerHTML = '<option value="">Все модели</option>';
                models.forEach(model => {
                    modelSelect.innerHTML += 
                        `<option value="${model.id}">${model.name}</option>`;
                });
            });
    } else {
        modelSelect.disabled = true;
        modelSelect.innerHTML = '<option value="">Все модели</option>';
    }
});

// Семантический поиск
document.getElementById('semanticSearchForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const searchBtn = document.getElementById('searchBtn');
    const spinner = document.getElementById('searchSpinner');
    const results = document.getElementById('searchResults');
    const analysisCard = document.getElementById('analysisCard');
    const analysisContent = document.getElementById('queryAnalysis');
    const resultsCount = document.getElementById('resultsCount');
    const searchType = document.getElementById('searchType');
    
    searchBtn.disabled = true;
    spinner.classList.remove('d-none');
    results.innerHTML = '<div class="text-center py-4"><div class="spinner-border"></div><p class="mt-2">Анализируем семантику...</p></div>';
    
    const formData = new FormData(this);
    
    fetch('/search/semantic', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayAnalysis(data.query_analysis);
            displaySemanticResults(data);
            analysisCard.classList.remove('d-none');
            searchType.classList.remove('d-none');
        } else {
            results.innerHTML = '<div class="alert alert-danger">Ошибка семантического поиска</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        results.innerHTML = '<div class="alert alert-danger">Ошибка при выполнении поиска</div>';
    })
    .finally(() => {
        searchBtn.disabled = false;
        spinner.classList.add('d-none');
    });
});

function displayAnalysis(analysis) {
    const analysisContent = document.getElementById('queryAnalysis');
    
    let html = `
        <p><strong>Определена проблема:</strong> ${analysis.diagnosis}</p>
        <p><strong>Тип запроса:</strong> ${analysis.intent}</p>
        <p><strong>Сложность:</strong> ${analysis.repair_complexity}</p>
        <p><strong>Время ремонта:</strong> ${analysis.estimated_repair_time}</p>
        <div class="mt-2">
            <small class="text-muted">Извлеченные понятия: ${analysis.keywords.join(', ')}</small>
        </div>
    `;
    
    analysisContent.innerHTML = html;
}

function displaySemanticResults(data) {
    const results = document.getElementById('searchResults');
    const resultsCount = document.getElementById('resultsCount');
    
    resultsCount.textContent = `${data.count} семантических совпадений`;
    resultsCount.classList.remove('d-none');
    
    if (data.count === 0) {
        results.innerHTML = `
            <div class="text-center text-muted py-5">
                <i class="fas fa-search-minus fa-3x mb-3"></i>
                <h5>Семантических совпадений не найдено</h5>
                <p>Попробуйте переформулировать запрос или использовать обычный поиск</p>
                <a href="{{ route('search.index') }}" class="btn btn-outline-primary mt-2">
                    Перейти к обычному поиску
                </a>
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            Результаты отсортированы по семантической релевантности
        </div>
    `;
    
    data.results.forEach((doc, index) => {
        const similarityPercent = (doc.semantic_similarity * 100).toFixed(1);
        const similarityColor = doc.semantic_similarity > 0.7 ? 'success' : 
                              doc.semantic_similarity > 0.5 ? 'warning' : 'info';
        
        html += `
            <div class="card mb-3 border-${similarityColor}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="card-title mb-0">${doc.title}</h6>
                        <span class="badge bg-${similarityColor}">
                            ${similarityPercent}% схожести
                        </span>
                    </div>
                    
                    <p class="card-text text-muted small mb-2">
                        <i class="fas fa-car"></i> ${doc.car_model.brand.name} ${doc.car_model.name} 
                        • <i class="fas fa-tools"></i> ${doc.category.name}
                    </p>
                    
                    <p class="card-text">${doc.content_text ? doc.content_text.substring(0, 400) + '...' : 'Содержимое не доступно'}</p>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-calendar"></i> ${new Date(doc.created_at).toLocaleDateString()}
                        </small>
                        <div>
                            <span class="badge bg-secondary me-2">${doc.file_type.toUpperCase()}</span>
                            <button class="btn btn-sm btn-outline-primary" onclick="viewDocument(${doc.id})">
                                <i class="fas fa-eye"></i> Подробнее
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    results.innerHTML = html;
}
</script>
@endpush