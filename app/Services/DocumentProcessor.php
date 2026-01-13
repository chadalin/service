<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use thiagoalessio\TesseractOCR\TesseractOCR;
use setasign\Fpdi\Fpdi;

class DocumentProcessor
{
    private $pdfParser;
    
    public function __construct()
    {
        $this->pdfParser = new Parser();
    }
    
    public function processDocument(Document $document)
    {
        Log::info("🔄 Processing document ID: {$document->id}");
        
        try {
            $filePath = $this->getFilePath($document);
            
            if (!file_exists($filePath)) {
                throw new \Exception("File not found: {$filePath}");
            }
            
            // 1. Извлекаем текст из PDF (возвращает массив)
            $extractedData = $this->extractTextFromPDF($filePath, $document->file_type);
            
            // Получаем контент из массива
            $rawContent = $extractedData['content'] ?? '';
            
            if (empty($rawContent)) {
                throw new \Exception("No content extracted from PDF");
            }
            
            // 2. Парсим структуру документа (передаем строку)
            $parsedData = $this->parseDocumentStructure($rawContent, $document->title);
            
            // 3. Очищаем текст
            $cleanContent = $this->cleanText($parsedData['content']);
            
            // 4. Извлекаем метаданные
            $metadata = $this->extractMetadata($cleanContent, $document);
            
            // 5. Извлекаем ключевые слова
            $keywords = $this->extractKeywords($cleanContent);
            
            // 6. Создаем поисковый вектор
            $searchVector = $this->createSearchVector($cleanContent, $keywords);
            
            // 7. Сохраняем структурированные данные
            $document->update([
                'content_text' => $cleanContent,
                'search_vector' => $searchVector,
                'keywords' => json_encode(array_merge($keywords, $metadata['keywords'] ?? [])),
                'sections' => json_encode($parsedData['sections'] ?? []),
                'metadata' => json_encode($metadata),
                'word_count' => str_word_count($cleanContent),
                'has_images' => $extractedData['has_images'] ?? false,
                'is_scanned' => $extractedData['is_scanned'] ?? false,
                'parsed_at' => now(),
                'status' => 'processed'
            ]);
            
            Log::info("✅ Document processed successfully");
            Log::info("📊 Content length: " . strlen($cleanContent) . " chars");
            Log::info("🔑 Keywords: " . count($keywords));
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("❌ Error processing document: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            $document->update(['status' => 'error', 'content_text' => 'Error: ' . $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Извлекает текст из PDF с сохранением структуры
     */
    private function extractTextFromPDF(string $filePath, string $fileType): array
    {
        Log::info("📄 Extracting text from: " . basename($filePath));
        
        $result = [
            'content' => '',
            'pages' => [],
            'has_images' => false,
            'is_scanned' => false,
        ];
        
        try {
            // Пытаемся парсить как текстовый PDF
            $pdf = $this->pdfParser->parseFile($filePath);
            $text = $pdf->getText();
            
            if (!empty($text) && strlen($text) > 100) {
                // Текстовый PDF
                $result['content'] = $text;
                
                // Получаем страницы
                $pages = $pdf->getPages();
                foreach ($pages as $page) {
                    $result['pages'][] = [
                        'number' => $page->getPageNumber(),
                        'text' => $page->getText(),
                    ];
                }
                
                Log::info("✅ Text PDF parsed successfully: " . strlen($text) . " chars");
                return $result;
            }
            
            // Если текста мало, возможно это сканированный PDF
            Log::warning("⚠️ Text PDF parsing yielded little text (" . strlen($text) . " chars)");
            
            // Проверяем размер файла
            $fileSize = filesize($filePath);
            if ($fileSize > 102400) { // больше 100KB
                $result['has_images'] = true;
                $result['is_scanned'] = true;
                $result['content'] = $text ?: "Сканированный PDF документ. ";
                $result['content'] .= "Размер файла: " . round($fileSize / 1024) . " KB. ";
                $result['content'] .= "Для полнотекстового поиска требуется ручная обработка OCR.";
                
                Log::info("📸 PDF appears to be scanned (size: " . round($fileSize / 1024) . " KB)");
                return $result;
            }
            
            // Даже если текста мало, используем что есть
            $result['content'] = $text ?: "Не удалось извлечь текст из PDF файла";
            return $result;
            
        } catch (\Exception $e) {
            Log::error("PDF extraction error: " . $e->getMessage());
            
            // Fallback: пробуем прочитать как текстовый файл
            $content = @file_get_contents($filePath);
            if ($content !== false) {
                $result['content'] = $this->extractTextFromBinary($content);
                Log::info("📄 Used fallback text extraction");
            } else {
                $result['content'] = "Ошибка чтения файла: " . $e->getMessage();
            }
            
            return $result;
        }
    }
    
    /**
     * Простая попытка извлечь текст из бинарного PDF
     */
    private function extractTextFromBinary(string $binaryContent): string
    {
        // Удаляем бинарные данные, оставляем только текст
        $text = preg_replace('/[^\x20-\x7E\x0A\x0D\xD0-\xDF\x80-\xBF]/u', ' ', $binaryContent);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        // Если получился осмысленный текст
        if (strlen($text) > 100) {
            return $text;
        }
        
        return "Бинарный PDF файл. Требуется специальная обработка для извлечения текста.";
    }
    
    /**
     * Парсит структуру документа (оглавление, разделы)
     */
    private function parseDocumentStructure(string $content, string $title): array
    {
        $lines = explode("\n", $content);
        $sections = [];
        $currentSection = null;
        $plainContent = '';
        
        // Паттерны для заголовков разделов
        $sectionPatterns = [
            '/^(ГЛАВА|РАЗДЕЛ|ЧАСТЬ)\s+[IVXLCDM0-9]+\.?\s*[-–]?\s*(.+)$/iu',
            '/^(\d+\.\d+\.?)\s+(.+)$/u', // 1.1. Название
            '/^(\d+\.)\s+(.+)$/u',       // 1. Название
            '/^([IVXLCDM]+)\.\s+(.+)$/iu', // Римские цифры
            '/^(Приложение\s+[A-ZА-Я0-9])\.?\s*(.+)$/iu',
        ];
        
        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $plainContent .= $line . "\n";
            
            // Проверяем, является ли строка заголовком
            foreach ($sectionPatterns as $pattern) {
                if (preg_match($pattern, $line, $matches)) {
                    $sectionTitle = trim($matches[count($matches) - 1]);
                    
                    if (mb_strlen($sectionTitle) > 5 && mb_strlen($sectionTitle) < 200) {
                        $sections[] = [
                            'title' => $sectionTitle,
                            'level' => $this->calculateHeadingLevel($matches[1] ?? ''),
                            'line' => $lineNumber,
                            'page' => floor($lineNumber / 50) + 1, // Примерная нумерация
                        ];
                        $currentSection = end($sections);
                    }
                    break;
                }
            }
            
            // Если это не заголовок, добавляем к текущему разделу
            if ($currentSection && !isset($sections[count($sections)-1]['content'])) {
                $sections[count($sections)-1]['content_start'] = strlen($plainContent) - strlen($line) - 1;
            }
        }
        
        // Добавляем содержание к разделам
        foreach ($sections as &$section) {
            if (isset($section['content_start'])) {
                $nextSectionStart = $this->findNextSectionStart($sections, $section['content_start']);
                $section['content'] = substr($plainContent, 
                    $section['content_start'], 
                    $nextSectionStart - $section['content_start']
                );
            }
        }
        
        return [
            'content' => $plainContent,
            'sections' => $sections,
            'has_images' => preg_match('/\[IMAGE\]|Рисунок|Рис\./iu', $content) > 0,
        ];
    }
    
    private function calculateHeadingLevel(string $marker): int
    {
        if (preg_match('/^\d+\.\d+\./', $marker)) return 3;
        if (preg_match('/^\d+\./', $marker)) return 2;
        if (preg_match('/^(ГЛАВА|РАЗДЕЛ|ЧАСТЬ)/iu', $marker)) return 1;
        return 2;
    }
    
    private function findNextSectionStart(array $sections, int $currentStart): int
    {
        foreach ($sections as $section) {
            if (isset($section['content_start']) && $section['content_start'] > $currentStart) {
                return $section['content_start'];
            }
        }
        return PHP_INT_MAX;
    }
    
    /**
     * Извлекает метаданные из документа
     */
    private function extractMetadata(string $content, Document $document): array
    {
        $metadata = [
            'document_type' => $this->detectDocumentType($content),
            'car_parts' => $this->extractCarParts($content),
            'procedures' => $this->extractProcedures($content),
            'warnings' => $this->extractWarnings($content),
            'tools_required' => $this->extractTools($content),
            'estimated_time' => $this->extractTimeEstimates($content),
            'difficulty' => $this->estimateDifficulty($content),
            'keywords' => [],
        ];
        
        // Извлекаем специфичные для автомобилей данные
        if ($document->carModel && $document->carModel->brand) {
            $metadata['car_specific'] = [
                'brand' => $document->carModel->brand->name,
                'model' => $document->carModel->name,
                'years' => $this->extractYears($content),
                'engine_codes' => $this->extractEngineCodes($content),
            ];
        }
        
        return $metadata;
    }
    
    private function detectDocumentType(string $content): string
    {
        $content = mb_strtolower($content);
        
        $patterns = [
            'repair_manual' => ['руководство по ремонту', 'manual repair', 'service manual'],
            'diagnostic' => ['диагностика', 'diagnostic', 'коды ошибок', 'trouble codes'],
            'maintenance' => ['техническое обслуживание', 'maintenance', 'то', 'замена'],
            'wiring_diagram' => ['электросхема', 'wiring diagram', 'электрическая схема'],
            'parts_catalog' => ['каталог запчастей', 'parts catalog', 'детали', 'артикул'],
            'recall' => ['отзывная кампания', 'recall', 'сервисное бюллетень'],
        ];
        
        foreach ($patterns as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($content, $keyword)) {
                    return $type;
                }
            }
        }
        
        return 'unknown';
    }
    
    private function extractCarParts(string $content): array
    {
        $parts = [];
        $content = mb_strtolower($content);
        
        $partPatterns = [
            '/артикул[:\s]+([A-Z0-9-]+)/iu',
            '/номер[:\s]+([A-Z0-9-]+)/iu',
            '/([A-Z]{2,3}\d{3,5}[A-Z]?)/', // Коды запчастей
            '/деталь[:\s]+(.+?)[\.\n]/iu',
        ];
        
        foreach ($partPatterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                $parts = array_merge($parts, $matches[1]);
            }
        }
        
        return array_unique(array_filter(array_map('trim', $parts)));
    }
    
    private function extractProcedures(string $content): array
    {
        $procedures = [];
        $lines = explode("\n", $content);
        
        $procedureKeywords = ['шаг', 'этап', 'процедура', 'действие', 'инструкция'];
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            foreach ($procedureKeywords as $keyword) {
                if (preg_match("/^{$keyword}\s+(\d+)[:\.]\s*(.+)$/iu", $line, $matches)) {
                    $procedures[] = [
                        'step' => $matches[1],
                        'description' => $matches[2],
                    ];
                    break;
                }
            }
            
            // Извлекаем нумерованные списки
            if (preg_match('/^(\d+)\)\s+(.+)$/u', $line, $matches)) {
                $procedures[] = [
                    'step' => $matches[1],
                    'description' => $matches[2],
                ];
            }
        }
        
        return $procedures;
    }
    
    private function extractWarnings(string $content): array
    {
        $warnings = [];
        
        // Ищем предупреждения и примечания
        $warningPatterns = [
            '/ВНИМАНИЕ[!\s]*\s*(.+?)(?=\n\n|\n[A-ZА-Я]|$)/ius',
            '/ПРЕДУПРЕЖДЕНИЕ[!\s]*\s*(.+?)(?=\n\n|\n[A-ZА-Я]|$)/ius',
            '/ВАЖНО[!\s]*\s*(.+?)(?=\n\n|\n[A-ZА-Я]|$)/ius',
            '/ПРИМЕЧАНИЕ[!\s]*\s*(.+?)(?=\n\n|\n[A-ZА-Я]|$)/ius',
        ];
        
        foreach ($warningPatterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                $warnings = array_merge($warnings, $matches[1]);
            }
        }
        
        return array_map('trim', $warnings);
    }
    
    private function extractTools(string $content): array
    {
        $tools = [];
        $content = mb_strtolower($content);
        
        $toolKeywords = [
            'ключ', 'отвертка', 'пассатижи', 'молоток', 'домкрат', 'съемник',
            'динамометрический ключ', 'тестер', 'мультиметр', 'компрессометр',
            'специальный инструмент', 'инструмент',
        ];
        
        foreach ($toolKeywords as $tool) {
            if (str_contains($content, $tool)) {
                // Извлекаем контекст
                preg_match_all('/[^.!?]*' . preg_quote($tool, '/') . '[^.!?]*[.!?]/iu', $content, $matches);
                foreach ($matches[0] as $match) {
                    $tools[] = trim($match);
                }
            }
        }
        
        return array_unique($tools);
    }
    
    private function extractTimeEstimates(string $content): array
    {
        $times = [];
        
        // Ищем указания времени
        if (preg_match_all('/(\d+[\.,]?\d*)\s*(часов?|ч|минут|мин|дней|дн)/iu', $content, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $times[] = $matches[1][$i] . ' ' . $matches[2][$i];
            }
        }
        
        return array_unique($times);
    }
    
    private function estimateDifficulty(string $content): string
    {
        $content = mb_strtolower($content);
        
        $difficultyKeywords = [
            'легко' => 1,
            'просто' => 1,
            'средне' => 2,
            'средняя' => 2,
            'сложно' => 3,
            'трудно' => 3,
            'требуется опыт' => 3,
            'специалист' => 3,
            'опасно' => 3,
        ];
        
        foreach ($difficultyKeywords as $keyword => $level) {
            if (str_contains($content, $keyword)) {
                return $level == 1 ? 'легко' : ($level == 2 ? 'средне' : 'сложно');
            }
        }
        
        return 'средне';
    }
    
    private function extractYears(string $content): array
    {
        $years = [];
        
        // Ищем года выпуска
        if (preg_match_all('/(\d{4})[-\s](\d{4})/u', $content, $matches)) {
            foreach ($matches[0] as $range) {
                $years[] = $range;
            }
        }
        
        if (preg_match_all('/с\s+(\d{4})/iu', $content, $matches)) {
            $years = array_merge($years, $matches[1]);
        }
        
        return array_unique($years);
    }
    
    private function extractEngineCodes(string $content): array
    {
        $codes = [];
        
        // Ищем коды двигателей
        if (preg_match_all('/([A-Z0-9]{4,8})/u', $content, $matches)) {
            foreach ($matches[1] as $code) {
                // Фильтруем слишком короткие или длинные коды
                if (preg_match('/^[A-Z0-9]{4,8}$/i', $code) && !is_numeric($code)) {
                    $codes[] = strtoupper($code);
                }
            }
        }
        
        return array_unique($codes);
    }
    
    /**
     * Очищает текст
     */
    private function cleanText(string $text): string
    {
        // Удаляем лишние пробелы и переносы
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Удаляем служебные символы
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
        
        // Сохраняем кириллицу, латиницу, цифры и знаки препинания
        $text = preg_replace('/[^\p{Cyrillic}\p{Latin}\p{N}\s\.\,\-\!\\?\(\)\:\;\"\'\«\»]/u', ' ', $text);
        
        return trim($text);
    }
    
    /**
     * Извлекает ключевые слова
     */
    private function extractKeywords(string $text): array
    {
        $text = mb_strtolower($text);
        
        // Список технических терминов для автомобилей
        $technicalTerms = $this->getTechnicalDictionary();
        
        $words = preg_split('/[\s\p{P}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $wordFreq = [];
        
        foreach ($words as $word) {
            $word = trim($word);
            
            if (mb_strlen($word) > 2 && !in_array($word, $this->getStopWords())) {
                // Проверяем, является ли слово техническим термином
                if (in_array($word, $technicalTerms) || $this->isTechnicalTerm($word)) {
                    $wordFreq[$word] = ($wordFreq[$word] ?? 0) + 1;
                }
            }
        }
        
        arsort($wordFreq);
        return array_slice(array_keys($wordFreq), 0, 30);
    }
    
    private function getTechnicalDictionary(): array
    {
        return [
            // Двигатель
            'двигатель', 'мотор', 'коленвал', 'распредвал', 'поршень', 'цилиндр', 'гбц',
            'клапан', 'топливо', 'бензин', 'дизель', 'инжектор', 'карбюратор', 'тнвд',
            
            // Трансмиссия
            'коробка', 'акпп', 'мкпп', 'вариатор', 'сцепление', 'диск', 'муфта',
            
            // Ходовая
            'подвеска', 'амортизатор', 'стойка', 'пружина', 'рычаг', 'сайлентблок',
            
            // Электрика
            'аккумулятор', 'генератор', 'стартер', 'реле', 'предохранитель', 'датчик',
        ];
    }
    
    private function getStopWords(): array
    {
        return [
            'и', 'в', 'на', 'с', 'по', 'для', 'из', 'от', 'до', 'за', 'к', 'у', 'о',
            'об', 'не', 'что', 'это', 'как', 'так', 'но', 'а', 'или', 'же', 'бы',
        ];
    }
    
    private function isTechnicalTerm(string $word): bool
    {
        // Проверяем по паттернам технических терминов
        $patterns = [
            '/.*тормоз.*/ui',
            '/.*подвеск.*/ui',
            '/.*двигател.*/ui',
            '/.*трансмисси.*/ui',
            '/.*электрик.*/ui',
            '/.*ремонт.*/ui',
            '/.*диагностик.*/ui',
            '/.*замен.*/ui',
            '/.*регулировк.*/ui',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $word)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Создает поисковый вектор
     */
    private function createSearchVector(string $content, array $keywords): string
    {
        $vector = [];
        
        // Добавляем ключевые слова
        foreach ($keywords as $keyword) {
            $vector[] = $keyword;
        }
        
        // Добавляем первые 200 символов
        $vector[] = substr($content, 0, 200);
        
        // Добавляем частые биграммы
        $bigrams = $this->extractBigrams($content);
        $vector = array_merge($vector, array_slice($bigrams, 0, 10));
        
        return implode(' ', array_unique($vector));
    }
    
    private function extractBigrams(string $text): array
    {
        $words = preg_split('/\s+/', $text);
        $bigrams = [];
        
        for ($i = 0; $i < count($words) - 1; $i++) {
            if (mb_strlen($words[$i]) > 2 && mb_strlen($words[$i + 1]) > 2) {
                $bigram = $words[$i] . ' ' . $words[$i + 1];
                $bigrams[] = $bigram;
            }
        }
        
        $bigramFreq = array_count_values($bigrams);
        arsort($bigramFreq);
        
        return array_keys($bigramFreq);
    }
    
    private function getFilePath(Document $document): string
    {
        $paths = [
            storage_path('app/public/' . $document->file_path),
            storage_path('app/' . $document->file_path),
            public_path('storage/' . $document->file_path),
            $document->file_path,
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        throw new \Exception("File not found for document {$document->id}");
    }
}