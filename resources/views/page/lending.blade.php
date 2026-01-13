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
    
    <!-- Animate.css для анимаций -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <!-- Swiper для слайдеров -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .hero-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .feature-gradient {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .card-gradient {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            @apply text-white font-bold py-4 px-8 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1;
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            @apply text-white font-bold py-4 px-8 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300;
        }
        
        .diagnostic-card {
            @apply bg-white rounded-2xl shadow-xl p-8 hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2;
        }
        
        .stat-card {
            @apply bg-gradient-to-br from-white to-blue-50 rounded-2xl shadow-lg p-6 border border-blue-100;
        }
        
        .testimonial-card {
            @apply bg-gradient-to-br from-gray-50 to-white rounded-2xl shadow-lg p-6 border border-gray-200;
        }
        
        .floating-animation {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
        
        .pulse-animation {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .complexity-indicator {
            @apply h-2 rounded-full;
        }
        
        .step-indicator {
            @apply w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg;
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
                    <a href="#how-it-works" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Как работает</a>
                    <a href="#features" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Возможности</a>
                    <a href="#pricing" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Тарифы</a>
                    <a href="#testimonials" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Отзывы</a>
                    <a href="{{ route('login') }}" class="btn-primary text-sm py-2 px-6">
                        <i class="fas fa-sign-in-alt mr-2"></i> Вход
                    </a>
                </div>
                
                <button class="md:hidden text-gray-700" id="mobile-menu-button">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-32 pb-20 px-4">
        <div class="container mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="animate__animated animate__fadeInLeft">
                    <span class="inline-block bg-gradient-to-r from-green-100 to-blue-100 text-green-800 font-bold py-2 px-4 rounded-full mb-6">
                        <i class="fas fa-bolt mr-2"></i> ТОЧНЫЙ ДИАГНОЗ ЗА 5 МИНУТ
                    </span>
                    
                    <h1 class="text-5xl lg:text-6xl font-bold text-gray-900 leading-tight mb-6">
                        Когда официальный сервис 
                        <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                            не нашел причину
                        </span>
                    </h1>
                    
                    <p class="text-xl text-gray-600 mb-8 leading-relaxed">
                        ИИ-система экспертной диагностики Land Rover. Получите точный диагноз, 
                        пошаговый план ремонта и консультацию топ-специалиста.
                    </p>
                    
                    <div class="flex flex-col sm:flex-row gap-4 mb-8">
                        <button onclick="startDiagnostic()" class="btn-primary">
                            <i class="fas fa-play-circle mr-2"></i> Начать диагностику
                        </button>
                        <a href="#how-it-works" class="btn-secondary">
                            <i class="fas fa-info-circle mr-2"></i> Как это работает
                        </a>
                    </div>
                    
                    <div class="flex items-center space-x-6">
                        <div class="flex items-center">
                            <div class="bg-green-100 p-2 rounded-lg mr-3">
                                <i class="fas fa-check-circle text-green-600"></i>
                            </div>
                            <span class="text-gray-700">Точность диагноза 94%</span>
                        </div>
                        <div class="flex items-center">
                            <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                <i class="fas fa-clock text-blue-600"></i>
                            </div>
                            <span class="text-gray-700">24/7 поддержка</span>
                        </div>
                    </div>
                </div>
                
                <div class="relative animate__animated animate__fadeInRight">
                    <div class="relative z-10">
                        <div class="bg-white rounded-3xl shadow-2xl p-8">
                            <!-- Диагностический интерфейс -->
                            <div class="mb-6">
                                <h3 class="text-xl font-bold text-gray-800 mb-4">Быстрая диагностика</h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-gray-700 mb-2">Что беспокоит?</label>
                                        <div class="grid grid-cols-2 gap-3">
                                            <button class="symptom-btn border-2 border-gray-200 rounded-xl p-3 text-left hover:border-blue-400 transition-colors">
                                                <i class="fas fa-engine text-blue-500 mr-2"></i>
                                                <span>Не заводится</span>
                                            </button>
                                            <button class="symptom-btn border-2 border-gray-200 rounded-xl p-3 text-left hover:border-blue-400 transition-colors">
                                                <i class="fas fa-exclamation-triangle text-orange-500 mr-2"></i>
                                                <span>Горит Check Engine</span>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-gray-700 mb-2">Модель авто</label>
                                        <select class="w-full border-2 border-gray-200 rounded-xl p-3">
                                            <option>Range Rover Sport</option>
                                            <option>Range Rover Vogue</option>
                                            <option>Discovery 5</option>
                                            <option>Defender</option>
                                        </select>
                                    </div>
                                    
                                    <button onclick="startDiagnostic()" class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold py-4 rounded-xl shadow-lg hover:shadow-xl transition-all">
                                        <i class="fas fa-search mr-2"></i> Анализировать симптомы
                                    </button>
                                </div>
                            </div>
                            
                            <div class="border-t pt-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-bold text-gray-800">Текущие консультации</h4>
                                    <span class="bg-green-100 text-green-800 text-sm font-bold py-1 px-3 rounded-full">12 онлайн</span>
                                </div>
                                
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                        <div class="flex items-center">
                                            <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                                            <span class="font-medium">Range Rover 2021</span>
                                        </div>
                                        <span class="text-sm text-gray-600">5 мин назад</span>
                                    </div>
                                    <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                        <div class="flex items-center">
                                            <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                                            <span class="font-medium">Discovery 2019</span>
                                        </div>
                                        <span class="text-sm text-gray-600">8 мин назад</span>
                                    </div>
                                </div>
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

    <!-- Stats Section -->
    <section class="py-12 bg-white">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="stat-card text-center">
                    <div class="text-4xl font-bold text-blue-600 mb-2">1,847+</div>
                    <div class="text-gray-600">Диагностик проведено</div>
                    <div class="mt-2">
                        <div class="complexity-indicator bg-gradient-to-r from-green-400 to-blue-500" style="width: 100%"></div>
                    </div>
                </div>
                
                <div class="stat-card text-center">
                    <div class="text-4xl font-bold text-green-600 mb-2">94%</div>
                    <div class="text-gray-600">Точность диагноза</div>
                    <div class="mt-2">
                        <div class="complexity-indicator bg-gradient-to-r from-green-400 to-blue-500" style="width: 94%"></div>
                    </div>
                </div>
                
                <div class="stat-card text-center">
                    <div class="text-4xl font-bold text-purple-600 mb-2">327</div>
                    <div class="text-gray-600">Сервисов-партнеров</div>
                    <div class="mt-2">
                        <div class="complexity-indicator bg-gradient-to-r from-green-400 to-blue-500" style="width: 85%"></div>
                    </div>
                </div>
                
                <div class="stat-card text-center">
                    <div class="text-4xl font-bold text-orange-600 mb-2">4.9</div>
                    <div class="text-gray-600">Средняя оценка</div>
                    <div class="mt-2">
                        <div class="complexity-indicator bg-gradient-to-r from-green-400 to-blue-500" style="width: 98%"></div>
                    </div>
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
                    4 простых шага к точному диагнозу и профессиональному ремонту
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Step 1 -->
                <div class="diagnostic-card text-center">
                    <div class="step-indicator bg-gradient-to-r from-blue-500 to-cyan-500 mb-6 mx-auto">
                        1
                    </div>
                    <div class="text-4xl text-blue-500 mb-4">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Опишите симптомы</h3>
                    <p class="text-gray-600">
                        Выберите симптомы из списка или опишите проблему своими словами
                    </p>
                </div>
                
                <!-- Step 2 -->
                <div class="diagnostic-card text-center">
                    <div class="step-indicator bg-gradient-to-r from-blue-500 to-cyan-500 mb-6 mx-auto">
                        2
                    </div>
                    <div class="text-4xl text-blue-500 mb-4">
                        <i class="fas fa-car"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Укажите авто</h3>
                    <p class="text-gray-600">
                        Выберите модель, год и двигатель вашего Land Rover
                    </p>
                </div>
                
                <!-- Step 3 -->
                <div class="diagnostic-card text-center">
                    <div class="step-indicator bg-gradient-to-r from-blue-500 to-cyan-500 mb-6 mx-auto">
                        3
                    </div>
                    <div class="text-4xl text-blue-500 mb-4">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Анализ ИИ</h3>
                    <p class="text-gray-600">
                        Наша система анализирует 1500+ кейсов и выдает вероятные причины
                    </p>
                </div>
                
                <!-- Step 4 -->
                <div class="diagnostic-card text-center">
                    <div class="step-indicator bg-gradient-to-r from-blue-500 to-cyan-500 mb-6 mx-auto">
                        4
                    </div>
                    <div class="text-4xl text-blue-500 mb-4">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Консультация</h3>
                    <p class="text-gray-600">
                        Получите консультацию эксперта и рекомендации по ремонту
                    </p>
                </div>
            </div>
            
            <div class="text-center mt-12">
                <button onclick="startDiagnostic()" class="btn-primary text-lg py-4 px-12">
                    <i class="fas fa-rocket mr-2"></i> Начать бесплатную диагностику
                </button>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Почему выбирают нас</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Уникальные возможности, которых нет у обычных сервисов
                </p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <div>
                    <div class="diagnostic-card mb-8">
                        <div class="flex items-start mb-4">
                            <div class="bg-gradient-to-r from-blue-100 to-cyan-100 p-4 rounded-xl mr-4">
                                <i class="fas fa-robot text-blue-600 text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800 mb-2">ИИ-анализ сложных случаев</h3>
                                <p class="text-gray-600">
                                    Наша система обучена на 8500+ реальных кейсах диагностики Land Rover. 
                                    Определяем даже самые редкие неисправности.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="diagnostic-card mb-8">
                        <div class="flex items-start mb-4">
                            <div class="bg-gradient-to-r from-green-100 to-teal-100 p-4 rounded-xl mr-4">
                                <i class="fas fa-video text-green-600 text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800 mb-2">Дистанционная диагностика</h3>
                                <p class="text-gray-600">
                                    Не нужно ехать в сервис. Загрузите логи сканера, фото и видео — 
                                    мы поставим диагноз удаленно.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="diagnostic-card">
                        <div class="flex items-start mb-4">
                            <div class="bg-gradient-to-r from-purple-100 to-pink-100 p-4 rounded-xl mr-4">
                                <i class="fas fa-graduation-cap text-purple-600 text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800 mb-2">Эксперты с сертификацией</h3>
                                <p class="text-gray-600">
                                    Работаем только с дипломированными специалистами, 
                                    прошедшими обучение у производителя.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="diagnostic-card mb-8">
                        <div class="flex items-start mb-4">
                            <div class="bg-gradient-to-r from-orange-100 to-red-100 p-4 rounded-xl mr-4">
                                <i class="fas fa-shield-alt text-orange-600 text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800 mb-2">Гарантия точности</h3>
                                <p class="text-gray-600">
                                    Если наш диагноз окажется неверным — вернем 100% стоимости 
                                    консультации.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="diagnostic-card mb-8">
                        <div class="flex items-start mb-4">
                            <div class="bg-gradient-to-r from-yellow-100 to-amber-100 p-4 rounded-xl mr-4">
                                <i class="fas fa-bolt text-yellow-600 text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800 mb-2">Экономия времени и денег</h3>
                                <p class="text-gray-600">
                                    Средняя экономия наших клиентов — 45 000 рублей за счет 
                                    точного диагноза с первого раза.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="diagnostic-card">
                        <div class="flex items-start mb-4">
                            <div class="bg-gradient-to-r from-indigo-100 to-blue-100 p-4 rounded-xl mr-4">
                                <i class="fas fa-network-wired text-indigo-600 text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800 mb-2">Сеть проверенных сервисов</h3>
                                <p class="text-gray-600">
                                    Порекомендуем лучший сервис для ремонта в вашем городе 
                                    с гарантией качества работ.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 hero-gradient text-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-4xl font-bold mb-6">Готовы решить проблему с автомобилем?</h2>
            <p class="text-xl mb-8 max-w-2xl mx-auto opacity-90">
                Начните диагностику прямо сейчас. Первый анализ симптомов — бесплатно.
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button onclick="startDiagnostic()" class="bg-white text-blue-600 font-bold py-4 px-12 rounded-xl shadow-2xl hover:shadow-3xl transition-all duration-300 transform hover:-translate-y-1 text-lg">
                    <i class="fas fa-play mr-2"></i> Бесплатная диагностика
                </button>
                <a href="#pricing" class="glass-effect font-bold py-4 px-12 rounded-xl hover:bg-white/20 transition-all duration-300 text-lg">
                    <i class="fas fa-crown mr-2"></i> Выбрать тариф
                </a>
            </div>
            
            <p class="mt-8 opacity-80">
                <i class="fas fa-lock mr-2"></i> Безопасная оплата · Конфиденциальность · Гарантия
            </p>
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
                            <p class="text-gray-400 text-sm">Экспертная диагностика Land Rover</p>
                        </div>
                    </div>
                    <p class="text-gray-400 mb-6">
                        Решаем сложные случаи диагностики Land Rover с 2018 года. 
                        Более 1800 успешных диагностик.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="bg-gray-800 p-3 rounded-lg hover:bg-blue-600 transition-colors">
                            <i class="fab fa-telegram"></i>
                        </a>
                        <a href="#" class="bg-gray-800 p-3 rounded-lg hover:bg-blue-600 transition-colors">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <a href="#" class="bg-gray-800 p-3 rounded-lg hover:bg-blue-600 transition-colors">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-bold mb-6">Услуги</h3>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Экспресс-диагностика</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Консультации экспертов</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Дистанционная помощь</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Аудит ремонта</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-bold mb-6">Для сервисов</h3>
                    <ul class="space-y-3">
                        <li><a href="{{ route('services.landing') }}" class="text-gray-400 hover:text-white transition-colors">Партнерская программа</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Обучение специалистов</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">SaaS для сервисов</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">База знаний</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-bold mb-6">Контакты</h3>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <i class="fas fa-phone text-blue-400 mr-3"></i>
                            <span>+7 (999) 123-45-67</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-envelope text-blue-400 mr-3"></i>
                            <span>support@lrdiagnostic.ru</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-clock text-blue-400 mr-3"></i>
                            <span>Поддержка 24/7</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-12 pt-8 text-center text-gray-400">
                <p>© 2024 LR Diagnostic Flow. Все права защищены.</p>
                <p class="mt-2 text-sm">Создано с <i class="fas fa-heart text-red-500"></i> для владельцев Land Rover</p>
            </div>
        </div>
    </footer>

    <script>
        function startDiagnostic() {
            window.location.href = "{{ route('diagnostic.start') }}";
        }
        
        // Анимация при скролле
        document.addEventListener('DOMContentLoaded', function() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate__animated', 'animate__fadeInUp');
                    }
                });
            }, { threshold: 0.1 });
            
            document.querySelectorAll('.diagnostic-card').forEach(card => {
                observer.observe(card);
            });
        });
    </script>
    
    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
</body>
</html>