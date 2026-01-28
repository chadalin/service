@extends('layouts.app')

@section('title', 'Расширенная обработка документов')

@push('styles')
<style>
    .document-preview-card {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        transition: all 0.3s ease;
        height: 100%;
    }
    
    .document-preview-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    
    .page-content-preview {
        max-height: 200px;
        overflow: hidden;
        position: relative;
    }
    
    .page-content-preview::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 50px;
        background: linear-gradient(to bottom, transparent, white);
    }
    
    .image-thumbnail {
        width: 100%;
        height: 150px;
        object-fit: cover;
        border-radius: 4px;
        cursor: pointer;
        transition: transform 0.3s ease;
    }
    
    .image-thumbnail:hover {
        transform: scale(1.05);
    }
    
    .processing-status {
        display: inline-flex;
        align-items: center;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .status-processing { background-color: #fff3cd; color: #856404; }
    .status-parsed { background-color: #d1ecf1; color: #0c5460; }
    .status-completed { background-color: #d4edda; color: #155724; }
    .status-error { background-color: #f8d7da; color: #721c24; }
    .status-uploaded { background-color: #6c757d; color: white; }
    .status-preview_created { background-color: #17a2b8; color: white; }
    
    .progress-container {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .progress-details {
        font-size: 14px;
        color: #6c757d;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .page-indicator {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background-color: #007bff;
        color: white;
        font-weight: bold;
        font-size: 14px;
    }
    
    .image-counter {
        position: absolute;
        top: 5px;
        right: 5px;
        background: rgba(0,0,0,0.7);
        color: white;
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 12px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Заголовок -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1">
                        <i class="bi bi-file-earmark-text text-primary me-2"></i>
                        Расширенная обработка документа
                    </h1>
                    <p class="text-muted mb-0">Парсинг, извлечение изображений и предпросмотр</p>
                </div>
                <a href="{{ route('admin.documents.processing.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Назад к списку
                </a>
            </div>
        </div>
    </div>

    <!-- Карточка документа -->
    <div class="row">
    <div class="col-md-8">
        <h4 class="card-title mb-2">{{ $document->title }}</h4>
        <div class="d-flex flex-wrap gap-3 mb-3">
            <div>
                <small class="text-muted d-block">Марка/Модель</small>
                <strong>
                    {{ $document->carModel->brand->name ?? '—' }} 
                    {{ $document->carModel->name ?? '—' }}
                </strong>
            </div>
            <div>
                <small class="text-muted d-block">Категория</small>
                <strong>{{ $document->category->name ?? '—' }}</strong>
            </div>
            <div>
                <small class="text-muted d-block">Тип файла</small>
                <strong class="text-uppercase">{{ $document->file_type }}</strong>
            </div>
            <div>
                <small class="text-muted d-block">Размер</small>
                <strong>
                    @php
                        // Функция для форматирования байтов
                        function formatBytesLocal($bytes, $decimals = 2) {
                            if ($bytes <= 0) return '0 Bytes';
                            $k = 1024;
                            $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
                            $i = floor(log($bytes) / log($k));
                            return number_format($bytes / pow($k, $i), $decimals) . ' ' . $sizes[$i];
                        }
                        
                        // Получаем размер файла
                        $filePath = storage_path('app/' . $document->file_path);
                        $fileSize = 0;
                        if (file_exists($filePath)) {
                            try {
                                $fileSize = filesize($filePath);
                            } catch (\Exception $e) {
                                $fileSize = 0;
                            }
                        }
                    @endphp
                    {{ formatBytesLocal($fileSize) }}
                </strong>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="d-flex flex-column h-100 justify-content-between">
            <div class="mb-3">
                <small class="text-muted d-block">Статус</small>
                @php
                    $statusClass = match($document->status) {
                        'uploaded' => 'status-uploaded',
                        'preview_created' => 'status-preview_created',
                        'processing', 'parsing' => 'status-processing',
                        'parsed' => 'status-parsed',
                        'indexed' => 'status-completed',
                        'processed' => 'status-completed',
                        'parse_error', 'index_error' => 'status-error',
                        default => 'status-uploaded'
                    };
                @endphp
                <span class="processing-status {{ $statusClass }}">
                    @if($document->status === 'uploaded')
                        Загружен
                    @elseif($document->status === 'preview_created')
                        Предпросмотр создан
                    @elseif($document->status === 'processing' || $document->status === 'parsing')
                        В обработке
                    @elseif($document->status === 'parsed')
                        Распарсен
                    @elseif($document->status === 'indexed')
                        Проиндексирован
                    @elseif($document->status === 'processed')
                        Обработан
                    @elseif($document->status === 'parse_error')
                        Ошибка парсинга
                    @elseif($document->status === 'index_error')
                        Ошибка индексации
                    @else
                        {{ $document->status }}
                    @endif
                </span>
            </div>
            <div class="action-buttons">
                @if(!$document->is_parsed)
                <button class="btn btn-warning btn-sm" id="createPreviewBtn">
                    <i class="bi bi-eye me-1"></i> Создать предпросмотр
                </button>
                @endif
                
                @if($document->total_pages && !$document->is_parsed)
                <button class="btn btn-success btn-sm" id="parseFullBtn">
                    <i class="bi bi-play-fill me-1"></i> Полный парсинг
                </button>
                @endif
                
                @if($document->is_parsed && !$document->search_indexed)
                <button class="btn btn-info btn-sm" id="indexDocumentBtn">
                    <i class="bi bi-search me-1"></i> Индексировать
                </button>
                @endif
                
                @if($document->is_parsed)
                <a href="{{ route('admin.documents.show', $document) }}" 
                   class="btn btn-primary btn-sm" target="_blank">
                    <i class="bi bi-file-text me-1"></i> Просмотр
                </a>
                @endif
            </div>
        </div>
    </div>
</div>

    <!-- Прогресс обработки -->
    <div class="row mb-4" id="progressSection" style="display: none;">
        <div class="col-12">
            <div class="progress-container">
                <h5 class="mb-3" id="progressTitle">Обработка документа</h5>
                <div class="progress mb-2" style="height: 20px;">
                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 0%"></div>
                </div>
                <div class="progress-details">
                    <div class="row">
                        <div class="col-md-4">
                            <small>Прогресс: <span id="progressPercent">0%</span></small>
                        </div>
                        <div class="col-md-4">
                            <small>Страницы: <span id="pagesProcessed">0</span>/<span id="totalPages">0</span></small>
                        </div>
                        <div class="col-md-4">
                            <small>Осталось: <span id="timeRemaining">—</span></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Предпросмотр страниц -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Предпросмотр страниц</h5>
                        <div>
                            <small class="text-muted me-3">
                                Всего страниц: <span id="totalPagesPreview">{{ $document->total_pages ?? 'неизвестно' }}</span>
                            </small>
                            <button class="btn btn-sm btn-outline-secondary" id="refreshPreviewBtn">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="previewPagesContainer" class="row">
                        @if($previewPages->count() > 0)
                            @foreach($previewPages as $page)
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="document-preview-card p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="page-indicator">{{ $page->page_number }}</span>
                                        <small class="text-muted">{{ $page->word_count }} слов</small>
                                    </div>
                                    
                                    @if($page->section_title)
                                    <h6 class="mb-2 text-primary">{{ $page->section_title }}</h6>
                                    @endif
                                    
                                    <div class="page-content-preview mb-3">
                                        {!! $page->content ?? nl2br(e($page->content_text)) !!}
                                    </div>
                                    
                                    @if($page->images_count > 0 && $page->images->count() > 0)
                                    <div class="mb-2">
                                        <small class="text-muted d-block mb-1">Изображения:</small>
                                        <div class="row g-2">
                                            @foreach($page->images as $image)
                                            <div class="col-4">
                                                <div class="position-relative">
                                                    <img src="{{ Storage::url($image->path) }}" 
                                                         class="image-thumbnail"
                                                         alt="{{ $image->description }}"
                                                         data-bs-toggle="modal" 
                                                         data-bs-target="#imageModal"
                                                         data-image-src="{{ Storage::url($image->path) }}"
                                                         data-image-desc="{{ $image->description }}">
                                                    <span class="image-counter">{{ $loop->iteration }}</span>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            Качество: {{ round($page->parsing_quality * 100) }}%
                                        </small>
                                        <a href="{{ route('admin.documents.show', $document) }}" 
                                           class="btn btn-sm btn-outline-primary" target="_blank">
                                            <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @else
                        <div class="col-12">
                            <div class="text-center py-5">
                                <i class="bi bi-file-earmark-text display-4 text-muted mb-3"></i>
                                <p class="text-muted">Предпросмотр не создан</p>
                                <button class="btn btn-warning" id="createPreviewBtn2">
                                    <i class="bi bi-eye me-1"></i> Создать предпросмотр
                                </button>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Статистика -->
    <!-- Статистика -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Статистика документа</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 mb-3">
                        <small class="text-muted d-block">Всего страниц</small>
                        <h4 id="statsTotalPages">{{ $document->total_pages ?? 0 }}</h4>
                    </div>
                    <div class="col-6 mb-3">
                        <small class="text-muted d-block">Обработано</small>
                        <h4 id="statsParsedPages">{{ $parsedPagesCount ?? 0 }}</h4>
                    </div>
                    <div class="col-6 mb-3">
                        <small class="text-muted d-block">Изображений</small>
                        <h4 id="statsTotalImages">{{ $totalImagesCount ?? 0 }}</h4>
                    </div>
                    <div class="col-6 mb-3">
                        <small class="text-muted d-block">Слов всего</small>
                        <h4 id="statsTotalWords">{{ $totalWordsCount ?? 0 }}</h4>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Качество парсинга</small>
                        <h4 id="statsParsingQuality">
                            {{ $document->parsing_quality ? round($document->parsing_quality * 100) : 0 }}%
                        </h4>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Размер текста</small>
                        <h4 id="statsTextSize">
                            @php
                                $textSize = $totalTextSize ?? 0;
                                if ($textSize <= 0) {
                                    echo '0 Bytes';
                                } else {
                                    $k = 1024;
                                    $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
                                    $i = floor(log($textSize) / log($k));
                                    echo number_format($textSize / pow($k, $i), 2) . ' ' . $sizes[$i];
                                }
                            @endphp
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Быстрые действия</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary" id="exportJsonBtn">
                        <i class="bi bi-download me-1"></i> Экспорт в JSON
                    </button>
                    <button class="btn btn-outline-secondary" id="exportHtmlBtn">
                        <i class="bi bi-file-earmark-code me-1"></i> Экспорт в HTML
                    </button>
                    <button class="btn btn-outline-info" id="getStatsBtn">
                        <i class="bi bi-graph-up me-1"></i> Обновить статистику
                    </button>
                    <button class="btn btn-outline-warning" id="reindexBtn">
                        <i class="bi bi-arrow-repeat me-1"></i> Переиндексировать
                    </button>
                    <button class="btn btn-outline-danger" id="deletePreviewBtn">
                        <i class="bi bi-trash me-1"></i> Удалить предпросмотр
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для изображения -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Изображение документа</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" class="img-fluid" alt="">
                <p id="modalImageDesc" class="mt-3 text-muted"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                <a href="#" id="downloadImageBtn" class="btn btn-primary" download>
                    <i class="bi bi-download me-1"></i> Скачать
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для прогресса -->
<div class="modal fade" id="processingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Обработка документа</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="processingMessage" class="mb-3">Подготовка к обработке...</div>
                <div class="progress mb-3" style="height: 10px;">
                    <div id="modalProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 0%"></div>
                </div>
                <div id="processingDetails" class="small text-muted"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const documentId = {{ $document->id }};
    let processingInterval = null;
    const processingModal = new bootstrap.Modal(document.getElementById('processingModal'));
    
    // Настройка CSRF
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    // Создание предпросмотра
    $('#createPreviewBtn, #createPreviewBtn2').on('click', function() {
        createPreview();
    });
    
    // Полный парсинг
    $('#parseFullBtn').on('click', function() {
        if (confirm('Выполнить полный парсинг документа? Это может занять некоторое время.')) {
            parseFullDocument();
        }
    });
    
    // Индексация
    $('#indexDocumentBtn').on('click', function() {
        indexDocument();
    });
    
    // Обновление предпросмотра
    $('#refreshPreviewBtn').on('click', function() {
        loadPreviewPages();
    });
    
    // Экспорт
    $('#exportJsonBtn').on('click', function() {
        exportDocument('json');
    });
    
    $('#exportHtmlBtn').on('click', function() {
        exportDocument('html');
    });
    
    // Статистика
    $('#getStatsBtn').on('click', function() {
        getDocumentStats();
    });
    
    // Переиндексация
    $('#reindexBtn').on('click', function() {
        if (confirm('Выполнить переиндексацию документа? Все текущие данные будут удалены.')) {
            reindexDocument();
        }
    });
    
    // Удаление предпросмотра
    $('#deletePreviewBtn').on('click', function() {
        if (confirm('Удалить предпросмотр документа?')) {
            deletePreview();
        }
    });
    
    // Модальное окно изображений
    $('#imageModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const imageSrc = button.data('image-src');
        const imageDesc = button.data('image-desc');
        
        $('#modalImage').attr('src', imageSrc);
        $('#modalImageDesc').text(imageDesc);
        $('#downloadImageBtn').attr('href', imageSrc);
    });
    
    // Функции
    function createPreview() {
        showProcessingModal('Создание предпросмотра...', 10);
        
        $.ajax({
            url: `/admin/documents-processing/${documentId}/preview`,
            method: 'POST',
            data: { pages: 5 },
            success: function(response) {
                if (response.success) {
                    updateProcessingModal('Предпросмотр создан успешно!', 100);
                    
                    setTimeout(function() {
                        processingModal.hide();
                        showToast('Предпросмотр создан успешно', 'success');
                        loadPreviewPages();
                        updateStats();
                    }, 1500);
                } else {
                    updateProcessingModal('Ошибка: ' + response.message, 100);
                    setTimeout(() => processingModal.hide(), 2000);
                }
            },
            error: function(xhr) {
                updateProcessingModal('Ошибка сервера', 100);
                setTimeout(() => processingModal.hide(), 2000);
            }
        });
    }
    
    function parseFullDocument() {
        showProcessingModal('Начало полного парсинга...', 10);
        
        $.ajax({
            url: `/admin/documents-processing/parse/${documentId}`,
            method: 'POST',
            success: function(response) {
                if (response.success) {
                    updateProcessingModal('Парсинг запущен', 30);
                    startProgressTracking();
                } else {
                    updateProcessingModal('Ошибка: ' + response.message, 100);
                    setTimeout(() => processingModal.hide(), 2000);
                }
            },
            error: function(xhr) {
                updateProcessingModal('Ошибка сервера', 100);
                setTimeout(() => processingModal.hide(), 2000);
            }
        });
    }
    
    function indexDocument() {
        showProcessingModal('Индексация документа...', 10);
        
        $.ajax({
            url: `/admin/documents-processing/index/${documentId}`,
            method: 'POST',
            success: function(response) {
                if (response.success) {
                    updateProcessingModal('Индексация завершена', 100);
                    
                    setTimeout(function() {
                        processingModal.hide();
                        showToast('Документ проиндексирован', 'success');
                        window.location.reload();
                    }, 1500);
                } else {
                    updateProcessingModal('Ошибка: ' + response.message, 100);
                    setTimeout(() => processingModal.hide(), 2000);
                }
            },
            error: function(xhr) {
                updateProcessingModal('Ошибка сервера', 100);
                setTimeout(() => processingModal.hide(), 2000);
            }
        });
    }
    
    function startProgressTracking() {
        $('#progressSection').show();
        
        if (processingInterval) {
            clearInterval(processingInterval);
        }
        
        processingInterval = setInterval(function() {
            $.get(`/admin/documents-processing/status/${documentId}`)
                .done(function(response) {
                    if (response.success) {
                        updateProgress(response);
                        
                        if (response.document.is_parsed || response.status === 'parsed') {
                            clearInterval(processingInterval);
                            updateProcessingModal('Парсинг завершен!', 100);
                            
                            setTimeout(function() {
                                processingModal.hide();
                                showToast('Документ полностью обработан', 'success');
                                window.location.reload();
                            }, 2000);
                        }
                    }
                })
                .fail(function() {
                    // Ошибка - продолжаем попытки
                });
        }, 2000);
    }
    
    function updateProgress(data) {
        // Простой прогресс
        const progress = data.document.is_parsed ? 100 : 50;
        
        $('#progressBar').css('width', progress + '%');
        $('#progressPercent').text(progress + '%');
        $('#modalProgressBar').css('width', progress + '%');
    }
    
    function loadPreviewPages() {
        // Просто перезагружаем страницу
        window.location.reload();
    }
    
    function exportDocument(format) {
        window.open(`/admin/documents/${documentId}/export?format=${format}`, '_blank');
    }
    
    function getDocumentStats() {
        // Просто перезагружаем страницу
        window.location.reload();
    }
    
    function reindexDocument() {
        $.ajax({
            url: `/admin/documents-processing/reset/${documentId}`,
            method: 'POST',
            success: function(response) {
                if (response.success) {
                    showToast('Документ подготовлен для переиндексации', 'warning');
                    setTimeout(() => window.location.reload(), 1000);
                }
            }
        });
    }
    
    function deletePreview() {
        // Удаляем предпросмотр перезагрузкой
        window.location.reload();
    }
    
    function showProcessingModal(message, progress) {
        $('#processingMessage').text(message);
        $('#modalProgressBar').css('width', progress + '%');
        processingModal.show();
    }
    
    function updateProcessingModal(message, progress) {
        $('#processingMessage').text(message);
        $('#modalProgressBar').css('width', progress + '%');
    }
    
    function showToast(message, type = 'info') {
        const toastHtml = `
            <div class="toast align-items-center text-bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        $('.toast-container').remove();
        $('body').append('<div class="toast-container position-fixed top-0 end-0 p-3"></div>');
        $('.toast-container').append(toastHtml);
        
        $('.toast').toast({ delay: 3000 }).toast('show');
    }
    
    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }
    
    // Инициализация
    if ('{{ $document->status }}' === 'processing') {
        startProgressTracking();
    }
});
</script>
@endpush