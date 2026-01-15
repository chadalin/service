<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SearchEngine;
use App\Services\ChatService;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Document;
use App\Models\DiagnosticCase;
use App\Models\DiagnosticSymptom;
use Illuminate\Http\Request;
use App\Services\SemanticSearchEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    protected $searchEngine;
    protected $chatService;
    protected $semanticEngine;

    public function __construct()
    {
        $this->searchEngine = new SearchEngine();
        $this->chatService = new ChatService();
        $this->semanticEngine = new SemanticSearchEngine();
    }

    public function index()
    {
        $brandsCount = Brand::count();
        
        if ($brandsCount === 0) {
            return redirect()->route('admin.cars.import')
                ->with('warning', 'Сначала необходимо импортировать базу автомобилей');
        }

        $brands = Brand::orderBy('name')->get();
        
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
        
        Log::info('Search page - Brands count: ' . $brands->count());
        Log::info('Search page - Models groups: ' . $models->count());
        
        return view('search.index', compact('brands', 'models'));
    }

    public function search(Request $request)
    {
        try {
            Log::info('Search request:', $request->all());
            
            $request->validate([
                'query' => 'required|string|min:2',
                'brand_id' => 'nullable|exists:brands,id',
                'car_model_id' => 'nullable|exists:car_models,id',
                'category_id' => 'nullable|exists:repair_categories,id'
            ]);

            // Анализируем запрос
            $queryAnalysis = $this->chatService->processQuery($request->query);
            
            Log::info('Query analysis:', $queryAnalysis);

            // Выполняем поиск в документах
            $documentResults = $this->searchDocuments(
                $queryAnalysis['processed_query'],
                $request->car_model_id,
                $request->category_id
            );
            
            // Выполняем поиск в диагностических случаях
            $diagnosticResults = $this->searchDiagnosticCases(
                $queryAnalysis['processed_query'],
                $request->car_model_id
            );
            
            // Выполняем поиск в симптомах
            $symptomResults = $this->searchDiagnosticSymptoms(
                $queryAnalysis['processed_query']
            );
            
            // Объединяем результаты
            $allResults = collect([])
                ->merge($documentResults)
                ->merge($diagnosticResults)
                ->merge($symptomResults)
                ->sortByDesc('relevance_score')
                ->values();

            return response()->json([
                'success' => true,
                'query_analysis' => $queryAnalysis,
                'results' => $allResults,
                'count' => $allResults->count(),
                'stats' => [
                    'documents' => $documentResults->count(),
                    'diagnostic_cases' => $diagnosticResults->count(),
                    'symptoms' => $symptomResults->count()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Search error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка поиска: ' . $e->getMessage(),
                'error' => env('APP_DEBUG') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Поиск в документах
     */
    protected function searchDocuments($query, $modelId = null, $categoryId = null)
    {
        try {
            $search = Document::query()
                ->with(['carModel.brand', 'category'])
                ->where('status', 'processed');
            
            // Поиск по тексту
            if (!empty($query)) {
                $search->where(function($q) use ($query) {
                    // Поиск по content_text
                    $q->where('content_text', 'LIKE', "%{$query}%")
                      ->orWhere('title', 'LIKE', "%{$query}%")
                      ->orWhere('keywords', 'LIKE', "%{$query}%");
                    
                    // Если есть search_vector (PostgreSQL) - используем полнотекстовый поиск
                    if (DB::connection()->getDriverName() === 'pgsql') {
                        $q->orWhereRaw("search_vector @@ plainto_tsquery('russian', ?)", [$query]);
                    }
                });
            }
            
            // Фильтр по модели
            if ($modelId) {
                $search->where('car_model_id', $modelId);
            }
            
            // Фильтр по категории
            if ($categoryId) {
                $search->where('category_id', $categoryId);
            }
            
            $results = $search->limit(50)->get();
            
            // Добавляем релевантность
            return $results->map(function($doc) use ($query) {
                $relevance = $this->calculateRelevance($doc, $query);
                
                return [
                    'id' => $doc->id,
                    'type' => 'document',
                    'title' => $doc->title,
                    'content_text' => $doc->content_text,
                    'car_model' => $doc->carModel,
                    'category' => $doc->category,
                    'file_path' => $doc->file_path,
                    'original_filename' => $doc->original_filename,
                    'relevance_score' => $relevance,
                    'created_at' => $doc->created_at,
                    'url' => route('admin.documents.show', $doc->id)
                ];
            });
            
        } catch (\Exception $e) {
            Log::error('Document search error: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Поиск в диагностических случаях
     */
    protected function searchDiagnosticCases($query, $modelId = null)
    {
        try {
            if (!class_exists(\App\Models\DiagnosticCase::class)) {
                return collect([]);
            }
            
            $search = DiagnosticCase::query()
                ->with(['carModel.brand', 'symptoms'])
                ->where('status', 'resolved');
            
            if (!empty($query)) {
                $search->where(function($q) use ($query) {
                    $q->where('problem_description', 'LIKE', "%{$query}%")
                      ->orWhere('solution', 'LIKE', "%{$query}%")
                      ->orWhere('diagnosis', 'LIKE', "%{$query}%");
                });
            }
            
            if ($modelId) {
                $search->where('car_model_id', $modelId);
            }
            
            $results = $search->limit(20)->get();
            
            return $results->map(function($case) use ($query) {
                $relevance = $this->calculateRelevance($case, $query, [
                    'problem_description',
                    'solution',
                    'diagnosis'
                ]);
                
                return [
                    'id' => $case->id,
                    'type' => 'diagnostic_case',
                    'title' => 'Диагностический кейс: ' . substr($case->problem_description, 0, 50) . '...',
                    'content_text' => "Проблема: {$case->problem_description}\nРешение: {$case->solution}",
                    'car_model' => $case->carModel,
                    'relevance_score' => $relevance,
                    'created_at' => $case->created_at,
                    'url' => route('diagnostic.report.show', $case->id)
                ];
            });
            
        } catch (\Exception $e) {
            Log::error('Diagnostic case search error: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Поиск в симптомах диагностики
     */
    protected function searchDiagnosticSymptoms($query)
    {
        try {
            if (!class_exists(\App\Models\DiagnosticSymptom::class)) {
                return collect([]);
            }
            
            $search = DiagnosticSymptom::query()
                ->with(['rules.causes.solutions'])
                ->where('name', 'LIKE', "%{$query}%")
                ->orWhere('description', 'LIKE', "%{$query}%")
                ->limit(20);
            
            $results = $search->get();
            
            return $results->map(function($symptom) use ($query) {
                $relevance = $this->calculateRelevance($symptom, $query);
                
                // Собираем информацию о решениях
                $solutions = collect([]);
                foreach ($symptom->rules as $rule) {
                    foreach ($rule->causes as $cause) {
                        $solutions = $solutions->merge($cause->solutions);
                    }
                }
                
                $solutionsText = $solutions->pluck('description')->implode("\n");
                
                return [
                    'id' => $symptom->id,
                    'type' => 'symptom',
                    'title' => 'Симптом: ' . $symptom->name,
                    'content_text' => $symptom->description . "\n\nВозможные решения:\n" . $solutionsText,
                    'relevance_score' => $relevance,
                    'created_at' => $symptom->created_at,
                    'url' => '#'
                ];
            });
            
        } catch (\Exception $e) {
            Log::error('Symptom search error: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Расчет релевантности
     */
    protected function calculateRelevance($item, $query, $fields = [])
    {
        $score = 0;
        $queryWords = explode(' ', strtolower($query));
        
        // Для документов
        if ($item instanceof Document) {
            $text = strtolower($item->content_text . ' ' . $item->title . ' ' . $item->keywords);
            foreach ($queryWords as $word) {
                if (strlen($word) > 2) {
                    $score += substr_count($text, $word) * 0.1;
                }
            }
        }
        // Для других моделей
        else {
            if (empty($fields)) {
                $fields = ['name', 'description', 'content_text', 'problem_description', 'solution'];
            }
            
            $text = '';
            foreach ($fields as $field) {
                if (isset($item->$field) && !empty($item->$field)) {
                    $text .= ' ' . strtolower($item->$field);
                }
            }
            
            foreach ($queryWords as $word) {
                if (strlen($word) > 2) {
                    $score += substr_count($text, $word) * 0.1;
                }
            }
        }
        
        return min(1.0, $score / 10);
    }

    public function advancedSearch()
    {
        $brands = Brand::with('carModels')->orderBy('name')->get();
        $categories = \App\Models\RepairCategory::all();
        
        return view('search.advanced', compact('brands', 'categories'));
    }

    public function semanticSearch(Request $request)
    {
        try {
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
            
        } catch (\Exception $e) {
            Log::error('Semantic search error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка семантического поиска: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * AJAX загрузка моделей
     */
    public function getModels($brandId)
    {
        try {
            $models = CarModel::where('brand_id', $brandId)
                ->orderBy('name')
                ->get()
                ->map(function($model) {
                    return [
                        'id' => $model->id,
                        'name' => $model->name_cyrillic ?? $model->name,
                        'year_from' => $model->year_from,
                        'year_to' => $model->year_to
                    ];
                });
            
            return response()->json([
                'success' => true,
                'models' => $models,
                'count' => $models->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Get models error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}