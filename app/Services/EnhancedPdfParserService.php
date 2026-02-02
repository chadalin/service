<?php

namespace App\Services;

use Smalot\PdfParser\Parser;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;
use Throwable;

class EnhancedPdfParserService
{
    protected $parser;
    protected $imageService;
    
    public function __construct()
    {
        $this->parser = new Parser();
        $this->imageService = new SimpleImageExtractionService();
    }
    
    /**
     * Полный парсинг PDF документа с сохранением в БД
     */
    public function parseFullDocument($documentId, $filePath, $maxPages = null)
    {
        try {
            $document = \App\Models\Document::findOrFail($documentId);
            
            // Обновляем статус
            $document->update([
                'status' => 'processing',
                'processing_started_at' => now(),
                'parsing_progress' => 0
            ]);
            
            // Парсим PDF
            $pdf = $this->parser->parseFile($filePath);
            $pages = $pdf->getPages();
            $totalPages = count($pages);
            
            // Устанавливаем общее количество страниц
            $document->update([
                'total_pages' => $totalPages
            ]);
            
            // Ограничиваем количество страниц если указано
            if ($maxPages) {
                $pages = array_slice($pages, 0, $maxPages);
            }
            
            // Извлекаем все изображения из документа
            $allImages = $this->imageService->extractAllImages($filePath, 'document_images');
            Log::info("Extracted " . count($allImages) . " images from document {$documentId}");
            
            // Группируем изображения по страницам (если возможно)
            $imagesByPage = $this->groupImagesByPage($allImages, $documentId);
            
            $totalWords = 0;
            $totalCharacters = 0;
            
            // Обрабатываем каждую страницу
            foreach ($pages as $index => $page) {
                try {
                    $pageNumber = $index + 1;
                    
                    // Обновляем прогресс
                    $progress = ($pageNumber / $totalPages) * 100;
                    $document->update(['parsing_progress' => $progress]);
                    
                    // Получаем текст страницы
                    $contentText = $page->getText();
                    $wordCount = str_word_count($contentText);
                    $charCount = mb_strlen($contentText);
                    
                    // Извлекаем детали страницы
                    $details = $page->getDetails();
                    
                    // Определяем заголовок раздела (если есть)
                    $sectionTitle = $this->extractSectionTitle($contentText);
                    
                    // Получаем изображения для этой страницы
                    $pageImages = $imagesByPage[$pageNumber] ?? [];
                    
                    // Сохраняем страницу в БД
                    $documentPage = \App\Models\DocumentPage::create([
                        'document_id' => $documentId,
                        'page_number' => $pageNumber,
                        'content' => $this->formatHtmlContent($contentText),
                        'content_text' => $contentText,
                        'word_count' => $wordCount,
                        'character_count' => $charCount,
                        'paragraph_count' => $this->countParagraphs($contentText),
                        'section_title' => $sectionTitle,
                        'has_images' => !empty($pageImages),
                        'metadata' => json_encode($details),
                        'parsing_quality' => $this->calculateParsingQuality($contentText),
                        'status' => 'parsed'
                    ]);
                    
                    // Сохраняем изображения страницы
                    if (!empty($pageImages)) {
                        foreach ($pageImages as $imageData) {
                            $this->saveDocumentImage($documentPage, $imageData);
                        }
                    }
                    
                    // Создаем превью для первой страницы
                    if ($pageNumber === 1 && !empty($pageImages)) {
                        $this->createPreviewImage($documentPage, $pageImages[0]);
                    }
                    
                    $totalWords += $wordCount;
                    $totalCharacters += $charCount;
                    
                } catch (Throwable $e) {
                    Log::error("Error processing page {$pageNumber}: " . $e->getMessage());
                    continue;
                }
            }
            
            // Обновляем статистику документа
            $parsingQuality = $this->calculateDocumentParsingQuality($documentId);
            
            $document->update([
                'content_text' => $this->extractAllDocumentText($documentId),
                'word_count' => $totalWords,
                'is_parsed' => true,
                'parsing_quality' => $parsingQuality,
                'parsing_progress' => 100,
                'parsed_at' => now(),
                'status' => 'parsed'
            ]);
            
            return [
                'success' => true,
                'total_pages' => $totalPages,
                'processed_pages' => count($pages),
                'word_count' => $totalWords,
                'images_extracted' => count($allImages),
                'parsing_quality' => $parsingQuality
            ];
            
        } catch (Throwable $e) {
            Log::error("PDF parsing failed: " . $e->getMessage());
            
            // Обновляем статус при ошибке
            if (isset($document)) {
                $document->update([
                    'status' => 'parse_error',
                    'parsing_progress' => 0
                ]);
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Создание предпросмотра (первые N страниц)
     */
    public function createPreview($documentId, $filePath, $previewPages = 5)
    {
        try {
            $document = \App\Models\Document::findOrFail($documentId);
            
            $document->update([
                'status' => 'processing',
                'processing_started_at' => now(),
                'parsing_progress' => 0
            ]);
            
            // Удаляем существующие превью-страницы
            \App\Models\DocumentPage::where('document_id', $documentId)
                ->where('is_preview', true)
                ->delete();
            
            // Парсим PDF
            $pdf = $this->parser->parseFile($filePath);
            $pages = $pdf->getPages();
            $totalPages = count($pages);
            
            $document->update(['total_pages' => $totalPages]);
            
            // Берем первые N страниц для предпросмотра
            $previewPages = array_slice($pages, 0, min($previewPages, $totalPages));
            
            // Извлекаем изображения (ограниченное количество для предпросмотра)
            $previewImages = $this->imageService->extractAllImages($filePath, 'document_images/preview');
            
            $totalWords = 0;
            $pageNumber = 0;
            
            foreach ($previewPages as $index => $page) {
                $pageNumber = $index + 1;
                
                // Обновляем прогресс
                $progress = ($pageNumber / count($previewPages)) * 100;
                $document->update(['parsing_progress' => $progress]);
                
                $contentText = $page->getText();
                $wordCount = str_word_count($contentText);
                
                // Сохраняем страницу как превью
                $documentPage = \App\Models\DocumentPage::create([
                    'document_id' => $documentId,
                    'page_number' => $pageNumber,
                    'content' => $this->formatHtmlContent($contentText),
                    'content_text' => $contentText,
                    'word_count' => $wordCount,
                    'character_count' => mb_strlen($contentText),
                    'is_preview' => true,
                    'section_title' => $this->extractSectionTitle($contentText),
                    'has_images' => false, // В превью сохраняем только текст
                    'parsing_quality' => $this->calculateParsingQuality($contentText),
                    'status' => 'preview'
                ]);
                
                $totalWords += $wordCount;
            }
            
            // Рассчитываем качество парсинга на основе превью
            $parsingQuality = $this->calculatePreviewParsingQuality($documentId);
            
            $document->update([
                'word_count' => $totalWords,
                'parsing_quality' => $parsingQuality,
                'parsing_progress' => 100,
                'status' => 'preview_created',
                'is_parsed' => false
            ]);
            
            return [
                'success' => true,
                'preview_pages' => count($previewPages),
                'total_pages' => $totalPages,
                'word_count' => $totalWords,
                'parsing_quality' => $parsingQuality
            ];
            
        } catch (Throwable $e) {
            Log::error("Preview creation failed: " . $e->getMessage());
            
            if (isset($document)) {
                $document->update([
                    'status' => 'parse_error',
                    'parsing_progress' => 0
                ]);
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Извлечение только изображений
     */
    public function extractImagesOnly($documentId, $filePath, $limit = 50)
    {
        try {
            $document = \App\Models\Document::findOrFail($documentId);
            
            $document->update([
                'status' => 'processing',
                'processing_started_at' => now()
            ]);
            
            // Извлекаем все изображения
            $images = $this->imageService->extractAllImages($filePath, 'document_images');
            
            // Создаем страницу-контейнер для изображений
            $documentPage = \App\Models\DocumentPage::create([
                'document_id' => $documentId,
                'page_number' => 0, // Специальная страница для изображений
                'content' => '<p>Извлеченные изображения</p>',
                'content_text' => 'Извлеченные изображения',
                'word_count' => 0,
                'has_images' => true,
                'status' => 'images_only'
            ]);
            
            // Сохраняем каждое изображение
            $imageCount = 0;
            foreach ($images as $imageData) {
                if ($imageCount >= $limit) break;
                
                $this->saveDocumentImage($documentPage, $imageData);
                $imageCount++;
            }
            
            $document->update([
                'status' => 'parsed',
                'is_parsed' => true
            ]);
            
            return [
                'success' => true,
                'images_extracted' => $imageCount
            ];
            
        } catch (Throwable $e) {
            Log::error("Image extraction failed: " . $e->getMessage());
            
            if (isset($document)) {
                $document->update([
                    'status' => 'parse_error'
                ]);
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Парсинг одной конкретной страницы
     */
    public function parseSinglePage($documentId, $filePath, $pageNumber)
    {
        try {
            $document = \App\Models\Document::findOrFail($documentId);
            
            $pdf = $this->parser->parseFile($filePath);
            $pages = $pdf->getPages();
            
            if ($pageNumber > count($pages) || $pageNumber < 1) {
                throw new Exception("Страница {$pageNumber} не существует");
            }
            
            $page = $pages[$pageNumber - 1];
            $contentText = $page->getText();
            $wordCount = str_word_count($contentText);
            
            // Проверяем, существует ли уже страница
            $existingPage = \App\Models\DocumentPage::where('document_id', $documentId)
                ->where('page_number', $pageNumber)
                ->first();
            
            if ($existingPage) {
                $existingPage->update([
                    'content' => $this->formatHtmlContent($contentText),
                    'content_text' => $contentText,
                    'word_count' => $wordCount,
                    'character_count' => mb_strlen($contentText),
                    'updated_at' => now()
                ]);
            } else {
                \App\Models\DocumentPage::create([
                    'document_id' => $documentId,
                    'page_number' => $pageNumber,
                    'content' => $this->formatHtmlContent($contentText),
                    'content_text' => $contentText,
                    'word_count' => $wordCount,
                    'character_count' => mb_strlen($contentText),
                    'section_title' => $this->extractSectionTitle($contentText),
                    'parsing_quality' => $this->calculateParsingQuality($contentText),
                    'status' => 'parsed'
                ]);
            }
            
            return [
                'success' => true,
                'page_number' => $pageNumber,
                'word_count' => $wordCount
            ];
            
        } catch (Throwable $e) {
            Log::error("Single page parsing failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Группировка изображений по страницам
     */
    protected function groupImagesByPage($images, $documentId)
    {
        $imagesByPage = [];
        
        // В простой реализации распределяем изображения равномерно
        // В реальном приложении нужно анализировать позиции изображений в PDF
        $imageCount = count($images);
        $pagesPerImage = max(1, floor($document->total_pages / $imageCount));
        
        $currentPage = 1;
        foreach ($images as $image) {
            $imagesByPage[$currentPage][] = $image;
            $currentPage = min($document->total_pages, $currentPage + $pagesPerImage);
        }
        
        return $imagesByPage;
    }
    
    /**
     * Сохранение изображения документа
     */
    protected function saveDocumentImage($documentPage, $imageData)
    {
        $thumbnailPath = $this->createThumbnail($imageData['path']);
        
        return \App\Models\DocumentImage::create([
            'document_id' => $documentPage->document_id,
            'page_id' => $documentPage->id,
            'page_number' => $documentPage->page_number,
            'filename' => basename($imageData['path']),
            'path' => $imageData['path'],
            'url' => $imageData['url'],
            'thumbnail_path' => $thumbnailPath,
            'thumbnail_url' => Storage::url($thumbnailPath),
            'width' => $imageData['width'] ?? null,
            'height' => $imageData['height'] ?? null,
            'size' => Storage::disk('public')->size($imageData['path']),
            'mime_type' => $this->getMimeType($imageData['path']),
            'extension' => pathinfo($imageData['path'], PATHINFO_EXTENSION),
            'description' => "Image from page {$documentPage->page_number}",
            'position' => 0,
            'status' => 'active'
        ]);
    }
    
    /**
     * Создание превью изображения
     */
    protected function createPreviewImage($documentPage, $imageData)
    {
        // Помечаем первое изображение как превью
        $documentPage->update(['is_preview' => true]);
        
        if (isset($imageData['path'])) {
            \App\Models\DocumentImage::where('path', $imageData['path'])
                ->update(['is_preview' => true]);
        }
    }
    
    /**
     * Создание миниатюры
     */
    protected function createThumbnail($imagePath)
    {
        try {
            $fullPath = Storage::disk('public')->path($imagePath);
            
            // Создаем директорию для миниатюр
            $thumbDir = 'document_images/thumbs/' . dirname($imagePath);
            Storage::disk('public')->makeDirectory($thumbDir);
            
            $thumbPath = $thumbDir . '/thumb_' . basename($imagePath);
            $thumbFullPath = Storage::disk('public')->path($thumbPath);
            
            // Используем GD для создания миниатюры
            list($width, $height, $type) = getimagesize($fullPath);
            
            $newWidth = 200;
            $newHeight = (int)($height * ($newWidth / $width));
            
            $source = $this->createImageResource($fullPath, $type);
            $thumb = imagecreatetruecolor($newWidth, $newHeight);
            
            imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            
            $this->saveImageResource($thumb, $thumbFullPath, $type);
            
            imagedestroy($source);
            imagedestroy($thumb);
            
            return $thumbPath;
            
        } catch (Throwable $e) {
            Log::error("Thumbnail creation failed: " . $e->getMessage());
            return $imagePath; // Возвращаем оригинальный путь при ошибке
        }
    }
    
    /**
     * Создание ресурса изображения
     */
    protected function createImageResource($path, $type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($path);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($path);
            default:
                throw new Exception("Unsupported image type");
        }
    }
    
    /**
     * Сохранение ресурса изображения
     */
    protected function saveImageResource($resource, $path, $type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($resource, $path, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($resource, $path, 8);
                break;
            case IMAGETYPE_GIF:
                imagegif($resource, $path);
                break;
        }
    }
    
    /**
     * Получение MIME-типа
     */
    protected function getMimeType($path)
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'webp' => 'image/webp'
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
    
    /**
     * Извлечение заголовка раздела
     */
    protected function extractSectionTitle($text)
    {
        // Ищем строки, которые могут быть заголовками
        $lines = explode("\n", $text);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Проверяем признаки заголовка
            if (mb_strlen($line) < 100 && 
                !empty($line) &&
                preg_match('/^[A-ZА-Я0-9\s\.\-:]+$/u', $line)) {
                return $line;
            }
        }
        
        return null;
    }
    
    /**
     * Форматирование HTML-контента
     */
    protected function formatHtmlContent($text)
    {
        $lines = explode("\n", $text);
        $html = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line)) {
                continue;
            }
            
            // Проверяем, является ли строка заголовком
            if ($this->isHeading($line)) {
                $html .= "<h4 class='document-heading'>" . htmlspecialchars($line) . "</h4>\n";
            } else {
                $html .= "<p class='document-paragraph'>" . htmlspecialchars($line) . "</p>\n";
            }
        }
        
        return $html;
    }
    
    /**
     * Проверка, является ли строка заголовком
     */
    protected function isHeading($line)
    {
        $length = mb_strlen($line);
        
        // Заголовки обычно короче 100 символов
        if ($length > 100) {
            return false;
        }
        
        // Проверяем паттерны заголовков
        $headingPatterns = [
            '/^[0-9]+[\.\)]\s+/u', // "1. " или "1) "
            '/^[IVXLCDM]+[\.\)]\s+/ui', // Римские цифры
            '/^[А-ЯA-Z][а-яa-z]+\s*:/u', // Слово с двоеточием
            '/^[А-ЯA-Z\s]+$/u', // Все заглавные
        ];
        
        foreach ($headingPatterns as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Подсчет параграфов
     */
    protected function countParagraphs($text)
    {
        $lines = explode("\n", $text);
        $paragraphs = 0;
        
        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $paragraphs++;
            }
        }
        
        return $paragraphs;
    }
    
    /**
     * Расчет качества парсинга для страницы
     */
    protected function calculateParsingQuality($text)
    {
        $length = mb_strlen($text);
        
        if ($length === 0) {
            return 0.0;
        }
        
        // Проверяем признаки хорошего текста
        $quality = 0.5; // Базовое качество
        
        // Наличие пунктуации
        $hasPunctuation = preg_match('/[\.\?!,;:]/u', $text);
        if ($hasPunctuation) $quality += 0.2;
        
        // Наличие заглавных букв в начале предложений
        $hasProperSentences = preg_match('/[\.\?!]\s+[А-ЯA-Z]/u', $text);
        if ($hasProperSentences) $quality += 0.2;
        
        // Минимальная длина
        if ($length > 100) $quality += 0.1;
        
        return min(1.0, $quality);
    }
    
    /**
     * Расчет качества парсинга документа
     */
    protected function calculateDocumentParsingQuality($documentId)
    {
        $pages = \App\Models\DocumentPage::where('document_id', $documentId)
            ->where('is_preview', false)
            ->get();
        
        if ($pages->isEmpty()) {
            return 0.0;
        }
        
        $totalQuality = 0;
        foreach ($pages as $page) {
            $totalQuality += $page->parsing_quality ?? 0.5;
        }
        
        return $totalQuality / $pages->count();
    }
    
    /**
     * Расчет качества для превью
     */
    protected function calculatePreviewParsingQuality($documentId)
    {
        $pages = \App\Models\DocumentPage::where('document_id', $documentId)
            ->where('is_preview', true)
            ->get();
        
        if ($pages->isEmpty()) {
            return 0.0;
        }
        
        $totalQuality = 0;
        foreach ($pages as $page) {
            $totalQuality += $page->parsing_quality ?? 0.5;
        }
        
        return $totalQuality / $pages->count();
    }
    
    /**
     * Извлечение всего текста документа
     */
    protected function extractAllDocumentText($documentId)
    {
        $pages = \App\Models\DocumentPage::where('document_id', $documentId)
            ->where('is_preview', false)
            ->orderBy('page_number')
            ->get();
        
        $fullText = '';
        foreach ($pages as $page) {
            $fullText .= $page->content_text . "\n\n";
        }
        
        return trim($fullText);
    }
}