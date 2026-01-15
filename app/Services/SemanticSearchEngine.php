<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DiagnosticCase;
use App\Models\DiagnosticSymptom;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenAI;

class SemanticSearchEngine
{
    protected $openai;
    
    public function __construct()
    {
        $this->openai = OpenAI::client(config('services.openai.api_key'));
    }
    
    /**
     * Семантический поиск с использованием OpenAI Embeddings
     */
    public function semanticSearch($query, $modelId = null, $categoryId = null)
    {
        try {
            // Генерируем эмбеддинг для запроса
            $queryEmbedding = $this->generateEmbedding($query);
            
            // Ищем в документах
            $documentResults = $this->searchDocumentsWithEmbedding($queryEmbedding, $modelId, $categoryId);
            
            // Ищем в диагностических кейсах
            $caseResults = $this->searchCasesWithEmbedding($queryEmbedding, $modelId);
            
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
     * Генерация эмбеддинга текста
     */
    protected function generateEmbedding($text)
    {
        try {
            $response = $this->openai->embeddings()->create([
                'model' => 'text-embedding-ada-002',
                'input' => $text,
            ]);
            
            return $response->embeddings[0]->embedding;
            
        } catch (\Exception $e) {
            Log::error('Embedding generation error: ' . $e->getMessage());
            
            // Возвращаем пустой массив в случае ошибки
            return [];
        }
    }
    
    /**
     * Поиск документов с использованием эмбеддингов
     */
    protected function searchDocumentsWithEmbedding($queryEmbedding, $modelId = null, $categoryId = null)
    {
        if (empty($queryEmbedding)) {
            return collect([]);
        }
        
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
        
        // Рассчитываем косинусное сходство для каждого документа
        return $documents->map(function($doc) use ($queryEmbedding) {
            $docEmbedding = $this->getDocumentEmbedding($doc);
            $similarity = $this->cosineSimilarity($queryEmbedding, $docEmbedding);
            
            return [
                'id' => $doc->id,
                'type' => 'document',
                'title' => $doc->title,
                'content_text' => $this->extractRelevantSnippet($doc->content_text, $queryEmbedding),
                'car_model' => $doc->carModel,
                'category' => $doc->category,
                'semantic_similarity' => $similarity,
                'created_at' => $doc->created_at,
                'url' => route('admin.documents.show', $doc->id)
            ];
        })->filter(function($item) {
            return $item['semantic_similarity'] > 0.3; // Минимальный порог сходства
        })->sortByDesc('semantic_similarity');
    }
    
    /**
     * Получение эмбеддинга документа
     */
    protected function getDocumentEmbedding(Document $document)
    {
        // Проверяем, есть ли сохраненный эмбеддинг
        if ($document->embedding) {
            return json_decode($document->embedding, true);
        }
        
        // Генерируем новый эмбеддинг
        $text = $document->title . ' ' . $document->keywords . ' ' . 
                substr($document->content_text, 0, 2000);
        
        $embedding = $this->generateEmbedding($text);
        
        // Сохраняем для будущего использования
        $document->update([
            'embedding' => json_encode($embedding)
        ]);
        
        return $embedding;
    }
    
    /**
     * Поиск диагностических кейсов с эмбеддингами
     */
    protected function searchCasesWithEmbedding($queryEmbedding, $modelId = null)
    {
        if (!class_exists(DiagnosticCase::class) || empty($queryEmbedding)) {
            return collect([]);
        }
        
        $cases = DiagnosticCase::query()
            ->with(['carModel.brand', 'symptoms'])
            ->where('status', 'resolved');
        
        if ($modelId) {
            $cases->where('car_model_id', $modelId);
        }
        
        $cases = $cases->limit(50)->get();
        
        return $cases->map(function($case) use ($queryEmbedding) {
            $text = $case->problem_description . ' ' . $case->diagnosis . ' ' . $case->solution;
            $caseEmbedding = $this->generateEmbedding($text);
            $similarity = $this->cosineSimilarity($queryEmbedding, $caseEmbedding);
            
            return [
                'id' => $case->id,
                'type' => 'diagnostic_case',
                'title' => 'Диагностический кейс: ' . substr($case->problem_description, 0, 50),
                'content_text' => "Проблема: {$case->problem_description}\nДиагноз: {$case->diagnosis}",
                'car_model' => $case->carModel,
                'semantic_similarity' => $similarity,
                'created_at' => $case->created_at,
                'url' => route('diagnostic.report.show', $case->id)
            ];
        })->filter(function($item) {
            return $item['semantic_similarity'] > 0.3;
        });
    }
    
    /**
     * Расчет косинусного сходства
     */
    protected function cosineSimilarity($vectorA, $vectorB)
    {
        if (empty($vectorA) || empty($vectorB) || count($vectorA) !== count($vectorB)) {
            return 0;
        }
        
        $dotProduct = 0;
        $normA = 0;
        $normB = 0;
        
        for ($i = 0; $i < count($vectorA); $i++) {
            $dotProduct += $vectorA[$i] * $vectorB[$i];
            $normA += $vectorA[$i] * $vectorA[$i];
            $normB += $vectorB[$i] * $vectorB[$i];
        }
        
        if ($normA == 0 || $normB == 0) {
            return 0;
        }
        
        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
    
    /**
     * Извлечение релевантного фрагмента текста
     */
    protected function extractRelevantSnippet($text, $queryEmbedding, $snippetLength = 300)
    {
        // Разбиваем текст на предложения
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);
        
        $bestSentence = '';
        $bestScore = 0;
        
        foreach ($sentences as $sentence) {
            if (strlen($sentence) > 20) {
                $sentenceEmbedding = $this->generateEmbedding($sentence);
                $score = $this->cosineSimilarity($queryEmbedding, $sentenceEmbedding);
                
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
        return substr($text, 0, $snippetLength) . '...';
    }
    
    /**
     * Интеллектуальный анализ запроса
     */
    public function analyzeQuery($query)
    {
        try {
            $response = $this->openai->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Ты - эксперт по автомобилям. Анализируй запрос пользователя и определяй:
                        1. Тип проблемы (двигатель, трансмиссия, электрика и т.д.)
                        2. Срочность (срочно/не срочно)
                        3. Уровень сложности (легкий/средний/сложный)
                        4. Ключевые слова для поиска
                        5. Рекомендуемые категории поиска
                        
                        Ответ в формате JSON.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $query
                    ]
                ],
                'temperature' => 0.3,
            ]);
            
            $analysis = json_decode($response->choices[0]->message->content, true);
            
            return [
                'original_query' => $query,
                'analysis' => $analysis,
                'enhanced_query' => $this->enhanceQuery($query, $analysis),
                'suggested_categories' => $analysis['categories'] ?? []
            ];
            
        } catch (\Exception $e) {
            Log::error('Query analysis error: ' . $e->getMessage());
            
            // Возвращаем базовый анализ в случае ошибки
            return [
                'original_query' => $query,
                'analysis' => [
                    'problem_type' => 'unknown',
                    'urgency' => 'normal',
                    'complexity' => 'medium',
                    'keywords' => explode(' ', $query),
                    'categories' => []
                ],
                'enhanced_query' => $query,
                'suggested_categories' => []
            ];
        }
    }
    
    /**
     * Улучшение поискового запроса
     */
    protected function enhanceQuery($query, $analysis)
    {
        $keywords = $analysis['keywords'] ?? [];
        
        if (!empty($keywords)) {
            $enhanced = $query . ' ' . implode(' ', array_slice($keywords, 0, 5));
            return trim($enhanced);
        }
        
        return $query;
    }
}