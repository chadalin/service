<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SemanticSearchEngine
{
    protected $similarityThreshold = 0.3;

    public function semanticSearch(string $query, ?int $carModelId = null, ?int $categoryId = null)
    {
        $processedQuery = $this->preprocessText($query);
        $queryEmbedding = $this->textToEmbedding($processedQuery);
        
        $documents = Document::with(['carModel.brand', 'category'])
            ->where('status', 'processed')
            ->when($carModelId, function($q) use ($carModelId) {
                return $q->where('car_model_id', $carModelId);
            })
            ->when($categoryId, function($q) use ($categoryId) {
                return $q->where('category_id', $categoryId);
            })
            ->get()
            ->map(function($document) use ($queryEmbedding) {
                $docEmbedding = $this->textToEmbedding($this->preprocessText($document->content_text));
                $similarity = $this->cosineSimilarity($queryEmbedding, $docEmbedding);
                
                $document->semantic_similarity = $similarity;
                $document->combined_score = $this->calculateCombinedScore($document, $similarity);
                
                return $document;
            })
            ->filter(function($document) {
                return $document->semantic_similarity > $this->similarityThreshold;
            })
            ->sortByDesc('combined_score')
            ->values();

        return $documents;
    }

    private function preprocessText(string $text): string
    {
        // Приводим к нижнему регистру
        $text = mb_strtolower($text);
        
        // Удаляем стоп-слова
        $stopWords = ['и', 'в', 'на', 'с', 'по', 'для', 'из', 'от', 'до', 'за', 'к', 'у', 'о', 'об', 'не'];
        $words = preg_split('/\s+/', $text);
        $words = array_filter($words, function($word) use ($stopWords) {
            return !in_array($word, $stopWords) && mb_strlen($word) > 2;
        });
        
        // Лемматизация (упрощенная)
        $words = array_map([$this, 'stemWord'], $words);
        
        return implode(' ', $words);
    }

    private function stemWord(string $word): string
    {
        // Упрощенная stemmer для русского языка
        $suffixes = [
            'ов', 'ев', 'ёв', 'ин', 'ын', 'ых', 'их', 'ого', 'его', 'ому', 'ему', 
            'ыми', 'ими', 'ах', 'ях', 'ами', 'ями', 'ии', 'ие', 'ью', 'ую', 'ой', 'ей',
            'ем', 'им', 'ом', 'ый', 'ий', 'ой', 'ая', 'яя', 'ое', 'ее', 'ость', 'ств'
        ];
        
        foreach ($suffixes as $suffix) {
            if (Str::endsWith($word, $suffix)) {
                $word = Str::beforeLast($word, $suffix);
                break;
            }
        }
        
        return $word;
    }

    private function textToEmbedding(string $text): array
    {
        // Упрощенное создание embedding (в реальном проекте используйте AI API)
        $words = array_unique(explode(' ', $text));
        $embedding = [];
        
        // Создаем простой бинарный вектор на основе ключевых слов
        $keywords = $this->getTechnicalKeywords();
        
        foreach ($keywords as $keyword) {
            $embedding[] = in_array($keyword, $words) ? 1 : 0;
        }
        
        // Добавляем TF-IDF like features
        $wordFreq = array_count_values(explode(' ', $text));
        $totalWords = count(explode(' ', $text));
        
        foreach ($wordFreq as $word => $freq) {
            if (in_array($word, $keywords)) {
                $tf = $freq / $totalWords;
                $embedding[array_search($word, $keywords)] = $tf;
            }
        }
        
        return $embedding;
    }

    private function getTechnicalKeywords(): array
    {
        return [
            'двигатель', 'трансмиссия', 'тормоз', 'подвеска', 'рулевой', 'электрика',
            'замена', 'ремонт', 'диагностика', 'настройка', 'регулировка', 'установка',
            'масло', 'фильтр', 'свеча', 'аккумулятор', 'генератор', 'стартер',
            'топливо', 'охлаждение', 'выхлоп', 'кузов', 'салон', 'кондиционер',
            'неисправность', 'проблема', 'ошибка', 'код', 'датчик', 'сигнал'
        ];
    }

    private function cosineSimilarity(array $vec1, array $vec2): float
    {
        $dotProduct = 0;
        $norm1 = 0;
        $norm2 = 0;
        
        for ($i = 0; $i < count($vec1); $i++) {
            $dotProduct += $vec1[$i] * $vec2[$i];
            $norm1 += $vec1[$i] * $vec1[$i];
            $norm2 += $vec2[$i] * $vec2[$i];
        }
        
        $norm1 = sqrt($norm1);
        $norm2 = sqrt($norm2);
        
        if ($norm1 == 0 || $norm2 == 0) {
            return 0;
        }
        
        return $dotProduct / ($norm1 * $norm2);
    }

    private function calculateCombinedScore(Document $document, float $semanticScore): float
    {
        // Комбинируем семантическую релевантность с другими факторами
        $titleBonus = Str::contains(mb_strtolower($document->title), mb_strtolower($document->title)) ? 0.2 : 0;
        $recencyBonus = $this->calculateRecencyBonus($document);
        
        return ($semanticScore * 0.7) + ($titleBonus * 0.2) + ($recencyBonus * 0.1);
    }

    private function calculateRecencyBonus(Document $document): float
    {
        $daysAgo = $document->created_at->diffInDays(now());
        
        if ($daysAgo < 30) return 0.1;      // Новые документы
        if ($daysAgo < 90) return 0.05;     // Не очень старые
        return 0;                           // Старые документы
    }

    public function findSimilarDocuments(Document $document, int $limit = 5)
    {
        return $this->semanticSearch($document->content_text, $document->car_model_id, $document->category_id)
            ->where('id', '!=', $document->id)
            ->take($limit);
    }
}