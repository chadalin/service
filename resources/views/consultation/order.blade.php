@extends('layouts.diagnostic')

@section('title', ' - Заказ консультации')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Заголовок -->
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Заказ консультации</h1>
        <p class="text-gray-600 text-sm">Заполните форму для заказа выбранной услуги</p>
    </div>

    <!-- Информация о заказе -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4">
        <div class="flex items-center mb-3">
            <div class="bg-blue-100 p-1.5 rounded-lg mr-2">
                <i class="fas fa-shopping-cart text-blue-600"></i>
            </div>
            <h2 class="font-bold text-gray-800">Детали заказа</h2>
        </div>
        
        <div class="space-y-2">
            <div class="flex justify-between items-center">
                <span class="text-gray-600 text-sm">Услуга:</span>
                <span class="font-medium text-sm">
                    @if($type === 'basic')
                        <span class="text-blue-600">Базовая диагностика</span>
                    @elseif($type === 'premium')
                        <span class="text-purple-600">Премиум отчёт</span>
                    @elseif($type === 'expert')
                        <span class="text-green-600">Консультация эксперта</span>
                    @endif
                </span>
            </div>
            
            <div class="flex justify-between items-center">
                <span class="text-gray-600 text-sm">Автомобиль:</span>
                <span class="font-medium text-sm">
                    {{ $case->brand->name ?? '' }} {{ $case->model->name ?? '' }}
                </span>
            </div>
            
            <div class="flex justify-between items-center">
                <span class="text-gray-600 text-sm">Сложность:</span>
                <span class="font-medium text-sm">
                    {{ $case->rule->complexity_level ?? 1 }}/10
                </span>
            </div>
            
            <div class="flex justify-between items-center">
                <span class="text-gray-600 text-sm">Цена:</span>
                <span class="text-lg font-bold text-green-600">{{ number_format($price, 0, '', ' ') }} ₽</span>
            </div>
        </div>
    </div>

    <!-- Форма заказа -->
    <form action="{{ route('consultation.order', $case->id) }}" method="POST" id="orderForm">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">
        
        <!-- Контактные данные -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4">
            <div class="flex items-center mb-3">
                <div class="bg-blue-100 p-1.5 rounded-lg mr-2">
                    <i class="fas fa-user text-blue-600"></i>
                </div>
                <h2 class="font-bold text-gray-800">Контактные данные</h2>
            </div>
            
            <div class="space-y-3">
                <div>
                    <label class="block text-gray-700 font-medium mb-1 text-sm">
                        Ваше имя *
                    </label>
                    <input type="text" 
                           name="name" 
                           value="{{ Auth::user()->name }}"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-gray-700 font-medium mb-1 text-sm">
                            Телефон *
                        </label>
                        <input type="tel" 
                               name="phone" 
                               placeholder="+7 (999) 123-45-67"
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-1 text-sm">
                            Email *
                        </label>
                        <input type="email" 
                               name="email" 
                               value="{{ Auth::user()->email }}"
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-1 text-sm">
                        Предпочтительный способ связи *
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                        <label class="flex items-center p-2 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="preferred_contact" value="phone" class="mr-2" checked>
                            <span class="text-sm">Телефон</span>
                        </label>
                        <label class="flex items-center p-2 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="preferred_contact" value="email" class="mr-2">
                            <span class="text-sm">Email</span>
                        </label>
                        <label class="flex items-center p-2 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="preferred_contact" value="telegram" class="mr-2">
                            <span class="text-sm">Telegram</span>
                        </label>
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-1 text-sm">
                        Комментарий (необязательно)
                    </label>
                    <textarea name="comment" 
                              rows="2"
                              placeholder="Дополнительная информация, удобное время для связи..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
            </div>
        </div>
        
        <!-- Информация о услуге -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
            <div class="flex items-start">
                <div class="mr-2">
                    <i class="fas fa-info-circle text-blue-600"></i>
                </div>
                <div class="text-blue-700 text-xs">
                    <p class="font-medium mb-1">Что входит в услугу:</p>
                    @if($type === 'basic')
                        <p>• Полный анализ проблемы</p>
                        <p>• PDF отчёт с деталями</p>
                        <p>• Список запчастей</p>
                    @elseif($type === 'premium')
                        <p>• Всё из базового</p>
                        <p>• Видео-инструкции</p>
                        <p>• Рейтинг сервисов в вашем городе</p>
                        <p>• Чат с помощником</p>
                    @elseif($type === 'expert')
                        <p>• Всё из премиум</p>
                        <p>• Личный разбор от специалиста</p>
                        <p>• Помощь в выборе сервиса</p>
                        <p>• Гарантия правильности диагноза</p>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Кнопки -->
        <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
            <div class="order-2 sm:order-1">
                <a href="{{ route('diagnostic.result', $case->id) }}" 
                   class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Вернуться
                </a>
            </div>
            
            <div class="flex space-x-2 w-full sm:w-auto order-1 sm:order-2">
                <a href="{{ route('diagnostic.result', $case->id) }}" 
                   class="btn-secondary py-2.5 px-4 text-sm flex-1 sm:flex-none text-center">
                    Отмена
                </a>
                <button type="submit" 
                        class="btn-primary py-2.5 px-4 text-sm flex-1 sm:flex-none">
                    <i class="fas fa-check mr-1.5"></i> Оформить заказ
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('orderForm');
    const phoneInput = form.querySelector('input[name="phone"]');
    
    // Маска для телефона
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,1})(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/);
            e.target.value = !x[2] ? x[1] : '+' + x[1] + ' (' + x[2] + (x[3] ? ') ' + x[3] + (x[4] ? '-' + x[4] : '') + (x[5] ? '-' + x[5] : '') : '');
        });
    }
    
    // Валидация формы
    form.addEventListener('submit', function(e) {
        const phone = form.querySelector('input[name="phone"]').value;
        const email = form.querySelector('input[name="email"]').value;
        
        // Простая валидация телефона
        if (phone.length < 10) {
            e.preventDefault();
            alert('Пожалуйста, введите корректный номер телефона');
            return;
        }
        
        // Валидация email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            alert('Пожалуйста, введите корректный email');
            return;
        }
        
        // Показать лоадер
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i> Обработка...';
        }
    });
});
</script>

<style>
@media (max-width: 640px) {
    .grid-cols-1.md\:grid-cols-3 {
        grid-template-columns: 1fr;
    }
    
    .grid-cols-1.md\:grid-cols-2 {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush
@endsection