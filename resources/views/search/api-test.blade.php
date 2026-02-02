{{-- resources/views/search/api-test.blade.php --}}
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тестирование поискового API</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .test-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        .api-test-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: #f8f9fa;
        }
        .test-result {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin-top: 10px;
            max-height: 300px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 0.9rem;
        }
        .endpoint-badge {
            background: #6c757d;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-right: 10px;
        }
        .test-button {
            min-width: 120px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1 class="mb-4">🧪 Тестирование поискового API</h1>
        
        <div class="mb-4">
            <a href="{{ route('search.test') }}" class="btn btn-outline-primary">
                ← Назад к поиску
            </a>
        </div>
        
        <!-- Тест 1: Проверка индексации -->
        <div class="api-test-card">
            <h4>1. Проверка индексации документов</h4>
            <p>Количество проиндексированных документов: <strong>{{ $results['total_documents'] ?? 0 }}</strong></p>
            <button id="testIndexing" class="btn btn-primary test-button">
                Проверить индексацию
            </button>
            <div id="indexingResult" class="test-result d-none"></div>
        </div>
        
        <!-- Тест 2: Быстрый поиск по тестовым запросам -->
        <div class="api-test-card">
            <h4>2. Тестирование поиска по запросам</h4>
            <div class="mb-3">
                @foreach($testQueries as $query)
                    <button class="btn btn-outline-secondary test-query-btn me-2 mb-2" 
                            data-query="{{ $query }}">
                        {{ $query }}
                    </button>
                @endforeach
            </div>
            <div class="input-group mb-3">
                <input type="text" id="customQuery" class="form-control" 
                       placeholder="Введите свой запрос...">
                <button id="testCustomQuery" class="btn btn-primary">
                    Тестировать
                </button>
            </div>
            <div id="searchResult" class="test-result d-none"></div>
        </div>
        
        <!-- Тест 3: Проверка API эндпоинтов -->
        <div class="api-test-card">
            <h4>3. Проверка API эндпоинтов</h4>
            <div class="mb-3">
                <button class="btn btn-info test-endpoint me-2 mb-2" 
                        data-endpoint="/api/search/autocomplete?q=двигатель">
                    <span class="endpoint-badge">GET</span> Автодополнение
                </button>
                <button class="btn btn-info test-endpoint me-2 mb-2" 
                        data-endpoint="/api/search/stats">
                    <span class="endpoint-badge">GET</span> Статистика
                </button>
                <button class="btn btn-info test-endpoint me-2 mb-2" 
                        data-endpoint="/api/search">
                    <span class="endpoint-badge">POST</span> Поиск
                </button>
            </div>
            <div id="endpointResult" class="test-result d-none"></div>
        </div>
        
        <!-- Результаты предварительного теста -->
        <div class="api-test-card">
            <h4>📊 Предварительные результаты тестирования</h4>
            <table class="table">
                <thead>
                    <tr>
                        <th>Запрос</th>
                        <th>Найдено документов</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($results as $query => $count)
                        <tr>
                            <td>{{ $query }}</td>
                            <td>
                                <span class="badge bg-{{ $count > 0 ? 'success' : 'warning' }}">
                                    {{ $count }}
                                </span>
                            </td>
                            <td>
                                @if($count > 0)
                                    <span class="text-success">✓ Работает</span>
                                @else
                                    <span class="text-warning">⚠ Нет результатов</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Тест индексации
            $('#testIndexing').on('click', function() {
                const btn = $(this);
                btn.prop('disabled', true).text('Проверяем...');
                
                $.ajax({
                    url: '{{ route("api.search.stats") }}',
                    type: 'GET',
                    success: function(data) {
                        $('#indexingResult').html(JSON.stringify(data, null, 2))
                                           .removeClass('d-none');
                    },
                    error: function(xhr) {
                        $('#indexingResult').html('Ошибка: ' + xhr.responseText)
                                           .removeClass('d-none');
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Проверить индексацию');
                    }
                });
            });
            
            // Тест поиска по запросам
            $('.test-query-btn').on('click', function() {
                const query = $(this).data('query');
                testSearchQuery(query);
            });
            
            $('#testCustomQuery').on('click', function() {
                const query = $('#customQuery').val();
                if (query.trim()) {
                    testSearchQuery(query);
                }
            });
            
            function testSearchQuery(query) {
                $('#searchResult').html('Тестируем запрос: ' + query + '...')
                                 .removeClass('d-none');
                
                $.ajax({
                    url: '{{ route("api.search") }}',
                    type: 'POST',
                    data: {
                        query: query,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(data) {
                        $('#searchResult').html(JSON.stringify(data, null, 2));
                    },
                    error: function(xhr) {
                        $('#searchResult').html('Ошибка: ' + xhr.responseText);
                    }
                });
            }
            
            // Тест API эндпоинтов
            $('.test-endpoint').on('click', function() {
                const endpoint = $(this).data('endpoint');
                const method = $(this).find('.endpoint-badge').text().trim();
                
                $('#endpointResult').html('Тестируем ' + method + ' ' + endpoint + '...')
                                   .removeClass('d-none');
                
                if (method === 'POST') {
                    $.ajax({
                        url: endpoint,
                        type: 'POST',
                        data: {
                            query: 'тестовый запрос',
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(data) {
                            $('#endpointResult').html(JSON.stringify(data, null, 2));
                        },
                        error: function(xhr) {
                            $('#endpointResult').html('Ошибка: ' + xhr.responseText);
                        }
                    });
                } else {
                    $.ajax({
                        url: endpoint,
                        type: 'GET',
                        success: function(data) {
                            $('#endpointResult').html(JSON.stringify(data, null, 2));
                        },
                        error: function(xhr) {
                            $('#endpointResult').html('Ошибка: ' + xhr.responseText);
                        }
                    });
                }
            });
            
            // Автозапуск базового теста
            setTimeout(function() {
                $('#testIndexing').click();
            }, 1000);
        });
    </script>
</body>
</html>