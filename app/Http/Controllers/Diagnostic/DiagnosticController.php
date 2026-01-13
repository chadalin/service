<?php

namespace App\Http\Controllers\Diagnostic;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Diagnostic\Symptom;
use App\Models\Diagnostic\Rule;
use App\Models\Diagnostic\Case as DiagnosticCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiagnosticController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function step1()
    {
        $symptoms = Symptom::where('is_active', true)->get();
        $brands = Brand::all();
        
        return view('diagnostic.step1', [
            'symptoms' => $symptoms,
            'brands' => $brands,
            'showProgress' => true,
            'currentStep' => 1
        ]);
    }
    
    public function getModels(Request $request, $brandId)
    {
        $models = CarModel::where('brand_id', $brandId)->get();
        return response()->json($models);
    }
    
    public function step2(Request $request)
    {
        $request->validate([
            'symptoms' => 'required|array',
            'brand_id' => 'required|exists:brands,id',
            'model_id' => 'nullable|exists:car_models,id',
        ]);
        
        $request->session()->put([
            'diagnostic.symptoms' => $request->symptoms,
            'diagnostic.brand_id' => $request->brand_id,
            'diagnostic.model_id' => $request->model_id,
        ]);
        
        return view('diagnostic.step2', [
            'showProgress' => true,
            'currentStep' => 2
        ]);
    }
    
    public function step3(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:1990|max:' . date('Y'),
            'engine_type' => 'required|string',
            'vin' => 'nullable|string|max:17',
            'mileage' => 'nullable|integer',
        ]);
        
        $request->session()->put([
            'diagnostic.car_data' => $request->only(['year', 'engine_type', 'vin', 'mileage']),
        ]);
        
        return view('diagnostic.step3', [
            'showProgress' => true,
            'currentStep' => 3
        ]);
    }
    
    public function analyze(Request $request)
    {
        $symptoms = $request->session()->get('diagnostic.symptoms', []);
        $brandId = $request->session()->get('diagnostic.brand_id');
        $modelId = $request->session()->get('diagnostic.model_id');
        $carData = $request->session()->get('diagnostic.car_data', []);
        
        if (empty($symptoms) || !$brandId) {
            return redirect()->route('diagnostic.start')->with('error', 'Недостаточно данных для анализа');
        }
        
        // Найти подходящие правила
        $rules = Rule::findMatchingRules($symptoms, $brandId, $modelId, $carData);
        
        if ($rules->isEmpty()) {
            return redirect()->back()->with('error', 'Не удалось найти подходящие решения. Попробуйте описать проблему более подробно.');
        }
        
        // Создать кейс
        $case = DiagnosticCase::create([
            'user_id' => Auth::id(),
            'rule_id' => $rules->first()->id,
            'brand_id' => $brandId,
            'model_id' => $modelId,
            'engine_type' => $carData['engine_type'] ?? null,
            'year' => $carData['year'] ?? null,
            'vin' => $carData['vin'] ?? null,
            'mileage' => $carData['mileage'] ?? null,
            'symptoms' => $symptoms,
            'description' => $request->description,
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
            'diagnostic.symptoms',
            'diagnostic.brand_id', 
            'diagnostic.model_id',
            'diagnostic.car_data'
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
        
        $report = $case->activeReport();
        
        return view('diagnostic.result', [
            'case' => $case,
            'report' => $report,
            'showProgress' => true,
            'currentStep' => 4
        ]);
    }
    
    public function orderConsultation(Request $request, $caseId)
    {
        $case = DiagnosticCase::findOrFail($caseId);
        $type = $request->input('type', 'basic');
        
        // Проверка прав доступа
        if ($case->user_id !== Auth::id()) {
            abort(403, 'Доступ запрещён');
        }
        
        // Рассчитать цену
        $price = $case->rule->calculatePrice(
            $type === 'expert' ? 1.5 : ($type === 'premium' ? 1.2 : 1)
        );
        
        // Создать запись о консультации
        $consultation = $case->consultation()->create([
            'user_id' => Auth::id(),
            'type' => $type,
            'price' => $price,
            'status' => 'pending',
        ]);
        
        // Перенаправить на оплату
        return redirect()->route('payment.checkout', $consultation->id)
            ->with('success', 'Консультация создана. Переходите к оплате.');
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
}