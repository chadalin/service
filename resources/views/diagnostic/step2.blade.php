@extends('layouts.diagnostic')

@section('title', ' - Шаг 2: Данные автомобиля')

@section('content')
@php
    $showProgress = true;
    $currentStep = 2;
@endphp

<div class="max-w-4xl mx-auto">
    <!-- Заголовок -->
    <div class="text-center mb-10">
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Расскажите о вашем автомобиле</h1>
        <p class="text-gray-600 text-lg">
            Чем больше информации вы предоставите, тем точнее будет диагностика.
        </p>
    </div>

    <form action="{{ route('diagnostic.step3') }}" method="POST" id="carDetailsForm">
        @csrf

        <!-- Основные данные -->
        <div class="diagnostic-card p-6 mb-8">
            <div class="flex items-center mb-6">
                <div class="bg-blue-100 p-3 rounded-lg mr-4">
                    <i class="fas fa-cogs text-blue-600 text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Технические характеристики</h2>
                    <p class="text-gray-600">Основные параметры автомобиля</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Год выпуска -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">
                        <i class="fas fa-calendar mr-1"></i> Год выпуска *
                    </label>
                    <select name="year" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="">Выберите год</option>
                        @for($year = date('Y'); $year >= 1990; $year--)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endfor
                    </select>
                </div>

                <!-- Тип двигателя -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">
                        <i class="fas fa-gas-pump mr-1"></i> Тип двигателя *
                    </label>
                    <select name="engine_type" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="">Выберите тип</option>
                        <option value="Дизель">Дизель</option>
                        <option value="Бензин">Бензин</option>
                        <option value="Гибрид">Гибрид</option>
                        <option value="Электрический">Электрический</option>
                    </select>
                </div>

                <!-- Пробег -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">
                        <i class="fas fa-tachometer-alt mr-1"></i> Пробег (км)
                    </label>
                    <input type="number" 
                           name="mileage" 
                           placeholder="Например: 125000"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    <p class="text-xs text-gray-500 mt-1">Укажите текущий пробег автомобиля</p>
                </div>

                <!-- VIN номер -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">
                        <i class="fas fa-fingerprint mr-1"></i> VIN номер
                    </label>
                    <input type="text" 
                           name="vin" 
                           placeholder="WVWZZZ1JZ3W386752"
                           pattern="[A-HJ-NPR-Z0-9]{17}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    <p class="text-xs text-gray-500 mt-1">17-значный идентификационный номер</p>
                </div>
            </div>
        </div>

        <!-- История обслуживания -->
        <div class="diagnostic-card p-6 mb-8">
            <div class="flex items-center mb-6">
                <div class="bg-blue-100 p-3 rounded-lg mr-4">
                    <i class="fas fa-history text-blue-600 text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">История обслуживания</h2>
                    <p class="text-gray-600">Недавние работы и замены (необязательно)</p>
                </div>
            </div>

            <div class="space-y-4" x-data="{ maintenanceItems: [] }">
                <!-- Список работ -->
                <template x-for="(item, index) in maintenanceItems" :key="index">
                    <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
                        <div class="flex-1 grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Вид работ</label>
                                <input type="text" 
                                       x-model="item.work"
                                       placeholder="Например: Замена масла"
                                       class="w-full px-3 py-2 border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Когда выполнено</label>
                                <input type="text" 
                                       x-model="item.date"
                                       placeholder="Например: 3 месяца назад"
                                       class="w-full px-3 py-2 border border-gray-300 rounded">
                            </div>
                        </div>
                        <button type="button" 
                                @click="maintenanceItems.splice(index, 1)"
                                class="text-red-500 hover:text-red-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </template>

                <!-- Кнопка добавить -->
                <button type="button"
                        @click="maintenanceItems.push({ work: '', date: '' })"
                        class="flex items-center text-blue-600 hover:text-blue-800">
                    <i class="fas fa-plus-circle mr-2"></i>
                    Добавить работу по обслуживанию
                </button>

                <input type="hidden" name="maintenance_history" x-model="JSON.stringify(maintenanceItems)">
            </div>
        </div>

        <!-- Информационный блок -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-8">
            <div class="flex items-start">
                <div class="mr-4">
                    <i class="fas fa-lightbulb text-blue-600 text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-blue-800 mb-2">Как это поможет диагностике?</h3>
                    <ul class="space-y-2 text-blue-700">
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                            <span><strong>Год выпуска:</strong> помогает определить типичные проблемы для конкретного поколения</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                            <span><strong>Тип двигателя:</strong> дизельные и бензиновые моторы имеют разные "болезни"</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                            <span><strong>Пробег:</strong> указывает на износ характерных для пробега узлов</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                            <span><strong>История обслуживания:</strong> исключает уже решённые проблемы</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Кнопки -->
        <div class="flex justify-between items-center mt-10">
            <div>
                <a href="{{ route('diagnostic.start') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                    <i class="fas fa-arrow-left mr-1"></i> Вернуться к симптомам
                </a>
            </div>
            
            <div class="flex space-x-4">
                <button type="button" onclick="window.history.back()" class="btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i> Назад
                </button>
                
                <button type="submit" class="btn-primary">
                    Продолжить <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('carDetailsForm');
    
    // Валидация VIN
    const vinInput = form.querySelector('input[name="vin"]');
    if (vinInput) {
        vinInput.addEventListener('blur', function() {
            const vin = this.value.trim().toUpperCase();
            if (vin.length === 17) {
                // Базовая проверка VIN
                const vinRegex = /^[A-HJ-NPR-Z0-9]{17}$/;
                if (vinRegex.test(vin)) {
                    this.classList.add('border-green-500');
                    this.classList.remove('border-red-500');
                } else {
                    this.classList.add('border-red-500');
                    this.classList.remove('border-green-500');
                }
            }
        });
    }
    
    // Автозаполнение года по VIN (10-я позиция)
    if (vinInput) {
        vinInput.addEventListener('change', function() {
            const vin = this.value.trim().toUpperCase();
            if (vin.length >= 10) {
                const yearChar = vin[9];
                const yearMap = {
                    'A': 2010, 'B': 2011, 'C': 2012, 'D': 2013, 'E': 2014,
                    'F': 2015, 'G': 2016, 'H': 2017, 'J': 2018, 'K': 2019,
                    'L': 2020, 'M': 2021, 'N': 2022, 'P': 2023, 'R': 2024,
                    'S': 1995, 'T': 1996, 'V': 1997, 'W': 1998, 'X': 1999,
                    'Y': 2000, '1': 2001, '2': 2002, '3': 2003, '4': 2004,
                    '5': 2005, '6': 2006, '7': 2007, '8': 2008, '9': 2009
                };
                
                if (yearMap[yearChar]) {
                    const yearSelect = form.querySelector('select[name="year"]');
                    if (yearSelect) {
                        yearSelect.value = yearMap[yearChar];
                    }
                }
            }
        });
    }
});
</script>
@endpush
@endsection