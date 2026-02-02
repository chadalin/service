<?php

namespace App\Services;

use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class ImageScreenshotService
{
    /**
     * Создает оптимизированный скриншот с обрезкой белого
     */
    public function createScreenshot($sourcePath, $destinationPath, $maxWidth = 800, $maxHeight = 600)
    {
        try {
            $fullSourcePath = Storage::disk('public')->path($sourcePath);
            
            // Проверяем существует ли исходный файл
            if (!file_exists($fullSourcePath)) {
                Log::error("Source image not found: {$fullSourcePath}");
                return false;
            }
            
            // Создаем изображение через Intervention Image
            $image = Image::make($fullSourcePath);
            
            // 1. Обрезаем белые поля
            $image = $this->trimWhiteSpace($image);
            
            // 2. Изменяем размер для скриншота
            $image->resize($maxWidth, $maxHeight, function ($constraint) {
                $constraint->aspectRatio(); // Сохраняем пропорции
                $constraint->upsize(); // Не увеличиваем если меньше
            });
            
            // 3. Оптимизируем качество
            $fullDestPath = Storage::disk('public')->path($destinationPath);
            
            // Создаем директорию если не существует
            $dir = dirname($fullDestPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Сохраняем с оптимизацией
            $extension = strtolower(pathinfo($fullSourcePath, PATHINFO_EXTENSION));
            
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $image->save($fullDestPath, 85); // 85% качество для JPEG
                    break;
                case 'png':
                    $image->save($fullDestPath, 8); // 8 уровень сжатия для PNG
                    break;
                case 'gif':
                    $image->save($fullDestPath);
                    break;
                default:
                    // Для неизвестных форматов конвертируем в JPEG
                    $image->encode('jpg', 85);
                    $fullDestPath = str_replace($extension, 'jpg', $fullDestPath);
                    $destinationPath = str_replace($extension, 'jpg', $destinationPath);
                    $image->save($fullDestPath);
                    break;
            }
            
            // Освобождаем память
            $image->destroy();
            
            Log::info("Screenshot created: {$destinationPath}");
            return $destinationPath;
            
        } catch (Exception $e) {
            Log::error("Screenshot creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Обрезает белые поля вокруг изображения
     */
    private function trimWhiteSpace($image)
    {
        try {
            // Конвертируем в RGB для работы с цветами
            $image = $image->encode('png');
            
            // Получаем размеры изображения
            $width = $image->width();
            $height = $image->height();
            
            // Находим границы не-белых пикселей
            $left = $width;
            $top = $height;
            $right = 0;
            $bottom = 0;
            
            // Проходим по всем пикселям для определения границ
            // Это упрощенный алгоритм - для больших изображений может быть медленным
            // В продакшене лучше использовать более оптимизированный метод
            
            // Вместо полного сканирования используем выборку
            $sampleStep = max(1, floor(min($width, $height) / 100));
            
            for ($x = 0; $x < $width; $x += $sampleStep) {
                for ($y = 0; $y < $height; $y += $sampleStep) {
                    $color = $image->pickColor($x, $y, 'array');
                    
                    // Проверяем, что пиксель не белый (не слишком светлый)
                    // Белый цвет: RGB(255,255,255)
                    $isWhite = $color[0] > 240 && $color[1] > 240 && $color[2] > 240;
                    
                    if (!$isWhite) {
                        $left = min($left, $x);
                        $top = min($top, $y);
                        $right = max($right, $x);
                        $bottom = max($bottom, $y);
                    }
                }
            }
            
            // Добавляем небольшой отступ
            $padding = 5;
            $left = max(0, $left - $padding);
            $top = max(0, $top - $padding);
            $right = min($width, $right + $padding);
            $bottom = min($height, $bottom + $padding);
            
            // Обрезаем только если нашли не-белые области
            if ($left < $right && $top < $bottom) {
                $cropWidth = $right - $left;
                $cropHeight = $bottom - $top;
                
                // Обрезаем не менее 10% от исходного размера
                if ($cropWidth > $width * 0.1 && $cropHeight > $height * 0.1) {
                    $image->crop($cropWidth, $cropHeight, $left, $top);
                }
            }
            
            return $image;
            
        } catch (Exception $e) {
            Log::warning("White space trim error: " . $e->getMessage());
            return $image; // Возвращаем оригинал в случае ошибки
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
                return false;
            }
            
            $image = Image::make($fullSourcePath);
            
            // Создаем миниатюру с сохранением пропорций
            $image->fit($width, $height, function ($constraint) {
                $constraint->upsize();
            });
            
            $fullDestPath = Storage::disk('public')->path($destinationPath);
            
            // Создаем директорию
            $dir = dirname($fullDestPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Сохраняем с оптимизацией
            $image->save($fullDestPath, 80);
            $image->destroy();
            
            return true;
            
        } catch (Exception $e) {
            Log::error("Thumbnail creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Анализирует изображение и возвращает информацию
     */
    public function analyzeImage($path)
    {
        try {
            $fullPath = Storage::disk('public')->path($path);
            
            if (!file_exists($fullPath)) {
                return ['error' => 'File not found'];
            }
            
            $image = Image::make($fullPath);
            
            $info = [
                'width' => $image->width(),
                'height' => $image->height(),
                'mime' => $image->mime(),
                'size' => filesize($fullPath),
                'extension' => pathinfo($fullPath, PATHINFO_EXTENSION),
                'colors' => $this->analyzeColors($image),
                'has_transparency' => $this->hasTransparency($image),
                'is_portrait' => $image->height() > $image->width(),
                'is_landscape' => $image->width() > $image->height(),
                'aspect_ratio' => $image->width() / $image->height()
            ];
            
            $image->destroy();
            
            return $info;
            
        } catch (Exception $e) {
            Log::error("Image analysis error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Анализирует основные цвета изображения
     */
    private function analyzeColors($image)
    {
        // Упрощенный анализ цветов
        $image->limitColors(8); // Ограничиваем количество цветов для анализа
        $colors = [];
        
        // Берем несколько точек для анализа
        $width = $image->width();
        $height = $image->height();
        
        $points = [
            [$width * 0.25, $height * 0.25],
            [$width * 0.75, $height * 0.25],
            [$width * 0.5, $height * 0.5],
            [$width * 0.25, $height * 0.75],
            [$width * 0.75, $height * 0.75]
        ];
        
        foreach ($points as $point) {
            $color = $image->pickColor($point[0], $point[1], 'hex');
            $colors[] = $color;
        }
        
        return array_unique($colors);
    }
    
    /**
     * Проверяет наличие прозрачности
     */
    private function hasTransparency($image)
    {
        try {
            $extension = strtolower(pathinfo($image->basePath(), PATHINFO_EXTENSION));
            
            // PNG и GIF могут иметь прозрачность
            if ($extension === 'png' || $extension === 'gif') {
                // Проверяем альфа-канал
                $alpha = $image->pickColor(0, 0, 'array')[3] ?? 1;
                return $alpha < 1;
            }
            
            return false;
            
        } catch (Exception $e) {
            return false;
        }
    }
}