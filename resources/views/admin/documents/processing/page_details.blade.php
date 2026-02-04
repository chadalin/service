<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Мета-теги -->
    <title>{{ $metaInfo['title'] ?: $document->title }} - Страница {{ $page->page_number }}</title>
    
    @if(!empty($metaInfo['description']))
    <meta name="description" content="{{ Str::limit($metaInfo['description'], 160) }}">
    @endif
    
    @if(!empty($metaInfo['keywords']))
    <meta name="keywords" content="{{ implode(', ', $metaInfo['keywords']) }}">
    @endif
    
    <!-- Open Graph -->
    <meta property="og:title" content="{{ $metaInfo['title'] ?: $document->title }}">
    @if(!empty($metaInfo['description']))
    <meta property="og:description" content="{{ Str::limit($metaInfo['description'], 160) }}">
    @endif
    @if(count($screenshots) > 0)
    <meta property="og:image" content="{{ url($screenshots[0]['url']) }}">
    @endif
    <meta property="og:type" content="article">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #4a6fa5;
            --secondary-color: #6c757d;
            --accent-color: #ff6b6b;
            --light-bg: #f8f9fa;
            --border-color: #dee2e6;
            --shadow: 0 4px 12px rgba(0,0,0,0.1);
            --border-radius: 12px;
        }
        
        body {
            background-color: #f8f9fa;
            color: #333;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            padding-bottom: 50px;
        }
        
        .container-xl {
            max-width: 1400px;
        }
        
        /* Навигация */
        .navigation-bar {
            background: white;
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .nav-brand {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.5rem;
        }
        
        /* Шапка документа */
        .document-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2.5rem 0;
            margin-bottom: 2.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }
        
        .document-title {
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }
        
        .document-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            margin-top: 1rem;
        }
        
        .meta-badge {
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Основной контент */
        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        @media (min-width: 992px) {
            .content-wrapper {
                grid-template-columns: 1fr 450px;
            }
        }
        
        @media (min-width: 1200px) {
            .content-wrapper {
                grid-template-columns: 1fr 500px;
            }
        }
        
        /* Блок текста */
        .text-content-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            order: 2;
        }
        
        @media (min-width: 992px) {
            .text-content-section {
                order: 1;
            }
        }
        
        .section-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 3px solid var(--accent-color);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .paragraph-container {
            margin-bottom: 2.5rem;
        }
        
        .paragraph-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.75rem;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .paragraph-text {
            line-height: 1.7;
            font-size: 1.05rem;
            color: #444;
            padding: 1.25rem;
            background: var(--light-bg);
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
            white-space: pre-line;
        }
        
        /* Блок скриншотов */
        .screenshots-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            order: 1;
            position: sticky;
            top: 100px;
            height: fit-content;
        }
        
        @media (min-width: 992px) {
            .screenshots-section {
                order: 2;
            }
        }
        
        .screenshot-container {
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .screenshot-container:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        
        .screenshot-wrapper {
            position: relative;
            overflow: hidden;
            background: #f5f5f5;
        }
        
        .screenshot-main {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.5s ease;
            cursor: zoom-in;
        }
        
        .screenshot-main:hover {
            transform: scale(1.02);
        }
        
        .screenshot-meta {
            padding: 1rem;
            background: white;
            border-top: 1px solid var(--border-color);
        }
        
        .screenshot-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }
        
        .screenshot-description {
            color: var(--secondary-color);
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
        }
        
        .screenshot-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: #666;
        }
        
        .zoom-hint {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }
        
        .screenshot-container:hover .zoom-hint {
            opacity: 1;
        }
        
        /* Дополнительные изображения */
        .additional-images {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px dashed var(--border-color);
        }
        
        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 0.75rem;
            margin-top: 1rem;
        }
        
        .thumbnail {
            width: 100%;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .thumbnail:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        /* Инструкции по ремонту */
        .instructions-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            margin-top: 2rem;
            grid-column: 1 / -1;
        }
        
        .instruction-item {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            padding: 1.25rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid var(--accent-color);
            transition: transform 0.2s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .instruction-item:hover {
            transform: translateX(5px);
        }
        
        .instruction-icon {
            color: var(--accent-color);
            font-size: 1.5rem;
            min-width: 40px;
        }
        
        /* Таблицы и диаграммы */
        .data-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            margin-top: 2rem;
            grid-column: 1 / -1;
        }
        
        .data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .data-card {
            padding: 1.25rem;
            border-radius: 8px;
            background: #f8f9fa;
            border-left: 4px solid var(--primary-color);
        }
        
        .data-card.warning {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        
        /* Статистика */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.25rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            transition: transform 0.2s ease;
            border: 1px solid var(--border-color);
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }
        
        /* Адаптивность */
        @media (max-width: 768px) {
            .document-header {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .document-title {
                font-size: 1.4rem;
            }
            
            .text-content-section,
            .screenshots-section,
            .instructions-section,
            .data-section {
                padding: 1.25rem;
            }
            
            .section-title {
                font-size: 1.2rem;
            }
            
            .paragraph-text {
                font-size: 1rem;
                padding: 1rem;
            }
            
            .screenshots-section {
                position: static;
            }
        }
        
        /* Кнопки */
        .btn-custom {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-custom-primary {
            background: var(--primary-color);
            color: white;
            border: none;
        }
        
        .btn-custom-primary:hover {
            background: #3a5a8c;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(58, 90, 140, 0.3);
        }
        
        /* Модальное окно для изображения */
        .modal-xl .modal-content {
            border-radius: 12px;
            overflow: hidden;
        }
        
        .modal-image {
            max-height: 80vh;
            object-fit: contain;
            width: 100%;
        }
        
        .modal-body {
            padding: 0;
            background: #f8f9fa;
        }
        
        /* Загрузка изображений */
        .image-loading {
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 8px;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* Кастомный скроллбар */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #3a5a8c;
        }
        
        /* Анимации */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .slide-in-left {
            animation: slideInLeft 0.6s ease-out;
        }
        
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .slide-in-right {
            animation: slideInRight 0.6s ease-out;
        }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        /* Индикатор важности */
        .importance-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: var(--accent-color);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            z-index: 10;
        }
    </style>
</head>
<body>
    <!-- Навигационная панель -->
    <div class="navigation-bar">
        <div class="container-xl">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a href="{{ route('admin.documents.processing.advanced', $document->id) }}" 
                       class="text-decoration-none nav-brand">
                        <i class="bi bi-arrow-left me-2"></i>
                        <span>Назад к документу</span>
                    </a>
                </div>
                
                <div class="d-flex gap-2">
                    <span class="badge bg-info">
                        <i class="bi bi-file-pdf me-1"></i>
                        PDF Парсер
                    </span>
                    <span class="badge bg-success">
                        Страница {{ $page->page_number }}
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container-xl">
        <!-- Шапка документа -->
        <div class="document-header fade-in">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="document-title">{{ $document->title }}</h1>
                    @if($metaInfo['title'])
                    <h2 class="h4 text-light opacity-90 mb-3">
                        <i class="bi bi-card-heading me-2"></i>
                        {{ $metaInfo['title'] }}
                    </h2>
                    @endif
                    
                    <div class="document-meta">
                        <span class="meta-badge">
                            <i class="bi bi-file-earmark-text"></i>
                            Страница {{ $page->page_number }}
                        </span>
                        
                        <span class="meta-badge">
                            <i class="bi bi-text-left"></i>
                            {{ $page->word_count ?? 0 }} слов
                        </span>
                        
                        <span class="meta-badge">
                            <i class="bi bi-camera"></i>
                            {{ count($screenshots) + count($additionalImages) }} изображений
                        </span>
                        
                        @if($page->section_title)
                        <span class="meta-badge">
                            <i class="bi bi-bookmark"></i>
                            {{ $page->section_title }}
                        </span>
                        @endif
                    </div>
                </div>
                
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-light" onclick="printPage()">
                            <i class="bi bi-printer"></i>
                        </button>
                        <button type="button" class="btn btn-light" onclick="toggleViewMode()" id="viewModeBtn">
                            <i class="bi bi-layout-sidebar"></i>
                        </button>
                        <button type="button" class="btn btn-light" onclick="downloadAllImages()">
                            <i class="bi bi-download"></i>
                        </button>
                        <a href="{{ Storage::url($document->file_path) }}" 
                           target="_blank" 
                           class="btn btn-primary">
                            <i class="bi bi-file-earmark-pdf"></i> PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Основной контент -->
        <div class="content-wrapper">
            <!-- Левый блок: Скриншоты -->
            <div class="screenshots-section slide-in-right">
                <h2 class="section-title">
                    <i class="bi bi-camera-fill"></i>
                    Скриншоты страницы
                </h2>
                
                @if(count($screenshots) > 0)
                    @foreach($screenshots as $index => $screenshot)
                    <div class="screenshot-container fade-in" style="animation-delay: {{ $index * 0.1 }}s">
                        @if($screenshot['is_main'])
                        <div class="importance-badge">
                            <i class="bi bi-star-fill me-1"></i>Главный вид
                        </div>
                        @endif
                        
                        <div class="screenshot-wrapper">
                            <img src="{{ $screenshot['url'] }}" 
                                 alt="{{ $screenshot['alt'] }}"
                                 class="screenshot-main"
                                 data-bs-toggle="modal" 
                                 data-bs-target="#screenshotModal{{ $index }}"
                                 onload="this.parentElement.classList.remove('image-loading')"
                                 onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22300%22 height=%22200%22><rect width=%22100%25%22 height=%22100%25%22 fill=%22%23f5f5f5%22/><text x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%23666%22>Изображение не загружено</text></svg>'">
                            
                            <div class="zoom-hint">
                                <i class="bi bi-zoom-in me-1"></i>Кликните для увеличения
                            </div>
                        </div>
                        
                        <div class="screenshot-meta">
                            <div class="screenshot-title">
                                <i class="bi bi-image me-2"></i>
                                {{ $screenshot['description'] }}
                            </div>
                            
                            @if($screenshot['description'] != $screenshot['alt'] && $screenshot['alt'])
                            <div class="screenshot-description">
                                {{ $screenshot['alt'] }}
                            </div>
                            @endif
                            
                            <div class="screenshot-info">
                                <div>
                                    @if($screenshot['width'] && $screenshot['height'])
                                    <span class="me-3">
                                        <i class="bi bi-aspect-ratio"></i>
                                        {{ $screenshot['width'] }}×{{ $screenshot['height'] }}px
                                    </span>
                                    @endif
                                    
                                    @if($screenshot['file_size'])
                                    <span>
                                        <i class="bi bi-hdd"></i>
                                        {{ round($screenshot['file_size'] / 1024, 1) }} KB
                                    </span>
                                    @endif
                                </div>
                                
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" 
                                            onclick="downloadScreenshot('{{ $screenshot['url'] }}', 'страница_{{ $page->page_number }}_{{ $index + 1 }}')">
                                        <i class="bi bi-download"></i>
                                    </button>
                                    <button class="btn btn-outline-success" 
                                            onclick="copyScreenshotUrl('{{ $screenshot['url'] }}')">
                                        <i class="bi bi-link-45deg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Модальное окно для увеличенного просмотра -->
                    <div class="modal fade" id="screenshotModal{{ $index }}" tabindex="-1">
                        <div class="modal-dialog modal-xl modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        <i class="bi bi-camera me-2"></i>
                                        {{ $screenshot['description'] }}
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center p-0">
                                    <img src="{{ $screenshot['url'] }}" 
                                         alt="{{ $screenshot['alt'] }}"
                                         class="modal-image">
                                </div>
                                <div class="modal-footer justify-content-between">
                                    <div>
                                        @if($screenshot['width'] && $screenshot['height'])
                                        <span class="text-muted me-3">
                                            {{ $screenshot['width'] }}×{{ $screenshot['height'] }}px
                                        </span>
                                        @endif
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                            <i class="bi bi-x-lg me-1"></i>Закрыть
                                        </button>
                                        <a href="{{ $screenshot['url'] }}" 
                                           download="страница_{{ $page->page_number }}_{{ $index + 1 }}_full.jpg"
                                           class="btn btn-primary">
                                            <i class="bi bi-download me-1"></i>Скачать
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    
                    <!-- Дополнительные изображения -->
                    @if(count($additionalImages) > 0)
                    <div class="additional-images">
                        <h5 class="mb-3">
                            <i class="bi bi-images text-primary me-2"></i>
                            Дополнительные изображения
                        </h5>
                        <div class="images-grid">
                            @foreach($additionalImages as $image)
                            <div class="thumbnail-wrapper" 
                                 data-bs-toggle="tooltip" 
                                 title="{{ $image->description ?? 'Изображение' }}"
                                 onclick="openImageModal('{{ $image->url }}', '{{ $image->description ?? 'Изображение' }}')">
                                <img src="{{ $image->url }}" 
                                     alt="{{ $image->description ?? 'Дополнительное изображение' }}"
                                     class="thumbnail">
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    
                @else
                    <div class="alert alert-warning">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-camera-video-off display-6 text-warning me-3"></i>
                            <div>
                                <h5 class="alert-heading">Скриншоты не найдены</h5>
                                <p class="mb-0">Для этой страницы не были созданы скриншоты. Выполните обработку документа для создания скриншотов страниц.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center py-4">
                        <div class="image-loading rounded mb-3" style="height: 200px;"></div>
                        <p class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Скриншоты будут отображаться здесь после обработки документа
                        </p>
                    </div>
                @endif
                
                <!-- Информация о скриншотах -->
                <div class="mt-4 p-3 bg-light rounded">
                    <h6 class="fw-bold mb-2">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        Информация о скриншотах:
                    </h6>
                    <div class="row small">
                        <div class="col-6">
                            <span class="text-muted">Найдено:</span><br>
                            <strong>{{ count($screenshots) }} скриншотов</strong>
                        </div>
                        <div class="col-6 text-end">
                            <span class="text-muted">Дополнительно:</span><br>
                            <strong>{{ count($additionalImages) }} изображений</strong>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Правый блок: Текст -->
            <div class="text-content-section slide-in-left">
                <h2 class="section-title">
                    <i class="bi bi-text-paragraph"></i>
                    Текст документа
                </h2>
                
                @if(count($paragraphs) > 0)
                    <!-- Навигация по абзацам -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Навигация по абзацам:</h6>
                            <span class="badge bg-primary">{{ count($paragraphs) }} абзацев</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            @for($i = 0; $i < count($paragraphs); $i++)
                            <a href="#paragraph-{{ $i + 1 }}" class="btn btn-sm btn-outline-primary">
                                Абзац {{ $i + 1 }}
                            </a>
                            @endfor
                        </div>
                    </div>
                    
                    <!-- Абзацы -->
                    @foreach($paragraphs as $index => $paragraph)
                    <div class="paragraph-container" id="paragraph-{{ $index + 1 }}">
                        <h3 class="paragraph-title">
                            <span class="badge bg-primary rounded-circle p-2">{{ $index + 1 }}</span>
                            <span>Абзац {{ $index + 1 }}</span>
                            <small class="ms-2 text-muted">
                                ({{ count(explode("\n", $paragraph)) }} строк)
                            </small>
                        </h3>
                        <div class="paragraph-text">
                            {!! nl2br(e($paragraph)) !!}
                        </div>
                        
                        <div class="mt-2 d-flex justify-content-end gap-2">
                            <button class="btn btn-sm btn-outline-secondary" 
                                    onclick="copyParagraph({{ $index }})"
                                    data-bs-toggle="tooltip" title="Копировать абзац">
                                <i class="bi bi-clipboard"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-info" 
                                    onclick="highlightParagraph({{ $index }})"
                                    data-bs-toggle="tooltip" title="Выделить">
                                <i class="bi bi-highlighter"></i>
                            </button>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="alert alert-info">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-file-text display-6 text-info me-3"></i>
                            <div>
                                <h5 class="alert-heading">Текст не найден</h5>
                                <p class="mb-0">На этой странице не обнаружен текст или он не был распознан при парсинге.</p>
                            </div>
                        </div>
                    </div>
                @endif
                
                <!-- Статистика текста -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value">{{ $page->word_count ?? 0 }}</div>
                        <div class="stat-label">Слов</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $page->character_count ?? 0 }}</div>
                        <div class="stat-label">Символов</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ count($paragraphs) }}</div>
                        <div class="stat-label">Абзацев</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ round($page->parsing_quality * 100, 1) }}%</div>
                        <div class="stat-label">Качество парсинга</div>
                    </div>
                </div>
            </div>
            
            <!-- Инструкции по ремонту (если есть) -->
            @if(count($metaInfo['instructions']) > 0)
            <div class="instructions-section fade-in">
                <h2 class="section-title">
                    <i class="bi bi-wrench"></i>
                    Инструкции по ремонту
                    <span class="badge bg-success ms-2">{{ count($metaInfo['instructions']) }}</span>
                </h2>
                
                @foreach($metaInfo['instructions'] as $instruction)
                <div class="instruction-item">
                    <div class="instruction-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="flex-grow-1">
                        {{ $instruction }}
                    </div>
                </div>
                @endforeach
                
                <div class="mt-3 text-muted small">
                    <i class="bi bi-lightbulb me-1"></i>
                    Эти инструкции автоматически извлечены из текста документа.
                </div>
            </div>
            @endif
            
            <!-- Таблицы и диаграммы -->
       
            
            <!-- Ключевые слова -->
            @if(count($metaInfo['keywords']) > 0)
            <div class="data-section fade-in">
                <h2 class="section-title">
                    <i class="bi bi-tags"></i>
                    Ключевые слова
                    <span class="badge bg-info ms-2">{{ count($metaInfo['keywords']) }}</span>
                </h2>
                
                <div class="d-flex flex-wrap gap-2">
                    @foreach($metaInfo['keywords'] as $keyword)
                    <span class="badge bg-light text-dark border p-2 d-flex align-items-center">
                        <i class="bi bi-hash me-1"></i>
                        {{ $keyword }}
                    </span>
                    @endforeach
                </div>
                
                <div class="mt-3 text-muted small">
                    <i class="bi bi-info-circle me-1"></i>
                    Эти ключевые слова автоматически извлечены из текста.
                </div>
            </div>
            @endif
        </div>
        
        <!-- Пагинация страниц -->
        <div class="card border-0 shadow-sm mt-4 fade-in">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        @php
                            $prevPage = \App\Models\DocumentPage::where('document_id', $document->id)
                                ->where('page_number', '<', $page->page_number)
                                ->orderBy('page_number', 'desc')
                                ->first();
                            
                            $nextPage = \App\Models\DocumentPage::where('document_id', $document->id)
                                ->where('page_number', '>', $page->page_number)
                                ->orderBy('page_number', 'asc')
                                ->first();
                        @endphp
                        
                        @if($prevPage)
                        <a href="{{ route('admin.documents.processing.page.detal', ['id' => $document->id, 'pageId' => $prevPage->id]) }}"
                           class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left me-1"></i> Предыдущая страница
                        </a>
                        @endif
                    </div>
                    
                    <div class="text-center">
                        <span class="badge bg-primary p-2 fs-6">
                            <i class="bi bi-file-earmark me-1"></i>
                            Страница {{ $page->page_number }} 
                            @if($document->total_pages)
                                из {{ $document->total_pages }}
                            @endif
                        </span>
                    </div>
                    
                    <div>
                        @if($nextPage)
                        <a href="{{ route('admin.documents.processing.page.detal', ['id' => $document->id, 'pageId' => $nextPage->id]) }}"
                           class="btn btn-outline-primary">
                            Следующая страница <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно для дополнительных изображений -->
    <div class="modal fade" id="additionalImageModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="additionalImageModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <img src="" alt="" class="img-fluid" id="additionalImageModalImg">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Закрыть
                    </button>
                    <a href="#" class="btn btn-primary" id="additionalImageModalDownload">
                        <i class="bi bi-download me-1"></i>Скачать
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Инициализация тултипов
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        
        // Ленивая загрузка изображений
        const images = document.querySelectorAll('img[data-src]');
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.getAttribute('data-src');
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
        
        // Выделение текста при клике на абзац
        document.querySelectorAll('.paragraph-text').forEach((paragraph, index) => {
            paragraph.addEventListener('click', function(e) {
                if (window.getSelection().toString() === '') {
                    const range = document.createRange();
                    range.selectNodeContents(this);
                    const selection = window.getSelection();
                    selection.removeAllRanges();
                    selection.addRange(range);
                    
                    // Показываем уведомление
                    showNotification('Абзац ' + (index + 1) + ' выделен', 'info');
                }
            });
        });
        
        // Плавная прокрутка к абзацам
        document.querySelectorAll('a[href^="#paragraph-"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({ 
                        behavior: 'smooth',
                        block: 'start'
                    });
                    
                    // Временное выделение
                    targetElement.classList.add('bg-warning', 'bg-opacity-25');
                    setTimeout(() => {
                        targetElement.classList.remove('bg-warning', 'bg-opacity-25');
                    }, 1500);
                }
            });
        });
        
        // Сохранение настроек просмотра
        if (localStorage.getItem('viewMode') === 'compact') {
            toggleViewMode();
        }
    });
    
    // Функции
    function toggleViewMode() {
        const textSection = document.querySelector('.text-content-section');
        const screenshotsSection = document.querySelector('.screenshots-section');
        const viewModeBtn = document.getElementById('viewModeBtn');
        
        if (textSection.classList.contains('col-12')) {
            // Переключаем на обычный вид
            textSection.classList.remove('col-12', 'order-first');
            screenshotsSection.classList.remove('col-12', 'order-last');
            viewModeBtn.innerHTML = '<i class="bi bi-layout-sidebar"></i>';
            viewModeBtn.title = "Компактный вид";
            localStorage.removeItem('viewMode');
        } else {
            // Переключаем на компактный вид
            textSection.classList.add('col-12', 'order-first');
            screenshotsSection.classList.add('col-12', 'order-last');
            viewModeBtn.innerHTML = '<i class="bi bi-layout-split"></i>';
            viewModeBtn.title = "Обычный вид";
            localStorage.setItem('viewMode', 'compact');
        }
    }
    
    function copyParagraph(index) {
        const paragraph = document.querySelectorAll('.paragraph-text')[index];
        const text = paragraph.textContent;
        
        navigator.clipboard.writeText(text)
            .then(() => {
                showNotification('Абзац ' + (index + 1) + ' скопирован!', 'success');
            })
            .catch(err => {
                console.error('Ошибка копирования: ', err);
                showNotification('Ошибка при копировании', 'danger');
            });
    }
    
    function highlightParagraph(index) {
        const paragraph = document.querySelectorAll('.paragraph-text')[index];
        paragraph.classList.toggle('bg-warning');
        paragraph.classList.toggle('bg-opacity-25');
    }
    
    function downloadScreenshot(url, filename) {
        const link = document.createElement('a');
        link.href = url;
        link.download = filename + '.jpg';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        showNotification('Скриншот скачивается...', 'info');
    }
    
    function downloadAllImages() {
        showNotification('Подготовка к скачиванию всех изображений...', 'info');
        // Реализуйте скачивание всех изображений
    }
    
    function copyScreenshotUrl(url) {
        navigator.clipboard.writeText(url)
            .then(() => {
                showNotification('Ссылка на скриншот скопирована!', 'success');
            })
            .catch(err => {
                console.error('Ошибка копирования: ', err);
                showNotification('Ошибка при копировании ссылки', 'danger');
            });
    }
    
    function openImageModal(url, title) {
        const modal = new bootstrap.Modal(document.getElementById('additionalImageModal'));
        document.getElementById('additionalImageModalImg').src = url;
        document.getElementById('additionalImageModalTitle').textContent = title;
        document.getElementById('additionalImageModalDownload').href = url;
        document.getElementById('additionalImageModalDownload').download = title + '.jpg';
        modal.show();
    }
    
    function printPage() {
        const originalContent = document.body.innerHTML;
        const printContent = document.querySelector('.content-wrapper').innerHTML;
        
        document.body.innerHTML = `
            <div class="container p-4">
                <h1 class="mb-3">{{ $document->title }}</h1>
                <h2 class="h4 mb-4">Страница {{ $page->page_number }}</h2>
                ${printContent}
                <div class="mt-4 text-muted small">
                    Распечатано: ${new Date().toLocaleString()}<br>
                    Источник: PDF Парсер
                </div>
            </div>
        `;
        
        window.print();
        document.body.innerHTML = originalContent;
        location.reload();
    }
    
    function showNotification(message, type = 'info') {
        // Создаем уведомление
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Автоматическое скрытие через 3 секунды
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }
    
    // Горячие клавиши
    document.addEventListener('keydown', function(e) {
        // Ctrl + P для печати
        if (e.ctrlKey && e.key === 'p') {
            e.preventDefault();
            printPage();
        }
        
        // Ctrl + C для копирования
        if (e.ctrlKey && e.key === 'c') {
            const selection = window.getSelection().toString();
            if (selection.length > 100) {
                showNotification('Текст скопирован: ' + selection.substring(0, 100) + '...', 'info');
            }
        }
        
        // Стрелки для навигации
        @if($prevPage)
        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            window.location.href = "{{ route('admin.documents.processing.page.show', ['id' => $document->id, 'pageId' => $prevPage->id]) }}";
        }
        @endif
        
        @if($nextPage)
        if (e.key === 'ArrowRight') {
            e.preventDefault();
            window.location.href = "{{ route('admin.documents.processing.page.show', ['id' => $document->id, 'pageId' => $nextPage->id]) }}";
        }
        @endif
        
        // V для переключения вида
        if (e.key === 'v' && !e.ctrlKey) {
            e.preventDefault();
            toggleViewMode();
        }
    });
    
    // Статистика времени на странице
    let startTime = Date.now();
    
    window.addEventListener('beforeunload', function() {
        const timeSpent = Math.round((Date.now() - startTime) / 1000);
        
        // Можно отправить на сервер
        if (timeSpent > 10) {
            fetch('/api/page-stats', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    page_id: {{ $page->id }},
                    time_spent: timeSpent,
                    images_viewed: document.querySelectorAll('.screenshot-main').length,
                    paragraphs_copied: 0 // Можно отслеживать
                })
            }).catch(console.error);
        }
    });
    
    // Предзагрузка следующей страницы
    @if($nextPage)
    setTimeout(() => {
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = "{{ route('admin.documents.processing.page.show', ['id' => $document->id, 'pageId' => $nextPage->id]) }}";
        document.head.appendChild(link);
    }, 2000);
    @endif
    </script>
</body>
</html>