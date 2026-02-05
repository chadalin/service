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
    
    try {
        // Проверяем наличие FULLTEXT индекса
        $useFulltext = $this->hasFulltextIndex('document_pages', 'ft_content_section');
        
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
        
    } catch (\Exception $e) {
        \Log::error('Hybrid search error: ' . $e->getMessage(), [
            'query' => $query,
            'filters' => $filters,
            'trace' => $e->getTraceAsString()
        ]);
        
        // Fallback на простой LIKE поиск при любой ошибке
        $results = $this->likeSearchWithPages($query, $filters, $perPage, $offset);
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        return [
            'data' => $results['data'],
            'total' => $results['total'],
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $results['total'] > 0 ? ceil($results['total'] / $perPage) : 1,
            'query' => $query,
            'filters' => $filters,
            'search_type' => 'like_fallback',
            'execution_time_ms' => $executionTime,
            'suggestions' => [],
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Упрощенный поиск документов для AI диагностики (гарантированно работает)
 */
public function searchDocumentsForAi(string $query, array $filters = [], int $limit = 5): array
{
    try {
        // Очищаем запрос
        $query = trim($query);
        if (empty($query)) {
            return [];
        }
        
        // Подготавливаем поисковый термин
        $searchTerm = $this->prepareLikeTerm($query);
        
        // Если запрос слишком короткий, возвращаем пустой результат
        if (mb_strlen($searchTerm) < 2) {
            \Log::warning('Search term too short', ['searchTerm' => $searchTerm]);
            return [];
        }
        
        $likeTerm = '%' . str_replace(' ', '%', $searchTerm) . '%';
        
        \Log::debug('AI Document search query:', [
            'original' => $query,
            'searchTerm' => $searchTerm,
            'likeTerm' => $likeTerm,
            'filters' => $filters
        ]);
        
        // Базовый запрос
        $queryBuilder = DB::table('document_pages')
            ->select([
                'document_pages.id as page_id',
                'document_pages.document_id',
                'document_pages.page_number',
                'document_pages.content_text',
                'document_pages.section_title',
                'documents.id',
                'documents.title as document_title',
                'documents.file_type',
                'documents.source_url',
                'documents.view_count',
                'documents.detected_system',
                'documents.detected_component',
                'documents.car_model_id',
            ])
            ->join('documents', 'document_pages.document_id', '=', 'documents.id')
            ->where(function($q) use ($likeTerm) {
                $q->where('document_pages.content_text', 'LIKE', $likeTerm)
                  ->orWhere('document_pages.section_title', 'LIKE', $likeTerm)
                  ->orWhere('documents.title', 'LIKE', $likeTerm);
            })
            ->whereNotNull('document_pages.content_text')
            ->where('document_pages.content_text', '<>', '')
            ->where('documents.is_parsed', 1);
        
        // Применяем фильтры
        if (!empty($filters['car_model_id'])) {
            $queryBuilder->where('documents.car_model_id', $filters['car_model_id']);
        }
        
        if (!empty($filters['brand_id'])) {
            $queryBuilder->whereHas('document.carModel', function($q) use ($filters) {
                $q->where('brand_id', $filters['brand_id']);
            });
        }
        
        // Выполняем запрос
        $pages = $queryBuilder
            ->orderBy('documents.view_count', 'desc')
            ->orderBy('document_pages.page_number')
            ->limit($limit * 3) // Берем больше для группировки
            ->get();
        
        \Log::debug('AI Document search raw results:', [
            'pages_count' => $pages->count()
        ]);
        
        if ($pages->isEmpty()) {
            \Log::debug('No pages found for query');
            return [];
        }
        
        // Группируем по документам (берем первый результат для каждого документа)
        $groupedDocuments = [];
        foreach ($pages as $page) {
            $docId = $page->document_id;
            
            if (!isset($groupedDocuments[$docId]) && count($groupedDocuments) < $limit) {
                // Создаем отрывок из контента
                $excerpt = $this->highlightText($page->content_text ?? '', $query, 150);
                if (empty($excerpt) && !empty($page->section_title)) {
                    $excerpt = $page->section_title;
                }
                
                $groupedDocuments[$docId] = [
                    'id' => $docId,
                    'title' => $page->document_title ?? 'Документ',
                    'file_type' => $page->file_type ?? 'pdf',
                    'source_url' => $page->source_url ?? '',
                    'detected_system' => $page->detected_system ?? '',
                    'detected_component' => $page->detected_component ?? '',
                    'view_count' => $page->view_count ?? 0,
                    'best_page' => $page->page_number ?? 1,
                    'excerpt' => $excerpt,
                ];
            }
        }
        
        $result = array_values($groupedDocuments);
        \Log::debug('AI Document search grouped results:', [
            'documents_count' => count($result)
        ]);
        
        return $result;
        
    } catch (\Exception $e) {
        \Log::error('AI document search error: ' . $e->getMessage(), [
            'query' => $query,
            'trace' => $e->getTraceAsString()
        ]);
        
        return [];
    }
}
    
   /**
 * FULLTEXT поиск по страницам документов
 */
private function fulltextSearchWithPages(string $query, array $filters, int $limit, int $offset): array
{
    $preparedQuery = $this->prepareFulltextQuery($query);
    
    // Если подготовленный запрос пустой, возвращаем пустой результат
    if (empty($preparedQuery)) {
        return ['data' => collect(), 'total' => 0];
    }
    
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
    
    if (!empty($filters['status'])) {
        $baseQuery->where('documents.status', $filters['status']);
    }
    
    try {
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
        
    } catch (\Exception $e) {
        \Log::error('FULLTEXT search error: ' . $e->getMessage(), [
            'query' => $query,
            'prepared_query' => $preparedQuery,
            'trace' => $e->getTraceAsString()
        ]);
        
        // Fallback на LIKE поиск при ошибке
        return $this->likeSearchWithPages($query, $filters, $limit, $offset);
    }
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
        
        if (!empty($filters['status'])) {
            $baseQuery->where('documents.status', $filters['status']);
        }
        
        $data = (clone $baseQuery)
            ->limit($limit)
            ->offset($offset)
            ->get();
        
        $total = (clone $baseQuery)->count();
        
        $data->transform(function($item) use ($query) {
            $item->highlighted_text = $this->highlightText($item->content_text, $query, 200);
            $item->highlighted_title = $this->highlightText($item->document_title, $query, 100);
            return $item;
        });
        
        return ['data' => $data, 'total' => $total];
    }
    
    /**
     * Получить поисковые подсказки
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
     * Логирование поискового запроса (старая версия для обратной совместимости)
     */
    private function logSearchOld(string $query, array $filters, int $resultsCount): void
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

    /**
     * Создание FULLTEXT индексов если их нет
     */
    public function createFulltextIndexes(): array
    {
        $created = [];
        
        try {
            // Проверяем существование индексов
            $existingIndexes = DB::select("
                SHOW INDEX FROM document_pages 
                WHERE Index_type = 'FULLTEXT' 
                AND Key_name = 'ft_content_section'
            ");
            
            if (empty($existingIndexes)) {
                // Создаем FULLTEXT индекс для document_pages
                DB::statement("
                    CREATE FULLTEXT INDEX ft_content_section 
                    ON document_pages (content_text, section_title)
                ");
                $created[] = 'document_pages (content_text, section_title)';
                \Log::info('Created FULLTEXT index for document_pages');
            }
            
            // Проверяем индекс для documents
            $existingDocIndexes = DB::select("
                SHOW INDEX FROM documents 
                WHERE Index_type = 'FULLTEXT' 
                AND Key_name = 'ft_document_search'
            ");
            
            if (empty($existingDocIndexes)) {
                // Создаем FULLTEXT индекс для documents
                DB::statement("
                    CREATE FULLTEXT INDEX ft_document_search 
                    ON documents (title, content_text, detected_system, detected_component)
                ");
                $created[] = 'documents (title, content_text, detected_system, detected_component)';
                \Log::info('Created FULLTEXT index for documents');
            }
            
        } catch (\Exception $e) {
            \Log::error('Failed to create FULLTEXT indexes: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            // Если ошибка связана с правами или типом таблицы, используем альтернативный подход
            $errorMessage = strtolower($e->getMessage());
            
            if (str_contains($errorMessage, 'myisam') || str_contains($errorMessage, 'storage engine')) {
                \Log::warning('MySQL table might not be MyISAM. FULLTEXT requires MyISAM or InnoDB (MySQL 5.6+).');
                
                // Проверяем тип таблицы
                try {
                    $tableInfo = DB::select("SHOW TABLE STATUS LIKE 'document_pages'");
                    if (!empty($tableInfo)) {
                        \Log::warning("Table engine: " . $tableInfo[0]->Engine);
                    }
                } catch (\Exception $e2) {
                    // Игнорируем
                }
            }
        }
        
        return $created;
    }

    /**
     * Проверка наличия FULLTEXT индекса
     */
    private function hasFulltextIndex(string $table, string $indexName = null): bool
    {
        try {
            $query = "SHOW INDEX FROM {$table} WHERE Index_type = 'FULLTEXT'";
            if ($indexName) {
                $query .= " AND Key_name = '{$indexName}'";
            }
            
            $indexes = DB::select($query);
            return !empty($indexes);
        } catch (\Exception $e) {
            \Log::warning("Cannot check FULLTEXT index for {$table}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Тестовый поиск для проверки производительности
     */
    public function testSearchPerformance(): array
    {
        $results = [];
        $testQueries = [
            'двигатель',
            'масло двигатель', 
            'тормозная система',
            'замена масла',
            'диагностика двигателя'
        ];
        
        foreach ($testQueries as $query) {
            $startTime = microtime(true);
            
            // Тестируем разные методы поиска
            $likeResults = $this->simpleSearch($query, 10);
            $likeTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $startTime = microtime(true);
            try {
                $fulltextResults = $this->fulltextSearch($query, 'document_pages', 10);
                $fulltextTime = round((microtime(true) - $startTime) * 1000, 2);
            } catch (\Exception $e) {
                $fulltextResults = collect();
                $fulltextTime = 'Error: ' . $e->getMessage();
            }
            
            $results[$query] = [
                'like_time_ms' => $likeTime,
                'like_count' => $likeResults->count(),
                'fulltext_time_ms' => $fulltextTime,
                'fulltext_count' => is_object($fulltextResults) ? $fulltextResults->count() : 0,
            ];
        }
        
        return $results;
    }

    /**
     * Простой FULLTEXT поиск (для тестирования)
     */
    public function fulltextSearch(string $query, string $table = 'document_pages', int $limit = 10)
    {
        $preparedQuery = $this->prepareFulltextQuery($query);
        
        if ($table === 'document_pages') {
            return DB::table($table)
                ->select('*')
                ->whereRaw("MATCH(content_text, section_title) AGAINST(? IN BOOLEAN MODE)", [$preparedQuery])
                ->limit($limit)
                ->get();
        } else {
            return DB::table($table)
                ->select('*')
                ->whereRaw("MATCH(title, content_text, detected_system, detected_component) AGAINST(? IN BOOLEAN MODE)", [$preparedQuery])
                ->limit($limit)
                ->get();
        }
    }

    /**
     * Подготовка запроса для FULLTEXT BOOLEAN MODE
     */
    private function prepareFulltextQuery(string $query): string
{
    $query = mb_strtolower(trim($query));
    
    // Если запрос слишком короткий, возвращаем пустую строку (будем использовать LIKE)
    if (mb_strlen($query) < 2) {
        return '';
    }
    
    // Разбиваем на слова
    $words = preg_split('/\s+/', $query);
    $words = array_filter($words, function($word) {
        $word = trim($word);
        return mb_strlen($word) > 1 && !$this->isStopWord($word);
    });
    
    // Если после фильтрации слов не осталось
    if (empty($words)) {
        return '';
    }
    
    $booleanTerms = [];
    foreach ($words as $word) {
        $word = preg_replace('/[^\p{L}\p{N}_-]/u', '', $word); // Очищаем от специальных символов
        
        if (mb_strlen($word) < 2) {
            continue;
        }
        
        // Если слово не начинается с минуса (исключение)
        if (!str_starts_with($word, '-')) {
            $booleanTerms[] = '+' . $word . '*'; // + для обязательного слова, * для префикса
        } else {
            $booleanTerms[] = substr($word, 1) . '* -' . substr($word, 1); // Для отрицательных
        }
    }
    
    if (empty($booleanTerms)) {
        return '';
    }
    
    return implode(' ', $booleanTerms);
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
            'для', 'чего', 'при', 'без', 'над', 'под', 'перед', 'после', 'во', 'со',
            'то', 'бы', 'же', 'ли', 'быть', 'стать', 'свой', 'наш', 'ваш', 'их'
        ];
        
        return in_array(mb_strtolower($word), $stopWords);
    }

    /**
     * Упрощенный поиск для AI диагностики (совместимый с текущим контроллером)
     */
    public function searchForAiDiagnostic(string $query, array $filters = [], int $limit = 5): array
    {
        $searchTerm = $this->prepareLikeTerm($query);
        $likeTerm = '%' . str_replace(' ', '%', $searchTerm) . '%';
        
        $pages = DB::table('document_pages')
            ->select([
                'document_pages.id',
                'document_pages.document_id',
                'document_pages.page_number',
                'document_pages.content_text',
                'document_pages.section_title',
                'documents.title as document_title',
                'documents.file_type',
                'documents.source_url',
                'documents.view_count',
                'documents.detected_system',
                'documents.detected_component',
                'documents.car_model_id',
            ])
            ->join('documents', 'document_pages.document_id', '=', 'documents.id')
            ->where(function($q) use ($likeTerm) {
                $q->where('document_pages.content_text', 'LIKE', $likeTerm)
                  ->orWhere('document_pages.section_title', 'LIKE', $likeTerm)
                  ->orWhere('documents.title', 'LIKE', $likeTerm);
            })
            ->whereNotNull('document_pages.content_text')
            ->where('document_pages.content_text', '<>', '')
            ->where('documents.is_parsed', true)
            ->orderBy('documents.view_count', 'desc')
            ->orderBy('document_pages.page_number')
            ->limit($limit)
            ->get();
        
        // Группируем по документам
        $groupedDocuments = [];
        foreach ($pages as $page) {
            $docId = $page->document_id;
            
            if (!isset($groupedDocuments[$docId])) {
                $groupedDocuments[$docId] = [
                    'id' => $docId,
                    'title' => $page->document_title,
                    'file_type' => $page->file_type,
                    'source_url' => $page->source_url,
                    'detected_system' => $page->detected_system,
                    'detected_component' => $page->detected_component,
                    'view_count' => $page->view_count,
                    'best_page' => $page->page_number,
                    'excerpt' => $this->highlightText($page->content_text, $query, 150),
                ];
            }
        }
        
        return array_values($groupedDocuments);
    }
}