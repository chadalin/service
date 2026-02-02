<?php
// app/Traits/SearchableTrait.php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait SearchableTrait
{
    /**
     * Выполнить полнотекстовый поиск (MySQL)
     */
    public function scopeFulltextSearch($query, $searchTerm, $filters = [], $limit = 20, $offset = 0)
    {
        $searchTerm = $this->prepareFulltextTerm($searchTerm);
        
        if (empty($searchTerm)) {
            return $query->where('id', 0); // Возвращаем пустой результат
        }
        
        return $query->selectRaw("
                *,
                MATCH(title, content_text, keywords_text, detected_system, detected_component) 
                AGAINST(? IN BOOLEAN MODE) as relevance_score
            ", [$searchTerm])
            ->whereRaw("
                MATCH(title, content_text, keywords_text, detected_system, detected_component) 
                AGAINST(? IN BOOLEAN MODE)
            ", [$searchTerm])
            ->when(isset($filters['car_model_id']), function ($q) use ($filters) {
                return $q->where('car_model_id', $filters['car_model_id']);
            })
            ->when(isset($filters['category_id']), function ($q) use ($filters) {
                return $q->where('category_id', $filters['category_id']);
            })
            ->when(isset($filters['file_type']), function ($q) use ($filters) {
                return $q->where('file_type', $filters['file_type']);
            })
            ->when(isset($filters['min_parsing_quality']), function ($q) use ($filters) {
                return $q->where('parsing_quality', '>=', $filters['min_parsing_quality']);
            })
            ->where('is_parsed', true)
            ->where('status', 'active')
            ->orderBy('relevance_score', 'desc')
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->offset($offset);
    }

    /**
     * Поиск по страницам документа (MySQL)
     */
    public function scopeSearchPages($query, $searchTerm, $documentId = null, $limit = 10)
    {
        $searchTerm = $this->prepareFulltextTerm($searchTerm);
        
        if (empty($searchTerm)) {
            return collect();
        }
        
        $pagesQuery = \App\Models\DocumentPage::selectRaw("
                *,
                MATCH(section_title, content_text) 
                AGAINST(? IN BOOLEAN MODE) as page_relevance
            ", [$searchTerm])
            ->whereRaw("
                MATCH(section_title, content_text) 
                AGAINST(? IN BOOLEAN MODE)
            ", [$searchTerm])
            ->where('status', 'active');
        
        if ($documentId) {
            $pagesQuery->where('document_id', $documentId);
        }
        
        return $pagesQuery
            ->orderBy('page_relevance', 'desc')
            ->orderBy('page_number', 'asc')
            ->limit($limit);
    }

    /**
     * LIKE-поиск для автодополнения
     */
    public function scopeAutocomplete($query, $term, $limit = 10)
    {
        return $query->select(
                'id',
                'title',
                'detected_system',
                'detected_component',
                'file_type',
                'car_model_id'
            )
            ->where(function($q) use ($term) {
                $q->where('title', 'LIKE', "%{$term}%")
                  ->orWhere('detected_system', 'LIKE', "%{$term}%")
                  ->orWhere('detected_component', 'LIKE', "%{$term}%")
                  ->orWhere('keywords_text', 'LIKE', "%{$term}%");
            })
            ->where('is_parsed', true)
            ->where('status', 'active')
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Комбинированный поиск (FULLTEXT + LIKE)
     */
    public function scopeSearch($query, $searchTerm, $filters = [], $limit = 20, $offset = 0)
    {
        // Сначала пробуем FULLTEXT поиск
        $fulltextTerm = $this->prepareFulltextTerm($searchTerm);
        
        if (!empty($fulltextTerm)) {
            return $this->scopeFulltextSearch($query, $searchTerm, $filters, $limit, $offset);
        }
        
        // Если FULLTEXT не сработал (короткие слова), используем LIKE
        return $this->scopeLikeSearch($query, $searchTerm, $filters, $limit, $offset);
    }

    /**
     * LIKE-поиск для коротких слов
     */
    public function scopeLikeSearch($query, $searchTerm, $filters = [], $limit = 20, $offset = 0)
    {
        $searchTerm = $this->prepareLikeTerm($searchTerm);
        
        return $query->selectRaw("
                *,
                CASE 
                    WHEN title LIKE ? THEN 100
                    WHEN content_text LIKE ? THEN 80
                    WHEN keywords_text LIKE ? THEN 120
                    WHEN detected_system LIKE ? THEN 90
                    WHEN detected_component LIKE ? THEN 90
                    ELSE 0
                END as relevance_score
            ", [
                "%{$searchTerm}%",
                "%{$searchTerm}%",
                "%{$searchTerm}%",
                "%{$searchTerm}%",
                "%{$searchTerm}%"
            ])
            ->where(function($q) use ($searchTerm) {
                $q->where('title', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('content_text', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('keywords_text', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('detected_system', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('detected_component', 'LIKE', "%{$searchTerm}%");
            })
            ->when(isset($filters['car_model_id']), function ($q) use ($filters) {
                return $q->where('car_model_id', $filters['car_model_id']);
            })
            ->when(isset($filters['category_id']), function ($q) use ($filters) {
                return $q->where('category_id', $filters['category_id']);
            })
            ->when(isset($filters['file_type']), function ($q) use ($filters) {
                return $q->where('file_type', $filters['file_type']);
            })
            ->where('is_parsed', true)
            ->where('status', 'active')
            ->orderBy('relevance_score', 'desc')
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->offset($offset);
    }

    /**
     * Подготовка поискового запроса для MySQL FULLTEXT
     */
    private function prepareFulltextTerm(string $term): string
    {
        $term = Str::lower($term);
        $term = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $term);
        $term = preg_replace('/\s+/', ' ', $term);
        $term = trim($term);
        
        if (empty($term)) {
            return '';
        }
        
        // Разбиваем на слова и фильтруем короткие слова
        $words = explode(' ', $term);
        $words = array_filter($words, function($word) {
            return mb_strlen($word) >= 3; // MySQL FULLTEXT min word length
        });
        
        if (empty($words)) {
            return '';
        }
        
        // Формируем запрос для BOOLEAN MODE
        $booleanQuery = [];
        foreach ($words as $word) {
            $booleanQuery[] = "+{$word}*";
        }
        
        return implode(' ', $booleanQuery);
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
     * Похожие документы
     */
    public function scopeSimilarDocuments($query, $documentId, $limit = 5)
    {
        $document = self::find($documentId);
        
        if (!$document) {
            return collect();
        }

        return $query->where('id', '!=', $documentId)
            ->where(function($q) use ($document) {
                if ($document->detected_system) {
                    $q->orWhere('detected_system', $document->detected_system);
                }
                if ($document->detected_component) {
                    $q->orWhere('detected_component', $document->detected_component);
                }
                if ($document->category_id) {
                    $q->orWhere('category_id', $document->category_id);
                }
            })
            ->where('is_parsed', true)
            ->where('status', 'active')
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Увеличить счетчик поиска
     */
    public function incrementSearchCount(): void
    {
        $this->increment('search_count');
    }

    /**
     * Увеличить счетчик просмотров
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }
}