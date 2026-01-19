@extends('layouts.diagnostic')

@section('title', ' - Шаг 2: Данные автомобиля')

@section('content')
@php
    $showProgress = true;
    $currentStep = 2;
@endphp

<div class="max-w-4xl mx-auto">
    <!-- Заголовок -->
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Данные автомобиля</h1>
        <p class="text-gray-600">Укажите технические характеристики</p>
    </div>

    <form action="{{ route('diagnostic.step3.process') }}" method="POST" id="carDetailsForm" x-data="{ maintenanceItems: [] }">
        @csrf

        <!-- Основные данные -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4">
            <div class="flex items-center mb-4">
                <div class="bg-blue-100 p-2 rounded-lg mr-3">
                    <i class="fas fa-cogs text-blue-600"></i>
                </div>
                <div>
                    <h2 class="font-bold text-gray-800">Технические характеристики</h2>
                    <p class="text-sm text-gray-600">Основные параметры автомобиля</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <!-- Год выпуска -->
                <div>
                    <label class="block text-gray-700 font-medium mb-1 text-sm">
                        Год выпуска *
                    </label>
                    <select name="year" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Выберите год</option>
                        @for($year = date('Y'); $year >= 1990; $year--)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endfor
                    </select>
                </div>

                <!-- Тип двигателя -->
                <div>
                    <label class="block text-gray-700 font-medium mb-1 text-sm">
                        Тип двигателя *
                    </label>
                    <select name="engine_type" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Выберите тип</option>
                        <option value="Дизель">Дизель</option>
                        <option value="Бензин">Бензин</option>
                        <option value="Гибрид">Гибрид</option>
                        <option value="Электрический">Электрический</option>
                    </select>
                </div>

                <!-- Пробег -->
                <div>
                    <label class="block text-gray-700 font-medium mb-1 text-sm">
                        Пробег (км)
                    </label>
                    <input type="number" 
                           name="mileage" 
                           placeholder="Например: 125000"
                           min="0"
                           max="1000000"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Укажите текущий пробег автомобиля</p>
                </div>

                <!-- VIN номер -->
                <div>
                    <label class="block text-gray-700 font-medium mb-1 text-sm">
                        VIN номер
                    </label>
                    <input type="text" 
                           name="vin" 
                           placeholder="WVWZZZ1JZ3W386752"
                           pattern="[A-HJ-NPR-Z0-9]{17}"
                           maxlength="17"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">17-значный идентификационный номер</p>
                </div>
            </div>
        </div>

        <!-- Информационный блок -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4">
            <div class="flex items-start">
                <div class="mr-3">
                    <i class="fas fa-lightbulb text-blue-600"></i>
                </div>
                <div>
                    <h3 class="font-bold text-blue-800 mb-2 text-sm">Как это поможет диагностике?</h3>
                    <ul class="space-y-1 text-blue-700 text-xs">
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mr-2 mt-0.5"></i>
                            <span><strong>Год выпуска:</strong> помогает определить типичные проблемы для конкретного поколения</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mr-2 mt-0.5"></i>
                            <span><strong>Тип двигателя:</strong> дизельные и бензиновые моторы имеют разные "болезни"</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mr-2 mt-0.5"></i>
                            <span><strong>Пробег:</strong> указывает на износ характерных для пробега узлов</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Кнопки -->
        <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
            <div class="order-2 sm:order-1">
                <a href="{{ route('diagnostic.start') }}" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Вернуться к симптомам
                </a>
            </div>
            
            <div class="flex space-x-3 w-full sm:w-auto order-1 sm:order-2">
                <a href="{{ route('diagnostic.start') }}" class="btn-secondary py-2.5 px-4 text-sm flex-1 sm:flex-none text-center">
                    <i class="fas fa-arrow-left mr-2"></i> Назад
                </a>
                
                <button type="submit" class="btn-primary py-2.5 px-4 text-sm flex-1 sm:flex-none">
                    Далее <i class="fas fa-arrow-right ml-2"></i>
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
    
    // Автозаполнение года по VIN
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