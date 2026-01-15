<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Search</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background-color: #343a40; }
        .sidebar .nav-link { color: #fff; }
        .sidebar .nav-link:hover { background-color: #495057; }
        .sidebar .nav-link.active { background-color: #007bff; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">
                <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">AutoDoc AI</h6>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/search">
                            🔍 Умный поиск
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Тест поиска</h1>
            </div>
            
            <!-- Простой вывод данных -->
            <div class="card">
                <div class="card-body">
                    <h5>Отладочная информация:</h5>
                    <p>Количество марок: {{ count($brands ?? []) }}</p>
                    
                    @if(isset($brands) && count($brands) > 0)
                        <h6>Марки:</h6>
                        <ul>
                            @foreach($brands as $brand)
                                <li>{{ $brand->id }}: {{ $brand->name }}</li>
                            @endforeach
                        </ul>
                        
                        <h6>Выбор марки:</h6>
                        <select id="brandSelect" class="form-select">
                            <option value="">Выберите марку</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    @else
                        <div class="alert alert-warning">Марки не загружены!</div>
                    @endif
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Тестовая страница загружена');
    
    const brandSelect = document.getElementById('brandSelect');
    if (brandSelect) {
        console.log('Brand select найден, options:', brandSelect.innerHTML);
        brandSelect.addEventListener('change', function() {
            console.log('Выбрана марка:', this.value);
        });
    }
});
</script>
</body>
</html>