<?php

namespace App\Http\Controllers\Diagnostic\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Diagnostic\Symptom;
use App\Models\Diagnostic\Rule;
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
            //dd($brand);
            return view('admin.diagnostic.rules.show', [
                'rule' => $rule,
                'brands' => $brands,
                'title' => 'Правило диагностики: ' . ($rule->symptom->name ?? 'Unknown')
            ]);
        } catch (\Exception $e) {
            Log::error('Error showing rule', ['rule_id' => $id, 'error' => $e->getMessage()]);
            
            return redirect()->route('admin.diagnostic.rules.index')
                ->with('error', 'Правило не найдено: ' . $e->getMessage());
        }
    }
}