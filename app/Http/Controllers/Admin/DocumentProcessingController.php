<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\FileParserService;
use App\Services\DocumentIndexer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DocumentProcessingController extends Controller
{
    protected $fileParser;
    protected $documentIndexer;
    
    public function __construct()
    {
        $this->fileParser = new FileParserService();
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
}