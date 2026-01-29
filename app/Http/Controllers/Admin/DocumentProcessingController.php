<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentPage;
use App\Models\DocumentImage;
use App\Services\AdvancedImageExtractionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;

class DocumentProcessingController extends Controller
{
    protected $imageExtractor;
    protected $pdfParser;
    
    public function __construct()
    {
        $this->imageExtractor = new AdvancedImageExtractionService();
        $this->pdfParser = new PdfParser();
    }
    
    /**
     * Страница управления обработкой документов
     */
    public function index()
    {
        $documents = Document::with(['carModel.brand', 'category'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        $stats = [
            'total' => Document::count(),
            'uploaded' => Document::where('status', 'uploaded')->count(),
            'parsed' => Document::where('is_parsed', true)->count(),
            'processing' => Document::where('status', 'processing')->count(),
            'preview_created' => Document::where('status', 'preview_created')->count(),
            'errors' => Document::where('status', 'parse_error')->count(),
        ];
        
        return view('admin.documents.processing', compact('documents', 'stats'));
    }
    
    /**
     * Расширенная обработка конкретного документа
     */
    public function advancedProcessing($id)
    {
        try {
            $document = Document::with(['carModel.brand', 'category'])
                ->findOrFail($id);
            
            // Получаем предпросмотр страниц
            $previewPages = DocumentPage::where('document_id', $id)
                ->where('is_preview', true)
                ->with('images')
                ->orderBy('page_number')
                ->get();
            
            // Полная статистика
            $stats = $this->getDocumentStats($id);
            
            return view('admin.documents.processing_advanced', [
                'document' => $document,
                'previewPages' => $previewPages,
                'stats' => $stats
            ]);
            
        } catch (\Exception $e) {
            Log::error("Ошибка загрузки страницы расширенной обработки: " . $e->getMessage());
            return redirect()->route('admin.documents.processing.index')
                ->with('error', 'Документ не найден: ' . $e->getMessage());
        }
    }
    
    /**
     * Создание предпросмотра документа (первые 5 страниц)
     */
    public function createPreview(Request $request, $id)
    {
        try {
            $document = Document::findOrFail($id);
            
            Log::info("Запрос на создание предпросмотра документа {$id}");
            
            // Проверяем, не обрабатывается ли уже документ
            if ($document->status === 'processing') {
                return redirect()->route('admin.documents.processing.advanced', $id)
                    ->with('error', 'Документ уже обрабатывается');
            }
            
            $filePath = storage_path('app/' . $document->file_path);
            
            if (!file_exists($filePath)) {
                return redirect()->route('admin.documents.processing.advanced', $id)
                    ->with('error', 'Файл не найден: ' . $document->file_path);
            }
            
            // Получаем общее количество страниц
            $totalPages = $this->getPdfPageCount($filePath);
            $pagesToParse = min(5, $totalPages);
            
            // Обновляем статус
            $document->update([
                'status' => 'processing',
                'parsing_progress' => 0,
                'total_pages' => $totalPages
            ]);
            
            // Очищаем старый предпросмотр
            DocumentPage::where('document_id', $id)
                ->where('is_preview', true)
                ->delete();
            DocumentImage::where('document_id', $id)
                ->where('is_preview', true)
                ->delete();
            
            $totalWords = 0;
            $totalImages = 0;
            $allText = '';
            
            // Парсим первые N страниц
            for ($pageNum = 1; $pageNum <= $pagesToParse; $pageNum++) {
                try {
                    // Извлекаем текст
                    $text = $this->extractPdfText($filePath, $pageNum);
                    $cleanedText = $this->cleanText($text);
                    
                    // Извлекаем изображения
                    $images = $this->imageExtractor->extractImagesUniversal($document, $filePath, $pageNum, true);
                    
                    // Создаем страницу
                    $page = DocumentPage::create([
                        'document_id' => $document->id,
                        'page_number' => $pageNum,
                        'content' => $this->formatContent($cleanedText),
                        'content_text' => $cleanedText,
                        'word_count' => str_word_count($cleanedText),
                        'character_count' => strlen($cleanedText),
                        'paragraph_count' => $this->countParagraphs($cleanedText),
                        'tables_count' => $this->countTables($cleanedText),
                        'section_title' => $this->detectSectionTitle($cleanedText),
                        'metadata' => json_encode($this->extractMetadata($cleanedText)),
                        'is_preview' => true,
                        'parsing_quality' => $this->calculatePageQuality($cleanedText, $images),
                        'status' => 'parsed',
                        'has_images' => !empty($images)
                    ]);
                    
                    // Сохраняем изображения
                    foreach ($images as $imageData) {
                        DocumentImage::create(array_merge($imageData, [
                            'document_id' => $document->id,
                            'page_id' => $page->id,
                            'page_number' => $pageNum,
                            'is_preview' => true,
                            'status' => 'extracted'
                        ]));
                        $totalImages++;
                    }
                    
                    $totalWords += str_word_count($cleanedText);
                    $allText .= $cleanedText . "\n\n";
                    
                    // Обновляем прогресс
                    $progress = ($pageNum / $pagesToParse) * 100;
                    $document->update(['parsing_progress' => $progress]);
                    
                } catch (\Exception $e) {
                    Log::error("Ошибка парсинга страницы {$pageNum}: " . $e->getMessage());
                    continue;
                }
            }
            
            // Обновляем документ
            $document->update([
                'status' => 'preview_created',
                'content_text' => $allText,
                'content' => $this->formatContent($allText),
                'word_count' => $totalWords,
                'parsing_quality' => $this->calculateDocumentQuality($allText),
                'parsing_progress' => 100,
                'is_parsed' => false,
                'parsed_at' => now()
            ]);
            
            Log::info("Предпросмотр создан для документа {$id}: страниц {$pagesToParse}, слов {$totalWords}, изображений {$totalImages}");
            
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('success', "Предпросмотр создан успешно! Обработано страниц: {$pagesToParse}, слов: {$totalWords}, изображений: {$totalImages}");
            
        } catch (\Exception $e) {
            Log::error("Ошибка создания предпросмотра документа {$id}: " . $e->getMessage());
            
            if (isset($document)) {
                $document->update(['status' => 'parse_error']);
            }
            
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('error', 'Ошибка создания предпросмотра: ' . $e->getMessage());
        }
    }
    
    /**
     * Полный парсинг документа
     */
    public function parseFull(Request $request, $id)
    {
        try {
            $document = Document::findOrFail($id);
            
            Log::info("Запрос на полный парсинг документа {$id}");
            
            // Проверяем, не обрабатывается ли уже документ
            if ($document->status === 'processing') {
                return redirect()->route('admin.documents.processing.advanced', $id)
                    ->with('error', 'Документ уже обрабатывается');
            }
            
            $filePath = storage_path('app/' . $document->file_path);
            
            if (!file_exists($filePath)) {
                return redirect()->route('admin.documents.processing.advanced', $id)
                    ->with('error', 'Файл не найден: ' . $document->file_path);
            }
            
            // Получаем общее количество страниц
            $totalPages = $this->getPdfPageCount($filePath);
            
            // Обновляем статус
            $document->update([
                'status' => 'processing',
                'parsing_progress' => 0,
                'total_pages' => $totalPages
            ]);
            
            // Очищаем все старые данные
            DocumentPage::where('document_id', $id)->delete();
            DocumentImage::where('document_id', $id)->delete();
            
            $totalWords = 0;
            $totalImages = 0;
            $allText = '';
            
            // Парсим все страницы
            for ($pageNum = 1; $pageNum <= $totalPages; $pageNum++) {
                try {
                    // Извлекаем текст
                    $text = $this->extractPdfText($filePath, $pageNum);
                    $cleanedText = $this->cleanText($text);
                    
                    // Извлекаем изображения
                    $images = $this->imageExtractor->extractImagesUniversal($document, $filePath, $pageNum, false);
                    
                    // Создаем страницу
                    $page = DocumentPage::create([
                        'document_id' => $document->id,
                        'page_number' => $pageNum,
                        'content' => $this->formatContent($cleanedText),
                        'content_text' => $cleanedText,
                        'word_count' => str_word_count($cleanedText),
                        'character_count' => strlen($cleanedText),
                        'paragraph_count' => $this->countParagraphs($cleanedText),
                        'tables_count' => $this->countTables($cleanedText),
                        'section_title' => $this->detectSectionTitle($cleanedText),
                        'metadata' => json_encode($this->extractMetadata($cleanedText)),
                        'is_preview' => false,
                        'parsing_quality' => $this->calculatePageQuality($cleanedText, $images),
                        'status' => 'parsed',
                        'has_images' => !empty($images)
                    ]);
                    
                    // Сохраняем изображения
                    foreach ($images as $imageData) {
                        DocumentImage::create(array_merge($imageData, [
                            'document_id' => $document->id,
                            'page_id' => $page->id,
                            'page_number' => $pageNum,
                            'is_preview' => false,
                            'status' => 'extracted'
                        ]));
                        $totalImages++;
                    }
                    
                    $totalWords += str_word_count($cleanedText);
                    $allText .= $cleanedText . "\n\n";
                    
                    // Обновляем прогресс каждые 5 страниц
                    if ($pageNum % 5 === 0 || $pageNum === $totalPages) {
                        $progress = ($pageNum / $totalPages) * 100;
                        $document->update(['parsing_progress' => $progress]);
                        Log::info("Прогресс парсинга документа {$id}: {$progress}% (страница {$pageNum}/{$totalPages})");
                    }
                    
                } catch (\Exception $e) {
                    Log::error("Ошибка парсинга страницы {$pageNum}: " . $e->getMessage());
                    
                    // Создаем страницу с ошибкой
                    DocumentPage::create([
                        'document_id' => $document->id,
                        'page_number' => $pageNum,
                        'content' => '<div class="alert alert-danger">Ошибка парсинга страницы: ' . htmlspecialchars($e->getMessage()) . '</div>',
                        'content_text' => 'Ошибка парсинга страницы: ' . $e->getMessage(),
                        'word_count' => 0,
                        'character_count' => 0,
                        'paragraph_count' => 0,
                        'tables_count' => 0,
                        'section_title' => null,
                        'metadata' => json_encode(['error' => $e->getMessage()]),
                        'is_preview' => false,
                        'parsing_quality' => 0,
                        'status' => 'error',
                        'has_images' => false
                    ]);
                    
                    continue;
                }
            }
            
            // Обновляем документ
            $document->update([
                'status' => 'parsed',
                'content_text' => $allText,
                'content' => $this->formatContent($allText),
                'word_count' => $totalWords,
                'parsing_quality' => $this->calculateDocumentQuality($allText),
                'parsing_progress' => 100,
                'is_parsed' => true,
                'parsed_at' => now()
            ]);
            
            Log::info("Полный парсинг завершен для документа {$id}: страниц {$totalPages}, слов {$totalWords}, изображений {$totalImages}");
            
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('success', "Документ полностью распарсен! Обработано страниц: {$totalPages}, слов: {$totalWords}, изображений: {$totalImages}");
            
        } catch (\Exception $e) {
            Log::error("Ошибка полного парсинга документа {$id}: " . $e->getMessage());
            
            if (isset($document)) {
                $document->update([
                    'status' => 'parse_error',
                    'content_text' => 'Ошибка парсинга: ' . substr($e->getMessage(), 0, 500)
                ]);
            }
            
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('error', 'Ошибка парсинга: ' . $e->getMessage());
        }
    }
    
    /**
     * Парсинг только изображений из документа
     */
    public function parseImagesOnly(Request $request, $id)
    {
        try {
            $document = Document::findOrFail($id);
            
            Log::info("Запрос на парсинг только изображений документа {$id}");
            
            $filePath = storage_path('app/' . $document->file_path);
            
            if (!file_exists($filePath)) {
                return redirect()->route('admin.documents.processing.advanced', $id)
                    ->with('error', 'Файл не найден: ' . $document->file_path);
            }
            
            // Обновляем статус
            $document->update(['status' => 'processing']);
            
            $totalImages = 0;
            $totalPages = $this->getPdfPageCount($filePath);
            $pagesProcessed = 0;
            
            // Парсим изображения для каждой страницы
            for ($pageNum = 1; $pageNum <= $totalPages; $pageNum++) {
                try {
                    $images = $this->imageExtractor->extractImagesUniversal($document, $filePath, $pageNum, false);
                    $totalImages += count($images);
                    
                    Log::info("Страница {$pageNum}: извлечено " . count($images) . " изображений");
                    
                    $pagesProcessed++;
                    
                    // Обновляем прогресс
                    $progress = ($pageNum / $totalPages) * 100;
                    $document->update(['parsing_progress' => $progress]);
                    
                } catch (\Exception $e) {
                    Log::error("Ошибка парсинга изображений страницы {$pageNum}: " . $e->getMessage());
                    continue;
                }
            }
            
            $document->update([
                'status' => 'parsed',
                'parsing_progress' => 100,
                'total_pages' => $totalPages
            ]);
            
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('success', "Извлечение изображений завершено! Страниц: {$pagesProcessed}, изображений: {$totalImages}");
            
        } catch (\Exception $e) {
            Log::error("Ошибка парсинга изображений документа {$id}: " . $e->getMessage());
            
            if (isset($document)) {
                $document->update(['status' => 'parse_error']);
            }
            
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('error', 'Ошибка извлечения изображений: ' . $e->getMessage());
        }
    }
    
    /**
     * Парсинг одной конкретной страницы
     */
    public function parseSinglePage(Request $request, $id)
    {
        try {
            $document = Document::findOrFail($id);
            $pageNumber = $request->input('page', 1);
            
            Log::info("Запрос на парсинг страницы {$pageNumber} документа {$id}");
            
            $filePath = storage_path('app/' . $document->file_path);
            
            if (!file_exists($filePath)) {
                return redirect()->route('admin.documents.processing.advanced', $id)
                    ->with('error', 'Файл не найден');
            }
            
            // Парсим текст страницы
            $text = $this->extractPdfText($filePath, $pageNumber);
            $cleanedText = $this->cleanText($text);
            
            // Извлекаем изображения
            $images = $this->imageExtractor->extractImagesUniversal($document, $filePath, $pageNumber, false);
            
            // Создаем или обновляем страницу
            $page = DocumentPage::updateOrCreate(
                [
                    'document_id' => $document->id,
                    'page_number' => $pageNumber,
                    'is_preview' => false
                ],
                [
                    'content' => $this->formatContent($cleanedText),
                    'content_text' => $cleanedText,
                    'word_count' => str_word_count($cleanedText),
                    'character_count' => strlen($cleanedText),
                    'paragraph_count' => $this->countParagraphs($cleanedText),
                    'tables_count' => $this->countTables($cleanedText),
                    'section_title' => $this->detectSectionTitle($cleanedText),
                    'metadata' => json_encode($this->extractMetadata($cleanedText)),
                    'parsing_quality' => $this->calculatePageQuality($cleanedText, $images),
                    'status' => 'parsed',
                    'has_images' => !empty($images)
                ]
            );
            
            // Сохраняем изображения
            foreach ($images as $imageData) {
                DocumentImage::updateOrCreate(
                    [
                        'document_id' => $document->id,
                        'page_id' => $page->id,
                        'position' => $imageData['position'] ?? 1,
                        'is_preview' => false
                    ],
                    array_merge($imageData, [
                        'document_id' => $document->id,
                        'page_id' => $page->id,
                        'page_number' => $pageNumber,
                        'is_preview' => false,
                        'status' => 'extracted'
                    ])
                );
            }
            
            // Обновляем статистику документа
            $this->updateDocumentStats($document);
            
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('success', "Страница {$pageNumber} обработана! Текст: " . str_word_count($cleanedText) . " слов, изображений: " . count($images));
            
        } catch (\Exception $e) {
            Log::error("Ошибка парсинга страницы документа {$id}: " . $e->getMessage());
            
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('error', 'Ошибка обработки страницы: ' . $e->getMessage());
        }
    }
    
    /**
     * Сброс статуса документа
     */
    public function resetStatus(Request $request, $id)
    {
        try {
            $document = Document::findOrFail($id);
            
            Log::info("Сброс статуса документа {$id}");
            
            // Начинаем транзакцию
            DB::beginTransaction();
            
            try {
                $document->update([
                    'status' => 'uploaded',
                    'is_parsed' => false,
                    'search_indexed' => false,
                    'content_text' => null,
                    'content' => null,
                    'keywords' => null,
                    'keywords_text' => null,
                    'detected_section' => null,
                    'detected_system' => null,
                    'detected_component' => null,
                    'parsing_quality' => 0,
                    'total_pages' => 0,
                    'word_count' => 0,
                    'parsing_progress' => 0,
                    'parsed_at' => null
                ]);
                
                // Очищаем связанные данные
                $pages = DocumentPage::where('document_id', $id)->get();
                
                foreach ($pages as $page) {
                    // Удаляем изображения страницы
                    DocumentImage::where('page_id', $page->id)->delete();
                    // Удаляем страницу
                    $page->delete();
                }
                
                // Удаляем оставшиеся изображения
                DocumentImage::where('document_id', $id)->delete();
                
                // Удаляем физические файлы изображений
                $imageDir = storage_path("app/documents/{$id}");
                if (is_dir($imageDir)) {
                    $this->deleteDirectory($imageDir);
                }
                
                DB::commit();
                
                return redirect()->route('admin.documents.processing.advanced', $id)
                    ->with('success', 'Статус документа сброшен. Все данные удалены. Можно начать обработку заново.');
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::error("Ошибка сброса статуса документа {$id}: " . $e->getMessage());
            
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('error', 'Ошибка сброса статуса: ' . $e->getMessage());
        }
    }
    
    /**
     * Удалить предпросмотр
     */
    public function deletePreview(Request $request, $id)
    {
        try {
            // Находим страницы предпросмотра
            $previewPages = DocumentPage::where('document_id', $id)
                ->where('is_preview', true)
                ->get();
            
            $deletedPages = 0;
            $deletedImages = 0;
            
            foreach ($previewPages as $page) {
                // Удаляем изображения страницы
                $images = DocumentImage::where('page_id', $page->id)->get();
                foreach ($images as $image) {
                    // Удаляем физические файлы
                    if (!empty($image->path)) {
                        $fullPath = storage_path("app/" . $image->path);
                        if (file_exists($fullPath)) {
                            unlink($fullPath);
                        }
                    }
                    if (!empty($image->thumbnail_path)) {
                        $thumbPath = storage_path("app/" . $image->thumbnail_path);
                        if (file_exists($thumbPath)) {
                            unlink($thumbPath);
                        }
                    }
                    $image->delete();
                    $deletedImages++;
                }
                
                // Удаляем страницу
                $page->delete();
                $deletedPages++;
            }
            
            // Удаляем оставшиеся изображения предпросмотра
            DocumentImage::where('document_id', $id)
                ->where('is_preview', true)
                ->delete();
            
            // Обновляем статус документа
            Document::where('id', $id)->update([
                'status' => 'uploaded',
                'parsing_quality' => 0,
                'total_pages' => DB::raw('COALESCE(total_pages, 0)'),
                'word_count' => 0
            ]);
                
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('success', "Предпросмотр удален. Удалено страниц: {$deletedPages}, изображений: {$deletedImages}");
            
        } catch (\Exception $e) {
            Log::error("Ошибка удаления предпросмотра документа {$id}: " . $e->getMessage());
            
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('error', 'Ошибка удаления предпросмотра: ' . $e->getMessage());
        }
    }
    
    /**
     * Просмотр изображений документа
     */
    public function viewImages(Request $request, $id)
    {
        try {
            $document = Document::findOrFail($id);
            
            $images = DocumentImage::where('document_id', $id)
                ->with('page')
                ->orderBy('page_number')
                ->orderBy('position')
                ->paginate(20);
            
            $stats = [
                'total_images' => DocumentImage::where('document_id', $id)->count(),
                'pages_with_images' => DocumentImage::where('document_id', $id)->distinct('page_number')->count('page_number'),
                'total_size' => DocumentImage::where('document_id', $id)->sum('size') / 1024 / 1024, // в MB
            ];
            
            return view('admin.documents.images', compact('document', 'images', 'stats'));
            
        } catch (\Exception $e) {
            Log::error("Ошибка загрузки изображений документа {$id}: " . $e->getMessage());
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('error', 'Ошибка загрузки изображений: ' . $e->getMessage());
        }
    }
    
    /**
     * Тестирование извлечения изображений
     */
    public function testImageExtraction(Request $request, $id)
    {
        try {
            $document = Document::findOrFail($id);
            
            $filePath = storage_path('app/' . $document->file_path);
            
            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Файл не найден'
                ], 404);
            }
            
            $pageNumber = $request->input('page', 1);
            
            Log::info("Тестирование извлечения изображений для документа {$id}, страница {$pageNumber}");
            
            // Извлекаем изображения
            $images = $this->imageExtractor->extractImagesUniversal($document, $filePath, $pageNumber, true);
            
            return response()->json([
                'success' => true,
                'message' => 'Извлечение изображений завершено',
                'document_id' => $document->id,
                'page_number' => $pageNumber,
                'images_count' => count($images),
                'images' => array_map(function($img) {
                    return [
                        'filename' => $img['filename'] ?? null,
                        'url' => $img['url'] ?? null,
                        'thumbnail_url' => $img['thumbnail_url'] ?? null,
                        'width' => $img['width'] ?? 0,
                        'height' => $img['height'] ?? 0,
                        'size' => $img['size'] ?? 0,
                        'description' => $img['description'] ?? null
                    ];
                }, $images)
            ]);
            
        } catch (\Exception $e) {
            Log::error("Ошибка тестирования извлечения изображений: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Получить прогресс обработки (AJAX)
     */
    public function getProcessingProgress($id)
    {
        try {
            $document = Document::findOrFail($id);
            
            $pagesProcessed = DocumentPage::where('document_id', $id)
                ->where('is_preview', false)
                ->count();
                
            $imagesProcessed = DocumentImage::where('document_id', $id)
                ->where('is_preview', false)
                ->count();
            
            // Если статус уже 'parsed' или 'preview_created', значит обработка завершена
            if (in_array($document->status, ['parsed', 'preview_created'])) {
                return response()->json([
                    'success' => true,
                    'status' => $document->status,
                    'progress' => 100,
                    'pages_processed' => $pagesProcessed,
                    'total_pages' => $document->total_pages ?? 0,
                    'images_processed' => $imagesProcessed,
                    'estimated_time' => 'Завершено',
                    'is_parsed' => $document->is_parsed,
                    'parsing_quality' => $document->parsing_quality,
                    'message' => $document->status === 'parsed' ? 'Документ распарсен' : 'Создан предпросмотр'
                ]);
            }
            
            // Если статус 'processing', рассчитываем прогресс
            $progress = $document->parsing_progress ?? 0;
            
            return response()->json([
                'success' => true,
                'status' => $document->status,
                'progress' => round($progress, 1),
                'pages_processed' => $pagesProcessed,
                'total_pages' => $document->total_pages ?? 0,
                'images_processed' => $imagesProcessed,
                'estimated_time' => $this->calculateEstimatedTime($document, $progress),
                'is_parsed' => $document->is_parsed,
                'parsing_quality' => $document->parsing_quality,
                'message' => 'Идет обработка...'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения прогресса: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Получить список страниц документа (AJAX)
     */
    public function getDocumentPages($id)
    {
        try {
            $pages = DocumentPage::where('document_id', $id)
                ->with(['images' => function($query) {
                    $query->orderBy('position');
                }])
                ->orderBy('page_number')
                ->get();
                
            $document = Document::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'pages' => $pages,
                'total_pages' => $document->total_pages ?? 0,
                'document' => [
                    'id' => $document->id,
                    'title' => $document->title,
                    'status' => $document->status,
                    'is_parsed' => $document->is_parsed,
                    'parsing_quality' => $document->parsing_quality
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения страниц: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Получить статистику по документу
     */
    public function getDocumentStats($id)
    {
        try {
            $document = Document::findOrFail($id);
            
            // Статусы в читаемом формате
            $statusLabels = [
                'uploaded' => 'Загружен',
                'processing' => 'В обработке',
                'parsed' => 'Распарсен',
                'preview_created' => 'Предпросмотр создан',
                'parse_error' => 'Ошибка парсинга'
            ];
            
            $stats = [
                'pages_count' => DocumentPage::where('document_id', $id)->count(),
                'total_pages' => $document->total_pages ?? 0,
                'images_count' => DocumentImage::where('document_id', $id)->count(),
                'words_count' => $document->word_count ?? 0,
                'characters_count' => $document->content_text ? strlen($document->content_text) : 0,
                'paragraphs_count' => DocumentPage::where('document_id', $id)->sum('paragraph_count'),
                'tables_count' => DocumentPage::where('document_id', $id)->sum('tables_count'),
                'last_parsed' => $document->parsed_at ? $document->parsed_at->format('d.m.Y H:i') : null,
                'parsing_quality' => $document->parsing_quality ?? 0,
                'is_parsed' => $document->is_parsed,
                'search_indexed' => $document->search_indexed,
                'file_size' => $this->getFileSize($document),
                'file_type' => $document->file_type,
                'status' => $document->status,
                'status_label' => $statusLabels[$document->status] ?? $document->status
            ];
            
            return $stats;
            
        } catch (\Exception $e) {
            Log::error("Ошибка получения статистики документа {$id}: " . $e->getMessage());
            
            // Возвращаем пустую статистику при ошибке
            return [
                'pages_count' => 0,
                'total_pages' => 0,
                'images_count' => 0,
                'words_count' => 0,
                'characters_count' => 0,
                'paragraphs_count' => 0,
                'tables_count' => 0,
                'last_parsed' => null,
                'parsing_quality' => 0,
                'is_parsed' => false,
                'search_indexed' => false,
                'file_size' => '0 B',
                'file_type' => 'unknown',
                'status' => 'error',
                'status_label' => 'Ошибка'
            ];
        }
    }
    
    /**
     * Вспомогательные методы
     */
    
    private function getFileSize(Document $document): string
    {
        $filePath = storage_path('app/' . $document->file_path);
        if (!file_exists($filePath)) {
            return '0 B';
        }
        
        $bytes = filesize($filePath);
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
    
    private function calculateEstimatedTime(Document $document, float $progress): string
    {
        if ($progress >= 100 || $document->status !== 'processing') {
            return 'Завершено';
        }
        
        // Если только начали, показываем "Рассчитывается..."
        if ($progress <= 5) {
            return 'Рассчитывается...';
        }
        
        // Простой расчет оставшегося времени
        if ($document->updated_at) {
            $elapsed = now()->diffInSeconds($document->updated_at);
            if ($progress > 0) {
                $totalEstimated = ($elapsed / $progress) * 100;
                $remaining = $totalEstimated - $elapsed;
                
                if ($remaining < 60) {
                    return ceil($remaining) . ' сек';
                } elseif ($remaining < 3600) {
                    return ceil($remaining / 60) . ' мин';
                } else {
                    return number_format($remaining / 3600, 1) . ' ч';
                }
            }
        }
        
        return 'Рассчитывается...';
    }
    
    private function getPdfPageCount(string $filePath): int
    {
        try {
            $pdf = $this->pdfParser->parseFile($filePath);
            $pages = $pdf->getPages();
            return count($pages);
        } catch (\Exception $e) {
            Log::warning("Не удалось определить количество страниц через Smalot: " . $e->getMessage());
        }
        
        // Альтернативный способ через pdfinfo
        if ($this->commandExists('pdfinfo')) {
            $command = "pdfinfo \"{$filePath}\" 2>&1";
            $output = shell_exec($command);
            
            if (preg_match('/Pages:\s*(\d+)/', $output, $matches)) {
                return (int)$matches[1];
            }
        }
        
        return 1;
    }
    
    private function commandExists(string $command): bool
    {
        $which = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'where' : 'which';
        $process = new \Symfony\Component\Process\Process([$which, $command]);
        $process->run();
        return $process->isSuccessful();
    }
    
    private function extractPdfText(string $filePath, int $pageNumber): string
    {
        try {
            $pdf = $this->pdfParser->parseFile($filePath);
            $pages = $pdf->getPages();
            
            if (isset($pages[$pageNumber - 1])) {
                return $pages[$pageNumber - 1]->getText();
            }
        } catch (\Exception $e) {
            Log::warning("Ошибка извлечения текста страницы {$pageNumber}: " . $e->getMessage());
        }
        
        return '';
    }
    
    private function cleanText(string $text): string
    {
        if (empty($text)) {
            return '';
        }
        
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = preg_replace('/[^\x20-\x7E\xA0-\xFF\x{0400}-\x{04FF}\.,;:!?\-\(\)\[\]\{\}\n\r\t]/u', ' ', $text);
        
        return trim($text);
    }
    
    private function formatContent(string $text): string
    {
        $paragraphs = explode("\n\n", trim($text));
        $formatted = '';
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (!empty($paragraph)) {
                if (strlen($paragraph) < 150 && preg_match('/^[A-ZА-Я0-9\s\.\-:]+$/u', $paragraph)) {
                    $formatted .= '<h4 class="document-heading">' . htmlspecialchars($paragraph) . '</h4>';
                } else {
                    $formatted .= '<p class="document-paragraph">' . nl2br(htmlspecialchars($paragraph)) . '</p>';
                }
            }
        }
        
        return $formatted;
    }
    
    private function countParagraphs(string $text): int
    {
        $paragraphs = preg_split('/\n\s*\n/', $text);
        return count(array_filter($paragraphs, function($p) {
            return trim($p) !== '';
        }));
    }
    
    private function countTables(string $text): int
    {
        $lines = explode("\n", $text);
        $tableCount = 0;
        $inTable = false;
        
        foreach ($lines as $line) {
            if (preg_match('/^\s*(\|.+\|)\s*$/', $line) || 
                preg_match('/^\s*(\+.+\+)\s*$/', $line)) {
                if (!$inTable) {
                    $tableCount++;
                    $inTable = true;
                }
            } else {
                $inTable = false;
            }
        }
        
        return $tableCount;
    }
    
    private function detectSectionTitle(string $text): ?string
    {
        $lines = explode("\n", trim($text));
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (strlen($line) > 10 && strlen($line) < 200) {
                if (preg_match('/^[A-ZА-Я][A-ZА-Яa-zа-я0-9\s\.\-:]+$/u', $line)) {
                    if (!preg_match('/(стр\.|страница|page|\d+\s*из\s*\d+)/iu', $line)) {
                        return $line;
                    }
                }
            }
        }
        
        return null;
    }
    
    private function extractMetadata(string $text): array
    {
        $metadata = [];
        
        // Поиск номеров деталей
        preg_match_all('/[A-Z]{1,5}[\-\s]?\d{4,10}[A-Z]?/i', $text, $partNumbers);
        if (!empty($partNumbers[0])) {
            $metadata['part_numbers'] = array_unique($partNumbers[0]);
        }
        
        return $metadata;
    }
    
    private function calculatePageQuality(string $text, array $images): float
    {
        $quality = 0.3;
        $wordCount = str_word_count($text);
        
        if ($wordCount > 100) $quality += 0.2;
        elseif ($wordCount > 50) $quality += 0.1;
        elseif ($wordCount > 20) $quality += 0.05;
        
        if ($this->detectSectionTitle($text)) {
            $quality += 0.15;
        }
        
        if (!empty($images)) {
            $quality += 0.15;
        }
        
        return min(1.0, $quality);
    }
    
    private function calculateDocumentQuality(string $fullText): float
    {
        if (empty($fullText)) {
            return 0.0;
        }
        
        $quality = 0.3;
        $totalWords = str_word_count($fullText);
        
        if ($totalWords > 1000) {
            $quality += 0.3;
        } elseif ($totalWords > 500) {
            $quality += 0.2;
        } elseif ($totalWords > 200) {
            $quality += 0.1;
        }
        
        return min(1.0, $quality);
    }
    
    private function updateDocumentStats(Document $document): void
    {
        $pages = DocumentPage::where('document_id', $document->id)->where('is_preview', false)->get();
        
        $totalWords = $pages->sum('word_count');
        $totalPages = $pages->count();
        $totalImages = DocumentImage::where('document_id', $document->id)->where('is_preview', false)->count();
        
        // Объединяем весь текст
        $allText = $pages->pluck('content_text')->filter()->implode("\n\n");
        
        $document->update([
            'word_count' => $totalWords,
            'total_pages' => $totalPages,
            'content_text' => $allText,
            'content' => $this->formatContent($allText),
            'is_parsed' => $totalPages > 0,
            'status' => $totalPages > 0 ? 'parsed' : $document->status,
            'parsing_quality' => $this->calculateDocumentQuality($allText)
        ]);
    }
    
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
}