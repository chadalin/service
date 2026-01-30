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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        
        // Получаем статистику с проверкой существования таблиц
        $stats = [
            'symptoms_count' => Symptom::where('is_active', true)->count(),
            'rules_count' => Rule::where('is_active', true)->count(),
            'brands_count' => Brand::count(),
            'models_count' => CarModel::count(),
        ];
        
        // Добавляем статистику по документам если таблица существует
        if (Schema::hasTable('documents')) {
            $stats['documents_count'] = Document::where('status', 'active')->count();
        } else {
            $stats['documents_count'] = 0;
        }
        
        // Добавляем статистику по запчастям если таблица существует
        if (Schema::hasTable('price_items')) {
            // Проверяем существование колонки quantity
            if (Schema::hasColumn('price_items', 'quantity')) {
                $stats['price_items_count'] = PriceItem::where('quantity', '>', 0)->count();
            } else {
                $stats['price_items_count'] = PriceItem::count();
            }
        } else {
            $stats['price_items_count'] = 0;
        }
        
        return view('diagnostic.ai-search.enhanced', compact('brands', 'models', 'stats'));
    }

    /**
     * Выполнить расширенный AI поиск
     */
    public function enhancedSearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|max:1000',
            'brand_id' => 'nullable|integer',
            'model_id' => 'nullable|integer',
            'search_type' => 'nullable|in:basic,advanced,full',
            'show_parts' => 'nullable|boolean',
            'show_docs' => 'nullable|boolean',
            'max_results' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        $startTime = microtime(true);
        $query = trim($request->input('query'));
        $brandId = $request->input('brand_id');
        $modelId = $request->input('model_id');
        $searchType = $request->input('search_type', 'basic');
        $showParts = $request->boolean('show_parts', true);
        $showDocs = $request->boolean('show_docs', true);
        $maxResults = $request->input('max_results', 15);

        Log::info('Enhanced AI Search', [
            'query' => $query,
            'brand_id' => $brandId,
            'model_id' => $modelId,
            'search_type' => $searchType,
        ]);

        try {
            // 1. Поиск симптомов и правил
            $searchResults = $this->searchSymptomsAndRules($query, $brandId, $modelId, $searchType);
            
            // 2. Поиск запчастей если нужно и если таблица существует
            $partsResults = [];
            if ($showParts && Schema::hasTable('price_items')) {
                $partsResults = $this->searchMatchingParts($searchResults, $brandId);
            }
            
            // 3. Поиск документов если нужно и если таблица существует
            $docsResults = [];
            if ($showDocs && Schema::hasTable('documents')) {
                $docsResults = $this->searchRelatedDocuments($searchResults, $brandId, $modelId);
            }
            
            // 4. Генерируем интегрированный AI ответ
            $aiResponse = $this->generateIntegratedAIResponse($query, $searchResults, $partsResults, $docsResults, $brandId, $modelId);
            
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            return response()->json([
                'success' => true,
                'query' => $query,
                'results' => $searchResults,
                'parts' => $partsResults,
                'documents' => $docsResults,
                'ai_response' => $aiResponse,
                'search_type' => $searchType,
                'execution_time' => $executionTime,
                'stats' => [
                    'symptoms_found' => count($searchResults),
                    'parts_found' => count($partsResults),
                    'documents_found' => count($docsResults),
                    'total_results' => count($searchResults) + count($partsResults) + count($docsResults),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Enhanced AI Search Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при выполнении поиска: ' . $e->getMessage(),
                'query' => $query
            ], 500);
        }
    }

    /**
     * Поиск симптомов и правил с учетом бренда
     */
    private function searchSymptomsAndRules($query, $brandId = null, $modelId = null, $searchType = 'basic')
    {
        $keywords = $this->extractKeywords($query);
        
        if (empty($keywords)) {
            return [];
        }

        $symptomsQuery = Symptom::where('is_active', true)
            ->with(['rules' => function($q) use ($brandId, $modelId) {
                $q->where('is_active', true)
                  ->with(['brand', 'model']);
                
                if ($brandId) {
                    $q->where('brand_id', $brandId);
                }
                
                if ($modelId) {
                    $q->where('model_id', $modelId);
                }
            }]);

        if ($searchType === 'advanced' || $searchType === 'full') {
            // Расширенный поиск с учетом всех полей
            $symptomsQuery->where(function($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    if (strlen($keyword) > 2) {
                        $q->orWhere('name', 'like', "%{$keyword}%")
                          ->orWhere('description', 'like', "%{$keyword}%")
                          ->orWhere('related_systems', 'like', "%{$keyword}%");
                    }
                }
            });
        } else {
            // Базовый поиск
            $symptomsQuery->where(function($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    if (strlen($keyword) > 2) {
                        $q->orWhere('name', 'like', "%{$keyword}%")
                          ->orWhere('description', 'like', "%{$keyword}%");
                    }
                }
            });
        }

        $symptoms = $symptomsQuery->get();
        
        // Формируем результаты с релевантностью
        $results = [];
        
        foreach ($symptoms as $symptom) {
            $relevanceScore = $this->calculateRelevance($symptom, $keywords);
            
            if ($symptom->rules->isNotEmpty()) {
                foreach ($symptom->rules as $rule) {
                    $results[] = [
                        'type' => 'rule',
                        'id' => $rule->id,
                        'symptom_id' => $symptom->id,
                        'title' => $symptom->name,
                        'description' => $symptom->description,
                        'brand' => $rule->brand->name ?? null,
                        'brand_id' => $rule->brand_id,
                        'model' => $rule->model->name ?? null,
                        'model_id' => $rule->model_id,
                        'diagnostic_steps' => $rule->diagnostic_steps ?? [],
                        'possible_causes' => $rule->possible_causes ?? [],
                        'required_data' => $rule->required_data ?? [],
                        'complexity_level' => $rule->complexity_level ?? 1,
                        'estimated_time' => $rule->estimated_time ?? 60,
                        'consultation_price' => $rule->base_consultation_price ?? 3000,
                        'relevance_score' => $relevanceScore,
                        'matched_keywords' => $keywords,
                    ];
                }
            } else {
                $results[] = [
                    'type' => 'symptom',
                    'id' => $symptom->id,
                    'title' => $symptom->name,
                    'description' => $symptom->description,
                    'relevance_score' => $relevanceScore,
                    'has_rules' => false,
                    'matched_keywords' => $keywords,
                ];
            }
        }
        
        // Сортируем по релевантности
        usort($results, function($a, $b) {
            return $b['relevance_score'] <=> $a['relevance_score'];
        });
        
        return array_slice($results, 0, 10);
    }

    /**
     * Улучшенный поиск соответствующих запчастей
     */
    private function searchMatchingParts($symptoms, $brandId = null)
    {
        if (empty($symptoms)) {
            return [];
        }
        
        $searchTerms = [];
        
        // Собираем ключевые слова из симптомов и возможных причин
        foreach ($symptoms as $symptom) {
            // Добавляем название симптома (только ключевые слова)
            $titleWords = $this->extractKeywords($symptom['title']);
            $searchTerms = array_merge($searchTerms, $titleWords);
            
            // Добавляем возможные причины из правил
            if (!empty($symptom['possible_causes']) && is_array($symptom['possible_causes'])) {
                foreach ($symptom['possible_causes'] as $cause) {
                    $causeWords = $this->extractKeywords($cause);
                    $searchTerms = array_merge($searchTerms, $causeWords);
                }
            }
        }
        
        // Фильтруем и уникализируем
        $searchTerms = array_filter(array_unique($searchTerms), function($term) {
            return strlen($term) > 2 && !$this->isStopWord($term);
        });
        
        if (empty($searchTerms)) {
            return [];
        }
        
        // Получаем доступные колонки таблицы price_items
        $tableColumns = Schema::getColumnListing('price_items');
        $selectColumns = ['id', 'sku', 'name', 'description', 'price'];
        
        // Добавляем только существующие колонки
        $availableColumns = [
            'quantity', 'catalog_brand', 'brand_id', 'category',
            'image_url', 'unit', 'min_order_qty'
        ];
        
        foreach ($availableColumns as $column) {
            if (in_array($column, $tableColumns)) {
                $selectColumns[] = $column;
            }
        }
        
        $partsQuery = PriceItem::query()
            ->select($selectColumns);
        
        // Условия наличия
        if (in_array('quantity', $tableColumns)) {
            $partsQuery->where('quantity', '>', 0);
        }
        
        if (in_array('price', $tableColumns)) {
            $partsQuery->where('price', '>', 0);
        }
        
        // Фильтр по бренду автомобиля если указан
        if ($brandId && in_array('catalog_brand', $tableColumns)) {
            $brand = Brand::find($brandId);
            if ($brand) {
                $partsQuery->where(function($q) use ($brand, $tableColumns) {
                    if (in_array('catalog_brand', $tableColumns)) {
                        $q->orWhere('catalog_brand', 'like', "%{$brand->name}%");
                    }
                    if (in_array('brand_id', $tableColumns)) {
                        $q->orWhere('brand_id', $brandId);
                    }
                });
            }
        }
        
        // Поиск по ключевым словам
        $partsQuery->where(function($q) use ($searchTerms, $tableColumns) {
            foreach ($searchTerms as $term) {
                $term = trim($term);
                if (strlen($term) > 2) {
                    if (in_array('name', $tableColumns)) {
                        $q->orWhere('name', 'like', "%{$term}%");
                    }
                    if (in_array('description', $tableColumns)) {
                        $q->orWhere('description', 'like', "%{$term}%");
                    }
                    if (in_array('sku', $tableColumns)) {
                        $q->orWhere('sku', 'like', "%{$term}%");
                    }
                    if (in_array('catalog_brand', $tableColumns)) {
                        $q->orWhere('catalog_brand', 'like', "%{$term}%");
                    }
                }
            }
        });
        
        try {
            $parts = $partsQuery->limit(20)->get();
            
            return $parts->map(function($item) use ($tableColumns) {
                $partData = [
                    'id' => $item->id,
                    'sku' => $item->sku ?? '',
                    'name' => $item->name ?? '',
                    'description' => $item->description ?? '',
                    'price' => $item->price ?? 0,
                    'formatted_price' => number_format($item->price ?? 0, 2, '.', ' '),
                ];
                
                // Добавляем только существующие поля
                if (in_array('quantity', $tableColumns)) {
                    $quantity = $item->quantity ?? 0;
                    $partData['quantity'] = $quantity;
                    $partData['availability'] = $quantity > 10 ? 'В наличии' : 
                                               ($quantity > 0 ? 'Мало' : 'Нет в наличии');
                }
                
                if (in_array('catalog_brand', $tableColumns) && !empty($item->catalog_brand)) {
                    $partData['brand'] = $item->catalog_brand;
                }
                
                if (in_array('category', $tableColumns) && !empty($item->category)) {
                    $partData['category'] = $item->category;
                }
                
                if (in_array('image_url', $tableColumns) && !empty($item->image_url)) {
                    $partData['image_url'] = $item->image_url;
                }
                
                return $partData;
            })->toArray();
            
        } catch (\Exception $e) {
            Log::error('Error searching parts: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Поиск связанных документов
     */
    private function searchRelatedDocuments($symptoms, $brandId = null, $modelId = null)
    {
        if (empty($symptoms) || !Schema::hasTable('documents')) {
            return [];
        }
        
        $searchTerms = [];
        
        foreach ($symptoms as $symptom) {
            $titleWords = $this->extractKeywords($symptom['title']);
            $searchTerms = array_merge($searchTerms, $titleWords);
        }
        
        $searchTerms = array_filter(array_unique($searchTerms), function($term) {
            return strlen($term) > 2;
        });
        
        if (empty($searchTerms)) {
            return [];
        }
        
        // Получаем доступные колонки таблицы documents
        $tableColumns = Schema::getColumnListing('documents');
        $selectColumns = ['id', 'title'];
        
        // Добавляем только существующие колонки
        $availableColumns = [
            'content_text', 'total_pages', 'file_type', 'file_path', 'source_url',
            'detected_section', 'detected_system', 'detected_component',
            'search_count', 'view_count'
        ];
        
        foreach ($availableColumns as $column) {
            if (in_array($column, $tableColumns)) {
                $selectColumns[] = $column;
            }
        }
        
        $docsQuery = Document::query()
            ->select($selectColumns);
        
        if (in_array('status', $tableColumns)) {
            $docsQuery->where('status', 'active');
        }
        
        if (in_array('is_parsed', $tableColumns)) {
            $docsQuery->where('is_parsed', true);
        }
        
        // Фильтр по модели автомобиля если указана
        if ($modelId && in_array('car_model_id', $tableColumns)) {
            $docsQuery->where('car_model_id', $modelId);
        } elseif ($brandId && in_array('car_model_id', $tableColumns)) {
            // Или фильтр по бренду через модель
            $docsQuery->whereHas('carModel', function($q) use ($brandId) {
                $q->where('brand_id', $brandId);
            });
        }
        
        // Поиск по ключевым словам
        $docsQuery->where(function($q) use ($searchTerms, $tableColumns) {
            foreach ($searchTerms as $term) {
                $term = trim($term);
                if (strlen($term) > 2) {
                    if (in_array('title', $tableColumns)) {
                        $q->orWhere('title', 'like', "%{$term}%");
                    }
                    if (in_array('content_text', $tableColumns)) {
                        $q->orWhere('content_text', 'like', "%{$term}%");
                    }
                    if (in_array('keywords_text', $tableColumns)) {
                        $q->orWhere('keywords_text', 'like', "%{$term}%");
                    }
                }
            }
        });
        
        try {
            $documents = $docsQuery->limit(10)->get();
            
            return $documents->map(function($doc) use ($tableColumns) {
                $docData = [
                    'id' => $doc->id,
                    'title' => $doc->title ?? '',
                    'icon' => $this->getFileIcon($doc->file_type ?? ''),
                ];
                
                // Добавляем только существующие поля
                if (in_array('content_text', $tableColumns) && !empty($doc->content_text)) {
                    $docData['excerpt'] = $this->truncateText($doc->content_text, 200);
                }
                
                if (in_array('total_pages', $tableColumns)) {
                    $docData['total_pages'] = $doc->total_pages;
                }
                
                if (in_array('file_type', $tableColumns) && !empty($doc->file_type)) {
                    $docData['file_type'] = $doc->file_type;
                }
                
                if (in_array('file_path', $tableColumns) && !empty($doc->file_path)) {
                    $docData['file_path'] = $doc->file_path;
                }
                
                if (in_array('view_count', $tableColumns)) {
                    $docData['views'] = $doc->view_count ?? 0;
                }
                
                return $docData;
            })->toArray();
            
        } catch (\Exception $e) {
            Log::error('Error searching documents: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Генерация интегрированного AI ответа
     */
    private function generateIntegratedAIResponse($query, $symptoms, $parts, $docs, $brandId = null, $modelId = null)
    {
        $response = "🤖 **AI-анализ диагностической проблемы**\n\n";
        $response .= "🔍 **Запрос:** {$query}\n";
        
        if ($brandId) {
            $brand = Brand::find($brandId);
            $response .= "🏷️ **Марка:** {$brand->name}\n";
        }
        
        $response .= "\n📊 **Обнаружено:**\n";
        $response .= "• 🔧 **Симптомы и правила:** " . count($symptoms) . "\n";
        $response .= "• 🛒 **Запчасти:** " . count($parts) . "\n";
        $response .= "• 📄 **Документы:** " . count($docs) . "\n";
        
        if (!empty($symptoms)) {
            $response .= "\n🎯 **Топ симптомы и решения:**\n\n";
            
            foreach (array_slice($symptoms, 0, 3) as $index => $item) {
                $response .= ($index + 1) . ". **{$item['title']}**\n";
                
                if (!empty($item['brand'])) {
                    $response .= "   🚗 Для: {$item['brand']}";
                    if (!empty($item['model'])) {
                        $response .= " {$item['model']}";
                    }
                    $response .= "\n";
                }
                
                if (!empty($item['possible_causes']) && count($item['possible_causes']) > 0) {
                    $causes = implode(', ', array_slice($item['possible_causes'], 0, 2));
                    $response .= "   ⚠️ Возможные причины: {$causes}\n";
                }
                
                if (!empty($item['estimated_time'])) {
                    $response .= "   ⏱️ Примерное время: {$item['estimated_time']} мин.\n";
                }
                
                $response .= "   📈 Релевантность: " . round($item['relevance_score'] * 100) . "%\n\n";
            }
        }
        
        if (!empty($parts)) {
            $response .= "🛒 **Рекомендуемые запчасти:**\n\n";
            
            foreach (array_slice($parts, 0, 3) as $index => $part) {
                $response .= ($index + 1) . ". **{$part['name']}**\n";
                $response .= "   🔢 Артикул: {$part['sku']}\n";
                $response .= "   💰 Цена: {$part['formatted_price']} ₽\n";
                if (isset($part['availability'])) {
                    $response .= "   📦 Наличие: {$part['availability']}";
                    if (isset($part['quantity'])) {
                        $response .= " ({$part['quantity']} шт.)";
                    }
                    $response .= "\n";
                }
                if (!empty($part['brand'])) {
                    $response .= "   🏷️ Производитель: {$part['brand']}\n";
                }
                $response .= "\n";
            }
        }
        
        if (!empty($docs)) {
            $response .= "📄 **Инструкции и документы:**\n\n";
            
            foreach (array_slice($docs, 0, 3) as $index => $doc) {
                $response .= ($index + 1) . ". **{$doc['title']}**\n";
                if (!empty($doc['file_type'])) {
                    $response .= "   📂 Тип: {$doc['file_type']}";
                    if (!empty($doc['total_pages'])) {
                        $response .= " ({$doc['total_pages']} стр.)";
                    }
                    $response .= "\n";
                }
                if (isset($doc['views'])) {
                    $response .= "   👀 Просмотров: {$doc['views']}\n";
                }
                $response .= "\n";
            }
        }
        
        $response .= "💡 **Рекомендации по ремонту:**\n";
        $response .= "1. Изучите шаги диагностики для вашего симптома\n";
        $response .= "2. Проверьте рекомендуемые запчасти\n";
        $response .= "3. Ознакомьтесь с инструкциями по замене\n";
        $response .= "4. При необходимости закажите консультацию специалиста\n";
        
        return $response;
    }

    /**
     * Вспомогательные методы
     */
    private function extractKeywords($query)
    {
        $stopWords = ['и', 'или', 'но', 'на', 'в', 'с', 'по', 'у', 'о', 'об', 'за', 'из', 'к'];
        $query = mb_strtolower(trim($query));
        $words = preg_split('/[\s,\.\-\(\)\[\]:;!?]+/', $query);
        
        $keywords = array_filter($words, function($word) use ($stopWords) {
            $word = trim($word);
            return strlen($word) > 2 && !in_array($word, $stopWords);
        });
        
        return array_unique($keywords);
    }
    
    private function isStopWord($word)
    {
        $stopWords = [
            'неисправность', 'повреждение', 'проблема', 'симптом',
            'диагностика', 'ремонт', 'замена', 'проверка', 'завод',
            'двигатель', 'автомобиль', 'машина'
        ];
        
        return in_array(mb_strtolower($word), $stopWords);
    }

    private function calculateRelevance($symptom, $keywords)
    {
        $score = 0.0;
        $name = mb_strtolower($symptom->name);
        $description = mb_strtolower($symptom->description);
        
        foreach ($keywords as $keyword) {
            $keyword = mb_strtolower($keyword);
            
            if (strpos($name, $keyword) !== false) {
                $score += 0.4;
            }
            
            if (strpos($description, $keyword) !== false) {
                $score += 0.2;
            }
        }
        
        return min(1.0, $score);
    }

    private function truncateText($text, $length = 150)
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        $truncated = mb_substr($text, 0, $length);
        $lastSpace = mb_strrpos($truncated, ' ');
        
        if ($lastSpace !== false) {
            $truncated = mb_substr($truncated, 0, $lastSpace);
        }
        
        return $truncated . '...';
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
        
        return $icons[strtolower($fileType)] ?? 'bi-file-earmark';
    }

    /**
     * Получить детали правила с запчастями
     */
    public function showRuleWithParts($id)
    {
        try {
            $rule = Rule::with(['symptom', 'brand', 'model'])
                ->findOrFail($id);

            // Поиск запчастей с учетом бренда и возможных причин
            $parts = $this->findPartsForRule($rule);
            
            // Поиск документов
            $documents = $this->findDocumentsForRule($rule);
            
            // Генерируем инструкцию по ремонту
            $repairGuide = $this->generateRepairGuide($rule, $parts, $documents);
            
            return view('diagnostic.ai-search.rule-details', [
                'rule' => $rule,
                'parts' => $parts,
                'documents' => $documents,
                'repair_guide' => $repairGuide,
                'title' => 'Диагностика: ' . ($rule->symptom->name ?? 'Unknown')
            ]);
        } catch (\Exception $e) {
            Log::error('Error showing rule with parts', ['rule_id' => $id, 'error' => $e->getMessage()]);
            
            return redirect()->back()->with('error', 'Ошибка загрузки деталей: ' . $e->getMessage());
        }
    }

    /**
     * Поиск запчастей для правила с учетом бренда
     */
    private function findPartsForRule(Rule $rule)
    {
        if (!Schema::hasTable('price_items')) {
            return collect();
        }
        
        $searchTerms = [];
        
        // Добавляем название симптома
        if ($rule->symptom && $rule->symptom->name) {
            $titleWords = $this->extractKeywords($rule->symptom->name);
            $searchTerms = array_merge($searchTerms, $titleWords);
        }
        
        // Добавляем возможные причины
        if ($rule->possible_causes && is_array($rule->possible_causes)) {
            foreach ($rule->possible_causes as $cause) {
                $causeWords = $this->extractKeywords($cause);
                $searchTerms = array_merge($searchTerms, $causeWords);
            }
        }
        
        $searchTerms = array_filter(array_unique($searchTerms), function($term) {
            return strlen($term) > 2 && !$this->isStopWord($term);
        });
        
        if (empty($searchTerms)) {
            return collect();
        }
        
        // Получаем доступные колонки
        $tableColumns = Schema::getColumnListing('price_items');
        $selectColumns = ['id', 'sku', 'name', 'description', 'price'];
        
        $availableColumns = [
            'quantity', 'catalog_brand', 'brand_id', 'category',
            'image_url', 'unit', 'min_order_qty'
        ];
        
        foreach ($availableColumns as $column) {
            if (in_array($column, $tableColumns)) {
                $selectColumns[] = $column;
            }
        }
        
        $partsQuery = PriceItem::query()
            ->select($selectColumns);
        
        if (in_array('quantity', $tableColumns)) {
            $partsQuery->where('quantity', '>', 0);
        }
        
        // Фильтр по бренду автомобиля
        if ($rule->brand_id && in_array('catalog_brand', $tableColumns)) {
            $brand = Brand::find($rule->brand_id);
            if ($brand) {
                $partsQuery->where(function($q) use ($brand, $tableColumns) {
                    if (in_array('catalog_brand', $tableColumns)) {
                        $q->orWhere('catalog_brand', 'like', "%{$brand->name}%");
                    }
                    if (in_array('brand_id', $tableColumns)) {
                        $q->orWhere('brand_id', $brand->id);
                    }
                });
            }
        }
        
        // Поиск по ключевым словам
        if (!empty($searchTerms)) {
            $partsQuery->where(function($q) use ($searchTerms, $tableColumns) {
                foreach ($searchTerms as $term) {
                    $term = trim($term);
                    if (strlen($term) > 2) {
                        if (in_array('name', $tableColumns)) {
                            $q->orWhere('name', 'like', "%{$term}%");
                        }
                        if (in_array('description', $tableColumns)) {
                            $q->orWhere('description', 'like', "%{$term}%");
                        }
                        if (in_array('sku', $tableColumns)) {
                            $q->orWhere('sku', 'like', "%{$term}%");
                        }
                    }
                }
            });
        }
        
        try {
            return $partsQuery->limit(15)->get();
        } catch (\Exception $e) {
            Log::error('Error finding parts for rule: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Поиск документов для правила
     */
    private function findDocumentsForRule(Rule $rule)
    {
        if (!Schema::hasTable('documents')) {
            return collect();
        }
        
        $searchTerms = [];
        
        if ($rule->symptom && $rule->symptom->name) {
            $titleWords = $this->extractKeywords($rule->symptom->name);
            $searchTerms = array_merge($searchTerms, $titleWords);
        }
        
        $searchTerms = array_filter(array_unique($searchTerms), function($term) {
            return strlen($term) > 2;
        });
        
        if (empty($searchTerms)) {
            return collect();
        }
        
        $tableColumns = Schema::getColumnListing('documents');
        $selectColumns = ['id', 'title'];
        
        $availableColumns = [
            'content_text', 'total_pages', 'file_type', 'file_path', 'source_url',
            'detected_section', 'detected_system', 'detected_component'
        ];
        
        foreach ($availableColumns as $column) {
            if (in_array($column, $tableColumns)) {
                $selectColumns[] = $column;
            }
        }
        
        $docsQuery = Document::query()
            ->select($selectColumns);
        
        if (in_array('status', $tableColumns)) {
            $docsQuery->where('status', 'active');
        }
        
        if (in_array('is_parsed', $tableColumns)) {
            $docsQuery->where('is_parsed', true);
        }
        
        // Фильтр по модели автомобиля
        if ($rule->model_id && in_array('car_model_id', $tableColumns)) {
            $docsQuery->where('car_model_id', $rule->model_id);
        } elseif ($rule->brand_id && in_array('car_model_id', $tableColumns)) {
            $docsQuery->whereHas('carModel', function($q) use ($rule) {
                $q->where('brand_id', $rule->brand_id);
            });
        }
        
        // Поиск по ключевым словам
        if (!empty($searchTerms)) {
            $docsQuery->where(function($q) use ($searchTerms, $tableColumns) {
                foreach ($searchTerms as $term) {
                    $term = trim($term);
                    if (strlen($term) > 2) {
                        if (in_array('title', $tableColumns)) {
                            $q->orWhere('title', 'like', "%{$term}%");
                        }
                        if (in_array('content_text', $tableColumns)) {
                            $q->orWhere('content_text', 'like', "%{$term}%");
                        }
                    }
                }
            });
        }
        
        try {
            return $docsQuery->limit(5)->get();
        } catch (\Exception $e) {
            Log::error('Error finding documents for rule: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Генерация инструкции по ремонту
     */
    private function generateRepairGuide(Rule $rule, $parts, $documents)
    {
        $guide = [];
        
        $guide[] = [
            'title' => 'Диагностика проблемы',
            'steps' => $rule->diagnostic_steps ?? [],
            'icon' => 'bi-search'
        ];
        
        if ($rule->possible_causes && count($rule->possible_causes) > 0) {
            $guide[] = [
                'title' => 'Возможные причины',
                'steps' => $rule->possible_causes,
                'icon' => 'bi-exclamation-triangle'
            ];
        }
        
        if ($parts->count() > 0) {
            $guide[] = [
                'title' => 'Рекомендуемые запчасти',
                'parts' => $parts,
                'icon' => 'bi-tools'
            ];
        }
        
        if ($documents->count() > 0) {
            $guide[] = [
                'title' => 'Инструкции по ремонту',
                'documents' => $documents,
                'icon' => 'bi-file-earmark-text'
            ];
        }
        
        $guide[] = [
            'title' => 'Проверка и завершение',
            'steps' => [
                'Проверить правильность установки всех деталей',
                'Очистить коды ошибок (если есть сканер)',
                'Протестировать работу системы',
                'Убедиться в отсутствии посторонних шумов и запахов',
            ],
            'icon' => 'bi-check-circle'
        ];
        
        return $guide;
    }
}