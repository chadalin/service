<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentPage;
use App\Models\DocumentImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use Symfony\Component\Process\Process;
use Intervention\Image\Laravel\Facades\Image;
use thiagoalessio\TesseractOCR\TesseractOCR;

class AdvancedDocumentParser
{
    private $pdfParser;
    private $imageManager;
    
    public function __construct()
    {
        $this->pdfParser = new PdfParser();
       // $this->imageManager = new ImageManager(['driver' => 'gd']);
    }
    
    /**
     * Создание предпросмотра документа
     */
     public function createPreview(Document $document, $pagesCount = 5)
    {
        Log::info("Создание предпросмотра документа {$document->id}");
        
        $filePath = storage_path('app/' . $document->file_path);
        
        if (!file_exists($filePath)) {
            throw new \Exception("Файл не найден: {$document->file_path}");
        }
        
        $totalPages = $this->getTotalPages($document, $filePath);
        $pages = [];
        
        // Парсим первые N страниц
        for ($i = 1; $i <= min($pagesCount, $totalPages); $i++) {
            try {
                $pageData = $this->parsePage($document, $filePath, $i, true);
                $pages[] = $pageData;
                
                // Сохраняем в базу
                $this->savePageToDatabase($document, $pageData, true);
                
            } catch (\Exception $e) {
                Log::error("Ошибка парсинга страницы {$i}: " . $e->getMessage());
                continue;
            }
        }
        
        // Обновляем информацию о документе
        $document->update([
            'total_pages' => $totalPages,
            'parsing_quality' => $this->calculateQuality($pages),
            'is_parsed' => false,
            'status' => 'preview_created'
        ]);
        
        return [
            'pages' => $pages,
            'total_pages' => $totalPages,
            'images_count' => 0 // Упрощено
        ];
    }
    
    /**
     * Полный парсинг документа
     */
    public function parseFull(Document $document)
    {
        Log::info("Начало полного парсинга документа {$document->id}");
        
        $filePath = storage_path('app/' . $document->file_path);
        
        if (!file_exists($filePath)) {
            throw new \Exception("Файл не найден: {$document->file_path}");
        }
        
        $totalPages = $this->getTotalPages($document, $filePath);
        $allPages = [];
        
        // Очищаем все страницы (включая предпросмотр)
        DocumentPage::where('document_id', $document->id)->delete();
        DocumentImage::where('document_id', $document->id)->delete();
        
        // Парсим все страницы
        for ($i = 1; $i <= $totalPages; $i++) {
            try {
                $pageData = $this->parsePage($document, $filePath, $i, false);
                $allPages[] = $pageData;
                
                // Сохраняем в базу
                $this->savePageToDatabase($document, $pageData, false);
                
                // Обновляем прогресс каждые 10 страниц
                if ($i % 10 === 0) {
                    $document->update([
                        'parsing_progress' => ($i / $totalPages) * 100
                    ]);
                    Log::info("Прогресс парсинга документа {$document->id}: " . round(($i / $totalPages) * 100) . "%");
                }
                
            } catch (\Exception $e) {
                Log::error("Ошибка парсинга страницы {$i}: " . $e->getMessage());
                
                // Создаем пустую страницу в случае ошибки
                $this->savePageToDatabase($document, [
                    'page_number' => $i,
                    'content' => '<p>Ошибка парсинга страницы</p>',
                    'content_text' => 'Ошибка парсинга страницы',
                    'word_count' => 0,
                    'character_count' => 0,
                    'paragraph_count' => 0,
                    'tables_count' => 0,
                    'section_title' => null,
                    'metadata' => ['error' => $e->getMessage()],
                    'images' => [],
                    'images_count' => 0
                ], false);
            }
        }
        
        // Объединяем текст всех страниц для основного содержимого
        $fullContent = '';
        $fullText = '';
        
        foreach ($allPages as $page) {
            $fullContent .= $page['content'] . "\n\n";
            $fullText .= $page['content_text'] . "\n\n";
        }
        
        // Обновляем документ
        $document->update([
            'content' => $fullContent,
            'content_text' => $fullText,
            'total_pages' => $totalPages,
            'parsing_quality' => $this->calculateQuality($allPages),
            'is_parsed' => true,
            'status' => 'parsed',
            'parsing_progress' => 100,
            'parsed_at' => now()
        ]);
        
        Log::info("Полный парсинг документа {$document->id} завершен успешно");
        
        return [
            'total_pages' => $totalPages,
            'pages_parsed' => count($allPages),
            'total_words' => array_sum(array_column($allPages, 'word_count')),
            'total_images' => array_sum(array_column($allPages, 'images_count'))
        ];
    }
    
    /**
     * Парсинг одной страницы
     */
    private function parsePage(Document $document, $filePath, $pageNumber, $isPreview = false)
    {
        Log::info("Парсинг страницы {$pageNumber} документа {$document->id}");
        
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'pdf':
                return $this->parsePdfPage($document, $filePath, $pageNumber, $isPreview);
            case 'doc':
            case 'docx':
                return $this->parseWordPage($document, $filePath, $pageNumber, $isPreview);
            default:
                throw new \Exception("Неподдерживаемый формат файла: {$extension}");
        }
    }
    
    /**
     * Парсинг страницы PDF
     */
    private function parsePdfPage(Document $document, $filePath, $pageNumber, $isPreview)
    {
        try {
            // Используем pdftotext для лучшего извлечения текста
            $text = $this->extractPdfTextWithPdftotext($filePath, $pageNumber);
            
            if (empty($text)) {
                // Пробуем использовать библиотеку
                $pdf = $this->pdfParser->parseFile($filePath);
                $pages = $pdf->getPages();
                
                if (isset($pages[$pageNumber - 1])) {
                    $text = $pages[$pageNumber - 1]->getText();
                }
            }
            
            // Извлекаем изображения из PDF
            $images = $this->extractPdfImages($document, $filePath, $pageNumber, $isPreview);
            
            // Анализируем структуру страницы
            $sectionTitle = $this->detectSectionTitle($text);
            
            return [
                'page_number' => $pageNumber,
                'content' => $this->formatContent($text),
                'content_text' => $this->cleanText($text),
                'word_count' => str_word_count($text),
                'character_count' => strlen($text),
                'paragraph_count' => count(explode("\n\n", trim($text))),
                'tables_count' => $this->countTables($text),
                'section_title' => $sectionTitle,
                'metadata' => $this->extractMetadata($text),
                'images' => $images,
                'images_count' => count($images)
            ];
            
        } catch (\Exception $e) {
            Log::error("Ошибка парсинга PDF страницы {$pageNumber}: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Парсинг страницы Word документа
     */
    private function parseWordPage(Document $document, $filePath, $pageNumber, $isPreview)
    {
        try {
            $phpWord = WordIOFactory::load($filePath);
            $text = '';
            
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . "\n";
                    }
                }
            }
            
            // Разделяем текст на страницы (примерно)
            $lines = explode("\n", $text);
            $linesPerPage = 50; // Примерное количество строк на страницу
            $startLine = ($pageNumber - 1) * $linesPerPage;
            $pageText = implode("\n", array_slice($lines, $startLine, $linesPerPage));
            
            return [
                'page_number' => $pageNumber,
                'content' => $this->formatContent($pageText),
                'content_text' => $this->cleanText($pageText),
                'word_count' => str_word_count($pageText),
                'character_count' => strlen($pageText),
                'paragraph_count' => count(explode("\n\n", trim($pageText))),
                'tables_count' => 0,
                'section_title' => $this->detectSectionTitle($pageText),
                'metadata' => $this->extractMetadata($pageText),
                'images' => [], // Word изображения сложнее извлекать
                'images_count' => 0
            ];
            
        } catch (\Exception $e) {
            Log::error("Ошибка парсинга Word страницы {$pageNumber}: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Извлечение текста из PDF с помощью pdftotext
     */
    private function extractPdfTextWithPdftotext($filePath, $pageNumber)
    {
        if (!function_exists('shell_exec')) {
            return '';
        }
        
        $tempFile = tempnam(sys_get_temp_dir(), 'pdf_text_');
        $command = "pdftotext -f {$pageNumber} -l {$pageNumber} \"{$filePath}\" \"{$tempFile}\" 2>&1";
        
        $process = new Process(explode(' ', $command));
        $process->run();
        
        if (!$process->isSuccessful()) {
            return '';
        }
        
        $text = file_get_contents($tempFile);
        unlink($tempFile);
        
        return $text ?: '';
    }
    
    /**
     * Извлечение изображений из PDF
     */
    private function extractPdfImages(Document $document, $filePath, $pageNumber, $isPreview)
    {
        $images = [];
        
        if (!function_exists('shell_exec') || !$this->commandExists('pdfimages')) {
            return $images;
        }
        
        $tempDir = storage_path('app/temp/' . Str::random(20));
        mkdir($tempDir, 0755, true);
        
        try {
            // Используем pdfimages для извлечения изображений
            $command = "pdfimages -p -j \"{$filePath}\" \"{$tempDir}/image\" 2>&1";
            
            $process = new Process(explode(' ', $command));
            $process->setTimeout(300);
            $process->run();
            
            if ($process->isSuccessful()) {
                $files = glob("{$tempDir}/image-*.{jpg,jpeg,png}", GLOB_BRACE);
                
                foreach ($files as $index => $imageFile) {
                    $imageInfo = getimagesize($imageFile);
                    
                    if ($imageInfo && $imageInfo[0] > 50 && $imageInfo[1] > 50) {
                        $filename = "doc_{$document->id}_page_{$pageNumber}_img_" . ($index + 1) . ".jpg";
                        $storagePath = "documents/{$document->id}/pages/{$pageNumber}/" . $filename;
                        
                        try {
                            // Создаем директорию если не существует
                            $directory = dirname(storage_path("app/" . $storagePath));
                            if (!is_dir($directory)) {
                                mkdir($directory, 0755, true);
                            }
                            
                            // Используем Intervention Image 3.x
                            $image = Image::read($imageFile);
                            
                            // Ресайз если нужно
                            if ($image->width() > 1200) {
                                $image->scale(width: 1200);
                            }
                            
                            // Сохраняем
                            $image->save(storage_path("app/" . $storagePath), quality: 85);
                            
                            $images[] = [
                                'filename' => $filename,
                                'path' => $storagePath,
                                'url' => Storage::url($storagePath),
                                'width' => $image->width(),
                                'height' => $image->height(),
                                'size' => filesize(storage_path("app/" . $storagePath)),
                                'description' => 'Изображение из документа',
                                'ocr_text' => '',
                                'position' => $index + 1
                            ];
                            
                        } catch (\Exception $e) {
                            Log::warning("Ошибка обработки изображения: " . $e->getMessage());
                            // Сохраняем оригинал без обработки
                            copy($imageFile, storage_path("app/" . $storagePath));
                        }
                    }
                }
            }
            
        } catch (\Exception $e) {
            Log::error("Ошибка извлечения изображений: " . $e->getMessage());
        } finally {
            // Очистка временных файлов
            if (is_dir($tempDir)) {
                array_map('unlink', glob("{$tempDir}/*"));
                rmdir($tempDir);
            }
        }
        
        return $images;
    }
    /**
     * OCR для извлечения текста с изображений
     */
    private function extractTextFromImage($imagePath)
    {
        if (!class_exists('thiagoalessio\TesseractOCR\TesseractOCR')) {
            return '';
        }
        
        try {
            $ocr = new TesseractOCR($imagePath);
            $ocr->lang('rus+eng');
            return $ocr->run();
        } catch (\Exception $e) {
            Log::warning("OCR ошибка: " . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Сохранение страницы в базу данных
     */
    private function savePageToDatabase(Document $document, $pageData, $isPreview)
    {
        $page = DocumentPage::create([
            'document_id' => $document->id,
            'page_number' => $pageData['page_number'],
            'content' => $pageData['content'],
            'content_text' => $pageData['content_text'],
            'word_count' => $pageData['word_count'],
            'character_count' => $pageData['character_count'],
            'paragraph_count' => $pageData['paragraph_count'],
            'tables_count' => $pageData['tables_count'],
            'section_title' => $pageData['section_title'],
            'metadata' => json_encode($pageData['metadata']),
            'is_preview' => $isPreview,
            'parsing_quality' => $this->calculatePageQuality($pageData),
            'status' => 'parsed'
        ]);
        
        // Сохраняем изображения
        foreach ($pageData['images'] as $imageData) {
            DocumentImage::create([
                'document_id' => $document->id,
                'page_id' => $page->id,
                'page_number' => $pageData['page_number'],
                'filename' => $imageData['filename'],
                'path' => $imageData['path'],
                'url' => $imageData['url'],
                'width' => $imageData['width'],
                'height' => $imageData['height'],
                'size' => $imageData['size'],
                'description' => $imageData['description'],
                'ocr_text' => $imageData['ocr_text'] ?? null,
                'position' => $imageData['position'],
                'is_preview' => $isPreview,
                'status' => 'extracted'
            ]);
        }
        
        return $page;
    }
    
    /**
     * Получение общего количества страниц
     */
    private function getTotalPages(Document $document, $filePath)
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if ($extension === 'pdf') {
            if (function_exists('shell_exec') && $this->commandExists('pdfinfo')) {
                $command = "pdfinfo \"{$filePath}\" 2>&1";
                $output = shell_exec($command);
                
                if (preg_match('/Pages:\s*(\d+)/', $output, $matches)) {
                    return (int) $matches[1];
                }
            }
            
            // Альтернативный метод
            try {
                $pdf = $this->pdfParser->parseFile($filePath);
                return count($pdf->getPages());
            } catch (\Exception $e) {
                Log::warning("Не удалось определить количество страниц PDF: " . $e->getMessage());
            }
        }
        
        // Для Word документов возвращаем примерное количество
        return 1;
    }
    
    /**
     * Проверка наличия команды в системе
     */
    private function commandExists($command)
    {
        $which = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'where' : 'which';
        $process = new Process([$which, $command]);
        $process->run();
        return $process->isSuccessful();
    }
    
    /**
     * Форматирование контента
     */
    private function formatContent($text)
    {
        $paragraphs = explode("\n\n", trim($text));
        $formatted = '';
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (!empty($paragraph)) {
                // Проверяем, является ли абзац заголовком
                if (strlen($paragraph) < 100 && preg_match('/^[A-ZА-Я0-9\s\.\-:]+$/u', $paragraph)) {
                    $formatted .= '<h3 class="document-heading">' . htmlspecialchars($paragraph) . '</h3>';
                } else {
                    $formatted .= '<p class="document-paragraph">' . nl2br(htmlspecialchars($paragraph)) . '</p>';
                }
            }
        }
        
        return $formatted;
    }
    
    /**
     * Очистка текста
     */
    private function cleanText($text)
    {
        // Удаляем лишние пробелы и переносы
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        
        // Удаляем специальные символы, но оставляем пунктуацию
        $text = preg_replace('/[^\x20-\x7F\xA0-\xFF\x{0400}-\x{04FF}\.,;:!?\-\(\)\[\]\{\}]/u', ' ', $text);
        
        return trim($text);
    }
    
    /**
     * Определение раздела/заголовка
     */
    private function detectSectionTitle($text)
    {
        $lines = explode("\n", trim($text));
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Проверяем, похожа ли строка на заголовок
            if (strlen($line) > 3 && strlen($line) < 200) {
                // Проверка на заглавные буквы в начале
                if (preg_match('/^[A-ZА-Я0-9][A-ZА-Яa-zа-я0-9\s\.\-:]+$/u', $line)) {
                    // Исключаем очевидные не-заголовки
                    if (!preg_match('/(стр\.|страница|page|\d+\s*из\s*\d+)/iu', $line)) {
                        return $line;
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * Извлечение метаданных из текста
     */
    private function extractMetadata($text)
    {
        $metadata = [];
        
        // Поиск номеров деталей
        preg_match_all('/[A-Z]{1,5}[\-\s]?\d{4,10}[A-Z]?/i', $text, $partNumbers);
        if (!empty($partNumbers[0])) {
            $metadata['part_numbers'] = array_unique($partNumbers[0]);
        }
        
        // Поиск времени выполнения
        if (preg_match('/(\d+[\.,]?\d*)\s*(час|ч|мин|мин\.|минут)/iu', $text, $timeMatches)) {
            $metadata['estimated_time'] = $timeMatches[1] . ' ' . $timeMatches[2];
        }
        
        // Поиск сложности
        if (preg_match('/(легк|средн|сложн|простой|сложный)/iu', $text, $difficultyMatches)) {
            $metadata['difficulty'] = mb_strtolower($difficultyMatches[1]);
        }
        
        return $metadata;
    }
    
    /**
     * Подсчет таблиц в тексте
     */
    private function countTables($text)
    {
        // Простая эвристика для поиска таблиц
        $lines = explode("\n", $text);
        $tableCount = 0;
        $inTable = false;
        
        foreach ($lines as $line) {
            if (preg_match('/^\s*(\|.+\|)\s*$/', $line) || 
                preg_match('/^\s*(\+.+\+)\s*$/', $line) ||
                preg_match('/^\s*(\-+\s+\-+)/', $line)) {
                if (!$inTable) {
                    $tableCount++;
                    $inTable = true;
                }
            } else {
                $inTable = false;
            }
        }
        
        return $tableCount;
    }
    
    /**
     * Генерация описания изображения
     */
    private function generateImageDescription($ocrText)
    {
        if (empty($ocrText)) {
            return 'Иллюстрация к документу';
        }
        
        $cleanText = substr(trim($ocrText), 0, 100);
        return 'Изображение: ' . $cleanText . (strlen($ocrText) > 100 ? '...' : '');
    }
    
    /**
     * Расчет качества парсинга страницы
     */
    private function calculatePageQuality($pageData)
    {
        $quality = 0.5; // Базовая оценка
        
        // Бонус за наличие текста
        if ($pageData['word_count'] > 10) {
            $quality += 0.2;
        }
        
        // Бонус за структуру
        if ($pageData['paragraph_count'] > 1) {
            $quality += 0.1;
        }
        
        // Бонус за заголовок раздела
        if (!empty($pageData['section_title'])) {
            $quality += 0.1;
        }
        
        // Бонус за изображения
        if ($pageData['images_count'] > 0) {
            $quality += 0.1;
        }
        
        return min(1.0, $quality);
    }
    
    /**
     * Расчет общего качества парсинга
     */
    private function calculateQuality($pages)
    {
        if (empty($pages)) {
            return 0.0;
        }
        
        $totalQuality = 0;
        foreach ($pages as $page) {
            $totalQuality += $this->calculatePageQuality($page);
        }
        
        return round($totalQuality / count($pages), 2);
    }
}