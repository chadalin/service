<?php
// public/test-search.php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Document;

echo "<h1>Проверка данных в документах</h1>";

// 1. Посчитаем документы
$total = Document::count();
$parsed = Document::where('is_parsed', true)->count();
$processed = Document::where('status', 'processed')->count();

echo "<p>Всего документов: {$total}</p>";
echo "<p>Распарсено: {$parsed}</p>";
echo "<p>Обработано: {$processed}</p>";

// 2. Покажем несколько документов
echo "<h2>Несколько документов для примера:</h2>";
$docs = Document::limit(5)->get();

foreach ($docs as $doc) {
    echo "<div style='border:1px solid #ccc; margin:10px; padding:10px;'>";
    echo "<h3>ID: {$doc->id} - {$doc->title}</h3>";
    echo "<p><strong>Статус:</strong> {$doc->status} | <strong>Парсинг:</strong> " . ($doc->is_parsed ? 'Да' : 'Нет') . "</p>";
    
    // Проверяем содержание полей
    echo "<p><strong>Title:</strong> " . htmlspecialchars(substr($doc->title, 0, 100)) . "</p>";
    
    if ($doc->content_text) {
        $contentPreview = substr($doc->content_text, 0, 200);
        echo "<p><strong>Content (первые 200 символов):</strong> " . htmlspecialchars($contentPreview) . "...</p>";
        echo "<p><strong>Длина контента:</strong> " . strlen($doc->content_text) . " символов</p>";
    } else {
        echo "<p><strong>Content:</strong> НЕТ</p>";
    }
    
    echo "<p><strong>Detected System:</strong> " . ($doc->detected_system ?? 'NULL') . "</p>";
    echo "<p><strong>Detected Component:</strong> " . ($doc->detected_component ?? 'NULL') . "</p>";
    echo "</div>";
}

// 3. Проверим поиск
echo "<h2>Проверка поиска:</h2>";

$testQueries = ['двигатель', 'ремонт', 'T22POW10030103651', 'масло'];

foreach ($testQueries as $query) {
    echo "<h3>Запрос: '{$query}'</h3>";
    
    // LIKE поиск
    $likeTerm = '%' . $query . '%';
    $count = Document::where(function($q) use ($likeTerm) {
            $q->where('title', 'LIKE', $likeTerm)
              ->orWhere('content_text', 'LIKE', $likeTerm)
              ->orWhere('detected_system', 'LIKE', $likeTerm)
              ->orWhere('detected_component', 'LIKE', $likeTerm);
        })
        ->where('is_parsed', true)
        ->where('status', 'processed')
        ->count();
    
    echo "<p>Найдено документов: {$count}</p>";
    
    if ($count > 0) {
        $docs = Document::where(function($q) use ($likeTerm) {
                $q->where('title', 'LIKE', $likeTerm)
                  ->orWhere('content_text', 'LIKE', $likeTerm)
                  ->orWhere('detected_system', 'LIKE', $likeTerm)
                  ->orWhere('detected_component', 'LIKE', $likeTerm);
            })
            ->where('is_parsed', true)
            ->where('status', 'processed')
            ->limit(3)
            ->get();
        
        foreach ($docs as $doc) {
            echo "<div style='border:1px solid #ddd; padding:5px; margin:5px;'>";
            echo "ID: {$doc->id} - {$doc->title}<br>";
            
            // Покажем где найдено
            if (stripos($doc->title, $query) !== false) {
                echo "<span style='color:green'>✓ Найдено в заголовке</span><br>";
            }
            if (stripos($doc->content_text ?? '', $query) !== false) {
                echo "<span style='color:green'>✓ Найдено в контенте</span><br>";
            }
            if (stripos($doc->detected_system ?? '', $query) !== false) {
                echo "<span style='color:green'>✓ Найдено в системе</span><br>";
            }
            if (stripos($doc->detected_component ?? '', $query) !== false) {
                echo "<span style='color:green'>✓ Найдено в компоненте</span><br>";
            }
            echo "</div>";
        }
    }
}

echo "<hr>";
echo "<h2>RAW SQL запрос для проверки:</h2>";
echo "<pre>";
echo "SELECT id, title, LEFT(content_text, 50) as preview 
FROM documents 
WHERE is_parsed = 1 AND status = 'processed'
LIMIT 5";
echo "</pre>";