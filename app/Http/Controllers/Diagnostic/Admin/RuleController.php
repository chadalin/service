<?php

namespace App\Http\Controllers\Diagnostic\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Diagnostic\Symptom;
use App\Models\Diagnostic\Rule;
use App\Models\PriceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RuleController extends Controller
{
    public function index()
    {
        $symptoms = Symptom::where('is_active', true)->get();
        $brands = Brand::all();
        $rules = Rule::with(['symptom', 'brand', 'model'])->paginate(20);
        return view('diagnostic.admin.rules.index', compact('rules','symptoms','brands'));
    }
    
    public function create()
    {
        $symptoms = Symptom::where('is_active', true)->get();
        $brands = Brand::all();
        
        return view('diagnostic.admin.rules.create', compact('symptoms', 'brands'));
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'symptom_id' => 'required|exists:diagnostic_symptoms,id',
            'brand_id' => 'required|exists:brands,id',
            'model_id' => 'nullable|exists:car_models,id',
            'conditions' => 'required|json',
            'possible_causes' => 'required|json',
            'diagnostic_steps' => 'required|json',
        ]);
        
        Rule::create([
            'symptom_id' => $request->symptom_id,
            'brand_id' => $request->brand_id,
            'model_id' => $request->model_id,
            'conditions' => json_decode($request->conditions, true),
            'possible_causes' => json_decode($request->possible_causes, true),
            'required_data' => json_decode($request->required_data, true),
            'diagnostic_steps' => json_decode($request->diagnostic_steps, true),
            'complexity_level' => $request->complexity_level ?? 1,
            'estimated_time' => $request->estimated_time,
            'base_consultation_price' => $request->base_consultation_price ?? 3000,
            'is_active' => $request->has('is_active'),
        ]);
        
        return redirect()->route('admin.diagnostic.rules.index')
            ->with('success', 'Правило добавлено');
    }
    
    public function edit(Rule $rule)
    {
        $symptoms = Symptom::where('is_active', true)->get();
        $brands = Brand::all();
        $models = CarModel::where('brand_id', $rule->brand_id)->get();
        
        return view('diagnostic.admin.rules.edit', compact('rule', 'symptoms', 'brands', 'models'));
    }
    
    public function update(Request $request, Rule $rule)
    {
        $request->validate([
            'symptom_id' => 'required|exists:diagnostic_symptoms,id',
            'brand_id' => 'required|exists:brands,id',
            'model_id' => 'nullable|exists:car_models,id',
            'conditions' => 'required|json',
            'possible_causes' => 'required|json',
            'diagnostic_steps' => 'required|json',
        ]);
        
        $rule->update([
            'symptom_id' => $request->symptom_id,
            'brand_id' => $request->brand_id,
            'model_id' => $request->model_id,
            'conditions' => json_decode($request->conditions, true),
            'possible_causes' => json_decode($request->possible_causes, true),
            'required_data' => json_decode($request->required_data, true),
            'diagnostic_steps' => json_decode($request->diagnostic_steps, true),
            'complexity_level' => $request->complexity_level ?? 1,
            'estimated_time' => $request->estimated_time,
            'base_consultation_price' => $request->base_consultation_price ?? 3000,
            'is_active' => $request->has('is_active'),
        ]);
        
        return redirect()->route('admin.diagnostic.rules.index')
            ->with('success', 'Правило обновлено');
    }
    
    public function destroy(Rule $rule)
    {
        $rule->delete();
        return redirect()->route('admin.diagnostic.rules.index')
            ->with('success', 'Правило удалено');
    }
    
    public function getModels(Request $request)
    {
        $models = CarModel::where('brand_id', $request->brand_id)->get();
        return response()->json($models);
    }

    public function show($id)
{
    try {
        $rule = Rule::with(['symptom', 'brand', 'model'])
            ->findOrFail($id);

        $brands = Brand::orderBy('name')->get();
        
        // Поиск связанных запчастей (с защитой от ошибок)
        $matchedPriceItems = $this->findMatchingPriceItemsSafely($rule);
        
        return view('admin.diagnostic.rules.show', [
            'rule' => $rule,
            'brands' => $brands,
            'matchedPriceItems' => $matchedPriceItems,
            'title' => 'Правило диагностики: ' . ($rule->symptom->name ?? 'Unknown')
        ]);
    } catch (\Exception $e) {
        Log::error('Error showing rule', ['rule_id' => $id, 'error' => $e->getMessage()]);
        
        return redirect()->route('admin.diagnostic.rules.index')
            ->with('error', 'Правило не найдено: ' . $e->getMessage());
    }
}

/**
 * Безопасный поиск связанных запчастей
 */
private function findMatchingPriceItemsSafely(Rule $rule)
{
    try {
        // Проверяем, существует ли модель PriceItem
        if (!class_exists(\App\Models\PriceItem::class)) {
            Log::warning('PriceItem model not found');
            return collect();
        }
        
        // Проверяем, существует ли таблица
        if (!\Illuminate\Support\Facades\Schema::hasTable('price_items')) {
            Log::warning('price_items table not found');
            return collect();
        }
        
        $searchTerms = [];
        
        // 1. Ищем по названию симптома
        if ($rule->symptom && !empty($rule->symptom->name)) {
            $searchTerms[] = $rule->symptom->name;
        }
        
        // 2. Ищем по возможным причинам из поля possible_causes
        if ($rule->possible_causes && is_array($rule->possible_causes)) {
            foreach ($rule->possible_causes as $cause) {
                if (!empty(trim($cause))) {
                    // Разбиваем сложные причины на отдельные слова
                    $words = preg_split('/[\s,;]+/', $cause);
                    foreach ($words as $word) {
                        if (strlen($word) > 3) { // Только слова длиннее 3 символов
                            $searchTerms[] = $word;
                        }
                    }
                }
            }
        }
        
        // 3. Ищем по описанию симптома
        if ($rule->symptom && !empty($rule->symptom->description)) {
            // Извлекаем ключевые слова из описания
            preg_match_all('/\b(\w{4,})\b/', $rule->symptom->description, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $keyword) {
                    $searchTerms[] = $keyword;
                }
            }
        }
        
        // Убираем дубликаты и ограничиваем количество терминов
        $searchTerms = array_unique($searchTerms);
        $searchTerms = array_slice($searchTerms, 0, 10); // Не более 10 терминов
        
        if (empty($searchTerms)) {
            return collect();
        }
        
        // Выполняем поиск
        $query = \App\Models\PriceItem::query()
            ->with(['brand'])
            ->where('quantity', '>', 0); // Только в наличии
        
        // Динамическое построение запроса
        $query->where(function($q) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                $term = trim($term);
                if (!empty($term)) {
                    $q->orWhere('name', 'like', '%' . $term . '%')
                      ->orWhere('description', 'like', '%' . $term . '%')
                      ->orWhere('sku', 'like', '%' . $term . '%')
                      ->orWhere('catalog_brand', 'like', '%' . $term . '%');
                }
            }
        });
        
        $priceItems = $query->limit(8)->get();
        
        // Если найдено мало запчастей, делаем более широкий поиск
        if ($priceItems->count() < 3 && $rule->symptom && !empty($rule->symptom->name)) {
            $mainTerm = $rule->symptom->name;
            $fallbackItems = \App\Models\PriceItem::query()
                ->with(['brand'])
                ->where('quantity', '>', 0)
                ->where(function($q) use ($mainTerm) {
                    $q->where('name', 'like', '%' . $mainTerm . '%')
                      ->orWhere('description', 'like', '%' . $mainTerm . '%');
                })
                ->limit(6)
                ->get();
            
            $priceItems = $priceItems->merge($fallbackItems)->unique('id');
        }
        
        // Сортируем по релевантности
        return $priceItems->sortByDesc(function($item) use ($searchTerms) {
            $score = 0;
            $itemText = strtolower($item->name . ' ' . $item->description . ' ' . $item->sku);
            
            foreach ($searchTerms as $term) {
                $termLower = strtolower($term);
                
                // Полное совпадение слова дает больше баллов
                if (preg_match('/\b' . preg_quote($termLower, '/') . '\b/', $itemText)) {
                    $score += 2;
                }
                // Частичное совпадение
                elseif (str_contains($itemText, $termLower)) {
                    $score += 1;
                }
            }
            
            // Дополнительные баллы за наличие и цену
            if ($item->quantity > 10) $score += 1;
            if ($item->price > 0) $score += 0.5;
            
            return $score;
        })->take(6);
        
    } catch (\Exception $e) {
        // В случае ошибки возвращаем пустую коллекцию
        Log::error('Error finding matching price items', [
            'rule_id' => $rule->id,
            'error' => $e->getMessage()
        ]);
        
        return collect();
    }
}
}