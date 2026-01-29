<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PriceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PriceItemController extends Controller
{
    /**
     * Показать список прайс-листа
     */
    public function index(Request $request)
    {
        $query = PriceItem::query()->with(['brand', 'matchedSymptoms']);
        
        // Фильтрация по бренду
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }
        
        // Фильтрация по названию
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Сортировка
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        
        if (in_array($sortField, ['sku', 'name', 'price', 'quantity', 'created_at'])) {
            $query->orderBy($sortField, $sortDirection);
        }
        
        $priceItems = $query->paginate(50)->withQueryString();
        
        $brands = \App\Models\Brand::orderBy('name')->get();
        
        return view('admin.price.index', compact('priceItems', 'brands'));
    }
    
    /**
     * Показать детальную информацию о товаре
     */
    public function show(PriceItem $priceItem)
    {
        $priceItem->load(['brand', 'matchedSymptoms']);
        
        return view('admin.price.show', compact('priceItem'));
    }
    
    /**
     * Удалить товар из прайс-листа
     */
    public function destroy(PriceItem $priceItem)
    {
        try {
            $priceItem->delete();
            
            return redirect()->route('admin.price.index')
                ->with('success', 'Товар успешно удален из прайс-листа');
        } catch (\Exception $e) {
            Log::error('Error deleting price item:', [
                'error' => $e->getMessage(),
                'price_item_id' => $priceItem->id
            ]);
            
            return back()->with('error', 'Ошибка при удалении товара: ' . $e->getMessage());
        }
    }
    
    /**
     * Вручную найти совпадения с симптомами
     */
    public function matchSymptoms(PriceItem $priceItem)
    {
        try {
            $matches = $priceItem->findMatchingSymptoms(0.3);
            $priceItem->saveSymptomMatches($matches);
            
            $count = count($matches);
            
            return response()->json([
                'success' => true,
                'message' => "Найдено {$count} совпадений с симптомами",
                'matches_count' => $count
            ]);
        } catch (\Exception $e) {
            Log::error('Error matching symptoms:', [
                'error' => $e->getMessage(),
                'price_item_id' => $priceItem->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при поиске совпадений: ' . $e->getMessage()
            ], 500);
        }
    }
}