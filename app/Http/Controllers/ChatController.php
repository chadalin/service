<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SimpleSearchEngine;

class ChatController extends Controller
{
    public function index()
    {
        $brands = Brand::orderBy('name')->get();
        return view('chat.index', compact('brands'));
    }

    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:3',
            'brand_id' => 'nullable|exists:brands,id',
            'car_model_id' => 'nullable|exists:car_models,id'
        ]);

        $query = $request->input('query');
        $brandId = $request->input('brand_id');
        $carModelId = $request->input('car_model_id');

        // Сохраняем поисковый запрос
        if (auth()->check()) {
            \App\Models\SearchQuery::create([
                'user_id' => auth()->id(),
                'query_text' => $query,
                'car_model_id' => $carModelId,
                'results_count' => 0
            ]);
        }

        // Поиск документов
        $documents = Document::with(['carModel.brand', 'category'])
            ->when($carModelId, function($q) use ($carModelId) {
                return $q->where('car_model_id', $carModelId);
            })
            ->when($brandId && !$carModelId, function($q) use ($brandId) {
                return $q->whereHas('carModel', function($q) use ($brandId) {
                    $q->where('brand_id', $brandId);
                });
            })
            ->where(function($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('content_text', 'like', "%{$query}%");
            })
            ->where('status', 'processed')
            ->orderByRaw("
                CASE 
                    WHEN title LIKE ? THEN 1 
                    WHEN content_text LIKE ? THEN 2 
                    ELSE 3 
                END
            ", ["%{$query}%", "%{$query}%"])
            ->limit(20)
            ->get();

        // Обновляем счетчик результатов
        if (auth()->check()) {
            \App\Models\SearchQuery::latest()
                ->where('user_id', auth()->id())
                ->first()
                ->update(['results_count' => $documents->count()]);
        }

        return response()->json([
            'success' => true,
            'query' => $query,
            'results' => $documents,
            'count' => $documents->count()
        ]);
    }

    public function getModels($brandId)
    {
        $models = CarModel::where('brand_id', $brandId)
            ->orderBy('name')
            ->get(['id', 'name', 'name_cyrillic', 'year_from', 'year_to']);

        return response()->json($models);
    }
}