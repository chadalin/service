@extends('layouts.diagnostic')

@section('title', ' - Шаг 1: Выбор симптомов')

@section('content')
@php
    $showProgress = true;
    $currentStep = 1;
@endphp

<div class="max-w-4xl mx-auto">
    <!-- Заголовок -->
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Опишите проблему</h1>
        <p class="text-gray-600">Выберите симптомы и укажите данные автомобиля</p>
    </div>

    <form action="{{ route('diagnostic.step2') }}" method="POST" id="diagnosticForm" x-data="diagnosticForm()">
        @csrf

        <!-- Выбор симптомов -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4">
            <div class="flex items-center mb-4">
                <div class="bg-blue-100 p-2 rounded-lg mr-3">
                    <i class="fas fa-exclamation-triangle text-blue-600"></i>
                </div>
                <div>
                    <h2 class="font-bold text-gray-800">Симптомы проблемы</h2>
                    <p class="text-sm text-gray-600">Выберите наблюдаемые симптомы</p>
                </div>
            </div>

            <!-- Выбранные симптомы (чипы) -->
            <div class="mb-4" x-show="selectedSymptoms.length > 0" x-cloak>
                <div class="flex flex-wrap gap-2 mb-2">
                    <template x-for="symptomId in selectedSymptoms" :key="symptomId">
                        <div class="flex items-center bg-blue-100 text-blue-800 px-3 py-1.5 rounded-full text-sm">
                            <span x-text="getSymptomName(symptomId)"></span>
                            <button type="button" 
                                    @click="removeSymptom(symptomId)"
                                    class="ml-2 text-blue-600 hover:text-blue-800">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </div>
                    </template>
                </div>
                <div class="text-sm text-green-600">
                    <i class="fas fa-check-circle mr-1"></i>
                    Выбрано: <span x-text="selectedSymptoms.length" class="font-bold"></span>
                </div>
            </div>

            <!-- Поиск симптомов -->
            <div class="mb-4">
                <input type="text" 
                       x-model="searchQuery"
                       @input="filterSymptoms"
                       placeholder="Поиск симптомов..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Список симптомов -->
            <div class="space-y-2 max-h-60 overflow-y-auto pr-2">
                @foreach($symptoms as $symptom)
                    <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer"
                         :class="{ 'bg-blue-50 border-blue-300': selectedSymptoms.includes({{ $symptom->id }}) }"
                         @click="toggleSymptom({{ $symptom->id }})">
                        <div class="flex items-center">
                            <div class="mr-3">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                                     :class="selectedSymptoms.includes({{ $symptom->id }}) ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-500'">
                                    <i class="fas" :class="selectedSymptoms.includes({{ $symptom->id }}) ? 'fa-check' : 'fa-plus'"></i>
                                </div>
                            </div>
                            <div>
                                <h3 class="font-medium text-gray-800 text-sm">{{ $symptom->name }}</h3>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $symptom->description }}</p>
                            </div>
                        </div>
                        <div class="text-xs text-gray-500">
                            @if($symptom->frequency > 50)
                                <span class="text-orange-600">
                                    <i class="fas fa-fire mr-1"></i>Частая
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            
            <!-- Сообщение об ошибке -->
            <div class="mt-2 text-sm text-red-600" x-show="showSymptomError" x-cloak>
                <i class="fas fa-exclamation-circle mr-1"></i>
                Пожалуйста, выберите хотя бы один симптом
            </div>
        </div>

        <!-- Выбор автомобиля -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4">
            <div class="flex items-center mb-4">
                <div class="bg-blue-100 p-2 rounded-lg mr-3">
                    <i class="fas fa-car text-blue-600"></i>
                </div>
                <div>
                    <h2 class="font-bold text-gray-800">Ваш автомобиль</h2>
                    <p class="text-sm text-gray-600">Укажите марку и модель</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <!-- Марка -->
                <div>
                    <label class="block text-gray-700 font-medium mb-1 text-sm">
                        Марка автомобиля *
                    </label>
                    <select name="brand_id" 
                            x-model="selectedBrand" 
                            @change="fetchModels"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Выберите марку</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                    <div class="mt-1 text-xs text-red-600" x-show="showBrandError" x-cloak>
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        Выберите марку автомобиля
                    </div>
                </div>

                <!-- Модель -->
                <div>
                    <label class="block text-gray-700 font-medium mb-1 text-sm">
                        Модель
                    </label>
                    <select name="model_id" 
                            id="modelSelect"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            :disabled="!selectedBrand || isLoadingModels">
                        <option value="">Выберите модель</option>
                        <template x-if="isLoadingModels">
                            <option value="" disabled>Загрузка моделей...</option>
                        </template>
                        <template x-for="model in models" :key="model.id">
                            <option :value="model.id" x-text="model.name"></option>
                        </template>
                    </select>
                </div>
            </div>
        </div>

        <!-- Описание проблемы -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
            <div class="flex items-center mb-3">
                <div class="bg-blue-100 p-2 rounded-lg mr-3">
                    <i class="fas fa-comment-alt text-blue-600"></i>
                </div>
                <div>
                    <h2 class="font-bold text-gray-800">Дополнительное описание</h2>
                    <p class="text-sm text-gray-600">Расскажите подробнее (необязательно)</p>
                </div>
            </div>

            <textarea name="description" 
                      rows="3"
                      placeholder="Например: Проблема проявляется при холодном пуске, после прогрева проходит. Машина начала глохнуть на ходу..."
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
        </div>

        <!-- Кнопки -->
        <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
            <div class="order-2 sm:order-1">
                <div class="flex items-center text-gray-600 text-sm">
                    <i class="fas fa-shield-alt mr-2"></i>
                    <span>Данные защищены</span>
                </div>
            </div>
            
            <div class="flex space-x-3 w-full sm:w-auto order-1 sm:order-2">
                <a href="{{ url()->previous() }}" class="btn-secondary py-2.5 px-4 text-sm flex-1 sm:flex-none text-center">
                    <i class="fas fa-arrow-left mr-2"></i> Назад
                </a>
                
                <button type="submit" 
                        class="btn-primary py-2.5 px-4 text-sm flex-1 sm:flex-none"
                        :class="{ 'opacity-50 cursor-not-allowed': !canSubmit }"
                        :disabled="!canSubmit"
                        @click="validateForm">
                    <span x-show="!isLoading">
                        Далее <i class="fas fa-arrow-right ml-2"></i>
                    </span>
                    <span x-show="isLoading" class="flex items-center justify-center" x-cloak>
                        <svg class="animate-spin h-4 w-4 text-white mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Обработка...
                    </span>
                </button>
            </div>
        </div>
        
        <!-- Скрытые поля для симптомов -->
        <template x-for="symptomId in selectedSymptoms" :key="symptomId">
            <input type="hidden" name="symptoms[]" :value="symptomId">
        </template>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('diagnosticForm', () => ({
        // Данные симптомов (собираем из HTML)
        allSymptoms: [],
        
        // Состояние
        selectedSymptoms: [],
        searchQuery: '',
        filteredSymptoms: [],
        selectedBrand: '',
        models: [],
        isLoadingModels: false,
        isLoading: false,
        showSymptomError: false,
        showBrandError: false,
        
        // Вычисляемые свойства
        get canSubmit() {
            return this.selectedSymptoms.length > 0 && this.selectedBrand !== '';
        },
        
        // Инициализация
        init() {
            // Собираем симптомы из HTML
            this.collectSymptoms();
            this.filteredSymptoms = [...this.allSymptoms];
            
            // Восстанавливаем из sessionStorage
            this.restoreFromStorage();
            
            // Наблюдаем за изменениями для сохранения
            this.$watch('selectedSymptoms', () => this.saveToStorage());
            this.$watch('selectedBrand', () => {
                this.saveToStorage();
                if (this.selectedBrand) {
                    this.fetchModels();
                }
            });
        },
        
        // Методы
        collectSymptoms() {
            const symptomElements = document.querySelectorAll('[data-symptom-id]');
            this.allSymptoms = Array.from(symptomElements).map(el => ({
                id: parseInt(el.dataset.symptomId),
                name: el.querySelector('.symptom-name').textContent,
                description: el.querySelector('.symptom-desc').textContent
            }));
        },
        
        toggleSymptom(symptomId) {
            symptomId = parseInt(symptomId);
            const index = this.selectedSymptoms.indexOf(symptomId);
            if (index > -1) {
                this.selectedSymptoms.splice(index, 1);
            } else {
                this.selectedSymptoms.push(symptomId);
            }
            this.showSymptomError = false;
        },
        
        removeSymptom(symptomId) {
            symptomId = parseInt(symptomId);
            this.selectedSymptoms = this.selectedSymptoms.filter(id => id !== symptomId);
        },
        
        getSymptomName(symptomId) {
            const symptom = this.allSymptoms.find(s => s.id === symptomId);
            return symptom ? symptom.name : '';
        },
        
        filterSymptoms() {
            if (!this.searchQuery.trim()) {
                this.filteredSymptoms = [...this.allSymptoms];
                return;
            }
            
            const query = this.searchQuery.toLowerCase();
            this.filteredSymptoms = this.allSymptoms.filter(symptom => 
                symptom.name.toLowerCase().includes(query) ||
                symptom.description.toLowerCase().includes(query)
            );
        },
        
        async fetchModels() {
            if (!this.selectedBrand) {
                this.models = [];
                return;
            }
            
            this.isLoadingModels = true;
            this.models = [];
            
            try {
                const response = await fetch(`/diagnostic/models/${this.selectedBrand}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                if (response.ok) {
                    this.models = await response.json();
                    this.showBrandError = false;
                } else {
                    console.error('Ошибка загрузки моделей');
                    this.models = [];
                }
            } catch (error) {
                console.error('Ошибка:', error);
                this.models = [];
            } finally {
                this.isLoadingModels = false;
            }
        },
        
        validateForm(event) {
            this.showSymptomError = this.selectedSymptoms.length === 0;
            this.showBrandError = !this.selectedBrand;
            
            if (this.showSymptomError || this.showBrandError) {
                event.preventDefault();
                
                // Прокрутка к первой ошибке
                if (this.showSymptomError) {
                    document.querySelector('[x-data="diagnosticForm()"]').scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
                
                return false;
            }
            
            this.isLoading = true;
            return true;
        },
        
        saveToStorage() {
            const data = {
                symptoms: this.selectedSymptoms,
                brand: this.selectedBrand,
                timestamp: Date.now()
            };
            sessionStorage.setItem('diagnostic_form', JSON.stringify(data));
        },
        
        restoreFromStorage() {
            try {
                const saved = sessionStorage.getItem('diagnostic_form');
                if (saved) {
                    const data = JSON.parse(saved);
                    // Восстанавливаем если не старше 1 часа
                    if (Date.now() - data.timestamp < 3600000) {
                        this.selectedSymptoms = data.symptoms || [];
                        this.selectedBrand = data.brand || '';
                        if (this.selectedBrand) {
                            this.fetchModels();
                        }
                    }
                }
            } catch (e) {
                console.error('Ошибка восстановления:', e);
            }
        }
    }));
});

// Очистка storage при успешной отправке
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('diagnosticForm');
    if (form) {
        form.addEventListener('submit', function() {
            // Задержка для гарантии отправки
            setTimeout(() => {
                sessionStorage.removeItem('diagnostic_form');
            }, 1000);
        });
    }
    
    // Адаптивность
    function adjustMobile() {
        if (window.innerWidth < 640) {
            document.querySelectorAll('select, textarea').forEach(el => {
                el.style.fontSize = '16px'; // Предотвращает зум в iOS
            });
        }
    }
    
    adjustMobile();
    window.addEventListener('resize', adjustMobile);
});
</script>

<style>
/* Компактные стили */
@media (max-width: 640px) {
    .btn-primary, .btn-secondary {
        padding: 10px 16px;
        font-size: 14px;
    }
    
    .max-h-60 {
        max-height: 200px;
    }
}

/* Кастомный скроллбар */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}

/* Анимации */
@keyframes slideIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.bg-white {
    animation: slideIn 0.3s ease-out;
}

/* Скрытие Alpine до инициализации */
[x-cloak] { display: none !important; }
</style>
@endpush
@endsection