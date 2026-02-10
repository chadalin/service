<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ isset($metaInfo['title']) && !empty($metaInfo['title']) ? $metaInfo['title'] : $document->title }} - Страница {{ $page->page_number }}</title>
    
    @if(isset($metaInfo['description']) && !empty($metaInfo['description']))
    <meta name="description" content="{{ Str::limit($metaInfo['description'], 160) }}">
    @endif
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color: #4a6fa5;
            --secondary-color: #6c757d;
            --light-bg: #f8f9fa;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', system-ui, sans-serif;
            padding-bottom: 30px;
        }
        
        .container-xl {
            max-width: 1200px;
        }
        
        .document-header {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-left: 5px solid var(--primary-color);
        }
        
        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }
        
        @media (min-width: 992px) {
            .content-wrapper {
                grid-template-columns: 1fr 400px;
            }
        }
        
        .text-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .images-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
            height: fit-content;
        }
        
        .screenshot-container {
            margin-bottom: 1.5rem;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .screenshot-img {
            width: 100%;
            height: auto;
            cursor: zoom-in;
        }
        
        .paragraph-container {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--light-bg);
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
        }
        
        mark.bg-warning {
            background-color: #ffc107 !important;
            padding: 0.1rem 0.2rem;
            border-radius: 3px;
        }
        
        .highlight-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .meta-info {
            font-size: 0.9rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container-xl">
        <!-- Шапка документа -->
        <div class="document-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="h2 mb-2">{{ $document->title }}</h1>
                    
                    <!-- Проверка наличия бренда -->
                    @if($document->carModel && $document->carModel->brand)
                    <p class="text-muted mb-1">
                        <i class="bi bi-car-front me-1"></i>
                        {{ $document->carModel->brand->name }} {{ $document->carModel->name }}
                    </p>
                    @endif
                    
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span class="badge bg-primary">
                            <i class="bi bi-file-earmark-text me-1"></i>
                            Страница {{ $page->page_number }}
                        </span>
                        <span class="badge bg-secondary">
                            <i class="bi bi-text-left me-1"></i>
                            {{ $page->word_count ?? 0 }} слов
                        </span>
                        @if($page->section_title)
                        <span class="badge bg-info">
                            <i class="bi bi-bookmark me-1"></i>
                            {{ $page->section_title }}
                        </span>
                        @endif
                    </div>
                </div>
                
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <div class="btn-group">
                        @if($prevPage)
                        <a href="{{ route('documents.pages.show', ['id' => $document->id, 'pageNumber' => $prevPage->page_number, 'highlight' => $highlightTerm]) }}"
                           class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        @endif
                        
                        <button class="btn btn-outline-primary" onclick="window.print()">
                            <i class="bi bi-printer"></i>
                        </button>
                        
                        @if($nextPage)
                        <a href="{{ route('documents.pages.show', ['id' => $document->id, 'pageNumber' => $nextPage->page_number, 'highlight' => $highlightTerm]) }}"
                           class="btn btn-outline-primary">
                            <i class="bi bi-arrow-right"></i>
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Основной контент -->
        <div class="content-wrapper">
            <!-- Блок текста -->
            <div class="text-section">
                @if($highlightTerm)
                <div class="highlight-notice">
                    <i class="bi bi-search text-warning me-2"></i>
                    <strong>Поисковый запрос:</strong> "{{ $highlightTerm }}"
                    @if($highlightedContent && strpos(strtolower($page->content_text), strtolower($highlightTerm)) !== false)
                    <span class="badge bg-warning text-dark ms-2">
                        Найдено {{ substr_count(strtolower($page->content_text), strtolower($highlightTerm)) }} совпадений
                    </span>
                    @endif
                </div>
                @endif
                
                <h2 class="h4 mb-3">
                    <i class="bi bi-text-paragraph text-primary me-2"></i>
                    Содержание страницы
                </h2>
                
                @if($highlightedContent)
                    <!-- Показать с подсветкой -->
                    <div class="content-text">
                        {!! nl2br($highlightedContent) !!}
                    </div>
                @elseif(isset($paragraphs) && count($paragraphs) > 0)
                    <!-- Показать абзацами -->
                    @foreach($paragraphs as $index => $paragraph)
                    <div class="paragraph-container">
                        <div class="paragraph-text">
                            {!! nl2br(e($paragraph)) !!}
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        На этой странице текст не обнаружен или не распознан.
                    </div>
                @endif
                
                <!-- Мета-информация -->
                @if(isset($metaInfo) && (!empty($metaInfo['keywords']) || !empty($metaInfo['instructions'])))
                <div class="mt-4 pt-3 border-top">
                    <h5 class="h6 mb-2">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        Мета-информация
                    </h5>
                    
                    @if(!empty($metaInfo['keywords']))
                    <div class="mb-2">
                        <strong>Ключевые слова:</strong>
                        @foreach($metaInfo['keywords'] as $keyword)
                        <span class="badge bg-light text-dark me-1">{{ $keyword }}</span>
                        @endforeach
                    </div>
                    @endif
                    
                    @if(!empty($metaInfo['instructions']))
                    <div>
                        <strong>Инструкции:</strong>
                        <ul class="mb-0">
                            @foreach(array_slice($metaInfo['instructions'], 0, 3) as $instruction)
                            <li>{{ $instruction }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
                @endif
                
                <!-- Навигация -->
                <div class="mt-4 pt-3 border-top">
                    <div class="row">
                        <div class="col">
                            @if($prevPage)
                            <a href="{{ route('documents.pages.show', ['id' => $document->id, 'pageNumber' => $prevPage->page_number, 'highlight' => $highlightTerm]) }}"
                               class="btn btn-outline-primary">
                                <i class="bi bi-arrow-left me-1"></i> Предыдущая страница
                            </a>
                            @endif
                        </div>
                        <div class="col text-center">
                            <span class="text-muted">
                                Страница {{ $page->page_number }}
                                @if($document->total_pages)
                                    из {{ $document->total_pages }}
                                @endif
                            </span>
                        </div>
                        <div class="col text-end">
                            @if($nextPage)
                            <a href="{{ route('documents.pages.show', ['id' => $document->id, 'pageNumber' => $nextPage->page_number, 'highlight' => $highlightTerm]) }}"
                               class="btn btn-outline-primary">
                                Следующая страница <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Блок изображений -->
            <div class="images-section">
                <h2 class="h4 mb-3">
                    <i class="bi bi-camera text-primary me-2"></i>
                    Изображения со страницы
                </h2>
                
                @if(isset($screenshots) && count($screenshots) > 0)
                    @foreach($screenshots as $index => $screenshot)
                    <div class="screenshot-container">
                        <img src="{{ $screenshot['url'] }}" 
                             alt="{{ $screenshot['description'] }}"
                             class="screenshot-img"
                             data-bs-toggle="modal" 
                             data-bs-target="#imageModal{{ $index }}">
                        
                        <!-- Модальное окно -->
                        <div class="modal fade" id="imageModal{{ $index }}" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">{{ $screenshot['description'] }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body text-center p-0">
                                        <img src="{{ $screenshot['url'] }}" 
                                             class="img-fluid"
                                             style="max-height: 70vh;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                @elseif(isset($images) && count($images) > 0)
                    @foreach($images as $image)
                    @if($image->has_screenshot)
                    <div class="screenshot-container">
                        <img src="{{ $image->screenshot_url }}" 
                             alt="{{ $image->description ?? 'Изображение' }}"
                             class="screenshot-img"
                             onclick="openImageModal(this.src)">
                    </div>
                    @endif
                    @endforeach
                @else
                    <div class="alert alert-warning">
                        <i class="bi bi-camera-video-off me-2"></i>
                        Для этой страницы нет скриншотов.
                    </div>
                @endif
                
                <!-- Статистика -->
                <div class="mt-4 p-3 bg-light rounded">
                    <h6 class="fw-bold mb-2">Информация:</h6>
                    <div class="small">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Найдено изображений:</span>
                            <strong>{{ (isset($screenshots) ? count($screenshots) : 0) + (isset($images) ? count($images) : 0) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Слов на странице:</span>
                            <strong>{{ $page->word_count ?? 0 }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Символов:</span>
                            <strong>{{ $page->character_count ?? 0 }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function openImageModal(src) {
        const modal = new bootstrap.Modal(document.createElement('div'));
        modal._element.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-body text-center p-0">
                        <img src="${src}" class="img-fluid" style="max-height: 80vh;">
                    </div>
                </div>
            </div>
        `;
        modal.show();
    }
    
    // Горячие клавиши для навигации
    document.addEventListener('keydown', function(e) {
        @if($prevPage)
        if (e.key === 'ArrowLeft') {
            window.location.href = "{{ route('documents.pages.show', ['id' => $document->id, 'pageNumber' => $prevPage->page_number, 'highlight' => $highlightTerm]) }}";
        }
        @endif
        
        @if($nextPage)
        if (e.key === 'ArrowRight') {
            window.location.href = "{{ route('documents.pages.show', ['id' => $document->id, 'pageNumber' => $nextPage->page_number, 'highlight' => $highlightTerm]) }}";
        }
        @endif
        
        // Пробел для скролла
        if (e.key === ' ' && !e.target.matches('input, textarea')) {
            e.preventDefault();
            window.scrollBy(0, window.innerHeight * 0.8);
        }
    });
    </script>
</body>
</html>