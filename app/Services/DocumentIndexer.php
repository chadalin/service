<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DocumentIndexer
{
    /**
     * Индексация документа
     */
    public function indexDocument(Document $document)
    {
        try {
            Log::info("Начало индексации документа {$document->id}");
            
            // Проверяем, что документ распарсен
            if (!$document->is_parsed || empty($document->content_text)) {
                throw new \Exception("Документ не распарсен или не содержит текста");
            }
            
            // 1. Извлекаем ключевые слова
            $keywords = $this->extractKeywords($document);
            
            // 2. Определяем секцию
            $section = $this->detectSection($document);
            
            // 3. Определяем систему
            $system = $this->detectSystem($section);
            
            // 4. Определяем компонент
            $component = $this->detectComponent($document);
            
            // 5. Подготавливаем текстовые ключевые слова для поиска
            $keywordsText = $this->prepareKeywordsText($keywords);
            
            // 6. Обновляем документ
            $document->update([
                'keywords' => $keywords,
                'keywords_text' => $keywordsText,
                'detected_section' => $section,
                'detected_system' => $system,
                'detected_component' => $component,
                'search_indexed' => true,
                'status' => 'indexed',
                'updated_at' => now(),
            ]);
            
            // 7. Создаем поисковый индекс (n-граммы)
            $this->createSearchIndex($document);
            
            Log::info("Документ {$document->id} успешно проиндексирован");
            
            return [
                'success' => true,
                'message' => 'Документ успешно проиндексирован',
                'document_id' => $document->id,
                'section' => $section,
                'system' => $system,
                'keywords_count' => count($keywords)
            ];
            
        } catch (\Exception $e) {
            Log::error("Ошибка индексации документа {$document->id}: " . $e->getMessage());
            
            $document->update([
                'status' => 'index_error'
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка индексации: ' . $e->getMessage(),
                'document_id' => $document->id
            ];
        }
    }
    
    /**
     * Извлечение ключевых слов
     */
    protected function extractKeywords(Document $document)
    {
        $text = mb_strtolower($document->title . ' ' . $document->content_text, 'UTF-8');
        
        // Удаляем стоп-слова
        $stopWords = ['и', 'в', 'на', 'с', 'по', 'для', 'или', 'но', 'а', 'же', 'ли', 'бы'];
        $words = preg_split('/\s+/', $text);
        $words = array_filter($words, function($word) use ($stopWords) {
            return mb_strlen($word, 'UTF-8') > 3 && !in_array($word, $stopWords);
        });
        
        // Считаем частоту
        $wordCount = array_count_values($words);
        arsort($wordCount);
        
        // Берем топ-20 слов
        $keywords = array_slice(array_keys($wordCount), 0, 20);
        
        return $keywords;
    }
    
    /**
     * Определение секции
     */
    protected function detectSection(Document $document)
    {
        $text = mb_strtolower($document->title . ' ' . $document->content_text, 'UTF-8');
        
        $sections = [
            'двигатель' => ['двигатель', 'мотор', 'engine', 'цилиндр', 'поршень', 'коленвал', 'распредвал'],
            'трансмиссия' => ['трансмиссия', 'коробка передач', 'сцепление', 'transmission', 'кпп', 'акпп'],
            'тормоза' => ['тормоз', 'brake', 'тормозной диск', 'колодки', 'суппорт'],
            'подвеска' => ['подвеска', 'suspension', 'амортизатор', 'стойка', 'рычаг', 'шаровая'],
            'электрика' => ['электрика', 'electrical', 'проводка', 'аккумулятор', 'генератор', 'стартер'],
            'кузов' => ['кузов', 'body', 'покраска', 'сварка', 'крыло', 'дверь', 'капот'],
            'рулевое' => ['рулевое', 'steering', 'рулевая рейка', 'насос', 'тяга'],
            'охлаждение' => ['охлаждение', 'cooling', 'радиатор', 'термостат', 'помпа'],
            'выхлоп' => ['выхлоп', 'exhaust', 'глушитель', 'катализатор', 'резонатор'],
            'топливо' => ['топливо', 'бензин', 'дизель', 'инжектор', 'карбюратор', 'форсунка'],
        ];
        
        $scores = [];
        foreach ($sections as $section => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                $score += substr_count($text, $keyword);
            }
            $scores[$section] = $score;
        }
        
        arsort($scores);
        $topSection = array_key_first($scores);
        
        return $scores[$topSection] > 0 ? $topSection : 'общее';
    }
    
    /**
     * Определение системы
     */
    protected function detectSystem($section)
    {
        $systems = [
            'двигатель' => 'силовая установка',
            'трансмиссия' => 'трансмиссия',
            'тормоза' => 'тормозная система',
            'подвеска' => 'ходовая часть',
            'электрика' => 'электрооборудование',
            'кузов' => 'кузов и элементы',
            'рулевое' => 'рулевое управление',
            'охлаждение' => 'система охлаждения',
            'выхлоп' => 'выхлопная система',
            'топливо' => 'топливная система',
            'общее' => 'общая информация',
        ];
        
        return $systems[$section] ?? 'неизвестно';
    }
    
    /**
     * Определение компонента
     */
    protected function detectComponent(Document $document)
    {
        $text = mb_strtolower($document->title, 'UTF-8');
        
        $components = [
            'генератор', 'стартер', 'аккумулятор', 'свеча', 'фильтр',
            'насос', 'ремень', 'цепь', 'датчик', 'клапан', 'турбина',
            'компрессор', 'инжектор', 'форсунка', 'радиатор', 'термостат',
            'амортизатор', 'пружина', 'диск', 'колодка', 'суппорт',
        ];
        
        foreach ($components as $component) {
            if (str_contains($text, mb_strtolower($component, 'UTF-8'))) {
                return $component;
            }
        }
        
        return 'основной компонент';
    }
    
    /**
     * Подготовка текстовых ключевых слов
     */
    protected function prepareKeywordsText($keywords)
    {
        if (is_array($keywords)) {
            return implode(', ', $keywords);
        }
        
        return $keywords;
    }
    
    /**
     * Создание поискового индекса (n-граммы)
     */
    protected function createSearchIndex(Document $document)
    {
        try {
            // Очищаем старые n-граммы
            DB::table('document_ngrams')->where('document_id', $document->id)->delete();
            
            $text = mb_strtolower($document->content_text, 'UTF-8');
            $words = preg_split('/\s+/', $text);
            
            // Фильтруем короткие слова
            $words = array_filter($words, function($word) {
                return mb_strlen($word, 'UTF-8') > 2;
            });
            
            $words = array_values($words);
            
            // Создаем триграммы
            $ngrams = [];
            for ($i = 0; $i < count($words) - 2; $i++) {
                $ngram = $words[$i] . ' ' . $words[$i + 1] . ' ' . $words[$i + 2];
                if (mb_strlen($ngram, 'UTF-8') <= 100) {
                    $ngrams[] = [
                        'document_id' => $document->id,
                        'ngram' => $ngram,
                        'ngram_type' => 'trigram',
                        'position' => $i,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            
            // Вставляем пачками
            $chunks = array_chunk($ngrams, 1000);
            foreach ($chunks as $chunk) {
                DB::table('document_ngrams')->insert($chunk);
            }
            
            return count($ngrams);
            
        } catch (\Exception $e) {
            Log::error("Ошибка создания поискового индекса для документа {$document->id}: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Массовая индексация
     */
    public function indexMultiple($documentIds)
    {
        $results = [
            'success' => [],
            'errors' => []
        ];
        
        foreach ($documentIds as $id) {
            $document = Document::find($id);
            if ($document) {
                $result = $this->indexDocument($document);
                if ($result['success']) {
                    $results['success'][] = $document->id;
                } else {
                    $results['errors'][] = [
                        'id' => $document->id,
                        'message' => $result['message']
                    ];
                }
            }
        }
        
        return $results;
    }
}