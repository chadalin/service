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
use App\Models\User;
use App\Models\DocumentPage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

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

        // Получаем текущего пользователя
        $user = Auth::user();
        
        return view('diagnostic.ai-search.enhanced', compact('brands', 'models', 'stats','user'));
    }

    /**
     * Выполнить расширенный AI поиск
     */
    public function enhancedSearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|max:1000',
            'brand_id' => 'nullable|string|max:255',
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
        
        // Получаем параметры
        $query = trim($request->input('query'));
        $brandId = $request->input('brand_id');
        $modelId = $request->input('model_id');
        $searchType = $request->input('search_type', 'advanced');

        Log::info('Enhanced AI Search Started', [
            'query' => $query,
            'brand_id' => $brandId,
            'model_id' => $modelId,
            'search_type' => $searchType,
            'all_params' => $request->all()
        ]);

        // Получаем объект бренда
        $brand = null;
        $brandIdForSearch = null;
        
        if (!empty($brandId)) {
            $brand = Brand::find($brandId);
            
            if ($brand) {
                $brandIdForSearch = $brand->id;
                Log::info('Brand found by ID', [
                    'brand_id' => $brandId,
                    'found_brand_name' => $brand->name
                ]);
            } else {
                Log::warning('Brand not found in database', ['brand_id' => $brandId]);
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
                    'brand_id' => $brandId,
                    'brand_name' => $brand ? $brand->name : 'N/A',
                    'model_id' => $modelId
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        } catch (\Exception $e) {
            Log::error('Enhanced AI Search Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'query' => $query,
                'brand_id' => $brandId,
                'model_id' => $modelId
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
        try {
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
            
            // Основной запрос на поиск правил
            $rulesQuery = Rule::where('is_active', true)
                ->with(['symptom' => function($q) {
                    $q->where('is_active', true);
                }, 'brand', 'model']);
            
            // Фильтрация по бренду если указан
            if (!empty($brandId)) {
                $rulesQuery->where('brand_id', $brandId);
                Log::debug('Filtering rules by brand', ['brand_id' => $brandId]);
            }
            
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
                if ($isErrorCodeSearch) {
                    $cleanErrorCode = preg_replace('/[^a-zA-Z0-9]/', '', $query);
                    $q->orWhere('possible_causes', 'like', "%{$cleanErrorCode}%")
                      ->orWhere('possible_causes', 'like', "%{$query}%");
                }
                
                // Поиск по отдельным терминам в возможных причинах
                foreach ($searchTerms as $term) {
                    if (mb_strlen($term) > 3) {
                        $q->orWhere('possible_causes', 'like', "%{$term}%");
                    }
                }
            });
            
            $rules = $rulesQuery->orderBy('complexity_level')->get();
            Log::debug('Rules found', ['count' => $rules->count()]);
            
            // Обработка найденных правил
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
                    ];
                }
            }
            
            // Если ничего не найдено, ищем симптомы без правил (только если не указан бренд)
            if (empty($results) && empty($brandId)) {
                Log::debug('No rules found, searching symptoms without rules');
                
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
                    ];
                }
            }
            
            // Сортировка по релевантности
            usort($results, function($a, $b) {
                return $b['relevance_score'] <=> $a['relevance_score'];
            });
            
            Log::debug('Final results count', ['count' => count($results)]);
            
            return array_slice($results, 0, 10);
            
        } catch (\Exception $e) {
            Log::error('Error in searchSymptomsWithRules: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'query' => $query,
                'brand_id' => $brandId
            ]);
            return [];
        }
    }

    /**
     * Поиск документов с фильтрацией по бренду
     */
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
        Log::warning('Documents tables not found');
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
    
    Log::debug('Document search terms', ['terms' => $searchTerms]);
    
    if (empty($searchTerms)) {
        return [];
    }
    
    try {
        // ПРОСТОЙ ЗАПРОС - БЕЗ СЛОЖНЫХ СВЯЗЕЙ
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
                'documents.car_model_id',
                'documents.file_path',
                'documents.detected_system',
                'documents.detected_component'
            ])
            ->join('documents', 'document_pages.document_id', '=', 'documents.id')
            ->whereNotNull('document_pages.content_text')
            ->where('document_pages.content_text', '<>', '');
        
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
            $modelIds = CarModel::where('brand_id', $brandId)->pluck('id')->toArray();
            Log::debug('Model IDs for brand', ['model_ids' => $modelIds]);
            
            if (!empty($modelIds)) {
                $pagesQuery->whereIn('documents.car_model_id', $modelIds);
            } else {
                Log::debug('No models found for brand', ['brand_id' => $brandId]);
                $pagesQuery->whereNull('documents.car_model_id');
            }
        }
        
        $pages = $pagesQuery
            ->orderBy('documents.view_count', 'desc')
            ->orderBy('document_pages.page_number')
            ->limit(50)
            ->get();
        
        Log::debug('Document pages found', ['count' => $pages->count()]);
        
        if ($pages->isEmpty()) {
            Log::debug('No document pages found, trying without brand filter');
            if ($brandId || $modelId) {
                return $this->searchAllDocuments($query);
            }
            return [];
        }
        
        // Группируем по документам
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
            
            // Получаем URL превью скриншота
            $previewImage = $this->getDocumentScreenshotUrl($page->doc_id, $page->page_number);
            
            // Уникальный ключ для документа
            $docKey = $docId;
            
            // Берем только лучшую страницу для каждого документа
            if (!isset($groupedDocuments[$docKey]) || 
                $relevance > $groupedDocuments[$docKey]['relevance_score']) {
                
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
                    'preview_image' => $previewImage,
                    'has_preview' => !empty($previewImage),
                    'preview_alt' => 'Скриншот страницы ' . $pageNumber . ' документа ' . ($page->document_title ?? $docId)
                ];
            }
        }
        
        // Сортируем по релевантности
        usort($groupedDocuments, function($a, $b) {
            return $b['relevance_score'] <=> $a['relevance_score'];
        });
        
        $results = array_slice($groupedDocuments, 0, 5);
        Log::debug('Final document results', ['count' => count($results)]);
        
        return $results;
        
    } catch (\Exception $e) {
        Log::error('Error searching document pages: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        return [];
    }
}

/**
 * Получить URL скриншота для страницы документа
 * Формат: https://service.local/storage/document_images/screenshots/{document_id}/page_{page_number}_full.jpg
 */
private function getDocumentScreenshotUrl($documentId, $pageNumber)
{
    try {
        // Базовый URL для скриншотов
        $baseUrl = url('/storage/document_images/screenshots');
        
        // Формируем имя файла: page_{page_number}_full.jpg
        $filename = 'page_' . $pageNumber . '_full.jpg';
        
        // Полный путь к файлу
        $screenshotUrl = $baseUrl . '/' . $documentId . '/' . $filename;
        
        Log::debug('Generated screenshot URL', [
            'document_id' => $documentId,
            'page_number' => $pageNumber,
            'url' => $screenshotUrl
        ]);
        
        // Проверяем существование файла (опционально)
        $filePath = public_path('storage/document_images/screenshots/' . $documentId . '/' . $filename);
        if (file_exists($filePath)) {
            return $screenshotUrl;
        }
        
        // Пробуем альтернативные форматы
        $alternativeFormats = [
            'page_' . $pageNumber . '.jpg',
            'page_' . $pageNumber . '_full.png',
            'screenshot_' . $pageNumber . '.jpg',
            $pageNumber . '.jpg',
            $documentId . '_' . $pageNumber . '.jpg'
        ];
        
        foreach ($alternativeFormats as $altFormat) {
            $altPath = public_path('storage/document_images/screenshots/' . $documentId . '/' . $altFormat);
            if (file_exists($altPath)) {
                return $baseUrl . '/' . $documentId . '/' . $altFormat;
            }
        }
        
        Log::debug('Screenshot file not found', [
            'path' => $filePath,
            'document_id' => $documentId,
            'page' => $pageNumber
        ]);
        
        return null;
        
    } catch (\Exception $e) {
        Log::error('Error getting screenshot URL: ' . $e->getMessage(), [
            'document_id' => $documentId,
            'page' => $pageNumber
        ]);
        return null;
    }
}


/**
 * Поиск всех документов без фильтров - УПРОЩЕННАЯ ВЕРСИЯ
 */
private function searchAllDocuments($query)
{
    Log::debug('Searching all documents');
    
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
            ->limit(50)
            ->get();
        
        Log::debug('All documents pages found', ['count' => $pages->count()]);
        
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
            
            // Получаем URL скриншота
            $previewImage = $this->getDocumentScreenshotUrl($page->doc_id, $page->page_number);
            
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
                'preview_image' => $previewImage,
                'has_preview' => !empty($previewImage),
                'preview_alt' => 'Скриншот страницы ' . $page->page_number . ' документа ' . ($page->document_title ?? $page->doc_id)
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
 * Получить превью изображение для страницы документа
 */
private function getPagePreviewImage($page)
{
    try {
        // Если у страницы есть скриншоты, берем главный
        if ($page->relationLoaded('screenshots') && $page->screenshots->isNotEmpty()) {
            $screenshot = $page->screenshots->first();
            
            // Проверяем разные возможные поля с URL
            if (!empty($screenshot->url)) {
                // Проверяем, полный ли это URL или относительный путь
                if (filter_var($screenshot->url, FILTER_VALIDATE_URL)) {
                    return $screenshot->url;
                } elseif (file_exists(public_path($screenshot->url))) {
                    return asset($screenshot->url);
                } elseif (file_exists(storage_path('app/public/' . $screenshot->url))) {
                    return asset('storage/' . $screenshot->url);
                }
            }
            
            // Пробуем другие поля
            if (!empty($screenshot->file_path)) {
                if (file_exists(public_path($screenshot->file_path))) {
                    return asset($screenshot->file_path);
                } elseif (file_exists(storage_path('app/public/' . $screenshot->file_path))) {
                    return asset('storage/' . $screenshot->file_path);
                }
            }
            
            if (!empty($screenshot->image_path)) {
                if (file_exists(public_path($screenshot->image_path))) {
                    return asset($screenshot->image_path);
                } elseif (file_exists(storage_path('app/public/' . $screenshot->image_path))) {
                    return asset('storage/' . $screenshot->image_path);
                }
            }
        }
        
        // Если нет скриншотов, пытаемся сгенерировать URL по шаблону
        $previewUrl = $this->generatePreviewUrl($page);
        if ($previewUrl) {
            return $previewUrl;
        }
        
    } catch (\Exception $e) {
        Log::error('Error getting page preview image: ' . $e->getMessage());
    }
    
    return null;
}

  /**
 * Генерация URL превью по шаблону
 */
private function generatePreviewUrl($page)
{
    // Попробуем разные шаблоны URL для скриншотов
    
    $templates = [
        // Шаблон 1: storage/app/public/screenshots/{document_id}/{page_number}.jpg
        'storage/app/public/screenshots/' . $page->document_id . '/' . $page->page_number . '.jpg',
        'storage/app/public/screenshots/' . $page->document_id . '/page_' . $page->page_number . '.jpg',
        'storage/app/public/screenshots/' . $page->document_id . '/' . $page->page_number . '_main.jpg',
        
        // Шаблон 2: public/screenshots/{document_id}/{page_number}.jpg
        'public/screenshots/' . $page->document_id . '/' . $page->page_number . '.jpg',
        'public/screenshots/' . $page->document_id . '/page_' . $page->page_number . '.jpg',
        
        // Шаблон 3: storage/app/public/document-screenshots/{document_id}/{page_id}.jpg
        'storage/app/public/document-screenshots/' . $page->document_id . '/' . $page->id . '.jpg',
        
        // Шаблон 4: если есть file_path в документе, меняем расширение
        function() use ($page) {
            if ($page->document && !empty($page->document->file_path)) {
                $pathInfo = pathinfo($page->document->file_path);
                $dir = dirname($page->document->file_path);
                return $dir . '/previews/page_' . $page->page_number . '.jpg';
            }
            return null;
        }
    ];
    
    foreach ($templates as $template) {
        $path = is_callable($template) ? $template() : $template;
        if ($path) {
            // Проверяем полный путь в storage
            $fullPath = storage_path('app/' . $path);
            if (file_exists($fullPath)) {
                return asset('storage/' . str_replace('storage/app/public/', '', $path));
            }
            
            // Проверяем в public
            $publicPath = public_path($path);
            if (file_exists($publicPath)) {
                return asset($path);
            }
            
            // Проверяем напрямую storage/public
            $storagePublicPath = storage_path('app/public/' . $path);
            if (file_exists($storagePublicPath)) {
                return asset('storage/' . $path);
            }
        }
    }
    
    return null;
}

/**
 * Сгенерировать превью из PDF
 */
private function generatePdfPreview($filePath, $pageNumber)
{
    try {
        $pdfPath = public_path($filePath);
        
        if (!file_exists($pdfPath)) {
            return null;
        }
        
        // Создаем директорию для превью, если её нет
        $previewDir = storage_path('app/public/document-previews');
        if (!file_exists($previewDir)) {
            mkdir($previewDir, 0755, true);
        }
        
        // Генерируем имя файла превью
        $pdfHash = md5($filePath . $pageNumber);
        $previewFilename = $pdfHash . '.jpg';
        $previewPath = $previewDir . '/' . $previewFilename;
        
        // Если превью уже существует, возвращаем его
        if (file_exists($previewPath)) {
            return asset('storage/document-previews/' . $previewFilename);
        }
        
        // Пытаемся использовать Imagick для создания превью
        if (extension_loaded('imagick')) {
            $imagick = new \Imagick();
            $imagick->setResolution(150, 150);
            $imagick->readImage($pdfPath . '[' . ($pageNumber - 1) . ']'); // Нумерация с 0
            $imagick->setImageFormat('jpg');
            $imagick->setImageCompressionQuality(85);
            $imagick->writeImage($previewPath);
            $imagick->clear();
            $imagick->destroy();
            
            if (file_exists($previewPath)) {
                return asset('storage/document-previews/' . $previewFilename);
            }
        }
        
        // Альтернатива: пробуем использовать командную строку с ghostscript
        if ($this->hasGhostscript()) {
            $output = shell_exec("gs -dNOPAUSE -sDEVICE=jpeg -dFirstPage={$pageNumber} -dLastPage={$pageNumber} " .
                                "-dJPEGQ=85 -r150 -sOutputFile=\"{$previewPath}\" \"{$pdfPath}\" 2>&1");
            
            if (file_exists($previewPath)) {
                return asset('storage/document-previews/' . $previewFilename);
            }
        }
        
    } catch (\Exception $e) {
        Log::error('Error generating PDF preview: ' . $e->getMessage());
    }
    
    return null;
}

/**
 * Проверить наличие ghostscript
 */
private function hasGhostscript()
{
    $output = shell_exec('gs --version 2>&1');
    return !empty($output) && strpos($output, 'GPL Ghostscript') !== false;
}

/**
 * Получить дефолтное изображение для типа файла
 */
private function getDefaultPreviewImage($fileType)
{
    $fileType = strtolower($fileType);
    
    // Дефолтные иконки Font Awesome для типов файлов
    $icons = [
        'pdf' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/svgs/solid/file-pdf.svg',
        'doc' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/svgs/solid/file-word.svg',
        'docx' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/svgs/solid/file-word.svg',
        'xls' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/svgs/solid/file-excel.svg',
        'xlsx' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/svgs/solid/file-excel.svg',
        'jpg' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/svgs/solid/file-image.svg',
        'png' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/svgs/solid/file-image.svg',
        'jpeg' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/svgs/solid/file-image.svg',
        'txt' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/svgs/solid/file-lines.svg',
    ];
    
    return $icons[$fileType] ?? 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/svgs/solid/file.svg';
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
            
            return $parts->map(function($item) {
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
            $response .= "✅ **Найдено симптомов:** " . count($results) . "\n\n";
            
            // Показываем топ-3 результата
            $topResults = array_slice($results, 0, 3);
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
                
                $response .= " - {$relevance}%\n";
                
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
        } else {
            $response .= "⚠️ **Совпадений не найдено.**\n\n";
            $response .= "💡 **Рекомендации:**\n";
            $response .= "• Проверьте правильность написания\n";
            $response .= "• Используйте более простые формулировки\n";
            $response .= "• Уточните детали проблемы\n";
        }
        
        if (!empty($documents)) {
            $response .= "📄 **Найдено документов:** " . count($documents) . "\n";
            
            // Показываем топ документ
            $topDoc = $documents[0] ?? null;
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
            $response .= "🛒 **Найдено запчастей:** " . count($parts) . "\n";
        }
        
        $response .= "\n💡 **Следующие шаги:**\n";
        $response .= "1. Изучите диагностические шаги\n";
        $response .= "2. Проверьте возможные причины\n";
        
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
     * Вспомогательные методы
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
            
            if (!empty($sectionLower) && strpos($sectionLower, $termLower) !== false) {
                $score += 0.5;
            }
            
            if (preg_match('/\b' . preg_quote($termLower, '/') . '\b/', $contentLower)) {
                $score += 0.3;
            }
            elseif (strpos($contentLower, $termLower) !== false) {
                $score += 0.1;
            }
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

     /**
     * Создать диагностический случай из пустого поиска
     */
    public function createCaseFromSearch(Request $request)
    {
        Log::info('=== CREATE CASE FROM SEARCH START ===', [
            'user' => Auth::id(),
            'data' => $request->except(['_token'])
        ]);

        try {
            // Валидация
            $validator = Validator::make($request->all(), [
                'query' => 'required|string|max:1000',
                'brand_id' => 'required|string|max:255',
                'model_id' => 'nullable|integer',
                'year' => 'nullable|integer|min:1990|max:' . date('Y'),
                'vin' => 'nullable|string|max:17',
                'mileage' => 'nullable|integer|min:0|max:1000000',
                'engine_type' => 'nullable|string|max:50',
                'description' => 'required|string|min:10|max:2000',
                'additional_info' => 'nullable|string|max:1000',
                'contact_phone' => 'nullable|string|max:20',
                'contact_email' => 'nullable|email',
                'symptom_photos' => 'nullable|array',
                'symptom_photos.*' => 'image|mimes:jpeg,png,jpg,gif|max:10240',
                'symptom_videos' => 'nullable|array',
                'symptom_videos.*' => 'mimes:mp4,mov,avi|max:51200',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                    'message' => 'Пожалуйста, исправьте ошибки в форме'
                ], 422);
            }

            $validated = $validator->validated();

            DB::beginTransaction();

            // Получаем или создаем правило для "неизвестной проблемы"
            $rule = $this->getOrCreateUnknownRule($validated['query']);
            
            // Получаем информацию о пользователе
            $user = Auth::user();
            
            // Создаем диагностический случай
            $case = new DiagnosticCase();
            $case->user_id = Auth::id();
            $case->rule_id = $rule->id;
            $case->brand_id = $validated['brand_id'];
            $case->model_id = $validated['model_id'] ?? null;
            $case->year = $validated['year'] ?? null;
            $case->vin = $validated['vin'] ?? null;
            $case->mileage = $validated['mileage'] ?? null;
            $case->engine_type = $validated['engine_type'] ?? null;
            $case->symptoms = json_encode([]);
            $case->description = $validated['description'] ?? $validated['query'];
            $case->status = 'consultation_pending';
            $case->step = 5;
            $case->price_estimate = 3000; // Базовая цена консультации
            $case->contact_name = $user ? ($user->name ?? $user->email) : null;
            $case->contact_phone = $validated['contact_phone'] ?? ($user->phone ?? null);
            $case->contact_email = $validated['contact_email'] ?? ($user->email ?? null);
            $case->contacted_at = now();
            $case->save();

            Log::info('Case created from search', ['case_id' => $case->id]);

            // Обработка файлов
            $files = [];
            if ($request->hasFile('symptom_photos')) {
                foreach ($request->file('symptom_photos') as $photo) {
                    $path = $photo->store('diagnostic/cases/' . $case->id . '/photos', 'public');
                    $files[] = [
                        'type' => 'photo',
                        'path' => $path,
                        'original_name' => $photo->getClientOriginalName()
                    ];
                }
            }

            if ($request->hasFile('symptom_videos')) {
                foreach ($request->file('symptom_videos') as $video) {
                    $path = $video->store('diagnostic/cases/' . $case->id . '/videos', 'public');
                    $files[] = [
                        'type' => 'video',
                        'path' => $path,
                        'original_name' => $video->getClientOriginalName()
                    ];
                }
            }

            // Сохраняем информацию о файлах в дополнительном поле
            if (!empty($files)) {
                $case->additional_data = json_encode([
                    'files' => $files,
                    'additional_info' => $validated['additional_info'] ?? null,
                    'created_from' => 'search_no_results'
                ]);
                $case->save();
            } elseif (!empty($validated['additional_info'])) {
                $case->additional_data = json_encode([
                    'additional_info' => $validated['additional_info'],
                    'created_from' => 'search_no_results'
                ]);
                $case->save();
            }

            DB::commit();

            // Получаем бренд для отображения
            $brand = Brand::find($validated['brand_id']);
            
            return response()->json([
                'success' => true,
                'message' => '✅ Диагностический случай создан! Наши специалисты свяжутся с вами в ближайшее время.',
                'case_id' => $case->id,
                'redirect_url' => route('diagnostic.consultation.order', ['case_id' => $case->id]),
                'case_data' => [
                    'id' => $case->id,
                    'brand' => $brand ? $brand->name : 'Неизвестная марка',
                    'model' => $validated['model_id'] ? CarModel::find($validated['model_id'])?->name : null,
                    'created_at' => $case->created_at->format('d.m.Y H:i'),
                    'status' => 'Ожидает консультации'
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating case from search: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '❌ Ошибка при создании диагностического случая: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить или создать правило для неизвестной проблемы
     */
    private function getOrCreateUnknownRule($query)
    {
        // Ищем существующее правило для неизвестных случаев
        $rule = Rule::where('is_active', true)
            ->where('is_default', true)
            ->where('name', 'like', '%неизвестная проблема%')
            ->first();

        if (!$rule) {
            // Создаем новое правило
            $rule = new Rule();
            $rule->name = 'Неизвестная диагностическая проблема';
            $rule->symptom_id = $this->getOrCreateUnknownSymptom();
            $rule->possible_causes = ['Требуется диагностика специалистом'];
            $rule->diagnostic_steps = [
                'Подробное описание проблемы',
                'Сбор дополнительной информации',
                'Консультация с экспертом'
            ];
            $rule->required_data = [
                'Марка автомобиля',
                'Модель автомобиля',
                'Описание проблемы'
            ];
            $rule->complexity_level = 5;
            $rule->estimated_time = 60;
            $rule->base_consultation_price = 3000;
            $rule->is_active = true;
            $rule->is_default = true;
            $rule->save();
        }

        return $rule;
    }

    /**
     * Получить или создать симптом для неизвестной проблемы
     */
    private function getOrCreateUnknownSymptom()
    {
        $symptom = Symptom::where('is_active', true)
            ->where('name', 'like', '%неизвестная неисправность%')
            ->first();

        if (!$symptom) {
            $symptom = new Symptom();
            $symptom->name = 'Неизвестная неисправность';
            $symptom->description = 'Проблема не найдена в базе данных. Требуется дополнительная диагностика.';
            $symptom->category = 'diagnostic';
            $symptom->is_active = true;
            $symptom->save();
        }

        return $symptom->id;
    }
}