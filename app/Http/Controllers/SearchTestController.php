<?php
// app/Http/Controllers/SearchTestController.php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentPage;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchTestController extends Controller
{
    protected $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function index()
    {
        // Получаем статистику
        $stats = [
            'total_documents' => Document::where('is_parsed', true)->count(),
            'indexed_documents' => Document::where('search_indexed', true)->count(),
            'total_pages' => DocumentPage::count(),
        ];
        
        // Последние документы
        $recentDocuments = Document::where('is_parsed', true)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'title', 'file_type', 'created_at']);
        
        // Проверяем наличие FULLTEXT индексов
        $indexes = [];
        try {
            $indexes = DB::select("SHOW INDEX FROM documents WHERE Index_type = 'FULLTEXT'");
        } catch (\Exception $e) {
            // Игнорируем ошибку
        }
        
        return view('search.test', compact('stats', 'recentDocuments', 'indexes'));
    }

    // В контроллере
public function search(Request $request)
{
    $query = $request->input('q', '');
    
    $documents = collect();
    $popularSearches = collect();
    
    // Простая заглушка для популярных запросов
    $popularQueries = ['двигатель', 'масло', 'тормоз', 'ремонт', 'диагностика'];
    foreach ($popularQueries as $popularQuery) {
        $popularSearches->push((object)[
            'query' => $popularQuery,
            'search_count' => rand(5, 50)
        ]);
    }
    
    if (!empty($query)) {
        $documents = Document::where('title', 'LIKE', "%{$query}%")
            ->orWhere('content_text', 'LIKE', "%{$query}%")
            ->where('is_parsed', true)
            ->paginate(20);
    }
    
    return view('search.results_simple', compact('query', 'documents', 'popularSearches'));
}

    public function autocomplete(Request $request)
    {
        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }
        
        try {
            $suggestions = Document::autocomplete($query);
            return response()->json($suggestions);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    }

    public function document($id)
    {
        $document = Document::with(['pages' => function($query) {
            $query->orderBy('page_number');
        }])->findOrFail($id);
        
        // Увеличиваем счетчик просмотров
        try {
            $document->incrementViewCount();
        } catch (\Exception $e) {
            // Игнорируем ошибку
        }
        
        return view('search.document', compact('document'));
    }

    public function testSearchApi()
    {
        $testResults = $this->searchService->testSearch();
        
        return view('search.api-test', [
            'results' => $testResults,
            'testQueries' => array_keys($testResults)
        ]);
    }
    
    public function apiSearch(Request $request)
    {
        $query = $request->input('query', '');
        $filters = $request->input('filters', []);
        
        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'Search query is required'
            ], 400);
        }
        
        $results = $this->searchService->paginatedSearch($query, $filters, 1, 10);
        
        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }
}