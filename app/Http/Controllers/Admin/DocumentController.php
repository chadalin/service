<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentPage;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Diagnostic\Symptom;
use App\Models\Diagnostic\Rule;
use App\Models\PriceItem;
use App\Models\RepairCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Jobs\ProcessDocumentJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\ManualParserService;

class DocumentController extends Controller
{

    protected $manualParser;

    public function __construct()
    {
        $this->manualParser = new ManualParserService();
    }
    public function index()
    {
        $documents = Document::with(['carModel.brand', 'category', 'uploadedBy'])->latest()->paginate(20);
        return view('admin.documents.index', compact('documents'));
    }

    public function create()
    {
        $brandsCount = Brand::count();
        
        if ($brandsCount === 0) {
            return redirect()->route('admin.cars.import')
                ->with('warning', 'Сначала необходимо импортировать базу автомобилей');
        }

        // Загружаем все бренды
        $brands = Brand::orderBy('name')->get();
        
        // Загружаем все модели сгруппированные по brand_id
        $models = CarModel::orderBy('name')->get()
            ->groupBy('brand_id')
            ->map(function($group) {
                return $group->map(function($model) {
                    return [
                        'id' => $model->id,
                        'name' => $model->name_cyrillic ?? $model->name,
                        'year_from' => $model->year_from,
                        'year_to' => $model->year_to
                    ];
                })->values();
            });
        
        $categories = RepairCategory::all();
        
        Log::info('Brands count: ' . $brands->count());
        Log::info('Models count by brand: ' . json_encode($models->map(function($m) {
            return count($m);
        })));
        
        return view('admin.documents.create', compact('brands', 'models', 'categories'));
    }

    public function store(Request $request)
    {
        // Если это чанковая загрузка
        if ($request->has('uploaded_file_name') && $request->has('uploaded_file_path')) {
            $request->validate([
                'title' => 'required|string|max:255',
                'brand_id' => 'required|exists:brands,id',
                'car_model_id' => 'required|exists:car_models,id',
                'category_id' => 'required|exists:repair_categories,id',
                'uploaded_file_name' => 'required|string',
                'uploaded_file_path' => 'required|string',
            ]);
            
            try {
                // Создаем запись в БД
                $document = Document::create([
                    'title' => $request->title,
                    'car_model_id' => $request->car_model_id,
                    'category_id' => $request->category_id,
                    'original_filename' => $request->uploaded_file_name,
                    'file_type' => pathinfo($request->uploaded_file_name, PATHINFO_EXTENSION),
                    'file_path' => $request->uploaded_file_path,
                    'uploaded_by' => auth()->id(),
                    'status' => 'processing'
                ]);
                
                // Запускаем обработку в фоне
                ProcessDocumentJob::dispatch($document)->onQueue('documents');
                
                return response()->json([
                    'success' => true,
                    'message' => 'Документ успешно загружен',
                    'redirect' => route('admin.documents.index')
                ]);
                
            } catch (\Exception $e) {
                Log::error("Error saving chunked document: " . $e->getMessage());
                
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка сохранения документа: ' . $e->getMessage()
                ], 500);
            }
        }
        
        // Обычная загрузка
        $request->validate([
            'title' => 'required|string|max:255',
            'brand_id' => 'required|exists:brands,id',
            'car_model_id' => 'required|exists:car_models,id',
            'category_id' => 'required|exists:repair_categories,id',
            'document' => 'required|file|mimes:pdf,doc,docx,txt|max:51200'
        ]);
        
        try {
            $file = $request->file('document');
            
            if ($file->getSize() > 50 * 1024 * 1024) {
                return redirect()->back()
                    ->with('error', 'Файл слишком большой. Максимальный размер: 50MB')
                    ->withInput();
            }
            
            $path = $file->store('documents');
            
            $document = Document::create([
                'title' => $request->title,
                'car_model_id' => $request->car_model_id,
                'category_id' => $request->category_id,
                'original_filename' => $file->getClientOriginalName(),
                'file_type' => $file->getClientOriginalExtension(),
                'file_path' => $path,
                'uploaded_by' => auth()->id(),
                'status' => 'processing'
            ]);
            
            ProcessDocumentJob::dispatch($document)->onQueue('documents');
            
            return redirect()->route('admin.documents.index')
                ->with('success', 'Документ загружен и обрабатывается');
                
        } catch (\Exception $e) {
            Log::error("Error uploading document: " . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Ошибка загрузки документа: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Document $document)
    {
        $document->delete();
        return redirect()->route('admin.documents.index')->with('success', 'Документ удален');
    }

    public function getModels($brandId)
    {
        $models = CarModel::where('brand_id', $brandId)
            ->orderBy('name')
            ->get(['id', 'name', 'name_cyrillic', 'year_from', 'year_to']);

        return response()->json($models);
    }

    public function show(Document $document)
    { 
        $document->load(['carModel.brand', 'category', 'uploadedBy']);
        
        $document->keywords = is_string($document->keywords) ? 
            json_decode($document->keywords, true) : $document->keywords;
        $document->sections = is_string($document->sections) ? 
            json_decode($document->sections, true) : $document->sections;
        $document->metadata = is_string($document->metadata) ? 
            json_decode($document->metadata, true) : $document->metadata;
            
        $similarDocuments = Document::with(['carModel.brand', 'category'])
            ->where('id', '!=', $document->id)
            ->where(function($query) use ($document) {
                if ($document->car_model_id) {
                    $query->where('car_model_id', $document->car_model_id);
                }
                if ($document->category_id) {
                    $query->orWhere('category_id', $document->category_id);
                }
            })
            ->where('status', 'processed')
            ->limit(5)
            ->get();
        
        return view('admin.documents.show', compact('document', 'similarDocuments'));
    }
    
    public function preview(Document $document)
    {
        $filePath = $this->getFilePath($document);
        
        if (!file_exists($filePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }
        
        $mimeType = mime_content_type($filePath);
        
        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $document->original_filename . '"'
        ]);
    }
    
    public function download(Document $document)
    {
        $filePath = $this->getFilePath($document);
        
        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }
        
        return response()->download($filePath, $document->original_filename);
    }
    
   
    
    private function getFilePath(Document $document): string
    {
        $paths = [
            storage_path('app/public/' . $document->file_path),
            storage_path('app/' . $document->file_path),
            public_path('storage/' . $document->file_path),
            $document->file_path,
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        throw new \Exception("File not found for document {$document->id}");
    }
    
    /**
     * AJAX загрузка больших файлов (чанками)
     */
   public function uploadChunk(Request $request)
{
    Log::info('Upload chunk started', [
        'chunkIndex' => $request->input('chunkIndex'),
        'totalChunks' => $request->input('totalChunks'),
        'fileName' => $request->input('fileName'),
        'fileSize' => $request->input('fileSize')
    ]);
    
    try {
        // Проверяем данные
        $validator = Validator::make($request->all(), [
            'file' => 'required|file',
            'chunkIndex' => 'required|integer|min:0',
            'totalChunks' => 'required|integer|min:1',
            'fileName' => 'required|string|max:500',
            'fileSize' => 'required|integer|min:1',
            'title' => 'required|string|max:255',
            'brand_id' => 'required|exists:brands,id',
            'car_model_id' => 'required|exists:car_models,id',
            'category_id' => 'required|exists:repair_categories,id',
        ]);
        
        if ($validator->fails()) {
            Log::error('Validation failed', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации: ' . $validator->errors()->first()
            ], 422);
        }
        
        $chunk = $request->file('file');
        $chunkIndex = (int)$request->input('chunkIndex');
        $totalChunks = (int)$request->input('totalChunks');
        $fileName = $request->input('fileName');
        $fileSize = (int)$request->input('fileSize');
        
        // Проверяем общий размер файла (500MB максимум)
        if ($fileSize > 500 * 1024 * 1024) {
            return response()->json([
                'success' => false,
                'message' => 'Файл слишком большой. Максимальный размер: 500MB'
            ], 400);
        }
        
        // Проверяем размер чанка (не более 10MB)
        if ($chunk->getSize() > 10 * 1024 * 1024) {
            return response()->json([
                'success' => false,
                'message' => 'Чанк слишком большой. Максимальный размер чанка: 10MB'
            ], 400);
        }
        
        // Получаем все данные
        $title = $request->input('title');
        $car_model_id = $request->input('car_model_id');
        $category_id = $request->input('category_id'); // Получаем category_id
        $brand_id = $request->input('brand_id'); // Получаем brand_id для логирования
        
        // Проверяем, что category_id действительно существует
        if (!RepairCategory::where('id', $category_id)->exists()) {
            Log::error('Category not found', ['category_id' => $category_id]);
            return response()->json([
                'success' => false,
                'message' => 'Категория не найдена'
            ], 400);
        }
        
        // Проверяем, что car_model_id существует
        if (!CarModel::where('id', $car_model_id)->exists()) {
            Log::error('Car model not found', ['car_model_id' => $car_model_id]);
            return response()->json([
                'success' => false,
                'message' => 'Модель автомобиля не найдена'
            ], 400);
        }
        
        Log::info('Validation passed', [
            'title' => $title,
            'car_model_id' => $car_model_id,
            'category_id' => $category_id,
            'brand_id' => $brand_id
        ]);
        
        // ВАЖНО: Используем постоянный идентификатор сессии загрузки
        $uploadId = $request->input('upload_id', md5($fileName . '_' . $fileSize . '_' . auth()->id()));
        
        // Сохраняем upload_id в сессии для последующих чанков
        if ($chunkIndex === 0) {
            // Для первого чанка создаем новый upload_id
            $uploadId = uniqid('upload_', true);
            session(['current_upload_id' => $uploadId]);
            session(['upload_data' => [
                'title' => $title,
                'car_model_id' => $car_model_id,
                'category_id' => $category_id,
                'fileName' => $fileName,
                'fileSize' => $fileSize
            ]]);
        } else {
            // Для последующих чанков берем upload_id из сессии
            $uploadId = session('current_upload_id', md5($fileName . '_' . $fileSize . '_' . auth()->id()));
            // Берем данные из сессии
            $uploadData = session('upload_data', []);
            $title = $uploadData['title'] ?? $title;
            $car_model_id = $uploadData['car_model_id'] ?? $car_model_id;
            $category_id = $uploadData['category_id'] ?? $category_id;
        }
        
        $tempDir = storage_path('app/temp/' . $uploadId);
        
        // Создаем временную директорию
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        // Сохраняем чанк
        $chunkPath = $tempDir . "/chunk_{$chunkIndex}";
        $chunk->move($tempDir, "chunk_{$chunkIndex}");
        
        Log::info('Chunk saved', [
            'uploadId' => $uploadId,
            'chunkIndex' => $chunkIndex,
            'tempDir' => $tempDir,
            'chunkPath' => $chunkPath,
            'chunkSize' => filesize($chunkPath)
        ]);
        
        // Также сохраняем метаданные о загрузке
        $uploadInfo = [
            'fileName' => $fileName,
            'fileSize' => $fileSize,
            'totalChunks' => $totalChunks,
            'uploadedChunks' => [],
            'uploadStartTime' => time(),
            'title' => $title,
            'car_model_id' => $car_model_id,
            'category_id' => $category_id,
            'brand_id' => $brand_id
        ];
        
        // Загружаем существующую информацию или создаем новую
        $infoPath = $tempDir . '/upload_info.json';
        if (file_exists($infoPath)) {
            $uploadInfo = json_decode(file_get_contents($infoPath), true);
        }
        
        // Добавляем текущий чанк в список загруженных
        if (!in_array($chunkIndex, $uploadInfo['uploadedChunks'])) {
            $uploadInfo['uploadedChunks'][] = $chunkIndex;
            sort($uploadInfo['uploadedChunks']);
        }
        
        // Обновляем данные формы
        $uploadInfo['title'] = $title;
        $uploadInfo['car_model_id'] = $car_model_id;
        $uploadInfo['category_id'] = $category_id;
        $uploadInfo['brand_id'] = $brand_id;
        
        // Сохраняем обновленную информацию
        file_put_contents($infoPath, json_encode($uploadInfo));
        
        // Если это последний чанк, объединяем файл
        if ($chunkIndex == $totalChunks - 1) {
            Log::info('Last chunk received, merging file', [
                'uploadId' => $uploadId,
                'tempDir' => $tempDir,
                'totalChunks' => $totalChunks,
                'title' => $title,
                'car_model_id' => $car_model_id,
                'category_id' => $category_id
            ]);
            
            // Проверяем, что все чанки на месте
            $uploadedChunks = $uploadInfo['uploadedChunks'];
            $missingChunks = [];
            for ($i = 0; $i < $totalChunks; $i++) {
                if (!in_array($i, $uploadedChunks)) {
                    $missingChunks[] = $i;
                }
            }
            
            if (!empty($missingChunks)) {
                Log::error('Missing chunks: ' . implode(',', $missingChunks), [
                    'uploadedChunks' => $uploadedChunks
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Отсутствуют чанки: ' . implode(',', $missingChunks),
                    'uploadedChunks' => $uploadedChunks,
                    'missingChunks' => $missingChunks
                ], 400);
            }
            
            // Проверяем физическое наличие всех чанков
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = $tempDir . "/chunk_{$i}";
                if (!file_exists($chunkPath)) {
                    Log::error('Chunk file not found', [
                        'chunkIndex' => $i,
                        'chunkPath' => $chunkPath
                    ]);
                    throw new \Exception("Файл чанка {$i} не найден");
                }
            }
            
            Log::info('All chunks present, starting merge');
            
            // Создаем уникальное имя для финального файла
            $finalFileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
            $finalPath = storage_path('app/documents/' . $finalFileName);
            
            // Создаем директорию для документов, если её нет
            $documentsDir = storage_path('app/documents');
            if (!file_exists($documentsDir)) {
                mkdir($documentsDir, 0755, true);
            }
            
            // Открываем финальный файл для записи
            $finalFile = fopen($finalPath, 'wb');
            if (!$finalFile) {
                throw new \Exception('Не удалось создать файл: ' . $finalPath);
            }
            
            $totalSize = 0;
            // Объединяем все чанки
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = $tempDir . "/chunk_{$i}";
                $chunkSize = filesize($chunkPath);
                
                $chunkContent = file_get_contents($chunkPath);
                if ($chunkContent === false) {
                    throw new \Exception("Не удалось прочитать чанк {$i}");
                }
                
                $written = fwrite($finalFile, $chunkContent);
                if ($written === false) {
                    throw new \Exception("Не удалось записать чанк {$i}");
                }
                
                $totalSize += $written;
                unlink($chunkPath); // Удаляем чанк
            }
            
            fclose($finalFile);
            
            // Проверяем размер итогового файла
            $finalFileSize = filesize($finalPath);
            if ($finalFileSize != $fileSize) {
                Log::error('File size mismatch', [
                    'expected' => $fileSize,
                    'actual' => $finalFileSize,
                    'totalSize' => $totalSize
                ]);
                unlink($finalPath); // Удаляем неполный файл
                throw new \Exception("Размер файла не совпадает: ожидалось {$fileSize}, получено {$finalFileSize}");
            }
            
            Log::info('File merged successfully', [
                'finalPath' => $finalPath,
                'fileSize' => $finalFileSize,
                'originalSize' => $fileSize
            ]);
            
            // Логируем данные для создания документа
            Log::info('Creating document record', [
                'title' => $title,
                'car_model_id' => $car_model_id,
                'category_id' => $category_id,
                'original_filename' => $fileName,
                'file_type' => strtolower(pathinfo($fileName, PATHINFO_EXTENSION)),
                'file_path' => 'documents/' . $finalFileName
            ]);
            
            // Создаем запись в БД с ВСЕМИ полями
            $document = Document::create([
                'title' => $title,
                'car_model_id' => $car_model_id,
                'category_id' => $category_id, // ВАЖНО: добавляем category_id
                'original_filename' => $fileName,
                'file_type' => strtolower(pathinfo($fileName, PATHINFO_EXTENSION)),
                'file_path' => 'documents/' . $finalFileName,
                'uploaded_by' => auth()->id(),
                'status' => 'processing'
            ]);
            
            Log::info('Document created successfully', ['document_id' => $document->id]);
            
            // Удаляем временные файлы
            if (file_exists($infoPath)) {
                unlink($infoPath);
            }
            if (is_dir($tempDir) && count(scandir($tempDir)) == 2) { // только . и ..
                @rmdir($tempDir);
            }
            
            // Очищаем сессию
            session()->forget(['current_upload_id', 'upload_data']);
            
            // Запускаем обработку в фоне
            ProcessDocumentJob::dispatch($document)->onQueue('documents');
            
            return response()->json([
                'success' => true,
                'message' => 'Файл успешно загружен и обрабатывается',
                'document_id' => $document->id,
                'file_path' => 'documents/' . $finalFileName
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Чанк загружен',
            'chunkIndex' => $chunkIndex,
            'totalChunks' => $totalChunks,
            'upload_id' => $uploadId  // Возвращаем upload_id клиенту
        ]);
        
    } catch (\Exception $e) {
        Log::error('Error uploading chunk: ' . $e->getMessage());
        Log::error($e->getTraceAsString());
        
        return response()->json([
            'success' => false,
            'message' => 'Ошибка загрузки: ' . $e->getMessage()
        ], 500);
    }
}
    
    /**
     * Проверка существования файла (для возобновляемой загрузки)
     */
    public function checkFile(Request $request)
    {
        $request->validate([
            'fileName' => 'required|string|max:255',
            'fileSize' => 'required|integer',
        ]);
        
        try {
            $fileName = $request->fileName;
            $fileSize = $request->fileSize;
            $safeFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
            $fileHash = md5($safeFileName . '_' . $fileSize);
            $tempDir = storage_path('app/temp/' . $fileHash);
            
            $uploadedChunks = [];
            
            if (file_exists($tempDir) && is_dir($tempDir)) {
                $files = scandir($tempDir);
                foreach ($files as $file) {
                    if (preg_match('/chunk_(\d+)/', $file, $matches)) {
                        $uploadedChunks[] = (int)$matches[1];
                    }
                }
                sort($uploadedChunks);
            }
            
            // Проверяем, существует ли уже полный файл
            $existingDocument = Document::where('original_filename', $fileName)
                ->where('uploaded_by', auth()->id())
                ->first();
            
            if ($existingDocument) {
                return response()->json([
                    'exists' => true,
                    'document_id' => $existingDocument->id,
                    'message' => 'Файл уже был загружен ранее'
                ]);
            }
            
            return response()->json([
                'exists' => false,
                'uploadedChunks' => $uploadedChunks,
                'resumable' => !empty($uploadedChunks),
                'tempDir' => $tempDir
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error checking file: ' . $e->getMessage());
            
            return response()->json([
                'exists' => false,
                'uploadedChunks' => [],
                'resumable' => true,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function reprocess($id)
    {
        $document = Document::findOrFail($id);
        
        try {
            // Парсим документ
            $result = $this->manualParser->parseDocument($document);
            
            if ($result) {
                // Создаем поисковый индекс
                $this->manualParser->createSearchIndex($document);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Документ успешно обработан и проиндексирован'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при обработке документа'
                ], 500);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Массовая индексация документов
     */
    public function batchIndex()
    {
        $documents = Document::where('status', '!=', 'processed')
            ->orWhereNull('search_indexed')
            ->limit(100)
            ->get();
        
        $processed = 0;
        $errors = 0;
        
        foreach ($documents as $document) {
            try {
                $this->manualParser->parseDocument($document);
                $this->manualParser->createSearchIndex($document);
                $processed++;
            } catch (\Exception $e) {
                $errors++;
                Log::error("Ошибка индексации документа {$document->id}: " . $e->getMessage());
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => "Обработано: {$processed}, Ошибок: {$errors}"
        ]);
    }

    // В DocumentController добавьте методы:
public function showPage($id, $pageNumber, Request $request)
    {
        // Находим документ
        $document = Document::with(['carModel.brand'])->findOrFail($id);
        
        // Находим страницу документа по номеру
        $page = DocumentPage::where('document_id', $id)
            ->where('page_number', $pageNumber)
            ->firstOrFail();
        
        // Получаем предыдущую и следующую страницы
        $prevPage = DocumentPage::where('document_id', $id)
            ->where('page_number', '<', $pageNumber)
            ->orderBy('page_number', 'desc')
            ->first();
        
        $nextPage = DocumentPage::where('document_id', $id)
            ->where('page_number', '>', $pageNumber)
            ->orderBy('page_number', 'asc')
            ->first();
        
        // Получаем терм для подсветки
        $highlightTerm = $request->input('highlight', '');
        
        // ПОИСК ДИАГНОСТИЧЕСКОЙ ИНФОРМАЦИИ
        $diagnosticInfo = $this->findDiagnosticInfo($page->content_text ?? '', $document);
        
        // Получаем связанные симптомы и правила
        $relatedSymptoms = $diagnosticInfo['symptoms'] ?? [];
        $relatedRules = $diagnosticInfo['rules'] ?? [];
        $errorCodes = $diagnosticInfo['error_codes'] ?? [];
        
        // Получаем рекомендуемые запчасти
        $recommendedParts = $this->findRecommendedParts($diagnosticInfo, $document);
        
        // Получаем скриншоты
        $screenshots = $this->getPageScreenshots($document, $page);
        
        // Получаем изображения
        $images = $this->getPageImages($document, $page);
        
        // Парсим текст с умной разбивкой на абзацы
        $paragraphs = $this->smartParagraphSplit($page->content_text ?? '');
        
        // Подсвечиваем текст
        $highlightedContent = $this->highlightText($page->content_text ?? '', $highlightTerm, $errorCodes);
        
        // Извлекаем мета-информацию
        $metaInfo = $this->extractMetaInfo($page, $document, $diagnosticInfo);
        
        // Формируем заголовок страницы
        $title = $this->generatePageTitle($document, $page, $diagnosticInfo);
        
        return view('documents.public.page', compact(
            'document',
            'page',
            'prevPage',
            'nextPage',
            'highlightTerm',
            'highlightedContent',
            'screenshots',
            'images',
            'paragraphs',
            'metaInfo',
            'title',
            'diagnosticInfo',
            'relatedSymptoms',
            'relatedRules',
            'errorCodes',
            'recommendedParts'
        ));
    }
/**
     * Поиск диагностической информации в тексте
     */
    private function findDiagnosticInfo($text, $document)
    {
        $result = [
            'symptoms' => [],
            'rules' => [],
            'error_codes' => [],
            'procedures' => [],
            'keywords' => []
        ];
        
        if (empty($text)) {
            return $result;
        }
        
        $textLower = mb_strtolower($text, 'UTF-8');
        
        // 1. Поиск кодов ошибок
        preg_match_all('/\b([A-Z]{1,2}[0-9]{3,4}(?:-[0-9]{1,2})?)\b/', $text, $errorMatches);
        $result['error_codes'] = array_unique($errorMatches[1] ?? []);
        
        // 2. Поиск симптомов в базе данных
        $possibleSymptoms = Symptom::where('is_active', true)
            ->where(function($q) use ($textLower) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . $textLower . '%'])
                  ->orWhereRaw('LOWER(description) LIKE ?', ['%' . $textLower . '%']);
            })
            ->limit(5)
            ->get();
        
        foreach ($possibleSymptoms as $symptom) {
            $result['symptoms'][] = [
                'id' => $symptom->id,
                'name' => $symptom->name,
                'description' => Str::limit($symptom->description, 150),
                'relevance' => $this->calculateRelevanceScore($textLower, $symptom)
            ];
        }
        
        // 3. Поиск правил диагностики
        if (!empty($result['error_codes']) || !empty($result['symptoms'])) {
            $rulesQuery = Rule::where('is_active', true)
                ->with(['symptom', 'brand', 'model']);
            
            // Фильтр по бренду
            if ($document->carModel && $document->carModel->brand) {
                $rulesQuery->where('brand_id', $document->carModel->brand->id);
            }
            
            // Поиск по кодам ошибок
            if (!empty($result['error_codes'])) {
                $rulesQuery->where(function($q) use ($result) {
                    foreach ($result['error_codes'] as $code) {
                        $q->orWhere('possible_causes', 'like', "%{$code}%");
                    }
                });
            }
            
            // Поиск по симптомам
            if (!empty($result['symptoms'])) {
                $symptomIds = array_column($result['symptoms'], 'id');
                $rulesQuery->orWhereIn('symptom_id', $symptomIds);
            }
            
            $rules = $rulesQuery->limit(3)->get();
            
            foreach ($rules as $rule) {
                $result['rules'][] = [
                    'id' => $rule->id,
                    'symptom_name' => $rule->symptom->name ?? 'Неизвестный симптом',
                    'possible_causes' => is_array($rule->possible_causes) ? array_slice($rule->possible_causes, 0, 3) : [],
                    'diagnostic_steps' => is_array($rule->diagnostic_steps) ? array_slice($rule->diagnostic_steps, 0, 3) : [],
                    'complexity' => $rule->complexity_level ?? 1,
                    'estimated_time' => $rule->estimated_time ?? 30,
                    'price' => $rule->base_consultation_price ?? 3000
                ];
            }
        }
        
        // 4. Извлечение диагностических процедур из текста
        $procedurePatterns = [
            '/[^.!?]*?(?:проверить|проверка|диагностик|измерить|заменить|отрегулировать)[^.!?]*[.!?]/iu',
            '/[^.!?]*?(?:check|inspect|test|measure|replace|adjust)[^.!?]*[.!?]/i',
            '/[^.!?]*?(?:неисправность|ошибка|проблема|симптом)[^.!?]*[.!?]/iu'
        ];
        
        foreach ($procedurePatterns as $pattern) {
            preg_match_all($pattern, $text, $matches);
            foreach ($matches[0] ?? [] as $procedure) {
                $procedure = trim($procedure);
                if (!empty($procedure) && !in_array($procedure, $result['procedures'])) {
                    $result['procedures'][] = $procedure;
                }
            }
        }
        $result['procedures'] = array_slice($result['procedures'], 0, 5);
        
        return $result;
    }

    /**
     * Поиск рекомендуемых запчастей
     */
    private function findRecommendedParts($diagnosticInfo, $document)
    {
        $parts = collect();
        
        // Поиск по кодам ошибок
        if (!empty($diagnosticInfo['error_codes'])) {
            foreach ($diagnosticInfo['error_codes'] as $code) {
                $found = PriceItem::where('sku', 'like', "%{$code}%")
                    ->orWhere('name', 'like', "%{$code}%")
                    ->where('price', '>', 0)
                    ->limit(2)
                    ->get();
                $parts = $parts->concat($found);
            }
        }
        
        // Поиск по симптомам
        if (!empty($diagnosticInfo['symptoms'])) {
            foreach ($diagnosticInfo['symptoms'] as $symptom) {
                $keywords = explode(' ', $symptom['name']);
                foreach ($keywords as $keyword) {
                    if (strlen($keyword) > 3) {
                        $found = PriceItem::where('name', 'like', "%{$keyword}%")
                            ->orWhere('description', 'like', "%{$keyword}%")
                            ->where('price', '>', 0)
                            ->limit(2)
                            ->get();
                        $parts = $parts->concat($found);
                    }
                }
            }
        }
        
        // Фильтр по бренду
        if ($document->carModel && $document->carModel->brand) {
            $parts = $parts->filter(function($part) use ($document) {
                return $part->brand_id == $document->carModel->brand->id;
            });
        }
        
        // Уникальные и ограничение
        $parts = $parts->unique('id')->take(3);
        
        return $parts->map(function($part) {
            return [
                'id' => $part->id,
                'sku' => $part->sku,
                'name' => Str::limit($part->name, 60),
                'price' => $part->price,
                'formatted_price' => number_format($part->price, 0, '', ' '),
                'brand' => $part->catalog_brand ?? '',
                'quantity' => $part->quantity ?? 0,
                'availability' => $part->quantity > 10 ? 'В наличии' : 
                                 ($part->quantity > 0 ? 'Мало' : 'Под заказ'),
                'url' => route('admin.price.show', $part->id)
            ];
        })->toArray();
    }

    /**
     * Умная разбивка текста на абзацы по 5-6 строк
     */
    private function smartParagraphSplit($text)
    {
        if (empty($text)) {
            return [];
        }
        
        // Сначала разбиваем по двойным переносам строк
        $paragraphs = preg_split('/\n\s*\n/', $text);
        
        $result = [];
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (empty($paragraph)) {
                continue;
            }
            
            // Разбиваем на строки
            $lines = explode("\n", $paragraph);
            $lines = array_filter(array_map('trim', $lines));
            
            if (count($lines) <= 6) {
                // Если абзац маленький - оставляем как есть
                $result[] = [
                    'title' => $this->extractParagraphTitle($paragraph),
                    'content' => $paragraph,
                    'lines' => count($lines)
                ];
            } else {
                // Разбиваем большой абзац на части по 5-6 строк
                $chunks = array_chunk($lines, 6);
                
                foreach ($chunks as $index => $chunk) {
                    $chunkText = implode("\n", $chunk);
                    $result[] = [
                        'title' => $this->extractParagraphTitle($chunkText, $index > 0),
                        'content' => $chunkText,
                        'lines' => count($chunk)
                    ];
                }
            }
        }
        
        return $result;
    }

    /**
     * Извлечение заголовка абзаца из первой строки
     */
    private function extractParagraphTitle($text, $isContinued = false)
    {
        $lines = explode("\n", trim($text));
        $firstLine = trim($lines[0] ?? '');
        
        // Берем первые 5-7 слов
        $words = explode(' ', $firstLine);
        $title = implode(' ', array_slice($words, 0, 7));
        
        if (strlen($title) > 50) {
            $title = substr($title, 0, 50) . '...';
        }
        
        if ($isContinued) {
            $title = 'Продолжение: ' . $title;
        }
        
        return $title;
    }

    /**
     * Подсветка текста с приоритетом для кодов ошибок
     */
    private function highlightText($text, $term, $errorCodes = [])
    {
        if (empty($text) || (empty($term) && empty($errorCodes))) {
            return null;
        }
        
        $escapedText = htmlspecialchars($text);
        
        // Сначала подсвечиваем коды ошибок (желтым)
        foreach ($errorCodes as $code) {
            $pattern = '/' . preg_quote($code, '/') . '/';
            $escapedText = preg_replace($pattern, '<mark class="bg-warning text-dark fw-bold">$0</mark>', $escapedText);
        }
        
        // Затем подсвечиваем поисковый термин (голубым)
        if (!empty($term)) {
            $pattern = '/' . preg_quote($term, '/') . '/iu';
            $escapedText = preg_replace($pattern, '<mark class="bg-info text-white">$0</mark>', $escapedText);
        }
        
        return $escapedText;
    }

    /**
     * Расчет релевантности симптома
     */
    private function calculateRelevanceScore($text, $symptom)
    {
        $score = 0;
        $nameLower = mb_strtolower($symptom->name, 'UTF-8');
        $descLower = mb_strtolower($symptom->description ?? '', 'UTF-8');
        
        if (strpos($text, $nameLower) !== false) {
            $score += 0.7;
        }
        
        if (strpos($text, $descLower) !== false) {
            $score += 0.3;
        }
        
        return min(1.0, $score);
    }

    /**
     * Генерация заголовка страницы
     */
    private function generatePageTitle($document, $page, $diagnosticInfo)
    {
        if (!empty($diagnosticInfo['error_codes'])) {
            return 'Код ошибки ' . implode(', ', array_slice($diagnosticInfo['error_codes'], 0, 2)) . 
                   ' - ' . $document->title;
        }
        
        if (!empty($diagnosticInfo['symptoms'])) {
            return $diagnosticInfo['symptoms'][0]['name'] . ' - ' . $document->title;
        }
        
        return $document->title . ' - Страница ' . $page->page_number;
    }

    /**
     * Извлечение мета-информации
     */
    private function extractMetaInfo($page, $document, $diagnosticInfo)
    {
        $metaInfo = [
            'title' => $page->section_title ?? null,
            'description' => null,
            'keywords' => [],
            'instructions' => $diagnosticInfo['procedures'] ?? []
        ];
        
        $text = $page->content_text ?? '';
        
        // Описание
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, 4);
        if (count($sentences) >= 2) {
            $metaInfo['description'] = implode(' ', array_slice($sentences, 0, 2));
        }
        
        // Ключевые слова
        $keywords = [];
        if (!empty($diagnosticInfo['error_codes'])) {
            $keywords = array_merge($keywords, $diagnosticInfo['error_codes']);
        }
        if (!empty($diagnosticInfo['symptoms'])) {
            foreach ($diagnosticInfo['symptoms'] as $symptom) {
                $keywords[] = $symptom['name'];
            }
        }
        $metaInfo['keywords'] = array_slice($keywords, 0, 10);
        
        return $metaInfo;
    }

   
   

/**
 * Получить скриншоты для страницы документа
 */
private function getPageScreenshots($document, $page)
{
    $screenshots = [];
    
    // Проверяем наличие модели Screenshot
    if (class_exists('\App\Models\DocumentScreenshot')) {
        $dbScreenshots = \App\Models\DocumentScreenshot::where('document_id', $document->id)
            ->where('page_number', $page->page_number)
            ->orderBy('is_main', 'desc')
            ->orderBy('order')
            ->get();
        
        foreach ($dbScreenshots as $index => $screenshot) {
            $screenshots[] = [
                'url' => $this->getMediaUrl($screenshot),
                'description' => $screenshot->description ?? 'Скриншот страницы ' . $page->page_number,
                'alt' => $screenshot->alt_text ?? 'Скриншот страницы ' . $page->page_number,
                'is_main' => $screenshot->is_main ?? false,
                'width' => $screenshot->width ?? null,
                'height' => $screenshot->height ?? null,
                'file_size' => $screenshot->file_size ?? null,
            ];
        }
    }
    
    // Если нет в базе, ищем файлы по шаблону
    if (empty($screenshots)) {
        $screenshots = $this->findScreenshotFiles($document, $page);
    }
    
    return $screenshots;
}

/**
 * Получить изображения для страницы документа
 */
private function getPageImages($document, $page)
{
    $images = [];
    
    if (class_exists('\App\Models\DocumentImage')) {
        $dbImages = \App\Models\DocumentImage::where('document_id', $document->id)
            ->where('page_number', $page->page_number)
            //->orderBy('order')
            ->get();
        
        foreach ($dbImages as $image) {
            $images[] = (object)[
                'url' => $this->getMediaUrl($image),
                'description' => $image->description ?? 'Изображение',
                'alt' => $image->alt_text ?? 'Изображение',
                'has_screenshot' => true,
                'screenshot_url' => $this->getMediaUrl($image),
            ];
        }
    }
    
    return $images;
}

/**
 * Получить URL медиафайла
 */
private function getMediaUrl($media)
{
    if (!empty($media->url)) {
        return $media->url;
    }
    
    if (!empty($media->file_path)) {
        if (file_exists(public_path($media->file_path))) {
            return asset($media->file_path);
        }
        if (file_exists(storage_path('app/public/' . $media->file_path))) {
            return asset('storage/' . $media->file_path);
        }
    }
    
    return null;
}

/**
 * Найти файлы скриншотов по шаблону
 */
private function findScreenshotFiles($document, $page)
{
    $screenshots = [];
    
    // Шаблоны путей для поиска
    $paths = [
        'storage/document_images/screenshots/' . $document->id . '/page_' . $page->page_number . '_full.jpg',
        'storage/document_images/screenshots/' . $document->id . '/page_' . $page->page_number . '.jpg',
        'storage/document_images/screenshots/' . $document->id . '/' . $page->page_number . '.jpg',
        'public/document_images/screenshots/' . $document->id . '/page_' . $page->page_number . '_full.jpg',
        'public/document_images/screenshots/' . $document->id . '/page_' . $page->page_number . '.jpg',
    ];
    
    foreach ($paths as $path) {
        // Проверяем в public
        $publicPath = public_path($path);
        if (file_exists($publicPath)) {
            $screenshots[] = [
                'url' => asset($path),
                'description' => 'Скриншот страницы ' . $page->page_number,
                'alt' => 'Скриншот страницы ' . $page->page_number,
                'is_main' => true,
                'width' => null,
                'height' => null,
                'file_size' => file_exists($publicPath) ? filesize($publicPath) : null,
            ];
            break;
        }
        
        // Проверяем в storage
        $storagePath = storage_path('app/' . $path);
        if (file_exists($storagePath)) {
            $urlPath = str_replace('storage/app/public/', 'storage/', $path);
            $screenshots[] = [
                'url' => asset($urlPath),
                'description' => 'Скриншот страницы ' . $page->page_number,
                'alt' => 'Скриншот страницы ' . $page->page_number,
                'is_main' => true,
                'width' => null,
                'height' => null,
                'file_size' => filesize($storagePath),
            ];
            break;
        }
    }
    
    return $screenshots;
}

/**
 * Парсинг текста на абзацы
 */
private function parseParagraphs($text)
{
    if (empty($text)) {
        return [];
    }
    
    // Разбиваем на абзацы
    $paragraphs = preg_split('/\n\s*\n/', $text);
    
    // Очищаем и фильтруем
    $paragraphs = array_map(function($p) {
        return trim(preg_replace('/\s+/', ' ', $p));
    }, $paragraphs);
    
    $paragraphs = array_filter($paragraphs, function($p) {
        return !empty($p) && mb_strlen($p) > 20;
    });
    
    return array_values($paragraphs);
}

/**
 * Подсветка текста
 */


/**
 * Извлечение мета-информации из текста
 */


public function viewPage(Request $request)
{
    $documentId = $request->get('document_id');
    $pageId = $request->get('page_id');
    $pageNumber = $request->get('page');
    $highlight = $request->get('highlight', '');
    
    $document = Document::findOrFail($documentId);
    
    // Получаем конкретную страницу
    $page = $pageId 
        ? DocumentPage::find($pageId)
        : DocumentPage::where('document_id', $documentId)
                      ->where('page_number', $pageNumber)
                      ->first();
    
    if (!$page) {
        abort(404, 'Страница не найдена');
    }
    
    return view('documents.view-page', compact('document', 'page', 'highlight'));
}
}