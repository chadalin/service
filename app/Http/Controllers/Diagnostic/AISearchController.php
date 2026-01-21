<?php

namespace App\Http\Controllers\Diagnostic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Diagnostic\Symptom;
use App\Models\Diagnostic\Rule;
use App\Models\Brand;
use App\Models\CarModel;
use Illuminate\Support\Facades\Log;

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
        $request->validate([
            'query' => 'required|string|max:1000',
            'brand_id' => 'nullable|integer',
            'model_id' => 'nullable|integer',
            'search_type' => 'nullable|in:basic,advanced',
        ]);

        $startTime = microtime(true);
        $query = $request->input('query');
        $brandId = $request->input('brand_id');
        $modelId = $request->input('model_id');
        $searchType = $request->input('search_type', 'basic');

        Log::info('AI Symptom Search', [
            'query' => $query,
            'brand_id' => $brandId,
            'model_id' => $modelId,
            'search_type' => $searchType,
            'user_id' => auth()->id(),
        ]);

        // 1. Поиск симптомов по описанию
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

        // Расширенный поиск по нескольким полям
        if ($searchType === 'advanced') {
            $symptomsQuery->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('related_systems', 'like', "%{$query}%");
            });
        } else {
            // Базовый поиск - ищем в названии и описании
            $keywords = $this->extractKeywords($query);
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
        
        // 2. Если не найдено по прямому поиску, ищем похожие симптомы
        if ($symptoms->isEmpty() && $searchType === 'advanced') {
            $symptoms = $this->findSimilarSymptoms($query);
        }

        // 3. Формируем результаты
        $results = $this->prepareResults($symptoms, $brandId, $modelId);
        
        // 4. Генерируем AI ответ
        $aiResponse = $this->generateAIResponse($query, $symptoms, $results, $brandId, $modelId);

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        return response()->json([
            'success' => true,
            'query' => $query,
            'count' => count($results),
            'results' => $results,
            'ai_response' => $aiResponse,
            'search_type' => $searchType,
            'execution_time' => $executionTime,
            'stats' => [
                'symptoms_found' => $symptoms->count(),
                'rules_found' => $symptoms->sum(function($symptom) {
                    return $symptom->rules->count();
                }),
            ]
        ]);
    }

    /**
     * Извлечение ключевых слов из запроса
     */
    private function extractKeywords($query)
    {
        // Удаляем стоп-слова
        $stopWords = ['и', 'или', 'но', 'на', 'в', 'с', 'по', 'у', 'о', 'об', 'от', 'до', 'за', 'из', 'к', 'со', 'то', 'же', 'бы', 'ли', 'не', 'нет', 'да', 'как', 'что', 'это', 'так', 'вот', 'ну', 'нужно', 'очень', 'можно', 'надо', 'мне', 'меня', 'мой', 'моя', 'мое', 'мои'];
        
        $words = preg_split('/[\s,\.\-\(\)]+/', mb_strtolower($query));
        $keywords = array_filter($words, function($word) use ($stopWords) {
            return strlen($word) > 2 && !in_array($word, $stopWords);
        });
        
        return array_unique($keywords);
    }

    /**
     * Поиск похожих симптомов
     */
    private function findSimilarSymptoms($query)
    {
        $keywords = $this->extractKeywords($query);
        
        if (empty($keywords)) {
            return collect();
        }
        
        // Ищем симптомы по отдельным словам
        return Symptom::where('is_active', true)
            ->with(['rules' => function($q) {
                $q->where('is_active', true)
                  ->with(['brand', 'model']);
            }])
            ->where(function($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->orWhere('name', 'like', "%{$keyword}%");
                }
            })
            ->get();
    }

    /**
     * Подготовка результатов для отображения
     */
    private function prepareResults($symptoms, $brandId = null, $modelId = null)
    {
        $results = [];
        
        foreach ($symptoms as $symptom) {
            if ($symptom->rules->isEmpty()) {
                // Симптом без конкретных правил
                $results[] = [
                    'type' => 'symptom',
                    'id' => $symptom->id,
                    'symptom_id' => $symptom->id,
                    'title' => $symptom->name,
                    'description' => $symptom->description,
                    'relevance_score' => 0.8,
                    'has_rules' => false,
                    'rules_count' => 0,
                    'related_systems' => $symptom->related_systems,
                ];
            } else {
                // Симптом с правилами
                foreach ($symptom->rules as $rule) {
                    // Фильтруем по бренду и модели если заданы
                    if ($brandId && $rule->brand_id != $brandId) {
                        continue;
                    }
                    
                    if ($modelId && $rule->model_id != $modelId) {
                        continue;
                    }
                    
                    $relevanceScore = $this->calculateRelevanceScore($symptom, $rule);
                    
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
                        'has_rules' => true,
                        'rules_count' => $symptom->rules->count(),
                        'related_systems' => $symptom->related_systems,
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
     * Расчет релевантности
     */
    private function calculateRelevanceScore($symptom, $rule)
    {
        $score = 0.7; // Базовый score
        
        // Бонус за наличие диагностических шагов
        if (!empty($rule->diagnostic_steps) && is_array($rule->diagnostic_steps)) {
            $score += 0.1;
        }
        
        // Бонус за наличие возможных причин
        if (!empty($rule->possible_causes) && is_array($rule->possible_causes)) {
            $score += 0.1;
        }
        
        // Бонус за конкретную модель
        if ($rule->model_id) {
            $score += 0.05;
        }
        
        return min(1.0, $score);
    }

    /**
     * Генерация AI ответа
     */
    private function generateAIResponse($query, $symptoms, $results, $brandId = null, $modelId = null)
    {
        if ($symptoms->isEmpty()) {
            return "🔍 **По вашему запросу \"{$query}\" не найдено подходящих симптомов в базе данных.**\n\n" .
                   "**Рекомендации:**\n" .
                   "1. Попробуйте описать проблему другими словами\n" .
                   "2. Уточните марку и модель автомобиля\n" .
                   "3. Обратитесь к специалисту для индивидуальной диагностики\n" .
                   "4. Проверьте, нет ли опечаток в описании проблемы";
        }

        $totalSymptoms = $symptoms->count();
        $totalRules = $symptoms->sum(function($symptom) {
            return $symptom->rules->count();
        });
        
        $response = "🤖 **AI-анализ вашей проблемы:**\n\n";
        $response .= "По запросу **\"{$query}\"** найдено **{$totalSymptoms} симптомов** с **{$totalRules} правилами диагностики**.\n\n";
        
        if ($brandId) {
            $brand = Brand::find($brandId);
            $response .= "🔧 **Фильтр по марке:** {$brand->name}\n";
        }
        
        if ($modelId) {
            $model = CarModel::find($modelId);
            $response .= "🚗 **Фильтр по модели:** {$model->name}\n";
        }
        
        $response .= "\n**Наиболее вероятные проблемы:**\n";
        
        // Показываем топ-3 результата
        $topResults = array_slice($results, 0, 3);
        foreach ($topResults as $index => $result) {
            $response .= "\n" . ($index + 1) . ". **{$result['title']}**\n";
            
            if (!empty($result['description'])) {
                $response .= "   📝 {$result['description']}\n";
            }
            
            if ($result['type'] === 'rule') {
                if ($result['brand']) {
                    $response .= "   🏷️ Для: {$result['brand']}" . 
                                ($result['model'] ? " {$result['model']}" : "") . "\n";
                }
                
                if (!empty($result['possible_causes']) && is_array($result['possible_causes'])) {
                    $causes = array_slice($result['possible_causes'], 0, 3);
                    $response .= "   ⚠️ Возможные причины: " . implode(', ', $causes);
                    if (count($result['possible_causes']) > 3) {
                        $response .= " и ещё " . (count($result['possible_causes']) - 3);
                    }
                    $response .= "\n";
                }
                
                $response .= "   ⏱️ Примерное время диагностики: {$result['estimated_time']} мин.\n";
                $response .= "   💰 Рекомендуемая цена консультации: " . number_format($result['consultation_price'], 0, '.', ' ') . " ₽\n";
            }
        }
        
        $response .= "\n**📊 Статистика найденного:**\n";
        $response .= "• Всего симптомов: {$totalSymptoms}\n";
        $response .= "• Всего правил диагностики: {$totalRules}\n";
        
        // Считаем по брендам
        $brandsCount = collect($results)
            ->where('type', 'rule')
            ->pluck('brand')
            ->filter()
            ->unique()
            ->count();
        
        if ($brandsCount > 0) {
            $response .= "• Затронуто марок: {$brandsCount}\n";
        }
        
        $response .= "\n**🎯 Рекомендации по дальнейшим действиям:**\n";
        $response .= "1. **Выберите наиболее подходящий симптом** из списка выше\n";
        $response .= "2. **Ознакомьтесь с диагностическими шагами** для выбранной проблемы\n";
        $response .= "3. **Проверьте необходимые данные** перед началом диагностики\n";
        
        if ($totalRules > 0) {
            $response .= "4. **При необходимости закажите консультацию** специалиста\n";
        } else {
            $response .= "4. **Для этого симптома пока нет конкретных правил** - обратитесь к эксперту\n";
        }
        
        $response .= "\n💡 **Совет:** Для более точной диагностики укажите марку и модель автомобиля.";
        
        return $response;
    }

    /**
     * Получить популярные симптомы
     */
    public function getPopularSymptoms()
    {
        $symptoms = Symptom::where('is_active', true)
            ->orderBy('frequency', 'desc')
            ->limit(10)
            ->get(['id', 'name', 'description', 'frequency']);
        
        return response()->json([
            'success' => true,
            'symptoms' => $symptoms
        ]);
    }

    /**
     * Получить симптомы по системе
     */
    public function getSymptomsBySystem($system)
    {
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
    }
}