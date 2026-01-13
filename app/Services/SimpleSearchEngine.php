<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\DB;

class SimpleSearchEngine
{
    public function search(string $query, $carModelId = null, $categoryId = null)
    {
        $searchQuery = $this->prepareQuery($query);
        
        $documents = Document::with(['carModel.brand', 'category'])
            ->where('status', 'processed')
            ->where(function($q) use ($searchQuery) {
                // Поиск по названию
                $q->where('title', 'like', "%{$searchQuery}%")
                  // Поиск по содержимому
                  ->orWhere('content_text', 'like', "%{$searchQuery}%")
                  // Поиск по ключевым словам
                  ->orWhere('keywords', 'like', "%{$searchQuery}%");
            })
            ->when($carModelId, function($q) use ($carModelId) {
                return $q->where('car_model_id', $carModelId);
            })
            ->when($categoryId, function($q) use ($categoryId) {
                return $q->where('category_id', $categoryId);
            })
            ->orderByRaw("
                CASE 
                    WHEN title LIKE ? THEN 1
                    WHEN content_text LIKE ? THEN 2
                    ELSE 3
                END
            ", ["%{$searchQuery}%", "%{$searchQuery}%"])
            ->get()
            ->map(function($document) use ($searchQuery) {
                // Вычисляем релевантность
                $document->relevance = $this->calculateRelevance($document, $searchQuery);
                return $document;
            })
            ->sortByDesc('relevance')
            ->values();

        return $documents;
    }

    private function prepareQuery(string $query): string
    {
        // Очищаем запрос
        $query = trim($query);
        $query = preg_replace('/[^\p{Cyrillic}\s\.\,\-\!\\?\(\)\:\;0-9]/u', ' ', $query);
        $query = preg_replace('/\s+/', ' ', $query);
        
        return $query;
    }

    private function calculateRelevance(Document $document, string $query): float
    {
        $score = 0;
        
        // Проверяем наличие в заголовке
        if (stripos($document->title, $query) !== false) {
            $score += 3;
        }
        
        // Проверяем наличие в контенте
        if (stripos($document->content_text ?? '', $query) !== false) {
            $score += 1;
        }
        
        // Проверяем ключевые слова
        $keywords = json_decode($document->keywords ?? '[]', true);
        foreach ($keywords as $keyword) {
            if (stripos($keyword, $query) !== false) {
                $score += 2;
            }
        }
        
        return $score;
    }
}