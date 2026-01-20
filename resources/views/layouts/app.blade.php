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
            --mobile-nav-height: 60px;
        }
        
        body {
            padding-bottom: var(--mobile-nav-height); /* Отступ для мобильной навигации */
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
                padding-bottom: 20px; /* Отступ для мобильной навигации */
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
            
            /* Мобильная навигация снизу */
            .mobile-bottom-nav {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                height: var(--mobile-nav-height);
                background: white;
                border-top: 1px solid #dee2e6;
                z-index: 1030;
                display: flex !important;
                justify-content: space-around;
                align-items: center;
                padding: 0 10px;
                box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            }
            
            .mobile-nav-item {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                flex: 1;
                padding: 8px 0;
                text-decoration: none;
                color: #6c757d;
                font-size: 0.75rem;
                transition: all 0.3s ease;
            }
            
            .mobile-nav-item.active {
                color: var(--primary-color);
                background: rgba(0, 123, 255, 0.05);
                border-radius: 8px;
            }
            
            .mobile-nav-item i {
                font-size: 1.2rem;
                margin-bottom: 4px;
            }
            
            .mobile-nav-badge {
                position: absolute;
                top: 2px;
                right: 10px;
                font-size: 0.6rem;
                padding: 1px 4px;
                min-width: 16px;
                height: 16px;
                border-radius: 8px;
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
            
            .mobile-bottom-nav {
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
                padding-bottom: 0; /* Убираем отступ на десктопе */
            }
            
            body {
                padding-bottom: 0; /* Убираем отступ на десктопе */
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
        
        /* Стили для уведомлений */
        .notification-badge {
            position: absolute;
            top: 5px;
            right: 5px;
            font-size: 0.6rem;
            padding: 0.2rem 0.4rem;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Стили для статусов консультаций */
        .status-badge {
            font-size: 0.7rem;
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
        }
        
        .status-pending { background: #ffc107; color: #000; }
        .status-in_progress { background: #17a2b8; color: #fff; }
        .status-completed { background: #28a745; color: #fff; }
        .status-cancelled { background: #dc3545; color: #fff; }
        
        /* Стили для экспертов */
        .expert-card {
            border-left: 4px solid var(--primary-color);
        }
        
        .expert-status {
            font-size: 0.75rem;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
        }
        
        .expert-online { background: #28a745; color: white; }
        .expert-offline { background: #6c757d; color: white; }
        .expert-busy { background: #ffc107; color: #000; }
        
        /* Прокрутка для мобильных */
        .mobile-scroll {
            -webkit-overflow-scrolling: touch;
            overflow-x: auto;
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
                    @if($unreadConsultationsCount ?? 0 > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge">
                            {{ min($unreadConsultationsCount, 9) }}{{ $unreadConsultationsCount > 9 ? '+' : '' }}
                        </span>
                    @endif
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item">{{ auth()->user()->name }}</span></li>
                    @if(auth()->user()->is_expert || auth()->user()->is_admin)
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="{{ route('diagnostic.consultation.expert.dashboard') }}">
                                <i class="bi bi-chat-dots me-2"></i>Панель эксперта
                                @if($pendingConsultationsCount ?? 0 > 0)
                                    <span class="badge bg-danger float-end">{{ $pendingConsultationsCount }}</span>
                                @endif
                            </a>
                        </li>
                    @endif
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
                    <a class="nav-link {{ request()->routeIs('diagnostic.start') ? 'active' : '' }}" 
                       href="{{ route('diagnostic.start') }}">
                        <i class="bi bi-tools me-2"></i>🔧 Диагностика
                    </a>
                </li>
                @if(auth()->user()->is_expert || auth()->user()->is_admin)
                <li class="nav-item position-relative">
                    <a class="nav-link {{ request()->routeIs('diagnostic.consultation.expert.*') ? 'active' : '' }}" 
                       href="{{ route('diagnostic.consultation.expert.dashboard') }}">
                        <i class="bi bi-chat-dots me-2"></i>💬 Экспертные консультации
                        @if($pendingConsultationsCount ?? 0 > 0)
                            <span class="position-absolute top-50 end-0 translate-middle-y badge rounded-pill bg-danger me-3 notification-badge">
                                {{ $pendingConsultationsCount }}
                            </span>
                        @endif
                    </a>
                </li>
                @endif
                <li class="nav-item position-relative">
                    <a class="nav-link {{ request()->routeIs('diagnostic.consultation.index') ? 'active' : '' }}" 
                       href="{{ route('diagnostic.consultation.index') }}">
                        <i class="bi bi-chat-left-text me-2"></i>📝 Мои консультации
                        @if($unreadConsultationsCount ?? 0 > 0)
                            <span class="position-absolute top-50 end-0 translate-middle-y badge rounded-pill bg-danger me-3 notification-badge">
                                {{ $unreadConsultationsCount }}
                            </span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('diagnostic.report.index') ? 'active' : '' }}" 
                       href="{{ route('diagnostic.report.index') }}">
                        <i class="bi bi-file-earmark-text me-2"></i>📄 Мои отчеты
                    </a>
                </li>
            </ul>
            
            @if(auth()->user()->is_admin)
            <h6 class="sidebar-heading px-3 mt-4 mb-2">
                <span>Управление консультациями</span>
            </h6>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.consultations.*') ? 'active' : '' }}" 
                       href="{{ route('admin.consultations.index') }}">
                        <i class="bi bi-chat-square-dots me-2"></i>🗂️ Все консультации
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.consultations.pending') ? 'active' : '' }}" 
                       href="{{ route('admin.consultations.pending') }}">
                        <i class="bi bi-clock-history me-2"></i>⏳ Ожидающие
                        @if($totalPendingConsultationsCount ?? 0 > 0)
                            <span class="position-absolute top-50 end-0 translate-middle-y badge rounded-pill bg-warning me-3 notification-badge">
                                {{ $totalPendingConsultationsCount }}
                            </span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.consultations.in-progress') ? 'active' : '' }}" 
                       href="{{ route('admin.consultations.in-progress') }}">
                        <i class="bi bi-gear me-2"></i>⚙️ В работе
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.experts.*') ? 'active' : '' }}" 
                       href="{{ route('admin.experts.index') }}">
                        <i class="bi bi-person-badge me-2"></i>👨‍🔧 Управление экспертами
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.consultations.statistics') ? 'active' : '' }}" 
                       href="{{ route('admin.consultations.statistics') }}">
                        <i class="bi bi-bar-chart me-2"></i>📊 Статистика консультаций
                    </a>
                </li>
            </ul>
            @endif
            
            <h6 class="sidebar-heading px-3 mt-4 mb-2">
                <span>Администрирование</span>
            </h6>
            <ul class="nav flex-column mb-4">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" 
                       href="{{ route('admin.categories.index') }}">
                        <i class="bi bi-folder me-2"></i>📂 Категории ремонта
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.cars.*') ? 'active' : '' }}" 
                       href="{{ route('admin.cars.import') }}">
                        <i class="bi bi-car-front me-2"></i>🚗 База автомобилей
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.search.*') ? 'active' : '' }}" 
                       href="{{ route('admin.search.index') }}">
                        <i class="bi bi-search me-2"></i>🔎 Расширенный поиск
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.documents.processing.*') ? 'active' : '' }}" 
                       href="{{ route('admin.documents.processing.index') }}">
                        <i class="bi bi-cpu me-2"></i>⚡ Обработка документов
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.diagnostic.symptoms.*') ? 'active' : '' }}" 
                       href="{{ route('admin.diagnostic.symptoms.index') }}">
                        <i class="bi bi-heart-pulse me-2"></i>🩺 Управление симптомами
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.diagnostic.rules.*') ? 'active' : '' }}" 
                       href="{{ route('admin.diagnostic.rules.index') }}">
                        <i class="bi bi-diagram-3 me-2"></i>🧩 Правила диагностики
                    </a>
                </li>
                @if(auth()->user()->is_admin)
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" 
                       href="{{ route('admin.users.index') }}">
                        <i class="bi bi-people me-2"></i>👥 Пользователи
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" 
                       href="{{ route('admin.settings.index') }}">
                        <i class="bi bi-sliders me-2"></i>⚙️ Настройки системы
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('project-info.all') }}">
                        <i class="bi bi-info-circle me-2"></i>ℹ️ Информация о проекте
                    </a>
                </li>
                @endif
            </ul>
            
            @if(auth()->user()->is_expert && !auth()->user()->is_admin)
            <h6 class="sidebar-heading px-3 mt-4 mb-2">
                <span>Экспертные функции</span>
            </h6>
            <ul class="nav flex-column mb-4">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('expert.profile.*') ? 'active' : '' }}" 
                       href="{{ route('expert.profile.edit') }}">
                        <i class="bi bi-person-circle me-2"></i>👤 Мой профиль
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('expert.schedule.*') ? 'active' : '' }}" 
                       href="{{ route('expert.schedule.index') }}">
                        <i class="bi bi-calendar me-2"></i>📅 Мой график
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('expert.analytics.*') ? 'active' : '' }}" 
                       href="{{ route('expert.analytics.index') }}">
                        <i class="bi bi-graph-up me-2"></i>📈 Моя статистика
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('expert.schedule.index') }}">
                        <i class="bi bi-clock-history me-2"></i>🕐 История консультаций
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('expert.analytics.index') }}">
                        <i class="bi bi-star me-2"></i>⭐ Мои отзывы
                    </a>
                </li>
            </ul>
            @endif
            
            <div class="px-3 mt-4">
                <div class="card bg-dark border-secondary">
                    <div class="card-body p-3">
                        <small class="text-muted d-block">Пользователь:</small>
                        <strong class="text-white">{{ auth()->user()->name }}</strong>
                        @if(auth()->user()->is_expert)
                            <span class="badge expert-online expert-status mt-1 d-inline-block">Эксперт</span>
                        @endif
                        @if(auth()->user()->is_admin)
                            <span class="badge bg-warning expert-status mt-1 d-inline-block">Админ</span>
                        @endif
                        <div class="mt-3">
                            <a href="{{ route('expert.profile.edit') }}" class="btn btn-sm btn-outline-light w-100 mb-2">
                                <i class="bi bi-person-circle me-1"></i>Профиль
                            </a>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-light w-100">
                                    <i class="bi bi-box-arrow-right me-1"></i>Выйти
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="fade-in">
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                <h1 class="h2 mb-3 mb-md-0">@yield('title')</h1>
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <div class="d-none d-md-block text-muted">
                        <i class="bi bi-person-circle me-1"></i>
                        {{ auth()->user()->name }}
                        @if(auth()->user()->is_expert)
                            <span class="badge expert-online expert-status ms-2">Эксперт</span>
                        @endif
                    </div>
                    @if(auth()->user()->is_expert || auth()->user()->is_admin)
                        <a href="{{ route('diagnostic.consultation.expert.dashboard') }}" 
                           class="btn btn-outline-primary btn-sm position-relative">
                            <i class="bi bi-chat-dots me-1"></i> Панель эксперта
                            @if($pendingConsultationsCount ?? 0 > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    {{ $pendingConsultationsCount }}
                                </span>
                            @endif
                        </a>
                    @endif
                    <a href="{{ route('diagnostic.consultation.index') }}" 
                       class="btn btn-outline-success btn-sm position-relative">
                        <i class="bi bi-chat-left-text me-1"></i> Мои консультации
                        @if($unreadConsultationsCount ?? 0 > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                {{ $unreadConsultationsCount }}
                            </span>
                        @endif
                    </a>
                    <a href="{{ route('diagnostic.start') }}" 
                       class="btn btn-outline-info btn-sm">
                        <i class="bi bi-tools me-1"></i> Новая диагностика
                    </a>
                </div>
            </div>
            @hasSection('subtitle')
                <p class="text-muted mb-0 mt-2">@yield('subtitle')</p>
            @endif
        </div>
        
        <!-- Быстрые действия для консультаций -->
        @if(request()->routeIs('admin.consultations.*') || request()->routeIs('diagnostic.consultation.*'))
        <div class="mb-4">
            <div class="card bg-light">
                <div class="card-body p-3">
                    <div class="row g-2">
                        <div class="col-auto">
                            <a href="{{ route('admin.consultations.pending') }}" class="btn btn-warning btn-sm">
                                <i class="bi bi-clock-history me-1"></i> Ожидающие
                                @if($totalPendingConsultationsCount ?? 0 > 0)
                                    <span class="badge bg-dark ms-1">{{ $totalPendingConsultationsCount }}</span>
                                @endif
                            </a>
                        </div>
                        <div class="col-auto">
                            <a href="{{ route('admin.consultations.in-progress') }}" class="btn btn-info btn-sm">
                                <i class="bi bi-gear me-1"></i> В работе
                            </a>
                        </div>
                        <div class="col-auto">
                            <a href="{{ route('admin.consultations.statistics') }}" class="btn btn-secondary btn-sm">
                                <i class="bi bi-bar-chart me-1"></i> Статистика
                            </a>
                        </div>
                        @if(auth()->user()->is_admin)
                        <div class="col-auto">
                            <a href="{{ route('admin.experts.index') }}" class="btn btn-dark btn-sm">
                                <i class="bi bi-person-badge me-1"></i> Эксперты
                            </a>
                        </div>
                        @endif
                        <div class="col-auto ms-auto">
                            <form method="GET" class="d-flex">
                                <input type="text" name="search" class="form-control form-control-sm me-2" 
                                       placeholder="Поиск консультаций..." value="{{ request('search') }}">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-search"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
        
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle me-2"></i>
                {{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @yield('content')
    </main>

    <!-- Мобильная навигация снизу -->
    <nav class="mobile-bottom-nav d-none">
        @php
            // Определяем активную страницу для мобильной навигации
            $currentRoute = request()->route()->getName();
            $activeRoute = function($routes) use ($currentRoute) {
                foreach ((array)$routes as $route) {
                    if (strpos($currentRoute, $route) === 0) {
                        return true;
                    }
                }
                return false;
            };
        @endphp
        
        <a href="{{ route('admin.dashboard') }}" 
           class="mobile-nav-item {{ $activeRoute(['admin.dashboard']) ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i>
            <span>Главная</span>
        </a>
        
        <a href="{{ route('chat.index') }}" 
           class="mobile-nav-item {{ $activeRoute(['chat.']) ? 'active' : '' }}">
            <i class="bi bi-search"></i>
            <span>Поиск</span>
        </a>
        
        <a href="{{ route('diagnostic.start') }}" 
           class="mobile-nav-item {{ $activeRoute(['diagnostic.']) && !$activeRoute(['diagnostic.consultation']) ? 'active' : '' }}">
            <i class="bi bi-tools"></i>
            <span>Диагностика</span>
        </a>
        
        @if(auth()->user()->is_expert || auth()->user()->is_admin)
        <a href="{{ route('diagnostic.consultation.expert.dashboard') }}" 
           class="mobile-nav-item position-relative {{ $activeRoute(['diagnostic.consultation.expert']) ? 'active' : '' }}">
            <i class="bi bi-chat-dots"></i>
            <span>Эксперт</span>
            @if($pendingConsultationsCount ?? 0 > 0)
                <span class="position-absolute badge bg-danger mobile-nav-badge">
                    {{ $pendingConsultationsCount }}
                </span>
            @endif
        </a>
        @endif
        
        <a href="{{ route('diagnostic.consultation.index') }}" 
           class="mobile-nav-item position-relative {{ $activeRoute(['diagnostic.consultation']) && !$activeRoute(['diagnostic.consultation.expert']) ? 'active' : '' }}">
            <i class="bi bi-chat-left-text"></i>
            <span>Консультации</span>
            @if($unreadConsultationsCount ?? 0 > 0)
                <span class="position-absolute badge bg-danger mobile-nav-badge">
                    {{ $unreadConsultationsCount }}
                </span>
            @endif
        </a>
        
        <a href="javascript:void(0)" 
           class="mobile-nav-item" 
           id="mobileMoreBtn">
            <i class="bi bi-three-dots"></i>
            <span>Ещё</span>
        </a>
    </nav>

    <!-- Попап меню для мобильных -->
    <div class="modal fade" id="mobileMoreModal" tabindex="-1">
        <div class="modal-dialog modal-bottom">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Дополнительно</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="list-group list-group-flush">
                        <a href="{{ route('admin.documents.index') }}" class="list-group-item list-group-item-action">
                            <i class="bi bi-files me-2"></i> Документы
                        </a>
                        <a href="{{ route('admin.categories.index') }}" class="list-group-item list-group-item-action">
                            <i class="bi bi-folder me-2"></i> Категории
                        </a>
                        <a href="{{ route('admin.cars.import') }}" class="list-group-item list-group-item-action">
                            <i class="bi bi-car-front me-2"></i> Автомобили
                        </a>
                        <a href="{{ route('diagnostic.report.index') }}" class="list-group-item list-group-item-action">
                            <i class="bi bi-file-earmark-text me-2"></i> Отчеты
                        </a>
                        @if(auth()->user()->is_admin)
                        <a href="{{ route('admin.diagnostic.symptoms.index') }}" class="list-group-item list-group-item-action">
                            <i class="bi bi-heart-pulse me-2"></i> Симптомы
                        </a>
                        @endif
                        @if(auth()->user()->is_expert)
                        <a href="{{ route('expert.profile.edit') }}" class="list-group-item list-group-item-action">
                            <i class="bi bi-person-circle me-2"></i> Профиль эксперта
                        </a>
                        @endif
                        <hr>
                        <a href="{{ route('logout') }}" 
                           class="list-group-item list-group-item-action text-danger"
                           onclick="event.preventDefault(); document.getElementById('logout-form-mobile').submit();">
                            <i class="bi bi-box-arrow-right me-2"></i> Выйти
                        </a>
                        <form id="logout-form-mobile" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
            const mobileMoreBtn = document.getElementById('mobileMoreBtn');
            const mobileMoreModal = new bootstrap.Modal(document.getElementById('mobileMoreModal'));
            
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
            
            if (mobileMoreBtn) {
                mobileMoreBtn.addEventListener('click', function() {
                    mobileMoreModal.show();
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
                } else if (link.getAttribute('href') && currentPath.startsWith(link.getAttribute('href'))) {
                    // Для родительских маршрутов
                    link.classList.add('active');
                }
            });
            
            // Обновление уведомлений в реальном времени
            function updateNotifications() {
                fetch('/api/consultations/unread-count')
                    .then(response => response.json())
                    .then(data => {
                        // Обновляем количество непрочитанных
                        const badges = document.querySelectorAll('.notification-badge, .mobile-nav-badge, .badge.bg-danger');
                        badges.forEach(badge => {
                            if (data.unread_count > 0) {
                                badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                                badge.style.display = 'flex';
                            } else {
                                badge.style.display = 'none';
                            }
                        });
                    })
                    .catch(err => console.error('Ошибка обновления уведомлений:', err));
                    
                // Для экспертов - обновляем количество ожидающих
                @if(auth()->check() && (auth()->user()->is_expert || auth()->user()->is_admin))
                fetch('/api/expert/pending-consultations')
                    .then(response => response.json())
                    .then(data => {
                        const expertBadges = document.querySelectorAll('.btn-outline-primary .badge');
                        expertBadges.forEach(badge => {
                            if (data.count > 0) {
                                badge.textContent = data.count > 9 ? '9+' : data.count;
                                badge.style.display = 'inline-block';
                            } else {
                                badge.style.display = 'none';
                            }
                        });
                    });
                @endif
            }
            
            // Обновляем уведомления каждые 30 секунд
            setInterval(updateNotifications, 30000);
            
            // Первое обновление
            setTimeout(updateNotifications, 1000);
            
            // Анимация для мобильных кнопок
            document.querySelectorAll('.mobile-nav-item').forEach(item => {
                item.addEventListener('click', function() {
                    if (!this.classList.contains('active')) {
                        document.querySelectorAll('.mobile-nav-item').forEach(i => {
                            i.classList.remove('active');
                        });
                        this.classList.add('active');
                    }
                });
            });
            
            // Обработка отправки форм с подтверждением
            document.querySelectorAll('form').forEach(form => {
                if (form.hasAttribute('data-confirm')) {
                    form.addEventListener('submit', function(e) {
                        if (!confirm(this.getAttribute('data-confirm'))) {
                            e.preventDefault();
                        }
                    });
                }
            });
            
            // Автоматическое скрытие алертов
            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(alert => {
                    bootstrap.Alert.getOrCreateInstance(alert).close();
                });
            }, 5000);
        });
        
        // PWA функциональность
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/service-worker.js').then(
                    function(registration) {
                        console.log('ServiceWorker зарегистрирован: ', registration.scope);
                    },
                    function(err) {
                        console.log('Ошибка регистрации ServiceWorker: ', err);
                    }
                );
            });
        }
        
        // Предотвращение масштабирования на мобильных
        document.addEventListener('touchmove', function(e) {
            if (e.scale !== 1) {
                e.preventDefault();
            }
        }, { passive: false });
    </script>
    
    @stack('scripts')
</body>
</html>