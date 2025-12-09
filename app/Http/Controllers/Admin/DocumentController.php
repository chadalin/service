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
}