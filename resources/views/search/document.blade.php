{{-- resources/views/search/document.blade.php --}}
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $document->title }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .document-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }
        .page-nav {
            position: sticky;
            top: 20px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .page-item {
            padding: 8px 12px;
            border-left: 3px solid transparent;
            cursor: pointer;
            margin-bottom: 5px;
            border-radius: 4px;
        }
        .page-item:hover {
            background: #f8f9fa;
        }
        .page-item.active {
            border-left-color: #007bff;
            background: #e7f1ff;
        }
        .content-section {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            min-height: 500px;
        }
        .metadata-badge {
            background: #e9ecef;
            padding: 5px 10px;
            border-radius: 15px;
            margin-right: 10px;
            margin-bottom: 10px;
            display: inline-block;
        }
        .keyword-badge {
            background: #d1ecf1;
            color: #0c5460;
            padding: 5px 10px;
            border-radius: 15px;
            margin-right: 5px;
            margin-bottom: 5px;
            display: inline-block;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <!-- Шапка документа -->
    <div class="document-header">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('search.test') }}" class="text-white">Поиск</a>
                    </li>
                    <li class="breadcrumb-item active text-white" aria-current="page">
                        Документ
                    </li>
                </ol>
            </nav>
            
            <h1 class="display-5">{{ $document->title }}</h1>
            
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="d-flex flex-wrap">
                        @if($document->file_type)
                            <span class="metadata-badge">📄 {{ strtoupper($document->file_type) }}</span>
                        @endif
                        @if($document->total_pages)
                            <span class="metadata-badge">📖 {{ $document->total_pages }} страниц</span>
                        @endif
                        <span class="metadata-badge">👁 {{ $document->view_count }} просмотров</span>
                        <span class="metadata-badge">🔍 {{ $document->search_count }} поисков</span>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="{{ route('search.test') }}?q={{ urlencode($document->title) }}" 
                       class="btn btn-light me-2">
                        🔍 Похожие документы
                    </a>
                    <button onclick="window.print()" class="btn btn-light">
                        🖨 Печать
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="row">
            <!-- Навигация по страницам -->
            <div class="col-md-3">
                <div class="page-nav">
                    <h5>Содержание</h5>
                    <div id="pageList">
                        @foreach($document->pages as $page)
                            <div class="page-item" data-page="{{ $page->page_number }}">
                                <strong>Страница {{ $page->page_number }}</strong>
                                @if($page->section_title)
                                    <div class="text-muted small">{{ $page->section_title }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- Ключевые слова -->
                @if($document->keywords && count($document->keywords) > 0)
                    <div class="mt-4">
                        <h6>Ключевые слова:</h6>
                        <div class="d-flex flex-wrap mt-2">
                            @foreach($document->keywords as $keyword)
                                <a href="{{ route('search.test') }}?q={{ urlencode($keyword) }}" 
                                   class="keyword-badge">
                                    {{ $keyword }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
            
            <!-- Содержание документа -->
            <div class="col-md-9">
                <div class="content-section">
                    <div id="documentContent">
                        <!-- Первая страница по умолчанию -->
                        @if($document->pages->count() > 0)
                            @php $firstPage = $document->pages->first(); @endphp
                            <h3>Страница {{ $firstPage->page_number }}</h3>
                            @if($firstPage->section_title)
                                <h5 class="text-muted mb-4">{{ $firstPage->section_title }}</h5>
                            @endif
                            <div class="content-text">
                                {!! nl2br(e($firstPage->content_text)) !!}
                            </div>
                        @else
                            <div class="alert alert-info">
                                Содержание документа пока не разбито на страницы.
                            </div>
                            @if($document->content_text)
                                <div class="content-text">
                                    {!! nl2br(e($document->content_text)) !!}
                                </div>
                            @endif
                        @endif
                    </div>
                    
                    <!-- Навигация между страницами -->
                    @if($document->pages->count() > 1)
                        <div class="mt-5 pt-4 border-top">
                            <div class="d-flex justify-content-between">
                                <button id="prevPage" class="btn btn-outline-primary">
                                    ← Предыдущая страница
                                </button>
                                <span id="pageCounter" class="align-self-center">
                                    Страница <span id="currentPage">1</span> из {{ $document->pages->count() }}
                                </span>
                                <button id="nextPage" class="btn btn-outline-primary">
                                    Следующая страница →
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let currentPage = 1;
        const totalPages = {{ $document->pages->count() }};
        const pages = {!! json_encode($document->keyBy('page_number')->toArray()) !!};
        
        $(document).ready(function() {
            // Клик по навигации страниц
            $('.page-item').on('click', function() {
                const pageNum = $(this).data('page');
                loadPage(pageNum);
            });
            
            // Кнопки навигации
            $('#prevPage').on('click', function() {
                if (currentPage > 1) {
                    loadPage(currentPage - 1);
                }
            });
            
            $('#nextPage').on('click', function() {
                if (currentPage < totalPages) {
                    loadPage(currentPage + 1);
                }
            });
            
            // Загрузка страницы
            function loadPage(pageNum) {
                if (!pages[pageNum]) return;
                
                const page = pages[pageNum];
                
                // Обновляем контент
                $('#documentContent').html(`
                    <h3>Страница ${page.page_number}</h3>
                    ${page.section_title ? `<h5 class="text-muted mb-4">${page.section_title}</h5>` : ''}
                    <div class="content-text">
                        ${page.content_text.replace(/\n/g, '<br>')}
                    </div>
                `);
                
                // Обновляем счетчик
                $('#currentPage').text(pageNum);
                currentPage = pageNum;
                
                // Обновляем активную страницу в навигации
                $('.page-item').removeClass('active');
                $(`.page-item[data-page="${pageNum}"]`).addClass('active');
                
                // Прокручиваем к активной странице в навигации
                const activeItem = $(`.page-item[data-page="${pageNum}"]`);
                $('.page-nav').animate({
                    scrollTop: activeItem.offset().top - $('.page-nav').offset().top + $('.page-nav').scrollTop()
                }, 300);
                
                // Обновляем URL без перезагрузки
                history.pushState(null, '', `#page-${pageNum}`);
            }
            
            // Обработка хэша в URL
            const hash = window.location.hash;
            if (hash && hash.startsWith('#page-')) {
                const pageNum = parseInt(hash.replace('#page-', ''));
                if (pageNum >= 1 && pageNum <= totalPages) {
                    loadPage(pageNum);
                }
            }
            
            // Первая страница активна по умолчанию
            $('.page-item:first').addClass('active');
            
            // Сочетания клавиш
            $(document).on('keydown', function(e) {
                if (e.key === 'ArrowLeft' && currentPage > 1) {
                    loadPage(currentPage - 1);
                } else if (e.key === 'ArrowRight' && currentPage < totalPages) {
                    loadPage(currentPage + 1);
                }
            });
        });
    </script>
</body>
</html>