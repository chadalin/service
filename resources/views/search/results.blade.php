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
        .back-link {
            color: white;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
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
            <form action="{{ route('search.test') }}" method="GET" class="mt-4">
                <div class="row g-2">
                    <div class="col-md-8">
                        <input type="text" 
                               name="q" 
                               class="form-control form-control-lg" 
                               placeholder="Новый поиск..."
                               value="{{ $query }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-light btn-lg w-100">
                            🔍 Найти снова
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="container">
        @if(isset($results['data']) && $results['data']->count() > 0)
            <!-- Статистика -->
            <div class="alert alert-info">
                Найдено документов: <strong>{{ $results['total'] }}</strong>
                @if(!empty($filters))
                    с фильтрами: 
                    @foreach($filters as $key => $value)
                        @if($value)
                            <span class="badge bg-secondary ms-1">{{ $key }}: {{ $value }}</span>
                        @endif
                    @endforeach
                @endif
            </div>
            
            <!-- Результаты -->
            @foreach($results['data'] as $document)
            <div class="result-card position-relative">
                @if($document->relevance_score > 0.7)
                    <span class="relevance-badge">Высокая релевантность</span>
                @endif
                
                <h4>
                    <a href="{{ route('search.document', $document->id) }}" class="text-decoration-none">
                        {{ $document->title }}
                    </a>
                </h4>
                
                <!-- Метаданные -->
                <div class="metadata mb-3">
                    <span>📄 {{ $document->file_type }}</span>
                    <span>📖 {{ $document->total_pages }} стр.</span>
                    <span>🔍 {{ $document->search_count }} просмотров</span>
                    @if($document->detected_system)
                        <span>⚙️ {{ $document->detected_system }}</span>
                    @endif
                    @if($document->detected_component)
                        <span>🔧 {{ $document->detected_component }}</span>
                    @endif
                </div>
                
                <!-- Сниппет с подсветкой -->
                @if($document->content_text)
                    <div class="snippet mb-3">
                        @php
                            // Простая подсветка (в реальной системе используйте SearchService)
                            $snippet = substr($document->content_text, 0, 300);
                            $words = explode(' ', $query);
                            foreach ($words as $word) {
                                if (strlen($word) > 2) {
                                    $snippet = preg_replace(
                                        "/\b(" . preg_quote($word, '/') . ")\b/i", 
                                        '<span class="highlight">$1</span>', 
                                        $snippet
                                    );
                                }
                            }
                        @endphp
                        {!! $snippet !!}...
                    </div>
                @endif
                
                <!-- Ключевые слова -->
                @if($document->keywords)
                    <div class="keywords">
                        @foreach(array_slice($document->keywords, 0, 5) as $keyword)
                            <span class="badge bg-light text-dark me-1">{{ $keyword }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
            @endforeach
            
            <!-- Пагинация -->
            @if($results['last_page'] > 1)
            <nav aria-label="Page navigation" class="pagination">
                <ul class="pagination justify-content-center">
                    @for($i = 1; $i <= $results['last_page']; $i++)
                        <li class="page-item {{ $i == $results['current_page'] ? 'active' : '' }}">
                            <a class="page-link" 
                               href="?q={{ $query }}&page={{ $i }}
                               @foreach($filters as $key => $value)
                                   @if($value)
                                       &{{ $key }}={{ $value }}
                                   @endif
                               @endforeach">
                                {{ $i }}
                            </a>
                        </li>
                    @endfor
                </ul>
            </nav>
            @endif
            
        @elseif($query)
            <!-- Нет результатов -->
            <div class="text-center py-5">
                <div class="display-1 mb-4">😕</div>
                <h2>По запросу "{{ $query }}" ничего не найдено</h2>
                <p class="lead mb-4">Попробуйте изменить запрос или использовать другие ключевые слова</p>
                
                <!-- Популярные запросы -->
                @if($popularSearches->count() > 0)
                    <div class="mt-5">
                        <h5>Популярные поисковые запросы:</h5>
                        <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                            @foreach($popularSearches as $popular)
                                <a href="?q={{ urlencode($popular->query) }}" 
                                   class="btn btn-outline-primary">
                                    {{ $popular->query }}
                                    <span class="badge bg-secondary ms-1">{{ $popular->search_count }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
                
                <a href="{{ route('search.test') }}" class="btn btn-primary btn-lg mt-4">
                    ← Вернуться к поиску
                </a>
            </div>
        @endif
        
        <!-- Популярные запросы -->
        @if($popularSearches->count() > 0 && isset($results['data']) && $results['data']->count() > 0)
            <div class="mt-5 pt-4 border-top">
                <h5>Часто ищут:</h5>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    @foreach($popularSearches as $popular)
                        <a href="?q={{ urlencode($popular->query) }}" 
                           class="btn btn-sm btn-outline-secondary">
                            {{ $popular->query }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Подсветка при клике на сниппет
        $(document).on('click', '.highlight', function() {
            const text = $(this).text();
            $('input[name="q"]').val(text);
            $(this).closest('form').submit();
        });
        
        // Сохранение фильтров при пагинации
        $('.page-link').on('click', function(e) {
            e.preventDefault();
            window.location.href = $(this).attr('href');
        });
    </script>
</body>
</html>