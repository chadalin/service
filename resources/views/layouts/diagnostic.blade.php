<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>LR Diagnostic Flow @yield('title')</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Alpine.js для интерактивности -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        * { font-family: 'Inter', sans-serif; }
        
        .btn-primary {
            @apply bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-3 px-6 rounded-lg shadow-md hover:shadow-lg transition-all duration-300;
        }
        
        .btn-secondary {
            @apply bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white font-semibold py-3 px-6 rounded-lg shadow hover:shadow-md transition-all duration-300;
        }
        
        .symptom-card {
            @apply border-2 border-gray-200 hover:border-blue-400 rounded-xl p-4 cursor-pointer transition-all duration-300 bg-white hover:bg-blue-50;
        }
        
        .symptom-card.selected {
            @apply border-blue-500 bg-blue-50 ring-2 ring-blue-200;
        }
        
        .progress-bar {
            @apply h-2 bg-gradient-to-r from-blue-500 to-green-500 rounded-full transition-all duration-500;
        }
        
        .step-indicator {
            @apply w-8 h-8 rounded-full flex items-center justify-center text-white font-bold;
        }
        
        .step-indicator.active {
            @apply bg-blue-600;
        }
        
        .step-indicator.completed {
            @apply bg-green-600;
        }
        
        .step-indicator.pending {
            @apply bg-gray-300;
        }
        
        .file-upload-area {
            @apply border-2 border-dashed border-gray-300 hover:border-blue-400 rounded-xl p-8 text-center transition-all duration-300;
        }
        
        .diagnostic-card {
            @apply bg-gradient-to-br from-white to-gray-50 border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-all duration-300;
        }
        
        .complexity-badge {
            @apply px-3 py-1 rounded-full text-xs font-bold;
        }
        
        .complexity-1 { @apply bg-green-100 text-green-800; }
        .complexity-2 { @apply bg-green-100 text-green-800; }
        .complexity-3 { @apply bg-yellow-100 text-yellow-800; }
        .complexity-4 { @apply bg-yellow-100 text-yellow-800; }
        .complexity-5 { @apply bg-orange-100 text-orange-800; }
        .complexity-6 { @apply bg-orange-100 text-orange-800; }
        .complexity-7 { @apply bg-red-100 text-red-800; }
        .complexity-8 { @apply bg-red-100 text-red-800; }
        .complexity-9 { @apply bg-red-100 text-red-800; }
        .complexity-10 { @apply bg-red-100 text-red-800; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <div class="bg-gradient-to-r from-blue-600 to-blue-800 p-2 rounded-lg">
                        <i class="fas fa-car text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">LR Diagnostic Flow</h1>
                        <p class="text-sm text-gray-600">Экспертная диагностика Land Rover</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    @auth
                        <a href="{{ route('admin.dashboard') }}" class="text-gray-600 hover:text-blue-600">
                            <i class="fas fa-tachometer-alt mr-1"></i> Панель
                        </a>
                        <a href="{{ route('diagnostic.start') }}" class="text-gray-600 hover:text-blue-600">
                            <i class="fas fa-stethoscope mr-1"></i> Диагностика
                        </a>
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-gray-600 hover:text-red-600">
                                <i class="fas fa-sign-out-alt mr-1"></i> Выйти
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                            <i class="fas fa-sign-in-alt mr-1"></i> Вход
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Progress Bar -->
    @if(isset($showProgress) && $showProgress)
    <div class="container mx-auto px-4 mt-4">
        <div class="mb-8">
            <div class="flex justify-between items-center mb-2">
                @php
                    $steps = [
                        1 => ['title' => 'Симптомы', 'route' => 'diagnostic.start'],
                        2 => ['title' => 'Автомобиль', 'route' => 'diagnostic.step2'],
                        3 => ['title' => 'Данные', 'route' => 'diagnostic.step3'],
                        4 => ['title' => 'Результат', 'route' => 'diagnostic.result'],
                    ];
                    $currentStep = $currentStep ?? 1;
                @endphp
                
                @foreach($steps as $stepNumber => $step)
                    <div class="flex flex-col items-center">
                        <div class="step-indicator 
                            {{ $stepNumber < $currentStep ? 'completed' : '' }}
                            {{ $stepNumber == $currentStep ? 'active' : '' }}
                            {{ $stepNumber > $currentStep ? 'pending' : '' }}">
                            @if($stepNumber < $currentStep)
                                <i class="fas fa-check text-xs"></i>
                            @else
                                {{ $stepNumber }}
                            @endif
                        </div>
                        <span class="mt-2 text-sm font-medium 
                            {{ $stepNumber <= $currentStep ? 'text-gray-800' : 'text-gray-400' }}">
                            {{ $step['title'] }}
                        </span>
                    </div>
                @endforeach
            </div>
            
            <div class="bg-gray-200 rounded-full h-2">
                <div class="progress-bar" style="width: {{ ($currentStep - 1) * 33.33 }}%"></div>
            </div>
        </div>
    </div>
    @endif

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
                        <li><a href="{{ route('home') }}" class="hover:text-blue-300">Дистанционная диагностика</a></li>
                        <li><a href="#" class="hover:text-blue-300">Консультации экспертов</a></li>
                        <li><a href="{{ route('services.landing') }}" class="hover:text-blue-300">Для сервисов (B2B)</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold mb-4">Поддержка</h4>
                    <ul class="space-y-2 text-sm text-gray-300">
                        <li><a href="#" class="hover:text-blue-300">FAQ</a></li>
                        <li><a href="#" class="hover:text-blue-300">Как это работает</a></li>
                        <li><a href="#" class="hover:text-blue-300">Контакты</a></li>
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