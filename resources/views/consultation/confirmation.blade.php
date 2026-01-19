@extends('layouts.diagnostic')

@section('title', ' - Подтверждение заказа')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
            <i class="fas fa-check text-green-600 text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Заказ успешно оформлен!</h1>
        <p class="text-gray-600 text-sm">Номер вашего заказа: <span class="font-mono text-blue-600">#{{ $consultation->id }}</span></p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4">
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-gray-600 text-sm">Услуга:</span>
                <span class="font-medium text-sm">
                    @if($consultation->type === 'basic')
                        <span class="text-blue-600">Базовая диагностика</span>
                    @elseif($consultation->type === 'premium')
                        <span class="text-purple-600">Премиум отчёт</span>
                    @elseif($consultation->type === 'expert')
                        <span class="text-green-600">Консультация эксперта</span>
                    @endif
                </span>
            </div>
            
            <div class="flex justify-between items-center">
                <span class="text-gray-600 text-sm">Цена:</span>
                <span class="text-lg font-bold text-green-600">{{ number_format($consultation->price, 0, '', ' ') }} ₽</span>
            </div>
            
            <div class="flex justify-between items-center">
                <span class="text-gray-600 text-sm">Статус:</span>
                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">
                    Ожидает обработки
                </span>
            </div>
            
            <div class="flex justify-between items-center">
                <span class="text-gray-600 text-sm">Дата заказа:</span>
                <span class="font-medium text-sm">{{ $consultation->created_at->format('d.m.Y H:i') }}</span>
            </div>
        </div>
    </div>

    <!-- Контактная информация -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
        <div class="flex items-center mb-2">
            <i class="fas fa-user-circle text-blue-600 mr-2"></i>
            <h3 class="font-bold text-blue-800">Контактная информация</h3>
        </div>
        
        <div class="space-y-2 text-sm">
            <p><span class="text-gray-600">Имя:</span> <span class="font-medium">{{ $consultation->customer_name }}</span></p>
            <p><span class="text-gray-600">Телефон:</span> <span class="font-medium">{{ $consultation->customer_phone }}</span></p>
            <p><span class="text-gray-600">Email:</span> <span class="font-medium">{{ $consultation->customer_email }}</span></p>
            <p><span class="text-gray-600">Способ связи:</span> <span class="font-medium">
                @if($consultation->preferred_contact === 'phone') Телефон
                @elseif($consultation->preferred_contact === 'email') Email
                @elseif($consultation->preferred_contact === 'telegram') Telegram
                @endif
            </span></p>
        </div>
    </div>

    <!-- Что дальше -->
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
        <div class="flex items-center mb-2">
            <i class="fas fa-clock text-green-600 mr-2"></i>
            <h3 class="font-bold text-green-800">Что дальше?</h3>
        </div>
        
        <div class="space-y-2 text-sm text-green-700">
            <p>1. Наш менеджер свяжется с вами в течение 24 часов</p>
            <p>2. Мы уточним детали и согласуем время</p>
            <p>3. Вы получите доступ к услуге или консультации</p>
        </div>
    </div>

    <!-- Кнопки -->
    <div class="flex flex-col sm:flex-row justify-center gap-3">
        <a href="{{ route('diagnostic.result', $consultation->case_id) }}" 
           class="btn-secondary py-2.5 px-4 text-sm text-center">
            <i class="fas fa-arrow-left mr-1.5"></i> К результатам диагностики
        </a>
        
        <a href="{{ route('diagnostic.start') }}" 
           class="btn-primary py-2.5 px-4 text-sm text-center">
            <i class="fas fa-redo mr-1.5"></i> Новая диагностика
        </a>
    </div>
</div>

@if(session('success'))
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Показать уведомление об успехе
    setTimeout(() => {
        alert('{{ session('success') }}');
    }, 300);
});
</script>
@endif
@endsection