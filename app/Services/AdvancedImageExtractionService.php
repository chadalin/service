<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;
use Exception;
use Throwable;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class AdvancedImageExtractionService
{
    protected $parser;
    protected $imageManager;
    
    public function __construct()
    {
        $this->parser = new Parser();
        $this->imageManager = new ImageManager(new Driver());
    }
    
    /**
     * Универсальное извлечение изображений из PDF
     */
    public function extractImagesUniversal(Document $document, string $filePath, int $pageNumber, bool $isPreview): array
    {
        Log::info("Универсальное извлечение изображений для документа {$document->id}, страница {$pageNumber}");
        
        $images = [];
        
        try {
            if (!file_exists($filePath)) {
                throw new \Exception("Файл не найден: {$filePath}");
            }
            
            // Способ 1: Через библиотеку Smalot (основной)
            $images = $this->extractWithSmalot($document, $filePath, $pageNumber, $isPreview);
            
            // Способ 2: Через pdftoppm (если первый не сработал)
            if (empty($images) && $this->commandExists('pdftoppm')) {
                Log::info("Первый способ не сработал, пробуем pdftoppm");
                $images = $this->extractWithPdftoppm($document, $filePath, $pageNumber, $isPreview);
            }
            
            // Способ 3: Простой способ - создаем заглушку
            if (empty($images)) {
                Log::info("Все способы не сработали, создаем заглушку");
                $images = $this->createPlaceholderImage($document, $pageNumber, $isPreview);
            }
            
            Log::info("Итог: извлечено " . count($images) . " изображений");
            return $images;
            
        } catch (Throwable $e) {
            Log::error("Ошибка извлечения изображений: " . $e->getMessage());
            return [];
        }
    }
    
    private function extractWithSmalot(Document $document, string $filePath, int $pageNumber, bool $isPreview): array
    {
        $images = [];
        $imageCount = 0;
        
        try {
            $pdf = $this->parser->parseFile($filePath);
            $objects = $pdf->getObjects();
            
            Log::info("Найдено объектов в PDF: " . count($objects));
            
            foreach ($objects as $index => $object) {
                try {
                    $details = $object->getDetails();
                    
                    // Проверяем, является ли объект изображением
                    if (isset($details['Subtype']) && $details['Subtype'] === 'Image') {
                        $imageCount++;
                        
                        try {
                            $imageData = $object->getContent();
                            
                            if (empty($imageData)) {
                                continue;
                            }
                            
                            // Определяем тип изображения
                            $imageInfo = $this->analyzeImageData($imageData, $details);
                            
                            if (!$imageInfo['extension']) {
                                continue;
                            }
                            
                            // Сохраняем изображение
                            $savedImage = $this->saveImage(
                                $document, 
                                $imageData, 
                                $imageInfo, 
                                $pageNumber, 
                                $imageCount, 
                                $isPreview
                            );
                            
                            if ($savedImage) {
                                $images[] = $savedImage;
                                Log::info("Изображение сохранено: {$savedImage['filename']}");
                            }
                            
                        } catch (Throwable $e) {
                            Log::warning("Ошибка обработки изображения #{$imageCount}: " . $e->getMessage());
                            continue;
                        }
                    }
                    
                } catch (Throwable $e) {
                    Log::warning("Ошибка обработки объекта #{$index}: " . $e->getMessage());
                    continue;
                }
            }
            
        } catch (Throwable $e) {
            Log::error("Ошибка парсинга PDF через Smalot: " . $e->getMessage());
        }
        
        return $images;
    }
    
    private function analyzeImageData(string $imageData, array $details): array
    {
        $result = [
            'data' => $imageData,
            'size' => strlen($imageData),
            'width' => $details['Width'] ?? 0,
            'height' => $details['Height'] ?? 0,
            'extension' => null,
            'mime_type' => null,
        ];
        
        // Определяем тип по фильтру
        if (isset($details['Filter'])) {
            $filter = is_array($details['Filter']) ? end($details['Filter']) : $details['Filter'];
            
            switch ($filter) {
                case 'DCTDecode':
                    $result['extension'] = 'jpg';
                    break;
                case 'FlateDecode':
                case 'LZWDecode':
                    $result['extension'] = 'png';
                    break;
                case 'CCITTFaxDecode':
                    $result['extension'] = 'tiff';
                    break;
                case 'JPXDecode':
                    $result['extension'] = 'jp2';
                    break;
            }
        }
        
        // Если не определили по фильтру, пробуем по сигнатуре
        if (!$result['extension']) {
            $result['extension'] = $this->detectExtensionFromSignature($imageData);
        }
        
        // Определяем MIME-тип
        $result['mime_type'] = $this->getMimeTypeFromExtension($result['extension']);
        
        return $result;
    }
    
    private function detectExtensionFromSignature(string $data): ?string
    {
        if (strlen($data) < 2) {
            return null;
        }
        
        $bytes = substr($data, 0, 8);
        
        // JPEG
        if (bin2hex(substr($bytes, 0, 2)) === 'ffd8') {
            return 'jpg';
        }
        
        // PNG
        if (substr($bytes, 0, 8) === "\x89PNG\r\n\x1a\n") {
            return 'png';
        }
        
        // GIF
        if (substr($bytes, 0, 3) === 'GIF') {
            return 'gif';
        }
        
        // BMP
        if (substr($bytes, 0, 2) === 'BM') {
            return 'bmp';
        }
        
        return 'jpg'; // По умолчанию JPEG
    }
    
    private function getMimeTypeFromExtension(?string $extension): ?string
    {
        if (!$extension) {
            return null;
        }
        
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'tiff' => 'image/tiff',
            'jp2' => 'image/jp2',
        ];
        
        return $mimeTypes[$extension] ?? 'image/jpeg';
    }
    
    private function saveImage(
        Document $document, 
        string $imageData, 
        array $imageInfo, 
        int $pageNumber, 
        int $position, 
        bool $isPreview
    ): ?array
    {
        try {
            // Создаем уникальное имя файла
            $timestamp = time();
            $originalName = "doc_{$document->id}_page_{$pageNumber}_img_{$position}_{$timestamp}";
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
            
            // Проверяем, что файл сохранен
            if (!file_exists($mainFullPath)) {
                return null;
            }
            
            // Получаем информацию о сохраненном файле
            $savedFileSize = filesize($mainFullPath);
            $imageDetails = @getimagesize($mainFullPath);
            
            if (!$imageDetails) {
                // Пробуем пересохранить как JPEG
                return $this->convertToJpeg($document, $mainFullPath, $pageNumber, $position, $isPreview);
            }
            
            $width = $imageDetails[0];
            $height = $imageDetails[1];
            $mimeType = $imageDetails['mime'];
            
            // Создаем миниатюру
            $thumbData = $this->createThumbnail($mainFullPath, $width, $height, $mimeType);
            
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
                'original_width' => $imageInfo['width'] ?: $width,
                'original_height' => $imageInfo['height'] ?: $height,
                'size' => $savedFileSize,
                'thumbnail_size' => $thumbData['size'],
                'description' => "Изображение {$position} со страницы {$pageNumber}",
                'position' => $position,
                'mime_type' => $mimeType,
                'extension' => $extension,
            ];
            
        } catch (Throwable $e) {
            Log::error("Ошибка сохранения изображения: " . $e->getMessage());
            return null;
        }
    }
    
    private function convertToJpeg(Document $document, string $sourcePath, int $pageNumber, int $position, bool $isPreview): ?array
    {
        try {
            // Пытаемся открыть через GD
            $image = @imagecreatefromstring(file_get_contents($sourcePath));
            
            if (!$image) {
                return null;
            }
            
            // Сохраняем как JPEG
            $jpgPath = str_replace(['.png', '.gif', '.bmp', '.tiff'], '.jpg', $sourcePath);
            imagejpeg($image, $jpgPath, 90);
            imagedestroy($image);
            
            // Удаляем оригинальный файл
            if ($jpgPath !== $sourcePath) {
                unlink($sourcePath);
            }
            
            $imageDetails = getimagesize($jpgPath);
            
            return [
                'filename' => basename($jpgPath),
                'path' => str_replace(storage_path('app/'), '', $jpgPath),
                'url' => Storage::url(str_replace(storage_path('app/'), '', $jpgPath)),
                'width' => $imageDetails[0],
                'height' => $imageDetails[1],
                'size' => filesize($jpgPath),
                'thumbnail_path' => null,
                'thumbnail_url' => null,
                'description' => "Изображение {$position} со страницы {$pageNumber} (конвертировано)",
                'position' => $position,
                'mime_type' => 'image/jpeg',
                'extension' => 'jpg',
            ];
            
        } catch (Throwable $e) {
            Log::error("Ошибка конвертации изображения: " . $e->getMessage());
            return null;
        }
    }
    
    private function createThumbnail(string $sourcePath, int $width, int $height, string $mimeType): array
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
            $image = $this->imageManager->read($sourcePath);
            
            // Масштабируем для миниатюры (макс. 300px по ширине)
            if ($width > 300) {
                $image->scale(width: 300);
            }
            
            // Сохраняем как JPEG
            $image->toJpeg(80)->save($thumbStoragePath);
            
            return [
                'path' => str_replace(storage_path('app/'), '', $thumbStoragePath),
                'url' => Storage::url(str_replace(storage_path('app/'), '', $thumbStoragePath)),
                'size' => filesize($thumbStoragePath)
            ];
            
        } catch (Throwable $e) {
            // Если не удалось создать миниатюру, копируем оригинал
            copy($sourcePath, $thumbStoragePath);
            
            return [
                'path' => str_replace(storage_path('app/'), '', $thumbStoragePath),
                'url' => Storage::url(str_replace(storage_path('app/'), '', $thumbStoragePath)),
                'size' => filesize($thumbStoragePath)
            ];
        }
    }
    
    private function extractWithPdftoppm(Document $document, string $filePath, int $pageNumber, bool $isPreview): array
    {
        $images = [];
        
        try {
            $tempDir = storage_path('app/temp_' . Str::random(10));
            mkdir($tempDir, 0755, true);
            
            $outputFile = "{$tempDir}/page_{$pageNumber}";
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
                    
                    $savedImage = $this->saveImage(
                        $document, 
                        $imageData, 
                        $imageInfo, 
                        $pageNumber, 
                        1, 
                        $isPreview
                    );
                    
                    if ($savedImage) {
                        $images[] = $savedImage;
                    }
                    
                    unlink($jpegFile);
                }
            }
            
            // Очистка
            if (is_dir($tempDir)) {
                array_map('unlink', glob("{$tempDir}/*"));
                rmdir($tempDir);
            }
            
        } catch (Throwable $e) {
            Log::error("Ошибка pdftoppm: " . $e->getMessage());
        }
        
        return $images;
    }
    
    private function createPlaceholderImage(Document $document, int $pageNumber, bool $isPreview): array
    {
        $images = [];
        
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
            
            $savedImage = $this->saveImage($document, $imageData, $imageInfo, $pageNumber, 1, $isPreview);
            
            return $savedImage ? [$savedImage] : [];
            
        } catch (Throwable $e) {
            Log::error("Ошибка создания заглушки: " . $e->getMessage());
            return [];
        }
    }
    
    private function commandExists(string $command): bool
    {
        $which = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'where' : 'which';
        $process = new \Symfony\Component\Process\Process([$which, $command]);
        $process->run();
        return $process->isSuccessful();
    }
}