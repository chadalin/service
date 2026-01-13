@extends('layouts.diagnostic')

@section('title', ' - Шаг 3: Загрузка данных')

@section('content')
@php
    $showProgress = true;
    $currentStep = 3;
@endphp

<div class="max-w-4xl mx-auto">
    <!-- Заголовок -->
    <div class="text-center mb-10">
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Загрузите дополнительные данные</h1>
        <p class="text-gray-600 text-lg">
            Фотографии, видео и логи сканера помогут эксперту точнее определить проблему.
        </p>
    </div>

    <form action="{{ route('diagnostic.analyze') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
        @csrf

        <!-- Загрузка фотографий -->
        <div class="diagnostic-card p-6 mb-8">
            <div class="flex items-center mb-6">
                <div class="bg-blue-100 p-3 rounded-lg mr-4">
                    <i class="fas fa-camera text-blue-600 text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Фотографии</h2>
                    <p class="text-gray-600">Сделайте фото приборной панели, моторного отсека и т.д.</p>
                </div>
            </div>

            <div class="file-upload-area mb-6" 
                 x-data="{ isDragging: false }"
                 @dragover.prevent="isDragging = true"
                 @dragleave.prevent="isDragging = false"
                 @drop.prevent="isDragging = false; handleDrop($event)">
                <input type="file" 
                       name="photos[]" 
                       id="photoInput" 
                       multiple 
                       accept="image/*"
                       class="hidden"
                       @change="handlePhotoUpload">
                
                <div class="text-center" :class="{ 'opacity-50': isDragging }">
                    <i class="fas fa-cloud-upload-alt text-4xl text-blue-500 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-700 mb-2">Перетащите фотографии сюда</h3>
                    <p class="text-gray-600 mb-4">или</p>
                    <label for="photoInput" class="btn-primary inline-block cursor-pointer">
                        <i class="fas fa-folder-open mr-2"></i> Выбрать файлы
                    </label>
                    <p class="text-sm text-gray-500 mt-4">
                        Поддерживаемые форматы: JPG, PNG, HEIC. Максимум 10 файлов по 5MB каждый.
                    </p>
                </div>
            </div>

            <!-- Предпросмотр фотографий -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6" id="photoPreview">
                <!-- Фото будут добавлены через JS -->
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-start">
                    <i class="fas fa-lightbulb text-yellow-600 mr-2 mt-1"></i>
                    <div>
                        <h4 class="font-bold text-yellow-800 mb-1">Что фотографировать?</h4>
                        <ul class="text-yellow-700 text-sm space-y-1">
                            <li>• Приборную панель с горящими индикаторами</li>
                            <li>• Моторный отсек (общий вид и проблемные места)</li>
                            <li>• Подтеки масла или охлаждающей жидкости</li>
                            <li>• Повреждённые детали (если есть)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Загрузка видео -->
        <div class="diagnostic-card p-6 mb-8">
            <div class="flex items-center mb-6">
                <div class="bg-blue-100 p-3 rounded-lg mr-4">
                    <i class="fas fa-video text-blue-600 text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Видео и аудио</h2>
                    <p class="text-gray-600">Запишите звук работы двигателя или видео проблемы</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Видео -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">
                        <i class="fas fa-video mr-1"></i> Видео проблемы
                    </label>
                    <input type="file" 
                           name="video" 
                           accept="video/*"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="text-xs text-gray-500 mt-1">MP4, MOV, AVI до 50MB</p>
                </div>

                <!-- Аудио -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">
                        <i class="fas fa-volume-up mr-1"></i> Аудио записи
                    </label>
                    <input type="file" 
                           name="audio" 
                           accept="audio/*"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="text-xs text-gray-500 mt-1">MP3, M4A, WAV до 10MB</p>
                </div>
            </div>
        </div>

        <!-- Логи сканера -->
        <div class="diagnostic-card p-6 mb-8">
            <div class="flex items-center mb-6">
                <div class="bg-blue-100 p-3 rounded-lg mr-4">
                    <i class="fas fa-file-code text-blue-600 text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Логи диагностического сканера</h2>
                    <p class="text-gray-600">Загрузите файлы с кодами ошибок и параметрами</p>
                </div>
            </div>

            <div class="space-y-4">
                <!-- Выбор типа сканера -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">
                        <i class="fas fa-toolbox mr-1"></i> Тип сканера
                    </label>
                    <select name="scanner_type" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="">Выберите тип сканера</option>
                        <option value="autel">Autel</option>
                        <option value="delphi">Delphi</option>
                        <option value="launch">Launch</option>
                        <option value="oem">OEM (дилерский)</option>
                        <option value="other">Другой</option>
                    </select>
                </div>

                <!-- Загрузка логов -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">
                        <i class="fas fa-file-upload mr-1"></i> Файлы логов
                    </label>
                    <input type="file" 
                           name="scanner_logs[]" 
                           multiple
                           accept=".txt,.log,.csv,.xml,.json"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="text-xs text-gray-500 mt-1">TXT, LOG, CSV, XML, JSON до 5MB каждый</p>
                </div>

                <!-- Или вставка кодов вручную -->
                <div x-data="{ showManualInput: false }">
                    <button type="button"
                            @click="showManualInput = !showManualInput"
                            class="text-blue-600 hover:text-blue-800 font-medium mb-2">
                        <i class="fas fa-keyboard mr-1"></i>
                        <span x-text="showManualInput ? 'Скрыть ручной ввод' : 'Или введите коды ошибок вручную'"></span>
                    </button>

                    <div x-show="showManualInput" x-cloak class="mt-2">
                        <textarea name="error_codes_manual" 
                                  rows="4"
                                  placeholder="Пример:
P0300 - Random/Multiple Cylinder Misfire Detected
P0301 - Cylinder 1 Misfire Detected
U0100 - Lost Communication With ECM/PCM"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg font-mono text-sm"></textarea>
                        <p class="text-xs text-gray-500 mt-1">Вводите по одному коду на строку в формате: КОД - Описание</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Согласие на обработку -->
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 mb-8">
            <div class="flex items-start">
                <input type="checkbox" 
                       name="consent" 
                       id="consent" 
                       required
                       class="mt-1 mr-3 h-5 w-5 text-blue-600 rounded focus:ring-blue-500">
                <label for="consent" class="text-gray-700">
                    Я даю согласие на обработку моих персональных данных и загруженных материалов для целей диагностики. 
                    Понимаю, что система является помощником и не заменяет очный осмотр у специалиста.
                </label>
            </div>
        </div>

        <!-- Кнопки -->
        <div class="flex justify-between items-center mt-10">
            <div>
                <a href="{{ route('diagnostic.step2') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                    <i class="fas fa-arrow-left mr-1"></i> Вернуться к данным авто
                </a>
            </div>
            
            <div class="flex space-x-4">
                <button type="button" onclick="window.history.back()" class="btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i> Назад
                </button>
                
                <button type="submit" 
                        id="analyzeButton"
                        class="btn-primary relative"
                        :disabled="!consentChecked"
                        :class="{ 'opacity-50 cursor-not-allowed': !consentChecked }">
                    <span id="buttonText">Начать анализ <i class="fas fa-play ml-2"></i></span>
                    <div id="loadingSpinner" class="hidden absolute inset-0 flex items-center justify-center bg-blue-600 rounded-lg">
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-white"></div>
                    </div>
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('uploadForm');
    const photoInput = document.getElementById('photoInput');
    const photoPreview = document.getElementById('photoPreview');
    const analyzeButton = document.getElementById('analyzeButton');
    const buttonText = document.getElementById('buttonText');
    const loadingSpinner = document.getElementById('loadingSpinner');
    
    let uploadedPhotos = [];
    
    // Обработка перетаскивания фото
    function handleDrop(event) {
        const files = event.dataTransfer.files;
        handlePhotoFiles(files);
    }
    
    // Обработка выбора фото
    function handlePhotoUpload(event) {
        const files = event.target.files;
        handlePhotoFiles(files);
    }
    
    function handlePhotoFiles(files) {
        for (let i = 0; i < Math.min(files.length, 10 - uploadedPhotos.length); i++) {
            const file = files[i];
            if (file.type.startsWith('image/') && file.size <= 5 * 1024 * 1024) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const photo = {
                        id: Date.now() + i,
                        name: file.name,
                        url: e.target.result,
                        file: file
                    };
                    
                    uploadedPhotos.push(photo);
                    renderPhotoPreview();
                };
                
                reader.readAsDataURL(file);
            } else {
                alert(`Файл ${file.name} не соответствует требованиям. Максимальный размер: 5MB`);
            }
        }
        
        // Обновить input files
        updatePhotoInput();
    }
    
    function renderPhotoPreview() {
        photoPreview.innerHTML = '';
        
        uploadedPhotos.forEach((photo, index) => {
            const col = document.createElement('div');
            col.className = 'relative group';
            
            col.innerHTML = `
                <div class="relative aspect-square rounded-lg overflow-hidden border border-gray-200">
                    <img src="${photo.url}" alt="${photo.name}" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-300 flex items-center justify-center">
                        <button type="button" 
                                onclick="removePhoto(${photo.id})"
                                class="opacity-0 group-hover:opacity-100 transform translate-y-2 group-hover:translate-y-0 transition-all duration-300 bg-red-500 text-white p-2 rounded-full hover:bg-red-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-2 truncate">${photo.name}</p>
            `;
            
            photoPreview.appendChild(col);
        });
        
        // Добавить пустые слоты
        const remainingSlots = 4 - (uploadedPhotos.length % 4 || 4);
        for (let i = 0; i < remainingSlots && uploadedPhotos.length < 10; i++) {
            const emptySlot = document.createElement('div');
            emptySlot.className = 'aspect-square rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center';
            emptySlot.innerHTML = '<i class="fas fa-plus text-gray-400"></i>';
            photoPreview.appendChild(emptySlot);
        }
    }
    
    function removePhoto(id) {
        uploadedPhotos = uploadedPhotos.filter(photo => photo.id !== id);
        renderPhotoPreview();
        updatePhotoInput();
    }
    
    function updatePhotoInput() {
        // Создаем новый DataTransfer
        const dataTransfer = new DataTransfer();
        
        // Добавляем файлы
        uploadedPhotos.forEach(photo => {
            dataTransfer.items.add(photo.file);
        });
        
        // Обновляем input
        photoInput.files = dataTransfer.files;
    }
    
    // Экспортируем функцию для Alpine.js
    window.handleDrop = handleDrop;
    window.handlePhotoUpload = handlePhotoUpload;
    window.removePhoto = removePhoto;
    
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
    const videoInput = form.querySelector('input[name="video"]');
    if (videoInput) {
        videoInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file && file.size > 50 * 1024 * 1024) {
                alert('Видео файл слишком большой. Максимальный размер: 50MB');
                this.value = '';
            }
        });
    }
    
    const scannerInput = form.querySelector('input[name="scanner_logs[]"]');
    if (scannerInput) {
        scannerInput.addEventListener('change', function() {
            const files = Array.from(this.files);
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