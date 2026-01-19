@extends('layouts.diagnostic')

@section('title', ' - Шаг 3: Загрузка данных')

@section('content')
@php
    $showProgress = true;
    $currentStep = 3;
@endphp

<div class="max-w-4xl mx-auto">
    <!-- Заголовок -->
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Загрузка данных</h1>
        <p class="text-gray-600">Загрузите дополнительные материалы для точной диагностики</p>
    </div>

    <form action="{{ route('diagnostic.analyze') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
        @csrf

        <!-- Загрузка фотографий -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4">
            <div class="flex items-center mb-4">
                <div class="bg-blue-100 p-2 rounded-lg mr-3">
                    <i class="fas fa-camera text-blue-600"></i>
                </div>
                <div>
                    <h2 class="font-bold text-gray-800">Фотографии</h2>
                    <p class="text-sm text-gray-600">Необязательно, но поможет точнее определить проблему</p>
                </div>
            </div>

            <div class="mb-4">
                <input type="file" 
                       name="photos[]" 
                       id="photoInput" 
                       multiple 
                       accept="image/*"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <p class="text-xs text-gray-500 mt-1">JPG, PNG до 5MB каждый. Максимум 10 файлов.</p>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                <div class="flex items-start">
                    <i class="fas fa-lightbulb text-yellow-600 mr-2 mt-0.5"></i>
                    <div>
                        <h4 class="font-bold text-yellow-800 mb-1 text-sm">Что фотографировать?</h4>
                        <ul class="text-yellow-700 text-xs space-y-0.5">
                            <li>• Приборную панель с индикаторами</li>
                            <li>• Моторный отсек</li>
                            <li>• Подтеки жидкостей</li>
                            <li>• Повреждённые детали</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Согласие на обработку -->
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-6">
            <div class="flex items-start">
                <input type="checkbox" 
                       name="consent" 
                       id="consent" 
                       required
                       class="mt-0.5 mr-3 h-4 w-4 text-blue-600 rounded focus:ring-blue-500">
                <label for="consent" class="text-gray-700 text-sm">
                    Я даю согласие на обработку моих персональных данных и загруженных материалов для целей диагностики. 
                    Понимаю, что система является помощником и не заменяет очный осмотр у специалиста.
                </label>
            </div>
        </div>

        <!-- Кнопки -->
        <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
            <div class="order-2 sm:order-1">
                <a href="{{ route('diagnostic.step3') }}" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Вернуться к данным авто
                </a>
            </div>
            
            <div class="flex space-x-3 w-full sm:w-auto order-1 sm:order-2">
                <a href="{{ route('diagnostic.step3') }}" class="btn-secondary py-2.5 px-4 text-sm flex-1 sm:flex-none text-center">
                    <i class="fas fa-arrow-left mr-2"></i> Назад
                </a>
                
                <button type="submit" 
                        id="analyzeButton"
                        class="btn-primary py-2.5 px-4 text-sm flex-1 sm:flex-none">
                    <span id="buttonText">Начать анализ <i class="fas fa-play ml-2"></i></span>
                    <span id="loadingSpinner" class="hidden">
                        <i class="fas fa-spinner fa-spin mr-2"></i> Анализ...
                    </span>
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('uploadForm');
    const analyzeButton = document.getElementById('analyzeButton');
    const buttonText = document.getElementById('buttonText');
    const loadingSpinner = document.getElementById('loadingSpinner');
    
    // Обработка отправки формы
    form.addEventListener('submit', function(e) {
        const consent = document.getElementById('consent');
        if (!consent.checked) {
            e.preventDefault();
            alert('Пожалуйста, подтвердите согласие на обработку данных');
            return;
        }
        
        // Показать спиннер загрузки
        buttonText.classList.add('hidden');
        loadingSpinner.classList.remove('hidden');
        analyzeButton.disabled = true;
        
        // Меняем текст кнопки
        setTimeout(() => {
            buttonText.textContent = 'Анализируем...';
        }, 1000);
    });
    
    // Валидация файлов
    const photoInput = form.querySelector('input[name="photos[]"]');
    if (photoInput) {
        photoInput.addEventListener('change', function() {
            const files = Array.from(this.files);
            if (files.length > 10) {
                alert('Максимум 10 файлов');
                this.value = '';
                return;
            }
            
            files.forEach(file => {
                if (file.size > 5 * 1024 * 1024) {
                    alert(`Файл ${file.name} слишком большой. Максимальный размер: 5MB`);
                    this.value = '';
                }
            });
        });
    }
});
</script>
@endpush
@endsection