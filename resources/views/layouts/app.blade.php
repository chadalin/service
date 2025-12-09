<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoDoc AI - @yield('title')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
        }
        .sidebar .nav-link {
            color: #fff;
        }
        .sidebar .nav-link:hover {
            background-color: #495057;
        }
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    @if(auth()->check())
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>AutoDoc AI</span>
                    </h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="{{ route('admin.dashboard') }}">
                                📊 Дашборд
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('admin.documents.index') }}">
                                📎 Документы
                            </a>
                        </li>
                    </ul>
                    
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Администрирование</span>
                    </h6>
                    <ul class="nav flex-column mb-2">
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                🚗 Бренды
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('admin.categories.index') }}">
                                🔧 Категории
                            </a>
                        </li>

                        <li class="nav-item">
                             <a class="nav-link" href="{{ route('admin.cars.brands') }}">
                                  🚗 Автомобили
                             </a>
                        </li>

                       
</li>
<li class="nav-item">
    <a class="nav-link" href="{{ route('chat.index') }}">
        🔍 Поиск
    </a>
</li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">@yield('title')</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <span class="me-2">{{ auth()->user()->name }}</span>
                        <a href="{{ route('logout') }}" class="btn btn-sm btn-outline-secondary">Выйти</a>
                    </div>
                </div>
                
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                
                @yield('content')
            </main>
        </div>
    </div>
    @else
        @yield('content')
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>