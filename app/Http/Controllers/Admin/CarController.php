<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CarModel;
use Illuminate\Http\Request;

class CarController extends Controller
{
    public function brands()
    {
        $brands = Brand::withCount('carModels')->orderBy('name')->paginate(50);
        return view('admin.cars.brands', compact('brands'));
    }

    public function models(Request $request)
    {
        $brandId = $request->get('brand_id');
        $models = CarModel::with('brand')
            ->when($brandId, function($query) use ($brandId) {
                return $query->where('brand_id', $brandId);
            })
            ->orderBy('name')
            ->paginate(50);
            
        $brands = Brand::orderBy('name')->get();
        
        return view('admin.cars.models', compact('models', 'brands', 'brandId'));
    }

    public function importForm()
    {
        return view('admin.cars.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_url' => 'nullable|url',
            'csv_file' => 'nullable|file|mimes:csv,txt'
        ]);

        $url = $request->csv_url;
        
        if ($request->hasFile('csv_file')) {
            // Обработка загруженного файла
            $file = $request->file('csv_file');
            $path = $file->store('temp');
            $url = storage_path('app/' . $path);
        }

        \Artisan::call('cars:import', [
            'url' => $url
        ]);

        return redirect()->route('admin.cars.brands')
            ->with('success', 'Данные автомобилей успешно импортированы');
    }
}