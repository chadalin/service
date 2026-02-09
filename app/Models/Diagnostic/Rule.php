<?php

namespace App\Models\Diagnostic;

use App\Models\Brand;
use App\Models\CarModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rule extends Model
{
    protected $table = 'diagnostic_rules';
    
    protected $fillable = [
        'symptom_id', 'brand_id', 'model_id', 'conditions',
        'possible_causes', 'required_data', 'diagnostic_steps',
        'complexity_level', 'estimated_time', 'base_consultation_price',
        'order', 'is_active'
    ];
    
    protected $casts = [
        'conditions' => 'array',
        'possible_causes' => 'array',
        'required_data' => 'array',
        'diagnostic_steps' => 'array',
        'is_active' => 'boolean',
    ];
    
    public function symptom()
    {
        return $this->belongsTo(Symptom::class, 'symptom_id');
    }
    
    public function brand()
    {
        return $this->belongsTo(\App\Models\Brand::class, 'brand_id');
    }
    
    public function model()
    {
        return $this->belongsTo(\App\Models\CarModel::class, 'model_id');
    }
    
    // Поиск правил по условиям
   public static function findMatchingRules(array $symptoms, int $brandId, ?int $modelId = null, array $carData = [])
{
    return self::where('brand_id', $brandId)
        ->when($modelId, fn($q) => $q->where('model_id', $modelId))
        ->where('is_active', true)
        ->whereIn('symptom_id', $symptoms)
        ->get()
        ->filter(function ($rule) use ($carData) {
            // Получаем условия с проверкой на null
            $conditions = $rule->conditions ?? [];
            
            // Проверяем что это массив
            if (!is_array($conditions)) {
                $conditions = [];
            }
            
            // Если условий нет, правило подходит
            if (empty($conditions)) {
                return true;
            }
            
            foreach ($conditions as $key => $condition) {
                if (!isset($carData[$key])) {
                    return false;
                }
                
                // Простая проверка условий
                if (str_starts_with($condition, '>')) {
                    $value = (int) substr($condition, 1);
                    if ($carData[$key] <= $value) return false;
                } elseif (str_starts_with($condition, '<')) {
                    $value = (int) substr($condition, 1);
                    if ($carData[$key] >= $value) return false;
                } elseif ($carData[$key] != $condition) {
                    return false;
                }
            }
            return true;
        });
}
    
    // Рассчитать цену консультации на основе сложности
    public function calculatePrice(int $complexityMultiplier = 1): float
    {
        return $this->base_consultation_price * $complexityMultiplier;
    }
}