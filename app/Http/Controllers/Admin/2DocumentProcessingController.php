<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentPage;
use App\Models\DocumentImage;
use App\Services\SimpleImageExtractionService;
use App\Services\ImageScreenshotService;
use App\Services\ImageProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;
use setasign\Fpdi\Fpdi;
use Exception;

class DocumentProcessingController extends Controller
{
    protected $imageProcessor;
    protected $screenshotService;
    
    public function __construct()
    {
        $this->imageProcessor = new ImageProcessingService();
        $this->screenshotService = new ImageScreenshotService();
    }
    

    /**
     * Проверяет доступность GD
     */
    private function checkGDAvailable()
    {
        return extension_loaded('gd') && function_exists('gd_info');
    }
    /**
     * Список документов для обработки
     */
    public function index()
    {
        $documents = Document::with(['carModel.brand', 'category'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return view('admin.documents.processing.index', compact('documents'));
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
        
        // Собираем статистику
        $stats = $this->calculateStats($document, $allPages, $images);
        
        return view('admin.documents.processing.processing_advanced', compact(
            'document', 
            'previewPages', 
            'stats'
        ));
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
            
            // Удаляем старые превью
            DocumentPage::where('document_id', $id)->where('is_preview', true)->delete();
            
            // Парсим PDF для предпросмотра (5 страниц)
            $result = $this->parsePdfDocument($id, $filePath, true, 5);
            
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
            Log::error('Preview error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            
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
     * Полный парсинг документа
     */
   public function parseFull(Request $request, $id)
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
        
        // Парсим весь PDF документ
        $result = $this->parsePdfDocument($id, $filePath);
        
        if ($result['success']) {
            // Извлекаем изображения с ресайзом
            $imagesResult = $this->extractImagesWithPages($id, $filePath);
            
            $document->update([
                'status' => 'parsed',
                'is_parsed' => true,
                'parsing_progress' => 100,
                'parsing_quality' => $result['parsing_quality'],
                'total_pages' => $result['total_pages'],
                'word_count' => $result['word_count'],
                'content_text' => $result['full_text'],
                'parsed_at' => now()
            ]);
            
            $message = "✅ Документ успешно распарсен!<br>";
            $message .= "📄 Страниц: {$result['processed_pages']}<br>";
            $message .= "📝 Слов: {$result['word_count']}<br>";
            
            if ($imagesResult['success']) {
                $message .= "🖼️ Изображений: {$imagesResult['images_count']}<br>";
                $message .= "🖼️ Миниатюр: {$imagesResult['thumbnails_created']}<br>";
                $message .= "📸 Скриншотов: {$imagesResult['screenshots_created']}<br>";
                $message .= "📖 Страниц с изображениями: {$imagesResult['pages_with_images']}<br>";
                if ($imagesResult['skipped_count'] > 0) {
                    $message .= "⏭️ Пропущено: {$imagesResult['skipped_count']}";
                }
            }
            
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('success', $message);
        } else {
            $document->update([
                'status' => 'parse_error',
                'parsing_progress' => 0,
                'parsing_quality' => 0.0
            ]);
            
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('error', "Ошибка парсинга: " . $result['error']);
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
     * Извлечь только изображения
     */
    public function parseImagesOnly(Request $request, $id)
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
            
            // Извлекаем только изображения
            $result = $this->extractImagesWithPages($id, $filePath);
            
            if ($result['success']) {
                $document->update([
                    'status' => 'parsed',
                    'parsing_progress' => 100,
                    'parsing_quality' => 0.9,
                    'parsed_at' => now()
                ]);
                
                return redirect()->route('admin.documents.processing.advanced', $id)
                    ->with('success', "Извлечено {$result['images_count']} изображений");
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
            Log::error('Image extraction error: ' . $e->getMessage());
            
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
     * Парсинг одной страницы
     */
    public function parseSinglePage(Request $request, $id)
    {
        $request->validate([
            'page' => 'required|integer|min:1'
        ]);
        
        try {
            $document = Document::findOrFail($id);
            $pageNumber = $request->input('page');
            
            if ($document->status === 'processing') {
                return redirect()->route('admin.documents.processing.advanced', $id)
                    ->with('error', 'Документ уже в обработке.');
            }
            
            $filePath = Storage::disk('local')->path($document->file_path);
            
            // Парсим одну страницу
            $result = $this->parseSinglePdfPage($document, $filePath, $pageNumber);
            
            if ($result['success']) {
                return redirect()->route('admin.documents.processing.advanced', $id)
                    ->with('success', "Страница {$pageNumber} распарсена: {$result['word_count']} слов");
            } else {
                return redirect()->route('admin.documents.processing.advanced', $id)
                    ->with('error', "Ошибка: " . $result['error']);
            }
            
        } catch (\Exception $e) {
            Log::error('Single page parsing error: ' . $e->getMessage());
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('error', "Ошибка: " . $e->getMessage());
        }
    }
    
    /**
     * Удалить предпросмотр
     */
    public function deletePreview(Request $request, $id)
    {
        try {
            $document = Document::findOrFail($id);
            
            if ($document->status === 'processing') {
                return redirect()->route('admin.documents.processing.advanced', $id)
                    ->with('error', 'Документ в обработке. Дождитесь завершения.');
            }
            
            // Удаляем превью-страницы
            $pagesDeleted = DocumentPage::where('document_id', $id)
                ->where('is_preview', true)
                ->delete();
            
            // Обновляем статус документа
            $document->update([
                'status' => 'uploaded',
                'parsing_quality' => 0.0,
                'parsing_progress' => 0,
                'is_parsed' => false,
                'word_count' => 0,
                'content_text' => null,
                'updated_at' => now()
            ]);
            
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('success', "Предпросмотр удален ($pagesDeleted страниц)");
                
        } catch (\Exception $e) {
            Log::error('Delete preview error: ' . $e->getMessage());
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('error', "Ошибка: " . $e->getMessage());
        }
    }
    
    /**
     * Сбросить статус обработки
     */
    public function resetStatus(Request $request, $id)
    {
        try {
            $document = Document::findOrFail($id);
            
            if ($document->status === 'processing') {
                return redirect()->route('admin.documents.processing.advanced', $id)
                    ->with('error', 'Документ в обработке. Дождитесь завершения.');
            }
            
            // Удаляем все связанные данные
            $pagesDeleted = DocumentPage::where('document_id', $id)->delete();
            $imagesDeleted = DocumentImage::where('document_id', $id)->delete();
            
            // Обновляем документ
            $document->update([
                'status' => 'uploaded',
                'is_parsed' => false,
                'parsing_quality' => 0.0,
                'parsing_progress' => 0,
                'word_count' => 0,
                'total_pages' => null,
                'content_text' => null,
                'parsed_at' => null,
                'processing_started_at' => null,
                'updated_at' => now()
            ]);
            
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('success', "Статус сброшен ($pagesDeleted страниц, $imagesDeleted изображений удалено)");
                
        } catch (\Exception $e) {
            Log::error('Reset status error: ' . $e->getMessage());
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('error', "Ошибка: " . $e->getMessage());
        }
    }
    
    /**
     * Получить прогресс обработки (JSON для AJAX)
     */
    public function getProcessingProgress(Request $request, $id)
    {
        try {
            $document = Document::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'progress' => number_format($document->parsing_progress ?? 0, 2),
                'status' => $document->status,
                'message' => $this->getProgressMessage($document)
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
     * Начать AJAX обработку
     */
    public function startProcessingWithAjax(Request $request, $id)
    {
        try {
            $document = Document::findOrFail($id);
            
            if ($document->status === 'processing') {
                return response()->json([
                    'success' => false,
                    'error' => 'Документ уже в обработке'
                ]);
            }
            
            // Обновляем статус
            $document->update([
                'status' => 'processing',
                'processing_started_at' => now(),
                'parsing_progress' => 0
            ]);
            
            // Запускаем обработку в фоне
            $this->startBackgroundProcessing($document);
            
            return response()->json([
                'success' => true,
                'task_id' => 'doc_' . $document->id . '_' . time(),
                'message' => 'Обработка начата'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Start AJAX processing error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Получить список страниц документа (JSON для AJAX)
     */
    public function getDocumentPages(Request $request, $id)
    {
        try {
            $pages = DocumentPage::where('document_id', $id)
                ->orderBy('page_number')
                ->get()
                ->map(function($page) {
                    return [
                        'id' => $page->id,
                        'page_number' => $page->page_number,
                        'word_count' => $page->word_count,
                        'character_count' => $page->character_count,
                        'section_title' => $page->section_title,
                        'has_images' => $page->has_images,
                        'parsing_quality' => $page->parsing_quality,
                        'is_preview' => $page->is_preview,
                        'created_at' => $page->created_at->format('d.m.Y H:i')
                    ];
                });
            
            return response()->json([
                'success' => true,
                'pages' => $pages,
                'count' => $pages->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get pages error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Тестирование извлечения изображений
     */
    public function testImageExtraction(Request $request, $id)
    {
        try {
            $document = Document::findOrFail($id);
            
            if ($document->status === 'processing') {
                return redirect()->route('admin.documents.processing.advanced', $id)
                    ->with('error', 'Документ уже в обработке. Дождитесь завершения.');
            }
            
            $document->update([
                'status' => 'processing',
                'processing_started_at' => now(),
                'parsing_progress' => 0,
                'parsing_quality' => 0.0
            ]);
            
            $filePath = Storage::disk('local')->path($document->file_path);
            
            // Тестируем извлечение изображений
            $result = $this->extractImagesWithPages($id, $filePath);
            
            if ($result['success']) {
                $document->update([
                    'status' => 'parsed',
                    'parsing_progress' => 100,
                    'parsing_quality' => 0.9
                ]);
                
                return redirect()->route('admin.documents.processing.advanced', $id)
                    ->with('success', "Тест изображений: извлечено {$result['images_count']} изображений");
            } else {
                $document->update([
                    'status' => 'parse_error',
                    'parsing_progress' => 0,
                    'parsing_quality' => 0.0
                ]);
                
                return redirect()->route('admin.documents.processing.advanced', $id)
                    ->with('error', "Ошибка теста: " . $result['error']);
            }
                
        } catch (\Exception $e) {
            Log::error('Test image extraction error: ' . $e->getMessage());
            
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
     * Обработка в фоне
     */
    public function parseInBackground(Request $request, $id)
    {
        try {
            $document = Document::findOrFail($id);
            
            if ($document->status === 'processing') {
                return redirect()->route('admin.documents.processing.advanced', $id)
                    ->with('error', 'Документ уже в обработке. Дождитесь завершения.');
            }
            
            $document->update([
                'status' => 'processing',
                'processing_started_at' => now(),
                'parsing_progress' => 0
            ]);
            
            // Запускаем в фоне
            $this->startBackgroundProcessing($document);
            
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('success', "Обработка запущена в фоне. Обновите страницу через несколько минут.");
                
        } catch (\Exception $e) {
            Log::error('Background parsing error: ' . $e->getMessage());
            
            if (isset($document)) {
                $document->update([
                    'status' => 'parse_error',
                    'parsing_progress' => 0
                ]);
            }
            
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('error', "Ошибка: " . $e->getMessage());
        }
    }
    
    /**
     * Показать все изображения
     */
    public function viewImages(Request $request, $id)
    {
        $document = Document::findOrFail($id);
        $images = DocumentImage::where('document_id', $id)
            ->orderBy('page_number')
            ->paginate(20);
            
        return view('admin.documents.processing.images', compact('document', 'images'));
    }
    
    /**
     * Основной метод парсинга PDF документа
     */
    private function parsePdfDocument($documentId, $filePath, $previewOnly = false, $maxPages = null)
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $pages = $pdf->getPages();
            $totalPages = count($pages);
            
            $document = Document::find($documentId);
            $document->update([
                'total_pages' => $totalPages,
                'parsing_progress' => 0
            ]);
            
            // Ограничиваем количество страниц для превью
            if ($previewOnly && $maxPages) {
                $pages = array_slice($pages, 0, min($maxPages, $totalPages));
            }
            
            $totalWords = 0;
            $totalQuality = 0;
            $fullText = '';
            
            foreach ($pages as $index => $page) {
                $pageNumber = $index + 1;
                
                // Обновляем прогресс
                $progress = ($pageNumber / count($pages)) * 100;
                $document->update(['parsing_progress' => $progress]);
                
                try {
                    $text = $page->getText();
                    $wordCount = str_word_count($text);
                    
                    // Форматируем HTML контент
                    $htmlContent = $this->formatHtmlContent($text);
                    
                    // Сохраняем страницу
                    $documentPage = DocumentPage::create([
                        'document_id' => $documentId,
                        'page_number' => $pageNumber,
                        'content' => $htmlContent,
                        'content_text' => $text,
                        'word_count' => $wordCount,
                        'character_count' => mb_strlen($text),
                        'paragraph_count' => substr_count($text, "\n\n") + 1,
                        'is_preview' => $previewOnly,
                        'section_title' => $this->extractSectionTitle($text),
                        'has_images' => false,
                        'parsing_quality' => $this->calculateParsingQuality($text),
                        'status' => $previewOnly ? 'preview' : 'parsed'
                    ]);
                    
                    $totalWords += $wordCount;
                    $totalQuality += $documentPage->parsing_quality;
                    $fullText .= $text . "\n\n";
                    
                } catch (\Exception $e) {
                    Log::warning("Error parsing page {$pageNumber}: " . $e->getMessage());
                    continue;
                }
            }
            
            // Рассчитываем среднее качество
            $avgQuality = count($pages) > 0 ? ($totalQuality / count($pages)) : 0.7;
            
            return [
                'success' => true,
                'processed_pages' => count($pages),
                'total_pages' => $totalPages,
                'word_count' => $totalWords,
                'full_text' => $fullText,
                'parsing_quality' => $avgQuality
            ];
            
        } catch (\Exception $e) {
            Log::error('PDF parsing error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Улучшенное извлечение изображений с привязкой к страницам
     */
   /**
 * Улучшенное извлечение изображений с привязкой к страницам
 */
 private function extractImagesWithPages($documentId, $filePath)
{
    try {
        // Проверяем GD
        if (!$this->checkGDAvailable()) {
            Log::error("GD extension not available");
            return [
                'success' => false,
                'error' => 'Расширение GD для обработки изображений не доступно'
            ];
        }
        
        set_time_limit(600); // 10 минут для полной обработки
        
        // Создаем директории
        $imagesDir = 'document_images/' . $documentId;
        $thumbsDir = 'document_images/thumbs/' . $documentId;
        $screenshotsDir = 'document_images/screenshots/' . $documentId;
        
        Storage::disk('public')->makeDirectory($imagesDir, 0755, true);
        Storage::disk('public')->makeDirectory($thumbsDir, 0755, true);
        Storage::disk('public')->makeDirectory($screenshotsDir, 0755, true);
        
        // Используем сервис извлечения изображений
        $imageService = new SimpleImageExtractionService();
        $images = $imageService->extractAllImages($filePath, $imagesDir);
        
        Log::info("Extracted " . count($images) . " images for document {$documentId}");
        
        if (empty($images)) {
            Log::warning("No images extracted from document {$documentId}");
            return [
                'success' => true,
                'images_count' => 0,
                'message' => 'Изображения не найдены в PDF документе'
            ];
        }
        
        // Получаем все страницы документа
        $pages = DocumentPage::where('document_id', $documentId)
            ->orderBy('page_number')
            ->get();
        
        if ($pages->isEmpty()) {
            DocumentPage::create([
                'document_id' => $documentId,
                'page_number' => 1,
                'content' => 'Документ для изображений',
                'content_text' => 'Документ для изображений',
                'word_count' => 0,
                'character_count' => 0,
                'is_preview' => false,
                'has_images' => false,
                'parsing_quality' => 0.0,
                'status' => 'parsed'
            ]);
            
            $pages = DocumentPage::where('document_id', $documentId)->get();
        }
        
        $pageMapping = [];
        foreach ($pages as $page) {
            $pageMapping[$page->page_number] = $page->id;
        }
        
        $savedCount = 0;
        $skippedCount = 0;
        $totalImages = count($images);
        $totalPages = $pages->count();
        
        // Распределяем изображения по страницам
        foreach ($images as $index => $imageData) {
            try {
                // Проверяем наличие данных изображения
                if (!isset($imageData['path']) || empty($imageData['path'])) {
                    $skippedCount++;
                    continue;
                }
                
                // Проверяем существует ли файл
                if (!Storage::disk('public')->exists($imageData['path'])) {
                    $skippedCount++;
                    continue;
                }
                
                // Проверяем, не пустое ли изображение
                if ($this->imageProcessor->isEmptyImage($imageData['path'])) {
                    Log::info("Skipping empty image at index {$index}");
                    $skippedCount++;
                    Storage::disk('public')->delete($imageData['path']);
                    continue;
                }
                
                // Определяем номер страницы
                $pageNumber = $this->calculatePageNumberForImage($index, $totalImages, $totalPages);
                $pageId = $pageMapping[$pageNumber] ?? null;
                
                $filename = basename($imageData['path']);
                $baseName = pathinfo($filename, PATHINFO_FILENAME);
                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                // Создаем пути
                $thumbnailPath = $thumbsDir . '/thumb_' . $baseName . '.jpg';
                $screenshotPath = $screenshotsDir . '/screen_' . $baseName . '.jpg';
                
                // Создаем миниатюру
                //$thumbnailCreated = $this->createThumbnailWithTrim($imageData['path'], $thumbnailPath);
                //$thumbnailCreated = $this->createScreenshotWithAggressiveTrim($imageData['path'], $thumbnailPath, 300, 200);
                //$thumbnailCreated = $this->createSmartScreenshot($imageData['path'], $thumbnailPath, 300, 200);
               // $thumbnailCreated = $this->createWorkingScreenshot($imageData['path'], $thumbnailPath, 300, 200);
                $thumbnailCreated = $this->createUltraScreenshot($imageData['path'], $thumbnailPath, 300, 200);
                // Создаем скриншот
               // $screenshotCreated = $this->createScreenshotWithTrim($imageData['path'], $screenshotPath);
                //$screenshotCreated = $this->createScreenshotWithAggressiveTrim($imageData['path'], $screenshotPath, 800, 600);
                //$screenshotCreated = $this->createSmartScreenshot($imageData['path'], $screenshotPath, 800, 600);
                //$screenshotCreated = $this->createWorkingScreenshot($imageData['path'], $screenshotPath, 800, 600);
                $screenshotCreated = $this->createUltraScreenshot($imageData['path'], $screenshotPath, 800, 600);
                // Анализируем изображение
                $analysis = $this->imageProcessor->analyzeImage($imageData['path']);
                
                // Создаем запись изображения в БД
                $imageNumber = $savedCount + 1;
                $documentImage = DocumentImage::create([
                    'document_id' => $documentId,
                    'page_id' => $pageId,
                    'page_number' => $pageNumber,
                    'filename' => $filename,
                    'original_filename' => $filename,
                    'path' => $imageData['path'],
                    'url' => Storage::url($imageData['path']),
                    'thumbnail_path' => $thumbnailCreated ? $thumbnailPath : null,
                    'thumbnail_url' => $thumbnailCreated ? Storage::url($thumbnailPath) : null,
                    'screenshot_path' => $screenshotCreated ? $screenshotPath : null,
                    'screenshot_url' => $screenshotCreated ? Storage::url($screenshotPath) : null,
                    'width' => $analysis['width'] ?? ($imageData['width'] ?? null),
                    'height' => $analysis['height'] ?? ($imageData['height'] ?? null),
                    'size' => Storage::disk('public')->size($imageData['path']),
                    'thumbnail_size' => $thumbnailCreated && Storage::disk('public')->exists($thumbnailPath) 
                        ? Storage::disk('public')->size($thumbnailPath) 
                        : 0,
                    'screenshot_size' => $screenshotCreated && Storage::disk('public')->exists($screenshotPath)
                        ? Storage::disk('public')->size($screenshotPath)
                        : 0,
                    'mime_type' => $analysis['mime'] ?? 'image/jpeg',
                    'extension' => $analysis['extension'] ?? $extension,
                    'description' => "Изображение {$imageNumber}",
                    'position' => $savedCount,
                    'is_preview' => ($index === 0),
                    'has_thumbnail' => $thumbnailCreated,
                    'has_screenshot' => $screenshotCreated,
                    'aspect_ratio' => $analysis['aspect_ratio'] ?? null,
                    'is_portrait' => $analysis['is_portrait'] ?? false,
                    'is_landscape' => $analysis['is_landscape'] ?? false,
                    'status' => 'active'
                ]);
                
                // Обновляем страницу
                if ($pageId) {
                    DocumentPage::where('id', $pageId)->update(['has_images' => true]);
                }
                
                $savedCount++;
                
                // Обновляем прогресс
                if ($savedCount % 5 === 0) {
                    $progress = ($savedCount / $totalImages) * 100;
                    Document::where('id', $documentId)->update([
                        'parsing_progress' => $progress
                    ]);
                }
                
            } catch (\Exception $e) {
                Log::error("Error saving image {$index}: " . $e->getMessage());
                $skippedCount++;
                continue;
            }
        }
        
        return [
            'success' => true,
            'images_count' => $savedCount,
            'skipped_count' => $skippedCount,
            'thumbnails_created' => DocumentImage::where('document_id', $documentId)
                ->where('has_thumbnail', true)
                ->count(),
            'screenshots_created' => DocumentImage::where('document_id', $documentId)
                ->where('has_screenshot', true)
                ->count(),
            'pages_with_images' => DocumentPage::where('document_id', $documentId)
                ->where('has_images', true)
                ->count(),
            'message' => "Сохранено {$savedCount} изображений, пропущено {$skippedCount}"
        ];
        
    } catch (\Exception $e) {
        Log::error('Image extraction error: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
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
 * Создание миниатюры
 */
private function createThumbnail($sourcePath, $destinationPath, $width, $height)
{
    try {
        $sourceFullPath = Storage::disk('public')->path($sourcePath);
        $destFullPath = Storage::disk('public')->path($destinationPath);
        
        // Проверяем существует ли исходное изображение
        if (!file_exists($sourceFullPath)) {
            Log::warning("Source image not found: {$sourceFullPath}");
            return false;
        }
        
        // Получаем информацию об изображении
        $imageInfo = getimagesize($sourceFullPath);
        if (!$imageInfo) {
            Log::warning("Invalid image: {$sourceFullPath}");
            return false;
        }
        
        // Создаем изображение в зависимости от типа
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourceFullPath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourceFullPath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($sourceFullPath);
                break;
            default:
                Log::warning("Unsupported image type: {$sourceFullPath}");
                return false;
        }
        
        if (!$sourceImage) {
            Log::warning("Failed to create image from source: {$sourceFullPath}");
            return false;
        }
        
        $srcWidth = imagesx($sourceImage);
        $srcHeight = imagesy($sourceImage);
        
        // Создаем новое изображение для миниатюры
        $thumbnail = imagecreatetruecolor($width, $height);
        
        // Добавляем белый фон для изображений с прозрачностью
        $white = imagecolorallocate($thumbnail, 255, 255, 255);
        imagefill($thumbnail, 0, 0, $white);
        
        // Вычисляем пропорции
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
        
        // Копируем и изменяем размер
        imagecopyresampled(
            $thumbnail, $sourceImage,
            $dstX, $dstY, 0, 0,
            $newWidth, $newHeight, $srcWidth, $srcHeight
        );
        
        // Сохраняем миниатюру
        $extension = strtolower(pathinfo($sourceFullPath, PATHINFO_EXTENSION));
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($thumbnail, $destFullPath, 85);
                break;
            case 'png':
                imagepng($thumbnail, $destFullPath, 8);
                break;
            case 'gif':
                imagegif($thumbnail, $destFullPath);
                break;
        }
        
        // Освобождаем память
        imagedestroy($sourceImage);
        imagedestroy($thumbnail);
        
        return true;
        
    } catch (\Exception $e) {
        Log::error("Thumbnail creation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Создание скриншота (упрощенная версия)
 */
private function createScreenshot($sourcePath, $destinationPath, $maxWidth, $maxHeight)
{
    try {
        $sourceFullPath = Storage::disk('public')->path($sourcePath);
        $destFullPath = Storage::disk('public')->path($destinationPath);
        
        if (!file_exists($sourceFullPath)) {
            return false;
        }
        
        // Просто копируем оригинал для скриншота (можно улучшить)
        return copy($sourceFullPath, $destFullPath);
        
    } catch (\Exception $e) {
        Log::error("Screenshot creation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Получение информации об изображении
 */
private function getImageInfo($path)
{
    try {
        $fullPath = Storage::disk('public')->path($path);
        if (!file_exists($fullPath)) {
            return ['width' => null, 'height' => null];
        }
        
        $imageInfo = getimagesize($fullPath);
        if ($imageInfo) {
            return [
                'width' => $imageInfo[0],
                'height' => $imageInfo[1]
            ];
        }
    } catch (\Exception $e) {
        Log::warning("Error getting image info: " . $e->getMessage());
    }
    
    return ['width' => null, 'height' => null];
}
    
    /**
     * Парсинг одной страницы PDF
     */
    private function parseSinglePdfPage($document, $filePath, $pageNumber)
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $pages = $pdf->getPages();
            
            if ($pageNumber > count($pages) || $pageNumber < 1) {
                return [
                    'success' => false,
                    'error' => "Страница {$pageNumber} не существует"
                ];
            }
            
            $page = $pages[$pageNumber - 1];
            $text = $page->getText();
            $wordCount = str_word_count($text);
            
            // Форматируем HTML контент
            $htmlContent = $this->formatHtmlContent($text);
            
            // Проверяем, существует ли уже страница
            $existingPage = DocumentPage::where('document_id', $document->id)
                ->where('page_number', $pageNumber)
                ->first();
            
            if ($existingPage) {
                $existingPage->update([
                    'content' => $htmlContent,
                    'content_text' => $text,
                    'word_count' => $wordCount,
                    'character_count' => mb_strlen($text),
                    'section_title' => $this->extractSectionTitle($text),
                    'parsing_quality' => $this->calculateParsingQuality($text),
                    'updated_at' => now()
                ]);
            } else {
                DocumentPage::create([
                    'document_id' => $document->id,
                    'page_number' => $pageNumber,
                    'content' => $htmlContent,
                    'content_text' => $text,
                    'word_count' => $wordCount,
                    'character_count' => mb_strlen($text),
                    'paragraph_count' => substr_count($text, "\n\n") + 1,
                    'section_title' => $this->extractSectionTitle($text),
                    'parsing_quality' => $this->calculateParsingQuality($text),
                    'status' => 'parsed'
                ]);
            }
            
            return [
                'success' => true,
                'page_number' => $pageNumber,
                'word_count' => $wordCount
            ];
            
        } catch (\Exception $e) {
            Log::error('Single page parsing error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Запуск фоновой обработки
     */
    private function startBackgroundProcessing($document)
    {
        // Здесь можно реализовать запуск через очереди Laravel
        // Для простоты делаем прямое выполнение с обновлением прогресса
        
        // В реальном приложении используйте:
        // dispatch(new ParseDocumentJob($document));
        
        // А пока просто отмечаем что обработка началась
        $document->update(['parsing_progress' => 5]);
        
        // Через 3 секунды обновляем прогресс
        sleep(3);
        $document->update(['parsing_progress' => 50]);
        
        // Через еще 3 секунды завершаем
        sleep(3);
        $document->update([
            'status' => 'parsed',
            'parsing_progress' => 100,
            'parsing_quality' => 0.8,
            'parsed_at' => now()
        ]);
    }
    
    /**
     * Список всех страниц документа
     */
    public function pagesList(Request $request, $id)
    {
        $document = Document::with(['carModel.brand', 'category'])
            ->findOrFail($id);
        
        $pages = DocumentPage::where('document_id', $id)
            ->orderBy('page_number')
            ->paginate(20);
        
        return view('admin.documents.processing.pages_list', compact('document', 'pages'));
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
        
        return view('admin.documents.processing.page_show', compact('document', 'page', 'images'));
    }
    
    /**
     * Просмотр сырого текста страницы
     */
    public function showPageRaw(Request $request, $id, $pageId)
    {
        $document = Document::findOrFail($id);
        
        $page = DocumentPage::where('document_id', $id)
            ->where('id', $pageId)
            ->firstOrFail();
        
        return response($page->content_text ?? '')
            ->header('Content-Type', 'text/plain; charset=utf-8');
    }
    
    /**
     * Вспомогательные методы
     */
    
    private function formatHtmlContent($text)
    {
        $lines = explode("\n", $text);
        $html = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                $html .= "<br>\n";
            } else {
                $html .= "<p>" . htmlspecialchars($line) . "</p>\n";
            }
        }
        
        return $html;
    }
    
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
    
    private function getMimeTypeFromPath($path)
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'webp' => 'image/webp',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml'
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
    
    private function calculatePageNumberForImage($imageIndex, $totalImages, $totalPages)
    {
        if ($totalPages === 0) return 1;
        
        $imagesPerPage = max(1, ceil($totalImages / $totalPages));
        $pageNumber = floor($imageIndex / $imagesPerPage) + 1;
        
        return min($pageNumber, $totalPages);
    }
    
    private function generateImageDescription($pageNumber, $imageIndex)
    {
        $descriptions = [
            "Иллюстрация на странице {$pageNumber}",
            "Схема на странице {$pageNumber}",
            "Диаграмма на странице {$pageNumber}",
            "Фотография на странице {$pageNumber}",
            "График на странице {$pageNumber}",
            "Чертеж на странице {$pageNumber}"
        ];
        
        return $descriptions[$imageIndex % count($descriptions)];
    }
    
    private function addImageToPageContent($pageId, $documentImage)
    {
        try {
            $page = DocumentPage::find($pageId);
            if (!$page) return;
            
            // Создаем HTML для вставки изображения
            $imageHtml = "\n\n<div class=\"document-image-container\">";
            $imageHtml .= "<div class=\"document-image\">";
            $imageHtml .= "<a href=\"" . Storage::url($documentImage->path) . "\" target=\"_blank\" class=\"image-link\">";
            $imageHtml .= "<img src=\"" . Storage::url($documentImage->screenshot_path ?? $documentImage->thumbnail_path ?? $documentImage->path) . "\" ";
            $imageHtml .= "alt=\"" . htmlspecialchars($documentImage->description) . "\" ";
            $imageHtml .= "class=\"img-fluid\" ";
            $imageHtml .= "style=\"max-width: 100%; height: auto; border: 1px solid #ddd; border-radius: 4px; margin: 10px 0;\">";
            $imageHtml .= "</a>";
            $imageHtml .= "<div class=\"image-caption\">";
            $imageHtml .= "<small class=\"text-muted\">" . htmlspecialchars($documentImage->description);
            
            if ($documentImage->width && $documentImage->height) {
                $imageHtml .= " ({$documentImage->width}×{$documentImage->height}px)";
            }
            
            $imageHtml .= "</small>";
            $imageHtml .= "</div>";
            $imageHtml .= "</div>";
            $imageHtml .= "</div>\n\n";
            
            // Добавляем изображение в контент
            $newContent = $page->content . $imageHtml;
            
            // Обновляем страницу
            $page->update([
                'content' => $newContent,
                'has_images' => true
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error adding image to page content: " . $e->getMessage());
        }
    }
    
    private function calculateStats($document, $pages, $images)
    {
        $pagesCount = $pages->count();
        $imagesCount = $images->count();
        
        $wordsCount = $pages->sum('word_count');
        $charactersCount = $pages->sum('character_count');
        
        // Форматируем размер файла
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
    
    private function getProgressMessage($document)
    {
        switch ($document->status) {
            case 'processing':
                return "Обработка документа... " . number_format($document->parsing_progress ?? 0, 2) . "%";
            case 'parsed':
                return "Документ успешно распарсен";
            case 'preview_created':
                return "Создан предпросмотр документа";
            case 'parse_error':
                return "Ошибка при обработке документа";
            default:
                return "Готов к обработке";
        }
    }

    /**
 * Быстрое извлечение изображений (пропускает пустые)
 */
private function extractImagesQuick($documentId, $filePath)
{
    try {
        set_time_limit(300); // 5 минут
        
        // Создаем директории
        $imagesDir = 'document_images/' . $documentId;
        $thumbsDir = 'document_images/thumbs/' . $documentId;
        $screenshotsDir = 'document_images/screenshots/' . $documentId;
        
        Storage::disk('public')->makeDirectory($imagesDir, 0755, true);
        Storage::disk('public')->makeDirectory($thumbsDir, 0755, true);
        Storage::disk('public')->makeDirectory($screenshotsDir, 0755, true);
        
        // Используем сервис извлечения изображений
        $imageService = new SimpleImageExtractionService();
        $images = $imageService->extractAllImages($filePath, $imagesDir);
        
        Log::info("Extracted " . count($images) . " images for document {$documentId}");
        
        if (empty($images)) {
            return [
                'success' => true,
                'images_count' => 0,
                'message' => 'Изображения не найдены'
            ];
        }
        
        $savedCount = 0;
        $skippedCount = 0;
        
        foreach ($images as $index => $imageData) {
            try {
                // Проверяем наличие данных изображения
                if (!isset($imageData['path']) || empty($imageData['path'])) {
                    $skippedCount++;
                    continue;
                }
                
                // Проверяем существует ли файл
                if (!Storage::disk('public')->exists($imageData['path'])) {
                    $skippedCount++;
                    continue;
                }
                
                $filename = basename($imageData['path']);
                $baseName = pathinfo($filename, PATHINFO_FILENAME);
                
                // 1. Проверяем, не пустое ли изображение
                if ($this->imageProcessor->isEmptyImage($imageData['path'])) {
                    Log::info("Skipping empty image: {$filename}");
                    $skippedCount++;
                    
                    // Удаляем пустое изображение
                    Storage::disk('public')->delete($imageData['path']);
                    continue;
                }
                
                // 2. Проверяем, не номер страницы ли это
                if ($this->imageProcessor->isPageNumberOnly($imageData['path'])) {
                    Log::info("Skipping page number image: {$filename}");
                    $skippedCount++;
                    
                    // Удаляем изображение с номером страницы
                    Storage::disk('public')->delete($imageData['path']);
                    continue;
                }
                
                // 3. Создаем миниатюру (быстрая версия)
                $thumbnailPath = $thumbsDir . '/thumb_' . $baseName . '.jpg';
                $thumbnailCreated = $this->createQuickThumbnail($imageData['path'], $thumbnailPath);
                
                // 4. Создаем скриншот с ресайзом
                $screenshotPath = $screenshotsDir . '/screen_' . $baseName . '.jpg';
                $screenshotCreated = $this->createResizedScreenshot($imageData['path'], $screenshotPath, 800, 600);
                
                // 5. Создаем запись в БД
                $imageNumber = $savedCount + 1;
                DocumentImage::create([
                    'document_id' => $documentId,
                    'page_number' => 1, // Простая логика
                    'filename' => $filename,
                    'path' => $imageData['path'],
                    'url' => Storage::url($imageData['path']),
                    'thumbnail_path' => $thumbnailCreated ? $thumbnailPath : null,
                    'thumbnail_url' => $thumbnailCreated ? Storage::url($thumbnailPath) : null,
                    'screenshot_path' => $screenshotCreated ? $screenshotPath : null,
                    'screenshot_url' => $screenshotCreated ? Storage::url($screenshotPath) : null,
                    'has_thumbnail' => $thumbnailCreated,
                    'has_screenshot' => $screenshotCreated,
                    'description' => "Изображение {$imageNumber}",
                    'position' => $savedCount,
                    'status' => 'active'
                ]);
                
                $savedCount++;
                
                // Обновляем прогресс каждые 10 изображений
                if ($savedCount % 10 === 0) {
                    Document::where('id', $documentId)->update([
                        'parsing_progress' => min(95, ($savedCount / count($images)) * 100)
                    ]);
                }
                
            } catch (\Exception $e) {
                Log::warning("Error processing image {$index}: " . $e->getMessage());
                $skippedCount++;
                continue;
            }
        }
        
        return [
            'success' => true,
            'images_count' => $savedCount,
            'skipped_count' => $skippedCount,
            'message' => "Сохранено: {$savedCount}, пропущено: {$skippedCount}"
        ];
        
    } catch (\Exception $e) {
        Log::error('Quick image extraction error: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Быстрое создание миниатюры
 */
private function createQuickThumbnail($sourcePath, $destinationPath)
{
    try {
        return $this->createResizedScreenshot($sourcePath, $destinationPath, 300, 200);
        
    } catch (\Exception $e) {
        Log::warning("Quick thumbnail error: " . $e->getMessage());
        
        // Резервный вариант - простое копирование
        try {
            $fullSourcePath = Storage::disk('public')->path($sourcePath);
            $fullDestPath = Storage::disk('public')->path($destinationPath);
            
            if (file_exists($fullSourcePath)) {
                $this->createDirectory($fullDestPath);
                return copy($fullSourcePath, $fullDestPath);
            }
        } catch (\Exception $e2) {
            Log::error("Backup copy failed: " . $e2->getMessage());
        }
        
        return false;
    }
}

/**
 * Быстрое создание скриншота
 */
private function createQuickScreenshot($sourcePath, $destinationPath)
{
    try {
        // Для скорости просто копируем оригинал как скриншот
        // В реальном приложении можно добавить ресайз
        $fullSourcePath = Storage::disk('public')->path($sourcePath);
        $fullDestPath = Storage::disk('public')->path($destinationPath);
        
        if (!file_exists($fullSourcePath)) {
            return false;
        }
        
        $this->createDirectory($fullDestPath);
        return copy($fullSourcePath, $fullDestPath);
        
    } catch (\Exception $e) {
        Log::warning("Quick screenshot error: " . $e->getMessage());
        return false;
    }
}

/**
 * Пошаговая обработка документа
 */
public function processStepByStep(Request $request, $id)
{
    $step = $request->input('step', 1);
    
    try {
        $document = Document::findOrFail($id);
        
        switch ($step) {
            case 1: // Парсинг текста
                $document->update(['status' => 'processing_text']);
                $result = $this->parseTextOnly($id);
                break;
                
            case 2: // Извлечение изображений
                $document->update(['status' => 'processing_images']);
                $result = $this->extractImagesOnly($id);
                break;
                
            case 3: // Создание превью
                $document->update(['status' => 'creating_previews']);
                $result = $this->createPreviews($id);
                break;
                
            default:
                return response()->json([
                    'success' => false,
                    'error' => 'Неверный шаг'
                ]);
        }
        
        if ($result['success']) {
            return response()->json([
                'success' => true,
                'step' => $step,
                'next_step' => $step < 3 ? $step + 1 : null,
                'message' => $result['message'] ?? 'Шаг выполнен'
            ]);
        } else {
            $document->update(['status' => 'parse_error']);
            return response()->json([
                'success' => false,
                'error' => $result['error']
            ]);
        }
        
    } catch (\Exception $e) {
        Log::error("Step {$step} error: " . $e->getMessage());
        
        if (isset($document)) {
            $document->update(['status' => 'parse_error']);
        }
        
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Только парсинг текста
 */
private function parseTextOnly($documentId)
{
    set_time_limit(300);
    
    try {
        $document = Document::find($documentId);
        $filePath = Storage::disk('local')->path($document->file_path);
        
        // Упрощенный парсинг PDF
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $pages = $pdf->getPages();
        
        $totalWords = 0;
        $fullText = '';
        
        foreach ($pages as $index => $page) {
            $text = $page->getText();
            $wordCount = str_word_count($text);
            
            // Сохраняем только если есть текст
            if ($wordCount > 10) {
                DocumentPage::create([
                    'document_id' => $documentId,
                    'page_number' => $index + 1,
                    'content_text' => $text,
                    'word_count' => $wordCount,
                    'character_count' => mb_strlen($text),
                    'status' => 'parsed'
                ]);
                
                $totalWords += $wordCount;
                $fullText .= $text . "\n\n";
            }
            
            // Обновляем прогресс
            if (($index + 1) % 10 === 0) {
                $progress = (($index + 1) / count($pages)) * 100;
                $document->update(['parsing_progress' => $progress]);
            }
        }
        
        $document->update([
            'total_pages' => count($pages),
            'word_count' => $totalWords,
            'content_text' => $fullText,
            'parsing_progress' => 100
        ]);
        
        return [
            'success' => true,
            'message' => "Распарсено страниц: " . count($pages)
        ];
        
    } catch (\Exception $e) {
        Log::error("Text parsing error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Создает скриншот с ресайзом
 */
private function createResizedScreenshot($sourcePath, $destinationPath, $maxWidth = 800, $maxHeight = 600)
{
    try {
        $fullSourcePath = Storage::disk('public')->path($sourcePath);
        $fullDestPath = Storage::disk('public')->path($destinationPath);
        
        if (!file_exists($fullSourcePath)) {
            return false;
        }
        
        // Получаем информацию об изображении
        $imageInfo = @getimagesize($fullSourcePath);
        if (!$imageInfo) {
            return false;
        }
        
        list($srcWidth, $srcHeight, $type) = $imageInfo;
        
        // Создаем изображение в зависимости от типа
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($fullSourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($fullSourcePath);
                // Сохраняем прозрачность для PNG
                imagealphablending($sourceImage, false);
                imagesavealpha($sourceImage, true);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($fullSourcePath);
                break;
            default:
                // Для других форматов пытаемся создать из строки
                $sourceImage = @imagecreatefromstring(file_get_contents($fullSourcePath));
                break;
        }
        
        if (!$sourceImage) {
            Log::warning("Failed to create image from: {$sourcePath}");
            return false;
        }
        
        // Рассчитываем новые размеры с сохранением пропорций
        if ($srcWidth <= $maxWidth && $srcHeight <= $maxHeight) {
            // Изображение уже меньше максимальных размеров
            $newWidth = $srcWidth;
            $newHeight = $srcHeight;
        } else {
            $ratio = min($maxWidth / $srcWidth, $maxHeight / $srcHeight);
            $newWidth = floor($srcWidth * $ratio);
            $newHeight = floor($srcHeight * $ratio);
        }
        
        // Создаем новое изображение для скриншота
        $screenshot = imagecreatetruecolor($newWidth, $newHeight);
        
        // Заполняем фон белым
        $white = imagecolorallocate($screenshot, 255, 255, 255);
        imagefill($screenshot, 0, 0, $white);
        
        // Для PNG сохраняем прозрачность
        if ($type == IMAGETYPE_PNG) {
            imagealphablending($screenshot, false);
            imagesavealpha($screenshot, true);
            $transparent = imagecolorallocatealpha($screenshot, 255, 255, 255, 127);
            imagefilledrectangle($screenshot, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Копируем и изменяем размер
        imagecopyresampled(
            $screenshot, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight, $srcWidth, $srcHeight
        );
        
        // Создаем директорию если не существует
        $this->createDirectory($fullDestPath);
        
        // Сохраняем результат
        $extension = strtolower(pathinfo($destinationPath, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $result = imagejpeg($screenshot, $fullDestPath, 85); // 85% качество
                break;
            case 'png':
                $result = imagepng($screenshot, $fullDestPath, 8); // 8 уровень сжатия
                break;
            case 'gif':
                $result = imagegif($screenshot, $fullDestPath);
                break;
            case 'webp':
                if (function_exists('imagewebp')) {
                    $result = imagewebp($screenshot, $fullDestPath, 85);
                } else {
                    // Конвертируем в JPEG если WebP не поддерживается
                    $newDestPath = str_replace('.webp', '.jpg', $fullDestPath);
                    $result = imagejpeg($screenshot, $newDestPath, 85);
                }
                break;
            default:
                // По умолчанию сохраняем как JPEG
                $newDestPath = $fullDestPath . '.jpg';
                $result = imagejpeg($screenshot, $newDestPath, 85);
                break;
        }
        
        // Освобождаем память
        imagedestroy($sourceImage);
        imagedestroy($screenshot);
        
        return $result;
        
    } catch (\Exception $e) {
        Log::error("Resized screenshot error for {$sourcePath}: " . $e->getMessage());
        return false;
    }
}

/**
     * Проверяет наличие белых полей у изображения
     * @return bool
     */
    private function hasWhiteBorders($image, $threshold = 245, $sampleRate = 20)
    {
        $width = $image->width();
        $height = $image->height();
        
        // Проверяем 4 угла изображения
        $corners = [
            [0, 0],                    // Верхний левый
            [$width - 1, 0],           // Верхний правый
            [0, $height - 1],          // Нижний левый
            [$width - 1, $height - 1]  // Нижний правый
        ];
        
        $whiteCorners = 0;
        
        foreach ($corners as $corner) {
            $color = $image->pickColor($corner[0], $corner[1]);
            if ($color[0] >= $threshold && $color[1] >= $threshold && $color[2] >= $threshold) {
                $whiteCorners++;
            }
        }
        
        // Если 3 из 4 углов белые - считаем что есть белые поля
        return $whiteCorners >= 3;
    }
    

     /**
     * Ручная обрезка белых полей
     * @return \Intervention\Image\Image
     */
    private function manualTrim($image, $threshold = 245)
    {
        $width = $image->width();
        $height = $image->height();
        
        // Находим границы контента
        $top = 0;
        $bottom = $height - 1;
        $left = 0;
        $right = $width - 1;
        
        // Ищем верхнюю границу
        for ($y = 0; $y < $height; $y += 5) {
            $hasContent = false;
            for ($x = 0; $x < $width; $x += 5) {
                $color = $image->pickColor($x, $y);
                if ($color[0] < $threshold || $color[1] < $threshold || $color[2] < $threshold) {
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
        for ($y = $height - 1; $y >= 0; $y -= 5) {
            $hasContent = false;
            for ($x = 0; $x < $width; $x += 5) {
                $color = $image->pickColor($x, $y);
                if ($color[0] < $threshold || $color[1] < $threshold || $color[2] < $threshold) {
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
        for ($x = 0; $x < $width; $x += 5) {
            $hasContent = false;
            for ($y = 0; $y < $height; $y += 5) {
                $color = $image->pickColor($x, $y);
                if ($color[0] < $threshold || $color[1] < $threshold || $color[2] < $threshold) {
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
        for ($x = $width - 1; $x >= 0; $x -= 5) {
            $hasContent = false;
            for ($y = 0; $y < $height; $y += 5) {
                $color = $image->pickColor($x, $y);
                if ($color[0] < $threshold || $color[1] < $threshold || $color[2] < $threshold) {
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
        $newWidth = $right - $left;
        $newHeight = $bottom - $top;
        
        // Обрезаем только если есть что обрезать
        if ($newWidth > 100 && $newHeight > 100 && 
            ($newWidth < $width || $newHeight < $height)) {
            Log::info("✂️ Ручная обрезка: {$width}x{$height} -> {$newWidth}x{$newHeight}");
            return $image->crop($newWidth, $newHeight, $left, $top);
        }
        
        Log::info("⚠️ Ручная обрезка не требуется");
        return $image;
    }
    
    /**
     * Создает скриншот с обрезкой белых полей
     * @return bool
     */
    public function createOptimizedScreenshot($imagePath, $outputPath, $maxWidth = 800, $maxHeight = 600)
    {
        try {
            Log::info("🎨 Создаем оптимизированный скриншот...");
            
            // Проверяем исходный файл
            if (!Storage::disk('public')->exists($imagePath)) {
                Log::error("❌ Файл не найден: {$imagePath}");
                return false;
            }
            
            $fullPath = Storage::disk('public')->path($imagePath);
            $image = Image::make($fullPath);
            
            // 1. Обрезаем белые поля
            $image = $this->trimWhiteBorders($image);
            
            // 2. Сохраняем информацию об обрезке
            $originalWidth = $image->width();
            $originalHeight = $image->height();
            
            // 3. Масштабируем до целевых размеров
            $image->resize($maxWidth, $maxHeight, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            
            // 4. Если изображение меньше целевого, добавляем белый фон
            if ($image->width() < $maxWidth || $image->height() < $maxHeight) {
                $canvas = Image::canvas($maxWidth, $maxHeight, '#ffffff');
                $canvas->insert($image, 'center');
                $image = $canvas;
            }
            
            // 5. Сохраняем результат
            $outputFullPath = Storage::disk('public')->path($outputPath);
            $image->save($outputFullPath, 85); // 85% качество
            
            // 6. Проверяем результат
            if (Storage::disk('public')->exists($outputPath)) {
                $screenshotSize = Storage::disk('public')->size($outputPath);
                $originalSize = Storage::disk('public')->size($imagePath);
                
                $savedPercent = 100 - ($screenshotSize / $originalSize * 100);
                
                Log::info("✅ Скриншот создан успешно!");
                Log::info("📍 Путь: {$outputPath}");
                Log::info("📏 Размеры: {$image->width()}x{$image->height()}");
                Log::info("💰 Экономия: " . number_format($savedPercent, 1) . "%");
                
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::error("❌ Ошибка создания скриншота: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Обработка PDF по чанкам (для больших файлов)
     * @return \Illuminate\Http\JsonResponse
     */
    public function processPdfInChunks(Request $request, $documentId)
    {
        try {
            $document = Document::findOrFail($documentId);
            $pdfPath = storage_path('app/public/' . $document->file_path);
            
            // Увеличиваем лимиты
            ini_set('memory_limit', '2048M');
            set_time_limit(3600);
            
            // Определяем размер чанка в зависимости от размера файла
            $fileSizeMB = filesize($pdfPath) / (1024 * 1024);
            $chunkSize = $fileSizeMB > 50 ? 5 : 10;
            
            Log::info("📦 Обработка PDF чанками");
            Log::info("📊 Размер файла: " . number_format($fileSizeMB, 1) . " MB");
            Log::info("🔢 Размер чанка: {$chunkSize} страниц");
            
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($pdfPath);
            
            $processed = 0;
            
            // Обрабатываем по чанкам
            for ($startPage = 1; $startPage <= $pageCount; $startPage += $chunkSize) {
                $endPage = min($startPage + $chunkSize - 1, $pageCount);
                
                Log::info("🔄 Чанк: страницы {$startPage}-{$endPage}");
                
                $this->processChunk($document, $pdf, $startPage, $endPage);
                $processed += ($endPage - $startPage + 1);
                
                // Очищаем память
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
                
                // Отправляем прогресс
                if ($request->ajax()) {
                    $progress = round(($processed / $pageCount) * 100);
                    // Здесь можно отправить SSE или WebSocket сообщение
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Обработано {$processed} страниц",
                'pages' => $processed
            ]);
            
        } catch (\Exception $e) {
            Log::error("❌ Ошибка обработки PDF: " . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка обработки',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Обработка чанка страниц
     */
    private function processChunk($document, $pdf, $startPage, $endPage)
    {
        for ($pageNum = $startPage; $pageNum <= $endPage; $pageNum++) {
            try {
                $this->processSinglePage($document, $pdf, $pageNum);
            } catch (\Exception $e) {
                Log::error("❌ Ошибка страницы {$pageNum}: " . $e->getMessage());
                continue;
            }
        }
    }
    
    /**
     * Обработка одной страницы
     */
    private function processSinglePage($document, $pdf, $pageNumber)
    {
        // Импортируем страницу
        $templateId = $pdf->importPage($pageNumber);
        $size = $pdf->getTemplateSize($templateId);
        
        // Создаем новую страницу
        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($templateId);
        
        // Сохраняем временный PDF
        $tempPdfPath = storage_path("app/temp/page_{$pageNumber}.pdf");
        $pdf->Output($tempPdfPath, 'F');
        
        // Конвертируем в изображение
        $image = Image::make($tempPdfPath);
        
        // Сохраняем оригинал
        $imagePath = "documents/{$document->id}/pages/page_{$pageNumber}.jpg";
        Storage::disk('public')->put($imagePath, $image->encode('jpg', 90));
        
        // Создаем скриншот
        $screenshotPath = "documents/{$document->id}/screenshots/page_{$pageNumber}.jpg";
        $this->createOptimizedScreenshot($imagePath, $screenshotPath);
        
        // Создаем миниатюру
        $thumbnailPath = "documents/{$document->id}/thumbnails/page_{$pageNumber}.jpg";
        $image->resize(300, 200, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        Storage::disk('public')->put($thumbnailPath, $image->encode('jpg', 80));
        
        // Сохраняем в базу
        DocumentPage::updateOrCreate(
            [
                'document_id' => $document->id,
                'page_number' => $pageNumber
            ],
            [
                'original_image_path' => $imagePath,
                'screenshot_path' => $screenshotPath,
                'thumbnail_path' => $thumbnailPath,
            ]
        );
        
        // Удаляем временный файл
        if (file_exists($tempPdfPath)) {
            unlink($tempPdfPath);
        }
        
        Log::info("✅ Страница {$pageNumber} обработана");
    }
    
    /**
     * Принудительная обрезка белых полей для существующего изображения
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceTrimImage(Request $request, $imageId)
    {
        try {
            $image = DocumentImage::findOrFail($imageId);
            
            if (!Storage::disk('public')->exists($image->path)) {
                return response()->json([
                    'error' => 'Исходное изображение не найдено'
                ], 404);
            }
            
            // Создаем новый скриншот с обрезкой
            $screenshotPath = str_replace('.jpg', '_trimmed.jpg', $image->screenshot_path ?? $image->path);
            $success = $this->createOptimizedScreenshot($image->path, $screenshotPath);
            
            if ($success) {
                $image->screenshot_path = $screenshotPath;
                $image->save();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Белые поля успешно обрезаны',
                    'screenshot_url' => Storage::url($screenshotPath)
                ]);
            }
            
            return response()->json([
                'error' => 'Не удалось обрезать изображение'
            ], 500);
            
        } catch (\Exception $e) {
            Log::error("❌ Ошибка обрезки: " . $e->getMessage());
            return response()->json([
                'error' => 'Внутренняя ошибка сервера'
            ], 500);
        }
    }
    
    /**
     * Проверка качества скриншота
     * @return array
     */
    private function checkScreenshotQuality($originalPath, $screenshotPath)
    {
        $result = [
            'has_screenshot' => false,
            'is_different' => false,
            'size_reduction' => 0,
            'dimensions_changed' => false
        ];
        
        if (!Storage::disk('public')->exists($screenshotPath)) {
            return $result;
        }
        
        $result['has_screenshot'] = true;
        
        // Сравниваем размеры файлов
        $originalSize = Storage::disk('public')->size($originalPath);
        $screenshotSize = Storage::disk('public')->size($screenshotPath);
        
        $result['size_reduction'] = 100 - ($screenshotSize / $originalSize * 100);
        $result['is_different'] = $screenshotSize < $originalSize * 0.9; // На 10% меньше
        
        // Сравниваем размеры изображений
        try {
            $original = Image::make(Storage::disk('public')->path($originalPath));
            $screenshot = Image::make(Storage::disk('public')->path($screenshotPath));
            
            $result['original_dimensions'] = $original->width() . 'x' . $original->height();
            $result['screenshot_dimensions'] = $screenshot->width() . 'x' . $screenshot->height();
            $result['dimensions_changed'] = $original->width() != $screenshot->width() || 
                                           $original->height() != $screenshot->height();
            
        } catch (\Exception $e) {
            Log::error("❌ Ошибка сравнения размеров: " . $e->getMessage());
        }
        
        return $result;
    }

    /**
 * Обрезает белые поля вокруг изображения
 */
 private function trimWhiteBorders($image)
    {
        try {
            Log::info("✂️ Начинаем обрезку белых полей...");
            
            $originalWidth = $image->width();
            $originalHeight = $image->height();
            
            // 1. Проверяем, есть ли белые поля
            $hasWhiteBorders = $this->hasWhiteBorders($image);
            
            if (!$hasWhiteBorders) {
                Log::info("⚡ Белых полей не обнаружено");
                return $image;
            }
            
            // 2. Пробуем автоматическую обрезку Intervention
            Log::info("🔄 Пробуем автоматическую обрезку...");
            $trimmed = $image->trim('top-left', array(255, 255, 255), 10);
            
            $newWidth = $trimmed->width();
            $newHeight = $trimmed->height();
            
            // 3. Проверяем результат обрезки
            if ($newWidth < $originalWidth * 0.8 || $newHeight < $originalHeight * 0.8) {
                Log::info("✅ Успешная обрезка: {$originalWidth}x{$originalHeight} -> {$newWidth}x{$newHeight}");
                return $trimmed;
            }
            
            // 4. Если автоматическая не сработала, пробуем ручную
            Log::info("⚠️ Автоматическая обрезка не сработала, пробуем ручную...");
            return $this->manualTrim($image);
            
        } catch (\Exception $e) {
            Log::error("❌ Ошибка обрезки: " . $e->getMessage());
            return $image; // Возвращаем оригинал в случае ошибки
        }
    }

/**
 * Создает скриншот с обрезкой белого фона и ресайзом
 */
private function createScreenshotWithTrim($sourcePath, $destinationPath, $maxWidth = 800, $maxHeight = 600)
{
    try {
        $fullSourcePath = Storage::disk('public')->path($sourcePath);
        $fullDestPath = Storage::disk('public')->path($destinationPath);
        
        if (!file_exists($fullSourcePath)) {
            Log::error("Source image not found: {$fullSourcePath}");
            return false;
        }
        
        // Получаем информацию об изображении
        $imageInfo = @getimagesize($fullSourcePath);
        if (!$imageInfo) {
            Log::error("Invalid image file: {$fullSourcePath}");
            return false;
        }
        
        list($srcWidth, $srcHeight, $type) = $imageInfo;
        
        // Создаем изображение в зависимости от типа
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($fullSourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($fullSourcePath);
                // Сохраняем прозрачность для PNG
                imagealphablending($sourceImage, false);
                imagesavealpha($sourceImage, true);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($fullSourcePath);
                break;
            default:
                // Для других форматов пытаемся создать из строки
                $sourceImage = @imagecreatefromstring(file_get_contents($fullSourcePath));
                break;
        }
        
        if (!$sourceImage) {
            Log::warning("Failed to create image from: {$sourcePath}");
            return false;
        }
        
        // 1. ОБРЕЗАЕМ БЕЛЫЕ ПОЛЯ
        list($croppedImage, $cropWidth, $cropHeight) = $this->trimWhiteBorders(
            $sourceImage, $srcWidth, $srcHeight
        );
        
        // 2. РЕСАЙЗИМ ДО МАКСИМАЛЬНЫХ РАЗМЕРОВ
        if ($cropWidth <= $maxWidth && $cropHeight <= $maxHeight) {
            // Изображение уже меньше максимальных размеров
            $newWidth = $cropWidth;
            $newHeight = $cropHeight;
        } else {
            $ratio = min($maxWidth / $cropWidth, $maxHeight / $cropHeight);
            $newWidth = floor($cropWidth * $ratio);
            $newHeight = floor($cropHeight * $ratio);
        }
        
        // Создаем финальное изображение для скриншота
        $screenshot = imagecreatetruecolor($newWidth, $newHeight);
        
        // Заполняем фон белым
        $white = imagecolorallocate($screenshot, 255, 255, 255);
        imagefill($screenshot, 0, 0, $white);
        
        // Для PNG сохраняем прозрачность
        if ($type == IMAGETYPE_PNG) {
            imagealphablending($screenshot, false);
            imagesavealpha($screenshot, true);
            $transparent = imagecolorallocatealpha($screenshot, 255, 255, 255, 127);
            imagefilledrectangle($screenshot, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Копируем и изменяем размер
        imagecopyresampled(
            $screenshot, $croppedImage,
            0, 0, 0, 0,
            $newWidth, $newHeight, $cropWidth, $cropHeight
        );
        
        // Создаем директорию если не существует
        $this->createDirectory($fullDestPath);
        
        // Сохраняем результат как JPEG (даже если был PNG, для скриншота лучше JPEG)
        $result = imagejpeg($screenshot, $fullDestPath, 85); // 85% качество
        
        // Освобождаем память
        imagedestroy($croppedImage);
        imagedestroy($screenshot);
        
        if ($result) {
            Log::info("Screenshot with trim created: {$destinationPath} ({$newWidth}x{$newHeight})");
        }
        
        return $result;
        
    } catch (\Exception $e) {
        Log::error("Screenshot with trim error for {$sourcePath}: " . $e->getMessage());
        return false;
    }
}

/**
 * Создает миниатюру с обрезкой белого фона
 */
private function createThumbnailWithTrim($sourcePath, $destinationPath)
{
    try {
        // Используем тот же метод, но с меньшим размером
        return $this->createScreenshotWithTrim($sourcePath, $destinationPath, 300, 200);
        
    } catch (\Exception $e) {
        Log::warning("Thumbnail with trim error: " . $e->getMessage());
        
        // Резервный вариант - простой ресайз без обрезки
        try {
            return $this->createResizedScreenshot($sourcePath, $destinationPath, 300, 200);
        } catch (\Exception $e2) {
            Log::error("Backup thumbnail failed: " . $e2->getMessage());
            return false;
        }
    }
}

/**
 * Агрессивная обрезка белых полей с использованием гистограммы
 */
private function trimWhiteBordersAggressive($sourceImage, $width, $height, $whiteThreshold = 245)
{
    try {
        // Создаем гистограммы яркости по краям
        $topHistogram = array_fill(0, $width, 0);
        $bottomHistogram = array_fill(0, $width, 0);
        $leftHistogram = array_fill(0, $height, 0);
        $rightHistogram = array_fill(0, $height, 0);
        
        // Анализируем верхнюю и нижнюю границы (каждые 2 пикселя)
        for ($x = 0; $x < $width; $x += 2) {
            for ($y = 0; $y < min(30, $height); $y += 2) { // Верхние 30px
                $color = imagecolorat($sourceImage, $x, $y);
                $rgb = imagecolorsforindex($sourceImage, $color);
                $brightness = ($rgb['red'] + $rgb['green'] + $rgb['blue']) / 3;
                $topHistogram[$x] += $brightness;
            }
            
            for ($y = max(0, $height - 30); $y < $height; $y += 2) { // Нижние 30px
                $color = imagecolorat($sourceImage, $x, $y);
                $rgb = imagecolorsforindex($sourceImage, $color);
                $brightness = ($rgb['red'] + $rgb['green'] + $rgb['blue']) / 3;
                $bottomHistogram[$x] += $brightness;
            }
        }
        
        // Анализируем левую и правую границы
        for ($y = 0; $y < $height; $y += 2) {
            for ($x = 0; $x < min(30, $width); $x += 2) { // Левые 30px
                $color = imagecolorat($sourceImage, $x, $y);
                $rgb = imagecolorsforindex($sourceImage, $color);
                $brightness = ($rgb['red'] + $rgb['green'] + $rgb['blue']) / 3;
                $leftHistogram[$y] += $brightness;
            }
            
            for ($x = max(0, $width - 30); $x < $width; $x += 2) { // Правые 30px
                $color = imagecolorat($sourceImage, $x, $y);
                $rgb = imagecolorsforindex($sourceImage, $color);
                $brightness = ($rgb['red'] + $rgb['green'] + $rgb['blue']) / 3;
                $rightHistogram[$y] += $brightness;
            }
        }
        
        // Находим границы где яркость падает ниже порога
        $top = 0;
        $bottom = $height - 1;
        $left = 0;
        $right = $width - 1;
        
        // Верхняя граница
        for ($y = 0; $y < $height; $y++) {
            $rowBright = true;
            for ($x = 0; $x < $width; $x += 10) {
                $color = imagecolorat($sourceImage, $x, $y);
                $rgb = imagecolorsforindex($sourceImage, $color);
                if ($rgb['red'] < $whiteThreshold || $rgb['green'] < $whiteThreshold || $rgb['blue'] < $whiteThreshold) {
                    $rowBright = false;
                    break;
                }
            }
            if (!$rowBright) {
                $top = $y;
                break;
            }
        }
        
        // Нижняя граница
        for ($y = $height - 1; $y >= 0; $y--) {
            $rowBright = true;
            for ($x = 0; $x < $width; $x += 10) {
                $color = imagecolorat($sourceImage, $x, $y);
                $rgb = imagecolorsforindex($sourceImage, $color);
                if ($rgb['red'] < $whiteThreshold || $rgb['green'] < $whiteThreshold || $rgb['blue'] < $whiteThreshold) {
                    $rowBright = false;
                    break;
                }
            }
            if (!$rowBright) {
                $bottom = $y;
                break;
            }
        }
        
        // Левая граница
        for ($x = 0; $x < $width; $x++) {
            $colBright = true;
            for ($y = 0; $y < $height; $y += 10) {
                $color = imagecolorat($sourceImage, $x, $y);
                $rgb = imagecolorsforindex($sourceImage, $color);
                if ($rgb['red'] < $whiteThreshold || $rgb['green'] < $whiteThreshold || $rgb['blue'] < $whiteThreshold) {
                    $colBright = false;
                    break;
                }
            }
            if (!$colBright) {
                $left = $x;
                break;
            }
        }
        
        // Правая граница
        for ($x = $width - 1; $x >= 0; $x--) {
            $colBright = true;
            for ($y = 0; $y < $height; $y += 10) {
                $color = imagecolorat($sourceImage, $x, $y);
                $rgb = imagecolorsforindex($sourceImage, $color);
                if ($rgb['red'] < $whiteThreshold || $rgb['green'] < $whiteThreshold || $rgb['blue'] < $whiteThreshold) {
                    $colBright = false;
                    break;
                }
            }
            if (!$colBright) {
                $right = $x;
                break;
            }
        }
        
        // Проверяем что границы валидны
        if ($left >= $right || $top >= $bottom) {
            Log::warning("Invalid crop boundaries: left={$left}, right={$right}, top={$top}, bottom={$bottom}");
            return [$sourceImage, $width, $height];
        }
        
        $cropWidth = $right - $left + 1;
        $cropHeight = $bottom - $top + 1;
        
        // Минимальный размер обрезки - 50% от оригинала
        if ($cropWidth < $width * 0.5 || $cropHeight < $height * 0.5) {
            Log::warning("Crop area too small: {$cropWidth}x{$cropHeight} from {$width}x{$height}");
            return [$sourceImage, $width, $height];
        }
        
        Log::info("Cropping from {$width}x{$height} to {$cropWidth}x{$cropHeight} (top={$top}, left={$left})");
        
        // Создаем обрезанное изображение
        $croppedImage = imagecreatetruecolor($cropWidth, $cropHeight);
        
        // Сохраняем прозрачность если есть
        $transparent = imagecolorallocatealpha($croppedImage, 0, 0, 0, 127);
        imagefill($croppedImage, 0, 0, $transparent);
        imagesavealpha($croppedImage, true);
        
        // Копируем обрезанную область
        imagecopy($croppedImage, $sourceImage, 0, 0, $left, $top, $cropWidth, $cropHeight);
        
        // Освобождаем память от исходного изображения
        imagedestroy($sourceImage);
        
        return [$croppedImage, $cropWidth, $cropHeight];
        
    } catch (\Exception $e) {
        Log::error("Aggressive trim error: " . $e->getMessage());
        return [$sourceImage, $width, $height];
    }
}

/**
 * Создает скриншот с агрессивной обрезкой белого фона
 */
private function createScreenshotWithAggressiveTrim($sourcePath, $destinationPath, $maxWidth = 800, $maxHeight = 600)
{
    try {
        $fullSourcePath = Storage::disk('public')->path($sourcePath);
        $fullDestPath = Storage::disk('public')->path($destinationPath);
        
        if (!file_exists($fullSourcePath)) {
            Log::error("Source image not found: {$fullSourcePath}");
            return false;
        }
        
        // Получаем информацию об изображении
        $imageInfo = @getimagesize($fullSourcePath);
        if (!$imageInfo) {
            Log::error("Invalid image file: {$fullSourcePath}");
            return false;
        }
        
        list($srcWidth, $srcHeight, $type) = $imageInfo;
        
        Log::info("Processing screenshot: {$sourcePath} ({$srcWidth}x{$srcHeight})");
        
        // Создаем изображение в зависимости от типа
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($fullSourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($fullSourcePath);
                // Сохраняем прозрачность
                imagealphablending($sourceImage, false);
                imagesavealpha($sourceImage, true);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($fullSourcePath);
                break;
            default:
                $sourceImage = @imagecreatefromstring(file_get_contents($fullSourcePath));
                break;
        }
        
        if (!$sourceImage) {
            Log::warning("Failed to create image from: {$sourcePath}");
            return false;
        }
        
        // 1. АГРЕССИВНАЯ ОБРЕЗКА БЕЛЫХ ПОЛЕЙ
        list($croppedImage, $cropWidth, $cropHeight) = $this->trimWhiteBordersAggressive(
            $sourceImage, $srcWidth, $srcHeight
        );
        
        Log::info("After trim: {$cropWidth}x{$cropHeight} (was {$srcWidth}x{$srcHeight})");
        
        // 2. РЕСАЙЗ ДО МАКСИМАЛЬНЫХ РАЗМЕРОВ
        if ($cropWidth <= $maxWidth && $cropHeight <= $maxHeight) {
            $newWidth = $cropWidth;
            $newHeight = $cropHeight;
        } else {
            $ratio = min($maxWidth / $cropWidth, $maxHeight / $cropHeight);
            $newWidth = floor($cropWidth * $ratio);
            $newHeight = floor($cropHeight * $ratio);
        }
        
        // 3. СОЗДАЕМ ФИНАЛЬНОЕ ИЗОБРАЖЕНИЕ
        $screenshot = imagecreatetruecolor($newWidth, $newHeight);
        
        // Белый фон для JPEG
        $white = imagecolorallocate($screenshot, 255, 255, 255);
        imagefill($screenshot, 0, 0, $white);
        
        // Для PNG сохраняем прозрачность
        if ($type == IMAGETYPE_PNG) {
            imagealphablending($screenshot, false);
            imagesavealpha($screenshot, true);
            $transparent = imagecolorallocatealpha($screenshot, 255, 255, 255, 127);
            imagefilledrectangle($screenshot, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Копируем с ресайзом
        imagecopyresampled(
            $screenshot, $croppedImage,
            0, 0, 0, 0,
            $newWidth, $newHeight, $cropWidth, $cropHeight
        );
        
        // Создаем директорию
        $this->createDirectory($fullDestPath);
        
        // Сохраняем как JPEG для лучшего сжатия
        $result = imagejpeg($screenshot, $fullDestPath, 90); // Высокое качество
        
        // Освобождаем память
        imagedestroy($croppedImage);
        imagedestroy($screenshot);
        
        if ($result) {
            $finalSize = filesize($fullDestPath);
            Log::info("Screenshot saved: {$destinationPath} ({$newWidth}x{$newHeight}, {$finalSize} bytes)");
            
            // Сравниваем с оригиналом
            $originalSize = filesize($fullSourcePath);
            $savedPercent = round(($originalSize - $finalSize) / $originalSize * 100, 2);
            Log::info("Size comparison: Original={$originalSize}b, Screenshot={$finalSize}b, Saved={$savedPercent}%");
        }
        
        return $result;
        
    } catch (\Exception $e) {
        Log::error("Aggressive screenshot error for {$sourcePath}: " . $e->getMessage());
        return false;
    }
}

/**
 * Улучшенная обрезка белых полей с анализом содержимого
 */
private function trimWhiteBordersAdvanced($sourceImage, $width, $height, $whiteThreshold = 240)
{
    try {
        // Проверяем, не является ли изображение полностью белым
        $isCompletelyWhite = true;
        $samplePoints = min(100, $width * $height / 100);
        
        for ($i = 0; $i < $samplePoints; $i++) {
            $x = rand(0, $width - 1);
            $y = rand(0, $height - 1);
            
            $color = imagecolorat($sourceImage, $x, $y);
            $rgb = imagecolorsforindex($sourceImage, $color);
            
            // Если нашли не-белый пиксель
            if ($rgb['red'] < $whiteThreshold || $rgb['green'] < $whiteThreshold || $rgb['blue'] < $whiteThreshold) {
                $isCompletelyWhite = false;
                break;
            }
        }
        
        if ($isCompletelyWhite) {
            Log::info("Image is completely white, no trimming needed");
            return [$sourceImage, $width, $height];
        }
        
        // Используем более агрессивный алгоритм поиска границ
        $top = $height;
        $bottom = 0;
        $left = $width;
        $right = 0;
        
        // Сканируем с шагом 5px для скорости
        $step = max(1, floor(min($width, $height) / 200));
        
        // Ищем границы по горизонтали
        for ($y = 0; $y < $height; $y += $step) {
            for ($x = 0; $x < $width; $x += $step) {
                $color = imagecolorat($sourceImage, $x, $y);
                $rgb = imagecolorsforindex($sourceImage, $color);
                
                // Проверяем что пиксель НЕ белый
                $isNotWhite = ($rgb['red'] < $whiteThreshold || 
                              $rgb['green'] < $whiteThreshold || 
                              $rgb['blue'] < $whiteThreshold);
                
                if ($isNotWhite) {
                    $top = min($top, $y);
                    $bottom = max($bottom, $y);
                    $left = min($left, $x);
                    $right = max($right, $x);
                }
            }
        }
        
        // Добавляем отступ 2%
        $paddingX = floor($width * 0.02);
        $paddingY = floor($height * 0.02);
        
        $left = max(0, $left - $paddingX);
        $top = max(0, $top - $paddingY);
        $right = min($width - 1, $right + $paddingX);
        $bottom = min($height - 1, $bottom + $paddingY);
        
        $cropWidth = $right - $left + 1;
        $cropHeight = $bottom - $top + 1;
        
        // Если область обрезки слишком похожа на оригинал (разница < 10%), не обрезаем
        $widthDiffPercent = ($width - $cropWidth) / $width * 100;
        $heightDiffPercent = ($height - $cropHeight) / $height * 100;
        
        if ($widthDiffPercent < 5 && $heightDiffPercent < 5) {
            Log::info("Trim area too similar to original: width {$widthDiffPercent}%, height {$heightDiffPercent}%");
            return [$sourceImage, $width, $height];
        }
        
        Log::info("Cropping from {$width}x{$height} to {$cropWidth}x{$cropHeight} " .
                 "(top={$top}, left={$left}, saved width={$widthDiffPercent}%, height={$heightDiffPercent}%)");
        
        // Создаем обрезанное изображение
        $croppedImage = imagecreatetruecolor($cropWidth, $cropHeight);
        
        // Белый фон
        $white = imagecolorallocate($croppedImage, 255, 255, 255);
        imagefill($croppedImage, 0, 0, $white);
        
        // Копируем обрезанную область
        imagecopy($croppedImage, $sourceImage, 0, 0, $left, $top, $cropWidth, $cropHeight);
        
        imagedestroy($sourceImage);
        
        return [$croppedImage, $cropWidth, $cropHeight];
        
    } catch (\Exception $e) {
        Log::error("Advanced trim error: " . $e->getMessage());
        return [$sourceImage, $width, $height];
    }
}

/**
 * Создает скриншот с интеллектуальной обрезкой
 */
/**
 * Создает скриншот с интеллектуальной обработкой
 */
private function createSmartScreenshot($sourcePath, $destinationPath, $maxWidth = 800, $maxHeight = 600)
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
        
        list($srcWidth, $srcHeight, $type) = $imageInfo;
        
        Log::info("Creating smart screenshot: {$sourcePath} ({$srcWidth}x{$srcHeight})");
        
        // Анализируем изображение
        $analysis = $this->analyzeImageContent($fullSourcePath, $type);
        
        Log::info("Analysis results: ", $analysis);
        
        // РЕШАЕМ: обрезать или нет
        $shouldTrim = false;
        
        // Правила для обрезки:
        // 1. Если это текстовая страница (много текста) - ОБРЕЗАТЬ
        if ($analysis['has_text'] && $analysis['text_density'] > 1) {
            Log::info("Text page detected (text density: {$analysis['text_density']}%), will trim");
            $shouldTrim = true;
        }
        // 2. Если много краев (графика/диаграммы) - ОБРЕЗАТЬ  
        elseif ($analysis['has_edges'] && $analysis['edge_density'] > 0.2) {
            Log::info("Graphic image detected (edge density: {$analysis['edge_density']}), will trim");
            $shouldTrim = true;
        }
        // 3. Если белых пикселей меньше 95% - возможно есть контент
        elseif ($analysis['white_percent'] < 95) {
            Log::info("Not completely white (white: {$analysis['white_percent']}%), will try trim");
            $shouldTrim = true;
        }
        // 4. Для очень простых/белых изображений - простой ресайз
        else {
            Log::info("Simple/white image (white: {$analysis['white_percent']}%), using simple resize");
            return $this->createSimpleResize($sourcePath, $destinationPath, $maxWidth, $maxHeight);
        }
        
        if (!$shouldTrim) {
            Log::info("No trimming needed, using simple resize");
            return $this->createSimpleResize($sourcePath, $destinationPath, $maxWidth, $maxHeight);
        }
        
        // Создаем изображение для обрезки
        $sourceImage = $this->createImageResource($fullSourcePath, $type);
        if (!$sourceImage) {
            return false;
        }
        
        // Обрезка белых полей
        list($croppedImage, $cropWidth, $cropHeight) = $this->trimContentBorders(
            $sourceImage, $srcWidth, $srcHeight, $analysis
        );
        
        Log::info("After content trim: {$cropWidth}x{$cropHeight} (was {$srcWidth}x{$srcHeight})");
        
        // Если обрезка не удалась или слишком мала
        if ($cropWidth >= $srcWidth * 0.95 || $cropHeight >= $srcHeight * 0.95) {
            Log::info("Trim ineffective, using simple resize");
            imagedestroy($croppedImage);
            return $this->createSimpleResize($sourcePath, $destinationPath, $maxWidth, $maxHeight);
        }
        
        // Ресайз
        if ($cropWidth <= $maxWidth && $cropHeight <= $maxHeight) {
            $newWidth = $cropWidth;
            $newHeight = $cropHeight;
        } else {
            $ratio = min($maxWidth / $cropWidth, $maxHeight / $cropHeight);
            $newWidth = floor($cropWidth * $ratio);
            $newHeight = floor($cropHeight * $ratio);
        }
        
        // Финальное изображение
        $finalImage = imagecreatetruecolor($newWidth, $newHeight);
        $white = imagecolorallocate($finalImage, 255, 255, 255);
        imagefill($finalImage, 0, 0, $white);
        
        imagecopyresampled(
            $finalImage, $croppedImage,
            0, 0, 0, 0,
            $newWidth, $newHeight, $cropWidth, $cropHeight
        );
        
        // Сохраняем
        $this->createDirectory($fullDestPath);
        $result = imagejpeg($finalImage, $fullDestPath, 85);
        
        // Логируем
        if ($result) {
            $originalSize = filesize($fullSourcePath);
            $finalSize = filesize($fullDestPath);
            $savedPercent = round(($originalSize - $finalSize) / $originalSize * 100, 2);
            
            Log::info("Smart screenshot created: {$destinationPath} ({$newWidth}x{$newHeight})");
            Log::info("Trimmed: {$srcWidth}x{$srcHeight} -> {$cropWidth}x{$cropHeight}");
            Log::info("Size saved: {$savedPercent}%");
        }
        
        imagedestroy($croppedImage);
        imagedestroy($finalImage);
        
        return $result;
        
    } catch (\Exception $e) {
        Log::error("Smart screenshot error: " . $e->getMessage());
        return false;
    }
}



/**
 * Рабочий метод создания скриншота с обрезкой
 */
private function createWorkingScreenshot($sourcePath, $destinationPath, $maxWidth = 800, $maxHeight = 600)
{
    try {
        $fullSourcePath = Storage::disk('public')->path($sourcePath);
        $fullDestPath = Storage::disk('public')->path($destinationPath);
        
        if (!file_exists($fullSourcePath)) {
            Log::error("Source not found: {$fullSourcePath}");
            return false;
        }
        
        $imageInfo = @getimagesize($fullSourcePath);
        if (!$imageInfo) {
            Log::error("Invalid image: {$fullSourcePath}");
            return false;
        }
        
        list($srcWidth, $srcHeight, $type) = $imageInfo;
        
        Log::info("Processing: {$sourcePath} ({$srcWidth}x{$srcHeight})");
        
        // Загружаем изображение
        $sourceImage = $this->createImageResource($fullSourcePath, $type);
        if (!$sourceImage) {
            Log::error("Failed to load image: {$fullSourcePath}");
            return false;
        }
        
        // 1. ПРОБУЕМ ОБРЕЗАТЬ
        list($croppedImage, $cropWidth, $cropHeight) = $this->trimWhiteBordersSimple(
            $sourceImage, $srcWidth, $srcHeight
        );
        
        // Проверяем эффективность обрезки
        $widthReduced = $srcWidth - $cropWidth;
        $heightReduced = $srcHeight - $cropHeight;
        $areaReduced = ($srcWidth * $srcHeight) - ($cropWidth * $cropHeight);
        $reductionPercent = $areaReduced / ($srcWidth * $srcHeight) * 100;
        
        Log::info("Trim results: reduced by {$widthReduced}px width, {$heightReduced}px height, {$reductionPercent}% area");
        
        // Если обрезка убрала меньше 5% площади - отменяем
        if ($reductionPercent < 5) {
            Log::info("Trim ineffective (<5%), using original");
            imagedestroy($croppedImage);
            $croppedImage = $this->createImageResource($fullSourcePath, $type);
            $cropWidth = $srcWidth;
            $cropHeight = $srcHeight;
        }
        
        // 2. РЕСАЙЗ ДО МАКСИМАЛЬНЫХ РАЗМЕРОВ
        if ($cropWidth <= $maxWidth && $cropHeight <= $maxHeight) {
            $newWidth = $cropWidth;
            $newHeight = $cropHeight;
        } else {
            $ratio = min($maxWidth / $cropWidth, $maxHeight / $cropHeight);
            $newWidth = floor($cropWidth * $ratio);
            $newHeight = floor($cropHeight * $ratio);
        }
        
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
        $this->createDirectory($fullDestPath);
        $result = imagejpeg($finalImage, $fullDestPath, 85);
        
        // Логируем
        if ($result) {
            $originalSize = filesize($fullSourcePath);
            $finalSize = filesize($fullDestPath);
            $savedPercent = round(($originalSize - $finalSize) / $originalSize * 100, 2);
            
            Log::info("✅ Screenshot created: {$destinationPath}");
            Log::info("📏 Size: {$newWidth}x{$newHeight}");
            Log::info("💾 Saved: {$savedPercent}% ({$originalSize} -> {$finalSize} bytes)");
            Log::info("✂️ Trimmed: {$widthReduced}px width, {$heightReduced}px height");
        }
        
        imagedestroy($croppedImage);
        imagedestroy($finalImage);
        
        return $result;
        
    } catch (\Exception $e) {
        Log::error("❌ Screenshot error for {$sourcePath}: " . $e->getMessage());
        return false;
    }
}

/**
 * Анализирует содержимое изображения
 */
/**
 * Исправленный анализ изображения
 */
private function analyzeImageContent($path, $type)
{
    try {
        $image = $this->createImageResource($path, $type);
        if (!$image) {
            return [
                'white_percent' => 100, 
                'complexity' => 0,
                'has_text' => false,
                'edge_density' => 0,
                'image_type' => 'unknown'
            ];
        }
        
        $width = imagesx($image);
        $height = imagesy($image);
        
        $whitePixels = 0;
        $darkPixels = 0;
        $totalPixels = 0;
        
        // Берем сетку 50x50 точек для анализа
        $xStep = max(1, floor($width / 50));
        $yStep = max(1, floor($height / 50));
        
        for ($x = 0; $x < $width; $x += $xStep) {
            for ($y = 0; $y < $height; $y += $yStep) {
                $color = imagecolorat($image, $x, $y);
                $rgb = imagecolorsforindex($image, $color);
                
                $brightness = ($rgb['red'] + $rgb['green'] + $rgb['blue']) / 3;
                
                // Белый: brightness > 240
                if ($brightness > 240) {
                    $whitePixels++;
                }
                // Темный (возможный текст): brightness < 100
                elseif ($brightness < 100) {
                    $darkPixels++;
                }
                
                $totalPixels++;
            }
        }
        
        imagedestroy($image);
        
        $whitePercent = ($totalPixels > 0) ? ($whitePixels / $totalPixels * 100) : 100;
        $darkPercent = ($totalPixels > 0) ? ($darkPixels / $totalPixels * 100) : 0;
        
        // Определяем тип
        if ($darkPercent > 10) {
            $imageType = 'text_page';
        } elseif ($whitePercent < 70) {
            $imageType = 'graphic';
        } else {
            $imageType = 'white_page';
        }
        
        Log::info("Image analysis: white={$whitePercent}%, dark={$darkPercent}%, type={$imageType}");
        
        return [
            'white_percent' => $whitePercent,
            'dark_percent' => $darkPercent,
            'has_text' => $darkPercent > 5,
            'image_type' => $imageType,
            'sample_size' => $totalPixels
        ];
        
    } catch (\Exception $e) {
        Log::error("Image analysis error: " . $e->getMessage());
        return [
            'white_percent' => 100, 
            'dark_percent' => 0,
            'has_text' => false,
            'image_type' => 'unknown'
        ];
    }
}

/**
 * Простой ресайз без обрезки
 */
private function createSimpleResize($sourcePath, $destinationPath, $maxWidth, $maxHeight)
{
    try {
        $fullSourcePath = Storage::disk('public')->path($sourcePath);
        $fullDestPath = Storage::disk('public')->path($destinationPath);
        
        $imageInfo = @getimagesize($fullSourcePath);
        if (!$imageInfo) {
            return false;
        }
        
        list($srcWidth, $srcHeight, $type) = $imageInfo;
        
        $sourceImage = $this->createImageResource($fullSourcePath, $type);
        if (!$sourceImage) {
            return false;
        }
        
        // Рассчитываем размеры
        if ($srcWidth <= $maxWidth && $srcHeight <= $maxHeight) {
            $newWidth = $srcWidth;
            $newHeight = $srcHeight;
        } else {
            $ratio = min($maxWidth / $srcWidth, $maxHeight / $srcHeight);
            $newWidth = floor($srcWidth * $ratio);
            $newHeight = floor($srcHeight * $ratio);
        }
        
        // Создаем новое изображение
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        $white = imagecolorallocate($resizedImage, 255, 255, 255);
        imagefill($resizedImage, 0, 0, $white);
        
        imagecopyresampled(
            $resizedImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight, $srcWidth, $srcHeight
        );
        
        $this->createDirectory($fullDestPath);
        $result = imagejpeg($resizedImage, $fullDestPath, 85);
        
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);
        
        if ($result) {
            Log::info("Simple resize created: {$destinationPath} ({$newWidth}x{$newHeight})");
        }
        
        return $result;
        
    } catch (\Exception $e) {
        Log::error("Simple resize error: " . $e->getMessage());
        return false;
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
 * Обрезка границ с учетом содержимого
 */
private function trimContentBorders($sourceImage, $width, $height, $analysis)
{
    try {
        // Для текстовых страниц ищем границы текста
        if ($analysis['image_type'] === 'text_page') {
            return $this->trimTextPageBorders($sourceImage, $width, $height);
        }
        // Для графики ищем края
        elseif ($analysis['image_type'] === 'graphic') {
            return $this->trimGraphicBorders($sourceImage, $width, $height);
        }
        // Для остальных - стандартная обрезка
        else {
            return $this->trimSimpleBorders($sourceImage, $width, $height);
        }
        
    } catch (\Exception $e) {
        Log::error("Content border trim error: " . $e->getMessage());
        return [$sourceImage, $width, $height];
    }
}

/**
 * Обрезка текстовой страницы
 */
private function trimTextPageBorders($sourceImage, $width, $height)
{
    $top = $height;
    $bottom = 0;
    $left = $width;
    $right = 0;
    
    // Ищем темные пиксели (текст)
    $textThreshold = 150; // Порог для темного пикселя
    
    // Сканируем строки
    $rowStep = max(1, floor($height / 100));
    $colStep = max(1, floor($width / 100));
    
    for ($y = 0; $y < $height; $y += $rowStep) {
        $hasTextInRow = false;
        for ($x = 0; $x < $width; $x += $colStep) {
            $color = imagecolorat($sourceImage, $x, $y);
            $rgb = imagecolorsforindex($sourceImage, $color);
            
            // Темный пиксель = возможный текст
            if ($rgb['red'] < $textThreshold && $rgb['green'] < $textThreshold && $rgb['blue'] < $textThreshold) {
                $hasTextInRow = true;
                $top = min($top, $y);
                $bottom = max($bottom, $y);
                break;
            }
        }
        
        if ($hasTextInRow) {
            // Для этой строки ищем левую и правую границы
            for ($x = 0; $x < $width; $x += $colStep) {
                $color = imagecolorat($sourceImage, $x, $y);
                $rgb = imagecolorsforindex($sourceImage, $color);
                
                if ($rgb['red'] < $textThreshold && $rgb['green'] < $textThreshold && $rgb['blue'] < $textThreshold) {
                    $left = min($left, $x);
                    $right = max($right, $x);
                }
            }
        }
    }
    
    // Добавляем отступы
    $paddingX = floor($width * 0.02);
    $paddingY = floor($height * 0.02);
    
    $left = max(0, $left - $paddingX);
    $top = max(0, $top - $paddingY);
    $right = min($width - 1, $right + $paddingX);
    $bottom = min($height - 1, $bottom + $paddingY);
    
    $cropWidth = $right - $left + 1;
    $cropHeight = $bottom - $top + 1;
    
    // Создаем обрезанное изображение
    $croppedImage = imagecreatetruecolor($cropWidth, $cropHeight);
    $white = imagecolorallocate($croppedImage, 255, 255, 255);
    imagefill($croppedImage, 0, 0, $white);
    
    imagecopy($croppedImage, $sourceImage, 0, 0, $left, $top, $cropWidth, $cropHeight);
    
    imagedestroy($sourceImage);
    
    Log::info("Text page trim: {$width}x{$height} -> {$cropWidth}x{$cropHeight}");
    
    return [$croppedImage, $cropWidth, $cropHeight];
}
/**
 * Простая но эффективная обрезка белых полей
 */
private function trimWhiteBordersSimple($sourceImage, $width, $height)
{
    try {
        // Находим границы НЕ-белого содержимого
        $top = $height;
        $bottom = 0;
        $left = $width;
        $right = 0;
        
        // Порог для "не-белого" (чем ниже, тем более агрессивная обрезка)
        $threshold = 240;
        
        // Сканируем каждую 20-ю строку и столбец для скорости
        $step = max(1, floor(min($width, $height) / 50));
        
        // Ищем верхнюю границу
        for ($y = 0; $y < $height; $y += $step) {
            $found = false;
            for ($x = 0; $x < $width; $x += $step) {
                $color = imagecolorat($sourceImage, $x, $y);
                $rgb = imagecolorsforindex($sourceImage, $color);
                
                // Если пиксель НЕ белый
                if ($rgb['red'] < $threshold || $rgb['green'] < $threshold || $rgb['blue'] < $threshold) {
                    $top = $y;
                    $found = true;
                    break;
                }
            }
            if ($found) break;
        }
        
        // Ищем нижнюю границу
        for ($y = $height - 1; $y >= 0; $y -= $step) {
            $found = false;
            for ($x = 0; $x < $width; $x += $step) {
                $color = imagecolorat($sourceImage, $x, $y);
                $rgb = imagecolorsforindex($sourceImage, $color);
                
                if ($rgb['red'] < $threshold || $rgb['green'] < $threshold || $rgb['blue'] < $threshold) {
                    $bottom = $y;
                    $found = true;
                    break;
                }
            }
            if ($found) break;
        }
        
        // Ищем левую границу
        for ($x = 0; $x < $width; $x += $step) {
            $found = false;
            for ($y = 0; $y < $height; $y += $step) {
                $color = imagecolorat($sourceImage, $x, $y);
                $rgb = imagecolorsforindex($sourceImage, $color);
                
                if ($rgb['red'] < $threshold || $rgb['green'] < $threshold || $rgb['blue'] < $threshold) {
                    $left = $x;
                    $found = true;
                    break;
                }
            }
            if ($found) break;
        }
        
        // Ищем правую границу
        for ($x = $width - 1; $x >= 0; $x -= $step) {
            $found = false;
            for ($y = 0; $y < $height; $y += $step) {
                $color = imagecolorat($sourceImage, $x, $y);
                $rgb = imagecolorsforindex($sourceImage, $color);
                
                if ($rgb['red'] < $threshold || $rgb['green'] < $threshold || $rgb['blue'] < $threshold) {
                    $right = $x;
                    $found = true;
                    break;
                }
            }
            if ($found) break;
        }
        
        // Если не нашли границ (все пиксели белые)
        if ($top == $height && $bottom == 0 && $left == $width && $right == 0) {
            Log::info("Image is completely white, no trimming possible");
            return [$sourceImage, $width, $height];
        }
        
        // Проверяем что границы валидны
        if ($top >= $bottom || $left >= $right) {
            Log::warning("Invalid borders found: top={$top}, bottom={$bottom}, left={$left}, right={$right}");
            return [$sourceImage, $width, $height];
        }
        
        // Добавляем небольшой отступ (1% от размеров)
        $paddingX = floor($width * 0.01);
        $paddingY = floor($height * 0.01);
        
        $top = max(0, $top - $paddingY);
        $bottom = min($height - 1, $bottom + $paddingY);
        $left = max(0, $left - $paddingX);
        $right = min($width - 1, $right + $paddingX);
        
        $cropWidth = $right - $left + 1;
        $cropHeight = $bottom - $top + 1;
        
        // Проверяем что обрезка имеет смысл (убрали хотя бы 5% с каждой стороны)
        $widthReduction = ($width - $cropWidth) / $width * 100;
        $heightReduction = ($height - $cropHeight) / $height * 100;
        
        Log::info("Found borders: top={$top}, bottom={$bottom}, left={$left}, right={$right}");
        Log::info("Original: {$width}x{$height}, Cropped: {$cropWidth}x{$cropHeight}");
        Log::info("Reduction: width {$widthReduction}%, height {$heightReduction}%");
        
        // Если обрезка убрала меньше 2% с каждой стороны - не обрезаем
        if ($widthReduction < 2 && $heightReduction < 2) {
            Log::info("Trim not effective enough (<2% reduction), keeping original");
            return [$sourceImage, $width, $height];
        }
        
        // Создаем обрезанное изображение
        $croppedImage = imagecreatetruecolor($cropWidth, $cropHeight);
        
        // Белый фон
        $white = imagecolorallocate($croppedImage, 255, 255, 255);
        imagefill($croppedImage, 0, 0, $white);
        
        // Копируем обрезанную область
        imagecopy($croppedImage, $sourceImage, 0, 0, $left, $top, $cropWidth, $cropHeight);
        
        // Освобождаем память исходного изображения
        imagedestroy($sourceImage);
        
        Log::info("✅ Successfully trimmed image");
        
        return [$croppedImage, $cropWidth, $cropHeight];
        
    } catch (\Exception $e) {
        Log::error("❌ Simple trim error: " . $e->getMessage());
        return [$sourceImage, $width, $height];
    }
}

  /**
 * Агрессивная обрезка - ищет САМЫЕ КРАЙНИЕ не-белые пиксели
 */
private function trimAggressive($sourceImage, $width, $height)
{
    try {
        Log::info("🔍 Starting AGGRESSIVE trim on {$width}x{$height} image");
        
        // Более низкий порог для "не-белого"
        $threshold = 250; // Почти чисто белый
        
        $top = $height;
        $bottom = 0;
        $left = $width;
        $right = 0;
        
        // Сканируем ВСЕ пиксели по границам
        // Верхняя и нижняя границы
        for ($x = 0; $x < $width; $x++) {
            // Верх
            $color = imagecolorat($sourceImage, $x, 0);
            $rgb = imagecolorsforindex($sourceImage, $color);
            if ($rgb['red'] < $threshold || $rgb['green'] < $threshold || $rgb['blue'] < $threshold) {
                $top = 0;
                $left = min($left, $x);
                $right = max($right, $x);
            }
            
            // Низ
            $color = imagecolorat($sourceImage, $x, $height-1);
            $rgb = imagecolorsforindex($sourceImage, $color);
            if ($rgb['red'] < $threshold || $rgb['green'] < $threshold || $rgb['blue'] < $threshold) {
                $bottom = $height-1;
                $left = min($left, $x);
                $right = max($right, $x);
            }
        }
        
        // Левая и правая границы
        for ($y = 0; $y < $height; $y++) {
            // Левая
            $color = imagecolorat($sourceImage, 0, $y);
            $rgb = imagecolorsforindex($sourceImage, $color);
            if ($rgb['red'] < $threshold || $rgb['green'] < $threshold || $rgb['blue'] < $threshold) {
                $left = 0;
                $top = min($top, $y);
                $bottom = max($bottom, $y);
            }
            
            // Правая
            $color = imagecolorat($sourceImage, $width-1, $y);
            $rgb = imagecolorsforindex($sourceImage, $color);
            if ($rgb['red'] < $threshold || $rgb['green'] < $threshold || $rgb['blue'] < $threshold) {
                $right = $width-1;
                $top = min($top, $y);
                $bottom = max($bottom, $y);
            }
        }
        
        Log::info("📐 Initial border scan: top={$top}, bottom={$bottom}, left={$left}, right={$right}");
        
        // Если на границах есть не-белые пиксели, значит нет белых полей
        if ($top == 0 && $bottom == $height-1 && $left == 0 && $right == $width-1) {
            Log::info("⚠️ No white borders detected at edges");
            
            // Попробуем найти контент внутри
            return $this->findContentInside($sourceImage, $width, $height);
        }
        
        // Обрезаем
        $cropWidth = $right - $left + 1;
        $cropHeight = $bottom - $top + 1;
        
        Log::info("📏 Would crop to: {$cropWidth}x{$cropHeight}");
        
        // Если обрезка минимальна - пробуем другой метод
        if ($cropWidth > $width * 0.95 || $cropHeight > $height * 0.95) {
            Log::info("🔄 Crop minimal, trying content detection...");
            return $this->findContentInside($sourceImage, $width, $height);
        }
        
        // Выполняем обрезку
        $croppedImage = imagecreatetruecolor($cropWidth, $cropHeight);
        $white = imagecolorallocate($croppedImage, 255, 255, 255);
        imagefill($croppedImage, 0, 0, $white);
        
        imagecopy($croppedImage, $sourceImage, 0, 0, $left, $top, $cropWidth, $cropHeight);
        
        imagedestroy($sourceImage);
        
        Log::info("✅ Aggressive trim successful");
        
        return [$croppedImage, $cropWidth, $cropHeight];
        
    } catch (\Exception $e) {
        Log::error("❌ Aggressive trim error: " . $e->getMessage());
        return [$sourceImage, $width, $height];
    }
}

/**
 * Ищет контент внутри изображения (для страниц без белых полей)
 */
private function findContentInside($sourceImage, $width, $height)
{
    try {
        Log::info("🔎 Looking for content inside image...");
        
        // Ищем самую белую строку и столбец
        $whitestRow = 0;
        $whitestRowBrightness = 0;
        $whitestCol = 0;
        $whitestColBrightness = 0;
        
        // Проверяем каждую 10-ю строку
        for ($y = 0; $y < $height; $y += 10) {
            $rowBrightness = 0;
            for ($x = 0; $x < $width; $x += 10) {
                $color = imagecolorat($sourceImage, $x, $y);
                $rgb = imagecolorsforindex($sourceImage, $color);
                $rowBrightness += ($rgb['red'] + $rgb['green'] + $rgb['blue']) / 3;
            }
            $rowBrightness = $rowBrightness / ceil($width/10);
            
            if ($rowBrightness > $whitestRowBrightness) {
                $whitestRowBrightness = $rowBrightness;
                $whitestRow = $y;
            }
        }
        
        // Проверяем каждую 10-й столбец
        for ($x = 0; $x < $width; $x += 10) {
            $colBrightness = 0;
            for ($y = 0; $y < $height; $y += 10) {
                $color = imagecolorat($sourceImage, $x, $y);
                $rgb = imagecolorsforindex($sourceImage, $color);
                $colBrightness += ($rgb['red'] + $rgb['green'] + $rgb['blue']) / 3;
            }
            $colBrightness = $colBrightness / ceil($height/10);
            
            if ($colBrightness > $whitestColBrightness) {
                $whitestColBrightness = $colBrightness;
                $whitestCol = $x;
            }
        }
        
        Log::info("📊 Whitest row: {$whitestRow} (brightness: {$whitestRowBrightness})");
        Log::info("📊 Whitest column: {$whitestCol} (brightness: {$whitestColBrightness})");
        
        // Если самая белая строка/столбец не совсем белые (>245), значит изображение равномерное
        if ($whitestRowBrightness < 245 || $whitestColBrightness < 245) {
            Log::info("🌫️ Image appears uniformly non-white, no trimming possible");
            return [$sourceImage, $width, $height];
        }
        
        // Попробуем обрезать 5% с каждой стороны (стандартные поля)
        $trimPercent = 0.05;
        $top = floor($height * $trimPercent);
        $bottom = floor($height * (1 - $trimPercent));
        $left = floor($width * $trimPercent);
        $right = floor($width * (1 - $trimPercent));
        
        $cropWidth = $right - $left;
        $cropHeight = $bottom - $top;
        
        Log::info("✂️ Trimming 5% from each side: {$cropWidth}x{$cropHeight}");
        
        $croppedImage = imagecreatetruecolor($cropWidth, $cropHeight);
        $white = imagecolorallocate($croppedImage, 255, 255, 255);
        imagefill($croppedImage, 0, 0, $white);
        
        imagecopy($croppedImage, $sourceImage, 0, 0, $left, $top, $cropWidth, $cropHeight);
        
        imagedestroy($sourceImage);
        
        return [$croppedImage, $cropWidth, $cropHeight];
        
    } catch (\Exception $e) {
        Log::error("❌ Find content error: " . $e->getMessage());
        return [$sourceImage, $width, $height];
    }
}
/**
 * Детальный анализ изображения (пиксельный анализ углов и границ)
 */
private function analyzeImageDetails($sourcePath)
{
    try {
        $fullPath = Storage::disk('public')->path($sourcePath);
        
        if (!file_exists($fullPath)) {
            Log::error("❌ File not found for analysis: {$sourcePath}");
            return ['error' => 'File not found'];
        }
        
        $imageInfo = @getimagesize($fullPath);
        if (!$imageInfo) {
            Log::error("❌ Invalid image for analysis: {$sourcePath}");
            return ['error' => 'Invalid image'];
        }
        
        list($width, $height, $type) = $imageInfo;
        
        $image = $this->createImageResource($fullPath, $type);
        if (!$image) {
            Log::error("❌ Failed to load image for analysis: {$sourcePath}");
            return ['error' => 'Failed to load image'];
        }
        
        // Анализируем углы
        $corners = [
            'top_left' => [0, 0],
            'top_right' => [$width-1, 0],
            'bottom_left' => [0, $height-1],
            'bottom_right' => [$width-1, $height-1],
            'center' => [floor($width/2), floor($height/2)]
        ];
        
        $cornerAnalysis = [];
        foreach ($corners as $name => $coord) {
            $color = imagecolorat($image, $coord[0], $coord[1]);
            $rgb = imagecolorsforindex($image, $color);
            $brightness = ($rgb['red'] + $rgb['green'] + $rgb['blue']) / 3;
            $isWhite = ($rgb['red'] > 240 && $rgb['green'] > 240 && $rgb['blue'] > 240);
            
            $cornerAnalysis[$name] = [
                'x' => $coord[0],
                'y' => $coord[1],
                'rgb' => $rgb,
                'brightness' => round($brightness, 2),
                'is_white' => $isWhite,
                'hex' => sprintf("#%02x%02x%02x", $rgb['red'], $rgb['green'], $rgb['blue'])
            ];
        }
        
        // Анализируем границы (10 точек на каждой стороне)
        $borderPoints = 10;
        $borderAnalysis = [
            'top' => [],
            'bottom' => [],
            'left' => [],
            'right' => []
        ];
        
        // Верхняя граница
        for ($i = 0; $i < $borderPoints; $i++) {
            $x = floor($width * $i / $borderPoints);
            $color = imagecolorat($image, $x, 0);
            $rgb = imagecolorsforindex($image, $color);
            $brightness = ($rgb['red'] + $rgb['green'] + $rgb['blue']) / 3;
            
            $borderAnalysis['top'][] = [
                'x' => $x, 'y' => 0,
                'brightness' => round($brightness, 2),
                'is_white' => ($rgb['red'] > 240 && $rgb['green'] > 240 && $rgb['blue'] > 240)
            ];
        }
        
        // Нижняя граница
        for ($i = 0; $i < $borderPoints; $i++) {
            $x = floor($width * $i / $borderPoints);
            $color = imagecolorat($image, $x, $height-1);
            $rgb = imagecolorsforindex($image, $color);
            $brightness = ($rgb['red'] + $rgb['green'] + $rgb['blue']) / 3;
            
            $borderAnalysis['bottom'][] = [
                'x' => $x, 'y' => $height-1,
                'brightness' => round($brightness, 2),
                'is_white' => ($rgb['red'] > 240 && $rgb['green'] > 240 && $rgb['blue'] > 240)
            ];
        }
        
        // Левая граница
        for ($i = 0; $i < $borderPoints; $i++) {
            $y = floor($height * $i / $borderPoints);
            $color = imagecolorat($image, 0, $y);
            $rgb = imagecolorsforindex($image, $color);
            $brightness = ($rgb['red'] + $rgb['green'] + $rgb['blue']) / 3;
            
            $borderAnalysis['left'][] = [
                'x' => 0, 'y' => $y,
                'brightness' => round($brightness, 2),
                'is_white' => ($rgb['red'] > 240 && $rgb['green'] > 240 && $rgb['blue'] > 240)
            ];
        }
        
        // Правая граница
        for ($i = 0; $i < $borderPoints; $i++) {
            $y = floor($height * $i / $borderPoints);
            $color = imagecolorat($image, $width-1, $y);
            $rgb = imagecolorsforindex($image, $color);
            $brightness = ($rgb['red'] + $rgb['green'] + $rgb['blue']) / 3;
            
            $borderAnalysis['right'][] = [
                'x' => $width-1, 'y' => $y,
                'brightness' => round($brightness, 2),
                'is_white' => ($rgb['red'] > 240 && $rgb['green'] > 240 && $rgb['blue'] > 240)
            ];
        }
        
        imagedestroy($image);
        
        // Подсчитываем статистику
        $whiteCorners = 0;
        foreach ($cornerAnalysis as $corner) {
            if ($corner['is_white']) $whiteCorners++;
        }
        
        $whiteBorders = 0;
        $totalBorderPoints = 0;
        foreach ($borderAnalysis as $side => $points) {
            foreach ($points as $point) {
                $totalBorderPoints++;
                if ($point['is_white']) $whiteBorders++;
            }
        }
        
        $whiteCornersPercent = round($whiteCorners / count($corners) * 100, 2);
        $whiteBordersPercent = round($whiteBorders / $totalBorderPoints * 100, 2);
        
        $result = [
            'image_size' => "{$width}x{$height}",
            'corners' => $cornerAnalysis,
            'borders_sample' => $borderPoints . ' points per side',
            'white_corners' => "{$whiteCorners}/" . count($corners) . " ({$whiteCornersPercent}%)",
            'white_borders' => "{$whiteBorders}/{$totalBorderPoints} ({$whiteBordersPercent}%)",
            'has_white_borders' => $whiteBordersPercent > 80,
            'analysis_time' => now()->toDateTimeString()
        ];
        
        Log::info("🔬 Image analysis for {$sourcePath}:");
        Log::info("   Size: {$width}x{$height}");
        Log::info("   White corners: {$whiteCornersPercent}%");
        Log::info("   White borders: {$whiteBordersPercent}%");
        
        return $result;
        
    } catch (\Exception $e) {
        Log::error("❌ Image analysis error for {$sourcePath}: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}
/**
 * Ультра-агрессивный скриншот с обрезкой
 */
/**
 * Ультра-агрессивный скриншот с обрезкой
 */
private function createUltraScreenshot($sourcePath, $destinationPath, $maxWidth = 800, $maxHeight = 600)
{
    try {
        $fullSourcePath = Storage::disk('public')->path($sourcePath);
        $fullDestPath = Storage::disk('public')->path($destinationPath);
        
        if (!file_exists($fullSourcePath)) {
            Log::error("❌ File not found: {$sourcePath}");
            return false;
        }
        
        $imageInfo = @getimagesize($fullSourcePath);
        if (!$imageInfo) {
            Log::error("❌ Invalid image: {$sourcePath}");
            return false;
        }
        
        list($srcWidth, $srcHeight, $type) = $imageInfo;
        
        Log::info("🚀 ULTRA processing: {$sourcePath}");
        Log::info("📐 Original size: {$srcWidth}x{$srcHeight}");
        
        // Анализируем изображение
        $analysis = $this->analyzeImageDetails($sourcePath);
        
        if (isset($analysis['error'])) {
            Log::error("❌ Analysis failed: " . $analysis['error']);
        } else {
            Log::info("🔬 Analysis: white borders = " . ($analysis['has_white_borders'] ? 'YES' : 'NO'));
        }
        
        $sourceImage = $this->createImageResource($fullSourcePath, $type);
        if (!$sourceImage) {
            Log::error("❌ Failed to load image");
            return false;
        }
        
        // Определяем стратегию обрезки на основе анализа
        $shouldTrim = false;
        $trimMethod = 'none';
        
        if (isset($analysis['has_white_borders']) && $analysis['has_white_borders']) {
            Log::info("⚡ White borders detected, using aggressive trim");
            $shouldTrim = true;
            $trimMethod = 'aggressive';
        } else {
            Log::info("⚡ No white borders, using fixed percentage trim");
            $shouldTrim = true;
            $trimMethod = 'fixed';
        }
        
        // Обрезка
        if ($shouldTrim) {
            if ($trimMethod === 'aggressive') {
                list($croppedImage, $cropWidth, $cropHeight) = $this->trimAggressive(
                    $sourceImage, $srcWidth, $srcHeight
                );
            } else {
                // Фиксированная обрезка 10%
                $trimPercent = 0.10;
                $top = floor($srcHeight * $trimPercent);
                $bottom = floor($srcHeight * (1 - $trimPercent));
                $left = floor($srcWidth * $trimPercent);
                $right = floor($srcWidth * (1 - $trimPercent));
                
                $cropWidth = $right - $left;
                $cropHeight = $bottom - $top;
                
                Log::info("✂️ Fixed 10% crop: {$cropWidth}x{$cropHeight}");
                
                $croppedImage = imagecreatetruecolor($cropWidth, $cropHeight);
                $white = imagecolorallocate($croppedImage, 255, 255, 255);
                imagefill($croppedImage, 0, 0, $white);
                
                imagecopy($croppedImage, $sourceImage, 0, 0, $left, $top, $cropWidth, $cropHeight);
                
                imagedestroy($sourceImage);
            }
        } else {
            $croppedImage = $sourceImage;
            $cropWidth = $srcWidth;
            $cropHeight = $srcHeight;
        }
        
        Log::info("📏 After processing: {$cropWidth}x{$cropHeight}");
        
        // Ресайз
        if ($cropWidth <= $maxWidth && $cropHeight <= $maxHeight) {
            $newWidth = $cropWidth;
            $newHeight = $cropHeight;
        } else {
            $ratio = min($maxWidth / $cropWidth, $maxHeight / $cropHeight);
            $newWidth = floor($cropWidth * $ratio);
            $newHeight = floor($cropHeight * $ratio);
        }
        
        Log::info("📐 Final size: {$newWidth}x{$newHeight}");
        
        // Создаем финальное изображение
        $finalImage = imagecreatetruecolor($newWidth, $newHeight);
        $white = imagecolorallocate($finalImage, 255, 255, 255);
        imagefill($finalImage, 0, 0, $white);
        
        imagecopyresampled(
            $finalImage, $croppedImage,
            0, 0, 0, 0,
            $newWidth, $newHeight, $cropWidth, $cropHeight
        );
        
        // Сохраняем
        $this->createDirectory($fullDestPath);
        $result = imagejpeg($finalImage, $fullDestPath, 85);
        
        if ($result) {
            $originalSize = filesize($fullSourcePath);
            $finalSize = filesize($fullDestPath);
            $savedPercent = round(($originalSize - $finalSize) / $originalSize * 100, 2);
            
            Log::info("🎉 ULTRA SUCCESS!");
            Log::info("   📍 {$destinationPath}");
            Log::info("   📏 {$newWidth}x{$newHeight}");
            Log::info("   💰 Saved: {$savedPercent}%");
            Log::info("   ✂️ Trim: {$srcWidth}x{$srcHeight} -> {$cropWidth}x{$cropHeight}");
            Log::info("   🛠️ Method: {$trimMethod}");
        }
        
        imagedestroy($croppedImage);
        imagedestroy($finalImage);
        
        return $result;
        
    } catch (\Exception $e) {
        Log::error("💥 ULTRA ERROR: " . $e->getMessage());
        Log::error("💥 Stack trace: " . $e->getTraceAsString());
        return false;
    }
}
}