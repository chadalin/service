<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>AutoDoc AI - @yield('title')</title>
    
    <!-- Иконки PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#007bff">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
    
    <!-- Bootstrap и иконки -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
            --sidebar-width: 280px;
            --header-height: 60px;
        }
        
        /* Мобильный адаптивный дизайн */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -280px;
                width: 280px;
                height: 100vh;
                z-index: 1050;
                transition: left 0.3s ease;
                overflow-y: auto;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .sidebar-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 1040;
                display: none;
            }
            
            .sidebar-backdrop.show {
                display: block;
            }
            
            main {
                padding-left: 0 !important;
                margin-top: var(--header-height);
            }
            
            .mobile-header {
                display: flex !important;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                height: var(--header-height);
                background: var(--dark-color);
                color: white;
                z-index: 1030;
                align-items: center;
                padding: 0 1rem;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            
            .mobile-header .logo {
                font-weight: bold;
                font-size: 1.2rem;
            }
            
            .mobile-menu-btn {
                background: none;
                border: none;
                color: white;
                font-size: 1.5rem;
                padding: 0.5rem;
                margin-right: 1rem;
            }
            
            .card {
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                margin-bottom: 1rem;
            }
            
            .btn {
                border-radius: 8px;
                padding: 0.75rem 1rem;
            }
            
            .form-control, .form-select {
                border-radius: 8px;
                padding: 0.75rem;
            }
        }
        
        /* Десктоп стили */
        @media (min-width: 769px) {
            .mobile-header {
                display: none !important;
            }
            
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                width: var(--sidebar-width);
                height: 100vh;
                overflow-y: auto;
            }
            
            main {
                margin-left: var(--sidebar-width);
                width: calc(100% - var(--sidebar-width));
            }
        }
        
        /* Общие стили */
        .sidebar {
            background: linear-gradient(180deg, #2c3e50 0%, #1a2530 100%);
            color: white;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1.5rem;
            margin: 0.25rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar .nav-link.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 2px 5px rgba(0,123,255,0.3);
        }
        
        .sidebar-heading {
            color: rgba(255,255,255,0.5);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0 1.5rem;
        }
        
        main {
            min-height: 100vh;
            background: #f5f7fa;
            padding: 1rem;
        }
        
        .page-header {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        /* Анимации */
        .fade-in {
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Улучшенные элементы формы */
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .input-group-text {
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        /* Спиннеры */
        .spinner-container {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        /* Результаты поиска */
        .result-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
        }
        
        .result-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .relevance-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
        }
    </style>
    
    @stack('styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    @if(auth()->check())
    <!-- Мобильный хедер -->
    <div class="mobile-header d-none">
        <button class="mobile-menu-btn" id="mobileMenuBtn">
            <i class="bi bi-list"></i>
        </button>
        <div class="logo">AutoDoc AI</div>
        <div class="ms-auto">
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-light" type="button" id="userDropdown" 
                        data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item">{{ auth()->user()->name }}</span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="bi bi-box-arrow-right me-2"></i>Выйти
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Бэкдроп для мобильного меню -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    
    <!-- Сайдбар -->
    <nav class="sidebar" id="sidebar">
        <div class="position-sticky pt-3">
            <div class="text-center mb-4">
                <h5 class="text-white mb-0">AutoDoc AI</h5>
                <small class="text-muted">Профессиональный поиск</small>
            </div>
            
            <h6 class="sidebar-heading px-3 mt-4 mb-2">
                <span>Основное</span>
            </h6>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" 
                       href="{{ route('admin.dashboard') }}">
                        <i class="bi bi-speedometer2 me-2"></i>📊 Дашборд
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.documents.*') ? 'active' : '' }}" 
                       href="{{ route('admin.documents.index') }}">
                        <i class="bi bi-files me-2"></i>📎 Документы
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('chat.index') ? 'active' : '' }}" 
                       href="{{ route('chat.index') }}">
                        <i class="bi bi-search me-2"></i>🔍 Умный поиск
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('diagnostic.*') ? 'active' : '' }}" 
                       href="{{ route('diagnostic.start') }}">
                        <i class="bi bi-tools me-2"></i>🔧 Диагностика
                    </a>
                </li>
            </ul>
            
            <h6 class="sidebar-heading px-3 mt-4 mb-2">
                <span>Администрирование</span>
            </h6>
            <ul class="nav flex-column mb-4">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" 
                       href="{{ route('admin.categories.index') }}">
                        <i class="bi bi-folder me-2"></i>📂 Категории
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('cars.*') ? 'active' : '' }}" 
                       href="{{ route('admin.cars.import') }}">
                        <i class="bi bi-car-front me-2"></i>🚗 Автомобили
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.search.*') ? 'active' : '' }}" 
                       href="{{ route('admin.search.index') }}">
                        <i class="bi bi-search me-2"></i>🔎 Поиск по документам
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.diagnostic.*') ? 'active' : '' }}" 
                       href="{{ route('admin.diagnostic.symptoms.index') }}">
                        <i class="bi bi-gear me-2"></i>⚙️ Диагностика (админ)
                    </a>
                </li>
            </ul>
            
            <div class="px-3 mt-4">
                <div class="card bg-dark border-secondary">
                    <div class="card-body p-3">
                        <small class="text-muted d-block">Пользователь:</small>
                        <strong class="text-white">{{ auth()->user()->name }}</strong>
                        <form action="{{ route('logout') }}" method="POST" class="mt-2">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-light w-100">
                                <i class="bi bi-box-arrow-right me-1"></i>Выйти
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="fade-in">
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                <h1 class="h2 mb-3 mb-md-0">@yield('title')</h1>
                <div class="d-flex align-items-center">
                    <div class="d-none d-md-block me-3 text-muted">
                        <i class="bi bi-person-circle me-1"></i>
                        {{ auth()->user()->name }}
                    </div>
                </div>
            </div>
        </div>
        
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @yield('content')
    </main>
    @else
        @yield('content')
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Мобильное меню
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const sidebar = document.getElementById('sidebar');
            const sidebarBackdrop = document.getElementById('sidebarBackdrop');
            
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function() {
                    sidebar.classList.add('show');
                    sidebarBackdrop.classList.add('show');
                });
            }
            
            if (sidebarBackdrop) {
                sidebarBackdrop.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    this.classList.remove('show');
                });
            }
            
            // Закрытие меню при клике на ссылку (мобильные)
            document.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 769) {
                        sidebar.classList.remove('show');
                        sidebarBackdrop.classList.remove('show');
                    }
                });
            });
            
            // Активная ссылка в меню
            const currentPath = window.location.pathname;
            document.querySelectorAll('.nav-link').forEach(link => {
                if (link.getAttribute('href') === currentPath) {
                    link.classList.add('active');
                }
            });
            
            // PWA функциональность
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/service-worker.js')
                    .then(registration => {
                        console.log('ServiceWorker registration successful');
                    })
                    .catch(err => {
                        console.log('ServiceWorker registration failed: ', err);
                    });
            }
            
            // Добавляем классы для тач устройств
            if ('ontouchstart' in window) {
                document.body.classList.add('touch-device');
            }
        });
        
        // Обработка изменения ориентации
        window.addEventListener('orientationchange', function() {
            setTimeout(() => {
                if (window.innerWidth >= 769) {
                    sidebar.classList.remove('show');
                    sidebarBackdrop.classList.remove('show');
                }
            }, 300);
        });
    </script>
    
    @stack('scripts')
</body>
</html>