<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\RepairCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index()
    {
        $documents = Document::with(['carModel.brand', 'RepairCategory', 'uploadedBy'])->latest()->paginate(20);
        return view('admin.documents.index', compact('documents'));
    }

    public function create()
{
    $brandsCount = Brand::count();
    
    if ($brandsCount === 0) {
        return redirect()->route('admin.cars.import')
            ->with('warning', 'Сначала необходимо импортировать базу автомобилей');
    }

    // Загружаем все бренды с моделями
    $brands = Brand::with(['carModels' => function($query) {
        $query->orderBy('name');
    }])->orderBy('name')->get();

    $categories = RepairCategory::all();
    
    // Логируем для отладки
    \Log::info('Brands loaded: ' . $brands->count());
    foreach ($brands as $brand) {
        \Log::info("Brand {$brand->name} has {$brand->carModels->count()} models");
    }
    
    return view('admin.documents.create', compact('brands', 'categories'));
}

   public function store(Request $request)
{
    $request->validate([
        'title' => 'required|string|max:255',
        'brand_id' => 'required|exists:brands,id',
        'car_model_id' => 'required|exists:car_models,id',
        'category_id' => 'required|exists:repair_categories,id',
        'document' => 'required|file|mimes:pdf,doc,docx,txt|max:10240'
    ]);

    try {
        $file = $request->file('document');
        
        // Простое сохранение файла - без указания диска
        $path = $file->store('documents');
        
        \Log::info("File saved to: {$path}");
        \Log::info("Full path: " . storage_path('app/' . $path));
        \Log::info("File exists: " . (file_exists(storage_path('app/' . $path)) ? 'YES' : 'NO'));

        // Создаем запись в БД
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

        // Немедленно обрабатываем
        \App\Jobs\ProcessDocumentJob::dispatchSync($document);

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

    // Метод для получения моделей по бренду (для AJAX)
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
        $document->load(['carModel.brand', 'RepairCategory', 'uploadedByUser']);
        
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
        
        return view('documents.show', compact('document', 'similarDocuments'));
    }
    
    /**
     * Предпросмотр файла
     */
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
    
    /**
     * Скачивание файла
     */
    public function download(Document $document)
    {
        $filePath = $this->getFilePath($document);
        
        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }
        
        return response()->download($filePath, $document->original_filename);
    }
    
    /**
     * Репроцессинг документа
     */
    public function reprocess(Document $document)
    {
        $document->update(['status' => 'pending']);
        
        ProcessDocumentJob::dispatch($document)->onQueue('documents');
        
        return redirect()->route('admin.documents.show', $document)
            ->with('success', 'Документ поставлен в очередь на обработку');
    }
    
    /**
     * Вспомогательный метод для получения пути к файлу
     */
    private function getFilePath(Document $document): string
    {
        $paths = [
            storage_path('app/public/' . $document->file_path),
            storage_path('app/' . $document->file_path),
            public_path('storage/' . $document->file_path),
            $document->file_path, // абсолютный путь
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        throw new \Exception("File not found for document {$document->id}");
    }
}