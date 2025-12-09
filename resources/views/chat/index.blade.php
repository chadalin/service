@extends('layouts.app')

@section('title', 'Поиск документации')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Параметры поиска</h5>
                </div>
                <div class="card-body">
                    <form id="searchForm">
                        <div class="mb-3">
                            <label for="query" class="form-label">Поисковый запрос</label>
                            <input type="text" class="form-control" id="query" name="query" 
                                   placeholder="Например: замена тормозных колодок" required>
                        </div>

                        <div class="mb-3">
                            <label for="brand_id" class="form-label">Бренд автомобиля</label>
                            <select class="form-select" id="brand_id" name="brand_id">
                                <option value="">Все бренды</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}">
                                        {{ $brand->name }} ({{ $brand->name_cyrillic }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="car_model_id" class="form-label">Модель автомобиля</label>
                            <select class="form-select" id="car_model_id" name="car_model_id" disabled>
                                <option value="">Сначала выберите бренд</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="searchBtn">
                            <span class="spinner-border spinner-border-sm d-none" id="searchSpinner"></span>
                            Найти документацию
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Результаты поиска</h5>
                </div>
                <div class="card-body">
                    <div id="searchResults">
                        <div class="text-center text-muted py-5">
                            <p>Введите поисковый запрос для начала поиска</p>
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
document.getElementById('brand_id').addEventListener('change', function() {
    const brandId = this.value;
    const modelSelect = document.getElementById('car_model_id');
    
    if (brandId) {
        modelSelect.disabled = false;
        modelSelect.innerHTML = '<option value="">Загрузка моделей...</option>';
        
        fetch(`/chat/models/${brandId}`)
            .then(response => response.json())
            .then(models => {
                modelSelect.innerHTML = '<option value="">Все модели</option>';
                models.forEach(model => {
                    const years = model.year_from && model.year_to ? 
                        ` (${model.year_from}-${model.year_to})` : '';
                    modelSelect.innerHTML += 
                        `<option value="${model.id}">${model.name} ${model.name_cyrillic}${years}</option>`;
                });
            })
            .catch(error => {
                modelSelect.innerHTML = '<option value="">Ошибка загрузки</option>';
                console.error('Error loading models:', error);
            });
    } else {
        modelSelect.disabled = true;
        modelSelect.innerHTML = '<option value="">Сначала выберите бренд</option>';
    }
});

document.getElementById('searchForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const searchBtn = document.getElementById('searchBtn');
    const spinner = document.getElementById('searchSpinner');
    const results = document.getElementById('searchResults');
    
    searchBtn.disabled = true;
    spinner.classList.remove('d-none');
    results.innerHTML = '<div class="text-center py-3"><div class="spinner-border"></div></div>';
    
    const formData = new FormData(this);
    
    fetch('/chat/search', {
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
            displayResults(data);
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

function displayResults(data) {
    const results = document.getElementById('searchResults');
    
    if (data.count === 0) {
        results.innerHTML = `
            <div class="text-center text-muted py-5">
                <p>По запросу "${data.query}" ничего не найдено</p>
                <p class="small">Попробуйте изменить параметры поиска</p>
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="mb-3">
            <p>Найдено документов: <strong>${data.count}</strong></p>
        </div>
    `;
    
    data.results.forEach(doc => {
        html += `
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title">${doc.title}</h6>
                    <p class="card-text text-muted small">
                        ${doc.car_model.brand.name} ${doc.car_model.name} • ${doc.category.name}
                    </p>
                    <p class="card-text">${doc.content_text ? doc.content_text.substring(0, 200) + '...' : 'Нет описания'}</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">${new Date(doc.created_at).toLocaleDateString()}</small>
                        <span class="badge bg-secondary">${doc.file_type.toUpperCase()}</span>
                    </div>
                </div>
            </div>
        `;
    });
    
    results.innerHTML = html;
}
</script>
@endpush