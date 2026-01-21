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
use App\Models\SearchQuery;
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
        
        // Используем локальный семантический поиск
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
        
        return view('search.index', compact('brands', 'models'));
    }
    
    public function search(Request $request)
    {
        try {
            $startTime = microtime(true);
            
            Log::info('Search request:', $request->all());
            
            $request->validate([
                'query' => 'required|string|min:2',
                'brand_id' => 'nullable|exists:brands,id',
                'car_model_id' => 'nullable|exists:car_models,id',
                'category_id' => 'nullable|exists:repair_categories,id',
                'search_type' => 'nullable|in:simple,semantic,all'
            ]);
            
            // Получаем тип поиска
            $searchType = $request->get('search_type', 'simple');
            
            // Анализируем запрос
            $queryAnalysis = $this->chatService->processQuery($request->query);
            
            Log::info('Query analysis:', $queryAnalysis);
            
            $results = collect([]);
            
            // Простой поиск (полнотекстовый)
            if (in_array($searchType, ['simple', 'all'])) {
                $simpleResults = $this->simpleSearch(
                    $request->query,
                    $request->car_model_id,
                    $request->category_id,
                    $request->brand_id
                );
                $results = $results->merge($simpleResults);
            }
            
            // Семантический поиск
            if (in_array($searchType, ['semantic', 'all'])) {
                $semanticResults = $this->semanticEngine->semanticSearch(
                    $request->query,
                    $request->car_model_id,
                    $request->category_id
                );
                $results = $results->merge($semanticResults);
            }
            
            // Убираем дубликаты и сортируем
            $results = $results->unique('id')->sortByDesc('relevance_score')->values();
            
            $executionTime = round(microtime(true) - $startTime, 4);
            
            // Логируем запрос
            if (Schema::hasTable('search_queries')) {
                SearchQuery::create([
                    'query' => $request->query,
                    'result_count' => $results->count(),
                    'user_id' => auth()->id(),
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'execution_time' => $executionTime,
                    'successful' => true,
                    'filters' => json_encode($request->only(['brand_id', 'car_model_id', 'category_id'])),
                    'car_model_id' => $request->car_model_id,
                    'brand_id' => $request->brand_id,
                    'category_id' => $request->category_id,
                    'search_type' => $searchType,
                ]);
            }
            
            return response()->json([
                'success' => true,
                'query_analysis' => $queryAnalysis,
                'results' => $results,
                'count' => $results->count(),
                'execution_time' => $executionTime,
                'search_type' => $searchType,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Search error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка поиска: ' . $e->getMessage(),
                'error' => env('APP_DEBUG') ? $e->getTraceAsString() : null
            ], 500);
        }
    }
    
    /**
     * Простой поиск (альтернатива без OpenAI)
     */
    protected function simpleSearch($query, $modelId = null, $categoryId = null, $brandId = null)
    {
        try {
            // Поиск в документах
            $documents = Document::query()
                ->with(['carModel.brand', 'category'])
                ->where('status', 'processed');
            
            // Используем LIKE поиск по нескольким полям
            $documents->where(function($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('content_text', 'LIKE', "%{$query}%");
                
                // Если есть keywords_text поле
                if (Schema::hasColumn('documents', 'keywords_text')) {
                    $q->orWhere('keywords_text', 'LIKE', "%{$query}%");
                } elseif (Schema::hasColumn('documents', 'keywords')) {
                    $q->orWhere('keywords', 'LIKE', "%{$query}%");
                }
                
                // Поиск по секциям и системам
                if (Schema::hasColumn('documents', 'detected_section')) {
                    $q->orWhere('detected_section', 'LIKE', "%{$query}%");
                }
                
                if (Schema::hasColumn('documents', 'detected_system')) {
                    $q->orWhere('detected_system', 'LIKE', "%{$query}%");
                }
            });
            
            // Фильтры
            if ($modelId) {
                $documents->where('car_model_id', $modelId);
            } elseif ($brandId) {
                $documents->whereHas('carModel', function($q) use ($brandId) {
                    $q->where('brand_id', $brandId);
                });
            }
            
            if ($categoryId) {
                $documents->where('category_id', $categoryId);
            }
            
            $documents = $documents->orderBy('search_count', 'desc')
                                 ->orderBy('view_count', 'desc')
                                 ->limit(50)
                                 ->get();
            
            $documentResults = $documents->map(function($doc) use ($query) {
                $relevance = $this->calculateSimpleRelevance($doc, $query);
                
                // Обновляем статистику
                if (Schema::hasColumn('documents', 'search_count')) {
                    $doc->increment('search_count');
                }
                
                return [
                    'id' => $doc->id,
                    'type' => 'document',
                    'title' => $doc->title,
                    'content_text' => $this->extractSnippet($doc->content_text, $query),
                    'car_model' => $doc->carModel,
                    'category' => $doc->category,
                    'relevance_score' => $relevance,
                    'created_at' => $doc->created_at,
                    'url' => route('admin.documents.show', $doc->id)
                ];
            });
            
            // Поиск в диагностических кейсах
            $caseResults = collect([]);
            if (class_exists(DiagnosticCase::class)) {
                $cases = DiagnosticCase::query()
                    ->with(['carModel.brand'])
                    ->where('status', 'resolved')
                    ->where(function($q) use ($query) {
                        $q->where('problem_description', 'LIKE', "%{$query}%")
                          ->orWhere('diagnosis', 'LIKE', "%{$query}%")
                          ->orWhere('solution', 'LIKE', "%{$query}%");
                    });
                
                if ($modelId) {
                    $cases->where('car_model_id', $modelId);
                }
                
                $cases = $cases->limit(20)->get();
                
                $caseResults = $cases->map(function($case) use ($query) {
                    $relevance = $this->calculateCaseRelevance($case, $query);
                    
                    return [
                        'id' => $case->id,
                        'type' => 'diagnostic_case',
                        'title' => 'Диагностический кейс: ' . substr($case->problem_description, 0, 50),
                        'content_text' => "Проблема: {$case->problem_description}\nДиагноз: {$case->diagnosis}",
                        'car_model' => $case->carModel,
                        'relevance_score' => $relevance,
                        'created_at' => $case->created_at,
                        'url' => route('diagnostic.report.show', $case->id)
                    ];
                });
            }
            
            // Поиск в симптомах
            $symptomResults = collect([]);
            if (class_exists(DiagnosticSymptom::class)) {
                $symptoms = DiagnosticSymptom::query()
                    ->with(['rules.causes.solutions'])
                    ->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%")
                    ->limit(10)
                    ->get();
                
                $symptomResults = $symptoms->map(function($symptom) use ($query) {
                    $relevance = $this->calculateSymptomRelevance($symptom, $query);
                    
                    // Собираем решения
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
                        'content_text' => $symptom->description . "\n\nРешения:\n" . $solutionsText,
                        'relevance_score' => $relevance,
                        'created_at' => $symptom->created_at,
                        'url' => '#'
                    ];
                });
            }
            
            return $documentResults->merge($caseResults)->merge($symptomResults);
            
        } catch (\Exception $e) {
            Log::error('Simple search error: ' . $e->getMessage());
            return collect([]);
        }
    }
    
    /**
     * Расчет релевантности для документа
     */
    protected function calculateSimpleRelevance(Document $document, $query)
    {
        $score = 0;
        $queryWords = explode(' ', mb_strtolower($query, 'UTF-8'));
        $text = mb_strtolower($document->title . ' ' . $document->content_text, 'UTF-8');
        
        foreach ($queryWords as $word) {
            if (mb_strlen($word, 'UTF-8') > 2) {
                // Подсчитываем вхождения
                $score += mb_substr_count($text, $word) * 0.1;
                
                // Бонус за совпадение в заголовке
                if (mb_strpos(mb_strtolower($document->title, 'UTF-8'), $word) !== false) {
                    $score += 0.5;
                }
                
                // Бонус за точное совпадение
                if (mb_strtolower($document->title, 'UTF-8') == $query) {
                    $score += 2.0;
                }
            }
        }
        
        // Бонус за популярность
        if (Schema::hasColumn('documents', 'search_count')) {
            $score += min($document->search_count * 0.001, 0.5);
        }
        
        return min(1.0, $score / 5);
    }
    
    /**
     * Расчет релевантности для диагностического кейса
     */
    protected function calculateCaseRelevance(DiagnosticCase $case, $query)
    {
        $score = 0;
        $queryWords = explode(' ', mb_strtolower($query, 'UTF-8'));
        $text = mb_strtolower(
            $case->problem_description . ' ' . $case->diagnosis . ' ' . $case->solution,
            'UTF-8'
        );
        
        foreach ($queryWords as $word) {
            if (mb_strlen($word, 'UTF-8') > 2) {
                $score += mb_substr_count($text, $word) * 0.1;
            }
        }
        
        return min(1.0, $score / 3);
    }
    
    /**
     * Расчет релевантности для симптома
     */
    protected function calculateSymptomRelevance(DiagnosticSymptom $symptom, $query)
    {
        $score = 0;
        $queryWords = explode(' ', mb_strtolower($query, 'UTF-8'));
        $text = mb_strtolower($symptom->name . ' ' . $symptom->description, 'UTF-8');
        
        foreach ($queryWords as $word) {
            if (mb_strlen($word, 'UTF-8') > 2) {
                $score += mb_substr_count($text, $word) * 0.1;
            }
        }
        
        return min(1.0, $score / 2);
    }
    
    /**
     * Извлечение сниппета с подсветкой запроса
     */
    protected function extractSnippet($text, $query, $length = 200)
    {
        if (empty($text)) {
            return '';
        }
        
        $text = strip_tags($text);
        $queryWords = explode(' ', mb_strtolower($query, 'UTF-8'));
        
        // Ищем первое вхождение любого слова запроса
        $position = false;
        foreach ($queryWords as $word) {
            if (mb_strlen($word, 'UTF-8') > 2) {
                $pos = mb_stripos($text, $word, 0, 'UTF-8');
                if ($pos !== false) {
                    $position = $pos;
                    break;
                }
            }
        }
        
        if ($position === false) {
            $position = 0;
        }
        
        // Вырезаем фрагмент
        $start = max(0, $position - 50);
        $snippet = mb_substr($text, $start, $length, 'UTF-8');
        
        // Добавляем многоточие если нужно
        if ($start > 0) {
            $snippet = '...' . $snippet;
        }
        
        if (mb_strlen($text, 'UTF-8') > $start + $length) {
            $snippet .= '...';
        }
        
        return $snippet;
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
    
    /**
     * Анализ запроса
     */
    public function analyzeQuery(Request $request)
    {
        try {
            $request->validate([
                'query' => 'required|string|min:2'
            ]);
            
            $analysis = $this->semanticEngine->analyzeQuery($request->query);
            
            return response()->json([
                'success' => true,
                'analysis' => $analysis
            ]);
            
        } catch (\Exception $e) {
            Log::error('Query analysis error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка анализа запроса'
            ], 500);
        }
    }
}