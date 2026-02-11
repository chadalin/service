<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    
    @if(!empty($metaInfo['description']))
    <meta name="description" content="{{ Str::limit($metaInfo['description'], 160) }}">
    @endif
    
    @if(!empty($metaInfo['keywords']))
    <meta name="keywords" content="{{ implode(', ', $metaInfo['keywords']) }}">
    @endif
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color: #4a6fa5;
            --secondary-color: #6c757d;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --light-bg: #f8f9fa;
            --border-color: #e5e7eb;
        }
        
        body {
            background-color: #f3f4f6;
            font-family: 'Segoe UI', system-ui, sans-serif;
            padding-bottom: 40px;
        }
        
        .container-xl {
            max-width: 1400px;
        }
        
        /* Шапка документа */
        .document-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
            border-radius: 16px;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .document-header h1 {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
        }
        
        /* Основной контент */
        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }
        
        @media (min-width: 1200px) {
            .content-wrapper {
                grid-template-columns: 1fr 400px;
            }
        }
        
        /* Секция текста */
        .text-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        /* Секция изображений */
        .images-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            position: sticky;
            top: 20px;
            height: fit-content;
        }
        
        /* Диагностическая информация */
        .diagnostic-section {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 5px solid var(--primary-color);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .error-code-badge {
            display: inline-block;
            background: #1e293b;
            color: #fbbf24;
            font-family: monospace;
            font-size: 1.1rem;
            font-weight: 700;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            border: 1px solid #fbbf24;
        }
        
        .symptom-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid var(--border-color);
            transition: all 0.2s;
        }
        
        .symptom-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.08);
            border-color: var(--primary-color);
        }
        
        .cause-tag {
            display: inline-block;
            background: #fee2e2;
            color: #991b1b;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            margin: 0.2rem;
        }
        
        .step-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .step-number {
            background: var(--primary-color);
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        
        /* Параграфы */
        .paragraph-container {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 12px;
            border-left: 4px solid var(--primary-color);
        }
        
        .paragraph-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .paragraph-text {
            line-height: 1.7;
            font-size: 1rem;
            color: #334155;
            white-space: pre-line;
        }
        
        /* Подсветка */
        mark.bg-warning {
            background-color: #fef3c7 !important;
            color: #92400e !important;
            padding: 0.1rem 0.2rem;
            border-radius: 4px;
            font-weight: 600;
        }
        
        mark.bg-info {
            background-color: #dbeafe !important;
            color: #1e40af !important;
            padding: 0.1rem 0.2rem;
            border-radius: 4px;
            font-weight: 600;
        }
        
        .highlight-notice {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        /* Запчасти */
        .part-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.2s;
        }
        
        .part-card:hover {
            border-color: var(--success-color);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.1);
        }
        
        .part-sku {
            font-family: monospace;
            background: #f1f5f9;
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
            font-size: 0.8rem;
            color: #475569;
        }
        
        .part-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--success-color);
        }
        
        /* Скриншоты */
        .screenshot-container {
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .screenshot-container:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        
        .screenshot-img {
            width: 100%;
            height: auto;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .screenshot-img:hover {
            transform: scale(1.02);
        }
        
        /* Кнопки */
        .btn-group {
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .btn-outline-primary {
            border-color: var(--border-color);
            color: #1e293b;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        /* Анимации */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Модальное окно */
        .modal-content {
            border-radius: 16px;
            overflow: hidden;
        }
        
        .modal-body {
            padding: 0;
            background: #0f172a;
        }
        
        .modal-image {
            max-height: 80vh;
            object-fit: contain;
        }
    </style>
</head>
<body>
    <div class="container-xl">
        <!-- Шапка документа -->
        <div class="document-header fade-in">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="h2 mb-2">{{ $document->title }}</h1>
                    
                    @if($document->carModel && $document->carModel->brand)
                    <p class="text-light opacity-90 mb-2">
                        <i class="bi bi-car-front me-2"></i>
                        {{ $document->carModel->brand->name }} {{ $document->carModel->name }}
                        ({{ $document->carModel->year_from ?? '?' }} - {{ $document->carModel->year_to ?? 'н.в.' }})
                    </p>
                    @endif
                    
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="badge bg-primary">
                            <i class="bi bi-file-earmark-text me-1"></i>
                            Страница {{ $page->page_number }}
                        </span>
                        
                        @if(!empty($errorCodes))
                        <span class="badge bg-warning text-dark">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Код ошибки: {{ implode(', ', array_slice($errorCodes, 0, 2)) }}
                        </span>
                        @endif
                        
                        @if($page->section_title)
                        <span class="badge bg-info">
                            <i class="bi bi-bookmark me-1"></i>
                            {{ Str::limit($page->section_title, 30) }}
                        </span>
                        @endif
                    </div>
                </div>
                
                <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                    <div class="btn-group" role="group">
                        @if($prevPage)
                        <a href="{{ route('documents.pages.show', ['id' => $document->id, 'pageNumber' => $prevPage->page_number, 'highlight' => $highlightTerm]) }}"
                           class="btn btn-light">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        @endif
                        
                        <button class="btn btn-light" onclick="window.print()">
                            <i class="bi bi-printer"></i>
                        </button>
                        
                        @if($nextPage)
                        <a href="{{ route('documents.pages.show', ['id' => $document->id, 'pageNumber' => $nextPage->page_number, 'highlight' => $highlightTerm]) }}"
                           class="btn btn-light">
                            <i class="bi bi-arrow-right"></i>
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ДИАГНОСТИЧЕСКАЯ ИНФОРМАЦИЯ -->
        @if(!empty($errorCodes) || !empty($relatedSymptoms) || !empty($relatedRules))
        <div class="diagnostic-section fade-in">
            <div class="d-flex align-items-center mb-3">
                <i class="bi bi-clipboard-check fs-3 text-primary me-3"></i>
                <h3 class="h4 mb-0">Диагностическая информация</h3>
            </div>
            
            <!-- Коды ошибок -->
            @if(!empty($errorCodes))
            <div class="mb-4">
                <h5 class="h6 fw-bold text-uppercase text-secondary mb-3">
                    <i class="bi bi-exclamation-diamond me-1"></i>
                    Обнаруженные коды ошибок
                </h5>
                <div>
                    @foreach($errorCodes as $code)
                    <span class="error-code-badge">
                        <i class="bi bi-code-square me-1"></i>
                        {{ $code }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endif
            
            <!-- Симптомы и правила -->
            @if(!empty($relatedRules))
            <div class="row g-4">
                @foreach($relatedRules as $rule)
                <div class="col-md-6">
                    <div class="symptom-card h-100">
                        <h5 class="h6 fw-bold text-primary mb-2">
                            <i class="bi bi-pin-angle me-1"></i>
                            {{ $rule['symptom_name'] }}
                        </h5>
                        
                        @if(!empty($rule['possible_causes']))
                        <div class="mb-3">
                            <small class="text-danger fw-bold d-block mb-2">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Возможные причины:
                            </small>
                            @foreach($rule['possible_causes'] as $cause)
                            <span class="cause-tag">{{ $cause }}</span>
                            @endforeach
                        </div>
                        @endif
                        
                        @if(!empty($rule['diagnostic_steps']))
                        <div class="mb-3">
                            <small class="text-primary fw-bold d-block mb-2">
                                <i class="bi bi-list-check me-1"></i>
                                Шаги диагностики:
                            </small>
                            @foreach($rule['diagnostic_steps'] as $index => $step)
                            <div class="step-item">
                                <span class="step-number">{{ $index + 1 }}</span>
                                <span class="small">{{ $step }}</span>
                            </div>
                            @endforeach
                        </div>
                        @endif
                        
                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                            <span class="small text-muted">
                                <i class="bi bi-clock me-1"></i>
                                {{ $rule['estimated_time'] ?? 30 }} мин
                            </span>
                            <span class="fw-bold text-success">
                                {{ number_format($rule['price'] ?? 3000, 0, '', ' ') }} ₽
                            </span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
            
            <!-- Рекомендуемые запчасти -->
            @if(!empty($recommendedParts))
            <div class="mt-4 pt-3 border-top">
                <h5 class="h6 fw-bold text-uppercase text-secondary mb-3">
                    <i class="bi bi-tools me-1"></i>
                    Рекомендуемые запчасти
                </h5>
                <div class="row g-3">
                    @foreach($recommendedParts as $part)
                    <div class="col-md-4">
                        <div class="part-card">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="part-sku">{{ $part['sku'] }}</span>
                                <span class="badge bg-{{ $part['quantity'] > 10 ? 'success' : ($part['quantity'] > 0 ? 'warning' : 'secondary') }}">
                                    {{ $part['availability'] }}
                                </span>
                            </div>
                            <p class="fw-bold mb-2 small">{{ $part['name'] }}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="part-price">{{ $part['formatted_price'] }} ₽</span>
                                <a href="{{ $part['url'] }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="bi bi-cart-plus me-1"></i>
                                    Детали
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif
        
        <!-- Основной контент -->
        <div class="content-wrapper">
            <!-- Блок текста -->
            <div class="text-section fade-in">
                @if($highlightTerm)
                <div class="highlight-notice">
                    <div>
                        <i class="bi bi-search fs-4 text-warning"></i>
                    </div>
                    <div class="flex-grow-1">
                        <strong>Поисковый запрос:</strong> "{{ $highlightTerm }}"
                        @if($highlightedContent && strpos(strtolower($page->content_text), strtolower($highlightTerm)) !== false)
                        <span class="badge bg-warning text-dark ms-2">
                            Найдено {{ substr_count(strtolower($page->content_text), strtolower($highlightTerm)) }} совпадений
                        </span>
                        @endif
                    </div>
                </div>
                @endif
                
                <h2 class="h4 mb-4 d-flex align-items-center">
                    <i class="bi bi-text-paragraph text-primary me-2"></i>
                    Содержание страницы
                    @if($page->word_count)
                    <span class="badge bg-secondary ms-2">{{ $page->word_count }} слов</span>
                    @endif
                </h2>
                
                @if($highlightedContent)
                    <!-- Текст с подсветкой -->
                    <div class="content-text">
                        {!! nl2br($highlightedContent) !!}
                    </div>
                @elseif(!empty($paragraphs))
                    <!-- Умная разбивка на абзацы -->
                    @foreach($paragraphs as $index => $paragraph)
                    <div class="paragraph-container" id="paragraph-{{ $index + 1 }}">
                        <div class="paragraph-title">
                            <span class="badge bg-primary rounded-pill me-2">{{ $index + 1 }}</span>
                            {{ $paragraph['title'] }}
                            <small class="text-muted ms-2">{{ $paragraph['lines'] }} строк</small>
                        </div>
                        <div class="paragraph-text">
                            {!! nl2br(e($paragraph['content'])) !!}
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
                @if(!empty($metaInfo['instructions']))
                <div class="mt-4 pt-4 border-top">
                    <h5 class="h6 mb-3">
                        <i class="bi bi-wrench-adjustable-circle text-primary me-2"></i>
                        Инструкции из документа
                    </h5>
                    <div class="bg-light p-3 rounded">
                        <ul class="mb-0">
                            @foreach($metaInfo['instructions'] as $instruction)
                            <li class="mb-2">{{ $instruction }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif
                
                <!-- Навигация -->
                <div class="mt-4 pt-4 border-top">
                    <div class="row g-3">
                        <div class="col-sm-4">
                            @if($prevPage)
                            <a href="{{ route('documents.pages.show', ['id' => $document->id, 'pageNumber' => $prevPage->page_number, 'highlight' => $highlightTerm]) }}"
                               class="btn btn-outline-primary w-100">
                                <i class="bi bi-arrow-left me-1"></i>
                                Предыдущая
                            </a>
                            @endif
                        </div>
                        <div class="col-sm-4 text-center">
                            <span class="badge bg-light text-dark p-2">
                                <i class="bi bi-file-earmark me-1"></i>
                                {{ $page->page_number }} / {{ $document->total_pages ?? '?' }}
                            </span>
                        </div>
                        <div class="col-sm-4 text-end">
                            @if($nextPage)
                            <a href="{{ route('documents.pages.show', ['id' => $document->id, 'pageNumber' => $nextPage->page_number, 'highlight' => $highlightTerm]) }}"
                               class="btn btn-outline-primary w-100">
                                Следующая
                                <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Блок изображений -->
            <div class="images-section fade-in">
                <h2 class="h4 mb-4 d-flex align-items-center">
                    <i class="bi bi-camera text-primary me-2"></i>
                    Изображения со страницы
                </h2>
                
                @if(!empty($screenshots))
                    @foreach($screenshots as $index => $screenshot)
                    <div class="screenshot-container">
                        <img src="{{ $screenshot['url'] }}" 
                             alt="{{ $screenshot['alt'] }}"
                             class="screenshot-img"
                             data-bs-toggle="modal" 
                             data-bs-target="#imageModal{{ $index }}">
                        
                        <div class="p-2 bg-light border-top">
                            <small class="text-muted d-block text-center">
                                {{ $screenshot['description'] }}
                            </small>
                        </div>
                    </div>
                    
                    <!-- Модальное окно -->
                    <div class="modal fade" id="imageModal{{ $index }}" tabindex="-1">
                        <div class="modal-dialog modal-xl modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header border-0 bg-dark text-white">
                                    <h5 class="modal-title">{{ $screenshot['description'] }}</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center p-0 bg-dark">
                                    <img src="{{ $screenshot['url'] }}" 
                                         class="img-fluid modal-image"
                                         style="max-height: 80vh;">
                                </div>
                                <div class="modal-footer border-0 bg-dark">
                                    <a href="{{ $screenshot['url'] }}" 
                                       download="page_{{ $page->page_number }}.jpg"
                                       class="btn btn-primary">
                                        <i class="bi bi-download me-1"></i>
                                        Скачать
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-image display-1 text-muted mb-3"></i>
                        <p class="text-muted mb-0">Для этой страницы нет изображений</p>
                    </div>
                @endif
                
                <!-- Статистика страницы -->
                <div class="mt-4 p-4 bg-light rounded">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        Информация о странице
                    </h6>
                    <div class="small">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Номер страницы:</span>
                            <strong>{{ $page->page_number }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Слов:</span>
                            <strong>{{ $page->word_count ?? 0 }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Символов:</span>
                            <strong>{{ $page->character_count ?? 0 }}</strong>
                        </div>
                        @if(!empty($errorCodes))
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Коды ошибок:</span>
                            <strong>{{ count($errorCodes) }}</strong>
                        </div>
                        @endif
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Изображений:</span>
                            <strong>{{ count($screenshots) + count($images ?? []) }}</strong>
                        </div>
                    </div>
                </div>
                
                <!-- Навигация по страницам (мини) -->
                @if($document->total_pages && $document->total_pages > 1)
                <div class="mt-4">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-diagram-3 me-2"></i>
                        Навигация по документу
                    </h6>
                    <div class="d-flex flex-wrap gap-1">
                        @for($i = 1; $i <= min($document->total_pages, 10); $i++)
                            @if($i == $page->page_number)
                                <span class="btn btn-sm btn-primary disabled">{{ $i }}</span>
                            @else
                                <a href="{{ route('documents.pages.show', ['id' => $document->id, 'pageNumber' => $i, 'highlight' => $highlightTerm]) }}" 
                                   class="btn btn-sm btn-outline-secondary">
                                    {{ $i }}
                                </a>
                            @endif
                        @endfor
                        @if($document->total_pages > 10)
                            <span class="btn btn-sm btn-outline-secondary disabled">...</span>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Инициализация всех модальных окон
            var modals = document.querySelectorAll('.modal');
            modals.forEach(function(modal) {
                new bootstrap.Modal(modal);
            });
            
            // Плавный скролл к якорям
            document.querySelectorAll('a[href^="#paragraph-"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        target.classList.add('bg-warning', 'bg-opacity-25');
                        setTimeout(() => {
                            target.classList.remove('bg-warning', 'bg-opacity-25');
                        }, 1500);
                    }
                });
            });
        });
        
        // Горячие клавиши
        document.addEventListener('keydown', function(e) {
            @if($prevPage)
            if (e.key === 'ArrowLeft' && !e.ctrlKey) {
                window.location.href = "{{ route('documents.pages.show', ['id' => $document->id, 'pageNumber' => $prevPage->page_number, 'highlight' => $highlightTerm]) }}";
            }
            @endif
            
            @if($nextPage)
            if (e.key === 'ArrowRight' && !e.ctrlKey) {
                window.location.href = "{{ route('documents.pages.show', ['id' => $document->id, 'pageNumber' => $nextPage->page_number, 'highlight' => $highlightTerm]) }}";
            }
            @endif
            
            // Home/End для навигации
            if (e.key === 'Home') {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
            if (e.key === 'End') {
                window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
            }
            
            // Пробел для прокрутки
            if (e.key === ' ' && !e.target.matches('input, textarea, button')) {
                e.preventDefault();
                window.scrollBy({ top: window.innerHeight * 0.8, behavior: 'smooth' });
            }
        });
        
        // Функция для открытия изображений
        function openImageModal(src, title) {
            const modalHtml = `
                <div class="modal fade" tabindex="-1">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-0 bg-dark text-white">
                                <h5 class="modal-title">${title || 'Просмотр изображения'}</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center p-0 bg-dark">
                                <img src="${src}" class="img-fluid" style="max-height: 80vh;">
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            const modalContainer = document.createElement('div');
            modalContainer.innerHTML = modalHtml;
            document.body.appendChild(modalContainer);
            
            const modal = new bootstrap.Modal(modalContainer.firstChild);
            modal.show();
            
            modalContainer.firstChild.addEventListener('hidden.bs.modal', function() {
                modalContainer.remove();
            });
        }
    </script>
</body>
</html>