<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatService
{
    public function processQuery($query)
    {
        // Простая обработка запроса без AI
        $processed = $this->normalizeQuery($query);
        
        return [
            'original_query' => $query,
            'processed_query' => $processed,
            'keywords' => $this->extractKeywords($processed),
            'intent' => $this->detectIntent($query),
            'complexity' => $this->estimateComplexity($query),
        ];
    }
    
    protected function normalizeQuery($query)
    {
        // Приводим к нижнему регистру
        $query = mb_strtolower($query, 'UTF-8');
        
        // Удаляем лишние пробелы
        $query = preg_replace('/\s+/', ' ', $query);
        
        // Удаляем знаки препинания, кроме дефиса
        $query = preg_replace('/[^\p{L}\p{N}\s-]/u', ' ', $query);
        
        return trim($query);
    }
    
    protected function extractKeywords($query)
    {
        $words = explode(' ', $query);
        
        // Список стоп-слов
        $stopWords = ['и', 'в', 'на', 'с', 'по', 'для', 'или', 'но', 'а', 'же'];
        
        $keywords = array_filter($words, function($word) use ($stopWords) {
            return mb_strlen($word, 'UTF-8') > 2 && !in_array($word, $stopWords);
        });
        
        return array_values($keywords);
    }
    
    protected function detectIntent($query)
    {
        $query = mb_strtolower($query, 'UTF-8');
        
        $intents = [
            'problem' => ['не работает', 'сломал', 'поломка', 'ошибка', 'неисправность', 'проблема'],
            'diagnostic' => ['почему', 'причина', 'диагностика', 'проверить', 'тест'],
            'repair' => ['как починить', 'ремонт', 'замена', 'установка', 'настройка'],
            'manual' => ['инструкция', 'руководство', 'схема', 'диаграмма', 'описание'],
            'specification' => ['характеристики', 'параметры', 'данные', 'spec', 'технические'],
        ];
        
        foreach ($intents as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($query, $keyword)) {
                    return $intent;
                }
            }
        }
        
        return 'general';
    }
    
    protected function estimateComplexity($query)
    {
        $complexityWords = [
            'сложный' => 3,
            'сложная' => 3,
            'проблема' => 2,
            'неисправность' => 2,
            'ремонт' => 2,
            'замена' => 2,
            'простой' => 1,
            'быстрый' => 1,
            'легкий' => 1,
        ];
        
        $score = 1; // По умолчанию средняя сложность
        
        foreach ($complexityWords as $word => $value) {
            if (str_contains(mb_strtolower($query, 'UTF-8'), $word)) {
                $score = max($score, $value);
            }
        }
        
        return $score;
    }
}