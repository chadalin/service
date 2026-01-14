<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>LR Diagnostic Flow PRO | SaaS для автосервисов Land Rover</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS для анимаций -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .pro-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .enterprise-gradient {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .btn-pro {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            @apply text-white font-bold py-4 px-8 rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 hover:scale-105;
        }
        
        .btn-enterprise {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            @apply text-white font-bold py-4 px-8 rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300;
        }
        
        .pro-card {
            @apply bg-white rounded-2xl shadow-2xl p-8 border border-gray-200 hover:border-blue-300 transition-all duration-500;
        }
        
        .feature-card {
            @apply bg-gradient-to-br from-white to-gray-50 rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-all duration-300;
        }
        
        .dashboard-preview {
            @apply bg-gradient-to-br from-gray-900 to-gray-800 rounded-2xl shadow-2xl p-6;
        }
        
        .stat-number {
            @apply text-5xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent;
        }
        
        .pricing-card {
            @apply bg-white rounded-2xl shadow-xl p-8 border-2 hover:border-blue-500 transition-all duration-300 transform hover:-translate-y-2;
        }
        
        .pricing-card.popular {
            @apply border-blue-500 shadow-2xl relative;
            transform: scale(1.05);
        }
        
        .badge-popular {
            @apply absolute -top-4 left-1/2 transform -translate-x-1/2 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold py-2 px-6 rounded-full shadow-lg;
        }
        
        .integration-card {
            @apply bg-gradient-to-br from-white to-blue-50 rounded-xl p-6 shadow-lg border border-blue-100;
        }
    </style>
</head>
<body class="bg-gradient-to-b from-gray-50 to-white">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-3 rounded-xl shadow-lg">
                        <i class="fas fa-tools text-white text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                            LR Diagnostic Flow PRO
                        </h1>
                        <p class="text-sm text-gray-600">SaaS для сервисов Land Rover</p>
                    </div>
                </div>
                
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#features" class="text-gray-700 hover:text-blue-600 font-medium">Возможности</a>
                    <a href="#pricing" class="text-gray-700 hover:text-blue-600 font-medium">Тарифы</a>
                    <a href="#integration" class="text-gray-700 hover:text-blue-600 font-medium">Интеграция</a>
                    <a href="#cases" class="text-gray-700 hover:text-blue-600 font-medium">Кейсы</a>
                    <a href="{{ route('login') }}" class="btn-pro text-sm py-2 px-6">
                        <i class="fas fa-sign-in-alt mr-2"></i> Демо-доступ
                    </a>
                </div>
                
                <button class="md:hidden text-gray-700">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-24 pb-20 px-4">
        <div class="container mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div data-aos="fade-right">
                    <span class="inline-block bg-gradient-to-r from-blue-100 to-purple-100 text-blue-800 font-bold py-2 px-4 rounded-full mb-6">
                        <i class="fas fa-crown mr-2"></i> ДЛЯ АВТОСЕРВИСОВ
                    </span>
                    
                    <h1 class="text-5xl lg:text-6xl font-bold text-gray-900 leading-tight mb-6">
                        Увеличьте прибыль 
                        <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                            на 40%
                        </span>
                        со сложными случаями Land Rover
                    </h1>
                    
                    <p class="text-xl text-gray-600 mb-8 leading-relaxed">
                        Профессиональная SaaS-платформа для диагностики и ремонта Land Rover. 
                        Решайте сложные случаи, которые другие сервисы не берут.
                    </p>
                    
                    <div class="flex flex-col sm:flex-row gap-4 mb-8">
                        <button onclick="requestDemo()" class="btn-pro">
                            <i class="fas fa-play-circle mr-2"></i> Получить демо-доступ
                        </button>
                        <a href="#pricing" class="btn-enterprise">
                            <i class="fas fa-chart-line mr-2"></i> Смотреть тарифы
                        </a>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-6">
                        <div class="flex items-center">
                            <div class="bg-green-100 p-3 rounded-xl mr-4">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                            <div>
                                <div class="font-bold text-gray-800">327 сервисов</div>
                                <div class="text-sm text-gray-600">Уже используют</div>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <div class="bg-blue-100 p-3 rounded-xl mr-4">
                                <i class="fas fa-clock text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <div class="font-bold text-gray-800">45 мин</div>
                                <div class="text-sm text-gray-600">Средняя экономия</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div data-aos="fade-left">
                    <div class="dashboard-preview">
                        <!-- Preview панели управления -->
                        <div class="mb-6">
                            <div class="flex justify-between items-center mb-4">
                                <div>
                                    <div class="text-white text-lg font-bold">Панель управления сервисом</div>
                                    <div class="text-gray-400">Активные диагностики: 12</div>
                                </div>
                                <div class="bg-green-500 text-white text-sm font-bold py-1 px-3 rounded-full">
                                    Онлайн
                                </div>
                            </div>
                            
                            <div class="bg-gray-800 rounded-xl p-4 mb-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="text-white font-medium">Range Rover Sport 2022</div>
                                    <div class="text-green-400 text-sm">В работе</div>
                                </div>
                                <div class="text-gray-400 text-sm mb-2">Проблема: Не заводится, ошибка P0087</div>
                                <div class="h-2 bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-blue-500 to-green-500" style="width: 75%"></div>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-3 gap-4">
                                <div class="bg-gray-800 rounded-lg p-3 text-center">
                                    <div class="text-white font-bold text-xl">8</div>
                                    <div class="text-gray-400 text-xs">Сегодня</div>
                                </div>
                                <div class="bg-gray-800 rounded-lg p-3 text-center">
                                    <div class="text-white font-bold text-xl">47</div>
                                    <div class="text-gray-400 text-xs">За неделю</div>
                                </div>
                                <div class="bg-gray-800 rounded-lg p-3 text-center">
                                    <div class="text-white font-bold text-xl">245к</div>
                                    <div class="text-gray-400 text-xs">Выручка</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats for Business -->
    <section class="py-16 bg-gradient-to-r from-blue-50 to-purple-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Результаты наших партнёров</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">
                    Сервисы, использующие нашу платформу, показывают значительный рост ключевых показателей
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="pro-card text-center">
                    <div class="stat-number mb-4">+40%</div>
                    <h3 class="text-lg font-bold text-gray-800 mb-2">Прибыль</h3>
                    <p class="text-gray-600 text-sm">
                        Средний рост прибыли за счет сложных заказов
                    </p>
                </div>
                
                <div class="pro-card text-center">
                    <div class="stat-number mb-4">-65%</div>
                    <h3 class="text-lg font-bold text-gray-800 mb-2">Время диагностики</h3>
                    <p class="text-gray-600 text-sm">
                        Сокращение времени на сложные случаи
                    </p>
                </div>
                
                <div class="pro-card text-center">
                    <div class="stat-number mb-4">+35%</div>
                    <h3 class="text-lg font-bold text-gray-800 mb-2">Клиентский поток</h3>
                    <p class="text-gray-600 text-sm">
                        Рост клиентов за счет экспертного позиционирования
                    </p>
                </div>
                
                <div class="pro-card text-center">
                    <div class="stat-number mb-4">93%</div>
                    <h3 class="text-lg font-bold text-gray-800 mb-2">Точность</h3>
                    <p class="text-gray-600 text-sm">
                        Точность диагноза с первого раза
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Возможности платформы</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Все инструменты для экспертной диагностики в одном месте
                </p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="feature-card" data-aos="fade-up">
                    <div class="text-4xl text-blue-500 mb-4">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">ИИ-помощник диагностики</h3>
                    <p class="text-gray-600 mb-4">
                        Нейросеть анализирует симптомы и предлагает алгоритм проверок 
                        на основе 8500+ реальных кейсов.
                    </p>
                    <ul class="space-y-2 text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Автоматический подбор диагностических шагов
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Прогноз сложности и времени ремонта
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Рекомендации по оборудованию
                        </li>
                    </ul>
                </div>
                
                <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="text-4xl text-purple-500 mb-4">
                        <i class="fas fa-database"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">База знаний и кейсов</h3>
                    <p class="text-gray-600 mb-4">
                        Доступ к закрытой базе решений сложных случаев диагностики Land Rover.
                    </p>
                    <ul class="space-y-2 text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            8500+ решенных кейсов с детальным разбором
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Схемы подключения и алгоритмы диагностики
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            База TSB (Technical Service Bulletins)
                        </li>
                    </ul>
                </div>
                
                <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="text-4xl text-green-500 mb-4">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Экспертная поддержка</h3>
                    <p class="text-gray-600 mb-4">
                        Прямая связь с топ-специалистами по Land Rover для консультаций в сложных случаях.
                    </p>
                    <ul class="space-y-2 text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Онлайн-консультации с экспертами
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Второе мнение по сложным диагнозам
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Обучение специалистов
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-8">
                <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="text-4xl text-orange-500 mb-4">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Инструменты для сервиса</h3>
                    <p class="text-gray-600 mb-4">
                        Полный набор инструментов для управления диагностическим процессом.
                    </p>
                    <ul class="space-y-2 text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Система ведения клиентских заявок
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Шаблоны диагностических отчетов
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Интеграция с 1C и CRM
                        </li>
                    </ul>
                </div>
                
                <div class="feature-card" data-aos="fade-up" data-aos-delay="400">
                    <div class="text-4xl text-red-500 mb-4">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Аналитика и отчеты</h3>
                    <p class="text-gray-600 mb-4">
                        Детальная аналитика эффективности работы сервиса и специалистов.
                    </p>
                    <ul class="space-y-2 text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            KPI специалистов и постов
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Анализ прибыльности услуг
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Прогноз загрузки и выручки
                        </li>
                    </ul>
                </div>
                
                <div class="feature-card" data-aos="fade-up" data-aos-delay="500">
                    <div class="text-4xl text-indigo-500 mb-4">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Клиентский портал</h3>
                    <p class="text-gray-600 mb-4">
                        White-label решение для ваших клиентов с брендированием под ваш сервис.
                    </p>
                    <ul class="space-y-2 text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Личный кабинет клиента
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Онлайн-запись и отслеживание ремонта
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Автоматические уведомления
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Integration -->
    <section id="integration" class="py-20 bg-gradient-to-b from-gray-50 to-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Легкая интеграция</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Работает с вашим текущим оборудованием и ПО
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="integration-card text-center">
                    <div class="text-5xl text-blue-500 mb-6">
                        <i class="fas fa-laptop-medical"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Диагностические сканеры</h3>
                    <div class="flex flex-wrap justify-center gap-3">
                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">Autel</span>
                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">Launch</span>
                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">Delphi</span>
                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">Bosch</span>
                    </div>
                </div>
                
                <div class="integration-card text-center">
                    <div class="text-5xl text-green-500 mb-6">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Бизнес-системы</h3>
                    <div class="flex flex-wrap justify-center gap-3">
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm">1C</span>
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm">CRM</span>
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm">ERP</span>
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm">API</span>
                    </div>
                </div>
                
                <div class="integration-card text-center">
                    <div class="text-5xl text-purple-500 mb-6">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Платежные системы</h3>
                    <div class="flex flex-wrap justify-center gap-3">
                        <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm">ЮKassa</span>
                        <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm">CloudPayments</span>
                        <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm">Сбербанк</span>
                        <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm">Тинькофф</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section id="pricing" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Тарифы для сервисов</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Выберите подходящий тариф. Все планы включают 14-дневный пробный период.
                </p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Basic Plan -->
                <div class="pricing-card" data-aos="fade-right">
                    <div class="text-center mb-8">
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">Базовый</h3>
                        <p class="text-gray-600">Для начинающих сервисов</p>
                        <div class="mt-6">
                            <span class="text-4xl font-bold text-gray-900">5 900 ₽</span>
                            <span class="text-gray-600">/месяц</span>
                        </div>
                    </div>
                    
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span>До 50 диагностик в месяц</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span>ИИ-помощник диагностики</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span>База знаний (чтение)</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span>Система отчетов</span>
                        </li>
                        <li class="flex items-center text-gray-400">
                            <i class="fas fa-times mr-3"></i>
                            <span>Экспертные консультации</span>
                        </li>
                        <li class="flex items-center text-gray-400">
                            <i class="fas fa-times mr-3"></i>
                            <span>White-label клиентский портал</span>
                        </li>
                    </ul>
                    
                    <button onclick="requestDemo()" class="w-full bg-gray-100 text-gray-800 font-bold py-4 rounded-xl hover:bg-gray-200 transition-colors">
                        Начать пробный период
                    </button>
                </div>
                
                <!-- Pro Plan (Popular) -->
                <div class="pricing-card popular" data-aos="fade-up">
                    <div class="badge-popular">САМЫЙ ПОПУЛЯРНЫЙ</div>
                    
                    <div class="text-center mb-8">
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">Профессиональный</h3>
                        <p class="text-gray-600">Для растущих сервисов</p>
                        <div class="mt-6">
                            <span class="text-4xl font-bold text-gray-900">14 900 ₽</span>
                            <span class="text-gray-600">/месяц</span>
                        </div>
                    </div>
                    
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span>До 200 диагностик в месяц</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span>ИИ-помощник диагностики</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span>Полная база знаний</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span>10 экспертных консультаций</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span>White-label клиентский портал</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span>Интеграция с 1C</span>
                        </li>
                    </ul>
                    
                    <button onclick="requestDemo()" class="w-full btn-pro">
                        <i class="fas fa-rocket mr-2"></i> Начать пробный период
                    </button>
                </div>
                
                <!-- Enterprise Plan -->
                <div class="pricing-card" data-aos="fade-left">
                    <div class="text-center mb-8">
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">Корпоративный</h3>
                        <p class="text-gray-600">Для сетевых сервисов</p>
                        <div class="mt-6">
                            <span class="text-4xl font-bold text-gray-900">29 900 ₽</span>
                            <span class="text-gray-600">/месяц</span>
                        </div>
                    </div>
                    
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span>Безлимитные диагностики</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span>ИИ-помощник диагностики</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span>Полная база знаний + обновления</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span>30 экспертных консультаций</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span>Мульти-брендовый портал</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span>Индивидуальная интеграция</span>
                        </li>
                    </ul>
                    
                    <button onclick="requestDemo()" class="w-full btn-enterprise">
                        <i class="fas fa-crown mr-2"></i> Запросить демо
                    </button>
                </div>
            </div>
            
            <div class="text-center mt-12">
                <p class="text-gray-600">
                    Есть вопросы по тарифам? 
                    <a href="#" class="text-blue-600 font-bold hover:underline">Свяжитесь с нашим менеджером</a>
                </p>
            </div>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="py-20 pro-gradient text-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-4xl font-bold mb-6">Начните зарабатывать на сложных случаях уже сегодня</h2>
            <p class="text-xl mb-8 max-w-2xl mx-auto opacity-90">
                Присоединяйтесь к 327 сервисам, которые уже увеличили прибыль с нашей платформой
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button onclick="requestDemo()" class="bg-white text-blue-600 font-bold py-4 px-12 rounded-xl shadow-2xl hover:shadow-3xl transition-all duration-300 transform hover:-translate-y-1 text-lg">
                    <i class="fas fa-play mr-2"></i> Получить демо-доступ
                </button>
                <a href="tel:+79991234567" class="glass-effect font-bold py-4 px-12 rounded-xl hover:bg-white/20 transition-all duration-300 text-lg">
                    <i class="fas fa-phone mr-2"></i> +7 (999) 123-45-67
                </a>
            </div>
            
            <div class="mt-12 grid grid-cols-2 md:grid-cols-4 gap-8 max-w-4xl mx-auto">
                <div>
                    <div class="text-3xl font-bold">14 дней</div>
                    <div class="text-blue-200">Пробный период</div>
                </div>
                <div>
                    <div class="text-3xl font-bold">24/7</div>
                    <div class="text-blue-200">Поддержка</div>
                </div>
                <div>
                    <div class="text-3xl font-bold">327</div>
                    <div class="text-blue-200">Сервисов</div>
                </div>
                <div>
                    <div class="text-3xl font-bold">94%</div>
                    <div class="text-blue-200">Точность</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-3 rounded-xl">
                            <i class="fas fa-tools text-white text-2xl"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold">LR Diagnostic Flow PRO</h2>
                            <p class="text-gray-400 text-sm">SaaS для сервисов Land Rover</p>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-bold mb-6">Для сервисов</h3>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Платформа</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Интеграция</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Обучение</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Партнерство</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-bold mb-6">Ресурсы</h3>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">База знаний</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Блог</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Кейсы</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Документация</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-bold mb-6">Контакты для бизнеса</h3>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <i class="fas fa-user-tie text-blue-400 mr-3"></i>
                            <span>Алексей, менеджер по партнерам</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-phone text-blue-400 mr-3"></i>
                            <span>+7 (999) 123-45-67</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-envelope text-blue-400 mr-3"></i>
                            <span>partners@lrdiagnostic.ru</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-12 pt-8 text-center text-gray-400">
                <p>© 2024 LR Diagnostic Flow PRO. Все права защищены.</p>
                <p class="mt-2 text-sm">Платформа для профессиональной диагностики Land Rover</p>
            </div>
        </div>
    </footer>

    <script>
        function requestDemo() {
            // Реализация запроса демо-доступа
          
        }
    </script>
    
    <!-- AOS Animation -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100
        });
    </script>
</body>
</html>