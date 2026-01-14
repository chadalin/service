<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\RepairCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Jobs\ProcessDocumentJob;

class DocumentController extends Controller
{
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
            })->values(); // добавляем values() для чистого массива
        });
    
    $categories = RepairCategory::all();
    
    \Log::info('Brands count: ' . $brands->count());
    \Log::info('Models count by brand: ' . json_encode($models->map(function($m) {
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
            
            return response()->json([
                'success' => true,
                'message' => 'Документ успешно загружен',
                'redirect' => route('admin.documents.index')
            ]);
            
        } catch (\Exception $e) {
            \Log::error("Error saving chunked document: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка сохранения документа: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Обычная загрузка (ваш существующий код)
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
        
        ProcessDocumentJob::dispatchSync($document);
        
        return redirect()->route('admin.documents.index')
            ->with('success', 'Документ загружен и обработан успешно');
            
    } catch (\Exception $e) {
        \Log::error("Error uploading document: " . $e->getMessage());
        
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
        // Загружаем связи
        $document->load(['carModel.brand', 'category', 'uploadedBy']);
        
        // Декодируем JSON поля
        $document->keywords = is_string($document->keywords) ? 
            json_decode($document->keywords, true) : $document->keywords;
        $document->sections = is_string($document->sections) ? 
            json_decode($document->sections, true) : $document->sections;
        $document->metadata = is_string($document->metadata) ? 
            json_decode($document->metadata, true) : $document->metadata;
            
        // Похожие документы
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
    
    public function reprocess(Document $document)
    {
        $document->update(['status' => 'pending']);
        
        ProcessDocumentJob::dispatch($document)->onQueue('documents');
        
        return redirect()->route('admin.documents.show', $document)
            ->with('success', 'Документ поставлен в очередь на обработку');
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
        $request->validate([
            'file' => 'required|file',
            'chunkIndex' => 'required|integer',
            'totalChunks' => 'required|integer',
            'fileName' => 'required|string|max:255',
            'fileSize' => 'required|integer',
            'title' => 'required|string|max:255',
            'brand_id' => 'required|exists:brands,id',
            'car_model_id' => 'required|exists:car_models,id',
            'category_id' => 'required|exists:repair_categories,id',
        ]);
        
        try {
            // Проверяем общий размер файла
            if ($request->fileSize > 500 * 1024 * 1024) { // 500MB
                return response()->json([
                    'success' => false,
                    'message' => 'Файл слишком большой. Максимальный размер: 500MB'
                ], 400);
            }
            
            $chunk = $request->file('file');
            $chunkIndex = $request->chunkIndex;
            $totalChunks = $request->totalChunks;
            $fileName = $request->fileName;
            
            // Создаем безопасное имя файла
            $safeFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
            $tempDir = storage_path('app/temp/' . md5($safeFileName));
            
            // Создаем временную директорию
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            // Сохраняем чанк
            $chunkPath = $tempDir . "/chunk_{$chunkIndex}";
            $chunk->move($tempDir, "chunk_{$chunkIndex}");
            
            // Если это последний чанк, объединяем файл
            if ($chunkIndex == $totalChunks - 1) {
                // Создаем уникальное имя для финального файла
                $finalFileName = uniqid() . '_' . $safeFileName;
                $finalPath = storage_path('app/documents/' . $finalFileName);
                
                // Открываем финальный файл для записи
                $finalFile = fopen($finalPath, 'wb');
                
                // Объединяем все чанки
                for ($i = 0; $i < $totalChunks; $i++) {
                    $chunkPath = $tempDir . "/chunk_{$i}";
                    if (file_exists($chunkPath)) {
                        $chunkContent = file_get_contents($chunkPath);
                        fwrite($finalFile, $chunkContent);
                        unlink($chunkPath); // Удаляем чанк
                    }
                }
                
                fclose($finalFile);
                
                // Удаляем временную директорию
                @rmdir($tempDir);
                
                // Создаем запись в БД
                $document = Document::create([
                    'title' => $request->title,
                    'car_model_id' => $request->car_model_id,
                    'category_id' => $request->category_id,
                    'original_filename' => $fileName,
                    'file_type' => pathinfo($fileName, PATHINFO_EXTENSION),
                    'file_path' => 'documents/' . $finalFileName,
                    'uploaded_by' => auth()->id(),
                    'status' => 'processing'
                ]);
                
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
                'totalChunks' => $totalChunks
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error uploading chunk: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
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
            $safeFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
            $tempDir = storage_path('app/temp/' . md5($safeFileName));
            
            $uploadedChunks = [];
            
            if (file_exists($tempDir)) {
                $files = scandir($tempDir);
                foreach ($files as $file) {
                    if (preg_match('/chunk_(\d+)/', $file, $matches)) {
                        $uploadedChunks[] = (int)$matches[1];
                    }
                }
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
                'resumable' => true,
                'tempDir' => $tempDir
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error checking file: ' . $e->getMessage());
            
            return response()->json([
                'exists' => false,
                'uploadedChunks' => [],
                'resumable' => true,
                'error' => $e->getMessage()
            ]);
        }
    }
}