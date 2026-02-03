<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageProcessorService
{
    protected $imageManager;
    
    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }
    
    /**
     * Создает обрезанный скриншот из изображения
     */
    public function createCroppedScreenshot($sourceContent, $destinationPath, $maxWidth = 800, $maxHeight = 600)
    {
        try {
            if (empty($sourceContent)) {
                return false;
            }
            
            // Создаем директорию если не существует
            $dir = dirname($destinationPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Создаем изображение из бинарных данных
            $image = $this->imageManager->read($sourceContent);
            
            // Автоматическая обрезка белых полей
            $image = $this->trimWhiteBorders($image);
            
            // Ресайз если нужно
            if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
                $image->scale($maxWidth, $maxHeight);
            }
            
            // Конвертируем в JPEG если нужно
            if (!in_array($image->extension(), ['jpg', 'jpeg'])) {
                $image->toJpeg(85);
            }
            
            // Сохраняем
            $image->save($destinationPath, 85);
            
            Log::debug("✅ Создан скриншот: {$destinationPath}, размер: {$image->width()}x{$image->height()}");
            
            return [
                'success' => true,
                'path' => $destinationPath,
                'width' => $image->width(),
                'height' => $image->height(),
                'size' => filesize($destinationPath)
            ];
            
        } catch (Exception $e) {
            Log::error("❌ Ошибка создания скриншота: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Обрезает белые поля вокруг изображения
     */
    private function trimWhiteBorders($image)
    {
        try {
            $width = $image->width();
            $height = $image->height();
            
            $top = $height;
            $bottom = 0;
            $left = $width;
            $right = 0;
            
            $threshold = 240; // Порог белого цвета
            
            // Сканируем края с шагом для скорости
            $step = 5;
            
            // Сканируем сверху вниз
            for ($y = 0; $y < $height; $y += $step) {
                for ($x = 0; $x < $width; $x += $step) {
                    $color = $image->pickColor($x, $y);
                    
                    // Если цвет не белый
                    if ($color[0] < $threshold || $color[1] < $threshold || $color[2] < $threshold) {
                        if ($y < $top) $top = $y;
                        break 2;
                    }
                }
            }
            
            // Сканируем снизу вверх
            for ($y = $height - 1; $y >= 0; $y -= $step) {
                for ($x = 0; $x < $width; $x += $step) {
                    $color = $image->pickColor($x, $y);
                    
                    if ($color[0] < $threshold || $color[1] < $threshold || $color[2] < $threshold) {
                        if ($y > $bottom) $bottom = $y;
                        break 2;
                    }
                }
            }
            
            // Сканируем слева направо
            for ($x = 0; $x < $width; $x += $step) {
                for ($y = 0; $y < $height; $y += $step) {
                    $color = $image->pickColor($x, $y);
                    
                    if ($color[0] < $threshold || $color[1] < $threshold || $color[2] < $threshold) {
                        if ($x < $left) $left = $x;
                        break 2;
                    }
                }
            }
            
            // Сканируем справа налево
            for ($x = $width - 1; $x >= 0; $x -= $step) {
                for ($y = 0; $y < $height; $y += $step) {
                    $color = $image->pickColor($x, $y);
                    
                    if ($color[0] < $threshold || $color[1] < $threshold || $color[2] < $threshold) {
                        if ($x > $right) $right = $x;
                        break 2;
                    }
                }
            }
            
            // Добавляем отступы
            $padding = 10;
            $top = max(0, $top - $padding);
            $bottom = min($height - 1, $bottom + $padding);
            $left = max(0, $left - $padding);
            $right = min($width - 1, $right + $padding);
            
            $cropWidth = $right - $left;
            $cropHeight = $bottom - $top;
            
            // Если область для обрезки слишком мала, не обрезаем
            if ($cropWidth < 50 || $cropHeight < 50) {
                return $image;
            }
            
            // Обрезаем
            return $image->crop($cropWidth, $cropHeight, $left, $top);
            
        } catch (Exception $e) {
            Log::warning("Ошибка обрезки белых полей: " . $e->getMessage());
            return $image;
        }
    }
    
    /**
     * Сохраняет изображение в публичную папку
     */
    public function saveImageToStorage($content, $documentId, $pageNumber, $index)
    {
        try {
            $imagesDir = "document_images/{$documentId}";
            Storage::disk('public')->makeDirectory($imagesDir, 0755, true);
            
            $filename = "img_page{$pageNumber}_{$index}.jpg";
            $path = $imagesDir . '/' . $filename;
            
            Storage::disk('public')->put($path, $content);
            
            return [
                'success' => true,
                'path' => $path,
                'url' => Storage::url($path),
                'filename' => $filename
            ];
            
        } catch (Exception $e) {
            Log::error("Ошибка сохранения изображения: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}