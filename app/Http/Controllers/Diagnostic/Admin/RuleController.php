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
use Illuminate\Support\Facades\DB;

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
 /**
 * Получить консультации, связанные с правилом
 */
/**
 * Получить консультации, связанные с правилом
 */
/**
 * Получить консультации, связанные с правилом
 */
private function getRelatedConsultations(Rule $rule)
{
    try {
        Log::info('========== GET RELATED CONSULTATIONS START ==========');
        Log::info('Rule data:', [
            'rule_id' => $rule->id,
            'symptom_id' => $rule->symptom_id
        ]);

        // 1. Прямой SQL запрос для получения консультаций
        $consultations = DB::table('diagnostic_consultations as c')
            ->join('diagnostic_cases as dc', 'dc.id', '=', 'c.case_id')
            ->where('dc.rule_id', $rule->id)
            ->whereNotNull('c.case_id')
            ->where('c.case_id', '!=', '0')
            ->where('c.case_id', '!=', '')
            ->whereIn('c.status', ['completed', 'in_progress', 'paid', 'confirmed', 'pending'])
            ->select(
                'c.*',
                'dc.id as case_id',
                'dc.uploaded_files',
                'dc.description as case_description',
                'dc.symptoms',
                'dc.created_at as case_created_at'
            )
            ->orderBy('c.created_at', 'desc')
            ->limit(6)
            ->get();
        
        Log::info('SQL Query Result:', [
            'count' => $consultations->count(),
            'data' => $consultations->toArray()
        ]);
        
        if ($consultations->isEmpty()) {
            Log::info('No consultations found');
            return collect();
        }
        
        // 2. Преобразуем в коллекцию моделей Consultation
        $result = collect();
        
        foreach ($consultations as $item) {
            // Создаем объект Consultation
            $consultation = new \App\Models\Diagnostic\Consultation();
            $consultation->id = $item->id;
            $consultation->case_id = $item->case_id;
            $consultation->user_id = $item->user_id;
            $consultation->expert_id = $item->expert_id;
            $consultation->type = $item->type;
            $consultation->price = $item->price;
            $consultation->status = $item->status;
            $consultation->created_at = $item->created_at;
            
            // Извлекаем фото из uploaded_files
            $images = [];
            if (!empty($item->uploaded_files)) {
                $files = is_string($item->uploaded_files) 
                    ? json_decode($item->uploaded_files, true) 
                    : $item->uploaded_files;
                
                if (is_array($files)) {
                    foreach ($files as $file) {
                        if (is_array($file) && isset($file['path'])) {
                            // Проверяем что это фото
                            $isImage = false;
                            if (isset($file['type']) && $file['type'] === 'photo') {
                                $isImage = true;
                            } elseif (isset($file['mime_type']) && str_starts_with($file['mime_type'], 'image/')) {
                                $isImage = true;
                            } else {
                                $ext = pathinfo($file['path'], PATHINFO_EXTENSION);
                                if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                                    $isImage = true;
                                }
                            }
                            
                            if ($isImage) {
                                $path = ltrim($file['path'], '/');
                                $path = str_replace(['public/', 'storage/'], '', $path);
                                $images[] = 'storage/' . $path;
                                
                                if (count($images) >= 3) break;
                            }
                        }
                    }
                }
            }
            
            $consultation->preview_images = $images;
            $consultation->short_description = \Illuminate\Support\Str::limit(
                $item->case_description ?? 'Консультация по диагностике', 
                120
            );
            $consultation->case_created_at = $item->case_created_at;
            
            // Получаем названия симптомов
            if (!empty($item->symptoms)) {
                $symptomIds = is_string($item->symptoms) 
                    ? json_decode($item->symptoms, true) 
                    : $item->symptoms;
                
                if (is_array($symptomIds) && !empty($symptomIds)) {
                    $symptomNames = Symptom::whereIn('id', $symptomIds)
                        ->pluck('name', 'id')
                        ->toArray();
                    $consultation->symptom_names = $symptomNames;
                }
            }
            
            $result->push($consultation);
            
            Log::info('Added consultation:', [
                'id' => $consultation->id,
                'case_id' => $consultation->case_id,
                'images' => $images
            ]);
        }
        
        Log::info('Final result:', [
            'count' => $result->count(),
            'ids' => $result->pluck('id')->toArray()
        ]);
        
        return $result;
        
    } catch (\Exception $e) {
        Log::error('Error in getRelatedConsultations:', [
            'rule_id' => $rule->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return collect();
    }
}
/**
 * Получить названия симптомов из кейса
 */
private function getSymptomNames($case)
{
    if (!$case || empty($case->symptoms)) {
        return [];
    }
    
    try {
        $symptomIds = is_string($case->symptoms) 
            ? json_decode($case->symptoms, true) 
            : $case->symptoms;
        
        if (empty($symptomIds) || !is_array($symptomIds)) {
            return [];
        }
        
        // Получаем названия симптомов
        $symptoms = Symptom::whereIn('id', $symptomIds)
            ->pluck('name', 'id')
            ->toArray();
        
        return $symptoms;
        
    } catch (\Exception $e) {
        \Log::warning('Error getting symptom names', ['case_id' => $case->id]);
        return [];
    }
}

  /**
 * Вспомогательный метод для шаблона
 */
public function getImageUrl($path)
{
    if (empty($path)) {
        return asset('img/no-image.jpg');
    }
    
    // Если это уже полный URL
    if (filter_var($path, FILTER_VALIDATE_URL)) {
        return $path;
    }
    
    // Если путь начинается с public/
    if (str_starts_with($path, 'public/')) {
        return Storage::url($path);
    }
    
    // Если путь начинается с uploads/
    if (str_starts_with($path, 'uploads/')) {
        return asset($path);
    }
    
    // Проверяем существование файла в различных директориях
    $possiblePaths = [
        'public/' . $path,
        'uploads/' . $path,
        'storage/' . $path,
        $path
    ];
    
    foreach ($possiblePaths as $possiblePath) {
        if (Storage::exists($possiblePath)) {
            return Storage::url($possiblePath);
        }
    }
    
    // Возвращаем заглушку если файл не найден
    return asset('img/no-image.jpg');
}

/**
 * Получить сокращенное описание из кейса
 */
private function getShortDescription($case)
{
    if (!$case) {
        return 'Консультация по диагностике';
    }
    
    if (!empty($case->description)) {
        return \Illuminate\Support\Str::limit($case->description, 120);
    }
    
    if (!empty($case->analysis_result)) {
        $result = is_string($case->analysis_result) 
            ? $case->analysis_result 
            : (is_array($case->analysis_result) ? json_encode($case->analysis_result) : '');
        
        return \Illuminate\Support\Str::limit($result, 120);
    }
    
    return 'Консультация по диагностике автомобиля';
}


 
/**
 * Извлечь изображения из кейса - ИСПРАВЛЕННАЯ ВЕРСИЯ
 */
/**
 * Извлечь изображения из case - ИСПРАВЛЕННАЯ ВЕРСИЯ
 */
private function extractImagesFromCase($caseData)
{
    $images = [];
    
    if (!$caseData || empty($caseData->uploaded_files)) {
        Log::info('No uploaded_files for case', ['case_id' => $caseData->id ?? null]);
        return $images;
    }
    
    try {
        // Получаем файлы
        $uploadedFiles = [];
        
        if (is_string($caseData->uploaded_files)) {
            $uploadedFiles = json_decode($caseData->uploaded_files, true) ?? [];
        } elseif (is_array($caseData->uploaded_files)) {
            $uploadedFiles = $caseData->uploaded_files;
        }
        
        Log::info('Processing uploaded_files', [
            'case_id' => $caseData->id,
            'files_count' => count($uploadedFiles)
        ]);
        
        if (empty($uploadedFiles)) {
            return $images;
        }
        
        // Проходим по всем файлам и ищем изображения
        foreach ($uploadedFiles as $file) {
            if (!is_array($file)) {
                continue;
            }
            
            // Проверяем наличие пути
            $filePath = $file['path'] ?? null;
            
            if (empty($filePath)) {
                continue;
            }
            
            // Определяем тип файла
            $isImage = false;
            
            // По mime_type
            if (isset($file['mime_type']) && str_starts_with($file['mime_type'], 'image/')) {
                $isImage = true;
            }
            // По type
            elseif (isset($file['type']) && $file['type'] === 'photo') {
                $isImage = true;
            }
            // По расширению
            else {
                $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
                if (in_array($extension, $imageExtensions)) {
                    $isImage = true;
                }
            }
            
            if ($isImage) {
                // Формируем URL
                $path = ltrim($filePath, '/');
                $path = str_replace('public/', '', $path);
                $path = str_replace('storage/', '', $path);
                
                $imageUrl = 'storage/' . $path;
                $images[] = $imageUrl;
                
                Log::info('Found image', [
                    'case_id' => $caseData->id,
                    'path' => $filePath,
                    'url' => $imageUrl,
                    'type' => $file['type'] ?? 'unknown',
                    'mime' => $file['mime_type'] ?? 'unknown'
                ]);
                
                if (count($images) >= 3) break;
            }
        }
        
        Log::info('Extracted images result', [
            'case_id' => $caseData->id,
            'images_found' => count($images),
            'images' => $images
        ]);
        
        return $images;
        
    } catch (\Exception $e) {
        Log::error('Error extracting images from case', [
            'case_id' => $caseData->id ?? null,
            'error' => $e->getMessage()
        ]);
        
        return [];
    }
}

/**
 * Извлечь путь к изображению из различных форматов
 */
private function extractImagePath($photo)
{
    if (empty($photo)) {
        return null;
    }
    
    // Если это строка - это путь
    if (is_string($photo)) {
        return $photo;
    }
    
    // Если это массив
    if (is_array($photo)) {
        // Проверяем разные возможные ключи
        $possibleKeys = ['path', 'filepath', 'url', 'src', 'tmp_name', 'name', 'filename'];
        
        foreach ($possibleKeys as $key) {
            if (isset($photo[$key]) && !empty($photo[$key]) && is_string($photo[$key])) {
                return $photo[$key];
            }
        }
        
        // Если ключей нет, берем первое значение
        foreach ($photo as $value) {
            if (is_string($value) && !empty($value)) {
                return $value;
            }
            break;
        }
    }
    
    return null;
}

/**
 * Нормализовать информацию о файле в единый формат
 */
private function normalizeFileInfo($file)
{
    if (empty($file)) {
        return null;
    }
    
    $normalized = [
        'original_name' => null,
        'name' => null,
        'path' => null,
        'filepath' => null,
        'url' => null,
        'size' => null,
        'mime_type' => null,
        'extension' => null
    ];
    
    if (is_string($file)) {
        $normalized['path'] = $file;
        $normalized['filepath'] = $file;
        $normalized['name'] = basename($file);
        $normalized['original_name'] = basename($file);
        $normalized['extension'] = pathinfo($file, PATHINFO_EXTENSION);
        
    } elseif (is_array($file)) {
        // Маппинг различных ключей
        $pathKeys = ['path', 'filepath', 'file_path', 'url', 'src', 'tmp_name'];
        foreach ($pathKeys as $key) {
            if (isset($file[$key]) && !empty($file[$key])) {
                $normalized['path'] = $file[$key];
                $normalized['filepath'] = $file[$key];
                break;
            }
        }
        
        // Ищем имя файла
        $nameKeys = ['original_name', 'originalName', 'name', 'filename', 'file_name'];
        foreach ($nameKeys as $key) {
            if (isset($file[$key]) && !empty($file[$key])) {
                $normalized['original_name'] = $file[$key];
                $normalized['name'] = $file[$key];
                break;
            }
        }
        
        // Если имя не найдено, берем из пути
        if (empty($normalized['name']) && !empty($normalized['path'])) {
            $normalized['name'] = basename($normalized['path']);
            $normalized['original_name'] = basename($normalized['path']);
        }
        
        // Размер файла
        if (isset($file['size'])) {
            $normalized['size'] = $file['size'];
        }
        
        // MIME тип
        if (isset($file['mime_type'])) {
            $normalized['mime_type'] = $file['mime_type'];
        } elseif (isset($file['type'])) {
            $normalized['mime_type'] = $file['type'];
        }
        
        // Расширение
        if (!empty($normalized['name'])) {
            $normalized['extension'] = pathinfo($normalized['name'], PATHINFO_EXTENSION);
        } elseif (!empty($normalized['path'])) {
            $normalized['extension'] = pathinfo($normalized['path'], PATHINFO_EXTENSION);
        }
    }
    
    // Проверяем, что у нас есть хотя бы путь
    if (empty($normalized['path'])) {
        return null;
    }
    
    return $normalized;
}


     /**
 * Извлечь путь из элемента файла
 */
private function extractPathFromFileItem($item)
{
    if (empty($item)) {
        return null;
    }
    
    if (is_string($item)) {
        return $item;
    }
    
    if (is_array($item)) {
        // Проверяем различные возможные ключи
        $pathKeys = ['path', 'url', 'filepath', 'file_path', 'name', 'filename', 'file', 'src', 'tmp_name'];
        
        foreach ($pathKeys as $key) {
            if (isset($item[$key]) && !empty($item[$key]) && is_string($item[$key])) {
                return $item[$key];
            }
        }
        
        // Если ключей нет, берем первый элемент
        $firstValue = reset($item);
        if (is_string($firstValue) && !empty($firstValue)) {
            return $firstValue;
        }
    }
    
    return null;
}

    /**
 * Получить путь к изображению из различных форматов
 */
private function getImagePath($file)
{
    if (is_string($file)) {
        return $file;
    }
    
    if (is_array($file)) {
        // Проверяем различные возможные ключи
        $pathKeys = ['path', 'url', 'filepath', 'file_path', 'name', 'filename'];
        foreach ($pathKeys as $key) {
            if (isset($file[$key]) && !empty($file[$key])) {
                return $file[$key];
            }
        }
    }
    
    return null;
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
    private function isImageFile($path)
{
    if (empty($path)) {
        return false;
    }
    
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'heic', 'heif'];
    
    return in_array($extension, $imageExtensions);
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