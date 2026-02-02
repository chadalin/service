@extends('layouts.processing_simple')

@section('title', 'Расширенная обработка документа')
@section('page_title', 'Расширенная обработка документа')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.documents.processing.index') }}">Обработка документов</a></li>
    <li class="breadcrumb-item active">{{ $document->title }}</li>
@endsection

@section('content')
    <!-- Информация о документе -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><i class="bi bi-info-circle"></i> Информация о документе</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="30%">ID:</th>
                                    <td><span class="badge bg-secondary">#{{ $document->id }}</span></td>
                                </tr>
                                <tr>
                                    <th>Название:</th>
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
                                    <th>Создан:</th>
                                    <td>{{ $document->created_at->format('d.m.Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
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
                                                'parse_error' => 'Ошибка парсинга'
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
                                                <div id="progressBar" class="progress-bar {{ $document->parsing_progress >= 100 ? 'bg-success' : 'bg-primary' }}" 
                                                     role="progressbar" 
                                                     style="width: {{ min($document->parsing_progress ?? 0, 100) }}%"
                                                     aria-valuenow="{{ $document->parsing_progress ?? 0 }}" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    {{ $document->parsing_progress ?? 0 }}%
                                                </div>
                                            </div>
                                            <span id="progressPercent">{{ $document->parsing_progress ?? 0 }}%</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Страниц:</th>
                                    <td>
                                        @if($document->total_pages)
                                            <span class="badge bg-secondary">{{ $document->total_pages }}</span>
                                            <small class="text-muted ms-2">обработано: {{ $stats['pages_count'] ?? 0 }}</small>
                                        @else
                                            <span class="text-muted">Неизвестно</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Изображения:</th>
                                    <td>
                                        <span class="badge bg-warning">{{ $stats['images_count'] ?? 0 }}</span>
                                        @if($stats['images_count'] ?? 0)
                                            <a href="{{ route('admin.documents.processing.view-images', $document->id) }}" 
                                               class="btn btn-sm btn-outline-warning ms-2">
                                                <i class="bi bi-eye"></i> Показать
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Статистика документа -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0"><i class="bi bi-bar-chart"></i> Статистика документа</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 col-sm-6 text-center mb-3">
                            <div class="stat-card">
                                <h6 class="card-title">Страницы</h6>
                                <h2 class="text-primary">{{ $stats['pages_count'] ?? 0 }}</h2>
                                <small class="text-muted">из {{ $stats['total_pages'] ?? '?' }}</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 text-center mb-3">
                            <div class="stat-card">
                                <h6 class="card-title">Слова</h6>
                                <h2 class="text-success">{{ number_format($stats['words_count'] ?? 0) }}</h2>
                                <small class="text-muted">{{ number_format($stats['characters_count'] ?? 0) }} символов</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 text-center mb-3">
                            <div class="stat-card">
                                <h6 class="card-title">Изображения</h6>
                                <h2 class="text-warning">{{ $stats['images_count'] ?? 0 }}</h2>
                                <small class="text-muted">извлечено</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 text-center mb-3">
                            <div class="stat-card">
                                <h6 class="card-title">Качество</h6>
                                <div class="progress mb-2">
                                    <div class="progress-bar bg-{{ ($stats['parsing_quality'] ?? 0) > 0.7 ? 'success' : (($stats['parsing_quality'] ?? 0) > 0.4 ? 'warning' : 'danger') }}" 
                                         role="progressbar" 
                                         style="width: {{ ($stats['parsing_quality'] ?? 0) * 100 }}%"
                                         aria-valuenow="{{ ($stats['parsing_quality'] ?? 0) * 100 }}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        {{ round(($stats['parsing_quality'] ?? 0) * 100, 1) }}%
                                    </div>
                                </div>
                                <small class="text-muted">точность парсинга</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Панель управления -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="card-title mb-0"><i class="bi bi-sliders"></i> Управление обработкой</h5>
                </div>
                <div class="card-body">
                    <!-- Прогресс обработки -->
                    <div id="processingProgress" class="mb-4" style="display: {{ $document->status === 'processing' ? 'block' : 'none' }};">
                        <div class="alert alert-warning">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">
                                    <i class="bi bi-arrow-repeat fa-spin"></i> 
                                    <span id="progressStatus">Идет обработка...</span>
                                </h6>
                                <span id="progressPercentText">{{ $document->parsing_progress ?? 0 }}%</span>
                            </div>
                            <div class="progress">
                                <div id="progressBarLive" class="progress-bar progress-bar-striped progress-bar-animated bg-warning" 
                                     role="progressbar" style="width: {{ $document->parsing_progress ?? 0 }}%" 
                                     aria-valuenow="{{ $document->parsing_progress ?? 0 }}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    <span class="progress-text fw-bold">{{ $document->parsing_progress ?? 0 }}%</span>
                                </div>
                            </div>
                            <div id="progressMessage" class="mt-2">
                                <small class="text-muted">Обновите страницу для обновления прогресса</small>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mb-4">
                        <i class="bi bi-info-circle"></i> <strong>Информация:</strong> Для больших документов рекомендуется сначала создать предпросмотр (первые 5 страниц), затем выполнить полный парсинг.
                    </div>
                    
                    <!-- Основные кнопки -->
                    <div class="row" id="controlButtons">
                        <div class="col-md-3 mb-3">
                            <form action="{{ route('admin.documents.processing.create-preview', $document->id) }}" method="POST" class="h-100">
                                @csrf
                                <button type="submit" class="btn btn-primary processing-btn w-100" 
                                        data-confirm="Создать предпросмотр документа (первые 5 страниц)?"
                                        {{ $document->status === 'processing' ? 'disabled' : '' }}>
                                    <i class="bi bi-eye"></i> Создать предпросмотр
                                    <small class="d-block">(первые 5 страниц)</small>
                                </button>
                            </form>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <form action="{{ route('admin.documents.processing.parse-full', $document->id) }}" method="POST" class="h-100">
                                @csrf
                                <button type="submit" class="btn btn-success processing-btn w-100" 
                                        data-confirm="Выполнить полный парсинг всего документа? Это может занять некоторое время."
                                        {{ $document->status === 'processing' ? 'disabled' : '' }}>
                                    <i class="bi bi-gear"></i> Полный парсинг
                                    <small class="d-block">(весь документ)</small>
                                </button>
                            </form>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <form action="{{ route('admin.documents.processing.reset-status', $document->id) }}" method="POST" class="h-100">
                                @csrf
                                <button type="submit" class="btn btn-warning processing-btn w-100" 
                                        data-confirm="Вы уверены, что хотите сбросить статус обработки? Все данные парсинга будут удалены."
                                        {{ $document->status === 'processing' ? 'disabled' : '' }}>
                                    <i class="bi bi-arrow-counterclockwise"></i> Сбросить статус
                                    <small class="d-block">(начать заново)</small>
                                </button>
                            </form>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('admin.documents.processing.index') }}" class="btn btn-secondary processing-btn w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                                <i class="bi bi-arrow-left"></i> Вернуться к списку
                                <small class="d-block">(все документы)</small>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Дополнительные действия -->
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <h6><i class="bi bi-tools"></i> Дополнительные действия</h6>
                                    <div class="row mt-2">
                                        <div class="col-md-3 mb-2">
                                            <form action="{{ route('admin.documents.processing.parse-images-only', $document->id) }}" method="POST" class="w-100">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-primary w-100" 
                                                        data-confirm="Извлечь только изображения из документа?"
                                                        {{ $document->status === 'processing' ? 'disabled' : '' }}>
                                                    <i class="bi bi-images"></i> Только изображения
                                                </button>
                                            </form>
                                        </div>
                                        
                                        @if($previewPages->count() > 0)
                                        <div class="col-md-3 mb-2">
                                            <form action="{{ route('admin.documents.processing.delete-preview', $document->id) }}" method="POST" class="w-100">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger w-100" 
                                                        data-confirm="Удалить предпросмотр документа?"
                                                        {{ $document->status === 'processing' ? 'disabled' : '' }}>
                                                    <i class="bi bi-trash"></i> Удалить предпросмотр
                                                </button>
                                            </form>
                                        </div>
                                        @endif
                                        
                                        <div class="col-md-3 mb-2">
                                            <a href="{{ route('admin.documents.processing.view-images', $document->id) }}" 
                                               class="btn btn-outline-info w-100">
                                                <i class="bi bi-image"></i> Все изображения
                                            </a>
                                        </div>
                                        
                                        <div class="col-md-3 mb-2">
                                            <button type="button" class="btn btn-outline-success w-100" 
                                                    data-bs-toggle="modal" data-bs-target="#parsePageModal"
                                                    {{ $document->status === 'processing' ? 'disabled' : '' }}>
                                                <i class="bi bi-file-earmark"></i> Парсить страницу
                                            </button>
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

    <!-- Предпросмотр документа -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="bi bi-file-pdf"></i> Предпросмотр документа</h5>
                        @if($previewPages->count() > 0)
                        <span class="badge bg-light text-dark">
                            <i class="bi bi-file-earmark"></i> {{ $previewPages->count() }} страниц
                        </span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if($previewPages->count() > 0)
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle"></i> Создан предпросмотр {{ $previewPages->count() }} страниц из {{ $stats['total_pages'] ?? '?' }}.
                                    @if($document->status === 'preview_created' && isset($stats['parsing_quality']))
                                    <span class="ms-2">Качество парсинга: {{ round($stats['parsing_quality'] * 100, 1) }}%</span>
                                    @endif
                                </div>
                            </div>
                            
                            @foreach($previewPages as $page)
                            <div class="col-md-12 mb-4">
                                <div class="card border">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">
                                                <i class="bi bi-file-earmark"></i> Страница {{ $page->page_number }}
                                                @if($page->section_title)
                                                    <span class="badge bg-info ms-2">{{ $page->section_title }}</span>
                                                @endif
                                            </h6>
                                            <div>
                                                <span class="badge bg-secondary me-2">
                                                    <i class="bi bi-fonts"></i> {{ $page->word_count }} слов
                                                </span>
                                                @if($page->has_images ?? false)
                                                <span class="badge bg-warning">
                                                    <i class="bi bi-image"></i> {{ $page->images->count() ?? 0 }} изображений
                                                </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        @if($page->images && $page->images->count() > 0)
                                        <div class="row">
                                            <div class="col-md-4">
                                                <h6><i class="bi bi-images"></i> Извлеченные изображения:</h6>
                                                <div class="row">
                                                    @foreach($page->images as $image)
                                                    <div class="col-6 mb-3">
                                                        <div class="card">
                                                            <a href="{{ Storage::url($image->path) }}" target="_blank" class="text-decoration-none">
                                                                <img src="{{ Storage::url($image->thumbnail_path ?? $image->path) }}" 
                                                                     alt="{{ $image->description }}" 
                                                                     class="card-img-top img-thumbnail"
                                                                     style="height: 120px; object-fit: contain;"
                                                                     onerror="this.onerror=null; this.src='data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"150\" height=\"120\" fill=\"%236c757d\"><rect width=\"100%\" height=\"100%\" fill=\"%23f8f9fa\"/><text x=\"50%\" y=\"50%\" dy=\".3em\" text-anchor=\"middle\" fill=\"%236c757d\">Изображение</text></svg>';">
                                                            </a>
                                                            <div class="card-body p-2">
                                                                <small class="text-muted d-block">{{ $image->description }}</small>
                                                                <small class="text-muted d-block">{{ $image->width ?? 0 }}×{{ $image->height ?? 0 }}px</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                        @else
                                            <div class="col-md-12">
                                        @endif
                                                <h6><i class="bi bi-text-left"></i> Содержимое:</h6>
                                                <div class="document-content">
                                                    {!! $page->content ?? '<p class="text-muted">Содержимое отсутствует</p>' !!}
                                                </div>
                                                @if(!empty($page->content_text))
                                                <div class="mt-3">
                                                    <button class="btn btn-sm btn-outline-secondary" type="button" 
                                                            data-bs-toggle="collapse" 
                                                            data-bs-target="#rawText{{ $page->id }}">
                                                        <i class="bi bi-code"></i> Показать исходный текст
                                                    </button>
                                                    <div class="collapse mt-2" id="rawText{{ $page->id }}">
                                                        <pre class="bg-light p-2" style="max-height: 200px; overflow: auto; font-size: 12px;">{{ $page->content_text }}</pre>
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5" id="noPreview">
                            <i class="bi bi-file-pdf text-muted mb-3" style="font-size: 4rem;"></i>
                            <h4>Предпросмотр не создан</h4>
                            <p class="text-muted">Нажмите "Создать предпросмотр" для извлечения первых 5 страниц документа.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для выбора страницы -->
<div class="modal fade" id="parsePageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark"></i> Парсинг конкретной страницы</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.documents.processing.parse-single-page', $document->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="pageNumber" class="form-label">Номер страницы</label>
                        <input type="number" class="form-control" id="pageNumber" name="page" 
                               min="1" max="{{ $stats['total_pages'] ?? 1 }}" value="1" required>
                        <div class="form-text">Всего страниц в документе: {{ $stats['total_pages'] ?? 'неизвестно' }}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Парсить страницу</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Добавляем URL для обновления прогресса -->
<div data-progress-url="{{ route('admin.documents.processing.progress', $document->id) }}" style="display: none;"></div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Document processing advanced page loaded');
    
    // Если документ в обработке, обновляем прогресс
    @if($document->status === 'processing')
        checkProgress();
    @endif
    
    // Функция проверки прогресса
    function checkProgress() {
        const progressUrl = document.querySelector('[data-progress-url]')?.getAttribute('data-progress-url');
        if (!progressUrl) return;
        
        console.log('Checking progress from:', progressUrl);
        
        fetch(progressUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Progress data received:', data);
                
                if (data.success) {
                    updateProgress(data);
                    
                    // Если обработка еще не завершена, продолжаем проверять
                    if (data.status === 'processing' && data.progress < 100) {
                        setTimeout(checkProgress, 3000);
                    }
                    
                    // Если обработка завершена, перезагружаем страницу
                    if (data.progress >= 100 || data.status !== 'processing') {
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    }
                }
            })
            .catch(error => {
                console.error('Error checking progress:', error);
                // Пробуем снова через 5 секунд
                setTimeout(checkProgress, 5000);
            });
    }
    
    // Функция обновления прогресс-бара
    function updateProgress(data) {
        console.log('Updating progress with:', data);
        
        // Обновляем основной прогресс-бар
        const progressBar = document.getElementById('progressBar');
        const progressBarLive = document.getElementById('progressBarLive');
        const progressPercent = document.getElementById('progressPercent');
        const progressPercentText = document.getElementById('progressPercentText');
        const progressStatus = document.getElementById('progressStatus');
        const progressMessage = document.getElementById('progressMessage');
        const processingProgress = document.getElementById('processingProgress');
        
        if (progressBar) {
            progressBar.style.width = data.progress + '%';
            progressBar.setAttribute('aria-valuenow', data.progress);
            progressBar.textContent = data.progress.toFixed(1) + '%';
        }
        
        if (progressBarLive) {
            progressBarLive.style.width = data.progress + '%';
            progressBarLive.setAttribute('aria-valuenow', data.progress);
            progressBarLive.querySelector('.progress-text').textContent = data.progress.toFixed(1) + '%';
        }
        
        if (progressPercent) {
            progressPercent.textContent = data.progress.toFixed(1) + '%';
        }
        
        if (progressPercentText) {
            progressPercentText.textContent = data.progress.toFixed(1) + '%';
        }
        
        if (progressStatus) {
            const statusText = data.status === 'processing' ? 'Идет обработка...' : 
                              data.status === 'parsed' ? 'Документ распарсен' :
                              data.status === 'preview_created' ? 'Создан предпросмотр' :
                              data.status === 'parse_error' ? 'Ошибка парсинга' : data.status;
            progressStatus.textContent = statusText;
        }
        
        if (progressMessage && data.message) {
            progressMessage.innerHTML = `<small class="text-muted"><i class="bi bi-info-circle"></i> ${data.message}</small>`;
        }
        
        // Показываем/скрываем блок прогресса
        if (processingProgress) {
            if (data.status === 'processing') {
                processingProgress.style.display = 'block';
            } else {
                processingProgress.style.display = 'none';
            }
        }
        
        // Обновляем статус кнопок
        const controlButtons = document.getElementById('controlButtons');
        if (controlButtons) {
            const buttons = controlButtons.querySelectorAll('button');
            buttons.forEach(button => {
                if (data.status === 'processing') {
                    button.disabled = true;
                } else {
                    button.disabled = false;
                }
            });
        }
    }
    
    // Обработка отправки форм
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && submitBtn.disabled) {
                e.preventDefault();
                return false;
            }
            
            // Если есть data-confirm, проверяем подтверждение
            if (submitBtn && submitBtn.hasAttribute('data-confirm')) {
                if (!confirm(submitBtn.getAttribute('data-confirm'))) {
                    e.preventDefault();
                    return false;
                }
            }
            
            // Показываем индикатор загрузки
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Обработка...';
                submitBtn.disabled = true;
                
                // Восстанавливаем кнопку через 30 секунд на всякий случай
                setTimeout(() => {
                    if (submitBtn.disabled) {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                }, 30000);
            }
            
            return true;
        });
    });
});
</script>
@endpush