<!DOCTYPE html>
<html>
<head>
    <title>Отладка изображений - {{ $document->title }}</title>
    <style>
        body { padding: 20px; }
        .image-container { margin: 10px; padding: 10px; border: 1px solid #ddd; }
        img { max-width: 200px; max-height: 200px; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <h1>Отладка изображений - {{ $document->title }}</h1>
    
    <div>
        <h3>Информация о документе:</h3>
        <p>ID: {{ $document->id }}</p>
        <p>Статус: {{ $document->status }}</p>
        <p>Изображений в БД: {{ $imagesCount }}</p>
    </div>
    
    <div>
        <h3>Проверка директорий:</h3>
        @foreach($directories as $dir => $exists)
            <p>{{ $dir }}: {{ $exists ? '✅ Существует' : '❌ Не существует' }}</p>
        @endforeach
    </div>
    
    <div>
        <h3>Проверка изображений в БД:</h3>
        @if($images->count() > 0)
            @foreach($images as $image)
            <div class="image-container">
                <h4>Изображение #{{ $image->id }}</h4>
                <p>Путь: {{ $image->path }}</p>
                <p>Скриншот: {{ $image->screenshot_path ?? 'Нет' }}</p>
                <p>Размер: {{ $image->size }} байт</p>
                
                <div>
                    <strong>Оригинал:</strong><br>
                    @php
                        $originalExists = Storage::disk('public')->exists($image->path);
                    @endphp
                    @if($originalExists)
                        <p class="success">✅ Файл существует</p>
                        <img src="{{ Storage::url($image->path) }}" alt="Оригинал">
                        <br>
                        <a href="{{ Storage::url($image->path) }}" target="_blank">Открыть</a>
                    @else
                        <p class="error">❌ Файл не найден</p>
                    @endif
                </div>
                
                @if($image->screenshot_path)
                <div>
                    <strong>Скриншот:</strong><br>
                    @php
                        $screenshotExists = Storage::disk('public')->exists($image->screenshot_path);
                    @endphp
                    @if($screenshotExists)
                        <p class="success">✅ Файл существует</p>
                        <img src="{{ Storage::url($image->screenshot_path) }}" alt="Скриншот">
                        <br>
                        <a href="{{ Storage::url($image->screenshot_path) }}" target="_blank">Открыть</a>
                    @else
                        <p class="error">❌ Файл не найден</p>
                    @endif
                </div>
                @endif
            </div>
            @endforeach
        @else
            <p>Нет изображений в базе данных</p>
        @endif
    </div>
    
    <div>
        
    </div>
</body>
</html>