<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">🚗 Импорт для конкретного автомобиля</h6>
            </div>
            <div class="card-body text-center py-5">
                <i class="bi bi-car-front display-1 text-primary mb-3"></i>
                <h4 class="mb-3">Выберите марку и модель</h4>
                <p class="text-muted mb-4">
                    Импортируйте симптомы и привяжите их к конкретной марке/модели автомобиля
                </p>
                <a href="{{ route('admin.symptoms.import.select') }}" 
                   class="btn btn-primary btn-lg">
                    <i class="bi bi-arrow-right me-2"></i> Начать импорт
                </a>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="bi bi-check-circle me-1"></i>
                        Не нужно указывать бренд в XLSX/CSV файле
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">🤖 Автоматический импорт</h6>
            </div>
            <div class="card-body text-center py-5">
                <i class="bi bi-robot display-1 text-info mb-3"></i>
                <h4 class="mb-3">Импорт из полного файла</h4>
                <p class="text-muted mb-4">
                    Импортируйте полный XLSX/CSV файл с брендами и моделями в колонках
                </p>
                <button type="button" 
                        class="btn btn-info btn-lg"
                        onclick="showAutoImportForm()">
                    <i class="bi bi-upload me-2"></i> Автоимпорт
                </button>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Требует правильных названий брендов в файле
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Форма автоматического импорта (скрыта по умолчанию) -->
<div id="autoImportForm" class="card d-none mt-4">
    <div class="card-header">
        <h6 class="mb-0">Автоматический импорт из XLSX/CSV</h6>
    </div>
    <div class="card-body">
        <form id="autoImportFormContent" enctype="multipart/form-data">
            @csrf
            
            <div class="mb-3">
                <label for="csv_file" class="form-label">Файл (XLSX или CSV)</label>
                <input type="file" 
                       class="form-control" 
                       id="csv_file" 
                       name="csv_file"
                       accept=".xlsx,.xls,.csv,.txt"
                       required>
                <div class="form-text">
                    Поддерживаемые форматы: .xlsx, .xls, .csv (макс. 10MB)
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" 
                               type="checkbox" 
                               id="auto_update_existing" 
                               name="update_existing"
                               checked>
                        <label class="form-check-label" for="auto_update_existing">
                            Обновлять существующие записи
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" 
                               type="checkbox" 
                               id="skip_unknown_brands" 
                               name="skip_unknown_brands"
                               checked>
                        <label class="form-check-label" for="skip_unknown_brands">
                            Пропускать неизвестные бренды
                        </label>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100" id="autoImportBtn">
                <i class="bi bi-upload me-2"></i> Начать автоматический импорт
            </button>
        </form>
        
        <div class="progress mt-3 d-none" id="autoProgressBar" style="height: 20px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                 role="progressbar" 
                 style="width: 0%"></div>
        </div>
    </div>
</div>

<script>
function showAutoImportForm() {
    document.getElementById('autoImportForm').classList.remove('d-none');
    document.getElementById('autoImportForm').scrollIntoView({ behavior: 'smooth' });
}

// Обработка автоматического импорта
document.getElementById('autoImportFormContent')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const importBtn = document.getElementById('autoImportBtn');
    const progressBar = document.getElementById('autoProgressBar');
    const progressBarInner = progressBar.querySelector('.progress-bar');
    
    const originalBtnText = importBtn.innerHTML;
    
    // Блокируем кнопку
    importBtn.disabled = true;
    importBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i> Импорт...';
    
    // Показываем прогресс-бар
    progressBar.classList.remove('d-none');
    progressBarInner.style.width = '10%';
    
    try {
        const response = await fetch('{{ route("admin.symptoms.import.auto") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
        
        progressBarInner.style.width = '50%';
        
        const data = await response.json();
        
        progressBarInner.style.width = '100%';
        
        if (data.success) {
            showImportResults(data.results);
            showToast('Автоматический импорт завершен!', 'success');
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

function showImportResults(results) {
    // Создаем модальное окно с результатами
    let html = `
        <div class="modal fade" id="importResultsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Результаты импорта</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-success">
                            <strong>Импорт завершен!</strong> Обработано ${results.total_rows} строк.
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-3 col-6 mb-2">
                                <div class="card text-center bg-success text-white">
                                    <div class="card-body p-2">
                                        <h4 class="mb-0">${results.symptoms_created}</h4>
                                        <small>Новых симптомов</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-2">
                                <div class="card text-center bg-info text-white">
                                    <div class="card-body p-2">
                                        <h4 class="mb-0">${results.symptoms_updated}</h4>
                                        <small>Обновлено симптомов</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-2">
                                <div class="card text-center bg-primary text-white">
                                    <div class="card-body p-2">
                                        <h4 class="mb-0">${results.rules_created}</h4>
                                        <small>Новых правил</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-2">
                                <div class="card text-center bg-warning text-white">
                                    <div class="card-body p-2">
                                        <h4 class="mb-0">${results.rules_updated}</h4>
                                        <small>Обновлено правил</small>
                                    </div>
                                </div>
                            </div>
                        </div>
    `;
    
    if (results.unknown_brands && results.unknown_brands.length > 0) {
        html += `
            <div class="alert alert-warning">
                <h6>Неизвестные бренды (${results.unknown_brands.length}):</h6>
                <div style="max-height: 150px; overflow-y: auto;">
                    ${results.unknown_brands.slice(0, 10).map(brand => 
                        `<div class="badge bg-light text-dark me-1 mb-1">${brand}</div>`
                    ).join('')}
                    ${results.unknown_brands.length > 10 ? 
                        `<div class="text-muted small">... и еще ${results.unknown_brands.length - 10} брендов</div>` : ''}
                </div>
            </div>
        `;
    }
    
    if (results.errors && results.errors.length > 0) {
        html += `
            <div class="alert alert-danger">
                <h6>Ошибки (${results.errors.length}):</h6>
                <div style="max-height: 200px; overflow-y: auto;">
        `;
        
        results.errors.slice(0, 10).forEach(error => {
            html += `<div class="small mb-1 text-danger">${error}</div>`;
        });
        
        if (results.errors.length > 10) {
            html += `<div class="text-muted small">... и еще ${results.errors.length - 10} ошибок</div>`;
        }
        
        html += `
                </div>
            </div>
        `;
    }
    
    html += `
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Добавляем модальное окно в DOM и показываем
    const modalContainer = document.createElement('div');
    modalContainer.innerHTML = html;
    document.body.appendChild(modalContainer);
    
    const modal = new bootstrap.Modal(document.getElementById('importResultsModal'));
    modal.show();
    
    // Удаляем модальное окно после закрытия
    document.getElementById('importResultsModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
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