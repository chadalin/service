<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DiagnosticCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SemanticSearchEngine
{
    protected $stopWords = [];
    
    public function __construct()
    {
        // Русские стоп-слова для обработки текста
        $this->stopWords = [
            'и', 'в', 'во', 'не', 'что', 'он', 'на', 'я', 'с', 'со', 'как', 'а',
            'то', 'все', 'она', 'так', 'его', 'но', 'да', 'ты', 'к', 'у', 'же',
            'вы', 'за', 'бы', 'по', 'только', 'ее', 'мне', 'было', 'вот', 'от',
            'меня', 'еще', 'нет', 'о', 'из', 'ему', 'теперь', 'когда', 'даже',
            'ну', 'ли', 'если', 'уже', 'или', 'ни', 'быть', 'был', 'него', 'до',
            'вас', 'нибудь', 'опять', 'уж', 'вам', 'ведь', 'там', 'потом', 'себя',
            'ничего', 'ей', 'может', 'они', 'тут', 'где', 'есть', 'надо', 'ней',
            'для', 'мы', 'тебя', 'их', 'чем', 'была', 'сам', 'чтоб', 'без', 'будто',
            'чего', 'раз', 'тоже', 'себе', 'под', 'будет', 'ж', 'тогда', 'кто',
            'этот', 'того', 'потому', 'этого', 'какой', 'совсем', 'ним', 'здесь',
            'этом', 'один', 'почти', 'мой', 'тем', 'чтобы', 'нее', 'сейчас', 'были',
            'куда', 'зачем', 'всех', 'никогда', 'можно', 'при', 'наконец', 'два',
            'об', 'другой', 'хоть', 'после', 'над', 'больше', 'тот', 'через', 'эти',
            'нас', 'про', 'всего', 'них', 'какая', 'много', 'разве', 'три', 'эту',
            'моя', 'впрочем', 'хорошо', 'свою', 'этой', 'перед', 'иногда', 'лучше',
            'чуть', 'том', 'нельзя', 'такой', 'им', 'более', 'всегда', 'конечно',
            'всю', 'между'
        ];
    }
    
    /**
     * Семантический поиск с использованием TF-IDF и косинусного сходства
     */
    public function semanticSearch($query, $modelId = null, $categoryId = null)
    {
        try {
            // Препроцессинг запроса
            $processedQuery = $this->preprocessText($query);
            $queryTerms = $this->extractTerms($processedQuery);
            
            if (empty($queryTerms)) {
                return collect([]);
            }
            
            // Поиск в документах
            $documentResults = $this->searchDocumentsTFIDF($queryTerms, $modelId, $categoryId);
            
            // Поиск в диагностических кейсах
            $caseResults = $this->searchCasesTFIDF($queryTerms, $modelId);
            
            // Объединяем результаты
            $allResults = collect([])
                ->merge($documentResults)
                ->merge($caseResults)
                ->sortByDesc('semantic_similarity')
                ->values();
            
            return $allResults;
            
        } catch (\Exception $e) {
            Log::error('Semantic search error: ' . $e->getMessage());
            return collect([]);
        }
    }
    
    /**
     * Поиск документов с использованием TF-IDF
     */
    protected function searchDocumentsTFIDF($queryTerms, $modelId = null, $categoryId = null)
    {
        // Получаем все документы для анализа
        $documents = Document::query()
            ->with(['carModel.brand', 'category'])
            ->where('status', 'processed');
        
        if ($modelId) {
            $documents->where('car_model_id', $modelId);
        }
        
        if ($categoryId) {
            $documents->where('category_id', $categoryId);
        }
        
        $documents = $documents->limit(100)->get();
        
        if ($documents->isEmpty()) {
            return collect([]);
        }
        
        // Вычисляем IDF для терминов запроса
        $idf = $this->calculateIDF($documents, $queryTerms);
        
        // Вычисляем TF-IDF векторы и косинусное сходство
        return $documents->map(function($doc) use ($queryTerms, $idf) {
            // Получаем текст документа
            $text = $this->getDocumentText($doc);
            $docTerms = $this->extractTerms($text);
            
            // Вычисляем TF-IDF векторы
            $queryVector = $this->calculateTFIDFVector($queryTerms, $queryTerms, $idf);
            $docVector = $this->calculateTFIDFVector($docTerms, $queryTerms, $idf);
            
            // Вычисляем косинусное сходство
            $similarity = $this->cosineSimilarity($queryVector, $docVector);
            
            // Порог релевантности
            if ($similarity > 0.1) {
                return [
                    'id' => $doc->id,
                    'type' => 'document',
                    'title' => $doc->title,
                    'content_text' => $this->extractRelevantSnippet($text, $queryTerms),
                    'car_model' => $doc->carModel,
                    'category' => $doc->category,
                    'semantic_similarity' => $similarity,
                    'relevance_score' => $similarity,
                    'created_at' => $doc->created_at,
                    'url' => route('admin.documents.show', $doc->id)
                ];
            }
            
            return null;
        })->filter()->sortByDesc('semantic_similarity');
    }
    
    /**
     * Поиск диагностических кейсов с использованием TF-IDF
     */
    protected function searchCasesTFIDF($queryTerms, $modelId = null)
    {
        if (!class_exists(DiagnosticCase::class)) {
            return collect([]);
        }
        
        $cases = DiagnosticCase::query()
            ->with(['carModel.brand', 'symptoms'])
            ->where('status', 'resolved');
        
        if ($modelId) {
            $cases->where('car_model_id', $modelId);
        }
        
        $cases = $cases->limit(50)->get();
        
        if ($cases->isEmpty()) {
            return collect([]);
        }
        
        // Вычисляем IDF для терминов запроса
        $idf = $this->calculateIDF($cases, $queryTerms);
        
        return $cases->map(function($case) use ($queryTerms, $idf) {
            $text = $case->problem_description . ' ' . $case->diagnosis . ' ' . $case->solution;
            $caseTerms = $this->extractTerms($text);
            
            $queryVector = $this->calculateTFIDFVector($queryTerms, $queryTerms, $idf);
            $caseVector = $this->calculateTFIDFVector($caseTerms, $queryTerms, $idf);
            
            $similarity = $this->cosineSimilarity($queryVector, $caseVector);
            
            if ($similarity > 0.1) {
                return [
                    'id' => $case->id,
                    'type' => 'diagnostic_case',
                    'title' => 'Диагностический кейс: ' . substr($case->problem_description, 0, 50),
                    'content_text' => "Проблема: {$case->problem_description}\nДиагноз: {$case->diagnosis}",
                    'car_model' => $case->carModel,
                    'semantic_similarity' => $similarity,
                    'relevance_score' => $similarity,
                    'created_at' => $case->created_at,
                    'url' => route('diagnostic.report.show', $case->id)
                ];
            }
            
            return null;
        })->filter()->sortByDesc('semantic_similarity');
    }
    
    /**
     * Препроцессинг текста
     */
    protected function preprocessText($text)
    {
        // Приводим к нижнему регистру
        $text = mb_strtolower($text, 'UTF-8');
        
        // Удаляем спецсимволы, оставляем буквы, цифры и пробелы
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        
        // Удаляем лишние пробелы
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }
    
    /**
     * Извлечение терминов из текста
     */
    protected function extractTerms($text)
    {
        $text = $this->preprocessText($text);
        
        // Разбиваем на слова
        $words = explode(' ', $text);
        
        // Фильтруем стоп-слова и короткие слова
        $terms = array_filter($words, function($word) {
            return mb_strlen($word, 'UTF-8') > 2 && !in_array($word, $this->stopWords);
        });
        
        // Приводим к базовой форме (простая лемматизация для русского)
        $terms = array_map([$this, 'stemWord'], $terms);
        
        return array_values($terms);
    }
    
    /**
     * Простая стемматизация (приведение к основе) для русского языка
     */
    protected function stemWord($word)
    {
        // Простая реализация стеммера Портера для русского
        $word = $this->removeEndings($word);
        
        return $word;
    }
    
    /**
     * Удаление окончаний (упрощенная версия)
     */
    protected function removeEndings($word)
    {
        $endings = [
            // Существительные
            'ами', 'ями', 'иями', 'ями', 'ями', 'ев', 'ов', 'ий', 'ый', 'ой',
            'ем', 'ом', 'ам', 'ом', 'ем', 'ах', 'ях', 'иях', 'ях', 'ях',
            'и', 'ы', 'у', 'ю', 'а', 'я', 'о', 'е', 'ь', 'ей', 'ой', 'ем',
            
            // Прилагательные
            'ого', 'его', 'ому', 'ему', 'им', 'ым', 'ом', 'ем', 'их', 'ых',
            'ую', 'юю', 'ая', 'яя', 'ое', 'ее',
            
            // Глаголы
            'ить', 'ать', 'ять', 'еть', 'уть', 'ти', 'чь', 'ться', 'тся',
            'ла', 'ло', 'ли', 'л', 'ем', 'ете', 'ют', 'им', 'ите', 'ат', 'ят',
            'ует', 'уют', 'ал', 'ял', 'ил', 'ыл', 'ан', 'ян', 'ен', 'ён',
        ];
        
        foreach ($endings as $ending) {
            if (mb_substr($word, -mb_strlen($ending, 'UTF-8'), null, 'UTF-8') == $ending) {
                $word = mb_substr($word, 0, -mb_strlen($ending, 'UTF-8'), 'UTF-8');
                break;
            }
        }
        
        return $word;
    }
    
    /**
     * Получение текста документа
     */
    protected function getDocumentText(Document $document)
    {
        return $document->title . ' ' . 
               ($document->keywords ?: '') . ' ' . 
               substr($document->content_text, 0, 2000);
    }
    
    /**
     * Вычисление IDF (Inverse Document Frequency)
     */
    protected function calculateIDF($documents, $queryTerms)
    {
        $totalDocs = $documents->count();
        $idf = [];
        
        foreach ($queryTerms as $term) {
            $docCount = 0;
            
            foreach ($documents as $doc) {
                $text = $this->getDocumentText($doc);
                $docTerms = $this->extractTerms($text);
                
                if (in_array($term, $docTerms)) {
                    $docCount++;
                }
            }
            
            if ($docCount > 0) {
                $idf[$term] = log($totalDocs / $docCount);
            } else {
                $idf[$term] = 0;
            }
        }
        
        return $idf;
    }
    
    /**
     * Вычисление TF-IDF вектора
     */
    protected function calculateTFIDFVector($docTerms, $queryTerms, $idf)
    {
        $vector = [];
        
        foreach ($queryTerms as $term) {
            // TF (Term Frequency) - частота термина в документе
            $tf = count(array_keys($docTerms, $term));
            
            // TF-IDF = TF * IDF
            $vector[$term] = $tf * ($idf[$term] ?? 0);
        }
        
        return $vector;
    }
    
    /**
     * Расчет косинусного сходства
     */
    public function cosineSimilarity($vectorA, $vectorB)
    {
        if (empty($vectorA) || empty($vectorB)) {
            return 0;
        }
        
        // Приводим векторы к одинаковому виду
        $allTerms = array_unique(array_merge(array_keys($vectorA), array_keys($vectorB)));
        
        $dotProduct = 0;
        $normA = 0;
        $normB = 0;
        
        foreach ($allTerms as $term) {
            $a = $vectorA[$term] ?? 0;
            $b = $vectorB[$term] ?? 0;
            
            $dotProduct += $a * $b;
            $normA += $a * $a;
            $normB += $b * $b;
        }
        
        if ($normA == 0 || $normB == 0) {
            return 0;
        }
        
        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
    
    /**
     * Извлечение релевантного фрагмента текста
     */
    protected function extractRelevantSnippet($text, $queryTerms, $snippetLength = 300)
    {
        // Разбиваем текст на предложения
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);
        
        $bestSentence = '';
        $bestScore = 0;
        
        foreach ($sentences as $sentence) {
            if (mb_strlen($sentence, 'UTF-8') > 20) {
                $sentenceTerms = $this->extractTerms($sentence);
                $score = 0;
                
                foreach ($queryTerms as $term) {
                    if (in_array($term, $sentenceTerms)) {
                        $score++;
                    }
                }
                
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestSentence = $sentence;
                }
            }
        }
        
        if (!empty($bestSentence)) {
            return $bestSentence;
        }
        
        // Если не нашли подходящее предложение, возвращаем начало текста
        return mb_substr($text, 0, $snippetLength, 'UTF-8') . '...';
    }
    
    /**
     * Интеллектуальный анализ запроса
     */
    public function analyzeQuery($query)
    {
        $terms = $this->extractTerms($query);
        
        // Определяем тип проблемы
        $problemTypes = [
            'двигатель' => ['двигатель', 'мотор', 'engine', 'цилиндр', 'поршень'],
            'трансмиссия' => ['трансмиссия', 'коробка', 'сцепление'],
            'тормоза' => ['тормоз', 'brake', 'колодки'],
            'электрика' => ['электрика', 'проводка', 'аккумулятор'],
            'подвеска' => ['подвеска', 'амортизатор', 'стойка'],
        ];
        
        $detectedType = 'общая';
        foreach ($problemTypes as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (in_array($this->stemWord($keyword), $terms)) {
                    $detectedType = $type;
                    break 2;
                }
            }
        }
        
        // Определяем срочность по ключевым словам
        $urgencyKeywords = ['срочно', 'срочная', 'немедленно', 'быстро', 'срочн', 'аварий', 'авария'];
        $urgency = 'нормальная';
        foreach ($urgencyKeywords as $keyword) {
            if (str_contains(mb_strtolower($query, 'UTF-8'), $keyword)) {
                $urgency = 'срочная';
                break;
            }
        }
        
        return [
            'original_query' => $query,
            'processed_terms' => $terms,
            'analysis' => [
                'problem_type' => $detectedType,
                'urgency' => $urgency,
                'complexity' => $this->estimateComplexity($terms),
                'keywords' => array_slice($terms, 0, 10),
                'categories' => $this->suggestCategories($terms),
            ],
            'enhanced_query' => $this->enhanceQuery($query, $terms),
            'suggested_categories' => $this->suggestCategories($terms),
        ];
    }
    
    /**
     * Оценка сложности проблемы
     */
    protected function estimateComplexity($terms)
    {
        $complexKeywords = [
            'ремонт', 'замена', 'диагностика', 'разборка', 'сборка',
            'регулировка', 'настройка', 'капитальный'
        ];
        
        $simpleKeywords = [
            'проверка', 'осмотр', 'чистка', 'смазка', 'подтяжка'
        ];
        
        $complexCount = 0;
        $simpleCount = 0;
        
        foreach ($terms as $term) {
            foreach ($complexKeywords as $keyword) {
                if (str_contains($term, $keyword)) {
                    $complexCount++;
                }
            }
            
            foreach ($simpleKeywords as $keyword) {
                if (str_contains($term, $keyword)) {
                    $simpleCount++;
                }
            }
        }
        
        if ($complexCount > $simpleCount) {
            return 'сложная';
        } elseif ($simpleCount > 0) {
            return 'простая';
        }
        
        return 'средняя';
    }
    
    /**
     * Предложение категорий
     */
    protected function suggestCategories($terms)
    {
        $categories = [
            1 => ['двигатель', 'мотор', 'engine', 'цилиндр', 'поршень'],
            2 => ['трансмиссия', 'коробка', 'сцепление', 'transmission'],
            3 => ['тормоз', 'brake', 'колодки', 'диск'],
            4 => ['подвеска', 'амортизатор', 'suspension', 'стойка'],
            5 => ['рулевой', 'steering', 'рейка', 'насос'],
            6 => ['электрика', 'electrical', 'проводка', 'аккумулятор'],
            7 => ['кузов', 'body', 'покраска', 'сварка'],
            8 => ['охлаждение', 'радиатор', 'cooling', 'термостат'],
            9 => ['выхлоп', 'exhaust', 'глушитель', 'катализатор'],
        ];
        
        $suggested = [];
        
        foreach ($categories as $categoryId => $keywords) {
            foreach ($keywords as $keyword) {
                $stemmedKeyword = $this->stemWord($keyword);
                if (in_array($stemmedKeyword, $terms)) {
                    $suggested[] = $categoryId;
                    break;
                }
            }
        }
        
        return array_unique($suggested);
    }
    
    /**
     * Улучшение поискового запроса
     */
    protected function enhanceQuery($query, $terms)
    {
        // Добавляем синонимы
        $synonyms = [
            'не заводится' => ['не запускается', 'не заводиться'],
            'стучит' => ['стук', 'постукивает'],
            'перегрев' => ['перегревается', 'температура'],
            'масло' => ['смазка', 'lubrication'],
            'тормоз' => ['brake', 'тормозная'],
            'двигатель' => ['мотор', 'engine'],
        ];
        
        $enhanced = $query;
        
        foreach ($synonyms as $original => $synonymList) {
            if (str_contains(mb_strtolower($query, 'UTF-8'), $original)) {
                $enhanced .= ' ' . implode(' ', $synonymList);
                break;
            }
        }
        
        return trim($enhanced);
    }
    
    /**
     * Генерация простых эмбеддингов (вектор частот слов)
     */
    public function generateSimpleEmbedding($text)
    {
        $terms = $this->extractTerms($text);
        $frequencies = array_count_values($terms);
        
        // Нормализуем частоты
        $total = array_sum($frequencies);
        if ($total > 0) {
            foreach ($frequencies as $term => $freq) {
                $frequencies[$term] = $freq / $total;
            }
        }
        
        return $frequencies;
    }
}