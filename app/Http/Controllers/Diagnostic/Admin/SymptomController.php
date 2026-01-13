<?php

namespace App\Http\Controllers\Diagnostic\Admin;

use App\Http\Controllers\Controller;
use App\Models\Diagnostic\Symptom;
use Illuminate\Http\Request;

class SymptomController extends Controller
{
    public function index()
    {
        $symptoms = Symptom::orderBy('frequency', 'desc')->paginate(20);
        return view('diagnostic.admin.symptoms.index', compact('symptoms'));
    }
    
    public function create()
    {
        return view('diagnostic.admin.symptoms.create');
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:diagnostic_symptoms',
            'description' => 'nullable|string',
            'related_systems' => 'nullable|array',
        ]);
        
        Symptom::create([
            'name' => $request->name,
            'slug' => \Illuminate\Support\Str::slug($request->name),
            'description' => $request->description,
            'related_systems' => $request->related_systems,
            'is_active' => $request->has('is_active'),
        ]);
        
        return redirect()->route('admin.diagnostic.symptoms.index')
            ->with('success', 'Симптом добавлен');
    }
    
    public function edit(Symptom $symptom)
    {
        return view('diagnostic.admin.symptoms.edit', compact('symptom'));
    }
    
    public function update(Request $request, Symptom $symptom)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:diagnostic_symptoms,name,' . $symptom->id,
            'description' => 'nullable|string',
            'related_systems' => 'nullable|array',
        ]);
        
        $symptom->update([
            'name' => $request->name,
            'slug' => \Illuminate\Support\Str::slug($request->name),
            'description' => $request->description,
            'related_systems' => $request->related_systems,
            'is_active' => $request->has('is_active'),
        ]);
        
        return redirect()->route('admin.diagnostic.symptoms.index')
            ->with('success', 'Симптом обновлён');
    }
    
    public function destroy(Symptom $symptom)
    {
        $symptom->delete();
        return redirect()->route('admin.diagnostic.symptoms.index')
            ->with('success', 'Симптом удалён');
    }
}