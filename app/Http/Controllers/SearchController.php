<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SearchEngine;
use App\Services\ChatService;
use App\Models\Brand;
use App\Models\CarModel;
use Illuminate\Http\Request;
use App\Services\SemanticSearchEngine;

protected $semanticEngine;
class SearchController extends Controller
{
    protected $searchEngine;
    protected $chatService;
    $this->semanticEngine = new SemanticSearchEngine();

    public function __construct()
    {
        $this->searchEngine = new SearchEngine();
        $this->chatService = new ChatService();
    }

    public function index()
    {
        $brands = Brand::orderBy('name')->get();
        return view('search.index', compact('brands'));
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