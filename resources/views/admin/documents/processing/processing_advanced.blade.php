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
        
        .nav-tabs .nav-link {
            border-radius: 8px 8px 0 0;
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
        
        .spinning {
            animation: spin 1s linear infinite;
        }
        
        .processing-status {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
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
                                            <div id="mainProgressBar" class="progress-bar {{ $document->parsing_progress >= 100 ? 'bg-success' : 'bg-primary' }}" 
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
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <h2 class="text-info">{{ round(($stats['parsing_quality'] ?? 0) * 100, 1) }}%</h2>
                    <small class="text-muted">Качество</small>
                </div>
            </div>
        </div>


        <!-- В разделе статистики добавьте: -->
<div class="col-md-3 col-sm-6 text-center mb-3">
    <div class="stat-card">
        <h2 class="text-warning">{{ $stats['images_count'] ?? 0 }}</h2>
        <small>Изображений</small>
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

<!-- В дополнительных действиях добавьте: -->
<div class="col-md-3 mb-2">
    <a href="{{ route('admin.documents.processing.pages.list', $document->id) }}" 
       class="btn btn-outline-secondary w-100">
        <i class="bi bi-file-text"></i> Все страницы
    </a>
</div>

        <!-- Прогресс обработки -->
        <div id="processingProgress" class="card {{ $document->status === 'processing' ? '' : 'd-none' }}">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="bi bi-arrow-repeat spinning"></i> Идет обработка...</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <h4 id="progressStatus">Обработка документа...</h4>
                    <h2 id="progressPercent">{{ $document->parsing_progress ?? 0 }}%</h2>
                </div>
                <div class="progress mb-3">
                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-warning" 
                         style="width: {{ $document->parsing_progress ?? 0 }}%">
                    </div>
                </div>
                <div id="progressMessage" class="text-center text-muted">
                    Пожалуйста, подождите...
                </div>
            </div>
        </div>

        <!-- Основные действия -->
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-sliders"></i> Управление обработкой</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Выберите действие для обработки документа
                </div>
                
                <div class="row">
                    <!-- Создать предпросмотр -->
                    <div class="col-md-3 col-sm-6">
                        <form action="{{ route('admin.documents.processing.create-preview', $document->id) }}" method="POST" id="createPreviewForm">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-action w-100 {{ $document->status === 'processing' ? 'disabled' : '' }}" 
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
                        <form action="{{ route('admin.documents.processing.parse-full', $document->id) }}" method="POST" id="parseFullForm">
                            @csrf
                            <button type="submit" class="btn btn-success btn-action w-100 {{ $document->status === 'processing' ? 'disabled' : '' }}"
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
                        <form action="{{ route('admin.documents.processing.parse-images-only', $document->id) }}" method="POST" id="parseImagesForm">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-action w-100 {{ $document->status === 'processing' ? 'disabled' : '' }}"
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
                        <form action="{{ route('admin.documents.processing.reset-status', $document->id) }}" method="POST" id="resetStatusForm">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-action w-100 {{ $document->status === 'processing' ? 'disabled' : '' }}"
                                    id="resetStatusBtn"
                                    {{ $document->status === 'processing' ? 'disabled' : '' }}>
                                <i class="bi bi-arrow-counterclockwise"></i>
                                <span>Сбросить статус</span>
                                <small>(начать заново)</small>
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Дополнительные действия -->
                <div class="row mt-4">
                    <div class="col-md-4">
                        <form action="{{ route('admin.documents.processing.test-images', $document->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary w-100 {{ $document->status === 'processing' ? 'disabled' : '' }}"
                                    {{ $document->status === 'processing' ? 'disabled' : '' }}>
                                <i class="bi bi-vr"></i> Тест изображений
                            </button>
                        </form>
                    </div>
                    <div class="col-md-4">
                        @if($previewPages->count() > 0)
                        <form action="{{ route('admin.documents.processing.delete-preview', $document->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100 {{ $document->status === 'processing' ? 'disabled' : '' }}"
                                    {{ $document->status === 'processing' ? 'disabled' : '' }}>
                                <i class="bi bi-trash"></i> Удалить предпросмотр
                            </button>
                        </form>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-outline-success w-100" data-bs-toggle="modal" data-bs-target="#parsePageModal"
                                {{ $document->status === 'processing' ? 'disabled' : '' }}>
                            <i class="bi bi-file-earmark"></i> Парсить страницу
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- В разделе "Дополнительные действия" добавьте: -->
<div class="col-md-3 mb-2">
    <a href="{{ route('admin.documents.processing.pages.list', $document->id) }}" 
       class="btn btn-outline-secondary w-100">
        <i class="bi bi-file-text"></i> Все страницы
    </a>
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
    console.log('Document processing page loaded');
    
    // Если документ в обработке, начинаем отслеживание прогресса
    @if($document->status === 'processing')
        startProgressTracking();
    @endif
    
    // Обработка отправки форм
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const button = this.querySelector('button[type="submit"]');
            const action = this.getAttribute('action');
            
            // Проверяем, не обрабатывается ли уже документ
            @if($document->status === 'processing')
                e.preventDefault();
                alert('Документ уже в обработке. Дождитесь завершения.');
                return false;
            @endif
            
            // Подтверждение для опасных действий
            if (action.includes('reset-status') || action.includes('delete-preview')) {
                if (!confirm('Вы уверены? Это действие нельзя отменить.')) {
                    e.preventDefault();
                    return false;
                }
            }
            
            // Показываем индикатор загрузки
            if (button) {
                const originalHTML = button.innerHTML;
                button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Обработка...';
                button.disabled = true;
                
                // Восстанавливаем кнопку через 30 секунд на всякий случай
                setTimeout(() => {
                    if (button.disabled) {
                        button.innerHTML = originalHTML;
                        button.disabled = false;
                    }
                }, 30000);
            }
            
            return true;
        });
    });
    
    // Функция отслеживания прогресса
    function startProgressTracking() {
        console.log('Starting progress tracking for document: {{ $document->id }}');
        
        const progressBar = document.getElementById('progressBar');
        const mainProgressBar = document.getElementById('mainProgressBar');
        const progressPercent = document.getElementById('progressPercent');
        const progressStatus = document.getElementById('progressStatus');
        const progressMessage = document.getElementById('progressMessage');
        const processingProgress = document.getElementById('processingProgress');
        
        // Показываем блок прогресса
        if (processingProgress) {
            processingProgress.classList.remove('d-none');
        }
        
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
                        // Обновляем прогресс-бары
                        if (progressBar) {
                            progressBar.style.width = data.progress + '%';
                            progressBar.setAttribute('aria-valuenow', parseFloat(data.progress));
                        }
                        if (mainProgressBar) {
                            mainProgressBar.style.width = data.progress + '%';
                            mainProgressBar.textContent = data.progress + '%';
                        }
                        if (progressPercent) {
                            progressPercent.textContent = data.progress + '%';
                        }
                        if (progressStatus) {
                            progressStatus.textContent = data.message || 'Обработка...';
                        }
                        
                        // Обновляем статус кнопок
                        updateButtonsStatus(data.status);
                        
                        // Если обработка завершена
                        if (parseFloat(data.progress) >= 100 || data.status !== 'processing') {
                            if (progressMessage) {
                                progressMessage.innerHTML = '<i class="bi bi-check-circle text-success"></i> Обработка завершена! Страница перезагрузится через 2 секунды...';
                            }
                            
                            // Перезагружаем страницу через 2 секунды
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        } else {
                            // Продолжаем проверять каждые 3 секунды
                            setTimeout(updateProgress, 3000);
                        }
                    } else {
                        console.error('Progress update failed:', data.error);
                        // Пробуем снова через 5 секунд
                        setTimeout(updateProgress, 5000);
                    }
                })
                .catch(error => {
                    console.error('Error updating progress:', error);
                    // Пробуем снова через 5 секунд
                    setTimeout(updateProgress, 5000);
                });
        }
        
        // Начинаем обновление
        updateProgress();
    }
    
    // Функция обновления статуса кнопок
    function updateButtonsStatus(status) {
        const buttons = document.querySelectorAll('button[type="submit"], button[data-bs-toggle="modal"]');
        buttons.forEach(button => {
            if (status === 'processing') {
                button.disabled = true;
                button.classList.add('disabled');
            } else {
                button.disabled = false;
                button.classList.remove('disabled');
            }
        });
    }
    
    // Обновляем статус кнопок при загрузке
    updateButtonsStatus('{{ $document->status }}');
});
</script>
</body>
</html>