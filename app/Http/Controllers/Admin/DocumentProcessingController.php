<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller as BaseController;

use App\Models\Document;
use App\Models\DocumentPage;
use App\Models\DocumentImage;
use App\Services\AdvancedDocumentParser;
use App\Services\DocumentIndexer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DocumentProcessingController extends BaseController
{
    protected $fileParser;
    protected $documentIndexer;
    
    public function __construct()
    {
         $this->documentParser = new AdvancedDocumentParser();
        $this->documentIndexer = new DocumentIndexer();
    }
    
    /**
     * Страница управления обработкой документов
     */
    public function index()
    {
        $documents = Document::with(['carModel.brand', 'category'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        // Статистика
        $stats = [
            'total' => Document::count(),
            'uploaded' => Document::where('status', 'uploaded')->count(),
            'parsed' => Document::where('is_parsed', true)->count(),
            'indexed' => Document::where('search_indexed', true)->count(),
            'processed' => Document::where('status', 'processed')->count(),
            'errors' => Document::whereIn('status', ['parse_error', 'index_error'])->count(),
        ];
        
        // Документы для парсинга
        $forParsing = Document::where('is_parsed', false)
            ->orWhere('status', 'uploaded')
            ->orWhere('status', 'parse_error')
            ->count();
        
        // Документы для индексации
        $forIndexing = Document::where('is_parsed', true)
            ->where('search_indexed', false)
            ->orWhere('status', 'parsed')
            ->orWhere('status', 'index_error')
            ->count();
        
        return view('admin.documents.processing', compact(
            'documents', 'stats', 'forParsing', 'forIndexing'
        ));
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
            
            // Статистика
            $parsedPagesCount = DocumentPage::where('document_id', $id)->count();
            $totalImagesCount = DocumentImage::where('document_id', $id)->count();
            $totalWordsCount = DocumentPage::where('document_id', $id)->sum('word_count');
            $totalTextSize = DocumentPage::where('document_id', $id)->sum('character_count');
            
            return view('admin.documents.processing_advanced', compact(
                'document',
                'previewPages',
                'parsedPagesCount',
                'totalImagesCount',
                'totalWordsCount',
                'totalTextSize'
            ));
            
        } catch (\Exception $e) {
            Log::error("Ошибка загрузки страницы расширенной обработки: " . $e->getMessage());
            return redirect()->route('admin.documents.processing')
                ->with('error', 'Документ не найден: ' . $e->getMessage());
        }
    }
    
    /**
     * Парсинг одного документа
     */
    public function parseDocument(Request $request, $id)
    {
        try {
            $document = Document::findOrFail($id);
            
            Log::info("Начало парсинга документа {$id}");
            
            // Меняем статус на обработку
            $document->update(['status' => 'processing']);
            
            // Парсим документ
            $result = $this->fileParser->parseDocument($document);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'content_length' => $result['content_length'],
                    'document_id' => $document->id
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }
            
        } catch (\Exception $e) {
            Log::error("Ошибка парсинга документа {$id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Индексация одного документа
     */
    public function indexDocument(Request $request, $id)
    {
        try {
            $document = Document::findOrFail($id);
            
            Log::info("Начало индексации документа {$id}");
            
            // Проверяем, что документ распарсен
            if (!$document->is_parsed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Документ не распарсен. Сначала выполните парсинг.'
                ], 400);
            }
            
            // Меняем статус на обработку
            $document->update(['status' => 'processing']);
            
            // Индексируем документ
            $result = $this->documentIndexer->indexDocument($document);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'document_id' => $document->id,
                    'section' => $result['section'] ?? null,
                    'keywords_count' => $result['keywords_count'] ?? 0
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }
            
        } catch (\Exception $e) {
            Log::error("Ошибка индексации документа {$id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Полная обработка документа
     */
    public function processDocument(Request $request, $id)
    {
        try {
            $document = Document::findOrFail($id);
            
            Log::info("Начало полной обработки документа {$id}");
            
            // 1. Парсинг
            $document->update(['status' => 'processing']);
            $parseResult = $this->fileParser->parseDocument($document);
            
            if (!$parseResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка парсинга: ' . $parseResult['message']
                ], 500);
            }
            
            // 2. Индексация
            $document->refresh();
            $indexResult = $this->documentIndexer->indexDocument($document);
            
            if (!$indexResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка индексации: ' . $indexResult['message']
                ], 500);
            }
            
            // 3. Финальный статус
            $document->update(['status' => 'processed']);
            
            return response()->json([
                'success' => true,
                'message' => 'Документ полностью обработан',
                'document_id' => $document->id
            ]);
            
        } catch (\Exception $e) {
            Log::error("Ошибка обработки документа {$id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Массовый парсинг документов
     */
   public function parseMultiple(Request $request)
{
    try {
        $documentIds = $request->input('document_ids', []);
        
        if (empty($documentIds)) {
            // Берем документы для парсинга с лимитом
            $documents = Document::readyForParsing()
                ->where('status', '!=', 'processing')
                ->limit(10) // Ограничиваем количество
                ->get();
            $documentIds = $documents->pluck('id')->toArray();
        }
        
        if (empty($documentIds)) {
            return response()->json([
                'success' => true,
                'message' => 'Нет документов для парсинга',
                'total' => 0,
                'success_count' => 0,
                'error_count' => 0
            ]);
        }
        
        Log::info("Начало массового парсинга: " . count($documentIds) . " документов");
        
        // Парсим с ограничением
        $results = $this->fileParser->parseMultiple($documentIds, 5);
        
        return response()->json([
            'success' => true,
            'message' => 'Массовый парсинг завершен',
            'total' => count($documentIds),
            'success_count' => count($results['success']),
            'error_count' => count($results['errors']),
            'limited' => count($documentIds) > 5 ? 'Обработано только 5 документов за раз' : null
        ]);
        
    } catch (\Exception $e) {
        Log::error("Ошибка массового парсинга: " . $e->getMessage());
        Log::error($e->getTraceAsString());
        
        return response()->json([
            'success' => false,
            'message' => 'Ошибка: ' . $e->getMessage()
        ], 500);
    }
}
    
    /**
     * Массовая индексация документов
     */
    public function indexMultiple(Request $request)
    {
        try {
            $documentIds = $request->input('document_ids', []);
            
            if (empty($documentIds)) {
                // Берем все документы для индексации
                $documents = Document::where('is_parsed', true)
                    ->where('search_indexed', false)
                    ->orWhere('status', 'parsed')
                    ->orWhere('status', 'index_error')
                    ->get();
                $documentIds = $documents->pluck('id')->toArray();
            }
            
            if (empty($documentIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Нет документов для индексации'
                ]);
            }
            
            Log::info("Массовая индексация документов: " . count($documentIds) . " шт.");
            
            // Индексируем
            $results = $this->documentIndexer->indexMultiple($documentIds);
            
            return response()->json([
                'success' => true,
                'message' => 'Индексация завершена',
                'total' => count($documentIds),
                'success_count' => count($results['success'] ?? []),
                'error_count' => count($results['errors'] ?? [])
            ]);
            
        } catch (\Exception $e) {
            Log::error("Ошибка массовой индексации: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Получение статуса документа
     */
    public function getStatus($id)
    {
        $document = Document::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'document' => $document,
            'status' => $document->status
        ]);
    }
    
    /**
     * Сброс статуса документа
     */
    public function resetStatus(Request $request, $id)
    {
        try {
            $document = Document::findOrFail($id);
            
            Log::info("Сброс статуса документа {$id}");
            
            $document->update([
                'status' => 'uploaded',
                'is_parsed' => false,
                'search_indexed' => false,
                'content_text' => null,
                'keywords' => null,
                'keywords_text' => null,
                'detected_section' => null,
                'detected_system' => null,
                'detected_component' => null,
                'parsing_quality' => 0,
            ]);
            
            // Очищаем n-граммы
            \Illuminate\Support\Facades\DB::table('document_ngrams')
                ->where('document_id', $id)
                ->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Статус документа сброшен',
                'document_id' => $document->id
            ]);
            
        } catch (\Exception $e) {
            Log::error("Ошибка сброса статуса документа {$id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ], 500);
        }
    } 


    /**
     * Предпросмотр документа (первые N страниц)
     */
    public function previewDocument(Request $request, $id)
    {
        try {
            $document = Document::findOrFail($id);
            $pagesCount = $request->get('pages', 5);
            
            // Проверяем, есть ли уже предпросмотр
            $previewPages = DocumentPage::where('document_id', $id)
                ->where('is_preview', true)
                ->orderBy('page_number')
                ->get();
                
            if ($previewPages->isEmpty()) {
                // Создаем предпросмотр
                $previewData = $this->documentParser->createPreview($document, $pagesCount);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Предпросмотр создан',
                    'pages' => $previewData['pages'],
                    'total_pages' => $previewData['total_pages'],
                    'images_count' => $previewData['images_count']
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Предпросмотр уже существует',
                'pages' => $previewPages,
                'total_pages' => $document->total_pages ?? 0
            ]);
            
        } catch (\Exception $e) {
            Log::error("Ошибка создания предпросмотра документа {$id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Полный парсинг документа
     */
    public function parseFullDocument(Request $request, $id)
    {
        try {
            $document = Document::findOrFail($id);
            
            Log::info("Начало полного парсинга документа {$id}");
            
            // Обновляем статус
            $document->update([
                'status' => 'processing',
                'processing_started_at' => now()
            ]);
            
            // Помещаем задачу в очередь
            dispatch(new \App\Jobs\ProcessDocumentJob($document->id));
            
            return response()->json([
                'success' => true,
                'message' => 'Документ поставлен в очередь на обработку',
                'document_id' => $document->id,
                'job_id' => null
            ]);
            
        } catch (\Exception $e) {
            Log::error("Ошибка постановки документа {$id} в очередь: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Получить прогресс обработки
     */
    public function getProcessingProgress($id)
    {
        try {
            $document = Document::findOrFail($id);
            $pagesProcessed = DocumentPage::where('document_id', $id)->count();
            $imagesProcessed = DocumentImage::where('document_id', $id)->count();
            
            $progress = 0;
            if ($document->total_pages > 0) {
                $progress = ($pagesProcessed / $document->total_pages) * 100;
            }
            
            return response()->json([
                'success' => true,
                'status' => $document->status,
                'progress' => round($progress, 2),
                'pages_processed' => $pagesProcessed,
                'total_pages' => $document->total_pages ?? 0,
                'images_processed' => $imagesProcessed,
                'estimated_time' => $this->calculateEstimatedTime($document, $progress)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения прогресса'
            ], 500);
        }
    }
    
    /**
     * Получить список страниц документа
     */
    public function getDocumentPages($id)
    {
        try {
            $pages = DocumentPage::where('document_id', $id)
                ->with('images')
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
                    'status' => $document->status
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения страниц'
            ], 500);
        }
    }
    
    /**
     * Экспорт документа в разные форматы
     */
    public function exportDocument(Request $request, $id)
    {
        try {
            $document = Document::findOrFail($id);
            $format = $request->get('format', 'json');
            
            if ($format === 'json') {
                $data = $this->prepareDocumentData($document);
                return response()->json($data);
            } elseif ($format === 'html') {
                return $this->exportToHtml($document);
            } elseif ($format === 'txt') {
                return $this->exportToText($document);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Неизвестный формат'
            ], 400);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка экспорта'
            ], 500);
        }
    }
    
    /**
     * Переиндексация документа
     */
    public function reindexDocument($id)
    {
        try {
            $document = Document::findOrFail($id);
            
            // Очищаем старые данные
            DocumentPage::where('document_id', $id)->delete();
            DocumentImage::where('document_id', $id)->delete();
            
            // Обновляем статус
            $document->update([
                'status' => 'pending_reindex',
                'is_parsed' => false,
                'search_indexed' => false,
                'total_pages' => 0
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Документ подготовлен для переиндексации',
                'document_id' => $document->id
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка переиндексации'
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
            
            $stats = [
                'pages_count' => DocumentPage::where('document_id', $id)->count(),
                'images_count' => DocumentImage::where('document_id', $id)->count(),
                'words_count' => DocumentPage::where('document_id', $id)->sum('word_count'),
                'characters_count' => DocumentPage::where('document_id', $id)->sum('character_count'),
                'paragraphs_count' => DocumentPage::where('document_id', $id)->sum('paragraph_count'),
                'tables_count' => DocumentPage::where('document_id', $id)->sum('tables_count'),
                'last_parsed' => $document->updated_at,
                'parsing_quality' => $document->parsing_quality,
                'detected_sections' => $this->extractSections($document)
            ];
            
            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения статистики'
            ], 500);
        }
    }
    
    /**
     * Удалить предпросмотр
     */
    public function deletePreview($id)
    {
        try {
            DocumentPage::where('document_id', $id)
                ->where('is_preview', true)
                ->delete();
                
            DocumentImage::where('document_id', $id)
                ->where('is_preview', true)
                ->delete();
                
            return response()->json([
                'success' => true,
                'message' => 'Предпросмотр удален'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка удаления предпросмотра'
            ], 500);
        }
    }
    
    private function calculateEstimatedTime($document, $progress)
    {
        if ($progress >= 100 || !$document->processing_started_at) {
            return 'Завершено';
        }
        
        $elapsed = now()->diffInSeconds($document->processing_started_at);
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
        
        return 'Рассчитывается...';
    }
    
    private function prepareDocumentData($document)
    {
        $pages = DocumentPage::where('document_id', $document->id)
            ->with('images')
            ->orderBy('page_number')
            ->get()
            ->map(function($page) {
                return [
                    'page_number' => $page->page_number,
                    'content' => $page->content,
                    'content_text' => $page->content_text,
                    'word_count' => $page->word_count,
                    'images' => $page->images->map(function($image) {
                        return [
                            'filename' => $image->filename,
                            'path' => $image->path,
                            'description' => $image->description,
                            'position' => $image->position
                        ];
                    })
                ];
            });
            
        return [
            'document' => [
                'id' => $document->id,
                'title' => $document->title,
                'car_model' => $document->carModel ? $document->carModel->name : null,
                'category' => $document->category ? $document->category->name : null,
                'file_type' => $document->file_type,
                'total_pages' => $document->total_pages,
                'parsing_quality' => $document->parsing_quality
            ],
            'pages' => $pages,
            'metadata' => [
                'created_at' => $document->created_at,
                'updated_at' => $document->updated_at,
                'pages_count' => $pages->count(),
                'total_words' => $pages->sum('word_count')
            ]
        ];
    }
    
    private function exportToHtml($document)
    {
        $data = $this->prepareDocumentData($document);
        
        $html = view('admin.documents.export.html', $data)->render();
        
        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'attachment; filename="document_' . $document->id . '.html"');
    }
    
    private function exportToText($document)
    {
        $pages = DocumentPage::where('document_id', $document->id)
            ->orderBy('page_number')
            ->get();
            
        $text = "Документ: {$document->title}\n";
        $text .= "ID: {$document->id}\n";
        $text .= "Тип: {$document->file_type}\n";
        $text .= "Страниц: " . $pages->count() . "\n";
        $text .= "=" . str_repeat("=", 50) . "=\n\n";
        
        foreach ($pages as $page) {
            $text .= "=== Страница {$page->page_number} ===\n\n";
            $text .= strip_tags($page->content_text) . "\n\n";
        }
        
        return response($text)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="document_' . $document->id . '.txt"');
    }
    
    private function extractSections($document)
    {
        $pages = DocumentPage::where('document_id', $document->id)
            ->whereNotNull('section_title')
            ->select('section_title', \DB::raw('count(*) as pages_count'))
            ->groupBy('section_title')
            ->orderBy('pages_count', 'desc')
            ->get();
            
        return $pages;
    }
}