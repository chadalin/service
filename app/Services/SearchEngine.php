<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Str;

class SearchEngine
{
    public function search(string $query, ?int $carModelId = null, ?int $categoryId = null)
    {
        $searchQuery = $this->prepareQuery($query);
        
        $documents = Document::with(['carModel.brand', 'category'])
            ->where('status', 'processed')
            ->when($carModelId, function($q) use ($carModelId) {
                return $q->where('car_model_id', $carModelId);
            })
            ->when($categoryId, function($q) use ($categoryId) {
                return $q->where('category_id', $categoryId);
            })
            ->where(function($q) use ($searchQuery) {
                // Поиск по названию (высокий приоритет)
                $q->where('title', 'like', "%{$searchQuery}%")
                  // Поиск по содержимому
                  ->orWhere('content_text', 'like', "%{$searchQuery}%");
            })
            ->get()
            ->map(function($document) use ($searchQuery) {
                // Вычисляем релевантность
                $document->relevance_score = $this->calculateRelevance($document, $searchQuery);
                return $document;
            })
            ->sortByDesc('relevance_score')
            ->values();

        return $documents;
    }

    private function prepareQuery(string $query): string
    {
        // Очищаем запрос и разбиваем на слова
        $query = trim($query);
        $query = preg_replace('/[^\x{0410}-\x{044F}\x{0401}\x{0451}a-zA-Z0-9\s]/u', ' ', $query);
        
        return $query;
    }

    private function calculateRelevance(Document $document, string $query): float
    {
        $score = 0;
        $queryWords = explode(' ', mb_strtolower($query));
        $title = mb_strtolower($document->title);
        $content = mb_strtolower($document->content_text);

        foreach ($queryWords as $word) {
            if (empty(trim($word))) continue;

            // Высокий вес для совпадений в названии
            if (Str::contains($title, $word)) {
                $score += 3;
            }

            // Средний вес для совпадений в контенте
            if (Str::contains($content, $word)) {
                $score += 1;
            }

            // Бонус за точное совпадение фразы
            if (Str::contains($title, $query) || Str::contains($content, $query)) {
                $score += 5;
            }
        }

        return $score;
    }

    public function findSimilarDocuments(Document $document, int $limit = 5)
    {
        $keywords = (new DocumentProcessor())->extractKeywords($document->content_text);
        
        if (empty($keywords)) {
            return collect();
        }

        return Document::where('id', '!=', $document->id)
            ->where('car_model_id', $document->car_model_id)
            ->where('status', 'processed')
            ->where(function($q) use ($keywords) {
                foreach (array_slice($keywords, 0, 5) as $keyword) {
                    $q->orWhere('title', 'like', "%{$keyword}%")
                      ->orWhere('content_text', 'like', "%{$keyword}%");
                }
            })
            ->limit($limit)
            ->get();
    }
}