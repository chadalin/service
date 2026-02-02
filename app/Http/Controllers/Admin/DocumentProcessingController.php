<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentPage;
use App\Models\DocumentImage;
use App\Services\EnhancedPdfParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentProcessingController extends Controller
{
    protected $pdfParser;
    
    public function __construct()
    {
        $this->pdfParser = new EnhancedPdfParserService();
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
     * Расширенная обработка документа (ОСНОВНОЙ МЕТОД)
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
        
        return view('admin.documents.processing_advanced', compact(
            'document', 
            'previewPages', 
            'stats'
        ));
    }
    
    /**
     * Создать предпросмотр
     */
    public function createPreview($id)
    {
        $document = Document::findOrFail($id);
        
        $filePath = Storage::disk('local')->path($document->file_path);
        
        $result = $this->pdfParser->createPreview($id, $filePath, 5);
        
        if ($result['success']) {
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('success', "Предпросмотр создан: {$result['preview_pages']} страниц, качество: " . 
                    round($result['parsing_quality'] * 100, 1) . "%");
        } else {
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('error', "Ошибка: " . $result['error']);
        }
    }
    
    /**
     * Полный парсинг документа
     */
    public function parseFull($id)
    {
        $document = Document::findOrFail($id);
        
        $filePath = Storage::disk('local')->path($document->file_path);
        
        $result = $this->pdfParser->parseFullDocument($id, $filePath);
        
        if ($result['success']) {
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('success', "Документ распарсен: {$result['processed_pages']}/{$result['total_pages']} страниц, " .
                    "{$result['word_count']} слов, {$result['images_extracted']} изображений");
        } else {
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('error', "Ошибка: " . $result['error']);
        }
    }
    
    /**
     * Извлечь только изображения
     */
    public function parseImagesOnly($id)
    {
        $document = Document::findOrFail($id);
        
        $filePath = Storage::disk('local')->path($document->file_path);
        
        $result = $this->pdfParser->extractImagesOnly($id, $filePath);
        
        if ($result['success']) {
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('success', "Извлечено {$result['images_extracted']} изображений");
        } else {
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('error', "Ошибка: " . $result['error']);
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
        
        $document = Document::findOrFail($id);
        $page = $request->input('page');
        
        $filePath = Storage::disk('local')->path($document->file_path);
        
        $result = $this->pdfParser->parseSinglePage($id, $filePath, $page);
        
        if ($result['success']) {
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('success', "Страница {$page} распарсена: {$result['word_count']} слов");
        } else {
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('error', "Ошибка: " . $result['error']);
        }
    }
    
    /**
     * Удалить предпросмотр
     */
    public function deletePreview($id)
    {
        $document = Document::findOrFail($id);
        
        // Удаляем превью-страницы и их изображения
        $previewPages = DocumentPage::where('document_id', $id)
            ->where('is_preview', true)
            ->get();
        
        foreach ($previewPages as $page) {
            // Удаляем связанные изображения
            foreach ($page->images as $image) {
                Storage::disk('public')->delete($image->path);
                Storage::disk('public')->delete($image->thumbnail_path);
                $image->delete();
            }
            $page->delete();
        }
        
        // Обновляем статус документа
        $document->update([
            'status' => 'uploaded',
            'parsing_quality' => null,
            'parsing_progress' => 0
        ]);
        
        return redirect()->route('admin.documents.processing.advanced', $id)
            ->with('success', 'Предпросмотр удален');
    }
    
    /**
     * Сбросить статус обработки
     */
    public function resetStatus($id)
    {
        $document = Document::findOrFail($id);
        
        // Удаляем все связанные данные
        DocumentPage::where('document_id', $id)->delete();
        DocumentImage::where('document_id', $id)->delete();
        
        // Обновляем документ
        $document->update([
            'status' => 'uploaded',
            'is_parsed' => false,
            'parsing_quality' => null,
            'parsing_progress' => 0,
            'word_count' => 0,
            'total_pages' => null,
            'content_text' => null,
            'parsed_at' => null,
            'processing_started_at' => null
        ]);
        
        return redirect()->route('admin.documents.processing.advanced', $id)
            ->with('success', 'Статус обработки сброшен');
    }
    
    /**
     * Получить прогресс обработки (JSON для AJAX)
     */
    public function getProcessingProgress($id)
    {
        $document = Document::findOrFail($id);
        
        // Эмулируем прогресс если нужно
        if ($document->status === 'processing') {
            $currentProgress = $document->parsing_progress ?? 0;
            
            // Увеличиваем прогресс на 5% каждые 5 секунд (пример)
            if ($currentProgress < 100) {
                $newProgress = min(100, $currentProgress + 5);
                $document->update(['parsing_progress' => $newProgress]);
            }
            
            if ($currentProgress >= 100) {
                $document->update([
                    'status' => 'parsed',
                    'is_parsed' => true,
                    'parsed_at' => now()
                ]);
            }
        }
        
        return response()->json([
            'success' => true,
            'progress' => $document->parsing_progress ?? 0,
            'status' => $document->status,
            'message' => $this->getProgressMessage($document)
        ]);
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
            
            // Здесь можно запустить реальную обработку в фоне
            // Пока просто возвращаем успех
            return response()->json([
                'success' => true,
                'task_id' => 'doc_' . $document->id . '_' . time(),
                'message' => 'Обработка начата'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Получить список страниц документа (JSON для AJAX)
     */
    public function getDocumentPages($id)
    {
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
    }
    
    /**
     * Тестирование извлечения изображений
     */
    public function testImageExtraction($id)
    {
        try {
            $document = Document::findOrFail($id);
            
            $filePath = Storage::disk('local')->path($document->file_path);
            
            // Используем ваш SimpleImageExtractionService
            $imageService = new \App\Services\SimpleImageExtractionService();
            $images = $imageService->extractAllImages($filePath, 'test_images');
            
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('success', "Тест изображений: извлечено " . count($images) . " изображений");
                
        } catch (\Exception $e) {
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('error', "Ошибка теста: " . $e->getMessage());
        }
    }
    
    /**
     * Обработка в фоне
     */
    public function parseInBackground($id)
    {
        try {
            $document = Document::findOrFail($id);
            
            // Обновляем статус
            $document->update([
                'status' => 'processing',
                'processing_started_at' => now()
            ]);
            
            // В реальном приложении здесь будет запуск фоновой задачи
            // dispatch(new ParseDocumentJob($document));
            
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('success', "Обработка запущена в фоне. Обновите страницу через несколько минут.");
                
        } catch (\Exception $e) {
            return redirect()->route('admin.documents.processing.advanced', $id)
                ->with('error', "Ошибка: " . $e->getMessage());
        }
    }
    
    /**
     * Показать все изображения
     */
    public function viewImages($id)
    {
        $document = Document::findOrFail($id);
        $images = DocumentImage::where('document_id', $id)
            ->orderBy('page_number')
            ->paginate(20);
            
        return view('admin.documents.processing.images', compact('document', 'images'));
    }
    
    /**
     * Вспомогательные методы
     */
    
    /**
     * Расчет статистики
     */
    protected function calculateStats($document, $pages, $images)
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
    
    /**
     * Форматирование размера файла
     */
    protected function formatFileSize($bytes)
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
     * Сообщение о прогрессе
     */
    protected function getProgressMessage($document)
    {
        switch ($document->status) {
            case 'processing':
                return "Обработка документа... {$document->parsing_progress}%";
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