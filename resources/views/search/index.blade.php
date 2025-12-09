@extends('layouts.app')

@section('title', 'Умный поиск документации')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">🔍 Умный поиск</h5>
                </div>
                <div class="card-body">
                    <form id="searchForm">
                        <div class="mb-3">
                            <label for="query" class="form-label">Опишите проблему</label>
                            <textarea class="form-control" id="query" name="query" 
                                      rows="3" placeholder="Например: автомобиль не заводится, стучит в двигателе..."
                                      required></textarea>
                            <div class="form-text">Опишите проблему как можно подробнее</div>
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
                            Найти решение
                        </button>
                    </form>
                </div>
            </div>

            <div class="card mt-3 d-none" id="analysisCard">
                <div class="card-header">
                    <h6 class="mb-0">📊 Анализ запроса</h6>
                </div>
                <div class="card-body">
                    <div id="queryAnalysis"></div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">📄 Результаты поиска</h5>
                    <span class="badge bg-primary d-none" id="resultsCount">0 найдено</span>
                </div>
                <div class="card-body">
                    <div id="searchResults">
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-search fa-3x mb-3"></i>
                            <p>Введите описание проблемы для поиска решений</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.document-card {
    transition: transform 0.2s;
    border-left: 4px solid #007bff;
}
.document-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.relevance-badge {
    font-size: 0.8em;
}
</style>
@endpush

@push('scripts')
<script>
// Загрузка моделей по бренду
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
                    const years = model.year_from && model.year_to ? 
                        ` (${model.year_from}-${model.year_to})` : '';
                    modelSelect.innerHTML += 
                        `<option value="${model.id}">${model.name}${years}</option>`;
                });
            });
    } else {
        modelSelect.disabled = true;
        modelSelect.innerHTML = '<option value="">Все модели</option>';
    }
});

// Поиск
document.getElementById('searchForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const searchBtn = document.getElementById('searchBtn');
    const spinner = document.getElementById('searchSpinner');
    const results = document.getElementById('searchResults');
    const analysisCard = document.getElementById('analysisCard');
    const analysisContent = document.getElementById('queryAnalysis');
    const resultsCount = document.getElementById('resultsCount');
    
    searchBtn.disabled = true;
    spinner.classList.remove('d-none');
    results.innerHTML = '<div class="text-center py-4"><div class="spinner-border"></div><p class="mt-2">Ищем решения...</p></div>';
    
    const formData = new FormData(this);
    
    fetch('/search', {
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
            displayResults(data);
            analysisCard.classList.remove('d-none');
        } else {
            results.innerHTML = '<div class="alert alert-danger">Ошибка поиска</div>';
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
        <p><strong>Сложность ремонта:</strong> ${analysis.repair_complexity}</p>
        <p><strong>Примерное время:</strong> ${analysis.estimated_repair_time}</p>
        <div class="mt-2">
            <small class="text-muted">Ключевые слова: ${analysis.keywords.join(', ')}</small>
        </div>
    `;
    
    analysisContent.innerHTML = html;
}

function displayResults(data) {
    const results = document.getElementById('searchResults');
    const resultsCount = document.getElementById('resultsCount');
    
    resultsCount.textContent = `${data.count} найдено`;
    resultsCount.classList.remove('d-none');
    
    if (data.count === 0) {
        results.innerHTML = `
            <div class="text-center text-muted py-5">
                <i class="fas fa-folder-open fa-3x mb-3"></i>
                <h5>Ничего не найдено</h5>
                <p>Попробуйте изменить параметры поиска или описать проблему по-другому</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    
    data.results.forEach((doc, index) => {
        const relevancePercent = Math.min(100, (doc.relevance_score / 10) * 100);
        const relevanceColor = relevancePercent > 70 ? 'success' : relevancePercent > 40 ? 'warning' : 'secondary';
        
        html += `
            <div class="card document-card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="card-title mb-0">${doc.title}</h6>
                        <span class="badge bg-${relevanceColor} relevance-badge">
                            ${relevancePercent.toFixed(0)}% релевантности
                        </span>
                    </div>
                    
                    <p class="card-text text-muted small mb-2">
                        <i class="fas fa-car"></i> ${doc.car_model.brand.name} ${doc.car_model.name} 
                        • <i class="fas fa-tools"></i> ${doc.category.name}
                        • <i class="fas fa-file"></i> ${doc.file_type.toUpperCase()}
                    </p>
                    
                    <p class="card-text">${doc.content_text ? doc.content_text.substring(0, 300) + '...' : 'Содержимое не доступно'}</p>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-calendar"></i> ${new Date(doc.created_at).toLocaleDateString()}
                        </small>
                        <button class="btn btn-sm btn-outline-primary" onclick="viewDocument(${doc.id})">
                            <i class="fas fa-eye"></i> Подробнее
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    results.innerHTML = html;
}

function viewDocument(documentId) {
    // Здесь можно добавить просмотр документа
    alert('Просмотр документа ' + documentId);
}
</script>
@endpush