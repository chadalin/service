<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class SimpleImageProcessor
{
    /**
     * Создает скриншот изображения
     */
    public function createScreenshot($sourcePath, $destinationPath, $maxWidth = 800, $maxHeight = 600)
    {
        try {
            $fullSourcePath = Storage::disk('public')->path($sourcePath);
            
            if (!file_exists($fullSourcePath)) {
                Log::error("Source image not found: {$fullSourcePath}");
                return false;
            }
            
            // Получаем информацию об изображении
            $imageInfo = @getimagesize($fullSourcePath);
            if (!$imageInfo) {
                Log::error("Invalid image file or unsupported format: {$fullSourcePath}");
                return false;
            }
            
            list($srcWidth, $srcHeight, $type) = $imageInfo;
            
            // Создаем изображение из файла
            $sourceImage = $this->createImageFromFile($fullSourcePath, $type);
            if (!$sourceImage) {
                return false;
            }
            
            // Рассчитываем новые размеры с сохранением пропорций
            list($newWidth, $newHeight) = $this->calculateResizeDimensions(
                $srcWidth, $srcHeight, $maxWidth, $maxHeight
            );
            
            // Создаем новое изображение для скриншота
            $screenshot = imagecreatetruecolor($newWidth, $newHeight);
            
            // Заполняем фон белым
            $white = imagecolorallocate($screenshot, 255, 255, 255);
            imagefill($screenshot, 0, 0, $white);
            
            // Копируем и изменяем размер
            imagecopyresampled(
                $screenshot, $sourceImage,
                0, 0, 0, 0,
                $newWidth, $newHeight, $srcWidth, $srcHeight
            );
            
            // Сохраняем результат
            $fullDestPath = Storage::disk('public')->path($destinationPath);
            $this->createDirectory($fullDestPath);
            
            $saved = $this->saveImage($screenshot, $fullDestPath, $type);
            
            // Освобождаем память
            imagedestroy($sourceImage);
            imagedestroy($screenshot);
            
            if ($saved) {
                Log::info("Screenshot created: {$destinationPath}");
                return $destinationPath;
            }
            
            return false;
            
        } catch (Exception $e) {
            Log::error("Screenshot creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Создает миниатюру изображения
     */
    public function createThumbnail($sourcePath, $destinationPath, $width = 300, $height = 200)
    {
        try {
            $fullSourcePath = Storage::disk('public')->path($sourcePath);
            
            if (!file_exists($fullSourcePath)) {
                Log::error("Source image not found: {$fullSourcePath}");
                return false;
            }
            
            $imageInfo = @getimagesize($fullSourcePath);
            if (!$imageInfo) {
                return false;
            }
            
            list($srcWidth, $srcHeight, $type) = $imageInfo;
            
            $sourceImage = $this->createImageFromFile($fullSourcePath, $type);
            if (!$sourceImage) {
                return false;
            }
            
            // Рассчитываем размеры для миниатюры с сохранением пропорций
            $srcRatio = $srcWidth / $srcHeight;
            $dstRatio = $width / $height;
            
            if ($dstRatio > $srcRatio) {
                $newHeight = $height;
                $newWidth = $height * $srcRatio;
            } else {
                $newWidth = $width;
                $newHeight = $width / $srcRatio;
            }
            
            $dstX = ($width - $newWidth) / 2;
            $dstY = ($height - $newHeight) / 2;
            
            // Создаем миниатюру
            $thumbnail = imagecreatetruecolor($width, $height);
            $white = imagecolorallocate($thumbnail, 255, 255, 255);
            imagefill($thumbnail, 0, 0, $white);
            
            imagecopyresampled(
                $thumbnail, $sourceImage,
                $dstX, $dstY, 0, 0,
                $newWidth, $newHeight, $srcWidth, $srcHeight
            );
            
            // Сохраняем
            $fullDestPath = Storage::disk('public')->path($destinationPath);
            $this->createDirectory($fullDestPath);
            
            $saved = $this->saveImage($thumbnail, $fullDestPath, $type);
            
            imagedestroy($sourceImage);
            imagedestroy($thumbnail);
            
            return $saved;
            
        } catch (Exception $e) {
            Log::error("Thumbnail creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Анализирует изображение
     */
    public function analyzeImage($path)
    {
        try {
            $fullPath = Storage::disk('public')->path($path);
            
            if (!file_exists($fullPath)) {
                return ['error' => 'File not found'];
            }
            
            $imageInfo = @getimagesize($fullPath);
            if (!$imageInfo) {
                return ['error' => 'Invalid image'];
            }
            
            list($width, $height, $type) = $imageInfo;
            
            $info = [
                'width' => $width,
                'height' => $height,
                'mime' => image_type_to_mime_type($type),
                'size' => filesize($fullPath),
                'extension' => $this->getExtensionFromType($type),
                'type' => $type,
                'is_portrait' => $height > $width,
                'is_landscape' => $width > $height,
                'aspect_ratio' => $width > 0 ? $width / $height : 0
            ];
            
            return $info;
            
        } catch (Exception $e) {
            Log::error("Image analysis error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Создает изображение из файла по типу
     */
    private function createImageFromFile($path, $type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($path);
                if ($image) {
                    // Сохраняем прозрачность для PNG
                    imagealphablending($image, false);
                    imagesavealpha($image, true);
                }
                return $image;
            case IMAGETYPE_GIF:
                return imagecreatefromgif($path);
            case IMAGETYPE_BMP:
                return $this->imagecreatefrombmp($path);
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    return imagecreatefromwebp($path);
                }
                Log::warning("WebP not supported");
                return false;
            default:
                Log::warning("Unsupported image type: {$type}");
                return false;
        }
    }
    
    /**
     * Рассчитывает размеры для ресайза с сохранением пропорций
     */
    private function calculateResizeDimensions($srcWidth, $srcHeight, $maxWidth, $maxHeight)
    {
        if ($srcWidth <= $maxWidth && $srcHeight <= $maxHeight) {
            return [$srcWidth, $srcHeight];
        }
        
        $ratio = min($maxWidth / $srcWidth, $maxHeight / $srcHeight);
        $newWidth = floor($srcWidth * $ratio);
        $newHeight = floor($srcHeight * $ratio);
        
        return [$newWidth, $newHeight];
    }
    
    /**
     * Сохраняет изображение
     */
    private function saveImage($image, $path, $type)
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return imagejpeg($image, $path, 85);
            case 'png':
                // Сохраняем прозрачность
                imagesavealpha($image, true);
                return imagepng($image, $path, 8);
            case 'gif':
                return imagegif($image, $path);
            case 'bmp':
                return $this->imagebmp($image, $path);
            case 'webp':
                if (function_exists('imagewebp')) {
                    return imagewebp($image, $path, 85);
                }
                // Конвертируем в JPEG если WebP не поддерживается
                $newPath = str_replace('.webp', '.jpg', $path);
                return imagejpeg($image, $newPath, 85);
            default:
                // По умолчанию сохраняем как JPEG
                $newPath = $path . '.jpg';
                return imagejpeg($image, $newPath, 85);
        }
    }
    
    /**
     * Создает директорию если не существует
     */
    private function createDirectory($filePath)
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    /**
     * Получает расширение файла по типу изображения
     */
    private function getExtensionFromType($type)
    {
        $extensions = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_GIF => 'gif',
            IMAGETYPE_BMP => 'bmp',
            IMAGETYPE_WEBP => 'webp'
        ];
        
        return $extensions[$type] ?? 'jpg';
    }
    
    /**
     * Поддержка BMP формата (если не встроен в PHP)
     */
    private function imagecreatefrombmp($filename)
    {
        // Упрощенная загрузка BMP
        // В продакшене лучше использовать GD с поддержкой BMP или конвертировать в другой формат
        return false;
    }
    
    private function imagebmp($image, $filename)
    {
        // Упрощенное сохранение BMP
        return false;
    }

    /**
 * Проверяет, является ли изображение пустым (только белый фон)
 */
public function isEmptyImage($imagePath, $whiteThreshold = 240)
{
    try {
        $fullPath = Storage::disk('public')->path($imagePath);
        
        if (!file_exists($fullPath)) {
            return true;
        }
        
        $imageInfo = @getimagesize($fullPath);
        if (!$imageInfo) {
            return true;
        }
        
        list($width, $height, $type) = $imageInfo;
        
        // Если изображение слишком маленькое - пропускаем
        if ($width < 10 || $height < 10) {
            return true;
        }
        
        $image = $this->createImageFromFile($fullPath, $type);
        if (!$image) {
            return true;
        }
        
        // Проверяем несколько случайных точек
        $samplePoints = min(100, $width * $height / 100);
        $whiteCount = 0;
        
        for ($i = 0; $i < $samplePoints; $i++) {
            $x = rand(0, $width - 1);
            $y = rand(0, $height - 1);
            
            $color = imagecolorat($image, $x, $y);
            $rgb = imagecolorsforindex($image, $color);
            
            if ($rgb['red'] >= $whiteThreshold && 
                $rgb['green'] >= $whiteThreshold && 
                $rgb['blue'] >= $whiteThreshold) {
                $whiteCount++;
            }
        }
        
        imagedestroy($image);
        
        // Если более 95% точек белые - считаем изображение пустым
        return ($whiteCount / $samplePoints) > 0.95;
        
    } catch (\Exception $e) {
        Log::error("isEmptyImage error: " . $e->getMessage());
        return false; // В случае ошибки не пропускаем
    }
}

/**
 * Проверяет, содержит ли изображение только номер страницы
 */
public function isPageNumberOnly($imagePath)
{
    try {
        $fullPath = Storage::disk('public')->path($imagePath);
        
        if (!file_exists($fullPath)) {
            return false;
        }
        
        // Простая проверка по размеру файла
        $size = filesize($fullPath);
        
        // Если файл очень маленький (меньше 2KB), скорее всего это номер страницы
        if ($size < 2048) {
            $imageInfo = @getimagesize($fullPath);
            if ($imageInfo) {
                list($width, $height) = $imageInfo;
                
                // Если изображение узкое и высокое или маленькое
                if ($width < 100 || $height < 100) {
                    return true;
                }
            }
        }
        
        return false;
        
    } catch (\Exception $e) {
        Log::error("isPageNumberOnly error: " . $e->getMessage());
        return false;
    }
}
}