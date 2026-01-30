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
            'brand_id' => 'nullable|integer',
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
        
        // Очищаем и нормализуем UTF-8 строку
        $query = $this->cleanUtf8String(trim($request->input('query')));
        $brandId = $request->input('brand_id');
        $modelId = $request->input('model_id');
        $searchType = $request->input('search_type', 'advanced');

        Log::info('Enhanced AI Search', [
            'query' => $query,
            'brand_id' => $brandId,
            'model_id' => $modelId,
            'search_type' => $searchType,
        ]);

        try {
            // 1. Приоритетный поиск симптомов
            $exactSymptoms = $this->searchExactSymptoms($query, $brandId, $modelId);
            
            // 2. Поиск по ключевым словам если точных нет
            if (empty($exactSymptoms)) {
                $keywordSymptoms = $this->searchByKeywords($query, $brandId, $modelId);
            } else {
                $keywordSymptoms = [];
            }
            
            // 3. Объединяем результаты
            $allSymptoms = array_merge($exactSymptoms, $keywordSymptoms);
            
            if (empty($allSymptoms)) {
                // Если ничего не найдено, ищем похожие
                $allSymptoms = $this->searchSimilarSymptoms($query, $brandId, $modelId);
            }
            
            // 4. Группируем симптомы с правилами
            $groupedResults = $this->groupSymptomsWithRules($allSymptoms);
            
            // 5. Ищем документы только если есть симптомы
            $documents = [];
            $parts = [];
            
            if (!empty($groupedResults)) {
                $topSymptoms = array_slice($groupedResults, 0, 3);
                $documents = $this->searchDocumentsForSymptoms($topSymptoms, $brandId, $modelId);
                $parts = $this->searchPartsForSymptoms($topSymptoms, $brandId);
            }
            
            // 6. Генерируем AI ответ
            $aiResponse = $this->generateStructuredAIResponse($query, $groupedResults, $parts, $documents, $brandId, $modelId);
            
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            // Очищаем данные перед отправкой JSON
            $cleanedResults = $this->cleanDataForJson($groupedResults);
            $cleanedParts = $this->cleanDataForJson($parts);
            $cleanedDocuments = $this->cleanDataForJson($documents);
            $cleanedAiResponse = $this->cleanUtf8String($aiResponse);

            return response()->json([
                'success' => true,
                'query' => $query,
                'results' => $cleanedResults,
                'parts' => $cleanedParts,
                'documents' => $cleanedDocuments,
                'ai_response' => $cleanedAiResponse,
                'search_type' => $searchType,
                'execution_time' => $executionTime,
                'stats' => [
                    'symptoms_found' => count($cleanedResults),
                    'parts_found' => count($cleanedParts),
                    'documents_found' => count($cleanedDocuments),
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
     * Очистка строки UTF-8
     */
    private function cleanUtf8String($string)
    {
        if (!mb_check_encoding($string, 'UTF-8')) {
            $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        }
        
        // Удаляем невалидные UTF-8 символы
        $string = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);
        
        // Удаляем BOM
        $string = preg_replace('/^\x{EF}\x{BB}\x{BF}/', '', $string);
        
        // Нормализуем пробелы
        $string = trim(preg_replace('/\s+/', ' ', $string));
        
        return $string;
    }

    /**
     * Очистка данных для JSON
     */
    private function cleanDataForJson($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $data[$key] = $this->cleanDataForJson($value);
                } elseif (is_string($value)) {
                    $data[$key] = $this->cleanUtf8String($value);
                }
            }
        } elseif (is_string($data)) {
            $data = $this->cleanUtf8String($data);
        }
        
        return $data;
    }

    /**
     * Поиск точных совпадений симптомов
     */
    private function searchExactSymptoms($query, $brandId = null, $modelId = null)
    {
        // Нормализуем запрос для поиска
        $searchQuery = $this->normalizeSearchQuery($query);
        
        $symptomsQuery = Symptom::where('is_active', true)
            ->with(['rules' => function($q) use ($brandId, $modelId) {
                $q->where('is_active', true)
                  ->with(['brand', 'model'])
                  ->orderBy('brand_id')
                  ->orderBy('model_id');
                
                if ($brandId) {
                    $q->where('brand_id', $brandId);
                }
                
                if ($modelId) {
                    $q->where('model_id', $modelId);
                }
            }]);
        
        // Разбиваем запрос на слова для поиска
        $words = $this->extractSearchWords($searchQuery);
        
        if (empty($words)) {
            return [];
        }
        
        $symptomsQuery->where(function($q) use ($words) {
            foreach ($words as $word) {
                if (mb_strlen($word) > 2) {
                    $q->orWhere('name', 'like', "%{$word}%");
                }
            }
        });
        
        $symptoms = $symptomsQuery->get();
        
        // Рассчитываем точность совпадения
        $scoredSymptoms = [];
        foreach ($symptoms as $symptom) {
            $score = $this->calculateExactMatchScore($symptom->name, $searchQuery);
            if ($score > 0.3) {
                $scoredSymptoms[] = [
                    'symptom' => $symptom,
                    'score' => $score,
                    'match_type' => 'exact'
                ];
            }
        }
        
        // Сортируем по убыванию точности
        usort($scoredSymptoms, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return array_slice($scoredSymptoms, 0, 5);
    }

    /**
     * Поиск по ключевым словам
     */
    private function searchByKeywords($query, $brandId = null, $modelId = null)
    {
        $keywords = $this->extractRelevantKeywords($query);
        
        if (empty($keywords)) {
            return [];
        }
        
        $symptomsQuery = Symptom::where('is_active', true)
            ->with(['rules' => function($q) use ($brandId, $modelId) {
                $q->where('is_active', true)
                  ->with(['brand', 'model'])
                  ->orderBy('brand_id')
                  ->orderBy('model_id');
                
                if ($brandId) {
                    $q->where('brand_id', $brandId);
                }
                
                if ($modelId) {
                    $q->where('model_id', $modelId);
                }
            }]);
        
        $symptomsQuery->where(function($q) use ($keywords) {
            foreach ($keywords as $keyword) {
                if (mb_strlen($keyword) > 2) {
                    $q->orWhere('name', 'like', "%{$keyword}%")
                      ->orWhere('description', 'like', "%{$keyword}%");
                }
            }
        });
        
        $symptoms = $symptomsQuery->get();
        
        $scoredSymptoms = [];
        foreach ($symptoms as $symptom) {
            $score = $this->calculateKeywordScore($symptom, $keywords);
            if ($score > 0.2) {
                $scoredSymptoms[] = [
                    'symptom' => $symptom,
                    'score' => $score,
                    'match_type' => 'keyword'
                ];
            }
        }
        
        usort($scoredSymptoms, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return array_slice($scoredSymptoms, 0, 5);
    }

    /**
     * Поиск похожих симптомов
     */
    private function searchSimilarSymptoms($query, $brandId = null, $modelId = null)
    {
        $keywords = $this->extractRelevantKeywords($query);
        
        if (empty($keywords)) {
            return [];
        }
        
        $symptomsQuery = Symptom::where('is_active', true)
            ->with(['rules' => function($q) use ($brandId, $modelId) {
                $q->where('is_active', true)
                  ->with(['brand', 'model'])
                  ->orderBy('brand_id')
                  ->orderBy('model_id');
                
                if ($brandId) {
                    $q->where('brand_id', $brandId);
                }
                
                if ($modelId) {
                    $q->where('model_id', $modelId);
                }
            }])
            ->where('frequency', '>', 0)
            ->orderBy('frequency', 'desc');
        
        $symptoms = $symptomsQuery->limit(10)->get();
        
        return $symptoms->map(function($symptom) use ($keywords) {
            $score = $this->calculateKeywordScore($symptom, $keywords) * 0.5; // Понижаем вес
            return [
                'symptom' => $symptom,
                'score' => max(0.1, $score),
                'match_type' => 'similar'
            ];
        })->toArray();
    }

    /**
     * Группировка симптомов с правилами
     */
    private function groupSymptomsWithRules($symptoms)
    {
        $groupedResults = [];
        
        foreach ($symptoms as $item) {
            $symptom = $item['symptom'];
            $score = $item['score'];
            $matchType = $item['match_type'];
            
            if ($symptom->rules->isEmpty()) {
                // Симптом без правил
                $groupedResults[] = [
                    'type' => 'symptom',
                    'id' => $symptom->id,
                    'title' => $this->cleanUtf8String($symptom->name),
                    'description' => $this->cleanUtf8String($symptom->description ?? ''),
                    'relevance_score' => $score,
                    'match_type' => $matchType,
                    'has_rules' => false,
                    'related_systems' => $symptom->related_systems,
                    'frequency' => $symptom->frequency ?? 0,
                ];
            } else {
                // Группируем правила по симптомам
                foreach ($symptom->rules as $rule) {
                    $groupedResults[] = [
                        'type' => 'rule',
                        'id' => $rule->id,
                        'symptom_id' => $symptom->id,
                        'title' => $this->cleanUtf8String($symptom->name),
                        'description' => $this->cleanUtf8String($symptom->description ?? ''),
                        'brand' => $this->cleanUtf8String($rule->brand->name ?? ''),
                        'brand_id' => $rule->brand_id,
                        'model' => $this->cleanUtf8String($rule->model->name ?? ''),
                        'model_id' => $rule->model_id,
                        'diagnostic_steps' => $this->cleanArrayForJson($rule->diagnostic_steps ?? []),
                        'possible_causes' => $this->cleanArrayForJson($rule->possible_causes ?? []),
                        'required_data' => $this->cleanArrayForJson($rule->required_data ?? []),
                        'complexity_level' => $rule->complexity_level ?? 1,
                        'estimated_time' => $rule->estimated_time ?? 60,
                        'consultation_price' => $rule->base_consultation_price ?? 3000,
                        'relevance_score' => $score,
                        'match_type' => $matchType,
                        'has_rules' => true,
                        'related_systems' => $symptom->related_systems,
                        'frequency' => $symptom->frequency ?? 0,
                    ];
                }
            }
        }
        
        // Сортируем по релевантности
        usort($groupedResults, function($a, $b) {
            if ($a['relevance_score'] != $b['relevance_score']) {
                return $b['relevance_score'] <=> $a['relevance_score'];
            }
            
            if ($a['has_rules'] != $b['has_rules']) {
                return $b['has_rules'] <=> $a['has_rules'];
            }
            
            return $b['frequency'] <=> $a['frequency'];
        });
        
        return array_slice($groupedResults, 0, 10);
    }

    /**
     * Поиск документов для симптомов
     */
    private function searchDocumentsForSymptoms($symptoms, $brandId = null, $modelId = null)
    {
        if (empty($symptoms) || !Schema::hasTable('documents')) {
            return [];
        }
        
        $searchTerms = [];
        foreach ($symptoms as $symptom) {
            if (!empty($symptom['title'])) {
                $keywords = $this->extractRelevantKeywords($symptom['title']);
                $searchTerms = array_merge($searchTerms, $keywords);
            }
        }
        
        $searchTerms = array_unique(array_filter($searchTerms, function($term) {
            return mb_strlen($term) > 2;
        }));
        
        if (empty($searchTerms)) {
            return [];
        }
        
        try {
            $docsQuery = Document::query()
                ->where('status', 'active');
            
            if ($modelId) {
                $docsQuery->where('car_model_id', $modelId);
            } elseif ($brandId) {
                $docsQuery->whereHas('carModel', function($q) use ($brandId) {
                    $q->where('brand_id', $brandId);
                });
            }
            
            $docsQuery->where(function($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $q->orWhere('title', 'like', "%{$term}%")
                      ->orWhere('content_text', 'like', "%{$term}%");
                }
            });
            
            $documents = $docsQuery->select([
                    'id', 'title', 'content_text', 'total_pages', 
                    'file_type', 'file_path', 'source_url'
                ])
                ->orderBy('view_count', 'desc')
                ->limit(3)
                ->get();
            
            return $documents->map(function($doc) {
                return [
                    'id' => $doc->id,
                    'title' => $this->cleanUtf8String($doc->title ?? ''),
                    'excerpt' => $this->truncateText($doc->content_text ?? '', 150),
                    'file_type' => $doc->file_type ?? 'pdf',
                    'total_pages' => $doc->total_pages ?? 0,
                    'icon' => $this->getFileIcon($doc->file_type ?? 'pdf'),
                ];
            })->toArray();
                
        } catch (\Exception $e) {
            Log::error('Error searching documents: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Поиск запчастей для возможных причин
     */
    private function searchPartsForSymptoms($symptoms, $brandId = null)
    {
        if (empty($symptoms) || !Schema::hasTable('price_items')) {
            return [];
        }
        
        $causes = [];
        foreach ($symptoms as $symptom) {
            if (!empty($symptom['possible_causes']) && is_array($symptom['possible_causes'])) {
                $causes = array_merge($causes, $symptom['possible_causes']);
            }
        }
        
        if (empty($causes)) {
            return [];
        }
        
        $searchTerms = [];
        foreach ($causes as $cause) {
            $keywords = $this->extractRelevantKeywords($cause);
            $searchTerms = array_merge($searchTerms, $keywords);
        }
        
        $searchTerms = array_unique(array_filter($searchTerms, function($term) {
            return mb_strlen($term) > 2 && !$this->isGenericTerm($term);
        }));
        
        if (empty($searchTerms)) {
            return [];
        }
        
        try {
            $partsQuery = PriceItem::query()
                ->where('quantity', '>', 0)
                ->where('price', '>', 0);
            
            if ($brandId) {
                $brand = Brand::find($brandId);
                if ($brand) {
                    $partsQuery->where(function($q) use ($brand) {
                        $q->orWhere('catalog_brand', 'like', "%{$brand->name}%")
                          ->orWhere('brand_id', $brandId);
                    });
                }
            }
            
            $limitedTerms = array_slice($searchTerms, 0, 3);
            
            $partsQuery->where(function($q) use ($limitedTerms) {
                foreach ($limitedTerms as $term) {
                    $q->orWhere('name', 'like', "%{$term}%")
                      ->orWhere('description', 'like', "%{$term}%");
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
                    'name' => $this->cleanUtf8String($item->name ?? ''),
                    'description' => $this->cleanUtf8String($item->description ?? ''),
                    'price' => $item->price ?? 0,
                    'formatted_price' => number_format($item->price ?? 0, 2, '.', ' '),
                    'quantity' => $item->quantity ?? 0,
                    'brand' => $this->cleanUtf8String($item->catalog_brand ?? ''),
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
     * Генерация структурированного AI ответа
     */
    private function generateStructuredAIResponse($query, $symptoms, $parts, $docs, $brandId = null, $modelId = null)
    {
        $response = "🤖 **AI-анализ диагностической проблемы**\n\n";
        $response .= "🔍 **Запрос:** {$query}\n";
        
        if ($brandId) {
            $brand = Brand::find($brandId);
            if ($brand) {
                $response .= "🏷️ **Марка:** {$brand->name}\n";
            }
        }
        if ($modelId) {
            $model = CarModel::find($modelId);
            if ($model) {
                $response .= "🚗 **Модель:** {$model->name}\n";
            }
        }
        
        $response .= "\n📊 **Результаты поиска:**\n";
        
        if (empty($symptoms)) {
            $response .= "⚠️ **Совпадений не найдено.**\n";
            $response .= "\n💡 **Рекомендации:**\n";
            $response .= "• Проверьте правильность написания\n";
            $response .= "• Используйте более простые формулировки\n";
            $response .= "• Уточните детали проблемы\n";
            $response .= "• Попробуйте поискать по отдельным словам\n";
            
            return $response;
        }
        
        $exactMatches = array_filter($symptoms, function($item) {
            return $item['match_type'] === 'exact' && $item['relevance_score'] > 0.7;
        });
        
        if (!empty($exactMatches)) {
            $response .= "✅ **Точные совпадения:** " . count($exactMatches) . "\n";
        }
        
        $response .= "🔍 **Найдено симптомов:** " . count($symptoms) . "\n";
        
        if (!empty($parts)) {
            $response .= "🛒 **Запчасти:** " . count($parts) . " наименований\n";
        }
        
        if (!empty($docs)) {
            $response .= "📄 **Документы:** " . count($docs) . " инструкций\n";
        }
        
        // Показываем топ-3 наиболее релевантных результата
        $response .= "\n🎯 **Наиболее релевантные результаты:**\n\n";
        
        foreach (array_slice($symptoms, 0, 3) as $index => $item) {
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
            
            if ($item['type'] === 'rule') {
                if (!empty($item['possible_causes']) && count($item['possible_causes']) > 0) {
                    $causes = implode(', ', array_slice($item['possible_causes'], 0, 2));
                    $response .= "   ⚠️ **Возможные причины:** {$causes}\n";
                }
                
                if (!empty($item['estimated_time'])) {
                    $response .= "   ⏱️ **Время диагностики:** {$item['estimated_time']} мин.\n";
                }
            }
            
            $response .= "\n";
        }
        
        $response .= "💡 **Следующие шаги:**\n";
        $response .= "1. Изучите диагностические шаги\n";
        $response .= "2. Проверьте возможные причины\n";
        $response .= "3. Ознакомьтесь с инструкциями\n";
        
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
    
    private function extractSearchWords($query)
    {
        $words = explode(' ', $query);
        $words = array_filter($words, function($word) {
            return mb_strlen($word) > 2;
        });
        
        return array_values($words);
    }
    
    private function extractRelevantKeywords($text)
    {
        $stopWords = [
            'и', 'или', 'но', 'на', 'в', 'с', 'по', 'у', 'о', 'об', 'от', 'до', 'за',
            'из', 'к', 'со', 'то', 'же', 'бы', 'ли', 'не', 'нет', 'да', 'как', 'что',
            'это', 'так', 'вот', 'ну', 'нужно', 'очень', 'можно', 'надо'
        ];
        
        $text = mb_strtolower($this->cleanUtf8String($text), 'UTF-8');
        $words = preg_split('/[\s,\.\-\(\)\[\]:;!?]+/', $text);
        
        $keywords = array_filter($words, function($word) use ($stopWords) {
            $word = trim($word);
            return mb_strlen($word) > 2 && !in_array($word, $stopWords);
        });
        
        return array_unique($keywords);
    }
    
    private function calculateExactMatchScore($symptomName, $query)
    {
        $symptomLower = mb_strtolower($this->cleanUtf8String($symptomName), 'UTF-8');
        $queryLower = mb_strtolower($this->cleanUtf8String($query), 'UTF-8');
        
        // Полное совпадение
        if (strpos($symptomLower, $queryLower) !== false) {
            return 1.0;
        }
        
        // Совпадение всех слов
        $symptomWords = $this->extractSearchWords($symptomLower);
        $queryWords = $this->extractSearchWords($queryLower);
        
        if (empty($queryWords)) {
            return 0;
        }
        
        $matchedWords = 0;
        foreach ($queryWords as $queryWord) {
            foreach ($symptomWords as $symptomWord) {
                if (strpos($symptomWord, $queryWord) !== false) {
                    $matchedWords++;
                    break;
                }
            }
        }
        
        return $matchedWords / count($queryWords);
    }
    
    private function calculateKeywordScore($symptom, $keywords)
    {
        $score = 0;
        $name = mb_strtolower($this->cleanUtf8String($symptom->name), 'UTF-8');
        $description = mb_strtolower($this->cleanUtf8String($symptom->description ?? ''), 'UTF-8');
        
        foreach ($keywords as $keyword) {
            $keyword = mb_strtolower($this->cleanUtf8String($keyword), 'UTF-8');
            
            if (strpos($name, $keyword) !== false) {
                $score += 0.5;
            }
            
            if (strpos($description, $keyword) !== false) {
                $score += 0.2;
            }
        }
        
        // Бонус за частоту симптома
        if ($symptom->frequency > 0) {
            $score += min(0.3, $symptom->frequency / 100);
        }
        
        return min(1.0, $score);
    }
    
    private function isGenericTerm($term)
    {
        $genericTerms = [
            'неисправность', 'повреждение', 'проблема', 'симптом',
            'диагностика', 'ремонт', 'замена', 'проверка'
        ];
        
        return in_array(mb_strtolower($this->cleanUtf8String($term), 'UTF-8'), $genericTerms);
    }
    
    private function truncateText($text, $length = 150)
    {
        $text = $this->cleanUtf8String($text);
        
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        $truncated = mb_substr($text, 0, $length, 'UTF-8');
        $lastSpace = mb_strrpos($truncated, ' ', 0, 'UTF-8');
        
        if ($lastSpace !== false) {
            $truncated = mb_substr($truncated, 0, $lastSpace, 'UTF-8');
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
        
        $fileType = strtolower($fileType);
        return $icons[$fileType] ?? 'bi-file-earmark';
    }
    
    private function cleanArrayForJson($array)
    {
        if (!is_array($array)) {
            return [];
        }
        
        $cleaned = [];
        foreach ($array as $item) {
            if (is_string($item)) {
                $cleaned[] = $this->cleanUtf8String($item);
            } elseif (is_array($item)) {
                $cleaned[] = $this->cleanArrayForJson($item);
            } else {
                $cleaned[] = $item;
            }
        }
        
        return $cleaned;
    }
}