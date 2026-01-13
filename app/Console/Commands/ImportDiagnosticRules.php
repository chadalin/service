<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Diagnostic\Symptom;
use App\Models\Diagnostic\Rule;
use App\Models\Brand;
use App\Models\CarModel;

class ImportDiagnosticRules extends Command
{
    protected $signature = 'diagnostic:import-rules {file}';
    protected $description = 'Импорт правил диагностики из CSV/JSON файла';
    
    public function handle()
    {
        $file = $this->argument('file');
        
        if (!file_exists($file)) {
            $this->error("Файл не найден: {$file}");
            return 1;
        }
        
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        
        if ($extension === 'json') {
            $this->importFromJson($file);
        } elseif ($extension === 'csv') {
            $this->importFromCsv($file);
        } else {
            $this->error('Поддерживаются только JSON и CSV файлы');
            return 1;
        }
        
        $this->info('Правила успешно импортированы');
        return 0;
    }
    
    private function importFromJson(string $file): void
    {
        $data = json_decode(file_get_contents($file), true);
        
        if (!isset($data['rules'])) {
            $this->error('Неверный формат JSON файла');
            return;
        }
        
        $this->importRules($data['rules']);
    }
    
    private function importFromCsv(string $file): void
    {
        // TODO: Реализация импорта из CSV
        $this->warn('Импорт из CSV пока не реализован');
    }
    
    private function importRules(array $rules): void
    {
        $bar = $this->output->createProgressBar(count($rules));
        
        foreach ($rules as $ruleData) {
            // Найти симптом
            $symptom = Symptom::where('name', $ruleData['symptom'])->first();
            if (!$symptom) {
                $this->warn("Симптом не найден: {$ruleData['symptom']}");
                $bar->advance();
                continue;
            }
            
            // Найти бренд
            $brand = Brand::where('name', 'like', "%{$ruleData['brand']}%")->first();
            if (!$brand) {
                $this->warn("Бренд не найден: {$ruleData['brand']}");
                $bar->advance();
                continue;
            }
            
            // Найти модель (если указана)
            $model = null;
            if (!empty($ruleData['model'])) {
                $model = CarModel::where('brand_id', $brand->id)
                    ->where('name', 'like', "%{$ruleData['model']}%")
                    ->first();
            }
            
            Rule::create([
                'symptom_id' => $symptom->id,
                'brand_id' => $brand->id,
                'model_id' => $model?->id,
                'conditions' => $ruleData['conditions'] ?? [],
                'possible_causes' => $ruleData['possible_causes'] ?? [],
                'required_data' => $ruleData['required_data'] ?? [],
                'diagnostic_steps' => $ruleData['diagnostic_steps'] ?? [],
                'complexity_level' => $ruleData['complexity_level'] ?? 1,
                'estimated_time' => $ruleData['estimated_time'] ?? null,
                'base_consultation_price' => $ruleData['consultation_price'] ?? 3000,
                'is_active' => true,
            ]);
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
    }
}