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
            
            // Удаляем старые данные
            DocumentPage::where('document_id', $id)->delete();
            DocumentImage::where('document_id', $id)->delete();
            
            // Парсим весь PDF документ
            $result = $this->parsePdfDocument($id, $filePath);
            
            if ($result['success']) {
                // Извлекаем изображения
                $imagesResult = $this->extractImagesWithPages($id, $filePath);
                
                // Обновляем статистику по изображениям
                $pagesWithImages = DocumentPage::where('document_id', $id)
                    ->where('has_images', true)
                    ->count();
                
                $totalImages = DocumentImage::where('document_id', $id)->count();
                
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
                    $message .= "📖 Страниц с изображениями: {$imagesResult['pages_with_images']}";
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
            Log::error('Full parse error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            
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
                    'success' => false,
                    'error' => 'Изображения не найдены в PDF документе'
                ];
            }
            
            // Получаем все страницы документа
            $pages = DocumentPage::where('document_id', $documentId)
                ->orderBy('page_number')
                ->get();
            
            if ($pages->isEmpty()) {
                // Если страниц нет, создаем хотя бы одну
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
            $totalImages = count($images);
            $totalPages = $pages->count();
            
            // Распределяем изображения по страницам
            foreach ($images as $index => $imageData) {
                try {
                    // Проверяем наличие данных изображения
                    if (!isset($imageData['path']) || empty($imageData['path'])) {
                        Log::warning("Image data missing path at index {$index}");
                        continue;
                    }
                    
                    // Проверяем существует ли файл
                    if (!Storage::disk('public')->exists($imageData['path'])) {
                        Log::warning("Image file not found: {$imageData['path']}");
                        continue;
                    }
                    
                    // Определяем номер страницы
                    $pageNumber = $this->calculatePageNumberForImage($index, $totalImages, $totalPages);
                    $pageId = $pageMapping[$pageNumber] ?? null;
                    
                    $filename = basename($imageData['path']);
                    $baseName = pathinfo($filename, PATHINFO_FILENAME);
                    $extension = pathinfo($filename, PATHINFO_EXTENSION);
                    
                    // 1. Создаем миниатюру (300×200)
                    $thumbnailFilename = "thumb_{$baseName}.{$extension}";
                    $thumbnailPath = $thumbsDir . '/' . $thumbnailFilename;
                    
                    $thumbnailCreated = $this->screenshotService->createThumbnail(
                        $imageData['path'], 
                        $thumbnailPath, 
                        300, 
                        200
                    );
                    
                    // 2. Создаем скриншот с обрезкой белого (800×600)
                    $screenshotFilename = "screen_{$baseName}.{$extension}";
                    $screenshotPath = $screenshotsDir . '/' . $screenshotFilename;
                    
                    $screenshotCreated = $this->screenshotService->createScreenshot(
                        $imageData['path'], 
                        $screenshotPath, 
                        800, 
                        600
                    );
                    
                    // 3. Анализируем изображение
                    $analysis = $this->screenshotService->analyzeImage($imageData['path']);
                    
                    // Получаем размеры файлов
                    $originalSize = Storage::disk('public')->size($imageData['path']);
                    $thumbnailSize = $thumbnailCreated ? Storage::disk('public')->size($thumbnailPath) : 0;
                    $screenshotSize = $screenshotCreated ? Storage::disk('public')->size($screenshotPath) : 0;
                    
                    // 4. Создаем запись изображения в БД
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
                        'original_width' => $analysis['width'] ?? ($imageData['width'] ?? null),
                        'original_height' => $analysis['height'] ?? ($imageData['height'] ?? null),
                        'size' => $originalSize,
                        'thumbnail_size' => $thumbnailSize,
                        'screenshot_size' => $screenshotSize,
                        'mime_type' => $analysis['mime'] ?? $this->getMimeTypeFromPath($imageData['path']),
                        'extension' => $analysis['extension'] ?? $extension,
                        'description' => $this->generateImageDescription($pageNumber, $index),
                        'position' => $index,
                        'is_preview' => ($index === 0),
                        'has_thumbnail' => $thumbnailCreated,
                        'has_screenshot' => $screenshotCreated,
                        'aspect_ratio' => $analysis['aspect_ratio'] ?? null,
                        'is_portrait' => $analysis['is_portrait'] ?? false,
                        'is_landscape' => $analysis['is_landscape'] ?? false,
                        'status' => 'active',
                        'processing_info' => json_encode([
                            'original_path' => $imageData['path'],
                            'thumbnail_created' => $thumbnailCreated,
                            'screenshot_created' => $screenshotCreated,
                            'created_at' => now()->toDateTimeString()
                        ])
                    ]);
                    
                    // 5. Обновляем страницу
                    if ($pageId) {
                        DocumentPage::where('id', $pageId)->update(['has_images' => true]);
                        
                        // Добавляем изображение в контент страницы
                        $this->addImageToPageContent($pageId, $documentImage);
                    }
                    
                    $savedCount++;
                    Log::info("Saved image {$savedCount}/{$totalImages}: {$filename}");
                    
                } catch (Exception $e) {
                    Log::error("Error saving image {$index}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                    continue;
                }
            }
            
            return [
                'success' => true,
                'images_count' => $savedCount,
                'thumbnails_created' => DocumentImage::where('document_id', $documentId)
                    ->where('has_thumbnail', true)
                    ->count(),
                'screenshots_created' => DocumentImage::where('document_id', $documentId)
                    ->where('has_screenshot', true)
                    ->count(),
                'pages_with_images' => DocumentPage::where('document_id', $documentId)
                    ->where('has_images', true)
                    ->count()
            ];
            
        } catch (Exception $e) {
            Log::error('Image extraction error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
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
}