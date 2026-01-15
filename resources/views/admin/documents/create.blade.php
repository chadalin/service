<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoDoc AI - Загрузка документа</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Стили для сайдбара */
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
        }
        .sidebar .nav-link {
            color: #fff;
        }
        .sidebar .nav-link:hover {
            background-color: #495057;
        }
        .sidebar .nav-link.active {
            background-color: #007bff;
        }
        
        /* Стили для загрузки */
        .border-dashed {
            border-style: dashed !important;
        }
        
        #dropArea.drag-over {
            background-color: rgba(13, 110, 253, 0.1) !important;
            border-color: #0d6efd !important;
        }
        
        .progress-bar .progress-text {
            position: absolute;
            left: 0;
            right: 0;
            text-align: center;
            color: #000;
            font-size: 14px;
            text-shadow: 0 0 2px white;
            line-height: 25px;
        }
        
        .progress {
            overflow: visible;
            position: relative;
            height: 25px;
        }
        
        /* Анимация спиннера */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .spinner-border {
            animation: spin 0.75s linear infinite;
        }
        
        /* Кнопка "Начать загрузку" всегда активна, когда есть файл */
        #startUploadBtn:not(:disabled) {
            background-color: #198754 !important;
            border-color: #198754 !important;
            opacity: 1 !important;
            cursor: pointer !important;
        }
        
        /* Кнопка "Загрузить документ" всегда активна */
        #submitBtn {
            opacity: 1 !important;
            cursor: pointer !important;
        }
        
        /* Показываем все кнопки управления */
        #pauseUploadBtn, 
        #resumeUploadBtn, 
        #cancelUploadBtn {
            display: inline-block !important;
            opacity: 1 !important;
            visibility: visible !important;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    @if(auth()->check())
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>AutoDoc AI</span>
                    </h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" 
                               href="{{ route('admin.dashboard') }}">
                                📊 Дашборд
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.documents.*') ? 'active' : '' }}" 
                               href="{{ route('admin.documents.index') }}">
                                📎 Документы
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('chat.index') ? 'active' : '' }}" 
                               href="{{ route('chat.index') }}">
                                🔍 Умный поиск
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('diagnostic.*') ? 'active' : '' }}" 
                               href="{{ route('diagnostic.start') }}">
                                🔧 Диагностика
                            </a>
                        </li>
                    </ul>
                    
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Администрирование</span>
                    </h6>
                    <ul class="nav flex-column mb-2">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" 
                               href="{{ route('admin.categories.index') }}">
                                📂 Категории
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('cars.*') ? 'active' : '' }}" 
                               href="{{ route('admin.cars.import') }}">
                                🚗 Автомобили
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.search.*') ? 'active' : '' }}" 
                               href="{{ route('admin.search.index') }}">
                                🔎 Поиск по документам
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.diagnostic.*') ? 'active' : '' }}" 
                               href="{{ route('admin.diagnostic.symptoms.index') }}">
                                ⚙️ Диагностика (админ)
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Загрузка документа</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <span class="me-2">{{ auth()->user()->name }}</span>
                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary">Выйти</button>
                        </form>
                    </div>
                </div>
                
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                
                <!-- Контент загрузки документа -->
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-upload me-2"></i> Загрузка документа
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <!-- Простая форма для маленьких файлов -->
                                    <form id="simpleUploadForm" action="{{ route('admin.documents.store') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        
                                        <!-- Название документа -->
                                        <div class="mb-4">
                                            <label for="title" class="form-label fw-bold">Название документа *</label>
                                            <input type="text" 
                                                   name="title" 
                                                   id="title" 
                                                   value="{{ old('title') }}"
                                                   required
                                                   class="form-control @error('title') is-invalid @enderror"
                                                   placeholder="Например: Руководство по ремонту двигателя 2.0 TDI">
                                            @error('title')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Выбор бренда и модели -->
                                        <div class="row mb-4">
                                            <div class="col-md-6">
                                                <label for="brand_id" class="form-label fw-bold">Марка автомобиля *</label>
                                                <select name="brand_id" 
                                                        id="brand_id" 
                                                        required
                                                        class="form-select @error('brand_id') is-invalid @enderror">
                                                    <option value="">Выберите марку</option>
                                                    @foreach($brands as $brand)
                                                        <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>
                                                            {{ $brand->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('brand_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6">
                                                <label for="car_model_id" class="form-label fw-bold">Модель *</label>
                                                <div class="input-group">
                                                    <select name="car_model_id" 
                                                            id="car_model_id" 
                                                            required
                                                            disabled
                                                            class="form-select @error('car_model_id') is-invalid @enderror">
                                                        <option value="">Сначала выберите марку</option>
                                                    </select>
                                                    <span class="input-group-text">
                                                        <div id="modelSpinner" class="spinner-border spinner-border-sm text-primary d-none" role="status">
                                                            <span class="visually-hidden">Загрузка...</span>
                                                        </div>
                                                    </span>
                                                </div>
                                                @error('car_model_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- Категория ремонта -->
                                        <div class="mb-4">
                                            <label for="category_id" class="form-label fw-bold">Категория ремонта *</label>
                                            <select name="category_id" 
                                                    id="category_id" 
                                                    required
                                                    class="form-select @error('category_id') is-invalid @enderror">
                                                <option value="">Выберите категорию</option>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('category_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Переключатель метода загрузки -->
                                        <div class="mb-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="chunkedUploadToggle">
                                                <label class="form-check-label fw-bold" for="chunkedUploadToggle">
                                                    <i class="bi bi-file-earmark-break me-1"></i> Загрузить большой файл (более 50MB)
                                                </label>
                                                <div class="form-text">Включите для загрузки файлов до 500MB с прогресс-баром и возобновлением</div>
                                            </div>
                                        </div>

                                        <!-- Простая загрузка файла -->
                                        <div id="simpleUploadSection" class="mb-4">
                                            <label for="document" class="form-label fw-bold">Файл документа *</label>
                                            <input type="file" 
                                                   name="document" 
                                                   id="document" 
                                                   required
                                                   accept=".pdf,.doc,.docx,.txt"
                                                   class="form-control @error('document') is-invalid @enderror">
                                            <div class="form-text">
                                                <i class="bi bi-info-circle me-1"></i> 
                                                Поддерживаемые форматы: PDF, DOC, DOCX, TXT. Максимальный размер: 50MB
                                            </div>
                                            @error('document')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Чанковая загрузка файла -->
                                        <div id="chunkedUploadSection" class="mb-4" style="display: none;">
                                            <label class="form-label fw-bold">Загрузка большого файла *</label>
                                            
                                            <div class="card border-primary">
                                                <div class="card-body text-center">
                                                    <div id="dropArea" class="border-2 border-dashed border-primary rounded p-5 bg-light">
                                                        <i class="bi bi-cloud-upload display-4 text-primary mb-3"></i>
                                                        <h5 class="mb-3">Перетащите файл сюда или</h5>
                                                        <button type="button" id="chooseFileBtn" class="btn btn-primary btn-lg mb-3">
                                                            <i class="bi bi-folder2-open me-2"></i> Выбрать файл
                                                        </button>
                                                        <input type="file" 
                                                               id="chunkedFile" 
                                                               class="d-none"
                                                               accept=".pdf,.doc,.docx,.txt">
                                                        <p class="text-muted mb-0">
                                                            Максимальный размер: 500MB. Поддерживается возобновление загрузки
                                                        </p>
                                                    </div>
                                                    
                                                    <!-- Информация о файле -->
                                                    <div id="fileInfo" class="mt-3 text-start" style="display: none;">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <p><strong>Файл:</strong> <span id="fileName"></span></p>
                                                                <p><strong>Размер:</strong> <span id="fileSize"></span></p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <p><strong>Прогресс:</strong> <span id="progressPercent">0%</span></p>
                                                                <p><strong>Статус:</strong> <span id="uploadStatus" class="badge bg-secondary">Ожидание</span></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Прогресс-бар -->
                                                    <div id="progressSection" class="mt-3">
                                                        <div class="progress" style="height: 25px; display: none;">
                                                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                                                                 role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                                                <span class="progress-text fw-bold">0%</span>
                                                            </div>
                                                        </div>
                                                        <div class="mt-2 d-flex justify-content-between" style="display: none;">
                                                            <small><span id="uploadedSize">0 MB</span> из <span id="totalSize">0 MB</span></small>
                                                            <small>Скорость: <span id="uploadSpeed">0 KB/s</span></small>
                                                            <small>Осталось: <span id="timeRemaining">--:--</span></small>
                                                        </div>
                                                        
                                                        <!-- Кнопки управления -->
                                                        <div class="mt-3">
                                                            <button type="button" id="startUploadBtn" class="btn btn-success me-2" disabled>
                                                                <i class="bi bi-play-circle me-1"></i> Начать загрузку
                                                            </button>
                                                            <button type="button" id="pauseUploadBtn" class="btn btn-warning me-2" style="display: none;">
                                                                <i class="bi bi-pause-circle me-1"></i> Пауза
                                                            </button>
                                                            <button type="button" id="resumeUploadBtn" class="btn btn-info me-2" style="display: none;">
                                                                <i class="bi bi-arrow-clockwise me-1"></i> Продолжить
                                                            </button>
                                                            <button type="button" id="cancelUploadBtn" class="btn btn-danger" style="display: none;">
                                                                <i class="bi bi-x-circle me-1"></i> Отмена
                                                            </button>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Информация о загруженном файле -->
                                                    <div id="uploadCompleteInfo" class="mt-3" style="display: none;">
                                                        <div class="alert alert-success">
                                                            <i class="bi bi-check-circle me-2"></i>
                                                            Файл успешно загружен! Нажмите "Загрузить документ" для завершения.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Скрытые поля для чанковой загрузки -->
                                        <input type="hidden" id="uploadedFileName" name="uploaded_file_name">
                                        <input type="hidden" id="uploadedFilePath" name="uploaded_file_path">

                                        <!-- Сообщения об ошибках -->
                                        <div id="errorAlert" class="alert alert-danger alert-dismissible fade" role="alert" style="display: none;">
                                            <div id="errorMessage"></div>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>

                                        <!-- Сообщения об успехе -->
                                        <div id="successAlert" class="alert alert-success alert-dismissible fade" role="alert" style="display: none;">
                                            <div id="successMessage"></div>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>

                                        <!-- Кнопки -->
                                        <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                                            <a href="{{ route('admin.documents.index') }}" class="btn btn-secondary">
                                                <i class="bi bi-arrow-left me-1"></i> Назад к списку
                                            </a>
                                            <button type="submit" id="submitBtn" class="btn btn-primary">
                                                <i class="bi bi-upload me-1"></i> Загрузить документ
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                <div class="card-footer text-muted">
                                    <small>
                                        <i class="bi bi-lightbulb me-1"></i>
                                        Для быстрой загрузки маленьких файлов используйте обычную форму. Для больших файлов включите чанковую загрузку.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Конец контента загрузки документа -->
            </main>
        </div>
    </div>
    @else
        @yield('content')
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Скрипт загрузки документа инициализирован');
    
    // ==================== ПЕРЕМЕННЫЕ ====================
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const brandSelect = document.getElementById('brand_id');
    const modelSelect = document.getElementById('car_model_id');
    const modelSpinner = document.getElementById('modelSpinner');
    const chunkedUploadToggle = document.getElementById('chunkedUploadToggle');
    const simpleUploadSection = document.getElementById('simpleUploadSection');
    const chunkedUploadSection = document.getElementById('chunkedUploadSection');
    const simpleUploadForm = document.getElementById('simpleUploadForm');
    const submitBtn = document.getElementById('submitBtn');
    
    // Элементы чанковой загрузки
    const dropArea = document.getElementById('dropArea');
    const chooseFileBtn = document.getElementById('chooseFileBtn');
    const chunkedFileInput = document.getElementById('chunkedFile');
    const fileInfo = document.getElementById('fileInfo');
    const progressSection = document.getElementById('progressSection');
    const startUploadBtn = document.getElementById('startUploadBtn');
    const pauseUploadBtn = document.getElementById('pauseUploadBtn');
    const resumeUploadBtn = document.getElementById('resumeUploadBtn');
    const cancelUploadBtn = document.getElementById('cancelUploadBtn');
    const uploadCompleteInfo = document.getElementById('uploadCompleteInfo');
    const errorAlert = document.getElementById('errorAlert');
    const successAlert = document.getElementById('successAlert');
    
    // Переменные для загрузки
    let currentFile = null;
    let isChunkedMode = false;
    let isUploading = false;
    let isPaused = false;
    let uploadedChunks = [];
    let uploadStartTime = null;
    let uploadSpeedInterval = null;
    let uploadedFilePath = '';
    let uploadedFileName = '';
    let chunkSize = 5 * 1024 * 1024; // 5MB
    
    // Модели предзагружены из сервера
    let allModels = {};
    
    try {
        const modelsData = JSON.parse('<?php 
            $modelsArray = [];
            if(isset($models)) {
                foreach($models as $brandId => $brandModels) {
                    $modelsArray[$brandId] = [];
                    foreach($brandModels as $model) {
                        $modelsArray[$brandId][] = [
                            "id" => $model["id"] ?? $model->id ?? 0,
                            "name" => $model["name"] ?? $model->name ?? "Модель",
                            "year_from" => $model["year_from"] ?? $model->year_from ?? null,
                            "year_to" => $model["year_to"] ?? $model->year_to ?? null
                        ];
                    }
                }
            }
            echo json_encode($modelsArray, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        ?>');
        allModels = modelsData;
        console.log('Модели загружены:', Object.keys(allModels).length, 'брендов');
    } catch (e) {
        console.error('Ошибка парсинга моделей:', e);
        allModels = {};
    }
    
    // ==================== ЗАГРУЗКА МОДЕЛЕЙ ====================
    
    if (brandSelect) {
        brandSelect.addEventListener('change', function() {
            const brandId = this.value;
            console.log('Выбрана марка:', brandId);
            
            if (!brandId) {
                modelSelect.innerHTML = '<option value="">Сначала выберите марку</option>';
                modelSelect.disabled = true;
                if (modelSpinner) modelSpinner.classList.add('d-none');
                return;
            }
            
            // Показываем спиннер
            if (modelSpinner) modelSpinner.classList.remove('d-none');
            modelSelect.innerHTML = '<option value="">Загрузка моделей...</option>';
            modelSelect.disabled = true;
            
            // Используем предзагруженные данные
            setTimeout(() => {
                populateModelSelect(brandId);
            }, 100);
        });
    }
    
    function populateModelSelect(brandId) {
        console.log('Загрузка моделей для бренда:', brandId);
        
        const models = allModels[brandId];
        
        if (!models || models.length === 0) {
            console.log('Нет моделей для бренда:', brandId);
            modelSelect.innerHTML = '<option value="">Нет доступных моделей</option>';
            modelSelect.disabled = false;
            if (modelSpinner) modelSpinner.classList.add('d-none');
            return;
        }
        
        let options = '<option value="">Выберите модель</option>';
        models.forEach(model => {
            const displayName = model.name || 'Модель ' + model.id;
            let yearInfo = '';
            if (model.year_from) {
                yearInfo = model.year_to ? 
                    ` (${model.year_from}-${model.year_to})` : 
                    ` (${model.year_from}-н.в.)`;
            }
            options += `<option value="${model.id}">${displayName}${yearInfo}</option>`;
        });
        
        modelSelect.innerHTML = options;
        modelSelect.disabled = false;
        if (modelSpinner) modelSpinner.classList.add('d-none');
        
        // Восстанавливаем выбранное значение
        <?php if(old('car_model_id')): ?>
            setTimeout(() => {
                const oldModelId = <?php echo old('car_model_id'); ?>;
                modelSelect.value = oldModelId;
            }, 50);
        <?php endif; ?>
    }
    
    // Инициализация при загрузке страницы
    <?php if(old('brand_id')): ?>
        setTimeout(() => {
            const oldBrandId = <?php echo old('brand_id'); ?>;
            console.log('Восстановление марки:', oldBrandId);
            if (brandSelect) {
                brandSelect.value = oldBrandId;
                const changeEvent = new Event('change');
                brandSelect.dispatchEvent(changeEvent);
                
                setTimeout(() => {
                    <?php if(old('car_model_id')): ?>
                        const oldModelId = <?php echo old('car_model_id'); ?>;
                        console.log('Восстановление модели:', oldModelId);
                        if (modelSelect) {
                            modelSelect.value = oldModelId;
                        }
                    <?php endif; ?>
                }, 300);
            }
        }, 200);
    <?php endif; ?>
    
    // ==================== ПЕРЕКЛЮЧЕНИЕ РЕЖИМОВ ЗАГРУЗКИ ====================
    
    if (chunkedUploadToggle) {
        chunkedUploadToggle.addEventListener('change', function() {
            isChunkedMode = this.checked;
            console.log('Режим чанковой загрузки:', isChunkedMode ? 'ВКЛ' : 'ВЫКЛ');
            
            if (isChunkedMode) {
                // Отключаем простое поле файла
                const simpleFileInput = document.getElementById('document');
                if (simpleFileInput) {
                    simpleFileInput.removeAttribute('required');
                    simpleFileInput.disabled = true;
                }
                
                simpleUploadSection.style.display = 'none';
                chunkedUploadSection.style.display = 'block';
                submitBtn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i> Загрузить документ';
                // Кнопка всегда активна
                submitBtn.disabled = false;
            } else {
                // Включаем простое поле файла
                const simpleFileInput = document.getElementById('document');
                if (simpleFileInput) {
                    simpleFileInput.setAttribute('required', 'required');
                    simpleFileInput.disabled = false;
                }
                
                simpleUploadSection.style.display = 'block';
                chunkedUploadSection.style.display = 'none';
                submitBtn.innerHTML = '<i class="bi bi-upload me-1"></i> Загрузить документ';
                submitBtn.disabled = false;
                resetChunkedUpload();
            }
        });
    }
    
    // ==================== ВЫБОР ФАЙЛА ====================
    
    if (chooseFileBtn) {
        chooseFileBtn.addEventListener('click', () => {
            console.log('Кнопка выбора файла нажата');
            if (chunkedFileInput) chunkedFileInput.click();
        });
    }
    
    if (chunkedFileInput) {
        chunkedFileInput.addEventListener('change', function(e) {
            console.log('Файл выбран:', this.files.length);
            if (this.files.length > 0) {
                handleFileSelect(this.files[0]);
            }
        });
    }
    
    // Drag & Drop
    if (dropArea) {
        dropArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropArea.classList.add('drag-over');
        });
        
        dropArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropArea.classList.remove('drag-over');
        });
        
        dropArea.addEventListener('drop', (e) => {
            e.preventDefault();
            dropArea.classList.remove('drag-over');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFileSelect(files[0]);
            }
        });
    }
    
    // ==================== ОБРАБОТКА ВЫБРАННОГО ФАЙЛА ====================
    
    function handleFileSelect(file) {
        console.log('Обработка файла:', file.name);
        
        // Проверка размера (макс. 500MB)
        const maxSize = 500 * 1024 * 1024;
        if (file.size > maxSize) {
            showError('Файл слишком большой. Максимальный размер: 500MB');
            return;
        }
        
        // Проверка типа
        const allowedExtensions = /\.(pdf|doc|docx|txt)$/i;
        if (!allowedExtensions.test(file.name)) {
            showError('Неподдерживаемый формат файла. Разрешены: PDF, DOC, DOCX, TXT');
            return;
        }
        
        currentFile = file;
        
        // Показываем информацию о файле
        if (document.getElementById('fileName')) {
            document.getElementById('fileName').textContent = file.name;
        }
        if (document.getElementById('fileSize')) {
            document.getElementById('fileSize').textContent = formatFileSize(file.size);
        }
        if (fileInfo) {
            fileInfo.style.display = 'block';
        }
        
        // Показываем прогресс-бар и информацию о скорости
        const progressBarContainer = document.querySelector('#progressSection .progress');
        const progressInfo = document.querySelector('#progressSection .mt-2.d-flex');
        if (progressBarContainer) progressBarContainer.style.display = 'block';
        if (progressInfo) progressInfo.style.display = 'flex';
        
        // Показываем кнопку "Отмена"
        if (cancelUploadBtn) {
            cancelUploadBtn.style.display = 'inline-block';
        }
        
        // Активируем кнопку "Начать загрузку"
        if (startUploadBtn) {
            startUploadBtn.disabled = false;
            startUploadBtn.classList.remove('disabled');
            startUploadBtn.style.opacity = '1';
            startUploadBtn.style.cursor = 'pointer';
            startUploadBtn.style.backgroundColor = '#198754';
            console.log('Кнопка "Начать загрузку" активирована');
        }
        
        console.log('Файл готов к загрузке:', file.name);
    }
    
    // ==================== УПРАВЛЕНИЕ ЗАГРУЗКОЙ ====================
    
    if (startUploadBtn) {
        startUploadBtn.addEventListener('click', startUpload);
    }
    
    if (pauseUploadBtn) {
        pauseUploadBtn.addEventListener('click', pauseUpload);
    }
    
    if (resumeUploadBtn) {
        resumeUploadBtn.addEventListener('click', resumeUpload);
    }
    
    if (cancelUploadBtn) {
        cancelUploadBtn.addEventListener('click', cancelUpload);
    }
    
    function startUpload() {
        console.log('=== НАЧАЛО ЗАГРУЗКИ ===');
        
        if (!currentFile) {
            showError('Сначала выберите файл');
            return;
        }
        
        // Проверяем заполнены ли обязательные поля
        if (!validateForm()) {
            showError('Заполните все обязательные поля');
            return;
        }
        
        isUploading = true;
        isPaused = false;
        uploadStartTime = Date.now();
        
        // Показываем кнопки управления
        if (startUploadBtn) {
            startUploadBtn.style.display = 'none';
        }
        if (pauseUploadBtn) {
            pauseUploadBtn.style.display = 'inline-block';
        }
        if (resumeUploadBtn) {
            resumeUploadBtn.style.display = 'none';
        }
        if (cancelUploadBtn) {
            cancelUploadBtn.style.display = 'inline-block';
        }
        
        // Обновляем статус
        updateStatus('Загрузка...', 'primary');
        
        console.log('Загрузка файла:', currentFile.name);
        
        // Начинаем загрузку
        uploadFile();
        
        // Запускаем обновление скорости
        uploadSpeedInterval = setInterval(updateUploadSpeed, 1000);
    }
    
    function pauseUpload() {
        console.log('Пауза загрузки');
        isPaused = true;
        
        if (pauseUploadBtn) {
            pauseUploadBtn.style.display = 'none';
        }
        if (resumeUploadBtn) {
            resumeUploadBtn.style.display = 'inline-block';
        }
        
        updateStatus('На паузе', 'warning');
    }
    
    function resumeUpload() {
        console.log('Продолжение загрузки');
        isPaused = false;
        
        if (resumeUploadBtn) {
            resumeUploadBtn.style.display = 'none';
        }
        if (pauseUploadBtn) {
            pauseUploadBtn.style.display = 'inline-block';
        }
        
        updateStatus('Загрузка...', 'primary');
        uploadFile();
    }
    
    function cancelUpload() {
        console.log('Отмена загрузки');
        isUploading = false;
        isPaused = false;
        clearInterval(uploadSpeedInterval);
        resetChunkedUpload();
        showInfo('Загрузка отменена');
    }
    
    // ==================== ЧАНКОВАЯ ЗАГРУЗКА ====================
    
    async function uploadFile() {
        if (!currentFile || !isUploading) {
            console.log('Загрузка остановлена');
            return;
        }
        
        const totalChunks = Math.ceil(currentFile.size / chunkSize);
        console.log(`Всего чанков: ${totalChunks}, размер файла: ${formatFileSize(currentFile.size)}`);
        
        // Определяем, с какого чанка начать
        let startChunk = 0;
        if (uploadedChunks.length > 0) {
            startChunk = Math.max(...uploadedChunks) + 1;
        }
        
        // Загружаем чанки последовательно
        for (let chunkIndex = startChunk; chunkIndex < totalChunks; chunkIndex++) {
            if (!isUploading || isPaused) {
                console.log('Загрузка прервана на чанке:', chunkIndex);
                break;
            }
            
            try {
                console.log(`Загрузка чанка ${chunkIndex + 1}/${totalChunks}`);
                await uploadChunk(chunkIndex, totalChunks);
                uploadedChunks.push(chunkIndex);
                
                // Обновляем прогресс
                const progress = (uploadedChunks.length / totalChunks) * 100;
                updateProgress(progress, chunkIndex * chunkSize);
                
                // Если это последний чанк, завершаем загрузку
                if (chunkIndex === totalChunks - 1) {
                    console.log('Последний чанк загружен');
                    completeUpload();
                }
            } catch (error) {
                console.error('Ошибка загрузки чанка:', error);
                showError(`Ошибка загрузки: ${error.message}`);
                pauseUpload();
                break;
            }
        }
    }
    
    async function uploadChunk(chunkIndex, totalChunks) {
        const start = chunkIndex * chunkSize;
        const end = Math.min(start + chunkSize, currentFile.size);
        const chunk = currentFile.slice(start, end);
        
        console.log(`Загрузка чанка ${chunkIndex}: байты ${start}-${end}`);
        
        const formData = new FormData();
        formData.append('file', chunk, `chunk_${chunkIndex}`);
        formData.append('chunkIndex', chunkIndex);
        formData.append('totalChunks', totalChunks);
        formData.append('fileName', currentFile.name);
        formData.append('fileSize', currentFile.size);
        formData.append('title', document.getElementById('title').value);
        formData.append('brand_id', document.getElementById('brand_id').value);
        formData.append('car_model_id', document.getElementById('car_model_id').value);
        formData.append('category_id', document.getElementById('category_id').value);
        
        try {
            const response = await fetch('/admin/documents/upload-chunk', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ошибка: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('Ответ сервера:', data);
            
            if (!data.success) {
                throw new Error(data.message || 'Ошибка загрузки чанка');
            }
            
            // Сохраняем путь к файлу
            if (data.file_path) {
                uploadedFilePath = data.file_path;
                uploadedFileName = currentFile.name;
            }
            
            return data;
        } catch (error) {
            console.error('Ошибка загрузки:', error);
            throw error;
        }
    }
    
    function completeUpload() {
        console.log('Загрузка завершена');
        clearInterval(uploadSpeedInterval);
        isUploading = false;
        
        // Обновляем UI
        updateStatus('Завершено', 'success');
        updateProgress(100, currentFile.size);
        
        if (pauseUploadBtn) {
            pauseUploadBtn.style.display = 'none';
        }
        if (resumeUploadBtn) {
            resumeUploadBtn.style.display = 'none';
        }
        
        // Кнопка отправки формы всегда активна
        if (submitBtn) {
            submitBtn.disabled = false;
        }
        
        // Заполняем скрытые поля
        if (document.getElementById('uploadedFileName')) {
            document.getElementById('uploadedFileName').value = uploadedFileName || currentFile.name;
        }
        if (document.getElementById('uploadedFilePath')) {
            document.getElementById('uploadedFilePath').value = uploadedFilePath || 'documents/' + currentFile.name;
        }
        
        // Показываем сообщение об успехе
        showSuccess('Файл успешно загружен! Нажмите "Загрузить документ" для сохранения.');
        
        // Показываем блок завершения
        if (uploadCompleteInfo) {
            uploadCompleteInfo.style.display = 'block';
        }
    }
    
    // ==================== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ====================
    
    function validateForm() {
        const title = document.getElementById('title')?.value;
        const brandId = document.getElementById('brand_id')?.value;
        const modelId = document.getElementById('car_model_id')?.value;
        const categoryId = document.getElementById('category_id')?.value;
        
        return !!(title && brandId && modelId && categoryId);
    }
    
    function updateProgress(percent, uploadedBytes) {
        const progressBar = document.getElementById('progressBar');
        const progressText = document.querySelector('.progress-text');
        const progressPercent = document.getElementById('progressPercent');
        const uploadedSize = document.getElementById('uploadedSize');
        const totalSize = document.getElementById('totalSize');
        
        const roundedPercent = Math.round(percent);
        
        if (progressBar) {
            progressBar.style.width = percent + '%';
            progressBar.setAttribute('aria-valuenow', percent);
        }
        if (progressText) {
            progressText.textContent = roundedPercent + '%';
        }
        if (progressPercent) {
            progressPercent.textContent = roundedPercent + '%';
        }
        if (uploadedSize) {
            uploadedSize.textContent = formatFileSize(uploadedBytes);
        }
        if (totalSize) {
            totalSize.textContent = formatFileSize(currentFile.size);
        }
    }
    
    function updateUploadSpeed() {
        if (!uploadStartTime || !uploadedChunks.length) return;
        
        const elapsedTime = (Date.now() - uploadStartTime) / 1000;
        const uploadedBytes = uploadedChunks.length * chunkSize;
        const speed = uploadedBytes / elapsedTime;
        
        const uploadSpeedElement = document.getElementById('uploadSpeed');
        if (uploadSpeedElement) {
            uploadSpeedElement.textContent = formatFileSize(speed) + '/с';
        }
        
        // Расчет оставшегося времени
        if (speed > 0) {
            const remainingBytes = currentFile.size - uploadedBytes;
            const remainingTime = remainingBytes / speed;
            const timeRemainingElement = document.getElementById('timeRemaining');
            if (timeRemainingElement) {
                timeRemainingElement.textContent = formatTime(remainingTime);
            }
        }
    }
    
    function updateStatus(text, type) {
        const statusElement = document.getElementById('uploadStatus');
        if (statusElement) {
            statusElement.textContent = text;
            statusElement.className = `badge bg-${type}`;
        }
    }
    
    function resetChunkedUpload() {
        console.log('Сброс загрузки');
        currentFile = null;
        isUploading = false;
        isPaused = false;
        uploadedChunks = [];
        clearInterval(uploadSpeedInterval);
        
        // Сбрасываем UI
        if (fileInfo) {
            fileInfo.style.display = 'none';
        }
        
        // Скрываем элементы прогресса
        const progressBarContainer = document.querySelector('#progressSection .progress');
        const progressInfo = document.querySelector('#progressSection .mt-2.d-flex');
        if (progressBarContainer) progressBarContainer.style.display = 'none';
        if (progressInfo) progressInfo.style.display = 'none';
        
        // Сбрасываем прогресс
        updateProgress(0, 0);
        
        // Показываем кнопку "Начать загрузку"
        if (startUploadBtn) {
            startUploadBtn.style.display = 'inline-block';
            startUploadBtn.disabled = true;
            startUploadBtn.style.backgroundColor = '#6c757d';
            startUploadBtn.style.cursor = 'not-allowed';
        }
        
        // Скрываем кнопки управления
        if (pauseUploadBtn) {
            pauseUploadBtn.style.display = 'none';
        }
        if (resumeUploadBtn) {
            resumeUploadBtn.style.display = 'none';
        }
        if (cancelUploadBtn) {
            cancelUploadBtn.style.display = 'none';
        }
        
        updateStatus('Ожидание', 'secondary');
        
        // Кнопка отправки формы всегда активна
        if (submitBtn) {
            submitBtn.disabled = false;
        }
        
        // Очищаем скрытые поля
        if (document.getElementById('uploadedFileName')) {
            document.getElementById('uploadedFileName').value = '';
        }
        if (document.getElementById('uploadedFilePath')) {
            document.getElementById('uploadedFilePath').value = '';
        }
        
        // Скрываем блок завершения
        if (uploadCompleteInfo) {
            uploadCompleteInfo.style.display = 'none';
        }
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    function formatTime(seconds) {
        if (seconds < 60) return '< 1 мин';
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) return minutes + ' мин';
        const hours = Math.floor(minutes / 60);
        const remainingMinutes = minutes % 60;
        return hours + ' ч ' + remainingMinutes + ' мин';
    }
    
    function showError(message) {
        console.error('Ошибка:', message);
        const errorMessage = document.getElementById('errorMessage');
        if (errorMessage) {
            errorMessage.textContent = message;
        }
        if (errorAlert) {
            errorAlert.style.display = 'block';
            errorAlert.classList.add('show');
            
            setTimeout(() => {
                errorAlert.classList.remove('show');
                setTimeout(() => {
                    errorAlert.style.display = 'none';
                }, 150);
            }, 10000);
        }
    }
    
    function showSuccess(message) {
        console.log('Успех:', message);
        const successMessage = document.getElementById('successMessage');
        if (successMessage) {
            successMessage.textContent = message;
        }
        if (successAlert) {
            successAlert.style.display = 'block';
            successAlert.classList.add('show');
            
            setTimeout(() => {
                successAlert.classList.remove('show');
                setTimeout(() => {
                    successAlert.style.display = 'none';
                }, 150);
            }, 5000);
        }
    }
    
    function showInfo(message) {
        console.log('Инфо:', message);
        alert(message);
    }
    
    // ==================== ОБРАБОТКА ФОРМЫ ====================
    
    if (simpleUploadForm) {
        simpleUploadForm.addEventListener('submit', function(e) {
            if (isChunkedMode) {
                e.preventDefault();
                console.log('Отправка формы с чанковой загрузкой');
                
                // Проверяем, загружен ли файл полностью
                if (!currentFile) {
                    showError('Сначала выберите файл');
                    return;
                }
                
                // Если загрузка не завершена, предупреждаем
                if (uploadedChunks.length > 0) {
                    const totalChunks = Math.ceil(currentFile.size / chunkSize);
                    if (uploadedChunks.length < totalChunks) {
                        if (!confirm('Файл загружен не полностью. Вы уверены, что хотите продолжить?')) {
                            return;
                        }
                    }
                }
                
                // Проверяем заполнены ли все поля
                if (!validateForm()) {
                    showError('Заполните все обязательные поля');
                    return;
                }
                
                // Показываем индикатор загрузки
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Сохранение...';
                }
                
                // Создаем новую форму для отправки
                const formData = new FormData();
                formData.append('title', document.getElementById('title').value);
                formData.append('brand_id', document.getElementById('brand_id').value);
                formData.append('car_model_id', document.getElementById('car_model_id').value);
                formData.append('category_id', document.getElementById('category_id').value);
                formData.append('uploaded_file_name', document.getElementById('uploadedFileName').value || currentFile?.name || '');
                formData.append('uploaded_file_path', document.getElementById('uploadedFilePath').value || '');
                formData.append('_token', csrfToken);
                
                console.log('Отправка данных формы...');
                
                // Отправляем форму
                fetch(this.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Ответ сервера:', response.status);
                    if (response.ok) {
                        return response.json();
                    } else {
                        throw new Error('Ошибка сервера: ' + response.status);
                    }
                })
                .then(data => {
                    console.log('Ответ от сервера:', data);
                    window.location.href = '/admin/documents';
                })
                .catch(error => {
                    console.error('Ошибка отправки формы:', error);
                    showError('Ошибка отправки формы: ' + error.message);
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i> Загрузить документ';
                    }
                });
            }
        });
    }
    
    // Инициализация
    console.log('Скрипт загрузки инициализирован');
});
</script>
</body>
</html>