<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdvancedImageExtractionService
{
    /**
     * Извлечение изображений из страницы PDF
     */
    public function extractImagesFromPage(Document $document, string $filePath, int $pageNumber, bool $isPreview): array
    {
        Log::info("Извлечение изображений для документа {$document->id}, страница {$pageNumber}");
        
        try {
            if (!file_exists($filePath)) {
                throw new \Exception("Файл не найден: {$filePath}");
            }
            
            $images = [];
            
            // Способ 1: Через pdftoppm (poppler) - конвертируем всю страницу в изображение
            if ($this->commandExists('pdftoppm')) {
                $images = $this->extractWithPdftoppm($document, $filePath, $pageNumber, $isPreview);
            }
            
            // Способ 2: Создаем заглушку если не удалось извлечь
            if (empty($images)) {
                $images = $this->createPagePlaceholder($document, $pageNumber, $isPreview);
            }
            
            Log::info("Извлечено изображений для страницы {$pageNumber}: " . count($images));
            return $images;
            
        } catch (\Exception $e) {
            Log::error("Ошибка извлечения изображений: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Извлечение через pdftoppm (конвертация страницы в JPEG)
     */
    private function extractWithPdftoppm(Document $document, string $filePath, int $pageNumber, bool $isPreview): array
    {
        $images = [];
        
        try {
            $tempDir = storage_path('app/temp_' . Str::random(10));
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            $outputFile = "{$tempDir}/page_{$pageNumber}";
            
            // Команда pdftoppm для конвертации страницы в JPEG
            $command = "pdftoppm -f {$pageNumber} -l {$pageNumber} -jpeg -singlefile \"{$filePath}\" \"{$outputFile}\" 2>&1";
            
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0) {
                $jpegFile = "{$outputFile}.jpg";
                if (file_exists($jpegFile)) {
                    $imageData = file_get_contents($jpegFile);
                    
                    $imageInfo = [
                        'data' => $imageData,
                        'size' => filesize($jpegFile),
                        'width' => 1200,
                        'height' => 1600,
                        'extension' => 'jpg',
                        'mime_type' => 'image/jpeg',
                    ];
                    
                    $savedImage = $this->savePageImage(
                        $document, 
                        $imageData, 
                        $imageInfo, 
                        $pageNumber, 
                        1, 
                        $isPreview,
                        'page_image'
                    );
                    
                    if ($savedImage) {
                        $images[] = $savedImage;
                    }
                    
                    unlink($jpegFile);
                }
            } else {
                Log::error("pdftoppm command failed: " . implode("\n", $output));
            }
            
            // Очистка временной директории
            if (is_dir($tempDir)) {
                @rmdir($tempDir);
            }
            
        } catch (\Exception $e) {
            Log::error("Ошибка pdftoppm: " . $e->getMessage());
        }
        
        return $images;
    }
    
    /**
     * Сохранение изображения страницы
     */
    private function savePageImage(
        Document $document, 
        string $imageData, 
        array $imageInfo, 
        int $pageNumber, 
        int $position, 
        bool $isPreview,
        string $type = 'page_image'
    ): ?array
    {
        try {
            // Создаем уникальное имя файла
            $timestamp = time();
            $originalName = "doc_{$document->id}_page_{$pageNumber}_{$type}_{$timestamp}";
            $extension = $imageInfo['extension'] ?? 'jpg';
            
            // Основное изображение
            $mainFilename = "{$originalName}.{$extension}";
            $mainStoragePath = "documents/{$document->id}/pages/{$pageNumber}/{$mainFilename}";
            $mainFullPath = storage_path("app/" . $mainStoragePath);
            
            // Создаем директории
            $mainDir = dirname($mainFullPath);
            if (!is_dir($mainDir)) {
                mkdir($mainDir, 0755, true);
            }
            
            // Сохраняем оригинальное изображение
            file_put_contents($mainFullPath, $imageData);
            
            if (!file_exists($mainFullPath)) {
                return null;
            }
            
            // Получаем информацию о сохраненном файле
            $savedFileSize = filesize($mainFullPath);
            
            // Получаем размеры изображения
            $imageSize = @getimagesize($mainFullPath);
            $width = $imageSize ? $imageSize[0] : 1200;
            $height = $imageSize ? $imageSize[1] : 1600;
            $mimeType = $imageSize ? $imageSize['mime'] : 'image/jpeg';
            
            // Создаем миниатюру
            $thumbData = $this->createThumbnail($mainFullPath, $width, $height);
            
            // Генерируем URL
            $mainUrl = Storage::url($mainStoragePath);
            
            return [
                'filename' => $mainFilename,
                'path' => $mainStoragePath,
                'url' => $mainUrl,
                'thumbnail_path' => $thumbData['path'],
                'thumbnail_url' => $thumbData['url'],
                'width' => $width,
                'height' => $height,
                'size' => $savedFileSize,
                'thumbnail_size' => $thumbData['size'],
                'description' => "Изображение {$position} со страницы {$pageNumber} ({$type})",
                'position' => $position,
                'mime_type' => $mimeType,
                'extension' => $extension,
                'image_type' => $type
            ];
            
        } catch (\Exception $e) {
            Log::error("Ошибка сохранения изображения: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Создать миниатюру
     */
    private function createThumbnail(string $sourcePath, int $width, int $height): array
    {
        $thumbFilename = pathinfo($sourcePath, PATHINFO_FILENAME) . '_thumb.jpg';
        $thumbStoragePath = str_replace(
            pathinfo($sourcePath, PATHINFO_BASENAME),
            'thumbnails/' . $thumbFilename,
            $sourcePath
        );
        
        $thumbDir = dirname($thumbStoragePath);
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }
        
        try {
            // Простой способ создания миниатюры через GD
            $sourceImage = @imagecreatefromjpeg($sourcePath);
            if (!$sourceImage) {
                $sourceImage = @imagecreatefrompng($sourcePath);
            }
            if (!$sourceImage) {
                $sourceImage = @imagecreatefromgif($sourcePath);
            }
            
            if ($sourceImage) {
                // Размеры миниатюры
                $thumbWidth = 300;
                $thumbHeight = floor($height * ($thumbWidth / $width));
                
                // Создаем изображение для миниатюры
                $thumbImage = imagecreatetruecolor($thumbWidth, $thumbHeight);
                
                // Копируем и изменяем размер
                imagecopyresampled($thumbImage, $sourceImage, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);
                
                // Сохраняем как JPEG
                imagejpeg($thumbImage, $thumbStoragePath, 80);
                
                // Освобождаем память
                imagedestroy($sourceImage);
                imagedestroy($thumbImage);
                
                return [
                    'path' => str_replace(storage_path('app/'), '', $thumbStoragePath),
                    'url' => Storage::url(str_replace(storage_path('app/'), '', $thumbStoragePath)),
                    'size' => filesize($thumbStoragePath)
                ];
            }
            
        } catch (\Exception $e) {
            Log::error("Ошибка создания миниатюры: " . $e->getMessage());
        }
        
        // Если не удалось создать миниатюру, копируем оригинал
        copy($sourcePath, $thumbStoragePath);
        
        return [
            'path' => str_replace(storage_path('app/'), '', $thumbStoragePath),
            'url' => Storage::url(str_replace(storage_path('app/'), '', $thumbStoragePath)),
            'size' => filesize($thumbStoragePath)
        ];
    }
    
    /**
     * Создать заглушку страницы
     */
    private function createPagePlaceholder(Document $document, int $pageNumber, bool $isPreview): array
    {
        try {
            $width = 800;
            $height = 1131;
            
            // Создаем пустое изображение
            $image = imagecreatetruecolor($width, $height);
            $bgColor = imagecolorallocate($image, 240, 240, 240);
            $textColor = imagecolorallocate($image, 100, 100, 100);
            
            imagefill($image, 0, 0, $bgColor);
            
            // Добавляем текст
            $text = "Страница {$pageNumber}\nИзображения не извлечены";
            $lines = explode("\n", $text);
            $y = $height / 2 - count($lines) * 20;
            
            foreach ($lines as $line) {
                imagestring($image, 5, $width/2 - strlen($line)*5, $y, $line, $textColor);
                $y += 30;
            }
            
            // Сохраняем в буфер
            ob_start();
            imagejpeg($image, null, 80);
            $imageData = ob_get_clean();
            imagedestroy($image);
            
            $imageInfo = [
                'data' => $imageData,
                'size' => strlen($imageData),
                'width' => $width,
                'height' => $height,
                'extension' => 'jpg',
                'mime_type' => 'image/jpeg',
            ];
            
            $savedImage = $this->savePageImage(
                $document, 
                $imageData, 
                $imageInfo, 
                $pageNumber, 
                1, 
                $isPreview,
                'placeholder'
            );
            
            return $savedImage ? [$savedImage] : [];
            
        } catch (\Exception $e) {
            Log::error("Ошибка создания заглушки: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Проверить существование команды в системе
     */
    private function commandExists(string $command): bool
    {
        try {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $output = shell_exec("where {$command} 2>&1");
            } else {
                $output = shell_exec("which {$command} 2>&1");
            }
            
            return !empty($output);
            
        } catch (\Exception $e) {
            return false;
        }
    }
}