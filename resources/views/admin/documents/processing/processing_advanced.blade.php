<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Обработка документа - {{ $document->title }}</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
            padding-bottom: 50px;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-weight: 600;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .card-header {
            border-radius: 10px 10px 0 0 !important;
            font-weight: 600;
            padding: 15px 20px;
        }
        
        .btn {
            border-radius: 8px;
            padding: 12px 20px;
            font-weight: 500;
        }
        
        .btn-action {
            height: 100px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .btn-action i {
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .progress {
            height: 25px;
            border-radius: 12px;
        }
        
        .progress-bar {
            border-radius: 12px;
            font-weight: 600;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .document-content {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            font-size: 14px;
            max-height: 400px;
            overflow-y: auto;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .stat-card {
            text-align: center;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            background: white;
            margin-bottom: 15px;
        }
        
        .stat-card h2 {
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .spinner {
            animation: spin 1s linear infinite;
        }
        
        .progress-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .progress-info {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .progress-message {
            font-size: 0.95rem;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Шапка -->
        <div class="header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-2"><i class="bi bi-gear"></i> Обработка документа</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb" style="background: rgba(255,255,255,0.1); padding: 8px 12px; border-radius: 6px;">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-white">Главная</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.documents.processing.index') }}" class="text-white">Обработка</a></li>
                            <li class="breadcrumb-item active text-white">{{ Str::limit($document->title, 30) }}</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('admin.documents.processing.index') }}" class="btn btn-outline-light">
                        <i class="bi bi-arrow-left"></i> Назад
                    </a>
                </div>
            </div>
        </div>

        <!-- Уведомления -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Прогресс обработки (показывается ТОЛЬКО когда status=processing) -->
        @if($document->status === 'processing')
        <div class="card mb-4" id="processingCard">
            <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-arrow-repeat spinner"></i> Идет обработка...</h5>
                <span id="currentProgress">{{ $document->parsing_progress ?? 0 }}%</span>
            </div>
            <div class="card-body">
                <!-- Основной прогресс-бар -->
                <div class="progress mb-3" style="height: 25px;">
                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-warning" 
                         style="width: {{ $document->parsing_progress ?? 0 }}%">
                        {{ $document->parsing_progress ?? 0 }}%
                    </div>
                </div>
                
                <!-- Детали прогресса -->
                <div class="row text-center mb-3">
                    <div class="col-md-3">
                        <div class="progress-info">Статус</div>
                        <div id="progressStatus" class="fw-bold text-warning">Обработка</div>
                    </div>
                    <div class="col-md-3">
                        <div class="progress-info">Прогресс</div>
                        <div id="progressPercent" class="fw-bold">{{ $document->parsing_progress ?? 0 }}%</div>
                    </div>
                    <div class="col-md-3">
                        <div class="progress-info">Страницы</div>
                        <div id="progressPages" class="fw-bold">-/-</div>
                    </div>
                    <div class="col-md-3">
                        <div class="progress-info">Изображения</div>
                        <div id="progressImages" class="fw-bold">-</div>
                    </div>
                </div>
                
                <!-- Сообщение -->
                <div id="progressMessage" class="alert alert-warning mb-0">
                    <i class="bi bi-info-circle"></i>
                    <span id="progressMessageText">Начинаем обработку документа...</span>
                </div>
                
                <!-- Кнопки управления -->
                <div class="text-center mt-3">
                    <button id="refreshProgressBtn" class="btn btn-outline-info btn-sm me-2">
                        <i class="bi bi-arrow-clockwise"></i> Обновить статус
                    </button>
                    <button id="autoRefreshToggle" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-play-circle"></i> Автообновление (вкл)
                    </button>
                </div>
            </div>
        </div>
        @endif

        <!-- Информация о документе -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Информация о документе</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%">Название:</th>
                                <td><strong>{{ $document->title }}</strong></td>
                            </tr>
                            <tr>
                                <th>Файл:</th>
                                <td>
                                    <span class="badge bg-info">{{ strtoupper($document->file_type) }}</span>
                                    <small class="text-muted ms-2">{{ $stats['file_size'] ?? 'Неизвестно' }}</small>
                                </td>
                            </tr>
                            <tr>
                                <th>ID:</th>
                                <td><span class="badge bg-secondary">#{{ $document->id }}</span></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%">Статус:</th>
                                <td>
                                    @php
                                        $statusColors = [
                                            'uploaded' => 'secondary',
                                            'processing' => 'warning',
                                            'parsed' => 'success',
                                            'preview_created' => 'info',
                                            'parse_error' => 'danger'
                                        ];
                                        $statusColor = $statusColors[$document->status] ?? 'secondary';
                                        
                                        $statusLabels = [
                                            'uploaded' => 'Загружен',
                                            'processing' => 'В обработке',
                                            'parsed' => 'Распарсен',
                                            'preview_created' => 'Превью создан',
                                            'parse_error' => 'Ошибка'
                                        ];
                                        $statusLabel = $statusLabels[$document->status] ?? $document->status;
                                    @endphp
                                    <span class="badge bg-{{ $statusColor }}">{{ $statusLabel }}</span>
                                </td>
                            </tr>
                            <tr>
                                <th>Прогресс:</th>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2">
                                            <div class="progress-bar {{ $document->parsing_progress >= 100 ? 'bg-success' : 'bg-primary' }}" 
                                                 style="width: {{ $document->parsing_progress ?? 0 }}%">
                                                {{ $document->parsing_progress ?? 0 }}%
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>Страниц:</th>
                                <td>
                                    @if($document->total_pages)
                                        {{ $document->total_pages }} всего
                                    @else
                                        <span class="text-muted">Неизвестно</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Статистика -->
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <h2 class="text-primary">{{ $stats['pages_count'] ?? 0 }}</h2>
                    <small class="text-muted">Обработанные страницы</small>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <h2 class="text-success">{{ number_format($stats['words_count'] ?? 0) }}</h2>
                    <small class="text-muted">Слов</small>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <h2 class="text-warning">{{ $stats['images_count'] ?? 0 }}</h2>
                    <small class="text-muted">Изображений</small>
                    @if($stats['images_count'] ?? 0)
                        <div class="mt-2">
                            <a href="{{ route('admin.documents.processing.view-images', $document->id) }}" 
                               class="btn btn-sm btn-outline-warning">
                                <i class="bi bi-images"></i> Просмотр
                            </a>
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <h2 class="text-info">{{ round(($stats['parsing_quality'] ?? 0) * 100, 1) }}%</h2>
                    <small class="text-muted">Качество</small>
                </div>
            </div>
        </div>

        <!-- Основные действия -->
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-sliders"></i> Управление обработкой</h5>
            </div>
            <div class="card-body">
                <!-- Простой статус без лишней логики -->
                @if($document->status === 'processing')
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> 
                        <strong>Документ в обработке!</strong> Дождитесь завершения.
                    </div>
                @elseif($document->status === 'preview_created')
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Создан предпросмотр</strong> ({{ $previewPages->count() }} страниц). Можете запустить полную обработку.
                    </div>
                @elseif($document->status === 'parsed')
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> 
                        <strong>Документ обработан!</strong> {{ $stats['pages_count'] ?? 0 }} страниц, {{ $stats['images_count'] ?? 0 }} изображений.
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Выберите действие для обработки документа
                    </div>
                @endif
                
                <div class="row">
                    <!-- Создать предпросмотр -->
                    <div class="col-md-3 col-sm-6">
                        <form action="{{ route('admin.documents.processing.create-preview', $document->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-action w-100" 
                                    id="createPreviewBtn"
                                    {{ $document->status === 'processing' ? 'disabled' : '' }}>
                                <i class="bi bi-eye"></i>
                                <span>Создать предпросмотр</span>
                                <small>(первые 5 страниц)</small>
                            </button>
                        </form>
                    </div>
                    
                    <!-- Полный парсинг -->
                    <div class="col-md-3 col-sm-6">
                        <form action="{{ route('admin.documents.processing.parse-full', $document->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success btn-action w-100"
                                    id="parseFullBtn"
                                    {{ $document->status === 'processing' ? 'disabled' : '' }}>
                                <i class="bi bi-gear"></i>
                                <span>Полный парсинг</span>
                                <small>(весь документ)</small>
                            </button>
                        </form>
                    </div>
                    
                    <!-- Извлечь изображения -->
                    <div class="col-md-3 col-sm-6">
                        <form action="{{ route('admin.documents.processing.parse-images-only', $document->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-action w-100"
                                    id="parseImagesBtn"
                                    {{ $document->status === 'processing' ? 'disabled' : '' }}>
                                <i class="bi bi-images"></i>
                                <span>Только изображения</span>
                                <small>(извлечь картинки)</small>
                            </button>
                        </form>
                    </div>
                    
                    <!-- Сбросить статус -->
                    <div class="col-md-3 col-sm-6">
                        <form action="{{ route('admin.documents.processing.reset-status', $document->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-action w-100"
                                    id="resetStatusBtn">
                                <i class="bi bi-arrow-counterclockwise"></i>
                                <span>Сбросить статус</span>
                                <small>(начать заново)</small>
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Дополнительные действия -->
                <div class="row mt-4">
                    <div class="col-md-3">
                        <a href="{{ route('admin.documents.processing.pages.list', $document->id) }}" 
                           class="btn btn-outline-secondary w-100" id="allPagesBtn">
                            <i class="bi bi-file-text"></i> Все страницы
                        </a>
                    </div>
                    <div class="col-md-3">
                        <form action="{{ route('admin.documents.processing.test-images', $document->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary w-100" id="testImagesBtn"
                                    {{ $document->status === 'processing' ? 'disabled' : '' }}>
                                <i class="bi bi-vr"></i> Тест изображений
                            </button>
                        </form>
                    </div>
                    <div class="col-md-3">
                        @if($previewPages->count() > 0)
                        <form action="{{ route('admin.documents.processing.delete-preview', $document->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100" id="deletePreviewBtn"
                                    {{ $document->status === 'processing' ? 'disabled' : '' }}>
                                <i class="bi bi-trash"></i> Удалить предпросмотр
                            </button>
                        </form>
                        @endif
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-outline-success w-100" data-bs-toggle="modal" data-bs-target="#parsePageModal"
                                id="parsePageBtn"
                                {{ $document->status === 'processing' ? 'disabled' : '' }}>
                            <i class="bi bi-file-earmark"></i> Парсить страницу
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Предпросмотр документа -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-file-pdf"></i> Предпросмотр документа</h5>
                    @if($previewPages->count() > 0)
                    <span class="badge bg-light text-dark">{{ $previewPages->count() }} страниц</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if($previewPages->count() > 0)
                    <div class="row">
                        @foreach($previewPages as $page)
                        <div class="col-md-12 mb-4">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="bi bi-file-earmark"></i> Страница {{ $page->page_number }}
                                        @if($page->section_title)
                                            <span class="badge bg-info ms-2">{{ $page->section_title }}</span>
                                        @endif
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="document-content">
                                        {!! $page->content ?? '<p class="text-muted">Содержимое отсутствует</p>' !!}
                                    </div>
                                    @if(!empty($page->content_text))
                                    <div class="mt-3">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#rawText{{ $page->id }}">
                                            <i class="bi bi-code"></i> Исходный текст
                                        </button>
                                        <div class="collapse mt-2" id="rawText{{ $page->id }}">
                                            <pre class="bg-light p-2" style="max-height: 200px; overflow: auto; font-size: 12px;">{{ $page->content_text }}</pre>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-file-pdf text-muted" style="font-size: 3rem;"></i>
                        <h4 class="mt-3">Предпросмотр не создан</h4>
                        <p class="text-muted">Нажмите "Создать предпросмотр" для извлечения первых 5 страниц документа.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Модальное окно для парсинга страницы -->
    <div class="modal fade" id="parsePageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-file-earmark"></i> Парсинг страницы</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.documents.processing.parse-single-page', $document->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Номер страницы</label>
                            <input type="number" name="page" class="form-control" min="1" 
                                   max="{{ $stats['total_pages'] ?? 1000 }}" value="1" required>
                            <div class="form-text">Всего страниц: {{ $stats['total_pages'] ?? 'неизвестно' }}</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Парсить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Document processing page loaded - status: {{ $document->status }}');
        
        // Если статус processing, запускаем отслеживание прогресса
        @if($document->status === 'processing')
            startProgressTracking();
        @endif
        
        // Функция отслеживания прогресса
        function startProgressTracking() {
            console.log('Starting progress tracking...');
            
            let autoRefresh = true;
            let refreshInterval = null;
            
            // Элементы прогресса
            const progressBar = document.getElementById('progressBar');
            const currentProgress = document.getElementById('currentProgress');
            const progressPercent = document.getElementById('progressPercent');
            const progressStatus = document.getElementById('progressStatus');
            const progressPages = document.getElementById('progressPages');
            const progressImages = document.getElementById('progressImages');
            const progressMessageText = document.getElementById('progressMessageText');
            const refreshProgressBtn = document.getElementById('refreshProgressBtn');
            const autoRefreshToggle = document.getElementById('autoRefreshToggle');
            
            // Функция обновления прогресса
            function updateProgress() {
                console.log('Fetching progress update...');
                
                fetch('{{ route("admin.documents.processing.progress", $document->id) }}')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Progress update received:', data);
                        
                        if (data.success) {
                            // Обновляем прогресс
                            const progress = parseFloat(data.progress) || 0;
                            const status = data.status || 'unknown';
                            const message = data.message || '';
                            
                            // Обновляем UI
                            if (progressBar) {
                                progressBar.style.width = progress + '%';
                                progressBar.setAttribute('aria-valuenow', progress);
                                progressBar.textContent = progress + '%';
                            }
                            
                            if (currentProgress) {
                                currentProgress.textContent = progress + '%';
                            }
                            
                            if (progressPercent) {
                                progressPercent.textContent = progress + '%';
                            }
                            
                            if (progressStatus) {
                                let statusText = '';
                                let statusClass = '';
                                
                                switch(status) {
                                    case 'processing':
                                        statusText = 'В обработке';
                                        statusClass = 'text-warning';
                                        break;
                                    case 'completed':
                                        statusText = 'Завершено';
                                        statusClass = 'text-success';
                                        break;
                                    case 'failed':
                                        statusText = 'Ошибка';
                                        statusClass = 'text-danger';
                                        break;
                                    default:
                                        statusText = status;
                                        statusClass = 'text-secondary';
                                }
                                
                                progressStatus.textContent = statusText;
                                progressStatus.className = 'fw-bold ' + statusClass;
                            }
                            
                            if (progressPages) {
                                const processed = data.processed_pages || 0;
                                const total = data.total_pages || 0;
                                progressPages.textContent = total > 0 ? `${processed}/${total}` : '-/-';
                            }
                            
                            if (progressImages) {
                                progressImages.textContent = data.images_count || 0;
                            }
                            
                            if (progressMessageText) {
                                progressMessageText.textContent = message;
                            }
                            
                            // Обновляем стиль прогресс-бара
                            if (progressBar) {
                                if (status === 'processing') {
                                    progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-warning';
                                } else if (status === 'completed') {
                                    progressBar.className = 'progress-bar bg-success';
                                    progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
                                } else if (status === 'failed') {
                                    progressBar.className = 'progress-bar bg-danger';
                                    progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
                                }
                            }
                            
                            // Если обработка завершена или провалена
                            if (status !== 'processing') {
                                // Останавливаем интервал
                                if (refreshInterval) {
                                    clearInterval(refreshInterval);
                                    refreshInterval = null;
                                }
                                
                                // Обновляем кнопку
                                if (autoRefreshToggle) {
                                    autoRefreshToggle.innerHTML = '<i class="bi bi-check-circle"></i> Обработка завершена';
                                    autoRefreshToggle.disabled = true;
                                    autoRefreshToggle.classList.remove('btn-outline-success');
                                    autoRefreshToggle.classList.add('btn-success');
                                }
                                
                                // Если завершено успешно, обновляем страницу через 3 секунды
                                if (status === 'completed') {
                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 3000);
                                }
                            } else {
                                // Если автообновление включено, продолжаем
                                if (autoRefresh && !refreshInterval) {
                                    refreshInterval = setInterval(updateProgress, 2000);
                                }
                            }
                        } else {
                            console.error('Progress update failed:', data.error);
                            if (progressMessageText) {
                                progressMessageText.textContent = 'Ошибка получения данных: ' + (data.error || 'Неизвестная ошибка');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error updating progress:', error);
                        if (progressMessageText) {
                            progressMessageText.textContent = 'Ошибка сети при получении прогресса';
                        }
                    });
            }
            
            // Кнопка ручного обновления
            if (refreshProgressBtn) {
                refreshProgressBtn.addEventListener('click', function() {
                    updateProgress();
                });
            }
            
            // Кнопка переключения автообновления
            if (autoRefreshToggle) {
                autoRefreshToggle.addEventListener('click', function() {
                    autoRefresh = !autoRefresh;
                    
                    if (autoRefresh) {
                        this.innerHTML = '<i class="bi bi-pause-circle"></i> Автообновление (вкл)';
                        this.classList.remove('btn-outline-warning');
                        this.classList.add('btn-outline-success');
                        
                        // Запускаем интервал
                        if (!refreshInterval) {
                            refreshInterval = setInterval(updateProgress, 2000);
                        }
                    } else {
                        this.innerHTML = '<i class="bi bi-play-circle"></i> Автообновление (выкл)';
                        this.classList.remove('btn-outline-success');
                        this.classList.add('btn-outline-warning');
                        
                        // Останавливаем интервал
                        if (refreshInterval) {
                            clearInterval(refreshInterval);
                            refreshInterval = null;
                        }
                    }
                });
            }
            
            // Начинаем обновление
            updateProgress();
        }
        
        // Простая обработка отправки форм
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const button = this.querySelector('button[type="submit"]');
                
                // Простая проверка - если документ уже в обработке, не отправляем
                @if($document->status === 'processing')
                    if (!button.disabled) {
                        e.preventDefault();
                        alert('Документ уже в обработке. Дождитесь завершения.');
                        return false;
                    }
                @endif
                
                // Если кнопка не заблокирована, показываем спиннер
                if (button && !button.disabled) {
                    const originalText = button.innerHTML;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Обработка...';
                    button.disabled = true;
                    
                    // Восстанавливаем кнопку через 10 секунд
                    setTimeout(() => {
                        if (button.disabled) {
                            button.innerHTML = originalText;
                            button.disabled = false;
                        }
                    }, 10000);
                }
                
                return true;
            });
        });
    });
    </script>
</body>
</html>