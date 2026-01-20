<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>LR Diagnostic Flow - Консультации @yield('title')</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Alpine.js для интерактивности -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Chart.js для графиков -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        * { font-family: 'Inter', sans-serif; }
        
        .btn-primary {
            @apply bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-3 px-6 rounded-lg shadow-md hover:shadow-lg transition-all duration-300;
        }
        
        .btn-secondary {
            @apply bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white font-semibold py-3 px-6 rounded-lg shadow hover:shadow-md transition-all duration-300;
        }
        
        .status-badge {
            @apply px-3 py-1 rounded-full text-xs font-bold uppercase;
        }
        
        .status-pending { @apply bg-yellow-100 text-yellow-800; }
        .status-scheduled { @apply bg-blue-100 text-blue-800; }
        .status-in_progress { @apply bg-purple-100 text-purple-800; }
        .status-completed { @apply bg-green-100 text-green-800; }
        .status-cancelled { @apply bg-red-100 text-red-800; }
        
        .type-badge {
            @apply px-3 py-1 rounded-full text-xs font-bold;
        }
        
        .type-basic { @apply bg-gray-100 text-gray-800; }
        .type-premium { @apply bg-blue-100 text-blue-800; }
        .type-expert { @apply bg-purple-100 text-purple-800; }
        
        .message-bubble {
            @apply max-w-xs lg:max-w-md rounded-lg p-3;
        }
        
        .message-sent {
            @apply bg-blue-100 text-blue-800 ml-auto;
        }
        
        .message-received {
            @apply bg-gray-100 text-gray-800 mr-auto;
        }
        
        .consultation-card {
            @apply bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-all duration-300;
        }
        
        .tab-active {
            @apply border-b-2 border-blue-600 text-blue-600 font-semibold;
        }
        
        .tab-inactive {
            @apply text-gray-500 hover:text-gray-700;
        }
        
        .chat-container {
            height: calc(100vh - 300px);
        }
        
        @media (max-width: 768px) {
            .chat-container {
                height: calc(100vh - 250px);
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <a href="{{ route('home') }}" class="flex items-center space-x-3">
                        <div class="bg-gradient-to-r from-blue-600 to-blue-800 p-2 rounded-lg">
                            <i class="fas fa-car text-white text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">LR Diagnostic Flow</h1>
                            <p class="text-sm text-gray-600">Экспертная диагностика Land Rover</p>
                        </div>
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    @auth
                        <!-- Навигация в зависимости от роли -->
                        @if(auth()->user()->is_expert)
                            <!-- Для экспертов -->
                            <div class="flex items-center space-x-4">
                                <div class="relative group">
                                    <button class="flex items-center space-x-2 text-gray-600 hover:text-blue-600">
                                        <i class="fas fa-user-tie"></i>
                                        <span>Эксперт</span>
                                        <i class="fas fa-chevron-down text-xs"></i>
                                    </button>
                                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 hidden group-hover:block z-50">
                                        <a href="{{ route('diagnostic.consultation.expert.dashboard') }}" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                                            <i class="fas fa-tachometer-alt mr-2"></i> Дашборд
                                        </a>
                                        <a href="{{ route('expert.profile.edit') }}" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                                            <i class="fas fa-user-circle mr-2"></i> Профиль
                                        </a>
                                        <a href="{{ route('expert.schedule.index') }}" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                                            <i class="fas fa-calendar-alt mr-2"></i> Расписание
                                        </a>
                                        <div class="border-t border-gray-100">
                                            <form action="{{ route('logout') }}" method="POST">
                                                @csrf
                                                <button type="submit" class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-red-50 hover:text-red-600">
                                                    <i class="fas fa-sign-out-alt mr-2"></i> Выйти
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- Для клиентов -->
                            <div class="flex items-center space-x-4">
                                <a href="{{ route('admin.dashboard') }}" class="text-gray-600 hover:text-blue-600">
                                    <i class="fas fa-tachometer-alt mr-1"></i> Панель
                                </a>
                                <a href="{{ route('diagnostic.start') }}" class="text-gray-600 hover:text-blue-600">
                                    <i class="fas fa-stethoscope mr-1"></i> Диагностика
                                </a>
                                <a href="{{ route('diagnostic.consultation.index') }}" class="text-gray-600 hover:text-blue-600 relative">
                                    <i class="fas fa-comments mr-1"></i> Консультации
                                    @php
                                        // Подсчет непрочитанных сообщений
                                        $unreadCount = \App\Models\Diagnostic\Consultation::where('user_id', auth()->id())
                                            ->whereHas('messages', function($q) {
                                                $q->where('user_id', '!=', auth()->id())
                                                  ->whereNull('read_at');
                                            })
                                            ->count();
                                    @endphp
                                    @if($unreadCount > 0)
                                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                                            {{ $unreadCount }}
                                        </span>
                                    @endif
                                </a>
                                <form action="{{ route('logout') }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-gray-600 hover:text-red-600">
                                        <i class="fas fa-sign-out-alt mr-1"></i> Выйти
                                    </button>
                                </form>
                            </div>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                            <i class="fas fa-sign-in-alt mr-1"></i> Вход
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        @if(session('info'))
            <div class="mb-6 bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-info-circle mr-2"></i>
                    <span>{{ session('info') }}</span>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-lg font-bold mb-4">LR Diagnostic Flow</h3>
                    <p class="text-gray-300 text-sm">
                        Экспертная система диагностики Land Rover. 
                        Быстрая диагностика и консультации от специалистов.
                    </p>
                </div>
                
                <div>
                    <h4 class="font-bold mb-4">Услуги</h4>
                    <ul class="space-y-2 text-sm text-gray-300">
                        <li><a href="{{ route('diagnostic.start') }}" class="hover:text-blue-300">Дистанционная диагностика</a></li>
                        <li><a href="{{ route('diagnostic.consultation.index') }}" class="hover:text-blue-300">Консультации экспертов</a></li>
                        <li><a href="{{ route('services.landing') }}" class="hover:text-blue-300">Для сервисов (B2B)</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold mb-4">Поддержка</h4>
                    <ul class="space-y-2 text-sm text-gray-300">
                        <li><a href="#" class="hover:text-blue-300">FAQ</a></li>
                        <li><a href="#" class="hover:text-blue-300">Как это работает</a></li>
                        <li><a href="mailto:support@lrdiagnostic.ru" class="hover:text-blue-300">Контакты</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold mb-4">Контакты</h4>
                    <div class="space-y-2 text-sm text-gray-300">
                        <div class="flex items-center">
                            <i class="fas fa-phone mr-2 text-blue-300"></i>
                            <span>+7 (999) 123-45-67</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-envelope mr-2 text-blue-300"></i>
                            <span>support@lrdiagnostic.ru</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fab fa-telegram mr-2 text-blue-300"></i>
                            <span>@lrdiagnostic_bot</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-8 pt-6 text-center text-gray-400 text-sm">
                <p>© 2024 LR Diagnostic Flow. Все права защищены.</p>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>