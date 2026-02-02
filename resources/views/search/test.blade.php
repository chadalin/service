{{-- resources/views/search/test.blade.php --}}
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тестирование поиска документов</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .search-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .index-status {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .index-ok {
            background: #d4edda;
            color: #155724;
        }
        .index-warning {
            background: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="search-container">
            <h1 class="text-center mb-4">🔍 Тестирование поиска документов</h1>
            
            <!-- Проверка системы -->
            @if(count($indexes) > 0)
                <div class="alert alert-success">
                    <h5>✅ FULLTEXT индексы настроены</h5>
                    <p>Найдено {{ count($indexes) }} FULLTEXT индекс(ов)</p>
                </div>
            @else
                <div class="alert alert-warning">
                    <h5>⚠ FULLTEXT индексы не найдены</h5>
                    <p>Используется LIKE-поиск. Для полноценного поиска выполните:</p>
                    <code>php artisan migrate</code><br>
                    <code>php artisan documents:index --all</code>
                </div>
            @endif
            
            <!-- Форма поиска -->
            <form action="{{ route('search.results') }}" method="GET" id="searchForm">
                <div class="mb-3">
                    <label for="searchInput" class="form-label">Поисковый запрос:</label>
                    <input type="text" 
                           name="q" 
                           id="searchInput"
                           class="form-control" 
                           placeholder="Введите запрос (например: двигатель, масло, ремонт)..."
                           value="{{ old('q') }}"
                           required>
                </div>
                
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label>Тип документа:</label>
                        <select name="file_type" class="form-control">
                            <option value="">Все типы</option>
                            <option value="pdf">PDF</option>
                            <option value="doc">DOC</option>
                            <option value="docx">DOCX</option>
                            <option value="txt">TXT</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg w-100">
                    🔍 Найти документы
                </button>
            </form>
            
            <!-- Статистика -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stats-card">
                        <h5>📄 Всего документов</h5>
                        <h2 class="text-primary">{{ $stats['total_documents'] }}</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <h5>⚡ Проиндексировано</h5>
                        <h2 class="text-success">{{ $stats['indexed_documents'] }}</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <h5>📖 Всего страниц</h5>
                        <h2 class="text-info">{{ $stats['total_pages'] }}</h2>
                    </div>
                </div>
            </div>
            
            <!-- Последние документы -->
            @if($recentDocuments->count() > 0)
            <div class="mb-4">
                <h4>Последние добавленные документы:</h4>
                <div class="list-group">
                    @foreach($recentDocuments as $doc)
                    <a href="{{ route('search.document', $doc->id) }}" 
                       class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">{{ $doc->title }}</h6>
                            <small>{{ $doc->created_at->format('d.m.Y') }}</small>
                        </div>
                        <small class="text-muted">Тип: {{ strtoupper($doc->file_type) }}</small>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
            
            <!-- Ссылки на тесты -->
            <div class="text-center">
                <a href="{{ route('search.api-test') }}" class="btn btn-outline-secondary">
                    🧪 Тестировать поисковый API
                </a>
                @if($stats['indexed_documents'] == 0)
                    <a href="/admin?command=index" class="btn btn-outline-warning ms-2">
                        ⚡ Запустить индексацию
                    </a>
                @endif
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Простая проверка формы
            $('#searchForm').on('submit', function(e) {
                const query = $('#searchInput').val().trim();
                if (query.length < 2) {
                    e.preventDefault();
                    alert('Пожалуйста, введите минимум 2 символа для поиска');
                    return false;
                }
            });
            
            // Примеры запросов
            const examples = ['двигатель', 'тормозная система', 'замена масла', 'электрическая схема'];
            let exampleIndex = 0;
            
            // Меняем placeholder каждые 3 секунды
            setInterval(function() {
                $('#searchInput').attr('placeholder', 'Например: ' + examples[exampleIndex] + '...');
                exampleIndex = (exampleIndex + 1) % examples.length;
            }, 3000);
        });
    </script>
</body>
</html>