@extends('layouts.app')

@section('title', 'Импорт прайс-листа')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-currency-dollar me-2"></i> Импорт прайс-листа
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Прогресс-бар импорта -->
                    <div id="importProgress" class="d-none">
                        <div class="card border-info mb-4">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">
                                    <i class="bi bi-hourglass-split me-2"></i> Идет импорт...
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="small">Прогресс импорта</span>
                                        <span class="small" id="progressPercent">0%</span>
                                    </div>
                                    <div class="progress" style="height: 20px;">
                                        <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                             role="progressbar" style="width: 0%"></div>
                                    </div>
                                </div>
                                
                                <div id="importStats" class="row text-center">
                                    <!-- Статистика будет заполняться динамически -->
                                </div>
                                
                                <div id="importErrors" class="mt-3 d-none">
                                    <h6 class="text-danger">
                                        <i class="bi bi-exclamation-triangle me-2"></i> Ошибки при импорте:
                                    </h6>
                                    <div class="alert alert-danger" id="errorList"></div>
                                </div>
                                
                                <div class="text-center mt-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Загрузка...</span>
                                    </div>
                                    <p class="mt-2">Пожалуйста, не закрывайте страницу до завершения импорта</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Форма выбора бренда и файла -->
                    <div id="importForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="bi bi-gear me-1"></i> Настройки импорта
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <form id="priceImportForm" enctype="multipart/form-data">
    @csrf
    
    <!-- Выбор бренда -->
    <div class="mb-4">
        <label for="brand_id" class="form-label fw-bold">
            <i class="bi bi-tag me-1"></i> Бренд
        </label>
        <select name="brand_id" 
                id="brand_id" 
                class="form-select form-select-lg"
                required>
            <option value="">-- Выберите бренд --</option>
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
    
    <!-- Загрузка файла -->
    <div class="mb-4">
        <label for="excel_file" class="form-label fw-bold">
            <i class="bi bi-file-earmark-excel me-1"></i> Файл прайс-листа
        </label>
        <input type="file" 
               name="excel_file" 
               id="excel_file" 
               class="form-control form-control-lg"
               accept=".xlsx,.xls,.csv"
               required>
    </div>
    
    <!-- Настройки -->
    <div class="card border mb-4">
        <div class="card-body">
            <h6 class="mb-3">
                <i class="bi bi-sliders me-1"></i> Дополнительные опции
            </h6>
            
            <!-- Используем hidden поля с правильными значениями -->
            <input type="hidden" name="update_existing" id="update_existing_hidden" value="false">
            <input type="hidden" name="match_symptoms" id="match_symptoms_hidden" value="false">
            
            <div class="form-check mb-3">
                <input class="form-check-input" 
                       type="checkbox" 
                       id="update_existing_checkbox" 
                       checked
                       onchange="document.getElementById('update_existing_hidden').value = this.checked ? 'true' : 'false'">
                <label class="form-check-label" for="update_existing_checkbox">
                    Обновлять существующие позиции
                </label>
            </div>
            
            <div class="form-check mb-3">
                <input class="form-check-input" 
                       type="checkbox" 
                       id="match_symptoms_checkbox" 
                       checked
                       onchange="document.getElementById('match_symptoms_hidden').value = this.checked ? 'true' : 'false'">
                <label class="form-check-label" for="match_symptoms_checkbox">
                    Сопоставлять с симптомами диагностики
                </label>
            </div>
        </div>
    </div>
    
    <!-- Кнопки действий -->
    <!-- ... -->

                                            
                                            <!-- Кнопки действий -->
                                            <div class="d-grid gap-2">
                                                <button type="button" 
                                                        class="btn btn-outline-primary btn-lg" 
                                                        id="previewBtn">
                                                    <i class="bi bi-eye me-2"></i> Предварительный просмотр
                                                </button>
                                                
                                                <button type="submit" 
                                                        class="btn btn-primary btn-lg" 
                                                        id="importBtn">
                                                    <i class="bi bi-upload me-2"></i> Начать импорт
                                                </button>
                                                
                                                <a href="{{ route('admin.price.import.template') }}" 
                                                   class="btn btn-outline-success">
                                                    <i class="bi bi-download me-2"></i> Скачать шаблон
                                                </a>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <!-- Предварительный просмотр -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="bi bi-table me-1"></i> Предварительный просмотр
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="previewContainer" class="d-none">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered" id="previewTable">
                                                    <thead id="previewHeaders">
                                                        <!-- Заголовки будут заполнены динамически -->
                                                    </thead>
                                                    <tbody id="previewBody">
                                                        <!-- Данные будут заполнены динамически -->
                                                    </tbody>
                                                </table>
                                            </div>
                                            
                                            <div class="alert alert-info mt-3">
                                                <i class="bi bi-info-circle me-2"></i>
                                                <strong>Информация:</strong>
                                                <ul class="mb-0">
                                                    <li>Показаны первые 5 строк файла</li>
                                                    <li>Общее количество строк: <span id="totalRows">0</span></li>
                                                    <li>Обнаружено колонок: <span id="totalColumns">0</span></li>
                                                </ul>
                                            </div>
                                        </div>
                                        
                                        <div id="noPreview" class="text-center py-5">
                                            <i class="bi bi-file-earmark-excel display-1 text-muted"></i>
                                            <p class="text-muted mt-3">
                                                Загрузите файл и нажмите "Предварительный просмотр"
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Информация о формате -->
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="bi bi-info-circle me-1"></i> Формат файла
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <h6>📋 Обязательные поля:</h6>
                                        <ul class="mb-3">
                                            <li><strong>Артикул (SKU)</strong> - уникальный код товара</li>
                                            <li><strong>Название</strong> - наименование запчасти</li>
                                        </ul>
                                        
                                        <h6>📁 Опциональные поля:</h6>
                                        <ul class="mb-3">
                                            <li><strong>Каталожный бренд</strong> - бренд производителя</li>
                                            <li><strong>Количество</strong> - количество на складе</li>
                                            <li><strong>Цена</strong> - цена товара</li>
                                            <li><strong>Единица измерения</strong> - шт, комплект и т.д.</li>
                                            <li><strong>Описание</strong> - дополнительное описание</li>
                                        </ul>
                                        
                                        <h6>⚠️ Важно:</h6>
                                        <div class="alert alert-warning">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            <strong>Внимание!</strong>
                                            <ul class="mb-0 mt-2">
                                                <li>SKU используется как уникальный идентификатор</li>
                                                <li>При повторной загрузке обновляются только количество и цена</li>
                                                <li>Название товара при обновлении не меняется</li>
                                                <li>Система автоматически ищет совпадения с симптомами диагностики</li>
                                            </ul>
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
</div>

<!-- Модальное окно результатов -->
<div class="modal fade" id="resultsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-check-circle me-2"></i> Импорт завершен
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="resultsContent">
                    <!-- Результаты будут заполнены динамически -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                <button type="button" class="btn btn-primary" id="viewPriceListBtn">
                    <i class="bi bi-list-ul me-2"></i> Перейти к прайс-листу
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .progress {
        border-radius: 10px;
        overflow: hidden;
    }
    
    .progress-bar {
        border-radius: 10px;
    }
    
    .stat-card {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        transition: all 0.3s;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .stat-value {
        font-size: 1.8rem;
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .stat-label {
        font-size: 0.9rem;
        color: #6c757d;
    }
    
    .table th {
        background-color: #f8f9fa;
    }
    
    .preview-highlight {
        background-color: #fff3cd !important;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const importForm = document.getElementById('priceImportForm');
    const previewBtn = document.getElementById('previewBtn');
    const importBtn = document.getElementById('importBtn');
    const previewContainer = document.getElementById('previewContainer');
    const noPreview = document.getElementById('noPreview');
    const importProgress = document.getElementById('importProgress');
    const progressBar = document.getElementById('progressBar');
    const progressPercent = document.getElementById('progressPercent');
    const importStats = document.getElementById('importStats');
    const importFormDiv = document.getElementById('importForm');
    const importErrors = document.getElementById('importErrors');
    const errorList = document.getElementById('errorList');
    const resultsModal = new bootstrap.Modal(document.getElementById('resultsModal'));
    const viewPriceListBtn = document.getElementById('viewPriceListBtn');
    
    let selectedFile = null;
    let previewData = null;
    
    // Предварительный просмотр файла
    previewBtn.addEventListener('click', function() {
        const formData = new FormData();
        const fileInput = document.getElementById('excel_file');
        const brandId = document.getElementById('brand_id').value;
        
        if (!fileInput.files.length) {
            showToast('Выберите файл для предпросмотра', 'warning');
            return;
        }
        
        if (!brandId) {
            showToast('Выберите бренд', 'warning');
            return;
        }
        
        formData.append('excel_file', fileInput.files[0]);
        formData.append('_token', document.querySelector('input[name="_token"]').value);
        
        previewBtn.disabled = true;
        previewBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Загрузка...';
        
        fetch('{{ route("admin.price.import.preview") }}', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                previewData = data.data;
                displayPreview(data.data);
                showToast('Файл успешно загружен для предпросмотра', 'success');
            } else {
                showToast(data.message || 'Ошибка при загрузке файла', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Ошибка при загрузке файла', 'error');
        })
        .finally(() => {
            previewBtn.disabled = false;
            previewBtn.innerHTML = '<i class="bi bi-eye me-2"></i> Предварительный просмотр';
        });
    });
    
    // Отправка формы импорта
    importForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const brandId = document.getElementById('brand_id').value;
        const fileInput = document.getElementById('excel_file');
        
        if (!fileInput.files.length) {
            showToast('Выберите файл для импорта', 'warning');
            return;
        }
        
        if (!brandId) {
            showToast('Выберите бренд', 'warning');
            return;
        }
        
        // Показываем прогресс-бар
        importFormDiv.classList.add('d-none');
        importProgress.classList.remove('d-none');
        
        // Сбрасываем прогресс
        progressBar.style.width = '0%';
        progressPercent.textContent = '0%';
        
        // Начинаем импорт
        startImport(formData);
    });
    
    function startImport(formData) {
        fetch('{{ route("admin.price.import.process") }}', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateProgress(data.results);
                
                if (data.results.processing) {
                    // Если импорт еще идет, продолжаем проверять статус
                    checkImportStatus(data.results);
                } else {
                    // Импорт завершен
                    completeImport(data.results);
                }
            } else {
                handleImportError(data.message, data.errors);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            handleImportError('Ошибка при импорте: ' + error.message);
        });
    }
    
    function checkImportStatus(results) {
        // Здесь можно реализовать периодическую проверку статуса
        // Для простоты используем setTimeout
        setTimeout(() => {
            updateProgress(results);
            
            if (results.processing) {
                // Симулируем прогресс
                const newProgress = Math.min(results.progress + 10, 100);
                results.progress = newProgress;
                checkImportStatus(results);
            } else {
                completeImport(results);
            }
        }, 1000);
    }
    
    function updateProgress(results) {
        const progress = results.progress || 0;
        progressBar.style.width = progress + '%';
        progressPercent.textContent = progress + '%';
        
        // Обновляем статистику
        updateStats(results);
        
        // Показываем ошибки, если есть
        if (results.errors && results.errors.length > 0) {
            importErrors.classList.remove('d-none');
            errorList.innerHTML = results.errors
                .map(error => `<div class="small">${error}</div>`)
                .join('');
        }
    }
    
    function updateStats(results) {
        const statsHtml = `
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-primary">${results.items_processed || 0}</div>
                    <div class="stat-label">Обработано</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-success">${results.items_created || 0}</div>
                    <div class="stat-label">Создано</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-warning">${results.items_updated || 0}</div>
                    <div class="stat-label">Обновлено</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-value text-info">${results.symptoms_matched || 0}</div>
                    <div class="stat-label">Совпадений</div>
                </div>
            </div>
        `;
        
        importStats.innerHTML = statsHtml;
    }
    
    function completeImport(results) {
        progressBar.classList.remove('progress-bar-animated');
        progressBar.classList.remove('bg-success');
        progressBar.classList.add('bg-info');
        
        // Показываем результаты в модальном окне
        showResultsModal(results);
    }
    
    function handleImportError(message, errors = null) {
        importProgress.classList.add('d-none');
        importFormDiv.classList.remove('d-none');
        
        let errorMessage = message;
        if (errors) {
            errorMessage += '<br>' + Object.values(errors).flat().join('<br>');
        }
        
        showToast(errorMessage, 'error');
    }
    
    function showResultsModal(results) {
        const resultsContent = document.getElementById('resultsContent');
        
        let errorsHtml = '';
        if (results.errors && results.errors.length > 0) {
            errorsHtml = `
                <div class="alert alert-danger">
                    <h6><i class="bi bi-exclamation-triangle me-2"></i> Ошибки:</h6>
                    <ul class="mb-0">
                        ${results.errors.map(error => `<li class="small">${error}</li>`).join('')}
                    </ul>
                </div>
            `;
        }
        
        resultsContent.innerHTML = `
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-success">
                        <h5 class="alert-heading">
                            <i class="bi bi-check-circle me-2"></i> Импорт прайс-листа завершен
                        </h5>
                        <p class="mb-0">
                            Бренд: <strong>${results.brand_name || 'Не указан'}</strong>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="text-center p-3 border rounded bg-light">
                        <div class="h2 text-primary">${results.items_processed || 0}</div>
                        <div class="text-muted">Обработано</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 border rounded bg-light">
                        <div class="h2 text-success">${results.items_created || 0}</div>
                        <div class="text-muted">Создано</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 border rounded bg-light">
                        <div class="h2 text-warning">${results.items_updated || 0}</div>
                        <div class="text-muted">Обновлено</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 border rounded bg-light">
                        <div class="h2 text-info">${results.symptoms_matched || 0}</div>
                        <div class="text-muted">Совпадений</div>
                    </div>
                </div>
            </div>
            
            ${errorsHtml}
            
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Информация:</strong>
                <ul class="mb-0 mt-2">
                    <li>SKU используется как уникальный идентификатор</li>
                    <li>При повторной загрузке обновляются только количество и цена</li>
                    <li>Система автоматически ищет совпадения с симптомами диагностики</li>
                </ul>
            </div>
        `;
        
        resultsModal.show();
    }
    
    function displayPreview(data) {
        previewContainer.classList.remove('d-none');
        noPreview.classList.add('d-none');
        
        // Обновляем общую информацию
        document.getElementById('totalRows').textContent = data.total_rows;
        document.getElementById('totalColumns').textContent = data.total_columns;
        
        // Заголовки таблицы
        const headersHtml = data.headers.map(header => {
            const fieldClass = header.suggested_field !== 'unknown' ? 'preview-highlight' : '';
            return `<th class="${fieldClass}" title="Предполагаемое поле: ${header.suggested_field}">
                ${header.value || '(пусто)'}
                <br>
                <small class="text-muted">${header.column}</small>
            </th>`;
        }).join('');
        
        document.getElementById('previewHeaders').innerHTML = `
            <tr>
                <th>#</th>
                ${headersHtml}
            </tr>
        `;
        
        // Данные таблицы
        const bodyHtml = data.preview.map((row, index) => {
            const cells = data.headers.map(header => {
                const value = row[header.column] || '';
                return `<td>${value}</td>`;
            }).join('');
            
            return `<tr>
                <td class="text-muted">${index + 1}</td>
                ${cells}
            </tr>`;
        }).join('');
        
        document.getElementById('previewBody').innerHTML = bodyHtml;
    }
    
    // Обработчик кнопки "Перейти к прайс-листу"
    viewPriceListBtn.addEventListener('click', function() {
        window.location.href = '{{ route("admin.price.index") }}';
    });
    
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