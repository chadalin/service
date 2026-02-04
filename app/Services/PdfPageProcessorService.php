<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Models\DocumentPage;
use App\Models\DocumentImage;

class PdfPageProcessorService
{
    protected $imageManager;
    
    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }
    
    /**
     * Обрабатывает все страницы PDF и создает скриншоты ВСЕХ страниц
     */
    public function processPdfPages($pdfFilePath, $documentId, $totalPages)
    {
        Log::info("🔍 Начинаем обработку PDF: {$pdfFilePath}, страниц: {$totalPages}");
        
        $pagesWithScreenshots = 0;
        
        try {
            // Создаем директории
            $screenshotsDir = "document_images/screenshots/{$documentId}";
            $pagesDir = "document_pages/{$documentId}";
            Storage::disk('public')->makeDirectory($screenshotsDir, 0755, true);
            Storage::disk('public')->makeDirectory($pagesDir, 0755, true);
            
            // Парсим текст для всех страниц
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($pdfFilePath);
            $pages = $pdf->getPages();
            
            // Обрабатываем каждую страницу
            for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++) {
                try {
                    Log::info("📄 Обработка страницы {$pageNumber}/{$totalPages}");
                    
                    // Получаем текст страницы
                    $page = $pages[$pageNumber - 1];
                    $text = $page->getText();
                    $textLength = strlen(trim($text));
                    
                    Log::debug("📝 Страница {$pageNumber}: {$textLength} символов");
                    
                    // Для КАЖДОЙ страницы создаем скриншот
                    $screenshotCreated = $this->createPageScreenshot($pdfFilePath, $documentId, $pageNumber, $text);
                    
                    if ($screenshotCreated) {
                        $pagesWithScreenshots++;
                        Log::info("✅ Страница {$pageNumber} - скриншот создан");
                    } else {
                        Log::warning("❌ Страница {$pageNumber} - не удалось создать скриншот");
                    }
                    
                } catch (Exception $e) {
                    Log::error("❌ Ошибка обработки страницы {$pageNumber}: " . $e->getMessage());
                }
            }
            
            Log::info("✅ Создано скриншотов страниц: {$pagesWithScreenshots} из {$totalPages}");
            
            return $pagesWithScreenshots;
            
        } catch (Exception $e) {
            Log::error("❌ Ошибка обработки PDF: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Создает скриншот страницы PDF
     */
    private function createPageScreenshot($pdfFilePath, $documentId, $pageNumber, $pageText)
    {
        try {
            // 1. Создаем скриншот всей страницы
            $screenshotPath = $this->createFullPageScreenshot($pdfFilePath, $pageNumber, $documentId);
            
            if (!$screenshotPath) {
                Log::warning("Не удалось создать скриншот страницы {$pageNumber}");
                return false;
            }
            
            // 2. Загружаем изображение
            $image = $this->imageManager->read($screenshotPath);
            $width = $image->width();
            $height = $image->height();
            
            Log::debug("📏 Размер скриншота страницы {$pageNumber}: {$width}x{$height}");
            
            // 3. Сохраняем скриншот страницы (полный)
            $saved = $this->savePageScreenshot($image, $documentId, $pageNumber, $pageText, $width, $height);
            
            // 4. Также создаем оптимизированную версию (для предпросмотра)
            $this->createOptimizedVersion($image, $documentId, $pageNumber);
            
            // 5. Очищаем временный файл
            if (file_exists($screenshotPath)) {
                unlink($screenshotPath);
            }
            
            return $saved;
            
        } catch (Exception $e) {
            Log::error("Ошибка создания скриншота страницы {$pageNumber}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Создает полный скриншот страницы PDF
     */
    private function createFullPageScreenshot($pdfFilePath, $pageNumber, $documentId)
    {
        try {
            if (!file_exists($pdfFilePath)) {
                Log::error("PDF файл не найден: {$pdfFilePath}");
                return null;
            }
            
            // Создаем временную директорию
            $tempDir = storage_path('app/temp_pdf_screenshots');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            $outputPath = $tempDir . "/page_{$documentId}_{$pageNumber}.jpg";
            
            // Используем Imagick для создания скриншота
            if (extension_loaded('imagick')) {
                try {
                    $imagick = new \Imagick();
                    $imagick->setResolution(150, 150);
                    
                    // Читаем конкретную страницу
                    $imagick->readImage($pdfFilePath . '[' . ($pageNumber - 1) . ']');
                    
                    // Устанавливаем формат и качество
                    $imagick->setImageFormat('jpg');
                    $imagick->setImageCompression(\Imagick::COMPRESSION_JPEG);
                    $imagick->setImageCompressionQuality(85);
                    
                    // Автоматический поворот
                    $imagick->autoOrient();
                    
                    // Сохраняем
                    $imagick->writeImage($outputPath);
                    $imagick->clear();
                    $imagick->destroy();
                    
                    Log::debug("✅ Скриншот страницы {$pageNumber} создан через Imagick");
                    
                } catch (\ImagickException $e) {
                    Log::warning("Imagick ошибка для страницы {$pageNumber}: " . $e->getMessage());
                    // Создаем fallback изображение
                    $this->createFallbackImage($outputPath, $pageNumber);
                }
            } else {
                // Fallback: создаем тестовое изображение
                $this->createFallbackImage($outputPath, $pageNumber);
            }
            
            return file_exists($outputPath) ? $outputPath : null;
            
        } catch (Exception $e) {
            Log::error("Ошибка создания скриншота страницы {$pageNumber}: " . $e->getMessage());
            
            // Создаем fallback изображение
            $tempDir = storage_path('app/temp_pdf_screenshots');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            $outputPath = $tempDir . "/page_{$documentId}_{$pageNumber}_fallback.jpg";
            $this->createFallbackImage($outputPath, $pageNumber);
            
            return file_exists($outputPath) ? $outputPath : null;
        }
    }
    
    /**
     * Создает fallback изображение для тестирования
     */
    private function createFallbackImage($outputPath, $pageNumber)
    {
        $width = 1200;
        $height = 1600;
        $image = imagecreatetruecolor($width, $height);
        
        // Цвета
        $bgColor = imagecolorallocate($image, 255, 255, 255);
        $textColor = imagecolorallocate($image, 0, 0, 0);
        $borderColor = imagecolorallocate($image, 200, 200, 200);
        
        // Фон
        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
        
        // Рамка
        imagerectangle($image, 10, 10, $width - 10, $height - 10, $borderColor);
        
        // Текст
        $text = "Страница {$pageNumber} - Тестовый скриншот";
        $font = 5;
        $textWidth = imagefontwidth($font) * strlen($text);
        $textX = ($width - $textWidth) / 2;
        $textY = $height / 2;
        
        imagestring($image, $font, $textX, $textY, $text, $textColor);
        
        // Сохраняем
        imagejpeg($image, $outputPath, 85);
        imagedestroy($image);
        
        Log::debug("📄 Создано fallback изображение для страницы {$pageNumber}");
    }
    
    /**
     * Сохраняет скриншот страницы
     */
    private function savePageScreenshot($image, $documentId, $pageNumber, $pageText, $originalWidth, $originalHeight)
    {
        try {
            // Создаем директории
            $screenshotsDir = "document_images/screenshots/{$documentId}";
            Storage::disk('public')->makeDirectory($screenshotsDir, 0755, true);
            
            // Имя файла для полного скриншота
            $fullFilename = "page_{$pageNumber}_full.jpg";
            $fullPath = Storage::disk('public')->path($screenshotsDir . '/' . $fullFilename);
            
            // Сохраняем полный скриншот (уменьшенный для экономии места)
            $maxWidth = 1600;
            $maxHeight = 1200;
            
            if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
                $image->scale($maxWidth, $maxHeight);
            }
            
            // Сохраняем с хорошим качеством
            $image->toJpeg(85)->save($fullPath);
            
            // Получаем информацию о файле
            $fileSize = filesize($fullPath);
            $imageInfo = getimagesize($fullPath);
            list($width, $height) = $imageInfo;
            
            // Сохраняем в базу данных
            $this->saveImageToDatabase($documentId, $pageNumber, $screenshotsDir . '/' . $fullFilename, 
                                     $width, $height, $fileSize, $pageText, $originalWidth, $originalHeight);
            
            // Обновляем контент страницы
            $this->updatePageContent($documentId, $pageNumber, $screenshotsDir . '/' . $fullFilename, $pageText);
            
            Log::info("💾 Сохранен скриншот страницы {$pageNumber}: {$width}x{$height}, {$fileSize} байт");
            
            return true;
            
        } catch (Exception $e) {
            Log::error("Ошибка сохранения скриншота страницы {$pageNumber}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Создает оптимизированную версию для предпросмотра
     */
    private function createOptimizedVersion($image, $documentId, $pageNumber)
    {
        try {
            $screenshotsDir = "document_images/screenshots/{$documentId}";
            
            // Имя файла для оптимизированной версии
            $optimizedFilename = "page_{$pageNumber}_preview.jpg";
            $optimizedPath = Storage::disk('public')->path($screenshotsDir . '/' . $optimizedFilename);
            
            // Создаем копию изображения для оптимизации
            $previewImage = clone $image;
            
            // Размер для предпросмотра
            $previewWidth = 800;
            $previewHeight = 600;
            
            if ($previewImage->width() > $previewWidth || $previewImage->height() > $previewHeight) {
                $previewImage->scale($previewWidth, $previewHeight);
            }
            
            // Улучшаем качество для просмотра
            $previewImage->contrast(5);
            $previewImage->sharpen(10);
            
            // Сохраняем с хорошим сжатием
            $previewImage->toJpeg(75)->save($optimizedPath);
            
            Log::debug("🖼️ Создана оптимизированная версия для страницы {$pageNumber}");
            
        } catch (Exception $e) {
            Log::debug("Не удалось создать оптимизированную версию для страницы {$pageNumber}: " . $e->getMessage());
        }
    }
    
    /**
     * Сохраняет информацию об изображении в базу
     */
    private function saveImageToDatabase($documentId, $pageNumber, $imagePath, $width, $height, $fileSize, $text, $originalWidth = null, $originalHeight = null)
    {
        try {
            // Проверяем, есть ли уже изображение для этой страницы
            $existingImage = DocumentImage::where('document_id', $documentId)
                ->where('page_number', $pageNumber)
                ->where('filename', basename($imagePath))
                ->first();
            
            if (!$existingImage) {
                DocumentImage::create([
                    'document_id' => $documentId,
                    'page_number' => $pageNumber,
                    'filename' => basename($imagePath),
                    'path' => $imagePath,
                    'url' => Storage::url($imagePath),
                    'screenshot_path' => $imagePath,
                    'screenshot_url' => Storage::url($imagePath),
                    'width' => $width,
                    'height' => $height,
                    'original_width' => $originalWidth,
                    'original_height' => $originalHeight,
                    'size' => $fileSize,
                    'format' => 'jpg',
                    'has_screenshot' => true,
                    'is_full_page' => true,
                    'description' => $this->generateDescription($text, $pageNumber),
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                Log::debug("💾 Изображение сохранено в БД для страницы {$pageNumber}");
            } else {
                // Обновляем существующее
                $existingImage->update([
                    'screenshot_path' => $imagePath,
                    'screenshot_url' => Storage::url($imagePath),
                    'width' => $width,
                    'height' => $height,
                    'original_width' => $originalWidth,
                    'original_height' => $originalHeight,
                    'size' => $fileSize,
                    'has_screenshot' => true,
                    'is_full_page' => true,
                    'updated_at' => now(),
                ]);
                
                Log::debug("🔄 Изображение обновлено в БД для страницы {$pageNumber}");
            }
            
        } catch (Exception $e) {
            Log::error("Ошибка сохранения в БД: " . $e->getMessage());
        }
    }
    
    /**
     * Обновляет контент страницы для отображения скриншота
     */
    private function updatePageContent($documentId, $pageNumber, $imagePath, $text)
    {
        try {
            $imageUrl = Storage::url($imagePath);
            $cleanText = htmlspecialchars(trim($text));
            $shortText = mb_substr($cleanText, 0, 300) . (mb_strlen($cleanText) > 300 ? '...' : '');
            
            $newContent = <<<HTML
<div class="page-with-screenshot">
    <div class="alert alert-success mb-3">
        <i class="bi bi-image"></i> <strong>Скриншот страницы сохранен</strong>
        <span class="badge bg-primary ms-2">Страница {$pageNumber}</span>
        <span class="badge bg-info ms-1"><i class="bi bi-check-circle"></i> Полная страница</span>
    </div>
    
    <div class="screenshot-section card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="text-center">
                <h5 class="mb-3 text-primary">
                    <i class="bi bi-file-earmark-image"></i> Скриншот страницы {$pageNumber}
                </h5>
                
                <div class="image-container mb-3">
                    <a href="{$imageUrl}" target="_blank" class="d-block">
                        <img src="{$imageUrl}" 
                             alt="Страница {$pageNumber}" 
                             class="img-fluid rounded border shadow-sm"
                             style="max-height: 600px; object-fit: contain; background: #f8f9fa; padding: 10px;">
                    </a>
                </div>
                
                <div class="image-actions">
                    <a href="{$imageUrl}" target="_blank" class="btn btn-sm btn-outline-primary me-2">
                        <i class="bi bi-zoom-in"></i> Увеличить
                    </a>
                    <a href="{$imageUrl}" download class="btn btn-sm btn-outline-success">
                        <i class="bi bi-download"></i> Скачать скриншот
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="showPageText()">
                        <i class="bi bi-text-paragraph"></i> Показать текст
                    </button>
                </div>
                
                <div class="image-info mt-3 small text-muted">
                    <i class="bi bi-info-circle"></i> Полный скриншот страницы сохранен для визуального просмотра
                </div>
            </div>
        </div>
    </div>
    
    <div class="page-text-section card mt-4" id="pageTextSection" style="display: none;">
        <div class="card-header bg-light">
            <h6 class="mb-0">
                <i class="bi bi-text-paragraph"></i> Текст со страницы
            </h6>
        </div>
        <div class="card-body">
            <div class="content-text" style="max-height: 400px; overflow-y: auto;">
                <p>{$shortText}</p>
            </div>
        </div>
    </div>
</div>

<script>
function showPageText() {
    var section = document.getElementById('pageTextSection');
    if (section.style.display === 'none') {
        section.style.display = 'block';
    } else {
        section.style.display = 'none';
    }
}
</script>
HTML;

            // Обновляем страницу
            $updated = DocumentPage::where('document_id', $documentId)
                ->where('page_number', $pageNumber)
                ->update([
                    'has_images' => true,
                    'content' => $newContent,
                    'updated_at' => now()
                ]);
            
            if ($updated) {
                Log::debug("📄 Контент страницы {$pageNumber} обновлен");
            } else {
                Log::warning("⚠️ Не удалось обновить контент страницы {$pageNumber}");
            }
                
        } catch (Exception $e) {
            Log::error("Ошибка обновления контента: " . $e->getMessage());
        }
    }
    
    /**
     * Генерирует описание
     */
    private function generateDescription($text, $pageNumber)
    {
        $cleanText = trim($text);
        if (empty($cleanText)) {
            return "Скриншот страницы {$pageNumber}";
        }
        
        // Берем первые 100 символов
        $shortText = mb_substr($cleanText, 0, 100);
        return "Страница {$pageNumber}: {$shortText}...";
    }
    
    /**
     * Извлекает текст страницы для описания
     */
    private function extractPageText($pdfFilePath, $pageNumber)
    {
        try {
            if (!file_exists($pdfFilePath)) {
                return "Страница {$pageNumber}";
            }
            
            // Используем Smalot PDF Parser для извлечения текста
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($pdfFilePath);
            $pages = $pdf->getPages();
            
            if (isset($pages[$pageNumber - 1])) {
                $text = $pages[$pageNumber - 1]->getText();
                if (!empty(trim($text))) {
                    return trim($text);
                }
            }
        } catch (Exception $e) {
            Log::debug("Не удалось извлечь текст страницы {$pageNumber}: " . $e->getMessage());
        }
        
        return "Страница {$pageNumber}";
    }

    /**
 * Публичный метод для создания скриншота одной страницы
 */
public function createPageScreenshotDirectly($pdfFilePath, $documentId, $pageNumber)
{
    try {
        Log::info("📸 Создание скриншота страницы {$pageNumber}");
        
        // Извлекаем текст страницы
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($pdfFilePath);
        $pages = $pdf->getPages();
        
        $pageText = '';
        if (isset($pages[$pageNumber - 1])) {
            $pageText = $pages[$pageNumber - 1]->getText();
        }
        
        // Создаем скриншот
        return $this->createPageScreenshot($pdfFilePath, $documentId, $pageNumber, $pageText);
        
    } catch (Exception $e) {
        Log::error("Ошибка создания скриншота страницы {$pageNumber}: " . $e->getMessage());
        return false;
    }
}
}