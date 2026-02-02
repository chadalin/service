<?php
// app/Services/SearchService.php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentPage;
use App\Models\DocumentImage;
use App\Models\SearchLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class SearchService
{
    private $cacheDuration = 300; // 5 минут
    
    /**
     * Выполнить интеллектуальный поиск
     */
    public function intelligentSearch(string $query, array $filters = [], int $limit = 20): array
    {
        $cacheKey = 'search_intelligent_' . md5($query . serialize($filters) . $limit);
        
        return Cache::remember($cacheKey, $this->cacheDuration, function() use ($query, $filters, $limit) {
            $results = [];
            
            // Основной поиск по документам
            $documents = Document::search($query, $filters, $limit)->get();
            
            foreach ($documents as $document) {
                $documentResult = [
                    'document' => $document,
                    'relevance_score' => $document->relevance_score ?? 0,
                    'highlighted_content' => $this->highlightText($document->content_text ?? '', $query, 150),
                    'snippet' => $this->generateSnippet($document->content_text ?? '', $query),
                    'metadata' => [
                        'car_model' => $document->carModel?->name ?? null,
                        'category' => $document->category?->name ?? null,
                        'pages' => $document->total_pages,
                        'file_type' => $document->file_type,
                        'parsing_quality' => $document->parsing_quality
                    ]
                ];
                
                $results[] = $documentResult;
            }
            
            return $this->groupResults($results);
        });
    }
    
    /**
     * Поиск с пагинацией
     */
    public function paginatedSearch(string $query, array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        
        $documents = Document::search($query, $filters, $perPage, $offset)->get();
        $total = $this->getSearchCount($query, $filters);
        
        // Логируем поиск
        $this->logSearch($query, $filters, $total);
        
        return [
            'data' => $documents,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $total > 0 ? ceil($total / $perPage) : 1,
            'query' => $query,
            'filters' => $filters
        ];
    }
    
    /**
     * Получить количество результатов
     */
    private function getSearchCount(string $query, array $filters = []): int
    {
        // Используем LIKE поиск для подсчета
        $searchTerm = $this->prepareLikeTerm($query);
        
        return Document::where(function($q) use ($searchTerm) {
                $q->where('title', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('content_text', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('keywords_text', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('detected_system', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('detected_component', 'LIKE', "%{$searchTerm}%");
            })
            ->when(!empty($filters), function($q) use ($filters) {
                foreach ($filters as $key => $value) {
                    if ($value !== null && $value !== '') {
                        $q->where($key, $value);
                    }
                }
            })
            ->where('is_parsed', true)
            ->where('status', 'active')
            ->count();
    }
    
    /**
     * Простой поиск для тестирования
     */
    public function simpleSearch(string $query, int $limit = 10): Collection
    {
        return Document::where(function($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('content_text', 'LIKE', "%{$query}%")
                  ->orWhere('keywords_text', 'LIKE', "%{$query}%");
            })
            ->where('is_parsed', true)
            ->where('status', 'active')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Подсветка найденного текста
     */
    private function highlightText(string $text, string $query, int $maxLength = 200): string
    {
        if (empty($text) || empty($query)) {
            return mb_substr($text, 0, $maxLength) . '...';
        }
        
        $searchTerm = $this->prepareLikeTerm($query);
        $words = explode(' ', $searchTerm);
        
        if (empty($words)) {
            return mb_substr($text, 0, $maxLength) . '...';
        }
        
        // Находим позицию первого совпадения
        $firstPos = null;
        foreach ($words as $word) {
            if (mb_strlen($word) < 2) continue;
            
            $pos = mb_stripos($text, $word);
            if ($pos !== false && ($firstPos === null || $pos < $firstPos)) {
                $firstPos = $pos;
            }
        }
        
        if ($firstPos === null) {
            return mb_substr($text, 0, $maxLength) . '...';
        }
        
        // Вырезаем фрагмент с контекстом
        $start = max(0, $firstPos - 50);
        $snippet = mb_substr($text, $start, $maxLength);
        
        // Подсвечиваем слова
        foreach ($words as $word) {
            if (mb_strlen($word) < 2) continue;
            
            $snippet = preg_replace(
                '/(' . preg_quote($word, '/') . ')/iu',
                '<mark>$1</mark>',
                $snippet
            );
        }
        
        return ($start > 0 ? '...' : '') . $snippet . '...';
    }
    
    /**
     * Генерация сниппета с контекстом
     */
    private function generateSnippet(string $text, string $query, int $length = 250): string
    {
        return $this->highlightText($text, $query, $length);
    }
    
    /**
     * Группировка результатов
     */
    private function groupResults(array $results): array
    {
        $grouped = [
            'high_relevance' => [],
            'medium_relevance' => [],
            'low_relevance' => []
        ];
        
        foreach ($results as $result) {
            $score = $result['relevance_score'] ?? 0;
            
            if ($score > 50) {
                $grouped['high_relevance'][] = $result;
            } elseif ($score > 10) {
                $grouped['medium_relevance'][] = $result;
            } else {
                $grouped['low_relevance'][] = $result;
            }
        }
        
        return $grouped;
    }
    
    /**
     * Подготовка термина для LIKE поиска
     */
    private function prepareLikeTerm(string $term): string
    {
        $term = mb_strtolower($term);
        $term = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $term);
        $term = preg_replace('/\s+/', ' ', $term);
        
        return trim($term);
    }
    
    /**
     * Логирование поискового запроса
     */
    private function logSearch(string $query, array $filters, int $resultsCount): void
    {
        try {
            if (class_exists('App\Models\SearchLog')) {
                SearchLog::create([
                    'query' => $query,
                    'filters' => json_encode($filters),
                    'results_count' => $resultsCount,
                    'response_time' => microtime(true) - LARAVEL_START,
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip(),
                    'user_id' => auth()->id() ?? null,
                    'search_type' => 'standard'
                ]);
            }
        } catch (\Exception $e) {
            // Не прерываем выполнение при ошибке логирования
            \Log::error('Failed to log search', [
                'error' => $e->getMessage(),
                'query' => $query
            ]);
        }
    }
    
    /**
     * Получить популярные поисковые запросы
     */
    public function getPopularSearches(int $limit = 10): Collection
    {
        if (class_exists('App\Models\SearchLog')) {
            return SearchLog::select('query', DB::raw('COUNT(*) as search_count'))
                ->groupBy('query')
                ->orderBy('search_count', 'desc')
                ->limit($limit)
                ->get();
        }
        
        return collect();
    }
    
    /**
     * Тестирование поиска
     */
    public function testSearch(): array
    {
        $testQueries = [
            'двигатель',
            'масло',
            'тормоз',
            'ремонт',
            'диагностика'
        ];
        
        $results = [];
        
        foreach ($testQueries as $query) {
            $count = $this->getSearchCount($query);
            $results[$query] = $count;
        }
        
        return $results;
    }
}