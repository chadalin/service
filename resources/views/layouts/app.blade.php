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
            --sidebar-collapsed: 70px;
            --header-height: 60px;
            --mobile-nav-height: 60px;
            --accordion-speed: 0.3s;
        }
        
        body {
            padding-bottom: var(--mobile-nav-height);
            overflow-x: hidden;
        }
        
        /* Аккордеон стили */
        .accordion-menu {
            transition: all var(--accordion-speed) ease;
        }
        
        .accordion-button {
            background: transparent !important;
            color: rgba(255,255,255,0.8) !important;
            border: none !important;
            padding: 0.75rem 1.5rem;
            margin: 0.25rem 1rem;
            border-radius: 8px !important;
            font-weight: 500;
            box-shadow: none !important;
        }
        
        .accordion-button:not(.collapsed) {
            background: rgba(255,255,255,0.1) !important;
            color: white !important;
            box-shadow: none !important;
        }
        
        .accordion-button::after {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='rgba%28255,255,255,0.8%29'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
            transition: transform var(--accordion-speed) ease;
            transform: rotate(-90deg);
        }
        
        .accordion-button:not(.collapsed)::after {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='white'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
            transform: rotate(0deg);
        }
        
        .accordion-body {
            padding: 0.25rem 0;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 0 0 8px 8px;
            margin: 0 1rem 0.25rem;
        }
        
        .accordion-item {
            background: transparent;
            border: none;
            margin-bottom: 0.25rem;
        }
        
        /* Свернутое состояние сайдбара */
        .sidebar-collapsed {
            width: var(--sidebar-collapsed) !important;
        }
        
        .sidebar-collapsed .sidebar-heading,
        .sidebar-collapsed .accordion-button span:not(.accordion-icon),
        .sidebar-collapsed .nav-link span:not(.nav-icon),
        .sidebar-collapsed .card-body,
        .sidebar-collapsed .logo-text {
            display: none !important;
        }
        
        .sidebar-collapsed .accordion-button {
            justify-content: center;
            padding: 0.75rem 0;
        }
        
        .sidebar-collapsed .accordion-button::after {
            display: none;
        }
        
        .sidebar-collapsed .nav-link {
            justify-content: center;
            padding: 0.75rem 0;
        }
        
        .sidebar-collapsed .text-center {
            padding: 0;
        }
        
        .sidebar-collapsed .accordion-body {
            position: absolute;
            left: var(--sidebar-collapsed);
            width: 200px;
            background: #2c3e50;
            border-radius: 8px;
            box-shadow: 2px 2px 10px rgba(0,0,0,0.3);
            z-index: 9999;
            display: none;
        }
        
        .sidebar-collapsed .accordion-button:hover + .accordion-body,
        .sidebar-collapsed .accordion-body:hover {
            display: block !important;
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
                padding-bottom: 20px;
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
            
            /* Скрываем toggle на мобильных */
            .sidebar-toggle {
                display: none !important;
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
                transition: width var(--accordion-speed) ease;
            }
            
            main {
                margin-left: var(--sidebar-width);
                width: calc(100% - var(--sidebar-width));
                padding-bottom: 0;
                transition: margin-left var(--accordion-speed) ease;
            }
            
            body {
                padding-bottom: 0;
            }
            
            /* При свернутом сайдбаре */
            .sidebar-collapsed + main {
                margin-left: var(--sidebar-collapsed);
                width: calc(100% - var(--sidebar-collapsed));
            }
        }
        
        /* Общие стили */
        .sidebar {
            background: linear-gradient(180deg, #2c3e50 0%, #1a2530 100%);
            color: white;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.6rem 1.5rem;
            margin: 0.15rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
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
        
        .nav-icon, .accordion-icon {
            min-width: 20px;
            text-align: center;
            margin-right: 10px;
        }
        
        .sidebar-collapsed .nav-icon,
        .sidebar-collapsed .accordion-icon {
            margin-right: 0;
        }
        
        .sidebar-heading {
            color: rgba(255,255,255,0.5);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0 1.5rem;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
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
        
        /* Кнопка сворачивания */
        .sidebar-toggle {
            position: absolute;
            bottom: 20px;
            right: 15px;
            background: rgba(255,255,255,0.1);
            border: none;
            color: rgba(255,255,255,0.7);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .sidebar-toggle:hover {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .sidebar-collapsed .sidebar-toggle {
            transform: rotate(180deg);
            right: 20px;
        }
        
        /* Улучшенные стили для уведомлений */
        .notification-badge {
            position: absolute;
            top: 8px;
            right: 10px;
            font-size: 0.65rem;
            padding: 0.2rem 0.4rem;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .nav-item.position-relative {
            position: relative !important;
        }
        
        /* Анимации */
        .fade-in {
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Плавное появление дочерних элементов */
        .accordion-body .nav-link {
            padding-left: 2.5rem;
            position: relative;
        }
        
        .accordion-body .nav-link::before {
            content: '';
            position: absolute;
            left: 1.8rem;
            top: 50%;
            width: 6px;
            height: 6px;
            background: rgba(255,255,255,0.5);
            border-radius: 50%;
            transform: translateY(-50%);
        }
        
        .accordion-body .nav-link.active::before {
            background: white;
        }
        
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
            <div class="text-center mb-4 px-3">
                <h5 class="text-white mb-0">
                    <span class="logo-text">AutoDoc AI</span>
                </h5>
                <small class="text-muted logo-text">Профессиональный поиск</small>
            </div>
            
            <!-- ОСНОВНОЕ МЕНЮ -->
            <h6 class="sidebar-heading">
                <span>Основное</span>
            </h6>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" 
                       href="{{ route('admin.dashboard') }}">
                        <span class="nav-icon">📊</span>
                        <span>Дашборд</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('chat.*') ? 'active' : '' }}" 
                       href="{{ route('chat.index') }}">
                        <span class="nav-icon">🔍</span>
                        <span>Умный поиск</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs(['diagnostic.ai-search.*', 'diagnostic.ai.*']) ? 'active' : '' }}" 
                       href="{{ route('diagnostic.ai-search.index') }}">
                        <span class="nav-icon">🤖</span>
                        <span>AI Поиск</span>
                    </a>
                </li>
            </ul>
            
            <!-- ДИАГНОСТИКА И КОНСУЛЬТАЦИИ -->
            <h6 class="sidebar-heading">
                <span>Диагностика</span>
            </h6>
            <div class="accordion accordion-menu" id="diagnosticAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                data-bs-target="#diagnosticCollapse">
                            <span class="accordion-icon">🔧</span>
                            <span>Диагностика и консультации</span>
                        </button>
                    </h2>
                    <div id="diagnosticCollapse" class="accordion-collapse collapse" 
                         data-bs-parent="#diagnosticAccordion">
                        <div class="accordion-body">
                            <a class="nav-link {{ request()->routeIs('diagnostic.start') ? 'active' : '' }}" 
                               href="{{ route('diagnostic.start') }}">
                                Новая диагностика
                            </a>
                            <a class="nav-link {{ request()->routeIs('diagnostic.consultation.index') ? 'active' : '' }}" 
                               href="{{ route('diagnostic.consultation.index') }}">
                                Мои консультации
                                @if($unreadConsultationsCount ?? 0 > 0)
                                    <span class="badge bg-danger float-end">{{ $unreadConsultationsCount }}</span>
                                @endif
                            </a>
                            <a class="nav-link {{ request()->routeIs('diagnostic.report.*') ? 'active' : '' }}" 
                               href="{{ route('diagnostic.report.index') }}">
                                Мои отчеты
                            </a>
                            @if(auth()->user()->is_expert || auth()->user()->is_admin)
                            <a class="nav-link {{ request()->routeIs('diagnostic.consultation.expert.*') ? 'active' : '' }}" 
                               href="{{ route('diagnostic.consultation.expert.dashboard') }}">
                                Экспертные консультации
                                @if($pendingConsultationsCount ?? 0 > 0)
                                    <span class="badge bg-danger float-end">{{ $pendingConsultationsCount }}</span>
                                @endif
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ДОКУМЕНТЫ И ПОИСК -->
            <h6 class="sidebar-heading">
                <span>Документы</span>
            </h6>
            <div class="accordion accordion-menu" id="documentsAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                data-bs-target="#documentsCollapse">
                            <span class="accordion-icon">📁</span>
                            <span>Документы и поиск</span>
                        </button>
                    </h2>
                    <div id="documentsCollapse" class="accordion-collapse collapse" 
                         data-bs-parent="#documentsAccordion">
                        <div class="accordion-body">
                            <a class="nav-link {{ request()->routeIs('admin.documents.index') ? 'active' : '' }}" 
                               href="{{ route('admin.documents.index') }}">
                                Все документы
                            </a>
                            <a class="nav-link {{ request()->routeIs('admin.documents.processing.*') ? 'active' : '' }}" 
                               href="{{ route('admin.documents.processing.index') }}">
                                Обработка документов
                            </a>
                            <a class="nav-link {{ request()->routeIs('admin.search.*') ? 'active' : '' }}" 
                               href="{{ route('admin.search.index') }}">
                                Расширенный поиск
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- АДМИНИСТРИРОВАНИЕ (Только для админов) -->
            @if(auth()->user()->is_admin)
            <h6 class="sidebar-heading">
                <span>Администрирование</span>
            </h6>
            
            <!-- Управление консультациями -->
            <div class="accordion accordion-menu" id="consultationsAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                data-bs-target="#consultationsCollapse">
                            <span class="accordion-icon">🗂️</span>
                            <span>Консультации</span>
                        </button>
                    </h2>
                    <div id="consultationsCollapse" class="accordion-collapse collapse" 
                         data-bs-parent="#consultationsAccordion">
                        <div class="accordion-body">
                            <a class="nav-link {{ request()->routeIs('admin.consultations.index') ? 'active' : '' }}" 
                               href="{{ route('admin.consultations.index') }}">
                                Все консультации
                            </a>
                            <a class="nav-link {{ request()->routeIs('admin.consultations.pending') ? 'active' : '' }}" 
                               href="{{ route('admin.consultations.pending') }}">
                                Ожидающие
                                @if($totalPendingConsultationsCount ?? 0 > 0)
                                    <span class="badge bg-warning float-end">{{ $totalPendingConsultationsCount }}</span>
                                @endif
                            </a>
                            <a class="nav-link {{ request()->routeIs('admin.consultations.in-progress') ? 'active' : '' }}" 
                               href="{{ route('admin.consultations.in-progress') }}">
                                В работе
                            </a>
                            <a class="nav-link {{ request()->routeIs('admin.consultations.statistics') ? 'active' : '' }}" 
                               href="{{ route('admin.consultations.statistics') }}">
                                Статистика
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- База знаний -->
            <div class="accordion accordion-menu" id="knowledgeBaseAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                data-bs-target="#knowledgeBaseCollapse">
                            <span class="accordion-icon">📚</span>
                            <span>База знаний</span>
                        </button>
                    </h2>
                    <div id="knowledgeBaseCollapse" class="accordion-collapse collapse" 
                         data-bs-parent="#knowledgeBaseAccordion">
                        <div class="accordion-body">
                            <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" 
                               href="{{ route('admin.categories.index') }}">
                                Категории ремонта
                            </a>
                            <a class="nav-link {{ request()->routeIs('admin.cars.*') ? 'active' : '' }}" 
                               href="{{ route('admin.cars.import') }}">
                                База автомобилей
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Диагностические правила -->
            <div class="accordion accordion-menu" id="rulesAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                data-bs-target="#rulesCollapse">
                            <span class="accordion-icon">⚙️</span>
                            <span>Диагностические правила</span>
                        </button>
                    </h2>
                    <div id="rulesCollapse" class="accordion-collapse collapse" 
                         data-bs-parent="#rulesAccordion">
                        <div class="accordion-body">
                            <a class="nav-link {{ request()->routeIs('admin.diagnostic.symptoms.*') ? 'active' : '' }}" 
                               href="{{ route('admin.diagnostic.symptoms.index') }}">
                                Управление симптомами
                            </a>
                            <a class="nav-link {{ request()->routeIs('admin.diagnostic.rules.*') ? 'active' : '' }}" 
                               href="{{ route('admin.diagnostic.rules.index') }}">
                                Правила диагностики
                            </a>
                            <a class="nav-link {{ request()->routeIs('admin.diagnostic.stats') ? 'active' : '' }}" 
                               href="{{ route('admin.diagnostic.stats') }}">
                                Статистика
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Импорт данных -->
            <div class="accordion accordion-menu" id="importAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                data-bs-target="#importCollapse">
                            <span class="accordion-icon">📥</span>
                            <span>Импорт данных</span>
                        </button>
                    </h2>
                    <div id="importCollapse" class="accordion-collapse collapse" 
                         data-bs-parent="#importAccordion">
                        <div class="accordion-body">
                            <a class="nav-link {{ request()->routeIs('admin.symptoms.import.*') ? 'active' : '' }}" 
                               href="{{ route('admin.symptoms.import.select') }}">
                                Импорт правил
                            </a>
                            <a class="nav-link {{ request()->routeIs('admin.price.import.*') ? 'active' : '' }}" 
                               href="{{ route('admin.price.import.select') }}">
                                Импорт прайс-листа
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Прайс-лист -->
            <div class="accordion accordion-menu" id="priceAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                data-bs-target="#priceCollapse">
                            <span class="accordion-icon">💰</span>
                            <span>Прайс-лист</span>
                        </button>
                    </h2>
                    <div id="priceCollapse" class="accordion-collapse collapse" 
                         data-bs-parent="#priceAccordion">
                        <div class="accordion-body">
                            <a class="nav-link {{ request()->routeIs('admin.price.index') ? 'active' : '' }}" 
                               href="{{ route('admin.price.index') }}">
                                Просмотр прайса
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Управление экспертами -->
            <div class="accordion accordion-menu" id="expertsAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                data-bs-target="#expertsCollapse">
                            <span class="accordion-icon">👨‍🔧</span>
                            <span>Управление экспертами</span>
                        </button>
                    </h2>
                    <div id="expertsCollapse" class="accordion-collapse collapse" 
                         data-bs-parent="#expertsAccordion">
                        <div class="accordion-body">
                            <a class="nav-link {{ request()->routeIs('admin.experts.*') ? 'active' : '' }}" 
                               href="{{ route('admin.experts.index') }}">
                                Список экспертов
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Система -->
            <div class="accordion accordion-menu" id="systemAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                data-bs-target="#systemCollapse">
                            <span class="accordion-icon">⚙️</span>
                            <span>Система</span>
                        </button>
                    </h2>
                    <div id="systemCollapse" class="accordion-collapse collapse" 
                         data-bs-parent="#systemAccordion">
                        <div class="accordion-body">
                            <a class="nav-link" href="{{ route('admin.search.index') }}">
                                <i class="bi bi-people me-1"></i> Пользователи
                            </a>
                            <a class="nav-link" href="{{ route('admin.search.index') }}">
                                <i class="bi bi-sliders me-1"></i> Настройки
                            </a>
                            <a class="nav-link" href="/project-info">
                                <i class="bi bi-info-circle me-1"></i> Информация
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            
            <!-- ФУНКЦИИ ЭКСПЕРТА (Только для экспертов) -->
            @if(auth()->user()->is_expert && !auth()->user()->is_admin)
            <h6 class="sidebar-heading">
                <span>Эксперт</span>
            </h6>
            <div class="accordion accordion-menu" id="expertAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                data-bs-target="#expertCollapse">
                            <span class="accordion-icon">👨‍🔧</span>
                            <span>Экспертные функции</span>
                        </button>
                    </h2>
                    <div id="expertCollapse" class="accordion-collapse collapse" 
                         data-bs-parent="#expertAccordion">
                        <div class="accordion-body">
                            <a class="nav-link {{ request()->routeIs('expert.profile.*') ? 'active' : '' }}" 
                               href="{{ route('expert.profile.edit') }}">
                                Мой профиль
                            </a>
                            <a class="nav-link {{ request()->routeIs('expert.schedule.*') ? 'active' : '' }}" 
                               href="{{ route('expert.schedule.index') }}">
                                Мой график
                            </a>
                            <a class="nav-link {{ request()->routeIs('expert.analytics.*') ? 'active' : '' }}" 
                               href="{{ route('expert.analytics.index') }}">
                                Моя статистика
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            
            <!-- ПРОФИЛЬ -->
            <div class="px-3 mt-4 d-none d-lg-block">
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
            
            <!-- Кнопка сворачивания сайдбара -->
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="bi bi-chevron-left"></i>
            </button>
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
                    @if(auth()->user()->is_admin)
                        <a href="{{ route('admin.price.import.select') }}" 
                           class="btn btn-outline-warning btn-sm">
                            <i class="bi bi-currency-dollar me-1"></i> Импорт прайса
                        </a>
                    @endif
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
                        <div class="col-auto">
                            <a href="{{ route('admin.price.index') }}" class="btn btn-outline-warning btn-sm">
                                <i class="bi bi-currency-dollar me-1"></i> Прайс-лист
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
                        <a href="{{ route('diagnostic.ai.search.page') }}" class="list-group-item list-group-item-action">
                            <i class="bi bi-robot me-2"></i> AI Поиск
                        </a>
                        @if(auth()->user()->is_admin)
                        <a href="{{ route('admin.categories.index') }}" class="list-group-item list-group-item-action">
                            <i class="bi bi-folder me-2"></i> Категории
                        </a>
                        <a href="{{ route('admin.cars.import') }}" class="list-group-item list-group-item-action">
                            <i class="bi bi-car-front me-2"></i> Автомобили
                        </a>
                        <a href="{{ route('admin.diagnostic.symptoms.index') }}" class="list-group-item list-group-item-action">
                            <i class="bi bi-heart-pulse me-2"></i> Симптомы
                        </a>
                        <a href="{{ route('admin.price.import.select') }}" class="list-group-item list-group-item-action">
                            <i class="bi bi-currency-dollar me-2"></i> Импорт прайса
                        </a>
                        <a href="{{ route('admin.price.index') }}" class="list-group-item list-group-item-action">
                            <i class="bi bi-list-ul me-2"></i> Прайс-лист
                        </a>
                        @endif
                        <a href="{{ route('diagnostic.report.index') }}" class="list-group-item list-group-item-action">
                            <i class="bi bi-file-earmark-text me-2"></i> Отчеты
                        </a>
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
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const sidebar = document.getElementById('sidebar');
            const sidebarBackdrop = document.getElementById('sidebarBackdrop');
            const mobileMoreBtn = document.getElementById('mobileMoreBtn');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const mobileMoreModal = new bootstrap.Modal(document.getElementById('mobileMoreModal'));
            
            // Мобильное меню
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
            
            // Сворачивание сайдбара на десктопе
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('sidebar-collapsed');
                    
                    // Сохраняем состояние в localStorage
                    const isCollapsed = sidebar.classList.contains('sidebar-collapsed');
                    localStorage.setItem('sidebarCollapsed', isCollapsed);
                    
                    // Обновляем иконку
                    const icon = this.querySelector('i');
                    if (isCollapsed) {
                        icon.className = 'bi bi-chevron-right';
                    } else {
                        icon.className = 'bi bi-chevron-left';
                    }
                });
            }
            
            // Восстанавливаем состояние сайдбара
            if (localStorage.getItem('sidebarCollapsed') === 'true' && window.innerWidth >= 769) {
                sidebar.classList.add('sidebar-collapsed');
                if (sidebarToggle) {
                    const icon = sidebarToggle.querySelector('i');
                    icon.className = 'bi bi-chevron-right';
                }
            }
            
            // Автоматическое сворачивание неактивных аккордеонов на мобильных
            if (window.innerWidth < 769) {
                const openAccordion = document.querySelector('.accordion-collapse.show');
                if (openAccordion) {
                    const accordionButton = openAccordion.previousElementSibling;
                    if (accordionButton) {
                        accordionButton.click();
                    }
                }
            }
            
            // Закрытие меню при клике на ссылку (мобильные)
            document.querySelectorAll('.nav-link, .accordion-button').forEach(link => {
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
                    // Раскрываем родительский аккордеон
                    let parentAccordion = link.closest('.accordion-collapse');
                    if (parentAccordion) {
                        const accordionButton = parentAccordion.previousElementSibling;
                        if (accordionButton && !accordionButton.classList.contains('collapsed')) {
                            accordionButton.click();
                        }
                        // Используем Bootstrap API для открытия аккордеона
                        const bsCollapse = new bootstrap.Collapse(parentAccordion, {
                            toggle: false
                        });
                        bsCollapse.show();
                    }
                }
            });
            
            // Обновление уведомлений
            function updateNotifications() {
                fetch('/api/consultations/unread-count')
                    .then(response => response.json())
                    .then(data => {
                        document.querySelectorAll('.notification-badge, .mobile-nav-badge').forEach(badge => {
                            if (data.unread_count > 0) {
                                badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                                badge.style.display = 'flex';
                            } else {
                                badge.style.display = 'none';
                            }
                        });
                    })
                    .catch(err => console.error('Ошибка обновления уведомлений:', err));
                    
                @if(auth()->check() && (auth()->user()->is_expert || auth()->user()->is_admin))
                fetch('/api/expert/pending-consultations')
                    .then(response => response.json())
                    .then(data => {
                        document.querySelectorAll('.btn-outline-primary .badge, .mobile-nav-badge.bg-danger').forEach(badge => {
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
            
            setInterval(updateNotifications, 30000);
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
            
            // При наведении на свернутый аккордеон - показываем его содержимое
            if (window.innerWidth >= 769) {
                document.querySelectorAll('.sidebar-collapsed .accordion-button').forEach(button => {
                    button.addEventListener('mouseenter', function() {
                        const collapseId = this.getAttribute('data-bs-target');
                        const collapseElement = document.querySelector(collapseId);
                        if (collapseElement) {
                            collapseElement.style.display = 'block';
                        }
                    });
                    
                    button.addEventListener('mouseleave', function() {
                        const collapseId = this.getAttribute('data-bs-target');
                        const collapseElement = document.querySelector(collapseId);
                        if (collapseElement && !collapseElement.matches(':hover')) {
                            collapseElement.style.display = 'none';
                        }
                    });
                });
                
                // При наведении на содержимое аккордеона
                document.querySelectorAll('.sidebar-collapsed .accordion-body').forEach(body => {
                    body.addEventListener('mouseleave', function() {
                        this.style.display = 'none';
                    });
                });
            }
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