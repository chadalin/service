@extends('layouts.processing')

@section('title', 'Страница ' . ($page->page_number ?? '') . ' - ' . ($document->title ?? ''))
@section('page_title', 'Просмотр страницы')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.documents.processing.index') }}">Обработка</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.documents.processing.advanced', $document->id) }}">Обработка документа</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.documents.processing.pages.list', $document->id) }}">Страницы</a></li>
    <li class="breadcrumb-item active">Страница {{ $page->page_number }}</li>
@endsection

@push('styles')
<style>
.document-image-container {
    margin: 20px 0;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #007bff;
}

.document-image {
    text-align: center;
}

.image-link {
    display: inline-block;
    transition: transform 0.3s ease;
}

.image-link:hover {
    transform: scale(1.02);
}

.image-caption {
    margin-top: 10px;
    text-align: center;
    font-style: italic;
    color: #6c757d;
}

.img-fluid {
    max-width: 100%;
    height: auto;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* Стили для галереи изображений */
.image-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.gallery-item {
    position: relative;
    overflow: hidden;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.gallery-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.gallery-item img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.gallery-item:hover img {
    transform: scale(1.05);
}

.gallery-caption {
    padding: 10px;
    background: white;
    text-align: center;
}

.gallery-badges {
    position: absolute;
    top: 10px;
    right: 10px;
    display: flex;
    gap: 5px;
}

.gallery-badge {
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 0.7rem;
}

/* Стили для вкладок изображений */
.image-tabs {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
}

.image-tabs-nav {
    display: flex;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.image-tab-btn {
    flex: 1;
    padding: 10px 15px;
    border: none;
    background: none;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
}

.image-tab-btn:hover {
    background: #e9ecef;
}

.image-tab-btn.active {
    background: white;
    border-bottom: 2px solid #007bff;
    color: #007bff;
}

.image-tab-content {
    padding: 15px;
    background: white;
}

.image-preview-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.image-preview-item {
    text-align: center;
}

.image-preview-item img {
    max-width: 100%;
    max-height: 300px;
    object-fit: contain;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 5px;
    background: white;
}

.image-info {
    margin-top: 10px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
}

.image-info-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
}

.image-info-label {
    font-weight: 500;
    color: #495057;
}

.image-info-value {
    color: #6c757d;
}

/* Стили для сравнения изображений */
.image-comparison-container {
    position: relative;
    width: 100%;
    max-width: 600px;
    margin: 0 auto;
}

.comparison-slider {
    position: absolute;
    top: 0;
    left: 50%;
    width: 3px;
    height: 100%;
    background: #007bff;
    cursor: col-resize;
    z-index: 10;
}

.comparison-slider::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 30px;
    height: 30px;
    background: #007bff;
    border-radius: 50%;
    border: 2px solid white;
    box-shadow: 0 0 5px rgba(0,0,0,0.3);
}

.comparison-image {
    position: absolute;
    top: 0;
    width: 50%;
    height: 100%;
    overflow: hidden;
}

.comparison-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

/* Стили для слайдера сравнения */
.comparison-slider-container {
    width: 100%;
    height: 400px;
    position: relative;
    margin: 20px 0;
}

.comparison-label {
    position: absolute;
    top: 10px;
    padding: 5px 10px;
    background: rgba(0,0,0,0.7);
    color: white;
    border-radius: 4px;
    font-size: 0.9rem;
}
</style>
@endpush

@section('content')
<div class="container">
    <!-- Заголовок -->
    <div class="header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-2"><i class="bi bi-file-text"></i> Страница {{ $page->page_number }}</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.documents.processing.advanced', $document->id) }}" class="text-white">{{ Str::limit($document->title, 15) }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.documents.processing.pages.list', $document->id) }}" class="text-white">Страницы</a></li>
                        <li class="breadcrumb-item active text-white">{{ $page->page_number }}</li>
                    </ol>
                </nav>
            </div>
            <div class="btn-group">
                <a href="{{ route('admin.documents.processing.pages.list', $document->id) }}" class="btn btn-outline-light">
                    <i class="bi bi-arrow-left"></i> Назад
                </a>
                @if($page->page_number > 1)
                    @php
                        $prevPage = \App\Models\DocumentPage::where('document_id', $document->id)
                            ->where('page_number', $page->page_number - 1)
                            ->first();
                    @endphp
                    @if($prevPage)
                        <a href="{{ route('admin.documents.processing.page.show', ['id' => $document->id, 'pageId' => $prevPage->id]) }}" 
                           class="btn btn-outline-light">
                            <i class="bi bi-chevron-left"></i> Предыдущая
                        </a>
                    @endif
                @endif
                @php
                    $nextPage = \App\Models\DocumentPage::where('document_id', $document->id)
                        ->where('page_number', $page->page_number + 1)
                        ->first();
                @endphp
                @if($nextPage)
                    <a href="{{ route('admin.documents.processing.page.show', ['id' => $document->id, 'pageId' => $nextPage->id]) }}" 
                       class="btn btn-outline-light">
                        Следующая <i class="bi bi-chevron-right"></i>
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Информация о странице -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <h2 class="text-primary">{{ $page->page_number }}</h2>
                <small>Номер страницы</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h2 class="text-success">{{ number_format($page->word_count) }}</h2>
                <small>Слов</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h2 class="text-info">{{ number_format($page->character_count) }}</h2>
                <small>Символов</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h2 class="text-warning">{{ round($page->parsing_quality * 100) }}%</h2>
                <small>Качество парсинга</small>
            </div>
        </div>
    </div>

    <!-- Изображения страницы с вкладками -->
   @if($images->count() > 0)
<div class="card mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="bi bi-images"></i> Изображения на странице ({{ $images->count() }})</h5>
    </div>
    <div class="card-body">
        @foreach($images as $image)
        <div class="mb-4">
            <div class="text-center">
                @if($image->has_screenshot && $image->screenshot_url)
                <a href="{{ $image->screenshot_url }}" target="_blank" class="d-block mb-2">
                    <img src="{{ $image->screenshot_url }}" 
                         alt="Скриншот" 
                         class="img-fluid rounded border shadow"
                         style="max-height: 500px; object-fit: contain;">
                </a>
                <div class="small text-muted">
                    <i class="bi bi-aspect-ratio"></i> {{ $image->width }}×{{ $image->height }}px | 
                    <i class="bi bi-zoom-in"></i> Кликните для увеличения
                </div>
                @elseif($image->url)
                <a href="{{ $image->url }}" target="_blank" class="d-block mb-2">
                    <img src="{{ $image->url }}" 
                         alt="Изображение" 
                         class="img-fluid rounded border shadow"
                         style="max-height: 500px; object-fit: contain;">
                </a>
                <div class="small text-muted">
                    <i class="bi bi-aspect-ratio"></i> {{ $image->width }}×{{ $image->height }}px
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
                
                <!-- Вкладка Галерея -->
                <div id="galleryTab" class="image-tab-content">
                    <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                        @foreach($images as $image)
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-img-top position-relative" style="height: 200px; overflow: hidden;">
                                    <a href="{{ Storage::url($image->path) }}" target="_blank" class="text-decoration-none">
                                        <img src="{{ Storage::url($image->path) }}" 
                                             alt="{{ $image->description }}"
                                             class="w-100 h-100"
                                             style="object-fit: contain; background: #f8f9fa;"
                                             onerror="this.onerror=null; this.src='data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 300 200\"><rect width=\"100%\" height=\"100%\" fill=\"%23f8f9fa\"/><text x=\"50%\" y=\"50%\" text-anchor=\"middle\" dy=\".3em\" fill=\"%236c757d\">Изображение</text></svg>';">
                                    </a>
                                    @if($image->screenshot_path || $image->thumbnail_path)
                                    <div class="position-absolute bottom-0 end-0 m-2">
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle"></i> Есть превью
                                        </span>
                                    </div>
                                    @endif
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title text-truncate" title="{{ $image->filename }}">
                                        {{ $image->filename }}
                                    </h6>
                                    <div class="small text-muted">
                                        <div><i class="bi bi-file-earmark"></i> {{ strtoupper($image->extension) }}</div>
                                        <div><i class="bi bi-hdd"></i> {{ number_format($image->size / 1024, 2) }} KB</div>
                                        @if($image->width && $image->height)
                                        <div><i class="bi bi-aspect-ratio"></i> {{ $image->width }}×{{ $image->height }}</div>
                                        @endif
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="btn-group btn-group-sm w-100" role="group">
                                        <a href="{{ Storage::url($image->path) }}" 
                                           target="_blank" 
                                           class="btn btn-outline-primary">
                                            <i class="bi bi-eye"></i> Оригинал
                                        </a>
                                        @if($image->screenshot_path)
                                        <a href="{{ Storage::url($image->screenshot_path) }}" 
                                           target="_blank" 
                                           class="btn btn-outline-info">
                                            <i class="bi bi-aspect-ratio"></i> Скриншот
                                        </a>
                                        @endif
                                        @if($image->thumbnail_path)
                                        <a href="{{ Storage::url($image->thumbnail_path) }}" 
                                           target="_blank" 
                                           class="btn btn-outline-success">
                                            <i class="bi bi-image"></i> Миниатюра
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- Вкладка Превью и скриншоты -->
                <div id="previewsTab" class="image-tab-content" style="display: none;">
                    @foreach($images as $image)
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">{{ $image->description }}</h6>
                            <small class="text-muted">{{ $image->filename }}</small>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Оригинальное изображение -->
                                <div class="col-md-4">
                                    <div class="image-preview-item">
                                        <h6 class="mb-2"><i class="bi bi-image"></i> Оригинал</h6>
                                        <a href="{{ Storage::url($image->path) }}" target="_blank">
                                            <img src="{{ Storage::url($image->path) }}" 
                                                 alt="Оригинал"
                                                 onerror="this.onerror=null; this.src='data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 300 200\"><rect width=\"100%\" height=\"100%\" fill=\"%23f8f9fa\"/><text x=\"50%\" y=\"50%\" text-anchor=\"middle\" dy=\".3em\" fill=\"%236c757d\">Оригинал</text></svg>';">
                                        </a>
                                        <div class="image-info mt-2">
                                            <div class="image-info-item">
                                                <span class="image-info-label">Размер:</span>
                                                <span class="image-info-value">{{ number_format($image->size / 1024, 2) }} KB</span>
                                            </div>
                                            <div class="image-info-item">
                                                <span class="image-info-label">Разрешение:</span>
                                                <span class="image-info-value">{{ $image->width }}×{{ $image->height }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
            <!-- Скриншот -->
<!-- Скриншот -->
<div class="col-md-4">
    <div class="image-preview-item">
        <h6 class="mb-2"><i class="bi bi-aspect-ratio"></i> Скриншот</h6>
        @if($image->screenshot_path && $image->has_screenshot)
            @php
                // Получаем размеры файлов для сравнения
                $screenshotSize = $image->screenshot_size ?? 0;
                $originalSize = $image->size ?? 0;
                $isDifferent = $screenshotSize > 0 && $originalSize > 0 && $screenshotSize != $originalSize;
                $savedPercent = $isDifferent ? round((1 - $screenshotSize / $originalSize) * 100, 2) : 0;
            @endphp
            
            <a href="{{ $image->screenshot_url }}" target="_blank" class="d-block mb-2">
                <img src="{{ $image->screenshot_url }}" 
                     alt="Скриншот"
                     class="img-fluid border rounded"
                     onerror="this.onerror=null; this.src='data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 300 200\"><rect width=\"100%\" height=\"100%\" fill=\"%23f8f9fa\"/><text x=\"50%\" y=\"50%\" text-anchor=\"middle\" dy=\".3em\" fill=\"%236c757d\">Скриншот</text></svg>';">
            </a>
            
            <div class="image-info mt-2">
                <div class="image-info-item">
                    <span class="image-info-label">Размер:</span>
                    <span class="image-info-value">{{ number_format($screenshotSize / 1024, 2) }} KB</span>
                </div>
                <div class="image-info-item">
                    <span class="image-info-label">Сжатие:</span>
                    <span class="image-info-value {{ $isDifferent ? 'text-success' : 'text-warning' }}">
                        @if($isDifferent)
                            <i class="bi bi-check-circle"></i> {{ $savedPercent }}% меньше
                        @else
                            <i class="bi bi-exclamation-triangle"></i> Не сжато
                        @endif
                    </span>
                </div>
                <div class="image-info-item">
                    <span class="image-info-label">Формат:</span>
                    <span class="image-info-value">800×600px</span>
                </div>
            </div>
            
            <!-- Кнопки действий -->
            <div class="mt-2">
                <div class="btn-group btn-group-sm w-100" role="group">
                    <a href="{{ $image->screenshot_url }}" 
                       target="_blank" 
                       class="btn btn-outline-primary">
                        <i class="bi bi-eye"></i> Просмотр
                    </a>
                    <a href="{{ $image->screenshot_url }}" 
                       download="screenshot_{{ $image->filename }}"
                       class="btn btn-outline-success">
                        <i class="bi bi-download"></i> Скачать
                    </a>
                </div>
            </div>
            
        @elseif($image->screenshot_path)
            <div class="alert alert-danger">
                <i class="bi bi-x-circle"></i> Файл скриншота не найден
                <div class="small mt-1">
                    Путь: {{ $image->screenshot_path }}<br>
                    <a href="{{ Storage::url($image->path) }}" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                        <i class="bi bi-eye"></i> Показать оригинал
                    </a>
                </div>
            </div>
        @else
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i> Скриншот не создан
                <div class="small mt-1">
                    Для этого изображения не был создан скриншот.
                </div>
            </div>
        @endif
    </div>
</div>

<!-- В шаблоне page_show.blade.php -->
@if($image->screenshot_path)
    <div class="mb-3">
        <h6>Скриншот (прямая проверка):</h6>
        @php
            // Прямая проверка существования файла
            $screenshotExists = Storage::disk('public')->exists($image->screenshot_path);
            $screenshotUrl = $screenshotExists ? Storage::url($image->screenshot_path) : null;
            
            // Также проверяем оригинал для сравнения
            $originalExists = Storage::disk('public')->exists($image->path);
            $originalUrl = $originalExists ? Storage::url($image->path) : null;
            
            // Сравниваем размеры
            if ($screenshotExists && $originalExists) {
                $screenshotSize = Storage::disk('public')->size($image->screenshot_path);
                $originalSize = Storage::disk('public')->size($image->path);
                $isDifferent = $screenshotSize != $originalSize;
            }
        @endphp
        
        @if($screenshotExists)
            <div class="row">
                <div class="col-md-6">
                    <h6>Оригинал:</h6>
                    <img src="{{ $originalUrl }}" alt="Оригинал" class="img-fluid border">
                    <div class="small text-muted mt-1">
                        {{ $image->width }}×{{ $image->height }}px, 
                        {{ number_format($originalSize / 1024, 2) }} KB
                    </div>
                </div>
                <div class="col-md-6">
                    <h6>Скриншот:</h6>
                    <img src="{{ $screenshotUrl }}" alt="Скриншот" class="img-fluid border">
                    <div class="small text-muted mt-1">
                        @if(isset($screenshotSize))
                            {{ number_format($screenshotSize / 1024, 2) }} KB
                            @if(isset($isDifferent))
                                <span class="{{ $isDifferent ? 'text-success' : 'text-warning' }}">
                                    ({{ $isDifferent ? 'отличается' : 'такой же' }})
                                </span>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            
            @if(isset($isDifferent) && $isDifferent)
                <div class="alert alert-success mt-2">
                    <i class="bi bi-check-circle"></i> Скриншот успешно создан и отличается от оригинала!
                </div>
            @elseif(isset($isDifferent) && !$isDifferent)
                <div class="alert alert-warning mt-2">
                    <i class="bi bi-exclamation-triangle"></i> Скриншот идентичен оригиналу (обрезка не сработала)
                </div>
            @endif
        @else
            <div class="alert alert-danger">
                <i class="bi bi-x-circle"></i> Файл скриншота не найден по пути: {{ $image->screenshot_path }}
            </div>
        @endif
    </div>
@endif


@if($images->count() > 0)
    <!-- Проверка скриншотов -->
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> 
        Найдено {{ $images->count() }} изображений на этой странице.
        @php
            $hasScreenshots = $images->where('has_screenshot', true)->count();
        @endphp
        @if($hasScreenshots > 0)
            <span class="text-success">
                <i class="bi bi-check-circle"></i> {{ $hasScreenshots }} со скриншотами
            </span>
        @endif
    </div>
    
    <!-- Галерея изображений -->
    <div class="row">
        @foreach($images as $image)
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-header py-1 bg-light">
                    <small>{{ $image->description ?? 'Изображение' }}</small>
                </div>
                <div class="card-body p-2">
                    <!-- Скриншот если есть -->
                    @if($image->has_screenshot && $image->screenshot_url)
                        <div class="text-center mb-2">
                            <h6><i class="bi bi-aspect-ratio"></i> Обрезанный скриншот</h6>
                            <a href="{{ $image->screenshot_url }}" target="_blank">
                                <img src="{{ $image->screenshot_url }}" 
                                     alt="Скриншот" 
                                     class="img-fluid border rounded"
                                     style="max-height: 200px;">
                            </a>
                            <div class="small text-muted mt-1">
                                {{ $image->width ?? '?' }}×{{ $image->height ?? '?' }}px
                                @if($image->screenshot_size)
                                    ({{ number_format($image->screenshot_size / 1024, 1) }} KB)
                                @endif
                            </div>
                        </div>
                        
                        <!-- Оригинал для сравнения -->
                        <div class="text-center">
                            <h6><i class="bi bi-image"></i> Оригинал</h6>
                            <a href="{{ $image->url }}" target="_blank">
                                <img src="{{ $image->url }}" 
                                     alt="Оригинал" 
                                     class="img-fluid border rounded"
                                     style="max-height: 150px;">
                            </a>
                            <div class="small text-muted mt-1">
                                {{ number_format($image->size / 1024, 1) }} KB
                            </div>
                        </div>
                    @else
                        <!-- Только оригинал если нет скриншота -->
                        <div class="text-center">
                            <a href="{{ $image->url }}" target="_blank">
                                <img src="{{ $image->url }}" 
                                     alt="Изображение" 
                                     class="img-fluid border rounded"
                                     style="max-height: 250px;">
                            </a>
                        </div>
                        <div class="alert alert-warning mt-2 p-2 small">
                            <i class="bi bi-exclamation-triangle"></i> 
                            Скриншот не создан для этого изображения
                        </div>
                    @endif
                </div>
                <div class="card-footer p-2">
                    <div class="btn-group btn-group-sm w-100" role="group">
                        <a href="{{ $image->url }}" 
                           target="_blank" 
                           class="btn btn-outline-primary">
                            <i class="bi bi-eye"></i> Оригинал
                        </a>
                        @if($image->has_screenshot && $image->screenshot_url)
                        <a href="{{ $image->screenshot_url }}" 
                           target="_blank" 
                           class="btn btn-outline-info">
                            <i class="bi bi-aspect-ratio"></i> Скриншот
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
@else
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> На этой странице не найдено изображений
    </div>
@endif
<!-- Отладочная информация -->
@if($images->count() > 0)
<div class="card mb-3">
    <div class="card-header bg-info text-white">
        <h6 class="mb-0"><i class="bi bi-bug"></i> Отладка изображений</h6>
    </div>
    <div class="card-body">
        @foreach($images as $image)
        <div class="mb-2">
            <strong>{{ $image->filename }}</strong><br>
            <small class="text-muted">
                Путь: {{ $image->path }}<br>
                Скриншот: {{ $image->screenshot_path ?: 'Нет' }}<br>
                @if($image->screenshot_path)
                    @php
                        $screenshotExists = Storage::disk('public')->exists($image->screenshot_path);
                        $originalExists = Storage::disk('public')->exists($image->path);
                    @endphp
                    Существует: {{ $screenshotExists ? 'Да' : 'Нет' }}<br>
                    URL: {{ Storage::url($image->screenshot_path) }}<br>
                    Размер: {{ $image->screenshot_size ? number_format($image->screenshot_size / 1024, 2) : 'N/A' }} KB<br>
                    Оригинал: {{ $image->size ? number_format($image->size / 1024, 2) : 'N/A' }} KB
                @endif
            </small>
        </div>
        @endforeach
    </div>
</div>
@endif
                                
                                <!-- Миниатюра -->
                                <div class="col-md-4">
                                    <div class="image-preview-item">
                                        <h6 class="mb-2"><i class="bi bi-card-image"></i> Миниатюра</h6>
                                        @if($image->thumbnail_path && Storage::disk('public')->exists($image->thumbnail_path))
                                        <a href="{{ Storage::url($image->thumbnail_path) }}" target="_blank">
                                            <img src="{{ Storage::url($image->thumbnail_path) }}" 
                                                 alt="Миниатюра"
                                                 onerror="this.onerror=null; this.src='data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 300 200\"><rect width=\"100%\" height=\"100%\" fill=\"%23f8f9fa\"/><text x=\"50%\" y=\"50%\" text-anchor=\"middle\" dy=\".3em\" fill=\"%236c757d\">Миниатюра</text></svg>';">
                                        </a>
                                        <div class="image-info mt-2">
                                            <div class="image-info-item">
                                                <span class="image-info-label">Размер:</span>
                                                <span class="image-info-value">{{ $image->thumbnail_size ? number_format($image->thumbnail_size / 1024, 2) : 'N/A' }} KB</span>
                                            </div>
                                            <div class="image-info-item">
                                                <span class="image-info-label">Размер:</span>
                                                <span class="image-info-value">300×200px</span>
                                            </div>
                                        </div>
                                        @else
                                        <div class="alert alert-warning">
                                            <i class="bi bi-exclamation-triangle"></i> Миниатюра не найдена
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Кнопки действий -->
                            <div class="mt-3">
                                <div class="btn-group" role="group">
                                    <a href="{{ Storage::url($image->path) }}" 
                                       target="_blank" 
                                       class="btn btn-primary">
                                        <i class="bi bi-eye"></i> Открыть оригинал
                                    </a>
                                    <a href="{{ Storage::url($image->path) }}" 
                                       download="{{ $image->filename }}"
                                       class="btn btn-success">
                                        <i class="bi bi-download"></i> Скачать оригинал
                                    </a>
                                    @if($image->screenshot_path && Storage::disk('public')->exists($image->screenshot_path))
                                    <a href="{{ Storage::url($image->screenshot_path) }}" 
                                       download="screenshot_{{ $image->filename }}"
                                       class="btn btn-info">
                                        <i class="bi bi-download"></i> Скачать скриншот
                                    </a>
                                    @endif
                                    <button type="button" 
                                            class="btn btn-secondary"
                                            onclick="copyImageInfo('{{ $image->id }}')">
                                        <i class="bi bi-clipboard"></i> Копировать информацию
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                <!-- Вкладка Сравнение -->
                <div id="comparisonTab" class="image-tab-content" style="display: none;">
                    @if($images->count() > 0)
                    <div class="mb-4">
                        <div class="form-group">
                            <label for="imageSelect">Выберите изображение для сравнения:</label>
                            <select id="imageSelect" class="form-control" onchange="loadComparison(this.value)">
                                <option value="">-- Выберите изображение --</option>
                                @foreach($images as $image)
                                <option value="{{ $image->id }}">{{ $image->filename }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div id="comparisonContainer" style="display: none;">
                        <h5 class="mb-3">Сравнение версий изображения</h5>
                        <div class="comparison-slider-container" id="comparisonSlider">
                            <!-- Сюда будет загружено сравнение -->
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <h6><i class="bi bi-image"></i> Оригинал</h6>
                                    <p>Исходное изображение из PDF документа с оригинальным разрешением.</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-success">
                                    <h6><i class="bi bi-aspect-ratio"></i> Скриншот</h6>
                                    <p>Оптимизированная версия для отображения на экране (800×600px).</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> Нет изображений для сравнения
                    </div>
                    @endif
                </div>
            </div>
            
            <!-- Сводная статистика по изображениям -->
            <div class="card mt-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-bar-chart"></i> Статистика изображений</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <h3 class="text-primary">{{ $images->count() }}</h3>
                                <small>Всего изображений</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h3 class="text-success">{{ $images->where('thumbnail_path', '!=', null)->count() }}</h3>
                                <small>С миниатюрами</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h3 class="text-info">{{ $images->where('screenshot_path', '!=', null)->count() }}</h3>
                                <small>Со скриншотами</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                @php
                                    $totalSize = $images->sum('size');
                                    $totalSizeMB = $totalSize / (1024 * 1024);
                                @endphp
                                <h3 class="text-warning">{{ number_format($totalSizeMB, 2) }} MB</h3>
                                <small>Общий размер</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

<style>
.page-with-screenshot {
    background: white;
    padding: 20px;
    border-radius: 10px;
    border: 1px solid #dee2e6;
}

.screenshot-container {
    margin-bottom: 30px;
}

.screenshot-wrapper {
    position: relative;
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.screenshot-link {
    display: block;
    transition: transform 0.3s ease;
}

.screenshot-link:hover {
    transform: scale(1.02);
}

.screenshot-link img {
    border: 2px solid #dee2e6;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.screenshot-info {
    font-size: 0.9rem;
}

.page-content {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
}

.content-text {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    font-size: 15px;
}

.content-text p {
    margin-bottom: 1rem;
}
</style>

    <!-- Основной контент -->
<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-file-text"></i> Содержимое страницы {{ $page->page_number }}</h5>
    </div>
    <div class="card-body">
        @if($page->has_images)
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> На этой странице есть схема/изображение
            </div>
        @endif
        
        <!-- Здесь будет отображаться скриншот + текст -->
        <div class="page-content">
            {!! $page->content ?? '<p class="text-muted">Содержимое отсутствует</p>' !!}
        </div>
        
        <!-- Исходный текст (если нужен) -->
        @if(!empty($page->content_text))
        <div class="mt-4">
            <button class="btn btn-sm btn-outline-secondary" type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#rawText{{ $page->id }}">
                <i class="bi bi-code"></i> Показать исходный текст
            </button>
            <div class="collapse mt-2" id="rawText{{ $page->id }}">
                <pre class="bg-light p-3 rounded" style="max-height: 300px; overflow: auto; font-size: 12px;">{{ $page->content_text }}</pre>
            </div>
        </div>
        @endif
    </div>
</div>

    <!-- Содержимое страницы -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-text-left"></i> Содержимое страницы</h5>
                <div class="btn-group">
                    <a href="{{ route('admin.documents.processing.page.raw', ['id' => $document->id, 'pageId' => $page->id]) }}" 
                       target="_blank" class="btn btn-light btn-sm">
                        <i class="bi bi-code"></i> Исходный текст
                    </a>
                    <button type="button" class="btn btn-light btn-sm" onclick="copyPageText()">
                        <i class="bi bi-clipboard"></i> Копировать текст
                    </button>
                    @if($page->has_images)
                    <button type="button" class="btn btn-light btn-sm" onclick="toggleImageGallery()">
                        <i class="bi bi-grid"></i> Галерея
                    </button>
                    @endif
                </div>
            </div>
        </div>
        <div class="card-body">
            @if($page->section_title)
            <div class="alert alert-info mb-3">
                <i class="bi bi-tag"></i> <strong>Заголовок раздела:</strong> {{ $page->section_title }}
            </div>
            @endif
            
            <!-- Галерея изображений в контенте -->
            @if($images->count() > 0)
            <div id="imageGallery" class="image-gallery mb-4" style="display: none;">
                @foreach($images as $image)
                <div class="gallery-item">
                    <a href="{{ Storage::url($image->path) }}" target="_blank">
                        @if($image->screenshot_path)
                        <img src="{{ Storage::url($image->screenshot_path) }}" 
                             alt="{{ $image->description }}">
                        @elseif($image->thumbnail_path)
                        <img src="{{ Storage::url($image->thumbnail_path) }}" 
                             alt="{{ $image->description }}">
                        @else
                        <img src="{{ Storage::url($image->path) }}" 
                             alt="{{ $image->description }}">
                        @endif
                    </a>
                    <div class="gallery-badges">
                        @if($image->width && $image->height)
                        <span class="gallery-badge">{{ $image->width }}×{{ $image->height }}</span>
                        @endif
                        <span class="gallery-badge">{{ strtoupper($image->extension) }}</span>
                        @if($image->screenshot_path)
                        <span class="gallery-badge bg-info">Скриншот</span>
                        @endif
                        @if($image->thumbnail_path)
                        <span class="gallery-badge bg-success">Миниатюра</span>
                        @endif
                    </div>
                    <div class="gallery-caption">
                        <small>{{ $image->description }}</small>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
            
            <!-- Основной контент с изображениями -->
            <div class="document-content border p-3" style="max-height: 600px; overflow-y: auto;">
                {!! $page->content ?? '<p class="text-muted">Содержимое отсутствует</p>' !!}
            </div>
            
            <!-- Кнопка показа/скрытия галереи -->
            @if($images->count() > 0)
            <div class="text-center mt-3">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleImageGallery()">
                    <i class="bi bi-grid"></i> 
                    <span id="galleryToggleText">Показать галерею изображений</span>
                    ({{ $images->count() }})
                </button>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Переключение вкладок изображений
function switchImageTab(tabName) {
    // Скрыть все вкладки
    document.getElementById('galleryTab').style.display = 'none';
    document.getElementById('previewsTab').style.display = 'none';
    document.getElementById('comparisonTab').style.display = 'none';
    
    // Убрать активный класс со всех кнопок
    document.querySelectorAll('.image-tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Показать выбранную вкладку
    document.getElementById(tabName + 'Tab').style.display = 'block';
    
    // Добавить активный класс к выбранной кнопке
    event.target.classList.add('active');
}

// Переключение галереи изображений в контенте
function toggleImageGallery() {
    const gallery = document.getElementById('imageGallery');
    const toggleText = document.getElementById('galleryToggleText');
    
    if (gallery.style.display === 'none') {
        gallery.style.display = 'grid';
        toggleText.textContent = 'Скрыть галерею изображений';
    } else {
        gallery.style.display = 'none';
        toggleText.textContent = 'Показать галерею изображений';
    }
}

// Загрузка сравнения изображений
function loadComparison(imageId) {
    const container = document.getElementById('comparisonContainer');
    const slider = document.getElementById('comparisonSlider');
    
    if (!imageId) {
        container.style.display = 'none';
        return;
    }
    
    // Здесь можно загрузить данные изображения через AJAX
    // Для примера покажем статическое содержимое
    const image = @json($images->first());
    
    if (image) {
        slider.innerHTML = `
            <div class="comparison-slider-container">
                <div class="comparison-label" style="left: 10px;">Оригинал</div>
                <div class="comparison-label" style="right: 10px;">Скриншот</div>
                <div class="comparison-image" style="left: 0;">
                    <img src="{{ Storage::url('${image.path}') }}" alt="Оригинал">
                </div>
                <div class="comparison-image" style="right: 0;">
                    <img src="{{ Storage::url('${image.screenshot_path || image.thumbnail_path || image.path}') }}" alt="Скриншот">
                </div>
                <div class="comparison-slider" 
                     onmousedown="startDragging(event)"
                     ontouchstart="startDragging(event)">
                </div>
            </div>
        `;
        container.style.display = 'block';
    }
}

// Функции для драг-н-дроп сравнения
let isDragging = false;

function startDragging(e) {
    isDragging = true;
    document.addEventListener('mousemove', drag);
    document.addEventListener('touchmove', drag);
    document.addEventListener('mouseup', stopDragging);
    document.addEventListener('touchend', stopDragging);
    e.preventDefault();
}

function drag(e) {
    if (!isDragging) return;
    
    const container = document.querySelector('.comparison-slider-container');
    const slider = document.querySelector('.comparison-slider');
    const leftImage = document.querySelector('.comparison-image:first-child');
    
    if (!container || !slider || !leftImage) return;
    
    const containerRect = container.getBoundingClientRect();
    const x = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
    const relativeX = x - containerRect.left;
    const percentage = (relativeX / containerRect.width) * 100;
    
    const clampedPercentage = Math.max(0, Math.min(100, percentage));
    
    slider.style.left = clampedPercentage + '%';
    leftImage.style.width = clampedPercentage + '%';
}

function stopDragging() {
    isDragging = false;
    document.removeEventListener('mousemove', drag);
    document.removeEventListener('touchmove', drag);
    document.removeEventListener('mouseup', stopDragging);
    document.removeEventListener('touchend', stopDragging);
}

// Копирование информации об изображении
function copyImageInfo(imageId) {
    // Здесь можно реализовать копирование информации об изображении
    const image = @json($images->first()); // В реальности нужно найти по ID
    
    const info = `
Название: ${image.filename}
Описание: ${image.description}
Размер: ${image.width}×${image.height}
Формат: ${image.extension}
Размер файла: ${(image.size / 1024).toFixed(2)} KB
URL оригинала: {{ Storage::url('${image.path}') }}
${image.screenshot_path ? `URL скриншота: {{ Storage::url('${image.screenshot_path}') }}` : ''}
${image.thumbnail_path ? `URL миниатюры: {{ Storage::url('${image.thumbnail_path}') }}` : ''}
    `.trim();
    
    navigator.clipboard.writeText(info).then(function() {
        alert('Информация об изображении скопирована в буфер обмена');
    });
}

// Копирование текста страницы
function copyPageText() {
    const text = `{{ addslashes($page->content_text) }}`;
    navigator.clipboard.writeText(text).then(function() {
        alert('Текст страницы скопирован в буфер обмена');
    }, function(err) {
        console.error('Ошибка копирования: ', err);
    });
}

// Навигация по страницам клавишами
document.addEventListener('keydown', function(e) {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
        return;
    }
    
    @if($prevPage)
        if (e.key === 'ArrowLeft') {
            window.location.href = '{{ route("admin.documents.processing.page.show", ["id" => $document->id, "pageId" => $prevPage->id]) }}';
        }
    @endif
    
    @if($nextPage)
        if (e.key === 'ArrowRight') {
            window.location.href = '{{ route("admin.documents.processing.page.show", ["id" => $document->id, "pageId" => $nextPage->id]) }}';
        }
    @endif
});

// Лайтбокс для изображений
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.document-image img, .gallery-item img, .image-preview-item img').forEach(img => {
        img.addEventListener('click', function(e) {
            e.stopPropagation();
            
            const lightbox = document.createElement('div');
            lightbox.style.position = 'fixed';
            lightbox.style.top = '0';
            lightbox.style.left = '0';
            lightbox.style.width = '100%';
            lightbox.style.height = '100%';
            lightbox.style.backgroundColor = 'rgba(0,0,0,0.9)';
            lightbox.style.display = 'flex';
            lightbox.style.alignItems = 'center';
            lightbox.style.justifyContent = 'center';
            lightbox.style.zIndex = '9999';
            lightbox.style.cursor = 'pointer';
            lightbox.id = 'imageLightbox';
            
            const fullImg = document.createElement('img');
            fullImg.src = this.src;
            fullImg.style.maxWidth = '90%';
            fullImg.style.maxHeight = '90%';
            fullImg.style.objectFit = 'contain';
            fullImg.style.boxShadow = '0 0 20px rgba(255,255,255,0.1)';
            
            lightbox.appendChild(fullImg);
            document.body.appendChild(lightbox);
            
            // Закрытие по клику
            lightbox.addEventListener('click', function() {
                document.body.removeChild(lightbox);
            });
            
            // Закрытие по ESC
            document.addEventListener('keydown', function closeOnEsc(e) {
                if (e.key === 'Escape') {
                    const lb = document.getElementById('imageLightbox');
                    if (lb) document.body.removeChild(lb);
                    document.removeEventListener('keydown', closeOnEsc);
                }
            });
        });
    });
    
    // Подсветка изображений при наведении
    document.querySelectorAll('.document-image-container').forEach(container => {
        container.addEventListener('mouseenter', function() {
            this.style.boxShadow = '0 4px 12px rgba(0,123,255,0.2)';
        });
        
        container.addEventListener('mouseleave', function() {
            this.style.boxShadow = 'none';
        });
    });
});
</script>
<script>
// Отслеживание прогресса обработки
function startProgressTracking(documentId) {
    const progressBar = document.getElementById('processingProgress');
    const progressText = document.getElementById('progressText');
    const statusBadge = document.getElementById('processingStatus');
    
    // Показываем прогресс бар
    document.getElementById('progressContainer').style.display = 'block';
    
    // Запускаем интервал проверки
    const progressInterval = setInterval(() => {
        fetch(`/admin/documents/${documentId}/progress`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Обновляем прогресс
                    progressBar.style.width = data.progress + '%';
                    progressBar.setAttribute('aria-valuenow', data.progress);
                    progressText.textContent = data.progress + '%';
                    
                    // Обновляем статус
                    statusBadge.textContent = data.message;
                    
                    // Меняем цвет в зависимости от статуса
                    if (data.status === 'processing') {
                        progressBar.classList.remove('bg-success', 'bg-danger');
                        progressBar.classList.add('bg-info', 'progress-bar-animated', 'progress-bar-striped');
                        statusBadge.classList.remove('bg-success', 'bg-danger');
                        statusBadge.classList.add('bg-info');
                    } else if (data.status === 'completed') {
                        progressBar.classList.remove('bg-info', 'progress-bar-animated', 'progress-bar-striped');
                        progressBar.classList.add('bg-success');
                        statusBadge.classList.remove('bg-info');
                        statusBadge.classList.add('bg-success');
                        clearInterval(progressInterval);
                        
                        // Обновляем страницу через 3 секунды
                        setTimeout(() => {
                            location.reload();
                        }, 3000);
                    } else if (data.status === 'failed') {
                        progressBar.classList.remove('bg-info', 'progress-bar-animated', 'progress-bar-striped');
                        progressBar.classList.add('bg-danger');
                        statusBadge.classList.remove('bg-info');
                        statusBadge.classList.add('bg-danger');
                        clearInterval(progressInterval);
                    }
                    
                    // Обновляем статистику
                    if (data.processed_pages && data.total_pages) {
                        document.getElementById('pagesProcessed').textContent = 
                            `${data.processed_pages}/${data.total_pages}`;
                    }
                    if (data.images_count) {
                        document.getElementById('imagesFound').textContent = data.images_count;
                    }
                }
            })
            .catch(error => {
                console.error('Ошибка получения прогресса:', error);
            });
    }, 2000); // Проверяем каждые 2 секунды
    
    // Возвращаем ID интервала для отмены
    return progressInterval;
}

// Запуск обработки
function startProcessing(documentId) {
    if (!confirm('Запустить полную обработку документа? Это может занять несколько минут.')) {
        return;
    }
    
    // Блокируем кнопки
    document.querySelectorAll('.processing-btn').forEach(btn => {
        btn.disabled = true;
    });
    
    // Показываем уведомление
    const notification = document.createElement('div');
    notification.className = 'alert alert-info alert-dismissible fade show';
    notification.innerHTML = `
        <i class="bi bi-info-circle"></i> Обработка запущена. Прогресс отображается ниже.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.container').prepend(notification);
    
    // Запускаем обработку
    fetch(`/admin/documents/${documentId}/parse-full`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Запускаем отслеживание прогресса
            startProgressTracking(documentId);
        } else {
            alert('Ошибка запуска: ' + (data.error || 'Неизвестная ошибка'));
            location.reload();
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        alert('Ошибка сети. Попробуйте снова.');
        location.reload();
    });
}

// Автоматическое отслеживание если документ в обработке
document.addEventListener('DOMContentLoaded', function() {
    const documentId = {{ $document->id }};
    const documentStatus = '{{ $document->status }}';
    
    if (documentStatus === 'processing') {
        // Если документ уже в обработке, запускаем отслеживание
        startProgressTracking(documentId);
    }
    
    // Кнопка принудительной проверки
    document.getElementById('checkProgressBtn')?.addEventListener('click', function() {
        fetch(`/admin/documents/${documentId}/progress`)
            .then(response => response.json())
            .then(data => {
                alert(`Статус: ${data.status}\nПрогресс: ${data.progress}%\n${data.message}`);
                if (data.status === 'processing') {
                    startProgressTracking(documentId);
                }
            });
    });
});
</script>
@endpush