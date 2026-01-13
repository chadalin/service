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
document.addEventListener('DOMContentLoaded', function() {
    const brandSelect = document.getElementById('brand_id');
    const modelSelect = document.getElementById('car_model_id');

    if (brandSelect && modelSelect) {
        console.log("✅ Chat page loaded, brand select found");
        
        brandSelect.addEventListener('change', function() {
            const brandId = this.value;
            console.log("Brand changed to:", brandId);
            
            if (brandId) {
                modelSelect.disabled = false;
                modelSelect.innerHTML = '<option value="">Загрузка моделей...</option>';
                
                // Делаем AJAX запрос
                fetch(`/chat/models/${brandId}`)
                    .then(response => {
                        console.log("Response status:", response.status);
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(models => {
                        console.log("Models loaded:", models);
                        modelSelect.innerHTML = '<option value="">Все модели</option>';
                        
                        if (models.length === 0) {
                            modelSelect.innerHTML += '<option value="">Нет моделей</option>';
                            return;
                        }
                        
                        models.forEach(model => {
                            const years = model.year_from && model.year_to ? 
                                ` (${model.year_from}-${model.year_to})` : '';
                            const displayName = model.name_cyrillic ? 
                                `${model.name} / ${model.name_cyrillic}${years}` : 
                                `${model.name}${years}`;
                                
                            modelSelect.innerHTML += 
                                `<option value="${model.id}">${displayName}</option>`;
                        });
                    })
                    .catch(error => {
                        console.error('Error loading models:', error);
                        modelSelect.innerHTML = '<option value="">Ошибка загрузки</option>';
                    });
            } else {
                modelSelect.disabled = true;
                modelSelect.innerHTML = '<option value="">Все модели</option>';
            }
        });
        
        // Инициализация если есть выбранный бренд
        if (brandSelect.value) {
            brandSelect.dispatchEvent(new Event('change'));
        }
    } else {
        console.error("❌ Brand or model select not found!");
    }

    // Обработчик формы поиска
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log("Search form submitted");
            
            const searchBtn = document.getElementById('searchBtn');
            const spinner = document.getElementById('searchSpinner');
            const results = document.getElementById('searchResults');
            
            searchBtn.disabled = true;
            spinner.classList.remove('d-none');
            results.innerHTML = '<div class="text-center py-3"><div class="spinner-border"></div><p>Ищем...</p></div>';
            
            const formData = new FormData(this);
            
            fetch('/chat/search', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            })
            .then(response => {
                console.log("Search response status:", response.status);
                return response.json();
            })
            .then(data => {
                console.log("Search results:", data);
                
                if (data.success) {
                    displayResults(data);
                } else {
                    results.innerHTML = '<div class="alert alert-danger">Ошибка поиска</div>';
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                results.innerHTML = '<div class="alert alert-danger">Ошибка соединения</div>';
            })
            .finally(() => {
                searchBtn.disabled = false;
                spinner.classList.add('d-none');
            });
        });
    }
});

function displayResults(data) {
    const results = document.getElementById('searchResults');
    
    if (data.count === 0) {
        results.innerHTML = `
            <div class="text-center text-muted py-5">
                <p>По запросу "${data.query}" ничего не найдено</p>
            </div>
        `;
        return;
    }
    
    let html = `<div class="mb-3"><p>Найдено: <strong>${data.count}</strong></p></div>`;
    
    data.results.forEach(doc => {
        html += `
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title">${doc.title}</h6>
                    <p class="card-text text-muted small">
                        ${doc.car_model?.brand?.name || ''} ${doc.car_model?.name || ''} 
                        • ${doc.category?.name || ''}
                    </p>
                    <p class="card-text">${doc.content_text ? doc.content_text.substring(0, 200) + '...' : 'Нет описания'}</p>
                </div>
            </div>
        `;
    });
    
    results.innerHTML = html;
}
</script>
@endpush