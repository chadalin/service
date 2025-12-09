<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Str;

class ChatService
{
    protected $searchEngine;

    public function __construct()
    {
        $this->searchEngine = new SearchEngine();
    }

    public function processQuery(string $query): array
    {
        $analysis = $this->analyzeQuery($query);
        
        return [
            'original_query' => $query,
            'processed_query' => $analysis['processed_query'],
            'intent' => $analysis['intent'],
            'keywords' => $analysis['keywords'],
            'diagnosis' => $this->generateDiagnosis($analysis),
            'estimated_repair_time' => $this->estimateRepairTime($analysis),
            'repair_complexity' => $this->estimateComplexity($analysis)
        ];
    }

    private function analyzeQuery(string $query): array
    {
        $query = mb_strtolower(trim($query));
        
        // Определяем намерение
        $intent = $this->detectIntent($query);
        
        // Извлекаем ключевые слова
        $keywords = $this->extractKeywords($query);
        
        // Обрабатываем синонимы
        $processedQuery = $this->expandWithSynonyms($query);

        return [
            'original_query' => $query,
            'processed_query' => $processedQuery,
            'intent' => $intent,
            'keywords' => $keywords
        ];
    }

    private function detectIntent(string $query): string
    {
        $intents = [
            'diagnosis' => ['не заводится', 'стучит', 'шумит', 'гремит', 'вибрация', 'дым', 'течет', 'не работает'],
            'repair' => ['замена', 'ремонт', 'установка', 'настройка', 'регулировка', 'чистка'],
            'maintenance' => ['обслуживание', 'тюнинг', 'диагностика', 'проверка'],
            'parts' => ['купить', 'запчасть', 'деталь', 'артикул', 'цена', 'стоимость']
        ];

        foreach ($intents as $intent => $patterns) {
            foreach ($patterns as $pattern) {
                if (Str::contains($query, $pattern)) {
                    return $intent;
                }
            }
        }

        return 'general';
    }

    private function extractKeywords(string $query): array
    {
        $stopWords = ['как', 'что', 'почему', 'где', 'когда', 'для', 'на', 'в', 'с', 'по', 'и', 'или', 'но', 'не'];
        
        $words = preg_split('/\s+/', $query);
        $words = array_filter($words, function($word) use ($stopWords) {
            return !in_array($word, $stopWords) && mb_strlen($word) > 2;
        });
        
        return array_values($words);
    }

    private function expandWithSynonyms(string $query): string
    {
        $synonyms = [
            'замена' => ['поменять', 'установка новой', 'монтаж'],
            'ремонт' => ['починка', 'восстановление', 'fix'],
            'двигатель' => ['мотор', 'движок'],
            'тормоз' => ['brake', 'стоп'],
            'масло' => ['oil', 'смазка']
        ];

        foreach ($synonyms as $word => $synonymList) {
            if (Str::contains($query, $word)) {
                $query .= ' ' . implode(' ', $synonymList);
            }
        }

        return $query;
    }

    private function generateDiagnosis(array $analysis): string
    {
        $keywords = implode(' ', $analysis['keywords']);
        
        $diagnosisPatterns = [
            'не заводится' => 'Возможные проблемы: стартер, аккумулятор, топливная система, зажигание',
            'стучит' => 'Возможные причины: износ подвески, проблемы с двигателем, выхлопной системой',
            'течет' => 'Необходимо проверить: системы охлаждения, масляную систему, тормозную жидкость',
            'перегревается' => 'Проверьте: систему охлаждения, термостат, радиатор, уровень антифриза',
            'вибрация' => 'Возможные причины: разбалансировка колес, проблемы с ШРУСами, опорами двигателя'
        ];

        foreach ($diagnosisPatterns as $pattern => $diagnosis) {
            if (Str::contains($keywords, $pattern)) {
                return $diagnosis;
            }
        }

        return 'Требуется диагностика для точного определения проблемы';
    }

    private function estimateRepairTime(array $analysis): string
    {
        $complexity = $this->estimateComplexity($analysis);
        
        return match($complexity) {
            'low' => '1-2 часа',
            'medium' => '3-5 часов', 
            'high' => '6-8 часов',
            'very_high' => '1-2 дня',
            default => 'Требуется диагностика'
        };
    }

    private function estimateComplexity(array $analysis): string
    {
        $complexKeywords = ['двигатель', 'трансмиссия', 'блок', 'турбина', 'редуктор'];
        $mediumKeywords = ['тормоз', 'подвеска', 'рулевое', 'стартер', 'генератор'];
        $simpleKeywords = ['лампочка', 'фильтр', 'щетки', 'зеркало', 'ручка'];
        
        foreach ($analysis['keywords'] as $keyword) {
            if (in_array($keyword, $complexKeywords)) return 'high';
            if (in_array($keyword, $mediumKeywords)) return 'medium';
            if (in_array($keyword, $simpleKeywords)) return 'low';
        }

        return 'medium';
    }

    public function suggestQuestions(string $query): array
    {
        $analysis = $this->analyzeQuery($query);
        
        $suggestions = [
            "Диагностика {$analysis['processed_query']}",
            "Стоимость ремонта {$analysis['processed_query']}",
            "Причины {$analysis['processed_query']}",
            "Как предотвратить {$analysis['processed_query']}"
        ];

        return array_slice($suggestions, 0, 3);
    }
}