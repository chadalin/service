<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>LR Diagnostic Flow | Экспертная диагностика Land Rover</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .hero-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            @apply text-white font-bold py-4 px-8 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1;
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            @apply text-white font-bold py-4 px-8 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .symptom-tag {
            @apply inline-block bg-blue-100 text-blue-800 text-sm font-medium px-3 py-1 rounded-full cursor-pointer hover:bg-blue-200 transition-colors;
        }
        
        .search-result-card {
            @apply bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border-l-4 border-blue-500 cursor-pointer;
        }
        
        .consultation-mini-card {
            @apply bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200;
        }
        
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .floating-animation {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
        
        .status-badge {
            @apply text-xs font-medium px-2 py-1 rounded-full;
        }
        
        .status-completed { @apply bg-green-100 text-green-800; }
        .status-in_progress { @apply bg-yellow-100 text-yellow-800; }
        .status-pending { @apply bg-gray-100 text-gray-800; }
        
        .price-tag {
            @apply text-green-600 font-bold;
        }
        
        .diagnostic-card {
            @apply bg-white rounded-2xl shadow-xl p-8 hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2;
        }
        
        .stat-card {
            @apply bg-gradient-to-br from-white to-blue-50 rounded-2xl shadow-lg p-6 border border-blue-100;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50">
    <!-- Navigation -->
    <nav class="glass-effect fixed w-full z-50">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-3 rounded-xl shadow-lg">
                        <i class="fas fa-stethoscope text-white text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                            LR Diagnostic Flow
                        </h1>
                        <p class="text-sm text-gray-600">Экспертная диагностика Land Rover</p>
                    </div>
                </div>
                
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#how-it-works" class="text-gray-700 hover:text-blue-600 font-medium">Как работает</a>
                    <a href="#features" class="text-gray-700 hover:text-blue-600 font-medium">Возможности</a>
                    <a href="#pricing" class="text-gray-700 hover:text-blue-600 font-medium">Тарифы</a>
                    <a href="{{ route('login') }}" class="btn-primary text-sm py-2 px-6">
                        <i class="fas fa-sign-in-alt mr-2"></i> Вход
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section с Умным Поиском -->
    <section class="pt-32 pb-20 px-4">
        <div class="container mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
                <!-- Левая колонка с текстом -->
                <div class="animate__animated animate__fadeInLeft">
                    <span class="inline-block bg-gradient-to-r from-green-100 to-blue-100 text-green-800 font-bold py-2 px-4 rounded-full mb-6">
                        <i class="fas fa-bolt mr-2"></i> ТОЧНЫЙ ДИАГНОЗ ЗА 5 МИНУТ
                    </span>
                    
                    <h1 class="text-5xl lg:text-6xl font-bold text-gray-900 leading-tight mb-6">
                        Умный поиск 
                        <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                            ошибок Land Rover
                        </span>
                    </h1>
                    
                    <p class="text-xl text-gray-600 mb-8 leading-relaxed">
                        Введите код ошибки или опишите симптомы. Мгновенный анализ по базе 1500+ кейсов.
                    </p>
                    
                    <!-- Статистика -->
                    <div class="flex flex-wrap gap-6 mb-8">
                        <div class="flex items-center">
                            <div class="bg-green-100 p-2 rounded-lg mr-3">
                                <i class="fas fa-check-circle text-green-600"></i>
                            </div>
                            <div>
                                <span class="font-bold text-xl">{{ $stats['success_rate'] }}%</span>
                                <span class="text-gray-600 block text-sm">Точность</span>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                <i class="fas fa-users text-blue-600"></i>
                            </div>
                            <div>
                                <span class="font-bold text-xl">{{ $stats['total_diagnostics'] }}</span>
                                <span class="text-gray-600 block text-sm">Диагностик</span>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <div class="bg-purple-100 p-2 rounded-lg mr-3">
                                <i class="fas fa-star text-purple-600"></i>
                            </div>
                            <div>
                                <span class="font-bold text-xl">{{ $stats['average_rating'] }}</span>
                                <span class="text-gray-600 block text-sm">Рейтинг</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Популярные симптомы -->
                    <div class="bg-white/50 backdrop-blur-sm rounded-2xl p-4">
                        <p class="text-sm text-gray-500 mb-2">Популярные запросы:</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($popularSymptoms as $symptom)
                                <button onclick="setSearchQuery('{{ $symptom->code ?? $symptom->name }}')" 
                                        class="symptom-tag">
                                    {{ $symptom->code ?? $symptom->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
                
                <!-- Правая колонка - форма поиска -->
                <div class="relative animate__animated animate__fadeInRight">
                    <div class="relative z-10">
                        <div class="bg-white rounded-3xl shadow-2xl p-8">
                            <h3 class="text-2xl font-bold text-gray-800 mb-6">
                                <i class="fas fa-search text-blue-500 mr-2"></i>
                                Найти неисправность
                            </h3>
                            
                            <form id="smartSearchForm" class="space-y-6">
                                @csrf
                                
                                <!-- Выбор бренда и модели -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-car mr-1"></i> Бренд
                                        </label>
                                        <select id="brandSelect" name="brand_id" 
                                                class="w-full border-2 border-gray-200 rounded-xl p-3 focus:border-blue-400 focus:ring focus:ring-blue-200 transition-all">
                                            <option value="">Все бренды</option>
                                            @foreach($brands as $brand)
                                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-tag mr-1"></i> Модель
                                        </label>
                                        <select id="modelSelect" name="model_id" 
                                                class="w-full border-2 border-gray-200 rounded-xl p-3 focus:border-blue-400 focus:ring focus:ring-blue-200 transition-all" disabled>
                                            <option value="">Выберите бренд</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Поле поиска -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Код ошибки или симптомы
                                    </label>
                                    <div class="relative">
                                        <input type="text" 
                                               id="searchQuery" 
                                               name="query"
                                               class="w-full border-2 border-gray-200 rounded-xl p-4 pr-12 focus:border-blue-400 focus:ring focus:ring-blue-200 transition-all"
                                               placeholder="P0300, не заводится, троит, горит check engine..."
                                               autocomplete="off"
                                               value="{{ request('q') }}">
                                        <button type="submit" 
                                                class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-blue-500 text-white p-2 rounded-xl hover:bg-blue-600 transition-colors">
                                            <i class="fas fa-arrow-right"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Кнопка расширенного поиска -->
                                <button type="button" onclick="toggleAdvancedSearch()" 
                                        class="text-blue-600 text-sm hover:underline flex items-center">
                                    <i class="fas fa-cog mr-1"></i> Расширенный поиск
                                </button>
                            </form>
                            
                            <!-- Индикатор загрузки -->
                            <div id="searchLoading" class="hidden text-center py-8">
                                <div class="loading-spinner mx-auto mb-4"></div>
                                <p class="text-gray-600">Анализируем симптомы...</p>
                            </div>
                            
                            <!-- Мини-листинг результатов (появляется сразу под поиском) -->
                            <div id="miniResults" class="mt-6 space-y-2 hidden">
                                <div class="flex justify-between items-center mb-2">
                                    <h4 class="font-semibold text-gray-700">
                                        <i class="fas fa-bolt text-yellow-500 mr-1"></i>
                                        Быстрые результаты:
                                    </h4>
                                    <span id="resultsCount" class="text-sm text-gray-500">0 найдено</span>
                                </div>
                                <div id="resultsList" class="space-y-2 max-h-60 overflow-y-auto pr-1">
                                    <!-- Сюда будут подгружаться мини-результаты -->
                                </div>
                                <button id="showAllResultsBtn" 
                                        onclick="showFullResults()"
                                        class="hidden w-full text-center text-sm text-blue-600 hover:text-blue-800 font-medium py-2">
                                    Показать все результаты <i class="fas fa-arrow-down ml-1"></i>
                                </button>
                            </div>
                            
                            <!-- Быстрая консультация -->
                            <div class="border-t pt-6 mt-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-bold text-gray-800">
                                        <i class="fas fa-headset text-blue-500 mr-2"></i>
                                        Эксперты онлайн
                                    </h4>
                                    <span class="bg-green-100 text-green-800 text-sm font-bold py-1 px-3 rounded-full pulse-animation">
                                        <i class="fas fa-circle text-xs mr-1"></i> {{ $stats['experts_online'] }} чел
                                    </span>
                                </div>
                                
                                <button onclick="openConsultationModal()" 
                                        class="w-full bg-gradient-to-r from-green-500 to-teal-500 text-white font-bold py-4 rounded-xl shadow-lg hover:shadow-xl transition-all">
                                    <i class="fas fa-video mr-2"></i>
                                    Срочная консультация
                                </button>
                                
                                <p class="text-xs text-gray-500 text-center mt-3">
                                    <i class="fas fa-shield-alt mr-1"></i>
                                    Первые 5 минут бесплатно
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Декоративные элементы -->
                    <div class="absolute -top-6 -right-6 w-24 h-24 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-full opacity-20 floating-animation"></div>
                    <div class="absolute -bottom-8 -left-8 w-32 h-32 bg-gradient-to-r from-blue-400 to-cyan-500 rounded-full opacity-20 floating-animation" style="animation-delay: 1s;"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Полные результаты поиска (появляются после клика "Показать все") -->
    <section id="fullResultsSection" class="py-12 bg-white hidden">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold text-gray-800">
                    <i class="fas fa-clipboard-list text-blue-500 mr-2"></i>
                    Результаты поиска
                </h2>
                <button onclick="hideFullResults()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Левая колонка: Найденные ошибки -->
                <div class="lg:col-span-2">
                    <div id="fullResultsList" class="space-y-4">
                        <!-- Сюда загрузятся полные результаты -->
                    </div>
                </div>
                
                <!-- Правая колонка: Связанные консультации -->
                <div>
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-6 sticky top-24">
                        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-comments text-blue-500 mr-2"></i>
                            Похожие консультации
                            <span class="ml-auto bg-blue-500 text-white text-xs px-2 py-1 rounded-full" id="consultationsCount">0</span>
                        </h3>
                        
                        <div id="relatedConsultations" class="space-y-3 max-h-[600px] overflow-y-auto pr-2">
                            <!-- Список консультаций будет подгружаться сюда -->
                            
                            @if($recentConsultations->count() > 0)
                                @foreach($recentConsultations as $consultation)
                                    <div class="consultation-mini-card" onclick="viewConsultation({{ $consultation->id }})">
                                        <div class="flex justify-between items-start mb-2">
                                            <span class="text-xs text-gray-500">
                                                <i class="far fa-calendar mr-1"></i>
                                                {{ $consultation->created_at->format('d.m.Y') }}
                                            </span>
                                            <span class="status-badge status-{{ $consultation->status }}">
                                                {{ $consultation->status == 'completed' ? 'Завершена' : 'В работе' }}
                                            </span>
                                        </div>
                                        
                                        @if($consultation->preview_images && count($consultation->preview_images) > 0)
                                            <div class="flex -space-x-2 mb-2">
                                                @foreach(array_slice($consultation->preview_images, 0, 3) as $image)
                                                    <img src="{{ $image }}" class="w-8 h-8 rounded-full border-2 border-white object-cover">
                                                @endforeach
                                            </div>
                                        @endif
                                        
                                        <p class="text-sm text-gray-700 mb-2 line-clamp-2">
                                            {{ $consultation->short_description }}
                                        </p>
                                        
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-gray-500">
                                                <i class="fas fa-user mr-1"></i>
                                                {{ $consultation->expert->name ?? 'Эксперт' }}
                                            </span>
                                            <span class="text-blue-600 font-medium">
                                                Подробнее <i class="fas fa-arrow-right ml-1"></i>
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center py-8 text-gray-500">
                                    <i class="fas fa-comment-slash text-3xl mb-2 opacity-50"></i>
                                    <p>Пока нет консультаций</p>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Призыв к действию -->
                        <div class="mt-6 pt-4 border-t border-blue-200">
                            <button onclick="openConsultationModal()" 
                                    class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold py-3 px-4 rounded-xl hover:shadow-lg transition-all">
                                <i class="fas fa-plus-circle mr-2"></i>
                                Записаться на консультацию
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-12 bg-white">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="stat-card text-center">
                    <div class="text-4xl font-bold text-blue-600 mb-2">{{ number_format($stats['total_diagnostics']) }}+</div>
                    <div class="text-gray-600">Диагностик проведено</div>
                </div>
                
                <div class="stat-card text-center">
                    <div class="text-4xl font-bold text-green-600 mb-2">{{ $stats['success_rate'] }}%</div>
                    <div class="text-gray-600">Точность диагноза</div>
                </div>
                
                <div class="stat-card text-center">
                    <div class="text-4xl font-bold text-purple-600 mb-2">{{ number_format($stats['partner_services']) }}</div>
                    <div class="text-gray-600">Сервисов-партнеров</div>
                </div>
                
                <div class="stat-card text-center">
                    <div class="text-4xl font-bold text-orange-600 mb-2">{{ $stats['average_rating'] }}</div>
                    <div class="text-gray-600">Средняя оценка</div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="py-20 bg-gradient-to-b from-white to-blue-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Как это работает</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    4 простых шага к точному диагнозу
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Step 1 -->
                <div class="diagnostic-card text-center">
                    <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full flex items-center justify-center text-white font-bold text-2xl mb-6 mx-auto shadow-lg">
                        1
                    </div>
                    <div class="text-4xl text-blue-500 mb-4">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Опишите симптомы</h3>
                    <p class="text-gray-600">
                        Введите код ошибки или опишите проблему своими словами
                    </p>
                </div>
                
                <!-- Step 2 -->
                <div class="diagnostic-card text-center">
                    <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full flex items-center justify-center text-white font-bold text-2xl mb-6 mx-auto shadow-lg">
                        2
                    </div>
                    <div class="text-4xl text-blue-500 mb-4">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Анализ ИИ</h3>
                    <p class="text-gray-600">
                        Система анализирует 1500+ кейсов и находит совпадения
                    </p>
                </div>
                
                <!-- Step 3 -->
                <div class="diagnostic-card text-center">
                    <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full flex items-center justify-center text-white font-bold text-2xl mb-6 mx-auto shadow-lg">
                        3
                    </div>
                    <div class="text-4xl text-blue-500 mb-4">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">План ремонта</h3>
                    <p class="text-gray-600">
                        Получаете пошаговые инструкции и список запчастей
                    </p>
                </div>
                
                <!-- Step 4 -->
                <div class="diagnostic-card text-center">
                    <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full flex items-center justify-center text-white font-bold text-2xl mb-6 mx-auto shadow-lg">
                        4
                    </div>
                    <div class="text-4xl text-blue-500 mb-4">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Консультация</h3>
                    <p class="text-gray-600">
                        Эксперт подтверждает диагноз и отвечает на вопросы
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Почему выбирают нас</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Уникальные возможности для точной диагностики
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="diagnostic-card">
                    <div class="bg-blue-100 w-16 h-16 rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-robot text-blue-600 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">ИИ-анализ</h3>
                    <p class="text-gray-600">
                        Нейросеть обучена на тысячах реальных случаев диагностики Land Rover
                    </p>
                </div>
                
                <div class="diagnostic-card">
                    <div class="bg-green-100 w-16 h-16 rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-video text-green-600 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Дистанционно</h3>
                    <p class="text-gray-600">
                        Загрузите фото, видео или логи сканера - диагноз удаленно
                    </p>
                </div>
                
                <div class="diagnostic-card">
                    <div class="bg-purple-100 w-16 h-16 rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-shield-alt text-purple-600 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Гарантия точности</h3>
                    <p class="text-gray-600">
                        Если диагноз неверный - вернем 100% стоимости
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 hero-gradient text-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-4xl font-bold mb-6">Начните диагностику прямо сейчас</h2>
            <p class="text-xl mb-8 max-w-2xl mx-auto opacity-90">
                Первый анализ симптомов - бесплатно
            </p>
            
            <button onclick="document.getElementById('searchQuery').focus()" 
                    class="bg-white text-blue-600 font-bold py-4 px-12 rounded-xl shadow-2xl hover:shadow-3xl transition-all duration-300 transform hover:-translate-y-1 text-lg">
                <i class="fas fa-play mr-2"></i> Бесплатная диагностика
            </button>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-3 rounded-xl">
                            <i class="fas fa-stethoscope text-white text-2xl"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold">LR Diagnostic Flow</h2>
                            <p class="text-gray-400 text-sm">Экспертная диагностика</p>
                        </div>
                    </div>
                    <p class="text-gray-400 mb-6">
                        Решаем сложные случаи диагностики Land Rover с 2018 года
                    </p>
                </div>
                
                <div>
                    <h3 class="text-lg font-bold mb-6">Контакты</h3>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <i class="fas fa-phone text-blue-400 w-6"></i>
                            <span>+7 (999) 123-45-67</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-envelope text-blue-400 w-6"></i>
                            <span>support@lrdiagnostic.ru</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-clock text-blue-400 w-6"></i>
                            <span>Поддержка 24/7</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-12 pt-8 text-center text-gray-400">
                <p>© 2024 LR Diagnostic Flow. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <!-- Модальное окно для консультации -->
    <div id="consultationModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto m-4">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-headset text-blue-500 mr-2"></i>
                        Запись на консультацию
                    </h3>
                    <button onclick="closeConsultationModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
                
                <form id="consultationForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="rule_id" id="modalRuleId">
                    
                    <div class="space-y-4">
                        <!-- Описание проблемы -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Опишите проблему *</label>
                            <textarea name="symptom_description" id="modalSymptomDesc" rows="3" required
                                      class="w-full border-2 border-gray-200 rounded-xl p-3 focus:border-blue-400"
                                      placeholder="Подробно опишите, что происходит..."></textarea>
                        </div>
                        
                        <!-- Контактные данные -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ваше имя *</label>
                                <input type="text" name="contact_name" required
                                       class="w-full border-2 border-gray-200 rounded-xl p-3 focus:border-blue-400">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Телефон *</label>
                                <input type="tel" name="contact_phone" id="modalPhone" required
                                       class="w-full border-2 border-gray-200 rounded-xl p-3 focus:border-blue-400"
                                       placeholder="+7 (___) ___-__-__">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input type="email" name="contact_email" required
                                   class="w-full border-2 border-gray-200 rounded-xl p-3 focus:border-blue-400">
                        </div>
                        
                        <!-- Данные авто -->
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Год</label>
                                <input type="number" name="year" min="1990" max="{{ date('Y') }}"
                                       class="w-full border-2 border-gray-200 rounded-xl p-3 focus:border-blue-400">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Пробег</label>
                                <input type="number" name="mileage" min="0"
                                       class="w-full border-2 border-gray-200 rounded-xl p-3 focus:border-blue-400">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">VIN</label>
                                <input type="text" name="vin" maxlength="17"
                                       class="w-full border-2 border-gray-200 rounded-xl p-3 focus:border-blue-400">
                            </div>
                        </div>
                        
                        <!-- Загрузка файлов -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-camera mr-1"></i> Фото/видео (необязательно)
                            </label>
                            <input type="file" name="files[]" multiple accept="image/*,video/*"
                                   class="w-full border-2 border-gray-200 rounded-xl p-2">
                            <p class="text-xs text-gray-500 mt-1">Максимум 10 файлов</p>
                        </div>
                        
                        <!-- Согласие -->
                        <div class="flex items-center">
                            <input type="checkbox" name="agreement" id="modalAgreement" required
                                   class="mr-2">
                            <label for="modalAgreement" class="text-sm text-gray-600">
                                Я согласен с условиями обработки персональных данных *
                            </label>
                        </div>
                        
                        <!-- Кнопка отправки -->
                        <button type="submit" id="submitConsultationBtn"
                                class="w-full bg-gradient-to-r from-green-500 to-teal-500 text-white font-bold py-4 rounded-xl hover:shadow-lg transition-all">
                            <span class="submit-text">Отправить заявку</span>
                            <span class="submit-spinner hidden">
                                <i class="fas fa-spinner fa-spin"></i> Отправка...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно для изображений -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-90 hidden items-center justify-center z-50">
        <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white text-2xl">
            <i class="fas fa-times"></i>
        </button>
        <img id="modalImage" src="" class="max-w-full max-h-full object-contain">
    </div>

    <script>
        // Глобальные переменные
        let currentBrandId = null;
        let currentSearchResults = [];
        let selectedRuleId = null;
        
        // Загрузка моделей при выборе бренда
        document.getElementById('brandSelect')?.addEventListener('change', function() {
            const brandId = this.value;
            const modelSelect = document.getElementById('modelSelect');
            
            if (!brandId) {
                modelSelect.innerHTML = '<option value="">Сначала выберите бренд</option>';
                modelSelect.disabled = true;
                return;
            }
            
            currentBrandId = brandId;
            modelSelect.disabled = true;
            modelSelect.innerHTML = '<option value="">Загрузка...</option>';
            
            fetch(`/api/brands/${brandId}/models`)
                .then(response => response.json())
                .then(models => {
                    modelSelect.innerHTML = '<option value="">Все модели</option>' + 
                        models.map(m => `<option value="${m.id}">${m.name}</option>`).join('');
                    modelSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error loading models:', error);
                    modelSelect.innerHTML = '<option value="">Ошибка загрузки</option>';
                });
        });
        
        // Обработка поиска
        document.getElementById('smartSearchForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const query = document.getElementById('searchQuery').value.trim();
            const brandId = document.getElementById('brandSelect').value;
            const modelId = document.getElementById('modelSelect').value;
            
            if (!query) {
                alert('Пожалуйста, введите код ошибки или опишите симптомы');
                return;
            }
            
            // Показываем загрузку
            document.getElementById('searchLoading').classList.remove('hidden');
            document.getElementById('miniResults').classList.add('hidden');
            
            try {
                const response = await fetch('/api/diagnostic/search', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ query, brand_id: brandId, model_id: modelId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    currentSearchResults = data.rules || [];
                    displayMiniResults(currentSearchResults);
                } else {
                    throw new Error(data.message || 'Ошибка поиска');
                }
                
            } catch (error) {
                console.error('Search error:', error);
                alert('Ошибка при поиске. Пожалуйста, попробуйте снова.');
            } finally {
                document.getElementById('searchLoading').classList.add('hidden');
            }
        });
        
        // Отображение мини-результатов
        function displayMiniResults(results) {
            const miniResults = document.getElementById('miniResults');
            const resultsList = document.getElementById('resultsList');
            const resultsCount = document.getElementById('resultsCount');
            const showAllBtn = document.getElementById('showAllResultsBtn');
            
            if (!results || results.length === 0) {
                resultsList.innerHTML = `
                    <div class="text-center py-4 text-gray-500">
                        <i class="fas fa-search text-2xl mb-2 opacity-50"></i>
                        <p>Ничего не найдено</p>
                        <button onclick="openConsultationModal()" class="text-blue-600 text-sm mt-2 hover:underline">
                            Заказать консультацию →
                        </button>
                    </div>
                `;
                resultsCount.textContent = '0 найдено';
                showAllBtn.classList.add('hidden');
                miniResults.classList.remove('hidden');
                return;
            }
            
            resultsCount.textContent = results.length + ' найдено';
            
            // Показываем первые 3 результата
            const displayResults = results.slice(0, 3);
            
            resultsList.innerHTML = displayResults.map(rule => `
                <div class="p-3 hover:bg-gray-50 rounded-lg cursor-pointer border border-gray-100" 
                     onclick="selectQuickResult(${rule.id})">
                    <div class="font-medium text-blue-600">${rule.symptom?.name || 'Код ошибки'}</div>
                    <div class="text-sm text-gray-600 line-clamp-1">${rule.symptom?.description || ''}</div>
                    <div class="flex justify-between items-center mt-1 text-xs">
                        <span class="text-gray-500">
                            <i class="far fa-clock mr-1"></i>${rule.estimated_time || 'N/A'} мин
                        </span>
                        <span class="price-tag">от ${formatPrice(rule.base_consultation_price)} ₽</span>
                    </div>
                </div>
            `).join('');
            
            if (results.length > 3) {
                showAllBtn.classList.remove('hidden');
                showAllBtn.innerHTML = `Показать все (${results.length}) <i class="fas fa-arrow-down ml-1"></i>`;
            } else {
                showAllBtn.classList.add('hidden');
            }
            
            miniResults.classList.remove('hidden');
        }
        
        // Выбор быстрого результата
        function selectQuickResult(ruleId) {
            selectedRuleId = ruleId;
            showFullResults();
            loadRelatedConsultations(ruleId);
        }
        
        // Показать полные результаты
        function showFullResults() {
            const fullResultsSection = document.getElementById('fullResultsSection');
            const fullResultsList = document.getElementById('fullResultsList');
            
            fullResultsList.innerHTML = currentSearchResults.map(rule => `
                <div class="search-result-card" onclick="selectFullResult(${rule.id})" id="full-rule-${rule.id}">
                    <div class="flex justify-between items-start mb-3">
                        <h4 class="text-lg font-bold text-gray-800">${rule.symptom?.name || 'Неизвестный код'}</h4>
                        <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                            Сложность: ${rule.complexity_level || '?'}/10
                        </span>
                    </div>
                    
                    <p class="text-gray-600 mb-3">${rule.symptom?.description || 'Описание отсутствует'}</p>
                    
                    ${rule.possible_causes && rule.possible_causes.length > 0 ? `
                        <div class="mb-3">
                            <p class="text-sm font-medium text-gray-700 mb-1">Возможные причины:</p>
                            <div class="flex flex-wrap gap-1">
                                ${rule.possible_causes.slice(0, 3).map(cause => 
                                    `<span class="symptom-tag text-xs">${cause}</span>`
                                ).join('')}
                                ${rule.possible_causes.length > 3 ? 
                                    `<span class="text-xs text-gray-500">+${rule.possible_causes.length - 3}</span>` : ''}
                            </div>
                        </div>
                    ` : ''}
                    
                    ${rule.matched_parts && rule.matched_parts.length > 0 ? `
                        <div class="mb-3 p-3 bg-yellow-50 rounded-lg">
                            <p class="text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-tools text-yellow-600 mr-1"></i>Возможные запчасти:
                            </p>
                            <div class="space-y-1">
                                ${rule.matched_parts.map(part => `
                                    <div class="text-sm flex justify-between">
                                        <span>${part.name}</span>
                                        <span class="font-medium">${part.price ? formatPrice(part.price) + ' ₽' : 'Цена по запросу'}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}
                    
                    <div class="flex justify-between items-center mt-4 pt-3 border-t">
                        <div class="text-gray-500 text-sm">
                            <i class="far fa-clock mr-1"></i> ~${rule.estimated_time || 'N/A'} мин
                            <span class="mx-2">•</span>
                            <i class="fas fa-comments mr-1"></i> ${rule.consultations_count || 0} консультаций
                        </div>
                        <div class="text-green-600 font-bold text-lg">
                            от ${formatPrice(rule.base_consultation_price)} ₽
                        </div>
                    </div>
                    
                    <div class="mt-3 flex gap-2">
                        <button onclick="event.stopPropagation(); openConsultationModal(${rule.id})" 
                                class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm">
                            <i class="fas fa-headset mr-1"></i> Консультация
                        </button>
                        <button onclick="event.stopPropagation(); viewDiagnosticSteps(${rule.id})" 
                                class="flex-1 border border-blue-600 text-blue-600 py-2 rounded-lg hover:bg-blue-50 transition-colors text-sm">
                            <i class="fas fa-list mr-1"></i> Шаги
                        </button>
                    </div>
                </div>
            `).join('');
            
            fullResultsSection.classList.remove('hidden');
            fullResultsSection.scrollIntoView({ behavior: 'smooth' });
        }
        
        // Выбор полного результата
        function selectFullResult(ruleId) {
            selectedRuleId = ruleId;
            
            // Подсвечиваем выбранный результат
            document.querySelectorAll('[id^="full-rule-"]').forEach(el => {
                el.classList.remove('border-blue-500', 'bg-blue-50');
            });
            const selected = document.getElementById(`full-rule-${ruleId}`);
            if (selected) {
                selected.classList.add('border-blue-500', 'bg-blue-50');
            }
            
            loadRelatedConsultations(ruleId);
        }
        
        // Загрузка связанных консультаций
        async function loadRelatedConsultations(ruleId) {
            try {
                const response = await fetch(`/api/diagnostic/rules/${ruleId}/consultations`);
                const data = await response.json();
                
                const consultationsDiv = document.getElementById('relatedConsultations');
                const countSpan = document.getElementById('consultationsCount');
                
                if (!data.success || !data.consultations || data.consultations.length === 0) {
                    consultationsDiv.innerHTML = `
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-comment-slash text-3xl mb-2 opacity-50"></i>
                            <p>Похожих консультаций пока нет</p>
                            <button onclick="openConsultationModal(${ruleId})" 
                                    class="text-blue-600 text-sm mt-2 hover:underline">
                                Будьте первым!
                            </button>
                        </div>
                    `;
                    countSpan.textContent = '0';
                    return;
                }
                
                countSpan.textContent = data.consultations.length;
                
                consultationsDiv.innerHTML = data.consultations.map(cons => `
                    <div class="consultation-mini-card" onclick="viewConsultation(${cons.id})">
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-xs text-gray-500">
                                <i class="far fa-calendar mr-1"></i>
                                ${new Date(cons.created_at).toLocaleDateString('ru-RU')}
                            </span>
                            <span class="status-badge status-${cons.status}">
                                ${getStatusText(cons.status)}
                            </span>
                        </div>
                        
                        ${cons.preview_images && cons.preview_images.length > 0 ? `
                            <div class="flex -space-x-2 mb-2">
                                ${cons.preview_images.slice(0, 3).map(img => `
                                    <img src="${img}" class="w-8 h-8 rounded-full border-2 border-white object-cover" 
                                         onerror="this.src='/img/no-image.jpg'">
                                `).join('')}
                            </div>
                        ` : ''}
                        
                        <p class="text-sm text-gray-700 mb-2 line-clamp-2">${cons.short_description}</p>
                        
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500">
                                <i class="fas fa-user mr-1"></i>
                                ${cons.expert?.name || 'Эксперт'}
                            </span>
                            <span class="text-blue-600 font-medium">
                                Подробнее <i class="fas fa-arrow-right ml-1"></i>
                            </span>
                        </div>
                        
                        ${cons.files_count > 0 ? `
                            <div class="mt-2 text-xs text-gray-400">
                                <i class="fas fa-paperclip mr-1"></i>
                                ${cons.files_count} файлов
                            </div>
                        ` : ''}
                    </div>
                `).join('');
                
            } catch (error) {
                console.error('Error loading consultations:', error);
            }
        }
        
        // Скрыть полные результаты
        function hideFullResults() {
            document.getElementById('fullResultsSection').classList.add('hidden');
            document.getElementById('miniResults').classList.remove('hidden');
        }
        
        // Открыть модальное окно консультации
        function openConsultationModal(ruleId = null) {
            if (ruleId) {
                document.getElementById('modalRuleId').value = ruleId;
                
                // Предзаполняем описание из выбранного правила
                const selectedRule = currentSearchResults.find(r => r.id === ruleId);
                if (selectedRule && selectedRule.symptom) {
                    document.getElementById('modalSymptomDesc').value = 
                        `Проблема: ${selectedRule.symptom.name}\n${selectedRule.symptom.description || ''}`;
                }
            }
            
            document.getElementById('consultationModal').classList.remove('hidden');
            document.getElementById('consultationModal').classList.add('flex');
        }
        
        function closeConsultationModal() {
            document.getElementById('consultationModal').classList.add('hidden');
            document.getElementById('consultationModal').classList.remove('flex');
        }
        
        // Просмотр консультации
        function viewConsultation(id) {
            window.location.href = `/diagnostic/consultations/${id}`;
        }
        
        // Просмотр шагов диагностики
        function viewDiagnosticSteps(ruleId) {
            // Можно открыть модальное окно с шагами или перейти на страницу
            alert('Функция в разработке');
        }
        
        // Установка поискового запроса
        function setSearchQuery(query) {
            document.getElementById('searchQuery').value = query;
            document.getElementById('smartSearchForm').dispatchEvent(new Event('submit'));
        }
        
        // Переключение расширенного поиска
        function toggleAdvancedSearch() {
            // Можно добавить дополнительные поля
            alert('Расширенный поиск будет доступен позже');
        }
        
        // Форматирование цены
        function formatPrice(price) {
            return new Intl.NumberFormat('ru-RU').format(price || 0);
        }
        
        // Статус текст
        function getStatusText(status) {
            const statuses = {
                'completed': 'Завершена',
                'in_progress': 'В работе',
                'pending': 'Ожидает',
                'paid': 'Оплачена',
                'confirmed': 'Подтверждена'
            };
            return statuses[status] || status;
        }
        
        // Маска для телефона
        function maskPhone(phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                
                if (value.length > 0) {
                    if (value[0] === '8') {
                        value = '7' + value.substring(1);
                    }
                    if (value.length === 1) {
                        value = '+7' + value;
                    }
                }
                
                let formatted = value;
                if (value.length > 1) {
                    formatted = '+7 (' + value.substring(1, 4);
                }
                if (value.length >= 5) {
                    formatted += ') ' + value.substring(4, 7);
                }
                if (value.length >= 8) {
                    formatted += '-' + value.substring(7, 9);
                }
                if (value.length >= 10) {
                    formatted += '-' + value.substring(9, 11);
                }
                
                e.target.value = formatted.substring(0, 18);
            });
        }
        
        // Инициализация масок для телефона
        document.getElementById('modalPhone') && maskPhone(document.getElementById('modalPhone'));
        
        // Обработка отправки формы консультации
        document.getElementById('consultationForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitConsultationBtn');
            const submitText = submitBtn.querySelector('.submit-text');
            const submitSpinner = submitBtn.querySelector('.submit-spinner');
            
            submitBtn.disabled = true;
            submitText.classList.add('hidden');
            submitSpinner.classList.remove('hidden');
            
            try {
                const formData = new FormData(this);
                
                const response = await fetch('/api/consultation/order', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });
                
                const result = await response.json();
                
                if (response.ok && result.success) {
                    alert('Заявка успешно отправлена! Наш эксперт свяжется с вами в ближайшее время.');
                    closeConsultationModal();
                    
                    if (result.redirect_url) {
                        window.location.href = result.redirect_url;
                    }
                } else {
                    throw new Error(result.message || 'Ошибка отправки');
                }
                
            } catch (error) {
                console.error('Error:', error);
                alert('Ошибка: ' + error.message);
            } finally {
                submitBtn.disabled = false;
                submitText.classList.remove('hidden');
                submitSpinner.classList.add('hidden');
            }
        });
        
        // Инициализация при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            // Если есть GET параметр q, выполняем поиск автоматически
            const urlParams = new URLSearchParams(window.location.search);
            const queryParam = urlParams.get('q');
            if (queryParam) {
                document.getElementById('searchQuery').value = queryParam;
                document.getElementById('smartSearchForm').dispatchEvent(new Event('submit'));
            }
        });
    </script>
</body>
</html>