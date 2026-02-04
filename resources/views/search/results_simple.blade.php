{{-- resources/views/search/results.blade.php --}}
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Результаты поиска: {{ $query }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .search-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        .result-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            background: white;
        }
        .result-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .relevance-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
        }
        .snippet {
            color: #666;
            line-height: 1.6;
        }
        .highlight {
            background-color: #fff9c4;
            font-weight: bold;
            padding: 0 2px;
        }
        .metadata {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .metadata span {
            margin-right: 15px;
        }
        .pagination {
            margin-top: 30px;
        }
        .pagination .page-link {
            color: #667eea;
        }
        .pagination .page-item.active .page-link {
            background-color: #667eea;
            border-color: #667eea;
        }
        .back-link {
            color: white;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .keywords .badge {
            margin-right: 5px;
            margin-bottom: 5px;
        }
        .no-results {
            min-height: 50vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
    </style>
</head>
<body>
    <!-- Шапка с поиском -->
    <div class="search-header">
        <div class="container">
            <a href="{{ route('search.test') }}" class="back-link mb-3 d-inline-block">
                ← Назад к поиску
            </a>
            <h1>Результаты поиска</h1>
            <p class="lead">По запросу: "<strong>{{ $query }}</strong>"</p>
            
            <!-- Форма поиска в шапке -->
            <form action="{{ route('search.results') }}" method="GET" class="mt-4">
                <div class="row g-2">
                    <div class="col-md-8">
                        <input type="text" 
                               name="q" 
                               class="form-control form-control-lg" 
                               placeholder="Новый поиск..."
                               value="{{ $query }}">
                    </div>
                    <div class="col-md-2">
                        <select name="file_type" class="form-control form-control-lg">
                            <option value="">Все типы</option>
                            <option value="pdf" {{ request('file_type') == 'pdf' ? 'selected' : '' }}>PDF</option>
                            <option value="doc" {{ request('file_type') == 'doc' ? 'selected' : '' }}>DOC</option>
                            <option value="docx" {{ request('file_type') == 'docx' ? 'selected' : '' }}>DOCX</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-light btn-lg w-100">
                            🔍 Найти
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="container">
        @if($documents && $documents->count() > 0)
            <!-- Статистика -->
            <div class="alert alert-info">
                Найдено документов: <strong>{{ $documents->total() }}</strong>
                @if(request('file_type'))
                    <span class="ms-2">
                        Тип: <span class="badge bg-secondary">{{ strtoupper(request('file_type')) }}</span>
                    </span>
                @endif
            </div>
            
            <!-- Результаты -->
            @foreach($documents as $document)
            <div class="result-card position-relative">
                @if(($document->average_relevance ?? 0) > 70)
                    <span class="relevance-badge">Высокая релевантность</span>
                @elseif(($document->average_relevance ?? 0) > 40)
                    <span class="relevance-badge" style="background: #ffc107;">Средняя релевантность</span>
                @endif
                
                <h4>
                    <a href="{{ route('search.document', $document->id) }}" class="text-decoration-none text-dark">
                        {{ $document->title ?? 'Без названия' }}
                    </a>
                </h4>
                
                <!-- Метаданные -->
                <div class="metadata mb-3">
                    <span>📄 {{ strtoupper($document->file_type ?? 'PDF') }}</span>
                    @if($document->total_pages)
                        <span>📖 {{ $document->total_pages }} стр.</span>
                    @endif
                    @if($document->search_count)
                        <span>🔍 {{ $document->search_count }} просмотров</span>
                    @endif
                    @if($document->detected_system)
                        <span>⚙️ {{ $document->detected_system }}</span>
                    @endif
                    @if($document->detected_component)
                        <span>🔧 {{ $document->detected_component }}</span>
                    @endif
                    @if($document->parsing_quality)
                        <span>⭐ {{ number_format($document->parsing_quality, 1) }}% качество</span>
                    @endif
                </div>
                
                <!-- Сниппет с подсветкой -->
                @if($document->content_text && !empty($query))
                    <div class="snippet mb-3">
                        @php
                            // Используем content_text или content
                            $text = $document->content_text ?? $document->content ?? '';
                            $snippet = substr($text, 0, 300);
                            
                            // Подсветка всех слов запроса
                            $queryWords = explode(' ', $query);
                            foreach ($queryWords as $word) {
                                $trimmedWord = trim($word);
                                if (strlen($trimmedWord) > 2) {
                                    $snippet = preg_replace(
                                        "/\b(" . preg_quote($trimmedWord, '/') . ")\b/i", 
                                        '<span class="highlight">$1</span>', 
                                        $snippet
                                    );
                                }
                            }
                            
                            // Если нет подсветки, показываем начало текста
                            if (!str_contains($snippet, 'highlight')) {
                                $snippet = substr($text, 0, 300);
                            }
                        @endphp
                        {!! $snippet !!}...
                    </div>
                @elseif($document->content_text)
                    <div class="snippet mb-3">
                        {{ substr($document->content_text, 0, 300) }}...
                    </div>
                @endif
                
                <!-- Ключевые слова -->
                @if($document->keywords)
                    <div class="keywords">
                        @php
                            // Обработка keywords (может быть массивом или JSON строкой)
                            $keywords = [];
                            if (is_array($document->keywords)) {
                                $keywords = $document->keywords;
                            } elseif (is_string($document->keywords) && !empty($document->keywords)) {
                                $decoded = json_decode($document->keywords, true);
                                $keywords = is_array($decoded) ? $decoded : [$document->keywords];
                            }
                            $keywords = array_slice($keywords, 0, 5);
                        @endphp
                        
                        @foreach($keywords as $keyword)
                            <span class="badge bg-light text-dark me-1 mb-1">{{ $keyword }}</span>
                        @endforeach
                    </div>
                @endif
                
                <!-- Ссылка на просмотр -->
                <div class="mt-3">
                    <a href="{{ route('search.document', $document->id) }}" class="btn btn-sm btn-outline-primary">
                        📄 Открыть документ
                    </a>
                </div>
            </div>
            @endforeach
            
            <!-- Пагинация Laravel -->
            @if($documents->hasPages())
            <nav aria-label="Page navigation" class="pagination">
                {{ $documents->appends(request()->except('page'))->links() }}
            </nav>
            @endif
            
        @elseif(!empty($query))
            <!-- Нет результатов -->
            <div class="no-results text-center py-5">
                <div class="display-1 mb-4">😕</div>
                <h2>По запросу "{{ $query }}" ничего не найдено</h2>
                <p class="lead mb-4">Попробуйте изменить запрос или использовать другие ключевые слова</p>
                
                <!-- Примеры популярных запросов -->
                <div class="mt-5">
                    <h5>Попробуйте поискать:</h5>
                    <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                        <a href="?q=двигатель" class="btn btn-outline-primary">двигатель</a>
                        <a href="?q=масло" class="btn btn-outline-primary">масло</a>
                        <a href="?q=тормоз" class="btn btn-outline-primary">тормоз</a>
                        <a href="?q=ремонт" class="btn btn-outline-primary">ремонт</a>
                        <a href="?q=диагностика" class="btn btn-outline-primary">диагностика</a>
                    </div>
                </div>
                
                <a href="{{ route('search.test') }}" class="btn btn-primary btn-lg mt-4">
                    ← Вернуться к поиску
                </a>
            </div>
        @else
            <!-- Пустой запрос -->
            <div class="no-results text-center py-5">
                <div class="display-1 mb-4">🔍</div>
                <h2>Введите поисковый запрос</h2>
                <p class="lead mb-4">Используйте форму выше для поиска документов</p>
                
                <a href="{{ route('search.test') }}" class="btn btn-primary btn-lg mt-4">
                    ← Вернуться на главную
                </a>
            </div>
        @endif
        
        <!-- Дополнительная информация -->
        @if($documents && $documents->count() > 0)
        <div class="mt-5 pt-4 border-top">
            <div class="row">
                <div class="col-md-6">
                    <h5>Советы по поиску:</h5>
                    <ul class="text-muted">
                        <li>Используйте конкретные термины (например, "тормозные колодки")</li>
                        <li>Попробуйте разные комбинации слов</li>
                        <li>Используйте фильтр по типу файла если нужно</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h5>Что можно искать:</h5>
                    <ul class="text-muted">
                        <li>Названия деталей и компонентов</li>
                        <li>Процедуры ремонта и обслуживания</li>
                        <li>Электрические схемы</li>
                        <li>Технические характеристики</li>
                    </ul>
                </div>
            </div>
        </div>
        @endif
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Подсветка при клике на слово в сниппете
            $(document).on('click', '.highlight', function() {
                const text = $(this).text();
                $('input[name="q"]').val(text);
                $(this).closest('form').submit();
            });
            
            // Автофокус на поле поиска
            $('input[name="q"]').focus();
            
            // Сохраняем фильтры при пагинации
            $('.pagination .page-link').on('click', function(e) {
                e.preventDefault();
                window.location.href = $(this).attr('href');
            });
            
            // Подсказки для поиска
            const examples = [
                'замена масла',
                'тормозная система', 
                'электрическая схема',
                'диагностика двигателя',
                'ремонт коробки передач'
            ];
            
            let exampleIndex = 0;
            const searchInput = $('input[name="q"]');
            
            // Меняем placeholder каждые 3 секунды
            setInterval(function() {
                if (!searchInput.is(':focus')) {
                    searchInput.attr('placeholder', 'Например: ' + examples[exampleIndex] + '...');
                    exampleIndex = (exampleIndex + 1) % examples.length;
                }
            }, 3000);
            
            // Быстрый поиск при нажатии Enter
            searchInput.on('keypress', function(e) {
                if (e.which === 13) {
                    $(this).closest('form').submit();
                }
            });
        });
    </script>
</body>
</html>