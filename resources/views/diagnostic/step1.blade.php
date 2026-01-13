@extends('layouts.diagnostic')

@section('title', ' - Шаг 1: Выбор симптомов')

@section('content')
@php
    $showProgress = true;
    $currentStep = 1;
@endphp

<div class="max-w-6xl mx-auto">
    <!-- Заголовок -->
    <div class="text-center mb-10">
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Опишите проблему с вашим Land Rover</h1>
        <p class="text-gray-600 text-lg">
            Выберите симптомы, которые наблюдаете. Это поможет системе предложить точный план диагностики.
        </p>
    </div>

    <form action="{{ route('diagnostic.step2') }}" method="POST" id="diagnosticForm">
        @csrf

        <!-- Выбор симптомов -->
        <div class="diagnostic-card p-6 mb-8">
            <div class="flex items-center mb-6">
                <div class="bg-blue-100 p-3 rounded-lg mr-4">
                    <i class="fas fa-exclamation-triangle text-blue-600 text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Какие симптомы наблюдаете?</h2>
                    <p class="text-gray-600">Выберите один или несколько пунктов</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" x-data="{ selectedSymptoms: [] }">
                @foreach($symptoms as $symptom)
                    <div class="symptom-card"
                         :class="{ 'selected': selectedSymptoms.includes({{ $symptom->id }}) }"
                         @click="
                             if (selectedSymptoms.includes({{ $symptom->id }})) {
                                 selectedSymptoms = selectedSymptoms.filter(id => id !== {{ $symptom->id }});
                             } else {
                                 selectedSymptoms.push({{ $symptom->id }});
                             }
                         ">
                        <div class="flex items-start">
                            <div class="mr-3 mt-1">
                                <i class="fas fa-{{ $symptom->frequency > 100 ? 'fire text-orange-500' : 'info-circle text-blue-500' }}"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-800 mb-1">{{ $symptom->name }}</h3>
                                <p class="text-sm text-gray-600 mb-2">{{ $symptom->description }}</p>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($symptom->related_systems ?? [] as $system)
                                        <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded">
                                            {{ $system }}
                                        </span>
                                    @endforeach
                                </div>
                                @if($symptom->frequency > 50)
                                    <div class="mt-2 text-xs text-gray-500">
                                        <i class="fas fa-chart-line mr-1"></i>
                                        Частая проблема
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Скрытое поле для симптомов -->
            <input type="hidden" name="symptoms[]" x-model="selectedSymptoms">
            
            <!-- Счетчик выбранных симптомов -->
            <div class="mt-4 text-sm text-gray-600" x-show="selectedSymptoms.length > 0" x-cloak>
                <i class="fas fa-check-circle text-green-500 mr-1"></i>
                Выбрано симптомов: <span x-text="selectedSymptoms.length" class="font-bold"></span>
            </div>
        </div>

        <!-- Выбор автомобиля -->
        <div class="diagnostic-card p-6 mb-8">
            <div class="flex items-center mb-6">
                <div class="bg-blue-100 p-3 rounded-lg mr-4">
                    <i class="fas fa-car text-blue-600 text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Ваш Land Rover</h2>
                    <p class="text-gray-600">Укажите модель для более точной диагностики</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Бренд -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">
                        <i class="fas fa-tag mr-1"></i> Марка автомобиля
                    </label>
                    <select name="brand_id" 
                            x-model="selectedBrand" 
                            @change="fetchModels"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="">Выберите марку</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Модель -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">
                        <i class="fas fa-car mr-1"></i> Модель
                    </label>
                    <select name="model_id" 
                            id="modelSelect"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                            :disabled="!selectedBrand">
                        <option value="">Сначала выберите марку</option>
                        <!-- Модели будут загружены через AJAX -->
                    </select>
                </div>
            </div>

            <div class="mt-4 text-sm text-gray-500">
                <i class="fas fa-info-circle mr-1"></i>
                Выбор конкретной модели поможет системе учесть типичные проблемы для вашего авто.
            </div>
        </div>

        <!-- Дополнительное описание -->
        <div class="diagnostic-card p-6 mb-8">
            <div class="flex items-center mb-6">
                <div class="bg-blue-100 p-3 rounded-lg mr-4">
                    <i class="fas fa-comment-alt text-blue-600 text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Дополнительное описание</h2>
                    <p class="text-gray-600">Расскажите подробнее о проблеме (необязательно)</p>
                </div>
            </div>

            <textarea name="description" 
                      rows="4"
                      placeholder="Например: Проблема проявляется при холодном пуске, после прогрева проходит. Машина начала глохнуть на ходу..."
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"></textarea>
        </div>

        <!-- Кнопки -->
        <div class="flex justify-between items-center mt-10">
            <div>
                <span class="text-gray-600 text-sm">
                    <i class="fas fa-shield-alt mr-1"></i>
                    Ваши данные защищены
                </span>
            </div>
            
            <div class="flex space-x-4">
                <button type="button" onclick="window.history.back()" class="btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i> Назад
                </button>
                
                <button type="submit" 
                        class="btn-primary"
                        :disabled="!selectedBrand || selectedSymptoms.length === 0"
                        :class="{ 'opacity-50 cursor-not-allowed': !selectedBrand || selectedSymptoms.length === 0 }">
                    Продолжить <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('diagnosticForm', () => ({
        selectedBrand: '',
        selectedSymptoms: [],
        
        async fetchModels() {
            if (!this.selectedBrand) {
                document.getElementById('modelSelect').innerHTML = '<option value="">Сначала выберите марку</option>';
                return;
            }
            
            try {
                const response = await fetch(`{{ route('diagnostic.models', '') }}/${this.selectedBrand}`);
                const models = await response.json();
                
                let options = '<option value="">Выберите модель</option>';
                models.forEach(model => {
                    options += `<option value="${model.id}">${model.name}</option>`;
                });
                
                document.getElementById('modelSelect').innerHTML = options;
            } catch (error) {
                console.error('Ошибка загрузки моделей:', error);
            }
        },
        
        init() {
            // Инициализация выбранных симптомов из формы
            const form = document.getElementById('diagnosticForm');
            const symptomInputs = form.querySelectorAll('input[name="symptoms[]"]');
            symptomInputs.forEach(input => {
                if (input.value) {
                    this.selectedSymptoms.push(parseInt(input.value));
                }
            });
        }
    }));
});

// Обработка выбора симптомов
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('diagnosticForm');
    const symptomsContainer = form.querySelector('.grid');
    
    symptomsContainer.addEventListener('click', function(e) {
        const symptomCard = e.target.closest('.symptom-card');
        if (!symptomCard) return;
        
        const symptomId = symptomCard.dataset.id;
        const hiddenInput = form.querySelector(`input[name="symptoms[]"][value="${symptomId}"]`);
        
        if (symptomCard.classList.contains('selected')) {
            if (hiddenInput) hiddenInput.remove();
        } else {
            const newInput = document.createElement('input');
            newInput.type = 'hidden';
            newInput.name = 'symptoms[]';
            newInput.value = symptomId;
            form.appendChild(newInput);
        }
    });
});
</script>
@endpush
@endsection