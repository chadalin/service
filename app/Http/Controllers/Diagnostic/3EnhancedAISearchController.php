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
       Log::debug('Searching request enhancedSearch', ['request'=>$request]);
    //dd($reques);
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
        
        // Получаем параметры - ВАЖНО: brand_id передается из формы
        $query = trim($request->input('query'));
        $brandId = $request->input('brand_id'); // Это строка типа "ALFA_ROMEO" из формы
        $modelId = $request->input('model_id');
        $searchType = $request->input('search_type', 'advanced');

        Log::info('Enhanced AI Search Started', [
            'query' => $query,
            'brand_id' => $brandId,
            'model_id' => $modelId,
            'search_type' => $searchType,
            'all_params' => $request->all()
        ]);

        // Получаем объект бренда для использования
        $brand = null;
        if ($brandId) {
            $brand = Brand::find($brandId);
            Log::info('Brand found from form', [
                'brand_id' => $brandId,
                'brand_exists' => $brand ? 'YES' : 'NO',
                'brand_name' => $brand ? $brand->name : 'N/A'
            ]);
        }

        try {
            // 1. Поиск симптомов с фильтрацией по бренду
            $groupedResults = $this->searchSymptomsWithRules($query, $brandId);
            
            // 2. Поиск документов (включая коды ошибок)
            $documents = $this->searchDocuments($query, $brand, $modelId);
            
            // 3. Поиск запчастей
            $parts = [];
            if (!empty($groupedResults)) {
                $parts = $this->searchParts($query, $brand);
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
        Log::debug('Searching symptoms with rules', ['query' => $query, 'brand_id' => $brandId]);
        
        $results = [];
        $cleanQuery = $this->normalizeSearchQuery($query);
        $searchTerms = $this->extractSearchTerms($cleanQuery);
        
        // 1. Сначала ищем правила с фильтрацией по бренду
        $rulesQuery = Rule::where('is_active', true)
            ->with(['symptom' => function($q) {
                $q->where('is_active', true);
            }, 'brand', 'model']);
        
        // Фильтрация по бренду если указан
        if ($brandId) {
            $rulesQuery->where('brand_id', $brandId);
            Log::debug('Filtering rules by brand', ['brand_id' => $brandId]);
        }
        
        // Поиск по симптомам
        $rulesQuery->whereHas('symptom', function($q) use ($searchTerms) {
            $q->where(function($subQ) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    if (mb_strlen($term) > 2) {
                        $subQ->orWhere('name', 'like', "%{$term}%")
                             ->orWhere('description', 'like', "%{$term}%");
                    }
                }
            });
        });
        
        $rules = $rulesQuery->get();
        Log::debug('Rules found', ['count' => $rules->count(), 'brand_filter' => $brandId]);
        
        // Обработка найденных правил
        foreach ($rules as $rule) {
            if ($rule->symptom) {
                $relevance = $this->calculateRelevance($rule->symptom->name, $rule->symptom->description, $query);
                
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
                    'match_type' => 'exact',
                    'has_rules' => true,
                    'related_systems' => $rule->symptom->related_systems ?? [],
                    'frequency' => $rule->symptom->frequency ?? 0,
                ];
            }
        }
        
        // 2. Если ничего не найдено или нет фильтра по бренду, ищем общие симптомы
        if (empty($results)) {
            $symptomsQuery = Symptom::where('is_active', true)
                ->with(['rules' => function($q) use ($brandId) {
                    $q->where('is_active', true)
                      ->when($brandId, function($q) use ($brandId) {
                          $q->where('brand_id', $brandId);
                      })
                      ->with(['brand', 'model']);
                }]);
            
            $symptomsQuery->where(function($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    if (mb_strlen($term) > 2) {
                        $q->orWhere('name', 'like', "%{$term}%")
                          ->orWhere('description', 'like', "%{$term}%");
                    }
                }
            });
            
            $symptoms = $symptomsQuery->get();
            
            foreach ($symptoms as $symptom) {
                $relevance = $this->calculateRelevance($symptom->name, $symptom->description, $query);
                
                // Если есть правила для этого симптома
                if ($symptom->rules->isNotEmpty()) {
                    foreach ($symptom->rules as $rule) {
                        // Пропускаем если фильтр по бренду и правило другого бренда
                        if ($brandId && $rule->brand_id !== $brandId) {
                            continue;
                        }
                        
                        $results[] = [
                            'type' => 'rule',
                            'id' => $rule->id,
                            'symptom_id' => $symptom->id,
                            'title' => $symptom->name,
                            'description' => $symptom->description ?? '',
                            'brand' => $rule->brand ? $rule->brand->name : '',
                            'brand_id' => $rule->brand_id,
                            'model' => $rule->model ? $rule->model->name : '',
                            'model_id' => $rule->model_id,
                            'diagnostic_steps' => is_array($rule->diagnostic_steps) ? $rule->diagnostic_steps : [],
                            'possible_causes' => is_array($rule->possible_causes) ? $rule->possible_causes : [],
                            'relevance_score' => $relevance,
                            'match_type' => 'symptom',
                            'has_rules' => true,
                        ];
                    }
                } else {
                    // Симптом без правил
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
        }
        
        // Сортировка по релевантности
        usort($results, function($a, $b) {
            return $b['relevance_score'] <=> $a['relevance_score'];
        });
        
        return array_slice($results, 0, 10);
    }

    /**
     * Поиск документов (включая коды ошибок)
     */
    private function searchDocuments($query, $brand = null, $modelId = null)
    {
        Log::debug('Searching documents', [
            'query' => $query, 
            'brand' => $brand ? $brand->name : 'N/A',
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
            
            // Базовый запрос без фильтров - ищем ВСЕ документы
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
            
            // Поиск по всем терминам - только это условие ОБЯЗАТЕЛЬНО
            $pagesQuery->where(function($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $cleanTerm = $this->cleanSearchTerm($term);
                    if (!empty($cleanTerm)) {
                        $q->orWhere('document_pages.content_text', 'like', "%{$cleanTerm}%")
                          ->orWhere('document_pages.section_title', 'like', "%{$cleanTerm}%");
                    }
                }
            });
            
            // Фильтрация по модели если указана
            if ($modelId) {
                Log::debug('Filtering by model_id', ['model_id' => $modelId]);
                $pagesQuery->where('documents.car_model_id', $modelId);
            }
            // Если указан бренд, но нет модели - фильтруем через модели этого бренда
            elseif ($brand) {
                Log::debug('Filtering by brand', ['brand_id' => $brand->id, 'brand_name' => $brand->name]);
                $modelIds = CarModel::where('brand_id', $brand->id)->pluck('id');
                if ($modelIds->isNotEmpty()) {
                    $pagesQuery->whereIn('documents.car_model_id', $modelIds);
                }
            }
            
            $pages = $pagesQuery
                ->orderByRaw('
                    CASE 
                        WHEN document_pages.section_title LIKE "%диагностик%" THEN 1
                        WHEN document_pages.section_title LIKE "%ремонт%" THEN 2
                        WHEN document_pages.section_title LIKE "%неисправн%" THEN 3
                        WHEN document_pages.section_title LIKE "%код ошиб%" THEN 4
                        WHEN document_pages.section_title LIKE "%error%" THEN 5
                        ELSE 6
                    END
                ')
                ->orderBy('documents.view_count', 'desc')
                ->orderBy('document_pages.page_number')
                ->limit(200) // Большой лимит для лучшего поиска
                ->get();
            
            Log::debug('Document pages found', ['count' => $pages->count()]);
            
            if ($pages->isEmpty()) {
                // Если с фильтрами ничего не найдено, ищем без фильтров
                Log::debug('Searching without filters');
                return $this->searchAllDocuments($query);
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
                        'content_preview' => $this->getContentPreview($page->content_text, $searchTerms, 500),
                        'search_terms_found' => $this->getFoundTerms($page->content_text, $searchTerms),
                        'is_filtered' => $brand || $modelId ? true : false
                    ];
                }
            }
            
            // Сортируем по релевантности
            usort($groupedDocuments, function($a, $b) {
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
                            $q->orWhere('document_pages.content_text', 'like', "%{$cleanTerm}%");
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
                    'is_filtered' => false
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
     * Поиск запчастей
     */
    private function searchParts($query, $brand = null)
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
            
            // Фильтрация по бренду
            if ($brand) {
                $partsQuery->where(function($q) use ($brand) {
                    $q->orWhere('catalog_brand', 'like', "%{$brand->name}%")
                      ->orWhere('catalog_brand', 'like', "%{$brand->name_cyrillic}%")
                      ->orWhere('brand_id', $brand->id);
                });
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
                ->limit(5)
                ->get();
            
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
        $brandName = 'неизвестной марки';
        if ($brand) {
            $brandName = $brand->name_cyrillic ?? $brand->name;
        }
        
        $response = "🤖 **AI-анализ диагностической проблемы**\n\n";
        $response .= "🔍 **Запрос:** {$query}\n";
        $response .= "🏷️ **Марка:** {$brandName}\n\n";
        
        if (!empty($results)) {
            $filteredCount = count(array_filter($results, function($item) use ($brand) {
                return !$brand || $item['brand_id'] === $brand->id;
            }));
            
            $response .= "✅ **Найдено симптомов:** " . count($results) . " ";
            if ($brand && $filteredCount < count($results)) {
                $response .= "(" . $filteredCount . " для " . $brandName . ")";
            }
            $response .= "\n\n";
            
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
            $filteredDocs = count(array_filter($documents, function($doc) use ($brand) {
                return !$brand || ($doc['brand'] && strpos($doc['brand'], $brand->name) !== false);
            }));
            
            $response .= "📄 **Найдено документов:** " . count($documents) . " ";
            if ($brand && $filteredDocs < count($documents)) {
                $response .= "(" . $filteredDocs . " для " . $brandName . ")";
            }
            $response .= "\n";
            
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
        $score = 0;
        $queryLower = mb_strtolower($query, 'UTF-8');
        $titleLower = mb_strtolower($title, 'UTF-8');
        $descLower = mb_strtolower($description, 'UTF-8');
        
        if (strpos($titleLower, $queryLower) !== false) {
            $score += 1.0;
        }
        
        if (strpos($descLower, $queryLower) !== false) {
            $score += 0.5;
        }
        
        $queryWords = $this->extractSearchTerms($queryLower);
        $titleWords = $this->extractSearchTerms($titleLower);
        $descWords = $this->extractSearchTerms($descLower);
        
        foreach ($queryWords as $qWord) {
            if (mb_strlen($qWord) < 3) continue;
            
            foreach ($titleWords as $tWord) {
                if (strpos($tWord, $qWord) !== false) {
                    $score += 0.3;
                    break;
                }
            }
            
            foreach ($descWords as $dWord) {
                if (strpos($dWord, $qWord) !== false) {
                    $score += 0.1;
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
            // Предполагаем, что есть маршрут для просмотра страницы документа
            return route('documents.page.view', [
                'id' => $documentId,
                'page' => $pageNumber
            ]);
        } catch (\Exception $e) {
            try {
                // Или маршрут для документа с параметром страницы
                return route('documents.view', [
                    'id' => $documentId,
                    'page' => $pageNumber
                ]);
            } catch (\Exception $e2) {
                // Последний вариант - ручная генерация URL
                return '/documents/' . $documentId . '/page/' . $pageNumber;
            }
        }
    }
}