<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentPage;
use App\Models\DocumentImage;
use App\Services\SimpleImageExtractionService;
use App\Services\ScreenshotService;
use App\Services\ImageProcessingService;
use App\Services\ImageProcessorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Smalot\PdfParser\Parser;
use Exception;

class DocumentProcessingController extends Controller
{
    protected $imageProcessor;
    protected $imageExtractor;
    protected $screenshotService;
   
    
    public function __construct()
    {
        $this->imageProcessor = new ImageProcessingService();
        $this->imageExtractor = new SimpleImageExtractionService();
        $this->screenshotService = new ScreenshotService();
        $this->imageProcessor = new ImageProcessorService();
    }
    
    // =================================================
    // ОСНОВНЫЕ ПУБЛИЧНЫЕ МЕТОДЫ
    // =================================================
    
    /**
     * Полный парсинг документа с разбиением на чанки
     */
    public function parseFull(Request $request, $id)
    {
        try {
            $document = Document::findOrFail($id);
            
            if ($document->status === 'processing') {
                return redirect()->route('admin.documents.processing.advanced', $id)
                    ->with('error', 'Документ уже в обработке.');
            }
            
            // Начинаем обработку
            $document->update([
                'status' => 'processing',
                'processing_started_at' => now(),
                'parsing_progress' => 0,
                'parsing_quality' => 0.0
            ]);
            
            // Сохраняем задачу в кэше
            Cache::put("document_processing_{$id}", [
                'status' => 'processing',
                'progress' => 0,
                'total_pages' => 0,
                'processed_pages' => 0,
                'started_at' => now(),
                'message' => 'Подготовка к обработке...'
            ], now()->addHours(2));
            
            // Для AJAX запроса возвращаем JSON
            if ($request->ajax()) {
                ignore_user_abort(true);
                set_time_limit(3600);
                
                // Запускаем обработку сразу
                $this->startProcessing($document);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Обработка запущена',
                    'task_id' => "doc_{$id}",
                    'check_url' => route('admin.documents.processing.progress', $id)
                ]);
            }
            
            // Для обычного запроса
            $result = $this->startProcessing($document);
            
            if ($result['success']) {
                return redirect()->route('admin.documents.processing.advanced', $id)
                    ->with('success', "✅ Документ успешно распарсен!<br>" . 
                           "📄 Страниц: {$result['pages']}<br>" .
                           "📝 Слов: {$result['words']}<br>" .
                           "🖼️ Изображений: {$result['images']}");
            } else {
                return redirect()->route('admin.documents.processing.advanced', $id)
                    ->with('error', "❌ Ошибка: " . $result['error']);
            }
            
        } catch (\Exception $e) {
            Log::error('Full parse error: ' . $e->getMessage());
            
            if (isset($document)) {
                $document->update([
                    'status' => 'parse_error',
                    'parsing_progress' => 0,
                    'parsing_quality' => 0.0
                ]);
            }
            
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('error', "Ошибка сервера: " . $e->getMessage());
        }
    }
    
    /**
     * Запуск обработки
     */
     private function startProcessing(Document $document)
    {
        try {
            $cacheKey = "document_processing_{$document->id}";
            
            ini_set('memory_limit', '2048M');
            set_time_limit(3600);
            
            $filePath = Storage::disk('local')->path($document->file_path);
            
            if (!file_exists($filePath)) {
                throw new \Exception("PDF файл не найден: {$filePath}");
            }
            
            Log::info("🚀 Начинаем обработку PDF: {$document->title}");
            
            // 1. ПАРСИНГ ТЕКСТА
            $textResult = $this->parsePdfText($document, $filePath);
            
            if (!$textResult['success']) {
                throw new \Exception($textResult['error']);
            }
            
            $pageCount = $textResult['page_count'];
            
            // 2. ИЗВЛЕЧЕНИЕ И ОБРАБОТКА ИЗОБРАЖЕНИЙ
            Log::info("🖼️ Начинаем обработку изображений...");
            
            $imagesResult = $this->processDocumentImages($document->id, $filePath);
            $imagesCount = $imagesResult['images_count'] ?? 0;
            
            // 3. ЗАВЕРШЕНИЕ
            $document->update([
                'status' => 'parsed',
                'is_parsed' => true,
                'parsing_progress' => 100,
                'parsing_quality' => 0.9,
                'word_count' => $textResult['word_count'],
                'content_text' => $textResult['full_text'],
                'total_pages' => $pageCount,
                'parsed_at' => now()
            ]);
            
            Cache::put($cacheKey, [
                'status' => 'completed',
                'progress' => 100,
                'message' => "✅ Обработка завершена! Страниц: {$pageCount}, Изображений: {$imagesCount}"
            ], now()->addHours(1));
            
            Log::info("🎉 Обработка завершена: {$pageCount} страниц, {$imagesCount} изображений");
            
            return [
                'success' => true,
                'pages' => $pageCount,
                'words' => $textResult['word_count'],
                'images' => $imagesCount,
                'message' => "Обработано {$pageCount} страниц"
            ];
            
        } catch (\Exception $e) {
            Log::error("❌ Processing error: " . $e->getMessage());
            
            Cache::put("document_processing_{$document->id}", [
                'status' => 'failed',
                'progress' => 0,
                'error' => $e->getMessage(),
                'message' => "❌ Ошибка: " . $e->getMessage()
            ], now()->addHours(1));
            
            $document->update([
                'status' => 'parse_error',
                'parsing_progress' => 0
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }


     /**
     * Обработка изображений документа
     */
    private function processDocumentImages($documentId, $filePath)
    {
        try {
            $cacheKey = "document_processing_{$documentId}";
            
            // 1. Извлекаем изображения
            $extractedImages = $this->imageExtractor->extractAllImages($filePath);
            
            Log::info("🔍 Найдено изображений в PDF: " . count($extractedImages));
            
            if (empty($extractedImages)) {
                return [
                    'success' => true,
                    'images_count' => 0,
                    'message' => "Изображения не найдены"
                ];
            }
            
            $savedCount = 0;
            $pagesWithImages = [];
            
            // 2. Обрабатываем каждое изображение
            foreach ($extractedImages as $index => $imageData) {
                $pageNumber = $imageData['page'] ?? 1;
                
                // Обновляем прогресс
                $progress = 60 + round((($index + 1) / count($extractedImages)) * 40);
                Cache::put($cacheKey, [
                    'status' => 'processing',
                    'progress' => $progress,
                    'message' => "Обработка изображений: " . ($index + 1) . "/" . count($extractedImages)
                ], now()->addHours(2));
                
                // Создаем директории
                $screenshotsDir = "document_images/screenshots/{$documentId}";
                Storage::disk('public')->makeDirectory($screenshotsDir, 0755, true);
                
                // Создаем скриншот (обрезанный)
                $screenshotName = "screen_page{$pageNumber}_" . ($index + 1) . ".jpg";
                $screenshotPath = Storage::disk('public')->path($screenshotsDir . '/' . $screenshotName);
                
                $screenshotResult = $this->imageProcessor->createCroppedScreenshot(
                    $imageData['content'],
                    $screenshotPath
                );
                
                if ($screenshotResult['success']) {
                    // Сохраняем оригинальное изображение
                    $originalResult = $this->imageProcessor->saveImageToStorage(
                        $imageData['content'],
                        $documentId,
                        $pageNumber,
                        $index + 1
                    );
                    
                    if ($originalResult['success']) {
                        // Сохраняем в базу данных
                        DocumentImage::create([
                            'document_id' => $documentId,
                            'page_number' => $pageNumber,
                            'filename' => $originalResult['filename'],
                            'path' => $originalResult['path'],
                            'url' => $originalResult['url'],
                            'screenshot_path' => $screenshotsDir . '/' . $screenshotName,
                            'screenshot_url' => Storage::url($screenshotsDir . '/' . $screenshotName),
                            'width' => $screenshotResult['width'] ?? null,
                            'height' => $screenshotResult['height'] ?? null,
                            'size' => $imageData['size'] ?? 0,
                            'format' => $imageData['format'] ?? 'jpg',
                            'has_screenshot' => true,
                            'description' => "Изображение на странице {$pageNumber}",
                            'status' => 'active',
                        ]);
                        
                        // Отмечаем страницу как имеющую изображения
                        $pagesWithImages[$pageNumber] = true;
                        
                        $savedCount++;
                        
                        Log::debug("✅ Изображение сохранено для страницы {$pageNumber}");
                    }
                }
            }
            
            // 3. Обновляем страницы с изображениями
            foreach (array_keys($pagesWithImages) as $pageNum) {
                DocumentPage::where('document_id', $documentId)
                    ->where('page_number', $pageNum)
                    ->update([
                        'has_images' => true,
                        'content' => $this->formatPageWithImages($documentId, $pageNum)
                    ]);
            }
            
            Log::info("✅ Обработано изображений: {$savedCount}");
            
            return [
                'success' => true,
                'images_count' => $savedCount,
                'pages_with_images' => count($pagesWithImages),
                'message' => "Обработано {$savedCount} изображений на " . count($pagesWithImages) . " страницах"
            ];
            
        } catch (\Exception $e) {
            Log::error("❌ Image processing error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'images_count' => 0
            ];
        }
    }
    
    /**
     * Форматирует контент страницы с изображениями
     */
    private function formatPageWithImages($documentId, $pageNumber)
    {
        // Получаем изображения для этой страницы
        $images = DocumentImage::where('document_id', $documentId)
            ->where('page_number', $pageNumber)
            ->where('has_screenshot', true)
            ->get();
        
        // Получаем текст страницы
        $page = DocumentPage::where('document_id', $documentId)
            ->where('page_number', $pageNumber)
            ->first();
        
        $text = $page->content_text ?? '';
        
        $html = '<div class="page-with-images">';
        
        if ($images->count() > 0) {
            $html .= '<div class="alert alert-success mb-3">';
            $html .= '<i class="bi bi-check-circle"></i> <strong>Эта страница содержит изображения</strong>';
            $html .= '<span class="badge bg-secondary ms-2">' . $images->count() . ' изображений</span>';
            $html .= '</div>';
            
            foreach ($images as $image) {
                $html .= $this->formatImageBlock($image);
            }
        }
        
        if (!empty(trim($text))) {
            $html .= '<div class="page-text-content mt-4">';
            $html .= '<h5><i class="bi bi-text-paragraph"></i> Текст со страницы:</h5>';
            $html .= '<div class="bg-light p-3 rounded">';
            $html .= '<p>' . nl2br(htmlspecialchars($text)) . '</p>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }


     /**
     * Форматирует блок изображения
     */
    private function formatImageBlock($image)
    {
        $html = <<<HTML
<div class="image-block mb-4">
    <div class="text-center">
        <div class="image-wrapper" style="max-width: 800px; margin: 0 auto;">
            <a href="{$image->screenshot_url}" target="_blank" class="d-block mb-2">
                <img src="{$image->screenshot_url}" 
                     alt="Изображение" 
                     class="img-fluid rounded border shadow"
                     style="max-height: 500px; object-fit: contain;">
            </a>
            <div class="image-info small text-muted">
                <i class="bi bi-aspect-ratio"></i> {$image->width}x{$image->height}px | 
                <i class="bi bi-zoom-in"></i> Кликните для увеличения
            </div>
        </div>
    </div>
</div>
HTML;
        
        return $html;
    }
    
    /**
     * Форматирует HTML контент
     */
    private function formatHtmlContent($text)
    {
        if (empty(trim($text))) {
            return '<p class="text-muted"><em>Текст отсутствует</em></p>';
        }
        
        $lines = explode("\n", $text);
        $html = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $html .= '<p>' . htmlspecialchars($line) . '</p>';
            }
        }
        
        return $html;
    }
    
    /**
     * Извлекает заголовок раздела
     */
    private function extractSectionTitle($text)
    {
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $line = trim($line);
            if (mb_strlen($line) < 100 && !empty($line) && preg_match('/^[А-ЯA-Z]/u', $line)) {
                return $line;
            }
        }
        return '';
    }
    
    /**
     * Рассчитывает качество парсинга
     */
   
    

private function parsePdfText(Document $document, $filePath)
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $pages = $pdf->getPages();
            
            $pageCount = count($pages);
            $totalWords = 0;
            $fullText = '';
            
            Cache::put("document_processing_{$document->id}", [
                'status' => 'processing',
                'progress' => 10,
                'total_pages' => $pageCount,
                'processed_pages' => 0,
                'message' => "Парсинг текста: 0/{$pageCount} страниц..."
            ], now()->addHours(2));
            
            foreach ($pages as $index => $page) {
                $pageNumber = $index + 1;
                
                // Обновляем прогресс
                if ($pageNumber % 5 === 0 || $pageNumber === $pageCount) {
                    $progress = 10 + round(($pageNumber / $pageCount) * 50);
                    Cache::put("document_processing_{$document->id}", [
                        'status' => 'processing',
                        'progress' => $progress,
                        'total_pages' => $pageCount,
                        'processed_pages' => $pageNumber,
                        'message' => "Парсинг текста: {$pageNumber}/{$pageCount} страниц..."
                    ], now()->addHours(2));
                    
                    $document->update(['parsing_progress' => $progress]);
                }
                
                // Извлекаем текст
                $text = $page->getText();
                $wordCount = str_word_count($text);
                
                // Сохраняем страницу
                DocumentPage::updateOrCreate(
                    [
                        'document_id' => $document->id,
                        'page_number' => $pageNumber
                    ],
                    [
                        'content' => $this->formatHtmlContent($text),
                        'content_text' => $text,
                        'word_count' => $wordCount,
                        'character_count' => mb_strlen($text),
                        'section_title' => $this->extractSectionTitle($text),
                        'parsing_quality' => $this->calculateParsingQuality($text),
                        'status' => 'parsed',
                        'updated_at' => now()
                    ]
                );
                
                $totalWords += $wordCount;
                $fullText .= $text . "\n\n";
                
                Log::debug("📄 Страница {$pageNumber} обработана: {$wordCount} слов");
            }
            
            return [
                'success' => true,
                'page_count' => $pageCount,
                'word_count' => $totalWords,
                'full_text' => $fullText
            ];
            
        } catch (\Exception $e) {
            Log::error("❌ Text parsing error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Извлечение и обработка изображений
     */
    /**
 * Извлечение и обработка изображений с созданием скриншотов страниц
 */
private function extractAndProcessImages($documentId, $filePath)
{
    try {
        $cacheKey = "document_processing_{$documentId}";
        
        Log::info("🚀 Начинаем извлечение изображений для документа: {$documentId}");
        
        // Используем сервис для извлечения изображений
        $extractedImages = $this->imageExtractor->extractAllImages($filePath);
        
        if (empty($extractedImages)) {
            Log::warning("📭 Изображения не найдены в PDF файле");
            return [
                'success' => true,
                'images_count' => 0,
                'message' => "Изображения не найдены в PDF файле"
            ];
        }
        
        Log::info("🔍 Найдено изображений: " . count($extractedImages));
        
        // Группируем изображения по страницам
        $imagesByPage = [];
        foreach ($extractedImages as $imageData) {
            $pageNumber = $imageData['page'] ?? 1;
            if (!isset($imagesByPage[$pageNumber])) {
                $imagesByPage[$pageNumber] = [];
            }
            $imagesByPage[$pageNumber][] = $imageData;
        }
        
        Log::info("📄 Страниц с изображениями: " . count($imagesByPage));
        
        $savedCount = 0;
        $pagesWithScreenshots = 0;
        
        // Для каждой страницы создаем скриншот
        foreach ($imagesByPage as $pageNumber => $pageImages) {
            // Обновляем прогресс
            $progress = 60 + round((($pageNumber) / count($imagesByPage)) * 40);
            Cache::put($cacheKey, [
                'status' => 'processing',
                'progress' => $progress,
                'message' => "Создание скриншотов: страница {$pageNumber}/" . count($imagesByPage)
            ], now()->addHours(2));
            
            // Создаем скриншот страницы (обрезанный)
            $screenshotResult = $this->createPageScreenshot($filePath, $pageNumber, $documentId);
            
            if ($screenshotResult['success']) {
                // Обновляем страницу в базе данных
                DocumentPage::where('document_id', $documentId)
                    ->where('page_number', $pageNumber)
                    ->update([
                        'has_images' => true,
                        'content' => $this->formatPageWithScreenshot(
                            $screenshotResult['url'],
                            $screenshotResult['width'],
                            $screenshotResult['height']
                        )
                    ]);
                
                $pagesWithScreenshots++;
                Log::debug("✅ Создан скриншот для страницы {$pageNumber}");
            }
            
            // Также сохраняем отдельные изображения для справки
            foreach ($pageImages as $index => $imageData) {
                $this->saveImageToDatabase($documentId, $pageNumber, $imageData, $index);
                $savedCount++;
            }
        }
        
        Log::info("🎉 Обработка завершена: {$savedCount} изображений, {$pagesWithScreenshots} скриншотов страниц");
        
        return [
            'success' => true,
            'images_count' => $savedCount,
            'pages_with_screenshots' => $pagesWithScreenshots,
            'message' => "Обработано {$savedCount} изображений, создано {$pagesWithScreenshots} скриншотов страниц"
        ];
        
    } catch (\Exception $e) {
        Log::error("❌ Image processing error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'images_count' => 0
        ];
    }
}

/**
 * Создает обрезанный скриншот страницы PDF
 */
private function createPageScreenshot($pdfFilePath, $pageNumber, $documentId)
{
    try {
        if (!file_exists($pdfFilePath)) {
            throw new \Exception("PDF файл не найден: {$pdfFilePath}");
        }
        
        // Создаем директорию для скриншотов страниц
        $screenshotsDir = "document_pages_screenshots/{$documentId}";
        Storage::disk('public')->makeDirectory($screenshotsDir, 0755, true);
        
        // Генерируем имя файла
        $filename = "page_{$pageNumber}_cropped.jpg";
        $screenshotPath = $screenshotsDir . '/' . $filename;
        $fullDestPath = Storage::disk('public')->path($screenshotPath);
        
        // 1. Сначала создаем временный скриншот всей страницы
        $tempImagePath = tempnam(sys_get_temp_dir(), 'pdf_page_') . '.jpg';
        
        // Используем Imagick для создания скриншота страницы
        $imagick = new \Imagick();
        $imagick->setResolution(150, 150); // Высокое разрешение для качественного скриншота
        $imagick->readImage($pdfFilePath . '[' . ($pageNumber - 1) . ']'); // Нумерация с 0
        $imagick->setImageFormat('jpg');
        $imagick->setImageCompression(\Imagick::COMPRESSION_JPEG);
        $imagick->setImageCompressionQuality(90);
        $imagick->writeImage($tempImagePath);
        $imagick->clear();
        $imagick->destroy();
        
        // 2. Обрезаем белые поля и ненужный текст
        $croppedImage = $this->cropPageScreenshot($tempImagePath);
        
        if (!$croppedImage) {
            // Если не удалось обрезать, используем оригинальный скриншот
            copy($tempImagePath, $fullDestPath);
        } else {
            // Сохраняем обрезанное изображение
            imagejpeg($croppedImage, $fullDestPath, 85);
            imagedestroy($croppedImage);
        }
        
        // Удаляем временный файл
        if (file_exists($tempImagePath)) {
            unlink($tempImagePath);
        }
        
        // Проверяем что файл создан
        if (!file_exists($fullDestPath)) {
            throw new \Exception("Не удалось создать скриншот страницы");
        }
        
        // Получаем размеры
        $imageInfo = getimagesize($fullDestPath);
        if (!$imageInfo) {
            throw new \Exception("Не удалось получить информацию о скриншоте");
        }
        
        list($width, $height) = $imageInfo;
        $fileSize = filesize($fullDestPath);
        
        // Если изображение слишком большое, ресайзим
        if ($width > 1200 || $height > 800) {
            $this->resizeScreenshot($fullDestPath, 1200, 800);
            
            // Обновляем размеры после ресайза
            $imageInfo = getimagesize($fullDestPath);
            list($width, $height) = $imageInfo;
            $fileSize = filesize($fullDestPath);
        }
        
        Log::info("✅ Создан скриншот страницы {$pageNumber}: {$width}x{$height}, {$fileSize} байт");
        
        return [
            'success' => true,
            'path' => $screenshotPath,
            'url' => Storage::url($screenshotPath),
            'width' => $width,
            'height' => $height,
            'size' => $fileSize
        ];
        
    } catch (\Exception $e) {
        Log::error("❌ Ошибка создания скриншота страницы {$pageNumber}: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}


/**
 * Обрезает скриншот страницы, удаляя белые поля и текст
 */
private function cropPageScreenshot($imagePath)
{
    try {
        $imageInfo = getimagesize($imagePath);
        if (!$imagePath) return false;
        
        list($width, $height, $type) = $imageInfo;
        
        // Загружаем изображение
        $sourceImage = imagecreatefromjpeg($imagePath);
        if (!$sourceImage) return false;
        
        // 1. Находим области с контентом (не белые)
        $threshold = 240; // Порог белого цвета
        $top = $height;
        $bottom = 0;
        $left = $width;
        $right = 0;
        
        // Сканируем с шагом для скорости
        $step = 10;
        
        for ($y = 0; $y < $height; $y += $step) {
            for ($x = 0; $x < $width; $x += $step) {
                $color = imagecolorat($sourceImage, $x, $y);
                $rgb = imagecolorsforindex($sourceImage, $color);
                
                // Ищем не-белые пиксели (контент)
                if ($rgb['red'] < $threshold || $rgb['green'] < $threshold || $rgb['blue'] < $threshold) {
                    if ($y < $top) $top = $y;
                    if ($y > $bottom) $bottom = $y;
                    if ($x < $left) $left = $x;
                    if ($x > $right) $right = $x;
                }
            }
        }
        
        // Если не нашли контент, возвращаем false
        if ($top >= $bottom || $left >= $right) {
            imagedestroy($sourceImage);
            return false;
        }
        
        // 2. Определяем, что это - текст или изображение
        // Изображения обычно имеют большие непрерывные области цвета
        // Текст имеет много мелких деталей
        
        $contentWidth = $right - $left;
        $contentHeight = $bottom - $top;
        
        // Если область слишком маленькая или слишком большая (весь текст), не обрезаем
        if ($contentWidth < 100 || $contentHeight < 100 || 
            $contentWidth > $width * 0.8 || $contentHeight > $height * 0.8) {
            imagedestroy($sourceImage);
            return false;
        }
        
        // 3. Добавляем небольшие отступы
        $padding = 20;
        $top = max(0, $top - $padding);
        $bottom = min($height - 1, $bottom + $padding);
        $left = max(0, $left - $padding);
        $right = min($width - 1, $right + $padding);
        
        $cropWidth = $right - $left + 1;
        $cropHeight = $bottom - $top + 1;
        
        // 4. Создаем обрезанное изображение
        $croppedImage = imagecreatetruecolor($cropWidth, $cropHeight);
        $white = imagecolorallocate($croppedImage, 255, 255, 255);
        imagefill($croppedImage, 0, 0, $white);
        
        imagecopy($croppedImage, $sourceImage, 0, 0, $left, $top, $cropWidth, $cropHeight);
        
        imagedestroy($sourceImage);
        
        Log::debug("✂️ Обрезано: {$width}x{$height} -> {$cropWidth}x{$cropHeight}");
        
        return $croppedImage;
        
    } catch (\Exception $e) {
        Log::error("Ошибка обрезки скриншота: " . $e->getMessage());
        return false;
    }
}

/**
 * Изменяет размер скриншота
 */
private function resizeScreenshot($imagePath, $maxWidth, $maxHeight)
{
    try {
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) return false;
        
        list($width, $height, $type) = $imageInfo;
        
        // Рассчитываем новые размеры
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);
        
        // Загружаем изображение
        $sourceImage = imagecreatefromjpeg($imagePath);
        if (!$sourceImage) return false;
        
        // Создаем новое изображение
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        $white = imagecolorallocate($resizedImage, 255, 255, 255);
        imagefill($resizedImage, 0, 0, $white);
        
        // Ресайзим
        imagecopyresampled(
            $resizedImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight, $width, $height
        );
        
        // Сохраняем
        imagejpeg($resizedImage, $imagePath, 85);
        
        // Очищаем память
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);
        
        Log::debug("📏 Ресайз скриншота: {$width}x{$height} -> {$newWidth}x{$newHeight}");
        
        return true;
        
    } catch (\Exception $e) {
        Log::error("Ошибка ресайза скриншота: " . $e->getMessage());
        return false;
    }
}


/**
 * Сохраняет изображение в базу данных
 */
private function saveImageToDatabase($documentId, $pageNumber, $imageData, $index)
{
    try {
        // Создаем директории
        $imagesDir = "document_images/{$documentId}";
        Storage::disk('public')->makeDirectory($imagesDir, 0755, true);
        
        // Генерируем уникальное имя файла
        $filename = "img_page{$pageNumber}_{$index}.{$imageData['format']}";
        $imagePath = $imagesDir . '/' . $filename;
        
        // Сохраняем изображение
        if (!empty($imageData['content'])) {
            Storage::disk('public')->put($imagePath, $imageData['content']);
        }
        
        // Получаем информацию об изображении
        $imageInfo = $this->getImageInfo($imagePath);
        $imageSize = Storage::disk('public')->size($imagePath);
        
        // Сохраняем в базу
        DocumentImage::updateOrCreate(
            [
                'document_id' => $documentId,
                'page_number' => $pageNumber,
                'filename' => $filename
            ],
            [
                'path' => $imagePath,
                'url' => Storage::url($imagePath),
                'width' => $imageInfo['width'] ?? null,
                'height' => $imageInfo['height'] ?? null,
                'size' => $imageSize,
                'format' => strtoupper($imageData['format'] ?? 'jpg'),
                'description' => "Изображение {$index} на странице {$pageNumber}",
                'status' => 'active',
                'updated_at' => now()
            ]
        );
        
    } catch (\Exception $e) {
        Log::warning("Ошибка сохранения изображения: " . $e->getMessage());
    }
}



/**
 * Форматирует контент страницы со скриншотом
 */
private function formatPageWithScreenshot($screenshotUrl, $width, $height)
{
    $html = <<<HTML
<div class="page-with-screenshot">
    <div class="screenshot-container text-center mb-4">
        <h5><i class="bi bi-image"></i> Схема/Изображение со страницы</h5>
        <div class="screenshot-wrapper" style="max-width: {$width}px; margin: 0 auto;">
            <a href="{$screenshotUrl}" target="_blank" class="screenshot-link">
                <img src="{$screenshotUrl}" 
                     alt="Скриншот страницы" 
                     class="img-fluid rounded border shadow-sm"
                     style="max-height: 600px; object-fit: contain;">
            </a>
            <div class="screenshot-info mt-2 small text-muted">
                <i class="bi bi-aspect-ratio"></i> {$width}×{$height}px
                <span class="ms-3"><i class="bi bi-zoom-in"></i> Кликните для увеличения</span>
            </div>
        </div>
    </div>
    
    <div class="page-content mt-4">
        <h5><i class="bi bi-text-paragraph"></i> Текстовое содержание</h5>
        <div class="content-text bg-light p-3 rounded">
            <p><em>Текст со страницы отображается здесь...</em></p>
        </div>
    </div>
</div>
HTML;

    return $html;
}


/**
 * Создает обрезанный скриншот без белого фона
 */
private function createTrimmedScreenshot($sourcePath, $destinationPath, $maxWidth = 800, $maxHeight = 600)
{
    try {
        $fullSourcePath = Storage::disk('public')->path($sourcePath);
        $fullDestPath = Storage::disk('public')->path($destinationPath);
        
        if (!file_exists($fullSourcePath)) {
            Log::error("❌ Файл не найден: {$fullSourcePath}");
            return false;
        }
        
        // Создаем директорию если не существует
        $dir = dirname($fullDestPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Используем GD для обработки
        $imageInfo = getimagesize($fullSourcePath);
        if (!$imageInfo) {
            return false;
        }
        
        list($width, $height, $type) = $imageInfo;
        
        // Загружаем изображение
        $sourceImage = $this->createImageResource($fullSourcePath, $type);
        if (!$sourceImage) {
            return false;
        }
        
        // Автоматическая обрезка (trim)
        $croppedImage = $this->autoTrim($sourceImage, $width, $height);
        if (!$croppedImage) {
            $croppedImage = $sourceImage;
        }
        
        // Получаем размеры обрезанного изображения
        $cropWidth = imagesx($croppedImage);
        $cropHeight = imagesy($croppedImage);
        
        // Ресайз если нужно
        if ($cropWidth > $maxWidth || $cropHeight > $maxHeight) {
            $ratio = min($maxWidth / $cropWidth, $maxHeight / $cropHeight);
            $newWidth = (int)($cropWidth * $ratio);
            $newHeight = (int)($cropHeight * $ratio);
            
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Сохраняем прозрачность для PNG
            if ($type == IMAGETYPE_PNG) {
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
                imagefill($resizedImage, 0, 0, $transparent);
            } else {
                $white = imagecolorallocate($resizedImage, 255, 255, 255);
                imagefill($resizedImage, 0, 0, $white);
            }
            
            imagecopyresampled(
                $resizedImage, $croppedImage,
                0, 0, 0, 0,
                $newWidth, $newHeight, $cropWidth, $cropHeight
            );
            
            $finalImage = $resizedImage;
        } else {
            $finalImage = $croppedImage;
        }
        
        // Сохраняем как JPEG
        $result = imagejpeg($finalImage, $fullDestPath, 85);
        
        // Очистка памяти
        imagedestroy($sourceImage);
        if ($croppedImage !== $sourceImage) {
            imagedestroy($croppedImage);
        }
        if (isset($finalImage) && $finalImage !== $croppedImage) {
            imagedestroy($finalImage);
        }
        
        if ($result) {
            Log::debug("✅ Создан обрезанный скриншот: {$destinationPath}");
        }
        
        return $result;
        
    } catch (\Exception $e) {
        Log::error("❌ Ошибка создания скриншота: " . $e->getMessage());
        return false;
    }
}
    

/**
 * Автоматическая обрезка белых полей
 */
private function autoTrim($image, $width, $height)
{
    try {
        $left = $width;
        $right = 0;
        $top = $height;
        $bottom = 0;
        
        $threshold = 245; // Более высокий порог для лучшей обрезки
        
        // Сканируем все пиксели для точной обрезки
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                
                // Если пиксель НЕ белый
                if ($r < $threshold || $g < $threshold || $b < $threshold) {
                    if ($x < $left) $left = $x;
                    if ($x > $right) $right = $x;
                    if ($y < $top) $top = $y;
                    if ($y > $bottom) $bottom = $y;
                }
            }
        }
        
        // Если не найдено не-белых пикселей
        if ($left > $right || $top > $bottom) {
            return $image;
        }
        
        // Добавляем небольшие отступы
        $left = max(0, $left - 5);
        $right = min($width - 1, $right + 5);
        $top = max(0, $top - 5);
        $bottom = min($height - 1, $bottom + 5);
        
        $cropWidth = $right - $left + 1;
        $cropHeight = $bottom - $top + 1;
        
        // Если обрезка минимальна, возвращаем оригинал
        if ($cropWidth > $width * 0.95 && $cropHeight > $height * 0.95) {
            return $image;
        }
        
        // Создаем обрезанное изображение
        $croppedImage = imagecreatetruecolor($cropWidth, $cropHeight);
        
        // Сохраняем прозрачность для PNG
        $imageType = imagesx($image) ? IMAGETYPE_JPEG : IMAGETYPE_PNG;
        if ($imageType == IMAGETYPE_PNG) {
            imagealphablending($croppedImage, false);
            imagesavealpha($croppedImage, true);
            $transparent = imagecolorallocatealpha($croppedImage, 0, 0, 0, 127);
            imagefill($croppedImage, 0, 0, $transparent);
        }
        
        imagecopy($croppedImage, $image, 0, 0, $left, $top, $cropWidth, $cropHeight);
        
        return $croppedImage;
        
    } catch (\Exception $e) {
        Log::error("Ошибка автобрезки: " . $e->getMessage());
        return $image;
    }
}

/**
 * Создает обычный скриншот (без обрезки)
 */
private function createRegularScreenshot($sourcePath, $destinationPath, $maxWidth = 800, $maxHeight = 600)
{
    try {
        $fullSourcePath = Storage::disk('public')->path($sourcePath);
        $fullDestPath = Storage::disk('public')->path($destinationPath);
        
        if (!file_exists($fullSourcePath)) {
            return false;
        }
        
        $imageInfo = getimagesize($fullSourcePath);
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
        
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Заполняем фон белым
        $white = imagecolorallocate($resizedImage, 255, 255, 255);
        imagefill($resizedImage, 0, 0, $white);
        
        // Ресайз
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
        Log::error("Ошибка создания обычного скриншота: " . $e->getMessage());
        return false;
    }
}
    // =================================================
    // МЕТОДЫ ОБРАБОТКИ СКРИНШОТОВ
    // =================================================
    
    /**
     * Создает оптимизированный скриншот с обрезкой белого фона
     */
    private function createOptimizedScreenshot($sourcePath, $destinationPath, $maxWidth = 800, $maxHeight = 600)
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
        $sourceImage = null;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = @imagecreatefromjpeg($fullSourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = @imagecreatefrompng($fullSourcePath);
                if ($sourceImage) {
                    imagealphablending($sourceImage, false);
                    imagesavealpha($sourceImage, true);
                }
                break;
            case IMAGETYPE_GIF:
                $sourceImage = @imagecreatefromgif($fullSourcePath);
                break;
            default:
                // Пробуем загрузить как строку
                $content = @file_get_contents($fullSourcePath);
                if ($content) {
                    $sourceImage = @imagecreatefromstring($content);
                }
                break;
        }
        
        if (!$sourceImage) {
            Log::error("❌ Не удалось загрузить изображение: {$fullSourcePath}");
            return false;
        }
        
        // Пробуем обрезать белые поля
        $croppedImage = $this->trimWhiteBorders($sourceImage, $srcWidth, $srcHeight);
        
        // Получаем размеры обрезанного изображения
        $cropWidth = imagesx($croppedImage);
        $cropHeight = imagesy($croppedImage);
        
        // Рассчитываем новые размеры для ресайза
        $ratio = min($maxWidth / $cropWidth, $maxHeight / $cropHeight);
        $newWidth = (int)($cropWidth * $ratio);
        $newHeight = (int)($cropHeight * $ratio);
        
        // Создаем новое изображение
        $finalImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Сохраняем прозрачность для PNG
        if ($type == IMAGETYPE_PNG) {
            imagealphablending($finalImage, false);
            imagesavealpha($finalImage, true);
            $transparent = imagecolorallocatealpha($finalImage, 0, 0, 0, 127);
            imagefill($finalImage, 0, 0, $transparent);
        } else {
            // Для JPEG делаем белый фон
            $white = imagecolorallocate($finalImage, 255, 255, 255);
            imagefill($finalImage, 0, 0, $white);
        }
        
        // Ресайзим изображение
        imagecopyresampled(
            $finalImage, $croppedImage,
            0, 0, 0, 0,
            $newWidth, $newHeight, $cropWidth, $cropHeight
        );
        
        // Сохраняем как JPEG
        $quality = 85; // Качество 85%
        $result = imagejpeg($finalImage, $fullDestPath, $quality);
        
        // Очищаем память
        imagedestroy($sourceImage);
        imagedestroy($croppedImage);
        imagedestroy($finalImage);
        
        if ($result && file_exists($fullDestPath)) {
            $originalSize = filesize($fullSourcePath);
            $finalSize = filesize($fullDestPath);
            $savedPercent = $originalSize > 0 ? round(($originalSize - $finalSize) / $originalSize * 100, 2) : 0;
            
            Log::debug("✅ Скриншот создан: {$destinationPath}, сжатие: {$savedPercent}%");
            return true;
        }
        
        return false;
        
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
     * Получает информацию об изображении
     */
  private function getImageInfo($path)
{
    try {
        $fullPath = Storage::disk('public')->path($path);
        
        if (!file_exists($fullPath)) {
            return ['width' => null, 'height' => null, 'mime' => null];
        }
        
        $imageInfo = @getimagesize($fullPath);
        
        if ($imageInfo) {
            return [
                'width' => $imageInfo[0],
                'height' => $imageInfo[1],
                'mime' => $imageInfo['mime']
            ];
        }
        
        // Пробуем определить по содержимому
        $content = @file_get_contents($fullPath, false, null, 0, 100);
        
        if (strpos($content, "\xFF\xD8") === 0) {
            return ['width' => null, 'height' => null, 'mime' => 'image/jpeg'];
        }
        if (strpos($content, "\x89PNG") === 0) {
            return ['width' => null, 'height' => null, 'mime' => 'image/png'];
        }
        
        return ['width' => null, 'height' => null, 'mime' => null];
        
    } catch (\Exception $e) {
        Log::warning("Error getting image info: " . $e->getMessage());
        return ['width' => null, 'height' => null, 'mime' => null];
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
    
    // =================================================
    // ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
    // =================================================
    
    /**
     * Форматирует HTML контент
     */
    
    
    /**
     * Извлекает заголовок раздела
     */
   
    
    /**
     * Рассчитывает качество парсинга
     */
    private function calculateParsingQuality($text)
    {
        $length = mb_strlen($text);
        if ($length === 0) return 0.0;
        
        $quality = 0.5;
        if (preg_match('/[.!?]/u', $text)) $quality += 0.2;
        if (preg_match('/[А-ЯA-Z]/u', $text)) $quality += 0.2;
        if ($length > 100) $quality += 0.1;
        
        return min(1.0, $quality);
    }
    
    /**
     * Получает прогресс обработки (JSON для AJAX)
     */
    public function getProcessingProgress(Request $request, $id)
    {
        try {
            $cacheKey = "document_processing_{$id}";
            $progressData = Cache::get($cacheKey, [
                'status' => 'not_started',
                'progress' => 0,
                'message' => 'Обработка не начата'
            ]);
            
            // Если нет данных в кэше, проверяем статус документа
            if ($progressData['status'] === 'not_started') {
                $document = Document::find($id);
                if ($document) {
                    $progressData = [
                        'status' => $document->status,
                        'progress' => $document->parsing_progress ?? 0,
                        'message' => $this->getProgressMessage($document)
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'status' => $progressData['status'],
                'progress' => $progressData['progress'],
                'message' => $progressData['message'] ?? '',
                'timestamp' => now()->toDateTimeString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get progress error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Получает сообщение о прогрессе
     */
    private function getProgressMessage($document)
    {
        $cacheKey = "document_processing_{$document->id}";
        $cacheData = Cache::get($cacheKey);
        
        if ($cacheData) {
            return $cacheData['message'] ?? 'Обработка...';
        }
        
        switch ($document->status) {
            case 'processing':
                return "Обработка документа... " . number_format($document->parsing_progress ?? 0, 2) . "%";
            case 'parsed':
                return "✅ Документ успешно распарсен";
            case 'preview_created':
                return "Создан предпросмотр документа";
            case 'parse_error':
                return "❌ Ошибка при обработке документа";
            default:
                return "Готов к обработке";
        }
    }
    
    // =================================================
    // ДОПОЛНИТЕЛЬНЫЕ МЕТОДЫ (оставьте ваши существующие)
    // =================================================
    
    /**
     * Список документов для обработки
     */
    public function index()
    {
        $documents = Document::with(['carModel.brand', 'category'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return view('admin.documents.index', compact('documents'));
    }
    
    /**
     * Расширенная обработка документа
     */
    public function advancedProcessing($id)
    {
        $document = Document::with(['carModel.brand', 'category'])
            ->findOrFail($id);
        
        $previewPages = DocumentPage::where('document_id', $id)
            ->where('is_preview', true)
            ->with('images')
            ->orderBy('page_number')
            ->get();
        
        $allPages = DocumentPage::where('document_id', $id)
            ->where('is_preview', false)
            ->get();
        
        $images = DocumentImage::where('document_id', $id)
            ->get();
        
        $stats = $this->calculateStats($document, $allPages, $images);
        
        return view('admin.documents.processing.processing_advanced', compact(
            'document', 
            'previewPages', 
            'stats'
        ));
    }
    
    /**
     * Рассчитывает статистику
     */
    private function calculateStats($document, $pages, $images)
    {
        $pagesCount = $pages->count();
        $imagesCount = $images->count();
        
        $wordsCount = $pages->sum('word_count');
        $charactersCount = $pages->sum('character_count');
        
        $fileSize = 'N/A';
        if ($document->file_path) {
            try {
                $size = Storage::disk('local')->size($document->file_path);
                $fileSize = $this->formatFileSize($size);
            } catch (\Exception $e) {
                // Игнорируем ошибку
            }
        }
        
        return [
            'pages_count' => $pagesCount,
            'total_pages' => $document->total_pages ?? $pagesCount,
            'words_count' => $wordsCount,
            'characters_count' => $charactersCount,
            'images_count' => $imagesCount,
            'parsing_quality' => $document->parsing_quality,
            'file_size' => $fileSize
        ];
    }
    
    /**
     * Форматирует размер файла
     */
    private function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            return $bytes . ' bytes';
        } elseif ($bytes == 1) {
            return '1 byte';
        } else {
            return '0 bytes';
        }
    }

    /**
 * Список всех страниц документа
 */
public function pagesList(Request $request, $id)
{
    $document = Document::with(['carModel.brand', 'category'])
        ->findOrFail($id);
    
    $query = DocumentPage::where('document_id', $id)
        ->orderBy('page_number');
    
    // Поиск по тексту
    if ($request->has('search') && $request->search) {
        $searchTerm = $request->search;
        $query->where(function($q) use ($searchTerm) {
            $q->where('content_text', 'like', "%{$searchTerm}%")
              ->orWhere('section_title', 'like', "%{$searchTerm}%");
        });
    }
    
    // Фильтрация по наличию изображений
    if ($request->has('has_images')) {
        $query->where('has_images', $request->has_images == 'yes');
    }
    
    $pages = $query->paginate(20);
    
    // Получаем информацию об изображениях по страницам для фильтра
    $imagesByPage = DocumentImage::where('document_id', $id)
        ->select('page_number')
        ->selectRaw('COUNT(*) as count')
        ->selectRaw('GROUP_CONCAT(id) as image_ids')
        ->groupBy('page_number')
        ->orderBy('page_number')
        ->get()
        ->mapWithKeys(function($item) {
            return [$item->page_number => [
                'count' => $item->count,
                'image_ids' => explode(',', $item->image_ids)
            ]];
        });
    
    return view('admin.documents.processing.pages_list', compact(
        'document', 
        'pages',
        'imagesByPage'
    ));
}
    
    /**
     * Просмотр конкретной страницы
     */
    public function showPage(Request $request, $id, $pageId)
    {
        $document = Document::with(['carModel.brand', 'category'])
            ->findOrFail($id);
        
        $page = DocumentPage::where('document_id', $id)
            ->where('id', $pageId)
            ->with('images')
            ->firstOrFail();
        
        $images = $page->images ?? collect();
        
        // Проверяем скриншоты
        foreach ($images as $image) {
            if ($image->screenshot_path) {
                $image->has_screenshot = Storage::disk('public')->exists($image->screenshot_path);
                if ($image->has_screenshot) {
                    $image->screenshot_url = Storage::url($image->screenshot_path);
                    $image->screenshot_size = Storage::disk('public')->size($image->screenshot_path);
                }
            }
        }
        
        return view('admin.documents.processing.page_show', compact('document', 'page', 'images'));
    }

     /**
 * Создать предпросмотр
 */
public function createPreview(Request $request, $id)
{
    try {
        $document = Document::findOrFail($id);
        
        if ($document->status === 'processing') {
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('error', 'Документ уже в обработке.');
        }
        
        $document->update([
            'status' => 'processing',
            'processing_started_at' => now(),
            'parsing_progress' => 0,
            'parsing_quality' => 0.0
        ]);
        
        $filePath = Storage::disk('local')->path($document->file_path);
        
        // Парсим первые 5 страниц
        $result = $this->parsePdfPreview($document, $filePath, 5);
        
        if ($result['success']) {
            $document->update([
                'status' => 'preview_created',
                'parsing_progress' => 100,
                'parsing_quality' => $result['parsing_quality'],
                'total_pages' => $result['total_pages'],
                'word_count' => $result['word_count'],
                'updated_at' => now()
            ]);
            
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('success', "Предпросмотр создан: {$result['processed_pages']} страниц");
        } else {
            $document->update([
                'status' => 'parse_error',
                'parsing_progress' => 0,
                'parsing_quality' => 0.0
            ]);
            
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('error', "Ошибка: " . $result['error']);
        }
        
    } catch (\Exception $e) {
        Log::error('Preview error: ' . $e->getMessage());
        
        if (isset($document)) {
            $document->update([
                'status' => 'parse_error',
                'parsing_progress' => 0,
                'parsing_quality' => 0.0
            ]);
        }
        
        return redirect()->route('admin.documents.processing.advanced', $id)
            ->with('error', "Ошибка: " . $e->getMessage());
    }
}

/**
 * Парсинг предпросмотра PDF
 */
private function parsePdfPreview($document, $filePath, $maxPages = 5)
{
    try {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $pages = $pdf->getPages();
        $totalPages = count($pages);
        
        $pages = array_slice($pages, 0, min($maxPages, $totalPages));
        
        $totalWords = 0;
        $totalQuality = 0;
        
        foreach ($pages as $index => $page) {
            $pageNumber = $index + 1;
            $text = $page->getText();
            $wordCount = str_word_count($text);
            
            // Используем updateOrCreate для предпросмотра
            DocumentPage::updateOrCreate(
                [
                    'document_id' => $document->id,
                    'page_number' => $pageNumber
                ],
                [
                    'content' => $this->formatHtmlContent($text),
                    'content_text' => $text,
                    'word_count' => $wordCount,
                    'character_count' => mb_strlen($text),
                    'is_preview' => true,
                    'section_title' => $this->extractSectionTitle($text),
                    'parsing_quality' => $this->calculateParsingQuality($text),
                    'status' => 'preview',
                    'updated_at' => now()
                ]
            );
            
            $totalWords += $wordCount;
            $totalQuality += $this->calculateParsingQuality($text);
        }
        
        $avgQuality = count($pages) > 0 ? ($totalQuality / count($pages)) : 0.7;
        
        return [
            'success' => true,
            'processed_pages' => count($pages),
            'total_pages' => $totalPages,
            'word_count' => $totalWords,
            'parsing_quality' => $avgQuality
        ];
        
    } catch (\Exception $e) {
        Log::error('PDF preview error: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Просмотр всех изображений документа
 */
public function viewImages(Request $request, $id)
{
    $document = Document::findOrFail($id);
    
    $query = DocumentImage::where('document_id', $id)
        ->orderBy('page_number')
        ->orderBy('created_at');
    
    // Фильтрация по странице
    if ($request->has('page')) {
        $query->where('page_number', $request->page);
    }
    
    // Фильтрация по наличию скриншотов
    if ($request->has('has_screenshot')) {
        $query->where('has_screenshot', $request->has_screenshot);
    }
    
    $images = $query->paginate(24);
    
    // Группировка по страницам для статистики
    $imagesByPage = DocumentImage::where('document_id', $id)
        ->select('page_number')
        ->selectRaw('COUNT(*) as count')
        ->selectRaw('SUM(CASE WHEN has_screenshot = 1 THEN 1 ELSE 0 END) as with_screenshots')
        ->groupBy('page_number')
        ->orderBy('page_number')
        ->get();
    
    return view('admin.documents.processing.images_list', compact(
        'document', 
        'images',
        'imagesByPage'
    ));
}

/**
 * Проверить существование скриншотов
 */
public function checkScreenshots($id)
{
    $document = Document::findOrFail($id);
    $images = DocumentImage::where('document_id', $id)->get();
    
    $checked = 0;
    $missing = 0;
    $existing = 0;
    
    foreach ($images as $image) {
        if ($image->screenshot_path) {
            $exists = Storage::disk('public')->exists($image->screenshot_path);
            if ($exists) {
                $image->has_screenshot = true;
                $image->screenshot_url = Storage::url($image->screenshot_path);
                $image->screenshot_size = Storage::disk('public')->size($image->screenshot_path);
                $existing++;
            } else {
                $image->has_screenshot = false;
                $missing++;
            }
            $image->save();
            $checked++;
        }
    }
    
    return redirect()->route('admin.documents.processing.advanced', $id)
        ->with('success', "Проверено {$checked} изображений. Существует: {$existing}, Отсутствует: {$missing}");
}




/**
 * Страница отладки изображений
 */
public function debugImages($id)
{
    $document = Document::findOrFail($id);
    $images = DocumentImage::where('document_id', $id)->get();
    
    // Проверяем существование директорий
    $directories = [
        'document_images/' . $id => Storage::disk('public')->exists('document_images/' . $id),
        'document_images/screenshots/' . $id => Storage::disk('public')->exists('document_images/screenshots/' . $id),
    ];
    
    return view('admin.documents.processing.debug_images', [
        'document' => $document,
        'images' => $images,
        'imagesCount' => $images->count(),
        'directories' => $directories,
    ]);
}

/**
 * Перепроверка изображений
 */
public function recheckImages(Request $request, $id)
{
    $images = DocumentImage::where('document_id', $id)->get();
    $checked = 0;
    $missingOriginal = 0;
    $missingScreenshot = 0;
    
    foreach ($images as $image) {
        // Проверяем оригинал
        if (!Storage::disk('public')->exists($image->path)) {
            $image->status = 'missing';
            $missingOriginal++;
        } else {
            $image->status = 'active';
        }
        
        // Проверяем скриншот
        if ($image->screenshot_path) {
            if (!Storage::disk('public')->exists($image->screenshot_path)) {
                $image->has_screenshot = false;
                $missingScreenshot++;
            } else {
                $image->has_screenshot = true;
            }
        }
        
        $image->save();
        $checked++;
    }
    
    return redirect()->back()
        ->with('success', "Проверено {$checked} изображений. Отсутствует оригиналов: {$missingOriginal}, скриншотов: {$missingScreenshot}");
}
}