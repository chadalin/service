<?php

namespace App\Http\Controllers\Diagnostic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Diagnostic\Symptom;
use App\Models\Diagnostic\Rule;
use App\Models\Brand;
use App\Models\CarModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AISearchController extends Controller
{
    /**
     * Показать страницу AI поиска
     */
    public function index()
    {
        $brands = Brand::where('is_popular', true)
            ->orderBy('name')
            ->get();
        
        // Группируем модели по брендам для быстрой загрузки
        $models = CarModel::whereIn('brand_id', $brands->pluck('id'))
            ->select('id', 'brand_id', 'name', 'name_cyrillic', 'year_from', 'year_to')
            ->get()
            ->groupBy('brand_id');
        
        // Статистика
        $stats = [
            'symptoms_count' => Symptom::where('is_active', true)->count(),
            'rules_count' => Rule::where('is_active', true)->count(),
            'brands_count' => Brand::count(),
            'models_count' => CarModel::count(),
        ];
        
        return view('diagnostic.ai-search.index', compact('brands', 'models', 'stats'));
    }

    /**
     * Выполнить AI поиск
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|max:1000',
            'brand_id' => 'nullable',
            'model_id' => 'nullable',
            'search_type' => 'nullable|in:basic,advanced',
            'complexity' => 'nullable|string',
            'max_results' => 'nullable|integer|min:1|max:50',
            'only_with_rules' => 'nullable|boolean',
            'group_by_brand' => 'nullable|boolean',
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
        $complexity = $request->input('complexity');
        $maxResults = $request->input('max_results', 10);
        $onlyWithRules = $request->boolean('only_with_rules', false);
        $groupByBrand = $request->boolean('group_by_brand', false);

        Log::info('AI Symptom Search', [
            'query' => $query,
            'brand_id' => $brandId,
            'model_id' => $modelId,
            'search_type' => $searchType,
            'user_id' => auth()->id(),
        ]);

        try {
            // 1. Поиск симптомов
            $symptoms = $this->searchSymptoms($query, $searchType);
            
            if ($symptoms->isEmpty()) {
                $symptoms = $this->findSimilarSymptoms($query);
            }

            // 2. Фильтрация по бренду и модели
            $filteredSymptoms = $this->filterSymptomsByBrandModel($symptoms, $brandId, $modelId);
            
            // 3. Фильтрация по правилам
            if ($onlyWithRules) {
                $filteredSymptoms = $filteredSymptoms->filter(function($symptom) {
                    return $symptom->rules->isNotEmpty();
                });
            }

            // 4. Формируем результаты с улучшенной релевантностью
            $results = $this->prepareEnhancedResults($filteredSymptoms, $query, $brandId, $modelId, $complexity);
            
            // 5. Ограничиваем количество результатов
            $results = array_slice($results, 0, $maxResults);
            
            // 6. Группировка по брендам если нужно
            if ($groupByBrand && !empty($results)) {
                $results = $this->groupResultsByBrand($results);
            }

            // 7. Генерируем AI ответ
            $aiResponse = $this->generateEnhancedAIResponse($query, $filteredSymptoms, $results, $brandId, $modelId);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            $totalRules = $filteredSymptoms->sum(function($symptom) {
                return $symptom->rules->count();
            });

            return response()->json([
                'success' => true,
                'query' => $query,
                'count' => count($results),
                'results' => $results,
                'ai_response' => $aiResponse,
                'search_type' => $searchType,
                'execution_time' => $executionTime,
                'stats' => [
                    'symptoms_found' => $filteredSymptoms->count(),
                    'rules_found' => $totalRules,
                    'total_results' => count($results),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('AI Search Error: ' . $e->getMessage(), [
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
     * Поиск симптомов
     */
    private function searchSymptoms($query, $searchType = 'basic')
    {
        $keywords = $this->extractEnhancedKeywords($query);
        
        if (empty($keywords)) {
            return collect();
        }

        $symptomsQuery = Symptom::where('is_active', true)
            ->with(['rules' => function($q) {
                $q->where('is_active', true)
                  ->with(['brand', 'model']);
            }]);

        if ($searchType === 'advanced') {
            // Расширенный поиск по всем полям
            $symptomsQuery->where(function($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->orWhere('name', 'like', "%{$keyword}%")
                      ->orWhere('description', 'like', "%{$keyword}%")
                      ->orWhere('slug', 'like', "%{$keyword}%")
                      ->orWhere('related_systems', 'like', "%{$keyword}%");
                }
            });
        } else {
            // Базовый поиск с учетом веса полей
            $symptomsQuery->where(function($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    if (strlen($keyword) > 2) {
                        // Больше вес для названия
                        $q->orWhere('name', 'like', "%{$keyword}%")
                          ->orWhere('description', 'like', "%{$keyword}%");
                    }
                }
            });
        }

        return $symptomsQuery->get();
    }

    /**
     * Улучшенное извлечение ключевых слов
     */
    private function extractEnhancedKeywords($query)
    {
        // Русские и английские стоп-слова
        $stopWords = [
            'и', 'или', 'но', 'на', 'в', 'с', 'по', 'у', 'о', 'об', 'от', 'до', 'за', 'из', 'к', 'со', 'то', 
            'же', 'бы', 'ли', 'не', 'нет', 'да', 'как', 'что', 'это', 'так', 'вот', 'ну', 'нужно', 'очень', 
            'можно', 'надо', 'мне', 'меня', 'мой', 'моя', 'мое', 'мои', 'автомобиль', 'машина', 'двигатель',
            'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'with', 'by', 'a', 'an', 'the', 'is',
            'are', 'was', 'were', 'be', 'been', 'being'
        ];
        
        // Нормализуем запрос
        $query = mb_strtolower(trim($query));
        
        // Разбиваем на слова
        $words = preg_split('/[\s,\.\-\(\)\[\]:;!?]+/', $query);
        
        // Фильтруем и уникализируем
        $keywords = array_filter($words, function($word) use ($stopWords) {
            $word = trim($word);
            return strlen($word) > 2 && !in_array($word, $stopWords);
        });
        
        // Удаляем дубликаты
        $keywords = array_unique($keywords);
        
        // Если нет ключевых слов, возвращаем весь запрос как один ключевой фраз
        if (empty($keywords) && strlen($query) > 3) {
            return [$query];
        }
        
        return $keywords;
    }

    /**
     * Поиск похожих симптомов
     */
    private function findSimilarSymptoms($query)
    {
        $keywords = $this->extractEnhancedKeywords($query);
        
        if (empty($keywords)) {
            return collect();
        }
        
        return Symptom::where('is_active', true)
            ->with(['rules' => function($q) {
                $q->where('is_active', true)
                  ->with(['brand', 'model']);
            }])
            ->where(function($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    if (strlen($keyword) > 2) {
                        $q->orWhere('name', 'like', "%{$keyword}%")
                          ->orWhere('description', 'like', "%{$keyword}%");
                    }
                }
            })
            ->get();
    }

    /**
     * Фильтрация симптомов по бренду и модели
     */
    private function filterSymptomsByBrandModel($symptoms, $brandId = null, $modelId = null)
    {
        if (!$brandId && !$modelId) {
            return $symptoms;
        }

        return $symptoms->filter(function($symptom) use ($brandId, $modelId) {
            if ($symptom->rules->isEmpty()) {
                return true; // Симптомы без правил показываем всегда
            }

            return $symptom->rules->contains(function($rule) use ($brandId, $modelId) {
                $matches = true;
                
                if ($brandId) {
                    $matches = $matches && ($rule->brand_id == $brandId);
                }
                
                if ($modelId) {
                    $matches = $matches && ($rule->model_id == $modelId);
                }
                
                return $matches;
            });
        });
    }

    /**
     * Улучшенная подготовка результатов
     */
    private function prepareEnhancedResults($symptoms, $query, $brandId = null, $modelId = null, $complexity = null)
    {
        $results = [];
        $queryKeywords = $this->extractEnhancedKeywords($query);
        
        foreach ($symptoms as $symptom) {
            if ($symptom->rules->isEmpty()) {
                // Симптом без правил
                $relevanceScore = $this->calculateSymptomRelevance($symptom, $queryKeywords);
                
                if ($relevanceScore > 0.3) { // Порог релевантности
                    $results[] = [
                        'type' => 'symptom',
                        'id' => $symptom->id,
                        'symptom_id' => $symptom->id,
                        'title' => $symptom->name,
                        'description' => $symptom->description,
                        'relevance_score' => $relevanceScore,
                        'has_rules' => false,
                        'rules_count' => 0,
                        'related_systems' => $symptom->related_systems,
                        'match_type' => 'symptom_only',
                        'matched_keywords' => $this->getMatchedKeywords($symptom, $queryKeywords),
                    ];
                }
            } else {
                // Симптом с правилами
                foreach ($symptom->rules as $rule) {
                    // Фильтр по сложности
                    if ($complexity && !$this->matchesComplexity($rule->complexity_level, $complexity)) {
                        continue;
                    }
                    
                    // Фильтр по бренду и модели
                    $brandModelMatch = true;
                    if ($brandId && $rule->brand_id != $brandId) {
                        $brandModelMatch = false;
                    }
                    if ($modelId && $rule->model_id != $modelId) {
                        $brandModelMatch = false;
                    }
                    
                    if (!$brandModelMatch) {
                        continue;
                    }
                    
                    $relevanceScore = $this->calculateEnhancedRelevanceScore($symptom, $rule, $queryKeywords);
                    
                    $results[] = [
                        'type' => 'rule',
                        'id' => $rule->id,
                        'symptom_id' => $symptom->id,
                        'title' => $symptom->name,
                        'description' => $this->truncateDescription($symptom->description, 150),
                        'full_description' => $symptom->description,
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
                        'has_rules' => true,
                        'rules_count' => $symptom->rules->count(),
                        'related_systems' => $symptom->related_systems,
                        'match_type' => 'full_match',
                        'matched_keywords' => $this->getMatchedKeywords($symptom, $queryKeywords),
                        'conditions' => $rule->conditions ?? [],
                    ];
                }
            }
        }
        
        // Сортируем по релевантности
        usort($results, function($a, $b) {
            return $b['relevance_score'] <=> $a['relevance_score'];
        });
        
        return $results;
    }

    /**
     * Расчет релевантности для симптома
     */
    private function calculateSymptomRelevance($symptom, $queryKeywords)
    {
        $score = 0.0;
        $name = mb_strtolower($symptom->name);
        $description = mb_strtolower($symptom->description);
        
        foreach ($queryKeywords as $keyword) {
            $keyword = mb_strtolower($keyword);
            
            // Проверка в названии (больший вес)
            if (strpos($name, $keyword) !== false) {
                $score += 0.4;
            }
            
            // Проверка в описании
            if (strpos($description, $keyword) !== false) {
                $score += 0.2;
            }
        }
        
        // Нормализуем до 1.0
        return min(1.0, $score);
    }

    /**
     * Улучшенный расчет релевантности
     */
    private function calculateEnhancedRelevanceScore($symptom, $rule, $queryKeywords)
    {
        $score = $this->calculateSymptomRelevance($symptom, $queryKeywords);
        
        // Бонусы за качество правила
        if (!empty($rule->diagnostic_steps) && is_array($rule->diagnostic_steps) && count($rule->diagnostic_steps) > 0) {
            $score += 0.15;
        }
        
        if (!empty($rule->possible_causes) && is_array($rule->possible_causes) && count($rule->possible_causes) > 0) {
            $score += 0.1;
        }
        
        if (!empty($rule->required_data) && is_array($rule->required_data) && count($rule->required_data) > 0) {
            $score += 0.05;
        }
        
        // Бонус за конкретную модель
        if ($rule->model_id) {
            $score += 0.05;
        }
        
        // Бонус за полный набор данных
        if ($rule->estimated_time && $rule->base_consultation_price) {
            $score += 0.05;
        }
        
        return min(1.0, $score);
    }

    /**
     * Получение совпавших ключевых слов
     */
    private function getMatchedKeywords($symptom, $queryKeywords)
    {
        $matched = [];
        $name = mb_strtolower($symptom->name);
        $description = mb_strtolower($symptom->description);
        
        foreach ($queryKeywords as $keyword) {
            $keyword = mb_strtolower($keyword);
            if (strpos($name, $keyword) !== false || strpos($description, $keyword) !== false) {
                $matched[] = $keyword;
            }
        }
        
        return array_unique($matched);
    }

    /**
     * Проверка соответствия сложности
     */
    private function matchesComplexity($ruleComplexity, $complexityFilter)
    {
        if (!$complexityFilter || !$ruleComplexity) {
            return true;
        }
        
        list($min, $max) = explode('-', $complexityFilter);
        return $ruleComplexity >= $min && $ruleComplexity <= $max;
    }

    /**
     * Группировка результатов по брендам
     */
    private function groupResultsByBrand($results)
    {
        $grouped = [];
        
        foreach ($results as $result) {
            $brand = $result['brand'] ?? 'Без привязки к марке';
            
            if (!isset($grouped[$brand])) {
                $grouped[$brand] = [
                    'brand' => $brand,
                    'results' => [],
                    'count' => 0,
                ];
            }
            
            $grouped[$brand]['results'][] = $result;
            $grouped[$brand]['count']++;
        }
        
        // Сортируем бренды по количеству результатов
        uasort($grouped, function($a, $b) {
            return $b['count'] <=> $a['count'];
        });
        
        return $grouped;
    }

    /**
     * Улучшенный AI ответ
     */
    private function generateEnhancedAIResponse($query, $symptoms, $results, $brandId = null, $modelId = null)
    {
        if (empty($results)) {
            return $this->generateNoResultsResponse($query, $brandId, $modelId);
        }

        $totalResults = is_array($results) ? count($results) : 0;
        $hasRules = collect($results)->contains('has_rules', true);
        
        $response = "🤖 **AI-анализ вашей проблемы**\n\n";
        $response .= "🔍 **Запрос:** {$query}\n";
        
        if ($brandId) {
            $brand = Brand::find($brandId);
            $response .= "🏷️ **Марка:** {$brand->name}\n";
        }
        
        if ($modelId) {
            $model = CarModel::find($modelId);
            $response .= "🚗 **Модель:** {$model->name}\n";
        }
        
        $response .= "\n📊 **Результаты поиска:**\n";
        $response .= "• Найдено решений: **{$totalResults}**\n";
        
        $symptomsWithRules = collect($results)->where('has_rules', true)->count();
        $symptomsWithoutRules = $totalResults - $symptomsWithRules;
        
        if ($symptomsWithRules > 0) {
            $response .= "• С правилами диагностики: **{$symptomsWithRules}**\n";
        }
        
        if ($symptomsWithoutRules > 0) {
            $response .= "• Только симптомы: **{$symptomsWithoutRules}**\n";
        }
        
        $response .= "\n🎯 **Топ рекомендации:**\n\n";
        
        // Показываем топ-3 результата с деталями
        $topResults = array_slice($results, 0, min(3, $totalResults));
        $resultNumber = 1;
        
        foreach ($topResults as $index => $result) {
            $response .= "**{$resultNumber}. {$result['title']}**\n";
            
            if (!empty($result['description'])) {
                $response .= "   📝 " . $this->truncateDescription($result['description'], 120) . "\n";
            }
            
            if ($result['type'] === 'rule') {
                $brandModel = [];
                if ($result['brand']) $brandModel[] = $result['brand'];
                if ($result['model']) $brandModel[] = $result['model'];
                
                if (!empty($brandModel)) {
                    $response .= "   🏷️ Для: " . implode(' ', $brandModel) . "\n";
                }
                
                if (!empty($result['possible_causes']) && is_array($result['possible_causes'])) {
                    $causes = array_slice($result['possible_causes'], 0, 2);
                    $response .= "   ⚠️ Причины: " . implode(', ', $causes);
                    if (count($result['possible_causes']) > 2) {
                        $response .= " и ещё " . (count($result['possible_causes']) - 2);
                    }
                    $response .= "\n";
                }
                
                $response .= "   🔧 Сложность: {$result['complexity_level']}/10\n";
                $response .= "   ⏱️ Время: ~{$result['estimated_time']} мин.\n";
                $response .= "   💰 Консультация: " . number_format($result['consultation_price'], 0, '.', ' ') . " ₽\n";
            } else {
                $response .= "   ℹ️ Требуется дополнительная диагностика\n";
            }
            
            $response .= "\n";
            $resultNumber++;
        }
        
        if ($totalResults > 3) {
            $remaining = $totalResults - 3;
            $response .= "📌 И ещё **{$remaining}** решений в списке ниже\n\n";
        }
        
        $response .= "💡 **Советы по использованию результатов:**\n";
        $response .= "1. **Кликайте на шаги диагностики** чтобы увидеть все шаги\n";
        $response .= "2. **Разворачивайте причины** для полного списка\n";
        $response .= "3. **Используйте фильтры** для уточнения поиска\n";
        $response .= "4. **Закажите консультацию** если нужна помощь специалиста\n";
        
        return $response;
    }

    /**
     * Ответ при отсутствии результатов
     */
    private function generateNoResultsResponse($query, $brandId = null, $modelId = null)
    {
        $response = "🔍 **По вашему запросу не найдено результатов**\n\n";
        $response .= "**Запрос:** {$query}\n";
        
        if ($brandId) {
            $brand = Brand::find($brandId);
            $response .= "**Марка:** {$brand->name}\n";
        }
        
        $response .= "\n**🎯 Возможные причины:**\n";
        $response .= "• Проблема слишком специфична\n";
        $response .= "• В базе пока нет такой комбинации симптомов\n";
        $response .= "• Возможно, есть опечатка в описании\n";
        
        $response .= "\n**💡 Рекомендации:**\n";
        $response .= "1. **Упростите запрос** - используйте более общие термины\n";
        $response .= "2. **Уберите фильтры** марки/модели\n";
        $response .= "3. **Попробуйте другие ключевые слова**\n";
        $response .= "4. **Обратитесь к специалисту** для индивидуальной консультации\n";
        
        $response .= "\n**📊 Примеры успешных запросов:**\n";
        $response .= "• \"не заводится двигатель\"\n";
        $response .= "• \"стук в двигателе\"\n";
        $response .= "• \"горит лампочка check engine\"\n";
        $response .= "• \"плохо греет печка\"\n";
        
        return $response;
    }

    /**
     * Обрезание описания
     */
    private function truncateDescription($text, $length = 150)
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

    /**
     * Получить популярные симптомы
     */
    public function getPopularSymptoms()
    {
        try {
            $symptoms = Symptom::where('is_active', true)
                ->withCount('rules')
                ->orderBy('rules_count', 'desc')
                ->orderBy('frequency', 'desc')
                ->limit(15)
                ->get(['id', 'name', 'description', 'frequency', 'slug']);
            
            return response()->json([
                'success' => true,
                'symptoms' => $symptoms
            ]);
        } catch (\Exception $e) {
            Log::error('Get popular symptoms error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки популярных симптомов'
            ], 500);
        }
    }

    /**
     * Получить симптомы по системе
     */
    public function getSymptomsBySystem($system)
    {
        try {
            $validSystems = ['engine', 'transmission', 'brakes', 'electrical', 'suspension', 'exhaust', 'fuel', 'cooling'];
            
            if (!in_array(strtolower($system), $validSystems)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Некорректная система'
                ], 422);
            }
            
            $symptoms = Symptom::where('is_active', true)
                ->whereJsonContains('related_systems', $system)
                ->with(['rules' => function($q) {
                    $q->where('is_active', true)
                      ->with(['brand', 'model']);
                }])
                ->get();
            
            return response()->json([
                'success' => true,
                'system' => $system,
                'symptoms' => $symptoms,
                'count' => $symptoms->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Get symptoms by system error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки симптомов по системе'
            ], 500);
        }
    }
}