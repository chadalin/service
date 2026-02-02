<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Exception;

class ImageProcessingService
{
    protected $imageManager;
    
    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }
    
    /**
     * Создание превью изображения
     */
    public function createThumbnail($sourcePath, $destinationPath, $width = 300, $height = 200)
    {
        try {
            $fullSourcePath = Storage::disk('public')->path($sourcePath);
            
            if (!file_exists($fullSourcePath)) {
                Log::error("Source image not found: {$fullSourcePath}");
                return false;
            }
            
            // Создаем директорию для превью если нет
            $thumbDir = dirname($destinationPath);
            Storage::disk('public')->makeDirectory($thumbDir);
            
            $fullDestPath = Storage::disk('public')->path($destinationPath);
            
            // Создаем превью с обрезкой по центру
            $image = $this->imageManager->read($fullSourcePath);
            
            // Определяем ориентацию
            $sourceWidth = $image->width();
            $sourceHeight = $image->height();
            
            if ($sourceWidth > $sourceHeight) {
                // Горизонтальное изображение
                $image->cover($width, $height, 'center');
            } else {
                // Вертикальное изображение
                $image->cover($width, $height, 'center');
            }
            
            // Сохраняем с хорошим качеством
            $image->save($fullDestPath, quality: 85);
            
            Log::info("Created thumbnail: {$destinationPath}");
            return true;
            
        } catch (Exception $e) {
            Log::error("Thumbnail creation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Создание скриншота для изображения (подогнанного под экран)
     */
    public function createScreenshot($sourcePath, $destinationPath, $maxWidth = 800, $maxHeight = 600)
    {
        try {
            $fullSourcePath = Storage::disk('public')->path($sourcePath);
            
            if (!file_exists($fullSourcePath)) {
                Log::error("Source image not found: {$fullSourcePath}");
                return false;
            }
            
            // Создаем директорию для скриншотов если нет
            $screenshotDir = dirname($destinationPath);
            Storage::disk('public')->makeDirectory($screenshotDir);
            
            $fullDestPath = Storage::disk('public')->path($destinationPath);
            
            // Читаем изображение
            $image = $this->imageManager->read($fullSourcePath);
            
            // Получаем размеры оригинала
            $originalWidth = $image->width();
            $originalHeight = $image->height();
            
            // Рассчитываем новые размеры с сохранением пропорций
            $ratio = $originalWidth / $originalHeight;
            
            if ($originalWidth > $originalHeight) {
                // Горизонтальное изображение
                $newWidth = min($maxWidth, $originalWidth);
                $newHeight = (int)($newWidth / $ratio);
            } else {
                // Вертикальное изображение
                $newHeight = min($maxHeight, $originalHeight);
                $newWidth = (int)($newHeight * $ratio);
            }
            
            // Убеждаемся что не превышаем лимиты
            if ($newWidth > $maxWidth) {
                $newWidth = $maxWidth;
                $newHeight = (int)($newWidth / $ratio);
            }
            
            if ($newHeight > $maxHeight) {
                $newHeight = $maxHeight;
                $newWidth = (int)($newHeight * $ratio);
            }
            
            // Ресайзим изображение
            $image->resize($newWidth, $newHeight);
            
            // Добавляем белую рамку если нужно
            if ($newWidth < $maxWidth || $newHeight < $maxHeight) {
                $image->resizeCanvas($maxWidth, $maxHeight, 'center', true, '#ffffff');
            }
            
            // Сохраняем с хорошим качеством
            $image->save($fullDestPath, quality: 90);
            
            Log::info("Created screenshot: {$destinationPath}");
            return true;
            
        } catch (Exception $e) {
            Log::error("Screenshot creation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Обнаружение текста на изображении с помощью OCR (опционально)
     */
    public function detectTextInImage($imagePath)
    {
        try {
            $fullPath = Storage::disk('public')->path($imagePath);
            
            if (!file_exists($fullPath)) {
                return null;
            }
            
            // Здесь можно интегрировать OCR библиотеку типа Tesseract
            // Пока возвращаем null
            return null;
            
        } catch (Exception $e) {
            Log::error("OCR failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Анализ изображения и извлечение информации
     */
    public function analyzeImage($imagePath)
    {
        try {
            $fullPath = Storage::disk('public')->path($imagePath);
            
            if (!file_exists($fullPath)) {
                return [
                    'success' => false,
                    'error' => 'Image not found'
                ];
            }
            
            $image = $this->imageManager->read($fullPath);
            
            return [
                'success' => true,
                'width' => $image->width(),
                'height' => $image->height(),
                'format' => pathinfo($imagePath, PATHINFO_EXTENSION),
                'size' => Storage::disk('public')->size($imagePath),
                'aspect_ratio' => $image->width() / $image->height(),
                'is_vertical' => $image->height() > $image->width(),
                'is_horizontal' => $image->width() > $image->height(),
                'is_square' => $image->width() == $image->height()
            ];
            
        } catch (Exception $e) {
            Log::error("Image analysis failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Конвертация изображения в другой формат
     */
    public function convertImage($sourcePath, $destinationPath, $format = 'jpg', $quality = 85)
    {
        try {
            $fullSourcePath = Storage::disk('public')->path($sourcePath);
            
            if (!file_exists($fullSourcePath)) {
                return false;
            }
            
            // Создаем директорию если нет
            $destDir = dirname($destinationPath);
            Storage::disk('public')->makeDirectory($destDir);
            
            $fullDestPath = Storage::disk('public')->path($destinationPath);
            
            $image = $this->imageManager->read($fullSourcePath);
            
            // Конвертируем в нужный формат
            switch (strtolower($format)) {
                case 'jpg':
                case 'jpeg':
                    $image->toJpeg($quality);
                    break;
                case 'png':
                    $image->toPng();
                    break;
                case 'webp':
                    $image->toWebp($quality);
                    break;
                default:
                    return false;
            }
            
            $image->save($fullDestPath);
            return true;
            
        } catch (Exception $e) {
            Log::error("Image conversion failed: " . $e->getMessage());
            return false;
        }
    }
}