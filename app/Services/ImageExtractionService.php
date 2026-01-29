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
     * Извлечение изображений из PDF документа с сохранением в базу
     */
    public function extractAndSaveImages(Document $document, string $filePath, int $pageNumber, bool $isPreview = false): array
    {
        $savedImages = [];
        
        try {
            Log::info("Начало извлечения изображений для документа {$document->id}, страница {$pageNumber}");
            
            if (!file_exists($filePath)) {
                throw new \Exception("Файл не найден: {$filePath}");
            }
            
            // Парсим PDF
            $pdf = $this->parser->parseFile($filePath);
            $objects = $pdf->getObjects();
            
            Log::info("Найдено объектов в PDF: " . count($objects));
            
            $imageCount = 0;
            $imageNumber = 0;
            
            foreach ($objects as $index => $object) {
                try {
                    $details = $object->getDetails();
                    
                    // Проверяем, является ли объект изображением
                    if ($this->isImageObject($details)) {
                        $imageNumber++;
                        
                        // Пропускаем изображения с предыдущих страниц
                        if ($this->shouldSkipImage($details, $pageNumber)) {
                            continue;
                        }
                        
                        $imageData = $object->getContent();
                        
                        if (empty($imageData)) {
                            Log::warning("Пустые данные изображения #{$imageNumber}");
                            continue;
                        }
                        
                        // Определяем тип изображения
                        $imageInfo = $this->analyzeImageData($imageData, $details);
                        
                        if (!$imageInfo['extension']) {
                            Log::warning("Не удалось определить тип изображения #{$imageNumber}");
                            continue;
                        }
                        
                        Log::info("Обработка изображения #{$imageNumber}: {$imageInfo['width']}×{$imageInfo['height']}, тип: {$imageInfo['extension']}");
                        
                        // Сохраняем изображение
                        $savedImage = $this->saveImage(
                            $document, 
                            $imageData, 
                            $imageInfo, 
                            $pageNumber, 
                            $imageNumber, 
                            $isPreview
                        );
                        
                        if ($savedImage) {
                            $savedImages[] = $savedImage;
                            $imageCount++;
                            
                            Log::info("Изображение сохранено: {$savedImage['filename']}, размер: {$imageInfo['size']} байт");
                        }
                        
                        // Ограничение на количество изображений на страницу
                        if ($imageCount >= 20) {
                            Log::info("Достигнут лимит изображений на страницу {$pageNumber}");
                            break;
                        }
                    }
                    
                } catch (Throwable $e) {
                    Log::warning("Ошибка обработки объекта #{$index}: " . $e->getMessage());
                    continue;
                }
            }
            
            Log::info("Извлечено изображений для страницы {$pageNumber}: " . count($savedImages));
            
            // Если не найдено встроенных изображений, пробуем извлечь страницу как изображение
            if (empty($savedImages)) {
                Log::info("Встроенные изображения не найдены, пробуем конвертировать страницу в изображение");
                $savedImages = $this->convertPageToImage($document, $filePath, $pageNumber, $isPreview);
            }
            
            return $savedImages;
            
        } catch (Throwable $e) {
            Log::error("Ошибка извлечения изображений: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            return [];
        }
    }
    
    /**
     * Проверяем, является ли объект изображением
     */
    private function isImageObject(array $details): bool
    {
        // Основной способ - проверка Subtype
        if (isset($details['Subtype']) && $details['Subtype'] === 'Image') {
            return true;
        }
        
        // Альтернативные способы определения изображений
        if (isset($details['Filter'])) {
            $filters = is_array($details['Filter']) ? $details['Filter'] : [$details['Filter']];
            $imageFilters = ['DCTDecode', 'JPXDecode', 'CCITTFaxDecode', 'JBIG2Decode', 'JPXDecode', 'FlateDecode', 'LZWDecode'];
            
            foreach ($filters as $filter) {
                if (in_array($filter, $imageFilters)) {
                    return true;
                }
            }
        }
        
        // Проверка по наличию размеров
        if (isset($details['Width']) && isset($details['Height']) && $details['Width'] > 10 && $details['Height'] > 10) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Пропускаем изображения с других страниц
     */
    private function shouldSkipImage(array $details, int $currentPage): bool
    {
        // Здесь можно добавить логику фильтрации по страницам
        // Некоторые PDF хранят информацию о странице в метаданных
        return false;
    }
    
    /**
     * Анализ данных изображения
     */
    private function analyzeImageData(string $imageData, array $details): array
    {
        $result = [
            'data' => $imageData,
            'size' => strlen($imageData),
            'width' => $details['Width'] ?? 0,
            'height' => $details['Height'] ?? 0,
            'extension' => null,
            'mime_type' => null,
            'color_space' => $details['ColorSpace'] ?? 'DeviceRGB',
            'bits_per_component' => $details['BitsPerComponent'] ?? 8,
            'filter' => $details['Filter'] ?? null,
        ];
        
        // Определяем тип по фильтру
        $result['extension'] = $this->getExtensionFromFilter($result['filter']);
        
        // Если не определили по фильтру, пробуем по сигнатуре
        if (!$result['extension']) {
            $result['extension'] = $this->detectExtensionFromSignature($imageData);
        }
        
        // Определяем MIME-тип
        $result['mime_type'] = $this->getMimeTypeFromExtension($result['extension']);
        
        // Если размеры не указаны, пытаемся определить их из данных
        if (!$result['width'] || !$result['height']) {
            $dimensions = $this->extractDimensionsFromImageData($imageData, $result['extension']);
            if ($dimensions) {
                $result['width'] = $dimensions['width'];
                $result['height'] = $dimensions['height'];
            }
        }
        
        return $result;
    }
    
    /**
     * Определение расширения по фильтру
     */
    private function getExtensionFromFilter($filter): ?string
    {
        if (!$filter) {
            return null;
        }
        
        $filters = is_array($filter) ? $filter : [$filter];
        
        foreach ($filters as $f) {
            switch ($f) {
                case 'DCTDecode':
                    return 'jpg';
                case 'FlateDecode':
                case 'LZWDecode':
                    return 'png';
                case 'CCITTFaxDecode':
                    return 'tiff';
                case 'JBIG2Decode':
                    return 'jbig2';
                case 'JPXDecode':
                    return 'jp2';
            }
        }
        
        return null;
    }
    
    /**
     * Определение типа по сигнатуре файла
     */
    private function detectExtensionFromSignature(string $data): ?string
    {
        $bytes = substr($data, 0, 12);
        
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
        
        // TIFF
        if (substr($bytes, 0, 4) === 'II*' || substr($bytes, 0, 4) === 'MM*') {
            return 'tiff';
        }
        
        // WebP
        if (substr($bytes, 0, 4) === 'RIFF' && substr($data, 8, 4) === 'WEBP') {
            return 'webp';
        }
        
        // JPEG2000
        if (bin2hex(substr($bytes, 0, 4)) === '0000000c' && substr($bytes, 4, 4) === 'jP  ') {
            return 'jp2';
        }
        
        return null;
    }
    
    /**
     * Получение MIME-типа по расширению
     */
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
            'webp' => 'image/webp',
            'jp2' => 'image/jp2',
            'jbig2' => 'image/jbig2',
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
    
    /**
     * Извлечение размеров из данных изображения
     */
    private function extractDimensionsFromImageData(string $data, ?string $extension): ?array
    {
        if (!$extension || strlen($data) < 24) {
            return null;
        }
        
        try {
            // Для JPEG
            if ($extension === 'jpg' || $extension === 'jpeg') {
                $offset = 2;
                while ($offset < strlen($data) - 2) {
                    $marker = unpack('n', substr($data, $offset, 2))[1];
                    $offset += 2;
                    
                    // SOF markers (Start of Frame)
                    if ($marker >= 0xFFC0 && $marker <= 0xFFCF && $marker != 0xFFC4 && $marker != 0xFFC8) {
                        $offset += 3; // Skip length
                        $height = unpack('n', substr($data, $offset, 2))[1];
                        $offset += 2;
                        $width = unpack('n', substr($data, $offset, 2))[1];
                        return ['width' => $width, 'height' => $height];
                    }
                    
                    $length = unpack('n', substr($data, $offset, 2))[1];
                    $offset += $length;
                }
            }
            
            // Для PNG
            if ($extension === 'png') {
                $width = unpack('N', substr($data, 16, 4))[1];
                $height = unpack('N', substr($data, 20, 4))[1];
                return ['width' => $width, 'height' => $height];
            }
            
            // Для GIF
            if ($extension === 'gif') {
                $width = unpack('v', substr($data, 6, 2))[1];
                $height = unpack('v', substr($data, 8, 2))[1];
                return ['width' => $width, 'height' => $height];
            }
            
            // Для BMP
            if ($extension === 'bmp') {
                $width = unpack('V', substr($data, 18, 4))[1];
                $height = unpack('V', substr($data, 22, 4))[1];
                return ['width' => $width, 'height' => $height];
            }
            
        } catch (Throwable $e) {
            Log::warning("Не удалось извлечь размеры изображения: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Сохранение изображения в хранилище и базу данных
     */
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
            $extension = $imageInfo['extension'];
            
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
            if (!file_exists($mainFullPath) || filesize($mainFullPath) === 0) {
                Log::error("Файл не сохранен или пустой: {$mainFullPath}");
                return null;
            }
            
            // Получаем информацию о сохраненном файле
            $savedFileSize = filesize($mainFullPath);
            $imageDetails = @getimagesize($mainFullPath);
            
            if (!$imageDetails) {
                Log::warning("Не удалось прочитать сохраненное изображение, возможно повреждено");
                // Пытаемся пересохранить в JPEG
                return $this->convertAndSave($document, $mainFullPath, $pageNumber, $position, $isPreview);
            }
            
            $width = $imageDetails[0];
            $height = $imageDetails[1];
            $mimeType = $imageDetails['mime'];
            
            // Создаем миниатюру
            $thumbData = $this->createThumbnail($mainFullPath, $width, $height, $mimeType);
            
            // Генерируем URL
            $mainUrl = Storage::url($mainStoragePath);
            
            $imageData = [
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
                'color_space' => $imageInfo['color_space'],
                'bits_per_component' => $imageInfo['bits_per_component'],
            ];
            
            // Сохраняем в базу данных
            $this->saveToDatabase($document, $imageData, $pageNumber, $isPreview);
            
            return $imageData;
            
        } catch (Throwable $e) {
            Log::error("Ошибка сохранения изображения: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            return null;
        }
    }
    
    /**
     * Создание миниатюры
     */
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
            // Используем Intervention Image для создания миниатюры
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
            Log::warning("Не удалось создать миниатюру: " . $e->getMessage());
            
            // Если не удалось создать миниатюру, копируем оригинал
            copy($sourcePath, $thumbStoragePath);
            
            return [
                'path' => str_replace(storage_path('app/'), '', $thumbStoragePath),
                'url' => Storage::url(str_replace(storage_path('app/'), '', $thumbStoragePath)),
                'size' => filesize($thumbStoragePath)
            ];
        }
    }
    
    /**
     * Конвертация и сохранение проблемного изображения
     */
    private function convertAndSave(Document $document, string $sourcePath, int $pageNumber, int $position, bool $isPreview): ?array
    {
        try {
            // Пытаемся открыть через GD
            $imageData = file_get_contents($sourcePath);
            $image = @imagecreatefromstring($imageData);
            
            if (!$image) {
                Log::warning("Не удалось открыть изображение даже через GD");
                return null;
            }
            
            // Сохраняем как JPEG
            $jpgPath = str_replace(['.png', '.gif', '.bmp', '.tiff'], '.jpg', $sourcePath);
            imagejpeg($image, $jpgPath, 90);
            imagedestroy($image);
            
            // Удаляем оригинальный файл
            unlink($sourcePath);
            
            // Обновляем информацию о файле
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
    
    /**
     * Сохранение информации в базу данных
     */
    private function saveToDatabase(Document $document, array $imageData, int $pageNumber, bool $isPreview): void
    {
        try {
            // Находим страницу
            $page = \App\Models\DocumentPage::where('document_id', $document->id)
                ->where('page_number', $pageNumber)
                ->where('is_preview', $isPreview)
                ->first();
            
            if (!$page) {
                Log::warning("Страница не найдена для сохранения изображения: документ {$document->id}, страница {$pageNumber}");
                return;
            }
            
            DocumentImage::create([
                'document_id' => $document->id,
                'page_id' => $page->id,
                'page_number' => $pageNumber,
                'filename' => $imageData['filename'],
                'path' => $imageData['path'],
                'url' => $imageData['url'],
                'thumbnail_path' => $imageData['thumbnail_path'],
                'thumbnail_url' => $imageData['thumbnail_url'],
                'width' => $imageData['width'],
                'height' => $imageData['height'],
                'original_width' => $imageData['original_width'],
                'original_height' => $imageData['original_height'],
                'size' => $imageData['size'],
                'thumbnail_size' => $imageData['thumbnail_size'],
                'description' => $imageData['description'],
                'position' => $imageData['position'],
                'mime_type' => $imageData['mime_type'],
                'extension' => $imageData['extension'],
                'is_preview' => $isPreview,
                'status' => 'extracted',
                'metadata' => json_encode([
                    'color_space' => $imageData['color_space'] ?? null,
                    'bits_per_component' => $imageData['bits_per_component'] ?? null,
                ])
            ]);
            
            // Обновляем флаг наличия изображений на странице
            $page->update(['has_images' => true]);
            
        } catch (Throwable $e) {
            Log::error("Ошибка сохранения изображения в БД: " . $e->getMessage());
        }
    }
    
    /**
     * Конвертация всей страницы в изображение (запасной метод)
     */
    private function convertPageToImage(Document $document, string $filePath, int $pageNumber, bool $isPreview): array
    {
        $images = [];
        
        // Проверяем доступность pdftoppm
        if ($this->commandExists('pdftoppm')) {
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
                        $imageInfo = $this->analyzeImageData($imageData, []);
                        
                        if ($imageInfo['extension']) {
                            $imageInfo['width'] = $imageInfo['width'] ?: 1200;
                            $imageInfo['height'] = $imageInfo['height'] ?: 1600;
                            
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
                Log::error("Ошибка конвертации страницы в изображение: " . $e->getMessage());
            }
        }
        
        return $images;
    }
    
    /**
     * Проверка наличия команды в системе
     */
    private function commandExists(string $command): bool
    {
        $which = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'where' : 'which';
        $process = new \Symfony\Component\Process\Process([$which, $command]);
        $process->run();
        return $process->isSuccessful();
    }
}