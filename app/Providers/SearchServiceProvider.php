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
     * Гибридный поиск с использованием FULLTEXT и LIKE
     */
    public function hybridSearch(string $query, array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $startTime = microtime(true);
        $offset = ($page - 1) * $perPage;
        
        // Проверяем наличие FULLTEXT индекса
        $useFulltext = $this->hasFulltextIndex('document_pages', 'content_text');
        
        if ($useFulltext && strlen($query) > 2) {
            $results = $this->fulltextSearchWithPages($query, $filters, $perPage, $offset);
        } else {
            $results = $this->likeSearchWithPages($query, $filters, $perPage, $offset);
        }
        
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        // Логируем поиск
        $this->logSearch($query, $filters, $results['total'], $executionTime, $useFulltext ? 'fulltext' : 'like');
        
        return [
            'data' => $results['data'],
            'total' => $results['total'],
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $results['total'] > 0 ? ceil($results['total'] / $perPage) : 1,
            'query' => $query,
            'filters' => $filters,
            'search_type' => $useFulltext ? 'fulltext' : 'like',
            'execution_time_ms' => $executionTime,
            'suggestions' => $this->getSuggestions($query)
        ];
    }
    
    /**
     * FULLTEXT поиск по страницам документов
     */
    private function fulltextSearchWithPages(string $query, array $filters, int $limit, int $offset): array
    {
        $preparedQuery = $this->prepareFulltextQuery($query);
        
        $baseQuery = DB::table('document_pages')
            ->select([
                'document_pages.id',
                'document_pages.document_id',
                'document_pages.page_number',
                'document_pages.content_text',
                'document_pages.section_title',
                'document_pages.word_count',
                'documents.title as document_title',
                'documents.file_type',
                'documents.source_url',
                'documents.view_count',
                'documents.detected_system',
                'documents.detected_component',
                'documents.car_model_id',
                DB::raw("MATCH(document_pages.content_text, document_pages.section_title) 
                        AGAINST(? IN BOOLEAN MODE) as relevance_score")
            ])
            ->join('documents', 'document_pages.document_id', '=', 'documents.id')
            ->whereRaw("MATCH(document_pages.content_text, document_pages.section_title) 
                      AGAINST(? IN BOOLEAN MODE)", [$preparedQuery])
            ->whereNotNull('document_pages.content_text')
            ->where('document_pages.content_text', '<>', '')
            ->where('documents.is_parsed', true)
            ->orderBy('relevance_score', 'desc')
            ->orderBy('documents.view_count', 'desc');
        
        // Применяем фильтры
        if (!empty($filters['car_model_id'])) {
            $baseQuery->where('documents.car_model_id', $filters['car_model_id']);
        }
        
        if (!empty($filters['detected_system'])) {
            $baseQuery->where('documents.detected_system', 'LIKE', "%{$filters['detected_system']}%");
        }
        
        // Получаем данные
        $data = (clone $baseQuery)
            ->limit($limit)
            ->offset($offset)
            ->get();
        
        // Считаем общее количество
        $total = (clone $baseQuery)->count();
        
        // Добавляем подсветку
        $data->transform(function($item) use ($query) {
            $item->highlighted_text = $this->highlightText($item->content_text, $query, 200);
            $item->highlighted_title = $this->highlightText($item->document_title, $query, 100);
            return $item;
        });
        
        return ['data' => $data, 'total' => $total];
    }
    
    /**
     * LIKE поиск (fallback)
     */
    private function likeSearchWithPages(string $query, array $filters, int $limit, int $offset): array
    {
        $searchTerm = $this->prepareLikeTerm($query);
        $likeTerm = '%' . str_replace(' ', '%', $searchTerm) . '%';
        
        $baseQuery = DB::table('document_pages')
            ->select([
                'document_pages.id',
                'document_pages.document_id',
                'document_pages.page_number',
                'document_pages.content_text',
                'document_pages.section_title',
                'document_pages.word_count',
                'documents.title as document_title',
                'documents.file_type',
                'documents.source_url',
                'documents.view_count',
                'documents.detected_system',
                'documents.detected_component',
                'documents.car_model_id',
                DB::raw("1 as relevance_score")
            ])
            ->join('documents', 'document_pages.document_id', '=', 'documents.id')
            ->where(function($q) use ($likeTerm) {
                $q->where('document_pages.content_text', 'LIKE', $likeTerm)
                  ->orWhere('document_pages.section_title', 'LIKE', $likeTerm)
                  ->orWhere('documents.title', 'LIKE', $likeTerm)
                  ->orWhere('documents.detected_system', 'LIKE', $likeTerm)
                  ->orWhere('documents.detected_component', 'LIKE', $likeTerm);
            })
            ->whereNotNull('document_pages.content_text')
            ->where('document_pages.content_text', '<>', '')
            ->where('documents.is_parsed', true)
            ->orderBy('documents.view_count', 'desc')
            ->orderBy('document_pages.page_number');
        
        // Фильтры
        if (!empty($filters['car_model_id'])) {
            $baseQuery->where('documents.car_model_id', $filters['car_model_id']);
        }
        
        $data = (clone $baseQuery)
            ->limit($limit)
            ->offset($offset)
            ->get();
        
        $total = (clone $baseQuery)->count();
        
        $data->transform(function($item) use ($query) {
            $item->highlighted_text = $this->highlightText($item->content_text, $query, 200);
            return $item;
        });
        
        return ['data' => $data, 'total' => $total];
    }
    
    /**
     * Подготовка запроса для FULLTEXT BOOLEAN MODE
     */
    private function prepareFulltextQuery(string $query): string
    {
        $query = mb_strtolower(trim($query));
        $words = array_filter(preg_split('/\s+/', $query), function($word) {
            $word = trim($word);
            return mb_strlen($word) > 2 && !$this->isStopWord($word);
        });
        
        if (empty($words)) {
            return '*';
        }
        
        $booleanTerms = [];
        foreach ($words as $word) {
            if (!str_starts_with($word, '-')) {
                // Обязательное слово с поддержкой окончаний
                $booleanTerms[] = '+' . $word . '*';
            } else {
                // Исключение слова
                $booleanTerms[] = $word;
            }
        }
        
        return implode(' ', $booleanTerms);
    }
    
    /**
     * Проверка наличия FULLTEXT индекса
     */
    private function hasFulltextIndex(string $table, string $column = null): bool
    {
        try {
            $indexes = DB::select("
                SHOW INDEX FROM {$table} 
                WHERE Index_type = 'FULLTEXT'
                " . ($column ? "AND Column_name = '{$column}'" : "") . "
            ");
            
            return !empty($indexes);
        } catch (\Exception $e) {
            \Log::warning("Cannot check FULLTEXT index for {$table}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Создание FULLTEXT индексов если их нет
     */
    public function createFulltextIndexes(): array
    {
        $created = [];
        
        // Для document_pages
        if (!$this->hasFulltextIndex('document_pages')) {
            try {
                DB::statement("
                    CREATE FULLTEXT INDEX ft_content_section 
                    ON document_pages (content_text, section_title)
                ");
                $created[] = 'document_pages.content_text + section_title';
            } catch (\Exception $e) {
                \Log::error("Failed to create FULLTEXT index for document_pages: " . $e->getMessage());
            }
        }
        
        // Для documents (опционально)
        if (!$this->hasFulltextIndex('documents')) {
            try {
                DB::statement("
                    CREATE FULLTEXT INDEX ft_document_search 
                    ON documents (title, content_text, detected_system, detected_component)
                ");
                $created[] = 'documents.title + content_text + detected_system + detected_component';
            } catch (\Exception $e) {
                \Log::error("Failed to create FULLTEXT index for documents: " . $e->getMessage());
            }
        }
        
        return $created;
    }
    
    /**
     * Проверка стоп-слов
     */
    private function isStopWord(string $word): bool
    {
        $stopWords = [
            'и', 'или', 'но', 'на', 'в', 'с', 'по', 'у', 'о', 'об', 'от', 'до', 'за',
            'из', 'к', 'со', 'то', 'же', 'бы', 'ли', 'не', 'нет', 'да', 'как', 'что',
            'это', 'так', 'вот', 'ну', 'нужно', 'очень', 'можно', 'надо', 'этого',
            'для', 'чего', 'при', 'без', 'над', 'под', 'перед', 'после'
        ];
        
        return in_array(mb_strtolower($word), $stopWords);
    }
    
    /**
     * Получение поисковых подсказок
     */
    public function getSuggestions(string $query): array
    {
        if (strlen($query) < 2) {
            return [];
        }
        
        $cacheKey = 'search_suggestions_' . md5($query);
        
        return Cache::remember($cacheKey, $this->cacheDuration, function() use ($query) {
            $searchTerm = $this->prepareLikeTerm($query);
            $likeTerm = $searchTerm . '%';
            
            $suggestions = [];
            
            // Предложения из заголовков документов
            $titleSuggestions = Document::where('title', 'LIKE', $likeTerm)
                ->where('is_parsed', true)
                ->distinct()
                ->limit(5)
                ->pluck('title')
                ->toArray();
            
            // Предложения из систем
            $systemSuggestions = Document::where('detected_system', 'LIKE', $likeTerm)
                ->where('is_parsed', true)
                ->whereNotNull('detected_system')
                ->distinct()
                ->limit(5)
                ->pluck('detected_system')
                ->toArray();
            
            // Предложения из компонентов
            $componentSuggestions = Document::where('detected_component', 'LIKE', $likeTerm)
                ->where('is_parsed', true)
                ->whereNotNull('detected_component')
                ->distinct()
                ->limit(5)
                ->pluck('detected_component')
                ->toArray();
            
            $suggestions = array_merge($titleSuggestions, $systemSuggestions, $componentSuggestions);
            $suggestions = array_unique(array_filter($suggestions));
            
            // Сортируем по длине (более короткие сначала)
            usort($suggestions, function($a, $b) {
                return mb_strlen($a) <=> mb_strlen($b);
            });
            
            return array_slice($suggestions, 0, 10);
        });
    }
    
    /**
     * Получение похожих документов
     */
    public function getSimilarDocuments(int $documentId, int $limit = 5): Collection
    {
        $document = Document::find($documentId);
        if (!$document) {
            return collect();
        }
        
        $cacheKey = "similar_docs_{$documentId}_{$limit}";
        
        return Cache::remember($cacheKey, $this->cacheDuration, function() use ($document, $limit) {
            $keywords = $document->keywords ?? [];
            if (empty($keywords) || !is_array($keywords)) {
                return collect();
            }
            
            // Берем первые 3 ключевых слова
            $topKeywords = array_slice($keywords, 0, 3);
            
            return Document::where('id', '!=', $document->id)
                ->where(function($q) use ($topKeywords) {
                    foreach ($topKeywords as $keyword) {
                        $q->orWhere('title', 'LIKE', "%{$keyword}%")
                          ->orWhere('content_text', 'LIKE', "%{$keyword}%")
                          ->orWhere('detected_system', 'LIKE', "%{$keyword}%")
                          ->orWhere('detected_component', 'LIKE', "%{$keyword}%");
                    }
                })
                ->where('is_parsed', true)
                ->orderBy('view_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }
    
    /**
     * Обновление логирования поиска
     */
    private function logSearch(string $query, array $filters, int $resultsCount, float $executionTime, string $searchType): void
    {
        try {
            if (class_exists('App\Models\SearchLog')) {
                SearchLog::create([
                    'query' => $query,
                    'filters' => json_encode($filters),
                    'results_count' => $resultsCount,
                    'response_time' => $executionTime / 1000, // конвертируем обратно в секунды
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip(),
                    'user_id' => auth()->id() ?? null,
                    'search_type' => $searchType,
                    'execution_time_ms' => $executionTime
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
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->get();
    }
}