@extends('layouts.app')

@section('title', $document->title . ' - AutoDoc AI')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Левая панель: Навигация и метаданные -->
        <div class="col-md-3 col-lg-2">
            <!-- Навигация по документу -->
            <div class="card mb-3">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0">📄 Навигация</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <a href="#document-content" class="list-group-item list-group-item-action">
                            📖 Содержание
                        </a>
                        <a href="#document-meta" class="list-group-item list-group-item-action">
                            📊 Метаданные
                        </a>
                        <a href="#document-sections" class="list-group-item list-group-item-action">
                            📑 Разделы
                        </a>
                        <a href="#similar-documents" class="list-group-item list-group-item-action">
                            🔗 Похожие документы
                        </a>
                    </div>
                </div>
            </div>

            <!-- Быстрые действия -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">⚡ Действия</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.documents.download', $document) }}" 
                           class="btn btn-outline-primary btn-sm">
                            ⬇️ Скачать оригинал
                        </a>
                        
                        @if($document->status === 'processed')
                        <a href="{{ route('admin.documents.preview', $document) }}" 
                           target="_blank" 
                           class="btn btn-outline-success btn-sm">
                            👁️ Предпросмотр
                        </a>
                        @endif
                        
                        <a href="{{ route('admin.documents.edit', $document) }}" 
                           class="btn btn-outline-warning btn-sm">
                            ✏️ Редактировать
                        </a>
                        
                        <form action="{{ route('admin.documents.reprocess', $document) }}" 
                              method="POST" 
                              class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-info btn-sm w-100">
                                🔄 Переобработать
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Статус документа -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">📈 Статус</h6>
                </div>
                <div class="card-body">
                    @switch($document->status)
                        @case('processed')
                            <span class="badge bg-success">✅ Обработан</span>
                            @break
                        @case('processing')
                            <span class="badge bg-warning">🔄 Обработка</span>
                            @break
                        @case('pending')
                            <span class="badge bg-secondary">⏳ В очереди</span>
                            @break
                        @case('error')
                            <span class="badge bg-danger">❌ Ошибка</span>
                            @break
                        @default
                            <span class="badge bg-secondary">{{ $document->status }}</span>
                    @endswitch
                    
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="fas fa-calendar"></i> 
                            Создан: {{ $document->created_at->format('d.m.Y H:i') }}
                        </small><br>
                        @if($document->parsed_at)
                        <small class="text-muted">
                            <i class="fas fa-sync"></i> 
                            Обработан: {{ $document->parsed_at->format('d.m.Y H:i') }}
                        </small>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Основное содержимое -->
        <div class="col-md-9 col-lg-10">
            <!-- Заголовок и информация -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">{{ $document->title }}</h4>
                            <div class="text-muted">
                                <span class="me-3">
                                    <i class="fas fa-car"></i> 
                                    {{ $document->carModel->brand->name ?? 'Бренд не указан' }} 
                                    {{ $document->carModel->name ?? 'Модель не указана' }}
                                </span>
                                <span class="me-3">
                                    <i class="fas fa-tools"></i> 
                                    {{ $document->category->name ?? 'Категория не указана' }}
                                </span>
                                <span>
                                    <i class="fas fa-file"></i> 
                                    {{ strtoupper($document->file_type) }} • 
                                    {{ number_format($document->word_count ?? 0) }} слов
                                </span>
                            </div>
                        </div>
                        <div>
                            <span class="badge bg-info">{{ $document->id }}</span>
                        </div>
                    </div>
                </div>
                
                @if(session('success'))
                    <div class="alert alert-success m-3">
                        {{ session('success') }}
                    </div>
                @endif
                
                @if($document->status === 'error')
                    <div class="alert alert-danger m-3">
                        <strong>Ошибка обработки:</strong> {{ $document->content_text }}
                    </div>
                @endif
            </div>

            <!-- Основное содержимое документа -->
            <div class="card mb-4" id="document-content">
                <div class="card-header">
                    <h5 class="mb-0">📖 Содержание документа</h5>
                </div>
                <div class="card-body">
                    @if($document->status === 'processed' && !empty($document->content_text))
                        <div class="document-content">
                            {!! nl2br(e($document->content_text)) !!}
                        </div>
                    @elseif($document->status === 'processing')
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                            <p class="mt-3">Документ обрабатывается...</p>
                        </div>
                    @elseif($document->status === 'pending')
                        <div class="text-center py-5">
                            <i class="fas fa-clock fa-3x text-secondary mb-3"></i>
                            <p>Документ в очереди на обработку</p>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            Содержимое документа недоступно или еще не обработано.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Разделы документа -->
            @if(!empty($document->sections) && is_array($document->sections))
            <div class="card mb-4" id="document-sections">
                <div class="card-header">
                    <h5 class="mb-0">📑 Структура документа</h5>
                </div>
                <div class="card-body">
                    <div class="accordion" id="sectionsAccordion">
                        @foreach($document->sections as $index => $section)
                            @if(!empty($section['title']) || !empty($section['content']))
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading{{ $index }}">
                                    <button class="accordion-button collapsed" type="button" 
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#collapse{{ $index }}">
                                        {{ $section['title'] ?? 'Раздел ' . ($index + 1) }}
                                        @if(!empty($section['level']))
                                            <span class="badge bg-secondary ms-2">Уровень {{ $section['level'] }}</span>
                                        @endif
                                    </button>
                                </h2>
                                <div id="collapse{{ $index }}" class="accordion-collapse collapse" 
                                     data-bs-parent="#sectionsAccordion">
                                    <div class="accordion-body">
                                        @if(!empty($section['content']))
                                            {!! nl2br(e($section['content'])) !!}
                                        @else
                                            <p class="text-muted">Нет содержимого</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Метаданные и ключевые слова -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4" id="document-meta">
                        <div class="card-header">
                            <h5 class="mb-0">📊 Метаданные</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Тип документа:</dt>
                                <dd class="col-sm-8">
                                    {{ $document->metadata['document_type'] ?? 'Не определен' }}
                                </dd>
                                
                                <dt class="col-sm-4">Сложность ремонта:</dt>
                                <dd class="col-sm-8">
                                    @switch($document->metadata['difficulty'] ?? 'medium')
                                        @case('легко')<span class="badge bg-success">Легко</span>@break
                                        @case('средне')<span class="badge bg-warning">Средне</span>@break
                                        @case('сложно')<span class="badge bg-danger">Сложно</span>@break
                                        @default<span class="badge bg-secondary">{{ $document->metadata['difficulty'] ?? 'Средняя' }}</span>
                                    @endswitch
                                </dd>
                                
                                <dt class="col-sm-4">Ориентировочное время:</dt>
                                <dd class="col-sm-8">
                                    @if(!empty($document->metadata['estimated_time']))
                                        {{ implode(', ', $document->metadata['estimated_time']) }}
                                    @else
                                        Не указано
                                    @endif
                                </dd>
                                
                                <dt class="col-sm-4">Загрузил:</dt>
                                <dd class="col-sm-8">
                                    {{ $document->uploadedByUser->name ?? 'Система' }}
                                </dd>
                                
                                <dt class="col-sm-4">Исходный файл:</dt>
                                <dd class="col-sm-8">
                                    <code>{{ $document->original_filename }}</code>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">🔑 Ключевые слова</h5>
                        </div>
                        <div class="card-body">
                            @if(!empty($document->keywords) && is_array($document->keywords))
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach(array_slice($document->keywords, 0, 20) as $keyword)
                                        <span class="badge bg-primary">{{ $keyword }}</span>
                                    @endforeach
                                </div>
                                @if(count($document->keywords) > 20)
                                    <p class="text-muted mt-2">
                                        и еще {{ count($document->keywords) - 20 }} ключевых слов...
                                    </p>
                                @endif
                            @else
                                <p class="text-muted">Ключевые слова не извлечены</p>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Запчасти -->
                    @if(!empty($document->metadata['car_parts']))
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">🔩 Запчасти</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($document->metadata['car_parts'] as $part)
                                    <span class="badge bg-secondary">{{ $part }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Похожие документы -->
            @if($similarDocuments->count() > 0)
            <div class="card" id="similar-documents">
                <div class="card-header">
                    <h5 class="mb-0">🔗 Похожие документы</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($similarDocuments as $similar)
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <a href="{{ route('admin.documents.show', $similar) }}">
                                                {{ $similar->title }}
                                            </a>
                                        </h6>
                                        <p class="card-text small text-muted">
                                            <i class="fas fa-car"></i> 
                                            {{ $similar->carModel->brand->name ?? '' }} 
                                            {{ $similar->carModel->name ?? '' }}<br>
                                            <i class="fas fa-file"></i> 
                                            {{ strtoupper($similar->file_type) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
.document-content {
    line-height: 1.6;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.document-content p {
    margin-bottom: 1rem;
}

.accordion-button:not(.collapsed) {
    background-color: #f8f9fa;
    color: #0d6efd;
}

.badge {
    font-weight: 500;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Автоматически открываем первый раздел
    const firstSection = document.querySelector('.accordion-button');
    if (firstSection) {
        firstSection.click();
    }
    
    // Добавляем плавную прокрутку для навигации
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if(targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if(targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 20,
                    behavior: 'smooth'
                });
            }
        });
    });
});
</script>
@endsection