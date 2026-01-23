@extends('layouts.diagnostic')

@section('title', ' - Заказ консультации')

@push('styles')
<style>
    .consultation-summary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .price-badge {
        background: rgba(255, 255, 255, 0.2);
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 8px;
        padding: 0.75rem 1.5rem;
        font-size: 1.5rem;
        font-weight: bold;
    }
    
    .form-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    .required-field::after {
        content: '*';
        color: #ef4444;
        margin-left: 4px;
    }
</style>
@endpush

@section('content')
<div class="max-w-6xl mx-auto">
    <!-- Заголовок -->
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-3">Заказ консультации специалиста</h1>
        <p class="text-gray-600 text-lg">Заполните форму ниже и наш эксперт свяжется с вами в течение 30 минут</p>
    </div>
    
    <!-- Сводка по заказу -->
    <div class="consultation-summary">
        <div class="flex flex-col md:flex-row justify-between items-center">
            <div class="mb-4 md:mb-0">
                <h2 class="text-xl font-bold mb-2">
                    @if($consultationType === 'basic')
                        📋 Базовая консультация
                    @elseif($consultationType === 'premium')
                        ⭐ Премиум консультация
                    @else
                        🎯 Экспертная консультация
                    @endif
                </h2>
                
                @if(isset($symptom_name) || isset($symptom_names))
                    <div class="flex flex-wrap gap-2 mt-2">
                        @if(isset($symptom_name))
                            <span class="bg-white/20 px-3 py-1 rounded-full text-sm">
                                {{ $symptom_name }}
                            </span>
                        @elseif(isset($symptom_names) && count($symptom_names) > 0)
                            @foreach(array_slice($symptom_names, 0, 3) as $name)
                                <span class="bg-white/20 px-3 py-1 rounded-full text-sm">
                                    {{ $name }}
                                </span>
                            @endforeach
                            @if(count($symptom_names) > 3)
                                <span class="bg-white/20 px-3 py-1 rounded-full text-sm">
                                    +{{ count($symptom_names) - 3 }}
                                </span>
                            @endif
                        @endif
                    </div>
                @endif
            </div>
            
            <div class="text-center">
                <div class="text-white/80 mb-1">Стоимость консультации</div>
                <div class="price-badge">
                    @php
                        $price = $consultationType === 'basic' ? 500 : 
                                 ($consultationType === 'premium' ? 1500 : 
                                 (isset($rule) ? $rule->base_consultation_price : 3000));
                    @endphp
                    {{ number_format($price, 0, '', ' ') }} ₽
                </div>
            </div>
        </div>
    </div>
    
    <form action="{{ route('consultation.order') }}" method="POST" id="consultationForm">
        @csrf
        
        <!-- Скрытые поля -->
        <input type="hidden" name="consultation_type" value="{{ $consultationType }}">
        @if(isset($rule))
            <input type="hidden" name="rule_id" value="{{ $rule->id }}">
        @endif
        @if(isset($case))
            <input type="hidden" name="case_id" value="{{ $case->id }}">
        @endif
        @if(isset($symptoms) && count($symptoms) > 0)
            @foreach($symptoms as $symptomId)
                <input type="hidden" name="symptoms[]" value="{{ $symptomId }}">
            @endforeach
        @endif
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Левая колонка: Данные автомобиля -->
            <div>
                <div class="form-card">
                    <h3 class="text-xl font-bold text-gray-800 mb-6 pb-3 border-b">
                        <i class="fas fa-car mr-2 text-blue-600"></i>Данные автомобиля
                    </h3>
                    
                    <!-- Марка -->
                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-2 required-field">Марка автомобиля</label>
                        <select name="brand_id" 
                                id="brandSelect"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            <option value="">-- Выберите марку --</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" 
                                        {{ (isset($brand_id) && $brand_id == $brand->id) ? 'selected' : '' }}>
                                    {{ $brand->name }}
                                </option>
                            @endforeach
                        </select>
                        <div id="brandError" class="text-red-600 text-sm mt-1 hidden"></div>
                    </div>
                    
                    <!-- Модель -->
                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-2">Модель (необязательно)</label>
                        <select name="model_id" 
                                id="modelSelect"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                {{ !isset($brand_id) ? 'disabled' : '' }}>
                            <option value="">-- Выберите модель --</option>
                            @if(isset($models) && $models->count() > 0)
                                @foreach($models as $model)
                                    <option value="{{ $model->id }}"
                                            {{ (isset($model_id) && $model_id == $model->id) ? 'selected' : '' }}>
                                        {{ $model->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    
                    <!-- Год выпуска -->
                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-2">Год выпуска</label>
                        <input type="number" 
                               name="year" 
                               value="{{ $year ?? '' }}"
                               min="1990" 
                               max="{{ date('Y') }}"
                               placeholder="Например: 2018"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>
                    
                    <!-- Пробег -->
                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-2">Пробег (км)</label>
                        <input type="number" 
                               name="mileage" 
                               value="{{ $mileage ?? '' }}"
                               min="0" 
                               max="1000000"
                               placeholder="Например: 125000"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>
                    
                    <!-- Тип двигателя -->
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Тип двигателя</label>
                        <select name="engine_type" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            <option value="">-- Выберите тип --</option>
                            <option value="Бензин" {{ (isset($engine_type) && $engine_type == 'Бензин') ? 'selected' : '' }}>Бензин</option>
                            <option value="Дизель" {{ (isset($engine_type) && $engine_type == 'Дизель') ? 'selected' : '' }}>Дизель</option>
                            <option value="Гибрид" {{ (isset($engine_type) && $engine_type == 'Гибрид') ? 'selected' : '' }}>Гибрид</option>
                            <option value="Электрический" {{ (isset($engine_type) && $engine_type == 'Электрический') ? 'selected' : '' }}>Электрический</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Правая колонка: Контактная информация -->
            <div>
                <div class="form-card">
                    <h3 class="text-xl font-bold text-gray-800 mb-6 pb-3 border-b">
                        <i class="fas fa-user mr-2 text-green-600"></i>Контактная информация
                    </h3>
                    
                    <!-- Имя -->
                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-2 required-field">Ваше имя</label>
                        <input type="text" 
                               name="contact_name" 
                               required
                               placeholder="Иван Иванов"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>
                    
                    <!-- Телефон -->
                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-2 required-field">Телефон</label>
                        <input type="tel" 
                               name="contact_phone" 
                               required
                               placeholder="+7 (999) 999-99-99"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <div class="text-gray-500 text-sm mt-1">На этот номер мы отправим подтверждение</div>
                    </div>
                    
                    <!-- Email -->
                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-2 required-field">Email</label>
                        <input type="email" 
                               name="contact_email" 
                               required
                               placeholder="example@mail.ru"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <div class="text-gray-500 text-sm mt-1">На эту почту придет отчет и инструкции</div>
                    </div>
                    
                    <!-- Описание проблемы -->
                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-2">Дополнительное описание</label>
                        <textarea name="description" 
                                  rows="4"
                                  placeholder="Опишите проблему подробнее, если это необходимо..."
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">{{ $description ?? '' }}</textarea>
                    </div>
                </div>
                
                <!-- Согласие -->
                <div class="form-card bg-gray-50">
                    <div class="flex items-start">
                        <input type="checkbox" 
                               name="agreement" 
                               id="agreement" 
                               required
                               class="mt-1 mr-3 h-5 w-5 text-blue-600 rounded focus:ring-blue-500">
                        <label for="agreement" class="text-gray-700">
                            Я согласен с 
                            <a href="#" class="text-blue-600 hover:text-blue-800 underline">условиями оказания услуг</a> 
                            и обработкой персональных данных. Подтверждаю, что предоставленная информация точна и полна.
                        </label>
                    </div>
                    <div id="agreementError" class="text-red-600 text-sm mt-2 hidden">
                        <i class="fas fa-exclamation-circle mr-1"></i> Необходимо согласиться с условиями
                    </div>
                </div>
                
                <!-- Кнопка отправки -->
                <div class="mt-6">
                    <button type="submit" 
                            id="submitButton"
                            class="w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-bold py-4 px-6 rounded-lg text-lg transition-all duration-300 transform hover:scale-[1.02] shadow-lg">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span id="buttonText">Заказать консультацию за {{ number_format($price, 0, '', ' ') }} ₽</span>
                        <div id="loadingSpinner" class="hidden inline-block ml-2">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                    </button>
                    
                    <div class="text-center mt-4 text-gray-600 text-sm">
                        <i class="fas fa-lock mr-1"></i> Данные защищены. Оплата после консультации.
                    </div>
                </div>
            </div>
        </div>
    </form>
    
    <!-- Что входит в консультацию -->
    <div class="form-card mt-8">
        <h3 class="text-xl font-bold text-gray-800 mb-6 pb-3 border-b">
            <i class="fas fa-gift mr-2 text-purple-600"></i>Что входит в консультацию
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Базовый -->
            @if($consultationType === 'basic')
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <div class="text-blue-600 text-3xl mb-3">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h4 class="font-bold text-gray-800 mb-2">Подробный отчет</h4>
                    <p class="text-gray-600 text-sm">PDF-файл с анализом проблемы и рекомендациями</p>
                </div>
                
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <div class="text-blue-600 text-3xl mb-3">
                        <i class="fas fa-list-check"></i>
                    </div>
                    <h4 class="font-bold text-gray-800 mb-2">План действий</h4>
                    <p class="text-gray-600 text-sm">Пошаговая инструкция по диагностике и ремонту</p>
                </div>
                
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <div class="text-blue-600 text-3xl mb-3">
                        <i class="fas fa-toolbox"></i>
                    </div>
                    <h4 class="font-bold text-gray-800 mb-2">Список запчастей</h4>
                    <p class="text-gray-600 text-sm">Перечень необходимых деталей с артикулами</p>
                </div>
            
            <!-- Премиум -->
            @elseif($consultationType === 'premium')
                <div class="text-center p-4 bg-purple-50 rounded-lg">
                    <div class="text-purple-600 text-3xl mb-3">
                        <i class="fas fa-video"></i>
                    </div>
                    <h4 class="font-bold text-gray-800 mb-2">Видео-инструкции</h4>
                    <p class="text-gray-600 text-sm">Наглядные видео по диагностике и ремонту</p>
                </div>
                
                <div class="text-center p-4 bg-purple-50 rounded-lg">
                    <div class="text-purple-600 text-3xl mb-3">
                        <i class="fas fa-star"></i>
                    </div>
                    <h4 class="font-bold text-gray-800 mb-2">Рейтинг сервисов</h4>
                    <p class="text-gray-600 text-sm">Список проверенных автосервисов в вашем городе</p>
                </div>
                
                <div class="text-center p-4 bg-purple-50 rounded-lg">
                    <div class="text-purple-600 text-3xl mb-3">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h4 class="font-bold text-gray-800 mb-2">Чат с помощником</h4>
                    <p class="text-gray-600 text-sm">24/7 поддержка в чате по вопросам диагностики</p>
                </div>
            
            <!-- Эксперт -->
            @else
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <div class="text-green-600 text-3xl mb-3">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h4 class="font-bold text-gray-800 mb-2">Личный разбор</h4>
                    <p class="text-gray-600 text-sm">Персональный анализ вашего случая экспертом</p>
                </div>
                
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <div class="text-green-600 text-3xl mb-3">
                        <i class="fas fa-phone-volume"></i>
                    </div>
                    <h4 class="font-bold text-gray-800 mb-2">Звонок эксперта</h4>
                    <p class="text-gray-600 text-sm">Телефонная консультация со специалистом</p>
                </div>
                
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <div class="text-green-600 text-3xl mb-3">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4 class="font-bold text-gray-800 mb-2">Гарантия точности</h4>
                    <p class="text-gray-600 text-sm">Гарантия правильного диагноза или возврат средств</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('consultationForm');
    const brandSelect = document.getElementById('brandSelect');
    const modelSelect = document.getElementById('modelSelect');
    const submitButton = document.getElementById('submitButton');
    const buttonText = document.getElementById('buttonText');
    const loadingSpinner = document.getElementById('loadingSpinner');
    
    // Загрузка моделей при выборе марки
    brandSelect.addEventListener('change', function() {
        const brandId = this.value;
        
        if (!brandId) {
            modelSelect.innerHTML = '<option value="">-- Выберите модель --</option>';
            modelSelect.disabled = true;
            return;
        }
        
        modelSelect.disabled = true;
        modelSelect.innerHTML = '<option value="">Загрузка моделей...</option>';
        
        // AJAX запрос за моделями
        fetch(`/diagnostic/consultation/models/${brandId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.models.length > 0) {
                let options = '<option value="">-- Выберите модель --</option>';
                data.models.forEach(model => {
                    options += `<option value="${model.id}">${model.name}</option>`;
                });
                modelSelect.innerHTML = options;
            } else {
                modelSelect.innerHTML = '<option value="">Нет доступных моделей</option>';
            }
            modelSelect.disabled = false;
        })
        .catch(error => {
            console.error('Error loading models:', error);
            modelSelect.innerHTML = '<option value="">Ошибка загрузки</option>';
            modelSelect.disabled = false;
        });
    });
    
    // Валидация телефона
    const phoneInput = form.querySelector('input[name="contact_phone"]');
    if (phoneInput) {
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
    
    // Валидация формы перед отправкой
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Проверка согласия
        const agreement = form.querySelector('#agreement');
        const agreementError = document.getElementById('agreementError');
        
        if (!agreement.checked) {
            agreementError.classList.remove('hidden');
            agreement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        
        // Проверка марки
        if (!brandSelect.value) {
            const brandError = document.getElementById('brandError');
            brandError.textContent = 'Выберите марку автомобиля';
            brandError.classList.remove('hidden');
            brandSelect.focus();
            return;
        }
        
        // Показываем загрузку
        submitButton.disabled = true;
        buttonText.textContent = 'Отправка заказа...';
        loadingSpinner.classList.remove('hidden');
        
        // Отправка формы
        setTimeout(() => {
            form.submit();
        }, 100);
    });
    
    // Скрываем ошибку при изменении согласия
    const agreementCheckbox = form.querySelector('#agreement');
    if (agreementCheckbox) {
        agreementCheckbox.addEventListener('change', function() {
            if (this.checked) {
                document.getElementById('agreementError').classList.add('hidden');
            }
        });
    }
});
</script>
@endpush