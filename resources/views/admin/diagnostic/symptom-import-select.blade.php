@extends('layouts.app')

@section('title', 'Выбор марки и модели для импорта')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-upload me-2"></i> Шаг 1: Выбор марки и модели
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Форма выбора -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Выберите автомобиль</h6>
                                </div>
                                <div class="card-body">
                                    <form id="selectForm">
                                        @csrf
                                        
                                        <!-- Выбор марки -->
                                        <div class="mb-4">
                                            <label for="brand_id" class="form-label fw-bold">
                                                <i class="bi bi-car-front me-1"></i> Марка автомобиля
                                            </label>
                                            <select name="brand_id" 
                                                    id="brand_id" 
                                                    class="form-select form-select-lg"
                                                    required>
                                                <option value="">-- Выберите марку --</option>
                                                @foreach($brands as $brand)
                                                    <option value="{{ $brand->id }}">
                                                        {{ $brand->name }}
                                                        @if($brand->name_cyrillic)
                                                            ({{ $brand->name_cyrillic }})
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="form-text">
                                                Выберите марку из списка
                                            </div>
                                        </div>

                                        <!-- Выбор модели -->
                                        <div class="mb-4">
                                            <label for="model_id" class="form-label fw-bold">
                                                <i class="bi bi-card-checklist me-1"></i> Модель (опционально)
                                            </label>
                                            <select name="model_id" 
                                                    id="model_id" 
                                                    class="form-select form-select-lg"
                                                    disabled>
                                                <option value="">-- Все модели --</option>
                                            </select>
                                            <div class="form-text">
                                                Можно выбрать конкретную модель или оставить "Все модели"
                                            </div>
                                            <div id="modelLoading" class="spinner-border spinner-border-sm text-primary d-none mt-2"></div>
                                        </div>

                                        <!-- Настройки импорта -->
                                        <div class="card border mb-4">
                                            <div class="card-body">
                                                <h6 class="mb-3">
                                                    <i class="bi bi-gear me-1"></i> Настройки импорта
                                                </h6>
                                                
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" 
                                                           type="checkbox" 
                                                           id="update_existing" 
                                                           name="update_existing"
                                                           checked>
                                                    <label class="form-check-label" for="update_existing">
                                                        Обновлять существующие правила
                                                    </label>
                                                    <div class="form-text">
                                                        Если правило уже существует для этой марки/модели и симптома
                                                    </div>
                                                </div>
                                                
                                                <div class="alert alert-info">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Как это работает:</strong><br>
                                                    1. CSV файл должен содержать только симптомы<br>
                                                    2. Все симптомы будут привязаны к выбранной марке/модели<br>
                                                    3. Если указана модель - только к ней, если нет - ко всем моделям марки
                                                </div>
                                            </div>
                                        </div>

                                        <button type="button" 
                                                class="btn btn-primary btn-lg w-100" 
                                                id="nextBtn"
                                                disabled>
                                            <i class="bi bi-arrow-right me-2"></i> Далее: Загрузка файла
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <!-- Информация о выборе -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Информация</h6>
                                </div>
                                <div class="card-body">
                                    <h6>📋 Преимущества этого метода:</h6>
                                    <ul class="mb-3">
                                        <li>Не нужно указывать бренд в CSV файле</li>
                                        <li>Избегаем ошибок "Бренд не найден"</li>
                                        <li>Быстрый импорт для конкретного автомобиля</li>
                                        <li>Можно выбрать "Все модели" марки</li>
                                    </ul>
                                    
                                    <h6>📁 Формат CSV файла:</h6>
                                    <p class="small text-muted">
                                        Файл должен содержать минимум 3 колонки:
                                    </p>
                                    <ol class="small">
                                        <li><strong>symptom_name</strong> - Название симптома</li>
                                        <li><strong>symptom_description</strong> - Описание симптома</li>
                                        <li><strong>symptom_slug</strong> - URL-ключ (опционально)</li>
                                    </ol>
                                    
                                    <p class="small text-muted">
                                        Опциональные колонки (для диагностической информации):
                                    </p>
                                    <ol class="small" start="4">
                                        <li><strong>diagnostic_steps</strong> - Шаги диагностики</li>
                                        <li><strong>possible_causes</strong> - Возможные причины</li>
                                        <li><strong>required_data</strong> - Требуемые данные</li>
                                        <li><strong>complexity_level</strong> - Сложность (1-10)</li>
                                        <li><strong>estimated_time</strong> - Время (минуты)</li>
                                        <li><strong>consultation_price</strong> - Цена консультации</li>
                                    </ol>
                                    
                                    <div class="alert alert-warning mt-3">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        <strong>Внимание:</strong> Все симптомы из файла будут привязаны к выбранной марке/модели!
                                    </div>
                                    
                                    <a href="{{ route('admin.symptoms.import.template') }}" 
                                       class="btn btn-outline-success w-100 mt-2">
                                        <i class="bi bi-download me-2"></i> Скачать шаблон
                                    </a>
                                    
                                    <button type="button" 
                                            class="btn btn-outline-info w-100 mt-2" 
                                            onclick="window.location.href='{{ route('admin.symptoms.import.page') }}'">
                                        <i class="bi bi-arrow-left me-2"></i> Автоматический импорт
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Выбранный автомобиль -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Выбранный автомобиль</h6>
                                </div>
                                <div class="card-body">
                                    <div id="selectedVehicle" class="text-center py-4">
                                        <i class="bi bi-car-front display-1 text-muted"></i>
                                        <p class="text-muted mt-3">Выберите марку и модель</p>
                                    </div>
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

@push('styles')
<style>
    .vehicle-card {
        transition: all 0.3s ease;
        cursor: pointer;
        border: 2px solid transparent;
    }
    
    .vehicle-card:hover {
        transform: translateY(-2px);
        border-color: #007bff;
    }
    
    .vehicle-card.selected {
        border-color: #28a745;
        background-color: #f8fff8;
    }
    
    .vehicle-icon {
        font-size: 2rem;
        color: #6c757d;
    }
    
    .vehicle-icon.selected {
        color: #28a745;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const brandSelect = document.getElementById('brand_id');
    const modelSelect = document.getElementById('model_id');
    const nextBtn = document.getElementById('nextBtn');
    const modelLoading = document.getElementById('modelLoading');
    const selectedVehicle = document.getElementById('selectedVehicle');
    
    let selectedBrand = null;
    let selectedModel = null;
    
    // Загрузка моделей при выборе марки
    brandSelect.addEventListener('change', function() {
        const brandId = this.value;
        
        if (!brandId) {
            resetModelSelect();
            updateNextButton();
            updateSelectedVehicle();
            return;
        }
        
        // Находим выбранный бренд
        selectedBrand = {
            id: brandId,
            name: this.options[this.selectedIndex].text.split(' (')[0]
        };
        
        // Загружаем модели
        loadModels(brandId);
        updateSelectedVehicle();
    });
    
    // Выбор модели
    modelSelect.addEventListener('change', function() {
        if (this.value) {
            selectedModel = {
                id: this.value,
                name: this.options[this.selectedIndex].text
            };
        } else {
            selectedModel = null;
        }
        
        updateNextButton();
        updateSelectedVehicle();
    });
    
    // Переход к загрузке файла
    nextBtn.addEventListener('click', function() {
        if (!selectedBrand) {
            showToast('Выберите марку автомобиля', 'warning');
            return;
        }
        
        // Сохраняем выбор в localStorage
        localStorage.setItem('import_brand_id', selectedBrand.id);
        localStorage.setItem('import_brand_name', selectedBrand.name);
        
        if (selectedModel) {
            localStorage.setItem('import_model_id', selectedModel.id);
            localStorage.setItem('import_model_name', selectedModel.name);
        } else {
            localStorage.removeItem('import_model_id');
            localStorage.removeItem('import_model_name');
        }
        
        const updateExisting = document.getElementById('update_existing').checked;
        localStorage.setItem('import_update_existing', updateExisting);
        
        // Перенаправляем на страницу загрузки файла
        window.location.href = '{{ route("admin.symptoms.import.page") }}?step=2';
    });
    
    function loadModels(brandId) {
        modelSelect.innerHTML = '<option value="">Загрузка моделей...</option>';
        modelSelect.disabled = true;
        modelLoading.classList.remove('d-none');
        
        fetch(`/admin/symptoms/get-models/${brandId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.models.length > 0) {
                    let options = '<option value="">-- Все модели --</option>';
                    
                    data.models.forEach(model => {
                        const displayName = model.name || model.name_cyrillic || `Модель ${model.id}`;
                        options += `<option value="${model.id}">${displayName}</option>`;
                    });
                    
                    modelSelect.innerHTML = options;
                } else {
                    modelSelect.innerHTML = '<option value="">Нет доступных моделей</option>';
                }
                
                modelSelect.disabled = false;
                modelLoading.classList.add('d-none');
                updateNextButton();
            })
            .catch(error => {
                console.error('Error loading models:', error);
                modelSelect.innerHTML = '<option value="">Ошибка загрузки</option>';
                modelSelect.disabled = false;
                modelLoading.classList.add('d-none');
            });
    }
    
    function resetModelSelect() {
        modelSelect.innerHTML = '<option value="">-- Все модели --</option>';
        modelSelect.disabled = true;
        selectedBrand = null;
        selectedModel = null;
    }
    
    function updateNextButton() {
        nextBtn.disabled = !selectedBrand;
    }
    
    function updateSelectedVehicle() {
        if (!selectedBrand) {
            selectedVehicle.innerHTML = `
                <i class="bi bi-car-front display-1 text-muted"></i>
                <p class="text-muted mt-3">Выберите марку и модель</p>
            `;
            return;
        }
        
        let html = `
            <div class="text-center">
                <i class="bi bi-car-front-fill display-1 text-primary"></i>
                <h5 class="mt-3">${selectedBrand.name}</h5>
        `;
        
        if (selectedModel) {
            html += `
                <p class="mb-1">
                    <small class="text-muted">Модель:</small><br>
                    <strong>${selectedModel.name}</strong>
                </p>
            `;
        } else {
            html += `
                <p class="mb-1">
                    <small class="text-muted">Все модели марки</small>
                </p>
            `;
        }
        
        html += `
                <div class="mt-3">
                    <small class="text-muted d-block">Будет создано:</small>
                    <div class="d-flex justify-content-center gap-3 mt-2">
                        <div class="text-center">
                            <div class="badge bg-info">Симптомы</div>
                            <div class="small text-muted">из CSV файла</div>
                        </div>
                        <div class="text-center">
                            <div class="badge bg-success">Правила</div>
                            <div class="small text-muted">для выбранного авто</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        selectedVehicle.innerHTML = html;
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
});
</script>
@endpush