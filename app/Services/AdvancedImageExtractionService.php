<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class AdvancedImageExtractionService
{
    protected $imageManager;
    
    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }
    
    /**
     * Основной метод для поиска и извлечения картинок из PDF
     */
    public function findAndExtractImages($pdfFilePath, $pageNumber, $documentId)
    {
        try {
            Log::info("🔍 Поиск картинок в странице {$pageNumber}");
            
            if (!file_exists($pdfFilePath)) {
                throw new Exception("PDF файл не найден: {$pdfFilePath}");
            }
            
            // 1. Сначала пробуем извлечь встроенные изображения из PDF
            $embeddedImages = $this->extractEmbeddedImages($pdfFilePath, $pageNumber, $documentId);
            
            if (!empty($embeddedImages)) {
                Log::info("✅ Найдено встроенных изображений: " . count($embeddedImages));
                return $embeddedImages;
            }
            
            // 2. Если встроенных нет, создаем скриншот всей страницы и анализируем
            Log::info("📄 Встроенных изображений не найдено, создаем скриншот страницы...");
            $screenshotPath = $this->createPageScreenshot($pdfFilePath, $pageNumber, $documentId);
            
            if (!$screenshotPath || !file_exists($screenshotPath)) {
                throw new Exception("Не удалось создать скриншот страницы");
            }
            
            // 3. Анализируем скриншот на наличие графических элементов
            $detectedImages = $this->analyzeScreenshotForImages($screenshotPath, $documentId, $pageNumber);
            
            // 4. Удаляем временный файл
            if (file_exists($screenshotPath)) {
                unlink($screenshotPath);
            }
            
            return $detectedImages;
            
        } catch (Exception $e) {
            Log::error("❌ Ошибка поиска картинок: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Извлекает встроенные изображения из PDF
     */
    private function extractEmbeddedImages($pdfFilePath, $pageNumber, $documentId)
    {
        try {
            $images = [];
            
            // Используем Smalot PDF Parser для извлечения изображений
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($pdfFilePath);
            
            // Получаем объекты со страницы
            $pages = $pdf->getPages();
            if (!isset($pages[$pageNumber - 1])) {
                return [];
            }
            
            $page = $pages[$pageNumber - 1];
            $details = $page->getDetails();
            
            // Ищем XObjects (обычно там хранятся изображения)
            if (isset($details['XObject'])) {
                $xObjects = $details['XObject'];
                
                foreach ($xObjects as $name => $xObject) {
                    if (isset($xObject['Subtype']) && $xObject['Subtype'] === 'Image') {
                        $imageData = $this->extractImageFromXObject($xObject, $documentId, $pageNumber, $name);
                        if ($imageData) {
                            $images[] = $imageData;
                        }
                    }
                }
            }
            
            return $images;
            
        } catch (Exception $e) {
            Log::debug("Не удалось извлечь встроенные изображения: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Извлекает изображение из XObject
     */
    private function extractImageFromXObject($xObject, $documentId, $pageNumber, $name)
    {
        try {
            if (!isset($xObject['Data']) && !isset($xObject['Filter'])) {
                return null;
            }
            
            // Создаем директорию
            $imagesDir = "document_images/{$documentId}";
            Storage::disk('public')->makeDirectory($imagesDir, 0755, true);
            
            // Генерируем имя файла
            $filename = "page{$pageNumber}_{$name}.jpg";
            $imagePath = $imagesDir . '/' . $filename;
            
            // Сохраняем данные изображения
            if (isset($xObject['Data'])) {
                $imageData = $xObject['Data'];
            } else {
                // Пробуем получить данные другими способами
                $imageData = $this->getImageDataFromXObject($xObject);
            }
            
            if (!$imageData) {
                return null;
            }
            
            // Сохраняем файл
            Storage::disk('public')->put($imagePath, $imageData);
            
            // Получаем информацию об изображении
            $fullPath = Storage::disk('public')->path($imagePath);
            $imageInfo = @getimagesize($fullPath);
            
            if (!$imageInfo) {
                // Если не JPEG, конвертируем
                $convertedPath = $this->convertToJpeg($fullPath);
                if ($convertedPath) {
                    $imagePath = $convertedPath;
                    $imageInfo = @getimagesize($convertedPath);
                }
            }
            
            if ($imageInfo) {
                list($width, $height) = $imageInfo;
                $size = filesize($fullPath);
                
                return [
                    'path' => $imagePath,
                    'url' => Storage::url($imagePath),
                    'width' => $width,
                    'height' => $height,
                    'size' => $size,
                    'filename' => $filename,
                    'description' => "Встроенное изображение на странице {$pageNumber}"
                ];
            }
            
            return null;
            
        } catch (Exception $e) {
            Log::debug("Ошибка извлечения XObject: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Создает скриншот страницы PDF
     */
    private function createPageScreenshot($pdfFilePath, $pageNumber, $documentId)
    {
        try {
            $tempDir = storage_path('app/temp_pdf_screenshots');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            $outputPath = $tempDir . "/page_{$documentId}_{$pageNumber}_full.jpg";
            
            if (extension_loaded('imagick')) {
                $imagick = new \Imagick();
                $imagick->setResolution(150, 150);
                $imagick->readImage($pdfFilePath . '[' . ($pageNumber - 1) . ']');
                $imagick->setImageFormat('jpg');
                $imagick->setImageCompression(\Imagick::COMPRESSION_JPEG);
                $imagick->setImageCompressionQuality(95);
                
                // Автоматический поворот
                $orientation = $imagick->getImageOrientation();
                switch($orientation) {
                    case \Imagick::ORIENTATION_BOTTOMRIGHT:
                        $imagick->rotateimage("#000", 180);
                        break;
                    case \Imagick::ORIENTATION_RIGHTTOP:
                        $imagick->rotateimage("#000", 90);
                        break;
                    case \Imagick::ORIENTATION_LEFTBOTTOM:
                        $imagick->rotateimage("#000", -90);
                        break;
                }
                
                $imagick->writeImage($outputPath);
                $imagick->clear();
                $imagick->destroy();
                
                Log::debug("✅ Скриншот создан: {$outputPath}");
                return $outputPath;
            }
            
            return null;
            
        } catch (Exception $e) {
            Log::error("Ошибка создания скриншота: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Анализирует скриншот для поиска графических элементов
     */
    private function analyzeScreenshotForImages($screenshotPath, $documentId, $pageNumber)
    {
        try {
            if (!file_exists($screenshotPath)) {
                return [];
            }
            
            // Загружаем изображение
            $image = $this->imageManager->read($screenshotPath);
            $width = $image->width();
            $height = $image->height();
            
            Log::debug("📏 Анализ скриншота: {$width}x{$height}");
            
            // Ищем области с изображениями
            $imageAreas = $this->detectImageAreas($image);
            
            if (empty($imageAreas)) {
                Log::debug("🖼️ Изображений не обнаружено");
                return [];
            }
            
            Log::debug("🎯 Найдено областей: " . count($imageAreas));
            
            // Сохраняем найденные области
            $savedImages = [];
            foreach ($imageAreas as $index => $area) {
                $savedImage = $this->saveDetectedImage($image, $area, $documentId, $pageNumber, $index + 1);
                if ($savedImage) {
                    $savedImages[] = $savedImage;
                }
            }
            
            return $savedImages;
            
        } catch (Exception $e) {
            Log::error("Ошибка анализа скриншота: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Детектирует области с изображениями
     */
    private function detectImageAreas($image)
    {
        $width = $image->width();
        $height = $image->height();
        
        // Стратегия 1: Ищем большие области с низкой энтропией текста
        $areas = $this->findLargeUniformAreas($image);
        
        // Стратегия 2: Ищем контрастные области (диаграммы, графики)
        if (empty($areas)) {
            $areas = $this->findHighContrastAreas($image);
        }
        
        // Стратегия 3: Ищем прямоугольные области с границами
        if (empty($areas)) {
            $areas = $this->findBorderedAreas($image);
        }
        
        // Фильтруем слишком маленькие области (вероятно, текст)
        $filteredAreas = array_filter($areas, function($area) {
            return $area['width'] > 100 && $area['height'] > 100;
        });
        
        return array_values($filteredAreas);
    }
    
    /**
     * Ищет большие однородные области
     */
    private function findLargeUniformAreas($image)
    {
        $width = $image->width();
        $height = $image->height();
        
        // Разбиваем на сетку 12x12
        $gridCols = 12;
        $gridRows = 12;
        $cellWidth = floor($width / $gridCols);
        $cellHeight = floor($height / $gridRows);
        
        $uniformCells = [];
        
        for ($row = 0; $row < $gridRows; $row++) {
            for ($col = 0; $col < $gridCols; $col++) {
                $cellX = $col * $cellWidth;
                $cellY = $row * $cellHeight;
                
                // Оцениваем однородность ячейки
                $uniformity = $this->calculateCellUniformity($image, $cellX, $cellY, $cellWidth, $cellHeight);
                
                if ($uniformity > 0.6) { // Высокая однородность
                    $uniformCells[] = [
                        'row' => $row,
                        'col' => $col,
                        'score' => $uniformity,
                        'x' => $cellX,
                        'y' => $cellY,
                        'width' => $cellWidth,
                        'height' => $cellHeight
                    ];
                }
            }
        }
        
        // Группируем соседние однородные ячейки
        return $this->groupAdjacentCells($uniformCells);
    }
    
    /**
     * Ищет области с высоким контрастом
     */
    private function findHighContrastAreas($image)
    {
        $width = $image->width();
        $height = $image->height();
        
        // Разбиваем на сетку 10x10
        $gridCols = 10;
        $gridRows = 10;
        $cellWidth = floor($width / $gridCols);
        $cellHeight = floor($height / $gridRows);
        
        $contrastCells = [];
        
        for ($row = 0; $row < $gridRows; $row++) {
            for ($col = 0; $col < $gridCols; $col++) {
                $cellX = $col * $cellWidth;
                $cellY = $row * $cellHeight;
                
                // Оцениваем контрастность ячейки
                $contrast = $this->calculateCellContrast($image, $cellX, $cellY, $cellWidth, $cellHeight);
                
                if ($contrast > 0.4) { // Высокий контраст
                    $contrastCells[] = [
                        'row' => $row,
                        'col' => $col,
                        'score' => $contrast,
                        'x' => $cellX,
                        'y' => $cellY,
                        'width' => $cellWidth,
                        'height' => $cellHeight
                    ];
                }
            }
        }
        
        // Группируем соседние контрастные ячейки
        return $this->groupAdjacentCells($contrastCells);
    }
    
    /**
     * Ищет области с границами (диаграммы, таблицы)
     */
    private function findBorderedAreas($image)
    {
        $width = $image->width();
        $height = $image->height();
        
        // Сканируем изображение для поиска вертикальных и горизонтальных линий
        $borderThreshold = 100; // Порог для темных пикселей
        $step = 5; // Шаг сканирования
        
        $verticalLines = [];
        $horizontalLines = [];
        
        // Поиск вертикальных линий
        for ($x = 0; $x < $width; $x += $step) {
            $lineLength = 0;
            $lineStart = 0;
            
            for ($y = 0; $y < $height; $y++) {
                $color = $image->pickColor($x, $y);
                $rgb = $this->colorToArray($color);
                $brightness = ($rgb['r'] + $rgb['g'] + $rgb['b']) / 3;
                
                if ($brightness < $borderThreshold) {
                    if ($lineLength === 0) {
                        $lineStart = $y;
                    }
                    $lineLength++;
                } else {
                    if ($lineLength > 20) { // Минимальная длина линии
                        $verticalLines[] = [
                            'x' => $x,
                            'y' => $lineStart,
                            'length' => $lineLength
                        ];
                    }
                    $lineLength = 0;
                }
            }
        }
        
        // Поиск горизонтальных линий
        for ($y = 0; $y < $height; $y += $step) {
            $lineLength = 0;
            $lineStart = 0;
            
            for ($x = 0; $x < $width; $x++) {
                $color = $image->pickColor($x, $y);
                $rgb = $this->colorToArray($color);
                $brightness = ($rgb['r'] + $rgb['g'] + $rgb['b']) / 3;
                
                if ($brightness < $borderThreshold) {
                    if ($lineLength === 0) {
                        $lineStart = $x;
                    }
                    $lineLength++;
                } else {
                    if ($lineLength > 20) {
                        $horizontalLines[] = [
                            'x' => $lineStart,
                            'y' => $y,
                            'length' => $lineLength
                        ];
                    }
                    $lineLength = 0;
                }
            }
        }
        
        // Находим прямоугольники из пересекающихся линий
        $rectangles = $this->findRectanglesFromLines($verticalLines, $horizontalLines, $width, $height);
        
        return $rectangles;
    }
    
    /**
     * Вычисляет однородность ячейки
     */
    private function calculateCellUniformity($image, $x, $y, $width, $height)
    {
        $samplePoints = 20;
        $stepX = max(1, floor($width / $samplePoints));
        $stepY = max(1, floor($height / $samplePoints));
        
        $colors = [];
        
        for ($py = $y; $py < min($y + $height, $image->height()); $py += $stepY) {
            for ($px = $x; $px < min($x + $width, $image->width()); $px += $stepX) {
                $color = $image->pickColor($px, $py);
                $colors[] = $this->colorToArray($color);
            }
        }
        
        if (count($colors) < 4) {
            return 0;
        }
        
        // Вычисляем средний цвет
        $avgR = array_sum(array_column($colors, 'r')) / count($colors);
        $avgG = array_sum(array_column($colors, 'g')) / count($colors);
        $avgB = array_sum(array_column($colors, 'b')) / count($colors);
        
        // Вычисляем дисперсию
        $variance = 0;
        foreach ($colors as $color) {
            $diffR = $color['r'] - $avgR;
            $diffG = $color['g'] - $avgG;
            $diffB = $color['b'] - $avgB;
            $variance += ($diffR * $diffR + $diffG * $diffG + $diffB * $diffB);
        }
        
        $variance /= count($colors);
        
        // Нормализуем (меньше дисперсия = больше однородность)
        $uniformity = max(0, 1 - ($variance / 10000));
        
        return $uniformity;
    }
    
    /**
     * Вычисляет контрастность ячейки
     */
    private function calculateCellContrast($image, $x, $y, $width, $height)
    {
        $samplePoints = 25;
        $stepX = max(1, floor($width / $samplePoints));
        $stepY = max(1, floor($height / $samplePoints));
        
        $brightnessValues = [];
        
        for ($py = $y; $py < min($y + $height, $image->height()); $py += $stepY) {
            for ($px = $x; $px < min($x + $width, $image->width()); $px += $stepX) {
                $color = $image->pickColor($px, $py);
                $rgb = $this->colorToArray($color);
                $brightness = 0.299 * $rgb['r'] + 0.587 * $rgb['g'] + 0.114 * $rgb['b'];
                $brightnessValues[] = $brightness;
            }
        }
        
        if (count($brightnessValues) < 4) {
            return 0;
        }
        
        // Вычисляем стандартное отклонение (мера контраста)
        $mean = array_sum($brightnessValues) / count($brightnessValues);
        $variance = 0;
        
        foreach ($brightnessValues as $value) {
            $variance += pow($value - $mean, 2);
        }
        
        $stdDev = sqrt($variance / count($brightnessValues));
        
        // Нормализуем
        $contrast = min(1.0, $stdDev / 128);
        
        return $contrast;
    }
    
    /**
     * Группирует соседние ячейки
     */
    private function groupAdjacentCells($cells)
    {
        if (empty($cells)) {
            return [];
        }
        
        $groups = [];
        $visited = [];
        
        foreach ($cells as $index => $cell) {
            if (isset($visited[$index])) {
                continue;
            }
            
            $group = [$cell];
            $visited[$index] = true;
            
            // Ищем соседей
            $this->findCellNeighbors($cells, $cell, $group, $visited, $index);
            
            if (count($group) >= 2) { // Группа должна содержать хотя бы 2 ячейки
                $groups[] = $this->mergeCellsIntoArea($group);
            }
        }
        
        return $groups;
    }
    
    /**
     * Ищет соседние ячейки
     */
    private function findCellNeighbors($cells, $currentCell, &$group, &$visited, $currentIndex)
    {
        foreach ($cells as $neighborIndex => $neighbor) {
            if (isset($visited[$neighborIndex])) {
                continue;
            }
            
            // Проверяем, являются ли ячейки соседями (разница в координатах ≤ 1)
            $rowDiff = abs($currentCell['row'] - $neighbor['row']);
            $colDiff = abs($currentCell['col'] - $neighbor['col']);
            
            if ($rowDiff <= 1 && $colDiff <= 1) {
                $group[] = $neighbor;
                $visited[$neighborIndex] = true;
                
                // Рекурсивно ищем соседей соседа
                $this->findCellNeighbors($cells, $neighbor, $group, $visited, $neighborIndex);
            }
        }
    }
    
    /**
     * Объединяет ячейки в одну область
     */
    private function mergeCellsIntoArea($cells)
    {
        $minX = min(array_column($cells, 'x'));
        $minY = min(array_column($cells, 'y'));
        $maxX = max(array_column($cells, 'x')) + $cells[0]['width'];
        $maxY = max(array_column($cells, 'y')) + $cells[0]['height'];
        
        return [
            'x' => $minX,
            'y' => $minY,
            'width' => $maxX - $minX,
            'height' => $maxY - $minY
        ];
    }
    
    /**
     * Сохраняет обнаруженное изображение
     */
    private function saveDetectedImage($originalImage, $area, $documentId, $pageNumber, $index)
    {
        try {
            // Создаем директорию для скриншотов
            $screenshotsDir = "document_images/screenshots/{$documentId}";
            Storage::disk('public')->makeDirectory($screenshotsDir, 0755, true);
            
            // Имя файла
            $filename = "page_{$pageNumber}_detected_{$index}.jpg";
            $outputPath = Storage::disk('public')->path($screenshotsDir . '/' . $filename);
            
            // Обрезаем область
            $croppedImage = $originalImage->crop(
                $area['width'],
                $area['height'],
                $area['x'],
                $area['y']
            );
            
            // Улучшаем качество
            $this->enhanceImage($croppedImage);
            
            // Ресайз для оптимального размера
            if ($croppedImage->width() > 1200 || $croppedImage->height() > 800) {
                $croppedImage->scale(1200, 800);
            }
            
            // Сохраняем
            $croppedImage->toJpeg(90)->save($outputPath);
            
            // Получаем информацию о файле
            $fileSize = filesize($outputPath);
            $imageInfo = getimagesize($outputPath);
            list($width, $height) = $imageInfo;
            
            return [
                'path' => $screenshotsDir . '/' . $filename,
                'url' => Storage::url($screenshotsDir . '/' . $filename),
                'width' => $width,
                'height' => $height,
                'size' => $fileSize,
                'filename' => $filename,
                'description' => "Обнаруженное изображение {$index} на странице {$pageNumber}",
                'detected_area' => $area
            ];
            
        } catch (Exception $e) {
            Log::error("Ошибка сохранения обнаруженного изображения: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Улучшает качество изображения
     */
    private function enhanceImage($image)
    {
        try {
            // Увеличиваем контраст
            $image->contrast(10);
            
            // Увеличиваем резкость
            $image->sharpen(15);
            
            // Автоматическая коррекция гаммы
            $image->gamma(1.1);
            
        } catch (Exception $e) {
            Log::debug("Ошибка улучшения изображения: " . $e->getMessage());
        }
    }
    
    /**
     * Преобразует цвет в массив RGB
     */
    private function colorToArray($color)
    {
        if (is_object($color) && method_exists($color, 'toArray')) {
            $array = $color->toArray();
            return [
                'r' => $array['r'] ?? $array['red'] ?? 0,
                'g' => $array['g'] ?? $array['green'] ?? 0,
                'b' => $array['b'] ?? $array['blue'] ?? 0,
            ];
        }
        
        if (is_array($color)) {
            return [
                'r' => $color['r'] ?? $color['red'] ?? $color[0] ?? 0,
                'g' => $color['g'] ?? $color['green'] ?? $color[1] ?? 0,
                'b' => $color['b'] ?? $color['blue'] ?? $color[2] ?? 0,
            ];
        }
        
        return ['r' => 0, 'g' => 0, 'b' => 0];
    }
}