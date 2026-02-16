<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Diagnostic\Symptom;
use App\Models\Diagnostic\Rule;
use App\Models\Diagnostic\Consultation;
use App\Models\PriceItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
     /**
     * Главная посадочная страница (B2C)
     */
    
 
    public function landing()
    {
        return view('page.lendingd');
    }

        /**
     * Главная страница с диагностикой
     */
    public function index()    {
        // Получаем все бренды для селекта
        $brands = Brand::orderBy('name')->get();
        
        // Получаем популярные симптомы (топ-10 по использованию)
        $popularSymptoms = Symptom::where('is_active', true)
            ->withCount('rules')
            ->orderBy('rules_count', 'desc')
            ->limit(10)
            ->get();
        
        // Получаем последние консультации для отображения
        $recentConsultations = Consultation::with(['case.brand', 'case.model', 'expert'])
            ->whereIn('status', ['completed', 'in_progress'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($consultation) {
                return $this->enrichConsultation($consultation);
            });
        
        // Статистика для отображения
        $stats = [
            'total_diagnostics' => Rule::count(),
            'success_rate' => 94,
            'partner_services' => 327,
            'average_rating' => 4.9,
            'total_consultations' => Consultation::count(),
            'experts_online' => $this->getOnlineExpertsCount()
        ];
        
        return view('page.lending', compact(
            'brands', 
            'popularSymptoms', 
            'recentConsultations',
            'stats'
        ));
    }
    
    /**
     * API поиска по симптомам и кодам ошибок
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'brand_id' => 'nullable|exists:brands,id',
            'model_id' => 'nullable|exists:car_models,id'
        ]);
        
        $query = $request->input('query');
        $brandId = $request->input('brand_id');
        $modelId = $request->input('model_id');
        
        try {
            // Поиск по симптомам (кодам ошибок)
            $rules = Rule::with(['symptom', 'brand', 'model'])
                ->where('is_active', true)
                ->where(function($q) use ($query) {
                    // Поиск по названию симптома
                    $q->whereHas('symptom', function($sq) use ($query) {
                        $sq->where('name', 'LIKE', "%{$query}%")
                          ->orWhere('code', 'LIKE', "%{$query}%")
                          ->orWhere('description', 'LIKE', "%{$query}%");
                    })
                    // Поиск по возможным причинам (в JSON)
                    ->orWhere('possible_causes', 'LIKE', "%{$query}%")
                    // Поиск по диагностическим шагам
                    ->orWhere('diagnostic_steps', 'LIKE', "%{$query}%");
                });
            
            // Фильтр по бренду
            if ($brandId) {
                $rules->where('brand_id', $brandId);
            }
            
            // Фильтр по модели
            if ($modelId) {
                $rules->where('model_id', $modelId);
            }
            
            $rules = $rules->limit(10)->get();
            
            // Обогащаем результаты дополнительной информацией
            $enrichedRules = $rules->map(function($rule) {
                return [
                    'id' => $rule->id,
                    'symptom' => [
                        'id' => $rule->symptom->id ?? null,
                        'name' => $rule->symptom->name ?? 'Неизвестный симптом',
                        'code' => $rule->symptom->code ?? null,
                        'description' => $rule->symptom->description ?? null
                    ],
                    'brand' => $rule->brand ? [
                        'id' => $rule->brand->id,
                        'name' => $rule->brand->name
                    ] : null,
                    'model' => $rule->model ? [
                        'id' => $rule->model->id,
                        'name' => $rule->model->name
                    ] : null,
                    'possible_causes' => $this->parseJsonField($rule->possible_causes),
                    'diagnostic_steps' => $this->parseJsonField($rule->diagnostic_steps),
                    'required_data' => $this->parseJsonField($rule->required_data),
                    'complexity_level' => $rule->complexity_level ?? 1,
                    'estimated_time' => $rule->estimated_time,
                    'base_consultation_price' => $rule->base_consultation_price ?? 3000,
                    'consultations_count' => $this->getConsultationsCount($rule),
                    'matched_parts' => $this->findMatchingParts($rule)
                ];
            });
            
            return response()->json([
                'success' => true,
                'rules' => $enrichedRules,
                'total' => $rules->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Search error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при поиске',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Получение моделей по бренду
     */
    public function getModels($brandId)
    {
        try {
            $models = CarModel::where('brand_id', $brandId)
                ->orderBy('name')
                ->get(['id', 'name']);
            
            return response()->json($models);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ошибка загрузки моделей'], 500);
        }
    }
    
    /**
     * Получение консультаций для конкретного правила
     */
    public function getRuleConsultations($ruleId)
    {
        try {
            $consultations = DB::table('diagnostic_consultations as c')
                ->join('diagnostic_cases as dc', 'dc.id', '=', 'c.case_id')
                ->where('dc.rule_id', $ruleId)
                ->whereIn('c.status', ['completed', 'in_progress', 'paid', 'confirmed'])
                ->select(
                    'c.*',
                    'dc.id as case_id',
                    'dc.uploaded_files',
                    'dc.description as case_description',
                    'dc.symptoms',
                    'dc.brand_id',
                    'dc.model_id',
                    'dc.year',
                    'dc.created_at as case_created_at'
                )
                ->orderBy('c.created_at', 'desc')
                ->limit(10)
                ->get();
            
            $enrichedConsultations = [];
            
            foreach ($consultations as $item) {
                // Получаем информацию об эксперте
                $expert = null;
                if ($item->expert_id) {
                    $expert = DB::table('users')
                        ->where('id', $item->expert_id)
                        ->select('id', 'name', 'email')
                        ->first();
                }
                
                // Получаем бренд и модель
                $brand = null;
                $model = null;
                
                if ($item->brand_id) {
                    $brand = DB::table('brands')
                        ->where('id', $item->brand_id)
                        ->select('id', 'name')
                        ->first();
                }
                
                if ($item->model_id) {
                    $model = DB::table('car_models')
                        ->where('id', $item->model_id)
                        ->select('id', 'name')
                        ->first();
                }
                
                // Извлекаем изображения
                $images = $this->extractImagesFromCase($item);
                
                $enrichedConsultations[] = [
                    'id' => $item->id,
                    'case_id' => $item->case_id,
                    'status' => $item->status,
                    'type' => $item->type,
                    'price' => $item->price,
                    'created_at' => $item->created_at,
                    'short_description' => $this->getShortDescription($item),
                    'customer_feedback' => $item->customer_feedback ?? null,
                    'expert' => $expert,
                    'brand' => $brand,
                    'model' => $model,
                    'year' => $item->year,
                    'preview_images' => $images,
                    'files_count' => $this->countFiles($item)
                ];
            }
            
            return response()->json([
                'success' => true,
                'consultations' => $enrichedConsultations
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error loading consultations: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки консультаций'
            ], 500);
        }
    }
    
    /**
     * Создание заказа консультации
     */
    public function orderConsultation(Request $request)
    {
        $request->validate([
            'rule_id' => 'nullable|exists:diagnostic_rules,id',
            'symptom_description' => 'required|string|min:10',
            'contact_name' => 'required|string|max:255',
            'contact_phone' => 'required|string|max:20',
            'contact_email' => 'required|email|max:255',
            'year' => 'nullable|integer|min:1990|max:' . date('Y'),
            'mileage' => 'nullable|integer|min:0|max:1000000',
            'vin' => 'nullable|string|max:17',
            'engine_type' => 'nullable|string|max:50',
            'additional_info' => 'nullable|string',
            'agreement' => 'required|accepted'
        ]);
        
        try {
            DB::beginTransaction();
            
            // Создаем диагностический кейс
            $caseData = [
                'description' => $request->symptom_description,
                'brand_id' => $request->brand_id,
                'model_id' => $request->model_id,
                'year' => $request->year,
                'mileage' => $request->mileage,
                'vin' => $request->vin,
                'engine_type' => $request->engine_type,
                'status' => 'pending',
                'symptoms' => $request->rule_id ? [$request->rule_id] : [],
                'additional_info' => $request->additional_info
            ];
            
            // Обработка загруженных файлов
            if ($request->hasFile('files')) {
                $uploadedFiles = [];
                
                foreach ($request->file('files') as $file) {
                    $path = $file->store('consultations/' . date('Y/m/d'), 'public');
                    
                    $uploadedFiles[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'type' => str_starts_with($file->getMimeType(), 'image/') ? 'photo' : 'video'
                    ];
                }
                
                $caseData['uploaded_files'] = json_encode($uploadedFiles);
            }
            
            $caseId = DB::table('diagnostic_cases')->insertGetId($caseData);
            
            // Создаем консультацию
            $consultationId = DB::table('diagnostic_consultations')->insertGetId([
                'case_id' => $caseId,
                'type' => 'expert',
                'price' => $this->getConsultationPrice($request->rule_id),
                'status' => 'pending',
                'customer_name' => $request->contact_name,
                'customer_phone' => $request->contact_phone,
                'customer_email' => $request->contact_email,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::commit();
            
            // Отправляем уведомления (можно добавить позже)
            
            return response()->json([
                'success' => true,
                'message' => 'Заявка успешно отправлена',
                'consultation_id' => $consultationId,
                'redirect_url' => route('consultation.success', $consultationId)
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order consultation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании заявки: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Страница успешного заказа
     */
    public function success($id)
    {
        $consultation = DB::table('diagnostic_consultations')
            ->where('id', $id)
            ->first();
        
        if (!$consultation) {
            abort(404);
        }
        
        return view('diagnostic.consultation-success', compact('consultation'));
    }
    
    /**
     * ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
     */
    
    private function enrichConsultation($consultation)
    {
        if ($consultation->case) {
            // Извлекаем изображения
            $consultation->preview_images = $this->extractImagesFromCase($consultation->case);
            $consultation->short_description = $this->getShortDescription($consultation->case);
            
            // Получаем симптомы
            if (!empty($consultation->case->symptoms)) {
                $symptomIds = is_string($consultation->case->symptoms) 
                    ? json_decode($consultation->case->symptoms, true) 
                    : $consultation->case->symptoms;
                
                if (is_array($symptomIds)) {
                    $consultation->symptoms = Symptom::whereIn('id', $symptomIds)->get();
                }
            }
        }
        
        return $consultation;
    }
    
    private function parseJsonField($field)
    {
        if (is_string($field)) {
            return json_decode($field, true) ?? [];
        }
        return $field ?? [];
    }
    
    private function getConsultationsCount($rule)
    {
        return DB::table('diagnostic_consultations as c')
            ->join('diagnostic_cases as dc', 'dc.id', '=', 'c.case_id')
            ->where('dc.rule_id', $rule->id)
            ->count();
    }
    
    private function findMatchingParts($rule)
    {
        try {
            if (!class_exists(PriceItem::class)) {
                return [];
            }
            
            $searchTerms = [];
            
            // Добавляем название симптома
            if ($rule->symptom && !empty($rule->symptom->name)) {
                $searchTerms[] = $rule->symptom->name;
            }
            
            // Добавляем возможные причины
            $causes = $this->parseJsonField($rule->possible_causes);
            foreach ($causes as $cause) {
                if (is_string($cause) && strlen($cause) > 3) {
                    $searchTerms[] = $cause;
                }
            }
            
            if (empty($searchTerms)) {
                return [];
            }
            
            // Ищем запчасти
            $parts = PriceItem::where('quantity', '>', 0)
                ->where(function($q) use ($searchTerms) {
                    foreach (array_slice($searchTerms, 0, 5) as $term) {
                        $q->orWhere('name', 'LIKE', "%{$term}%")
                          ->orWhere('description', 'LIKE', "%{$term}%")
                          ->orWhere('sku', 'LIKE', "%{$term}%");
                    }
                })
                ->limit(3)
                ->get();
            
            return $parts->map(function($part) {
                return [
                    'id' => $part->id,
                    'name' => $part->name,
                    'sku' => $part->sku,
                    'price' => $part->price,
                    'quantity' => $part->quantity,
                    'brand' => $part->brand->name ?? $part->catalog_brand ?? 'Неизвестный бренд'
                ];
            });
            
        } catch (\Exception $e) {
            Log::warning('Error finding matching parts: ' . $e->getMessage());
            return [];
        }
    }
    
    private function extractImagesFromCase($case)
    {
        $images = [];
        
        if (empty($case->uploaded_files)) {
            return $images;
        }
        
        try {
            $files = is_string($case->uploaded_files) 
                ? json_decode($case->uploaded_files, true) 
                : $case->uploaded_files;
            
            if (!is_array($files)) {
                return $images;
            }
            
            foreach ($files as $file) {
                if (!is_array($file)) continue;
                
                $isImage = false;
                $path = $file['path'] ?? null;
                
                if (!$path) continue;
                
                // Проверяем тип
                if (isset($file['mime_type']) && str_starts_with($file['mime_type'], 'image/')) {
                    $isImage = true;
                } elseif (isset($file['type']) && $file['type'] === 'photo') {
                    $isImage = true;
                } else {
                    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $isImage = true;
                    }
                }
                
                if ($isImage) {
                    // Формируем URL
                    $path = ltrim($path, '/');
                    $path = str_replace(['public/', 'storage/'], '', $path);
                    $images[] = '/storage/' . $path;
                    
                    if (count($images) >= 3) break;
                }
            }
            
        } catch (\Exception $e) {
            Log::warning('Error extracting images: ' . $e->getMessage());
        }
        
        return $images;
    }
    
    private function getShortDescription($case)
    {
        if (!$case) {
            return 'Консультация по диагностике';
        }
        
        if (!empty($case->description)) {
            return mb_substr($case->description, 0, 100) . '...';
        }
        
        return 'Консультация по диагностике автомобиля';
    }
    
    private function countFiles($case)
    {
        if (empty($case->uploaded_files)) {
            return 0;
        }
        
        $files = is_string($case->uploaded_files) 
            ? json_decode($case->uploaded_files, true) 
            : $case->uploaded_files;
        
        return is_array($files) ? count($files) : 0;
    }
    
    private function getOnlineExpertsCount()
    {
        // Здесь можно добавить реальную логику подсчета онлайн экспертов
        // Например, по последней активности в течение 15 минут
        return rand(3, 8); // Пока рандом для демонстрации
    }
    
    private function getConsultationPrice($ruleId = null)
    {
        if ($ruleId) {
            $rule = Rule::find($ruleId);
            if ($rule && $rule->base_consultation_price) {
                return $rule->base_consultation_price;
            }
        }
        
        return 3000; // Базовая цена по умолчанию
    }
    
}
