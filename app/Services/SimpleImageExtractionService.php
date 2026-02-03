<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;
use Smalot\PdfParser\Config;

class SimpleImageExtractionService
{
    /**
     * Извлекает все изображения из PDF файла
     */
    public function extractAllImages($pdfFilePath)
    {
        Log::info("🔍 Начинаем извлечение изображений из: {$pdfFilePath}");
        
        $images = [];
        
        try {
            if (!file_exists($pdfFilePath)) {
                throw new Exception("PDF файл не найден: {$pdfFilePath}");
            }
            
            // Создаем конфигурацию парсера
            $config = new Config();
            $parser = new Parser([], $config);
            $pdf = $parser->parseFile($pdfFilePath);
            
            // Получаем все объекты
            $objects = $pdf->getObjects();
            $pageNumber = 1;
            $imageIndex = 0;
            
            Log::info("🔍 Анализируем объекты PDF...");
            
            foreach ($objects as $object) {
                // Проверяем, является ли объект изображением
                if ($this->isImageObject($object)) {
                    try {
                        $imageIndex++;
                        
                        // Получаем содержимое
                        $content = $object->getContent();
                        
                        if (empty($content)) {
                            continue;
                        }
                        
                        // Определяем формат
                        $format = $this->detectImageFormat($content);
                        
                        if (!$format) {
                            continue;
                        }
                        
                        // Пытаемся определить страницу
                        // (это приблизительно, но лучше чем ничего)
                        $pageForImage = $this->guessPageNumber($object, $pageNumber);
                        
                        $images[] = [
                            'index' => $imageIndex,
                            'page' => $pageForImage,
                            'format' => $format,
                            'content' => $content,
                            'size' => strlen($content),
                        ];
                        
                        Log::debug("✅ Найдено изображение #{$imageIndex} на странице ~{$pageForImage}");
                        
                    } catch (Exception $e) {
                        Log::warning("Ошибка обработки изображения: " . $e->getMessage());
                    }
                }
            }
            
            Log::info("✅ Найдено изображений: " . count($images));
            
        } catch (Exception $e) {
            Log::error("❌ Ошибка извлечения изображений: " . $e->getMessage());
        }
        
        return $images;
    }
    
    /**
     * Проверяет, является ли объект изображением
     */
    private function isImageObject($object)
    {
        try {
            $details = $object->getDetails();
            
            // Проверяем признаки изображения
            $hasWidth = isset($details['Width']) && $details['Width'] > 0;
            $hasHeight = isset($details['Height']) && $details['Height'] > 0;
            $hasFilter = isset($details['Filter']) || isset($details['ColorSpace']);
            
            return $hasWidth && $hasHeight && $hasFilter;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Определяет формат изображения
     */
    private function detectImageFormat($content)
    {
        // Проверяем первые байты
        $firstBytes = substr($content, 0, 8);
        
        // JPEG
        if (strpos($firstBytes, "\xFF\xD8") === 0) {
            return 'jpg';
        }
        
        // PNG
        if (strpos($firstBytes, "\x89PNG") === 0) {
            return 'png';
        }
        
        // JPEG 2000
        if (strpos($firstBytes, "\x00\x00\x00\x0C\x6A\x50\x20\x20") === 0) {
            return 'jp2';
        }
        
        // Если не определили, пробуем загрузить как изображение
        if (@imagecreatefromstring($content) !== false) {
            return 'jpg'; // предполагаем JPEG
        }
        
        return null;
    }
    
    /**
     * Пытается определить номер страницы для изображения
     */
    private function guessPageNumber($object, &$currentPage)
    {
        try {
            $details = $object->getDetails();
            
            // Проверяем наличие информации о странице
            if (isset($details['Page']) && is_numeric($details['Page'])) {
                return (int)$details['Page'];
            }
            
            // Или используем инкремент
            return $currentPage++;
            
        } catch (Exception $e) {
            return $currentPage++;
        }
    }
    
    /**
     * Проверяет страницу на наличие изображений по тексту
     */
    public function checkPageForImages($pdfFilePath, $pageNumber)
    {
        try {
            if (!file_exists($pdfFilePath)) {
                return false;
            }
            
            $parser = new Parser();
            $pdf = $parser->parseFile($pdfFilePath);
            $pages = $pdf->getPages();
            
            if (!isset($pages[$pageNumber - 1])) {
                return false;
            }
            
            $page = $pages[$pageNumber - 1];
            $text = $page->getText();
            
            // Эвристика для определения страниц с изображениями:
            // 1. Если текста мало (< 300 символов)
            // 2. Или есть ключевые слова
            $textLength = strlen(trim($text));
            
            if ($textLength < 300) {
                return true;
            }
            
            // Ключевые слова для страниц с изображениями
            $imageKeywords = [
                'рис.', 'рисунок', 'схема', 'диаграмма', 'график',
                'чертеж', 'изображение', 'иллюстрация', 'фото'
            ];
            
            $lowerText = mb_strtolower($text, 'UTF-8');
            
            foreach ($imageKeywords as $keyword) {
                if (strpos($lowerText, $keyword) !== false) {
                    return true;
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            Log::warning("Ошибка проверки страницы: " . $e->getMessage());
            return false;
        }
    }
}