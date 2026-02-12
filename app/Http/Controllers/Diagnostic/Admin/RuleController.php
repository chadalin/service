<?php

namespace App\Http\Controllers\Diagnostic\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Diagnostic\Symptom;
use App\Models\Diagnostic\Rule;
use App\Models\PriceItem;
use Illuminate\Http\Request;
use App\Models\Diagnostic\DiagnosticCase;
use App\Models\Diagnostic\Consultation;
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
            
            // Получаем консультации, связанные с этим правилом
            $consultations = $this->getRelatedConsultations($rule);
            
            return view('admin.diagnostic.rules.show', [
                'rule' => $rule,
                'brands' => $brands,
                'matchedPriceItems' => $matchedPriceItems,
                'consultations' => $consultations,
                'title' => 'Код ошибки или симптом OBD: ' . ($rule->symptom->name ?? 'Unknown')
            ]);
        } catch (\Exception $e) {
            Log::error('Error showing rule', ['rule_id' => $id, 'error' => $e->getMessage()]);
            
            return redirect()->route('admin.diagnostic.rules.index')
                ->with('error', 'Код ошибки или симптом OBD: ' . $e->getMessage());
        }
    }

    /**
     * Получить консультации, связанные с правилом
     */
    private function getRelatedConsultations(Rule $rule)
    {
        try {
            // Находим кейсы, созданные по этому правилу
            $caseIds = DiagnosticCase::where('rule_id', $rule->id)
                ->pluck('id')
                ->toArray();
            
            if (empty($caseIds)) {
                return collect();
            }
            
            // Получаем консультации с связанными данными
            $consultations = Consultation::with(['case', 'expert', 'user'])
                ->whereIn('case_id', $caseIds)
                ->where('status', 'completed') // Только завершенные консультации
                ->orWhere('status', 'in_progress') // Или в процессе
                ->orderBy('created_at', 'desc')
                ->limit(6) // Показываем не более 6 консультаций
                ->get();
            
            // Для каждой консультации пытаемся получить файлы из кейса
            foreach ($consultations as $consultation) {
                if ($consultation->case && !empty($consultation->case->uploaded_files)) {
                    $files = is_string($consultation->case->uploaded_files) 
                        ? json_decode($consultation->case->uploaded_files, true) 
                        : $consultation->case->uploaded_files;
                    
                    $consultation->preview_images = $this->extractPreviewImages($files);
                } else {
                    $consultation->preview_images = [];
                }
                
                // Сокращаем описание симптомов для превью
                if ($consultation->case && !empty($consultation->case->description)) {
                    $consultation->short_description = Str::limit($consultation->case->description, 120);
                } elseif ($consultation->case && !empty($consultation->case->symptoms)) {
                    $symptoms = is_string($consultation->case->symptoms) 
                        ? json_decode($consultation->case->symptoms, true) 
                        : $consultation->case->symptoms;
                    
                    if (is_array($symptoms) && !empty($symptoms)) {
                        $consultation->short_description = 'Симптомы: ' . implode(', ', array_slice($symptoms, 0, 3));
                    } else {
                        $consultation->short_description = 'Консультация эксперта';
                    }
                } else {
                    $consultation->short_description = 'Консультация по диагностике';
                }
            }
            
            return $consultations;
            
        } catch (\Exception $e) {
            Log::error('Error getting related consultations', [
                'rule_id' => $rule->id,
                'error' => $e->getMessage()
            ]);
            
            return collect();
        }
    }

    /**
     * Извлечь изображения для превью из загруженных файлов
     */
    private function extractPreviewImages($files)
    {
        $images = [];
        
        if (empty($files)) {
            return $images;
        }
        
        try {
            // Если files это массив
            if (is_array($files)) {
                foreach ($files as $key => $fileGroup) {
                    if (is_array($fileGroup)) {
                        foreach ($fileGroup as $file) {
                            if ($this->isImageFile($file)) {
                                $images[] = $file;
                                if (count($images) >= 3) break; // Не более 3 изображений
                            }
                        }
                    }
                }
            }
            
            // Также проверяем отдельные поля для фото симптомов
            if (isset($files['symptom_photos']) && is_array($files['symptom_photos'])) {
                foreach ($files['symptom_photos'] as $photo) {
                    if ($this->isImageFile($photo)) {
                        $images[] = $photo;
                        if (count($images) >= 3) break;
                    }
                }
            }
            
        } catch (\Exception $e) {
            Log::warning('Error extracting preview images', ['error' => $e->getMessage()]);
        }
        
        return $images;
    }

    /**
     * Проверить, является ли файл изображением
     */
    private function isImageFile($file)
    {
        if (is_string($file)) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        }
        
        if (is_array($file) && isset($file['name'])) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        }
        
        return false;
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