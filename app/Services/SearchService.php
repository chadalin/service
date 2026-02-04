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
     * Поиск с пагинацией (исправленный под вашу структуру)
     */
    public function paginatedSearch(string $query, array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        
        // Используем исправленный scope search с вашей структурой
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
     * Получить количество результатов (исправленный)
     */
    private function getSearchCount(string $query, array $filters = []): int
    {
        $searchTerm = $this->prepareLikeTerm($query);
        $likeTerm = '%' . str_replace(' ', '%', $searchTerm) . '%';
        
        return Document::where(function($q) use ($likeTerm) {
                $q->where('title', 'LIKE', $likeTerm)
                  ->orWhere('content_text', 'LIKE', $likeTerm);
                
                // Поиск по detected_system и detected_component если они есть
                $q->orWhere('detected_system', 'LIKE', $likeTerm)
                  ->orWhere('detected_component', 'LIKE', $likeTerm);
                
                // Поиск по keywords (JSON поле)
                $q->orWhere('keywords', 'LIKE', '%"' . str_replace('%', '', $searchTerm) . '"%');
            })
            ->when(!empty($filters), function($q) use ($filters) {
                foreach ($filters as $key => $value) {
                    if ($value !== null && $value !== '') {
                        $q->where($key, $value);
                    }
                }
            })
            ->where('is_parsed', true)
            ->where('status', 'processed')
            ->count();
    }
    
    /**
     * Подсветка найденного текста
     */
    public function highlightText(string $text, string $query, int $maxLength = 200): string
    {
        if (empty($text) || empty($query)) {
            return mb_substr($text, 0, $maxLength) . '...';
        }
        
        $searchTerm = $this->prepareLikeTerm($query);
        $words = explode(' ', $searchTerm);
        $words = array_filter($words, function($word) {
            return mb_strlen($word) > 2;
        });
        
        if (empty($words)) {
            return mb_substr($text, 0, $maxLength) . '...';
        }
        
        // Находим позицию первого совпадения
        $firstPos = null;
        foreach ($words as $word) {
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
            $snippet = preg_replace(
                '/(' . preg_quote($word, '/') . ')/iu',
                '<mark>$1</mark>',
                $snippet
            );
        }
        
        return ($start > 0 ? '...' : '') . $snippet . '...';
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
            \Log::error('Failed to log search', [
                'error' => $e->getMessage(),
                'query' => $query
            ]);
        }
    }
    
    /**
     * Простой рабочий поиск для проверки
     */
    public function simpleSearch(string $query, int $limit = 10): Collection
    {
        $searchTerm = $this->prepareLikeTerm($query);
        $likeTerm = '%' . str_replace(' ', '%', $searchTerm) . '%';
        
        return Document::where(function($q) use ($likeTerm) {
                $q->where('title', 'LIKE', $likeTerm)
                  ->orWhere('content_text', 'LIKE', $likeTerm)
                  ->orWhere('detected_system', 'LIKE', $likeTerm)
                  ->orWhere('detected_component', 'LIKE', $likeTerm);
            })
            ->where('is_parsed', true)
            ->where('status', 'processed')
            ->orderBy('average_relevance', 'desc')
            ->limit($limit)
            ->get();
    }
}