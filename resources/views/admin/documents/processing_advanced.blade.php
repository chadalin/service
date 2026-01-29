@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1><i class="fas fa-file-alt"></i> Расширенная обработка документа</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.documents.processing.index') }}"><i class="fas fa-tasks"></i> Обработка документов</a></li>
                    <li class="breadcrumb-item active">{{ $document->title }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Информация о документе -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-info-circle"></i> Информация о документе</h5>
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
                                    <th>Модель авто:</th>
                                    <td>
                                        @if($document->carModel && $document->carModel->brand)
                                            {{ $document->carModel->brand->name }} {{ $document->carModel->name }}
                                        @else
                                            <span class="text-muted">Не указана</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Категория:</th>
                                    <td>{{ $document->category->name ?? '<span class="text-muted">Не указана</span>' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="40%">Тип файла:</th>
                                    <td><span class="badge bg-info">{{ strtoupper($document->file_type) }}</span></td>
                                </tr>
                                <tr>
                                    <th>Размер файла:</th>
                                    <td>{{ $stats['file_size'] ?? 'Неизвестно' }}</td>
                                </tr>
                                <tr>
                                    <th>Статус:</th>
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
                                            
                                            // Метки статусов
                                            $statusLabels = [
                                                'uploaded' => 'Загружен',
                                                'processing' => 'В обработке',
                                                'parsed' => 'Распарсен',
                                                'preview_created' => 'Предпросмотр создан',
                                                'parse_error' => 'Ошибка парсинга'
                                            ];
                                            $statusLabel = $statusLabels[$document->status] ?? $document->status;
                                        @endphp
                                        <span class="badge bg-{{ $statusColor }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Парсинг:</th>
                                    <td>
                                        @if($document->is_parsed)
                                            <span class="badge bg-success">Выполнен</span>
                                        @elseif($document->status === 'preview_created')
                                            <span class="badge bg-info">Предпросмотр</span>
                                        @else
                                            <span class="badge bg-secondary">Не выполнен</span>
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
                    <h5 class="card-title mb-0"><i class="fas fa-chart-bar"></i> Статистика документа</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 col-sm-6 text-center mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Страницы</h6>
                                    <h2 class="text-primary">{{ $stats['pages_count'] ?? 0 }}</h2>
                                    <small class="text-muted">из {{ $stats['total_pages'] ?? '?' }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 text-center mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Слова</h6>
                                    <h2 class="text-success">{{ number_format($stats['words_count'] ?? 0) }}</h2>
                                    <small class="text-muted">{{ number_format($stats['characters_count'] ?? 0) }} символов</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 text-center mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Изображения</h6>
                                    <h2 class="text-warning">{{ $stats['images_count'] ?? 0 }}</h2>
                                    <small class="text-muted">извлечено</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 text-center mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Качество</h6>
                                    <div class="progress mb-2" style="height: 20px;">
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
    </div>

    <!-- Панель управления с простыми ссылками -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-cogs"></i> Управление обработкой</h5>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
                    @if(session('info'))
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="fas fa-info-circle"></i> {{ session('info') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle"></i> <strong>Информация:</strong> Для больших документов рекомендуется сначала создать предпросмотр (5 страниц), затем выполнить полный парсинг.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <form action="{{ route('admin.documents.processing.create-preview', $document->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100 h-100" 
                                        onclick="return confirm('Создать предпросмотр документа (первые 5 страниц)?')">
                                    <i class="fas fa-eye"></i> Создать предпросмотр
                                    <br>
                                    <small class="d-block">(первые 5 страниц)</small>
                                </button>
                            </form>
                        </div>
                        <div class="col-md-3 mb-3">
                            <form action="{{ route('admin.documents.processing.parse-full', $document->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success w-100 h-100" 
                                        onclick="return confirm('Выполнить полный парсинг всего документа? Это может занять некоторое время для больших файлов.')">
                                    <i class="fas fa-cogs"></i> Полный парсинг
                                    <br>
                                    <small class="d-block">(весь документ)</small>
                                </button>
                            </form>
                        </div>
                        <div class="col-md-3 mb-3">
                            <form action="{{ route('admin.documents.processing.reset-status', $document->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-warning w-100 h-100" 
                                        onclick="return confirm('Вы уверены, что хотите сбросить статус обработки? Все данные парсинга будут удалены.')">
                                    <i class="fas fa-redo"></i> Сбросить статус
                                    <br>
                                    <small class="d-block">(начать заново)</small>
                                </button>
                            </form>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('admin.documents.processing.index') }}" class="btn btn-secondary w-100 h-100">
                                <i class="fas fa-arrow-left"></i> Вернуться к списку
                                <br>
                                <small class="d-block">(все документы)</small>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Статус обработки -->
                    @if($document->status === 'processing')
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="alert alert-warning">
                                <i class="fas fa-sync-alt fa-spin"></i> <strong>Документ в обработке</strong>
                                <p class="mb-1">Прогресс: {{ $document->parsing_progress ?? 0 }}%</p>
                                <p class="mb-0">Пожалуйста, обновите страницу через некоторое время.</p>
                            </div>
                        </div>
                    </div>
                    @endif
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
                        <h5 class="card-title mb-0"><i class="fas fa-file-pdf"></i> Предпросмотр документа</h5>
                        @if($previewPages->count() > 0)
                        <form action="{{ route('admin.documents.processing.delete-preview', $document->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-light" 
                                    onclick="return confirm('Удалить предпросмотр документа?')">
                                <i class="fas fa-trash"></i> Удалить предпросмотр
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if($previewPages->count() > 0)
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i> Создан предпросмотр {{ $previewPages->count() }} страниц из {{ $stats['total_pages'] ?? '?' }}.
                                </div>
                            </div>
                            
                            @foreach($previewPages as $page)
                            <div class="col-md-12 mb-4">
                                <div class="card border">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">
                                                <i class="fas fa-file"></i> Страница {{ $page->page_number }}
                                                @if($page->section_title)
                                                    <span class="badge bg-info ms-2">{{ $page->section_title }}</span>
                                                @endif
                                            </h6>
                                            <div>
                                                <span class="badge bg-secondary me-2">
                                                    <i class="fas fa-font"></i> {{ $page->word_count }} слов
                                                </span>
                                                @if($page->has_images ?? false)
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-image"></i> {{ $page->images->count() }} изображений
                                                </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        @if($page->images->count() > 0)
                                        <div class="row">
                                            <div class="col-md-4">
                                                <h6><i class="fas fa-images"></i> Извлеченные изображения:</h6>
                                                <div class="row">
                                                    @foreach($page->images as $image)
                                                    <div class="col-6 mb-3">
                                                        <div class="card">
                                                            <a href="{{ $image->url ?? '#' }}" target="_blank" class="text-decoration-none">
                                                                <img src="{{ $image->thumbnail_url ?? $image->url ?? '#' }}" 
                                                                     alt="{{ $image->description }}" 
                                                                     class="card-img-top img-thumbnail"
                                                                     style="height: 120px; object-fit: contain;"
                                                                     onerror="this.onerror=null; this.src='{{ asset('images/no-image.png') }}';">
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
                                                <h6><i class="fas fa-align-left"></i> Содержимое:</h6>
                                                <div class="document-content border p-3" style="max-height: 400px; overflow-y: auto;">
                                                    {!! $page->content !!}
                                                </div>
                                                @if(!empty($page->content_text))
                                                <div class="mt-3">
                                                    <button class="btn btn-sm btn-outline-secondary" type="button" 
                                                            data-bs-toggle="collapse" 
                                                            data-bs-target="#rawText{{ $page->id }}">
                                                        <i class="fas fa-code"></i> Показать исходный текст
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
                        <div class="text-center py-5">
                            <i class="fas fa-file-pdf fa-4x text-muted mb-3"></i>
                            <h4>Предпросмотр не создан</h4>
                            <p class="text-muted">Нажмите "Создать предпросмотр" для извлечения первых 5 страниц документа.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.document-content {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    font-size: 14px;
}
.document-content p {
    margin-bottom: 1rem;
    text-align: justify;
}
.document-heading {
    font-weight: bold;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
    color: #2c3e50;
    border-bottom: 2px solid #3498db;
    padding-bottom: 5px;
}
.document-paragraph {
    color: #34495e;
}
.part-number {
    background-color: #fff3cd;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: monospace;
}
.btn {
    transition: all 0.3s ease;
}
.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: box-shadow 0.3s ease;
}
.card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}
</style>
@endpush