<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Brand;
use App\Models\CarModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ImportCarsData extends Command
{
    protected $signature = 'cars:import {url?}';
    protected $description = 'Import cars data from CSV';

    public function handle()
{
    $url = $this->argument('url') ?? 'https://raw.githubusercontent.com/blanzh/carsBase/master/cars.csv';
    
    $this->info("Downloading data from: {$url}");
    
    try {
        // Если это локальный файл
        if (file_exists($url)) {
            $csvData = file_get_contents($url);
        } else {
            $response = Http::get($url);
            
            if (!$response->successful()) {
                $this->error("Failed to download data");
                return 1;
            }
            
            $csvData = $response->body();
        }
        
        $lines = explode("\n", $csvData);
        
        $this->info("Processing " . (count($lines) - 1) . " records...");
        
        $brands = [];
        $models = [];
        $importedBrands = 0;
        $importedModels = 0;
        
        // Пропускаем заголовок
        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;
            
            $data = str_getcsv($line);
            
            if (count($data) < 13) {
                $this->warn("Skipping invalid line: {$line}");
                continue;
            }
            
            // Обрабатываем бренд
            $brandId = $data[0]; // ID_MARK
            if (!isset($brands[$brandId])) {
                $brands[$brandId] = [
                    'id' => $brandId,
                    'name' => $data[1], // Марка
                    'name_cyrillic' => $data[2], // Марка кириллица
                    'is_popular' => $data[3] === '1',
                    'country' => $data[4],
                    'year_from' => $data[5] ?: null,
                    'year_to' => $data[6] ?: null,
                ];
            }
            
            // Обрабатываем модель
            $modelId = $data[7]; // MODEL_ID
            $models[] = [
                'brand_id' => $brandId,
                'model_id' => $modelId,
                'name' => $data[8], // Модель
                'name_cyrillic' => $data[9], // Модель кириллица
                'class' => $data[10],
                'year_from' => $data[11] ?: null,
                'year_to' => $data[12] ?: null,
            ];
            
            if ($i % 100 === 0) {
                $this->info("Processed {$i} records...");
            }
        }
        
        // Импортируем бренды
        $this->info("Importing brands...");
        foreach ($brands as $brandData) {
            Brand::updateOrCreate(
                ['id' => $brandData['id']],
                [
                    'name' => $brandData['name'],
                    'name_cyrillic' => $brandData['name_cyrillic'],
                    'is_popular' => $brandData['is_popular'],
                    'country' => $brandData['country'],
                    'year_from' => $brandData['year_from'],
                    'year_to' => $brandData['year_to'],
                ]
            );
            $importedBrands++;
        }
        
        // Импортируем модели
        $this->info("Importing models...");
        foreach ($models as $modelData) {
            CarModel::updateOrCreate(
                ['model_id' => $modelData['model_id']],
                [
                    'brand_id' => $modelData['brand_id'],
                    'name' => $modelData['name'],
                    'name_cyrillic' => $modelData['name_cyrillic'],
                    'class' => $modelData['class'],
                    'year_from' => $modelData['year_from'],
                    'year_to' => $modelData['year_to'],
                ]
            );
            $importedModels++;
        }
        
        $this->info("Import completed!");
        $this->info("Brands: {$importedBrands}");
        $this->info("Models: {$importedModels}");
        
    } catch (\Exception $e) {
        $this->error("Error: " . $e->getMessage());
        return 1;
    }
    
    return 0;
}
}
// php artisan cars:import  команда для запуска импорта