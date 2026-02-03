<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ScreenshotService
{
    /**
     * Создает оптимизированный скриншот с обрезкой белого фона
     */
    public function createOptimizedScreenshot($sourcePath, $destinationPath, $maxWidth = 800, $maxHeight = 600)
    {
        try {
            $fullSourcePath = Storage::disk('public')->path($sourcePath);
            $fullDestPath = Storage::disk('public')->path($destinationPath);
            
            if (!file_exists($fullSourcePath)) {
                Log::error("❌ Файл не найден: {$sourcePath}");
                return false;
            }
            
            // Создаем директорию если не существует
            $destDir = dirname($fullDestPath);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            
            // Получаем информацию об изображении
            $imageInfo = @getimagesize($fullSourcePath);
            if (!$imageInfo) {
                Log::error("❌ Неверный формат изображения: {$fullSourcePath}");
                return false;
            }
            
            list($srcWidth, $srcHeight, $type) = $imageInfo;
            
            // Загружаем изображение
            $sourceImage = $this->createImageResource($fullSourcePath, $type);
            if (!$sourceImage) {
                Log::error("❌ Не удалось загрузить изображение");
                return false;
            }
            
            // 1. ОБРЕЗКА БЕЛЫХ ПОЛЕЙ
            list($croppedImage, $cropWidth, $cropHeight) = $this->trimWhiteBorders($sourceImage, $srcWidth, $srcHeight);
            
            Log::info("✂️ Обрезка: {$srcWidth}x{$srcHeight} -> {$cropWidth}x{$cropHeight}");
            
            // 2. РЕСАЙЗ ДО МАКСИМАЛЬНЫХ РАЗМЕРОВ
            if ($cropWidth <= $maxWidth && $cropHeight <= $maxHeight) {
                $newWidth = $cropWidth;
                $newHeight = $cropHeight;
            } else {
                $ratio = min($maxWidth / $cropWidth, $maxHeight / $cropHeight);
                $newWidth = floor($cropWidth * $ratio);
                $newHeight = floor($cropHeight * $ratio);
            }
            
            Log::info("📏 Ресайз: {$cropWidth}x{$cropHeight} -> {$newWidth}x{$newHeight}");
            
            // 3. СОЗДАЕМ ФИНАЛЬНОЕ ИЗОБРАЖЕНИЕ
            $finalImage = imagecreatetruecolor($newWidth, $newHeight);
            $white = imagecolorallocate($finalImage, 255, 255, 255);
            imagefill($finalImage, 0, 0, $white);
            
            imagecopyresampled(
                $finalImage, $croppedImage,
                0, 0, 0, 0,
                $newWidth, $newHeight, $cropWidth, $cropHeight
            );
            
            // 4. СОХРАНЯЕМ
            $result = imagejpeg($finalImage, $fullDestPath, 85); // 85% качество
            
            if ($result) {
                $originalSize = filesize($fullSourcePath);
                $finalSize = filesize($fullDestPath);
                $savedPercent = round(($originalSize - $finalSize) / $originalSize * 100, 2);
                
                Log::info("✅ Скриншот создан: {$destinationPath}");
                Log::info("📏 Размеры: {$newWidth}x{$newHeight}");
                Log::info("💰 Сжатие: {$savedPercent}%");
            }
            
            // Очистка памяти
            imagedestroy($sourceImage);
            imagedestroy($croppedImage);
            imagedestroy($finalImage);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error("❌ Ошибка создания скриншота: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Обрезка белых полей
     */
    private function trimWhiteBorders($sourceImage, $width, $height)
    {
        try {
            $threshold = 240; // Порог белого цвета (0-255)
            $top = $height;
            $bottom = 0;
            $left = $width;
            $right = 0;
            
            // Сканируем с шагом 5px для скорости
            $step = 5;
            
            // Ищем верхнюю границу
            for ($y = 0; $y < $height; $y += $step) {
                $hasContent = false;
                for ($x = 0; $x < $width; $x += $step) {
                    $color = imagecolorat($sourceImage, $x, $y);
                    $rgb = imagecolorsforindex($sourceImage, $color);
                    
                    // Если пиксель НЕ белый
                    if ($rgb['red'] < $threshold || $rgb['green'] < $threshold || $rgb['blue'] < $threshold) {
                        $hasContent = true;
                        break;
                    }
                }
                if ($hasContent) {
                    $top = max(0, $y - 10); // Добавляем отступ
                    break;
                }
            }
            
            // Ищем нижнюю границу
            for ($y = $height - 1; $y >= 0; $y -= $step) {
                $hasContent = false;
                for ($x = 0; $x < $width; $x += $step) {
                    $color = imagecolorat($sourceImage, $x, $y);
                    $rgb = imagecolorsforindex($sourceImage, $color);
                    
                    if ($rgb['red'] < $threshold || $rgb['green'] < $threshold || $rgb['blue'] < $threshold) {
                        $hasContent = true;
                        break;
                    }
                }
                if ($hasContent) {
                    $bottom = min($height - 1, $y + 10);
                    break;
                }
            }
            
            // Ищем левую границу
            for ($x = 0; $x < $width; $x += $step) {
                $hasContent = false;
                for ($y = 0; $y < $height; $y += $step) {
                    $color = imagecolorat($sourceImage, $x, $y);
                    $rgb = imagecolorsforindex($sourceImage, $color);
                    
                    if ($rgb['red'] < $threshold || $rgb['green'] < $threshold || $rgb['blue'] < $threshold) {
                        $hasContent = true;
                        break;
                    }
                }
                if ($hasContent) {
                    $left = max(0, $x - 10);
                    break;
                }
            }
            
            // Ищем правую границу
            for ($x = $width - 1; $x >= 0; $x -= $step) {
                $hasContent = false;
                for ($y = 0; $y < $height; $y += $step) {
                    $color = imagecolorat($sourceImage, $x, $y);
                    $rgb = imagecolorsforindex($sourceImage, $color);
                    
                    if ($rgb['red'] < $threshold || $rgb['green'] < $threshold || $rgb['blue'] < $threshold) {
                        $hasContent = true;
                        break;
                    }
                }
                if ($hasContent) {
                    $right = min($width - 1, $x + 10);
                    break;
                }
            }
            
            // Вычисляем новые размеры
            $cropWidth = $right - $left + 1;
            $cropHeight = $bottom - $top + 1;
            
            // Если обрезка минимальна, возвращаем оригинал
            if ($cropWidth > $width * 0.95 && $cropHeight > $height * 0.95) {
                return [$sourceImage, $width, $height];
            }
            
            // Создаем обрезанное изображение
            $croppedImage = imagecreatetruecolor($cropWidth, $cropHeight);
            $white = imagecolorallocate($croppedImage, 255, 255, 255);
            imagefill($croppedImage, 0, 0, $white);
            
            imagecopy($croppedImage, $sourceImage, 0, 0, $left, $top, $cropWidth, $cropHeight);
            
            imagedestroy($sourceImage);
            
            return [$croppedImage, $cropWidth, $cropHeight];
            
        } catch (\Exception $e) {
            Log::error("❌ Ошибка обрезки: " . $e->getMessage());
            return [$sourceImage, $width, $height];
        }
    }
    
    /**
     * Создает ресурс изображения
     */
    private function createImageResource($path, $type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                $img = imagecreatefrompng($path);
                if ($img) {
                    imagealphablending($img, false);
                    imagesavealpha($img, true);
                }
                return $img;
            case IMAGETYPE_GIF:
                return imagecreatefromgif($path);
            default:
                return @imagecreatefromstring(file_get_contents($path));
        }
    }
    
    /**
     * Создает простой скриншот без обрезки
     */
    public function createSimpleScreenshot($sourcePath, $destinationPath, $maxWidth = 800, $maxHeight = 600)
    {
        try {
            $fullSourcePath = Storage::disk('public')->path($sourcePath);
            $fullDestPath = Storage::disk('public')->path($destinationPath);
            
            if (!file_exists($fullSourcePath)) {
                return false;
            }
            
            $imageInfo = @getimagesize($fullSourcePath);
            if (!$imageInfo) {
                return false;
            }
            
            list($width, $height, $type) = $imageInfo;
            
            $sourceImage = $this->createImageResource($fullSourcePath, $type);
            if (!$sourceImage) {
                return false;
            }
            
            // Рассчитываем новые размеры
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);
            
            // Создаем новое изображение
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Для PNG сохраняем прозрачность
            if ($type == IMAGETYPE_PNG) {
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
                imagefill($resizedImage, 0, 0, $transparent);
            } else {
                // Для других форматов белый фон
                $white = imagecolorallocate($resizedImage, 255, 255, 255);
                imagefill($resizedImage, 0, 0, $white);
            }
            
            // Ресайзим
            imagecopyresampled(
                $resizedImage, $sourceImage,
                0, 0, 0, 0,
                $newWidth, $newHeight, $width, $height
            );
            
            // Сохраняем
            $result = imagejpeg($resizedImage, $fullDestPath, 85);
            
            imagedestroy($sourceImage);
            imagedestroy($resizedImage);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error("Ошибка создания простого скриншота: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Проверяет, нужно ли создавать скриншот (если файл слишком большой)
     */
    public function needsScreenshot($imagePath, $maxSize = 102400) // 100KB
    {
        try {
            if (!Storage::disk('public')->exists($imagePath)) {
                return false;
            }
            
            $size = Storage::disk('public')->size($imagePath);
            return $size > $maxSize;
            
        } catch (\Exception $e) {
            Log::warning("Ошибка проверки размера: " . $e->getMessage());
            return true;
        }
    }
}