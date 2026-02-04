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
.header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.stat-card {
    background: white;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.stat-card h2 {
    margin: 0;
    font-weight: bold;
    font-size: 2rem;
}

.stat-card small {
    color: #6c757d;
    font-size: 0.85rem;
}

.image-comparison {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 20px;
}

.image-comparison-header {
    background: #f8f9fa;
    padding: 10px 15px;
    border-bottom: 1px solid #dee2e6;
    font-weight: 500;
}

.image-comparison-body {
    padding: 15px;
}

.original-image {
    position: relative;
    border-right: 2px dashed #dee2e6;
}

.original-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.8rem;
}

.processed-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: rgba(40,167,69,0.9);
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.8rem;
}

.image-container {
    position: relative;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    min-height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.image-container img {
    max-width: 100%;
    max-height: 400px;
    object-fit: contain;
}

.image-actions {
    margin-top: 15px;
}

.image-info-panel {
    background: white;
    border-radius: 8px;
    padding: 15px;
    border: 1px solid #e9ecef;
}

.image-info-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f8f9fa;
}

.image-info-item:last-child {
    border-bottom: none;
}

.image-info-label {
    font-weight: 500;
    color: #495057;
}

.image-info-value {
    color: #6c757d;
}

.progress-container {
    margin-top: 20px;
}

.processing-indicator {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.spinner-border {
    width: 1.5rem;
    height: 1.5rem;
}

.image-quality-badge {
    font-size: 0.75rem;
    padding: 3px 8px;
}

.image-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.gallery-item {
    position: relative;
    overflow: hidden;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    background: white;
    border: 1px solid #e9ecef;
}

.gallery-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.gallery-item img {
    width: 100%;
    height: 180px;
    object-fit: contain;
    background: #f8f9fa;
    padding: 10px;
    transition: transform 0.3s ease;
}

.gallery-item:hover img {
    transform: scale(1.05);
}

.gallery-caption {
    padding: 10px;
    border-top: 1px solid #e9ecef;
}

.gallery-badges {
    position: absolute;
    top: 10px;
    right: 10px;
    display: flex;
    gap: 5px;
    flex-direction: column;
    align-items: flex-end;
}

.gallery-badge {
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    backdrop-filter: blur(4px);
}

.gallery-badge.bg-success {
    background: rgba(40,167,69,0.9);
}

.gallery-badge.bg-info {
    background: rgba(23,162,184,0.9);
}

.gallery-badge.bg-warning {
    background: rgba(255,193,7,0.9);
}

.page-content-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
}

.page-content-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 15px;
    border-bottom: 1px solid #dee2e6;
}

.page-content-body {
    padding: 20px;
    max-height: 600px;
    overflow-y: auto;
}

.content-section {
    margin-bottom: 30px;
}

.content-section:last-child {
    margin-bottom: 0;
}

.section-title {
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    padding-bottom: 8px;
    margin-bottom: 15px;
    font-weight: 600;
}

.text-content {
    line-height: 1.6;
    font-size: 15px;
}

.text-content p {
    margin-bottom: 1rem;
}

.highlight-box {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 15px;
    margin: 15px 0;
    border-radius: 0 4px 4px 0;
}

.no-image-message {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.no-image-message i {
    font-size: 3rem;
    margin-bottom: 15px;
    display: block;
}

.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    border-radius: 8px;
}

.loading-spinner {
    width: 50px;
    height: 50px;
}

.compact-image-view {
    max-height: 200px;
    overflow: hidden;
    position: relative;
}

.compact-image-view::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 30px;
    background: linear-gradient(to bottom, transparent, rgba(255,255,255,0.9));
}

.expand-image-btn {
    position: absolute;
    bottom: 10px;
    right: 10px;
    z-index: 10;
}

.tab-content {
    padding: 20px 0;
}

.nav-tabs .nav-link {
    border: 1px solid transparent;
    border-bottom: none;
    border-radius: 4px 4px 0 0;
    padding: 10px 20px;
    color: #6c757d;
    font-weight: 500;
    transition: all 0.3s ease;
}

.nav-tabs .nav-link:hover {
    border-color: #e9ecef #e9ecef #dee2e6;
    color: #495057;
}

.nav-tabs .nav-link.active {
    color: #495057;
    background-color: white;
    border-color: #dee2e6 #dee2e6 white;
}

.image-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 10px;
    margin-bottom: 20px;
}

.stat-box {
    background: white;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    border: 1px solid #e9ecef;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.85rem;
    color: #6c757d;
}

.comparison-slider-container {
    position: relative;
    width: 100%;
    height: 400px;
    background: #f8f9fa;
    border-radius: 8px;
    overflow: hidden;
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

.comparison-slider {
    position: absolute;
    top: 0;
    left: 50%;
    width: 4px;
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
    width: 40px;
    height: 40px;
    background: #007bff;
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 0 10px rgba(0,0,0,0.3);
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Заголовок страницы -->
    <div class="header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-2">
                    <i class="bi bi-file-text me-2"></i>
                    Страница {{ $page->page_number }} 
                    <span class="badge bg-light text-dark ms-2">{{ $document->title }}</span>
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.documents.processing.advanced', $document->id) }}" class="text-white opacity-75">{{ Str::limit($document->title, 20) }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.documents.processing.pages.list', $document->id) }}" class="text-white opacity-75">Страницы</a></li>
                        <li class="breadcrumb-item active text-white">Страница {{ $page->page_number }}</li>
                    </ol>
                </nav>
            </div>
            <div class="btn-group">
                <a href="{{ route('admin.documents.processing.pages.list', $document->id) }}" class="btn btn-light">
                    <i class="bi bi-arrow-left me-1"></i> Назад к списку
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
 <!-- В шапке страницы, рядом с кнопками навигации -->
<div class="btn-group ms-2">
    <button type="button" class="btn btn-warning" onclick="reprocessPageImages()" 
            title="Перезапустить поиск изображений на этой странице">
        <i class="bi bi-arrow-clockwise me-1"></i> Переобработать изображения
    </button>
</div>
    <!-- Статистика страницы -->
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

    <!-- Вкладки -->
    <ul class="nav nav-tabs mb-4" id="pageTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="images-tab" data-bs-toggle="tab" data-bs-target="#images-tab-pane" type="button" role="tab">
                <i class="bi bi-images me-1"></i> Изображения 
                @if($images->count() > 0)
                <span class="badge bg-success ms-1">{{ $images->count() }}</span>
                @endif
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="content-tab" data-bs-toggle="tab" data-bs-target="#content-tab-pane" type="button" role="tab">
                <i class="bi bi-text-left me-1"></i> Содержимое
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="raw-tab" data-bs-toggle="tab" data-bs-target="#raw-tab-pane" type="button" role="tab">
                <i class="bi bi-code me-1"></i> Исходный текст
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats-tab-pane" type="button" role="tab">
                <i class="bi bi-bar-chart me-1"></i> Статистика
            </button>
        </li>
    </ul>

    <!-- Контент вкладок -->
    <div class="tab-content" id="pageTabsContent">
        
        <!-- Вкладка: Изображения -->
        <div class="tab-pane fade show active" id="images-tab-pane" role="tabpanel" tabindex="0">
            
            @if($images->count() > 0)
                <!-- Статистика изображений -->
                <div class="image-stats-grid mb-4">
                    <div class="stat-box">
                        <div class="stat-value text-primary">{{ $images->count() }}</div>
                        <div class="stat-label">Всего изображений</div>
                    </div>
                    <div class="stat-box">
                        @php
                            $withScreenshots = $images->where('has_screenshot', true)->count();
                        @endphp
                        <div class="stat-value text-success">{{ $withScreenshots }}</div>
                        <div class="stat-label">Со скриншотами</div>
                    </div>
                    <div class="stat-box">
                        @php
                            $totalSize = $images->sum('size');
                        @endphp
                        <div class="stat-value text-info">{{ number_format($totalSize / 1024, 1) }} KB</div>
                        <div class="stat-label">Общий размер</div>
                    </div>
                    <div class="stat-box">
                        @php
                            $avgQuality = $images->avg('quality') ?? 0;
                        @endphp
                        <div class="stat-value text-warning">{{ round($avgQuality * 100) }}%</div>
                        <div class="stat-label">Среднее качество</div>
                    </div>
                </div>

                <!-- Список изображений -->
                <div class="row">
                    @foreach($images as $image)
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="gallery-item">
                            <div class="position-relative">
                                @if($image->has_screenshot && $image->screenshot_url)
                                    <a href="{{ $image->screenshot_url }}" target="_blank" class="d-block">
                                        <img src="{{ $image->screenshot_url }}" 
                                             alt="Обрезанная схема" 
                                             class="img-fluid"
                                             onerror="this.onerror=null; this.src='data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 300 200\"><rect width=\"100%\" height=\"100%\" fill=\"%23f8f9fa\"/><text x=\"50%\" y=\"50%\" text-anchor=\"middle\" dy=\".3em\" fill=\"%236c757d\">Изображение</text></svg>';">
                                    </a>
                                    
                                    <div class="gallery-badges">
                                        @if($image->has_screenshot)
                                            <span class="gallery-badge bg-success">
                                                <i class="bi bi-scissors"></i> Обрезано
                                            </span>
                                        @endif
                                        @if($image->width && $image->height)
                                            <span class="gallery-badge bg-info">
                                                {{ $image->width }}×{{ $image->height }}
                                            </span>
                                        @endif
                                        @if($image->size)
                                            <span class="gallery-badge bg-dark">
                                                {{ number_format($image->size / 1024, 1) }} KB
                                            </span>
                                        @endif
                                    </div>
                                @elseif($image->url)
                                    <a href="{{ $image->url }}" target="_blank" class="d-block">
                                        <img src="{{ $image->url }}" 
                                             alt="Оригинал" 
                                             class="img-fluid"
                                             onerror="this.onerror=null; this.src='data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 300 200\"><rect width=\"100%\" height=\"100%\" fill=\"%23f8f9fa\"/><text x=\"50%\" y=\"50%\" text-anchor=\"middle\" dy=\".3em\" fill=\"%236c757d\">Изображение</text></svg>';">
                                    </a>
                                    
                                    <div class="gallery-badges">
                                        <span class="gallery-badge bg-warning">
                                            <i class="bi bi-exclamation-triangle"></i> Без скриншота
                                        </span>
                                        @if($image->width && $image->height)
                                            <span class="gallery-badge bg-info">
                                                {{ $image->width }}×{{ $image->height }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            
                            <div class="gallery-caption">
                                <h6 class="mb-1 text-truncate" title="{{ $image->description ?? 'Изображение' }}">
                                    {{ $image->description ?? 'Изображение' }}
                                </h6>
                                <div class="small text-muted">
                                    <div class="d-flex justify-content-between">
                                        <span>Формат: {{ strtoupper($image->format ?? 'JPG') }}</span>
                                        <span class="badge {{ $image->has_screenshot ? 'bg-success' : 'bg-warning' }}">
                                            {{ $image->has_screenshot ? '✓ Скриншот' : 'Нет скриншота' }}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="mt-2">
                                    <div class="btn-group btn-group-sm w-100" role="group">
                                        @if($image->has_screenshot && $image->screenshot_url)
                                            <a href="{{ $image->screenshot_url }}" 
                                               target="_blank" 
                                               class="btn btn-outline-primary">
                                                <i class="bi bi-eye"></i> Просмотр
                                            </a>
                                            <a href="{{ $image->screenshot_url }}" 
                                               download="схема_страница{{ $page->page_number }}.jpg"
                                               class="btn btn-outline-success">
                                                <i class="bi bi-download"></i> Скачать
                                            </a>
                                        @elseif($image->url)
                                            <a href="{{ $image->url }}" 
                                               target="_blank" 
                                               class="btn btn-outline-primary">
                                                <i class="bi bi-eye"></i> Оригинал
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Детальное сравнение -->
                @if($images->where('has_screenshot', true)->count() > 0)
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i> Сравнение обрезанных схем</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($images->where('has_screenshot', true)->take(3) as $image)
                            <div class="col-md-4 mb-3">
                                <div class="image-comparison">
                                    <div class="image-comparison-header">
                                        <small>{{ $image->description ?? 'Сравнение' }}</small>
                                    </div>
                                    <div class="image-comparison-body">
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <div class="text-center">
                                                    <small class="text-muted">Оригинал</small>
                                                    @if($image->url)
                                                        <div class="image-container compact-image-view mt-2">
                                                            <a href="{{ $image->url }}" target="_blank">
                                                                <img src="{{ $image->url }}" 
                                                                     alt="Оригинал"
                                                                     class="img-fluid">
                                                            </a>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-center">
                                                    <small class="text-muted">Обрезанная схема</small>
                                                    <div class="image-container compact-image-view mt-2">
                                                        <a href="{{ $image->screenshot_url }}" target="_blank">
                                                            <img src="{{ $image->screenshot_url }}" 
                                                                 alt="Схема"
                                                                 class="img-fluid">
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="image-info-panel mt-3">
                                            <div class="image-info-item">
                                                <span class="image-info-label">Качество обрезки:</span>
                                                <span class="image-info-value">
                                                    @if($image->width && $image->height)
                                                        @php
                                                            $originalRatio = $image->width / max(1, $image->height);
                                                            $screenshotRatio = 1000 / 800; // Стандартный размер скриншота
                                                            $qualityScore = 1 - min(1, abs($originalRatio - $screenshotRatio) / 2);
                                                        @endphp
                                                        <span class="badge {{ $qualityScore > 0.7 ? 'bg-success' : ($qualityScore > 0.4 ? 'bg-warning' : 'bg-danger') }}">
                                                            {{ round($qualityScore * 100) }}%
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary">N/A</span>
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="image-info-item">
                                                <span class="image-info-label">Размер оригинала:</span>
                                                <span class="image-info-value">{{ $image->width ?? '?' }}×{{ $image->height ?? '?' }}</span>
                                            </div>
                                            <div class="image-info-item">
                                                <span class="image-info-label">Размер скриншота:</span>
                                                <span class="image-info-value">1000×800</span>
                                            </div>
                                            <div class="image-info-item">
                                                <span class="image-info-label">Формат:</span>
                                                <span class="image-info-value">{{ strtoupper($image->format ?? 'JPG') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

            @else
                <!-- Нет изображений -->
                <div class="no-image-message">
                    <i class="bi bi-image text-muted"></i>
                    <h4 class="text-muted mb-3">Изображения не обнаружены</h4>
                    <p class="text-muted mb-3">
                        На этой странице не найдено схем, диаграмм или изображений.<br>
                        Возможно, страница содержит только текст.
                    </p>
                    <div class="highlight-box">
                        <p class="mb-2"><strong>Рекомендации:</strong></p>
                        <ul class="mb-0 ps-3">
                            <li>Проверьте, содержит ли страница визуальные элементы</li>
                            <li>Убедитесь, что PDF файл содержит изображения</li>
                            <li>Попробуйте перезапустить обработку документа</li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>

        <!-- Вкладка: Содержимое -->
        <div class="tab-pane fade" id="content-tab-pane" role="tabpanel" tabindex="0">
            <div class="page-content-card">
                <div class="page-content-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-text-left me-2"></i> Содержимое страницы {{ $page->page_number }}</h5>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyPageContent()">
                                <i class="bi bi-clipboard me-1"></i> Копировать
                            </button>
                            <a href="{{ route('admin.documents.processing.page.raw', ['id' => $document->id, 'pageId' => $page->id]) }}" 
                               target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-code me-1"></i> Исходный текст
                            </a>
                        </div>
                    </div>
                </div>
                <div class="page-content-body">
                    @if($page->has_images)
                        <div class="alert alert-success mb-3">
                            <i class="bi bi-check-circle me-2"></i>
                            <strong>На этой странице обнаружены схемы/изображения</strong>
                            <span class="badge bg-primary ms-2">{{ $images->count() }} изображений</span>
                        </div>
                    @endif
                    
                    @if($page->section_title)
                        <div class="alert alert-info mb-3">
                            <i class="bi bi-tag me-2"></i>
                            <strong>Раздел:</strong> {{ $page->section_title }}
                        </div>
                    @endif
                    
                    <div class="content-section">
                        <h6 class="section-title">Текстовое содержание</h6>
                        <div class="text-content">
                            {!! $page->content ?? '<p class="text-muted">Содержимое отсутствует</p>' !!}
                        </div>
                    </div>
                    
                    @if($page->parsing_quality < 0.5)
                        <div class="alert alert-warning mt-3">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Внимание:</strong> Низкое качество парсинга текста ({{ round($page->parsing_quality * 100) }}%).
                            Возможны ошибки в распознавании.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Вкладка: Исходный текст -->
        <div class="tab-pane fade" id="raw-tab-pane" role="tabpanel" tabindex="0">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-code me-2"></i> Исходный текст страницы</h5>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-light" onclick="copyRawText()">
                                <i class="bi bi-clipboard me-1"></i> Копировать
                            </button>
                            <button type="button" class="btn btn-sm btn-light" onclick="downloadRawText()">
                                <i class="bi bi-download me-1"></i> Скачать
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <pre id="rawTextContent" class="bg-light p-3 mb-0" style="max-height: 600px; overflow: auto; font-size: 13px; line-height: 1.4; margin: 0;">{{ htmlspecialchars($page->content_text ?? 'Текст отсутствует') }}</pre>
                </div>
            </div>
        </div>

        <!-- Вкладка: Статистика -->
        <div class="tab-pane fade" id="stats-tab-pane" role="tabpanel" tabindex="0">
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i> Общая информация</h5>
                        </div>
                        <div class="card-body">
                            <div class="image-info-panel">
                                <div class="image-info-item">
                                    <span class="image-info-label">Номер страницы:</span>
                                    <span class="image-info-value">{{ $page->page_number }}</span>
                                </div>
                                <div class="image-info-item">
                                    <span class="image-info-label">Количество слов:</span>
                                    <span class="image-info-value">{{ number_format($page->word_count) }}</span>
                                </div>
                                <div class="image-info-item">
                                    <span class="image-info-label">Количество символов:</span>
                                    <span class="image-info-value">{{ number_format($page->character_count) }}</span>
                                </div>
                                <div class="image-info-item">
                                    <span class="image-info-label">Качество парсинга:</span>
                                    <span class="image-info-value">
                                        <span class="badge {{ $page->parsing_quality > 0.7 ? 'bg-success' : ($page->parsing_quality > 0.4 ? 'bg-warning' : 'bg-danger') }}">
                                            {{ round($page->parsing_quality * 100) }}%
                                        </span>
                                    </span>
                                </div>
                                <div class="image-info-item">
                                    <span class="image-info-label">Изображений на странице:</span>
                                    <span class="image-info-value">{{ $images->count() }}</span>
                                </div>
                                <div class="image-info-item">
                                    <span class="image-info-label">Дата обработки:</span>
                                    <span class="image-info-value">{{ $page->updated_at->format('d.m.Y H:i') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-image me-2"></i> Статистика изображений</h5>
                        </div>
                        <div class="card-body">
                            @if($images->count() > 0)
                                <div class="image-info-panel">
                                    @php
                                        $withScreenshots = $images->where('has_screenshot', true)->count();
                                        $withoutScreenshots = $images->where('has_screenshot', false)->count();
                                        $totalSize = $images->sum('size');
                                        $avgWidth = $images->avg('width') ?? 0;
                                        $avgHeight = $images->avg('height') ?? 0;
                                    @endphp
                                    
                                    <div class="image-info-item">
                                        <span class="image-info-label">Всего изображений:</span>
                                        <span class="image-info-value">{{ $images->count() }}</span>
                                    </div>
                                    <div class="image-info-item">
                                        <span class="image-info-label">Со скриншотами:</span>
                                        <span class="image-info-value">
                                            <span class="badge bg-success">{{ $withScreenshots }}</span>
                                        </span>
                                    </div>
                                    <div class="image-info-item">
                                        <span class="image-info-label">Без скриншотов:</span>
                                        <span class="image-info-value">
                                            <span class="badge bg-warning">{{ $withoutScreenshots }}</span>
                                        </span>
                                    </div>
                                    <div class="image-info-item">
                                        <span class="image-info-label">Общий размер:</span>
                                        <span class="image-info-value">{{ number_format($totalSize / 1024, 1) }} KB</span>
                                    </div>
                                    <div class="image-info-item">
                                        <span class="image-info-label">Средний размер:</span>
                                        <span class="image-info-value">
                                            @if($images->count() > 0)
                                                {{ number_format($totalSize / $images->count() / 1024, 1) }} KB
                                            @else
                                                0 KB
                                            @endif
                                        </span>
                                    </div>
                                    <div class="image-info-item">
                                        <span class="image-info-label">Среднее разрешение:</span>
                                        <span class="image-info-value">
                                            @if($avgWidth > 0 && $avgHeight > 0)
                                                {{ round($avgWidth) }}×{{ round($avgHeight) }}
                                            @else
                                                N/A
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-2 mb-0">Нет данных об изображениях</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Качество обработки -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i> Качество обработки</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center p-3">
                                <div class="mb-2">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-success" 
                                             role="progressbar" 
                                             style="width: {{ round($page->parsing_quality * 100) }}%"
                                             aria-valuenow="{{ round($page->parsing_quality * 100) }}" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <strong>Качество парсинга текста</strong><br>
                                    <small class="text-muted">Точность распознавания текста</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="text-center p-3">
                                <div class="mb-2">
                                    <div class="progress" style="height: 20px;">
                                        @php
                                            $imageQuality = $images->count() > 0 ? $withScreenshots / $images->count() : 0;
                                        @endphp
                                        <div class="progress-bar bg-info" 
                                             role="progressbar" 
                                             style="width: {{ round($imageQuality * 100) }}%"
                                             aria-valuenow="{{ round($imageQuality * 100) }}" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <strong>Качество обработки изображений</strong><br>
                                    <small class="text-muted">Доля изображений со скриншотами</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="text-center p-3">
                                <div class="mb-2">
                                    @php
                                        $overallQuality = ($page->parsing_quality + $imageQuality) / 2;
                                    @endphp
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-warning" 
                                             role="progressbar" 
                                             style="width: {{ round($overallQuality * 100) }}%"
                                             aria-valuenow="{{ round($overallQuality * 100) }}" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <strong>Общее качество</strong><br>
                                    <small class="text-muted">Средняя оценка обработки страницы</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Копирование содержимого страницы
function copyPageContent() {
    const content = `{{ addslashes($page->content_text ?? '') }}`;
    navigator.clipboard.writeText(content).then(function() {
        showToast('success', 'Текст скопирован в буфер обмена');
    }, function(err) {
        console.error('Ошибка копирования: ', err);
        showToast('error', 'Не удалось скопировать текст');
    });
}

// Копирование исходного текста
function copyRawText() {
    const rawText = document.getElementById('rawTextContent').textContent;
    navigator.clipboard.writeText(rawText).then(function() {
        showToast('success', 'Исходный текст скопирован');
    }, function(err) {
        console.error('Ошибка копирования: ', err);
        showToast('error', 'Не удалось скопировать текст');
    });
}

// Скачивание исходного текста
function downloadRawText() {
    const text = document.getElementById('rawTextContent').textContent;
    const blob = new Blob([text], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `страница_${ {!! $page->page_number !!} }_исходный_текст.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
    showToast('success', 'Текст загружается...');
}

// Функция для показа уведомлений
function showToast(type, message) {
    // Создаем элемент уведомления
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    // Добавляем в контейнер
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(container);
    }
    
    document.getElementById('toastContainer').appendChild(toast);
    
    // Инициализируем и показываем
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Удаляем после скрытия
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}

// Навигация по страницам с клавиатуры
document.addEventListener('keydown', function(e) {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
        return;
    }
    
    // Стрелка влево - предыдущая страница
    if (e.key === 'ArrowLeft') {
        @if(isset($prevPage) && $prevPage)
            window.location.href = '{{ route("admin.documents.processing.page.show", ["id" => $document->id, "pageId" => $prevPage->id]) }}';
        @endif
    }
    
    // Стрелка вправо - следующая страница
    if (e.key === 'ArrowRight') {
        @if(isset($nextPage) && $nextPage)
            window.location.href = '{{ route("admin.documents.processing.page.show", ["id" => $document->id, "pageId" => $nextPage->id]) }}';
        @endif
    }
    
    // Ctrl + C - копировать текст текущей вкладки
    if (e.ctrlKey && e.key === 'c') {
        const activeTab = document.querySelector('.nav-tabs .nav-link.active');
        if (activeTab) {
            const tabId = activeTab.id;
            if (tabId === 'content-tab') {
                copyPageContent();
                e.preventDefault();
            } else if (tabId === 'raw-tab') {
                copyRawText();
                e.preventDefault();
            }
        }
    }
});

// Лайтбокс для изображений
document.addEventListener('DOMContentLoaded', function() {
    // Добавляем обработчики кликов на все изображения
    document.querySelectorAll('.gallery-item img, .image-container img').forEach(img => {
        img.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            showLightbox(this.src, this.alt);
        });
    });
    
    // Функция показа лайтбокса
    function showLightbox(src, alt) {
        // Создаем overlay
        const overlay = document.createElement('div');
        overlay.className = 'lightbox-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            cursor: pointer;
        `;
        
        // Создаем контейнер изображения
        const container = document.createElement('div');
        container.className = 'lightbox-container';
        container.style.cssText = `
            position: relative;
            max-width: 90%;
            max-height: 90%;
            cursor: default;
        `;
        
        // Создаем изображение
        const lightboxImg = document.createElement('img');
        lightboxImg.src = src;
        lightboxImg.alt = alt;
        lightboxImg.style.cssText = `
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
            border-radius: 4px;
            box-shadow: 0 0 30px rgba(0,0,0,0.5);
        `;
        
        // Создаем кнопку закрытия
        const closeBtn = document.createElement('button');
        closeBtn.className = 'lightbox-close';
        closeBtn.innerHTML = '<i class="bi bi-x"></i>';
        closeBtn.style.cssText = `
            position: absolute;
            top: -40px;
            right: 0;
            background: none;
            border: none;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            padding: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        `;
        
        // Создаем подпись
        const caption = document.createElement('div');
        caption.className = 'lightbox-caption';
        caption.textContent = alt;
        caption.style.cssText = `
            color: white;
            text-align: center;
            margin-top: 15px;
            font-size: 0.9rem;
            opacity: 0.8;
        `;
        
        // Собираем всё вместе
        container.appendChild(lightboxImg);
        container.appendChild(closeBtn);
        container.appendChild(caption);
        overlay.appendChild(container);
        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden'; // Отключаем скролл страницы
        
        // Обработчики закрытия
        closeBtn.addEventListener('click', closeLightbox);
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                closeLightbox();
            }
        });
        
        // Закрытие по ESC
        document.addEventListener('keydown', function escHandler(e) {
            if (e.key === 'Escape') {
                closeLightbox();
                document.removeEventListener('keydown', escHandler);
            }
        });
        
        function closeLightbox() {
            document.body.removeChild(overlay);
            document.body.style.overflow = '';
        }
    }
});

// Автоматическое обновление прогресса если страница в обработке
@if($document->status === 'processing')
setInterval(function() {
    fetch('{{ route("admin.documents.processing.progress", $document->id) }}')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.status === 'completed') {
                location.reload();
            }
        })
        .catch(error => console.error('Ошибка проверки прогресса:', error));
}, 5000); // Проверяем каждые 5 секунд
@endif

// Функция для перезапуска обработки изображений для этой страницы
function reprocessPageImages() {
    if (!confirm('Перезапустить обработку изображений для этой страницы?')) {
        return;
    }
    
    const button = event.target;
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="bi bi-arrow-clockwise spinner-border spinner-border-sm me-2"></i> Обработка...';
    
    fetch('{{ route("admin.documents.processing.page.reprocess", ["id" => $document->id, "pageId" => $page->id]) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message);
            setTimeout(() => location.reload(), 2000);
        } else {
            showToast('error', data.error || 'Ошибка обработки');
            button.disabled = false;
            button.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        showToast('error', 'Ошибка сети');
        button.disabled = false;
        button.innerHTML = originalText;
    });
}
</script>
@endpush