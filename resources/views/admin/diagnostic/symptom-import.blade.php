@extends('layouts.app')

@section('title', 'Импорт симптомов и правил')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-upload me-2"></i> Импорт симптомов и правил диагностики
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Форма загрузки -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Загрузка файла Excel</h6>
                                </div>
                                <div class="card-body">
                                    <form id="importForm" enctype="multipart/form-data">
                                        @csrf
                                        
                                        <div class="mb-3">
                                            <label for="excel_file" class="form-label">Файл Excel</label>
                                            <input type="file" 
                                                   class="form-control" 
                                                   id="excel_file" 
                                                   name="excel_file"
                                                   accept=".xlsx,.xls,.csv"
                                                   required>
                                            <div class="form-text">
                                                Поддерживаемые форматы: .xlsx, .xls, .csv (макс. 10MB)
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" 
                                                   class="form-check-input" 
                                                   id="update_existing" 
                                                   name="update_existing"
                                                   checked>
                                            <label class="form-check-label" for="update_existing">
                                                Обновлять существующие записи
                                            </label>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary w-100" id="importBtn">
                                            <i class="bi bi-upload me-2"></i> Начать импорт
                                        </button>
                                        
                                        <div class="progress mt-3 d-none" id="progressBar">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                                 role="progressbar" 
                                                 style="width: 0%"></div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <!-- Информация и шаблон -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Информация</h6>
                                </div>
                                <div class="card-body">
                                    <h6>📋 Формат файла:</h6>
                                    <p class="small text-muted">
                                        Файл должен содержать следующие колонки:
                                    </p>
                                    <ul class="small">
                                        <li><strong>symptom_name</strong> - Название симптома (обязательно)</li>
                                        <li><strong>symptom_description</strong> - Описание симптома</li>
                                        <li><strong>symptom_slug</strong> - URL-ключ (или будет создан автоматически)</li>
                                        <li><strong>brand</strong> - Марка автомобиля</li>
                                        <li><strong>model</strong> - Модель автомобиля (опционально)</li>
                                        <li><strong>diagnostic_steps</strong> - Шаги диагностики (JSON массив)</li>
                                        <li><strong>possible_causes</strong> - Возможные причины (JSON массив)</li>
                                        <li><strong>required_data</strong> - Требуемые данные для диагностики</li>
                                        <li><strong>complexity_level</strong> - Уровень сложности (1-10)</li>
                                        <li><strong>estimated_time</strong> - Примерное время диагностики (минут)</li>
                                        <li><strong>consultation_price</strong> - Базовая цена консультации</li>
                                    </ul>
                                    
                                    <div class="alert alert-info mt-3">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <strong>Примечание:</strong> Если поле brand заполнено, будет создано правило диагностики
                                    </div>
                                    
                                    <a href="{{ route('admin.symptoms.import.template') }}" 
                                       class="btn btn-outline-success w-100 mt-3">
                                        <i class="bi bi-download me-2"></i> Скачать шаблон
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Статистика -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Статистика базы</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="text-center p-2 bg-light rounded">
                                                <div class="h4 mb-0">{{ \App\Models\Diagnostic\Symptom::count() }}</div>
                                                <small class="text-muted">Симптомов</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-center p-2 bg-light rounded">
                                                <div class="h4 mb-0">{{ \App\Models\Diagnostic\Rule::count() }}</div>
                                                <small class="text-muted">Правил</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Результаты импорта -->
                    <div class="card d-none" id="resultsCard">
                        <div class="card-header">
                            <h6 class="mb-0">Результаты импорта</h6>
                        </div>
                        <div class="card-body">
                            <div id="importResults"></div>
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
    .result-item {
        padding: 10px;
        margin-bottom: 5px;
        border-radius: 5px;
    }
    
    .success-item {
        background-color: #d4edda;
        border-left: 4px solid #28a745;
    }
    
    .error-item {
        background-color: #f8d7da;
        border-left: 4px solid #dc3545;
    }
    
    .warning-item {
        background-color: #fff3cd;
        border-left: 4px solid #ffc107;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const importForm = document.getElementById('importForm');
    const importBtn = document.getElementById('importBtn');
    const progressBar = document.getElementById('progressBar');
    const resultsCard = document.getElementById('resultsCard');
    const importResults = document.getElementById('importResults');
    
    importForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const originalBtnText = importBtn.innerHTML;
        
        // Блокируем кнопку
        importBtn.disabled = true;
        importBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i> Импорт...';
        
        // Показываем прогресс-бар
        progressBar.classList.remove('d-none');
        const progressBarInner = progressBar.querySelector('.progress-bar');
        progressBarInner.style.width = '10%';
        
        try {
            const response = await fetch('{{ route("admin.symptoms.import") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            
            const data = await response.json();
            
            // Обновляем прогресс
            progressBarInner.style.width = '100%';
            
            if (data.success) {
                // Показываем результаты
                resultsCard.classList.remove('d-none');
                displayResults(data.results);
                
                // Уведомление
                showToast('Импорт успешно завершен!', 'success');
            } else {
                showToast('Ошибка импорта: ' + data.message, 'danger');
            }
            
        } catch (error) {
            console.error('Import error:', error);
            showToast('Ошибка при импорте файла', 'danger');
        } finally {
            // Восстанавливаем кнопку
            importBtn.disabled = false;
            importBtn.innerHTML = originalBtnText;
            
            // Скрываем прогресс-бар через 1 секунду
            setTimeout(() => {
                progressBar.classList.add('d-none');
                progressBarInner.style.width = '0%';
            }, 1000);
        }
    });
    
    function displayResults(results) {
        let html = `
            <div class="alert alert-success">
                <strong>Импорт завершен!</strong> Обработано ${results.total_rows} строк.
            </div>
            
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="card text-center bg-success text-white">
                        <div class="card-body p-2">
                            <h4 class="mb-0">${results.symptoms_created}</h4>
                            <small>Новых симптомов</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center bg-info text-white">
                        <div class="card-body p-2">
                            <h4 class="mb-0">${results.symptoms_updated}</h4>
                            <small>Обновлено симптомов</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center bg-primary text-white">
                        <div class="card-body p-2">
                            <h4 class="mb-0">${results.rules_created}</h4>
                            <small>Новых правил</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center bg-warning text-white">
                        <div class="card-body p-2">
                            <h4 class="mb-0">${results.rules_updated}</h4>
                            <small>Обновлено правил</small>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        if (results.errors && results.errors.length > 0) {
            html += `
                <div class="alert alert-danger">
                    <h6>Ошибки (${results.errors.length}):</h6>
                    <div style="max-height: 200px; overflow-y: auto;">
            `;
            
            results.errors.forEach(error => {
                html += `<div class="error-item mb-1 p-2">${error}</div>`;
            });
            
            html += `
                    </div>
                </div>
            `;
        }
        
        importResults.innerHTML = html;
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