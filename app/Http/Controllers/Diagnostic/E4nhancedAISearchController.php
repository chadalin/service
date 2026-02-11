<?php

namespace App\Http\Controllers\Diagnostic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Diagnostic\Symptom;
use App\Models\Diagnostic\Rule;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\PriceItem;
use App\Models\Document;
use App\Models\DocumentPage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EnhancedAISearchController extends Controller
{
    /**
     * Показать страницу AI поиска
     */
    public function index()
    {
        $brands = Brand::where('is_popular', true)
            ->orderBy('name')
            ->get();
        
        $models = CarModel::whereIn('brand_id', $brands->pluck('id'))
            ->select('id', 'brand_id', 'name', 'name_cyrillic', 'year_from', 'year_to')
            ->get()
            ->groupBy('brand_id');
        
        $stats = [
            'symptoms_count' => Symptom::where('is_active', true)->count(),
            'rules_count' => Rule::where('is_active', true)->count(),
            'brands_count' => Brand::count(),
            'models_count' => CarModel::count(),
        ];
        
        return view('diagnostic.ai-search.enhanced', compact('brands', 'models', 'stats'));
    }

    /**
     * Выполнить расширенный AI поиск
     */
    public function enhancedSearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|max:1000',
            'brand' => 'nullable|string|max:255', // Изменено с brand_id на brand
            'brand_id' => 'nullable|string|max:255', // Оставляем для обратной совместимости
            'model_id' => 'nullable|integer',
            'search_type' => 'nullable|in:basic,advanced,full',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        $startTime = microtime(true);
        
        // Получаем параметры - ВАЖНО: сначала проверяем brand, потом brand_id
        $query = trim($request->input('query'));
        $brandName = $request->input('brand'); // Название бренда из формы
        $brandId = $request->input('brand_id'); // ID бренда из формы
        $modelId = $request->input('model_id');
        $searchType = $request->input('search_type', 'advanced');

        Log::info('Enhanced AI Search Started', [
            'query' => $query,
            'brand_name_from_form' => $brandName,
            'brand_id_from_form' => $brandId,
            'model_id' => $modelId,
            'search_type' => $searchType,
            'all_params' => $request->all()
        ]);

        // Получаем объект бренда - ПРИОРИТЕТ для brand из формы
        $brand = null;
        $brandIdForSearch = null;
        
        if (!empty($brandName)) {
            // Ищем бренд по названию (name или name_cyrillic)
            $brand = Brand::where('name', $brandName)
                ->orWhere('name_cyrillic', $brandName)
                ->orWhere('id', $brandName)
                ->first();
                
            if ($brand) {
                $brandIdForSearch = $brand->id;
                Log::info('Brand found by name', [
                    'brand_name' => $brandName,
                    'found_brand_id' => $brand->id,
                    'found_brand_name' => $brand->name
                ]);
            }
        } elseif (!empty($brandId)) {
            // Ищем бренд по ID
            $brand = Brand::find($brandId);
            if ($brand) {
                $brandIdForSearch = $brand->id;
                Log::info('Brand found by ID', [
                    'brand_id' => $brandId,
                    'found_brand_name' => $brand->name
                ]);
            }
        }

        Log::info('Final brand for search', [
            'brand_id_for_search' => $brandIdForSearch,
            'brand_name' => $brand ? $brand->name : 'N/A'
        ]);

        try {
            // 1. Поиск симптомов с правилами и фильтрацией по бренду
            $groupedResults = $this->searchSymptomsWithRules($query, $brandIdForSearch);
            
            // 2. Поиск документов с фильтрацией по бренду
            $documents = $this->searchDocuments($query, $brandIdForSearch, $modelId);
            
            // 3. Поиск запчастей с фильтрацией по бренду
            $parts = [];
            if (!empty($groupedResults)) {
                $parts = $this->searchParts($query, $brandIdForSearch);
            }
            
            // 4. Генерация AI ответа
            $aiResponse = $this->generateAIResponse($query, $groupedResults, $documents, $parts, $brand);
            
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            return response()->json([
                'success' => true,
                'query' => $query,
                'results' => $groupedResults,
                'parts' => $parts,
                'documents' => $documents,
                'ai_response' => $aiResponse,
                'search_type' => $searchType,
                'execution_time' => $executionTime,
                'stats' => [
                    'symptoms_found' => count($groupedResults),
                    'parts_found' => count($parts),
                    'documents_found' => count($documents),
                ],
                'debug' => [
                    'brand_id_from_form' => $brandId,
                    'brand_name_from_form' => $brandName,
                    'brand_id_for_search' => $brandIdForSearch,
                    'brand_name' => $brand ? $brand->name : 'N/A',
                    'model_id' => $modelId
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        } catch (\Exception $e) {
            Log::error('Enhanced AI Search Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при выполнении поиска: ' . $e->getMessage(),
                'query' => $query
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Поиск симптомов и правил с фильтрацией по бренду
     */
    private function searchSymptomsWithRules($query, $brandId = null)
    {
        Log::debug('Searching symptoms with rules', [
            'query' => $query, 
            'brand_id' => $brandId,
            'brand_id_type' => gettype($brandId)
        ]);
        
        $results = [];
        $cleanQuery = $this->normalizeSearchQuery($query);
        $searchTerms = $this->extractSearchTerms($cleanQuery);
        
        // Определяем, является ли запрос кодом ошибки
        $isErrorCodeSearch = $this->isErrorCode($query);
        
        Log::debug('Search parameters', [
            'is_error_code' => $isErrorCodeSearch,
            'search_terms' => $searchTerms,
            'clean_query' => $cleanQuery
        ]);
        
        // 1. Сначала ищем правила для конкретного бренда (если указан)
        if ($brandId) {
            Log::debug('Starting search WITH brand filter', ['brand_id' => $brandId]);
            
            $rulesQuery->where('brand_id', $brandId);
            
            // Поиск по симптомам и возможным причинам
            $rulesQuery->where(function($q) use ($searchTerms, $isErrorCodeSearch, $query) {
                // Поиск через связанные симптомы
                $q->whereHas('symptom', function($symptomQuery) use ($searchTerms, $isErrorCodeSearch, $query) {
                    $symptomQuery->where('is_active', true)
                        ->where(function($subQ) use ($searchTerms, $isErrorCodeSearch, $query) {
                            foreach ($searchTerms as $term) {
                                if (mb_strlen($term) > 2) {
                                    $subQ->orWhere('name', 'like', "%{$term}%")
                                         ->orWhere('description', 'like', "%{$term}%");
                                }
                            }
                            
                            if ($isErrorCodeSearch) {
                                $cleanErrorCode = preg_replace('/[^a-zA-Z0-9]/', '', $query);
                                $subQ->orWhere('description', 'like', "%{$cleanErrorCode}%")
                                     ->orWhere('description', 'like', "%{$query}%");
                            }
                        });
                });
                
                // Также ищем в возможных причинах
                foreach ($searchTerms as $term) {
                    if (mb_strlen($term) > 2) {
                        $q->orWhere('possible_causes', 'like', "%{$term}%");
                    }
                }
                
                if ($isErrorCodeSearch) {
                    $cleanErrorCode = preg_replace('/[^a-zA-Z0-9]/', '', $query);
                    $q->orWhere('possible_causes', 'like', "%{$cleanErrorCode}%")
                      ->orWhere('possible_causes', 'like', "%{$query}%");
                }
            });
            
            $rules = $rulesQuery->orderBy('complexity_level')->get();
            Log::debug('Rules found with brand filter', ['count' => $rules->count()]);
            
            // Обработка найденных правил для указанного бренда
            foreach ($rules as $rule) {
                if ($rule->symptom) {
                    $relevance = $this->calculateRelevanceForSymptom(
                        $rule->symptom->name, 
                        $rule->symptom->description, 
                        $query,
                        $rule->possible_causes
                    );
                    
                    $results[] = [
                        'type' => 'rule',
                        'id' => $rule->id,
                        'symptom_id' => $rule->symptom->id,
                        'title' => $rule->symptom->name,
                        'description' => $rule->symptom->description ?? '',
                        'brand' => $rule->brand ? $rule->brand->name : '',
                        'brand_id' => $rule->brand_id,
                        'model' => $rule->model ? $rule->model->name : '',
                        'model_id' => $rule->model_id,
                        'diagnostic_steps' => is_array($rule->diagnostic_steps) ? $rule->diagnostic_steps : [],
                        'possible_causes' => is_array($rule->possible_causes) ? $rule->possible_causes : [],
                        'required_data' => is_array($rule->required_data) ? $rule->required_data : [],
                        'complexity_level' => $rule->complexity_level ?? 1,
                        'estimated_time' => $rule->estimated_time ?? 60,
                        'consultation_price' => $rule->base_consultation_price ?? 3000,
                        'relevance_score' => $relevance,
                        'match_type' => $isErrorCodeSearch ? 'error_code' : 'exact',
                        'has_rules' => true,
                        'related_systems' => $rule->symptom->related_systems ?? [],
                        'frequency' => $rule->symptom->frequency ?? 0,
                        'is_brand_specific' => true,
                    ];
                }
            }
            
            // Если для указанного бренда ничего не найдено, можно показать сообщение
            if (empty($results)) {
                Log::debug('No rules found for specified brand, trying general search');
            }
        }
        
        // 2. Поиск без фильтра по бренду (общие симптомы и правила)
        // Делаем это если: а) бренд не указан, б) для указанного бренда ничего не найдено
        
        if (empty($results) || !$brandId) {
            Log::debug('Starting search WITHOUT brand filter or extending search');
            
            $generalRulesQuery = Rule::where('is_active', true)
                ->with(['symptom' => function($q) {
                    $q->where('is_active', true);
                }, 'brand', 'model']);
            
            // Если бренд указан, но не найдено правил - ищем правила других брендов
            if ($brandId) {
                // Исключаем уже найденные правила для этого бренда
                $generalRulesQuery->where('brand_id', '!=', $brandId);
            }
            
            $generalRulesQuery->where(function($q) use ($searchTerms, $isErrorCodeSearch, $query) {
                // Поиск через связанные симптомы
                $q->whereHas('symptom', function($symptomQuery) use ($searchTerms, $isErrorCodeSearch, $query) {
                    $symptomQuery->where('is_active', true)
                        ->where(function($subQ) use ($searchTerms, $isErrorCodeSearch, $query) {
                            foreach ($searchTerms as $term) {
                                if (mb_strlen($term) > 2) {
                                    $subQ->orWhere('name', 'like', "%{$term}%")
                                         ->orWhere('description', 'like', "%{$term}%");
                                }
                            }
                            
                            if ($isErrorCodeSearch) {
                                $cleanErrorCode = preg_replace('/[^a-zA-Z0-9]/', '', $query);
                                $subQ->orWhere('description', 'like', "%{$cleanErrorCode}%")
                                     ->orWhere('description', 'like', "%{$query}%");
                            }
                        });
                });
                
                // Также ищем в возможных причинах
                foreach ($searchTerms as $term) {
                    if (mb_strlen($term) > 2) {
                        $q->orWhere('possible_causes', 'like', "%{$term}%");
                    }
                }
                
                if ($isErrorCodeSearch) {
                    $cleanErrorCode = preg_replace('/[^a-zA-Z0-9]/', '', $query);
                    $q->orWhere('possible_causes', 'like', "%{$cleanErrorCode}%")
                      ->orWhere('possible_causes', 'like', "%{$query}%");
                }
            });
            
            $generalRules = $generalRulesQuery->orderBy('complexity_level')->get();
            Log::debug('General rules found', ['count' => $generalRules->count()]);
            
            foreach ($generalRules as $rule) {
                if ($rule->symptom) {
                    $relevance = $this->calculateRelevanceForSymptom(
                        $rule->symptom->name, 
                        $rule->symptom->description, 
                        $query,
                        $rule->possible_causes
                    );
                    
                    // Снижаем релевантность для правил других брендов
                    $adjustedRelevance = $brandId ? $relevance * 0.7 : $relevance;
                    
                    $results[] = [
                        'type' => 'rule',
                        'id' => $rule->id,
                        'symptom_id' => $rule->symptom->id,
                        'title' => $rule->symptom->name,
                        'description' => $rule->symptom->description ?? '',
                        'brand' => $rule->brand ? $rule->brand->name : '',
                        'brand_id' => $rule->brand_id,
                        'model' => $rule->model ? $rule->model->name : '',
                        'model_id' => $rule->model_id,
                        'diagnostic_steps' => is_array($rule->diagnostic_steps) ? $rule->diagnostic_steps : [],
                        'possible_causes' => is_array($rule->possible_causes) ? $rule->possible_causes : [],
                        'required_data' => is_array($rule->required_data) ? $rule->required_data : [],
                        'complexity_level' => $rule->complexity_level ?? 1,
                        'estimated_time' => $rule->estimated_time ?? 60,
                        'consultation_price' => $rule->base_consultation_price ?? 3000,
                        'relevance_score' => $adjustedRelevance,
                        'match_type' => $isErrorCodeSearch ? 'error_code' : 'exact',
                        'has_rules' => true,
                        'related_systems' => $rule->symptom->related_systems ?? [],
                        'frequency' => $rule->symptom->frequency ?? 0,
                        'is_brand_specific' => $brandId && $rule->brand_id == $brandId,
                    ];
                }
            }
        }
        
        // 3. Поиск симптомов без правил (только если не указан бренд)
        if (!$brandId && empty($results)) {
            Log::debug('Searching symptoms without rules');
            
            $symptomsQuery = Symptom::where('is_active', true);
            
            $symptomsQuery->where(function($q) use ($searchTerms, $isErrorCodeSearch, $query) {
                foreach ($searchTerms as $term) {
                    if (mb_strlen($term) > 2) {
                        $q->orWhere('name', 'like', "%{$term}%")
                          ->orWhere('description', 'like', "%{$term}%");
                    }
                }
                
                if ($isErrorCodeSearch) {
                    $cleanErrorCode = preg_replace('/[^a-zA-Z0-9]/', '', $query);
                    $q->orWhere('description', 'like', "%{$cleanErrorCode}%")
                      ->orWhere('description', 'like', "%{$query}%");
                }
            });
            
            $symptoms = $symptomsQuery->get();
            Log::debug('Symptoms found without rules', ['count' => $symptoms->count()]);
            
            foreach ($symptoms as $symptom) {
                $relevance = $this->calculateRelevance($symptom->name, $symptom->description, $query);
                
                $results[] = [
                    'type' => 'symptom',
                    'id' => $symptom->id,
                    'title' => $symptom->name,
                    'description' => $symptom->description ?? '',
                    'relevance_score' => $relevance,
                    'match_type' => 'symptom',
                    'has_rules' => false,
                    'related_systems' => $symptom->related_systems ?? [],
                    'frequency' => $symptom->frequency ?? 0,
                    'is_brand_specific' => false,
                ];
            }
        }
        
        // Удаляем дубликаты по ID правила
        $uniqueResults = [];
        $addedIds = [];
        foreach ($results as $result) {
            $key = $result['type'] . '_' . $result['id'];
            if (!isset($addedIds[$key])) {
                $uniqueResults[] = $result;
                $addedIds[$key] = true;
            }
        }
        
        // Сортировка по релевантности (сначала правила для указанного бренда, потом остальные)
        usort($uniqueResults, function($a, $b) {
            // Сначала сравниваем по is_brand_specific
            if ($a['is_brand_specific'] !== $b['is_brand_specific']) {
                return $b['is_brand_specific'] <=> $a['is_brand_specific'];
            }
            // Затем по релевантности
            return $b['relevance_score'] <=> $a['relevance_score'];
        });
        
        Log::debug('Final results', ['count' => count($uniqueResults)]);
        
        return array_slice($uniqueResults, 0, 10);
    }

    /**
     * Поиск документов с фильтрацией по бренду
     */
    private function searchDocuments($query, $brandId = null, $modelId = null)
    {
        Log::debug('Searching documents', [
            'query' => $query, 
            'brand_id' => $brandId,
            'model_id' => $modelId
        ]);
        
        if (!Schema::hasTable('document_pages') || !Schema::hasTable('documents')) {
            return [];
        }
        
        $searchTerms = $this->extractSearchTerms($query);
        
        // Для кодов ошибок добавляем вариации
        if ($this->isErrorCode($query)) {
            $cleanErrorCode = preg_replace('/[^a-zA-Z0-9]/', '', $query);
            $searchTerms = array_merge($searchTerms, [
                $query,
                $cleanErrorCode,
                strtoupper($query),
                strtolower($query),
                str_replace('-', '', $query),
                str_replace('-', ' ', $query)
            ]);
        }
        
        $searchTerms = array_unique(array_filter($searchTerms, function($term) {
            return !empty($term) && mb_strlen($term) > 1;
        }));
        
        if (empty($searchTerms)) {
            return [];
        }
        
        try {
            Log::debug('Document search terms', ['terms' => $searchTerms]);
            
            // Базовый запрос
            $pagesQuery = DB::table('document_pages')
                ->select([
                    'document_pages.id as page_id',
                    'document_pages.document_id',
                    'document_pages.page_number',
                    'document_pages.content_text',
                    'document_pages.section_title',
                    'documents.id as doc_id',
                    'documents.title as document_title',
                    'documents.file_type',
                    'documents.source_url',
                    'documents.view_count',
                    'documents.total_pages',
                    'documents.detected_system',
                    'documents.detected_component',
                    'documents.car_model_id',
                    'documents.file_path'
                ])
                ->join('documents', 'document_pages.document_id', '=', 'documents.id')
                ->whereNotNull('document_pages.content_text')
                ->where('document_pages.content_text', '<>', '')
                ->where('document_pages.status', 'processed');
            
            // Поиск по всем терминам
            $pagesQuery->where(function($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $cleanTerm = $this->cleanSearchTerm($term);
                    if (!empty($cleanTerm)) {
                        $q->orWhere('document_pages.content_text', 'like', "%{$cleanTerm}%")
                          ->orWhere('document_pages.section_title', 'like', "%{$cleanTerm}%")
                          ->orWhere('documents.title', 'like', "%{$cleanTerm}%");
                    }
                }
            });
            
            // ФИЛЬТРАЦИЯ ПО БРЕНДУ И МОДЕЛИ
            if ($modelId) {
                Log::debug('Filtering by model_id', ['model_id' => $modelId]);
                $pagesQuery->where('documents.car_model_id', $modelId);
            } elseif ($brandId) {
                Log::debug('Filtering by brand_id', ['brand_id' => $brandId]);
                // Получаем ID моделей этого бренда
                $modelIds = CarModel::where('brand_id', $brandId)->pluck('id');
                if ($modelIds->isNotEmpty()) {
                    $pagesQuery->whereIn('documents.car_model_id', $modelIds);
                } else {
                    // Если у бренда нет моделей, возвращаем пустой результат
                    Log::debug('No models found for brand', ['brand_id' => $brandId]);
                    return [];
                }
            }
            
            $pages = $pagesQuery
                ->orderByRaw('
                    CASE 
                        WHEN document_pages.section_title LIKE "%код ошиб%" THEN 1
                        WHEN document_pages.section_title LIKE "%диагностик%" THEN 2
                        WHEN document_pages.section_title LIKE "%ремонт%" THEN 3
                        WHEN document_pages.section_title LIKE "%неисправн%" THEN 4
                        WHEN document_pages.section_title LIKE "%error%" THEN 5
                        WHEN document_pages.content_text LIKE "%' . $this->cleanSearchTerm($query) . '%" THEN 6
                        ELSE 7
                    END
                ')
                ->orderBy('documents.view_count', 'desc')
                ->orderBy('document_pages.page_number')
                ->limit(150)
                ->get();
            
            Log::debug('Document pages found', ['count' => $pages->count()]);
            
            if ($pages->isEmpty()) {
                // Если с фильтрами ничего не найдено, ищем без фильтров (только если не указан бренд)
                if (!$brandId && !$modelId) {
                    Log::debug('Searching all documents without filters');
                    return $this->searchAllDocuments($query);
                }
                return [];
            }
            
            // Группируем по документам и выбираем лучшую страницу
            $groupedDocuments = [];
            foreach ($pages as $page) {
                $docId = $page->doc_id;
                $pageNumber = $page->page_number;
                
                // Рассчитываем релевантность
                $relevance = $this->calculateDocumentRelevance($page->content_text, $searchTerms, $page->section_title);
                
                // Получаем информацию о бренде и модели
                $brandName = '';
                $modelName = '';
                
                if ($page->car_model_id) {
                    $model = CarModel::find($page->car_model_id);
                    if ($model) {
                        $modelName = $model->name;
                        $docBrand = Brand::find($model->brand_id);
                        if ($docBrand) {
                            $brandName = $docBrand->name;
                        }
                    }
                }
                
                // Создаем уникальный ключ для документа+страницы
                $docKey = $docId . '_' . $pageNumber;
                
                if (!isset($groupedDocuments[$docKey])) {
                    // Генерируем URL для конкретной страницы
                    $viewUrl = $this->generateDocumentPageUrl($docId, $pageNumber, $page->file_path, $page->source_url);
                    
                    $groupedDocuments[$docKey] = [
                        'id' => $docId,
                        'page_id' => $page->page_id,
                        'page_number' => $pageNumber,
                        'title' => $page->document_title ?? 'Документ',
                        'excerpt' => $this->getBestExcerpt($page->content_text, $searchTerms, 200),
                        'file_type' => $page->file_type ?? 'pdf',
                        'total_pages' => $page->total_pages ?? 0,
                        'source_url' => $page->source_url ?? '',
                        'file_path' => $page->file_path ?? '',
                        'detected_system' => $page->detected_system ?? '',
                        'detected_component' => $page->detected_component ?? '',
                        'view_count' => $page->view_count ?? 0,
                        'icon' => $this->getFileIcon($page->file_type ?? 'pdf'),
                        'relevance_score' => $relevance,
                        'view_url' => $viewUrl,
                        'page_title' => $page->section_title ?? '',
                        'brand' => $brandName,
                        'model' => $modelName,
                        'car_model_id' => $page->car_model_id,
                        'content_preview' => $this->getContentPreview($page->content_text, $searchTerms, 300),
                        'search_terms_found' => $this->getFoundTerms($page->content_text, $searchTerms),
                        'is_filtered' => $brandId || $modelId ? true : false,
                        'is_brand_specific' => $brandId && $brandName && Brand::where('id', $brandId)->where('name', $brandName)->exists(),
                    ];
                }
            }
            
            // Сортируем по релевантности и приоритету бренда
            usort($groupedDocuments, function($a, $b) {
                // Сначала документы для указанного бренда
                if ($a['is_brand_specific'] !== $b['is_brand_specific']) {
                    return $b['is_brand_specific'] <=> $a['is_brand_specific'];
                }
                // Затем по релевантности
                return $b['relevance_score'] <=> $a['relevance_score'];
            });
            
            return array_slice($groupedDocuments, 0, 5);
            
        } catch (\Exception $e) {
            Log::error('Error searching document pages: ' . $e->getMessage());
            return $this->searchAllDocuments($query);
        }
    }

    /**
     * Поиск всех документов без фильтров
     */
    private function searchAllDocuments($query)
    {
        $searchTerms = $this->extractSearchTerms($query);
        
        if ($this->isErrorCode($query)) {
            $cleanErrorCode = preg_replace('/[^a-zA-Z0-9]/', '', $query);
            $searchTerms = array_merge($searchTerms, [$cleanErrorCode]);
        }
        
        try {
            $pages = DB::table('document_pages')
                ->select([
                    'document_pages.id as page_id',
                    'document_pages.document_id',
                    'document_pages.page_number',
                    'document_pages.content_text',
                    'document_pages.section_title',
                    'documents.id as doc_id',
                    'documents.title as document_title',
                    'documents.file_type',
                    'documents.source_url',
                    'documents.view_count',
                    'documents.total_pages',
                    'documents.car_model_id',
                    'documents.file_path'
                ])
                ->join('documents', 'document_pages.document_id', '=', 'documents.id')
                ->whereNotNull('document_pages.content_text')
                ->where('document_pages.content_text', '<>', '')
                ->where(function($q) use ($searchTerms) {
                    foreach ($searchTerms as $term) {
                        $cleanTerm = $this->cleanSearchTerm($term);
                        if (!empty($cleanTerm)) {
                            $q->orWhere('document_pages.content_text', 'like', "%{$cleanTerm}%")
                              ->orWhere('document_pages.section_title', 'like', "%{$cleanTerm}%");
                        }
                    }
                })
                ->limit(100)
                ->get();
            
            if ($pages->isEmpty()) {
                return [];
            }
            
            $results = [];
            foreach ($pages as $page) {
                $relevance = $this->calculateDocumentRelevance($page->content_text, $searchTerms, $page->section_title);
                
                // Получаем бренд и модель
                $brandName = '';
                $modelName = '';
                if ($page->car_model_id) {
                    $model = CarModel::find($page->car_model_id);
                    if ($model) {
                        $modelName = $model->name;
                        $brand = Brand::find($model->brand_id);
                        if ($brand) {
                            $brandName = $brand->name;
                        }
                    }
                }
                
                $viewUrl = $this->generateDocumentPageUrl($page->doc_id, $page->page_number, $page->file_path, $page->source_url);
                
                $results[] = [
                    'id' => $page->doc_id,
                    'page_id' => $page->page_id,
                    'page_number' => $page->page_number,
                    'title' => $page->document_title ?? 'Документ',
                    'excerpt' => $this->getBestExcerpt($page->content_text, $searchTerms, 200),
                    'relevance_score' => $relevance,
                    'view_url' => $viewUrl,
                    'page_title' => $page->section_title ?? '',
                    'brand' => $brandName,
                    'model' => $modelName,
                    'is_filtered' => false,
                    'is_brand_specific' => false,
                ];
            }
            
            usort($results, function($a, $b) {
                return $b['relevance_score'] <=> $a['relevance_score'];
            });
            
            return array_slice($results, 0, 5);
            
        } catch (\Exception $e) {
            Log::error('Search all documents error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Поиск запчастей с фильтрацией по бренду
     */
    private function searchParts($query, $brandId = null)
    {
        if (!Schema::hasTable('price_items')) {
            return [];
        }
        
        $searchTerms = $this->extractSearchTerms($query);
        $searchTerms = array_filter($searchTerms, function($term) {
            return mb_strlen($term) > 2 && !$this->isGenericTerm($term);
        });
        
        if (empty($searchTerms)) {
            return [];
        }
        
        try {
            $partsQuery = PriceItem::query()
                ->where('price', '>', 0);
            
            // ФИЛЬТРАЦИЯ ПО БРЕНДУ
            if ($brandId) {
                // Ищем по brand_id (прямой ID)
                $partsQuery->where('brand_id', $brandId);
                Log::debug('Filtering parts by brand_id', ['brand_id' => $brandId]);
            }
            
            // Поиск по терминам
            $partsQuery->where(function($q) use ($searchTerms) {
                foreach (array_slice($searchTerms, 0, 3) as $term) {
                    $q->orWhere('name', 'like', "%{$term}%")
                      ->orWhere('description', 'like', "%{$term}%")
                      ->orWhere('sku', 'like', "%{$term}%");
                }
            });
            
            $parts = $partsQuery->select([
                    'id', 'sku', 'name', 'description', 'price', 
                    'quantity', 'catalog_brand', 'brand_id'
                ])
                ->orderBy('quantity', 'desc')
                ->orderBy('price')
                ->limit(5)
                ->get();
            
            Log::debug('Parts found', ['count' => $parts->count(), 'brand_filter' => $brandId ? 'YES' : 'NO']);
            
            return $parts->map(function($item) use ($brandId) {
                return [
                    'id' => $item->id,
                    'sku' => $item->sku ?? '',
                    'name' => $item->name ?? '',
                    'description' => $item->description ?? '',
                    'price' => $item->price ?? 0,
                    'formatted_price' => number_format($item->price ?? 0, 2, '.', ' '),
                    'quantity' => $item->quantity ?? 0,
                    'brand' => $item->catalog_brand ?? '',
                    'brand_id' => $item->brand_id,
                    'availability' => ($item->quantity ?? 0) > 10 ? 'В наличии' : 
                                     (($item->quantity ?? 0) > 0 ? 'Мало' : 'Нет в наличии'),
                    'is_brand_specific' => $brandId && $item->brand_id == $brandId,
                ];
            })->toArray();
                
        } catch (\Exception $e) {
            Log::error('Error searching parts: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Генерация AI ответа
     */
    private function generateAIResponse($query, $results, $documents, $parts, $brand = null)
    {
        $brandName = $brand ? ($brand->name_cyrillic ?? $brand->name) : 'неизвестной марки';
        
        $response = "🤖 **AI-анализ диагностической проблемы**\n\n";
        $response .= "🔍 **Запрос:** {$query}\n";
        $response .= "🏷️ **Марка:** {$brandName}\n\n";
        
        if (!empty($results)) {
            // Фильтруем результаты по бренду если указан
            $brandSpecificResults = $brand 
                ? array_filter($results, function($item) use ($brand) {
                    return isset($item['is_brand_specific']) && $item['is_brand_specific'];
                })
                : [];
            
            $brandSpecificCount = count($brandSpecificResults);
            $totalCount = count($results);
            
            $response .= "✅ **Найдено симптомов:** {$totalCount} ";
            if ($brand && $brandSpecificCount > 0) {
                $response .= "({$brandSpecificCount} специфично для {$brandName})";
            }
            $response .= "\n\n";
            
            // Показываем топ-3 результата (сначала для указанного бренда)
            $topResults = !empty($brandSpecificResults) 
                ? array_slice($brandSpecificResults, 0, 3)
                : array_slice($results, 0, 3);
            
            $response .= "🎯 **Наиболее релевантные результаты:**\n\n";
            
            foreach ($topResults as $index => $item) {
                $number = $index + 1;
                $relevance = round($item['relevance_score'] * 100);
                
                $response .= "**{$number}. {$item['title']}** ";
                
                if ($item['type'] === 'rule' && !empty($item['brand'])) {
                    $response .= "({$item['brand']}";
                    if (!empty($item['model'])) {
                        $response .= " {$item['model']}";
                    }
                    $response .= ")";
                }
                
                $response .= " - {$relevance}%";
                
                if (isset($item['is_brand_specific']) && $item['is_brand_specific']) {
                    $response .= " ✅";
                }
                
                $response .= "\n";
                
                if ($item['type'] === 'rule' && !empty($item['possible_causes']) && count($item['possible_causes']) > 0) {
                    $causes = implode(', ', array_slice($item['possible_causes'], 0, 2));
                    $response .= "   ⚠️ **Возможные причины:** {$causes}\n";
                }
                
                if ($item['type'] === 'rule' && !empty($item['diagnostic_steps']) && count($item['diagnostic_steps']) > 0) {
                    $stepsCount = count($item['diagnostic_steps']);
                    $response .= "   🔧 **Диагностические шаги:** {$stepsCount} шагов\n";
                }
                
                $response .= "\n";
            }
            
            // Если есть результаты не для указанного бренда
            if ($brand && $brandSpecificCount < $totalCount) {
                $otherBrandsCount = $totalCount - $brandSpecificCount;
                $response .= "ℹ️ **Также найдено {$otherBrandsCount} результатов для других марок**\n\n";
            }
        } else {
            $response .= "⚠️ **Совпадений не найдено.**\n\n";
            $response .= "💡 **Рекомендации:**\n";
            $response .= "• Проверьте правильность написания\n";
            $response .= "• Используйте более простые формулировки\n";
            $response .= "• Уточните детали проблемы\n";
            if ($brand) {
                $response .= "• Попробуйте поиск без фильтра по марке\n";
            }
        }
        
        if (!empty($documents)) {
            $brandSpecificDocs = $brand 
                ? array_filter($documents, function($doc) use ($brand) {
                    return isset($doc['is_brand_specific']) && $doc['is_brand_specific'];
                })
                : [];
            
            $brandSpecificDocsCount = count($brandSpecificDocs);
            $totalDocsCount = count($documents);
            
            $response .= "📄 **Найдено документов:** {$totalDocsCount} ";
            if ($brand && $brandSpecificDocsCount > 0) {
                $response .= "({$brandSpecificDocsCount} для {$brandName})";
            }
            $response .= "\n";
            
            // Показываем топ документ
            $topDoc = !empty($brandSpecificDocs) ? $brandSpecificDocs[0] : $documents[0];
            if ($topDoc) {
                $pageInfo = $topDoc['page_number'] ? " (стр. {$topDoc['page_number']})" : "";
                $response .= "   📋 **Лучший документ:** {$topDoc['title']}{$pageInfo}\n";
                if ($topDoc['brand']) {
                    $response .= "   🚗 **Для:** {$topDoc['brand']}";
                    if ($topDoc['model']) {
                        $response .= " {$topDoc['model']}";
                    }
                    $response .= "\n";
                }
            }
        } else {
            $response .= "📄 **Документы:** не найдено\n";
        }
        
        if (!empty($parts)) {
            $brandSpecificParts = $brand 
                ? array_filter($parts, function($part) use ($brand) {
                    return isset($part['is_brand_specific']) && $part['is_brand_specific'];
                })
                : [];
            
            $brandSpecificPartsCount = count($brandSpecificParts);
            $totalPartsCount = count($parts);
            
            $response .= "🛒 **Найдено запчастей:** {$totalPartsCount} ";
            if ($brand && $brandSpecificPartsCount > 0) {
                $response .= "({$brandSpecificPartsCount} для {$brandName})";
            }
            $response .= "\n";
        }
        
        $response .= "\n💡 **Следующие шаги:**\n";
        if (!empty($results)) {
            $response .= "1. Изучите диагностические шаги\n";
            $response .= "2. Проверьте возможные причины\n";
        }
        
        if (!empty($documents)) {
            $response .= "3. Ознакомьтесь с инструкциями (откройте нужную страницу)\n";
        }
        
        if (!empty($parts)) {
            $response .= "4. Закажите необходимые запчасти\n";
        }
        
        $response .= "5. При необходимости - консультация специалиста\n";
        
        return $response;
    }

    /**
     * Вспомогательные методы (остаются без изменений)
     */
    private function normalizeSearchQuery($query)
    {
        $query = mb_strtolower($query, 'UTF-8');
        $query = preg_replace('/[^\w\sа-яА-ЯёЁ\-]/u', ' ', $query);
        $query = trim(preg_replace('/\s+/', ' ', $query));
        
        return $query;
    }
    
    private function extractSearchTerms($query)
    {
        $words = preg_split('/[\s,\.\-\(\)\[\]:;!?]+/', $query);
        
        $stopWords = [
            'и', 'или', 'но', 'на', 'в', 'с', 'по', 'у', 'о', 'об', 'от', 'до', 'за',
            'из', 'к', 'со', 'то', 'же', 'бы', 'ли', 'не', 'нет', 'да', 'как', 'что',
            'это', 'так', 'вот', 'ну', 'нужно', 'очень', 'можно', 'надо'
        ];
        
        $terms = array_filter($words, function($word) use ($stopWords) {
            $word = trim($word);
            return !empty($word) && !in_array(mb_strtolower($word, 'UTF-8'), $stopWords);
        });
        
        return array_unique(array_values($terms));
    }
    
    private function cleanSearchTerm($term)
    {
        $term = trim($term);
        if (empty($term)) {
            return '';
        }
        
        $term = str_replace(['%', '_', '[', ']', '^'], ['\%', '\_', '\[', '\]', '\^'], $term);
        
        return $term;
    }
    
    private function calculateRelevance($title, $description, $query)
    {
        return $this->calculateRelevanceForSymptom($title, $description, $query, []);
    }
    
    private function calculateRelevanceForSymptom($title, $description, $query, $possibleCauses = [])
    {
        $score = 0;
        $queryLower = mb_strtolower($query, 'UTF-8');
        $titleLower = mb_strtolower($title, 'UTF-8');
        $descLower = mb_strtolower($description, 'UTF-8');
        
        // Получаем коды ошибок из возможных причин
        $causesText = '';
        if (is_array($possibleCauses) && !empty($possibleCauses)) {
            $causesText = implode(' ', $possibleCauses);
        } elseif (is_string($possibleCauses)) {
            $causesText = $possibleCauses;
        }
        $causesLower = mb_strtolower($causesText, 'UTF-8');
        
        // Проверяем, является ли запрос кодом ошибки
        $isErrorCode = $this->isErrorCode($query);
        
        if ($isErrorCode) {
            $cleanErrorCode = preg_replace('/[^a-zA-Z0-9]/', '', $query);
            
            // Поиск кода ошибки в возможных причинах (самый высокий приоритет)
            if (strpos($causesLower, $cleanErrorCode) !== false || 
                strpos($causesLower, $queryLower) !== false) {
                $score += 1.5;
            }
            
            // Поиск в описании симптома
            if (strpos($descLower, $cleanErrorCode) !== false || 
                strpos($descLower, $queryLower) !== false) {
                $score += 1.0;
            }
            
            // Поиск в названии
            if (strpos($titleLower, $cleanErrorCode) !== false || 
                strpos($titleLower, $queryLower) !== false) {
                $score += 0.8;
            }
        } else {
            // Обычный поиск
            if (strpos($titleLower, $queryLower) !== false) {
                $score += 1.0;
            }
            
            if (strpos($descLower, $queryLower) !== false) {
                $score += 0.5;
            }
            
            if (strpos($causesLower, $queryLower) !== false) {
                $score += 0.7;
            }
        }
        
        $queryWords = $this->extractSearchTerms($queryLower);
        $titleWords = $this->extractSearchTerms($titleLower);
        $descWords = $this->extractSearchTerms($descLower);
        $causesWords = $this->extractSearchTerms($causesLower);
        
        foreach ($queryWords as $qWord) {
            if (mb_strlen($qWord) < 3) continue;
            
            foreach ($titleWords as $tWord) {
                if (strpos($tWord, $qWord) !== false || strpos($qWord, $tWord) !== false) {
                    $score += 0.3;
                    break;
                }
            }
            
            foreach ($descWords as $dWord) {
                if (strpos($dWord, $qWord) !== false || strpos($qWord, $dWord) !== false) {
                    $score += 0.1;
                    break;
                }
            }
            
            foreach ($causesWords as $cWord) {
                if (strpos($cWord, $qWord) !== false || strpos($qWord, $cWord) !== false) {
                    $score += 0.2;
                    break;
                }
            }
        }
        
        return min(1.0, $score);
    }
    
    private function calculateDocumentRelevance($content, $searchTerms, $sectionTitle = '')
    {
        $score = 0;
        $contentLower = mb_strtolower($content, 'UTF-8');
        $sectionLower = mb_strtolower($sectionTitle, 'UTF-8');
        
        foreach ($searchTerms as $term) {
            $termLower = mb_strtolower($term, 'UTF-8');
            
            // Высокий приоритет для заголовков секций
            if (!empty($sectionLower) && strpos($sectionLower, $termLower) !== false) {
                $score += 0.5;
            }
            
            // Поиск точных совпадений
            if (preg_match('/\b' . preg_quote($termLower, '/') . '\b/', $contentLower)) {
                $score += 0.3;
            }
            // Частичные совпадения
            elseif (strpos($contentLower, $termLower) !== false) {
                $score += 0.1;
            }
        }
        
        // Дополнительные бонусы за ключевые слова
        if (strpos($sectionLower, 'код ошиб') !== false || strpos($contentLower, 'код ошиб') !== false) {
            $score += 0.2;
        }
        if (strpos($sectionLower, 'диагностик') !== false || strpos($contentLower, 'диагностик') !== false) {
            $score += 0.1;
        }
        
        return min(1.0, $score);
    }
    
    private function isErrorCode($query)
    {
        return preg_match('/^[a-zA-Z]\d{3,4}([-_]\d{2,3})*(-\d+)?$/i', $query) ||
               preg_match('/^\d{4,5}$/', $query) ||
               preg_match('/^[a-zA-Z]\d{4,5}$/i', $query);
    }
    
    private function isGenericTerm($term)
    {
        $genericTerms = [
            'неисправность', 'повреждение', 'проблема', 'симптом',
            'диагностика', 'ремонт', 'замена', 'проверка', 'код', 'ошибка'
        ];
        
        return in_array(mb_strtolower($term, 'UTF-8'), $genericTerms);
    }
    
    private function getBestExcerpt($text, $searchTerms, $length = 200)
    {
        $text = $this->cleanText($text);
        $textLower = mb_strtolower($text, 'UTF-8');
        
        $bestPos = 0;
        $bestScore = 0;
        
        for ($i = 0; $i < mb_strlen($text) - $length; $i += 50) {
            $chunk = mb_substr($textLower, $i, $length);
            $score = 0;
            
            foreach ($searchTerms as $term) {
                $termLower = mb_strtolower($term, 'UTF-8');
                if (strpos($chunk, $termLower) !== false) {
                    $score++;
                }
            }
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestPos = $i;
            }
        }
        
        $start = max(0, $bestPos - 30);
        $excerpt = mb_substr($text, $start, $length + 60);
        
        if ($start > 0) {
            $excerpt = '...' . $excerpt;
        }
        if ($start + $length + 60 < mb_strlen($text)) {
            $excerpt .= '...';
        }
        
        return $excerpt;
    }
    
    private function getContentPreview($text, $searchTerms, $maxLength = 500)
    {
        $text = $this->cleanText($text);
        
        $paragraphs = preg_split('/\n+/', $text);
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (empty($paragraph) || mb_strlen($paragraph) < 50) {
                continue;
            }
            
            $paragraphLower = mb_strtolower($paragraph, 'UTF-8');
            foreach ($searchTerms as $term) {
                $termLower = mb_strtolower($term, 'UTF-8');
                if (strpos($paragraphLower, $termLower) !== false) {
                    if (mb_strlen($paragraph) > $maxLength) {
                        $paragraph = mb_substr($paragraph, 0, $maxLength) . '...';
                    }
                    
                    return $paragraph;
                }
            }
        }
        
        $preview = mb_substr($text, 0, $maxLength);
        if (mb_strlen($text) > $maxLength) {
            $preview .= '...';
        }
        
        return $preview;
    }
    
    private function getFoundTerms($text, $searchTerms)
    {
        $found = [];
        $textLower = mb_strtolower($text, 'UTF-8');
        
        foreach ($searchTerms as $term) {
            $termLower = mb_strtolower($term, 'UTF-8');
            if (strpos($textLower, $termLower) !== false) {
                $found[] = $term;
            }
        }
        
        return $found;
    }
    
    private function cleanText($text)
    {
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        return $text;
    }
    
    private function getFileIcon($fileType)
    {
        $icons = [
            'pdf' => 'bi-file-pdf',
            'doc' => 'bi-file-word',
            'docx' => 'bi-file-word',
            'xls' => 'bi-file-excel',
            'xlsx' => 'bi-file-excel',
            'jpg' => 'bi-file-image',
            'png' => 'bi-file-image',
            'txt' => 'bi-file-text',
        ];
        
        $fileType = strtolower($fileType);
        return $icons[$fileType] ?? 'bi-file-earmark';
    }
    
    /**
     * Генерация URL для конкретной страницы документа
     */
    private function generateDocumentPageUrl($documentId, $pageNumber, $filePath = null, $sourceUrl = null)
    {
        // Если есть прямой URL к файлу
        if (!empty($sourceUrl)) {
            if (str_ends_with(strtolower($sourceUrl), '.pdf') && $pageNumber > 1) {
                return $sourceUrl . '#page=' . $pageNumber;
            }
            return $sourceUrl;
        }
        
        // Если есть локальный путь к файлу
        if (!empty($filePath) && file_exists(public_path($filePath))) {
            $url = asset($filePath);
            if (str_ends_with(strtolower($url), '.pdf') && $pageNumber > 1) {
                return $url . '#page=' . $pageNumber;
            }
            return $url;
        }
        
        // Генерируем URL через маршрут Laravel на конкретную страницу
        try {
            return route('documents.page.view', [
                'id' => $documentId,
                'page' => $pageNumber
            ]);
        } catch (\Exception $e) {
            try {
                return route('documents.view', [
                    'id' => $documentId,
                    'page' => $pageNumber
                ]);
            } catch (\Exception $e2) {
                return '/documents/' . $documentId . '/page/' . $pageNumber;
            }
        }
    }
}