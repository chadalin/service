<?php

namespace App\Http\Controllers\Diagnostic;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Diagnostic\Symptom;
use App\Models\Diagnostic\Rule;
use App\Models\Diagnostic\DiagnosticCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiagnosticController extends Controller
{
    public function __construct()
    {
      //  $this->middleware('auth');
    }

     public function step1()
    {
        $symptoms = Symptom::where('is_active', true)
            ->orderBy('frequency', 'desc')
            ->get(['id', 'name', 'description', 'frequency']);
            
        $brands = Brand::orderBy('name')->get(['id', 'name']);
        
        return view('diagnostic.step1', [
            'symptoms' => $symptoms,
            'brands' => $brands,
            'showProgress' => true,
            'currentStep' => 1
        ]);
    }
    
    public function getModels(Request $request, $brandId)
    {
        $models = CarModel::where('brand_id', $brandId)
            ->orderBy('name')
            ->get(['id', 'name']);
        return response()->json($models);
    }
    
    public function step2(Request $request)
    {
        $request->validate([
            'symptoms' => 'required|array',
            'symptoms.*' => 'integer|exists:diagnostic_symptoms,id',
            'brand_id' => 'required|exists:brands,id',
            'model_id' => 'nullable|exists:car_models,id',
            'description' => 'nullable|string|max:1000',
        ]);
        
        // Сохраняем в сессию
        $request->session()->put('diagnostic.step1', [
            'symptoms' => $request->symptoms,
            'brand_id' => $request->brand_id,
            'model_id' => $request->model_id,
            'description' => $request->description,
        ]);
        
        // Перенаправляем на шаг 3 (GET)
        return redirect()->route('diagnostic.step3');
    }
    
    public function showstep3(Request $request)
    {
        // Проверяем данные шага 1
        if (!$request->session()->has('diagnostic.step1')) {
            return redirect()->route('diagnostic.start')
                ->with('error', 'Пожалуйста, сначала выберите симптомы и автомобиль');
        }
        
        return view('diagnostic.step3', [
            'showProgress' => true,
            'currentStep' => 3
        ]);
    }
    
    public function processStep3(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:1990|max:' . date('Y'),
            'engine_type' => 'required|string',
            'vin' => 'nullable|string|max:17',
            'mileage' => 'nullable|integer|min:0|max:1000000',
            'maintenance_history' => 'nullable|string',
        ]);
        
        // Сохраняем данные шага 3
        $request->session()->put('diagnostic.step3', [
            'year' => $request->year,
            'engine_type' => $request->engine_type,
            'vin' => $request->vin,
            'mileage' => $request->mileage,
            'maintenance_history' => $request->maintenance_history,
        ]);
        
        // Перенаправляем на страницу загрузки файлов
        return view('diagnostic.step3_files', [
            'showProgress' => true,
            'currentStep' => 3
        ]);
    }
    
    public function analyze(Request $request)
    {
        // Получаем все данные из сессии
        $step1Data = $request->session()->get('diagnostic.step1', []);
        $step3Data = $request->session()->get('diagnostic.step3', []);
        
        if (empty($step1Data['symptoms']) || !$step1Data['brand_id']) {
            return redirect()->route('diagnostic.start')->with('error', 'Недостаточно данных для анализа');
        }
        
        // Приводим к правильному типу
        $symptoms = array_map('intval', (array) $step1Data['symptoms']);
        $brandId = (int) $step1Data['brand_id'];
        $modelId = isset($step1Data['model_id']) ? (int) $step1Data['model_id'] : null;
        
        // Найти подходящие правила
        $rules = Rule::findMatchingRules($symptoms, $brandId, $modelId, $step3Data);
        
        if ($rules->isEmpty()) {
            // Если нет правил, создаем базовый результат
            return $this->createBasicAnalysis($brandId, $modelId, $symptoms, $step3Data, $step1Data['description'] ?? '', $request);
        }
        
        // Создать кейс
        $case = DiagnosticCase::create([
            'user_id' => Auth::id(),
            'rule_id' => $rules->first()->id,
            'brand_id' => $brandId,
            'model_id' => $modelId,
            'engine_type' => $step3Data['engine_type'] ?? null,
            'year' => $step3Data['year'] ?? null,
            'vin' => $step3Data['vin'] ?? null,
            'mileage' => $step3Data['mileage'] ?? null,
            'symptoms' => $symptoms,
            'description' => $step1Data['description'] ?? null,
            'status' => 'analyzing',
        ]);
        
        // Обработать загруженные файлы
        if ($request->hasFile('files')) {
            $uploadedFiles = [];
            foreach ($request->file('files') as $file) {
                $path = $file->store('diagnostic/' . $case->id, 'public');
                $uploadedFiles[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'type' => $file->getMimeType(),
                ];
            }
            $case->update(['uploaded_files' => $uploadedFiles]);
        }
        
        // Проанализировать и сгенерировать результат
        $analysisResult = $this->generateAnalysis($case, $rules);
        $case->completeAnalysis($analysisResult);
        
        // Очистить сессию
        $request->session()->forget([
            'diagnostic.step1',
            'diagnostic.step3'
        ]);
        
        return redirect()->route('diagnostic.result', $case->id);
    }
    
    public function result($caseId)
    {
        $case = DiagnosticCase::with(['brand', 'model', 'rule'])->findOrFail($caseId);
        
        // Проверка прав доступа
        if ($case->user_id !== Auth::id()) {
            abort(403, 'Доступ запрещён');
        }
        
        return view('diagnostic.result', [
            'case' => $case,
            'showProgress' => true,
            'currentStep' => 4
        ]);
    }
    
   
    
    private function generateAnalysis(DiagnosticCase $case, $rules): array
    {
        $rule = $rules->first();
        
        return [
            'possible_causes' => $rule->possible_causes ?? [],
            'required_data' => $rule->required_data ?? [],
            'diagnostic_steps' => $rule->diagnostic_steps ?? [],
            'complexity_level' => $rule->complexity_level ?? 1,
            'estimated_time' => $rule->estimated_time ?? 60,
            'estimated_price' => $rule->base_consultation_price * ($rule->complexity_level / 5),
            'recommended_consultation' => $case->getRecommendedConsultationType(),
        ];
    }

     private function createBasicAnalysis($brandId, $modelId, $symptoms, $carData, $description, $request)
    {
        // Создаем базовый кейс без правила
        $case = DiagnosticCase::create([
            'user_id' => Auth::id(),
            'rule_id' => null, // null для случаев без правила
            'brand_id' => $brandId,
            'model_id' => $modelId,
            'engine_type' => $carData['engine_type'] ?? null,
            'year' => $carData['year'] ?? null,
            'vin' => $carData['vin'] ?? null,
            'mileage' => $carData['mileage'] ?? null,
            'symptoms' => $symptoms,
            'description' => $description,
            'status' => 'report_ready',
        ]);
        
        // Обработать загруженные файлы
        if ($request->hasFile('files')) {
            $uploadedFiles = [];
            foreach ($request->file('files') as $file) {
                $path = $file->store('diagnostic/' . $case->id, 'public');
                $uploadedFiles[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'type' => $file->getMimeType(),
                ];
            }
            $case->update(['uploaded_files' => $uploadedFiles]);
        }
        
        // Генерируем базовый результат
        $analysisResult = [
            'possible_causes' => [
                'Необходима дополнительная диагностика',
                'Рекомендуется проверить электронные системы автомобиля',
                'Возможны проблемы с датчиками или исполнительными механизмами'
            ],
            'required_data' => [
                'Коды ошибок OBD2',
                'Фото приборной панели',
                'Информация о поведении автомобиля'
            ],
            'diagnostic_steps' => [
                'Считать коды ошибок с помощью диагностического сканера',
                'Проверить основные датчики системы',
                'Провести визуальный осмотр моторного отсека'
            ],
            'complexity_level' => 5,
            'estimated_time' => 90,
            'estimated_price' => 3000,
            'recommended_consultation' => 'expert',
        ];
        
        $case->completeAnalysis($analysisResult);
        
        return redirect()->route('diagnostic.result', $case->id);
    }


     public function start(Request $request)
{
    \Log::info('Consultation order form accessed with params:', $request->all());
    
    // Обрабатываем разные источники данных
    $consultationType = $request->input('type', 'basic');
    $symptomId = $request->input('symptom_id');
    $ruleId = $request->input('rule_id');
    $caseId = $request->input('case');
    $brandId = $request->input('brand_id');
    $modelId = $request->input('model_id');
    
    // Загружаем данные в зависимости от источника
    $symptomNames = [];
    $symptomIds = [];
    $rule = null;
    
    // Если передан симптом ID
    if ($symptomId) {
        $symptomIds = [$symptomId];
        $symptom = Symptom::find($symptomId);
        if ($symptom) {
            $symptomNames = [$symptom->name];
        }
    }
    
    // Если передан ID кейса
    if ($caseId) {
        $case = DiagnosticCase::find($caseId);
        if ($case) {
            // Получаем симптомы из кейса
            $symptomIds = $case->symptoms ?? [];
            if (!empty($symptomIds)) {
                $symptoms = Symptom::whereIn('id', $symptomIds)->get();
                $symptomNames = $symptoms->pluck('name')->toArray();
            }
            
            // Устанавливаем данные автомобиля
            $brandId = $brandId ?? $case->brand_id;
            $modelId = $modelId ?? $case->model_id;
            $year = $request->input('year') ?? $case->year;
            $mileage = $request->input('mileage') ?? $case->mileage;
            $engine_type = $request->input('engine_type') ?? $case->engine_type;
            $description = $request->input('description') ?? $case->description;
        }
    }
    
    // Если передан ID правила
    if ($ruleId) {
        $rule = Rule::find($ruleId);
        if ($rule) {
            // Получаем симптомы из правила
            $symptomIds = $rule->symptoms->pluck('id')->toArray();
            $symptomNames = $rule->symptoms->pluck('name')->toArray();
            
            // Устанавливаем данные автомобиля
            $brandId = $brandId ?? $rule->brand_id;
            $modelId = $modelId ?? $rule->model_id;
        }
    }
    
    // Загружаем марки автомобилей
    $brands = Brand::orderBy('name')->get();
    
    // Загружаем модели, если есть brand_id
    $models = collect();
    if ($brandId) {
        $models = CarModel::where('brand_id', $brandId)->orderBy('name')->get();
    }
    
    // Получаем данные из запроса
    $data = [
        'consultationType' => $consultationType,
        'rule' => $rule,
        'caseId' => $caseId,
        'symptoms' => $symptomIds,
        'symptom_names' => $symptomNames,
        'brands' => $brands,
        'models' => $models,
        'brand_id' => $brandId,
        'model_id' => $modelId,
        'year' => $request->input('year'),
        'mileage' => $request->input('mileage'),
        'engine_type' => $request->input('engine_type'),
        'description' => $request->input('description'),
    ];
    
    \Log::info('Data for consultation form:', $data);
    
    return view('diagnostic.consultation.order', $data);
}
    
}
