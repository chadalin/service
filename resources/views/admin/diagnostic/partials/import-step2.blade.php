<div class="row">
    <div class="col-md-4">
        <!-- Информация о выбранном автомобиле -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Выбранный автомобиль</h6>
            </div>
            <div class="card-body text-center">
                <i class="bi bi-car-front-fill display-1 text-primary mb-3"></i>
                <h5 id="selectedBrandName">Загрузка...</h5>
                <p id="selectedModelInfo" class="text-muted">Загрузка...</p>
                <div class="mt-3">
                    <a href="{{ route('admin.symptoms.import.select') }}" 
                       class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Изменить выбор
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Статистика -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Статистика базы</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 mb-2">
                        <div class="text-center p-2 bg-light rounded">
                            <div class="h4 mb-0" id="statsSymptoms">0</div>
                            <small class="text-muted">Симптомов</small>
                        </div>
                    </div>
                    <div class="col-6 mb-2">
                        <div class="text-center p-2 bg-light rounded">
                            <div class="h4 mb-0" id="statsRules">0</div>
                            <small class="text-muted">Правил</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <!-- Форма загрузки файла -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Загрузка файла (XLSX/CSV)</h6>
            </div>
            <div class="card-body">
                <form id="importForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="brand_id" id="importBrandId">
                    <input type="hidden" name="model_id" id="importModelId">
                    <input type="hidden" name="update_existing" id="importUpdateExisting">
                    
                    <div class="alert alert-info mb-4">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Внимание:</strong> Все симптомы из файла будут привязаны к выбранному автомобилю.
                        <div class="mt-2">
                            <strong>Минимальный формат файла:</strong><br>
                            1. symptom_name, 2. symptom_description, 3. symptom_slug (опционально)
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="csv_file" class="form-label">Файл с симптомами (XLSX или CSV)</label>
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
    
                    <button type="submit" class="btn btn-primary btn-lg w-100" id="importBtn">
                        <i class="bi bi-upload me-2"></i> Начать импорт
                    </button>
                    
                    <div class="progress mt-3 d-none" id="progressBar" style="height: 20px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" 
                             style="width: 0%"></div>
                    </div>
                </form>
                
                <div class="mt-4 text-center">
                    <a href="{{ route('admin.symptoms.import.template') }}" 
                       class="btn btn-outline-success">
                        <i class="bi bi-download me-2"></i> Скачать шаблон (CSV)
                    </a>
                    <button type="button" class="btn btn-outline-info ms-2" onclick="downloadExcelTemplate()">
                        <i class="bi bi-download me-2"></i> Скачать шаблон (XLSX)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Результаты импорта -->
<div class="card d-none mt-4" id="resultsCard">
    <div class="card-header">
        <h6 class="mb-0">Результаты импорта</h6>
    </div>
    <div class="card-body">
        <div id="importResults"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Загружаем сохраненные данные из localStorage
    const brandId = localStorage.getItem('import_brand_id');
    const brandName = localStorage.getItem('import_brand_name');
    const modelId = localStorage.getItem('import_model_id');
    const modelName = localStorage.getItem('import_model_name');
    const updateExisting = localStorage.getItem('import_update_existing') === 'true';
    
    if (brandId && brandName) {
        const brandNameElement = document.getElementById('selectedBrandName');
        const brandIdInput = document.getElementById('importBrandId');
        const updateExistingInput = document.getElementById('importUpdateExisting');
        const modelInfoElement = document.getElementById('selectedModelInfo');
        const modelIdInput = document.getElementById('importModelId');
        
        if (brandNameElement) brandNameElement.textContent = brandName;
        if (brandIdInput) brandIdInput.value = brandId;
        if (updateExistingInput) updateExistingInput.value = updateExisting ? '1' : '0';
        
        if (modelId && modelName) {
            if (modelInfoElement) {
                modelInfoElement.innerHTML = `
                    <small class="text-muted">Модель:</small><br>
                    <strong>${modelName}</strong>
                `;
            }
            if (modelIdInput) modelIdInput.value = modelId;
        } else {
            if (modelInfoElement) {
                modelInfoElement.innerHTML = `
                    <small class="text-muted">Все модели марки</small>
                `;
            }
        }
        
        // Загружаем статистику
        loadStats();
    } else {
        // Если нет сохраненных данных, возвращаем на шаг 1
        const selectRoute = '{{ route("admin.symptoms.import.select") }}';
        if (selectRoute) {
            window.location.href = selectRoute;
        } else {
            console.error('Route not defined');
        }
    }


      // В обработчике submit формы добавьте:
        console.log('Brand ID value:', document.getElementById('importBrandId').value);
        console.log('Brand ID type:', typeof document.getElementById('importBrandId').value);
        console.log('Parsed as int:', parseInt(document.getElementById('importBrandId').value));

    // Обработка формы импорта
    const importForm = document.getElementById('importForm');
    if (importForm) {
        importForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Проверка файла
            const fileInput = document.getElementById('csv_file');
            if (!fileInput || !fileInput.files.length) {
                showAlert('Выберите файл для загрузки!', 'warning');
                return;
            }
            
            const file = fileInput.files[0];
            if (!file.name.match(/\.(xlsx|xls)$/i)) {
                showAlert('Файл должен быть в формате Excel (.xlsx или .xls)', 'warning');
                return;
            }
            
            // Проверка размера файла (10MB = 10 * 1024 * 1024)
            if (file.size > 10 * 1024 * 1024) {
                showAlert('Размер файла не должен превышать 10MB', 'warning');
                return;
            }
            
            // Отладка
            console.log('File selected:', file.name, 'Size:', file.size, 'Type:', file.type);
            
            const formData = new FormData(this);
            const importBtn = document.getElementById('importBtn');
            const progressBar = document.getElementById('progressBar');
            const progressBarInner = progressBar ? progressBar.querySelector('.progress-bar') : null;
            const resultsCard = document.getElementById('resultsCard');
            const importResults = document.getElementById('importResults');
            
            if (!importBtn) {
                console.error('Import button not found');
                return;
            }
            
            const originalBtnText = importBtn.innerHTML;
            
            // Блокируем кнопку
            importBtn.disabled = true;
            importBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i> Импорт...';
            
            // Показываем прогресс-бар
            if (progressBar) {
                progressBar.classList.remove('d-none');
                updateProgress(10, progressBarInner);
            }
            
            try {
                const response = await fetch('{{ route("admin.symptoms.import.brand-model") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                
                if (progressBarInner) updateProgress(50, progressBarInner);
                
                const data = await response.json();
                
                if (progressBarInner) updateProgress(100, progressBarInner);
                
                console.log('Server response:', data);
                
                if (data.success) {
                    // Показываем результаты
                    if (resultsCard) {
                        resultsCard.classList.remove('d-none');
                    }
                    if (importResults && data.results) {
                        displayResults(data.results, importResults);
                    }
                    
                    // Обновляем статистику
                    loadStats();
                    
                    // Уведомление
                    showAlert('Импорт успешно завершен!', 'success');
                } else {
                    console.error('Import error response:', data);
                    
                    let errorMessage = 'Ошибка импорта';
                    if (data.message) {
                        errorMessage += ': ' + data.message;
                    }
                    
                    if (data.errors) {
                        errorMessage += '\n' + Object.values(data.errors).flat().join('\n');
                    }
                    
                    showAlert(errorMessage, 'danger');
                    
                    // Показываем ошибки если есть
                    if (importResults && data.errors) {
                        displayErrors(data.errors, importResults);
                    }
                }
                
            } catch (error) {
                console.error('Network error:', error);
                showAlert('Ошибка сети: ' + error.message, 'danger');
            } finally {
                // Восстанавливаем кнопку
                importBtn.disabled = false;
                importBtn.innerHTML = originalBtnText;
                
                // Скрываем прогресс-бар через 1 секунду
                if (progressBar) {
                    setTimeout(() => {
                        progressBar.classList.add('d-none');
                        if (progressBarInner) {
                            progressBarInner.style.width = '0%';
                            progressBarInner.textContent = '';
                        }
                    }, 1000);
                }
            }
        });
    } else {
        console.error('Import form not found');
    }
});

function updateProgress(percent, progressBarInner) {
    if (!progressBarInner) return;
    
    progressBarInner.style.width = percent + '%';
    progressBarInner.textContent = percent + '%';
}

function displayResults(results, container) {
    if (!container) return;
    
    let html = `
        <div class="alert alert-success">
            <strong>Импорт завершен для ${results.brand_name}!</strong>
            ${results.model_name !== 'Все модели' ? `<br>Модель: ${results.model_name}` : '<br>Все модели марки'}
            <br>Обработано ${results.total_rows} строк.
            ${results.skipped_rows > 0 ? `<br>Пропущено: ${results.skipped_rows} строк` : ''}
        </div>
        
        <div class="row mb-4">
            <div class="col-md-3 col-6 mb-2">
                <div class="card text-center bg-success text-white">
                    <div class="card-body p-2">
                        <h4 class="mb-0">${results.symptoms_created || 0}</h4>
                        <small>Новых симптомов</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="card text-center bg-info text-white">
                    <div class="card-body p-2">
                        <h4 class="mb-0">${results.symptoms_updated || 0}</h4>
                        <small>Обновлено симптомов</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="card text-center bg-primary text-white">
                    <div class="card-body p-2">
                        <h4 class="mb-0">${results.rules_created || 0}</h4>
                        <small>Новых правил</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="card text-center bg-warning text-white">
                    <div class="card-body p-2">
                        <h4 class="mb-0">${results.rules_updated || 0}</h4>
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
        
        results.errors.slice(0, 10).forEach(error => {
            html += `<div class="error-item mb-1 p-2 border-bottom">${error}</div>`;
        });
        
        if (results.errors.length > 10) {
            html += `<div class="text-muted small mt-2">... и еще ${results.errors.length - 10} ошибок</div>`;
        }
        
        html += `
                </div>
            </div>
        `;
    }
    
    container.innerHTML = html;
}

function displayErrors(errors, container) {
    if (!container) return;
    
    let html = '<div class="alert alert-danger"><h6>Ошибки валидации:</h6><ul>';
    
    Object.entries(errors).forEach(([field, messages]) => {
        messages.forEach(message => {
            html += `<li><strong>${field}:</strong> ${message}</li>`;
        });
    });
    
    html += '</ul></div>';
    container.innerHTML = html;
    
    // Показываем контейнер с результатами
    const resultsCard = document.getElementById('resultsCard');
    if (resultsCard) {
        resultsCard.classList.remove('d-none');
    }
}

async function loadStats() {
    try {
        const brandId = document.getElementById('importBrandId')?.value;
        const modelId = document.getElementById('importModelId')?.value;
        
        let url = '/admin/diagnostic/stats';
        const params = new URLSearchParams();
        if (brandId) params.append('brand_id', brandId);
        if (modelId) params.append('model_id', modelId);
        
        if (params.toString()) {
            url += '?' + params.toString();
        }
        
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            const symptomsElement = document.getElementById('statsSymptoms');
            const rulesElement = document.getElementById('statsRules');
            
            if (symptomsElement) symptomsElement.textContent = data.symptoms || 0;
            if (rulesElement) rulesElement.textContent = data.rules || 0;
        }
    } catch (error) {
        console.error('Error loading stats:', error);
        // Не показываем ошибку пользователю, это не критично
    }
}

function downloadExcelTemplate() {
    try {
        // Используем ваш маршрут для скачивания шаблона
        const templateRoute = '{{ route("admin.symptoms.import.template") }}';
        if (templateRoute && templateRoute !== '') {
            window.location.href = templateRoute;
        } else {
            // Fallback: создаем простой CSV
            const csvContent = "symptom_name;symptom_description;symptom_slug;diagnostic_steps;possible_causes;required_data;complexity_level;estimated_time;consultation_price\n" +
                              "Не заводится двигатель;Двигатель не запускается при повороте ключа;engine-not-starting;Проверить аккумулятор; Проверить стартер; Проверить топливную систему;Разряженный аккумулятор; Неисправный стартер; Проблемы с топливным насосом;Напряжение аккумулятора; Состояние стартера; Давление топлива;3;120;3500\n" +
                              "Стук в двигателе;Металлический стук при работе двигателя;engine-knocking;Проверить уровень масла; Диагностика датчика детонации; Проверить свечи зажигания;Низкий уровень масла; Неисправный датчик детонации; Износ шатунных вкладышей;Уровень масла; Коды ошибок; Звук стука;4;180;4500";
            
            const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', 'symptoms_template.csv');
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            showAlert('Шаблон скачан в формате CSV', 'info');
        }
    } catch (error) {
        console.error('Error downloading template:', error);
        showAlert('Ошибка при скачивании шаблона', 'danger');
    }
}

// Простая функция для показа уведомлений (замена toast)
function showAlert(message, type = 'info') {
    // Удаляем старые уведомления
    const oldAlerts = document.querySelectorAll('.import-alert');
    oldAlerts.forEach(alert => alert.remove());
    
    // Создаем новое уведомление
    const alertId = 'alert-' + Date.now();
    const alertHtml = `
        <div id="${alertId}" class="alert alert-${type} import-alert alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px;">
            ${message}
            <button type="button" class="btn-close" onclick="document.getElementById('${alertId}').remove()"></button>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', alertHtml);
    
    // Автоматическое удаление через 5 секунд
    setTimeout(() => {
        const alertElement = document.getElementById(alertId);
        if (alertElement) {
            alertElement.remove();
        }
    }, 5000);
}

// Альтернативная функция для toast (если Bootstrap Toast работает)
function showToast(message, type = 'info') {
    try {
        // Проверяем, доступен ли Bootstrap Toast
        if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
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
        } else {
            // Fallback на простой alert
            showAlert(message, type);
        }
    } catch (error) {
        console.error('Toast error:', error);
        showAlert(message, type);
    }
}
</script>