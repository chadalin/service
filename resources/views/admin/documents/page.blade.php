@extends('layouts.app')

@section('title', "Страница {$page->page_number} - {$document->title}")

@push('styles')
<style>
    .document-page-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    .page-header {
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 1rem;
        margin-bottom: 2rem;
    }
    
    .page-content {
        line-height: 1.8;
        font-family: 'Georgia', serif;
        font-size: 16px;
    }
    
    .page-image-gallery {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1.5rem;
        margin: 2rem 0;
    }
    
    .image-gallery-item {
        margin-bottom: 1.5rem;
        border-bottom: 1px solid #dee2e6;
        padding-bottom: 1.5rem;
    }
    
    .image-gallery-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .page-navigation {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 2rem;
    }
    
    .page-meta-info {
        background: #e9ecef;
        border-radius: 8px;
        padding: 1rem;
        font-size: 14px;
    }
    
    .highlight-section {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 1rem;
        margin: 1rem 0;
        border-radius: 0 4px 4px 0;
    }
    
    .document-heading {
        color: #2c3e50;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #dee2e6;
    }
    
    .document-paragraph {
        margin-bottom: 1rem;
        text-align: justify;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- Навигация -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.documents.index') }}">Документы</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.documents.show', $document) }}">{{ Str::limit($document->title, 30) }}</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        Страница {{ $page->page_number }}
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Заголовок документа -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h1 class="h3 mb-2">{{ $document->title }}</h1>
                            <div class="d-flex flex-wrap gap-3">
                                <span class="badge bg-primary">
                                    Страница {{ $page->page_number }} из {{ $document->total_pages }}
                                </span>
                                @if($page->section_title)
                                <span class="badge bg-info">{{ $page->section_title }}</span>
                                @endif
                                <span class="badge bg-secondary">{{ $page->word_count }} слов</span>
                            </div>
                        </div>
                        <div class="btn-group">
                            @if($page->previous_page)
                            <a href="{{ route('document.page', ['document' => $document->id, 'page' => $page->previous_page->page_number]) }}" 
                               class="btn btn-outline-primary">
                                <i class="bi bi-chevron-left"></i> Назад
                            </a>
                            @endif
                            
                            @if($page->next_page)
                            <a href="{{ route('document.page', ['document' => $document->id, 'page' => $page->next_page->page_number]) }}" 
                               class="btn btn-outline-primary">
                                Вперед <i class="bi bi-chevron-right"></i>
                            </a>
                            @endif
                            
                            <button class="btn btn-outline-secondary" onclick="window.print()">
                                <i class="bi bi-printer"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Основное содержимое -->
    <div class="row">
        <div class="col-lg-8">
            <div class="document-page-container">
                <!-- Навигация по странице -->
                <div class="page-navigation">
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Марка:</strong> {{ $document->carModel->brand->name ?? '—' }}
                        </div>
                        <div class="col-md-4">
                            <strong>Модель:</strong> {{ $document->carModel->name ?? '—' }}
                        </div>
                        <div class="col-md-4">
                            <strong>Категория:</strong> {{ $document->category->name ?? '—' }}
                        </div>
                    </div>
                </div>

                <!-- Содержимое страницы -->
                <div class="page-content">
                    @if($page->section_title)
                    <div class="highlight-section">
                        <h2 class="h4 mb-0">{{ $page->section_title }}</h2>
                    </div>
                    @endif
                    
                    {!! $page->formatted_content !!}
                </div>

                <!-- Изображения страницы -->
                @if($page->images->count() > 0)
                <div class="page-image-gallery">
                    <h3 class="h5 mb-3">
                        <i class="bi bi-images text-primary me-2"></i>
                        Изображения на странице ({{ $page->images->count() }})
                    </h3>
                    
                    @foreach($page->images as $image)
                    <div class="image-gallery-item">
                        <div class="row">
                            <div class="col-md-4">
                                <img src="{{ $image->full_url }}" 
                                     class="img-fluid rounded mb-3 mb-md-0"
                                     alt="{{ $image->description }}"
                                     style="max-height: 200px; object-fit: contain;">
                            </div>
                            <div class="col-md-8">
                                <h6 class="mb-2">Изображение {{ $loop->iteration }}</h6>
                                <p class="text-muted mb-2">{{ $image->description }}</p>
                                
                                <div class="row small text-muted mb-2">
                                    <div class="col-4">
                                        <i class="bi bi-arrows-fullscreen"></i> 
                                        {{ $image->width }}×{{ $image->height }}
                                    </div>
                                    <div class="col-4">
                                        <i class="bi bi-hdd"></i> 
                                        {{ formatBytes($image->size) }}
                                    </div>
                                    <div class="col-4">
                                        <i class="bi bi-geo-alt"></i> 
                                        Позиция {{ $image->position }}
                                    </div>
                                </div>
                                
                                @if($image->ocr_text)
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-outline-info" 
                                            type="button" 
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#ocrText{{ $image->id }}">
                                        Показать распознанный текст
                                    </button>
                                    
                                    <div class="collapse mt-2" id="ocrText{{ $image->id }}">
                                        <div class="card card-body small">
                                            {{ $image->ocr_text }}
                                        </div>
                                    </div>
                                </div>
                                @endif
                                
                                <div class="mt-3">
                                    <a href="{{ $image->full_url }}" 
                                       class="btn btn-sm btn-primary" 
                                       target="_blank" 
                                       download="{{ $image->filename }}">
                                        <i class="bi bi-download me-1"></i> Скачать
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                <!-- Метаданные страницы -->
                <div class="page-meta-info mt-4">
                    <h6 class="mb-3">Информация о странице</h6>
                    <div class="row small">
                        <div class="col-md-3">
                            <strong>Слов:</strong> {{ $page->word_count }}
                        </div>
                        <div class="col-md-3">
                            <strong>Символов:</strong> {{ $page->character_count }}
                        </div>
                        <div class="col-md-3">
                            <strong>Абзацев:</strong> {{ $page->paragraph_count }}
                        </div>
                        <div class="col-md-3">
                            <strong>Таблиц:</strong> {{ $page->tables_count }}
                        </div>
                    </div>
                    
                    @if($page->metadata && is_array($page->metadata))
                    <div class="mt-3">
                        <strong>Детали:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($page->metadata as $key => $value)
                            @if(!empty($value))
                            <li>
                                <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                @if(is_array($value))
                                    {{ implode(', ', $value) }}
                                @else
                                    {{ $value }}
                                @endif
                            </li>
                            @endif
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Навигация внизу -->
            <div class="d-flex justify-content-between mt-4">
                @if($page->previous_page)
                <a href="{{ route('document.page', ['document' => $document->id, 'page' => $page->previous_page->page_number]) }}" 
                   class="btn btn-outline-primary">
                    <i class="bi bi-chevron-left me-1"></i> 
                    Страница {{ $page->previous_page->page_number }}
                </a>
                @else
                <span></span>
                @endif
                
                @if($page->next_page)
                <a href="{{ route('document.page', ['document' => $document->id, 'page' => $page->next_page->page_number]) }}" 
                   class="btn btn-outline-primary">
                    Страница {{ $page->next_page->page_number }}
                    <i class="bi bi-chevron-right ms-1"></i>
                </a>
                @endif
            </div>
        </div>

        <!-- Боковая панель -->
        <div class="col-lg-4">
            <!-- Информация о документе -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Информация о документе</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Тип файла:</dt>
                        <dd class="col-sm-7">
                            <span class="badge bg-secondary text-uppercase">
                                {{ $document->file_type }}
                            </span>
                        </dd>
                        
                        <dt class="col-sm-5">Всего страниц:</dt>
                        <dd class="col-sm-7">{{ $document->total_pages }}</dd>
                        
                        <dt class="col-sm-5">Качество парсинга:</dt>
                        <dd class="col-sm-7">
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-{{ $document->parsing_quality > 0.7 ? 'success' : ($document->parsing_quality > 0.4 ? 'warning' : 'danger') }}" 
                                     style="width: {{ $document->parsing_quality * 100 }}%">
                                </div>
                            </div>
                            <small>{{ round($document->parsing_quality * 100) }}%</small>
                        </dd>
                        
                        <dt class="col-sm-5">Загружен:</dt>
                        <dd class="col-sm-7">
                            {{ $document->created_at->format('d.m.Y H:i') }}
                        </dd>
                        
                        <dt class="col-sm-5">Обработан:</dt>
                        <dd class="col-sm-7">
                            @if($document->parsed_at)
                                {{ $document->parsed_at->format('d.m.Y H:i') }}
                            @else
                                <span class="text-muted">Не обработан</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            <!-- Быстрые действия -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Действия</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.documents.download', $document) }}" 
                           class="btn btn-outline-primary">
                            <i class="bi bi-download me-1"></i> Скачать оригинал
                        </a>
                        
                        <a href="{{ route('document.pages', $document) }}" 
                           class="btn btn-outline-info">
                            <i class="bi bi-list-ul me-1"></i> Все страницы
                        </a>
                        
                        <a href="{{ route('admin.documents.processing.advanced', $document) }}" 
                           class="btn btn-outline-warning">
                            <i class="bi bi-gear me-1"></i> Обработка
                        </a>
                        
                        <a href="{{ route('admin.documents.edit', $document) }}" 
                           class="btn btn-outline-secondary">
                            <i class="bi bi-pencil me-1"></i> Редактировать
                        </a>
                    </div>
                </div>
            </div>

            <!-- Соседние страницы -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Соседние страницы</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @if($page->previous_page)
                        <a href="{{ route('document.page', ['document' => $document->id, 'page' => $page->previous_page->page_number]) }}" 
                           class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-chevron-left text-primary me-2"></i>
                                    <strong>Страница {{ $page->previous_page->page_number }}</strong>
                                </div>
                                <small class="text-muted">
                                    {{ $page->previous_page->word_count }} слов
                                </small>
                            </div>
                            @if($page->previous_page->section_title)
                            <small class="text-muted d-block mt-1">
                                {{ Str::limit($page->previous_page->section_title, 40) }}
                            </small>
                            @endif
                        </a>
                        @endif
                        
                        @if($page->next_page)
                        <a href="{{ route('document.page', ['document' => $document->id, 'page' => $page->next_page->page_number]) }}" 
                           class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Страница {{ $page->next_page->page_number }}</strong>
                                    <i class="bi bi-chevron-right text-primary ms-2"></i>
                                </div>
                                <small class="text-muted">
                                    {{ $page->next_page->word_count }} слов
                                </small>
                            </div>
                            @if($page->next_page->section_title)
                            <small class="text-muted d-block mt-1">
                                {{ Str::limit($page->next_page->section_title, 40) }}
                            </small>
                            @endif
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Плавная прокрутка к якорям
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Подсветка поисковых терминов в URL
    const urlParams = new URLSearchParams(window.location.search);
    const searchTerm = urlParams.get('q');
    
    if (searchTerm) {
        highlightText(searchTerm);
    }
    
    // Копирование ссылки на страницу
    const copyPageLink = document.getElementById('copyPageLink');
    if (copyPageLink) {
        copyPageLink.addEventListener('click', function() {
            const pageUrl = window.location.href;
            navigator.clipboard.writeText(pageUrl)
                .then(() => {
                    showToast('Ссылка скопирована в буфер обмена', 'success');
                })
                .catch(err => {
                    console.error('Ошибка копирования:', err);
                });
        });
    }
});

function highlightText(text) {
    const contentElement = document.querySelector('.page-content');
    if (!contentElement || !text) return;
    
    const regex = new RegExp(`(${text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
    const highlighted = contentElement.innerHTML.replace(regex, '<mark>$1</mark>');
    contentElement.innerHTML = highlighted;
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
</script>
@endpush