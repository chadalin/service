<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SearchEngine;
use App\Services\ChatService;
use App\Models\Brand;
use App\Models\CarModel;
use Illuminate\Http\Request;
use App\Services\SemanticSearchEngine;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

//protected $semanticEngine;
class SearchController extends Controller
{
    protected $searchEngine;
    protected $chatService;
     protected $semanticEngine;
   // $this->semanticEngine = new SemanticSearchEngine();

    public function __construct()
    {
        $this->searchEngine = new SearchEngine();
        $this->chatService = new ChatService();
        $this->semanticEngine = new SemanticSearchEngine(); 
    }

   // В контроллере SearchController добавьте
public function index()
{
    $brandsCount = Brand::count();
    
    if ($brandsCount === 0) {
        return redirect()->route('admin.cars.import')
            ->with('warning', 'Сначала необходимо импортировать базу автомобилей');
    }

    // Загружаем все бренды
    $brands = Brand::orderBy('name')->get();
    
    // Загружаем все модели сгруппированные по brand_id (точно как в DocumentController)
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
    
    \Log::info('Search page - Brands count: ' . $brands->count());
    \Log::info('Search page - Models groups: ' . $models->count());
    
    // Для отладки - проверяем первый бренд
    if ($models->count() > 0) {
        $firstBrandId = $models->keys()->first();
        $firstBrand = Brand::find($firstBrandId);
        $firstBrandModels = $models[$firstBrandId];
        \Log::info('First brand: ' . ($firstBrand ? $firstBrand->name : 'Unknown') . 
                  ' (ID: ' . $firstBrandId . ') has ' . count($firstBrandModels) . ' models');
    }
    
    return view('search.index', compact('brands', 'models'));
}

    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'brand_id' => 'nullable|exists:brands,id',
            'car_model_id' => 'nullable|exists:car_models,id',
            'category_id' => 'nullable|exists:repair_categories,id'
        ]);

        // Анализируем запрос
        $queryAnalysis = $this->chatService->processQuery($request->query);
        
        // Выполняем поиск
        $results = $this->searchEngine->search(
            $queryAnalysis['processed_query'],
            $request->car_model_id,
            $request->category_id
        );

        return response()->json([
            'success' => true,
            'query_analysis' => $queryAnalysis,
            'results' => $results,
            'count' => $results->count()
        ]);
    }

    public function advancedSearch()
    {
        $brands = Brand::with('carModels')->orderBy('name')->get();
        $categories = \App\Models\RepairCategory::all();
        
        return view('search.advanced', compact('brands', 'categories'));
    }

    public function semanticSearch(Request $request)
{
    $request->validate([
        'query' => 'required|string|min:2',
        'brand_id' => 'nullable|exists:brands,id',
        'car_model_id' => 'nullable|exists:car_models,id',
        'category_id' => 'nullable|exists:repair_categories,id'
    ]);

    $queryAnalysis = $this->chatService->processQuery($request->query);
    
    // Используем семантический поиск
    $results = $this->semanticEngine->semanticSearch(
        $queryAnalysis['processed_query'],
        $request->car_model_id,
        $request->category_id
    );

    return response()->json([
        'success' => true,
        'query_analysis' => $queryAnalysis,
        'results' => $results,
        'count' => $results->count(),
        'search_type' => 'semantic'
    ]);
}
}